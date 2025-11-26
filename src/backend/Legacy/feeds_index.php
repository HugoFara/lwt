<?php

/**
 * \file
 * \brief Prepare RSS feeds.
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package  Lwt
 * @author   andreask7 <andreask7@users.noreply.github.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    1.6.0-fork
 * @since    2.7.1-fork Functional refactoring
 */

namespace Lwt\Interface\Do_Feeds;

require_once 'Core/session_utility.php';


/**
 * @return (int|string)[]
 *
 * @psalm-return list{0|1, string}
 */
function dummy_function_1(): array
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $edit_text = 0;
    $message = '';
    if (is_array($_REQUEST['marked_items'])) {
        $marked_items = implode(',', array_filter($_REQUEST['marked_items'], 'is_scalar'));
    } else {
        $marked_items = $_REQUEST['marked_items'];
    }
    $res = do_mysqli_query(
        "SELECT * FROM (
            SELECT * FROM {$tbpref}feedlinks
            WHERE FlID IN ($marked_items)
            ORDER BY FlNfID
        ) A
        left join {$tbpref}newsfeeds ON NfID=FlNfID"
    );
    $count = $message1 = $message2 = $message3 = $message4 = 0;
    while ($row = mysqli_fetch_assoc($res)) {
        if (get_nf_option($row['NfOptions'], 'edit_text') == 1) {
            if ($edit_text == 1) {
                $count++;
            } else {
                echo '<form class="validate" action="/feeds" method="post">';
                $edit_text = 1;
            }
        }
        $doc = array();
        $doc[0] = array(
            'link' => empty($row['FlLink']) ? ('#' . $row['FlID']) : $row['FlLink'],
            'title' => $row['FlTitle'],
            'audio' => $row['FlAudio'],
            'text' => $row['FlText']
        );
        $NfName = (string) $row['NfName'];
        $nf_id = (int) $row['NfID'];
        $nf_options = $row['NfOptions'];
        if (!$nf_tag_name = get_nf_option($nf_options, 'tag')) {
            $nf_tag_name = mb_substr($NfName, 0, 20, "utf-8");
        }
        if (!$nf_max_texts = (int)get_nf_option($nf_options, 'max_texts')) {
            $nf_max_texts = (int)getSettingWithDefault('set-max-texts-per-feed');
        }
        $texts = get_text_from_rsslink(
            $doc,
            $row['NfArticleSectionTags'],
            $row['NfFilterTags'],
            get_nf_option($nf_options, 'charset')
        );
        if (isset($texts['error'])) {
            echo $texts['error']['message'];
            foreach ($texts['error']['link'] as $err_links) {
                runsql(
                    'UPDATE ' . $tbpref . 'feedlinks
                    SET FlLink=CONCAT(" ",FlLink)
                    where FlLink in (' . convert_string_to_sqlsyntax($err_links) . ')',
                    ""
                );
            }
            unset($texts['error']);
        }

        if (get_nf_option($nf_options, 'edit_text') == 1) {
            foreach ($texts as $text) {
                ?>
<table class="tab3" cellspacing="0" cellpadding="5">
    <tr>
        <td class="td1 right">
            <input class="markcheck" type="checkbox" name="Nf_count[<?php echo $count; ?>]" value="<?php echo $count; ?>" checked="checked" />
            &nbsp; &nbsp; &nbsp; Title:
        </td>
        <td class="td1">
            <input type="text" class="notempty" name="feed[<?php echo $count; ?>][TxTitle]" value="<?php echo tohtml($text['TxTitle']); ?>" maxlength="200" size="60" />
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">Language:</td>
        <td class="td1">
            <select name="feed[<?php echo $count; ?>][TxLgID]" class="notempty setfocus">
                <?php
                $result = do_mysqli_query(
                    "SELECT LgName,LgID FROM " . $tbpref . "languages
                    where LgName<>'' ORDER BY LgName"
                );
                while ($row_l = mysqli_fetch_assoc($result)) {
                    echo '<option value="' . $row_l['LgID'] . '"';
                    if ($row['NfLgID'] === $row_l['LgID']) {
                        echo ' selected="selected"';
                    }
                    echo '>' . $row_l['LgName'] . '</option>';
                }
                mysqli_free_result($result);
                ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="td1 right">Text:</td>
        <td class="td1">
            <textarea
                <?php echo getScriptDirectionTag((int) $row['NfLgID']); ?>
            name="feed[<?php echo $count; ?>][TxText]" class="notempty checkbytes"
            cols="60" rows="20"
            ><?php echo tohtml($text['TxText']); ?></textarea>
            <img src="/assets/icons/status-busy.png" title="Field must not be empty"
            alt="Field must not be empty" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">Source URI:</td>
        <td class="td1">
            <input type="text" class="checkurl"
            name="feed[<?php echo $count; ?>][TxSourceURI]"
            value="<?php echo $text['TxSourceURI']; ?>" maxlength="1000"
            size="60" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">Tags:</td>
        <td class="td1">
            <ul name="feed[<?php echo $count; ?>][TagList][]"
            style="width:340px;margin-top:0px;margin-bottom:0px;margin-left:2px;">
                <li>
                    <?php echo $nf_tag_name; ?>
                </li>
            </ul>
            <input type="hidden" name="feed[<?php echo $count; ?>][Nf_ID]" value="<?php echo $nf_id; ?>" />
            <input type="hidden" name="feed[<?php echo $count; ?>][Nf_Max_Texts]" value="<?php echo $nf_max_texts; ?>" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">Audio-URI:</td>
        <td class="td1">
            <input type="text" name="feed[<?php echo $count; ?>][TxAudioURI]" value="<?php echo $text['TxAudioURI']; ?>" maxlength="200" size="60" />
        </td>
    </tr>
</table>
                <?php
            }
        } else {
            do_mysqli_query(
                'insert ignore into ' . $tbpref . 'tags2 (T2Text)
                values("' . $nf_tag_name . '")'
            );
            foreach ($texts as $text) {
                echo '<div class="msgblue">
                <p class="hide_message">+++ "' . $text['TxTitle'] . '" added! +++</p>
                </div>';
                do_mysqli_query(
                    'INSERT INTO ' . $tbpref . 'texts (
                        TxLgID,TxTitle,TxText,TxAudioURI,TxSourceURI
                    )
                    VALUES (
                        ' . $row['NfLgID'] . ',' .
                        convert_string_to_sqlsyntax($text['TxTitle']) . ',' .
                        convert_string_to_sqlsyntax($text['TxText']) . ',' .
                        convert_string_to_sqlsyntax($text['TxAudioURI']) . ',' .
                        convert_string_to_sqlsyntax($text['TxSourceURI']) . '
                    )'
                );
                $id = get_last_key();
                splitCheckText(
                    get_first_value(
                        'select TxText as value from ' . $tbpref . 'texts
                        where TxID = ' . $id
                    ),
                    get_first_value(
                        'select TxLgID as value from ' . $tbpref . 'texts
                        where TxID = ' . $id
                    ),
                    $id
                );
                runsql(
                    'insert into ' . $tbpref . 'texttags (TtTxID, TtT2ID)
                    select ' . $id . ', T2ID
                    from ' . $tbpref . 'tags2
                    where T2Text = "' . $nf_tag_name . '"',
                    ""
                );
            }
            get_texttags(1);
            $result = do_mysqli_query(
                "SELECT TtTxID FROM " . $tbpref . "texttags
                join " . $tbpref . "tags2 on TtT2ID=T2ID
                WHERE T2Text='" . $nf_tag_name . "'"
            );
            $text_count = 0;
            $text_item = array();
            while ($row = mysqli_fetch_assoc($result)) {
                $text_item[$text_count++] = $row['TtTxID'];
            }
            mysqli_free_result($result);
            if (!empty($text_item)) {
                sort($text_item, SORT_NUMERIC);
                if ($text_count > $nf_max_texts) {
                    $text_item = array_slice($text_item, 0, $text_count - $nf_max_texts);
                    foreach ($text_item as $text_ID) {
                        $temp = runsql(
                            'delete from ' . $tbpref . 'textitems2 where Ti2TxID = ' . $text_ID,
                            ""
                        );
                        if (is_numeric($temp)) {
                            $message3 += (int) $temp;
                        }
                        $temp = runsql(
                            'delete from ' . $tbpref . 'sentences where SeTxID = ' . $text_ID,
                            ""
                        );
                        if (is_numeric($temp)) {
                            $message2 += (int) $temp;
                        }
                        $temp = runsql(
                            'INSERT INTO ' . $tbpref . 'archivedtexts (
                                AtLgID, AtTitle, AtText,
                                AtAnnotatedText, AtAudioURI, AtSourceURI
                            )
                            SELECT TxLgID, TxTitle, TxText,
                            TxAnnotatedText, TxAudioURI, TxSourceURI
                            FROM ' . $tbpref . 'texts
                            WHERE TxID = ' . $text_ID,
                            ""
                        );
                        if (is_numeric($temp)) {
                            $message4 += (int) $temp;
                        }
                        $id = get_last_key();
                        runsql(
                            'INSERT INTO ' . $tbpref . 'archtexttags (AgAtID, AgT2ID)
                            SELECT ' . $id . ', TtT2ID
                            FROM ' . $tbpref . 'texttags
                            WHERE TtTxID = ' . $text_ID,
                            ""
                        );
                        $temp = runsql(
                            'DELETE FROM ' . $tbpref . 'texts
                            WHERE TxID = ' . $text_ID,
                            ""
                        );
                        if (is_numeric($temp)) {
                            $message1 += (int) $temp;
                        }
                        adjust_autoincr('texts', 'TxID');
                        adjust_autoincr('sentences', 'SeID');
                        runsql(
                            "DELETE " . $tbpref . "texttags
                            FROM ("
                                . $tbpref . "texttags
                                LEFT JOIN " . $tbpref . "texts
                                ON TtTxID = TxID
                            )
                            WHERE TxID IS NULL",
                            ''
                        );
                    }
                }
            }
        }
    }
    mysqli_free_result($res);
    if ($message4 > 0 || $message1 > 0) {
        $message = "Texts archived: $message1 / Sentences deleted: $message2" .
        " / Text items deleted: $message3";
    }
    if ($edit_text == 1) {
        ?>
   <input id="markaction" type="submit" value="Save" />
   <input type="button" value="Cancel" onclick="location.href='/feeds';" />
   <input type="hidden" name="checked_feeds_save" value="1" />
   </form>

   <script type="text/javascript">
    $(document).ready(function() {
        $(document).scrollTo($('table').eq(0));
    });
    $('input[type="checkbox"]').change(function(){
        var feed = '[name^=feed\\['+ $(this).val() +'\\]';
        if(this.checked){
            $(feed+']').prop('disabled', false);
            $(feed+'\\[TxTitle\\]],'+feed+'\\[TxText\\]]').addClass("notempty");
            $('ul'+feed+']').css("background","");
            $('ul'+feed+'] li.tagit-new input').prop('disabled', false)
            .addClass("ui-widget-content");
            $('ul'+feed+'] a').css("display", "");
            $('ul'+feed+'] li').css("color", "").css("background", "");
        } else {
            $(feed+']').prop('disabled', true).removeClass("notempty");
            var bg=$('textarea'+feed+']').css("background");
            $('ul'+feed+']').css("background",bg);
            $('ul'+feed+'] li.tagit-new input').prop('disabled', true)
            .removeClass("ui-widget-content");
            $('ul'+feed+'] a').css("display", "none");
            $('ul'+feed+'] li').css("color", $('textarea'+feed+']')
            .css("color")).css("background", "transparent");
        }
    });
    $('ul[name^="feed"]').each(function() {
        var tagrepl=$(this).attr('name');
        $(this).tagit({
        availableTags : TEXTTAGS,
        fieldName: tagrepl
    });});
   </script>
        <?php
    }
    if ($edit_text == 1) {
        echo '</form>';
    }
    ?>
