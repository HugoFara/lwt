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
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Core/Http/param_helpers.php';
require_once __DIR__ . '/../../../src/backend/Services/LanguageService.php';
require_once __DIR__ . '/../../../src/backend/Services/TextStatisticsService.php';
require_once __DIR__ . '/../../../src/backend/Services/SentenceService.php';
require_once __DIR__ . '/../../../src/backend/Services/AnnotationService.php';
require_once __DIR__ . '/../../../src/backend/Services/SimilarTermsService.php';
require_once __DIR__ . '/../../../src/backend/Services/TextNavigationService.php';
require_once __DIR__ . '/../../../src/backend/Services/TextParsingService.php';
require_once __DIR__ . '/../../../src/backend/Services/ExpressionService.php';
require_once __DIR__ . '/../../../src/backend/Core/Database/Restore.php';
require_once __DIR__ . '/../../../src/backend/Controllers/BaseController.php';
require_once __DIR__ . '/../../../src/backend/Controllers/TextController.php';
require_once __DIR__ . '/../../../src/backend/Services/TextService.php';

/**
 * Unit tests for the TextController::archived() method and related functionality.
 *
 * Tests archived text listing, editing, unarchiving, and bulk operations.
 */
class TextControllerArchivedTest extends TestCase
{
    private static bool $dbConnected = false;
    private static string $tbpref = '';
    private static int $testLangId = 0;
    private static int $testArchivedTextId = 0;
    private static int $testArchivedText2Id = 0;
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
        self::$tbpref = Globals::getTablePrefix();

        if (self::$dbConnected) {
            $tbpref = self::$tbpref;

            // Create a test language if it doesn't exist
            $existingLang = Connection::fetchValue(
                "SELECT LgID AS value FROM {$tbpref}languages WHERE LgName = 'ArchivedTestLang' LIMIT 1"
            );

            if ($existingLang) {
                self::$testLangId = (int)$existingLang;
            } else {
                Connection::query(
                    "INSERT INTO {$tbpref}languages (LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, " .
                    "LgTextSize, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, " .
                    "LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar, LgRightToLeft, LgShowRomanization) " .
                    "VALUES ('ArchivedTestLang', 'http://dict1.test/###', 'http://dict2.test/###', " .
                    "'http://translate.test/?sl=en&tl=fr&text=###', " .
                    "100, '', '.!?', '', 'a-zA-Z', 0, 0, 0, 1)"
                );
                self::$testLangId = (int)Connection::fetchValue(
                    "SELECT LAST_INSERT_ID() AS value"
                );
            }

            // Create first archived test text
            Connection::query(
                "INSERT INTO {$tbpref}archivedtexts (AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI) " .
                "VALUES (" . self::$testLangId . ", 'ArchivedTestText', 'Test archived content.', " .
                "'0\tTest\t\t*\n0\tarchived\t\ttranslation', " .
                "'http://audio.test/audio.mp3', 'http://source.test/article')"
            );
            self::$testArchivedTextId = (int)Connection::fetchValue(
                "SELECT LAST_INSERT_ID() AS value"
            );

            // Create second archived test text
            Connection::query(
                "INSERT INTO {$tbpref}archivedtexts (AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI) " .
                "VALUES (" . self::$testLangId . ", 'ArchivedTestText2', 'Second archived content.', " .
                "'0\tSecond\t\t*', " .
                "'', '')"
            );
            self::$testArchivedText2Id = (int)Connection::fetchValue(
                "SELECT LAST_INSERT_ID() AS value"
            );
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$dbConnected && self::$testLangId > 0) {
            $tbpref = self::$tbpref;

            // Clean up archived texts
            if (self::$testArchivedTextId > 0) {
                Connection::query(
                    "DELETE FROM {$tbpref}archtexttags WHERE AgAtID = " . self::$testArchivedTextId
                );
                Connection::query(
                    "DELETE FROM {$tbpref}archivedtexts WHERE AtID = " . self::$testArchivedTextId
                );
            }
            if (self::$testArchivedText2Id > 0) {
                Connection::query(
                    "DELETE FROM {$tbpref}archtexttags WHERE AgAtID = " . self::$testArchivedText2Id
                );
                Connection::query(
                    "DELETE FROM {$tbpref}archivedtexts WHERE AtID = " . self::$testArchivedText2Id
                );
            }

            // Clean up test language
            Connection::query("DELETE FROM {$tbpref}languages WHERE LgID = " . self::$testLangId);
        }
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
        $_SERVER = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/text/archived'];
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

