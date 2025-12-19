<?php declare(strict_types=1);
namespace Lwt\Tests\Controllers;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Api\V1\Handlers\TermHandler;
use Lwt\Controllers\WordController;
use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Services\WordService;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Services/ExportService.php';
require_once __DIR__ . '/../../../src/backend/Services/WordStatusService.php';
require_once __DIR__ . '/../../../src/backend/Services/ExpressionService.php';
require_once __DIR__ . '/../../../src/backend/Controllers/BaseController.php';
require_once __DIR__ . '/../../../src/backend/Controllers/WordController.php';
require_once __DIR__ . '/../../../src/backend/Services/WordService.php';
require_once __DIR__ . '/../../../src/backend/Api/V1/Handlers/TermHandler.php';

/**
 * Unit tests for the WordController class.
 *
 * Tests controller initialization, service integration,
 * and verifies the MVC pattern implementation.
 */
class WordControllerTest extends TestCase
{
    private static bool $dbConnected = false;
    private static int $testLangId = 0;
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

        if (self::$dbConnected) {
            $languagesTable = Globals::table('languages');

            // Reset auto_increment to prevent overflow (LgID is tinyint max 255)
            $maxId = Connection::fetchValue("SELECT COALESCE(MAX(LgID), 0) AS value FROM {$languagesTable}");
            Connection::query("ALTER TABLE {$languagesTable} AUTO_INCREMENT = " . ((int)$maxId + 1));

            // Create a test language if it doesn't exist
            $existingLang = Connection::fetchValue(
                "SELECT LgID AS value FROM {$languagesTable} WHERE LgName = 'WordControllerTestLang' LIMIT 1"
            );

            if ($existingLang) {
                self::$testLangId = (int)$existingLang;
            } else {
                Connection::query(
                    "INSERT INTO {$languagesTable} (LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, " .
                    "LgTextSize, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, " .
                    "LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar, LgRightToLeft, LgShowRomanization) " .
                    "VALUES ('WordControllerTestLang', 'http://test.com/###', '', 'http://translate.test/###', " .
                    "100, '', '.!?', '', 'a-zA-Z', 0, 0, 0, 1)"
                );
                self::$testLangId = (int)Connection::fetchValue(
                    "SELECT LAST_INSERT_ID() AS value"
                );
            }
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        $languagesTable = Globals::table('languages');
        $wordsTable = Globals::table('words');
        // Clean up test words and language
        Connection::query("DELETE FROM {$wordsTable} WHERE WoLgID = " . self::$testLangId);
        Connection::query("DELETE FROM {$languagesTable} WHERE LgName = 'WordControllerTestLang'");

        // Reset auto_increment to prevent overflow (LgID is tinyint max 255)
        $maxId = Connection::fetchValue("SELECT COALESCE(MAX(LgID), 0) AS value FROM {$languagesTable}");
        Connection::query("ALTER TABLE {$languagesTable} AUTO_INCREMENT = " . ((int)$maxId + 1));
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

        // Clean up test words
        $wordsTable = Globals::table('words');
        Connection::query("DELETE FROM {$wordsTable} WHERE WoText LIKE 'ctrl_test_%'");
    }

    // ===== Constructor tests =====

    public function testControllerCanBeInstantiated(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());

