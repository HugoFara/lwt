<?php

/**
 * Feed Wizard Step 1 - Choose How to Add a Feed
 *
 * Provides three paths:
 * 1. Browse Sources - Pick from curated feed registry
 * 2. Enter Feed URL - Guided wizard (steps 2-4)
 * 3. Manual Setup - Fill all fields directly
 *
 * Variables expected:
 * - $errorMessage: string|null error message to display
 * - $rssUrl: string|null previously entered RSS URL
 * - $editFeedId: int|null ID of feed being edited
 * - $languages: array of language data [{id, name}, ...]
 * - $curatedFeeds: array of curated feed groups [{language, languageName, sources: [...]}]
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

use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\FormHelper;

// Build JSON config for Alpine.js
/** @var array<int, array{id: int, name: string}> $languages */
$languages = $languages ?? [];
$languagesJson = array_map(
    function (array $lang): array {
        return ['id' => $lang['id'], 'name' => $lang['name']];
    },
    $languages
);

$configJson = json_encode([
    'rssUrl' => $rssUrl ?? '',
    'hasError' => !empty($errorMessage),
    'editFeedId' => $editFeedId ?? null,
    'languages' => $languagesJson,
    'curatedFeeds' => $curatedFeeds ?? [],
    'currentLanguageId' => $currentLanguageId ?? 0,
    'currentLanguageName' => $currentLanguageName ?? '',
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<script type="application/json" id="wizard-step1-config"><?php echo $configJson; ?></script>

<div x-data="feedWizardStep1" x-cloak>
    <?php if (!empty($errorMessage)) : ?>
    <div class="notification is-danger is-light">
        <span class="icon-text">
            <span class="icon">
                <?php echo IconHelper::render('alert-circle', ['alt' => 'Error']); ?>
            </span>
            <span><strong><?php echo __e('feed.wizard.step1.error_label'); ?></strong>
                <?php echo __e('feed.wizard.step1.error_check_uri'); ?></span>
        </span>
    </div>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <div class="tabs is-boxed is-medium mb-0">
        <ul>
            <li :class="{ 'is-active': activeTab === 'browse' }">
                <a @click.prevent="activeTab = 'browse'">
                    <span class="icon is-small">
                        <?php echo IconHelper::render('library', ['alt' => 'Browse']); ?>
                    </span>
                    <span><?php echo __e('feed.wizard.step1.tab_browse'); ?></span>
                </a>
            </li>
            <li :class="{ 'is-active': activeTab === 'wizard' }">
                <a @click.prevent="activeTab = 'wizard'">
                    <span class="icon is-small">
                        <?php echo IconHelper::render('wand-2', ['alt' => 'Wizard']); ?>
                    </span>
                    <span><?php echo __e('feed.wizard.step1.tab_wizard'); ?></span>
                </a>
            </li>
            <li :class="{ 'is-active': activeTab === 'manual' }">
                <a @click.prevent="activeTab = 'manual'">
                    <span class="icon is-small">
                        <?php echo IconHelper::render('settings', ['alt' => 'Manual']); ?>
                    </span>
                    <span><?php echo __e('feed.wizard.step1.tab_manual'); ?></span>
                </a>
            </li>
        </ul>
    </div>

    <!-- ===================== TAB 1: Browse Curated Sources ===================== -->
    <div class="box" x-show="activeTab === 'browse'" x-transition>
        <p class="mb-4 has-text-grey">
            <?php echo __e('feed.wizard.step1.browse_intro'); ?>
        </p>

        <!-- Language filter -->
        <div class="field is-grouped mb-4">
            <div class="control">
                <div class="select">
                    <select x-model="browseLanguageFilter">
                        <option value=""><?php echo __e('feed.wizard.step1.browse_all_languages'); ?></option>
                        <template x-for="group in curatedFeeds" :key="group.language">
                            <option :value="group.language" x-text="group.languageName"></option>
                        </template>
                    </select>
                </div>
            </div>
            <div class="control is-expanded">
                <input class="input" type="search"
                       placeholder="<?php echo __e('feed.wizard.step1.browse_search_placeholder'); ?>"
                       x-model="browseSearch" />
            </div>
        </div>

        <!-- Feed cards grouped by language -->
        <template x-if="filteredCuratedFeeds.length === 0">
            <div class="notification is-light">
                <?php echo __e('feed.wizard.step1.browse_no_match'); ?>
            </div>
        </template>

        <template x-for="group in filteredCuratedFeeds" :key="group.language">
            <div class="mb-5">
                <h3 class="title is-5 mb-3" x-text="group.languageName"></h3>
                <div class="columns is-multiline">
                    <template x-for="source in group.sources" :key="source.url">
                        <div class="column is-half-tablet is-one-third-desktop">
                            <label class="card" style="display: block; cursor: pointer;">
                                <div class="card-content p-4">
                                    <div class="is-flex is-align-items-center mb-2">
                                        <input type="checkbox"
                                               class="mr-2"
                                               x-model="selectedUrls"
                                               :value="source.url" />
                                        <p class="title is-6 mb-0" x-text="source.name"></p>
                                    </div>
                                    <div class="tags mb-2">
                                        <span class="tag is-info is-light" x-text="source.category"></span>
                                        <span class="tag is-light" x-text="source.level"></span>
                                    </div>
                                    <p class="is-size-7 has-text-grey is-clipped" x-text="source.url"
                                       style="max-height: 1.5em; overflow: hidden; text-overflow: ellipsis;"></p>
                                </div>
                            </label>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <!-- Add selected feeds button -->
        <div class="field is-grouped is-grouped-right mt-4">
            <div class="control">
                <button type="button" class="button is-primary" @click="addSelectedFeeds()">
                    <span class="icon is-small">
                        <?php echo IconHelper::render('plus', ['alt' => 'Add']); ?>
                    </span>
                    <span><?php echo __e('feed.wizard.step1.browse_add_selected'); ?></span>
                </button>
            </div>
        </div>

        <!-- Hidden form for curated feed submission -->
        <form id="curated-feed-form" action="/feeds/new" method="post" style="display: none;">
            <?php echo FormHelper::csrfField(); ?>
            <input type="hidden" name="save_feed" value="1" />
            <input type="hidden" name="NfLgID" x-bind:value="curatedFormData.NfLgID" />
            <input type="hidden" name="NfName" x-model="curatedFormData.NfName" />
            <input type="hidden" name="NfSourceURI" x-model="curatedFormData.NfSourceURI" />
            <input type="hidden" name="NfArticleSectionTags" x-model="curatedFormData.NfArticleSectionTags" />
            <input type="hidden" name="NfFilterTags" x-model="curatedFormData.NfFilterTags" />
            <input type="hidden" name="NfOptions" x-model="curatedFormData.NfOptions" />
        </form>
    </div>

    <!-- ===================== TAB 2: Wizard (Enter Feed URL) ===================== -->
    <div class="box" x-show="activeTab === 'wizard'" x-transition>
        <p class="mb-4 has-text-grey">
            <?php echo __e('feed.wizard.step1.wizard_intro'); ?>
        </p>

        <form class="validate" action="/feeds/wizard" method="post">
            <?php echo FormHelper::csrfField(); ?>
            <input type="hidden" name="step" value="2" />
            <input type="hidden" name="selected_feed" value="0" />
            <input type="hidden" name="article_tags" value="1" />

            <div class="field">
                <label class="label" for="rss_url">
                    <?php echo __e('feed.wizard.step1.feed_uri_label'); ?>
                    <span class="has-text-danger" title="<?php echo __e('feed.wizard.step1.required'); ?>">*</span>
                </label>
                <div class="control">
                    <input class="input notempty"
                           type="url"
                           name="rss_url"
                           id="rss_url"
                           placeholder="https://example.com/feed.xml"
                           x-model="rssUrl"
                           :class="{ 'is-success': isValidUrl, 'is-danger': rssUrl && !isValidUrl }"
                           required />
                </div>
                <p class="help"><?php echo __e('feed.wizard.step1.feed_uri_help'); ?></p>
            </div>

            <!-- Form Actions -->
            <div class="field is-grouped is-grouped-right mt-5">
                <div class="control">
                    <button type="button" class="button is-danger is-outlined" @click="cancel">
                        <?php echo __e('feed.wizard.step1.cancel'); ?>
                    </button>
                </div>
                <div class="control">
                    <button type="submit" class="button is-primary" :disabled="!isValidUrl">
                        <span><?php echo __e('feed.wizard.step1.next'); ?></span>
                        <span class="icon is-small">
                            <?php
                            echo IconHelper::render('arrow-right', ['alt' => __('feed.wizard.step1.next')]);
                            ?>
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ===================== TAB 3: Manual Setup ===================== -->
    <div class="box" x-show="activeTab === 'manual'" x-transition>
        <p class="mb-4 has-text-grey">
            <?php echo __e('feed.wizard.step1.manual_intro'); ?>
        </p>

        <script type="application/json" id="feed-form-config">
        <?php echo json_encode([
            'editText' => true,
            'autoUpdate' => false,
            'autoUpdateValue' => '',
            'autoUpdateUnit' => 'h',
            'maxLinks' => false,
            'maxLinksValue' => '',
            'charset' => false,
            'charsetValue' => '',
            'maxTexts' => false,
            'maxTextsValue' => '',
            'tag' => false,
            'tagValue' => '',
            'articleSource' => false,
            'articleSourceValue' => '',
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
        </script>
        <form class="validate" action="/feeds/new" method="post"
              x-data="feedForm"
              @submit="handleSubmit($event)">
            <?php echo FormHelper::csrfField(); ?>
            <input type="hidden" name="NfOptions" value="" />
            <input type="hidden" name="save_feed" value="1" />

            <div class="box">
                <input type="hidden" name="NfLgID" x-bind:value="currentLanguageId" />

                <!-- Name -->
                <div class="field">
                    <label class="label" for="manual_NfName">
                        <?php echo __e('feed.wizard.step1.name_label'); ?>
                        <span class="has-text-danger" title="<?php echo __e('feed.wizard.step1.required'); ?>">*</span>
                    </label>
                    <div class="control">
                        <input class="input notempty"
                               type="text"
                               name="NfName"
                               id="manual_NfName"
                               placeholder="<?php echo __e('feed.wizard.step1.name_placeholder'); ?>"
                               required />
                    </div>
                </div>

                <!-- Newsfeed URL -->
                <div class="field">
                    <label class="label" for="manual_NfSourceURI">
                        <?php echo __e('feed.wizard.step1.url_label'); ?>
                        <span class="has-text-danger" title="<?php echo __e('feed.wizard.step1.required'); ?>">*</span>
                    </label>
                    <div class="control">
                        <input class="input notempty"
                               type="url"
                               name="NfSourceURI"
                               id="manual_NfSourceURI"
                               placeholder="<?php echo __e('feed.wizard.step1.url_placeholder'); ?>"
                               required />
                    </div>
                </div>

                <!-- Article Section -->
                <div class="field">
                    <label class="label" for="manual_NfArticleSectionTags">
                        <?php echo __e('feed.wizard.step1.article_section_label'); ?>
                        <span class="has-text-danger" title="<?php echo __e('feed.wizard.step1.required'); ?>">*</span>
                    </label>
                    <div class="control">
                        <input class="input notempty"
                               type="text"
                               name="NfArticleSectionTags"
                               id="manual_NfArticleSectionTags"
                               placeholder="<?php echo __e('feed.wizard.step1.article_section_placeholder'); ?>"
                               required />
                    </div>
                </div>

                <!-- Filter Tags -->
                <div class="field">
                    <label class="label" for="manual_NfFilterTags">
                        <?php echo __e('feed.wizard.step1.filter_tags_label'); ?>
                    </label>
                    <div class="control">
                        <input class="input"
                               type="text"
                               name="NfFilterTags"
                               id="manual_NfFilterTags"
                               placeholder="<?php echo __e('feed.wizard.step1.filter_tags_placeholder'); ?>" />
                    </div>
                </div>

                <!-- Options Section -->
                <div class="field">
                    <label class="label"><?php echo __e('feed.wizard.step1.options_label'); ?></label>
                    <div class="box" style="background-color: var(--bulma-scheme-main-bis);">
                        <div class="columns is-multiline">
                            <!-- Edit Text -->
                            <div class="column is-half">
                                <label class="checkbox">
                                    <input type="checkbox" name="edit_text" x-model="editText" checked />
                                    <strong><?php echo __e('feed.wizard.step1.opt_review'); ?></strong>
                                </label>
                                <p class="help"><?php echo __e('feed.wizard.step1.opt_review_help'); ?></p>
                            </div>

                            <!-- Auto Update -->
                            <div class="column is-half">
                                <label class="checkbox">
                                    <input type="checkbox" name="c_autoupdate" x-model="autoUpdate" />
                                    <strong><?php echo __e('feed.wizard.step1.opt_auto_refresh'); ?></strong>
                                </label>
                                <div class="field has-addons mt-2" x-show="autoUpdate" x-transition>
                                    <div class="control">
                                        <input class="input is-small posintnumber"
                                               :class="autoUpdate ? 'notempty' : ''"
                                               type="number"
                                               min="1"
                                               name="autoupdate"
                                               data_info="Auto Update Interval"
                                               x-model="autoUpdateValue"
                                               style="width: 80px;"
                                               :disabled="!autoUpdate" />
                                    </div>
                                    <div class="control">
                                        <div class="select is-small">
                                            <select name="autoupdate_unit" x-model="autoUpdateUnit"
                                                :disabled="!autoUpdate">
                                                <option value="h">
                                                    <?php echo __e('feed.wizard.step1.opt_unit_hours'); ?>
                                                </option>
                                                <option value="d">
                                                    <?php echo __e('feed.wizard.step1.opt_unit_days'); ?>
                                                </option>
                                                <option value="w">
                                                    <?php echo __e('feed.wizard.step1.opt_unit_weeks'); ?>
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Max Links -->
                            <div class="column is-half">
                                <label class="checkbox">
                                    <input type="checkbox" name="c_max_links" x-model="maxLinks" />
                                    <strong><?php echo __e('feed.wizard.step1.opt_limit_articles'); ?></strong>
                                </label>
                                <p class="help"><?php echo __e('feed.wizard.step1.opt_limit_articles_help'); ?></p>
                                <div class="control mt-2" x-show="maxLinks" x-transition>
                                    <input class="input is-small posintnumber maxint_300"
                                           :class="maxLinks ? 'notempty' : ''"
                                           type="number"
                                           min="1"
                                           max="300"
                                           name="max_links"
                                           data_info="Max. Links"
                                           x-model="maxLinksValue"
                                           style="width: 100px;"
                                           :disabled="!maxLinks" />
                                </div>
                            </div>

                            <!-- Charset -->
                            <div class="column is-half">
                                <label class="checkbox">
                                    <input type="checkbox" name="c_charset" x-model="charset" />
                                    <strong><?php echo __e('feed.wizard.step1.opt_charset'); ?></strong>
                                </label>
                                <p class="help"><?php echo __e('feed.wizard.step1.opt_charset_help'); ?></p>
                                <div class="control mt-2" x-show="charset" x-transition>
                                    <input class="input is-small"
                                           :class="charset ? 'notempty' : ''"
                                           type="text"
                                           name="charset"
                                           data_info="Charset"
                                           x-model="charsetValue"
                                           placeholder="e.g., UTF-8"
                                           :disabled="!charset" />
                                </div>
                            </div>

                            <!-- Max Texts -->
                            <div class="column is-half">
                                <label class="checkbox">
                                    <input type="checkbox" name="c_max_texts" x-model="maxTexts" />
                                    <strong><?php echo __e('feed.wizard.step1.opt_limit_texts'); ?></strong>
                                </label>
                                <p class="help"><?php echo __e('feed.wizard.step1.opt_limit_texts_help'); ?></p>
                                <div class="control mt-2" x-show="maxTexts" x-transition>
                                    <input class="input is-small posintnumber maxint_30"
                                           :class="maxTexts ? 'notempty' : ''"
                                           type="number"
                                           min="1"
                                           max="30"
                                           name="max_texts"
                                           data_info="Max. Texts"
                                           x-model="maxTextsValue"
                                           style="width: 100px;"
                                           :disabled="!maxTexts" />
                                </div>
                            </div>

                            <!-- Tag -->
                            <div class="column is-half">
                                <label class="checkbox">
                                    <input type="checkbox" name="c_tag" x-model="tag" />
                                    <strong><?php echo __e('feed.wizard.step1.opt_auto_tag'); ?></strong>
                                </label>
                                <p class="help"><?php echo __e('feed.wizard.step1.opt_auto_tag_help'); ?></p>
                                <div class="control mt-2" x-show="tag" x-transition>
                                    <input class="input is-small"
                                           :class="tag ? 'notempty' : ''"
                                           type="text"
                                           name="tag"
                                           data_info="Tag"
                                           x-model="tagValue"
                                           placeholder="Tag name"
                                           :disabled="!tag" />
                                </div>
                            </div>

                            <!-- Article Source -->
                            <div class="column is-full">
                                <label class="checkbox">
                                    <input type="checkbox" name="c_article_source" x-model="articleSource" />
                                    <strong><?php echo __e('feed.wizard.step1.opt_article_source'); ?></strong>
                                </label>
                                <div class="control mt-2" x-show="articleSource" x-transition>
                                    <input class="input is-small"
                                           :class="articleSource ? 'notempty' : ''"
                                           type="text"
                                           name="article_source"
                                           data_info="Article Source"
                                           x-model="articleSourceValue"
                                           placeholder="Source identifier"
                                           :disabled="!articleSource" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="field is-grouped is-grouped-right">
                <div class="control">
                    <button type="button" class="button is-light" @click="cancel">
                        <?php echo __e('feed.wizard.step1.cancel'); ?>
                    </button>
                </div>
                <div class="control">
                    <button type="submit" class="button is-primary">
                        <span class="icon is-small">
                            <?php echo IconHelper::render('save', ['alt' => __('feed.wizard.step1.save')]); ?>
                        </span>
                        <span><?php echo __e('feed.wizard.step1.save'); ?></span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
