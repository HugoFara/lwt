<?php declare(strict_types=1);

require_once __DIR__ . '/../../inc/kernel_utility.php';

use PHPUnit\Framework\TestCase;

final class KernelUtilityTest extends TestCase
{
    
    /**
     * Test the display of version as a string
     */
    public function testGetVersion(): void
    {
        $version = get_version();
        $this->assertIsString($version);
    }

    /**
     * Test the correct format of version as v{3-digit MAJOR}{3-digit MINOR}{3-digit PATCH}
     */
    public function testGetVersionNumber(): void 
    {
        $version = get_version_number();
        $this->assertTrue(str_starts_with($version, 'v'));
        $this->assertSame(10, strlen($version));
    }

    /**
     * Test if the language from dictionary feature is properly working.
     */
    public function testLangFromDict(): void
    {
        $urls = [
            'http://translate.google.com/lwt_term?ie=UTF-8&sl=ar&tl=en&text=&lwt_popup=true',
            'http://localhost/lwt/ggl.php/?sl=ar&tl=hr&text=',
            'http://localhost:5000/?lwt_translator=libretranslate&source=ar&target=en&q=lwt_term',
            'ggl.php?sl=ar&tl=en&text=###'
        ];
        foreach ($urls as $url) {
            $this->assertSame("ar", langFromDict($url));
        }
    }

    /**
     * Test HTML escaping with various inputs
     */
    public function testTohtml(): void
    {
        // Basic HTML escaping
        $this->assertEquals('&lt;script&gt;', tohtml('<script>'));
        $this->assertEquals('&lt;div&gt;Test&lt;/div&gt;', tohtml('<div>Test</div>'));

        // Special characters
        $this->assertEquals('&amp;', tohtml('&'));
        $this->assertEquals('&quot;', tohtml('"'));
        // Single quotes are not escaped by htmlspecialchars with ENT_COMPAT
        $this->assertEquals("'", tohtml("'"));

        // Empty and null values
        $this->assertEquals('', tohtml(null));
        $this->assertEquals('', tohtml(''));

        // Normal text should pass through
        $this->assertEquals('Hello World', tohtml('Hello World'));

        // UTF-8 characters should be preserved
        $this->assertEquals('日本語', tohtml('日本語'));
        $this->assertEquals('Ελληνικά', tohtml('Ελληνικά'));
    }

    /**
     * Test line ending normalization
     */
    public function testPrepareTextdata(): void
    {
        // Windows line endings to Unix
        $this->assertEquals("line1\nline2", prepare_textdata("line1\r\nline2"));
        $this->assertEquals("a\nb\nc", prepare_textdata("a\r\nb\r\nc"));

        // Unix line endings should remain unchanged
        $this->assertEquals("line1\nline2", prepare_textdata("line1\nline2"));

        // Mac line endings should remain unchanged
        $this->assertEquals("line1\rline2", prepare_textdata("line1\rline2"));

        // Empty string
        $this->assertEquals('', prepare_textdata(''));

        // No line endings
        $this->assertEquals('single line', prepare_textdata('single line'));
    }

    /**
     * Test space removal function
     */
    public function testRemoveSpaces(): void
    {
        // Remove spaces when requested
        $this->assertEquals('test', remove_spaces('t e s t', true));
        $this->assertEquals('hello', remove_spaces('h e l l o', true));
        $this->assertEquals('nospaceshere', remove_spaces('n o s p a c e s h e r e', true));

        // Don't remove spaces when not requested
        $this->assertEquals('t e s t', remove_spaces('t e s t', false));
        $this->assertEquals('hello world', remove_spaces('hello world', false));

        // Empty string handling
        $this->assertEquals('', remove_spaces('', true));
        $this->assertEquals('', remove_spaces('', false));

        // String with no spaces
        $this->assertEquals('test', remove_spaces('test', true));
        $this->assertEquals('test', remove_spaces('test', false));
    }

    /**
     * Test status name retrieval
     */
    public function testGetStatuses(): void
    {
        $statuses = get_statuses();

        // Test structure
        $this->assertIsArray($statuses);
        $this->assertCount(7, $statuses);

        // Test learning statuses (1-5)
        for ($i = 1; $i <= 4; $i++) {
            $this->assertArrayHasKey($i, $statuses);
            $this->assertEquals((string)$i, $statuses[$i]['abbr']);
            $this->assertEquals('Learning', $statuses[$i]['name']);
        }

        // Test status 5 (Learned)
        $this->assertArrayHasKey(5, $statuses);
        $this->assertEquals('5', $statuses[5]['abbr']);
        $this->assertEquals('Learned', $statuses[5]['name']);

        // Test status 99 (Well Known)
        $this->assertArrayHasKey(99, $statuses);
        $this->assertEquals('WKn', $statuses[99]['abbr']);
        $this->assertEquals('Well Known', $statuses[99]['name']);

        // Test status 98 (Ignored)
        $this->assertArrayHasKey(98, $statuses);
        $this->assertEquals('Ign', $statuses[98]['abbr']);
        $this->assertEquals('Ignored', $statuses[98]['name']);
    }

