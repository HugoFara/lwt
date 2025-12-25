<?php declare(strict_types=1);
/**
 * Language Form View
 *
 * Variables expected:
 * - $language: Language view object (stdClass)
 * - $sourceLg: string source language code
 * - $targetLg: string target language code
 * - $isNew: bool true if creating new language
 * - $parserInfo: array parser info from ParserRegistry::getParserInfo()
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @psalm-suppress TypeDoesNotContainType View included from different contexts
 */

namespace Lwt\Views\Language;

use Lwt\Shared\UI\Helpers\IconHelper;

?>
<script type="application/json" id="language-form-config">
<?php echo json_encode([
    'languageId' => $language->id,
    'languageName' => $language->name,
    'sourceLg' => $sourceLg,
    'targetLg' => $targetLg,
    'languageDefs' => \Lwt\Modules\Language\Infrastructure\LanguagePresets::getAll(),
    'allLanguages' => $allLanguages
]); ?>
</script>

<form class="validate" action="/languages" method="post" name="lg_form"
      x-data="{
          textSize: <?php echo $language->textsize ?: 100; ?>,
          parserType: '<?php echo htmlspecialchars($language->parsertype ?? 'regex', ENT_QUOTES, 'UTF-8'); ?>',
          showJapaneseOptions: <?php echo ($language->name === 'Japanese') ? 'true' : 'false'; ?>,
          showTranslatorKey: false,
          sections: {
              dictionaries: true,
              display: false,
              textProcessing: false,
              advanced: false
          }
      }">
    <input type="hidden" name="LgID" value="<?php echo $language->id; ?>" />

    <?php if (!$isNew): ?>
    <!-- Edit Warning -->
    <article class="message is-warning mb-4">
        <div class="message-body">
            <strong>Warning:</strong> Changing certain language settings
            (e.g. RegExp Word Characters, etc.) may cause partial or complete
            loss of improved annotated texts!
        </div>
    </article>
    <?php endif; ?>

    <!-- Basic Information (always visible) -->
    <div class="box mb-4">
        <h4 class="title is-5 mb-4">
            <span class="icon mr-2">
                <?php echo IconHelper::render('languages', ['alt' => 'Language']); ?>
            </span>
            Basic Information
        </h4>

        <div class="field">
            <label class="label" for="LgName">
                Study Language "L2"
                <span class="has-text-danger" title="Required">*</span>
            </label>
            <div class="control">
                <input type="text"
                       class="input notempty setfocus checkoutsidebmp"
                       data_info="Study Language"
                       name="LgName"
                       id="LgName"
                       value="<?php echo htmlspecialchars($language->name ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                       maxlength="40"
                       @input="showJapaneseOptions = ($event.target.value === 'Japanese')"
                       required />
            </div>
            <p class="help">The name of the language you want to learn</p>
        </div>
    </div>

    <!-- Dictionaries Section -->
    <div class="box mb-4" x-data="{ open: sections.dictionaries }">
        <header class="is-flex is-align-items-center is-justify-content-space-between is-clickable mb-0"
                @click="open = !open; sections.dictionaries = open">
            <h4 class="title is-5 mb-0 is-flex is-align-items-center">
                <span class="icon mr-2">
                    <?php echo IconHelper::render('book-open', ['alt' => 'Dictionaries']); ?>
                </span>
                Dictionaries & Translation
            </h4>
            <span class="icon">
                <i :class="open ? 'rotate-180' : ''" class="transition-transform" data-lucide="chevron-down"></i>
            </span>
        </header>

        <div x-show="open" x-transition x-cloak class="mt-4">
            <!-- Dictionary 1 URI -->
            <div class="field">
                <label class="label">
                    Dictionary 1 URI
                    <span class="has-text-danger" title="Required">*</span>
                </label>
                <div class="control">
                    <input type="url"
                           class="input notempty checkdicturl checkoutsidebmp"
                           name="LgDict1URI"
                           value="<?php echo htmlspecialchars($language->dict1uri ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           maxlength="200"
                           data_info="Dictionary 1 URI"
                           required />
                </div>
                <label class="checkbox mt-2">
                    <input type="checkbox" name="LgDict1PopUp" id="LgDict1PopUp" value="1"
                           <?php echo ($language->dict1popup ?? false) ? 'checked' : ''; ?> />
                    <span class="has-text-grey-dark" title="Open in a new window. Some dictionaries cannot be displayed in iframes">
                        Open in Pop-Up
                    </span>
                </label>
            </div>

            <!-- Dictionary 2 URI -->
            <div class="field">
                <label class="label">Dictionary 2 URI</label>
                <div class="control">
                    <input type="url"
                           class="input checkdicturl checkoutsidebmp"
                           name="LgDict2URI"
                           value="<?php echo htmlspecialchars($language->dict2uri ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           maxlength="200"
                           data_info="Dictionary 2 URI" />
                </div>
                <label class="checkbox mt-2">
                    <input type="checkbox" name="LgDict2PopUp" id="LgDict2PopUp" value="1"
                           <?php echo ($language->dict2popup ?? false) ? 'checked' : ''; ?> />
                    <span class="has-text-grey-dark" title="Open in a new window. Some dictionaries cannot be displayed in iframes">
                        Open in Pop-Up
                    </span>
                </label>
            </div>

            <!-- Sentence Translator URI -->
            <div class="field">
                <label class="label">Sentence Translator</label>
                <div class="field">
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="LgTranslatorName"
                                    @change="showTranslatorKey = ($event.target.value === 'libretranslate')">
                                <option value="google_translate">Google Translate (webpage)</option>
                                <option value="libretranslate">LibreTranslate API</option>
                                <option value="ggl">GoogleTranslate API</option>
                                <option value="glosbe" class="is-hidden">Glosbe API</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="field">
                    <div class="control">
                        <input type="url"
                               class="input checkdicturl checkoutsidebmp"
                               name="LgGoogleTranslateURI"
                               value="<?php echo htmlspecialchars($language->translator ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               maxlength="200"
                               data_info="GoogleTranslate URI"
                               placeholder="Translator URI" />
                    </div>
                </div>

                <div class="field" x-show="showTranslatorKey" x-transition>
                    <label class="label is-small" for="LgTranslatorKey">API Key</label>
                    <div class="control">
                        <input type="text"
                               class="input is-small"
                               id="LgTranslatorKey"
                               name="LgTranslatorKey" />
                    </div>
                </div>

                <label class="checkbox mt-2">
                    <input type="checkbox" name="LgGoogleTranslatePopUp" id="LgGoogleTranslatePopUp" value="1"
                           <?php echo ($language->translatorpopup ?? false) ? 'checked' : ''; ?> />
                    <span class="has-text-grey-dark" title="Open in a new window. Some translators cannot be displayed in iframes">
                        Open in Pop-Up
                    </span>
                </label>
                <p id="translator_error" class="help is-danger"></p>
            </div>

            <!-- Source/Target Language Codes -->
            <div class="columns mt-4">
                <div class="column">
                    <div class="field">
                        <label class="label">Source Language Code</label>
                        <div class="control">
                            <input type="text"
                                   class="input"
                                   name="LgSourceLang"
                                   id="LgSourceLang"
                                   value="<?php echo htmlspecialchars($language->sourcelang ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                   maxlength="10"
                                   placeholder="e.g., de, ja, zh" />
                        </div>
                        <p class="help">ISO code of the language you're learning (used for translation APIs)</p>
                    </div>
                </div>
                <div class="column">
                    <div class="field">
                        <label class="label">Target Language Code</label>
                        <div class="control">
                            <input type="text"
                                   class="input"
                                   name="LgTargetLang"
                                   id="LgTargetLang"
                                   value="<?php echo htmlspecialchars($language->targetlang ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                   maxlength="10"
                                   placeholder="e.g., en" />
                        </div>
                        <p class="help">ISO code of your native language (used for translation APIs)</p>
                    </div>
                </div>
            </div>

            <!-- Placeholder help note -->
            <article class="message is-warning is-small mt-4">
                <div class="message-body">
                    <strong>Important:</strong> The placeholders "<code>••</code>" for the from/sl and dest/tl
                    language codes in the URIs must be <strong>replaced</strong> by the actual source and target
                    language codes.
                    <a href="docs/info.html#howtolang" target="_blank">Read the documentation</a>.
                    Languages with a <strong>non-Latin alphabet need special attention</strong>,
                    <a href="docs/info.html#langsetup" target="_blank">see also here</a>.
                </div>
            </article>

            <!-- Local Dictionary Mode -->
            <div class="field mt-4">
                <label class="label">Local Dictionary Mode</label>
                <div class="control">
                    <div class="select">
                        <select name="LgLocalDictMode" id="LgLocalDictMode">
                            <option value="0" <?php echo ($language->localdictmode ?? 0) == 0 ? 'selected' : ''; ?>>
                                Online dictionaries only
                            </option>
                            <option value="1" <?php echo ($language->localdictmode ?? 0) == 1 ? 'selected' : ''; ?>>
                                Local first, online fallback
                            </option>
                            <option value="2" <?php echo ($language->localdictmode ?? 0) == 2 ? 'selected' : ''; ?>>
                                Local dictionaries only
                            </option>
                            <option value="3" <?php echo ($language->localdictmode ?? 0) == 3 ? 'selected' : ''; ?>>
                                Combined (show both)
                            </option>
                        </select>
                    </div>
                </div>
                <p class="help">
                    Configure how local (offline) dictionaries are used.
                    <?php if (($language->id ?? 0) > 0): ?>
                    <a href="/dictionaries?lang=<?php echo $language->id; ?>">
                        Manage local dictionaries
                    </a>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Display Section -->
    <div class="box mb-4" x-data="{ open: sections.display }">
        <header class="is-flex is-align-items-center is-justify-content-space-between is-clickable mb-0"
                @click="open = !open; sections.display = open">
            <h4 class="title is-5 mb-0 is-flex is-align-items-center">
                <span class="icon mr-2">
                    <?php echo IconHelper::render('type', ['alt' => 'Display']); ?>
                </span>
                Display Settings
            </h4>
            <span class="icon">
                <i :class="open ? 'rotate-180' : ''" class="transition-transform" data-lucide="chevron-down"></i>
            </span>
        </header>

        <div x-show="open" x-transition x-cloak class="mt-4">
            <!-- Text Size -->
            <div class="field">
                <label class="label">Text Size (%)</label>
                <div class="control">
                    <input name="LgTextSize"
                           type="number"
                           min="100"
                           max="250"
                           step="50"
                           class="input"
                           style="max-width: 120px;"
                           x-model="textSize"
                           value="<?php echo $language->textsize; ?>" />
                </div>
                <div class="field mt-2">
                    <div class="control">
                        <input type="text"
                               class="input"
                               id="LgTextSizeExample"
                               :style="'font-size: ' + textSize + '%'"
                               value="Text will be this size"
                               readonly />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Text Processing Section -->
    <div class="box mb-4" x-data="{ open: sections.textProcessing }">
        <header class="is-flex is-align-items-center is-justify-content-space-between is-clickable mb-0"
                @click="open = !open; sections.textProcessing = open">
            <h4 class="title is-5 mb-0 is-flex is-align-items-center">
                <span class="icon mr-2">
                    <?php echo IconHelper::render('settings', ['alt' => 'Text Processing']); ?>
                </span>
                Text Processing
            </h4>
            <span class="icon">
                <i :class="open ? 'rotate-180' : ''" class="transition-transform" data-lucide="chevron-down"></i>
            </span>
        </header>

        <div x-show="open" x-transition x-cloak class="mt-4">
            <!-- Parser Type -->
            <div class="field">
                <label class="label">Parser Type</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select name="LgParserType" id="LgParserType" x-model="parserType">
                            <?php foreach ($parserInfo as $type => $info): ?>
                            <option value="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo ($language->parsertype === $type) ? 'selected' : ''; ?>
                                    <?php echo !$info['available'] ? 'disabled' : ''; ?>>
                                <?php echo htmlspecialchars($info['name'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php echo !$info['available'] ? ' (unavailable)' : ''; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <p class="help">Select the parsing algorithm for this language</p>
                <?php foreach ($parserInfo as $type => $info): ?>
                    <?php if (!$info['available'] && $info['message']): ?>
                    <p class="help is-warning" x-show="parserType === '<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>'" x-cloak>
                        <?php echo htmlspecialchars($info['message'], ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Character Substitutions -->
            <div class="field">
                <label class="label">Character Substitutions</label>
                <div class="control">
                    <input type="text"
                           class="input checkoutsidebmp"
                           data_info="Character Substitutions"
                           name="LgCharacterSubstitutions"
                           value="<?php echo htmlspecialchars($language->charactersubst ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           maxlength="500" />
                </div>
                <p class="help">Replace characters before parsing (format: from=to, separated by |)</p>
            </div>

            <!-- RegExp Split Sentences (not needed for mecab) -->
            <div class="field" x-show="parserType !== 'mecab'" x-transition x-cloak>
                <label class="label">
                    RegExp Split Sentences
                    <span class="has-text-danger" title="Required" x-show="parserType === 'regex'">*</span>
                </label>
                <div class="control">
                    <input type="text"
                           class="input checkoutsidebmp"
                           :class="{ 'notempty': parserType === 'regex' }"
                           name="LgRegexpSplitSentences"
                           value="<?php echo htmlspecialchars($language->regexpsplitsent ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           maxlength="500"
                           data_info="RegExp Split Sentences"
                           :required="parserType === 'regex'" />
                </div>
                <p class="help">Regular expression to split text into sentences</p>
            </div>

            <!-- Exceptions Split Sentences (not needed for mecab) -->
            <div class="field" x-show="parserType !== 'mecab'" x-transition x-cloak>
                <label class="label">Exceptions Split Sentences</label>
                <div class="control">
                    <input type="text"
                           class="input checkoutsidebmp"
                           data_info="Exceptions Split Sentences"
                           name="LgExceptionsSplitSentences"
                           value="<?php echo htmlspecialchars($language->exceptionsplitsent ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           maxlength="500" />
                </div>
                <p class="help">Words that should not trigger sentence splitting (e.g., Mr., Dr.)</p>
            </div>

            <!-- RegExp Word Characters (only for regex parser) -->
            <div class="field" x-show="parserType === 'regex'" x-transition x-cloak>
                <label class="label">
                    RegExp Word Characters
                    <span class="has-text-danger" title="Required">*</span>
                </label>
                <div x-show="showJapaneseOptions" x-transition class="field">
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="LgRegexpAlt">
                                <option value="regexp">Regular Expressions (demo)</option>
                                <option value="mecab">MeCab (recommended)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="control">
                    <input type="text"
                           class="input notempty checkoutsidebmp"
                           data_info="RegExp Word Characters"
                           name="LgRegexpWordCharacters"
                           value="<?php echo htmlspecialchars($language->regexpwordchar ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           maxlength="500"
                           :required="parserType === 'regex'" />
                </div>
                <p class="help">Regular expression defining valid word characters</p>
            </div>

            <!-- Divider before script options -->
            <hr class="my-4" />

            <!-- Split Each Char (only for regex parser - implied by character parser) -->
            <div class="field" x-show="parserType === 'regex'" x-transition x-cloak>
                <label class="checkbox">
                    <input type="checkbox"
                           name="LgSplitEachChar"
                           id="LgSplitEachChar"
                           value="1"
                           <?php echo $language->spliteachchar ? "checked" : ""; ?> />
                    <strong>Make each character a word</strong>
                </label>
                <p class="help ml-5">For Chinese, Japanese, etc. (Use "Character" parser instead)</p>
            </div>

            <!-- Info message when using character/mecab parser -->
            <div class="field" x-show="parserType === 'character'" x-transition x-cloak>
                <p class="help is-info">
                    <span class="icon"><i data-lucide="info"></i></span>
                    The Character parser automatically treats each character as a word.
                </p>
            </div>
            <div class="field" x-show="parserType === 'mecab'" x-transition x-cloak>
                <p class="help is-info">
                    <span class="icon"><i data-lucide="info"></i></span>
                    MeCab automatically handles word segmentation for Japanese.
                </p>
            </div>

            <div class="field">
                <label class="checkbox">
                    <input type="checkbox"
                           name="LgRemoveSpaces"
                           id="LgRemoveSpaces"
                           value="1"
                           <?php echo $language->removespaces ? "checked" : ""; ?> />
                    <strong>Remove spaces</strong>
                </label>
                <p class="help ml-5">For Chinese, Japanese, etc.</p>
            </div>

            <div class="field">
                <label class="checkbox">
                    <input type="checkbox"
                           name="LgRightToLeft"
                           id="LgRightToLeft"
                           value="1"
                           <?php echo $language->rightoleft ? "checked" : ""; ?> />
                    <strong>Right-To-Left Script</strong>
                </label>
                <p class="help ml-5">For Arabic, Hebrew, Farsi, Urdu, etc.</p>
            </div>

            <div class="field">
                <label class="checkbox">
                    <input type="checkbox"
                           name="LgShowRomanization"
                           id="LgShowRomanization"
                           value="1"
                           <?php echo $language->showromanization ? "checked" : ""; ?> />
                    <strong>Show Romanization</strong>
                </label>
                <p class="help ml-5">
                    Show <a href="https://en.wikipedia.org/wiki/Romanization">romanization</a> field.
                    Recommended for Chinese, Japanese, etc.
                </p>
            </div>
        </div>
    </div>

    <!-- Advanced Section -->
    <div class="box mb-4" x-data="{ open: sections.advanced }">
        <header class="is-flex is-align-items-center is-justify-content-space-between is-clickable mb-0"
                @click="open = !open; sections.advanced = open">
            <h4 class="title is-5 mb-0 is-flex is-align-items-center">
                <span class="icon mr-2">
                    <?php echo IconHelper::render('sliders', ['alt' => 'Advanced']); ?>
                </span>
                Advanced
            </h4>
            <span class="icon">
                <i :class="open ? 'rotate-180' : ''" class="transition-transform" data-lucide="chevron-down"></i>
            </span>
        </header>

        <div x-show="open" x-transition x-cloak class="mt-4">
            <!-- Export Template -->
            <div class="field">
                <label class="label">
                    Export Template
                    <span class="icon is-small click" data-action="show-export-template-help" title="Help">
                        <?php echo IconHelper::render('help-circle', ['alt' => 'Help']); ?>
                    </span>
                </label>
                <div class="control">
                    <input type="text"
                           class="input checkoutsidebmp"
                           data_info="Export Template"
                           name="LgExportTemplate"
                           value="<?php echo htmlspecialchars($language->exporttemplate ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           maxlength="1000" />
                </div>
                <p class="help">Template for exporting terms (e.g., to Anki)</p>
            </div>

            <!-- Third-Party Text-to-Speech Voice API -->
            <div class="field">
                <label class="label">Third-Party Text-to-Speech Voice API</label>
                <div class="control mb-2">
                    <input type="text"
                           class="input"
                           name="LgVoiceAPIDemo"
                           title="Input any text you want to read."
                           value="Read this demo text."
                           placeholder="Demo text to test TTS" />
                </div>
                <div class="control">
                    <textarea class="textarea checkoutsidebmp"
                              data_info="Third-Party Text-to-Speech API"
                              name="LgTTSVoiceAPI"
                              maxlength="2048"
                              rows="4"
                              placeholder="JSON configuration for TTS API"><?php echo htmlspecialchars($language->ttsvoiceapi ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="buttons mt-3">
                    <button type="button"
                            class="button is-small is-info is-outlined"
                            data-action="check-voice-api">
                        <span class="icon is-small">
                            <?php echo IconHelper::render('check', ['alt' => 'Check']); ?>
                        </span>
                        <span>Check Voice API</span>
                    </button>
                    <button type="button"
                            class="button is-small is-success is-outlined"
                            data-action="test-voice-api">
                        <span class="icon is-small">
                            <?php echo IconHelper::render('play', ['alt' => 'Test']); ?>
                        </span>
                        <span>Test!</span>
                    </button>
                </div>
                <p class="help is-danger is-hidden" id="voice-api-message-zone"></p>

                <!-- Voice API Help -->
                <article class="message is-info is-small mt-4">
                    <div class="message-body">
                        <p>
                            You can customize the voice API using an external service.
                            Use the following JSON format:
                        </p>
                        <pre class="voice-api-code mt-2 mb-2"><code lang="json">{
    "input": ...,
    "options": ...
}</code></pre>
                        <p>
                            LWT will insert text in <code>lwt_term</code> (required),
                            you can specify the language with <code>lwt_lang</code> (optional).
                            <a href="https://github.com/HugoFara/lwt/discussions/174" target="_blank">
                                See examples and get help
                            </a>.
                        </p>
                    </div>
                </article>
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="field is-grouped is-grouped-right">
        <div class="control">
            <button type="button"
                    class="button is-light"
                    data-action="cancel-form"
                    data-redirect="/languages">
                Cancel
            </button>
        </div>
        <div class="control">
            <?php if ($isNew): ?>
            <button type="submit" name="op" value="Save" class="button is-primary">
                <span class="icon is-small">
                    <?php echo IconHelper::render('save', ['alt' => 'Save']); ?>
                </span>
                <span>Save</span>
            </button>
            <?php else: ?>
            <button type="submit" name="op" value="Change" class="button is-primary">
                <span class="icon is-small">
                    <?php echo IconHelper::render('save', ['alt' => 'Save']); ?>
                </span>
                <span>Save Changes</span>
            </button>
            <?php endif; ?>
        </div>
    </div>
</form>
