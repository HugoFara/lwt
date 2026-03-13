<?php

/**
 * Admin Settings Form View
 *
 * Server-wide admin settings: appearance, feed limits, multi-user.
 * User-scoped preferences (reading, review, TTS, pagination) have moved to
 * the user preferences page at /profile/preferences.
 *
 * Variables expected:
 * - $settings: array of current settings values
 * - $themes: array of available themes (from ThemeService)
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Views\Admin;

use Lwt\Shared\UI\Helpers\SelectOptionsBuilder;
use Lwt\Shared\UI\Helpers\IconHelper;

/**
 * @var array<string, string> $settings Current settings values
 * @psalm-suppress MixedArgument
 */
$settings = array_map(
    /**
     * @param mixed $v
     * @return string
     */
    static fn($v): string => (string)($v ?? ''),
    is_array($settings ?? null) ? $settings : []
);

/**
 * @var array<int, array{
 *     name: string,
 *     path: string,
 *     description?: string,
 *     mode?: string,
 *     highlighting?: string,
 *     wordBreaking?: string
 * }> $themes Available themes from ThemeService
 */
$themes = is_array($themes ?? null) ? $themes : [];

?>

<!-- Link to user preferences -->
<div class="notification is-info is-light mb-5">
    <span class="icon-text">
        <?php echo IconHelper::render('settings', ['alt' => 'Preferences']); ?>
        <span class="ml-2">
            Looking for reading, review, TTS, or pagination settings?
            <a href="/profile/preferences"><strong>Go to Preferences</strong></a>
        </span>
    </span>
</div>

