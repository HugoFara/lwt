<?php

/**
 * \file
 * \brief Change the text display mode
 *
 * Call: set_text_mode.php?text=[textid]&mode=0/1&showLeaning=0/1
 *
 * PHP version 8.1
 *
 * @category Helper_Frame
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @since    1.0.3.1
 */

require_once 'Core/session_utility.php';


/**
 * Save text mode settings.
 *
 * @param int $showAll      Whether all word should be shown
 * @param int $showLearning Whether to show translation of learning words
 *
 * @return int If show learning were previously true (1) or false (0)
 *
 * @psalm-return 0|1
 */
function text_mode_save_settings($showAll, $showLearning): int
{
    saveSetting('showallwords', $showAll);
    $oldShowLearning = getSettingZeroOrOne('showlearningtranslations', 1);
    saveSetting('showlearningtranslations', $showLearning);
    return $oldShowLearning;
}

/**
 * Do the JavaScript action to change the display of translations.
 *
 * @param int $showLearning    Whether to show translation of learning words
 * @param int $oldShowLearning If show learning was previously true (1) or false (0)
 *
 * @return void
 */
function text_annotations_mode_javascript($showLearning, $oldShowLearning)
{
    ?>

<script type="text/javascript">
    //<![CDATA[
    /** @var {boolean} showLearningChanged hide all translations status has changed */
    const showLearningChanged = <?php echo json_encode($showLearning != $oldShowLearning); ?>;  // 0 (jquery) or 1 (reload)
    const showLearning = <?php echo json_encode($showLearning) ?>;

    /**
     * Hide translations for words being learned. Doesn't work.
     *
     * @param {object} context Window containing words
     */
    function hideAnnotations(context) {
        $('.mword',context)
        .removeClass('wsty')
        .addClass('mwsty')
        .each(function(){
            const c = '&nbsp;' + $(this).attr('data_code') + '&nbsp;';
            $(this).html(c);
        });
        $('span',context).not('#totalcharcount').removeClass('hide');
    }

    /**
     * Hide translations for all words. Doesn't work.
     *
     * @param {object} context Window containing words
     */
    function showAnnotations(context) {
        $('.mword',context)
        .removeClass('mwsty')
        .addClass('wsty')
        .each(function(){
            const c = $(this).attr('data_text');
            $(this).text(c);
            if($(this).not('.hide').length){
                let u = parseInt($(this).attr('data_code')) *2 + parseInt($(this).attr('data_order')) -1;
                $(this).nextUntil('[id^="ID-' + u + '-"]',context).addClass('hide');
            }
        });
    }
    /* Doesn't work
    if (showLearningChanged) {
        window.parent.location.reload(true);
    } else {
        const context = window.parent.document.getElementById('frame-l');
        if (showLearning) {
            showAnnotations(context);
        } else {
            hideAnnotations(context);
        }
    }
    */
    $('#waiting').html('<b>OK -- </b>');


    //]]>
</script>
    <?php
}

/**
 * Do the main page content when chaning display of translations.
 *
 * @param int $showAll         Whether all word should be shown
 * @param int $showLearning    Whether to show translation of learning words
 * @param int $oldShowLearning If show learning was previously true (1) or false (0)
 *
 * @return void
 */
function text_mode_page_content($showAll, $showLearning, $oldShowLearning)
{
    pagestart("Text Display Mode changed", false);

    echo '<p><span id="waiting"><img src="' .
    get_file_path('assets/icons/waiting.gif') .
    '" alt="Please wait" title="Please wait" />&nbsp;&nbsp;Please wait ...</span>';
    flush();
    text_annotations_mode_javascript($showLearning, $oldShowLearning);
    if ($showAll == 1) {
        echo '<b><i>Show All</i></b> is set to <b>ON</b>.
        <br /><br />ALL terms are now shown, and all multi-word terms are shown as superscripts before the first word. The superscript indicates the number of words in the multi-word term.
        <br /><br />To concentrate more on the multi-word terms and to display them without superscript, set <i>Show All</i> to OFF.</p>';
    } else {
        echo '<b><i>Show All</i></b> is set to <b>OFF</b>.
        <br /><br />Multi-word terms now hide single words and shorter or overlapping multi-word terms. The creation and deletion of multi-word terms can be a bit slow in long texts.
        <br /><br />To  manipulate ALL terms, set <i>Show All</i> to ON.</p>';
    }

    echo "<br /><br />";

    if ($showLearning == 1) {
        echo '<b><i>Learning Translations</i></b> is set to <b>ON</b>.
        <br /><br />Terms that have Learning Level&nbsp;1 will show their translations beneath the term in the reading mode.
        <br /><br />To hide the translations, set <i>Learning Translations</i> to OFF.</p>';
    } else {
        echo '<b><i>Learning Translations</i></b> is set to <b>OFF</b>.
        <br /><br />No translations will be shown directly in the reading window.
        <br /><br />To see translations for terms with Learning Level&nbsp;1 underneath the terms in the reading window, set <i>Learning Translations</i> to ON.</p>';
    }

    pageend();
}

/**
 * Complete workflow for changing text mode.
 * It edits the settings in the database, show the success message
 * and do JavaScript action to change its display.
 *
 * @param int $textid       ID of the current text
 * @param int $showAll      Whether all word should be shown
 * @param int $showLearning Whether to show translation of learning words
 *
 * @return void
 */
function change_text_mode($textid, $showAll, $showLearning)
{
    $oldShowLearning = text_mode_save_settings($showAll, $showLearning);
    text_mode_page_content($showAll, $showLearning, $oldShowLearning);
}

if (getreq('text') != '') {
    change_text_mode((int)getreq('text'), (int)getreq('mode'), (int)getreq('showLearning'));
}

?>
