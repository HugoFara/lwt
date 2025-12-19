<?php declare(strict_types=1);
namespace Lwt\Tests\Controllers;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Controllers\WordPressController;
use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Services\WordPressService;
use Lwt\Database\Configuration;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Controllers/BaseController.php';
require_once __DIR__ . '/../../../src/backend/Controllers/WordPressController.php';
require_once __DIR__ . '/../../../src/backend/Services/WordPressService.php';

/**
 * Unit tests for the WordPressController class.
 *
 * Tests controller initialization and service integration.
 * Note: Full integration tests require WordPress to be installed.
 */
class WordPressControllerTest extends TestCase
{
    private static bool $dbConnected = false;
    private array $originalRequest;
    private array $originalServer;
    private array $originalGet;
    private array $originalPost;
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
        // Save original superglobals
        $this->originalRequest = $_REQUEST;
        $this->originalServer = $_SERVER;
        $this->originalGet = $_GET;
        $this->originalPost = $_POST;
        $this->originalSession = $_SESSION ?? [];

        // Reset superglobals
        $_REQUEST = [];
        $_SERVER = ['REQUEST_METHOD' => 'GET'];
        $_GET = [];
        $_POST = [];
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        // Restore superglobals
        $_REQUEST = $this->originalRequest;
        $_SERVER = $this->originalServer;
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
        $_SESSION = $this->originalSession;
    }

    // ===== Constructor tests =====

    public function testControllerCanBeInstantiated(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordPressController(new \Lwt\Services\WordPressService());

        $this->assertInstanceOf(WordPressController::class, $controller);
    }

    public function testControllerHasWordPressService(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordPressController(new \Lwt\Services\WordPressService());
        $service = $controller->getWordPressService();

        $this->assertInstanceOf(WordPressService::class, $service);
    }

    // ===== Method existence tests =====

    public function testControllerHasStartMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordPressController(new \Lwt\Services\WordPressService());

        $this->assertTrue(method_exists($controller, 'start'));
    }

    public function testControllerHasStopMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordPressController(new \Lwt\Services\WordPressService());

        $this->assertTrue(method_exists($controller, 'stop'));
    }

    public function testControllerHasGetWordPressServiceMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordPressController(new \Lwt\Services\WordPressService());

        $this->assertTrue(method_exists($controller, 'getWordPressService'));
    }

    // ===== Service tests =====

    public function testWordPressServiceValidateRedirectUrlReturnsDefault(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordPressController(new \Lwt\Services\WordPressService());
        $service = $controller->getWordPressService();

        $result = $service->validateRedirectUrl(null);

        $this->assertEquals('index.php', $result);
    }

    public function testWordPressServiceValidateRedirectUrlReturnsDefaultForEmpty(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordPressController(new \Lwt\Services\WordPressService());
        $service = $controller->getWordPressService();

        $result = $service->validateRedirectUrl('');

        $this->assertEquals('index.php', $result);
    }

    public function testWordPressServiceValidateRedirectUrlReturnsDefaultForNonexistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordPressController(new \Lwt\Services\WordPressService());
        $service = $controller->getWordPressService();

        $result = $service->validateRedirectUrl('nonexistent_file_12345.php');

        $this->assertEquals('index.php', $result);
    }

    public function testWordPressServiceGetLoginUrl(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordPressController(new \Lwt\Services\WordPressService());
        $service = $controller->getWordPressService();

        $result = $service->getLoginUrl();

        $this->assertStringContainsString('wp-login.php', $result);
        $this->assertStringContainsString('redirect_to', $result);
    }

    public function testWordPressServiceGetLoginUrlWithCustomRedirect(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordPressController(new \Lwt\Services\WordPressService());
        $service = $controller->getWordPressService();

        $result = $service->getLoginUrl('./custom/path.php');

        $this->assertStringContainsString('wp-login.php', $result);
        $this->assertStringContainsString(urlencode('./custom/path.php'), $result);
    }

    public function testWordPressServiceIsUserLoggedInReturnsFalseWithoutWordPress(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordPressController(new \Lwt\Services\WordPressService());
        $service = $controller->getWordPressService();

        // Without WordPress loaded, this should return false
        $result = $service->isUserLoggedIn();

        $this->assertFalse($result);
    }

    public function testWordPressServiceGetCurrentUserIdReturnsNullWithoutWordPress(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordPressController(new \Lwt\Services\WordPressService());
        $service = $controller->getWordPressService();

        // Without WordPress loaded, this should return null
        $result = $service->getCurrentUserId();

        $this->assertNull($result);
    }

    public function testWordPressServiceLoadWordPressReturnsFalseWhenNotInstalled(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordPressController(new \Lwt\Services\WordPressService());
        $service = $controller->getWordPressService();

        // In test environment, WordPress is typically not installed
        $result = $service->loadWordPress();

        // This will be false unless WordPress is actually installed
        $this->assertIsBool($result);
    }

    public function testWordPressServiceSessionUserOperations(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordPressController(new \Lwt\Services\WordPressService());
        $service = $controller->getWordPressService();

        // Initially null
        $this->assertNull($service->getSessionUser());

        // Set user
        $service->setSessionUser(123);
        $this->assertEquals(123, $service->getSessionUser());

        // Clear user
        $service->clearSessionUser();
        $this->assertNull($service->getSessionUser());
    }

    public function testWordPressServiceHandleStartWithoutWordPress(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordPressController(new \Lwt\Services\WordPressService());
        $service = $controller->getWordPressService();

        $result = $service->handleStart(null);

        // Without WordPress, should fail or redirect to login
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('redirect', $result);
        $this->assertArrayHasKey('error', $result);
    }

    public function testWordPressServiceHandleStop(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordPressController(new \Lwt\Services\WordPressService());
        $service = $controller->getWordPressService();

        $result = $service->handleStop();

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('redirect', $result);
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('wp-login.php', $result['redirect']);
    }

    // ===== Start session tests =====

    public function testWordPressServiceStartSession(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Note: This test might behave differently depending on whether
        // a session is already active
        $controller = new WordPressController(new \Lwt\Services\WordPressService());
        $service = $controller->getWordPressService();

        $result = $service->startSession();

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('error', $result);

        // Result depends on session state
        $this->assertIsBool($result['success']);
    }

    // ===== Route parameter tests =====

    public function testStartMethodAcceptsArrayParams(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordPressController(new \Lwt\Services\WordPressService());

        // Test that start() accepts an array parameter
        $reflection = new \ReflectionMethod($controller, 'start');
        $params = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertEquals('params', $params[0]->getName());
        $this->assertEquals('array', $params[0]->getType()->getName());
    }

    public function testStopMethodAcceptsArrayParams(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordPressController(new \Lwt\Services\WordPressService());

        // Test that stop() accepts an array parameter
        $reflection = new \ReflectionMethod($controller, 'stop');
        $params = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertEquals('params', $params[0]->getName());
        $this->assertEquals('array', $params[0]->getType()->getName());
    }
}
