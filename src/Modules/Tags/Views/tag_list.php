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
 * - $service: TagsFacade instance
 * - $isTextTag: boolean - true for text tags, false for term tags
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 *
 * @psalm-suppress UndefinedVariable - Variables are set by the including controller
 */

declare(strict_types=1);

namespace Lwt\Modules\Tags\Views;

use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;
use Lwt\Shared\UI\Helpers\SelectOptionsBuilder;
use Lwt\Shared\UI\Helpers\FormHelper;
use Lwt\Modules\Tags\Application\TagsFacade;

/**
 * @var string $message
 * @var array<int, array{id: int, text: string, comment: string, usageCount: int, archivedUsageCount?: int}> $tags
 * @var int $totalCount
 * @var array{pages: int, currentPage: int, perPage: int} $pagination
 * @var string $currentQuery
 * @var int $currentSort
 * @var TagsFacade $service
 * @var bool $isTextTag
 */

// Ensure variables are properly typed for Psalm
assert(is_string($message));
assert(is_array($tags));
assert(is_int($totalCount));
assert(is_array($pagination));
assert(is_string($currentQuery));
assert(is_int($currentSort));
assert($service instanceof TagsFacade);
assert(is_bool($isTextTag));

$baseUrl = $service->getBaseUrl();
/** @var array<int, array{value: int, text: string}> $sortOptions */
$sortOptions = $service->getSortOptions();
$itemLabel = $isTextTag ? __('tags.items_label_texts') : __('tags.items_label_terms');
$newTagLabel = $isTextTag ? __('tags.list_new_text_tag') : __('tags.list_new_term_tag');

PageLayoutHelper::renderMessage($message, false);

echo PageLayoutHelper::buildActionCard([
    [
        'url' => $baseUrl . '/new',
        'label' => $newTagLabel,
        'icon' => 'circle-plus',
        'class' => 'is-primary'
    ],
]);
?>

<!-- NOTE: Search bar planned for future UI refactoring.
     Planned features:
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
                       placeholder="<?= htmlspecialchars(__('tags.list_search_placeholder'), ENT_QUOTES, 'UTF-8') ?>"
                       disabled />
                <span class="icon is-left">
                    <?php echo IconHelper::render('search', ['alt' => 'Search']); ?>
                </span>
            </div>
            <div class="control">
                <button type="button" class="button is-info" disabled>
                    <?= __('tags.list_search_button') ?>
                </button>
            </div>
        </div>
        <p class="help has-text-grey">
            <?php echo IconHelper::render('info', ['alt' => 'Info', 'class' => 'icon-inline']); ?>
            <?= __('tags.list_search_redesign_notice') ?>
        </p>

        <?php if ($totalCount > 0) : ?>
        <!-- Results Summary & Pagination -->
        <div class="level mt-4 pt-4" style="border-top: 1px solid #dbdbdb;">
            <div class="level-left">
                <div class="level-item">
                    <span class="tag is-info is-medium">
                        <?= $totalCount === 1
                            ? __('tags.list_count_one', ['count' => $totalCount])
                            : __('tags.list_count_many', ['count' => $totalCount]) ?>
                    </span>
                </div>
            </div>
            <div class="level-item">
                <?php
                echo PageLayoutHelper::buildPager(
                    $pagination['currentPage'],
                    $pagination['pages'],
                    $baseUrl,
                    'form1',
                    ['query' => $currentQuery, 'sort' => $currentSort]
                );
                ?>
            </div>
            <div class="level-right">
                <div class="level-item">
                    <div class="field has-addons">
                        <div class="control">
                            <span class="button is-static is-small"><?= __('tags.list_sort') ?></span>
                        </div>
                        <div class="control">
                            <div class="select is-small">
                                <select name="sort" data-action="sort">
                                    <?php foreach ($sortOptions as $option) : ?>
                                    <option
                                        value="<?php echo $option['value']; ?>"
                                        <?php echo $currentSort == $option['value'] ? ' selected="selected"' : ''; ?>
                                    ><?php echo $option['text']; ?></option>
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

