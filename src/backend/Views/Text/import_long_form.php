<?php

/**
 * Long Text Import Form View
 *
 * Variables expected:
 * - $languageData: array - Mapping of language ID to language code
 * - $languagesOption: string - HTML options for language select
 * - $maxInputVars: int - Maximum input variables
 *
 * PHP version 8.1
 *
 * @category Views
 * @package  Lwt
 * @license  Unlicense <http://unlicense.org/>
 * @since    3.0.0
 */

?>
<script type="text/javascript" charset="utf-8">
    /**
     * Change the language of inputs for text and title based on selected
     * language.
     *
     * @returns undefined
     */
    function change_textboxes_language() {
        const lid = document.getElementById("TxLgID").value;
        const language_data = <?php echo json_encode($languageData); ?>;
        $('#TxTitle').attr('lang', language_data[lid]);
        $('#TxText').attr('lang', language_data[lid]);
    }

    $(document).ready(lwtFormCheck.askBeforeExit);
    $(document).ready(change_textboxes_language);
</script>

<div class="flex-spaced">
    <div title="Import of a single text, max. 65,000 bytes long, with optional audio">
        <a href="/texts?new=1">
            <img src="/assets/icons/plus-button.png">
            Short Text Import
        </a>
    </div>
    <div>
        <a href="/feeds?page=1&amp;check_autoupdate=1">
            <img src="/assets/icons/plus-button.png">
            Newsfeed Import
        </a>
    </div>
    <div>
        <a href="/texts?query=&amp;page=1">
            <img src="/assets/icons/drawer--plus.png">
            Active Texts
        </a>
    </div>
    <div>
        <a href="/text/archived?query=&amp;page=1">
            <img src="/assets/icons/drawer--minus.png">
            Archived Texts
        </a>
    </div>
</div>

<form enctype="multipart/form-data" class="validate" action="/text/import-long" method="post">
<table class="tab1" cellspacing="0" cellpadding="5">
    <tr>
        <td class="td1 right">Language:</td>
        <td class="td1">
            <select name="LgID" id="TxLgID" class="notempty setfocus" onchange="change_textboxes_language();">
                <?php echo $languagesOption; ?>
            </select>
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">Title:</td>
        <td class="td1">
            <input type="text" class="notempty checkoutsidebmp respinput"
            data_info="Title" name="TxTitle" id="TxTitle" value="" maxlength="200" />
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">
            Text:
        </td>
        <td class="td1">
            Either specify a <b>File to upload</b>:<br />
            <input name="thefile" type="file" /><br /><br />
            <b>Or</b> paste a text from the clipboard
            (and do <b>NOT</b> specify file):<br />

            <textarea class="checkoutsidebmp respinput" data_info="Upload"
            name="Upload" id="TxText" rows="15"></textarea>

            <p class="smallgray">
                If the text is too long, the import may not be possible. <wbr />
                Current upload limits (in bytes):
                <br />
                <b>post_max_size</b>:
                <?php echo ini_get('post_max_size'); ?>
                <br />
                <b>upload_max_filesize</b>:
                <?php echo ini_get('upload_max_filesize'); ?>
                <br />
                If needed, increase in <wbr />"<?php echo tohtml(php_ini_loaded_file()); ?>" <wbr />
                and restart the server.
            </p>
        </td>
    </tr>
    <tr>
        <td class="td1 right">NEWLINES and paragraphs:</td>
        <td class="td1">
            <select name="paragraph_handling" class="respinput">
                <option value="1" selected="selected">
                    ONE NEWLINE: Paragraph ends
                </option>
                <option value="2">
                    TWO NEWLINEs: Paragraph ends. Single NEWLINE converted to SPACE
                </option>
            </select>
        <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">Maximum sentences per text:</td>
        <td class="td1">
            <input type="number" min="0" max="999" class="notempty posintnumber"
            data_info="Maximum Sentences per Text" name="maxsent" value="50" maxlength="3" size="3" />
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
            <br />
            <span class="smallgray">
                Values higher than 100 may slow down text display.
                Very low values (< 5) may result in too many texts.
                <br />
                The maximum number of new texts must not exceed <?php echo ($maxInputVars - 20); ?>.
                A single new text will never exceed the length of 65,000 bytes.
            </span>
        </td>
    </tr>
    <tr>
        <td class="td1 right">Source URI:</td>
        <td class="td1">
            <input type="url" class="checkurl checkoutsidebmp respinput"
            data_info="Source URI" name="TxSourceURI" value="" maxlength="1000" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">Tags:</td>
        <td class="td1">
            <?php echo \Lwt\Services\TagService::getTextTagsHtml(0); ?>
        </td>
    </tr>
    <tr>
        <td class="td1 right" colspan="2">
            <input type="button" value="Cancel" onclick="{lwtFormCheck.resetDirty(); location.href='index.php';}" />
            <input type="submit" name="op" value="NEXT STEP: Check the Texts" />
        </td>
    </tr>
</table>
</form>
