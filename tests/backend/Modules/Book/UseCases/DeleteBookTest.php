<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Book\UseCases;

use Lwt\Modules\Book\Application\UseCases\DeleteBook;
use Lwt\Modules\Book\Domain\Book;
use Lwt\Modules\Book\Domain\BookRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the DeleteBook use case.
 */
class DeleteBookTest extends TestCase
{
    /** @var BookRepositoryInterface&MockObject */
    private BookRepositoryInterface $repository;
    private DeleteBook $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(BookRepositoryInterface::class);
        $this->useCase = new DeleteBook($this->repository);
    }

    private function createBook(int $id, string $title, int $totalChapters = 5): Book
    {
        return Book::reconstitute(
            id: $id,
            userId: null,
            languageId: 1,
            title: $title,
            author: null,
            description: null,
            coverPath: null,
            sourceType: 'text',
            sourceHash: null,
            totalChapters: $totalChapters,
            currentChapter: 0,
            createdAt: '2024-01-01 00:00:00',
            updatedAt: null
        );
    }

    // =========================================================================
    // execute() Tests
    // =========================================================================

    public function testExecuteDeletesBookSuccessfully(): void
    {
        $book = $this->createBook(1, 'Test Book', 10);

        $this->repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($book);

        $this->repository->expects($this->once())
            ->method('delete')
            ->with(1)
            ->willReturn(true);

        $result = $this->useCase->execute(1);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Test Book', $result['message']);
        $this->assertStringContainsString('10 chapters', $result['message']);
    }

    public function testExecuteReturnsErrorWhenBookNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->repository->expects($this->never())
            ->method('delete');

        $result = $this->useCase->execute(999);

        $this->assertFalse($result['success']);
        $this->assertEquals('Book not found', $result['message']);
    }

    public function testExecuteReturnsErrorWhenDeleteFails(): void
    {
        $book = $this->createBook(1, 'Book');

        $this->repository->method('findById')
            ->willReturn($book);

        $this->repository->expects($this->once())
            ->method('delete')
            ->with(1)
            ->willReturn(false);

        $result = $this->useCase->execute(1);

        $this->assertFalse($result['success']);
        $this->assertEquals('Failed to delete book', $result['message']);
    }

    public function testExecuteIncludesChapterCountInMessage(): void
    {
        $book = $this->createBook(1, 'My Book', 25);

        $this->repository->method('findById')
            ->willReturn($book);

        $this->repository->method('delete')
            ->willReturn(true);

        $result = $this->useCase->execute(1);

        $this->assertStringContainsString('25 chapters', $result['message']);
    }

    public function testExecuteIncludesBookTitleInMessage(): void
    {
        $book = $this->createBook(1, 'Adventures in Testing', 5);

        $this->repository->method('findById')
            ->willReturn($book);

        $this->repository->method('delete')
            ->willReturn(true);

        $result = $this->useCase->execute(1);

        $this->assertStringContainsString('Adventures in Testing', $result['message']);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testExecuteWithZeroChapters(): void
    {
        $book = $this->createBook(1, 'Empty Book', 0);

        $this->repository->method('findById')
            ->willReturn($book);

        $this->repository->method('delete')
            ->willReturn(true);

        $result = $this->useCase->execute(1);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('0 chapters', $result['message']);
    }

    public function testExecuteResultContainsSuccessAndMessage(): void
    {
        $book = $this->createBook(1, 'Book');

        $this->repository->method('findById')
            ->willReturn($book);

        $this->repository->method('delete')
            ->willReturn(true);

        $result = $this->useCase->execute(1);

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertCount(2, $result);
    }

    public function testExecuteCallsFindByIdFirst(): void
    {
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn(null);

        $this->useCase->execute(42);
    }

    public function testExecuteDoesNotDeleteWhenBookNotFound(): void
    {
        $this->repository->method('findById')
            ->willReturn(null);

        $this->repository->expects($this->never())
            ->method('delete');

        $this->useCase->execute(1);
    }
}
