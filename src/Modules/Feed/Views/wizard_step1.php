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
            <span><strong>Error:</strong> Please check your newsfeed URI!</span>
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
                    <span>Browse Sources</span>
                </a>
            </li>
            <li :class="{ 'is-active': activeTab === 'wizard' }">
                <a @click.prevent="activeTab = 'wizard'">
                    <span class="icon is-small">
                        <?php echo IconHelper::render('wand-2', ['alt' => 'Wizard']); ?>
                    </span>
                    <span>Enter Feed URL</span>
                </a>
            </li>
            <li :class="{ 'is-active': activeTab === 'manual' }">
                <a @click.prevent="activeTab = 'manual'">
                    <span class="icon is-small">
                        <?php echo IconHelper::render('settings', ['alt' => 'Manual']); ?>
                    </span>
                    <span>Manual Setup</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- ===================== TAB 1: Browse Curated Sources ===================== -->
    <div class="box" x-show="activeTab === 'browse'" x-transition>
        <p class="mb-4 has-text-grey">
            Pick from pre-configured news sources. Selectors and options are already set up for you.
        </p>

        <!-- Language filter -->
        <div class="field is-grouped mb-4">
            <div class="control">
                <div class="select">
                    <select x-model="browseLanguageFilter">
                        <option value="">All languages</option>
                        <template x-for="group in curatedFeeds" :key="group.language">
                            <option :value="group.language" x-text="group.languageName"></option>
                        </template>
                    </select>
                </div>
            </div>
            <div class="control is-expanded">
                <input class="input" type="search" placeholder="Search feeds..."
                       x-model="browseSearch" />
            </div>
        </div>

        <!-- Feed cards grouped by language -->
        <template x-if="filteredCuratedFeeds.length === 0">
            <div class="notification is-light">
                No feeds match your search.
            </div>
        </template>

        <template x-for="group in filteredCuratedFeeds" :key="group.language">
            <div class="mb-5">
                <h3 class="title is-5 mb-3" x-text="group.languageName"></h3>
                <div class="columns is-multiline">
                    <template x-for="source in group.sources" :key="source.url">
                        <div class="column is-half-tablet is-one-third-desktop">
                            <div class="card">
                                <div class="card-content p-4">
                                    <p class="title is-6 mb-2" x-text="source.name"></p>
                                    <div class="tags mb-2">
                                        <span class="tag is-info is-light" x-text="source.category"></span>
                                        <span class="tag is-light" x-text="source.level"></span>
                                    </div>
                                    <p class="is-size-7 has-text-grey is-clipped" x-text="source.url"
                                       style="max-height: 1.5em; overflow: hidden; text-overflow: ellipsis;"></p>
                                </div>
                                <footer class="card-footer">
                                    <a class="card-footer-item has-text-primary"
                                       @click.prevent="addCuratedFeed(source)">
                                        <span class="icon is-small mr-1">
                                            <?php echo IconHelper::render('plus', ['alt' => 'Add']); ?>
                                        </span>
                                        Add
                                    </a>
                                </footer>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>

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
            Enter a feed URL and we'll guide you through configuring article extraction step by step.
        </p>

        <form class="validate" action="/feeds/wizard" method="post">
            <?php echo FormHelper::csrfField(); ?>
            <input type="hidden" name="step" value="2" />
            <input type="hidden" name="selected_feed" value="0" />
            <input type="hidden" name="article_tags" value="1" />

            <div class="field">
                <label class="label" for="rss_url">
                    Feed URI
                    <span class="has-text-danger" title="Required">*</span>
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
                <p class="help">Enter the URL of an RSS or Atom feed</p>
            </div>

            <!-- Form Actions -->
            <div class="field is-grouped is-grouped-right mt-5">
                <div class="control">
                    <button type="button" class="button is-danger is-outlined" @click="cancel">
                        Cancel
                    </button>
                </div>
                <div class="control">
                    <button type="submit" class="button is-primary" :disabled="!isValidUrl">
                        <span>Next</span>
                        <span class="icon is-small">
                            <?php echo IconHelper::render('arrow-right', ['alt' => 'Next']); ?>
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ===================== TAB 3: Manual Setup ===================== -->
    <div class="box" x-show="activeTab === 'manual'" x-transition>
        <p class="mb-4 has-text-grey">
            Set up a feed manually. You'll need to know the RSS URL, article CSS selectors, and filter selectors.
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
                        Name
                        <span class="has-text-danger" title="Required">*</span>
                    </label>
                    <div class="control">
                        <input class="input notempty"
                               type="text"
                               name="NfName"
                               id="manual_NfName"
                               placeholder="Feed name"
                               required />
                    </div>
                </div>

                <!-- Newsfeed URL -->
                <div class="field">
                    <label class="label" for="manual_NfSourceURI">
                        Newsfeed URL
                        <span class="has-text-danger" title="Required">*</span>
                    </label>
                    <div class="control">
                        <input class="input notempty"
                               type="url"
                               name="NfSourceURI"
                               id="manual_NfSourceURI"
                               placeholder="https://example.com/feed.xml"
                               required />
                    </div>
                </div>

                <!-- Article Section -->
                <div class="field">
                    <label class="label" for="manual_NfArticleSectionTags">
                        Article Section
                        <span class="has-text-danger" title="Required">*</span>
                    </label>
                    <div class="control">
                        <input class="input notempty"
                               type="text"
                               name="NfArticleSectionTags"
                               id="manual_NfArticleSectionTags"
                               placeholder="CSS selector for article content"
                               required />
                    </div>
                </div>

                <!-- Filter Tags -->
                <div class="field">
                    <label class="label" for="manual_NfFilterTags">Filter Tags</label>
                    <div class="control">
                        <input class="input"
                               type="text"
                               name="NfFilterTags"
                               id="manual_NfFilterTags"
                               placeholder="Optional: CSS selectors to filter out" />
                    </div>
                </div>

                <!-- Options Section -->
                <div class="field">
                    <label class="label">Options</label>
                    <div class="box" style="background-color: var(--bulma-scheme-main-bis);">
                        <div class="columns is-multiline">
                            <!-- Edit Text -->
                            <div class="column is-half">
                                <label class="checkbox">
                                    <input type="checkbox" name="edit_text" x-model="editText" checked />
                                    <strong>Edit Text</strong>
                                </label>
                            </div>

                            <!-- Auto Update -->
                            <div class="column is-half">
                                <label class="checkbox">
                                    <input type="checkbox" name="c_autoupdate" x-model="autoUpdate" />
                                    <strong>Auto Update Interval</strong>
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
                                                <option value="h">Hour(s)</option>
                                                <option value="d">Day(s)</option>
                                                <option value="w">Week(s)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Max Links -->
                            <div class="column is-half">
                                <label class="checkbox">
                                    <input type="checkbox" name="c_max_links" x-model="maxLinks" />
                                    <strong>Max. Links</strong>
                                </label>
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
                                    <strong>Charset</strong>
                                </label>
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
                                    <strong>Max. Texts</strong>
                                </label>
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
                                    <strong>Tag</strong>
                                </label>
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
                                    <strong>Article Source</strong>
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
                        Cancel
                    </button>
                </div>
                <div class="control">
                    <button type="submit" class="button is-primary">
                        <span class="icon is-small">
                            <?php echo IconHelper::render('save', ['alt' => 'Save']); ?>
                        </span>
                        <span>Save</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
