<?php

/**
 * \file
 * \brief Editing and Managing RSS feeds.
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package  Lwt
 * @author   andreask7 <andreask7@users.noreply.github.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    1.6.0-fork
 * @since    3.0.0 MVC refactoring
 */

namespace Lwt\Interface\Edit_Feeds;

require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../Core/UI/ui_helpers.php';
require_once __DIR__ . '/../Core/Feed/feeds.php';
require_once __DIR__ . '/../Core/Http/param_helpers.php';
require_once __DIR__ . '/../Core/Language/language_utilities.php';

use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Database\Settings;
use Lwt\Database\Validation;
use Lwt\Services\FeedService;

/**
 * Handle delete actions and return status message.
 *
 * @param FeedService $service     Feed service instance
 * @param string      $currentFeed Current selected feed(s)
 *
 * @return string Status message
 */
function handleMarkAction(FeedService $service, string $currentFeed): string
{
    if (!isset($_REQUEST['markaction']) || empty($currentFeed)) {
        return '';
    }

    $action = $_REQUEST['markaction'];

    switch ($action) {
        case 'del':
            $service->deleteFeeds($currentFeed);
            return "Article item(s) deleted / Newsfeed(s) deleted";

        case 'del_art':
            $service->deleteArticles($currentFeed);
            return "Article item(s) deleted";

        case 'res_art':
            $service->resetUnloadableArticles($currentFeed);
            return "Article(s) reset";

        default:
            return '';
    }
}

/**
 * Handle update feed submission.
 *
 * @param FeedService $service Feed service instance
 *
 * @return void
 */
function handleUpdateFeed(FeedService $service): void
{
    if (!isset($_REQUEST['update_feed'])) {
        return;
    }

    $feedId = (int)$_REQUEST['NfID'];

    $data = [
        'NfLgID' => $_REQUEST['NfLgID'] ?? '',
        'NfName' => $_REQUEST['NfName'] ?? '',
        'NfSourceURI' => $_REQUEST['NfSourceURI'] ?? '',
        'NfArticleSectionTags' => $_REQUEST['NfArticleSectionTags'] ?? '',
        'NfFilterTags' => $_REQUEST['NfFilterTags'] ?? '',
        'NfOptions' => rtrim($_REQUEST['NfOptions'] ?? '', ','),
    ];

    $service->updateFeed($feedId, $data);
}

/**
 * Handle save new feed submission.
 *
 * @param FeedService $service Feed service instance
 *
 * @return void
 */
function handleSaveFeed(FeedService $service): void
{
    if (!isset($_REQUEST['save_feed'])) {
        return;
    }

    $data = [
        'NfLgID' => $_REQUEST['NfLgID'] ?? '',
        'NfName' => $_REQUEST['NfName'] ?? '',
        'NfSourceURI' => $_REQUEST['NfSourceURI'] ?? '',
        'NfArticleSectionTags' => $_REQUEST['NfArticleSectionTags'] ?? '',
        'NfFilterTags' => $_REQUEST['NfFilterTags'] ?? '',
        'NfOptions' => rtrim($_REQUEST['NfOptions'] ?? '', ','),
    ];

    $service->createFeed($data);
}

/**
 * Display session messages for feed loading.
 *
 * @return void
 */
function displaySessionMessages(): void
{
    if (!isset($_SESSION['feed_loaded'])) {
        return;
    }

    foreach ($_SESSION['feed_loaded'] as $lf) {
        echo "\n<div class=\"msgblue\"><p class=\"hide_message\">+++ ", $lf, " +++</p></div>";
    }
    ?>
<script type="text/javascript">
$(".hide_message").delay(2500).slideUp(1000);
</script>
    <?php
    unset($_SESSION['feed_loaded']);
}

/**
 * Display the new feed form.
 *
 * @param int $currentLang Current language filter
 *
 * @return void
 */
function displayNewFeed(int $currentLang): void
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $result = Connection::query(
        "SELECT LgName, LgID FROM {$tbpref}languages
        WHERE LgName <> '' ORDER BY LgName"
    );
    ?>
<h2>New Feed</h2>
<a href="/feeds?page=1">My Feeds</a>
<span class="nowrap"></span>
<a href="/feeds/wizard?step=1">
    <img src="/assets/icons/wizard.png" title="new_feed_wizard" alt="new_feed_wizard" style="height: 20px;"/>
    New Feed Wizard