        $this->assertInstanceOf(WordController::class, $controller);
    }

    public function testControllerHasWordService(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $this->assertInstanceOf(WordService::class, $service);
    }

    // ===== Service integration tests =====

    public function testWordServiceCreateThroughController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'ctrl_test_word',
            'WoStatus' => 1,
            'WoTranslation' => 'test translation',
        ];

        $result = $service->create($data);

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['id']);
        $this->assertEquals('ctrl_test_word', $result['textlc']);
    }

    public function testWordServiceDeleteThroughController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Create a word first
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'ctrl_test_delete',
            'WoStatus' => 1,
            'WoTranslation' => 'to delete',
        ];
        $createResult = $service->create($data);
        $wordId = $createResult['id'];

        // Verify it exists
        $word = $service->findById($wordId);
        $this->assertNotNull($word);

        // Delete it
        $deleteResult = $service->delete($wordId);
        $this->assertEquals('Deleted', $deleteResult);

        // Verify it's gone
        $deletedWord = $service->findById($wordId);
        $this->assertNull($deletedWord);
    }

    public function testWordServiceDeleteMultipleThroughController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Create multiple words
        $wordIds = [];
        for ($i = 1; $i <= 3; $i++) {
            $data = [
                'WoLgID' => self::$testLangId,
                'WoText' => "ctrl_test_multi_del_$i",
                'WoStatus' => 1,
                'WoTranslation' => "translation $i",
            ];
            $result = $service->create($data);
            $wordIds[] = $result['id'];
        }

        // Delete all
        $count = $service->deleteMultiple($wordIds);

        $this->assertEquals(3, $count);

        // Verify they're all gone
        foreach ($wordIds as $id) {
            $this->assertNull($service->findById($id));
        }
    }

    public function testWordServiceUpdateStatusMultipleThroughController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Create words with status 1
        $wordIds = [];
        for ($i = 1; $i <= 3; $i++) {
            $data = [
                'WoLgID' => self::$testLangId,
                'WoText' => "ctrl_test_status_$i",
                'WoStatus' => 1,
                'WoTranslation' => "translation $i",
            ];
            $result = $service->create($data);
            $wordIds[] = $result['id'];
        }

        // Update to status 5
        $count = $service->updateStatusMultiple($wordIds, 5);

        $this->assertEquals(3, $count);

        // Verify status changed
        foreach ($wordIds as $id) {
            $word = $service->findById($id);
            $this->assertEquals('5', $word['WoStatus']);
        }
    }

    public function testWordServiceUpdateStatusRelativeIncrementThroughController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Create word with status 2
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'ctrl_test_incr',
            'WoStatus' => 2,
            'WoTranslation' => 'increment test',
        ];
        $result = $service->create($data);
        $wordId = $result['id'];

        // Increment status
        $count = $service->updateStatusMultiple([$wordId], 1, true);

        $this->assertEquals(1, $count);

        // Verify status is now 3
        $word = $service->findById($wordId);
        $this->assertEquals('3', $word['WoStatus']);
    }

    public function testWordServiceUpdateStatusRelativeDecrementThroughController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Create word with status 4
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'ctrl_test_decr',
            'WoStatus' => 4,
            'WoTranslation' => 'decrement test',
        ];
        $result = $service->create($data);
        $wordId = $result['id'];

        // Decrement status
        $count = $service->updateStatusMultiple([$wordId], -1, true);

        $this->assertEquals(1, $count);

        // Verify status is now 3
        $word = $service->findById($wordId);
        $this->assertEquals('3', $word['WoStatus']);
    }

    public function testWordServiceDeleteSentencesMultipleThroughController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Create word with sentence
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'ctrl_test_sent',
            'WoStatus' => 1,
            'WoTranslation' => 'sentence test',
            'WoSentence' => 'This is a {ctrl_test_sent} sentence.',
        ];
        $result = $service->create($data);
        $wordId = $result['id'];

        // Verify sentence exists
        $word = $service->findById($wordId);
        $this->assertNotEmpty($word['WoSentence']);

        // Delete sentences
        $count = $service->deleteSentencesMultiple([$wordId]);

        $this->assertEquals(1, $count);

        // Verify sentence is null
        $word = $service->findById($wordId);
        $this->assertNull($word['WoSentence']);
    }

    public function testWordServiceToLowercaseMultipleThroughController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Create word with mixed case
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'Ctrl_Test_Case',
            'WoStatus' => 1,
            'WoTranslation' => 'case test',
        ];
        $result = $service->create($data);
        $wordId = $result['id'];

        // Convert to lowercase
        $count = $service->toLowercaseMultiple([$wordId]);

        $this->assertEquals(1, $count);

        // Verify text is now lowercase
        $word = $service->findById($wordId);
        $this->assertEquals('ctrl_test_case', $word['WoText']);
    }

    public function testWordServiceCapitalizeMultipleThroughController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Create word in lowercase
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'ctrl_test_cap',
            'WoStatus' => 1,
            'WoTranslation' => 'capitalize test',
        ];
        $result = $service->create($data);
        $wordId = $result['id'];

        // Capitalize
        $count = $service->capitalizeMultiple([$wordId]);

        $this->assertEquals(1, $count);

        // Verify text is now capitalized
        $word = $service->findById($wordId);
        $this->assertEquals('Ctrl_test_cap', $word['WoText']);
    }

    public function testWordServiceCreateWithStatusThroughController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Create word with well-known status (99)
        $result = $service->createWithStatus(
            self::$testLangId,
            'ctrl_test_known',
            'ctrl_test_known',
            99
        );

        $this->assertGreaterThan(0, $result['id']);
        $this->assertEquals(1, $result['rows']);

        // Verify status is 99
        $word = $service->findById($result['id']);
        $this->assertEquals('99', $word['WoStatus']);
    }

    public function testWordServiceCreateWithStatusDoesNotDuplicateThroughController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Create first word
        $result1 = $service->createWithStatus(
            self::$testLangId,
            'ctrl_test_nodup',
            'ctrl_test_nodup',
            99
        );

        // Try to create again
        $result2 = $service->createWithStatus(
            self::$testLangId,
            'ctrl_test_nodup',
            'ctrl_test_nodup',
            98
        );

        // Should return existing ID with 0 rows affected
        $this->assertEquals($result1['id'], $result2['id']);
        $this->assertEquals(0, $result2['rows']);
    }

    // ===== Method existence tests =====

    public function testControllerHasRequiredMethods(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());

        $this->assertTrue(method_exists($controller, 'edit'));
        $this->assertTrue(method_exists($controller, 'listEdit'));
        $this->assertTrue(method_exists($controller, 'editMulti'));
        $this->assertTrue(method_exists($controller, 'delete'));
        $this->assertTrue(method_exists($controller, 'all'));
        $this->assertTrue(method_exists($controller, 'create'));
        $this->assertTrue(method_exists($controller, 'show'));
        $this->assertTrue(method_exists($controller, 'inlineEdit'));
        $this->assertTrue(method_exists($controller, 'bulkTranslate'));
        $this->assertTrue(method_exists($controller, 'setStatus'));
        $this->assertTrue(method_exists($controller, 'upload'));
        $this->assertTrue(method_exists($controller, 'getWordService'));
    }

    // ===== Empty array handling tests =====

    public function testDeleteMultipleWithEmptyArrayReturnsZero(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $count = $service->deleteMultiple([]);

        $this->assertEquals(0, $count);
    }

    public function testUpdateStatusMultipleWithEmptyArrayReturnsZero(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $count = $service->updateStatusMultiple([], 5);

        $this->assertEquals(0, $count);
    }

    public function testUpdateStatusDateMultipleWithEmptyArrayReturnsZero(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $count = $service->updateStatusDateMultiple([]);

        $this->assertEquals(0, $count);
    }

    public function testDeleteSentencesMultipleWithEmptyArrayReturnsZero(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $count = $service->deleteSentencesMultiple([]);

        $this->assertEquals(0, $count);
    }

    public function testToLowercaseMultipleWithEmptyArrayReturnsZero(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $count = $service->toLowercaseMultiple([]);

        $this->assertEquals(0, $count);
    }

    public function testCapitalizeMultipleWithEmptyArrayReturnsZero(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $count = $service->capitalizeMultiple([]);

        $this->assertEquals(0, $count);
    }

    // ===== setStatus() tests =====

    public function testSetStatusUpdatesSingleWord(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();
        $termHandler = new TermHandler();

        // Create a word with status 1
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'ctrl_test_setstatus',
            'WoStatus' => 1,
            'WoTranslation' => 'status test',
        ];
        $result = $service->create($data);
        $wordId = $result['id'];

        // Set status to 5 using modern API
        $termHandler->formatSetStatus($wordId, 5);

        // Verify
        $word = $service->findById($wordId);
        $this->assertEquals('5', $word['WoStatus']);
    }

    public function testSetStatusToWellKnown(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();
        $termHandler = new TermHandler();

        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'ctrl_test_wellknown',
            'WoStatus' => 1,
            'WoTranslation' => 'well known test',
        ];
        $result = $service->create($data);
        $wordId = $result['id'];

        // Set status to 99 (well-known) using modern API
        $termHandler->formatSetStatus($wordId, 99);

        $word = $service->findById($wordId);
        $this->assertEquals('99', $word['WoStatus']);
    }

    public function testSetStatusToIgnored(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();
        $termHandler = new TermHandler();

        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'ctrl_test_ignored',
            'WoStatus' => 1,
            'WoTranslation' => 'ignored test',
        ];
        $result = $service->create($data);
        $wordId = $result['id'];

        // Set status to 98 (ignored) using modern API
        $termHandler->formatSetStatus($wordId, 98);

        $word = $service->findById($wordId);
        $this->assertEquals('98', $word['WoStatus']);
    }

    // ===== getWordData() tests =====

    public function testGetWordDataReturnsCorrectData(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'ctrl_test_getdata',
            'WoStatus' => 1,
            'WoTranslation' => 'my translation',
            'WoRomanization' => 'my romanization',
        ];
        $result = $service->create($data);

        $wordData = $service->getWordData($result['id']);

        $this->assertIsArray($wordData);
        $this->assertArrayHasKey('text', $wordData);
        $this->assertArrayHasKey('translation', $wordData);
        $this->assertArrayHasKey('romanization', $wordData);
        $this->assertEquals('ctrl_test_getdata', $wordData['text']);
        $this->assertEquals('my translation', $wordData['translation']);
        $this->assertEquals('my romanization', $wordData['romanization']);
    }

    public function testGetWordDataReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $result = $service->getWordData(999999);

        $this->assertNull($result);
    }

    // ===== getWordText() tests =====

    public function testGetWordTextReturnsText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'ctrl_test_gettext',
            'WoStatus' => 1,
            'WoTranslation' => 'translation',
        ];
        $result = $service->create($data);

        $text = $service->getWordText($result['id']);

        $this->assertEquals('ctrl_test_gettext', $text);
    }

    public function testGetWordTextReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $result = $service->getWordText(999999);

        $this->assertNull($result);
    }

    // ===== Controller action parameter validation tests =====

    public function testDeleteTermWithNonExistentIdReturnsError(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $termHandler = new TermHandler();

        // Attempting to delete a non-existent term should return error
        $result = $termHandler->deleteTerm(999999);

        $this->assertFalse($result['deleted']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Term not found', $result['error']);
    }

    public function testDeleteTermSuccessfully(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();
        $termHandler = new TermHandler();

        // Create a word first
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'ctrl_test_api_delete',
            'WoStatus' => 1,
            'WoTranslation' => 'to delete via API',
        ];
        $createResult = $service->create($data);
        $wordId = $createResult['id'];

        // Verify it exists
        $word = $service->findById($wordId);
        $this->assertNotNull($word);

        // Delete it using the modern API
        $deleteResult = $termHandler->deleteTerm($wordId);
        $this->assertTrue($deleteResult['deleted']);

        // Verify it's gone
        $deletedWord = $service->findById($wordId);
        $this->assertNull($deletedWord);
    }

    public function testInsertWellknownReturnsEarlyWithMissingParams(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $termHandler = new TermHandler();

        // Test that createQuickTerm returns error with invalid parameters
        $result = $termHandler->createQuickTerm(999999, 999999, 99);

        $this->assertArrayHasKey('error', $result);
    }

    public function testInsertIgnoreReturnsEarlyWithMissingParams(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $termHandler = new TermHandler();

        // Test that createQuickTerm returns error with invalid parameters (status=98 for ignored)
        $result = $termHandler->createQuickTerm(999999, 999999, 98);

        $this->assertArrayHasKey('error', $result);
    }

    public function testSetStatusReturnsEarlyWithMissingParams(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());

        $_REQUEST = [];
        ob_start();
        $controller->setStatus([]);
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    // ===== Bulk translate tests =====

    public function testBulkTranslateMethodExists(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());

        $this->assertTrue(method_exists($controller, 'bulkTranslate'));
    }

    public function testBulkSaveTermsThroughController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $terms = [
            ['lg' => self::$testLangId, 'text' => 'ctrl_test_bulk1', 'status' => 1, 'trans' => 'bulk1'],
            ['lg' => self::$testLangId, 'text' => 'ctrl_test_bulk2', 'status' => 2, 'trans' => 'bulk2'],
        ];

        $maxWoId = $service->bulkSaveTerms($terms);

        // Verify words were created
        $word1 = $service->findByText('ctrl_test_bulk1', self::$testLangId);
        $word2 = $service->findByText('ctrl_test_bulk2', self::$testLangId);

        $this->assertNotNull($word1);
        $this->assertNotNull($word2);
        $this->assertGreaterThan($maxWoId, $word1);
        $this->assertGreaterThan($maxWoId, $word2);
    }

    public function testGetLanguageDictionariesThroughController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Test with non-existent text (returns empty values)
        $result = $service->getLanguageDictionaries(999999);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('dict1', $result);
        $this->assertArrayHasKey('dict2', $result);
        $this->assertArrayHasKey('translate', $result);
    }

    public function testGetNewWordsAfterThroughController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Get max before
        $maxBefore = $service->bulkSaveTerms([]);

        // Create a word
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'ctrl_test_newafter',
            'WoStatus' => 1,
            'WoTranslation' => 'translation',
        ];
        $service->create($data);

        // Get new words
        $res = $service->getNewWordsAfter($maxBefore);

        $found = false;
        foreach ($res as $record) {
            if ($record['WoTextLC'] === 'ctrl_test_newafter') {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found);
    }

    // ===== Multi-word edit tests =====

    public function testEditMultiMethodExists(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());

        $this->assertTrue(method_exists($controller, 'editMulti'));
    }

    public function testCreateMultiWordThroughController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $data = [
            'lgid' => self::$testLangId,
            'text' => 'ctrl_multi_word_test',
            'textlc' => 'ctrl_multi_word_test',
            'status' => 1,
            'translation' => 'controller multi word',
            'sentence' => '',
            'roman' => '',
            'wordcount' => 3,
        ];

        // Buffer output since insertExpressions outputs JS
        ob_start();
        $result = $service->createMultiWord($data);
        ob_end_clean();

        $this->assertArrayHasKey('id', $result);
        $this->assertGreaterThan(0, $result['id']);

        // Verify
        $word = $service->findById($result['id']);
        $this->assertNotNull($word);
        $this->assertEquals('ctrl_multi_word_test', $word['WoTextLC']);
    }

    public function testUpdateMultiWordThroughController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Create first (buffer output)
        $createData = [
            'lgid' => self::$testLangId,
            'text' => 'ctrl_update_multi',
            'textlc' => 'ctrl_update_multi',
            'status' => 1,
            'translation' => 'original',
            'sentence' => '',
            'roman' => '',
            'wordcount' => 2,
        ];
        ob_start();
        $created = $service->createMultiWord($createData);
        ob_end_clean();

        // Update
        $updateData = [
            'text' => 'ctrl_update_multi',
            'textlc' => 'ctrl_update_multi',
            'translation' => 'updated via controller',
            'sentence' => 'Updated sentence',
            'roman' => 'updated roman',
        ];

        $result = $service->updateMultiWord($created['id'], $updateData, 1, 3);

        $this->assertEquals($created['id'], $result['id']);
        $this->assertEquals(3, $result['status']);

        // Verify
        $word = $service->findById($created['id']);
        $this->assertEquals('updated via controller', $word['WoTranslation']);
        $this->assertEquals('3', $word['WoStatus']);
    }

    public function testGetMultiWordDataThroughController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Create a multi-word (buffer output)
        $createData = [
            'lgid' => self::$testLangId,
            'text' => 'ctrl_get_multi',
            'textlc' => 'ctrl_get_multi',
            'status' => 2,
            'translation' => 'get test',
            'sentence' => 'Test sentence.',
            'roman' => 'test roman',
            'wordcount' => 2,
        ];
        ob_start();
        $created = $service->createMultiWord($createData);
        ob_end_clean();

        $result = $service->getMultiWordData($created['id']);

        $this->assertNotNull($result);
        $this->assertEquals('ctrl_get_multi', $result['text']);
        $this->assertEquals(self::$testLangId, $result['lgid']);
        $this->assertEquals('get test', $result['translation']);
        $this->assertEquals(2, $result['status']);
    }

    public function testExportTermAsJsonThroughController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $json = $service->exportTermAsJson(
            456,
            'controller test',
            'ctrl roman',
            'ctrl translation',
            3
        );

        $decoded = json_decode($json, true);
        $this->assertNotNull($decoded);
        $this->assertEquals(456, $decoded['woid']);
        $this->assertEquals('controller test', $decoded['text']);
        $this->assertEquals(3, $decoded['status']);
    }

    // ===== Additional Controller Action Tests =====

    // ===== edit() method tests =====

    public function testEditReturnsEarlyWithNoParameters(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());

        // Without wid, tid, ord, or op, should return early
        $_REQUEST = [];
        ob_start();
        $controller->edit([]);
        $output = ob_get_clean();

        // Should produce no output (early return)
        $this->assertEmpty($output);
    }

    // ===== editTerm() method tests =====

    public function testEditTermMethodExists(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $this->assertTrue(method_exists($controller, 'editTerm'));
    }

    // ===== listEdit() method tests =====

    public function testListEditMethodExists(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $this->assertTrue(method_exists($controller, 'listEdit'));
    }

    // ===== all() method tests =====

    public function testAllReturnsEarlyWithoutTextParam(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());

        $_REQUEST = [];
        ob_start();
        $controller->all([]);
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testAllMethodExists(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $this->assertTrue(method_exists($controller, 'all'));
    }

    // ===== inlineEdit() method tests =====

    public function testInlineEditTranslation(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Create a word first
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'ctrl_test_inline_trans',
            'WoStatus' => 1,
            'WoTranslation' => 'original translation',
        ];
        $result = $service->create($data);
        $wordId = $result['id'];

        $_POST = [
            'id' => 'trans' . $wordId,
            'value' => 'new inline translation'
        ];

        ob_start();
        $controller->inlineEdit([]);
        $output = ob_get_clean();

        $this->assertEquals('new inline translation', $output);

        // Verify in database
        $word = $service->findById($wordId);
        $this->assertEquals('new inline translation', $word['WoTranslation']);
    }

    public function testInlineEditRomanization(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Create a word first
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'ctrl_test_inline_roman',
            'WoStatus' => 1,
            'WoTranslation' => 'translation',
            'WoRomanization' => 'original roman',
        ];
        $result = $service->create($data);
        $wordId = $result['id'];

        $_POST = [
            'id' => 'roman' . $wordId,
            'value' => 'new romanization'
        ];

        ob_start();
        $controller->inlineEdit([]);
        $output = ob_get_clean();

        $this->assertEquals('new romanization', $output);
    }

    public function testInlineEditWithInvalidIdReturnsError(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());

        $_POST = [
            'id' => 'invalid123',
            'value' => 'some value'
        ];

        ob_start();
        $controller->inlineEdit([]);
        $output = ob_get_clean();

        $this->assertStringContainsString('ERROR', $output);
    }

    public function testInlineEditEmptyTranslationBecomesStar(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Create a word
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'ctrl_test_empty_trans',
            'WoStatus' => 1,
            'WoTranslation' => 'has translation',
        ];
        $result = $service->create($data);
        $wordId = $result['id'];

        $_POST = [
            'id' => 'trans' . $wordId,
            'value' => ''
        ];

        ob_start();
        $controller->inlineEdit([]);
        $output = ob_get_clean();

        $this->assertEquals('*', $output);
    }

    // ===== show() method tests =====

    public function testShowMethodExists(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $this->assertTrue(method_exists($controller, 'show'));
    }

    // ===== upload() method tests =====

    public function testUploadMethodExists(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $this->assertTrue(method_exists($controller, 'upload'));
    }

    public function testGetUploadServiceForTest(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $uploadService = $controller->getUploadServiceForTest();

        $this->assertNotNull($uploadService);
    }

    // ===== WordService additional method tests =====

    public function testFindByTextReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $result = $service->findByText('nonexistent_word_xyz', self::$testLangId);

        $this->assertNull($result);
    }

    public function testFindByTextReturnsIdForExisting(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Create a word
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'ctrl_test_findbytext',
            'WoStatus' => 1,
            'WoTranslation' => 'translation',
        ];
        $result = $service->create($data);
        $wordId = $result['id'];

        $foundId = $service->findByText('ctrl_test_findbytext', self::$testLangId);

        $this->assertEquals($wordId, $foundId);
    }

    public function testGetTermFromTextItemReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Non-existent text and ord should return null
        $result = $service->getTermFromTextItem(999999, 999999);

        $this->assertNull($result);
    }

    public function testGetLanguageDataReturnsExpectedKeys(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $data = $service->getLanguageData(self::$testLangId);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('showRoman', $data);
        $this->assertArrayHasKey('translateUri', $data);
        $this->assertArrayHasKey('name', $data);
    }

    public function testGetTextLanguageIdReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $result = $service->getTextLanguageId(999999);

        $this->assertNull($result);
    }

    public function testGetFilteredWordIdsWithEmptyFilters(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Empty filters should return all words
        $ids = $service->getFilteredWordIds([]);

        $this->assertIsArray($ids);
    }

    public function testGetFilteredWordIdsWithLangFilter(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Create some words
        for ($i = 1; $i <= 3; $i++) {
            $data = [
                'WoLgID' => self::$testLangId,
                'WoText' => "ctrl_test_filter_$i",
                'WoStatus' => 1,
                'WoTranslation' => "translation $i",
            ];
            $service->create($data);
        }

        $ids = $service->getFilteredWordIds(['langId' => self::$testLangId]);

        $this->assertIsArray($ids);
        $this->assertNotEmpty($ids);
    }

    public function testUpdateWithStatusChange(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Create a word with status 1
        $createData = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'ctrl_test_update_status',
            'WoStatus' => 1,
            'WoTranslation' => 'original',
        ];
        $createResult = $service->create($createData);
        $wordId = $createResult['id'];

        // Update with status change
        $updateData = [
            'WoText' => 'ctrl_test_update_status',
            'WoTranslation' => 'updated',
            'WoStatus' => 3,
            'WoOldStatus' => 1,
        ];
        $updateResult = $service->update($wordId, $updateData);

        $this->assertTrue($updateResult['success']);

        // Verify status changed
        $word = $service->findById($wordId);
        $this->assertEquals('3', $word['WoStatus']);
    }

    public function testCreateWithDuplicateReturnsError(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Create first word
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'ctrl_test_duplicate',
            'WoStatus' => 1,
            'WoTranslation' => 'first',
        ];
        $result1 = $service->create($data);
        $this->assertTrue($result1['success']);

        // Try to create duplicate
        $result2 = $service->create($data);
        $this->assertFalse($result2['success']);
        $this->assertStringContainsString('Duplicate', $result2['message']);
    }

    public function testGetSentenceForTermWithNonExistentPosition(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $result = $service->getSentenceForTerm(999999, 999999, 'test');

        $this->assertEquals('', $result);
    }

    public function testTextToClassNameConvertsCorrectly(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $hex = $service->textToClassName('hello');

        $this->assertIsString($hex);
        $this->assertNotEmpty($hex);
    }

    public function testGetWordCountReturnsZeroForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $count = $service->getWordCount(999999);

        $this->assertEquals(0, $count);
    }

    public function testLinkToTextItemsDoesNotThrowException(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // This should not throw an exception even with non-existent data
        $service->linkToTextItems(999999, self::$testLangId, 'test');

        // If we get here, the test passes
        $this->assertTrue(true);
    }

    public function testGetUnknownWordsInTextReturnsResult(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // This should return an array even for non-existent text
        $result = $service->getUnknownWordsInText(999999);

        $this->assertIsArray($result);
    }

    public function testLinkAllTextItemsDoesNotThrowException(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // This should not throw an exception
        $service->linkAllTextItems();

        // If we get here, the test passes
        $this->assertTrue(true);
    }

    public function testGetWordAtPositionReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $result = $service->getWordAtPosition(999999, 999999);

        $this->assertNull($result);
    }

    public function testGetWordDetailsReturnsCorrectStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Create a word
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'ctrl_test_details',
            'WoStatus' => 2,
            'WoTranslation' => 'details translation',
            'WoRomanization' => 'details roman',
            'WoSentence' => 'Test {ctrl_test_details} sentence.',
        ];
        $result = $service->create($data);

        $details = $service->getWordDetails($result['id']);

        $this->assertIsArray($details);
        $this->assertArrayHasKey('langId', $details);
        $this->assertArrayHasKey('text', $details);
        $this->assertArrayHasKey('translation', $details);
        $this->assertArrayHasKey('sentence', $details);
        $this->assertArrayHasKey('romanization', $details);
        $this->assertArrayHasKey('status', $details);
        $this->assertEquals('ctrl_test_details', $details['text']);
        $this->assertEquals(2, $details['status']);
    }

    public function testGetWordDetailsReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $details = $service->getWordDetails(999999);

        $this->assertNull($details);
    }

    public function testGetWordDetailsNormalizesStarTranslation(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Create a word with * translation (empty)
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'ctrl_test_star_trans',
            'WoStatus' => 1,
            'WoTranslation' => '',  // Will become * in DB
        ];
        $result = $service->create($data);

        $details = $service->getWordDetails($result['id']);

        // Star should be converted to empty string for display
        $this->assertEquals('', $details['translation']);
    }

    public function testFindMultiWordByTextReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $result = $service->findMultiWordByText('nonexistent_multiword', self::$testLangId);

        $this->assertNull($result);
    }

    public function testGetLanguageIdFromTextReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $result = $service->getLanguageIdFromText(999999);

        $this->assertNull($result);
    }

    public function testGetSentenceIdAtPositionReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $result = $service->getSentenceIdAtPosition(999999, 999999);

        $this->assertNull($result);
    }

    public function testShouldShowRomanizationForNonExistentText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Should return false for non-existent text
        $result = $service->shouldShowRomanization(999999);

        $this->assertFalse($result);
    }

    public function testGetMultiWordDataReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        $result = $service->getMultiWordData(999999);

        $this->assertNull($result);
    }

    public function testUpdateRomanizationWithStarBecomesEmpty(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Create a word with romanization
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'ctrl_test_roman_star',
            'WoStatus' => 1,
            'WoTranslation' => 'translation',
            'WoRomanization' => 'some roman',
        ];
        $result = $service->create($data);
        $wordId = $result['id'];

        // Update with * (should become empty)
        $updated = $service->updateRomanization($wordId, '*');

        // Should return * for display since it's now empty
        $this->assertEquals('*', $updated);

        // Verify in database it's empty
        $word = $service->findById($wordId);
        $this->assertEquals('', $word['WoRomanization']);
    }

    public function testGetAllUnknownWordsInTextReturnsResult(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Should return an array even for non-existent text
        $result = $service->getAllUnknownWordsInText(999999);

        $this->assertIsArray($result);
    }

    public function testDeleteMultiWordMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $service = $controller->getWordService();

        // Create a multi-word (buffer output)
        $createData = [
            'lgid' => self::$testLangId,
            'text' => 'ctrl_delete_multi_test',
            'textlc' => 'ctrl_delete_multi_test',
            'status' => 1,
            'translation' => 'to be deleted',
            'sentence' => '',
            'roman' => '',
            'wordcount' => 3,
        ];
        ob_start();
        $created = $service->createMultiWord($createData);
        ob_end_clean();

        // Delete it
        $result = $service->deleteMultiWord($created['id']);

        // Verify deleted
        $word = $service->findById($created['id']);
        $this->assertNull($word);
    }

    // ===== create() action tests =====

    public function testCreateActionMethodExists(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController(new \Lwt\Services\WordService(), new \Lwt\Services\LanguageService());
        $this->assertTrue(method_exists($controller, 'create'));
    }
}
