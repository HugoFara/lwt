<?php declare(strict_types=1);
/**
 * Feeds Browse View - Main feeds index page
 *
 * Variables expected:
 * - $currentLang: int current language filter
 * - $currentQuery: string search query
 * - $currentQueryMode: string query mode (title,desc,text or title)
 * - $currentRegexMode: string regex mode setting
 * - $feeds: array of feed records
 * - $currentFeed: int current feed ID
 * - $recno: int total article count
 * - $currentPage: int current page number
 * - $currentSort: int current sort index
 * - $maxPerPage: int articles per page
 * - $pages: int total pages
 * - $articles: array of feed article records
 * - $feedTime: int|null last update timestamp
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

echo PageLayoutHelper::buildActionCard([
    ['url' => '/feeds/edit?new_feed=1', 'label' => 'New Feed', 'icon' => 'rss', 'class' => 'is-primary'],
    ['url' => '/feeds/edit?manage_feeds=1', 'label' => 'Manage Feeds', 'icon' => 'settings'],
    ['url' => '/texts?query=&page=1', 'label' => 'Active Texts', 'icon' => 'book-open'],
    ['url' => '/text/archived?query=&page=1', 'label' => 'Archived Texts', 'icon' => 'archive'],
]);
?>
<div x-data="feedBrowse({currentQuery: '<?php echo htmlspecialchars($currentQuery, ENT_QUOTES, 'UTF-8'); ?>', currentQueryMode: '<?php echo htmlspecialchars($currentQueryMode, ENT_QUOTES, 'UTF-8'); ?>'})">

<!-- NOTE: Search bar planned for future UI refactoring.
     Planned features:
     - Universal search across article title, description, and text
     - Filter chips for active filters (language, feed)
     - Autocomplete suggestions
     - Advanced filter toggle for power users
-->
<form name="form1" action="#" data-lwt-feed-browse="true" data-search-placeholder="feed-articles">
    <div class="box mb-4">
        <div class="field has-addons">
            <div class="control is-expanded has-icons-left">
                <input type="text"
                       name="query"
                       class="input"
                       value="<?php echo htmlspecialchars($currentQuery, ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="Search articles... (e.g., lang:Spanish feed:news title)"
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

<?php if (empty($feeds)): ?>
        <p class="mt-4">No feed available.</p>
    </div>
</form>
<?php return; endif; ?>

        <?php if ($recno > 0): ?>
        <!-- Results Summary & Pagination -->
        <div class="level mt-4 pt-4" style="border-top: 1px solid #dbdbdb;">
            <div class="level-left">
                <div class="level-item">
                    <span class="tag is-info is-medium">
                        <?php echo $recno; ?> Article<?php echo $recno == 1 ? '' : 's'; ?>
                    </span>
                </div>
            </div>
            <div class="level-item">
                <?php echo \Lwt\Shared\UI\Helpers\PageLayoutHelper::buildPager($currentPage, $pages, '/feeds', 'form1'); ?>
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

<?php if ($recno > 0): ?>
  <form name="form2" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" method="post">
  <table class="tab2" cellspacing="0" cellpadding="5">
  <tr><th class="th1" colspan="2">Multi Actions <?php echo IconHelper::render('zap', ['title' => 'Multi Actions', 'alt' => 'Multi Actions']); ?></th></tr>
  <tr><td class="td1 center feeds-filter-cell">
  <input type="button" value="Mark All" @click="markAll()" />
  <input type="button" value="Mark None" @click="markNone()" />
  </td><td class="td1 center">
  Marked Texts:&nbsp;
  <input id="markaction" type="submit" value="Get Marked Texts" />&nbsp;&nbsp;
  </td></tr></table>
  <table  class="tab2 sortable" cellspacing="0" cellpadding="5">
  <tr>
  <th class="th1 sorttable_nosort">Mark</th>
  <th class="th1 clickable">Articles</th>
  <th class="th1 sorttable_nosort">Link</th>
  <th class="th1 clickable feeds-date-col">Date</th>
  </tr>
    <?php foreach ($articles as $row): ?>
        <tr>
        <?php if ($row['TxID'] && empty($row['TxArchivedAt'])): ?>
            <td class="td1 center"><a href="/text/read?start=<?php echo $row['TxID']; ?>" >
            <?php echo \Lwt\Shared\UI\Helpers\IconHelper::render('book-open', ['title' => 'Read', 'alt' => '-']); ?></a>
        <?php elseif ($row['TxID'] && !empty($row['TxArchivedAt'])): ?>
            <td class="td1 center"><span title="archived"><?php echo IconHelper::render('circle-x', ['alt' => '-']); ?></span>
        <?php elseif (!empty($row['FlLink']) && str_starts_with((string)$row['FlLink'], ' ')): ?>
            <td class="td1 center">
            <span class="not_found" name="<?php echo $row['FlID']; ?>" title="download error" @click="handleNotFoundClick($event)"><?php echo IconHelper::render('alert-circle', ['alt' => '-']); ?></span>
        <?php else: ?>
            <td class="td1 center"><input type="checkbox" class="markcheck" name="marked_items[]" value="<?php echo $row['FlID']; ?>" />
        <?php endif; ?>
        </td>
            <td class="td1 center">
            <span title="<?php echo htmlentities((string)$row['FlDescription'], ENT_QUOTES, 'UTF-8', false); ?>"><b><?php echo $row['FlTitle']; ?></b></span>
        <?php if ($row['FlAudio']): ?>
            <a href="<?php echo $row['FlAudio']; ?>" @click.prevent="openPopup('<?php echo $row['FlAudio']; ?>', 'audio')" target="_blank" rel="noopener">
            <?php echo IconHelper::render('volume-2', ['alt' => 'Audio']); ?></a>
        <?php endif; ?>
        </td>
            <td class="td1 center valign-middle">
        <?php if (!empty($row['FlLink']) && !str_starts_with(trim((string)$row['FlLink']), '#')): ?>
            <a href="<?php echo trim((string)$row['FlLink']); ?>" title="<?php echo trim((string)$row['FlLink']); ?>" @click.prevent="openPopup('<?php echo trim((string)$row['FlLink']); ?>', 'external')" target="_blank" rel="noopener">
            <?php echo IconHelper::render('external-link', ['alt' => '-']); ?></a>
        <?php endif; ?>
        </td><td class="td1 center"><?php echo $row['FlDate']; ?></td></tr>
    <?php endforeach; ?>

    </table>
    </form>

<?php if ($pages > 1): ?>
    <form name="form3" method="get" action ="">
        <table class="tab2" cellspacing="0" cellpadding="5">
        <tr><th class="th1 feeds-filter-cell"><?php echo $recno; ?></th><th class="th1">
    <?php echo \Lwt\Shared\UI\Helpers\PageLayoutHelper::buildPager($currentPage, $pages, '/feeds', 'form3'); ?>
        </th></tr></table></form>
<?php endif; ?>
<?php else: ?>
<p>No articles found.</p>
<?php endif; ?>
</div>
<!-- Feed browse component: feeds/components/feed_browse_component.ts -->

