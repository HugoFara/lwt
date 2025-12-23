<?php declare(strict_types=1);
namespace Lwt\Tests\Services;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Modules\Language\Domain\Language;
use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use Lwt\Services\LanguageService;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Services/LanguageService.php';
// Module classes loaded via autoloader

/**
 * Unit tests for the LanguageService class.
 *
 * Tests language CRUD operations, text reparsing,
 * and related data count functionality.
 */
class LanguageServiceTest extends TestCase
{
    private static bool $dbConnected = false;
    private LanguageService $service;
    private static array $testLanguageIds = [];

    /** @var array<string, mixed> Original $_REQUEST for cleanup */
    private array $originalRequest;

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
    }

    protected function setUp(): void
    {
        $this->originalRequest = $_REQUEST;
        $_REQUEST = [];
        $this->service = new LanguageService();
    }

    protected function tearDown(): void
    {
        $_REQUEST = $this->originalRequest;

        if (!self::$dbConnected) {
            return;
        }

        // Clean up test languages after each test
        Connection::query("DELETE FROM languages WHERE LgName LIKE 'Test_%'");
        Connection::query("DELETE FROM languages WHERE LgName LIKE 'TestLang%'");
        self::$testLanguageIds = [];
    }

    /**
     * Set language data in $_REQUEST for testing create/update.
     *
     * @param array<string, mixed> $data Language data
     */
    private function setLanguageRequestData(array $data): void
    {
        $_REQUEST = $data;
    }

    /**
     * Helper to create a test language directly in the database.
     *
     * @param string $name Language name
     *
     * @return int The created language ID
     */
    private function createTestLanguage(string $name): int
    {
        Connection::query(
            "INSERT INTO languages (
                LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI,
                LgTextSize, LgRegexpSplitSentences, LgRegexpWordCharacters,
                LgRemoveSpaces, LgSplitEachChar, LgRightToLeft, LgShowRomanization
            ) VALUES (
                '$name', 'https://dict.test/lwt_term', '', 'https://translate.test/lwt_term',
                100, '.!?', 'a-zA-Z',
                0, 0, 0, 1
            )"
        );
        $id = (int) mysqli_insert_id(Globals::getDbConnection());
        self::$testLanguageIds[] = $id;
        return $id;
    }

    // ===== getAllLanguages() tests =====

    public function testGetAllLanguagesReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getAllLanguages();
        $this->assertIsArray($result);
    }

    public function testGetAllLanguagesReturnsNameToIdMapping(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create a test language
        $this->createTestLanguage('TestLang_GetAll');

        $result = $this->service->getAllLanguages();

        $this->assertArrayHasKey('TestLang_GetAll', $result);
        $this->assertIsInt($result['TestLang_GetAll']);
    }

    public function testGetAllLanguagesExcludesEmptyNames(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Clean up any existing empty-name language first
        Connection::query(
            "DELETE FROM languages WHERE LgName = ''"
        );

        // Insert language with empty name (placeholder)
        Connection::query(
            "INSERT INTO languages (LgName, LgDict1URI, LgTextSize, LgRegexpSplitSentences, LgRegexpWordCharacters)
             VALUES ('', 'https://test.com', 100, '.!?', 'a-z')"
        );

        $result = $this->service->getAllLanguages();

        $this->assertArrayNotHasKey('', $result);

        // Clean up
        Connection::query(
            "DELETE FROM languages WHERE LgName = ''"
        );
    }

    // ===== getById() tests =====

    public function testGetByIdReturnsLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguage('TestLang_GetById');

        $result = $this->service->getById($id);

        $this->assertInstanceOf(Language::class, $result);
        $this->assertEquals($id, $result->id()->toInt());
        $this->assertEquals('TestLang_GetById', $result->name());
    }

    public function testGetByIdReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getById(999999);

        $this->assertNull($result);
    }

    public function testGetByIdReturnsEmptyLanguageForZero(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getById(0);

        $this->assertInstanceOf(Language::class, $result);
        $this->assertTrue($result->id()->isNew());
        $this->assertEquals('New Language', $result->name());
    }

    public function testGetByIdReturnsEmptyLanguageForNegative(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getById(-1);

        $this->assertInstanceOf(Language::class, $result);
        $this->assertTrue($result->id()->isNew());
    }

    public function testGetByIdPopulatesAllFields(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguage('TestLang_AllFields');

        $result = $this->service->getById($id);

        $this->assertEquals('https://dict.test/lwt_term', $result->dict1Uri());
        $this->assertEquals('', $result->dict2Uri());
        $this->assertEquals('https://translate.test/lwt_term', $result->translatorUri());
        $this->assertEquals(100, $result->textSize());
        $this->assertEquals('.!?', $result->regexpSplitSentences());
        $this->assertEquals('a-zA-Z', $result->regexpWordCharacters());
        $this->assertFalse($result->removeSpaces());
        $this->assertFalse($result->splitEachChar());
        $this->assertFalse($result->rightToLeft());
        $this->assertTrue($result->showRomanization());
    }

    // ===== createEmptyLanguage() tests =====

    public function testCreateEmptyLanguageReturnsLanguageObject(): void
    {
        $result = $this->service->createEmptyLanguage();

        $this->assertInstanceOf(Language::class, $result);
    }

    public function testCreateEmptyLanguageHasDefaultValues(): void
    {
        $result = $this->service->createEmptyLanguage();

        $this->assertTrue($result->id()->isNew());
        $this->assertEquals('New Language', $result->name());
        $this->assertEquals('', $result->dict1Uri());
        $this->assertEquals('', $result->dict2Uri());
        $this->assertEquals('', $result->translatorUri());
        $this->assertEquals(100, $result->textSize());
        $this->assertEquals('', $result->characterSubstitutions());
        $this->assertEquals('.!?', $result->regexpSplitSentences());
        $this->assertEquals('', $result->exceptionsSplitSentences());
        $this->assertEquals('a-zA-Z', $result->regexpWordCharacters());
        $this->assertFalse($result->removeSpaces());
        $this->assertFalse($result->splitEachChar());
        $this->assertFalse($result->rightToLeft());
        $this->assertEquals('', $result->ttsVoiceApi());
        $this->assertTrue($result->showRomanization());
    }

    // ===== exists() tests =====

    public function testExistsReturnsTrueForExistingLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguage('TestLang_Exists');

        $result = $this->service->exists($id);

        $this->assertTrue($result);
    }

    public function testExistsReturnsFalseForNonExistentLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->exists(999999);

        $this->assertFalse($result);
    }

    // ===== create() tests =====

    public function testCreateSavesLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->setLanguageRequestData([
            'LgName' => 'TestLang_Create',
            'LgDict1URI' => 'https://dict.test/lwt_term',
            'LgDict2URI' => '',
            'LgGoogleTranslateURI' => 'https://translate.test/lwt_term',
            'LgExportTemplate' => '',
            'LgTextSize' => '150',
            'LgCharacterSubstitutions' => '',
            'LgRegexpSplitSentences' => '.!?',
            'LgExceptionsSplitSentences' => '',
            'LgRegexpWordCharacters' => 'a-zA-Z',
            'LgTTSVoiceAPI' => '',
        ]);

        $result = $this->service->create();

        $this->assertStringContainsString('Saved', $result);

        // Verify language was created
        $languages = $this->service->getAllLanguages();
        $this->assertArrayHasKey('TestLang_Create', $languages);
    }

    public function testCreateWithCheckboxOptions(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->setLanguageRequestData([
            'LgName' => 'TestLang_Checkboxes',
            'LgDict1URI' => 'https://dict.test/lwt_term',
            'LgDict2URI' => '',
            'LgGoogleTranslateURI' => '',
            'LgExportTemplate' => '',
            'LgTextSize' => '100',
            'LgCharacterSubstitutions' => '',
            'LgRegexpSplitSentences' => '.!?',
            'LgExceptionsSplitSentences' => '',
            'LgRegexpWordCharacters' => 'a-zA-Z',
            'LgTTSVoiceAPI' => '',
            'LgRemoveSpaces' => '1',
            'LgSplitEachChar' => '1',
            'LgRightToLeft' => '1',
            'LgShowRomanization' => '1',
        ]);

        $this->service->create();

        $languages = $this->service->getAllLanguages();
        $id = $languages['TestLang_Checkboxes'];
        $lang = $this->service->getById($id);

        $this->assertTrue($lang->removeSpaces());
        $this->assertTrue($lang->splitEachChar());
        $this->assertTrue($lang->rightToLeft());
        $this->assertTrue($lang->showRomanization());
    }

    // ===== update() tests =====

    public function testUpdateModifiesLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguage('TestLang_Update');

        $this->setLanguageRequestData([
            'LgName' => 'TestLang_Updated',
            'LgDict1URI' => 'https://newdict.test/lwt_term',
            'LgDict2URI' => 'https://dict2.test/lwt_term',
            'LgGoogleTranslateURI' => 'https://newtranslate.test/lwt_term',
            'LgExportTemplate' => 'template',
            'LgTextSize' => '200',
            'LgCharacterSubstitutions' => 'a=b',
            'LgRegexpSplitSentences' => '.!?:',
            'LgExceptionsSplitSentences' => 'Mr.',
            'LgRegexpWordCharacters' => 'a-zA-Z0-9',
            'LgTTSVoiceAPI' => '{"input": "lwt_term"}',
        ]);

        $result = $this->service->update($id);

        $this->assertStringContainsString('Updated', $result);

        $lang = $this->service->getById($id);
        $this->assertEquals('TestLang_Updated', $lang->name());
        $this->assertEquals('https://newdict.test/lwt_term', $lang->dict1Uri());
        $this->assertEquals(200, $lang->textSize());
    }

    public function testUpdateReturnsErrorForNonExistentLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->setLanguageRequestData([
            'LgName' => 'TestLang_NotFound',
            'LgDict1URI' => '',
            'LgDict2URI' => '',
            'LgGoogleTranslateURI' => '',
            'LgExportTemplate' => '',
            'LgTextSize' => '100',
            'LgCharacterSubstitutions' => '',
            'LgRegexpSplitSentences' => '.!?',
            'LgExceptionsSplitSentences' => '',
            'LgRegexpWordCharacters' => 'a-z',
            'LgTTSVoiceAPI' => '',
        ]);

        $result = $this->service->update(999999);

        $this->assertStringContainsString('Cannot access language data', $result);
    }

    public function testUpdateIndicatesReparsingNotNeeded(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguage('TestLang_NoReparse');

        // Update only non-parsing-related fields
        $this->setLanguageRequestData([
            'LgName' => 'TestLang_NoReparse_Updated',
            'LgDict1URI' => 'https://newdict.test/lwt_term',
            'LgDict2URI' => '',
            'LgGoogleTranslateURI' => '',
            'LgExportTemplate' => '',
            'LgTextSize' => '150',
            'LgCharacterSubstitutions' => '',
            'LgRegexpSplitSentences' => '.!?',
            'LgExceptionsSplitSentences' => '',
            'LgRegexpWordCharacters' => 'a-zA-Z',
            'LgTTSVoiceAPI' => '',
        ]);

        $result = $this->service->update($id);

        // When no parsing-related fields change, it says "Reparsing not needed"
        $this->assertStringContainsString('Reparsing not needed', $result);
    }

    // ===== delete() tests =====

    public function testDeleteRemovesLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguage('TestLang_Delete');

        $result = $this->service->delete($id);

        $this->assertStringContainsString('Deleted', $result);
        $this->assertFalse($this->service->exists($id));
    }

    public function testDeleteFailsWithRelatedTexts(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguage('TestLang_WithTexts');

        // Add a related text
        Connection::query(
            "INSERT INTO texts (TxLgID, TxTitle, TxText, TxAudioURI)
             VALUES ($id, 'Test Text', 'Test content', '')"
        );

        $result = $this->service->delete($id);

        $this->assertStringContainsString('must first delete', $result);
        $this->assertTrue($this->service->exists($id));

        // Cleanup
        Connection::query("DELETE FROM texts WHERE TxLgID = $id");
    }

    public function testDeleteFailsWithRelatedWords(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguage('TestLang_WithWords');

        // Add a related word
        Connection::query(
            "INSERT INTO words (WoLgID, WoText, WoTextLC, WoStatus, WoWordCount, WoStatusChanged)
             VALUES ($id, 'test', 'test', 1, 1, NOW())"
        );

        $result = $this->service->delete($id);

        $this->assertStringContainsString('must first delete', $result);
        $this->assertTrue($this->service->exists($id));

        // Cleanup
        Connection::query("DELETE FROM words WHERE WoLgID = $id");
    }

    // ===== getRelatedDataCounts() tests =====

    public function testGetRelatedDataCountsReturnsZerosForNewLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguage('TestLang_NoCounts');

        $result = $this->service->getRelatedDataCounts($id);

        $this->assertEquals(0, $result['texts']);
        $this->assertEquals(0, $result['archivedTexts']);
        $this->assertEquals(0, $result['words']);
        $this->assertEquals(0, $result['feeds']);
    }

    public function testGetRelatedDataCountsReturnsCorrectCounts(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguage('TestLang_WithData');

        // Add texts
        Connection::query(
            "INSERT INTO texts (TxLgID, TxTitle, TxText, TxAudioURI)
             VALUES ($id, 'Text 1', 'Content 1', ''), ($id, 'Text 2', 'Content 2', '')"
        );

        // Add a word
        Connection::query(
            "INSERT INTO words (WoLgID, WoText, WoTextLC, WoStatus, WoWordCount, WoStatusChanged)
             VALUES ($id, 'word', 'word', 1, 1, NOW())"
        );

        $result = $this->service->getRelatedDataCounts($id);

        $this->assertEquals(2, $result['texts']);
        $this->assertEquals(1, $result['words']);
        $this->assertEquals(0, $result['archivedTexts']);
        $this->assertEquals(0, $result['feeds']);

        // Cleanup
        Connection::query("DELETE FROM texts WHERE TxLgID = $id");
        Connection::query("DELETE FROM words WHERE WoLgID = $id");
    }

    // ===== canDelete() tests =====

    public function testCanDeleteReturnsTrueForNewLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguage('TestLang_CanDelete');

        $result = $this->service->canDelete($id);

        $this->assertTrue($result);
    }

    public function testCanDeleteReturnsFalseWithRelatedData(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguage('TestLang_CannotDelete');

        // Add a related text
        Connection::query(
            "INSERT INTO texts (TxLgID, TxTitle, TxText, TxAudioURI)
             VALUES ($id, 'Test', 'Content', '')"
        );

        $result = $this->service->canDelete($id);

        $this->assertFalse($result);

        // Cleanup
        Connection::query("DELETE FROM texts WHERE TxLgID = $id");
    }

    // ===== isDuplicateName() tests =====

    public function testIsDuplicateNameReturnsTrueForDuplicate(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestLanguage('TestLang_Duplicate');

        $result = $this->service->isDuplicateName('TestLang_Duplicate');

        $this->assertTrue($result);
    }

    public function testIsDuplicateNameReturnsFalseForUnique(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->isDuplicateName('NonExistentLanguage123');

        $this->assertFalse($result);
    }

    public function testIsDuplicateNameExcludesCurrentLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguage('TestLang_SameName');

        // Should not be duplicate when excluding its own ID
        $result = $this->service->isDuplicateName('TestLang_SameName', $id);

        $this->assertFalse($result);
    }

    // ===== getLanguagesWithStats() tests =====

    public function testGetLanguagesWithStatsReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguagesWithStats();

        $this->assertIsArray($result);
    }

    public function testGetLanguagesWithStatsIncludesExpectedFields(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestLanguage('TestLang_Stats');

        $result = $this->service->getLanguagesWithStats();

        // Find our test language
        $testLang = null;
        foreach ($result as $lang) {
            if ($lang['name'] === 'TestLang_Stats') {
                $testLang = $lang;
                break;
            }
        }

        $this->assertNotNull($testLang);
        $this->assertArrayHasKey('id', $testLang);
        $this->assertArrayHasKey('name', $testLang);
        $this->assertArrayHasKey('hasExportTemplate', $testLang);
        $this->assertArrayHasKey('textCount', $testLang);
        $this->assertArrayHasKey('archivedTextCount', $testLang);
        $this->assertArrayHasKey('wordCount', $testLang);
        $this->assertArrayHasKey('feedCount', $testLang);
        $this->assertArrayHasKey('articleCount', $testLang);
    }

    public function testGetLanguagesWithStatsExcludesEmptyNames(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // First create a valid test language to ensure we have at least one result
        $this->createTestLanguage('TestLang_StatsNotEmpty');

        // Insert language with empty name
        Connection::query(
            "INSERT INTO languages (LgName, LgDict1URI, LgTextSize, LgRegexpSplitSentences, LgRegexpWordCharacters)
             VALUES ('', 'https://test.com', 100, '.!?', 'a-z')"
        );

        $result = $this->service->getLanguagesWithStats();

        // Ensure we have results to check
        $this->assertNotEmpty($result);

        foreach ($result as $lang) {
            $this->assertNotEquals('', $lang['name']);
        }
    }

    // ===== refresh() tests =====

    public function testRefreshReturnsMessage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguage('TestLang_Refresh');

        $result = $this->service->refresh($id);

        $this->assertStringContainsString('Sentences deleted', $result);
        $this->assertStringContainsString('Text items deleted', $result);
        $this->assertStringContainsString('Sentences added', $result);
        $this->assertStringContainsString('Text items added', $result);
    }
}
