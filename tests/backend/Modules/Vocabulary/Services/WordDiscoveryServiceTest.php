<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Services;

require_once __DIR__ . '/../../../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\Bootstrap\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Modules\Vocabulary\Application\Services\WordDiscoveryService;
use Lwt\Modules\Vocabulary\Application\Services\WordCrudService;
use Lwt\Shared\Infrastructure\Database\Configuration;
use Lwt\Shared\Infrastructure\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../../../src/backend/Core/Bootstrap/db_bootstrap.php';

/**
 * Unit tests for the WordDiscoveryService class.
 *
 * Tests word discovery and quick creation operations.
 */
class WordDiscoveryServiceTest extends TestCase
{
    private static bool $dbConnected = false;
    private static int $testLangId = 0;
    private WordDiscoveryService $service;
    private WordCrudService $crudService;

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
        $this->service = new WordDiscoveryService();
        $this->crudService = new WordCrudService();
    }

    protected function tearDown(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        // Clean up test words after each test
        Connection::query("DELETE FROM " . Globals::table('words') . " WHERE WoText LIKE 'test%'");
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
        $createResult = $this->crudService->create($data);
        $wordId = $createResult['id'];

        // Set status to 5
        $result = $this->service->setStatus($wordId, 5);
        $this->assertNotEmpty($result);

        // Verify status changed
        $word = $this->crudService->findById($wordId);
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
        $createResult = $this->crudService->create($data);
        $wordId = $createResult['id'];

        $this->service->setStatus($wordId, 99);

        $word = $this->crudService->findById($wordId);
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
        $createResult = $this->crudService->create($data);
        $wordId = $createResult['id'];

        $this->service->setStatus($wordId, 98);

        $word = $this->crudService->findById($wordId);
        $this->assertEquals('98', $word['WoStatus']);
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
        $word = $this->crudService->findById($result['id']);
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

        $word = $this->crudService->findById($result['id']);
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
        $createResult = $this->crudService->create($data);
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
}
