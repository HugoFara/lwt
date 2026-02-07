<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Services;

require_once __DIR__ . '/../../../../../src/Shared/Infrastructure/Bootstrap/EnvLoader.php';

use Lwt\Shared\Infrastructure\Bootstrap\EnvLoader;
use Lwt\Shared\Infrastructure\Globals;
use Lwt\Modules\Vocabulary\Application\Services\WordContextService;
use Lwt\Shared\Infrastructure\Database\Configuration;
use Lwt\Shared\Infrastructure\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../../../src/Shared/Infrastructure/Bootstrap/db_bootstrap.php';

/**
 * Unit tests for the WordContextService class.
 *
 * Tests language/text context operations.
 */
class WordContextServiceTest extends TestCase
{
    private static bool $dbConnected = false;
    private static int $testLangId = 0;
    private WordContextService $service;

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
                    "INSERT INTO " . Globals::table('languages') .
                    " (LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, " .
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

        // Clean up test language
        Connection::query("DELETE FROM " . Globals::table('languages') . " WHERE LgID = " . self::$testLangId);
    }

    protected function setUp(): void
    {
        $this->service = new WordContextService();
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

    // ===== exportTermAsJson() tests =====

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

    // ===== getLanguageIdFromText() tests =====

    public function testGetLanguageIdFromTextReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguageIdFromText(999999);
        $this->assertNull($result);
    }
}