</a>
<br></br>
<form class="validate" action="/feeds/edit" method="post">
<table class="tab2" cellspacing="0" cellpadding="5">
<tr><td class="td1">Language: </td><td class="td1"><select name="NfLgID">
    <?php
    while ($row_l = mysqli_fetch_assoc($result)) {
        echo '<option value="' . $row_l['LgID'] . '"';
        if ($currentLang === (int)$row_l['LgID']) {
            echo ' selected="selected"';
        }
        echo '>' . $row_l['LgName'] . '</option>';
    }
    mysqli_free_result($result);
    ?>
</select></td></tr>
<tr><td class="td1">
Name: </td><td class="td1">
    <input class="notempty" style="width:95%" type="text" name="NfName" />
<img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
</td></tr>
<tr><td class="td1">Newsfeed url: </td>
<td class="td1"><input class="notempty" style="width:95%" type="text" name="NfSourceURI" />
<img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
</td></tr>
<tr><td class="td1">Article Section: </td>
<td class="td1"><input class="notempty" style="width:95%" type="text" name="NfArticleSectionTags" />
<img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
</td></tr>
<tr><td class="td1">Filter Tags: </td>
<td class="td1"><input type="text" style="width:95%" name="NfFilterTags" /></td></tr>
<tr><td class="td1">Options: </td>
<td class="td1"><table style="width:100%">
<tr><td style="width:35%"><input type="checkbox" name="edit_text" checked="checked" /> Edit Text </td>
<td>
    <input type="checkbox" name="c_autoupdate" /> Auto Update Interval:
    <input class="posintnumber" data_info="Auto Update Interval" type="number" min="0" size="4" name="autoupdate" disabled />
    <select name="autoupdate" disabled><option value="h">Hour(s)</option>
    <option value="d">Day(s)</option><option value="w">Week(s)</option></select>
</td></tr>
<tr><td>
    <input type="checkbox" name="c_max_links" /> Max. Links:
    <input class="posintnumber maxint_300" data_info="Max. Links" type="number" min="0" max="300" size="4" name="max_links" disabled /></td>
    <td><input type="checkbox" name="c_charset" /> Charset:
    <input type="text" data_info="Charset" size="20" name="charset" disabled /> </td></tr>
<tr><td>
    <input type="checkbox" name="c_max_texts" /> Max. Texts:
    <input class="posintnumber maxint_30" data_info="Max. Texts" type="number" min="0" max="30" size="4" name="max_texts" disabled /></td>
    <td>
        <input type="checkbox" name="c_tag" /> Tag:
        <input type="text" data_info="Tag" size="20" name="tag" disabled />
    </td>
</tr>
<tr><td colspan="2">
    <input type="checkbox" name="c_article_source" /> Article Source:
    <input data_info="Article Source" type="text" size="20" name="article_source" disabled /></td></tr>
</table></td></tr>
</table><input type="submit" value="Save" />
<input type="hidden" name="NfOptions" value="" />
<input type="hidden" name="save_feed" value="1" />
<input type="button" value="Cancel" onclick="location.href='/feeds/edit';" />
</form>
<script type="text/javascript">
$('[name^="c_"]').change(function(){
    if(this.checked){
        $(this).parent().children('input[type="text"]')
        .removeAttr('disabled').addClass("notempty");
        $(this).parent().find('select').removeAttr('disabled');
    } else {
        $(this).parent().children('input[type="text"]')
        .attr('disabled','disabled').removeClass("notempty");
        $(this).parent().find('select').attr('disabled','disabled');
    }
});
$('[type="submit"]').on('click', function(){
    var str;
    str=$('[name="edit_text"]:checked').length > 0?"edit_text=1,":"";
    $('[name^="c_"]').each(function(){
        str+=this.checked ? $(this).parent().children('input[type="text"]')
        .attr('name') + '='
        + $(this).parent().children('input[type="text"]').val()
        + ($(this).attr('name')=='c_autoupdate' ? $(this).parent().find('select').val() + ',' : ','): '';
    });
    $('input[name="NfOptions"]').val(str);
});
</script>
    <?php
}

/**
 * Display the edit feed form.
 *
 * @param FeedService $service     Feed service instance
 * @param int         $currentFeed Feed ID to edit
 *
 * @return void
 */
