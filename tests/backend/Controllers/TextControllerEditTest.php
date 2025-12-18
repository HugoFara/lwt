<?php declare(strict_types=1);
namespace Lwt\Tests\Controllers;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Controllers\TextController;
use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Services\TextService;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use Lwt\Database\Settings;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Services/LanguageService.php';
require_once __DIR__ . '/../../../src/backend/Services/MediaService.php';
require_once __DIR__ . '/../../../src/backend/Controllers/BaseController.php';
require_once __DIR__ . '/../../../src/backend/Controllers/TextController.php';
require_once __DIR__ . '/../../../src/backend/Services/TextService.php';

/**
 * Unit tests for the TextController::edit() method and related functionality.
 *
 * Tests text editing interface, mark actions, and text list display.
 */
class TextControllerEditTest extends TestCase
{
    private static bool $dbConnected = false;
    private static int $testLangId = 0;
    private static int $testTextId = 0;
    private static int $testText2Id = 0;
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

        if (self::$dbConnected) {
            // Create a test language
            $existingLang = Connection::fetchValue(
                "SELECT LgID AS value FROM languages WHERE LgName = 'EditControllerTestLang' LIMIT 1"
            );

            if ($existingLang) {
                self::$testLangId = (int)$existingLang;
            } else {
                Connection::query(
                    "INSERT INTO languages (LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, " .
                    "LgTextSize, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, " .
                    "LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar, LgRightToLeft, LgShowRomanization) " .
                    "VALUES ('EditControllerTestLang', 'http://dict1.test/###', 'http://dict2.test/###', " .
                    "'http://translate.test/?sl=en&tl=fr&text=###', " .
                    "100, '', '.!?', '', 'a-zA-Z', 0, 0, 0, 1)"
                );
                self::$testLangId = (int)Connection::fetchValue(
                    "SELECT LAST_INSERT_ID() AS value"
                );
            }

            // Create first test text
            Connection::query(
                "INSERT INTO texts (TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, " .
                "TxSourceURI, TxAudioPosition, TxPosition) " .
                "VALUES (" . self::$testLangId . ", 'EditControllerTestText', 'Test content.', " .
                "'0\tTest\t\t*\n0\tcontent\t\ttranslation', " .
                "'http://audio.test/audio.mp3', 'http://source.test/article', 0, 0)"
            );
            self::$testTextId = (int)Connection::fetchValue(
                "SELECT LAST_INSERT_ID() AS value"
            );

            // Create second test text
            Connection::query(
                "INSERT INTO texts (TxLgID, TxTitle, TxText, TxAnnotatedText) " .
                "VALUES (" . self::$testLangId . ", 'EditControllerTestText2', 'Second test content.', '')"
            );
            self::$testText2Id = (int)Connection::fetchValue(
                "SELECT LAST_INSERT_ID() AS value"
            );
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        // Clean up test data
        Connection::query("DELETE FROM textitems2 WHERE Ti2TxID IN (" . self::$testTextId . ", " . self::$testText2Id . ")");
        Connection::query("DELETE FROM sentences WHERE SeTxID IN (" . self::$testTextId . ", " . self::$testText2Id . ")");
        Connection::query("DELETE FROM texts WHERE TxID IN (" . self::$testTextId . ", " . self::$testText2Id . ")");
        Connection::query("DELETE FROM languages WHERE LgName = 'EditControllerTestLang'");
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

    // ===== Controller instantiation tests =====

    public function testControllerCanBeInstantiated(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextController();

        $this->assertInstanceOf(TextController::class, $controller);
    }

    // ===== Method existence tests =====

    public function testControllerHasEditMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextController();

        $this->assertTrue(method_exists($controller, 'edit'));
    }

    public function testControllerHasDisplayMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextController();

