<?php

/**
 * Feeds Browse View - Main feeds index page
 *
 * Variables expected:
 * - $currentLang: int current language filter
 * - $currentQuery: string search query
 * - $currentQueryMode: string query mode (title,desc,text or title)
 * - $currentRegexMode: string regex mode setting
 * - $feeds: array of feed records
 * - $currentFeed: int current feed ID
 * - $recno: int total article count
 * - $currentPage: int current page number
 * - $currentSort: int current sort index
 * - $maxPerPage: int articles per page
 * - $pages: int total pages
 * - $articles: array of feed article records
 * - $feedTime: int|null last update timestamp
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Views\Feed;

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
<?php if (empty($feeds)): ?>
         no feed available</td><td class="td1"></td></tr></table></form>
<?php return; endif; ?>
Newsfeed:
    <select name="selected_feed" onchange="{val=document.form1.selected_feed.value;location.href='/feeds?page=1&amp;selected_feed=' + val;return false;}">
        <option value="0">[Filter off]</option>
    <?php foreach ($feeds as $row): ?>
        <option value="<?php echo $row['NfID']; ?>"<?php
        if ($currentFeed === (int)$row['NfID']) {
            echo ' selected="selected"';
        }
        ?>><?php echo tohtml($row['NfName']); ?></option>
    <?php endforeach; ?>
    </select>
    </td>
    <td class="td1 center" colspan="2">
    <?php
    if (count($feeds) == 1 || $currentFeed > 0):
        echo '<a href="' . $_SERVER['PHP_SELF'] . '?page=1&amp;load_feed=1&amp;selected_feed=' . $currentFeed . '">
        <span title="update feed"><img src="/assets/icons/arrow-circle-135.png" alt="-" /></span></a>';
    else:
        echo '<a href="/feeds/edit?multi_load_feed=1&amp;selected_feed=' . implode(',', array_column($feeds, 'NfID')) . '">
        update multiple feeds</a>';
    endif;

    if ($lastUpdateFormatted):
        echo ' ' . $lastUpdateFormatted;
    endif;
    ?>
    </td>
</tr>
<?php if ($recno > 0): ?>
<tr><th class="th1" style="width:30%;"> <?php echo $recno; ?> articles </th><th class="th1">
<?php makePager($currentPage, $pages, '/feeds', 'form1'); ?>
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
    <?php foreach ($articles as $row): ?>
        <tr>
        <?php if ($row['TxID']): ?>
            <td class="td1 center"><a href="/text/read?start=<?php echo $row['TxID']; ?>" >
            <img src="/assets/icons/book-open-bookmark.png" title="Read" alt="-" /></a>
        <?php elseif ($row['AtID']): ?>
            <td class="td1 center"><span title="archived"><img src="/assets/icons/status-busy.png" alt="-" /></span>
        <?php elseif (!empty($row['FlLink']) && str_starts_with((string)$row['FlLink'], ' ')): ?>
            <td class="td1 center">
            <img class="not_found" name="<?php echo $row['FlID']; ?>" title="download error" src="/assets/icons/exclamation-button.png" alt="-" />
        <?php else: ?>
            <td class="td1 center"><input type="checkbox" class="markcheck" name="marked_items[]" value="<?php echo $row['FlID']; ?>" />
        <?php endif; ?>
        </td>
            <td class="td1 center">
            <span title="<?php echo htmlentities((string)$row['FlDescription'], ENT_QUOTES, 'UTF-8', false); ?>"><b><?php echo $row['FlTitle']; ?></b></span>
        <?php if ($row['FlAudio']): ?>
            <a href="<?php echo $row['FlAudio']; ?>" onclick="window.open(this.href, 'child', 'scrollbars,width=650,height=600'); return false;">
            <img src="<?php print_file_path('icn/speaker-volume.png'); ?>" alt="-" /></a>
        <?php endif; ?>
        </td>
            <td class="td1 center" style="vertical-align: middle">
        <?php if (!empty($row['FlLink']) && !str_starts_with(trim((string)$row['FlLink']), '#')): ?>
            <a href="<?php echo trim((string)$row['FlLink']); ?>" title="<?php echo trim((string)$row['FlLink']); ?>" onclick="window.open('<?php echo $row['FlLink']; ?>');return false;">
            <img src="/assets/icons/external.png" alt="-" /></a>
        <?php endif; ?>
        </td><td class="td1 center"><?php echo $row['FlDate']; ?></td></tr>
    <?php endforeach; ?>

    </table>
    </form>

<?php if ($pages > 1): ?>
    <form name="form3" method="get" action ="">
        <table class="tab2" cellspacing="0" cellpadding="5">
        <tr><th class="th1" style="width:30%;"><?php echo $recno; ?></th><th class="th1">
    <?php makePager($currentPage, $pages, '/feeds', 'form3'); ?>
        </th></tr></table></form>
<?php endif; ?>
<?php else: ?>
</table></form>
<?php endif; ?>

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
