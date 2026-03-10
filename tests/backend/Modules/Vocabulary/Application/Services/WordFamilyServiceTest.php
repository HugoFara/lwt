<?php

declare(strict_types=1);

namespace Tests\Backend\Modules\Vocabulary\Application\Services;

use PHPUnit\Framework\TestCase;
use Lwt\Modules\Vocabulary\Application\Services\WordFamilyService;
use Lwt\Modules\Vocabulary\Infrastructure\MySqlTermRepository;

/**
 * Unit tests for WordFamilyService.
 *
 * Tests word family queries, details, and status updates.
 */
class WordFamilyServiceTest extends TestCase
{
    private WordFamilyService $service;
    private MySqlTermRepository $mockRepository;

    protected function setUp(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required');
        }
        $this->mockRepository = $this->createMock(MySqlTermRepository::class);
        $this->service = new WordFamilyService($this->mockRepository);
    }

    // =========================================================================
    // getWordFamily Tests
    // =========================================================================

    public function testGetWordFamilyDelegatesToRepository(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('findByLemma')
            ->with(1, 'run')
            ->willReturn([]);

        $result = $this->service->getWordFamily(1, 'run');

        $this->assertSame([], $result);
    }

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
    // updateWordFamilyStatus Tests
    // =========================================================================

    public function testUpdateWordFamilyStatusRejectsInvalidStatus(): void
    {
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

    public function testUpdateWordFamilyStatusRejectsNegativeStatus(): void
    {
        $result = $this->service->updateWordFamilyStatus(1, 'run', -1);

        $this->assertSame(0, $result);
    }

    /**
     * @dataProvider validStatusProvider
     */
    public function testUpdateWordFamilyStatusAcceptsValidStatus(int $status): void
    {
        $result = $this->service->updateWordFamilyStatus(1, 'test', $status);

        $this->assertIsInt($result);
    }

    public function testUpdateWordFamilyStatusWithEmptyLemma(): void
    {
        // Empty lemma should still pass validation and reach DB
        $result = $this->service->updateWordFamilyStatus(1, '', 5);

        $this->assertSame(0, $result);
    }

    // =========================================================================
    // bulkUpdateTermStatus Tests
    // =========================================================================

    public function testBulkUpdateTermStatusRejectsEmptyArray(): void
    {
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
        $result = $this->service->bulkUpdateTermStatus([1], 0);

        $this->assertSame(0, $result);
    }

    public function testBulkUpdateTermStatusRejectsNegativeStatus(): void
    {
        $result = $this->service->bulkUpdateTermStatus([1, 2], -1);

        $this->assertSame(0, $result);
    }

    /**
     * @dataProvider validStatusProvider
     */
    public function testBulkUpdateTermStatusAcceptsValidStatus(int $status): void
    {
        $result = $this->service->bulkUpdateTermStatus([1], $status);

        $this->assertIsInt($result);
    }

    // =========================================================================
    // getWordFamilyByLemma Tests
    // =========================================================================

    public function testGetWordFamilyByLemmaReturnsNullForEmptyLemma(): void
    {
        $result = $this->service->getWordFamilyByLemma(1, '');

        $this->assertNull($result);
    }

    public function testGetWordFamilyByLemmaReturnsNullForNonExistent(): void
    {
        $result = $this->service->getWordFamilyByLemma(999999, 'nonexistent_lemma');

        $this->assertNull($result);
    }

    // =========================================================================
    // getWordFamilyList Tests
    // =========================================================================

    public function testGetWordFamilyListClampsPaginationMin(): void
    {
        $result = $this->service->getWordFamilyList(1, 0, 50, 'lemma', 'asc');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('families', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertSame(1, $result['pagination']['page']);
    }

    public function testGetWordFamilyListClampsPerPageMin(): void
    {
        $result = $this->service->getWordFamilyList(1, 1, 0, 'lemma', 'asc');

        $this->assertIsArray($result);
        $this->assertSame(1, $result['pagination']['perPage']);
    }

    public function testGetWordFamilyListClampsPerPageMax(): void
    {
        $result = $this->service->getWordFamilyList(1, 1, 200, 'lemma', 'asc');

        $this->assertIsArray($result);
        $this->assertSame(100, $result['pagination']['perPage']);
    }

    public function testGetWordFamilyListSortByCount(): void
    {
        $result = $this->service->getWordFamilyList(1, 1, 50, 'count', 'desc');

        $this->assertIsArray($result);
    }

    public function testGetWordFamilyListSortByStatus(): void
    {
        $result = $this->service->getWordFamilyList(1, 1, 50, 'status', 'asc');

        $this->assertIsArray($result);
    }

    public function testGetWordFamilyListSortByLemma(): void
    {
        $result = $this->service->getWordFamilyList(1, 1, 50, 'lemma', 'desc');

        $this->assertIsArray($result);
    }

    public function testGetWordFamilyListInvalidSortFallsBackToLemma(): void
    {
        $result = $this->service->getWordFamilyList(1, 1, 50, 'invalid', 'asc');

        $this->assertIsArray($result);
    }

    public function testGetWordFamilyListNegativePageClamped(): void
    {
        $result = $this->service->getWordFamilyList(1, -5, 50, 'lemma', 'asc');

        $this->assertSame(1, $result['pagination']['page']);
    }

    // =========================================================================
    // getWordFamilyDetails Tests
    // =========================================================================

    public function testGetWordFamilyDetailsReturnsNullForNonExistent(): void
    {
        $result = $this->service->getWordFamilyDetails(999999999);

        $this->assertNull($result);
    }

    // =========================================================================
    // getSuggestedFamilyUpdate Tests
    // =========================================================================

    public function testGetSuggestedFamilyUpdateStructure(): void
    {
        $result = $this->service->getSuggestedFamilyUpdate(999999, 5);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('suggestion', $result);
        $this->assertArrayHasKey('affected_count', $result);
        $this->assertArrayHasKey('term_ids', $result);
    }

    public function testGetSuggestedFamilyUpdateNonExistentTerm(): void
    {
        $result = $this->service->getSuggestedFamilyUpdate(999999999, 99);

        $this->assertSame('none', $result['suggestion']);
        $this->assertSame(0, $result['affected_count']);
        $this->assertSame([], $result['term_ids']);
    }

    // =========================================================================
    // getWordFamilies Tests
    // =========================================================================

    public function testGetWordFamiliesReturnsArray(): void
    {
        $result = $this->service->getWordFamilies(1, 50);

        $this->assertIsArray($result);
    }

    public function testGetWordFamiliesWithSmallLimit(): void
    {
        $result = $this->service->getWordFamilies(1, 5);

        $this->assertIsArray($result);
    }

    // =========================================================================
    // findPotentialLemmaGroups Tests
    // =========================================================================

    public function testFindPotentialLemmaGroupsReturnsArray(): void
    {
        $result = $this->service->findPotentialLemmaGroups(1, 20);

        $this->assertIsArray($result);
    }

    public function testFindPotentialLemmaGroupsWithLimit(): void
    {
        $result = $this->service->findPotentialLemmaGroups(1, 5);

        $this->assertIsArray($result);
    }

    // =========================================================================
    // getLemmaAggregateStats (via getWordFamilyDetails stats)
    // =========================================================================

    // =========================================================================
    // Constructor Tests
    // =========================================================================

    public function testConstructorAcceptsRepository(): void
    {
        $repository = $this->createMock(MySqlTermRepository::class);
        $service = new WordFamilyService($repository);

        $this->assertInstanceOf(WordFamilyService::class, $service);
    }

    // =========================================================================
    // Data Providers
    // =========================================================================

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
}
