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

namespace Lwt\Modules\Tags\Views;

use Lwt\View\Helper\IconHelper;
use Lwt\View\Helper\PageLayoutHelper;
use Lwt\View\Helper\SelectOptionsBuilder;
use Lwt\View\Helper\FormHelper;

/** @var string $message */
/** @var array $tags */
/** @var int $totalCount */
/** @var array $pagination */
/** @var string $currentQuery */
/** @var int $currentSort */
/** @var \Lwt\Modules\Tags\Application\TagsFacade $service */
/** @var bool $isTextTag */

$baseUrl = $service->getBaseUrl();
$tagTypeLabel = $service->getTagTypeLabel();
$sortOptions = $service->getSortOptions();
$itemLabel = $isTextTag ? 'Texts' : 'Terms';

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
                       value="<?php echo htmlspecialchars($currentQuery, ENT_QUOTES, 'UTF-8'); ?>"
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
                <?php echo PageLayoutHelper::buildPager($pagination['currentPage'], $pagination['pages'], $baseUrl, 'form1'); ?>
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
<p class="has-text-grey">No tags found.</p>
<?php else: ?>
<form name="form2" action="<?php echo $baseUrl; ?>" method="post">
<input type="hidden" name="data" value="" />

<!-- Multi Actions Section -->
<div class="box mb-4">
    <div class="level is-mobile mb-3">
        <div class="level-left">
            <div class="level-item">
                <span class="icon-text">
                    <?php echo IconHelper::render('zap', ['title' => 'Multi Actions', 'alt' => 'Multi Actions']); ?>
                    <span class="has-text-weight-semibold ml-1">Multi Actions</span>
                </span>
            </div>
        </div>
    </div>

    <div class="field is-grouped is-grouped-multiline">
        <div class="control">
            <div class="field has-addons">
                <div class="control">
                    <span class="button is-static is-small">
                        <strong>ALL</strong>&nbsp;<?php echo ($totalCount == 1 ? '1 Tag' : $totalCount . ' Tags'); ?>
                    </span>
                </div>
                <div class="control">
                    <div class="select is-small">
                        <select name="allaction" data-action="all-action" data-recno="<?php echo $totalCount; ?>">
                            <?php echo SelectOptionsBuilder::forAllTagsActions(); ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="field is-grouped is-grouped-multiline mt-3">
        <div class="control">
            <div class="buttons are-small">
                <button type="button" class="button is-light" data-action="mark-all">
                    <?php echo IconHelper::render('check-check', ['alt' => 'Mark All']); ?>
                    <span class="ml-1">Mark All</span>
                </button>
                <button type="button" class="button is-light" data-action="mark-none">
                    <?php echo IconHelper::render('x', ['alt' => 'Mark None']); ?>
                    <span class="ml-1">Mark None</span>
                </button>
            </div>
        </div>
        <div class="control">
            <div class="field has-addons">
                <div class="control">
                    <span class="button is-static is-small">Marked Tags</span>
                </div>
                <div class="control">
                    <div class="select is-small">
                        <select name="markaction" id="markaction" disabled="disabled" data-action="mark-action">
                            <?php echo SelectOptionsBuilder::forMultipleTagsActions(); ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Desktop Table View -->
<div class="table-container is-hidden-mobile">
<table class="table is-striped is-hoverable is-fullwidth sortable">
<thead>
<tr>
    <th class="has-text-centered sorttable_nosort" style="width: 3em;">Mark</th>
    <th class="has-text-centered sorttable_nosort" style="width: 6em;">Actions</th>
    <th class="clickable">Tag Text</th>
    <th class="clickable">Tag Comment</th>
    <th class="has-text-centered clickable"><?php echo $itemLabel; ?> With Tag</th>
    <?php if ($isTextTag): ?>
    <th class="has-text-centered clickable">Arch. Texts With Tag</th>
    <?php endif; ?>
