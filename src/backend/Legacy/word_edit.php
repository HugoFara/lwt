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

require_once 'Core/database_connect.php';
require_once 'Core/UI/ui_helpers.php';
require_once 'Core/Tag/tags.php';
require_once 'Core/Text/text_helpers.php';
require_once 'Core/Http/param_helpers.php';
require_once 'Core/Word/dictionary_links.php';
require_once 'Core/Language/language_utilities.php';
require_once 'Core/Word/word_status.php';
require_once 'Core/simterms.php';
require_once 'Core/Language/langdefs.php';

use Lwt\Database\Escaping;
use Lwt\Database\Settings;

/**
 * Insert a new word to the database
 *
 * @param string $textlc      The word to insert, in lowercase
 * @param string $translation Translation of this term
 *
 * @return (int|string)[] Word id, and then an insertion message
 *
 * @psalm-return list{int, string}
 */
function insert_new_word($textlc, $translation): array
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();

    $titletext = "New Term: " . tohtml(Escaping::prepareTextdata($_REQUEST["WoTextLC"]));
    pagestart_nobody($titletext);
    echo '<h1>' . $titletext . '</h1>';

    $message = runsql(
        'INSERT INTO ' . $tbpref . 'words
        (
            WoLgID, WoTextLC, WoText, WoStatus, WoTranslation,
            WoSentence, WoWordCount, WoRomanization, WoStatusChanged,'
            .  make_score_random_insert_update('iv') . '
        ) VALUES(
            ' . $_REQUEST["WoLgID"] . ', ' .
            convert_string_to_sqlsyntax($_REQUEST["WoTextLC"]) . ', ' .
            convert_string_to_sqlsyntax($_REQUEST["WoText"]) . ', ' .
            $_REQUEST["WoStatus"] . ', ' .
            convert_string_to_sqlsyntax($translation) . ', ' .
            convert_string_to_sqlsyntax(repl_tab_nl($_REQUEST["WoSentence"])) . ', 1, ' .
            convert_string_to_sqlsyntax($_REQUEST["WoRomanization"]) . ', NOW(), ' .
            make_score_random_insert_update('id') .
        ')',
        "Term saved"
    );
    $wid = get_last_key();
    do_mysqli_query(
        'UPDATE ' . $tbpref . 'textitems2 SET Ti2WoID = ' . $wid . '
        WHERE Ti2LgID = ' . $_REQUEST["WoLgID"] . ' AND LOWER(Ti2Text) =' .
        convert_string_to_sqlsyntax_notrim_nonull($textlc)
    );
    return array($wid, $message);
}

/**
 * Edit an existing term.
 *
 * @param string $translation New translation for this term
 *
 * @return array{0: string, 1: string} Word id, and then an insertion message
 */
function edit_term($translation)
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();

    $titletext = "Edit Term: " . tohtml(Escaping::prepareTextdata($_REQUEST["WoTextLC"]));
    pagestart_nobody($titletext);
    echo '<h1>' . $titletext . '</h1>';

    $oldstatus = $_REQUEST["WoOldStatus"];
    $newstatus = $_REQUEST["WoStatus"];
    $xx = '';
    if ($oldstatus != $newstatus) {
        $xx = ', WoStatus = ' .    $newstatus . ', WoStatusChanged = NOW()';
    }

    $message = runsql(
        'update ' . $tbpref . 'words set WoText = ' .
        convert_string_to_sqlsyntax($_REQUEST["WoText"]) . ', WoTranslation = ' .
        convert_string_to_sqlsyntax($translation) . ', WoSentence = ' .
        convert_string_to_sqlsyntax(repl_tab_nl($_REQUEST["WoSentence"])) . ', WoRomanization = ' .
        convert_string_to_sqlsyntax($_REQUEST["WoRomanization"]) . $xx . ',' .
        make_score_random_insert_update('u') .
        ' where WoID = ' . $_REQUEST["WoID"],
        "Updated"
    );
    $wid = $_REQUEST["WoID"];
    return array($wid, $message);
}

/**
 * Use this function if the lowercase version of the word does not correspond.
 * It will echo an error message.
 *
 * @param string $textlc The lowercase version of the word we want.
 */
