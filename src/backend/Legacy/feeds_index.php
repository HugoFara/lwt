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
 * @since    3.0.0 MVC refactoring
 */

namespace Lwt\Interface\Do_Feeds;

require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../Core/UI/ui_helpers.php';
require_once __DIR__ . '/../Core/Tag/tags.php';
require_once __DIR__ . '/../Core/Feed/feeds.php';
require_once __DIR__ . '/../Core/Text/text_helpers.php';
require_once __DIR__ . '/../Core/Http/param_helpers.php';
require_once __DIR__ . '/../Core/Media/media_helpers.php';
require_once __DIR__ . '/../Core/Language/language_utilities.php';

use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Database\Validation;
use Lwt\Database\Settings;
use Lwt\Database\Maintenance;
use Lwt\Database\TextParsing;
use Lwt\Services\FeedService;

/**
 * Process marked feed items and create texts from them.
 *
 * @param FeedService $service Feed service instance
 *
 * @return array{editText: int, message: string}
 */
function processMarkedItems(FeedService $service): array
{
    $editText = 0;
    $message = '';

    if (!isset($_REQUEST['marked_items']) || !is_array($_REQUEST['marked_items'])) {
        return ['editText' => $editText, 'message' => $message];
    }

    $markedItems = implode(',', array_filter($_REQUEST['marked_items'], 'is_scalar'));
    $feedLinks = $service->getMarkedFeedLinks($markedItems);

    $stats = ['archived' => 0, 'sentences' => 0, 'textitems' => 0, 'texts' => 0];
    $count = 0;

    foreach ($feedLinks as $row) {
        $requiresEdit = $service->getNfOption($row['NfOptions'], 'edit_text') == 1;

        if ($requiresEdit) {
            if ($editText == 1) {
                $count++;
            } else {
                echo '<form class="validate" action="/feeds" method="post">';
                $editText = 1;
            }
        }

        $doc = [[
            'link' => empty($row['FlLink']) ? ('#' . $row['FlID']) : $row['FlLink'],
            'title' => $row['FlTitle'],
            'audio' => $row['FlAudio'],
            'text' => $row['FlText']
        ]];

        $nfName = (string)$row['NfName'];
        $nfId = (int)$row['NfID'];
        $nfOptions = $row['NfOptions'];

        $tagName = $service->getNfOption($nfOptions, 'tag');
        if (!$tagName) {
            $tagName = mb_substr($nfName, 0, 20, "utf-8");
        }

        $maxTexts = (int)$service->getNfOption($nfOptions, 'max_texts');
        if (!$maxTexts) {
            $maxTexts = (int)Settings::getWithDefault('set-max-texts-per-feed');
        }

        $texts = get_text_from_rsslink(
            $doc,
            $row['NfArticleSectionTags'],
            $row['NfFilterTags'],
            $service->getNfOption($nfOptions, 'charset')
        );

        if (isset($texts['error'])) {
            echo $texts['error']['message'];
            foreach ($texts['error']['link'] as $errLink) {
                $service->markLinkAsError($errLink);
            }
            unset($texts['error']);
        }

        if ($requiresEdit) {
            renderEditTextForm($texts, $row, $count, $tagName, $nfId, $maxTexts);
        } else {
            $result = createTextsFromFeed($service, $texts, $row, $tagName, $maxTexts);
            $stats['archived'] += $result['archived'];
            $stats['sentences'] += $result['sentences'];
            $stats['textitems'] += $result['textitems'];
        }
    }

    if ($stats['archived'] > 0 || $stats['texts'] > 0) {
        $message = "Texts archived: {$stats['archived']} / Sentences deleted: {$stats['sentences']}" .
                   " / Text items deleted: {$stats['textitems']}";
    }

    if ($editText == 1) {
        renderEditFormFooter();
    }

    ?>
<script type="text/javascript">
$(".hide_message").delay(2500).slideUp(1000);
</script>
    <?php

    return ['editText' => $editText, 'message' => $message];
}

/**
 * Render the edit text form for a single feed item.
 *
 * @param array  $texts    Parsed text data
 * @param array  $row      Feed link and feed data
 * @param int    $count    Form item counter
 * @param string $tagName  Tag name for the text
 * @param int    $nfId     Feed ID
 * @param int    $maxTexts Maximum texts setting
 *
 * @return void
 */