function editFeed(FeedService $service, int $currentFeed): void
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $row = $service->getFeedById($currentFeed);

    if (!$row) {
        echo '<p class="red">Feed not found.</p>';
        return;
    }

    $result = Connection::query(
        "SELECT LgName, LgID FROM {$tbpref}languages
        WHERE LgName <> '' ORDER BY LgName"
    );

    $autoUpdI = $service->getNfOption($row['NfOptions'], 'autoupdate');
    if ($autoUpdI == null) {
        $autoUpdV = null;
    } else {
        $autoUpdV = substr($autoUpdI, -1);
        $autoUpdI = substr($autoUpdI, 0, -1);
    }
    ?>
<h2>
    Edit Feed
    <a target="_blank" href="docs/info.html#new_feed">
        <img src="/assets/icons/question-frame.png" title="Help" alt="Help" />
    </a>
</h2>
<a href="/feeds?page=1"> My Feeds</a>
<span class="nowrap"></span>
<a href="/feeds/wizard?step=2&amp;edit_feed=<?php echo $currentFeed;?>">
<img src="/assets/icons/wizard.png" title="feed_wizard" alt="feed_wizard" />Feed Wizard</a>
<form class="validate" action="/feeds/edit" method="post">
<table class="tab2" cellspacing="0" cellpadding="5">
<tr>
    <td class="td1">Language: </td>
    <td class="td1">
        <select name="NfLgID">
    <?php
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
    <td class="td1">Name: </td>
    <td class="td1">
        <input class="notempty" style="width:95%" type="text" name="NfName"
        value="<?php echo tohtml($row['NfName']); ?>" />
        <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
    </td>
</tr>
<tr>
    <td class="td1">Newsfeed url: </td>
    <td class="td1">
        <input class="notempty" style="width:95%" type="text" name="NfSourceURI"
        value="<?php echo tohtml($row['NfSourceURI']); ?>" />
        <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
    </td>
</tr>
<tr>
    <td class="td1">Article Section: </td>
    <td class="td1">
        <input class="notempty" style="width:95%" type="text"
        name="NfArticleSectionTags" value="<?php echo tohtml($row['NfArticleSectionTags']); ?>" />
        <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
    </td>
</tr>
<tr>
    <td class="td1">Filter Tags: </td>
    <td class="td1">
        <input type="text" style="width:95%" name="NfFilterTags"
        value="<?php echo tohtml($row['NfFilterTags']); ?>" />
    </td>
</tr>
<tr>
    <td class="td1">Options: </td>
    <td class="td1">
        <table style="width:100%">
        <tr>
            <td style="width:35%">
            <input type="checkbox" name="edit_text"<?php
            if ($service->getNfOption($row['NfOptions'], 'edit_text') !== null) {
                echo ' checked="checked"';
            } ?> />
            Edit Text
        </td>
<td>
    <input type="checkbox" name="c_autoupdate"<?php if ($autoUpdI !== null) {
        echo ' checked="checked"';
                                              } ?> />
Auto Update Interval: <input class="posintnumber<?php if ($service->getNfOption($row['NfOptions'], 'autoupdate') !== null) {
    echo ' notempty';
                                                } ?>" data_info="Auto Update Interval" type="number" min="0" size="4" name="autoupdate" value="<?php echo $autoUpdI; ?>"
    <?php
    if ($autoUpdI == null) {
        echo ' disabled';
    } ?> />
<select name="autoupdate" value="<?php
    echo $autoUpdV . '"';
if ($autoUpdV == null) {
    echo ' disabled';
} ?>>
<option value="h" <?php if ($autoUpdV == 'h') {
    echo ' selected="selected"';
                  }?>>Hour(s)</option>
<option value="d"<?php if ($autoUpdV == 'd') {
    echo ' selected="selected"';
                 }?>>Day(s)</option>
<option value="w"<?php if ($autoUpdV == 'w') {
    echo ' selected="selected"';
                 }?>>Week(s)</option>
</select>
</td>
</tr>
<tr>
    <td>
        <input type="checkbox" name="c_max_links"<?php if ($service->getNfOption($row['NfOptions'], 'max_links') !== null) {
            echo ' checked="checked"';
                                                 } ?> />
