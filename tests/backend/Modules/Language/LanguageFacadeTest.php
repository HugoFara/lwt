<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Language;

require_once __DIR__ . '/../../../../src/Shared/Infrastructure/Bootstrap/EnvLoader.php';

use Lwt\Shared\Infrastructure\Bootstrap\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Shared\Infrastructure\Database\Configuration;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Modules\Language\Application\UseCases\CreateLanguage;
use Lwt\Modules\Language\Application\UseCases\DeleteLanguage;
use Lwt\Modules\Language\Application\UseCases\GetLanguageById;
use Lwt\Modules\Language\Application\UseCases\GetLanguageCode;
use Lwt\Modules\Language\Application\UseCases\GetPhoneticReading;
use Lwt\Modules\Language\Application\UseCases\ListLanguages;
use Lwt\Modules\Language\Application\UseCases\ReparseLanguageTexts;
use Lwt\Modules\Language\Application\UseCases\UpdateLanguage;
use Lwt\Modules\Language\Domain\Language;
use Lwt\Modules\Language\Domain\LanguageRepositoryInterface;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../../src/Shared/Infrastructure/Bootstrap/db_bootstrap.php';

/**
 * Unit tests for the LanguageFacade class.
 *
 * Tests language management operations through the facade layer.
 */
class LanguageFacadeTest extends TestCase
{
    private static bool $dbConnected = false;
    private LanguageFacade $facade;

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

