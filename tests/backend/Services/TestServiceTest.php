<?php

declare(strict_types=1);

namespace Lwt\Tests\Services;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Services\TestService;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Services/ExportService.php';
require_once __DIR__ . '/../../../src/backend/Services/WordStatusService.php';
require_once __DIR__ . '/../../../src/backend/Services/TextStatisticsService.php';
require_once __DIR__ . '/../../../src/backend/Services/SentenceService.php';
require_once __DIR__ . '/../../../src/backend/Services/AnnotationService.php';
require_once __DIR__ . '/../../../src/backend/Services/SimilarTermsService.php';
require_once __DIR__ . '/../../../src/backend/Services/TextNavigationService.php';
require_once __DIR__ . '/../../../src/backend/Services/TextParsingService.php';
require_once __DIR__ . '/../../../src/backend/Services/ExpressionService.php';
require_once __DIR__ . '/../../../src/backend/Core/Database/Restore.php';
require_once __DIR__ . '/../../../src/backend/Services/TestService.php';

/**
 * Unit tests for the TestService class.
 *
 * Tests word testing/review operations through the service layer.
 */
class TestServiceTest extends TestCase
{
    private static bool $dbConnected = false;
    private static string $tbpref = '';
    private static int $testLangId = 0;
    private static int $testTextId = 0;
    private static array $testWordIds = [];
    private TestService $service;

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
        self::$tbpref = Globals::getTablePrefix();

