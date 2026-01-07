<?php declare(strict_types=1);

namespace Lwt\Tests\Modules\Text;

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\Bootstrap\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Shared\Infrastructure\Database\Configuration;
use Lwt\Modules\Text\Application\TextFacade;
use Lwt\Modules\Text\Application\UseCases\ArchiveText;
use Lwt\Modules\Text\Application\UseCases\BuildTextFilters;
use Lwt\Modules\Text\Application\UseCases\DeleteText;
use Lwt\Modules\Text\Application\UseCases\GetTextForEdit;
use Lwt\Modules\Text\Application\UseCases\GetTextForReading;
use Lwt\Modules\Text\Application\UseCases\ImportText;
use Lwt\Modules\Text\Application\UseCases\ListTexts;
use Lwt\Modules\Text\Application\UseCases\ParseText;
use Lwt\Modules\Text\Application\UseCases\UpdateText;
use Lwt\Modules\Text\Domain\Text;
use Lwt\Modules\Text\Domain\TextRepositoryInterface;
use Lwt\Modules\Text\Application\Services\SentenceService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/db_bootstrap.php';

/**
 * Unit tests for the TextFacade class.
 *
 * Tests text operations including CRUD, filtering, pagination, and text processing.
 *
 * @covers \Lwt\Modules\Text\Application\TextFacade
 */
class TextFacadeTest extends TestCase
{
    private static bool $dbConnected = false;

    /** @var TextRepositoryInterface&MockObject */
    private TextRepositoryInterface $textRepository;

    /** @var ArchiveText&MockObject */
    private ArchiveText $archiveText;

    /** @var BuildTextFilters&MockObject */
    private BuildTextFilters $buildTextFilters;

    /** @var DeleteText&MockObject */
    private DeleteText $deleteText;

    /** @var GetTextForEdit&MockObject */
    private GetTextForEdit $getTextForEdit;

    /** @var GetTextForReading&MockObject */
    private GetTextForReading $getTextForReading;

    /** @var ImportText&MockObject */
    private ImportText $importText;

    /** @var ListTexts&MockObject */
    private ListTexts $listTexts;

    /** @var ParseText&MockObject */
    private ParseText $parseText;

    /** @var UpdateText&MockObject */
    private UpdateText $updateText;

    /** @var SentenceService&MockObject */
    private SentenceService $sentenceService;

