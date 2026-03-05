<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Text\Http;

use Lwt\Modules\Text\Http\TextController;
use Lwt\Modules\Text\Application\TextFacade;
use Lwt\Modules\Text\Application\Services\TextDisplayService;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Shared\Infrastructure\Http\RedirectResponse;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for TextController.
 *
 * Tests text management, reading, display, archive/unarchive,
 * mark actions, class structure, and method signatures.
 */
class TextControllerTest extends TestCase
{
    /** @var TextFacade&MockObject */
    private TextFacade $textService;

    /** @var LanguageFacade&MockObject */
    private LanguageFacade $languageService;

    /** @var TextDisplayService&MockObject */
    private TextDisplayService $displayService;

    private TextController $controller;

    protected function setUp(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required');
        }
        $this->textService = $this->createMock(TextFacade::class);
        $this->languageService = $this->createMock(LanguageFacade::class);
        $this->displayService = $this->createMock(TextDisplayService::class);

        $this->controller = new TextController(
            $this->textService,
            $this->languageService,
            $this->displayService
        );
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    #[Test]
    public function constructorCreatesValidController(): void
    {
        $this->assertInstanceOf(TextController::class, $this->controller);
    }

    #[Test]
    public function constructorAcceptsNullParameters(): void
    {
        $controller = new TextController(null, null, null);
        $this->assertInstanceOf(TextController::class, $controller);
    }

    #[Test]
    public function constructorSetsTextServiceProperty(): void
    {
        $reflection = new \ReflectionProperty(TextController::class, 'textService');
        $reflection->setAccessible(true);

        $this->assertSame($this->textService, $reflection->getValue($this->controller));
    }

    #[Test]
    public function constructorSetsLanguageServiceProperty(): void
    {
        $reflection = new \ReflectionProperty(TextController::class, 'languageService');
        $reflection->setAccessible(true);

        $this->assertSame($this->languageService, $reflection->getValue($this->controller));
    }

    #[Test]
    public function constructorSetsDisplayServiceProperty(): void
    {
        $reflection = new \ReflectionProperty(TextController::class, 'displayService');
        $reflection->setAccessible(true);

        $this->assertSame($this->displayService, $reflection->getValue($this->controller));
    }

    #[Test]
    public function constructorWithNullCreatesDefaultServices(): void
    {
        $controller = new TextController(null, null, null);
        $reflection = new \ReflectionProperty(TextController::class, 'textService');
        $reflection->setAccessible(true);

        $this->assertInstanceOf(TextFacade::class, $reflection->getValue($controller));
    }

    // =========================================================================
    // Class structure tests
    // =========================================================================

    #[Test]
    public function classExtendsBaseController(): void
    {
        $reflection = new \ReflectionClass(TextController::class);
        $this->assertSame(
            'Lwt\Shared\Http\BaseController',
            $reflection->getParentClass()->getName()
        );
    }

