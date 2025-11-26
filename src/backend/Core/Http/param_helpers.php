<?php

/**
 * \file
 * \brief Request/Session/Database parameter handling utilities.
 *
 * Functions for processing parameters from requests, sessions, and database.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-param-helpers.html
 * @since    3.0.0
 */

require_once __DIR__ . '/../database_connect.php';

use Lwt\Database\Settings;

/**
 * Get a session value and update it if necessary.
 *
 * @param string     $reqkey  If in $_REQUEST, update the session with $_REQUEST[$reqkey]
 * @param string     $sesskey Field of the session to get or update
 * @param string|int $default Default value to return
 * @param bool       $isnum   If true, convert the result to an int
 *
 * @return string|int The required data unless $isnum is specified
 */
function processSessParam($reqkey, $sesskey, $default, $isnum)
{
    if (isset($_REQUEST[$reqkey])) {
        $reqdata = trim($_REQUEST[$reqkey]);
        $_SESSION[$sesskey] = $reqdata;
        $result = $reqdata;
    } elseif (isset($_SESSION[$sesskey])) {
        $result = $_SESSION[$sesskey];
    } else {
        $result = $default;
    }
    if ($isnum) {
        $result = (int)$result;
    }
    return $result;
}

/**
 * Get a database value and update it if necessary.
 *
 * @param string $reqkey  If in $_REQUEST, update the database with $_REQUEST[$reqkey]
 * @param string $dbkey   Field of the database to get or update
 * @param string $default Default value to return
 * @param bool   $isnum   If true, convert the result to an int
 *
 * @return string|int The string data unless $isnum is specified
 */
function processDBParam($reqkey, $dbkey, $default, $isnum)
{
    $dbdata = Settings::get($dbkey);
    if (isset($_REQUEST[$reqkey])) {
        $reqdata = trim($_REQUEST[$reqkey]);
        Settings::save($dbkey, $reqdata);
        $result = $reqdata;
    } elseif ($dbdata != '') {
        $result = $dbdata;
    } else {
        $result = $default;
    }
    if ($isnum) {
        $result = (int)$result;
    }
    return $result;
}

/**
 * Get a request when possible. Otherwise, return an empty string.
 *
 * @param  string $s Request key
 * @return string Trimmed request or empty string
 */
function getreq($s)
{
    if (isset($_REQUEST[$s])) {
        return trim($_REQUEST[$s]);
    }
    return '';
}

/**
 * Get a session variable when possible. Otherwise, return an empty string.
 *
 * @param  string $s Session variable key
 * @return string Trimmed sesseion variable or empty string
 */
function getsess($s)
{
    if (isset($_SESSION[$s])) {
        $value = $_SESSION[$s];
        if (is_array($value)) {
            return '';
        }
        return trim((string)$value);
    }
    return '';
}
