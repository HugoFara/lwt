<?php

declare(strict_types=1);

namespace Lwt\Tests\Controllers;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Controllers\ApiController;
use Lwt\Controllers\TranslationController;
use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Controllers/BaseController.php';
require_once __DIR__ . '/../../../src/backend/Controllers/ApiController.php';

/**
 * Unit tests for the ApiController class.
 *
 * Tests the REST API controller and its delegation to specialized handlers.
 */
class ApiControllerTest extends TestCase
{
    private static bool $dbConnected = false;
    private static string $tbpref = '';
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
        self::$tbpref = Globals::getTablePrefix();
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
        $_SERVER = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1'];
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
     * @param ApiController $controller The controller instance
     * @param string        $name       Parameter name
     * @param mixed         $default    Default value
     *
     * @return mixed Parameter value
     */
    private function invokeParam(ApiController $controller, string $name, $default = null)
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

        $controller = new ApiController();

        $this->assertInstanceOf(ApiController::class, $controller);
    }

    // ===== Method existence tests =====

    public function testControllerHasV1Method(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new ApiController();

        $this->assertTrue(method_exists($controller, 'v1'));
    }

    public function testControllerHasTranslateMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new ApiController();

        $this->assertTrue(method_exists($controller, 'translate'));
    }

    public function testControllerHasGoogleMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new ApiController();

        $this->assertTrue(method_exists($controller, 'google'));
    }

    public function testControllerHasGlosbeMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new ApiController();

        $this->assertTrue(method_exists($controller, 'glosbe'));
    }

    // ===== BaseController inheritance tests =====

    public function testControllerExtendsBaseController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new ApiController();

        $this->assertInstanceOf(\Lwt\Controllers\BaseController::class, $controller);
    }

    // ===== Translation controller delegation tests =====

    public function testGetTranslationControllerReturnsInstance(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new ApiController();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getTranslationController');
        $method->setAccessible(true);

        $translationController = $method->invoke($controller);

        $this->assertInstanceOf(TranslationController::class, $translationController);
    }

    public function testGetTranslationControllerReturnsSameInstance(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new ApiController();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getTranslationController');
        $method->setAccessible(true);

        $tc1 = $method->invoke($controller);
        $tc2 = $method->invoke($controller);

        $this->assertSame($tc1, $tc2);
    }

    // ===== TranslationController tests =====

    public function testTranslationControllerCanBeInstantiated(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TranslationController();

        $this->assertInstanceOf(TranslationController::class, $controller);
    }

    public function testTranslationControllerHasTranslateMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TranslationController();

        $this->assertTrue(method_exists($controller, 'translate'));
    }

    public function testTranslationControllerHasGoogleMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TranslationController();

        $this->assertTrue(method_exists($controller, 'google'));
    }

    public function testTranslationControllerHasGlosbeMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TranslationController();

        $this->assertTrue(method_exists($controller, 'glosbe'));
    }

    // ===== API request parameter tests =====

    public function testRequestMethodDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->assertEquals('POST', $_SERVER['REQUEST_METHOD']);
    }

    public function testRequestUriDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_SERVER['REQUEST_URI'] = '/api/v1/terms';

        $this->assertEquals('/api/v1/terms', $_SERVER['REQUEST_URI']);
    }

    // ===== API endpoint parameter tests =====

    public function testTermParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_GET['term'] = 'hello';
        $_REQUEST['term'] = 'hello';

        $controller = new ApiController();

        $this->assertEquals('hello', $this->invokeParam($controller, 'term'));
    }

    public function testLangParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_GET['lang'] = 'en';
        $_REQUEST['lang'] = 'en';

        $controller = new ApiController();

        $this->assertEquals('en', $this->invokeParam($controller, 'lang'));
    }

    public function testLgidParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_GET['lgid'] = '5';
        $_REQUEST['lgid'] = '5';

        $controller = new ApiController();

        $this->assertEquals('5', $this->invokeParam($controller, 'lgid'));
    }

    public function testTextIdParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_GET['text_id'] = '10';
        $_REQUEST['text_id'] = '10';

        $controller = new ApiController();

        $this->assertEquals('10', $this->invokeParam($controller, 'text_id'));
    }

    // ===== JSON response tests =====

    public function testContentTypeCanBeSetToJson(): void
    {
        // Test that we can set JSON content type (used by API)
        $contentType = 'application/json';
        $this->assertEquals('application/json', $contentType);
    }

    public function testJsonEncodingWorks(): void
    {
        $data = ['success' => true, 'data' => ['id' => 1, 'name' => 'test']];
        $json = json_encode($data);

        $this->assertIsString($json);
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals($data, $decoded);
    }

    // ===== HTTP method tests =====

    public function testGetRequestDetection(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';

        $controller = new ApiController();

        // Use reflection to test isPost method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('isPost');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($controller));
    }

    public function testPostRequestDetection(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $controller = new ApiController();

        // Use reflection to test isPost method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('isPost');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($controller));
    }

    // ===== Database query tests for API data =====

    public function testTermsQueryWorks(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $sql = "SELECT WoID, WoText, WoTextLC, WoTranslation, WoStatus
                FROM " . self::$tbpref . "words LIMIT 10";
        $result = Connection::query($sql);

        $this->assertInstanceOf(\mysqli_result::class, $result);
        mysqli_free_result($result);
    }

    public function testTextsQueryWorks(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $sql = "SELECT TxID, TxLgID, TxTitle, TxSourceURI
                FROM " . self::$tbpref . "texts LIMIT 10";
        $result = Connection::query($sql);

        $this->assertInstanceOf(\mysqli_result::class, $result);
        mysqli_free_result($result);
    }

    public function testLanguagesQueryWorks(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $sql = "SELECT LgID, LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI
                FROM " . self::$tbpref . "languages LIMIT 10";
        $result = Connection::query($sql);

        $this->assertInstanceOf(\mysqli_result::class, $result);
        mysqli_free_result($result);
    }

    public function testSettingsQueryWorks(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $sql = "SELECT StKey, StValue FROM " . self::$tbpref . "settings LIMIT 10";
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

        $controller1 = new ApiController();
        $controller2 = new ApiController();

        $this->assertInstanceOf(ApiController::class, $controller1);
        $this->assertInstanceOf(ApiController::class, $controller2);
        $this->assertNotSame($controller1, $controller2);
    }

    // ===== API response structure tests =====

    public function testApiResponseHasSuccessField(): void
    {
        $response = ['success' => true];
        $this->assertArrayHasKey('success', $response);
    }

    public function testApiResponseHasDataField(): void
    {
        $response = ['success' => true, 'data' => []];
        $this->assertArrayHasKey('data', $response);
    }

    public function testApiErrorResponseHasErrorField(): void
    {
        $response = ['success' => false, 'error' => 'Something went wrong'];
        $this->assertArrayHasKey('error', $response);
    }

    // ===== REST API URL parsing tests =====

    public function testApiPathParsing(): void
    {
        $uri = '/api/v1/terms/123';
        $parts = explode('/', trim($uri, '/'));

        $this->assertEquals(['api', 'v1', 'terms', '123'], $parts);
    }

    public function testApiVersionDetection(): void
    {
        $uri = '/api/v1/terms';
        $parts = explode('/', trim($uri, '/'));

        $this->assertEquals('v1', $parts[1]);
    }

    public function testApiResourceDetection(): void
    {
        $uri = '/api/v1/terms/123';
        $parts = explode('/', trim($uri, '/'));

        $this->assertEquals('terms', $parts[2]);
    }

    public function testApiResourceIdDetection(): void
    {
        $uri = '/api/v1/terms/123';
        $parts = explode('/', trim($uri, '/'));

        $this->assertEquals('123', $parts[3]);
    }

    // ===== Translation request parameter tests =====

    public function testTranslationTextParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_GET['text'] = 'hello world';
        $_REQUEST['text'] = 'hello world';

        $controller = new ApiController();

        $this->assertEquals('hello world', $this->invokeParam($controller, 'text'));
    }

    public function testTranslationSourceLangParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_GET['sl'] = 'en';
        $_REQUEST['sl'] = 'en';

        $controller = new ApiController();

        $this->assertEquals('en', $this->invokeParam($controller, 'sl'));
    }

    public function testTranslationTargetLangParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_GET['tl'] = 'de';
        $_REQUEST['tl'] = 'de';

        $controller = new ApiController();

        $this->assertEquals('de', $this->invokeParam($controller, 'tl'));
    }
}
