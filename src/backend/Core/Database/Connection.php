<?php

/**
 * \file
 * \brief Database connection wrapper class.
 *
 * PHP version 8.1
 *
 * @category Database
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-database-connection.html
 * @since    3.0.0
 */

namespace Lwt\Database;

use Lwt\Core\Globals;

/**
 * Database connection wrapper providing a clean interface for database operations.
 *
 * This class wraps mysqli and provides methods for common database operations.
 * It uses Globals internally for backward compatibility.
 *
 * @since 3.0.0
 */
class Connection
{
    /**
     * @var \mysqli|null The mysqli connection instance
     */
    private static ?\mysqli $instance = null;

    /**
     * Get the database connection instance.
     *
     * @return \mysqli The database connection
     * @throws \RuntimeException If no connection is available
     */
    public static function getInstance(): \mysqli
    {
        if (self::$instance === null) {
            self::$instance = Globals::getDbConnection();
        }

        if (self::$instance === null) {
            throw new \RuntimeException('Database connection not initialized');
        }

        return self::$instance;
    }

    /**
     * Set the database connection instance.
     *
     * @param \mysqli $connection The mysqli connection
     *
     * @return void
     */
    public static function setInstance(\mysqli $connection): void
    {
        self::$instance = $connection;
        Globals::setDbConnection($connection);
    }

    /**
     * Execute a raw SQL query.
     *
     * @param string $sql The SQL query to execute
     *
     * @return \mysqli_result|true Query result or true for non-SELECT queries
     *
     * @throws \RuntimeException On query failure
     */
    public static function query(string $sql): \mysqli_result|bool
    {
        $connection = self::getInstance();
        $result = mysqli_query($connection, $sql);

        if ($result === false) {
            throw new \RuntimeException(
                'SQL Error [' . mysqli_errno($connection) . ']: ' .
                mysqli_error($connection) . "\nQuery: " . $sql
            );
        }

        return $result;
    }

    /**
     * Execute a query and return all rows as an array.
     *
     * @param string $sql The SQL query to execute
     *
     * @return (float|int|null|string)[][]
     *
     * @psalm-return list<non-empty-array<string, float|int|null|string>>
     */
    public static function fetchAll(string $sql): array
    {
        $result = self::query($sql);

        if ($result === true) {
            return [];
        }

        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        mysqli_free_result($result);

        return $rows;
    }

    /**
     * Execute a query and return the first row.
     *
     * @param string $sql The SQL query to execute
     *
     * @return (float|int|null|string)[]|null
     *
     * @psalm-return array<string, float|int|null|string>|null
     */
    public static function fetchOne(string $sql): array|null
    {
        $result = self::query($sql);

        if ($result === true) {
            return null;
        }

        $row = mysqli_fetch_assoc($result);
        mysqli_free_result($result);

        return ($row !== false) ? $row : null;
    }

    /**
     * Execute a query and return a single value from the first row.
     *
     * @param string $sql    The SQL query to execute
     * @param string $column The column name to retrieve (default: 'value')
     *
     * @return mixed The value or null if not found
     */
    public static function fetchValue(string $sql, string $column = 'value'): mixed
    {
        $row = self::fetchOne($sql);

        if ($row === null || !array_key_exists($column, $row)) {
            return null;
        }

        return $row[$column];
    }

    /**
     * Execute an INSERT/UPDATE/DELETE query and return affected rows.
     *
     * @param string $sql The SQL query to execute
     *
     * @return int|numeric-string Number of affected rows
     *
     * @psalm-return int<-1, max>|numeric-string
     */
    public static function execute(string $sql): int|string
    {
        self::query($sql);
        return mysqli_affected_rows(self::getInstance());
    }

    /**
     * Get the last inserted ID.
     *
     * @return int|string The last insert ID
     */
    public static function lastInsertId(): int|string
    {
        return mysqli_insert_id(self::getInstance());
    }

    /**
     * Escape a string for use in SQL queries.
     *
     * @param string $value The value to escape
     *
     * @return string The escaped string
     */
    public static function escape(string $value): string
    {
        return mysqli_real_escape_string(self::getInstance(), $value);
    }

    /**
     * Escape and quote a string for SQL, returning 'NULL' for empty strings.
     *
     * @param string $value The value to escape
     *
     * @return string The escaped and quoted string, or 'NULL'
     */
    public static function escapeOrNull(string $value): string
    {
        $value = trim(str_replace("\r\n", "\n", $value));
        if ($value === '') {
            return 'NULL';
        }
        return "'" . self::escape($value) . "'";
    }

    /**
     * Escape and quote a string for SQL (never returns NULL).
     *
     * @param string $value The value to escape
     *
     * @return string The escaped and quoted string
     */
    public static function escapeString(string $value): string
    {
        $value = trim(str_replace("\r\n", "\n", $value));
        return "'" . self::escape($value) . "'";
    }

    /**
     * Reset the connection instance (primarily for testing).
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
