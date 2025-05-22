<?php
/**
 * \file
 * \brief Launch an AJAX query to show imported terms
 *
 * Call: inc/ajax_show_imported_terms?last_update=[last_update]&page=[page number]&count=[count]&rt=[rtl]
 *
 * @package Lwt
 * @author  andreask7 <andreask7@users.noreply.github.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-ajax-show-imported-terms.html
 * @since   1.6.0-fork
 */
require_once __DIR__ . '/session_utility.php';

function limit_current_page($currentpage, $recno, $maxperpage)
{
    $pages = intval(($recno-1) / $maxperpage) + 1;
    if ($currentpage < 1) {
        $currentpage = 1;
    }
    if ($currentpage > $pages) {
        $currentpage = $pages;
    }
    return $currentpage;
}

/**
 * Prepare the page to display imported terms.
 *
 * @param int    $recno       Record number
 * @param int    $currentpage Current page
 * @param string $last_update Last update
 * @param int    $maxperpage  Maximum number of terms per page
 *
 * @return void
 */
function imported_terms_header($recno, $currentpage, $last_update, $maxperpage=100): void
{
    $pages = intval(($recno-1) / $maxperpage) + 1;
    $currentpage = limit_current_page($currentpage, $recno, $maxperpage);
    ?>
<table class="tab2"  cellspacing="0" cellpadding="2">
    <tr>
        <th class="th1" colspan="2" nowrap="nowrap">
            <span id="recno"><?php echo $recno; ?></span>
            Term<?php echo ($recno == 1 ?'':'s'); ?>
        </th>
        <th class="th1" colspan="1" nowrap="nowrap">
            &nbsp; &nbsp;
            <?php
            if ($currentpage > 1) {
                ?>
            <img src="icn/control-stop-180.png" title="First Page" alt="First Page"
            onclick="showImportedTerms('<?php echo $last_update; ?>', undefined, $('#recno').text(), '1')" />
            &nbsp;
            <img  src="icn/control-180.png" title="Previous Page" alt="Previous Page"
            onclick="showImportedTerms('<?php echo $last_update; ?>', undefined, $('#recno').text(), <?php echo $currentpage-1; ?>)" />
                <?php
            } else {
                ?>
            <img src="<?php print_file_path('icn/placeholder.png');?>" alt="-" />&nbsp;
            <img src="<?php print_file_path('icn/placeholder.png');?>" alt="-" />
                <?php
            }
            ?> &nbsp;
            Page
            <?php
            if ($pages==1) {
                echo '1';
            } else {
                ?>
            <select name="page"
            onchange="{val=document.form1.page.options[document.form1.page.selectedIndex].value;showImportedTerms('<?php echo $last_update; ?>', undefined, $('#recno').text(), val);}">
                <?php echo get_paging_selectoptions($currentpage, $pages); ?>
            </select>
                <?php
            }
            echo ' of ' . $pages . '&nbsp; ';
            if ($currentpage < $pages) {
                ?>
            <img src="icn/control.png" title="Next Page" alt="Next Page"
            onclick="showImportedTerms('<?php echo $last_update; ?>', undefined, $('#recno').text(), '<?php echo $currentpage+1; ?>')" />
            &nbsp;
            <img src="icn/control-stop.png" title="Last Page" alt="Last Page"
            onclick="showImportedTerms('<?php echo $last_update; ?>', undefined, $('#recno').text(), <?php echo $pages; ?>)" />
                <?php
            } else {
                ?>
            <img src="<?php print_file_path('icn/placeholder.png');?>" alt="-" />
            &nbsp;
            <img src="<?php print_file_path('icn/placeholder.png');?>" alt="-" />
                <?php
            }
            ?>
            &nbsp; &nbsp;
        </th>
    </table>
    <?php
}

/**
 * Prepare the page to display imported terms.
 *
 * @param int    $recno       Record number
 * @param int    $currentpage Current page
 * @param string $last_update Last update
 *
 * @return string SQL-formatted query to limit the number of results
 *
 * @deprecated 2.9.0 Use imported_terms_header instead
 */
function get_imported_terms($recno, $currentpage, $last_update): string
{
    $maxperpage = 100;
    $currentpage = limit_current_page($currentpage, $recno, $maxperpage);
    imported_terms_header($recno, $currentpage, $last_update, $maxperpage);
    $offset = ($currentpage - 1) * $maxperpage;
    return " LIMIT $offset, $maxperpage";
}


/**
 * @return (float|int|null|string)[][]
 *
 * @psalm-return list<list<float|int|null|string>>
 */
function select_imported_terms($last_update, $offset, $max_terms): array
{
    global $tbpref;
    $sql = "SELECT WoID, WoText, WoTranslation, WoRomanization, WoSentence,
    IFNULL(WoSentence, '') LIKE CONCAT('%{', WoText, '}%') AS SentOK,
    WoStatus,
    IFNULL(
        CONCAT(
            '[',
            group_concat(DISTINCT TgText ORDER BY TgText separator ', '),
            ']'
        ), ''
    ) AS taglist
    FROM (
        ({$tbpref}words LEFT JOIN {$tbpref}wordtags ON WoID = WtWoID)
        LEFT JOIN {$tbpref}tags ON TgID = WtTgID
    )
    WHERE WoStatusChanged > " . convert_string_to_sqlsyntax($last_update) . "
    GROUP BY WoID
    LIMIT $offset, $max_terms";
    $res = do_mysqli_query($sql);
    $records = mysqli_fetch_all($res);
    mysqli_free_result($res);
    return $records;
}

