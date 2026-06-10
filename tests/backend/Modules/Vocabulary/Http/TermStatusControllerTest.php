<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Http;

use Lwt\Modules\Vocabulary\Http\TermStatusController;
use Lwt\Modules\Vocabulary\Http\VocabularyBaseController;
use Lwt\Modules\Vocabulary\Application\VocabularyFacade;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for TermStatusController.
 *
 * Tests constructor behavior, class structure, method signatures,
 * status update logic, and edge cases.
 */
class TermStatusControllerTest extends TestCase
{
    /** @var VocabularyFacade&MockObject */
    private VocabularyFacade $facade;

    private TermStatusController $controller;

    protected function setUp(): void
    {
        $this->facade = $this->createMock(VocabularyFacade::class);
        $this->controller = new TermStatusController($this->facade);
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    #[Test]
    public function constructorCreatesValidController(): void
    {
        $this->assertInstanceOf(TermStatusController::class, $this->controller);
    }

    #[Test]
    public function constructorAcceptsNullParameter(): void
    {
        $controller = new TermStatusController(null);
        $this->assertInstanceOf(TermStatusController::class, $controller);
    }

    #[Test]
    public function constructorSetsFacadeProperty(): void
    {
        $reflection = new \ReflectionProperty(TermStatusController::class, 'facade');

        $this->assertSame($this->facade, $reflection->getValue($this->controller));
    }

    // =========================================================================
    // Class structure tests
    // =========================================================================

    #[Test]
    public function classExtendsVocabularyBaseController(): void
    {
        $reflection = new \ReflectionClass(TermStatusController::class);

        $this->assertSame(
            VocabularyBaseController::class,
            $reflection->getParentClass()->getName()
        );
    }

    #[Test]
    public function classHasRequiredPublicMethods(): void
    {
        $reflection = new \ReflectionClass(TermStatusController::class);

        $expectedMethods = [
            'updateStatus',
            'setReviewStatusView',
            'markAllWords',
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "TermStatusController should have method: $methodName"
            );
            $method = $reflection->getMethod($methodName);
            $this->assertTrue(
                $method->isPublic(),
                "Method $methodName should be public"
            );
        }
    }

    // =========================================================================
    // Method signature tests
    // =========================================================================

    #[Test]
    public function updateStatusAcceptsNullableIntParameter(): void
    {
        $method = new \ReflectionMethod(TermStatusController::class, 'updateStatus');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('wid', $params[0]->getName());
        $this->assertTrue($params[0]->allowsNull());
        $this->assertTrue($params[0]->isDefaultValueAvailable());
        $this->assertNull($params[0]->getDefaultValue());
    }

    #[Test]
    public function setReviewStatusViewAcceptsArrayParameter(): void
    {
        $method = new \ReflectionMethod(TermStatusController::class, 'setReviewStatusView');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
    }

    #[Test]
    public function markAllWordsAcceptsArrayParameter(): void
    {
        $method = new \ReflectionMethod(TermStatusController::class, 'markAllWords');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
    }

    // =========================================================================
    // updateStatus tests
    // =========================================================================

    #[Test]
    public function updateStatusWithZeroTermIdReturns400(): void
    {
        // No query params, wid=null fallback to 0, status=0
        ob_start();
        $this->controller->updateStatus(0);
        $output = ob_get_clean();

        $decoded = json_decode($output, true);
        $this->assertArrayHasKey('error', $decoded);
        $this->assertSame('Term ID and status required', $decoded['error']);
    }

    #[Test]
    public function updateStatusWithNullWidAndNoQueryParamsReturns400(): void
    {
        ob_start();
        $this->controller->updateStatus(null);
        $output = ob_get_clean();

        $decoded = json_decode($output, true);
        $this->assertArrayHasKey('error', $decoded);
    }

    #[Test]
    public function updateStatusWithValidWidCallsFacade(): void
    {
        // Set status via REQUEST
        $_REQUEST['status'] = '3';

        $this->facade->expects($this->once())
            ->method('updateStatus')
            ->with(42, 3)
            ->willReturn(true);

        ob_start();
        $this->controller->updateStatus(42);
        $output = ob_get_clean();

        $decoded = json_decode($output, true);
        $this->assertTrue($decoded['success']);

        unset($_REQUEST['status']);
    }

    #[Test]
    public function updateStatusReturnsFalseOnFailure(): void
    {
        $_REQUEST['status'] = '5';

        $this->facade->expects($this->once())
            ->method('updateStatus')
            ->with(99, 5)
            ->willReturn(false);

        ob_start();
        $this->controller->updateStatus(99);
        $output = ob_get_clean();

        $decoded = json_decode($output, true);
        $this->assertFalse($decoded['success']);

        unset($_REQUEST['status']);
    }

    // =========================================================================
    // Lazy-loaded service accessor tests
    // =========================================================================

    #[Test]
    public function discoveryServiceIsNullByDefault(): void
    {
        $reflection = new \ReflectionProperty(VocabularyBaseController::class, 'discoveryService');

        $this->assertNull($reflection->getValue($this->controller));
    }

    #[Test]
    public function textStatisticsServiceIsNullByDefault(): void
    {
        $reflection = new \ReflectionProperty(VocabularyBaseController::class, 'textStatisticsService');

        $this->assertNull($reflection->getValue($this->controller));
    }

    // =========================================================================
    // markAllWords edge case
    // =========================================================================

    #[Test]
    public function markAllWordsReturnsEarlyWhenTextIdIsNull(): void
    {
        // No GET 'text' param => textId is null => early return
        ob_start();
        $this->controller->markAllWords([]);
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }
}
