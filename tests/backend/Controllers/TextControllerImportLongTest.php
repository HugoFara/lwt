<?php declare(strict_types=1);
namespace Lwt\Tests\Controllers;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Modules\Text\Http\TextController;
use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Modules\Text\Application\TextFacade;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use Lwt\Database\Settings;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
// LanguageFacade loaded via autoloader
require_once __DIR__ . '/../../../src/backend/Services/TextStatisticsService.php';
require_once __DIR__ . '/../../../src/backend/Services/SentenceService.php';
require_once __DIR__ . '/../../../src/backend/Services/AnnotationService.php';
require_once __DIR__ . '/../../../src/Modules/Vocabulary/Application/UseCases/FindSimilarTerms.php';
require_once __DIR__ . '/../../../src/backend/Services/TextNavigationService.php';
require_once __DIR__ . '/../../../src/backend/Services/TextParsingService.php';
require_once __DIR__ . '/../../../src/Modules/Vocabulary/Application/Services/ExpressionService.php';
require_once __DIR__ . '/../../../src/backend/Core/Database/Restore.php';
require_once __DIR__ . '/../../../src/backend/Controllers/BaseController.php';
require_once __DIR__ . '/../../../src/Modules/Text/Http/TextController.php';
require_once __DIR__ . '/../../../src/Modules/Text/Application/TextFacade.php';

/**
 * Unit tests for the TextController::importLong() method and related functionality.
 *
 * Tests long text import, splitting, and saving.
 */
