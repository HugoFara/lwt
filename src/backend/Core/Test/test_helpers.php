<?php

/**
 * \file
 * \brief Word test utilities.
 *
 * Functions for creating SQL projections for word tests.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-test-helpers.html
 * @since    3.0.0
 */

require_once __DIR__ . '/database_connect.php';

/**
 * Create a projection operator do perform word test.
 *
 * @param string    $key   Type of test.
 *                         - 'words': selection from words
 *                         - 'texts': selection from texts
 *                         - 'lang': selection from language
 *                         - 'text': selection from single text
 * @param array|int $value Object to select.
 *
 * @return string|null SQL projection necessary
 */
function do_test_test_get_projection($key, $value): string|null
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $testsql = null;
    switch ($key) {
        case 'words':
            // Test words in a list of words ID
            $id_string = implode(",", $value);
            $testsql = " {$tbpref}words WHERE WoID IN ($id_string) ";
            $cntlang = get_first_value(
                "SELECT COUNT(DISTINCT WoLgID) AS value
                FROM $testsql"
            );
            if ($cntlang > 1) {
                echo "<p>Sorry - The selected terms are in $cntlang languages," .
                " but tests are only possible in one language at a time.</p>";
                exit();
            }
            break;
        case 'texts':
            // Test text items from a list of texts ID
            $id_string = implode(",", $value);
            $testsql = " {$tbpref}words, {$tbpref}textitems2
            WHERE Ti2LgID = WoLgID AND Ti2WoID = WoID AND Ti2TxID IN ($id_string) ";
            $cntlang = get_first_value(
                "SELECT COUNT(DISTINCT WoLgID) AS value
            FROM $testsql"
            );
            if ($cntlang > 1) {
                echo "<p>Sorry - The selected terms are in $cntlang languages," .
                " but tests are only possible in one language at a time.</p>";
                exit();
            }
            break;
        case 'lang':
            // Test words from a specific language
            $testsql = " {$tbpref}words WHERE WoLgID = $value ";
            break;
        case 'text':
            // Test text items from a specific text ID
            $testsql = " {$tbpref}words, {$tbpref}textitems2
            WHERE Ti2LgID = WoLgID AND Ti2WoID = WoID AND Ti2TxID = $value ";
            break;
        default:
            my_die("do_test_test.php called with wrong parameters");
    }
    return $testsql;
}

/**
 * Prepare the SQL when the text is a selection.
 *
 * @param int    $selection_type. 2 is words selection and 3 is terms selection.
 * @param string $selection_data  Comma separated ID of elements to test.
 *
 * @return null|string SQL formatted string suitable to projection (inserted in a "FROM ")
 */
function do_test_test_from_selection(int $selection_type, string $selection_data): string|null
{
    $data_string_array = explode(",", trim($selection_data, "()"));
    $data_int_array = array_map('intval', $data_string_array);
    switch ($selection_type) {
        case 2:
            $test_sql = do_test_test_get_projection('words', $data_int_array);
            break;
        case 3:
            $test_sql = do_test_test_get_projection('texts', $data_int_array);
            break;
        default:
            $test_sql = $selection_data;
            $cntlang = get_first_value(
                "SELECT COUNT(DISTINCT WoLgID) AS value
                FROM $test_sql"
            );
            if ($cntlang > 1) {
                echo "<p>Sorry - The selected terms are in $cntlang languages," .
                " but tests are only possible in one language at a time.</p>";
                exit();
            }
    }
    return $test_sql;
}
