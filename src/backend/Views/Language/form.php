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
<script type="text/javascript">
    const edit_languages_js = {
        reloadDictURLs: function(sourceLg='auto', targetLg='en') {
            let base_url = window.location.href;
            base_url = base_url.substring(0, base_url.lastIndexOf('/'));

            GGTRANSLATE = 'https://translate.google.com/?' + $.param({
                    ie: "UTF-8",
                    sl: sourceLg,
                    tl: targetLg,
                    text: 'lwt_term'
            });

            LIBRETRANSLATE = 'http://localhost:5000/?' + $.param({
                lwt_translator: 'libretranslate',
                source: sourceLg,
                target: targetLg,
                q: "lwt_term"
            });

            GGL = base_url + '/ggl.php/?' + $.param({
                sl: sourceLg, tl: targetLg, text: 'lwt_term'
            });
        },

        checkLanguageChanged: function(value) {
            if (value == "Japanese") {
                $(document.forms.lg_form.LgRegexpAlt).css("display", "block");
            } else {
                $(document.forms.lg_form.LgRegexpAlt).css("display", "none");
            }
        },

        multiWordsTranslateChange: function(value) {
            let result;
            let uses_key = false;
            let base_url = window.location.href;
            base_url = base_url.replace('//languages', '/');
            switch (value) {
            case "google_translate":
                result = GGTRANSLATE;
                break;
            case "libretranslate":
                result = LIBRETRANSLATE;
                uses_key = true;
                break;
            case "ggl":
                result = GGL;
                break;
            case "glosbe":
                result = base_url + "glosbe.php";
                break;
            }
            if (result) {
                document.forms.lg_form.LgGoogleTranslateURI.value = result;
            }
            $('#LgTranslatorKeyWrapper')
            .css("display", uses_key ? "inherit" : "none");
        },

        displayLibreTranslateError: function(error) {
            $('#translator_status')
            .html('<a href="https://libretranslate.com/">LibreTranslate</a> server seems to be unreachable.' +
            'You can install it on your server with the <a href="">LibreTranslate installation guide</a>.' +
            'Error: ' + error);
        },

        checkTranslatorStatus(url) {
            if (url.startsWith('*')) {
            url = url.substring(1);
            }
            const url_obj = new URL(url);
            const params = url_obj.searchParams;
            if (params.get('lwt_translator') == 'libretranslate') {
            try {
                this.checkLibreTranslateStatus(url_obj, { key: params.key });
            } catch (error) {
                this.displayLibreTranslateError(error);
            }
            }
        },

        checkLibreTranslateStatus(url, key = "") {
            const trans_url = new URL(url);
            trans_url.searchParams.append('lwt_key', key);
            getLibreTranslateTranslation(trans_url, 'ping', 'en', 'es')
            .then(
                function (translation) {
                if (typeof translation === "string") {
                    $('#translator_status')
                    .html('<a href="https://libretranslate.com/">LibreTranslate</a> online!')
                    .attr('class', 'msgblue');
                }
                },
                this.displayLibreTranslateError
            );
        },

        changeLanguageTextSize(value) {
            $('#LgTextSizeExample').css("font-size", value + "%");
        },

        wordCharChange(value) {
            const regex = LANGDEFS[<?php echo json_encode($language->name); ?>][3];
            const mecab = "mecab";

            let result;
            switch (value) {
            case "regexp":
                result = regex;
                break;
            case "mecab":
                result = mecab;
                break;
            }
            if (result) {
            document.forms.lg_form.LgRegexpWordCharacters.value = result;
            }
        },

        addPopUpOption: function(url, checked) {
            if (url.startsWith('*')) {
                url = url.substring(1);
            }
            const built_url = new URL(url);
            if (checked && built_url.searchParams.has('lwt_popup'))
                return built_url.href;
            if (!checked && !built_url.searchParams.has('lwt_popup'))
                return built_url.href;
            if (checked) {
                built_url.searchParams.append('lwt_popup', 'true');
                return built_url.href;
            }
            built_url.searchParams.delete('lwt_popup');
            return built_url.href;
        },

        changePopUpState: function (elem) {
            const l_form = document.forms.lg_form;
            let target;
            switch (elem.name) {
                case "LgDict1PopUp":
                    target = l_form.LgDict1URI;
                    break;
                case "LgDict2PopUp":
                    target = l_form.LgDict2URI;
                    break;
                case "LgGoogleTranslatePopUp":
                    target = l_form.LgGoogleTranslateURI;
                    break;
            }
            target.value = addPopUpOption(target.value, elem.checked);
        },

        checkDictionaryChanged: function(input_box) {
            const l_form = document.forms.lg_form;
            if (input_box.value == '')
                return;
            switch (input_box.name) {
                case "LgDict1URI":
                    target = l_form.LgDict1PopUp;
                    break;
                case "LgDict2URI":
                    target = l_form.LgDict2PopUp;
                    break;
                case "LgGoogleTranslateURI":
                    target = l_form.LgGoogleTranslatePopUp;
                    break;
            }
            let popup = false;
            if (input_box.value.startsWith('*')) {
                input_box.value = input_box.value.substring(1);
                popup = true;
            }
            popup = popup || (new URL(input_box.value)).searchParams.has("lwt_popup");
            target.checked = popup;
        },

        checkTranslatorType: function (url, type_select) {
            const parsed_url = new URL(url);
            let final_value;
            switch (parsed_url.searchParams.get("lwt_translator")) {
                case "libretranslate":
                    final_value = "libretranslate";
                    break;
                default:
                    final_value = "google_translate";
                    break;
            }
            type_select.value = final_value;
        },

        checkWordChar: function (method) {
            const method_option = (method == "mecab") ? "mecab" : "regexp";
            document.forms.lg_form.LgRegexpAlt.value = method_option;
        },

        checkVoiceAPI: function (api_value) {
            message_field = $('#voice-api-message-zone');
            if (api_value == "") {
                message_field.hide();
                return;
            }
            if (!api_value.includes("lwt_term")) {
                message_field.text('"lwt_term" is missing!')
                message_field.show();
                return false
            }
            let query;
            try {
                query = JSON.parse(api_value);
            } catch (error) {
                message_field.text("Cannot parse as JSON! " +  error)
                message_field.show();
                return false;
            }
            if (deepFindValue(query, "lwt_term") === null) {
                message_field.text("Cannot find 'lwt_term' in JSON!")
                message_field.show();
                return false;
            }
            message_field.hide();
            return true;
        },

        testVoiceAPI: function () {
            const api_value = document.forms.lg_form.LgTTSVoiceAPI.value;
            const term = document.forms.lg_form.LgVoiceAPIDemo.value;
            const lang = <?php echo json_encode($language->name); ?>;
            readTextWithExternal(term, api_value, lang);
        },

        fullFormCheck: function () {
            checkLanguageForm(document.forms.lg_form);
        }
    }

    function reloadDictURLs(sourceLg='auto', targetLg='en') {
        return edit_languages_js.reloadDictURLs(sourceLg, targetLg)
    }

    edit_languages_js.reloadDictURLs(
        <?php echo json_encode($sourceLg); ?>,
        <?php echo json_encode($targetLg); ?>
    );

    function checkLanguageChanged(value) {
        return edit_languages_js.checkLanguageChanged(value);
    }

    function multiWordsTranslateChange(value) {
        return edit_languages_js.multiWordsTranslateChange(value);
    }

    function checkTranslatorStatus(url) {
        return edit_languages_js.checkTranslatorStatus(url);
    }

    function checkLibreTranslateStatus(url, key="") {
        return edit_languages_js.checkLibreTranslateStatus(url, key);
    }

    function changeLanguageTextSize(value) {
        return edit_languages_js.changeLanguageTextSize(value);
    }

    function wordCharChange(value) {
        return edit_languages_js.wordCharChange(value);
    }

    function addPopUpOption(url, checked) {
        return edit_languages_js.addPopUpOption(url, checked);
    }

    function changePopUpState(elem) {
        return edit_languages_js.changePopUpState(elem);
    }

    function checkDictionaryChanged(input_box) {
        return edit_languages_js.checkDictionaryChanged(input_box);
    }

    function checkTranslatorType(url, type_select) {
        return edit_languages_js.checkTranslatorType(url, type_select);
    }

    function checkTranslatorChanged(translator_input) {
        edit_languages_js.checkTranslatorStatus(translator_input.value);
        edit_languages_js.checkDictionaryChanged(translator_input);
        edit_languages_js.checkTranslatorType(
            translator_input.value, document.forms.lg_form.LgTranslatorName
        );
    }

    function checkWordChar(method) {
        return edit_languages_js.checkWordChar(method);
    }

    function checkVoiceAPI(api_value) {
        return edit_languages_js.checkVoiceAPI(api_value);
    }

    function checkLanguageForm(l_form) {
        edit_languages_js.checkLanguageChanged(l_form.LgName.value);
        edit_languages_js.checkDictionaryChanged(l_form.LgDict1URI);
        edit_languages_js.checkDictionaryChanged(l_form.LgDict2URI);
        checkTranslatorChanged(l_form.LgGoogleTranslateURI);
        edit_languages_js.checkWordChar(l_form.LgRegexpWordCharacters.value);
    }

    $(edit_languages_js.fullFormCheck);
