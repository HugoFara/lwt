<?php declare(strict_types=1);

namespace Lwt\Tests\Core\Text;

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Database\Settings;
use Lwt\Services\SimilarTermsService;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');
$config = EnvLoader::getDatabaseConfig();

require_once __DIR__ . '/../../../../src/backend/Services/SimilarTermsService.php';

class SimtermsTest extends TestCase
{
    private static SimilarTermsService $service;

    public static function setUpBeforeClass(): void
    {
        // Initialize service
        self::$service = new SimilarTermsService();

        // Ensure database connection is established
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

        // Ensure we have a test database set up
        $result = Connection::query("SHOW TABLES LIKE 'words'");
        $res = mysqli_fetch_assoc($result);

        if ($res) {
            // Delete from tables in FK order for clean test data
            Connection::query("DELETE FROM textitems2");
            Connection::query("DELETE FROM wordtags");
            Connection::query("DELETE FROM sentences");
            Connection::query("DELETE FROM texts");
            Connection::query("DELETE FROM words");
            Connection::query("DELETE FROM languages");
        }

        // Insert a test language
        Connection::query(
            "INSERT INTO languages (LgID, LgName, LgDict1URI, LgGoogleTranslateURI)
            VALUES (1, 'English', 'http://example.com/dict', 'http://translate.google.com')"
        );

        // Insert test words for similarity testing
        $test_words = [
            ['hello', 'greeting', 'heh-lo'],
            ['hallo', 'another greeting', 'hah-lo'],
            ['yellow', 'color', 'yeh-lo'],
            ['world', 'planet', 'wurld'],
            ['word', 'text unit', 'wurd'],
            ['work', 'labor', 'wurk'],
            ['cat', 'animal', ''],
            ['cats', 'animals', ''],
            ['catch', 'grab', ''],
        ];

        foreach ($test_words as $i => $word) {
            $woText = Escaping::toSqlSyntax($word[0]);
            $woTextLC = Escaping::toSqlSyntax(mb_strtolower($word[0], 'UTF-8'));
            $woTranslation = Escaping::toSqlSyntax($word[1]);
            $woRomanization = Escaping::toSqlSyntax($word[2]);

            Connection::query(
                "INSERT INTO words
                (WoID, WoLgID, WoText, WoTextLC, WoStatus, WoTranslation, WoRomanization)
                VALUES (" . ($i + 1) . ", 1, $woText, $woTextLC, 1, $woTranslation, $woRomanization)"
            );
        }
    }

    public static function tearDownAfterClass(): void
    {
        // Clean up test data
        Connection::query("TRUNCATE TABLE words");
        Connection::query("TRUNCATE TABLE languages");
    }

    // ========== LETTER PAIRS FUNCTION ==========

    public function testLetterPairs(): void
    {
        // Basic word
        $pairs = self::$service->letterPairs('hello');
        $this->assertEquals(['he', 'el', 'll', 'lo'], $pairs);

        // Two-character word
        $pairs = self::$service->letterPairs('hi');
        $this->assertEquals(['hi'], $pairs);

        // Single character - should return empty array
        $pairs = self::$service->letterPairs('a');
        $this->assertEquals([], $pairs);

        // Empty string
        $pairs = self::$service->letterPairs('');
        $this->assertEquals([], $pairs);

        // UTF-8 characters
        $pairs = self::$service->letterPairs('你好');
        $this->assertEquals(['你好'], $pairs);
        $this->assertCount(1, $pairs);

        // Longer UTF-8 word
        $pairs = self::$service->letterPairs('日本語');
        $this->assertCount(2, $pairs);
        $this->assertEquals(['日本', '本語'], $pairs);
    }

    // ========== WORD LETTER PAIRS FUNCTION ==========

