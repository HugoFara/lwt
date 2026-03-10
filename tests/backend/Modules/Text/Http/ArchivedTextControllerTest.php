<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Text\Http;

use Lwt\Modules\Text\Http\ArchivedTextController;
use Lwt\Modules\Text\Application\TextFacade;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Shared\Http\BaseController;
use Lwt\Shared\Infrastructure\Http\RedirectResponse;
use Lwt\Shared\Infrastructure\Globals;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for ArchivedTextController.
 *
 * Tests handleArchivedMarkAction branches and deleteArchived.
 */
class ArchivedTextControllerTest extends TestCase
{
    /** @var TextFacade&MockObject */
    private TextFacade $textService;

    /** @var LanguageFacade&MockObject */
    private LanguageFacade $languageService;

    private ArchivedTextController $controller;

    protected function setUp(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required');
        }
        $this->textService = $this->createMock(TextFacade::class);
        $this->languageService = $this->createMock(LanguageFacade::class);

        $this->controller = new ArchivedTextController(
            $this->textService,
            $this->languageService
        );
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    #[Test]
    public function constructorCreatesValidController(): void
    {
        $this->assertInstanceOf(ArchivedTextController::class, $this->controller);
    }

    #[Test]
    public function controllerExtendsBaseController(): void
    {
        $this->assertInstanceOf(BaseController::class, $this->controller);
    }

    // =========================================================================
    // handleArchivedMarkAction — empty marked array
    // =========================================================================

    #[Test]
    public function handleArchivedMarkActionReturnsZeroMessageWhenMarkedEmpty(): void
    {
        $result = $this->controller->handleArchivedMarkAction('del', [], '');
        $this->assertSame('Multiple Actions: 0', $result);
    }

    #[Test]
    public function handleArchivedMarkActionReturnsStringWhenMarkedEmpty(): void
    {
        $result = $this->controller->handleArchivedMarkAction('unarch', [], 'somedata');
        $this->assertIsString($result);
    }

    // =========================================================================
    // handleArchivedMarkAction — 'del' branch
    // =========================================================================

    #[Test]
    public function handleArchivedMarkActionDelCallsDeleteArchivedTexts(): void
    {
        $marked = [1, 2, 3];
        $this->textService->expects($this->once())
            ->method('deleteArchivedTexts')
            ->with($marked)
            ->willReturn(['count' => 3]);

        $result = $this->controller->handleArchivedMarkAction('del', $marked, '');
        $this->assertSame('Archived Texts deleted: 3', $result);
    }

    #[Test]
    public function handleArchivedMarkActionDelReturnsSingleDeleteMessage(): void
    {
        $marked = [42];
        $this->textService->expects($this->once())
            ->method('deleteArchivedTexts')
            ->with($marked)
            ->willReturn(['count' => 1]);

        $result = $this->controller->handleArchivedMarkAction('del', $marked, '');
        $this->assertSame('Archived Texts deleted: 1', $result);
    }

    // =========================================================================
    // handleArchivedMarkAction — 'unarch' branch
    // =========================================================================

    #[Test]
    public function handleArchivedMarkActionUnarchCallsUnarchiveTexts(): void
    {
        $marked = [5, 10];
        $this->textService->expects($this->once())
            ->method('unarchiveTexts')
            ->with($marked)
            ->willReturn(['count' => 2]);

        $result = $this->controller->handleArchivedMarkAction('unarch', $marked, '');
        $this->assertSame('Unarchived Text(s): 2', $result);
    }

    #[Test]
    public function handleArchivedMarkActionUnarchReturnsSingleCount(): void
    {
        $marked = [7];
        $this->textService->expects($this->once())
            ->method('unarchiveTexts')
            ->with($marked)
            ->willReturn(['count' => 1]);

        $result = $this->controller->handleArchivedMarkAction('unarch', $marked, '');
        $this->assertStringContainsString('1', $result);
    }

    // =========================================================================
    // handleArchivedMarkAction — 'deltag' branch
    // =========================================================================

    #[Test]
    public function handleArchivedMarkActionDeltagReturnsRedirectResponse(): void
    {
        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection required for TagsFacade');
        }

        $marked = [1, 2];
        $result = $this->controller->handleArchivedMarkAction('deltag', $marked, '5');
        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertSame('/text/archived', $result->getUrl());
    }

    // =========================================================================
    // handleArchivedMarkAction — 'addtag' branch
    // =========================================================================

    #[Test]
    public function handleArchivedMarkActionAddtagReturnsString(): void
    {
        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection required for TagsFacade');
        }

        $marked = [1, 2];
        // TagsFacade::addTagToArchivedTexts is static, so we just verify the
        // return type is string (not RedirectResponse).
        $result = $this->controller->handleArchivedMarkAction('addtag', $marked, '3');
        $this->assertIsString($result);
    }

    // =========================================================================
    // handleArchivedMarkAction — unknown action
    // =========================================================================

    #[Test]
    public function handleArchivedMarkActionUnknownActionReturnsDefaultMessage(): void
    {
        $marked = [1];
        $result = $this->controller->handleArchivedMarkAction('unknown', $marked, '');
        $this->assertSame('Multiple Actions: 0', $result);
    }

    // =========================================================================
    // deleteArchived
    // =========================================================================

    #[Test]
    public function deleteArchivedCallsServiceAndReturnsRedirect(): void
    {
        $this->textService->expects($this->once())
            ->method('deleteArchivedText')
            ->with(42);

        $result = $this->controller->deleteArchived(42);
        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertSame('/text/archived', $result->getUrl());
    }

    #[Test]
    public function deleteArchivedReturns302StatusCode(): void
    {
        $this->textService->method('deleteArchivedText');

        $result = $this->controller->deleteArchived(1);
        $this->assertSame(302, $result->getStatusCode());
    }

    // =========================================================================
    // Method signature tests
    // =========================================================================

    #[Test]
    public function handleArchivedMarkActionIsPublic(): void
    {
        $method = new \ReflectionMethod(
            ArchivedTextController::class,
            'handleArchivedMarkAction'
        );
        $this->assertTrue($method->isPublic());
        $this->assertCount(3, $method->getParameters());
    }

    #[Test]
    public function deleteArchivedAcceptsIntParameter(): void
    {
        $method = new \ReflectionMethod(
            ArchivedTextController::class,
            'deleteArchived'
        );
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('int', $params[0]->getType()->getName());
    }
}
