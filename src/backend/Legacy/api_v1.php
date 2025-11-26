<?php

namespace Lwt\Ajax;

require_once 'Core/session_utility.php';
require_once 'Core/simterms.php';
require_once __DIR__ . '/test_test.php';
require_once 'Core/langdefs.php';

// ============================================================================
// Functions migrated from ajax_add_term_transl.php
// ============================================================================

/**
 * Add the translation for a new term.
 *
 * @param string $text Associated text
 * @param int    $lang Language ID
 * @param string $data Translation
 *
 * @return (int|string)[]|string [new word ID, lowercase $text] if success, error message otherwise
 *
 * @since 2.9.0 Error messages are much more explicit
 * @since 2.9.0 Return an array
 *
 * @psalm-return list{int, string}|string
 */
function add_new_term_transl($text, $lang, $data): array|string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $textlc = mb_strtolower($text, 'UTF-8');
    $dummy = runsql(
        "INSERT INTO {$tbpref}words (
            WoLgID, WoTextLC, WoText, WoStatus, WoTranslation,
            WoSentence, WoRomanization, WoStatusChanged,
            " .  make_score_random_insert_update('iv') . '
        ) VALUES( ' .
        $lang . ', ' .
        convert_string_to_sqlsyntax($textlc) . ', ' .
        convert_string_to_sqlsyntax($text) . ', 1, ' .
        convert_string_to_sqlsyntax($data) . ', ' .
        convert_string_to_sqlsyntax('') . ', ' .
        convert_string_to_sqlsyntax('') . ', NOW(), ' .
        make_score_random_insert_update('id') . ')', ""
    );
    if (!is_numeric($dummy)) {
        // Error message
        return $dummy;
    }
    if ((int)$dummy != 1) {
        return "Error: $dummy rows affected, expected 1!";
    }
    $wid = get_last_key();
    do_mysqli_query(
        "UPDATE {$tbpref}textitems2
        SET Ti2WoID = $wid
        WHERE Ti2LgID = $lang AND LOWER(Ti2Text) = " .
        convert_string_to_sqlsyntax_notrim_nonull($textlc)
    );
    return array($wid, $textlc);
}

/**
 * Edit the translation for an existing term.
 *
 * @param int    $wid       Word ID
 * @param string $new_trans New translation
 *
 * @return string WoTextLC, lowercase version of the word
 */
function edit_term_transl($wid, $new_trans)
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $oldtrans = (string) get_first_value(
        "SELECT WoTranslation AS value
        FROM {$tbpref}words
        WHERE WoID = $wid"
    );

    $oldtransarr = preg_split('/[' . get_sepas()  . ']/u', $oldtrans);
    if ($oldtransarr === false) {
        // Something wrong happened, stop here
        return (string)get_first_value(
            "SELECT WoTextLC AS value
            FROM {$tbpref}words
            WHERE WoID = $wid"
        );
    }
    array_walk($oldtransarr, 'trim_value');

    if (!in_array($new_trans, $oldtransarr)) {
        if (trim($oldtrans) == '' || trim($oldtrans) == '*') {
            $oldtrans = $new_trans;
        } else {
            $oldtrans .= ' ' . get_first_sepa() . ' ' . $new_trans;
        }
        runsql(
            "UPDATE {$tbpref}words
            SET WoTranslation = " . convert_string_to_sqlsyntax($oldtrans) .
            " WHERE WoID = $wid",
            ""
        );
    }
    return (string)get_first_value(
        "SELECT WoTextLC AS value
        FROM {$tbpref}words
        WHERE WoID = $wid"
    );
}


/**
 * Edit term translation if it exists.
 *
 * @param int    $wid       Word ID
 * @param string $new_trans New translation
 *
 * @return string Term in lower case, or error message if term does not exist
 */
function do_ajax_check_update_translation($wid, $new_trans)
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $cnt_words = (int)get_first_value(
        "SELECT COUNT(WoID) AS value
        FROM {$tbpref}words
        WHERE WoID = $wid"
    );
    if ($cnt_words == 1) {
        return edit_term_transl($wid, $new_trans);
    }
    return "Error: " . $cnt_words . " word ID found!";
}

// ============================================================================
// Functions migrated from ajax_chg_term_status.php
// ============================================================================

/**
 * Force a term to get a new status.
 *
 * @param string|int $wid    ID of the word to edit
 * @param string|int $status New status to set
 *
 * @return string Number of affected rows or error message
 */
function set_word_status($wid, $status)
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $m1 = runsql(
        "UPDATE {$tbpref}words
        SET WoStatus = $status, WoStatusChanged = NOW()," .
        make_score_random_insert_update('u') . "
        WHERE WoID = $wid",
        ''
    );
    return $m1;
}

/**
 * Check the consistency of the new status.
 *
 * @param int  $oldstatus Old status
 * @param bool $up        True if status should incremented, false if decrementation needed
 *
 * @return int<1, 5>|98|99 New status in the good number range.
 */
function get_new_status($oldstatus, $up)
{
    $currstatus = $oldstatus;
    if ($up) {
        $currstatus++; // 98,1,2,3,4,5 => 99,2,3,4,5,6
        if ($currstatus == 99) {
            $currstatus = 1;  // 98->1
        } else if ($currstatus == 6) {
            $currstatus = 99;  // 5->99
        }
    } else {
        $currstatus--; // 1,2,3,4,5,99 => 0,1,2,3,4,98
        if ($currstatus == 98) {
            $currstatus = 5;  // 99->5
        } else if ($currstatus == 0) {
            $currstatus = 98;  // 1->98
        }
    }
    return $currstatus;
}

/**
 * Save the new word status to the database, return the controls.
 *
 * @param int $wid        Word ID
 * @param int $currstatus Current status in the good value range.
 *
 * @return string|null HTML-formatted string with plus/minus controls if a success.
 */
function update_word_status($wid, $currstatus)
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    if (($currstatus >= 1 && $currstatus <= 5) || $currstatus == 99 || $currstatus == 98) {
        $m1 = (int)set_word_status($wid, $currstatus);
        if ($m1 == 1) {
            $currstatus = get_first_value(
                "SELECT WoStatus AS value FROM {$tbpref}words WHERE WoID = $wid"
            );
            if (!isset($currstatus)) {
                return null;
            }
            return make_status_controls_test_table(1, (int)$currstatus, $wid);
        }
    } else {
        return null;
    }
}


/**
 * Do a word status change.
 *
 * @param int  $wid Word ID
 * @param bool $up  Should the status be incremeted or decremented
 *
 * @return string HTML-formatted string for increments
 */
function ajax_increment_term_status($wid, $up)
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();

    $tempstatus = get_first_value(
        "SELECT WoStatus as value
        FROM {$tbpref}words
        WHERE WoID = $wid"
    );
    if (!isset($tempstatus)) {
        return '';
    }
    $currstatus = get_new_status((int)$tempstatus, $up);
    $formatted = update_word_status($wid, $currstatus);
    if ($formatted === null) {
        return '';
    }
    return $formatted;
}

