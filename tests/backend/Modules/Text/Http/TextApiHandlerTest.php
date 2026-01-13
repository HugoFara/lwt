<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Text\Http;

use Lwt\Modules\Text\Http\TextApiHandler;
use Lwt\Modules\Vocabulary\Application\Services\WordDiscoveryService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for TextApiHandler.
 *
 * Tests text API operations including position saving and annotation handling.
 */
class TextApiHandlerTest extends TestCase
{
    /** @var WordDiscoveryService&MockObject */
    private WordDiscoveryService $discoveryService;

    private TextApiHandler $handler;

    protected function setUp(): void
    {
        $this->discoveryService = $this->createMock(WordDiscoveryService::class);
        $this->handler = new TextApiHandler($this->discoveryService);
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    public function testConstructorCreatesValidHandler(): void
    {
        $this->assertInstanceOf(TextApiHandler::class, $this->handler);
    }

    public function testConstructorAcceptsNullParameter(): void
    {
        $handler = new TextApiHandler(null);
        $this->assertInstanceOf(TextApiHandler::class, $handler);
    }

    // =========================================================================
    // saveImprText tests
    // =========================================================================

    public function testSaveImprTextHandlesRegularElement(): void
    {
        // Create a data object with the expected property
        $data = new \stdClass();
        $data->tx5 = 'test annotation';

        // We can't easily test this without database, but we can test the method exists
        // and handles input correctly (will fail gracefully without DB)
        $result = $this->handler->saveImprText(0, 'tx5', $data);

        // Without DB, we expect an error
        $this->assertIsArray($result);
    }

    public function testSaveImprTextHandlesRgElementWithEmptyValue(): void
    {
        $data = new \stdClass();
        $data->rg5 = '';
        $data->tx5 = 'fallback annotation';

        $result = $this->handler->saveImprText(0, 'rg5', $data);

        $this->assertIsArray($result);
    }

    public function testSaveImprTextHandlesRgElementWithValue(): void
    {
        $data = new \stdClass();
        $data->rg5 = 'romanization';
        $data->tx5 = 'translation';

        $result = $this->handler->saveImprText(0, 'rg5', $data);

        $this->assertIsArray($result);
    }

    // =========================================================================
    // formatSetTextPosition tests
    // =========================================================================

    public function testFormatSetTextPositionReturnsMessage(): void
    {
        // This will fail without DB but tests structure
        $result = $this->handler->formatSetTextPosition(1, 100);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('text', $result);
        $this->assertSame('Reading position set', $result['text']);
    }

    // =========================================================================
    // Data parsing tests (using reflection for private method)
    // =========================================================================

    public function testFormatAnnotationErrorReturnsOkForSuccess(): void
    {
        $method = new \ReflectionMethod(TextApiHandler::class, 'formatAnnotationError');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, ['success' => true]);

        $this->assertSame('OK', $result);
    }

    public function testFormatAnnotationErrorReturnsParseAnnotationFailed(): void
    {
        $method = new \ReflectionMethod(TextApiHandler::class, 'formatAnnotationError');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, [
            'success' => false,
            'error' => 'parse_annotation_failed'
        ]);

