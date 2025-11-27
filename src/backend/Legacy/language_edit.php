<?php

/**
 * \file
 * \brief Manage languages
 *
 * Call: /languages?....
 *      ... refresh=[langid] ... reparse all texts in lang
 *      ... del=[langid] ... do delete
 *      ... op=Save ... do insert new
 *      ... op=Change ... do update
 *      ... new=1 ... display new lang. screen
 *      ... chg=[langid] ... display edit screen
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/edit-languages.html
 * @since    1.0.3
 * @since    2.4.0 Refactored with functional paradigm
 */

require_once 'Core/Bootstrap/db_bootstrap.php';
require_once 'Core/UI/ui_helpers.php';
require_once 'Core/Http/param_helpers.php';
require_once 'Core/Language/language_utilities.php';
require_once 'Core/Language/langdefs.php';
require_once 'Core/Entity/Language.php';

use Lwt\Classes\Language;
use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Database\Maintenance;
use Lwt\Database\Settings;
use Lwt\Database\TextParsing;


/**
 * Prepare a JavaScript code that checks for duplicate names in languages.
 *
 * @return void
 */
function edit_languages_alert_duplicate()
{

    ?>

<script type="text/javascript">
    //<![CDATA[
    var LANGUAGES = <?php echo json_encode(get_languages()); ?>;

    /// Check if langname exists and its lang# != curr
    function check_dupl_lang(curr) {
        const l = $('#LgName').val();
        if (l in LANGUAGES) {
            if (curr != LANGUAGES[l]) {
                alert('Language "' + l +
                '" already exists. Please change the language name!');
                $('#LgName').focus();
                return false;
            }
        }
        return true;
    }

    //]]>
</script>

    <?php
}


/**
 * Refresh sentence and text items from a specific language.
 *
 * @param int $lid Language ID
 *
 * @return string Number of sentences and textitems refreshed
 *
 * @global string $tbpref Database table prefix
 */
function edit_languages_refresh($lid): string
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $message2 = Connection::execute(
        "DELETE FROM {$tbpref}sentences where SeLgID = $lid",
        "Sentences deleted"
    );
    $message3 = Connection::execute(
        "DELETE FROM {$tbpref}textitems2 where Ti2LgID = $lid",
        "Text items deleted"
    );
    Maintenance::adjustAutoIncrement('sentences', 'SeID');
    $sql = "select TxID, TxText from " . $tbpref . "texts
    where TxLgID = " . $lid . "
    order by TxID";
    $res = Connection::query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        $txtid = (int)$record["TxID"];
        $txttxt = $record["TxText"];
        TextParsing::splitCheck($txttxt, $lid, $txtid);
    }
    mysqli_free_result($res);
    $message = $message2 .
    " / " . $message3 .
    " / Sentences added: " . Connection::fetchValue(
        "SELECT count(*) as value
        FROM {$tbpref}sentences
        where SeLgID = $lid"
    ) .
    " / Text items added: " . Connection::fetchValue(
        "SELECT count(*) as value
        FROM {$tbpref}textitems2
        where Ti2LgID = $lid"
    );
    return $message;
}



/**
 * Delete a language.
 *
 * @param int $lid Language ID
 *
 * @return int|string Info on the number of languages deleted
 *
 * @global string $tbpref Database table prefix
 *
 * @psalm-return int<-1, max>|string
 */
function edit_languages_delete($lid): int|string
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $anztexts = Connection::fetchValue(
        "SELECT count(TxID) as value
        FROM {$tbpref}texts
        where TxLgID = $lid"
    );
    $anzarchtexts = Connection::fetchValue(
        "SELECT count(AtID) as value
        FROM {$tbpref}archivedtexts
        where AtLgID = $lid"
    );
    $anzwords = Connection::fetchValue(
        "SELECT count(WoID) as value
        FROM {$tbpref}words
        where WoLgID = $lid"
    );
    $anzfeeds = Connection::fetchValue(
        "SELECT count(NfID) as value
        FROM {$tbpref}newsfeeds
        where NfLgID = $lid"
    );
    if ($anztexts > 0 || $anzarchtexts > 0 || $anzwords > 0 || $anzfeeds > 0) {
        $message = 'You must first delete texts, archived texts, newsfeeds and words with this language!';
    } else {
        $message = Connection::execute(
            "DELETE FROM {$tbpref}languages
            WHERE LgID = $lid",
            "Deleted"
        );
    }
    return $message;
}


/**
 * Save a new language to the database.
 *
 * @return int|numeric-string Success or error message
 *
 * @global string $tbpref Database table prefix
 *
 * @psalm-return int<-1, max>|numeric-string
 */
