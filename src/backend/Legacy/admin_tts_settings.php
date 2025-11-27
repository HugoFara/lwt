<?php

/**
 * \file
 * \brief Utility for calling system speech synthesizer
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package  Lwt
 * @author   chaosarium <leonluleonlu@gmail.com>
 * @author   HugoFara <Hugo.Farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/text-to-speech-settings.html
 * @since    2.2.2-fork
 */

require_once 'Core/database_connect.php';
require_once 'Core/UI/ui_helpers.php';
require_once 'Core/Language/language_utilities.php';
require_once 'Core/Language/langdefs.php';

use Lwt\Database\Settings;

/**
 * Two-letter language code from from language name (e. g. : "English" = > "en" ).
 *
 * @param string $language Language name, for instance "English"
 *
 * @return string Two-letter language name
 *
 * @ðeprecated Since 2.9.1-fork, use getLanguageCode
 */
function get_language_code($language)
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $lg_id = (int) get_first_value(
        "SELECT LgID as value
        FROM {$tbpref}languages
        WHERE LgName = " . convert_string_to_sqlsyntax($language)
    );
    return getLanguageCode($lg_id, LWT_LANGUAGES_ARRAY);
}

/**
 * Return the language ID from a two-letter language code or a BCP 47 tag.
 *
 * If two languages have the same country name, only thte first one will be returned.
 *
 * @param string $code Two letters, or four letters separated with caret
 *                     ("fr" or "en-US").
 *
 * @return int Language ID if found, -1 otherwise.
 */
function language_id_from_code($code)
{
    $trimmed = substr($code, 0, 2);
    foreach (get_languages() as $language => $language_id) {
        $elem = LWT_LANGUAGES_ARRAY[$language];
        if ($elem[0] == $trimmed) {
            return $language_id;
        }
    }
    return -1;
}

/**
 * String to population a SELECT tag.
 *
 * @return string HTML-formatted string.
 */
function tts_language_options(): string
{
    $output = '';
    foreach (get_languages() as $language => $language_id) {
        $languageCode = getLanguageCode($language_id, LWT_LANGUAGES_ARRAY);
        $output .= sprintf(
            '<option value="%s">%s</option>',
            $languageCode,
            $language
        );
    }
    return $output;
}


/**
 * Prepare a from for all the TTS settings.
 *
 * @return void
 */
function tts_settings_form()
{
    ?>
<form class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
    <table class="tab1" cellspacing="0" cellpadding="5">
        <tr>
            <th class="th1">Group</th>
            <th class="th1">Description</th>
            <th class="th1" colspan="2">Value</th>
        </tr>
        <tr>
            <th class="th1 center" rowspan="2">Language</th>
            <td class="td1 center">Language code</td>
            <td class="td1 center">
            <select name="LgName" id="get-language" class="notempty respinput"
            onchange="tts_settings.populateVoiceList();">
                <?php echo tts_language_options(); ?>
            </select>
            </td>
            <td class="td1 center">
                <img src="<?php print_file_path("icn/status-busy.png") ?>"
                title="Field must not be empty" alt="Field must not be empty" />
            </td>
        </tr>
        <tr>
            <td class="td1 center">Voice <wbr />(depends on your browser)</td>
            <td class="td1 center">
                <select name="LgVoice" id="voice" class="notempty respinput">
                </select>
            </td>
            <td class="td1 center">
                <img src="<?php print_file_path("icn/status-busy.png") ?>"
                title="Field must not be empty" alt="Field must not be empty" />
            </td>
        </tr>
        <tr>
            <th class="th1 center" rowspan="2">Speech</th>
            <td class="td1 center">Reading Rate</td>
            <td class="td1 center">
                <input type="range" name="LgTTSRate" class="respinput"
                min="0.5" max="2" value="1" step="0.1" id="rate">
            </td>
            <td class="td1 center">
                <img src="<?php print_file_path("icn/status.png") ?>" alt="status icon"/>
            </td>
        </tr>
        <tr>
            <td class="td1 center">Pitch</td>
            <td class="td1 center">
                <input type="range" name="LgPitch" class="respinput" min="0"
                max="2" value="1" step="0.1" id="pitch">
            </td>
            <td class="td1 center">
                <img src="<?php print_file_path("icn/status.png") ?>" alt="status icon" />
            </td>
        </tr>
        <tr>
            <?php tts_demo(); ?>
        </tr>
        <tr>
            <td class="td1 right" colspan="4">
                <input type="button" value="Cancel"
                onclick="tts_settings.clickCancel();" />
                <input type="submit" name="op" value="Save" />
            </td>
        </tr>
    </table>
</form>
    <?php
}

/**
 * Prepare a demo for TTS.
 *
 * @return void
 */
function tts_demo()
{
    ?>
<th class="th1 center">Demo</th>
<td class="td1 center" colspan="2">
    <textarea id="tts-demo" title="Enter your text here" class="respinput"
    >Lorem ipsum dolor sit amet...</textarea>
</td>
<td class="td1 right">
    <input type="button" onclick="tts_settings.readingDemo();" value="Read"/>
</td>
    <?php
}

/**
 * Prepare the JavaScript content for text-to-speech.
 *
 * @return void
 */
