<?php

/**
 * Language Wizard View
 *
 * Variables expected:
 * - $currentNativeLanguage: string current native language setting
 * - $languageOptions: string HTML options for language select
 * - $languageDefsJson: string JSON-encoded language definitions
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
<script type="application/json" id="language-wizard-config">
<?php echo json_encode(['languageDefs' => json_decode($languageDefsJson, true)]); ?>
</script>
<div class="td1 center">
    <div class="center" style="border: 1px solid black;">
        <h3 class="clickedit" data-action="wizard-toggle">
            Language Settings Wizard
        </h3>
        <div id="wizard_zone">
            <img src="/assets/icons/wizard.png" title="Language Settings Wizard" alt="Language Settings Wizard" />

            <div class="flex-spaced">
                <div>
                    <b>My native language is:</b>
                    <div>
                        <label for="l1">L1</label>
                        <select name="l1" id="l1">
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
            <input type="button" style="margin: 5px;" value="Set Language Settings"
            data-action="wizard-go" />
            <p class="smallgray">
                Select your native (L1) and study (L2) languages, and let the
                wizard set all language settings marked in yellow!<br />
                (You can adjust the settings afterwards.)
            </p>
        </div>
    </div>
</div>
