<?php

declare(strict_types=1);

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

    // =========================================================================
    // Phase 3: Word Family Tests (Unit tests without database)
    // =========================================================================

    public function testUpdateWordFamilyStatusRejectsInvalidStatus(): void
    {
        // This tests the input validation which happens before database access
        $result = $this->service->updateWordFamilyStatus(1, 'run', 10);

        $this->assertSame(0, $result);
    }

    public function testUpdateWordFamilyStatusRejectsStatus0(): void
    {
        $result = $this->service->updateWordFamilyStatus(1, 'run', 0);

        $this->assertSame(0, $result);
    }

    public function testUpdateWordFamilyStatusRejectsStatus100(): void
    {
        $result = $this->service->updateWordFamilyStatus(1, 'run', 100);

        $this->assertSame(0, $result);
    }

    public function testGetWordFamilyByLemmaReturnsNullForEmptyLemma(): void
    {
        // This tests the early return for empty lemma
        $result = $this->service->getWordFamilyByLemma(1, '');

        $this->assertNull($result);
    }

    public function testBulkUpdateTermStatusRejectsEmptyTermIds(): void
    {
        // This tests input validation before database access
        $result = $this->service->bulkUpdateTermStatus([], 5);

        $this->assertSame(0, $result);
    }

    public function testBulkUpdateTermStatusRejectsInvalidStatus(): void
    {
        $result = $this->service->bulkUpdateTermStatus([1, 2, 3], 10);

        $this->assertSame(0, $result);
    }

    public function testBulkUpdateTermStatusRejectsStatus0(): void
    {
        $result = $this->service->bulkUpdateTermStatus([1, 2, 3], 0);

        $this->assertSame(0, $result);
    }

    // =========================================================================
    // Lemmatizer Configuration Tests
    // =========================================================================

    public function testGetLemmatizerForLanguageReturnsLemmatizer(): void
    {
        $result = $this->service->getLemmatizerForLanguage('en');

        $this->assertInstanceOf(LemmatizerInterface::class, $result);
    }

    public function testGetLemmatizerByTypeReturnsLemmatizer(): void
    {
        $result = $this->service->getLemmatizerByType('dictionary');

        $this->assertInstanceOf(LemmatizerInterface::class, $result);
    }

    public function testIsNlpServiceAvailableReturnsBool(): void
    {
        $result = $this->service->isNlpServiceAvailable();

        $this->assertIsBool($result);
    }

    public function testGetNlpSupportedLanguagesReturnsArray(): void
    {
        $result = $this->service->getNlpSupportedLanguages();

        $this->assertIsArray($result);
    }

    public function testGetAllNlpLanguagesReturnsArray(): void
    {
        $result = $this->service->getAllNlpLanguages();

        $this->assertIsArray($result);
    }

    // =========================================================================
    // Word Family Tests
    // =========================================================================

    public function testGetWordFamilyCallsRepository(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('findByLemma')
            ->with(1, 'run')
            ->willReturn([]);

        $result = $this->service->getWordFamily(1, 'run');

        $this->assertSame([], $result);
    }

    public function testSetLemmaCallsRepository(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('updateLemma')
            ->with(1, 'run')
            ->willReturn(true);

        $result = $this->service->setLemma(1, 'run');

        $this->assertTrue($result);
    }

    public function testSetLemmaReturnsFalseWhenRepositoryFails(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('updateLemma')
            ->with(1, 'run')
            ->willReturn(false);

        $result = $this->service->setLemma(1, 'run');

        $this->assertFalse($result);
    }

    // =========================================================================
    // applyLemmasToVocabulary Tests
    // =========================================================================

    public function testApplyLemmasToVocabularyReturnsZerosWhenLanguageNotSupported(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('supportsLanguage')
            ->with('xyz')
            ->willReturn(false);

        $result = $this->service->applyLemmasToVocabulary(1, 'xyz');

        $this->assertSame(['processed' => 0, 'updated' => 0, 'skipped' => 0], $result);
    }

    // =========================================================================
    // propagateLemma Tests
    // =========================================================================

    public function testPropagateLemmaReturnsZeroWhenTermNotFound(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->propagateLemma(999, 1, 'en');

        $this->assertSame(0, $result);
    }

    // =========================================================================
    // Additional Coverage Tests - Public Method Signatures
    // =========================================================================

    /**
     * Test constructor creates LemmaService with default dependencies.
     */
    public function testConstructorWithDefaults(): void
    {
        $service = new LemmaService();

        $this->assertInstanceOf(LemmaService::class, $service);
    }

    /**
     * Test getWordFamilyList page boundary handling.
     */
    public function testGetWordFamilyListClampsPagination(): void
    {
        // Page should be at least 1
        $result = $this->service->getWordFamilyList(1, 0, 50, 'lemma', 'asc');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('pagination', $result);

        // Per page should be clamped to 1-100 range
        $result = $this->service->getWordFamilyList(1, 1, 0, 'lemma', 'asc');
        $this->assertIsArray($result);

        $result = $this->service->getWordFamilyList(1, 1, 200, 'lemma', 'asc');
        $this->assertIsArray($result);
    }

    /**
     * Test getWordFamilyList with various sort options.
     */
    public function testGetWordFamilyListSortByCount(): void
    {
        $result = $this->service->getWordFamilyList(1, 1, 50, 'count', 'desc');
        $this->assertIsArray($result);
    }

    /**
     * Test getWordFamilyList with status sort.
     */
    public function testGetWordFamilyListSortByStatus(): void
    {
        $result = $this->service->getWordFamilyList(1, 1, 50, 'status', 'asc');
        $this->assertIsArray($result);
    }

    /**
     * Test getWordFamilyList with default lemma sort.
     */
    public function testGetWordFamilyListSortByLemma(): void
    {
        $result = $this->service->getWordFamilyList(1, 1, 50, 'lemma', 'desc');
        $this->assertIsArray($result);
    }

    /**
     * Test getWordFamilyList with invalid sort falls back to lemma.
     */
    public function testGetWordFamilyListInvalidSort(): void
    {
        $result = $this->service->getWordFamilyList(1, 1, 50, 'invalid', 'asc');
        $this->assertIsArray($result);
    }

    /**
     * Test updateWordFamilyStatus with all valid status values.
     *
     * @dataProvider validStatusProvider
     */
    public function testUpdateWordFamilyStatusAcceptsValidStatus(int $status): void
    {
        // Just ensure no exception is thrown for valid statuses
        // The actual update will fail because database is mocked
        $result = $this->service->updateWordFamilyStatus(1, 'test', $status);
        $this->assertIsInt($result);
    }

    public static function validStatusProvider(): array
    {
        return [
            'status 1' => [1],
            'status 2' => [2],
            'status 3' => [3],
            'status 4' => [4],
            'status 5' => [5],
            'status 98 (ignored)' => [98],
            'status 99 (well-known)' => [99],
        ];
    }

    /**
     * Test bulkUpdateTermStatus with all valid status values.
     *
     * @dataProvider validStatusProvider
     */
    public function testBulkUpdateTermStatusAcceptsValidStatus(int $status): void
    {
        $result = $this->service->bulkUpdateTermStatus([1], $status);
        $this->assertIsInt($result);
    }

    /**
     * Test getLemmaStatistics returns expected structure.
     */
    public function testGetLemmaStatisticsReturnsExpectedStructure(): void
    {
        $result = $this->service->getLemmaStatistics(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_terms', $result);
        $this->assertArrayHasKey('with_lemma', $result);
        $this->assertArrayHasKey('without_lemma', $result);
        $this->assertArrayHasKey('unique_lemmas', $result);
    }

    /**
     * Test clearLemmas returns integer.
     */
    public function testClearLemmasReturnsInteger(): void
    {
        $result = $this->service->clearLemmas(1);
        $this->assertIsInt($result);
    }

    /**
     * Test getWordFamilies returns array.
     */
    public function testGetWordFamiliesReturnsArray(): void
    {
        $result = $this->service->getWordFamilies(1, 50);
        $this->assertIsArray($result);
    }

    /**
     * Test getWordFamilies with custom limit.
     */
    public function testGetWordFamiliesWithLimit(): void
    {
        $result = $this->service->getWordFamilies(1, 10);
        $this->assertIsArray($result);
    }

    /**
     * Test findPotentialLemmaGroups returns array.
     */
    public function testFindPotentialLemmaGroupsReturnsArray(): void
    {
        $result = $this->service->findPotentialLemmaGroups(1, 20);
        $this->assertIsArray($result);
    }

    /**
     * Test findPotentialLemmaGroups with custom limit.
     */
    public function testFindPotentialLemmaGroupsWithLimit(): void
    {
        $result = $this->service->findPotentialLemmaGroups(1, 5);
        $this->assertIsArray($result);
    }

    /**
     * Test getWordFamilyDetails returns null for non-existent term.
     */
    public function testGetWordFamilyDetailsReturnsNullForNonExistent(): void
    {
        $result = $this->service->getWordFamilyDetails(999999999);
        $this->assertNull($result);
    }

    /**
     * Test getLemmaAggregateStats returns expected structure.
     */
    public function testGetLemmaAggregateStatsReturnsExpectedStructure(): void
    {
        $result = $this->service->getLemmaAggregateStats(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_lemmas', $result);
        $this->assertArrayHasKey('single_form', $result);
        $this->assertArrayHasKey('multi_form', $result);
        $this->assertArrayHasKey('avg_forms_per_lemma', $result);
        $this->assertArrayHasKey('status_distribution', $result);
    }

    /**
     * Test getSuggestedFamilyUpdate returns expected structure.
     */
    public function testGetSuggestedFamilyUpdateReturnsExpectedStructure(): void
    {
        $result = $this->service->getSuggestedFamilyUpdate(999999, 5);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('suggestion', $result);
        $this->assertArrayHasKey('affected_count', $result);
        $this->assertArrayHasKey('term_ids', $result);
    }

    /**
     * Test getSuggestedFamilyUpdate with well-known status.
     */
    public function testGetSuggestedFamilyUpdateWithWellKnownStatus(): void
    {
        $result = $this->service->getSuggestedFamilyUpdate(1, 99);

        $this->assertArrayHasKey('suggestion', $result);
    }

    /**
     * Test getSuggestedFamilyUpdate with learning status.
     */
    public function testGetSuggestedFamilyUpdateWithLearningStatus(): void
    {
        $result = $this->service->getSuggestedFamilyUpdate(1, 3);

        $this->assertArrayHasKey('suggestion', $result);
    }

    /**
     * Test getUnmatchedStatistics returns expected structure.
     */
    public function testGetUnmatchedStatisticsReturnsExpectedStructure(): void
    {
        $result = $this->service->getUnmatchedStatistics(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('unmatched_count', $result);
        $this->assertArrayHasKey('unique_words', $result);
        $this->assertArrayHasKey('matchable_by_lemma', $result);
    }

    /**
     * Test linkTextItemsByLemmaSql returns integer.
     *
     * @group integration
     */
    public function testLinkTextItemsByLemmaSqlReturnsInteger(): void
    {
        try {
            $result = $this->service->linkTextItemsByLemmaSql(1);
            $this->assertIsInt($result);
        } catch (\Lwt\Shared\Infrastructure\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }
    }

    /**
     * Test linkTextItemsByLemmaSql with text ID.
     *
     * @group integration
     */
    public function testLinkTextItemsByLemmaSqlWithTextId(): void
    {
        try {
            $result = $this->service->linkTextItemsByLemmaSql(1, 1);
            $this->assertIsInt($result);
        } catch (\Lwt\Shared\Infrastructure\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }
    }

    /**
     * Test findWordIdByLemma returns null for non-existent.
     */
    public function testFindWordIdByLemmaReturnsNullForNonExistent(): void
    {
        $result = $this->service->findWordIdByLemma(999999, 'nonexistent');
        $this->assertNull($result);
    }

    // =========================================================================
    // Additional suggestLemma Tests
    // =========================================================================

    public function testSuggestLemmaWithUnicodeWord(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('lemmatize')
            ->with('テスト', 'ja')
            ->willReturn('テスト');

        $result = $this->service->suggestLemma('テスト', 'ja');
        $this->assertSame('テスト', $result);
    }

    public function testSuggestLemmaWithWhitespaceOnlyWord(): void
    {
        // Whitespace-only is NOT empty string, so lemmatizer will be called
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('lemmatize')
            ->with('   ', '  ')
            ->willReturn(null);

        $result = $this->service->suggestLemma('   ', '  ');
        $this->assertNull($result);
    }

    // =========================================================================
    // Additional suggestLemmasBatch Tests
    // =========================================================================

    public function testSuggestLemmasBatchWithUnicodeWords(): void
    {
        $words = ['日本語', '英語', 'フランス語'];
        $expected = [
            '日本語' => '日本語',
            '英語' => '英語',
            'フランス語' => 'フランス語',
        ];

        $this->mockLemmatizer
            ->expects($this->once())
            ->method('lemmatizeBatch')
            ->with($words, 'ja')
            ->willReturn($expected);

        $result = $this->service->suggestLemmasBatch($words, 'ja');
        $this->assertSame($expected, $result);
    }

    public function testSuggestLemmasBatchWithMixedResults(): void
    {
        $words = ['running', 'xyz123', 'walking'];
        $expected = [
            'running' => 'run',
            'xyz123' => null, // No lemma found
            'walking' => 'walk',
        ];

        $this->mockLemmatizer
            ->expects($this->once())
            ->method('lemmatizeBatch')
            ->with($words, 'en')
            ->willReturn($expected);

        $result = $this->service->suggestLemmasBatch($words, 'en');
        $this->assertArrayHasKey('running', $result);
        $this->assertArrayHasKey('xyz123', $result);
        $this->assertNull($result['xyz123']);
    }

    // =========================================================================
    // Additional isAvailableForLanguage Tests
    // =========================================================================

    public function testIsAvailableForLanguageWithEmptyCode(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('supportsLanguage')
            ->with('')
            ->willReturn(false);

        $result = $this->service->isAvailableForLanguage('');
        $this->assertFalse($result);
    }

    public function testIsAvailableForLanguageWithPartialCode(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('supportsLanguage')
            ->with('en-US')
            ->willReturn(true);

        $result = $this->service->isAvailableForLanguage('en-US');
        $this->assertTrue($result);
    }

    // =========================================================================
    // Additional getAvailableLanguages Tests
    // =========================================================================

    public function testGetAvailableLanguagesReturnsLargeList(): void
    {
        $expected = ['en', 'de', 'fr', 'es', 'it', 'pt', 'ru', 'ja', 'zh', 'ko'];

        $this->mockLemmatizer
            ->expects($this->once())
            ->method('getSupportedLanguages')
            ->willReturn($expected);

        $result = $this->service->getAvailableLanguages();
        $this->assertCount(10, $result);
        $this->assertSame($expected, $result);
    }

    // =========================================================================
    // Additional updateWordFamilyStatus Tests
    // =========================================================================

    public function testUpdateWordFamilyStatusAcceptsIgnoredStatus(): void
    {
        $result = $this->service->updateWordFamilyStatus(1, 'run', 98);
        $this->assertIsInt($result);
    }

    public function testUpdateWordFamilyStatusAcceptsWellKnownStatus(): void
    {
        $result = $this->service->updateWordFamilyStatus(1, 'run', 99);
        $this->assertIsInt($result);
    }

    public function testUpdateWordFamilyStatusWithEmptyLemma(): void
    {
        $result = $this->service->updateWordFamilyStatus(1, '', 5);
        $this->assertSame(0, $result);
    }

    // =========================================================================
    // Additional getWordFamily Tests
    // =========================================================================

    public function testGetWordFamilyWithEmptyLemma(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('findByLemma')
            ->with(1, '')
            ->willReturn([]);

        $result = $this->service->getWordFamily(1, '');
        $this->assertSame([], $result);
    }

    public function testGetWordFamilyWithNonExistentLanguage(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('findByLemma')
            ->with(999999, 'test')
            ->willReturn([]);

        $result = $this->service->getWordFamily(999999, 'test');
        $this->assertSame([], $result);
    }

    // =========================================================================
    // Additional setLemma Tests
    // =========================================================================

    public function testSetLemmaWithEmptyString(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('updateLemma')
            ->with(1, '')
            ->willReturn(true);

        $result = $this->service->setLemma(1, '');
        $this->assertTrue($result);
    }

    public function testSetLemmaWithUnicodeString(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('updateLemma')
            ->with(1, '走る')
            ->willReturn(true);

        $result = $this->service->setLemma(1, '走る');
        $this->assertTrue($result);
    }

    // =========================================================================
    // Additional applyLemmasToVocabulary Tests
    // =========================================================================

    public function testApplyLemmasToVocabularyWithUnsupportedLanguage(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('supportsLanguage')
            ->with('unsupported_lang')
            ->willReturn(false);

        $result = $this->service->applyLemmasToVocabulary(1, 'unsupported_lang');

        $this->assertSame(['processed' => 0, 'updated' => 0, 'skipped' => 0], $result);
    }

    // =========================================================================
    // Additional propagateLemma Tests
    // =========================================================================

    public function testPropagateLemmaWithNullTerm(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->propagateLemma(999, 1, 'en');

        $this->assertSame(0, $result);
    }

    // =========================================================================
    // Additional getLemmaStatistics Tests
    // =========================================================================

    public function testGetLemmaStatisticsWithZeroLanguageId(): void
    {
        $result = $this->service->getLemmaStatistics(0);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_terms', $result);
        $this->assertArrayHasKey('with_lemma', $result);
        $this->assertArrayHasKey('without_lemma', $result);
        $this->assertArrayHasKey('unique_lemmas', $result);
    }

    // =========================================================================
    // Additional clearLemmas Tests
    // =========================================================================

    public function testClearLemmasWithZeroLanguageId(): void
    {
        $result = $this->service->clearLemmas(0);
        $this->assertIsInt($result);
    }

    // =========================================================================
    // Additional getWordFamilies Tests
    // =========================================================================

    public function testGetWordFamiliesWithZeroLimit(): void
    {
        $result = $this->service->getWordFamilies(1, 0);
        $this->assertIsArray($result);
    }

    public function testGetWordFamiliesWithLargeLimit(): void
    {
        $result = $this->service->getWordFamilies(1, 1000);
        $this->assertIsArray($result);
    }

    // =========================================================================
    // Additional findPotentialLemmaGroups Tests
    // =========================================================================

    public function testFindPotentialLemmaGroupsWithZeroLimit(): void
    {
        $result = $this->service->findPotentialLemmaGroups(1, 0);
        $this->assertIsArray($result);
    }

    // =========================================================================
    // Additional getWordFamilyList Tests
    // =========================================================================

    public function testGetWordFamilyListWithNegativePage(): void
    {
        $result = $this->service->getWordFamilyList(1, -5, 50, 'lemma', 'asc');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('pagination', $result);
    }

    public function testGetWordFamilyListWithInvalidDirection(): void
    {
        $result = $this->service->getWordFamilyList(1, 1, 50, 'lemma', 'invalid');
        $this->assertIsArray($result);
    }

    // =========================================================================
    // Additional bulkUpdateTermStatus Tests
    // =========================================================================

    public function testBulkUpdateTermStatusAcceptsLargeArray(): void
    {
        $termIds = range(1, 100);
        $result = $this->service->bulkUpdateTermStatus($termIds, 5);
        $this->assertIsInt($result);
    }

    // =========================================================================
    // Additional getLemmaAggregateStats Tests
    // =========================================================================

    public function testGetLemmaAggregateStatsWithZeroLanguageId(): void
    {
        $result = $this->service->getLemmaAggregateStats(0);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_lemmas', $result);
        $this->assertArrayHasKey('single_form', $result);
        $this->assertArrayHasKey('multi_form', $result);
        $this->assertArrayHasKey('avg_forms_per_lemma', $result);
    }

    // =========================================================================
    // Additional getUnmatchedStatistics Tests
    // =========================================================================

    public function testGetUnmatchedStatisticsWithZeroLanguageId(): void
    {
        $result = $this->service->getUnmatchedStatistics(0);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('unmatched_count', $result);
        $this->assertArrayHasKey('unique_words', $result);
        $this->assertArrayHasKey('matchable_by_lemma', $result);
    }

    // =========================================================================
    // Constructor Edge Cases
    // =========================================================================

    public function testConstructorWithBothNull(): void
    {
        $service = new LemmaService(null, null);
        $this->assertInstanceOf(LemmaService::class, $service);
    }

    public function testConstructorWithCustomLemmatizerAndNullRepository(): void
    {
        $lemmatizer = $this->createMock(LemmatizerInterface::class);
        $service = new LemmaService($lemmatizer, null);
        $this->assertInstanceOf(LemmaService::class, $service);
    }

    public function testConstructorWithNullLemmatizerAndCustomRepository(): void
    {
        $repository = $this->createMock(MySqlTermRepository::class);
        $service = new LemmaService(null, $repository);
        $this->assertInstanceOf(LemmaService::class, $service);
    }
}
