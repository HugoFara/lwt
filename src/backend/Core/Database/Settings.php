<?php

/**
 * \file
 * \brief Application settings management.
 *
 * PHP version 8.1
 *
 * @category Database
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-database-settings.html
 * @since    3.0.0
 */

namespace Lwt\Database;

use Lwt\Core\Globals;

/**
 * Application settings management.
 *
 * Provides methods for reading, writing, and managing application settings
 * stored in the database, as well as LWT general table operations.
 *
 * @since 3.0.0
 */
class Settings
{
    /**
     * Convert a setting to 0 or 1.
     *
     * @param string     $key The setting key
     * @param string|int $dft Default value to use, should be convertible to string
     *
     * @return int
     *
     * @psalm-return 0|1
     */
    public static function getZeroOrOne(string $key, string|int $dft): int
    {
        $r = self::get($key);
        $r = ($r == '' ? $dft : (((int)$r !== 0) ? 1 : 0));
        return (int)$r;
    }

    /**
     * Get a setting from the database. It can also check for its validity.
     *
     * @param string $key Setting key. If $key is 'currentlanguage' or
     *                    'currenttext', we validate language/text.
     *
     * @return string Value in the database if found, or an empty string
     */
    public static function get(string $key): string
    {
        $val = get_first_value(
            'SELECT StValue AS value
            FROM ' . Globals::getTablePrefix() . 'settings
            WHERE StKey = ' . Escaping::toSqlSyntax($key)
        );
        if (isset($val)) {
            $val = trim((string) $val);
            if ($key == 'currentlanguage') {
                $val = Validation::language($val);
            }
            if ($key == 'currenttext') {
                $val = Validation::text($val);
            }
            return $val;
        }
        return '';
    }

    /**
     * Get the settings value for a specific key. Return a default value when possible.
     *
     * @param string $key Settings key
     *
     * @return string Requested setting, or default value, or ''
     */
    public static function getWithDefault(string $key): string
    {
        $tbpref = Globals::getTablePrefix();
        $dft = get_setting_data();
        $val = (string) get_first_value(
            'SELECT StValue AS value
             FROM ' . $tbpref . 'settings
             WHERE StKey = ' . Escaping::toSqlSyntax($key)
        );
        if ($val != '') {
            return trim($val);
        }
        if (isset($dft[$key])) {
            return $dft[$key]['dft'];
        }
        return '';
    }

    /**
     * Save the setting identified by a key with a specific value.
     *
     * @param string $k Setting key
     * @param mixed  $v Setting value, will get converted to string
     *
     * @return string Success message (starts by "OK: "), or error message
     *
     * @since 2.9.0 Success message starts by "OK: "
     */
    public static function save(string $k, mixed $v): string
    {
        $tbpref = Globals::getTablePrefix();
        $dft = get_setting_data();
        if (!isset($v)) {
            return 'Value is not set!';
        }
        if ($v === '') {
            return 'Value is an empty string!';
        }
        runsql(
            "DELETE FROM {$tbpref}settings
            WHERE StKey = " . Escaping::toSqlSyntax($k),
            ''
        );
        if (isset($dft[$k]) && $dft[$k]['num']) {
            $v = (int)$v;
            if ($v < $dft[$k]['min']) {
                $v = $dft[$k]['dft'];
            }
            if ($v > $dft[$k]['max']) {
                $v = $dft[$k]['dft'];
            }
        }
        $dum = runsql(
            "INSERT INTO {$tbpref}settings (StKey, StValue) VALUES(" .
            Escaping::toSqlSyntax($k) . ', ' .
            Escaping::toSqlSyntax((string)$v) . ')',
            ''
        );
        if (is_numeric($dum)) {
            return "OK: $dum rows changed";
        }
        /** @var string $dum Error message from runsql */
        return $dum;
    }

    /**
     * Check if the _lwtgeneral table exists, create it if not.
     *
     * @return void
     */
    public static function lwtTableCheck(): void
    {
        if (mysqli_num_rows(do_mysqli_query("SHOW TABLES LIKE '\\_lwtgeneral'")) == 0) {
            runsql(
                "CREATE TABLE IF NOT EXISTS _lwtgeneral (
                    LWTKey varchar(40) NOT NULL,
                    LWTValue varchar(40) DEFAULT NULL,
                    PRIMARY KEY (LWTKey)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8",
                ''
            );
            if (
                mysqli_num_rows(
                    do_mysqli_query("SHOW TABLES LIKE '\\_lwtgeneral'")
                ) == 0
            ) {
                my_die("Unable to create table '_lwtgeneral'!");
            }
        }
    }

    /**
     * Set a value in the _lwtgeneral table.
     *
     * @param string $key Key to set
     * @param string $val Value to store
     *
     * @return void
     */
    public static function lwtTableSet(string $key, string $val): void
    {
        self::lwtTableCheck();
        runsql(
            "INSERT INTO _lwtgeneral (LWTKey, LWTValue) VALUES (
                " . Escaping::toSqlSyntax($key) . ",
                " . Escaping::toSqlSyntax($val) . "
            ) ON DUPLICATE KEY UPDATE LWTValue = " . Escaping::toSqlSyntax($val),
            ''
        );
    }

    /**
     * Get a value from the _lwtgeneral table.
     *
     * @param string $key Key to retrieve
     *
     * @return string Value or empty string if not found
     */
    public static function lwtTableGet(string $key): string
    {
        self::lwtTableCheck();
        return (string)get_first_value(
            "SELECT LWTValue as value
            FROM _lwtgeneral
            WHERE LWTKey = " . Escaping::toSqlSyntax($key)
        );
    }
}
