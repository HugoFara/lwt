<?php

/**
 * New word, created while reading or testing
 *
 * Call: new_word.php?...
 *      ... text=[textid]&lang=[langid] ... new term input
 *      ... op=Save ... do the insert
 *
 * PHP version 8.1
 *
 * @category Helper_Frame
 * @package Lwt
 */

namespace Lwt\Interface\New_Word;

require_once 'inc/session_utility.php';
require_once 'inc/simterms.php';

// INSERT

if (isset($_REQUEST['op'])) {

    if ($_REQUEST['op'] == 'Save') {

        $text = trim(prepare_textdata($_REQUEST["WoText"]));
        $textlc = mb_strtolower($text, 'UTF-8');
        $translation_raw = repl_tab_nl(getreq("WoTranslation"));
        if ($translation_raw == '' ) {
            $translation = '*';
        }
        else {
            $translation = $translation_raw;
        }

        $titletext = "New Term: " . tohtml($textlc);
        pagestart_nobody($titletext);
        echo '<h1>' . $titletext . '</h1>';

        $message = runsql(
            'insert into ' . $tbpref . 'words (WoLgID, WoTextLC, WoText, ' .
            'WoStatus, WoTranslation, WoSentence, WoRomanization, WoStatusChanged,' .  make_score_random_insert_update('iv') . ') values( ' .
            $_REQUEST["WoLgID"] . ', ' .
            convert_string_to_sqlsyntax($textlc) . ', ' .
            convert_string_to_sqlsyntax($text) . ', ' .
            $_REQUEST["WoStatus"] . ', ' .
            convert_string_to_sqlsyntax($translation) . ', ' .
            convert_string_to_sqlsyntax(repl_tab_nl($_REQUEST["WoSentence"])) . ', ' .
            convert_string_to_sqlsyntax($_REQUEST["WoRomanization"]) . ', NOW(), ' .
            make_score_random_insert_update('id') . ')', "Term saved", $sqlerrdie = false
        );

        if (substr($message, 0, 22) == 'Error: Duplicate entry') {
            $message = 'Error: <b>Duplicate entry for <i>' . $textlc . '</i></b><br /><br /><input type="button" value="&lt;&lt; Back" onclick="history.back();" />';
        }

        $wid = get_last_key();

        saveWordTags($wid);
        init_word_count();
        //        $showAll = getSettingZeroOrOne('showallwords',1);
        ?>

   <p><?php echo $message; ?></p>

        <?php
        if (substr($message, 0, 5) != 'Error') {

            $len = get_first_value('select WoWordCount as value from ' . $tbpref . 'words where WoID = ' . $wid);
            if ($len > 1) {
                insertExpressions($textlc, $_REQUEST["WoLgID"], $wid, $len, 0);
            } else if ($len == 1) {
                $hex = strToClassName(prepare_textdata($textlc));
                do_mysqli_query(
                    'UPDATE ' . $tbpref . 'textitems2 SET Ti2WoID = ' . $wid . '
                    WHERE Ti2LgID = ' . $_REQUEST["WoLgID"] . ' AND LOWER(Ti2Text) = ' . convert_string_to_sqlsyntax_notrim_nonull($textlc)
                );
                ?>
<script type="text/javascript">
    var context = window.parent.document;
    var woid = <?php echo prepare_textdata_js($wid); ?>;
    var status = <?php echo prepare_textdata_js($_REQUEST["WoStatus"]); ?>;
    var trans = <?php echo prepare_textdata_js($translation . getWordTagList($wid, ' ', 1, 0)); ?>;
    var roman = <?php echo prepare_textdata_js($_REQUEST["WoRomanization"]); ?>;
    var title = '';
    if (window.parent.LWT_DATA.settings.jQuery_tooltip) {
        title = make_tooltip(
            <?php echo prepare_textdata_js($_REQUEST["WoText"]); ?>,
            trans, roman, status
        );
    }

    if($('.TERM<?php echo $hex; ?>', context).length){
        $('.TERM<?php echo $hex; ?>', context)
        .removeClass('status0')
        .addClass('word' + woid + ' ' + 'status' + status)
        .attr('data_trans',trans)
        .attr('data_rom',roman)
        .attr('data_status',status)
        .attr('data_wid',woid)
        .attr('title',title);
        $('#learnstatus', context).html('<?php echo addslashes(todo_words_content((int) $_REQUEST['tid'])); ?>');
    }
</script>
                <?php
                flush();
            } ?>
<script type="text/javascript">
    cleanupRightFrames();
</script>
            <?php
        } // (substr($message,0,5) != 'Error')

    } // $_REQUEST['op'] == 'Save'

} // if (isset($_REQUEST['op']))

// FORM

else {  // if (! isset($_REQUEST['op']))

    // new_word.php?text=..&lang=..

    $lang = (int)getreq('lang');
    $text = (int)getreq('text');
    $scrdir = getScriptDirectionTag($lang);
    $showRoman = (bool) get_first_value(
        "SELECT LgShowRomanization AS value
        FROM {$tbpref}languages
        WHERE LgID = $lang"
    );
    pagestart_nobody('');
    ?>
    <script type="text/javascript">
        $(document).ready(lwtFormCheck.askBeforeExit);
        $(window).on('beforeunload', function() {
            setTimeout(function() {window.parent.frames['ru'].location.href = 'empty.html';}, 0);
        });
    </script>

    <form name="newword" class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <input type="hidden" name="WoLgID" id="langfield" value="<?php echo $lang; ?>" />
        <input type="hidden" name="tid" value="<?php echo $text; ?>" />
        <table class="tab2" cellspacing="0" cellpadding="5">
            <tr>
                <td class="td1 right"><b>New Term:</b></td>
                <td class="td1"><input <?php echo $scrdir; ?>
                class="notempty setfocus checkoutsidebmp" data_info="New Term"
                type="text" name="WoText" id="wordfield" value="" maxlength="250" size="35" />
                <img src="/icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
            </tr>
            <?php print_similar_terms_tabrow(); ?>
            <tr>
                <td class="td1 right">Translation:</td>
                <td class="td1">
                    <textarea class="textarea-noreturn checklength checkoutsidebmp"
                    data_maxlength="500" data_info="Translation" name="WoTranslation" cols="35" rows="3"></textarea>
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
                    <input type="text" class="checkoutsidebmp" data_info="Romanization" name="WoRomanization" value="" maxlength="100" size="35" />
                </td>
            </tr>
            <tr>
                <td class="td1 right">Sentence<br />Term in {...}:</td>
                <td class="td1">
                    <textarea <?php echo $scrdir; ?> name="WoSentence" cols="35" rows="3" class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="1000" data_info="Sentence"></textarea>
                </td>
            </tr>
            <tr>
                <td class="td1 right">Status:</td>
                <td class="td1">
                    <?php echo get_wordstatus_radiooptions(1); ?>
                </td>
            </tr>
            <tr>
                <td class="td1 right" colspan="2">  &nbsp;
                    <?php echo createDictLinksInEditWin3($lang, 'document.forms[\'newword\'].WoSentence', 'document.forms[\'newword\'].WoText'); ?>
                    &nbsp; &nbsp;
                    <input type="submit" name="op" value="Save" />
                </td>
            </tr>
        </table>
    </form>

    <?php

}

pageend();

?>