Max. Links: <input class="<?php
if ($service->getNfOption($row['NfOptions'], 'max_links') !== null) {
    echo 'notempty ';
} ?>posintnumber maxint_300" data_info="Max. Links" type="number" min="0" max="300" size="4" name="max_links" value="<?php echo $service->getNfOption($row['NfOptions'], 'max_links') . '"';
if ($service->getNfOption($row['NfOptions'], 'max_links') == null) {
    echo ' disabled';
} ?> />
</td>
<td>
    <input type="checkbox" name="c_charset"<?php
    if ($service->getNfOption($row['NfOptions'], 'charset') !== null) {
        echo ' checked="checked"';
    } ?> />
Charset: <input <?php if ($service->getNfOption($row['NfOptions'], 'charset') !== null) {
    echo 'class="notempty" ';
                } ?>type="text" data_info="Charset" size="20" name="charset" value="<?php echo $service->getNfOption($row['NfOptions'], 'charset') . '"';
if ($service->getNfOption($row['NfOptions'], 'charset') == null) {
    echo ' disabled';
} ?> />
</td>
</tr>
<tr>
    <td>
        <input type="checkbox" name="c_max_texts"<?php if ($service->getNfOption($row['NfOptions'], 'max_texts') !== null) {
            echo ' checked="checked"';
                                                 } ?> />
Max. Texts:
<input class="<?php if ($service->getNfOption($row['NfOptions'], 'max_texts') !== null) {
    echo 'notempty ';
              } ?>posintnumber maxint_30" data_info="Max. Texts" type="number" min="0" max="30"
size="4" name="max_texts"
value="<?php echo $service->getNfOption($row['NfOptions'], 'max_texts') . '"';if ($service->getNfOption($row['NfOptions'], 'max_texts') == null) {
    echo ' disabled';
       } ?> />
</td>
<td>
    <input type="checkbox" name="c_tag"<?php if ($service->getNfOption($row['NfOptions'], 'tag') !== null) {
        echo ' checked="checked"';
                                       } ?> />
       Tag: <input <?php if ($service->getNfOption($row['NfOptions'], 'tag') !== null) {
            echo 'class="notempty" ';
                   } ?>type="text" data_info="Tag" size="20" name="tag" value="<?php echo $service->getNfOption($row['NfOptions'], 'tag') . '"';
if ($service->getNfOption($row['NfOptions'], 'tag') == null) {
    echo ' disabled';
} ?> />
</td>
</tr>
<tr>
    <td colspan="2">
    <input type="checkbox" name="c_article_source"<?php if ($service->getNfOption($row['NfOptions'], 'article_source') !== null) {
        echo ' checked="checked"';
                                                  } ?> />
Article Source: <input class="<?php if ($service->getNfOption($row['NfOptions'], 'article_source') !== null) {
    echo 'notempty ';
                              } ?>" data_info="Article Source" type="text" size="20" name="article_source" value="<?php echo $service->getNfOption($row['NfOptions'], 'article_source') . '"';
if ($service->getNfOption($row['NfOptions'], 'article_source') == null) {
    echo ' disabled';
} ?> />
</td>
</tr>
</table>
</td>
</tr>
</table>
<input type="submit" value="Update" />
<input type="hidden" name="NfID" value="<?php echo tohtml($row['NfID']); ?>" />
<input type="button" value="Cancel" onclick="location.href='/feeds/edit';" />
<input type="hidden" name="NfOptions" value="" />
<input type="hidden" name="update_feed" value="1" />
</form>
<script type="text/javascript">
$('[name^="c_"]').change(function(){
    if (this.checked){
        $(this).parent().children('input[type="text"]')
        .removeAttr('disabled').addClass("notempty");
        $(this).parent().find('select').removeAttr('disabled');
    } else {
        $(this).parent().children('input[type="text"]')
        .attr('disabled','disabled').removeClass("notempty");
        $(this).parent().find('select').attr('disabled','disabled');
    }
});
$('[type="submit"]').on('click', function(){
    var str;
    str=$('[name="edit_text"]:checked').length > 0?"edit_text=1,":"";
    $('[name^="c_"]').each(function(){
        str+=this.checked ? $(this).parent().children('input[type="text"]').attr('name') + '='
        + $(this).parent().children('input[type="text"]').val()
        + ($(this).attr('name')=='c_autoupdate' ? $(this).parent().find('select').val() + ',' : ','): '';
    });
    $('input[name="NfOptions"]').val(str);
});
</script>
    <?php
}

