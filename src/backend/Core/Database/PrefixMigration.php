<?php declare(strict_types=1);
/**
 * Prefix to User Migration - Converts prefixed table sets to user_id-based system.
 *
 * This class handles the migration of data from the old prefix-based multi-user
 * system to the new user_id column-based system.
 *
 * PHP version 8.1
 *
 * @category Database
 * @package  Lwt\Database
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Database;

use Lwt\Core\Globals;
use Lwt\Services\TableSetService;

/**
 * Handles migration from prefix-based to user_id-based multi-user system.
 *
 * @category Database
 * @package  Lwt\Database
 * @since    3.0.0
 */
class PrefixMigration
{
    /**
     * Tables that contain user data and need user_id assignment.
     * Maps table name to its user_id column name.
     */
    private const USER_TABLES = [
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
     * Tables with foreign keys that reference primary tables.
     * These need ID mapping during migration.
     */
    private const DEPENDENT_TABLES = [
        'sentences' => ['SeTxID' => 'texts', 'SeLgID' => 'languages'],
        'textitems2' => ['Ti2TxID' => 'texts', 'Ti2LgID' => 'languages', 'Ti2SeID' => 'sentences'],
        'wordtags' => ['WtWoID' => 'words', 'WtTgID' => 'tags'],
        'texttags' => ['TtTxID' => 'texts', 'TtT2ID' => 'tags2'],
        'archtexttags' => ['AgAtID' => 'archivedtexts', 'AgT2ID' => 'tags2'],
        'feedlinks' => ['FlNfID' => 'newsfeeds'],
    ];

    /**
     * Migration log entries.
     *
     * @var array<int|string, mixed>
     */
    private array $log = [];

    /**
     * Whether to output progress messages.
     *
     * @var bool
     */
    private bool $verbose;

    /**
     * Constructor.
     *
     * @param bool $verbose Whether to output progress messages
     */
    public function __construct(bool $verbose = false)
    {
        $this->verbose = $verbose;
    }

    /**
     * Run the complete migration process.
     *
     * @param bool $dropAfterMigration Whether to drop prefixed tables after migration
     *
     * @return array{success: bool, prefixes_migrated: int, errors: string[]}
     */
    public function migrate(bool $dropAfterMigration = false): array
    {
        $errors = [];
        $prefixesMigrated = 0;

        // Get all prefixed table sets
        $prefixes = TableSetService::getAllPrefixes();

        if (empty($prefixes)) {
            $this->log('No prefixed table sets found. Nothing to migrate.');
            return [
                'success' => true,
                'prefixes_migrated' => 0,
                'errors' => []
            ];
        }

        $this->log('Found ' . count($prefixes) . ' prefixed table set(s): ' . implode(', ', $prefixes));

        foreach ($prefixes as $prefix) {
            try {
                $this->migratePrefix($prefix, $dropAfterMigration);
                $prefixesMigrated++;
            } catch (\Exception $e) {
                $errors[] = "Failed to migrate prefix '{$prefix}': " . $e->getMessage();
            }
        }

        return [
            'success' => empty($errors),
            'prefixes_migrated' => $prefixesMigrated,
            'errors' => $errors
        ];
    }

    /**
     * Migrate a single prefixed table set to the user_id system.
     *
     * @param string $prefix              The table prefix (without trailing underscore)
     * @param bool   $dropAfterMigration  Whether to drop prefixed tables after migration
     *
     * @return int The user ID created for this prefix
     *
     * @throws \RuntimeException If migration fails
     *
     * @psalm-suppress PossiblyUnusedReturnValue - Return value useful for callers
     */
    public function migratePrefix(string $prefix, bool $dropAfterMigration = false): int
    {
        $this->log("Starting migration for prefix: {$prefix}");

        // Check if already migrated
        if ($this->isPrefixMigrated($prefix)) {
            $this->log("Prefix '{$prefix}' already migrated. Skipping.");
            return $this->getMigratedUserId($prefix);
        }

        // Create user for this prefix
        $userId = $this->createUserForPrefix($prefix);
        $this->log("Created user ID {$userId} for prefix '{$prefix}'");

        // Store ID mappings for dependent tables
        $idMappings = [];

        // Migrate primary tables (those with user_id columns)
        foreach (self::USER_TABLES as $table => $userIdColumn) {
            $idMappings[$table] = $this->migrateTable($prefix, $table, $userIdColumn, $userId);
        }

        // Migrate dependent tables (junction tables, etc.)
        foreach (self::DEPENDENT_TABLES as $table => $foreignKeys) {
            $this->migrateDependentTable($prefix, $table, $foreignKeys, $idMappings);
        }

        // Log successful migration
        $this->logMigration($prefix, $userId);

        // Drop old prefixed tables if requested
        if ($dropAfterMigration) {
            $this->dropPrefixedTables($prefix);
        }

        $this->log("Completed migration for prefix: {$prefix}");

        return $userId;
    }

    /**
     * Check if a prefix has already been migrated.
     *
     * @param string $prefix The prefix to check
     *
     * @return bool True if already migrated
     */
    private function isPrefixMigrated(string $prefix): bool
    {
        $result = Connection::preparedFetchValue(
            "SELECT COUNT(*) AS value FROM _prefix_migration_log WHERE prefix = ?",
            [$prefix]
        );
        return (int)$result > 0;
    }

    /**
     * Get the user ID for an already-migrated prefix.
     *
     * @param string $prefix The prefix
     *
     * @return int The user ID
     */
    private function getMigratedUserId(string $prefix): int
    {
        return (int)Connection::preparedFetchValue(
            "SELECT user_id AS value FROM _prefix_migration_log WHERE prefix = ?",
            [$prefix]
        );
    }

    /**
     * Create a user account for a table set prefix.
     *
     * @param string $prefix The prefix (will be used as username)
     *
     * @return int The created user's ID
     */
    private function createUserForPrefix(string $prefix): int
    {
        // Sanitize prefix for username
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $prefix);
        if (empty($username)) {
            $username = 'user_' . substr(md5($prefix), 0, 8);
        }

        // Check if username already exists
        $existingId = Connection::preparedFetchValue(
            "SELECT UsID AS value FROM users WHERE UsUsername = ?",
            [$username]
        );

        if ($existingId !== null) {
            // Username exists, try with a suffix
            $suffix = 1;
            while (true) {
                $newUsername = $username . '_' . $suffix;
                $existingId = Connection::preparedFetchValue(
                    "SELECT UsID AS value FROM users WHERE UsUsername = ?",
                    [$newUsername]
                );
                if ($existingId === null) {
                    $username = $newUsername;
                    break;
                }
                $suffix++;
            }
        }

        // Create the user
        Connection::preparedExecute(
            "INSERT INTO users (UsUsername, UsEmail, UsRole, UsIsActive)
             VALUES (?, ?, 'user', 1)",
            [$username, $username . '@migrated.local']
        );

        return (int)Connection::lastInsertId();
    }

