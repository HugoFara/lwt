<?php declare(strict_types=1);
/**
 * \file
 * \brief Prepared statement wrapper class for safe parameterized queries.
 *
 * PHP version 8.1
 *
 * @category Database
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-database-preparedstatement.html
 * @since    3.0.0
 */

namespace Lwt\Shared\Infrastructure\Database;

/**
 * Wrapper class for mysqli prepared statements.
 *
 * Provides a fluent interface for executing parameterized queries safely.
 *
 * Usage:
 * ```php
 * // Simple query with parameters
 * $stmt = Connection::prepare('SELECT * FROM words WHERE WoLgID = ? AND WoStatus = ?');
 * $rows = $stmt->bind('ii', $langId, $status)->fetchAll();
 *
 * // Insert with parameters
 * $stmt = Connection::prepare('INSERT INTO words (WoText, WoLgID) VALUES (?, ?)');
 * $stmt->bind('si', $text, $langId)->execute();
 * $insertId = $stmt->insertId();
 *
 * // Update with parameters
 * $stmt = Connection::prepare('UPDATE words SET WoStatus = ? WHERE WoID = ?');
 * $affected = $stmt->bind('ii', $status, $wordId)->execute();
 * ```
 *
 * @since 3.0.0
 */
class PreparedStatement
{
    /**
     * @var \mysqli_stmt The underlying mysqli statement
     */
    private \mysqli_stmt $stmt;

    /**
     * @var \mysqli The database connection
     */
    private \mysqli $connection;

    /**
     * @var string The original SQL query (for error messages)
     */
    private string $sql;

    /**
     * Create a new prepared statement wrapper.
     *
     * @param \mysqli $connection The database connection
     * @param string  $sql        The SQL query with placeholders
     *
     * @throws \RuntimeException If the statement cannot be prepared
     */
    public function __construct(\mysqli $connection, string $sql)
    {
        $this->connection = $connection;
        $this->sql = $sql;

        $stmt = $connection->prepare($sql);
        if ($stmt === false) {
            throw new \RuntimeException(
                'Failed to prepare statement [' . $connection->errno . ']: ' .
                $connection->error . "\nQuery: " . $sql
            );
        }

        $this->stmt = $stmt;
    }

    /**
     * Bind parameters to the prepared statement.
     *
     * @param string $types  Type string (i=integer, d=double, s=string, b=blob)
     * @param mixed  ...$params Parameters to bind
     *
     * @return $this For method chaining
     *
     * @throws \RuntimeException If binding fails
     */
    public function bind(string $types, mixed ...$params): static
    {
        if (strlen($types) !== count($params)) {
            throw new \RuntimeException(
                'Type string length (' . strlen($types) . ') does not match ' .
                'parameter count (' . count($params) . ')'
            );
        }

        if (count($params) > 0) {
            // mysqli_stmt::bind_param requires references
            $refs = [];
            $refs[] = $types;
            foreach ($params as $key => $_) {
                $refs[] = &$params[$key];
            }

            if (!$this->stmt->bind_param(...$refs)) {
                throw new \RuntimeException(
                    'Failed to bind parameters [' . $this->stmt->errno . ']: ' .
                    $this->stmt->error . "\nQuery: " . $this->sql
                );
            }
        }

        return $this;
    }

    /**
     * Bind parameters using an associative array.
     *
     * Automatically determines types based on PHP types:
     * - int -> 'i'
     * - float -> 'd'
     * - string/other -> 's'
     * - null -> 's' (will bind as NULL)
     *
     * @param array<int, mixed> $params Parameters to bind (indexed array)
     *
     * @return $this For method chaining
     *
     * @psalm-suppress PossiblyUnusedReturnValue Return value available for method chaining
     */
    public function bindValues(array $params): static
    {
        if (empty($params)) {
            return $this;
        }

        $types = '';
        foreach ($params as $value) {
            $types .= $this->getParamType($value);
        }

        return $this->bind($types, ...array_values($params));
    }

