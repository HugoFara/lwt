<?php

/**
 * Manage archived texts
 *
 * Call: /text/archived?....
 *  ... markaction=[opcode] ... do actions on marked texts
 *  ... del=[textid] ... do delete
 *  ... unarch=[textid] ... do unarchive
 *  ... op=Change ... do update
 *  ... chg=[textid] ... display edit screen
 *  ... filterlang=[langid] ... language filter
 *  ... sort=[sortcode] ... sort
 *  ... page=[pageno] ... page
 *  ... query=[titlefilter] ... title filter
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package  Lwt
 */

require_once 'Core/Bootstrap/db_bootstrap.php';
require_once 'Core/UI/ui_helpers.php';
require_once 'Core/Tag/tags.php';
require_once 'Core/Text/text_helpers.php';
require_once 'Core/Http/param_helpers.php';
require_once 'Core/Language/language_utilities.php';

use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Database\Validation;
use Lwt\Database\Settings;
use Lwt\Database\Maintenance;
use Lwt\Database\TextParsing;

$currentlang = Validation::language((string) processDBParam("filterlang", 'currentlanguage', '', false));
$currentsort = (int) processDBParam("sort", 'currentarchivesort', '1', true);

$currentpage = (int) processSessParam("page", "currentarchivepage", '1', true);
$currentquery = (string) processSessParam("query", "currentarchivequery", '', false);
$currentquerymode = (string) processSessParam(
    "query_mode",
    "currentarchivequerymode",
    'title,text',
    false
);
$currentregexmode = Settings::getWithDefault("set-regex-mode");
$currenttag1 = Validation::archTextTag(
    (string) processSessParam("tag1", "currentarchivetexttag1", '', false),
    $currentlang
);
$currenttag2 = Validation::archTextTag(
    (string) processSessParam("tag2", "currentarchivetexttag2", '', false),
    $currentlang
);
$currenttag12 = (string) processSessParam(
    "tag12",
    "currentarchivetexttag12",
    '',
    false
);

$wh_lang = ($currentlang != '') ? (' and AtLgID=' . $currentlang) : '';
$wh_query = $currentregexmode . 'LIKE ' .
Escaping::toSqlSyntax(
    ($currentregexmode == '') ?
    str_replace("*", "%", mb_strtolower($currentquery, 'UTF-8')) :
    $currentquery
);
switch ($currentquerymode) {
    case 'title,text':
        $wh_query = ' and (AtTitle ' . $wh_query . ' or AtText ' . $wh_query . ')';
        break;
    case 'title':
        $wh_query = ' and (AtTitle ' . $wh_query . ')';
        break;
    case 'text':
        $wh_query = ' and (AtText ' . $wh_query . ')';
        break;
}
if ($currentquery !== '') {
    if ($currentregexmode !== '') {
        if (
            @mysqli_query(
                $GLOBALS["DBCONNECTION"],
                'select "test" rlike ' . Escaping::toSqlSyntax($currentquery)
            ) === false
        ) {
            $currentquery = '';
            $wh_query = '';
            unset($_SESSION['currentwordquery']);
            if (isset($_REQUEST['query'])) {
                echo '<p id="hide3" style="color:red;text-align:center;">' .
                '+++ Warning: Invalid Search +++</p>';
            }
        }
    }
} else {
    $wh_query = '';
}

$wh_tag1 = null;
$wh_tag2 = null;
if ($currenttag1 == '' && $currenttag2 == '') {
    $wh_tag = '';
} else {
    if ($currenttag1 != '') {
        if ($currenttag1 == -1) {
            $wh_tag1 = "group_concat(AgT2ID) IS NULL";
        } else {
            $wh_tag1 = "concat('/',group_concat(AgT2ID separator '/'),'/') like '%/" .
            $currenttag1 . "/%'";
        }
    }
    if ($currenttag2 != '') {
        if ($currenttag2 == -1) {
            $wh_tag2 = "group_concat(AgT2ID) IS NULL";
        } else {
            $wh_tag2 = "concat('/',group_concat(AgT2ID separator '/'),'/') like '%/" .
            $currenttag2 . "/%'";
        }
    }
    if ($currenttag1 != '' && $currenttag2 == '') {
        $wh_tag = " having (" . $wh_tag1 . ') ';
    } elseif ($currenttag2 != '' && $currenttag1 == '') {
        $wh_tag = " having (" . $wh_tag2 . ') ';
    } else {
        $wh_tag = " having ((" . $wh_tag1 . ($currenttag12 ? ') AND (' : ') OR (') .
        $wh_tag2 . ')) ';
    }
}

