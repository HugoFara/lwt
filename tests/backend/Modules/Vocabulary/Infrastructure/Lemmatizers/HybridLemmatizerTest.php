<?php declare(strict_types=1);

namespace Tests\Backend\Modules\Vocabulary\Infrastructure\Lemmatizers;

use PHPUnit\Framework\TestCase;
use Lwt\Modules\Vocabulary\Infrastructure\Lemmatizers\DictionaryLemmatizer;
use Lwt\Modules\Vocabulary\Infrastructure\Lemmatizers\HybridLemmatizer;
use Lwt\Modules\Vocabulary\Infrastructure\Lemmatizers\NlpServiceLemmatizer;

/**
 * Unit tests for HybridLemmatizer.
 *
 * Tests the hybrid lemmatization strategy that combines
 * dictionary and NLP service lemmatization.
 */
class HybridLemmatizerTest extends TestCase
{
    private HybridLemmatizer $lemmatizer;
    private DictionaryLemmatizer $mockDictionary;
    private NlpServiceLemmatizer $mockNlp;

    protected function setUp(): void
    {
        $this->mockDictionary = $this->createMock(DictionaryLemmatizer::class);
        $this->mockNlp = $this->createMock(NlpServiceLemmatizer::class);
        $this->lemmatizer = new HybridLemmatizer($this->mockDictionary, $this->mockNlp);
    }

    // =========================================================================
    // lemmatize Tests
    // =========================================================================

    public function testLemmatizeReturnsNullForEmptyWord(): void
    {
        $this->mockDictionary->expects($this->never())
            ->method('lemmatize');
        $this->mockNlp->expects($this->never())
            ->method('lemmatize');

        $result = $this->lemmatizer->lemmatize('', 'en');
        $this->assertNull($result);
    }

    public function testLemmatizeUsesDictionaryFirst(): void
    {
        $this->mockDictionary->expects($this->once())
            ->method('lemmatize')
            ->with('running', 'en')
            ->willReturn('run');

        // NLP should not be called if dictionary found result
        $this->mockNlp->expects($this->never())
            ->method('lemmatize');

        $result = $this->lemmatizer->lemmatize('running', 'en');
        $this->assertSame('run', $result);
    }

    public function testLemmatizeFallsBackToNlpWhenDictionaryMisses(): void
    {
        $this->mockDictionary->expects($this->once())
            ->method('lemmatize')
            ->with('uncommonword', 'en')
            ->willReturn(null);

        $this->mockNlp->expects($this->once())
            ->method('lemmatize')
            ->with('uncommonword', 'en')
            ->willReturn('uncommon');

        $result = $this->lemmatizer->lemmatize('uncommonword', 'en');
        $this->assertSame('uncommon', $result);
    }

    public function testLemmatizeReturnsNullWhenBothMiss(): void
    {
        $this->mockDictionary->expects($this->once())
            ->method('lemmatize')
            ->with('xyz', 'en')
            ->willReturn(null);

        $this->mockNlp->expects($this->once())
            ->method('lemmatize')
            ->with('xyz', 'en')
            ->willReturn(null);

        $result = $this->lemmatizer->lemmatize('xyz', 'en');
        $this->assertNull($result);
    }

    // =========================================================================
    // lemmatizeBatch Tests
    // =========================================================================

    public function testLemmatizeBatchReturnsEmptyForEmptyArray(): void
    {
        $this->mockDictionary->expects($this->never())
            ->method('lemmatizeBatch');
        $this->mockNlp->expects($this->never())
            ->method('lemmatizeBatch');

        $result = $this->lemmatizer->lemmatizeBatch([], 'en');
        $this->assertSame([], $result);
    }

    public function testLemmatizeBatchUsesDictionaryFirst(): void
    {
        $words = ['running', 'walking'];

        $this->mockDictionary->expects($this->once())
            ->method('lemmatizeBatch')
            ->with($words, 'en')
            ->willReturn(['running' => 'run', 'walking' => 'walk']);

        // NLP should not be called if dictionary found all results
        $this->mockNlp->expects($this->never())
            ->method('lemmatizeBatch');

        $result = $this->lemmatizer->lemmatizeBatch($words, 'en');
        $this->assertSame(['running' => 'run', 'walking' => 'walk'], $result);
    }

