<?php

/**
 * \file
 * \brief Long Text Import
 *
 * Call: long_text_import.php?...
 *                          op=...
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/long-text-import.html
 * @since    1.5.9
 */

require_once 'Core/session_utility.php';

/**
 * Display the check page before a long text import.
 *
 * @param int $max_input_vars Maximale bytes size for the text.
 *
 * @return void
 */
function long_text_check($max_input_vars): void
{

    $langid = (int) $_REQUEST["LgID"];
    $title = (string) $_REQUEST["TxTitle"];
    $paragraph_handling = (int) $_REQUEST["paragraph_handling"];
    $maxsent = (int) $_REQUEST["maxsent"];
    $source_uri = (string) $_REQUEST["TxSourceURI"];
    $texttags = null;
    if (isset($_REQUEST["TextTags"])) {
        $texttags = json_encode($_REQUEST["TextTags"]);
    }

    // Get $data with \n line endings
    if (
        isset($_FILES["thefile"])
        && $_FILES["thefile"]["tmp_name"] != ""
        && $_FILES["thefile"]["error"] == 0
    ) {
        $data = file_get_contents($_FILES["thefile"]["tmp_name"]);
        $data = str_replace("\r\n", "\n", $data);
    } else {
        $data = prepare_textdata($_REQUEST["Upload"]);
    }
    $data = replace_supp_unicode_planes_char($data);
    $data = trim($data);

    // Use ¶ symbol for paragraphs separation
    if ($paragraph_handling == 2) {
        $data = preg_replace('/\n\s*?\n/u', '¶', $data);
        $data = str_replace("\n", ' ', $data);
    } else {
        $data = str_replace("\n", '¶', $data);
    }
    $data = preg_replace('/\s{2,}/u', ' ', $data);
    $data = str_replace('¶ ', '¶', $data);
    // Separate paragraphs by \n finally
    $data = str_replace('¶', "\n", $data);

    if ($data == "") {
        $message = "Error: No text specified!";
        echo error_message_with_hide($message, false);
    } else {
        $sent_array = splitCheckText($data, $langid, -2);
        $texts = array();
        $text_index = 0;
        $texts[$text_index] = array();
        $cnt = 0;
        $bytes = 0;
        foreach ($sent_array as $item) {
            $item_len = strlen($item) + 1;
            if ($item != '¶') {
                $cnt++;
            }
            if ($cnt <= $maxsent && $bytes + $item_len < 65000) {
                $texts[$text_index][] = $item;
                $bytes += $item_len;
            } else {
                $text_index++;
                $texts[$text_index] = array($item);
                $cnt = 1;
                $bytes = $item_len;
            }
        }
        $textcount = count($texts);
        $plural = ($textcount == 1 ? '' : 's');
        $shorter = ($textcount == 1 ? ' ' : ' shorter ');

        if ($textcount > $max_input_vars - 20) {
            $message = "Error: Too many texts (" . $textcount . " > " .
            ($max_input_vars - 20) .
            "). You must increase 'Maximum Sentences per Text'!";
            echo error_message_with_hide($message, false);
        } else {
            ?>
<script type="text/javascript">
    //<![CDATA[
        $(document).ready(lwtFormCheck.askBeforeExit);
        lwtFormCheck.makeDirty();
    //]]>
</script>
<form enctype="multipart/form-data"  action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<input type="hidden" name="LgID" value="<?php echo $langid; ?>" />
<input type="hidden" name="TxTitle" value="<?php echo tohtml($title); ?>" />
<input type="hidden" name="TxSourceURI" value="<?php echo tohtml($source_uri); ?>" />
<input type="hidden" name="TextTags" value="<?php echo tohtml($texttags); ?>" />
<input type="hidden" name="TextCount" value="<?php echo $textcount; ?>" />
<table class="tab1" cellspacing="0" cellpadding="5">
    <tr>
        <td class="td1" colspan="2">
            <?php echo "This long text will be split into " . $textcount . $shorter . "text" . $plural . " - as follows:"; ?>
        </td>
    </tr>
    <tr>
        <td class="td1 right" colspan="2">
            <input type="button" value="Cancel" onclick="{lwtFormCheck.resetDirty(); location.href='index.php';}" />
            <span class="nowrap"></span>
            <input type="button" value="Go Back" onclick="{lwtFormCheck.resetDirty(); history.back();}" />
            <span class="nowrap"></span>
            <input type="submit" name="op" value="Create <?php echo $textcount; ?> text<?php echo $plural; ?>" />
        </td>
    </tr>
            <?php
            $textno = -1;
            foreach ($texts as $item) {
                $textno++;
                $textstring = str_replace("¶", "\n", implode(" ", $item));
                $bytes = strlen($textstring);
                ?>
    <tr>
        <td class="td1 right">
            <b>Text <?php echo $textno + 1; ?>:</b>
            <br /><br /><br />
            Length:<br /><?php echo $bytes; ?><br />Bytes
        </td>
        <td class="td1">
            <textarea readonly="readonly"
                <?php echo getScriptDirectionTag($langid); ?>
            name="text[<?php echo $textno; ?>]" cols="60" rows="10">
                    <?php echo $textstring; ?>
            </textarea>
        </td>
    </tr>
                <?php
            }
            ?>
</table>
</form>
            <?php
        }
    }
}