$no_pagestart =
    (getreq('markaction') == 'deltag');
if (!$no_pagestart) {
    pagestart('My ' . getLanguage($currentlang) . ' Text Archive', true);
}

$message = '';

// MARK ACTIONS

$id = null;
if (isset($_REQUEST['markaction'])) {
    $markaction = $_REQUEST['markaction'];
    $actiondata = getreq('data');
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
                    $message = Connection::execute(
                        'delete from ' . $tbpref . 'archivedtexts
                        where AtID in ' . $list,
                        "Archived Texts deleted"
                    );
                    Maintenance::adjustAutoIncrement('archivedtexts', 'AtID');
                    Connection::execute(
                        "DELETE " . $tbpref . "archtexttags
                        FROM (
                            " . $tbpref . "archtexttags
                            LEFT JOIN " . $tbpref . "archivedtexts
                            on AgAtID = AtID
                        )
                        WHERE AtID IS NULL",
                        ''
                    );
                } elseif ($markaction == 'addtag') {
                    $message = addarchtexttaglist($actiondata, $list);
                } elseif ($markaction == 'deltag') {
                    removearchtexttaglist($actiondata, $list);
                    header("Location: /text/archived");
                    exit();
                } elseif ($markaction == 'unarch') {
                    $count = 0;
                    $sql = "select AtID, AtLgID
                    from " . $tbpref . "archivedtexts
                    where AtID in " . $list;
                    $res = Connection::query($sql);
                    while ($record = mysqli_fetch_assoc($res)) {
                        $ida = $record['AtID'];
                        $mess = (int)Connection::execute(
                            'insert into ' . $tbpref . 'texts (
                                TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI,
                                TxSourceURI
                            )
                            select AtLgID, AtTitle, AtText, AtAnnotatedText,
                            AtAudioURI, AtSourceURI
                            from ' . $tbpref . 'archivedtexts
                            where AtID = ' . $ida,
                            ""
                        );
                        $count += $mess;
                        $id = get_last_key();
                        Connection::execute(
                            'insert into ' . $tbpref . 'texttags (TtTxID, TtT2ID)
                            select ' . $id . ', AgT2ID
                            from ' . $tbpref . 'archtexttags
                            where AgAtID = ' . $ida,
                            ""
                        );
                        TextParsing::splitCheck(
                            Connection::fetchValue(
                                'select TxText as value
                                from ' . $tbpref . 'texts
                                where TxID = ' . $id
                            ),
                            $record['AtLgID'],
                            $id
                        );
                        Connection::execute(
                            'delete from ' . $tbpref . 'archivedtexts
                            where AtID = ' . $ida,
                            ""
                        );
                    }
                    mysqli_free_result($res);
                    Maintenance::adjustAutoIncrement('archivedtexts', 'AtID');
                    Connection::execute(
                        "DELETE " . $tbpref . "archtexttags
                        FROM (
                            " . $tbpref . "archtexttags
                            LEFT JOIN " . $tbpref . "archivedtexts
                            on AgAtID = AtID
                        )
                        WHERE AtID IS NULL",
                        ''
                    );
                    $message = 'Unarchived Text(s): ' . $count;
                }
            }
        }
    }
}


