<?php declare(strict_types=1);
/**
 * Archived Text List View - Display grouped archived texts by language
 *
 * Variables expected:
 * - $message: string - Status/error message to display
 * - $activeLanguageId: int - Currently active language ID for default expansion
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

use Lwt\Shared\UI\Helpers\SelectOptionsBuilder;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;
use Lwt\Shared\UI\Helpers\IconHelper;

PageLayoutHelper::renderMessage($message, false);

echo PageLayoutHelper::buildActionCard([
    ['url' => '/texts?new=1', 'label' => 'New Text', 'icon' => 'circle-plus', 'class' => 'is-primary'],
    ['url' => '/text/import-long', 'label' => 'Long Text Import', 'icon' => 'file-up'],
    ['url' => '/feeds?page=1&check_autoupdate=1', 'label' => 'Newsfeed Import', 'icon' => 'rss'],
    ['url' => '/texts?query=&page=1', 'label' => 'Active Texts', 'icon' => 'book-open'],
]);
?>

<!-- Alpine.js container for grouped archived texts -->
<div x-data="archivedTextsGroupedApp()" x-init="init()" x-cloak>

    <!-- Loading state -->
    <div x-show="loading" class="has-text-centered py-6">
        <span class="icon is-large">
            <i data-lucide="loader-2" class="icon-spin"></i>
        </span>
        <p class="mt-2">Loading archived texts...</p>
    </div>

    <!-- Global sort control -->
    <div x-show="!loading && languages.length > 0" class="box mb-4">
        <div class="level">
            <div class="level-left">
                <div class="level-item">
                    <span class="has-text-weight-semibold" x-text="languages.reduce((sum, lang) => sum + lang.text_count, 0) + ' archived texts in ' + languages.length + ' language' + (languages.length === 1 ? '' : 's')"></span>
                </div>
            </div>
            <div class="level-right">
                <div class="level-item">
                    <div class="field has-addons">
                        <div class="control">
                            <span class="button is-static is-small">Sort</span>
                        </div>
                        <div class="control">
                            <div class="select is-small">
                                <select @change="handleSortChange($event)" aria-label="Sort texts by">
                                    <?php echo SelectOptionsBuilder::forTextSort(); ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Language sections -->
    <template x-for="lang in languages" :key="lang.id">
        <div class="card mb-4">
            <!-- Collapsible header -->
            <header class="card-header is-clickable" @click="toggleLanguage(lang.id)" style="user-select: none;">
                <p class="card-header-title">
                    <span x-text="lang.name"></span>
                    <span class="tag is-warning ml-2" x-text="lang.text_count + ' archived text' + (lang.text_count === 1 ? '' : 's')"></span>
                </p>
                <button class="card-header-icon" type="button"
                        :aria-label="isCollapsed(lang.id) ? 'Expand ' + lang.name + ' texts' : 'Collapse ' + lang.name + ' texts'"
                        :aria-expanded="!isCollapsed(lang.id)">
                    <span class="icon">
                        <i :data-lucide="isCollapsed(lang.id) ? 'chevron-right' : 'chevron-down'"></i>
                    </span>
                </button>
            </header>

            <!-- Content (texts for this language) -->
            <div class="card-content" x-show="!isCollapsed(lang.id)" x-collapse.duration.200ms>
                <!-- Loading state for this language -->
                <div x-show="isLoadingMore(lang.id) && getTextsForLanguage(lang.id).length === 0" class="has-text-centered py-4">
                    <span class="icon">
                        <i data-lucide="loader-2" class="icon-spin"></i>
                    </span>
                    <span class="ml-2">Loading...</span>
                </div>

                <!-- Per-language bulk actions -->
                <div x-show="getTextsForLanguage(lang.id).length > 0" class="level mb-4">
                    <div class="level-left">
                        <div class="level-item">
                            <div class="buttons are-small">
                                <button type="button" class="button" @click="markAll(lang.id, true)">
                                    <?php echo IconHelper::render('check-square', ['size' => 14]); ?>
                                    <span class="ml-1">Mark All</span>
                                </button>
                                <button type="button" class="button" @click="markAll(lang.id, false)">
                                    <?php echo IconHelper::render('square', ['size' => 14]); ?>
                                    <span class="ml-1">Mark None</span>
                                </button>
                                <span x-show="hasMarkedInLanguage(lang.id)" class="tag is-warning ml-2" x-text="getMarkedCount(lang.id) + ' selected'"></span>
                            </div>
                        </div>
                    </div>
                    <div class="level-right">
                        <div class="level-item">
                            <div class="field has-addons">
                                <div class="control">
                                    <span class="button is-static is-small">
                                        <?php echo IconHelper::render('zap', ['size' => 14]); ?>
                                        <span class="ml-1">Actions</span>
                                    </span>
                                </div>
                                <div class="control">
                                    <div class="select is-small">
                                        <select :disabled="!hasMarkedInLanguage(lang.id)" @change="handleMultiAction(lang.id, $event)" aria-label="Bulk actions for selected texts">
                                            <?php echo SelectOptionsBuilder::forMultipleArchivedTextsActions(); ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Text cards grid -->
                <div class="columns is-multiline text-cards archived-text-cards" x-show="getTextsForLanguage(lang.id).length > 0">
                    <template x-for="text in getTextsForLanguage(lang.id)" :key="text.id">
                        <div class="column is-4-desktop is-6-tablet is-12-mobile">
                            <div class="card text-card is-archived">
                                <header class="card-header">
                                    <label class="card-header-icon checkbox-wrapper" @click.stop>
                                        <input type="checkbox"
                                               class="markcheck"
                                               :aria-label="'Select ' + text.title"
                                               :checked="isMarked(lang.id, text.id)"
                                               @change="toggleMark(lang.id, text.id, $event.target.checked)" />
                                    </label>
                                    <p class="card-header-title" x-text="text.title"></p>
                                    <div class="card-header-icon card-icons">
                                        <span x-show="text.has_audio" title="With Audio">
                                            <?php echo IconHelper::render('volume-2', ['size' => 16]); ?>
                                        </span>
                                        <a x-show="text.has_source" :href="text.source_uri" target="_blank" title="Source Link" @click.stop>
                                            <?php echo IconHelper::render('external-link', ['size' => 16]); ?>
                                        </a>
                                        <span x-show="text.annotated" title="Annotated Text">
                                            <?php echo IconHelper::render('file-text', ['size' => 16]); ?>
                                        </span>
                                    </div>
                                </header>

                                <div class="card-content">
                                    <!-- Tags -->
                                    <div x-show="text.taglist" class="text-meta mb-3">
                                        <div class="tags" x-html="renderTags(text.taglist)"></div>
                                    </div>

                                    <!-- Archive Status Badge -->
                                    <div class="archive-badge">
                                        <span class="tag is-warning is-light">
                                            <?php echo IconHelper::render('archive', ['size' => 12]); ?>
                                            <span class="ml-1">Archived</span>
                                        </span>
                                    </div>
                                </div>

                                <footer class="card-footer">
                                    <a :href="'/text/archived?unarch=' + text.id" class="card-footer-item is-primary-action">
                                        <?php echo IconHelper::render('archive-restore', ['size' => 16]); ?>
                                        <span>Unarchive</span>
                                    </a>
                                    <a :href="'/text/archived?chg=' + text.id" class="card-footer-item">
                                        <?php echo IconHelper::render('file-pen', ['size' => 16]); ?>
                                        <span>Edit</span>
                                    </a>
                                    <a class="card-footer-item has-text-danger" @click.prevent="handleDelete($event, '/text/archived?del=' + text.id)">
                                        <?php echo IconHelper::render('trash-2', ['size' => 16]); ?>
                                        <span>Delete</span>
                                    </a>
                                </footer>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Per-language "Show More" pagination -->
                <div x-show="hasMoreTexts(lang.id)" class="has-text-centered mt-4">
                    <button type="button"
                            class="button is-info is-outlined"
                            @click="loadMoreTexts(lang.id)"
                            :class="{ 'is-loading': isLoadingMore(lang.id) }">
                        <span class="icon">
                            <i data-lucide="chevron-down"></i>
                        </span>
                        <span>Show More</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Empty state -->
    <div x-show="!loading && languages.length === 0" class="notification is-info is-light">
        <p>No archived texts found. Texts you archive will appear here.</p>
    </div>
</div>

<!-- Config for Alpine - pass active language for default expansion -->
<script type="application/json" id="archived-texts-grouped-config"><?php echo json_encode([
    'activeLanguageId' => $activeLanguageId
], JSON_HEX_TAG | JSON_HEX_AMP); ?></script>
