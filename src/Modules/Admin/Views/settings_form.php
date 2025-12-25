<?php declare(strict_types=1);
/**
 * Settings Form View
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
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Views\Admin;

use Lwt\Shared\UI\Helpers\SelectOptionsBuilder;
use Lwt\Shared\UI\Helpers\IconHelper;

?>
<form class="validate" action="/admin/settings" method="post" data-lwt-settings-form>

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
            <div class="field is-horizontal"
                 x-data="{
                     currentTheme: '<?php echo htmlspecialchars($settings['set-theme-dir'] ?? '', ENT_QUOTES, 'UTF-8'); ?>',
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
                                        <?php echo SelectOptionsBuilder::forThemes($themes, $settings['set-theme-dir']); ?>
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
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" for="set-words-to-do-buttons">Words To Do Buttons</label>
                </div>
                <div class="field-body">
                    <div class="field has-addons">
                        <div class="control is-expanded">
                            <div class="select is-fullwidth">
                                <select name="set-words-to-do-buttons" id="set-words-to-do-buttons" class="notempty" required>
                                    <?php echo SelectOptionsBuilder::forWordsToDoButtons($settings['set-words-to-do-buttons']); ?>
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

            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" for="set-tooltip-mode">Tooltips</label>
                </div>
                <div class="field-body">
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
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" for="set-ggl-translation-per-page">Translations per Page</label>
                </div>
                <div class="field-body">
                    <div class="field has-addons">
                        <div class="control">
                            <input class="input notempty posintnumber"
                                   type="number"
                                   min="0"
                                   id="set-ggl-translation-per-page"
                                   name="set-ggl-translation-per-page"
                                   data_info="New Term Translations per Page"
                                   value="<?php echo htmlspecialchars($settings['set-ggl-translation-per-page'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
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
                </div>
            </div>
        </div>
    </div>

    <!-- Test Screen Section -->
    <div class="card settings-section mb-4" x-data="{ open: false }">
        <header class="card-header is-clickable" @click="open = !open">
            <p class="card-header-title">
                <?php echo IconHelper::render('graduation-cap', ['alt' => 'Test Screen']); ?>
                <span class="ml-2">Test Screen</span>
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
                    <label class="label" for="set-test-main-frame-waiting-time">Display Next Test After</label>
                </div>
                <div class="field-body">
                    <div class="field has-addons">
                        <div class="control">
                            <input class="input notempty zeroposintnumber"
                                   type="number"
                                   min="0"
                                   id="set-test-main-frame-waiting-time"
                                   name="set-test-main-frame-waiting-time"
                                   data_info="Waiting time after assessment to display next test"
                                   value="<?php echo htmlspecialchars($settings['set-test-main-frame-waiting-time'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
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
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" for="set-test-edit-frame-waiting-time">Clear Edit Frame After</label>
                </div>
                <div class="field-body">
                    <div class="field has-addons">
                        <div class="control">
                            <input class="input notempty zeroposintnumber"
                                   type="number"
                                   min="0"
                                   id="set-test-edit-frame-waiting-time"
                                   name="set-test-edit-frame-waiting-time"
                                   data_info="Waiting Time to clear the message/edit frame"
                                   value="<?php echo htmlspecialchars($settings['set-test-edit-frame-waiting-time'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
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
                </div>
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
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" for="set-text-visit-statuses-via-key">Navigate Term Statuses</label>
                </div>
                <div class="field-body">
                    <div class="field">
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
                        <p class="help">Visit saved terms with these statuses via keystrokes (RIGHT, SPACE, LEFT, etc.)</p>
                    </div>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" for="set-display-text-frame-term-translation">Show Translations For</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <div class="select is-fullwidth">
                                <select name="set-display-text-frame-term-translation" id="set-display-text-frame-term-translation">
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
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" for="set-text-frame-annotation-position">Translation Position</label>
                </div>
                <div class="field-body">
                    <div class="field has-addons">
                        <div class="control is-expanded">
                            <div class="select is-fullwidth">
                                <select name="set-text-frame-annotation-position" id="set-text-frame-annotation-position" class="notempty" required>
                                    <?php echo SelectOptionsBuilder::forAnnotationPosition($settings['set-text-frame-annotation-position']); ?>
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
    </div>

    <!-- Testing & Terms Section -->
    <div class="card settings-section mb-4" x-data="{ open: false }">
        <header class="card-header is-clickable" @click="open = !open">
            <p class="card-header-title">
                <?php echo IconHelper::render('spell-check', ['alt' => 'Testing & Terms']); ?>
                <span class="ml-2">Testing &amp; Terms</span>
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
                    <label class="label" for="set-test-sentence-count">Test Sentence Count</label>
                </div>
                <div class="field-body">
                    <div class="field has-addons">
                        <div class="control is-expanded">
                            <div class="select is-fullwidth">
                                <select name="set-test-sentence-count" id="set-test-sentence-count" class="notempty" required>
                                    <?php echo SelectOptionsBuilder::forSentenceCount($settings['set-test-sentence-count']); ?>
                                </select>
                            </div>
                        </div>
                        <div class="control">
                            <span class="icon has-text-danger" title="Field must not be empty">
                                <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                            </span>
                        </div>
                    </div>
                    <p class="help">Number of sentences displayed from text during testing</p>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" for="set-term-sentence-count">Term Sentence Count</label>
                </div>
                <div class="field-body">
                    <div class="field has-addons">
                        <div class="control is-expanded">
                            <div class="select is-fullwidth">
                                <select name="set-term-sentence-count" id="set-term-sentence-count" class="notempty" required>
                                    <?php echo SelectOptionsBuilder::forSentenceCount($settings['set-term-sentence-count']); ?>
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
            </div>

            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" for="set-similar-terms-count">Similar Terms Count</label>
                </div>
                <div class="field-body">
                    <div class="field has-addons">
                        <div class="control">
                            <input class="input notempty zeroposintnumber"
                                   type="number"
                                   min="0"
                                   max="9"
                                   id="set-similar-terms-count"
                                   name="set-similar-terms-count"
                                   data_info="Similar terms to be displayed while adding/editing a term"
                                   value="<?php echo htmlspecialchars($settings['set-similar-terms-count'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
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
            </div>

            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" for="set-term-translation-delimiters">Translation Delimiters</label>
                </div>
                <div class="field-body">
                    <div class="field has-addons">
                        <div class="control">
                            <input class="input notempty"
                                   type="text"
                                   id="set-term-translation-delimiters"
                                   name="set-term-translation-delimiters"
                                   value="<?php echo htmlspecialchars($settings['set-term-translation-delimiters'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
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
                    <p class="help">Characters that delimit different translations (used in annotation selection)</p>
                </div>
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
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label">Save Audio to Disk</label>
                </div>
                <div class="field-body">
                    <div class="field">
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
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" for="set-hts">Read Word Aloud</label>
                </div>
                <div class="field-body">
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
            </div>

            <hr class="my-4">

            <!-- Browser Voice Settings - Alpine.js Component -->
            <div x-data="ttsSettingsApp({ currentLanguageCode: <?php echo $currentLanguageCode; ?> })"
                 @submit.window="saveSettings()">

                <h4 class="subtitle is-6 mb-3">Browser Voice Settings</h4>
                <p class="help mb-3">These settings are stored in your browser and apply per language.</p>

                <!-- Language Code -->
                <div class="field is-horizontal">
                    <div class="field-label is-normal">
                        <label class="label" for="get-language">Language</label>
                    </div>
                    <div class="field-body">
                        <div class="field">
                            <div class="control is-expanded">
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
                    </div>
                </div>

                <!-- Voice Selection -->
                <div class="field is-horizontal">
                    <div class="field-label is-normal">
                        <label class="label" for="voice">Voice</label>
                    </div>
                    <div class="field-body">
                        <div class="field">
                            <div class="control is-expanded">
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
                    </div>
                </div>

                <!-- Reading Rate -->
                <div class="field is-horizontal">
                    <div class="field-label is-normal">
                        <label class="label" for="rate">Reading Rate</label>
                    </div>
                    <div class="field-body">
                        <div class="field">
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
                                        <span class="tag is-info" x-text="rate + 'x'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pitch -->
                <div class="field is-horizontal">
                    <div class="field-label is-normal">
                        <label class="label" for="pitch">Pitch</label>
                    </div>
                    <div class="field-body">
                        <div class="field">
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
                    </div>
                </div>

                <!-- Demo -->
                <div class="field is-horizontal">
                    <div class="field-label is-normal">
                        <label class="label" for="tts-demo">Test</label>
                    </div>
                    <div class="field-body">
                        <div class="field">
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
                                       value="<?php echo htmlspecialchars($settings['set-texts-per-page'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                       maxlength="4"
                                       required />
                            </div>
                            <div class="control">
                                <span class="icon has-text-danger" title="Field must not be empty">
                                    <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="column is-half">
                    <div class="field">
                        <label class="label" for="set-archivedtexts-per-page">Archived Texts per Page</label>
                        <div class="field has-addons">
                            <div class="control is-expanded">
                                <input class="input notempty posintnumber"
                                       type="number"
                                       min="0"
                                       id="set-archivedtexts-per-page"
                                       name="set-archivedtexts-per-page"
                                       data_info="Archived Texts per Page"
                                       value="<?php echo htmlspecialchars($settings['set-archivedtexts-per-page'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                       maxlength="4"
                                       required />
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
                                       value="<?php echo htmlspecialchars($settings['set-terms-per-page'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                       maxlength="4"
                                       required />
                            </div>
                            <div class="control">
                                <span class="icon has-text-danger" title="Field must not be empty">
                                    <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                                </span>
                            </div>
                        </div>
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
                                       value="<?php echo htmlspecialchars($settings['set-tags-per-page'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                       maxlength="4"
                                       required />
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
                                       value="<?php echo htmlspecialchars($settings['set-articles-per-page'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                       maxlength="4"
                                       required />
                            </div>
                            <div class="control">
                                <span class="icon has-text-danger" title="Field must not be empty">
                                    <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                                </span>
                            </div>
                        </div>
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
                                       value="<?php echo htmlspecialchars($settings['set-feeds-per-page'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                       maxlength="4"
                                       required />
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

            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" for="set-regex-mode">Query Mode</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <div class="select is-fullwidth">
                                <select name="set-regex-mode" id="set-regex-mode">
                                    <?php echo SelectOptionsBuilder::forRegexMode($settings['set-regex-mode']); ?>
                                </select>
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
                    <label class="label" for="set-max-articles-with-text">Max Articles <span class="has-text-weight-normal">(with cache)</span></label>
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
                                   value="<?php echo htmlspecialchars($settings['set-max-articles-with-text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
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
                    <label class="label" for="set-max-articles-without-text">Max Articles <span class="has-text-weight-normal">(no cache)</span></label>
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
                                   value="<?php echo htmlspecialchars($settings['set-max-articles-without-text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
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
                                   value="<?php echo htmlspecialchars($settings['set-max-texts-per-feed'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
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