function edit_languages_op_save(): int|string
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $val = Connection::fetchValue(
        "SELECT MIN(LgID) AS value FROM {$tbpref}languages WHERE LgName=''"
    );
    if (!isset($val)) {
        $message = Connection::execute(
            "INSERT INTO {$tbpref}languages (
                LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI,
                LgExportTemplate, LgTextSize, LgCharacterSubstitutions,
                LgRegexpSplitSentences, LgExceptionsSplitSentences,
                LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar,
                LgRightToLeft, LgTTSVoiceAPI, LgShowRomanization
            ) VALUES(" .
                Escaping::toSqlSyntax($_REQUEST["LgName"]) . ', ' .
                Escaping::toSqlSyntax($_REQUEST["LgDict1URI"]) . ', ' .
                Escaping::toSqlSyntax($_REQUEST["LgDict2URI"]) . ', ' .
                Escaping::toSqlSyntax($_REQUEST["LgGoogleTranslateURI"]) . ', ' .
                Escaping::toSqlSyntax($_REQUEST["LgExportTemplate"]) . ', ' .
                Escaping::toSqlSyntax($_REQUEST["LgTextSize"]) . ', ' .
                Escaping::toSqlSyntaxNoTrimNoNull($_REQUEST["LgCharacterSubstitutions"]) . ', ' .
                Escaping::toSqlSyntax($_REQUEST["LgRegexpSplitSentences"]) . ', ' .
                Escaping::toSqlSyntaxNoTrimNoNull($_REQUEST["LgExceptionsSplitSentences"]) . ', ' .
                Escaping::toSqlSyntax($_REQUEST["LgRegexpWordCharacters"]) . ', ' .
                ((int)isset($_REQUEST["LgRemoveSpaces"])) . ', ' .
                ((int)isset($_REQUEST["LgSplitEachChar"])) . ', ' .
                ((int)isset($_REQUEST["LgRightToLeft"])) . ', ' .
                Escaping::toSqlSyntaxNoNull($_REQUEST["LgTTSVoiceAPI"]) . ', ' .
                ((int)isset($_REQUEST["LgShowRomanization"])) .
            ')',
            'Saved'
        );
    } else {
        $message = Connection::execute(
            "UPDATE {$tbpref}languages SET " .
            'LgName = ' . Escaping::toSqlSyntax($_REQUEST["LgName"]) . ', ' .
            'LgDict1URI = ' . Escaping::toSqlSyntax($_REQUEST["LgDict1URI"]) . ', ' .
            'LgDict2URI = ' . Escaping::toSqlSyntax($_REQUEST["LgDict2URI"]) . ', ' .
            'LgGoogleTranslateURI = ' . Escaping::toSqlSyntax($_REQUEST["LgGoogleTranslateURI"]) . ', ' .
            'LgExportTemplate = ' . Escaping::toSqlSyntax($_REQUEST["LgExportTemplate"]) . ', ' .
            'LgTextSize = ' . Escaping::toSqlSyntax($_REQUEST["LgTextSize"]) . ', ' .
            'LgCharacterSubstitutions = ' . Escaping::toSqlSyntaxNoTrimNoNull($_REQUEST["LgCharacterSubstitutions"]) . ', ' .
            'LgRegexpSplitSentences = ' . Escaping::toSqlSyntax($_REQUEST["LgRegexpSplitSentences"]) . ', ' .
            'LgExceptionsSplitSentences = ' . Escaping::toSqlSyntaxNoTrimNoNull($_REQUEST["LgExceptionsSplitSentences"]) . ', ' .
            'LgRegexpWordCharacters = ' . Escaping::toSqlSyntax($_REQUEST["LgRegexpWordCharacters"]) . ', ' .
            'LgRemoveSpaces = ' . ((int)isset($_REQUEST["LgRemoveSpaces"])) . ', ' .
            'LgSplitEachChar = ' . ((int)isset($_REQUEST["LgSplitEachChar"])) . ', ' .
            'LgRightToLeft = ' . ((int)isset($_REQUEST["LgRightToLeft"])) . ', ' .
            "LgTTSVoiceAPI = " . Escaping::toSqlSyntaxNoNull($_REQUEST["LgTTSVoiceAPI"]) . ', ' .
            "LgShowRomanization = " . ((int)isset($_REQUEST["LgShowRomanization"])) .
            " WHERE LgID = $val",
            'Saved'
        );
    }
    return $message;
}

/**
 * Edit an existing text in the database.
 *
 * @param int $lid Language ID
 *
 * @return string Number of texts updated and items reparsed.
 *
 * @global string $tbpref Database table prefix
 */
