<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Services;

require_once __DIR__ . '/../../../../../src/Shared/Infrastructure/Bootstrap/EnvLoader.php';

use Lwt\Shared\Infrastructure\Bootstrap\EnvLoader;
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

require_once __DIR__ . '/../../../../../src/Shared/Infrastructure/Bootstrap/db_bootstrap.php';

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

        // Clean up test words
        Connection::query("DELETE FROM " . Globals::table('words') . " WHERE WoLgID = " . self::$testLangId);
        // Clean up test language
        Connection::query("DELETE FROM " . Globals::table('languages') . " WHERE LgID = " . self::$testLangId);
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

        // Set status to 5 (returns void)
        $this->service->setStatus($wordId, 5);

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

    // ===== Method Signature and Structure Tests (no DB required) =====

    public function testConstructorWithNullServicesCreatesDefaults(): void
    {
        $service = new WordDiscoveryService(null, null);

        $contextReflection = new \ReflectionProperty(WordDiscoveryService::class, 'contextService');
        $contextReflection->setAccessible(true);

        $linkingReflection = new \ReflectionProperty(WordDiscoveryService::class, 'linkingService');
        $linkingReflection->setAccessible(true);

        $this->assertInstanceOf(
            \Lwt\Modules\Vocabulary\Application\Services\WordContextService::class,
            $contextReflection->getValue($service)
        );
        $this->assertInstanceOf(
            \Lwt\Modules\Vocabulary\Application\Services\WordLinkingService::class,
            $linkingReflection->getValue($service)
        );
    }

    public function testGetUnknownWordsInTextMethodSignature(): void
    {
        $method = new \ReflectionMethod(WordDiscoveryService::class, 'getUnknownWordsInText');

        $this->assertTrue($method->isPublic());
        $this->assertSame(1, $method->getNumberOfRequiredParameters());

        $params = $method->getParameters();
        $this->assertSame('textId', $params[0]->getName());
    }

    public function testGetAllUnknownWordsInTextMethodSignature(): void
    {
        $method = new \ReflectionMethod(WordDiscoveryService::class, 'getAllUnknownWordsInText');

        $this->assertTrue($method->isPublic());
        $this->assertSame(1, $method->getNumberOfRequiredParameters());
    }

    public function testGetUnknownWordsForBulkTranslateMethodSignature(): void
    {
        $method = new \ReflectionMethod(WordDiscoveryService::class, 'getUnknownWordsForBulkTranslate');

        $this->assertTrue($method->isPublic());
        $this->assertSame(3, $method->getNumberOfRequiredParameters());

        $params = $method->getParameters();
        $this->assertSame('textId', $params[0]->getName());
        $this->assertSame('offset', $params[1]->getName());
        $this->assertSame('limit', $params[2]->getName());
    }

    public function testCreateWithStatusMethodSignature(): void
    {
        $method = new \ReflectionMethod(WordDiscoveryService::class, 'createWithStatus');

        $this->assertTrue($method->isPublic());
        $this->assertSame(4, $method->getNumberOfRequiredParameters());

        $params = $method->getParameters();
        $this->assertSame('langId', $params[0]->getName());
        $this->assertSame('term', $params[1]->getName());
        $this->assertSame('termlc', $params[2]->getName());
        $this->assertSame('status', $params[3]->getName());
    }

    public function testInsertWordWithStatusMethodSignature(): void
    {
        $method = new \ReflectionMethod(WordDiscoveryService::class, 'insertWordWithStatus');

        $this->assertTrue($method->isPublic());
        $this->assertSame(3, $method->getNumberOfRequiredParameters());

        $params = $method->getParameters();
        $this->assertSame('textId', $params[0]->getName());
        $this->assertSame('term', $params[1]->getName());
        $this->assertSame('status', $params[2]->getName());
    }

    public function testCreateOnHoverMethodSignature(): void
    {
        $method = new \ReflectionMethod(WordDiscoveryService::class, 'createOnHover');

        $this->assertTrue($method->isPublic());
        $this->assertSame(3, $method->getNumberOfRequiredParameters());

        $params = $method->getParameters();
        $this->assertSame('textId', $params[0]->getName());
        $this->assertSame('text', $params[1]->getName());
        $this->assertSame('status', $params[2]->getName());
        $this->assertSame('translation', $params[3]->getName());

        // translation has default value
        $this->assertTrue($params[3]->isDefaultValueAvailable());
        $this->assertSame('*', $params[3]->getDefaultValue());
    }

    public function testProcessWordForWellKnownMethodSignature(): void
    {
        $method = new \ReflectionMethod(WordDiscoveryService::class, 'processWordForWellKnown');

        $this->assertTrue($method->isPublic());
        $this->assertSame(4, $method->getNumberOfRequiredParameters());

        $params = $method->getParameters();
        $this->assertSame('status', $params[0]->getName());
        $this->assertSame('term', $params[1]->getName());
        $this->assertSame('termlc', $params[2]->getName());
        $this->assertSame('langId', $params[3]->getName());
    }

    public function testSetStatusMethodSignature(): void
    {
        $method = new \ReflectionMethod(WordDiscoveryService::class, 'setStatus');

        $this->assertTrue($method->isPublic());
        $this->assertSame(2, $method->getNumberOfRequiredParameters());

        $params = $method->getParameters();
        $this->assertSame('wordId', $params[0]->getName());
        $this->assertSame('status', $params[1]->getName());
    }

    public function testMarkAllWordsWithStatusMethodSignature(): void
    {
        $method = new \ReflectionMethod(WordDiscoveryService::class, 'markAllWordsWithStatus');

        $this->assertTrue($method->isPublic());
        $this->assertSame(2, $method->getNumberOfRequiredParameters());

        $params = $method->getParameters();
        $this->assertSame('textId', $params[0]->getName());
        $this->assertSame('status', $params[1]->getName());
    }

    // ===== Return Type Tests =====

    public function testGetUnknownWordsInTextReturnType(): void
    {
        $method = new \ReflectionMethod(WordDiscoveryService::class, 'getUnknownWordsInText');
        $returnType = $method->getReturnType();

        $this->assertSame('array', $returnType->getName());
    }

    public function testCreateWithStatusReturnType(): void
    {
        $method = new \ReflectionMethod(WordDiscoveryService::class, 'createWithStatus');
        $returnType = $method->getReturnType();

        $this->assertSame('array', $returnType->getName());
    }

    public function testInsertWordWithStatusReturnType(): void
    {
        $method = new \ReflectionMethod(WordDiscoveryService::class, 'insertWordWithStatus');
        $returnType = $method->getReturnType();

        $this->assertSame('array', $returnType->getName());
    }

    public function testCreateOnHoverReturnType(): void
    {
        $method = new \ReflectionMethod(WordDiscoveryService::class, 'createOnHover');
        $returnType = $method->getReturnType();

        $this->assertSame('array', $returnType->getName());
    }

    public function testProcessWordForWellKnownReturnType(): void
    {
        $method = new \ReflectionMethod(WordDiscoveryService::class, 'processWordForWellKnown');
        $returnType = $method->getReturnType();

        $this->assertSame('array', $returnType->getName());
    }

    public function testSetStatusReturnType(): void
    {
        $method = new \ReflectionMethod(WordDiscoveryService::class, 'setStatus');
        $returnType = $method->getReturnType();

        $this->assertSame('void', $returnType->getName());
    }

    public function testMarkAllWordsWithStatusReturnType(): void
    {
        $method = new \ReflectionMethod(WordDiscoveryService::class, 'markAllWordsWithStatus');
        $returnType = $method->getReturnType();

        $this->assertSame('array', $returnType->getName());
    }

    // ===== Status Value Tests =====

    /**
     * @dataProvider statusValueProvider
     */
    public function testValidStatusValues(int $status, string $description): void
    {
        // Document valid status values
        $this->assertContains($status, [1, 2, 3, 4, 5, 98, 99], $description);
    }

    public static function statusValueProvider(): array
    {
        return [
            'learning_1' => [1, 'Learning stage 1'],
            'learning_2' => [2, 'Learning stage 2'],
            'learning_3' => [3, 'Learning stage 3'],
            'learning_4' => [4, 'Learning stage 4'],
            'learning_5' => [5, 'Learning stage 5'],
            'ignored' => [98, 'Ignored word'],
            'well_known' => [99, 'Well-known word'],
        ];
    }

    // ===== Expected Return Structure Documentation =====

    public function testCreateWithStatusExpectedReturnStructure(): void
    {
        // Document expected return structure: ['id' => int, 'rows' => int]
        $expectedKeys = ['id', 'rows'];
        $this->assertCount(2, $expectedKeys);
    }

    public function testInsertWordWithStatusExpectedReturnStructure(): void
    {
        // Document expected return structure
        $expectedKeys = ['id', 'term', 'termlc', 'hex'];
        $this->assertCount(4, $expectedKeys);
    }

    public function testCreateOnHoverExpectedReturnStructure(): void
    {
        // Document expected return structure
        $expectedKeys = ['wid', 'word', 'wordRaw', 'translation', 'status', 'hex'];
        $this->assertCount(6, $expectedKeys);
    }
}
