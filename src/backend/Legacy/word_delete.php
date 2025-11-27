<?php

/**
 * \file
 * \brief Delete a word
 *
 * Call: delete_word.php?wid=[wordid]&tid=[textid]
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

require_once 'Core/Bootstrap/db_bootstrap.php';
require_once 'Core/UI/ui_helpers.php';
require_once 'Core/Text/text_helpers.php';
require_once 'Core/Http/param_helpers.php';
require_once 'Core/Word/word_status.php';

use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Database\Maintenance;

/**
 * Return the term corresponding to the ID.
 *
 * @param string $wid ID of the word
 *
 * @return string A word
 *
 * @global string $tbpref
 */
function get_term($wid)
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $term = Connection::fetchValue(
        "SELECT WoText AS value
        FROM " . $tbpref . "words
        WHERE WoID = " . $wid
    );
    return (string)$term;
}

/**
 * Edit the word from the database.
 *
 * @param string $wid ID of the word to delete
 *
 * @return int|numeric-string Some edit message, number of affected rows or error message
 *
 * @global string $tbpref
 *
 * @psalm-return int<-1, max>|numeric-string
 */
function delete_word_from_database($wid): int|string
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $m1 = Connection::execute(
        'DELETE FROM ' . $tbpref . 'words
        WHERE WoID = ' . $wid,
        ''
    );
    Maintenance::adjustAutoIncrement('words', 'WoID');
    Connection::execute(
        "UPDATE  " . $tbpref . "textitems2
        SET Ti2WoID  = 0
        WHERE Ti2WordCount=1 AND Ti2WoID  = " . $wid,
        ''
    );
    return $m1;
}

/**
 * Do the JavaScript action for changing display of the word.
 *
 * @param string $wid ID of the word to delete
 * @param int    $tid Text ID
 *
 * @return void
 */
function delete_word_javascript($wid, $tid)
{
    ?>
<script type="text/javascript">
    //<![CDATA[

    /**
     * Make the visual effects to delete a word from the page.
     *
     * @param {int} wid Word ID
     *
     * @returns {undefined}
     */
    function delete_word(wid) {
        const context = window.parent.document;
        const elem = $('.word' + wid, context);
        let title = "";
        if (!window.parent.document.LWT_DATA.settings.jQuery_tooltip) {
            const ann = elem.attr('data_ann');
            title = make_tooltip(
                <?php echo Escaping::prepareTextdataJs(get_term($wid)); ?>,
                ann + (ann ? ' / ' : '') + elem.attr('data_trans'),
                elem.attr('data_rom'),
                elem.attr('data_status')
            );
        }
        elem
        .removeClass('status99 status98 status1 status2 status3 status4 status5 word' + wid)
        .addClass('status0')
        .attr('data_status', '0')
        .attr('data_trans', '')
        .attr('data_rom', '')
        .attr('data_wid', '')
        .attr('title', title)
        .removeAttr("data_img");
        $('#learnstatus', context).html('<?php echo addslashes(todo_words_content($tid)); ?>');

        cleanupRightFrames();
    }

    delete_word(<?php echo $wid; ?>);
    //]]>
</script>
    <?php
}

/**
 * Make the HTML content of the page when deleting a word.
 *
 * @param int    $tid  Text ID
 * @param string $wid  ID of the word to delete
 * @param string $term The deleted word
 * @param string $m1   Some edit message, number of affected rows or error message
 *
 * @return void
 */
function delete_word_page_content($tid, $wid, $term, $m1)
{
    pagestart("Term: " . $term, false);
    echo "<p>OK, term deleted, now unknown (" . $m1 . ").</p>";
    delete_word_javascript($wid, $tid);
    pageend();
}

/**
 * Complete workflow for deleting a word.
 * It edits the database, show the success message
 * and do JavaScript action to change its display.
 *
 * @param int    $textid ID of the affected text
 * @param string $wordid ID of the word to delete
 *
 * @return void
 *
 * @since 2.0.4-fork
 */
function do_delete_word($textid, $wordid)
{
    $term = get_term($wordid);
    $m1 = delete_word_from_database($wordid);
    delete_word_page_content($textid, $wordid, $term, $m1);
}

if (getreq('tid') != '' && getreq('wid') != '') {
    do_delete_word((int)getreq('tid'), getreq('wid'));
}

?>