/**
 * Display multi-load feed form.
 *
 * @param FeedService $service     Feed service instance
 * @param int         $currentLang Current language filter
 *
 * @return void
 */
function multiLoadFeed(FeedService $service, int $currentLang): void
{
    $feeds = $service->getFeeds($currentLang ?: null);
    ?>
<form name="form1" action="/feeds" onsubmit="document.form1.querybutton.click(); return false;">
<table class="tab3" style="border-left: none;border-top: none; background-color:inherit" cellspacing="0" cellpadding="5">
<tr>
<th class="th1 borderleft" colspan="2">Language:<select name="filterlang" onchange="{setLang(document.form1.filterlang,'/feeds/edit?multi_load_feed=1%26page=1');}">
    <?php
    echo get_languages_selectoptions($currentLang, '[Filter off]');
    ?>
</select>
</th>
<th class="th1 borderright" colspan="2">
<input type="button" value="Mark All" onclick="selectToggle(true,'form1');return false;" />
<input type="button" value="Mark None" onclick="selectToggle(false,'form1');return false;" /></th>
</tr>
<tr>
<td colspan="4" style="padding-left: 0px;padding-right: 0px;border-bottom: none;width: 100%;border-left: none;background-color: transparent;"><table class="sortable tab2" cellspacing="0" cellpadding="5">
<tr>
<th class="th1 sorttable_nosort">Mark</th>
<th class="th1 clickable" colspan="2">Newsfeeds</th>
<th class="th1 sorttable_numeric clickable">Last Update</th>
</tr>
    <?php
    $time = time();
    foreach ($feeds as $row) {
        $diff = $time - (int)$row['NfUpdate'];
        echo '<tr><td class="td1 center">
        <input class="markcheck" type="checkbox" name="selected_feed[]" value="' . $row['NfID'] . '" checked="checked" />
        </td>
        <td class="td1 center" colspan="2">' . tohtml($row['NfName']) . '</td>
        <td class="td1 center" sorttable_customkey="' . $diff . '">';
        if ($row['NfUpdate']) {
            print_last_feed_update($diff);
        }
        echo '</td></tr>';
    }
    ?>
</table>
</td>
</tr>
<tr>
<th class="th1 borderleft" colspan="3"><input id="map" type="hidden" name="selected_feed" value="" />
<input type="hidden" name="load_feed" value="1" />
<button id="markaction">Update Marked Newsfeeds</button></th>
<th class="th1 borderright">
    <input type="button" value="Cancel" onclick="location.href='/feeds?selected_feed=0'; return false;" /></th></tr>
</table>
</form>

<script type="text/javascript">
$( "button" ).on('click', function() {
    $("#map").val( $('input[type="checkbox"]:checked').map(function(){
        return $(this).val();
    }).get().join(", ") );
});
</script>
    <?php
}

/**
 * Display the main feeds management page.
 *
 * @param FeedService $service      Feed service instance
 * @param int         $currentLang  Current language filter
 * @param string      $currentQuery Current search query
 * @param int         $currentPage  Current page number
 * @param int         $currentSort  Current sort index
 * @param string      $whQuery      WHERE clause for query filter
 *
 * @return void
 */