    /**
     * Migrate a primary table from prefix to user_id.
     *
     * @param string $prefix       The table prefix
     * @param string $table        The table name (without prefix)
     * @param string $userIdColumn The user ID column name in the target table
     * @param int    $userId       The user ID to assign
     *
     * @return array<int, int> Mapping of old IDs to new IDs
     */
    private function migrateTable(
        string $prefix,
        string $table,
        string $userIdColumn,
        int $userId
    ): array {
        $sourceTable = $prefix . '_' . $table;
        $idMapping = [];

        // Check if source table exists
        if (!$this->tableExists($sourceTable)) {
            $this->log("Source table '{$sourceTable}' does not exist. Skipping.");
            return $idMapping;
        }

        // Get primary key column for this table
        $pkColumn = $this->getPrimaryKeyColumn($table);

        // Get all rows from source table
        $rows = Connection::fetchAll("SELECT * FROM `{$sourceTable}`");

        $this->log("Migrating {$table}: " . count($rows) . " rows");

        foreach ($rows as $row) {
            $oldId = $row[$pkColumn] ?? null;

            // Remove the primary key from the data (will be auto-generated)
            unset($row[$pkColumn]);

            // Add user_id
            $row[$userIdColumn] = $userId;

            // Build insert query
            $columns = array_keys($row);
            $placeholders = array_fill(0, count($columns), '?');

            $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`)
                    VALUES (" . implode(', ', $placeholders) . ")";

            try {
                Connection::preparedExecute($sql, array_values($row));
                $newId = Connection::lastInsertId();

                if ($oldId !== null) {
                    $idMapping[(int)$oldId] = (int)$newId;
                }
            } catch (\Exception $e) {
                $this->log("Warning: Failed to insert row into {$table}: " . $e->getMessage());
            }
        }

        return $idMapping;
    }

    /**
     * Migrate a dependent table (junction table) using ID mappings.
     *
     * @param string               $prefix      The table prefix
     * @param string               $table       The table name
     * @param array<string,string> $foreignKeys Map of FK column to parent table
     * @param array<string,array>  $idMappings  ID mappings from parent table migrations
     *
     * @return void
     */
    private function migrateDependentTable(
        string $prefix,
        string $table,
        array $foreignKeys,
        array $idMappings
    ): void {
        $sourceTable = $prefix . '_' . $table;

        if (!$this->tableExists($sourceTable)) {
            $this->log("Source table '{$sourceTable}' does not exist. Skipping.");
            return;
        }

        $rows = Connection::fetchAll("SELECT * FROM `{$sourceTable}`");
        $this->log("Migrating {$table}: " . count($rows) . " rows");

        foreach ($rows as $row) {
            // Remap foreign key IDs
            $valid = true;
            foreach ($foreignKeys as $fkColumn => $parentTable) {
                if (isset($row[$fkColumn]) && isset($idMappings[$parentTable])) {
                    $oldFkId = (int)$row[$fkColumn];
                    if (isset($idMappings[$parentTable][$oldFkId])) {
                        $row[$fkColumn] = $idMappings[$parentTable][$oldFkId];
                    } else {
                        // FK references an ID that wasn't migrated (orphaned data)
                        $valid = false;
                        break;
                    }
                }
            }

            if (!$valid) {
                continue; // Skip orphaned rows
            }

            // Get primary key column(s) and remove from data
            $pkColumns = $this->getPrimaryKeyColumns($table);
            foreach ($pkColumns as $pk) {
                if (!in_array($pk, array_keys($foreignKeys))) {
                    unset($row[$pk]);
                }
            }

            // Build insert query
            $columns = array_keys($row);
            $placeholders = array_fill(0, count($columns), '?');

            $sql = "INSERT IGNORE INTO `{$table}` (`" . implode('`, `', $columns) . "`)
                    VALUES (" . implode(', ', $placeholders) . ")";

            try {
                Connection::preparedExecute($sql, array_values($row));
            } catch (\Exception $e) {
                // Ignore duplicate key errors for junction tables
                if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                    $this->log("Warning: Failed to insert row into {$table}: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Check if a table exists in the database.
     *
     * @param string $tableName The table name
     *
     * @return bool True if table exists
     */
    private function tableExists(string $tableName): bool
    {
        $dbName = Globals::getDatabaseName();
        $result = Connection::preparedFetchValue(
            "SELECT COUNT(*) AS value
             FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?",
            [$dbName, $tableName]
        );
        return (int)$result > 0;
    }

    /**
     * Get the primary key column for a table.
     *
     * @param string $table The table name
     *
     * @return string The primary key column name
     */
    private function getPrimaryKeyColumn(string $table): string
    {
        $pkMap = [
            'languages' => 'LgID',
            'texts' => 'TxID',
            'archivedtexts' => 'AtID',
            'words' => 'WoID',
            'tags' => 'TgID',
            'tags2' => 'T2ID',
            'newsfeeds' => 'NfID',
            'settings' => 'StKey',
            'sentences' => 'SeID',
            'feedlinks' => 'FlID',
        ];

        return $pkMap[$table] ?? 'id';
    }

    /**
     * Get all primary key columns for a table.
     *
     * @param string $table The table name
     *
     * @return string[] Primary key column names
     */
    private function getPrimaryKeyColumns(string $table): array
    {
        $pkMap = [
            'wordtags' => ['WtWoID', 'WtTgID'],
            'texttags' => ['TtTxID', 'TtT2ID'],
            'archtexttags' => ['AgAtID', 'AgT2ID'],
            'textitems2' => ['Ti2TxID', 'Ti2Order', 'Ti2WordCount'],
        ];

        return $pkMap[$table] ?? [$this->getPrimaryKeyColumn($table)];
    }

    /**
     * Log a migration to the tracking table.
     *
     * @param string $prefix The prefix that was migrated
     * @param int    $userId The user ID created
     *
     * @return void
     */
    private function logMigration(string $prefix, int $userId): void
    {
        $tableCount = count(self::USER_TABLES) + count(self::DEPENDENT_TABLES);

        Connection::preparedExecute(
            "INSERT INTO _prefix_migration_log (prefix, user_id, tables_migrated)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), tables_migrated = VALUES(tables_migrated)",
            [$prefix, $userId, $tableCount]
        );
    }

    /**
     * Drop all prefixed tables for a given prefix.
     *
     * @param string $prefix The prefix
     *
     * @return void
     */
    private function dropPrefixedTables(string $prefix): void
    {
        $allTables = array_merge(array_keys(self::USER_TABLES), array_keys(self::DEPENDENT_TABLES));
        // Also include temp tables
        $allTables[] = 'temptextitems';
        $allTables[] = 'tempwords';

        foreach ($allTables as $table) {
            $prefixedTable = $prefix . '_' . $table;
            if ($this->tableExists($prefixedTable)) {
                Connection::execute("DROP TABLE `{$prefixedTable}`");
                $this->log("Dropped table: {$prefixedTable}");
            }
        }
    }

    /**
     * Output a log message.
     *
     * @param string $message The message
     *
     * @return void
     */
    private function log(string $message): void
    {
        $this->log[] = $message;
        if ($this->verbose) {
            echo "[PrefixMigration] " . $message . "\n";
        }
    }

    /**
     * Get all log messages.
     *
     * @return string[] Log messages
     */
    public function getLog(): array
    {
        return $this->log;
    }

    /**
     * Get migration status for all prefixes.
     *
     * @return array<string, array{user_id: int, tables_migrated: int, migrated_at: string}>
     */
    public function getMigrationStatus(): array
    {
        $rows = Connection::fetchAll(
            "SELECT prefix, user_id, tables_migrated, migrated_at
             FROM _prefix_migration_log"
        );

        $status = [];
        foreach ($rows as $row) {
            $prefix = (string)$row['prefix'];
            $status[$prefix] = [
                'user_id' => (int)$row['user_id'],
                'tables_migrated' => (int)$row['tables_migrated'],
                'migrated_at' => (string)($row['migrated_at'] ?? '')
            ];
        }

        return $status;
    }
}
