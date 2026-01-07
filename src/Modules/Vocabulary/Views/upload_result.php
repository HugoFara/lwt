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

use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

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

<div class="box"
     x-data="wordUploadResultApp({
         lastUpdate: '<?php echo htmlspecialchars($lastUpdate); ?>',
         rtl: <?php echo $rtl ? 'true' : 'false'; ?>,
         recno: <?php echo $recno; ?>
     })">

    <!-- No terms message -->
    <template x-if="!hasTerms && !isLoading">
        <p class="has-text-centered has-text-grey py-4">
            No terms imported.
        </p>
    </template>

    <!-- Loading indicator -->
    <template x-if="isLoading">
        <div class="has-text-centered py-4">
            <span class="icon">
                <?php echo IconHelper::render('loader-2', ['alt' => 'Loading', 'class' => 'animate-spin']); ?>
            </span>
            <span>Loading...</span>
        </div>
    </template>

    <!-- Results content -->
    <template x-if="hasTerms && !isLoading">
        <div>
            <!-- Pagination Navigation -->
            <nav class="level mb-4">
                <div class="level-left">
                    <div class="level-item">
                        <span class="tag is-medium is-info is-light">
                            <span x-text="recno"></span>&nbsp;Term<span x-show="recno !== 1">s</span>
                        </span>
                    </div>
                </div>
                <div class="level-right">
                    <div class="level-item">
                        <nav class="pagination is-small" role="navigation" aria-label="pagination">
                            <span class="pagination-previous" x-show="currentPage > 1">
                                <span class="icon is-clickable" title="First Page" @click="goFirst()">
                                    <?php echo IconHelper::render('chevrons-left', ['alt' => 'First Page']); ?>
                                </span>
                                <span class="icon is-clickable" title="Previous Page" @click="goPrev()">
                                    <?php echo IconHelper::render('chevron-left', ['alt' => 'Previous Page']); ?>
                                </span>
                            </span>
                            <span class="pagination-list">
                                <span class="mr-2">Page</span>
                                <template x-if="totalPages <= 1">
                                    <span>1</span>
                                </template>
                                <template x-if="totalPages > 1">
                                    <select class="select is-small" x-model="currentPage" @change="goToPage(parseInt($event.target.value))">
                                        <template x-for="p in totalPages" :key="p">
                                            <option :value="p" x-text="p" :selected="p === currentPage"></option>
                                        </template>
                                    </select>
                                </template>
                                <span class="ml-1 mr-1">of</span>
                                <span x-text="totalPages"></span>
                            </span>
                            <span class="pagination-next" x-show="currentPage < totalPages">
                                <span class="icon is-clickable" title="Next Page" @click="goNext()">
                                    <?php echo IconHelper::render('chevron-right', ['alt' => 'Next Page']); ?>
                                </span>
                                <span class="icon is-clickable" title="Last Page" @click="goLast()">
                                    <?php echo IconHelper::render('chevrons-right', ['alt' => 'Last Page']); ?>
                                </span>
                            </span>
                        </nav>
                    </div>
                </div>
            </nav>

            <!-- Results Table -->
            <div class="table-container">
                <table class="table is-striped is-hoverable is-fullwidth sortable">
                    <thead>
                        <tr>
                            <th class="is-clickable">Term / Romanization</th>
                            <th class="is-clickable">Translation</th>
                            <th>Tags</th>
                            <th class="has-text-centered" title="Sentence">Se.</th>
                            <th class="has-text-centered is-clickable">Status</th>
                        </tr>
                    </thead>
                    <tbody x-html="terms.map(term => formatTermRow(term)).join('')">
                    </tbody>
                </table>
            </div>
        </div>
    </template>
</div>
