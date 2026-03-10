<?php

declare(strict_types=1);

namespace Tests\Backend\Modules\Vocabulary\Application\Services;

use PHPUnit\Framework\TestCase;
use Lwt\Modules\Vocabulary\Application\Services\LemmatizerManager;
use Lwt\Modules\Vocabulary\Domain\LemmatizerInterface;

/**
 * Unit tests for LemmatizerManager.
 *
 * Tests lemmatizer instantiation and NLP availability checks.
 */
class LemmatizerManagerTest extends TestCase
{
    private LemmatizerManager $manager;
    private LemmatizerInterface $mockLemmatizer;

    protected function setUp(): void
    {
        $this->mockLemmatizer = $this->createMock(LemmatizerInterface::class);
        $this->manager = new LemmatizerManager($this->mockLemmatizer);
    }

    // =========================================================================
    // getLemmatizerForLanguage Tests
    // =========================================================================

    public function testGetLemmatizerForLanguageReturnsLemmatizer(): void
    {
        $result = $this->manager->getLemmatizerForLanguage('en');

        $this->assertInstanceOf(LemmatizerInterface::class, $result);
    }

    public function testGetLemmatizerForLanguageWithUnknownCode(): void
    {
        $result = $this->manager->getLemmatizerForLanguage('unknown');

        $this->assertInstanceOf(LemmatizerInterface::class, $result);
    }

    public function testGetLemmatizerForLanguageWithEmptyCode(): void
    {
        $result = $this->manager->getLemmatizerForLanguage('');

        $this->assertInstanceOf(LemmatizerInterface::class, $result);
    }

    // =========================================================================
    // getLemmatizerByType Tests
    // =========================================================================

    public function testGetLemmatizerByTypeDictionary(): void
    {
        $result = $this->manager->getLemmatizerByType('dictionary');

        $this->assertInstanceOf(LemmatizerInterface::class, $result);
    }

    public function testGetLemmatizerByTypeSpacy(): void
    {
        $result = $this->manager->getLemmatizerByType('spacy');

        $this->assertInstanceOf(LemmatizerInterface::class, $result);
    }

    public function testGetLemmatizerByTypeHybrid(): void
    {
        $result = $this->manager->getLemmatizerByType('hybrid');

        $this->assertInstanceOf(LemmatizerInterface::class, $result);
    }

    public function testGetLemmatizerByTypeDefaultFallback(): void
    {
        $result = $this->manager->getLemmatizerByType('nonexistent');

        $this->assertInstanceOf(LemmatizerInterface::class, $result);
    }

    // =========================================================================
    // isNlpServiceAvailable Tests
    // =========================================================================

    public function testIsNlpServiceAvailableReturnsBool(): void
    {
        $result = $this->manager->isNlpServiceAvailable();

        $this->assertIsBool($result);
    }

    // =========================================================================
    // getNlpSupportedLanguages Tests
    // =========================================================================

    public function testGetNlpSupportedLanguagesReturnsArray(): void
    {
        $result = $this->manager->getNlpSupportedLanguages();

        $this->assertIsArray($result);
    }

    // =========================================================================
    // getAllNlpLanguages Tests
    // =========================================================================

    public function testGetAllNlpLanguagesReturnsArray(): void
    {
        $result = $this->manager->getAllNlpLanguages();

        $this->assertIsArray($result);
    }

    public function testGetAllNlpLanguagesContainsStrings(): void
    {
        $result = $this->manager->getAllNlpLanguages();

        foreach ($result as $lang) {
            $this->assertIsString($lang);
        }
    }

    // =========================================================================
    // isAvailableForLanguage Tests
    // =========================================================================

    public function testIsAvailableForLanguageReturnsTrue(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('supportsLanguage')
            ->with('en')
            ->willReturn(true);

        $result = $this->manager->isAvailableForLanguage('en');

        $this->assertTrue($result);
    }

    public function testIsAvailableForLanguageReturnsFalse(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('supportsLanguage')
            ->with('unknown')
            ->willReturn(false);

        $result = $this->manager->isAvailableForLanguage('unknown');

        $this->assertFalse($result);
    }

    public function testIsAvailableForLanguageWithEmptyCode(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('supportsLanguage')
            ->with('')
            ->willReturn(false);

        $result = $this->manager->isAvailableForLanguage('');

        $this->assertFalse($result);
    }

    public function testIsAvailableForLanguageDelegatesToLemmatizer(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('supportsLanguage')
            ->with('de')
            ->willReturn(true);

        $this->manager->isAvailableForLanguage('de');
    }

    // =========================================================================
    // getAvailableLanguages Tests
    // =========================================================================

    public function testGetAvailableLanguagesReturnsArray(): void
    {
        $expected = ['en', 'de', 'fr'];

        $this->mockLemmatizer
            ->expects($this->once())
            ->method('getSupportedLanguages')
            ->willReturn($expected);

        $result = $this->manager->getAvailableLanguages();

        $this->assertSame($expected, $result);
    }

    public function testGetAvailableLanguagesReturnsEmptyWhenNone(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('getSupportedLanguages')
            ->willReturn([]);

        $result = $this->manager->getAvailableLanguages();

        $this->assertSame([], $result);
    }

    public function testGetAvailableLanguagesReturnsLargeList(): void
    {
        $expected = ['en', 'de', 'fr', 'es', 'it', 'pt', 'ru', 'ja', 'zh', 'ko'];

        $this->mockLemmatizer
            ->expects($this->once())
            ->method('getSupportedLanguages')
            ->willReturn($expected);

        $result = $this->manager->getAvailableLanguages();

        $this->assertCount(10, $result);
    }

    public function testGetAvailableLanguagesDelegatesToLemmatizer(): void
    {
        $this->mockLemmatizer
            ->expects($this->once())
            ->method('getSupportedLanguages')
            ->willReturn(['en']);

        $this->manager->getAvailableLanguages();
    }

    // =========================================================================
    // Constructor Tests
    // =========================================================================

    public function testConstructorAcceptsLemmatizerInterface(): void
    {
        $lemmatizer = $this->createMock(LemmatizerInterface::class);
        $manager = new LemmatizerManager($lemmatizer);

        $this->assertInstanceOf(LemmatizerManager::class, $manager);
    }
}
