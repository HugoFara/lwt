<?php declare(strict_types=1);
/**
 * Archived Text List View - Display list of archived texts with filtering
 *
 * Variables expected:
 * - $message: string - Status/error message to display
 * - $texts: array - Array of archived text records
 * - $totalCount: int - Total number of archived texts matching filter
 * - $pagination: array - Array with 'pages', 'currentPage', 'limit'
 * - $languages: array - Array of languages for select dropdown
 * - $currentLang: string - Current language filter
 * - $currentQuery: string - Current filter query
 * - $currentQueryMode: string - Current query mode
 * - $currentRegexMode: string - Current regex mode
 * - $currentSort: int - Current sort index
 * - $currentTag1: string|int - First tag filter
 * - $currentTag2: string|int - Second tag filter
 * - $currentTag12: string - AND/OR operator
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

namespace Lwt\Views\Text;

use Lwt\View\Helper\IconHelper;
use Lwt\View\Helper\PageLayoutHelper;

/** @var string $message */
/** @var array $texts */
/** @var int $totalCount */
/** @var array $pagination */
/** @var string $currentLang */
/** @var string $currentQuery */
/** @var string $currentQueryMode */
/** @var string $currentRegexMode */
/** @var int $currentSort */
/** @var string|int $currentTag1 */
/** @var string|int $currentTag2 */
/** @var string $currentTag12 */

PageLayoutHelper::renderMessage($message, false);

echo PageLayoutHelper::buildActionCard([
    ['url' => '/texts?new=1', 'label' => 'New Text', 'icon' => 'circle-plus', 'class' => 'is-primary'],
    ['url' => '/text/import-long', 'label' => 'Long Text Import', 'icon' => 'file-up'],
    ['url' => '/feeds?page=1&check_autoupdate=1', 'label' => 'Newsfeed Import', 'icon' => 'rss'],
    ['url' => '/texts?query=&page=1', 'label' => 'Active Texts', 'icon' => 'book-open'],
]);
?>

<!-- TODO: Make this search bar functional once the UI refactoring of this page is done.
     This search bar should support:
     - Universal search across title and text content
     - Filter chips for active filters (language, tags)
     - Autocomplete suggestions
     - Advanced filter toggle for power users
-->
<form name="form1" action="#" data-base-url="/text/archived" data-search-placeholder="archived-texts">
    <div class="box mb-4">
        <div class="field has-addons">
            <div class="control is-expanded has-icons-left">
                <input type="text"
                       name="query"
                       class="input"
                       value="<?php echo htmlspecialchars($currentQuery ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="Search archived texts... (e.g., lang:Spanish tag:news title)"
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

        <?php if ($totalCount > 0): ?>
        <!-- Results Summary & Pagination -->
        <div class="level mt-4 pt-4" style="border-top: 1px solid #dbdbdb;">
            <div class="level-left">
                <div class="level-item">
                    <span class="tag is-info is-medium">
                        <?php echo $totalCount; ?> Text<?php echo $totalCount == 1 ? '' : 's'; ?>
                    </span>
                </div>
            </div>
            <div class="level-item">
                <?php echo \Lwt\View\Helper\PageLayoutHelper::buildPager($pagination['currentPage'], $pagination['pages'], '/text/archived', 'form1'); ?>
            </div>
            <div class="level-right">
                <div class="level-item">
                    <div class="field has-addons">
                        <div class="control">
                            <span class="button is-static is-small">Sort</span>
                        </div>
                        <div class="control">
                            <div class="select is-small">
                                <select name="sort" data-action="sort">
                                    <?php echo \Lwt\View\Helper\SelectOptionsBuilder::forTextSort($currentSort); ?>
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

<?php if ($totalCount == 0): ?>
<p>No archived texts found.</p>
<?php else: ?>
<form name="form2" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<input type="hidden" name="data" value="" />
<table class="tab2" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1" colspan="2">
            Multi Actions
            <?php echo IconHelper::render('zap', ['title' => 'Multi Actions', 'alt' => 'Multi Actions']); ?>
        </th>
    </tr>
    <tr>
        <td class="td1 center">
            <input type="button" value="Mark All" data-action="mark-toggle" data-mark-all="true" />
            <input type="button" value="Mark None" data-action="mark-toggle" data-mark-all="false" />
        </td>
        <td class="td1 center">
            Marked Texts:&nbsp;
            <select name="markaction" id="markaction" disabled="disabled" data-action="multi-action">
                <?php echo \Lwt\View\Helper\SelectOptionsBuilder::forMultipleArchivedTextsActions(); ?>
            </select>
        </td>
    </tr>
