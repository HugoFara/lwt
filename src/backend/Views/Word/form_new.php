<?php

/**
 * New Word Form View
 *
 * Variables expected:
 * - $lang: int - Language ID
 * - $textId: int - Text ID
 * - $scrdir: string - Script direction tag
 * - $showRoman: bool - Show romanization field
 * - $dictService: DictionaryService - Dictionary service instance
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

use Lwt\View\Helper\SelectOptionsBuilder;
use Lwt\Services\TagService;

?>
<script type="text/javascript">
    $(document).ready(lwtFormCheck.askBeforeExit);
    $(window).on('beforeunload', function() {
        setTimeout(function() {window.parent.frames['ru'].location.href = 'empty.html';}, 0);
    });
</script>

<form name="newword" class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
    <input type="hidden" name="WoLgID" id="langfield" value="<?php echo $lang; ?>" />
    <input type="hidden" name="tid" value="<?php echo $textId; ?>" />
    <table class="tab2" cellspacing="0" cellpadding="5">
        <tr>
            <td class="td1 right"><b>New Term:</b></td>
            <td class="td1"><input <?php echo $scrdir; ?>
            class="notempty setfocus checkoutsidebmp" data_info="New Term"
            type="text" name="WoText" id="wordfield" value="" maxlength="250" size="35" />
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
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
            <?php echo TagService::getWordTagsHtml(0); ?>
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
                <?php echo SelectOptionsBuilder::forWordStatusRadio(1); ?>
            </td>
        </tr>
        <tr>
            <td class="td1 right" colspan="2">  &nbsp;
                <?php echo $dictService->createDictLinksInEditWin3($lang, 'document.forms[\'newword\'].WoSentence', 'document.forms[\'newword\'].WoText'); ?>
                &nbsp; &nbsp;
                <input type="submit" name="op" value="Save" />
            </td>
        </tr>
    </table>
</form>