// ============================================================================
// Functions migrated from ajax_save_text_position.php
// ============================================================================

/**
 * Save the reading position of the text.
 *
 * @param int $textid   Text ID
 * @param int $position Position in text to save
 *
 * @return void
 */
function save_text_position($textid, $position)
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    runsql(
        "UPDATE {$tbpref}texts
        SET TxPosition = $position
        WHERE TxID = $textid",
        ""
    );
}

/**
 * Save the audio position in the text.
 *
 * @param int $textid        Text ID
 * @param int $audioposition Audio position
 *
 * @return void
 */
function save_audio_position($textid, $audioposition)
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    runsql(
        "UPDATE {$tbpref}texts
        SET TxAudioPosition = $audioposition
        WHERE TxID = $textid",
        ""
    );
}

// ============================================================================
// Functions migrated from ajax_save_impr_text.php
// ============================================================================

/**
 * Save data from printed text.
 *
 * @param int    $textid Text ID
 * @param int    $line   Line number to save
 * @param string $val    Proposed new annotation for the term
 *
 * @return string Error message, or "OK" if success.
 */
function save_impr_text_data($textid, $line, $val): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $ann = (string) get_first_value(
        "SELECT TxAnnotatedText AS value
        FROM {$tbpref}texts
        WHERE TxID = $textid"
    );
    $items = preg_split('/[\n]/u', $ann);
    if (count($items) <= $line) {
        return "Unreachable translation: line request is $line, but only " .
        count($items) . " translations were found";
    }
    // Annotation should be in format "pos   term text   term ID    translation"
    $vals = preg_split('/[\t]/u', $items[$line]);
    if ((int)$vals[0] <= -1) {
        return "Term is punctation! Term position is {$vals[0]}";
    }
    if (count($vals) < 4) {
        return "Not enough columns: " . count($vals);
    }
    // Change term translation
    $items[$line] = implode("\t", array($vals[0], $vals[1], $vals[2], $val));
    runsql(
        "UPDATE {$tbpref}texts
        SET TxAnnotatedText = " .
        convert_string_to_sqlsyntax(implode("\n", $items)) . "
        WHERE TxID = $textid",
        ""
    );
    return "OK";
}

/**
 * Save a text with improved annotations.
 *
 * @param int    $textid Text ID
 * @param string $elem   Element to select
 * @param mixed  $data   Data element
 *
 * @return string[] Result as array, with answer on "error" or "success"
 *
 * @psalm-return array{error?: string, success?: 'OK'}
 */
function save_impr_text($textid, $elem, $data): array
{
    $new_annotation = $data->{$elem};
    $line = (int)substr($elem, 2);
    if (str_starts_with($elem, "rg") && $new_annotation == "") {
        $new_annotation = $data->{'tx' . $line};
    }
    $status = save_impr_text_data($textid, $line, $new_annotation);
    if ($status != "OK") {
        $output = array("error" => $status);
    } else {
        $output = array("success" => $status);
    }
    return $output;
}

// ============================================================================
// Functions migrated from ajax_show_imported_terms.php
// ============================================================================

/**
 * Limit the current page within valid bounds.
 *
 * @param int $currentpage Current page number
 * @param int $recno       Record number
 * @param int $maxperpage  Maximum records per page
 *
 * @return int Valid page number
 */
function limit_current_page($currentpage, $recno, $maxperpage)
{
    $pages = intval(($recno-1) / $maxperpage) + 1;
    if ($currentpage < 1) {
        $currentpage = 1;
    }
    if ($currentpage > $pages) {
        $currentpage = $pages;
    }
    return $currentpage;
}

/**
 * Select imported terms from the database.
 *
 * @param string $last_update Last update timestamp
 * @param int    $offset      Offset for pagination
 * @param int    $max_terms   Maximum terms to return
 *
 * @return (float|int|null|string)[][]
 *
 * @psalm-return list<list<float|int|null|string>>
 */
function select_imported_terms($last_update, $offset, $max_terms): array
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $sql = "SELECT WoID, WoText, WoTranslation, WoRomanization, WoSentence,
    IFNULL(WoSentence, '') LIKE CONCAT('%{', WoText, '}%') AS SentOK,
    WoStatus,
    IFNULL(
        CONCAT(
            '[',
            group_concat(DISTINCT TgText ORDER BY TgText separator ', '),
            ']'
        ), ''
    ) AS taglist
    FROM (
        ({$tbpref}words LEFT JOIN {$tbpref}wordtags ON WoID = WtWoID)
        LEFT JOIN {$tbpref}tags ON TgID = WtTgID
    )
    WHERE WoStatusChanged > " . convert_string_to_sqlsyntax($last_update) . "
    GROUP BY WoID
    LIMIT $offset, $max_terms";
    $res = do_mysqli_query($sql);
    $records = mysqli_fetch_all($res);
    mysqli_free_result($res);
    return $records;
}

/**
 * Return the list of imported terms of pages information.
 *
 * @param string $last_update Terms import time
 * @param int    $currentpage Current page number
 * @param int    $recno       Number of imported terms
 *
 * @return ((int|mixed)[]|mixed)[]
 *
 * @psalm-return array{navigation: array{current_page: mixed, total_pages: int}, terms: mixed}
 */
function imported_terms_list($last_update, $currentpage, $recno): array
{
    $maxperpage = 100;
    $currentpage = limit_current_page($currentpage, $recno, $maxperpage);
    $offset = ($currentpage - 1) * $maxperpage;

    $pages = intval(($recno-1) / $maxperpage) + 1;
    $output = array(
        "navigation" => array(
            "current_page" => $currentpage,
            "total_pages" => $pages
        ),
        "terms" => select_imported_terms($last_update, $offset, $maxperpage)
    );
    return $output;
}

// ============================================================================
// Functions migrated from ajax_edit_impr_text.php
// ============================================================================

namespace Lwt\Ajax\Improved_Text;

/**
 * Make the translations choices for a term.
 *
 * @param int      $i     Word unique index in the form
 * @param int|null $wid   Word ID or null
 * @param string   $trans Current translation set for the term, may be empty
 * @param string   $word  Term text
 * @param int      $lang  Language ID
 *
 * @return string HTML-formatted string
 */
