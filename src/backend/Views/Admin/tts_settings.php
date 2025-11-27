<?php

/**
 * TTS Settings View
 *
 * Variables expected:
 * - $languageOptions: string HTML options for language select
 * - $currentLanguageCode: string Current language code (JSON-encoded for JS)
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
                <?php echo $languageOptions; ?>
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
            <th class="th1 center">Demo</th>
            <td class="td1 center" colspan="2">
                <textarea id="tts-demo" title="Enter your text here" class="respinput"
                >Lorem ipsum dolor sit amet...</textarea>
            </td>
            <td class="td1 right">
                <input type="button" onclick="tts_settings.readingDemo();" value="Read"/>
            </td>
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
<p>
    <b>Note</b>: language settings depend on your web browser, as different web
    browser have different ways to read languages. Saving anything here will save
    it as a cookie on your browser and will not be accessible by the LWT database.
</p>
<script type="text/javascript" charset="utf-8">

    const tts_settings = {
        /** @var string current_language Current language being learnt. */
        current_language: <?php echo $currentLanguageCode; ?>,

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
