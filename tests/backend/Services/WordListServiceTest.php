<?php declare(strict_types=1);
namespace Lwt\Tests\Services;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Services\WordListService;
use Lwt\Services\WordService;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Services/ExportService.php';
require_once __DIR__ . '/../../../src/backend/Services/LanguageService.php';
require_once __DIR__ . '/../../../src/backend/Services/WordStatusService.php';
require_once __DIR__ . '/../../../src/backend/Services/ExpressionService.php';
require_once __DIR__ . '/../../../src/backend/Services/WordService.php';
require_once __DIR__ . '/../../../src/backend/Services/WordListService.php';

/**
 * Unit tests for the WordListService class.
 */
class WordListServiceTest extends TestCase
{
    private static bool $dbConnected = false;
    private static int $testLangId = 0;

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
            // Reset auto_increment
            $maxId = Connection::fetchValue("SELECT COALESCE(MAX(LgID), 0) AS value FROM " . Globals::getTablePrefix() . "languages");
            Connection::query("ALTER TABLE " . Globals::getTablePrefix() . "languages AUTO_INCREMENT = " . ((int)$maxId + 1));

            // Create a test language
            $existingLang = Connection::fetchValue(
                "SELECT LgID AS value FROM " . Globals::getTablePrefix() . "languages WHERE LgName = 'WordListTestLang' LIMIT 1"
            );

            if ($existingLang) {
                self::$testLangId = (int)$existingLang;
            } else {
                Connection::query(
                    "INSERT INTO " . Globals::getTablePrefix() . "languages (LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, " .
                    "LgTextSize, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, " .
                    "LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar, LgRightToLeft, LgShowRomanization) " .
                    "VALUES ('WordListTestLang', 'http://test.com/###', '', 'http://translate.test/###', " .
                    "100, '', '.!?', '', 'a-zA-Z', 0, 0, 0, 1)"
                );
                self::$testLangId = (int)Connection::fetchValue("SELECT LAST_INSERT_ID() AS value");
            }
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        // Clean up test words and language
        Connection::query("DELETE FROM " . Globals::getTablePrefix() . "words WHERE WoLgID = " . self::$testLangId);
        Connection::query("DELETE FROM " . Globals::getTablePrefix() . "languages WHERE LgName = 'WordListTestLang'");

