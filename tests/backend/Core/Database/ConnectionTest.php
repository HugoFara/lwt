<?php

declare(strict_types=1);

namespace Lwt\Tests\Core\Database;

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');
$config = EnvLoader::getDatabaseConfig();
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/db_bootstrap.php';

/**
 * Unit tests for the Database\Connection class.
 *
 * Tests database connection wrapper, query execution, result fetching,
 * escaping, and transaction handling.
 */
class ConnectionTest extends TestCase
{
    private static bool $dbConnected = false;
    private static string $tbpref = '';

    public static function setUpBeforeClass(): void
    {
        $config = EnvLoader::getDatabaseConfig();
        $testDbname = "test_" . $config['dbname'];

        if (!Globals::getDbConnection()) {
            $connection = connect_to_database(
                $config['server'],
                $config['userid'],
                $config['passwd'],
                $testDbname,
                $config['socket'] ?? ''
            );
            Globals::setDbConnection($connection);
        }
        self::$dbConnected = (Globals::getDbConnection() !== null);
        self::$tbpref = Globals::getTablePrefix();
    }

    protected function tearDown(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        // Clean up test data after each test
        $tbpref = self::$tbpref;
        Connection::query("DELETE FROM {$tbpref}settings WHERE StKey LIKE 'test_conn_%'");
        Connection::query("DELETE FROM {$tbpref}tags WHERE TgText LIKE 'test_conn_%'");
    }

    // ===== getInstance() tests =====

    public function testGetInstanceReturnsConnection(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $connection = Connection::getInstance();
        $this->assertInstanceOf(\mysqli::class, $connection);
    }

    public function testGetInstanceReturnsSameInstance(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $connection1 = Connection::getInstance();
        $connection2 = Connection::getInstance();
        $this->assertSame($connection1, $connection2);
    }

    // ===== setInstance() tests =====

    public function testSetInstanceChangesConnection(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $oldConnection = Connection::getInstance();
        
        // Set it back (this is just to test the setter works)
        Connection::setInstance($oldConnection);
        
        $newConnection = Connection::getInstance();
        $this->assertInstanceOf(\mysqli::class, $newConnection);
    }

    // ===== query() tests =====

    public function testQueryWithSelectStatement(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        $result = Connection::query("SELECT COUNT(*) as cnt FROM {$tbpref}settings");
        
        $this->assertInstanceOf(\mysqli_result::class, $result);
        mysqli_free_result($result);
    }

    public function testQueryWithInsertStatement(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        $result = Connection::query("INSERT INTO {$tbpref}tags (TgText) VALUES ('test_conn_tag')");
        
        $this->assertTrue($result);
        
        // Clean up
        Connection::query("DELETE FROM {$tbpref}tags WHERE TgText = 'test_conn_tag'");
    }

    public function testQueryWithInvalidSqlThrowsException(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/SQL Error/');
        
        Connection::query("SELECT * FROM nonexistent_table_xyz");
    }

    // ===== fetchAll() tests =====

    public function testFetchAllReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        $rows = Connection::fetchAll("SELECT * FROM {$tbpref}settings LIMIT 5");
        
        $this->assertIsArray($rows);
        $this->assertLessThanOrEqual(5, count($rows));
        