        if (self::$dbConnected) {
            self::createTestData();
        }
    }

    /**
     * Create test data for tests.
     */
    private static function createTestData(): void
    {
        $tbpref = self::$tbpref;

        // Create test language
        $existingLang = Connection::fetchValue(
            "SELECT LgID AS value FROM {$tbpref}languages WHERE LgName = 'TestLanguage' LIMIT 1"
        );

        if ($existingLang) {
            self::$testLangId = (int)$existingLang;
        } else {
            Connection::query(
                "INSERT INTO {$tbpref}languages (LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, " .
                "LgTextSize, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, " .
                "LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar, LgRightToLeft, LgShowRomanization) " .
                "VALUES ('TestLanguage', 'http://test.com/###', 'http://test2.com/###', 'http://translate.test/###', " .
                "100, '', '.!?', '', 'a-zA-Z', 0, 0, 0, 1)"
            );
            self::$testLangId = (int)Connection::fetchValue(
                "SELECT LAST_INSERT_ID() AS value"
            );
        }

        // Create test text
        Connection::query(
            "INSERT INTO {$tbpref}texts (TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI) " .
            "VALUES (" . self::$testLangId . ", 'Test Text', 'This is a test.', '', '')"
        );
        self::$testTextId = (int)Connection::fetchValue(
            "SELECT LAST_INSERT_ID() AS value"
        );

        // Create test words
        for ($i = 1; $i <= 5; $i++) {
            Connection::query(
                "INSERT INTO {$tbpref}words (WoLgID, WoText, WoTextLC, WoStatus, WoTranslation, " .
                "WoStatusChanged, WoTodayScore, WoTomorrowScore) " .
                "VALUES (" . self::$testLangId . ", 'testword{$i}', 'testword{$i}', {$i}, 'translation{$i}', " .
                "NOW(), -1.0, -0.5)"
            );
            self::$testWordIds[] = (int)Connection::fetchValue(
                "SELECT LAST_INSERT_ID() AS value"
            );
        }

        // Create text items linking words to text
        foreach (self::$testWordIds as $index => $wordId) {
            Connection::query(
                "INSERT INTO {$tbpref}textitems2 (Ti2TxID, Ti2LgID, Ti2WoID, Ti2SeID, Ti2Order, " .
                "Ti2WordCount, Ti2Text) " .
                "VALUES (" . self::$testTextId . ", " . self::$testLangId . ", {$wordId}, 1, {$index}, " .
                "1, 'testword" . ($index + 1) . "')"
            );
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        $tbpref = self::$tbpref;

        // Clean up test data
        Connection::query("DELETE FROM {$tbpref}textitems2 WHERE Ti2TxID = " . self::$testTextId);
        Connection::query("DELETE FROM {$tbpref}words WHERE WoLgID = " . self::$testLangId);
        Connection::query("DELETE FROM {$tbpref}texts WHERE TxID = " . self::$testTextId);
    }

    protected function setUp(): void
    {
        $this->service = new TestService();
    }

    // ===== getTestIdentifier() tests =====

    public function testGetTestIdentifierWithLanguage(): void
    {
        $result = $this->service->getTestIdentifier(null, null, 1, null);

        $this->assertEquals(['lang', 1], $result);
    }

    public function testGetTestIdentifierWithText(): void
    {
        $result = $this->service->getTestIdentifier(null, null, null, 42);

        $this->assertEquals(['text', 42], $result);
    }

    public function testGetTestIdentifierWithWordsSelection(): void
    {
        $result = $this->service->getTestIdentifier(2, "1,2,3", null, null);

        $this->assertEquals(['words', [1, 2, 3]], $result);
    }

    public function testGetTestIdentifierWithTextsSelection(): void
    {
        $result = $this->service->getTestIdentifier(3, "10,20,30", null, null);

        $this->assertEquals(['texts', [10, 20, 30]], $result);
    }

    public function testGetTestIdentifierWithNoParams(): void
    {
        $result = $this->service->getTestIdentifier(null, null, null, null);

        $this->assertEquals(['', ''], $result);
    }

    // ===== getTestSql() tests =====

    public function testGetTestSqlWithLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $sql = $this->service->getTestSql('lang', self::$testLangId);

        $this->assertIsString($sql);
        $this->assertStringContainsString('words', $sql);
        $this->assertStringContainsString('WoLgID', $sql);
    }

    public function testGetTestSqlWithText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $sql = $this->service->getTestSql('text', self::$testTextId);

        $this->assertIsString($sql);
        $this->assertStringContainsString('words', $sql);
        $this->assertStringContainsString('textitems2', $sql);
    }

    // ===== validateTestSelection() tests =====

    public function testValidateTestSelectionSingleLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $sql = $this->service->getTestSql('lang', self::$testLangId);
        $result = $this->service->validateTestSelection($sql);

        $this->assertTrue($result['valid']);
        $this->assertEquals(1, $result['langCount']);
        $this->assertNull($result['error']);
    }

    // ===== getL2LanguageName() tests =====

    public function testGetL2LanguageNameFromLangId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $name = $this->service->getL2LanguageName(self::$testLangId, null);

        $this->assertEquals('TestLanguage', $name);
    }

    public function testGetL2LanguageNameFromTextId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $name = $this->service->getL2LanguageName(null, self::$testTextId);

        $this->assertEquals('TestLanguage', $name);
    }

    public function testGetL2LanguageNameDefault(): void
    {
        $name = $this->service->getL2LanguageName(null, null);

        $this->assertEquals('L2', $name);
    }

    // ===== getTestCounts() tests =====

    public function testGetTestCounts(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $sql = $this->service->getTestSql('lang', self::$testLangId);
        $counts = $this->service->getTestCounts($sql);

        $this->assertIsArray($counts);
        $this->assertArrayHasKey('due', $counts);
        $this->assertArrayHasKey('total', $counts);
        $this->assertGreaterThanOrEqual(0, $counts['due']);
        $this->assertGreaterThanOrEqual(0, $counts['total']);
    }

    // ===== getTomorrowTestCount() tests =====

    public function testGetTomorrowTestCount(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $sql = $this->service->getTestSql('lang', self::$testLangId);
        $count = $this->service->getTomorrowTestCount($sql);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    // ===== getNextWord() tests =====

    public function testGetNextWord(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $sql = $this->service->getTestSql('lang', self::$testLangId);
        $word = $this->service->getNextWord($sql);

        // May be null if no words are due
        if ($word !== null) {
            $this->assertArrayHasKey('WoID', $word);
            $this->assertArrayHasKey('WoText', $word);
            $this->assertArrayHasKey('WoTranslation', $word);
            $this->assertArrayHasKey('WoStatus', $word);
        }
    }

    // ===== getLanguageSettings() tests =====

    public function testGetLanguageSettings(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $settings = $this->service->getLanguageSettings(self::$testLangId);

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('name', $settings);
        $this->assertArrayHasKey('dict1Uri', $settings);
        $this->assertArrayHasKey('dict2Uri', $settings);
        $this->assertArrayHasKey('translateUri', $settings);
        $this->assertArrayHasKey('textSize', $settings);
        $this->assertArrayHasKey('removeSpaces', $settings);
        $this->assertArrayHasKey('rtl', $settings);

        $this->assertEquals('TestLanguage', $settings['name']);
        $this->assertIsBool($settings['removeSpaces']);
        $this->assertIsBool($settings['rtl']);
    }

    public function testGetLanguageSettingsInvalidId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $settings = $this->service->getLanguageSettings(999999);

        $this->assertIsArray($settings);
        $this->assertEmpty($settings);
    }

    // ===== getLanguageIdFromTestSql() tests =====

    public function testGetLanguageIdFromTestSql(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $sql = $this->service->getTestSql('lang', self::$testLangId);
        $langId = $this->service->getLanguageIdFromTestSql($sql);

        $this->assertEquals(self::$testLangId, $langId);
    }

    // ===== calculateNewStatus() tests =====

    public function testCalculateNewStatusIncrement(): void
    {
        $this->assertEquals(2, $this->service->calculateNewStatus(1, 1));
        $this->assertEquals(3, $this->service->calculateNewStatus(2, 1));
        $this->assertEquals(5, $this->service->calculateNewStatus(4, 1));
    }

    public function testCalculateNewStatusDecrement(): void
    {
        $this->assertEquals(1, $this->service->calculateNewStatus(2, -1));
        $this->assertEquals(4, $this->service->calculateNewStatus(5, -1));
    }

    public function testCalculateNewStatusClampMin(): void
    {
        $this->assertEquals(1, $this->service->calculateNewStatus(1, -1));
        $this->assertEquals(1, $this->service->calculateNewStatus(1, -5));
    }

    public function testCalculateNewStatusClampMax(): void
    {
        $this->assertEquals(5, $this->service->calculateNewStatus(5, 1));
        $this->assertEquals(5, $this->service->calculateNewStatus(5, 5));
    }

    // ===== calculateStatusChange() tests =====

    public function testCalculateStatusChangePositive(): void
    {
        $this->assertEquals(1, $this->service->calculateStatusChange(1, 3));
        $this->assertEquals(1, $this->service->calculateStatusChange(2, 5));
    }

    public function testCalculateStatusChangeNegative(): void
    {
        $this->assertEquals(-1, $this->service->calculateStatusChange(3, 1));
        $this->assertEquals(-1, $this->service->calculateStatusChange(5, 2));
    }

    public function testCalculateStatusChangeZero(): void
    {
        $this->assertEquals(0, $this->service->calculateStatusChange(3, 3));
        $this->assertEquals(0, $this->service->calculateStatusChange(1, 1));
    }

    // ===== clampTestType() tests =====

    public function testClampTestTypeValid(): void
    {
        $this->assertEquals(1, $this->service->clampTestType(1));
        $this->assertEquals(3, $this->service->clampTestType(3));
        $this->assertEquals(5, $this->service->clampTestType(5));
    }

    public function testClampTestTypeTooLow(): void
    {
        $this->assertEquals(1, $this->service->clampTestType(0));
        $this->assertEquals(1, $this->service->clampTestType(-5));
    }

    public function testClampTestTypeTooHigh(): void
    {
        $this->assertEquals(5, $this->service->clampTestType(6));
        $this->assertEquals(5, $this->service->clampTestType(100));
    }

    // ===== isWordMode() tests =====

    public function testIsWordModeFalse(): void
    {
        $this->assertFalse($this->service->isWordMode(1));
        $this->assertFalse($this->service->isWordMode(2));
        $this->assertFalse($this->service->isWordMode(3));
    }

    public function testIsWordModeTrue(): void
    {
        $this->assertTrue($this->service->isWordMode(4));
        $this->assertTrue($this->service->isWordMode(5));
    }

    // ===== getBaseTestType() tests =====

    public function testGetBaseTestTypeSentenceMode(): void
    {
        $this->assertEquals(1, $this->service->getBaseTestType(1));
        $this->assertEquals(2, $this->service->getBaseTestType(2));
        $this->assertEquals(3, $this->service->getBaseTestType(3));
    }

    public function testGetBaseTestTypeWordMode(): void
    {
        $this->assertEquals(1, $this->service->getBaseTestType(4));
        $this->assertEquals(2, $this->service->getBaseTestType(5));
    }

    // ===== getTableTestSettings() tests =====

    public function testGetTableTestSettings(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $settings = $this->service->getTableTestSettings();

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('edit', $settings);
        $this->assertArrayHasKey('status', $settings);
        $this->assertArrayHasKey('term', $settings);
        $this->assertArrayHasKey('trans', $settings);
        $this->assertArrayHasKey('rom', $settings);
        $this->assertArrayHasKey('sentence', $settings);
    }

    // ===== getWordText() tests =====

    public function testGetWordText(): void
    {
        if (!self::$dbConnected || empty(self::$testWordIds)) {
            $this->markTestSkipped('Database connection and test data required');
        }

        $text = $this->service->getWordText(self::$testWordIds[0]);

        $this->assertEquals('testword1', $text);
    }

    public function testGetWordTextInvalidId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = $this->service->getWordText(999999);

        $this->assertNull($text);
    }

    // ===== Session management tests =====

    public function testInitializeTestSession(): void
    {
        $this->service->initializeTestSession(10);

        $this->assertEquals(10, $_SESSION['testtotal']);
        $this->assertEquals(0, $_SESSION['testcorrect']);
        $this->assertEquals(0, $_SESSION['testwrong']);
        $this->assertIsInt($_SESSION['teststart']);
    }

    public function testGetTestSessionData(): void
    {
        $_SESSION['teststart'] = time();
        $_SESSION['testcorrect'] = 5;
        $_SESSION['testwrong'] = 2;
        $_SESSION['testtotal'] = 10;

        $data = $this->service->getTestSessionData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('start', $data);
        $this->assertArrayHasKey('correct', $data);
        $this->assertArrayHasKey('wrong', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertEquals(5, $data['correct']);
        $this->assertEquals(2, $data['wrong']);
        $this->assertEquals(10, $data['total']);
    }

    public function testUpdateSessionProgressCorrect(): void
    {
        $_SESSION['testtotal'] = 10;
        $_SESSION['testcorrect'] = 3;
        $_SESSION['testwrong'] = 2;

        $result = $this->service->updateSessionProgress(1);

        $this->assertEquals(4, $result['correct']);
        $this->assertEquals(2, $result['wrong']);
        $this->assertEquals(4, $result['remaining']);
    }

    public function testUpdateSessionProgressWrong(): void
    {
        $_SESSION['testtotal'] = 10;
        $_SESSION['testcorrect'] = 3;
        $_SESSION['testwrong'] = 2;

        $result = $this->service->updateSessionProgress(-1);

        $this->assertEquals(3, $result['correct']);
        $this->assertEquals(3, $result['wrong']);
        $this->assertEquals(4, $result['remaining']);
    }

    public function testUpdateSessionProgressNoRemaining(): void
    {
        $_SESSION['testtotal'] = 5;
        $_SESSION['testcorrect'] = 3;
        $_SESSION['testwrong'] = 2;

        $result = $this->service->updateSessionProgress(1);

        // No change when no tests remaining
        $this->assertEquals(3, $result['correct']);
        $this->assertEquals(2, $result['wrong']);
        $this->assertEquals(0, $result['remaining']);
    }

    // ===== getTestSolution() tests =====

    public function testGetTestSolutionType1SentenceMode(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $wordData = [
            'WoID' => 1,
            'WoTranslation' => 'test translation'
        ];

        $solution = $this->service->getTestSolution(1, $wordData, false, 'word');

        $this->assertStringContainsString('[', $solution);
        $this->assertStringContainsString('test translation', $solution);
    }

    public function testGetTestSolutionType1WordMode(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $wordData = [
            'WoID' => 1,
            'WoTranslation' => 'test translation'
        ];

        $solution = $this->service->getTestSolution(4, $wordData, true, 'word');

        // Word mode doesn't wrap in square brackets at the beginning, tags may add brackets
        $this->assertNotEquals('[', $solution[0] ?? '');
        $this->assertStringContainsString('test translation', $solution);
    }

    public function testGetTestSolutionType2ReturnsWordText(): void
    {
        $wordData = [
            'WoID' => 1,
            'WoTranslation' => 'translation'
        ];

        $solution = $this->service->getTestSolution(2, $wordData, false, 'theword');

        $this->assertEquals('theword', $solution);
    }

    // ===== getWaitingTime() tests =====

    public function testGetWaitingTime(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $time = $this->service->getWaitingTime();

        $this->assertIsInt($time);
        $this->assertGreaterThanOrEqual(0, $time);
    }

    public function testGetEditFrameWaitingTime(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $time = $this->service->getEditFrameWaitingTime();

        $this->assertIsInt($time);
        $this->assertGreaterThanOrEqual(0, $time);
    }

    // ===== getTestDataFromParams() tests =====

    public function testGetTestDataFromParamsWithLangId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTestDataFromParams(
            null,
            null,
            self::$testLangId,
            null
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('property', $result);
        $this->assertArrayHasKey('counts', $result);
        $this->assertStringContainsString('TestLanguage', $result['title']);
        $this->assertEquals("lang=" . self::$testLangId, $result['property']);
    }

    public function testGetTestDataFromParamsWithTextId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTestDataFromParams(
            null,
            null,
            null,
            self::$testTextId
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('property', $result);
        $this->assertEquals("text=" . self::$testTextId, $result['property']);
    }

    public function testGetTestDataFromParamsNoParams(): void
    {
        $result = $this->service->getTestDataFromParams(null, null, null, null);

        $this->assertNull($result);
    }

    // ===== Integration test =====

    public function testFullTestWorkflow(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // 1. Get test identifier
        $identifier = $this->service->getTestIdentifier(null, null, self::$testLangId, null);
        $this->assertEquals('lang', $identifier[0]);

        // 2. Get test SQL
        $testsql = $this->service->getTestSql($identifier[0], $identifier[1]);
        $this->assertIsString($testsql);

        // 3. Validate selection
        $validation = $this->service->validateTestSelection($testsql);
        $this->assertTrue($validation['valid']);

        // 4. Get language name
        $langName = $this->service->getL2LanguageName(self::$testLangId, null);
        $this->assertEquals('TestLanguage', $langName);

        // 5. Get language settings
        $langSettings = $this->service->getLanguageSettings(self::$testLangId);
        $this->assertNotEmpty($langSettings);

        // 6. Get test counts
        $counts = $this->service->getTestCounts($testsql);
        $this->assertArrayHasKey('due', $counts);
        $this->assertArrayHasKey('total', $counts);
    }
}
