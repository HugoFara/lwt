<?php declare(strict_types=1);

namespace Tests\Backend\Modules\Vocabulary\Infrastructure\Lemmatizers;

use PHPUnit\Framework\TestCase;
use Lwt\Modules\Vocabulary\Infrastructure\Lemmatizers\DictionaryLemmatizer;

/**
 * Unit tests for DictionaryLemmatizer.
 *
 * Tests dictionary loading, lemma lookup, and batch processing.
 */
class DictionaryLemmatizerTest extends TestCase
{
    private string $testDictPath;
    private DictionaryLemmatizer $lemmatizer;

    protected function setUp(): void
    {
        // Use the actual lemma dictionaries directory for testing
        $this->testDictPath = dirname(__DIR__, 6) . '/data/lemma-dictionaries';
        $this->lemmatizer = new DictionaryLemmatizer($this->testDictPath);
    }

    // =========================================================================
    // Constructor Tests
    // =========================================================================

    public function testConstructorWithCustomPath(): void
    {
        $customPath = '/tmp/custom-dict-path';
        $lemmatizer = new DictionaryLemmatizer($customPath);
        $this->assertSame($customPath, $lemmatizer->getDictionaryPath());
    }

    public function testConstructorWithDefaultPath(): void
    {
        $lemmatizer = new DictionaryLemmatizer();
        $this->assertNotEmpty($lemmatizer->getDictionaryPath());
    }

    // =========================================================================
    // lemmatize Tests
    // =========================================================================

    public function testLemmatizeReturnsLemmaForKnownWord(): void
    {
        // 'running' should map to 'run' in our English dictionary
        $result = $this->lemmatizer->lemmatize('running', 'en');
        $this->assertSame('run', $result);
    }

    public function testLemmatizeReturnsNullForUnknownWord(): void
    {
        $result = $this->lemmatizer->lemmatize('xyznonexistent', 'en');
        $this->assertNull($result);
    }

    public function testLemmatizeCaseInsensitive(): void
    {
        // Should work with uppercase input
        $result = $this->lemmatizer->lemmatize('RUNNING', 'en');
        $this->assertSame('run', $result);

        $result = $this->lemmatizer->lemmatize('Running', 'en');
        $this->assertSame('run', $result);
    }

    public function testLemmatizeVerbForms(): void
    {
        $this->assertSame('run', $this->lemmatizer->lemmatize('runs', 'en'));
        $this->assertSame('run', $this->lemmatizer->lemmatize('ran', 'en'));
        $this->assertSame('walk', $this->lemmatizer->lemmatize('walking', 'en'));
        $this->assertSame('walk', $this->lemmatizer->lemmatize('walked', 'en'));
    }

    public function testLemmatizeNounPlurals(): void
    {
        $this->assertSame('child', $this->lemmatizer->lemmatize('children', 'en'));
        $this->assertSame('woman', $this->lemmatizer->lemmatize('women', 'en'));
        $this->assertSame('man', $this->lemmatizer->lemmatize('men', 'en'));
        $this->assertSame('foot', $this->lemmatizer->lemmatize('feet', 'en'));
    }

    public function testLemmatizeAdjectives(): void
    {
        $this->assertSame('good', $this->lemmatizer->lemmatize('better', 'en'));
        $this->assertSame('good', $this->lemmatizer->lemmatize('best', 'en'));
        $this->assertSame('bad', $this->lemmatizer->lemmatize('worse', 'en'));
        $this->assertSame('bad', $this->lemmatizer->lemmatize('worst', 'en'));
    }

    public function testLemmatizeReturnsNullForBaseForm(): void
    {
        // Base forms shouldn't be in the dictionary (we only store mappings)
        // 'run' is a base form, so it won't have an entry
        $result = $this->lemmatizer->lemmatize('run', 'en');
        $this->assertNull($result);
    }

    // =========================================================================
    // lemmatizeBatch Tests
    // =========================================================================

