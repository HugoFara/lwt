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
}
