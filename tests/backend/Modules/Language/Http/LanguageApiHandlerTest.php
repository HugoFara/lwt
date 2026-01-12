<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Language\Http;

use Lwt\Modules\Language\Http\LanguageApiHandler;
use Lwt\Modules\Language\Application\LanguageFacade;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for LanguageApiHandler.
 *
 * Tests language API operations including reading configuration and phonetic reading.
 */
class LanguageApiHandlerTest extends TestCase
{
    /** @var LanguageFacade&MockObject */
    private LanguageFacade $languageFacade;

    private LanguageApiHandler $handler;

    protected function setUp(): void
    {
        $this->languageFacade = $this->createMock(LanguageFacade::class);
        $this->handler = new LanguageApiHandler($this->languageFacade);
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    public function testConstructorCreatesValidHandler(): void
    {
        $this->assertInstanceOf(LanguageApiHandler::class, $this->handler);
    }

    public function testConstructorAcceptsNullParameter(): void
    {
        $handler = new LanguageApiHandler(null);
        $this->assertInstanceOf(LanguageApiHandler::class, $handler);
    }

    // =========================================================================
    // getPhoneticReading tests
    // =========================================================================

    public function testGetPhoneticReadingByIdCallsFacade(): void
    {
        $this->languageFacade->expects($this->once())
            ->method('getPhoneticReadingById')
            ->with('hello', 1)
            ->willReturn('həˈloʊ');

        $result = $this->handler->getPhoneticReading('hello', 1);

        $this->assertArrayHasKey('phonetic_reading', $result);
        $this->assertSame('həˈloʊ', $result['phonetic_reading']);
    }

    public function testGetPhoneticReadingByCodeCallsFacade(): void
    {
        $this->languageFacade->expects($this->once())
            ->method('getPhoneticReadingByCode')
            ->with('hello', 'en')
            ->willReturn('həˈloʊ');

        $result = $this->handler->getPhoneticReading('hello', null, 'en');

        $this->assertArrayHasKey('phonetic_reading', $result);
        $this->assertSame('həˈloʊ', $result['phonetic_reading']);
    }

    public function testGetPhoneticReadingPrefersIdOverCode(): void
    {
        $this->languageFacade->expects($this->once())
            ->method('getPhoneticReadingById')
            ->with('hello', 5)
            ->willReturn('phonetic');
        $this->languageFacade->expects($this->never())
            ->method('getPhoneticReadingByCode');

        $this->handler->getPhoneticReading('hello', 5, 'en');
    }

    public function testGetPhoneticReadingHandlesEmptyCode(): void
    {
        $this->languageFacade->expects($this->once())
            ->method('getPhoneticReadingByCode')
            ->with('hello', '')
            ->willReturn('');

        $result = $this->handler->getPhoneticReading('hello', null, null);

        $this->assertSame('', $result['phonetic_reading']);
    }

    // =========================================================================
    // formatPhoneticReading tests
    // =========================================================================

    public function testFormatPhoneticReadingWithLanguageId(): void
    {
        $this->languageFacade->method('getPhoneticReadingById')
            ->willReturn('phonetic');

        $result = $this->handler->formatPhoneticReading([
            'text' => 'hello',
            'language_id' => 1
        ]);

        $this->assertSame('phonetic', $result['phonetic_reading']);
    }

    public function testFormatPhoneticReadingWithStringLanguageId(): void
    {
        $this->languageFacade->method('getPhoneticReadingById')
            ->willReturn('phonetic');

        $result = $this->handler->formatPhoneticReading([
            'text' => 'hello',
            'language_id' => '5'
        ]);

        $this->assertSame('phonetic', $result['phonetic_reading']);
    }

    public function testFormatPhoneticReadingWithLangCode(): void
    {
        $this->languageFacade->method('getPhoneticReadingByCode')
            ->willReturn('phonetic');

        $result = $this->handler->formatPhoneticReading([
            'text' => 'hello',
            'lang' => 'en'
        ]);

        $this->assertSame('phonetic', $result['phonetic_reading']);
    }

    public function testFormatPhoneticReadingWithMissingText(): void
    {
        $this->languageFacade->method('getPhoneticReadingByCode')
            ->with('', null)
            ->willReturn('');

        $result = $this->handler->formatPhoneticReading([]);

        $this->assertSame('', $result['phonetic_reading']);
    }

    public function testFormatPhoneticReadingPrefersLanguageIdOverLang(): void
    {
        $this->languageFacade->expects($this->once())
            ->method('getPhoneticReadingById')
            ->willReturn('by_id');
        $this->languageFacade->expects($this->never())
            ->method('getPhoneticReadingByCode');

        $result = $this->handler->formatPhoneticReading([
            'text' => 'hello',
            'language_id' => 1,
            'lang' => 'en'
        ]);

        $this->assertSame('by_id', $result['phonetic_reading']);
    }

    // =========================================================================
    // formatReadingConfiguration tests (thin wrapper)
    // =========================================================================

    /**
     * @group integration
     */
    public function testFormatReadingConfigurationDelegatesToGetReadingConfiguration(): void
    {
        // This test requires database access with full schema
        try {
            $result = $this->handler->formatReadingConfiguration(1);
        } catch (\Lwt\Core\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('voiceapi', $result);
        $this->assertArrayHasKey('word_parsing', $result);
        $this->assertArrayHasKey('abbreviation', $result);
        $this->assertArrayHasKey('reading_mode', $result);
    }

    // =========================================================================
    // getReadingConfiguration tests (structure validation)
    // =========================================================================

    /**
     * @group integration
     */
    public function testGetReadingConfigurationReturnsExpectedStructure(): void
    {
        // This test requires database access with full schema
        try {
            $result = $this->handler->getReadingConfiguration(999);
        } catch (\Lwt\Core\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('voiceapi', $result);
        $this->assertArrayHasKey('word_parsing', $result);
        $this->assertArrayHasKey('abbreviation', $result);
        $this->assertArrayHasKey('reading_mode', $result);
    }

    /**
     * @group integration
     */
    public function testGetReadingConfigurationReturnsEmptyForNonexistentLanguage(): void
    {
        // This test requires database access with full schema
        try {
            $result = $this->handler->getReadingConfiguration(0);
        } catch (\Lwt\Core\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }

        $this->assertSame('', $result['name']);
        $this->assertSame('direct', $result['reading_mode']);
    }

    // =========================================================================
    // getSimilarTerms tests
    // =========================================================================

    /**
     * @group integration
     */
    public function testGetSimilarTermsReturnsExpectedStructure(): void
    {
        try {
            $result = $this->handler->getSimilarTerms(1, 'test');
        } catch (\Lwt\Core\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }

        $this->assertIsArray($result);
        $this->assertArrayHasKey('similar_terms', $result);
        $this->assertIsString($result['similar_terms']);
    }

    /**
     * @group integration
     */
    public function testFormatSimilarTermsDelegatesToGetSimilarTerms(): void
    {
        try {
            $result = $this->handler->formatSimilarTerms(1, 'test');
        } catch (\Lwt\Core\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }

        $this->assertIsArray($result);
        $this->assertArrayHasKey('similar_terms', $result);
    }

    // =========================================================================
    // getSentencesWithTerm tests
    // =========================================================================

    /**
     * @group integration
     */
    public function testGetSentencesWithTermReturnsArray(): void
    {
        try {
            $result = $this->handler->getSentencesWithTerm(1, 'test', null);
        } catch (\Lwt\Core\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }

        $this->assertIsArray($result);
    }

    /**
     * @group integration
     */
    public function testGetSentencesWithTermWithWordId(): void
    {
        try {
            $result = $this->handler->getSentencesWithTerm(1, 'test', 1);
        } catch (\Lwt\Core\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }

        $this->assertIsArray($result);
    }

    /**
     * @group integration
     */
    public function testFormatSentencesWithRegisteredTermDelegates(): void
    {
        try {
            $result = $this->handler->formatSentencesWithRegisteredTerm(1, 'test', 1);
        } catch (\Lwt\Core\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }

        $this->assertIsArray($result);
    }

    /**
     * @group integration
     */
    public function testFormatSentencesWithNewTermDelegates(): void
    {
        try {
            $result = $this->handler->formatSentencesWithNewTerm(1, 'test');
        } catch (\Lwt\Core\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }

        $this->assertIsArray($result);
    }

    /**
     * @group integration
     */
    public function testFormatSentencesWithNewTermAdvancedSearch(): void
    {
        try {
            $result = $this->handler->formatSentencesWithNewTerm(1, 'test', true);
        } catch (\Lwt\Core\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }

        $this->assertIsArray($result);
    }

    // =========================================================================
    // formatLanguagesWithTexts tests
    // =========================================================================

    public function testFormatLanguagesWithTextsReturnsExpectedStructure(): void
    {
        $this->languageFacade->method('getLanguagesWithTextCounts')
            ->willReturn([
                ['id' => 1, 'name' => 'English', 'text_count' => 5]
            ]);

        $result = $this->handler->formatLanguagesWithTexts();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('languages', $result);
        $this->assertIsArray($result['languages']);
    }

    // =========================================================================
    // formatLanguagesWithArchivedTexts tests
    // =========================================================================

    public function testFormatLanguagesWithArchivedTextsReturnsExpectedStructure(): void
    {
        $this->languageFacade->method('getLanguagesWithArchivedTextCounts')
            ->willReturn([
                ['id' => 1, 'name' => 'English', 'text_count' => 3]
            ]);

        $result = $this->handler->formatLanguagesWithArchivedTexts();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('languages', $result);
        $this->assertIsArray($result['languages']);
    }

    // =========================================================================
    // formatGetAll tests
    // =========================================================================

    public function testFormatGetAllReturnsExpectedStructure(): void
    {
        $this->languageFacade->method('getLanguagesWithStats')
            ->willReturn([]);

        try {
            $result = $this->handler->formatGetAll();
        } catch (\Lwt\Core\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }

        $this->assertIsArray($result);
        $this->assertArrayHasKey('languages', $result);
        $this->assertArrayHasKey('currentLanguageId', $result);
    }

    // =========================================================================
    // formatGetOne tests
    // =========================================================================

    public function testFormatGetOneReturnsNullForNonExistent(): void
    {
        $this->languageFacade->method('getById')
            ->with(999999)
            ->willReturn(null);

        $result = $this->handler->formatGetOne(999999);

        $this->assertNull($result);
    }

    // =========================================================================
    // formatCreate tests
    // =========================================================================

    public function testFormatCreateReturnsErrorForEmptyName(): void
    {
        $result = $this->handler->formatCreate(['name' => '']);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('Language name is required', $result['error']);
    }

    public function testFormatCreateReturnsErrorForMissingName(): void
    {
        $result = $this->handler->formatCreate([]);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('Language name is required', $result['error']);
    }

    public function testFormatCreateReturnsErrorForDuplicateName(): void
    {
        $this->languageFacade->method('isDuplicateName')
            ->with('English')
            ->willReturn(true);

        $result = $this->handler->formatCreate(['name' => 'English']);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('A language with this name already exists', $result['error']);
    }

    public function testFormatCreateReturnsSuccessOnValidData(): void
    {
        $this->languageFacade->method('isDuplicateName')
            ->willReturn(false);
        $this->languageFacade->method('createFromData')
            ->willReturn(1);

        $result = $this->handler->formatCreate(['name' => 'New Language']);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['id']);
    }

    public function testFormatCreateReturnsErrorOnFailure(): void
    {
        $this->languageFacade->method('isDuplicateName')
            ->willReturn(false);
        $this->languageFacade->method('createFromData')
            ->willReturn(0);

        $result = $this->handler->formatCreate(['name' => 'New Language']);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('Failed to create language', $result['error']);
    }

    // =========================================================================
    // formatUpdate tests
    // =========================================================================

    public function testFormatUpdateReturnsErrorForNonExistent(): void
    {
        $this->languageFacade->method('getById')
            ->with(999999)
            ->willReturn(null);

        $result = $this->handler->formatUpdate(999999, ['name' => 'Test']);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('Language not found', $result['error']);
    }

    // =========================================================================
    // formatDelete tests
    // =========================================================================

    public function testFormatDeleteReturnsErrorWhenCannotDelete(): void
    {
        $this->languageFacade->method('canDelete')
            ->with(1)
            ->willReturn(false);
        $this->languageFacade->method('getRelatedDataCounts')
            ->with(1)
            ->willReturn(['texts' => 5, 'archivedTexts' => 0, 'words' => 100, 'feeds' => 0]);

        $result = $this->handler->formatDelete(1);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('Cannot delete language with existing data', $result['error']);
        $this->assertArrayHasKey('relatedData', $result);
    }

    public function testFormatDeleteReturnsSuccessWhenCanDelete(): void
    {
        $this->languageFacade->method('canDelete')
            ->with(1)
            ->willReturn(true);
        $this->languageFacade->method('deleteById')
            ->with(1)
            ->willReturn(true);

        $result = $this->handler->formatDelete(1);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
    }

    // =========================================================================
    // formatGetStats tests
    // =========================================================================

    public function testFormatGetStatsReturnsExpectedStructure(): void
    {
        $this->languageFacade->method('getRelatedDataCounts')
            ->with(1)
            ->willReturn(['texts' => 5, 'archivedTexts' => 2, 'words' => 100, 'feeds' => 1]);

        $result = $this->handler->formatGetStats(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('texts', $result);
        $this->assertArrayHasKey('archivedTexts', $result);
        $this->assertArrayHasKey('words', $result);
        $this->assertArrayHasKey('feeds', $result);
    }

    // =========================================================================
    // formatRefresh tests
    // =========================================================================

    public function testFormatRefreshReturnsExpectedStructure(): void
    {
        $this->languageFacade->method('refreshTexts')
            ->with(1)
            ->willReturn([
                'sentencesDeleted' => 10,
                'textItemsDeleted' => 50,
                'sentencesAdded' => 12,
                'textItemsAdded' => 55
            ]);

        $result = $this->handler->formatRefresh(1);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('sentencesDeleted', $result);
        $this->assertArrayHasKey('textItemsDeleted', $result);
        $this->assertArrayHasKey('sentencesAdded', $result);
        $this->assertArrayHasKey('textItemsAdded', $result);
    }

    // =========================================================================
    // formatGetDefinitions tests
    // =========================================================================

    public function testFormatGetDefinitionsReturnsExpectedStructure(): void
    {
        $result = $this->handler->formatGetDefinitions();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('definitions', $result);
        $this->assertIsArray($result['definitions']);
    }

    // =========================================================================
    // formatSetDefault tests
    // =========================================================================

    /**
     * @group integration
     */
    public function testFormatSetDefaultReturnsSuccess(): void
    {
        try {
            $result = $this->handler->formatSetDefault(1);
        } catch (\Lwt\Core\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
    }
}
