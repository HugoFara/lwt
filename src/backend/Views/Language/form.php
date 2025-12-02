<?php declare(strict_types=1);
/**
 * Language Form View
 *
 * Variables expected:
 * - $language: Language object
 * - $sourceLg: string source language code
 * - $targetLg: string target language code
 * - $isNew: bool true if creating new language
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

namespace Lwt\Views\Language;

use Lwt\View\Helper\IconHelper;

?>
<script type="application/json" id="language-form-config">
<?php echo json_encode([
    'languageId' => $language->id,
    'languageName' => $language->name,
    'sourceLg' => $sourceLg,
    'targetLg' => $targetLg,
    'languageDefs' => \Lwt\Services\LanguageDefinitions::getAll(),
    'allLanguages' => $allLanguages ?? []
]); ?>
</script>

<form class="validate" action="/languages" method="post" name="lg_form"
      x-data="{
          textSize: <?php echo $language->textsize ?: 100; ?>,
          showJapaneseOptions: <?php echo ($language->name === 'Japanese') ? 'true' : 'false'; ?>,
          showTranslatorKey: false
      }">
    <input type="hidden" name="LgID" value="<?php echo $language->id; ?>" />

    <div class="box">
        <!-- Study Language -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label" for="LgName">Study Language "L2"</label>
            </div>
            <div class="field-body">
                <div class="field has-addons">
                    <div class="control is-expanded">
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
                    <div class="control">
                        <span class="icon has-text-danger" title="Field must not be empty">
                            <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dictionary 1 URI -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Dictionary 1 URI</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <div class="control has-icons-right">
                        <input type="url"
                               class="input notempty checkdicturl checkoutsidebmp"
                               name="LgDict1URI"
                               value="<?php echo htmlspecialchars($language->dict1uri ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               maxlength="200"
                               data_info="Dictionary 1 URI"
                               required />
                        <span class="icon is-right has-text-danger">
                            <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                        </span>
                    </div>
                    <label class="checkbox mt-2">
                        <input type="checkbox" name="LgDict1PopUp" id="LgDict1PopUp" />
                        <span class="has-text-grey-dark" title="Open in a new window. Some dictionaries cannot be displayed in iframes">
                            Open in Pop-Up
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Dictionary 2 URI -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Dictionary 2 URI</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <div class="control">
                        <input type="url"
                               class="input checkdicturl checkoutsidebmp"
                               name="LgDict2URI"
                               value="<?php echo htmlspecialchars($language->dict2uri ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               maxlength="200"
                               data_info="Dictionary 2 URI" />
                    </div>
                    <label class="checkbox mt-2">
                        <input type="checkbox" name="LgDict2PopUp" id="LgDict2PopUp" />
                        <span class="has-text-grey-dark" title="Open in a new window. Some dictionaries cannot be displayed in iframes">
                            Open in Pop-Up
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Sentence Translator URI -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Sentence Translator URI</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <div class="field has-addons">
                        <div class="control">
                            <div class="select">
                                <select name="LgTranslatorName"
                                        @change="showTranslatorKey = ($event.target.value === 'libretranslate')">
                                    <option value="google_translate">Google Translate (webpage)</option>
                                    <option value="libretranslate">LibreTranslate API</option>
                                    <option value="ggl">GoogleTranslate API</option>
                                    <option value="glosbe" class="is-hidden">Glosbe API</option>
                                </select>
                            </div>
                        </div>
                        <div class="control is-expanded">
                            <input type="url"
                                   class="input checkdicturl checkoutsidebmp"
                                   name="LgGoogleTranslateURI"
                                   value="<?php echo htmlspecialchars($language->translator ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                   maxlength="200"
                                   data_info="GoogleTranslate URI" />
                        </div>
                    </div>

                    <div class="field mt-2" x-show="showTranslatorKey" x-transition>
                        <label class="label is-small" for="LgTranslatorKey">API Key</label>
                        <div class="control">
                            <input type="text"
                                   class="input is-small"
                                   id="LgTranslatorKey"
                                   name="LgTranslatorKey" />
                        </div>
                    </div>

                    <label class="checkbox mt-2">
                        <input type="checkbox" name="LgGoogleTranslatePopUp" id="LgGoogleTranslatePopUp" />
                        <span class="has-text-grey-dark" title="Open in a new window. Some translators cannot be displayed in iframes">
                            Open in Pop-Up
                        </span>
                    </label>
                    <p id="translator_error" class="help is-danger"></p>
                </div>
            </div>
        </div>

        <!-- Text Size -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Text Size (%)</label>
            </div>
            <div class="field-body">
                <div class="field has-addons">
                    <div class="control">
                        <input name="LgTextSize"
                               type="number"
                               min="100"
                               max="250"
                               step="50"
                               class="input"
                               style="width: 100px;"
                               x-model="textSize"
                               value="<?php echo $language->textsize; ?>" />
                    </div>
                    <div class="control is-expanded">
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

        <!-- Character Substitutions -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Character Substitutions</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <div class="control">
                        <input type="text"
                               class="input checkoutsidebmp"
                               data_info="Character Substitutions"
                               name="LgCharacterSubstitutions"
                               value="<?php echo htmlspecialchars($language->charactersubst ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               maxlength="500" />
                    </div>
                </div>
            </div>
        </div>

        <!-- RegExp Split Sentences -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">RegExp Split Sentences</label>
            </div>
            <div class="field-body">
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <input type="text"
                               class="input notempty checkoutsidebmp"
                               name="LgRegexpSplitSentences"
                               value="<?php echo htmlspecialchars($language->regexpsplitsent ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               maxlength="500"
                               data_info="RegExp Split Sentences"
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

        <!-- Exceptions Split Sentences -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Exceptions Split Sentences</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <div class="control">
                        <input type="text"
                               class="input checkoutsidebmp"
                               data_info="Exceptions Split Sentences"
                               name="LgExceptionsSplitSentences"
                               value="<?php echo htmlspecialchars($language->exceptionsplitsent ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               maxlength="500" />
                    </div>
                </div>
            </div>
        </div>

        <!-- RegExp Word Characters -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">RegExp Word Characters</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <div class="field has-addons">
                        <div class="control" x-show="showJapaneseOptions" x-transition>
                            <div class="select">
                                <select name="LgRegexpAlt">
                                    <option value="regexp">Regular Expressions (demo)</option>
                                    <option value="mecab">MeCab (recommended)</option>
                                </select>
                            </div>
                        </div>
                        <div class="control is-expanded">
                            <input type="text"
                                   class="input notempty checkoutsidebmp"
                                   data_info="RegExp Word Characters"
                                   name="LgRegexpWordCharacters"
                                   value="<?php echo htmlspecialchars($language->regexpwordchar ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                   maxlength="500"
                                   required />
                        </div>
                        <div class="control">
                            <span class="icon has-text-danger" title="Field must not be empty">
                                <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                            </span>
                        </div>
                    </div>
                    <p class="help is-danger is-hidden" id="mecab_not_installed">
                        <a href="https://en.wikipedia.org/wiki/MeCab">MeCab</a> does
                        not seem to be installed on your server.
                        Please read the <a href="">MeCab installation guide</a>.
                    </p>
                </div>
            </div>
        </div>

        <!-- Checkbox options -->
        <div class="field is-horizontal">
            <div class="field-label">
                <label class="label">Script Options</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <label class="checkbox">
                        <input type="checkbox"
                               name="LgSplitEachChar"
                               id="LgSplitEachChar"
                               value="1"
                               <?php echo $language->spliteachchar ? "checked" : ""; ?> />
                        <strong>Make each character a word</strong>
                        <span class="has-text-grey">(e.g. for Chinese, Japanese, etc.)</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="field is-horizontal">
            <div class="field-label"></div>
            <div class="field-body">
                <div class="field">
                    <label class="checkbox">
                        <input type="checkbox"
                               name="LgRemoveSpaces"
                               id="LgRemoveSpaces"
                               value="1"
                               <?php echo $language->removespaces ? "checked" : ""; ?> />
                        <strong>Remove spaces</strong>
                        <span class="has-text-grey">(e.g. for Chinese, Japanese, etc.)</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="field is-horizontal">
            <div class="field-label"></div>
            <div class="field-body">
                <div class="field">
                    <label class="checkbox">
                        <input type="checkbox"
                               name="LgRightToLeft"
                               id="LgRightToLeft"
                               value="1"
                               <?php echo $language->rightoleft ? "checked" : ""; ?> />
                        <strong>Right-To-Left Script</strong>
                        <span class="has-text-grey">(e.g. for Arabic, Hebrew, Farsi, Urdu, etc.)</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="field is-horizontal">
            <div class="field-label"></div>
            <div class="field-body">
                <div class="field">
                    <label class="checkbox">
                        <input type="checkbox"
                               name="LgShowRomanization"
                               id="LgShowRomanization"
                               value="1"
                               <?php echo $language->showromanization ? "checked" : ""; ?> />
                        <strong>Show Romanization</strong>
                        <span class="has-text-grey">
                            Show/Hide <a href="https://en.wikipedia.org/wiki/Romanization">romanization</a> field.
                            Recommended for difficult writing systems (e.g.: Chinese, Japanese...)
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Export Template -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">
                    Export Template
                    <span class="icon is-small click" data-action="show-export-template-help" title="Help">
                        <?php echo IconHelper::render('help-circle', ['alt' => 'Help']); ?>
                    </span>
                </label>
            </div>
            <div class="field-body">
                <div class="field">
                    <div class="control">
                        <input type="text"
                               class="input checkoutsidebmp"
                               data_info="Export Template"
                               name="LgExportTemplate"
                               value="<?php echo htmlspecialchars($language->exporttemplate ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               maxlength="1000" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Third-Party Text-to-Speech Voice API -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Third-Party Text-to-Speech Voice API</label>
            </div>
            <div class="field-body">
                <div class="field">
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
                                  rows="6"
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
                </div>
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
