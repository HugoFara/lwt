<?php declare(strict_types=1);
/**
 * Word list filter form view
 *
 * Variables expected:
 * - $languages: Array of languages for filter dropdown
 * - $texts: Array of texts for filter dropdown
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

namespace Lwt\Views\Word;

use Lwt\View\Helper\SelectOptionsBuilder;
use Lwt\View\Helper\PageLayoutHelper;

?>
<form name="form1" action="#">
<table class="tab2" cellspacing="0" cellpadding="5">
<tr>
<th class="th1" colspan="4">Filter <img src="/assets/icons/funnel.png" title="Filter" alt="Filter" />&nbsp;
<input type="button" value="Reset All" data-action="reset-all" /></th>
</tr>
<tr>
<td class="td1 center" colspan="2">
Language:
<select name="filterlang" data-action="filter-language">
    <?php echo SelectOptionsBuilder::forLanguages($languages, $currentlang, '[Filter off]'); ?>
</select>
</td>
<td class="td1 center" colspan="2">
<select name="text_mode" data-action="text-mode">
<option value="0"<?php if ($currenttextmode == "0") echo ' selected="selected"'; ?>>Text:</option>
<option value="1"<?php if ($currenttextmode == "1") echo ' selected="selected"'; ?>>Text Tag:</option>
</select>
<select name="text" data-action="filter-text">
    <?php echo ($currenttextmode != 1) ? (SelectOptionsBuilder::forTexts($texts, $currenttext, false)) : (\Lwt\Services\TagService::getTextTagSelectOptionsWithTextIds($currentlang, $currenttexttag)); ?>
</select>
</td>
</tr>
<tr>
<td class="td1 center" colspan="2" nowrap="nowrap">
Status:
<select name="status" data-action="filter-status">
    <?php echo SelectOptionsBuilder::forWordStatus($currentstatus, true, false); ?>
</select>
</td>
<td class="td1 center" colspan="2" nowrap="nowrap">
<select name="query_mode" data-action="query-mode">
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
<input type="button" name="querybutton" value="Filter" data-action="filter-query" />&nbsp;
<input type="button" value="Clear" data-action="clear-query" />
</td>
</tr>
<tr>
<td class="td1 center" colspan="2" nowrap="nowrap">
Tag #1:
<select name="tag1" data-action="filter-tag1">
    <?php echo \Lwt\Services\TagService::getTermTagSelectOptions($currenttag1, $currentlang); ?>
</select>
</td>
<td class="td1 center" nowrap="nowrap">
Tag #1 .. <select name="tag12" data-action="filter-tag12">
    <?php echo SelectOptionsBuilder::forAndOr($currenttag12); ?>
</select> .. Tag #2
</td>
<td class="td1 center" nowrap="nowrap">
Tag #2:
<select name="tag2" data-action="filter-tag2">
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
    <?php PageLayoutHelper::buildPager($currentpage, $pages, '/words/edit', 'form1'); ?>
</th>
<th class="th1" nowrap="nowrap">
Sort Order:
<select name="sort" data-action="sort">
    <?php echo SelectOptionsBuilder::forWordSort($currentsort); ?>
</select>
</th></tr>
<?php } ?>
</table>
</form>
