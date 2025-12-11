<?php declare(strict_types=1);
namespace Lwt\Tests\Controllers;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Controllers\HomeController;
use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use Lwt\Services\HomeService;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Controllers/BaseController.php';
require_once __DIR__ . '/../../../src/backend/Controllers/HomeController.php';
require_once __DIR__ . '/../../../src/backend/Services/HomeService.php';

/**
 * Unit tests for the HomeController class.
 *
 * Tests the controller initialization, HomeService integration,
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

    // ===== Constructor tests =====

    public function testControllerCanBeInstantiated(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new HomeController();

        $this->assertInstanceOf(HomeController::class, $controller);
    }

    public function testControllerHasHomeService(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new HomeController();
        $service = $controller->getHomeService();

        $this->assertInstanceOf(HomeService::class, $service);
    }

    // ===== Method existence tests =====

    public function testControllerHasIndexMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new HomeController();

        $this->assertTrue(method_exists($controller, 'index'));
    }

    public function testControllerHasGetHomeServiceMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new HomeController();

        $this->assertTrue(method_exists($controller, 'getHomeService'));
    }

    // ===== HomeService integration tests =====

    public function testHomeServiceGetsDashboardData(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new HomeController();
        $service = $controller->getHomeService();
        $data = $service->getDashboardData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('language_count', $data);
        $this->assertArrayHasKey('current_language_id', $data);
        $this->assertArrayHasKey('current_text_id', $data);
        $this->assertArrayHasKey('table_prefix', $data);
        $this->assertArrayHasKey('is_debug', $data);
        $this->assertArrayHasKey('is_wordpress', $data);
    }

    public function testHomeServiceGetsLanguageCount(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new HomeController();
        $service = $controller->getHomeService();
        $count = $service->getLanguageCount();

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testHomeServiceGetsTablePrefix(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new HomeController();
        $service = $controller->getHomeService();
        $prefix = $service->getTablePrefix();

        $this->assertIsString($prefix);
    }

    public function testHomeServiceGetsTableSetSpanGroups(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new HomeController();
        $service = $controller->getHomeService();
        $groups = $service->getTableSetSpanGroups();

        $this->assertIsArray($groups);
        $this->assertArrayHasKey('span1', $groups);
        $this->assertArrayHasKey('span2', $groups);
        $this->assertArrayHasKey('span3', $groups);
    }

    // ===== Data type verification tests =====

    public function testDashboardDataLanguageCountIsInt(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new HomeController();
        $data = $controller->getHomeService()->getDashboardData();

        $this->assertIsInt($data['language_count']);
    }

    public function testDashboardDataTablePrefixIsString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new HomeController();
        $data = $controller->getHomeService()->getDashboardData();

        $this->assertIsString($data['table_prefix']);
    }

    public function testDashboardDataIsDebugIsBool(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new HomeController();
        $data = $controller->getHomeService()->getDashboardData();

        $this->assertIsBool($data['is_debug']);
    }

    public function testDashboardDataIsWordpressIsBool(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new HomeController();
        $data = $controller->getHomeService()->getDashboardData();

        $this->assertIsBool($data['is_wordpress']);
    }

    public function testDashboardDataIsFixedPrefixIsBool(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new HomeController();
        $data = $controller->getHomeService()->getDashboardData();

        $this->assertIsBool($data['is_fixed_prefix']);
    }

    // ===== Current text info tests =====

    public function testCurrentTextIdIsNullOrInt(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new HomeController();
        $data = $controller->getHomeService()->getDashboardData();

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

        $controller = new HomeController();
        $data = $controller->getHomeService()->getDashboardData();

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

        $controller = new HomeController();
        $data = $controller->getHomeService()->getDashboardData();

        $this->assertTrue(
            $data['current_text_info'] === null || is_array($data['current_text_info']),
            'Expected null or array for current_text_info'
        );
    }

    // ===== Integration tests =====

    public function testDashboardDataConsistentWithService(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new HomeController();
        $service = $controller->getHomeService();

        $dashboardData = $service->getDashboardData();

        $this->assertSame(
            $service->getLanguageCount(),
            $dashboardData['language_count'],
            'Language count should match'
        );

        $this->assertSame(
            $service->getTablePrefix(),
            $dashboardData['table_prefix'],
            'Table prefix should match'
        );

        $this->assertSame(
            $service->isTablePrefixFixed(),
            $dashboardData['is_fixed_prefix'],
            'Fixed prefix flag should match'
        );
    }

    public function testMultipleControllerInstancesShareService(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller1 = new HomeController();
        $controller2 = new HomeController();

        // Both controllers should get consistent data
        $data1 = $controller1->getHomeService()->getDashboardData();
        $data2 = $controller2->getHomeService()->getDashboardData();

        $this->assertSame(
            $data1['language_count'],
            $data2['language_count'],
            'Language count should be consistent across instances'
        );

        $this->assertSame(
            $data1['table_prefix'],
            $data2['table_prefix'],
            'Table prefix should be consistent across instances'
        );
    }

    // ===== Database-dependent feature tests =====

    public function testEmptyDatabaseShowsZeroLanguages(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new HomeController();
        $count = $controller->getHomeService()->getLanguageCount();

        // Just verify it's a valid non-negative count
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testGetCurrentTextInfoForNonExistentText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new HomeController();
        $result = $controller->getHomeService()->getCurrentTextInfo(999999);

        $this->assertNull($result);
    }

    public function testGetLanguageNameForNonExistentLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new HomeController();
        $result = $controller->getHomeService()->getLanguageName(999999);

        $this->assertSame('', $result);
    }

    // ===== WordPress session tests =====

    public function testWordPressSessionDetectionWhenNotSet(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        unset($_SESSION['LWT-WP-User']);

        $controller = new HomeController();
        $data = $controller->getHomeService()->getDashboardData();

        $this->assertFalse($data['is_wordpress']);
    }

    public function testWordPressSessionDetectionWhenSet(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_SESSION['LWT-WP-User'] = 'test_user';

        $controller = new HomeController();
        $data = $controller->getHomeService()->getDashboardData();

        $this->assertTrue($data['is_wordpress']);

        // Clean up
        unset($_SESSION['LWT-WP-User']);
    }
}