function make_trans($i, $wid, $trans, $word, $lang): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $trans = trim($trans);
    $widset = is_numeric($wid);
    $r = "";
    if ($widset) {
        $alltrans = (string) get_first_value(
            "SELECT WoTranslation AS value FROM {$tbpref}words
            WHERE WoID = $wid"
        );
        $transarr = preg_split('/[' . get_sepas()  . ']/u', $alltrans);
        $set = false;
        $set_default = true;
        foreach ($transarr as $t) {
            $tt = trim($t);
            if ($tt == '*' || $tt == '') {
                continue;
            }
            $set_default = false;
            // true if the translation should be checked (this translation is set)
            $set = $set || $tt == $trans;
            // Add a candidate annotation
            $r .= '<span class="nowrap">
                <input class="impr-ann-radio" ' .
                ($tt == $trans ? 'checked="checked" ' : '') . 'type="radio" name="rg' .
                $i . '" value="' . tohtml($tt) . '" />
                &nbsp;' . tohtml($tt) . '
            </span>
            <br />';
        }
        ;
    }
    // Set the empty translation if no translation have been set yet
    $set = $set || $set_default;
    // Empty radio button and text field after the list of translations
    $r .= '<span class="nowrap">
    <input class="impr-ann-radio" type="radio" name="rg' . $i . '" ' .
    ($set ? 'checked="checked" ' : '') . 'value="" />
    &nbsp;
    <input class="impr-ann-text" type="text" name="tx' . $i .
    '" id="tx' . $i . '" value="' . ($set ? tohtml($trans) : '') .
    '" maxlength="50" size="40" />
     &nbsp;
    <img class="click" src="/assets/icons/eraser.png" title="Erase Text Field"
    alt="Erase Text Field"
    onclick="$(\'#tx' . $i . '\').val(\'\').trigger(\'change\');" />
     &nbsp;
    <img class="click" src="/assets/icons/star.png" title="* (Set to Term)"
    alt="* (Set to Term)"
    onclick="$(\'#tx' . $i . '\').val(\'*\').trigger(\'change\');" />
    &nbsp;';
    // Add the "plus button" to add a translation
    if ($widset) {
        $r .=
        '<img class="click" src="/assets/icons/plus-button.png"
        title="Save another translation to existent term"
        alt="Save another translation to existent term"
        onclick="updateTermTranslation(' . $wid . ', \'#tx' . $i . '\');" />';
    } else {
        $r .=
        '<img class="click" src="/assets/icons/plus-button.png"
        title="Save translation to new term"
        alt="Save translation to new term"
        onclick="addTermTranslation(\'#tx' . $i . '\',' . prepare_textdata_js($word) . ',' . $lang . ');" />';
    }
    $r .= '&nbsp;&nbsp;
    <span id="wait' . $i . '">
        <img src="/assets/icons/empty.gif" />
    </span>
    </span>';
    return $r;
}


/**
 * Find the possible translations for a term.
 *
 * @param int $word_id Term ID
 *
 * @return string[] Return the possible translations.
 *
 * @psalm-return list<non-empty-string>
 */
function get_translations($word_id): array
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $translations = array();
    $alltrans = (string) get_first_value(
        "SELECT WoTranslation AS value FROM {$tbpref}words
        WHERE WoID = $word_id"
    );
    $transarr = preg_split('/[' . get_sepas()  . ']/u', $alltrans);
    foreach ($transarr as $t) {
        $tt = trim($t);
        if ($tt == '*' || $tt == '') {
            continue;
        }
        $translations[] = $tt;
    }
    return $translations;
}


/**
 * Gather useful data to edit a term annotation on a specific text.
 *
 * @param string $wordlc Term in lower case
 * @param int    $textid Text ID
 *
 * @return (array|int|null|string)[] Return the useful data to edit a term annotation on a specific text.
 *
 * @psalm-return array{term_lc?: string, wid?: int|null, trans?: string, ann_index?: int<0, max>, term_ord?: int, translations?: array, lang_id?: int, error?: 'Annotation line is ill-formatted'|'Annotation not found'}
 */
function get_term_translations($wordlc, $textid): array
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $sql = "SELECT TxLgID, TxAnnotatedText
    FROM {$tbpref}texts WHERE TxID = $textid";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $langid = (int)$record['TxLgID'];
    $ann = (string)$record['TxAnnotatedText'];
    if (strlen($ann) > 0) {
        $ann = recreate_save_ann($textid, $ann);
    }
    mysqli_free_result($res);

    // Get the first annotation containing the input word
    $annotations = preg_split('/[\n]/u', $ann);
    $i = -1;
    foreach (array_values($annotations) as $index => $annotation_line) {
        $vals = preg_split('/[\t]/u', $annotation_line);
        // Check if annotation could be split
        if ($vals === false) {
            continue;
        }
        // Skip when term is punctuation
        if ($vals[0] <= -1) {
            continue;
        }
        // Check if the input word is the same as the annotation
        if (trim($wordlc) != mb_strtolower(trim($vals[1]), 'UTF-8')) {
            continue;
        }
        $i = $index;
        break;
    }

    $ann_data = array();
    if ($i == -1) {
        $ann_data["error"] = "Annotation not found";
        return $ann_data;
    }

    // Get the line conatining the annotation
    $annotation_line = $annotations[$i];
    $vals = preg_split('/[\t]/u', $annotation_line);
    if ($vals === false) {
        $ann_data["error"] = "Annotation line is ill-formatted";
        return $ann_data;
    }
    $ann_data["term_lc"] = trim($wordlc);
    $ann_data["wid"] = null;
    $ann_data["trans"] = '';
    $ann_data["ann_index"] = $i;
    $ann_data["term_ord"] = (int)$vals[0];
    // Annotation should be in format "pos   term text   term ID    translation"
    $wid = null;
    // Word exists and has an ID
    if (count($vals) > 2 && ctype_digit($vals[2])) {
        $wid = (int)$vals[2];
        $temp_wid = (int)get_first_value(
            "SELECT COUNT(WoID) AS value
            FROM {$tbpref}words
            WHERE WoID = $wid"
        );
        if ($temp_wid < 1) {
            $wid = null;
        }
    }
    if ($wid !== null) {
        $ann_data["wid"] = $wid;
        // Add other translation choices
        $ann_data["translations"] = get_translations($wid);
    }
    // Current translation
    if (count($vals) > 3) {
        $ann_data["trans"] = $vals[3];
    }
    $ann_data["lang_id"] = $langid;
    return $ann_data;
}

/**
 * Full form for terms edition in a given text.
 *
 * @param int $textid Text ID.
 *
 * @return string HTML table for all terms
 */