if (isset($_REQUEST['del'])) {
    // DEL
    $message = Connection::execute(
        'delete from ' . $tbpref . 'archivedtexts where AtID = ' . $_REQUEST['del'],
        "Archived Texts deleted"
    );
    Maintenance::adjustAutoIncrement('archivedtexts', 'AtID');
    Connection::execute(
        "DELETE " . $tbpref . "archtexttags
        FROM (
            " . $tbpref . "archtexttags
            LEFT JOIN " . $tbpref . "archivedtexts on AgAtID = AtID
        )
        WHERE AtID IS NULL",
        ''
    );
} elseif (isset($_REQUEST['unarch'])) {
    // UNARCH
    $message2 = Connection::execute(
        'insert into ' . $tbpref . 'texts (
            TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI
        ) select AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI
        from ' . $tbpref . 'archivedtexts
        where AtID = ' . $_REQUEST['unarch'],
        "Texts added"
    );
    $id = get_last_key();
    Connection::execute(
        'insert into ' . $tbpref . 'texttags (TtTxID, TtT2ID)
        select ' . $id . ', AgT2ID
        from ' . $tbpref . 'archtexttags
        where AgAtID = ' . $_REQUEST['unarch'],
        ""
    );
    TextParsing::splitCheck(
        Connection::fetchValue(
            'select TxText as value from ' . $tbpref . 'texts where TxID = ' . $id
        ),
        Connection::fetchValue(
            'select TxLgID as value from ' . $tbpref . 'texts where TxID = ' . $id
        ),
        $id
    );
    $message1 = Connection::execute(
        'delete from ' . $tbpref . 'archivedtexts
        where AtID = ' . $_REQUEST['unarch'],
        "Archived Texts deleted"
    );
    $message = $message1 . " / " . $message2 . " / Sentences added: " .
    Connection::fetchValue(
        'select count(*) as value
        from ' . $tbpref . 'sentences
        where SeTxID = ' . $id
    ) . " / Text items added: " . Connection::fetchValue(
        'select count(*) as value from ' . $tbpref . 'textitems2
        where Ti2TxID = ' . $id
    );
    Maintenance::adjustAutoIncrement('archivedtexts', 'AtID');
    Connection::execute(
        "DELETE " . $tbpref . "archtexttags
        FROM (" . $tbpref . "archtexttags
        LEFT JOIN " . $tbpref . "archivedtexts on AgAtID = AtID)
        WHERE AtID IS NULL",
        ''
    );
} elseif (isset($_REQUEST['op'])) {
    // UPD
    if ($_REQUEST['op'] == 'Change') {
        // UPDATE
        $oldtext = Connection::fetchValue(
            'select AtText as value
            from ' . $tbpref . 'archivedtexts
            where AtID = ' . $_REQUEST["AtID"]
        );
        $textsdiffer = (Escaping::toSqlSyntax($_REQUEST["AtText"]) !=
        Escaping::toSqlSyntax($oldtext));
        $message = Connection::execute(
            'UPDATE ' . $tbpref . 'archivedtexts SET ' .
            'AtLgID = ' . $_REQUEST["AtLgID"] . ', ' .
            'AtTitle = ' . Escaping::toSqlSyntax($_REQUEST["AtTitle"]) . ', ' .
            'AtText = ' . Escaping::toSqlSyntax($_REQUEST["AtText"]) . ', ' .
            'AtAudioURI = ' . Escaping::toSqlSyntax($_REQUEST["AtAudioURI"]) .
            ', ' .
            'AtSourceURI = ' . Escaping::toSqlSyntax($_REQUEST["AtSourceURI"]) .
            ' WHERE AtID = ' . $_REQUEST["AtID"],
            "Updated"
        );
        if ($message == 'Updated: 1' && $textsdiffer) {
            Connection::execute(
                "update " . $tbpref . "archivedtexts set
                AtAnnotatedText = ''
                where AtID = " . $_REQUEST["AtID"],
                ""
            );
        }
        $id = $_REQUEST["AtID"];
    }
    saveArchivedTextTags($id);
}


