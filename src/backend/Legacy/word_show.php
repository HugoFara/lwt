<?php

/**
 * Show term
 *
 * Call: show_word.php?wid=...&ann=...
 *
 * PHP version 8.1
 *
 * @category Lwt
 */

require_once 'Core/database_connect.php';
require_once 'Core/UI/ui_helpers.php';
require_once 'Core/Tag/tags.php';
require_once 'Core/Text/text_helpers.php';
require_once 'Core/Http/param_helpers.php';
require_once 'Core/Word/dictionary_links.php';
require_once 'Core/Language/language_utilities.php';
require_once 'Core/Word/word_status.php';

use Lwt\Database\Connection;

pagestart_nobody('Term');

$wid = getreq('wid');
$ann = $_REQUEST["ann"];

if ($wid == '') {
    my_die('Word not found in show_word.php');
}

$sql = 'select WoLgID, WoText, WoTranslation, WoSentence, WoRomanization, WoStatus
from ' . $tbpref . 'words where WoID = ' . $wid;
$res = Connection::query($sql);
if ($record = mysqli_fetch_assoc($res)) {
    $transl = repl_tab_nl($record['WoTranslation']);
    if ($transl == '*') {
        $transl = '';
    }

    $tags = getWordTagList($wid, '', 0, 0);
    $rom = $record['WoRomanization'];
    $scrdir = getScriptDirectionTag((int) $record['WoLgID']);

    ?>


<table class="tab2" cellspacing="0" cellpadding="5">
<tr>
    <td class="td1 right" style="width:30px;">Term:</td>
    <td class="td1" style="font-size:120%; border-top-right-radius:inherit;" <?php echo $scrdir; ?>><b><?php echo tohtml($record['WoText']); ?></b></td>
</tr>
<tr>
    <td class="td1 right">Translation:</td>
    <td class="td1" style="font-size:120%;"><b><?php
    if (!empty($ann)) {
        echo
        str_replace_first(
            tohtml($ann),
            '<span style="color:red">' . tohtml($ann) . '</span>',
            tohtml($transl)
        );
    } else {
        echo tohtml($transl);
    }
    ?></b></td>
</tr>
    <?php if ($tags != '') { ?>
<tr>
<td class="td1 right">Tags:</td>
<td class="td1" style="font-size:120%;"><b><?php echo tohtml($tags); ?></b></td>
</tr>
        <?php
    } ?>
    <?php if ($rom != '') { ?>
<tr>
<td class="td1 right">Romaniz.:</td>
<td class="td1" style="font-size:120%;"><b><?php echo tohtml($rom); ?></b></td>
</tr>
        <?php
    } ?>
<tr>
<td class="td1 right">Sentence<br />Term in {...}:</td>
<td class="td1" <?php echo $scrdir; ?>><?php echo tohtml($record['WoSentence']); ?></td>
</tr>
<tr>
<td class="td1 right">Status:</td>
<td class="td1"><?php echo get_colored_status_msg($record['WoStatus']); ?></span>
</td>
</tr>
</table>

<script type="text/javascript">
    //<![CDATA[
    cleanupRightFrames();
    //]]>
</script>

    <?php
}

mysqli_free_result($res);

pageend();

?>
