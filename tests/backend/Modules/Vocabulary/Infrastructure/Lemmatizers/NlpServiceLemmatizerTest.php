<?php

declare(strict_types=1);

namespace Tests\Backend\Modules\Vocabulary\Infrastructure\Lemmatizers;

use PHPUnit\Framework\TestCase;
use Lwt\Modules\Language\Infrastructure\NlpServiceHandler;
use Lwt\Modules\Vocabulary\Infrastructure\Lemmatizers\NlpServiceLemmatizer;

/**
 * Unit tests for NlpServiceLemmatizer.
 *
 * Tests NLP service integration for lemmatization.
 * Note: These tests mock the NLP service handler since the actual service
 * may not be available during testing.
 */
class NlpServiceLemmatizerTest extends TestCase
{
    private NlpServiceLemmatizer $lemmatizer;
    private NlpServiceHandler $mockHandler;

    protected function setUp(): void
    {
        $this->mockHandler = $this->createMock(NlpServiceHandler::class);
        $this->lemmatizer = new NlpServiceLemmatizer($this->mockHandler);
    }

    // =========================================================================
    // lemmatize Tests
    // =========================================================================

    public function testLemmatizeReturnsNullForEmptyWord(): void
    {
        $this->mockHandler->expects($this->never())
            ->method('lemmatize');

        $result = $this->lemmatizer->lemmatize('', 'en');
        $this->assertNull($result);
    }

    public function testLemmatizeCallsHandler(): void
    {
        $this->mockHandler->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);

        $this->mockHandler->expects($this->once())
            ->method('getAvailableLemmatizers')
            ->willReturn([
                'spacy_models' => ['installed' => ['en', 'de']]
            ]);

        $this->mockHandler->expects($this->once())
            ->method('lemmatize')
            ->with('running', 'en', 'spacy')
            ->willReturn('run');

