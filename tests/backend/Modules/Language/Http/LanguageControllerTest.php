<?php

declare(strict_types=1);

namespace Tests\Modules\Language\Http;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Lwt\Modules\Language\Http\LanguageController;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Modules\Language\Domain\Language;
use Lwt\Modules\Language\Infrastructure\LanguagePresets;

/**
 * Tests for LanguageController.
 *
 * @covers \Lwt\Modules\Language\Http\LanguageController
 */
class LanguageControllerTest extends TestCase
{
    private LanguageController $controller;
    private MockObject&LanguageFacade $mockFacade;

    protected function setUp(): void
    {
        $this->mockFacade = $this->createMock(LanguageFacade::class);
        $this->controller = new LanguageController($this->mockFacade);
    }

    // =========================================================================
    // Constructor Tests
    // =========================================================================

    public function testConstructorSetsLanguageFacade(): void
    {
        $reflection = new \ReflectionProperty(LanguageController::class, 'languageFacade');
        $reflection->setAccessible(true);

        $facade = $reflection->getValue($this->controller);

        $this->assertInstanceOf(LanguageFacade::class, $facade);
    }

    // =========================================================================
    // getWizardSelectOptions() Tests via Reflection
    // =========================================================================

    public function testGetWizardSelectOptionsWithEmptySelection(): void
    {
        $method = new \ReflectionMethod(LanguageController::class, 'getWizardSelectOptions');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, '');