        $this->assertTrue(method_exists($controller, 'display'));
    }

    public function testControllerHasPrintTextMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextController();

        $this->assertTrue(method_exists($controller, 'printText'));
    }

    public function testControllerHasPrintPlainMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextController();

        $this->assertTrue(method_exists($controller, 'printPlain'));
    }

    public function testControllerHasImportLongMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextController();

        $this->assertTrue(method_exists($controller, 'importLong'));
    }

    public function testControllerHasSetModeMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextController();

        $this->assertTrue(method_exists($controller, 'setMode'));
    }

    public function testControllerHasCheckMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextController();

        $this->assertTrue(method_exists($controller, 'check'));
    }

    public function testControllerHasArchivedMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextController();

        $this->assertTrue(method_exists($controller, 'archived'));
    }

    // ===== TextService CRUD tests =====

    public function testTextServiceGetTextById(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $text = $service->getTextById(self::$testTextId);

        $this->assertIsArray($text);
        $this->assertEquals('EditControllerTestText', $text['TxTitle']);
        $this->assertEquals(self::$testLangId, (int)$text['TxLgID']);
    }

    public function testTextServiceGetTextByIdReturnsNullForInvalidId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $text = $service->getTextById(999999);

        $this->assertNull($text);
    }

    public function testTextServiceGetTextForEdit(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $text = $service->getTextForEdit(self::$testTextId);

        $this->assertIsArray($text);
        $this->assertEquals('EditControllerTestText', $text['TxTitle']);
        $this->assertEquals('Test content.', $text['TxText']);
        $this->assertEquals('http://source.test/article', $text['TxSourceURI']);
        $this->assertEquals('http://audio.test/audio.mp3', $text['TxAudioURI']);
    }

    public function testTextServiceGetTextCount(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        // Count all texts
        $count = $service->getTextCount('', '', '');

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(2, $count); // At least our 2 test texts

        // Count with language filter
        $langCount = $service->getTextCount(' AND TxLgID = ' . self::$testLangId, '', '');

        $this->assertIsInt($langCount);
        $this->assertGreaterThanOrEqual(2, $langCount);
    }

    public function testTextServiceGetTextsList(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $texts = $service->getTextsList(
            ' AND TxLgID = ' . self::$testLangId,
            '',
            '',
            1, // sort
            1, // page
            50 // perPage
        );

        $this->assertIsArray($texts);
        $this->assertNotEmpty($texts);

        // Check that our test texts are in the list
        $titles = array_column($texts, 'TxTitle');
        $this->assertContains('EditControllerTestText', $titles);
    }

    public function testTextServiceValidateTextLength(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        // Short text should pass
        $this->assertTrue($service->validateTextLength('Short text'));

        // Very long text should fail
        $longText = str_repeat('a', 70000);
        $this->assertFalse($service->validateTextLength($longText));

        // Exactly at limit should pass
        $limitText = str_repeat('a', 64999);
        $this->assertTrue($service->validateTextLength($limitText));
    }

    // ===== handleMarkAction tests =====

    public function testHandleMarkActionWithEmptyMarkedArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextController();

        // Use reflection to test private method
        $method = new \ReflectionMethod(TextController::class, 'handleMarkAction');
        $method->setAccessible(true);

        $result = $method->invoke($controller, 'del', [], '');

        $this->assertEquals('Multiple Actions: 0', $result);
    }

    // ===== buildTextQueryWhereClause tests =====

    public function testBuildTextQueryWhereClauseWithEmptyQuery(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $result = $service->buildTextQueryWhereClause('', 'title,text', '');

        $this->assertEquals(['clause' => '', 'params' => []], $result);
    }

    public function testBuildTextQueryWhereClauseWithTitleQuery(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $result = $service->buildTextQueryWhereClause('test', 'title', '');

        $this->assertStringContainsString('TxTitle', $result['clause']);
        $this->assertStringContainsString('LIKE', $result['clause']);
        $this->assertEquals(['test'], $result['params']);
    }

    public function testBuildTextQueryWhereClauseWithTextQuery(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $result = $service->buildTextQueryWhereClause('test', 'text', '');

        $this->assertStringContainsString('TxText', $result['clause']);
        $this->assertStringContainsString('LIKE', $result['clause']);
        $this->assertEquals(['test'], $result['params']);
    }

    public function testBuildTextQueryWhereClauseWithTitleAndTextQuery(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $result = $service->buildTextQueryWhereClause('test', 'title,text', '');

        $this->assertStringContainsString('TxTitle', $result['clause']);
        $this->assertStringContainsString('TxText', $result['clause']);
        $this->assertStringContainsString('OR', $result['clause']);
        $this->assertEquals(['test', 'test'], $result['params']);
    }

    public function testBuildTextQueryWhereClauseWithRegexMode(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $result = $service->buildTextQueryWhereClause('test.*pattern', 'title', 'r');

        // MySQL uses RLIKE instead of REGEXP in some contexts
        $this->assertTrue(
            str_contains($result['clause'], 'REGEXP') || str_contains($result['clause'], 'rLIKE'),
            "Expected REGEXP or rLIKE in: {$result['clause']}"
        );
        $this->assertEquals(['test.*pattern'], $result['params']);
    }

    // ===== buildTextTagHavingClause tests =====

    public function testBuildTextTagHavingClauseWithNoTags(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $result = $service->buildTextTagHavingClause('', '', '');

        $this->assertEquals('', $result);
    }

    public function testBuildTextTagHavingClauseWithSingleTag(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $result = $service->buildTextTagHavingClause('1', '', '');

        $this->assertStringContainsString('HAVING', $result);
    }

    public function testBuildTextTagHavingClauseWithTwoTagsAnd(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $result = $service->buildTextTagHavingClause('1', '2', '1'); // 1 = AND

        $this->assertStringContainsString('HAVING', $result);
        $this->assertStringContainsString('AND', $result);
    }

    public function testBuildTextTagHavingClauseWithTwoTagsOr(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $result = $service->buildTextTagHavingClause('1', '2', '0'); // 0 = OR

        $this->assertStringContainsString('HAVING', $result);
        $this->assertStringContainsString('OR', $result);
    }

    public function testBuildTextTagHavingClauseWithUntagged(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $result = $service->buildTextTagHavingClause('-1', '', '');

        $this->assertStringContainsString('IS NULL', $result);
    }

    // ===== Pagination tests =====

    public function testGetPaginationWithEmptyCount(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $result = $service->getPagination(0, 1, 10);

        $this->assertEquals(0, $result['pages']);
        // Current page stays at 1 even with 0 results
        $this->assertEquals(1, $result['currentPage']);
    }

    public function testGetPaginationCalculatesCorrectly(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        // 100 items, 10 per page = 10 pages
        $result = $service->getPagination(100, 5, 10);

        $this->assertEquals(10, $result['pages']);
        $this->assertEquals(5, $result['currentPage']);
        $this->assertEquals('LIMIT 40,10', $result['limit']);
    }

    public function testGetPaginationClampsPageToMax(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        // Request page 100, but only 10 pages exist
        $result = $service->getPagination(100, 100, 10);

        $this->assertEquals(10, $result['currentPage']);
    }

    public function testGetPaginationClampsPageToMin(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        // Request page 0
        $result = $service->getPagination(100, 0, 10);

        $this->assertEquals(1, $result['currentPage']);
    }

    // ===== Regex validation tests =====

    public function testValidateRegexQueryWithValidRegex(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $this->assertTrue($service->validateRegexQuery('test.*pattern', 'r'));
        $this->assertTrue($service->validateRegexQuery('^start', 'r'));
        $this->assertTrue($service->validateRegexQuery('end$', 'r'));
        $this->assertTrue($service->validateRegexQuery('[a-z]+', 'r'));
    }

    public function testValidateRegexQueryWithInvalidRegex(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        // Unbalanced brackets
        $this->assertFalse($service->validateRegexQuery('[a-z', 'r'));
        // Invalid escape
        $this->assertFalse($service->validateRegexQuery('\\', 'r'));
    }

    public function testValidateRegexQueryWithEmptyRegexMode(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        // Empty regex mode should always return true
        $this->assertTrue($service->validateRegexQuery('any query', ''));
    }

    // ===== Language data tests =====

    public function testGetLanguageDataForForm(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $result = $service->getLanguageDataForForm();

        $this->assertIsArray($result);

        // Check that our test language is included (keyed by LgID)
        $this->assertArrayHasKey(self::$testLangId, $result);
    }

    public function testGetLanguageTranslateUris(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $result = $service->getLanguageTranslateUris();

        $this->assertIsArray($result);
        $this->assertArrayHasKey(self::$testLangId, $result);
        $this->assertStringContainsString('translate.test', $result[self::$testLangId]);
    }

    // ===== Long text preparation tests =====

    public function testPrepareLongTextDataFromText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        // Test with simple text
        $result = $service->prepareLongTextData([], "Test text content.", 1);

        $this->assertIsString($result);
        $this->assertEquals("Test text content.", $result);
    }

    public function testPrepareLongTextDataWithParagraphHandling(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        // Test with paragraph handling mode 2 (keep paragraphs)
        $text = "Para 1.\n\nPara 2.";
        $result = $service->prepareLongTextData([], $text, 2);

        $this->assertIsString($result);
        $this->assertStringContainsString("\n", $result);
    }

    public function testSplitLongText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        // Test splitting text into chunks
        $text = "First sentence. Second sentence. Third sentence. Fourth sentence. Fifth sentence.";
        $result = $service->splitLongText($text, self::$testLangId, 2);

        $this->assertIsArray($result);
        // Should split into multiple chunks based on maxSent parameter
    }

    // ===== Text per page settings tests =====

    public function testGetTextsPerPage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $result = $service->getTextsPerPage();

        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    // ===== Multiple controller tests =====

    public function testMultipleControllerInstances(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller1 = new TextController();
        $controller2 = new TextController();

        $this->assertInstanceOf(TextController::class, $controller1);
        $this->assertInstanceOf(TextController::class, $controller2);
        $this->assertNotSame($controller1, $controller2);
    }

    // ===== Settings integration tests =====

    public function testSettingsIntegration(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Test that settings can be retrieved
        $showCounts = Settings::getWithDefault('set-show-text-word-counts');
        $this->assertIsString($showCounts);

        $regexMode = Settings::getWithDefault('set-regex-mode');
        $this->assertIsString($regexMode);
    }
}
