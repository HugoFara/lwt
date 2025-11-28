<?php

/**
 * \file
 * \brief Word Controller - Vocabulary/term management
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/src-php-controllers-wordcontroller.html
 * @since   3.0.0
 */

namespace Lwt\Controllers;

use Lwt\Services\WordService;

/**
 * Controller for vocabulary/term management.
 *
 * Handles:
 * - Word/term CRUD operations
 * - Multi-word expressions
 * - Bulk operations (translate, status changes)
 * - Word import/upload
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class WordController extends BaseController
{
    /**
     * @var WordService Word service instance
     */
    protected WordService $wordService;

    /**
     * Initialize controller with WordService.
     */
    public function __construct()
    {
        parent::__construct();
        $this->wordService = new WordService();
    }

    /**
     * Get the word service instance.
     *
     * @return WordService
     */
    public function getWordService(): WordService
    {
        return $this->wordService;
    }
    /**
     * Edit single word (replaces word_edit.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function edit(array $params): void
    {
        include __DIR__ . '/../Legacy/word_edit.php';
    }

    /**
     * Edit words list (replaces words_edit.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function listEdit(array $params): void
    {
        include __DIR__ . '/../Legacy/words_edit.php';
    }

    /**
     * Edit multi-word expression (replaces word_edit_multi.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function editMulti(array $params): void
    {
        include __DIR__ . '/../Legacy/word_edit_multi.php';
    }

    /**
     * Delete word (replaces word_delete.php)
     *
     * Call: ?tid=[textid]&wid=[wordid]
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function delete(array $params): void
    {
        $textId = isset($_REQUEST['tid']) ? (int) $_REQUEST['tid'] : 0;
        $wordId = isset($_REQUEST['wid']) ? (int) $_REQUEST['wid'] : 0;

        if ($textId === 0 || $wordId === 0) {
            return;
        }

        $term = $this->wordService->getWordText($wordId);
        if ($term === null) {
            return;
        }

        $message = $this->wordService->delete($wordId);

        \pagestart("Term: " . $term, false);

        $wid = $wordId;
        include __DIR__ . '/../Views/Word/delete_result.php';

        \pageend();
    }

    /**
     * Delete multi-word (replaces word_delete_multi.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function deleteMulti(array $params): void
    {
        include __DIR__ . '/../Legacy/word_delete_multi.php';
    }

    /**
     * All words list (replaces words_all.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function all(array $params): void
    {
        include __DIR__ . '/../Legacy/words_all.php';
    }

    /**
     * New word form (replaces word_new.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function create(array $params): void
    {
        include __DIR__ . '/../Legacy/word_new.php';
    }

    /**
     * Show word details (replaces word_show.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function show(array $params): void
    {
        include __DIR__ . '/../Legacy/word_show.php';
    }

    /**
     * Insert well-known word (replaces word_insert_wellknown.php)
     *
     * Call: ?tid=[textid]&ord=[textpos]
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function insertWellknown(array $params): void
    {
        $textId = isset($_REQUEST['tid']) ? (int) $_REQUEST['tid'] : 0;
        $ord = isset($_REQUEST['ord']) ? (int) $_REQUEST['ord'] : 0;

        if ($textId === 0 || $ord === 0) {
            return;
        }

        $word = $this->wordService->getWordAtPosition($textId, $ord);
        if ($word === null) {
            return;
        }

        $result = $this->wordService->insertWordWithStatus($textId, $word, 99);

        \pagestart("Term: " . $word, false);

        $term = $result['term'];
        $wid = $result['id'];
        $hex = $result['hex'];
        include __DIR__ . '/../Views/Word/insert_wellknown_result.php';

        \pageend();
    }

    /**
     * Insert ignored word (replaces word_insert_ignore.php)
     *
     * Call: ?tid=[textid]&ord=[textpos]
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function insertIgnore(array $params): void
    {
        $textId = isset($_REQUEST['tid']) ? (int) $_REQUEST['tid'] : 0;
        $ord = isset($_REQUEST['ord']) ? (int) $_REQUEST['ord'] : 0;

        if ($textId === 0 || $ord === 0) {
            return;
        }

        $word = $this->wordService->getWordAtPosition($textId, $ord);
        if ($word === null) {
            return;
        }

        $result = $this->wordService->insertWordWithStatus($textId, $word, 98);

        \pagestart("Term: " . $word, false);

        $term = $result['term'];
        $wid = $result['id'];
        $hex = $result['hex'];
        include __DIR__ . '/../Views/Word/insert_ignore_result.php';

        \pageend();
    }

    /**
     * Inline edit word (replaces word_inline_edit.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function inlineEdit(array $params): void
    {
        include __DIR__ . '/../Legacy/word_inline_edit.php';
    }

    /**
     * Bulk translate words (replaces word_bulk_translate.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function bulkTranslate(array $params): void
    {
        include __DIR__ . '/../Legacy/word_bulk_translate.php';
    }

    /**
     * Set word status (replaces word_set_status.php)
     *
     * Call: ?tid=[textid]&wid=[wordid]&status=1..5/98/99
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function setStatus(array $params): void
    {
        $textId = isset($_REQUEST['tid']) ? (int) $_REQUEST['tid'] : 0;
        $wordId = isset($_REQUEST['wid']) ? (int) $_REQUEST['wid'] : 0;
        $status = isset($_REQUEST['status']) ? (int) $_REQUEST['status'] : 0;

        if ($textId === 0 || $wordId === 0 || $status === 0) {
            return;
        }

        $wordData = $this->wordService->getWordData($wordId);
        if ($wordData === null) {
            \my_die("Word not found");
            return;
        }

        $term = $wordData['text'];
        $translation = $wordData['translation'] . \getWordTagList($wordId, ' ', 1, 0);
        $romanization = $wordData['romanization'];
        $wid = $wordId;

        \pagestart("Term: $term", false);

        include __DIR__ . '/../Views/Word/status_result.php';

        \pageend();
    }

    /**
     * Upload words (replaces word_upload.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function upload(array $params): void
    {
        include __DIR__ . '/../Legacy/word_upload.php';
    }
}
