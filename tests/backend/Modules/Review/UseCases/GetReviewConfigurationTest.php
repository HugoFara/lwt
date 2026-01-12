<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Review\UseCases;

use Lwt\Modules\Review\Application\UseCases\GetReviewConfiguration;
use Lwt\Modules\Review\Domain\ReviewConfiguration;
use Lwt\Modules\Review\Domain\ReviewRepositoryInterface;
use Lwt\Modules\Review\Infrastructure\SessionStateManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the GetReviewConfiguration use case.
 */
class GetReviewConfigurationTest extends TestCase
{
    /** @var ReviewRepositoryInterface&MockObject */
    private ReviewRepositoryInterface $repository;

    /** @var SessionStateManager&MockObject */
    private SessionStateManager $sessionManager;

    private GetReviewConfiguration $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(ReviewRepositoryInterface::class);
        $this->sessionManager = $this->createMock(SessionStateManager::class);
        $this->useCase = new GetReviewConfiguration($this->repository, $this->sessionManager);
    }

    // =========================================================================
    // parseFromParams() Tests
    // =========================================================================

    public function testParseFromParamsWithLanguageId(): void
    {
        $config = $this->useCase->parseFromParams(
            selection: null,
            sessTestsql: null,
            langId: 1,
            textId: null,
            testType: 1
        );

        $this->assertInstanceOf(ReviewConfiguration::class, $config);
        $this->assertTrue($config->isValid());
        $this->assertEquals(ReviewConfiguration::KEY_LANG, $config->reviewKey);
        $this->assertEquals(1, $config->selection);
    }

    public function testParseFromParamsWithTextId(): void
    {
        $config = $this->useCase->parseFromParams(
            selection: null,
            sessTestsql: null,
            langId: null,
            textId: 42,
            testType: 2
        );

        $this->assertInstanceOf(ReviewConfiguration::class, $config);
        $this->assertTrue($config->isValid());
        $this->assertEquals(ReviewConfiguration::KEY_TEXT, $config->reviewKey);
        $this->assertEquals(42, $config->selection);
    }

    public function testParseFromParamsWithWordsSelection(): void
    {
        $config = $this->useCase->parseFromParams(
            selection: 2,
            sessTestsql: '(1,2,3,4,5)',
            langId: null,
            textId: null,
            testType: 1
        );

        $this->assertTrue($config->isValid());
        $this->assertEquals(ReviewConfiguration::KEY_WORDS, $config->reviewKey);
        $this->assertEquals([1, 2, 3, 4, 5], $config->selection);
    }

    public function testParseFromParamsWithTextsSelection(): void
    {
        $config = $this->useCase->parseFromParams(
            selection: 3,
            sessTestsql: '(10,20,30)',
            langId: null,
            textId: null,
            testType: 1
        );

        $this->assertTrue($config->isValid());
        $this->assertEquals(ReviewConfiguration::KEY_TEXTS, $config->reviewKey);
        $this->assertEquals([10, 20, 30], $config->selection);
    }

    public function testParseFromParamsWithTableMode(): void
    {
        $config = $this->useCase->parseFromParams(
            selection: null,
            sessTestsql: null,
            langId: 1,
            textId: null,
            testType: 1,
            isTableMode: true
        );

        $this->assertTrue($config->isValid());
        $this->assertTrue($config->isTableMode);
    }

    public function testParseFromParamsReturnsInvalidConfigWhenNoSelection(): void
    {
        $config = $this->useCase->parseFromParams(
            selection: null,
            sessTestsql: null,
            langId: null,
            textId: null
        );

        $this->assertFalse($config->isValid());
    }

    public function testParseFromParamsClampsTestType(): void
    {
        // Test type should be clamped to 1-5
        $config = $this->useCase->parseFromParams(
            selection: 2,
            sessTestsql: '(1)',
            langId: null,
            textId: null,
            testType: 10  // Out of range
        );

        $this->assertTrue($config->isValid());
        // ReviewType should be max 5
        $this->assertLessThanOrEqual(5, $config->reviewType);
    }

    public function testParseFromParamsClampsTestTypeMin(): void
    {
        $config = $this->useCase->parseFromParams(
            selection: 2,
            sessTestsql: '(1)',
            langId: null,
            textId: null,
            testType: 0  // Below minimum
        );

        $this->assertTrue($config->isValid());
        $this->assertGreaterThanOrEqual(1, $config->reviewType);
    }

    public function testParseFromParamsSetsWordModeForHighTestTypes(): void
    {
        $config = $this->useCase->parseFromParams(
            selection: 2,
            sessTestsql: '(1)',
            langId: null,
            textId: null,
            testType: 4  // > 3 means word mode
        );

        $this->assertTrue($config->wordMode);
    }

    // =========================================================================
    // execute() Tests
    // =========================================================================

    public function testExecuteReturnsErrorForInvalidConfig(): void
    {
        $invalidConfig = new ReviewConfiguration('', '', 1, false, false);

        $result = $this->useCase->execute($invalidConfig);

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Invalid review configuration', $result['error']);
    }

    public function testExecuteReturnsErrorWhenValidationFails(): void
    {
        $config = ReviewConfiguration::fromLanguage(1, 1);

        $this->repository->expects($this->once())
            ->method('validateSingleLanguage')
            ->with($config)
            ->willReturn([
                'valid' => false,
                'error' => 'Terms from multiple languages are selected'
            ]);

        $result = $this->useCase->execute($config);

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Terms from multiple languages are selected', $result['error']);
    }

    public function testExecuteReturnsErrorWhenNoWordsAvailable(): void
    {
        $config = ReviewConfiguration::fromLanguage(1, 1);

        $this->repository->method('validateSingleLanguage')
            ->willReturn(['valid' => true, 'error' => null]);

        $this->repository->expects($this->once())
            ->method('getLanguageIdFromConfig')
            ->with($config)
            ->willReturn(null);

        $result = $this->useCase->execute($config);

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('No words available for testing', $result['error']);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testParseFromParamsHandlesTrailingParens(): void
    {
        // The method trims parens, so '(1,2,3)' becomes '1,2,3' then split
        $config = $this->useCase->parseFromParams(
            selection: 2,
            sessTestsql: '(1,2,3)',
            langId: null,
            textId: null,
            testType: 1
        );

        $this->assertTrue($config->isValid());
        $this->assertEquals([1, 2, 3], $config->selection);
    }

    public function testParseFromParamsHandlesSingleWordId(): void
    {
        $config = $this->useCase->parseFromParams(
            selection: 2,
            sessTestsql: '(999)',
            langId: null,
            textId: null,
            testType: 1
        );

        $this->assertTrue($config->isValid());
        $this->assertEquals([999], $config->selection);
    }

    public function testParseFromParamsLanguageTakesPrecedenceOverText(): void
    {
        // When both langId and textId are provided, langId should take precedence
        $config = $this->useCase->parseFromParams(
            selection: null,
            sessTestsql: null,
            langId: 1,
            textId: 42,
            testType: 1
        );

        // Should use language
        $this->assertEquals(ReviewConfiguration::KEY_LANG, $config->reviewKey);
    }

    public function testParseFromParamsSelectionTakesPrecedenceOverLanguage(): void
    {
        // When selection and langId are both provided, selection should take precedence
        $config = $this->useCase->parseFromParams(
            selection: 2,
            sessTestsql: '(1,2,3)',
            langId: 1,
            textId: null,
            testType: 1
        );

        // Should use selection
        $this->assertEquals(ReviewConfiguration::KEY_WORDS, $config->reviewKey);
    }
}
