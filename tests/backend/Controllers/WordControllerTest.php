<?php

declare(strict_types=1);

namespace Lwt\Tests\Controllers;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

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
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Core/Export/export_helpers.php';
require_once __DIR__ . '/../../../src/backend/Core/Word/word_scoring.php';
require_once __DIR__ . '/../../../src/backend/Controllers/BaseController.php';
require_once __DIR__ . '/../../../src/backend/Controllers/WordController.php';
require_once __DIR__ . '/../../../src/backend/Services/WordService.php';

/**
 * Unit tests for the WordController class.
 *
 * Tests controller initialization, service integration,
 * and verifies the MVC pattern implementation.
 */
class WordControllerTest extends TestCase
{
    private static bool $dbConnected = false;
    private static string $tbpref = '';
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
        self::$tbpref = Globals::getTablePrefix();

        if (self::$dbConnected) {
            $tbpref = self::$tbpref;

            // Reset auto_increment to prevent overflow (LgID is tinyint max 255)
            $maxId = Connection::fetchValue("SELECT COALESCE(MAX(LgID), 0) AS value FROM {$tbpref}languages");
            Connection::query("ALTER TABLE {$tbpref}languages AUTO_INCREMENT = " . ((int)$maxId + 1));

            // Create a test language if it doesn't exist
            $existingLang = Connection::fetchValue(
                "SELECT LgID AS value FROM {$tbpref}languages WHERE LgName = 'WordControllerTestLang' LIMIT 1"
            );

            if ($existingLang) {
                self::$testLangId = (int)$existingLang;
            } else {
                Connection::query(
                    "INSERT INTO {$tbpref}languages (LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, " .
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

        $tbpref = self::$tbpref;
        // Clean up test words and language
        Connection::query("DELETE FROM {$tbpref}words WHERE WoLgID = " . self::$testLangId);
        Connection::query("DELETE FROM {$tbpref}languages WHERE LgName = 'WordControllerTestLang'");

        // Reset auto_increment to prevent overflow (LgID is tinyint max 255)
        $maxId = Connection::fetchValue("SELECT COALESCE(MAX(LgID), 0) AS value FROM {$tbpref}languages");
        Connection::query("ALTER TABLE {$tbpref}languages AUTO_INCREMENT = " . ((int)$maxId + 1));
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
        $tbpref = self::$tbpref;
        Connection::query("DELETE FROM {$tbpref}words WHERE WoText LIKE 'ctrl_test_%'");
    }

    // ===== Constructor tests =====

    public function testControllerCanBeInstantiated(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController();

        $this->assertInstanceOf(WordController::class, $controller);
    }

    public function testControllerHasWordService(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController();
        $service = $controller->getWordService();

        $this->assertInstanceOf(WordService::class, $service);
    }

    // ===== Service integration tests =====

    public function testWordServiceCreateThroughController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController();
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

        $controller = new WordController();
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

        $controller = new WordController();
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

        $controller = new WordController();
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

        $controller = new WordController();
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

        $controller = new WordController();
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

        $controller = new WordController();
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

        $controller = new WordController();
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

        $controller = new WordController();
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

        $controller = new WordController();
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

        $controller = new WordController();
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

        $controller = new WordController();

        $this->assertTrue(method_exists($controller, 'edit'));
        $this->assertTrue(method_exists($controller, 'listEdit'));
        $this->assertTrue(method_exists($controller, 'editMulti'));
        $this->assertTrue(method_exists($controller, 'delete'));
        $this->assertTrue(method_exists($controller, 'deleteMulti'));
        $this->assertTrue(method_exists($controller, 'all'));
        $this->assertTrue(method_exists($controller, 'create'));
        $this->assertTrue(method_exists($controller, 'show'));
        $this->assertTrue(method_exists($controller, 'insertWellknown'));
        $this->assertTrue(method_exists($controller, 'insertIgnore'));
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

        $controller = new WordController();
        $service = $controller->getWordService();

        $count = $service->deleteMultiple([]);

        $this->assertEquals(0, $count);
    }

    public function testUpdateStatusMultipleWithEmptyArrayReturnsZero(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController();
        $service = $controller->getWordService();

        $count = $service->updateStatusMultiple([], 5);

        $this->assertEquals(0, $count);
    }

    public function testUpdateStatusDateMultipleWithEmptyArrayReturnsZero(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController();
        $service = $controller->getWordService();

        $count = $service->updateStatusDateMultiple([]);

        $this->assertEquals(0, $count);
    }

    public function testDeleteSentencesMultipleWithEmptyArrayReturnsZero(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController();
        $service = $controller->getWordService();

        $count = $service->deleteSentencesMultiple([]);

        $this->assertEquals(0, $count);
    }

    public function testToLowercaseMultipleWithEmptyArrayReturnsZero(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController();
        $service = $controller->getWordService();

        $count = $service->toLowercaseMultiple([]);

        $this->assertEquals(0, $count);
    }

    public function testCapitalizeMultipleWithEmptyArrayReturnsZero(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController();
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

        $controller = new WordController();
        $service = $controller->getWordService();

        // Create a word with status 1
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'ctrl_test_setstatus',
            'WoStatus' => 1,
            'WoTranslation' => 'status test',
        ];
        $result = $service->create($data);
        $wordId = $result['id'];

        // Set status to 5
        $service->setStatus($wordId, 5);

        // Verify
        $word = $service->findById($wordId);
        $this->assertEquals('5', $word['WoStatus']);
    }