        // Reset auto_increment
        $maxId = Connection::fetchValue("SELECT COALESCE(MAX(LgID), 0) AS value FROM " . Globals::getTablePrefix() . "languages");
        Connection::query("ALTER TABLE " . Globals::getTablePrefix() . "languages AUTO_INCREMENT = " . ((int)$maxId + 1));
    }

    protected function tearDown(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        // Clean up test words
        Connection::query("DELETE FROM " . Globals::getTablePrefix() . "words WHERE WoText LIKE 'list_test_%'");
    }

    // ===== Constructor and basic tests =====

    public function testServiceCanBeInstantiated(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordListService();

        $this->assertInstanceOf(WordListService::class, $service);
    }

    // ===== Filter condition tests =====

    public function testBuildLangConditionWithLanguageId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordListService();
        $result = $service->buildLangCondition('5');

        $this->assertStringContainsString('WoLgID=5', $result);
    }

    public function testBuildLangConditionWithEmptyLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordListService();
        $result = $service->buildLangCondition('');

        $this->assertEquals('', $result);
    }

    public function testBuildStatusConditionWithStatus(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordListService();
        $result = $service->buildStatusCondition('1');

        $this->assertNotEmpty($result);
        $this->assertStringContainsString('WoStatus', $result);
    }

    public function testBuildStatusConditionWithEmptyStatus(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordListService();
        $result = $service->buildStatusCondition('');

        $this->assertEquals('', $result);
    }

    public function testBuildQueryConditionWithTermMode(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordListService();
        $result = $service->buildQueryCondition('test', 'term', '');

        $this->assertStringContainsString('WoText', $result);
        $this->assertStringContainsString('like', $result);
    }

    public function testBuildQueryConditionWithRomanizationMode(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordListService();
        $result = $service->buildQueryCondition('test', 'rom', '');

        $this->assertStringContainsString('WoRomanization', $result);
    }

    public function testBuildQueryConditionWithTranslationMode(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordListService();
        $result = $service->buildQueryCondition('test', 'transl', '');

        $this->assertStringContainsString('WoTranslation', $result);
    }

    public function testBuildQueryConditionWithEmptyQuery(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordListService();
        $result = $service->buildQueryCondition('', 'term', '');

        $this->assertEquals('', $result);
    }

    public function testBuildTagConditionWithSingleTag(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordListService();
        $result = $service->buildTagCondition('1', '', '');

        $this->assertStringContainsString('having', $result);
        $this->assertStringContainsString('WtTgID', $result);
    }

    public function testBuildTagConditionWithBothTags(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordListService();
        $result = $service->buildTagCondition('1', '2', '1');

        $this->assertStringContainsString('having', $result);
        $this->assertStringContainsString('AND', $result);
    }

    public function testBuildTagConditionWithNoTags(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordListService();
        $result = $service->buildTagCondition('', '', '');

        $this->assertEquals('', $result);
    }

    public function testBuildTagConditionWithUntaggedFilter(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordListService();
        $result = $service->buildTagCondition('-1', '', '');

        $this->assertStringContainsString('IS NULL', $result);
    }

    // ===== Word operations tests =====

    public function testCountWordsReturnsZeroForNoMatches(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordListService();

        // Filter for a language with no words
        $count = $service->countWords('', ' and WoLgID=999999', '', '', '');

        $this->assertEquals(0, $count);
    }

    public function testCountWordsWithExistingWords(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $wordService = new WordService();
        $listService = new WordListService();

        // Create test words
        $wordService->create([
            'WoLgID' => self::$testLangId,
            'WoText' => 'list_test_count1',
            'WoStatus' => 1,
            'WoTranslation' => 'count test 1'
        ]);
        $wordService->create([
            'WoLgID' => self::$testLangId,
            'WoText' => 'list_test_count2',
            'WoStatus' => 1,
            'WoTranslation' => 'count test 2'
        ]);

        $count = $listService->countWords(
            '',
            ' and WoLgID=' . self::$testLangId,
            '',
            '',
            ''
        );

        $this->assertGreaterThanOrEqual(2, $count);
    }

    public function testDeleteSingleWord(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $wordService = new WordService();
        $listService = new WordListService();

        // Create a word to delete
        $result = $wordService->create([
            'WoLgID' => self::$testLangId,
            'WoText' => 'list_test_delete',
            'WoStatus' => 1,
            'WoTranslation' => 'delete test'
        ]);
        $wordId = $result['id'];

        // Delete it
        $result = $listService->deleteSingleWord($wordId);

        // Result is affected row count (should be 1) or may be a success indicator
        $this->assertNotEmpty($result);

        // Verify it's gone
        $word = $wordService->findById($wordId);
        $this->assertNull($word);
    }

    public function testDeleteByIdList(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $wordService = new WordService();
        $listService = new WordListService();

        // Create words to delete
        $ids = [];
        for ($i = 1; $i <= 3; $i++) {
            $result = $wordService->create([
                'WoLgID' => self::$testLangId,
                'WoText' => "list_test_dellist_$i",
                'WoStatus' => 1,
                'WoTranslation' => "delete list test $i"
            ]);
            $ids[] = $result['id'];
        }

        // Delete by list
        $idList = '(' . implode(',', $ids) . ')';
        $result = $listService->deleteByIdList($idList);

        // Result is affected row count (should be 3) or success indicator
        $this->assertNotEmpty($result);

        // Verify all are gone
        foreach ($ids as $id) {
            $this->assertNull($wordService->findById($id));
        }
    }

    public function testUpdateStatusByIdList(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $wordService = new WordService();
        $listService = new WordListService();

        // Create a word with status 1
        $result = $wordService->create([
            'WoLgID' => self::$testLangId,
            'WoText' => 'list_test_status',
            'WoStatus' => 1,
            'WoTranslation' => 'status test'
        ]);
        $wordId = $result['id'];

        // Update status to 5
        $message = $listService->updateStatusByIdList("($wordId)", 5, false, 's5');

        $this->assertNotEmpty($message);

        // Verify
        $word = $wordService->findById($wordId);
        $this->assertEquals('5', $word['WoStatus']);
    }

    public function testUpdateStatusByIdListRelativeIncrement(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $wordService = new WordService();
        $listService = new WordListService();

        // Create a word with status 2
        $result = $wordService->create([
            'WoLgID' => self::$testLangId,
            'WoText' => 'list_test_incr',
            'WoStatus' => 2,
            'WoTranslation' => 'increment test'
        ]);
        $wordId = $result['id'];

        // Increment status
        $message = $listService->updateStatusByIdList("($wordId)", 1, true, 'spl1');

        // Verify status is now 3
        $word = $wordService->findById($wordId);
        $this->assertEquals('3', $word['WoStatus']);
    }

    public function testUpdateStatusDateByIdList(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $wordService = new WordService();
        $listService = new WordListService();

        // Create a word
        $result = $wordService->create([
            'WoLgID' => self::$testLangId,
            'WoText' => 'list_test_date',
            'WoStatus' => 1,
            'WoTranslation' => 'date test'
        ]);
        $wordId = $result['id'];

        // Update date
        $message = $listService->updateStatusDateByIdList("($wordId)");

        $this->assertNotEmpty($message);
    }

    public function testDeleteSentencesByIdList(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $wordService = new WordService();
        $listService = new WordListService();

        // Create a word with sentence
        $result = $wordService->create([
            'WoLgID' => self::$testLangId,
            'WoText' => 'list_test_sent',
            'WoStatus' => 1,
            'WoTranslation' => 'sentence test',
            'WoSentence' => 'This is a {list_test_sent} sentence.'
        ]);
        $wordId = $result['id'];

        // Delete sentences
        $message = $listService->deleteSentencesByIdList("($wordId)");

        $this->assertNotEmpty($message);

        // Verify sentence is null
        $word = $wordService->findById($wordId);
        $this->assertNull($word['WoSentence']);
    }

    public function testToLowercaseByIdList(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $wordService = new WordService();
        $listService = new WordListService();

        // Create a word with mixed case
        $result = $wordService->create([
            'WoLgID' => self::$testLangId,
            'WoText' => 'List_Test_Case',
            'WoStatus' => 1,
            'WoTranslation' => 'case test'
        ]);
        $wordId = $result['id'];

        // Convert to lowercase
        $message = $listService->toLowercaseByIdList("($wordId)");

        $this->assertNotEmpty($message);

        // Verify text is now lowercase
        $word = $wordService->findById($wordId);
        $this->assertEquals('list_test_case', $word['WoText']);
    }

    public function testCapitalizeByIdList(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $wordService = new WordService();
        $listService = new WordListService();

        // Create a word in lowercase
        $result = $wordService->create([
            'WoLgID' => self::$testLangId,
            'WoText' => 'list_test_cap',
            'WoStatus' => 1,
            'WoTranslation' => 'capitalize test'
        ]);
        $wordId = $result['id'];

        // Capitalize
        $message = $listService->capitalizeByIdList("($wordId)");

        $this->assertNotEmpty($message);

        // Verify text is capitalized
        $word = $wordService->findById($wordId);
        $this->assertEquals('List_test_cap', $word['WoText']);
    }

    // ===== Regex validation tests =====

    public function testValidateRegexPatternWithValidPattern(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordListService();
        $result = $service->validateRegexPattern('^test');

        $this->assertTrue($result);
    }

    public function testValidateRegexPatternWithInvalidPattern(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordListService();
        $result = $service->validateRegexPattern('[invalid');

        $this->assertFalse($result);
    }

    // ===== Form data tests =====

    public function testGetNewTermFormData(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordListService();
        $result = $service->getNewTermFormData(self::$testLangId);

        $this->assertArrayHasKey('showRoman', $result);
        $this->assertArrayHasKey('scrdir', $result);
        $this->assertTrue($result['showRoman']); // Our test lang has LgShowRomanization=1
    }

    public function testGetEditFormDataWithExistingWord(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $wordService = new WordService();
        $listService = new WordListService();

        // Create a word
        $result = $wordService->create([
            'WoLgID' => self::$testLangId,
            'WoText' => 'list_test_edit',
            'WoStatus' => 2,
            'WoTranslation' => 'edit test'
        ]);
        $wordId = $result['id'];

        $formData = $listService->getEditFormData($wordId);

        $this->assertNotNull($formData);
        $this->assertEquals($wordId, $formData['WoID']);
        $this->assertEquals(self::$testLangId, $formData['WoLgID']);
        $this->assertEquals('list_test_edit', $formData['WoText']);
        $this->assertEquals('edit test', $formData['WoTranslation']);
        $this->assertEquals(2, $formData['WoStatus']);
    }

    public function testGetEditFormDataWithNonExistentWord(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordListService();
        $result = $service->getEditFormData(999999);

        $this->assertNull($result);
    }

    // ===== Export SQL tests =====

    public function testGetAnkiExportSqlWithIdList(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordListService();
        $sql = $service->getAnkiExportSql('(1,2,3)', '', '', '', '', '');

        $this->assertStringContainsString('WoID', $sql);
        $this->assertStringContainsString('WoTranslation', $sql);
        $this->assertStringContainsString('in (1,2,3)', $sql);
    }

    public function testGetTsvExportSqlWithIdList(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordListService();
        $sql = $service->getTsvExportSql('(1,2,3)', '', '', '', '', '');

        $this->assertStringContainsString('WoStatus', $sql);
        $this->assertStringContainsString('in (1,2,3)', $sql);
    }

    public function testGetFlexibleExportSqlWithIdList(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordListService();
        $sql = $service->getFlexibleExportSql('(1,2,3)', '', '', '', '', '');

        $this->assertStringContainsString('LgExportTemplate', $sql);
        $this->assertStringContainsString('in (1,2,3)', $sql);
    }

    public function testGetTestWordIdsSql(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new WordListService();
        $sql = $service->getTestWordIdsSql('', '', '', '', '', '');

        $this->assertStringContainsString('WoID', $sql);
        $this->assertStringContainsString('words', $sql);
    }

    // ===== Get filtered word IDs =====

    public function testGetFilteredWordIds(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $wordService = new WordService();
        $listService = new WordListService();

        // Create test words
        $wordService->create([
            'WoLgID' => self::$testLangId,
            'WoText' => 'list_test_filter1',
            'WoStatus' => 1,
            'WoTranslation' => 'filter test 1'
        ]);
        $wordService->create([
            'WoLgID' => self::$testLangId,
            'WoText' => 'list_test_filter2',
            'WoStatus' => 1,
            'WoTranslation' => 'filter test 2'
        ]);

        $ids = $listService->getFilteredWordIds(
            '',
            ' and WoLgID=' . self::$testLangId,
            '',
            '',
            ''
        );

        $this->assertIsArray($ids);
        $this->assertGreaterThanOrEqual(2, count($ids));
    }

    // ===== Words list tests =====

    public function testGetWordsList(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $wordService = new WordService();
        $listService = new WordListService();

        // Create test word
        $wordService->create([
            'WoLgID' => self::$testLangId,
            'WoText' => 'list_test_listword',
            'WoStatus' => 1,
            'WoTranslation' => 'list word test'
        ]);

        $filters = [
            'whLang' => ' and WoLgID=' . self::$testLangId,
            'whStat' => '',
            'whQuery' => '',
            'whTag' => '',
            'textId' => ''
        ];

        $result = $listService->getWordsList($filters, 1, 1, 10);

        $this->assertNotFalse($result);

        $found = false;
        while ($record = mysqli_fetch_assoc($result)) {
            if ($record['WoText'] === 'list_test_listword') {
                $found = true;
                $this->assertEquals('list word test', $record['WoTranslation']);
            }
        }
        mysqli_free_result($result);

        $this->assertTrue($found);
    }

    // ===== Save/update tests =====

    public function testSaveNewWord(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $wordService = new WordService();
        $listService = new WordListService();

        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'list_test_save',
            'WoStatus' => 1,
            'WoTranslation' => 'save test',
            'WoSentence' => '',
            'WoRomanization' => ''
        ];

        $message = $listService->saveNewWord($data);

        $this->assertEquals('Saved', $message);

        // Verify word exists
        $word = $wordService->findByText('list_test_save', self::$testLangId);
        $this->assertNotNull($word);
    }

    public function testUpdateWord(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $wordService = new WordService();
        $listService = new WordListService();

        // Create word first
        $result = $wordService->create([
            'WoLgID' => self::$testLangId,
            'WoText' => 'list_test_update',
            'WoStatus' => 1,
            'WoTranslation' => 'original'
        ]);
        $wordId = $result['id'];

        // Update it
        $data = [
            'WoID' => $wordId,
            'WoText' => 'list_test_update',
            'WoStatus' => 3,
            'WoOldStatus' => 1,
            'WoTranslation' => 'updated',
            'WoSentence' => 'New sentence',
            'WoRomanization' => 'roman'
        ];

        $result = $listService->updateWord($data);

        // Result is affected row count (should be 1) or success indicator
        $this->assertNotEmpty($result);

        // Verify changes
        $word = $wordService->findById($wordId);
        $this->assertEquals('3', $word['WoStatus']);
        $this->assertEquals('updated', $word['WoTranslation']);
    }
}
