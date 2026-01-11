<?php

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

declare(strict_types=1);

namespace Lwt\Shared\Infrastructure\Database;

use Lwt\Core\Bootstrap\EnvLoader;
use Lwt\Core\Utils\ErrorHandler;

/**
 * Database configuration and connection utilities.
 *
 * Handles loading database configuration from .env files
 * and establishing connections.
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
                DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
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

        @mysqli_query($dbconnection, "SET NAMES 'utf8mb4'");

        @mysqli_query($dbconnection, "SET SESSION sql_mode = 'STRICT_ALL_TABLES'");

        // Set shorter timeouts for test database connections to prevent zombie locks
        if (str_starts_with($dbname, 'test_')) {
            @mysqli_query($dbconnection, "SET SESSION wait_timeout = 60");
            @mysqli_query($dbconnection, "SET SESSION interactive_timeout = 60");
            @mysqli_query($dbconnection, "SET SESSION lock_wait_timeout = 30");
        }

        return $dbconnection;
    }
}
