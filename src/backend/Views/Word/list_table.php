<?php declare(strict_types=1);
/**
 * Word list table view
 *
 * Variables expected:
 * - $recno: Total record count
 * - $currentlang: Current language filter
 * - $currentpage: Current page number
 * - $currentsort: Current sort option
 * - $pages: Total pages
 * - $words: Array of word records from query result
 *
 * PHP version 8.1
 */

namespace Lwt\Views\Word;
?>
<?php if ($recno == 0) { ?>
<p>No terms found.</p>
<?php } else { ?>
<form name="form2" action="/words/edit" method="post">
<input type="hidden" name="data" value="" />
<table class="tab2" cellspacing="0" cellpadding="5">
<tr><th class="th1 center" colspan="2">
Multi Actions <img src="/assets/icons/lightning.png" title="Multi Actions" alt="Multi Actions" />
</th></tr>
<tr><td class="td1 center" colspan="2">
<b>ALL</b> <?php echo ($recno == 1 ? '1 Term' : $recno . ' Terms'); ?>:&nbsp;
<select name="allaction" data-action="all-action" data-recno="<?php echo $recno; ?>">
    <?php echo \Lwt\View\Helper\SelectOptionsBuilder::forAllWordsActions(); ?>
</select>
</td></tr>
<tr><td class="td1 center">
<input type="button" value="Mark All" data-action="mark-all" />
<input type="button" value="Mark None" data-action="mark-none" />
</td>
<td class="td1 center">Marked Terms:&nbsp;
<select name="markaction" id="markaction" disabled="disabled" data-action="mark-action">
    <?php echo \Lwt\View\Helper\SelectOptionsBuilder::forMultipleWordsActions(); ?>
</select>
</td></tr></table>

<table class="sortable tab2" cellspacing="0" cellpadding="5">
<tr>
<th class="th1 sorttable_nosort">Mark</th>
<th class="th1 sorttable_nosort">Act.</th>
<?php if ($currentlang == '') { echo '<th class="th1 clickable">Lang.</th>'; } ?>
<th class="th1 clickable">Term /<br />Romanization</th>
<th class="th1 clickable">Translation [Tags]<br /><span id="waitinfo">Please <img src="<?php print_file_path('icn/waiting2.gif'); ?>" /> wait ...</span></th>
<th class="th1 sorttable_nosort">Se.<br />?</th>
<th class="th1 sorttable_numeric clickable">Stat./<br />Days</th>
<th class="th1 sorttable_numeric clickable">Score<br />%</th>
<?php if ($currentsort == 7) { ?>
<th class="th1 sorttable_numeric clickable" title="Word Count in Active Texts">WCnt<br />Txts</th>
<?php } ?>
</tr>

<?php
foreach ($words as $record) {
    $days = $record['Days'];
    if ($record['WoStatus'] > 5) {
        $days = "-";
    }
    $score = $record['Score'];
    if ($score < 0) {
        $score = '<span class="scorered">0 <img src="/assets/icons/status-busy.png" title="Test today!" alt="Test today!" /></span>';
    } else {
        $score = '<span class="scoregreen">' . floor((int)$score) . ($record['Score2'] < 0 ? ' <img src="/assets/icons/status-away.png" title="Test tomorrow!" alt="Test tomorrow!" />' : ' <img src="/assets/icons/status.png" title="-" alt="-" />') . '</span>';
    }
    ?>
<tr>
    <td class="td1 center"><a name="rec<?php echo $record['WoID']; ?>"><input name="marked[]" type="checkbox" class="markcheck" value="<?php echo $record['WoID']; ?>" <?php echo \Lwt\View\Helper\FormHelper::checkInRequest($record['WoID'], 'marked'); ?> /></a></td>
    <td class="td1 center" nowrap="nowrap">&nbsp;<a href="/words/edit?chg=<?php echo $record['WoID']; ?>"><img src="/assets/icons/sticky-note--pencil.png" title="Edit" alt="Edit" /></a>&nbsp; <a class="confirmdelete" href="/words/edit?del=<?php echo $record['WoID']; ?>"><img src="/assets/icons/minus-button.png" title="Delete" alt="Delete" /></a>&nbsp;</td>
<?php if ($currentlang == '') { ?>
    <td class="td1 center"><?php echo htmlspecialchars($record['LgName'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
<?php } ?>
    <td class="td1"><span<?php
    if (!empty($record['LgGoogleTranslateURI']) && strpos((string) $record['LgGoogleTranslateURI'], '&sl=') !== false) {
        echo ' class="tts_' . preg_replace('/.*[?&]sl=([a-zA-Z\-]*)(&.*)*$/', '$1', $record['LgGoogleTranslateURI']) . '"';
    }
    echo ($record['LgRightToLeft'] ? ' dir="rtl" ' : '');
    ?>><?php echo htmlspecialchars($record['WoText'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span><?php
    echo ($record['WoRomanization'] != '' ? (' / <span id="roman' . $record['WoID'] . '" class="edit_area clickedit">' . htmlspecialchars(\Lwt\Services\ExportService::replaceTabNewline($record['WoRomanization']) ?? '', ENT_QUOTES, 'UTF-8') . '</span>') : (' / <span id="roman' . $record['WoID'] . '" class="edit_area clickedit">*</span>'));
    ?></td>
    <td class="td1"><span id="trans<?php echo $record['WoID']; ?>" class="edit_area clickedit"><?php echo htmlspecialchars(\Lwt\Services\ExportService::replaceTabNewline($record['WoTranslation']) ?? '', ENT_QUOTES, 'UTF-8'); ?></span> <span class="smallgray2"><?php echo htmlspecialchars($record['taglist'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span></td>
    <td class="td1 center"><b><?php echo ($record['SentOK'] != 0 ? '<img src="/assets/icons/status.png" title="' . htmlspecialchars($record['WoSentence'] ?? '', ENT_QUOTES, 'UTF-8') . '" alt="Yes" />' : '<img src="/assets/icons/status-busy.png" title="(No valid sentence)" alt="No" />'); ?></b></td>
    <td class="td1 center" title="<?php echo htmlspecialchars(\Lwt\View\Helper\StatusHelper::getName((int)$record['WoStatus']) ?? '', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(\Lwt\View\Helper\StatusHelper::getAbbr((int)$record['WoStatus']) ?? '', ENT_QUOTES, 'UTF-8'); ?><?php echo ($record['WoStatus'] < 98 ? '/' . $days : ''); ?></td>
    <td class="td1 center" nowrap="nowrap"><?php echo $score; ?></td>
<?php if ($currentsort == 7) { ?>
    <td class="td1 center" nowrap="nowrap"><?php echo $record['textswordcount'] ?? 0; ?></td>
<?php } ?>
</tr>
<?php } ?>
</table>

<?php if ($pages > 1) { ?>
<table class="tab2" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1" nowrap="nowrap">
            <?php echo $recno; ?> Term<?php echo ($recno == 1 ? '' : 's'); ?>
        </th>
        <th class="th1" nowrap="nowrap">
            <?php echo \Lwt\View\Helper\PageLayoutHelper::buildPager($currentpage, $pages, '/words/edit', 'form2'); ?>
        </th>
    </tr>
</table>
</form>
<?php } ?>
<?php } ?>
