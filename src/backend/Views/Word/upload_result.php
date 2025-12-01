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

?>
<form name="form1" action="#"
data-action="upload-result-form"
data-last-update="<?php echo htmlspecialchars($lastUpdate); ?>"
data-rtl="<?php echo $rtl ? 'true' : 'false'; ?>"
data-recno="<?php echo $recno; ?>">
<div id="res_data">
    <table id="res_data-navigation" class="tab2" cellspacing="0" cellpadding="2">
    <tr>
        <th class="th1" colspan="2" nowrap="nowrap">
            <span id="recno"><?php echo $recno; ?></span>
            Term<?php echo ($recno == 1 ? '' : 's'); ?>
        </th>
        <th class="th1 flex-spaced" colspan="1" nowrap="nowrap">
            <span>
                <span id="res_data-navigation-prev">
                    <?php echo IconHelper::render('chevrons-left', ['id' => 'res_data-navigation-prev-first', 'title' => 'First Page', 'alt' => 'First Page']); ?>
                    &nbsp;
                    <?php echo IconHelper::render('chevron-left', ['id' => 'res_data-navigation-prev-minus', 'title' => 'Previous Page', 'alt' => 'Previous Page']); ?>
                </span>
            </span>
            <span>
                Page
                <span id="res_data-navigation-no_quick_nav">1</span>
                <select id="res_data-navigation-quick_nav" name="page"></select>
                of <span id="res_data-navigation-totalPages"></span>
            </span>
            <span>
                <span id="res_data-navigation-next">
                    <?php echo IconHelper::render('chevron-right', ['id' => 'res_data-navigation-next-plus', 'title' => 'Next Page', 'alt' => 'Next Page']); ?>
                    &nbsp;
                    <?php echo IconHelper::render('chevrons-right', ['id' => 'res_data-navigation-next-last', 'title' => 'Last Page', 'alt' => 'Last Page']); ?>
                </span>
            </span>
        </th>
    </table>
    <table id="res_data-res_table" class="sortable tab2" cellspacing="0" cellpadding="5">
        <thead id="res_data-res_table-header">
            <tr>
                <th class="th1 clickable">Term /<br />Romanization</th>
                <th class="th1 clickable">Translation</th>
                <th class="th1 sorttable_nosort">Tags</th>
                <th class="th1 sorttable_nosort">Se.</th>
                <th class="th1 sorttable_numeric clickable">Status</th>
            </tr>
        </thead>
        <tbody id="res_data-res_table-body">
        </tbody>
    </table>
    <p id="res_data-no_terms_imported" style="display: none;">
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
