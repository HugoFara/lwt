<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Review\UseCases;

use Lwt\Modules\Review\Application\UseCases\GetTomorrowCount;
use Lwt\Modules\Review\Domain\ReviewConfiguration;
use Lwt\Modules\Review\Domain\ReviewRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the GetTomorrowCount use case.
 */
class GetTomorrowCountTest extends TestCase
{
    /** @var ReviewRepositoryInterface&MockObject */
    private ReviewRepositoryInterface $repository;
    private GetTomorrowCount $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(ReviewRepositoryInterface::class);
        $this->useCase = new GetTomorrowCount($this->repository);
    }

    // =========================================================================
    // execute() Tests
    // =========================================================================

    public function testExecuteReturnsCountForValidLanguageConfig(): void
    {
        $config = ReviewConfiguration::fromLanguage(1, 1);

        $this->repository->expects($this->once())
            ->method('getTomorrowCount')
            ->with($config)
            ->willReturn(42);

        $result = $this->useCase->execute($config);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('count', $result);
        $this->assertEquals(42, $result['count']);
    }

    public function testExecuteReturnsCountForValidTextConfig(): void
    {
        $config = ReviewConfiguration::fromText(5, 2);

        $this->repository->expects($this->once())
            ->method('getTomorrowCount')
            ->with($config)
            ->willReturn(15);

        $result = $this->useCase->execute($config);

        $this->assertEquals(15, $result['count']);
    }

    public function testExecuteReturnsZeroForInvalidConfig(): void
    {
        // Create an invalid configuration
        $config = new ReviewConfiguration('', '', 1, false, false);

        $this->repository->expects($this->never())
            ->method('getTomorrowCount');

        $result = $this->useCase->execute($config);

        $this->assertEquals(0, $result['count']);
    }

    public function testExecuteReturnsZeroCountFromRepository(): void
    {
        $config = ReviewConfiguration::fromLanguage(1, 1);

        $this->repository->method('getTomorrowCount')
            ->willReturn(0);

        $result = $this->useCase->execute($config);

        $this->assertEquals(0, $result['count']);
    }

    public function testExecuteReturnsLargeCount(): void
    {
        $config = ReviewConfiguration::fromLanguage(1, 1);

        $this->repository->method('getTomorrowCount')
            ->willReturn(9999);

        $result = $this->useCase->execute($config);

        $this->assertEquals(9999, $result['count']);
    }

    // =========================================================================
    // Different Configuration Types
    // =========================================================================

    public function testExecuteWithWordsSelection(): void
    {
        $config = new ReviewConfiguration(
            ReviewConfiguration::KEY_WORDS,
            [1, 2, 3, 4, 5],
            1,
            false,
            false
        );

        $this->repository->expects($this->once())
            ->method('getTomorrowCount')
            ->willReturn(3);

        $result = $this->useCase->execute($config);

        $this->assertEquals(3, $result['count']);
    }

    public function testExecuteWithTextsSelection(): void
    {
        $config = new ReviewConfiguration(
            ReviewConfiguration::KEY_TEXTS,
            [10, 20],
            2,
            false,
            false
        );

        $this->repository->expects($this->once())
            ->method('getTomorrowCount')
            ->willReturn(25);

        $result = $this->useCase->execute($config);

        $this->assertEquals(25, $result['count']);
    }

    public function testExecuteWithTableModeConfig(): void
    {
        $config = ReviewConfiguration::forTableMode(ReviewConfiguration::KEY_LANG, 1);

        $this->repository->expects($this->once())
            ->method('getTomorrowCount')
            ->willReturn(100);

        $result = $this->useCase->execute($config);

        $this->assertEquals(100, $result['count']);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testExecuteResultContainsOnlyCountKey(): void
    {
        $config = ReviewConfiguration::fromLanguage(1, 1);

        $this->repository->method('getTomorrowCount')
            ->willReturn(10);

        $result = $this->useCase->execute($config);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('count', $result);
    }

    public function testExecuteCallsRepositoryOnce(): void
    {
        $config = ReviewConfiguration::fromLanguage(1, 1);

        $this->repository->expects($this->once())
            ->method('getTomorrowCount')
            ->willReturn(5);

        $this->useCase->execute($config);
    }

    public function testExecuteWithDifferentTestTypes(): void
    {
        for ($testType = 1; $testType <= 5; $testType++) {
            $config = ReviewConfiguration::fromLanguage(1, $testType);

            $this->repository->method('getTomorrowCount')
                ->willReturn($testType * 10);

            $result = $this->useCase->execute($config);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('count', $result);
        }
    }
}
