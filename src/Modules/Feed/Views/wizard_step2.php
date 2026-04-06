<?php

/**
 * Feed Wizard Step 2 - Select Article Text
 *
 * Variables expected:
 * - $wizardData: array wizard session data
 * - $feedLen: int number of feed items
 * - $feedHtml: string HTML content of the selected feed item
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Views\Feed;

use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Shared\UI\Helpers\IconHelper;

/**
 * @var array{rss_url: string, feed: array<int|string, mixed>, feed_title?: string,
 *     detected_feed?: string, article_tags?: string, host: array<string, string>,
 *     selected_feed: int, select_mode: string, hide_images: string, maxim: int,
 *     edit_feed?: int} $wizardData Wizard session data
 * @var int $feedLen Number of feed items
 * @var string $feedHtml HTML content of the selected feed item
 */

// Prepare feed items for JSON
$feedItemsJson = [];
for ($i = 0; $i < $feedLen; $i++) {
    $feedItem = $wizardData['feed'][$i] ?? null;
    if (!is_array($feedItem)) {
        continue;
    }
    $link = isset($feedItem['link']) && is_string($feedItem['link']) ? $feedItem['link'] : '';
    $feedHost = parse_url($link, PHP_URL_HOST) ?? '';
    $hostStatus = is_string($feedHost) ? ($wizardData['host'][$feedHost] ?? '-') : '-';
    $feedItemsJson[] = [
        'index' => $i,
        'title' => isset($feedItem['title']) && is_string($feedItem['title']) ? $feedItem['title'] : '',
        'link' => $link,
        'host' => is_string($feedHost) ? $feedHost : '',
        'hostStatus' => $hostStatus,
        'hasHtml' => isset($feedItem['html']) || $i == $wizardData['selected_feed']
    ];
}

// Prepare article sources
$articleSources = [];
$sources = ['description', 'encoded', 'content'];
foreach ($sources as $source) {
    if (isset($wizardData['feed'][0][$source])) {
        $articleSources[] = $source;
    }
}

// Map selection mode to typed value
$selectionModeMap = [
    '0' => 'smart',
    'all' => 'all',
    'adv' => 'adv'
];
$selectionMode = $selectionModeMap[$wizardData['select_mode']] ?? 'smart';

