<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Text\Http;

use Lwt\Modules\Text\Http\TextTermApiHandler;
use Lwt\Modules\Text\Application\TextFacade;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for TextTermApiHandler.
 *
 * Tests term translations, word retrieval, text scoring,
 * text listing by language, and response formatting.
 */
class TextTermApiHandlerTest extends TestCase
{
    /** @var TextFacade&MockObject */
    private TextFacade $textService;

    private TextTermApiHandler $handler;

    protected function setUp(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required');
        }
        $this->textService = $this->createMock(TextFacade::class);
        $this->handler = new TextTermApiHandler($this->textService);
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    #[Test]
    public function constructorCreatesValidHandler(): void
    {
        $this->assertInstanceOf(TextTermApiHandler::class, $this->handler);
    }

    #[Test]
    public function constructorAcceptsNullParameter(): void
    {
        $handler = new TextTermApiHandler(null);
        $this->assertInstanceOf(TextTermApiHandler::class, $handler);
    }

    #[Test]
    public function constructorSetsTextServiceProperty(): void
    {
        $reflection = new \ReflectionProperty(TextTermApiHandler::class, 'textService');
        $reflection->setAccessible(true);

        $this->assertSame($this->textService, $reflection->getValue($this->handler));
    }

    #[Test]
    public function constructorWithNullCreatesDefaultService(): void
    {
        $handler = new TextTermApiHandler(null);
        $reflection = new \ReflectionProperty(TextTermApiHandler::class, 'textService');
        $reflection->setAccessible(true);

        $this->assertInstanceOf(TextFacade::class, $reflection->getValue($handler));
    }

    // =========================================================================
    // Class structure tests
    // =========================================================================