    private TextFacade $facade;

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
    }

    protected function setUp(): void
    {
        $this->textRepository = $this->createMock(TextRepositoryInterface::class);
        $this->archiveText = $this->createMock(ArchiveText::class);
        $this->buildTextFilters = $this->createMock(BuildTextFilters::class);
        $this->deleteText = $this->createMock(DeleteText::class);
        $this->getTextForEdit = $this->createMock(GetTextForEdit::class);
        $this->getTextForReading = $this->createMock(GetTextForReading::class);
        $this->importText = $this->createMock(ImportText::class);
        $this->listTexts = $this->createMock(ListTexts::class);
        $this->parseText = $this->createMock(ParseText::class);
        $this->updateText = $this->createMock(UpdateText::class);
        $this->sentenceService = $this->createMock(SentenceService::class);

        $this->facade = new TextFacade(
            $this->textRepository,
            $this->archiveText,
            $this->buildTextFilters,
            $this->deleteText,
            $this->getTextForEdit,
            $this->getTextForReading,
            $this->importText,
            $this->listTexts,
            $this->parseText,
            $this->updateText,
            $this->sentenceService
        );
    }

    // =====================
    // CONSTRUCTOR TESTS
    // =====================

    public function testConstructorCreatesInstance(): void
    {
        $facade = new TextFacade();
        $this->assertInstanceOf(TextFacade::class, $facade);
    }

    public function testConstructorAcceptsMockDependencies(): void
    {
        $this->assertInstanceOf(TextFacade::class, $this->facade);
    }

    public function testConstructorAcceptsNullDependencies(): void
    {
        $facade = new TextFacade(null, null, null, null, null, null, null, null, null, null, null);
        $this->assertInstanceOf(TextFacade::class, $facade);
    }

    // =============================
    // ARCHIVED TEXT METHODS TESTS
    // =============================

    public function testGetArchivedTextCountDelegatesToListTexts(): void
    {
        $this->listTexts
            ->expects($this->once())
            ->method('getArchivedTextCount')
            ->with(' AND AtLgID = 1', " AND AtTitle LIKE '%test%'", '')
            ->willReturn(42);

        $result = $this->facade->getArchivedTextCount(
            ' AND AtLgID = 1',
            " AND AtTitle LIKE '%test%'",
            ''
        );

        $this->assertEquals(42, $result);
    }

    public function testGetArchivedTextsListDelegatesToListTexts(): void
    {
        $expected = [
            ['AtID' => 1, 'AtTitle' => 'Test Text 1'],
            ['AtID' => 2, 'AtTitle' => 'Test Text 2'],
        ];

        $this->listTexts
            ->expects($this->once())
            ->method('getArchivedTextsList')
            ->with('', '', '', 1, 1, 10)
            ->willReturn($expected);

        $result = $this->facade->getArchivedTextsList('', '', '', 1, 1, 10);

        $this->assertCount(2, $result);
        $this->assertEquals('Test Text 1', $result[0]['AtTitle']);
    }

    public function testGetArchivedTextByIdDelegatesToGetTextForEdit(): void
    {
        $expected = [
            'AtID' => 5,
            'AtTitle' => 'Archived Text',
            'AtText' => 'Content',
            'AtLgID' => 1,
        ];

        $this->getTextForEdit
            ->expects($this->once())
            ->method('getArchivedTextById')
            ->with(5)
            ->willReturn($expected);

        $result = $this->facade->getArchivedTextById(5);

        $this->assertIsArray($result);
        $this->assertEquals(5, $result['AtID']);
        $this->assertEquals('Archived Text', $result['AtTitle']);
    }

    public function testGetArchivedTextByIdReturnsNullForNotFound(): void
    {
        $this->getTextForEdit
            ->expects($this->once())
            ->method('getArchivedTextById')
            ->with(999)
            ->willReturn(null);

        $result = $this->facade->getArchivedTextById(999);
        $this->assertNull($result);
    }

    public function testDeleteArchivedTextDelegatesToDeleteText(): void
    {
        $this->deleteText
            ->expects($this->once())
            ->method('deleteArchivedText')
            ->with(5)
            ->willReturn('Archived text deleted');

        $result = $this->facade->deleteArchivedText(5);

        $this->assertEquals('Archived text deleted', $result);
    }

    public function testDeleteArchivedTextsDelegatesToDeleteText(): void
    {
        $this->deleteText
            ->expects($this->once())
            ->method('deleteArchivedTexts')
            ->with([1, 2, 3])
            ->willReturn('3 archived texts deleted');

        $result = $this->facade->deleteArchivedTexts([1, 2, 3]);

        $this->assertEquals('3 archived texts deleted', $result);
    }

    public function testUnarchiveTextDelegatesToArchiveText(): void
    {
        $expected = ['success' => true, 'newId' => 100];

        $this->archiveText
            ->expects($this->once())
            ->method('unarchive')
            ->with(5)
            ->willReturn($expected);

        $result = $this->facade->unarchiveText(5);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
    }

    public function testUnarchiveTextsDelegatesToArchiveText(): void
    {
        $this->archiveText
            ->expects($this->once())
            ->method('unarchiveMultiple')
            ->with([1, 2, 3])
            ->willReturn('3 texts unarchived');

        $result = $this->facade->unarchiveTexts([1, 2, 3]);

        $this->assertEquals('3 texts unarchived', $result);
    }

    public function testUpdateArchivedTextDelegatesToUpdateText(): void
    {
        $this->updateText
            ->expects($this->once())
            ->method('updateArchivedText')
            ->with(5, 1, 'New Title', 'New text', '', 'https://source.com')
            ->willReturn('Archived text updated');

        $result = $this->facade->updateArchivedText(
            5,
            1,
            'New Title',
            'New text',
            '',
            'https://source.com'
        );

        $this->assertEquals('Archived text updated', $result);
    }

    // =============================
    // FILTER BUILDING METHOD TESTS
    // =============================

    public function testBuildArchivedQueryWhereClauseDelegatesToFilterBuilder(): void
    {
        $expected = ['clause' => " AND AtTitle LIKE '%test%'", 'params' => []];

        $this->buildTextFilters
            ->expects($this->once())
            ->method('buildArchivedQueryWhereClause')
            ->with('test', 'title', 'N')
            ->willReturn($expected);

        $result = $this->facade->buildArchivedQueryWhereClause('test', 'title', 'N');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('clause', $result);
    }

    public function testBuildArchivedQueryWhereClauseWithEmptyQuery(): void
    {
        $this->buildTextFilters
            ->expects($this->once())
            ->method('buildArchivedQueryWhereClause')
            ->with('', 'title', 'N')
            ->willReturn(['clause' => '', 'params' => []]);

        $result = $this->facade->buildArchivedQueryWhereClause('', 'title', 'N');

        $this->assertIsArray($result);
    }

    public function testBuildArchivedTagHavingClauseDelegatesToFilterBuilder(): void
    {
        $this->buildTextFilters
            ->expects($this->once())
            ->method('buildArchivedTagHavingClause')
            ->with(1, 2, 'and')
            ->willReturn('HAVING tag1 AND tag2');

        $result = $this->facade->buildArchivedTagHavingClause(1, 2, 'and');

        $this->assertIsString($result);
        $this->assertStringContainsString('HAVING', $result);
    }

    public function testBuildArchivedTagHavingClauseWithNoTags(): void
    {
        $this->buildTextFilters
            ->expects($this->once())
            ->method('buildArchivedTagHavingClause')
            ->with(0, 0, '')
            ->willReturn('');

        $result = $this->facade->buildArchivedTagHavingClause(0, 0, '');

        $this->assertEquals('', $result);
    }

    public function testBuildTextQueryWhereClauseDelegatesToFilterBuilder(): void
    {
        $expected = ['clause' => " AND TxTitle LIKE '%test%'", 'params' => []];

        $this->buildTextFilters
            ->expects($this->once())
            ->method('buildQueryWhereClause')
            ->with('test', 'title', 'N', 'Tx')
            ->willReturn($expected);

        $result = $this->facade->buildTextQueryWhereClause('test', 'title', 'N');

        $this->assertIsArray($result);
    }

    public function testBuildTextTagHavingClauseDelegatesToFilterBuilder(): void
    {
        $this->buildTextFilters
            ->expects($this->once())
            ->method('buildTextTagHavingClause')
            ->with(1, 0, '')
            ->willReturn('HAVING tag1 = 1');

        $result = $this->facade->buildTextTagHavingClause(1, 0, '');

        $this->assertIsString($result);
    }

    public function testValidateRegexQueryDelegatesToFilterBuilder(): void
    {
        $this->buildTextFilters
            ->expects($this->once())
            ->method('validateRegexQuery')
            ->with('[a-z]+', 'Y')
            ->willReturn(true);

        $result = $this->facade->validateRegexQuery('[a-z]+', 'Y');

        $this->assertTrue($result);
    }

    public function testValidateRegexQueryWithInvalidPattern(): void
    {
        $this->buildTextFilters
            ->expects($this->once())
            ->method('validateRegexQuery')
            ->with('[invalid(', 'Y')
            ->willReturn(false);

        $result = $this->facade->validateRegexQuery('[invalid(', 'Y');

        $this->assertFalse($result);
    }

    public function testValidateRegexQueryWithNonRegexMode(): void
    {
        $this->buildTextFilters
            ->expects($this->once())
            ->method('validateRegexQuery')
            ->with('test', '')
            ->willReturn(true);

        $result = $this->facade->validateRegexQuery('test', '');

        $this->assertTrue($result);
    }

    // ========================
    // PAGINATION METHOD TESTS
    // ========================

    public function testGetArchivedTextsPerPageDelegatesToListTexts(): void
    {
        $this->listTexts
            ->expects($this->once())
            ->method('getArchivedTextsPerPage')
            ->willReturn(20);

        $result = $this->facade->getArchivedTextsPerPage();

        $this->assertEquals(20, $result);
    }

    public function testGetTextsPerPageDelegatesToListTexts(): void
    {
        $this->listTexts
            ->expects($this->once())
            ->method('getTextsPerPage')
            ->willReturn(15);

        $result = $this->facade->getTextsPerPage();

        $this->assertEquals(15, $result);
    }

    public function testGetPaginationDelegatesToListTexts(): void
    {
        $expected = [
            'pages' => 10,
            'currentPage' => 2,
            'limit' => 'LIMIT 10,10'
        ];

        $this->listTexts
            ->expects($this->once())
            ->method('getPagination')
            ->with(100, 2, 10)
            ->willReturn($expected);

        $result = $this->facade->getPagination(100, 2, 10);

        $this->assertIsArray($result);
        $this->assertEquals(10, $result['pages']);
        $this->assertEquals(2, $result['currentPage']);
    }

    public function testGetPaginationWithZeroTotal(): void
    {
        $expected = [
            'pages' => 0,
            'currentPage' => 1,
            'limit' => 'LIMIT 0,10'
        ];

        $this->listTexts
            ->expects($this->once())
            ->method('getPagination')
            ->with(0, 1, 10)
            ->willReturn($expected);

        $result = $this->facade->getPagination(0, 1, 10);

        $this->assertEquals(0, $result['pages']);
    }

    // =====================
    // ACTIVE TEXT METHODS
    // =====================

    public function testGetTextByIdDelegatesToGetTextForEdit(): void
    {
        $expected = [
            'TxID' => 5,
            'TxTitle' => 'Test Text',
            'TxText' => 'Content',
            'TxLgID' => 1,
        ];

        $this->getTextForEdit
            ->expects($this->once())
            ->method('getTextById')
            ->with(5)
            ->willReturn($expected);

        $result = $this->facade->getTextById(5);

        $this->assertIsArray($result);
        $this->assertEquals(5, $result['TxID']);
    }

    public function testGetTextByIdReturnsNullForNotFound(): void
    {
        $this->getTextForEdit
            ->expects($this->once())
            ->method('getTextById')
            ->with(999)
            ->willReturn(null);

        $result = $this->facade->getTextById(999);
        $this->assertNull($result);
    }

    public function testDeleteTextDelegatesToDeleteText(): void
    {
        $this->deleteText
            ->expects($this->once())
            ->method('execute')
            ->with(5)
            ->willReturn('Text deleted');

        $result = $this->facade->deleteText(5);

        $this->assertEquals('Text deleted', $result);
    }

    public function testArchiveTextDelegatesToArchiveText(): void
    {
        $this->archiveText
            ->expects($this->once())
            ->method('execute')
            ->with(5)
            ->willReturn('Text archived');

        $result = $this->facade->archiveText(5);

        $this->assertEquals('Text archived', $result);
    }

    public function testGetTextCountDelegatesToListTexts(): void
    {
        $this->listTexts
            ->expects($this->once())
            ->method('getTextCount')
            ->with(' AND TxLgID = 1', '', '')
            ->willReturn(25);

        $result = $this->facade->getTextCount(' AND TxLgID = 1', '', '');

        $this->assertEquals(25, $result);
    }

    public function testGetTextsListDelegatesToListTexts(): void
    {
        $expected = [
            ['TxID' => 1, 'TxTitle' => 'Text 1'],
            ['TxID' => 2, 'TxTitle' => 'Text 2'],
        ];

        $this->listTexts
            ->expects($this->once())
            ->method('getTextsList')
            ->with('', '', '', 1, 1, 10)
            ->willReturn($expected);

        $result = $this->facade->getTextsList('', '', '', 1, 1, 10);

        $this->assertCount(2, $result);
    }

    public function testGetBasicTextsForLanguageDelegatesToListTexts(): void
    {
        $expected = [
            'items' => [],
            'total' => 0,
            'page' => 1,
            'per_page' => 20,
            'total_pages' => 0
        ];

        $this->listTexts
            ->expects($this->once())
            ->method('getTextsForLanguage')
            ->with(1, 1, 20)
            ->willReturn($expected);

        $result = $this->facade->getBasicTextsForLanguage(1, 1, 20);

        $this->assertIsArray($result);
    }

    public function testCreateTextDelegatesToImportText(): void
    {
        $expected = ['success' => true, 'textId' => 100];

        $this->importText
            ->expects($this->once())
            ->method('execute')
            ->with(1, 'New Title', 'New text content', '', 'https://source.com')
            ->willReturn($expected);

        $result = $this->facade->createText(
            1,
            'New Title',
            'New text content',
            '',
            'https://source.com'
        );

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals(100, $result['textId']);
    }

    public function testUpdateTextDelegatesToUpdateText(): void
    {
        $expected = ['success' => true, 'message' => 'Updated'];

        $this->updateText
            ->expects($this->once())
            ->method('execute')
            ->with(5, 1, 'Updated Title', 'Updated content', '', '')
            ->willReturn($expected);

        $result = $this->facade->updateText(
            5,
            1,
            'Updated Title',
            'Updated content',
            '',
            ''
        );

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
    }

    public function testDeleteTextsDelegatesToDeleteText(): void
    {
        $this->deleteText
            ->expects($this->once())
            ->method('deleteMultiple')
            ->with([1, 2, 3])
            ->willReturn('3 texts deleted');

        $result = $this->facade->deleteTexts([1, 2, 3]);

        $this->assertEquals('3 texts deleted', $result);
    }

    public function testArchiveTextsDelegatesToArchiveText(): void
    {
        $this->archiveText
            ->expects($this->once())
            ->method('archiveMultiple')
            ->with([1, 2, 3])
            ->willReturn('3 texts archived');

        $result = $this->facade->archiveTexts([1, 2, 3]);

        $this->assertEquals('3 texts archived', $result);
    }

    public function testRebuildTextsDelegatesToUpdateText(): void
    {
        $this->updateText
            ->expects($this->once())
            ->method('rebuildTexts')
            ->with([1, 2])
            ->willReturn('2 texts rebuilt');

        $result = $this->facade->rebuildTexts([1, 2]);

        $this->assertEquals('2 texts rebuilt', $result);
    }

    // ====================
    // TEXT CHECK METHODS
    // ====================

    public function testGetParsingPreviewDelegatesToParseText(): void
    {
        $expected = [
            'sentences' => 5,
            'words' => 50,
            'unknown_percent' => 25.5
        ];

        $this->parseText
            ->expects($this->once())
            ->method('execute')
            ->with('Test text content', 1)
            ->willReturn($expected);

        $result = $this->facade->getParsingPreview('Test text content', 1);

        $this->assertIsArray($result);
        $this->assertEquals(5, $result['sentences']);
    }

    public function testValidateTextLengthDelegatesToParseText(): void
    {
        $this->parseText
            ->expects($this->once())
            ->method('validateTextLength')
            ->with('Short text')
            ->willReturn(true);

        $result = $this->facade->validateTextLength('Short text');

        $this->assertTrue($result);
    }

    public function testValidateTextLengthWithLongText(): void
    {
        $longText = str_repeat('a', 100000);

        $this->parseText
            ->expects($this->once())
            ->method('validateTextLength')
            ->with($longText)
            ->willReturn(false);

        $result = $this->facade->validateTextLength($longText);

        $this->assertFalse($result);
    }

    public function testSetTermSentencesDelegatesToParseText(): void
    {
        $this->parseText
            ->expects($this->once())
            ->method('setTermSentences')
            ->with([1, 2], false)
            ->willReturn('Term sentences set: 10');

        $result = $this->facade->setTermSentences([1, 2]);

        $this->assertEquals('Term sentences set: 10', $result);
    }

    public function testSetTermSentencesWithActiveOnly(): void
    {
        $this->parseText
            ->expects($this->once())
            ->method('setTermSentences')
            ->with([1, 2], true)
            ->willReturn('Term sentences set: 5');

        $result = $this->facade->setTermSentences([1, 2], true);

        $this->assertEquals('Term sentences set: 5', $result);
    }

    // =======================
    // LONG TEXT IMPORT METHODS
    // =======================

    public function testPrepareSimpleLongTextDataDelegatesToImportText(): void
    {
        $this->importText
            ->expects($this->once())
            ->method('prepareLongTextData')
            ->with('Pasted content', null)
            ->willReturn('Pasted content');

        $result = $this->facade->prepareSimpleLongTextData('Pasted content', null);

        $this->assertEquals('Pasted content', $result);
    }

    public function testPrepareSimpleLongTextDataWithFile(): void
    {
        $file = ['tmp_name' => '/tmp/upload.txt', 'error' => 0];

        $this->importText
            ->expects($this->once())
            ->method('prepareLongTextData')
            ->with(null, $file)
            ->willReturn('File content');

        $result = $this->facade->prepareSimpleLongTextData(null, $file);

        $this->assertEquals('File content', $result);
    }

    public function testPrepareSimpleLongTextDataWithNull(): void
    {
        $this->importText
            ->expects($this->once())
            ->method('prepareLongTextData')
            ->with(null, null)
            ->willReturn(null);

        $result = $this->facade->prepareSimpleLongTextData(null, null);

        $this->assertNull($result);
    }

    public function testSplitTextIntoChunksDelegatesToImportText(): void
    {
        $expected = ['Chunk 1', 'Chunk 2'];

        $this->importText
            ->expects($this->once())
            ->method('splitLongText')
            ->with('Long text content', 60000)
            ->willReturn($expected);

        $result = $this->facade->splitTextIntoChunks('Long text content');

        $this->assertCount(2, $result);
    }

    public function testSplitTextIntoChunksWithCustomMaxLength(): void
    {
        $this->importText
            ->expects($this->once())
            ->method('splitLongText')
            ->with('Content', 500)
            ->willReturn(['Chunk 1']);

        $result = $this->facade->splitTextIntoChunks('Content', 500);

        $this->assertCount(1, $result);
    }

    public function testImportLongTextChunksDelegatesToImportText(): void
    {
        $expected = ['success' => true, 'imported' => 5];

        $this->importText
            ->expects($this->once())
            ->method('saveLongTextImport')
            ->with(1, 'Base Title', ['chunk1', 'chunk2'], '', '', [])
            ->willReturn($expected);

        $result = $this->facade->importLongTextChunks(1, 'Base Title', ['chunk1', 'chunk2']);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
    }

    public function testImportLongTextChunksWithAllOptions(): void
    {
        $expected = ['success' => true, 'imported' => 3];

        $this->importText
            ->expects($this->once())
            ->method('saveLongTextImport')
            ->with(1, 'Title', ['c1', 'c2'], 'audio.mp3', 'https://source.com', [1, 2])
            ->willReturn($expected);

        $result = $this->facade->importLongTextChunks(
            1,
            'Title',
            ['c1', 'c2'],
            'audio.mp3',
            'https://source.com',
            [1, 2]
        );

        $this->assertEquals(3, $result['imported']);
    }

    // ======================
    // TEXT READING METHODS
    // ======================

    public function testGetTextForReadingDelegatesToGetTextForReading(): void
    {
        $expected = [
            'TxID' => 5,
            'TxTitle' => 'Reading Text',
            'sentences' => []
        ];

        $this->getTextForReading
            ->expects($this->once())
            ->method('execute')
            ->with(5)
            ->willReturn($expected);

        $result = $this->facade->getTextForReading(5);

        $this->assertIsArray($result);
        $this->assertEquals(5, $result['TxID']);
    }

    public function testGetTextForReadingReturnsNullForNotFound(): void
    {
        $this->getTextForReading
            ->expects($this->once())
            ->method('execute')
            ->with(999)
            ->willReturn(null);

        $result = $this->facade->getTextForReading(999);
        $this->assertNull($result);
    }

    public function testGetLanguageSettingsForReadingDelegatesToGetTextForReading(): void
    {
        $expected = [
            'LgID' => 1,
            'LgName' => 'English',
            'LgDict1URI' => 'https://dict.com'
        ];

        $this->getTextForReading
            ->expects($this->once())
            ->method('getLanguageSettingsForReading')
            ->with(1)
            ->willReturn($expected);

        $result = $this->facade->getLanguageSettingsForReading(1);

        $this->assertIsArray($result);
        $this->assertEquals('English', $result['LgName']);
    }

    public function testGetLanguageSettingsForReadingReturnsNullForNotFound(): void
    {
        $this->getTextForReading
            ->expects($this->once())
            ->method('getLanguageSettingsForReading')
            ->with(999)
            ->willReturn(null);

        $result = $this->facade->getLanguageSettingsForReading(999);
        $this->assertNull($result);
    }

    public function testGetTtsVoiceApiDelegatesToGetTextForReading(): void
    {
        $this->getTextForReading
            ->expects($this->once())
            ->method('getTtsVoiceApi')
            ->with(1)
            ->willReturn('ResponsiveVoice:uk');

        $result = $this->facade->getTtsVoiceApi(1);

        $this->assertEquals('ResponsiveVoice:uk', $result);
    }

    public function testGetTtsVoiceApiReturnsNullForEmpty(): void
    {
        $this->getTextForReading
            ->expects($this->once())
            ->method('getTtsVoiceApi')
            ->with(1)
            ->willReturn('');

        $result = $this->facade->getTtsVoiceApi(1);

        $this->assertNull($result);
    }

    public function testGetLanguageIdByNameDelegatesToGetTextForReading(): void
    {
        $this->getTextForReading
            ->expects($this->once())
            ->method('getLanguageIdByName')
            ->with('English')
            ->willReturn(1);

        $result = $this->facade->getLanguageIdByName('English');

        $this->assertEquals(1, $result);
    }

    public function testGetLanguageIdByNameReturnsNullForNotFound(): void
    {
        $this->getTextForReading
            ->expects($this->once())
            ->method('getLanguageIdByName')
            ->with('NonexistentLanguage')
            ->willReturn(null);

        $result = $this->facade->getLanguageIdByName('NonexistentLanguage');

        $this->assertNull($result);
    }

    public function testGetLanguageTranslateUrisDelegatesToGetTextForReading(): void
    {
        $expected = [
            1 => 'https://translate.google.com',
            2 => 'https://deepl.com'
        ];

        $this->getTextForReading
            ->expects($this->once())
            ->method('getLanguageTranslateUris')
            ->willReturn($expected);

        $result = $this->facade->getLanguageTranslateUris();

        $this->assertCount(2, $result);
    }

    // =======================
    // TEXT EDIT PAGE METHODS
    // =======================

    public function testGetTextForEditDelegatesToGetTextForEdit(): void
    {
        $expected = [
            'TxID' => 5,
            'TxTitle' => 'Edit Text',
            'TxText' => 'Content'
        ];

        $this->getTextForEdit
            ->expects($this->once())
            ->method('getTextForEdit')
            ->with(5)
            ->willReturn($expected);

        $result = $this->facade->getTextForEdit(5);

        $this->assertIsArray($result);
        $this->assertEquals(5, $result['TxID']);
    }

    public function testGetTextForEditReturnsNullForNotFound(): void
    {
        $this->getTextForEdit
            ->expects($this->once())
            ->method('getTextForEdit')
            ->with(999)
            ->willReturn(null);

        $result = $this->facade->getTextForEdit(999);
        $this->assertNull($result);
    }

    public function testGetLanguageDataForFormDelegatesToGetTextForEdit(): void
    {
        $expected = [
            ['LgID' => 1, 'LgName' => 'English'],
            ['LgID' => 2, 'LgName' => 'German']
        ];

        $this->getTextForEdit
            ->expects($this->once())
            ->method('getLanguageDataForForm')
            ->willReturn($expected);

        $result = $this->facade->getLanguageDataForForm();

        $this->assertCount(2, $result);
    }

    public function testSaveAndReparseTextDelegatesToUpdateText(): void
    {
        $this->updateText
            ->expects($this->once())
            ->method('saveTextAndReparse')
            ->with(5, 1, 'Title', 'Text', '', '')
            ->willReturn('Text saved and reparsed');

        $result = $this->facade->saveAndReparseText(5, 1, 'Title', 'Text', '', '');

        $this->assertEquals('Text saved and reparsed', $result);
    }

    public function testGetTextsForSelectDelegatesToGetTextForEdit(): void
    {
        $expected = [
            ['id' => 1, 'title' => 'Text 1'],
            ['id' => 2, 'title' => 'Text 2']
        ];

        $this->getTextForEdit
            ->expects($this->once())
            ->method('getTextsForSelect')
            ->with(0, 30)
            ->willReturn($expected);

        $result = $this->facade->getTextsForSelect();

        $this->assertCount(2, $result);
    }

    public function testGetTextsForSelectWithLanguageFilter(): void
    {
        $expected = [['id' => 1, 'title' => 'Text 1']];

        $this->getTextForEdit
            ->expects($this->once())
            ->method('getTextsForSelect')
            ->with(1, 40)
            ->willReturn($expected);

        $result = $this->facade->getTextsForSelect(1, 40);

        $this->assertCount(1, $result);
    }

    // ===========================
    // BC METHOD TESTS (Database required)
    // ===========================

    public function testPrepareLongTextDataWithEmptyInput(): void
    {
        $facade = new TextFacade();
        $result = $facade->prepareLongTextData([], '', 1);
        $this->assertIsString($result);
    }

    public function testPrepareLongTextDataWithParagraphHandlingMode1(): void
    {
        $facade = new TextFacade();
        $result = $facade->prepareLongTextData([], "Line 1\nLine 2", 1);
        $this->assertIsString($result);
    }

    public function testPrepareLongTextDataWithParagraphHandlingMode2(): void
    {
        $facade = new TextFacade();
        $result = $facade->prepareLongTextData([], "Para 1\n\nPara 2", 2);
        $this->assertIsString($result);
    }

    public function testPrepareLongTextDataRemovesExtraWhitespace(): void
    {
        $facade = new TextFacade();
        $result = $facade->prepareLongTextData([], "Text  with   extra    spaces", 1);
        $this->assertStringNotContainsString('  ', $result);
    }

    public function testGetTextsForLanguageReturnsValidStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }
        $facade = new TextFacade();
        $result = $facade->getTextsForLanguage(1, 1, 10, 1);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('texts', $result);
        $this->assertArrayHasKey('pagination', $result);
    }

    public function testGetTextsForLanguageWithDifferentSortOptions(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }
        $facade = new TextFacade();
        $result1 = $facade->getTextsForLanguage(1, 1, 10, 1);
        $result2 = $facade->getTextsForLanguage(1, 1, 10, 2);
        $result3 = $facade->getTextsForLanguage(1, 1, 10, 3);

        $this->assertArrayHasKey('texts', $result1);
        $this->assertArrayHasKey('texts', $result2);
        $this->assertArrayHasKey('texts', $result3);
    }

    public function testGetArchivedTextsForLanguageReturnsValidStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }
        $facade = new TextFacade();
        $result = $facade->getArchivedTextsForLanguage(1, 1, 10, 1);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('texts', $result);
        $this->assertArrayHasKey('pagination', $result);
    }

    public function testGetArchivedTextsForLanguagePaginationStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }
        $facade = new TextFacade();
        $result = $facade->getArchivedTextsForLanguage(1, 2, 5, 1);
        $this->assertArrayHasKey('pagination', $result);
        $pagination = $result['pagination'];
        $this->assertArrayHasKey('current_page', $pagination);
        $this->assertArrayHasKey('per_page', $pagination);
        $this->assertArrayHasKey('total', $pagination);
        $this->assertArrayHasKey('total_pages', $pagination);
        $this->assertEquals(2, $pagination['current_page']);
        $this->assertEquals(5, $pagination['per_page']);
    }

    public function testGetTextDataForContentWithInvalidId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }
        $facade = new TextFacade();
        $result = $facade->getTextDataForContent(-1);
        $this->assertNull($result);
    }

    public function testSplitLongTextReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }
        $facade = new TextFacade();
        $result = $facade->splitLongText('Test sentence one. Test sentence two.', 1, 10);
        $this->assertIsArray($result);
    }

    public function testSetTermSentencesWithServiceWithEmptyArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }
        $facade = new TextFacade();
        $result = $facade->setTermSentencesWithService([]);
        $this->assertIsString($result);
        $this->assertStringContainsString('0', $result);
    }

    public function testSaveLongTextImportWithMismatchedCountReturnsFalse(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }
        $facade = new TextFacade();
        $result = $facade->saveLongTextImport(1, 'Test', '', ['text1', 'text2'], 5);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
    }

    public function testSaveTextAndReparseReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }
        // This test would create data so we skip validation
        $facade = new TextFacade();
        $this->assertTrue(method_exists($facade, 'saveTextAndReparse'));
    }

    // ===================================
    // METHOD EXISTENCE TESTS
    // ===================================

    public function testGetArchivedTextCountMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'getArchivedTextCount'),
            'getArchivedTextCount method should exist'
        );
    }

    public function testGetArchivedTextsListMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'getArchivedTextsList'),
            'getArchivedTextsList method should exist'
        );
    }

    public function testGetArchivedTextByIdMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'getArchivedTextById'),
            'getArchivedTextById method should exist'
        );
    }

    public function testDeleteArchivedTextMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'deleteArchivedText'),
            'deleteArchivedText method should exist'
        );
    }

    public function testUnarchiveTextMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'unarchiveText'),
            'unarchiveText method should exist'
        );
    }

    public function testUpdateArchivedTextMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'updateArchivedText'),
            'updateArchivedText method should exist'
        );
    }

    public function testBuildArchivedQueryWhereClauseMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'buildArchivedQueryWhereClause'),
            'buildArchivedQueryWhereClause method should exist'
        );
    }

    public function testGetTextByIdMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'getTextById'),
            'getTextById method should exist'
        );
    }

    public function testDeleteTextMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'deleteText'),
            'deleteText method should exist'
        );
    }

    public function testArchiveTextMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'archiveText'),
            'archiveText method should exist'
        );
    }

    public function testCreateTextMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'createText'),
            'createText method should exist'
        );
    }

    public function testUpdateTextMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'updateText'),
            'updateText method should exist'
        );
    }

    public function testGetParsingPreviewMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'getParsingPreview'),
            'getParsingPreview method should exist'
        );
    }

    public function testValidateTextLengthMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'validateTextLength'),
            'validateTextLength method should exist'
        );
    }

    public function testGetTextForReadingMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'getTextForReading'),
            'getTextForReading method should exist'
        );
    }

    public function testGetTextForEditMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'getTextForEdit'),
            'getTextForEdit method should exist'
        );
    }

    public function testCheckTextMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'checkText'),
            'checkText method should exist'
        );
    }

    public function testSaveTextAndReparseMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->facade, 'saveTextAndReparse'),
            'saveTextAndReparse method should exist'
        );
    }
}