function edit_term_form($textid): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $sql = "SELECT TxLgID, TxAnnotatedText
    FROM {$tbpref}texts WHERE TxID = $textid";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $langid = (int) $record['TxLgID'];
    $ann = (string) $record['TxAnnotatedText'];
    if (strlen($ann) > 0) {
        $ann = recreate_save_ann($textid, $ann);
    }
    mysqli_free_result($res);

    $sql = "SELECT LgTextSize, LgRightToLeft
    FROM {$tbpref}languages WHERE LgID = $langid";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $textsize = (int)$record['LgTextSize'];
    if ($textsize > 100) {
        $textsize = intval($textsize * 0.8);
    }
    $rtlScript = $record['LgRightToLeft'];
    mysqli_free_result($res);

    $r =
    '<form action="" method="post">
        <table class="tab2" cellspacing="0" cellpadding="5">
            <tr>
                <th class="th1 center">Text</th>
                <th class="th1 center">Dict.</th>
                <th class="th1 center">Edit<br />Term</th>
                <th class="th1 center">
                    Term Translations (Delim.: ' .
                    tohtml(getSettingWithDefault('set-term-translation-delimiters')) . ')
                    <br />
                    <input type="button" value="Reload" onclick="do_ajax_edit_impr_text(0,\'\');" />
                </th>
            </tr>';
    $items = preg_split('/[\n]/u', $ann);
    $nontermbuffer ='';
    foreach (array_values($items) as $i => $item) {
        $vals = preg_split('/[\t]/u', $item);
        if ((int)$vals[0] > -1) {
            if ($nontermbuffer != '') {
                $r .= '<tr>
                    <td class="td1 center" style="font-size:' . $textsize . '%;">' .
                        $nontermbuffer .
                    '</td>
                    <td class="td1 right" colspan="3">
                    <img class="click" src="/assets/icons/tick.png" title="Back to \'Display/Print Mode\'" alt="Back to \'Display/Print Mode\'" onclick="location.href=\'print_impr_text.php?text=' . $textid . '\';" />
                    </td>
                </tr>';
                $nontermbuffer = '';
            }
            $wid = null;
            $trans = '';
            if (count($vals) > 2) {
                $str_wid = $vals[2];
                if (is_numeric($str_wid)) {
                    $temp_wid = (int)get_first_value(
                        "SELECT COUNT(WoID) AS value
                        FROM {$tbpref}words
                        WHERE WoID = $str_wid"
                    );
                    if ($temp_wid < 1) {
                        $wid = null;
                    } else {
                        $wid = (int) $str_wid;
                    }
                } else {
                    $wid = null;
                }
            }
            if (count($vals) > 3) {
                $trans = $vals[3];
            }
            $word_link = "&nbsp;";
            if ($wid !== null) {
                $word_link = '<a name="rec' . $i . '"></a>
                <span class="click"
                onclick="oewin(\'edit_word.php?fromAnn=\' + $(document).scrollTop() + \'&amp;wid=' .
                $wid . '&amp;tid=' . $textid . '&amp;ord=' . (int)$vals[0] . '\');">
                    <img src="/assets/icons/sticky-note--pencil.png" title="Edit Term" alt="Edit Term" />
                </span>';
            }
            $r .= '<tr>
                <td class="td1 center" style="font-size:' . $textsize . '%;"' .
                ($rtlScript ? ' dir="rtl"' : '') . '>
                    <span id="term' . $i . '">' . tohtml($vals[1]) .
                    '</span>
                </td>
                <td class="td1 center" nowrap="nowrap">' .
                    makeDictLinks($langid, prepare_textdata_js($vals[1])) .
                '</td>
                <td class="td1 center">
                    <span id="editlink' . $i . '">' . $word_link . '</span>
                </td>
                <td class="td1" style="font-size:90%;">
                    <span id="transsel' . $i . '">' .
                        make_trans($i, $wid, $trans, $vals[1], $langid) . '
                    </span>
                </td>
            </tr>';
        } else {
            // Not a term, may add a new line
            $nontermbuffer .= str_replace(
                "¶",
                '<img src="/assets/icons/new_line.png" title="New Line" alt="New Line" />',
                tohtml(trim($vals[1]))
            );
        }
    }
    if ($nontermbuffer != '') {
        $r .= '<tr>
            <td class="td1 center" style="font-size:' . $textsize . '%;">' .
            $nontermbuffer .
            '</td>
            <td class="td1 right" colspan="3">
                <img class="click" src="/assets/icons/tick.png" title="Back to \'Display/Print Mode\'" alt="Back to \'Display/Print Mode\'" onclick="location.href=\'print_impr_text.php?text=' . $textid . '\';" />
            </td>
        </tr>';
    }
    $r .= '
                <th class="th1 center">Text</th>
                <th class="th1 center">Dict.</th>
                <th class="th1 center">Edit<br />Term</th>
                <th class="th1 center">
                    Term Translations (Delim.: ' .
                    tohtml(getSettingWithDefault('set-term-translation-delimiters')) . ')
                    <br />
                    <input type="button" value="Reload" onclick="do_ajax_edit_impr_text(1e6,\'\');" />
                    <a name="bottom"></a>
                </th>
            </tr>
        </table>
    </form>';
    return $r;
}

// ============================================================================
// Functions migrated from ajax_load_feed.php
// ============================================================================

namespace Lwt\Ajax\Feed;

/**
 * Get the list of feeds.
 *
 * @param string[][] $feed A feed
 * @param int        $nfid News feed ID
 *
 * @return array{0: int, 1: int} Number of imported feeds and number of duplicated feeds.
 */
function get_feeds_list($feed, $nfid): array
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $valuesArr = array();
    foreach ($feed as $data) {
        $d_title=convert_string_to_sqlsyntax($data['title']);
        $d_link=convert_string_to_sqlsyntax($data['link']);
        $d_text=convert_string_to_sqlsyntax(isset($data['text']) ?  $data['text'] : null);
        $d_desc=convert_string_to_sqlsyntax($data['desc']);
        $d_date=convert_string_to_sqlsyntax($data['date']);
        $d_audio=convert_string_to_sqlsyntax($data['audio']);
        $d_feed=convert_string_to_sqlsyntax((string)$nfid);
        $valuesArr[] = "($d_title,$d_link,$d_text,$d_desc,$d_date,$d_audio,$d_feed)";
    }
    $sql = 'INSERT IGNORE INTO ' . $tbpref . 'feedlinks (FlTitle,FlLink,FlText,FlDescription,FlDate,FlAudio,FlNfID)
    VALUES ' . implode(',', $valuesArr);
    do_mysqli_query($sql);
    $imported_feed = mysqli_affected_rows($GLOBALS["DBCONNECTION"]);
    $nif = count($valuesArr) - $imported_feed;
    unset($valuesArr);
    return array($imported_feed, $nif);
}

/**
 * Update the feeds database and return a result message.
 *
 * @param int    $imported_feed Number of imported feeds
 * @param int    $nif           Number of duplicated feeds
 * @param string $nfname        News feed name
 * @param int    $nfid          News feed ID
 * @param string $nfoptions     News feed options
 *
 * @return string Result message
 */
