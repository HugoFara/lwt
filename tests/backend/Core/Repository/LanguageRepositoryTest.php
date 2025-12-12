<?php declare(strict_types=1);
namespace Lwt\Tests\Core\Repository;

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\Entity\Language;
use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Core\Repository\LanguageRepository;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../../src/backend/Core/Entity/ValueObject/LanguageId.php';
require_once __DIR__ . '/../../../../src/backend/Core/Entity/Language.php';
require_once __DIR__ . '/../../../../src/backend/Core/Database/PreparedStatement.php';
require_once __DIR__ . '/../../../../src/backend/Core/Repository/RepositoryInterface.php';
require_once __DIR__ . '/../../../../src/backend/Core/Repository/AbstractRepository.php';
require_once __DIR__ . '/../../../../src/backend/Core/Repository/LanguageRepository.php';

/**
 * Unit tests for the LanguageRepository class.
 */
class LanguageRepositoryTest extends TestCase
{
    private static bool $dbConnected = false;
    private LanguageRepository $repository;
    private static array $testLanguageIds = [];

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
        $this->repository = new LanguageRepository();
    }

    protected function tearDown(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        // Clean up test languages after each test
        $prefix = Globals::getTablePrefix();
        Connection::query("DELETE FROM {$prefix}languages WHERE LgName LIKE 'RepoTest_%'");
        self::$testLanguageIds = [];
    }

    /**
     * Helper to create a test language entity.
     */
    private function createTestLanguageEntity(string $name): Language
    {
        $language = Language::create(
            $name,
            'https://dict.test/lwt_term',
            '.!?',
            'a-zA-Z'
        );
        return $language;
    }

    /**
     * Helper to create a test language directly in DB.
     */
    private function createTestLanguageInDb(string $name): int
    {
        $prefix = Globals::getTablePrefix();
        Connection::query(
            "INSERT INTO {$prefix}languages (
                LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI,
                LgTextSize, LgRegexpSplitSentences, LgRegexpWordCharacters,
                LgRemoveSpaces, LgSplitEachChar, LgRightToLeft, LgShowRomanization
            ) VALUES (
                '$name', 'https://dict.test/lwt_term', '', '',
                100, '.!?', 'a-zA-Z',
                0, 0, 0, 1
            )"
        );
        $id = (int) mysqli_insert_id(Globals::getDbConnection());
        self::$testLanguageIds[] = $id;
        return $id;
    }

    // ===== find() tests =====

    public function testFindReturnsLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguageInDb('RepoTest_Find');

        $result = $this->repository->find($id);

        $this->assertInstanceOf(Language::class, $result);
        $this->assertEquals($id, $result->id()->toInt());
        $this->assertEquals('RepoTest_Find', $result->name());
    }

    public function testFindReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->repository->find(999999);

        $this->assertNull($result);
    }

    // ===== findAll() tests =====

    public function testFindAllReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->repository->findAll();

        $this->assertIsArray($result);
    }

    public function testFindAllReturnsLanguages(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestLanguageInDb('RepoTest_FindAll1');
        $this->createTestLanguageInDb('RepoTest_FindAll2');

        $result = $this->repository->findAll();

        $names = array_map(fn($lang) => $lang->name(), $result);
        $this->assertContains('RepoTest_FindAll1', $names);
        $this->assertContains('RepoTest_FindAll2', $names);
    }

    // ===== findBy() tests =====

    public function testFindByWithSingleCriteria(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguageInDb('RepoTest_FindBy');

        $result = $this->repository->findBy(['name' => 'RepoTest_FindBy']);

        $this->assertCount(1, $result);
        $this->assertEquals($id, $result[0]->id()->toInt());
    }

    public function testFindByWithLimit(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestLanguageInDb('RepoTest_Limit1');
        $this->createTestLanguageInDb('RepoTest_Limit2');
        $this->createTestLanguageInDb('RepoTest_Limit3');

        $result = $this->repository->findBy([], null, 2);

        $this->assertLessThanOrEqual(2, count($result));
    }

    public function testFindByWithOrderBy(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestLanguageInDb('RepoTest_OrderA');
        $this->createTestLanguageInDb('RepoTest_OrderZ');

        $result = $this->repository->findBy(
            [],
            ['name' => 'DESC'],
            10
        );

        // Find positions in result
        $names = array_map(fn($l) => $l->name(), $result);
        $posA = array_search('RepoTest_OrderA', $names);
        $posZ = array_search('RepoTest_OrderZ', $names);

        // Z should come before A in descending order
        $this->assertNotFalse($posA);
        $this->assertNotFalse($posZ);
        $this->assertLessThan($posA, $posZ);
    }

    // ===== findOneBy() tests =====

    public function testFindOneByReturnsSingleEntity(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguageInDb('RepoTest_FindOneBy');

        $result = $this->repository->findOneBy(['name' => 'RepoTest_FindOneBy']);

        $this->assertInstanceOf(Language::class, $result);
        $this->assertEquals($id, $result->id()->toInt());
    }

    public function testFindOneByReturnsNullWhenNoMatch(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->repository->findOneBy(['name' => 'NonExistent12345']);

        $this->assertNull($result);
    }

    // ===== save() tests =====

    public function testSaveInsertsNewEntity(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $language = $this->createTestLanguageEntity('RepoTest_Insert');

        $id = $this->repository->save($language);

        $this->assertGreaterThan(0, $id);
        $this->assertEquals($id, $language->id()->toInt());

        // Verify in database
        $found = $this->repository->find($id);
        $this->assertNotNull($found);
        $this->assertEquals('RepoTest_Insert', $found->name());

        self::$testLanguageIds[] = $id;
    }

    public function testSaveUpdatesExistingEntity(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguageInDb('RepoTest_Update');
        $language = $this->repository->find($id);

        $language->rename('RepoTest_Updated');
        $language->setTextSize(150);

        $this->repository->save($language);

        $updated = $this->repository->find($id);
        $this->assertEquals('RepoTest_Updated', $updated->name());
        $this->assertEquals(150, $updated->textSize());
    }

    // ===== delete() tests =====

    public function testDeleteByEntity(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguageInDb('RepoTest_DeleteEntity');
        $language = $this->repository->find($id);

        $result = $this->repository->delete($language);

        $this->assertTrue($result);
        $this->assertNull($this->repository->find($id));
    }

    public function testDeleteById(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguageInDb('RepoTest_DeleteById');

        $result = $this->repository->delete($id);

        $this->assertTrue($result);
        $this->assertNull($this->repository->find($id));
    }

    public function testDeleteReturnsFalseForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->repository->delete(999999);

        $this->assertFalse($result);
    }

    // ===== count() tests =====

    public function testCountAllEntities(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $initialCount = $this->repository->count();

        $this->createTestLanguageInDb('RepoTest_Count1');
        $this->createTestLanguageInDb('RepoTest_Count2');

        $newCount = $this->repository->count();

        $this->assertEquals($initialCount + 2, $newCount);
    }

    public function testCountWithCriteria(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestLanguageInDb('RepoTest_CountCrit');

        $count = $this->repository->count(['name' => 'RepoTest_CountCrit']);

        $this->assertEquals(1, $count);
    }

    // ===== exists() tests =====

    public function testExistsReturnsTrueForExisting(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguageInDb('RepoTest_Exists');

        $this->assertTrue($this->repository->exists($id));
    }

    public function testExistsReturnsFalseForNonExisting(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->assertFalse($this->repository->exists(999999));
    }

    // ===== Custom repository methods =====

    public function testFindAllActiveExcludesEmptyNames(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $prefix = Globals::getTablePrefix();
        // Try to insert language with empty name (may already exist)
        try {
            Connection::query(
                "INSERT INTO {$prefix}languages (LgName, LgDict1URI, LgTextSize, LgRegexpSplitSentences, LgRegexpWordCharacters)
                 VALUES ('', 'https://test.com', 100, '.!?', 'a-z')"
            );
        } catch (\RuntimeException $e) {
            // Empty language may already exist, that's fine
        }

        $this->createTestLanguageInDb('RepoTest_Active');

        $result = $this->repository->findAllActive();

        $names = array_map(fn($l) => $l->name(), $result);
        $this->assertNotContains('', $names);
        $this->assertContains('RepoTest_Active', $names);
    }

    public function testFindByName(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguageInDb('RepoTest_ByName');

        $result = $this->repository->findByName('RepoTest_ByName');

        $this->assertInstanceOf(Language::class, $result);
        $this->assertEquals($id, $result->id()->toInt());
    }

    public function testNameExists(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguageInDb('RepoTest_NameExists');

        $this->assertTrue($this->repository->nameExists('RepoTest_NameExists'));
        $this->assertFalse($this->repository->nameExists('NonExistent12345'));
    }

    public function testNameExistsExcludesId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguageInDb('RepoTest_NameExclude');

        // Should not find duplicate when excluding its own ID
        $this->assertFalse($this->repository->nameExists('RepoTest_NameExclude', $id));
        // Should find duplicate without exclusion
        $this->assertTrue($this->repository->nameExists('RepoTest_NameExclude', null));
    }

    public function testGetAllAsDict(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestLanguageInDb('RepoTest_Dict');

        $result = $this->repository->getAllAsDict();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('RepoTest_Dict', $result);
        $this->assertIsInt($result['RepoTest_Dict']);
    }

    public function testGetForSelect(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestLanguageInDb('RepoTest_Select');

        $result = $this->repository->getForSelect();

        $this->assertIsArray($result);

        // Find our test language
        $found = false;
        foreach ($result as $item) {
            if ($item['name'] === 'RepoTest_Select') {
                $found = true;
                $this->assertArrayHasKey('id', $item);
                $this->assertArrayHasKey('name', $item);
                break;
            }
        }
        $this->assertTrue($found, 'Test language should be in results');
    }

    public function testGetForSelectTruncatesLongNames(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $longName = 'RepoTest_' . str_repeat('A', 50);
        $this->createTestLanguageInDb($longName);

        $result = $this->repository->getForSelect(30);

        foreach ($result as $item) {
            if (str_starts_with($item['name'], 'RepoTest_AA')) {
                $this->assertLessThanOrEqual(33, strlen($item['name'])); // 30 + '...'
                $this->assertStringEndsWith('...', $item['name']);
                return;
            }
        }
        $this->fail('Long name language not found in results');
    }

    public function testCreateEmpty(): void
    {
        $result = $this->repository->createEmpty();

        $this->assertInstanceOf(Language::class, $result);
        $this->assertTrue($result->id()->isNew());
        $this->assertEquals('New Language', $result->name());
        $this->assertEquals(100, $result->textSize());
        $this->assertTrue($result->showRomanization());
    }

    public function testIsRightToLeft(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $prefix = Globals::getTablePrefix();
        // Create RTL language
        Connection::query(
            "INSERT INTO {$prefix}languages (
                LgName, LgDict1URI, LgTextSize, LgRegexpSplitSentences,
                LgRegexpWordCharacters, LgRightToLeft
            ) VALUES (
                'RepoTest_RTL', 'https://dict.test', 100, '.!?', 'a-z', 1
            )"
        );
        $rtlId = (int) mysqli_insert_id(Globals::getDbConnection());
        self::$testLanguageIds[] = $rtlId;

        // Create LTR language
        $ltrId = $this->createTestLanguageInDb('RepoTest_LTR');

        $this->assertTrue($this->repository->isRightToLeft($rtlId));
        $this->assertFalse($this->repository->isRightToLeft($ltrId));
    }

    public function testGetWordCharacters(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestLanguageInDb('RepoTest_WordChars');

        $result = $this->repository->getWordCharacters($id);

        $this->assertEquals('a-zA-Z', $result);
    }
}