<script type="text/javascript">
$(".hide_message").delay(2500).slideUp(1000);
</script>
    <?php
    return array($edit_text, $message);
}

function check_errors($message): void
{

    if (isset($_REQUEST['checked_feeds_save'])) {
        $message = write_rss_to_db($_REQUEST['feed']);
        ?>
    <script type="text/javascript">
    $(".hide_message").delay(2500).slideUp(1000);
    </script>
        <?php
    }
    if (isset($_SESSION['feed_loaded'])) {
        foreach ($_SESSION['feed_loaded'] as $lf) {
            if (substr($lf, 0, 5) == "Error") {
                echo "\n<div class=\"red\"><p>";
            } else {
                echo "\n<div class=\"msgblue\"><p class=\"hide_message\">";
            }
            echo "+++ ",$lf," +++</p></div>";
        }
        ?>
    <script type="text/javascript">
    $(".hide_message").delay(2500).slideUp(1000);
    </script>
        <?php
        unset($_SESSION['feed_loaded']);
    }
    echo error_message_with_hide($message, false);
}

function dummy_function_2($currentlang, $currentfeed): void
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $debug = \Lwt\Core\LWT_Globals::isDebug();
    $currentquery = (string) processSessParam("query", "currentrssquery", '', false);
    $currentquerymode = (string) processSessParam(
        "query_mode",
        "currentrssquerymode",
        'title,desc,text',
        false
    );
    $currentregexmode = getSettingWithDefault("set-regex-mode");
    $wh_query = $currentregexmode . 'like ' .  convert_string_to_sqlsyntax(
        ($currentregexmode == '') ?
        str_replace("*", "%", mb_strtolower($currentquery, 'UTF-8')) :
        $currentquery
    );
    switch ($currentquerymode) {
        case 'title,desc,text':
            $wh_query = ' and (FlTitle ' . $wh_query . ' or FlDescription ' . $wh_query . ' or FlText ' . $wh_query . ')';
            break;
        case 'title':
            $wh_query = ' and (FlTitle ' . $wh_query . ')';
            break;
    }

    if ($currentquery !== '') {
        if ($currentregexmode !== '') {
            if (@mysqli_query($GLOBALS["DBCONNECTION"], 'select "test" rlike ' . convert_string_to_sqlsyntax($currentquery)) === false) {
                $currentquery = '';
                $wh_query = '';
                unset($_SESSION['currentwordquery']);
                if (isset($_REQUEST['query'])) {
                    echo '<p id="hide3" style="color:red;text-align:center;">+++ Warning: Invalid Search +++</p>';
                }
            }
        }
    } else {
        $wh_query = '';
    }
    $currentpage = (int) processSessParam("page", "currentrsspage", '1', true);
    $currentsort = (int) processDBParam("sort", 'currentrsssort', '2', true);
    ?>

