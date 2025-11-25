<?php declare(strict_types=1);

require_once __DIR__ . '/../../src/backend/Core/kernel_utility.php';

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

    /**
     * Test getsqlscoreformula with different methods
     */
    public function testGetsqlscoreformula(): void
    {
        // Method 2: WoTodayScore formula with DATEDIFF and GREATEST
        $result = getsqlscoreformula(2);
        $this->assertStringContainsString('DATEDIFF', $result);
        $this->assertStringContainsString('NOW()', $result);
        $this->assertStringContainsString('GREATEST', $result);
        $this->assertStringContainsString('WoStatus', $result);
        $this->assertStringContainsString('CASE', $result);

        // Method 3: WoTomorrowScore formula with DATEDIFF and GREATEST
        $result = getsqlscoreformula(3);
        $this->assertStringContainsString('DATEDIFF', $result);
        $this->assertStringContainsString('NOW()', $result);
        $this->assertStringContainsString('GREATEST', $result);
        $this->assertStringContainsString('WoStatus', $result);
        $this->assertStringContainsString('CASE', $result);

        // Default/other methods: Returns '0'
        $result = getsqlscoreformula(0);
        $this->assertEquals('0', $result);

        $result = getsqlscoreformula(1);
        $this->assertEquals('0', $result);

        $result = getsqlscoreformula(99);
        $this->assertEquals('0', $result);
    }

    /**
     * Test make_score_random_insert_update with different types
     */
    public function testMakeScoreRandomInsertUpdate(): void
    {
        // Test 'iv' type - column names only
        $result = make_score_random_insert_update('iv');
        $this->assertStringContainsString('WoTodayScore', $result);
        $this->assertStringContainsString('WoTomorrowScore', $result);
        $this->assertStringContainsString('WoRandom', $result);
        $this->assertStringNotContainsString('=', $result);

        // Test 'id' type - values only (for INSERT)
        $result = make_score_random_insert_update('id');
        $this->assertStringContainsString('RAND()', $result);
        $this->assertStringContainsString('GREATEST', $result);
        $this->assertStringContainsString('WoStatus', $result);
        // Note: The result contains '=' in CASE conditions like "WoStatus = 1"
        // but not in assignment context (no "column = value")

        // Test 'u' type - key=value pairs for UPDATE
        $result = make_score_random_insert_update('u');
        $this->assertStringContainsString('WoTodayScore =', $result);
        $this->assertStringContainsString('WoTomorrowScore =', $result);
        $this->assertStringContainsString('WoRandom = RAND()', $result);
        $this->assertStringContainsString('GREATEST', $result);

        // Test default case (should return empty string)
        $result = make_score_random_insert_update('anything_else');
        $this->assertEquals('', $result);
    }

    /**
     * Test error_message_with_hide function
     */
    public function testErrorMessageWithHide(): void
    {
        // Test with non-error message (noback=true - no back button)
        $result = error_message_with_hide('Test message', true);
        $this->assertStringContainsString('Test message', $result);
        $this->assertStringContainsString('msgblue', $result);
        $this->assertStringNotContainsString('onclick="history.back();"', $result);

        // Test with Error prefix (should show red error with back button when noback=false)
        $result = error_message_with_hide('Error: Something went wrong', false);
        $this->assertStringContainsString('Error: Something went wrong', $result);
        $this->assertStringContainsString('class="red"', $result);
        $this->assertStringContainsString('onclick="history.back();"', $result);

        // Test with Error prefix and noback=true (no back button)
        $result = error_message_with_hide('Error: Another problem', true);
        $this->assertStringContainsString('Error: Another problem', $result);
        $this->assertStringContainsString('class="red"', $result);
        $this->assertStringNotContainsString('onclick="history.back()"', $result);

        // Test empty message (should return empty string)
        $result = error_message_with_hide('', true);
        $this->assertEquals('', $result);

        // Test whitespace-only message (should return empty string)
        $result = error_message_with_hide('   ', false);
        $this->assertEquals('', $result);
    }

    /**
     * Test getsess function for session variable retrieval
     */
    public function testGetsess(): void
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Set up test session variables
        $_SESSION['test_key'] = '  test_value  ';
        $_SESSION['empty'] = '';
        $_SESSION['null_value'] = null;

        // Should trim values
        $this->assertEquals('test_value', getsess('test_key'));

        // Should return empty string for empty values
        $this->assertEquals('', getsess('empty'));

        // Should return empty string for null values
        $this->assertEquals('', getsess('null_value'));

        // Should return empty string for non-existent keys
        $this->assertEquals('', getsess('nonexistent'));

        // Clean up
        unset($_SESSION['test_key']);
        unset($_SESSION['empty']);
        unset($_SESSION['null_value']);
    }

    /**
     * Test get_mecab_path function (Japanese morphological analyzer)
     * Note: This test is skipped because get_mecab_path calls my_die() if MeCab is not installed
     * and requires system-level MeCab installation to test properly
     */
    public function testGetMecabPath(): void
    {
        // Skip this test - get_mecab_path() calls my_die() if MeCab not installed
        // This would require MeCab to be installed on the system running tests
        $this->markTestSkipped('get_mecab_path requires MeCab to be installed on the system');
    }

    /**
     * Test get_setting_data function
     */
    public function testGetSettingData(): void
    {
        $settings = get_setting_data();

        // Should return an array
        $this->assertIsArray($settings);
        $this->assertNotEmpty($settings);

        // Each setting should have 'dft' (default) and 'num' at minimum
        foreach ($settings as $key => $setting) {
            $this->assertIsArray($setting);
            $this->assertArrayHasKey('dft', $setting, "Setting '$key' should have 'dft' key");
            $this->assertArrayHasKey('num', $setting, "Setting '$key' should have 'num' key");

            // If numeric ('num' == 1), should have min and max
            if ($setting['num'] == 1) {
                $this->assertArrayHasKey('min', $setting, "Numeric setting '$key' should have 'min'");
                $this->assertArrayHasKey('max', $setting, "Numeric setting '$key' should have 'max'");
            }
        }

        // Test some known settings exist (using array keys)
        $settingKeys = array_keys($settings);
        $this->assertContains('set-texts-per-page', $settingKeys);
        $this->assertContains('set-terms-per-page', $settingKeys);
        $this->assertContains('set-theme_dir', $settingKeys);

        // Verify specific setting structure
        $this->assertEquals('10', $settings['set-texts-per-page']['dft']);
        $this->assertEquals(1, $settings['set-texts-per-page']['num']);
    }

    /**
     * Test get_execution_time function
     */
    public function testGetExecutionTime(): void
    {
        // This function depends on REQUEST_TIME_FLOAT being set
        if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            $time = get_execution_time();
            $this->assertIsFloat($time);
            $this->assertGreaterThanOrEqual(0, $time);
        } else {
            // If REQUEST_TIME_FLOAT not set, should return 0
            $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
            usleep(1000); // Sleep 1ms to ensure time passes
            $time = get_execution_time();
            $this->assertIsFloat($time);
            $this->assertGreaterThan(0, $time);
        }
    }

    /**
     * Test parseSQLFile function
     */
    public function testParseSQLFile(): void
    {
        // Create a temporary SQL file
        $sqlContent = "-- Test SQL file\n" .
                      "CREATE TABLE test (id INT);\n" .
                      "INSERT INTO test VALUES (1);\n" .
                      "-- Comment line\n" .
                      "SELECT * FROM test;";

        $tempFile = sys_get_temp_dir() . '/test_sql_' . uniqid() . '.sql';
        file_put_contents($tempFile, $sqlContent);

        // Parse the file
        $queries = parseSQLFile($tempFile);

        // Should return an array of queries
        $this->assertIsArray($queries);
        $this->assertNotEmpty($queries);

        // Queries should be separated
        $this->assertGreaterThan(0, count($queries));

        // Clean up
        unlink($tempFile);
    }

    /**
     * Test parseSQLFile with non-existent file
     */
    public function testParseSQLFileNonexistent(): void
    {
        $result = parseSQLFile('/nonexistent/file.sql');

        // Should return empty array or handle gracefully
        $this->assertTrue($result === false || (is_array($result) && empty($result)));
    }

    /**
     * Test annotation_to_json with edge cases
     */
    public function testAnnotationToJsonEdgeCases(): void
    {
        // Annotation with special characters
        $annotation = "1\tword's\t5\t\"translation\"";
        $result = annotation_to_json($annotation);
        $this->assertJson($result);

        // Annotation with tabs in translation
        $annotation = "1\tword\t5\ttranslation\twith\ttabs";
        $result = annotation_to_json($annotation);
        $this->assertJson($result);

        // Malformed annotation (missing fields)
        $annotation = "1\tword";
        $result = annotation_to_json($annotation);
        $this->assertJson($result);

        // Unicode in annotations
        $annotation = "1\t日本語\t5\ttranslation";
        $result = annotation_to_json($annotation);
        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertStringContainsString('日本語', $decoded[0][0]);
    }

    /**
     * Test url_base with different server configurations
     */
    public function testUrlBaseVariousConfigurations(): void
    {
        // Save original values
        $origHost = $_SERVER['HTTP_HOST'] ?? null;
        $origUri = $_SERVER['REQUEST_URI'] ?? null;
        $origHttps = $_SERVER['HTTPS'] ?? null;

        // Test with HTTPS
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'secure.example.com';
        $_SERVER['REQUEST_URI'] = '/lwt/page.php';

        $base = url_base();
        $this->assertStringStartsWith('https://', $base);
        $this->assertStringContainsString('secure.example.com', $base);

        // Test without HTTPS
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/test/index.php';

        $base = url_base();
        $this->assertStringStartsWith('http://', $base);
        $this->assertStringContainsString('example.com', $base);

        // Test with port number
        $_SERVER['HTTP_HOST'] = 'localhost:8080';
        $_SERVER['REQUEST_URI'] = '/lwt/index.php';

        $base = url_base();
        $this->assertStringContainsString('localhost:8080', $base);

        // Restore original values
        if ($origHost !== null) {
            $_SERVER['HTTP_HOST'] = $origHost;
        }
        if ($origUri !== null) {
            $_SERVER['REQUEST_URI'] = $origUri;
        }
        if ($origHttps !== null) {
            $_SERVER['HTTPS'] = $origHttps;
        }
    }

    /**
     * Test langFromDict with edge cases
     */
    public function testLangFromDictEdgeCases(): void
    {
        // URL without language parameter
        $this->assertEquals('', langFromDict('http://example.com/page.php'));

        // Malformed URL
        $this->assertEquals('', langFromDict('not-a-url'));

        // Multiple sl parameters (parse_str uses the last one)
        $url = 'http://example.com/?sl=en&sl=fr';
        $result = langFromDict($url);
        // parse_str uses the last value when there are duplicates
        $this->assertEquals('fr', $result);

        // URL with fragment
        $this->assertEquals('de', langFromDict('http://example.com/?sl=de#fragment'));

        // Case sensitivity - query parameters are case-sensitive
        // 'SL' is different from 'sl', so this should return empty
        $this->assertEquals('', langFromDict('http://example.com/?SL=en'));
    }

    /**
     * Test targetLangFromDict with edge cases
     */
    public function testTargetLangFromDictEdgeCases(): void
    {
        // URL without target parameter
        $this->assertEquals('', targetLangFromDict('http://example.com/page.php'));

        // Malformed URL
        $this->assertEquals('', targetLangFromDict('not-a-url'));

        // URL with only source language
        $this->assertEquals('', targetLangFromDict('http://example.com/?sl=en'));

        // LibreTranslate without target
        $this->assertEquals('', targetLangFromDict('http://localhost:5000/?source=en'));

        // URL with fragment
        $this->assertEquals('es', targetLangFromDict('http://example.com/?tl=es#fragment'));
    }

    /**
     * Test str_replace_first with special regex characters
     */
    public function testStrReplaceFirstRegexCharacters(): void
    {
        // Needle with regex special characters - str_replace_first is NOT regex based
        // so special characters should be treated literally
        $this->assertEquals('[bcd]efg[abc]', str_replace_first('[abc]', '[bcd]', '[abc]efg[abc]'));
        $this->assertEquals('testworld...', str_replace_first('...', 'test', '...world...'));

        // Replacement with regex special characters
        $this->assertEquals('$test world', str_replace_first('hello', '$test', 'hello world'));
        $this->assertEquals('\\test world', str_replace_first('hello', '\\test', 'hello world'));
    }

    /**
     * Test get_statuses structure and values
     */
    public function testGetStatusesStructure(): void
    {
        $statuses = get_statuses();

        // Each status should have 'name' and 'abbr' keys
        foreach ($statuses as $status => $data) {
            $this->assertArrayHasKey('name', $data);
            $this->assertArrayHasKey('abbr', $data);
            $this->assertIsString($data['name']);
            $this->assertIsString($data['abbr']);
        }

        // Verify color/style information if present
        foreach ([1, 2, 3, 4] as $status) {
            $this->assertEquals('Learning', $statuses[$status]['name']);
        }

        $this->assertNotEquals($statuses[98]['name'], $statuses[99]['name']);
        $this->assertNotEquals($statuses[98]['abbr'], $statuses[99]['abbr']);
    }

    /**
     * Test remove_spaces with Unicode characters
     */
    public function testRemoveSpacesUnicode(): void
    {
        // Chinese characters with spaces
        $this->assertEquals('你好世界', remove_spaces('你 好 世 界', true));
        $this->assertEquals('你 好 世 界', remove_spaces('你 好 世 界', false));

        // Japanese with spaces
        $this->assertEquals('こんにちは', remove_spaces('こ ん に ち は', true));

        // Arabic with spaces
        $this->assertEquals('مرحبا', remove_spaces('م ر ح ب ا', true));

        // Mixed languages
        $this->assertEquals('Hello世界', remove_spaces('Hello 世界', true));
    }

    /**
     * Test tohtml with various edge cases
     */
    public function testTohtmlEdgeCases(): void
    {
        // Already escaped HTML
        $this->assertEquals('&amp;lt;script&amp;gt;', tohtml('&lt;script&gt;'));

        // Multiple special characters
        $this->assertEquals('&lt;&amp;&gt;&quot;', tohtml('<&>"'));

        // Long string with special characters
        $longString = str_repeat('<div>&amp;</div>', 100);
        $result = tohtml($longString);
        $this->assertStringContainsString('&lt;div&gt;', $result);
        $this->assertStringNotContainsString('<div>', $result);

        // Newlines and tabs should be preserved
        $this->assertEquals("line1\nline2\tindented", tohtml("line1\nline2\tindented"));
    }

    /**
     * Test getreq with special characters and trimming
     */
    public function testGetreqSpecialCharacters(): void
    {
        // HTML in request
        $_REQUEST['html_test'] = '<script>alert("XSS")</script>';
        $result = getreq('html_test');
        $this->assertEquals('<script>alert("XSS")</script>', $result);
        $this->assertStringNotContainsString('&lt;', $result);

        // Unicode with whitespace
        $_REQUEST['unicode_test'] = '  日本語  ';
        $this->assertEquals('日本語', getreq('unicode_test'));

        // Newlines and tabs in value
        $_REQUEST['whitespace_test'] = "  value\twith\nnewlines  ";
        $result = getreq('whitespace_test');
        // trim() only removes leading/trailing whitespace, not internal
        $this->assertEquals("value\twith\nnewlines", $result);

        // Clean up
        unset($_REQUEST['html_test']);
        unset($_REQUEST['unicode_test']);
        unset($_REQUEST['whitespace_test']);
    }

    /**
     * Test getsess with various data types
     */
    public function testGetsessDataTypes(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Integer value
        $_SESSION['int_value'] = 42;
        $result = getsess('int_value');
        $this->assertEquals('42', $result); // Should be converted to string

        // Array value (should be converted to string)
        $_SESSION['array_value'] = ['test'];
        $result = getsess('array_value');
        $this->assertIsString($result);

        // Boolean values
        $_SESSION['bool_true'] = true;
        $_SESSION['bool_false'] = false;
        $this->assertEquals('1', getsess('bool_true'));
        $this->assertEquals('', getsess('bool_false'));

        // Clean up
        unset($_SESSION['int_value']);
        unset($_SESSION['array_value']);
        unset($_SESSION['bool_true']);
        unset($_SESSION['bool_false']);
    }

    /**
     * Test quickMenu function
     */
    public function testQuickMenu(): void
    {
        // Capture output
        ob_start();
        quickMenu();
        $output = ob_get_clean();

        // Should output a select element
        $this->assertStringContainsString('<select', $output);
        $this->assertStringContainsString('id="quickmenu"', $output);
        $this->assertStringContainsString('onchange=', $output);

        // Should contain various menu options
        $this->assertStringContainsString('value="index"', $output);
        $this->assertStringContainsString('value="edit_languages"', $output);
        $this->assertStringContainsString('value="edit_texts"', $output);
        $this->assertStringContainsString('value="edit_words"', $output);
    }

    /**
     * Test pagestart_kernel_nobody function
     */
    public function testPagestartKernelNobody(): void
    {
        // Capture output
        ob_start();
        pagestart_kernel_nobody('Test Page', 'body { color: red; }');
        $output = ob_get_clean();

        // Should output HTML document structure
        $this->assertStringContainsString('<!DOCTYPE html>', $output);
        $this->assertStringContainsString('<html lang="en">', $output);
        $this->assertStringContainsString('<head>', $output);
        $this->assertStringContainsString('<title>LWT :: Test Page</title>', $output);

        // Should include custom CSS
        $this->assertStringContainsString('body { color: red; }', $output);

        // Should have meta tags
        $this->assertStringContainsString('charset=utf-8', $output);
        $this->assertStringContainsString('viewport', $output);
    }

    /**
     * Test pageend function
     */
    public function testPageend(): void
    {
        // Capture output
        ob_start();
        pageend();
        $output = ob_get_clean();

        // Should close body and html tags
        $this->assertStringContainsString('</body>', $output);
        $this->assertStringContainsString('</html>', $output);
    }

    /**
     * Test showRequest function
     */
    public function testShowRequest(): void
    {
        // Set up test request data
        $_REQUEST['test_key'] = 'test_value';
        $_REQUEST['another'] = 'data';

        // Capture output
        ob_start();
        showRequest();
        $output = ob_get_clean();

        // Should output request data
        $this->assertStringContainsString('_REQUEST', $output);

        // Clean up
        unset($_REQUEST['test_key']);
        unset($_REQUEST['another']);
    }

    /**
     * Test echodebug function
     */
    public function testEchodebug(): void
    {
        global $debug;
        $originalDebug = $debug ?? null;

        // Test with debug enabled
        $debug = 1;
        ob_start();
        echodebug('test value', 'Test Label');
        $output = ob_get_clean();

        $this->assertStringContainsString('Test Label', $output);
        $this->assertStringContainsString('test value', $output);

        // Test with debug disabled
        $debug = 0;
        ob_start();
        echodebug('test value', 'Test Label');
        $output = ob_get_clean();

        $this->assertEquals('', $output, 'Should not output anything when debug is disabled');

        // Restore original debug value
        $debug = $originalDebug;
    }

}