function displayMainPage(
    FeedService $service,
    int $currentLang,
    string $currentQuery,
    int $currentPage,
    int $currentSort,
    string $whQuery
): void {
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $debug = \Lwt\Core\Globals::isDebug();
    ?>

<div class="flex-spaced">
    <div><a href="/feeds">My Feeds</a></div>
    <div>
        <a href="/feeds/edit?new_feed=1">
            <img src="/assets/icons/feed--plus.png" title="new feed" alt="new feed" />
            New Feed...
        </a>
    </div>
</div>
<form name="form1" action="#" onsubmit="document.form1.querybutton.click(); return false;">
<table class="tab2" cellspacing="0" cellpadding="5"><tr>
<th class="th1" colspan="4">Filter <img src="/assets/icons/funnel.png" title="Filter" alt="Filter" />&nbsp;
<input type="button" value="Reset All" onclick="resetAll('/feeds/edit');" /></th>
</tr>
<tr>
    <td class="td1 center" colspan="2" style="width:30%;">
    Language:&nbsp;<select name="filterlang" onchange="{setLang(document.form1.filterlang,'/feeds/edit?manage_feeds=1');}">
    <?php
    echo get_languages_selectoptions($currentLang, '[Filter off]');
    ?>
</select>
</td>
<td class="td1 center" colspan="4">
    Feed Name (Wildc.=*):
    <input type="text" name="query" value="<?php echo tohtml($currentQuery); ?>" maxlength="50" size="15" />&nbsp;
    <input type="button" name="querybutton" value="Filter" onclick="{val=document.form1.query.value; location.href='/feeds/edit?page=1&amp;query=' + val;}" />&nbsp;
    <input type="button" value="Clear" onclick="{location.href='/feeds/edit?page=1&amp;query=';}" />
</td>
</tr>
</table>

<input id="map" type="hidden" name="selected_feed" value="" />
<table class="tab2" cellspacing="0" cellpadding="5">
<tr>
    <th class="th1" colspan="3">
        Multi Actions <img src="/assets/icons/lightning.png" title="Multi Actions" alt="Multi Actions" />
    </th>
</tr>
<tr><td class="td1 center" style="width:30%;">
<input type="button" value="Mark All" onclick="selectToggle(true,'form2');" />
<input type="button" value="Mark None" onclick="selectToggle(false,'form2');" />
</td><td class="td1 center" colspan="2">Marked Newsfeeds:&nbsp;
<select name="markaction" id="markaction" disabled="disabled" onchange="$('#map').val($('input:checked').map(function(){return $(this).val();}).get().join(', '));multiActionGo(document.form1, document.form1.markaction);return false;">
    <option value="">[Choose...]</option>
    <option disabled="disabled">------------</option>
    <option value="update">Update</option>
    <option disabled="disabled">------------</option>
    <option value="res_art">Reset Unloadable Articles</option>
    <option disabled="disabled">------------</option>
    <option value="del_art">Delete All Articles</option>
    <option disabled="disabled">------------</option>
    <option value="del">Delete</option>
</select></td></tr>
    <?php

    $recno = $service->countFeeds($currentLang ?: null, $whQuery);

    if ($debug) {
        echo "Count: $recno";
    }

    if ($recno) {
        $maxPerPage = (int)Settings::getWithDefault('set-feeds-per-page');
        $pages = $recno == 0 ? 0 : (intval(($recno - 1) / $maxPerPage) + 1);
        if ($currentPage < 1) {
            $currentPage = 1;
        }
        if ($currentPage > $pages) {
            $currentPage = $pages;
        }

        $sorts = ['NfName', 'NfUpdate DESC', 'NfUpdate ASC'];
        $lsorts = count($sorts);
        if ($currentSort < 1) {
            $currentSort = 1;
        }
        if ($currentSort > $lsorts) {
            $currentSort = $lsorts;
        }

        $total = $recno;
        echo '<tr><th class="th1" style="width:30%;"> ' . $total . ' newsfeeds ';
        echo '</th><th class="th1">';
        makePager($currentPage, $pages, '/feeds/edit', 'form1');

        $sql = "SELECT * FROM {$tbpref}newsfeeds WHERE ";
        if (!empty($currentLang)) {
            $sql .= "NfLgID = $currentLang $whQuery";
        } else {
            $sql .= "(1=1) $whQuery";
        }
        $sql .= " ORDER BY " . $sorts[$currentSort - 1];

        $result = Connection::query($sql);
        ?>
        </th>
        <th class="th1" colspan="1" nowrap="nowrap">
        Sort Order:
        <select name="sort" onchange="{val=document.form1.sort.options[document.form1.sort.selectedIndex].value; location.href='/feeds/edit?page=1&amp;sort=' + val;}"><?php echo get_textssort_selectoptions($currentSort); ?></select>
</th>
</table>
</form>
<form name="form2" action="" method="get">
<table class="sortable tab2" cellspacing="0" cellpadding="5">
<tr>
    <th class="th1 sorttable_nosort">Mark</th>
    <th class="th1 sorttable_nosort">Actions</th>
    <th class="th1 clickable">Newsfeeds</th>
    <th class="th1 sorttable_nosort">Options</th>
    <th class="th1 sorttable_numeric clickable">Last Update</th>
</tr>
            <?php
            $time = time();
            while ($row = mysqli_fetch_assoc($result)) {
                $diff = $time - (int)$row['NfUpdate'];
                echo '<tr>
                <td class="td1 center">
                <input type="checkbox" name="marked[]" class="markcheck" value="' . $row['NfID'] . '" /></td>
                <td style="white-space: nowrap" class="td1 center">
                <a href="' . $_SERVER['PHP_SELF'] . '?edit_feed=1&amp;selected_feed=' . $row['NfID'] . '">
                <img src="/assets/icons/feed--pencil.png" title="Edit" alt="Edit" />
                </a>
                &nbsp; <a href="' . $_SERVER['PHP_SELF'] . '?manage_feeds=1&amp;load_feed=1&amp;selected_feed=' . $row['NfID'] . '">
                <span title="Update Feed"><img src="/assets/icons/arrow-circle-135.png" alt="-" /></span></a>&nbsp;
                <a href="' . $row['NfSourceURI'] . '" onclick="window.open(this.href); return false">
                <img src="/assets/icons/external.png" title="Show Feed" alt="Link" /></a>&nbsp;
                <span class="click" onclick="if (confirm (\'Are you sure?\')) location.href=\'' . $_SERVER['PHP_SELF'] . '?markaction=del&amp;selected_feed=' . $row['NfID'] . '\';">
                <img src="/assets/icons/minus-button.png" title="Delete" alt="Delete" /></span></td>
                <td class="td1 center">' . tohtml($row['NfName']) . '</td>
                <td class="td1 center">' . str_replace(',', ', ', $row['NfOptions']) . '</td>
                <td class="td1 center" sorttable_customkey="' . $diff . '">';
                if ($row['NfUpdate']) {
                    print_last_feed_update($diff);
                }
                echo '</td></tr>';
            }
            mysqli_free_result($result);
            ?>
</table>
</form>
        <?php
        if ($pages > 1) {
            echo '<form name="form3" method="get" action ="">
            <table class="tab2" cellspacing="0" cellpadding="5">
            <tr><th class="th1" style="width:30%;">';
            echo $total;
            echo '</th><th class="th1">';
            makePager($currentPage, $pages, '/feeds', 'form3');
            echo '</th></tr></table></form>';
        }
    }
}