function tts_js()
{
    $lid = (int) Settings::get('currentlanguage');
    $lg_code = getLanguageCode($lid, LWT_LANGUAGES_ARRAY);
    ?>
<script type="text/javascript" charset="utf-8">

    const tts_settings = {
        /** @var string current_language Current language being learnt. */
        current_language: <?php echo json_encode($lg_code); ?>,

        autoSetCurrentLanguage: function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('lang')) {
                tts_settings.current_language = urlParams.get('lang');
            }
        },

        /**
         * Get the language country code from the page.
         *
         * @returns {string} Language code (e. g. "en")
         */
        getLanguageCode: function() {
            return $('#get-language').val();
        },

        /**
         * Gather data in the page to read the demo.
         *
         * @returns {undefined}
         */
        readingDemo: function() {
            const lang = tts_settings.getLanguageCode();
            readTextAloud(
                $('#tts-demo').val(),
                lang,
                parseFloat($('#rate').val()),
                parseFloat($('#pitch').val()),
                $('#voice').val()
            );
        },


        /**
         * Set the Text-to-Speech data using cookies
         */
        presetTTSData: function() {
            const lang_name = tts_settings.current_language;
            $('#get-language').val(lang_name);
            $('#voice').val(getCookie('tts[' + lang_name + 'RegName]'));
            $('#rate').val(getCookie('tts[' + lang_name + 'Rate]'));
            $('#pitch').val(getCookie('tts[' + lang_name + 'Pitch]'));
        },

        /**
         * Populate the languages region list.
         *
         * @returns {undefined}
         */
        populateVoiceList: function() {
            let voices = window.speechSynthesis.getVoices();
            $('#voice').empty();
            const languageCode = getLanguageCode();
            for (i = 0; i < voices.length ; i++) {
                if (voices[i].lang != languageCode && !voices[i].default)
                    continue;
                let option = document.createElement('option');
                option.textContent = voices[i].name;

                if (voices[i].default) {
                    option.textContent += ' -- DEFAULT';
                }

                option.setAttribute('data-lang', voices[i].lang);
                option.setAttribute('data-name', voices[i].name);
                $('#voice')[0].appendChild(option);
            }
        },

        clickCancel: function() {
            lwtFormCheck.resetDirty();
            location.href = '/admin/settings/tts';
        }
    };

    /**
     * @deprecated Since 2.10.0-fork
     */
    const CURRENT_LANGUAGE = tts_settings.current_language;


    /**
     * Get the language country code from the page.
     *
     * @returns {string} Language code (e. g. "en")
     *
     * @deprecated Since 2.10.0-fork
     */
    function getLanguageCode()
    {
        return tts_settings.getLanguageCode();
    }

    /**
     * Gather data in the page to read the demo.
     *
     * @returns {undefined}
     *
     * @deprecated Since 2.10.0-fork
     */
    function readingDemo()
    {
        return tts_settings.readingDemo();
    }

    /**
     * Set the Text-to-Speech data using cookies
     *
     * @deprecated Since 2.10.0-fork
     */
    function presetTTSData()
    {
        return tts_settings.presetTTSData()
    }

    /**
     * Populate the languages region list.
     *
     * @returns {undefined}
     *
     * @deprecated Since 2.10.0-fork
     */
    function populateVoiceList() {
        return tts_settings.populateVoiceList();
    }

    $(tts_settings.autoSetCurrentLanguage);
    $(tts_settings.presetTTSData);
    $(tts_settings.populateVoiceList);
</script>
    <?php
}

/**
 * Make only a partial, embedable page for text-to-speech settings.
 *
 * @return void
 */
function tts_settings_minimal_page()
{
    tts_settings_form();
    ?>
    <p>
        <b>Note</b>: language settings depend on your web browser, as different web
        browser have different ways to read languages. Saving anything here will save
        it as a cookie on your browser and will not be accessible by the LWT database.
    </p>
    <?php
    tts_js();
}

/**
 * Make the complete HTML page for text-to-speech settings.
 *
 * @param string $message A message to display when page loads.
 *
 * @return void
 */
function tts_settings_full_page($message)
{
    pagestart('Text-to-Speech Settings', true);
    if ($message != '') {
        error_message_with_hide($message, false);
        if ($_COOKIE[$message]) {
            error_message_with_hide('Text-to-Speech settings saved!', false);
        } else {
            error_message_with_hide('Error: Unable to set cookies', false);
        }
    }
    tts_settings_minimal_page();
    pageend();
}

/**
 * Save the text-to-speech settings as cookies.
 *
 * @param array $form Inputs from the main form.
 *
 * @return void
 */
function tts_save_settings($form): void
{
    $lgname = $form['LgName'];
    $prefix = 'tts[' . $lgname;
    /* Could be useful if problems with cookies
    $params = session_get_cookie_params();
    'domain' => isset($params['domain']),
    'secure' => isset($params['secure']),
    'httponly' => isset($params['httponly'])
    */
    $cookie_options = array(
        'expires' => strtotime('+5 years'),
        'path' => '/',
        'samesite' => 'Strict' // None || Lax || Strict
    );
    setcookie($prefix . 'Voice]', $form['LgVoice'], $cookie_options);
    setcookie($prefix . 'Rate]', $form['LgTTSRate'], $cookie_options);
    setcookie($prefix . 'Pitch]', $form['LgPitch'], $cookie_options);
}

$message = '';
if (array_key_exists('op', $_REQUEST) && $_REQUEST['op'] == 'Save') {
    tts_save_settings($_REQUEST);
    $message = "Settings saved!";
}
tts_settings_full_page($message);

?>
