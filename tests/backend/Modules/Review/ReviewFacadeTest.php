<?php declare(strict_types=1);
namespace Lwt\Tests\Modules\Review;

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\Bootstrap\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Shared\Infrastructure\Database\Configuration;
use Lwt\Modules\Review\Application\ReviewFacade;
use Lwt\Modules\Review\Domain\ReviewRepositoryInterface;
use Lwt\Modules\Review\Domain\ReviewConfiguration;
use Lwt\Modules\Review\Infrastructure\SessionStateManager;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/db_bootstrap.php';

/**
 * Unit tests for the ReviewFacade class.
 *
 * Tests review/test operations through the facade layer.
 */
class ReviewFacadeTest extends TestCase
{
    private static bool $dbConnected = false;
    private ReviewFacade $facade;

    public static function setUpBeforeClass(): void
    {
        $config = EnvLoader::getDatabaseConfig();
        $testDbname = "test_" . $config['dbname'];

        if (!Globals::getDbConnection()) {
            try {
                $connection = Configuration::connect(
                    $config['server'],
                    $config['userid'],
                    $config['passwd'],
                    $testDbname,
                    $config['socket'] ?? ''
                );
                Globals::setDbConnection($connection);
                self::$dbConnected = true;
            } catch (\Exception $e) {
                self::$dbConnected = false;
            }
        } else {
            self::$dbConnected = true;
        }
    }

    protected function setUp(): void
    {
        $this->facade = new ReviewFacade();
    }

    // ===== Constructor tests =====

    public function testConstructorCreatesValidFacade(): void
    {
        $facade = new ReviewFacade();
        $this->assertInstanceOf(ReviewFacade::class, $facade);
    }

    public function testConstructorAcceptsCustomRepository(): void
    {
        $mockRepo = $this->createMock(ReviewRepositoryInterface::class);
        $facade = new ReviewFacade($mockRepo);
        $this->assertInstanceOf(ReviewFacade::class, $facade);
    }

    public function testConstructorAcceptsCustomSessionManager(): void
    {
        $mockRepo = $this->createMock(ReviewRepositoryInterface::class);
        $mockSession = $this->createMock(SessionStateManager::class);
        $facade = new ReviewFacade($mockRepo, $mockSession);
        $this->assertInstanceOf(ReviewFacade::class, $facade);
    }

    // ===== ReviewConfiguration tests =====

    public function testReviewConfigurationFromLanguage(): void
    {
        $config = ReviewConfiguration::fromLanguage(1, 2, false);

        $this->assertEquals(ReviewConfiguration::KEY_LANG, $config->testKey);
        $this->assertEquals(1, $config->selection);
        $this->assertEquals(2, $config->testType);
        $this->assertFalse($config->isTableMode);
        $this->assertTrue($config->isValid());
    }

    public function testReviewConfigurationFromText(): void
    {
        $config = ReviewConfiguration::fromText(42, 3, true);

        $this->assertEquals(ReviewConfiguration::KEY_TEXT, $config->testKey);
        $this->assertEquals(42, $config->selection);
        $this->assertEquals(3, $config->testType);
        $this->assertTrue($config->wordMode);
    }

    public function testReviewConfigurationFromWords(): void
    {
        $wordIds = [1, 2, 3, 4, 5];
        $config = ReviewConfiguration::fromWords($wordIds, 1, false);

        $this->assertEquals(ReviewConfiguration::KEY_WORDS, $config->testKey);
        $this->assertEquals($wordIds, $config->selection);
        $this->assertEquals('1,2,3,4,5', $config->getSelectionString());
    }

    public function testReviewConfigurationFromTexts(): void
    {
        $textIds = [10, 20, 30];
        $config = ReviewConfiguration::fromTexts($textIds, 2);

        $this->assertEquals(ReviewConfiguration::KEY_TEXTS, $config->testKey);
        $this->assertEquals($textIds, $config->selection);
    }

    public function testReviewConfigurationForTableMode(): void
    {
        $config = ReviewConfiguration::forTableMode(ReviewConfiguration::KEY_LANG, 1);

        $this->assertTrue($config->isTableMode);
        $this->assertEquals(1, $config->testType);
    }