    /**
     * Determine the mysqli type character for a value.
     *
     * @param mixed $value The value to check
     *
     * @return string The type character (i, d, s, or b)
     */
    private function getParamType(mixed $value): string
    {
        if (is_int($value)) {
            return 'i';
        }
        if (is_float($value)) {
            return 'd';
        }
        // Strings, nulls, and everything else treated as string
        return 's';
    }

    /**
     * Execute the prepared statement.
     *
     * @return int Number of affected rows (for INSERT/UPDATE/DELETE), -1 on error
     *
     * @throws \RuntimeException If execution fails
     */
    public function execute(): int
    {
        if (!$this->stmt->execute()) {
            throw new \RuntimeException(
                'Failed to execute statement [' . $this->stmt->errno . ']: ' .
                $this->stmt->error . "\nQuery: " . $this->sql
            );
        }

        return (int) $this->stmt->affected_rows;
    }

    /**
     * Execute and fetch all rows as an associative array.
     *
     * @return array<int, array<string, mixed>> Array of rows
     */
    public function fetchAll(): array
    {
        if (!$this->stmt->execute()) {
            throw new \RuntimeException(
                'Failed to execute statement [' . $this->stmt->errno . ']: ' .
                $this->stmt->error . "\nQuery: " . $this->sql
            );
        }

        $result = $this->stmt->get_result();
        if ($result === false) {
            // For queries that don't return a result set
            return [];
        }

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();

        return $rows;
    }

    /**
     * Execute and fetch the first row.
     *
     * @return array<string, mixed>|null The first row or null if no results
     */
    public function fetchOne(): ?array
    {
        if (!$this->stmt->execute()) {
            throw new \RuntimeException(
                'Failed to execute statement [' . $this->stmt->errno . ']: ' .
                $this->stmt->error . "\nQuery: " . $this->sql
            );
        }

        $result = $this->stmt->get_result();
        if ($result === false) {
            return null;
        }

        $row = $result->fetch_assoc();
        $result->free();

        return $row ?: null;
    }

    /**
     * Execute and fetch a single column value from the first row.
     *
     * @param string $column The column name to retrieve
     *
     * @return mixed The value or null if not found
     */
    public function fetchValue(string $column = 'value'): mixed
    {
        $row = $this->fetchOne();

        if ($row === null || !array_key_exists($column, $row)) {
            return null;
        }

        return $row[$column];
    }

    /**
     * Execute and fetch a single column from all rows.
     *
     * @param string $column The column name to retrieve
     *
     * @return array<int, mixed> Array of values
     */
    public function fetchColumn(string $column): array
    {
        $rows = $this->fetchAll();

        return array_column($rows, $column);
    }

    /**
     * Get the ID generated by the last INSERT query.
     *
     * @return int|string The insert ID
     */
    public function insertId(): int|string
    {
        return $this->stmt->insert_id;
    }

    /**
     * Get the number of affected rows from the last query.
     *
     * @return int Number of affected rows, -1 on error
     */
    public function affectedRows(): int
    {
        return (int) $this->stmt->affected_rows;
    }

    /**
     * Get the number of rows in the result set.
     *
     * Note: This only works after fetchAll() has been called,
     * or after execute() for queries that return a result.
     *
     * @return int Number of rows (0 or more)
     */
    public function numRows(): int
    {
        return (int) $this->stmt->num_rows;
    }

    /**
     * Close the prepared statement.
     *
     * @return void
     */
    public function close(): void
    {
        $this->stmt->close();
    }

    /**
     * Get the underlying mysqli_stmt object.
     *
     * For advanced operations not covered by this wrapper.
     *
     * @return \mysqli_stmt The underlying statement
     */
    public function getStatement(): \mysqli_stmt
    {
        return $this->stmt;
    }

    /**
     * Destructor - close the statement when done.
     */
    public function __destruct()
    {
        // Only close if the connection is still valid
        if ($this->connection->ping()) {
            $this->stmt->close();
        }
    }
}