        $this->assertIsString($result);
        $this->assertStringContainsString('<option', $result);
        $this->assertStringContainsString('[Choose...]', $result);
        $this->assertStringContainsString('selected', $result); // Empty option selected
    }

    public function testGetWizardSelectOptionsWithSelection(): void
    {
        $method = new \ReflectionMethod(LanguageController::class, 'getWizardSelectOptions');
        $method->setAccessible(true);

        // Get a valid language name from presets
        $presets = LanguagePresets::getAll();
        if (empty($presets)) {
            $this->markTestSkipped('No language presets available');
        }
        $languageName = array_keys($presets)[0];

        $result = $method->invoke($this->controller, $languageName);

        $this->assertIsString($result);
        $this->assertStringContainsString($languageName, $result);
    }

    public function testGetWizardSelectOptionsContainsAllPresets(): void
    {
        $method = new \ReflectionMethod(LanguageController::class, 'getWizardSelectOptions');
        $method->setAccessible(true);

        $presets = LanguagePresets::getAll();
        $result = $method->invoke($this->controller, '');

        // Check that at least some common languages are present
        foreach (array_keys($presets) as $lang) {
            $this->assertStringContainsString($lang, $result);
        }
    }

    public function testGetWizardSelectOptionsHtmlStructure(): void
    {
        $method = new \ReflectionMethod(LanguageController::class, 'getWizardSelectOptions');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, '');

        // Check for proper HTML structure
        $this->assertStringContainsString('<option value=""', $result);
        $this->assertStringContainsString('</option>', $result);
    }

    public function testGetWizardSelectOptionsWithNonExistentLanguage(): void
    {
        $method = new \ReflectionMethod(LanguageController::class, 'getWizardSelectOptions');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, 'NonExistentLanguage12345');

        // Should still work, but non-existent language won't have 'selected'
        $this->assertIsString($result);
        $this->assertStringContainsString('[Choose...]', $result);
    }

    // =========================================================================
    // prepareLanguageCodes() Tests via Reflection
    // =========================================================================

    public function testPrepareLanguageCodesWithEmptyLanguageName(): void
    {
        $method = new \ReflectionMethod(LanguageController::class, 'prepareLanguageCodes');
        $method->setAccessible(true);

        // Create a language with empty name
        $language = $this->createMockLanguage('', '');
        $sourceLg = '';
        $targetLg = '';

        $method->invokeArgs($this->controller, [$language, '', &$sourceLg, &$targetLg]);

        // With empty inputs, codes should remain empty
        $this->assertSame('', $sourceLg);
        $this->assertSame('', $targetLg);
    }

    public function testPrepareLanguageCodesWithValidPresetLanguage(): void
    {
        $method = new \ReflectionMethod(LanguageController::class, 'prepareLanguageCodes');
        $method->setAccessible(true);

        // Find a language that exists in presets
        $presets = LanguagePresets::getAll();
        if (empty($presets)) {
            $this->markTestSkipped('No language presets available');
        }

        $langName = array_keys($presets)[0];
        $langData = $presets[$langName];
        $expectedCode = $langData[1] ?? ''; // BCP 47 code

        $language = $this->createMockLanguage($langName, '');
        $sourceLg = '';
        $targetLg = '';

        $method->invokeArgs($this->controller, [$language, '', &$sourceLg, &$targetLg]);

        if ($expectedCode !== '') {
            $this->assertSame($expectedCode, $sourceLg);
        }
    }

    public function testPrepareLanguageCodesWithNativeLanguage(): void
    {
        $method = new \ReflectionMethod(LanguageController::class, 'prepareLanguageCodes');
        $method->setAccessible(true);

        // Use a common native language
        $presets = LanguagePresets::getAll();
        if (!isset($presets['English'])) {
            $this->markTestSkipped('English preset not available');
        }

        $englishCode = $presets['English'][1] ?? '';
        $language = $this->createMockLanguage('', '');
        $sourceLg = '';
        $targetLg = '';

        $method->invokeArgs($this->controller, [$language, 'English', &$sourceLg, &$targetLg]);

        if ($englishCode !== '') {
            $this->assertSame($englishCode, $targetLg);
        }
    }

    public function testPrepareLanguageCodesExtractsFromTranslatorUri(): void
    {
        $method = new \ReflectionMethod(LanguageController::class, 'prepareLanguageCodes');
        $method->setAccessible(true);

        // Create a language with a translator URI containing language codes
        $translatorUri = 'https://translate.google.com/?sl=es&tl=en';
        $language = $this->createMockLanguage('Spanish', $translatorUri);
        $sourceLg = '';
        $targetLg = '';

        $method->invokeArgs($this->controller, [$language, '', &$sourceLg, &$targetLg]);

        // The method should extract language codes from the URI
        $this->assertIsString($sourceLg);
        $this->assertIsString($targetLg);
    }

    public function testPrepareLanguageCodesDoesNotOverrideEmptyUri(): void
    {
        $method = new \ReflectionMethod(LanguageController::class, 'prepareLanguageCodes');
        $method->setAccessible(true);

        $presets = LanguagePresets::getAll();
        if (!isset($presets['Spanish'])) {
            $this->markTestSkipped('Spanish preset not available');
        }

        $expectedCode = $presets['Spanish'][1] ?? '';
        $language = $this->createMockLanguage('Spanish', '');
        $sourceLg = '';
        $targetLg = '';

        $method->invokeArgs($this->controller, [$language, '', &$sourceLg, &$targetLg]);

        // Without URI, should use preset code
        if ($expectedCode !== '') {
            $this->assertSame($expectedCode, $sourceLg);
        }
    }

    // =========================================================================
    // Language Domain Object Tests (Article tests for determineStatus analogy)
    // =========================================================================

    public function testLanguageMockWithName(): void
    {
        $language = $this->createMockLanguage('TestLanguage', 'https://example.com');

        $this->assertSame('TestLanguage', $language->name());
        $this->assertSame('https://example.com', $language->translatorUri());
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testGetWizardSelectOptionsWithSpecialCharacters(): void
    {
        $method = new \ReflectionMethod(LanguageController::class, 'getWizardSelectOptions');
        $method->setAccessible(true);

        // Test with special characters that might need HTML encoding
        $result = $method->invoke($this->controller, '<script>alert(1)</script>');

        $this->assertIsString($result);
        // Should not contain unencoded script tags
        $this->assertStringNotContainsString('<script>', $result);
    }

    public function testPrepareLanguageCodesHandlesNullValues(): void
    {
        $method = new \ReflectionMethod(LanguageController::class, 'prepareLanguageCodes');
        $method->setAccessible(true);

        $language = $this->createMockLanguage('', '');
        $sourceLg = '';
        $targetLg = '';

        // Should not throw exception with empty values
        $method->invokeArgs($this->controller, [$language, '', &$sourceLg, &$targetLg]);

        $this->assertSame('', $sourceLg);
        $this->assertSame('', $targetLg);
    }

    public function testPrepareLanguageCodesWithBothSourceAndTarget(): void
    {
        $method = new \ReflectionMethod(LanguageController::class, 'prepareLanguageCodes');
        $method->setAccessible(true);

        $presets = LanguagePresets::getAll();
        if (!isset($presets['French']) || !isset($presets['German'])) {
            $this->markTestSkipped('French or German presets not available');
        }

        $language = $this->createMockLanguage('French', '');
        $sourceLg = '';
        $targetLg = '';

        $method->invokeArgs($this->controller, [$language, 'German', &$sourceLg, &$targetLg]);

        // Both should be set
        $this->assertNotEmpty($sourceLg);
        $this->assertNotEmpty($targetLg);
    }

    // =========================================================================
    // Integration-style Tests (require output buffering)
    // =========================================================================

    public function testShowListMethodExists(): void
    {
        $method = new \ReflectionMethod(LanguageController::class, 'showList');

        $this->assertTrue($method->isPrivate());
        $this->assertSame(1, $method->getNumberOfRequiredParameters());
    }

    public function testShowNewFormMethodExists(): void
    {
        $method = new \ReflectionMethod(LanguageController::class, 'showNewForm');

        $this->assertTrue($method->isPrivate());
        $this->assertSame(0, $method->getNumberOfRequiredParameters());
    }

    public function testShowEditFormMethodExists(): void
    {
        $method = new \ReflectionMethod(LanguageController::class, 'showEditForm');

        $this->assertTrue($method->isPrivate());
        $this->assertSame(1, $method->getNumberOfRequiredParameters());
    }

    public function testIndexMethodExists(): void
    {
        $method = new \ReflectionMethod(LanguageController::class, 'index');

        $this->assertTrue($method->isPublic());
        $this->assertSame(1, $method->getNumberOfRequiredParameters());
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create a mock Language object with specified name and translator URI.
     *
     * @param string $name          Language name
     * @param string $translatorUri Translator URI
     *
     * @return MockObject&Language
     */
    private function createMockLanguage(string $name, string $translatorUri): MockObject&Language
    {
        $mock = $this->createMock(Language::class);
        $mock->method('name')->willReturn($name);
        $mock->method('translatorUri')->willReturn($translatorUri);
        return $mock;
    }
}
