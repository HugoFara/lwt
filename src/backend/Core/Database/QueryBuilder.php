<?php declare(strict_types=1);
/**
 * \file
 * \brief Fluent query builder for database operations.
 *
 * PHP version 8.1
 *
 * @category Database
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-database-querybuilder.html
 * @since    3.0.0
 */

namespace Lwt\Database;

use Lwt\Core\Globals;

/**
 * Fluent query builder for constructing SQL queries.
 *
 * Supports both traditional escaped queries and parameterized prepared statements.
 *
 * Usage (Traditional - escaped):
 * ```php
 * // SELECT query
 * $users = QueryBuilder::table('words')
 *     ->select(['WoID', 'WoText'])
 *     ->where('WoLgID', '=', 1)
 *     ->orderBy('WoText')
 *     ->limit(10)
 *     ->get();
 *
 * // INSERT query
 * $id = QueryBuilder::table('words')
 *     ->insert(['WoText' => 'hello', 'WoLgID' => 1]);
 * ```
 *
 * Usage (Prepared Statements - recommended):
 * ```php
 * // SELECT with prepared statement
 * $users = QueryBuilder::table('words')
 *     ->select(['WoID', 'WoText'])
 *     ->where('WoLgID', '=', 1)
 *     ->orderBy('WoText')
 *     ->limit(10)
 *     ->getPrepared();
 *
 * // INSERT with prepared statement
 * $id = QueryBuilder::table('words')
 *     ->insertPrepared(['WoText' => 'hello', 'WoLgID' => 1]);
 *
 * // UPDATE with prepared statement
 * QueryBuilder::table('words')
 *     ->where('WoID', '=', 5)
 *     ->updatePrepared(['WoStatus' => 2]);
 *
 * // DELETE with prepared statement
 * QueryBuilder::table('words')
 *     ->where('WoID', '=', 5)
 *     ->deletePrepared();
 * ```
 *
 * @since 3.0.0
 */
class QueryBuilder
{
    /**
     * @var string The table name (with prefix)
     */
    private string $table;

    /**
     * @var array<int, string> Columns to select
     */
    private array $columns = ['*'];

    /**
     * @var array<int, array{column: string, operator: string, value: mixed, boolean: string}> WHERE conditions
     */
    private array $wheres = [];

    /**
     * @var array<int, array{column: string, direction: string}> ORDER BY clauses
     */
    private array $orders = [];

    /**
     * @var array<int, string> GROUP BY columns
     */
    private array $groups = [];

    /**
     * @var int|null LIMIT value
     */
    private ?int $limitValue = null;

    /**
     * @var int|null OFFSET value
     */
    private ?int $offsetValue = null;

    /**
     * @var list<array{type: string, table: string, first: string, operator: string, second: string}> JOIN clauses
     */
    private array $joins = [];

    /**
     * @var bool Whether to use DISTINCT
     */
    private bool $distinct = false;

    /**
     * @var array<int, mixed> Parameters for prepared statements
     */
    private array $bindings = [];

    /**
     * Create a new query builder instance for a table.
     *
     * @param string $tableName The table name (without prefix)
     */
    public function __construct(string $tableName)
    {
        $this->table = Globals::getTablePrefix() . $tableName;
    }

    /**
     * Create a new query builder for a table.
     *
     * @param string $tableName The table name (without prefix)
     *
     * @return self
     */
    public static function table(string $tableName): self
    {
        return new self($tableName);
    }

    /**
     * Set the columns to select.
     *
     * @param array<int, string>|string $columns Columns to select
     */
    public function select(array|string $columns = ['*']): static
    {
        $this->columns = is_array($columns) ? $columns : [$columns];
        return $this;
    }