    public function testSetStatusToWellKnown(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController();
        $service = $controller->getWordService();

        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'ctrl_test_wellknown',
            'WoStatus' => 1,
            'WoTranslation' => 'well known test',
        ];
        $result = $service->create($data);
        $wordId = $result['id'];

        $service->setStatus($wordId, 99);

        $word = $service->findById($wordId);
        $this->assertEquals('99', $word['WoStatus']);
    }

    public function testSetStatusToIgnored(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController();
        $service = $controller->getWordService();

        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'ctrl_test_ignored',
            'WoStatus' => 1,
            'WoTranslation' => 'ignored test',
        ];
        $result = $service->create($data);
        $wordId = $result['id'];

        $service->setStatus($wordId, 98);

        $word = $service->findById($wordId);
        $this->assertEquals('98', $word['WoStatus']);
    }

    // ===== getWordData() tests =====

    public function testGetWordDataReturnsCorrectData(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController();
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

        $controller = new WordController();
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

        $controller = new WordController();
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

        $controller = new WordController();
        $service = $controller->getWordService();

        $result = $service->getWordText(999999);

        $this->assertNull($result);
    }

    // ===== Controller action parameter validation tests =====

    public function testDeleteActionReturnsEarlyWithMissingParams(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController();

        // Without any parameters, should return early
        $_REQUEST = [];
        ob_start();
        $controller->delete([]);
        $output = ob_get_clean();

        // Should produce no output (early return)
        $this->assertEmpty($output);
    }

    public function testInsertWellknownReturnsEarlyWithMissingParams(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController();

        $_REQUEST = [];
        ob_start();
        $controller->insertWellknown([]);
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testInsertIgnoreReturnsEarlyWithMissingParams(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController();

        $_REQUEST = [];
        ob_start();
        $controller->insertIgnore([]);
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testSetStatusReturnsEarlyWithMissingParams(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController();

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

        $controller = new WordController();

        $this->assertTrue(method_exists($controller, 'bulkTranslate'));
    }

    public function testBulkSaveTermsThroughController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController();
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

        $controller = new WordController();
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

        $controller = new WordController();
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
        while ($record = mysqli_fetch_assoc($res)) {
            if ($record['WoTextLC'] === 'ctrl_test_newafter') {
                $found = true;
                break;
            }
        }
        mysqli_free_result($res);

        $this->assertTrue($found);
    }
}
