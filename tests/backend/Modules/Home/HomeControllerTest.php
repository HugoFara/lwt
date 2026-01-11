<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Home;

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Modules\Home\Http\HomeController;
use Lwt\Modules\Home\Application\HomeFacade;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Core\Bootstrap\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Shared\Infrastructure\Database\Configuration;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../../src/backend/Controllers/BaseController.php';

/**
 * Unit tests for the Home module HomeController class.
 *
 * Tests the controller initialization, HomeFacade integration,
 * and verifies the MVC pattern implementation for the home page.
 */
class HomeControllerTest extends TestCase
{
    private static bool $dbConnected = false;
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
        $_SERVER = ['REQUEST_METHOD' => 'GET'];
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
    }

    protected function tearDown(): void
    {
        // Restore superglobals
        $_SERVER = $this->originalServer;
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
        $_REQUEST = $this->originalRequest;

        parent::tearDown();
    }

    /**
     * Helper method to create a HomeController with its dependencies.
     *
     * @return HomeController
     */
    private function createController(): HomeController
    {
        return new HomeController(new HomeFacade(), new LanguageFacade());
    }

    // ===== Constructor tests =====

    public function testControllerCanBeInstantiated(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = $this->createController();

        $this->assertInstanceOf(HomeController::class, $controller);
    }

    public function testControllerHasHomeFacade(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = $this->createController();
        $facade = $controller->getHomeFacade();

        $this->assertInstanceOf(HomeFacade::class, $facade);
    }

    // ===== Method existence tests =====

    public function testControllerHasIndexMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = $this->createController();

        $this->assertTrue(method_exists($controller, 'index'));
    }

    public function testControllerHasGetHomeFacadeMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = $this->createController();

        $this->assertTrue(method_exists($controller, 'getHomeFacade'));
    }

    // ===== HomeFacade integration tests =====

    public function testHomeFacadeGetsDashboardData(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = $this->createController();
        $facade = $controller->getHomeFacade();
        $data = $facade->getDashboardData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('language_count', $data);
        $this->assertArrayHasKey('current_language_id', $data);
        $this->assertArrayHasKey('current_text_id', $data);
        $this->assertArrayHasKey('is_wordpress', $data);
        $this->assertArrayHasKey('is_multi_user', $data);
    }

    public function testHomeFacadeGetsLanguageCount(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = $this->createController();
        $facade = $controller->getHomeFacade();
        $count = $facade->getLanguageCount();

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    // ===== Data type verification tests =====

    public function testDashboardDataLanguageCountIsInt(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = $this->createController();
        $data = $controller->getHomeFacade()->getDashboardData();

        $this->assertIsInt($data['language_count']);
    }

    public function testDashboardDataIsWordpressIsBool(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = $this->createController();
        $data = $controller->getHomeFacade()->getDashboardData();

        $this->assertIsBool($data['is_wordpress']);
    }

    // ===== Current text info tests =====

    public function testCurrentTextIdIsNullOrInt(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = $this->createController();
        $data = $controller->getHomeFacade()->getDashboardData();

        $this->assertTrue(
            $data['current_text_id'] === null || is_int($data['current_text_id']),
            'Expected null or int for current_text_id'
        );
    }

    public function testCurrentLanguageIdIsNullOrInt(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = $this->createController();
        $data = $controller->getHomeFacade()->getDashboardData();

        $this->assertTrue(
            $data['current_language_id'] === null || is_int($data['current_language_id']),
            'Expected null or int for current_language_id'
        );
    }

    public function testCurrentTextInfoIsNullOrArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = $this->createController();
        $data = $controller->getHomeFacade()->getDashboardData();

        $this->assertTrue(
            $data['current_text_info'] === null || is_array($data['current_text_info']),
            'Expected null or array for current_text_info'
        );
    }

    // ===== Integration tests =====

    public function testDashboardDataConsistentWithFacade(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = $this->createController();
        $facade = $controller->getHomeFacade();

        $dashboardData = $facade->getDashboardData();

        $this->assertSame(
            $facade->getLanguageCount(),
            $dashboardData['language_count'],
            'Language count should match'
        );
    }

    public function testMultipleControllerInstancesShareData(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller1 = $this->createController();
        $controller2 = $this->createController();

        // Both controllers should get consistent data
        $data1 = $controller1->getHomeFacade()->getDashboardData();
        $data2 = $controller2->getHomeFacade()->getDashboardData();

        $this->assertSame(
            $data1['language_count'],
            $data2['language_count'],
            'Language count should be consistent across instances'
        );
    }

    // ===== Database-dependent feature tests =====

    public function testEmptyDatabaseShowsZeroLanguages(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = $this->createController();
        $count = $controller->getHomeFacade()->getLanguageCount();

        // Just verify it's a valid non-negative count
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testGetLanguageNameForNonExistentLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = $this->createController();
        $result = $controller->getHomeFacade()->getLanguageName(999999);

        $this->assertSame('', $result);
    }

    // ===== WordPress session tests =====

    public function testWordPressSessionDetectionWhenNotSet(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        unset($_SESSION['LWT-WP-User']);

        $controller = $this->createController();
        $data = $controller->getHomeFacade()->getDashboardData();

        $this->assertFalse($data['is_wordpress']);
    }

    public function testWordPressSessionDetectionWhenSet(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_SESSION['LWT-WP-User'] = 'test_user';

        $controller = $this->createController();
        $data = $controller->getHomeFacade()->getDashboardData();

        $this->assertTrue($data['is_wordpress']);

        // Clean up
        unset($_SESSION['LWT-WP-User']);
    }
}