</tr>
</thead>
<tbody>
<?php foreach ($tags as $tag): ?>
<tr>
    <td class="has-text-centered">
        <a name="rec<?php echo $tag['id']; ?>">
            <input name="marked[]" type="checkbox" class="markcheck" value="<?php echo $tag['id']; ?>" <?php echo FormHelper::checkInRequest($tag['id'], 'marked'); ?> />
        </a>
    </td>
    <td class="has-text-centered" style="white-space: nowrap;">
        <div class="buttons are-small is-centered">
            <a href="<?php echo $baseUrl; ?>?chg=<?php echo $tag['id']; ?>" class="button is-small is-ghost" title="Edit">
                <?php echo IconHelper::render('file-pen', ['title' => 'Edit', 'alt' => 'Edit']); ?>
            </a>
            <a class="button is-small is-ghost confirmdelete" href="<?php echo $baseUrl; ?>?del=<?php echo $tag['id']; ?>" title="Delete">
                <?php echo IconHelper::render('circle-minus', ['title' => 'Delete', 'alt' => 'Delete']); ?>
            </a>
        </div>
    </td>
    <td>
        <span class="tag is-medium is-light"><?php echo htmlspecialchars($tag['text'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
    </td>
    <td class="has-text-grey"><?php echo htmlspecialchars($tag['comment'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
    <td class="has-text-centered">
        <?php if ($tag['usageCount'] > 0): ?>
        <a href="<?php echo $service->getItemsUrl($tag['id']); ?>" class="tag is-link is-light">
            <?php echo $tag['usageCount']; ?>
        </a>
        <?php else: ?>
        <span class="tag is-light">0</span>
        <?php endif; ?>
    </td>
    <?php if ($isTextTag): ?>
    <td class="has-text-centered">
        <?php $archivedCount = $tag['archivedUsageCount'] ?? 0; ?>
        <?php if ($archivedCount > 0): ?>
        <a href="<?php echo $service->getArchivedItemsUrl($tag['id']); ?>" class="tag is-link is-light">
            <?php echo $archivedCount; ?>
        </a>
        <?php else: ?>
        <span class="tag is-light">0</span>
        <?php endif; ?>
    </td>
    <?php endif; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- Mobile Card View -->
<div class="is-hidden-tablet">
<?php foreach ($tags as $tag): ?>
<div class="card mb-3">
    <div class="card-content">
        <div class="level is-mobile mb-2">
            <div class="level-left">
                <div class="level-item">
                    <label class="checkbox">
                        <input name="marked[]" type="checkbox" class="markcheck" value="<?php echo $tag['id']; ?>" <?php echo FormHelper::checkInRequest($tag['id'], 'marked'); ?> />
                    </label>
                </div>
                <div class="level-item">
                    <span class="tag is-medium is-info is-light"><?php echo htmlspecialchars($tag['text'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
            <div class="level-right">
                <div class="level-item">
                    <div class="buttons are-small">
                        <a href="<?php echo $baseUrl; ?>?chg=<?php echo $tag['id']; ?>" class="button is-small is-info is-light">
                            <?php echo IconHelper::render('file-pen', ['alt' => 'Edit']); ?>
                        </a>
                        <a class="button is-small is-danger is-light confirmdelete" href="<?php echo $baseUrl; ?>?del=<?php echo $tag['id']; ?>">
                            <?php echo IconHelper::render('circle-minus', ['alt' => 'Delete']); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($tag['comment'])): ?>
        <p class="has-text-grey mb-2"><?php echo htmlspecialchars($tag['comment'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <div class="is-flex is-flex-wrap-wrap" style="gap: 0.5rem;">
            <div class="tags has-addons mb-0">
                <span class="tag is-dark"><?php echo $itemLabel; ?></span>
                <?php if ($tag['usageCount'] > 0): ?>
                <a href="<?php echo $service->getItemsUrl($tag['id']); ?>" class="tag is-link"><?php echo $tag['usageCount']; ?></a>
                <?php else: ?>
                <span class="tag is-light">0</span>
                <?php endif; ?>
            </div>
            <?php if ($isTextTag): ?>
            <div class="tags has-addons mb-0">
                <span class="tag is-dark">Archived</span>
                <?php $archivedCountMobile = $tag['archivedUsageCount'] ?? 0; ?>
                <?php if ($archivedCountMobile > 0): ?>
                <a href="<?php echo $service->getArchivedItemsUrl($tag['id']); ?>" class="tag is-link"><?php echo $archivedCountMobile; ?></a>
                <?php else: ?>
                <span class="tag is-light">0</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<?php if ($pagination['pages'] > 1): ?>
<!-- Pagination -->
<nav class="level mt-4">
    <div class="level-left">
        <div class="level-item">
            <span class="tag is-info is-medium">
                <?php echo $totalCount; ?> Tag<?php echo ($totalCount == 1 ? '' : 's'); ?>
            </span>
        </div>
    </div>
    <div class="level-right">
        <div class="level-item">
            <?php echo PageLayoutHelper::buildPager($pagination['currentPage'], $pagination['pages'], $baseUrl, 'form2'); ?>
        </div>
    </div>
</nav>
<?php endif; ?>
</form>
<?php endif; ?>
