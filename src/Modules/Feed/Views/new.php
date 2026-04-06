<?php

/**
 * New Feed Form View
 *
 * Variables expected:
 * - $languages: array of language data [{LgID, LgName}, ...]
 * - $currentLang: int current language ID (for pre-selection)
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
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

/**
 * @var array<int, array{LgID: int, LgName: string}> $languages Language records
 * @var int $currentLang Current language ID (for pre-selection)
 */

$esc = static fn(string $key): string => htmlspecialchars(__($key), ENT_QUOTES, 'UTF-8');
$titleRequired = $esc('feed.new_required');
$phName = $esc('feed.new_placeholder_name');
$phUrl = $esc('feed.new_placeholder_url');
$phArticleSection = $esc('feed.new_placeholder_article_section');
$phFilterTags = $esc('feed.new_placeholder_filter_tags');
$phCharset = $esc('feed.new_opt_charset_placeholder');
$phAutoTag = $esc('feed.new_opt_auto_tag_placeholder');
$phSource = $esc('feed.new_opt_source_placeholder');

$actions = [
    ['url' => '/feeds?page=1', 'label' => __('feed.new_action_feeds'), 'icon' => 'list'],
    [
        'url' => '/feeds/new',
        'label' => __('feed.new_action_wizard'),
        'icon' => 'wand-2',
        'class' => 'is-info',
    ],
];

?>
<h2 class="title is-4"><?= __('feed.new_title') ?></h2>

