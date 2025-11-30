<?php declare(strict_types=1);
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

namespace Lwt\Core\Http;

require_once __DIR__ . '/../Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/InputValidator.php';

use Lwt\Database\Settings;

/**
 * Request/Session/Database parameter handling utilities.
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class ParamHelpers
{
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
    public static function processSessParam($reqkey, $sesskey, $default, $isnum)
    {
        if (InputValidator::has($reqkey)) {
            $reqdata = InputValidator::getString($reqkey);
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
    public static function processDBParam($reqkey, $dbkey, $default, $isnum)
    {
        $dbdata = Settings::get($dbkey);
        if (InputValidator::has($reqkey)) {
            $reqdata = InputValidator::getString($reqkey);
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
     *
     * @deprecated Use InputValidator::getString() instead
     */
    public static function getreq($s)
    {
        return InputValidator::getString($s);
    }

    /**
     * Get a session variable when possible. Otherwise, return an empty string.
     *
     * @param  string $s Session variable key
     * @return string Trimmed session variable or empty string
     */
    public static function getsess($s)
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
}