<div class="flex-spaced">
    <div title="Import of a single text, max. 65,000 bytes long, with optional audio">
        <a href="/feeds/edit?new_feed=1">
            <img src="/assets/icons/feed--plus.png">
            New Feed
        </a>
    </div>
    <div>
        <a href="/feeds/edit?manage_feeds=1">
            <img src="/assets/icons/plus-button.png" title="manage feeds" alt="manage feeds" />
            Manage Feeds
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
<form name="form1" action="#" onsubmit="document.form1.querybutton.click(); return false;">
<table class="tab2" cellspacing="0" cellpadding="5"><tr>
    <th class="th1" colspan="4">
        Filter <img src="/assets/icons/funnel.png" title="Filter" alt="Filter" />&nbsp;
        <input type="button" value="Reset All" onclick="resetAll('/feeds');" />
    </th>
    </tr>
    <tr>
        <td class="td1 center" style="width:30%;">
            Language:&nbsp;
            <select name="filterlang" onchange="{setLang(document.form1.filterlang,'/feeds?page=1%26selected_feed=0');}">
                <?php echo get_languages_selectoptions($currentlang, '[Filter off]'); ?>
            </select>
        </td>
        <td class="td1 center" colspan="3">
            <select name="query_mode" onchange="{val=document.form1.query.value;mode=document.form1.query_mode.value; location.href='/feeds?page=1&amp;query=' + val + '&amp;query_mode=' + mode;return false;}">
                <option value="title,desc,text"<?php
                if ($currentquerymode == "title,desc,text") {
                    echo ' selected="selected"';
                } ?>>Title, Desc., Text</option>
                <option disabled="disabled">------------</option>
                <option value="title"<?php
                if ($currentquerymode == "title") {
                    echo ' selected="selected"';
                } ?>>Title</option>
            </select>
            <span style="vertical-align: middle">
            <?php
            if ($currentregexmode == '') {
                echo ' (Wildc.=*):';
            } elseif ($currentregexmode == 'r') {
                echo 'RegEx Mode:';
            } else {
                echo 'RegEx(CS) Mode:';
            }
            ?>
            </span>
            <input type="text" name="query" value="<?php echo tohtml($currentquery); ?>" maxlength="50" size="15" />&nbsp;
            <input type="button" name="querybutton" value="Filter" onclick="{val=document.form1.query.value;val=encodeURIComponent(val); location.href='/feeds?page=1&amp;query=' + val;return false;}" />&nbsp;
            <input type="button" value="Clear" onclick="{location.href='/feeds?page=1&amp;query=';return false;}" />
        </td>
    </tr>
    <tr>
        <td class="td1 center" colspan="2" style="width:70%;"><?php
        if (!empty($currentlang)) {
            $result = do_mysqli_query(
                "SELECT NfName, NfID, NfUpdate FROM {$tbpref}newsfeeds
        WHERE NfLgID=$currentlang ORDER BY NfUpdate DESC"
            );
        } else {
            $result = do_mysqli_query(
                "SELECT NfName, NfID, NfUpdate FROM {$tbpref}newsfeeds
        ORDER BY NfUpdate DESC"
            );
        }
        if (!mysqli_data_seek($result, 0)) {
            echo ' no feed available</td><td class="td1"></td></tr></table></form>';
        }
        if (mysqli_data_seek($result, 0)) {
            ?>Newsfeed:
    <select name="selected_feed" onchange="{val=document.form1.selected_feed.value;location.href='/feeds?page=1&amp;selected_feed=' + val;return false;}">
        <option value="0">[Filter off]</option>
                <?php
                $feeds_list = '';
                $time = '';
                while ($row = mysqli_fetch_assoc($result)) {
                    echo '<option value="' . $row['NfID'] . '"';
                    if ($currentfeed === $row['NfID']) {
                        echo ' selected="selected"';
                        $time = $row['NfUpdate'];
                    }
                    echo '>' . tohtml($row['NfName']) . '</option>';
                    $feeds_list .= ',' . $row['NfID'];
                }
                mysqli_free_result($result);
                echo '</select>
    </td>
    <td class="td1 center" colspan="2">';
                if ($currentfeed == 0 || $currentfeed == '' || strpos($feeds_list, $currentfeed) === false) {
                    $currentfeed = substr($feeds_list, 1);
                    // explode(',', $feeds_list)[0] may work as well (2.8.0)
                }

                if (strpos($currentfeed, ',') === false) {
                    echo '<a href="' . $_SERVER['PHP_SELF'] . '?page=1&amp;load_feed=1&amp;selected_feed=' . $currentfeed . '">
        <span title="update feed"><img src="/assets/icons/arrow-circle-135.png" alt="-" /></span></a>';
                } else {
                    echo '<a href="/feeds/edit?multi_load_feed=1&amp;selected_feed=' . $currentfeed . '">
        update multiple feeds</a>';
                }
                if ($time) {
                    $diff = time() - (int) $time;
                    print_last_feed_update($diff);
                }
                echo '</td></tr>';
                $sql = "SELECT count(*) AS value FROM {$tbpref}feedlinks
    WHERE FlNfID in ($currentfeed)$wh_query";
                $recno = (int)get_first_value($sql);
                if ($debug) {
                    echo $sql . ' ===&gt; ' . $recno;
                }
                if ($recno) {
                    $maxperpage = (int)getSettingWithDefault('set-articles-per-page');
                    $pages = $recno == 0 ? 0 : (intval(($recno - 1) / $maxperpage) + 1);
                    if ($currentpage < 1) {
                        $currentpage = 1;
                    }
                    if ($currentpage > $pages) {
                        $currentpage = $pages;
                    }
                    $limit = 'LIMIT ' . (($currentpage - 1) * $maxperpage) . ',' . $maxperpage;
                    $sorts = array('FlTitle','FlDate DESC','FlDate ASC');
                    $lsorts = count($sorts);
                    if ($currentsort < 1) {
                        $currentsort = 1;
                    }
                    if ($currentsort > $lsorts) {
                        $currentsort = $lsorts;
                    }
                    echo '<tr><th class="th1" style="width:30%;"> ' . $total = $recno . ' articles ';///
                    echo '</th><th class="th1">';
                    makePager($currentpage, $pages, '/feeds', 'form1');
                    ?>
  </th>
  <th class="th1" colspan="2" nowrap="nowrap">
  Sort Order:
  <select name="sort" onchange="{val=document.form1.sort.options[document.form1.sort.selectedIndex].value; location.href='/feeds?page=1&amp;sort=' + val;return false;}"><?php echo get_textssort_selectoptions($currentsort); ?></select>
  </th>
  </tr>
  </table></form>
  <form name="form2" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
  <table class="tab2" cellspacing="0" cellpadding="5">
  <tr><th class="th1" colspan="2">Multi Actions <img src="/assets/icons/lightning.png" title="Multi Actions" alt="Multi Actions" /></th></tr>
  <tr><td class="td1 center" style="width:30%;">
  <input type="button" value="Mark All" onclick="selectToggle(true,'form2');" />
  <input type="button" value="Mark None" onclick="selectToggle(false,'form2');" />
  </td><td class="td1 center">
  Marked Texts:&nbsp;
  <input id="markaction" type="submit" value="Get Marked Texts" />&nbsp;&nbsp;
  </td></tr></table>
  <table  class="tab2 sortable" cellspacing="0" cellpadding="5">
  <tr>
  <th class="th1 sorttable_nosort">Mark</th>
  <th class="th1 clickable">Articles</th>
  <th class="th1 sorttable_nosort">Link</th>
  <th class="th1 clickable" style="min-width:90px;">Date</th>
  </tr>
                    <?php
                        $result = do_mysqli_query(
                            "SELECT FlID, FlTitle, FlLink, FlDescription, FlDate,
                            FlAudio,TxID, AtID
                            FROM " . $tbpref . "feedlinks
                            left join " . $tbpref . "texts
                            on TxSourceURI=trim(FlLink)
                            left join " . $tbpref . "archivedtexts
                            on AtSourceURI=trim(FlLink)
                            WHERE FlNfID in ($currentfeed) " . $wh_query . "
                            ORDER BY " . $sorts[$currentsort - 1] . " " . $limit
                        );
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo '<tr>';
                        if ($row['TxID']) {
                            echo '<td class="td1 center"><a href="/text/read?start=' .
                            $row['TxID'] . '" >
                            <img src="/assets/icons/book-open-bookmark.png" title="Read" alt="-" /></a>';
                        } elseif ($row['AtID']) {
                            echo '<td class="td1 center"><span title="archived"><img src="/assets/icons/status-busy.png" alt="-" /></span>';
                        } elseif (!empty($row['FlLink']) && str_starts_with((string) $row['FlLink'], ' ')) {
                            echo '<td class="td1 center">
                            <img class="not_found" name="' .
                            $row['FlID'] .
                            '" title="download error" src="/assets/icons/exclamation-button.png" alt="-" />';
                        } else {
                            echo '<td class="td1 center"><input type="checkbox" class="markcheck" name="marked_items[]" value="' .
                            $row['FlID'] . '" />';
                        }
                        echo '</td>
            <td class="td1 center">
            <span title="' . htmlentities((string) $row['FlDescription'], ENT_QUOTES, 'UTF-8', false) . '"><b>' .
                        $row['FlTitle'] . '</b></span>';
                        if ($row['FlAudio']) {
                            echo '<a href="' . $row['FlAudio'] .
                            '" onclick="window.open(this.href, \'child\', \'scrollbars,width=650,height=600\'); return false;">
                            <img src="';
                            print_file_path('icn/speaker-volume.png');
                            echo '" alt="-" /></a>';
                        }
                        echo '</td>
            <td class="td1 center" style="vertical-align: middle">';
                        if (
                            !empty($row['FlLink'])
                            && !str_starts_with(trim((string) $row['FlLink']), '#')
                        ) {
                            echo '<a href="' . trim((string) $row['FlLink']) . '"  title="' .
                            trim((string) $row['FlLink']) . '" onclick="window.open(\'' .
                            $row['FlLink'] . '\');return false;">
                            <img src="/assets/icons/external.png" alt="-" /></a>';
                        }
                        echo  '</td><td class="td1 center">' . $row['FlDate'] . '</td></tr>';
                    }
                    mysqli_free_result($result);
                    echo '</table>';
                    echo '</form>';
                    if ($pages > 1) {
                        echo '<form name="form3" method="get" action ="">
            <table class="tab2" cellspacing="0" cellpadding="5">
            <tr><th class="th1" style="width:30%;">';
                        echo $total;
                        echo '</th><th class="th1">';
                        makePager($currentpage, $pages, '/feeds', 'form3');
                        echo '</th></tr></table></form>';
                    }
                } else {
                    echo '</table></form>';
                }
        }
        ?>