/**
 * Save a long text to the database.
 *
 * @return void
 *
 * @global string $tppref Database table prefix.
 */
function long_text_save(): void
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $langid = (int) $_REQUEST["LgID"];
    $title = $_REQUEST["TxTitle"];
    $source_uri = $_REQUEST["TxSourceURI"];
    if (isset($_REQUEST["TextTags"])) {
        $_REQUEST["TextTags"] = json_decode($_REQUEST["TextTags"], true);
    }
    $textcount = (int)$_REQUEST["TextCount"];
    $texts = $_REQUEST["text"];

    if (count($texts) != $textcount) {
        $message = "Error: Number of texts wrong: " .  count($texts) . " != " . $textcount;
    } else {
        $imported = 0;
        for ($i = 0; $i < $textcount; $i++) {
            $texts[$i] = remove_soft_hyphens($texts[$i]);
            $counter = makeCounterWithTotal($textcount, $i + 1);
            $thistitle = $title . ($counter == '' ? '' : (' (' . $counter . ')'));
            $imported += (int) runsql(
                'insert into ' . $tbpref . 'texts (
                    TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI,
                    TxSourceURI
                ) values( ' .
                    $langid . ', ' .
                    convert_string_to_sqlsyntax($thistitle) . ', ' .
                    convert_string_to_sqlsyntax($texts[$i]) . ", '',
                    NULL, " .
                    convert_string_to_sqlsyntax($source_uri) .
                ')',
                ''
            );
            $id = get_last_key();
            saveTextTags($id);
            splitCheckText($texts[$i], $langid, $id);
        }
        $message = $imported . " Text(s) imported!";
    }

    echo error_message_with_hide($message, false);

    ?>
 <p>&nbsp;<br /><input type="button" value="Show Texts" onclick="location.href='/texts';" /></p>
    <?php
}

/**
 * Display the main page for a long tex import.
 *
 * @param int $max_input_vars Maximal number of bytes for the text.
 *
 * @global $tbpref
 *
 * @return void
 */
function long_text_display($max_input_vars)
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $sql = "SELECT LgID, LgGoogleTranslateURI FROM {$tbpref}languages
    WHERE LgGoogleTranslateURI<>''";
    $res = do_mysqli_query($sql);
    $return = array();
    while ($lg_record = mysqli_fetch_assoc($res)) {
        $url = $lg_record["LgGoogleTranslateURI"];
        $return[$lg_record["LgID"]] = langFromDict($url);
    }
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
            const language_data = <?php echo json_encode($return); ?>;
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

    <form enctype="multipart/form-data" class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
    <table class="tab1" cellspacing="0" cellpadding="5">
        <tr>
            <td class="td1 right">Language:</td>
            <td class="td1">
                <select name="LgID" id="TxLgID" class="notempty setfocus" onchange="change_textboxes_language();">
                    <?php
                    echo get_languages_selectoptions(getSetting('currentlanguage'), '[Choose...]');
                    ?>
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
                    The maximum number of new texts must not exceed <?php echo ($max_input_vars - 20); ?>.
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
                <?php echo getTextTags(0); ?>
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

    <?php
}

/**
 * Do the main page for the long text import.
 *
 * @return void
 */
function long_text_do_page(): void
{
    pagestart('Long Text Import', true);

    $max_input_vars = ini_get('max_input_vars');
    if ($max_input_vars === false || $max_input_vars == '') {
        $max_input_vars = 1000;
    }

    if (isset($_REQUEST['op'])) {
        if (substr($_REQUEST['op'], 0, 5) == 'NEXT ') {
            long_text_check($max_input_vars);
        } elseif (substr($_REQUEST['op'], 0, 5) == 'Creat') {
            long_text_save();
        }
    } else {
        long_text_display($max_input_vars);
    }

    pageend();
}

long_text_do_page();

?>