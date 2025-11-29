<?php

/**
 * Tag List View - Display list of tags with filtering and pagination
 *
 * Variables expected:
 * - $message: Status/error message to display
 * - $tags: Array of tag records
 * - $totalCount: Total number of tags matching filter
 * - $pagination: Array with 'pages', 'currentPage', 'perPage'
 * - $currentQuery: Current filter query
 * - $currentSort: Current sort index
 * - $service: TagService instance
 * - $isTextTag: boolean - true for text tags, false for term tags
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @psalm-suppress UndefinedVariable - Variables are set by the including controller
 */

namespace Lwt\Views\Tags;

/** @var string $message */
/** @var array $tags */
/** @var int $totalCount */
/** @var array $pagination */
/** @var string $currentQuery */
/** @var int $currentSort */
/** @var \Lwt\Services\TagService $service */
/** @var bool $isTextTag */

$baseUrl = $service->getBaseUrl();
$tagTypeLabel = $service->getTagTypeLabel();
$sortOptions = $service->getSortOptions();

\Lwt\View\Helper\PageLayoutHelper::renderMessage($message, false);

?>
<p><a href="<?php echo $baseUrl; ?>?new=1"><img src="/assets/icons/plus-button.png" title="New" alt="New" /> New <?php echo $tagTypeLabel; ?> Tag ...</a></p>

<form name="form1" action="#">
<table class="tab2" cellspacing="0" cellpadding="5">
<tr>
<th class="th1" colspan="4">Filter <img src="/assets/icons/funnel.png" title="Filter" alt="Filter" />&nbsp;
<input type="button" value="Reset All" data-action="reset-all" data-base-url="<?php echo $baseUrl; ?>" /></th>
</tr>
<tr>
<td class="td1 center" colspan="4">
Tag Text or Comment:
<input type="text" name="query" value="<?php echo tohtml($currentQuery); ?>" maxlength="50" size="15" />&nbsp;
<input type="button" name="querybutton" value="Filter" data-action="filter-query" />&nbsp;
<input type="button" value="Clear" data-action="clear-query" />
</td>
</tr>
<?php if ($totalCount > 0): ?>
<tr>
<th class="th1" colspan="1" nowrap="nowrap">
    <?php echo $totalCount; ?> Tag<?php echo ($totalCount == 1 ? '' : 's'); ?>
</th><th class="th1" colspan="2" nowrap="nowrap">
    <?php makePager($pagination['currentPage'], $pagination['pages'], $baseUrl, 'form1'); ?>
</th><th class="th1" nowrap="nowrap">
Sort Order:
<select name="sort" data-action="sort">
<?php foreach ($sortOptions as $option): ?>
<option value="<?php echo $option['value']; ?>"<?php echo $currentSort == $option['value'] ? ' selected="selected"' : ''; ?>><?php echo $option['text']; ?></option>
<?php endforeach; ?>
</select>
</th></tr>
<?php endif; ?>
</table>
</form>

<?php if ($totalCount == 0): ?>
<p>No tags found.</p>
<?php else: ?>
<form name="form2" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<input type="hidden" name="data" value="" />
<table class="tab2" cellspacing="0" cellpadding="5">
<tr><th class="th1 center" colspan="2">
Multi Actions <img src="/assets/icons/lightning.png" title="Multi Actions" alt="Multi Actions" />
</th></tr>
<tr><td class="td1 center" colspan="2">
<b>ALL</b> <?php echo ($totalCount == 1 ? '1 Tag' : $totalCount . ' Tags'); ?>:&nbsp;
<select name="allaction" data-action="all-action" data-recno="<?php echo $totalCount; ?>"><?php echo get_alltagsactions_selectoptions(); ?></select>
</td></tr>
<tr><td class="td1 center">
<input type="button" value="Mark All" data-action="mark-all" />
<input type="button" value="Mark None" data-action="mark-none" />
</td>
<td class="td1 center">Marked Tags:&nbsp;
<select name="markaction" id="markaction" disabled="disabled" data-action="mark-action"><?php echo get_multipletagsactions_selectoptions(); ?></select>
</td></tr></table>

<table class="sortable tab2" cellspacing="0" cellpadding="5">
<tr>
<th class="th1 sorttable_nosort">Mark</th>
<th class="th1 sorttable_nosort">Actions</th>
<th class="th1 clickable">Tag Text</th>
<th class="th1 clickable">Tag Comment</th>
<th class="th1 clickable"><?php echo $isTextTag ? 'Texts' : 'Terms'; ?> With Tag</th>
<?php if ($isTextTag): ?>
<th class="th1 clickable">Arch.Texts<br />With Tag</th>
<?php endif; ?>
</tr>

<?php foreach ($tags as $tag): ?>
<tr>
    <td class="td1 center">
        <a name="rec<?php echo $tag['id']; ?>">
        <input name="marked[]" type="checkbox" class="markcheck" value="<?php echo $tag['id']; ?>" <?php echo checkTest($tag['id'], 'marked'); ?> />
        </a>
    </td>
    <td class="td1 center" nowrap="nowrap">
        &nbsp;<a href="<?php echo $_SERVER['PHP_SELF']; ?>?chg=<?php echo $tag['id']; ?>"><img src="/assets/icons/document--pencil.png" title="Edit" alt="Edit" /></a>&nbsp;
        <a class="confirmdelete" href="<?php echo $_SERVER['PHP_SELF']; ?>?del=<?php echo $tag['id']; ?>"><img src="/assets/icons/minus-button.png" title="Delete" alt="Delete" /></a>&nbsp;
    </td>
    <td class="td1 center"><?php echo tohtml($tag['text']); ?></td>
    <td class="td1 center"><?php echo tohtml($tag['comment']); ?></td>
    <td class="td1 center">
        <?php if ($tag['usageCount'] > 0): ?>
        <a href="<?php echo $service->getItemsUrl($tag['id']); ?>"><?php echo $tag['usageCount']; ?></a>
        <?php else: ?>
        0
        <?php endif; ?>
    </td>
    <?php if ($isTextTag): ?>
    <td class="td1 center">
        <?php if ($tag['archivedUsageCount'] > 0): ?>
        <a href="<?php echo $service->getArchivedItemsUrl($tag['id']); ?>"><?php echo $tag['archivedUsageCount']; ?></a>
        <?php else: ?>
        0
        <?php endif; ?>
    </td>
    <?php endif; ?>
</tr>
<?php endforeach; ?>
</table>

<?php if ($pagination['pages'] > 1): ?>
<table class="tab2" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1" nowrap="nowrap">
            <?php echo $totalCount; ?> Tag<?php echo ($totalCount == 1 ? '' : 's'); ?>
        </th>
        <th class="th1" nowrap="nowrap">
            <?php makePager($pagination['currentPage'], $pagination['pages'], $baseUrl, 'form2'); ?>
        </th>
    </tr>
</table>
<?php endif; ?>
</form>
<?php endif; ?>