    public function testReviewConfigurationGetBaseType(): void
    {
        $config1 = new ReviewConfiguration(ReviewConfiguration::KEY_LANG, 1, 1);
        $config4 = new ReviewConfiguration(ReviewConfiguration::KEY_LANG, 1, 4);

        $this->assertEquals(1, $config1->getBaseType());
        $this->assertEquals(1, $config4->getBaseType()); // 4 -> 1 (word mode)
    }

    public function testReviewConfigurationToSqlProjectionForLang(): void
    {
        $config = ReviewConfiguration::fromLanguage(5);
        $sql = $config->toSqlProjection();

        $this->assertStringContainsString('words', $sql);
        $this->assertStringContainsString('WoLgID = 5', $sql);
    }

    public function testReviewConfigurationToSqlProjectionForText(): void
    {
        $config = ReviewConfiguration::fromText(10);
        $sql = $config->toSqlProjection();

        $this->assertStringContainsString('words', $sql);
        $this->assertStringContainsString('textitems2', $sql);
        $this->assertStringContainsString('Ti2TxID = 10', $sql);
    }

    public function testReviewConfigurationToSqlProjectionForWords(): void
    {
        $config = ReviewConfiguration::fromWords([1, 2, 3]);
        $sql = $config->toSqlProjection();

        $this->assertStringContainsString('WoID IN (1,2,3)', $sql);
    }

    public function testReviewConfigurationToUrlProperty(): void
    {
        $langConfig = ReviewConfiguration::fromLanguage(5);
        $this->assertEquals('lang=5', $langConfig->toUrlProperty());

        $textConfig = ReviewConfiguration::fromText(10);
        $this->assertEquals('text=10', $textConfig->toUrlProperty());

        $wordsConfig = ReviewConfiguration::fromWords([1, 2, 3]);
        $this->assertEquals('selection=2', $wordsConfig->toUrlProperty());

        $textsConfig = ReviewConfiguration::fromTexts([5, 6]);
        $this->assertEquals('selection=3', $textsConfig->toUrlProperty());
    }

    public function testReviewConfigurationIsValid(): void
    {
        $validConfig = ReviewConfiguration::fromLanguage(1);
        $this->assertTrue($validConfig->isValid());

        $invalidConfig = new ReviewConfiguration('', 0);
        $this->assertFalse($invalidConfig->isValid());
    }

    public function testReviewConfigurationClampsTestType(): void
    {
        // Test type 0 should clamp to 1
        $config0 = ReviewConfiguration::fromLanguage(1, 0);
        $this->assertEquals(1, $config0->testType);

        // Test type 10 should clamp to 5
        $config10 = ReviewConfiguration::fromLanguage(1, 10);
        $this->assertEquals(5, $config10->testType);
    }

    // ===== Utility method tests =====

    public function testClampTestType(): void
    {
        $this->assertEquals(1, $this->facade->clampTestType(0));
        $this->assertEquals(1, $this->facade->clampTestType(-5));
        $this->assertEquals(3, $this->facade->clampTestType(3));
        $this->assertEquals(5, $this->facade->clampTestType(5));
        $this->assertEquals(5, $this->facade->clampTestType(10));
    }

    public function testIsWordMode(): void
    {
        $this->assertFalse($this->facade->isWordMode(1));
        $this->assertFalse($this->facade->isWordMode(2));
        $this->assertFalse($this->facade->isWordMode(3));
        $this->assertTrue($this->facade->isWordMode(4));
        $this->assertTrue($this->facade->isWordMode(5));
    }

    public function testGetBaseTestType(): void
    {
        $this->assertEquals(1, $this->facade->getBaseTestType(1));
        $this->assertEquals(2, $this->facade->getBaseTestType(2));
        $this->assertEquals(3, $this->facade->getBaseTestType(3));
        $this->assertEquals(1, $this->facade->getBaseTestType(4)); // 4-3=1
        $this->assertEquals(2, $this->facade->getBaseTestType(5)); // 5-3=2
    }

