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
 * - $languages: array of language data [{LgID, LgName}, ...]
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
$languagesJson = array_map(
    /** @param array{LgID: int|string, LgName: string} $lang */
    function (array $lang): array {
        return ['id' => (int)$lang['LgID'], 'name' => $lang['LgName']];
    },
    $languages ?? []
);

$configJson = json_encode([
    'rssUrl' => $rssUrl ?? '',
    'hasError' => !empty($errorMessage),
    'editFeedId' => $editFeedId ?? null,
    'languages' => $languagesJson,
    'curatedFeeds' => $curatedFeeds ?? [],
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

        <!-- LWT Language mapping -->
        <div class="field mb-4">
            <label class="label">Your LWT Language</label>
            <div class="control">
                <div class="select is-fullwidth">
                    <select x-model="browseLwtLanguageId" required>
                        <option value="">Select your language in LWT...</option>
                        <template x-for="lang in languages" :key="lang.id">
                            <option :value="lang.id" x-text="lang.name"></option>
                        </template>
                    </select>
                </div>
            </div>
            <p class="help">Required: which LWT language should imported texts belong to?</p>
        </div>

        <!-- No languages warning -->
        <template x-if="languages.length === 0">
            <div class="notification is-warning is-light">
                <strong>No languages configured.</strong>
                <a href="/languages/new">Create a language</a> first, then come back to add feeds.
            </div>
        </template>

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
                                       @click.prevent="addCuratedFeed(source)"
                                       :class="{ 'is-disabled': !browseLwtLanguageId }">
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
            <input type="hidden" name="NfLgID" x-model="curatedFormData.NfLgID" />
            <input type="hidden" name="NfName" x-model="curatedFormData.NfName" />
            <input type="hidden" name="NfSourceURI" x-model="curatedFormData.NfSourceURI" />
            <input type="hidden" name="NfArticleSectionTags" x-model="curatedFormData.NfArticleSectionTags" />
            <input type="hidden" name="NfFilterTags" x-model="curatedFormData.NfFilterTags" />
            <input type="hidden" name="NfOptions" x-model="curatedFormData.NfOptions" />
        </form>
    </div>

    <!-- ===================== TAB 2: Wizard (Enter Feed URL) ===================== -->
    <div class="box" x-show="activeTab === 'wizard'" x-transition>
        <!-- Steps indicator -->
        <div class="steps is-small mb-5">
            <div class="step-item is-active is-primary">
                <div class="step-marker">1</div>
                <div class="step-details"><p class="step-title">Feed URL</p></div>
            </div>
            <div class="step-item">
                <div class="step-marker">2</div>
                <div class="step-details"><p class="step-title">Select Article</p></div>
            </div>
            <div class="step-item">
                <div class="step-marker">3</div>
                <div class="step-details"><p class="step-title">Filter Text</p></div>
            </div>
            <div class="step-item">
                <div class="step-marker">4</div>
                <div class="step-details"><p class="step-title">Save</p></div>
            </div>
        </div>

        <p class="mb-4 has-text-grey">
            Enter a feed URL and we'll guide you through configuring article extraction step by step.
        </p>

        <form class="validate" action="/feeds/wizard" method="post">
            <?php echo FormHelper::csrfField(); ?>
            <input type="hidden" name="step" value="2" />
            <input type="hidden" name="selected_feed" value="0" />
            <input type="hidden" name="article_tags" value="1" />

            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" for="rss_url">Feed URI</label>
                </div>
                <div class="field-body">
                    <div class="field has-addons">
                        <div class="control is-expanded">
                            <input class="input notempty"
                                   type="url"
                                   name="rss_url"
                                   id="rss_url"
                                   placeholder="https://example.com/feed.xml"
                                   x-model="rssUrl"
                                   :class="{ 'is-success': isValidUrl, 'is-danger': rssUrl && !isValidUrl }"
                                   required />
                        </div>
                        <div class="control">
                            <span class="icon has-text-danger" title="Field must not be empty">
                                <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                            </span>
                        </div>
                    </div>
                    <p class="help">Enter the URL of an RSS or Atom feed</p>
                </div>
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
              x-data="feedForm()"
              @submit="handleSubmit($event)">
            <?php echo FormHelper::csrfField(); ?>
            <input type="hidden" name="NfOptions" value="" />
            <input type="hidden" name="save_feed" value="1" />

            <!-- Language -->
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" for="manual_NfLgID">Language</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <div class="select is-fullwidth">
                                <select name="NfLgID" id="manual_NfLgID" required>
                                    <option value="">[Select...]</option>
                                    <?php foreach ($languages as $lang) : ?>
                                    <option value="<?php echo (int)$lang['LgID']; ?>">
                                        <?php echo htmlspecialchars($lang['LgName'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Name -->
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" for="manual_NfName">Name</label>
                </div>
                <div class="field-body">
                    <div class="field has-addons">
                        <div class="control is-expanded">
                            <input class="input notempty"
                                   type="text"
                                   name="NfName"
                                   id="manual_NfName"
                                   placeholder="Feed name"
                                   required />
                        </div>
                        <div class="control">
                            <span class="icon has-text-danger" title="Field must not be empty">
                                <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Newsfeed URL -->
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" for="manual_NfSourceURI">Newsfeed URL</label>
                </div>
                <div class="field-body">
                    <div class="field has-addons">
                        <div class="control is-expanded">
                            <input class="input notempty"
                                   type="url"
                                   name="NfSourceURI"
                                   id="manual_NfSourceURI"
                                   placeholder="https://example.com/feed.xml"
                                   required />
                        </div>
                        <div class="control">
                            <span class="icon has-text-danger" title="Field must not be empty">
                                <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Article Section -->
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" for="manual_NfArticleSectionTags">Article Section</label>
                </div>
                <div class="field-body">
                    <div class="field has-addons">
                        <div class="control is-expanded">
                            <input class="input notempty"
                                   type="text"
                                   name="NfArticleSectionTags"
                                   id="manual_NfArticleSectionTags"
                                   placeholder="CSS selector for article content"
                                   required />
                        </div>
                        <div class="control">
                            <span class="icon has-text-danger" title="Field must not be empty">
                                <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Tags -->
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" for="manual_NfFilterTags">Filter Tags</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <input class="input"
                                   type="text"
                                   name="NfFilterTags"
                                   id="manual_NfFilterTags"
                                   placeholder="Optional: CSS selectors to filter out" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Options Section -->
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label">Options</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="box has-background-light">
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
            </div>

            <!-- Form Actions -->
            <div class="field is-grouped is-grouped-right mt-5">
                <div class="control">
                    <button type="button" class="button is-danger is-outlined" @click="cancel">
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