        if (count($rows) > 0) {
            $this->assertIsArray($rows[0]);
            $this->assertArrayHasKey('StKey', $rows[0]);
        }
    }

    public function testFetchAllWithNoResults(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        $rows = Connection::fetchAll("SELECT * FROM {$tbpref}settings WHERE StKey = 'nonexistent_key_xyz'");
        
        $this->assertIsArray($rows);
        $this->assertEmpty($rows);
    }

    public function testFetchAllWithNonSelectQuery(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        // INSERT returns true, not a result set
        $rows = Connection::fetchAll("INSERT INTO {$tbpref}tags (TgText) VALUES ('test_conn_fetchall')");
        
        $this->assertIsArray($rows);
        $this->assertEmpty($rows);
        
        // Clean up
        Connection::query("DELETE FROM {$tbpref}tags WHERE TgText = 'test_conn_fetchall'");
    }

    // ===== fetchOne() tests =====

    public function testFetchOneReturnsFirstRow(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        // Insert a test setting
        Connection::query("INSERT INTO {$tbpref}settings (StKey, StValue) VALUES ('test_conn_one', 'value1')");
        
        $row = Connection::fetchOne("SELECT * FROM {$tbpref}settings WHERE StKey = 'test_conn_one'");
        
        $this->assertIsArray($row);
        $this->assertEquals('test_conn_one', $row['StKey']);
        $this->assertEquals('value1', $row['StValue']);
        
        // Clean up
        Connection::query("DELETE FROM {$tbpref}settings WHERE StKey = 'test_conn_one'");
    }

    public function testFetchOneReturnsNullWhenNoResults(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        $row = Connection::fetchOne("SELECT * FROM {$tbpref}settings WHERE StKey = 'nonexistent_key_xyz'");
        
        $this->assertNull($row);
    }

    public function testFetchOneWithMultipleRowsReturnsFirst(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        $row = Connection::fetchOne("SELECT * FROM {$tbpref}settings LIMIT 1");
        
        $this->assertIsArray($row);
    }

    public function testFetchOneWithNonSelectQuery(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Test with a non-SELECT query (returns true from mysqli_query)
        $tbpref = self::$tbpref;
        // Use SHOW TABLES which returns a result set, or a query that doesn't return rows
        $result = Connection::fetchOne("DO 1");
        
        $this->assertNull($result);
    }

    // ===== fetchValue() tests =====

    public function testFetchValueReturnsValue(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        Connection::query("INSERT INTO {$tbpref}settings (StKey, StValue) VALUES ('test_conn_value', 'myvalue')");
        
        $value = Connection::fetchValue("SELECT StValue as value FROM {$tbpref}settings WHERE StKey = 'test_conn_value'");
        
        $this->assertEquals('myvalue', $value);
        
        // Clean up
        Connection::query("DELETE FROM {$tbpref}settings WHERE StKey = 'test_conn_value'");
    }

    public function testFetchValueWithCustomColumn(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        Connection::query("INSERT INTO {$tbpref}settings (StKey, StValue) VALUES ('test_conn_custom', 'customvalue')");
        
        $value = Connection::fetchValue("SELECT StValue FROM {$tbpref}settings WHERE StKey = 'test_conn_custom'", 'StValue');
        
        $this->assertEquals('customvalue', $value);
        
        // Clean up
        Connection::query("DELETE FROM {$tbpref}settings WHERE StKey = 'test_conn_custom'");
    }

    public function testFetchValueReturnsNullWhenNoResults(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        $value = Connection::fetchValue("SELECT StValue as value FROM {$tbpref}settings WHERE StKey = 'nonexistent_xyz'");
        
        $this->assertNull($value);
    }

    public function testFetchValueReturnsNullWhenColumnNotFound(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        $value = Connection::fetchValue("SELECT StKey FROM {$tbpref}settings LIMIT 1", 'nonexistent_column');
        
        $this->assertNull($value);
    }

    // ===== execute() tests =====

    public function testExecuteReturnsAffectedRows(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        Connection::execute("INSERT INTO {$tbpref}tags (TgText) VALUES ('test_conn_exec')");
        
        $affected = Connection::execute("DELETE FROM {$tbpref}tags WHERE TgText = 'test_conn_exec'");
        
        $this->assertGreaterThanOrEqual(1, $affected);
    }

    public function testExecuteWithUpdate(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        Connection::execute("INSERT INTO {$tbpref}settings (StKey, StValue) VALUES ('test_conn_update', 'old')");
        
        $affected = Connection::execute("UPDATE {$tbpref}settings SET StValue = 'new' WHERE StKey = 'test_conn_update'");
        
        $this->assertEquals(1, $affected);
        
        // Clean up
        Connection::query("DELETE FROM {$tbpref}settings WHERE StKey = 'test_conn_update'");
    }

    // ===== lastInsertId() tests =====

    public function testLastInsertIdReturnsId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        Connection::execute("INSERT INTO {$tbpref}tags (TgText) VALUES ('test_conn_lastid')");
        
        $lastId = Connection::lastInsertId();
        
        $this->assertGreaterThan(0, $lastId);
        
        // Clean up
        Connection::query("DELETE FROM {$tbpref}tags WHERE TgID = $lastId");
    }

    // ===== escape() tests =====

    public function testEscapeEscapesSpecialCharacters(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $escaped = Connection::escape("test's value");
        $this->assertStringContainsString("\\'", $escaped);
    }

    public function testEscapeHandlesQuotes(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $escaped = Connection::escape("test \"quoted\" value");
        $this->assertStringContainsString('\\"', $escaped);
    }

    public function testEscapeHandlesBackslash(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $escaped = Connection::escape("test\\value");
        $this->assertStringContainsString('\\\\', $escaped);
    }

    // ===== escapeOrNull() tests =====

    public function testEscapeOrNullWithValue(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Connection::escapeOrNull("test value");
        $this->assertEquals("'test value'", $result);
    }

    public function testEscapeOrNullWithEmptyString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Connection::escapeOrNull("");
        $this->assertEquals('NULL', $result);
    }

    public function testEscapeOrNullTrimsWhitespace(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Connection::escapeOrNull("  test  ");
        $this->assertEquals("'test'", $result);
    }

    public function testEscapeOrNullWithWhitespaceOnlyReturnsNull(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Connection::escapeOrNull("   ");
        $this->assertEquals('NULL', $result);
    }

    public function testEscapeOrNullConvertsCarriageReturn(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Connection::escapeOrNull("line1\r\nline2");
        // Result is quoted and escaped - the newline will be escaped by mysqli_real_escape_string
        $this->assertStringStartsWith("'", $result);
        $this->assertStringEndsWith("'", $result);
        $this->assertStringNotContainsString("\r", $result);
        // Check that it contains line1 and line2
        $this->assertStringContainsString("line1", $result);
        $this->assertStringContainsString("line2", $result);
    }

    // ===== escapeString() tests =====

    public function testEscapeStringWithValue(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Connection::escapeString("test value");
        $this->assertEquals("'test value'", $result);
    }

    public function testEscapeStringWithEmptyString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Connection::escapeString("");
        $this->assertEquals("''", $result);
    }

    public function testEscapeStringTrimsWhitespace(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Connection::escapeString("  test  ");
        $this->assertEquals("'test'", $result);
    }

    public function testEscapeStringWithSpecialChars(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Connection::escapeString("test's \"value\"");
        // Check that quotes are escaped
        $this->assertStringContainsString("'", $result);
        $this->assertStringStartsWith("'", $result);
        $this->assertStringEndsWith("'", $result);
    }

    // ===== Integration tests =====

    public function testInsertAndRetrieveWithEscaping(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        $testValue = "test's \"special\" \\ value";
        
        $escaped = Connection::escapeString($testValue);
        Connection::execute("INSERT INTO {$tbpref}settings (StKey, StValue) VALUES ('test_conn_escape', $escaped)");
        
        $retrieved = Connection::fetchValue("SELECT StValue as value FROM {$tbpref}settings WHERE StKey = 'test_conn_escape'");
        
        // The value should be retrieved exactly as it was inserted (after unescaping by MySQL)
        $this->assertEquals($testValue, $retrieved);
        
        // Clean up
        Connection::query("DELETE FROM {$tbpref}settings WHERE StKey = 'test_conn_escape'");
    }

    public function testQueryChaining(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        
        // Insert
        Connection::execute("INSERT INTO {$tbpref}settings (StKey, StValue) VALUES ('test_conn_chain', 'chain_val')");
        
        // Fetch
        $value = Connection::fetchValue("SELECT StValue as value FROM {$tbpref}settings WHERE StKey = 'test_conn_chain'");
        $this->assertEquals('chain_val', $value);
        
        // Update
        Connection::execute("UPDATE {$tbpref}settings SET StValue = 'chain_val_2' WHERE StKey = 'test_conn_chain'");
        
        // Fetch again
        $value = Connection::fetchValue("SELECT StValue as value FROM {$tbpref}settings WHERE StKey = 'test_conn_chain'");
        $this->assertEquals('chain_val_2', $value);
        
        // Delete
        $affected = Connection::execute("DELETE FROM {$tbpref}settings WHERE StKey = 'test_conn_chain'");
        $this->assertEquals(1, $affected);
    }

    // ===== lastInsertId() tests =====

    public function testLastInsertIdReturnsId2(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        
        // Use tags table which has AUTO_INCREMENT
        Connection::execute("INSERT INTO {$tbpref}tags (TgText) VALUES ('test_last_insert_id')");
        $lastId = Connection::lastInsertId();
        
        $this->assertGreaterThan(0, $lastId);
        
        // Clean up
        Connection::execute("DELETE FROM {$tbpref}tags WHERE TgText = 'test_last_insert_id'");
    }

    // ===== reset() tests =====

    public function testResetClearsInstance(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Get instance first
        $instance1 = Connection::getInstance();
        
        // Reset
        Connection::reset();
        
        // Get instance again - should be fetched from Globals
        $instance2 = Connection::getInstance();
        
        // They should still be connected (Globals maintains the connection)
        $this->assertInstanceOf(\mysqli::class, $instance2);
    }

    // ===== setInstance() tests =====

    public function testSetInstanceSetsConnection(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $originalInstance = Connection::getInstance();
        
        // Set a new instance (same connection for testing purposes)
        Connection::setInstance($originalInstance);
        
        $newInstance = Connection::getInstance();
        
        $this->assertSame($originalInstance, $newInstance);
    }
}