    public function testCalculateNewStatus(): void
    {
        // Increase within range
        $this->assertEquals(3, $this->facade->calculateNewStatus(2, 1));
        $this->assertEquals(5, $this->facade->calculateNewStatus(4, 1));

        // Decrease within range
        $this->assertEquals(2, $this->facade->calculateNewStatus(3, -1));
        $this->assertEquals(1, $this->facade->calculateNewStatus(2, -1));

        // Clamp at min
        $this->assertEquals(1, $this->facade->calculateNewStatus(1, -1));
        $this->assertEquals(1, $this->facade->calculateNewStatus(1, -5));

        // Clamp at max
        $this->assertEquals(5, $this->facade->calculateNewStatus(5, 1));
        $this->assertEquals(5, $this->facade->calculateNewStatus(5, 10));
    }

    public function testCalculateStatusChange(): void
    {
        // Increase
        $this->assertEquals(1, $this->facade->calculateStatusChange(1, 2));
        $this->assertEquals(1, $this->facade->calculateStatusChange(1, 5));

        // Decrease
        $this->assertEquals(-1, $this->facade->calculateStatusChange(3, 2));
        $this->assertEquals(-1, $this->facade->calculateStatusChange(5, 1));

        // No change
        $this->assertEquals(0, $this->facade->calculateStatusChange(3, 3));
    }

    // ===== Test identifier tests =====

    public function testGetTestIdentifierWithLang(): void
    {
        $result = $this->facade->getReviewIdentifier(null, null, 1, null);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals(ReviewConfiguration::KEY_LANG, $result[0]);
        $this->assertEquals(1, $result[1]);
    }

    public function testGetTestIdentifierWithText(): void
    {
        $result = $this->facade->getReviewIdentifier(null, null, null, 42);

        $this->assertIsArray($result);
        $this->assertEquals(ReviewConfiguration::KEY_TEXT, $result[0]);
        $this->assertEquals(42, $result[1]);
    }

    public function testGetTestIdentifierWithNoParams(): void
    {
        $result = $this->facade->getReviewIdentifier(null, null, null, null);

        $this->assertIsArray($result);
        $this->assertEquals('', $result[0]);
        $this->assertEquals('', $result[1]);
    }

    // ===== Test SQL tests =====

    public function testGetTestSqlForLang(): void
    {
        $sql = $this->facade->getReviewSql(ReviewConfiguration::KEY_LANG, 1);

        $this->assertIsString($sql);
        $this->assertStringContainsString('words', $sql);
        $this->assertStringContainsString('WoLgID = 1', $sql);
    }

    public function testGetTestSqlForText(): void
    {
        $sql = $this->facade->getReviewSql(ReviewConfiguration::KEY_TEXT, 42);

        $this->assertIsString($sql);
        $this->assertStringContainsString('textitems2', $sql);
        $this->assertStringContainsString('Ti2TxID = 42', $sql);
    }

    public function testGetTestSqlForWords(): void
    {
        $sql = $this->facade->getReviewSql(ReviewConfiguration::KEY_WORDS, [1, 2, 3]);

        $this->assertIsString($sql);
        $this->assertStringContainsString('WoID IN', $sql);
    }

    // ===== Session tests =====