function edit_languages_op_change($lid): string
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    // Get old values
    $sql = "SELECT * FROM {$tbpref}languages where LgID = $lid";
    $res = Connection::query($sql);
    $record = mysqli_fetch_assoc($res);
    if ($record == false) {
        my_die("Cannot access language data: $sql");
    }

    $needReParse = (
        Escaping::toSqlSyntaxNoTrimNoNull($_REQUEST["LgCharacterSubstitutions"])
        != Escaping::toSqlSyntaxNoTrimNoNull($record['LgCharacterSubstitutions'])
    ) || (
        Escaping::toSqlSyntax($_REQUEST["LgRegexpSplitSentences"]) !=
        Escaping::toSqlSyntax($record['LgRegexpSplitSentences'])
    ) || (
        Escaping::toSqlSyntaxNoTrimNoNull($_REQUEST["LgExceptionsSplitSentences"])
        != Escaping::toSqlSyntaxNoTrimNoNull($record['LgExceptionsSplitSentences'])
    ) || (
        Escaping::toSqlSyntax($_REQUEST["LgRegexpWordCharacters"]) !=
        Escaping::toSqlSyntax($record['LgRegexpWordCharacters'])
    ) || ((isset($_REQUEST["LgRemoveSpaces"]) ? 1 : 0) != $record['LgRemoveSpaces']) ||
    ((isset($_REQUEST["LgSplitEachChar"]) ? 1 : 0) != $record['LgSplitEachChar']);

    mysqli_free_result($res);


    $message = Connection::execute(
        "UPDATE {$tbpref}languages SET " .
        'LgName = ' . Escaping::toSqlSyntax($_REQUEST["LgName"]) . ', ' .
        'LgDict1URI = ' . Escaping::toSqlSyntax($_REQUEST["LgDict1URI"]) . ', ' .
        'LgDict2URI = ' . Escaping::toSqlSyntax($_REQUEST["LgDict2URI"]) . ', ' .
        'LgGoogleTranslateURI = ' . Escaping::toSqlSyntax($_REQUEST["LgGoogleTranslateURI"]) . ', ' .
        'LgExportTemplate = ' . Escaping::toSqlSyntax($_REQUEST["LgExportTemplate"]) . ', ' .
        'LgTextSize = ' . Escaping::toSqlSyntax($_REQUEST["LgTextSize"]) . ', ' .
        'LgCharacterSubstitutions = ' . Escaping::toSqlSyntaxNoTrimNoNull($_REQUEST["LgCharacterSubstitutions"]) . ', ' .
        'LgRegexpSplitSentences = ' . Escaping::toSqlSyntax($_REQUEST["LgRegexpSplitSentences"]) . ', ' .
        'LgExceptionsSplitSentences = ' . Escaping::toSqlSyntaxNoTrimNoNull($_REQUEST["LgExceptionsSplitSentences"]) . ', ' .
        'LgRegexpWordCharacters = ' . Escaping::toSqlSyntax($_REQUEST["LgRegexpWordCharacters"]) . ', ' .
        'LgRemoveSpaces = ' . ((int)isset($_REQUEST["LgRemoveSpaces"])) . ', ' .
        'LgSplitEachChar = ' . ((int)isset($_REQUEST["LgSplitEachChar"])) . ', ' .
        'LgRightToLeft = ' . ((int)isset($_REQUEST["LgRightToLeft"])) . ', ' .
        'LgTTSVoiceAPI = ' . Escaping::toSqlSyntaxNoNull($_REQUEST["LgTTSVoiceAPI"]) . ', ' .
        'LgShowRomanization = ' . ((int)isset($_REQUEST["LgShowRomanization"])) .
        " WHERE LgID = $lid",
        'Updated'
    );

    if ($needReParse) {
        Connection::execute(
            "DELETE FROM {$tbpref}sentences where SeLgID = $lid",
            "Sentences deleted"
        );
        Connection::execute(
            "DELETE FROM {$tbpref}textitems2 where Ti2LgID = $lid",
            "Text items deleted"
        );
        Maintenance::adjustAutoIncrement('sentences', 'SeID');
        Connection::execute(
            "UPDATE {$tbpref}words SET WoWordCount = 0 WHERE WoLgID = $lid",
            ''
        );
        Maintenance::initWordCount();
        $sql = "SELECT TxID, TxText FROM {$tbpref}texts
        WHERE TxLgID = $lid ORDER BY TxID";
        $res = Connection::query($sql);
        $cntrp = 0;
        while ($record = mysqli_fetch_assoc($res)) {
            $txtid = (int)$record["TxID"];
            $txttxt = $record["TxText"];
            TextParsing::splitCheck($txttxt, $lid, $txtid);
            $cntrp++;
        }
        mysqli_free_result($res);
        $message .= " / Reparsed texts: " . $cntrp;
    } else {
        $message .= " / Reparsing not needed";
    }
    return $message;
}

/**
 * Load a language object based in language ID.
 *
 * @param int $lgid Language ID, if 0 load empty data.
 *
 * @return Language Created object
 *
 * @global string $tbpref
 */
