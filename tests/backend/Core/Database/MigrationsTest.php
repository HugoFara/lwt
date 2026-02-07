<?php

declare(strict_types=1);

namespace Lwt\Tests\Core\Database;

require_once __DIR__ . '/../../../../src/Shared/Infrastructure/Bootstrap/EnvLoader.php';

use Lwt\Shared\Infrastructure\Bootstrap\EnvLoader;
use Lwt\Shared\Infrastructure\Globals;
use Lwt\Shared\Infrastructure\Database\Migrations;
use Lwt\Shared\Infrastructure\Database\Configuration;
use Lwt\Shared\Infrastructure\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../../src/Shared/Infrastructure/Bootstrap/db_bootstrap.php';

/**
 * Unit tests for the Database\Migrations class.
 *
 * Tests database migrations and initialization utilities.
 */
class MigrationsTest extends TestCase
{
    private static bool $dbConnected = false;

    public static function setUpBeforeClass(): void
    {
        $config = EnvLoader::getDatabaseConfig();
        $testDbname = "test_" . $config['dbname'];

        if (!Globals::getDbConnection()) {
            $connection = Configuration::connect(
                $config['server'],
                $config['userid'],
                $config['passwd'],
                $testDbname,
                $config['socket'] ?? ''
            );
            Globals::setDbConnection($connection);
        }
        self::$dbConnected = (Globals::getDbConnection() !== null);
    }

    // ===== prefixQuery() tests =====

    /**
     * @dataProvider providerPrefixQueryInsert
     */
    public function testPrefixQueryInsert(string $sql, string $prefix, string $expected): void
    {
        $result = Migrations::prefixQuery($sql, $prefix);
        $this->assertEquals($expected, $result);
    }

    public static function providerPrefixQueryInsert(): array
    {
        return [
            'INSERT INTO with prefix' => [
                "INSERT INTO languages (LgName) VALUES ('Test');",
                "prefix_",
                "INSERT INTO prefix_languages (LgName) VALUES ('Test');"
            ],
            'INSERT INTO with empty prefix' => [
                "INSERT INTO languages (LgName) VALUES ('Test');",
                "",
                "INSERT INTO languages (LgName) VALUES ('Test');"
            ],
            'INSERT INTO multiple columns' => [
                "INSERT INTO words (WoLgID, WoText) VALUES (1, 'test');",
                "lwt_",
                "INSERT INTO lwt_words (WoLgID, WoText) VALUES (1, 'test');"
            ],
        ];
    }

    /**
     * @dataProvider providerPrefixQueryCreateTable
     */
    public function testPrefixQueryCreateTable(string $sql, string $prefix, string $expected): void
    {
        $result = Migrations::prefixQuery($sql, $prefix);
        $this->assertEquals($expected, $result);
    }

    public static function providerPrefixQueryCreateTable(): array
    {
        return [
            'CREATE TABLE basic' => [
                "CREATE TABLE languages (id INT);",
                "test_",
                "CREATE TABLE test_languages (id INT);"
            ],
            'CREATE TABLE with backticks' => [
                "CREATE TABLE `users` (id INT);",
                "pre_",
                "CREATE TABLE `pre_users` (id INT);"
            ],
            'CREATE TABLE IF NOT EXISTS' => [
                "CREATE TABLE IF NOT EXISTS languages (id INT);",
                "lwt_",
                "CREATE TABLE IF NOT EXISTS lwt_languages (id INT);"
            ],
            'CREATE TABLE IF NOT EXISTS with backticks' => [
                "CREATE TABLE IF NOT EXISTS `texts` (id INT);",
                "app_",
                "CREATE TABLE IF NOT EXISTS `app_texts` (id INT);"
            ],
            'CREATE TABLE with empty prefix' => [
                "CREATE TABLE users (id INT);",
                "",
                "CREATE TABLE users (id INT);"
            ],
        ];
    }

    /**
     * @dataProvider providerPrefixQueryAlterTable
     */
    public function testPrefixQueryAlterTable(string $sql, string $prefix, string $expected): void
    {
        $result = Migrations::prefixQuery($sql, $prefix);
        $this->assertEquals($expected, $result);
    }

