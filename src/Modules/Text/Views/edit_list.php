<?php

declare(strict_types=1);

/**
 * Active Text List View - Display texts for current language
 *
 * Variables expected:
 * - $message: string - Status/error message to display
 * - $statuses: array - Word status definitions
 * - $activeLanguageId: int - Currently selected language ID
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
 *
 * @var string $message
 * @var array<int, array{status: int, label: string}> $statuses
 * @var int $activeLanguageId
 */

namespace Lwt\Views\Text;

use Lwt\Shared\UI\Helpers\SelectOptionsBuilder;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;
use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\Infrastructure\Utilities\StringUtils;

// Type-safe variable extraction from controller context
/**
 * @var string $message
*/

?>
<link rel="stylesheet" type="text/css" href="<?php StringUtils::printFilePath('css/css_charts.css');?>" />

<?php \Lwt\Shared\UI\Helpers\PageLayoutHelper::renderMessage($message, false); ?>

<?php
echo PageLayoutHelper::buildActionCard(
    [
    ['url' => '/texts/new', 'label' => 'New Text', 'icon' => 'circle-plus', 'class' => 'is-primary'],
    ['url' => '/text/archived?query=&page=1', 'label' => 'Archived Texts', 'icon' => 'archive'],
    ]
);
?>

<!-- Alpine.js container for texts list -->
<div x-data="textsGroupedApp" x-cloak>

    <!-- Loading state -->
    <div x-show="loading" class="has-text-centered py-6">
        <span class="icon is-large">
            <i data-lucide="loader-2" class="icon-spin"></i>
        </span>
        <p class="mt-2">Loading texts...</p>
    </div>

    <!-- Sort control and summary -->
    <div x-show="!loading && texts.length > 0" class="box mb-4">
        <div class="level">
            <div class="level-left">
                <div class="level-item">
                    <span
                        class="has-text-weight-semibold"
                        x-text="summaryText"></span>
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

    <!-- Bulk actions -->
    <div x-show="!loading && texts.length > 0" class="level mb-4">
        <div class="level-left">
            <div class="level-item">
                <div class="buttons are-small">
                    <button type="button" class="button" @click="markAllTexts(true)">
                        <?php echo IconHelper::render('check-square', ['size' => 14]); ?>
                        <span class="ml-1">Mark All</span>
                    </button>
                    <button type="button" class="button" @click="markAllTexts(false)">
                        <?php echo IconHelper::render('square', ['size' => 14]); ?>
                        <span class="ml-1">Mark None</span>
                    </button>
                    <span
                        x-show="markedTexts.size > 0"
                        class="tag is-warning ml-2"
                        x-text="markedTexts.size + ' selected'"></span>
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
                            <select
                                :disabled="markedTexts.size === 0"
                                @change="handleMultiAction($event)"
                                aria-label="Bulk actions for selected texts">
                                <?php echo SelectOptionsBuilder::forMultipleTextsActions(); ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Text cards grid -->
    <div class="columns is-multiline text-cards" x-show="!loading && texts.length > 0">
        <template x-for="text in texts" :key="text.id">
            <div class="column is-4-desktop is-6-tablet is-12-mobile">
                <div class="card text-card">
                    <header class="card-header">
                        <label class="card-header-icon checkbox-wrapper" @click.stop>
                            <input type="checkbox"
                                   class="markcheck"
                                   :aria-label="'Select ' + text.title"
                                   :checked="isTextMarked(text.id)"
                                   :data-text-id="text.id"
                                   @change="toggleTextMark($event)" />
                        </label>
                        <p class="card-header-title" x-text="text.title"></p>
                        <div class="card-header-icon card-icons">
                            <span x-show="text.has_audio" title="With Audio">
                                <?php echo IconHelper::render('volume-2', ['size' => 16]); ?>
                            </span>
                            <a
                                x-show="text.has_source"
                                :href="text.source_uri"
                                target="_blank"
                                title="Source Link"
                                @click.stop>
                                <?php echo IconHelper::render('external-link', ['size' => 16]); ?>
                            </a>
                            <a
                                x-show="text.annotated"
                                :href="'/text/' + text.id + '/print'"
                                title="Annotated Text"
                                @click.stop>
                                <?php echo IconHelper::render('file-text', ['size' => 16]); ?>
                            </a>
                        </div>
                    </header>

                    <div class="card-content">
                        <!-- Tags -->
                        <div x-show="text.taglist" class="text-meta mb-3">
                            <div class="tags">
                                <template x-for="tag in parseTags(text.taglist)" :key="tag">
                                    <span class="tag is-info is-light is-small" x-text="tag"></span>
                                </template>
                            </div>
                        </div>

                        <!-- Word Statistics -->
                        <div class="text-stats">
                            <template x-if="getStatsForText(text.id)">
                                <div>
                                    <div class="stat-row">
                                        <div
                                            class="stat-item"
                                            title="Total number of unique words in this text">
                                            <span class="stat-label">Total</span>
                                            <span
                                                class="stat-value"
                                                x-text="getStatTotal(text.id)"></span>
                                        </div>
                                        <div
                                            class="stat-item"
                                            title="Words you have saved to your vocabulary">
                                            <span class="stat-label">Saved</span>
                                            <span class="stat-value">
                                                <a
                                                    class="status4"
                                                    :href="'/words/edit?page=1&query=&status=' +
                                                        '&tag12=0&tag2=&tag1=&text_mode=0&text=' +
                                                        text.id"
                                                    @click.stop
                                                    x-text="getStatSaved(text.id)"></a>
                                            </span>
                                        </div>
                                        <div
                                            class="stat-item"
                                            title="Words you haven't saved yet">
                                            <span class="stat-label">Unknown</span>
                                            <span
                                                class="stat-value status0"
                                                x-text="getStatUnknown(text.id)"></span>
                                        </div>
                                        <div
                                            class="stat-item"
                                            title="Percentage of unknown words">
                                            <span class="stat-label">Unkn.%</span>
                                            <span
                                                class="stat-value"
                                                x-text="getStatUnknownPercent(text.id)">
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Status distribution bar chart -->
                                    <div class="status-bar-chart">
                                        <template
                                            x-for="seg in getStatusSegments(text.id)"
                                            :key="seg.status">
                                            <div :class="'status-segment bc' + seg.status"
                                                 :style="'width: ' + seg.percent"
                                                 :title="seg.label"></div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            <template x-if="!getStatsForText(text.id)">
                                <div class="stat-row">
                                    <span class="has-text-grey is-size-7">Loading statistics...</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <footer class="card-footer">
                        <a :href="'/text/' + text.id + '/read'" class="card-footer-item is-primary-action">
                            <?php echo IconHelper::render('book-open', ['size' => 16]); ?>
                            <span>Read</span>
                        </a>
                        <a :href="'/review?text=' + text.id" class="card-footer-item">
                            <?php echo IconHelper::render('circle-help', ['size' => 16]); ?>
                            <span>Review</span>
                        </a>
                        <div class="card-footer-item has-dropdown" x-data="dropdownToggle">
                            <a @click.prevent.stop="toggle()" class="dropdown-trigger-link">
                                <?php echo IconHelper::render('more-horizontal', ['size' => 16]); ?>
                                <span>More</span>
                            </a>
                            <div
                                class="dropdown-menu card-dropdown"
                                x-show="open"
                                @click.outside="close()"
                                x-cloak>
                                <div class="dropdown-content">
                                    <a :href="'/text/' + text.id + '/print-plain'" class="dropdown-item">
                                        <?php echo IconHelper::render('printer', ['size' => 14]); ?>
                                        <span>Print</span>
                                    </a>
                                    <a href="#"
                                       class="dropdown-item"
                                       :data-url="'/texts/' + text.id + '/archive'"
                                       @click.prevent="handlePostActionFromEvent($event)">
                                        <?php echo IconHelper::render('archive', ['size' => 14]); ?>
                                        <span>Archive</span>
                                    </a>
                                    <a :href="'/texts/' + text.id + '/edit'" class="dropdown-item">
                                        <?php echo IconHelper::render('file-pen', ['size' => 14]); ?>
                                        <span>Edit</span>
                                    </a>
                                    <hr class="dropdown-divider">
                                    <a
                                        class="dropdown-item has-text-danger"
                                        :data-url="'/texts/' + text.id"
                                        @click.prevent="handleRestDeleteFromEvent($event)">
                                        <?php echo IconHelper::render('trash-2', ['size' => 14]); ?>
                                        <span>Delete</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </footer>
                </div>
            </div>
        </template>
    </div>

    <!-- Show More pagination -->
    <div x-show="!loading && hasMore" class="has-text-centered mt-4">
        <button type="button"
                class="button is-info is-outlined"
                @click="loadMore()"
                :class="{ 'is-loading': loadingMore }">
            <span class="icon">
                <i data-lucide="chevron-down"></i>
            </span>
            <span>Show More</span>
        </button>
    </div>

    <!-- Empty state -->
    <div x-show="!loading && texts.length === 0" class="notification is-info is-light">
        <p>No texts found for this language.
            <a href="<?php
                echo \Lwt\Shared\Infrastructure\Http\UrlUtilities::url('/texts/new');
            ?>">Create your first text</a> to get started!</p>
    </div>
</div>

<!-- Config for Alpine - pass statuses and active language -->
<script type="application/json" id="texts-grouped-config"><?php echo json_encode(
    [
    'statuses' => $statuses,
    'activeLanguageId' => $activeLanguageId
    ],
    JSON_HEX_TAG | JSON_HEX_AMP
); ?></script>