function load_language($lgid)
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();

    $language = new Language();
    $language->id = $lgid;
    if ($lgid == 0) {
        // Special case: set all to empty
        $language->dict1uri = "";
        $language->dict2uri = "";
        $language->translator = "";
        $language->exporttemplate = "";
        $language->textsize = 100;
        $language->charactersubst = "";
        $language->regexpsplitsent = "";
        $language->exceptionsplitsent = "";
        $language->regexpwordchar = "";
        $language->removespaces = null;
        $language->spliteachchar = null;
        $language->rightoleft = null;
        $language->ttsvoiceapi = "";
        $language->showromanization = true;
    } else {
        // Load data from database
        $sql = "SELECT * FROM {$tbpref}languages WHERE LgID = $lgid";
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        $language->name =  $record["LgName"];
        $language->dict1uri = $record["LgDict1URI"];
        $language->dict2uri = $record["LgDict2URI"];
        $language->translator = $record["LgGoogleTranslateURI"];
        $language->exporttemplate = $record["LgExportTemplate"];
        $language->textsize = (int) $record["LgTextSize"];
        $language->charactersubst = $record["LgCharacterSubstitutions"];
        $language->regexpsplitsent = $record["LgRegexpSplitSentences"];
        $language->exceptionsplitsent = $record["LgExceptionsSplitSentences"];
        $language->regexpwordchar = $record["LgRegexpWordCharacters"];
        $language->removespaces = (bool) $record["LgRemoveSpaces"];
        $language->spliteachchar = (bool) $record["LgSplitEachChar"];
        $language->rightoleft = (bool) $record["LgRightToLeft"];
        $language->ttsvoiceapi =  $record["LgTTSVoiceAPI"];
        $language->showromanization = (bool) $record["LgShowRomanization"];
        mysqli_free_result($res);
    }
    return $language;
}

function edit_languages_displayThirdPartyVoiceAPI(): void
{
    ?>
<h2>Third-Party Voice API</h2>
<p>
    You can customize the voice API using an external service.
    You have to use the following JSON format.
</p>
<pre
style="background-color: #f0f0f0; padding: 10px; border: 1px solid #ccc;"
><code lang="json"
>{
    "input": ...,
    "options": ...
}</code></pre>
<p>
    LWT will insert text in <code>lwt_term</code> (required),
    you can specify the language with <code>lwt_lang</code> (optional).<br />
    If you need help, suggestions or want to see some demo, please go to
    <a href="https://github.com/HugoFara/lwt/discussions/174">discussion 174</a>.
</p>
    <?php
}


/**
 * Create the form for a language.
 *
 * @param Language $language Language object
 */
