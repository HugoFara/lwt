<?php declare(strict_types=1);
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

namespace Lwt\Views\Feed;

use Lwt\View\Helper\IconHelper;
use Lwt\View\Helper\PageLayoutHelper;

$actions = [
    ['url' => '/feeds?page=1', 'label' => 'My Feeds', 'icon' => 'list'],
    ['url' => '/feeds/wizard?step=2&edit_feed=' . $feed['NfID'], 'label' => 'Feed Wizard', 'icon' => 'wand-2', 'class' => 'is-info']
];

?>
<h2 class="title is-4 is-flex is-align-items-center">
    Edit Feed
    <a target="_blank" href="docs/info.html#new_feed" class="ml-2">
        <?php echo IconHelper::render('help-circle', ['title' => 'Help', 'alt' => 'Help']); ?>
    </a>
</h2>

<?php echo PageLayoutHelper::buildActionCard('Feed Options', $actions, 'feeds'); ?>

<form class="validate" action="/feeds/edit" method="post"
      x-data="{
          editText: <?php echo isset($options['edit_text']) ? 'true' : 'false'; ?>,
          autoUpdate: <?php echo $autoUpdateInterval !== null ? 'true' : 'false'; ?>,
          maxLinks: <?php echo isset($options['max_links']) ? 'true' : 'false'; ?>,
          charset: <?php echo isset($options['charset']) ? 'true' : 'false'; ?>,
          maxTexts: <?php echo isset($options['max_texts']) ? 'true' : 'false'; ?>,
          tag: <?php echo isset($options['tag']) ? 'true' : 'false'; ?>,
          articleSource: <?php echo isset($options['article_source']) ? 'true' : 'false'; ?>
      }">
    <input type="hidden" name="NfID" value="<?php echo htmlspecialchars($feed['NfID'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
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
                                <?php foreach ($languages as $lang): ?>
                                <option value="<?php echo $lang['LgID']; ?>"<?php if ($feed['NfLgID'] === $lang['LgID']) echo ' selected'; ?>>
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
                        <input class="input notempty"
                               type="text"
                               name="NfArticleSectionTags"
                               id="NfArticleSectionTags"
                               value="<?php echo htmlspecialchars($feed['NfArticleSectionTags'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
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
                        <input class="input"
                               type="text"
                               name="NfFilterTags"
                               id="NfFilterTags"
                               value="<?php echo htmlspecialchars($feed['NfFilterTags'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
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
                                               value="<?php echo $autoUpdateInterval; ?>"
                                               style="width: 80px;"
                                               :disabled="!autoUpdate" />
                                    </div>
                                    <div class="control">
                                        <div class="select is-small">
                                            <select name="autoupdate_unit" :disabled="!autoUpdate">
                                                <option value="h"<?php if ($autoUpdateUnit === 'h') echo ' selected'; ?>>Hour(s)</option>
                                                <option value="d"<?php if ($autoUpdateUnit === 'd') echo ' selected'; ?>>Day(s)</option>
                                                <option value="w"<?php if ($autoUpdateUnit === 'w') echo ' selected'; ?>>Week(s)</option>
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
                                           value="<?php echo $options['max_links'] ?? ''; ?>"
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
                                           value="<?php echo $options['charset'] ?? ''; ?>"
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
                                           value="<?php echo $options['max_texts'] ?? ''; ?>"
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
                                           value="<?php echo $options['tag'] ?? ''; ?>"
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
                                           value="<?php echo $options['article_source'] ?? ''; ?>"
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
<!-- Feed form interactions handled by feeds/feed_form.ts -->
