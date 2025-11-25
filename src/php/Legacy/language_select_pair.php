<?php

/**
 * \file
 * \brief Display Language Pair Selection Window for Wizard
 *
 * Call: select_lang_pair.php
 *
 * PHP version 8.1
 *
 * @category Helper_Frame
 * @package Lwt
 * @author https://sourceforge.net/projects/lwt/ LWT Project
 * @since  1.5.11
 */

require_once 'inc/session_utility.php';
require_once 'inc/langdefs.php';

/**
 * Returns a dropdown menu of the different languages
 */
function get_wizard_selectoptions($v): string
{
    $r = "<option value=\"\"" . get_selected($v, "") . ">[Choose...]</option>";
    $keys = array_keys(LWT_LANGUAGES_ARRAY);
    foreach ($keys as $item) {
        $r .= "<option value=\"" . $item . "\"" . get_selected($v, $item) . ">" . $item . "</option>";
    }
    return $r;
}

pagestart_nobody('Language Settings Wizard', 'html{background-color: rgba(0, 0, 0, 0);}');

$currentnativelanguage = getSetting('currentnativelanguage');

?>

<script type="text/javascript">
    //<![CDATA[

    const LANGDEFS = <?php echo json_encode(LWT_LANGUAGES_ARRAY); ?>;

    /// Execute the wizard
    function wizard_go() {
        const l1 = $('#l1').val();
        const l2 = $('#l2').val();
        if (l1 == '') {
            alert ('Please choose your native language (L1)!');
            return;
        }
        if (l2 == '') {
            alert ('Please choose your language you want to read/study (L2)!');
            return;
        }
        if (l2 == l1) {
            alert ('L1 L2 Languages must not be equal!');
            return;
        }
        var w = window.opener;
        if (typeof w == 'undefined') {
                alert ('Language setting cannot be set. Please try again.');
                wizard_exit();
            }
        var context = w.document;
        $('input[name="LgName"]',context).val(l2);
        $('input[name="LgDict1URI"]',context).val(
            'https://de.glosbe.com/' + LANGDEFS[l2][0] + '/' +
            LANGDEFS[l1][0] + '/lwt_term'
        );
        $('input[name="LgDict1PopUp"]', context).attr('checked', true);
        $('input[name="LgGoogleTranslateURI"]',context).val(
            'http://translate.google.com/?ie=UTF-8&sl=' +
            LANGDEFS[l2][1] + '&tl=' + LANGDEFS[l1][1] + '&text=lwt_term'
        );
        $('input[name="LgGoogleTranslatePopUp"]', context).attr('checked', true);
        $('input[name="LgTextSize"]',context).val(LANGDEFS[l2][2] ? 200 : 150);
        $('input[name="LgRegexpSplitSentences"]',context).val(LANGDEFS[l2][4]);
        $('input[name="LgRegexpWordCharacters"]',context).val(LANGDEFS[l2][3]);
        $('select[name="LgSplitEachChar"]',context).val(LANGDEFS[l2][5] ? 1 : 0);
        $('select[name="LgRemoveSpaces"]',context).val(LANGDEFS[l2][6] ? 1 : 0);
        $('select[name="LgRightToLeft"]',context).val(LANGDEFS[l2][7] ? 1 : 0);
        wizard_exit();
    }

    /// Closes the wizard
    function wizard_exit() {
        window.close();
    }

    $(function(){
        $('.center').addClass('backlightyellow');
        const bg = $('.center').css('background-color');
        $('body').css('background-color',bg);
        $('.center').removeClass('backlightyellow');
    });
    //]]>
</script>

<div class="center">
    <p class="wizard">
        <img src="/assets/icons/wizard.png" title="Language Settings Wizard" alt="Language Settings Wizard" />
    </p>

    <h1 class="wizard">
        Language Settings Wizard
    </h1>
    <p class="wizard">
        <b>My Native language is:</b>
        <br />
        L1:
        <select name="l1" id="l1" onchange="{do_ajax_save_setting('currentnativelanguage',($('#l1').val()));}">
            <?php echo get_wizard_selectoptions($currentnativelanguage); ?>
        </select>
    </p>
    <p class="wizard">
        <b>I want to study:</b>
        <br />
        L2:
        <select name="l2" id="l2">
            <?php echo get_wizard_selectoptions(''); ?>
        </select>
    </p>
    <p class="wizard">
        <input type="button" style="font-size:1.1em;" value="Set Language Settings" onclick="wizard_go();" />
    </p>

    <p class="wizard">
        <input type="button" value="Cancel" onclick="wizard_exit();" />
    </p>
</div>
<?php

pageend();

?>