<?php if ($totalCount == 0) : ?>
<p class="has-text-grey"><?= __('tags.list_no_tags') ?></p>
<?php else : ?>
<form name="form2" action="<?php echo $baseUrl; ?>" method="post">
    <?php echo \Lwt\Shared\UI\Helpers\FormHelper::csrfField(); ?>
<input type="hidden" name="data" value="" />

<!-- Multi Actions Section -->
<div class="box mb-4">
    <div class="level is-mobile mb-3">
        <div class="level-left">
            <div class="level-item">
                <span class="icon-text">
                    <?php echo IconHelper::render('zap', ['title' => 'Multi Actions', 'alt' => 'Multi Actions']); ?>
                    <span class="has-text-weight-semibold ml-1"><?= __('tags.list_multi_actions') ?></span>
                </span>
            </div>
        </div>
    </div>

    <div class="field is-grouped is-grouped-multiline">
        <div class="control">
            <div class="field has-addons">
                <div class="control">
                    <span class="button is-static is-small">
                        <strong><?= __('tags.list_all_label') ?></strong>&nbsp;<?= $totalCount === 1
                            ? __('tags.list_all_count_one')
                            : __('tags.list_all_count_many', ['count' => $totalCount]) ?>
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
                    <span class="ml-1"><?= __('tags.list_mark_all') ?></span>
                </button>
                <button type="button" class="button is-light" data-action="mark-none">
                    <?php echo IconHelper::render('x', ['alt' => 'Mark None']); ?>
                    <span class="ml-1"><?= __('tags.list_mark_none') ?></span>
                </button>
            </div>
        </div>
        <div class="control">
            <div class="field has-addons">
                <div class="control">
                    <span class="button is-static is-small"><?= __('tags.list_marked_tags') ?></span>
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
    <th class="has-text-centered sorttable_nosort" style="width: 3em;"><?= __('tags.list_col_mark') ?></th>
    <th class="has-text-centered sorttable_nosort" style="width: 6em;"><?= __('tags.list_col_actions') ?></th>
    <th class="clickable"><?= __('tags.list_col_text') ?></th>
    <th class="clickable"><?= __('tags.list_col_comment') ?></th>
    <th class="has-text-centered clickable"><?= $isTextTag
        ? __('tags.list_col_items_with_tag_texts')
        : __('tags.list_col_items_with_tag_terms') ?></th>
    <?php if ($isTextTag) : ?>
    <th class="has-text-centered clickable"><?= __('tags.list_col_archived_with_tag') ?></th>
    <?php endif; ?>
</tr>
</thead>
<tbody>
    <?php foreach ($tags as $tag) : ?>
