<?php

/**
 * \file
 * \brief Annotation management functions.
 *
 * This file contains functions for creating, saving, and managing
 * text annotations for the print/impr view.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-session-utility.html
 * @since    3.0.0 Split from text_helpers.php
 */

/**
 * Uses provided annotations, and annotations from database to update annotations.
 *
 * @param int    $textid Id of the text on which to update annotations
 * @param string $oldann Old annotations
 *
 * @return string Updated annotations for this text.
 */
function recreate_save_ann($textid, $oldann): string
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    // Get the translations from $oldann:
    $oldtrans = array();
    $olditems = preg_split('/[\n]/u', $oldann);
    foreach ($olditems as $olditem) {
        $oldvals = preg_split('/[\t]/u', $olditem);
        if (count($oldvals) >= 2 && (int)$oldvals[0] > -1) {
            $trans = '';
            if (count($oldvals) > 3) {
                $trans = $oldvals[3];
            }
            $oldtrans[$oldvals[0] . "\t" . $oldvals[1]] = $trans;
        }
    }
    // Reset the translations from $oldann in $newann and rebuild in $ann:
    $newann = create_ann($textid);
    $newitems = preg_split('/[\n]/u', $newann);
    $ann = '';
    foreach ($newitems as $newitem) {
        $newvals = preg_split('/[\t]/u', $newitem);
        if ((int)$newvals[0] > -1) {
            $key = $newvals[0] . "\t";
            if (isset($newvals[1])) {
                $key .= $newvals[1];
            }
            if (isset($oldtrans[$key])) {
                $newvals[3] = $oldtrans[$key];
            }
            $item = implode("\t", $newvals);
        } else {
            $item = $newitem;
        }
        $ann .= $item . "\n";
    }
    runsql(
        "UPDATE {$tbpref}texts
        SET TxAnnotatedText = " . convert_string_to_sqlsyntax($ann) . "
        WHERE TxID = $textid",
        ""
    );
    return (string)get_first_value(
        "SELECT TxAnnotatedText AS value
        FROM {$tbpref}texts
        where TxID = $textid"
    );
}

/**
 * Create new annotations for a text.
 *
 * @param int $textid Id of the text to create annotations for
 *
 * @return string Annotations for the text
 *
 * @since 2.9.0 Annotations "position" change, they are now equal to Ti2Order
 * it was shifted by one index before.
 */
function create_ann($textid): string
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $ann = '';
    $sql =
    "SELECT
    CASE WHEN Ti2WordCount>0 THEN Ti2WordCount ELSE 1 END AS Code,
    CASE WHEN CHAR_LENGTH(Ti2Text)>0 THEN Ti2Text ELSE WoText END AS TiText,
    Ti2Order,
    CASE WHEN Ti2WordCount > 0 THEN 0 ELSE 1 END AS TiIsNotWord,
    WoID, WoTranslation
    FROM (
        {$tbpref}textitems2
        LEFT JOIN {$tbpref}words
        ON Ti2WoID = WoID AND Ti2LgID = WoLgID
    )
    WHERE Ti2TxID = $textid
    ORDER BY Ti2Order ASC, Ti2WordCount DESC";
    $until = 0;
    $res = do_mysqli_query($sql);
    // For each term (includes blanks)
    while ($record = mysqli_fetch_assoc($res)) {
        $actcode = (int)$record['Code'];
        $order = (int)$record['Ti2Order'];
        if ($order <= $until) {
            continue;
        }
        $savenonterm = '';
        $saveterm = '';
        $savetrans = '';
        $savewordid = '';
        $until = $order;
        if ($record['TiIsNotWord'] != 0) {
            $savenonterm = $record['TiText'];
        } else {
            $until = $order + 2 * ($actcode - 1);
            $saveterm = $record['TiText'];
            if (isset($record['WoID'])) {
                $savetrans = $record['WoTranslation'];
                $savewordid = $record['WoID'];
            }
        }
        // Append the annotation
        $ann .= process_term(
            $savenonterm,
            $saveterm,
            $savetrans,
            $savewordid,
            $order
        );
    }
    mysqli_free_result($res);
    return $ann;
}

/**
 * Create and save annotations for a text.
 *
 * @param int $textid Text ID
 *
 * @return string Annotations for the text
 */
function create_save_ann($textid): string
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $ann = create_ann($textid);
    runsql(
        'update ' . $tbpref . 'texts set ' .
        'TxAnnotatedText = ' . convert_string_to_sqlsyntax($ann) . '
        where TxID = ' . $textid,
        ""
    );
    return (string)get_first_value(
        "select TxAnnotatedText as value
        from " . $tbpref . "texts
        where TxID = " . $textid
    );
}

/**
 * Process a term for annotation output.
 *
 * @param string $nonterm Non-term text (punctuation, spaces)
 * @param string $term    Term text
 * @param string $trans   Translation
 * @param string $wordid  Word ID
 * @param int    $line    Line/order number
 *
 * @return string Formatted annotation line
 */
function process_term($nonterm, $term, $trans, $wordid, $line): string
{
    $r = '';
    if ($nonterm != '') {
        $r = "-1\t$nonterm\n";
    }
    if ($term != '') {
        $r .=  "$line\t$term\t" . trim($wordid) . "\t" .
        get_first_translation($trans) . "\n";
    }
    return $r;
}

/**
 * Get the first translation from a translation string.
 *
 * @param string $trans Full translation string (may contain separators)
 *
 * @return string First translation only
 */
function get_first_translation($trans): string
{
    $arr = preg_split('/[' . get_sepas()  . ']/u', $trans);
    if (count($arr) < 1) {
        return '';
    }
    $r = trim($arr[0]);
    if ($r == '*') {
        $r = "";
    }
    return $r;
}

/**
 * Get a link to the annotated text if it exists.
 *
 * @param int $textid Text ID
 *
 * @return string HTML link or empty string
 */
function get_annotation_link($textid): string
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    if (get_first_value('select length(TxAnnotatedText) as value from ' . $tbpref . 'texts where TxID=' . $textid) > 0) {
        return ' &nbsp;<a href="print_impr_text.php?text=' . $textid .
        '" target="_top"><img src="/assets/icons/tick.png" title="Annotated Text" alt="Annotated Text" /></a>';
    } else {
        return '';
    }
}

/**
 * Like trim, but in place (modify variable)
 *
 * @param string $value Value to be trimmed
 */
function trim_value(&$value): void
{
    $value = trim($value);
}

/**
 * Convert annotations in a JSON format.
 *
 * @param string $ann Annotations.
 *
 * @return string A JSON-encoded version of the annotations
 */
function annotation_to_json($ann): string|false
{
    if ($ann == '') {
        return "{}";
    }
    $arr = array();
    $items = preg_split('/[\n]/u', $ann);
    foreach ($items as $item) {
        $vals = preg_split('/[\t]/u', $item);
        if (count($vals) > 3 && $vals[0] >= 0 && $vals[2] > 0) {
            $arr[intval($vals[0]) - 1] = array($vals[1], $vals[2], $vals[3]);
        }
    }
    $json_data = json_encode($arr);
    if ($json_data === false) {
        my_die("Unable to format to JSON");
    }
    return $json_data;
}