if (isset($_REQUEST['chg'])) {
    // CHG
    $sql = 'select AtLgID, AtTitle, AtText, AtAudioURI, AtSourceURI,
    length(AtAnnotatedText) as annotlen
    from ' . $tbpref . 'archivedtexts
    where AtID = ' . $_REQUEST['chg'];
    $res = Connection::query($sql);
    if ($record = mysqli_fetch_assoc($res)) {
        ?>

     <script type="text/javascript" charset="utf-8">
         $(document).ready(lwtFormCheck.askBeforeExit);
     </script>
     <h2>Edit Archived Text</h2>
     <form class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>#rec<?php echo $_REQUEST['chg']; ?>" method="post">
     <input type="hidden" name="AtID" value="<?php echo $_REQUEST['chg']; ?>" />
     <table class="tab3" cellspacing="0" cellpadding="5">
        <tr>
            <td class="td1 right">Language:</td>
            <td class="td1">
            <select name="AtLgID" class="notempty setfocus">
                <?php
                echo get_languages_selectoptions($record['AtLgID'], "[Choose...]");
                ?>
            </select>
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
        </tr>
        <tr>
            <td class="td1 right">Title:</td>
            <td class="td1"><input type="text" class="notempty checkoutsidebmp" data_info="Title" name="AtTitle" value="<?php echo tohtml($record['AtTitle']); ?>" maxlength="200" size="60" /> <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
        </tr>
        <tr>
            <td class="td1 right">Text:</td>
            <td class="td1">
            <textarea name="AtText" class="notempty checkbytes checkoutsidebmp" data_maxlength="65000" data_info="Text" cols="60" rows="20"><?php echo tohtml($record['AtText']); ?></textarea> <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
        </tr>
        <tr>
            <td class="td1 right">Ann.Text:</td>
            <td class="td1">
                <?php echo ($record['annotlen'] ? '<img src="/assets/icons/tick.png" title="With Annotation" alt="With Annotation" /> Exists - May be partially or fully lost if you change the text!' : '<img src="/assets/icons/cross.png" title="No Annotation" alt="No Annotation" /> - None'); ?>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Source URI:</td>
            <td class="td1"><input type="text" class="checkurl checkoutsidebmp" data_info="Source URI" name="AtSourceURI" value="<?php echo tohtml($record['AtSourceURI']); ?>" maxlength="1000" size="60" /></td>
        </tr>
        <tr>
            <td class="td1 right">Tags:</td>
            <td class="td1">
                <?php echo getArchivedTextTags((int) $_REQUEST['chg']); ?>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Audio-URI:</td>
            <td class="td1">
                <input type="text" class="checkoutsidebmp" data_info="Audio-URI" name="AtAudioURI" value="<?php echo tohtml($record['AtAudioURI']); ?>" maxlength="200" size="60" />
                <span id="mediaselect"><?php echo selectmediapath('AtAudioURI'); ?></span>
            </td>
        </tr>
        <tr>
            <td class="td1 right" colspan="2">
                <input type="button" value="Cancel" onclick="{lwtFormCheck.resetDirty(); location.href='/text/archived#rec<?php echo $_REQUEST['chg']; ?>';}" />
                <input type="submit" name="op" value="Change" />
            </td>
        </tr>
     </table>
     </form>

        <?php
    }
    mysqli_free_result($res);
} else {
    // DISPLAY

    echo error_message_with_hide($message, false);

    $sql = 'select count(*) as value from (select AtID
    from (
        ' . $tbpref . 'archivedtexts
        left JOIN ' . $tbpref . 'archtexttags
        ON AtID = AgAtID
    ) where (1=1) ' . $wh_lang . $wh_query . '
    group by AtID ' . $wh_tag . ') as dummy';
    $recno = (int)Connection::fetchValue($sql);
    if ($debug) {
        echo $sql . ' ===&gt; ' . $recno;
    }

    $maxperpage = (int)Settings::getWithDefault('set-archivedtexts-per-page');

    $pages = $recno == 0 ? 0 : intval(($recno - 1) / $maxperpage) + 1;

    if ($currentpage < 1) {
        $currentpage = 1;
    }
    if ($currentpage > $pages) {
        $currentpage = $pages;
    }
    $limit = 'LIMIT ' . (($currentpage - 1) * $maxperpage) . ',' . $maxperpage;

    $sorts = array('AtTitle','AtID desc','AtID');
    $lsorts = count($sorts);
    if ($currentsort < 1) {
        $currentsort = 1;
    }
    if ($currentsort > $lsorts) {
        $currentsort = $lsorts;
    }

    ?>


