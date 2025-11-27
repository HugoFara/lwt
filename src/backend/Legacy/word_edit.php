<?php

/**
 * \file
 * \brief Create or edit single word
 *
 * Call: edit_word.php?....
 *  ... op=Save ... do insert new
 *  ... op=Change ... do update
 *  ... fromAnn=recno&tid=[textid]&ord=[textpos] ... calling from impr. annotation editing
 *  ... tid=[textid]&ord=[textpos]&wid= ... new word
 *  ... tid=[textid]&ord=[textpos]&wid=[wordid] ... edit word
 *
 * PHP version 8.1
 *
 * @category Helper_Frame
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    1.0.3
 */

namespace Lwt\Interface\Edit_Word;

require_once 'Core/Bootstrap/db_bootstrap.php';
require_once 'Core/UI/ui_helpers.php';
require_once 'Core/Tag/tags.php';
require_once 'Core/Text/text_helpers.php';
require_once 'Core/Http/param_helpers.php';
require_once 'Core/Word/dictionary_links.php';
require_once 'Core/Language/language_utilities.php';
require_once 'Core/Word/word_status.php';
require_once 'Core/simterms.php';
require_once 'Core/Language/langdefs.php';

use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Database\Settings;
use Lwt\Services\WordService;

require_once __DIR__ . '/../Services/WordService.php';

/**
 * Handle save/update operation
 */
function handleOperation(WordService $wordService, string $fromAnn): void
{
    $textlc = trim(Escaping::prepareTextdata($_REQUEST["WoTextLC"]));
    $text = trim(Escaping::prepareTextdata($_REQUEST["WoText"]));

    // Validate lowercase matches
    if (mb_strtolower($text, 'UTF-8') != $textlc) {
        $titletext = "New/Edit Term: " . tohtml($textlc);
        pagestart_nobody($titletext);
        echo '<h1>' . $titletext . '</h1>';
        $message = 'Error: Term in lowercase must be exactly = "' . $textlc . '", please go back and correct this!';
        echo error_message_with_hide($message, false);
        pageend();
        exit();
    }

    $translation = repl_tab_nl(getreq("WoTranslation"));
    if ($translation == '') {
        $translation = '*';
    }

    if ($_REQUEST['op'] == 'Save') {
        // Insert new term
        $result = $wordService->create($_REQUEST);
        $isNew = true;
        $hex = $wordService->textToClassName($_REQUEST["WoTextLC"]);
        $oldStatus = 0;

        $titletext = "New Term: " . tohtml($textlc);
    } else {
        // Update existing term
        $result = $wordService->update((int)$_REQUEST["WoID"], $_REQUEST);
        $isNew = false;
        $hex = null;
        $oldStatus = $_REQUEST['WoOldStatus'];

        $titletext = "Edit Term: " . tohtml($textlc);
    }

    pagestart_nobody($titletext);
    echo '<h1>' . $titletext . '</h1>';

    $wid = $result['id'];
    $message = $result['message'];

    saveWordTags($wid);

    // Prepare view variables
    $textId = (int)$_REQUEST['tid'];
    $status = $_REQUEST["WoStatus"];
    $romanization = $_REQUEST["WoRomanization"];

    include __DIR__ . '/../Views/Word/edit_result.php';
}

/**
 * Display the word form (new or edit)
 */
function displayForm(WordService $wordService, int $wid, int $textId, int $ord, string $fromAnn): void
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();

    if ($wid == -1) {
        // Get the term from text items
        $termData = $wordService->getTermFromTextItem($textId, $ord);
        if ($termData === null) {
            my_die("Cannot access Term and Language in edit_word.php");
        }
        $term = (string) $termData['Ti2Text'];
        $lang = (int) $termData['Ti2LgID'];
        $termlc = mb_strtolower($term, 'UTF-8');

        // Check if word already exists
        $existingId = $wordService->findByText($termlc, $lang);
        if ($existingId !== null) {
            $new = false;
            $wid = $existingId;
        } else {
            $new = true;
        }
    } else {
        // Get existing word data
        $wordData = $wordService->findById($wid);
        if (!$wordData) {
            my_die("Cannot access Term and Language in edit_word.php");
        }
        $term = (string) $wordData['WoText'];
        $lang = (int) $wordData['WoLgID'];
        $termlc = mb_strtolower($term, 'UTF-8');
        $new = false;
    }

    $titletext = ($new ? "New Term" : "Edit Term") . ": " . tohtml($term);
    pagestart_nobody($titletext);

    $scrdir = getScriptDirectionTag($lang);
    $langData = $wordService->getLanguageData($lang);
    $showRoman = $langData['showRoman'];

    if ($new) {
        // New word form
        $sentence = $wordService->getSentenceForTerm($textId, $ord, $termlc);
        $transUri = $langData['translateUri'];
        $lgname = $langData['name'];
        $langShort = array_key_exists($lgname, LWT_LANGUAGES_ARRAY) ?
            LWT_LANGUAGES_ARRAY[$lgname][1] : '';

        include __DIR__ . '/../Views/Word/form_edit_new.php';
    } else {
        // Edit existing word form
        $wordData = $wordService->findById($wid);
        if (!$wordData) {
            my_die("Cannot access word data");
        }

        $status = $wordData['WoStatus'];
        if ($fromAnn == '' && $status >= 98) {
            $status = 1;
        }

        $sentence = repl_tab_nl($wordData['WoSentence']);
        if ($sentence == '' && $textId !== 0 && $ord !== 0) {
            $sentence = $wordService->getSentenceForTerm($textId, $ord, $termlc);
        }

        $transl = repl_tab_nl($wordData['WoTranslation']);
        if ($transl == '*') {
            $transl = '';
        }

        // Get showRoman from language joined with text
        $showRoman = (bool) Connection::fetchValue(
            "SELECT LgShowRomanization AS value
            FROM {$tbpref}languages JOIN {$tbpref}texts
            ON TxLgID = LgID
            WHERE TxID = $textId"
        );

        include __DIR__ . '/../Views/Word/form_edit_existing.php';
    }
}

/**
 * Main entry point
 */
function do_content(): void
{
    $wordService = new WordService();

    // from-recno or empty
    $fromAnn = getreq("fromAnn");

    if (isset($_REQUEST['op'])) {
        handleOperation($wordService, $fromAnn);
    } else {
        // Display a form
        if (array_key_exists("wid", $_REQUEST) && is_integer(getreq('wid'))) {
            $wid = (int)getreq('wid');
        } else {
            $wid = -1;
        }
        $textId = (int)getreq("tid");
        $ord = (int)getreq("ord");
        displayForm($wordService, $wid, $textId, $ord, $fromAnn);
    }

    pageend();
}


if (
    getreq("wid") != ""
    || getreq("tid") . getreq("ord") != ""
    || array_key_exists("op", $_REQUEST)
) {
    do_content();
}
