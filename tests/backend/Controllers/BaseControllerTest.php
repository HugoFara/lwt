<?php

declare(strict_types=1);

namespace Lwt\Tests\Controllers;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';
require_once __DIR__ . '/../../../src/backend/Controllers/BaseController.php';

use Lwt\Controllers\BaseController;
use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';

/**
 * Unit tests for the BaseController class.
 *
 * Tests controller helper methods, request parameter handling,
 * database operations, and utility functions.
 */
class BaseControllerTest extends TestCase
{
    private static bool $dbConnected = false;
    private TestableController $controller;
    private array $originalServer;
    private array $originalGet;
    private array $originalPost;
    private array $originalRequest;

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
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Save original superglobals
        $this->originalServer = $_SERVER;
        $this->originalGet = $_GET;
        $this->originalPost = $_POST;
        $this->originalRequest = $_REQUEST;
        
        // Reset superglobals
        $_SERVER = [];
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        
        $this->controller = new TestableController();
    }

    protected function tearDown(): void
    {
        // Restore superglobals
        $_SERVER = $this->originalServer;
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
        $_REQUEST = $this->originalRequest;
        
        // Clean up test data
        if (self::$dbConnected) {
            $tbpref = Globals::getTablePrefix();
            do_mysqli_query("DELETE FROM {$tbpref}tags WHERE TgText LIKE 'test_ctrl_%'");
        }
        
        parent::tearDown();
    }

    // ===== Constructor tests =====

    public function testConstructorSetsTablePrefix(): void
    {
        $tbpref = $this->controller->getTablePrefix();
        $this->assertIsString($tbpref);
    }

    public function testConstructorSetsDbConnection(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $db = $this->controller->getDbConnection();
        $this->assertInstanceOf(\mysqli::class, $db);
    }

    // ===== param() tests =====

    public function testParamReturnsValue(): void
    {
        $_REQUEST['test_param'] = 'test_value';
        $value = $this->controller->testParam('test_param');
        $this->assertEquals('test_value', $value);
    }

    public function testParamReturnsDefault(): void
    {
        $value = $this->controller->testParam('nonexistent', 'default_value');
        $this->assertEquals('default_value', $value);
    }

    public function testParamReturnsNullByDefault(): void
    {
        $value = $this->controller->testParam('nonexistent');
        $this->assertNull($value);
    }

    // ===== get() tests =====

    public function testGetReturnsValue(): void
    {
        $_GET['test_get'] = 'get_value';
        $value = $this->controller->testGet('test_get');
        $this->assertEquals('get_value', $value);
    }

    public function testGetReturnsDefault(): void
    {
        $value = $this->controller->testGet('nonexistent', 'default');
        $this->assertEquals('default', $value);
    }

    // ===== post() tests =====

    public function testPostReturnsValue(): void
    {
        $_POST['test_post'] = 'post_value';
        $value = $this->controller->testPost('test_post');
        $this->assertEquals('post_value', $value);
    }

    public function testPostReturnsDefault(): void
    {
        $value = $this->controller->testPost('nonexistent', 'default');
        $this->assertEquals('default', $value);
    }

    // ===== isPost() tests =====

    public function testIsPostReturnsTrue(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertTrue($this->controller->testIsPost());
    }

    public function testIsPostReturnsFalse(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertFalse($this->controller->testIsPost());
    }

    // ===== isGet() tests =====

    public function testIsGetReturnsTrue(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertTrue($this->controller->testIsGet());
    }

    public function testIsGetReturnsFalse(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertFalse($this->controller->testIsGet());
    }

    // ===== table() tests =====

    public function testTableAddsPrefix(): void
    {
        $tableName = $this->controller->testTable('tags');
        $tbpref = Globals::getTablePrefix();
        $this->assertEquals($tbpref . 'tags', $tableName);
    }

    // ===== escape() tests =====

    public function testEscapeHandlesQuotes(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $escaped = $this->controller->testEscape("test's value");
        $this->assertStringContainsString("test\\'s value", $escaped);
        $this->assertStringStartsWith("'", $escaped);
        $this->assertStringEndsWith("'", $escaped);
    }

    public function testEscapeHandlesEmptyString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $escaped = $this->controller->testEscape("");
        $this->assertEquals('NULL', $escaped);
    }

    // ===== escapeNonNull() tests =====

    public function testEscapeNonNullHandlesQuotes(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $escaped = $this->controller->testEscapeNonNull("test's value");
        $this->assertStringContainsString("test\\'s value", $escaped);
    }

    public function testEscapeNonNullWithEmptyString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $escaped = $this->controller->testEscapeNonNull("");
        $this->assertEquals("''", $escaped);
    }

    // ===== query() tests =====

    public function testQueryExecutesSelect(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = Globals::getTablePrefix();
        $result = $this->controller->testQuery("SELECT * FROM {$tbpref}tags LIMIT 1");
        
        $this->assertInstanceOf(\mysqli_result::class, $result);
        mysqli_free_result($result);
    }

    // ===== execute() tests =====

    public function testExecuteInsertsData(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = Globals::getTablePrefix();
        $result = $this->controller->testExecute(
            "INSERT INTO {$tbpref}tags (TgText) VALUES ('test_ctrl_exec')",
            "Test insert"
        );
        
        // runsql returns message with row count, e.g. "Test insert: 1"
        $this->assertStringContainsString('Test insert', $result);
        $this->assertStringContainsString('1', $result);
        
        // Clean up
        do_mysqli_query("DELETE FROM {$tbpref}tags WHERE TgText = 'test_ctrl_exec'");
    }

    // ===== getValue() tests =====

    public function testGetValueReturnsValue(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = Globals::getTablePrefix();
        
        // Insert test data
        do_mysqli_query("INSERT INTO {$tbpref}tags (TgText) VALUES ('test_ctrl_value')");
        
        $value = $this->controller->testGetValue(
            "SELECT TgText as value FROM {$tbpref}tags WHERE TgText = 'test_ctrl_value'"
        );
        
        $this->assertEquals('test_ctrl_value', $value);
        
        // Clean up
        do_mysqli_query("DELETE FROM {$tbpref}tags WHERE TgText = 'test_ctrl_value'");
    }

    // ===== getMarkedIds() tests =====

    public function testGetMarkedIdsWithArray(): void
    {
        $ids = $this->controller->testGetMarkedIds(['1', '2', '3']);
        $this->assertEquals([1, 2, 3], $ids);
    }

    public function testGetMarkedIdsWithString(): void
    {
        $ids = $this->controller->testGetMarkedIds('not_an_array');
        $this->assertEquals([], $ids);
    }

    public function testGetMarkedIdsWithEmptyArray(): void
    {
        $ids = $this->controller->testGetMarkedIds([]);
        $this->assertEquals([], $ids);
    }

    public function testGetMarkedIdsConvertsToInt(): void
    {
        $ids = $this->controller->testGetMarkedIds(['1', '2', '3', 'invalid']);
        $this->assertIsInt($ids[0]);
        $this->assertIsInt($ids[1]);
        $this->assertIsInt($ids[2]);
        $this->assertEquals(0, $ids[3]); // intval('invalid') = 0
    }

    // ===== sessionParam() tests =====

    public function testSessionParamWithRequestValue(): void
    {
        $_REQUEST['test_sess_req'] = '42';
        
        $value = $this->controller->testSessionParam(
            'test_sess_req',
            'test_sess_key',
            '0',
            true
        );
        
        $this->assertEquals(42, $value);
        $this->assertEquals(42, $_SESSION['test_sess_key'] ?? null);
    }

    public function testSessionParamWithoutRequestValue(): void
    {
        $_SESSION['test_sess_existing'] = 'existing_value';
        
        $value = $this->controller->testSessionParam(
            'nonexistent_req',
            'test_sess_existing',
            'default',
            false
        );
        
        $this->assertEquals('existing_value', $value);
    }

    // ===== dbParam() tests =====

    public function testDbParamWithRequestValue(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['test_db_req'] = '123';
        
        $value = $this->controller->testDbParam(
            'test_db_req',
            'test_db_key',
            '0',
            true
        );
        
        $this->assertEquals(123, $value);
        
        // Verify saved to database
        $saved = getSetting('test_db_key');
        $this->assertEquals('123', $saved);
        
        // Clean up
        $tbpref = Globals::getTablePrefix();
        do_mysqli_query("DELETE FROM {$tbpref}settings WHERE StKey = 'test_db_key'");
    }
}

