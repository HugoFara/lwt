<?php declare(strict_types=1);
/**
 * Text Edit Form View - Display form for creating/editing texts
 *
 * Variables expected:
 * - $textId: int - Text ID (0 for new text)
 * - $text: Lwt\Classes\Text - Text object
 * - $annotated: bool - Whether the text has annotations
 * - $languageData: array - Mapping of language ID to language code
 * - $isNew: bool - Whether this is a new text
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @psalm-suppress UndefinedVariable - Variables are set by the including controller
 */

namespace Lwt\Views\Text;

/** @var int $textId */
/** @var \Lwt\Classes\Text $text */
/** @var bool $annotated */
/** @var array $languageData */
/** @var array $languages */
/** @var bool $isNew */

use Lwt\View\Helper\SelectOptionsBuilder;
use Lwt\View\Helper\IconHelper;
use Lwt\View\Helper\PageLayoutHelper;

// Build actions based on whether this is a new or existing text
$actions = [];
if (!$isNew) {
    $actions[] = ['url' => '/texts?new=1', 'label' => 'New Text', 'icon' => 'circle-plus', 'class' => 'is-primary'];
}
$actions[] = ['url' => '/text/import-long', 'label' => 'Long Text Import', 'icon' => 'file-up'];
$actions[] = ['url' => '/feeds?page=1&check_autoupdate=1', 'label' => 'Newsfeed Import', 'icon' => 'rss'];
$actions[] = ['url' => '/texts?query=&page=1', 'label' => 'Active Texts', 'icon' => 'book-open'];
if ($isNew) {
    $actions[] = ['url' => '/text/archived?query=&page=1', 'label' => 'Archived Texts', 'icon' => 'archive'];
}

?>
<script type="application/json" id="text-edit-config">
<?php echo json_encode(['languageData' => $languageData]); ?>
</script>

<h2 class="title is-4 is-flex is-align-items-center">
    <?php echo $isNew ? "New" : "Edit"; ?> Text
    <a target="_blank" href="docs/info.html#howtotext" class="ml-2">
        <?php echo IconHelper::render('help-circle', ['title' => 'Help', 'alt' => 'Help']); ?>
    </a>
</h2>

<?php echo PageLayoutHelper::buildActionCard('Text Actions', $actions, 'texts'); ?>