        $result = $this->lemmatizer->lemmatize('running', 'en');
        $this->assertSame('run', $result);
    }

    public function testLemmatizeReturnsNullWhenServiceUnavailable(): void
    {
        $this->mockHandler->expects($this->once())
            ->method('isAvailable')
            ->willReturn(false);

        $this->mockHandler->expects($this->never())
            ->method('lemmatize');

        $result = $this->lemmatizer->lemmatize('running', 'en');
        $this->assertNull($result);
    }

    public function testLemmatizeReturnsNullForUnsupportedLanguage(): void
    {
        $this->mockHandler->expects($this->never())
            ->method('lemmatize');

        // Language 'xyz' is not in the SPACY_MODELS constant
        $result = $this->lemmatizer->lemmatize('word', 'xyz');
        $this->assertNull($result);
    }

    // =========================================================================
    // lemmatizeBatch Tests
    // =========================================================================

    public function testLemmatizeBatchReturnsEmptyForEmptyArray(): void
    {
        $this->mockHandler->expects($this->never())
            ->method('lemmatizeBatch');

        $result = $this->lemmatizer->lemmatizeBatch([], 'en');
        $this->assertSame([], $result);
    }

    public function testLemmatizeBatchCallsHandler(): void
    {
        $this->mockHandler->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);

        $this->mockHandler->expects($this->once())
            ->method('getAvailableLemmatizers')
            ->willReturn([
                'spacy_models' => ['installed' => ['en']]
            ]);

        $this->mockHandler->expects($this->once())
            ->method('lemmatizeBatch')
            ->with(['running', 'walking'], 'en', 'spacy')
            ->willReturn(['running' => 'run', 'walking' => 'walk']);

        $result = $this->lemmatizer->lemmatizeBatch(['running', 'walking'], 'en');
        $this->assertSame(['running' => 'run', 'walking' => 'walk'], $result);
    }

    // =========================================================================
    // supportsLanguage Tests
    // =========================================================================

    public function testSupportsLanguageReturnsFalseForUnknownLanguage(): void
    {
        // Language 'xyz' is not in SPACY_MODELS
        $result = $this->lemmatizer->supportsLanguage('xyz');
        $this->assertFalse($result);
    }

    public function testSupportsLanguageReturnsTrueWhenModelInstalled(): void
    {
        $this->mockHandler->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);

        $this->mockHandler->expects($this->once())
            ->method('getAvailableLemmatizers')
            ->willReturn([
                'spacy_models' => ['installed' => ['en', 'de', 'fr']]
            ]);

        $result = $this->lemmatizer->supportsLanguage('en');
        $this->assertTrue($result);
    }

    public function testSupportsLanguageReturnsFalseWhenModelNotInstalled(): void
    {
        $this->mockHandler->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);

        $this->mockHandler->expects($this->once())
            ->method('getAvailableLemmatizers')
            ->willReturn([
                'spacy_models' => ['installed' => ['de', 'fr']]
            ]);

        // 'en' is a valid language but not installed
        $result = $this->lemmatizer->supportsLanguage('en');
        $this->assertFalse($result);
    }

    // =========================================================================
    // getSupportedLanguages Tests
    // =========================================================================

    public function testGetSupportedLanguagesReturnsInstalledOnly(): void
    {
        $this->mockHandler->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);

        $this->mockHandler->expects($this->once())
            ->method('getAvailableLemmatizers')
            ->willReturn([
                'spacy_models' => ['installed' => ['en', 'de']]
            ]);

        $result = $this->lemmatizer->getSupportedLanguages();
        $this->assertContains('en', $result);
        $this->assertContains('de', $result);
    }

    public function testGetSupportedLanguagesReturnsEmptyWhenServiceUnavailable(): void
    {
        $this->mockHandler->expects($this->once())
            ->method('isAvailable')
            ->willReturn(false);

        $result = $this->lemmatizer->getSupportedLanguages();
        $this->assertSame([], $result);
    }

    // =========================================================================
    // getAllPotentialLanguages Tests
    // =========================================================================

    public function testGetAllPotentialLanguagesReturnsAllModelLanguages(): void
    {
        $languages = $this->lemmatizer->getAllPotentialLanguages();

        $this->assertContains('en', $languages);
        $this->assertContains('de', $languages);
        $this->assertContains('fr', $languages);
        $this->assertContains('es', $languages);
        $this->assertContains('ja', $languages);
        $this->assertContains('zh', $languages);
    }

    // =========================================================================
    // Language Code Normalization Tests
    // =========================================================================

    public function testLemmatizeNormalizesLanguageCodes(): void
    {
        $this->mockHandler->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);

        $this->mockHandler->expects($this->once())
            ->method('getAvailableLemmatizers')
            ->willReturn([
                'spacy_models' => ['installed' => ['en']]
            ]);

        $this->mockHandler->expects($this->once())
            ->method('lemmatize')
            ->with('running', 'en', 'spacy')  // Should normalize 'en-US' to 'en'
            ->willReturn('run');

        // Using 'en-US' should normalize to 'en'
        $result = $this->lemmatizer->lemmatize('running', 'en-US');
        $this->assertSame('run', $result);
    }

    public function testLemmatizeNormalizesThreeLetterCodes(): void
    {
        $this->mockHandler->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);

        $this->mockHandler->expects($this->once())
            ->method('getAvailableLemmatizers')
            ->willReturn([
                'spacy_models' => ['installed' => ['en']]
            ]);

        $this->mockHandler->expects($this->once())
            ->method('lemmatize')
            ->with('running', 'en', 'spacy')  // Should normalize 'eng' to 'en'
            ->willReturn('run');

        // Using 'eng' should normalize to 'en'
        $result = $this->lemmatizer->lemmatize('running', 'eng');
        $this->assertSame('run', $result);
    }

    // =========================================================================
    // isServiceAvailable Tests
    // =========================================================================

    public function testIsServiceAvailableReturnsHandlerResult(): void
    {
        $this->mockHandler->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);

        $result = $this->lemmatizer->isServiceAvailable();
        $this->assertTrue($result);
    }
}
