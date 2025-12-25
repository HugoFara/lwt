<?php declare(strict_types=1);
/**
 * Database Operations View
 *
 * Modern Bulma + Alpine.js version of the backup/restore page.
 *
 * Variables expected:
 * - $prefinfo: string HTML prefix info
 * - $message: string Message to display (if any)
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

namespace Lwt\Views\Admin;

use Lwt\Core\Globals;
use Lwt\Shared\UI\Helpers\IconHelper;

$escapedDbName = htmlspecialchars(Globals::getDatabaseName(), ENT_QUOTES, 'UTF-8');
$escapedIniFile = htmlspecialchars(php_ini_loaded_file() ?: '', ENT_QUOTES, 'UTF-8');
$postMaxSize = ini_get('post_max_size');
$uploadMaxFilesize = ini_get('upload_max_filesize');
?>
<div class="container" x-data="backupManager()">
    <!-- Backup Section -->
    <section class="box mb-4" x-data="{ open: false }">
        <header class="collapsible-header" @click="open = !open">
            <h2 class="title is-4 mb-0">
                <span class="icon-text">
                    <span class="icon has-text-info">
                        <?php echo IconHelper::render('download', ['class' => 'icon']); ?>
                    </span>
                    <span>Backup</span>
                </span>
            </h2>
            <span class="icon collapse-icon" :class="{ 'is-rotated': open }">
                <?php echo IconHelper::render('chevron-down'); ?>
            </span>
        </header>

        <div class="collapsible-content" x-show="open" x-collapse>
            <div class="content mt-4">
                <p>
                    The database <strong><?php echo $escapedDbName; ?></strong>
                    <?php echo $prefinfo; ?> will be exported to a gzipped SQL file.
                </p>
            </div>

            <div class="notification is-info is-light">
                <div class="columns is-vcentered">
                    <div class="column is-narrow">
                        <span class="icon is-medium has-text-info">
                            <?php echo IconHelper::render('circle-help', ['width' => 24, 'height' => 24]); ?>
                        </span>
                    </div>
                    <div class="column">
                        <p class="has-text-weight-semibold mb-1">Keep your backup safe</p>
                        <ul class="is-size-7 mt-0">
                            <li>You can restore this backup using the Restore function below</li>
                            <li>The <strong>Official LWT Backup</strong> doesn't include newsfeeds, saved text positions, or settings</li>
                            <li>Large backup files may not be restorable (see upload limits below)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <form action="/admin/backup" method="post" class="mt-4">
                <div class="field is-grouped is-grouped-right">
                    <div class="control">
                        <button type="submit" name="orig_backup" class="button is-info is-outlined">
                            <?php echo IconHelper::render('download'); ?>
                            <span>Download Official LWT Backup</span>
                        </button>
                    </div>
                    <div class="control">
                        <button type="submit" name="backup" class="button is-info">
                            <?php echo IconHelper::render('download'); ?>
                            <span>Download Full LWT Backup</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- Restore Section -->
    <section class="box mb-4" x-data="{ open: false }">
        <header class="collapsible-header" @click="open = !open">
            <h2 class="title is-4 mb-0">
                <span class="icon-text">
                    <span class="icon has-text-warning">
                        <?php echo IconHelper::render('upload', ['class' => 'icon']); ?>
                    </span>
                    <span>Restore</span>
                </span>
            </h2>
            <span class="icon collapse-icon" :class="{ 'is-rotated': open }">
                <?php echo IconHelper::render('chevron-down'); ?>
            </span>
        </header>

        <div class="collapsible-content" x-show="open" x-collapse>
            <div class="content mt-4">
                <p>
                    The database <strong><?php echo $escapedDbName; ?></strong>
                    <?php echo $prefinfo; ?> will be <strong>replaced</strong> by the data in the specified backup file
                    (gzipped or normal SQL file).
                </p>
            </div>

            <div class="notification is-warning is-light">
                <div class="columns is-vcentered">
                    <div class="column is-narrow">
                        <span class="icon is-medium has-text-warning">
                            <?php echo IconHelper::render('triangle-alert', ['width' => 24, 'height' => 24]); ?>
                        </span>
                    </div>
                    <div class="column">
                        <p class="has-text-weight-semibold mb-1">Upload Limits</p>
                        <p class="is-size-7">
                            Large backup files may fail to restore due to PHP limits:<br>
                            <code>post_max_size = <?php echo $postMaxSize; ?></code> /
                            <code>upload_max_filesize = <?php echo $uploadMaxFilesize; ?></code><br>
                            If needed, increase these values in <code><?php echo $escapedIniFile; ?></code> and restart your server.
                        </p>
                    </div>
                </div>
            </div>

            <form action="/admin/backup" method="post" enctype="multipart/form-data"
                  @submit="restoring = true"
                  x-show="!restoring"
                  data-confirm-submit="Are you sure? This will REPLACE all existing data!">
                <input type="hidden" name="restore" value="1">
                <div class="field">
                    <label class="label">Backup File</label>
                    <div class="file has-name is-fullwidth">
                        <label class="file-label">
                            <input class="file-input" type="file" name="thefile"
                                   @change="fileName = $event.target.files[0]?.name || ''"
                                   accept=".sql,.gz,.sql.gz">
                            <span class="file-cta">
                                <span class="file-icon">
                                    <?php echo IconHelper::render('upload'); ?>
                                </span>
                                <span class="file-label">Choose a file…</span>
                            </span>
                            <span class="file-name" x-text="fileName || 'No file selected'"></span>
                        </label>
                    </div>
                </div>

                <div class="field is-grouped is-grouped-right mt-4">
                    <div class="control">
                        <button type="submit" class="button is-warning"
                                :disabled="!fileName">
                            <span class="icon">
                                <?php echo IconHelper::render('triangle-alert'); ?>
                            </span>
                            <span>Restore from Backup</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Restoring state -->
            <div x-show="restoring" x-cloak class="has-text-centered py-5">
                <p class="is-size-5 mb-4">
                    <span class="icon is-medium has-text-info">
                        <span class="loader"></span>
                    </span>
                    <span class="ml-2">Restoring database...</span>
                </p>
                <p class="has-text-grey is-size-7">This may take a while. Please don't close this page.</p>
            </div>
        </div>
    </section>

    <!-- Demo Database Section -->
    <section class="box mb-4" x-data="{ open: false }">
        <header class="collapsible-header" @click="open = !open">
            <h2 class="title is-4 mb-0">
                <span class="icon-text">
                    <span class="icon has-text-success">
                        <?php echo IconHelper::render('package', ['class' => 'icon']); ?>
                    </span>
                    <span>Install Demo Database</span>
                </span>
            </h2>
            <span class="icon collapse-icon" :class="{ 'is-rotated': open }">
                <?php echo IconHelper::render('chevron-down'); ?>
            </span>
        </header>

        <div class="collapsible-content" x-show="open" x-collapse>
            <div class="content mt-4">
                <p>
                    The database <strong><?php echo $escapedDbName; ?></strong>
                    <?php echo $prefinfo; ?> will be <strong>replaced</strong> by the LWT demo database.
                </p>
                <p class="is-size-7 has-text-grey">
                    The demo includes sample texts and vocabulary in multiple languages to help you explore LWT's features.
                </p>
            </div>

            <div class="field is-grouped is-grouped-right">
                <div class="control">
                    <a href="/admin/install-demo" class="button is-success is-outlined">
                        <?php echo IconHelper::render('download'); ?>
                        <span>Install Demo Database</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Empty Database Section -->
    <section class="box mb-4" x-data="{ open: false }">
        <header class="collapsible-header" @click="open = !open">
            <h2 class="title is-4 mb-0">
                <span class="icon-text">
                    <span class="icon has-text-danger">
                        <?php echo IconHelper::render('trash-2', ['class' => 'icon']); ?>
                    </span>
                    <span>Empty Database</span>
                </span>
            </h2>
            <span class="icon collapse-icon" :class="{ 'is-rotated': open }">
                <?php echo IconHelper::render('chevron-down'); ?>
            </span>
        </header>

        <div class="collapsible-content" x-show="open" x-collapse>
            <div class="content mt-4">
                <p>
                    Delete the contents of all tables (except Settings) in database
                    <strong><?php echo $escapedDbName; ?></strong> <?php echo $prefinfo; ?>.
                </p>
            </div>

            <div class="notification is-danger is-light">
                <div class="columns is-vcentered">
                    <div class="column is-narrow">
                        <span class="icon is-medium has-text-danger">
                            <?php echo IconHelper::render('triangle-alert', ['width' => 24, 'height' => 24]); ?>
                        </span>
                    </div>
                    <div class="column">
                        <p class="has-text-weight-semibold">Warning: This action cannot be undone!</p>
                        <p class="is-size-7">All your texts, vocabulary, and learning progress will be permanently deleted.</p>
                    </div>
                </div>
            </div>

            <form action="/admin/backup" method="post"
                  @submit="emptying = true"
                  x-show="!emptying"
                  data-confirm-submit="Are you sure? This will DELETE all your data!">
                <input type="hidden" name="empty" value="1">
                <div class="field" x-show="!emptying">
                    <label class="checkbox">
                        <input type="checkbox" x-model="confirmEmpty">
                        <span class="has-text-weight-medium">
                            I understand that this will permanently delete all my data
                        </span>
                    </label>
                </div>

                <div class="field is-grouped is-grouped-right mt-4">
                    <div class="control">
                        <button type="submit" class="button is-danger"
                                :disabled="!confirmEmpty">
                            <span class="icon">
                                <?php echo IconHelper::render('trash-2'); ?>
                            </span>
                            <span>Empty Database</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Emptying state -->
            <div x-show="emptying" x-cloak class="has-text-centered py-5">
                <p class="is-size-5 mb-4">
                    <span class="icon is-medium has-text-danger">
                        <span class="loader"></span>
                    </span>
                    <span class="ml-2">Emptying database...</span>
                </p>
            </div>
        </div>
    </section>

    <!-- Back Button -->
    <div class="field">
        <div class="control">
            <a href="/" class="button is-light">
                <?php echo IconHelper::render('arrow-left'); ?>
                <span>Back to Main Menu</span>
            </a>
        </div>
    </div>
</div>

<style>
/* Collapsible section styles */
.collapsible-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    user-select: none;
}

.collapsible-header:hover {
    opacity: 0.8;
}

.collapse-icon {
    transition: transform 0.2s ease;
}

.collapse-icon.is-rotated {
    transform: rotate(180deg);
}

/* Simple CSS loader animation */
.loader {
    display: inline-block;
    width: 1.5em;
    height: 1.5em;
    border: 3px solid #dbdbdb;
    border-radius: 50%;
    border-top-color: #3273dc;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Alpine.js cloak */
[x-cloak] {
    display: none !important;
}
</style>
<!-- backupManager() is now registered via src/frontend/js/admin/backup_manager.ts -->