<tr>
    <td class="has-text-centered">
        <a name="rec<?php echo $tag['id']; ?>">
            <input
                name="marked[]"
                type="checkbox"
                class="markcheck"
                value="<?php echo $tag['id']; ?>"
                <?php echo FormHelper::checkInRequest($tag['id'], 'marked'); ?>
            />
        </a>
    </td>
    <td class="has-text-centered" style="white-space: nowrap;">
        <div class="buttons are-small is-centered">
            <a
                href="<?php echo $baseUrl; ?>/<?php echo $tag['id']; ?>/edit"
                class="button is-small is-ghost"
                title="Edit"
            >
                <?php echo IconHelper::render('file-pen', ['title' => 'Edit', 'alt' => 'Edit']); ?>
            </a>
            <a
                class="button is-small is-ghost confirmdelete"
                href="<?php echo $baseUrl; ?>/<?php echo $tag['id']; ?>"
                data-method="delete"
                title="Delete"
            >
                <?php echo IconHelper::render('circle-minus', ['title' => 'Delete', 'alt' => 'Delete']); ?>
            </a>
        </div>
    </td>
    <td>
        <span class="tag is-medium is-light">
            <?php echo htmlspecialchars($tag['text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </span>
    </td>
    <td class="has-text-grey"><?php echo htmlspecialchars($tag['comment'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
    <td class="has-text-centered">
        <?php if ($tag['usageCount'] > 0) : ?>
        <a href="<?php echo $service->getItemsUrl($tag['id']); ?>" class="tag is-link is-light">
            <?php echo $tag['usageCount']; ?>
        </a>
        <?php else : ?>
        <span class="tag is-light">0</span>
        <?php endif; ?>
    </td>
        <?php if ($isTextTag) : ?>
    <td class="has-text-centered">
            <?php $archivedCount = $tag['archivedUsageCount'] ?? 0; ?>
            <?php if ($archivedCount > 0) : ?>
        <a href="<?php echo $service->getArchivedItemsUrl($tag['id']); ?>" class="tag is-link is-light">
                <?php echo $archivedCount; ?>
        </a>
            <?php else : ?>
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
    <?php foreach ($tags as $tag) : ?>
<div class="card mb-3">
    <div class="card-content">
        <div class="level is-mobile mb-2">
            <div class="level-left">
                <div class="level-item">
                    <label class="checkbox">
                        <input
                            name="marked[]"
                            type="checkbox"
                            class="markcheck"
                            value="<?php echo $tag['id']; ?>"
                            <?php echo FormHelper::checkInRequest($tag['id'], 'marked'); ?>
                        />
                    </label>
                </div>
                <div class="level-item">
                    <span class="tag is-medium is-info is-light">
                        <?php echo htmlspecialchars($tag['text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>
            </div>
            <div class="level-right">
                <div class="level-item">
                    <div class="buttons are-small">
                        <a
                            href="<?php echo $baseUrl; ?>/<?php echo $tag['id']; ?>/edit"
                            class="button is-small is-info is-light"
                        >
                            <?php echo IconHelper::render('file-pen', ['alt' => 'Edit']); ?>
                        </a>
                        <a
                            class="button is-small is-danger is-light confirmdelete"
                            href="<?php echo $baseUrl; ?>/<?php echo $tag['id']; ?>"
                            data-method="delete"
                        >
                            <?php echo IconHelper::render('circle-minus', ['alt' => 'Delete']); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($tag['comment'] !== '') : ?>
        <p class="has-text-grey mb-2"><?php echo htmlspecialchars($tag['comment'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <div class="is-flex is-flex-wrap-wrap" style="gap: 0.5rem;">
            <div class="tags has-addons mb-0">
                <span class="tag is-dark"><?php echo $itemLabel; ?></span>
                <?php if ($tag['usageCount'] > 0) : ?>
                <a href="<?php echo $service->getItemsUrl($tag['id']); ?>" class="tag is-link">
                    <?php echo $tag['usageCount']; ?>
                </a>
                <?php else : ?>
                <span class="tag is-light">0</span>
                <?php endif; ?>
            </div>
            <?php if ($isTextTag) : ?>
            <div class="tags has-addons mb-0">
                <span class="tag is-dark"><?= __('tags.label_archived') ?></span>
                <?php $archivedCountMobile = $tag['archivedUsageCount'] ?? 0; ?>
                <?php if ($archivedCountMobile > 0) : ?>
                <a href="<?php echo $service->getArchivedItemsUrl($tag['id']); ?>" class="tag is-link">
                    <?php echo $archivedCountMobile; ?>
                </a>
                <?php else : ?>
                <span class="tag is-light">0</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
    <?php endforeach; ?>
</div>

    <?php if ($pagination['pages'] > 1) : ?>
<!-- Pagination -->
<nav class="level mt-4">
    <div class="level-left">
        <div class="level-item">
            <span class="tag is-info is-medium">
                <?= $totalCount === 1
                    ? __('tags.list_count_one', ['count' => $totalCount])
                    : __('tags.list_count_many', ['count' => $totalCount]) ?>
            </span>
        </div>
    </div>
    <div class="level-right">
        <div class="level-item">
            <?php
            echo PageLayoutHelper::buildPager(
                $pagination['currentPage'],
                $pagination['pages'],
                $baseUrl,
                'form2',
                ['query' => $currentQuery, 'sort' => $currentSort]
            );
            ?>
        </div>
    </div>
</nav>
    <?php endif; ?>
</form>
<?php endif; ?>
