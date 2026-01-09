<?php declare(strict_types=1);
/**
 * Install Demo View
 *
 * Modern Bulma + Alpine.js version of the demo installation page.
 *
 * Variables expected:
 * - $prefinfo: string HTML prefix info
 * - $langcnt: int Count of existing languages
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
use Lwt\Shared\Infrastructure\Http\UrlUtilities;
use Lwt\Shared\UI\Helpers\IconHelper;

$prefinfo = (string)($prefinfo ?? '');
$langcnt = (int)($langcnt ?? 0);
$base = UrlUtilities::getBasePath();

?>
<div class="container" x-data="{ confirmed: false, installing: false }">
    <div class="box">
        <h2 class="title is-4">
            <span class="icon-text">
                <span class="icon has-text-info">
                    <?php echo IconHelper::render('database', ['class' => 'icon']); ?>
                </span>
                <span>Install Demo Database</span>
            </span>
        </h2>

        <div class="content">
            <p>
                The demo database includes sample texts and vocabulary in multiple languages
                to help you explore LWT's features.
            </p>
        </div>

        <!-- Warning notification -->
        <div class="notification is-warning is-light">
            <div class="columns is-vcentered">
                <div class="column is-narrow">
                    <span class="icon is-large has-text-warning">
                        <?php echo IconHelper::render('triangle-alert', ['width' => 32, 'height' => 32]); ?>
                    </span>
                </div>
                <div class="column">
                    <p class="has-text-weight-semibold">Warning: This action will replace your current data</p>
                    <p class="is-size-7">
                        The database <strong><?php echo htmlspecialchars(Globals::getDatabaseName(), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <?php echo $prefinfo; ?> will be <strong>replaced</strong> by the LWT demo database.
                        <?php if ($langcnt > 0): ?>
                        <br>Your existing <?php echo $langcnt; ?> language(s) and all associated data will be <strong>overwritten</strong>.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Confirmation checkbox -->
        <div class="field" x-show="!installing">
            <label class="checkbox">
                <input type="checkbox" x-model="confirmed">
                <span class="has-text-weight-medium">
                    I understand that this will permanently replace my current database
                </span>
            </label>
        </div>

        <!-- Install form -->
        <form action="<?php echo $base; ?>/admin/install-demo" method="post"
              @submit="installing = true"
              x-show="!installing">
            <?php echo \Lwt\Shared\UI\Helpers\FormHelper::csrfField(); ?>
            <div class="field is-grouped mt-5">
                <div class="control">
                    <button type="submit"
                            name="install"
                            class="button is-danger"
                            :disabled="!confirmed"
                            :class="{ 'is-loading': installing }">
                        <?php echo IconHelper::render('download'); ?>
                        <span>Install Demo Database</span>
                    </button>
                </div>
                <div class="control">
                    <a href="<?php echo $base; ?>/" class="button is-light">
                        <?php echo IconHelper::render('arrow-left'); ?>
                        <span>Back to Main Menu</span>
                    </a>
                </div>
            </div>
        </form>

        <!-- Installing state -->
        <div x-show="installing" x-cloak class="has-text-centered py-5">
            <p class="is-size-5 mb-4">
                <span class="icon is-medium has-text-info">
                    <span class="loader"></span>
                </span>
                <span class="ml-2">Installing demo database...</span>
            </p>
            <p class="has-text-grey is-size-7">This may take a minute. Please don't close this page.</p>
        </div>
    </div>

    <!-- What's included info -->
    <div class="box" x-show="!installing">
        <h3 class="title is-5">
            <span class="icon-text">
                <span class="icon has-text-success">
                    <?php echo IconHelper::render('package', ['class' => 'icon']); ?>
                </span>
                <span>What's Included</span>
            </span>
        </h3>
        <div class="content">
            <div class="columns">
                <div class="column">
                    <ul>
                        <li><strong>Sample texts</strong> in multiple languages</li>
                        <li><strong>Vocabulary</strong> with translations and notes</li>
                        <li><strong>Language configurations</strong> ready to use</li>
                    </ul>
                </div>
                <div class="column">
                    <ul>
                        <li><strong>Tags</strong> for organizing content</li>
                        <li><strong>Example settings</strong> optimized for learning</li>
                        <li><strong>Audio files</strong> for pronunciation practice</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
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
</style>
