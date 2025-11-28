<?php

/**
 * \file
 * \brief Show test frame with vocab table - Legacy wrapper
 *
 * Call: do_test_table.php?lang=[langid]
 * Call: do_test_test.php?text=[textid]
 * Call: do_test_test.php?&selection=1 (SQL via $_SESSION['testsql'])
 *
 * This file provides backward-compatible functions while delegating
 * main functionality to TestService and TestViews.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/do-test-table.html
 * @since   1.5.4
 *
 * @deprecated 3.0.0 Use TestController and TestService instead
 */

require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../Core/UI/ui_helpers.php';
require_once __DIR__ . '/../Core/Tag/tags.php';
require_once __DIR__ . '/../Core/Test/test_helpers.php';
require_once __DIR__ . '/../Core/Http/param_helpers.php';
require_once __DIR__ . '/../Core/Language/language_utilities.php';
require_once __DIR__ . '/../Core/Word/word_status.php';
require_once __DIR__ . '/../Core/Word/dictionary_links.php';
require_once __DIR__ . '/../Services/TestService.php';
require_once __DIR__ . '/../Views/TestViews.php';

use Lwt\Database\Connection;
use Lwt\Database\Settings;
use Lwt\Services\TestService;
use Lwt\Views\TestViews;

/**
 * Set sql request for the word test.
 *
 * @return string SQL request string
 *
 * @deprecated 3.0.0 Use TestService::getTestIdentifier instead
 */
function get_test_table_sql()
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    if (isset($_REQUEST['selection']) && isset($_SESSION['testsql'])) {
        $testsql = $_SESSION['testsql'];
        $cntlang = Connection::fetchValue('SELECT count(distinct WoLgID) AS value FROM ' . $testsql);
        if ($cntlang > 1) {
            echo '<p>Sorry - The selected terms are in ' . $cntlang .
            ' languages, but tests are only possible in one language at a time.</p>';
            exit();
        }
    } elseif (isset($_REQUEST['lang'])) {
        $testsql = ' ' . $tbpref . 'words where WoLgID = ' . $_REQUEST['lang'] . ' ';
    } elseif (isset($_REQUEST['text'])) {
        $testsql = ' ' . $tbpref . 'words, ' . $tbpref . 'textitems2
        WHERE Ti2LgID = WoLgID AND Ti2WoID = WoID AND Ti2TxID = ' . $_REQUEST['text'] . ' ';
    } else {
        my_die("do_test_table.php called with wrong parameters");
    }
    return $testsql;
}


/**
 * @return (float|int|null|string)[]|false|null
 *
 * @deprecated 3.0.0 Use TestService::getLanguageSettings instead
 */
function do_test_table_language_settings(string $testsql)
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();

    $lang = Connection::fetchValue('SELECT WoLgID AS value FROM ' . $testsql . ' LIMIT 1');

    if (!isset($lang)) {
        echo '<p class="center">&nbsp;<br />
        Sorry - No terms to display or to test at this time.</p>';
        pageend();
        exit();
    }

    $sql = 'SELECT LgTextSize, LgRegexpWordCharacters, LgRightToLeft
    FROM ' . $tbpref . 'languages WHERE LgID = ' . $lang;
    $res = Connection::query($sql);
    $record = mysqli_fetch_assoc($res);
    mysqli_free_result($res);
    return $record;
}

/**
 * @return int[]
 *
 * @deprecated 3.0.0 Use TestService::getTableTestSettings instead
 */
function get_test_table_settings(): array
{
    $service = new TestService();
    $settings = $service->getTableTestSettings();
    return [
        $settings['edit'],
        $settings['status'],
        $settings['term'],
        $settings['trans'],
        $settings['rom'],
        $settings['sentence']
    ];
}

/**
 * @deprecated 3.0.0 Use TestViews::renderTableTestJs instead
 */
function do_test_table_javascript(): void
{
    $views = new TestViews();
    $views->renderTableTestJs();
}


/**
 * @deprecated 3.0.0 Use TestViews::renderTableTestSettings instead
 */
function do_test_table_settings(array $settings): void
{
    $views = new TestViews();
    $views->renderTableTestSettings([
        'edit' => $settings[0],
        'status' => $settings[1],
        'term' => $settings[2],
        'trans' => $settings[3],
        'rom' => $settings[4],
        'sentence' => $settings[5]
    ]);
}


/**
 * @deprecated 3.0.0 Use TestViews::renderTableTestHeader instead
 */
function do_test_table_header(): void
{
    $views = new TestViews();
    $views->renderTableTestHeader();
}

/**
 * @deprecated 3.0.0 Use TestService::getTableTestWords and TestViews::renderTableTestRow instead
 */
function do_test_table_table_content(array $lang_record, string $testsql): void
{
    $service = new TestService();
    $views = new TestViews();

    $textsize = round(((int)$lang_record['LgTextSize'] - 100) / 2, 0) + 100;
    $regexword = $lang_record['LgRegexpWordCharacters'];
    $rtlScript = (bool)$lang_record['LgRightToLeft'];

    $words = $service->getTableTestWords($testsql);
    while ($record = mysqli_fetch_assoc($words)) {
        $views->renderTableTestRow($record, $regexword, (int)$textsize, $rtlScript);
    }
    mysqli_free_result($words);
}

/**
 * @deprecated 3.0.0 Use TestViews::renderTableTestRow instead
 */
function do_test_table_row(array $record, string $regexword, int $textsize, string $span1, string $span2): void
{
    $views = new TestViews();
    $rtl = ($span1 !== '');
    $views->renderTableTestRow($record, $regexword, $textsize, $rtl);
}

/**
 * @deprecated 3.0.0 Use TestController::table instead
 */
function do_test_table(): void
{
    $service = new TestService();
    $views = new TestViews();

    $testsql = get_test_table_sql();
    $lang_record = do_test_table_language_settings($testsql);
    $settings = get_test_table_settings();

    $views->renderTableTestJs();
    $views->renderTableTestSettings([
        'edit' => $settings[0],
        'status' => $settings[1],
        'term' => $settings[2],
        'trans' => $settings[3],
        'rom' => $settings[4],
        'sentence' => $settings[5]
    ]);

    echo '<table class="sortable tab2" style="width:auto;" cellspacing="0" cellpadding="5">';

    $views->renderTableTestHeader();
    do_test_table_table_content($lang_record, $testsql);
    echo '</table>';
}
