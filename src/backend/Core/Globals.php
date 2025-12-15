<?php declare(strict_types=1);
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

use Lwt\Core\Exception\AuthException;

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
 * // Get database connection
 * $db = Globals::getDbConnection();
 *
 * // Get current user ID
 * $userId = Globals::getCurrentUserId();
 *
 * // Require user ID (throws if not authenticated)
 * $userId = Globals::requireUserId();
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
     *
     * @deprecated 3.0.0 Table prefix feature is deprecated. Multi-user isolation
     *             is now handled via user_id columns instead of table prefixes.
     *             Will be removed in a future version.
     */
    private static string $tablePrefix = '';

    /**
     * Whether the table prefix is fixed (from .env) or from DB
     *
     * @var bool
     *
     * @deprecated 3.0.0 Table prefix feature is deprecated. Multi-user isolation
     *             is now handled via user_id columns instead of table prefixes.
     *             Will be removed in a future version.
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
     * Current authenticated user ID
     *
     * @var int|null
     */
    private static ?int $currentUserId = null;

    /**
     * Whether multi-user mode is enabled
     *
     * When enabled, user_id filtering is applied to queries.
     *
     * @var bool
     */
    private static bool $multiUserEnabled = false;

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
     *
     * @deprecated 3.0.0 Table prefix feature is deprecated. Multi-user isolation
     *             is now handled via user_id columns instead of table prefixes.
     *             Will be removed in a future version.
     */
    public static function setTablePrefix(
        string $prefix,
        bool $isFixed = false
    ): void {
        @trigger_error(
            'Globals::setTablePrefix() is deprecated since version 3.0.0 ' .
            'and will be removed in a future version.',
            E_USER_DEPRECATED
        );
        self::$tablePrefix = $prefix;
        self::$tablePrefixIsFixed = $isFixed;
    }

    /**
     * Get the database table prefix.
     *
     * @return string The table prefix (may be empty string)
     *
     * @deprecated 3.0.0 Table prefix feature is deprecated. Multi-user isolation
     *             is now handled via user_id columns instead of table prefixes.
     *             Will be removed in a future version. Use table names directly.
     */
    public static function getTablePrefix(): string
    {
        @trigger_error(
            'Globals::getTablePrefix() is deprecated since version 3.0.0 ' .
            'and will be removed in a future version.',
            E_USER_DEPRECATED
        );
        return self::$tablePrefix;
    }

    /**
     * Check if the table prefix is fixed.
     *
     * A fixed prefix means it was set in .env rather than
     * being determined from the database.
     *
     * @return bool True if prefix is fixed
     *
     * @deprecated 3.0.0 Table prefix feature is deprecated. Multi-user isolation
     *             is now handled via user_id columns instead of table prefixes.
     *             Will be removed in a future version.
     */
    public static function isTablePrefixFixed(): bool
    {
        @trigger_error(
            'Globals::isTablePrefixFixed() is deprecated since version 3.0.0 ' .
            'and will be removed in a future version.',
            E_USER_DEPRECATED
        );
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
     * Note: Table prefixes are deprecated since version 3.0.0. This method
     * will continue to work but will return the table name unchanged once
     * table prefix support is removed.
     *
     * @param string $tableName The base table name (e.g., 'words')
     *
     * @return string The prefixed table name (e.g., 'lwt_words')
     */
    public static function table(string $tableName): string
    {
        return self::$tablePrefix . $tableName;
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

    // =========================================================================
    // User Context Management
    // =========================================================================

    /**
     * Set the current authenticated user ID.
     *
     * This should be called after successful authentication to establish
     * the user context for all subsequent database operations.
     *
     * @param int|null $userId The authenticated user's ID, or null to clear
     *
     * @return void
     *
     * @since 3.0.0
     */
    public static function setCurrentUserId(?int $userId): void
    {
        self::$currentUserId = $userId;
    }

    /**
     * Get the current authenticated user ID.
     *
     * Returns null if no user is authenticated.
     *
     * @return int|null The current user ID or null
     *
     * @since 3.0.0
     */
    public static function getCurrentUserId(): ?int
    {
        return self::$currentUserId;
    }

    /**
     * Get the current user ID, throwing if not authenticated.
     *
     * Use this method when a user must be authenticated for the operation
     * to proceed. It provides a cleaner alternative to checking for null.
     *
     * Usage:
     * ```php
     * try {
     *     $userId = Globals::requireUserId();
     *     // Proceed with user-specific operation
     * } catch (AuthException $e) {
     *     // Handle unauthenticated user
     * }
     * ```
     *
     * @return int The current user ID
     *
     * @throws AuthException If no user is authenticated
     *
     * @since 3.0.0
     */
    public static function requireUserId(): int
    {
        if (self::$currentUserId === null) {
            throw AuthException::userNotAuthenticated();
        }
        return self::$currentUserId;
    }

    /**
     * Check if a user is currently authenticated.
     *
     * @return bool True if a user is authenticated
     *
     * @since 3.0.0
     */
    public static function isAuthenticated(): bool
    {
        return self::$currentUserId !== null;
    }

    /**
     * Enable multi-user mode.
     *
     * When enabled, QueryBuilder will automatically filter queries by user_id
     * for user-scoped tables.
     *
     * @param bool $enabled Whether to enable multi-user mode
     *
     * @return void
     *
     * @since 3.0.0
     */
    public static function setMultiUserEnabled(bool $enabled): void
    {
        self::$multiUserEnabled = $enabled;
    }

    /**
     * Check if multi-user mode is enabled.
     *
     * @return bool True if multi-user mode is enabled
     *
     * @since 3.0.0
     */
    public static function isMultiUserEnabled(): bool
    {
        return self::$multiUserEnabled;
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
        self::$currentUserId = null;
        self::$multiUserEnabled = false;
    }
}
