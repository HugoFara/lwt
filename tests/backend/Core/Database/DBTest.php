<?php declare(strict_types=1);
namespace Lwt\Tests\Core\Database;

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\DB;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/db_bootstrap.php';

/**
 * Unit tests for the Database\DB facade class.
 *
 * Tests the simplified database interface, query builder access,
 * raw query execution, and convenience methods.
 */
class DBTest extends TestCase
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

    protected function tearDown(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        // Clean up test data
        Connection::query("DELETE FROM settings WHERE StKey LIKE 'test_db_%'");
        Connection::query("DELETE FROM tags WHERE TgText LIKE 'test_db_%'");
    }

    // ===== table() tests =====

    public function testTableReturnsQueryBuilder(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $builder = DB::table('settings');
        $this->assertInstanceOf(\Lwt\Database\QueryBuilder::class, $builder);
    }

    public function testTableBuilderWorks(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $results = DB::table('settings')->limit(5)->get();
        $this->assertIsArray($results);
        $this->assertLessThanOrEqual(5, count($results));
    }

    // ===== query() tests =====

    public function testQueryWithSelect(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = DB::query("SELECT * FROM settings LIMIT 1");

        $this->assertInstanceOf(\mysqli_result::class, $result);
        mysqli_free_result($result);
    }

    public function testQueryWithInsert(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = DB::query("INSERT INTO tags (TgText) VALUES ('test_db_query')");

        $this->assertTrue($result);

        // Clean up
        Connection::query("DELETE FROM tags WHERE TgText = 'test_db_query'");
    }

    // ===== fetchAll() tests =====

    public function testFetchAllReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $rows = DB::fetchAll("SELECT * FROM settings LIMIT 3");

        $this->assertIsArray($rows);
        $this->assertLessThanOrEqual(3, count($rows));
    }

    public function testFetchAllWithNoResults(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $rows = DB::fetchAll("SELECT * FROM settings WHERE StKey = 'nonexistent_xyz'");

        $this->assertIsArray($rows);
        $this->assertEmpty($rows);
    }

    // ===== fetchOne() tests =====

    public function testFetchOneReturnsRow(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        DB::execute("INSERT INTO settings (StKey, StValue) VALUES ('test_db_one', 'value1')");

        $row = DB::fetchOne("SELECT * FROM settings WHERE StKey = 'test_db_one'");

        $this->assertIsArray($row);
        $this->assertEquals('test_db_one', $row['StKey']);

        // Clean up
        Connection::query("DELETE FROM settings WHERE StKey = 'test_db_one'");
    }

    public function testFetchOneReturnsNull(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $row = DB::fetchOne("SELECT * FROM settings WHERE StKey = 'nonexistent_xyz'");

        $this->assertNull($row);
    }

    // ===== fetchValue() tests =====

    public function testFetchValueReturnsValue(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        DB::execute("INSERT INTO settings (StKey, StValue) VALUES ('test_db_value', 'myvalue')");

        $value = DB::fetchValue("SELECT StValue as value FROM settings WHERE StKey = 'test_db_value'");

        $this->assertEquals('myvalue', $value);

        // Clean up
        Connection::query("DELETE FROM settings WHERE StKey = 'test_db_value'");
    }

    public function testFetchValueWithCustomColumn(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        DB::execute("INSERT INTO settings (StKey, StValue) VALUES ('test_db_custom', 'customval')");

        $value = DB::fetchValue("SELECT StKey as mykey FROM settings WHERE StKey = 'test_db_custom'", 'mykey');

        $this->assertEquals('test_db_custom', $value);

        // Clean up
        Connection::query("DELETE FROM settings WHERE StKey = 'test_db_custom'");
    }

    // ===== execute() tests =====

    public function testExecuteReturnsAffectedRows(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        DB::execute("INSERT INTO settings (StKey, StValue) VALUES ('test_db_exec', 'value')");

        $affected = DB::execute("DELETE FROM settings WHERE StKey = 'test_db_exec'");

        $this->assertGreaterThanOrEqual(1, $affected);
    }

    public function testExecuteWithUpdate(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        DB::execute("INSERT INTO settings (StKey, StValue) VALUES ('test_db_update', 'old')");

        $affected = DB::execute("UPDATE settings SET StValue = 'new' WHERE StKey = 'test_db_update'");

        $this->assertEquals(1, $affected);

        // Verify update
        $value = DB::fetchValue("SELECT StValue as value FROM settings WHERE StKey = 'test_db_update'");
        $this->assertEquals('new', $value);

        // Clean up
        Connection::query("DELETE FROM settings WHERE StKey = 'test_db_update'");
    }

    // ===== lastInsertId() tests =====

    public function testLastInsertIdReturnsId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        DB::execute("INSERT INTO tags (TgText) VALUES ('test_db_lastid')");

        $lastId = DB::lastInsertId();

        $this->assertGreaterThan(0, $lastId);

        // Clean up
        Connection::query("DELETE FROM tags WHERE TgID = $lastId");
    }

    // ===== escape() tests =====

    public function testEscapeEscapesQuotes(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $escaped = DB::escape("test's value");
        $this->assertStringContainsString("\\'", $escaped);
    }

    public function testEscapeHandlesBackslash(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $escaped = DB::escape("test\\value");
        $this->assertStringContainsString('\\\\', $escaped);
    }

    // ===== escapeString() tests =====

    public function testEscapeStringReturnsQuotedString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = DB::escapeString("test value");
        $this->assertEquals("'test value'", $result);
    }

    public function testEscapeStringWithEmptyString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = DB::escapeString("");
        $this->assertEquals("''", $result);
    }

    // ===== escapeOrNull() tests =====

    public function testEscapeOrNullWithValue(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = DB::escapeOrNull("test value");
        $this->assertEquals("'test value'", $result);
    }

    public function testEscapeOrNullWithEmptyString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = DB::escapeOrNull("");
        $this->assertEquals('NULL', $result);
    }

    // ===== connection() tests =====

    public function testConnectionReturnsMySQL(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $connection = DB::connection();
        $this->assertInstanceOf(\mysqli::class, $connection);
    }

    // ===== Transaction tests =====

    public function testBeginTransaction(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = DB::beginTransaction();
        $this->assertTrue($result);
        
        // Rollback to clean up
        DB::rollback();
    }

    public function testCommitTransaction(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        DB::beginTransaction();
        DB::execute("INSERT INTO tags (TgText) VALUES ('test_db_commit')");
        $result = DB::commit();

        $this->assertTrue($result);

        // Verify committed
        $row = DB::fetchOne("SELECT * FROM tags WHERE TgText = 'test_db_commit'");
        $this->assertIsArray($row);

        // Clean up
        Connection::query("DELETE FROM tags WHERE TgText = 'test_db_commit'");
    }

    public function testRollbackTransaction(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        DB::beginTransaction();
        DB::execute("INSERT INTO tags (TgText) VALUES ('test_db_rollback')");
        $result = DB::rollback();

        $this->assertTrue($result);

        // Note: MyISAM doesn't support transactions, so rollback won't actually rollback
        // This test verifies the rollback method executes without error
        // Clean up the inserted row
        DB::execute("DELETE FROM tags WHERE TgText = 'test_db_rollback'");
    }

    // ===== Integration tests =====

    public function testFullCRUDCycle(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create
        DB::execute("INSERT INTO settings (StKey, StValue) VALUES ('test_db_crud', 'initial')");

        // Read
        $value = DB::fetchValue("SELECT StValue as value FROM settings WHERE StKey = 'test_db_crud'");
        $this->assertEquals('initial', $value);

        // Update
        DB::execute("UPDATE settings SET StValue = 'updated' WHERE StKey = 'test_db_crud'");
        $value = DB::fetchValue("SELECT StValue as value FROM settings WHERE StKey = 'test_db_crud'");
        $this->assertEquals('updated', $value);

        // Delete
        $affected = DB::execute("DELETE FROM settings WHERE StKey = 'test_db_crud'");
        $this->assertEquals(1, $affected);

        // Verify deleted
        $row = DB::fetchOne("SELECT * FROM settings WHERE StKey = 'test_db_crud'");
        $this->assertNull($row);
    }

    public function testQueryBuilderThroughFacade(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Insert test data
        DB::table('tags')->insert(['TgText' => 'test_db_builder1']);
        DB::table('tags')->insert(['TgText' => 'test_db_builder2']);
        
        // Query
        $results = DB::table('tags')
            ->where('TgText', 'LIKE', 'test_db_builder%')
            ->orderBy('TgText')
            ->get();
        
        $this->assertCount(2, $results);
        $this->assertEquals('test_db_builder1', $results[0]['TgText']);
        $this->assertEquals('test_db_builder2', $results[1]['TgText']);
        
        // Clean up
        DB::table('tags')->where('TgText', 'LIKE', 'test_db_builder%')->delete();
    }
}
