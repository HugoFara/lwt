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
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Views\Book;

use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;
use Lwt\Shared\UI\Helpers\FormHelper;

/**
 * @var bool $showFromTextNotice
 */

$actions = [
    ['url' => '/books', 'label' => __('book.my_books'), 'icon' => 'library'],
    ['url' => '/texts/new', 'label' => __('book.new_text'), 'icon' => 'circle-plus'],
    ['url' => '/texts', 'label' => __('book.all_texts'), 'icon' => 'book-open'],
];

$zipMissing = !extension_loaded('zip');
$formStyle = $zipMissing ? ' style="pointer-events: none; opacity: 0.6;"' : '';
$submitDisabled = $zipMissing ? ' disabled title="ZIP extension required"' : '';
?>

<h2 class="title is-4">
    <?php echo __('book.import_epub'); ?>
    <a target="_blank" href="docs/info.html#howtotext" class="ml-2">
        <?php
        echo IconHelper::render('help-circle', [
            'title' => __('common.help'),
            'alt' => __('common.help'),
        ]);
        ?>
    </a>
</h2>

<?php echo PageLayoutHelper::buildActionCard($actions); ?>

<div class="notification is-warning is-light">
    <p class="mb-2">
        <?php echo IconHelper::render('alert-triangle', ['alt' => '', 'class' => 'mr-2']); ?>
        <strong><?php echo __('book.deprecated_page_title'); ?></strong>
    </p>
    <p class="mb-3"><?php echo __('book.deprecated_page_body'); ?></p>
    <a href="/texts/new" class="button is-primary is-small">
        <?php echo IconHelper::render('circle-plus', ['alt' => '']); ?>
        <span class="ml-2"><?php echo __('book.go_to_new_text'); ?></span>
    </a>
</div>

<?php if ($showFromTextNotice) : ?>
<div class="notification is-info is-light">
    <p>
        <?php echo IconHelper::render('info', ['alt' => 'Info', 'class' => 'mr-2']); ?>
        <?php echo __('book.epub_intro_notice'); ?>
    </p>
</div>
<?php endif; ?>

<?php if ($zipMissing) : ?>
<div class="notification is-danger">
    <p>
        <strong><?php
            echo IconHelper::render('alert-circle', ['alt' => __('common.error'), 'class' => 'mr-2']);
        ?>
        <?php echo __('book.zip_required_title'); ?></strong>
    </p>
    <p>
        <?php echo __('book.zip_required_body'); ?>
    </p>
    <details class="mt-2">
        <summary><?php echo __('book.install_instructions'); ?></summary>
        <div class="content mt-2">
            <p><strong><?php echo __('book.install_ubuntu'); ?></strong></p>
            <pre><code>sudo apt-get install php-zip
sudo systemctl restart apache2  # or nginx</code></pre>

            <p><strong><?php echo __('book.install_centos'); ?></strong></p>
            <pre><code>sudo dnf install php-zip  # or yum install php-zip
sudo systemctl restart httpd  # or nginx</code></pre>

            <p><strong><?php echo __('book.install_docker'); ?></strong></p>
            <p><?php echo __('book.install_docker_help'); ?></p>
        </div>
    </details>
</div>
<?php endif; ?>

<form enctype="multipart/form-data" class="validate" action="/book/import" method="post"<?php echo $formStyle; ?>>
    <?php echo FormHelper::csrfField(); ?>

    <div class="box">
        <!-- Language -->
        <div class="field">
            <label class="label" for="LgID">
                <?php echo __('common.language'); ?>
                <span class="icon has-text-danger is-small"
                      title="<?php echo htmlspecialchars(__('common.required_field'), ENT_QUOTES); ?>">
                    <?php echo IconHelper::render('asterisk', ['alt' => __('common.required_field')]); ?>
                </span>
            </label>
            <div class="control">
                <div class="select is-fullwidth">
                    <select name="LgID" id="LgID" class="notempty setfocus" required>
                        <?php echo $languagesOption; ?>
                    </select>
                </div>
            </div>
            <p class="help"><?php echo __('book.select_book_language'); ?></p>
        </div>

        <!-- EPUB File -->
        <div class="field">
            <label class="label">
                <?php echo __('book.epub_file'); ?>
                <span class="icon has-text-danger is-small"
                      title="<?php echo htmlspecialchars(__('common.required_field'), ENT_QUOTES); ?>">
                    <?php echo IconHelper::render('asterisk', ['alt' => __('common.required_field')]); ?>
                </span>
            </label>
            <?php $noFile = htmlspecialchars(__('book.no_file_selected'), ENT_QUOTES); ?>
            <div class="file has-name is-fullwidth">
                <label class="file-label">
                    <input class="file-input"
                           type="file"
                           name="thefile"
                           accept=".epub"
                           required
                           @change="document.getElementById('filename').textContent =
                               $el.files[0]?.name || '<?php echo $noFile; ?>'" />
                    <span class="file-cta">
                        <span class="file-icon">
                            <?php echo IconHelper::render('upload', ['alt' => __('common.upload')]); ?>
                        </span>
                        <span class="file-label"><?php echo __('book.choose_epub_file'); ?></span>
                    </span>
                    <span class="file-name" id="filename"><?php echo __('book.no_file_selected'); ?></span>
                </label>
            </div>
            <p class="help">
                <?php echo __('book.upload_limits'); ?>
                <strong>post_max_size</strong>: <?php echo ini_get('post_max_size'); ?>,
                <strong>upload_max_filesize</strong>: <?php echo ini_get('upload_max_filesize'); ?>
            </p>
        </div>

        <!-- Override Title (optional) -->
        <div class="field">
            <label class="label" for="TxTitle"><?php echo __('book.title_override'); ?></label>
            <div class="control">
                <input type="text"
                       class="input"
                       name="TxTitle"
                       id="TxTitle"
                       maxlength="200"
                       placeholder="<?php
                           echo htmlspecialchars(__('book.title_override_placeholder'), ENT_QUOTES);
                        ?>"
                       title="<?php
                           echo htmlspecialchars(__('book.title_override_help'), ENT_QUOTES);
                        ?>" />
            </div>
            <p class="help"><?php echo __('book.title_override_help'); ?></p>
        </div>

        <!-- Tags (optional) -->
        <div class="field">
            <label class="label"><?php echo __('common.tags'); ?></label>
            <div class="control">
                <?php echo \Lwt\Modules\Tags\Application\TagsFacade::getTextTagsHtml(0); ?>
            </div>
            <p class="help"><?php echo __('book.tags_help'); ?></p>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="field is-grouped is-grouped-right">
        <div class="control">
            <a href="/books" class="button is-light"><?php echo __('common.cancel'); ?></a>
        </div>
        <div class="control">
            <button type="submit" name="op" value="Import"
                    class="button is-primary"<?php echo $submitDisabled; ?>>
                <?php echo IconHelper::render('upload', ['alt' => __('common.import')]); ?>
                <span class="ml-2"><?php echo __('book.import_epub'); ?></span>
            </button>
        </div>
    </div>
</form>
