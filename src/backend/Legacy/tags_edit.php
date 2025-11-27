<?php

/**
 * Manage tags
 *
 * Call: /tags?....
 *  ... markaction=[opcode] ... do actions on marked tags
 *  ... allaction=[opcode] ... do actions on all tags
 *  ... del=[wordid] ... do delete
 *  ... op=Save ... do insert new
 *  ... op=Change ... do update
 *  ... new=1 ... display new tag screen
 *  ... chg=[wordid] ... display edit screen
 *  ... sort=[sortcode] ... sort
 *  ... page=[pageno] ... page
 *  ... query=[tagtextfilter] ... tag text filter
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package  Lwt
 */

namespace Lwt\Interface\Edit_Tags;

require_once 'Core/database_connect.php';
require_once 'Core/UI/ui_helpers.php';
require_once 'Core/Tag/tags.php';
require_once 'Core/Http/param_helpers.php';

use Lwt\Database\Settings;
use Lwt\Database\Maintenance;

$currentsort = (int) processDBParam("sort", 'currenttagsort', '1', true);

$currentpage = (int) processSessParam("page", "currenttagpage", '1', true);
$currentquery = (string) processSessParam("query", "currenttagquery", '', false);

$wh_query = convert_string_to_sqlsyntax(str_replace("*", "%", $currentquery));
$wh_query = ($currentquery != '') ? (' and (TgText like ' . $wh_query . ' or TgComment like ' . $wh_query . ')') : '';

pagestart('My Term Tags', true);

$message = '';

// MARK ACTIONS

if (isset($_REQUEST['markaction'])) {
    $markaction = $_REQUEST['markaction'];
    $message = "Multiple Actions: 0";
    if (isset($_REQUEST['marked'])) {
        if (is_array($_REQUEST['marked'])) {
            $l = count($_REQUEST['marked']);
            if ($l > 0) {
                $list = "(" . $_REQUEST['marked'][0];
                for ($i = 1; $i < $l; $i++) {
                    $list .= "," . $_REQUEST['marked'][$i];
                }
                $list .= ")";
                if ($markaction == 'del') {
                    $message = runsql(
                        'delete from ' . $tbpref . 'tags
                        where TgID in ' . $list,
                        "Deleted"
                    );
                    runsql(
                        "DELETE " . $tbpref . "wordtags FROM (
                            " . $tbpref . "wordtags LEFT JOIN " . $tbpref . "tags
                            on WtTgID = TgID
                        ) WHERE TgID IS NULL",
                        ''
                    );
                    Maintenance::adjustAutoIncrement('tags', 'TgID');
                }
            }
        }
    }
}


// ALL ACTIONS

if (isset($_REQUEST['allaction'])) {
    $allaction = $_REQUEST['allaction'];
    if ($allaction == 'delall') {
        $message = runsql('delete from ' . $tbpref . 'tags where (1=1) ' . $wh_query, "Deleted");
        runsql("DELETE " . $tbpref . "wordtags FROM (" . $tbpref . "wordtags LEFT JOIN " . $tbpref . "tags on WtTgID = TgID) WHERE TgID IS NULL", '');
        Maintenance::adjustAutoIncrement('tags', 'TgID');
    }
} elseif (isset($_REQUEST['del'])) {
    // DEL
    $message = runsql('delete from ' . $tbpref . 'tags where TgID = ' . $_REQUEST['del'], "Deleted");
    runsql("DELETE " . $tbpref . "wordtags FROM (" . $tbpref . "wordtags LEFT JOIN " . $tbpref . "tags on WtTgID = TgID) WHERE TgID IS NULL", '');
    Maintenance::adjustAutoIncrement('tags', 'TgID');
} elseif (isset($_REQUEST['op'])) {
    // INS/UPD
    // INSERT

    if ($_REQUEST['op'] == 'Save') {
        $message = runsql(
            'insert into ' . $tbpref . 'tags (TgText, TgComment) values(' .
            convert_string_to_sqlsyntax($_REQUEST["TgText"]) . ', ' .
            convert_string_to_sqlsyntax_nonull($_REQUEST["TgComment"]) . ')',
            "Saved",
            $sqlerrdie = false
        );
    } elseif ($_REQUEST['op'] == 'Change') {
        // UPDATE
        $message = runsql(
            'update ' . $tbpref . 'tags set TgText = ' .
            convert_string_to_sqlsyntax($_REQUEST["TgText"]) . ', TgComment = ' .
            convert_string_to_sqlsyntax_nonull($_REQUEST["TgComment"]) . ' where TgID = ' . $_REQUEST["TgID"],
            "Updated",
            $sqlerrdie = false
        );
    }
}

// NEW

