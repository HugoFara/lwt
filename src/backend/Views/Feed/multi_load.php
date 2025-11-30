<?php declare(strict_types=1);
/**
 * Multi-Load Feeds View
 *
 * Variables expected:
 * - $feeds: array of feed data
 * - $currentLang: int current language filter
 * - $feedService: FeedService instance for utility methods
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
<form name="form1" action="/feeds" data-auto-submit-button="querybutton">
<table class="tab3" style="border-left: none;border-top: none; background-color:inherit" cellspacing="0" cellpadding="5">
<tr>
<th class="th1 borderleft" colspan="2">Language:<select name="filterlang"
data-action="filter-language"
data-url="/feeds/edit?multi_load_feed=1&amp;page=1">
    <?php echo get_languages_selectoptions($currentLang, '[Filter off]'); ?>
</select>
</th>
<th class="th1 borderright" colspan="2">
<input type="button" value="Mark All" data-action="mark-all" />
<input type="button" value="Mark None" data-action="mark-none" /></th>
</tr>
<tr>
<td colspan="4" style="padding-left: 0px;padding-right: 0px;border-bottom: none;width: 100%;border-left: none;background-color: transparent;"><table class="sortable tab2" cellspacing="0" cellpadding="5">
<tr>
<th class="th1 sorttable_nosort">Mark</th>
<th class="th1 clickable" colspan="2">Newsfeeds</th>
<th class="th1 sorttable_numeric clickable">Last Update</th>
</tr>
    <?php
    $time = time();
    foreach ($feeds as $row):
        $diff = $time - (int)$row['NfUpdate'];
    ?>
    <tr>
        <td class="td1 center">
            <input class="markcheck" type="checkbox" name="selected_feed[]" value="<?php echo $row['NfID']; ?>" checked="checked" />
        </td>
        <td class="td1 center" colspan="2"><?php echo htmlspecialchars($row['NfName'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
        <td class="td1 center" sorttable_customkey="<?php echo $diff; ?>">
            <?php if ($row['NfUpdate']) { echo $feedService->formatLastUpdate($diff); } ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</td>
</tr>
<tr>
<th class="th1 borderleft" colspan="3"><input id="map" type="hidden" name="selected_feed" value="" />
<input type="hidden" name="load_feed" value="1" />
<button id="markaction">Update Marked Newsfeeds</button></th>
<th class="th1 borderright">
    <input type="button" value="Cancel" data-action="cancel" data-url="/feeds?selected_feed=0" /></th></tr>
</table>
</form>
