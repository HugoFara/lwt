<?php

/**
 * \file
 * \brief Show test header frame - Legacy wrapper
 *
 * Call: do_test_header.php?lang=[langid]
 * Call: do_test_header.php?text=[textid]
 * Call: do_test_header.php?selection=1
 *      (SQL via $_SESSION['testsql'])
 *
 * This file provides backward-compatible functions while delegating
 * main functionality to TestService and TestViews.
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/do-test-header.html
 * @since    1.0.3
 *
 * @deprecated 3.0.0 Use TestController and TestService instead
 */

require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../Core/UI/ui_helpers.php';
require_once __DIR__ . '/../Core/Tag/tags.php';
require_once __DIR__ . '/../Core/Test/test_helpers.php';
require_once __DIR__ . '/../Core/Http/param_helpers.php';
require_once __DIR__ . '/../Core/Language/language_utilities.php';
require_once __DIR__ . '/../Services/TestService.php';
require_once __DIR__ . '/../Views/TestViews.php';

use Lwt\Database\Connection;
use Lwt\Database\Settings;
use Lwt\Services\TestService;
use Lwt\Views\TestViews;

/**
 * Set useful data for the test using SQL query.
 *
 * @param string &$title Title to be overwritten
 * @param string &$p     Property URL to be overwritten
 *
 * @return string SQL query to use
 *
 * @deprecated 3.0.0 Use TestService::getTestDataFromParams instead
 */
function get_sql_test_data(&$title, &$p)
{
    $service = new TestService();
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $p = "selection=" . $_REQUEST['selection'];

    $testsql = do_test_test_from_selection(
        $_REQUEST['selection'],
        $_SESSION['testsql']
    );

    $totalcount = Connection::fetchValue(
        "SELECT count(distinct WoID) AS value FROM $testsql"
    );
    $title = 'Selected ' . $totalcount . ' Term' . ($totalcount < 2 ? '' : 's');

    $cntlang = Connection::fetchValue(
        'SELECT count(distinct WoLgID) AS value FROM ' . $testsql
    );
    if ($cntlang > 1) {
        $message = 'Error: The selected terms are in ' . $cntlang . ' languages, ' .
        'but tests are only possible in one language at a time.';
        echo error_message_with_hide($message, true);
        return '';
    }
    $title .= ' IN ' . Connection::fetchValue(
        "SELECT LgName AS value
        FROM {$tbpref}languages, {$testsql} AND LgID = WoLgID
        LIMIT 1"
    );
    return $testsql;
}

/**
 * Set useful data for the test using language.
 *
 * @param string $title Title to be overwritten
 * @param string $p     Property URL to be overwritten
 *
 * @return string SQL query to use
 *
 * @deprecated 3.0.0 Use TestService::getTestDataFromParams instead
 */
function get_lang_test_data(&$title, &$p): string
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $langid = getreq('lang');
    $p = "lang=" . $langid;
    $title = "All Terms in " . Connection::fetchValue(
        "SELECT LgName AS value FROM {$tbpref}languages WHERE LgID = $langid"
    );
    $testsql = ' ' . $tbpref . 'words WHERE WoLgID = ' . $langid . ' ';
    return $testsql;
}

/**
 * Set useful data for the test using text.
 *
 * @param string $title Title to be overwritten
 * @param string $p     Property URL to be overwritten
 *
 * @return string SQL query to use
 *
 * @deprecated 3.0.0 Use TestService::getTestDataFromParams instead
 */
function get_text_test_data(&$title, &$p): string
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $textid = getreq('text');
    $p = "text=" . $textid;
    $title = Connection::fetchValue(
        'SELECT TxTitle AS value FROM ' . $tbpref . 'texts WHERE TxID = ' . $textid
    );
    Settings::save('currenttext', $_REQUEST['text']);
    $testsql =
    ' ' . $tbpref . 'words, ' . $tbpref . 'textitems2
    WHERE Ti2LgID = WoLgID AND Ti2WoID = WoID AND Ti2TxID = ' . $textid . ' ';
    return $testsql;
}

