<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Text\Http;

use Lwt\Modules\Text\Http\TextController;
use Lwt\Modules\Text\Application\TextFacade;
use Lwt\Modules\Text\Application\Services\TextDisplayService;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Shared\Infrastructure\Http\RedirectResponse;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for TextController.
 *
 * Tests text management, reading, and display functionality.
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

    public function testConstructorCreatesValidController(): void
    {
        $this->assertInstanceOf(TextController::class, $this->controller);
    }

    public function testConstructorAcceptsNullParameters(): void
    {
        $controller = new TextController(null, null, null);
        $this->assertInstanceOf(TextController::class, $controller);
    }

    // =========================================================================
    // Private method tests via reflection
    // =========================================================================

    public function testGetTextIdFromRequestWithInjectedId(): void
    {
        $reflection = new \ReflectionMethod(TextController::class, 'getTextIdFromRequest');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($this->controller, 123);

        $this->assertSame(123, $result);
    }

    public function testGetTextIdFromRequestReturnsNullWhenEmpty(): void
    {
        $reflection = new \ReflectionMethod(TextController::class, 'getTextIdFromRequest');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($this->controller, null);

        // Without query params set, should return null
        $this->assertNull($result);
    }

    // =========================================================================
    // handleMarkAction tests via reflection
    // =========================================================================

    public function testHandleMarkActionReturnsDefaultForEmptyMarked(): void
    {
        $reflection = new \ReflectionMethod(TextController::class, 'handleMarkAction');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($this->controller, 'del', [], '');

        $this->assertSame('Multiple Actions: 0', $result);
    }

    public function testHandleMarkActionDeleteCallsService(): void
    {
        $reflection = new \ReflectionMethod(TextController::class, 'handleMarkAction');
        $reflection->setAccessible(true);

        $this->textService->expects($this->once())
            ->method('deleteTexts')
            ->with([1, 2, 3])
            ->willReturn(['count' => 3]);

        $result = $reflection->invoke($this->controller, 'del', ['1', '2', '3'], '');

        $this->assertSame('Texts deleted: 3', $result);
    }

    public function testHandleMarkActionArchiveCallsService(): void
    {
        $reflection = new \ReflectionMethod(TextController::class, 'handleMarkAction');
        $reflection->setAccessible(true);

        $this->textService->expects($this->once())
            ->method('archiveTexts')
            ->with([1, 2])
            ->willReturn(['count' => 2]);

        $result = $reflection->invoke($this->controller, 'arch', ['1', '2'], '');

        $this->assertSame('Archived Text(s): 2', $result);
    }

    public function testHandleMarkActionSetSentencesCallsService(): void
    {
        $reflection = new \ReflectionMethod(TextController::class, 'handleMarkAction');
        $reflection->setAccessible(true);

        $this->textService->expects($this->once())
            ->method('setTermSentences')
            ->with([1], false)
            ->willReturn(5);

        $result = $reflection->invoke($this->controller, 'setsent', ['1'], '');

        $this->assertSame('Term sentences set: 5', $result);
    }

    public function testHandleMarkActionSetActiveSentencesCallsService(): void
    {
        $reflection = new \ReflectionMethod(TextController::class, 'handleMarkAction');
        $reflection->setAccessible(true);

        $this->textService->expects($this->once())
            ->method('setTermSentences')
            ->with([1], true)
            ->willReturn(3);

        $result = $reflection->invoke($this->controller, 'setactsent', ['1'], '');

        $this->assertSame('Active term sentences set: 3', $result);
    }

    public function testHandleMarkActionRebuildCallsService(): void
    {
        $reflection = new \ReflectionMethod(TextController::class, 'handleMarkAction');
        $reflection->setAccessible(true);

        $this->textService->expects($this->once())
            ->method('rebuildTexts')
            ->with([1, 2])
            ->willReturn(2);

        $result = $reflection->invoke($this->controller, 'rebuild', ['1', '2'], '');

        $this->assertSame('Rebuilt Text(s): 2', $result);
    }

    public function testHandleMarkActionReviewReturnsRedirect(): void
    {
        $reflection = new \ReflectionMethod(TextController::class, 'handleMarkAction');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($this->controller, 'review', ['1', '2'], '');

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testHandleMarkActionDeltagReturnsRedirect(): void
    {
        $reflection = new \ReflectionMethod(TextController::class, 'handleMarkAction');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($this->controller, 'deltag', ['1'], 'tag');

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    // =========================================================================
    // handleArchivedMarkAction tests via reflection
    // =========================================================================

    public function testHandleArchivedMarkActionReturnsDefaultForEmptyMarked(): void
    {
        $reflection = new \ReflectionMethod(TextController::class, 'handleArchivedMarkAction');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($this->controller, 'del', [], '');

        $this->assertSame('Multiple Actions: 0', $result);
    }

    public function testHandleArchivedMarkActionDeleteCallsService(): void
    {
        $reflection = new \ReflectionMethod(TextController::class, 'handleArchivedMarkAction');
        $reflection->setAccessible(true);

        $this->textService->expects($this->once())
            ->method('deleteArchivedTexts')
            ->with([1, 2])
            ->willReturn(['count' => 2]);

        $result = $reflection->invoke($this->controller, 'del', ['1', '2'], '');

        $this->assertSame('Archived Texts deleted: 2', $result);
    }

    public function testHandleArchivedMarkActionUnarchiveCallsService(): void
    {
        $reflection = new \ReflectionMethod(TextController::class, 'handleArchivedMarkAction');
        $reflection->setAccessible(true);

        $this->textService->expects($this->once())
            ->method('unarchiveTexts')
            ->with([1])
            ->willReturn(['count' => 1]);

        $result = $reflection->invoke($this->controller, 'unarch', ['1'], '');

        $this->assertSame('Unarchived Text(s): 1', $result);
    }

    public function testHandleArchivedMarkActionDeltagReturnsRedirect(): void
    {
        $reflection = new \ReflectionMethod(TextController::class, 'handleArchivedMarkAction');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($this->controller, 'deltag', ['1'], 'tag');

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    // =========================================================================
    // Class structure tests
    // =========================================================================

    public function testClassHasRequiredMethods(): void
    {
        $reflection = new \ReflectionClass(TextController::class);

        $expectedMethods = [
            'read',
            'edit',
            'display',
            'setMode',
            'check',
            'archived',
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "TextController should have method: $methodName"
            );
        }
    }

    public function testReadMethodIsPublic(): void
    {
        $reflection = new \ReflectionMethod(TextController::class, 'read');
        $this->assertTrue($reflection->isPublic());
    }

    public function testEditMethodIsPublic(): void
    {
        $reflection = new \ReflectionMethod(TextController::class, 'edit');
        $this->assertTrue($reflection->isPublic());
    }

    public function testDisplayMethodIsPublic(): void
    {
        $reflection = new \ReflectionMethod(TextController::class, 'display');
        $this->assertTrue($reflection->isPublic());
    }

    public function testSetModeMethodIsPublic(): void
    {
        $reflection = new \ReflectionMethod(TextController::class, 'setMode');
        $this->assertTrue($reflection->isPublic());
    }

    public function testCheckMethodIsPublic(): void
    {
        $reflection = new \ReflectionMethod(TextController::class, 'check');
        $this->assertTrue($reflection->isPublic());
    }

    public function testArchivedMethodIsPublic(): void
    {
        $reflection = new \ReflectionMethod(TextController::class, 'archived');
        $this->assertTrue($reflection->isPublic());
    }

    // =========================================================================
    // Private handler method tests
    // =========================================================================

    public function testShowNewTextFormMethodExists(): void
    {
        $reflection = new \ReflectionClass(TextController::class);
        $this->assertTrue($reflection->hasMethod('showNewTextForm'));

        $method = $reflection->getMethod('showNewTextForm');
        $this->assertTrue($method->isPrivate());
    }

    public function testShowEditTextFormMethodExists(): void
    {
        $reflection = new \ReflectionClass(TextController::class);
        $this->assertTrue($reflection->hasMethod('showEditTextForm'));

        $method = $reflection->getMethod('showEditTextForm');
        $this->assertTrue($method->isPrivate());
    }

    public function testShowTextsListMethodExists(): void
    {
        $reflection = new \ReflectionClass(TextController::class);
        $this->assertTrue($reflection->hasMethod('showTextsList'));

        $method = $reflection->getMethod('showTextsList');
        $this->assertTrue($method->isPrivate());
    }

    public function testHandleTextOperationMethodExists(): void
    {
        $reflection = new \ReflectionClass(TextController::class);
        $this->assertTrue($reflection->hasMethod('handleTextOperation'));

        $method = $reflection->getMethod('handleTextOperation');
        $this->assertTrue($method->isPrivate());
    }

    public function testRenderReadPageMethodExists(): void
    {
        $reflection = new \ReflectionClass(TextController::class);
        $this->assertTrue($reflection->hasMethod('renderReadPage'));

        $method = $reflection->getMethod('renderReadPage');
        $this->assertTrue($method->isPrivate());
    }

    public function testHandleAutoSplitImportMethodExists(): void
    {
        $reflection = new \ReflectionClass(TextController::class);
        $this->assertTrue($reflection->hasMethod('handleAutoSplitImport'));

        $method = $reflection->getMethod('handleAutoSplitImport');
        $this->assertTrue($method->isPrivate());
    }
}
