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

/**
 * @var array<int, array{NfID: int, NfLgID: int, NfName: string, NfSourceURI: string, NfArticleSectionTags: string, NfFilterTags: string, NfUpdate: int, NfOptions: string}> $feeds Feed data
 * @var int $currentLang Current language filter
 * @var array<int, array{id: int, name: string}> $languages Language records (transformed for SelectOptionsBuilder)
 * @var \Lwt\Modules\Feed\Application\FeedFacade $feedService Feed service
 */

?>
<div x-data="feedMultiLoad()">
<form name="form1" action="/feeds" data-auto-submit-button="querybutton">
<table class="table is-bordered" style="border-left: none;border-top: none; background-color:inherit">
<tr>
<th class="borderleft" colspan="2">Language:<select name="filterlang"
@change="handleLanguageFilter($event)">
    <?php echo \Lwt\Shared\UI\Helpers\SelectOptionsBuilder::forLanguages($languages, $currentLang, '[Filter off]'); ?>
</select>
</th>
<th class="borderright" colspan="2">
<input type="button" value="Mark All" @click="markAll()" />
<input type="button" value="Mark None" @click="markNone()" /></th>
</tr>
<tr>
<td colspan="4" style="padding-left: 0px;padding-right: 0px;border-bottom: none;width: 100%;border-left: none;background-color: transparent;"><table class="table is-bordered is-fullwidth sortable">
<tr>
<th class="sorttable_nosort">Mark</th>
<th class="clickable" colspan="2">Newsfeeds</th>
<th class="sorttable_numeric clickable">Last Update</th>
</tr>
    <?php
    $time = time();
    foreach ($feeds as $row):
        $diff = $time - $row['NfUpdate'];
    ?>
    <tr>
        <td class="has-text-centered">
            <input class="markcheck" type="checkbox" name="selected_feed[]" value="<?php echo $row['NfID']; ?>" checked="checked" />
        </td>
        <td class="has-text-centered" colspan="2"><?php echo htmlspecialchars($row['NfName'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
        <td class="has-text-centered" sorttable_customkey="<?php echo $diff; ?>">
            <?php if ($row['NfUpdate']) { echo $feedService->formatLastUpdate($diff); } ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</td>
</tr>
<tr>
<th class="borderleft" colspan="3"><input id="map" type="hidden" name="selected_feed" value="" />
<input type="hidden" name="load_feed" value="1" />
<button id="markaction" @click="collectAndSubmit()">Update Marked Newsfeeds</button></th>
<th class="borderright">
    <input type="button" value="Cancel" @click="cancel()" /></th></tr>
</table>
</form>
</div>
<!-- Feed multi-load component: feeds/components/feed_multi_load_component.ts -->