        $this->assertSame('Failed to parse annotation text', $result);
    }

    public function testFormatAnnotationErrorReturnsLineOutOfRange(): void
    {
        $method = new \ReflectionMethod(TextApiHandler::class, 'formatAnnotationError');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, [
            'success' => false,
            'error' => 'line_out_of_range',
            'requested' => 10,
            'available' => 5
        ]);

        $this->assertStringContainsString('10', $result);
        $this->assertStringContainsString('5', $result);
    }

    public function testFormatAnnotationErrorReturnsParseLineFailed(): void
    {
        $method = new \ReflectionMethod(TextApiHandler::class, 'formatAnnotationError');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, [
            'success' => false,
            'error' => 'parse_line_failed'
        ]);

        $this->assertSame('Failed to parse annotation line', $result);
    }

    public function testFormatAnnotationErrorReturnsPunctuationTerm(): void
    {
        $method = new \ReflectionMethod(TextApiHandler::class, 'formatAnnotationError');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, [
            'success' => false,
            'error' => 'punctuation_term',
            'position' => -1
        ]);

        $this->assertStringContainsString('punctuation', $result);
        $this->assertStringContainsString('-1', $result);
    }

    public function testFormatAnnotationErrorReturnsInsufficientColumns(): void
    {
        $method = new \ReflectionMethod(TextApiHandler::class, 'formatAnnotationError');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, [
            'success' => false,
            'error' => 'insufficient_columns',
            'found' => 2
        ]);

        $this->assertStringContainsString('columns', $result);
        $this->assertStringContainsString('2', $result);
    }

    public function testFormatAnnotationErrorReturnsUnknownError(): void
    {
        $method = new \ReflectionMethod(TextApiHandler::class, 'formatAnnotationError');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, [
            'success' => false,
            'error' => 'some_unknown_error'
        ]);

        $this->assertSame('Unknown error', $result);
    }

    public function testFormatAnnotationErrorHandlesMissingErrorKey(): void
    {
        $method = new \ReflectionMethod(TextApiHandler::class, 'formatAnnotationError');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, [
            'success' => false
        ]);

        $this->assertSame('Unknown error', $result);
    }

    public function testFormatAnnotationErrorHandlesMissingOptionalFields(): void
    {
        $method = new \ReflectionMethod(TextApiHandler::class, 'formatAnnotationError');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, [
            'success' => false,
            'error' => 'line_out_of_range'
        ]);

        $this->assertStringContainsString('?', $result);
    }

    // =========================================================================
    // saveImprText additional tests
    // =========================================================================

    public function testSaveImprTextHandlesMissingProperty(): void
    {
        $data = new \stdClass();

        $result = $this->handler->saveImprText(0, 'tx5', $data);

        $this->assertIsArray($result);
    }

    public function testSaveImprTextExtractsNumberFromElementId(): void
    {
        $data = new \stdClass();
        $data->tx10 = 'test value';

        $result = $this->handler->saveImprText(0, 'tx10', $data);

        $this->assertIsArray($result);
    }

    public function testSaveImprTextHandlesRgWithNonEmptyTranslation(): void
    {
        $data = new \stdClass();
        $data->rg5 = 'romanization value';
        $data->tx5 = 'translation value';

        $result = $this->handler->saveImprText(0, 'rg5', $data);

        $this->assertIsArray($result);
    }

    // =========================================================================
    // formatGetRecommendedTexts tests
    // =========================================================================

    public function testFormatGetRecommendedTextsReturnsArray(): void
    {
        // Without DB this will return empty/error, but tests structure
        $result = $this->handler->formatGetRecommendedTexts(1, []);

        $this->assertIsArray($result);
    }

    // =========================================================================
    // formatGetTextScore tests
    // =========================================================================

    public function testFormatGetTextScoreReturnsArray(): void
    {
        // Without DB this will return empty/error, but tests structure
        $result = $this->handler->formatGetTextScore(1);

        $this->assertIsArray($result);
    }

    // =========================================================================
    // formatTermTranslations tests
    // =========================================================================

    public function testFormatTermTranslationsReturnsArray(): void
    {
        $result = $this->handler->formatTermTranslations('test', 1);

        $this->assertIsArray($result);
    }

    // =========================================================================
    // Constructor with null creates default service
    // =========================================================================

    public function testConstructorCreatesDefaultService(): void
    {
        $handler = new TextApiHandler(null);

        $this->assertInstanceOf(TextApiHandler::class, $handler);
    }

    // =========================================================================
    // setTextPosition tests
    // =========================================================================

    public function testSetTextPositionReturnsMessage(): void
    {
        $result = $this->handler->formatSetTextPosition(1, 0);

        $this->assertArrayHasKey('text', $result);
        $this->assertSame('Reading position set', $result['text']);
    }

    public function testSetTextPositionWithDifferentPositions(): void
    {
        $result1 = $this->handler->formatSetTextPosition(1, 100);
        $result2 = $this->handler->formatSetTextPosition(1, -1);

        $this->assertSame('Reading position set', $result1['text']);
        $this->assertSame('Reading position set', $result2['text']);
    }
}
