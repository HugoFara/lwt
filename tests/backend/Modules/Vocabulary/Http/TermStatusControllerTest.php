<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Http;

use Lwt\Modules\Vocabulary\Http\TermStatusController;
use Lwt\Modules\Vocabulary\Http\VocabularyBaseController;
use Lwt\Modules\Vocabulary\Application\VocabularyFacade;
use Lwt\Modules\Vocabulary\Application\UseCases\CreateTermFromHover;
use Lwt\Modules\Vocabulary\Domain\Term;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for TermStatusController.
 *
 * Tests constructor behavior, class structure, method signatures,
 * status update logic, hover insert, and edge cases.
 */
class TermStatusControllerTest extends TestCase
{
    /** @var VocabularyFacade&MockObject */
    private VocabularyFacade $facade;

    /** @var CreateTermFromHover&MockObject */
    private CreateTermFromHover $createTermFromHover;

    private TermStatusController $controller;

    protected function setUp(): void
    {
        $this->facade = $this->createMock(VocabularyFacade::class);
        $this->createTermFromHover = $this->createMock(CreateTermFromHover::class);
        $this->controller = new TermStatusController(
            $this->facade,
            $this->createTermFromHover
        );
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
    public function constructorAcceptsAllNullParameters(): void
    {
        $controller = new TermStatusController(null, null);
        $this->assertInstanceOf(TermStatusController::class, $controller);
    }

    #[Test]
    public function constructorSetsFacadeProperty(): void
    {
        $reflection = new \ReflectionProperty(TermStatusController::class, 'facade');
        $reflection->setAccessible(true);

        $this->assertSame($this->facade, $reflection->getValue($this->controller));
    }

    #[Test]
    public function constructorSetsCreateTermFromHoverProperty(): void
    {
        $reflection = new \ReflectionProperty(TermStatusController::class, 'createTermFromHover');
        $reflection->setAccessible(true);

        $this->assertSame($this->createTermFromHover, $reflection->getValue($this->controller));
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
            'setWordStatusView',
            'setReviewStatusView',
            'insertWellknown',
            'insertIgnore',
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

    #[Test]
    public function classHasRequiredPrivateMethods(): void
    {
        $reflection = new \ReflectionClass(TermStatusController::class);

        $expectedMethods = [
            'insertWordWithStatus',
            'createFromHover',
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "TermStatusController should have private method: $methodName"
            );
            $method = $reflection->getMethod($methodName);
            $this->assertTrue(
                $method->isPrivate(),
                "Method $methodName should be private"
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
    public function setWordStatusViewAcceptsArrayParameter(): void
    {
        $method = new \ReflectionMethod(TermStatusController::class, 'setWordStatusView');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
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
    public function insertWellknownAcceptsArrayParameter(): void
    {
        $method = new \ReflectionMethod(TermStatusController::class, 'insertWellknown');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
    }

    #[Test]
    public function insertIgnoreAcceptsArrayParameter(): void
    {
        $method = new \ReflectionMethod(TermStatusController::class, 'insertIgnore');
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
    // insertWordWithStatus tests via reflection
    // =========================================================================

    #[Test]
    public function insertWordWithStatusAcceptsIntStatus(): void
    {
        $method = new \ReflectionMethod(TermStatusController::class, 'insertWordWithStatus');
        $method->setAccessible(true);

        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertSame('params', $params[0]->getName());
        $this->assertSame('status', $params[1]->getName());
        $this->assertSame('int', $params[1]->getType()->getName());
    }

    // =========================================================================
    // createFromHover tests via reflection
    // =========================================================================

    #[Test]
    public function createFromHoverCallsExecuteOnUseCase(): void
    {
        $method = new \ReflectionMethod(TermStatusController::class, 'createFromHover');
        $method->setAccessible(true);

        $this->createTermFromHover->expects($this->once())
            ->method('shouldSetNoCacheHeaders')
            ->with(1)
            ->willReturn(false);

        $this->createTermFromHover->expects($this->once())
            ->method('execute')
            ->with(10, 'hello', 1, '', '')
            ->willReturn([
                'wid' => 5,
                'hex' => 'abc123',
                'word' => 'hello',
            ]);

        $result = $method->invoke($this->controller, 10, 'hello', 1, '', '');

        $this->assertSame(5, $result['wid']);
        $this->assertSame('abc123', $result['hex']);
    }

    #[Test]
    public function createFromHoverPassesLanguageParams(): void
    {
        $method = new \ReflectionMethod(TermStatusController::class, 'createFromHover');
        $method->setAccessible(true);

        $this->createTermFromHover->expects($this->once())
            ->method('shouldSetNoCacheHeaders')
            ->willReturn(false);

        $this->createTermFromHover->expects($this->once())
            ->method('execute')
            ->with(1, 'bonjour', 2, 'fr', 'en')
            ->willReturn(['wid' => 1, 'hex' => '', 'word' => 'bonjour']);

        $method->invoke($this->controller, 1, 'bonjour', 2, 'fr', 'en');
    }

    // =========================================================================
    // Lazy-loaded service accessor tests
    // =========================================================================

    #[Test]
    public function discoveryServiceIsNullByDefault(): void
    {
        $reflection = new \ReflectionProperty(VocabularyBaseController::class, 'discoveryService');
        $reflection->setAccessible(true);

        $this->assertNull($reflection->getValue($this->controller));
    }

    #[Test]
    public function textStatisticsServiceIsNullByDefault(): void
    {
        $reflection = new \ReflectionProperty(VocabularyBaseController::class, 'textStatisticsService');
        $reflection->setAccessible(true);

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
