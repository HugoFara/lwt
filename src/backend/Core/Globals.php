<?php

declare(strict_types=1);

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
     * Whether error display is enabled
     *
     * @var bool
     */
    private static bool $errorDisplayEnabled = false;

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
     * Whether the current authenticated user is an admin.
     *
     * @var bool
     */
    private static bool $currentUserIsAdmin = false;

    /**
     * Whether backup restore is enabled.
     *
     * Disabled by default in multi-user mode for security.
     *
     * @var bool|null Null means use default based on multi-user mode
     */
    private static ?bool $backupRestoreEnabled = null;

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

        // All settings default to off
        self::$errorDisplayEnabled = false;

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
     * Enable or disable error display.
     *
     * @param bool $enabled True to enable error display
     *
     * @return void
     */
    public static function setErrorDisplay(bool $enabled): void
    {
        self::$errorDisplayEnabled = $enabled;
    }

    /**
     * Check if error display is enabled.
     *
     * @return bool True if error display is on
     */
    public static function isErrorDisplayEnabled(): bool
    {
        return self::$errorDisplayEnabled;
    }

    /**
     * Get a table name.
     *
     * Convenience method to get a full table name.
     *
     * @param string $tableName The base table name (e.g., 'words')
     *
     * @return string The table name
     */
    public static function table(string $tableName): string
    {
        return $tableName;
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
     * @return \Lwt\Shared\Infrastructure\Database\QueryBuilder
     *
     * @since 3.0.0
     */
    public static function query(string $tableName): \Lwt\Shared\Infrastructure\Database\QueryBuilder
    {
        return \Lwt\Shared\Infrastructure\Database\QueryBuilder::table($tableName);
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
     * Set whether the current user is an admin.
     *
     * This should be called after successful authentication to establish
     * the admin context for authorization checks.
     *
     * @param bool $isAdmin Whether the current user is an admin
     *
     * @return void
     *
     * @since 3.0.0
     */
    public static function setCurrentUserIsAdmin(bool $isAdmin): void
    {
        self::$currentUserIsAdmin = $isAdmin;
    }

    /**
     * Check if the current authenticated user is an admin.
     *
     * @return bool True if the current user is an admin
     *
     * @since 3.0.0
     */
    public static function isCurrentUserAdmin(): bool
    {
        return self::$currentUserIsAdmin;
    }

    /**
     * Set whether backup restore is enabled.
     *
     * @param bool|null $enabled Whether to enable backup restore, null for default
     *
     * @return void
     *
     * @since 3.0.0
     */
    public static function setBackupRestoreEnabled(?bool $enabled): void
    {
        self::$backupRestoreEnabled = $enabled;
    }

    /**
     * Check if backup restore is enabled.
     *
     * In multi-user mode, backup restore is disabled by default for security.
     * Single-user mode allows restore by default.
     * This can be overridden by explicitly setting BACKUP_RESTORE_ENABLED.
     *
     * @return bool True if backup restore is enabled
     *
     * @since 3.0.0
     */
    public static function isBackupRestoreEnabled(): bool
    {
        // Explicit configuration takes precedence
        if (self::$backupRestoreEnabled !== null) {
            return self::$backupRestoreEnabled;
        }

        // Default: disabled in multi-user mode, enabled in single-user
        return !self::$multiUserEnabled;
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
        self::$errorDisplayEnabled = false;
        self::$databaseName = '';
        self::$initialized = false;
        self::$currentUserId = null;
        self::$multiUserEnabled = false;
        self::$currentUserIsAdmin = false;
        self::$backupRestoreEnabled = null;
    }
}
