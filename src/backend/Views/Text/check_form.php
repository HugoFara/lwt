<?php declare(strict_types=1);
/**
 * Text Check Form View - Form to check text parsing
 *
 * Variables expected:
 * - $languagesOption: string - HTML select options for languages
 * - $languageData: array - Mapping of language ID to language code
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
 * @psalm-suppress UndefinedVariable - Variables are set by the including controller
 */

namespace Lwt\Views\Text;

use Lwt\View\Helper\IconHelper;

/** @var string $languagesOption */
/** @var array $languageData */

// JavaScript moved to forms/form_initialization.ts (auto-detects form.validate and language-data-config)

?>
<script type="application/json" id="language-data-config"><?php echo json_encode($languageData); ?></script>

<form class="validate" action="/text/check" method="post">
    <table class="tab3" cellspacing="0" cellpadding="5">
        <tr>
            <td class="td1 right">Language:</td>
            <td class="td1">
                <select name="TxLgID" id="TxLgID" class="notempty setfocus">
                    <?php echo $languagesOption; ?>
                </select>
                <?php echo IconHelper::render('circle-x', ['title' => 'Field must not be empty', 'alt' => 'Field must not be empty']); ?>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Text:<br /><br />(max.<br />65,000<br />bytes)</td>
            <td class="td1">
                <textarea name="TxText" id="TxText" class="notempty checkbytes checkoutsidebmp" data_maxlength="65000" data_info="Text" cols="60" rows="20"></textarea>
                <?php echo IconHelper::render('circle-x', ['title' => 'Field must not be empty', 'alt' => 'Field must not be empty']); ?>
            </td>
        </tr>
        <tr>
            <td class="td1 right" colspan="2">
                <input type="button" value="&lt;&lt; Back" data-action="navigate" data-url="/" />
                <input type="submit" name="op" value="Check" />
            </td>
        </tr>
    </table>
</form>
