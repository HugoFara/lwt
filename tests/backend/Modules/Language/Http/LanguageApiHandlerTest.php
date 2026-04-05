<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Language\Http;

use Lwt\Modules\Language\Http\LanguageApiHandler;
use Lwt\Modules\Language\Application\LanguageFacade;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\Group;

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
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required');
        }
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
    #[Group('integration')]
    public function testFormatReadingConfigurationDelegatesToGetReadingConfiguration(): void
    {
        // This test requires database access with full schema
        try {
            $result = $this->handler->formatReadingConfiguration(1);
        } catch (\Lwt\Shared\Infrastructure\Exception\DatabaseException $e) {
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
    #[Group('integration')]
    public function testGetReadingConfigurationReturnsExpectedStructure(): void
    {
        // This test requires database access with full schema
        try {
            $result = $this->handler->getReadingConfiguration(999);
        } catch (\Lwt\Shared\Infrastructure\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('voiceapi', $result);
        $this->assertArrayHasKey('word_parsing', $result);
        $this->assertArrayHasKey('abbreviation', $result);
        $this->assertArrayHasKey('reading_mode', $result);
    }
    #[Group('integration')]
    public function testGetReadingConfigurationReturnsEmptyForNonexistentLanguage(): void
    {
        // This test requires database access with full schema
        try {
            $result = $this->handler->getReadingConfiguration(0);
        } catch (\Lwt\Shared\Infrastructure\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }

        $this->assertSame('', $result['name']);
        $this->assertSame('direct', $result['reading_mode']);
    }

    // =========================================================================
    // getSimilarTerms tests
    // =========================================================================
    #[Group('integration')]
    public function testGetSimilarTermsReturnsExpectedStructure(): void
    {
        try {
            $result = $this->handler->getSimilarTerms(1, 'test');
        } catch (\Lwt\Shared\Infrastructure\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }

        $this->assertIsArray($result);
        $this->assertArrayHasKey('similar_terms', $result);
        $this->assertIsString($result['similar_terms']);
    }
    #[Group('integration')]
    public function testFormatSimilarTermsDelegatesToGetSimilarTerms(): void
    {
        try {
            $result = $this->handler->formatSimilarTerms(1, 'test');
        } catch (\Lwt\Shared\Infrastructure\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }

        $this->assertIsArray($result);
        $this->assertArrayHasKey('similar_terms', $result);
    }

    // =========================================================================
    // getSentencesWithTerm tests
    // =========================================================================
    #[Group('integration')]
    public function testGetSentencesWithTermReturnsArray(): void
    {
        try {
            $result = $this->handler->getSentencesWithTerm(1, 'test', null);
        } catch (\Lwt\Shared\Infrastructure\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }

        $this->assertIsArray($result);
    }
    #[Group('integration')]
    public function testGetSentencesWithTermWithWordId(): void
    {
        try {
            $result = $this->handler->getSentencesWithTerm(1, 'test', 1);
        } catch (\Lwt\Shared\Infrastructure\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }

        $this->assertIsArray($result);
    }
    #[Group('integration')]
    public function testFormatSentencesWithRegisteredTermDelegates(): void
    {
        try {
            $result = $this->handler->formatSentencesWithRegisteredTerm(1, 'test', 1);
        } catch (\Lwt\Shared\Infrastructure\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }

        $this->assertIsArray($result);
    }
    #[Group('integration')]
    public function testFormatSentencesWithNewTermDelegates(): void
    {
        try {
            $result = $this->handler->formatSentencesWithNewTerm(1, 'test');
        } catch (\Lwt\Shared\Infrastructure\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }

        $this->assertIsArray($result);
    }
    #[Group('integration')]
    public function testFormatSentencesWithNewTermAdvancedSearch(): void
    {
        try {
            $result = $this->handler->formatSentencesWithNewTerm(1, 'test', true);
        } catch (\Lwt\Shared\Infrastructure\Exception\DatabaseException $e) {
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
        } catch (\Lwt\Shared\Infrastructure\Exception\DatabaseException $e) {
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
    #[Group('integration')]
    public function testFormatSetDefaultReturnsSuccess(): void
    {
        try {
            $result = $this->handler->formatSetDefault(1);
        } catch (\Lwt\Shared\Infrastructure\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
    }

    // =========================================================================
    // formatGetOne with real Language entity tests
    // =========================================================================

    public function testFormatGetOneReturnsLanguageDataForExistingLanguage(): void
    {
        $language = \Lwt\Modules\Language\Domain\Language::reconstitute(
            1,
            'English',
            'https://dict.example.com/###',
            'https://dict2.example.com/###',
            'https://translate.example.com/###',
            true,
            false,
            true,
            'en',
            'fr',
            '$w\\t$t',
            150,
            'áa-éeíi',
            '.!?',
            '',
            'a-zA-Z',
            false,
            false,
            false,
            'https://tts.example.com',
            true
        );

        $this->languageFacade->method('getById')
            ->with(1)
            ->willReturn($language);

        $this->languageFacade->method('getAllLanguages')
            ->willReturn([]);

        $result = $this->handler->formatGetOne(1);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('language', $result);
        $this->assertSame(1, $result['language']['id']);
        $this->assertSame('English', $result['language']['name']);
        $this->assertSame('https://dict.example.com/###', $result['language']['dict1Uri']);
        $this->assertSame('https://dict2.example.com/###', $result['language']['dict2Uri']);
        $this->assertSame('https://translate.example.com/###', $result['language']['translatorUri']);
        $this->assertSame('$w\\t$t', $result['language']['exportTemplate']);
        $this->assertSame(150, $result['language']['textSize']);
        $this->assertSame('áa-éeíi', $result['language']['characterSubstitutions']);
        $this->assertSame('.!?', $result['language']['regexpSplitSentences']);
        $this->assertSame('a-zA-Z', $result['language']['regexpWordCharacters']);
        $this->assertFalse($result['language']['removeSpaces']);
        $this->assertFalse($result['language']['splitEachChar']);
        $this->assertFalse($result['language']['rightToLeft']);
        $this->assertSame('https://tts.example.com', $result['language']['ttsVoiceApi']);
        $this->assertTrue($result['language']['showRomanization']);
        $this->assertArrayHasKey('allLanguages', $result);
    }

    // =========================================================================
    // formatUpdate with existing language tests
    // =========================================================================

    public function testFormatUpdateReturnsErrorForEmptyName(): void
    {
        $language = \Lwt\Modules\Language\Domain\Language::reconstitute(
            1, 'English', '', '', '', false, false, false,
            null, null, '', 100, '', '.!?', '', 'a-z',
            false, false, false, '', true
        );

        $this->languageFacade->method('getById')
            ->with(1)
            ->willReturn($language);

        $result = $this->handler->formatUpdate(1, ['name' => '']);

        $this->assertFalse($result['success']);
        $this->assertSame('Language name is required', $result['error']);
    }

    public function testFormatUpdateReturnsErrorForDuplicateName(): void
    {
        $language = \Lwt\Modules\Language\Domain\Language::reconstitute(
            1, 'English', '', '', '', false, false, false,
            null, null, '', 100, '', '.!?', '', 'a-z',
            false, false, false, '', true
        );

        $this->languageFacade->method('getById')
            ->with(1)
            ->willReturn($language);

        $this->languageFacade->method('isDuplicateName')
            ->with('French', 1)
            ->willReturn(true);

        $result = $this->handler->formatUpdate(1, ['name' => 'French']);

        $this->assertFalse($result['success']);
        $this->assertSame('A language with this name already exists', $result['error']);
    }

    public function testFormatUpdateReturnsSuccessOnValidData(): void
    {
        $language = \Lwt\Modules\Language\Domain\Language::reconstitute(
            1, 'English', '', '', '', false, false, false,
            null, null, '', 100, '', '.!?', '', 'a-z',
            false, false, false, '', true
        );

        $this->languageFacade->method('getById')
            ->with(1)
            ->willReturn($language);

        $this->languageFacade->method('isDuplicateName')
            ->willReturn(false);

        $this->languageFacade->method('updateFromData')
            ->with(1, ['name' => 'English Updated'])
            ->willReturn(['reparsed' => 5, 'message' => 'Reparsed 5 texts']);

        $result = $this->handler->formatUpdate(1, ['name' => 'English Updated']);

        $this->assertTrue($result['success']);
        $this->assertSame(5, $result['reparsed']);
        $this->assertSame('Reparsed 5 texts', $result['message']);
    }

    public function testFormatUpdateMissingNameKey(): void
    {
        $language = \Lwt\Modules\Language\Domain\Language::reconstitute(
            1, 'English', '', '', '', false, false, false,
            null, null, '', 100, '', '.!?', '', 'a-z',
            false, false, false, '', true
        );

        $this->languageFacade->method('getById')
            ->with(1)
            ->willReturn($language);

        $result = $this->handler->formatUpdate(1, []);

        $this->assertFalse($result['success']);
        $this->assertSame('Language name is required', $result['error']);
    }

    public function testFormatUpdateNameIsNotString(): void
    {
        $language = \Lwt\Modules\Language\Domain\Language::reconstitute(
            1, 'English', '', '', '', false, false, false,
            null, null, '', 100, '', '.!?', '', 'a-z',
            false, false, false, '', true
        );

        $this->languageFacade->method('getById')
            ->with(1)
            ->willReturn($language);

        $result = $this->handler->formatUpdate(1, ['name' => 123]);

        $this->assertFalse($result['success']);
        $this->assertSame('Language name is required', $result['error']);
    }

    // =========================================================================
    // formatCreate additional tests
    // =========================================================================

    public function testFormatCreateNameIsNotString(): void
    {
        $result = $this->handler->formatCreate(['name' => 42]);

        $this->assertFalse($result['success']);
        $this->assertSame('Language name is required', $result['error']);
    }

    public function testFormatCreateNullName(): void
    {
        $result = $this->handler->formatCreate(['name' => null]);

        $this->assertFalse($result['success']);
        $this->assertSame('Language name is required', $result['error']);
    }

    public function testFormatCreateCallsCreateFromDataWithFullPayload(): void
    {
        $data = [
            'name' => 'Spanish',
            'dict1Uri' => 'https://example.com',
            'regexpSplitSentences' => '.!?',
            'regexpWordCharacters' => 'a-zA-Z'
        ];

        $this->languageFacade->method('isDuplicateName')
            ->with('Spanish')
            ->willReturn(false);

        $this->languageFacade->expects($this->once())
            ->method('createFromData')
            ->with($data)
            ->willReturn(5);

        $result = $this->handler->formatCreate($data);

        $this->assertTrue($result['success']);
        $this->assertSame(5, $result['id']);
    }

    // =========================================================================
    // formatDelete additional tests
    // =========================================================================

    public function testFormatDeleteReturnsFalseOnDeleteFailure(): void
    {
        $this->languageFacade->method('canDelete')
            ->with(1)
            ->willReturn(true);
        $this->languageFacade->method('deleteById')
            ->with(1)
            ->willReturn(false);

        $result = $this->handler->formatDelete(1);

        $this->assertFalse($result['success']);
    }

    public function testFormatDeleteRelatedDataIncludesAllCounts(): void
    {
        $this->languageFacade->method('canDelete')
            ->with(2)
            ->willReturn(false);
        $this->languageFacade->method('getRelatedDataCounts')
            ->with(2)
            ->willReturn([
                'texts' => 10,
                'archivedTexts' => 5,
                'words' => 200,
                'feeds' => 3
            ]);

        $result = $this->handler->formatDelete(2);

        $this->assertFalse($result['success']);
        $this->assertSame(10, $result['relatedData']['texts']);
        $this->assertSame(5, $result['relatedData']['archivedTexts']);
        $this->assertSame(200, $result['relatedData']['words']);
        $this->assertSame(3, $result['relatedData']['feeds']);
    }

    // =========================================================================
    // formatRefresh additional tests
    // =========================================================================

    public function testFormatRefreshReturnsCorrectCounts(): void
    {
        $this->languageFacade->method('refreshTexts')
            ->with(3)
            ->willReturn([
                'sentencesDeleted' => 20,
                'textItemsDeleted' => 100,
                'sentencesAdded' => 25,
                'textItemsAdded' => 110
            ]);

        $result = $this->handler->formatRefresh(3);

        $this->assertTrue($result['success']);
        $this->assertSame(20, $result['sentencesDeleted']);
        $this->assertSame(100, $result['textItemsDeleted']);
        $this->assertSame(25, $result['sentencesAdded']);
        $this->assertSame(110, $result['textItemsAdded']);
    }

    // =========================================================================
    // formatGetDefinitions additional tests
    // =========================================================================

    public function testFormatGetDefinitionsHasExpectedFieldsPerEntry(): void
    {
        $result = $this->handler->formatGetDefinitions();

        if (empty($result['definitions'])) {
            $this->markTestSkipped('No language presets available');
        }

        $first = reset($result['definitions']);
        $this->assertArrayHasKey('glosbeIso', $first);
        $this->assertArrayHasKey('googleIso', $first);
        $this->assertArrayHasKey('biggerFont', $first);
        $this->assertArrayHasKey('wordCharRegExp', $first);
        $this->assertArrayHasKey('sentSplRegExp', $first);
        $this->assertArrayHasKey('makeCharacterWord', $first);
        $this->assertArrayHasKey('removeSpaces', $first);
        $this->assertArrayHasKey('rightToLeft', $first);
    }

    // =========================================================================
    // formatSentencesWithNewTerm tests
    // =========================================================================

    public function testFormatSentencesWithNewTermPassesNullWordIdByDefault(): void
    {
        // Verify that without advanced search, wordId is null
        // This is a structural test - the method calls getSentencesWithTerm with null
        $reflection = new \ReflectionMethod(LanguageApiHandler::class, 'formatSentencesWithNewTerm');
        $params = $reflection->getParameters();

        $this->assertCount(3, $params);
        $this->assertSame('langId', $params[0]->getName());
        $this->assertSame('wordLc', $params[1]->getName());
        $this->assertSame('advancedSearch', $params[2]->getName());
        $this->assertTrue($params[2]->isOptional());
        $this->assertFalse($params[2]->getDefaultValue());
    }

    // =========================================================================
    // routeGet tests
    // =========================================================================

    public function testRouteGetEmptyFragmentReturnsAllLanguages(): void
    {
        $this->languageFacade->method('getLanguagesWithStats')
            ->willReturn([]);

        try {
            $result = $this->handler->routeGet(['languages', ''], []);
        } catch (\Lwt\Shared\Infrastructure\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    public function testRouteGetDefinitionsReturnsJsonResponse(): void
    {
        $result = $this->handler->routeGet(['languages', 'definitions'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    public function testRouteGetWithTextsReturnsJsonResponse(): void
    {
        $this->languageFacade->method('getLanguagesWithTextCounts')
            ->willReturn([]);

        $result = $this->handler->routeGet(['languages', 'with-texts'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    public function testRouteGetWithArchivedTextsReturnsJsonResponse(): void
    {
        $this->languageFacade->method('getLanguagesWithArchivedTextCounts')
            ->willReturn([]);

        $result = $this->handler->routeGet(['languages', 'with-archived-texts'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    public function testRouteGetNonDigitNonKeywordReturns404(): void
    {
        $result = $this->handler->routeGet(['languages', 'invalid'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
        $this->assertSame(404, $result->getStatusCode());
    }

    public function testRouteGetLanguageByIdReturnsData(): void
    {
        $language = \Lwt\Modules\Language\Domain\Language::reconstitute(
            1, 'English', '', '', '', false, false, false,
            null, null, '', 100, '', '.!?', '', 'a-z',
            false, false, false, '', true
        );

        $this->languageFacade->method('getById')
            ->with(1)
            ->willReturn($language);
        $this->languageFacade->method('getAllLanguages')
            ->willReturn([]);

        $result = $this->handler->routeGet(['languages', '1', ''], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    public function testRouteGetLanguageByIdNotFoundReturns404(): void
    {
        $this->languageFacade->method('getById')
            ->with(999)
            ->willReturn(null);

        $result = $this->handler->routeGet(['languages', '999', ''], []);

        $this->assertSame(404, $result->getStatusCode());
    }

    public function testRouteGetStatsSubpathReturnsData(): void
    {
        $this->languageFacade->method('getRelatedDataCounts')
            ->with(1)
            ->willReturn(['texts' => 0, 'archivedTexts' => 0, 'words' => 0, 'feeds' => 0]);

        $result = $this->handler->routeGet(['languages', '1', 'stats'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    public function testRouteGetUnknownSubpathReturns404(): void
    {
        $result = $this->handler->routeGet(['languages', '1', 'unknown'], []);

        $this->assertSame(404, $result->getStatusCode());
    }

    // =========================================================================
    // routePost tests
    // =========================================================================

    public function testRoutePostEmptyFragmentCreatesLanguage(): void
    {
        $this->languageFacade->method('isDuplicateName')->willReturn(false);
        $this->languageFacade->method('createFromData')->willReturn(1);

        $result = $this->handler->routePost(['languages', ''], ['name' => 'Test']);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    public function testRoutePostRefreshSubpath(): void
    {
        $this->languageFacade->method('refreshTexts')
            ->willReturn([
                'sentencesDeleted' => 0,
                'textItemsDeleted' => 0,
                'sentencesAdded' => 0,
                'textItemsAdded' => 0
            ]);

        $result = $this->handler->routePost(['languages', '1', 'refresh'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    public function testRoutePostSetDefaultSubpath(): void
    {
        try {
            $result = $this->handler->routePost(['languages', '1', 'set-default'], []);
        } catch (\Lwt\Shared\Infrastructure\Exception\DatabaseException $e) {
            $this->markTestSkipped('Database schema not compatible: ' . $e->getMessage());
        }

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    public function testRoutePostUnknownSubpathReturns404(): void
    {
        $result = $this->handler->routePost(['languages', '1', 'invalid'], []);

        $this->assertSame(404, $result->getStatusCode());
    }

    public function testRoutePostNonDigitIdReturns404(): void
    {
        $result = $this->handler->routePost(['languages', 'abc'], []);

        $this->assertSame(404, $result->getStatusCode());
    }

    // =========================================================================
    // routePut tests
    // =========================================================================

    public function testRoutePutUpdatesLanguage(): void
    {
        $language = \Lwt\Modules\Language\Domain\Language::reconstitute(
            1, 'English', '', '', '', false, false, false,
            null, null, '', 100, '', '.!?', '', 'a-z',
            false, false, false, '', true
        );

        $this->languageFacade->method('getById')->willReturn($language);
        $this->languageFacade->method('isDuplicateName')->willReturn(false);
        $this->languageFacade->method('updateFromData')
            ->willReturn(['reparsed' => 0, 'message' => '']);

        $result = $this->handler->routePut(['languages', '1', ''], ['name' => 'English']);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    public function testRoutePutEmptyIdReturns404(): void
    {
        $result = $this->handler->routePut(['languages', ''], []);

        $this->assertSame(404, $result->getStatusCode());
    }

    public function testRoutePutNonDigitIdReturns404(): void
    {
        $result = $this->handler->routePut(['languages', 'abc'], []);

        $this->assertSame(404, $result->getStatusCode());
    }

    public function testRoutePutWithSubpathReturns404(): void
    {
        $result = $this->handler->routePut(['languages', '1', 'extra'], []);

        $this->assertSame(404, $result->getStatusCode());
    }

    // =========================================================================
    // routeDelete tests
    // =========================================================================

    public function testRouteDeleteCallsFormatDelete(): void
    {
        $this->languageFacade->method('canDelete')->willReturn(true);
        $this->languageFacade->method('deleteById')->willReturn(true);

        $result = $this->handler->routeDelete(['languages', '1'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    public function testRouteDeleteEmptyIdReturns404(): void
    {
        $result = $this->handler->routeDelete(['languages', ''], []);

        $this->assertSame(404, $result->getStatusCode());
    }

    public function testRouteDeleteNonDigitIdReturns404(): void
    {
        $result = $this->handler->routeDelete(['languages', 'abc'], []);

        $this->assertSame(404, $result->getStatusCode());
    }

    // =========================================================================
    // Class structure tests
    // =========================================================================

    public function testClassImplementsApiRoutableInterface(): void
    {
        $reflection = new \ReflectionClass(LanguageApiHandler::class);
        $this->assertTrue($reflection->implementsInterface(\Lwt\Shared\Http\ApiRoutableInterface::class));
    }

    public function testClassHasRequiredPublicMethods(): void
    {
        $reflection = new \ReflectionClass(LanguageApiHandler::class);

        $expectedMethods = [
            'getReadingConfiguration',
            'getPhoneticReading',
            'getSimilarTerms',
            'getSentencesWithTerm',
            'formatReadingConfiguration',
            'formatPhoneticReading',
            'formatSimilarTerms',
            'formatSentencesWithRegisteredTerm',
            'formatSentencesWithNewTerm',
            'formatLanguagesWithTexts',
            'formatLanguagesWithArchivedTexts',
            'formatGetAll',
            'formatGetOne',
            'formatCreate',
            'formatUpdate',
            'formatDelete',
            'formatGetStats',
            'formatRefresh',
            'formatGetDefinitions',
            'formatSetDefault',
            'routeGet',
            'routePost',
            'routePut',
            'routeDelete',
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "LanguageApiHandler should have method: $methodName"
            );
        }
    }
}