<form class="validate" method="post"
      action="/texts<?php echo $isNew ? '' : '#rec' . $textId; ?>"
      x-data="{ showAnnotation: <?php echo $isNew ? 'false' : 'true'; ?> }">
    <input type="hidden" name="TxID" value="<?php echo $textId; ?>" />

    <div class="box">
        <!-- Language -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label" for="TxLgID">Language</label>
            </div>
            <div class="field-body">
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <div class="select is-fullwidth">
                            <select name="TxLgID" id="TxLgID" class="notempty setfocus"
                                    data-action="change-language" required>
                                <?php echo SelectOptionsBuilder::forLanguages($languages, $text->lgid, "[Choose...]"); ?>
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

        <!-- Title -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label" for="TxTitle">Title</label>
            </div>
            <div class="field-body">
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <input type="text"
                               class="input notempty checkoutsidebmp"
                               data_info="Title"
                               name="TxTitle"
                               id="TxTitle"
                               value="<?php echo \htmlspecialchars($text->title ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               maxlength="200"
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

        <!-- Text Content -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label" for="TxText">
                    Text
                    <span class="has-text-grey is-size-7">(max. 65,000 bytes)</span>
                </label>
            </div>
            <div class="field-body">
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <textarea <?php echo $scrdir; ?>
                                  name="TxText"
                                  id="TxText"
                                  class="textarea notempty checkbytes checkoutsidebmp"
                                  data_maxlength="65000"
                                  data_info="Text"
                                  rows="15"
                                  required><?php echo \htmlspecialchars($text->text ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="control">
                        <span class="icon has-text-danger" title="Field must not be empty">
                            <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Annotated Text (only for existing texts) -->
        <?php if (!$isNew): ?>
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Annotated Text</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <div class="control">
                        <?php if ($annotated): ?>
                        <div class="notification is-info is-light">
                            <span class="icon-text">
                                <span class="icon has-text-success">
                                    <?php echo IconHelper::render('check', ['alt' => 'Has Annotation']); ?>
                                </span>
                                <span>Exists - May be partially or fully lost if you change the text!</span>
                            </span>
                            <div class="mt-2">
                                <button type="button"
                                        class="button is-small is-info is-outlined"
                                        data-action="navigate"
                                        data-url="/text/print?text=<?php echo $textId; ?>">
                                    <span class="icon is-small">
                                        <?php echo IconHelper::render('printer', ['alt' => 'Print']); ?>
                                    </span>
                                    <span>Print/Edit...</span>
                                </button>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="notification is-light">
                            <span class="icon-text">
                                <span class="icon has-text-grey">
                                    <?php echo IconHelper::render('x', ['alt' => 'No Annotation']); ?>
                                </span>
                                <span>None</span>
                            </span>
                            <div class="mt-2">
                                <button type="button"
                                        class="button is-small is-outlined"
                                        data-action="navigate"
                                        data-url="print_impr_text.php?edit=1&amp;text=<?php echo $textId; ?>">
                                    <span class="icon is-small">
                                        <?php echo IconHelper::render('plus', ['alt' => 'Create']); ?>
                                    </span>
                                    <span>Create/Print...</span>
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Source URI -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label" for="TxSourceURI">Source URI</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <div class="control">
                        <input type="url"
                               class="input checkurl checkoutsidebmp"
                               data_info="Source URI"
                               name="TxSourceURI"
                               id="TxSourceURI"
                               value="<?php echo \htmlspecialchars($text->source ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               maxlength="1000"
                               placeholder="https://..." />
                    </div>
                </div>
            </div>
        </div>

        <!-- Tags -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Tags</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <div class="control">
                        <?php echo \Lwt\Services\TagService::getTextTagsHtml($textId); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Media URI -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label" for="TxAudioURI" title="A soundtrack or a video to be displayed while reading">
                    Media URI
                </label>
            </div>
            <div class="field-body">
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <input type="text"
                               class="input checkoutsidebmp"
                               data_info="Audio-URI"
                               name="TxAudioURI"
                               id="TxAudioURI"
                               maxlength="2048"
                               value="<?php echo \htmlspecialchars($text->media_uri ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder="Path to audio/video file or URL" />
                    </div>
                    <div class="control" id="mediaselect">
                        <?php echo \selectmediapath('TxAudioURI'); ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($isNew && defined('YT_API_KEY') && YT_API_KEY != null) {
            \Lwt\Text_From_Youtube\do_form_fragment();
        } ?>
    </div>

    <!-- Form Actions -->
    <div class="field is-grouped is-grouped-right">
        <div class="control">
            <button type="button"
                    class="button is-light"
                    data-action="cancel-form"
                    data-url="/texts<?php echo $isNew ? '' : '#rec' . $textId; ?>">
                Cancel
            </button>
        </div>
        <div class="control">
            <button type="submit" name="op" value="Check" class="button is-info is-outlined">
                <span class="icon is-small">
                    <?php echo IconHelper::render('check', ['alt' => 'Check']); ?>
                </span>
                <span>Check</span>
            </button>
        </div>
        <div class="control">
            <button type="submit" name="op" value="<?php echo $isNew ? 'Save' : 'Change'; ?>" class="button is-primary">
                <span class="icon is-small">
                    <?php echo IconHelper::render('save', ['alt' => 'Save']); ?>
                </span>
                <span><?php echo $isNew ? 'Save' : 'Save Changes'; ?></span>
            </button>
        </div>
        <div class="control">
            <button type="submit" name="op" value="<?php echo $isNew ? 'Save' : 'Change'; ?> and Open" class="button is-success">
                <span class="icon is-small">
                    <?php echo IconHelper::render('book-open', ['alt' => 'Save and Open']); ?>
                </span>
                <span><?php echo $isNew ? 'Save' : 'Save'; ?> &amp; Open</span>
            </button>
        </div>
    </div>
</form>
<?php if ($isNew && defined('YT_API_KEY') && YT_API_KEY != null) {
    \Lwt\Text_From_Youtube\do_js();
} ?>