// Build JSON config
$configJson = json_encode([
    'rssUrl' => $wizardData['rss_url'] ?? '',
    'feedTitle' => $wizardData['feed']['feed_title'] ?? '',
    'feedText' => $wizardData['feed']['feed_text'] ?? '',
    'detectedFeed' => $wizardData['detected_feed'] ?? '',
    'feedItems' => $feedItemsJson,
    'selectedFeedIndex' => $wizardData['selected_feed'],
    'articleTags' => $wizardData['article_tags'] ?? '',
    'settings' => [
        'selectionMode' => $selectionMode,
        'hideImages' => $wizardData['hide_images'] === 'yes',
        'isMinimized' => $wizardData['maxim'] == 0
    ],
    'editFeedId' => $wizardData['edit_feed'] ?? null,
    'articleSources' => $articleSources,
    'multipleHosts' => count($wizardData['host'] ?? []) > 1
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<script type="application/json" id="wizard-step2-config"><?php echo $configJson; ?></script>

<div x-data="feedWizardStep2" x-cloak>
    <!-- Advanced Mode Panel (shown when minimized) -->
    <div id="adv" class="box mb-2" x-show="isMinimized" x-cloak>
        <div class="buttons">
            <button type="button" class="button is-small is-danger is-outlined" @click="cancel">
                <?php echo __e('feed.wizard.common.cancel'); ?>
            </button>
            <button type="button" class="button is-small is-info" @click="getAdvanced" x-show="store.isAdvancedOpen">
                <?php echo __e('feed.wizard.common.get'); ?>
            </button>
        </div>
        <!-- Advanced options will be rendered here when in advanced mode -->
        <template x-if="store.isAdvancedOpen">
            <div class="content">
                <p class="is-size-7 mb-2"><?php echo __e('feed.wizard.common.select_xpath_option'); ?></p>
                <template x-for="option in store.advancedOptions" :key="option.value">
                    <div class="field">
                        <label class="radio">
                            <input type="radio" name="adv_xpath"
                                   :value="option.value"
                                   @click="selectAdvancedOption(option.value)" />
                            <span x-text="option.label"></span>
                            <span class="tag is-small is-light"
                                  x-text="'(' + option.count + ' ' + $t('feed.wizard.common.matches') + ')'"></span>
                        </label>
                    </div>
                </template>
                <div class="field">
                    <label class="radio">
                        <input type="radio" name="adv_xpath" value="" @click="selectAdvancedOption('')" />
                        <?php echo __e('feed.wizard.common.custom'); ?>
                        <input type="text" class="input is-small" style="width: 300px;"
                               x-model="store.customXPath"
                               :class="{ 'is-danger': store.customXPath && !store.customXPathValid }" />
                    </label>
                </div>
                <div class="buttons mt-3">
                    <button type="button" class="button is-small" @click="cancelAdvanced">
                        <?php echo __e('feed.wizard.common.cancel'); ?>
                    </button>
                    <button type="button" class="button is-small is-info" @click="getAdvanced">
                        <?php echo __e('feed.wizard.common.get'); ?>
                    </button>
                </div>
            </div>
        </template>
    </div>

    <!-- Settings Modal -->
    <div class="modal" :class="settingsOpen ? 'is-active' : ''">
        <div class="modal-background" @click="settingsOpen = false"></div>
        <div class="modal-card">
            <header class="modal-card-head">
                <p class="modal-card-title">
                    <span class="icon mr-2">
                        <?php echo IconHelper::render('settings', ['alt' => __('feed.wizard.common.settings')]); ?>
                    </span>
                    <?php echo __e('feed.wizard.common.settings_title'); ?>
                </p>
                <button class="delete" aria-label="close" type="button" @click="settingsOpen = false"></button>
            </header>
            <section class="modal-card-body">
                <div class="field">
                    <label class="label"><?php echo __e('feed.wizard.common.selection_mode'); ?></label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select x-model="store.selectionMode" @change="changeSelectMode">
                                <option value="smart"><?php echo __e('feed.wizard.common.mode_smart'); ?></option>
                                <option value="all"><?php echo __e('feed.wizard.common.mode_all'); ?></option>
                                <option value="adv"><?php echo __e('feed.wizard.common.mode_advanced'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="field">
                    <label class="label"><?php echo __e('feed.wizard.common.hide_images'); ?></label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select x-model="store.hideImages" @change="changeHideImages">
                                <option :value="true"><?php echo __e('feed.wizard.common.yes'); ?></option>
                                <option :value="false"><?php echo __e('feed.wizard.common.no'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </section>
            <footer class="modal-card-foot">
                <button type="button" class="button is-success" @click="settingsOpen = false">
                    <?php echo __e('feed.wizard.common.ok'); ?>
                </button>
            </footer>
        </div>
    </div>

    <div id="lwt_container" x-show="!isMinimized">
        <!-- Steps indicator -->
        <div class="steps is-small mb-4">
            <div class="step-item is-completed is-success">
                <div class="step-marker">1</div>
                <div class="step-details">
                    <p class="step-title"><?php echo __e('feed.wizard.common.step_feed_url'); ?></p>
                </div>
            </div>
            <div class="step-item is-active is-primary">
                <div class="step-marker">2</div>
                <div class="step-details">
                    <p class="step-title"><?php echo __e('feed.wizard.common.step_select_article'); ?></p>
                </div>
            </div>
            <div class="step-item">
                <div class="step-marker">3</div>
                <div class="step-details">
                    <p class="step-title"><?php echo __e('feed.wizard.common.step_filter_text'); ?></p>
                </div>
            </div>
            <div class="step-item">
                <div class="step-marker">4</div>
                <div class="step-details">
                    <p class="step-title"><?php echo __e('feed.wizard.common.step_save'); ?></p>
                </div>
            </div>
        </div>

        <!-- Selected elements list -->
        <div class="box mb-4" style="background-color: var(--bulma-scheme-main-bis);">
            <p class="is-size-7 has-text-grey mb-2"><?php echo __e('feed.wizard.step2.selected_elements'); ?></p>
            <ol id="lwt_sel" class="ml-4">
                <template x-for="selector in articleSelectors" :key="selector.id">
                    <li class="is-flex is-align-items-center mb-1"
                        :class="{ 'has-text-weight-bold': selector.isHighlighted }">
                        <span class="is-family-monospace is-size-7" x-text="selector.xpath"
                              @click="toggleSelectorHighlight(selector.id)"
                              style="cursor: pointer;"></span>
                        <button type="button" class="delete is-small ml-2"
                                @click="deleteSelector(selector.id)"></button>
                    </li>
                </template>
            </ol>
            <!-- Hidden input for form submission -->
            <?php
            if (InputValidator::has('html')) {
                echo '<template x-if="articleSelectors.length === 0">';
                echo '<li>' . InputValidator::getString('html', '', false) . '</li>';
                echo '</template>';
            }
            if (InputValidator::has('article_tags') || InputValidator::has('edit_feed')) {
                echo '<template x-if="articleSelectors.length === 0">';
                echo '<li>' . ($wizardData['article_tags'] ?? '') . '</li>';
                echo '</template>';
            }
            ?>
        </div>

        <!-- Feed Info -->
        <div class="box">
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label"><?php echo __e('feed.wizard.common.name'); ?></label>
                </div>
                <div class="field-body">
                    <div class="field has-addons">
                        <div class="control is-expanded">
                            <input class="input notempty"
                                   type="text"
                                   name="NfName"
                                   x-model="feedName"
                                   required />
                        </div>
                        <div class="control">
                            <span class="icon has-text-danger"
                                  title="<?php echo __e('feed.wizard.common.field_required'); ?>">
                                <?php
                                echo IconHelper::render('asterisk', ['alt' => __('feed.wizard.common.required')]);
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label"><?php echo __e('feed.wizard.common.newsfeed_url'); ?></label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <p class="control">
                            <span class="has-text-grey-dark is-size-7" x-text="config.rssUrl"></span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label"><?php echo __e('feed.wizard.common.article_source'); ?></label>
                </div>
                <div class="field-body">
                    <div class="field has-addons">
                        <div class="control">
                            <div class="select">
                                <select name="NfArticleSection" x-model="articleSource" @change="changeArticleSection">
                                    <option value=""><?php echo __e('feed.wizard.common.webpage_link'); ?></option>
                                    <?php foreach ($articleSources as $source) : ?>
                                    <option value="<?php echo $source; ?>"><?php echo $source; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="control">
                            <span class="tag is-info is-light" x-text="'(' + config.detectedFeed + ')'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Wizard Controls -->
    <form name="lwt_form1" class="validate" action="/feeds/wizard" method="post">
        <?php echo \Lwt\Shared\UI\Helpers\FormHelper::csrfField(); ?>
        <nav class="level wizard-controls mt-4">
            <div class="level-left">
                <div class="level-item">
                    <input type="hidden" name="rss_url" :value="config.rssUrl" />
                    <button type="button" class="button is-danger is-outlined" @click="cancel">
                        <?php echo __e('feed.wizard.common.cancel'); ?>
                    </button>
                </div>
            </div>

            <div class="level-item">
                <div class="field has-addons">
                    <div class="control">
                        <div class="select">
                            <select name="selected_feed" x-model="selectedFeedIndex" @change="changeSelectedFeed">
                                <template x-for="item in config.feedItems" :key="item.index">
                                    <option :value="item.index"
                                            :title="item.title"
                                            x-text="(item.hasHtml ? '► ' : '- ') +
                                                    (item.index + 1) + ' ' +
                                                    item.hostStatus + ' host: ' + item.host">
                                    </option>
                                </template>
                            </select>
                        </div>
                    </div>
                    <input type="hidden" name="host_name" :value="config.feedItems[selectedFeedIndex]?.host || ''" />
                    <template x-if="config.multipleHosts">
                        <div class="control">
                            <div class="select">
                                <select name="host_status" x-model="hostStatus">
                                    <option value="-">-</option>
                                    <option value="☆">☆</option>
                                    <option value="★">★</option>
                                </select>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="level-item actions-cell">
                <div class="field has-addons">
                    <div class="control">
                        <div class="select">
                            <select name="mark_action" @change="handleMarkActionChange">
                                <option value=""><?php echo __e('feed.wizard.common.click_on_text'); ?></option>
                                <template x-for="option in markActionOptions" :key="option.value">
                                    <option :value="option.value" x-text="option.label"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                    <div class="control">
                        <button type="button" class="button is-info"
                                :disabled="!currentXPath"
                                @click="getSelection"><?php echo __e('feed.wizard.common.get'); ?></button>
                    </div>
                    <div class="control">
                        <button type="button" class="button" @click="settingsOpen = true">
                            <?php
                            $settingsLabel = __('feed.wizard.common.settings');
                            echo IconHelper::render(
                                'settings',
                                ['title' => $settingsLabel, 'alt' => $settingsLabel]
                            );
                            ?>
                        </button>
                    </div>
                </div>
            </div>

            <div class="level-right">
                <div class="level-item">
                    <div class="buttons">
                        <button type="button" class="button" @click="goBack">
                            <span class="icon is-small">
                                <?php
                                echo IconHelper::render('arrow-left', ['alt' => __('feed.wizard.common.back')]);
                                ?>
                            </span>
                            <span><?php echo __e('feed.wizard.common.back'); ?></span>
                        </button>
                        <button type="button" class="button is-primary"
                                :disabled="!canProceed"
                                @click="goNext">
                            <span><?php echo __e('feed.wizard.common.next'); ?></span>
                            <span class="icon is-small">
                                <?php
                                echo IconHelper::render('arrow-right', ['alt' => __('feed.wizard.common.next')]);
                                ?>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <button type="button" class="button is-small wizard-minmax mt-2" @click="toggleMinimize">
            <span class="icon is-small">
                <?php echo IconHelper::render('minimize-2', ['alt' => __('feed.wizard.common.min_max')]); ?>
            </span>
            <span><?php echo __e('feed.wizard.common.min_max'); ?></span>
        </button>

        <input type="hidden" name="step" value="2" />
        <input type="hidden" name="html" />
        <input type="hidden" name="article_tags" />
        <input type="hidden" name="maxim" :value="isMinimized ? '0' : '1'" />
        <input type="hidden" name="select_mode" :value="selectionMode" />
        <input type="hidden" name="hide_images" :value="hideImages ? 'yes' : 'no'" />
    </form>
</div>

<br /><p id="lwt_last"></p>
<?php echo $feedHtml; ?>
