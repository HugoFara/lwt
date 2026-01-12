<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Book\UseCases;

use Lwt\Modules\Book\Application\UseCases\GetBookById;
use Lwt\Modules\Book\Domain\Book;
use Lwt\Modules\Book\Domain\BookRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the GetBookById use case.
 */
class GetBookByIdTest extends TestCase
{
    /** @var BookRepositoryInterface&MockObject */
    private BookRepositoryInterface $repository;
    private GetBookById $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(BookRepositoryInterface::class);
        $this->useCase = new GetBookById($this->repository);
    }

    private function createBook(
        int $id,
        string $title,
        int $languageId = 1,
        ?string $author = null,
        int $totalChapters = 0,
        int $currentChapter = 0
    ): Book {
        return Book::reconstitute(
            id: $id,
            userId: null,
            languageId: $languageId,
            title: $title,
            author: $author,
            description: null,
            coverPath: null,
            sourceType: 'text',
            sourceHash: null,
            totalChapters: $totalChapters,
            currentChapter: $currentChapter,
            createdAt: '2024-01-01 00:00:00',
            updatedAt: null
        );
    }

    // =========================================================================
    // execute() Tests
    // =========================================================================

    public function testExecuteReturnsBookWhenFound(): void
    {
        $book = $this->createBook(1, 'Test Book', 1, 'Test Author', 10, 3);

        $this->repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($book);

        $this->repository->expects($this->once())
            ->method('getChapters')
            ->with(1)
            ->willReturn([
                ['id' => 101, 'num' => 1, 'title' => 'Chapter 1'],
                ['id' => 102, 'num' => 2, 'title' => 'Chapter 2'],
            ]);

        $result = $this->useCase->execute(1);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('book', $result);
        $this->assertArrayHasKey('chapters', $result);

        $this->assertEquals(1, $result['book']['id']);
        $this->assertEquals('Test Book', $result['book']['title']);
        $this->assertEquals('Test Author', $result['book']['author']);
        $this->assertEquals(10, $result['book']['totalChapters']);
        $this->assertEquals(3, $result['book']['currentChapter']);
        $this->assertCount(2, $result['chapters']);
    }

    public function testExecuteReturnsNullWhenNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $result = $this->useCase->execute(999);

        $this->assertNull($result);
    }

    public function testExecuteCalculatesProgress(): void
    {
        $book = $this->createBook(1, 'Book', 1, null, 10, 5);

        $this->repository->method('findById')
            ->willReturn($book);

        $this->repository->method('getChapters')
            ->willReturn([]);

        $result = $this->useCase->execute(1);

        $this->assertEquals(50.0, $result['book']['progress']);
    }

    public function testExecuteReturnsZeroProgressForNoChapters(): void
    {
        $book = $this->createBook(1, 'Empty Book', 1, null, 0, 0);

        $this->repository->method('findById')
            ->willReturn($book);

        $this->repository->method('getChapters')
            ->willReturn([]);

        $result = $this->useCase->execute(1);

        $this->assertEquals(0.0, $result['book']['progress']);
    }

    public function testExecuteIncludesSourceType(): void
    {
        $book = $this->createBook(1, 'Book', 1);

        $this->repository->method('findById')
            ->willReturn($book);

        $this->repository->method('getChapters')
            ->willReturn([]);

        $result = $this->useCase->execute(1);

        $this->assertEquals('text', $result['book']['sourceType']);
    }

    public function testExecuteIncludesTimestamps(): void
    {
        $book = $this->createBook(1, 'Book', 1);

        $this->repository->method('findById')
            ->willReturn($book);

        $this->repository->method('getChapters')
            ->willReturn([]);

        $result = $this->useCase->execute(1);

        $this->assertArrayHasKey('createdAt', $result['book']);
        $this->assertArrayHasKey('updatedAt', $result['book']);
    }

    // =========================================================================
    // getBookContextForText() Tests
    // =========================================================================

    public function testGetBookContextForTextReturnsContextWhenFound(): void
    {
        $context = [
            'bookId' => 1,
            'bookTitle' => 'My Book',
            'chapterNum' => 3,
            'chapterTitle' => 'Chapter 3',
            'totalChapters' => 10,
            'prevTextId' => 102,
            'nextTextId' => 104,
        ];

        $this->repository->expects($this->once())
            ->method('getBookContextForText')
            ->with(103)
            ->willReturn($context);

        $this->repository->expects($this->once())
            ->method('getChapters')
            ->with(1)
            ->willReturn([
                ['id' => 101, 'num' => 1, 'title' => 'Chapter 1'],
                ['id' => 102, 'num' => 2, 'title' => 'Chapter 2'],
                ['id' => 103, 'num' => 3, 'title' => 'Chapter 3'],
            ]);

        $result = $this->useCase->getBookContextForText(103);

        $this->assertNotNull($result);
        $this->assertEquals(1, $result['bookId']);
        $this->assertEquals('My Book', $result['bookTitle']);
        $this->assertEquals(3, $result['chapterNum']);
        $this->assertEquals(102, $result['prevTextId']);
        $this->assertEquals(104, $result['nextTextId']);
        $this->assertArrayHasKey('chapters', $result);
        $this->assertCount(3, $result['chapters']);
    }

    public function testGetBookContextForTextReturnsNullWhenTextNotInBook(): void
    {
        $this->repository->expects($this->once())
            ->method('getBookContextForText')
            ->with(999)
            ->willReturn(null);

        $result = $this->useCase->getBookContextForText(999);

        $this->assertNull($result);
    }

    public function testGetBookContextForTextIncludesChapterNavigation(): void
    {
        $context = [
            'bookId' => 1,
            'bookTitle' => 'Book',
            'chapterNum' => 1,
            'chapterTitle' => 'First',
            'totalChapters' => 3,
            'prevTextId' => null,
            'nextTextId' => 102,
        ];

        $this->repository->method('getBookContextForText')
            ->willReturn($context);

        $this->repository->method('getChapters')
            ->willReturn([]);

        $result = $this->useCase->getBookContextForText(101);

        $this->assertNull($result['prevTextId']);
        $this->assertEquals(102, $result['nextTextId']);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testExecuteWithEmptyChapters(): void
    {
        $book = $this->createBook(1, 'Book', 1);

        $this->repository->method('findById')
            ->willReturn($book);

        $this->repository->method('getChapters')
            ->willReturn([]);

        $result = $this->useCase->execute(1);

        $this->assertIsArray($result['chapters']);
        $this->assertEmpty($result['chapters']);
    }

    public function testExecuteWithNullAuthor(): void
    {
        $book = $this->createBook(1, 'Book', 1, null);

        $this->repository->method('findById')
            ->willReturn($book);

        $this->repository->method('getChapters')
            ->willReturn([]);

        $result = $this->useCase->execute(1);

        $this->assertNull($result['book']['author']);
    }
}
