<?php declare(strict_types=1);
/**
 * Word List View - Alpine.js SPA version
 *
 * This view provides a full reactive word list with:
 * - Client-side filtering, sorting, and pagination
 * - Inline editing of translations and romanizations
 * - Bulk selection and actions
 * - Mobile-responsive table/card views
 *
 * Variables expected:
 * - $currentlang: int - Currently selected language ID
 * - $perPage: int - Terms per page setting
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

namespace Lwt\Views\Word;

use Lwt\Shared\UI\Helpers\PageLayoutHelper;
use Lwt\Shared\UI\Helpers\IconHelper;

/** @var int $currentlang */
/** @var int $perPage */

?>

<?php
echo PageLayoutHelper::buildActionCard([
    ['url' => '/words/edit?new=1', 'label' => 'New Term', 'icon' => 'circle-plus', 'class' => 'is-primary'],
    ['url' => '/word/upload', 'label' => 'Import Terms', 'icon' => 'file-up'],
    ['url' => '/word/tags', 'label' => 'Term Tags', 'icon' => 'tags'],
]);
?>

<!-- Alpine.js container for word list -->
<div x-data="wordListApp()" x-init="init()" x-cloak x-effect="filters.lang; filterOptions.languages; updatePageTitle()">

    <!-- Loading state -->
    <div x-show="loading" class="has-text-centered py-6">
        <span class="icon is-large">
            <?php echo IconHelper::render('loader-2', ['class' => 'animate-spin', 'alt' => 'Loading']); ?>
        </span>
        <p class="mt-2">Loading terms...</p>
    </div>

    <!-- Filter bar -->
    <div x-show="!loading" class="box mb-4">
        <div class="columns is-multiline is-vcentered">
            <!-- Language filter -->
            <div class="column is-narrow">
                <div class="field has-addons">
                    <div class="control">
                        <span class="button is-static is-small">Language</span>
                    </div>
                    <div class="control">
                        <div class="select is-small">
                            <select x-model="filters.lang" @change="setFilter('lang', filters.lang)">
                                <option value="">All Languages</option>
                                <template x-for="lang in filterOptions.languages" :key="lang.id">
                                    <option :value="lang.id" x-text="lang.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Text filter (only when language is selected) -->
            <div class="column is-narrow" x-show="filters.lang && filterOptions.texts.length > 0">
                <div class="field has-addons">
                    <div class="control">
                        <span class="button is-static is-small">Text</span>
                    </div>
                    <div class="control">
                        <div class="select is-small">
                            <select x-model="filters.text_id" @change="setFilter('text_id', filters.text_id)">
                                <option value="">All Texts</option>
                                <template x-for="text in filterOptions.texts" :key="text.id">
                                    <option :value="text.id" x-text="text.title"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status filter -->
            <div class="column is-narrow">
                <div class="field has-addons">
                    <div class="control">
                        <span class="button is-static is-small">Status</span>
                    </div>
                    <div class="control">
                        <div class="select is-small">
                            <select x-model="filters.status" @change="setFilter('status', filters.status)">
                                <template x-for="status in filterOptions.statuses" :key="status.value">
                                    <option :value="status.value" x-text="status.label"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tag filters -->
            <div class="column is-narrow" x-show="filterOptions.tags.length > 0">
                <div class="field has-addons">
                    <div class="control">
                        <span class="button is-static is-small">Tag</span>
                    </div>
                    <div class="control">
                        <div class="select is-small">
                            <select x-model="filters.tag1" @change="setFilter('tag1', filters.tag1)">
                                <option value="">Any Tag</option>
                                <template x-for="tag in filterOptions.tags" :key="tag.id">
                                    <option :value="tag.id" x-text="tag.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sort -->
            <div class="column is-narrow">
                <div class="field has-addons">
                    <div class="control">
                        <span class="button is-static is-small">Sort</span>
                    </div>
                    <div class="control">
                        <div class="select is-small">
                            <select x-model="filters.sort" @change="setFilter('sort', parseInt(filters.sort))">
                                <template x-for="sort in filterOptions.sorts" :key="sort.value">
                                    <option :value="sort.value" x-text="sort.label"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search query -->
            <div class="column">
                <div class="field has-addons">
                    <div class="control is-expanded has-icons-left">
                        <input type="text"
                               class="input is-small"
                               placeholder="Search terms..."
                               x-model="filters.query"
                               @keyup.enter="setFilter('query', filters.query)"
                               @keyup.debounce.500ms="setFilter('query', filters.query)" />
                        <span class="icon is-left">
                            <?php echo IconHelper::render('search', ['alt' => 'Search']); ?>
                        </span>
                    </div>
                    <div class="control">
                        <button type="button" class="button is-small" @click="resetFilters()" title="Reset all filters">
                            <?php echo IconHelper::render('x', ['alt' => 'Reset']); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results summary -->
        <div class="level mt-3 pt-3" style="border-top: 1px solid #dbdbdb;" x-show="pagination.total > 0">
            <div class="level-left">
                <div class="level-item">
                    <span class="tag is-info is-medium" x-text="pagination.total + ' Term' + (pagination.total === 1 ? '' : 's')"></span>
                </div>
            </div>
            <div class="level-right">
                <div class="level-item">
                    <span class="has-text-grey is-size-7" x-text="'Page ' + pagination.page + ' of ' + pagination.total_pages"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- No results message -->
    <div x-show="!loading && words.length === 0" class="notification is-info is-light">
        <p>No terms found matching your filters. <a href="/words/edit?new=1">Create a new term</a> or adjust your filters.</p>
    </div>

    <!-- Multi Actions Section -->
    <div x-show="!loading && words.length > 0" class="box mb-4">
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
                            <strong>ALL</strong>&nbsp;<span x-text="pagination.total + ' Term' + (pagination.total === 1 ? '' : 's')"></span>
                        </span>
                    </div>
                    <div class="control">
                        <div class="select is-small">
                            <select @change="handleAllAction($event)">
                                <option value="">[ Choose Action ]</option>
                                <optgroup label="Status Changes">
                                    <option value="alls1">Set Status to 1</option>
                                    <option value="alls2">Set Status to 2</option>
                                    <option value="alls3">Set Status to 3</option>
                                    <option value="alls4">Set Status to 4</option>
                                    <option value="alls5">Set Status to 5</option>
                                    <option value="alls98">Set Status to Ignored</option>
                                    <option value="alls99">Set Status to Well Known</option>
                                    <option value="allspl1">Increment Status (+1)</option>
                                    <option value="allsmi1">Decrement Status (-1)</option>
                                </optgroup>
                                <optgroup label="Edits">
                                    <option value="alllower">Set to Lowercase</option>
                                    <option value="allcap">Capitalize</option>
                                    <option value="alladdtag">Add Tag</option>
                                    <option value="alldeltag">Remove Tag</option>
                                </optgroup>
                                <optgroup label="Danger Zone">
                                    <option value="alldel">Delete ALL</option>
                                </optgroup>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="field is-grouped is-grouped-multiline mt-3">
            <div class="control">
                <div class="buttons are-small">
                    <button type="button" class="button is-light" @click="markAll(true)">
                        <?php echo IconHelper::render('check-check', ['alt' => 'Mark All']); ?>
                        <span class="ml-1">Mark All</span>
                    </button>
                    <button type="button" class="button is-light" @click="markAll(false)">
                        <?php echo IconHelper::render('x', ['alt' => 'Mark None']); ?>
                        <span class="ml-1">Mark None</span>
                    </button>
                    <span x-show="getMarkedCount() > 0" class="tag is-warning ml-2" x-text="getMarkedCount() + ' selected'"></span>
                </div>
            </div>
            <div class="control">
                <div class="field has-addons">
                    <div class="control">
                        <span class="button is-static is-small">Marked Terms</span>
                    </div>
                    <div class="control">
                        <div class="select is-small">
                            <select :disabled="getMarkedCount() === 0" @change="handleMultiAction($event)">
                                <option value="">[ Choose Action ]</option>
                                <optgroup label="Status Changes">
                                    <option value="s1">Set Status to 1</option>
                                    <option value="s2">Set Status to 2</option>
                                    <option value="s3">Set Status to 3</option>
                                    <option value="s4">Set Status to 4</option>
                                    <option value="s5">Set Status to 5</option>
                                    <option value="s98">Set Status to Ignored</option>
                                    <option value="s99">Set Status to Well Known</option>
                                    <option value="spl1">Increment Status (+1)</option>
                                    <option value="smi1">Decrement Status (-1)</option>
                                    <option value="today">Set Today's Date</option>
                                </optgroup>
                                <optgroup label="Edits">
                                    <option value="lower">Set to Lowercase</option>
                                    <option value="cap">Capitalize</option>
                                    <option value="delsent">Clear Sentences</option>
                                    <option value="addtag">Add Tag</option>
                                    <option value="deltag">Remove Tag</option>
                                </optgroup>
                                <optgroup label="Export">
                                    <option value="exp">Export (Anki)</option>
                                    <option value="exptsv">Export (TSV)</option>
                                </optgroup>
                                <optgroup label="Other">
                                    <option value="review">Review Selection</option>
                                </optgroup>
                                <optgroup label="Danger Zone">
                                    <option value="del">Delete Selected</option>
                                </optgroup>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Desktop Table View -->
    <div class="table-container is-hidden-mobile" x-show="!loading && words.length > 0">
        <table class="table is-striped is-hoverable is-fullwidth">
            <thead>
                <tr>
                    <th class="has-text-centered" style="width: 3em;">Mark</th>
                    <th class="has-text-centered" style="width: 5em;">Act.</th>
                    <th x-show="!filters.lang">Lang.</th>
                    <th>Term / Romanization</th>
                    <th>Translation [Tags]</th>
                    <th class="has-text-centered" style="width: 3em;" title="Has valid sentence?">Se.?</th>
                    <th class="has-text-centered" style="width: 5em;">Stat./Days</th>
                    <th class="has-text-centered" style="width: 5em;">Score %</th>
                    <th class="has-text-centered" style="width: 5em;" x-show="filters.sort === 7" title="Word Count in Active Texts">WCnt Txts</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="word in words" :key="word.id">
                    <tr>
                        <!-- Checkbox -->
                        <td class="has-text-centered">
                            <input type="checkbox"
                                   class="markcheck"
                                   :checked="isMarked(word.id)"
                                   @change="toggleMark(word.id, $event.target.checked)" />
                        </td>

                        <!-- Actions -->
                        <td class="has-text-centered" style="white-space: nowrap;">
                            <div class="buttons are-small is-centered">
                                <a :href="'/words/edit?chg=' + word.id" class="button is-small is-ghost" title="Edit">
                                    <?php echo IconHelper::render('file-pen-line', ['title' => 'Edit', 'alt' => 'Edit']); ?>
                                </a>
                            </div>
                        </td>

                        <!-- Language (when showing all) -->
                        <td x-show="!filters.lang" class="has-text-centered" x-text="word.langName"></td>

                        <!-- Term / Romanization -->
                        <td>
                            <span :class="word.ttsClass" :dir="word.rightToLeft ? 'rtl' : 'ltr'">
                                <strong x-text="word.text"></strong>
                            </span>
                            <span class="has-text-grey"> / </span>
                            <!-- Inline edit for romanization -->
                            <template x-if="isEditing(word.id, 'romanization')">
                                <span class="inline-edit-container">
                                    <textarea class="textarea is-small"
                                              :data-edit-id="word.id"
                                              data-edit-field="romanization"
                                              x-model="editValue"
                                              @keydown.escape="cancelEdit()"
                                              @keydown.ctrl.enter="saveEdit()"
                                              rows="1"></textarea>
                                    <div class="buttons are-small mt-1">
                                        <button type="button" class="button is-small is-success" @click="saveEdit()" :disabled="editSaving">
                                            <?php echo IconHelper::render('check', ['alt' => 'Save']); ?>
                                        </button>
                                        <button type="button" class="button is-small" @click="cancelEdit()">
                                            <?php echo IconHelper::render('x', ['alt' => 'Cancel']); ?>
                                        </button>
                                    </div>
                                </span>
                            </template>
                            <template x-if="!isEditing(word.id, 'romanization')">
                                <span class="clickedit has-text-grey-dark"
                                      @click="startEdit(word.id, 'romanization')"
                                      x-text="getDisplayValue(word, 'romanization')"></span>
                            </template>
                        </td>

                        <!-- Translation [Tags] -->
                        <td>
                            <!-- Inline edit for translation -->
                            <template x-if="isEditing(word.id, 'translation')">
                                <span class="inline-edit-container">
                                    <textarea class="textarea is-small"
                                              :data-edit-id="word.id"
                                              data-edit-field="translation"
                                              x-model="editValue"
                                              @keydown.escape="cancelEdit()"
                                              @keydown.ctrl.enter="saveEdit()"
                                              rows="2"></textarea>
                                    <div class="buttons are-small mt-1">
                                        <button type="button" class="button is-small is-success" @click="saveEdit()" :disabled="editSaving">
                                            <?php echo IconHelper::render('check', ['alt' => 'Save']); ?>
                                        </button>
                                        <button type="button" class="button is-small" @click="cancelEdit()">
                                            <?php echo IconHelper::render('x', ['alt' => 'Cancel']); ?>
                                        </button>
                                    </div>
                                </span>
                            </template>
                            <template x-if="!isEditing(word.id, 'translation')">
                                <span class="clickedit"
                                      @click="startEdit(word.id, 'translation')"
                                      x-text="$markdown(getDisplayValue(word, 'translation'))"></span>
                            </template>
                            <span x-show="word.tags" class="has-text-grey is-size-7 ml-1" x-text="word.tags"></span>
                        </td>

                        <!-- Sentence OK -->
                        <td class="has-text-centered">
                            <template x-if="word.sentenceOk">
                                <span class="has-text-success" :title="word.sentence">
                                    <?php echo IconHelper::render('circle-check', ['alt' => 'Yes']); ?>
                                </span>
                            </template>
                            <template x-if="!word.sentenceOk">
                                <span class="has-text-danger" title="No valid sentence">
                                    <?php echo IconHelper::render('circle-x', ['alt' => 'No']); ?>
                                </span>
                            </template>
                        </td>

                        <!-- Status / Days -->
                        <td class="has-text-centered" :title="word.statusLabel">
                            <span class="tag is-light" x-text="word.statusAbbr + (word.status < 98 ? '/' + word.days : '')"></span>
                        </td>

                        <!-- Score -->
                        <td class="has-text-centered" style="white-space: nowrap;">
                            <span class="tag is-light" :class="getStatusClass(word.status)" x-text="formatScore(word.score)"></span>
                        </td>

                        <!-- Word count (for sort 7) -->
                        <td class="has-text-centered" x-show="filters.sort === 7" x-text="word.textsWordCount || 0"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Mobile Card View -->
    <div class="is-hidden-tablet" x-show="!loading && words.length > 0">
        <template x-for="word in words" :key="word.id">
            <div class="card mb-3">
                <div class="card-content">
                    <div class="level is-mobile mb-2">
                        <div class="level-left">
                            <div class="level-item">
                                <label class="checkbox">
                                    <input type="checkbox"
                                           class="markcheck"
                                           :checked="isMarked(word.id)"
                                           @change="toggleMark(word.id, $event.target.checked)" />
                                </label>
                            </div>
                            <div class="level-item">
                                <span :class="word.ttsClass" :dir="word.rightToLeft ? 'rtl' : 'ltr'">
                                    <strong class="is-size-5" x-text="word.text"></strong>
                                </span>
                            </div>
                        </div>
                        <div class="level-right">
                            <div class="level-item">
                                <div class="tags has-addons mb-0">
                                    <span class="tag is-light" x-text="word.statusAbbr"></span>
                                    <span class="tag is-light" :class="getStatusClass(word.status)" x-text="formatScore(word.score)"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Romanization (if exists) -->
                    <p x-show="word.romanization && word.romanization !== '*'" class="has-text-grey is-size-7 mb-1">
                        <span class="clickedit" @click="startEdit(word.id, 'romanization')" x-text="word.romanization"></span>
                    </p>

                    <!-- Translation -->
                    <p class="mb-2">
                        <!-- Inline edit for translation on mobile -->
                        <template x-if="isEditing(word.id, 'translation')">
                            <span class="inline-edit-container">
                                <textarea class="textarea is-small"
                                          :data-edit-id="word.id"
                                          data-edit-field="translation"
                                          x-model="editValue"
                                          @keydown.escape="cancelEdit()"
                                          @keydown.ctrl.enter="saveEdit()"
                                          rows="2"></textarea>
                                <div class="buttons are-small mt-1">
                                    <button type="button" class="button is-small is-success" @click="saveEdit()" :disabled="editSaving">Save</button>
                                    <button type="button" class="button is-small" @click="cancelEdit()">Cancel</button>
                                </div>
                            </span>
                        </template>
                        <template x-if="!isEditing(word.id, 'translation')">
                            <span class="clickedit" @click="startEdit(word.id, 'translation')" x-text="$markdown(getDisplayValue(word, 'translation'))"></span>
                        </template>
                    </p>

                    <div class="is-flex is-justify-content-space-between is-align-items-center">
                        <div class="tags">
                            <span x-show="!filters.lang && word.langName" class="tag is-info is-light" x-text="word.langName"></span>
                            <span x-show="word.tags" class="tag is-light" x-text="word.tags"></span>
                            <template x-if="word.sentenceOk">
                                <span class="tag is-success is-light" :title="word.sentence">
                                    <?php echo IconHelper::render('message-square', ['alt' => 'Has sentence']); ?>
                                </span>
                            </template>
                        </div>
                        <div class="buttons are-small">
                            <a :href="'/words/edit?chg=' + word.id" class="button is-small is-info is-light">
                                <?php echo IconHelper::render('file-pen-line', ['alt' => 'Edit']); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Pagination -->
    <nav class="level mt-4" x-show="!loading && pagination.total_pages > 1">
        <div class="level-left">
            <div class="level-item">
                <span class="tag is-info is-medium" x-text="pagination.total + ' Term' + (pagination.total === 1 ? '' : 's')"></span>
            </div>
        </div>
        <div class="level-right">
            <div class="level-item">
                <div class="buttons">
                    <button type="button"
                            class="button is-small"
                            :disabled="pagination.page <= 1"
                            @click="goToPage(1)"
                            title="First page">
                        <?php echo IconHelper::render('chevrons-left', ['alt' => 'First']); ?>
                    </button>
                    <button type="button"
                            class="button is-small"
                            :disabled="pagination.page <= 1"
                            @click="goToPage(pagination.page - 1)"
                            title="Previous page">
                        <?php echo IconHelper::render('chevron-left', ['alt' => 'Previous']); ?>
                    </button>
                    <span class="button is-static is-small" x-text="pagination.page + ' / ' + pagination.total_pages"></span>
                    <button type="button"
                            class="button is-small"
                            :disabled="pagination.page >= pagination.total_pages"
                            @click="goToPage(pagination.page + 1)"
                            title="Next page">
                        <?php echo IconHelper::render('chevron-right', ['alt' => 'Next']); ?>
                    </button>
                    <button type="button"
                            class="button is-small"
                            :disabled="pagination.page >= pagination.total_pages"
                            @click="goToPage(pagination.total_pages)"
                            title="Last page">
                        <?php echo IconHelper::render('chevrons-right', ['alt' => 'Last']); ?>
                    </button>
                </div>
            </div>
        </div>
    </nav>
</div>

<!-- Config for Alpine - pass active language and per-page setting -->
<script type="application/json" id="word-list-config"><?php echo json_encode([
    'activeLanguageId' => $currentlang,
    'perPage' => $perPage
], JSON_HEX_TAG | JSON_HEX_AMP); ?></script>

<style>
    .clickedit {
        cursor: pointer;
        border-bottom: 1px dotted #ccc;
    }
    .clickedit:hover {
        background-color: #f5f5f5;
    }
    .inline-edit-container {
        display: inline-block;
        min-width: 150px;
    }
    .inline-edit-container .textarea {
        min-height: 2em;
    }
</style>
