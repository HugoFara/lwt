<?php declare(strict_types=1);
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
<script type="application/json" id="tts-settings-config">
{"currentLanguageCode": <?php echo $currentLanguageCode; ?>}
</script>
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
            <select name="LgName" id="get-language" class="notempty respinput">
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
                <input type="button" data-action="tts-demo" value="Read"/>
            </td>
        </tr>
        <tr>
            <td class="td1 right" colspan="4">
                <input type="button" value="Cancel" data-action="tts-cancel" />
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
