<?php

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

?>
<script type="application/json" id="language-form-config">
<?php echo json_encode([
    'languageId' => $language->id,
    'languageName' => $language->name,
    'sourceLg' => $sourceLg,
    'targetLg' => $targetLg,
    'languageDefs' => \Lwt\Core\LanguageDefinitions::getAll(),
    'allLanguages' => $allLanguages ?? []
]); ?>
</script>
<form class="validate" action="/languages"
    method="post" name="lg_form">
    <input type="hidden" name="LgID" value="<?php echo $language->id; ?>" />
    <table class="tab1" cellspacing="0" cellpadding="5">
    <tr>
        <td class="td1 right">Study Language "L2":</td>
        <td class="td1">
            <input type="text" class="notempty setfocus checkoutsidebmp respinput"
            data_info="Study Language" name="LgName" id="LgName"
            value="<?php echo tohtml($language->name); ?>" maxlength="40" />
            <img src="/assets/icons/status-busy.png" title="Field must not be empty"
            alt="Field must not be empty" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">Dictionary 1 URI:</td>
        <td class="td1">
            <input type="url" class="notempty checkdicturl checkoutsidebmp respinput"
            name="LgDict1URI"
            value="<?php echo tohtml($language->dict1uri); ?>"
            maxlength="200" data_info="Dictionary 1 URI" />

            <br />
            <input type="checkbox" name="LgDict1PopUp" id="LgDict1PopUp" />

            <label for="LgDict1PopUp"
            title="Open in a new window. Some dictionaries cannot be displayed in iframes">
                Open in Pop-Up
            </label>
            <img src="/assets/icons/status-busy.png" title="Field must not be empty"
            alt="Field must not be empty" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">Dictionary 2 URI:</td>
        <td class="td1">
            <input type="url" class="checkdicturl checkoutsidebmp respinput"
            name="LgDict2URI"
            value="<?php echo tohtml($language->dict2uri); ?>" maxlength="200"
            data_info="Dictionary 2 URI" />

            <br />
            <input type="checkbox" name="LgDict2PopUp" id="LgDict2PopUp" />

            <label for="LgDict2PopUp"
            title="Open in a new window. Some dictionaries cannot be displayed in iframes">
                Open in Pop-Up
            </label>
        </td>
    </tr>
    <tr>
        <td class="td1 right">Sentence Translator URI:</td>
        <td class="td1">
            <select name="LgTranslatorName">
                <option value="google_translate">Google Translate (webpage)</option>
                <option value="libretranslate">LibreTranslate API</option>
                <option value="ggl">
                    GoogleTranslate API
                </option>
                <option value="glosbe" class="language-option-hidden">
                    Glosbe API
                </option>
            </select>
            <input type="url" class="checkdicturl checkoutsidebmp respinput"
            name="LgGoogleTranslateURI"
            value="<?php echo tohtml($language->translator); ?>"
            maxlength="200" data_info="GoogleTranslate URI" />

            <div id="LgTranslatorKeyWrapper" class="language-form-hidden">
                <label for="LgTranslatorKey">Key :</label>
                <input type="text" id="LgTranslatorKey" name="LgTranslatorKey"/>
            </div>
            <br />
            <input type="checkbox" name="LgGoogleTranslatePopUp"
            id="LgGoogleTranslatePopUp" />
            <label for="LgGoogleTranslatePopUp"
            title="Open in a new window. Some translators cannot be displayed in iframes">
                Open in Pop-Up
            </label>
            <div id="translator_error" class="red" ></div>
        </td>
    </tr>
    <tr>
        <td class="td1 right">Text Size (%):</td>
        <td class="td1">
            <input name="LgTextSize" defaultValue="100" type="number" min="100" max="250"
            value="<?php echo $language->textsize; ?>" step="50"
            class="respinput" />
            <input type="text" class="respinput"
            style="font-size: <?php echo $language->textsize ?>%;"
            id="LgTextSizeExample"
            value="Text will be this size" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">Character Substitutions:</td>
        <td class="td1">
            <input type="text" class="checkoutsidebmp respinput"
            data_info="Character Substitutions" name="LgCharacterSubstitutions"
            value="<?php echo tohtml($language->charactersubst); ?>"
            maxlength="500" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">RegExp Split Sentences:</td>
        <td class="td1">
            <input type="text" class="notempty checkoutsidebmp respinput"
            name="LgRegexpSplitSentences"
            value="<?php echo tohtml($language->regexpsplitsent); ?>"
            maxlength="500"
            data_info="RegExp Split Sentences" />
            <img src="/assets/icons/status-busy.png" title="Field must not be empty"
            alt="Field must not be empty" />
        </td>
    </tr>
    <tr>
    <td class="td1 right">Exceptions Split Sentences:</td>
    <td class="td1">
        <input type="text" class="checkoutsidebmp respinput"
        data_info="Exceptions Split Sentences"
        name="LgExceptionsSplitSentences"
        value="<?php echo tohtml($language->exceptionsplitsent); ?>"
        maxlength="500" />
    </td>
    </tr>
    <tr>
        <td class="td1 right">RegExp Word Characters:</td>
        <td class="td1">
            <select class="language-form-hidden" name="LgRegexpAlt">
                <option value="regexp">Regular Expressions (demo)</option>
                <option value="mecab">MeCab (recommended)</option>
            </select>
            <input type="text" class="notempty checkoutsidebmp respinput"
            data_info="RegExp Word Characters" name="LgRegexpWordCharacters"
            value="<?php echo tohtml($language->regexpwordchar); ?>"
            maxlength="500" />
            <img src="/assets/icons/status-busy.png" title="Field must not be empty"
            alt="Field must not be empty" />
            <div class="red language-form-hidden" id="mecab_not_installed">
                <a href="https://en.wikipedia.org/wiki/MeCab">MeCab</a> does
                not seem to be installed on your server.
                Please read the <a href="">MeCab installation guide</a>.
            </div>
        </td>
    </tr>
    <tr>
        <td class="td1 right">Make each character a word:</td>
        <td class="td1">
            <input type="checkbox" name="LgSplitEachChar" id="LgSplitEachChar"
            value="1" <?php echo $language->spliteachchar ? "checked" : ""; ?> />
            <label for="LgSplitEachChar">(e.g. for Chinese, Japanese, etc.)</label>
        </td>
    </tr>
    <tr>
        <td class="td1 right">Remove spaces:</td>
        <td class="td1">
            <input type="checkbox" name="LgRemoveSpaces" id="LgRemoveSpaces"
            value="1" <?php echo $language->removespaces ? "checked" : ""; ?> />
            <label for="LgRemoveSpaces">(e.g. for Chinese, Japanese, etc.)</label>
        </td>
    </tr>
    <tr>
        <td class="td1 right">Right-To-Left Script:</td>
        <td class="td1">
            <input type="checkbox" name="LgRightToLeft" id="LgRightToLeft"
            value="1" <?php echo $language->rightoleft ? "checked" : ""; ?> />
            <label for="LgRightToLeft">
                (e.g. for Arabic, Hebrew, Farsi, Urdu,  etc.)
            </label>
        </td>
    </tr>
    <tr>
        <td class="td1 right">Show Romanization:</td>
        <td class="td1">
            <input type="checkbox" name="LgShowRomanization" id="LgShowRomanization"
            value="1" <?php echo $language->showromanization ? "checked" : ""; ?> />
            <label for="LgShowRomanization">
                Show/Hide <a href="https://en.wikipedia.org/wiki/Romanization">romanization</a> field.
                Recommended for difficult writing systems (e. g.: Chinese, Japanese...)
            </label>
        </td>
    </tr>
    <tr>
        <td class="td1 right">
            Export Template
            <img class="click" src="/assets/icons/question-frame.png" title="Help" alt="Help"
            data-action="show-export-template-help" /> :
        </td>
        <td class="td1">
            <input type="text" class="checkoutsidebmp respinput" data_info="Export Template"
            name="LgExportTemplate"
            value="<?php echo tohtml($language->exporttemplate); ?>"
            maxlength="1000" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">
            Third-Party Text-to-Speech Voice API
        </td>
        <td class="td1">
            <input type="text" class="respinput" name="LgVoiceAPIDemo"
            title="Input any text you want to read." value="Read this demo text."  />
            <textarea class="checkoutsidebmp respinput"
            data_info="Third-Party Text-to-Speech API"
            name="LgTTSVoiceAPI"
            maxlength="2048" rows="10"
            ><?php echo tohtml($language->ttsvoiceapi); ?></textarea>
            <hr class="language-form-separator" />

            <input type="button" data-action="check-voice-api"
            value="Check Voice API Errors"/>
            <input type="button" data-action="test-voice-api"
            value="Test!"/>
            <p hidden class="error" id="voice-api-message-zone"></p>
        </td>
    </tr>
    <tr>
        <td class="td1 right" colspan="2">
            <input type="button" value="Cancel" data-action="cancel-form"
            data-redirect="/languages" />
            <?php if ($isNew): ?>
            <input type="submit" name="op" value="Save" />
            <?php else: ?>
            <input type="submit" name="op" value="Change" />
            <?php endif; ?>
        </td>
    </tr>
    </table>
</form>
