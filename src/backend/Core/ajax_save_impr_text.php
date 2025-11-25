<?php
/**
 * \file
 * \brief Save Improved Annotation
 *
 * Call: inc/ajax_save_impr_text.php
 *
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-ajax-save-impr-text.html
 * @since   1.5.0
 */

require_once __DIR__ . '/session_utility.php';

/**
 * Save data from printed text.
 *
 * @param int    $textid Text ID
 * @param int    $line   Line number to save
 * @param string $val    Proposed new annotation for the term
 *
 * @return string Error message, or "OK" if success.
 *
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
 * Save a printed text.
 *
 * @param int    $textid Text ID
 * @param string $elem   Element to edit
 * @param mixed  $data   JSON data
 *
 * @return string Success string
 */
function do_ajax_save_impr_text($textid, $elem, $data): string
{
    chdir('..');

    $val = $data->{$elem};
    if (substr($elem, 0, 2) == "rg" && $val == "") {
        $val = $data->{'tx' . substr($elem, 2)};
    }
    $line = (int)substr($elem, 2);
    return save_impr_text_data($textid, $line, $val);
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

if (isset($_POST['id']) && isset($_POST['elem']) && isset($_POST['data'])) {
    echo do_ajax_save_impr_text(
        (int)$_POST['id'],
        $_POST['elem'],
        json_decode($_POST['data'])
    );
}

?>
