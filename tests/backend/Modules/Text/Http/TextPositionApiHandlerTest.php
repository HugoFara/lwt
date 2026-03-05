<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Text\Http;

use Lwt\Modules\Text\Http\TextPositionApiHandler;
use Lwt\Modules\Vocabulary\Application\Services\WordDiscoveryService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for TextPositionApiHandler.
 *
 * Tests text/audio position saving, display mode settings,
 * bulk word status operations, and response formatting.
 */
class TextPositionApiHandlerTest extends TestCase
{
    /** @var WordDiscoveryService&MockObject */
    private WordDiscoveryService $discoveryService;

    private TextPositionApiHandler $handler;

    protected function setUp(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required');
        }
        $this->discoveryService = $this->createMock(WordDiscoveryService::class);
        $this->handler = new TextPositionApiHandler($this->discoveryService);
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    #[Test]
    public function constructorCreatesValidHandler(): void
    {
        $this->assertInstanceOf(TextPositionApiHandler::class, $this->handler);
    }

    #[Test]
    public function constructorAcceptsNullParameter(): void
    {
        $handler = new TextPositionApiHandler(null);
        $this->assertInstanceOf(TextPositionApiHandler::class, $handler);
    }

    #[Test]
    public function constructorSetsDiscoveryServiceProperty(): void
    {
        $reflection = new \ReflectionProperty(TextPositionApiHandler::class, 'discoveryService');
        $reflection->setAccessible(true);

        $this->assertSame($this->discoveryService, $reflection->getValue($this->handler));
    }

    #[Test]
    public function constructorWithNullCreatesDefaultService(): void
    {
        $handler = new TextPositionApiHandler(null);
        $reflection = new \ReflectionProperty(TextPositionApiHandler::class, 'discoveryService');
        $reflection->setAccessible(true);

        $this->assertInstanceOf(WordDiscoveryService::class, $reflection->getValue($handler));
    }

    // =========================================================================
    // Class structure tests
    // =========================================================================