function get_feed_result($imported_feed, $nif, $nfname, $nfid, $nfoptions): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    do_mysqli_query(
        'UPDATE ' . $tbpref . 'newsfeeds
        SET NfUpdate="' . time() . '"
        WHERE NfID=' . $nfid
    );
    $nf_max_links = get_nf_option($nfoptions, 'max_links');
    if (!$nf_max_links) {
        if (get_nf_option($nfoptions, 'article_source')) {
            $nf_max_links=getSettingWithDefault('set-max-articles-with-text');
        } else {
            $nf_max_links=getSettingWithDefault('set-max-articles-without-text');
        }
    }
    $msg = $nfname . ": ";
    if (!$imported_feed) {
        $msg .= "no";
    } else {
        $msg .= $imported_feed;
    }
    $msg .= " new article";
    if ($imported_feed > 1) {
        $msg .=  "s";
    }
    $msg .= " imported";
    if ($nif > 1) {
        $msg .= ", $nif articles are dublicates";
    } else if ($nif==1) {
        $msg.= ", $nif dublicated article";
    }
    $result=do_mysqli_query(
        "SELECT COUNT(*) AS total
        FROM " . $tbpref . "feedlinks
        WHERE FlNfID IN (".$nfid.")"
    );
    $row = mysqli_fetch_assoc($result);
    $to = ($row['total'] - $nf_max_links);
    if ($to>0) {
        do_mysqli_query(
            "DELETE FROM " . $tbpref . "feedlinks
            WHERE FlNfID in (".$nfid.")
            ORDER BY FlDate
            LIMIT $to"
        );
        $msg.= ", $to old article(s) deleted";
    }
    return $msg;
}

/**
 * Load a feed and return result.
 *
 * @param string $nfname      Newsfeed name
 * @param int    $nfid        News feed ID
 * @param string $nfsourceuri News feed source
 * @param string $nfoptions   News feed options
 *
 * @return array Result with success or error message
 */
function load_feed($nfname, $nfid, $nfsourceuri, $nfoptions): array
{
    $feed = get_links_from_rss($nfsourceuri, get_nf_option($nfoptions, 'article_source'));
    if (empty($feed)) {
        return array(
            "error" => 'Could not load "' . $nfname . '"'
        );
    }
    list($imported_feed, $nif) = get_feeds_list($feed, $nfid);
    $msg = get_feed_result($imported_feed, $nif, $nfname, $nfid, $nfoptions);
    return array(
        "success" => true,
        "message" => $msg,
        "imported" => $imported_feed,
        "duplicates" => $nif
    );
}

// ============================================================================
// Back to main namespace for API handling
// ============================================================================

namespace Lwt\Ajax;


/**
 * @var string Version of this current LWT API.
 */
define('LWT_API_VERSION', "0.1.1");

/**
 * @var string Date of the last released change of the LWT API.
 */
define('LWT_API_RELEASE_DATE', "2023-12-29");

/**
 * Send JSON response and exit.
 *
 * @param int   $status Status code to display
 * @param mixed $data   Any data to return
 *
 * @return never
 */
function send_response($status = 200, $data = null)
{
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit;
}

/**
 * Check if an API endpoint exists.
 *
 * @param string $method     Method name (e.g. 'GET' or 'POST')
 * @param string $requestURI The URI being requested.
 *
 * @return string The first matching endpoint
 */
function endpoint_exits($method, $requestUri)
{
    // Set up API endpoints
    $endpoints = [
        'languages' => [ 'GET' ],
        //'languages/(?<lang-id>\d+)/reading-configuration' => [ 'GET' ],

        'media-files' => [ 'GET' ],

        'phonetic-reading' => [ 'GET' ],

        'review/next-word' => [ 'GET' ],
        'review/tomorrow-count' => [ 'GET' ],

        'sentences-with-term' => [ 'GET' ],
        //'sentences-with-term/(?<term-id>\d+)' => [ 'GET' ],

        'similar-terms' => [ 'GET' ],

        'settings' => [ 'POST' ],
        'settings/theme-path' => [ 'GET' ],

        'terms' => [ 'GET', 'POST' ],
        'terms/imported' => [ 'GET' ],
        'terms/new' => [ 'POST' ],

        //'terms/(?<term-id>\d+)/translations' => [ 'GET', 'POST' ],

        //'terms/(?<term-id>\d+)/status/down' => [ 'POST' ],
        //'terms/(?<term-id>\d+)/status/up' => [ 'POST' ],
        //'terms/(?<term-id>\d+)/status/(?<new-status>\d+)' => [ 'POST' ],

        'texts' => [ 'POST' ],

        //'texts/(?<text-id>\d+)/annotation' => [ 'POST' ],
        //'texts/(?<text-id>\d+)/audio-position' => [ 'POST' ],
        //'texts/(?<text-id>\d+)/reading-position' => [ 'POST' ],

        'texts/statistics' => [ 'GET' ],

        'feeds' => [ 'POST' ],

        'version' => [ 'GET' ],
    ];


    // Extract requested endpoint from URI
    $uri_query = parse_url($requestUri, PHP_URL_PATH);
    // Support both legacy /api.php/v1/ and new /api/v1/ URL formats
    $matching = preg_match('/(.*?\/api(?:\.php)?\/v\d\/).+/', $uri_query, $matches);
    if (!$matching) {
        send_response(400, ['error' => 'Unrecognized URL format ' . $uri_query]);
    }
    if (count($matches) == 0) {
        send_response(404, ['error' => 'Wrong API Location: ' . $uri_query]);
    }
    // endpoint without prepending URL, like 'version'
    $req_endpoint = rtrim(str_replace($matches[1], '', $uri_query), '/');
    if (array_key_exists($req_endpoint, $endpoints)) {
        $methods_allowed = $endpoints[$req_endpoint];
    } else {
        $first_elem = preg_split('/\//', $req_endpoint)[0];
        if (array_key_exists($first_elem, $endpoints)) {
            $methods_allowed = $endpoints[$first_elem];
        } else {
            send_response(404, ['error' => 'Endpoint Not Found: ' . $req_endpoint]);
        }
    }

    // Validate request method for the req_endpoint
    if (!in_array($method, $methods_allowed)) {
        send_response(405, ['error' => 'Method Not Allowed']);
    }
    return $req_endpoint;
}


// -------------------------- GET REQUESTS -------------------------

/**
 * Return the API version.
 *
 * @param array $get_req GET request, unnused
 *
 * @return string[] JSON-encoded version
 *
 * @psalm-return array{version: '0.1.1', release_date: '2023-12-29'}
 */
function rest_api_version($get_req): array
{
    return array(
        "version"      => LWT_API_VERSION,
        "release_date" => LWT_API_RELEASE_DATE
    );
}

/**
 * List the audio and video files in the media folder.
 *
 * @param array $get_req Unnused
 *
 * @return string[] Path of media files
 */
function media_files($get_req)
{
    return get_media_paths();
}


/**
 * The way text should be read
 */
