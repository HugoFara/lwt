<?php declare(strict_types=1);
/**
 * Word list filter form view
 *
 * Variables expected:
 * - $languages: Array of languages for filter dropdown
 * - $texts: Array of texts for filter dropdown
 * - $currentlang: Current language filter
 * - $currenttext: Current text filter
 * - $currenttexttag: Current text tag filter
 * - $currenttextmode: Current text/tag mode (0=text, 1=tag)
 * - $currentstatus: Current status filter
 * - $currentquery: Current search query
 * - $currentquerymode: Current query mode
 * - $currentregexmode: Current regex mode
 * - $currenttag1: First tag filter
 * - $currenttag2: Second tag filter
 * - $currenttag12: Tag logic (0=OR, 1=AND)
 * - $currentsort: Current sort option
 * - $currentpage: Current page number
 * - $recno: Total record count
 * - $pages: Total pages
 *
 * PHP version 8.1
 */

namespace Lwt\Views\Word;

use Lwt\View\Helper\SelectOptionsBuilder;
use Lwt\View\Helper\PageLayoutHelper;
use Lwt\View\Helper\IconHelper;

// Determine search mode label
$searchModeLabel = match($currentregexmode) {
    'r' => 'RegEx',
    'R' => 'RegEx(CS)',
    default => 'Wildcard (*)'
};
?>

