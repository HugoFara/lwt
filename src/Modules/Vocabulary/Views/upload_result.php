<?php declare(strict_types=1);
/**
 * Word Upload Result View
 *
 * Displays the results of a word import operation with pagination.
 *
 * Expected variables:
 * - $lastUpdate: Timestamp of last word update (for filtering)
 * - $rtl: Whether the language is right-to-left
 * - $recno: Number of records imported
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views\Word
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Views\Word;

use Lwt\View\Helper\IconHelper;
use Lwt\View\Helper\PageLayoutHelper;

// Action buttons for navigation
$actions = [
    ['url' => '/word/upload', 'label' => 'Import More Terms', 'icon' => 'file-up', 'class' => 'is-primary'],
    ['url' => '/words', 'label' => 'My Terms', 'icon' => 'list'],
    ['url' => '/', 'label' => 'Home', 'icon' => 'home']
];
echo PageLayoutHelper::buildActionCard($actions);
?>

<!-- Import Result Feedback -->
<?php if ($recno > 0): ?>
<article class="message is-success mb-4">
    <div class="message-body">
        <span class="icon-text">
            <span class="icon">
                <?php echo IconHelper::render('check', ['alt' => 'Success']); ?>
            </span>
            <span>
                <strong>Import successful!</strong>
                <span id="recno"><?php echo $recno; ?></span>
                term<?php echo ($recno == 1 ? '' : 's'); ?> imported.
            </span>
        </span>
    </div>
</article>
<?php else: ?>
<article class="message is-warning mb-4">
    <div class="message-body">
        <span class="icon-text">
            <span class="icon">
                <?php echo IconHelper::render('alert-triangle', ['alt' => 'Warning']); ?>
            </span>
            <span>
                <strong>No terms were imported.</strong>
                This could mean all terms already exist or the input was empty.
            </span>
        </span>
    </div>
</article>
<?php endif; ?>

<form name="form1" action="#"
      class="box"
      data-action="upload-result-form"
      data-last-update="<?php echo htmlspecialchars($lastUpdate); ?>"
      data-rtl="<?php echo $rtl ? 'true' : 'false'; ?>"
      data-recno="<?php echo $recno; ?>">

    <div id="res_data">
        <!-- Pagination Navigation -->
        <nav id="res_data-navigation" class="level mb-4" style="<?php echo $recno == 0 ? 'display: none;' : ''; ?>">
            <div class="level-left">
                <div class="level-item">
                    <span class="tag is-medium is-info is-light">
                        <span id="recno-display"><?php echo $recno; ?></span>&nbsp;Term<?php echo ($recno == 1 ? '' : 's'); ?>
                    </span>
                </div>
            </div>
            <div class="level-right">
                <div class="level-item">
                    <nav class="pagination is-small" role="navigation" aria-label="pagination">
                        <span id="res_data-navigation-prev" class="pagination-previous" style="display: none;">
                            <span id="res_data-navigation-prev-first" class="icon is-clickable" title="First Page">
                                <?php echo IconHelper::render('chevrons-left', ['alt' => 'First Page']); ?>
                            </span>
                            <span id="res_data-navigation-prev-minus" class="icon is-clickable" title="Previous Page">
                                <?php echo IconHelper::render('chevron-left', ['alt' => 'Previous Page']); ?>
                            </span>
                        </span>
                        <span class="pagination-list">
                            <span class="mr-2">Page</span>
                            <span id="res_data-navigation-no_quick_nav">1</span>
                            <select id="res_data-navigation-quick_nav" name="page" class="select is-small"></select>
                            <span class="ml-1 mr-1">of</span>
                            <span id="res_data-navigation-totalPages"></span>
                        </span>
                        <span id="res_data-navigation-next" class="pagination-next" style="display: none;">
                            <span id="res_data-navigation-next-plus" class="icon is-clickable" title="Next Page">
                                <?php echo IconHelper::render('chevron-right', ['alt' => 'Next Page']); ?>
                            </span>
                            <span id="res_data-navigation-next-last" class="icon is-clickable" title="Last Page">
                                <?php echo IconHelper::render('chevrons-right', ['alt' => 'Last Page']); ?>
                            </span>
                        </span>
                    </nav>
                </div>
            </div>
        </nav>

        <!-- Results Table -->
        <div class="table-container">
            <table id="res_data-res_table" class="table is-striped is-hoverable is-fullwidth sortable">
                <thead id="res_data-res_table-header">
                    <tr>
                        <th class="is-clickable">Term / Romanization</th>
                        <th class="is-clickable">Translation</th>
                        <th>Tags</th>
                        <th class="has-text-centered" title="Sentence">Se.</th>
                        <th class="has-text-centered is-clickable">Status</th>
                    </tr>
                </thead>
                <tbody id="res_data-res_table-body">
                </tbody>
            </table>
        </div>

        <p id="res_data-no_terms_imported" class="has-text-centered has-text-grey py-4" style="display: none;">
            No terms imported.
        </p>
    </div>
</form>

<script type="application/json" data-lwt-upload-result-config>
<?php echo json_encode([
    'lastUpdate' => $lastUpdate,
    'rtl' => $rtl,
    'recno' => $recno
]); ?>
</script>