    public function testLemmatizeBatchFallsBackToNlpForMissing(): void
    {
        $words = ['running', 'uncommonword', 'walking'];

        $this->mockDictionary->expects($this->once())
            ->method('lemmatizeBatch')
            ->with($words, 'en')
            ->willReturn([
                'running' => 'run',
                'uncommonword' => null,  // Not in dictionary
                'walking' => 'walk'
            ]);

        // NLP should be called only for words not found in dictionary
        $this->mockNlp->expects($this->once())
            ->method('lemmatizeBatch')
            ->with(['uncommonword'], 'en')
            ->willReturn(['uncommonword' => 'uncommon']);

        $result = $this->lemmatizer->lemmatizeBatch($words, 'en');

        $this->assertSame('run', $result['running']);
        $this->assertSame('uncommon', $result['uncommonword']);
        $this->assertSame('walk', $result['walking']);
    }

    // =========================================================================
    // supportsLanguage Tests
    // =========================================================================

    public function testSupportsLanguageReturnsTrueIfDictionarySupports(): void
    {
        $this->mockDictionary->expects($this->once())
            ->method('supportsLanguage')
            ->with('en')
            ->willReturn(true);

        // NLP should not be checked if dictionary supports
        $this->mockNlp->expects($this->never())
            ->method('supportsLanguage');

        $result = $this->lemmatizer->supportsLanguage('en');
        $this->assertTrue($result);
    }

    public function testSupportsLanguageReturnsTrueIfNlpSupports(): void
    {
        $this->mockDictionary->expects($this->once())
            ->method('supportsLanguage')
            ->with('fi')
            ->willReturn(false);

        $this->mockNlp->expects($this->once())
            ->method('supportsLanguage')
            ->with('fi')
            ->willReturn(true);

        $result = $this->lemmatizer->supportsLanguage('fi');
        $this->assertTrue($result);
    }

    public function testSupportsLanguageReturnsFalseIfNeitherSupports(): void
    {
        $this->mockDictionary->expects($this->once())
            ->method('supportsLanguage')
            ->with('xyz')
            ->willReturn(false);

        $this->mockNlp->expects($this->once())
            ->method('supportsLanguage')
            ->with('xyz')
            ->willReturn(false);

        $result = $this->lemmatizer->supportsLanguage('xyz');
        $this->assertFalse($result);
    }

    // =========================================================================
    // getSupportedLanguages Tests
    // =========================================================================

    public function testGetSupportedLanguagesCombinesBothSources(): void
    {
        $this->mockDictionary->expects($this->once())
            ->method('getSupportedLanguages')
            ->willReturn(['en', 'de']);

        $this->mockNlp->expects($this->once())
            ->method('getSupportedLanguages')
            ->willReturn(['en', 'fr', 'es']);

        $result = $this->lemmatizer->getSupportedLanguages();

        // Should contain unique languages from both
        $this->assertContains('en', $result);
        $this->assertContains('de', $result);
        $this->assertContains('fr', $result);
        $this->assertContains('es', $result);
        // No duplicates
        $this->assertCount(4, $result);
    }

    // =========================================================================
    // Helper Method Tests
    // =========================================================================

    public function testHasDictionarySupportDelegatesToDictionary(): void
    {
        $this->mockDictionary->expects($this->once())
            ->method('supportsLanguage')
            ->with('en')
            ->willReturn(true);

        $result = $this->lemmatizer->hasDictionarySupport('en');
        $this->assertTrue($result);
    }

    public function testHasNlpSupportDelegatesToNlp(): void
    {
        $this->mockNlp->expects($this->once())
            ->method('supportsLanguage')
            ->with('fi')
            ->willReturn(true);

        $result = $this->lemmatizer->hasNlpSupport('fi');
        $this->assertTrue($result);
    }
}
