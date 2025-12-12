<?php declare(strict_types=1);
/**
 * \file
 * \brief Helper for adding user scope to raw SQL queries.
 *
 * PHP version 8.1
 *
 * @category Database
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-database-userscopedquery.html
 * @since    3.0.0
 */

namespace Lwt\Database;

use Lwt\Core\Globals;

/**
 * Helper class for adding user scope filtering to raw SQL queries.
 *
 * Use this class when you need to execute raw SQL but still want
 * automatic user scope filtering. For most cases, prefer using
 * QueryBuilder which handles this automatically.
 *
 * Usage:
 * ```php
 * // Get user scope condition for WHERE clause (for raw SQL)
 * $sql = "SELECT * FROM words WHERE WoLgID = 1"
 *      . UserScopedQuery::forTable('words');
 *
 * // For prepared statements
 * $bindings = [1]; // WoLgID = 1
 * $sql = "SELECT * FROM words WHERE WoLgID = ?"
 *      . UserScopedQuery::forTablePrepared('words', $bindings);
 *
 * // Get user_id for INSERT (when using raw SQL)
 * $userId = UserScopedQuery::getUserIdForInsert('words');
 * if ($userId !== null) {
 *     $data['WoUsID'] = $userId;
 * }
 * ```
 *
 * @category Database
 * @package  Lwt\Database
 * @since    3.0.0
 *
 * @psalm-suppress UnusedClass This class will be used by services in Phase 9
 */
class UserScopedQuery
{
    /**
     * Mapping of user-scoped tables to their user ID column.
     *
     * @var array<string, string>
     */
    private const USER_SCOPED_TABLES = [
        'languages' => 'LgUsID',
        'texts' => 'TxUsID',
        'archivedtexts' => 'AtUsID',
        'words' => 'WoUsID',
        'tags' => 'TgUsID',
        'tags2' => 'T2UsID',
        'newsfeeds' => 'NfUsID',
        'settings' => 'StUsID',
    ];

    /**
     * Get the user ID column name for a table.
     *
     * @param string $tableName The table name (without prefix)
     *
     * @return string|null The column name or null if not user-scoped
     */
    public static function getUserIdColumn(string $tableName): ?string
    {
        return self::USER_SCOPED_TABLES[$tableName] ?? null;
    }

    /**
     * Check if a table is user-scoped.
     *
     * @param string $tableName The table name (without prefix)
     *
     * @return bool True if the table requires user_id filtering
     */
    public static function isUserScopedTable(string $tableName): bool
    {
        return isset(self::USER_SCOPED_TABLES[$tableName]);
    }

    /**
     * Get the user ID value to use for inserts.
     *
     * Returns the current user ID when multi-user mode is enabled
     * and a user is authenticated. Returns null otherwise.
     *
     * @param string $tableName The table name (without prefix)
     *
     * @return int|null The user ID or null
     */
    public static function getUserIdForInsert(string $tableName): ?int
    {
        if (!Globals::isMultiUserEnabled()) {
            return null;
        }

        if (!self::isUserScopedTable($tableName)) {
            return null;
        }

        return Globals::getCurrentUserId();
    }

    /**
     * Get WHERE condition for user scope filtering.
     *
     * Returns a SQL fragment like " AND WoUsID = 1" when user scope
     * should be applied, or empty string otherwise.
     *
     * @param string $tableName The table name (without prefix)
     * @param string $alias     Optional table alias to prefix the column
     *
     * @return string SQL WHERE condition fragment (includes leading AND)
     */
    public static function forTable(string $tableName, string $alias = ''): string
    {
        if (!Globals::isMultiUserEnabled()) {
            return '';
        }

        $userId = Globals::getCurrentUserId();
        if ($userId === null) {
            return '';
        }

        $column = self::getUserIdColumn($tableName);
        if ($column === null) {
            return '';
        }

        $columnRef = $alias !== '' ? "{$alias}.{$column}" : $column;
        return " AND {$columnRef} = " . (int) $userId;
    }

    /**
     * Get WHERE condition for user scope filtering (prepared statement version).
     *
     * Returns a SQL fragment like " AND WoUsID = ?" and adds the user ID
     * to the provided bindings array.
     *
     * @param string             $tableName The table name (without prefix)
     * @param array<int, mixed>  &$bindings Reference to bindings array
     * @param string             $alias     Optional table alias
     *
     * @return string SQL WHERE condition fragment (includes leading AND)
     */
    public static function forTablePrepared(
        string $tableName,
        array &$bindings,
        string $alias = ''
    ): string {
        if (!Globals::isMultiUserEnabled()) {
            return '';
        }

        $userId = Globals::getCurrentUserId();
        if ($userId === null) {
            return '';
        }

        $column = self::getUserIdColumn($tableName);
        if ($column === null) {
            return '';
        }

        $columnRef = $alias !== '' ? "{$alias}.{$column}" : $column;
        $bindings[] = $userId;
        return " AND {$columnRef} = ?";
    }

    /**
     * Get a standalone WHERE clause for user scope.
     *
     * Returns "WHERE WoUsID = 1" when applicable, empty string otherwise.
     * Use this when you need a WHERE clause that only contains user scope.
     *
     * @param string $tableName The table name (without prefix)
     * @param string $alias     Optional table alias
     *
     * @return string SQL WHERE clause or empty string
     */
    public static function whereClause(string $tableName, string $alias = ''): string
    {
        if (!Globals::isMultiUserEnabled()) {
            return '';
        }

        $userId = Globals::getCurrentUserId();
        if ($userId === null) {
            return '';
        }

        $column = self::getUserIdColumn($tableName);
        if ($column === null) {
            return '';
        }

        $columnRef = $alias !== '' ? "{$alias}.{$column}" : $column;
        return "WHERE {$columnRef} = " . (int) $userId;
    }

    /**
     * Get a standalone WHERE clause (prepared statement version).
     *
     * @param string            $tableName The table name (without prefix)
     * @param array<int, mixed> &$bindings Reference to bindings array
     * @param string            $alias     Optional table alias
     *
     * @return string SQL WHERE clause or empty string
     */
    public static function whereClausePrepared(
        string $tableName,
        array &$bindings,
        string $alias = ''
    ): string {
        if (!Globals::isMultiUserEnabled()) {
            return '';
        }

        $userId = Globals::getCurrentUserId();
        if ($userId === null) {
            return '';
        }

        $column = self::getUserIdColumn($tableName);
        if ($column === null) {
            return '';
        }

        $columnRef = $alias !== '' ? "{$alias}.{$column}" : $column;
        $bindings[] = $userId;
        return "WHERE {$columnRef} = ?";
    }

    /**
     * Get the list of all user-scoped tables.
     *
     * @return array<string, string> Table name => user ID column mapping
     */
    public static function getUserScopedTables(): array
    {
        return self::USER_SCOPED_TABLES;
    }
}