if (isset($_REQUEST['new'])) {
    ?>
    <h2>New Tag</h2>
    <script type="text/javascript" charset="utf-8">
        $(document).ready(lwtFormCheck.askBeforeExit);
    </script>
    <form name="newtag" class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
    <table class="tab1" cellspacing="0" cellpadding="5">
        <tr>
            <td class="td1 right">Tag:</td>
            <td class="td1">
                <input class="notempty setfocus noblanksnocomma checkoutsidebmp respinput"
                type="text" name="TgText" data_info="Tag" value="" maxlength="20" size="20" />
                <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
            </td>
        </tr>
        <tr>
            <td class="td1 right">Comment:</td>
            <td class="td1">
                <textarea class="textarea-noreturn checklength checkoutsidebmp respinput"
                data_maxlength="200" data_info="Comment" name="TgComment" cols="40" rows="3"></textarea>
            </td>
        </tr>
        <tr>
            <td class="td1 right" colspan="2">
                <input type="button" value="Cancel" onclick="{lwtFormCheck.resetDirty(); location.href='/tags';}" />
                <input type="submit" name="op" value="Save" />
            </td>
        </tr>
    </table>
    </form>

    <?php
} elseif (isset($_REQUEST['chg'])) {
    // CHG
    $sql = 'select * from ' . $tbpref . 'tags where TgID = ' . $_REQUEST['chg'];
    $res = do_mysqli_query($sql);
    if ($record = mysqli_fetch_assoc($res)) {
        ?>
     <h2>Edit Tag</h2>
     <script type="text/javascript" charset="utf-8">
         $(document).ready(lwtFormCheck.askBeforeExit);
     </script>
     <form name="edittag" class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>#rec<?php echo $_REQUEST['chg']; ?>" method="post">
     <input type="hidden" name="TgID" value="<?php echo $record['TgID']; ?>" />
     <table class="tab1" cellspacing="0" cellpadding="5">
        <tr>
            <td class="td1 right">Tag:</td>
            <td class="td1">
                <input data_info="Tag" class="notempty setfocus noblanksnocomma checkoutsidebmp respinput"
                type="text" name="TgText" value="<?php echo tohtml($record['TgText']); ?>" maxlength="20" size="20" />
                <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
        </tr>
        <tr>
            <td class="td1 right">Comment:</td>
            <td class="td1">
                <textarea class="textarea-noreturn checklength checkoutsidebmp respinput"
                data_maxlength="200" data_info="Comment" name="TgComment" rows="3"><?php echo tohtml($record['TgComment']); ?></textarea>
            </td>
        </tr>
        <tr>
        <td class="td1 right" colspan="2">
            <input type="button" value="Cancel" onclick="{lwtFormCheck.resetDirty(); location.href='/tags#rec<?php echo $_REQUEST['chg']; ?>';}" />
            <input type="submit" name="op" value="Change" /></td>
        </tr>
    </table>
    </form>
        <?php
    }
    mysqli_free_result($res);
} else {
    // DISPLAY

    if (
        substr($message, 0, 24) == "Error: Duplicate entry '"
        && substr($message, -18) == "' for key 'TgText'"
    ) {
        $message = substr($message, 24);
        $message = substr($message, 0, strlen($message) - 18);
        $message = "Error: Term Tag '" . $message .
        "' already exists. Please go back and correct this!";
    }
    echo error_message_with_hide($message, false);

    get_tags($refresh = 1);   // refresh tags cache

    $sql = 'select count(TgID) as value from ' . $tbpref . 'tags where (1=1) ' . $wh_query;
    $recno = (int) get_first_value($sql);
    if ($debug) {
        echo $sql . ' ===&gt; ' . $recno;
    }

    $maxperpage = (int) Settings::getWithDefault('set-tags-per-page');

    $pages = $recno == 0 ? 0 : (intval(($recno - 1) / $maxperpage) + 1);

    if ($currentpage < 1) {
        $currentpage = 1;
    }
    if ($currentpage > $pages) {
        $currentpage = $pages;
    }
    $limit = 'LIMIT ' . (($currentpage - 1) * $maxperpage) . ',' . $maxperpage;

    $sorts = array('TgText','TgComment','TgID desc','TgID asc');
    $lsorts = count($sorts);
    if ($currentsort < 1) {
        $currentsort = 1;
    }
    if ($currentsort > $lsorts) {
        $currentsort = $lsorts;
    }

    ?>
<p><a href="<?php echo $_SERVER['PHP_SELF']; ?>?new=1"><img src="/assets/icons/plus-button.png" title="New" alt="New" /> New Term Tag ...</a></p>

<form name="form1" action="#" onsubmit="document.form1.querybutton.click(); return false;">
<table class="tab2" cellspacing="0" cellpadding="5">
<tr>
<th class="th1" colspan="4">Filter <img src="/assets/icons/funnel.png" title="Filter" alt="Filter" />&nbsp;
<input type="button" value="Reset All" onclick="{location.href='/tags?page=1&amp;query=';}" /></th>
</tr>
<tr>
<td class="td1 center" colspan="4">
Tag Text or Comment:
<input type="text" name="query" value="<?php echo tohtml($currentquery); ?>" maxlength="50" size="15" />&nbsp;
<input type="button" name="querybutton" value="Filter" onclick="{val=document.form1.query.value; location.href='/tags?page=1&amp;query=' + val;}" />&nbsp;
<input type="button" value="Clear" onclick="{location.href='/tags?page=1&amp;query=';}" />
</td>
</tr>
    <?php if ($recno > 0) { ?>
<tr>
<th class="th1" colspan="1" nowrap="nowrap">
        <?php echo $recno; ?> Tag<?php echo ($recno == 1 ? '' : 's'); ?>
</th><th class="th1" colspan="2" nowrap="nowrap">
        <?php makePager($currentpage, $pages, '/tags', 'form1'); ?>
</th><th class="th1" nowrap="nowrap">
Sort Order:
<select name="sort" onchange="{val=document.form1.sort.options[document.form1.sort.selectedIndex].value; location.href='/tags?page=1&amp;sort=' + val;}"><?php echo get_tagsort_selectoptions($currentsort); ?></select>
</th></tr>
        <?php
    } ?>
</table>
</form>

    <?php
    if ($recno == 0) {
        ?>
<p>No tags found.</p>
        <?php
    } else {
        ?>
<form name="form2" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<input type="hidden" name="data" value="" />
<table class="tab2" cellspacing="0" cellpadding="5">
<tr><th class="th1 center" colspan="2">
Multi Actions <img src="/assets/icons/lightning.png" title="Multi Actions" alt="Multi Actions" />
</th></tr>
<tr><td class="td1 center" colspan="2">
<b>ALL</b> <?php echo ($recno == 1 ? '1 Tag' : $recno . ' Tags'); ?>:&nbsp;
<select name="allaction" onchange="allActionGo(document.form2, document.form2.allaction,<?php echo $recno; ?>);"><?php echo get_alltagsactions_selectoptions(); ?></select>
</td></tr>
<tr><td class="td1 center">
<input type="button" value="Mark All" onclick="selectToggle(true,'form2');" />
<input type="button" value="Mark None" onclick="selectToggle(false,'form2');" />
</td>
<td class="td1 center">Marked Tags:&nbsp;
<select name="markaction" id="markaction" disabled="disabled" onchange="multiActionGo(document.form2, document.form2.markaction);"><?php echo get_multipletagsactions_selectoptions(); ?></select>
</td></tr></table>

<table class="sortable tab2"  cellspacing="0" cellpadding="5">
<tr>
<th class="th1 sorttable_nosort">Mark</th>
<th class="th1 sorttable_nosort">Actions</th>
<th class="th1 clickable">Tag Text</th>
<th class="th1 clickable">Tag Comment</th>
<th class="th1 clickable">Terms With Tag</th>
</tr>

        <?php

        $sql = 'select TgID, TgText, TgComment from ' . $tbpref . 'tags where (1=1) ' . $wh_query . ' order by ' . $sorts[$currentsort - 1] . ' ' . $limit;
        if ($debug) {
            echo $sql;
        }
        $res = do_mysqli_query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            $c = get_first_value('select count(*) as value from ' . $tbpref . 'wordtags where WtTgID=' . $record['TgID']);
            echo '<tr>
                <td class="td1 center">
                    <a name="rec' . $record['TgID'] . '">
                    <input name="marked[]" type="checkbox" class="markcheck" value="' . $record['TgID'] . '" ' . checkTest($record['TgID'], 'marked') . ' />
                    </a></td>
                    <td class="td1 center" nowrap="nowrap">&nbsp;<a href="' . $_SERVER['PHP_SELF'] . '?chg=' . $record['TgID'] . '"><img src="/assets/icons/document--pencil.png" title="Edit" alt="Edit" /></a>&nbsp; <a class="confirmdelete" href="' . $_SERVER['PHP_SELF'] . '?del=' . $record['TgID'] . '"><img src="/assets/icons/minus-button.png" title="Delete" alt="Delete" /></a>&nbsp;</td>
                    <td class="td1 center">' . tohtml($record['TgText']) . '</td>
                    <td class="td1 center">' . tohtml($record['TgComment']) . '</td>
                    <td class="td1 center">' . ($c > 0 ? '<a href="edit_words.php?page=1&amp;query=&amp;text=&amp;status=&amp;filterlang=&amp;status=&amp;tag12=0&amp;tag2=&amp;tag1=' . $record['TgID'] . '">' . $c . '</a>' : '0' ) . '</td>
                </tr>';
        }
        mysqli_free_result($res);

        ?>
</table>


        <?php if ($pages > 1) { ?>
<table class="tab2" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1" nowrap="nowrap">
            <?php echo $recno; ?> Tag<?php echo ($recno == 1 ? '' : 's'); ?>
        </th>
        <th class="th1" nowrap="nowrap">
            <?php makePager($currentpage, $pages, '/tags', 'form2'); ?>
        </th>
    </tr>
</table>
</form>
            <?php
        } ?>

        <?php
    }
}

pageend();

?>