    public static function providerPrefixQueryAlterTable(): array
    {
        return [
            'ALTER TABLE basic' => [
                "ALTER TABLE languages ADD COLUMN name VARCHAR(255);",
                "pre_",
                "ALTER TABLE pre_languages ADD COLUMN name VARCHAR(255);"
            ],
            'ALTER TABLE with backticks' => [
                "ALTER TABLE `users` DROP COLUMN email;",
                "test_",
                "ALTER TABLE `test_users` DROP COLUMN email;"
            ],
            'ALTER TABLE with empty prefix' => [
                "ALTER TABLE settings MODIFY StValue TEXT;",
                "",
                "ALTER TABLE settings MODIFY StValue TEXT;"
            ],
        ];
    }

    /**
     * @dataProvider providerPrefixQueryDropTable
     */
    public function testPrefixQueryDropTable(string $sql, string $prefix, string $expected): void
    {
        $result = Migrations::prefixQuery($sql, $prefix);
        $this->assertEquals($expected, $result);
    }

    public static function providerPrefixQueryDropTable(): array
    {
        return [
            'DROP TABLE basic' => [
                "DROP TABLE temp_data;",
                "pre_",
                "DROP TABLE pre_temp_data;"
            ],
            'DROP TABLE IF EXISTS' => [
                "DROP TABLE IF EXISTS temp_data;",
                "lwt_",
                "DROP TABLE IF EXISTS lwt_temp_data;"
            ],
            'DROP TABLE with backticks' => [
                "DROP TABLE `old_table`;",
                "test_",
                "DROP TABLE `test_old_table`;"
            ],
        ];
    }

    public function testPrefixQueryNonMatchingStatement(): void
    {
        // SELECT statements should not be modified
        $sql = "SELECT * FROM languages;";
        $result = Migrations::prefixQuery($sql, "prefix_");
        $this->assertEquals($sql, $result, 'Non-matching statements should be unchanged');
    }

    public function testPrefixQueryUpdateStatement(): void
    {
        // UPDATE statements should not be modified by prefixQuery
        $sql = "UPDATE languages SET LgName = 'Test';";
        $result = Migrations::prefixQuery($sql, "prefix_");
        $this->assertEquals($sql, $result, 'UPDATE statements should be unchanged');
    }

    public function testPrefixQueryDeleteStatement(): void
    {
        // DELETE statements should not be modified by prefixQuery
        $sql = "DELETE FROM languages WHERE LgID = 1;";
        $result = Migrations::prefixQuery($sql, "prefix_");
        $this->assertEquals($sql, $result, 'DELETE statements should be unchanged');
    }

    public function testPrefixQueryComplexCreateTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `languages` (
            LgID tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
            LgName varchar(40) NOT NULL,
            PRIMARY KEY (LgID)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

        $result = Migrations::prefixQuery($sql, "test_");

        $this->assertStringContainsString("CREATE TABLE IF NOT EXISTS `test_languages`", $result);
    }

    // ===== reparseAllTexts() tests =====

    public function testReparseAllTextsRuns(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Clean up any texts that reference non-existent languages
        // to avoid "Language data not found" errors
        Connection::query("DELETE FROM word_occurrences WHERE Ti2TxID IN (
            SELECT TxID FROM texts WHERE TxLgID NOT IN (SELECT LgID FROM languages)
        )");
        Connection::query("DELETE FROM sentences WHERE SeTxID IN (
            SELECT TxID FROM texts WHERE TxLgID NOT IN (SELECT LgID FROM languages)
        )");
        Connection::query("DELETE FROM texts WHERE TxLgID NOT IN (SELECT LgID FROM languages)");

        // This function truncates and rebuilds text data
        // Should run without error on empty/minimal database
        Migrations::reparseAllTexts();
        $this->assertTrue(true, 'reparseAllTexts should complete without error');
    }

    // ===== update() tests =====

