<?php declare(strict_types=1);
/**
 * Language Pair Selection View - Wizard popup for selecting language pairs
 *
 * Variables expected:
 * - $currentnativelanguage: string current native language setting
 * - $languageOptions: string HTML options for native language (L1) select
 * - $languageOptionsEmpty: string HTML options for target language (L2) select
 * - $languagesJson: string JSON-encoded language definitions from LanguageDefinitions::getAll()
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
<script type="application/json" id="language-wizard-popup-config">
{"languageDefs": <?php echo $languagesJson; ?>}
</script>

<div class="center">
    <p class="wizard">
        <?php echo IconHelper::render('wand-2', ['title' => 'Language Settings Wizard', 'alt' => 'Language Settings Wizard']); ?>
    </p>

    <h1 class="wizard">
        Language Settings Wizard
    </h1>
    <p class="wizard">
        <b>Native language:</b>
        <br />
        L1:
        <select name="l1" id="l1">
            <?php echo $languageOptions; ?>
        </select>
    </p>
    <p class="wizard">
        <b>I want to study:</b>
        <br />
        L2:
        <select name="l2" id="l2">
            <?php echo $languageOptionsEmpty; ?>
        </select>
    </p>
    <p class="wizard">
        <input type="button" class="wizard-popup-btn" value="Set Language Settings"
            data-action="wizard-popup-go" />
    </p>

    <p class="wizard">
        <input type="button" value="Cancel" data-action="wizard-popup-cancel" />
    </p>
</div>