function readingConfiguration($get_req): array
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    // language, voiceAPI, abbr
    $req = do_mysqli_query(
        "SELECT LgName, LgTTSVoiceAPI, LgRegexpWordCharacters FROM {$tbpref}languages
        WHERE LgID = " . $get_req["lang_id"]
    );
    $record = mysqli_fetch_assoc($req);
    $abbr = getLanguageCode($get_req["lang_id"], LWT_LANGUAGES_ARRAY);
    if ($record["LgTTSVoiceAPI"] != '') {
        $readingMode = "external";
    } elseif ($record["LgRegexpWordCharacters"] == "mecab") {
        $readingMode = "internal";
    } else {
        $readingMode = "direct";
    }
    return array(
        "name" => $record["LgName"],
        "voiceapi" => $record["LgTTSVoiceAPI"],
        "word_parsing" => $record["LgRegexpWordCharacters"],
        "abbreviation" => $abbr,
        "reading_mode" => $readingMode
    );
}

/**
 * Get the phonetic reading of a word based on it's language.
 *
 * @param array $get_req Array with the fields "text" and "lang" (short language name)
 *
 * @return string[] JSON-encoded result
 *
 * @psalm-return array{phonetic_reading: string}
 *
 * @since 2.10.0-fork Can also accept a language ID with "lgid" parameter
 */
function get_phonetic_reading($get_req): array
{
    if (array_key_exists("lang_id", $get_req)) {
        $data = phoneticReading($get_req['text'], $get_req['lang_id']);
    } else {
        $data = phonetic_reading($get_req['text'], $get_req['lang']);
    }
    return array("phonetic_reading" => $data);
}


/**
 * Retun the next word to test as JSON
 *
 * @param string $testsql   SQL projection query
 * @param bool   $word_mode Test is in word mode
 * @param int    $lgid      Language ID
 * @param string $wordregex Word selection regular expression
 * @param int    $testtype  Test type
 *
 * @return (int|mixed|string)[] Next word formatted as an array.
 *
 * @psalm-return array{word_id: 0|mixed, solution?: string, word_text: string, group: string}
 */
function get_word_test_ajax($testsql, $word_mode, $lgid, $wordregex, $testtype): array
{
    $word_record = do_test_get_word($testsql);
    if (empty($word_record)) {
        $output = array(
            "word_id" => 0,
            "word_text" => '',
            "group" => ''
        );
        return $output;
    }
    if ($word_mode) {
        // Word alone
        $sent = "{" . $word_record['WoText'] . "}";
    } else {
        // Word surrounded by sentence
        list($sent, $_) = do_test_test_sentence(
            $word_record['WoID'],
            $lgid,
            $word_record['WoTextLC']
        );
        if ($sent === null) {
            $sent = "{" . $word_record['WoText'] . "}";
        }
    }
    list($html_sentence, $save) = do_test_get_term_test(
        $word_record,
        $sent,
        $testtype,
        $word_mode,
        $wordregex
    );
    $solution = get_test_solution($testtype, $word_record, $word_mode, $save);

    return array(
        "word_id" => $word_record['WoID'],
        "solution" => $solution,
        "word_text" => $save,
        "group" => $html_sentence
    );
}


/**
 * Return the next word to test.
 *
 * @param array $get_req Array with the fields {
 *                       test_key: string, selection: string, word_mode: bool,
 *                       lg_id: int, word_regex: string, type: int
 *                       }
 *
 * @return array Next word formatted as JSON.
 */
function word_test_ajax($get_req): array
{
    $test_sql = do_test_test_get_projection(
        $get_req['test_key'],
        $get_req['selection']
    );
    return get_word_test_ajax(
        $test_sql,
        filter_var($get_req['word_mode'], FILTER_VALIDATE_BOOLEAN),
        $get_req['lg_id'],
        $get_req['word_regex'],
        $get_req['type']
    );
}

/**
 * Return the number of reviews for tomorrow by using the suplied query.
 *
 * @param array $get_req Array with the fields "test_key" and "selection"
 *
 * @return array JSON-encoded result
 *
 * @psalm-return array{count: int}
 */
function tomorrow_test_count($get_req): array
{
    $test_sql = do_test_test_get_projection(
        $get_req['test_key'],
        $get_req['selection']
    );
    $output = array(
        "count" => do_test_get_tomorrow_tests_count($test_sql)
    );
    return $output;
}


/**
 * Get the file path using theme.
 *
 * @param array $get_req Get request with field "path", relative filepath using theme.
 *
 * @return array JSON-encoded result
 *
 * @psalm-return array{theme_path: string}
 */
function get_theme_path($get_req): array
{
    return array("theme_path" => get_file_path($get_req['path']));
}

/**
 * Return statistics about a group of text.
 *
 * @param array $get_req Get request with field "texts_id", texts ID.
 */
function get_texts_statistics($get_req): array
{
    return return_textwordcount($get_req["texts_id"]);
}

/**
 * Sentences containing an input word.
 *
 * @param array $get_req Get request with fields "lg_id", "word_lc" and "word_id".
 */
function sentences_with_registred_term($get_req): array
{
    return sentences_with_word(
        (int) $get_req["lg_id"],
        $get_req["word_lc"],
        (int) $get_req["word_id"]
    );
}

/**
 * Return the example sentences containing an input word.
 *
 * @param array $get_req Get request with fields "lg_id" and "advanced_search" (optional).
 */
function sentences_with_new_term($get_req): array
{
    $advanced = null;
    if (array_key_exists("advanced_search", $get_req)) {
        $advanced = -1;
    }
    return sentences_with_word(
        (int) $get_req["lg_id"],
        $get_req["word_lc"],
        $advanced
    );
}

/**
 * Get terms similar to a given term.
 *
 * @param array $get_req Get request with fields "lg_id" and "term".
 *
 * @return array Similar terms in HTML format.
 *
 * @psalm-return array{similar_terms: string}
 */
function similar_terms($get_req): array
{
    return array("similar_terms" => print_similar_terms(
        (int)$get_req["lg_id"],
        (string) $get_req["term"]
    ));
}

/**
 * Return the list of imported terms.
 *
 * @param array $get_req Get request with fields "last_update", "page" and "count".
 *
 * @return array
 */
function imported_terms($get_req)
{
    return imported_terms_list(
        $get_req["last_update"],
        $get_req["page"],
        $get_req["count"]
    );
}



/**
 * Translations for a term to choose an annotation.
 *
 * @param array $get_req Get request with fields "text_id" and "term_lc".
 */
function term_translations($get_req): array
{
    return \Lwt\Ajax\Improved_Text\get_term_translations(
        (string)$get_req["term_lc"],
        (int)$get_req["text_id"]
    );
}


/**
 * Error message when the provided action_type does not match anything known.
 *
 * @param array $post_req      GET request used
 * @param bool  $action_exists Set to true if the action is recognized but not
 *                             the action_type
 *
 * @return array JSON-encoded error message.
 *
 * @psalm-return array{error: string}
 */