    #[Test]
    public function classHasRequiredPublicMethods(): void
    {
        $reflection = new \ReflectionClass(TextPositionApiHandler::class);

        $expectedMethods = [
            'saveTextPosition', 'saveAudioPosition',
            'formatSetTextPosition', 'formatSetAudioPosition',
            'setDisplayMode', 'formatSetDisplayMode',
            'markAllWellKnown', 'markAllIgnored',
            'formatMarkAllWellKnown', 'formatMarkAllIgnored',
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "TextPositionApiHandler should have method: $methodName"
            );
            $method = $reflection->getMethod($methodName);
            $this->assertTrue(
                $method->isPublic(),
                "Method $methodName should be public"
            );
        }
    }

    // =========================================================================
    // formatSetTextPosition tests
    // =========================================================================

    #[Test]
    public function formatSetTextPositionReturnsTextKey(): void
    {
        $result = $this->handler->formatSetTextPosition(1, 100);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('text', $result);
    }

    #[Test]
    public function formatSetTextPositionReturnsCorrectMessage(): void
    {
        $result = $this->handler->formatSetTextPosition(1, 100);

        $this->assertSame('Reading position set', $result['text']);
    }

    #[Test]
    public function formatSetTextPositionWithZeroPosition(): void
    {
        $result = $this->handler->formatSetTextPosition(1, 0);

        $this->assertSame('Reading position set', $result['text']);
    }

    #[Test]
    public function formatSetTextPositionWithNegativePosition(): void
    {
        $result = $this->handler->formatSetTextPosition(1, -1);

        $this->assertSame('Reading position set', $result['text']);
    }

    #[Test]
    public function formatSetTextPositionWithLargePosition(): void
    {
        $result = $this->handler->formatSetTextPosition(1, 9999);

        $this->assertSame('Reading position set', $result['text']);
    }

    // =========================================================================
    // formatSetAudioPosition tests
    // =========================================================================

    #[Test]
    public function formatSetAudioPositionReturnsAudioKey(): void
    {
        $result = $this->handler->formatSetAudioPosition(1, 500);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('audio', $result);
    }

    #[Test]
    public function formatSetAudioPositionReturnsCorrectMessage(): void
    {
        $result = $this->handler->formatSetAudioPosition(1, 500);

        $this->assertSame('Audio position set', $result['audio']);
    }

    #[Test]
    public function formatSetAudioPositionWithZero(): void
    {
        $result = $this->handler->formatSetAudioPosition(1, 0);

        $this->assertSame('Audio position set', $result['audio']);
    }

    // =========================================================================
    // setDisplayMode tests
    // =========================================================================

    #[Test]
    public function setDisplayModeReturnsErrorForNonExistentText(): void
    {
        $result = $this->handler->setDisplayMode(999999, 1, true, true);

        $this->assertArrayHasKey('updated', $result);
        $this->assertFalse($result['updated']);
        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Text not found', $result['error']);
    }

    #[Test]
    public function setDisplayModeMethodSignature(): void
    {
        $method = new \ReflectionMethod(TextPositionApiHandler::class, 'setDisplayMode');
        $params = $method->getParameters();

        $this->assertCount(4, $params);
        $this->assertSame('textId', $params[0]->getName());
        $this->assertSame('annotations', $params[1]->getName());
        $this->assertSame('romanization', $params[2]->getName());
        $this->assertSame('translation', $params[3]->getName());
    }

    #[Test]
    public function setDisplayModeAnnotationsParameterIsNullable(): void
    {
        $method = new \ReflectionMethod(TextPositionApiHandler::class, 'setDisplayMode');
        $params = $method->getParameters();

        $this->assertTrue($params[1]->allowsNull());
        $this->assertTrue($params[2]->allowsNull());
        $this->assertTrue($params[3]->allowsNull());
    }

    // =========================================================================
    // formatSetDisplayMode tests
    // =========================================================================

    #[Test]
    public function formatSetDisplayModeParseAnnotationsParam(): void
    {
        $result = $this->handler->formatSetDisplayMode(999999, ['annotations' => '2']);

        $this->assertArrayHasKey('updated', $result);
        // Non-existent text should return error
        $this->assertFalse($result['updated']);
    }

    #[Test]
    public function formatSetDisplayModeParsesRomanizationBoolean(): void
    {
        $result = $this->handler->formatSetDisplayMode(999999, ['romanization' => 'true']);

        $this->assertFalse($result['updated']);
    }

    #[Test]
    public function formatSetDisplayModeParsesTranslationBoolean(): void
    {
        $result = $this->handler->formatSetDisplayMode(999999, ['translation' => 'false']);

        $this->assertFalse($result['updated']);
    }

    #[Test]
    public function formatSetDisplayModeHandlesEmptyParams(): void
    {
        $result = $this->handler->formatSetDisplayMode(999999, []);

        $this->assertFalse($result['updated']);
    }

    #[Test]
    public function formatSetDisplayModeHandlesAllParams(): void
    {
        $result = $this->handler->formatSetDisplayMode(999999, [
            'annotations' => '3',
            'romanization' => 'true',
            'translation' => 'false',
        ]);

        $this->assertFalse($result['updated']);
    }

    // =========================================================================
    // markAllWellKnown tests
    // =========================================================================

    #[Test]
    public function markAllWellKnownCallsDiscoveryServiceWithStatus99(): void
    {
        $this->discoveryService->expects($this->once())
            ->method('markAllWordsWithStatus')
            ->with(42, 99)
            ->willReturn([5, [['id' => 1], ['id' => 2]]]);

        $result = $this->handler->markAllWellKnown(42);

        $this->assertArrayHasKey('count', $result);
        $this->assertSame(5, $result['count']);
        $this->assertArrayHasKey('words', $result);
    }

    #[Test]
    public function markAllWellKnownReturnsCountAndWords(): void
    {
        $wordsData = [['id' => 1, 'text' => 'hello'], ['id' => 2, 'text' => 'world']];
        $this->discoveryService->method('markAllWordsWithStatus')
            ->willReturn([2, $wordsData]);

        $result = $this->handler->markAllWellKnown(1);

        $this->assertSame(2, $result['count']);
        $this->assertSame($wordsData, $result['words']);
    }

    #[Test]
    public function markAllWellKnownWithZeroCount(): void
    {
        $this->discoveryService->method('markAllWordsWithStatus')
            ->willReturn([0, []]);

        $result = $this->handler->markAllWellKnown(1);

        $this->assertSame(0, $result['count']);
        $this->assertEmpty($result['words']);
    }

    // =========================================================================
    // markAllIgnored tests
    // =========================================================================

    #[Test]
    public function markAllIgnoredCallsDiscoveryServiceWithStatus98(): void
    {
        $this->discoveryService->expects($this->once())
            ->method('markAllWordsWithStatus')
            ->with(42, 98)
            ->willReturn([3, []]);

        $result = $this->handler->markAllIgnored(42);

        $this->assertArrayHasKey('count', $result);
        $this->assertSame(3, $result['count']);
    }

    #[Test]
    public function markAllIgnoredReturnsCountAndWords(): void
    {
        $wordsData = [['id' => 10]];
        $this->discoveryService->method('markAllWordsWithStatus')
            ->willReturn([1, $wordsData]);

        $result = $this->handler->markAllIgnored(1);

        $this->assertSame(1, $result['count']);
        $this->assertSame($wordsData, $result['words']);
    }

    // =========================================================================
    // formatMarkAllWellKnown tests
    // =========================================================================

    #[Test]
    public function formatMarkAllWellKnownDelegatesToMarkAllWellKnown(): void
    {
        $this->discoveryService->expects($this->once())
            ->method('markAllWordsWithStatus')
            ->with(42, 99)
            ->willReturn([7, [['id' => 1]]]);

        $result = $this->handler->formatMarkAllWellKnown(42);

        $this->assertSame(7, $result['count']);
    }

    // =========================================================================
    // formatMarkAllIgnored tests
    // =========================================================================

    #[Test]
    public function formatMarkAllIgnoredDelegatesToMarkAllIgnored(): void
    {
        $this->discoveryService->expects($this->once())
            ->method('markAllWordsWithStatus')
            ->with(42, 98)
            ->willReturn([4, []]);

        $result = $this->handler->formatMarkAllIgnored(42);

        $this->assertSame(4, $result['count']);
    }

    // =========================================================================
    // saveTextPosition method signature tests
    // =========================================================================

    #[Test]
    public function saveTextPositionReturnsVoid(): void
    {
        $method = new \ReflectionMethod(TextPositionApiHandler::class, 'saveTextPosition');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('void', $returnType->getName());
    }

    #[Test]
    public function saveTextPositionAcceptsTwoIntParams(): void
    {
        $method = new \ReflectionMethod(TextPositionApiHandler::class, 'saveTextPosition');
        $params = $method->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('textid', $params[0]->getName());
        $this->assertSame('position', $params[1]->getName());
    }

    // =========================================================================
    // saveAudioPosition method signature tests
    // =========================================================================

    #[Test]
    public function saveAudioPositionReturnsVoid(): void
    {
        $method = new \ReflectionMethod(TextPositionApiHandler::class, 'saveAudioPosition');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('void', $returnType->getName());
    }

    #[Test]
    public function saveAudioPositionAcceptsTwoIntParams(): void
    {
        $method = new \ReflectionMethod(TextPositionApiHandler::class, 'saveAudioPosition');
        $params = $method->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('textid', $params[0]->getName());
        $this->assertSame('audioposition', $params[1]->getName());
    }
}