    public function testLemmatizeBatchReturnsMapping(): void
    {
        $words = ['running', 'walks', 'eating'];
        $result = $this->lemmatizer->lemmatizeBatch($words, 'en');

        $this->assertArrayHasKey('running', $result);
        $this->assertArrayHasKey('walks', $result);
        $this->assertArrayHasKey('eating', $result);

        $this->assertSame('run', $result['running']);
        $this->assertSame('walk', $result['walks']);
        $this->assertSame('eat', $result['eating']);
    }

    public function testLemmatizeBatchWithMixedKnownUnknown(): void
    {
        $words = ['running', 'xyznonexistent', 'walking'];
        $result = $this->lemmatizer->lemmatizeBatch($words, 'en');

        $this->assertSame('run', $result['running']);
        $this->assertNull($result['xyznonexistent']);
        $this->assertSame('walk', $result['walking']);
    }

    public function testLemmatizeBatchWithEmptyArray(): void
    {
        $result = $this->lemmatizer->lemmatizeBatch([], 'en');
        $this->assertSame([], $result);
    }

    // =========================================================================
    // supportsLanguage Tests
    // =========================================================================

    public function testSupportsLanguageReturnsTrueForEnglish(): void
    {
        $result = $this->lemmatizer->supportsLanguage('en');
        $this->assertTrue($result);
    }

    public function testSupportsLanguageReturnsFalseForUnsupported(): void
    {
        $result = $this->lemmatizer->supportsLanguage('nonexistent');
        $this->assertFalse($result);
    }

    public function testSupportsLanguageNormalizesCode(): void
    {
        // Should normalize 'en-US' to 'en'
        $result = $this->lemmatizer->supportsLanguage('en-US');
        $this->assertTrue($result);

        // Should normalize 'en_GB' to 'en'
        $result = $this->lemmatizer->supportsLanguage('en_GB');
        $this->assertTrue($result);
    }

    // =========================================================================
    // getSupportedLanguages Tests
    // =========================================================================

    public function testGetSupportedLanguagesReturnsArray(): void
    {
        $result = $this->lemmatizer->getSupportedLanguages();
        $this->assertIsArray($result);
        $this->assertContains('en', $result);
    }

    // =========================================================================
    // loadDictionary Tests
    // =========================================================================

    public function testLoadDictionaryReturnsTrueForExisting(): void
    {
        $result = $this->lemmatizer->loadDictionary('en');
        $this->assertTrue($result);
    }

    public function testLoadDictionaryReturnsFalseForNonexistent(): void
    {
        $result = $this->lemmatizer->loadDictionary('nonexistent');
        $this->assertFalse($result);
    }

    // =========================================================================
    // getStatistics Tests
    // =========================================================================

    public function testGetStatisticsReturnsEntryCounts(): void
    {
        // Load the English dictionary first
        $this->lemmatizer->loadDictionary('en');
        $stats = $this->lemmatizer->getStatistics();

        $this->assertArrayHasKey('en', $stats);
        $this->assertArrayHasKey('entries', $stats['en']);
        $this->assertArrayHasKey('file_size', $stats['en']);
        $this->assertGreaterThan(0, $stats['en']['entries']);
    }

    // =========================================================================
    // clearCache Tests
    // =========================================================================

    public function testClearCacheRemovesLoadedDictionaries(): void
    {
        $this->lemmatizer->loadDictionary('en');
        $statsBefore = $this->lemmatizer->getStatistics();
        $this->assertArrayHasKey('en', $statsBefore);

        $this->lemmatizer->clearCache();
        $statsAfter = $this->lemmatizer->getStatistics();
        $this->assertEmpty($statsAfter);
    }

    // =========================================================================
    // Language Code Normalization Tests
    // =========================================================================

    public function testNormalizesThreeLetterCodes(): void
    {
        // 'eng' should normalize to 'en'
        $result = $this->lemmatizer->lemmatize('running', 'eng');
        $this->assertSame('run', $result);
    }

    public function testNormalizesLocaleCodes(): void
    {
        // 'en-US' should normalize to 'en'
        $result = $this->lemmatizer->lemmatize('running', 'en-US');
        $this->assertSame('run', $result);
    }
}