/**
 * Concrete implementation of BaseController for testing.
 * 
 * Exposes protected methods for testing purposes.
 */
class TestableController extends BaseController
{
    public function getTablePrefix(): string
    {
        return $this->tbpref;
    }

    public function getDbConnection(): ?\mysqli
    {
        return $this->db;
    }

    public function testParam(string $key, mixed $default = null): mixed
    {
        return $this->param($key, $default);
    }

    public function testGet(string $key, mixed $default = null): mixed
    {
        return $this->get($key, $default);
    }

    public function testPost(string $key, mixed $default = null): mixed
    {
        return $this->post($key, $default);
    }

    public function testIsPost(): bool
    {
        return $this->isPost();
    }

    public function testIsGet(): bool
    {
        return $this->isGet();
    }

    public function testTable(string $table): string
    {
        return $this->table($table);
    }

    public function testEscape(string $value): string
    {
        return $this->escape($value);
    }

    public function testEscapeNonNull(string $value): string
    {
        return $this->escapeNonNull($value);
    }

    public function testQuery(string $sql): \mysqli_result|bool
    {
        return $this->query($sql);
    }

    public function testExecute(string $sql, string $message = '', bool $errDie = true): string
    {
        return $this->execute($sql, $message, $errDie);
    }

    public function testGetValue(string $sql): mixed
    {
        return $this->getValue($sql);
    }

    public function testGetMarkedIds(string|array $marked): array
    {
        return $this->getMarkedIds($marked);
    }

    public function testSessionParam(
        string $reqKey,
        string $sessKey,
        mixed $default,
        bool $isNumeric = false
    ): mixed {
        return $this->sessionParam($reqKey, $sessKey, $default, $isNumeric);
    }

    public function testDbParam(
        string $reqKey,
        string $dbKey,
        mixed $default,
        bool $isNumeric = false
    ): mixed {
        return $this->dbParam($reqKey, $dbKey, $default, $isNumeric);
    }
}
