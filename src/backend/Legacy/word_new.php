<?php

/**
 * New word, created while reading or testing
 *
 * Call: new_word.php?...
 *      ... text=[textid]&lang=[langid] ... new term input
 *      ... op=Save ... do the insert
 *
 * PHP version 8.1
 *
 * @category Helper_Frame
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 */

namespace Lwt\Interface\New_Word;

require_once 'Core/Bootstrap/db_bootstrap.php';
require_once 'Core/UI/ui_helpers.php';
require_once 'Core/Tag/tags.php';
require_once 'Core/Text/text_helpers.php';
require_once 'Core/Http/param_helpers.php';
require_once 'Core/Word/dictionary_links.php';
require_once 'Core/Language/language_utilities.php';
require_once 'Core/Word/word_status.php';
require_once 'Core/simterms.php';

use Lwt\Database\Escaping;
use Lwt\Database\Maintenance;
use Lwt\Services\WordService;

require_once __DIR__ . '/../Services/WordService.php';

$wordService = new WordService();

// Handle save operation
if (isset($_REQUEST['op']) && $_REQUEST['op'] == 'Save') {
    $result = $wordService->create($_REQUEST);

    $titletext = "New Term: " . tohtml($result['textlc']);
    pagestart_nobody($titletext);
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
        saveWordTags($wid);
        Maintenance::initWordCount();

        echo '<p>' . $result['message'] . '</p>';

        $len = $wordService->getWordCount($wid);
        if ($len > 1) {
            insertExpressions($result['textlc'], $_REQUEST["WoLgID"], $wid, $len, 0);
        } elseif ($len == 1) {
            $wordService->linkToTextItems($wid, $_REQUEST["WoLgID"], $result['textlc']);

            // Prepare view variables
            $hex = $wordService->textToClassName($result['textlc']);
            $translation = repl_tab_nl(getreq("WoTranslation"));
            if ($translation == '') {
                $translation = '*';
            }
            $status = $_REQUEST["WoStatus"];
            $romanization = $_REQUEST["WoRomanization"];
            $text = $result['text'];
            $textId = (int)$_REQUEST['tid'];
            $success = true;

            include __DIR__ . '/../Views/Word/save_result.php';
        }
    }
} else {
    // Display the new word form
    $lang = (int)getreq('lang');
    $textId = (int)getreq('text');
    $scrdir = getScriptDirectionTag($lang);

    $langData = $wordService->getLanguageData($lang);
    $showRoman = $langData['showRoman'];

    pagestart_nobody('');

    include __DIR__ . '/../Views/Word/form_new.php';
}

pageend();