    #[Test]
    public function classHasRequiredPublicMethods(): void
    {
        $reflection = new \ReflectionClass(TextTermApiHandler::class);

        $expectedMethods = [
            'getWords', 'formatGetWords',
            'formatTextsByLanguage', 'formatArchivedTextsByLanguage',
            'getTranslations', 'getTermTranslations', 'formatTermTranslations',
            'formatGetTextScore', 'formatGetTextScores',
            'formatGetRecommendedTexts',
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "TextTermApiHandler should have method: $methodName"
            );
            $method = $reflection->getMethod($methodName);
            $this->assertTrue(
                $method->isPublic(),
                "Method $methodName should be public"
            );
        }
    }

    // =========================================================================
    // getWords tests
    // =========================================================================

    #[Test]
    public function getWordsReturnsErrorForNonExistentText(): void
    {
        $result = $this->handler->getWords(999999);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Text not found', $result['error']);
    }

    #[Test]
    public function formatGetWordsDelegatesToGetWords(): void
    {
        $result = $this->handler->formatGetWords(999999);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Text not found', $result['error']);
    }

    // =========================================================================
    // formatTextsByLanguage tests
    // =========================================================================

    #[Test]
    public function formatTextsByLanguageDefaultsPagination(): void
    {
        $this->textService->expects($this->once())
            ->method('getTextsForLanguage')
            ->with(1, 1, 10, 1)
            ->willReturn(['texts' => [], 'pagination' => ['page' => 1]]);

        $result = $this->handler->formatTextsByLanguage(1, []);

        $this->assertIsArray($result);
    }

    #[Test]
    public function formatTextsByLanguageRespectsPageParam(): void
    {
        $this->textService->expects($this->once())
            ->method('getTextsForLanguage')
            ->with(1, 3, 10, 1)
            ->willReturn(['texts' => [], 'pagination' => ['page' => 3]]);

        $result = $this->handler->formatTextsByLanguage(1, ['page' => '3']);

        $this->assertIsArray($result);
    }

    #[Test]
    public function formatTextsByLanguageRespectsPerPageParam(): void
    {
        $this->textService->expects($this->once())
            ->method('getTextsForLanguage')
            ->with(1, 1, 25, 1)
            ->willReturn(['texts' => [], 'pagination' => []]);

        $result = $this->handler->formatTextsByLanguage(1, ['per_page' => '25']);

        $this->assertIsArray($result);
    }

    #[Test]
    public function formatTextsByLanguageClampsPerPageMin(): void
    {
        $this->textService->expects($this->once())
            ->method('getTextsForLanguage')
            ->with(1, 1, 1, 1)
            ->willReturn(['texts' => [], 'pagination' => []]);

        $result = $this->handler->formatTextsByLanguage(1, ['per_page' => '-5']);

        $this->assertIsArray($result);
    }

    #[Test]
    public function formatTextsByLanguageClampsPerPageMax(): void
    {
        $this->textService->expects($this->once())
            ->method('getTextsForLanguage')
            ->with(1, 1, 100, 1)
            ->willReturn(['texts' => [], 'pagination' => []]);

        $result = $this->handler->formatTextsByLanguage(1, ['per_page' => '999']);

        $this->assertIsArray($result);
    }

    #[Test]
    public function formatTextsByLanguageClampsPageMin(): void
    {
        $this->textService->expects($this->once())
            ->method('getTextsForLanguage')
            ->with(1, 1, 10, 1)
            ->willReturn(['texts' => [], 'pagination' => []]);

        $result = $this->handler->formatTextsByLanguage(1, ['page' => '-1']);

        $this->assertIsArray($result);
    }

    #[Test]
    public function formatTextsByLanguageRespectsSortParam(): void
    {
        $this->textService->expects($this->once())
            ->method('getTextsForLanguage')
            ->with(1, 1, 10, 3)
            ->willReturn(['texts' => [], 'pagination' => []]);

        $result = $this->handler->formatTextsByLanguage(1, ['sort' => '3']);

        $this->assertIsArray($result);
    }

    // =========================================================================
    // formatArchivedTextsByLanguage tests
    // =========================================================================

    #[Test]
    public function formatArchivedTextsByLanguageDefaultsPagination(): void
    {
        $this->textService->expects($this->once())
            ->method('getArchivedTextsForLanguage')
            ->with(1, 1, 10, 1)
            ->willReturn(['texts' => [], 'pagination' => ['page' => 1]]);

        $result = $this->handler->formatArchivedTextsByLanguage(1, []);

        $this->assertIsArray($result);
    }

    #[Test]
    public function formatArchivedTextsByLanguageRespectsPageParam(): void
    {
        $this->textService->expects($this->once())
            ->method('getArchivedTextsForLanguage')
            ->with(1, 2, 10, 1)
            ->willReturn(['texts' => [], 'pagination' => []]);

        $result = $this->handler->formatArchivedTextsByLanguage(1, ['page' => '2']);

        $this->assertIsArray($result);
    }

    #[Test]
    public function formatArchivedTextsByLanguageClampsPerPage(): void
    {
        $this->textService->expects($this->once())
            ->method('getArchivedTextsForLanguage')
            ->with(1, 1, 100, 1)
            ->willReturn(['texts' => [], 'pagination' => []]);

        $result = $this->handler->formatArchivedTextsByLanguage(1, ['per_page' => '500']);

        $this->assertIsArray($result);
    }

    #[Test]
    public function formatArchivedTextsByLanguageRespectsSortParam(): void
    {
        $this->textService->expects($this->once())
            ->method('getArchivedTextsForLanguage')
            ->with(1, 1, 10, 2)
            ->willReturn(['texts' => [], 'pagination' => []]);

        $result = $this->handler->formatArchivedTextsByLanguage(1, ['sort' => '2']);

        $this->assertIsArray($result);
    }

    // =========================================================================
    // getTranslations tests
    // =========================================================================

    #[Test]
    public function getTranslationsReturnsArrayForNonExistentWord(): void
    {
        $result = $this->handler->getTranslations(999999);

        $this->assertIsArray($result);
    }

    #[Test]
    public function getTranslationsReturnsEmptyForNonExistentWord(): void
    {
        $result = $this->handler->getTranslations(999999);

        $this->assertEmpty($result);
    }

    // =========================================================================
    // getTermTranslations tests
    // =========================================================================

    #[Test]
    public function getTermTranslationsReturnsErrorForNonExistentText(): void
    {
        $result = $this->handler->getTermTranslations('hello', 999999);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Text not found', $result['error']);
    }

    #[Test]
    public function formatTermTranslationsDelegatesToGetTermTranslations(): void
    {
        $result = $this->handler->formatTermTranslations('hello', 999999);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Text not found', $result['error']);
    }

    // =========================================================================
    // formatGetTextScore tests
    // =========================================================================

    #[Test]
    public function formatGetTextScoreReturnsArray(): void
    {
        $result = $this->handler->formatGetTextScore(999999);

        $this->assertIsArray($result);
    }

    #[Test]
    public function formatGetTextScoreMethodSignature(): void
    {
        $method = new \ReflectionMethod(TextTermApiHandler::class, 'formatGetTextScore');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('textId', $params[0]->getName());
    }

    // =========================================================================
    // formatGetTextScores tests
    // =========================================================================

    #[Test]
    public function formatGetTextScoresReturnsScoresKey(): void
    {
        $result = $this->handler->formatGetTextScores([]);

        $this->assertArrayHasKey('scores', $result);
    }

    #[Test]
    public function formatGetTextScoresReturnsEmptyForEmptyInput(): void
    {
        $result = $this->handler->formatGetTextScores([]);

        $this->assertEmpty($result['scores']);
    }

    #[Test]
    public function formatGetTextScoresAcceptsArrayOfIds(): void
    {
        $method = new \ReflectionMethod(TextTermApiHandler::class, 'formatGetTextScores');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('textIds', $params[0]->getName());
    }

    // =========================================================================
    // formatGetRecommendedTexts tests
    // =========================================================================

    #[Test]
    public function formatGetRecommendedTextsReturnsRecommendationsKey(): void
    {
        $result = $this->handler->formatGetRecommendedTexts(1, []);

        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('target_comprehensibility', $result);
    }

    #[Test]
    public function formatGetRecommendedTextsDefaultsTarget095(): void
    {
        $result = $this->handler->formatGetRecommendedTexts(1, []);

        $this->assertSame(0.95, $result['target_comprehensibility']);
    }

    #[Test]
    public function formatGetRecommendedTextsRespectsTargetParam(): void
    {
        $result = $this->handler->formatGetRecommendedTexts(1, ['target' => '0.85']);

        $this->assertSame(0.85, $result['target_comprehensibility']);
    }

    #[Test]
    public function formatGetRecommendedTextsClampsTargetMin(): void
    {
        $result = $this->handler->formatGetRecommendedTexts(1, ['target' => '0.1']);

        $this->assertSame(0.5, $result['target_comprehensibility']);
    }

    #[Test]
    public function formatGetRecommendedTextsClampsTargetMax(): void
    {
        $result = $this->handler->formatGetRecommendedTexts(1, ['target' => '2.0']);

        $this->assertSame(1.0, $result['target_comprehensibility']);
    }

    #[Test]
    public function formatGetRecommendedTextsReturnsEmptyForNoTexts(): void
    {
        $result = $this->handler->formatGetRecommendedTexts(999999, []);

        $this->assertIsArray($result['recommendations']);
    }

    #[Test]
    public function formatGetRecommendedTextsRespectsLimitParam(): void
    {
        // Just verify the method does not crash with a limit param
        $result = $this->handler->formatGetRecommendedTexts(1, ['limit' => '5']);

        $this->assertArrayHasKey('recommendations', $result);
    }

    #[Test]
    public function formatGetRecommendedTextsClampsLimitMin(): void
    {
        $result = $this->handler->formatGetRecommendedTexts(1, ['limit' => '0']);

        $this->assertArrayHasKey('recommendations', $result);
    }

    #[Test]
    public function formatGetRecommendedTextsClampsLimitMax(): void
    {
        $result = $this->handler->formatGetRecommendedTexts(1, ['limit' => '100']);

        $this->assertArrayHasKey('recommendations', $result);
    }

    // =========================================================================
    // Method signature tests
    // =========================================================================

    #[Test]
    public function getWordsAcceptsIntParam(): void
    {
        $method = new \ReflectionMethod(TextTermApiHandler::class, 'getWords');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('textId', $params[0]->getName());
    }

    #[Test]
    public function getTranslationsAcceptsIntParam(): void
    {
        $method = new \ReflectionMethod(TextTermApiHandler::class, 'getTranslations');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('wordId', $params[0]->getName());
    }

    #[Test]
    public function getTermTranslationsAcceptsStringAndInt(): void
    {
        $method = new \ReflectionMethod(TextTermApiHandler::class, 'getTermTranslations');
        $params = $method->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('wordlc', $params[0]->getName());
        $this->assertSame('textid', $params[1]->getName());
    }

    #[Test]
    public function formatTextsByLanguageAcceptsIntAndArray(): void
    {
        $method = new \ReflectionMethod(TextTermApiHandler::class, 'formatTextsByLanguage');
        $params = $method->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('langId', $params[0]->getName());
        $this->assertSame('params', $params[1]->getName());
    }

    #[Test]
    public function formatGetRecommendedTextsAcceptsIntAndArray(): void
    {
        $method = new \ReflectionMethod(TextTermApiHandler::class, 'formatGetRecommendedTexts');
        $params = $method->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('languageId', $params[0]->getName());
        $this->assertSame('params', $params[1]->getName());
    }
}