<div class="flex-spaced">
    <div>
        <a href="/texts?new=1">
            <img src="/assets/icons/plus-button.png">
            New Text
        </a>
    </div>
    <div>
        <a href="/text/import-long">
            <img src="/assets/icons/plus-button.png">
            Long Text Import
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
</div>
<form name="form1" action="#" onsubmit="document.form1.querybutton.click(); return false;">
<table class="tab2" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1" colspan="4">Filter <img src="/assets/icons/funnel.png" title="Filter" alt="Filter" />&nbsp;
            <input type="button" value="Reset All" onclick="resetAll('/text/archived');" />
        </th>
    </tr>
    <tr>
        <td class="td1 center" colspan="2">
            Language:
            <select name="filterlang" onchange="{setLang(document.form1.filterlang,'/text/archived');}">
                <?php echo get_languages_selectoptions($currentlang, '[Filter off]'); ?>
            </select>
        </td>
        <td class="td1 center" colspan="2">
            <select name="query_mode" onchange="{val=document.form1.query.value;mode=document.form1.query_mode.value; location.href='/text/archived?page=1&amp;query=' + val + '&amp;query_mode=' + mode;}">
                <option value="title,text"<?php
                if ($currentquerymode == "title,text") {
                    echo ' selected="selected"';
                } ?>>Title &amp; Text</option>
                <option disabled="disabled">------------</option>
                <option value="title"<?php
                if ($currentquerymode == "title") {
                    echo ' selected="selected"';
                } ?>>Title</option>
                <option value="text"<?php
                if ($currentquerymode == "text") {
                    echo ' selected="selected"';
                } ?>>Text</option>
            </select>
            <?php
            if ($currentregexmode == '') {
                echo '<span style="vertical-align: middle"> (Wildc.=*): </span>';
            } elseif ($currentregexmode == 'r') {
                echo '<span style="vertical-align: middle"> RegEx Mode: </span>';
            } else {
                echo '<span style="vertical-align: middle"> RegEx(CS) Mode: </span>';
            }?>
            <input type="text" name="query" value="<?php echo tohtml($currentquery); ?>" maxlength="50" size="15" />&nbsp;
            <input type="button" name="querybutton" value="Filter" onclick="{val=document.form1.query.value;val=encodeURIComponent(val); location.href='/text/archived?page=1&amp;query=' + val;}" />&nbsp;
            <input type="button" value="Clear" onclick="{location.href='/text/archived?page=1&amp;query=';}" />
        </td>
    </tr>
    <tr>
        <td class="td1 center" colspan="2" nowrap="nowrap">
            Tag #1:
            <select name="tag1" onchange="{val=document.form1.tag1.options[document.form1.tag1.selectedIndex].value; location.href='/text/archived?page=1&amp;tag1=' + val;}"><?php echo get_archivedtexttag_selectoptions($currenttag1, $currentlang); ?></select>
        </td>
        <td class="td1 center" nowrap="nowrap">
            Tag #1 ..
            <select name="tag12" onchange="{val=document.form1.tag12.options[document.form1.tag12.selectedIndex].value; location.href='/text/archived?page=1&amp;tag12=' + val;}"><?php echo get_andor_selectoptions($currenttag12); ?></select> .. Tag #2
        </td>
        <td class="td1 center" nowrap="nowrap">
            Tag #2:
            <select name="tag2" onchange="{val=document.form1.tag2.options[document.form1.tag2.selectedIndex].value; location.href='/text/archived?page=1&amp;tag2=' + val;}"><?php echo get_archivedtexttag_selectoptions($currenttag2, $currentlang); ?></select>
        </td>
    </tr>
        <?php if ($recno > 0) { ?>
    <tr>
    <th class="th1" colspan="2" nowrap="nowrap">
            <?php echo $recno; ?> Text<?php echo ($recno == 1 ? '' : 's'); ?>
    </th>
    <th class="th1" colspan="1" nowrap="nowrap">
            <?php makePager($currentpage, $pages, '/text/archived', 'form1'); ?>
    </th>
    <th class="th1" nowrap="nowrap">
    Sort Order:
    <select name="sort" onchange="{val=document.form1.sort.options[document.form1.sort.selectedIndex].value; location.href='/text/archived?page=1&amp;sort=' + val;}"><?php echo get_textssort_selectoptions($currentsort); ?></select>
    </th></tr>
            <?php
        } ?>
