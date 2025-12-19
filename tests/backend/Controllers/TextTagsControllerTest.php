<?php declare(strict_types=1);
namespace Lwt\Tests\Controllers;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Controllers\TextTagsController;
use Lwt\Controllers\AbstractCrudController;
use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\Configuration;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Controllers/BaseController.php';
require_once __DIR__ . '/../../../src/backend/Controllers/AbstractCrudController.php';
require_once __DIR__ . '/../../../src/backend/Controllers/TextTagsController.php';

/**
 * Unit tests for the TextTagsController class.
 *
 * Tests the controller that extends AbstractCrudController for text tag management.
 */
class TextTagsControllerTest extends TestCase
{
    private static bool $dbConnected = false;
    private array $originalServer;
    private array $originalGet;
    private array $originalPost;
    private array $originalRequest;
    private array $originalSession;

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
        $this->originalSession = $_SESSION ?? [];

        // Reset superglobals
        $_SERVER = ['REQUEST_METHOD' => 'GET'];
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        // Restore superglobals
        $_SERVER = $this->originalServer;
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
        $_REQUEST = $this->originalRequest;
        $_SESSION = $this->originalSession;

        parent::tearDown();
    }

    // ===== Constructor tests =====

    public function testControllerCanBeInstantiated(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextTagsController();

        $this->assertInstanceOf(TextTagsController::class, $controller);
    }

    public function testControllerExtendsAbstractCrudController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextTagsController();

        $this->assertInstanceOf(AbstractCrudController::class, $controller);
    }

    // ===== Property tests =====

    public function testControllerHasCorrectPageTitle(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextTagsController();

        $reflection = new \ReflectionClass($controller);
        $property = $reflection->getProperty('pageTitle');
        $property->setAccessible(true);

        $this->assertEquals('Text Tags', $property->getValue($controller));
    }

    public function testControllerHasCorrectResourceName(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextTagsController();

        $reflection = new \ReflectionClass($controller);
        $property = $reflection->getProperty('resourceName');
        $property->setAccessible(true);

        $this->assertEquals('tag', $property->getValue($controller));
    }

    // ===== AbstractCrudController method tests =====

    public function testControllerHasIndexMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextTagsController();

        $this->assertTrue(method_exists($controller, 'index'));
    }

    public function testControllerHasHandleCreateMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextTagsController();

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('handleCreate');

        $this->assertTrue($method->isProtected());
    }

    public function testControllerHasHandleUpdateMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextTagsController();

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('handleUpdate');

        $this->assertTrue($method->isProtected());
    }

    public function testControllerHasHandleDeleteMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextTagsController();

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('handleDelete');

        $this->assertTrue($method->isProtected());
    }

    public function testControllerHasRenderListMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextTagsController();

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('renderList');

        $this->assertTrue($method->isProtected());
    }

    public function testControllerHasRenderCreateFormMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextTagsController();

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('renderCreateForm');

        $this->assertTrue($method->isProtected());
    }

    public function testControllerHasRenderEditFormMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextTagsController();

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('renderEditForm');

        $this->assertTrue($method->isProtected());
    }

    // ===== ID parameter name test =====

    public function testGetIdParameterNameReturnsCorrectValue(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextTagsController();

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getIdParameterName');
        $method->setAccessible(true);

        $this->assertEquals('T2ID', $method->invoke($controller));
    }
}
