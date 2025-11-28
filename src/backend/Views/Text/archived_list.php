<?php

/**
 * Archived Text List View - Display list of archived texts with filtering
 *
 * Variables expected:
 * - $message: string - Status/error message to display
 * - $texts: array - Array of archived text records
 * - $totalCount: int - Total number of archived texts matching filter
 * - $pagination: array - Array with 'pages', 'currentPage', 'limit'
 * - $currentLang: string - Current language filter
 * - $currentQuery: string - Current filter query
 * - $currentQueryMode: string - Current query mode
 * - $currentRegexMode: string - Current regex mode
 * - $currentSort: int - Current sort index
 * - $currentTag1: string|int - First tag filter
 * - $currentTag2: string|int - Second tag filter
 * - $currentTag12: string - AND/OR operator
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

echo error_message_with_hide($message, false);

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
                <?php echo get_languages_selectoptions($currentLang, '[Filter off]'); ?>
            </select>
        </td>
        <td class="td1 center" colspan="2">
            <select name="query_mode" onchange="{val=document.form1.query.value;mode=document.form1.query_mode.value; location.href='/text/archived?page=1&amp;query=' + val + '&amp;query_mode=' + mode;}">
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
            <input type="button" name="querybutton" value="Filter" onclick="{val=document.form1.query.value;val=encodeURIComponent(val); location.href='/text/archived?page=1&amp;query=' + val;}" />&nbsp;
            <input type="button" value="Clear" onclick="{location.href='/text/archived?page=1&amp;query=';}" />
        </td>
    </tr>
    <tr>
        <td class="td1 center" colspan="2" nowrap="nowrap">
            Tag #1:
            <select name="tag1" onchange="{val=document.form1.tag1.options[document.form1.tag1.selectedIndex].value; location.href='/text/archived?page=1&amp;tag1=' + val;}">
                <?php echo get_archivedtexttag_selectoptions($currentTag1, $currentLang); ?>
            </select>
        </td>
        <td class="td1 center" nowrap="nowrap">
            Tag #1 ..
            <select name="tag12" onchange="{val=document.form1.tag12.options[document.form1.tag12.selectedIndex].value; location.href='/text/archived?page=1&amp;tag12=' + val;}">
                <?php echo get_andor_selectoptions($currentTag12); ?>
            </select> .. Tag #2
        </td>
        <td class="td1 center" nowrap="nowrap">
            Tag #2:
            <select name="tag2" onchange="{val=document.form1.tag2.options[document.form1.tag2.selectedIndex].value; location.href='/text/archived?page=1&amp;tag2=' + val;}">
                <?php echo get_archivedtexttag_selectoptions($currentTag2, $currentLang); ?>
            </select>
        </td>
    </tr>
    <?php if ($totalCount > 0): ?>
    <tr>
        <th class="th1" colspan="2" nowrap="nowrap">
            <?php echo $totalCount; ?> Text<?php echo $totalCount == 1 ? '' : 's'; ?>
        </th>
        <th class="th1" colspan="1" nowrap="nowrap">
            <?php makePager($pagination['currentPage'], $pagination['pages'], '/text/archived', 'form1'); ?>
        </th>
        <th class="th1" nowrap="nowrap">
            Sort Order:
            <select name="sort" onchange="{val=document.form1.sort.options[document.form1.sort.selectedIndex].value; location.href='/text/archived?page=1&amp;sort=' + val;}">
                <?php echo get_textssort_selectoptions($currentSort); ?>
            </select>
        </th>
    </tr>
    <?php endif; ?>
</table>
</form>

<?php if ($totalCount == 0): ?>
<p>No archived texts found.</p>
<?php else: ?>
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
            <select name="markaction" id="markaction" disabled="disabled" onchange="multiActionGo(document.form2, document.form2.markaction);">
                <?php echo get_multiplearchivedtextactions_selectoptions(); ?>
            </select>
        </td>
    </tr>
</table>

<table class="sortable tab2" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1 sorttable_nosort">Mark</th>
        <th class="th1 sorttable_nosort">Actions</th>
        <?php if ($currentLang == ''): ?>
        <th class="th1 clickable">Lang.</th>
        <?php endif; ?>
        <th class="th1 clickable">
            Title [Tags] / Audio:&nbsp;
            <img src="<?php print_file_path('icn/speaker-volume.png'); ?>" title="With Audio" alt="With Audio" />, Src.Link:&nbsp;
            <img src="<?php print_file_path('icn/chain.png'); ?>" title="Source Link available" alt="Source Link available" />, Ann.Text:&nbsp;
            <img src="/assets/icons/tick.png" title="Annotated Text available" alt="Annotated Text available" />
        </th>
    </tr>
    <?php foreach ($texts as $record): ?>
    <tr>
        <td class="td1 center">
            <a name="rec<?php echo $record['AtID']; ?>">
            <input name="marked[]" class="markcheck" type="checkbox" value="<?php echo $record['AtID']; ?>" <?php echo checkTest($record['AtID'], 'marked'); ?> />
            </a>
        </td>
        <td nowrap="nowrap" class="td1 center">&nbsp;
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>?unarch=<?php echo $record['AtID']; ?>">
                <img src="/assets/icons/inbox-upload.png" title="Unarchive" alt="Unarchive" />
            </a>&nbsp;
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>?chg=<?php echo $record['AtID']; ?>">
                <img src="/assets/icons/document--pencil.png" title="Edit" alt="Edit" />
            </a>&nbsp;
            <span class="click" onclick="if (confirmDelete()) location.href='<?php echo $_SERVER['PHP_SELF']; ?>?del=<?php echo $record['AtID']; ?>';">
                <img src="/assets/icons/minus-button.png" title="Delete" alt="Delete" />
            </span>&nbsp;
        </td>
        <?php if ($currentLang == ''): ?>
        <td class="td1 center"><?php echo tohtml($record['LgName']); ?></td>
        <?php endif; ?>
        <td class="td1 center">
            <?php echo tohtml($record['AtTitle']); ?>
            <span class="smallgray2"><?php echo tohtml($record['taglist']); ?></span> &nbsp;
            <?php if (isset($record['AtAudioURI']) && $record['AtAudioURI']): ?>
            <img src="<?php echo get_file_path('assets/icons/speaker-volume.png'); ?>" title="With Audio" alt="With Audio" />
            <?php endif; ?>
            <?php if (isset($record['AtSourceURI']) && $record['AtSourceURI']): ?>
            <a href="<?php echo $record['AtSourceURI']; ?>" target="_blank">
                <img src="<?php echo get_file_path('assets/icons/chain.png'); ?>" title="Link to Text Source" alt="Link to Text Source" />
            </a>
            <?php endif; ?>
            <?php if ($record['annotlen']): ?>
            <img src="/assets/icons/tick.png" title="Annotated Text available" alt="Annotated Text available" />
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<?php if ($pagination['pages'] > 1): ?>
<table class="tab2" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1" nowrap="nowrap">
            <?php echo $totalCount; ?> Text<?php echo $totalCount == 1 ? '' : 's'; ?>
        </th>
        <th class="th1" nowrap="nowrap">
            <?php makePager($pagination['currentPage'], $pagination['pages'], '/text/archived', 'form2'); ?>
        </th>
    </tr>
</table>
<?php endif; ?>
</form>
<?php endif; ?>