<script type="text/javascript">
$('img.not_found').on('click', function () {
    var id = $(this).attr('name');
    $(this).after('<label class="wrap_checkbox" for="'+id+'"><span></span></label>');
    $(this).replaceWith(
        '<input type="checkbox" class="markcheck" onchange="markClick()" id=' + id +
        ' value=' + id +' name="marked_items[]" />'
    );
    $(":input,.wrap_checkbox span,a:not([name^=rec]),select")
    .each(function (i) { $(this).attr('tabindex', i + 1); });
});
</script>
    <?php
}

function do_page(): void
{
    session_start();
    $currentlang = validateLang(
        (string) processDBParam("filterlang", 'currentlanguage', '', false)
    );
    pagestart('My ' . getLanguage($currentlang) . ' Feeds', true);
    $currentfeed = (string) processSessParam(
        "selected_feed",
        "currentrssfeed",
        '',
        false
    );

    $edit_text = 0;
    $message = '';
    if (isset($_REQUEST['marked_items']) && is_array($_REQUEST['marked_items'])) {
        list($edit_text, $message) = dummy_function_1();
    }
    check_errors($message);

    if (
        isset($_REQUEST['load_feed']) || isset($_REQUEST['check_autoupdate'])
        || (isset($_REQUEST['markaction']) && $_REQUEST['markaction'] == 'update')
    ) {
        load_feeds($currentfeed);
    } elseif (empty($edit_text)) {
        dummy_function_2($currentlang, $currentfeed);
    }

    pageend();
}

do_page();
?>
