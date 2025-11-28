<?php

/**
 * Edit Word Form View - For creating new word from reading screen
 *
 * Variables expected:
 * - $lang: int - Language ID
 * - $textId: int - Text ID
 * - $ord: int - Word order
 * - $fromAnn: string - From annotation flag
 * - $term: string - Term text
 * - $termlc: string - Lowercase term
 * - $scrdir: string - Script direction tag
 * - $showRoman: bool - Show romanization field
 * - $sentence: string - Example sentence
 * - $transUri: string - Translation API URI
 * - $langShort: string - Short language code
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

namespace Lwt\Views\Word;

use Lwt\Core\Http\InputValidator;

?>
<script type="text/javascript">
    $(document).ready(lwtFormCheck.askBeforeExit);
    $(window).on('beforeunload',function() {
        setTimeout(function() {window.parent.frames['ru'].location.href = 'empty.html';}, 0);
    });
</script>

<form name="newword" class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<input type="hidden" name="fromAnn" value="<?php echo $fromAnn; ?>" />
<input type="hidden" name="WoLgID" id="langfield" value="<?php echo $lang; ?>" />
<input type="hidden" name="WoTextLC" value="<?php echo tohtml($termlc); ?>" />
<input type="hidden" name="tid" value="<?php echo $textId; ?>" />
<input type="hidden" name="ord" value="<?php echo $ord; ?>" />
<table class="tab2" cellspacing="0" cellpadding="5">
   <tr title="Only change uppercase/lowercase!">
       <td class="td1 right"><b>New Term:</b></td>
       <td class="td1">
           <input <?php echo $scrdir; ?> class="notempty checkoutsidebmp"
           data_info="New Term" type="text"
           name="WoText" id="wordfield" value="<?php echo tohtml($term); ?>"
           maxlength="250" size="35" />
           <img src="/assets/icons/status-busy.png" title="Field must not be empty"
           alt="Field must not be empty" />
       </td>
   </tr>
   <?php print_similar_terms_tabrow(); ?>
   <tr>
       <td class="td1 right">Translation:</td>
       <td class="td1">
           <textarea name="WoTranslation"
           class="setfocus textarea-noreturn checklength checkoutsidebmp"
           data_maxlength="500"
           data_info="Translation" cols="35" rows="3"></textarea>
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
           <input type="text" class="checkoutsidebmp" data_info="Romanization"
           name="WoRomanization"
           value="" maxlength="100" size="35" />
       </td>
   </tr>
   <tr>
       <td class="td1 right">Sentence<br />Term in {...}:</td>
       <td class="td1">
           <textarea <?php echo $scrdir; ?> name="WoSentence"
           class="textarea-noreturn checklength checkoutsidebmp"
           data_maxlength="1000" data_info="Sentence" cols="35"
           rows="3"><?php echo tohtml($sentence); ?></textarea>
       </td>
   </tr>
   <?php print_similar_terms_tabrow(); ?>
   <tr>
       <td class="td1 right">Status:</td>
       <td class="td1">
           <?php echo get_wordstatus_radiooptions(1); ?>
       </td>
   </tr>
   <tr>
       <td class="td1 right" colspan="2">
           <?php echo createDictLinksInEditWin(
               $lang,
               $term,
               'document.forms[0].WoSentence',
               !InputValidator::hasFromGet('nodict')
           ); ?>
       &nbsp; &nbsp; &nbsp;
       <input type="submit" name="op" value="Save" /></td>
   </tr>
</table>
</form>
<script type="text/javascript">
    const TRANS_URI = <?php echo json_encode($transUri); ?>
    const LANG_SHORT = <?php echo json_encode($langShort); ?> ||
    getLangFromDict(TRANS_URI);

    /**
     * Sets the translation of a term.
     */
    const autoTranslate = function () {
        const translator_url = new URL(TRANS_URI);
        const urlParams = translator_url.searchParams;
        if (urlParams.get("lwt_translator") == "libretranslate") {
            const term = $('#wordfield').val();
            getLibreTranslateTranslation(
                translator_url, term,
                (urlParams.has("source") ?
                urlParams.get("source") : LANG_SHORT),
                urlParams.get("target")
            )
            .then(function (translation) {
                newword.WoTranslation.value = translation;
            });
        }
    }

    /**
     * Sets the romanization of a term.
     */
    const autoRomanization = function (lgid) {
        const term = $('#wordfield').val();
        getPhoneticTextAsync(term, lgid)
        .then(function (phonetic) {
            newword.WoRomanization.value = phonetic["phonetic_reading"];
        });
    }

    autoTranslate();
    autoRomanization(<?php echo json_encode($lang); ?>);
</script>
<?php
// Display example sentence button
example_sentences_area($lang, $termlc, 'document.forms.newword.WoSentence', 0);
?>
