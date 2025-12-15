<?php declare(strict_types=1);
/**
 * \file
 * \brief Database configuration and connection setup.
 *
 * PHP version 8.1
 *
 * @category Database
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-database-configuration.html
 * @since    3.0.0
 */

namespace Lwt\Database;

use Lwt\Core\Globals;
use Lwt\Core\EnvLoader;
use Lwt\Core\Utils\ErrorHandler;

/**
 * Database configuration and connection utilities.
 *
 * Handles loading database configuration from .env files,
 * establishing connections, and managing table prefixes.
 *
 * @since 3.0.0
 */
class Configuration
{
    /**
     * Load database configuration from .env file.
     *
     * @param string $envPath Path to the .env file
     *
     * @return array{server: string, userid: string, passwd: string, dbname: string, socket: string}
     */
    public static function loadFromEnv(string $envPath): array
    {
        $defaults = [
            'server' => 'localhost',
            'userid' => 'root',
            'passwd' => '',
            'dbname' => 'learning-with-texts',
            'socket' => ''
        ];

        if (EnvLoader::load($envPath)) {
            return EnvLoader::getDatabaseConfig();
        }

        return $defaults;
    }

    /**
     * Make the connection to the database.
     *
     * @param string $server Server name
     * @param string $userid Database user ID
     * @param string $passwd User password
     * @param string $dbname Database name
     * @param string $socket Database socket
     *
     * @return \mysqli Connection to the database
     *
     * @psalm-suppress UndefinedDocblockClass
     */
    public static function connect(
        string $server,
        string $userid,
        string $passwd,
        string $dbname,
        string $socket = ""
    ): \mysqli {
        // @ suppresses error messages

        // Necessary since mysqli_report default setting in PHP 8.1+ has changed
        @mysqli_report(MYSQLI_REPORT_OFF);

        $dbconnection = mysqli_init();

        if ($dbconnection === false) {
            ErrorHandler::die(
                'Database connection error. Is MySQL running?
                You can refer to the documentation:
                https://hugofara.github.io/lwt/docs/install.html
                [Error Code: ' . mysqli_connect_errno() .
                ' / Error Message: ' . (mysqli_connect_error() ?? 'Unknown error') . ']'
            );
        }

        @mysqli_options($dbconnection, MYSQLI_OPT_LOCAL_INFILE, 1);

        if ($socket != "") {
            $success = @mysqli_real_connect(
                $dbconnection,
                $server,
                $userid,
                $passwd,
                $dbname,
                socket: $socket
            );
        } else {
            $success = @mysqli_real_connect(
                $dbconnection,
                $server,
                $userid,
                $passwd,
                $dbname
            );
        }

        if (!$success && mysqli_connect_errno() == 1049) {
            // Database unknown, try with generic database
            $success = @mysqli_real_connect(
                $dbconnection,
                $server,
                $userid,
                $passwd
            );

            if (!$success) {
                ErrorHandler::die(
                    'DB connect error, connection parameters may be wrong,
                    please check your ".env" file.
                    You can refer to the documentation:
                    https://hugofara.github.io/lwt/docs/install.html
                    [Error Code: ' . mysqli_connect_errno() .
                    ' / Error Message: ' . (mysqli_connect_error() ?? 'Unknown error') . ']'
                );
            }
            $result = mysqli_query(
                $dbconnection,
                "CREATE DATABASE `$dbname`
                DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci"
            );
            if (!$result) {
                ErrorHandler::die("Failed to create database!");
            }
            mysqli_close($dbconnection);
            $success = @mysqli_real_connect(
                $dbconnection,
                $server,
                $userid,
                $passwd,
                $dbname
            );
        }

        if (!$success) {
            ErrorHandler::die(
                'DB connect error, connection parameters may be wrong,
                please check your ".env" file.
                You can refer to the documentation:
                https://hugofara.github.io/lwt/docs/install.html
                [Error Code: ' . mysqli_connect_errno() .
                ' / Error Message: ' . (mysqli_connect_error() ?? 'Unknown error') . ']'
            );
        }

        @mysqli_query($dbconnection, "SET NAMES 'utf8'");

        // @mysqli_query($dbconnection, "SET SESSION sql_mode = 'STRICT_ALL_TABLES'");
        @mysqli_query($dbconnection, "SET SESSION sql_mode = ''");

        // Set shorter timeouts for test database connections to prevent zombie locks
        if (str_starts_with($dbname, 'test_')) {
            @mysqli_query($dbconnection, "SET SESSION wait_timeout = 60");
            @mysqli_query($dbconnection, "SET SESSION interactive_timeout = 60");
            @mysqli_query($dbconnection, "SET SESSION lock_wait_timeout = 30");
        }

        return $dbconnection;
    }

    /**
     * Get the prefixes for the database.
     *
     * Is $tbpref set in .env? Take it and $fixed_tbpref=true.
     * If not: $fixed_tbpref=false. Is it set in table "_lwtgeneral"? Take it.
     * If not: Use $tbpref = '' (no prefix, old/standard behaviour).
     *
     * @param \mysqli     $dbconnection     Database connection
     * @param string|null $configuredPrefix Prefix from configuration (or null if not set)
     *
     * @return (bool|string)[]
     *
     * @psalm-return list{string, bool}
     *
     * @deprecated 3.0.0 Table prefix feature is deprecated. Multi-user isolation
     *             is now handled via user_id columns instead of table prefixes.
     *             Will be removed in a future version.
     */
    public static function getPrefix(\mysqli $dbconnection, ?string $configuredPrefix = null): array
    {
        @trigger_error(
            'Configuration::getPrefix() is deprecated since version 3.0.0 ' .
            'and will be removed in a future version. ' .
            'Table prefix feature is replaced by user_id-based isolation.',
            E_USER_DEPRECATED
        );

        // Set connection in Globals for backward compatibility
        Globals::setDbConnection($dbconnection);

        if ($configuredPrefix === null) {
            $fixed_tbpref = false;
            $tbpref = Settings::lwtTableGet("current_table_prefix");
        } else {
            $fixed_tbpref = true;
            $tbpref = $configuredPrefix;
        }

        $len_tbpref = strlen($tbpref);
        if ($len_tbpref > 0) {
            if ($len_tbpref > 20) {
                ErrorHandler::die(
                    'Table prefix/set "' . $tbpref .
                    '" longer than 20 digits or characters.' .
                    ' Please fix DB_TABLE_PREFIX in ".env".'
                );
            }
            for ($i = 0; $i < $len_tbpref; $i++) {
                if (
                    strpos(
                        "_0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ",
                        substr($tbpref, $i, 1)
                    ) === false
                ) {
                    ErrorHandler::die(
                        'Table prefix/set "' . $tbpref .
                        '" contains characters or digits other than 0-9, a-z, A-Z ' .
                        'or _. Please fix DB_TABLE_PREFIX in ".env".'
                    );
                }
            }
        }

        if (!$fixed_tbpref) {
            Settings::lwtTableSet("current_table_prefix", $tbpref);
        }

        // IF PREFIX IS NOT '', THEN ADD A '_', TO ENSURE NO IDENTICAL NAMES
        if ($tbpref !== '') {
            $tbpref .= "_";
        }
        return array($tbpref, $fixed_tbpref);
    }
}