<form name="form1" action="#" x-data="{ showAdvanced: false }">
    <div class="box mb-4">
        <!-- Header -->
        <div class="level mb-4">
            <div class="level-left">
                <div class="level-item">
                    <h3 class="title is-5 mb-0">
                        <span class="icon-text">
                            <span class="icon">
                                <?php echo IconHelper::render('filter', ['alt' => 'Filter']); ?>
                            </span>
                            <span>Filter</span>
                        </span>
                    </h3>
                </div>
            </div>
            <div class="level-right">
                <div class="level-item">
                    <div class="buttons are-small">
                        <button type="button"
                                class="button is-light"
                                @click="showAdvanced = !showAdvanced">
                            <span class="icon is-small">
                                <?php echo IconHelper::render('sliders', ['alt' => 'Advanced']); ?>
                            </span>
                            <span x-text="showAdvanced ? 'Simple' : 'Advanced'">Advanced</span>
                        </button>
                        <button type="button"
                                class="button is-warning is-light"
                                data-action="reset-all">
                            <span class="icon is-small">
                                <?php echo IconHelper::render('rotate-ccw', ['alt' => 'Reset']); ?>
                            </span>
                            <span>Reset All</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Primary Filters -->
        <div class="columns is-multiline">
            <!-- Language -->
            <div class="column is-half-tablet is-one-quarter-desktop">
                <div class="field">
                    <label class="label is-small">Language</label>
                    <div class="control">
                        <div class="select is-fullwidth is-small">
                            <select name="filterlang" data-action="filter-language">
                                <?php echo SelectOptionsBuilder::forLanguages($languages, $currentlang, '[Filter off]'); ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Text/Text Tag -->
            <div class="column is-half-tablet is-one-quarter-desktop">
                <div class="field">
                    <label class="label is-small">
                        <div class="select is-small" style="font-weight: normal;">
                            <select name="text_mode" data-action="text-mode">
                                <option value="0"<?php if ($currenttextmode == "0") echo ' selected'; ?>>Text</option>
                                <option value="1"<?php if ($currenttextmode == "1") echo ' selected'; ?>>Text Tag</option>
                            </select>
                        </div>
                    </label>
                    <div class="control">
                        <div class="select is-fullwidth is-small">
                            <select name="text" data-action="filter-text">
                                <?php echo ($currenttextmode != 1) ? (SelectOptionsBuilder::forTexts($texts, $currenttext, false)) : (\Lwt\Services\TagService::getTextTagSelectOptionsWithTextIds($currentlang, $currenttexttag)); ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="column is-half-tablet is-one-quarter-desktop">
                <div class="field">
                    <label class="label is-small">Status</label>
                    <div class="control">
                        <div class="select is-fullwidth is-small">
                            <select name="status" data-action="filter-status">
                                <?php echo SelectOptionsBuilder::forWordStatus($currentstatus, true, false); ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search -->
            <div class="column is-half-tablet is-one-quarter-desktop">
                <div class="field">
                    <label class="label is-small">
                        Search
                        <span class="tag is-light is-small ml-1"><?php echo $searchModeLabel; ?></span>
                    </label>
                    <div class="field has-addons">
                        <div class="control is-expanded">
                            <input type="text"
                                   name="query"
                                   class="input is-small"
                                   value="<?php echo htmlspecialchars($currentquery ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                   maxlength="50"
                                   placeholder="Search terms..." />
                        </div>
                        <div class="control">
                            <button type="button"
                                    name="querybutton"
                                    class="button is-info is-small"
                                    data-action="filter-query">
                                <span class="icon is-small">
                                    <?php echo IconHelper::render('search', ['alt' => 'Search']); ?>
                                </span>
                            </button>
                        </div>
                        <div class="control">
                            <button type="button"
                                    class="button is-light is-small"
                                    data-action="clear-query">
                                <span class="icon is-small">
                                    <?php echo IconHelper::render('x', ['alt' => 'Clear']); ?>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advanced Filters -->
        <div x-show="showAdvanced" x-transition x-cloak class="mt-4 pt-4" style="border-top: 1px solid #dbdbdb;">
            <div class="columns is-multiline">
                <!-- Query Mode -->
                <div class="column is-half-tablet is-one-quarter-desktop">
                    <div class="field">
                        <label class="label is-small">Search In</label>
                        <div class="control">
                            <div class="select is-fullwidth is-small">
                                <select name="query_mode" data-action="query-mode">
                                    <option value="term,rom,transl"<?php if ($currentquerymode == "term,rom,transl") echo ' selected'; ?>>Term, Rom., Transl.</option>
                                    <option disabled>------------</option>
                                    <option value="term"<?php if ($currentquerymode == "term") echo ' selected'; ?>>Term</option>
                                    <option value="rom"<?php if ($currentquerymode == "rom") echo ' selected'; ?>>Romanization</option>
                                    <option value="transl"<?php if ($currentquerymode == "transl") echo ' selected'; ?>>Translation</option>
                                    <option disabled>------------</option>
                                    <option value="term,rom"<?php if ($currentquerymode == "term,rom") echo ' selected'; ?>>Term, Rom.</option>
                                    <option value="term,transl"<?php if ($currentquerymode == "term,transl") echo ' selected'; ?>>Term, Transl.</option>
                                    <option value="rom,transl"<?php if ($currentquerymode == "rom,transl") echo ' selected'; ?>>Rom., Transl.</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tag #1 -->
                <div class="column is-half-tablet is-one-quarter-desktop">
                    <div class="field">
                        <label class="label is-small">Tag #1</label>
                        <div class="control">
                            <div class="select is-fullwidth is-small">
                                <select name="tag1" data-action="filter-tag1">
                                    <?php echo \Lwt\Services\TagService::getTermTagSelectOptions($currenttag1, $currentlang); ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tag Logic -->
                <div class="column is-half-tablet is-one-quarter-desktop">
                    <div class="field">
                        <label class="label is-small">Tag Logic</label>
                        <div class="control">
                            <div class="select is-fullwidth is-small">
                                <select name="tag12" data-action="filter-tag12">
                                    <?php echo SelectOptionsBuilder::forAndOr($currenttag12); ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tag #2 -->
                <div class="column is-half-tablet is-one-quarter-desktop">
                    <div class="field">
                        <label class="label is-small">Tag #2</label>
                        <div class="control">
                            <div class="select is-fullwidth is-small">
                                <select name="tag2" data-action="filter-tag2">
                                    <?php echo \Lwt\Services\TagService::getTermTagSelectOptions($currenttag2, $currentlang); ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($recno > 0) { ?>
        <!-- Results Summary & Pagination -->
        <div class="level mt-4 pt-4" style="border-top: 1px solid #dbdbdb;">
            <div class="level-left">
                <div class="level-item">
                    <span class="tag is-info is-medium">
                        <?php echo $recno; ?> Term<?php echo ($recno == 1 ? '' : 's'); ?>
                    </span>
                </div>
            </div>
            <div class="level-item">
                <?php PageLayoutHelper::buildPager($currentpage, $pages, '/words/edit', 'form1'); ?>
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
                                    <?php echo SelectOptionsBuilder::forWordSort($currentsort); ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>
</form>
