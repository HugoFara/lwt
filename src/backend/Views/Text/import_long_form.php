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

use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

// Type assertions for view variables
/** @var array<int, string> $languageData */
$languageData = $languageData ?? [];
$languagesOption = (string) ($languagesOption ?? '');
$maxInputVars = (int) ($maxInputVars ?? 1000);

$actions = [
    ['url' => '/texts?new=1', 'label' => 'Short Text Import', 'icon' => 'circle-plus', 'class' => 'is-primary'],
    ['url' => '/feeds?page=1&check_autoupdate=1', 'label' => 'Newsfeed Import', 'icon' => 'rss'],
    ['url' => '/texts?query=&page=1', 'label' => 'Active Texts', 'icon' => 'book-open'],
    ['url' => '/text/archived?query=&page=1', 'label' => 'Archived Texts', 'icon' => 'archive']
];

?>
<script type="application/json" id="language-data-config"><?php echo json_encode($languageData); ?></script>

<h2 class="title is-4">Long Text Import</h2>

<?php echo PageLayoutHelper::buildActionCard($actions); ?>

<form enctype="multipart/form-data" class="validate" action="/text/import-long" method="post"
      x-data="{ inputMethod: 'paste' }">
    <div class="box">
        <!-- Language -->
        <div class="field">
            <label class="label" for="TxLgID">
                Language
                <span class="icon has-text-danger is-small" title="Required field">
                    <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                </span>
            </label>
            <div class="control">
                <div class="select is-fullwidth">
                    <select name="LgID" id="TxLgID" class="notempty setfocus"
                            title="Select the language of your text"
                            required>
                        <?php echo $languagesOption; ?>
                    </select>
                </div>
            </div>
            <p class="help">The language determines how the text will be parsed into words and sentences.</p>
        </div>

        <!-- Title -->
        <div class="field">
            <label class="label" for="TxTitle">
                Title
                <span class="icon has-text-danger is-small" title="Required field">
                    <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                </span>
            </label>
            <div class="control">
                <input type="text"
                       class="input notempty checkoutsidebmp"
                       data_info="Title"
                       name="TxTitle"
                       id="TxTitle"
                       value=""
                       maxlength="200"
                       placeholder="Enter a base title (will be numbered automatically)"
                       title="Base title for the imported texts"
                       required />
            </div>
            <p class="help">Each text section will be numbered (e.g., "My Book (1)", "My Book (2)").</p>
        </div>

        <!-- Text Input Method -->
        <div class="field">
            <label class="label">
                Text
                <span class="icon has-text-danger is-small" title="Required field">
                    <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                </span>
            </label>
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
                               title="Select a text file to upload"
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
                              placeholder="Paste your long text here..."
                              title="The long text to be split into smaller sections"></textarea>
                </div>
            </div>

            <!-- Upload limits info -->
            <p class="help mt-2">
                Upload limits:
                <strong>post_max_size</strong>: <?php echo ini_get('post_max_size'); ?>,
                <strong>upload_max_filesize</strong>: <?php echo ini_get('upload_max_filesize'); ?>
                <br />
                <span class="is-size-7">
                    <?php $iniFile = php_ini_loaded_file(); ?>
                    Adjust in "<?php echo htmlspecialchars($iniFile !== false ? $iniFile : '', ENT_QUOTES, 'UTF-8'); ?>" and restart server if needed.
                </span>
            </p>
        </div>

        <!-- Paragraph Handling -->
        <div class="field">
            <label class="label" for="paragraph_handling">
                Newlines &amp; Paragraphs
                <span class="icon has-text-danger is-small" title="Required field">
                    <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                </span>
            </label>
            <div class="control">
                <div class="select is-fullwidth">
                    <select name="paragraph_handling" id="paragraph_handling"
                            title="How to handle line breaks in the text">
                        <option value="1" selected>
                            ONE NEWLINE: Paragraph ends
                        </option>
                        <option value="2">
                            TWO NEWLINEs: Paragraph ends (single newline becomes space)
                        </option>
                    </select>
                </div>
            </div>
            <p class="help">Choose how line breaks should be interpreted when splitting into paragraphs.</p>
        </div>

        <!-- Maximum Sentences -->
        <div class="field">
            <label class="label" for="maxsent">
                Max Sentences per Text
                <span class="icon has-text-danger is-small" title="Required field">
                    <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                </span>
            </label>
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
                       style="width: 150px;"
                       placeholder="50"
                       title="Number of sentences before creating a new text section"
                       required />
            </div>
            <p class="help">
                Recommended: 20-100. Higher values may slow down text display. Very low values (&lt; 5) create many small texts.
                <br />
                Max new texts: <?php echo ($maxInputVars - 20); ?>. Each text limited to 65,000 bytes.
            </p>
        </div>

        <!-- Source URI -->
        <div class="field">
            <label class="label" for="TxSourceURI">Source URI</label>
            <div class="control">
                <input type="url"
                       class="input checkurl checkoutsidebmp"
                       data_info="Source URI"
                       name="TxSourceURI"
                       id="TxSourceURI"
                       value=""
                       maxlength="1000"
                       placeholder="https://example.com/article"
                       title="Link to the original source of this text" />
            </div>
            <p class="help">Optional. The original webpage or document where this text came from.</p>
        </div>

        <!-- Tags -->
        <div class="field">
            <label class="label" title="Organize texts with tags for easy filtering">Tags</label>
            <div class="control">
                <?php echo \Lwt\Modules\Tags\Application\TagsFacade::getTextTagsHtml(0); ?>
            </div>
            <p class="help">Optional. Add tags to categorize and filter your texts. Tags will be applied to all imported sections.</p>
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