</table>

<table class="sortable tab2" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1 sorttable_nosort">Mark</th>
        <th class="th1 sorttable_nosort">Actions</th>
        <?php if ($currentLang == ''): ?>
        <th class="th1 clickable">Lang.</th>
        <?php endif; ?>
        <th class="th1 clickable">
            Title [Tags] / Audio:&nbsp;
            <?php echo IconHelper::render('volume-2', ['title' => 'With Audio', 'alt' => 'With Audio']); ?>, Src.Link:&nbsp;
            <?php echo IconHelper::render('link', ['title' => 'Source Link available', 'alt' => 'Source Link available']); ?>, Ann.Text:&nbsp;
            <?php echo IconHelper::render('check', ['title' => 'Annotated Text available', 'alt' => 'Annotated Text available']); ?>
        </th>
    </tr>
    <?php foreach ($texts as $record): ?>
    <tr>
        <td class="td1 center">
            <a name="rec<?php echo $record['AtID']; ?>">
            <input name="marked[]" class="markcheck" type="checkbox" value="<?php echo $record['AtID']; ?>" <?php echo \Lwt\View\Helper\FormHelper::checkInRequest($record['AtID'], 'marked'); ?> />
            </a>
        </td>
        <td nowrap="nowrap" class="td1 center">&nbsp;
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>?unarch=<?php echo $record['AtID']; ?>">
                <?php echo IconHelper::render('archive-restore', ['title' => 'Unarchive', 'alt' => 'Unarchive']); ?>
            </a>&nbsp;
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>?chg=<?php echo $record['AtID']; ?>">
                <?php echo IconHelper::render('file-pen', ['title' => 'Edit', 'alt' => 'Edit']); ?>
            </a>&nbsp;
            <span class="click" data-action="confirm-delete" data-url="<?php echo $_SERVER['PHP_SELF']; ?>?del=<?php echo $record['AtID']; ?>">
                <?php echo IconHelper::render('circle-minus', ['title' => 'Delete', 'alt' => 'Delete']); ?>
            </span>&nbsp;
        </td>
        <?php if ($currentLang == ''): ?>
        <td class="td1 center"><?php echo htmlspecialchars($record['LgName'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
        <?php endif; ?>
        <td class="td1 center">
            <?php echo htmlspecialchars($record['AtTitle'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
            <span class="smallgray2"><?php echo htmlspecialchars($record['taglist'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span> &nbsp;
            <?php if (isset($record['AtAudioURI']) && $record['AtAudioURI']): ?>
            <?php echo IconHelper::render('volume-2', ['title' => 'With Audio', 'alt' => 'With Audio']); ?>
            <?php endif; ?>
            <?php if (isset($record['AtSourceURI']) && $record['AtSourceURI']): ?>
            <a href="<?php echo $record['AtSourceURI']; ?>" target="_blank">
                <?php echo IconHelper::render('link', ['title' => 'Link to Text Source', 'alt' => 'Link to Text Source']); ?>
            </a>
            <?php endif; ?>
            <?php if ($record['annotlen']): ?>
            <?php echo IconHelper::render('check', ['title' => 'Annotated Text available', 'alt' => 'Annotated Text available']); ?>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<?php if ($pagination['pages'] > 1): ?>
<table class="tab2" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1" nowrap="nowrap">
            <?php echo $totalCount; ?> Text<?php echo $totalCount == 1 ? '' : 's'; ?>
        </th>
        <th class="th1" nowrap="nowrap">
            <?php echo \Lwt\View\Helper\PageLayoutHelper::buildPager($pagination['currentPage'], $pagination['pages'], '/text/archived', 'form2'); ?>
        </th>
    </tr>
</table>
<?php endif; ?>
</form>
<?php endif; ?>