    public function testUpdateRuns(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // The update function checks and updates database schema
        // Should run without error on a properly initialized database
        Migrations::update();
        $this->assertTrue(true, 'update should complete without error');
    }

    // ===== checkAndUpdate() tests =====

    public function testCheckAndUpdateRuns(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // This is the main entry point for database initialization
        // Should run without error
        Migrations::checkAndUpdate();
        $this->assertTrue(true, 'checkAndUpdate should complete without error');
    }

    public function testCheckAndUpdateEnsuresTablesExist(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        Migrations::checkAndUpdate();

        // Verify core tables exist
        $tables = [
            'languages', 'texts', 'words', 'sentences',
            'settings', 'tags', 'text_tags', 'word_occurrences'
        ];

        foreach ($tables as $table) {
            $result = Connection::query("SHOW TABLES LIKE '{$table}'");
            $exists = mysqli_num_rows($result) > 0;
            mysqli_free_result($result);
            $this->assertTrue($exists, "Table {$table} should exist after checkAndUpdate");
        }
    }

    public function testCheckAndUpdateMigrationsTable(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        Migrations::checkAndUpdate();

        // Verify _migrations table exists (without prefix)
        $result = Connection::query("SHOW TABLES LIKE '_migrations'");
        $exists = mysqli_num_rows($result) > 0;
        mysqli_free_result($result);
        $this->assertTrue($exists, "_migrations table should exist");
    }

    // ===== Edge cases and complex scenarios =====

    public function testPrefixQueryWithSpecialCharacters(): void
    {
        // Test with table name that has underscores
        $sql = "CREATE TABLE my_table_name (id INT);";
        $result = Migrations::prefixQuery($sql, "prefix_");
        $this->assertEquals("CREATE TABLE prefix_my_table_name (id INT);", $result);
    }

    public function testPrefixQueryWithNumericPrefix(): void
    {
        // Prefix with numbers
        $sql = "CREATE TABLE users (id INT);";
        $result = Migrations::prefixQuery($sql, "app123_");
        $this->assertEquals("CREATE TABLE app123_users (id INT);", $result);
    }

    public function testPrefixQueryCaseInsensitive(): void
    {
        // prefixQuery should handle SQL keywords case-insensitively
        $sql = "create table users (id INT);";
        $result = Migrations::prefixQuery($sql, "pre_");
        $this->assertStringContainsString("pre_users", $result, 'Lowercase CREATE TABLE should be prefixed');

        $sql = "Create Table users (id INT);";
        $result = Migrations::prefixQuery($sql, "pre_");
        $this->assertStringContainsString("pre_users", $result, 'Mixed case CREATE TABLE should be prefixed');

        $sql = "insert into languages (LgName) VALUES ('Test');";
        $result = Migrations::prefixQuery($sql, "pre_");
        $this->assertStringContainsString("pre_languages", $result, 'Lowercase INSERT INTO should be prefixed');

        $sql = "drop table IF EXISTS temp_data;";
        $result = Migrations::prefixQuery($sql, "pre_");
        $this->assertStringContainsString("pre_temp_data", $result, 'Lowercase DROP TABLE should be prefixed');
    }

    public function testPrefixQueryInsertMultipleValues(): void
    {
        $sql = "INSERT INTO languages (LgID, LgName) VALUES (1, 'English'), (2, 'French');";
        $result = Migrations::prefixQuery($sql, "test_");
        $this->assertEquals(
            "INSERT INTO test_languages (LgID, LgName) VALUES (1, 'English'), (2, 'French');",
            $result
        );
    }

    public function testCheckAndUpdateSetsLastScorecalc(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Clear lastscorecalc to force recalculation
        Connection::query("DELETE FROM settings WHERE StKey = 'lastscorecalc'");

        Migrations::checkAndUpdate();

        // Verify lastscorecalc was set
        $result = Connection::fetchValue(
            "SELECT StValue as value FROM settings WHERE StKey = 'lastscorecalc'"
        );

        $this->assertNotEmpty($result, 'lastscorecalc should be set after checkAndUpdate');
        // Should be today's date
        $this->assertEquals(date('Y-m-d'), $result, 'lastscorecalc should be today');
    }

