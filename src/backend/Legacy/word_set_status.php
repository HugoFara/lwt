<?php

/**
 * \file
 * \brief Change status of term while reading
 *
 * Call: set_word_status.php?...
 *      ... tid=[textid]&wid=[wordid]&status=1..5/98/99
 *
 * PHP version 8.1
 *
 * @category Helper_Frame
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   1.0.3
 */

require_once 'Core/session_utility.php';

/**
 * Get various data for the word corresponding to the ID.
 *
 * @param string $wid ID of the word
 *
 * @return array{0: string, 1: string, 2: string} The word in plain text,
 * his translation and his romanization
 *
 * @global string $tbpref
 */
function get_word_data($wid)
{
    global $tbpref;
    $sql = "SELECT WoText, WoTranslation, WoRomanization
    FROM {$tbpref}words WHERE WoID = $wid";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    if (!$record) {
        my_die("Word not found in set_word_status.php");
    }
    $word = $record['WoText'];
    $trans = repl_tab_nl($record['WoTranslation']) . getWordTagList($wid, ' ', 1, 0);
    $roman = $record['WoRomanization'];
    mysqli_free_result($res);
    return array($word, $trans, $roman);
}


/**
 * Sent an AJAX request to change a word satus.
 *
 * @param string $wid    ID of the word status to change
 * @param string $status New status to set
 */
function set_word_status_ajax($wid, $status): void
{
    ?>
<script type="text/javascript">
    const wordid = parseInt(<?php echo $wid; ?>, 10);
    const status = parseInt(<?php echo $status; ?>, 10);
    $.post(
        'api.php/v1/terms/' + wordid + '/status/' + status,
        {},
        function (data) {
            if (data == "" || "error" in data) {
                word_update_error();
            } else {
                apply_word_update(wordid, status);
            }
        },
        "json"
    );
</script>
    <?php
}

/**
 * Edit the term status in the database.
 *
 * @param string $wid    ID of the word to update
 * @param string $status New status to set
 *
 * @return string Some edit message, number of affected rows or error message
 *
 * @global string $tbpref
 */
function set_word_status_database($wid, $status)
{
    global $tbpref;
    $m1 = runsql(
        "UPDATE {$tbpref}words
        SET WoStatus = $status, WoStatusChanged = NOW()," .
        make_score_random_insert_update('u') . "
        WHERE WoID = $wid",
        'Status changed'
    );
    return $m1;
}

/**
 * Do the JavaScript action for changing display of the word.
 *
 * @param string $tid    Text ID
 * @param string $wid    ID of the word that changed status, unnused
 * @param string $status New status, unnused
 * @param string $word   Word in plain text
 * @param string $trans  Translation of the word
 * @param string $roman  Romanization of the word
 *
 * @return void
 */
function set_word_status_javascript($tid, $wid, $status, $word, $trans, $roman)
{
    $todo_content = todo_words_content((int) $tid);
    ?>
<script type="text/javascript">
    function word_update_error() {
        $('#status_change_log').text("Word status update failed!");
        cleanupRightFrames();
    }

    function update_word_display(wid, status, word, trans, roman, tid) {
        let context = window.parent.document.getElementById('frame-l');
        let contexth = window.parent.document.getElementById('frame-h');
        let title = '';
        if (!window.parent.LWT_DATA.settings.jQuery_tooltip) {
            title = make_tooltip(word, trans, roman, status);
        }
        $('.word' + wid, context)
        .removeClass('status98 status99 status1 status2 status3 status4 status5')
        .addClass('status' + status)
        .attr('data_status', status)
        .attr('title', title);
        $('#learnstatus', contexth).html(tid);
    }

    function apply_word_update(wid, status) {
        $('#status_change_log').text('Term status changed to ' + status);
        update_word_display(
            wid, status,
            <?php echo json_encode($word); ?>,
            <?php echo json_encode($trans); ?>,
            <?php echo json_encode($roman); ?>,
            <?php echo json_encode($todo_content); ?>
        );
        cleanupRightFrames();
    }
</script>
    <?php
}

/**
 * Echo the HTML content of the page.
 *
 * @param string $tid    Text ID
 * @param string $wid    ID of the word that changed status
 * @param string $status New status
 * @param string $word   Word in plain text
 * @param string $trans  Translation of the word
 * @param string $roman  Romanization of the word
 *
 * @return void
 */
function set_word_status_display_page($tid, $wid, $status, $word, $trans, $roman)
{
    pagestart("Term: $word", false);
    echo "<p id='status_change_log'>Term status updating...</p>";
    set_word_status_javascript($tid, $wid, $status, $word, $trans, $roman);
    pageend();

}


/**
 * Complete workflow for updating a word.
 * It edits the database, show the success message
 * and do JavaScript action to change its display.
 *
 * @param string $textid ID of the affected text
 * @param string $wordid ID of the word to update
 * @param string $status New status for this word
 *
 * @return void
 *
 * @since 2.0.4-fork
 */
function do_set_word_status($textid, $wordid, $status)
{
    list($word, $trans, $roman) = get_word_data($wordid);
    set_word_status_display_page($textid, $wordid, $status, $word, $trans, $roman);
    set_word_status_ajax($wordid, $status);
}


if (getreq('tid') != '' && getreq('wid') != '' && getreq('status') != '') {
    do_set_word_status(getreq('tid'), getreq('wid'), getreq('status'));
}

?>