    public function testInitializeTestSession(): void
    {
        $this->facade->initializeTestSession(10);
        $data = $this->facade->getTestSessionData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('start', $data);
        $this->assertArrayHasKey('correct', $data);
        $this->assertArrayHasKey('wrong', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertEquals(10, $data['total']);
        $this->assertEquals(0, $data['correct']);
        $this->assertEquals(0, $data['wrong']);
    }

    public function testUpdateSessionProgress(): void
    {
        $this->facade->initializeTestSession(5);

        // Correct answer (positive change)
        $result1 = $this->facade->updateSessionProgress(1);
        $this->assertEquals(1, $result1['correct']);
        $this->assertEquals(0, $result1['wrong']);

        // Wrong answer (negative change)
        $result2 = $this->facade->updateSessionProgress(-1);
        $this->assertEquals(1, $result2['correct']);
        $this->assertEquals(1, $result2['wrong']);
    }

    public function testGetTestSessionDataReturnsExpectedStructure(): void
    {
        // Test that session data has the expected structure
        $data = $this->facade->getTestSessionData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('start', $data);
        $this->assertArrayHasKey('correct', $data);
        $this->assertArrayHasKey('wrong', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertIsInt($data['start']);
        $this->assertIsInt($data['correct']);
        $this->assertIsInt($data['wrong']);
        $this->assertIsInt($data['total']);
    }

    // ===== Settings tests =====

    public function testGetWaitingTime(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getWaitingTime();
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function testGetEditFrameWaitingTime(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getEditFrameWaitingTime();
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    // ===== Database-dependent tests =====

    public function testValidateTestSelectionWithEmptySql(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Test with invalid/empty SQL
        $result = $this->facade->validateReviewSelection(' words WHERE 1=0 ');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('langCount', $result);
    }

    public function testGetTestCountsWithValidConfig(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $sql = ' words WHERE WoLgID = 999999 ';
        $result = $this->facade->getReviewCounts($sql);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('due', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(0, $result['due']);
        $this->assertEquals(0, $result['total']);
    }

    public function testGetTomorrowTestCount(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $sql = ' words WHERE WoLgID = 999999 ';
        $result = $this->facade->getTomorrowTestCount($sql);

        $this->assertIsInt($result);
        $this->assertEquals(0, $result);
    }

    public function testGetNextWordReturnsNullForNoWords(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $sql = ' words WHERE WoLgID = 999999 ';
        $result = $this->facade->getNextWord($sql);

        $this->assertNull($result);
    }

    public function testGetLanguageIdFromTestSql(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $sql = ' words WHERE WoLgID = 999999 ';
        $result = $this->facade->getLanguageIdFromTestSql($sql);

        // Should return null for non-existent language
        $this->assertNull($result);
    }

    public function testGetTableTestSettings(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getTableReviewSettings();

        $this->assertIsArray($result);
        // Check for expected keys
        $this->assertArrayHasKey('edit', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('term', $result);
        $this->assertArrayHasKey('trans', $result);
    }

    public function testGetLanguageSettings(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Use a non-existent language ID
        $result = $this->facade->getLanguageSettings(999999);

        $this->assertIsArray($result);
    }

    public function testGetWordTextReturnsNullForNonexistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getWordText(999999);
        $this->assertNull($result);
    }

    public function testGetL2LanguageName(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // With non-existent language, should return default
        $result = $this->facade->getL2LanguageName(999999, null);
        $this->assertIsString($result);
    }

    public function testGetTestDataFromParamsWithInvalidParams(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getTestDataFromParams(null, null, null, null);
        $this->assertNull($result);
    }

    public function testBuildSelectionTestSql(): void
    {
        // Type 2 = words
        $result = $this->facade->buildSelectionReviewSql(2, '1,2,3');
        $this->assertIsString($result);
        $this->assertStringContainsString('WoID IN', $result);

        // Type 3 = texts
        $result = $this->facade->buildSelectionReviewSql(3, '10,20');
        $this->assertIsString($result);
        $this->assertStringContainsString('Ti2TxID IN', $result);

        // Type 1 = raw SQL (returns as-is)
        $rawSql = 'words WHERE WoLgID = 1';
        $result = $this->facade->buildSelectionReviewSql(1, $rawSql);
        $this->assertEquals($rawSql, $result);
    }

    // ===== Method existence tests =====

    public function testGetSentenceForWordMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'getSentenceForWord'),
            'getSentenceForWord method should exist'
        );
    }

    public function testUpdateWordStatusMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'updateWordStatus'),
            'updateWordStatus method should exist'
        );
    }

    public function testFetchNextTermMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'fetchNextTerm'),
            'fetchNextTerm method should exist'
        );
    }

    public function testFetchTableWordsMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'fetchTableWords'),
            'fetchTableWords method should exist'
        );
    }

    public function testFetchReviewConfigurationMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'fetchReviewConfiguration'),
            'fetchReviewConfiguration method should exist'
        );
    }

    public function testSubmitAnswerMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'submitAnswer'),
            'submitAnswer method should exist'
        );
    }

    public function testSubmitAnswerWithChangeMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'submitAnswerWithChange'),
            'submitAnswerWithChange method should exist'
        );
    }

    public function testGetTableTestWordsMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'getTableReviewWords'),
            'getTableReviewWords method should exist'
        );
    }

    public function testGetTestSolutionMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'getTestSolution'),
            'getTestSolution method should exist'
        );
    }
}
