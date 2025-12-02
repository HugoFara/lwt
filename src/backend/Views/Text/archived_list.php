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
use Lwt\View\Helper\FormHelper;
use Lwt\View\Helper\SelectOptionsBuilder;

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

echo PageLayoutHelper::buildActionCard([
    ['url' => '/texts?new=1', 'label' => 'New Text', 'icon' => 'circle-plus', 'class' => 'is-primary'],
    ['url' => '/text/import-long', 'label' => 'Long Text Import', 'icon' => 'file-up'],
    ['url' => '/feeds?page=1&check_autoupdate=1', 'label' => 'Newsfeed Import', 'icon' => 'rss'],
    ['url' => '/texts?query=&page=1', 'label' => 'Active Texts', 'icon' => 'book-open'],
]);
?>

<form name="form1" action="#" data-base-url="/text/archived" data-search-placeholder="archived-texts">
    <div class="box mb-4">
        <div class="field has-addons">
            <div class="control is-expanded has-icons-left">
                <input type="text"
                       name="query"
                       class="input"
                       value="<?php echo htmlspecialchars($currentQuery ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="Search archived texts... (e.g., lang:Spanish tag:news title)"
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
                <?php echo PageLayoutHelper::buildPager($pagination['currentPage'], $pagination['pages'], '/text/archived', 'form1'); ?>
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
<p>No archived texts found.</p>
<?php else: ?>
<form name="form2" action="/text/archived" method="post" x-data="textListManager()">
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
                                    <?php echo SelectOptionsBuilder::forMultipleArchivedTextsActions(); ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Archived Text Cards Grid -->
<div class="columns is-multiline text-cards archived-text-cards">
    <?php foreach ($texts as $record):
        $atid = $record['AtID'];
        $audio = isset($record['AtAudioURI']) ? trim($record['AtAudioURI']) : '';
        $sourceUri = isset($record['AtSourceURI']) ? trim($record['AtSourceURI']) : '';
        $hasSource = $sourceUri !== '';
    ?>
    <div class="column is-4-desktop is-6-tablet is-12-mobile">
        <div class="card text-card is-archived" x-data="{ showDetails: false }">
            <a name="rec<?php echo $atid; ?>"></a>
            <header class="card-header">
                <label class="card-header-icon checkbox-wrapper">
                    <input name="marked[]"
                           class="markcheck"
                           type="checkbox"
                           value="<?php echo $atid; ?>"
                           <?php echo FormHelper::checkInRequest($atid, 'marked'); ?>
                           @change="updateMarked($event)" />
                </label>
                <p class="card-header-title">
                    <?php echo htmlspecialchars($record['AtTitle'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                </p>
                <div class="card-header-icon card-icons">
                    <?php if ($audio !== ''): ?>
                    <span title="With Audio">
                        <?php echo IconHelper::render('volume-2', ['size' => 16]); ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($hasSource): ?>
                    <a href="<?php echo htmlspecialchars($sourceUri, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" title="Source Link">
                        <?php echo IconHelper::render('external-link', ['size' => 16]); ?>
                    </a>
                    <?php endif; ?>
                    <?php if ($record['annotlen']): ?>
                    <span title="Annotated Text">
                        <?php echo IconHelper::render('file-text', ['size' => 16]); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </header>

            <div class="card-content">
                <!-- Language & Tags -->
                <div class="text-meta mb-3">
                    <?php if ($currentLang == '' && isset($record['LgName'])): ?>
                    <span class="tag is-link is-light"><?php echo htmlspecialchars($record['LgName'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($record['taglist'])): ?>
                    <span class="tags-list"><?php echo htmlspecialchars($record['taglist'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Archive Status Badge -->
                <div class="archive-badge">
                    <span class="tag is-warning is-light">
                        <?php echo IconHelper::render('archive', ['size' => 12]); ?>
                        <span class="ml-1">Archived</span>
                    </span>
                </div>
            </div>

            <footer class="card-footer">
                <a href="/text/archived?unarch=<?php echo $atid; ?>" class="card-footer-item is-primary-action">
                    <?php echo IconHelper::render('archive-restore', ['size' => 16]); ?>
                    <span>Unarchive</span>
                </a>
                <a href="/text/archived?chg=<?php echo $atid; ?>" class="card-footer-item">
                    <?php echo IconHelper::render('file-pen', ['size' => 16]); ?>
                    <span>Edit</span>
                </a>
                <a class="card-footer-item has-text-danger" data-action="confirm-delete" data-url="/text/archived?del=<?php echo $atid; ?>">
                    <?php echo IconHelper::render('trash-2', ['size' => 16]); ?>
                    <span>Delete</span>
                </a>
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
                <?php echo PageLayoutHelper::buildPager($pagination['currentPage'], $pagination['pages'], '/text/archived', 'form2'); ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
</form>
<?php endif; ?>
