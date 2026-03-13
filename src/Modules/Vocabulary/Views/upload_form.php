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
use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

// Type assertions for variables passed from controller
assert($currentLanguage === null || is_int($currentLanguage) || is_string($currentLanguage));
assert(is_array($languages));
/** @var array<int, array{id: int, name: string}> $languages */
/** @var string|null $activeTab */
/** @var list<array<string, mixed>>|null $curatedDictionaries */
if (!isset($curatedDictionaries)) {
    $curatedDictionaries = [];
}
$curatedDictionariesJson = json_encode(
    $curatedDictionaries,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
);

if (!isset($activeTab)) {
    $activeTab = 'dictionary';
}
// Map legacy tab values
if ($activeTab === 'text') {
    $activeTab = 'file';
}

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

<script type="application/json" id="word-upload-page-config"><?php echo json_encode(
    [
        'activeTab' => $activeTab ?: 'dictionary',
        'currentLanguageId' => ($currentLanguage !== null && $currentLanguage !== '' && $currentLanguage !== 0) ? (int) $currentLanguage : 0,
        'currentLanguageName' => $currentLanguageName ?? '',
    ],
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
); ?></script>
<div x-data="wordUploadPageApp">

<!-- ==================== LANGUAGE TABS ==================== -->
<?php if (!empty($languages)) : ?>
<div class="tabs is-toggle is-fullwidth mb-4">
    <ul>
        <?php foreach ($languages as $lang) : ?>
        <li :class="{ 'is-active': selectedLanguageId === <?php echo $lang['id']; ?> }">
            <a @click.prevent="selectLanguage(<?php echo $lang['id']; ?>, '<?php echo htmlspecialchars($lang['name'], ENT_QUOTES, 'UTF-8'); ?>')">
                <span><?php echo htmlspecialchars($lang['name'], ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- Curated Dictionaries Browser (outside form to avoid Alpine scope conflicts) -->
<script type="application/json" id="curated-dictionaries-config"><?php echo $curatedDictionariesJson; ?></script>
<div x-show="isDictionary" x-transition
     <?php echo $activeTab !== 'dictionary' ? 'style="display:none"' : ''; ?>>
    <div x-data="curatedDictBrowser" class="box mb-4">
        <p class="mb-4 has-text-grey">
            Import a curated dictionary directly, or download it to upload manually.
        </p>

        <!-- Import result notification -->
        <template x-if="importResult !== null">
            <div :class="importResult.success ? 'notification is-success is-light' : 'notification is-danger is-light'" class="mb-4">
                <button class="delete" @click="dismissResult()"></button>
                <template x-if="importResult.success">
                    <p>
                        <strong>Import successful!</strong>
                        Imported <span x-text="importResult.imported"></span> entries.
                    </p>
                </template>
                <template x-if="!importResult.success">
                    <p>
                        <strong>Import failed:</strong>
                        <span x-text="importResult.error"></span>
                    </p>
                </template>
            </div>
        </template>

        <!-- Language filter + search -->
        <div class="field is-grouped mb-4">
            <div class="control">
                <div class="select">
                    <select x-model="dictLanguageFilter">
                        <option value="">All languages</option>
                        <template x-for="group in allGroups" :key="group.language">
                            <option :value="group.language" x-text="group.languageName"></option>
                        </template>
                    </select>
                </div>
            </div>
            <div class="control is-expanded">
                <input class="input" type="search" placeholder="Search dictionaries..."
                       x-model="dictSearch" />
            </div>
        </div>

        <!-- Dictionary cards grouped by language -->
        <template x-if="filteredGroups.length === 0">
            <div class="notification is-light">
                No dictionaries match your search.
            </div>
        </template>

        <template x-for="group in filteredGroups" :key="group.language">
            <div class="mb-5">
                <h3 class="title is-5 mb-3" x-text="group.languageName"></h3>
                <div class="columns is-multiline">
                    <template x-for="source in group.sources" :key="source.name">
                        <div class="column is-half-tablet is-one-third-desktop">
                            <div class="card">
                                <div class="card-content p-4">
                                    <p class="title is-6 mb-2" x-text="source.name"></p>
                                    <div class="tags mb-2">
                                        <span class="tag is-info is-light" x-text="source.format"></span>
                                        <span class="tag is-light" x-text="source.entries"></span>
                                        <span class="tag is-success is-light" x-text="source.license"></span>
                                    </div>
                                    <p class="is-size-7 has-text-grey" x-text="source.notes"></p>
                                </div>
                                <footer class="card-footer">
                                    <a class="card-footer-item has-text-success"
                                       x-show="source.directDownload"
                                       @click.prevent="importCurated(source)">
                                        <span class="icon is-small mr-1" x-show="!isImporting(source.url)">
                                            <?php echo IconHelper::render('download', ['alt' => 'Import']); ?>
                                        </span>
                                        <span x-text="isImporting(source.url) ? 'Importing...' : 'Import'"></span>
                                    </a>
                                    <a class="card-footer-item has-text-primary"
                                       :href="source.url" target="_blank" rel="noopener">
                                        <span class="icon is-small mr-1">
                                            <?php echo IconHelper::render('external-link', ['alt' => 'Download']); ?>
                                        </span>
                                        Download
                                    </a>
                                </footer>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>
</div>

<form enctype="multipart/form-data"
      class="validate"
      action="/word/upload"
      method="post"
      x-data="wordUploadFormApp">
    <?php echo \Lwt\Shared\UI\Helpers\FormHelper::csrfField(); ?>
    <!-- Language ID synced from tabs above -->
    <input type="hidden" name="LgID" :value="selectedLanguageId" />

    <!-- ==================== INPUT SOURCE ==================== -->
    <div class="box">
        <!-- Import Source Tabs -->
        <div class="field">
            <label class="label">Import from</label>
            <div class="tabs is-boxed is-small mb-3">
                <ul>
                    <li :class="{ 'is-active': inputMethod === 'dictionary' }">
                        <a @click.prevent="setInputMethod('dictionary')">
                            <span class="icon is-small">
                                <?php echo IconHelper::render('book-open', ['alt' => 'Dictionary']); ?>
                            </span>
                            <span>Dictionary File</span>
                        </a>
                    </li>
                    <li :class="{ 'is-active': inputMethod === 'file' }">
                        <a @click.prevent="setInputMethod('file')">
                            <span class="icon is-small">
                                <?php echo IconHelper::render('file-up', ['alt' => 'File']); ?>
                            </span>
                            <span>CSV / TSV File</span>
                        </a>
                    </li>
                    <li :class="{ 'is-active': inputMethod === 'paste' }">
                        <a @click.prevent="setInputMethod('paste')">
                            <span class="icon is-small">
                                <?php echo IconHelper::render('clipboard-paste', ['alt' => 'Paste']); ?>
                            </span>
                            <span>Paste Text</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Dictionary File -->
            <div x-show="isDictionary" x-transition
                 <?php echo $activeTab !== 'dictionary' ? 'style="display:none"' : ''; ?>>

                <!-- Upload section -->
                <h5 class="title is-6 mb-3">Upload a dictionary file</h5>
                <div class="field mb-3">
                    <label class="label is-small">File Format</label>
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
                               @change="updateDictFileName($event)" />
                        <span class="file-cta">
                            <span class="file-icon">
                                <?php echo IconHelper::render('upload', ['alt' => 'Upload']); ?>
                            </span>
                            <span class="file-label">Choose a file...</span>
                        </span>
                        <span class="file-name" x-text="dictFileLabel"></span>
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
        </div>
    </div>

    <!-- ==================== FORMAT SETTINGS (text/paste modes) ==================== -->
    <div class="box" x-show="isNotDictionary" x-transition
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
                    @click="addColumn()">
                <span class="icon is-small">
                    <?php echo IconHelper::render('plus', ['alt' => 'Add']); ?>
                </span>
                <span>Add column</span>
            </button>
            <button type="button"
                    class="button is-small is-light"
                    x-show="extraCols > 0"
                    @click="removeColumn()">
                <span class="icon is-small">
                    <?php echo IconHelper::render('minus', ['alt' => 'Remove']); ?>
                </span>
                <span>Remove column</span>
            </button>
        </div>

        <!-- Live Preview -->
        <div class="mt-3" x-show="hasPreview()" x-transition>
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
    <div class="box" x-show="showDictCsvOptions" x-transition
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
    <div class="box" x-show="isNotDictionary" x-transition
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
                                    @change="updateImportMode($event)">
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
    <article class="message is-warning" x-show="isNotDictionary">
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
        <div class="control" x-show="isNotDictionary">
            <button type="submit" name="op" value="Import" class="button is-primary">
                <span class="icon is-small">
                    <?php echo IconHelper::render('upload', ['alt' => 'Import']); ?>
                </span>
                <span>Import Terms</span>
            </button>
        </div>
        <div class="control" x-show="isDictionary">
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
</div><!-- /x-data inputMethod wrapper -->

