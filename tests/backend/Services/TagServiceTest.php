<?php declare(strict_types=1);
namespace Lwt\Tests\Services;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Services\TagService;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Services/TagService.php';

/**
 * Unit tests for the TagService class.
 *
 * Tests tag management (both term tags and text tags) through the service layer.
 */
class TagServiceTest extends TestCase
{
    private static bool $dbConnected = false;
    private TagService $termTagService;
    private TagService $textTagService;

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
        $this->termTagService = new TagService('term');
        $this->textTagService = new TagService('text');
    }

    // ===== Constructor tests =====

    public function testConstructorDefaultsToTermTags(): void
    {
        $service = new TagService();
        $this->assertEquals('Term', $service->getTagTypeLabel());
        $this->assertEquals('/tags', $service->getBaseUrl());
    }

    public function testConstructorAcceptsTermType(): void
    {
        $service = new TagService('term');
        $this->assertEquals('Term', $service->getTagTypeLabel());
        $this->assertEquals('/tags', $service->getBaseUrl());
    }

    public function testConstructorAcceptsTextType(): void
    {
        $service = new TagService('text');
        $this->assertEquals('Text', $service->getTagTypeLabel());
        $this->assertEquals('/tags/text', $service->getBaseUrl());
    }

    // ===== buildWhereClause() tests =====

    public function testBuildWhereClauseReturnsEmptyForEmptyQuery(): void
    {
        $result = $this->termTagService->buildWhereClause('');
        $this->assertEquals('', $result);
    }

    public function testBuildWhereClauseReturnsClauseForQuery(): void
    {
        $result = $this->termTagService->buildWhereClause('test');
        $this->assertStringContainsString('TgText like', $result);
        $this->assertStringContainsString('TgComment like', $result);
    }

    public function testBuildWhereClauseForTextTags(): void
    {
        $result = $this->textTagService->buildWhereClause('test');
        $this->assertStringContainsString('T2Text like', $result);
        $this->assertStringContainsString('T2Comment like', $result);
    }

    public function testBuildWhereClauseReplacesWildcard(): void
    {
        $result = $this->termTagService->buildWhereClause('test*');
        $this->assertStringContainsString('like', $result);
    }

    // ===== getCount() tests =====

    public function testGetCountReturnsInteger(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->termTagService->getCount();
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function testGetCountWithWhereClause(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $whereClause = $this->termTagService->buildWhereClause('nonexistent_tag_xyz');
        $result = $this->termTagService->getCount($whereClause);
        $this->assertIsInt($result);
    }

    // ===== getPagination() tests =====

    public function testGetPaginationReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->termTagService->getPagination(100, 1);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('pages', $result);
        $this->assertArrayHasKey('currentPage', $result);
        $this->assertArrayHasKey('perPage', $result);
    }

    public function testGetPaginationCalculatesPages(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $perPage = $this->termTagService->getMaxPerPage();
        $totalCount = $perPage * 3 + 5; // 3 full pages + partial

        $result = $this->termTagService->getPagination($totalCount, 1);
        $this->assertEquals(4, $result['pages']);
    }

    public function testGetPaginationNormalizesCurrentPage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Page 0 should become page 1
        $result = $this->termTagService->getPagination(100, 0);
        $this->assertEquals(1, $result['currentPage']);

        // Page beyond max should become max page
        $result = $this->termTagService->getPagination(10, 999);
        $this->assertLessThanOrEqual($result['pages'], $result['currentPage']);
    }

    public function testGetPaginationReturnsZeroPagesForEmptyCount(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->termTagService->getPagination(0, 1);
        $this->assertEquals(0, $result['pages']);
    }

    // ===== getMaxPerPage() tests =====

    public function testGetMaxPerPageReturnsInteger(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->termTagService->getMaxPerPage();
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    // ===== getSortOptions() tests =====

    public function testGetSortOptionsReturnsArray(): void
    {
        $result = $this->termTagService->getSortOptions();
        $this->assertIsArray($result);
        $this->assertCount(4, $result);
    }

    public function testGetSortOptionsHasCorrectStructure(): void
    {
        $result = $this->termTagService->getSortOptions();
        foreach ($result as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('text', $option);
        }
    }

    // ===== getSortColumn() tests =====

    public function testGetSortColumnReturnsCorrectColumn(): void
    {
        $this->assertEquals('Text', $this->termTagService->getSortColumn(1));
        $this->assertEquals('Comment', $this->termTagService->getSortColumn(2));
        $this->assertEquals('ID desc', $this->termTagService->getSortColumn(3));
        $this->assertEquals('ID asc', $this->termTagService->getSortColumn(4));
    }

    public function testGetSortColumnNormalizesOutOfRange(): void
    {
        // Index 0 should become index 1
        $this->assertEquals('Text', $this->termTagService->getSortColumn(0));

        // Index > 4 should become index 4
        $this->assertEquals('ID asc', $this->termTagService->getSortColumn(99));
    }

    // ===== getList() tests =====

    public function testGetListReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->termTagService->getList();
        $this->assertIsArray($result);
    }

    public function testGetListReturnsTagsWithCorrectStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->termTagService->getList();
        if (!empty($result)) {
            $tag = $result[0];
            $this->assertArrayHasKey('id', $tag);
            $this->assertArrayHasKey('text', $tag);
            $this->assertArrayHasKey('comment', $tag);
            $this->assertArrayHasKey('usageCount', $tag);
        }
        $this->assertTrue(true); // Pass if no tags exist
    }

    public function testGetListForTextTagsIncludesArchivedCount(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->textTagService->getList();
        if (!empty($result)) {
            $tag = $result[0];
            $this->assertArrayHasKey('archivedUsageCount', $tag);
        }
        $this->assertTrue(true); // Pass if no tags exist
    }

    // ===== getById() tests =====

    public function testGetByIdReturnsNullForNonexistentId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->termTagService->getById(999999);
        $this->assertNull($result);
    }

    // ===== getUsageCount() tests =====

    public function testGetUsageCountReturnsInteger(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->termTagService->getUsageCount(1);
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    // ===== getArchivedUsageCount() tests =====

    public function testGetArchivedUsageCountReturnsZeroForTermTags(): void
    {
        $result = $this->termTagService->getArchivedUsageCount(1);
        $this->assertEquals(0, $result);
    }

    public function testGetArchivedUsageCountReturnsIntegerForTextTags(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->textTagService->getArchivedUsageCount(1);
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    // ===== getTagTypeLabel() tests =====

    public function testGetTagTypeLabelReturnsTermForTermTags(): void
    {
        $this->assertEquals('Term', $this->termTagService->getTagTypeLabel());
    }

    public function testGetTagTypeLabelReturnsTextForTextTags(): void
    {
        $this->assertEquals('Text', $this->textTagService->getTagTypeLabel());
    }

    // ===== getBaseUrl() tests =====

    public function testGetBaseUrlReturnsCorrectPathForTermTags(): void
    {
        $this->assertEquals('/tags', $this->termTagService->getBaseUrl());
    }

    public function testGetBaseUrlReturnsCorrectPathForTextTags(): void
    {
        $this->assertEquals('/tags/text', $this->textTagService->getBaseUrl());
    }

    // ===== getItemsUrl() tests =====

    public function testGetItemsUrlReturnsWordsUrlForTermTags(): void
    {
        $result = $this->termTagService->getItemsUrl(123);
        $this->assertStringContainsString('/words', $result);
        $this->assertStringContainsString('tag1=123', $result);
    }

    public function testGetItemsUrlReturnsTextsUrlForTextTags(): void
    {
        $result = $this->textTagService->getItemsUrl(456);
        $this->assertStringContainsString('/texts', $result);
        $this->assertStringContainsString('tag1=456', $result);
    }

    // ===== getArchivedItemsUrl() tests =====

    public function testGetArchivedItemsUrlReturnsArchivedTextsUrl(): void
    {
        $result = $this->textTagService->getArchivedItemsUrl(789);
        $this->assertStringContainsString('edit_archivedtexts.php', $result);
        $this->assertStringContainsString('tag1=789', $result);
    }

    // ===== formatDuplicateError() tests =====

    public function testFormatDuplicateErrorReturnsOriginalForNonDuplicate(): void
    {
        $message = "Some other error";
        $result = $this->termTagService->formatDuplicateError($message);
        $this->assertEquals($message, $result);
    }

    public function testFormatDuplicateErrorFormatsTermTagDuplicate(): void
    {
        $message = "Error: Duplicate entry 'mytag' for key 'TgText'";
        $result = $this->termTagService->formatDuplicateError($message);
        $this->assertStringContainsString('Term Tag', $result);
        $this->assertStringContainsString('mytag', $result);
        $this->assertStringContainsString('already exists', $result);
    }

    public function testFormatDuplicateErrorFormatsTextTagDuplicate(): void
    {
        $message = "Error: Duplicate entry 'mytexttag' for key 'T2Text'";
        $result = $this->textTagService->formatDuplicateError($message);
        $this->assertStringContainsString('Text Tag', $result);
        $this->assertStringContainsString('mytexttag', $result);
        $this->assertStringContainsString('already exists', $result);
    }

    // ===== deleteMultiple() tests =====

    public function testDeleteMultipleWithEmptyArrayReturnsMessage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->termTagService->deleteMultiple([]);
        $this->assertEquals("Multiple Actions: 0", $result);
    }

    // ===== CRUD operation tests (integration) =====

    public function testCreateMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->termTagService, 'create'),
            'create method should exist'
        );
    }

    public function testUpdateMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->termTagService, 'update'),
            'update method should exist'
        );
    }

    public function testDeleteMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->termTagService, 'delete'),
            'delete method should exist'
        );
    }

    public function testDeleteAllMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->termTagService, 'deleteAll'),
            'deleteAll method should exist'
        );
    }
}
