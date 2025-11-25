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
 * @package Lwt
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-LWT-Globals.html
 * @since   3.0.0
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
 * $prefix = LWT_Globals::getTablePrefix();
 *
 * // Instead of: global $DBCONNECTION;
 * $db = LWT_Globals::getDbConnection();
 * ```
 *
 * @since 3.0.0
 */
class LWT_Globals
{
    /**
     * @var \mysqli|null Database connection object
     */
    private static ?\mysqli $dbConnection = null;

    /**
     * @var string Database table prefix (usually empty string)
     */
    private static string $tablePrefix = '';

    /**
     * @var bool Whether the table prefix is fixed (from .env) or from DB
     */
    private static bool $tablePrefixIsFixed = false;

    /**
     * @var int Debug mode flag (0=off, 1=on)
     */
    private static int $debug = 0;

    /**
     * @var int Error display flag (0=off, 1=on)
     */
    private static int $displayErrors = 0;

    /**
     * @var int Execution time display flag (0=off, 1=on)
     */
    private static int $displayTime = 0;

    /**
     * @var string Database name
     */
    private static string $databaseName = '';

    /**
     * @var bool Whether globals have been initialized
     */
    private static bool $initialized = false;

    /**
     * Initialize all global variables.
     *
     * This should be called once during application bootstrap.
     * It also populates $GLOBALS for backward compatibility.
     *
     * @return void
     */
    public static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        // Settings are initialized with defaults in settings.php
        self::$debug = $GLOBALS['debug'] ?? 0;
        self::$displayErrors = $GLOBALS['dsplerrors'] ?? 0;
        self::$displayTime = $GLOBALS['dspltime'] ?? 0;

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
        // Backward compatibility: also set in $GLOBALS
        $GLOBALS['DBCONNECTION'] = $connection;
    }

    /**
     * Get the database connection.
     *
     * @return \mysqli|null The database connection object
     */
    public static function getDbConnection(): ?\mysqli
    {
        return self::$dbConnection ?? $GLOBALS['DBCONNECTION'] ?? null;
    }

    /**
     * Set the database table prefix.
     *
     * @param string $prefix  The table prefix
     * @param bool   $isFixed Whether the prefix is fixed (from .env)
     *
     * @return void
     */
    public static function setTablePrefix(string $prefix, bool $isFixed = false): void
    {
        self::$tablePrefix = $prefix;
        self::$tablePrefixIsFixed = $isFixed;
        // Backward compatibility: also set in $GLOBALS
        $GLOBALS['tbpref'] = $prefix;
        $GLOBALS['fixed_tbpref'] = (int) $isFixed;
    }

    /**
     * Get the database table prefix.
     *
     * @return string The table prefix (may be empty string)
     */
    public static function getTablePrefix(): string
    {
        // Check $GLOBALS first for backward compatibility during transition
        if (isset($GLOBALS['tbpref'])) {
            return $GLOBALS['tbpref'];
        }
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
        if (isset($GLOBALS['fixed_tbpref'])) {
            return (bool) $GLOBALS['fixed_tbpref'];
        }
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
        $GLOBALS['dbname'] = $name;
    }

    /**
     * Get the database name.
     *
     * @return string The database name
     */
    public static function getDatabaseName(): string
    {
        if (isset($GLOBALS['dbname'])) {
            return $GLOBALS['dbname'];
        }
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
        $GLOBALS['debug'] = $value;
    }

    /**
     * Check if debug mode is enabled.
     *
     * @return bool True if debug mode is on
     */
    public static function isDebug(): bool
    {
        return (bool) ($GLOBALS['debug'] ?? self::$debug);
    }

    /**
     * Get debug value as integer.
     *
     * @return int Debug flag (0 or 1)
     */
    public static function getDebug(): int
    {
        return (int) ($GLOBALS['debug'] ?? self::$debug);
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
        $GLOBALS['dsplerrors'] = $value;
    }

    /**
     * Check if error display is enabled.
     *
     * @return bool True if error display is on
     */
    public static function shouldDisplayErrors(): bool
    {
        return (bool) ($GLOBALS['dsplerrors'] ?? self::$displayErrors);
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
        $GLOBALS['dspltime'] = $value;
    }

    /**
     * Check if execution time display is enabled.
     *
     * @return bool True if time display is on
     */
    public static function shouldDisplayTime(): bool
    {
        return (bool) ($GLOBALS['dspltime'] ?? self::$displayTime);
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
