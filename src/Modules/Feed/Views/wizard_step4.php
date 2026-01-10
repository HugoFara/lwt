<?php declare(strict_types=1);
/**
 * Feed Wizard Step 4 - Edit Options
 *
 * Variables expected:
 * - $wizardData: array wizard session data
 * - $languages: array of language records
 * - $autoUpdI: string|null auto update interval value
 * - $autoUpdV: string|null auto update interval unit
 * - $service: FeedService instance
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Views\Feed;

use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Shared\UI\Helpers\IconHelper;

/**
 * @var array{rss_url: string, feed: array<int|string, mixed>, lang?: int, options?: string, redirect?: string, article_section?: string, edit_feed?: int} $wizardData Wizard session data
 * @var array<int, array{LgID: int, LgName: string}> $languages Language records
 * @var string|null $autoUpdI Auto update interval value
 * @var string|null $autoUpdV Auto update interval unit
 * @var \Lwt\Modules\Feed\Application\FeedFacade $service Feed service
 */

// Prepare languages array for JSON
$languagesJson = array_map(
    /** @param array{LgID: int|string, LgName: string} $lang */
    function (array $lang): array {
        return ['id' => (int)$lang['LgID'], 'name' => $lang['LgName']];
    },
    $languages
);

// Prepare options for JSON config
$options = $wizardData['options'] ?? '';
$maxLinksValue = $service->getNfOption($options, 'max_links');
$maxTextsValue = $service->getNfOption($options, 'max_texts');
$charsetValue = $service->getNfOption($options, 'charset');
$tagValue = $service->getNfOption($options, 'tag');

$optionsConfig = [
    'editText' => $service->getNfOption($options, 'edit_text') !== null,
    'autoUpdate' => [
        'enabled' => $autoUpdI !== null,
        'interval' => $autoUpdI !== null ? (int)$autoUpdI : null,
        'unit' => $autoUpdV ?? 'h'
    ],
    'maxLinks' => [
        'enabled' => $maxLinksValue !== null,
        'value' => is_string($maxLinksValue) && is_numeric($maxLinksValue) ? (int)$maxLinksValue : null
    ],
    'maxTexts' => [
        'enabled' => $maxTextsValue !== null,
        'value' => is_string($maxTextsValue) && is_numeric($maxTextsValue) ? (int)$maxTextsValue : null
    ],
    'charset' => [
        'enabled' => $charsetValue !== null,
        'value' => is_string($charsetValue) ? $charsetValue : ''
    ],
    'tag' => [
        'enabled' => $tagValue !== null,
        'value' => is_string($tagValue) ? $tagValue : ''
    ]
];