/**
 * Main page function.
 *
 * @param FeedService $service Feed service instance
 *
 * @return void
 */
function doPage(FeedService $service): void
{

    $currentLang = Validation::language((string)processDBParam("filterlang", 'currentlanguage', '', false));
    $currentSort = (int)processDBParam("sort", 'currentmanagefeedssort', '2', true);
    $currentQuery = (string)processSessParam("query", "currentmanagefeedsquery", '', false);
    $currentPage = (int)processSessParam("page", "currentmanagefeedspage", '1', true);
    $currentFeed = (string)processSessParam("selected_feed", "currentmanagefeedsfeed", '', false);

    $whQuery = Escaping::toSqlSyntax(str_replace("*", "%", $currentQuery));
    $whQuery = ($currentQuery != '') ? (' and (NfName like ' . $whQuery . ')') : '';

    pagestart('Manage ' . getLanguage($currentLang) . ' Feeds', true);

    if (isset($_SESSION['wizard'])) {
        unset($_SESSION['wizard']);
    }

    // Handle mark actions
    $message = handleMarkAction($service, $currentFeed);
    if (!empty($message)) {
        echo error_message_with_hide($message, false);
    }

    displaySessionMessages();
    handleUpdateFeed($service);
    handleSaveFeed($service);

    // Route to appropriate view
    if (
        isset($_REQUEST['load_feed']) || isset($_REQUEST['check_autoupdate'])
        || (isset($_REQUEST['markaction']) && $_REQUEST['markaction'] == 'update')
    ) {
        load_feeds((int)$currentFeed);
    } elseif (isset($_REQUEST['new_feed'])) {
        displayNewFeed((int)$currentLang);
    } elseif (isset($_REQUEST['edit_feed'])) {
        editFeed($service, (int)$currentFeed);
    } elseif (isset($_REQUEST['multi_load_feed'])) {
        multiLoadFeed($service, (int)$currentLang);
    } else {
        displayMainPage(
            $service,
            (int)$currentLang,
            $currentQuery,
            $currentPage,
            $currentSort,
            $whQuery
        );
    }

    pageend();
}