    /**
     * Test string replacement (first occurrence only)
     */
    public function testStrReplaceFirst(): void
    {
        // Basic replacement (only first occurrence should be replaced)
        $this->assertEquals('goodbye world hello', str_replace_first('hello', 'goodbye', 'hello world hello'));
        $this->assertEquals('xbc abc', str_replace_first('a', 'x', 'abc abc'));

        // No match
        $this->assertEquals('hello world', str_replace_first('goodbye', 'hi', 'hello world'));

        // Empty needle
        $this->assertEquals('test', str_replace_first('', 'x', 'test'));

        // Empty haystack
        $this->assertEquals('', str_replace_first('a', 'b', ''));

        // Needle at start
        $this->assertEquals('replaced test', str_replace_first('original', 'replaced', 'original test'));

        // Needle at end
        $this->assertEquals('test replaced', str_replace_first('original', 'replaced', 'test original'));
    }

    /**
     * Test annotation to JSON conversion
     */
    public function testAnnotationToJson(): void
    {
        // Empty annotation
        $this->assertEquals('{}', annotation_to_json(''));

        // Single annotation
        $annotation = "1\tword\t5\ttranslation";
        $result = annotation_to_json($annotation);
        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertArrayHasKey(0, $decoded);
        $this->assertEquals(['word', '5', 'translation'], $decoded[0]);

        // Multiple annotations
        $annotation = "1\tword1\t5\ttrans1\n2\tword2\t3\ttrans2";
        $result = annotation_to_json($annotation);
        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertCount(2, $decoded);
        $this->assertEquals(['word1', '5', 'trans1'], $decoded[0]);
        $this->assertEquals(['word2', '3', 'trans2'], $decoded[1]);
    }

    /**
     * Test URL base extraction
     */
    public function testUrlBase(): void
    {
        // Mock server variables for testing
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/lwt/index.php';

        $base = url_base();
        $this->assertStringStartsWith('http://', $base);
        $this->assertStringEndsWith('/', $base);
        $this->assertStringContainsString('localhost', $base);
    }

    /**
     * Test target language extraction from dictionary URL
     */
    public function testTargetLangFromDict(): void
    {
        // Google Translate URLs
        $this->assertEquals('en', targetLangFromDict('http://translate.google.com/?sl=ar&tl=en&text=test'));
        $this->assertEquals('fr', targetLangFromDict('http://localhost/ggl.php?sl=ar&tl=fr&text='));

        // LibreTranslate URLs
        $this->assertEquals('en', targetLangFromDict('http://localhost:5000/?lwt_translator=libretranslate&source=ar&target=en&q=test'));

        // Empty URL
        $this->assertEquals('', targetLangFromDict(''));
    }

