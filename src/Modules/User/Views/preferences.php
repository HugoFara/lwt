<?php

/**
 * User Preferences View
 *
 * User-scoped settings for appearance, reading, review, TTS, and pagination.
 * In multi-user mode, these are stored per-user in the settings table.
 *
 * Variables expected:
 * - $settings: array of current user preference values
 * - $currentLanguageCode: string JSON-encoded language code for TTS
 * - $languageOptions: string HTML options for language select
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\User\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\User\Views;

use Lwt\Shared\UI\Helpers\SelectOptionsBuilder;
use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\FormHelper;

/**
 * @var array<string, string> $settings Current user preference values
 * @var string $currentLanguageCode Current language code for TTS (already JSON-encoded)
 * @var string $languageOptions HTML options for language select
 * @var array<int, array{
 *     name: string,
 *     path: string,
 *     description?: string,
 *     mode?: string,
 *     highlighting?: string,
 *     wordBreaking?: string
 * }> $themes Available themes from ThemeService
 */

?>
<form class="validate" action="/profile/preferences" method="post" data-lwt-settings-form>
    <?php echo FormHelper::csrfField(); ?>

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
            <div x-data="themeSelector"
                 data-current-theme="<?php echo $currentTheme; ?>">
                <div class="field">
                    <label class="label" for="set-theme-dir">Theme</label>
                    <div class="field has-addons">
                        <div class="control is-expanded">
                            <div class="select is-fullwidth">
                                <select name="set-theme-dir" id="set-theme-dir" class="notempty" required
                                        x-model="currentTheme"
                                        @change="onThemeChange()">
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

    <!-- Language Section -->
    <div class="card settings-section mb-4" x-data="{ open: false }">
        <header class="card-header is-clickable" @click="open = !open">
            <p class="card-header-title">
                <?php echo IconHelper::render('languages', ['alt' => 'Language']); ?>
                <span class="ml-2">Interface Language</span>
            </p>
            <button type="button" class="card-header-icon" aria-label="toggle section">
                <span class="icon">
                    <i :class="open ? 'rotate-180' : ''" class="transition-transform" data-lucide="chevron-down"></i>
                </span>
            </button>
        </header>
        <div class="card-content" x-show="open" x-transition>
            <div class="field">
                <label class="label" for="app_language">Application Language</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <?php
                        $i18nContainer = \Lwt\Shared\Infrastructure\Container\Container::getInstance();
                        $availableLocales = ['en'];
                        if ($i18nContainer->has(\Lwt\Shared\I18n\Translator::class)) {
                            $availableLocales = $i18nContainer
                                ->getTyped(\Lwt\Shared\I18n\Translator::class)
                                ->getAvailableLocales();
                        }
                        ?>
                        <select name="app_language" id="app_language">
                            <?php
                            echo SelectOptionsBuilder::forAppLanguages(
                                $availableLocales,
                                $settings['app_language'] ?? 'en'
                            );
                            ?>
                        </select>
                    </div>
                </div>
                <p class="help">
                    The language used for the LWT user interface.
                    Changes apply after saving and reloading the page.
                </p>
            </div>
        </div>
    </div>

    <!-- Read Text Screen Section -->
    <div class="card settings-section mb-4" x-data="{ open: false }">
        <header class="card-header is-clickable" @click="open = !open">
            <p class="card-header-title">
                <?php echo IconHelper::render('book-open', ['alt' => 'Read Text Screen']); ?>
                <span class="ml-2">Read Text Screen</span>
            </p>
            <button type="button" class="card-header-icon" aria-label="toggle section">
                <span class="icon">
                    <i :class="open ? 'rotate-180' : ''" class="transition-transform" data-lucide="chevron-down"></i>
                </span>
            </button>
        </header>
        <div class="card-content" x-show="open" x-transition>
            <div class="field">
                <label class="label" for="set-words-to-do-buttons">Words To Do Buttons</label>
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <div class="select is-fullwidth">
                            <select name="set-words-to-do-buttons"
                                    id="set-words-to-do-buttons"
                                    class="notempty"
                                    required>
                                <?php
                                echo SelectOptionsBuilder::forWordsToDoButtons(
                                    $settings['set-words-to-do-buttons']
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
                <p class="help">
                    Which bulk-action buttons appear on the reading screen for unknown
                    words: just "I Know All", or also "Ignore All".
                </p>
            </div>

            <div class="field">
                <label class="label" for="set-tooltip-mode">Tooltips</label>
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <div class="select is-fullwidth">
                            <select name="set-tooltip-mode" id="set-tooltip-mode" class="notempty" required>
                                <?php echo SelectOptionsBuilder::forTooltipType($settings['set-tooltip-mode']); ?>
                            </select>
                        </div>
                    </div>
                    <div class="control">
                        <span class="icon has-text-danger" title="Field must not be empty">
                            <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                        </span>
                    </div>
                </div>
                <p class="help">
                    Style of the popup that appears when hovering a word while reading.
                </p>
            </div>

            <div class="field">
                <label class="label" for="set-ggl-translation-per-page">Translations per Page</label>
                <div class="field has-addons">
                    <div class="control">
                        <input class="input notempty posintnumber"
                               type="number"
                               min="0"
                               id="set-ggl-translation-per-page"
                               name="set-ggl-translation-per-page"
                               data_info="New Term Translations per Page"
                               value="<?php
                                   echo htmlspecialchars(
                                       $settings['set-ggl-translation-per-page'] ?? '',
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
                <p class="help">
                    How many machine-translation suggestions to fetch per page when
                    looking up a new term.
                </p>
            </div>
        </div>
    </div>

    <!-- Review Screen Section -->
    <div class="card settings-section mb-4" x-data="{ open: false }">
        <header class="card-header is-clickable" @click="open = !open">
            <p class="card-header-title">
                <?php echo IconHelper::render('graduation-cap', ['alt' => 'Review Screen']); ?>
                <span class="ml-2">Review Screen</span>
            </p>
            <button type="button" class="card-header-icon" aria-label="toggle section">
                <span class="icon">
                    <i :class="open ? 'rotate-180' : ''" class="transition-transform" data-lucide="chevron-down"></i>
                </span>
            </button>
        </header>
        <div class="card-content" x-show="open" x-transition>
            <div class="field">
                <label class="label" for="set-test-main-frame-waiting-time">Display Next Review After</label>
                <div class="field has-addons">
                    <div class="control">
                        <input class="input notempty zeroposintnumber"
                               type="number"
                               min="0"
                               id="set-test-main-frame-waiting-time"
                               name="set-test-main-frame-waiting-time"
                               data_info="Waiting time after assessment to display next review"
                               value="<?php
                                   echo htmlspecialchars(
                                       $settings['set-test-main-frame-waiting-time'] ?? '',
                                       ENT_QUOTES,
                                       'UTF-8'
                                   );
                                    ?>"
                               maxlength="4"
                               style="width: 120px;"
                               required />
                    </div>
                    <div class="control">
                        <span class="button is-static">ms</span>
                    </div>
                    <div class="control">
                        <span class="icon has-text-danger" title="Field must not be empty">
                            <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                        </span>
                    </div>
                </div>
                <p class="help">Waiting time after assessment to display next review</p>
            </div>

            <div class="field">
                <label class="label" for="set-test-edit-frame-waiting-time">Clear Edit Frame After</label>
                <div class="field has-addons">
                    <div class="control">
                        <input class="input notempty zeroposintnumber"
                               type="number"
                               min="0"
                               id="set-test-edit-frame-waiting-time"
                               name="set-test-edit-frame-waiting-time"
                               data_info="Waiting Time to clear the message/edit frame"
                               value="<?php
                                   echo htmlspecialchars(
                                       $settings['set-test-edit-frame-waiting-time'] ?? '',
                                       ENT_QUOTES,
                                       'UTF-8'
                                   );
                                    ?>"
                               maxlength="8"
                               style="width: 120px;"
                               required />
                    </div>
                    <div class="control">
                        <span class="button is-static">ms</span>
                    </div>
                    <div class="control">
                        <span class="icon has-text-danger" title="Field must not be empty">
                            <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                        </span>
                    </div>
                </div>
                <p class="help">Waiting time to clear the message/edit frame</p>
            </div>
        </div>
    </div>

    <!-- Reading Section -->
    <div class="card settings-section mb-4" x-data="{ open: false }">
        <header class="card-header is-clickable" @click="open = !open">
            <p class="card-header-title">
                <?php echo IconHelper::render('glasses', ['alt' => 'Reading']); ?>
                <span class="ml-2">Reading</span>
            </p>
            <button type="button" class="card-header-icon" aria-label="toggle section">
                <span class="icon">
                    <i :class="open ? 'rotate-180' : ''" class="transition-transform" data-lucide="chevron-down"></i>
                </span>
            </button>
        </header>
        <div class="card-content" x-show="open" x-transition>
            <div class="field">
                <label class="label" for="set-text-visit-statuses-via-key">Navigate Term Statuses</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select name="set-text-visit-statuses-via-key" id="set-text-visit-statuses-via-key">
                            <?php
                            echo SelectOptionsBuilder::forWordStatus(
                                $settings['set-text-visit-statuses-via-key'],
                                true,
                                true,
                                true
                            );
                            ?>
                        </select>
                    </div>
                </div>
                <p class="help">
                    Visit saved terms with these statuses via keystrokes (RIGHT, SPACE, LEFT, etc.)
                </p>
            </div>

            <div class="field">
                <label class="label" for="set-display-text-frame-term-translation">Show Translations For</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select name="set-display-text-frame-term-translation"
                                id="set-display-text-frame-term-translation">
                            <?php
                            echo SelectOptionsBuilder::forWordStatus(
                                $settings['set-display-text-frame-term-translation'],
                                true,
                                true,
                                true
                            );
                            ?>
                        </select>
                    </div>
                </div>
                <p class="help">Display translations of terms with these statuses</p>
            </div>

            <div class="field">
                <label class="label" for="set-text-frame-annotation-position">Translation Position</label>
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <div class="select is-fullwidth">
                            <select name="set-text-frame-annotation-position"
                                    id="set-text-frame-annotation-position"
                                    class="notempty"
                                    required>
                                <?php
                                echo SelectOptionsBuilder::forAnnotationPosition(
                                    $settings['set-text-frame-annotation-position']
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
            </div>
        </div>
    </div>

    <!-- Review & Terms Section -->
    <div class="card settings-section mb-4" x-data="{ open: false }">
        <header class="card-header is-clickable" @click="open = !open">
            <p class="card-header-title">
                <?php echo IconHelper::render('spell-check', ['alt' => 'Review & Terms']); ?>
                <span class="ml-2">Review &amp; Terms</span>
            </p>
            <button type="button" class="card-header-icon" aria-label="toggle section">
                <span class="icon">
                    <i :class="open ? 'rotate-180' : ''" class="transition-transform" data-lucide="chevron-down"></i>
                </span>
            </button>
        </header>
        <div class="card-content" x-show="open" x-transition>
            <div class="field">
                <label class="label" for="set-test-sentence-count">Review Sentence Count</label>
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <div class="select is-fullwidth">
                            <select name="set-test-sentence-count"
                                    id="set-test-sentence-count"
                                    class="notempty"
                                    required>
                                <?php
                                echo SelectOptionsBuilder::forSentenceCount(
                                    $settings['set-test-sentence-count']
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
                <p class="help">Number of sentences displayed from text during review</p>
            </div>

            <div class="field">
                <label class="label" for="set-term-sentence-count">Term Sentence Count</label>
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <div class="select is-fullwidth">
                            <select name="set-term-sentence-count"
                                    id="set-term-sentence-count"
                                    class="notempty"
                                    required>
                                <?php
                                echo SelectOptionsBuilder::forSentenceCount(
                                    $settings['set-term-sentence-count']
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
                <p class="help">Number of sentences generated from text for terms</p>
            </div>

            <div class="field">
                <label class="label" for="set-similar-terms-count">Similar Terms Count</label>
                <div class="field has-addons">
                    <div class="control">
                        <input class="input notempty zeroposintnumber"
                               type="number"
                               min="0"
                               max="9"
                               id="set-similar-terms-count"
                               name="set-similar-terms-count"
                               data_info="Similar terms to be displayed while adding/editing a term"
                               value="<?php
                                   echo htmlspecialchars(
                                       $settings['set-similar-terms-count'] ?? '',
                                       ENT_QUOTES,
                                       'UTF-8'
                                   );
                                    ?>"
                               maxlength="1"
                               style="width: 80px;"
                               required />
                    </div>
                    <div class="control">
                        <span class="icon has-text-danger" title="Field must not be empty">
                            <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                        </span>
                    </div>
                </div>
                <p class="help">Similar terms displayed while adding/editing a term</p>
            </div>

            <div class="field">
                <label class="label" for="set-term-translation-delimiters">Translation Delimiters</label>
                <div class="field has-addons">
                    <div class="control">
                        <input class="input notempty"
                               type="text"
                               id="set-term-translation-delimiters"
                               name="set-term-translation-delimiters"
                               value="<?php
                                   echo htmlspecialchars(
                                       $settings['set-term-translation-delimiters'] ?? '',
                                       ENT_QUOTES,
                                       'UTF-8'
                                   );
                                    ?>"
                               maxlength="8"
                               style="width: 120px;"
                               required />
                    </div>
                    <div class="control">
                        <span class="icon has-text-danger" title="Field must not be empty">
                            <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                        </span>
                    </div>
                </div>
                <p class="help">
                    Characters that delimit different translations (used in annotation selection)
                </p>
            </div>
        </div>
    </div>

    <!-- Text to Speech Section -->
    <div class="card settings-section mb-4" x-data="{ open: false }">
        <header class="card-header is-clickable" @click="open = !open">
            <p class="card-header-title">
                <?php echo IconHelper::render('volume-2', ['alt' => 'Text to Speech']); ?>
                <span class="ml-2">Text to Speech</span>
            </p>
            <button type="button" class="card-header-icon" aria-label="toggle section">
                <span class="icon">
                    <i :class="open ? 'rotate-180' : ''" class="transition-transform" data-lucide="chevron-down"></i>
                </span>
            </button>
        </header>
        <div class="card-content" x-show="open" x-transition>
            <div class="field">
                <label class="label">Save Audio to Disk</label>
                <div class="control">
                    <label class="checkbox">
                        <input type="checkbox"
                               name="set-tts"
                               value="1"
                               <?php echo ((int)$settings['set-tts'] ? "checked" : ""); ?> />
                        Enable saving TTS audio files to disk
                    </label>
                </div>
            </div>

            <div class="field">
                <label class="label" for="set-hts">Read Word Aloud</label>
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <div class="select is-fullwidth">
                            <select name="set-hts" id="set-hts" class="notempty" required>
                                <?php echo SelectOptionsBuilder::forHoverTranslation($settings['set-hts']); ?>
                            </select>
                        </div>
                    </div>
                    <div class="control">
                        <span class="icon has-text-danger" title="Field must not be empty">
                            <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                        </span>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <!-- Browser Voice Settings - Alpine.js Component -->
            <script type="application/json" id="tts-settings-config">
                {"currentLanguageCode": <?php echo $currentLanguageCode; ?>}
            </script>
            <div x-data="ttsSettingsApp"
                 @submit.window="saveSettings()">

                <h4 class="subtitle is-6 mb-3">Browser Voice Settings</h4>
                <p class="help mb-3">These settings are stored in your browser and apply per language.</p>

                <!-- Language Code -->
                <div class="field">
                    <label class="label" for="get-language">Language</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="LgName"
                                    id="get-language"
                                    x-model="currentLanguage"
                                    @change="onLanguageChange()">
                                <?php echo $languageOptions; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Voice Selection -->
                <div class="field">
                    <label class="label" for="voice">Voice</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="LgVoice" id="voice" x-model="selectedVoice">
                                <template x-if="voicesLoading">
                                    <option value="">Loading voices...</option>
                                </template>
                                <template x-if="!voicesLoading && voices.length === 0">
                                    <option value="">No voices available</option>
                                </template>
                                <template x-for="voice in voices" :key="voice.name">
                                    <option :value="voice.name"
                                            :data-lang="voice.lang"
                                            :data-name="voice.name"
                                            x-text="getVoiceDisplayName(voice)">
                                    </option>
                                </template>
                            </select>
                        </div>
                    </div>
                    <p class="help">Available voices depend on your web browser</p>
                </div>

                <!-- Reading Rate -->
                <div class="field">
                    <label class="label" for="rate">Reading Rate</label>
                    <div class="control">
                        <div class="columns is-vcentered is-mobile">
                            <div class="column is-narrow">
                                <span class="tag is-light">0.5x</span>
                            </div>
                            <div class="column">
                                <input type="range"
                                       name="LgTTSRate"
                                       id="rate"
                                       class="slider is-fullwidth"
                                       min="0.5"
                                       max="2"
                                       step="0.1"
                                       x-model.number="rate" />
                            </div>
                            <div class="column is-narrow">
                                <span class="tag is-light">2x</span>
                            </div>
                            <div class="column is-narrow">
                                <span class="tag is-info" x-text="getRateDisplay()"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pitch -->
                <div class="field">
                    <label class="label" for="pitch">Pitch</label>
                    <div class="control">
                        <div class="columns is-vcentered is-mobile">
                            <div class="column is-narrow">
                                <span class="tag is-light">Low</span>
                            </div>
                            <div class="column">
                                <input type="range"
                                       name="LgPitch"
                                       id="pitch"
                                       class="slider is-fullwidth"
                                       min="0"
                                       max="2"
                                       step="0.1"
                                       x-model.number="pitch" />
                            </div>
                            <div class="column is-narrow">
                                <span class="tag is-light">High</span>
                            </div>
                            <div class="column is-narrow">
                                <span class="tag is-info" x-text="pitch"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Demo -->
                <div class="field">
                    <label class="label" for="tts-demo">Test</label>
                    <div class="control">
                        <div class="columns is-vcentered">
                            <div class="column">
                                <input type="text"
                                       class="input"
                                       id="tts-demo"
                                       placeholder="Enter text to test speech..."
                                       x-model="demoText" />
                            </div>
                            <div class="column is-narrow">
                                <button type="button"
                                        class="button is-info"
                                        @click="playDemo()">
                                    <span class="icon is-small">
                                        <?php echo IconHelper::render('play', ['alt' => 'Play']); ?>
                                    </span>
                                    <span>Test</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <p class="help">Voice settings are stored in your browser's local storage</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tables & Pagination Section -->
    <div class="card settings-section mb-4" x-data="{ open: false }">
        <header class="card-header is-clickable" @click="open = !open">
            <p class="card-header-title">
                <?php echo IconHelper::render('table', ['alt' => 'Tables & Pagination']); ?>
                <span class="ml-2">Tables &amp; Pagination</span>
            </p>
            <button type="button" class="card-header-icon" aria-label="toggle section">
                <span class="icon">
                    <i :class="open ? 'rotate-180' : ''" class="transition-transform" data-lucide="chevron-down"></i>
                </span>
            </button>
        </header>
        <div class="card-content" x-show="open" x-transition>
            <div class="columns">
                <div class="column is-half">
                    <div class="field">
                        <label class="label" for="set-texts-per-page">Texts per Page</label>
                        <div class="field has-addons">
                            <div class="control is-expanded">
                                <input class="input notempty posintnumber"
                                       type="number"
                                       min="0"
                                       id="set-texts-per-page"
                                       name="set-texts-per-page"
                                       data_info="Texts per Page"
                                       value="<?php
                                           echo htmlspecialchars(
                                               $settings['set-texts-per-page'] ?? '',
                                               ENT_QUOTES,
                                               'UTF-8'
                                           );
                                            ?>"
                                       maxlength="4"
                                       required />
                            </div>
                            <div class="control">
                                <span class="icon has-text-danger" title="Field must not be empty">
                                    <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                                </span>
                            </div>
                        </div>
                        <p class="help">Number of rows shown on the active texts list.</p>
                    </div>
                </div>

                <div class="column is-half">
                    <div class="field">
                        <label class="label" for="set-archived_texts-per-page">Archived Texts per Page</label>
                        <div class="field has-addons">
                            <div class="control is-expanded">
                                <input class="input notempty posintnumber"
                                       type="number"
                                       min="0"
                                       id="set-archived_texts-per-page"
                                       name="set-archived_texts-per-page"
                                       data_info="Archived Texts per Page"
                                       value="<?php
                                           echo htmlspecialchars(
                                               $settings['set-archived_texts-per-page'] ?? '',
                                               ENT_QUOTES,
                                               'UTF-8'
                                           );
                                            ?>"
                                       maxlength="4"
                                       required />
                            </div>
                            <div class="control">
                                <span class="icon has-text-danger" title="Field must not be empty">
                                    <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                                </span>
                            </div>
                        </div>
                        <p class="help">Number of rows shown on the archived texts list.</p>
                    </div>
                </div>
            </div>

            <div class="columns">
                <div class="column is-half">
                    <div class="field">
                        <label class="label" for="set-terms-per-page">Terms per Page</label>
                        <div class="field has-addons">
                            <div class="control is-expanded">
                                <input class="input notempty posintnumber"
                                       type="number"
                                       min="0"
                                       id="set-terms-per-page"
                                       name="set-terms-per-page"
                                       data_info="Terms per Page"
                                       value="<?php
                                           echo htmlspecialchars(
                                               $settings['set-terms-per-page'] ?? '',
                                               ENT_QUOTES,
                                               'UTF-8'
                                           );
                                            ?>"
                                       maxlength="4"
                                       required />
                            </div>
                            <div class="control">
                                <span class="icon has-text-danger" title="Field must not be empty">
                                    <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                                </span>
                            </div>
                        </div>
                        <p class="help">Number of vocabulary entries shown per page on the terms list.</p>
                    </div>
                </div>

                <div class="column is-half">
                    <div class="field">
                        <label class="label" for="set-tags-per-page">Tags per Page</label>
                        <div class="field has-addons">
                            <div class="control is-expanded">
                                <input class="input notempty posintnumber"
                                       type="number"
                                       min="0"
                                       id="set-tags-per-page"
                                       name="set-tags-per-page"
                                       data_info="Tags per Page"
                                       value="<?php
                                           echo htmlspecialchars(
                                               $settings['set-tags-per-page'] ?? '',
                                               ENT_QUOTES,
                                               'UTF-8'
                                           );
                                            ?>"
                                       maxlength="4"
                                       required />
                            </div>
                            <div class="control">
                                <span class="icon has-text-danger" title="Field must not be empty">
                                    <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                                </span>
                            </div>
                        </div>
                        <p class="help">Number of tags shown per page on the tag management screens.</p>
                    </div>
                </div>
            </div>

            <div class="columns">
                <div class="column is-half">
                    <div class="field">
                        <label class="label" for="set-articles-per-page">Feed Articles per Page</label>
                        <div class="field has-addons">
                            <div class="control is-expanded">
                                <input class="input notempty posintnumber"
                                       type="number"
                                       min="0"
                                       id="set-articles-per-page"
                                       name="set-articles-per-page"
                                       data_info="Feed Articles per Page"
                                       value="<?php
                                           echo htmlspecialchars(
                                               $settings['set-articles-per-page'] ?? '',
                                               ENT_QUOTES,
                                               'UTF-8'
                                           );
                                            ?>"
                                       maxlength="4"
                                       required />
                            </div>
                            <div class="control">
                                <span class="icon has-text-danger" title="Field must not be empty">
                                    <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                                </span>
                            </div>
                        </div>
                        <p class="help">Number of articles to display when browsing a single newsfeed.</p>
                    </div>
                </div>

                <div class="column is-half">
                    <div class="field">
                        <label class="label" for="set-feeds-per-page">Feeds per Page</label>
                        <div class="field has-addons">
                            <div class="control is-expanded">
                                <input class="input notempty posintnumber"
                                       type="number"
                                       min="0"
                                       id="set-feeds-per-page"
                                       name="set-feeds-per-page"
                                       data_info="Feeds per Page"
                                       value="<?php
                                           echo htmlspecialchars(
                                               $settings['set-feeds-per-page'] ?? '',
                                               ENT_QUOTES,
                                               'UTF-8'
                                           );
                                            ?>"
                                       maxlength="4"
                                       required />
                            </div>
                            <div class="control">
                                <span class="icon has-text-danger" title="Field must not be empty">
                                    <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                                </span>
                            </div>
                        </div>
                        <p class="help">Number of feeds shown per page on the newsfeeds list.</p>
                    </div>
                </div>
            </div>

            <div class="field">
                <label class="label" for="set-regex-mode">Query Mode</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select name="set-regex-mode" id="set-regex-mode">
                            <?php echo SelectOptionsBuilder::forRegexMode($settings['set-regex-mode']); ?>
                        </select>
                    </div>
                </div>
                <p class="help">
                    How filter boxes interpret your input: plain text matching or
                    full regular expressions.
                </p>
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="field is-grouped is-grouped-right">
        <div class="control">
            <a href="/profile" class="button is-light">
                <span class="icon is-small">
                    <?php echo IconHelper::render('arrow-left', ['alt' => 'Back']); ?>
                </span>
                <span>Back to Profile</span>
            </a>
        </div>
        <div class="control">
            <button type="submit" name="op" value="Save" class="button is-primary">
                <span class="icon is-small">
                    <?php echo IconHelper::render('save', ['alt' => 'Save']); ?>
                </span>
                <span>Save Preferences</span>
            </button>
        </div>
    </div>
</form>
