<?php declare(strict_types=1);
/**
 * Long Text Import Form View
 *
 * Variables expected:
 * - $languageData: array - Mapping of language ID to language code
 * - $languagesOption: string - HTML options for language select
 * - $maxInputVars: int - Maximum input variables
 *
 * PHP version 8.1
 *
 * @category Views
 * @package  Lwt
 * @license  Unlicense <http://unlicense.org/>
 * @since    3.0.0
 */

namespace Lwt\Views\Text;

use Lwt\View\Helper\IconHelper;
use Lwt\View\Helper\PageLayoutHelper;

$actions = [
    ['url' => '/texts?new=1', 'label' => 'Short Text Import', 'icon' => 'circle-plus', 'class' => 'is-primary'],
    ['url' => '/feeds?page=1&check_autoupdate=1', 'label' => 'Newsfeed Import', 'icon' => 'rss'],
    ['url' => '/texts?query=&page=1', 'label' => 'Active Texts', 'icon' => 'book-open'],
    ['url' => '/text/archived?query=&page=1', 'label' => 'Archived Texts', 'icon' => 'archive']
];

?>
<script type="application/json" id="language-data-config"><?php echo json_encode($languageData); ?></script>

<h2 class="title is-4">Long Text Import</h2>

<?php echo PageLayoutHelper::buildActionCard('Import Options', $actions, 'texts'); ?>

<form enctype="multipart/form-data" class="validate" action="/text/import-long" method="post"
      x-data="{ inputMethod: 'paste' }">
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
                            <select name="LgID" id="TxLgID" class="notempty setfocus" required>
                                <?php echo $languagesOption; ?>
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
                               value=""
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

        <!-- Text Input Method -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Text</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <!-- Input method tabs -->
                    <div class="tabs is-boxed is-small mb-3">
                        <ul>
                            <li :class="inputMethod === 'paste' ? 'is-active' : ''">
                                <a @click.prevent="inputMethod = 'paste'">
                                    <span class="icon is-small">
                                        <?php echo IconHelper::render('clipboard', ['alt' => 'Paste']); ?>
                                    </span>
                                    <span>Paste Text</span>
                                </a>
                            </li>
                            <li :class="inputMethod === 'file' ? 'is-active' : ''">
                                <a @click.prevent="inputMethod = 'file'">
                                    <span class="icon is-small">
                                        <?php echo IconHelper::render('file-up', ['alt' => 'Upload']); ?>
                                    </span>
                                    <span>Upload File</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- File upload option -->
                    <div x-show="inputMethod === 'file'" x-transition x-cloak>
                        <div class="file has-name is-fullwidth">
                            <label class="file-label">
                                <input class="file-input" type="file" name="thefile"
                                       @change="$refs.filename.textContent = $event.target.files[0]?.name || 'No file selected'" />
                                <span class="file-cta">
                                    <span class="file-icon">
                                        <?php echo IconHelper::render('upload', ['alt' => 'Upload']); ?>
                                    </span>
                                    <span class="file-label">Choose a file...</span>
                                </span>
                                <span class="file-name" x-ref="filename">No file selected</span>
                            </label>
                        </div>
                    </div>

                    <!-- Paste text option -->
                    <div x-show="inputMethod === 'paste'" x-transition>
                        <div class="control">
                            <textarea class="textarea checkoutsidebmp"
                                      data_info="Upload"
                                      name="Upload"
                                      id="TxText"
                                      rows="12"
                                      placeholder="Paste your long text here..."></textarea>
                        </div>
                    </div>

                    <!-- Upload limits info -->
                    <p class="help has-text-grey mt-2">
                        <span class="icon is-small">
                            <?php echo IconHelper::render('info', ['alt' => 'Info']); ?>
                        </span>
                        Upload limits:
                        <strong>post_max_size</strong>: <?php echo ini_get('post_max_size'); ?>,
                        <strong>upload_max_filesize</strong>: <?php echo ini_get('upload_max_filesize'); ?>
                        <br />
                        <span class="is-size-7">
                            Adjust in "<?php echo htmlspecialchars(php_ini_loaded_file() ?? '', ENT_QUOTES, 'UTF-8'); ?>" and restart server if needed.
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Paragraph Handling -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label" for="paragraph_handling">Newlines &amp; Paragraphs</label>
            </div>
            <div class="field-body">
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <div class="select is-fullwidth">
                            <select name="paragraph_handling" id="paragraph_handling">
                                <option value="1" selected>
                                    ONE NEWLINE: Paragraph ends
                                </option>
                                <option value="2">
                                    TWO NEWLINEs: Paragraph ends (single newline becomes space)
                                </option>
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

        <!-- Maximum Sentences -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label" for="maxsent">Max Sentences per Text</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <div class="field has-addons">
                        <div class="control">
                            <input type="number"
                                   min="1"
                                   max="999"
                                   class="input notempty posintnumber"
                                   data_info="Maximum Sentences per Text"
                                   name="maxsent"
                                   id="maxsent"
                                   value="50"
                                   maxlength="3"
                                   style="width: 100px;"
                                   required />
                        </div>
                        <div class="control">
                            <span class="icon has-text-danger" title="Field must not be empty">
                                <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                            </span>
                        </div>
                    </div>
                    <p class="help has-text-grey">
                        Values higher than 100 may slow down text display.
                        Very low values (&lt; 5) may result in too many texts.
                        <br />
                        Max new texts: <?php echo ($maxInputVars - 20); ?>.
                        Each text limited to 65,000 bytes.
                    </p>
                </div>
            </div>
        </div>

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
                               value=""
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
                        <?php echo \Lwt\Services\TagService::getTextTagsHtml(0); ?>
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
                    data-action="cancel-form"
                    data-url="index.php">
                Cancel
            </button>
        </div>
        <div class="control">
            <button type="submit" name="op" value="NEXT STEP: Check the Texts" class="button is-primary">
                <span class="icon is-small">
                    <?php echo IconHelper::render('arrow-right', ['alt' => 'Next']); ?>
                </span>
                <span>Next Step: Check Texts</span>
            </button>
        </div>
    </div>
</form>
