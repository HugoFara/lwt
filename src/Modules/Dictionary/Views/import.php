<?php declare(strict_types=1);
/**
 * Local Dictionary Import View
 *
 * Variables expected:
 * - $langId: int current language ID
 * - $langName: string current language name
 * - $dictionary: LocalDictionary entity or null
 * - $dictionaries: array of LocalDictionary entities for this language
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Dictionary\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Dictionary\Views;

use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

// Error handling
$errorRaw = $_GET['error'] ?? '';
$error = is_string($errorRaw) ? $errorRaw : '';
if (!empty($error)):
?>
<div class="notification is-danger is-light mb-4">
    <button class="delete" onclick="this.parentElement.remove()"></button>
    <?php echo htmlspecialchars(urldecode($error), ENT_QUOTES); ?>
</div>
<?php endif; ?>

<?php
echo PageLayoutHelper::buildActionCard([
    ['url' => "/dictionaries?lang=$langId", 'label' => 'Back to Dictionaries', 'icon' => 'arrow-left'],
]);
?>

<div class="box" x-data="dictionaryImport()">
    <h3 class="title is-4">Import Dictionary</h3>
    <p class="subtitle is-6">Import dictionary entries from CSV, JSON, or StarDict files.</p>

    <form method="POST" action="/dictionaries/import" enctype="multipart/form-data"
          @submit="submitting = true">
        <input type="hidden" name="lang_id" value="<?php echo $langId; ?>">

        <!-- Dictionary Selection -->
        <div class="field">
            <label class="label">Dictionary</label>
            <div class="control">
                <?php if ($dictionary): ?>
                <input type="hidden" name="dict_id" value="<?php echo $dictionary->id(); ?>">
                <input type="text" class="input" value="<?php echo htmlspecialchars($dictionary->name(), ENT_QUOTES); ?>" readonly>
                <p class="help">Adding entries to existing dictionary.</p>
                <?php elseif (!empty($dictionaries)): ?>
                <div class="select is-fullwidth">
                    <select name="dict_id">
                        <option value="">-- Create new dictionary --</option>
                        <?php foreach ($dictionaries as $dict): ?>
                        <option value="<?php echo $dict->id(); ?>">
                            <?php echo htmlspecialchars($dict->name(), ENT_QUOTES); ?>
                            (<?php echo number_format($dict->entryCount()); ?> entries)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                <p class="help">A new dictionary will be created.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Dictionary Name (for new dictionaries) -->
        <?php if (!$dictionary): ?>
        <div class="field">
            <label class="label">Dictionary Name</label>
            <div class="control">
                <input type="text" name="dict_name" class="input"
                       placeholder="e.g., JMdict Japanese-English">
            </div>
            <p class="help">Leave empty to auto-generate from filename.</p>
        </div>
        <?php endif; ?>

        <!-- File Format -->
        <div class="field">
            <label class="label">File Format</label>
            <div class="control">
                <div class="select is-fullwidth">
                    <select name="format" x-model="format" @change="resetOptions()">
                        <option value="csv">CSV / TSV (Comma/Tab separated)</option>
                        <option value="json">JSON</option>
                        <option value="stardict">StarDict (.ifo file)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- File Upload -->
        <div class="field">
            <label class="label">Dictionary File</label>
            <div class="file has-name is-fullwidth">
                <label class="file-label">
                    <input class="file-input" type="file" name="file" required
                           @change="fileSelected($event)"
                           :accept="acceptTypes[format]">
                    <span class="file-cta">
                        <span class="file-icon">
                            <?php echo IconHelper::render('upload', ['alt' => 'Upload']); ?>
                        </span>
                        <span class="file-label">Choose file...</span>
                    </span>
                    <span class="file-name" x-text="fileName || 'No file selected'"></span>
                </label>
            </div>
            <p class="help" x-show="format === 'csv'">CSV files with term and definition columns.</p>
            <p class="help" x-show="format === 'json'">JSON array of objects with term/definition fields.</p>
            <p class="help" x-show="format === 'stardict'">Select the .ifo file. The .idx and .dict files must be in the same directory.</p>
        </div>

        <!-- CSV Options -->
        <div x-show="format === 'csv'" class="box">
            <h5 class="title is-6">CSV Options</h5>

            <div class="field">
                <label class="label">Delimiter</label>
                <div class="control">
                    <div class="select">
                        <select name="delimiter">
                            <option value=",">Comma (,)</option>
                            <option value="tab">Tab</option>
                            <option value=";">Semicolon (;)</option>
                            <option value="|">Pipe (|)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="field">
                <label class="checkbox">
                    <input type="checkbox" name="has_header" value="yes" checked>
                    First row is header
                </label>
            </div>

            <h6 class="title is-6 mt-4">Column Mapping</h6>
            <div class="columns">
                <div class="column is-3">
                    <div class="field">
                        <label class="label">Term Column</label>
                        <div class="control">
                            <input type="number" name="term_column" class="input" value="0" min="0">
                        </div>
                        <p class="help">0 = first column</p>
                    </div>
                </div>
                <div class="column is-3">
                    <div class="field">
                        <label class="label">Definition Column</label>
                        <div class="control">
                            <input type="number" name="definition_column" class="input" value="1" min="0">
                        </div>
                    </div>
                </div>
                <div class="column is-3">
                    <div class="field">
                        <label class="label">Reading Column</label>
                        <div class="control">
                            <input type="number" name="reading_column" class="input" placeholder="Optional">
                        </div>
                    </div>
                </div>
                <div class="column is-3">
                    <div class="field">
                        <label class="label">POS Column</label>
                        <div class="control">
                            <input type="number" name="pos_column" class="input" placeholder="Optional">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- JSON Options -->
        <div x-show="format === 'json'" class="box">
            <h5 class="title is-6">JSON Field Mapping</h5>
            <p class="mb-3">Leave empty for auto-detection (looks for common field names like term, word, definition, meaning, etc.)</p>

            <div class="columns">
                <div class="column is-3">
                    <div class="field">
                        <label class="label">Term Field</label>
                        <div class="control">
                            <input type="text" name="term_field" class="input" placeholder="e.g., word">
                        </div>
                    </div>
                </div>
                <div class="column is-3">
                    <div class="field">
                        <label class="label">Definition Field</label>
                        <div class="control">
                            <input type="text" name="definition_field" class="input" placeholder="e.g., meaning">
                        </div>
                    </div>
                </div>
                <div class="column is-3">
                    <div class="field">
                        <label class="label">Reading Field</label>
                        <div class="control">
                            <input type="text" name="reading_field" class="input" placeholder="e.g., furigana">
                        </div>
                    </div>
                </div>
                <div class="column is-3">
                    <div class="field">
                        <label class="label">POS Field</label>
                        <div class="control">
                            <input type="text" name="pos_field" class="input" placeholder="e.g., pos">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- StarDict Info -->
        <div x-show="format === 'stardict'" class="box">
            <h5 class="title is-6">StarDict Format</h5>
            <p>StarDict dictionaries consist of three files:</p>
            <ul class="mt-2 mb-2">
                <li><strong>.ifo</strong> - Dictionary information (select this file)</li>
                <li><strong>.idx</strong> - Word index</li>
                <li><strong>.dict</strong> or <strong>.dict.dz</strong> - Definitions</li>
            </ul>
            <p class="has-text-info">All three files must be in the same directory.</p>
        </div>

        <!-- Submit -->
        <div class="field mt-5">
            <div class="control">
                <button type="submit" class="button is-primary is-medium"
                        :disabled="submitting || !fileName"
                        :class="{'is-loading': submitting}">
                    <?php echo IconHelper::render('upload', ['alt' => 'Import']); ?>
                    Import Dictionary
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function dictionaryImport() {
    return {
        format: 'csv',
        fileName: '',
        submitting: false,
        acceptTypes: {
            'csv': '.csv,.tsv,.txt',
            'json': '.json',
            'stardict': '.ifo'
        },

        fileSelected(event) {
            const file = event.target.files[0];
            this.fileName = file ? file.name : '';
        },

        resetOptions() {
            // Reset options when format changes
        }
    };
}
</script>