    public function testPrefixQueryPreservesRestOfStatement(): void
    {
        // Ensure the rest of the SQL statement is preserved correctly
        $sql = "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;";

        $result = Migrations::prefixQuery($sql, "app_");

        $this->assertStringContainsString("CREATE TABLE app_users", $result);
        $this->assertStringContainsString("AUTO_INCREMENT PRIMARY KEY", $result);
        $this->assertStringContainsString("VARCHAR(100) NOT NULL DEFAULT ''", $result);
        $this->assertStringContainsString("ENGINE=InnoDB", $result);
    }

    public function testUpdateSetsDbversion(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        Migrations::update();

        // Verify dbversion is set
        $result = Connection::fetchValue(
            "SELECT StValue as value FROM settings WHERE StKey = 'dbversion'"
        );

        $this->assertNotEmpty($result, 'dbversion should be set after update');
        // Should match current version format (vXXXYYYZZZ)
        $this->assertMatchesRegularExpression('/^v\d{9}$/', $result, 'dbversion should match version format');
    }

    // ===== New migration tracking tests =====

    public function testGetMigrationFilesReturnsSortedList(): void
    {
        $files = Migrations::getMigrationFiles();

        $this->assertIsArray($files);
        $this->assertNotEmpty($files, 'Should find migration files in db/migrations/');

        // Verify files are sorted
        $sortedFiles = $files;
        sort($sortedFiles);
        $this->assertEquals($sortedFiles, $files, 'Migration files should be sorted');

        // Verify all entries are SQL files
        foreach ($files as $file) {
            $this->assertStringEndsWith('.sql', $file, 'All migration files should be .sql');
        }
    }

    public function testGetAppliedMigrationsReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $applied = Migrations::getAppliedMigrations();

        $this->assertIsArray($applied);
    }

    public function testRecordMigrationTracksNewMigration(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $testFilename = 'test_migration_' . time() . '.sql';

        // Record a test migration
        Migrations::recordMigration($testFilename);

        // Verify it was recorded
        $applied = Migrations::getAppliedMigrations();
        $this->assertContains($testFilename, $applied, 'Recorded migration should appear in applied list');

        // Clean up
        Connection::preparedExecute("DELETE FROM _migrations WHERE filename = ?", [$testFilename]);
    }

    public function testRecordMigrationIsIdempotent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $testFilename = 'test_idempotent_' . time() . '.sql';

        // Record the same migration twice
        Migrations::recordMigration($testFilename);
        Migrations::recordMigration($testFilename);

        // Should not throw an error and should only have one entry
        $count = Connection::preparedFetchValue(
            "SELECT COUNT(*) as value FROM _migrations WHERE filename = ?",
            [$testFilename]
        );
        $this->assertEquals(1, $count, 'Recording the same migration twice should result in one entry');

        // Clean up
        Connection::preparedExecute("DELETE FROM _migrations WHERE filename = ?", [$testFilename]);
    }

    public function testUpgradeMigrationsTableAddsAppliedAtColumn(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Run upgrade (should be idempotent)
        Migrations::upgradeMigrationsTable();

        // Verify applied_at column exists
        $dbname = Globals::getDatabaseName();
        $columns = Connection::preparedFetchAll(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = '_migrations'",
            [$dbname]
        );
        $columnNames = array_column($columns, 'COLUMN_NAME');

        $this->assertContains('applied_at', $columnNames, '_migrations should have applied_at column');
    }

    public function testMigrationsOnlyRunOnce(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Get currently applied migrations
        $appliedBefore = Migrations::getAppliedMigrations();

        // Run update again
        Migrations::update();

        // Applied migrations should be the same (no new runs)
        $appliedAfter = Migrations::getAppliedMigrations();

        $this->assertEquals(
            count($appliedBefore),
            count($appliedAfter),
            'Running update twice should not add new migration entries'
        );
    }
}
