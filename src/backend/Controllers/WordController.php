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
     * @param array $params Route parameters
     *
     * @return void
     */
    public function delete(array $params): void
    {
        include __DIR__ . '/../Legacy/word_delete.php';
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
     * @param array $params Route parameters
     *
     * @return void
     */
    public function insertWellknown(array $params): void
    {
        include __DIR__ . '/../Legacy/word_insert_wellknown.php';
    }

    /**
     * Insert ignored word (replaces word_insert_ignore.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function insertIgnore(array $params): void
    {
        include __DIR__ . '/../Legacy/word_insert_ignore.php';
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
     * @param array $params Route parameters
     *
     * @return void
     */
    public function setStatus(array $params): void
    {
        include __DIR__ . '/../Legacy/word_set_status.php';
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
