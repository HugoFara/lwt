<?php declare(strict_types=1);
/**
 * Book API Handler
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Book\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Book\Http;

use Lwt\Modules\Book\Application\BookFacade;
use Lwt\Core\Globals;

/**
 * API handler for book operations.
 *
 * Handles REST API endpoints for book management.
 *
 * @since 3.0.0
 */
class BookApiHandler
{
    private BookFacade $bookFacade;

    /**
     * Constructor.
     *
     * @param BookFacade $bookFacade Book facade
     */
    public function __construct(BookFacade $bookFacade)
    {
        $this->bookFacade = $bookFacade;
    }

    /**
     * Handle GET /api/v1/books request.
     *
     * @param array $params Request parameters
     *
     * @return array Response data
     */
    public function listBooks(array $params): array
    {
        $userId = Globals::getCurrentUserId();
        $languageId = isset($params['lg_id']) ? (int) $params['lg_id'] : null;
        $page = isset($params['page']) ? max(1, (int) $params['page']) : 1;
        $perPage = isset($params['per_page']) ? min(100, max(1, (int) $params['per_page'])) : 20;

        $result = $this->bookFacade->getBooks($userId, $languageId, $page, $perPage);

        return [
            'success' => true,
            'data' => $result['books'],
            'pagination' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'per_page' => $result['perPage'],
                'total_pages' => $result['totalPages'],
            ],
        ];
    }

    /**
     * Handle GET /api/v1/books/{id} request.
     *
     * @param array $params Request parameters (id)
     *
     * @return array Response data
     */
    public function getBook(array $params): array
    {
        $bookId = (int) ($params['id'] ?? 0);

        if ($bookId <= 0) {
            return [
                'success' => false,
                'error' => 'Invalid book ID',
            ];
        }

        $result = $this->bookFacade->getBook($bookId);

        if ($result === null) {
            return [
                'success' => false,
                'error' => 'Book not found',
            ];
        }

        return [
            'success' => true,
            'data' => $result,
        ];
    }

    /**
     * Handle GET /api/v1/books/{id}/chapters request.
     *
     * @param array $params Request parameters (id)
     *
     * @return array Response data
     */
    public function getChapters(array $params): array
    {
        $bookId = (int) ($params['id'] ?? 0);

        if ($bookId <= 0) {
            return [
                'success' => false,
                'error' => 'Invalid book ID',
            ];
        }

        $chapters = $this->bookFacade->getChapters($bookId);

        return [
            'success' => true,
            'data' => $chapters,
        ];
    }

    /**
     * Handle DELETE /api/v1/books/{id} request.
     *
     * @param array $params Request parameters (id)
     *
     * @return array Response data
     */
    public function deleteBook(array $params): array
    {
        $bookId = (int) ($params['id'] ?? 0);

        if ($bookId <= 0) {
            return [
                'success' => false,
                'error' => 'Invalid book ID',
            ];
        }

        $result = $this->bookFacade->deleteBook($bookId);

        return [
            'success' => $result['success'],
            'message' => $result['message'],
        ];
    }

    /**
     * Handle PUT /api/v1/books/{id}/progress request.
     *
     * Update reading progress for a book.
     *
     * @param array $params Request parameters (id, chapter)
     *
     * @return array Response data
     */
    public function updateProgress(array $params): array
    {
        $bookId = (int) ($params['id'] ?? 0);
        $chapterNum = (int) ($params['chapter'] ?? 0);

        if ($bookId <= 0 || $chapterNum <= 0) {
            return [
                'success' => false,
                'error' => 'Invalid book ID or chapter number',
            ];
        }

        $this->bookFacade->updateReadingProgress($bookId, $chapterNum);

        return [
            'success' => true,
            'message' => 'Reading progress updated',
        ];
    }
}
