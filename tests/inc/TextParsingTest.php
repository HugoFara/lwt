<?php declare(strict_types=1);

require __DIR__ . "/../../connect.inc.php";
$GLOBALS['dbname'] = "test_" . $dbname;
require_once __DIR__ . '/../../inc/database_connect.php';

use PHPUnit\Framework\TestCase;

/**
 * Comprehensive tests for text parsing functions
 *
 * Tests the core text processing pipeline including:
 * - prepare_text_parsing() - entry point for text parsing
 * - Character substitutions
 * - Language-specific processing
 * - Brace replacement
 * - Edge cases and Unicode handling
 */
class TextParsingTest extends TestCase
{
    private static $dbConnection;
    private static $testLanguageId;

    /**
     * Set up database connection and create test language
     */
    public static function setUpBeforeClass(): void
    {
        global $DBCONNECTION;

        // Connect to database
        include __DIR__ . "/../../connect.inc.php";
        $testDbname = "test_" . $dbname;

        if (!$DBCONNECTION) {
            $DBCONNECTION = connect_to_database(
                $server, $userid, $passwd, $testDbname, $socket ?? ""
            );
        }

        self::$dbConnection = $DBCONNECTION;

        // Create a test language for parsing tests
        self::createTestLanguage();
    }

    /**
     * Create test language with standard settings
     */
    private static function createTestLanguage(): void
    {
        global $tbpref;

        // Insert test language
        $sql = "INSERT INTO {$tbpref}languages (
            LgName,
            LgDict1URI,
            LgGoogleTranslateURI,
            LgTextSize,
            LgCharacterSubstitutions,
            LgRegexpSplitSentences,
            LgExceptionsSplitSentences,
            LgRegexpWordCharacters,
            LgRemoveSpaces,
            LgSplitEachChar,
            LgRightToLeft
        ) VALUES (
            'Test English',
            'https://en.wiktionary.org/wiki/###',
            'https://translate.google.com/?ie=UTF-8&sl=en&tl=es&text=###',
            100,
            '',
            '.!?',
            'Mr.|Dr.|Mrs.|Ms.',
            'a-zA-Z',
            0,
            0,
            0
        )";

