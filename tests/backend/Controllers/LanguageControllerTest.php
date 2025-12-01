<?php declare(strict_types=1);
namespace Lwt\Tests\Controllers;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Controllers\LanguageController;
use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Services\LanguageService;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Controllers/BaseController.php';
require_once __DIR__ . '/../../../src/backend/Controllers/LanguageController.php';

/**
 * Unit tests for the LanguageController class.
 *
 * Tests controller initialization and service integration.
 * Note: Full integration tests for output rendering should use E2E tests.
 */
class LanguageControllerTest extends TestCase
{
    private static bool $dbConnected = false;
    private static string $tbpref = '';
    private array $originalRequest;
    private array $originalServer;
    private array $originalGet;
    private array $originalPost;

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

        if (self::$dbConnected) {
            // Reset auto_increment to prevent overflow (LgID is tinyint max 255)
            $tbpref = self::$tbpref;
            $maxId = Connection::fetchValue("SELECT COALESCE(MAX(LgID), 0) AS value FROM {$tbpref}languages");
            Connection::query("ALTER TABLE {$tbpref}languages AUTO_INCREMENT = " . ((int)$maxId + 1));
        }
    }

    protected function setUp(): void
    {
        // Save original superglobals
        $this->originalRequest = $_REQUEST;
        $this->originalServer = $_SERVER;
        $this->originalGet = $_GET;
        $this->originalPost = $_POST;

        // Reset superglobals
        $_REQUEST = [];
        $_SERVER = ['REQUEST_METHOD' => 'GET'];
        $_GET = [];
        $_POST = [];
    }

    protected function tearDown(): void
    {
        // Restore superglobals
        $_REQUEST = $this->originalRequest;
        $_SERVER = $this->originalServer;
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;

        if (!self::$dbConnected) {
            return;
        }

        // Clean up test languages
        $tbpref = self::$tbpref;
        Connection::query("DELETE FROM {$tbpref}languages WHERE LgName LIKE 'Test_%'");
        Connection::query("DELETE FROM {$tbpref}languages WHERE LgName LIKE 'TestLang%'");

        // Reset auto_increment to prevent overflow (LgID is tinyint max 255)
        $maxId = Connection::fetchValue("SELECT COALESCE(MAX(LgID), 0) AS value FROM {$tbpref}languages");
        Connection::query("ALTER TABLE {$tbpref}languages AUTO_INCREMENT = " . ((int)$maxId + 1));
    }

    /**
     * Helper to create a test language directly in the database.
     *
     * @param string $name Language name
     *
     * @return int The created language ID
     */
    private function createTestLanguage(string $name): int
    {
        $tbpref = self::$tbpref;
        Connection::query(
            "INSERT INTO {$tbpref}languages (
                LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI,
                LgTextSize, LgRegexpSplitSentences, LgRegexpWordCharacters,
                LgRemoveSpaces, LgSplitEachChar, LgRightToLeft, LgShowRomanization
            ) VALUES (
                '$name', 'https://dict.test/lwt_term', '', 'https://translate.test/lwt_term',
                100, '.!?', 'a-zA-Z',
                0, 0, 0, 1
            )"
        );
        return (int) mysqli_insert_id(Globals::getDbConnection());
    }

    // ===== Constructor tests =====

    public function testControllerCanBeInstantiated(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new LanguageController();

        $this->assertInstanceOf(LanguageController::class, $controller);
    }

    public function testControllerHasLanguageService(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new LanguageController();

        // Use reflection to check private property
        $reflection = new \ReflectionClass($controller);
        $property = $reflection->getProperty('languageService');
        $property->setAccessible(true);
        $service = $property->getValue($controller);

        $this->assertInstanceOf(LanguageService::class, $service);
    }

    // ===== Route parameter tests =====

    public function testIndexMethodExists(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new LanguageController();

        $this->assertTrue(method_exists($controller, 'index'));
    }

    public function testSelectPairMethodExists(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new LanguageController();

        $this->assertTrue(method_exists($controller, 'selectPair'));
    }

    // ===== Service delegation tests =====

    public function testControllerUsesLanguageServiceForGetAll(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create a test language to ensure there's data
        $this->createTestLanguage('TestLang_ServiceCheck');

        $controller = new LanguageController();

        // Get the service from the controller
        $reflection = new \ReflectionClass($controller);
        $property = $reflection->getProperty('languageService');
        $property->setAccessible(true);
        $service = $property->getValue($controller);

        $languages = $service->getAllLanguages();

        $this->assertArrayHasKey('TestLang_ServiceCheck', $languages);
    }

    // ===== Request handling tests =====

    public function testIndexAcceptsArrayParams(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new LanguageController();

        // Test that index() accepts an array parameter
        $reflection = new \ReflectionMethod($controller, 'index');
        $params = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertEquals('params', $params[0]->getName());
        $this->assertEquals('array', $params[0]->getType()->getName());
    }

    // ===== Service method delegation tests =====

    public function testServiceCreateMethodIsCalled(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new LanguageService();

        $_REQUEST = [
            'LgName' => 'TestLang_ControllerCreate',
            'LgDict1URI' => 'https://dict.test/lwt_term',
            'LgDict2URI' => '',
            'LgGoogleTranslateURI' => '',
            'LgExportTemplate' => '',
            'LgTextSize' => '100',
            'LgCharacterSubstitutions' => '',
            'LgRegexpSplitSentences' => '.!?',
            'LgExceptionsSplitSentences' => '',
            'LgRegexpWordCharacters' => 'a-zA-Z',
            'LgTTSVoiceAPI' => '',
        ];

        $result = $service->create();

        $this->assertStringContainsString('Saved', $result);
        $this->assertArrayHasKey('TestLang_ControllerCreate', $service->getAllLanguages());
    }

    public function testServiceUpdateMethodIsCalled(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguage('TestLang_ControllerUpdate');
        $service = new LanguageService();

        $_REQUEST = [
            'LgName' => 'TestLang_ControllerUpdated',
            'LgDict1URI' => 'https://newdict.test/lwt_term',
            'LgDict2URI' => '',
            'LgGoogleTranslateURI' => '',
            'LgExportTemplate' => '',
            'LgTextSize' => '150',
            'LgCharacterSubstitutions' => '',
            'LgRegexpSplitSentences' => '.!?',
            'LgExceptionsSplitSentences' => '',
            'LgRegexpWordCharacters' => 'a-zA-Z',
            'LgTTSVoiceAPI' => '',
        ];

        $result = $service->update($id);

        $this->assertStringContainsString('Updated', $result);
        $lang = $service->getById($id);
        $this->assertEquals('TestLang_ControllerUpdated', $lang->name());
    }

    public function testServiceDeleteMethodIsCalled(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguage('TestLang_ControllerDelete');

        // Clean up any related data that might exist for this language
        $tbpref = self::$tbpref;
        Connection::query("DELETE FROM {$tbpref}texts WHERE TxLgID = $id");
        Connection::query("DELETE FROM {$tbpref}archivedtexts WHERE AtLgID = $id");
        Connection::query("DELETE FROM {$tbpref}words WHERE WoLgID = $id");
        Connection::query("DELETE FROM {$tbpref}newsfeeds WHERE NfLgID = $id");

        $service = new LanguageService();

        $result = $service->delete($id);

        $this->assertStringContainsString('Deleted', $result);
        $this->assertFalse($service->exists($id));
    }

    public function testServiceRefreshMethodIsCalled(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguage('TestLang_ControllerRefresh');
        $service = new LanguageService();

        $result = $service->refresh($id);

        $this->assertStringContainsString('Sentences deleted', $result);
        $this->assertStringContainsString('Text items deleted', $result);
    }

    // ===== Integration tests with service =====

    public function testControllerServiceGetByIdWorks(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguage('TestLang_ControllerGetById');

        $controller = new LanguageController();
        $reflection = new \ReflectionClass($controller);
        $property = $reflection->getProperty('languageService');
        $property->setAccessible(true);
        $service = $property->getValue($controller);

        $lang = $service->getById($id);

        $this->assertEquals('TestLang_ControllerGetById', $lang->name());
        $this->assertEquals($id, $lang->id()->toInt());
    }

    public function testControllerServiceGetLanguagesWithStatsWorks(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestLanguage('TestLang_ControllerStats');

        $controller = new LanguageController();
        $reflection = new \ReflectionClass($controller);
        $property = $reflection->getProperty('languageService');
        $property->setAccessible(true);
        $service = $property->getValue($controller);

        $stats = $service->getLanguagesWithStats();

        $found = false;
        foreach ($stats as $lang) {
            if ($lang['name'] === 'TestLang_ControllerStats') {
                $found = true;
                $this->assertArrayHasKey('textCount', $lang);
                $this->assertArrayHasKey('wordCount', $lang);
                break;
            }
        }

        $this->assertTrue($found, 'Test language should be in stats');
    }

    // ===== Edge case tests =====

    public function testServiceRejectsEmptyLanguageName(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new LanguageService();

        // Empty name should throw an exception (database constraint)
        $_REQUEST = [
            'LgName' => '',
            'LgDict1URI' => 'https://dict.test/lwt_term',
            'LgDict2URI' => '',
            'LgGoogleTranslateURI' => '',
            'LgExportTemplate' => '',
            'LgTextSize' => '100',
            'LgCharacterSubstitutions' => '',
            'LgRegexpSplitSentences' => '.!?',
            'LgExceptionsSplitSentences' => '',
            'LgRegexpWordCharacters' => 'a-zA-Z',
            'LgTTSVoiceAPI' => '',
        ];

        $this->expectException(\RuntimeException::class);
        $service->create();
    }

    public function testServiceHandlesSpecialCharactersInName(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new LanguageService();

        $_REQUEST = [
            'LgName' => "TestLang_Special'Chars\"",
            'LgDict1URI' => 'https://dict.test/lwt_term',
            'LgDict2URI' => '',
            'LgGoogleTranslateURI' => '',
            'LgExportTemplate' => '',
            'LgTextSize' => '100',
            'LgCharacterSubstitutions' => '',
            'LgRegexpSplitSentences' => '.!?',
            'LgExceptionsSplitSentences' => '',
            'LgRegexpWordCharacters' => 'a-zA-Z',
            'LgTTSVoiceAPI' => '',
        ];

        $result = $service->create();

        $this->assertStringContainsString('Saved', $result);
    }

    public function testServiceHandlesUnicodeInName(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new LanguageService();

        $_REQUEST = [
            'LgName' => 'TestLang_日本語',
            'LgDict1URI' => 'https://dict.test/lwt_term',
            'LgDict2URI' => '',
            'LgGoogleTranslateURI' => '',
            'LgExportTemplate' => '',
            'LgTextSize' => '100',
            'LgCharacterSubstitutions' => '',
            'LgRegexpSplitSentences' => '.!?',
            'LgExceptionsSplitSentences' => '',
            'LgRegexpWordCharacters' => 'a-zA-Z',
            'LgTTSVoiceAPI' => '',
        ];

        $result = $service->create();

        $this->assertStringContainsString('Saved', $result);

        $langs = $service->getAllLanguages();
        $this->assertArrayHasKey('TestLang_日本語', $langs);
    }
}
