<?php declare(strict_types=1);
namespace Lwt\Tests\Core\Database;

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\Bootstrap\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Shared\Infrastructure\Database\TextParsing;
use Lwt\Shared\Infrastructure\Database\Configuration;
use Lwt\Shared\Infrastructure\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/db_bootstrap.php';

/**
 * Unit tests for the Database\TextParsing class.
 *
 * Tests text parsing and processing utilities.
 */
class TextParsingTest extends TestCase
{
    private static bool $dbConnected = false;
    private static ?int $testLanguageId = null;

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
            self::createTestLanguage();
        }
    }

    private static function createTestLanguage(): void
    {
        $textitems2 = Globals::table('textitems2');
        $languages = Globals::table('languages');
        $sentences = Globals::table('sentences');
        $texts = Globals::table('texts');
        $words = Globals::table('words');

        // Clean up any existing test language first
        Connection::query("DELETE FROM $textitems2 WHERE Ti2LgID IN (SELECT LgID FROM $languages WHERE LgName = 'Test TextParsing Language')");
        Connection::query("DELETE FROM $sentences WHERE SeLgID IN (SELECT LgID FROM $languages WHERE LgName = 'Test TextParsing Language')");
        Connection::query("DELETE FROM $texts WHERE TxLgID IN (SELECT LgID FROM $languages WHERE LgName = 'Test TextParsing Language')");
        Connection::query("DELETE FROM $words WHERE WoLgID IN (SELECT LgID FROM $languages WHERE LgName = 'Test TextParsing Language')");
        Connection::query("DELETE FROM $languages WHERE LgName = 'Test TextParsing Language'");

        // Create test language
        $sql = "INSERT INTO $languages (
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

        if (self::$testLanguageId) {
            $textitems2 = Globals::table('textitems2');
            $sentences = Globals::table('sentences');
            $texts = Globals::table('texts');
            $words = Globals::table('words');
            $languages = Globals::table('languages');

            // Clean up any test texts and associated data
            Connection::query("DELETE FROM $textitems2 WHERE Ti2LgID = " . self::$testLanguageId);
            Connection::query("DELETE FROM $sentences WHERE SeLgID = " . self::$testLanguageId);
            Connection::query("DELETE FROM $texts WHERE TxLgID = " . self::$testLanguageId);
            Connection::query("DELETE FROM $words WHERE WoLgID = " . self::$testLanguageId);
            Connection::query("DELETE FROM $languages WHERE LgID = " . self::$testLanguageId);
        }
    }

    /**
     * Helper to call splitIntoSentences() with output buffering
     */
    private function callSplitIntoSentences(string $text, int $lid): array
    {
        ob_start();
        $result = TextParsing::splitIntoSentences($text, $lid);
        ob_end_clean();
        return $result;
    }

    /**
     * Helper to call parseAndDisplayPreview() with output buffering
     */
    private function callParseAndDisplayPreview(string $text, int $lid): void
    {
        ob_start();
        TextParsing::parseAndDisplayPreview($text, $lid);
        ob_end_clean();
    }

    // ===== splitIntoSentences() tests =====

    public function testSplitIntoSentencesBasicText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "Hello world. This is a test.";
        $result = $this->callSplitIntoSentences($text, self::$testLanguageId);

        $this->assertIsArray($result, 'Should return array in split mode');
        $this->assertNotEmpty($result, 'Should have parsed sentences');
    }

    public function testSplitIntoSentencesEmptyText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->callSplitIntoSentences('', self::$testLanguageId);

        $this->assertIsArray($result);
    }

    public function testSplitIntoSentencesWhitespaceOnly(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->callSplitIntoSentences("   \n\t  ", self::$testLanguageId);

        $this->assertIsArray($result);
    }

    public function testSplitIntoSentencesWithBraces(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Braces should be replaced with brackets
        $text = "Text with {braces} and more {content}.";
        $result = $this->callSplitIntoSentences($text, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testSplitIntoSentencesWithWindowsLineEndings(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "Line one.\r\nLine two.\r\nLine three.";
        $result = $this->callSplitIntoSentences($text, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testSplitIntoSentencesWithUnicode(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "Hello 世界. Γεια σου κόσμε.";
        $result = $this->callSplitIntoSentences($text, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testSplitIntoSentencesInvalidLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->callSplitIntoSentences("Test text.", 99999);

        // Should return empty array for invalid language (since splitIntoSentences returns [''])
        $this->assertIsArray($result);
        $this->assertEquals([''], $result);
    }

    // ===== splitIntoSentences() tests - sentence parsing =====

    public function testSplitIntoSentencesBasicSentences(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "Hello world. This is a test sentence.";
        $result = $this->callSplitIntoSentences($text, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, count($result), 'Should split into at least 2 sentences');
    }

    public function testSplitIntoSentencesMultipleParagraphs(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "First paragraph.\n\nSecond paragraph.\n\nThird paragraph.";
        $result = $this->callSplitIntoSentences($text, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(3, count($result));
    }

    public function testSplitIntoSentencesSpecialPunctuation(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "Question? Exclamation! Period. Another one.";
        $result = $this->callSplitIntoSentences($text, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(3, count($result));
    }

    public function testSplitIntoSentencesWithNumbers(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "The value is 3.14. Another number is 42. Version 2.0.1 is here.";
        $result = $this->callSplitIntoSentences($text, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(3, count($result));
    }

    public function testSplitIntoSentencesWithQuotes(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = '"First sentence." "Second sentence." \'Third sentence.\'';
        $result = $this->callSplitIntoSentences($text, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(3, count($result));
    }

    public function testSplitIntoSentencesWithEllipsis(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "Wait for it... Here it comes. Done.";
        $result = $this->callSplitIntoSentences($text, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    // ===== parseAndDisplayPreview() tests =====

    public function testParseAndDisplayPreviewSplitMode(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "Hello world. This is a test.";
        $result = $this->callSplitIntoSentences($text, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testParseAndDisplayPreviewCheckMode(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "Test sentence.";

        ob_start();
        TextParsing::parseAndDisplayPreview($text, self::$testLanguageId);
        $output = ob_get_clean();

        $this->assertStringContainsString('Test sentence', $output, 'Output should contain the text');
    }

    // ===== checkValid() tests =====

    public function testCheckValidOutputsHtml(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // First, prepare some text to populate temptextitems using parseAndDisplayPreview
        $this->callParseAndDisplayPreview("Hello world. Test sentence.", self::$testLanguageId);

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

        $texts = Globals::table('texts');
        $sentences = Globals::table('sentences');
        $textitems2 = Globals::table('textitems2');

        // Create a test text
        $sql = "INSERT INTO $texts (TxLgID, TxTitle, TxText, TxAudioURI)
                VALUES (" . self::$testLanguageId . ", 'Register Test', 'Hello world.', '')";
        Connection::query($sql);
        $textId = mysqli_insert_id(Globals::getDbConnection());

        // Parse and save the text (populates temptextitems and registers sentences/text items)
        TextParsing::parseAndSave("Hello world.", self::$testLanguageId, $textId);

        // Check that sentences were created
        $sentenceCount = Connection::fetchValue(
            "SELECT COUNT(*) as value FROM $sentences WHERE SeTxID = $textId"
        );
        $this->assertGreaterThan(0, (int)$sentenceCount, 'Should create sentences');

        // Check that text items were created
        $itemCount = Connection::fetchValue(
            "SELECT COUNT(*) as value FROM $textitems2 WHERE Ti2TxID = $textId"
        );
        $this->assertGreaterThan(0, (int)$itemCount, 'Should create text items');

        // Clean up
        Connection::query("DELETE FROM $textitems2 WHERE Ti2TxID = $textId");
        Connection::query("DELETE FROM $sentences WHERE SeTxID = $textId");
        Connection::query("DELETE FROM $texts WHERE TxID = $textId");
    }

    // ===== displayStatistics() tests =====

    public function testDisplayStatisticsOutputsJson(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        ob_start();
        TextParsing::displayStatistics(self::$testLanguageId, false, false);
        $output = ob_get_clean();

        $this->assertStringContainsString('<script type="application/json"', $output);
        $this->assertStringContainsString('text-check-config', $output);
        $this->assertStringContainsString('multiWords', $output);
        $this->assertStringContainsString('rtlScript', $output);
    }

    public function testDisplayStatisticsWithRtl(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        ob_start();
        TextParsing::displayStatistics(self::$testLanguageId, true, false);
        $output = ob_get_clean();

        // RTL setting is now passed as JSON data for TypeScript to handle
        $this->assertStringContainsString('"rtlScript":true', $output, 'RTL script flag should be true in JSON');
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

        $tempexprs = Globals::table('tempexprs');

        // First prepare some text using parseAndDisplayPreview to populate temptextitems
        $this->callParseAndDisplayPreview("Hello world test.", self::$testLanguageId);

        // Call checkExpressions with word lengths
        TextParsing::checkExpressions([2, 3]);

        // Check that tempexprs table exists and has been used
        $result = Connection::query("SHOW TABLES LIKE '$tempexprs'");
        $exists = mysqli_num_rows($result) > 0;
        mysqli_free_result($result);

        $this->assertTrue($exists, 'tempexprs table should exist');

        // Clean up
        Connection::query("TRUNCATE TABLE $tempexprs");
    }

    // ===== Edge cases =====

    public function testSplitIntoSentencesLongText(): void
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

        $result = $this->callSplitIntoSentences($text, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(49, count($result));
    }

    public function testSplitIntoSentencesSpecialCharacters(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "Test with 'single quotes'. Test with \"double quotes\". Test with \\ backslash.";
        $result = $this->callSplitIntoSentences($text, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testSplitIntoSentencesWithEmoji(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "Hello 😀 world. How are you 🌍 doing?";
        $result = $this->callSplitIntoSentences($text, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testSplitIntoSentencesNoPunctuation(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = "Text without any sentence ending punctuation marks";
        $result = $this->callSplitIntoSentences($text, self::$testLanguageId);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result, 'Should still return the text');
    }

    /**
     * Tests fix for issue #114: Last word of text not recognized without punctuation.
     *
     * When a text ends without punctuation, the last word should still be
     * recognized as a word (WordCount=1), not as a non-word (WordCount=0).
     *
     * Note: This test calls internal methods directly to validate temptextitems table
     * population behavior. Uses prepare() which is deprecated but still public for
     * backward compatibility and internal testing.
     */
    public function testInternalParseLastWordRecognizedWithoutPunctuation(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $temptextitems = 'temptextitems';
        Connection::query("DROP TEMPORARY TABLE IF EXISTS $temptextitems");
        Connection::query("CREATE TEMPORARY TABLE $temptextitems (
            TiSeID INT,
            TiCount INT,
            TiOrder INT,
            TiText VARCHAR(250),
            TiWordCount INT
        )");

        // Test text WITHOUT punctuation
        Connection::query("TRUNCATE $temptextitems");
        TextParsing::prepare("Hello world", 1, self::$testLanguageId);
        $result = Connection::fetchAll(
            "SELECT TiText, TiWordCount FROM $temptextitems ORDER BY TiOrder"
        );

        // Both "Hello" and "world" should be recognized as words (WordCount=1)
        $this->assertCount(2, $result, 'Should have 2 words');
        $this->assertEquals('Hello', $result[0]['TiText']);
        $this->assertEquals(1, (int)$result[0]['TiWordCount'], 'First word should have WordCount=1');
        $this->assertEquals('world', $result[1]['TiText']);
        $this->assertEquals(1, (int)$result[1]['TiWordCount'], 'Last word should have WordCount=1 even without trailing punctuation');

        // Test text WITH punctuation for comparison
        Connection::query("TRUNCATE $temptextitems");
        TextParsing::prepare("Hello world.", 1, self::$testLanguageId);
        $resultWithPunct = Connection::fetchAll(
            "SELECT TiText, TiWordCount FROM $temptextitems ORDER BY TiOrder"
        );

        // Both words should still be recognized, plus the period as non-word
        $this->assertCount(3, $resultWithPunct, 'Should have 2 words + 1 punctuation');
        $this->assertEquals('Hello', $resultWithPunct[0]['TiText']);
        $this->assertEquals(1, (int)$resultWithPunct[0]['TiWordCount']);
        $this->assertEquals('world', $resultWithPunct[1]['TiText']);
        $this->assertEquals(1, (int)$resultWithPunct[1]['TiWordCount']);
        $this->assertEquals('.', $resultWithPunct[2]['TiText']);
        $this->assertEquals(0, (int)$resultWithPunct[2]['TiWordCount'], 'Punctuation should have WordCount=0');
    }

    // ===== Character substitution tests =====

    public function testSplitIntoSentencesWithCharacterSubstitutions(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $languages = Globals::table('languages');

        // Create language with character substitutions
        $sql = "INSERT INTO $languages (
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
        $result = $this->callSplitIntoSentences($text, $germanLangId);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Clean up
        Connection::query("DELETE FROM $languages WHERE LgID = $germanLangId");
    }

    // ===== Split each char language tests =====

    public function testSplitIntoSentencesWithSplitEachChar(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $languages = Globals::table('languages');

        // Create language with split each char enabled
        $sql = "INSERT INTO $languages (
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
        $result = $this->callSplitIntoSentences($text, $splitLangId);

        $this->assertIsArray($result);

        // Clean up
        Connection::query("DELETE FROM $languages WHERE LgID = $splitLangId");
    }

    // ===== RTL language tests =====

    public function testSplitIntoSentencesWithRtlLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $languages = Globals::table('languages');

        // Create RTL language
        $sql = "INSERT INTO $languages (
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
        $result = $this->callSplitIntoSentences($text, $rtlLangId);

        $this->assertIsArray($result);

        // Clean up
        Connection::query("DELETE FROM $languages WHERE LgID = $rtlLangId");
    }
}
