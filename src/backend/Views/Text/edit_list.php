<?php

/**
 * Active Text List View - Display list of active texts with filtering
 *
 * Variables expected:
 * - $message: string - Status/error message to display
 * - $texts: array - Array of text records
 * - $totalCount: int - Total number of texts matching filter
 * - $pagination: array - Array with 'pages', 'currentPage', 'limit'
 * - $currentLang: string - Current language filter
 * - $currentQuery: string - Current filter query
 * - $currentQueryMode: string - Current query mode
 * - $currentRegexMode: string - Current regex mode
 * - $currentSort: int - Current sort index
 * - $currentTag1: string|int - First tag filter
 * - $currentTag2: string|int - Second tag filter
 * - $currentTag12: string - AND/OR operator
 * - $showCounts: string - 5-character string for word count display settings
 * - $statuses: array - Word status definitions
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @psalm-suppress UndefinedVariable - Variables are set by the including controller
 */

namespace Lwt\Views\Text;

/** @var string $message */
/** @var array $texts */
/** @var int $totalCount */
/** @var array $pagination */
/** @var string $currentLang */
/** @var string $currentQuery */
/** @var string $currentQueryMode */
/** @var string $currentRegexMode */
/** @var int $currentSort */
/** @var string|int $currentTag1 */
/** @var string|int $currentTag2 */
/** @var string $currentTag12 */
/** @var string $showCounts */
/** @var array $statuses */

?>
<link rel="stylesheet" type="text/css" href="<?php print_file_path('css/css_charts.css');?>" />

<?php \Lwt\View\Helper\PageLayoutHelper::renderMessage($message, false); ?>

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
        <a href="/text/archived?query=&amp;page=1">
            <img src="/assets/icons/drawer--minus.png">
            Archived Texts
        </a>
    </div>
</div>

<form name="form1" action="#" onsubmit="document.form1.querybutton.click(); return false;">
<table class="tab2" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1" colspan="4">Filter <img src="/assets/icons/funnel.png" title="Filter" alt="Filter" />&nbsp;
            <input type="button" value="Reset All" onclick="resetAll('/texts');" />
        </th>
    </tr>
    <tr>
        <td class="td1 center" colspan="2">
            Language:
            <select name="filterlang" onchange="{setLang(document.form1.filterlang,'/texts');}">
                <?php echo get_languages_selectoptions($currentLang, '[Filter off]'); ?>
            </select>
        </td>
        <td class="td1 center" colspan="2">
            <select name="query_mode" onchange="{val=document.form1.query.value;mode=document.form1.query_mode.value; location.href='/texts?page=1&amp;query=' + val + '&amp;query_mode=' + mode;}">
                <option value="title,text"<?php echo $currentQueryMode == "title,text" ? ' selected="selected"' : ''; ?>>Title &amp; Text</option>
                <option disabled="disabled">------------</option>
                <option value="title"<?php echo $currentQueryMode == "title" ? ' selected="selected"' : ''; ?>>Title</option>
                <option value="text"<?php echo $currentQueryMode == "text" ? ' selected="selected"' : ''; ?>>Text</option>
            </select>
            <?php
            if ($currentRegexMode == '') {
                echo '<span style="vertical-align: middle"> (Wildc.=*): </span>';
            } elseif ($currentRegexMode == 'r') {
                echo '<span style="vertical-align: middle"> RegEx Mode: </span>';
            } else {
                echo '<span style="vertical-align: middle"> RegEx(CS) Mode: </span>';
            }
            ?>
            <input type="text" name="query" value="<?php echo tohtml($currentQuery); ?>" maxlength="50" size="15" />&nbsp;
            <input type="button" name="querybutton" value="Filter" onclick="{val=document.form1.query.value;val=encodeURIComponent(val); location.href='/texts?page=1&amp;query=' + val;}" />&nbsp;
            <input type="button" value="Clear" onclick="{location.href='/texts?page=1&amp;query=';}" />
        </td>
    </tr>
    <tr>
        <td class="td1 center" colspan="2">
            Tag #1:
            <select name="tag1" onchange="{val=document.form1.tag1.options[document.form1.tag1.selectedIndex].value; location.href='/texts?page=1&amp;tag1=' + val;}">
                <?php echo \Lwt\Services\TagService::getTextTagSelectOptions($currentTag1, $currentLang); ?>
            </select>
        </td>
        <td class="td1 center">
            Tag #1 ..
            <select name="tag12" onchange="{val=document.form1.tag12.options[document.form1.tag12.selectedIndex].value; location.href='/texts?page=1&amp;tag12=' + val;}">
                <?php echo get_andor_selectoptions($currentTag12); ?>
            </select> .. Tag #2
        </td>
        <td class="td1 center">
            Tag #2:
            <select name="tag2" onchange="{val=document.form1.tag2.options[document.form1.tag2.selectedIndex].value; location.href='/texts?page=1&amp;tag2=' + val;}">
                <?php echo \Lwt\Services\TagService::getTextTagSelectOptions($currentTag2, $currentLang); ?>
            </select>
        </td>
    </tr>
    <?php if ($totalCount > 0): ?>
    <tr>
        <th class="th1" colspan="2">
            <?php echo $totalCount; ?> Text<?php echo $totalCount == 1 ? '' : 's'; ?>
        </th>
        <th class="th1" colspan="1">
            <?php makePager($pagination['currentPage'], $pagination['pages'], '/texts', 'form1'); ?>
        </th>
        <th class="th1" colspan="1">
            Sort Order:
            <select name="sort" onchange="{val=document.form1.sort.options[document.form1.sort.selectedIndex].value; location.href='/texts?page=1&amp;sort=' + val;}">
                <?php echo get_textssort_selectoptions($currentSort); ?>
            </select>
        </th>
    </tr>
    <?php endif; ?>
