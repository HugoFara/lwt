<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Text\Http;

use Lwt\Modules\Text\Http\TextPrintController;
use Lwt\Modules\Text\Application\Services\TextPrintService;
use Lwt\Shared\Http\BaseController;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for TextPrintController.
 *
 * Covers constructor, service injection, method signatures,
 * and redirect-on-zero-ID / deleteAnnotation behavior.
 */
class TextPrintControllerTest extends TestCase
{
    /** Stub view data matching preparePlainPrintData return shape */
    private const STUB_PLAIN_VIEW_DATA = [
        'textId' => 0,
        'title' => 'Test',
        'sourceUri' => '',
        'audioUri' => '',
        'langId' => 1,
        'textSize' => 100,
        'rtlScript' => false,
        'hasAnnotation' => false,
    ];

    /** Stub view data matching prepareAnnotatedPrintData return shape */
    private const STUB_ANNOTATED_VIEW_DATA = [
        'textId' => 0,
        'title' => 'Test',
        'sourceUri' => '',
        'audioUri' => '',
        'langId' => 1,
        'textSize' => 100,
        'rtlScript' => false,
        'annotation' => null,
        'hasAnnotation' => false,
        'ttsClass' => '',
    ];

    /** @var TextPrintService&MockObject */
    private TextPrintService $printService;

    private TextPrintController $controller;

    /** @var array<string,mixed> Saved superglobals */
    private array $savedGet;
    private array $savedPost;
    private array $savedRequest;

    protected function setUp(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required');
        }

        $this->savedGet = $_GET;
        $this->savedPost = $_POST;
        $this->savedRequest = $_REQUEST;

        $_GET = [];
        $_POST = [];
        $_REQUEST = [];

