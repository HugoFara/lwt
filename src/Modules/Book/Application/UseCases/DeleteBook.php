<?php

declare(strict_types=1);

/**
 * Delete Book Use Case
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Book\Application\UseCases
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Book\Application\UseCases;

use Lwt\Modules\Book\Domain\BookRepositoryInterface;

/**
 * Use case for deleting a book and all its chapters.
 *
 * @since 3.0.0
 */
class DeleteBook
{
    private BookRepositoryInterface $bookRepository;

    /**
     * Constructor.
     *
     * @param BookRepositoryInterface $bookRepository Book repository
     */
    public function __construct(BookRepositoryInterface $bookRepository)
    {
        $this->bookRepository = $bookRepository;
    }

    /**
     * Delete a book by its ID.
     *
     * This will cascade delete all associated text chapters due to the
     * foreign key constraint.
     *
     * @param int $bookId Book ID
     *
     * @return array{success: bool, message: string}
     */
    public function execute(int $bookId): array
    {
        $book = $this->bookRepository->findById($bookId);

        if ($book === null) {
            return [
                'success' => false,
                'message' => 'Book not found',
            ];
        }

        $title = $book->title();
        $chapterCount = $book->totalChapters();

        $deleted = $this->bookRepository->delete($bookId);

        if (!$deleted) {
            return [
                'success' => false,
                'message' => 'Failed to delete book',
            ];
        }

        return [
            'success' => true,
            'message' => "Deleted book '{$title}' and {$chapterCount} chapters",
        ];
    }
}
