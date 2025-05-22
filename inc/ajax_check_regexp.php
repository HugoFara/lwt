<?php
/**
 * \file
 * \brief Check whether a regexp is valid, returns error message or empty string
 *
 * Call: inc/ajax_check_regexp.php?....
 *      ... regex=regular_expression
 *
 * @package Lwt
 * @author  andreask7 <andreask7@users.noreply.github.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-ajax-check-regexp.html
 * @since   1.6.27-fork
 */

require_once __DIR__ . '/session_utility.php';

/**
 * Check if mecab is installed and accessible under the 'mecab' alias.
 *
 * @return void
 */
function check_mecab_accessibility()
{
    $conf = '';
    $handle = popen(get_mecab_path(" -P"), 'r');
    while (!feof($handle)) {
        $conf .= fgets($handle, 256);
    }
    pclose($handle);
    $conf_data = array();
    foreach (explode("\n", $conf) as $cLine) {
        if ($cLine) {
            list ($cKey, $cValue) = explode(":", $cLine, 2);
            $conf_data[$cKey] = trim($cValue);
        }
    }

    if (isset($conf_data['dicdir'])) {
        $dic = '';
        $handle = popen(get_mecab_path(" -D"), 'r');
        while (!feof($handle)) {
            $dic .= fgets($handle, 256);
        }
        pclose($handle);
        $dic_data = array();
        foreach (explode("\n", $dic) as $cLine) {
            if ($cLine) {
                list ($cKey, $cValue) = explode(":", $cLine, 2);
                $dic_data[$cKey] = trim($cValue);
            }
        }
        if ($dic_data["charset"] != "UTF-8") {
            echo "ERROR\n\nWRONG ENCODING!\nMeCab Dictionary must compiled with UTF-8!\n";
        }
    } else {
        echo "ERROR\n\nCould not find '" . get_mecab_path() . "'\n";
    }

}

/**
 * Check if string 'test' is consistently recognized as a word.
 *
 * @return void
 */
function check_standard_regex($regex)
{
    $match = preg_match('/[' . $regex . ']/u', 'test');
    $err = false;
    if ($match === false) {
        $err = true;
    } else {
        $record = mysqli_query(
            $GLOBALS["DBCONNECTION"],
            'SELECT "test" RLIKE ' . convert_regexp_to_sqlsyntax('['.$regex.']')
        );
        if ($record === false) {
            $err = true;
        }
    }
    if ($err) {
        echo "ERROR\n\nIncorrect Syntax of Field 'Word Regexp Characters'";
    }
}

/**
 * Make the actual query to check the regex.
 *
 * @param string $regex Regex to test
 *
 * @return void
 */
function do_ajax_check_regexp($regex)
{
    chdir('..');
    // Turn off error reporting
    $old_error = error_reporting(0);
    if ('MECAB'== strtoupper(trim($regex))) {
        check_mecab_accessibility();
    } else {
        check_standard_regex($regex);
    }
    // Set error reporting to old level
    error_reporting($old_error);
}

if (getreq('regex')) {
    // Deprecated way of accessing the request since 2.9.0!
    // Use the REST API with "action_type=regexp".
    // Please mind the final "p"!
    do_ajax_check_regexp(getreq('regex'));
}

?>