function lowercase_term_not_equal($textlc): void
{
    $titletext = "New/Edit Term: " . tohtml(Escaping::prepareTextdata($_REQUEST["WoTextLC"]));
    pagestart_nobody($titletext);
    echo '<h1>' . $titletext . '</h1>';
    $message =
    'Error: Term in lowercase must be exactly = "' .
    $textlc . '", please go back and correct this!';
    echo error_message_with_hide($message, false);
}

/**
 * Echoes a JavaScript element, that will edit terms diplay
 */
function change_term_display(int $wid, string $translation, ?string $hex): void
{
    ?>
<script type="text/javascript">
    const context = window.parent.document.getElementById('frame-l');
    const contexth = window.parent.document.getElementById('frame-h');
    const woid = <?php echo Escaping::prepareTextdataJs($wid); ?>;
    const status = <?php echo Escaping::prepareTextdataJs($_REQUEST["WoStatus"]); ?>;
    const trans = <?php
    echo Escaping::prepareTextdataJs($translation . getWordTagList($wid, ' ', 1, 0));
    ?>;
    const roman = <?php echo Escaping::prepareTextdataJs($_REQUEST["WoRomanization"]); ?>;
    let title;
    if (window.parent.LWT_DATA.settings.jQuery_tooltip) {
        title = '';
    } else {
        title = make_tooltip(
            <?php echo Escaping::prepareTextdataJs($_REQUEST["WoText"]); ?>,
            trans, roman, status
        );
    }
    <?php
    if ($_REQUEST['op'] == 'Save') {
        ?>
        $('.TERM<?php echo $hex; ?>', context)
        .removeClass('status0')
        .addClass('word' + woid + ' ' + 'status' + status)
        .attr('data_trans', trans)
        .attr('data_rom', roman)
        .attr('data_status', status)
        .attr('title', title)
        .attr('data_wid', woid);
        <?php
    } else {
        ?>
        $('.word' + woid, context)
        .removeClass('status<?php echo $_REQUEST['WoOldStatus']; ?>')
        .addClass('status' + status)
        .attr('data_trans', trans)
        .attr('data_rom', roman)
        .attr('data_status', status)
        .attr('title', title);
        <?php
    }
    ?>
    $('#learnstatus', contexth)
    .html('<?php echo addslashes(todo_words_content((int) $_REQUEST['tid'])); ?>');

    cleanupRightFrames();
</script>
    <?php
}

// INS/UPD
function edit_word_do_operation(string $translation, string $fromAnn): void
{
    $hex = null;
    $textlc = trim(Escaping::prepareTextdata($_REQUEST["WoTextLC"]));
    $text = trim(Escaping::prepareTextdata($_REQUEST["WoText"]));

    if (mb_strtolower($text, 'UTF-8') != $textlc) {
        lowercase_term_not_equal($textlc);
        pageend();
        exit();
    }

    if ($_REQUEST['op'] == 'Save') {
        // Insert new term
        $output = insert_new_word($textlc, $translation);
        $hex = strToClassName(Escaping::prepareTextdata($_REQUEST["WoTextLC"]));
    } else {
        // Update existing term
        $output = edit_term($translation);
    }
    $wid = $output[0];
    $message = $output[1];
    saveWordTags($wid);

    echo '<p>OK: ' . tohtml($message) . '</p>';

    if ($fromAnn === "") {
        // Word was edited from reading screen
        change_term_display((int)$wid, $translation, $hex);
    } else {
        // Word is from the annotation editing window
        ?>
<script type="text/javascript">
    window.opener.do_ajax_edit_impr_text(
        <?php echo $fromAnn; ?>,
        <?php echo Escaping::prepareTextdataJs($textlc); ?>,
        <?php echo $wid; ?>
        );
</script>
        <?php
    }
}


/**
 * @return void
 */