<form class="validate" action="/admin/settings" method="post" data-lwt-settings-form>
    <?php echo \Lwt\Shared\UI\Helpers\FormHelper::csrfField(); ?>

    <!-- Appearance Section -->
    <div class="card settings-section mb-4" x-data="{ open: false }">
        <header class="card-header is-clickable" @click="open = !open">
            <p class="card-header-title">
                <?php echo IconHelper::render('palette', ['alt' => 'Appearance']); ?>
                <span class="ml-2">Appearance</span>
            </p>
            <button type="button" class="card-header-icon" aria-label="toggle section">
                <span class="icon">
                    <i :class="open ? 'rotate-180' : ''" class="transition-transform" data-lucide="chevron-down"></i>
                </span>
            </button>
        </header>
        <div class="card-content" x-show="open" x-transition>
            <?php $currentTheme = htmlspecialchars($settings['set-theme-dir'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
            <div class="field is-horizontal"
                 x-data="{
                     currentTheme: '<?php echo $currentTheme; ?>',
                     description: '',
                     mode: '',
                     highlighting: '',
                     wordBreaking: '',
                     updateInfo() {
                         const select = document.getElementById('set-theme-dir');
                         const option = select.options[select.selectedIndex];
                         this.description = option.dataset.description || '';
                         this.mode = option.dataset.mode || 'light';
                         this.highlighting = option.dataset.highlighting || '';
                         this.wordBreaking = option.dataset.wordBreaking || '';
                     },
                     previewTheme() {
                         const themePath = document.getElementById('set-theme-dir').value;
                         const styleId = 'theme-preview-styles';
                         let styleEl = document.getElementById(styleId);
                         if (!styleEl) {
                             styleEl = document.createElement('link');
                             styleEl.id = styleId;
                             styleEl.rel = 'stylesheet';
                             document.head.appendChild(styleEl);
                         }
                         styleEl.href = '/' + themePath + 'styles.css?preview=' + Date.now();
                     }
                 }"
                 x-init="updateInfo()">
                <div class="field-label is-normal">
                    <label class="label" for="set-theme-dir">Theme</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="field has-addons">
                            <div class="control is-expanded">
                                <div class="select is-fullwidth">
                                    <select name="set-theme-dir" id="set-theme-dir" class="notempty" required
                                            x-model="currentTheme"
                                            @change="updateInfo(); previewTheme()">
                                        <?php
                                        echo SelectOptionsBuilder::forThemes(
                                            $themes,
                                            $settings['set-theme-dir']
                                        );
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="control">
                                <span class="icon has-text-danger" title="Field must not be empty">
                                    <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="box mt-3" x-show="description || highlighting || wordBreaking" x-transition>
                            <p class="mb-2" x-show="description">
                                <strong>Description:</strong> <span x-text="description"></span>
                            </p>
                            <div class="tags">
                                <span class="tag" :class="mode === 'dark' ? 'is-dark' : 'is-light'" x-show="mode">
                                    <i data-lucide="sun" style="width:14px;height:14px;margin-right:4px"></i>
                                    <span x-text="mode === 'dark' ? 'Dark Mode' : 'Light Mode'"></span>
                                </span>
                                <span class="tag is-info is-light" x-show="highlighting">
                                    <i data-lucide="palette" style="width:14px;height:14px;margin-right:4px"></i>
                                    <span x-text="highlighting"></span>
                                </span>
                                <span class="tag is-primary is-light" x-show="wordBreaking">
                                    <i data-lucide="wrap-text" style="width:14px;height:14px;margin-right:4px"></i>
                                    <span x-text="wordBreaking"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Newsfeeds Section -->
    <div class="card settings-section mb-4" x-data="{ open: false }">
        <header class="card-header is-clickable" @click="open = !open">
            <p class="card-header-title">
                <?php echo IconHelper::render('rss', ['alt' => 'Newsfeeds']); ?>
                <span class="ml-2">Newsfeeds</span>
            </p>
            <button type="button" class="card-header-icon" aria-label="toggle section">
                <span class="icon">
                    <i :class="open ? 'rotate-180' : ''" class="transition-transform" data-lucide="chevron-down"></i>
                </span>
            </button>
        </header>
        <div class="card-content" x-show="open" x-transition>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" for="set-max-articles-with-text">
                        Max Articles <span class="has-text-weight-normal">(with cache)</span>
                    </label>
                </div>
                <div class="field-body">
                    <div class="field has-addons">
                        <div class="control">
                            <input class="input notempty posintnumber"
                                   type="number"
                                   min="0"
                                   id="set-max-articles-with-text"
                                   name="set-max-articles-with-text"
                                   data_info="Max Articles per Feed with cached text"
                                   value="<?php
                                       echo htmlspecialchars(
                                           $settings['set-max-articles-with-text'] ?? '',
                                           ENT_QUOTES,
                                           'UTF-8'
                                       );
                                        ?>"
                                   maxlength="4"
                                   style="width: 100px;"
                                   required />
                        </div>
                        <div class="control">
                            <span class="icon has-text-danger" title="Field must not be empty">
                                <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                            </span>
                        </div>
                    </div>
                    <p class="help">Maximum articles per feed with cached text</p>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" for="set-max-articles-without-text">
                        Max Articles <span class="has-text-weight-normal">(no cache)</span>
                    </label>
                </div>
                <div class="field-body">
                    <div class="field has-addons">
                        <div class="control">
                            <input class="input notempty posintnumber"
                                   type="number"
                                   min="0"
                                   id="set-max-articles-without-text"
                                   name="set-max-articles-without-text"
                                   data_info="Max Articles per Feed without cached text"
                                   value="<?php
                                       echo htmlspecialchars(
                                           $settings['set-max-articles-without-text'] ?? '',
                                           ENT_QUOTES,
                                           'UTF-8'
                                       );
                                        ?>"
                                   maxlength="4"
                                   style="width: 100px;"
                                   required />
                        </div>
                        <div class="control">
                            <span class="icon has-text-danger" title="Field must not be empty">
                                <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                            </span>
                        </div>
                    </div>
                    <p class="help">Maximum articles per feed without cached text</p>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" for="set-max-texts-per-feed">Max Texts per Feed</label>
                </div>
                <div class="field-body">
                    <div class="field has-addons">
                        <div class="control">
                            <input class="input notempty posintnumber"
                                   type="number"
                                   min="0"
                                   id="set-max-texts-per-feed"
                                   name="set-max-texts-per-feed"
                                   data_info="Max Texts per Feed"
                                   value="<?php
                                       echo htmlspecialchars(
                                           $settings['set-max-texts-per-feed'] ?? '',
                                           ENT_QUOTES,
                                           'UTF-8'
                                       );
                                        ?>"
                                   maxlength="4"
                                   style="width: 100px;"
                                   required />
                        </div>
                        <div class="control">
                            <span class="icon has-text-danger" title="Field must not be empty">
                                <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                            </span>
                        </div>
                    </div>
                    <p class="help">Older texts are moved to the Text Archive</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Multi-User Settings -->
    <div class="card mb-5" x-data="{ open: false }">
        <header class="card-header is-clickable" @click="open = !open">
            <p class="card-header-title">
                <?php echo IconHelper::render('users', ['alt' => 'Multi-User']); ?>
                <span class="ml-2">Multi-User</span>
            </p>
            <button class="card-header-icon" aria-label="expand">
                <span class="icon" :class="{ 'has-text-primary': open }">
                    <i data-lucide="chevron-down"
                       :style="open && 'transform: rotate(180deg)'"></i>
                </span>
            </button>
        </header>
        <div class="card-content" x-show="open" x-transition>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label">Allow Registration</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <label class="checkbox">
                                <input type="checkbox"
                                       name="set-allow-registration"
                                       value="1"
                                       <?php echo ((int)($settings['set-allow-registration'] ?? '1') ? "checked" : ""); ?> />
                                Allow new users to register accounts
                            </label>
                        </div>
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
                    data-action="settings-navigate"
                    data-url="index.php">
                <span class="icon is-small">
                    <?php echo IconHelper::render('arrow-left', ['alt' => 'Back']); ?>
                </span>
                <span>Back</span>
            </button>
        </div>
        <div class="control">
            <button type="button"
                    class="button is-warning is-outlined"
                    data-action="settings-navigate"
                    data-url="/admin/settings?op=reset">
                <span class="icon is-small">
                    <?php echo IconHelper::render('rotate-ccw', ['alt' => 'Reset']); ?>
                </span>
                <span>Reset to Defaults</span>
            </button>
        </div>
        <div class="control">
            <button type="submit" name="op" value="Save" class="button is-primary">
                <span class="icon is-small">
                    <?php echo IconHelper::render('save', ['alt' => 'Save']); ?>
                </span>
                <span>Save</span>
            </button>
        </div>
    </div>
</form>
