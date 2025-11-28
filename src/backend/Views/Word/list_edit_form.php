<?php
/**
 * Edit word form for the words list page
 *
 * Variables expected:
 * - $word: Word data array with WoID, WoLgID, WoText, etc.
 * - $scrdir: Script direction attribute
 * - $showRoman: Whether to show romanization
 * - $transl: Translation value (with '*' replaced by '')
 *
 * PHP version 8.1
 */
?>
<h2>Edit Term</h2>
<script type="text/javascript" charset="utf-8">
    $(document).ready(lwtFormCheck.askBeforeExit);
</script>
<form name="editword" class="validate" action="/words/edit#rec<?php echo $word['WoID']; ?>" method="post">
<input type="hidden" name="WoID" value="<?php echo $word['WoID']; ?>" />
<input type="hidden" name="WoLgID" id="langfield" value="<?php echo $word['WoLgID']; ?>" />
<input type="hidden" name="WoOldStatus" value="<?php echo $word['WoStatus']; ?>" />
<table class="tab1" cellspacing="0" cellpadding="5">
<tr>
   <td class="td1 right">Language:</td>
   <td class="td1"><?php echo tohtml($word['LgName']); ?></td>
</tr>
<tr title="Normally only change uppercase/lowercase here!">
   <td class="td1 right">Term:</td>
   <td class="td1"><input <?php echo $scrdir; ?> class="notempty setfocus checkoutsidebmp" data_info="Term" type="text" name="WoText" id="wordfield" value="<?php echo tohtml($word['WoText']); ?>" maxlength="250" size="40" /> <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
   </td>
</tr>
<?php print_similar_terms_tabrow(); ?>
<tr>
   <td class="td1 right">Translation:</td>
   <td class="td1">
       <textarea class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="500" data_info="Translation" name="WoTranslation" cols="40" rows="3"><?php echo tohtml($transl); ?></textarea>
   </td>
</tr>
<tr>
   <td class="td1 right">Tags:</td>
   <td class="td1">
       <?php echo getWordTags($word['WoID']); ?>
   </td>
</tr>
<tr class="<?php echo ($showRoman ? '' : 'hide'); ?>">
   <td class="td1 right">Romaniz.:</td>
   <td class="td1">
       <input type="text" class="checkoutsidebmp" data_info="Romanization" name="WoRomanization" maxlength="100" size="40"
       value="<?php echo tohtml($word['WoRomanization']); ?>" />
   </td>
</tr>
<tr>
   <td class="td1 right">Sentence<br />Term in {...}:</td>
   <td class="td1"><textarea <?php echo $scrdir; ?> class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="1000" data_info="Sentence" name="WoSentence" cols="40" rows="3"><?php echo tohtml($word['WoSentence']); ?></textarea></td>
</tr>
<tr>
   <td class="td1 right">Status:</td>
   <td class="td1">
       <?php echo get_wordstatus_radiooptions($word['WoStatus']); ?>
   </td>
</tr>
<tr>
   <td class="td1 right" colspan="2">  &nbsp;
       <?php echo createDictLinksInEditWin2($word['WoLgID'], 'document.forms[\'editword\'].WoSentence', 'document.forms[\'editword\'].WoText'); ?>
       &nbsp; &nbsp;
       <input type="button" value="Cancel" onclick="{lwtFormCheck.resetDirty(); location.href='/words/edit#rec<?php echo $word['WoID']; ?>';}" />
       <input type="submit" name="op" value="Change" />
   </td>
</tr>
</table>
</form>
<?php
// Display example sentence button
example_sentences_area($word['WoLgID'], $word['WoTextLC'], "document.forms['editword'].WoSentence", $word['WoID']);
?>
