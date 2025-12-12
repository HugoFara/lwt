<?php declare(strict_types=1);
namespace Lwt\Tests\Services;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Services\WordService;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Services/ExportService.php';
require_once __DIR__ . '/../../../src/backend/Services/WordStatusService.php';
require_once __DIR__ . '/../../../src/backend/Services/ExpressionService.php';
require_once __DIR__ . '/../../../src/backend/Services/WordService.php';

/**
 * Unit tests for the WordService class.
 *
 * Tests word/term CRUD operations through the service layer.
 */
class WordServiceTest extends TestCase
{
    private static bool $dbConnected = false;
    private static int $testLangId = 0;
    private WordService $service;

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
            // Create a test language if it doesn't exist
            $existingLang = Connection::fetchValue(
                "SELECT LgID AS value FROM " . Globals::table('languages') . " WHERE LgName = 'TestLanguage' LIMIT 1"
            );

            if ($existingLang) {
                self::$testLangId = (int)$existingLang;
            } else {
                Connection::query(
                    "INSERT INTO " . Globals::table('languages') . " (LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, " .
                    "LgTextSize, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, " .
                    "LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar, LgRightToLeft, LgShowRomanization) " .
                    "VALUES ('TestLanguage', 'http://test.com/###', '', 'http://translate.test/###', " .
                    "100, '', '.!?', '', 'a-zA-Z', 0, 0, 0, 1)"
                );
                self::$testLangId = (int)Connection::fetchValue(
                    "SELECT LAST_INSERT_ID() AS value"
                );
            }
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        // Clean up test words
        Connection::query("DELETE FROM " . Globals::table('words') . " WHERE WoLgID = " . self::$testLangId);
    }

    protected function setUp(): void
    {
        $this->service = new WordService();
    }

    protected function tearDown(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        // Clean up test words after each test
        Connection::query("DELETE FROM " . Globals::table('words') . " WHERE WoText LIKE 'test%'");
    }

    // ===== create() tests =====

    public function testCreateNewWord(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'testword',
            'WoStatus' => 1,
            'WoTranslation' => 'test translation',
            'WoSentence' => 'This is a {testword} sentence.',
            'WoRomanization' => 'testwɜːd',
        ];

        $result = $this->service->create($data);

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['id']);
        $this->assertEquals('testword', $result['textlc']);
        $this->assertStringContainsString('Term saved', $result['message']);
    }

    public function testCreateWordWithEmptyTranslation(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'testempty',
            'WoStatus' => 1,
            'WoTranslation' => '',
        ];

        $result = $this->service->create($data);

        $this->assertTrue($result['success']);

        // Verify the translation was saved as '*'
        $word = $this->service->findById($result['id']);
        $this->assertEquals('*', $word['WoTranslation']);
    }

    public function testCreateWordConvertsToLowercase(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'TestMixedCase',
            'WoStatus' => 1,
            'WoTranslation' => 'translation',
        ];

        $result = $this->service->create($data);

        $this->assertTrue($result['success']);
        $this->assertEquals('testmixedcase', $result['textlc']);
    }

    public function testCreateDuplicateWordFails(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'testduplicate',
            'WoStatus' => 1,
            'WoTranslation' => 'first',
        ];

        // Create first word
        $result1 = $this->service->create($data);
        $this->assertTrue($result1['success']);

        // Try to create duplicate
        $data['WoTranslation'] = 'second';
        $result2 = $this->service->create($data);

        $this->assertFalse($result2['success']);
        $this->assertStringContainsString('Duplicate entry', $result2['message']);
    }

    // ===== update() tests =====

    public function testUpdateWord(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create a word first
        $createData = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'testupdate',
            'WoStatus' => 1,
            'WoTranslation' => 'original',
        ];
        $createResult = $this->service->create($createData);
        $wordId = $createResult['id'];

        // Update the word
        $updateData = [
            'WoText' => 'testupdate',
            'WoStatus' => 3,
            'WoOldStatus' => 1,
            'WoTranslation' => 'updated translation',
            'WoSentence' => 'New sentence.',
            'WoRomanization' => 'new roman',
        ];

        $result = $this->service->update($wordId, $updateData);

        $this->assertTrue($result['success']);
        $this->assertEquals($wordId, $result['id']);
        $this->assertStringContainsString('Updated', $result['message']);

        // Verify the update
        $word = $this->service->findById($wordId);
        $this->assertEquals('updated translation', $word['WoTranslation']);
        $this->assertEquals('3', $word['WoStatus']);
    }

    public function testUpdateWordStatusChange(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create a word
        $createData = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'teststatus',
            'WoStatus' => 1,
            'WoTranslation' => 'translation',
        ];
        $createResult = $this->service->create($createData);
        $wordId = $createResult['id'];

        // Wait at least 1 second to ensure time difference (MySQL NOW() has second precision)
        sleep(1);

        // Update with status change
        $updateData = [
            'WoText' => 'teststatus',
            'WoStatus' => 5,
            'WoOldStatus' => 1,
            'WoTranslation' => 'translation',
        ];
        $this->service->update($wordId, $updateData);

        // Verify status was changed
        $updatedWord = $this->service->findById($wordId);
        $this->assertEquals('5', $updatedWord['WoStatus']);
        // Note: WoStatusChanged is updated with NOW() when status changes
    }

    // ===== findById() tests =====

    public function testFindByIdReturnsWord(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create a word
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'testfind',
            'WoStatus' => 2,
            'WoTranslation' => 'find me',
        ];
        $createResult = $this->service->create($data);

        // Find it
        $word = $this->service->findById($createResult['id']);

        $this->assertIsArray($word);
        $this->assertEquals('testfind', $word['WoText']);
        $this->assertEquals('find me', $word['WoTranslation']);
        $this->assertEquals('2', $word['WoStatus']);
    }

    public function testFindByIdReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->findById(999999);
        $this->assertNull($result);
    }

    // ===== findByText() tests =====

    public function testFindByTextReturnsWordId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create a word
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'testfindtext',
            'WoStatus' => 1,
            'WoTranslation' => 'translation',
        ];
        $createResult = $this->service->create($data);

        // Find by text
        $foundId = $this->service->findByText('testfindtext', self::$testLangId);

        $this->assertEquals($createResult['id'], $foundId);
    }

    public function testFindByTextReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->findByText('nonexistentword12345', self::$testLangId);
        $this->assertNull($result);
    }

    public function testFindByTextIsCaseSensitive(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create a lowercase word
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'testcase',
            'WoStatus' => 1,
            'WoTranslation' => 'translation',
        ];
        $this->service->create($data);

        // Search with lowercase should find it
        $result = $this->service->findByText('testcase', self::$testLangId);
        $this->assertNotNull($result);

        // Search with uppercase should not find it (WoTextLC stores lowercase)
        $result = $this->service->findByText('TESTCASE', self::$testLangId);
        $this->assertNull($result);
    }

    // ===== getLanguageData() tests =====

    public function testGetLanguageDataReturnsCorrectData(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $langData = $this->service->getLanguageData(self::$testLangId);

        $this->assertIsArray($langData);
        $this->assertArrayHasKey('showRoman', $langData);
        $this->assertArrayHasKey('translateUri', $langData);
        $this->assertArrayHasKey('name', $langData);

        $this->assertTrue($langData['showRoman']); // We set LgShowRomanization = 1
        $this->assertEquals('TestLanguage', $langData['name']);
        $this->assertStringContainsString('translate.test', $langData['translateUri']);
    }

    // ===== getWordCount() tests =====

    public function testGetWordCountReturnsWordCount(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create a word (note: WoWordCount is typically set during text parsing,
        // not during direct word creation, so it defaults to 0 or NULL)
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'testsingle',
            'WoStatus' => 1,
            'WoTranslation' => 'translation',
        ];
        $createResult = $this->service->create($data);

        $count = $this->service->getWordCount($createResult['id']);

        // Word count is an integer (0 for newly created words without text parsing)
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    // ===== textToClassName() tests =====

    public function testTextToClassNameConvertsText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $className = $this->service->textToClassName('hello');

        // Should return a hex-like string
        $this->assertIsString($className);
        $this->assertNotEmpty($className);
    }

    public function testTextToClassNameHandlesSpecialCharacters(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $className = $this->service->textToClassName('héllo wörld');

        $this->assertIsString($className);
        $this->assertNotEmpty($className);
    }

    // ===== Integration tests =====

    public function testCreateUpdateFindRoundTrip(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create
        $createData = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'testroundtrip',
            'WoStatus' => 1,
            'WoTranslation' => 'original',
            'WoRomanization' => 'roman1',
        ];
        $createResult = $this->service->create($createData);
        $wordId = $createResult['id'];

        // Verify create
        $word = $this->service->findById($wordId);
        $this->assertEquals('original', $word['WoTranslation']);

        // Update
        $updateData = [
            'WoText' => 'testroundtrip',
            'WoStatus' => 4,
            'WoOldStatus' => 1,
            'WoTranslation' => 'modified',
            'WoRomanization' => 'roman2',
        ];
        $this->service->update($wordId, $updateData);

        // Verify update
        $updatedWord = $this->service->findById($wordId);
        $this->assertEquals('modified', $updatedWord['WoTranslation']);
        $this->assertEquals('4', $updatedWord['WoStatus']);
        $this->assertEquals('roman2', $updatedWord['WoRomanization']);

        // Find by text
        $foundId = $this->service->findByText('testroundtrip', self::$testLangId);
        $this->assertEquals($wordId, $foundId);
    }

    // ===== delete() tests =====

    public function testDeleteWord(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create a word
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'testdelete',
            'WoStatus' => 1,
            'WoTranslation' => 'to be deleted',
        ];
        $createResult = $this->service->create($data);
        $wordId = $createResult['id'];

        // Verify it exists
        $word = $this->service->findById($wordId);
        $this->assertNotNull($word);

        // Delete it
        $result = $this->service->delete($wordId);
        $this->assertEquals('Deleted', $result);

        // Verify it's gone
        $deletedWord = $this->service->findById($wordId);
        $this->assertNull($deletedWord);
    }

    // ===== deleteMultiple() tests =====

    public function testDeleteMultipleWords(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create multiple words
        $ids = [];
        for ($i = 1; $i <= 3; $i++) {
            $data = [
                'WoLgID' => self::$testLangId,
                'WoText' => "testdelmulti$i",
                'WoStatus' => 1,
                'WoTranslation' => "translation $i",
            ];
            $result = $this->service->create($data);
            $ids[] = $result['id'];
        }

        // Delete them all
        $count = $this->service->deleteMultiple($ids);
        $this->assertEquals(3, $count);

        // Verify they're all gone
        foreach ($ids as $id) {
            $this->assertNull($this->service->findById($id));
        }
    }

    public function testDeleteMultipleWithEmptyArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->deleteMultiple([]);
        $this->assertEquals(0, $result);
    }

    // ===== setStatus() tests =====

    public function testSetStatus(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create a word
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'testsetstatus',
            'WoStatus' => 1,
            'WoTranslation' => 'translation',
        ];
        $createResult = $this->service->create($data);
        $wordId = $createResult['id'];

        // Set status to 5
        $result = $this->service->setStatus($wordId, 5);
        $this->assertNotEmpty($result);

        // Verify status changed
        $word = $this->service->findById($wordId);
        $this->assertEquals('5', $word['WoStatus']);
    }

    public function testSetStatusToWellKnown(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'testwellknown',
            'WoStatus' => 1,
            'WoTranslation' => 'translation',
        ];
        $createResult = $this->service->create($data);
        $wordId = $createResult['id'];

        $this->service->setStatus($wordId, 99);

        $word = $this->service->findById($wordId);
        $this->assertEquals('99', $word['WoStatus']);
    }

    public function testSetStatusToIgnored(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'testignored',
            'WoStatus' => 1,
            'WoTranslation' => 'translation',
        ];
        $createResult = $this->service->create($data);
        $wordId = $createResult['id'];

        $this->service->setStatus($wordId, 98);

        $word = $this->service->findById($wordId);
        $this->assertEquals('98', $word['WoStatus']);
    }

    // ===== updateStatusMultiple() tests =====

    public function testUpdateStatusMultipleAbsolute(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create multiple words
        $ids = [];
        for ($i = 1; $i <= 3; $i++) {
            $data = [
                'WoLgID' => self::$testLangId,
                'WoText' => "teststatmulti$i",
                'WoStatus' => 1,
                'WoTranslation' => "translation $i",
            ];
            $result = $this->service->create($data);
            $ids[] = $result['id'];
        }

        // Update all to status 5
        $count = $this->service->updateStatusMultiple($ids, 5);
        $this->assertEquals(3, $count);

        // Verify
        foreach ($ids as $id) {
            $word = $this->service->findById($id);
            $this->assertEquals('5', $word['WoStatus']);
        }
    }

    public function testUpdateStatusMultipleIncrement(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create a word with status 2
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'testincrement',
            'WoStatus' => 2,
            'WoTranslation' => 'translation',
        ];
        $createResult = $this->service->create($data);
        $wordId = $createResult['id'];

        // Increment status
        $this->service->updateStatusMultiple([$wordId], 1, true);

        // Verify status is now 3
        $word = $this->service->findById($wordId);
        $this->assertEquals('3', $word['WoStatus']);
    }

    public function testUpdateStatusMultipleDecrement(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create a word with status 4
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'testdecrement',
            'WoStatus' => 4,
            'WoTranslation' => 'translation',
        ];
        $createResult = $this->service->create($data);
        $wordId = $createResult['id'];

        // Decrement status
        $this->service->updateStatusMultiple([$wordId], -1, true);

        // Verify status is now 3
        $word = $this->service->findById($wordId);
        $this->assertEquals('3', $word['WoStatus']);
    }

    public function testUpdateStatusMultipleWithEmptyArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->updateStatusMultiple([], 5);
        $this->assertEquals(0, $result);
    }

    // ===== getWordData() tests =====

    public function testGetWordData(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create a word
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'testgetdata',
            'WoStatus' => 1,
            'WoTranslation' => 'my translation',
            'WoRomanization' => 'my romanization',
        ];
        $createResult = $this->service->create($data);
        $wordId = $createResult['id'];

        // Get word data
        $wordData = $this->service->getWordData($wordId);

        $this->assertIsArray($wordData);
        $this->assertEquals('testgetdata', $wordData['text']);
        $this->assertEquals('my translation', $wordData['translation']);
        $this->assertEquals('my romanization', $wordData['romanization']);
    }

    public function testGetWordDataReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getWordData(999999);
        $this->assertNull($result);
    }

    // ===== getWordText() tests =====

    public function testGetWordText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'testgettext',
            'WoStatus' => 1,
            'WoTranslation' => 'translation',
        ];
        $createResult = $this->service->create($data);

        $text = $this->service->getWordText($createResult['id']);
        $this->assertEquals('testgettext', $text);
    }

    public function testGetWordTextReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getWordText(999999);
        $this->assertNull($result);
    }

    // ===== deleteSentencesMultiple() tests =====

    public function testDeleteSentencesMultiple(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create a word with a sentence
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'testdelsent',
            'WoStatus' => 1,
            'WoTranslation' => 'translation',
            'WoSentence' => 'This is a {testdelsent} sentence.',
        ];
        $createResult = $this->service->create($data);
        $wordId = $createResult['id'];

        // Delete sentences
        $count = $this->service->deleteSentencesMultiple([$wordId]);
        $this->assertEquals(1, $count);

        // Verify sentence is null
        $word = $this->service->findById($wordId);
        $this->assertNull($word['WoSentence']);
    }

    // ===== toLowercaseMultiple() tests =====

    public function testToLowercaseMultiple(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create a word with mixed case
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'TestLower',
            'WoStatus' => 1,
            'WoTranslation' => 'translation',
        ];
        $createResult = $this->service->create($data);
        $wordId = $createResult['id'];

        // Convert to lowercase
        $count = $this->service->toLowercaseMultiple([$wordId]);
        $this->assertEquals(1, $count);

        // Verify text is lowercase
        $word = $this->service->findById($wordId);
        $this->assertEquals('testlower', $word['WoText']);
    }

    // ===== capitalizeMultiple() tests =====

    public function testCapitalizeMultiple(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create a lowercase word
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'testcapital',
            'WoStatus' => 1,
            'WoTranslation' => 'translation',
        ];
        $createResult = $this->service->create($data);
        $wordId = $createResult['id'];

        // Capitalize
        $count = $this->service->capitalizeMultiple([$wordId]);
        $this->assertEquals(1, $count);

        // Verify text is capitalized
        $word = $this->service->findById($wordId);
        $this->assertEquals('Testcapital', $word['WoText']);
    }

    // ===== createWithStatus() tests =====

    public function testCreateWithStatusWellKnown(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->createWithStatus(
            self::$testLangId,
            'testcreatewk',
            'testcreatewk',
            99
        );

        $this->assertGreaterThan(0, $result['id']);
        $this->assertEquals(1, $result['rows']);

        // Verify status
        $word = $this->service->findById($result['id']);
        $this->assertEquals('99', $word['WoStatus']);
    }

    public function testCreateWithStatusIgnored(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->createWithStatus(
            self::$testLangId,
            'testcreateig',
            'testcreateig',
            98
        );

        $this->assertGreaterThan(0, $result['id']);
        $this->assertEquals(1, $result['rows']);

        $word = $this->service->findById($result['id']);
        $this->assertEquals('98', $word['WoStatus']);
    }

    public function testCreateWithStatusExistingWord(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create a word first
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'testexisting',
            'WoStatus' => 1,
            'WoTranslation' => 'translation',
        ];
        $createResult = $this->service->create($data);
        $existingId = $createResult['id'];

        // Try to create with status - should return existing ID
        $result = $this->service->createWithStatus(
            self::$testLangId,
            'testexisting',
            'testexisting',
            99
        );

        $this->assertEquals($existingId, $result['id']);
        $this->assertEquals(0, $result['rows']); // No new rows inserted
    }

    // ===== getTextLanguageId() tests =====

    public function testGetTextLanguageIdReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTextLanguageId(999999);
        $this->assertNull($result);
    }

    // ===== bulkSaveTerms() tests =====

    public function testBulkSaveTermsCreatesMultipleWords(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $terms = [
            ['lg' => self::$testLangId, 'text' => 'testbulk1', 'status' => 1, 'trans' => 'bulk trans 1'],
            ['lg' => self::$testLangId, 'text' => 'testbulk2', 'status' => 2, 'trans' => 'bulk trans 2'],
            ['lg' => self::$testLangId, 'text' => 'testbulk3', 'status' => 3, 'trans' => ''],
        ];

        $maxWoId = $this->service->bulkSaveTerms($terms);

        // Verify all words were created
        $word1 = $this->service->findByText('testbulk1', self::$testLangId);
        $word2 = $this->service->findByText('testbulk2', self::$testLangId);
        $word3 = $this->service->findByText('testbulk3', self::$testLangId);

        $this->assertNotNull($word1);
        $this->assertNotNull($word2);
        $this->assertNotNull($word3);

        // Verify all IDs are greater than maxWoId
        $this->assertGreaterThan($maxWoId, $word1);
        $this->assertGreaterThan($maxWoId, $word2);
        $this->assertGreaterThan($maxWoId, $word3);

        // Verify statuses
        $wordData1 = $this->service->findById($word1);
        $wordData2 = $this->service->findById($word2);
        $wordData3 = $this->service->findById($word3);

        $this->assertEquals('1', $wordData1['WoStatus']);
        $this->assertEquals('2', $wordData2['WoStatus']);
        $this->assertEquals('3', $wordData3['WoStatus']);

        // Verify empty translation becomes '*'
        $this->assertEquals('*', $wordData3['WoTranslation']);
    }

    public function testBulkSaveTermsWithEmptyArrayReturnsMaxId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $maxWoId = $this->service->bulkSaveTerms([]);

        // Should return a non-negative value (the current max ID)
        $this->assertGreaterThanOrEqual(0, $maxWoId);
    }

    // ===== getNewWordsAfter() tests =====

    public function testGetNewWordsAfterReturnsNewWords(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Get current max ID
        $maxBefore = $this->service->bulkSaveTerms([]);

        // Create a word
        $data = [
            'WoLgID' => self::$testLangId,
            'WoText' => 'testnewafter',
            'WoStatus' => 1,
            'WoTranslation' => 'translation',
        ];
        $this->service->create($data);

        // Get new words
        $res = $this->service->getNewWordsAfter($maxBefore);

        $found = false;
        foreach ($res as $record) {
            if ($record['WoTextLC'] === 'testnewafter') {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found);
    }

    // ===== getLanguageDictionaries() tests =====

    public function testGetLanguageDictionariesReturnsEmptyForNonExistentText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguageDictionaries(999999);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('dict1', $result);
        $this->assertArrayHasKey('dict2', $result);
        $this->assertArrayHasKey('translate', $result);
    }

    // ===== Multi-word expression tests =====

    public function testCreateMultiWordCreatesExpression(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $data = [
            'lgid' => self::$testLangId,
            'text' => 'test multi word',
            'textlc' => 'test multi word',
            'status' => 1,
            'translation' => 'multi word translation',
            'sentence' => 'This is a {test multi word} sentence.',
            'roman' => 'test romanization',
            'wordcount' => 3,
        ];

        // Buffer output since insertExpressions outputs JS
        ob_start();
        $result = $this->service->createMultiWord($data);
        ob_end_clean();

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertGreaterThan(0, $result['id']);

        // Verify word was created
        $word = $this->service->findById($result['id']);
        $this->assertNotNull($word);
        $this->assertEquals('test multi word', $word['WoTextLC']);
        $this->assertEquals('1', $word['WoStatus']);
        $this->assertEquals('3', $word['WoWordCount']);
    }

    public function testUpdateMultiWordUpdatesExpression(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // First create a multi-word (buffer output)
        $createData = [
            'lgid' => self::$testLangId,
            'text' => 'update multi word',
            'textlc' => 'update multi word',
            'status' => 1,
            'translation' => 'original translation',
            'sentence' => '',
            'roman' => '',
            'wordcount' => 3,
        ];
        ob_start();
        $created = $this->service->createMultiWord($createData);
        ob_end_clean();
        $wid = $created['id'];

        // Now update it
        $updateData = [
            'text' => 'update multi word',
            'textlc' => 'update multi word',
            'translation' => 'updated translation',
            'sentence' => 'Updated sentence.',
            'roman' => 'updated roman',
        ];

        $result = $this->service->updateMultiWord($wid, $updateData, 1, 2);

        $this->assertEquals($wid, $result['id']);
        $this->assertEquals(2, $result['status']);

        // Verify update
        $word = $this->service->findById($wid);
        $this->assertEquals('updated translation', $word['WoTranslation']);
        $this->assertEquals('2', $word['WoStatus']);
    }

    public function testGetMultiWordDataReturnsData(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create a multi-word (buffer output)
        $createData = [
            'lgid' => self::$testLangId,
            'text' => 'get multi word',
            'textlc' => 'get multi word',
            'status' => 2,
            'translation' => 'get translation',
            'sentence' => 'Get sentence.',
            'roman' => 'get roman',
            'wordcount' => 3,
        ];
        ob_start();
        $created = $this->service->createMultiWord($createData);
        ob_end_clean();

        $result = $this->service->getMultiWordData($created['id']);

        $this->assertNotNull($result);
        $this->assertEquals('get multi word', $result['text']);
        $this->assertEquals(self::$testLangId, $result['lgid']);
        $this->assertEquals('get translation', $result['translation']);
        $this->assertEquals(2, $result['status']);
    }

    public function testGetMultiWordDataReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getMultiWordData(999999);
        $this->assertNull($result);
    }

    public function testFindMultiWordByTextFindsWord(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create a multi-word (buffer output)
        $createData = [
            'lgid' => self::$testLangId,
            'text' => 'find multi word',
            'textlc' => 'find multi word',
            'status' => 1,
            'translation' => '*',
            'sentence' => '',
            'roman' => '',
            'wordcount' => 3,
        ];
        ob_start();
        $created = $this->service->createMultiWord($createData);
        ob_end_clean();

        $result = $this->service->findMultiWordByText('find multi word', self::$testLangId);

        $this->assertEquals($created['id'], $result);
    }

    public function testFindMultiWordByTextReturnsNullWhenNotFound(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->findMultiWordByText('nonexistent multi word xyz', self::$testLangId);
        $this->assertNull($result);
    }

    public function testExportTermAsJsonReturnsValidJson(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $json = $this->service->exportTermAsJson(
            123,
            'test term',
            'test roman',
            'test translation',
            2
        );

        $decoded = json_decode($json, true);
        $this->assertNotNull($decoded);
        $this->assertEquals(123, $decoded['woid']);
        $this->assertEquals('test term', $decoded['text']);
        $this->assertEquals('test roman', $decoded['romanization']);
        $this->assertEquals('test translation', $decoded['translation']);
        $this->assertEquals(2, $decoded['status']);
    }
}
