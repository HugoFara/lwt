<?php
/**
 * \file
 * \brief Add a translation to term.
 *
 * Call: inc/ajax_add_term_transl.php
 *
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-ajax-add-term-transl.html
 * @since   1.5.0
 */

require_once __DIR__ . '/session_utility.php';

/**
 * Add the translation for a new term.
 *
 * @param string $text Associated text
 * @param int    $lang Language ID
 * @param string $data Translation
 *
 * @return (int|string)[]|string [new word ID, lowercase $text] if success, error message otherwise
 *
 * @global string $tbpref Database table prefix
 *
 * @since 2.9.0 Error messages are much more explicit
 * @since 2.9.0 Return an array
 *
 * @psalm-return list{int, string}|string
 */
function add_new_term_transl($text, $lang, $data): array|string
{
    global $tbpref;
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
 *
 * @global string $tbpref Database table prefix
 */
function edit_term_transl($wid, $new_trans)
{
    global $tbpref;
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
 *
 * @global string $tbpref
 */
function do_ajax_check_update_translation($wid, $new_trans)
{
    global $tbpref;
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

/**
 * Add or edit a term translation.
 *
 * @param int    $wid  Word ID
 * @param string $data Translation
 *
 * @return string Database alteration message
 *
 * @deprecated Deprecated in 2.9.0 in favor to the REST API.
 */
function do_ajax_add_term_transl($wid, $data)
{
    chdir('..');
    // Save data
    if ($wid == 0) {
        $status = add_new_term_transl(
            trim($_POST['text']), (int)$_POST['lang'], $data
        );
        if (is_array($status)) {
            $success = $status[1];
        } else {
            $success = $status;
        }
    } else {
        $success = do_ajax_check_update_translation($wid, $data);
    }
    return $success;
}

if (isset($_POST['id']) && isset($_POST['data'])) {
    echo do_ajax_add_term_transl((int)$_POST['id'], trim($_POST['data']));
}

?>
