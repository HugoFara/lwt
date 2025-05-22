<?php
/**
 * \file
 * \brief Change term status (Table Test).
 *
 * value-difference should be either 1 or -1.
 *
 * Call: inc/ajax_chg_term_status.php?id=[wordID]&data=[value-difference]
 *
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-ajax-chg-term-status.html
 * @since   1.5.4
 */

require_once __DIR__ . '/session_utility.php';


/**
 * Force a term to get a new status.
 *
 * @param string|int $wid    ID of the word to edit
 * @param string|int $status New status to set
 *
 * @return string Number of affected rows or error message
 *
 * @global string $tbpref
 */
function set_word_status($wid, $status)
{
    global $tbpref;
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
 *
 * @global string $tbpref Database table prefix
 */
function update_word_status($wid, $currstatus)
{
    global $tbpref;
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
 *
 * @global string $tbpref Database table prefix.
 *
 * @todo 2.9.0 Dirty PHP implementation, needs further refactoring
 */
function ajax_increment_term_status($wid, $up)
{
    global $tbpref;

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

/**
 * Do a word status change and print the result.
 *
 * @param int  $wid Word ID
 * @param bool $up  Should the status be incremeted or decremented
 *
 * @return void
 *
 * @global string $tbpref Database table prefix.
 *
 * @see ajax_increment_term_status Return values instead of printing.
 */
function do_ajax_chg_term_status($wid, $up)
{
    $result = ajax_increment_term_status($wid, $up);
    if ($result == null) {
        echo '';
    }
    echo $result;
}

if (getreq('id') != '' && getreq('data') != '') {
    // Deprecated way of accessing the request since 2.9.0!
    // Use the REST API with "action_type=regexp".
    do_ajax_chg_term_status((int)$_REQUEST['id'], (bool)$_REQUEST['data']);
}


?>