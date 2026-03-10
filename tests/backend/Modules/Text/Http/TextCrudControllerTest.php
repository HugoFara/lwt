<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Text\Http;

use Lwt\Modules\Text\Http\TextCrudController;
use Lwt\Modules\Text\Application\TextFacade;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Shared\Infrastructure\Http\RedirectResponse;
use Lwt\Shared\Infrastructure\Globals;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for TextCrudController.
 *
 * Covers handleMarkAction branches, delete, archive, unarchive.
 */
class TextCrudControllerTest extends TestCase
{
    /** @var TextFacade&MockObject */
    private TextFacade $textService;

    /** @var LanguageFacade&MockObject */
    private LanguageFacade $languageService;

    private TextCrudController $controller;

    protected function setUp(): void
    {
        $this->textService = $this->createMock(TextFacade::class);
        $this->languageService = $this->createMock(LanguageFacade::class);

        $this->controller = new TextCrudController(
            $this->textService,
            $this->languageService
        );
    }

    // =========================================================================
    // Class structure
    // =========================================================================

    #[Test]
    public function classExtendsBaseController(): void
    {
        $reflection = new \ReflectionClass(TextCrudController::class);
        $this->assertSame(
            'Lwt\Shared\Http\BaseController',
            $reflection->getParentClass()->getName()
        );
    }

    #[Test]
    public function constructorAcceptsMocks(): void
    {
        $this->assertInstanceOf(TextCrudController::class, $this->controller);
    }

    #[Test]
    public function handleMarkActionIsPublic(): void
    {
        $method = new \ReflectionMethod(TextCrudController::class, 'handleMarkAction');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function handleMarkActionHasCorrectParameters(): void
    {
        $method = new \ReflectionMethod(TextCrudController::class, 'handleMarkAction');
        $params = $method->getParameters();
        $this->assertCount(3, $params);
        $this->assertSame('markAction', $params[0]->getName());
        $this->assertSame('marked', $params[1]->getName());
        $this->assertSame('actionData', $params[2]->getName());
    }

    // =========================================================================
    // delete()
    // =========================================================================

    #[Test]
    public function deleteCallsTextServiceAndRedirects(): void
    {
        $this->textService->expects($this->once())
            ->method('deleteText')
            ->with(42);

        $result = $this->controller->delete(42);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertSame('/texts', $result->getUrl());
    }

    #[Test]
    public function deleteWithDifferentId(): void
    {
        $this->textService->expects($this->once())
            ->method('deleteText')
            ->with(999);

        $result = $this->controller->delete(999);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertSame('/texts', $result->getUrl());
    }

    // =========================================================================
    // archive()
    // =========================================================================

    #[Test]
    public function archiveCallsTextServiceAndRedirects(): void
    {
        $this->textService->expects($this->once())
            ->method('archiveText')
            ->with(17);

        $result = $this->controller->archive(17);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertSame('/texts', $result->getUrl());
    }

    #[Test]
    public function archiveWithDifferentId(): void
    {
        $this->textService->expects($this->once())
            ->method('archiveText')
            ->with(500);

        $result = $this->controller->archive(500);
        $this->assertSame('/texts', $result->getUrl());
    }

    // =========================================================================
    // unarchive()
    // =========================================================================

    #[Test]
    public function unarchiveCallsTextServiceAndRedirects(): void
    {
        $this->textService->expects($this->once())
            ->method('unarchiveText')
            ->with(33);

        $result = $this->controller->unarchive(33);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertSame('/text/archived', $result->getUrl());
    }

    #[Test]
    public function unarchiveRedirectsDifferentFromArchive(): void
    {
        $this->textService->expects($this->once())
            ->method('unarchiveText')
            ->with(1);

        $archiveResult = $this->controller->unarchive(1);

        $this->assertNotSame('/texts', $archiveResult->getUrl());
        $this->assertSame('/text/archived', $archiveResult->getUrl());
    }

    // =========================================================================
    // handleMarkAction() — empty marked array
    // =========================================================================

    #[Test]
    public function handleMarkActionWithEmptyMarkedReturnsZeroMessage(): void
    {
        $result = $this->controller->handleMarkAction('del', [], '');

        $this->assertIsString($result);
        $this->assertSame('Multiple Actions: 0', $result);
    }

    #[Test]
    public function handleMarkActionWithEmptyMarkedDoesNotCallService(): void
    {
        $this->textService->expects($this->never())
            ->method('deleteTexts');
        $this->textService->expects($this->never())
            ->method('archiveTexts');

        $this->controller->handleMarkAction('del', [], '');
    }

    // =========================================================================
    // handleMarkAction() — 'del' branch
    // =========================================================================

    #[Test]
    public function handleMarkActionDelDeletesAndReturnsMessage(): void
    {
        $marked = ['1', '2', '3'];
        $this->textService->expects($this->once())
            ->method('deleteTexts')
            ->with($marked)
            ->willReturn(['count' => 3]);

        $result = $this->controller->handleMarkAction('del', $marked, '');

        $this->assertIsString($result);
        $this->assertSame('Texts deleted: 3', $result);
    }

    #[Test]
    public function handleMarkActionDelSingleText(): void
    {
        $marked = ['55'];
        $this->textService->expects($this->once())
            ->method('deleteTexts')
            ->with($marked)
            ->willReturn(['count' => 1]);

        $result = $this->controller->handleMarkAction('del', $marked, '');
        $this->assertStringContainsString('1', $result);
    }

    // =========================================================================
    // handleMarkAction() — 'arch' branch
    // =========================================================================

    #[Test]
    public function handleMarkActionArchArchivesAndReturnsMessage(): void
    {
        $marked = ['10', '20'];
        $this->textService->expects($this->once())
            ->method('archiveTexts')
            ->with($marked)
            ->willReturn(['count' => 2]);

        $result = $this->controller->handleMarkAction('arch', $marked, '');

        $this->assertIsString($result);
        $this->assertSame('Archived Text(s): 2', $result);
    }

    // =========================================================================
    // handleMarkAction() — 'setsent' branch
    // =========================================================================

    #[Test]
    public function handleMarkActionSetsentCallsSetTermSentences(): void
    {
        $marked = ['5', '6'];
        $this->textService->expects($this->once())
            ->method('setTermSentences')
            ->with($marked, false)
            ->willReturn(10);

        $result = $this->controller->handleMarkAction('setsent', $marked, '');

        $this->assertIsString($result);
        $this->assertSame('Term sentences set: 10', $result);
    }

    // =========================================================================
    // handleMarkAction() — 'setactsent' branch
    // =========================================================================

    #[Test]
    public function handleMarkActionSetactsentCallsWithActiveFlag(): void
    {
        $marked = ['7', '8'];
        $this->textService->expects($this->once())
            ->method('setTermSentences')
            ->with($marked, true)
            ->willReturn(5);

        $result = $this->controller->handleMarkAction('setactsent', $marked, '');

        $this->assertIsString($result);
        $this->assertSame('Active term sentences set: 5', $result);
    }

    // =========================================================================
    // handleMarkAction() — 'rebuild' branch
    // =========================================================================

    #[Test]
    public function handleMarkActionRebuildReturnsCount(): void
    {
        $marked = ['11', '12', '13'];
        $this->textService->expects($this->once())
            ->method('rebuildTexts')
            ->with($marked)
            ->willReturn(3);

        $result = $this->controller->handleMarkAction('rebuild', $marked, '');

        $this->assertIsString($result);
        $this->assertSame('Rebuilt Text(s): 3', $result);
    }

    // =========================================================================
    // handleMarkAction() — 'review' branch
    // =========================================================================

    #[Test]
    public function handleMarkActionReviewReturnsRedirect(): void
    {
        $marked = ['1', '2'];

        $result = $this->controller->handleMarkAction('review', $marked, '');

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertSame('/review?selection=3', $result->getUrl());
    }

    // =========================================================================
    // handleMarkAction() — 'deltag' branch
    // =========================================================================

    #[Test]
    public function handleMarkActionDeltagReturnsRedirect(): void
    {
        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection required for TagsFacade');
        }

        $marked = ['1'];

        // deltag calls static TagsFacade::removeTagFromTexts then redirects
        $result = $this->controller->handleMarkAction('deltag', $marked, '5');

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertSame('/texts', $result->getUrl());
    }