</table>
</form>

<?php if ($totalCount == 0): ?>
<p>No text found.</p>
<?php else: ?>
<form name="form2" action="/texts" method="post">
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
            <select name="markaction" id="markaction" disabled="disabled" onchange="multiActionGo(document.form2, document.form2.markaction);">
                <?php echo get_multipletextactions_selectoptions(); ?>
            </select>
        </td>
    </tr>
</table>
<table class="sortable tab2" cellspacing="0" cellpadding="5">
<thead>
    <tr>
        <th class="th1 sorttable_nosort">Mark</th>
        <th class="th1 sorttable_nosort">Read<br />&amp;&nbsp;Test</th>
        <th class="th1 sorttable_nosort">Actions</th>
        <?php if ($currentLang == ''): ?>
        <th class="th1 clickable">Lang.</th>
        <?php endif; ?>
        <th class="th1 clickable">
            Title [Tags] / Audio:&nbsp;
            <img src="<?php print_file_path('icn/speaker-volume.png'); ?>" title="With Audio" alt="With Audio" />,
            Src.Link:&nbsp;
            <img src="<?php print_file_path('icn/chain.png'); ?>" title="Source Link available" alt="Source Link available" />,
            Ann.Text:&nbsp;
            <img src="/assets/icons/tick.png" title="Annotated Text available" alt="Annotated Text available" />
        </th>
        <th class="th1 sorttable_numeric clickable">
            Total<br />Words<br />
            <div class="wc_cont">
                <span id="total" data_wo_cnt="<?php echo substr($showCounts, 0, 1); ?>"></span>
            </div>
        </th>
        <th class="th1 sorttable_numeric clickable">
            Saved<br />Wo+Ex<br />
            <div class="wc_cont">
                <span id="saved" data_wo_cnt="<?php echo substr($showCounts, 1, 1); ?>"></span>
            </div>
        </th>
        <th class="th1 sorttable_numeric clickable">
            Unkn.<br />Words<br />
            <div class="wc_cont">
                <span id="unknown" data_wo_cnt="<?php echo substr($showCounts, 2, 1); ?>"></span>
            </div>
        </th>
        <th class="th1 sorttable_numeric clickable">
            Unkn.<br />Perc.<br />
            <div class="wc_cont">
                <span id="unknownpercent" data_wo_cnt="<?php echo substr($showCounts, 3, 1); ?>"></span>
            </div>
        </th>
        <th class="th1 sorttable_numeric clickable">
            Status<br />Charts<br />
            <div class="wc_cont">
                <span id="chart" data_wo_cnt="<?php echo substr($showCounts, 4, 1); ?>"></span>
            </div>
        </th>
    </tr>
