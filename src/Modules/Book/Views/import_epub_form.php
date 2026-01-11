<?php

/**
 * EPUB Import Form View
 *
 * Variables expected:
 * - $languagesOption: string - HTML options for language select
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Book\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Views\Book;

use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;
use Lwt\Shared\UI\Helpers\FormHelper;

$actions = [
    ['url' => '/books', 'label' => 'My Books', 'icon' => 'library'],
    ['url' => '/texts?new=1', 'label' => 'New Text', 'icon' => 'circle-plus'],
    ['url' => '/texts', 'label' => 'All Texts', 'icon' => 'book-open'],
];

?>

<h2 class="title is-4">
    Import EPUB
    <a target="_blank" href="docs/info.html#howtotext" class="ml-2">
        <?php echo IconHelper::render('help-circle', ['title' => 'Help', 'alt' => 'Help']); ?>
    </a>
</h2>

<?php echo PageLayoutHelper::buildActionCard($actions); ?>

<?php if (isset($_GET['from']) && $_GET['from'] === 'text') : ?>
<div class="notification is-info is-light">
    <p>
        <?php echo IconHelper::render('info', ['alt' => 'Info', 'class' => 'mr-2']); ?>
        EPUB files are imported as books with chapters. Please select your EPUB file below.
    </p>
</div>
<?php endif; ?>

<form enctype="multipart/form-data" class="validate" action="/book/import" method="post">
    <?php echo FormHelper::csrfField(); ?>

    <div class="box">
        <!-- Language -->
        <div class="field">
            <label class="label" for="LgID">
                Language
                <span class="icon has-text-danger is-small" title="Required field">
                    <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                </span>
            </label>
            <div class="control">
                <div class="select is-fullwidth">
                    <select name="LgID" id="LgID" class="notempty setfocus" required>
                        <?php echo $languagesOption; ?>
                    </select>
                </div>
            </div>
            <p class="help">Select the language of the book.</p>
        </div>

        <!-- EPUB File -->
        <div class="field">
            <label class="label">
                EPUB File
                <span class="icon has-text-danger is-small" title="Required field">
                    <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                </span>
            </label>
            <div class="file has-name is-fullwidth">
                <label class="file-label">
                    <input class="file-input"
                           type="file"
                           name="thefile"
                           accept=".epub"
                           required
                           @change="document.getElementById('filename').textContent =
                               $el.files[0]?.name || 'No file selected'" />
                    <span class="file-cta">
                        <span class="file-icon">
                            <?php echo IconHelper::render('upload', ['alt' => 'Upload']); ?>
                        </span>
                        <span class="file-label">Choose EPUB file...</span>
                    </span>
                    <span class="file-name" id="filename">No file selected</span>
                </label>
            </div>
            <p class="help">
                Upload limits:
                <strong>post_max_size</strong>: <?php echo ini_get('post_max_size'); ?>,
                <strong>upload_max_filesize</strong>: <?php echo ini_get('upload_max_filesize'); ?>
            </p>
        </div>

        <!-- Override Title (optional) -->
        <div class="field">
            <label class="label" for="TxTitle">Title Override</label>
            <div class="control">
                <input type="text"
                       class="input"
                       name="TxTitle"
                       id="TxTitle"
                       maxlength="200"
                       placeholder="Leave empty to use title from EPUB"
                       title="Optional: Override the book title from the EPUB" />
            </div>
            <p class="help">Optional. Leave empty to use the title from the EPUB file.</p>
        </div>

        <!-- Tags (optional) -->
        <div class="field">
            <label class="label">Tags</label>
            <div class="control">
                <?php echo \Lwt\Modules\Tags\Application\TagsFacade::getTextTagsHtml(0); ?>
            </div>
            <p class="help">Optional. Tags will be applied to all chapters.</p>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="field is-grouped is-grouped-right">
        <div class="control">
            <a href="/books" class="button is-light">Cancel</a>
        </div>
        <div class="control">
            <button type="submit" name="op" value="Import" class="button is-primary">
                <?php echo IconHelper::render('upload', ['alt' => 'Import']); ?>
                <span class="ml-2">Import EPUB</span>
            </button>
        </div>
    </div>
</form>
