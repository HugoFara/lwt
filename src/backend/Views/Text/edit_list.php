<?php declare(strict_types=1);
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

use Lwt\View\Helper\SelectOptionsBuilder;
use Lwt\View\Helper\PageLayoutHelper;
use Lwt\View\Helper\FormHelper;
use Lwt\View\Helper\IconHelper;

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
<link rel="stylesheet" type="text/css" href="<?php \print_file_path('css/css_charts.css');?>" />

<?php \Lwt\View\Helper\PageLayoutHelper::renderMessage($message, false); ?>

<?php
echo PageLayoutHelper::buildActionCard([
    ['url' => '/texts?new=1', 'label' => 'New Text', 'icon' => 'circle-plus', 'class' => 'is-primary'],
    ['url' => '/text/import-long', 'label' => 'Long Text Import', 'icon' => 'file-up'],
    ['url' => '/feeds?page=1&check_autoupdate=1', 'label' => 'Newsfeed Import', 'icon' => 'rss'],
    ['url' => '/text/archived?query=&page=1', 'label' => 'Archived Texts', 'icon' => 'archive'],
]);
?>

<!-- TODO: Make this search bar functional once the UI refactoring of this page is done.
     This search bar should support:
     - Universal search across title and text content
     - Filter chips for active filters (language, tags)
     - Autocomplete suggestions
     - Advanced filter toggle for power users
-->
<form name="form1" action="#" data-base-url="/texts" data-search-placeholder="texts">
    <div class="box mb-4">
        <div class="field has-addons">
            <div class="control is-expanded has-icons-left">
                <input type="text"
                       name="query"
                       class="input"
                       value="<?php echo \htmlspecialchars($currentQuery ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="Search texts... (e.g., lang:Spanish tag:news title)"
                       disabled />
                <span class="icon is-left">
                    <?php echo IconHelper::render('search', ['alt' => 'Search']); ?>
                </span>
            </div>
            <div class="control">
                <button type="button" class="button is-info" disabled>
                    Search
                </button>
            </div>
        </div>
        <p class="help has-text-grey">
            <?php echo IconHelper::render('info', ['alt' => 'Info', 'class' => 'icon-inline']); ?>
            Search functionality is being redesigned. Full filtering will be available soon.
        </p>

        <?php if ($totalCount > 0): ?>
        <!-- Results Summary & Pagination -->
        <div class="level mt-4 pt-4" style="border-top: 1px solid #dbdbdb;">
            <div class="level-left">
                <div class="level-item">
                    <span class="tag is-info is-medium">
                        <?php echo $totalCount; ?> Text<?php echo $totalCount == 1 ? '' : 's'; ?>
                    </span>
                </div>
            </div>
            <div class="level-item">
                <?php PageLayoutHelper::buildPager($pagination['currentPage'], $pagination['pages'], '/texts', 'form1'); ?>
            </div>
            <div class="level-right">
                <div class="level-item">
                    <div class="field has-addons">
                        <div class="control">
                            <span class="button is-static is-small">Sort</span>
                        </div>
                        <div class="control">
                            <div class="select is-small">
                                <select name="sort" data-action="sort">
                                    <?php echo SelectOptionsBuilder::forTextSort($currentSort); ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
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
                <?php echo SelectOptionsBuilder::forMultipleTextsActions(); ?>
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
            <?php echo IconHelper::render('volume-2', ['title' => 'With Audio', 'alt' => 'With Audio']); ?>,
            Src.Link:&nbsp;
            <?php echo IconHelper::render('link', ['title' => 'Source Link available', 'alt' => 'Source Link available']); ?>,
            Ann.Text:&nbsp;
            <?php echo IconHelper::render('check', ['title' => 'Annotated Text available', 'alt' => 'Annotated Text available']); ?>
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
                <input name="marked[]" class="markcheck" type="checkbox" value="<?php echo $txid; ?>" <?php echo FormHelper::checkInRequest($txid, 'marked'); ?> />
            </a>
        </td>
        <td class="td1 center">
            <a href="/text/read?start=<?php echo $txid; ?>">
                <?php echo \Lwt\View\Helper\IconHelper::render('book-open', ['title' => 'Read', 'alt' => 'Read']); ?>
            </a>
            <a href="/test?text=<?php echo $txid; ?>">
                <?php echo \Lwt\View\Helper\IconHelper::render('circle-help', ['title' => 'Test', 'alt' => 'Test']); ?>
            </a>
        </td>
        <td class="td1 center">
            <a href="/text/print-plain?text=<?php echo $txid; ?>">
                <?php echo \Lwt\View\Helper\IconHelper::render('printer', ['title' => 'Print', 'alt' => 'Print']); ?>
            </a>
            <a href="/texts?arch=<?php echo $txid; ?>">
                <?php echo IconHelper::render('archive', ['title' => 'Archive', 'alt' => 'Archive']); ?>
            </a>
            <a href="/texts?chg=<?php echo $txid; ?>">
                <?php echo \Lwt\View\Helper\IconHelper::render('file-pen', ['title' => 'Edit', 'alt' => 'Edit']); ?>
            </a>
            <span class="click" data-action="confirm-delete" data-url="/texts?del=<?php echo $txid; ?>">
                <?php echo IconHelper::render('circle-minus', ['title' => 'Delete', 'alt' => 'Delete']); ?>
            </span>
        </td>
        <?php if ($currentLang == ''): ?>
        <td class="td1 center"><?php echo \htmlspecialchars($record['LgName'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
        <?php endif; ?>
        <td class="td1 center">
            <?php echo \htmlspecialchars($record['TxTitle'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
            <span class="smallgray2"><?php echo \htmlspecialchars($record['taglist'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span> &nbsp;
            <?php if ($audio != ''): ?>
            <?php echo IconHelper::render('volume-2', ['title' => 'With Audio', 'alt' => 'With Audio']); ?>
            <?php endif; ?>
            <?php if (isset($record['TxSourceURI']) && substr(trim($record['TxSourceURI']), 0, 1) != '#'): ?>
            <a href="<?php echo $record['TxSourceURI']; ?>" target="_blank">
                <?php echo IconHelper::render('link', ['title' => 'Link to Text Source', 'alt' => 'Link to Text Source']); ?>
            </a>
            <?php endif; ?>
            <?php if ($record['annotlen']): ?>
            <a href="/text/print?text=<?php echo $txid; ?>">
                <?php echo IconHelper::render('check', ['title' => 'Annotated Text available', 'alt' => 'Annotated Text available']); ?>
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
            <?php PageLayoutHelper::buildPager($pagination['currentPage'], $pagination['pages'], '/texts', 'form2'); ?>
        </th>
    </tr>
</table>
<?php endif; ?>
</form>

<script type="application/json" id="text-list-config"><?php echo json_encode(['showCounts' => intval($showCounts, 2)]); ?></script>
<?php endif; ?>
