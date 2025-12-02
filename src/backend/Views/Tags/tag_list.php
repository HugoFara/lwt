<?php declare(strict_types=1);
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

use Lwt\View\Helper\IconHelper;
use Lwt\View\Helper\PageLayoutHelper;

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

PageLayoutHelper::renderMessage($message, false);

echo PageLayoutHelper::buildActionCard([
    ['url' => $baseUrl . '?new=1', 'label' => 'New ' . $tagTypeLabel . ' Tag', 'icon' => 'circle-plus', 'class' => 'is-primary'],
]);
?>

<!-- TODO: Make this search bar functional once the UI refactoring of this page is done.
     This search bar should support:
     - Search across tag text and comments
     - Autocomplete suggestions
-->
<form name="form1" action="#" data-search-placeholder="tags">
    <div class="box mb-4">
        <div class="field has-addons">
            <div class="control is-expanded has-icons-left">
                <input type="text"
                       name="query"
                       class="input"
                       value="<?php echo htmlspecialchars($currentQuery ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="Search tags..."
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
                        <?php echo $totalCount; ?> Tag<?php echo ($totalCount == 1 ? '' : 's'); ?>
                    </span>
                </div>
            </div>
            <div class="level-item">
                <?php echo \Lwt\View\Helper\PageLayoutHelper::buildPager($pagination['currentPage'], $pagination['pages'], $baseUrl, 'form1'); ?>
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
                                    <?php foreach ($sortOptions as $option): ?>
                                    <option value="<?php echo $option['value']; ?>"<?php echo $currentSort == $option['value'] ? ' selected="selected"' : ''; ?>><?php echo $option['text']; ?></option>
                                    <?php endforeach; ?>
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
<p>No tags found.</p>
<?php else: ?>
<form name="form2" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<input type="hidden" name="data" value="" />
<table class="tab2" cellspacing="0" cellpadding="5">
<tr><th class="th1 center" colspan="2">
Multi Actions <?php echo IconHelper::render('zap', ['title' => 'Multi Actions', 'alt' => 'Multi Actions']); ?>
</th></tr>
<tr><td class="td1 center" colspan="2">
<b>ALL</b> <?php echo ($totalCount == 1 ? '1 Tag' : $totalCount . ' Tags'); ?>:&nbsp;
<select name="allaction" data-action="all-action" data-recno="<?php echo $totalCount; ?>"><?php echo \Lwt\View\Helper\SelectOptionsBuilder::forAllTagsActions(); ?></select>
</td></tr>
<tr><td class="td1 center">
<input type="button" value="Mark All" data-action="mark-all" />
<input type="button" value="Mark None" data-action="mark-none" />
</td>
<td class="td1 center">Marked Tags:&nbsp;
<select name="markaction" id="markaction" disabled="disabled" data-action="mark-action"><?php echo \Lwt\View\Helper\SelectOptionsBuilder::forMultipleTagsActions(); ?></select>
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
        <input name="marked[]" type="checkbox" class="markcheck" value="<?php echo $tag['id']; ?>" <?php echo \Lwt\View\Helper\FormHelper::checkInRequest($tag['id'], 'marked'); ?> />
        </a>
    </td>
    <td class="td1 center" nowrap="nowrap">
        &nbsp;<a href="<?php echo $_SERVER['PHP_SELF']; ?>?chg=<?php echo $tag['id']; ?>"><?php echo IconHelper::render('file-pen', ['title' => 'Edit', 'alt' => 'Edit']); ?></a>&nbsp;
        <a class="confirmdelete" href="<?php echo $_SERVER['PHP_SELF']; ?>?del=<?php echo $tag['id']; ?>"><?php echo IconHelper::render('circle-minus', ['title' => 'Delete', 'alt' => 'Delete']); ?></a>&nbsp;
    </td>
    <td class="td1 center"><?php echo htmlspecialchars($tag['text'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
    <td class="td1 center"><?php echo htmlspecialchars($tag['comment'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
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
            <?php echo \Lwt\View\Helper\PageLayoutHelper::buildPager($pagination['currentPage'], $pagination['pages'], $baseUrl, 'form2'); ?>
        </th>
    </tr>
</table>
<?php endif; ?>
</form>
<?php endif; ?>