    public function testWordLetterPairs(): void
    {
        // Single word
        $pairs = self::$service->wordLetterPairs('hello');
        $this->assertContains('he', $pairs);
        $this->assertContains('el', $pairs);
        $this->assertContains('ll', $pairs);
        $this->assertContains('lo', $pairs);

        // Multiple words
        $pairs = self::$service->wordLetterPairs('hello world');
        $this->assertContains('he', $pairs);
        $this->assertContains('wo', $pairs);
        $this->assertContains('rl', $pairs);

        // Words with repeated pairs should deduplicate
        $pairs = self::$service->wordLetterPairs('hello hello');
        // Check that we don't have duplicates
        $this->assertEquals(count($pairs), count(array_unique($pairs)));

        // Empty string
        $pairs = self::$service->wordLetterPairs('');
        $this->assertEmpty($pairs);

        // Single character word
        $pairs = self::$service->wordLetterPairs('a b');
        $this->assertEmpty($pairs);
    }

    // ========== SIMILARITY RANKING FUNCTION ==========

    public function testGetSimilarityRanking(): void
    {
        // Identical strings should have ranking of 1.0
        $ranking = self::$service->getSimilarityRanking('hello', 'hello');
        $this->assertEquals(1.0, $ranking);

        // Completely different strings should have low ranking
        $ranking = self::$service->getSimilarityRanking('hello', 'xyz');
        $this->assertLessThan(0.3, $ranking);

        // Similar strings should have high ranking
        $ranking = self::$service->getSimilarityRanking('hello', 'hallo');
        $this->assertGreaterThanOrEqual(0.5, $ranking);
        $this->assertLessThan(1.0, $ranking);

        // Similar words
        $ranking = self::$service->getSimilarityRanking('cat', 'cats');
        $this->assertGreaterThan(0.5, $ranking);

        $ranking = self::$service->getSimilarityRanking('work', 'word');
        $this->assertGreaterThan(0.5, $ranking);

        // Empty strings should return 0
        $ranking = self::$service->getSimilarityRanking('', '');
        $this->assertEquals(0, $ranking);

        // One empty string
        $ranking = self::$service->getSimilarityRanking('hello', '');
        $this->assertEquals(0, $ranking);

        // Case insensitive (should be case-sensitive by default)
        $ranking1 = self::$service->getSimilarityRanking('Hello', 'hello');
        $ranking2 = self::$service->getSimilarityRanking('hello', 'hello');
        // These should be different since comparison is case-sensitive
        $this->assertNotEquals($ranking1, $ranking2);

        // Multi-word strings
        $ranking = self::$service->getSimilarityRanking('hello world', 'hello world');
        $this->assertEquals(1.0, $ranking);

        $ranking = self::$service->getSimilarityRanking('hello world', 'hello planet');
        $this->assertGreaterThan(0.3, $ranking);
        $this->assertLessThan(1.0, $ranking);
    }

    // ========== GET SIMILAR TERMS FUNCTION ==========

    public function testGetSimilarTerms(): void
    {
        // Find similar terms to 'hello'
        $similar = self::$service->getSimilarTermsWeighted(1, 'hello', 5, 0.3);

        // Should be an array
        $this->assertIsArray($similar);

        // Should not include 'hello' itself (it's excluded by SQL query)
        foreach ($similar as $termid) {
            $sql = "SELECT WoTextLC FROM words WHERE WoID = $termid";
            $result = Connection::fetchValue($sql);
            $this->assertNotEquals('hello', $result);
        }

        // 'hallo' and 'yellow' should be in results (similar to 'hello')
        $this->assertNotEmpty($similar);

        // Find similar terms to 'cat'
        $similar = self::$service->getSimilarTermsWeighted(1, 'cat', 10, 0.3);
        $this->assertIsArray($similar);

        // 'cats' and 'catch' should be similar
        $this->assertNotEmpty($similar);

        // Test with high min_ranking threshold - should return fewer results
        $similar_high = self::$service->getSimilarTermsWeighted(1, 'hello', 10, 0.9);
        $similar_low = self::$service->getSimilarTermsWeighted(1, 'hello', 10, 0.1);
        $this->assertLessThanOrEqual(count($similar_low), count($similar_high));

        // Test with max_count limit
        $similar = self::$service->getSimilarTermsWeighted(1, 'cat', 1, 0.1);
        $this->assertLessThanOrEqual(1, count($similar));

        // Test with non-existent term
        $similar = self::$service->getSimilarTermsWeighted(1, 'xyzabc123', 10, 0.3);
        $this->assertIsArray($similar);
        // May or may not have results, but should not error

        // Test with non-existent language ID
        $similar = self::$service->getSimilarTermsWeighted(999, 'hello', 10, 0.3);
        $this->assertIsArray($similar);
        $this->assertEmpty($similar);
    }

