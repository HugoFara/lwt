<?php

/**
 * \file
 * \brief Setting all unknown words to Well Known (99)
 *
 * Call: all_words_wellknown.php?text=[textid]
 *                              (mark all words as well-known)
 *       all_words_wellknown.php?text=[textid]&status=[statusint]
 *                              (mark with a specific status, normally 98 or 99)
 *
 * PHP version 8.1
 *
 * @category Helper_Frame
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/all-words-wellknown.html
 * @since   1.0.3
 */

require_once 'Core/session_utility.php';

/**
 * Make the SQL query for all words in the text.
 *
 * @param int $txid Text id
 *
 * @return mysqli_result|true SQL query.
 */
function all_words_wellknown_get_words($txid)
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $sql = "SELECT DISTINCT Ti2Text, LOWER(Ti2Text) AS Ti2TextLC
    FROM (
        {$tbpref}textitems2
        LEFT JOIN {$tbpref}words
        ON LOWER(Ti2Text) = WoTextLC AND Ti2LgID = WoLgID
    )
    WHERE WoID IS NULL AND Ti2WordCount = 1 AND Ti2TxID = $txid
    ORDER BY Ti2Order";
    return do_mysqli_query($sql);
}

/**
 * For each word, add the word to the database.
 *
 * @param int    $status New word status
 * @param string $term   Word to mark
 * @param string $termlc Same as $term, but in lowercase.
 * @param int    $langid Language ID
 *
 * @return array [int, string] Number of rows edited and a javascript query.
 *
 * @since 2.5.3-fork Do not crash when echoing an error
 * @since 2.5.3-fork Do not crash when a word is already registred to the database
 *
 * @psalm-return list{int, string}
 */
function all_words_wellknown_process_word($status, $term, $termlc, $langid): array
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $wid = get_first_value(
        "SELECT WoID AS value FROM words
        WHERE WoTextLC = " . convert_string_to_sqlsyntax($termlc)
    );
    if ($wid !== null) {
        $rows = 0;
    } else {
        $message = runsql(
            "INSERT INTO {$tbpref}words (
                WoLgID, WoText, WoTextLC, WoStatus, WoStatusChanged,"
                . make_score_random_insert_update('iv') .
            ")
            VALUES(
                $langid, " .
                convert_string_to_sqlsyntax($term) . ", " .
                convert_string_to_sqlsyntax($termlc) . ", $status, NOW(), " .
                make_score_random_insert_update('id') .
            ")",
            ''
        );
        if (!is_numeric($message)) {
            my_die("ERROR: Could not modify words! Message: $message");
        }
        if ((int)$message == 0) {
            error_message_with_hide(
                "WARNING: No rows modified! Message: $message",
                false
            );
        }
        $rows = (int) $message;
        $wid = get_last_key();
    }
    $javascript = '';
    if (getSettingWithDefault('set-tooltip-mode') == 1 && $rows > 0) {
        $javascript .= "title = make_tooltip(" .
        prepare_textdata_js($term) . ", '*', '', '$status');";
    }
    $javascript .= "$('.TERM" . strToClassName($termlc) . "', context)
    .removeClass('status0')
    .addClass('status$status word$wid')
    .attr('data_status', '$status')
    .attr('data_wid', '$wid')
    .attr('title', title);";
    return array($rows, $javascript);
}

/**
 * Main processing loop to mark all words of a text with a new status.
 *
 * @param int $txid   Text ID
 * @param int $status New status to apply to all words.
 *
 * @return array [int, string] Number of edited words, and JavaScript query to change their display
 *
 * @since 2.5.3-fork Use 'let' instead of 'var' in returned JS
 *
 * @psalm-return list{int, string}
 */
function all_words_wellknown_main_loop($txid, $status): array
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $langid = get_first_value(
        "SELECT TxLgID AS value
        FROM {$tbpref}texts
        WHERE TxID = $txid"
    );
    $javascript = "let title='';";
    $count = 0;
    $res = all_words_wellknown_get_words($txid);
    while ($record = mysqli_fetch_assoc($res)) {
        list($modified_rows, $new_js) = all_words_wellknown_process_word(
            $status,
            $record['Ti2Text'],
            $record['Ti2TextLC'],
            $langid
        );
        $javascript .= $new_js;
        $count += $modified_rows;
    }
    mysqli_free_result($res);

    // Associate existing textitems.
    runsql(
        "UPDATE {$tbpref}words
        JOIN {$tbpref}textitems2
        ON Ti2WoID = 0 AND LOWER(Ti2Text) = WoTextLC AND Ti2LgID = WoLgID
        SET Ti2WoID = WoID",
        ''
    );

    return array($count, $javascript);
}

/**
 * Display the number of edited words.
 *
 * @param int $status New status
 * @param int $count  Number of edited words.
 *
 * @return void
 *
 * @since 2.5.3-fork Improved messages (more clear, and can handle singular/plural)
 */
function all_words_wellknown_count_terms($status, $count)
{
    $message = "<p>";
    if ($status == 98) {
        if ($count > 1) {
            $message .= "Ignored all $count words!";
        } elseif ($count == 1) {
            $message .= "Ignored 1 word.";
        } else {
            $message .= "No new word ignored!";
        }
    } else {
        if ($count > 1) {
            $message .= "You know all $count words well!";
        } elseif ($count == 1) {
            $message .= "1 new word added as known";
        } else {
            $message .= "No new known word added!";
        }
    }
    $message .= "</p>";
    echo $message;
}

/**
 * Execute JavaScript to change the display of all words.
 *
 * @param int    $txid       Text ID
 * @param string $javascript JavaScript-formatted string.
 *
 * @return void
 */
function all_words_wellknown_javascript($txid, $javascript)
{
    $content = todo_words_content($txid);
    ?>
<script type="text/javascript">
    //<![CDATA[
    const context = window.parent.document;
    <?php echo $javascript; ?>
    $('#learnstatus', context).html('<?php echo addslashes($content); ?>');
    window.parent.setTimeout(window.parent.cClick, 1000);
    //]]>
</script>
    <?php
}

/**
 * Make the main content of the page for all well-known words.
 *
 * @param int $txid   Text ID
 * @param int $status New status to apply to words.
 *
 * @return void
 */
function all_words_wellknown_content($txid, $status)
{
    list($count, $javascript) = all_words_wellknown_main_loop($txid, $status);
    all_words_wellknown_count_terms($status, $count);
    all_words_wellknown_javascript($txid, $javascript);
}

/**
 * Make a full HTML page for all well-known words.
 *
 * @param int $txid   Text ID
 * @param int $status New status to apply to words.
 *
 * @return void
 */
function all_words_wellknown_full($txid, $status)
{
    if ($status == 98) {
        pagestart("Setting all blue words to Ignore", false);
    } else {
        pagestart("Setting all blue words to Well-known", false);
    }
    all_words_wellknown_content($txid, $status);
    pageend();
}

if (isset($_REQUEST['text'])) {
    all_words_wellknown_full(
        (int) $_REQUEST['text'],
        isset($_REQUEST['stat']) ? (int) $_REQUEST['stat'] : 99
    );
}


?>
