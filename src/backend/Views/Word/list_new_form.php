<?php
/**
 * New word form for the words list page
 *
 * Variables expected:
 * - $lgid: Language ID
 * - $scrdir: Script direction attribute
 * - $showRoman: Whether to show romanization
 * - $languageName: Language name
 *
 * PHP version 8.1
 */
?>
<h2>New Term</h2>
<form name="newword" class="validate" action="/words/edit" method="post" data-lwt-form-check="true">
<input type="hidden" name="WoLgID" id="langfield" value="<?php echo $lgid; ?>" />
<table class="tab1" cellspacing="0" cellpadding="5">
<tr>
<td class="td1 right">Language:</td>
<td class="td1"><?php echo tohtml($languageName); ?></td>
</tr>
<tr>
<td class="td1 right">Term:</td>
<td class="td1">
    <input <?php echo $scrdir; ?> class="notempty setfocus checkoutsidebmp"
    data_info="Term" type="text" name="WoText" id="WoText" value="" maxlength="250" />
    <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
</tr>
<?php print_similar_terms_tabrow(); ?>
<tr>
<td class="td1 right">Translation:</td>
<td class="td1">
    <textarea class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="500" data_info="Translation" name="WoTranslation" cols="40" rows="3"></textarea></td>
</tr>
<tr>
<td class="td1 right">Tags:</td>
<td class="td1">
<?php echo \Lwt\Services\TagService::getWordTagsHtml(0); ?>
</td>
</tr>
<tr class="<?php echo ($showRoman ? '' : 'hide'); ?>">
<td class="td1 right">Romaniz.:</td>
<td class="td1"><input type="text" class="checkoutsidebmp" data_info="Romanization" name="WoRomanization" value="" maxlength="100" size="40" /></td>
</tr>
<tr>
<td class="td1 right">Sentence<br />Term in {...}:</td>
<td class="td1"><textarea <?php echo $scrdir; ?> name="WoSentence" id="WoSentence" cols="40" rows="3" class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="1000" data_info="Sentence"></textarea></td>
</tr>
<tr>
<td class="td1 right">Status:</td>
<td class="td1">
<?php echo get_wordstatus_radiooptions(1); ?>
</td>
</tr>
<tr>
<td class="td1 right" colspan="2">  &nbsp;
<?php echo createDictLinksInEditWin2(
    $lgid,
    'WoSentence',
    'WoText'
); ?>
    &nbsp; &nbsp;
<input type="button" value="Cancel" data-action="cancel-navigate" data-url="/words/edit" />
<input type="submit" name="op" value="Save" /></td>
</tr>
</table>
</form>
