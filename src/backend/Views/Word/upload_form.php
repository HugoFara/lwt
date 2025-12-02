<?php declare(strict_types=1);
/**
 * Word Upload Form View
 *
 * Displays the form for importing terms from file or text.
 *
 * Expected variables:
 * - $currentLanguage: Current language setting (from settings)
 * - $languages: array - Array of languages for select dropdown
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views\Word
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Views\Word;

use Lwt\Database\Settings;
use Lwt\View\Helper\SelectOptionsBuilder;
use Lwt\View\Helper\IconHelper;

/** @var string|null $currentLanguage */
/** @var array $languages */

$langToUse = isset($currentLanguage) ? $currentLanguage : Settings::get('currentlanguage');

// Column options for reuse
$columnOptions = [
    'w' => 'Term',
    't' => 'Translation',
    'r' => 'Romanization',
    's' => 'Sentence',
    'g' => 'Tag List',
    'x' => "Don't import"
];
?>

<!-- Info Message -->
<article class="message is-info mb-4">
    <div class="message-body">
        <p>
            <strong>Important:</strong> You must specify the term.
            Translation, romanization, sentence and tag list are optional.
            The tag list must be separated either by spaces or commas.
        </p>
    </div>
</article>

<form enctype="multipart/form-data"
      class="validate"
      action="/word/upload"
      method="post"
      x-data="{
          importMode: '0',
          showDelimiter: false,
          inputMethod: 'file'
      }">

    <div class="box">
        <!-- Language Selection -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Language</label>
            </div>
            <div class="field-body">
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <div class="select is-fullwidth">
                            <select name="LgID" class="notempty setfocus" required>
                                <?php echo SelectOptionsBuilder::forLanguages($languages, $langToUse, '[Choose...]'); ?>
                            </select>
                        </div>
                    </div>
                    <div class="control">
                        <span class="icon has-text-danger mt-2" title="Required">
                            <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import Data Source -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Import Data</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <!-- Tab-style selector -->
                    <div class="tabs is-boxed is-small mb-3">
                        <ul>
                            <li :class="{ 'is-active': inputMethod === 'file' }">
                                <a @click.prevent="inputMethod = 'file'">
                                    <span class="icon is-small">
                                        <?php echo IconHelper::render('file-up', ['alt' => 'File']); ?>
                                    </span>
                                    <span>Upload File</span>
                                </a>
                            </li>
                            <li :class="{ 'is-active': inputMethod === 'paste' }">
                                <a @click.prevent="inputMethod = 'paste'">
                                    <span class="icon is-small">
                                        <?php echo IconHelper::render('clipboard-paste', ['alt' => 'Paste']); ?>
                                    </span>
                                    <span>Paste Text</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- File Upload -->
                    <div x-show="inputMethod === 'file'" x-transition>
                        <div class="file has-name is-fullwidth">
                            <label class="file-label">
                                <input class="file-input" type="file" name="thefile" />
                                <span class="file-cta">
                                    <span class="file-icon">
                                        <?php echo IconHelper::render('upload', ['alt' => 'Upload']); ?>
                                    </span>
                                    <span class="file-label">Choose a file...</span>
                                </span>
                                <span class="file-name">No file selected</span>
                            </label>
                        </div>
                        <p class="help">Supports CSV, TSV, or text files</p>
                    </div>

                    <!-- Text Paste -->
                    <div x-show="inputMethod === 'paste'" x-transition x-cloak>
                        <div class="control">
                            <textarea class="textarea checkoutsidebmp"
                                      data_info="Upload"
                                      name="Upload"
                                      rows="12"
                                      placeholder="Paste your terms here..."></textarea>
                        </div>
                        <p class="help">Type or paste data directly (don't specify a file when using this option)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Format Settings -->
    <div class="box">
        <h4 class="title is-5 mb-4">
            <span class="icon-text">
                <span class="icon">
                    <?php echo IconHelper::render('settings-2', ['alt' => 'Settings']); ?>
                </span>
                <span>Format Settings</span>
            </span>
        </h4>

        <div class="notification is-light is-small mb-4">
            <strong>Format per line:</strong> C1 D C2 D C3 D C4 D C5
            <span class="has-text-grey">(where D is delimiter)</span>
        </div>

        <div class="columns">
            <div class="column is-half">
                <!-- Field Delimiter -->
                <div class="field">
                    <label class="label is-small">Field Delimiter "D"</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="Tab">
                                <option value="c" selected>Comma "," [CSV File, LingQ]</option>
                                <option value="t">TAB (ASCII 9) [TSV File]</option>
                                <option value="h">Hash "#" [Direct Input]</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="column is-half">
                <!-- Ignore First Line -->
                <div class="field">
                    <label class="label is-small">Ignore First Line</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="IgnFirstLine">
                                <option value="0" selected>No</option>
                                <option value="1">Yes (header row)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Column Assignment -->
        <h5 class="title is-6 mt-4 mb-3">Column Assignment</h5>
        <div class="columns is-multiline">
            <?php
            $columnDefaults = ['w', 't', 'x', 'x', 'x'];
            for ($i = 1; $i <= 5; $i++) {
                $default = $columnDefaults[$i - 1];
                ?>
            <div class="column is-one-fifth-desktop is-half-tablet">
                <div class="field">
                    <label class="label is-small">
                        <span class="tag is-light">C<?php echo $i; ?></span>
                    </label>
                    <div class="control">
                        <div class="select is-fullwidth is-small">
                            <select name="Col<?php echo $i; ?>">
                                <?php foreach ($columnOptions as $val => $label): ?>
                                <option value="<?php echo $val; ?>"<?php echo ($val === $default) ? ' selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>

    <!-- Import Options -->
    <div class="box">
        <h4 class="title is-5 mb-4">
            <span class="icon-text">
                <span class="icon">
                    <?php echo IconHelper::render('package-import', ['alt' => 'Import']); ?>
                </span>
                <span>Import Options</span>
            </span>
        </h4>

        <div class="columns">
            <div class="column is-half">
                <!-- Import Mode -->
                <div class="field">
                    <label class="label is-small">Import Mode</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="Over"
                                    data-action="update-import-mode"
                                    x-model="importMode"
                                    @change="showDelimiter = ['4', '5'].includes($event.target.value)">
                                <option value="0" title="Don't overwrite existing terms, import new terms">
                                    Import only new terms
                                </option>
                                <option value="1" title="Overwrite existing terms, import new terms">
                                    Replace all fields
                                </option>
                                <option value="2" title="Update only empty fields, import new terms">
                                    Update empty fields
                                </option>
                                <option value="3" title="Overwrite existing with new non-empty values, no new terms">
                                    No new terms
                                </option>
                                <option value="4" title="Add new translations to existing ones, import new terms">
                                    Merge translation fields
                                </option>
                                <option value="5" title="Add new translations to existing ones, no new terms">
                                    Update existing translations
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Translation Delimiter (conditional) -->
                <div class="field mt-3" x-show="showDelimiter" x-transition x-cloak>
                    <label class="label is-small">Import Translation Delimiter</label>
                    <div class="field has-addons">
                        <div class="control">
                            <input class="input is-small notempty"
                                   type="text"
                                   name="transl_delim"
                                   style="width: 5em;"
                                   value="<?php echo Settings::getWithDefault('set-term-translation-delimiters'); ?>" />
                        </div>
                        <div class="control">
                            <span class="icon has-text-danger mt-1" title="Required">
                                <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="column is-half">
                <!-- Status -->
                <div class="field">
                    <label class="label is-small">Status for All Uploaded Terms</label>
                    <div class="field has-addons">
                        <div class="control is-expanded">
                            <div class="select is-fullwidth">
                                <select class="notempty" name="WoStatus" required>
                                    <?php echo SelectOptionsBuilder::forWordStatus(null, false, false); ?>
                                </select>
                            </div>
                        </div>
                        <div class="control">
                            <span class="icon has-text-danger mt-2" title="Required">
                                <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Warning & Actions -->
    <article class="message is-warning">
        <div class="message-body">
            <div class="level">
                <div class="level-left">
                    <div class="level-item">
                        <span class="icon is-medium">
                            <?php echo IconHelper::render('alert-triangle', ['alt' => 'Warning']); ?>
                        </span>
                    </div>
                    <div class="level-item">
                        <div>
                            <p class="has-text-weight-bold">A database backup may be advisable!</p>
                            <p class="is-size-7">Please double-check everything before importing.</p>
                        </div>
                    </div>
                </div>
                <div class="level-right">
                    <div class="level-item">
                        <button type="button"
                                class="button is-warning is-outlined is-small"
                                data-action="navigate"
                                data-url="/admin/backup">
                            <span class="icon is-small">
                                <?php echo IconHelper::render('database', ['alt' => 'Backup']); ?>
                            </span>
                            <span>Backup</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </article>

    <!-- Form Actions -->
    <div class="field is-grouped is-grouped-right">
        <div class="control">
            <button type="button"
                    class="button is-light"
                    data-action="navigate"
                    data-url="/">
                <span class="icon is-small">
                    <?php echo IconHelper::render('arrow-left', ['alt' => 'Back']); ?>
                </span>
                <span>Back</span>
            </button>
        </div>
        <div class="control">
            <button type="submit" name="op" value="Import" class="button is-primary">
                <span class="icon is-small">
                    <?php echo IconHelper::render('upload', ['alt' => 'Import']); ?>
                </span>
                <span>Import</span>
            </button>
        </div>
    </div>
</form>

<!-- Help Note -->
<article class="message is-light mt-5">
    <div class="message-body is-size-7">
        <p>
            <strong>Note:</strong> Sentences should contain the term in curly brackets, e.g., "... {term} ...".
            If not, such sentences can be automatically created later with the
            "Set Term Sentences" action in the
            <a href="/texts?query=&amp;page=1" class="has-text-link">Texts</a> screen.
        </p>
    </div>
</article>
