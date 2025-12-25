<?php declare(strict_types=1);
/**
 * Feed Wizard Step 1 - Insert Feed URI
 *
 * Variables expected:
 * - $errorMessage: string|null error message to display
 * - $rssUrl: string|null previously entered RSS URL
 * - $editFeedId: int|null ID of feed being edited
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

use Lwt\Shared\UI\Helpers\IconHelper;

// Build JSON config
$configJson = json_encode([
    'rssUrl' => $rssUrl ?? '',
    'hasError' => !empty($errorMessage),
    'editFeedId' => $editFeedId ?? null
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<script type="application/json" id="wizard-step1-config"><?php echo $configJson; ?></script>

<div x-data="feedWizardStep1" x-cloak>
    <?php echo \Lwt\Shared\UI\Helpers\PageLayoutHelper::buildLogo(); ?>

    <h1 class="title is-4 is-flex is-align-items-center">
        <span class="icon mr-2">
            <?php echo IconHelper::render('wand-2', ['alt' => 'Wizard']); ?>
        </span>
        Feed Wizard - Step 1: Enter Feed URL
        <a href="docs/info.html#feed_wizard" target="_blank" class="ml-2">
            <?php echo IconHelper::render('help-circle', ['title' => 'Help', 'alt' => 'Help']); ?>
        </a>
    </h1>

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

    <?php if (!empty($errorMessage)): ?>
    <div class="notification is-danger is-light">
        <span class="icon-text">
            <span class="icon">
                <?php echo IconHelper::render('alert-circle', ['alt' => 'Error']); ?>
            </span>
            <span><strong>Error:</strong> Please check your newsfeed URI!</span>
        </span>
    </div>
    <?php endif; ?>

    <form class="validate" action="/feeds/wizard" method="post">
        <input type="hidden" name="step" value="2" />
        <input type="hidden" name="selected_feed" value="0" />
        <input type="hidden" name="article_tags" value="1" />

        <div class="box">
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
