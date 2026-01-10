<?php declare(strict_types=1);
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

use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

/**
 * @var array<int, array{NfID: int, NfLgID: int, NfName: string, NfSourceURI: string, NfArticleSectionTags: string, NfFilterTags: string, NfUpdate: int, NfOptions: string}> $feeds Feed data
 * @var int $currentLang Current language filter
 * @var string $currentQuery Search query
 * @var int $currentPage Current page number
 * @var int $currentSort Current sort index
 * @var int $totalFeeds Total number of feeds
 * @var int $pages Total pages
 * @var int $maxPerPage Feeds per page
 * @var \Lwt\Modules\Feed\Application\FeedFacade $feedService Feed service
 */

echo PageLayoutHelper::buildActionCard([
    ['url' => '/feeds', 'label' => 'Feeds', 'icon' => 'list'],
    ['url' => '/feeds/edit?new_feed=1', 'label' => 'New Feed', 'icon' => 'rss', 'class' => 'is-primary'],
]);
?>
<div x-data="feedIndex({currentQuery: '<?php echo htmlspecialchars($currentQuery, ENT_QUOTES, 'UTF-8'); ?>'})">
<!-- NOTE: Search bar planned for future UI refactoring.
     Planned features:
     - Search across feed names
     - Filter chips for language filter
     - Autocomplete suggestions
-->
<form name="form1" action="#" data-search-placeholder="feeds">
    <div class="box mb-4">
        <div class="field has-addons">
            <div class="control is-expanded has-icons-left">
                <input type="text"
                       name="query"
                       class="input"
                       value="<?php echo htmlspecialchars($currentQuery, ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="Search feeds... (e.g., lang:Spanish news)"
                       disabled />
                <span class="icon is-left">
                    <?php echo IconHelper::render('search', ['alt' => 'Search']); ?>
                </span>
            </div>
            <div class="control">
                <button type="button" class="button is-info" disabled>
                    Search
                </button>
            </div>
        </div>
        <p class="help has-text-grey">
            <?php echo IconHelper::render('info', ['alt' => 'Info', 'class' => 'icon-inline']); ?>
            Search functionality is being redesigned. Full filtering will be available soon.
        </p>

        <?php if ($totalFeeds > 0): ?>
        <!-- Results Summary & Pagination -->
        <div class="level mt-4 pt-4" style="border-top: 1px solid #dbdbdb;">
            <div class="level-left">
                <div class="level-item">
                    <span class="tag is-info is-medium">
                        <?php echo $totalFeeds; ?> Newsfeed<?php echo $totalFeeds == 1 ? '' : 's'; ?>
                    </span>
                </div>
            </div>
            <div class="level-item">
                <?php echo \Lwt\Shared\UI\Helpers\PageLayoutHelper::buildPager($currentPage, $pages, '/feeds/edit', 'form1', ['query' => $currentQuery, 'sort' => $currentSort, 'manage_feeds' => 1]); ?>
            </div>
            <div class="level-right">
                <div class="level-item">
                    <div class="field has-addons">
                        <div class="control">
                            <span class="button is-static is-small">Sort</span>
                        </div>
                        <div class="control">
                            <div class="select is-small">
                                <select name="sort" @change="handleSort($event)">
                                    <?php echo \Lwt\Shared\UI\Helpers\SelectOptionsBuilder::forTextSort($currentSort); ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</form>

<input id="map" type="hidden" name="selected_feed" value="" />
<?php if ($totalFeeds > 0): ?>
<form name="form2" action="" method="get">
<table class="tab2" cellspacing="0" cellpadding="5">
<tr>
    <th class="th1" colspan="3">
        Multi Actions <?php echo IconHelper::render('zap', ['title' => 'Multi Actions', 'alt' => 'Multi Actions']); ?>
    </th>
</tr>
<tr><td class="td1 center feeds-filter-cell">
<input type="button" value="Mark All" @click="markAll()" />
<input type="button" value="Mark None" @click="markNone()" />
</td><td class="td1 center" colspan="2">Marked Newsfeeds:&nbsp;
<select name="markaction" id="markaction" disabled="disabled" @change="handleMarkAction($event)">
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
</table>
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
foreach ($feeds as $row):
    $diff = $time - $row['NfUpdate'];
?>
<tr>
    <td class="td1 center">
        <input type="checkbox" name="marked[]" class="markcheck" value="<?php echo $row['NfID']; ?>" />
    </td>
    <td class="td1 center nowrap">
        <a href="/feeds/edit?edit_feed=1&amp;selected_feed=<?php echo $row['NfID']; ?>">
            <?php echo IconHelper::render('rss', ['title' => 'Edit', 'alt' => 'Edit']); ?>
        </a>
        &nbsp; <a href="/feeds/edit?manage_feeds=1&amp;load_feed=1&amp;selected_feed=<?php echo $row['NfID']; ?>">
            <span title="Update Feed"><?php echo IconHelper::render('refresh-cw', ['alt' => '-']); ?></span>
        </a>&nbsp;
        <a href="<?php echo $row['NfSourceURI']; ?>" data-action="open-window">
            <?php echo IconHelper::render('external-link', ['title' => 'Show Feed', 'alt' => 'Link']); ?>
        </a>&nbsp;
        <span class="click" @click="confirmDelete('<?php echo $row['NfID']; ?>')">
            <?php echo IconHelper::render('circle-minus', ['title' => 'Delete', 'alt' => 'Delete']); ?>
        </span>
    </td>
    <td class="td1 center"><?php echo htmlspecialchars($row['NfName'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
    <td class="td1 center"><?php echo str_replace(',', ', ', $row['NfOptions']); ?></td>
    <td class="td1 center" sorttable_customkey="<?php echo $diff; ?>">
        <?php if ($row['NfUpdate']) { echo $feedService->formatLastUpdate($diff); } ?>
    </td>
</tr>
<?php endforeach; ?>
</table>
</form>
<?php if ($pages > 1): ?>
<form name="form3" method="get" action="">
<table class="tab2" cellspacing="0" cellpadding="5">
<tr><th class="th1 feeds-filter-cell"><?php echo $totalFeeds; ?></th>
<th class="th1"><?php echo \Lwt\Shared\UI\Helpers\PageLayoutHelper::buildPager($currentPage, $pages, '/feeds/edit', 'form3', ['query' => $currentQuery, 'sort' => $currentSort, 'manage_feeds' => 1]); ?></th>
</tr></table>
</form>
<?php endif; ?>
<?php endif; ?>
</div>
<!-- Feed index component: feeds/components/feed_index_component.ts -->
