<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Dictionary\Http;

use Lwt\Modules\Dictionary\Http\DictionaryApiHandler;
use Lwt\Modules\Dictionary\Application\DictionaryFacade;
use Lwt\Modules\Dictionary\Domain\LocalDictionary;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for DictionaryApiHandler.
 *
 * Tests dictionary API operations including CRUD, lookup, and import functionality.
 */
class DictionaryApiHandlerTest extends TestCase
{
    /** @var DictionaryFacade&MockObject */
    private DictionaryFacade $facade;

    private DictionaryApiHandler $handler;

    protected function setUp(): void
    {
        $this->facade = $this->createMock(DictionaryFacade::class);
        $this->handler = new DictionaryApiHandler($this->facade);
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    public function testConstructorCreatesValidHandler(): void
    {
        $this->assertInstanceOf(DictionaryApiHandler::class, $this->handler);
    }

    public function testConstructorAcceptsNullParameter(): void
    {
        $handler = new DictionaryApiHandler(null);
        $this->assertInstanceOf(DictionaryApiHandler::class, $handler);
    }

    // =========================================================================
    // getDictionary tests
    // =========================================================================

    public function testGetDictionaryReturnsErrorWhenNotFound(): void
    {
        $this->facade->method('getById')
            ->willReturn(null);

        $result = $this->handler->getDictionary(999);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Dictionary not found', $result['error']);
    }

    public function testGetDictionaryReturnsDictionaryData(): void
    {
        $dictionary = $this->createMockDictionary(1, 'Test Dict', 1);
        $this->facade->method('getById')
            ->willReturn($dictionary);

        $result = $this->handler->getDictionary(1);

        $this->assertArrayNotHasKey('error', $result);
        $this->assertSame(1, $result['id']);
        $this->assertSame('Test Dict', $result['name']);
    }

    // =========================================================================
    // getDictionaries tests
    // =========================================================================

    public function testGetDictionariesReturnsFormattedList(): void
    {
        $dict1 = $this->createMockDictionary(1, 'Dict 1', 1);
        $dict2 = $this->createMockDictionary(2, 'Dict 2', 1);

        $this->facade->method('getAllForLanguage')
            ->with(1)
            ->willReturn([$dict1, $dict2]);
        $this->facade->method('getLocalDictMode')
            ->with(1)
            ->willReturn(0);

        $result = $this->handler->getDictionaries(1);

        $this->assertArrayHasKey('dictionaries', $result);
        $this->assertArrayHasKey('mode', $result);
        $this->assertCount(2, $result['dictionaries']);
        $this->assertSame(0, $result['mode']);
    }

    public function testGetDictionariesReturnsEmptyArray(): void
    {
        $this->facade->method('getAllForLanguage')
            ->willReturn([]);
        $this->facade->method('getLocalDictMode')
            ->willReturn(0);

        $result = $this->handler->getDictionaries(1);

        $this->assertSame([], $result['dictionaries']);
    }

    // =========================================================================
    // createDictionary tests
    // =========================================================================

    public function testCreateDictionaryReturnsErrorWhenLanguageIdMissing(): void
    {
        $result = $this->handler->createDictionary([
            'name' => 'Test Dict'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Language ID is required', $result['error']);
    }

    public function testCreateDictionaryReturnsErrorWhenLanguageIdZero(): void
    {
        $result = $this->handler->createDictionary([
            'language_id' => 0,
            'name' => 'Test Dict'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Language ID is required', $result['error']);
    }

    public function testCreateDictionaryReturnsErrorWhenLanguageIdNegative(): void
    {
        $result = $this->handler->createDictionary([
            'language_id' => -1,
            'name' => 'Test Dict'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Language ID is required', $result['error']);
    }

    public function testCreateDictionaryReturnsErrorWhenNameEmpty(): void
    {
        $result = $this->handler->createDictionary([
            'language_id' => 1,
            'name' => ''
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Dictionary name is required', $result['error']);
    }

    public function testCreateDictionaryReturnsErrorWhenNameOnlyWhitespace(): void
    {
        $result = $this->handler->createDictionary([
            'language_id' => 1,
            'name' => '   '
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Dictionary name is required', $result['error']);
    }

    public function testCreateDictionaryReturnsSuccessWithValidData(): void
    {
        $dictionary = $this->createMockDictionary(123, 'Test Dict', 1);

        $this->facade->expects($this->once())
            ->method('create')
            ->with(1, 'Test Dict', 'csv', null)
            ->willReturn(123);
        $this->facade->method('getById')
            ->willReturn($dictionary);

        $result = $this->handler->createDictionary([
            'language_id' => 1,
            'name' => 'Test Dict'
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('dictionary', $result);
    }

    public function testCreateDictionaryPassesOptionalFields(): void
    {
        $dictionary = $this->createMockDictionary(1, 'Test', 1);

        $this->facade->expects($this->once())
            ->method('create')
            ->with(1, 'Test', 'json', 'Description')
            ->willReturn(1);
        $this->facade->method('getById')
            ->willReturn($dictionary);

        $this->handler->createDictionary([
            'language_id' => 1,
            'name' => 'Test',
            'description' => 'Description',
            'source_format' => 'json'
        ]);
    }

    // =========================================================================
    // updateDictionary tests
    // =========================================================================

    public function testUpdateDictionaryReturnsErrorWhenNotFound(): void
    {
        $this->facade->method('getById')
            ->willReturn(null);

        $result = $this->handler->updateDictionary(999, ['name' => 'New Name']);

        $this->assertFalse($result['success']);
        $this->assertSame('Dictionary not found', $result['error']);
    }

    public function testUpdateDictionaryReturnsSuccessWithValidData(): void
    {
        $dictionary = $this->createMockDictionary(1, 'Old Name', 1);

        $this->facade->method('getById')
            ->willReturn($dictionary);
        $this->facade->expects($this->once())
            ->method('update');

        $result = $this->handler->updateDictionary(1, ['name' => 'New Name']);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('dictionary', $result);
    }

    // =========================================================================
    // deleteDictionary tests
    // =========================================================================

    public function testDeleteDictionaryReturnsErrorWhenNotFound(): void
    {
        $this->facade->method('delete')
            ->willReturn(false);

        $result = $this->handler->deleteDictionary(999);

        $this->assertFalse($result['success']);
        $this->assertSame('Dictionary not found', $result['error']);
    }

    public function testDeleteDictionaryReturnsSuccessWhenDeleted(): void
    {
        $this->facade->method('delete')
            ->willReturn(true);

        $result = $this->handler->deleteDictionary(1);

        $this->assertTrue($result['success']);
        $this->assertArrayNotHasKey('error', $result);
    }

    // =========================================================================
    // lookup tests
    // =========================================================================

    public function testLookupReturnsResultsAndMode(): void
    {
        $lookupResults = [
            ['term' => 'hello', 'definition' => 'greeting']
        ];

        $this->facade->method('lookup')
            ->with(1, 'hello')
            ->willReturn($lookupResults);
        $this->facade->method('getLocalDictMode')
            ->with(1)
            ->willReturn(1);

        $result = $this->handler->lookup(1, 'hello');

        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('mode', $result);
        $this->assertSame($lookupResults, $result['results']);
        $this->assertSame(1, $result['mode']);
    }

    public function testLookupReturnsEmptyResults(): void
    {
        $this->facade->method('lookup')
            ->willReturn([]);
        $this->facade->method('getLocalDictMode')
            ->willReturn(0);

        $result = $this->handler->lookup(1, 'nonexistent');

        $this->assertSame([], $result['results']);
    }

    // =========================================================================
    // lookupPrefix tests
    // =========================================================================

    public function testLookupPrefixReturnsResults(): void
    {
        $prefixResults = [
            ['term' => 'hello', 'definition' => 'greeting'],
            ['term' => 'help', 'definition' => 'assist']
        ];

        $this->facade->method('lookupPrefix')
            ->with(1, 'hel', 10)
            ->willReturn($prefixResults);

        $result = $this->handler->lookupPrefix(1, 'hel');

        $this->assertArrayHasKey('results', $result);
        $this->assertSame($prefixResults, $result['results']);
    }

    public function testLookupPrefixUsesCustomLimit(): void
    {
        $this->facade->expects($this->once())
            ->method('lookupPrefix')
            ->with(1, 'hel', 5)
            ->willReturn([]);

        $this->handler->lookupPrefix(1, 'hel', 5);
    }

    // =========================================================================
    // importFile tests
    // =========================================================================

    public function testImportFileReturnsErrorWhenDictionaryNotFound(): void
    {
        $this->facade->method('getById')
            ->willReturn(null);

        $result = $this->handler->importFile(999, [
            'file_path' => '/tmp/test.csv',
            'format' => 'csv'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Dictionary not found', $result['error']);
    }

    public function testImportFileReturnsErrorWhenFilePathEmpty(): void
    {
        $dictionary = $this->createMockDictionary(1, 'Test', 1);
        $this->facade->method('getById')
            ->willReturn($dictionary);

        $result = $this->handler->importFile(1, [
            'file_path' => '',
            'format' => 'csv'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('File not found', $result['error']);
    }

    public function testImportFileReturnsErrorWhenFileNotExists(): void
    {
        $dictionary = $this->createMockDictionary(1, 'Test', 1);
        $this->facade->method('getById')
            ->willReturn($dictionary);

        $result = $this->handler->importFile(1, [
            'file_path' => '/nonexistent/path/file.csv',
            'format' => 'csv'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('File not found', $result['error']);
    }

    // =========================================================================
    // previewFile tests
    // =========================================================================

    public function testPreviewFileReturnsErrorWhenFilePathEmpty(): void
    {
        $result = $this->handler->previewFile([
            'file_path' => '',
            'format' => 'csv'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('File not found', $result['error']);
    }

    public function testPreviewFileReturnsErrorWhenFileNotExists(): void
    {
        $result = $this->handler->previewFile([
            'file_path' => '/nonexistent/path/file.csv',
            'format' => 'csv'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('File not found', $result['error']);
    }

    // =========================================================================
    // clearEntries tests
    // =========================================================================

    public function testClearEntriesReturnsErrorWhenDictionaryNotFound(): void
    {
        $this->facade->method('getById')
            ->willReturn(null);

        $result = $this->handler->clearEntries(999);

        $this->assertFalse($result['success']);
        $this->assertSame('Dictionary not found', $result['error']);
    }

    // =========================================================================
    // getEntries tests
    // =========================================================================

    public function testGetEntriesReturnsErrorWhenDictionaryNotFound(): void
    {
        $this->facade->method('getById')
            ->willReturn(null);

        $result = $this->handler->getEntries(999);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Dictionary not found', $result['error']);
    }

    public function testGetEntriesReturnsPaginatedResults(): void
    {
        $dictionary = $this->createMockDictionary(1, 'Test', 1);
        $this->facade->method('getById')
            ->willReturn($dictionary);
        $this->facade->method('getEntries')
            ->willReturn([
                'entries' => [['term' => 'hello']],
                'page' => 1,
                'perPage' => 50,
                'total' => 100
            ]);

        $result = $this->handler->getEntries(1);

        $this->assertArrayHasKey('entries', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertSame(1, $result['pagination']['page']);
        $this->assertSame(50, $result['pagination']['per_page']);
        $this->assertSame(100, $result['pagination']['total']);
        $this->assertSame(2, $result['pagination']['total_pages']);
    }

    public function testGetEntriesClampsPaginationParams(): void
    {
        $dictionary = $this->createMockDictionary(1, 'Test', 1);
        $this->facade->method('getById')
            ->willReturn($dictionary);
        $this->facade->expects($this->once())
            ->method('getEntries')
            ->with(1, 1, 100)
            ->willReturn(['entries' => [], 'page' => 1, 'perPage' => 100, 'total' => 0]);

        $this->handler->getEntries(1, ['page' => -5, 'per_page' => 500]);
    }

    // =========================================================================
    // addEntry tests
    // =========================================================================

    public function testAddEntryReturnsErrorWhenDictionaryNotFound(): void
    {
        $this->facade->method('getById')
            ->willReturn(null);

        $result = $this->handler->addEntry(999, [
            'term' => 'hello',
            'definition' => 'greeting'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Dictionary not found', $result['error']);
    }

    public function testAddEntryReturnsErrorWhenTermEmpty(): void
    {
        $dictionary = $this->createMockDictionary(1, 'Test', 1);
        $this->facade->method('getById')
            ->willReturn($dictionary);

        $result = $this->handler->addEntry(1, [
            'term' => '',
            'definition' => 'greeting'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Term is required', $result['error']);
    }

    public function testAddEntryReturnsErrorWhenDefinitionEmpty(): void
    {
        $dictionary = $this->createMockDictionary(1, 'Test', 1);
        $this->facade->method('getById')
            ->willReturn($dictionary);

        $result = $this->handler->addEntry(1, [
            'term' => 'hello',
            'definition' => ''
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Definition is required', $result['error']);
    }

    // =========================================================================
    // updateEntry tests
    // =========================================================================

    public function testUpdateEntryReturnsErrorWhenTermEmpty(): void
    {
        $result = $this->handler->updateEntry(1, [
            'term' => '',
            'definition' => 'greeting'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Term is required', $result['error']);
    }

    public function testUpdateEntryReturnsErrorWhenDefinitionEmpty(): void
    {
        $result = $this->handler->updateEntry(1, [
            'term' => 'hello',
            'definition' => ''
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Definition is required', $result['error']);
    }

    // =========================================================================
    // Format method tests (thin wrappers)
    // =========================================================================

    public function testFormatGetDictionariesDelegatesToGetDictionaries(): void
    {
        $this->facade->method('getAllForLanguage')->willReturn([]);
        $this->facade->method('getLocalDictMode')->willReturn(0);

        $result = $this->handler->formatGetDictionaries(1);

        $this->assertArrayHasKey('dictionaries', $result);
    }

    public function testFormatGetDictionaryDelegatesToGetDictionary(): void
    {
        $this->facade->method('getById')->willReturn(null);

        $result = $this->handler->formatGetDictionary(1);

        $this->assertArrayHasKey('error', $result);
    }

    public function testFormatCreateDictionaryDelegatesToCreateDictionary(): void
    {
        $result = $this->handler->formatCreateDictionary([]);

        $this->assertFalse($result['success']);
    }

    public function testFormatDeleteDictionaryDelegatesToDeleteDictionary(): void
    {
        $this->facade->method('delete')->willReturn(false);

        $result = $this->handler->formatDeleteDictionary(1);

        $this->assertFalse($result['success']);
    }

    public function testFormatLookupDelegatesToLookup(): void
    {
        $this->facade->method('lookup')->willReturn([]);
        $this->facade->method('getLocalDictMode')->willReturn(0);

        $result = $this->handler->formatLookup(1, 'test');

        $this->assertArrayHasKey('results', $result);
    }

    // =========================================================================
    // Helper methods
    // =========================================================================

    /**
     * Create a mock LocalDictionary object.
     *
     * @param int    $id     Dictionary ID
     * @param string $name   Dictionary name
     * @param int    $langId Language ID
     *
     * @return LocalDictionary&MockObject
     */
    private function createMockDictionary(int $id, string $name, int $langId): LocalDictionary
    {
        $dictionary = $this->createMock(LocalDictionary::class);
        $dictionary->method('id')->willReturn($id);
        $dictionary->method('name')->willReturn($name);
        $dictionary->method('languageId')->willReturn($langId);
        $dictionary->method('description')->willReturn(null);
        $dictionary->method('sourceFormat')->willReturn('csv');
        $dictionary->method('entryCount')->willReturn(0);
        $dictionary->method('priority')->willReturn(0);
        $dictionary->method('isEnabled')->willReturn(true);
        $dictionary->method('created')->willReturn(new \DateTimeImmutable());

        return $dictionary;
    }
}
