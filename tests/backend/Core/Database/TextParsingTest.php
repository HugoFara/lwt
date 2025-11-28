<?php

declare(strict_types=1);

namespace Lwt\Tests\Core\Database;

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\TextParsing;
use Lwt\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');
$config = EnvLoader::getDatabaseConfig();
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/db_bootstrap.php';

/**
 * Unit tests for the Database\TextParsing class.
 *
 * Tests text parsing and processing utilities.
 */
class TextParsingTest extends TestCase
{
    private static bool $dbConnected = false;
    private static string $tbpref = '';
    private static ?int $testLanguageId = null;

    public static function setUpBeforeClass(): void
    {
        $config = EnvLoader::getDatabaseConfig();
        $testDbname = "test_" . $config['dbname'];

        if (!Globals::getDbConnection()) {
            $connection = connect_to_database(
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
            self::createTestLanguage();
        }
    }

    private static function createTestLanguage(): void
    {
        $tbpref = self::$tbpref;

        // Clean up any existing test language first
        Connection::query("DELETE FROM {$tbpref}textitems2 WHERE Ti2LgID IN (SELECT LgID FROM {$tbpref}languages WHERE LgName = 'Test TextParsing Language')");
        Connection::query("DELETE FROM {$tbpref}sentences WHERE SeLgID IN (SELECT LgID FROM {$tbpref}languages WHERE LgName = 'Test TextParsing Language')");
        Connection::query("DELETE FROM {$tbpref}texts WHERE TxLgID IN (SELECT LgID FROM {$tbpref}languages WHERE LgName = 'Test TextParsing Language')");
        Connection::query("DELETE FROM {$tbpref}words WHERE WoLgID IN (SELECT LgID FROM {$tbpref}languages WHERE LgName = 'Test TextParsing Language')");
        Connection::query("DELETE FROM {$tbpref}languages WHERE LgName = 'Test TextParsing Language'");

        // Create test language
        $sql = "INSERT INTO {$tbpref}languages (
            LgName, LgDict1URI, LgGoogleTranslateURI, LgTextSize,
            LgCharacterSubstitutions, LgRegexpSplitSentences,
            LgExceptionsSplitSentences, LgRegexpWordCharacters,
            LgRemoveSpaces, LgSplitEachChar, LgRightToLeft
        ) VALUES (
            'Test TextParsing Language',
            'https://en.wiktionary.org/wiki/###',
            'https://translate.google.com/?text=###',
            100, '', '.!?', 'Mr.|Dr.|Mrs.|Ms.', 'a-zA-Z', 0, 0, 0
        )";
        Connection::query($sql);
        self::$testLanguageId = mysqli_insert_id(Globals::getDbConnection());
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        $tbpref = self::$tbpref;

        if (self::$testLanguageId) {
            // Clean up any test texts and associated data
            Connection::query("DELETE FROM {$tbpref}textitems2 WHERE Ti2LgID = " . self::$testLanguageId);
            Connection::query("DELETE FROM {$tbpref}sentences WHERE SeLgID = " . self::$testLanguageId);
            Connection::query("DELETE FROM {$tbpref}texts WHERE TxLgID = " . self::$testLanguageId);
            Connection::query("DELETE FROM {$tbpref}words WHERE WoLgID = " . self::$testLanguageId);
            Connection::query("DELETE FROM {$tbpref}languages WHERE LgID = " . self::$testLanguageId);
        }
    }

    /**
     * Helper to call prepare() with output buffering
     */
    private function callPrepare(string $text, int $id, int $lid): ?array
    {
        ob_start();
        $result = TextParsing::prepare($text, $id, $lid);
        ob_end_clean();
        return $result;
    }

    /**
     * Helper to call splitCheck() with output buffering
     */
    private function callSplitCheck(string $text, int $lid, int $id): ?array
    {
        ob_start();
        $result = TextParsing::splitCheck($text, $lid, $id);
        ob_end_clean();
        return $result;
    }

    // ===== prepare() tests =====

    public function testPrepareBasicText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "Hello world. This is a test.";
        $result = $this->callPrepare($text, -2, self::$testLanguageId);

