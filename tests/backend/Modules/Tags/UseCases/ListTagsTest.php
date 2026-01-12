<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Tags\UseCases;

use Lwt\Modules\Tags\Application\UseCases\ListTags;
use Lwt\Modules\Tags\Domain\Tag;
use Lwt\Modules\Tags\Domain\TagRepositoryInterface;
use Lwt\Modules\Tags\Domain\TagType;
use Lwt\Shared\Infrastructure\Database\Settings;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the ListTags use case.
 */
class ListTagsTest extends TestCase
{
    /** @var TagRepositoryInterface&MockObject */
    private TagRepositoryInterface $repository;
    private ListTags $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(TagRepositoryInterface::class);
        $this->useCase = new ListTags($this->repository);
    }

    private function createTag(int $id, string $text, string $comment = ''): Tag
    {
        return Tag::reconstitute($id, TagType::TERM, $text, $comment);
    }

    // =========================================================================
    // execute() Tests
    // =========================================================================

    public function testExecuteReturnsPaginatedTags(): void
    {
        $tags = [
            $this->createTag(1, 'alpha', 'First tag'),
            $this->createTag(2, 'beta', 'Second tag'),
        ];

        $this->repository->expects($this->once())
            ->method('paginate')
            ->with(1, 50, '', 'text')
            ->willReturn([
                'tags' => $tags,
                'usageCounts' => [1 => 5, 2 => 10],
                'totalCount' => 2,
            ]);

        $result = $this->useCase->execute(1, 50);

        $this->assertCount(2, $result['tags']);
        $this->assertEquals(2, $result['totalCount']);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertArrayHasKey('usageCounts', $result);
    }

    public function testExecuteAppliesQueryFilter(): void
    {
        $this->repository->expects($this->once())
            ->method('paginate')
            ->with(1, 50, 'test*', 'text')
            ->willReturn([
                'tags' => [],
                'usageCounts' => [],
                'totalCount' => 0,
            ]);

        $this->useCase->execute(1, 50, 'test*');
    }

    public function testExecuteAppliesOrderBy(): void
    {
        $this->repository->expects($this->once())
            ->method('paginate')
            ->with(1, 50, '', 'newest')
            ->willReturn([
                'tags' => [],
                'usageCounts' => [],
                'totalCount' => 0,
            ]);

        $this->useCase->execute(1, 50, '', 'newest');
    }

    public function testExecuteIncludesPagination(): void
    {
        $this->repository->method('paginate')
            ->willReturn([
                'tags' => [],
                'usageCounts' => [],
                'totalCount' => 100,
            ]);

        $result = $this->useCase->execute(1, 10);

        $this->assertArrayHasKey('pagination', $result);
        $this->assertEquals(10, $result['pagination']['pages']);
        $this->assertEquals(1, $result['pagination']['currentPage']);
        $this->assertEquals(10, $result['pagination']['perPage']);
    }

    public function testExecuteWithDefaultPage(): void
    {
        $this->repository->expects($this->once())
            ->method('paginate')
            ->with(1, $this->anything(), '', 'text')
            ->willReturn([
                'tags' => [],
                'usageCounts' => [],
                'totalCount' => 0,
            ]);

        $this->useCase->execute();
    }

    // =========================================================================
    // findAll() Tests
    // =========================================================================

    public function testFindAllReturnsTags(): void
    {
        $tags = [
            $this->createTag(1, 'alpha'),
            $this->createTag(2, 'beta'),
            $this->createTag(3, 'gamma'),
        ];

        $this->repository->expects($this->once())
            ->method('findAll')
            ->with('text', 'ASC')
            ->willReturn($tags);

        $result = $this->useCase->findAll();

        $this->assertCount(3, $result);
        $this->assertInstanceOf(Tag::class, $result[0]);
    }

    public function testFindAllWithOrderBy(): void
    {
        $this->repository->expects($this->once())
            ->method('findAll')
            ->with('comment', 'DESC')
            ->willReturn([]);

        $this->useCase->findAll('comment', 'DESC');
    }

    public function testFindAllReturnsEmptyArray(): void
    {
        $this->repository->method('findAll')
            ->willReturn([]);

        $result = $this->useCase->findAll();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // =========================================================================
    // count() Tests
    // =========================================================================

    public function testCountReturnsTotal(): void
    {
        $this->repository->expects($this->once())
            ->method('count')
            ->with('')
            ->willReturn(25);

        $count = $this->useCase->count();

        $this->assertEquals(25, $count);
    }

    public function testCountWithQuery(): void
    {
        $this->repository->expects($this->once())
            ->method('count')
            ->with('verb*')
            ->willReturn(10);

        $count = $this->useCase->count('verb*');

        $this->assertEquals(10, $count);
    }

    public function testCountReturnsZero(): void
    {
        $this->repository->method('count')
            ->willReturn(0);

        $count = $this->useCase->count('nomatch');

        $this->assertEquals(0, $count);
    }

    // =========================================================================
    // getAllTexts() Tests
    // =========================================================================

    public function testGetAllTextsReturnsTagNames(): void
    {
        $texts = ['alpha', 'beta', 'gamma'];

        $this->repository->expects($this->once())
            ->method('getAllTexts')
            ->willReturn($texts);

        $result = $this->useCase->getAllTexts();

        $this->assertEquals($texts, $result);
    }

    public function testGetAllTextsReturnsEmptyArray(): void
    {
        $this->repository->method('getAllTexts')
            ->willReturn([]);

        $result = $this->useCase->getAllTexts();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // =========================================================================
    // getPagination() Tests
    // =========================================================================

    public function testGetPaginationCalculatesCorrectPages(): void
    {
        $pagination = $this->useCase->getPagination(100, 1, 10);

        $this->assertEquals(10, $pagination['pages']);
        $this->assertEquals(1, $pagination['currentPage']);
        $this->assertEquals(10, $pagination['perPage']);
    }

    public function testGetPaginationHandlesPartialPage(): void
    {
        $pagination = $this->useCase->getPagination(25, 1, 10);

        $this->assertEquals(3, $pagination['pages']);
    }

    public function testGetPaginationHandlesZeroItems(): void
    {
        $pagination = $this->useCase->getPagination(0, 1, 10);

        $this->assertEquals(0, $pagination['pages']);
        $this->assertEquals(1, $pagination['currentPage']);
    }

    public function testGetPaginationClampsCurrentPageToMin(): void
    {
        $pagination = $this->useCase->getPagination(100, 0, 10);

        $this->assertEquals(1, $pagination['currentPage']);
    }

    public function testGetPaginationClampsCurrentPageToMax(): void
    {
        $pagination = $this->useCase->getPagination(100, 20, 10);

        $this->assertEquals(10, $pagination['currentPage']);
    }

    public function testGetPaginationHandlesNegativePage(): void
    {
        $pagination = $this->useCase->getPagination(100, -5, 10);

        $this->assertEquals(1, $pagination['currentPage']);
    }

    public function testGetPaginationWithSinglePage(): void
    {
        $pagination = $this->useCase->getPagination(5, 1, 10);

        $this->assertEquals(1, $pagination['pages']);
    }

    public function testGetPaginationWithExactMultiple(): void
    {
        $pagination = $this->useCase->getPagination(50, 3, 10);

        $this->assertEquals(5, $pagination['pages']);
        $this->assertEquals(3, $pagination['currentPage']);
    }

    // =========================================================================
    // getSortOptions() Tests
    // =========================================================================

    public function testGetSortOptionsReturnsAllOptions(): void
    {
        $options = $this->useCase->getSortOptions();

        $this->assertCount(4, $options);
    }

    public function testGetSortOptionsContainsRequiredKeys(): void
    {
        $options = $this->useCase->getSortOptions();

        foreach ($options as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('text', $option);
        }
    }

    public function testGetSortOptionsValues(): void
    {
        $options = $this->useCase->getSortOptions();
        $values = array_column($options, 'value');

        $this->assertEquals([1, 2, 3, 4], $values);
    }

    // =========================================================================
    // getSortColumn() Tests
    // =========================================================================

    public function testGetSortColumnReturnsText(): void
    {
        $column = $this->useCase->getSortColumn(1);

        $this->assertEquals('text', $column);
    }

    public function testGetSortColumnReturnsComment(): void
    {
        $column = $this->useCase->getSortColumn(2);

        $this->assertEquals('comment', $column);
    }

    public function testGetSortColumnReturnsNewest(): void
    {
        $column = $this->useCase->getSortColumn(3);

        $this->assertEquals('newest', $column);
    }

    public function testGetSortColumnReturnsOldest(): void
    {
        $column = $this->useCase->getSortColumn(4);

        $this->assertEquals('oldest', $column);
    }

    public function testGetSortColumnDefaultsToText(): void
    {
        $column = $this->useCase->getSortColumn(0);

        $this->assertEquals('text', $column);
    }

    public function testGetSortColumnDefaultsToTextForInvalidIndex(): void
    {
        $column = $this->useCase->getSortColumn(99);

        $this->assertEquals('text', $column);
    }

    public function testGetSortColumnDefaultsToTextForNegativeIndex(): void
    {
        $column = $this->useCase->getSortColumn(-1);

        $this->assertEquals('text', $column);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testExecuteWithLargePageNumber(): void
    {
        $this->repository->method('paginate')
            ->willReturn([
                'tags' => [],
                'usageCounts' => [],
                'totalCount' => 10,
            ]);

        $result = $this->useCase->execute(999, 10);

        // Should clamp to last page
        $this->assertEquals(1, $result['pagination']['currentPage']);
    }

    public function testExecuteWithUsageCounts(): void
    {
        $tags = [$this->createTag(1, 'popular')];

        $this->repository->method('paginate')
            ->willReturn([
                'tags' => $tags,
                'usageCounts' => [1 => 150],
                'totalCount' => 1,
            ]);

        $result = $this->useCase->execute(1, 10);

        $this->assertEquals(150, $result['usageCounts'][1]);
    }
}
