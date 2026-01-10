<?php declare(strict_types=1);
/**
 * Feeds Management Index View
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
 * @var array<int, array{NfID: int, NfName: string, NfSourceURI: string, NfOptions: string, NfUpdate: int}> $feeds
 * @var int $currentLang
 * @var string $currentQuery
 * @var int $currentPage
 * @var int $currentSort
 * @var int $totalFeeds
 * @var int $pages
 * @var int $maxPerPage
 * @var \Lwt\Modules\Feed\Application\FeedFacade $feedService
 */
$feeds = $feeds ?? [];
$currentLang = $currentLang ?? 0;
$currentQuery = $currentQuery ?? '';
$currentPage = $currentPage ?? 1;
$currentSort = $currentSort ?? 1;
$totalFeeds = $totalFeeds ?? 0;
$pages = $pages ?? 1;
$maxPerPage = $maxPerPage ?? 50;

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
<table class="table is-bordered is-fullwidth">
<tr>
    <th class="" colspan="3">
        Multi Actions <?php echo IconHelper::render('zap', ['title' => 'Multi Actions', 'alt' => 'Multi Actions']); ?>
    </th>
</tr>
<tr><td class="has-text-centered feeds-filter-cell">
<input type="button" value="Mark All" @click="markAll()" />
<input type="button" value="Mark None" @click="markNone()" />
</td><td class="has-text-centered" colspan="2">Marked Newsfeeds:&nbsp;
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
<table class="table is-bordered is-fullwidth sortable">
<tr>
    <th class="sorttable_nosort">Mark</th>
    <th class="sorttable_nosort">Actions</th>
    <th class="clickable">Newsfeeds</th>
    <th class="sorttable_nosort">Options</th>
    <th class="sorttable_numeric clickable">Last Update</th>
</tr>
<?php
$time = time();
$feedsArr = $feeds ?? [];
/** @var array{NfID: int, NfName: string, NfSourceURI: string, NfOptions: string, NfUpdate: int} $row */
foreach ($feedsArr as $row):
    $diff = $time - (int)$row['NfUpdate'];
?>
<tr>
    <td class="has-text-centered">
        <input type="checkbox" name="marked[]" class="markcheck" value="<?php echo $row['NfID']; ?>" />
    </td>
    <td class="has-text-centered nowrap">
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
    <td class="has-text-centered"><?php echo htmlspecialchars($row['NfName'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
    <td class="has-text-centered"><?php echo str_replace(',', ', ', (string) ($row['NfOptions'] ?? '')); ?></td>
    <td class="has-text-centered" sorttable_customkey="<?php echo $diff; ?>">
        <?php if ($row['NfUpdate']) { echo $feedService->formatLastUpdate($diff); } ?>
    </td>
</tr>
<?php endforeach; ?>
</table>
</form>
<?php if ($pages > 1): ?>
<form name="form3" method="get" action="">
<table class="table is-bordered is-fullwidth">
<tr><th class="feeds-filter-cell"><?php echo $totalFeeds; ?></th>
<th class=""><?php echo \Lwt\Shared\UI\Helpers\PageLayoutHelper::buildPager($currentPage, $pages, '/feeds/edit', 'form3', ['query' => $currentQuery, 'sort' => $currentSort, 'manage_feeds' => 1]); ?></th>
</tr></table>
</form>
<?php endif; ?>
<?php endif; ?>
</div>
<!-- Feed index component: feeds/components/feed_index_component.ts -->