</thead>
<tbody>
    <?php foreach ($texts as $record):
        $txid = $record['TxID'];
        $audio = isset($record['TxAudioURI']) ? trim($record['TxAudioURI']) : '';
    ?>
    <tr>
        <td class="td1 center">
            <a name="rec<?php echo $txid; ?>">
                <input name="marked[]" class="markcheck" type="checkbox" value="<?php echo $txid; ?>" <?php echo checkTest($txid, 'marked'); ?> />
            </a>
        </td>
        <td class="td1 center">
            <a href="/text/read?start=<?php echo $txid; ?>">
                <img src="/assets/icons/book-open-bookmark.png" title="Read" alt="Read" />
            </a>
            <a href="/test?text=<?php echo $txid; ?>">
                <img src="/assets/icons/question-balloon.png" title="Test" alt="Test" />
            </a>
        </td>
        <td class="td1 center">
            <a href="/text/print-plain?text=<?php echo $txid; ?>">
                <img src="/assets/icons/printer.png" title="Print" alt="Print" />
            </a>
            <a href="/texts?arch=<?php echo $txid; ?>">
                <img src="/assets/icons/inbox-download.png" title="Archive" alt="Archive" />
            </a>
            <a href="/texts?chg=<?php echo $txid; ?>">
                <img src="/assets/icons/document--pencil.png" title="Edit" alt="Edit" />
            </a>
            <span class="click" onclick="if (confirmDelete()) location.href='/texts?del=<?php echo $txid; ?>';">
                <img src="/assets/icons/minus-button.png" title="Delete" alt="Delete" />
            </span>
        </td>
        <?php if ($currentLang == ''): ?>
        <td class="td1 center"><?php echo tohtml($record['LgName']); ?></td>
        <?php endif; ?>
        <td class="td1 center">
            <?php echo tohtml($record['TxTitle']); ?>
            <span class="smallgray2"><?php echo tohtml($record['taglist']); ?></span> &nbsp;
            <?php if ($audio != ''): ?>
            <img src="<?php echo get_file_path('assets/icons/speaker-volume.png'); ?>" title="With Audio" alt="With Audio" />
            <?php endif; ?>
            <?php if (isset($record['TxSourceURI']) && substr(trim($record['TxSourceURI']), 0, 1) != '#'): ?>
            <a href="<?php echo $record['TxSourceURI']; ?>" target="_blank">
                <img src="<?php echo get_file_path('assets/icons/chain.png'); ?>" title="Link to Text Source" alt="Link to Text Source" />
            </a>
            <?php endif; ?>
            <?php if ($record['annotlen']): ?>
            <a href="/text/print?text=<?php echo $txid; ?>">
                <img src="/assets/icons/tick.png" title="Annotated Text available" alt="Annotated Text available" />
            </a>
            <?php endif; ?>
        </td>
        <!-- Word count columns -->
        <td class="td1 center">
            <span title="Total" id="total_<?php echo $txid; ?>"></span>
        </td>
        <td class="td1 center">
            <span title="Saved" data_id="<?php echo $txid; ?>">
                <a class="status4" id="saved_<?php echo $txid; ?>"
                href="/words/edit?page=1&amp;query=&amp;status=&amp;tag12=0&amp;tag2=&amp;tag1=&amp;text_mode=0&amp;text=<?php echo $txid; ?>">
                </a>
            </span>
        </td>
        <td class="td1 center">
            <span title="Unknown" class="status0" id="todo_<?php echo $txid; ?>"></span>
        </td>
        <td class="td1 center">
            <span title="Unknown (%)" id="unknownpercent_<?php echo $txid; ?>"></span>
        </td>
        <td class="td1 center">
            <ul class="barchart">
                <?php
                $statusOrder = array(0,1,2,3,4,5,99,98);
                foreach ($statusOrder as $statusNum): ?>
                <li class="bc<?php echo $statusNum; ?>"
                    title="<?php echo $statuses[$statusNum]["name"]; ?> (<?php echo $statuses[$statusNum]["abbr"]; ?>)"
                    style="border-top-width: 25px;">
                    <span id="stat_<?php echo $statusNum; ?>_<?php echo $txid; ?>">0</span>
                </li>
                <?php endforeach; ?>
            </ul>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>
</table>

<?php if ($pagination['pages'] > 1): ?>
<table class="tab2" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1">
            <?php echo $totalCount; ?> Text<?php echo $totalCount == 1 ? '' : 's'; ?>
        </th>
        <th class="th1">
            <?php makePager($pagination['currentPage'], $pagination['pages'], '/texts', 'form2'); ?>
        </th>
    </tr>
</table>
<?php endif; ?>
</form>

<script type="application/json" id="text-list-config"><?php echo json_encode(['showCounts' => intval($showCounts, 2)]); ?></script>
<?php endif; ?>