<?php echo PageLayoutHelper::buildActionCard($actions); ?>

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
    <?php echo \Lwt\Shared\UI\Helpers\FormHelper::csrfField(); ?>
    <input type="hidden" name="NfOptions" value="" />
    <input type="hidden" name="save_feed" value="1" />

    <div class="box">
        <!-- Language -->
        <div class="field">
            <label class="label" for="NfLgID"><?= __('feed.new_label_language') ?></label>
            <div class="control">
                <div class="select is-fullwidth">
                    <select name="NfLgID" id="NfLgID">
                        <?php foreach ($languages as $lang) : ?>
                        <option value="<?php echo $lang['LgID']; ?>"<?php if ($currentLang === $lang['LgID']) {
                            echo ' selected';
                                       } ?>>
                            <?php echo htmlspecialchars($lang['LgName'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Name -->
        <div class="field">
            <label class="label" for="NfName">
                <?= __('feed.new_label_name') ?>
                <span class="has-text-danger" title="<?= $titleRequired ?>">*</span>
            </label>
            <div class="control">
                <input class="input notempty"
                       type="text"
                       name="NfName"
                       id="NfName"
                       placeholder="<?= $phName ?>"
                       required />
            </div>
        </div>

        <!-- Newsfeed URL -->
        <div class="field">
            <label class="label" for="NfSourceURI">
                <?= __('feed.new_label_url') ?>
                <span class="has-text-danger" title="<?= $titleRequired ?>">*</span>
            </label>
            <div class="control">
                <input class="input notempty"
                       type="url"
                       name="NfSourceURI"
                       id="NfSourceURI"
                       placeholder="<?= $phUrl ?>"
                       required />
            </div>
        </div>

        <!-- Article Section -->
        <div class="field">
            <label class="label" for="NfArticleSectionTags">
                <?= __('feed.new_label_article_section') ?>
                <span class="has-text-danger" title="<?= $titleRequired ?>">*</span>
            </label>
            <div class="control">
                <input class="input notempty"
                       type="text"
                       name="NfArticleSectionTags"
                       id="NfArticleSectionTags"
                       placeholder="<?= $phArticleSection ?>"
                       required />
            </div>
        </div>

        <!-- Filter Tags -->
        <div class="field">
            <label class="label" for="NfFilterTags"><?= __('feed.new_label_filter_tags') ?></label>
            <div class="control">
                <input class="input"
                       type="text"
                       name="NfFilterTags"
                       id="NfFilterTags"
                       placeholder="<?= $phFilterTags ?>" />
            </div>
        </div>

        <!-- Options Section -->
        <div class="field">
            <label class="label"><?= __('feed.new_label_options') ?></label>
            <div class="box" style="background-color: var(--bulma-scheme-main-bis);">
                <div class="columns is-multiline">
                    <!-- Edit Text -->
                    <div class="column is-half">
                        <label class="checkbox">
                            <input type="checkbox" name="edit_text" x-model="editText" checked />
                            <strong><?= __('feed.new_opt_review_before_importing') ?></strong>
                        </label>
                        <p class="help"><?= __('feed.new_opt_review_help') ?></p>
                    </div>

                    <!-- Auto Update -->
                    <div class="column is-half">
                        <label class="checkbox">
                            <input type="checkbox" name="c_autoupdate" x-model="autoUpdate" />
                            <strong><?= __('feed.new_opt_auto_refresh') ?></strong>
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
                                        <option value="h"><?= __('feed.new_opt_unit_hours') ?></option>
                                        <option value="d"><?= __('feed.new_opt_unit_days') ?></option>
                                        <option value="w"><?= __('feed.new_opt_unit_weeks') ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Max Links -->
                    <div class="column is-half">
                        <label class="checkbox">
                            <input type="checkbox" name="c_max_links" x-model="maxLinks" />
                            <strong><?= __('feed.new_opt_limit_articles') ?></strong>
                        </label>
                        <p class="help"><?= __('feed.new_opt_limit_articles_help') ?></p>
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
                            <strong><?= __('feed.new_opt_charset') ?></strong>
                        </label>
                        <p class="help"><?= __('feed.new_opt_charset_help') ?></p>
                        <div class="control mt-2" x-show="charset" x-transition>
                            <input class="input is-small"
                                   :class="charset ? 'notempty' : ''"
                                   type="text"
                                   name="charset"
                                   data_info="Charset"
                                   x-model="charsetValue"
                                   placeholder="<?= $phCharset ?>"
                                   :disabled="!charset" />
                        </div>
                    </div>

                    <!-- Max Texts -->
                    <div class="column is-half">
                        <label class="checkbox">
                            <input type="checkbox" name="c_max_texts" x-model="maxTexts" />
                            <strong><?= __('feed.new_opt_limit_texts') ?></strong>
                        </label>
                        <p class="help"><?= __('feed.new_opt_limit_texts_help') ?></p>
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
                            <strong><?= __('feed.new_opt_auto_tag') ?></strong>
                        </label>
                        <p class="help"><?= __('feed.new_opt_auto_tag_help') ?></p>
                        <div class="control mt-2" x-show="tag" x-transition>
                            <input class="input is-small"
                                   :class="tag ? 'notempty' : ''"
                                   type="text"
                                   name="tag"
                                   data_info="Tag"
                                   x-model="tagValue"
                                   placeholder="<?= $phAutoTag ?>"
                                   :disabled="!tag" />
                        </div>
                    </div>

                    <!-- Article Source -->
                    <div class="column is-full">
                        <label class="checkbox">
                            <input type="checkbox" name="c_article_source" x-model="articleSource" />
                            <strong><?= __('feed.new_opt_source') ?></strong>
                        </label>
                        <p class="help"><?= __('feed.new_opt_source_help') ?></p>
                        <div class="control mt-2" x-show="articleSource" x-transition>
                            <input class="input is-small"
                                   :class="articleSource ? 'notempty' : ''"
                                   type="text"
                                   name="article_source"
                                   data_info="Article Source"
                                   x-model="articleSourceValue"
                                   placeholder="<?= $phSource ?>"
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
            <button type="button"
                    class="button is-light"
                    data-action="navigate"
                    data-url="/feeds/manage">
                <?= __('feed.new_cancel') ?>
            </button>
        </div>
        <div class="control">
            <button type="submit" class="button is-primary">
                <span class="icon is-small">
                    <?php echo IconHelper::render('save', ['alt' => 'Save']); ?>
                </span>
                <span><?= __('feed.new_save') ?></span>
            </button>
        </div>
    </div>
</form>
<!-- Feed form component: feeds/components/feed_form_component.ts -->
