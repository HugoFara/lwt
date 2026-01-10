<?php declare(strict_types=1);

namespace Tests\Backend\Modules\Vocabulary\Services;

use PHPUnit\Framework\TestCase;
use Lwt\Modules\Vocabulary\Application\Services\LemmaService;
use Lwt\Modules\Vocabulary\Domain\LemmatizerInterface;
use Lwt\Modules\Vocabulary\Infrastructure\MySqlTermRepository;

/**
 * Unit tests for LemmaService.
 *
 * Tests lemma suggestion and batch processing functionality.
 */
class LemmaServiceTest extends TestCase
{
    private LemmaService $service;
    private LemmatizerInterface $mockLemmatizer;
    private MySqlTermRepository $mockRepository;

    protected function setUp(): void
    {
        $this->mockLemmatizer = $this->createMock(LemmatizerInterface::class);
        $this->mockRepository = $this->createMock(MySqlTermRepository::class);
        $this->service = new LemmaService($this->mockLemmatizer, $this->mockRepository);
    }

    // =========================================================================
    // suggestLemma Tests
    // =========================================================================

    public function testSuggestLemmaReturnsLemmaFromLemmatizer(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('lemmatize')
            ->with('running', 'en')
            ->willReturn('run');

        $result = $this->service->suggestLemma('running', 'en');
        $this->assertSame('run', $result);
    }

    public function testSuggestLemmaReturnsNullWhenNotFound(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('lemmatize')
            ->with('unknownword', 'en')
            ->willReturn(null);

        $result = $this->service->suggestLemma('unknownword', 'en');
        $this->assertNull($result);
    }

    public function testSuggestLemmaReturnsNullForEmptyWord(): void
    {
        $this->mockLemmatizer
            ->expects($this->never())
            ->method('lemmatize');

        $result = $this->service->suggestLemma('', 'en');
        $this->assertNull($result);
    }

    public function testSuggestLemmaReturnsNullForEmptyLanguageCode(): void
    {
        $this->mockLemmatizer
            ->expects($this->never())
            ->method('lemmatize');

        $result = $this->service->suggestLemma('running', '');
        $this->assertNull($result);
    }

    // =========================================================================
    // suggestLemmasBatch Tests
    // =========================================================================

    public function testSuggestLemmasBatchReturnsMapping(): void
    {
        $words = ['running', 'walks', 'eating'];
        $expected = [
            'running' => 'run',
            'walks' => 'walk',
            'eating' => 'eat',
        ];

        $this->mockLemmatizer
            ->expects($this->once())
            ->method('lemmatizeBatch')
            ->with($words, 'en')
            ->willReturn($expected);

        $result = $this->service->suggestLemmasBatch($words, 'en');
        $this->assertSame($expected, $result);
    }

    public function testSuggestLemmasBatchReturnsEmptyForEmptyArray(): void
    {
        $this->mockLemmatizer
            ->expects($this->never())
            ->method('lemmatizeBatch');

        $result = $this->service->suggestLemmasBatch([], 'en');
        $this->assertSame([], $result);
    }

    public function testSuggestLemmasBatchReturnsEmptyForEmptyLanguage(): void
    {
        $this->mockLemmatizer
            ->expects($this->never())
            ->method('lemmatizeBatch');

        $result = $this->service->suggestLemmasBatch(['running'], '');
        $this->assertSame([], $result);
    }

    // =========================================================================
    // isAvailableForLanguage Tests
    // =========================================================================

    public function testIsAvailableForLanguageReturnsTrue(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('supportsLanguage')
            ->with('en')
            ->willReturn(true);

        $result = $this->service->isAvailableForLanguage('en');
        $this->assertTrue($result);
    }

    public function testIsAvailableForLanguageReturnsFalse(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('supportsLanguage')
            ->with('unknown')
            ->willReturn(false);

        $result = $this->service->isAvailableForLanguage('unknown');
        $this->assertFalse($result);
    }

    // =========================================================================
    // getAvailableLanguages Tests
    // =========================================================================

    public function testGetAvailableLanguagesReturnsArray(): void
    {
        $expected = ['en', 'de', 'fr'];

        $this->mockLemmatizer
            ->expects($this->once())
            ->method('getSupportedLanguages')
            ->willReturn($expected);

        $result = $this->service->getAvailableLanguages();
        $this->assertSame($expected, $result);
    }

    public function testGetAvailableLanguagesReturnsEmptyWhenNone(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('getSupportedLanguages')
            ->willReturn([]);

        $result = $this->service->getAvailableLanguages();
        $this->assertSame([], $result);
    }

    // =========================================================================
    // Phase 4: Smart Matching Tests
    // =========================================================================

    public function testLinkTextItemsByLemmaReturnsEmptyWhenLanguageNotSupported(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('supportsLanguage')
            ->with('unsupported')
            ->willReturn(false);

        $result = $this->service->linkTextItemsByLemma(1, 'unsupported');

        $this->assertSame(['linked' => 0, 'unmatched' => 0, 'errors' => 0], $result);
    }

    public function testLinkTextItemsByLemmaCallsSupportsLanguage(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('supportsLanguage')
            ->with('en')
            ->willReturn(false);

        // When language is not supported, method should return early
        $result = $this->service->linkTextItemsByLemma(1, 'en');

        $this->assertArrayHasKey('linked', $result);
        $this->assertArrayHasKey('unmatched', $result);
        $this->assertArrayHasKey('errors', $result);
    }
}