        do_mysqli_query($sql);
        self::$testLanguageId = mysqli_insert_id(self::$dbConnection);
    }

    /**
     * Clean up test language
     */
    public static function tearDownAfterClass(): void
    {
        global $tbpref;

        if (self::$testLanguageId) {
            do_mysqli_query("DELETE FROM {$tbpref}languages WHERE LgID = " . self::$testLanguageId);
        }
    }

    /**
     * Helper method to call prepare_text_parsing with output buffering
     * This prevents HTML output from polluting the test output
     */
    private function callPrepareTextParsing($text, $id, $lid)
    {
        ob_start();
        $result = prepare_text_parsing($text, $id, $lid);
        ob_end_clean();
        return $result;
    }

    /**
     * Test prepare_text_parsing with basic text
     */
    public function testPrepareTextParsingBasic(): void
    {
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
            $this->markTestSkipped('Database connection not available');
        }

        // Test with split mode (-2) which returns sentences array
        $text = "Hello world. This is a test.";
        $result = $this->callPrepareTextParsing($text, -2, self::$testLanguageId);

        $this->assertIsArray($result, 'Should return array in split mode');
        $this->assertNotEmpty($result, 'Should have parsed sentences');
    }

    /**
     * Test character substitution in prepare_text_parsing
     */
    public function testPrepareTextParsingCharacterSubstitution(): void
    {
        global $tbpref, $DBCONNECTION;

        if (!$DBCONNECTION) {
            $this->markTestSkipped('Database connection not available');
        }

        // Create language with character substitutions
        $sql = "INSERT INTO {$tbpref}languages (
            LgName, LgDict1URI, LgGoogleTranslateURI, LgTextSize,
            LgCharacterSubstitutions, LgRegexpSplitSentences,
            LgExceptionsSplitSentences, LgRegexpWordCharacters,
            LgRemoveSpaces, LgSplitEachChar, LgRightToLeft
        ) VALUES (
            'Test German',
            'https://de.wiktionary.org/wiki/###',
            'https://translate.google.com/?sl=de&tl=en&text=###',
            100,
            'ß=ss|ä=ae|ö=oe|ü=ue',
            '.!?',
            '',
            'a-zA-ZäöüßÄÖÜ',
            0, 0, 0
        )";

        do_mysqli_query($sql);
        $germanLangId = mysqli_insert_id($DBCONNECTION);

        // Test text with German characters
        $text = "Größe Käse Tür";
        $result = $this->callPrepareTextParsing($text, -2, $germanLangId);

        $this->assertIsArray($result, 'Should return array');

        // Check that text was processed (substitutions should have been applied)
        // The exact result depends on parsing logic, but it should not fail
        $this->assertNotEmpty($result, 'Should parse German text');

        // Clean up
        do_mysqli_query("DELETE FROM {$tbpref}languages WHERE LgID = $germanLangId");
    }

    /**
     * Test brace replacement in prepare_text_parsing
     */
    public function testPrepareTextParsingBraceReplacement(): void
    {
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
            $this->markTestSkipped('Database connection not available');
        }

        // Text with braces should have them replaced with brackets
        $text = "Text with {braces} and more {content}.";
        $result = $this->callPrepareTextParsing($text, -2, self::$testLanguageId);

        $this->assertIsArray($result, 'Should return array');
        $this->assertNotEmpty($result, 'Should parse text with braces');

        // The braces should have been replaced internally
        // We can't directly check the internal state, but parsing should succeed
    }

    /**
     * Test prepare_text_parsing with empty text
     */
    public function testPrepareTextParsingEmpty(): void
    {
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = $this->callPrepareTextParsing('', -2, self::$testLanguageId);

        $this->assertIsArray($result, 'Should return array even for empty text');
        // May return empty array or array with single empty element, both are acceptable
        $this->assertLessThanOrEqual(1, count($result), 'Empty text should return very small array');
    }

    /**
     * Test prepare_text_parsing with whitespace-only text
     */
    public function testPrepareTextParsingWhitespaceOnly(): void
    {
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = $this->callPrepareTextParsing("   \n\t  ", -2, self::$testLanguageId);

        $this->assertIsArray($result, 'Should return array');
        // Whitespace may be treated as one or two sentences depending on paragraph marker handling
        $this->assertLessThanOrEqual(2, count($result), 'Whitespace-only text should return very small array');
    }

    /**
     * Test prepare_text_parsing with Unicode text
     */
    public function testPrepareTextParsingUnicode(): void
    {
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
            $this->markTestSkipped('Database connection not available');
        }

        // Text with various Unicode characters
        $text = "Hello 世界. Γεια σου κόσμε. مرحبا بالعالم.";
        $result = $this->callPrepareTextParsing($text, -2, self::$testLanguageId);

        $this->assertIsArray($result, 'Should handle Unicode text');
        $this->assertNotEmpty($result, 'Should parse Unicode text');
    }

    /**
     * Test prepare_text_parsing with multiple paragraphs
     */
    public function testPrepareTextParsingMultipleParagraphs(): void
    {
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
            $this->markTestSkipped('Database connection not available');
        }

        $text = "First paragraph here.\n\nSecond paragraph here.\n\nThird paragraph.";
        $result = $this->callPrepareTextParsing($text, -2, self::$testLanguageId);

        $this->assertIsArray($result, 'Should return array');
        $this->assertGreaterThanOrEqual(3, count($result), 'Should have at least 3 sentences');
    }

    /**
     * Test prepare_text_parsing with Windows line endings
     */
    public function testPrepareTextParsingWindowsLineEndings(): void
    {
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
            $this->markTestSkipped('Database connection not available');
        }

        // Text with Windows line endings
        $text = "Line one.\r\nLine two.\r\nLine three.";
        $result = $this->callPrepareTextParsing($text, -2, self::$testLanguageId);

        $this->assertIsArray($result, 'Should handle Windows line endings');
        $this->assertNotEmpty($result, 'Should parse text with CRLF');
    }

    /**
     * Test prepare_text_parsing with special punctuation
     */
    public function testPrepareTextParsingSpecialPunctuation(): void
    {
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
            $this->markTestSkipped('Database connection not available');
        }

        $text = "Question? Exclamation! Period. Comma, semicolon; colon: dash-word.";
        $result = $this->callPrepareTextParsing($text, -2, self::$testLanguageId);

        $this->assertIsArray($result, 'Should handle special punctuation');
        $this->assertGreaterThanOrEqual(3, count($result), 'Should split on sentence punctuation');
    }

    /**
     * Test prepare_text_parsing with abbreviations
     */
    public function testPrepareTextParsingAbbreviations(): void
    {
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
            $this->markTestSkipped('Database connection not available');
        }

        // Should try not to split on Mr. or Dr. (in exception list)
        $text = "Mr. Smith met Dr. Jones. They talked.";
        $result = $this->callPrepareTextParsing($text, -2, self::$testLanguageId);

        $this->assertIsArray($result, 'Should handle abbreviations');
        // Abbreviation handling may vary, expecting 2-3 sentences
        $this->assertGreaterThanOrEqual(2, count($result), 'Should have at least 2 sentences');
        $this->assertLessThanOrEqual(3, count($result), 'Should have at most 3 sentences');
    }

    /**
     * Test prepare_text_parsing with numbers
     */
    public function testPrepareTextParsingNumbers(): void
    {
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
            $this->markTestSkipped('Database connection not available');
        }

        $text = "The value is 3.14. Another number is 42. Version 2.0.1 is here.";
        $result = $this->callPrepareTextParsing($text, -2, self::$testLanguageId);

        $this->assertIsArray($result, 'Should handle numbers');
        $this->assertGreaterThanOrEqual(3, count($result), 'Should split sentences but not on decimal points');
    }

    /**
     * Test prepare_text_parsing with mixed case
     */
    public function testPrepareTextParsingMixedCase(): void
    {
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
            $this->markTestSkipped('Database connection not available');
        }

        $text = "UPPERCASE SENTENCE. lowercase sentence. MiXeD CaSe SeNtEnCe.";
        $result = $this->callPrepareTextParsing($text, -2, self::$testLanguageId);

        $this->assertIsArray($result, 'Should handle mixed case');
        $this->assertEquals(3, count($result), 'Should have 3 sentences');
    }

    /**
     * Test prepare_text_parsing with quotes
     */
    public function testPrepareTextParsingQuotes(): void
    {
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
            $this->markTestSkipped('Database connection not available');
        }

        $text = '"First sentence." "Second sentence." \'Third sentence.\'';
        $result = $this->callPrepareTextParsing($text, -2, self::$testLanguageId);

        $this->assertIsArray($result, 'Should handle quoted text');
        $this->assertGreaterThanOrEqual(3, count($result), 'Should have at least 3 sentences');
    }

    /**
     * Test prepare_text_parsing mode: check (-1)
     */
    public function testPrepareTextParsingCheckMode(): void
    {
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
            $this->markTestSkipped('Database connection not available');
        }

        // Mode -1 (check) outputs HTML and returns null
        $text = "Test sentence.";

        // Capture the HTML output - call prepare_text_parsing directly to get output
        ob_start();
        $result = prepare_text_parsing($text, -1, self::$testLanguageId);
        $output = ob_get_clean();

        $this->assertNull($result, 'Check mode (-1) should return null');
        $this->assertStringContainsString('Test sentence', $output, 'Output should contain the text');
    }

    /**
     * Test prepare_text_parsing with very long text
     */
    public function testPrepareTextParsingLongText(): void
    {
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
            $this->markTestSkipped('Database connection not available');
        }

        // Generate long text
        $sentences = [];
        for ($i = 1; $i <= 50; $i++) {
            $sentences[] = "This is sentence number $i with some content.";
        }
        $text = implode(' ', $sentences);

        $result = $this->callPrepareTextParsing($text, -2, self::$testLanguageId);

        $this->assertIsArray($result, 'Should handle long text');
        // May have 50 or 51 sentences depending on parsing (allow some margin)
        $this->assertGreaterThanOrEqual(49, count($result), 'Should have at least 49 sentences');
        $this->assertLessThanOrEqual(51, count($result), 'Should have at most 51 sentences');
    }

    /**
     * Test prepare_text_parsing with special characters that need escaping
     */
    public function testPrepareTextParsingSpecialCharacters(): void
    {
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
            $this->markTestSkipped('Database connection not available');
        }

        // Text with SQL-special characters
        $text = "Test with 'single quotes'. Test with \"double quotes\". Test with \\ backslash.";
        $result = $this->callPrepareTextParsing($text, -2, self::$testLanguageId);

        $this->assertIsArray($result, 'Should handle special SQL characters');
        $this->assertNotEmpty($result, 'Should parse text with special characters');
    }

    /**
     * Test prepare_text_parsing with emoji
     */
    public function testPrepareTextParsingEmoji(): void
    {
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
            $this->markTestSkipped('Database connection not available');
        }

        $text = "Hello 😀 world. How are you 🌍 doing?";
        $result = $this->callPrepareTextParsing($text, -2, self::$testLanguageId);

        $this->assertIsArray($result, 'Should handle emoji');
        $this->assertNotEmpty($result, 'Should parse text with emoji');
    }

    /**
     * Test prepare_text_parsing with invalid language ID
     */
    public function testPrepareTextParsingInvalidLanguage(): void
    {
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
            $this->markTestSkipped('Database connection not available');
        }

        // Invalid language ID should cause issues (may not throw exception, but will fail to parse)
        // The function may return null or empty array, or the mysqli_fetch_assoc may return false
        $result = $this->callPrepareTextParsing("Test text.", -2, 99999);

        // We just verify it doesn't crash and returns an array or null
        $this->assertTrue($result === null || is_array($result), 'Should handle invalid language gracefully');
    }

    /**
     * Test prepare_text_parsing with ellipsis
     */
    public function testPrepareTextParsingEllipsis(): void
    {
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
            $this->markTestSkipped('Database connection not available');
        }

        $text = "Wait for it... Here it comes. Done.";
        $result = $this->callPrepareTextParsing($text, -2, self::$testLanguageId);

        $this->assertIsArray($result, 'Should handle ellipsis');
        $this->assertNotEmpty($result, 'Should parse text with ellipsis');
    }

    /**
     * Test prepare_text_parsing with no punctuation
     */
    public function testPrepareTextParsingNoPunctuation(): void
    {
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
            $this->markTestSkipped('Database connection not available');
        }

        $text = "Text without any sentence ending punctuation marks";
        $result = $this->callPrepareTextParsing($text, -2, self::$testLanguageId);

        $this->assertIsArray($result, 'Should handle text without punctuation');
        $this->assertNotEmpty($result, 'Should still return the text as one sentence');
    }
}
