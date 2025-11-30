<?php
/**
 * Word Upload Form View
 *
 * Displays the form for importing terms from file or text.
 *
 * Expected variables:
 * - $currentLanguage: Current language setting (from settings)
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views\Word
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Views\Word;

use Lwt\Database\Settings;
?>
<p>
    <b>Important:</b><br />
    You must specify the term.
    <wbr />Translation, romanization, sentence and tag list are optional.
    <wbr />The tag list must be separated either by spaces or commas.
</p>
<form enctype="multipart/form-data" class="validate" action="/word/upload" method="post">
<table class="tab1" cellspacing="0" cellpadding="5">
    <tr>
        <td class="td1 center"><b>Language:</b></td>
        <td class="td1">
            <select name="LgID" class="notempty setfocus">
                <?php
                $langToUse = isset($currentLanguage) ? $currentLanguage : Settings::get('currentlanguage');
                echo get_languages_selectoptions($langToUse, '[Choose...]');
                ?>
            </select>
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <tr>
        <td class="td1 center">
            <b>Import Data:</b>
        </td>
        <td class="td1">
            Either specify a <b>File to upload</b>:<br />
            <input name="thefile" type="file" /><br /><br />
            <b>Or</b> type in or paste from clipboard (do <b>NOT</b> specify file):<br />
            <textarea class="checkoutsidebmp respinput" data_info="Upload" name="Upload" rows="25"></textarea>
        </td>
    </tr>
    <tr>
        <th class="th1 center">Format per line:</th>
        <th class="th1">C1 D C2 D C3 D C4 D C5</th>
    </tr>
    <tr>
        <td class="td1"><b>Field Delimiter "D":</b></td>
        <td class="td1">
            <select name="Tab" class="respinput">
                <option value="c" selected="selected">
                    Comma "," [CSV File, LingQ]
                </option>
                <option value="t">TAB (ASCII 9) [TSV File]</option>
                <option value="h">Hash "#" [Direct Input]</option>
            </select>
        </td>
    </tr>
    <tr>
        <td class="td1"><b>Ignore first line</b>:</td>
        <td class="td1">
            <select name="IgnFirstLine" class="respinput">
                <option value="0" selected="selected">No</option>
                <option value="1">Yes</option>
            </select>
        </td>
    </tr>
    <tr>
        <th class="th1" colspan="2"><b>Column Assignment:</b></th>
    </tr>
    <tr>
        <td class="td1">"C1":</td>
        <td class="td1">
            <select name="Col1" class="respinput">
                <option value="w" selected="selected">Term</option>
                <option value="t">Translation</option>
                <option value="r">Romanization</option>
                <option value="s">Sentence</option>
                <option value="g">Tag List</option>
                <option value="x">Don't import</option>
            </select>
        </td>
    </tr>
    <tr>
        <td class="td1">"C2":</td>
        <td class="td1">
            <select name="Col2" class="respinput">
                <option value="w">Term</option>
                <option value="t" selected="selected">Translation</option>
                <option value="r">Romanization</option>
                <option value="s">Sentence</option>
                <option value="g">Tag List</option>
                <option value="x">Don't import</option>
            </select>
        </td>
    </tr>
    <tr>
        <td class="td1">"C3":</td>
        <td class="td1">
            <select name="Col3" class="respinput">
                <option value="w">Term</option>
                <option value="t">Translation</option>
                <option value="r">Romanization</option>
                <option value="s">Sentence</option>
                <option value="g">Tag List</option>
                <option value="x" selected="selected">Don't import</option>
            </select>
        </td>
    </tr>
    <tr>
        <td class="td1">"C4":</td>
        <td class="td1">
            <select name="Col4" class="respinput">
                <option value="w">Term</option>
                <option value="t">Translation</option>
                <option value="r">Romanization</option>
                <option value="s">Sentence</option>
                <option value="g">Tag List</option>
                <option value="x" selected="selected">Don't import</option>
        </select>
        </td>
    </tr>
    <tr>
        <td class="td1">"C5":</td>
        <td class="td1">
            <select name="Col5" class="respinput">
                <option value="w">Term</option>
                <option value="t">Translation</option>
                <option value="r">Romanization</option>
                <option value="s">Sentence</option>
                <option value="g">Tag List</option>
                <option value="x" selected="selected">Don't import</option>
            </select>
        </td>
    </tr>
    <tr>
        <td class="td1"><b>Import Mode</b>:</td>
        <td class="td1">
            <select name="Over" data-action="update-import-mode" class="respinput">
                <option value="0" title="- don't overwrite existent terms&#x000A;- import new terms" selected="selected">
                    Import only new terms
                </option>
                <option value="1" title="- overwrite existent terms&#x000A;- import new terms">
                    Replace all fields
                </option>
                <option value="2" title="- update only empty fields&#x000A;- import new terms">
                    Update empty fields
                </option>
                <option value="3" title="- overwrite existing terms with new not empty values&#x000A;- don't import new terms">
                    No new terms
                </option>
                <option value="4" title="- add new translations to existing ones&#x000A;- import new terms">
                    Merge translation fields
                </option>
                <option value="5" title="- add new translations to existing ones&#x000A;- don't import new terms">
                    Update existing translations
                </option>
            </select>
        <div class="hide" id="imp_transl_delim">
            Import Translation Delimiter:<br />
            <input class="notempty" type="text" name="transl_delim" style="width:4em;" value="<?php echo Settings::getWithDefault('set-term-translation-delimiters'); ?>" />
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </div>
        </td>
    </tr>
    <tr><th class="th1" colspan="2">Imported words status</th></tr>
    <tr>
        <td class="td1 center"><b>Status</b> for all uploaded terms:</td>
        <td class="td1">
            <select class="notempty respinput" name="WoStatus">
                <?php echo get_wordstatus_selectoptions(null, false, false); ?>
            </select>
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <tr>
        <td class="td1 center" colspan="2">
            <span class="red2">
                A DATABASE <input type="button" value="BACKUP" data-action="navigate" data-url="/admin/backup" />
                MAY BE ADVISABLE!<br />
                PLEASE DOUBLE-CHECK EVERYTHING!
            </span>
            <br />
            <input type="button" value="&lt;&lt; Back" data-action="navigate" data-url="/" />
            <span class="nowrap"></span>
            <input type="submit" name="op" value="Import" />
        </td>
    </tr>
</table>
</form>

<p>
    Sentences should contain the term in curly brackets "... {term} ...".<br />
    If not, such sentences can be automatically created later with the <br />
    "Set Term Sentences" action in the <input type="button" value="My Texts" data-action="navigate" data-url="/texts?query=&amp;page=1" /> screen.
</p>
