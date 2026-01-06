<?php declare(strict_types=1);
namespace Lwt\Tests\Controllers;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';
require_once __DIR__ . '/../../../src/backend/Controllers/BaseController.php';

use Lwt\Controllers\BaseController;
use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Shared\Infrastructure\Database\Configuration;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\Settings;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();

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
            Connection::query("DELETE FROM tags WHERE TgText LIKE 'test_ctrl_%'");
        }
        
        parent::tearDown();
    }

    // ===== Constructor tests =====

    public function testConstructorCreatesInstance(): void
    {
        // BaseController no longer stores a $db property - it uses
        // static Connection methods. Test that controller instantiates.
        $this->assertInstanceOf(TestableController::class, $this->controller);
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

    public function testParamReturnsEmptyStringByDefault(): void
    {
        $value = $this->controller->testParam('nonexistent');
        $this->assertSame('', $value);
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

    // ===== query() tests =====

    public function testQueryExecutesSelect(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->controller->testQuery("SELECT * FROM tags LIMIT 1");

        $this->assertInstanceOf(\mysqli_result::class, $result);
        mysqli_free_result($result);
    }

    // ===== execute() tests =====

    public function testExecuteInsertsData(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->controller->testExecute(
            "INSERT INTO tags (TgText) VALUES ('test_ctrl_exec')"
        );

        // execute returns number of affected rows
        $this->assertEquals(1, $result);

        // Clean up
        Connection::query("DELETE FROM tags WHERE TgText = 'test_ctrl_exec'");
    }

    // ===== getValue() tests =====

    public function testGetValueReturnsValue(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Insert test data
        Connection::query("INSERT INTO tags (TgText) VALUES ('test_ctrl_value')");

        $value = $this->controller->testGetValue(
            "SELECT TgText as value FROM tags WHERE TgText = 'test_ctrl_value'"
        );

        $this->assertEquals('test_ctrl_value', $value);

        // Clean up
        Connection::query("DELETE FROM tags WHERE TgText = 'test_ctrl_value'");
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

}

/**
 * Concrete implementation of BaseController for testing.
 * 
 * Exposes protected methods for testing purposes.
 */
class TestableController extends BaseController
{
    public function testParam(string $key, string $default = ''): string
    {
        return $this->param($key, $default);
    }

    public function testGet(string $key, string $default = ''): string
    {
        return $this->get($key, $default);
    }

    public function testPost(string $key, string $default = ''): string
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

    public function testQuery(string $sql): \mysqli_result|bool
    {
        return $this->query($sql);
    }

    public function testExecute(string $sql): int
    {
        return $this->execute($sql);
    }

    public function testGetValue(string $sql): mixed
    {
        return $this->getValue($sql);
    }

    public function testGetMarkedIds(string|array $marked): array
    {
        return $this->getMarkedIds($marked);
    }

}
