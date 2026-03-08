<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Application\Services;

use Lwt\Modules\Vocabulary\Application\Services\FrequencyLanguageMap;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

class FrequencyLanguageMapTest extends TestCase
{
    protected function setUp(): void
    {
        FrequencyLanguageMap::reset();
    }

    #[Test]
    public function isSupportedReturnsTrueForSpanish(): void
    {
        $this->assertTrue(FrequencyLanguageMap::isSupported('Spanish'));
    }

    #[Test]
    public function isSupportedReturnsTrueForFrench(): void
    {
        $this->assertTrue(FrequencyLanguageMap::isSupported('French'));
    }

    #[Test]
    public function isSupportedReturnsFalseForUnknownLanguage(): void
    {
        $this->assertFalse(FrequencyLanguageMap::isSupported('Klingon'));
    }

    #[Test]
    public function isSupportedReturnsFalseForEmptyString(): void
    {
        $this->assertFalse(FrequencyLanguageMap::isSupported(''));
    }

    #[Test]
    public function getFrequencyCodeReturnsCorrectCode(): void
    {
        $this->assertSame('es', FrequencyLanguageMap::getFrequencyCode('Spanish'));
        $this->assertSame('fr', FrequencyLanguageMap::getFrequencyCode('French'));
        $this->assertSame('de', FrequencyLanguageMap::getFrequencyCode('German'));
        $this->assertSame('ja', FrequencyLanguageMap::getFrequencyCode('Japanese'));
    }

    #[Test]
    public function getFrequencyCodeReturnsNullForUnknown(): void
    {
        $this->assertNull(FrequencyLanguageMap::getFrequencyCode('Klingon'));
    }

    #[Test]
    public function getFrequencyCodeHandlesSpecialCases(): void
    {
        $this->assertSame('ze_zh', FrequencyLanguageMap::getFrequencyCode('Chinese (Simplified)'));
        $this->assertSame('no', FrequencyLanguageMap::getFrequencyCode('Norwegian Bokmål'));
    }

    #[Test]
    public function getKaikkiLanguageNameReturnsCorrectName(): void
    {
        $this->assertSame('Spanish', FrequencyLanguageMap::getKaikkiLanguageName('Spanish'));
        $this->assertSame('Greek', FrequencyLanguageMap::getKaikkiLanguageName('Greek (Modern)'));
        $this->assertSame('Chinese', FrequencyLanguageMap::getKaikkiLanguageName('Chinese (Simplified)'));
    }

    #[Test]
    public function getKaikkiLanguageNameReturnsNullForUnknown(): void
    {
        $this->assertNull(FrequencyLanguageMap::getKaikkiLanguageName('Klingon'));
    }

    #[Test]
    public function getWiktionaryCodeReturnsCorrectCode(): void
    {
        $this->assertSame('es', FrequencyLanguageMap::getWiktionaryCode('Spanish'));
        $this->assertSame('zh', FrequencyLanguageMap::getWiktionaryCode('Chinese (Simplified)'));
        $this->assertSame('el', FrequencyLanguageMap::getWiktionaryCode('Greek (Modern)'));
    }

    #[Test]
    public function getWiktionaryCodeReturnsNullForUnknown(): void
    {
        $this->assertNull(FrequencyLanguageMap::getWiktionaryCode('Klingon'));
    }

    #[Test]
    public function getSupportedLanguagesReturnsNonEmptyArray(): void
    {
        $languages = FrequencyLanguageMap::getSupportedLanguages();
        $this->assertNotEmpty($languages);
        $this->assertContains('Spanish', $languages);
        $this->assertContains('English', $languages);
        $this->assertContains('French', $languages);
    }

    #[Test]
    public function getSupportedLanguagesReturnsOnlyStrings(): void
    {
        $languages = FrequencyLanguageMap::getSupportedLanguages();
        foreach ($languages as $lang) {
            $this->assertIsString($lang);
        }
    }

    #[Test]
    public function resetClearsCache(): void
    {
        // Load the map
        FrequencyLanguageMap::isSupported('Spanish');
        // Reset
        FrequencyLanguageMap::reset();
        // Should still work (reloads from file)
        $this->assertTrue(FrequencyLanguageMap::isSupported('Spanish'));
    }

    #[Test]
    #[DataProvider('allLanguagesProvider')]
    public function allMappedLanguagesHaveAllThreeFields(string $langName): void
    {
        $this->assertNotNull(FrequencyLanguageMap::getFrequencyCode($langName));
        $this->assertNotNull(FrequencyLanguageMap::getKaikkiLanguageName($langName));
        $this->assertNotNull(FrequencyLanguageMap::getWiktionaryCode($langName));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function allLanguagesProvider(): array
    {
        // Load the JSON directly for the data provider
        $path = __DIR__ . '/../../../../../../src/Modules/Vocabulary/Infrastructure/Data/frequency_language_map.json';
        $map = json_decode(file_get_contents($path), true);
        $cases = [];
        foreach (array_keys($map) as $name) {
            $cases[$name] = [$name];
        }
        return $cases;
    }
}