function edit_language_form($language): void
{
    $sourceLg = '';
    $targetLg = '';
    $currentnativelanguage = Settings::get('currentnativelanguage');
    if (array_key_exists($currentnativelanguage, LWT_LANGUAGES_ARRAY)) {
        $targetLg = LWT_LANGUAGES_ARRAY[$currentnativelanguage][1];
    }
    if ($language->name) {
        if (array_key_exists($language->name, LWT_LANGUAGES_ARRAY)) {
            $sourceLg = LWT_LANGUAGES_ARRAY[$language->name][1];
        }
        $lgFromDict = langFromDict($language->translator);
        if ($lgFromDict != '' && $lgFromDict != $sourceLg) {
            $sourceLg = $lgFromDict;
        }


        $targetFromDict = targetLangFromDict($language->translator);
        if ($targetFromDict != '' && $targetFromDict != $targetLg) {
            $targetLg = $targetFromDict;
        }
    }
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


        /**
         * Check for specific language option based on language name
         */
        checkLanguageChanged: function(value) {
            if (value == "Japanese") {
                $(document.forms.lg_form.LgRegexpAlt).css("display", "block");
            } else {
                $(document.forms.lg_form.LgRegexpAlt).css("display", "none");
            }
        },

        /**
         * Handles any change on multi-words translate mode.
         */
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

        /**
         * Check status of the requested translation API.
         */
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

        /**
         * Check LibreTranslate translator status.
         */
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

        /**
         * Change the size of demo text.
         */
        changeLanguageTextSize(value) {
            $('#LgTextSizeExample').css("font-size", value + "%");
        },

        /**
         * Handle changes to the words split method.
         */
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

        /**
         * Build a dictionary/translator URL with the pop-up option
         */
        addPopUpOption: function(url, checked) {
            if (url.startsWith('*')) {
                url = url.substring(1);
            }
            const built_url = new URL(url);
            // Remove trivial cases
            if (checked && built_url.searchParams.has('lwt_popup'))
                return built_url.href;
            if (!checked && !built_url.searchParams.has('lwt_popup'))
                return built_url.href;
            // Now we should change status
            if (checked) {
                built_url.searchParams.append('lwt_popup', 'true');
                return built_url.href;
            }
            built_url.searchParams.delete('lwt_popup');
            return built_url.href;
        },


        /**
         * Change the Pop-Up URL of dictionary.
         */
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

        /**
         * Change Pop-Up checkboxes based on input box value.
         */
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

        /**
         * Modify the value of the translator select box if not coherent with the URL.
         */
        checkTranslatorType: function (url, type_select) {
            const parsed_url = new URL(url);
            let final_value;
            switch (parsed_url.searchParams.get("lwt_translator")) {
                case "libretranslate":
                    // Using LibreTranslate
                    final_value = "libretranslate";
                    break;
                default:
                    // Defaulting to Google
                    final_value = "google_translate";
                    break;
            }
            type_select.value = final_value;
        },

        /**
         * Check the word splitting method.
         */
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
            // Check if we have "lwt_term"
            if (!api_value.includes("lwt_term")) {
                message_field.text('"lwt_term" is missing!')
                message_field.show();
                return false
            }
            // Check if query can be parsed as JSON
            let query;
            try {
                query = JSON.parse(api_value);
            } catch (error) {
                message_field.text("Cannot parse as JSON! " +  error)
                message_field.show();
                return false;
            }
            // Check if we find "lwt_term" in JSON
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

    /**
     * Check for specific language option based on language name.
     *
     * @deprecated Since 2.10.0, use edit_languages_js.checkLanguageChanged.
     */
    function checkLanguageChanged(value) {
        return edit_languages_js.checkLanguageChanged(value);
    }

    /**
     * Handles any change on multi-words translate mode.
     *
     * @deprecated Since 2.10.0, use edit_languages_js.multiWordsTranslateChange.
     */
    function multiWordsTranslateChange(value) {
        return edit_languages_js.multiWordsTranslateChange(value);
    }

    /**
     * Check status of the requested translation API.
     *
     * @deprecated Since 2.10.0, use edit_languages_js.checkTranslatorStatus
     */
    function checkTranslatorStatus(url) {
        return edit_languages_js.checkTranslatorStatus(url);
    }

    /**
     * Check LibreTranslate translator status.
     *
     * @deprecated Since 2.10.0, use edit_languages_js.checkLibreTranslateStatus
     */
    function checkLibreTranslateStatus(url, key="") {
        return edit_languages_js.checkLibreTranslateStatus(url, key);
    }

    /**
     * Change the size of demo text.
     *
     * @deprecated Since 2.10.0, use edit_languages_js.changeLanguageTextSize
     */
    function changeLanguageTextSize(value) {
        return edit_languages_js.changeLanguageTextSize(value);
    }

    /**
     * Handle changes to the words split method.
     *
     * @deprecated Since 2.10.0, use edit_languages_js.wordCharChange
     */
    function wordCharChange(value) {
        return edit_languages_js.wordCharChange(value);
    }

    /**
     * Build a dictionary/translator URL with the pop-up option
     *
     * @deprecated Since 2.10.0, use edit_languages_js.addPopUpOption
     */
    function addPopUpOption(url, checked) {
        return edit_languages_js.addPopUpOption(url, checked);
    }

    /**
     * Change the Pop-Up URL of dictionary.
     *
     * @deprecated Since 2.10.0, use edit_languages_js.changePopUpState
     */
    function changePopUpState(elem) {
        return edit_languages_js.changePopUpState(elem);
    }

    /**
     * Change Pop-Up checkboxes based on input box value.
     *
     * @deprecated Since 2.10.0, use edit_languages_js.checkDictionaryChanged
     */
    function checkDictionaryChanged(input_box) {
        return edit_languages_js.checkDictionaryChanged(input_box);
    }

    /**
     * Modify the value of the translator select box if not coherent with the URL.
     *
     * @deprecated Since 2.10.0, use edit_languages_js.checkTranslatorType
     */
    function checkTranslatorType(url, type_select) {
        return edit_languages_js.checkTranslatorType(url, type_select);
    }

    /**
     * Check if all fields are coherent with translator URL.
     */
    function checkTranslatorChanged(translator_input) {
        edit_languages_js.checkTranslatorStatus(translator_input.value);
        edit_languages_js.checkDictionaryChanged(translator_input);
        edit_languages_js.checkTranslatorType(
            translator_input.value, document.forms.lg_form.LgTranslatorName
        );
    }

    /**
     * Check the word splitting method.
     *
     * @deprecated Since 2.10.0, use edit_languages_js.checkWordChar
     */
    function checkWordChar(method) {
        return edit_languages_js.checkWordChar(method);
    }

    function checkVoiceAPI(api_value) {
        return edit_languages_js.checkVoiceAPI(api_value);
    }

    /**
     * Check if the help field are coherent with the input fields.
     *
     * param {element} l_form Language form.
     */
    function checkLanguageForm(l_form) {
        edit_languages_js.checkLanguageChanged(l_form.LgName.value);
        edit_languages_js.checkDictionaryChanged(l_form.LgDict1URI);
        edit_languages_js.checkDictionaryChanged(l_form.LgDict2URI);
        checkTranslatorChanged(l_form.LgGoogleTranslateURI);
        edit_languages_js.checkWordChar(l_form.LgRegexpWordCharacters.value);
    }

    $(edit_languages_js.fullFormCheck);
</script>
<form class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>"
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
                <!-- Glosbe has stopped the API -->
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
            <?php
            if ($language->id == 0) {
                echo '<input type="submit" name="op" value="Save" />';
            } else {
                echo '<input type="submit" name="op" value="Change" />';
            }
            ?>
        </td>
    </tr>
    </table>
</form>
    <?php
}

/**
 * Returns a dropdown menu of the different languages.
 *
 * @param string $currentnativelanguage Default language
 */
function get_wizard_selectoptions($currentnativelanguage): string
{
    $r = "<option value=\"\"" . get_selected($currentnativelanguage, "") .
    ">[Choose...]</option>";
    $keys = array_keys(LWT_LANGUAGES_ARRAY);
    foreach ($keys as $item) {
        $r .= "<option value=\"" . $item . "\"" .
        get_selected($currentnativelanguage, $item) . ">" . $item . "</option>";
    }
    return $r;
}

