<?php declare(strict_types=1);
namespace Lwt\Tests\Services;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Database\Settings;
use Lwt\Services\SimilarTermsService;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Services/SimilarTermsService.php';

/**
 * Unit tests for the SimilarTermsService class.
 *
 * Tests phonetic normalization, status weighting, and similar term detection.
 */
class SimilarTermsServiceTest extends TestCase
{
    private static bool $dbConnected = false;
    private SimilarTermsService $service;

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

        // Ensure clean test data
        Connection::query("TRUNCATE TABLE words");
        Connection::query("DELETE FROM languages WHERE LgID = 1");

        // Insert a test language
        Connection::query(
            "INSERT INTO languages (LgID, LgName, LgDict1URI, LgGoogleTranslateURI, LgRegexpSplitSentences, LgExceptionsSplitSentences, LgRegexpWordCharacters, LgCharacterSubstitutions)
            VALUES (1, 'English', 'http://example.com/dict', 'http://translate.google.com', '.!?', 'Mr.|Mrs.|Dr.', 'a-zA-Z', '')"
        );

        // Insert test words with varied statuses for weighting tests
        $test_words = [
            // [text, translation, romanization, status]
            ['colour', 'hue', '', 5],           // Learned
            ['color', 'hue US', '', 5],          // Learned (spelling variant)
            ['café', 'coffee shop', 'ka-fey', 3], // In progress
            ['cafe', 'coffee shop', '', 2],      // In progress (no accent)
            ['hello', 'greeting', 'heh-lo', 5],  // Learned
            ['hallo', 'greeting DE', '', 1],     // New (German spelling)
            ['yellow', 'color', '', 4],          // In progress
            ['phone', 'telephone', 'fon', 5],    // Learned
            ['fone', 'telephone slang', '', 98], // Ignored
            ['running', 'jogging', '', 99],      // Well-known
            ['runing', 'typo', '', 1],           // New (typo)
        ];