</table>
</form>

    <?php
    if ($recno == 0) {
        ?>
<p>No archived texts found.</p>
        <?php
    } else {
        ?>
<form name="form2" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<input type="hidden" name="data" value="" />
<table class="tab2" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1" colspan="2">
            Multi Actions
            <img src="/assets/icons/lightning.png" title="Multi Actions" alt="Multi Actions" />
        </th>
    </tr>
    <tr>
        <td class="td1 center">
            <input type="button" value="Mark All" onclick="selectToggle(true,'form2');" />
            <input type="button" value="Mark None" onclick="selectToggle(false,'form2');" />
        </td>
        <td class="td1 center">
            Marked Texts:&nbsp;
            <select name="markaction" id="markaction" disabled="disabled" onchange="multiActionGo(document.form2, document.form2.markaction);"><?php echo get_multiplearchivedtextactions_selectoptions(); ?></select>
        </td>
    </tr>
</table>

<table class="sortable tab2" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1 sorttable_nosort">Mark</th>
        <th class="th1 sorttable_nosort">Actions</th>
        <?php if ($currentlang == '') {
            echo '<th class="th1 clickable">Lang.</th>';
        } ?>
        <th class="th1 clickable">
            Title [Tags] / Audio:&nbsp;
            <img src="<?php print_file_path('icn/speaker-volume.png'); ?>" title="With Audio" alt="With Audio" />, Src.Link:&nbsp;
            <img src="<?php print_file_path('icn/chain.png'); ?>" title="Source Link available" alt="Source Link available" />, Ann.Text:&nbsp;
            <img src="/assets/icons/tick.png" title="Annotated Text available" alt="Annotated Text available" />
        </th>
    </tr>
        <?php

        $sql = "SELECT AtID, AtTitle, LgName, AtAudioURI, AtSourceURI,
        length(AtAnnotatedText) as annotlen,
        IF(
            COUNT(T2Text)=0,
            '',
            CONCAT(
                '[',group_concat(DISTINCT T2Text ORDER BY T2Text separator ', '),']'
            )
        ) AS taglist
        from (
            (
                {$tbpref}archivedtexts
                left JOIN {$tbpref}archtexttags
                ON AtID = AgAtID
            ) left join {$tbpref}tags2
            on T2ID = AgT2ID
        ), {$tbpref}languages
        where LgID=AtLgID $wh_lang$wh_query
        group by AtID $wh_tag
        order by {$sorts[$currentsort-1]}
        $limit";

        if ($debug) {
            echo $sql;
        }

        $res = Connection::query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            echo '<tr>
            <td class="td1 center">
            <a name="rec' . $record['AtID'] . '">
            <input name="marked[]" class="markcheck" type="checkbox" value="' .
            $record['AtID'] . '" ' . checkTest($record['AtID'], 'marked') .
            ' /></a></td>
            <td nowrap="nowrap" class="td1 center">&nbsp;
            <a href="' . $_SERVER['PHP_SELF'] . '?unarch=' . $record['AtID'] . '">
            <img src="/assets/icons/inbox-upload.png" title="Unarchive" alt="Unarchive" />
            </a>&nbsp;
            <a href="' . $_SERVER['PHP_SELF'] . '?chg=' . $record['AtID'] . '">
            <img src="/assets/icons/document--pencil.png" title="Edit" alt="Edit" /></a>&nbsp;
            <span class="click" onclick="if (confirmDelete()) location.href=\''
            . $_SERVER['PHP_SELF'] . '?del=' . $record['AtID'] . '\';">
            <img src="/assets/icons/minus-button.png" title="Delete" alt="Delete" />
            </span>&nbsp;</td>';
            if ($currentlang == '') {
                echo '<td class="td1 center">' . tohtml($record['LgName']) . '</td>';
            }
            echo '<td class="td1 center">' . tohtml($record['AtTitle']) .
            ' <span class="smallgray2">' . tohtml($record['taglist']) . '</span> &nbsp;';
            if (isset($record['AtAudioURI'])) {
                echo '<img src="' . get_file_path('assets/icons/speaker-volume.png') .
                '" title="With Audio" alt="With Audio" />';
            } else {
                echo '';
            }
            if (isset($record['AtSourceURI'])) {
                echo ' <a href="' . $record['AtSourceURI'] . '" target="_blank">
                <img src="' . get_file_path('assets/icons/chain.png') .
                '" title="Link to Text Source" alt="Link to Text Source" /></a>';
            }
            if ($record['annotlen']) {
                echo ' <img src="/assets/icons/tick.png" title="Annotated Text available" ' .
                'alt="Annotated Text available" />';
            }
            echo '</td>';
            echo '</tr>';
        }
        mysqli_free_result($res);
        ?>
</table>


        <?php if ($pages > 1) { ?>
<table class="tab2" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1" nowrap="nowrap">
            <?php echo $recno; ?> Text<?php echo ($recno == 1 ? '' : 's'); ?>
        </th>
        <th class="th1" nowrap="nowrap">
            <?php makePager($currentpage, $pages, '/text/archived', 'form2'); ?>
        </th>
    </tr>
</table>
</form>
            <?php
        }
    }
}

pageend();

?>