    // =========================================================================
    // handleMarkAction() — 'addtag' branch
    // =========================================================================

    #[Test]
    public function handleMarkActionAddtagReturnsString(): void
    {
        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection required for TagsFacade');
        }

        $marked = ['1', '2'];

        // addtag calls static TagsFacade::addTagToTexts — we can't mock it
        // but we verify the return is a string (not a redirect)
        $result = $this->controller->handleMarkAction('addtag', $marked, '3');

        $this->assertIsString($result);
    }

    // =========================================================================
    // handleMarkAction() — unknown action
    // =========================================================================

    #[Test]
    public function handleMarkActionUnknownActionReturnsDefaultMessage(): void
    {
        $marked = ['1'];

        $result = $this->controller->handleMarkAction('unknown_action', $marked, '');

        // Falls through the switch with the default message
        $this->assertIsString($result);
        $this->assertSame('Multiple Actions: 0', $result);
    }

    // =========================================================================
    // Method return types
    // =========================================================================

    #[Test]
    public function deleteReturnTypeIsRedirectResponse(): void
    {
        $this->textService->method('deleteText');
        $result = $this->controller->delete(1);
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    #[Test]
    public function archiveReturnTypeIsRedirectResponse(): void
    {
        $this->textService->method('archiveText');
        $result = $this->controller->archive(1);
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    #[Test]
    public function unarchiveReturnTypeIsRedirectResponse(): void
    {
        $this->textService->method('unarchiveText');
        $result = $this->controller->unarchive(1);
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }
}