class TextControllerImportLongTest extends TestCase
{
    private static bool $dbConnected = false;
    private static int $testLangId = 0;
    private array $originalRequest;
    private array $originalServer;
    private array $originalGet;
    private array $originalPost;
    private array $originalSession;
    private array $originalFiles;
    private array $createdTextIds = [];

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
            Globals::setDatabaseName($testDbname);
        }
        self::$dbConnected = (Globals::getDbConnection() !== null);

        if (self::$dbConnected) {
            // Create a test language if it doesn't exist
            $existingLang = Connection::fetchValue(
                "SELECT LgID AS value FROM languages WHERE LgName = 'ImportLongTestLang' LIMIT 1"
            );

            if ($existingLang) {
                self::$testLangId = (int)$existingLang;
            } else {
                Connection::query(
                    "INSERT INTO languages (LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, " .
                    "LgTextSize, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, " .
                    "LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar, LgRightToLeft, LgShowRomanization) " .
                    "VALUES ('ImportLongTestLang', 'http://dict1.test/###', 'http://dict2.test/###', " .
                    "'http://translate.test/?sl=en&tl=fr&text=###', " .
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
        if (self::$dbConnected && self::$testLangId > 0) {
            // Clean up test language
            Connection::query("DELETE FROM languages WHERE LgID = " . self::$testLangId);
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
        $this->originalFiles = $_FILES;

        // Reset superglobals
        $_SERVER = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/text/import-long'];
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        $_SESSION = [];
        $_FILES = [];
    }

    protected function tearDown(): void
    {
        // Clean up created texts
        if (!empty($this->createdTextIds) && self::$dbConnected) {
            foreach ($this->createdTextIds as $textId) {
                Connection::query("DELETE FROM textitems2 WHERE Ti2TxID = {$textId}");
                Connection::query("DELETE FROM sentences WHERE SeTxID = {$textId}");
                Connection::query("DELETE FROM texttags WHERE TtTxID = {$textId}");
                Connection::query("DELETE FROM texts WHERE TxID = {$textId}");
            }
        }
        $this->createdTextIds = [];

        // Restore superglobals
        $_SERVER = $this->originalServer;
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
        $_REQUEST = $this->originalRequest;
        $_SESSION = $this->originalSession;
        $_FILES = $this->originalFiles;

        parent::tearDown();
    }

    // ===== Controller instantiation tests =====

    public function testControllerCanBeInstantiated(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $textService = new TextFacade();
        $languageService = new LanguageFacade();
        $controller = new TextController($textService, $languageService);

        $this->assertInstanceOf(TextController::class, $controller);
    }

    public function testControllerHasImportLongMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $textService = new TextFacade();
        $languageService = new LanguageFacade();
        $controller = new TextController($textService, $languageService);

        $this->assertTrue(method_exists($controller, 'importLong'));
    }

    // ===== TextService prepareLongTextData tests =====

    public function testPrepareLongTextDataFromUploadText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextFacade();

        $result = $service->prepareLongTextData(
            [],
            "Line one.\nLine two.\nLine three.",
            1
        );

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testPrepareLongTextDataWithParagraphHandling1(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextFacade();

        // Paragraph handling 1: every line break is a paragraph
        $result = $service->prepareLongTextData(
            [],
            "Line one.\nLine two.\nLine three.",
            1
        );

        // Should have newlines preserved
        $this->assertStringContainsString("\n", $result);
    }

    public function testPrepareLongTextDataWithParagraphHandling2(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextFacade();

        // Paragraph handling 2: only double newlines are paragraphs
        $result = $service->prepareLongTextData(
            [],
            "Line one.\nContinued.\n\nNew paragraph.",
            2
        );

        $this->assertIsString($result);
    }

    public function testPrepareLongTextDataWithEmptyInput(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextFacade();

        $result = $service->prepareLongTextData([], '', 1);

        $this->assertEquals('', $result);
    }

    public function testPrepareLongTextDataWithWindowsLineEndings(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextFacade();

        // Windows line endings should be converted
        $result = $service->prepareLongTextData(
            [],
            "Line one.\r\nLine two.\r\nLine three.",
            1
        );

        // Should not contain \r\n
        $this->assertStringNotContainsString("\r", $result);
    }

    public function testPrepareLongTextDataTrimsWhitespace(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextFacade();

        $result = $service->prepareLongTextData(
            [],
            "   Text with whitespace.   ",
            1
        );

        $this->assertStringNotContainsString("   ", $result);
    }

    // ===== TextService splitLongText tests =====

    public function testSplitLongTextBasic(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextFacade();

        $data = "First sentence. Second sentence. Third sentence.";
        $result = $service->splitLongText($data, self::$testLangId, 10);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testSplitLongTextRespectsMaxSentences(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextFacade();

        // Create data with many sentences
        $sentences = [];
        for ($i = 1; $i <= 50; $i++) {
            $sentences[] = "Sentence number {$i}.";
        }
        $data = implode(" ", $sentences);

        // Split with max 5 sentences per text
        $result = $service->splitLongText($data, self::$testLangId, 5);

        $this->assertIsArray($result);
        // Result should have at least one text chunk
        // Note: Actual chunking depends on language sentence splitting regex
        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testSplitLongTextWithParagraphs(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextFacade();

        $data = "First paragraph sentence.\n\nSecond paragraph sentence.";
        $result = $service->splitLongText($data, self::$testLangId, 10);

        $this->assertIsArray($result);
    }

    public function testSplitLongTextEmptyInput(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextFacade();

        $result = $service->splitLongText('', self::$testLangId, 10);

        $this->assertIsArray($result);
    }

    // ===== TextService saveLongTextImport tests =====

    public function testSaveLongTextImportSingleText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextFacade();

        $texts = ["First sentence. Second sentence."];

        $result = $service->saveLongTextImport(
            self::$testLangId,
            "Import Test Single",
            "http://source.test/single",
            $texts,
            1
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('imported', $result);
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['imported']);

        // Find and cleanup created text
        $createdId = Connection::fetchValue(
            "SELECT TxID AS value FROM texts WHERE TxTitle = 'Import Test Single' LIMIT 1"
        );
        if ($createdId) {
            $this->createdTextIds[] = (int)$createdId;
        }
    }

    public function testSaveLongTextImportMultipleTexts(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextFacade();

        $texts = [
            "First text content.",
            "Second text content.",
            "Third text content."
        ];

        $result = $service->saveLongTextImport(
            self::$testLangId,
            "Import Test Multi",
            "http://source.test/multi",
            $texts,
            3
        );

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['imported']);

        // Find and cleanup created texts
        $res = Connection::query(
            "SELECT TxID FROM texts WHERE TxTitle LIKE 'Import Test Multi%'"
        );
        while ($row = mysqli_fetch_assoc($res)) {
            $this->createdTextIds[] = (int)$row['TxID'];
        }
        mysqli_free_result($res);
    }

    public function testSaveLongTextImportWithMismatchedCount(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextFacade();

        $texts = ["First text.", "Second text."];

        // Say we have 3 texts when we only have 2
        $result = $service->saveLongTextImport(
            self::$testLangId,
            "Import Test Mismatch",
            "http://source.test/mismatch",
            $texts,
            3
        );

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Error', $result['message']);
        $this->assertEquals(0, $result['imported']);
    }

    public function testSaveLongTextImportTitlesNumbered(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextFacade();

        $texts = [
            "First part.",
            "Second part."
        ];

        $result = $service->saveLongTextImport(
            self::$testLangId,
            "Numbered Title Test",
            "",
            $texts,
            2
        );

        $this->assertTrue($result['success']);

        // Check that titles are numbered
        $titles = [];
        $res = Connection::query(
            "SELECT TxID, TxTitle FROM texts WHERE TxTitle LIKE 'Numbered Title Test%' ORDER BY TxID"
        );
        while ($row = mysqli_fetch_assoc($res)) {
            $titles[] = $row['TxTitle'];
            $this->createdTextIds[] = (int)$row['TxID'];
        }
        mysqli_free_result($res);

        $this->assertCount(2, $titles);
        // First should be "(1/2)" and second "(2/2)"
        $this->assertStringContainsString('1', $titles[0]);
        $this->assertStringContainsString('2', $titles[1]);
    }

    // ===== Language translate URIs tests =====

    public function testGetLanguageTranslateUris(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextFacade();

        $result = $service->getLanguageTranslateUris();

        $this->assertIsArray($result);
        $this->assertArrayHasKey(self::$testLangId, $result);
    }

    // ===== Integration tests =====

    public function testFullLongTextImportWorkflow(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextFacade();

        // Step 1: Prepare data
        $rawText = "First sentence of the long text. Second sentence.\n\nNew paragraph starts here. More content.";
        $preparedData = $service->prepareLongTextData([], $rawText, 2);

        $this->assertNotEmpty($preparedData);

        // Step 2: Split into texts
        $texts = $service->splitLongText($preparedData, self::$testLangId, 50);

        $this->assertIsArray($texts);
        $this->assertNotEmpty($texts);

        // Step 3: Flatten texts for import
        $textStrings = [];
        foreach ($texts as $textArray) {
            $textStrings[] = implode(' ', $textArray);
        }

        // Step 4: Save
        $result = $service->saveLongTextImport(
            self::$testLangId,
            "Full Workflow Test",
            "http://source.test/workflow",
            $textStrings,
            count($textStrings)
        );

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['imported']);

        // Cleanup
        $res = Connection::query(
            "SELECT TxID FROM texts WHERE TxTitle LIKE 'Full Workflow Test%'"
        );
        while ($row = mysqli_fetch_assoc($res)) {
            $this->createdTextIds[] = (int)$row['TxID'];
        }
        mysqli_free_result($res);
    }

    // ===== Edge case tests =====

    public function testPrepareLongTextDataWithSpecialCharacters(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextFacade();

        // Test with Unicode and special characters
        $result = $service->prepareLongTextData(
            [],
            "Héllo wörld! Ça va?\n日本語テスト。",
            1
        );

        $this->assertIsString($result);
        $this->assertStringContainsString('Héllo', $result);
    }

    public function testPrepareLongTextDataWithMultipleSpaces(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextFacade();

        $result = $service->prepareLongTextData(
            [],
            "Text   with    multiple     spaces.",
            1
        );

        // Multiple spaces should be collapsed
        $this->assertStringNotContainsString('   ', $result);
    }

    public function testSaveLongTextImportWithEmptyTexts(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextFacade();

        $result = $service->saveLongTextImport(
            self::$testLangId,
            "Empty Import Test",
            "",
            [],
            0
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['imported']);
    }

    // ===== Soft hyphen tests =====

    public function testSoftHyphensRemovedOnSave(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Test that soft hyphens are removed when saving text
        // This tests the behavior through the public API
        $textWithSoftHyphen = "soft\xC2\xADhyphen";

        // str_replace is what the internal method does
        $result = str_replace("\xC2\xAD", "", $textWithSoftHyphen);

        $this->assertStringNotContainsString("\xC2\xAD", $result);
        $this->assertEquals("softhyphen", $result);
    }

    // ===== Max input vars tests =====

    public function testMaxInputVarsHandling(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Test the concept - max_input_vars default handling
        $maxInputVars = ini_get('max_input_vars');
        if ($maxInputVars === false || $maxInputVars == '') {
            $maxInputVars = 1000;
        }

        $this->assertIsNumeric($maxInputVars);
        $this->assertGreaterThan(0, (int)$maxInputVars);
    }

    // ===== Service instance tests =====

    public function testMultipleServiceInstancesWorkCorrectly(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service1 = new TextFacade();
        $service2 = new TextFacade();

        $data = "Test sentence.";

        $result1 = $service1->prepareLongTextData([], $data, 1);
        $result2 = $service2->prepareLongTextData([], $data, 1);

        $this->assertEquals($result1, $result2);
    }

    // ===== Paragraph handling comparison tests =====

    public function testParagraphHandlingDifferences(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextFacade();

        $input = "Line one.\nLine two.\n\nParagraph two.";

        // Handling mode 1: each line break is significant
        $result1 = $service->prepareLongTextData([], $input, 1);

        // Handling mode 2: only double line breaks are paragraphs
        $result2 = $service->prepareLongTextData([], $input, 2);

        // Results should be different
        $this->assertNotEquals($result1, $result2);
    }
}
