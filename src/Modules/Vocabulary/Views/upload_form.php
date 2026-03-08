<?php

/**
 * Word Upload Form View
 *
 * Displays a unified form for importing terms via paste, CSV/TSV file,
 * or dictionary file (JSON, StarDict).
 *
 * Expected variables:
 * - $currentLanguage: Current language setting (from settings)
 * - $languages: array - Array of languages for select dropdown
 * - $activeTab: string - Active input tab ('paste', 'file', or 'dictionary')
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views\Word
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Views\Word;

use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Shared\UI\Helpers\SelectOptionsBuilder;
use Lwt\Shared\UI\Helpers\SearchableSelectHelper;
use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

// Type assertions for variables passed from controller
assert($currentLanguage === null || is_int($currentLanguage) || is_string($currentLanguage));
assert(is_array($languages));
/** @var array<int, array{id: int, name: string}> $languages */
/** @var string|null $activeTab */

if (!isset($activeTab)) {
    $activeTab = 'file';
}
// Map legacy tab values
if ($activeTab === 'text') {
    $activeTab = 'file';
}

$langToUse = $currentLanguage;

// Column options for reuse (text/paste modes)
$columnOptions = [
    'w' => 'Term',
    't' => 'Translation',
    'r' => 'Romanization',
    's' => 'Sentence',
    'g' => 'Tag List',
    'x' => "Don't import"
];

// Action buttons for navigation
$actions = [
    ['url' => '/words', 'label' => 'My Terms', 'icon' => 'list', 'class' => 'is-primary'],
    ['url' => '/term-tags', 'label' => 'Term Tags', 'icon' => 'tags'],
    ['url' => '/', 'label' => 'Home', 'icon' => 'home']
];
echo PageLayoutHelper::buildActionCard($actions);
?>

