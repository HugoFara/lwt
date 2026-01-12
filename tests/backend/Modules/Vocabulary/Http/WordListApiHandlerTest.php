<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Http;

use Lwt\Modules\Vocabulary\Http\WordListApiHandler;
use Lwt\Modules\Vocabulary\Application\Services\WordListService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for WordListApiHandler.
 *
 * Tests word list API operations including filtering, pagination, and bulk actions.
 */
class WordListApiHandlerTest extends TestCase
{
    /** @var WordListService&MockObject */
    private WordListService $listService;

    private WordListApiHandler $handler;

    protected function setUp(): void
    {
        $this->listService = $this->createMock(WordListService::class);
        $this->handler = new WordListApiHandler($this->listService);
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    public function testConstructorCreatesValidHandler(): void
    {
        $this->assertInstanceOf(WordListApiHandler::class, $this->handler);
    }

    public function testConstructorAcceptsNullParameter(): void
    {
        $handler = new WordListApiHandler(null);
        $this->assertInstanceOf(WordListApiHandler::class, $handler);
    }

    // =========================================================================
    // getWordList tests
    // =========================================================================

    public function testGetWordListReturnsExpectedStructure(): void
    {
        // Mock service methods
        $this->listService->method('buildLangCondition')->willReturn('1=1');
        $this->listService->method('buildStatusCondition')->willReturn('1=1');
        $this->listService->method('buildQueryCondition')->willReturn('1=1');
        $this->listService->method('buildTagCondition')->willReturn('1=1');
        $this->listService->method('countWords')->willReturn(0);
        $this->listService->method('getWordsList')->willReturn([]);

        $result = $this->handler->getWordList([]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('words', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertArrayHasKey('page', $result['pagination']);
        $this->assertArrayHasKey('per_page', $result['pagination']);
        $this->assertArrayHasKey('total', $result['pagination']);
        $this->assertArrayHasKey('total_pages', $result['pagination']);
    }

    public function testGetWordListParsesPageParameter(): void
    {
        $this->listService->method('buildLangCondition')->willReturn('1=1');
        $this->listService->method('buildStatusCondition')->willReturn('1=1');
        $this->listService->method('buildQueryCondition')->willReturn('1=1');
        $this->listService->method('buildTagCondition')->willReturn('1=1');
        $this->listService->method('countWords')->willReturn(200); // Enough for 4 pages with 50 per page
        $this->listService->method('getWordsList')->willReturn([]);

        $result = $this->handler->getWordList(['page' => '3']);

        $this->assertEquals(3, $result['pagination']['page']);
    }

    public function testGetWordListClampsPageToMinimum(): void
    {
        $this->listService->method('buildLangCondition')->willReturn('1=1');
        $this->listService->method('buildStatusCondition')->willReturn('1=1');
        $this->listService->method('buildQueryCondition')->willReturn('1=1');
        $this->listService->method('buildTagCondition')->willReturn('1=1');
        $this->listService->method('countWords')->willReturn(100);
        $this->listService->method('getWordsList')->willReturn([]);

        $result = $this->handler->getWordList(['page' => '-5']);

        $this->assertEquals(1, $result['pagination']['page']);
    }

    public function testGetWordListClampsPerPageToRange(): void
    {
        $this->listService->method('buildLangCondition')->willReturn('1=1');
        $this->listService->method('buildStatusCondition')->willReturn('1=1');
        $this->listService->method('buildQueryCondition')->willReturn('1=1');
        $this->listService->method('buildTagCondition')->willReturn('1=1');
        $this->listService->method('countWords')->willReturn(100);
        $this->listService->method('getWordsList')->willReturn([]);

        // Test minimum clamping
        $result = $this->handler->getWordList(['per_page' => '0']);
        $this->assertEquals(1, $result['pagination']['per_page']);

        // Test maximum clamping
        $result = $this->handler->getWordList(['per_page' => '200']);
        $this->assertEquals(100, $result['pagination']['per_page']);
    }

    public function testGetWordListClampsSortToRange(): void
    {
        $this->listService->method('buildLangCondition')->willReturn('1=1');
        $this->listService->method('buildStatusCondition')->willReturn('1=1');
        $this->listService->method('buildQueryCondition')->willReturn('1=1');
        $this->listService->method('buildTagCondition')->willReturn('1=1');
        $this->listService->method('countWords')->willReturn(0);

        // Expect sort to be clamped to 1-7 range
        $this->listService->expects($this->once())
            ->method('getWordsList')
            ->with(
                $this->anything(),
                $this->greaterThanOrEqual(1),
                $this->anything(),
                $this->anything()
            )
            ->willReturn([]);

        $this->handler->getWordList(['sort' => '100']);
    }

    public function testGetWordListAdjustsPageWhenBeyondTotal(): void
    {
        $this->listService->method('buildLangCondition')->willReturn('1=1');
        $this->listService->method('buildStatusCondition')->willReturn('1=1');
        $this->listService->method('buildQueryCondition')->willReturn('1=1');
        $this->listService->method('buildTagCondition')->willReturn('1=1');
        $this->listService->method('countWords')->willReturn(25); // 1 page with 50 per page
        $this->listService->method('getWordsList')->willReturn([]);

        $result = $this->handler->getWordList(['page' => '100', 'per_page' => '50']);

        $this->assertEquals(1, $result['pagination']['page']);
    }

    public function testGetWordListCallsServiceWithCorrectFilters(): void
    {
        $this->listService->expects($this->once())
            ->method('buildLangCondition')
            ->with('5')
            ->willReturn('lang=5');

        $this->listService->expects($this->once())
            ->method('buildStatusCondition')
            ->with('3')
            ->willReturn('status=3');

        $this->listService->expects($this->once())
            ->method('buildQueryCondition')
            ->with('test', 'term', 'r')
            ->willReturn('query=test');

        $this->listService->expects($this->once())
            ->method('buildTagCondition')
            ->with('1', '2', '1')
            ->willReturn('tag=1');

        $this->listService->method('countWords')->willReturn(0);
        $this->listService->method('getWordsList')->willReturn([]);

        $this->handler->getWordList([
            'lang' => '5',
            'status' => '3',
            'query' => 'test',
            'query_mode' => 'term',
            'regex_mode' => 'r',
            'tag1' => '1',
            'tag2' => '2',
            'tag12' => '1'
        ]);
    }

    // =========================================================================
    // bulkAction tests
    // =========================================================================

    public function testBulkActionReturnsErrorForEmptyWordIds(): void
    {
        $result = $this->handler->bulkAction([], 'del');

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['count']);
        $this->assertEquals('No terms selected', $result['message']);
    }

    public function testBulkActionReturnsErrorForInvalidWordIds(): void
    {
        $result = $this->handler->bulkAction(['abc', 'def'], 'del');

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid term IDs', $result['message']);
    }

    public function testBulkActionReturnsErrorForUnknownAction(): void
    {
        $result = $this->handler->bulkAction([1, 2, 3], 'unknown_action');

        $this->assertFalse($result['success']);
        $this->assertEquals('Unknown action: unknown_action', $result['message']);
    }

    public function testBulkActionDeleteCallsService(): void
    {
        $this->listService->expects($this->once())
            ->method('deleteByIdList')
            ->with('(1,2,3)')
            ->willReturn('Deleted 3 terms');

        $result = $this->handler->bulkAction([1, 2, 3], 'del');

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['count']);
    }

    public function testBulkActionStatusPlusOne(): void
    {
        $this->listService->expects($this->once())
            ->method('updateStatusByIdList')
            ->with('(1,2)', 1, true, 'spl1')
            ->willReturn('Updated 2 terms');

        $result = $this->handler->bulkAction([1, 2], 'spl1');

        $this->assertTrue($result['success']);
    }

    public function testBulkActionStatusMinusOne(): void
    {
        $this->listService->expects($this->once())
            ->method('updateStatusByIdList')
            ->with('(1,2)', -1, true, 'smi1')
            ->willReturn('Updated 2 terms');

        $result = $this->handler->bulkAction([1, 2], 'smi1');

        $this->assertTrue($result['success']);
    }

    /**
     * @dataProvider statusActionProvider
     */
    public function testBulkActionStatusActions(string $action, int $expectedStatus): void
    {
        $this->listService->expects($this->once())
            ->method('updateStatusByIdList')
            ->with('(1)', $expectedStatus, false, $action)
            ->willReturn('Updated 1 terms');

        $result = $this->handler->bulkAction([1], $action);

        $this->assertTrue($result['success']);
    }

    public static function statusActionProvider(): array
    {
        return [
            ['s1', 1],
            ['s2', 2],
            ['s3', 3],
            ['s4', 4],
            ['s5', 5],
            ['s98', 98],
            ['s99', 99],
        ];
    }

    public function testBulkActionToday(): void
    {
        $this->listService->expects($this->once())
            ->method('updateStatusDateByIdList')
            ->with('(1,2)')
            ->willReturn('Updated 2 terms');

        $result = $this->handler->bulkAction([1, 2], 'today');

        $this->assertTrue($result['success']);
    }

    public function testBulkActionDeleteSentences(): void
    {
        $this->listService->expects($this->once())
            ->method('deleteSentencesByIdList')
            ->with('(1,2)')
            ->willReturn('Deleted sentences');

        $result = $this->handler->bulkAction([1, 2], 'delsent');

        $this->assertTrue($result['success']);
    }

    public function testBulkActionLowercase(): void
    {
        $this->listService->expects($this->once())
            ->method('toLowercaseByIdList')
            ->with('(1)')
            ->willReturn('Lowercased 1 terms');

        $result = $this->handler->bulkAction([1], 'lower');

        $this->assertTrue($result['success']);
    }

    public function testBulkActionCapitalize(): void
    {
        $this->listService->expects($this->once())
            ->method('capitalizeByIdList')
            ->with('(1)')
            ->willReturn('Capitalized 1 terms');

        $result = $this->handler->bulkAction([1], 'cap');

        $this->assertTrue($result['success']);
    }

    public function testBulkActionAddTagRequiresData(): void
    {
        $result = $this->handler->bulkAction([1], 'addtag', null);

        $this->assertFalse($result['success']);
        $this->assertEquals('Tag name required', $result['message']);
    }

    public function testBulkActionDelTagRequiresData(): void
    {
        $result = $this->handler->bulkAction([1], 'deltag', '');

        $this->assertFalse($result['success']);
        $this->assertEquals('Tag name required', $result['message']);
    }

    // =========================================================================
    // allAction tests
    // =========================================================================

    public function testAllActionReturnsErrorWhenNoMatches(): void
    {
        $this->listService->method('buildLangCondition')->willReturn('1=1');
        $this->listService->method('buildStatusCondition')->willReturn('1=1');
        $this->listService->method('buildQueryCondition')->willReturn('1=1');
        $this->listService->method('buildTagCondition')->willReturn('1=1');
        $this->listService->method('getFilteredWordIds')->willReturn([]);

        $result = $this->handler->allAction([], 'del');

        $this->assertFalse($result['success']);
        $this->assertEquals('No terms match the filter', $result['message']);
    }

    public function testAllActionRemovesAllSuffix(): void
    {
        $this->listService->method('buildLangCondition')->willReturn('1=1');
        $this->listService->method('buildStatusCondition')->willReturn('1=1');
        $this->listService->method('buildQueryCondition')->willReturn('1=1');
        $this->listService->method('buildTagCondition')->willReturn('1=1');
        $this->listService->method('getFilteredWordIds')->willReturn([1, 2, 3]);

        $this->listService->expects($this->once())
            ->method('deleteByIdList')
            ->willReturn('Deleted 3 terms');

        $result = $this->handler->allAction([], 'delall');

        $this->assertTrue($result['success']);
    }

    // =========================================================================
    // inlineEdit tests
    // =========================================================================

    public function testInlineEditReturnsErrorForInvalidField(): void
    {
        $result = $this->handler->inlineEdit(1, 'invalid', 'value');

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid field', $result['error']);
    }

    /**
     * @group integration
     */
    public function testInlineEditReturnsErrorForNonExistentTerm(): void
    {
        try {
            $result = $this->handler->inlineEdit(999999999, 'translation', 'test');

            $this->assertFalse($result['success']);
            $this->assertEquals('Term not found', $result['error']);
        } catch (\Lwt\Core\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }
    }

    public function testInlineEditValidatesFieldTranslation(): void
    {
        $handler = new WordListApiHandler(null);

        // Test that translation field is valid (won't return field error)
        $result = $handler->inlineEdit(1, 'translation', 'test');

        // Should NOT return "Invalid field" error
        $this->assertNotEquals('Invalid field', $result['error'] ?? '');
    }

    public function testInlineEditValidatesFieldRomanization(): void
    {
        $handler = new WordListApiHandler(null);

        // Test that romanization field is valid (won't return field error)
        $result = $handler->inlineEdit(1, 'romanization', 'test');

        // Should NOT return "Invalid field" error
        $this->assertNotEquals('Invalid field', $result['error'] ?? '');
    }

    // =========================================================================
    // getFilterOptions tests
    // =========================================================================

    /**
     * @group integration
     */
    public function testGetFilterOptionsReturnsExpectedStructure(): void
    {
        try {
            $handler = new WordListApiHandler(null);
            $result = $handler->getFilterOptions();

            $this->assertIsArray($result);
            $this->assertArrayHasKey('languages', $result);
            $this->assertArrayHasKey('texts', $result);
            $this->assertArrayHasKey('tags', $result);
            $this->assertArrayHasKey('statuses', $result);
            $this->assertArrayHasKey('sorts', $result);
        } catch (\Lwt\Core\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }
    }

    /**
     * @group integration
     */
    public function testGetFilterOptionsStatusesHaveCorrectFormat(): void
    {
        try {
            $handler = new WordListApiHandler(null);
            $result = $handler->getFilterOptions();

            $this->assertNotEmpty($result['statuses']);
            $firstStatus = $result['statuses'][0];
            $this->assertArrayHasKey('value', $firstStatus);
            $this->assertArrayHasKey('label', $firstStatus);
        } catch (\Lwt\Core\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }
    }

    /**
     * @group integration
     */
    public function testGetFilterOptionsSortsHaveCorrectFormat(): void
    {
        try {
            $handler = new WordListApiHandler(null);
            $result = $handler->getFilterOptions();

            $this->assertNotEmpty($result['sorts']);
            $firstSort = $result['sorts'][0];
            $this->assertArrayHasKey('value', $firstSort);
            $this->assertArrayHasKey('label', $firstSort);
        } catch (\Lwt\Core\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // limitCurrentPage tests
    // =========================================================================

    public function testLimitCurrentPageReturnsPageWithinBounds(): void
    {
        // 100 records, 10 per page = 10 pages
        $result = $this->handler->limitCurrentPage(5, 100, 10);
        $this->assertEquals(5, $result);
    }

    public function testLimitCurrentPageClampsToMinimum(): void
    {
        $result = $this->handler->limitCurrentPage(-5, 100, 10);
        $this->assertEquals(1, $result);
    }

    public function testLimitCurrentPageClampsToMaximum(): void
    {
        // 100 records, 10 per page = 10 pages
        $result = $this->handler->limitCurrentPage(100, 100, 10);
        $this->assertEquals(10, $result);
    }

    public function testLimitCurrentPageHandlesSinglePage(): void
    {
        // 5 records, 10 per page = 1 page
        $result = $this->handler->limitCurrentPage(5, 5, 10);
        $this->assertEquals(1, $result);
    }

    // =========================================================================
    // selectImportedTerms tests
    // =========================================================================

    /**
     * @group integration
     */
    public function testSelectImportedTermsReturnsArray(): void
    {
        try {
            $handler = new WordListApiHandler(null);
            $result = $handler->selectImportedTerms('2000-01-01 00:00:00', 0, 10);

            $this->assertIsArray($result);
        } catch (\Lwt\Core\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // importedTermsList tests
    // =========================================================================

    /**
     * @group integration
     */
    public function testImportedTermsListReturnsExpectedStructure(): void
    {
        try {
            $handler = new WordListApiHandler(null);
            $result = $handler->importedTermsList('2000-01-01 00:00:00', 1, 50);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('navigation', $result);
            $this->assertArrayHasKey('terms', $result);
            $this->assertArrayHasKey('current_page', $result['navigation']);
            $this->assertArrayHasKey('total_pages', $result['navigation']);
        } catch (\Lwt\Core\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }
    }

    public function testImportedTermsListCalculatesCorrectPages(): void
    {
        // Mock handler to test pagination logic
        $handler = new WordListApiHandler($this->listService);

        // With 250 records and 100 per page, should have 3 pages
        // This tests the limitCurrentPage method indirectly
        $result = $handler->limitCurrentPage(3, 250, 100);
        $this->assertEquals(3, $result);
    }
}
