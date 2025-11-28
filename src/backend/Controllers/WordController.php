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
     * Call: ?tid=[textid]&ord=[textpos]&wid= - new word
     *       ?tid=[textid]&ord=[textpos]&wid=[wordid] - edit existing
     *       ?op=Save - save new word
     *       ?op=Change - update existing word
     *       ?fromAnn=recno&tid=[textid]&ord=[textpos] - from annotation editing
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function edit(array $params): void
    {
        // Check for valid entry point
        if (
            \getreq("wid") == ""
            && \getreq("tid") . \getreq("ord") == ""
            && !array_key_exists("op", $_REQUEST)
        ) {
            return;
        }

        $fromAnn = \getreq("fromAnn");

        if (isset($_REQUEST['op'])) {
            $this->handleEditOperation($fromAnn);
        } else {
            $wid = (array_key_exists("wid", $_REQUEST) && is_integer(\getreq('wid')))
                ? (int)\getreq('wid')
                : -1;
            $textId = (int)\getreq("tid");
            $ord = (int)\getreq("ord");
            $this->displayEditForm($wid, $textId, $ord, $fromAnn);
        }

        \pageend();
    }

    /**
     * Handle save/update operation for word edit.
     *
     * @param string $fromAnn From annotation flag
     *
     * @return void
     */
    private function handleEditOperation(string $fromAnn): void
    {
        $textlc = trim(\Lwt\Database\Escaping::prepareTextdata($_REQUEST["WoTextLC"]));
        $text = trim(\Lwt\Database\Escaping::prepareTextdata($_REQUEST["WoText"]));

        // Validate lowercase matches
        if (mb_strtolower($text, 'UTF-8') != $textlc) {
            $titletext = "New/Edit Term: " . \tohtml($textlc);
            \pagestart_nobody($titletext);
            echo '<h1>' . $titletext . '</h1>';
            $message = 'Error: Term in lowercase must be exactly = "' . $textlc .
                '", please go back and correct this!';
            echo \error_message_with_hide($message, false);
            \pageend();
            exit();
        }

        $translation = \repl_tab_nl(\getreq("WoTranslation"));
        if ($translation == '') {
            $translation = '*';
        }

        if ($_REQUEST['op'] == 'Save') {
            // Insert new term
            $result = $this->wordService->create($_REQUEST);
            $isNew = true;
            $hex = $this->wordService->textToClassName($_REQUEST["WoTextLC"]);
            $oldStatus = 0;
            $titletext = "New Term: " . \tohtml($textlc);
        } else {
            // Update existing term
            $result = $this->wordService->update((int)$_REQUEST["WoID"], $_REQUEST);
            $isNew = false;
            $hex = null;
            $oldStatus = $_REQUEST['WoOldStatus'];
            $titletext = "Edit Term: " . \tohtml($textlc);
        }

        \pagestart_nobody($titletext);
        echo '<h1>' . $titletext . '</h1>';

        $wid = $result['id'];
        $message = $result['message'];

        \saveWordTags($wid);

        // Prepare view variables
        $textId = (int)$_REQUEST['tid'];
        $status = $_REQUEST["WoStatus"];
        $romanization = $_REQUEST["WoRomanization"];

        include __DIR__ . '/../Views/Word/edit_result.php';
    }

    /**
     * Display the word edit form (new or existing).
     *
     * @param int    $wid     Word ID (-1 for new)
     * @param int    $textId  Text ID
     * @param int    $ord     Word order position
     * @param string $fromAnn From annotation flag
     *
     * @return void
     */
    private function displayEditForm(int $wid, int $textId, int $ord, string $fromAnn): void
    {
        if ($wid == -1) {
            // Get the term from text items
            $termData = $this->wordService->getTermFromTextItem($textId, $ord);
            if ($termData === null) {
                \my_die("Cannot access Term and Language in edit_word.php");
            }
            $term = (string) $termData['Ti2Text'];
            $lang = (int) $termData['Ti2LgID'];
            $termlc = mb_strtolower($term, 'UTF-8');

            // Check if word already exists
            $existingId = $this->wordService->findByText($termlc, $lang);
            if ($existingId !== null) {
                $new = false;
                $wid = $existingId;
            } else {
                $new = true;
            }
        } else {
            // Get existing word data
            $wordData = $this->wordService->findById($wid);
            if (!$wordData) {
                \my_die("Cannot access Term and Language in edit_word.php");
            }
            $term = (string) $wordData['WoText'];
            $lang = (int) $wordData['WoLgID'];
            $termlc = mb_strtolower($term, 'UTF-8');
            $new = false;
        }

        $titletext = ($new ? "New Term" : "Edit Term") . ": " . \tohtml($term);
        \pagestart_nobody($titletext);

        $scrdir = \getScriptDirectionTag($lang);
        $langData = $this->wordService->getLanguageData($lang);
        $showRoman = $langData['showRoman'];

        if ($new) {
            // New word form
            $sentence = $this->wordService->getSentenceForTerm($textId, $ord, $termlc);
            $transUri = $langData['translateUri'];
            $lgname = $langData['name'];
            $langShort = array_key_exists($lgname, \LWT_LANGUAGES_ARRAY) ?
                \LWT_LANGUAGES_ARRAY[$lgname][1] : '';

            include __DIR__ . '/../Views/Word/form_edit_new.php';
        } else {
            // Edit existing word form
            $wordData = $this->wordService->findById($wid);
            if (!$wordData) {
                \my_die("Cannot access word data");
            }

            $status = $wordData['WoStatus'];
            if ($fromAnn == '' && $status >= 98) {
                $status = 1;
            }

            $sentence = \repl_tab_nl($wordData['WoSentence']);
            if ($sentence == '' && $textId !== 0 && $ord !== 0) {
                $sentence = $this->wordService->getSentenceForTerm($textId, $ord, $termlc);
            }

            $transl = \repl_tab_nl($wordData['WoTranslation']);
            if ($transl == '*') {
                $transl = '';
            }

            // Get showRoman from language joined with text
            $tbpref = \Lwt\Core\Globals::getTablePrefix();
            $showRoman = (bool) \Lwt\Database\Connection::fetchValue(
                "SELECT LgShowRomanization AS value
                FROM {$tbpref}languages JOIN {$tbpref}texts
                ON TxLgID = LgID
                WHERE TxID = $textId"
            );

            include __DIR__ . '/../Views/Word/form_edit_existing.php';
        }
    }

    /**
     * Edit term while testing (replaces word_edit_term.php)
     *
     * Call: ?wid=[wordid] - display edit form
     *       ?op=Change - update the term
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function editTerm(array $params): void
    {
        $tbpref = \Lwt\Core\Globals::getTablePrefix();

        $translation_raw = \repl_tab_nl(\getreq("WoTranslation"));
        $translation = ($translation_raw == '') ? '*' : $translation_raw;

        if (isset($_REQUEST['op'])) {
            $this->handleEditTermOperation($tbpref, $translation);
        } else {
            $this->displayEditTermForm($tbpref);
        }

        \pageend();
    }

    /**
     * Handle update operation for edit term.
     *
     * @param string $tbpref      Table prefix
     * @param string $translation Translation value
     *
     * @return void
     */
    private function handleEditTermOperation(string $tbpref, string $translation): void
    {
        $textlc = trim(\Lwt\Database\Escaping::prepareTextdata($_REQUEST["WoTextLC"]));
        $text = trim(\Lwt\Database\Escaping::prepareTextdata($_REQUEST["WoText"]));

        if (mb_strtolower($text, 'UTF-8') != $textlc) {
            $titletext = "New/Edit Term: " . \tohtml(\Lwt\Database\Escaping::prepareTextdata($_REQUEST["WoTextLC"]));
            \pagestart_nobody($titletext);
            echo '<h1>' . $titletext . '</h1>';
            $message = 'Error: Term in lowercase must be exactly = "' . $textlc .
                '", please go back and correct this!';
            echo \error_message_with_hide($message, false);
            \pageend();
            exit();
        }

        if ($_REQUEST['op'] == 'Change') {
            $titletext = "Edit Term: " . \tohtml(\Lwt\Database\Escaping::prepareTextdata($_REQUEST["WoTextLC"]));
            \pagestart_nobody($titletext);
            echo '<h1>' . $titletext . '</h1>';

            $oldstatus = $_REQUEST["WoOldStatus"];
            $newstatus = $_REQUEST["WoStatus"];
            $xx = '';
            if ($oldstatus != $newstatus) {
                $xx = ', WoStatus = ' . $newstatus . ', WoStatusChanged = NOW()';
            }

            \Lwt\Database\Connection::execute(
                'update ' . $tbpref . 'words set WoText = ' .
                \Lwt\Database\Escaping::toSqlSyntax($_REQUEST["WoText"]) . ', WoTranslation = ' .
                \Lwt\Database\Escaping::toSqlSyntax($translation) . ', WoSentence = ' .
                \Lwt\Database\Escaping::toSqlSyntax(\repl_tab_nl($_REQUEST["WoSentence"])) .
                ', WoRomanization = ' .
                \Lwt\Database\Escaping::toSqlSyntax($_REQUEST["WoRomanization"]) . $xx .
                ',' . \make_score_random_insert_update('u') .
                ' where WoID = ' . $_REQUEST["WoID"],
                "Updated"
            );
            $wid = (int)$_REQUEST["WoID"];
            \saveWordTags($wid);

            $message = 'Updated';

            $lang = \Lwt\Database\Connection::fetchValue(
                'select WoLgID as value from ' . $tbpref . 'words where WoID = ' . $wid
            );
            if (!isset($lang)) {
                \my_die('Cannot retrieve language in edit_tword.php');
            }
            $regexword = \Lwt\Database\Connection::fetchValue(
                'select LgRegexpWordCharacters as value from ' . $tbpref . 'languages where LgID = ' . $lang
            );
            if (!isset($regexword)) {
                \my_die('Cannot retrieve language data in edit_tword.php');
            }
            $sent = \tohtml(\repl_tab_nl($_REQUEST["WoSentence"]));
            $sent1 = str_replace(
                "{",
                ' <b>[',
                str_replace(
                    "}",
                    ']</b> ',
                    \mask_term_in_sentence($sent, $regexword)
                )
            );

            $status = $_REQUEST["WoStatus"];
            $romanization = $_REQUEST["WoRomanization"];
            $text = $_REQUEST["WoText"];

            include __DIR__ . '/../Views/Word/edit_term_result.php';
        }
    }

    /**
     * Display the edit term form.
     *
     * @param string $tbpref Table prefix
     *
     * @return void
     */
    private function displayEditTermForm(string $tbpref): void
    {
        $wid = \getreq('wid');

        if ($wid == '') {
            \my_die("Term ID missing in edit_tword.php");
        }

        $sql = 'select WoText, WoLgID, WoTranslation, WoSentence, WoRomanization, WoStatus from ' .
            $tbpref . 'words where WoID = ' . $wid;
        $res = \Lwt\Database\Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        if ($record) {
            $term = (string) $record['WoText'];
            $lang = (int) $record['WoLgID'];
            $transl = \repl_tab_nl($record['WoTranslation']);
            if ($transl == '*') {
                $transl = '';
            }
            $sentence = \repl_tab_nl($record['WoSentence']);
            $rom = $record['WoRomanization'];
            $status = $record['WoStatus'];
            $showRoman = (bool) \Lwt\Database\Connection::fetchValue(
                "SELECT LgShowRomanization AS value
                FROM {$tbpref}languages
                WHERE LgID = $lang"
            );
        } else {
            \my_die("Term data not found in edit_tword.php");
        }
        mysqli_free_result($res);

        $termlc = mb_strtolower($term, 'UTF-8');
        $titletext = "Edit Term: " . \tohtml($term);
        \pagestart_nobody($titletext);
        $scrdir = \getScriptDirectionTag($lang);

        include __DIR__ . '/../Views/Word/form_edit_term.php';
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
     * Mark all words as well-known or ignored (replaces words_all.php)
     *
     * Call: ?text=[textid] - mark all as well-known (99)
     *       ?text=[textid]&stat=[status] - mark with specific status (98 or 99)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function all(array $params): void
    {
        if (!isset($_REQUEST['text'])) {
            return;
        }

        $textId = (int) $_REQUEST['text'];
        $status = isset($_REQUEST['stat']) ? (int) $_REQUEST['stat'] : 99;

        if ($status == 98) {
            \pagestart("Setting all blue words to Ignore", false);
        } else {
            \pagestart("Setting all blue words to Well-known", false);
        }

        list($count, $javascript) = $this->wordService->markAllWordsWithStatus($textId, $status);

        include __DIR__ . '/../Views/Word/all_wellknown_result.php';

        \pageend();
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
     * Call: ?tid=[textid]&sl=[sourcelg]&tl=[targetlg]&offset=[pos]
     *       POST: term[n][text], term[n][lg], term[n][status], term[n][trans]
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function bulkTranslate(array $params): void
    {
        $tid = (int) ($_REQUEST['tid'] ?? 0);
        $pos = isset($_REQUEST['offset']) ? (int) $_REQUEST['offset'] : null;

        // Handle form submission (save terms)
        if (isset($_REQUEST['term'])) {
            $terms = $_REQUEST['term'];
            $cnt = count($terms);

            if ($pos !== null) {
                $pos -= $cnt;
            }

            \pagestart($cnt . ' New Word' . ($cnt == 1 ? '' : 's') . ' Saved', false);
            $this->handleBulkSave($terms, $tid, $pos === null);
        } else {
            \pagestart_nobody('Translate New Words');
        }

        // Show next page of terms if there are more
        if ($pos !== null) {
            $sl = $_REQUEST['sl'] ?? null;
            $tl = $_REQUEST['tl'] ?? null;
            $this->displayBulkTranslateForm($tid, $sl, $tl, $pos);
        }

        \pageend();
    }

    /**
     * Handle saving bulk translated terms.
     *
     * @param array $terms   Array of term data
     * @param int   $tid     Text ID
     * @param bool  $cleanUp Whether to clean up right frames after save
     *
     * @return void
     */
    private function handleBulkSave(array $terms, int $tid, bool $cleanUp): void
    {
        $maxWoId = $this->wordService->bulkSaveTerms($terms);

        $tooltipMode = \Lwt\Database\Settings::getWithDefault('set-tooltip-mode');
        $res = $this->wordService->getNewWordsAfter($maxWoId);

        $this->wordService->linkNewWordsToTextItems($maxWoId);

        // Prepare data for view
        $newWords = [];
        while ($record = mysqli_fetch_assoc($res)) {
            $record['hex'] = \strToClassName(
                \Lwt\Database\Escaping::prepareTextdata($record['WoTextLC'])
            );
            $record['translation'] = $record['WoTranslation'];
            $newWords[] = $record;
        }
        mysqli_free_result($res);

        include __DIR__ . '/../Views/Word/bulk_save_result.php';
    }

    /**
     * Display the bulk translate form.
     *
     * @param int         $tid Text ID
     * @param string|null $sl  Source language code
     * @param string|null $tl  Target language code
     * @param int         $pos Offset position
     *
     * @return void
     */
    private function displayBulkTranslateForm(int $tid, ?string $sl, ?string $tl, int $pos): void
    {
        $limit = (int) \Lwt\Database\Settings::getWithDefault('set-ggl-translation-per-page') + 1;
        $dictionaries = $this->wordService->getLanguageDictionaries($tid);

        $res = $this->wordService->getUnknownWordsForBulkTranslate($tid, $pos, $limit);

        // Collect terms and check if there are more
        $terms = [];
        $hasMore = false;
        $cnt = 0;
        while ($record = mysqli_fetch_assoc($res)) {
            $cnt++;
            if ($cnt < $limit) {
                $terms[] = $record;
            } else {
                $hasMore = true;
            }
        }
        mysqli_free_result($res);

        // Calculate next offset if there are more terms
        $nextOffset = $hasMore ? $pos + $limit - 1 : null;

        include __DIR__ . '/../Views/Word/bulk_translate_form.php';
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