<form enctype="multipart/form-data"
      class="validate"
      action="/word/upload"
      method="post"
      x-data="{
          inputMethod: '<?php echo htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8'); ?>',
          importMode: '0',
          showDelimiter: false,
          delimiter: 'c',
          cols: ['w', 't', 'x', 'x', 'x'],
          extraCols: 0,
          dictFormat: 'csv',
          dictFileName: '',
          _labels: {w:'Term',t:'Translation',r:'Romanization',s:'Sentence',g:'Tags'},
          _examples: {w:'Haus',t:'house',r:'haus',s:'Das Haus ist gross.',g:'A1 housing'},
          previewHeaders() {
              return this.cols.map(c => this._labels[c]).filter(Boolean);
          },
          previewRow() {
              return this.cols.map(c => this._examples[c]).filter(Boolean);
          },
          isDictMode() {
              return this.inputMethod === 'dictionary';
          }
      }">
    <?php echo \Lwt\Shared\UI\Helpers\FormHelper::csrfField(); ?>

    <!-- ==================== LANGUAGE & INPUT SOURCE ==================== -->
    <div class="box">
        <!-- Language Selection -->
        <div class="field">
            <label class="label">Language</label>
            <div class="field has-addons">
                <div class="control is-expanded">
                    <?php echo SearchableSelectHelper::forLanguages(
                        $languages,
                        $langToUse,
                        [
                            'name' => 'LgID',
                            'id' => 'LgID',
                            'placeholder' => '[Choose...]',
                            'required' => true
                        ]
                    ); ?>
                </div>
                <div class="control">
                    <span class="icon has-text-danger mt-2" title="Required">
                        <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Import Source Tabs -->
        <div class="field">
            <label class="label">Import from</label>
            <div class="tabs is-boxed is-small mb-3">
                <ul>
                    <li :class="{ 'is-active': inputMethod === 'file' }">
                        <a @click.prevent="inputMethod = 'file'">
                            <span class="icon is-small">
                                <?php echo IconHelper::render('file-up', ['alt' => 'File']); ?>
                            </span>
                            <span>CSV / TSV File</span>
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
                    <li :class="{ 'is-active': inputMethod === 'dictionary' }">
                        <a @click.prevent="inputMethod = 'dictionary'">
                            <span class="icon is-small">
                                <?php echo IconHelper::render('book-open', ['alt' => 'Dictionary']); ?>
                            </span>
                            <span>Dictionary File</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- CSV/TSV File Upload -->
            <div x-show="inputMethod === 'file'" x-transition
                 <?php echo $activeTab !== 'file' ? 'style="display:none"' : ''; ?>>
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
                <p class="help">CSV, TSV, or text file with one term per line</p>
            </div>

            <!-- Paste Text -->
            <div x-show="inputMethod === 'paste'" x-transition
                 <?php echo $activeTab !== 'paste' ? 'style="display:none"' : ''; ?>>
                <div class="control">
                    <textarea class="textarea checkoutsidebmp"
                              data_info="Upload"
                              name="Upload"
                              rows="10"
                              placeholder="One term per line, e.g.:&#10;Haus,house&#10;Katze,cat"></textarea>
                </div>
                <p class="help">One term per line, using the delimiter chosen below</p>
            </div>

            <!-- Dictionary File -->
            <div x-show="inputMethod === 'dictionary'" x-transition
                 <?php echo $activeTab !== 'dictionary' ? 'style="display:none"' : ''; ?>>
                <div class="field mb-3">
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="dict_format" x-model="dictFormat">
                                <option value="csv">CSV / TSV dictionary</option>
                                <option value="json">JSON</option>
                                <option value="stardict">StarDict (.ifo file)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="file has-name is-fullwidth">
                    <label class="file-label">
                        <input class="file-input" type="file" name="dict_file"
                               @change="dictFileName = $event.target.files[0]?.name || ''" />
                        <span class="file-cta">
                            <span class="file-icon">
                                <?php echo IconHelper::render('upload', ['alt' => 'Upload']); ?>
                            </span>
                            <span class="file-label">Choose a file...</span>
                        </span>
                        <span class="file-name" x-text="dictFileName || 'No file selected'"></span>
                    </label>
                </div>
                <p class="help" x-show="dictFormat === 'stardict'">
                    Select the .ifo file. The .idx and .dict files must be in the same directory.
                </p>
                <div class="field mt-3">
                    <label class="label is-small">Dictionary Name</label>
                    <div class="control">
                        <input type="text" name="dict_name" class="input is-small"
                               placeholder="Auto-generated from filename if empty">
                    </div>
                </div>

                <!-- Where to find dictionaries (collapsible) -->
                <details class="mt-4">
                    <summary class="is-flex is-align-items-center is-clickable has-text-grey">
                        <span class="icon mr-1">
                            <?php echo IconHelper::render('lightbulb', ['alt' => 'Tips']); ?>
                        </span>
                        Where to find dictionary files?
                    </summary>
                    <div class="content is-small mt-3">
                        <p>Free downloadable dictionaries you can import:</p>

                        <h5 class="mb-2">StarDict Format (recommended)</h5>
                        <ul>
                            <li>
                                <a href="https://freedict.org/downloads/" target="_blank" rel="noopener">FreeDict</a>
                                &mdash; 140+ free bilingual dictionaries in ~45 languages
                            </li>
                            <li>
                                <a href="https://download.wikdict.com/dictionaries/stardict/" target="_blank" rel="noopener">WikDict</a>
                                &mdash; 17M+ translations across 26 languages, from Wiktionary
                            </li>
                            <li>
                                <a href="https://github.com/Vuizur/Wiktionary-Dictionaries" target="_blank" rel="noopener">Wiktionary Dictionaries</a>
                                &mdash; StarDict and TSV for nearly all languages
                            </li>
                            <li>
                                <a href="https://archive.org/details/stardict_collections" target="_blank" rel="noopener">Internet Archive StarDict</a>
                                &mdash; Archived StarDict collections in many languages
                            </li>
                        </ul>

                        <h5 class="mb-2">CSV / TSV Format</h5>
                        <ul>
                            <li>
                                <a href="https://tatoeba.org/en/downloads" target="_blank" rel="noopener">Tatoeba</a>
                                &mdash; Bilingual sentence pairs in 400+ languages (tab-separated)
                            </li>
                            <li>
                                <a href="https://www.manythings.org/anki/" target="_blank" rel="noopener">ManyThings.org</a>
                                &mdash; Pre-formatted bilingual pairs from Tatoeba (tab-separated)
                            </li>
                        </ul>

                        <h5 class="mb-2">Language-Specific</h5>
                        <ul>
                            <li>
                                <a href="https://www.mdbg.net/chinese/dictionary?page=cedict" target="_blank" rel="noopener">CC-CEDICT</a>
                                &mdash; 124,000+ entry Chinese-English dictionary
                            </li>
                            <li>
                                <a href="http://www.edrdg.org/jmdict/edict.html" target="_blank" rel="noopener">JMdict / EDICT</a>
                                &mdash; 214,000+ entry Japanese-English dictionary
                            </li>
                            <li>
                                <a href="https://kaikki.org/dictionary/rawdata.html" target="_blank" rel="noopener">Kaikki.org</a>
                                &mdash; Machine-readable Wiktionary data for hundreds of languages
                            </li>
                        </ul>

                        <p class="has-text-grey mt-2">
                            Tip: For StarDict files, select the .ifo file &mdash; the .idx and .dict
                            files must be in the same directory.
                        </p>
                    </div>
                </details>
            </div>
        </div>
    </div>

    <!-- ==================== FORMAT SETTINGS (text/paste modes) ==================== -->
    <div class="box" x-show="!isDictMode()" x-transition
         <?php echo $activeTab === 'dictionary' ? 'style="display:none"' : ''; ?>>
        <h4 class="title is-5 mb-4">
            <span class="icon-text">
                <span class="icon">
                    <?php echo IconHelper::render('settings-2', ['alt' => 'Settings']); ?>
                </span>
                <span>Format Settings</span>
            </span>
        </h4>

        <div class="notification is-light is-small mb-4">
            Each line in your file should have fields separated by the
            delimiter you choose below, e.g.:
            <code>word,translation,romanization,sentence,tags</code>
        </div>

        <div class="columns">
            <div class="column is-half">
                <div class="field">
                    <label class="label is-small">Field Delimiter</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="Tab" x-model="delimiter">
                                <option value="c">Comma "," (CSV, LingQ)</option>
                                <option value="t">Tab (TSV)</option>
                                <option value="h">Hash "#" (direct input)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="column is-half">
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
            for ($i = 1; $i <= 2; $i++) {
                $default = $columnDefaults[$i - 1];
                $colIndex = $i - 1;
                ?>
            <div class="column is-half-tablet">
                <div class="field">
                    <label class="label is-small">Column <?php echo $i; ?></label>
                    <div class="control">
                        <div class="select is-fullwidth is-small">
                            <select name="Col<?php echo $i; ?>"
                                    x-model="cols[<?php echo $colIndex; ?>]">
                                <?php foreach ($columnOptions as $val => $label) : ?>
                                <option value="<?php echo $val; ?>"<?php
                                    echo ($val === $default) ? ' selected' : '';
                                ?>>
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

        <!-- Extra columns (shown on demand) -->
        <?php for ($i = 3; $i <= 5; $i++) {
            $colIndex = $i - 1;
            ?>
        <div class="columns" x-show="extraCols >= <?php echo $i - 2; ?>" x-transition>
            <div class="column is-half-tablet">
                <div class="field">
                    <label class="label is-small">Column <?php echo $i; ?></label>
                    <div class="control">
                        <div class="select is-fullwidth is-small">
                            <select name="Col<?php echo $i; ?>"
                                    x-model="cols[<?php echo $colIndex; ?>]">
                                <?php foreach ($columnOptions as $val => $label) : ?>
                                <option value="<?php echo $val; ?>"<?php
                                    echo ($val === 'x') ? ' selected' : '';
                                ?>>
                                    <?php echo $label; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>

        <div class="buttons mt-2">
            <button type="button"
                    class="button is-small is-light"
                    x-show="extraCols < 3"
                    @click="extraCols++">
                <span class="icon is-small">
                    <?php echo IconHelper::render('plus', ['alt' => 'Add']); ?>
                </span>
                <span>Add column</span>
            </button>
            <button type="button"
                    class="button is-small is-light"
                    x-show="extraCols > 0"
                    @click="cols[1 + extraCols] = 'x'; extraCols--">
                <span class="icon is-small">
                    <?php echo IconHelper::render('minus', ['alt' => 'Remove']); ?>
                </span>
                <span>Remove column</span>
            </button>
        </div>

        <!-- Live Preview -->
        <div class="mt-3" x-show="previewHeaders().length > 0" x-transition>
            <h5 class="title is-6 mb-2">Preview</h5>
            <div class="table-container">
                <table class="table is-bordered is-narrow is-size-7 is-fullwidth">
                    <thead>
                        <tr>
                            <template x-for="header in previewHeaders()" :key="header">
                                <th x-text="header" class="has-background-light"></th>
                            </template>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <template x-for="(cell, i) in previewRow()" :key="i">
                                <td x-text="cell"></td>
                            </template>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="help has-text-grey">
                Example of how your file will be parsed with the current settings.
            </p>
        </div>
    </div>

    <!-- ==================== DICTIONARY CSV OPTIONS (dict CSV mode only) ==================== -->
    <div class="box" x-show="isDictMode() && dictFormat === 'csv'" x-transition
         <?php echo $activeTab !== 'dictionary' ? 'style="display:none"' : ''; ?>>
        <h4 class="title is-5 mb-4">
            <span class="icon-text">
                <span class="icon">
                    <?php echo IconHelper::render('settings-2', ['alt' => 'Settings']); ?>
                </span>
                <span>CSV Options</span>
            </span>
        </h4>

        <div class="columns">
            <div class="column is-one-third">
                <div class="field">
                    <label class="label is-small">Delimiter</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="dict_delimiter">
                                <option value=",">Comma (,)</option>
                                <option value="tab">Tab</option>
                                <option value=";">Semicolon (;)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="column is-one-third">
                <div class="field">
                    <label class="label is-small">First Row</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="dict_has_header">
                                <option value="yes">Header row</option>
                                <option value="no">Data row</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="columns">
            <div class="column is-one-third">
                <div class="field">
                    <label class="label is-small">Term Column</label>
                    <div class="control">
                        <input type="number" name="dict_term_column" class="input is-small"
                               value="0" min="0">
                    </div>
                    <p class="help">0 = first column</p>
                </div>
            </div>
            <div class="column is-one-third">
                <div class="field">
                    <label class="label is-small">Definition Column</label>
                    <div class="control">
                        <input type="number" name="dict_definition_column" class="input is-small"
                               value="1" min="0">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== IMPORT OPTIONS (text/paste modes) ==================== -->
    <div class="box" x-show="!isDictMode()" x-transition
         <?php echo $activeTab === 'dictionary' ? 'style="display:none"' : ''; ?>>
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

    <!-- ==================== WARNING & SUBMIT ==================== -->
    <article class="message is-warning" x-show="!isDictMode()">
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
        <div class="control" x-show="!isDictMode()">
            <button type="submit" name="op" value="Import" class="button is-primary">
                <span class="icon is-small">
                    <?php echo IconHelper::render('upload', ['alt' => 'Import']); ?>
                </span>
                <span>Import Terms</span>
            </button>
        </div>
        <div class="control" x-show="isDictMode()">
            <button type="submit" name="op" value="ImportDictionary" class="button is-primary">
                <span class="icon is-small">
                    <?php echo IconHelper::render('upload', ['alt' => 'Import']); ?>
                </span>
                <span>Import Dictionary</span>
            </button>
        </div>
    </div>
</form>

<!-- Help notes (context-sensitive) -->
<article class="message is-light mt-5" x-data="{ inputMethod: '<?php echo htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8'); ?>' }">
    <div class="message-body is-size-7">
        <p>
            <strong>Note:</strong> Sentences should contain the term in curly brackets, e.g., "... {term} ...".
            If not, such sentences can be automatically created later with the
            "Set Term Sentences" action in the
            <a href="/texts?query=&amp;page=1" class="has-text-link">Texts</a> screen.
        </p>
    </div>
</article>