        $this->printService = $this->createMock(TextPrintService::class);
        $this->controller = new TextPrintController($this->printService);
    }

    protected function tearDown(): void
    {
        if (isset($this->savedGet)) {
            $_GET = $this->savedGet;
            $_POST = $this->savedPost;
            $_REQUEST = $this->savedRequest;
        }
    }

    /**
     * Helper to safely call a void controller method that may include views.
     *
     * The controller methods call redirect() (which returns RedirectResponse
     * but the return is discarded since they are void). When the service mock
     * returns null for view data, the included view file may throw a TypeError.
     * This helper captures output and cleans up buffers.
     *
     * @param callable $fn The method call to execute
     */
    private function callSafely(callable $fn): void
    {
        $level = ob_get_level();
        ob_start();
        try {
            $fn();
        } catch (\TypeError $e) {
            // Expected when view receives null viewData
        } finally {
            // Clean up any extra output buffers opened by the controller
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
        }
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    #[Test]
    public function constructorWithMockServiceCreatesController(): void
    {
        $this->assertInstanceOf(TextPrintController::class, $this->controller);
    }

    #[Test]
    public function constructorWithNullServiceCreatesController(): void
    {
        $controller = new TextPrintController(null);
        $this->assertInstanceOf(TextPrintController::class, $controller);
    }

    #[Test]
    public function constructorWithNoArgsCreatesController(): void
    {
        $controller = new TextPrintController();
        $this->assertInstanceOf(TextPrintController::class, $controller);
    }

    // =========================================================================
    // Class structure
    // =========================================================================

    #[Test]
    public function classExtendsBaseController(): void
    {
        $this->assertInstanceOf(BaseController::class, $this->controller);
    }

    #[Test]
    public function classExtendsBaseControllerByReflection(): void
    {
        $reflection = new \ReflectionClass(TextPrintController::class);
        $this->assertSame(
            'Lwt\Shared\Http\BaseController',
            $reflection->getParentClass()->getName()
        );
    }

    // =========================================================================
    // getPrintService()
    // =========================================================================

    #[Test]
    public function getPrintServiceReturnsInjectedService(): void
    {
        $this->assertSame($this->printService, $this->controller->getPrintService());
    }

    #[Test]
    public function getPrintServiceReturnsDefaultServiceWhenNullInjected(): void
    {
        $controller = new TextPrintController(null);
        $this->assertInstanceOf(TextPrintService::class, $controller->getPrintService());
    }

    #[Test]
    public function getPrintServiceReturnsSameInstanceEachCall(): void
    {
        $first = $this->controller->getPrintService();
        $second = $this->controller->getPrintService();
        $this->assertSame($first, $second);
    }

    #[Test]
    public function getPrintServiceIsNotSameAcrossControllers(): void
    {
        $otherMock = $this->createMock(TextPrintService::class);
        $otherController = new TextPrintController($otherMock);
        $this->assertNotSame(
            $this->controller->getPrintService(),
            $otherController->getPrintService()
        );
    }

    // =========================================================================
    // Method existence and signatures — printPlain
    // =========================================================================

    #[Test]
    public function printPlainMethodExists(): void
    {
        $this->assertTrue(method_exists(TextPrintController::class, 'printPlain'));
    }

    #[Test]
    public function printPlainIsPublic(): void
    {
        $method = new \ReflectionMethod(TextPrintController::class, 'printPlain');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function printPlainAcceptsNullableIntParameter(): void
    {
        $method = new \ReflectionMethod(TextPrintController::class, 'printPlain');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('text', $params[0]->getName());
        $this->assertTrue($params[0]->allowsNull());
        $this->assertTrue($params[0]->isDefaultValueAvailable());
        $this->assertNull($params[0]->getDefaultValue());
    }

    #[Test]
    public function printPlainReturnsVoid(): void
    {
        $method = new \ReflectionMethod(TextPrintController::class, 'printPlain');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('void', $returnType->getName());
    }

    // =========================================================================
    // Method existence and signatures — printAnnotated
    // =========================================================================

    #[Test]
    public function printAnnotatedMethodExists(): void
    {
        $this->assertTrue(method_exists(TextPrintController::class, 'printAnnotated'));
    }

    #[Test]
    public function printAnnotatedIsPublic(): void
    {
        $method = new \ReflectionMethod(TextPrintController::class, 'printAnnotated');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function printAnnotatedAcceptsArrayParameter(): void
    {
        $method = new \ReflectionMethod(TextPrintController::class, 'printAnnotated');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
        $this->assertSame('array', $params[0]->getType()->getName());
    }

    // =========================================================================
    // Method existence and signatures — editAnnotation
    // =========================================================================

    #[Test]
    public function editAnnotationMethodExists(): void
    {
        $this->assertTrue(method_exists(TextPrintController::class, 'editAnnotation'));
    }

    #[Test]
    public function editAnnotationIsPublic(): void
    {
        $method = new \ReflectionMethod(TextPrintController::class, 'editAnnotation');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function editAnnotationAcceptsArrayParameter(): void
    {
        $method = new \ReflectionMethod(TextPrintController::class, 'editAnnotation');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
        $this->assertSame('array', $params[0]->getType()->getName());
    }

    // =========================================================================
    // Method existence and signatures — deleteAnnotation
    // =========================================================================

    #[Test]
    public function deleteAnnotationMethodExists(): void
    {
        $this->assertTrue(method_exists(TextPrintController::class, 'deleteAnnotation'));
    }

    #[Test]
    public function deleteAnnotationIsPublic(): void
    {
        $method = new \ReflectionMethod(TextPrintController::class, 'deleteAnnotation');
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function deleteAnnotationAcceptsArrayParameter(): void
    {
        $method = new \ReflectionMethod(TextPrintController::class, 'deleteAnnotation');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
        $this->assertSame('array', $params[0]->getType()->getName());
    }

    #[Test]
    public function deleteAnnotationReturnsVoid(): void
    {
        $method = new \ReflectionMethod(TextPrintController::class, 'deleteAnnotation');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('void', $returnType->getName());
    }

    // =========================================================================
    // printPlain — service interactions with zero/null ID
    // =========================================================================

    #[Test]
    public function printPlainWithZeroCallsPreparePlainPrintData(): void
    {
        $this->printService->method('getAnnotationSetting')->willReturn(3);
        $this->printService->method('getStatusRangeSetting')->willReturn(14);
        $this->printService->method('getAnnotationPlacementSetting')->willReturn(0);
        $this->printService->expects($this->once())
            ->method('preparePlainPrintData')
            ->with(0)
            ->willReturn(self::STUB_PLAIN_VIEW_DATA);
        $this->printService->method('savePrintSettings');

        $this->callSafely(fn () => $this->controller->printPlain(0));
    }

    #[Test]
    public function printPlainWithNullAndNoQueryParamFallsBackToZero(): void
    {
        $this->printService->method('getAnnotationSetting')->willReturn(3);
        $this->printService->method('getStatusRangeSetting')->willReturn(14);
        $this->printService->method('getAnnotationPlacementSetting')->willReturn(0);
        $this->printService->expects($this->once())
            ->method('preparePlainPrintData')
            ->with(0)
            ->willReturn(self::STUB_PLAIN_VIEW_DATA);
        $this->printService->method('savePrintSettings');

        $this->callSafely(fn () => $this->controller->printPlain(null));
    }

    #[Test]
    public function printPlainCallsSavePrintSettingsWithResolvedValues(): void
    {
        $this->printService->method('getAnnotationSetting')->willReturn(5);
        $this->printService->method('getStatusRangeSetting')->willReturn(7);
        $this->printService->method('getAnnotationPlacementSetting')->willReturn(2);
        $this->printService->method('preparePlainPrintData')->willReturn(self::STUB_PLAIN_VIEW_DATA);
        $this->printService->expects($this->once())
            ->method('savePrintSettings')
            ->with(0, 5, 7, 2);

        $this->callSafely(fn () => $this->controller->printPlain(0));
    }

    // =========================================================================
    // printAnnotated — service interactions with zero ID
    // =========================================================================

    #[Test]
    public function printAnnotatedWithZeroIdCallsGetAnnotatedText(): void
    {
        $this->printService->expects($this->once())
            ->method('getAnnotatedText')
            ->with(0)
            ->willReturn(null);
        $this->printService->method('prepareAnnotatedPrintData')->willReturn(self::STUB_ANNOTATED_VIEW_DATA);
        $this->printService->method('setCurrentText');

        $this->callSafely(fn () => $this->controller->printAnnotated(['text' => 0]));
    }

    #[Test]
    public function printAnnotatedWithEmptyParamsUsesZero(): void
    {
        $this->printService->expects($this->once())
            ->method('getAnnotatedText')
            ->with(0)
            ->willReturn(null);
        $this->printService->method('prepareAnnotatedPrintData')->willReturn(self::STUB_ANNOTATED_VIEW_DATA);
        $this->printService->method('setCurrentText');

        $this->callSafely(fn () => $this->controller->printAnnotated([]));
    }

    #[Test]
    public function printAnnotatedCallsSetCurrentText(): void
    {
        $this->printService->method('getAnnotatedText')->willReturn(null);
        $this->printService->method('prepareAnnotatedPrintData')->willReturn(self::STUB_ANNOTATED_VIEW_DATA);
        $this->printService->expects($this->once())
            ->method('setCurrentText')
            ->with(0);

        $this->callSafely(fn () => $this->controller->printAnnotated(['text' => 0]));
    }

    // =========================================================================
    // editAnnotation — service interactions with zero ID
    // =========================================================================

    #[Test]
    public function editAnnotationWithZeroIdCallsGetAnnotatedText(): void
    {
        $this->printService->expects($this->once())
            ->method('getAnnotatedText')
            ->with(0)
            ->willReturn(null);
        $this->printService->method('prepareAnnotatedPrintData')->willReturn(self::STUB_ANNOTATED_VIEW_DATA);
        $this->printService->method('setCurrentText');

        $this->callSafely(fn () => $this->controller->editAnnotation(['text' => 0]));
    }

    #[Test]
    public function editAnnotationWithEmptyParamsUsesZero(): void
    {
        $this->printService->expects($this->once())
            ->method('getAnnotatedText')
            ->with(0)
            ->willReturn(null);
        $this->printService->method('prepareAnnotatedPrintData')->willReturn(self::STUB_ANNOTATED_VIEW_DATA);
        $this->printService->method('setCurrentText');

        $this->callSafely(fn () => $this->controller->editAnnotation([]));
    }

    #[Test]
    public function editAnnotationCallsSetCurrentText(): void
    {
        $this->printService->method('getAnnotatedText')->willReturn(null);
        $this->printService->method('prepareAnnotatedPrintData')->willReturn(self::STUB_ANNOTATED_VIEW_DATA);
        $this->printService->expects($this->once())
            ->method('setCurrentText')
            ->with(0);

        $this->callSafely(fn () => $this->controller->editAnnotation(['text' => 0]));
    }

    // =========================================================================
    // deleteAnnotation — full behavior tests (no view rendering)
    // =========================================================================

    #[Test]
    public function deleteAnnotationWithZeroIdStillCallsService(): void
    {
        $this->printService->expects($this->once())
            ->method('deleteAnnotation')
            ->with(0)
            ->willReturn(false);

        $this->controller->deleteAnnotation(['text' => 0]);
    }

    #[Test]
    public function deleteAnnotationWithEmptyParamsUsesZero(): void
    {
        $this->printService->expects($this->once())
            ->method('deleteAnnotation')
            ->with(0)
            ->willReturn(false);

        $this->controller->deleteAnnotation([]);
    }

    #[Test]
    public function deleteAnnotationSuccessCallsServiceWithCorrectId(): void
    {
        $this->printService->expects($this->once())
            ->method('deleteAnnotation')
            ->with(42)
            ->willReturn(true);

        $this->controller->deleteAnnotation(['text' => 42]);
    }

    #[Test]
    public function deleteAnnotationFailureCallsServiceWithCorrectId(): void
    {
        $this->printService->expects($this->once())
            ->method('deleteAnnotation')
            ->with(99)
            ->willReturn(false);

        $this->controller->deleteAnnotation(['text' => 99]);
    }

    #[Test]
    public function deleteAnnotationWithLargeIdCallsService(): void
    {
        $this->printService->expects($this->once())
            ->method('deleteAnnotation')
            ->with(999999)
            ->willReturn(true);

        $this->controller->deleteAnnotation(['text' => 999999]);
    }

    #[Test]
    public function deleteAnnotationStringIdIsCastToInt(): void
    {
        // Route params may arrive as strings; (int) cast in controller
        $this->printService->expects($this->once())
            ->method('deleteAnnotation')
            ->with(7)
            ->willReturn(false);

        $this->controller->deleteAnnotation(['text' => '7']);
    }
}