    // ===== Controller instantiation tests =====

    public function testControllerCanBeInstantiated(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextController();

        $this->assertInstanceOf(TextController::class, $controller);
    }

    public function testControllerHasArchivedMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextController();

        $this->assertTrue(method_exists($controller, 'archived'));
    }

    // ===== TextService archived methods tests =====

    public function testGetArchivedTextById(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $result = $service->getArchivedTextById(self::$testArchivedTextId);

        $this->assertIsArray($result);
        $this->assertEquals('ArchivedTestText', $result['AtTitle']);
        $this->assertEquals('Test archived content.', $result['AtText']);
    }

    public function testGetArchivedTextByIdNotFound(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $result = $service->getArchivedTextById(999999);

        $this->assertNull($result);
    }

    public function testGetArchivedTextCount(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $count = $service->getArchivedTextCount('', '', '');

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(2, $count);
    }

    public function testGetArchivedTextCountWithLanguageFilter(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $count = $service->getArchivedTextCount(' and AtLgID=' . self::$testLangId, '', '');

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(2, $count);
    }

    public function testGetArchivedTextsList(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $texts = $service->getArchivedTextsList('', '', '', 1, 1, 10);

        $this->assertIsArray($texts);
        $this->assertNotEmpty($texts);

        // Check first text has required keys
        $firstText = $texts[0];
        $this->assertArrayHasKey('AtID', $firstText);
        $this->assertArrayHasKey('AtTitle', $firstText);
        $this->assertArrayHasKey('LgName', $firstText);
    }

    public function testGetArchivedTextsListWithSort(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        // Sort by title ascending
        $texts = $service->getArchivedTextsList(' and AtLgID=' . self::$testLangId, '', '', 1, 1, 10);

        $this->assertIsArray($texts);
        $this->assertNotEmpty($texts);
    }

    public function testGetArchivedTextsPerPage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $perPage = $service->getArchivedTextsPerPage();

        $this->assertIsInt($perPage);
        $this->assertGreaterThan(0, $perPage);
    }

    // ===== buildArchivedQueryWhereClause tests =====

    public function testBuildArchivedQueryWhereClauseEmpty(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $result = $service->buildArchivedQueryWhereClause('', 'title', '');

        $this->assertEquals('', $result);
    }

    public function testBuildArchivedQueryWhereClauseWithTitle(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $result = $service->buildArchivedQueryWhereClause('test', 'title', '');

        $this->assertStringContainsString('AtTitle', $result);
        $this->assertStringContainsString('test', $result);
    }

    public function testBuildArchivedQueryWhereClauseWithText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $result = $service->buildArchivedQueryWhereClause('content', 'text', '');

        $this->assertStringContainsString('AtText', $result);
        $this->assertStringContainsString('content', $result);
    }

    public function testBuildArchivedQueryWhereClauseWithTitleAndText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $result = $service->buildArchivedQueryWhereClause('search', 'title,text', '');

        $this->assertStringContainsString('AtTitle', $result);
        $this->assertStringContainsString('AtText', $result);
    }

    // ===== buildArchivedTagHavingClause tests =====

    public function testBuildArchivedTagHavingClauseEmpty(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $result = $service->buildArchivedTagHavingClause('', '', '');

        $this->assertEquals('', $result);
    }

    // ===== Archived text CRUD tests =====

    public function testUpdateArchivedText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();
        $tbpref = self::$tbpref;

        // Create a temporary archived text to update
        Connection::query(
            "INSERT INTO {$tbpref}archivedtexts (AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI) " .
            "VALUES (" . self::$testLangId . ", 'TempUpdateTest', 'Temp content.', '', '', '')"
        );
        $tempId = (int)Connection::fetchValue("SELECT LAST_INSERT_ID() AS value");

        $message = $service->updateArchivedText(
            $tempId,
            self::$testLangId,
            'Updated Title',
            'Updated content.',
            'http://audio.test/updated.mp3',
            'http://source.test/updated'
        );

        $this->assertIsString($message);

        // Verify update
        $updated = $service->getArchivedTextById($tempId);
        $this->assertEquals('Updated Title', $updated['AtTitle']);
        $this->assertEquals('Updated content.', $updated['AtText']);

        // Cleanup
        Connection::query("DELETE FROM {$tbpref}archivedtexts WHERE AtID = {$tempId}");
    }

    public function testDeleteArchivedText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();
        $tbpref = self::$tbpref;

        // Create a temporary archived text to delete
        Connection::query(
            "INSERT INTO {$tbpref}archivedtexts (AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI) " .
            "VALUES (" . self::$testLangId . ", 'TempDeleteTest', 'Temp content.', '', '', '')"
        );
        $tempId = (int)Connection::fetchValue("SELECT LAST_INSERT_ID() AS value");

        $message = $service->deleteArchivedText($tempId);

        $this->assertIsString($message);

        // Verify deletion
        $this->assertNull($service->getArchivedTextById($tempId));
    }

    public function testDeleteArchivedTexts(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();
        $tbpref = self::$tbpref;

        // Create temporary archived texts to delete
        Connection::query(
            "INSERT INTO {$tbpref}archivedtexts (AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI) " .
            "VALUES (" . self::$testLangId . ", 'TempMultiDel1', 'Temp1.', '', '', '')"
        );
        $tempId1 = (int)Connection::fetchValue("SELECT LAST_INSERT_ID() AS value");

        Connection::query(
            "INSERT INTO {$tbpref}archivedtexts (AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI) " .
            "VALUES (" . self::$testLangId . ", 'TempMultiDel2', 'Temp2.', '', '', '')"
        );
        $tempId2 = (int)Connection::fetchValue("SELECT LAST_INSERT_ID() AS value");

        $message = $service->deleteArchivedTexts([$tempId1, $tempId2]);

        $this->assertIsString($message);
        $this->assertStringContainsString('2', $message);

        // Verify deletion
        $this->assertNull($service->getArchivedTextById($tempId1));
        $this->assertNull($service->getArchivedTextById($tempId2));
    }

    // ===== Unarchive tests =====

    public function testUnarchiveText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();
        $tbpref = self::$tbpref;

        // Create a temporary archived text to unarchive
        Connection::query(
            "INSERT INTO {$tbpref}archivedtexts (AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI) " .
            "VALUES (" . self::$testLangId . ", 'TempUnarchiveTest', 'Temp content.', " .
            "'0\tTemp\t\t*', 'http://audio.test/temp.mp3', 'http://source.test/temp')"
        );
        $tempArchivedId = (int)Connection::fetchValue("SELECT LAST_INSERT_ID() AS value");

        $result = $service->unarchiveText($tempArchivedId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);

        // The archived text should be gone
        $this->assertNull($service->getArchivedTextById($tempArchivedId));

        // Find and clean up the restored text
        $restoredId = Connection::fetchValue(
            "SELECT TxID AS value FROM {$tbpref}texts WHERE TxTitle = 'TempUnarchiveTest' LIMIT 1"
        );
        if ($restoredId) {
            Connection::query("DELETE FROM {$tbpref}textitems2 WHERE Ti2TxID = {$restoredId}");
            Connection::query("DELETE FROM {$tbpref}sentences WHERE SeTxID = {$restoredId}");
            Connection::query("DELETE FROM {$tbpref}texts WHERE TxID = {$restoredId}");
        }
    }

    public function testUnarchiveTexts(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();
        $tbpref = self::$tbpref;

        // Create temporary archived texts to unarchive
        Connection::query(
            "INSERT INTO {$tbpref}archivedtexts (AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI) " .
            "VALUES (" . self::$testLangId . ", 'TempMultiUnarch1', 'Temp1.', '0\tTemp1\t\t*', '', '')"
        );
        $tempId1 = (int)Connection::fetchValue("SELECT LAST_INSERT_ID() AS value");

        Connection::query(
            "INSERT INTO {$tbpref}archivedtexts (AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI) " .
            "VALUES (" . self::$testLangId . ", 'TempMultiUnarch2', 'Temp2.', '0\tTemp2\t\t*', '', '')"
        );
        $tempId2 = (int)Connection::fetchValue("SELECT LAST_INSERT_ID() AS value");

        $message = $service->unarchiveTexts([$tempId1, $tempId2]);

        $this->assertIsString($message);
        // Message format is "Unarchived Text(s): N" (capital U)
        $this->assertStringContainsString('Unarchived', $message);

        // Verify archived texts are gone
        $this->assertNull($service->getArchivedTextById($tempId1));
        $this->assertNull($service->getArchivedTextById($tempId2));

        // Clean up restored texts
        $restoredIds = [];
        $res = Connection::query(
            "SELECT TxID FROM {$tbpref}texts WHERE TxTitle LIKE 'TempMultiUnarch%'"
        );
        while ($row = mysqli_fetch_assoc($res)) {
            $restoredIds[] = (int)$row['TxID'];
        }
        mysqli_free_result($res);

        foreach ($restoredIds as $restoredId) {
            Connection::query("DELETE FROM {$tbpref}textitems2 WHERE Ti2TxID = {$restoredId}");
            Connection::query("DELETE FROM {$tbpref}sentences WHERE SeTxID = {$restoredId}");
            Connection::query("DELETE FROM {$tbpref}texts WHERE TxID = {$restoredId}");
        }
    }

    // ===== Validation tests =====

    public function testValidationClassHasArchTextTagMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->assertTrue(class_exists('Lwt\\Database\\Validation'));
        $this->assertTrue(method_exists('Lwt\\Database\\Validation', 'archTextTag'));
    }

    public function testValidationArchTextTagWithEmpty(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = \Lwt\Database\Validation::archTextTag('', '');

        $this->assertEquals('', $result);
    }

    // ===== Sort order tests =====

    public function testArchivedSortOrdersExist(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        // Test different sort orders (1-4)
        foreach ([1, 2, 3, 4] as $sort) {
            $texts = $service->getArchivedTextsList('', '', '', $sort, 1, 10);
            $this->assertIsArray($texts);
        }
    }

    // ===== Settings integration tests =====

    public function testSettingsRegexModeDefault(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $regexMode = Settings::getWithDefault('set-regex-mode');

        $this->assertIsString($regexMode);
    }

    // ===== Pagination tests for archived texts =====

    public function testGetPaginationForArchivedTexts(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        $totalCount = $service->getArchivedTextCount('', '', '');
        $perPage = $service->getArchivedTextsPerPage();
        $pagination = $service->getPagination($totalCount, 1, $perPage);

        $this->assertArrayHasKey('pages', $pagination);
        $this->assertArrayHasKey('currentPage', $pagination);
        $this->assertArrayHasKey('limit', $pagination);
    }

    // ===== Query mode tests =====

    public function testArchivedQueryModeTitleText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        // Test title,text mode (default)
        $result = $service->buildArchivedQueryWhereClause('content', 'title,text', '');

        $this->assertStringContainsString('AtTitle', $result);
        $this->assertStringContainsString('AtText', $result);
        $this->assertStringContainsString('OR', $result);
    }

    public function testArchivedQueryModeWithRegex(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextService();

        // Test regex mode
        $result = $service->buildArchivedQueryWhereClause('test.*', 'title', 'r');

        // MySQL uses RLIKE instead of REGEXP in some contexts
        $this->assertTrue(
            str_contains($result, 'REGEXP') || str_contains($result, 'rLIKE'),
            "Expected REGEXP or rLIKE in: $result"
        );
    }

    // ===== Concurrent access simulation tests =====

    public function testMultipleServiceInstances(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service1 = new TextService();
        $service2 = new TextService();

        // Both should return same archived text
        $text1 = $service1->getArchivedTextById(self::$testArchivedTextId);
        $text2 = $service2->getArchivedTextById(self::$testArchivedTextId);

        $this->assertEquals($text1, $text2);
    }
}
