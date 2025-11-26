<?php

/**
 * \file
 * \brief Check (parse & split) a Text (into sentences/words)
 *
 * Call: check_text.php?...
 *      op=Check ... do the check
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/check-text.html
 * @since    1.0.3
 */

namespace Lwt\Interface\Check_Text;

require_once 'Core/session_utility.php';

use Lwt\Database\Settings;

use Lwt\Database\Escaping;
use Lwt\Database\TextParsing;

/**
 * Do the check text operation.
 *
 * @param string $text Text to check.
 * @param int    $lgid Language ID.
 *
 * @return void
 */
function do_operation($text, $lgid)
{
    echo '<p><input type="button" value="&lt;&lt; Back" onclick="history.back();" /></p>';
    if (strlen(Escaping::prepareTextdata($text)) > 65000) {
        echo "<p>Error: Text too long, must be below 65000 Bytes.</p>";
    } else {
        TextParsing::splitCheck($text, $lgid, -1);
    }
    echo '<p><input type="button" value="&lt;&lt; Back" onclick="history.back();" /></p>';
}

/**
 * Display the main form for the check text page.
 *
 * @global string $tbpref
 *
 * @return void
 */
function display_form()
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $sql = "SELECT LgID, LgGoogleTranslateURI FROM {$tbpref}languages
    WHERE LgGoogleTranslateURI<>''";
    $res = do_mysqli_query($sql);
    $return = array();
    while ($lg_record = mysqli_fetch_assoc($res)) {
        $url = $lg_record["LgGoogleTranslateURI"];
        $return[$lg_record["LgID"]] = langFromDict($url);
    }
    $languages_option = get_languages_selectoptions(
        Settings::get('currentlanguage'),
        '[Choose...]'
    );
    ?>

    <script type="text/javascript" charset="utf-8">
        /**
         * Change the language of inputs for text and title based on selected
         * language.
         *
         * @returns {undefined}
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
<form class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<table class="tab3" cellspacing="0" cellpadding="5">
    <tr>
    <td class="td1 right">Language:</td>
    <td class="td1">
        <select name="TxLgID" id="TxLgID" class="notempty setfocus" onchange="change_textboxes_language();">
            <?php echo $languages_option;?>
        </select>
        <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
    </td>
    </tr>
    <tr>
        <td class="td1 right">Text:<br /><br />(max.<br />65,000<br />bytes)</td>
        <td class="td1">
            <textarea name="TxText" id="TxText" class="notempty checkbytes checkoutsidebmp" data_maxlength="65000" data_info="Text" cols="60" rows="20"></textarea>
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <tr>
        <td class="td1 right" colspan="2">
            <input type="button" value="&lt;&lt; Back" onclick="location.href='index.php';" />
            <input type="submit" name="op" value="Check" />
        </td>
</tr>
</table>
</form>
    <?php
}

pagestart('Check a Text', true);

if (isset($_REQUEST['op'])) {
    do_operation((string)$_REQUEST['TxText'], (int)$_REQUEST['TxLgID']);
} else {
    display_form();
}
pageend();

?>