function renderEditTextForm(array $texts, array $row, int &$count, string $tagName, int $nfId, int $maxTexts): void
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();

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
                $result = Connection::query(
                    "SELECT LgName, LgID FROM {$tbpref}languages
                    WHERE LgName <> '' ORDER BY LgName"
                );
                while ($rowLang = mysqli_fetch_assoc($result)) {
                    echo '<option value="' . $rowLang['LgID'] . '"';
                    if ($row['NfLgID'] === $rowLang['LgID']) {
                        echo ' selected="selected"';
                    }
                    echo '>' . $rowLang['LgName'] . '</option>';
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
                <?php echo getScriptDirectionTag((int)$row['NfLgID']); ?>
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
                    <?php echo $tagName; ?>
                </li>
            </ul>
            <input type="hidden" name="feed[<?php echo $count; ?>][Nf_ID]" value="<?php echo $nfId; ?>" />
            <input type="hidden" name="feed[<?php echo $count; ?>][Nf_Max_Texts]" value="<?php echo $maxTexts; ?>" />
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
        $count++;
    }
}

/**
 * Render the edit form footer with submit button and JavaScript.
 *
 * @return void
 */
function renderEditFormFooter(): void
{
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

/**
 * Create texts from feed data without edit form.
 *
 * @param FeedService $service  Feed service instance
 * @param array       $texts    Parsed text data
 * @param array       $row      Feed data
 * @param string      $tagName  Tag name
 * @param int         $maxTexts Maximum texts to keep
 *
 * @return array{archived: int, sentences: int, textitems: int}
 */
function createTextsFromFeed(FeedService $service, array $texts, array $row, string $tagName, int $maxTexts): array
{
    foreach ($texts as $text) {
        echo '<div class="msgblue">
        <p class="hide_message">+++ "' . $text['TxTitle'] . '" added! +++</p>
        </div>';

        $service->createTextFromFeed([
            'TxLgID' => $row['NfLgID'],
            'TxTitle' => $text['TxTitle'],
            'TxText' => $text['TxText'],
            'TxAudioURI' => $text['TxAudioURI'] ?? '',
            'TxSourceURI' => $text['TxSourceURI'] ?? ''
        ], $tagName);
    }

    get_texttags(1);

    return $service->archiveOldTexts($tagName, $maxTexts);
}

/**
 * Display errors and messages.
 *
 * @param string $message Message to display
 *
 * @return void
 */
function displayMessages(string $message): void
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
            echo "+++ ", $lf, " +++</p></div>";
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

/**
 * Render the main feeds index page.
 *
 * @param FeedService $service      Feed service instance
 * @param int         $currentLang  Current language filter
 * @param int         $currentFeed  Current feed filter
 *
 * @return void
 */
function renderFeedsIndex(FeedService $service, int $currentLang, int $currentFeed): void
{
    $debug = \Lwt\Core\Globals::isDebug();

    $currentQuery = (string)processSessParam("query", "currentrssquery", '', false);
    $currentQueryMode = (string)processSessParam("query_mode", "currentrssquerymode", 'title,desc,text', false);
    $currentRegexMode = Settings::getWithDefault("set-regex-mode");

    $whQuery = $service->buildQueryFilter($currentQuery, $currentQueryMode, $currentRegexMode);

    if (!empty($currentQuery) && !empty($currentRegexMode)) {
        if (!$service->validateRegexPattern($currentQuery)) {
            $currentQuery = '';
            $whQuery = '';
            unset($_SESSION['currentwordquery']);
            if (isset($_REQUEST['query'])) {
                echo '<p id="hide3" style="color:red;text-align:center;">+++ Warning: Invalid Search +++</p>';
            }
        }
    }

    $currentPage = (int)processSessParam("page", "currentrsspage", '1', true);
    $currentSort = (int)processDBParam("sort", 'currentrsssort', '2', true);

    renderFeedsHeader();
    renderFilterForm($currentLang, $currentQuery, $currentQueryMode, $currentRegexMode);

    $feeds = $service->getFeeds($currentLang ?: null);

    if (empty($feeds)) {
        echo ' no feed available</td><td class="td1"></td></tr></table></form>';
        return;
    }

    renderFeedSelector($feeds, $currentFeed);

    if ($currentFeed == 0 || !in_array($currentFeed, array_column($feeds, 'NfID'))) {
        $currentFeed = $feeds[0]['NfID'];
        $feedIds = (string)$currentFeed;
    } else {
        $feedIds = (string)$currentFeed;
    }

    $recno = $service->countFeedLinks($feedIds, $whQuery);

    if ($debug) {
        echo "Feed IDs: $feedIds, Count: $recno";
    }

    if ($recno > 0) {
        renderFeedArticles($service, $feedIds, $whQuery, $currentPage, $currentSort, $recno);
    } else {
        echo '</table></form>';
    }

    renderNotFoundScript();
}

/**
 * Render the feeds page header with navigation.
 *
 * @return void
 */
function renderFeedsHeader(): void
{
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
    <?php
}

/**
 * Render the filter form.
 *
 * @param int    $currentLang      Current language filter
 * @param string $currentQuery     Current search query
 * @param string $currentQueryMode Current query mode
 * @param string $currentRegexMode Current regex mode
 *
 * @return void
 */
function renderFilterForm(int $currentLang, string $currentQuery, string $currentQueryMode, string $currentRegexMode): void
{
    ?>
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
                <?php echo get_languages_selectoptions($currentLang, '[Filter off]'); ?>
            </select>
        </td>
        <td class="td1 center" colspan="3">
            <select name="query_mode" onchange="{val=document.form1.query.value;mode=document.form1.query_mode.value; location.href='/feeds?page=1&amp;query=' + val + '&amp;query_mode=' + mode;return false;}">
                <option value="title,desc,text"<?php
                if ($currentQueryMode == "title,desc,text") {
                    echo ' selected="selected"';
                } ?>>Title, Desc., Text</option>
                <option disabled="disabled">------------</option>
                <option value="title"<?php
                if ($currentQueryMode == "title") {
                    echo ' selected="selected"';
                } ?>>Title</option>
            </select>
            <span style="vertical-align: middle">
            <?php
            if ($currentRegexMode == '') {
                echo ' (Wildc.=*):';
            } elseif ($currentRegexMode == 'r') {
                echo 'RegEx Mode:';
            } else {
                echo 'RegEx(CS) Mode:';
            }
            ?>
            </span>
            <input type="text" name="query" value="<?php echo tohtml($currentQuery); ?>" maxlength="50" size="15" />&nbsp;
            <input type="button" name="querybutton" value="Filter" onclick="{val=document.form1.query.value;val=encodeURIComponent(val); location.href='/feeds?page=1&amp;query=' + val;return false;}" />&nbsp;
            <input type="button" value="Clear" onclick="{location.href='/feeds?page=1&amp;query=';return false;}" />
        </td>
    </tr>
    <tr>
        <td class="td1 center" colspan="2" style="width:70%;">
    <?php
}

/**
 * Render the feed selector dropdown.
 *
 * @param array $feeds       Array of feed records
 * @param int   $currentFeed Current feed ID (passed by reference)
 *
 * @return void
 */
function renderFeedSelector(array $feeds, int &$currentFeed): void
{
    $time = '';
    $feedsList = '';

    ?>Newsfeed:
    <select name="selected_feed" onchange="{val=document.form1.selected_feed.value;location.href='/feeds?page=1&amp;selected_feed=' + val;return false;}">
        <option value="0">[Filter off]</option>
    <?php
    foreach ($feeds as $row) {
        echo '<option value="' . $row['NfID'] . '"';
        if ($currentFeed === (int)$row['NfID']) {
            echo ' selected="selected"';
            $time = $row['NfUpdate'];
        }
        echo '>' . tohtml($row['NfName']) . '</option>';
        $feedsList .= ',' . $row['NfID'];
    }
    ?>
    </select>
    </td>
    <td class="td1 center" colspan="2">
    <?php

    if ($currentFeed == 0 || $currentFeed == '' || strpos($feedsList, (string)$currentFeed) === false) {
        $currentFeed = (int)$feeds[0]['NfID'];
    }

    if (count($feeds) == 1 || $currentFeed > 0) {
        echo '<a href="' . $_SERVER['PHP_SELF'] . '?page=1&amp;load_feed=1&amp;selected_feed=' . $currentFeed . '">
        <span title="update feed"><img src="/assets/icons/arrow-circle-135.png" alt="-" /></span></a>';
    } else {
        echo '<a href="/feeds/edit?multi_load_feed=1&amp;selected_feed=' . implode(',', array_column($feeds, 'NfID')) . '">
        update multiple feeds</a>';
    }

    if ($time) {
        $diff = time() - (int)$time;
        print_last_feed_update($diff);
    }

    echo '</td></tr>';
}

/**
 * Render the feed articles table.
 *
 * @param FeedService $service     Feed service instance
 * @param string      $feedIds     Comma-separated feed IDs
 * @param string      $whQuery     WHERE clause for filtering
 * @param int         $currentPage Current page number
 * @param int         $currentSort Current sort index
 * @param int         $recno       Total record count
 *
 * @return void
 */
function renderFeedArticles(FeedService $service, string $feedIds, string $whQuery, int $currentPage, int $currentSort, int $recno): void
{
    $maxPerPage = (int)Settings::getWithDefault('set-articles-per-page');
    $pages = $recno == 0 ? 0 : (intval(($recno - 1) / $maxPerPage) + 1);

    if ($currentPage < 1) {
        $currentPage = 1;
    }
    if ($currentPage > $pages) {
        $currentPage = $pages;
    }

    $offset = ($currentPage - 1) * $maxPerPage;
    $sortColumn = $service->getSortColumn($currentSort);

    echo '<tr><th class="th1" style="width:30%;"> ' . $recno . ' articles ';
    echo '</th><th class="th1">';
    makePager($currentPage, $pages, '/feeds', 'form1');
    ?>
  </th>
  <th class="th1" colspan="2" nowrap="nowrap">
  Sort Order:
  <select name="sort" onchange="{val=document.form1.sort.options[document.form1.sort.selectedIndex].value; location.href='/feeds?page=1&amp;sort=' + val;return false;}"><?php echo get_textssort_selectoptions($currentSort); ?></select>
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

    $articles = $service->getFeedLinks($feedIds, $whQuery, $sortColumn, $offset, $maxPerPage);

    foreach ($articles as $row) {
        echo '<tr>';
        if ($row['TxID']) {
            echo '<td class="td1 center"><a href="/text/read?start=' .
            $row['TxID'] . '" >
            <img src="/assets/icons/book-open-bookmark.png" title="Read" alt="-" /></a>';
        } elseif ($row['AtID']) {
            echo '<td class="td1 center"><span title="archived"><img src="/assets/icons/status-busy.png" alt="-" /></span>';
        } elseif (!empty($row['FlLink']) && str_starts_with((string)$row['FlLink'], ' ')) {
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
            <span title="' . htmlentities((string)$row['FlDescription'], ENT_QUOTES, 'UTF-8', false) . '"><b>' .
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
            && !str_starts_with(trim((string)$row['FlLink']), '#')
        ) {
            echo '<a href="' . trim((string)$row['FlLink']) . '"  title="' .
            trim((string)$row['FlLink']) . '" onclick="window.open(\'' .
            $row['FlLink'] . '\');return false;">
            <img src="/assets/icons/external.png" alt="-" /></a>';
        }
        echo '</td><td class="td1 center">' . $row['FlDate'] . '</td></tr>';
    }

    echo '</table>';
    echo '</form>';

    if ($pages > 1) {
        echo '<form name="form3" method="get" action ="">
            <table class="tab2" cellspacing="0" cellpadding="5">
            <tr><th class="th1" style="width:30%;">';
        echo $recno;
        echo '</th><th class="th1">';
        makePager($currentPage, $pages, '/feeds', 'form3');
        echo '</th></tr></table></form>';
    }
}

/**
 * Render the JavaScript for handling not found articles.
 *
 * @return void
 */
function renderNotFoundScript(): void
{
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

/**
 * Main page function.
 *
 * @param FeedService $service Feed service instance
 *
 * @return void
 */
function do_page(FeedService $service): void
{
    session_start();

    $currentLang = Validation::language(
        (string)processDBParam("filterlang", 'currentlanguage', '', false)
    );
    pagestart('My ' . getLanguage($currentLang) . ' Feeds', true);

    $currentFeed = (string)processSessParam(
        "selected_feed",
        "currentrssfeed",
        '',
        false
    );

    $editText = 0;
    $message = '';

    if (isset($_REQUEST['marked_items']) && is_array($_REQUEST['marked_items'])) {
        $result = processMarkedItems($service);
        $editText = $result['editText'];
        $message = $result['message'];
    }

    displayMessages($message);

    if (
        isset($_REQUEST['load_feed']) || isset($_REQUEST['check_autoupdate'])
        || (isset($_REQUEST['markaction']) && $_REQUEST['markaction'] == 'update')
    ) {
        load_feeds((int)$currentFeed);
    } elseif (empty($editText)) {
        renderFeedsIndex($service, (int)$currentLang, (int)$currentFeed);
    }

    pageend();
}
