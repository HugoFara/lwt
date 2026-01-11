<?php

/**
 * Edit Feed Form View
 *
 * Variables expected:
 * - $feed: array feed data from database
 * - $languages: array of language data [{LgID, LgName}, ...]
 * - $options: array parsed feed options
 * - $autoUpdateInterval: string|null auto-update interval value
 * - $autoUpdateUnit: string|null auto-update unit (h/d/w)
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

declare(strict_types=1);

namespace Lwt\Views\Feed;

use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

/**
 * @var array{NfID: int|null, NfLgID: int, NfName: string, NfSourceURI: string,
 *            NfArticleSectionTags: string, NfFilterTags: string, NfUpdate: int,
 *            NfOptions: string} $feed Feed data
 * @var array<int, array{LgID: int, LgName: string}> $languages Language records
 * @var array<string, string> $options Parsed feed options
 * @var string|null $autoUpdateInterval Auto-update interval value
 * @var string|null $autoUpdateUnit Auto-update unit (h/d/w)
 */

$actions = [
    ['url' => '/feeds?page=1', 'label' => 'Feeds', 'icon' => 'list'],
    [
        'url' => '/feeds/wizard?step=2&edit_feed=' . (int)$feed['NfID'],
        'label' => 'Feed Wizard',
        'icon' => 'wand-2',
        'class' => 'is-info'
    ]
];

?>
<h2 class="title is-4 is-flex is-align-items-center">
    Edit Feed
    <a target="_blank" href="docs/info.html#new_feed" class="ml-2">
        <?php echo IconHelper::render('help-circle', ['title' => 'Help', 'alt' => 'Help']); ?>
    </a>
</h2>

<?php echo PageLayoutHelper::buildActionCard($actions); ?>

<script type="application/json" id="feed-form-config">
<?php echo json_encode([
    'editText' => isset($options['edit_text']),
    'autoUpdate' => $autoUpdateInterval !== null,
    'autoUpdateValue' => $autoUpdateInterval ?? '',
    'autoUpdateUnit' => $autoUpdateUnit ?? 'h',
    'maxLinks' => isset($options['max_links']),
    'maxLinksValue' => $options['max_links'] ?? '',
    'charset' => isset($options['charset']),
    'charsetValue' => $options['charset'] ?? '',
    'maxTexts' => isset($options['max_texts']),
    'maxTextsValue' => $options['max_texts'] ?? '',
    'tag' => isset($options['tag']),
    'tagValue' => $options['tag'] ?? '',
    'articleSource' => isset($options['article_source']),
    'articleSourceValue' => $options['article_source'] ?? '',
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>
<form class="validate" action="/feeds/edit" method="post"
      x-data="feedForm()"
      @submit="handleSubmit($event)">
    <?php echo \Lwt\Shared\UI\Helpers\FormHelper::csrfField(); ?>
    <input type="hidden" name="NfID" value="<?php echo $feed['NfID'] ?? ''; ?>" />
    <input type="hidden" name="NfOptions" value="" />
    <input type="hidden" name="update_feed" value="1" />

    <div class="box">
        <!-- Language -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label" for="NfLgID">Language</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="NfLgID" id="NfLgID">
                                <?php foreach ($languages as $lang) : ?>
                                    <?php $selected = ($feed['NfLgID'] === $lang['LgID']) ? ' selected' : ''; ?>
                                    <option value="<?php echo $lang['LgID']; ?>"<?php echo $selected; ?>>
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
                <label class="label" for="NfName">Name</label>
            </div>
            <div class="field-body">
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <input class="input notempty"
                               type="text"
                               name="NfName"
                               id="NfName"
                               value="<?php echo htmlspecialchars($feed['NfName'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
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
                <label class="label" for="NfSourceURI">Newsfeed URL</label>
            </div>
            <div class="field-body">
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <input class="input notempty"
                               type="url"
                               name="NfSourceURI"
                               id="NfSourceURI"
                               value="<?php echo htmlspecialchars($feed['NfSourceURI'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
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
                <label class="label" for="NfArticleSectionTags">Article Section</label>
            </div>
            <div class="field-body">
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <?php
                        $articleSection = $feed['NfArticleSectionTags'] ?? '';
                        $articleSectionVal = htmlspecialchars($articleSection, ENT_QUOTES, 'UTF-8');
                        ?>
                        <input class="input notempty"
                               type="text"
                               name="NfArticleSectionTags"
                               id="NfArticleSectionTags"
                               value="<?php echo $articleSectionVal; ?>"
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
                <label class="label" for="NfFilterTags">Filter Tags</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <div class="control">
                        <?php
                        $filterTags = $feed['NfFilterTags'] ?? '';
                        $filterTagsVal = htmlspecialchars($filterTags, ENT_QUOTES, 'UTF-8');
                        ?>
                        <input class="input"
                               type="text"
                               name="NfFilterTags"
                               id="NfFilterTags"
                               value="<?php echo $filterTagsVal; ?>" />
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
                                    <input type="checkbox" name="edit_text" x-model="editText" />
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
                                            <select name="autoupdate_unit"
                                                    x-model="autoUpdateUnit"
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
                                           :disabled="!articleSource" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="field is-grouped is-grouped-right">
        <div class="control">
            <button type="button"
                    class="button is-light"
                    data-action="navigate"
                    data-url="/feeds/edit">
                Cancel
            </button>
        </div>
        <div class="control">
            <button type="submit" class="button is-primary">
                <span class="icon is-small">
                    <?php echo IconHelper::render('save', ['alt' => 'Save']); ?>
                </span>
                <span>Update</span>
            </button>
        </div>
    </div>
</form>
<!-- Feed form component: feeds/components/feed_form_component.ts -->