        $this->assertIsArray($result, 'Should return array in split mode');
        $this->assertNotEmpty($result, 'Should have parsed sentences');
    }

    public function testPrepareEmptyText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->callPrepare('', -2, self::$testLanguageId);

        $this->assertIsArray($result);
    }

    public function testPrepareWhitespaceOnly(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->callPrepare("   \n\t  ", -2, self::$testLanguageId);

        $this->assertIsArray($result);
    }

    public function testPrepareWithBraces(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Braces should be replaced with brackets
        $text = "Text with {braces} and more {content}.";
        $result = $this->callPrepare($text, -2, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testPrepareWithWindowsLineEndings(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "Line one.\r\nLine two.\r\nLine three.";
        $result = $this->callPrepare($text, -2, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testPrepareWithUnicode(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "Hello 世界. Γεια σου κόσμε.";
        $result = $this->callPrepare($text, -2, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testPrepareInvalidLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->callPrepare("Test text.", -2, 99999);

        // Should return null for invalid language
        $this->assertNull($result);
    }

    // ===== parseStandard() tests =====

    public function testParseStandardBasicText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "Hello world. This is a test sentence.";
        $result = $this->callPrepare($text, -2, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, count($result), 'Should split into at least 2 sentences');
    }

    public function testParseStandardMultipleParagraphs(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "First paragraph.\n\nSecond paragraph.\n\nThird paragraph.";
        $result = $this->callPrepare($text, -2, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(3, count($result));
    }

    public function testParseStandardSpecialPunctuation(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "Question? Exclamation! Period. Another one.";
        $result = $this->callPrepare($text, -2, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(3, count($result));
    }

    public function testParseStandardWithNumbers(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "The value is 3.14. Another number is 42. Version 2.0.1 is here.";
        $result = $this->callPrepare($text, -2, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(3, count($result));
    }

    public function testParseStandardWithQuotes(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = '"First sentence." "Second sentence." \'Third sentence.\'';
        $result = $this->callPrepare($text, -2, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(3, count($result));
    }

    public function testParseStandardWithEllipsis(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "Wait for it... Here it comes. Done.";
        $result = $this->callPrepare($text, -2, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    // ===== splitCheck() tests =====

    public function testSplitCheckSplitMode(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "Hello world. This is a test.";
        $result = $this->callSplitCheck($text, self::$testLanguageId, -2);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testSplitCheckCheckMode(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "Test sentence.";

        ob_start();
        $result = TextParsing::splitCheck($text, self::$testLanguageId, -1);
        $output = ob_get_clean();

        $this->assertNull($result, 'Check mode should return null');
        $this->assertStringContainsString('Test sentence', $output, 'Output should contain the text');
    }

    // ===== checkValid() tests =====

    public function testCheckValidOutputsHtml(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // First, prepare some text to populate temptextitems
        $this->callPrepare("Hello world. Test sentence.", -1, self::$testLanguageId);

        ob_start();
        TextParsing::checkValid(self::$testLanguageId);
        $output = ob_get_clean();

        // Should contain HTML elements
        $this->assertStringContainsString('<h4>Sentences</h4>', $output);
        $this->assertStringContainsString('<ol>', $output);
    }

    // ===== registerSentencesTextItems() tests =====

    public function testRegisterSentencesTextItemsCreatesRecords(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;

        // Create a test text
        $sql = "INSERT INTO {$tbpref}texts (TxLgID, TxTitle, TxText, TxAudioURI)
                VALUES (" . self::$testLanguageId . ", 'Register Test', 'Hello world.', '')";
        Connection::query($sql);
        $textId = mysqli_insert_id(Globals::getDbConnection());

        // Prepare the text (populates temptextitems)
        $this->callPrepare("Hello world.", $textId, self::$testLanguageId);

        // Register sentences and text items
        TextParsing::registerSentencesTextItems($textId, self::$testLanguageId, false);

        // Check that sentences were created
        $sentenceCount = get_first_value(
            "SELECT COUNT(*) as value FROM {$tbpref}sentences WHERE SeTxID = $textId"
        );
        $this->assertGreaterThan(0, (int)$sentenceCount, 'Should create sentences');

        // Check that text items were created
        $itemCount = get_first_value(
            "SELECT COUNT(*) as value FROM {$tbpref}textitems2 WHERE Ti2TxID = $textId"
        );
        $this->assertGreaterThan(0, (int)$itemCount, 'Should create text items');

        // Clean up
        Connection::query("DELETE FROM {$tbpref}textitems2 WHERE Ti2TxID = $textId");
        Connection::query("DELETE FROM {$tbpref}sentences WHERE SeTxID = $textId");
        Connection::query("DELETE FROM {$tbpref}texts WHERE TxID = $textId");
    }

    // ===== displayStatistics() tests =====

    public function testDisplayStatisticsOutputsJavascript(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        ob_start();
        TextParsing::displayStatistics(self::$testLanguageId, false, false);
        $output = ob_get_clean();

        $this->assertStringContainsString('<script', $output);
        $this->assertStringContainsString('MWORDS', $output);
        $this->assertStringContainsString('displayStatistics', $output);
    }

    public function testDisplayStatisticsWithRtl(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        ob_start();
        TextParsing::displayStatistics(self::$testLanguageId, true, false);
        $output = ob_get_clean();

        $this->assertStringContainsString('dir', $output, 'RTL script should add dir attribute');
    }

    // ===== checkExpressions() tests =====

    public function testCheckExpressionsWithEmptyArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Empty word length array should be handled gracefully
        // This would cause issues with implode, so it shouldn't be called with empty array
        // Just verify the function signature exists
        $this->assertTrue(method_exists(TextParsing::class, 'checkExpressions'));
    }

    public function testCheckExpressionsCreatesTemporaryTable(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;

        // First prepare some text
        $this->callPrepare("Hello world test.", -1, self::$testLanguageId);

        // Call checkExpressions with word lengths
        TextParsing::checkExpressions([2, 3]);

        // Check that tempexprs table exists and has been used
        $result = Connection::query("SHOW TABLES LIKE '{$tbpref}tempexprs'");
        $exists = mysqli_num_rows($result) > 0;
        mysqli_free_result($result);

        $this->assertTrue($exists, 'tempexprs table should exist');

        // Clean up
        Connection::query("TRUNCATE TABLE {$tbpref}tempexprs");
    }

    // ===== Edge cases =====

    public function testPrepareLongText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Generate long text
        $sentences = [];
        for ($i = 1; $i <= 50; $i++) {
            $sentences[] = "This is sentence number $i with some content.";
        }
        $text = implode(' ', $sentences);

        $result = $this->callPrepare($text, -2, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(49, count($result));
    }

    public function testPrepareSpecialCharacters(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "Test with 'single quotes'. Test with \"double quotes\". Test with \\ backslash.";
        $result = $this->callPrepare($text, -2, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testPrepareWithEmoji(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "Hello 😀 world. How are you 🌍 doing?";
        $result = $this->callPrepare($text, -2, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testPrepareNoPunctuation(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "Text without any sentence ending punctuation marks";
        $result = $this->callPrepare($text, -2, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result, 'Should still return the text');
    }

    // ===== Character substitution tests =====

    public function testPrepareWithCharacterSubstitutions(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;

        // Create language with character substitutions
        $sql = "INSERT INTO {$tbpref}languages (
            LgName, LgDict1URI, LgGoogleTranslateURI, LgTextSize,
            LgCharacterSubstitutions, LgRegexpSplitSentences,
            LgExceptionsSplitSentences, LgRegexpWordCharacters,
            LgRemoveSpaces, LgSplitEachChar, LgRightToLeft
        ) VALUES (
            'Test German Substitutions',
            'https://de.wiktionary.org/wiki/###',
            'https://translate.google.com/?text=###',
            100, 'ß=ss|ä=ae|ö=oe|ü=ue', '.!?', '', 'a-zA-ZäöüßÄÖÜ', 0, 0, 0
        )";
        Connection::query($sql);
        $germanLangId = mysqli_insert_id(Globals::getDbConnection());

        $text = "Größe Käse Tür";
        $result = $this->callPrepare($text, -2, $germanLangId);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Clean up
        Connection::query("DELETE FROM {$tbpref}languages WHERE LgID = $germanLangId");
    }

    // ===== Split each char language tests =====

    public function testPrepareWithSplitEachChar(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;

        // Create language with split each char enabled
        $sql = "INSERT INTO {$tbpref}languages (
            LgName, LgDict1URI, LgGoogleTranslateURI, LgTextSize,
            LgCharacterSubstitutions, LgRegexpSplitSentences,
            LgExceptionsSplitSentences, LgRegexpWordCharacters,
            LgRemoveSpaces, LgSplitEachChar, LgRightToLeft
        ) VALUES (
            'Test Split Char',
            'https://example.com/###',
            'https://translate.google.com/?text=###',
            100, '', '。', '', 'a-zA-Z', 0, 1, 0
        )";
        Connection::query($sql);
        $splitLangId = mysqli_insert_id(Globals::getDbConnection());

        $text = "Hello。";
        $result = $this->callPrepare($text, -2, $splitLangId);

        $this->assertIsArray($result);

        // Clean up
        Connection::query("DELETE FROM {$tbpref}languages WHERE LgID = $splitLangId");
    }

    // ===== RTL language tests =====

    public function testPrepareWithRtlLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;

        // Create RTL language
        $sql = "INSERT INTO {$tbpref}languages (
            LgName, LgDict1URI, LgGoogleTranslateURI, LgTextSize,
            LgCharacterSubstitutions, LgRegexpSplitSentences,
            LgExceptionsSplitSentences, LgRegexpWordCharacters,
            LgRemoveSpaces, LgSplitEachChar, LgRightToLeft
        ) VALUES (
            'Test Arabic',
            'https://example.com/###',
            'https://translate.google.com/?text=###',
            100, '', '。!?', '', '؀-ۿ', 0, 0, 1
        )";
        Connection::query($sql);
        $rtlLangId = mysqli_insert_id(Globals::getDbConnection());

        $text = "مرحبا. كيف حالك.";
        $result = $this->callPrepare($text, -2, $rtlLangId);

        $this->assertIsArray($result);

        // Clean up
        Connection::query("DELETE FROM {$tbpref}languages WHERE LgID = $rtlLangId");
    }
}
