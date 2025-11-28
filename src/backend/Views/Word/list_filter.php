<?php
/**
 * Word list filter form view
 *
 * Variables expected:
 * - $currentlang: Current language filter
 * - $currenttext: Current text filter
 * - $currenttexttag: Current text tag filter
 * - $currenttextmode: Current text/tag mode (0=text, 1=tag)
 * - $currentstatus: Current status filter
 * - $currentquery: Current search query
 * - $currentquerymode: Current query mode
 * - $currentregexmode: Current regex mode
 * - $currenttag1: First tag filter
 * - $currenttag2: Second tag filter
 * - $currenttag12: Tag logic (0=OR, 1=AND)
 * - $currentsort: Current sort option
 * - $currentpage: Current page number
 * - $recno: Total record count
 * - $pages: Total pages
 *
 * PHP version 8.1
 */
?>
<form name="form1" action="#" onsubmit="document.form1.querybutton.click(); return false;">
<table class="tab2" cellspacing="0" cellpadding="5">
<tr>
<th class="th1" colspan="4">Filter <img src="/assets/icons/funnel.png" title="Filter" alt="Filter" />&nbsp;
<input type="button" value="Reset All" onclick="resetAll('/words/edit');" /></th>
</tr>
<tr>
<td class="td1 center" colspan="2">
Language:
<select name="filterlang" onchange="{setLang(document.form1.filterlang,'/words/edit');}">
    <?php echo get_languages_selectoptions($currentlang, '[Filter off]'); ?>
</select>
</td>
<td class="td1 center" colspan="2">
<select name="text_mode" onchange="{val=document.form1.text_mode.value; location.href='/words/edit?page=1&amp;texttag=&amp;text=&amp;text_mode=' + val;}">
<option value="0"<?php if ($currenttextmode == "0") echo ' selected="selected"'; ?>>Text:</option>
<option value="1"<?php if ($currenttextmode == "1") echo ' selected="selected"'; ?>>Text Tag:</option>
</select>
<select name="text" onchange="{val=document.form1.text.options[document.form1.text.selectedIndex].value; location.href='/words/edit?page=1&amp;text=' + val;}">
    <?php echo ($currenttextmode != 1) ? (get_texts_selectoptions($currentlang, $currenttext)) : (\Lwt\Services\TagService::getTextTagSelectOptionsWithTextIds($currentlang, $currenttexttag)); ?>
</select>
</td>
</tr>
<tr>
<td class="td1 center" colspan="2" nowrap="nowrap">
Status:
<select name="status" onchange="{val=document.form1.status.options[document.form1.status.selectedIndex].value; location.href='/words/edit?page=1&amp;status=' + val;}">
    <?php echo get_wordstatus_selectoptions($currentstatus, true, false); ?>
</select>
</td>
<td class="td1 center" colspan="2" nowrap="nowrap">
<select name="query_mode" onchange="{val=document.form1.query.value;mode=document.form1.query_mode.value; location.href='/words/edit?page=1&amp;query=' + val + '&amp;query_mode=' + mode;}">
<option value="term,rom,transl"<?php if ($currentquerymode == "term,rom,transl") echo ' selected="selected"'; ?>>Term, Rom., Transl.</option>
<option disabled="disabled">------------</option>
<option value="term"<?php if ($currentquerymode == "term") echo ' selected="selected"'; ?>>Term</option>
<option value="rom"<?php if ($currentquerymode == "rom") echo ' selected="selected"'; ?>>Romanization</option>
<option value="transl"<?php if ($currentquerymode == "transl") echo ' selected="selected"'; ?>>Translation</option>
<option disabled="disabled">------------</option>
<option value="term,rom"<?php if ($currentquerymode == "term,rom") echo ' selected="selected"'; ?>>Term, Rom.</option>
<option value="term,transl"<?php if ($currentquerymode == "term,transl") echo ' selected="selected"'; ?>>Term, Transl.</option>
<option value="rom,transl"<?php if ($currentquerymode == "rom,transl") echo ' selected="selected"'; ?>>Rom., Transl.</option>
</select>
<?php
if ($currentregexmode == '') {
    echo '<span style="vertical-align: middle"> (Wildc.=*): </span>';
} elseif ($currentregexmode == 'r') {
    echo '<span style="vertical-align: middle"> RegEx Mode: </span>';
} else {
    echo '<span style="vertical-align: middle"> RegEx(CS) Mode: </span>';
}
?>
<input type="text" name="query" value="<?php echo tohtml($currentquery); ?>" maxlength="50" size="15" />&nbsp;
<input type="button" name="querybutton" value="Filter" onclick="{val=document.form1.query.value;val=encodeURIComponent(val);mode=document.form1.query_mode.value; location.href='/words/edit?page=1&amp;query=' + val + '&amp;query_mode=' + mode;}" />&nbsp;
<input type="button" value="Clear" onclick="{location.href='/words/edit?page=1&amp;query=';}" />
</td>
</tr>
<tr>
<td class="td1 center" colspan="2" nowrap="nowrap">
Tag #1:
<select name="tag1" onchange="{val=document.form1.tag1.options[document.form1.tag1.selectedIndex].value; location.href='/words/edit?page=1&amp;tag1=' + val;}">
    <?php echo \Lwt\Services\TagService::getTermTagSelectOptions($currenttag1, $currentlang); ?>
</select>
</td>
<td class="td1 center" nowrap="nowrap">
Tag #1 .. <select name="tag12" onchange="{val=document.form1.tag12.options[document.form1.tag12.selectedIndex].value; location.href='/words/edit?page=1&amp;tag12=' + val;}">
    <?php echo get_andor_selectoptions($currenttag12); ?>
</select> .. Tag #2
</td>
<td class="td1 center" nowrap="nowrap">
Tag #2:
<select name="tag2" onchange="{val=document.form1.tag2.options[document.form1.tag2.selectedIndex].value; location.href='/words/edit?page=1&amp;tag2=' + val;}">
    <?php echo \Lwt\Services\TagService::getTermTagSelectOptions($currenttag2, $currentlang); ?>
</select>
</td>
</tr>
<?php if ($recno > 0) { ?>
<tr>
<th class="th1" colspan="2" nowrap="nowrap">
    <?php echo $recno; ?> Term<?php echo ($recno == 1 ? '' : 's'); ?>
</th>
<th class="th1" colspan="1" nowrap="nowrap">
    <?php makePager($currentpage, $pages, '/words/edit', 'form1'); ?>
</th>
<th class="th1" nowrap="nowrap">
Sort Order:
<select name="sort" onchange="{val=document.form1.sort.options[document.form1.sort.selectedIndex].value; location.href='/words/edit?page=1&amp;sort=' + val;}">
    <?php echo get_wordssort_selectoptions($currentsort); ?>
</select>
</th></tr>
<?php } ?>
</table>
</form>