/**
 * Display a form to create a new language.
 *
 * @return void
 */
function edit_languages_new()
{
    $currentnativelanguage = Settings::get('currentnativelanguage');
    ?>
    <h2>
        New Language
        <a target="_blank" href="docs/info.html#howtolang">
            <img src="/assets/icons/question-frame.png" title="Help" alt="Help" />
        </a>
    </h2>

    <script type="text/javascript" charset="utf-8">

        const LANGDEFS = <?php echo json_encode(LWT_LANGUAGES_ARRAY); ?>;

        /**
         * Main variable for the language selection wizard.
         */
        const language_wizard = {

            /**
             * Fetches langauge data and launches wizard.
             */
            go: function () {
                const l1 = $('#l1').val();
                const l2 = $('#l2').val();
                if (l1 == '') {
                    alert ('Please choose your native language (L1)!');
                    return;
                }
                if (l2 == '') {
                    alert ('Please choose your language you want to read/study (L2)!');
                    return;
                }
                if (l2 == l1) {
                    alert ('L1 L2 Languages must not be equal!');
                    return;
                }
                this.apply(LANGDEFS[l2], LANGDEFS[l1], l2);
            },

            /**
             * Apply wizard based on entered data.
             */
            apply: function (learning_lg, known_lg, learning_lg_name) {
                reloadDictURLs(learning_lg[1], known_lg[1]);
                const url = new URL(window.location.href);
                const base_url = url.protocol + "//" + url.hostname;
                let path = url.pathname;
                const exploded_path = path.split('/');
                exploded_path.pop();
                //path = exploded_path.join('/') + '/trans.php';
                path = path.substring(0, path.lastIndexOf('/languages'));
                LIBRETRANSLATE = base_url + ':5000/?' + $.param({
                    lwt_translator: "libretranslate",
                    lwt_translator_ajax: encodeURIComponent(base_url + ":5000/translate/?"),
                    source: learning_lg[1],
                    target: known_lg[1],
                    q: "lwt_term"
                });
                $('input[name="LgName"]').val(learning_lg_name).change();
                // There may be a cleaner way to trigger the event
                checkLanguageChanged(learning_lg_name);
                $('input[name="LgDict1URI"]').val(
                    'https://de.glosbe.com/' + learning_lg[0] + '/' +
                    known_lg[0] + '/lwt_term?lwt_popup=1'
                );
                $('input[name="LgDict1PopUp"]').attr('checked', true);
                $('input[name="LgGoogleTranslateURI"]').val(GGTRANSLATE);
                $('input[name="LgTextSize"]')
                .val(learning_lg[2] ? 200 : 150)
                .change();
                $('input[name="LgRegexpSplitSentences"]').val(learning_lg[4]);
                $('input[name="LgRegexpWordCharacters"]').val(learning_lg[3]);
                $('input[name="LgSplitEachChar"]').attr("checked", learning_lg[5]);
                $('input[name="LgRemoveSpaces"]').attr("checked", learning_lg[6]);
                $('input[name="LgRightToLeft"]').attr("checked", learning_lg[7]);
            },

            /**
             * Changed the "current native language".
             */
            change_native: function (value) {
                do_ajax_save_setting('currentnativelanguage', value);
            }
        };

        $(document).ready(lwtFormCheck.askBeforeExit);
    </script>
    <div class="td1 center">
        <div class="center" style="border: 1px solid black;">
            <h3 class="clickedit" onclick="$('#wizard_zone').toggle(400);" >
                Language Settings Wizard
            </h3>
            <div id="wizard_zone">
                <img src="/assets/icons/wizard.png" title="Language Settings Wizard" alt="Language Settings Wizard" />

                <div class="flex-spaced">
                    <div>
                        <b>My native language is:</b>
                        <div>
                            <label for="l1">L1</label>
                            <select name="l1" id="l1" onchange="language_wizard.change_native(this.value);">
                                <?php echo get_wizard_selectoptions($currentnativelanguage); ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <b>I want to study:</b>
                        <div>
                        <label for="l2">L2</label>
                            <select name="l2" id="l2">
                                <?php echo get_wizard_selectoptions(''); ?>
                            </select>
                        </div>
                    </div>
                </div>
                <input type="button" style="margin: 5px;" value="Set Language Settings" onclick="language_wizard.go();" />
                <p class="smallgray">
                    Select your native (L1) and study (L2) languages, and let the
                    wizard set all language settings marked in yellow!<br />
                    (You can adjust the settings afterwards.)
                </p>
            </div>
        </div>
    </div>
    <?php
    $language = load_language(0);
    edit_language_form($language);
    ?>
    <p class="smallgray">
        <b>Important:</b>
        <br />
        The placeholders "••" for the from/sl and dest/tl language codes in the
        URIs must be <b>replaced</b> by the actual source and target language
        codes!<br />
        <a href="docs/info.html#howtolang" target="_blank">Please read the documentation</a>.
        Languages with a <b>non-Latin alphabet need special attention</b>,
        <a href="docs/info.html#langsetup" target="_blank">see also here</a>.
    </p>
    <?php
    edit_languages_displayThirdPartyVoiceAPI();
}