</script>
<form class="validate" action="/languages"
    method="post" onsubmit="return check_dupl_lang(<?php echo $language->id; ?>);"
    name="lg_form">
    <input type="hidden" name="LgID" value="<?php echo $language->id; ?>" />
    <table class="tab1" cellspacing="0" cellpadding="5">
    <tr>
        <td class="td1 right">Study Language "L2":</td>
        <td class="td1">
            <input type="text" class="notempty setfocus checkoutsidebmp respinput"
            data_info="Study Language" name="LgName" id="LgName"
            value="<?php echo tohtml($language->name); ?>" maxlength="40"
            oninput="checkLanguageChanged(this.value);" />
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
            maxlength="200" data_info="Dictionary 1 URI"
            oninput="checkDictionaryChanged(this);" />

            <br />
            <input type="checkbox" name="LgDict1PopUp" id="LgDict1PopUp"
            onchange="changePopUpState(this);" />

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
            data_info="Dictionary 2 URI"
            oninput="checkDictionaryChanged(this);" />

            <br />
            <input type="checkbox" name="LgDict2PopUp" id="LgDict2PopUp"
            onchange="changePopUpState(this);" />

            <label for="LgDict2PopUp"
            title="Open in a new window. Some dictionaries cannot be displayed in iframes">
                Open in Pop-Up
            </label>
        </td>
    </tr>
    <tr>
        <td class="td1 right">Sentence Translator URI:</td>
        <td class="td1">
            <select onchange="multiWordsTranslateChange(this.value);"
            name="LgTranslatorName">
                <option value="google_translate">Google Translate (webpage)</option>
                <option value="libretranslate">LibreTranslate API</option>
                <option value="ggl">
                    GoogleTranslate API
                </option>
                <option value="glosbe" style="display: none;">
                    Glosbe API
                </option>
            </select>
            <input type="url" class="checkdicturl checkoutsidebmp respinput"
            name="LgGoogleTranslateURI"
            value="<?php echo tohtml($language->translator); ?>"
            maxlength="200" data_info="GoogleTranslate URI"
            oninput="checkTranslatorChanged(this);" class="respinput"
             />

            <div id="LgTranslatorKeyWrapper" style="display: none;">
                <label for="LgTranslatorKey">Key :</label>
                <input type="text" id="LgTranslatorKey" name="LgTranslatorKey"/>
            </div>
            <br />
            <input type="checkbox" name="LgGoogleTranslatePopUp"
            id="LgGoogleTranslatePopUp"
            onchange="edit_languages_js.changePopUpState(this);"/>
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
            onchange="edit_languages_js.changeLanguageTextSize(this.value);"
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
            <select onchange="wordCharChange(this.value);" style="display: none;"
            name="LgRegexpAlt">
                <option value="regexp">Regular Expressions (demo)</option>
                <option value="mecab">MeCab (recommended)</option>
            </select>
            <input type="text" class="notempty checkoutsidebmp respinput"
            data_info="RegExp Word Characters" name="LgRegexpWordCharacters"
            value="<?php echo tohtml($language->regexpwordchar); ?>"
            maxlength="500" />
            <img src="/assets/icons/status-busy.png" title="Field must not be empty"
            alt="Field must not be empty" />
            <div style="display: none;" class="red" id="mecab_not_installed">
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
            onclick="oewin('export_template.html');" /> :
        </td>
        <td class="td1">
            <input type="text" class="checkoutsidebmp" data_info="Export Template"
            name="LgExportTemplate" class="respinput"
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
            name="LgTTSVoiceAPI" class="respinput"
            value="<?php echo tohtml($language->ttsvoiceapi); ?>"
            maxlength="2048" rows="10"
            onchange="edit_languages_js.checkVoiceAPI(this.value);"
            ><?php echo tohtml($language->ttsvoiceapi); ?></textarea>
            <hr style="color: transparent;" />

            <input type="button"
            onclick="edit_languages_js.checkVoiceAPI(document.forms.lg_form.LgTTSVoiceAPI.value);"
            value="Check Voice API Errors"/>
            <input type="button" onclick="edit_languages_js.testVoiceAPI();"
            value="Test!"/>
            <p hidden class="error" id="voice-api-message-zone"></p>
        </td>
    </tr>
    <tr>
        <td class="td1 right" colspan="2">
            <input type="button" value="Cancel"
            onclick="{lwtFormCheck.resetDirty(); location.href='/languages';}" />
            <?php if ($isNew): ?>
            <input type="submit" name="op" value="Save" />
            <?php else: ?>
            <input type="submit" name="op" value="Change" />
            <?php endif; ?>
        </td>
    </tr>
    </table>
</form>