function unknown_get_action_type($get_req, $action_exists = false): array
{
    if ($action_exists) {
        $message = 'action_type with value "' . $get_req["action_type"] .
        '" with action "' . $get_req["action"] . '" does not exist!';
    } else {
        $message = 'action_type with value "' . $get_req["action_type"] .
        '" with default action (' . $get_req["action"] . ') does not exist';
    }
    return array("error" => $message);
}

// --------------------------------- POST REQUESTS ---------------------

/**
 * Save a setting to the database.
 *
 * @param array $post_req Array with the fields "key" (setting name) and "value"
 *
 * @return string[] Setting save status
 *
 * @psalm-return array{error?: string, message?: string}
 */
function save_setting($post_req): array
{
    $status = saveSetting($post_req['key'], $post_req['value']);
    $raw_answer = array();
    if (str_starts_with($status, "OK: ")) {
        $raw_answer["message"] = substr($status, 4);
    } else {
        $raw_answer["error"] = $status;
    }
    return $raw_answer;
}

/**
 * Save the annotation for a term.
 *
 * @param array $post_req Post request with keys "text_id", "elem" and "data".
 *
 * @return string[] JSON-encoded result
 *
 * @psalm-return array{save_impr_text?: string, error?: string}
 */
function set_annotation($post_req): array
{
    $result = save_impr_text(
        (int)$post_req["text_id"],
        $post_req['elem'],
        json_decode($post_req['data'])
    );
    $raw_answer = array();
    if (array_key_exists("error", $result)) {
        $raw_answer["error"] = $result["error"];
    } else {
        $raw_answer["save_impr_text"] = $result["success"];
    }
    return $raw_answer;
}

/**
 * Set audio position.
 *
 * @param array $post_req Array with the fields "text_id" (int) and "position"
 *
 * @return string[] Success message
 *
 * @psalm-return array{audio: 'Audio position set'}
 */
function set_audio_position($post_req): array
{
    save_audio_position(
        (int)$post_req["text_id"],
        (int)$post_req["position"]
    );
    return array(
        "audio" => "Audio position set"
    );
}

/**
 * Set text reading position.
 *
 * @param array $post_req Array with the fields "text_id" (int) and "position"
 *
 * @return string[] Success message
 *
 * @psalm-return array{text: 'Reading position set'}
 */
function set_text_position($post_req): array
{
    save_text_position(
        (int)$post_req["text_id"],
        (int)$post_req["position"]
    );
    return array("text" => "Reading position set");
}


/**
 * Change the status of a term by one unit.
 *
 * @param array $post_req Array with the fields "term_id" (int) and "status_up" (1 or 0)
 *
 * @return string[] Status message
 *
 * @psalm-return array{increment?: string, error?: ''}
 */
function increment_term_status($post_req): array
{
    $result = ajax_increment_term_status(
        (int)$post_req['term_id'],
        (bool)$post_req['status_up']
    );
    $raw_answer = array();
    if ($result == '') {
        $raw_answer["error"] = '';
    } else {
        $raw_answer["increment"] = $result;
    }
    return $raw_answer;
}


/**
 * Set the status of a term.
 *
 * @param array $post_req Array with the fields "term_id" (int) and "status" (0-5|98|99)
 *
 * @return (int|string)[]
 *
 * @psalm-return array{error?: string, set?: int}
 */
function set_term_status($post_req): array
{
    $result = set_word_status((int)$post_req['term_id'], (int)$post_req['status']);
    $raw_answer = array();
    if (is_numeric($result)) {
        $raw_answer["set"] = (int)$result;
    } else {
        $raw_answer["error"] = $result;
    }
    return $raw_answer;
}


/**
 * Edit the translation of an existing term.
 *
 * @param array $post_req Array with the fields "term_id" (int) and "translation".
 *
 * @return string[] Term in lower case, or "" if term does not exist
 *
 * @psalm-return array{update?: string, error?: string}
 */
function update_translation($post_req): array
{
    $result = do_ajax_check_update_translation(
        (int)$post_req['term_id'],
        trim($post_req['translation'])
    );
    $raw_answer = array();
    if (str_starts_with($result, "Error")) {
        $raw_answer["error"] = $result;
    } else {
        $raw_answer["update"] = $result;
    }
    return $raw_answer;
}

/**
 * Create the translation for a new term.
 *
 * @param array $post_req Array with the fields "term_text", "lg_id" (int) and "translation".
 *
 * @return (int|string)[] Error message in case of failure, lowercase term otherwise
 *
 * @psalm-return array{error?: string, add?: string, term_id?: mixed, term_lc?: mixed}
 */
function add_translation($post_req): array
{
    $text = trim($post_req['term_text']);
    $result = add_new_term_transl(
        $text,
        (int)$post_req['lg_id'],
        trim($post_req['translation'])
    );
    $raw_answer = array();
    if (is_array($result)) {
        $raw_answer["term_id"] = (int) $result[0];
        $raw_answer["term_lc"] = (string) $result[1];
    } elseif ($result == mb_strtolower($text, 'UTF-8')) {
        $raw_answer["add"] = $result;
    } else {
        $raw_answer["error"] = $result;
    }
    return $raw_answer;
}

/**
 * Notify of an error on POST method.
 *
 * @param array $post_req      POST request used
 * @param bool  $action_exists Set to true if the action is recognized but not
 *                             the action_type
 *
 * @return string[] JSON-encoded error message
 *
 * @psalm-return array{error: string}
 */
function unknown_post_action_type($post_req, $action_exists = false): array
{
    if ($action_exists) {
        $message = 'action_type with value "' . $post_req["action_type"] .
        '" with action "' . $post_req["action"] . '" does not exist!';
    } else {
        $message = 'action_type with value "' . $post_req["action_type"] .
        '" with default action (' . $post_req["action"] . ') does not exist';
    }
    return array("error" => $message);
}

/**
 * Main handler for any provided request, while answer the result.
 *
 * @param string     $method     Method name (e.g. 'GET' or 'POST')
 * @param string     $requestURI The URI being requested.
 * @param array|null $post_param Post arguments, usually equal to $_POST
 *
 * @return never
 */
