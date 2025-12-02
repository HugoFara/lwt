<?php declare(strict_types=1);
/**
 * Archived Text List View - Display list of archived texts with filtering
 *
 * Variables expected:
 * - $message: string - Status/error message to display
 * - $texts: array - Array of archived text records
 * - $totalCount: int - Total number of archived texts matching filter
 * - $pagination: array - Array with 'pages', 'currentPage', 'limit'
 * - $languages: array - Array of languages for select dropdown
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

use Lwt\View\Helper\IconHelper;
use Lwt\View\Helper\PageLayoutHelper;

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

PageLayoutHelper::renderMessage($message, false);

echo PageLayoutHelper::buildActionCard(
    'Archive Actions',
    [
        ['url' => '/texts?new=1', 'label' => 'New Text', 'icon' => 'circle-plus', 'class' => 'is-primary'],
        ['url' => '/text/import-long', 'label' => 'Long Text Import', 'icon' => 'file-up'],
        ['url' => '/feeds?page=1&check_autoupdate=1', 'label' => 'Newsfeed Import', 'icon' => 'rss'],
        ['url' => '/texts?query=&page=1', 'label' => 'Active Texts', 'icon' => 'book-open'],
    ],
    'texts'
);
?>

<form name="form1" action="#" data-base-url="/text/archived">
<table class="tab2" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1" colspan="4">Filter <?php echo IconHelper::render('filter', ['title' => 'Filter', 'alt' => 'Filter']); ?>&nbsp;
            <input type="button" value="Reset All" data-action="reset-all" />
        </th>
    </tr>
    <tr>
        <td class="td1 center" colspan="2">
            Language:
            <select name="filterlang" data-action="filter-language">
                <?php echo \Lwt\View\Helper\SelectOptionsBuilder::forLanguages($languages, $currentLang, '[Filter off]'); ?>
            </select>
        </td>
        <td class="td1 center" colspan="2">
            <select name="query_mode" data-action="filter-query-mode">
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
            <input type="text" name="query" value="<?php echo htmlspecialchars($currentQuery ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="50" size="15" />&nbsp;
            <input type="button" name="querybutton" value="Filter" data-action="filter" />&nbsp;
            <input type="button" value="Clear" data-action="clear-filter" />
        </td>
    </tr>
    <tr>
        <td class="td1 center" colspan="2" nowrap="nowrap">
            Tag #1:
            <select name="tag1" data-action="filter-tag" data-tag-num="1">
                <?php echo \Lwt\Services\TagService::getArchivedTextTagSelectOptions($currentTag1, $currentLang); ?>
            </select>
        </td>
        <td class="td1 center" nowrap="nowrap">
            Tag #1 ..
            <select name="tag12" data-action="filter-tag-operator">
                <?php echo \Lwt\View\Helper\SelectOptionsBuilder::forAndOr($currentTag12); ?>
            </select> .. Tag #2
        </td>
        <td class="td1 center" nowrap="nowrap">
            Tag #2:
            <select name="tag2" data-action="filter-tag" data-tag-num="2">
                <?php echo \Lwt\Services\TagService::getArchivedTextTagSelectOptions($currentTag2, $currentLang); ?>
            </select>
        </td>
    </tr>
    <?php if ($totalCount > 0): ?>
    <tr>
        <th class="th1" colspan="2" nowrap="nowrap">
            <?php echo $totalCount; ?> Text<?php echo $totalCount == 1 ? '' : 's'; ?>
        </th>
        <th class="th1" colspan="1" nowrap="nowrap">
            <?php echo \Lwt\View\Helper\PageLayoutHelper::buildPager($pagination['currentPage'], $pagination['pages'], '/text/archived', 'form1'); ?>
        </th>
        <th class="th1" nowrap="nowrap">
            Sort Order:
            <select name="sort" data-action="sort">
                <?php echo \Lwt\View\Helper\SelectOptionsBuilder::forTextSort($currentSort); ?>
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
            <?php echo IconHelper::render('zap', ['title' => 'Multi Actions', 'alt' => 'Multi Actions']); ?>
        </th>
    </tr>
    <tr>
        <td class="td1 center">
            <input type="button" value="Mark All" data-action="mark-toggle" data-mark-all="true" />
            <input type="button" value="Mark None" data-action="mark-toggle" data-mark-all="false" />
        </td>
        <td class="td1 center">
            Marked Texts:&nbsp;
            <select name="markaction" id="markaction" disabled="disabled" data-action="multi-action">
                <?php echo \Lwt\View\Helper\SelectOptionsBuilder::forMultipleArchivedTextsActions(); ?>
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
            <?php echo IconHelper::render('volume-2', ['title' => 'With Audio', 'alt' => 'With Audio']); ?>, Src.Link:&nbsp;
            <?php echo IconHelper::render('link', ['title' => 'Source Link available', 'alt' => 'Source Link available']); ?>, Ann.Text:&nbsp;
            <?php echo IconHelper::render('check', ['title' => 'Annotated Text available', 'alt' => 'Annotated Text available']); ?>
        </th>
    </tr>
    <?php foreach ($texts as $record): ?>
    <tr>
        <td class="td1 center">
            <a name="rec<?php echo $record['AtID']; ?>">
            <input name="marked[]" class="markcheck" type="checkbox" value="<?php echo $record['AtID']; ?>" <?php echo \Lwt\View\Helper\FormHelper::checkInRequest($record['AtID'], 'marked'); ?> />
            </a>
        </td>
        <td nowrap="nowrap" class="td1 center">&nbsp;
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>?unarch=<?php echo $record['AtID']; ?>">
                <?php echo IconHelper::render('archive-restore', ['title' => 'Unarchive', 'alt' => 'Unarchive']); ?>
            </a>&nbsp;
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>?chg=<?php echo $record['AtID']; ?>">
                <?php echo IconHelper::render('file-pen', ['title' => 'Edit', 'alt' => 'Edit']); ?>
            </a>&nbsp;
            <span class="click" data-action="confirm-delete" data-url="<?php echo $_SERVER['PHP_SELF']; ?>?del=<?php echo $record['AtID']; ?>">
                <?php echo IconHelper::render('circle-minus', ['title' => 'Delete', 'alt' => 'Delete']); ?>
            </span>&nbsp;
        </td>
        <?php if ($currentLang == ''): ?>
        <td class="td1 center"><?php echo htmlspecialchars($record['LgName'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
        <?php endif; ?>
        <td class="td1 center">
            <?php echo htmlspecialchars($record['AtTitle'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
            <span class="smallgray2"><?php echo htmlspecialchars($record['taglist'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span> &nbsp;
            <?php if (isset($record['AtAudioURI']) && $record['AtAudioURI']): ?>
            <?php echo IconHelper::render('volume-2', ['title' => 'With Audio', 'alt' => 'With Audio']); ?>
            <?php endif; ?>
            <?php if (isset($record['AtSourceURI']) && $record['AtSourceURI']): ?>
            <a href="<?php echo $record['AtSourceURI']; ?>" target="_blank">
                <?php echo IconHelper::render('link', ['title' => 'Link to Text Source', 'alt' => 'Link to Text Source']); ?>
            </a>
            <?php endif; ?>
            <?php if ($record['annotlen']): ?>
            <?php echo IconHelper::render('check', ['title' => 'Annotated Text available', 'alt' => 'Annotated Text available']); ?>
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
            <?php echo \Lwt\View\Helper\PageLayoutHelper::buildPager($pagination['currentPage'], $pagination['pages'], '/text/archived', 'form2'); ?>
        </th>
    </tr>
</table>
<?php endif; ?>
</form>
<?php endif; ?>