/**
 * Display a form to edit an existing language.
 *
 * @param int $lid Language ID
 *
 * @return void
 *
 * @global string $tbpref
 */
function edit_languages_change($lid)
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $sql = "SELECT * FROM {$tbpref}languages WHERE LgID = $lid";
    $res = Connection::query($sql);
    if (mysqli_fetch_assoc($res)) {
        ?>
    <script type="text/javascript" charset="utf-8">
        const LANGDEFS = <?php echo json_encode(LWT_LANGUAGES_ARRAY); ?>;

        $(document).ready(lwtFormCheck.askBeforeExit);
    </script>
    <h2>Edit Language
        <a target="_blank" href="docs/info.html#howtolang">
            <img src="/assets/icons/question-frame.png" title="Help" alt="Help" />
        </a>
    </h2>
        <?php
        $language = load_language($lid);
        edit_language_form($language);
        ?>
    <p class="smallgray">
        <b>Warning:</b> Changing certain language settings
        (e.g. RegExp Word Characters, etc.)<wbr />
        may cause partial or complete loss of improved annotated texts!
    </p>
        <?php
        edit_languages_displayThirdPartyVoiceAPI();
    }
    mysqli_free_result($res);
}

/**
 * Display the standard page of saved languages.
 *
 * @param string $message An information message to display.
 *
 * @global string $tbpref
 * @global int    $debug
 *
 * @return void
 */
function edit_languages_display($message)
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $debug = \Lwt\Core\Globals::isDebug();

    echo error_message_with_hide($message, false);

    $current = (int) Settings::get('currentlanguage');

    $recno = Connection::fetchValue(
        "SELECT COUNT(*) AS value
        FROM {$tbpref}languages
        WHERE LgName<>''"
    );

    ?>

<p>
    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?new=1">
        <img src="/assets/icons/plus-button.png" title="New" alt="New" /> New Language ...
    </a>
</p>

    <?php
    if ($recno == 0) {
        ?>
<p>No languages found.</p>
        <?php
        return;
    }
    ?>

