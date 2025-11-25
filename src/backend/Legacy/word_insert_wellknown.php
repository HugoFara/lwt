<?php

/**
 * \file
 * \brief Ignore single word (new term with status 99)
 *
 * Call: insert_word_wellknown.php?tid=[textid]&ord=[textpos]
 *
 * PHP version 8.1
 *
 * @category Helper_Frame
 * @package Lwt
 * @author LWT Project <lwt-project@hotmail.com>
 * @since  1.0.3
 */

require_once 'Core/session_utility.php';

/**
 * Return the word at a specific position in a text.
 *
 * @param string $textid  ID of the text
 * @param string $textpos Position of the word in the text.
 *
 * @return string A word
 *
 * @global string $tbpref
 */
function get_word($textid, $textpos): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $word = (string)get_first_value(
        "SELECT Ti2Text AS value
        FROM " . $tbpref . "textitems2
        WHERE Ti2WordCount = 1 AND Ti2TxID = " . $textid . " AND Ti2Order = " . $textpos
    );
    return $word;
}

/**
 * Edit the database to add the word.
 *
 * @param string $textif ID of the text
 * @param string $word   Word to add
 *
 * @return int Word ID
 *
 * @global string $tbpref
 */
function insert_word_wellknown_to_database($textid, $word)
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();

    $wordlc = mb_strtolower($word, 'UTF-8');

    $langid = get_first_value(
        "SELECT TxLgID AS value
        FROM " . $tbpref . "texts
        WHERE TxID = " . $textid
    );
    runsql(
        'INSERT INTO ' . $tbpref . 'words (
            WoLgID, WoText, WoTextLC, WoStatus, WoWordCount, WoStatusChanged,' .  make_score_random_insert_update('iv') . '
        ) values( ' .
            $langid . ', ' .
            convert_string_to_sqlsyntax($word) . ', ' .
            convert_string_to_sqlsyntax($wordlc) . ', 99, 1, NOW(), ' .
            make_score_random_insert_update('id') . '
        )',
        'Term added'
    );
    $wid = get_last_key();
    do_mysqli_query(
        "UPDATE  " . $tbpref . "textitems2
        SET Ti2WoID  = " . $wid . "
        WHERE Ti2LgID = " . $langid . " AND lower(Ti2Text) = " . convert_string_to_sqlsyntax($wordlc)
    );
    return $wid;
}

/**
 * Make the well-known word as no longer marked.
 *
 * @param string     $word   New well-known word
 * @param string|int $wid    New well-known word ID
 * @param string     $hex    Hexadecimal version of the lowercase word.
 * @param string|int $textid ID of the text.
 *
 * @global string $tbpref
 *
 * @return void
 */
function insert_word_wellknown_javascript($word, $wid, $hex, $textid)
{
    ?>
    <script type="text/javascript">
    //<![CDATA[
    var context = window.parent.document.getElementById('frame-l');
    var contexth = window.parent.document.getElementById('frame-h');
    var title = make_tooltip(<?php echo prepare_textdata_js($word); ?>,'*','','99');
    $('.TERM<?php echo $hex; ?>', context)
    .removeClass('status0')
    .addClass('status99 word<?php echo $wid; ?>')
    .attr('data_status','99')
    .attr('data_wid','<?php echo $wid; ?>')
    .attr('title',title);
    $('#learnstatus', contexth).html('<?php echo addslashes(todo_words_content((int) $textid)); ?>');

    cleanupRightFrames();
    //]]>
    </script>
    <?php

}

/**
 * Echoes a complete HTML page, with JavaScript content.
 *
 * @param string     $word   New well-known word
 * @param string|int $wid    New well-known word ID
 * @param string     $hex    Hexadecimal version of the lowercase word.
 * @param string|int $textid ID of the text.
 *
 * @return void
 */
function show_page_insert_word_wellknown($word, $wid, $hex, $textid)
{
    pagestart("Term: " . $word, false);
    echo "<p>OK, you know this term well!</p>";
    insert_word_wellknown_javascript($word, $wid, $hex, $textid);
    pageend();
}

/**
 * Main function to insert a new word with display and JS action.
 *
 * @param string $textid  ID of the text
 * @param string $textpos Position of the word in the text.
 *
 * @return void
 *
 * @since 2.0.4-fork
 */
function do_insert_word_wellknown($textid, $textpos)
{
    $word = get_word($textid, $textpos);
    $wid = insert_word_wellknown_to_database($textid, $word);
    $hex = strToClassName(mb_strtolower($word, 'UTF-8'));
    show_page_insert_word_wellknown($word, $wid, $hex, $textid);
}

if (getreq('tid') != '' && getreq('ord') != '') {
    do_insert_word_wellknown(getreq('tid'), getreq('ord'));
}
?>