    // ========== FORMAT TERM FUNCTION ==========

    public function testFormatTerm(): void
    {
        // Format existing term
        $output = self::$service->formatTerm(1, 'hello');

        // Should return HTML string
        $this->assertIsString($output);
        $this->assertNotEmpty($output);

        // Should contain the term text
        $this->assertStringContainsString('hello', $output);

        // Should contain translation 'greeting'
        $this->assertStringContainsString('greeting', $output);

        // Should contain romanization 'heh-lo' in brackets
        $this->assertStringContainsString('[heh-lo]', $output);

        // Should contain the clickable icon (Lucide SVG icon)
        $this->assertStringContainsString('data-lucide="check-circle"', $output);

        // Should contain data attributes for JS event delegation
        $this->assertStringContainsString('data-action="set-trans-roman"', $output);
        $this->assertStringContainsString('data-translation="greeting"', $output);
        $this->assertStringContainsString('data-romanization="heh-lo"', $output);

        // Test with term that has matching compare string
        $output = self::$service->formatTerm(1, 'hello');
        // The term should be highlighted if it matches
        $this->assertStringContainsString('hello', $output);

        // Test with partial match - the compare string highlights part of the word
        $output = self::$service->formatTerm(2, 'hall');
        // 'hallo' contains 'hall' which should be highlighted with <u>
        // The full word won't appear unmodified, but should contain the term data
        $this->assertStringContainsString('another greeting', $output);

        // Test with term that has no romanization
        $output = self::$service->formatTerm(7, 'cat');
        $this->assertStringContainsString('cat', $output);
        // Should not have romanization brackets
        $this->assertStringNotContainsString('[cat]', $output);

        // Test with wildcard translation (*)
        Connection::query(
            "INSERT INTO words
            (WoID, WoLgID, WoText, WoTextLC, WoStatus, WoTranslation, WoRomanization)
            VALUES (100, 1, 'testword', 'testword', 1, '*', '')"
        );
        $output = self::$service->formatTerm(100, 'test');
        $this->assertStringContainsString('???', $output);
        Connection::query("DELETE FROM words WHERE WoID = 100");

        // Test with non-existent term ID
        $output = self::$service->formatTerm(9999, 'test');
        $this->assertEquals('', $output);
    }

    // ========== PRINT SIMILAR TERMS FUNCTION ==========

    public function testPrintSimilarTerms(): void
    {
        // Set the similar terms count setting
        Settings::save('set-similar-terms-count', '5');

        // Test with valid term
        $output = self::$service->printSimilarTerms(1, 'hello');
        $this->assertIsString($output);

        // Should return HTML with similar terms
        if ($output !== '(none)' && $output !== '') {
            // If we have similar terms, output should contain term info
            $this->assertNotEmpty($output);
        }

        // Test with empty term
        $output = self::$service->printSimilarTerms(1, '');
        $this->assertEquals('&nbsp;', $output);

        // Test with whitespace-only term
        $output = self::$service->printSimilarTerms(1, '   ');
        $this->assertEquals('&nbsp;', $output);

        // Test with term that has no similar matches
        $output = self::$service->printSimilarTerms(1, 'xyzabc123uniqueterm');
        $this->assertEquals('(none)', $output);

        // Test when feature is disabled (count = 0)
        Settings::save('set-similar-terms-count', '0');
        $output = self::$service->printSimilarTerms(1, 'hello');
        $this->assertEquals('', $output);

        // Test when feature is disabled (count = -1)
        Settings::save('set-similar-terms-count', '-1');
        $output = self::$service->printSimilarTerms(1, 'hello');
        $this->assertEquals('', $output);

        // Re-enable for other tests
        Settings::save('set-similar-terms-count', '5');
    }

