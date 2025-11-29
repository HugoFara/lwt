<?php

/**
 * Feeds Management Index View
 *
 * Variables expected:
 * - $feeds: array of feed data from query result
 * - $currentLang: int current language filter
 * - $currentQuery: string search query
 * - $currentPage: int current page number
 * - $currentSort: int current sort index
 * - $totalFeeds: int total number of feeds
 * - $pages: int total number of pages
 * - $maxPerPage: int feeds per page
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

namespace Lwt\Views\Feed;

?>
<div class="flex-spaced">
    <div><a href="/feeds">My Feeds</a></div>
    <div>
        <a href="/feeds/edit?new_feed=1">
            <img src="/assets/icons/feed--plus.png" title="new feed" alt="new feed" />
            New Feed...
        </a>
    </div>
</div>
<form name="form1" action="#">
<table class="tab2" cellspacing="0" cellpadding="5"><tr>
<th class="th1" colspan="4">Filter <img src="/assets/icons/funnel.png" title="Filter" alt="Filter" />&nbsp;
<input type="button" value="Reset All" data-action="reset-all" data-url="/feeds/edit" /></th>
</tr>
<tr>
    <td class="td1 center" colspan="2" style="width:30%;">
    Language:&nbsp;<select name="filterlang" data-action="filter-language" data-url="/feeds/edit?manage_feeds=1">
    <?php echo get_languages_selectoptions($currentLang, '[Filter off]'); ?>
</select>
</td>
<td class="td1 center" colspan="4">
    Feed Name (Wildc.=*):
    <input type="text" name="query" value="<?php echo tohtml($currentQuery); ?>" maxlength="50" size="15" />&nbsp;
    <input type="button" name="querybutton" value="Filter" data-action="filter-query" />&nbsp;
    <input type="button" value="Clear" data-action="clear-query" />
</td>
</tr>
</table>

<input id="map" type="hidden" name="selected_feed" value="" />
<table class="tab2" cellspacing="0" cellpadding="5">
<tr>
    <th class="th1" colspan="3">
        Multi Actions <img src="/assets/icons/lightning.png" title="Multi Actions" alt="Multi Actions" />
    </th>
</tr>
<tr><td class="td1 center" style="width:30%;">
<input type="button" value="Mark All" data-action="mark-all" />
<input type="button" value="Mark None" data-action="mark-none" />
</td><td class="td1 center" colspan="2">Marked Newsfeeds:&nbsp;
<select name="markaction" id="markaction" disabled="disabled" data-action="mark-action">
    <option value="">[Choose...]</option>
    <option disabled="disabled">------------</option>
    <option value="update">Update</option>
    <option disabled="disabled">------------</option>
    <option value="res_art">Reset Unloadable Articles</option>
    <option disabled="disabled">------------</option>
    <option value="del_art">Delete All Articles</option>
    <option disabled="disabled">------------</option>
    <option value="del">Delete</option>
</select></td></tr>
<?php if ($totalFeeds > 0): ?>
<tr><th class="th1" style="width:30%;"> <?php echo $totalFeeds; ?> newsfeeds </th>
<th class="th1">
<?php makePager($currentPage, $pages, '/feeds/edit', 'form1'); ?>
</th>
<th class="th1" colspan="1" nowrap="nowrap">
Sort Order:
<select name="sort" data-action="sort">
<?php echo get_textssort_selectoptions($currentSort); ?>
</select>
</th>
</table>
</form>
<form name="form2" action="" method="get">
<table class="sortable tab2" cellspacing="0" cellpadding="5">
<tr>
    <th class="th1 sorttable_nosort">Mark</th>
    <th class="th1 sorttable_nosort">Actions</th>
    <th class="th1 clickable">Newsfeeds</th>
    <th class="th1 sorttable_nosort">Options</th>
    <th class="th1 sorttable_numeric clickable">Last Update</th>
</tr>
<?php
$time = time();
while ($row = mysqli_fetch_assoc($feeds)):
    $diff = $time - (int)$row['NfUpdate'];
?>
<tr>
    <td class="td1 center">
        <input type="checkbox" name="marked[]" class="markcheck" value="<?php echo $row['NfID']; ?>" />
    </td>
    <td style="white-space: nowrap" class="td1 center">
        <a href="/feeds/edit?edit_feed=1&amp;selected_feed=<?php echo $row['NfID']; ?>">
            <img src="/assets/icons/feed--pencil.png" title="Edit" alt="Edit" />
        </a>
        &nbsp; <a href="/feeds/edit?manage_feeds=1&amp;load_feed=1&amp;selected_feed=<?php echo $row['NfID']; ?>">
            <span title="Update Feed"><img src="/assets/icons/arrow-circle-135.png" alt="-" /></span>
        </a>&nbsp;
        <a href="<?php echo $row['NfSourceURI']; ?>" onclick="window.open(this.href); return false">
            <img src="/assets/icons/external.png" title="Show Feed" alt="Link" />
        </a>&nbsp;
        <span class="click" data-action="delete-feed" data-feed-id="<?php echo $row['NfID']; ?>">
            <img src="/assets/icons/minus-button.png" title="Delete" alt="Delete" />
        </span>
    </td>
    <td class="td1 center"><?php echo tohtml($row['NfName']); ?></td>
    <td class="td1 center"><?php echo str_replace(',', ', ', $row['NfOptions']); ?></td>
    <td class="td1 center" sorttable_customkey="<?php echo $diff; ?>">
        <?php if ($row['NfUpdate']) { echo $feedService->formatLastUpdate($diff); } ?>
    </td>
</tr>
<?php endwhile; ?>
</table>
</form>
<?php if ($pages > 1): ?>
<form name="form3" method="get" action="">
<table class="tab2" cellspacing="0" cellpadding="5">
<tr><th class="th1" style="width:30%;"><?php echo $totalFeeds; ?></th>
<th class="th1"><?php makePager($currentPage, $pages, '/feeds', 'form3'); ?></th>
</tr></table>
</form>
<?php endif; ?>
<?php endif; ?>