    /**
     * Test get request helper
     */
    public function testGetreq(): void
    {
        // Set up test request
        $_REQUEST['test_key'] = '  test_value  ';
        $_REQUEST['empty'] = '';

        // Should trim values
        $this->assertEquals('test_value', getreq('test_key'));

        // Should return empty string for empty values
        $this->assertEquals('', getreq('empty'));

        // Should return empty string for non-existent keys
        $this->assertEquals('', getreq('nonexistent'));

        // Clean up
        unset($_REQUEST['test_key']);
        unset($_REQUEST['empty']);
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
        // Test 1: Real sentence end (period followed by capital letter with space in match[6])
        // Pattern typically captures: [1]=word, [2]=., [3]=space, [6]=space/empty, [7]=NextWord
        // When match[6] is empty and match[7] has alphanumeric after, it adds \t instead of \r
        $matches = ['End. Start', 'End', '.', '', '', '', '', 'Start'];
        $result = find_latin_sentence_end($matches, '');
        // This specific case adds \t based on the code logic (line 305-306)
        $this->assertStringContainsString("\t", $result, 'Period before capital may mark with tab');

        // Test 2: Abbreviation - single letter followed by period (Dr. Smith)
        // Single letter abbreviation should NOT end sentence
        $matches = ['A. Smith', 'A', '.', '', '', '', '', 'Smith'];
        $result = find_latin_sentence_end($matches, '');
        $this->assertStringEndsNotWith("\r", $result, 'Single letter abbreviation should not end sentence');

        // Test 3: Number with decimal point (3.14)
        $matches = ['3.14', '3', '.', '', '', '', '', '14'];
        $result = find_latin_sentence_end($matches, '');
        $this->assertStringEndsNotWith("\r", $result, 'Decimal number should not end sentence');

        // Test 4: Number with period at end (Year 2023.)
        // Small number (< 3 digits) with period should not end sentence
        $matches = ['10.', '10', '.', '', '', '', '', ''];
        $result = find_latin_sentence_end($matches, '');
        $this->assertStringEndsNotWith("\r", $result, 'Small number with period should not end sentence');

        // Test 5: Large number with period (Year 2023.) - should end sentence
        $matches = ['2023.', '2023', '.', '', '', '', '', ''];
        $result = find_latin_sentence_end($matches, '');
        $this->assertStringEndsWith("\r", $result, 'Large number (3+ digits) with period should end sentence');

        // Test 6: Period followed by lowercase (ellipsis or mid-sentence)
        $matches = ['test. then', 'test', '.', '', '', '', '', 'then'];
        $result = find_latin_sentence_end($matches, '');
        $this->assertStringEndsNotWith("\r", $result, 'Period before lowercase should not end sentence');

        // Test 7: Custom exception - "Dr." in exception list
        $matches = ['Dr. Smith', 'Dr', '.', '', '', '', '', 'Smith'];
        $result = find_latin_sentence_end($matches, 'Dr.|Mr.|Mrs.');
        $this->assertStringEndsNotWith("\r", $result, 'Exception list should prevent sentence end');

        // Test 8: Custom exception - "Mr." in exception list
        $matches = ['Mr. Jones', 'Mr', '.', '', '', '', '', 'Jones'];
        $result = find_latin_sentence_end($matches, 'Dr.|Mr.|Mrs.');
        $this->assertStringEndsNotWith("\r", $result, 'Mr. in exception list should not end sentence');

        // Test 9: Not in exception list - may end with \t or \r depending on match structure
        $matches = ['End. Start', 'End', '.', '', '', '', '', 'Start'];
        $result = find_latin_sentence_end($matches, 'Dr.|Mr.|Mrs.');
        // With empty match[6] and alphanumeric match[7], returns \t (line 305-306)
        $this->assertStringContainsString("\t", $result, 'Word not in exception list marks sentence (with tab)');

        // Test 10: Common abbreviation patterns - consonant clusters
        // Abbreviations like "St.", "Rd." (street, road) should not end sentence
        $matches = ['St. John', 'St', '.', true, '', '', '', 'John'];
        $result = find_latin_sentence_end($matches, '');
        $this->assertStringEndsNotWith("\r", $result, 'Consonant abbreviation should not end sentence');

        // Test 11: Single vowel abbreviation (e.g., "A.")
        $matches = ['A. Smith', 'A', '.', true, '', '', '', 'Smith'];
        $result = find_latin_sentence_end($matches, '');
        $this->assertStringEndsNotWith("\r", $result, 'Single vowel abbreviation should not end sentence');

        // Test 12: Colon followed by lowercase (list continuation)
        $matches = ['test: item', 'test', ':', '', '', '', '', 'item'];
        $result = find_latin_sentence_end($matches, '');
        $this->assertStringEndsNotWith("\r", $result, 'Colon before lowercase should not end sentence');

        // Test 13: Empty exception string (no exceptions)
        $matches = ['End. Start', 'End', '.', '', '', '', '', 'Start'];
        $result = find_latin_sentence_end($matches, '');
        // Still returns \t because match[6] is empty and match[7] has alphanumeric
        $this->assertStringContainsString("\t", $result, 'No exceptions marks with tab based on structure');

        // Test 14: Match at end of text (no following word)
        $matches = ['End.', 'End', '.', '', '', '', '', ''];
        $result = find_latin_sentence_end($matches, '');
        $this->assertStringEndsWith("\r", $result, 'Period at text end should mark sentence end');
    }

    /**
     * Test zero-width space handling in remove_spaces function
     *
     * Note: Current implementation only removes regular spaces (U+0020)
     * The comment in the code mentions zero-width space but doesn't actually remove it
     */
    public function testRemoveSpacesZeroWidth(): void
    {
        // Zero-width space (U+200B) - current implementation does NOT remove it
        $text_with_zwsp = "test\u{200B}word";

        // When remove is true, only regular spaces are removed (not zero-width)
        $result = remove_spaces($text_with_zwsp, true);
        $this->assertEquals($text_with_zwsp, $result, 'Current implementation does not remove zero-width spaces');

        // When remove is false, everything remains
        $result = remove_spaces($text_with_zwsp, false);
        $this->assertEquals($text_with_zwsp, $result, 'Should keep all characters when not removing');

        // Multiple regular spaces are removed, but zero-width spaces remain
        $complex = "a b\u{200B}c d";
        $result = remove_spaces($complex, true);
        $this->assertEquals("ab\u{200B}cd", $result, 'Should remove regular spaces but keep zero-width');
    }

}
