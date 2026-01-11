<?php

declare(strict_types=1);

namespace Lwt\Tests\Core\Repository;

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Modules\Text\Domain\Text;
use Lwt\Modules\Language\Domain\ValueObject\LanguageId;
use Lwt\Modules\Text\Domain\ValueObject\TextId;
use Lwt\Modules\Text\Infrastructure\MySqlTextRepository;
use Lwt\Core\Bootstrap\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Shared\Infrastructure\Database\Configuration;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../../src/Modules/Text/Domain/Text.php';
require_once __DIR__ . '/../../../../src/Shared/Infrastructure/Database/PreparedStatement.php';
require_once __DIR__ . '/../../../../src/backend/Core/Repository/RepositoryInterface.php';
require_once __DIR__ . '/../../../../src/backend/Core/Repository/AbstractRepository.php';
require_once __DIR__ . '/../../../../src/Modules/Text/Infrastructure/MySqlTextRepository.php';

/**
 * Tests for the MySqlTextRepository class.
 *
 * @covers \Lwt\Modules\Text\Infrastructure\MySqlTextRepository
 * @covers \Lwt\Core\Repository\AbstractRepository
 */
class TextRepositoryTest extends TestCase
{
    private static bool $dbConnected = false;
    private MySqlTextRepository $repository;
    private static int $testLanguageId = 0;
    private static array $testTextIds = [];

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
            // Create a test language
            $prefix = '';
            Connection::query(
                "INSERT INTO {$prefix}languages (
                    LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI,
                    LgTextSize, LgRegexpSplitSentences, LgRegexpWordCharacters,
                    LgRemoveSpaces, LgSplitEachChar, LgRightToLeft, LgShowRomanization
                ) VALUES (
                    'TextRepoTest_Language', 'https://dict.test/lwt_term', '', '',
                    100, '.!?', 'a-zA-Z',
                    0, 0, 0, 1
                )"
            );
            self::$testLanguageId = (int) mysqli_insert_id(Globals::getDbConnection());
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        $prefix = '';
        // Clean up test texts
        Connection::query("DELETE FROM {$prefix}texts WHERE TxTitle LIKE 'TextRepoTest_%'");
        // Clean up test language
        if (self::$testLanguageId > 0) {
            Connection::query("DELETE FROM {$prefix}languages WHERE LgID = " . self::$testLanguageId);
        }
    }

    protected function setUp(): void
    {
        $this->repository = new MySqlTextRepository();
    }

    protected function tearDown(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        // Clean up texts created during this test
        $prefix = '';
        Connection::query("DELETE FROM {$prefix}texts WHERE TxTitle LIKE 'TextRepoTest_%'");
        self::$testTextIds = [];
    }

    /**
     * Helper to create a test text directly in DB.
     */
    private function createTestTextInDb(
        string $title,
        string $content = 'Test content.',
        string $audioUri = '',
        string $sourceUri = ''
    ): int {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $prefix = '';
        $escapedTitle = mysqli_real_escape_string(Globals::getDbConnection(), $title);
        $escapedContent = mysqli_real_escape_string(Globals::getDbConnection(), $content);
        $escapedAudioUri = mysqli_real_escape_string(Globals::getDbConnection(), $audioUri);
        $escapedSourceUri = mysqli_real_escape_string(Globals::getDbConnection(), $sourceUri);

        Connection::query(
            "INSERT INTO {$prefix}texts (
                TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI, TxPosition, TxAudioPosition
            ) VALUES (
                " . self::$testLanguageId . ", '$escapedTitle', '$escapedContent', '', '$escapedAudioUri', '$escapedSourceUri', 0, 0
            )"
        );
        $id = (int) mysqli_insert_id(Globals::getDbConnection());
        self::$testTextIds[] = $id;
        return $id;
    }

    // ===== find() tests =====

    public function testFindReturnsText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestTextInDb('TextRepoTest_Find', 'Some test content.');

        $result = $this->repository->find($id);

        $this->assertInstanceOf(Text::class, $result);
        $this->assertEquals($id, $result->id()->toInt());
        $this->assertEquals('TextRepoTest_Find', $result->title());
        $this->assertEquals('Some test content.', $result->text());
    }

    public function testFindReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->repository->find(999999);

        $this->assertNull($result);
    }

    // ===== save() tests =====

    public function testSaveInsertsNewEntity(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $text = Text::create(
            LanguageId::fromInt(self::$testLanguageId),
            'TextRepoTest_Insert',
            'Content for insert test.'
        );

        $id = $this->repository->save($text);

        $this->assertGreaterThan(0, $id);
        $this->assertEquals($id, $text->id()->toInt());

        // Verify in database
        $found = $this->repository->find($id);
        $this->assertNotNull($found);
        $this->assertEquals('TextRepoTest_Insert', $found->title());

        self::$testTextIds[] = $id;
    }

    public function testSaveUpdatesExistingEntity(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestTextInDb('TextRepoTest_Update');
        $text = $this->repository->find($id);

        $text->rename('TextRepoTest_Updated');
        $this->repository->save($text);

        $updated = $this->repository->find($id);
        $this->assertEquals('TextRepoTest_Updated', $updated->title());
    }

    // ===== delete() tests =====

    public function testDeleteById(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestTextInDb('TextRepoTest_DeleteById');

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

    // ===== exists() tests =====

    public function testExists(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestTextInDb('TextRepoTest_Exists');

        $this->assertTrue($this->repository->exists($id));
        $this->assertFalse($this->repository->exists(999999));
    }

    // ===== count() tests =====

    public function testCountWithCriteria(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestTextInDb('TextRepoTest_Count1');
        $this->createTestTextInDb('TextRepoTest_Count2');

        $count = $this->repository->count(['TxLgID' => self::$testLanguageId]);

        $this->assertGreaterThanOrEqual(2, $count);
    }

    // ===== findByLanguage() tests =====

    public function testFindByLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestTextInDb('TextRepoTest_Lang1');
        $this->createTestTextInDb('TextRepoTest_Lang2');

        $texts = $this->repository->findByLanguage(self::$testLanguageId);

        $this->assertIsArray($texts);
        $this->assertGreaterThanOrEqual(2, count($texts));
        $this->assertContainsOnlyInstancesOf(Text::class, $texts);
    }

    // ===== findByTitle() tests =====

    public function testFindByTitle(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestTextInDb('TextRepoTest_UniqueTitle');

        $found = $this->repository->findByTitle(self::$testLanguageId, 'TextRepoTest_UniqueTitle');

        $this->assertNotNull($found);
        $this->assertEquals('TextRepoTest_UniqueTitle', $found->title());
    }

    public function testFindByTitleNotFound(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $found = $this->repository->findByTitle(self::$testLanguageId, 'NonExistentTitle12345');

        $this->assertNull($found);
    }

    // ===== titleExists() tests =====

    public function testTitleExists(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestTextInDb('TextRepoTest_TitleExists');

        $this->assertTrue(
            $this->repository->titleExists(self::$testLanguageId, 'TextRepoTest_TitleExists')
        );
        $this->assertFalse(
            $this->repository->titleExists(self::$testLanguageId, 'NonExistentTitle')
        );
    }

    public function testTitleExistsWithExclude(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestTextInDb('TextRepoTest_TitleExclude');

        // Should not find if excluding the same text
        $this->assertFalse(
            $this->repository->titleExists(
                self::$testLanguageId,
                'TextRepoTest_TitleExclude',
                $id
            )
        );
    }

    // ===== countByLanguage() tests =====

    public function testCountByLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $initialCount = $this->repository->countByLanguage(self::$testLanguageId);

        $this->createTestTextInDb('TextRepoTest_CountLang1');
        $this->createTestTextInDb('TextRepoTest_CountLang2');

        $newCount = $this->repository->countByLanguage(self::$testLanguageId);

        $this->assertEquals($initialCount + 2, $newCount);
    }

    // ===== getForSelect() tests =====

    public function testGetForSelect(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestTextInDb('TextRepoTest_Select1');

        $options = $this->repository->getForSelect(self::$testLanguageId);

        $this->assertIsArray($options);
        $this->assertNotEmpty($options);

        $firstOption = $options[0];
        $this->assertArrayHasKey('id', $firstOption);
        $this->assertArrayHasKey('title', $firstOption);
        $this->assertArrayHasKey('language_id', $firstOption);
    }

    // ===== findWithMedia() tests =====

    public function testFindWithMedia(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create text without media
        $this->createTestTextInDb('TextRepoTest_NoMedia');

        // Create text with media
        $idWithMedia = $this->createTestTextInDb(
            'TextRepoTest_WithMedia',
            'Content',
            'http://example.com/audio.mp3'
        );

        $textsWithMedia = $this->repository->findWithMedia(self::$testLanguageId);

        $this->assertContainsOnlyInstancesOf(Text::class, $textsWithMedia);

        $ids = array_map(fn(Text $t) => $t->id()->toInt(), $textsWithMedia);
        $this->assertContains($idWithMedia, $ids);
    }

    // ===== updatePosition() tests =====

    public function testUpdatePosition(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestTextInDb('TextRepoTest_Position');

        $this->assertTrue($this->repository->updatePosition($id, 50));

        $found = $this->repository->find($id);
        $this->assertEquals(50, $found->position());
    }

    public function testUpdateAudioPosition(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestTextInDb('TextRepoTest_AudioPos');

        $this->assertTrue($this->repository->updateAudioPosition($id, 125.5));

        $found = $this->repository->find($id);
        $this->assertEquals(125.5, $found->audioPosition());
    }

    public function testResetProgress(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestTextInDb('TextRepoTest_ResetProg');
        $this->repository->updatePosition($id, 100);
        $this->repository->updateAudioPosition($id, 200.0);

        $this->assertTrue($this->repository->resetProgress($id));

        $found = $this->repository->find($id);
        $this->assertEquals(0, $found->position());
        $this->assertEquals(0.0, $found->audioPosition());
    }

    // ===== getBasicInfo() tests =====

    public function testGetBasicInfo(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestTextInDb('TextRepoTest_BasicInfo');

        $info = $this->repository->getBasicInfo($id);

        $this->assertNotNull($info);
        $this->assertEquals($id, $info['id']);
        $this->assertEquals('TextRepoTest_BasicInfo', $info['title']);
        $this->assertEquals(self::$testLanguageId, $info['language_id']);
        $this->assertFalse($info['has_media']);
        $this->assertFalse($info['has_annotation']);
    }

    public function testGetBasicInfoNotFound(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $info = $this->repository->getBasicInfo(999999);

        $this->assertNull($info);
    }

    // ===== navigation tests =====

    public function testGetPreviousAndNextTextId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id1 = $this->createTestTextInDb('TextRepoTest_Nav1');
        $id2 = $this->createTestTextInDb('TextRepoTest_Nav2');
        $id3 = $this->createTestTextInDb('TextRepoTest_Nav3');

        $prevOf3 = $this->repository->getPreviousTextId($id3, self::$testLanguageId);
        $nextOf1 = $this->repository->getNextTextId($id1, self::$testLanguageId);

        $this->assertEquals($id2, $prevOf3);
        $this->assertEquals($id2, $nextOf1);
    }

    // ===== findPaginated() tests =====

    public function testFindPaginated(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        for ($i = 1; $i <= 5; $i++) {
            $this->createTestTextInDb("TextRepoTest_Paginated$i");
        }

        $result = $this->repository->findPaginated(
            self::$testLanguageId,
            1,
            3,
            'TxTitle',
            'ASC'
        );

        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('per_page', $result);
        $this->assertArrayHasKey('total_pages', $result);

        $this->assertLessThanOrEqual(3, count($result['items']));
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(3, $result['per_page']);
    }

    // ===== searchByTitle() tests =====

    public function testSearchByTitle(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestTextInDb('TextRepoTest_SearchAlpha');
        $this->createTestTextInDb('TextRepoTest_SearchBeta');
        $this->createTestTextInDb('TextRepoTest_Different');

        $results = $this->repository->searchByTitle('TextRepoTest_Search', self::$testLanguageId);

        $this->assertGreaterThanOrEqual(2, count($results));
        foreach ($results as $text) {
            $this->assertStringContainsString('Search', $text->title());
        }
    }

    // ===== getStatistics() tests =====

    public function testGetStatistics(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestTextInDb('TextRepoTest_Stats1');
        $this->createTestTextInDb('TextRepoTest_Stats2', 'Content', 'http://example.com/audio.mp3');

        $stats = $this->repository->getStatistics(self::$testLanguageId);

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('with_media', $stats);
        $this->assertArrayHasKey('annotated', $stats);
        $this->assertArrayHasKey('with_source', $stats);

        $this->assertGreaterThanOrEqual(2, $stats['total']);
        $this->assertGreaterThanOrEqual(1, $stats['with_media']);
    }

    // ===== getLanguagesWithTexts() tests =====

    public function testGetLanguagesWithTexts(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestTextInDb('TextRepoTest_LangCheck');

        $languageIds = $this->repository->getLanguagesWithTexts();

        $this->assertContains(self::$testLanguageId, $languageIds);
    }
}