    /**
     * Add DISTINCT to the query.
     */
    public function distinct(): static
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * Add a WHERE clause.
     *
     * @param string $column   The column name
     * @param mixed  $operator The comparison operator or value (if using 2-arg form)
     * @param mixed  $value    The value to compare against
     * @param string $boolean  The boolean connector (AND/OR)
     */
    public function where(
        string $column,
        mixed $operator = '=',
        mixed $value = null,
        string $boolean = 'AND'
    ): static {
        // Handle simple equality: where('col', 'value')
        if ($value === null && !in_array(strtoupper((string)$operator), ['=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'IS NULL', 'IS NOT NULL'])) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'column' => $column,
            'operator' => strtoupper($operator),
            'value' => $value,
            'boolean' => strtoupper($boolean)
        ];

        return $this;
    }

    /**
     * Add an OR WHERE clause.
     *
     * @param string $column   The column name
     * @param mixed  $operator The comparison operator or value (if using 2-arg form)
     * @param mixed  $value    The value to compare against
     */
    public function orWhere(string $column, mixed $operator = '=', mixed $value = null): static
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * Add a WHERE IN clause.
     *
     * @param string       $column  The column name
     * @param array<mixed> $values  The values to check against
     * @param string       $boolean The boolean connector
     * @param bool         $not     Whether to use NOT IN
     */
    public function whereIn(
        string $column,
        array $values,
        string $boolean = 'AND',
        bool $not = false
    ): static {
        $operator = $not ? 'NOT IN' : 'IN';
        return $this->where($column, $operator, $values, $boolean);
    }

    /**
     * Add a WHERE NOT IN clause.
     *
     * @param string       $column The column name
     * @param array<mixed> $values The values to check against
     */
    public function whereNotIn(string $column, array $values): static
    {
        return $this->whereIn($column, $values, 'AND', true);
    }

    /**
     * Add a WHERE NULL clause.
     *
     * @param string $column  The column name
     * @param string $boolean The boolean connector
     * @param bool   $not     Whether to use IS NOT NULL
     */
    public function whereNull(string $column, string $boolean = 'AND', bool $not = false): static
    {
        $operator = $not ? 'IS NOT NULL' : 'IS NULL';
        return $this->where($column, $operator, null, $boolean);
    }

    /**
     * Add a WHERE NOT NULL clause.
     *
     * @param string $column The column name
     */
    public function whereNotNull(string $column): static
    {
        return $this->whereNull($column, 'AND', true);
    }

    /**
     * Add a raw WHERE clause.
     *
     * @param string $sql     Raw SQL for the WHERE clause
     * @param string $boolean The boolean connector
     */
    public function whereRaw(string $sql, string $boolean = 'AND'): static
    {
        $this->wheres[] = [
            'column' => '',
            'operator' => 'RAW',
            'value' => $sql,
            'boolean' => strtoupper($boolean)
        ];

        return $this;
    }

    /**
     * Add a JOIN clause.
     *
     * @param string $table    The table to join
     * @param string $first    The first column
     * @param string $operator The join operator
     * @param string $second   The second column
     * @param string $type     The join type (INNER, LEFT, RIGHT)
     */
    public function join(
        string $table,
        string $first,
        string $operator = '=',
        string $second = '',
        string $type = 'INNER'
    ): static {
        // Handle simple join: join('table', 'col1', 'col2')
        if ($second === '' && !in_array($operator, ['=', '!=', '<', '>', '<=', '>='])) {
            $second = $operator;
            $operator = '=';
        }

        $this->joins[] = [
            'type' => strtoupper($type),
            'table' => Globals::getTablePrefix() . $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];

        return $this;
    }

    /**
     * Add a LEFT JOIN clause.
     *
     * @param string $table    The table to join
     * @param string $first    The first column
     * @param string $operator The join operator
     * @param string $second   The second column
     */
    public function leftJoin(string $table, string $first, string $operator = '=', string $second = ''): static
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * Add a RIGHT JOIN clause.
     *
     * @param string $table    The table to join
     * @param string $first    The first column
     * @param string $operator The join operator
     * @param string $second   The second column
     */
    public function rightJoin(string $table, string $first, string $operator = '=', string $second = ''): static
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * Add an ORDER BY clause.
     *
     * @param string $column    The column to order by
     * @param string $direction The sort direction (ASC/DESC)
     */
    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtoupper($direction)
        ];

        return $this;
    }

    /**
     * Add a descending ORDER BY clause.
     *
     * @param string $column The column to order by
     */
    public function orderByDesc(string $column): static
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * Add a GROUP BY clause.
     *
     * @param string|array<int, string> $columns The column(s) to group by
     */
    public function groupBy(string|array $columns): static
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $this->groups = array_merge($this->groups, $columns);

