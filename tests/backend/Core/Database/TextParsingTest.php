<?php

declare(strict_types=1);

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
        $word_occurrences = Globals::table('word_occurrences');
        $languages = Globals::table('languages');
        $sentences = Globals::table('sentences');
        $texts = Globals::table('texts');
        $words = Globals::table('words');

        // Clean up any existing test language first
        Connection::query("DELETE FROM $word_occurrences WHERE Ti2LgID IN (SELECT LgID FROM $languages WHERE LgName = 'Test TextParsing Language')");
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
            $word_occurrences = Globals::table('word_occurrences');
            $sentences = Globals::table('sentences');
            $texts = Globals::table('texts');
            $words = Globals::table('words');
            $languages = Globals::table('languages');

            // Clean up any test texts and associated data
            Connection::query("DELETE FROM $word_occurrences WHERE Ti2LgID = " . self::$testLanguageId);
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

    // ===== parseAndSave() tests =====

    public function testParseAndSaveCreatesSentencesAndTextItems(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $texts = Globals::table('texts');
        $sentences = Globals::table('sentences');
        $word_occurrences = Globals::table('word_occurrences');

        // Create a test text
        $sql = "INSERT INTO $texts (TxLgID, TxTitle, TxText, TxAudioURI)
                VALUES (" . self::$testLanguageId . ", 'Register Test', 'Hello world.', '')";
        Connection::query($sql);
        $textId = mysqli_insert_id(Globals::getDbConnection());

        // Parse and save the text (populates temp_word_occurrences and registers sentences/text items)
        TextParsing::parseAndSave("Hello world.", self::$testLanguageId, $textId);

        // Check that sentences were created
        $sentenceCount = Connection::fetchValue(
            "SELECT COUNT(*) as value FROM $sentences WHERE SeTxID = $textId"
        );
        $this->assertGreaterThan(0, (int)$sentenceCount, 'Should create sentences');

        // Check that text items were created
        $itemCount = Connection::fetchValue(
            "SELECT COUNT(*) as value FROM $word_occurrences WHERE Ti2TxID = $textId"
        );
        $this->assertGreaterThan(0, (int)$itemCount, 'Should create text items');

        // Clean up
        Connection::query("DELETE FROM $word_occurrences WHERE Ti2TxID = $textId");
        Connection::query("DELETE FROM $sentences WHERE SeTxID = $textId");
        Connection::query("DELETE FROM $texts WHERE TxID = $textId");
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
     * This test verifies the behavior through the public parseAndSave() API
     * and checks the final word_occurrences table for correct word recognition.
     */
    public function testParseAndSaveLastWordRecognizedWithoutPunctuation(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $texts = Globals::table('texts');
        $sentences = Globals::table('sentences');
        $word_occurrences = Globals::table('word_occurrences');

        // Test text WITHOUT punctuation
        $sql = "INSERT INTO $texts (TxLgID, TxTitle, TxText, TxAudioURI)
                VALUES (" . self::$testLanguageId . ", 'Issue 114 Test NoPunct', 'Hello world', '')";
        Connection::query($sql);
        $textIdNoPunct = mysqli_insert_id(Globals::getDbConnection());

        TextParsing::parseAndSave("Hello world", self::$testLanguageId, $textIdNoPunct);

        // Query word_occurrences to check word recognition
        $resultNoPunct = Connection::fetchAll(
            "SELECT Ti2Text, Ti2WordCount FROM $word_occurrences
             WHERE Ti2TxID = $textIdNoPunct ORDER BY Ti2Order"
        );

        // Filter to only word items (Ti2WordCount > 0)
        $wordsNoPunct = array_filter($resultNoPunct, fn($r) => (int)$r['Ti2WordCount'] > 0);
        $wordsNoPunct = array_values($wordsNoPunct); // Re-index

        // Both "Hello" and "world" should be recognized as words
        $this->assertCount(2, $wordsNoPunct, 'Should have 2 words without punctuation');
        $this->assertEquals('Hello', $wordsNoPunct[0]['Ti2Text']);
        $this->assertEquals(1, (int)$wordsNoPunct[0]['Ti2WordCount'], 'First word should have WordCount=1');
        $this->assertEquals('world', $wordsNoPunct[1]['Ti2Text']);
        $this->assertEquals(1, (int)$wordsNoPunct[1]['Ti2WordCount'], 'Last word should have WordCount=1 even without trailing punctuation');

        // Test text WITH punctuation for comparison
        $sql = "INSERT INTO $texts (TxLgID, TxTitle, TxText, TxAudioURI)
                VALUES (" . self::$testLanguageId . ", 'Issue 114 Test WithPunct', 'Hello world.', '')";
        Connection::query($sql);
        $textIdWithPunct = mysqli_insert_id(Globals::getDbConnection());

        TextParsing::parseAndSave("Hello world.", self::$testLanguageId, $textIdWithPunct);

        $resultWithPunct = Connection::fetchAll(
            "SELECT Ti2Text, Ti2WordCount FROM $word_occurrences
             WHERE Ti2TxID = $textIdWithPunct ORDER BY Ti2Order"
        );

        // Filter to only word items
        $wordsWithPunct = array_filter($resultWithPunct, fn($r) => (int)$r['Ti2WordCount'] > 0);
        $wordsWithPunct = array_values($wordsWithPunct);

        // Both words should be recognized
        $this->assertCount(2, $wordsWithPunct, 'Should have 2 words with punctuation');
        $this->assertEquals('Hello', $wordsWithPunct[0]['Ti2Text']);
        $this->assertEquals(1, (int)$wordsWithPunct[0]['Ti2WordCount']);
        $this->assertEquals('world', $wordsWithPunct[1]['Ti2Text']);
        $this->assertEquals(1, (int)$wordsWithPunct[1]['Ti2WordCount']);

        // Check that punctuation is also stored (as non-word)
        $punctuation = array_filter($resultWithPunct, fn($r) => $r['Ti2Text'] === '.');
        $this->assertNotEmpty($punctuation, 'Punctuation should be stored');
        $punctItem = array_values($punctuation)[0];
        $this->assertEquals(0, (int)$punctItem['Ti2WordCount'], 'Punctuation should have WordCount=0');

        // Clean up
        Connection::query("DELETE FROM $word_occurrences WHERE Ti2TxID IN ($textIdNoPunct, $textIdWithPunct)");
        Connection::query("DELETE FROM $sentences WHERE SeTxID IN ($textIdNoPunct, $textIdWithPunct)");
        Connection::query("DELETE FROM $texts WHERE TxID IN ($textIdNoPunct, $textIdWithPunct)");
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
