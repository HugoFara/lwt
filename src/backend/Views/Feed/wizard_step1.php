<?php declare(strict_types=1);
/**
 * Feed Wizard Step 1 - Insert Feed URI
 *
 * Variables expected:
 * - $errorMessage: string|null error message to display
 * - $rssUrl: string|null previously entered RSS URL
 *
 * JavaScript moved to src/frontend/js/feeds/feed_wizard_common.ts
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

?>
<script type="application/json" id="wizard-step1-config"><?php echo json_encode(['step' => 1]); ?></script>

<h2 class="title is-4 is-flex is-align-items-center">
    <span class="icon mr-2">
        <?php echo IconHelper::render('wand-2', ['alt' => 'Wizard']); ?>
    </span>
    Feed Wizard - Step 1
</h2>

<div class="steps is-small mb-5">
    <div class="step-item is-active is-primary">
        <div class="step-marker">1</div>
        <div class="step-details">
            <p class="step-title">Feed URL</p>
        </div>
    </div>
    <div class="step-item">
        <div class="step-marker">2</div>
        <div class="step-details">
            <p class="step-title">Select Article</p>
        </div>
    </div>
    <div class="step-item">
        <div class="step-marker">3</div>
        <div class="step-details">
            <p class="step-title">Filter Text</p>
        </div>
    </div>
    <div class="step-item">
        <div class="step-marker">4</div>
        <div class="step-details">
            <p class="step-title">Save</p>
        </div>
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
                               value="<?php echo htmlspecialchars($rssUrl ?? '', ENT_QUOTES, 'UTF-8'); ?>"
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
    <div class="field is-grouped is-grouped-right">
        <div class="control">
            <button type="button"
                    class="button is-light"
                    data-action="wizard-cancel"
                    data-url="/feeds/edit?del_wiz=1">
                Cancel
            </button>
        </div>
        <div class="control">
            <button type="submit" class="button is-primary">
                <span>Next</span>
                <span class="icon is-small">
                    <?php echo IconHelper::render('arrow-right', ['alt' => 'Next']); ?>
                </span>
            </button>
        </div>
    </div>
</form>