        return $this;
    }

    /**
     * Set the LIMIT value.
     *
     * @param int $limit The maximum number of rows
     */
    public function limit(int $limit): static
    {
        $this->limitValue = $limit;
        return $this;
    }

    /**
     * Set the OFFSET value.
     *
     * @param int $offset The number of rows to skip
     */
    public function offset(int $offset): static
    {
        $this->offsetValue = $offset;
        return $this;
    }

    /**
     * Build the SELECT SQL query.
     *
     * @return string The SQL query
     */
    public function toSql(): string
    {
        $sql = 'SELECT ';

        if ($this->distinct) {
            $sql .= 'DISTINCT ';
        }

        $sql .= implode(', ', $this->columns);
        $sql .= ' FROM ' . $this->table;

        // Add JOINs
        foreach ($this->joins as $join) {
            $sql .= ' ' . $join['type'] . ' JOIN ' . $join['table'];
            $sql .= ' ON ' . $join['first'] . ' ' . $join['operator'] . ' ' . $join['second'];
        }

        // Add WHERE clauses
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->compileWheres();
        }

        // Add GROUP BY
        if (!empty($this->groups)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groups);
        }

        // Add ORDER BY
        if (!empty($this->orders)) {
            $orderClauses = array_map(
                fn($order) => $order['column'] . ' ' . $order['direction'],
                $this->orders
            );
            $sql .= ' ORDER BY ' . implode(', ', $orderClauses);
        }

        // Add LIMIT
        if ($this->limitValue !== null) {
            $sql .= ' LIMIT ' . $this->limitValue;
        }

        // Add OFFSET
        if ($this->offsetValue !== null) {
            $sql .= ' OFFSET ' . $this->offsetValue;
        }

        return $sql;
    }

    /**
     * Compile WHERE clauses into SQL.
     *
     * @return string The WHERE clause SQL
     */
    private function compileWheres(): string
    {
        $sql = '';

        foreach ($this->wheres as $index => $where) {
            // Add boolean connector (skip for first condition)
            if ($index > 0) {
                $sql .= ' ' . $where['boolean'] . ' ';
            }

            if ($where['operator'] === 'RAW') {
                $sql .= $where['value'];
                continue;
            }

            if (in_array($where['operator'], ['IS NULL', 'IS NOT NULL'])) {
                $sql .= $where['column'] . ' ' . $where['operator'];
                continue;
            }

            if (in_array($where['operator'], ['IN', 'NOT IN'])) {
                $values = array_map(
                    fn(mixed $v): string => $this->quoteValue($v),
                    $where['value']
                );
                $sql .= $where['column'] . ' ' . $where['operator'] . ' (' . implode(', ', $values) . ')';
                continue;
            }

            $sql .= $where['column'] . ' ' . $where['operator'] . ' ' . $this->quoteValue($where['value']);
        }

        return $sql;
    }

    /**
     * Quote a value for use in SQL.
     *
     * @param mixed $value The value to quote
     *
     * @return string The quoted value
     */
    private function quoteValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'" . Connection::escape((string) $value) . "'";
    }

    /**
     * Execute the query and return all results.
     *
     * @return (float|int|null|string)[][] Array of rows
     *
     * @psalm-return list<non-empty-array<string, float|int|null|string>>
     */
    public function get(): array
    {
        return Connection::fetchAll($this->toSql());
    }

    /**
     * Execute the query and return the first result.
     *
     * @return (float|int|null|string)[]|null The first row or null
     *
     * @psalm-return array<string, float|int|null|string>|null
     */
    public function first(): array|null
    {
        $this->limit(1);
        return Connection::fetchOne($this->toSql());
    }

    /**
     * Execute the query and return a single column value.
     *
     * @param string $column The column to retrieve
     *
     * @return mixed The value or null
     */
    public function value(string $column): mixed
    {
        $this->select($column);
        $row = $this->first();

        return $row[$column] ?? null;
    }

    /**
     * Get the count of matching rows.
     *
     * @param string $column The column to count (default: *)
     *
     * @return int The count
     */
    public function count(string $column = '*'): int
    {
        $this->columns = ["COUNT($column) AS cnt"];
        $row = Connection::fetchOne($this->toSql());

        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Check if any rows exist matching the query.
     *
     * @return bool True if rows exist
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Insert a new row.
     *
     * @param array<string, mixed> $data Column => value pairs to insert
     *
     * @return int|string The last insert ID
     */
    public function insert(array $data): int|string
    {
        $columns = array_keys($data);
        $values = array_map(fn($v) => $this->quoteValue($v), array_values($data));

        $sql = 'INSERT INTO ' . $this->table;
        $sql .= ' (' . implode(', ', $columns) . ')';
        $sql .= ' VALUES (' . implode(', ', $values) . ')';

        Connection::execute($sql);

        return Connection::lastInsertId();
    }

    /**
     * Insert multiple rows.
     *
     * @param array<int, array<string, mixed>> $rows Array of column => value pairs
     *
     * @return int Number of inserted rows
     */
    public function insertMany(array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        $columns = array_keys($rows[0]);
        $valueGroups = [];

        foreach ($rows as $row) {
            $values = array_map(fn($v) => $this->quoteValue($v), array_values($row));
            $valueGroups[] = '(' . implode(', ', $values) . ')';
        }

        $sql = 'INSERT INTO ' . $this->table;
        $sql .= ' (' . implode(', ', $columns) . ')';
        $sql .= ' VALUES ' . implode(', ', $valueGroups);

        return (int) Connection::execute($sql);
    }

    /**
     * Update matching rows.
     *
     * @param array<string, mixed> $data Column => value pairs to update
     *
     * @return int Number of affected rows
     */
    public function update(array $data): int
    {
        $setClauses = [];
        foreach ($data as $column => $value) {
            $setClauses[] = $column . ' = ' . $this->quoteValue($value);
        }

        $sql = 'UPDATE ' . $this->table;
        $sql .= ' SET ' . implode(', ', $setClauses);

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->compileWheres();
        }

        return (int) Connection::execute($sql);
    }

    /**
     * Delete matching rows.
     *
     * Supports WHERE, ORDER BY, and LIMIT clauses for MySQL.
     *
     * @return int Number of deleted rows
     */
    public function delete(): int
    {
        $sql = 'DELETE FROM ' . $this->table;

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->compileWheres();
        }

        // Add ORDER BY for DELETE (MySQL supports this)
        if (!empty($this->orders)) {
            $orderClauses = array_map(
                fn($order) => $order['column'] . ' ' . $order['direction'],
                $this->orders
            );
            $sql .= ' ORDER BY ' . implode(', ', $orderClauses);
        }

        // Add LIMIT for DELETE (MySQL supports this)
        if ($this->limitValue !== null) {
            $sql .= ' LIMIT ' . $this->limitValue;
        }

        return (int) Connection::execute($sql);
    }

    /**
     * Truncate the table.
     *
     * @return void
     */
    public function truncate(): void
    {
        Connection::execute('TRUNCATE TABLE ' . $this->table);
    }

    // =========================================================================
    // PREPARED STATEMENT METHODS
    // =========================================================================

    /**
     * Get the bindings for prepared statements.
     *
     * @return array<int, mixed> The parameter bindings
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Reset bindings array.
     *
     * @return void
     */
    private function resetBindings(): void
    {
        $this->bindings = [];
    }

    /**
     * Build the SELECT SQL query with placeholders for prepared statements.
     *
     * @return string The SQL query with ? placeholders
     */
    public function toSqlPrepared(): string
    {
        $this->resetBindings();

        $sql = 'SELECT ';

        if ($this->distinct) {
            $sql .= 'DISTINCT ';
        }

        $sql .= implode(', ', $this->columns);
        $sql .= ' FROM ' . $this->table;

        // Add JOINs
        foreach ($this->joins as $join) {
            $sql .= ' ' . $join['type'] . ' JOIN ' . $join['table'];
            $sql .= ' ON ' . $join['first'] . ' ' . $join['operator'] . ' ' . $join['second'];
        }

        // Add WHERE clauses
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->compileWheresPrepared();
        }

        // Add GROUP BY
        if (!empty($this->groups)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groups);
        }

        // Add ORDER BY
        if (!empty($this->orders)) {
            $orderClauses = array_map(
                fn($order) => $order['column'] . ' ' . $order['direction'],
                $this->orders
            );
            $sql .= ' ORDER BY ' . implode(', ', $orderClauses);
        }

        // Add LIMIT
        if ($this->limitValue !== null) {
            $sql .= ' LIMIT ' . $this->limitValue;
        }

        // Add OFFSET
        if ($this->offsetValue !== null) {
            $sql .= ' OFFSET ' . $this->offsetValue;
        }

        return $sql;
    }

    /**
     * Compile WHERE clauses into SQL with placeholders.
     *
     * @return string The WHERE clause SQL with ? placeholders
     */
    private function compileWheresPrepared(): string
    {
        $sql = '';

        foreach ($this->wheres as $index => $where) {
            // Add boolean connector (skip for first condition)
            if ($index > 0) {
                $sql .= ' ' . $where['boolean'] . ' ';
            }

            if ($where['operator'] === 'RAW') {
                $sql .= $where['value'];
                continue;
            }

            if (in_array($where['operator'], ['IS NULL', 'IS NOT NULL'])) {
                $sql .= $where['column'] . ' ' . $where['operator'];
                continue;
            }

            if (in_array($where['operator'], ['IN', 'NOT IN'])) {
                $placeholders = array_fill(0, count($where['value']), '?');
                $sql .= $where['column'] . ' ' . $where['operator'] . ' (' . implode(', ', $placeholders) . ')';
                foreach ($where['value'] as $v) {
                    $this->bindings[] = $v;
                }
                continue;
            }

            $sql .= $where['column'] . ' ' . $where['operator'] . ' ?';
            $this->bindings[] = $where['value'];
        }

        return $sql;
    }

    /**
     * Execute the query using prepared statements and return all results.
     *
     * @return array<int, array<string, mixed>> Array of rows
     */
    public function getPrepared(): array
    {
        $sql = $this->toSqlPrepared();
        return Connection::preparedFetchAll($sql, $this->bindings);
    }

    /**
     * Execute the query using prepared statements and return the first result.
     *
     * @return array<string, mixed>|null The first row or null
     */
    public function firstPrepared(): ?array
    {
        $this->limit(1);
        $sql = $this->toSqlPrepared();
        return Connection::preparedFetchOne($sql, $this->bindings);
    }

    /**
     * Execute the query using prepared statements and return a single column value.
     *
     * @param string $column The column to retrieve
     *
     * @return mixed The value or null
     */
    public function valuePrepared(string $column): mixed
    {
        $this->select($column);
        $row = $this->firstPrepared();

        return $row[$column] ?? null;
    }

    /**
     * Get the count of matching rows using prepared statements.
     *
     * @param string $column The column to count (default: *)
     *
     * @return int The count
     */
    public function countPrepared(string $column = '*'): int
    {
        $this->columns = ["COUNT($column) AS cnt"];
        $sql = $this->toSqlPrepared();
        $row = Connection::preparedFetchOne($sql, $this->bindings);

        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Check if any rows exist using prepared statements.
     *
     * @return bool True if rows exist
     */
    public function existsPrepared(): bool
    {
        return $this->countPrepared() > 0;
    }

    /**
     * Insert a new row using prepared statement.
     *
     * @param array<string, mixed> $data Column => value pairs to insert
     *
     * @return int|string The last insert ID
     */
    public function insertPrepared(array $data): int|string
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($data), '?');
        $values = array_values($data);

        $sql = 'INSERT INTO ' . $this->table;
        $sql .= ' (' . implode(', ', $columns) . ')';
        $sql .= ' VALUES (' . implode(', ', $placeholders) . ')';

        return Connection::preparedInsert($sql, $values);
    }

    /**
     * Insert multiple rows using prepared statement.
     *
     * @param array<int, array<string, mixed>> $rows Array of column => value pairs
     *
     * @return int Number of inserted rows
     */
    public function insertManyPrepared(array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        $columns = array_keys($rows[0]);
        $placeholderRow = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $placeholderGroups = array_fill(0, count($rows), $placeholderRow);

        $sql = 'INSERT INTO ' . $this->table;
        $sql .= ' (' . implode(', ', $columns) . ')';
        $sql .= ' VALUES ' . implode(', ', $placeholderGroups);

        $params = [];
        foreach ($rows as $row) {
            foreach (array_values($row) as $value) {
                $params[] = $value;
            }
        }

        return Connection::preparedExecute($sql, $params);
    }

    /**
     * Update matching rows using prepared statement.
     *
     * @param array<string, mixed> $data Column => value pairs to update
     *
     * @return int Number of affected rows
     */
    public function updatePrepared(array $data): int
    {
        $this->resetBindings();

        $setClauses = [];
        $setParams = [];
        foreach ($data as $column => $value) {
            $setClauses[] = $column . ' = ?';
            $setParams[] = $value;
        }

        $sql = 'UPDATE ' . $this->table;
        $sql .= ' SET ' . implode(', ', $setClauses);

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->compileWheresPrepared();
        }

        // Merge SET params with WHERE params
        $params = array_merge($setParams, $this->bindings);

        return Connection::preparedExecute($sql, $params);
    }

    /**
     * Delete matching rows using prepared statement.
     *
     * @return int Number of deleted rows
     */
    public function deletePrepared(): int
    {
        $this->resetBindings();

        $sql = 'DELETE FROM ' . $this->table;

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->compileWheresPrepared();
        }

        // Add ORDER BY for DELETE (MySQL supports this)
        if (!empty($this->orders)) {
            $orderClauses = array_map(
                fn($order) => $order['column'] . ' ' . $order['direction'],
                $this->orders
            );
            $sql .= ' ORDER BY ' . implode(', ', $orderClauses);
        }

        // Add LIMIT for DELETE (MySQL supports this)
        if ($this->limitValue !== null) {
            $sql .= ' LIMIT ' . $this->limitValue;
        }

        return Connection::preparedExecute($sql, $this->bindings);
    }
}
