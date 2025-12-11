<?php declare(strict_types=1);

namespace Lwt\Tests\Core\Text;

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/EnvLoader.php';
require_once __DIR__ . '/../../../../src/backend/Services/TextParsingService.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use Lwt\Database\TextParsing;
use Lwt\Services\TextParsingService;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');
$config = EnvLoader::getDatabaseConfig();
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/db_bootstrap.php';

/**
 * Comprehensive tests for text parsing functions
 *
 * Tests the core text processing pipeline including:
 * - TextParsing::prepare() - entry point for text parsing
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
        // Connect to database
        $config = EnvLoader::getDatabaseConfig();
        $testDbname = "test_" . $config['dbname'];

        if (!Globals::getDbConnection()) {
            $connection = Configuration::connect(
                $config['server'], $config['userid'], $config['passwd'], $testDbname, $config['socket']
            );
            Globals::setDbConnection($connection);
        }

        self::$dbConnection = Globals::getDbConnection();

        // Create a test language for parsing tests
        self::createTestLanguage();
    }

    /**
     * Create test language with standard settings
     */
    private static function createTestLanguage(): void
    {
        // Insert test language
        $sql = "INSERT INTO " . Globals::getTablePrefix() . "languages (
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

        Connection::query($sql);
        self::$testLanguageId = mysqli_insert_id(self::$dbConnection);
    }

    /**
     * Clean up test language
     */
    public static function tearDownAfterClass(): void
    {
        if (self::$testLanguageId) {
            Connection::query("DELETE FROM " . Globals::getTablePrefix() . "languages WHERE LgID = " . self::$testLanguageId);
        }
    }

    /**
     * Helper method to call prepare_text_parsing with output buffering
     * This prevents HTML output from polluting the test output
     *
     * @psalm-param -2 $id
     *
     * @return null|string[]
     *
     * @psalm-return non-empty-list<string>|null
     */
    private function callPrepareTextParsing(string $text, int $id, int|string $lid): array|null
    {
        ob_start();
        $result = TextParsing::prepare($text, $id, $lid);
        ob_end_clean();
        return $result;
    }

    /**
     * Test prepare_text_parsing with basic text
     */
    public function testPrepareTextParsingBasic(): void
    {
        
        if (!Globals::getDbConnection()) {
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
        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Create language with character substitutions
        $sql = "INSERT INTO " . Globals::getTablePrefix() . "languages (
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

        Connection::query($sql);
        $germanLangId = mysqli_insert_id(Globals::getDbConnection());

        // Test text with German characters
        $text = "Größe Käse Tür";
        $result = $this->callPrepareTextParsing($text, -2, $germanLangId);

        $this->assertIsArray($result, 'Should return array');

        // Check that text was processed (substitutions should have been applied)
        // The exact result depends on parsing logic, but it should not fail
        $this->assertNotEmpty($result, 'Should parse German text');

        // Clean up
        Connection::query("DELETE FROM " . Globals::getTablePrefix() . "languages WHERE LgID = $germanLangId");
    }

    /**
     * Test brace replacement in prepare_text_parsing
     */
    public function testPrepareTextParsingBraceReplacement(): void
    {
        
        if (!Globals::getDbConnection()) {
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
        
        if (!Globals::getDbConnection()) {
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
        
        if (!Globals::getDbConnection()) {
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
        
        if (!Globals::getDbConnection()) {
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
        
        if (!Globals::getDbConnection()) {
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
        
        if (!Globals::getDbConnection()) {
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
        
        if (!Globals::getDbConnection()) {
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
        
        if (!Globals::getDbConnection()) {
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
        
        if (!Globals::getDbConnection()) {
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
        
        if (!Globals::getDbConnection()) {
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
        
        if (!Globals::getDbConnection()) {
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
        
        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Mode -1 (check) outputs HTML and returns null
        $text = "Test sentence.";

        // Capture the HTML output - call prepare_text_parsing directly to get output
        ob_start();
        $result = TextParsing::prepare($text, -1, self::$testLanguageId);
        $output = ob_get_clean();

        $this->assertNull($result, 'Check mode (-1) should return null');
        $this->assertStringContainsString('Test sentence', $output, 'Output should contain the text');
    }

    /**
     * Test prepare_text_parsing with very long text
     */
    public function testPrepareTextParsingLongText(): void
    {
        
        if (!Globals::getDbConnection()) {
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
        
        if (!Globals::getDbConnection()) {
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
        
        if (!Globals::getDbConnection()) {
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
        
        if (!Globals::getDbConnection()) {
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
        
        if (!Globals::getDbConnection()) {
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
        
        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $text = "Text without any sentence ending punctuation marks";
        $result = $this->callPrepareTextParsing($text, -2, self::$testLanguageId);

        $this->assertIsArray($result, 'Should handle text without punctuation');
        $this->assertNotEmpty($result, 'Should still return the text as one sentence');
    }

    /**
     * Test find_latin_sentence_end function - comprehensive tests
     *
     * This function analyzes regex matches to determine if punctuation marks
     * end of sentence based on context (abbreviations, numbers, case, etc.)
     *
     * Note: The function may return different markers (\r or \t) depending on context
     */
    public function testFindLatinSentenceEnd(): void
    {
        $service = new TextParsingService();

        // Test 1: Real sentence end (period followed by capital letter with space in match[6])
        // Pattern typically captures: [1]=word, [2]=., [3]=space, [6]=space/empty, [7]=NextWord
        // When match[6] is empty and match[7] has alphanumeric after, it adds \t instead of \r
        $matches = ['End. Start', 'End', '.', '', '', '', '', 'Start'];
        $result = $service->findLatinSentenceEnd($matches, '');
        // This specific case adds \t based on the code logic (line 305-306)
        $this->assertStringContainsString("\t", $result, 'Period before capital may mark with tab');

        // Test 2: Abbreviation - single letter followed by period (Dr. Smith)
        // Single letter abbreviation should NOT end sentence
        $matches = ['A. Smith', 'A', '.', '', '', '', '', 'Smith'];
        $result = $service->findLatinSentenceEnd($matches, '');
        $this->assertStringEndsNotWith("\r", $result, 'Single letter abbreviation should not end sentence');

        // Test 3: Number with decimal point (3.14)
        $matches = ['3.14', '3', '.', '', '', '', '', '14'];
        $result = $service->findLatinSentenceEnd($matches, '');
        $this->assertStringEndsNotWith("\r", $result, 'Decimal number should not end sentence');

        // Test 4: Number with period at end (Year 2023.)
        // Small number (< 3 digits) with period should not end sentence
        $matches = ['10.', '10', '.', '', '', '', '', ''];
        $result = $service->findLatinSentenceEnd($matches, '');
        $this->assertStringEndsNotWith("\r", $result, 'Small number with period should not end sentence');

        // Test 5: Large number with period (Year 2023.) - should end sentence
        $matches = ['2023.', '2023', '.', '', '', '', '', ''];
        $result = $service->findLatinSentenceEnd($matches, '');
        $this->assertStringEndsWith("\r", $result, 'Large number (3+ digits) with period should end sentence');

        // Test 6: Period followed by lowercase (ellipsis or mid-sentence)
        $matches = ['test. then', 'test', '.', '', '', '', '', 'then'];
        $result = $service->findLatinSentenceEnd($matches, '');
        $this->assertStringEndsNotWith("\r", $result, 'Period before lowercase should not end sentence');

        // Test 7: Custom exception - "Dr." in exception list
        $matches = ['Dr. Smith', 'Dr', '.', '', '', '', '', 'Smith'];
        $result = $service->findLatinSentenceEnd($matches, 'Dr.|Mr.|Mrs.');
        $this->assertStringEndsNotWith("\r", $result, 'Exception list should prevent sentence end');

        // Test 8: Custom exception - "Mr." in exception list
        $matches = ['Mr. Jones', 'Mr', '.', '', '', '', '', 'Jones'];
        $result = $service->findLatinSentenceEnd($matches, 'Dr.|Mr.|Mrs.');
        $this->assertStringEndsNotWith("\r", $result, 'Mr. in exception list should not end sentence');

        // Test 9: Not in exception list - may end with \t or \r depending on match structure
        $matches = ['End. Start', 'End', '.', '', '', '', '', 'Start'];
        $result = $service->findLatinSentenceEnd($matches, 'Dr.|Mr.|Mrs.');
        // With empty match[6] and alphanumeric match[7], returns \t (line 305-306)
        $this->assertStringContainsString("\t", $result, 'Word not in exception list marks sentence (with tab)');

        // Test 10: Common abbreviation patterns - consonant clusters
        // Abbreviations like "St.", "Rd." (street, road) should not end sentence
        $matches = ['St. John', 'St', '.', true, '', '', '', 'John'];
        $result = $service->findLatinSentenceEnd($matches, '');
        $this->assertStringEndsNotWith("\r", $result, 'Consonant abbreviation should not end sentence');

        // Test 11: Single vowel abbreviation (e.g., "A.")
        $matches = ['A. Smith', 'A', '.', true, '', '', '', 'Smith'];
        $result = $service->findLatinSentenceEnd($matches, '');
        $this->assertStringEndsNotWith("\r", $result, 'Single vowel abbreviation should not end sentence');

        // Test 12: Colon followed by lowercase (list continuation)
        $matches = ['test: item', 'test', ':', '', '', '', '', 'item'];
        $result = $service->findLatinSentenceEnd($matches, '');
        $this->assertStringEndsNotWith("\r", $result, 'Colon before lowercase should not end sentence');

        // Test 13: Empty exception string (no exceptions)
        $matches = ['End. Start', 'End', '.', '', '', '', '', 'Start'];
        $result = $service->findLatinSentenceEnd($matches, '');
        // Still returns \t because match[6] is empty and match[7] has alphanumeric
        $this->assertStringContainsString("\t", $result, 'No exceptions marks with tab based on structure');

        // Test 14: Match at end of text (no following word)
        $matches = ['End.', 'End', '.', '', '', '', '', ''];
        $result = $service->findLatinSentenceEnd($matches, '');
        $this->assertStringEndsWith("\r", $result, 'Period at text end should mark sentence end');
    }
}
