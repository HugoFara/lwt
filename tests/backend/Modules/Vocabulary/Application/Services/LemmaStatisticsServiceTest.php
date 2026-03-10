<?php

declare(strict_types=1);

namespace Tests\Backend\Modules\Vocabulary\Application\Services;

use PHPUnit\Framework\TestCase;
use Lwt\Modules\Vocabulary\Application\Services\LemmaStatisticsService;

/**
 * Unit tests for LemmaStatisticsService.
 *
 * Tests lemma statistics and cleanup operations.
 * Requires database connection since methods use Connection static methods.
 */
class LemmaStatisticsServiceTest extends TestCase
{
    private LemmaStatisticsService $service;

    protected function setUp(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required');
        }
        $this->service = new LemmaStatisticsService();
    }

    // =========================================================================
    // getLemmaStatistics Tests
    // =========================================================================

    public function testGetLemmaStatisticsReturnsExpectedStructure(): void
    {
        $result = $this->service->getLemmaStatistics(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_terms', $result);
        $this->assertArrayHasKey('with_lemma', $result);
        $this->assertArrayHasKey('without_lemma', $result);
        $this->assertArrayHasKey('unique_lemmas', $result);
    }

    public function testGetLemmaStatisticsValuesAreIntegers(): void
    {
        $result = $this->service->getLemmaStatistics(1);

        $this->assertIsInt($result['total_terms']);
        $this->assertIsInt($result['with_lemma']);
        $this->assertIsInt($result['without_lemma']);
        $this->assertIsInt($result['unique_lemmas']);
    }

    public function testGetLemmaStatisticsWithoutLemmaIsComputed(): void
    {
        $result = $this->service->getLemmaStatistics(1);

        $this->assertSame(
            $result['total_terms'] - $result['with_lemma'],
            $result['without_lemma']
        );
    }

    public function testGetLemmaStatisticsWithZeroLanguageId(): void
    {
        $result = $this->service->getLemmaStatistics(0);

        $this->assertIsArray($result);
        $this->assertSame(0, $result['total_terms']);
    }

    public function testGetLemmaStatisticsWithNonExistentLanguage(): void
    {
        $result = $this->service->getLemmaStatistics(999999);

        $this->assertSame(0, $result['total_terms']);
        $this->assertSame(0, $result['with_lemma']);
        $this->assertSame(0, $result['without_lemma']);
        $this->assertSame(0, $result['unique_lemmas']);
    }

    // =========================================================================
    // clearLemmas Tests
    // =========================================================================

    public function testClearLemmasReturnsInt(): void
    {
        $result = $this->service->clearLemmas(999999);

        $this->assertIsInt($result);
    }

    public function testClearLemmasWithZeroLanguageId(): void
    {
        $result = $this->service->clearLemmas(0);

        $this->assertIsInt($result);
        $this->assertSame(0, $result);
    }

    public function testClearLemmasWithNonExistentLanguage(): void
    {
        $result = $this->service->clearLemmas(999999);

        $this->assertSame(0, $result);
    }

    // =========================================================================
    // getUnmatchedStatistics Tests
    // =========================================================================

    public function testGetUnmatchedStatisticsReturnsExpectedStructure(): void
    {
        $result = $this->service->getUnmatchedStatistics(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('unmatched_count', $result);
        $this->assertArrayHasKey('unique_words', $result);
        $this->assertArrayHasKey('matchable_by_lemma', $result);
    }

    public function testGetUnmatchedStatisticsValuesAreIntegers(): void
    {
        $result = $this->service->getUnmatchedStatistics(1);

        $this->assertIsInt($result['unmatched_count']);
        $this->assertIsInt($result['unique_words']);
        $this->assertIsInt($result['matchable_by_lemma']);
    }

    public function testGetUnmatchedStatisticsWithZeroLanguageId(): void
    {
        $result = $this->service->getUnmatchedStatistics(0);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('unmatched_count', $result);
    }

    public function testGetUnmatchedStatisticsWithNonExistentLanguage(): void
    {
        $result = $this->service->getUnmatchedStatistics(999999);

        $this->assertSame(0, $result['unmatched_count']);
        $this->assertSame(0, $result['unique_words']);
        $this->assertSame(0, $result['matchable_by_lemma']);
    }

    public function testGetUnmatchedStatisticsValuesAreNonNegative(): void
    {
        $result = $this->service->getUnmatchedStatistics(1);

        $this->assertGreaterThanOrEqual(0, $result['unmatched_count']);
        $this->assertGreaterThanOrEqual(0, $result['unique_words']);
        $this->assertGreaterThanOrEqual(0, $result['matchable_by_lemma']);
    }

    // =========================================================================
    // getLemmaAggregateStats Tests
    // =========================================================================

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

    public function testGetLemmaAggregateStatsTypesAreCorrect(): void
    {
        $result = $this->service->getLemmaAggregateStats(1);

        $this->assertIsInt($result['total_lemmas']);
        $this->assertIsInt($result['single_form']);
        $this->assertIsInt($result['multi_form']);
        $this->assertTrue(
            is_float($result['avg_forms_per_lemma']) || is_int($result['avg_forms_per_lemma']),
            'avg_forms_per_lemma should be numeric'
        );
        $this->assertIsArray($result['status_distribution']);
    }

    public function testGetLemmaAggregateStatsWithZeroLanguageId(): void
    {
        $result = $this->service->getLemmaAggregateStats(0);

        $this->assertIsArray($result);
        $this->assertSame(0, $result['total_lemmas']);
    }

    public function testGetLemmaAggregateStatsWithNonExistentLanguage(): void
    {
        $result = $this->service->getLemmaAggregateStats(999999);

        $this->assertSame(0, $result['total_lemmas']);
        $this->assertSame(0, $result['single_form']);
        $this->assertSame(0, $result['multi_form']);
    }

    public function testGetLemmaAggregateStatsTotalIsSum(): void
    {
        $result = $this->service->getLemmaAggregateStats(1);

        $this->assertSame(
            $result['total_lemmas'],
            $result['single_form'] + $result['multi_form']
        );
    }

    public function testGetLemmaAggregateStatsAvgZeroWhenNoLemmas(): void
    {
        $result = $this->service->getLemmaAggregateStats(999999);

        $this->assertEqualsWithDelta(0.0, $result['avg_forms_per_lemma'], 0.001);
    }

    public function testGetLemmaAggregateStatsStatusDistributionHasFiveKeys(): void
    {
        $result = $this->service->getLemmaAggregateStats(1);

        $this->assertCount(5, $result['status_distribution']);
        $this->assertArrayHasKey(1, $result['status_distribution']);
        $this->assertArrayHasKey(2, $result['status_distribution']);
        $this->assertArrayHasKey(3, $result['status_distribution']);
        $this->assertArrayHasKey(4, $result['status_distribution']);
        $this->assertArrayHasKey(5, $result['status_distribution']);
    }

    // =========================================================================
    // Constructor Tests
    // =========================================================================

    public function testConstructor(): void
    {
        $service = new LemmaStatisticsService();

        $this->assertInstanceOf(LemmaStatisticsService::class, $service);
    }
}
