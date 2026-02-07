<?php

declare(strict_types=1);

namespace Lwt\Tests\Controllers;

require_once __DIR__ . '/../../../src/Shared/Infrastructure/Bootstrap/EnvLoader.php';

use Lwt\Modules\Review\Http\ReviewController;
use Lwt\Modules\Review\Application\ReviewFacade;
use Lwt\Modules\Review\Infrastructure\SessionStateManager;
use Lwt\Shared\Infrastructure\Bootstrap\EnvLoader;
use Lwt\Shared\Infrastructure\Globals;
use Lwt\Shared\Infrastructure\Database\Configuration;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Modules\Review\Application\Services\ReviewService;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../src/Shared/Infrastructure/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Controllers/BaseController.php';
require_once __DIR__ . '/../../../src/Modules/Review/Http/ReviewController.php';
require_once __DIR__ . '/../../../src/Modules/Review/Application/Services/ReviewService.php';

/**
 * Unit tests for the ReviewController class.
 *
 * Tests the word testing/review interface controller (from Review module)
 * and ReviewService integration.
 */
class ReviewControllerTest extends TestCase
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

    /**
     * Helper method to create a ReviewController with its dependencies.
     *
     * @return ReviewController
     */
    private function createController(): ReviewController
    {
        return new ReviewController(new ReviewFacade());
    }

    /**
     * Helper method to call protected param() method.
     *
     * @param ReviewController $controller The controller instance
     * @param string         $name       Parameter name
     * @param string         $default    Default value
     *
     * @return string Parameter value
     */
    private function invokeParam(ReviewController $controller, string $name, string $default = ''): string
    {
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('param');
        $method->setAccessible(true);
        return $method->invoke($controller, $name, $default);
    }

    // ===== Constructor tests =====

    public function testControllerCanBeInstantiated(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = $this->createController();

        $this->assertInstanceOf(ReviewController::class, $controller);
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

    public function testControllerHasHeaderMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = $this->createController();

        $this->assertTrue(method_exists($controller, 'header'));
    }

    public function testControllerHasTableTestMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = $this->createController();

        $this->assertTrue(method_exists($controller, 'tableReview'));
    }

    // ===== BaseController inheritance tests =====

    public function testControllerExtendsBaseController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = $this->createController();

        $this->assertInstanceOf(\Lwt\Controllers\BaseController::class, $controller);
    }

    // ===== ReviewService tests =====

    public function testReviewServiceCanBeInstantiated(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new ReviewService();

        $this->assertInstanceOf(ReviewService::class, $service);
    }

    public function testReviewServiceCalculateNewStatusIncrements(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new ReviewService();

        // Test status increment
        $newStatus = $service->calculateNewStatus(2, 1);
        $this->assertEquals(3, $newStatus);
    }

    public function testReviewServiceCalculateNewStatusDecrements(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new ReviewService();

        // Test status decrement
        $newStatus = $service->calculateNewStatus(3, -1);
        $this->assertEquals(2, $newStatus);
    }

    public function testReviewServiceCalculateNewStatusClampsMin(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new ReviewService();

        // Test minimum status clamping
        $newStatus = $service->calculateNewStatus(1, -5);
        $this->assertEquals(1, $newStatus);
    }

    public function testReviewServiceCalculateNewStatusClampsMax(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new ReviewService();

        // Test maximum status clamping
        $newStatus = $service->calculateNewStatus(5, 10);
        $this->assertEquals(5, $newStatus);
    }

    public function testReviewServiceCalculateStatusChangePositive(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new ReviewService();

        // Returns 1 for positive change (not the actual difference)
        $change = $service->calculateStatusChange(2, 4);
        $this->assertEquals(1, $change);
    }

    public function testReviewServiceCalculateStatusChangeNegative(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new ReviewService();

        // Returns -1 for negative change (not the actual difference)
        $change = $service->calculateStatusChange(4, 2);
        $this->assertEquals(-1, $change);
    }

    public function testReviewServiceCalculateStatusChangeZero(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new ReviewService();

        // Returns 0 for no change
        $change = $service->calculateStatusChange(3, 3);
        $this->assertEquals(0, $change);
    }

    public function testReviewServiceClampTestType(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new ReviewService();

        // Test clamping within valid range
        $this->assertEquals(1, $service->clampReviewType(1));
        $this->assertEquals(5, $service->clampReviewType(5));
    }

    public function testReviewServiceClampTestTypeClampsLow(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new ReviewService();

        $this->assertEquals(1, $service->clampReviewType(0));
        $this->assertEquals(1, $service->clampReviewType(-5));
    }

    public function testReviewServiceClampTestTypeClampsHigh(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new ReviewService();

        // Assuming max test type is around 5-6
        $result = $service->clampReviewType(100);
        $this->assertGreaterThanOrEqual(1, $result);
        $this->assertLessThanOrEqual(6, $result);
    }

    public function testReviewServiceIsWordMode(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new ReviewService();

        // Test word mode detection
        $this->assertIsBool($service->isWordMode(1));
    }

    public function testReviewServiceGetBaseTestType(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new ReviewService();

        $baseType = $service->getBaseReviewType(1);
        $this->assertIsInt($baseType);
        $this->assertGreaterThanOrEqual(1, $baseType);
    }

    public function testReviewServiceGetWaitingTime(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new ReviewService();

        $waitTime = $service->getWaitingTime();
        $this->assertIsInt($waitTime);
        $this->assertGreaterThanOrEqual(0, $waitTime);
    }

    public function testReviewServiceGetEditFrameWaitingTime(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new ReviewService();

        $waitTime = $service->getEditFrameWaitingTime();
        $this->assertIsInt($waitTime);
        $this->assertGreaterThanOrEqual(0, $waitTime);
    }

    public function testReviewServiceGetWordText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new ReviewService();

        // Test with non-existent word ID
        $text = $service->getWordText(999999);
        $this->assertNull($text);
    }

    public function testReviewServiceGetReviewSessionData(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new ReviewService();

        $sessionData = $service->getReviewSessionData();
        $this->assertIsArray($sessionData);
        $this->assertArrayHasKey('wrong', $sessionData);
        $this->assertArrayHasKey('correct', $sessionData);
    }

    public function testReviewServiceGetTableTestSettings(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new ReviewService();

        $settings = $service->getTableReviewSettings();
        $this->assertIsArray($settings);
    }

    // ===== Parameter tests =====

    public function testLangParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['lang'] = '5';

        $controller = $this->createController();

        $this->assertEquals('5', $this->invokeParam($controller, 'lang'));
    }

    public function testTextParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['text'] = '10';

        $controller = $this->createController();

        $this->assertEquals('10', $this->invokeParam($controller, 'text'));
    }

    public function testSelectionParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['selection'] = '3';

        $controller = $this->createController();

        $this->assertEquals('3', $this->invokeParam($controller, 'selection'));
    }

    public function testTypeParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['type'] = '2';

        $controller = $this->createController();

        $this->assertEquals('2', $this->invokeParam($controller, 'type'));
    }

    public function testWidParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['wid'] = '100';

        $controller = $this->createController();

        $this->assertEquals('100', $this->invokeParam($controller, 'wid'));
    }

    public function testStatusParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['status'] = '4';

        $controller = $this->createController();

        $this->assertEquals('4', $this->invokeParam($controller, 'status'));
    }

    public function testStchangeParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['stchange'] = '1';

        $controller = $this->createController();

        $this->assertEquals('1', $this->invokeParam($controller, 'stchange'));
    }

    public function testAjaxParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['ajax'] = '1';

        $this->assertTrue(isset($_REQUEST['ajax']));
    }

    // ===== Session tests =====

    public function testSessionTestsqlDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_SESSION['testsql'] = 'SELECT * FROM words WHERE WoStatus < 5';

        $this->assertEquals('SELECT * FROM words WHERE WoStatus < 5', $_SESSION['testsql']);
    }

    // ===== Test property determination =====

    public function testGetTestPropertyWithSelection(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['selection'] = '1';
        // Use SessionStateManager to store criteria instead of raw SQL
        $sessionManager = new SessionStateManager();
        $sessionManager->saveCriteria('texts', [1, 2, 3]);

        $controller = $this->createController();

        // Use reflection to test private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getReviewProperty');
        $method->setAccessible(true);

        $result = $method->invoke($controller);

        $this->assertEquals('selection=1', $result);

        // Clean up
        $sessionManager->clearCriteria();
    }

    public function testGetTestPropertyWithLang(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['lang'] = '5';

        $controller = $this->createController();

        // Use reflection to test private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getReviewProperty');
        $method->setAccessible(true);

        $result = $method->invoke($controller);

        $this->assertEquals('lang=5', $result);
    }

    public function testGetTestPropertyWithText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['text'] = '10';

        $controller = $this->createController();

        // Use reflection to test private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getReviewProperty');
        $method->setAccessible(true);

        $result = $method->invoke($controller);

        $this->assertEquals('text=10', $result);
    }

    public function testGetTestPropertyReturnsEmptyWhenNoParams(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = $this->createController();

        // Use reflection to test private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getReviewProperty');
        $method->setAccessible(true);

        $result = $method->invoke($controller);

        $this->assertEquals('', $result);
    }

    // ===== Database query tests =====

    public function testWordsQueryWorks(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $sql = "SELECT WoID, WoText, WoStatus FROM " . Globals::table('words') . " LIMIT 10";
        $result = Connection::query($sql);

        $this->assertInstanceOf(\mysqli_result::class, $result);
        mysqli_free_result($result);
    }

    public function testWordsStatusQuery(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $sql = "SELECT COUNT(*) AS value FROM " . Globals::table('words') . " WHERE WoStatus BETWEEN 1 AND 5";
        $result = Connection::fetchValue($sql);

        $this->assertIsNumeric($result);
    }

    public function testLanguageSettingsQuery(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $sql = "SELECT LgID, LgName, LgTextSize, LgRegexpWordCharacters, LgRightToLeft
                FROM " . Globals::table('languages') . " LIMIT 5";
        $result = Connection::query($sql);

        $this->assertInstanceOf(\mysqli_result::class, $result);
        mysqli_free_result($result);
    }

    // ===== Multiple controller instances test =====

    public function testMultipleControllerInstances(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller1 = $this->createController();
        $controller2 = $this->createController();

        $this->assertInstanceOf(ReviewController::class, $controller1);
        $this->assertInstanceOf(ReviewController::class, $controller2);
        $this->assertNotSame($controller1, $controller2);
    }

    // ===== ReviewService test identifier tests =====

    public function testReviewServiceGetTestIdentifierWithLang(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new ReviewService();

        $identifier = $service->getReviewIdentifier(null, null, 1, null);

        $this->assertIsArray($identifier);
        $this->assertCount(2, $identifier);
    }

    public function testReviewServiceGetTestIdentifierWithText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new ReviewService();

        $identifier = $service->getReviewIdentifier(null, null, null, 1);

        $this->assertIsArray($identifier);
        $this->assertCount(2, $identifier);
    }

    public function testReviewServiceGetTestIdentifierWithSelection(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new ReviewService();

        $identifier = $service->getReviewIdentifier(1, 'SELECT * FROM words', null, null);

        $this->assertIsArray($identifier);
        $this->assertCount(2, $identifier);
    }

    // ===== ReviewService validation tests =====

    public function testValidateTestSelectionMethodExists(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new ReviewService();

        $this->assertTrue(method_exists($service, 'validateReviewSelection'));
    }

    public function testReviewServiceValidateTestSelectionReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new ReviewService();

        // The method expects a subquery format like "(SELECT WoID FROM words) AS t"
        // Use proper subquery syntax
        $subquery = "(SELECT WoID, WoLgID FROM " . Globals::table('words') . " WHERE WoLgID = 1 LIMIT 1) AS subq";

        $result = $service->validateReviewSelection($subquery);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('langCount', $result);
    }

    // ===== Session progress tests =====

    public function testReviewServiceInitializeReviewSession(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new ReviewService();
        $service->initializeReviewSession(10);

        $sessionData = $service->getReviewSessionData();

        $this->assertIsArray($sessionData);
        $this->assertArrayHasKey('start', $sessionData);
    }

    public function testReviewServiceUpdateSessionProgress(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new ReviewService();
        $service->initializeReviewSession(10);

        $result = $service->updateSessionProgress(1);

        $this->assertIsArray($result);
    }
}
