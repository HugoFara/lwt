<?php declare(strict_types=1);
/**
 * Book Controller
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
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Shared\Infrastructure\Container\Container;
use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;
use Lwt\Shared\UI\Helpers\SelectOptionsBuilder;
use Lwt\Core\Globals;

require_once __DIR__ . '/../../../backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../Shared/UI/Helpers/PageLayoutHelper.php';

/**
 * Controller for book management operations.
 *
 * @since 3.0.0
 */
class BookController
{
    /**
     * View base path.
     */
    private string $viewPath;

    /**
     * Book facade.
     */
    private BookFacade $bookFacade;

    /**
     * Constructor.
     *
     * @param BookFacade $bookFacade Book facade
     */
    public function __construct(BookFacade $bookFacade)
    {
        $this->viewPath = __DIR__ . '/../Views/';
        $this->bookFacade = $bookFacade;
    }

    /**
     * List all books.
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function index(array $params): void
    {
        $userId = Globals::getCurrentUserId();
        $languageId = InputValidator::getInt('lg_id');
        $page = max(1, InputValidator::getInt('page') ?: 1);

        $result = $this->bookFacade->getBooks($userId, $languageId, $page);
        $books = $result['books'];
        $pagination = [
            'total' => $result['total'],
            'page' => $result['page'],
            'perPage' => $result['perPage'],
            'totalPages' => $result['totalPages'],
        ];

        // Get languages for filter dropdown
        $languageFacade = Container::getInstance()->getTyped(LanguageFacade::class);
        $languages = $languageFacade->getAllLanguages();
        $languagesOption = SelectOptionsBuilder::forLanguages($languages, $languageId, "[All Languages]");

        PageLayoutHelper::renderPageStart('My Books', true, 'books');
        include $this->viewPath . 'index.php';
        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Show a single book with chapters.
     *
     * @param array $params Route parameters (id)
     *
     * @return void
     */
    public function show(array $params): void
    {
        $bookId = (int) ($params['id'] ?? 0);

        if ($bookId <= 0) {
            header('Location: /books');
            exit;
        }

        $result = $this->bookFacade->getBook($bookId);

        if ($result === null) {
            header('Location: /books');
            exit;
        }

        $book = $result['book'];
        $chapters = $result['chapters'];

        PageLayoutHelper::renderPageStart($book['title'], true, 'books');
        include $this->viewPath . 'show.php';
        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Show EPUB import form or handle import submission.
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function import(array $params): void
    {
        $op = InputValidator::getString('op');

        if ($op === 'Import') {
            $this->processImport();
            return;
        }

        // Show import form
        $languageFacade = Container::getInstance()->getTyped(LanguageFacade::class);
        $languages = $languageFacade->getAllLanguages();
        $languagesOption = SelectOptionsBuilder::forLanguages($languages, null, "[Choose...]");

        PageLayoutHelper::renderPageStart('Import EPUB', true, 'books');
        include $this->viewPath . 'import_epub_form.php';
        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Process EPUB import submission.
     *
     * @return void
     */
    private function processImport(): void
    {
        $languageId = InputValidator::getInt('LgID');
        $overrideTitle = InputValidator::getString('TxTitle');
        $uploadedFile = InputValidator::getUploadedFile('thefile');
        $userId = Globals::getCurrentUserId();

        // Get tag IDs if any
        $tagIds = [];
        $tagList = InputValidator::getString('TextTags');
        if ($tagList !== '') {
            $tagIds = array_map('intval', explode(',', $tagList));
        }

        if ($languageId <= 0) {
            $message = 'Please select a language';
            $messageType = 'is-danger';
            $this->showImportResult($message, $messageType, null);
            return;
        }

        if ($uploadedFile === null || empty($uploadedFile['tmp_name'])) {
            $message = 'Please select an EPUB file to upload';
            $messageType = 'is-danger';
            $this->showImportResult($message, $messageType, null);
            return;
        }

        $result = $this->bookFacade->importEpub(
            $languageId,
            $uploadedFile,
            $overrideTitle !== '' ? $overrideTitle : null,
            $tagIds,
            $userId
        );

        if ($result['success']) {
            $message = $result['message'];
            $messageType = 'is-success';
            $bookId = $result['bookId'];
        } else {
            $message = $result['message'];
            $messageType = 'is-danger';
            $bookId = null;
        }

        $this->showImportResult($message, $messageType, $bookId);
    }

    /**
     * Show import result page.
     *
     * @param string   $message     Result message
     * @param string   $messageType Bulma notification class
     * @param int|null $bookId      Book ID if successful
     *
     * @return void
     */
    private function showImportResult(string $message, string $messageType, ?int $bookId): void
    {
        PageLayoutHelper::renderPageStart('Import Result', true, 'books');
        include $this->viewPath . 'import_result.php';
        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Delete a book.
     *
     * @param array $params Route parameters (id)
     *
     * @return void
     */
    public function delete(array $params): void
    {
        $bookId = (int) ($params['id'] ?? 0);

        if ($bookId > 0) {
            $result = $this->bookFacade->deleteBook($bookId);
            $message = $result['message'];
        } else {
            $message = 'Invalid book ID';
        }

        // Redirect back to books list with message
        header('Location: /books?message=' . urlencode($message));
        exit;
    }
}