/**
 * Return the words count for this test.
 *
 * @param string $testsql SQL query for this test.
 *
 * @return array{0: string, 1: string} Total words due and total words learning
 *
 * @deprecated 3.0.0 Use TestService::getTestCounts instead
 */
function get_test_counts($testsql)
{
    $service = new TestService();
    $counts = $service->getTestCounts($testsql);
    return array($counts['due'], $counts['total']);
}

/**
 * Make the header row for tests.
 *
 * @param mixed $_p URL property to use (unused)
 *
 * @return void
 *
 * @deprecated 3.0.0 Use TestViews::renderHeaderRow instead
 */
function do_test_header_row($_p)
{
    $textId = is_numeric(getreq('text')) ? (int) getreq('text') : null;
    $views = new TestViews();
    $views->renderHeaderRow($textId);
}

/**
 * Prepare JavaScript content for the header.
 *
 * @return void
 *
 * @deprecated 3.0.0 Use TestViews::renderHeaderJs instead
 */
function do_test_header_js()
{
    $views = new TestViews();
    $views->renderHeaderJs();
}

/**
 * Make the header content for tests.
 *
 * @param string $title         Page title
 * @param string $p             URL property to use
 * @param string $totalcountdue Number of words due for today
 * @param string $totalcount    Total number of words.
 * @param string $language      L2 language name
 *
 * @return void
 *
 * @deprecated 3.0.0 Use TestViews::renderHeaderContent instead
 */
function do_test_header_content($title, $p, $totalcountdue, $totalcount, $language)
{
    $views = new TestViews();
    $views->renderHeaderContent($title, $p, (int)$totalcountdue, (int)$totalcount, $language);
}

/**
 * Set useful data for the test.
 *
 * @param string $title Title to be overwritten
 * @param string $p     Property URL to be overwritten
 *
 * @return array{0: string, 1: string} Total words due and total words learning
 *
 * @deprecated 3.0.0 Use TestService::getTestDataFromParams instead
 */
function get_test_data(&$title, &$p)
{
    if (isset($_REQUEST['selection']) && isset($_SESSION['testsql'])) {
        $testsql = get_sql_test_data($title, $p);
    } elseif (isset($_REQUEST['lang'])) {
        $testsql = get_lang_test_data($title, $p);
    } elseif (isset($_REQUEST['text'])) {
        $testsql = get_text_test_data($title, $p);
    } else {
        $testsql = '';
        $p = '';
        $title = 'Request Error!';
        pagestart($title, true);
        my_die("do_test_header.php called with wrong parameters");
    }
    return get_test_counts($testsql);
}

/**
 * Do the header for test page.
 *
 * @param string $title         Page title
 * @param string $p             URL property to use
 * @param string $totalcountdue Number of words due for today
 * @param string $totalcount    Total number of words.
 * @param string $language      L2 Language name
 *
 * @return void
 *
 * @deprecated 3.0.0 Use TestController::header instead
 */
function do_test_header_page($title, $p, $totalcountdue, $totalcount, $language)
{
    $service = new TestService();
    $views = new TestViews();

    $views->renderHeaderJs();

    $service->initializeTestSession((int)$totalcountdue);

    $views->renderHeaderRow(is_numeric(getreq('text')) ? (int)getreq('text') : null);
    $views->renderHeaderContent($title, $p, (int)$totalcountdue, (int)$totalcount, $language);
}

/**
 * Use requests passed to the page to start it.
 *
 * @param string $language L2 language name
 *
 * @return void
 *
 * @deprecated 3.0.0 Use TestController::header instead
 */
function start_test_header_page($language = 'L2')
{
    $title = $p = '';
    list($totalcountdue, $totalcount) = get_test_data($title, $p);
    do_test_header_page($title, $p, $totalcountdue, $totalcount, $language);
}