$configJson = json_encode([
    'editFeedId' => $wizardData['edit_feed'] ?? null,
    'feedTitle' => $wizardData['feed']['feed_title'] ?? '',
    'rssUrl' => $wizardData['rss_url'] ?? '',
    'articleSection' => preg_replace('/[ ]+/', ' ', trim(($wizardData['redirect'] ?? '') . ($wizardData['article_section'] ?? ''))),
    'filterTags' => preg_replace('/[ ]+/', ' ', InputValidator::getString('html')),
    'feedText' => $wizardData['feed']['feed_text'] ?? '',
    'langId' => $wizardData['lang'] ?? null,
    'options' => $optionsConfig,
    'languages' => $languagesJson
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<script type="application/json" id="wizard-step4-config"><?php echo $configJson; ?></script>

<div x-data="feedWizardStep4" x-cloak>
    <?php echo \Lwt\Shared\UI\Helpers\PageLayoutHelper::buildLogo(); ?>

    <h1 class="title is-4 is-flex is-align-items-center">
        <span class="icon mr-2">
            <?php echo IconHelper::render('wand-2', ['alt' => 'Wizard']); ?>
        </span>
        Feed Wizard - Step 4: Edit Options
        <a href="docs/info.html#feed_wizard" target="_blank" class="ml-2">
            <?php echo IconHelper::render('help-circle', ['title' => 'Help', 'alt' => 'Help']); ?>
        </a>
    </h1>

    <!-- Steps indicator -->
    <div class="steps is-small mb-5">
        <div class="step-item is-completed is-success">
            <div class="step-marker">1</div>
            <div class="step-details"><p class="step-title">Feed URL</p></div>
        </div>
        <div class="step-item is-completed is-success">
            <div class="step-marker">2</div>
            <div class="step-details"><p class="step-title">Select Article</p></div>
        </div>
        <div class="step-item is-completed is-success">
            <div class="step-marker">3</div>
            <div class="step-details"><p class="step-title">Filter Text</p></div>
        </div>
        <div class="step-item is-active is-primary">
            <div class="step-marker">4</div>
            <div class="step-details"><p class="step-title">Save</p></div>
        </div>
    </div>

    <form class="validate" action="/feeds/edit" method="post" @submit="handleSubmit">
        <?php echo \Lwt\Shared\UI\Helpers\FormHelper::csrfField(); ?>
        <div class="box">
            <!-- Language -->
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label">Language</label>
                </div>
                <div class="field-body">
                    <div class="field has-addons">
                        <div class="control is-expanded">
                            <div class="select is-fullwidth">
                                <select name="NfLgID" x-model="languageId" required class="notempty">
                                    <option value="">[Select...]</option>
                                    <template x-for="lang in languages" :key="lang.id">
                                        <option :value="lang.id" x-text="lang.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        <div class="control">
                            <span class="icon has-text-danger" title="Field must not be empty">
                                <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Name -->
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label">Name</label>
                </div>
                <div class="field-body">
                    <div class="field has-addons">
                        <div class="control is-expanded">
                            <input class="input notempty" type="text" name="NfName"
                                   x-model="feedName" required />
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
                    <label class="label">Newsfeed URL</label>
                </div>
                <div class="field-body">
                    <div class="field has-addons">
                        <div class="control is-expanded">
                            <input class="input notempty" type="text" name="NfSourceURI"
                                   x-model="sourceUri" required />
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
                    <label class="label">Article Section</label>
                </div>
                <div class="field-body">
                    <div class="field has-addons">
                        <div class="control is-expanded">
                            <input class="input notempty" type="text" name="NfArticleSectionTags"
                                   x-model="articleSection" required />
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
                    <label class="label">Filter Tags</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <input class="input" type="text" name="NfFilterTags"
                                   x-model="filterTags" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Options Box -->
        <div class="box">
            <h2 class="subtitle is-5 mb-4">Options</h2>

            <div class="columns">
                <div class="column">
                    <!-- Edit Text -->
                    <div class="field">
                        <label class="checkbox">
                            <input type="checkbox" name="edit_text" x-model="editText" />
                            Edit Text
                        </label>
                        <p class="help">Show edit form before saving each text</p>
                    </div>

                    <!-- Max Links -->
                    <div class="field">
                        <label class="checkbox">
                            <input type="checkbox" name="c_max_links" x-model="maxLinksEnabled" />
                            Max. Links:
                        </label>
                        <div class="control mt-1">
                            <input class="input is-small" type="number" name="max_links"
                                   min="0" max="300" style="width: 100px;"
                                   x-model="maxLinks"
                                   :disabled="!maxLinksEnabled"
                                   :class="{ 'notempty': maxLinksEnabled }" />
                        </div>
                    </div>

                    <!-- Max Texts -->
                    <div class="field">
                        <label class="checkbox">
                            <input type="checkbox" name="c_max_texts" x-model="maxTextsEnabled" />
                            Max. Texts:
                        </label>
                        <div class="control mt-1">
                            <input class="input is-small" type="number" name="max_texts"
                                   min="0" max="30" style="width: 100px;"
                                   x-model="maxTexts"
                                   :disabled="!maxTextsEnabled"
                                   :class="{ 'notempty': maxTextsEnabled }" />
                        </div>
                    </div>
                </div>

                <div class="column">
                    <!-- Auto Update -->
                    <div class="field">
                        <label class="checkbox">
                            <input type="checkbox" name="c_autoupdate" x-model="autoUpdateEnabled" />
                            Auto Update Interval:
                        </label>
                        <div class="field has-addons mt-1">
                            <div class="control">
                                <input class="input is-small" type="number" name="autoupdate"
                                       min="0" style="width: 80px;"
                                       x-model="autoUpdateInterval"
                                       :disabled="!autoUpdateEnabled"
                                       :class="{ 'notempty': autoUpdateEnabled }" />
                            </div>
                            <div class="control">
                                <div class="select is-small">
                                    <select name="autoupdate_unit" x-model="autoUpdateUnit"
                                            :disabled="!autoUpdateEnabled">
                                        <option value="h">Hour(s)</option>
                                        <option value="d">Day(s)</option>
                                        <option value="w">Week(s)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charset -->
                    <div class="field">
                        <label class="checkbox">
                            <input type="checkbox" name="c_charset" x-model="charsetEnabled" />
                            Charset:
                        </label>
                        <div class="control mt-1">
                            <input class="input is-small" type="text" name="charset"
                                   style="width: 150px;"
                                   x-model="charset"
                                   :disabled="!charsetEnabled"
                                   :class="{ 'notempty': charsetEnabled }" />
                        </div>
                    </div>

                    <!-- Tag -->
                    <div class="field">
                        <label class="checkbox">
                            <input type="checkbox" name="c_tag" x-model="tagEnabled" />
                            Tag:
                        </label>
                        <div class="control mt-1">
                            <input class="input is-small" type="text" name="tag"
                                   style="width: 150px;"
                                   x-model="tag"
                                   :disabled="!tagEnabled"
                                   :class="{ 'notempty': tagEnabled }" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hidden inputs -->
        <template x-if="isEditMode">
            <input type="hidden" name="NfID" :value="config.editFeedId" />
        </template>
        <input type="hidden" name="NfOptions" value="" />
        <input type="hidden" name="article_source" :value="config.feedText" />
        <input type="hidden" :name="isEditMode ? 'update_feed' : 'save_feed'" value="1" />

        <!-- Form Actions -->
        <div class="field is-grouped is-grouped-right mt-5">
            <div class="control">
                <button type="button" class="button is-danger is-outlined" @click="cancel">
                    Cancel
                </button>
            </div>
            <div class="control">
                <button type="button" class="button" @click="goBack">
                    <span class="icon is-small">
                        <?php echo IconHelper::render('arrow-left', ['alt' => 'Back']); ?>
                    </span>
                    <span>Back</span>
                </button>
            </div>
            <div class="control">
                <button type="submit" class="button is-primary">
                    <span x-text="submitLabel">Save</span>
                </button>
            </div>
        </div>
    </form>
</div>