/**
 * Show the imported terms.
 *
 * @param string $last_update Last update
 * @param string $limit       SQL-formatted query to limit the number of results
 *
 * @return void
 */
function show_imported_terms($last_update, $limit, $rtl)
{
    ?>
    <table class="sortable tab2" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1 clickable">Term /<br />Romanization</th>
        <th class="th1 clickable">Translation</th>
        <th class="th1 sorttable_nosort">Tags</th>
        <th class="th1 sorttable_nosort">Se.</th>
        <th class="th1 sorttable_numeric clickable">Status</th>
    </tr>
    <?php
    preg_match('/.+(\d+)\s*,\s*(\d+)/', $limit, $matches);
    $rows = select_imported_terms($last_update, $matches[1], $matches[2]);
    foreach ($rows as $_key => $record) {
        echo '<tr>
            <td class="td1">
                <span' . ($rtl ? ' dir="rtl" ' : '') . '>' .
                    tohtml($record['WoText']) . '</span>' .
                    ' / <span id="roman' . $record['WoID'] . '" class="edit_area clickedit">' .
                    ($record['WoRomanization'] != '' ? tohtml(repl_tab_nl($record['WoRomanization'])) : '*') .
                '</span>
            </td>
            <td class="td1">
                <span id="trans' . $record['WoID'] . '" class="edit_area clickedit">' .
                    tohtml(repl_tab_nl($record['WoTranslation'])) .
                '</span>
            </td>
            <td class="td1">
                <span class="smallgray2">' . tohtml($record['taglist']) . '</span>
            </td>
            <td class="td1 center">
                <b>' .
                    (
                        $record['SentOK'] !=0  ?
                        '<img src="icn/status.png" title="' . tohtml($record['WoSentence']) . '" alt="Yes" />' :
                        '<img src="icn/status-busy.png" title="(No valid sentence)" alt="No" />'
                    ) .
                '</b>
            </td>
            <td class="td1 center" title="' . tohtml(get_status_name($record['WoStatus'])) . '">' .
                tohtml(get_status_abbr($record['WoStatus'])) .
            '</td>
        </tr>';
    }
    ?>
    </table>
    <script type="text/javascript">
        $(document).ready(function() {
            $('.edit_area').editable(
                'inline_edit.php',
                {
                    type      : 'textarea',
                    indicator : '<img src="icn/indicator.gif">',
                    tooltip   : 'Click to edit...',
                    submit    : 'Save',
                    cancel    : 'Cancel',
                    rows      : 3,
                    cols      : 35
                }
            );
        });
    </script>
    <?php
}

/**
 * Show the imported terms.
 *
 * @param string $last_update Last update
 * @param int    $currentpage Current number of the page
 * @param int    $recno       Number of record
 * @param bool   $rtl         True if this language is right-to-left
 *
 * @return void
 *
 * @deprecated 2.9.0 Use the AJAX API instead.
 */
function do_ajax_show_imported_terms($last_update, $currentpage, $recno, $rtl)
{
    chdir('..');
    if ($recno > 0) {
        $maxperpage = 100;
        $currentpage = limit_current_page($currentpage, $recno, $maxperpage);
        imported_terms_header($recno, $currentpage, $last_update, $maxperpage);
        $offset = ($currentpage - 1) * $maxperpage;
        $limit = " LIMIT $offset, $maxperpage";
        show_imported_terms($last_update, $limit, $rtl);
    } else if ($recno==0) {
        echo '<p>No terms imported.</p>';
    }
}

/**
 * Return the list of imported terms of pages information.
 *
 * @param string $last_update Terms import time
 * @param int    $currentpage Current page number
 * @param int    $recno       Number of imported terms
 *
 * @return ((int|mixed)[]|mixed)[]
 *
 * @psalm-return array{navigation: array{current_page: mixed, total_pages: int}, terms: mixed}
 */
function imported_terms_list($last_update, $currentpage, $recno): array
{
    $maxperpage = 100;
    $currentpage = limit_current_page($currentpage, $recno, $maxperpage);
    $offset = ($currentpage - 1) * $maxperpage;

    $pages = intval(($recno-1) / $maxperpage) + 1;
    $output = array(
        "navigation" => array(
            "current_page" => $currentpage,
            "total_pages" => $pages
        ),
        "terms" => select_imported_terms($last_update, $offset, $maxperpage)
    );
    return $output;
}

if (isset($_REQUEST['last_update']) && isset($_REQUEST['page'])
    && isset($_REQUEST['count']) && isset($_REQUEST['rtl'])
) {
    do_ajax_show_imported_terms(
        $_REQUEST['last_update'],
        (int)$_REQUEST['page'],
        (int)$_REQUEST['count'],
        (bool)$_REQUEST['rtl']
    );
}

?>
