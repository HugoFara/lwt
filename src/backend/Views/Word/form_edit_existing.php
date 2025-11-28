<?php

/**
 * Edit Word Form View - For editing an existing word
 *
 * Variables expected:
 * - $lang: int - Language ID
 * - $textId: int - Text ID
 * - $ord: int - Word order
 * - $wid: int - Word ID
 * - $fromAnn: string - From annotation flag
 * - $term: string - Term text
 * - $termlc: string - Lowercase term
 * - $scrdir: string - Script direction tag
 * - $showRoman: bool - Show romanization field
 * - $wordData: array - Current word data from database
 * - $sentence: string - Example sentence
 * - $status: int - Current status
 * - $transl: string - Current translation
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

<form name="editword" class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<input type="hidden" name="WoLgID" id="langfield" value="<?php echo $lang; ?>" />
<input type="hidden" name="fromAnn" value="<?php echo $fromAnn; ?>" />
<input type="hidden" name="WoID" value="<?php echo $wid; ?>" />
<input type="hidden" name="WoOldStatus" value="<?php echo $wordData['WoStatus']; ?>" />
<input type="hidden" name="WoTextLC" value="<?php echo tohtml($termlc); ?>" />
<input type="hidden" name="tid" value="<?php echo InputValidator::getString('tid'); ?>" />
<input type="hidden" name="ord" value="<?php echo InputValidator::getString('ord'); ?>" />
<table class="tab2" cellspacing="0" cellpadding="5">
   <tr title="Only change uppercase/lowercase!">
       <td class="td1 right"><b>Edit Term:</b></td>
       <td class="td1">
           <input <?php echo $scrdir; ?> class="notempty checkoutsidebmp"
           data_info="Term" type="text"
           name="WoText" id="wordfield"
           value="<?php echo tohtml($term); ?>" maxlength="250" size="35" />
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
           data_maxlength="500" data_info="Translation" cols="35"
           rows="3"><?php echo tohtml($transl); ?></textarea>
       </td>
   </tr>
   <tr>
       <td class="td1 right">Tags:</td>
       <td class="td1">
           <?php echo \Lwt\Services\TagService::getWordTagsHtml($wid); ?>
       </td>
   </tr>
   <tr class="<?php echo ($showRoman ? '' : 'hide'); ?>">
       <td class="td1 right">Romaniz.:</td>
       <td class="td1">
           <input type="text" class="checkoutsidebmp"
           data_info="Romanization" name="WoRomanization" maxlength="100"
           size="35"
           value="<?php echo tohtml($wordData['WoRomanization']); ?>" />
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
   <tr>
       <td class="td1 right">Status:</td>
       <td class="td1">
           <?php echo get_wordstatus_radiooptions($status); ?>
       </td>
   </tr>
   <tr>
       <td class="td1 right" colspan="2">
           <?php
           if ($fromAnn !== '') {
               echo createDictLinksInEditWin2(
                   $lang,
                   'document.forms[0].WoSentence',
                   'document.forms[0].WoText'
               );
           } else {
               echo createDictLinksInEditWin(
                   $lang,
                   $term,
                   'document.forms[0].WoSentence',
                   !InputValidator::hasFromGet('nodict')
               );
           }
           ?>
           &nbsp; &nbsp; &nbsp;
           <input type="submit" name="op" value="Change" />
       </td>
   </tr>
</table>
</form>
<?php
// Display example sentences button
example_sentences_area(
    $lang,
    $termlc,
    'document.forms.editword.WoSentence',
    $wid
);
?>
