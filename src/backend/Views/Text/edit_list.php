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
<form name="form2" action="/texts" method="post" x-data="textListManager()">
<input type="hidden" name="data" value="" />

<!-- Multi Actions Card -->
<div class="card mb-4">
    <div class="card-content py-3">
        <div class="level is-mobile">
            <div class="level-left">
                <div class="level-item">
                    <div class="buttons are-small">
                        <button type="button" class="button" @click="markAll(true)">
                            <?php echo IconHelper::render('check-square', ['size' => 14]); ?>
                            <span class="ml-1">Mark All</span>
                        </button>
                        <button type="button" class="button" @click="markAll(false)">
                            <?php echo IconHelper::render('square', ['size' => 14]); ?>
                            <span class="ml-1">Mark None</span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="level-right">
                <div class="level-item">
                    <div class="field has-addons">
                        <div class="control">
                            <span class="button is-static is-small">
                                <?php echo IconHelper::render('zap', ['size' => 14]); ?>
                                <span class="ml-1">Actions</span>
                            </span>
                        </div>
                        <div class="control">
                            <div class="select is-small">
                                <select name="markaction" id="markaction" :disabled="!hasMarked" data-action="multi-action">
                                    <?php echo SelectOptionsBuilder::forMultipleTextsActions(); ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Text Cards Grid -->
<div class="columns is-multiline text-cards">
    <?php foreach ($texts as $record):
        $txid = $record['TxID'];
        $audio = isset($record['TxAudioURI']) ? trim($record['TxAudioURI']) : '';
        $sourceUri = isset($record['TxSourceURI']) ? trim($record['TxSourceURI']) : '';
        $hasSource = $sourceUri !== '' && substr($sourceUri, 0, 1) !== '#';
    ?>
    <div class="column is-4-desktop is-6-tablet is-12-mobile">
        <div class="card text-card" x-data="{ showDetails: false }">
            <a name="rec<?php echo $txid; ?>"></a>
            <header class="card-header">
                <label class="card-header-icon checkbox-wrapper">
                    <input name="marked[]"
                           class="markcheck"
                           type="checkbox"
                           value="<?php echo $txid; ?>"
                           <?php echo FormHelper::checkInRequest($txid, 'marked'); ?>
                           @change="updateMarked($event)" />
                </label>
                <p class="card-header-title">
                    <?php echo \htmlspecialchars($record['TxTitle'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                </p>
                <div class="card-header-icon card-icons">
                    <?php if ($audio !== ''): ?>
                    <span title="With Audio">
                        <?php echo IconHelper::render('volume-2', ['size' => 16]); ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($hasSource): ?>
                    <a href="<?php echo \htmlspecialchars($sourceUri, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" title="Source Link">
                        <?php echo IconHelper::render('external-link', ['size' => 16]); ?>
                    </a>
                    <?php endif; ?>
                    <?php if ($record['annotlen']): ?>
                    <a href="/text/print?text=<?php echo $txid; ?>" title="Annotated Text">
                        <?php echo IconHelper::render('file-text', ['size' => 16]); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </header>

            <div class="card-content">
                <!-- Language & Tags -->
                <div class="text-meta mb-3">
                    <?php if ($currentLang == '' && isset($record['LgName'])): ?>
                    <span class="tag is-link is-light"><?php echo \htmlspecialchars($record['LgName'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($record['taglist'])): ?>
                    <span class="tags-list"><?php echo \htmlspecialchars($record['taglist'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Word Statistics -->
                <div class="text-stats">
                    <div class="stat-row">
                        <div class="stat-item">
                            <span class="stat-label">Total</span>
                            <span class="stat-value" id="total_<?php echo $txid; ?>">-</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Saved</span>
                            <span class="stat-value">
                                <a class="status4" id="saved_<?php echo $txid; ?>"
                                   href="/words/edit?page=1&amp;query=&amp;status=&amp;tag12=0&amp;tag2=&amp;tag1=&amp;text_mode=0&amp;text=<?php echo $txid; ?>"
                                   data_id="<?php echo $txid; ?>">-</a>
                            </span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Unknown</span>
                            <span class="stat-value status0" id="todo_<?php echo $txid; ?>">-</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Unkn.%</span>
                            <span class="stat-value" id="unknownpercent_<?php echo $txid; ?>">-</span>
                        </div>
                    </div>

                    <!-- Status Chart (Chart.js) -->
                    <div class="status-chart-wrapper">
                        <canvas id="chart_<?php echo $txid; ?>"
                                class="text-status-chart"
                                data-text-id="<?php echo $txid; ?>"
                                height="30"></canvas>
                        <!-- Hidden spans for data storage (used by existing JS) -->
                        <div class="chart-data-store" style="display: none;">
                            <?php
                            $statusOrder = array(0,1,2,3,4,5,99,98);
                            foreach ($statusOrder as $statusNum): ?>
                            <span id="stat_<?php echo $statusNum; ?>_<?php echo $txid; ?>"
                                  data-status="<?php echo $statusNum; ?>"
                                  data-label="<?php echo $statuses[$statusNum]["name"]; ?>">0</span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="card-footer">
                <a href="/text/read?start=<?php echo $txid; ?>" class="card-footer-item is-primary-action">
                    <?php echo IconHelper::render('book-open', ['size' => 16]); ?>
                    <span>Read</span>
                </a>
                <a href="/test?text=<?php echo $txid; ?>" class="card-footer-item">
                    <?php echo IconHelper::render('circle-help', ['size' => 16]); ?>
                    <span>Test</span>
                </a>
                <div class="card-footer-item has-dropdown" x-data="{ open: false }">
                    <a @click.prevent="open = !open" class="dropdown-trigger-link">
                        <?php echo IconHelper::render('more-horizontal', ['size' => 16]); ?>
                        <span>More</span>
                    </a>
                    <div class="dropdown-menu card-dropdown" x-show="open" @click.outside="open = false" x-cloak>
                        <div class="dropdown-content">
                            <a href="/text/print-plain?text=<?php echo $txid; ?>" class="dropdown-item">
                                <?php echo IconHelper::render('printer', ['size' => 14]); ?>
                                <span>Print</span>
                            </a>
                            <a href="/texts?arch=<?php echo $txid; ?>" class="dropdown-item">
                                <?php echo IconHelper::render('archive', ['size' => 14]); ?>
                                <span>Archive</span>
                            </a>
                            <a href="/texts?chg=<?php echo $txid; ?>" class="dropdown-item">
                                <?php echo IconHelper::render('file-pen', ['size' => 14]); ?>
                                <span>Edit</span>
                            </a>
                            <hr class="dropdown-divider">
                            <a class="dropdown-item has-text-danger" data-action="confirm-delete" data-url="/texts?del=<?php echo $txid; ?>">
                                <?php echo IconHelper::render('trash-2', ['size' => 14]); ?>
                                <span>Delete</span>
                            </a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($pagination['pages'] > 1): ?>
<!-- Bottom Pagination -->
<div class="box mt-4">
    <div class="level">
        <div class="level-left">
            <div class="level-item">
                <span class="tag is-info is-medium">
                    <?php echo $totalCount; ?> Text<?php echo $totalCount == 1 ? '' : 's'; ?>
                </span>
            </div>
        </div>
        <div class="level-right">
            <div class="level-item">
                <?php PageLayoutHelper::buildPager($pagination['currentPage'], $pagination['pages'], '/texts', 'form2'); ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
</form>

<script type="application/json" id="text-list-config"><?php echo json_encode(['showCounts' => intval($showCounts, 2)]); ?></script>
<?php endif; ?>
