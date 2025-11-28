<?php
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
?>
<script type="text/javascript">
    /**
     * Navigation header for the imported terms.
     *
     * @param {JSON} data Data object for navigation.
     * @param {string} last_update Terms import timestamp for SQL
     * @param {bool} rtl If text is right-to-left
     */
function formatImportedTermsNavigation(data, last_update, rtl) {
    const currentPage = parseInt(data["current_page"], 10);
    const totalPages = parseInt(data["total_pages"], 10);
    const importedTerms = parseInt($('#recno').text(), 10);
    const showOtherPage = function (page_number) {
        return function () {
            showImportedTerms(last_update, rtl, importedTerms, page_number);
        };
    }

    if (currentPage > 1) {
        $('#res_data-navigation-prev').css("display", "initial");
    } else {
        $('#res_data-navigation-prev').css("display", "none");
    }
    $('#res_data-navigation-prev-first')
    .on("click", showOtherPage(1));
    $('#res_data-navigation-prev-minus')
    .on("click", showOtherPage(currentPage - 1));
    if (totalPages == 1) {
        $('#res_data-navigation-quick_nav').css("display", "none");
        $('#res_data-navigation-no_quick_nav').css("display", "initial");
    } else {
        $('#res_data-navigation-quick_nav').css("display", "initial");
        $('#res_data-navigation-no_quick_nav').css("display", "none");
    }
    let options = "";
    for (let i = 1; i < totalPages + 1; i++) {
            options = '<option value="' + i + '"' +
            (i == currentPage ? ' selected="selected"' : '') + '>' + i +
            '</option>';
    }
    $("#res_data-navigation-quick_nav").html(options);
    $("#res_data-navigation-quick_nav").on(
        "change", showOtherPage(
            document.form1.page.options[document.form1.page.selectedIndex].value
        )
    );
    $('#res_data-navigation-totalPages').text(totalPages);
    if (currentPage < totalPages) {
        $('#res_data-navigation-next').css("display", "initial");
    } else {
        $('#res_data-navigation-next').css("display", "none");
    }
    $('#res_data-navigation-next-plus')
    .on("click", showOtherPage(currentPage + 1));
    $('#res_data-navigation-next-last')
    .on("click", showOtherPage(totalPages));
}

/**
 * Create the rows with the imported terms.
 *
 * @param {JSON} data Data object containing terms.
 * @param {bool} rtl If text is right-to-left.
 *
 * @returns {string} HTML-formated rows to display
 */
function formatImportedTerms(data, rtl) {
    let output = "", row, record;
    for (let i = 0; i < data.length; i++) {
        record = data[i];
        row = `<tr>
            <td class="td1">
                <span` + (rtl ? ` dir="rtl" ` : ``) + `>` +
                escape_html_chars(record[`WoText`]) + `</span>` +
                    ` / <span id="roman` + record[`WoText`] + `" class="edit_area clickedit">` +
                    (record[`WoText`] != `` ? escape_html_chars(record[`WoText`]) : `*`) +
                `</span>
            </td>
            <td class="td1">
                <span id="trans` + record[`WoID`] + `" class="edit_area clickedit">` +
                escape_html_chars(record[`WoTranslation`]) +
                `</span>
            </td>
            <td class="td1">
                <span class="smallgray2">` + escape_html_chars(record[`taglist`]) + `</span>
            </td>
            <td class="td1 center">
                <b>` +
                    (
                        record[`SentOK`] !=0  ?
                        `<img src="/assets/icons/status.png" title="` + escape_html_chars(record[`WoSentence`]) + `" alt="Yes" />` :
                        `<img src="/assets/icons/status-busy.png" title="(No valid sentence)" alt="No" />`
                    ) +
                `</b>
            </td>
            <td class="td1 center" title="` + escape_html_chars(STATUSES[record[`WoStatus`]].name) + `">` +
                escape_html_chars(STATUSES[record[`WoStatus`]].abbr) +
            `</td>
        </tr>`;
        output += row;
    }
    return output;
}

/**
 * Display page content based on raw server answer.
 *
 * @param {JSON} data Data object for navigation.
 * @param {string} last_update Terms import timestamp for SQL
 * @param {bool} rtl If text is right-to-left
 */
function imported_terms_handle_answer(data, last_update, rtl)
{
    formatImportedTermsNavigation(data["navigation"], last_update, rtl);
    const html_content = formatImportedTerms(data["terms"], rtl);
    $('#res_data-res_table-body').empty();
    $('#res_data-res_table-body').append($(html_content));
}

/**
 * Show the terms imported.
 *
 * @param {string} last_update Last update date in SQL compatible format
 * @param {bool} rtl If text is right-to-left
 * @param {int} count Number of terms imported
 * @param {int} page Current page number
 */
function showImportedTerms(last_update, rtl, count, page) {
    if (parseInt(count, 10) === 0) {
        $('#res_data-no_terms_imported').css("display", "inherit");
        $('#res_data-navigation').css("display", "none");
        $('#res_data-res_table').css("display", "none");
    } else {
        $('#res_data-no_terms_imported').css("display", "none");
        $('#res_data-navigation').css("display", "");
        $('#res_data-res_table').css("display", "");
        $.getJSON(
            "api.php/v1/terms/imported",
            {
                last_update: last_update,
                count: count,
                page: page
            },
            function (data) {
                imported_terms_handle_answer(data, last_update, rtl)
            }
        )
    }
}
</script>
<form name="form1" action="#"
onsubmit="showImportedTerms('<?php echo $lastUpdate; ?>', <?php echo $rtl; ?>, <?php echo $recno; ?>, document.form1.page.options[document.form1.page.selectedIndex].value); return false;">
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
                    <img id="res_data-navigation-prev-first"
                    src="/assets/icons/control-stop-180.png" title="First Page"
                    alt="First Page" />
                    &nbsp;
                    <img id="res_data-navigation-prev-minus"
                    src="/assets/icons/control-180.png" title="Previous Page"
                    alt="Previous Page" />
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
                    <img id="res_data-navigation-next-plus"
                    src="/assets/icons/control.png" title="Next Page" alt="Next Page" />
                    &nbsp;
                    <img id="res_data-navigation-next-last"
                    src="/assets/icons/control-stop.png" title="Last Page" alt="Last Page" />
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
<script type="text/javascript">
    showImportedTerms(
        '<?php echo $lastUpdate; ?>', '<?php echo $rtl; ?>',
        <?php echo $recno; ?>, '1'
    );
</script>
