<?php

/**
 * Language Wizard View
 *
 * Variables expected:
 * - $currentNativeLanguage: string current native language setting
 * - $languageOptions: string HTML options for language select
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
<script type="text/javascript" charset="utf-8">

    const LANGDEFS = <?php echo json_encode(LWT_LANGUAGES_ARRAY); ?>;

    const language_wizard = {

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

        apply: function (learning_lg, known_lg, learning_lg_name) {
            reloadDictURLs(learning_lg[1], known_lg[1]);
            const url = new URL(window.location.href);
            const base_url = url.protocol + "//" + url.hostname;
            let path = url.pathname;
            const exploded_path = path.split('/');
            exploded_path.pop();
            path = path.substring(0, path.lastIndexOf('/languages'));
            LIBRETRANSLATE = base_url + ':5000/?' + $.param({
                lwt_translator: "libretranslate",
                lwt_translator_ajax: encodeURIComponent(base_url + ":5000/translate/?"),
                source: learning_lg[1],
                target: known_lg[1],
                q: "lwt_term"
            });
            $('input[name="LgName"]').val(learning_lg_name).change();
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
                            <?php echo $languageOptions; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <b>I want to study:</b>
                    <div>
                    <label for="l2">L2</label>
                        <select name="l2" id="l2">
                            <?php echo $languageOptionsEmpty; ?>
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
