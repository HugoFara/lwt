<?php

declare(strict_types=1);

namespace Lwt\Tests\Controllers;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Controllers\MobileController;
use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use Lwt\Services\MobileService;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Controllers/BaseController.php';
require_once __DIR__ . '/../../../src/backend/Controllers/MobileController.php';
require_once __DIR__ . '/../../../src/backend/Services/MobileService.php';

/**
 * Unit tests for the MobileController class.
 *
 * Tests the mobile interface controller and MobileService integration.
 */
class MobileControllerTest extends TestCase
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
     * @param MobileController $controller The controller instance
     * @param string           $name       Parameter name
     * @param mixed            $default    Default value
     *
     * @return mixed Parameter value
     */
    private function invokeParam(MobileController $controller, string $name, $default = null)
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

        $controller = new MobileController();

        $this->assertInstanceOf(MobileController::class, $controller);
    }

    // ===== Method existence tests =====

    public function testControllerHasIndexMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new MobileController();

        $this->assertTrue(method_exists($controller, 'index'));
    }

    public function testControllerHasStartMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new MobileController();

        $this->assertTrue(method_exists($controller, 'start'));
    }

    // ===== BaseController inheritance tests =====

    public function testControllerExtendsBaseController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new MobileController();

        $this->assertInstanceOf(\Lwt\Controllers\BaseController::class, $controller);
    }

    // ===== MobileService tests =====

    public function testMobileServiceCanBeInstantiated(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new MobileService();

        $this->assertInstanceOf(MobileService::class, $service);
    }

    public function testMobileServiceGetLanguages(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new MobileService();
        $languages = $service->getLanguages();

        $this->assertIsArray($languages);
    }

    public function testMobileServiceGetVersion(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new MobileService();
        $version = $service->getVersion();

        $this->assertIsString($version);
        $this->assertNotEmpty($version);
    }

    public function testMobileServiceGetLanguageNameReturnsNullForInvalidId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new MobileService();
        $name = $service->getLanguageName(999999);

        $this->assertNull($name);
    }

    public function testMobileServiceGetTextByIdReturnsNullForInvalidId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new MobileService();
        $text = $service->getTextById(999999);

        $this->assertNull($text);
    }

    public function testMobileServiceGetSentenceByIdReturnsNullForInvalidId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new MobileService();
        $sentence = $service->getSentenceById(999999);

        $this->assertNull($sentence);
    }

    public function testMobileServiceGetTextsByLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new MobileService();
        $texts = $service->getTextsByLanguage(1);

        $this->assertIsArray($texts);
    }

    public function testMobileServiceGetSentencesByText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new MobileService();
        $sentences = $service->getSentencesByText(1);

        $this->assertIsArray($sentences);
    }

    public function testMobileServiceGetTermsBySentence(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new MobileService();
        $terms = $service->getTermsBySentence(1);

        $this->assertIsArray($terms);
    }

    public function testMobileServiceGetNextSentenceIdReturnsNullWhenNoNext(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new MobileService();
        $nextId = $service->getNextSentenceId(999999, 999999);

        $this->assertNull($nextId);
    }

    // ===== Action parameter tests =====

    public function testActionNullShowsMainPage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new MobileController();

        // No action set should show main page
        $this->assertNull($this->invokeParam($controller, 'action'));
    }

    public function testAction1ShowsLanguageMenu(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['action'] = '1';

        $controller = new MobileController();

        $this->assertEquals('1', $this->invokeParam($controller, 'action'));
    }

    public function testAction2ShowsTextsList(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['action'] = '2';

        $controller = new MobileController();

        $this->assertEquals('2', $this->invokeParam($controller, 'action'));
    }

    public function testAction3ShowsSentencesList(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['action'] = '3';

        $controller = new MobileController();

        $this->assertEquals('3', $this->invokeParam($controller, 'action'));
    }

    public function testAction4ShowsTermsList(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['action'] = '4';

        $controller = new MobileController();

        $this->assertEquals('4', $this->invokeParam($controller, 'action'));
    }

    public function testAction5ShowsTermsListNext(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['action'] = '5';

        $controller = new MobileController();

        $this->assertEquals('5', $this->invokeParam($controller, 'action'));
    }

    // ===== Language param tests =====

    public function testLangParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['lang'] = '5';

        $controller = new MobileController();

        $this->assertEquals('5', $this->invokeParam($controller, 'lang'));
    }

    // ===== Text param tests =====

    public function testTextParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['text'] = '10';

        $controller = new MobileController();

        $this->assertEquals('10', $this->invokeParam($controller, 'text'));
    }

    // ===== Sentence param tests =====

    public function testSentParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['sent'] = '15';

        $controller = new MobileController();

        $this->assertEquals('15', $this->invokeParam($controller, 'sent'));
    }

    // ===== Start method tests =====

    public function testStartMethodHandlesGetRequest(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';

        $controller = new MobileController();

        // Use reflection to test isPost method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('isPost');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($controller));
    }

    public function testStartMethodHandlesPostRequest(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $controller = new MobileController();

        // Use reflection to test isPost method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('isPost');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($controller));
    }

    public function testPrefixParamDetectedForTableSetSelection(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['prefix'] = 'test_';

        $controller = new MobileController();

        $this->assertEquals('test_', $this->invokeParam($controller, 'prefix'));
    }

    // ===== Database query tests =====

    public function testLanguagesQueryWorks(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $sql = "SELECT LgID, LgName FROM " . self::$tbpref . "languages ORDER BY LgName";
        $result = Connection::query($sql);

        $this->assertInstanceOf(\mysqli_result::class, $result);
        mysqli_free_result($result);
    }

    public function testTextsQueryWorks(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $sql = "SELECT TxID, TxTitle FROM " . self::$tbpref . "texts ORDER BY TxTitle LIMIT 10";
        $result = Connection::query($sql);

        $this->assertInstanceOf(\mysqli_result::class, $result);
        mysqli_free_result($result);
    }

    public function testSentencesQueryWorks(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $sql = "SELECT SeID, SeTxID, SeOrder FROM " . self::$tbpref . "sentences ORDER BY SeID LIMIT 10";
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

        $controller1 = new MobileController();
        $controller2 = new MobileController();

        $this->assertInstanceOf(MobileController::class, $controller1);
        $this->assertInstanceOf(MobileController::class, $controller2);
        $this->assertNotSame($controller1, $controller2);
    }

    // ===== Default action values =====

    public function testDefaultLanguageIdIsZero(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new MobileController();
        $langId = (int) $this->invokeParam($controller, 'lang', 0);

        $this->assertEquals(0, $langId);
    }

    public function testDefaultTextIdIsZero(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new MobileController();
        $textId = (int) $this->invokeParam($controller, 'text', 0);

        $this->assertEquals(0, $textId);
    }

    public function testDefaultSentIdIsZero(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new MobileController();
        $sentId = (int) $this->invokeParam($controller, 'sent', 0);

        $this->assertEquals(0, $sentId);
    }
}
