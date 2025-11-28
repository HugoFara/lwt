<?php

declare(strict_types=1);

namespace Lwt\Tests\Controllers;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Controllers\WordController;
use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Services\WordUploadService;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Controllers/BaseController.php';
require_once __DIR__ . '/../../../src/backend/Controllers/WordController.php';
require_once __DIR__ . '/../../../src/backend/Services/WordService.php';
require_once __DIR__ . '/../../../src/backend/Services/WordUploadService.php';

/**
 * Unit tests for the WordController upload functionality and WordUploadService.
 *
 * Tests the word upload MVC implementation.
 */
class WordUploadTest extends TestCase
{
    private static bool $dbConnected = false;
    private static string $tbpref = '';
    private static int $testLangId = 0;
    private array $originalRequest;
    private array $originalServer;
    private array $originalGet;
    private array $originalPost;
    private array $originalFiles;

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
                "SELECT LgID AS value FROM {$tbpref}languages WHERE LgName = 'WordUploadTestLang' LIMIT 1"
            );

            if ($existingLang) {
                self::$testLangId = (int)$existingLang;
            } else {
                Connection::query(
                    "INSERT INTO {$tbpref}languages (LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, " .
                    "LgTextSize, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, " .
                    "LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar, LgRightToLeft, LgShowRomanization) " .
                    "VALUES ('WordUploadTestLang', 'http://test.com/###', '', 'http://translate.test/###', " .
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

        // Clean up test words
        Connection::query("DELETE FROM {$tbpref}wordtags WHERE WtWoID IN (SELECT WoID FROM {$tbpref}words WHERE WoLgID = " . self::$testLangId . ")");
        Connection::query("DELETE FROM {$tbpref}words WHERE WoLgID = " . self::$testLangId);

        // Clean up test language
        Connection::query("DELETE FROM {$tbpref}languages WHERE LgName = 'WordUploadTestLang'");

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
        $this->originalFiles = $_FILES;

        // Reset superglobals
        $_REQUEST = [];
        $_SERVER = ['REQUEST_METHOD' => 'GET'];
        $_GET = [];
        $_POST = [];
        $_FILES = [];

        if (!self::$dbConnected) {
            return;
        }

        // Clean up any test words from previous tests
        $tbpref = self::$tbpref;
        Connection::query("DELETE FROM {$tbpref}wordtags WHERE WtWoID IN (SELECT WoID FROM {$tbpref}words WHERE WoLgID = " . self::$testLangId . ")");
        Connection::query("DELETE FROM {$tbpref}words WHERE WoLgID = " . self::$testLangId);
    }

    protected function tearDown(): void
    {
        // Restore superglobals
        $_REQUEST = $this->originalRequest;
        $_SERVER = $this->originalServer;
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
        $_FILES = $this->originalFiles;
    }

    // ===== Controller tests =====

    public function testControllerHasUploadMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController();

        $this->assertTrue(method_exists($controller, 'upload'));
    }

    public function testControllerHasUploadServiceGetter(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new WordController();

        $this->assertTrue(method_exists($controller, 'getUploadServiceForTest'));

        $service = $controller->getUploadServiceForTest();
        $this->assertInstanceOf(WordUploadService::class, $service);
    }

    // ===== WordUploadService tests =====

    public function testServiceCreation(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordUploadService();

        $this->assertInstanceOf(WordUploadService::class, $service);
    }

    public function testGetLanguageData(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordUploadService();
        $langData = $service->getLanguageData(self::$testLangId);

        $this->assertNotNull($langData);
        $this->assertEquals('WordUploadTestLang', $langData['LgName']);
        $this->assertEquals('0', $langData['LgRemoveSpaces']);
    }

    public function testGetLanguageDataReturnsNullForInvalidId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordUploadService();
        $langData = $service->getLanguageData(99999);

        $this->assertNull($langData);
    }

    public function testGetDelimiter(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordUploadService();

        $this->assertEquals(',', $service->getDelimiter('c'));
        $this->assertEquals('#', $service->getDelimiter('h'));
        $this->assertEquals("\t", $service->getDelimiter('t'));
    }

    public function testGetSqlDelimiter(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordUploadService();

        $this->assertEquals(',', $service->getSqlDelimiter('c'));
        $this->assertEquals('#', $service->getSqlDelimiter('h'));
        $this->assertEquals("\\t", $service->getSqlDelimiter('t'));
    }

    public function testParseColumnMapping(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordUploadService();

        $columns = [
            1 => 'w',
            2 => 't',
            3 => 'r',
            4 => 's',
            5 => 'g',
        ];

        $result = $service->parseColumnMapping($columns, false);

        $this->assertArrayHasKey('columns', $result);
        $this->assertArrayHasKey('fields', $result);

        $this->assertEquals('WoText', $result['columns'][1]);
        $this->assertEquals('WoTranslation', $result['columns'][2]);
        $this->assertEquals('WoRomanization', $result['columns'][3]);
        $this->assertEquals('WoSentence', $result['columns'][4]);
        $this->assertEquals('@taglist', $result['columns'][5]);

        $this->assertEquals(1, $result['fields']['txt']);
        $this->assertEquals(2, $result['fields']['tr']);
        $this->assertEquals(3, $result['fields']['ro']);
        $this->assertEquals(4, $result['fields']['se']);
        $this->assertEquals(5, $result['fields']['tl']);
    }

    public function testParseColumnMappingWithRemoveSpaces(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordUploadService();

        $columns = [1 => 'w', 2 => 't'];

        $result = $service->parseColumnMapping($columns, true);

        // With removeSpaces=true, word column uses @wotext instead of WoText
        $this->assertEquals('@wotext', $result['columns'][1]);
    }

    public function testParseColumnMappingWithSkippedColumns(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordUploadService();

        $columns = [
            1 => 'w',
            2 => 'x',
            3 => 't',
        ];

        $result = $service->parseColumnMapping($columns, false);

        $this->assertEquals('WoText', $result['columns'][1]);
        $this->assertEquals('@dummy', $result['columns'][2]);
        $this->assertEquals('WoTranslation', $result['columns'][3]);
    }

    public function testCreateTempFile(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordUploadService();
        $content = "test,translation\nhello,world";

        $fileName = $service->createTempFile($content);

        $this->assertFileExists($fileName);
        $this->assertStringContainsString('LWT', $fileName);

        // Clean up
        unlink($fileName);
    }

    public function testGetLastWordUpdate(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordUploadService();

        // Insert a test word
        $tbpref = self::$tbpref;
        Connection::query(
            "INSERT INTO {$tbpref}words (WoLgID, WoText, WoTextLC, WoStatus, WoTranslation, WoStatusChanged) " .
            "VALUES (" . self::$testLangId . ", 'testword', 'testword', 1, 'test translation', NOW())"
        );

        $lastUpdate = $service->getLastWordUpdate();

        $this->assertNotNull($lastUpdate);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}/', $lastUpdate);
    }

    public function testIsRightToLeft(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordUploadService();

        // Our test language is LTR
        $rtl = $service->isRightToLeft(self::$testLangId);

        $this->assertFalse($rtl);
    }

    public function testCountImportedTerms(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordUploadService();
        $tbpref = self::$tbpref;

        // Get current timestamp
        $beforeInsert = date('Y-m-d H:i:s', strtotime('-1 second'));

        // Insert test words
        Connection::query(
            "INSERT INTO {$tbpref}words (WoLgID, WoText, WoTextLC, WoStatus, WoTranslation, WoStatusChanged) VALUES " .
            "(" . self::$testLangId . ", 'word1', 'word1', 1, 'trans1', NOW()), " .
            "(" . self::$testLangId . ", 'word2', 'word2', 1, 'trans2', NOW())"
        );

        $count = $service->countImportedTerms($beforeInsert);

        $this->assertEquals(2, $count);
    }

    public function testGetImportedTerms(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordUploadService();
        $tbpref = self::$tbpref;

        // Get current timestamp
        $beforeInsert = date('Y-m-d H:i:s', strtotime('-1 second'));

        // Insert test words
        Connection::query(
            "INSERT INTO {$tbpref}words (WoLgID, WoText, WoTextLC, WoStatus, WoTranslation, WoSentence, WoStatusChanged) VALUES " .
            "(" . self::$testLangId . ", 'apple', 'apple', 1, 'pomme', 'I eat an {apple}.', NOW()), " .
            "(" . self::$testLangId . ", 'banana', 'banana', 2, 'banane', '', NOW())"
        );

        $terms = $service->getImportedTerms($beforeInsert, 0, 10);

        $this->assertCount(2, $terms);

        // Check first term (sorted by WoText)
        $this->assertEquals('apple', $terms[0]['WoText']);
        $this->assertEquals('pomme', $terms[0]['WoTranslation']);
        $this->assertEquals('1', $terms[0]['SentOK']); // Has valid sentence

        // Check second term
        $this->assertEquals('banana', $terms[1]['WoText']);
        $this->assertEquals('0', $terms[1]['SentOK']); // No valid sentence
    }

    // ===== Import tests =====

    public function testImportSimpleWithPHP(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordUploadService();
        $tbpref = self::$tbpref;

        // Create a temp file with CSV content
        $content = "cat,chat\ndog,chien\nbird,oiseau";
        $fileName = $service->createTempFile($content);

        $fields = ['txt' => 1, 'tr' => 2, 'ro' => 0, 'se' => 0, 'tl' => 0];
        $columnsClause = '(WoText,WoTranslation)';

        // Use comma delimiter for testing
        $service->importSimple(
            self::$testLangId,
            $fields,
            $columnsClause,
            ',',
            $fileName,
            1,
            false
        );

        // Clean up temp file
        unlink($fileName);

        // Verify words were imported
        $count = (int) Connection::fetchValue(
            "SELECT COUNT(*) AS value FROM {$tbpref}words WHERE WoLgID = " . self::$testLangId
        );

        $this->assertEquals(3, $count);

        // Check specific word
        $word = Connection::fetchValue(
            "SELECT WoTranslation AS value FROM {$tbpref}words WHERE WoTextLC = 'cat' AND WoLgID = " . self::$testLangId
        );
        $this->assertEquals('chat', $word);
    }

    public function testImportWithIgnoreFirstLine(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordUploadService();
        $tbpref = self::$tbpref;

        // Create a temp file with header line
        $content = "Term,Translation\nhouse,maison\ncar,voiture";
        $fileName = $service->createTempFile($content);

        $fields = ['txt' => 1, 'tr' => 2, 'ro' => 0, 'se' => 0, 'tl' => 0];
        $columnsClause = '(WoText,WoTranslation)';

        $service->importSimple(
            self::$testLangId,
            $fields,
            $columnsClause,
            ',',
            $fileName,
            1,
            true  // Ignore first line
        );

        unlink($fileName);

        // Should only import 2 rows (excluding header)
        $count = (int) Connection::fetchValue(
            "SELECT COUNT(*) AS value FROM {$tbpref}words WHERE WoLgID = " . self::$testLangId
        );

        $this->assertEquals(2, $count);

        // Verify 'Term' was not imported as a word
        $termWord = Connection::fetchValue(
            "SELECT WoID AS value FROM {$tbpref}words WHERE WoTextLC = 'term' AND WoLgID = " . self::$testLangId
        );
        $this->assertNull($termWord);
    }

    public function testImportWithHashDelimiter(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordUploadService();
        $tbpref = self::$tbpref;

        // Create a temp file with hash-delimited content
        $content = "red#rouge\nblue#bleu";
        $fileName = $service->createTempFile($content);

        $fields = ['txt' => 1, 'tr' => 2, 'ro' => 0, 'se' => 0, 'tl' => 0];
        $columnsClause = '(WoText,WoTranslation)';

        $service->importSimple(
            self::$testLangId,
            $fields,
            $columnsClause,
            '#',
            $fileName,
            1,
            false
        );

        unlink($fileName);

        // Verify words were imported
        $word = Connection::fetchValue(
            "SELECT WoTranslation AS value FROM {$tbpref}words WHERE WoTextLC = 'red' AND WoLgID = " . self::$testLangId
        );
        $this->assertEquals('rouge', $word);
    }

    // ===== View file tests =====

    public function testUploadFormViewFileExists(): void
    {
        $viewFile = __DIR__ . '/../../../src/backend/Views/Word/upload_form.php';
        $this->assertFileExists($viewFile);
    }

    public function testUploadResultViewFileExists(): void
    {
        $viewFile = __DIR__ . '/../../../src/backend/Views/Word/upload_result.php';
        $this->assertFileExists($viewFile);
    }

    // ===== Edge case tests =====

    public function testImportEmptyContent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordUploadService();
        $tbpref = self::$tbpref;

        // Create a temp file with only whitespace
        $content = "\n\n  \n";
        $fileName = $service->createTempFile($content);

        $fields = ['txt' => 1, 'tr' => 2, 'ro' => 0, 'se' => 0, 'tl' => 0];
        $columnsClause = '(WoText,WoTranslation)';

        $service->importSimple(
            self::$testLangId,
            $fields,
            $columnsClause,
            ',',
            $fileName,
            1,
            false
        );

        unlink($fileName);

        // Should not import any words
        $count = (int) Connection::fetchValue(
            "SELECT COUNT(*) AS value FROM {$tbpref}words WHERE WoLgID = " . self::$testLangId
        );

        $this->assertEquals(0, $count);
    }

    public function testImportWithMissingColumns(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordUploadService();
        $tbpref = self::$tbpref;

        // Content where first line is complete but second line is incomplete
        // The import should skip or handle incomplete lines gracefully
        $content = "word1,trans1\nword2,";  // Second line has empty translation
        $fileName = $service->createTempFile($content);

        $fields = ['txt' => 1, 'tr' => 2, 'ro' => 0, 'se' => 0, 'tl' => 0];
        $columnsClause = '(WoText,WoTranslation)';

        $service->importSimple(
            self::$testLangId,
            $fields,
            $columnsClause,
            ',',
            $fileName,
            1,
            false
        );

        unlink($fileName);

        // At minimum, the first word should be imported
        $word = Connection::fetchValue(
            "SELECT WoTranslation AS value FROM {$tbpref}words WHERE WoTextLC = 'word1' AND WoLgID = " . self::$testLangId
        );
        $this->assertEquals('trans1', $word);

        // Second word should also be imported (with empty translation)
        $word2 = Connection::fetchValue(
            "SELECT WoID AS value FROM {$tbpref}words WHERE WoTextLC = 'word2' AND WoLgID = " . self::$testLangId
        );
        $this->assertNotNull($word2);
    }

    // ===== Status tests =====

    public function testImportWithDifferentStatuses(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordUploadService();
        $tbpref = self::$tbpref;

        // Test import with status 3
        $content = "hello,bonjour";
        $fileName = $service->createTempFile($content);

        $fields = ['txt' => 1, 'tr' => 2, 'ro' => 0, 'se' => 0, 'tl' => 0];
        $columnsClause = '(WoText,WoTranslation)';

        $service->importSimple(
            self::$testLangId,
            $fields,
            $columnsClause,
            ',',
            $fileName,
            3,  // Status 3
            false
        );

        unlink($fileName);

        // Verify word has correct status
        $status = Connection::fetchValue(
            "SELECT WoStatus AS value FROM {$tbpref}words WHERE WoTextLC = 'hello' AND WoLgID = " . self::$testLangId
        );
        $this->assertEquals('3', $status);
    }
}