function edit_word_do_form(int $wid, int $text_id, int $ord, string $fromAnn)
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();

    if ($wid == -1) {
        // Get the $wid from the term text
        $sql =
        "SELECT Ti2Text, Ti2LgID FROM {$tbpref}textitems2
        WHERE Ti2TxID = $text_id AND Ti2WordCount = 1 AND Ti2Order = $ord";
        $res = do_mysqli_query($sql);
        $record = mysqli_fetch_assoc($res);
        if ($record === false) {
            my_die("Failure while running the SQL query!");
        }
        if ($record === null) {
            my_die("Cannot access Term and Language in edit_word.php");
        }
        $term = (string) $record['Ti2Text'];
        $lang = (int) $record['Ti2LgID'];
        mysqli_free_result($res);

        $termlc = mb_strtolower($term, 'UTF-8');

        $temp_id = get_first_value(
            "SELECT WoID AS value
            FROM {$tbpref}words
            WHERE WoLgID = $lang AND WoTextLC = " .
            convert_string_to_sqlsyntax($termlc)
        );
        if ($temp_id === null) {
            $new = true;
        } else {
            $new = false;
            $wid = (int)$temp_id;
        }
    } else {
        $sql = "SELECT WoText, WoLgID FROM {$tbpref}words WHERE WoID = $wid";
        $res = do_mysqli_query($sql);
        $record = mysqli_fetch_assoc($res);
        if (!$record) {
            my_die("Cannot access Term and Language in edit_word.php");
        }
        $term = (string) $record['WoText'];
        $lang = (int) $record['WoLgID'];
        mysqli_free_result($res);
        $termlc = mb_strtolower($term, 'UTF-8');
        $new = false;
    }

    $titletext = ($new ? "New Term" : "Edit Term") . ": " . tohtml($term);
    pagestart_nobody($titletext);
    ?>
<script type="text/javascript">
    $(document).ready(lwtFormCheck.askBeforeExit);
    $(window).on('beforeunload',function() {
        setTimeout(function() {window.parent.frames['ru'].location.href = 'empty.html';}, 0);
    });