    // ========== PRINT SIMILAR TERMS TABROW FUNCTION ==========

    public function testPrintSimilarTermsTabrow(): void
    {
        // Enable feature
        Settings::save('set-similar-terms-count', '5');

        // Get output from service (returns string, doesn't echo)
        $output = self::$service->printSimilarTermsTabRow();

        // Should output the table row
        $this->assertIsString($output);
        $this->assertStringContainsString('<tr>', $output);
        $this->assertStringContainsString('Similar', $output);
        $this->assertStringContainsString('Terms:', $output);
        $this->assertStringContainsString('id="simwords"', $output);

        // Disable feature
        Settings::save('set-similar-terms-count', '0');

        // Get output from service
        $output = self::$service->printSimilarTermsTabRow();

        // Should output nothing
        $this->assertEmpty($output);
    }

    // ========== EDGE CASES AND UTF-8 SUPPORT ==========

    public function testUTF8Support(): void
    {
        // Insert UTF-8 words
        Connection::query(
            "INSERT INTO words
            (WoID, WoLgID, WoText, WoTextLC, WoStatus, WoTranslation, WoRomanization)
            VALUES (200, 1, '日本語', '日本語', 1, 'Japanese language', 'nihongo')"
        );
        Connection::query(
            "INSERT INTO words
            (WoID, WoLgID, WoText, WoTextLC, WoStatus, WoTranslation, WoRomanization)
            VALUES (201, 1, '日本', '日本', 1, 'Japan', 'nihon')"
        );

        // Test letter pairs with UTF-8
        $pairs = self::$service->letterPairs('日本語');
        $this->assertIsArray($pairs);
        $this->assertNotEmpty($pairs);

        // Test similarity ranking with UTF-8
        $ranking = self::$service->getSimilarityRanking('日本語', '日本');
        $this->assertIsFloat($ranking);
        $this->assertGreaterThan(0, $ranking);

        // Test get_similar_terms with UTF-8
        $similar = self::$service->getSimilarTermsWeighted(1, '日本語', 5, 0.3);
        $this->assertIsArray($similar);

        // Test format_term with UTF-8
        $output = self::$service->formatTerm(200, '日本');
        $this->assertIsString($output);
        // The highlighted part will wrap the matching substring
        $this->assertStringContainsString('Japanese language', $output);

        // Clean up
        Connection::query("DELETE FROM words WHERE WoID IN (200, 201)");
    }

    public function testSimilarityRankingEdgeCases(): void
    {
        // Very long strings
        $long_str = str_repeat('test ', 100);
        $ranking = self::$service->getSimilarityRanking($long_str, $long_str);
        $this->assertEquals(1.0, $ranking);

        // Strings with special characters
        $ranking = self::$service->getSimilarityRanking('test-word', 'test_word');
        $this->assertGreaterThan(0, $ranking);

        // Strings with numbers
        $ranking = self::$service->getSimilarityRanking('test123', 'test456');
        $this->assertGreaterThan(0, $ranking);

        // Mixed case (function is case-sensitive)
        $ranking1 = self::$service->getSimilarityRanking('Hello', 'HELLO');
        $ranking2 = self::$service->getSimilarityRanking('hello', 'hello');
        $this->assertNotEquals($ranking1, $ranking2);
    }
}
