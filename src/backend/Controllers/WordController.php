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
     * Delete multi-word expression (replaces word_delete_multi.php)
     *
     * Call: ?wid=[wordid]&tid=[textid]
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function deleteMulti(array $params): void
    {
        $textId = isset($_REQUEST['tid']) ? (int) $_REQUEST['tid'] : 0;
        $wordId = isset($_REQUEST['wid']) ? (int) $_REQUEST['wid'] : 0;

        $term = $this->wordService->getWordText($wordId);
        if ($term === null) {
            \my_die('Word not found');
            return;
        }

        \pagestart("Term: " . $term, false);

        $rowsAffected = $this->wordService->deleteMultiWord($wordId);

        $showAll = \Lwt\Database\Settings::getZeroOrOne('showallwords', 1);
        $wid = $wordId;

        include __DIR__ . '/../Views/Word/delete_multi_result.php';

        \pageend();
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
     * Call: ?text=[textid]&lang=[langid] - display new term form
     *       ?op=Save - save the new term
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function create(array $params): void
    {
        // Handle save operation
        if (isset($_REQUEST['op']) && $_REQUEST['op'] === 'Save') {
            $result = $this->wordService->create($_REQUEST);

            $titletext = "New Term: " . \tohtml($result['textlc']);
            \pagestart_nobody($titletext);
            echo '<h1>' . $titletext . '</h1>';

            if (!$result['success']) {
                // Handle duplicate entry error
                if (strpos($result['message'], 'Duplicate entry') !== false) {
                    $message = 'Error: <b>Duplicate entry for <i>' . $result['textlc'] .
                        '</i></b><br /><br /><input type="button" value="&lt;&lt; Back" onclick="history.back();" />';
                } else {
                    $message = $result['message'];
                }
                echo '<p>' . $message . '</p>';
            } else {
                $wid = $result['id'];
                \saveWordTags($wid);
                \Lwt\Database\Maintenance::initWordCount();

                echo '<p>' . $result['message'] . '</p>';

                $len = $this->wordService->getWordCount($wid);
                if ($len > 1) {
                    \insertExpressions($result['textlc'], (int) $_REQUEST["WoLgID"], $wid, $len, 0);
                } elseif ($len == 1) {
                    $this->wordService->linkToTextItems($wid, (int) $_REQUEST["WoLgID"], $result['textlc']);

                    // Prepare view variables
                    $hex = $this->wordService->textToClassName($result['textlc']);
                    $translation = \repl_tab_nl(\getreq("WoTranslation"));
                    if ($translation === '') {
                        $translation = '*';
                    }
                    $status = $_REQUEST["WoStatus"];
                    $romanization = $_REQUEST["WoRomanization"];
                    $text = $result['text'];
                    $textId = (int)$_REQUEST['tid'];
                    $success = true;
                    $message = $result['message'];

                    include __DIR__ . '/../Views/Word/save_result.php';
                }
            }
        } else {
            // Display the new word form
            $lang = (int)\getreq('lang');
            $textId = (int)\getreq('text');
            $scrdir = \getScriptDirectionTag($lang);

            $langData = $this->wordService->getLanguageData($lang);
            $showRoman = $langData['showRoman'];

            \pagestart_nobody('');

            include __DIR__ . '/../Views/Word/form_new.php';
        }

        \pageend();
    }

    /**
     * Show word details (replaces word_show.php)
     *
     * Call: ?wid=[wordid]&ann=[annotation]
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function show(array $params): void
    {
        \pagestart_nobody('Term');

        $wid = \getreq('wid');
        $ann = isset($_REQUEST['ann']) ? $_REQUEST['ann'] : '';

        if ($wid === '') {
            \my_die('Word not found in show_word.php');
            return;
        }

        $word = $this->wordService->getWordDetails((int) $wid);
        if ($word === null) {
            \my_die('Word not found');
            return;
        }

        $tags = \getWordTagList($wid, '', 0, 0);
        $scrdir = \getScriptDirectionTag($word['langId']);

        include __DIR__ . '/../Views/Word/show.php';

        \pageend();
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
     * Handles AJAX inline editing of translation or romanization fields.
     * POST parameters:
     * - id: string - Field identifier (e.g., "trans123" or "roman123" where 123 is word ID)
     * - value: string - New value for the field
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function inlineEdit(array $params): void
    {
        $value = isset($_POST['value']) ? $_POST['value'] : '';
        $id = isset($_POST['id']) ? $_POST['id'] : '';

        if (substr($id, 0, 5) === 'trans') {
            $wordId = (int) substr($id, 5);
            echo $this->wordService->updateTranslation($wordId, $value);
            return;
        }

        if (substr($id, 0, 5) === 'roman') {
            $wordId = (int) substr($id, 5);
            echo $this->wordService->updateRomanization($wordId, $value);
            return;
        }

        echo 'ERROR - please refresh page!';
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
