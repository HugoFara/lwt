<?php

declare(strict_types=1);

namespace Lwt\Tests\Services;

require_once __DIR__ . '/../../../src/Shared/Infrastructure/Bootstrap/EnvLoader.php';

use Lwt\Shared\Infrastructure\Bootstrap\EnvLoader;
use Lwt\Shared\Infrastructure\Globals;
use Lwt\Shared\Infrastructure\Database\Configuration;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Modules\Text\Application\TextFacade;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../src/Shared/Infrastructure/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/Modules/Text/Application/TextFacade.php';

/**
 * Unit tests for the TextFacade class.
 *
 * Tests text management (active and archived texts) through the facade layer.
 */
class TextServiceTest extends TestCase
{
    private static bool $dbConnected = false;
    private TextFacade $service;

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
        $this->service = new TextFacade();
    }

    // ===== Constructor tests =====

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(TextFacade::class, $this->service);
    }

    // ===== Method existence tests =====

    public function testServiceHasGetArchivedTextCount(): void
    {
        $this->assertTrue(method_exists($this->service, 'getArchivedTextCount'));
    }

    public function testServiceHasGetArchivedTextsList(): void
    {
        $this->assertTrue(method_exists($this->service, 'getArchivedTextsList'));
    }

    public function testServiceHasGetArchivedTextById(): void
    {
        $this->assertTrue(method_exists($this->service, 'getArchivedTextById'));
    }

    public function testServiceHasDeleteArchivedText(): void
    {
        $this->assertTrue(method_exists($this->service, 'deleteArchivedText'));
    }

    public function testServiceHasDeleteArchivedTexts(): void
    {
        $this->assertTrue(method_exists($this->service, 'deleteArchivedTexts'));
    }

    public function testServiceHasUnarchiveText(): void
    {
        $this->assertTrue(method_exists($this->service, 'unarchiveText'));
    }

    public function testServiceHasUnarchiveTexts(): void
    {
        $this->assertTrue(method_exists($this->service, 'unarchiveTexts'));
    }

    public function testServiceHasUpdateArchivedText(): void
    {
        $this->assertTrue(method_exists($this->service, 'updateArchivedText'));
    }

    public function testServiceHasGetTextById(): void
    {
        $this->assertTrue(method_exists($this->service, 'getTextById'));
    }

    public function testServiceHasDeleteText(): void
    {
        $this->assertTrue(method_exists($this->service, 'deleteText'));
    }

    public function testServiceHasArchiveText(): void
    {
        $this->assertTrue(method_exists($this->service, 'archiveText'));
    }

    public function testServiceHasGetTextCount(): void
    {
        $this->assertTrue(method_exists($this->service, 'getTextCount'));
    }

    public function testServiceHasGetTextsList(): void
    {
        $this->assertTrue(method_exists($this->service, 'getTextsList'));
    }

    public function testServiceHasCreateText(): void
    {
        $this->assertTrue(method_exists($this->service, 'createText'));
    }

    public function testServiceHasUpdateText(): void
    {
        $this->assertTrue(method_exists($this->service, 'updateText'));
    }

    public function testServiceHasDeleteTexts(): void
    {
        $this->assertTrue(method_exists($this->service, 'deleteTexts'));
    }

    public function testServiceHasArchiveTexts(): void
    {
        $this->assertTrue(method_exists($this->service, 'archiveTexts'));
    }

    public function testServiceHasRebuildTexts(): void
    {
        $this->assertTrue(method_exists($this->service, 'rebuildTexts'));
    }

    // ===== Archived text count tests =====

    public function testGetArchivedTextCountReturnsInteger(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getArchivedTextCount('', '', '');
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function testGetArchivedTextCountWithLanguageFilter(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getArchivedTextCount(' AND TxLgID = 1', '', '');
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    // ===== Archived text list tests =====

    public function testGetArchivedTextsListReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getArchivedTextsList('', '', '', 1, 1, 10);
        $this->assertIsArray($result);
    }

    // ===== Get archived text by ID tests =====

    public function testGetArchivedTextByIdReturnsNullForNonexistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getArchivedTextById(999999);
        $this->assertNull($result);
    }

    // ===== Active text count tests =====

    public function testGetTextCountReturnsInteger(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTextCount('', '', '');
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function testGetTextCountWithLanguageFilter(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTextCount(' AND TxLgID = 1', '', '');
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    // ===== Active text list tests =====

    public function testGetTextsListReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTextsList('', '', '', 1, 1, 10);
        $this->assertIsArray($result);
    }

    // ===== Get text by ID tests =====

    public function testGetTextByIdReturnsNullForNonexistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTextById(999999);
        $this->assertNull($result);
    }

    // ===== buildArchivedQueryWhereClause tests =====

    public function testBuildArchivedQueryWhereClauseReturnsEmptyForEmptyQuery(): void
    {
        $result = $this->service->buildArchivedQueryWhereClause('', 'title,text', '');
        $this->assertEquals(['clause' => '', 'params' => []], $result);
    }

    public function testBuildArchivedQueryWhereClauseForTitleAndText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->buildArchivedQueryWhereClause('test', 'title,text', '');
        $this->assertStringContainsString('TxTitle', $result['clause']);
        $this->assertStringContainsString('TxText', $result['clause']);
        $this->assertStringContainsString('OR', $result['clause']);
        $this->assertEquals(['test', 'test'], $result['params']);
    }

    public function testBuildArchivedQueryWhereClauseForTitleOnly(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->buildArchivedQueryWhereClause('test', 'title', '');
        $this->assertStringContainsString('TxTitle', $result['clause']);
        $this->assertStringNotContainsString('TxText', $result['clause']);
        $this->assertEquals(['test'], $result['params']);
    }

    public function testBuildArchivedQueryWhereClauseForTextOnly(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->buildArchivedQueryWhereClause('test', 'text', '');
        $this->assertStringNotContainsString('TxTitle LIKE', $result['clause']);
        $this->assertStringContainsString('TxText', $result['clause']);
        $this->assertEquals(['test'], $result['params']);
    }

    // ===== buildArchivedTagHavingClause tests =====

    public function testBuildArchivedTagHavingClauseReturnsEmptyForNoTags(): void
    {
        $result = $this->service->buildArchivedTagHavingClause('', '', '');
        $this->assertEquals('', $result);
    }

    public function testBuildArchivedTagHavingClauseForSingleTag(): void
    {
        $result = $this->service->buildArchivedTagHavingClause('1', '', '');
        $this->assertStringContainsString('HAVING', $result);
        $this->assertStringContainsString('TtT2ID', $result);
    }

    public function testBuildArchivedTagHavingClauseForUntagged(): void
    {
        $result = $this->service->buildArchivedTagHavingClause('-1', '', '');
        $this->assertStringContainsString('IS NULL', $result);
    }

    public function testBuildArchivedTagHavingClauseForTwoTagsAnd(): void
    {
        $result = $this->service->buildArchivedTagHavingClause('1', '2', '1');
        $this->assertStringContainsString('HAVING', $result);
        $this->assertStringContainsString('AND', $result);
    }

    public function testBuildArchivedTagHavingClauseForTwoTagsOr(): void
    {
        $result = $this->service->buildArchivedTagHavingClause('1', '2', '0');
        $this->assertStringContainsString('HAVING', $result);
        $this->assertStringContainsString('OR', $result);
    }

    // ===== buildTextQueryWhereClause tests =====

    public function testBuildTextQueryWhereClauseReturnsEmptyForEmptyQuery(): void
    {
        $result = $this->service->buildTextQueryWhereClause('', 'title,text', '');
        $this->assertEquals(['clause' => '', 'params' => []], $result);
    }

    public function testBuildTextQueryWhereClauseForTitleAndText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->buildTextQueryWhereClause('test', 'title,text', '');
        $this->assertStringContainsString('TxTitle', $result['clause']);
        $this->assertStringContainsString('TxText', $result['clause']);
        $this->assertStringContainsString('OR', $result['clause']);
        $this->assertEquals(['test', 'test'], $result['params']);
    }

    public function testBuildTextQueryWhereClauseForTitleOnly(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->buildTextQueryWhereClause('test', 'title', '');
        $this->assertStringContainsString('TxTitle', $result['clause']);
        $this->assertStringNotContainsString('TxText', $result['clause']);
        $this->assertEquals(['test'], $result['params']);
    }

    public function testBuildTextQueryWhereClauseForTextOnly(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->buildTextQueryWhereClause('test', 'text', '');
        $this->assertStringNotContainsString('TxTitle LIKE', $result['clause']);
        $this->assertStringContainsString('TxText', $result['clause']);
        $this->assertEquals(['test'], $result['params']);
    }

    // ===== buildTextTagHavingClause tests =====

    public function testBuildTextTagHavingClauseReturnsEmptyForNoTags(): void
    {
        $result = $this->service->buildTextTagHavingClause('', '', '');
        $this->assertEquals('', $result);
    }

    public function testBuildTextTagHavingClauseForSingleTag(): void
    {
        $result = $this->service->buildTextTagHavingClause('1', '', '');
        $this->assertStringContainsString('HAVING', $result);
        $this->assertStringContainsString('TtT2ID', $result);
    }

    public function testBuildTextTagHavingClauseForUntagged(): void
    {
        $result = $this->service->buildTextTagHavingClause('-1', '', '');
        $this->assertStringContainsString('IS NULL', $result);
    }

    public function testBuildTextTagHavingClauseForTwoTagsAnd(): void
    {
        $result = $this->service->buildTextTagHavingClause('1', '2', '1');
        $this->assertStringContainsString('HAVING', $result);
        $this->assertStringContainsString('AND', $result);
    }

    public function testBuildTextTagHavingClauseForTwoTagsOr(): void
    {
        $result = $this->service->buildTextTagHavingClause('1', '2', '0');
        $this->assertStringContainsString('HAVING', $result);
        $this->assertStringContainsString('OR', $result);
    }

    // ===== validateRegexQuery tests =====

    public function testValidateRegexQueryReturnsTrueForEmptyQuery(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->validateRegexQuery('', '');
        $this->assertTrue($result);
    }

    public function testValidateRegexQueryReturnsTrueForEmptyRegexMode(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->validateRegexQuery('test', '');
        $this->assertTrue($result);
    }

    public function testValidateRegexQueryReturnsTrueForValidRegex(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->validateRegexQuery('test.*pattern', 'r');
        $this->assertTrue($result);
    }

    // ===== Pagination tests =====

    public function testGetArchivedTextsPerPageReturnsInteger(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getArchivedTextsPerPage();
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    public function testGetTextsPerPageReturnsInteger(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTextsPerPage();
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    public function testGetPaginationReturnsArray(): void
    {
        $result = $this->service->getPagination(100, 1, 10);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('pages', $result);
        $this->assertArrayHasKey('currentPage', $result);
        $this->assertArrayHasKey('limit', $result);
    }

    public function testGetPaginationCalculatesPages(): void
    {
        $result = $this->service->getPagination(100, 1, 10);
        $this->assertEquals(10, $result['pages']);
    }

    public function testGetPaginationNormalizesPage(): void
    {
        // Page 0 should become page 1
        $result = $this->service->getPagination(100, 0, 10);
        $this->assertEquals(1, $result['currentPage']);

        // Page > max should become max
        $result = $this->service->getPagination(100, 999, 10);
        $this->assertEquals(10, $result['currentPage']);
    }

    public function testGetPaginationGeneratesLimitClause(): void
    {
        $result = $this->service->getPagination(100, 3, 10);
        $this->assertEquals('LIMIT 20,10', $result['limit']);
    }

    public function testGetPaginationReturnsZeroPagesForEmptyCount(): void
    {
        $result = $this->service->getPagination(0, 1, 10);
        $this->assertEquals(0, $result['pages']);
    }

    // ===== Delete multiple tests =====

    public function testDeleteArchivedTextsWithEmptyArrayReturnsMessage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->deleteArchivedTexts([]);
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['count']);
    }

    public function testDeleteTextsWithEmptyArrayReturnsMessage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->deleteTexts([]);
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['count']);
    }

    public function testArchiveTextsWithEmptyArrayReturnsMessage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->archiveTexts([]);
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['count']);
    }

    public function testUnarchiveTextsWithEmptyArrayReturnsMessage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->unarchiveTexts([]);
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['count']);
    }

    public function testRebuildTextsWithEmptyArrayReturnsMessage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->rebuildTexts([]);
        $this->assertEquals(0, $result);
    }

    public function testSetTermSentencesWithEmptyArrayReturnsZero(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->setTermSentences([]);
        $this->assertEquals(0, $result);
    }

    // ===== Text validation tests =====

    public function testValidateTextLengthReturnsTrueForShortText(): void
    {
        $result = $this->service->validateTextLength('short text');
        $this->assertTrue($result);
    }

    public function testValidateTextLengthReturnsFalseForLongText(): void
    {
        // Create a string longer than 65000 bytes
        $longText = str_repeat('a', 70000);
        $result = $this->service->validateTextLength($longText);
        $this->assertFalse($result);
    }

    // ===== Reading text methods tests =====

    public function testGetTextForReadingReturnsNullForNonexistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTextForReading(999999);
        $this->assertNull($result);
    }

    public function testGetTextDataForContentReturnsNullForNonexistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTextDataForContent(999999);
        $this->assertNull($result);
    }

    public function testGetLanguageSettingsForReadingReturnsNullForNonexistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguageSettingsForReading(999999);
        $this->assertNull($result);
    }

    public function testGetTtsVoiceApiReturnsNullForNonexistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTtsVoiceApi(999999);
        $this->assertNull($result);
    }

    public function testGetLanguageIdByNameReturnsNullForNonexistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguageIdByName('Nonexistent Language Name 12345');
        $this->assertNull($result);
    }

    // ===== Language translation URIs tests =====

    public function testGetLanguageTranslateUrisReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguageTranslateUris();
        $this->assertIsArray($result);
    }

    public function testGetLanguageDataForFormReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguageDataForForm();
        $this->assertIsArray($result);
    }

    // ===== Get text for edit tests =====

    public function testGetTextForEditReturnsNullForNonexistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTextForEdit(999999);
        $this->assertNull($result);
    }

    // ===== Unarchive text tests =====

    public function testUnarchiveTextReturnsArrayForNonexistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->unarchiveText(999999);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('textId', $result);
        $this->assertFalse($result['success']);
        $this->assertNull($result['textId']);
    }

    // ===== Multiple service instances test =====

    public function testMultipleServiceInstances(): void
    {
        $service1 = new TextFacade();
        $service2 = new TextFacade();

        $this->assertInstanceOf(TextFacade::class, $service1);
        $this->assertInstanceOf(TextFacade::class, $service2);
        $this->assertNotSame($service1, $service2);
    }
}