</script>
    <?php
    $scrdir = getScriptDirectionTag($lang);

    // NEW

    if ($new) {
        $seid = get_first_value(
            "SELECT Ti2SeID AS value FROM " . $tbpref . "textitems2
            WHERE Ti2TxID = $text_id AND Ti2WordCount = 1 AND Ti2Order = $ord"
        );
        $sent = getSentence(
            $seid,
            $termlc,
            (int) Settings::getWithDefault('set-term-sentence-count')
        );
        $trans_uri = (string) get_first_value(
            "SELECT LgGoogleTranslateURI AS value FROM {$tbpref}languages
            WHERE LgID = $lang"
        );
        $lgname = (string) get_first_value(
            "SELECT LgName AS value FROM {$tbpref}languages
            WHERE LgID = $lang"
        );
        $lang_short = array_key_exists($lgname, LWT_LANGUAGES_ARRAY) ?
        LWT_LANGUAGES_ARRAY[$lgname][1] : '';
        $showRoman = (bool) get_first_value(
            "SELECT LgShowRomanization AS value
            FROM {$tbpref}languages
            WHERE LgID = $lang"
        );

        ?>

 <form name="newword" class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
 <input type="hidden" name="fromAnn" value="<?php echo $fromAnn; ?>" />
 <input type="hidden" name="WoLgID" id="langfield" value="<?php echo $lang; ?>" />
 <input type="hidden" name="WoTextLC" value="<?php echo tohtml($termlc); ?>" />
 <input type="hidden" name="tid" value="<?php echo $text_id; ?>" />
 <input type="hidden" name="ord" value="<?php echo $ord; ?>" />
 <table class="tab2" cellspacing="0" cellpadding="5">
    <tr title="Only change uppercase/lowercase!">
        <td class="td1 right"><b>New Term:</b></td>
        <td class="td1">
            <input <?php echo $scrdir; ?> class="notempty checkoutsidebmp"
            data_info="New Term" type="text"
            name="WoText" id="wordfield" value="<?php echo tohtml($term); ?>"
            maxlength="250" size="35" />
            <img src="/assets/icons/status-busy.png" title="Field must not be empty"
            alt="Field must not be empty" />
        </td>
    </tr>
            <?php print_similar_terms_tabrow(); ?>
    <tr>
        <td class="td1 right">Translation:</td>
        <td class="td1">
            <textarea name="WoTranslation"
            class="setfocus textarea-noreturn checklength checkoutsidebmp"
            data_maxlength="500"
            data_info="Translation" cols="35" rows="3"></textarea>
        </td>
    </tr>
    <tr>
        <td class="td1 right">Tags:</td>
        <td class="td1">
            <?php echo getWordTags(0); ?>
        </td>
    </tr>
    <tr class="<?php echo ($showRoman ? '' : 'hide'); ?>">
        <td class="td1 right">Romaniz.:</td>
        <td class="td1">
            <input type="text" class="checkoutsidebmp" data_info="Romanization"
            name="WoRomanization"
            value="" maxlength="100" size="35" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">Sentence<br />Term in {...}:</td>
        <td class="td1">
            <textarea <?php echo $scrdir; ?> name="WoSentence"
            class="textarea-noreturn checklength checkoutsidebmp"
            data_maxlength="1000" data_info="Sentence" cols="35"
            rows="3"><?php echo tohtml(repl_tab_nl($sent[1])); ?></textarea>
        </td>
    </tr>
            <?php print_similar_terms_tabrow(); ?>
    <tr>
        <td class="td1 right">Status:</td>
        <td class="td1">
            <?php echo get_wordstatus_radiooptions(1); ?>
        </td>
    </tr>
    <tr>
        <td class="td1 right" colspan="2">
            <?php echo createDictLinksInEditWin(
                $lang,
                $term,
                'document.forms[0].WoSentence',
                !isset($_GET['nodict'])
            ); ?>
        &nbsp; &nbsp; &nbsp;
        <input type="submit" name="op" value="Save" /></td>
    </tr>
 </table>
 </form>
    <script type="text/javascript">
        const TRANS_URI = <?php echo json_encode($trans_uri); ?>
        const LANG_SHORT = <?php echo json_encode($lang_short); ?> ||
        getLangFromDict(TRANS_URI);

        /**
         * Sets the translation of a term.
         */
        const autoTranslate = function () {
            const translator_url = new URL(TRANS_URI);
            const urlParams = translator_url.searchParams;
            if (urlParams.get("lwt_translator") == "libretranslate") {
                const term = $('#wordfield').val();
                getLibreTranslateTranslation(
                    translator_url, term,
                    (urlParams.has("source") ?
                    urlParams.get("source") : LANG_SHORT),
                    urlParams.get("target")
                )
                .then(function (translation) {
                    newword.WoTranslation.value = translation;
                });
            }
        }

        /**
         * Sets the romanization of a term.
         */
        const autoRomanization = function (lgid) {
            const term = $('#wordfield').val();
            getPhoneticTextAsync(term, lgid)
            .then(function (phonetic) {
                newword.WoRomanization.value = phonetic["phonetic_reading"];
            });
        }

        autoTranslate();
        autoRomanization(<?php echo json_encode($lang); ?>);

    </script>
        <?php
        // Display example sentence button
        example_sentences_area($lang, $termlc, 'document.forms.newword.WoSentence', 0);
    } else {
        // CHG
        $sql = "SELECT WoTranslation, WoSentence, WoRomanization, WoStatus
        FROM {$tbpref}words WHERE WoID = $wid";
        $res = do_mysqli_query($sql);
        if ($record = mysqli_fetch_assoc($res)) {
            $status = $record['WoStatus'];
            if ($fromAnn == '' && $status >= 98) {
                $status = 1;
            }
            $sentence = repl_tab_nl($record['WoSentence']);
            if ($sentence == '' && $text_id !== 0 && $ord !== 0) {
                $seid = get_first_value(
                    "SELECT Ti2SeID as value from {$tbpref}textitems2
                    where Ti2TxID = $text_id and Ti2WordCount = 1 and Ti2Order = $ord"
                );
                $sent = getSentence($seid, $termlc, (int) Settings::getWithDefault('set-term-sentence-count'));
                $sentence = repl_tab_nl($sent[1]);
            }
            $transl = repl_tab_nl($record['WoTranslation']);
            if ($transl == '*') {
                $transl = '';
            }
            $showRoman = (bool) get_first_value(
                "SELECT LgShowRomanization AS value
                FROM {$tbpref}languages JOIN {$tbpref}texts
                ON TxLgID = LgID
                WHERE TxID = $text_id"
            );
            ?>

     <form name="editword" class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
     <input type="hidden" name="WoLgID" id="langfield" value="<?php echo $lang; ?>" />
     <input type="hidden" name="fromAnn" value="<?php echo $fromAnn; ?>" />
     <input type="hidden" name="WoID" value="<?php echo $wid; ?>" />
     <input type="hidden" name="WoOldStatus" value="<?php echo $record['WoStatus']; ?>" />
     <input type="hidden" name="WoTextLC" value="<?php echo tohtml($termlc); ?>" />
     <input type="hidden" name="tid" value="<?php echo getreq('tid'); ?>" />
     <input type="hidden" name="ord" value="<?php echo getreq('ord'); ?>" />
     <table class="tab2" cellspacing="0" cellpadding="5">
        <tr title="Only change uppercase/lowercase!">
            <td class="td1 right"><b>Edit Term:</b></td>
            <td class="td1">
                <input <?php echo $scrdir; ?> class="notempty checkoutsidebmp"
                data_info="Term" type="text"
                name="WoText" id="wordfield"
                value="<?php echo tohtml($term); ?>" maxlength="250" size="35" />
                <img src="/assets/icons/status-busy.png" title="Field must not be empty"
                alt="Field must not be empty" />
            </td>
        </tr>
            <?php print_similar_terms_tabrow(); ?>
        <tr>
            <td class="td1 right">Translation:</td>
            <td class="td1">
                <textarea name="WoTranslation"
                class="setfocus textarea-noreturn checklength checkoutsidebmp"
                data_maxlength="500" data_info="Translation" cols="35"
                rows="3"><?php echo tohtml($transl); ?></textarea>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Tags:</td>
            <td class="td1">
                <?php echo getWordTags($wid); ?>
            </td>
        </tr>
        <tr class="<?php echo ($showRoman ? '' : 'hide'); ?>">
            <td class="td1 right">Romaniz.:</td>
            <td class="td1">
                <input type="text" class="checkoutsidebmp"
                data_info="Romanization" name="WoRomanization" maxlength="100"
                size="35"
                value="<?php echo tohtml($record['WoRomanization']); ?>" />
            </td>
        </tr>
        <tr>
            <td class="td1 right">Sentence<br />Term in {...}:</td>
            <td class="td1">
                <textarea <?php echo $scrdir; ?> name="WoSentence"
                class="textarea-noreturn checklength checkoutsidebmp"
                data_maxlength="1000" data_info="Sentence" cols="35"
                rows="3"><?php echo tohtml($sentence); ?></textarea>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Status:</td>
            <td class="td1">
                <?php echo get_wordstatus_radiooptions($status); ?>
            </td>
        </tr>
        <tr>
            <td class="td1 right" colspan="2">
                <?php
                if ($fromAnn !== '') {
                    echo createDictLinksInEditWin2(
                        $lang,
                        'document.forms[0].WoSentence',
                        'document.forms[0].WoText'
                    );
                } else {
                    echo createDictLinksInEditWin(
                        $lang,
                        $term,
                        'document.forms[0].WoSentence',
                        !isset($_GET['nodict'])
                    );
                }
                ?>
                &nbsp; &nbsp; &nbsp;
                <input type="submit" name="op" value="Change" />
            </td>
        </tr>
     </table>
     </form>
            <?php
            // Display example sentences button
            example_sentences_area(
                $lang,
                $termlc,
                'document.forms.editword.WoSentence',
                $wid
            );
        }
        mysqli_free_result($res);
    }
}

function do_content(): void
{
    // from-recno or empty
    $fromAnn = getreq("fromAnn");
    if (isset($_REQUEST['op'])) {
        $translation_raw = repl_tab_nl(getreq("WoTranslation"));
        if ($translation_raw == '') {
            $translation = '*';
        } else {
            $translation = $translation_raw;
        }
        edit_word_do_operation($translation, $fromAnn);
    } else {
        // Display a form
        if (array_key_exists("wid", $_REQUEST) && is_integer(getreq('wid'))) {
            $wid = (int)getreq('wid');
        } else {
            $wid = -1;
        }
        $text_id = (int)getreq("tid");
        $ord = (int)getreq("ord");
        edit_word_do_form($wid, $text_id, $ord, $fromAnn);
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

?>