function request_handler($method, $requestUri, $post_param)
{
    // Extract requested endpoint from URI
    $req_endpoint = endpoint_exits($method, $requestUri);
    $endpoint_fragments = preg_split("/\//", $req_endpoint);

    // Process endpoint request
    if ($method === 'GET') {
        // Handle GET request for each endpoint
        $uri_query = parse_url($requestUri, PHP_URL_QUERY);
        if ($uri_query == null) {
            $req_param = array();
        } else {
            parse_str($uri_query, $req_param);
        }
        switch ($endpoint_fragments[0]) {
        case 'languages':
            if (ctype_digit($endpoint_fragments[1])) {
                if ($endpoint_fragments[2] == 'reading-configuration') {
                    $req_param['lang_id'] = (int) $endpoint_fragments[1];
                    $answer = readingConfiguration($req_param);
                    send_response(200, $answer);
                } else {
                    send_response(
                        404,
                        ['error' => 'Expected "reading-configuration", Got ' .
                        $endpoint_fragments[2]]
                    );
                }
            } else {
                send_response(
                    404,
                    ['error' => 'Expected Language ID, found ' .
                    $endpoint_fragments[1]]
                );
            }
            break;
        case 'media-files':
            $answer = media_files($req_param);
            send_response(200, $answer);
            break;
        case 'phonetic-reading':
            $answer = get_phonetic_reading($req_param);
            send_response(200, $answer);
            break;
        case 'review':
            switch ($endpoint_fragments[1]) {
            case 'next-word':
                $answer = word_test_ajax($req_param);
                send_response(200, $answer);
                break;
            case 'tomorrow-count':
                $answer = tomorrow_test_count($req_param);
                send_response(200, $answer);
                break;
            default:
                send_response(
                    404,
                    ['error' => 'Endpoint Not Found' .
                    $endpoint_fragments[1]]
                );
            }
            break;
        case 'sentences-with-term':
            if (ctype_digit($endpoint_fragments[1])) {
                $req_param['word_id'] = (int) $endpoint_fragments[1];
                $answer = sentences_with_registred_term($req_param);
            } else {
                $answer = sentences_with_new_term($req_param);
            }
            send_response(200, $answer);
            break;
        case 'similar-terms':
            $answer = similar_terms($req_param);
            send_response(200, $answer);
            break;
        case 'settings':
            switch ($endpoint_fragments[1]) {
            case 'theme-path':
                $answer = get_theme_path($req_param);
                send_response(200, $answer);
                break;
            default:
                send_response(
                    404,
                    ['error' => 'Endpoint Not Found: ' .
                    $endpoint_fragments[1]]
                );
            }
            break;
        case 'terms':
            if ($endpoint_fragments[1] == "imported") {
                $answer = imported_terms($req_param);
                send_response(200, $answer);
            } elseif (ctype_digit($endpoint_fragments[1])) {
                if ($endpoint_fragments[2] == 'translations') {
                    $req_param['term_id'] = $endpoint_fragments[1];
                    $answer = term_translations($req_param);
                    send_response(200, $answer);
                } else {
                    send_response(
                        404,
                        ['error' => 'Expected "translation", Got ' .
                        $endpoint_fragments[2]]
                    );
                }
            } else {
                send_response(
                    404,
                    ['error' => 'Endpoint Not Found' .
                    $endpoint_fragments[1]]
                );
            }
            break;
        case 'texts':
            if ($endpoint_fragments[1] == 'statistics') {
                $answer = get_texts_statistics($req_param);
                send_response(200, $answer);
            } else {
                send_response(
                    404,
                    ['error' => 'Expected "statistics", Got ' .
                    $endpoint_fragments[1]]
                );
            }
            break;
        case 'version':
            $answer = rest_api_version($req_param);
            send_response(200, $answer);
            break;
                // Add more GET handlers for other endpoints
        default:
            send_response(
                404,
                ['error' => 'Endpoint Not Found: ' .
                $endpoint_fragments[0]]
            );
        }
    } elseif ($method === 'POST') {
        // Handle POST request for each endpoint
        switch ($endpoint_fragments[0]) {
        case 'settings':
            $answer = save_setting($post_param);
            send_response(200, $answer);
            break;
        case 'texts':
            if (!ctype_digit($endpoint_fragments[1])) {
                send_response(
                    404,
                    ['error' => 'Text ID (Integer) Expected, Got ' .
                    $endpoint_fragments[1]]
                );
            }
            $post_param["text_id"] = (int) $endpoint_fragments[1];
            switch ($endpoint_fragments[2]) {
            case 'annotation':
                $answer = set_annotation($post_param);
                send_response(200, $answer);
                break;
            case 'audio-position':
                $answer = set_audio_position($post_param);
                send_response(200, $answer);
                break;
            case 'reading-position':
                $answer = set_text_position($post_param);
                send_response(200, $answer);
                break;
            default:
                send_response(
                    404,
                    ['error' => 'Endpoint Not Found: ' .
                    $endpoint_fragments[2]]
                );
            }
            break;
        case 'terms':
            if (ctype_digit($endpoint_fragments[1])) {
                $post_param['term_id'] = (int) $endpoint_fragments[1];
                if ($endpoint_fragments[2] == "status") {
                    if ($endpoint_fragments[3] == 'down') {
                        $post_param['status_up'] = 0;
                        $answer = increment_term_status($post_param);
                        send_response(200, $answer);
                    } elseif ($endpoint_fragments[3] == 'up') {
                        $post_param['status_up'] = 1;
                        $answer = increment_term_status($post_param);
                        send_response(200, $answer);
                    } elseif (ctype_digit($endpoint_fragments[3])) {
                        $post_param['status'] = (int) $endpoint_fragments[3];
                        $answer = set_term_status($post_param);
                        send_response(200, $answer);
                    } else {
                        send_response(
                            404,
                            ['error' => 'Endpoint Not Found: ' .
                            $endpoint_fragments[3]]
                        );
                    }
                } elseif ($endpoint_fragments[2] == 'translations') {
                    $answer = update_translation($post_param);
                    send_response(200, $answer);
                } else {
                    send_response(
                        404,
                        [
                            'error' =>
                            '"status" or "translations"' .
                            ' Expected, Got ' . $endpoint_fragments[2]
                        ]
                    );
                }
            } elseif ($endpoint_fragments[1] == 'new') {
                $answer = add_translation($post_param);
                send_response(200, $answer);
            } else {
                send_response(
                    404,
                    [
                        'error' =>
                        'Term ID (Integer) or "new" Expected,' .
                        ' Got ' . $endpoint_fragments[1]
                    ]
                );
            }
            break;
        case 'feeds':
            if (ctype_digit($endpoint_fragments[1])) {
                // Load feed: POST /feeds/{id}/load
                if ($endpoint_fragments[2] == 'load') {
                    $answer = \Lwt\Ajax\Feed\load_feed(
                        $post_param['name'],
                        (int)$endpoint_fragments[1],
                        $post_param['source_uri'],
                        $post_param['options']
                    );
                    send_response(200, $answer);
                } else {
                    send_response(
                        404,
                        ['error' => 'Expected "load", Got ' . $endpoint_fragments[2]]
                    );
                }
            } else {
                send_response(
                    404,
                    ['error' => 'Feed ID (Integer) Expected, Got ' . $endpoint_fragments[1]]
                );
            }
            break;
        default:
            send_response(
                404,
                ['error' => 'Endpoint Not Found On POST: ' .
                $endpoint_fragments[0]]
            );
        }
    }
}


// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_response(405, ['error' => 'Method Not Allowed']);
} else {
    request_handler(
        $_SERVER['REQUEST_METHOD'],
        $_SERVER['REQUEST_URI'],
        $_POST
    );
}

?>
