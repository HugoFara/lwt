<?php

/**
 * \file
 * \brief Centralized global state management for LWT.
 *
 * This class provides a clear, type-safe way to access application-wide
 * global variables. It replaces scattered `global $var` declarations
 * with explicit method calls.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-LWT-Globals.html
 * @since    3.0.0
 */

namespace Lwt\Core;

/**
 * Centralized management of LWT global variables.
 *
 * This class encapsulates all global state used throughout LWT,
 * making dependencies explicit and easier to track.
 *
 * Usage:
 * ```php
 * // Instead of: global $tbpref;
 * $prefix = Globals::getTablePrefix();
 *
 * // Instead of: global $DBCONNECTION;
 * $db = Globals::getDbConnection();
 * ```
 *
 * @category Lwt
 * @package  Lwt\Core
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-LWT-Globals.html
 * @since    3.0.0
 */
class Globals
{
    /**
     * Database connection object
     *
     * @var \mysqli|null
     */
    private static ?\mysqli $dbConnection = null;

    /**
     * Database table prefix (usually empty string)
     *
     * @var string
     */
    private static string $tablePrefix = '';

    /**
     * Whether the table prefix is fixed (from .env) or from DB
     *
     * @var bool
     */
    private static bool $tablePrefixIsFixed = false;

    /**
     * Debug mode flag (0=off, 1=on)
     *
     * @var int
     */
    private static int $debug = 0;

    /**
     * Error display flag (0=off, 1=on)
     *
     * @var int
     */
    private static int $displayErrors = 0;

    /**
     * Execution time display flag (0=off, 1=on)
     *
     * @var int
     */
    private static int $displayTime = 0;

    /**
     * Database name
     *
     * @var string
     */
    private static string $databaseName = '';

    /**
     * Whether globals have been initialized
     *
     * @var bool
     */
    private static bool $initialized = false;

    /**
     * Initialize all global variables.
     *
     * This should be called once during application bootstrap.
     *
     * @return void
     */
    public static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        // All settings default to 0 (off)
        self::$debug = 0;
        self::$displayErrors = 0;
        self::$displayTime = 0;

        self::$initialized = true;
    }

    /**
     * Set the database connection.
     *
     * @param \mysqli $connection The mysqli connection object
     *
     * @return void
     */
    public static function setDbConnection(\mysqli $connection): void
    {
        self::$dbConnection = $connection;
    }

    /**
     * Get the database connection.
     *
     * @return \mysqli|null The database connection object
     */
    public static function getDbConnection(): ?\mysqli
    {
        return self::$dbConnection;
    }

    /**
     * Set the database table prefix.
     *
     * @param string $prefix  The table prefix
     * @param bool   $isFixed Whether the prefix is fixed
     *
     * @return void
     */
    public static function setTablePrefix(
        string $prefix,
        bool $isFixed = false
    ): void {
        self::$tablePrefix = $prefix;
        self::$tablePrefixIsFixed = $isFixed;
    }

    /**
     * Get the database table prefix.
     *
     * @return string The table prefix (may be empty string)
     */
    public static function getTablePrefix(): string
    {
        return self::$tablePrefix;
    }

    /**
     * Check if the table prefix is fixed.
     *
     * A fixed prefix means it was set in .env rather than
     * being determined from the database.
     *
     * @return bool True if prefix is fixed
     */
    public static function isTablePrefixFixed(): bool
    {
        return self::$tablePrefixIsFixed;
    }

    /**
     * Set the database name.
     *
     * @param string $name The database name
     *
     * @return void
     */
    public static function setDatabaseName(string $name): void
    {
        self::$databaseName = $name;
    }

    /**
     * Get the database name.
     *
     * @return string The database name
     */
    public static function getDatabaseName(): string
    {
        return self::$databaseName;
    }

    /**
     * Set debug mode.
     *
     * @param int $value 1 for debug on, 0 for off
     *
     * @return void
     */
    public static function setDebug(int $value): void
    {
        self::$debug = $value;
    }

    /**
     * Check if debug mode is enabled.
     *
     * @return bool True if debug mode is on
     */
    public static function isDebug(): bool
    {
        return (bool) self::$debug;
    }

    /**
     * Get debug value as integer.
     *
     * @return int Debug flag (0 or 1)
     */
    public static function getDebug(): int
    {
        return self::$debug;
    }

    /**
     * Set error display mode.
     *
     * @param int $value 1 for display on, 0 for off
     *
     * @return void
     */
    public static function setDisplayErrors(int $value): void
    {
        self::$displayErrors = $value;
    }

    /**
     * Check if error display is enabled.
     *
     * @return bool True if error display is on
     */
    public static function shouldDisplayErrors(): bool
    {
        return (bool) self::$displayErrors;
    }

    /**
     * Set execution time display mode.
     *
     * @param int $value 1 for display on, 0 for off
     *
     * @return void
     */
    public static function setDisplayTime(int $value): void
    {
        self::$displayTime = $value;
    }

    /**
     * Check if execution time display is enabled.
     *
     * @return bool True if time display is on
     */
    public static function shouldDisplayTime(): bool
    {
        return (bool) self::$displayTime;
    }

    /**
     * Get a prefixed table name.
     *
     * Convenience method to get a full table name with prefix.
     *
     * @param string $tableName The base table name (e.g., 'words')
     *
     * @return string The prefixed table name (e.g., 'lwt_words')
     */
    public static function table(string $tableName): string
    {
        return self::getTablePrefix() . $tableName;
    }

    /**
     * Get a query builder instance for a table.
     *
     * Convenience method to start building a database query.
     *
     * Usage:
     * ```php
     * // SELECT query
     * $words = Globals::query('words')
     *     ->where('WoLgID', '=', 1)
     *     ->get();
     *
     * // INSERT query
     * Globals::query('words')
     *     ->insert(['WoText' => 'hello', 'WoLgID' => 1]);
     * ```
     *
     * @param string $tableName The base table name (e.g., 'words')
     *
     * @return \Lwt\Database\QueryBuilder
     *
     * @since 3.0.0
     */
    public static function query(string $tableName): \Lwt\Database\QueryBuilder
    {
        return \Lwt\Database\QueryBuilder::table($tableName);
    }

    /**
     * Reset all globals to initial state.
     *
     * Primarily used for testing.
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$dbConnection = null;
        self::$tablePrefix = '';
        self::$tablePrefixIsFixed = false;
        self::$debug = 0;
        self::$displayErrors = 0;
        self::$displayTime = 0;
        self::$databaseName = '';
        self::$initialized = false;
    }
}