        if (self::$dbConnected) {
            // Clean up any lingering test data from previous runs
            $deleteFeeds = "DELETE FROM news_feeds WHERE NfLgID IN " .
                "(SELECT LgID FROM languages WHERE LgName LIKE 'TestLang_%' OR LgName LIKE 'DuplicateTest_%')";
            Connection::query($deleteFeeds);

            $deleteLangs = "DELETE FROM languages " .
                "WHERE LgName LIKE 'TestLang_%' OR LgName LIKE 'DuplicateTest_%'";
            Connection::query($deleteLangs);

            // Also clean up orphaned feeds that reference non-existent languages
            Connection::query("DELETE FROM news_feeds WHERE NfLgID NOT IN (SELECT LgID FROM languages)");
        }
    }

    protected function setUp(): void
    {
        $this->facade = new LanguageFacade();
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        // Clean up any test languages created during tests
        $deleteFeeds = "DELETE FROM news_feeds WHERE NfLgID IN " .
            "(SELECT LgID FROM languages WHERE LgName LIKE 'TestLang_%' OR LgName LIKE 'DuplicateTest_%')";
        Connection::query($deleteFeeds);

        $deleteLangs = "DELETE FROM languages " .
            "WHERE LgName LIKE 'TestLang_%' OR LgName LIKE 'DuplicateTest_%'";
        Connection::query($deleteLangs);
    }

    // ===== Constructor tests =====

    public function testConstructorCreatesValidFacade(): void
    {
        $facade = new LanguageFacade();
        $this->assertInstanceOf(LanguageFacade::class, $facade);
    }

    public function testConstructorAcceptsCustomRepository(): void
    {
        $mockRepo = $this->createMock(LanguageRepositoryInterface::class);
        $facade = new LanguageFacade($mockRepo);
        $this->assertInstanceOf(LanguageFacade::class, $facade);
    }

    // ===== createEmptyLanguage() tests =====

    public function testCreateEmptyLanguageReturnsLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $language = $this->facade->createEmptyLanguage();
        $this->assertInstanceOf(Language::class, $language);
    }

    public function testCreateEmptyLanguageHasDefaultValues(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $language = $this->facade->createEmptyLanguage();
        // Empty language has a default placeholder name
        $this->assertNotEmpty($language->name());
        $this->assertEquals(100, $language->textSize());
        $this->assertFalse($language->rightToLeft());
    }

    // ===== getById() tests =====

    public function testGetByIdReturnsNullForNonexistentId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getById(999999);
        $this->assertNull($result);
    }

    public function testGetByIdReturnsEmptyLanguageForZeroId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // ID 0 returns an empty/default language (for new language forms)
        $result = $this->facade->getById(0);
        $this->assertInstanceOf(Language::class, $result);
        $this->assertEquals(0, $result->id()->toInt());
    }

    public function testGetByIdReturnsEmptyLanguageForNegativeId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Negative IDs are normalized to 0 and return empty language
        $result = $this->facade->getById(-1);
        $this->assertInstanceOf(Language::class, $result);
        $this->assertEquals(0, $result->id()->toInt());
    }

    // ===== exists() tests =====

    public function testExistsReturnsFalseForNonexistentId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->assertFalse($this->facade->exists(999999));
    }

    public function testExistsReturnsFalseForZeroId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->assertFalse($this->facade->exists(0));
    }

    // ===== isDuplicateName() tests =====

    public function testIsDuplicateNameReturnsFalseForUniqueName(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $uniqueName = 'UniqueTestLanguage_' . uniqid();
        $this->assertFalse($this->facade->isDuplicateName($uniqueName));
    }

    public function testIsDuplicateNameReturnsFalseForEmptyName(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Empty names are not considered duplicates
        $this->assertFalse($this->facade->isDuplicateName(''));
    }

    // ===== getAllLanguages() tests =====

    public function testGetAllLanguagesReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getAllLanguages();
        $this->assertIsArray($result);
    }

    public function testGetAllLanguagesReturnsNameToIdMapping(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getAllLanguages();
        foreach ($result as $name => $id) {
            $this->assertIsString($name);
            $this->assertIsInt($id);
        }
    }

    // ===== getLanguagesForSelect() tests =====

    public function testGetLanguagesForSelectReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getLanguagesForSelect();
        $this->assertIsArray($result);
    }

    public function testGetLanguagesForSelectHasCorrectStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getLanguagesForSelect();
        foreach ($result as $option) {
            $this->assertArrayHasKey('id', $option);
            $this->assertArrayHasKey('name', $option);
            $this->assertIsInt($option['id']);
            $this->assertIsString($option['name']);
        }
    }

    public function testGetLanguagesForSelectTruncatesLongNames(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $maxLength = 10;
        $result = $this->facade->getLanguagesForSelect($maxLength);
        foreach ($result as $option) {
            $this->assertLessThanOrEqual($maxLength + 3, strlen($option['name'])); // +3 for "..."
        }
    }

    // ===== getLanguagesWithStats() tests =====

    public function testGetLanguagesWithStatsReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getLanguagesWithStats();
        $this->assertIsArray($result);
    }

    // ===== getLanguagesWithTextCounts() tests =====

    public function testGetLanguagesWithTextCountsReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getLanguagesWithTextCounts();
        $this->assertIsArray($result);
    }

    public function testGetLanguagesWithTextCountsHasCorrectStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getLanguagesWithTextCounts();
        $this->assertIsArray($result);
        foreach ($result as $item) {
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('text_count', $item);
        }
    }

    // ===== getLanguagesWithArchivedTextCounts() tests =====

    public function testGetLanguagesWithArchivedTextCountsReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getLanguagesWithArchivedTextCounts();
        $this->assertIsArray($result);
    }

    // ===== getRelatedDataCounts() tests =====

    public function testGetRelatedDataCountsReturnsArrayForNonexistentId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getRelatedDataCounts(999999);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('texts', $result);
        $this->assertArrayHasKey('archivedTexts', $result);
        $this->assertArrayHasKey('words', $result);
        $this->assertArrayHasKey('feeds', $result);
    }

    public function testGetRelatedDataCountsReturnsZerosForNonexistentId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getRelatedDataCounts(999999);
        $this->assertEquals(0, $result['texts']);
        $this->assertEquals(0, $result['archivedTexts']);
        $this->assertEquals(0, $result['words']);
        $this->assertEquals(0, $result['feeds']);
    }

    // ===== toViewObject() tests =====

    public function testToViewObjectReturnsStdClass(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $language = $this->facade->createEmptyLanguage();
        $viewObj = $this->facade->toViewObject($language);
        $this->assertInstanceOf(\stdClass::class, $viewObj);
    }

    public function testToViewObjectHasExpectedProperties(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $language = $this->facade->createEmptyLanguage();
        $viewObj = $this->facade->toViewObject($language);

        // View object uses lowercase property names
        $this->assertObjectHasProperty('id', $viewObj);
        $this->assertObjectHasProperty('name', $viewObj);
        $this->assertObjectHasProperty('textsize', $viewObj);
        $this->assertObjectHasProperty('rightoleft', $viewObj);
        $this->assertObjectHasProperty('dict1uri', $viewObj);
        $this->assertObjectHasProperty('translator', $viewObj);
    }

    // ===== canDelete() tests =====

    public function testCanDeleteReturnsTrueForNonexistentId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // A non-existent language should be deletable (nothing to check)
        $result = $this->facade->canDelete(999999);
        $this->assertTrue($result);
    }

    // ===== getLanguageName() tests =====

    public function testGetLanguageNameReturnsEmptyForNonexistentId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getLanguageName(999999);
        $this->assertEquals('', $result);
    }

    public function testGetLanguageNameReturnsEmptyForZeroId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getLanguageName(0);
        $this->assertEquals('', $result);
    }

    public function testGetLanguageNameAcceptsStringId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getLanguageName('999999');
        $this->assertEquals('', $result);
    }

    // ===== getScriptDirectionTag() tests =====

    public function testGetScriptDirectionTagReturnsEmptyForNonexistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getScriptDirectionTag(999999);
        $this->assertEquals('', $result);
    }

    public function testGetScriptDirectionTagReturnsEmptyForNull(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getScriptDirectionTag(null);
        $this->assertEquals('', $result);
    }

    public function testGetScriptDirectionTagAcceptsStringId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getScriptDirectionTag('999999');
        $this->assertIsString($result);
    }

    // ===== getLanguageCode() tests =====

    public function testGetLanguageCodeReturnsEmptyForNonexistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getLanguageCode(999999, []);
        $this->assertEquals('', $result);
    }

    public function testGetLanguageCodeReturnsEmptyWhenNoMatchInTable(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // When language table doesn't contain a matching preset, returns empty
        $result = $this->facade->getLanguageCode(999999, []);
        $this->assertEquals('', $result);
    }

    // ===== Method existence tests =====

    public function testCreateMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'create'),
            'create method should exist'
        );
    }

    public function testCreateFromDataMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'createFromData'),
            'createFromData method should exist'
        );
    }

    public function testUpdateMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'update'),
            'update method should exist'
        );
    }

    public function testUpdateFromDataMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'updateFromData'),
            'updateFromData method should exist'
        );
    }

    public function testDeleteMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'delete'),
            'delete method should exist'
        );
    }

    public function testDeleteByIdMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'deleteById'),
            'deleteById method should exist'
        );
    }

    public function testRefreshMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'refresh'),
            'refresh method should exist'
        );
    }

    public function testRefreshTextsMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'refreshTexts'),
            'refreshTexts method should exist'
        );
    }

    public function testGetPhoneticReadingByIdMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'getPhoneticReadingById'),
            'getPhoneticReadingById method should exist'
        );
    }

    public function testGetPhoneticReadingByCodeMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'getPhoneticReadingByCode'),
            'getPhoneticReadingByCode method should exist'
        );
    }

    public function testGetLanguageDataFromRequestMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'getLanguageDataFromRequest'),
            'getLanguageDataFromRequest method should exist'
        );
    }

    // ===== Integration test: CRUD lifecycle =====

    public function testCrudLifecycle(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $uniqueName = 'TestLang_' . uniqid();

        // Create
        $langId = $this->facade->createFromData([
            'name' => $uniqueName,
            'dict1Uri' => 'https://example.com/dict?q=lwt_term',
            'regexpSplitSentences' => '.!?',
            'regexpWordCharacters' => 'a-zA-Z',
        ]);
        $this->assertIsInt($langId);
        $this->assertGreaterThan(0, $langId);

        // Read
        $language = $this->facade->getById($langId);
        $this->assertInstanceOf(Language::class, $language);
        $this->assertEquals($uniqueName, $language->name());

        // Exists
        $this->assertTrue($this->facade->exists($langId));

        // Get name
        $this->assertEquals($uniqueName, $this->facade->getLanguageName($langId));

        // Is in list
        $allLanguages = $this->facade->getAllLanguages();
        $this->assertArrayHasKey($uniqueName, $allLanguages);

        // Update
        $updatedName = $uniqueName . '_Updated';
        $updateResult = $this->facade->updateFromData($langId, [
            'name' => $updatedName,
            'dict1Uri' => 'https://example.com/dict?q=lwt_term',  // Required: NOT NULL field
        ]);
        $this->assertTrue($updateResult['success']);

        // Verify update
        $updated = $this->facade->getById($langId);
        $this->assertEquals($updatedName, $updated->name());

        // Related data counts
        $counts = $this->facade->getRelatedDataCounts($langId);
        $this->assertEquals(0, $counts['texts']);
        $this->assertEquals(0, $counts['words']);
        $this->assertEquals(0, $counts['archivedTexts']);
        $this->assertEquals(0, $counts['feeds']);

        // Can delete (should be true - no related data)
        $this->assertTrue($this->facade->canDelete($langId));

        // Delete
        $deleted = $this->facade->deleteById($langId);
        $this->assertTrue($deleted);

        // Verify deletion
        $this->assertNull($this->facade->getById($langId));
        $this->assertFalse($this->facade->exists($langId));
    }

    // ===== Duplicate name tests =====

    public function testDuplicateNameDetection(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $uniqueName = 'DuplicateTest_' . uniqid();

        // Create first language
        $langId1 = $this->facade->createFromData([
            'name' => $uniqueName,
            'dict1Uri' => 'https://example.com/dict?q=lwt_term',
            'regexpSplitSentences' => '.!?',
            'regexpWordCharacters' => 'a-zA-Z',
        ]);
        $this->assertGreaterThan(0, $langId1);

        // Check duplicate detection
        $this->assertTrue($this->facade->isDuplicateName($uniqueName));

        // Exclude self should return false
        $this->assertFalse($this->facade->isDuplicateName($uniqueName, $langId1));

        // Cleanup
        $this->facade->deleteById($langId1);
    }

    // ===== getLanguagesForSelect with existing languages =====

    public function testGetLanguagesForSelectWithExistingLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $uniqueName = 'SelectTest_' . uniqid();

        // Create a test language
        $langId = $this->facade->createFromData([
            'name' => $uniqueName,
            'dict1Uri' => 'https://example.com/dict?q=lwt_term',
            'regexpSplitSentences' => '.!?',
            'regexpWordCharacters' => 'a-zA-Z',
        ]);

        // Get select options
        $options = $this->facade->getLanguagesForSelect();

        // Find our language in options
        $found = false;
        foreach ($options as $option) {
            if ($option['id'] === $langId) {
                $this->assertEquals($uniqueName, $option['name']);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Created language should appear in select options');

        // Cleanup
        $this->facade->deleteById($langId);
    }
}