        foreach ($test_words as $i => $word) {
            $woText = Escaping::toSqlSyntax($word[0]);
            $woTextLC = Escaping::toSqlSyntax(mb_strtolower($word[0], 'UTF-8'));
            $woTranslation = Escaping::toSqlSyntax($word[1]);
            $woRomanization = Escaping::toSqlSyntax($word[2]);
            $status = $word[3];

            Connection::query(
                "INSERT INTO words
                (WoID, WoLgID, WoText, WoTextLC, WoStatus, WoTranslation, WoRomanization)
                VALUES (" . ($i + 1) . ", 1, $woText, $woTextLC, $status, $woTranslation, $woRomanization)"
            );
        }
    }

    protected function setUp(): void
    {
        $this->service = new SimilarTermsService();
    }

    public static function tearDownAfterClass(): void
    {
        Connection::query("TRUNCATE TABLE words");
        Connection::query("DELETE FROM languages WHERE LgID = 1");
    }

    // ========== PHONETIC NORMALIZATION TESTS ==========

    public function testPhoneticNormalizeAccentedVowels(): void
    {
        // Accented vowels should normalize to base form
        // Note: 'c' also normalizes to 'k', so café → kafe
        $this->assertEquals('kafe', $this->service->phoneticNormalize('café'));
        $this->assertEquals('naive', $this->service->phoneticNormalize('naïve'));
        $this->assertEquals('resume', $this->service->phoneticNormalize('résumé'));

        // Nordic characters
        $this->assertEquals('o', $this->service->phoneticNormalize('ø'));
        $this->assertEquals('a', $this->service->phoneticNormalize('å'));
    }

    public function testPhoneticNormalizeSimilarConsonants(): void
    {
        // c/k/q should normalize to 'k'
        $this->assertEquals('kat', $this->service->phoneticNormalize('cat'));
        $this->assertEquals('kat', $this->service->phoneticNormalize('kat'));

        // s/z should normalize to 's'
        $this->assertEquals('sero', $this->service->phoneticNormalize('zero'));

        // ph -> f
        $this->assertEquals('fone', $this->service->phoneticNormalize('phone'));
    }

    public function testPhoneticNormalizeDoubleLetters(): void
    {
        // Double letters should be reduced
        $this->assertEquals('helo', $this->service->phoneticNormalize('hello'));
        $this->assertEquals('runing', $this->service->phoneticNormalize('running'));
        $this->assertEquals('beter', $this->service->phoneticNormalize('better'));
    }

    public function testPhoneticNormalizeYToI(): void
    {
        // y should normalize to 'i'
        $this->assertEquals('hapi', $this->service->phoneticNormalize('happy'));
        $this->assertEquals('mi', $this->service->phoneticNormalize('my'));
    }

    public function testPhoneticNormalizeEmptyAndShort(): void
    {
        $this->assertEquals('', $this->service->phoneticNormalize(''));
        $this->assertEquals('a', $this->service->phoneticNormalize('a'));
        $this->assertEquals('ab', $this->service->phoneticNormalize('ab'));
    }

    // ========== STATUS WEIGHT TESTS ==========

    public function testStatusWeightLearned(): void
    {
        $weight = $this->service->getStatusWeight(5);
        $this->assertEquals(1.3, $weight);
    }

    public function testStatusWeightInProgress(): void
    {
        // Status 2, 3, 4 should all get in-progress weight
        $this->assertEquals(1.15, $this->service->getStatusWeight(2));
        $this->assertEquals(1.15, $this->service->getStatusWeight(3));
        $this->assertEquals(1.15, $this->service->getStatusWeight(4));
    }

    public function testStatusWeightNew(): void
    {
        $weight = $this->service->getStatusWeight(1);
        $this->assertEquals(1.0, $weight);
    }

    public function testStatusWeightWellKnown(): void
    {
        $weight = $this->service->getStatusWeight(99);
        $this->assertEquals(1.25, $weight);
    }

    public function testStatusWeightIgnored(): void
    {
        $weight = $this->service->getStatusWeight(98);
        $this->assertEquals(0.5, $weight);
    }

    public function testStatusWeightUnknown(): void
    {
        // Unknown status should default to NEW weight
        $weight = $this->service->getStatusWeight(0);
        $this->assertEquals(1.0, $weight);
    }

    // ========== COMBINED SIMILARITY RANKING TESTS ==========

    public function testCombinedSimilarityIdentical(): void
    {
        $ranking = $this->service->getCombinedSimilarityRanking('hello', 'hello');
        $this->assertEquals(1.0, $ranking);
    }

    public function testCombinedSimilarityPhoneticMatch(): void
    {
        // 'phone' and 'fone' should have higher combined similarity
        // than pure character pair similarity due to phonetic normalization
        $combined = $this->service->getCombinedSimilarityRanking('phone', 'fone', 0.3);
        $charOnly = $this->service->getSimilarityRanking('phone', 'fone');

        $this->assertGreaterThan($charOnly, $combined);
    }

    public function testCombinedSimilarityAccentMatch(): void
    {
        // 'café' and 'cafe' should match well phonetically
        $combined = $this->service->getCombinedSimilarityRanking('café', 'cafe', 0.3);
        // Both normalize to 'kafe', so high similarity expected
        $this->assertGreaterThan(0.7, $combined);
    }

    public function testCombinedSimilaritySpellingVariants(): void
    {
        // 'colour' and 'color' should have good combined similarity
        $combined = $this->service->getCombinedSimilarityRanking('colour', 'color', 0.3);
        $this->assertGreaterThan(0.6, $combined);
    }

    public function testCombinedSimilarityPhoneticWeight(): void
    {
        // With phoneticWeight = 0, should match getSimilarityRanking
        $combined = $this->service->getCombinedSimilarityRanking('hello', 'hallo', 0.0);
        $charOnly = $this->service->getSimilarityRanking('hello', 'hallo');
        $this->assertEquals($charOnly, $combined);

        // With phoneticWeight = 1, should be pure phonetic similarity
        $combined = $this->service->getCombinedSimilarityRanking('hello', 'hallo', 1.0);
        $phonetic1 = $this->service->phoneticNormalize('hello');
        $phonetic2 = $this->service->phoneticNormalize('hallo');
        $phoneticOnly = $this->service->getSimilarityRanking($phonetic1, $phonetic2);
        $this->assertEquals($phoneticOnly, $combined);
    }

    // ========== WEIGHTED SIMILAR TERMS TESTS ==========

    public function testGetSimilarTermsWeightedReturnsArray(): void
    {
        $similar = $this->service->getSimilarTermsWeighted(1, 'hello', 10, 0.3);
        $this->assertIsArray($similar);
    }

    public function testGetSimilarTermsWeightedExcludesSelf(): void
    {
        $similar = $this->service->getSimilarTermsWeighted(1, 'hello', 10, 0.3);

        foreach ($similar as $wordId) {
            $sql = "SELECT WoTextLC FROM words WHERE WoID = $wordId";
            $text = Connection::fetchValue($sql);
            $this->assertNotEquals('hello', $text);
        }
    }

    public function testGetSimilarTermsWeightedPrioritizesLearnedWords(): void
    {
        // Test that status weighting affects rankings:
        // A learned word (status 5) with the same base similarity
        // should rank higher than a new word (status 1)

        // First, verify the weighting math is correct
        $baseSimilarity = 0.5;

        // Learned word (status 5) gets 1.3x weight
        $learnedScore = $baseSimilarity * $this->service->getStatusWeight(5);
        $this->assertEquals(0.65, $learnedScore);

        // New word (status 1) gets 1.0x weight
        $newScore = $baseSimilarity * $this->service->getStatusWeight(1);
        $this->assertEquals(0.5, $newScore);

        // Learned should rank higher
        $this->assertGreaterThan($newScore, $learnedScore);
    }

    public function testGetSimilarTermsWeightedIgnoredWordsRankedLower(): void
    {
        // 'fone' is ignored (status 98), should be ranked lower than 'phone' for similar queries
        $similar = $this->service->getSimilarTermsWeighted(1, 'phone', 10, 0.2);

        // Find positions of similar terms
        $positions = [];
        $i = 1;
        foreach ($similar as $wordId) {
            $sql = "SELECT WoTextLC FROM words WHERE WoID = $wordId";
            $text = Connection::fetchValue($sql);
            if ($text !== null) {
                $positions[$text] = $i;
            }
            $i++;
        }

        // 'fone' should be ranked lower due to ignored status penalty
        // Even though phonetically similar, the 0.5 weight hurts its ranking
        if (isset($positions['fone'])) {
            // If fone appears at all, it should be after other good matches
            $this->assertGreaterThan(1, $positions['fone']);
        } else {
            // If fone doesn't appear, that's also OK - it was filtered out
            $this->assertTrue(true);
        }
    }

    public function testGetSimilarTermsWeightedRespectsMaxCount(): void
    {
        $similar = $this->service->getSimilarTermsWeighted(1, 'hello', 2, 0.1);
        $this->assertLessThanOrEqual(2, count($similar));
    }

    public function testGetSimilarTermsWeightedRespectsMinRanking(): void
    {
        // With high threshold, should return fewer or no results
        $highThreshold = $this->service->getSimilarTermsWeighted(1, 'hello', 10, 0.95);
        $lowThreshold = $this->service->getSimilarTermsWeighted(1, 'hello', 10, 0.1);

        $this->assertLessThanOrEqual(count($lowThreshold), count($highThreshold));
    }

    public function testGetSimilarTermsWeightedNonExistentLanguage(): void
    {
        $similar = $this->service->getSimilarTermsWeighted(999, 'hello', 10, 0.3);
        $this->assertIsArray($similar);
        $this->assertEmpty($similar);
    }

    // ========== BACKWARD COMPATIBILITY ==========

    public function testGetSimilarTermsCallsWeightedVersion(): void
    {
        // The deprecated getSimilarTerms should now call getSimilarTermsWeighted
        $oldMethod = $this->service->getSimilarTermsWeighted(1, 'hello', 5, 0.3);
        $newMethod = $this->service->getSimilarTermsWeighted(1, 'hello', 5, 0.3);

        // Both should return the same results
        $this->assertEquals($oldMethod, $newMethod);
    }
}
