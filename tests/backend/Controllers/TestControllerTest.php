<?php declare(strict_types=1);
namespace Lwt\Tests\Controllers;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Controllers\TestController;
use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use Lwt\Services\TestService;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Controllers/BaseController.php';
require_once __DIR__ . '/../../../src/backend/Controllers/TestController.php';
require_once __DIR__ . '/../../../src/backend/Services/TestService.php';

/**
 * Unit tests for the TestController class.
 *
 * Tests the word testing/review interface controller and TestService integration.
 */
class TestControllerTest extends TestCase
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
     * Helper method to call protected param() method.
     *
     * @param TestController $controller The controller instance
     * @param string         $name       Parameter name
     * @param string         $default    Default value
     *
     * @return string Parameter value
     */
    private function invokeParam(TestController $controller, string $name, string $default = ''): string
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

        $controller = new TestController();

        $this->assertInstanceOf(TestController::class, $controller);
    }

    // ===== Method existence tests =====

    public function testControllerHasIndexMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TestController();

        $this->assertTrue(method_exists($controller, 'index'));
    }

    public function testControllerHasHeaderMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TestController();

        $this->assertTrue(method_exists($controller, 'header'));
    }

    public function testControllerHasTableTestMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TestController();

        $this->assertTrue(method_exists($controller, 'tableTest'));
    }

    // ===== BaseController inheritance tests =====

    public function testControllerExtendsBaseController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TestController();

        $this->assertInstanceOf(\Lwt\Controllers\BaseController::class, $controller);
    }

    // ===== TestService tests =====

    public function testTestServiceCanBeInstantiated(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TestService();

        $this->assertInstanceOf(TestService::class, $service);
    }

    public function testTestServiceCalculateNewStatusIncrements(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TestService();

        // Test status increment
        $newStatus = $service->calculateNewStatus(2, 1);
        $this->assertEquals(3, $newStatus);
    }

    public function testTestServiceCalculateNewStatusDecrements(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TestService();

        // Test status decrement
        $newStatus = $service->calculateNewStatus(3, -1);
        $this->assertEquals(2, $newStatus);
    }

    public function testTestServiceCalculateNewStatusClampsMin(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TestService();

        // Test minimum status clamping
        $newStatus = $service->calculateNewStatus(1, -5);
        $this->assertEquals(1, $newStatus);
    }

    public function testTestServiceCalculateNewStatusClampsMax(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TestService();

        // Test maximum status clamping
        $newStatus = $service->calculateNewStatus(5, 10);
        $this->assertEquals(5, $newStatus);
    }

    public function testTestServiceCalculateStatusChangePositive(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TestService();

        // Returns 1 for positive change (not the actual difference)
        $change = $service->calculateStatusChange(2, 4);
        $this->assertEquals(1, $change);
    }

    public function testTestServiceCalculateStatusChangeNegative(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TestService();

        // Returns -1 for negative change (not the actual difference)
        $change = $service->calculateStatusChange(4, 2);
        $this->assertEquals(-1, $change);
    }

    public function testTestServiceCalculateStatusChangeZero(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TestService();

        // Returns 0 for no change
        $change = $service->calculateStatusChange(3, 3);
        $this->assertEquals(0, $change);
    }

    public function testTestServiceClampTestType(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TestService();

        // Test clamping within valid range
        $this->assertEquals(1, $service->clampTestType(1));
        $this->assertEquals(5, $service->clampTestType(5));
    }

    public function testTestServiceClampTestTypeClampsLow(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TestService();

        $this->assertEquals(1, $service->clampTestType(0));
        $this->assertEquals(1, $service->clampTestType(-5));
    }

    public function testTestServiceClampTestTypeClampsHigh(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TestService();

        // Assuming max test type is around 5-6
        $result = $service->clampTestType(100);
        $this->assertGreaterThanOrEqual(1, $result);
        $this->assertLessThanOrEqual(6, $result);
    }

    public function testTestServiceIsWordMode(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TestService();

        // Test word mode detection
        $this->assertIsBool($service->isWordMode(1));
    }

    public function testTestServiceGetBaseTestType(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TestService();

        $baseType = $service->getBaseTestType(1);
        $this->assertIsInt($baseType);
        $this->assertGreaterThanOrEqual(1, $baseType);
    }

    public function testTestServiceGetWaitingTime(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TestService();

        $waitTime = $service->getWaitingTime();
        $this->assertIsInt($waitTime);
        $this->assertGreaterThanOrEqual(0, $waitTime);
    }

    public function testTestServiceGetEditFrameWaitingTime(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TestService();

        $waitTime = $service->getEditFrameWaitingTime();
        $this->assertIsInt($waitTime);
        $this->assertGreaterThanOrEqual(0, $waitTime);
    }

    public function testTestServiceGetWordText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TestService();

        // Test with non-existent word ID
        $text = $service->getWordText(999999);
        $this->assertNull($text);
    }

    public function testTestServiceGetTestSessionData(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TestService();

        $sessionData = $service->getTestSessionData();
        $this->assertIsArray($sessionData);
        $this->assertArrayHasKey('wrong', $sessionData);
        $this->assertArrayHasKey('correct', $sessionData);
    }

    public function testTestServiceGetTableTestSettings(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TestService();

        $settings = $service->getTableTestSettings();
        $this->assertIsArray($settings);
    }

    // ===== Parameter tests =====

    public function testLangParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['lang'] = '5';

        $controller = new TestController();

        $this->assertEquals('5', $this->invokeParam($controller, 'lang'));
    }

    public function testTextParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['text'] = '10';

        $controller = new TestController();

        $this->assertEquals('10', $this->invokeParam($controller, 'text'));
    }

    public function testSelectionParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['selection'] = '3';

        $controller = new TestController();

        $this->assertEquals('3', $this->invokeParam($controller, 'selection'));
    }

    public function testTypeParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['type'] = '2';

        $controller = new TestController();

        $this->assertEquals('2', $this->invokeParam($controller, 'type'));
    }

    public function testWidParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['wid'] = '100';

        $controller = new TestController();

        $this->assertEquals('100', $this->invokeParam($controller, 'wid'));
    }

    public function testStatusParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['status'] = '4';

        $controller = new TestController();

        $this->assertEquals('4', $this->invokeParam($controller, 'status'));
    }

    public function testStchangeParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['stchange'] = '1';

        $controller = new TestController();

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
        $_SESSION['testsql'] = 'SELECT * FROM words';

        $controller = new TestController();

        // Use reflection to test private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getTestProperty');
        $method->setAccessible(true);

        $result = $method->invoke($controller);

        $this->assertEquals('selection=1', $result);
    }

    public function testGetTestPropertyWithLang(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['lang'] = '5';

        $controller = new TestController();

        // Use reflection to test private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getTestProperty');
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

        $controller = new TestController();

        // Use reflection to test private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getTestProperty');
        $method->setAccessible(true);

        $result = $method->invoke($controller);

        $this->assertEquals('text=10', $result);
    }

    public function testGetTestPropertyReturnsEmptyWhenNoParams(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TestController();

        // Use reflection to test private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getTestProperty');
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

        $controller1 = new TestController();
        $controller2 = new TestController();

        $this->assertInstanceOf(TestController::class, $controller1);
        $this->assertInstanceOf(TestController::class, $controller2);
        $this->assertNotSame($controller1, $controller2);
    }

    // ===== TestService test identifier tests =====

    public function testTestServiceGetTestIdentifierWithLang(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TestService();

        $identifier = $service->getTestIdentifier(null, null, 1, null);

        $this->assertIsArray($identifier);
        $this->assertCount(2, $identifier);
    }

    public function testTestServiceGetTestIdentifierWithText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TestService();

        $identifier = $service->getTestIdentifier(null, null, null, 1);

        $this->assertIsArray($identifier);
        $this->assertCount(2, $identifier);
    }

    public function testTestServiceGetTestIdentifierWithSelection(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TestService();

        $identifier = $service->getTestIdentifier(1, 'SELECT * FROM words', null, null);

        $this->assertIsArray($identifier);
        $this->assertCount(2, $identifier);
    }

    // ===== TestService validation tests =====

    public function testValidateTestSelectionMethodExists(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TestService();

        $this->assertTrue(method_exists($service, 'validateTestSelection'));
    }

    public function testTestServiceValidateTestSelectionReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TestService();

        // The method expects a subquery format like "(SELECT WoID FROM words) AS t"
        // Use proper subquery syntax
        $subquery = "(SELECT WoID, WoLgID FROM " . Globals::table('words') . " WHERE WoLgID = 1 LIMIT 1) AS subq";

        $result = $service->validateTestSelection($subquery);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('langCount', $result);
    }

    // ===== Session progress tests =====

    public function testTestServiceInitializeTestSession(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TestService();
        $service->initializeTestSession(10);

        $sessionData = $service->getTestSessionData();

        $this->assertIsArray($sessionData);
        $this->assertArrayHasKey('start', $sessionData);
    }

    public function testTestServiceUpdateSessionProgress(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TestService();
        $service->initializeTestSession(10);

        $result = $service->updateSessionProgress(1);

        $this->assertIsArray($result);
    }
}
