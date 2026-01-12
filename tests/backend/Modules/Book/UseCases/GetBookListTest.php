<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Book\UseCases;

use Lwt\Modules\Book\Application\UseCases\GetBookList;
use Lwt\Modules\Book\Domain\Book;
use Lwt\Modules\Book\Domain\BookRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the GetBookList use case.
 */
class GetBookListTest extends TestCase
{
    /** @var BookRepositoryInterface&MockObject */
    private BookRepositoryInterface $repository;
    private GetBookList $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(BookRepositoryInterface::class);
        $this->useCase = new GetBookList($this->repository);
    }

    private function createBook(int $id, string $title, int $languageId = 1): Book
    {
        return Book::reconstitute(
            id: $id,
            userId: null,
            languageId: $languageId,
            title: $title,
            author: null,
            description: null,
            coverPath: null,
            sourceType: 'text',
            sourceHash: null,
            totalChapters: 5,
            currentChapter: 2,
            createdAt: '2024-01-01 00:00:00',
            updatedAt: null
        );
    }

    // =========================================================================
    // execute() Tests
    // =========================================================================

    public function testExecuteReturnsPaginatedBooks(): void
    {
        $books = [
            $this->createBook(1, 'Book 1'),
            $this->createBook(2, 'Book 2'),
        ];

        $this->repository->expects($this->once())
            ->method('findAll')
            ->with(null, null, 20, 0)
            ->willReturn($books);

        $this->repository->expects($this->once())
            ->method('count')
            ->with(null, null)
            ->willReturn(2);

        $result = $this->useCase->execute();

        $this->assertCount(2, $result['books']);
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(20, $result['perPage']);
        $this->assertEquals(1, $result['totalPages']);
    }

    public function testExecuteWithPagination(): void
    {
        $books = [$this->createBook(11, 'Book 11')];

        $this->repository->expects($this->once())
            ->method('findAll')
            ->with(null, null, 10, 10)  // Page 2, 10 per page = offset 10
            ->willReturn($books);

        $this->repository->method('count')
            ->willReturn(25);

        $result = $this->useCase->execute(null, null, 2, 10);

        $this->assertEquals(2, $result['page']);
        $this->assertEquals(10, $result['perPage']);
        $this->assertEquals(3, $result['totalPages']);  // 25 / 10 = 3 pages
    }

    public function testExecuteFiltersByUser(): void
    {
        $this->repository->expects($this->once())
            ->method('findAll')
            ->with(5, null, 20, 0)
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('count')
            ->with(5, null)
            ->willReturn(0);

        $this->useCase->execute(5);
    }

    public function testExecuteFiltersByLanguage(): void
    {
        $this->repository->expects($this->once())
            ->method('findAll')
            ->with(null, 3, 20, 0)
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('count')
            ->with(null, 3)
            ->willReturn(0);

        $this->useCase->execute(null, 3);
    }

    public function testExecuteFiltersByBoth(): void
    {
        $this->repository->expects($this->once())
            ->method('findAll')
            ->with(5, 3, 20, 0)
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('count')
            ->with(5, 3)
            ->willReturn(0);

        $this->useCase->execute(5, 3);
    }

    public function testExecuteClampsPageToMinimum(): void
    {
        $this->repository->expects($this->once())
            ->method('findAll')
            ->with(null, null, 20, 0)  // Should clamp to page 1 -> offset 0
            ->willReturn([]);

        $this->repository->method('count')
            ->willReturn(0);

        $result = $this->useCase->execute(null, null, -5);

        $this->assertEquals(1, $result['page']);
    }

    public function testExecuteClampsPerPageToMinimum(): void
    {
        $this->repository->expects($this->once())
            ->method('findAll')
            ->with(null, null, 1, 0)  // Should clamp to min 1
            ->willReturn([]);

        $this->repository->method('count')
            ->willReturn(0);

        $result = $this->useCase->execute(null, null, 1, 0);

        $this->assertEquals(1, $result['perPage']);
    }

    public function testExecuteClampsPerPageToMaximum(): void
    {
        $this->repository->expects($this->once())
            ->method('findAll')
            ->with(null, null, 100, 0)  // Should clamp to max 100
            ->willReturn([]);

        $this->repository->method('count')
            ->willReturn(0);

        $result = $this->useCase->execute(null, null, 1, 200);

        $this->assertEquals(100, $result['perPage']);
    }

    public function testExecuteReturnsEmptyArrayWhenNoBooks(): void
    {
        $this->repository->method('findAll')
            ->willReturn([]);

        $this->repository->method('count')
            ->willReturn(0);

        $result = $this->useCase->execute();

        $this->assertIsArray($result['books']);
        $this->assertEmpty($result['books']);
        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['totalPages']);
    }

    public function testExecuteFormatsBookDataCorrectly(): void
    {
        $book = $this->createBook(1, 'Test Book', 2);

        $this->repository->method('findAll')
            ->willReturn([$book]);

        $this->repository->method('count')
            ->willReturn(1);

        $result = $this->useCase->execute();

        $bookData = $result['books'][0];

        $this->assertEquals(1, $bookData['id']);
        $this->assertEquals('Test Book', $bookData['title']);
        $this->assertEquals(2, $bookData['languageId']);
        $this->assertEquals('text', $bookData['sourceType']);
        $this->assertEquals(5, $bookData['totalChapters']);
        $this->assertEquals(2, $bookData['currentChapter']);
        $this->assertEquals(40.0, $bookData['progress']);  // 2/5 = 40%
        $this->assertArrayHasKey('createdAt', $bookData);
        $this->assertArrayHasKey('updatedAt', $bookData);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testExecuteCalculatesTotalPagesCorrectly(): void
    {
        $this->repository->method('findAll')
            ->willReturn([]);

        $this->repository->method('count')
            ->willReturn(45);

        $result = $this->useCase->execute(null, null, 1, 10);

        $this->assertEquals(5, $result['totalPages']);  // ceil(45/10) = 5
    }

    public function testExecuteCalculatesSinglePageCorrectly(): void
    {
        $this->repository->method('findAll')
            ->willReturn([]);

        $this->repository->method('count')
            ->willReturn(10);

        $result = $this->useCase->execute(null, null, 1, 10);

        $this->assertEquals(1, $result['totalPages']);
    }

    public function testExecuteHandlesLargePageNumber(): void
    {
        $this->repository->method('findAll')
            ->willReturn([]);

        $this->repository->method('count')
            ->willReturn(10);

        $result = $this->useCase->execute(null, null, 999);

        // Should still return valid pagination info
        $this->assertEquals(999, $result['page']);
        $this->assertEquals(1, $result['totalPages']);
    }
}