    #[Test]
    public function classHasRequiredPublicMethods(): void
    {
        $reflection = new \ReflectionClass(TextController::class);

        $expectedMethods = [
            'read', 'new', 'editSingle', 'delete', 'archive',
            'unarchive', 'edit', 'display', 'setMode', 'check',
            'archived', 'archivedEdit', 'deleteArchived',
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "TextController should have method: $methodName"
            );
            $method = $reflection->getMethod($methodName);
            $this->assertTrue(
                $method->isPublic(),
                "Method $methodName should be public"
            );
        }
    }

    #[Test]
    public function classHasRequiredPrivateMethods(): void
    {
        $reflection = new \ReflectionClass(TextController::class);

        $expectedMethods = [
            'getTextIdFromRequest', 'renderReadPage', 'handleMarkAction',
            'handleTextOperation', 'handleAutoSplitImport', 'showNewTextForm',
            'showEditTextForm', 'showTextsList', 'handleArchivedMarkAction',
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "TextController should have private method: $methodName"
            );
            $method = $reflection->getMethod($methodName);
            $this->assertTrue(
                $method->isPrivate(),
                "Method $methodName should be private"
            );
        }
    }

    #[Test]
    public function moduleViewsConstantPointsToViewsDirectory(): void
    {
        $reflection = new \ReflectionClassConstant(TextController::class, 'MODULE_VIEWS');
        $value = $reflection->getValue();

        $this->assertStringEndsWith('/Views', $value);
    }

    // =========================================================================
    // read() method tests
    // =========================================================================

    #[Test]
    public function readRedirectsWhenNoTextId(): void
    {
        $result = $this->controller->read(null);

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    #[Test]
    public function readRedirectsWhenTextNotFound(): void
    {
        $this->textService->expects($this->once())
            ->method('getTextForReading')
            ->with(999)
            ->willReturn(null);

        $result = $this->controller->read(999);

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    // =========================================================================
    // delete() method tests
    // =========================================================================

    #[Test]
    public function deleteCallsServiceAndRedirects(): void
    {
        $this->textService->expects($this->once())
            ->method('deleteText')
            ->with(42);

        $result = $this->controller->delete(42);

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    #[Test]
    public function deleteRedirectsToTextsList(): void
    {
        $this->textService->method('deleteText')->willReturn(['sentences' => 0, 'textItems' => 0]);

        $result = $this->controller->delete(1);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertSame('/texts', $result->getUrl());
    }

    // =========================================================================
    // archive() method tests
    // =========================================================================

    #[Test]
    public function archiveCallsServiceAndRedirects(): void
    {
        $this->textService->expects($this->once())
            ->method('archiveText')
            ->with(42);

        $result = $this->controller->archive(42);

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    #[Test]
    public function archiveRedirectsToTextsList(): void
    {
        $this->textService->method('archiveText')->willReturn(['sentences' => 0, 'textItems' => 0]);

        $result = $this->controller->archive(1);

        $this->assertSame('/texts', $result->getUrl());
    }

    // =========================================================================
    // unarchive() method tests
    // =========================================================================

    #[Test]
    public function unarchiveCallsServiceAndRedirects(): void
    {
        $this->textService->expects($this->once())
            ->method('unarchiveText')
            ->with(42);

        $result = $this->controller->unarchive(42);

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    #[Test]
    public function unarchiveRedirectsToArchivedTextsList(): void
    {
        $this->textService->method('unarchiveText')
            ->willReturn(['success' => true, 'sentences' => 0, 'textItems' => 0]);

        $result = $this->controller->unarchive(1);

        $this->assertSame('/text/archived', $result->getUrl());
    }

    // =========================================================================
    // deleteArchived() method tests
    // =========================================================================

    #[Test]
    public function deleteArchivedCallsServiceAndRedirects(): void
    {
        $this->textService->expects($this->once())
            ->method('deleteArchivedText')
            ->with(42);

        $result = $this->controller->deleteArchived(42);

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    #[Test]
    public function deleteArchivedRedirectsToArchivedList(): void
    {
        $this->textService->method('deleteArchivedText')->willReturn([]);

        $result = $this->controller->deleteArchived(1);

        $this->assertSame('/text/archived', $result->getUrl());
    }

    // =========================================================================
    // getTextIdFromRequest() private method tests via reflection
    // =========================================================================

    #[Test]
    public function getTextIdFromRequestReturnsInjectedId(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'getTextIdFromRequest');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, 123);

        $this->assertSame(123, $result);
    }

    #[Test]
    public function getTextIdFromRequestReturnsNullWhenEmpty(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'getTextIdFromRequest');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, null);

        $this->assertNull($result);
    }

    #[Test]
    public function getTextIdFromRequestPrioritizesInjectedOverQueryParam(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'getTextIdFromRequest');
        $method->setAccessible(true);

        // Even if query params exist, injected takes priority
        $result = $method->invoke($this->controller, 42);

        $this->assertSame(42, $result);
    }

    #[Test]
    public function getTextIdFromRequestReturnsZeroInjectedId(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'getTextIdFromRequest');
        $method->setAccessible(true);

        // 0 is not null, should be returned
        $result = $method->invoke($this->controller, 0);

        $this->assertSame(0, $result);
    }

    // =========================================================================
    // handleMarkAction() private method tests via reflection
    // =========================================================================

    #[Test]
    public function handleMarkActionReturnsDefaultForEmptyMarked(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'handleMarkAction');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, 'del', [], '');

        $this->assertSame('Multiple Actions: 0', $result);
    }

    #[Test]
    public function handleMarkActionDeleteCallsService(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'handleMarkAction');
        $method->setAccessible(true);

        $this->textService->expects($this->once())
            ->method('deleteTexts')
            ->with([1, 2, 3])
            ->willReturn(['count' => 3]);

        $result = $method->invoke($this->controller, 'del', ['1', '2', '3'], '');

        $this->assertSame('Texts deleted: 3', $result);
    }

    #[Test]
    public function handleMarkActionArchiveCallsService(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'handleMarkAction');
        $method->setAccessible(true);

        $this->textService->expects($this->once())
            ->method('archiveTexts')
            ->with([1, 2])
            ->willReturn(['count' => 2]);

        $result = $method->invoke($this->controller, 'arch', ['1', '2'], '');

        $this->assertSame('Archived Text(s): 2', $result);
    }

    #[Test]
    public function handleMarkActionSetSentencesCallsService(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'handleMarkAction');
        $method->setAccessible(true);

        $this->textService->expects($this->once())
            ->method('setTermSentences')
            ->with([1], false)
            ->willReturn(5);

        $result = $method->invoke($this->controller, 'setsent', ['1'], '');

        $this->assertSame('Term sentences set: 5', $result);
    }

    #[Test]
    public function handleMarkActionSetActiveSentencesCallsService(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'handleMarkAction');
        $method->setAccessible(true);

        $this->textService->expects($this->once())
            ->method('setTermSentences')
            ->with([1], true)
            ->willReturn(3);

        $result = $method->invoke($this->controller, 'setactsent', ['1'], '');

        $this->assertSame('Active term sentences set: 3', $result);
    }

    #[Test]
    public function handleMarkActionRebuildCallsService(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'handleMarkAction');
        $method->setAccessible(true);

        $this->textService->expects($this->once())
            ->method('rebuildTexts')
            ->with([1, 2])
            ->willReturn(2);

        $result = $method->invoke($this->controller, 'rebuild', ['1', '2'], '');

        $this->assertSame('Rebuilt Text(s): 2', $result);
    }

    #[Test]
    public function handleMarkActionReviewReturnsRedirect(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'handleMarkAction');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, 'review', ['1', '2'], '');

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    #[Test]
    public function handleMarkActionReviewRedirectsToReview(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'handleMarkAction');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, 'review', ['1'], '');

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertSame('/review?selection=3', $result->getUrl());
    }

    #[Test]
    public function handleMarkActionDeltagReturnsRedirect(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'handleMarkAction');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, 'deltag', ['1'], 'tag');

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    #[Test]
    public function handleMarkActionDeltagRedirectsToTexts(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'handleMarkAction');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, 'deltag', ['1'], 'tag');

        $this->assertSame('/texts', $result->getUrl());
    }

    #[Test]
    public function handleMarkActionUnknownActionReturnsDefault(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'handleMarkAction');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, 'unknownaction', ['1'], '');

        $this->assertSame('Multiple Actions: 0', $result);
    }

    // =========================================================================
    // handleArchivedMarkAction() private method tests via reflection
    // =========================================================================

    #[Test]
    public function handleArchivedMarkActionReturnsDefaultForEmptyMarked(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'handleArchivedMarkAction');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, 'del', [], '');

        $this->assertSame('Multiple Actions: 0', $result);
    }

    #[Test]
    public function handleArchivedMarkActionDeleteCallsService(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'handleArchivedMarkAction');
        $method->setAccessible(true);

        $this->textService->expects($this->once())
            ->method('deleteArchivedTexts')
            ->with([1, 2])
            ->willReturn(['count' => 2]);

        $result = $method->invoke($this->controller, 'del', ['1', '2'], '');

        $this->assertSame('Archived Texts deleted: 2', $result);
    }

    #[Test]
    public function handleArchivedMarkActionUnarchiveCallsService(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'handleArchivedMarkAction');
        $method->setAccessible(true);

        $this->textService->expects($this->once())
            ->method('unarchiveTexts')
            ->with([1])
            ->willReturn(['count' => 1]);

        $result = $method->invoke($this->controller, 'unarch', ['1'], '');

        $this->assertSame('Unarchived Text(s): 1', $result);
    }

    #[Test]
    public function handleArchivedMarkActionDeltagReturnsRedirect(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'handleArchivedMarkAction');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, 'deltag', ['1'], 'tag');

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    #[Test]
    public function handleArchivedMarkActionDeltagRedirectsToArchived(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'handleArchivedMarkAction');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, 'deltag', ['1'], 'tag');

        $this->assertSame('/text/archived', $result->getUrl());
    }

    #[Test]
    public function handleArchivedMarkActionUnknownReturnsDefault(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'handleArchivedMarkAction');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, 'unknownaction', ['1'], '');

        $this->assertSame('Multiple Actions: 0', $result);
    }

    // =========================================================================
    // display() method tests
    // =========================================================================

    #[Test]
    public function displayRedirectsWhenTextIdIsZero(): void
    {
        $result = $this->controller->display(0);

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    #[Test]
    public function displayRedirectsWhenAnnotatedTextEmpty(): void
    {
        $this->displayService->expects($this->once())
            ->method('getAnnotatedText')
            ->with(42)
            ->willReturn('');

        $result = $this->controller->display(42);

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    #[Test]
    public function displayRedirectsWhenSettingsNull(): void
    {
        $this->displayService->method('getAnnotatedText')
            ->willReturn('some annotation text');
        $this->displayService->method('getTextDisplaySettings')
            ->with(42)
            ->willReturn(null);

        $result = $this->controller->display(42);

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    #[Test]
    public function displayRedirectsWhenHeaderDataNull(): void
    {
        $this->displayService->method('getAnnotatedText')
            ->willReturn('some annotation text');
        $this->displayService->method('getTextDisplaySettings')
            ->willReturn(['textSize' => 100, 'rtlScript' => false]);
        $this->displayService->method('getHeaderData')
            ->with(42)
            ->willReturn(null);

        $result = $this->controller->display(42);

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    #[Test]
    public function displayRedirectsWhenNullPassedAndNoQueryParam(): void
    {
        // null defaults to 0, which should redirect
        $result = $this->controller->display(null);

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    // =========================================================================
    // Method signature tests
    // =========================================================================

    #[Test]
    public function readMethodAcceptsNullableInt(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'read');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('text', $params[0]->getName());
        $this->assertTrue($params[0]->allowsNull());
    }

    #[Test]
    public function deleteMethodAcceptsInt(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'delete');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertFalse($params[0]->allowsNull());
    }

    #[Test]
    public function archiveMethodAcceptsInt(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'archive');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    #[Test]
    public function editSingleMethodAcceptsInt(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'editSingle');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    #[Test]
    public function deleteReturnsRedirectResponse(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'delete');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame(RedirectResponse::class, $returnType->getName());
    }

    #[Test]
    public function archiveReturnsRedirectResponse(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'archive');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame(RedirectResponse::class, $returnType->getName());
    }

    #[Test]
    public function unarchiveReturnsRedirectResponse(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'unarchive');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame(RedirectResponse::class, $returnType->getName());
    }

    #[Test]
    public function readReturnsNullableRedirectResponse(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'read');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull());
    }

    #[Test]
    public function displayReturnsNullableRedirectResponse(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'display');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull());
    }

    #[Test]
    public function editMethodAcceptsArrayParams(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'edit');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
    }

    #[Test]
    public function newMethodAcceptsArrayParams(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'new');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
    }

    #[Test]
    public function setModeReturnsVoid(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'setMode');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('void', $returnType->getName());
    }

    #[Test]
    public function checkReturnsVoid(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'check');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('void', $returnType->getName());
    }

    #[Test]
    public function archivedEditAcceptsIntParam(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'archivedEdit');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    #[Test]
    public function deleteArchivedReturnsRedirectResponse(): void
    {
        $method = new \ReflectionMethod(TextController::class, 'deleteArchived');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame(RedirectResponse::class, $returnType->getName());
    }
}