<table class="sortable tab2" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1 sorttable_nosort">Curr. Lang.</th>
        <th class="th1 sorttable_nosort">Test ↓↓↓</th>
        <th class="th1 sorttable_nosort">Actions</th>
        <th class="th1 clickable">Language</th>
        <th class="th1 sorttable_numeric clickable">Texts, Reparse</th>
        <th class="th1 sorttable_numeric clickable">Arch. Texts</th>
        <th class="th1 sorttable_numeric clickable">News feeds<wbr />(Articles)</th>
        <th class="th1 sorttable_numeric clickable">Terms</th>
        <th class="th1 sorttable_nosort">Export Template?</th>
    </tr>

    <?php

    $sql = "SELECT LgID, LgName, LgExportTemplate
    FROM {$tbpref}languages
    WHERE LgName<>'' ORDER BY LgName";
    if ($debug) {
        echo $sql;
    }
    // May be refactored with KISS principle
    $res = Connection::query(
        "SELECT NfLgID, count(*) as value
        FROM {$tbpref}newsfeeds
        group by NfLgID"
    );
    $newsfeedcount = null;
    while ($record = mysqli_fetch_assoc($res)) {
        $newsfeedcount[$record['NfLgID']] = $record['value'];
    }
    // May be refactored with KISS principle
    $res = Connection::query(
        "SELECT NfLgID, count(*) AS value
        FROM {$tbpref}newsfeeds, {$tbpref}feedlinks
        WHERE NfID=FlNfID
        GROUP BY NfLgID"
    );
    $feedarticlescount = null;
    while ($record = mysqli_fetch_assoc($res)) {
        $feedarticlescount[$record['NfLgID']] = $record['value'];
    }
    $res = Connection::query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        $lid = (int)$record['LgID'];
        $foo = Connection::fetchValue(
            "SELECT count(TxID) as value
            FROM {$tbpref}texts
            where TxLgID = $lid"
        );
        $textcount = is_numeric($foo) ? (int)$foo : 0;
        $foo = Connection::fetchValue(
            "SELECT count(AtID) as value
            FROM {$tbpref}archivedtexts
            where AtLgID = $lid"
        );
        $archtextcount = is_numeric($foo) ? (int)$foo : 0;
        $foo = Connection::fetchValue(
            "SELECT count(WoID) as value
            FROM {$tbpref}words
            where WoLgID = $lid"
        );
        $wordcount = is_numeric($foo) ? (int)$foo : 0;
        if (is_null($newsfeedcount)) {
            $nfcount = 0;
        } elseif (isset($newsfeedcount[$lid])) {
            $nfcount = (int)$newsfeedcount[$lid];
        } else {
            $nfcount = 0;
        }
        if (is_null($feedarticlescount)) {
            $fartcount = 0;
        } elseif (isset($feedarticlescount[$lid])) {
            $fartcount = (int)$feedarticlescount[$lid];
        } else {
            $fartcount = 0;
        }
        echo '<tr>';
        if ($current == $lid) {
            $tdth = 'td';
            $style = ' style="background-color: #8884; font-weight: bold;"';
            echo '<td ' . $style . ' class="td1 center">
                <img src="/assets/icons/exclamation-red.png" title="Current Language" alt="Current Language" />
                </td>';
        } else {
            $tdth = 'td';
            $style = '';
            echo '<td class="td1 center">
                <a href="inc/save_setting_redirect.php?k=currentlanguage&amp;v=' . $lid . '&amp;u=/languages">
                <img src="/assets/icons/tick-button.png" title="Set as Current Language" alt="Set as Current Language" />
                </a>
                </td>';
        }

        echo '<td' . $style . ' class="td1 center"><a href="do_test.php?lang=' . $lid . '">
            <img src="/assets/icons/question-balloon.png" title="Test" alt="Test" /></a>
        </' . $tdth . '>
        <td' . $style . ' class="td1 center">
            <a href="' . $_SERVER['PHP_SELF'] . '?chg=' . $lid . '">
                <img src="/assets/icons/document--pencil.png" title="Edit" alt="Edit" />
            </a>';
        if ($textcount == 0 && $archtextcount == 0 && $wordcount == 0 && $nfcount == 0) {
            echo '&nbsp; <span class="click" onclick="if (confirmDelete()) location.href=\'' . $_SERVER['PHP_SELF'] . '?del=' . $lid . '\';">
                <img src="/assets/icons/minus-button.png" title="Delete" alt="Delete" /></span>';
        } else {
            echo '&nbsp; <img src="/assets/icons/placeholder.png" title="Delete not possible" alt="Delete not possible" />';
        }
        echo '</td>
        <td ' . $style . ' class="td1 center">' . tohtml((string)$record['LgName']) . '</td>';
        if ($textcount > 0) {
            echo '<td ' . $style . ' class="td1 center">
                <a href="edit_texts.php?page=1&amp;query=&amp;filterlang=' . $lid . '">' .
            $textcount . '</a> <a href="' . $_SERVER['PHP_SELF'] . '?refresh=' . $lid . '">
                <img src="/assets/icons/lightning.png" title="Reparse Texts" alt="Reparse Texts" /></a>';
        } else {
            echo '<td ' . $style . ' class="td1 center">0 <img src="';
            print_file_path('icn/placeholder.png');
            echo'" title="No texts to reparse" alt="No texts to reparse" />';
        }
        echo '</td>';
        echo '<td ' . $style . ' class="td1 center">' .
        ($archtextcount > 0 ? '<a href="edit_archivedtexts.php?page=1&amp;query=&amp;filterlang=' . $lid . '">' .
        $archtextcount . '</a>' : '0' ) . '</td>';
        echo '<td ' . $style . ' class="td1 center">' .
        ($nfcount > 0 ? '<a href="do_feeds.php?query=&amp;selected_feed=&amp;check_autoupdate=1&amp;filterlang=' . $lid . '">' .
        $nfcount . ' (' . $fartcount . ')</a>' : '0' ) . '</td>';
        echo '<td ' . $style . ' class="td1 center">' .
        ($wordcount > 0 ? '<a href="edit_words.php?page=1&amp;query=&amp;text=&amp;status=&amp;filterlang=' .
        $lid . '&amp;status=&amp;tag12=0&amp;tag2=&amp;tag1=">' . $wordcount . '</a>' : '0' ) . '</td>';
        echo '<td ' . $style . ' class="td1 center" style="border-top-right-radius:0;">' .
        (isset($record['LgExportTemplate']) ? '<img src="/assets/icons/status.png" title="Yes" alt="Yes" />' :
        '<img src="/assets/icons/status-busy.png" title="No" alt="No" />' ) . '</td>';
        echo '</tr>';
    }
    mysqli_free_result($res);

    ?>

</table>

        <?php
}

/**
 * Display a variation of the edit_language page.
 *
 * @return void
 */
function edit_languages_do_page()
{
    pagestart('My Languages', true);
    edit_languages_alert_duplicate();
    $message = '';
    if (isset($_REQUEST['refresh'])) {
        $message = edit_languages_refresh((int) $_REQUEST['refresh']);
    }
    if (isset($_REQUEST['del'])) {
        $message = edit_languages_delete((int)$_REQUEST['del']);
    } elseif (isset($_REQUEST['op'])) {
        // Insert new language or change an existing one
        if ($_REQUEST['op'] == 'Save') {
            // New language
            $message = edit_languages_op_save();
        } elseif ($_REQUEST['op'] == 'Change') {
            // Language edition
            $message = edit_languages_op_change((int)$_REQUEST["LgID"]);
        }
    }

    // Display part

    if (isset($_REQUEST['new'])) {
        edit_languages_new();
    } elseif (isset($_REQUEST['chg'])) {
        edit_languages_change((int)$_REQUEST['chg']);
    } else {
        edit_languages_display($message);
    }
    pageend();
}

edit_languages_do_page();
?>
