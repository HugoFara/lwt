<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\UseCases;

use DateTimeImmutable;
use Lwt\Core\Globals;
use Lwt\Modules\Vocabulary\Application\UseCases\UpdateTermStatus;
use Lwt\Modules\Vocabulary\Domain\Term;
use Lwt\Modules\Vocabulary\Domain\TermRepositoryInterface;
use Lwt\Modules\Vocabulary\Domain\ValueObject\TermStatus;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the UpdateTermStatus use case.
 */
class UpdateTermStatusTest extends TestCase
{
    /** @var TermRepositoryInterface&MockObject */
    private TermRepositoryInterface $repository;
    private UpdateTermStatus $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        Globals::reset();
        $this->repository = $this->createMock(TermRepositoryInterface::class);
        $this->useCase = new UpdateTermStatus($this->repository);
    }

    protected function tearDown(): void
    {
        Globals::reset();
        parent::tearDown();
    }

    private function createTestTerm(int $id = 42, int $status = 1): Term
    {
        return Term::reconstitute(
            $id,
            1,
            'test',
            'test',
            null,
            null,
            $status,
            '*',
            '',
            '',
            '',
            1,
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            0.0,
            0.0,
            0.5
        );
    }

    // =========================================================================
    // execute() Tests
    // =========================================================================

    public function testExecuteUpdatesStatusSuccessfully(): void
    {
        $this->repository->expects($this->once())
            ->method('updateStatus')
            ->with(42, 3)
            ->willReturn(true);

        $result = $this->useCase->execute(42, 3);

        $this->assertTrue($result);
    }

    public function testExecuteReturnsFalseWhenUpdateFails(): void
    {
        $this->repository->expects($this->once())
            ->method('updateStatus')
            ->with(42, 3)
            ->willReturn(false);

        $result = $this->useCase->execute(42, 3);

        $this->assertFalse($result);
    }

    public function testExecuteReturnsFalseForZeroId(): void
    {
        $this->repository->expects($this->never())->method('updateStatus');

        $result = $this->useCase->execute(0, 3);

        $this->assertFalse($result);
    }

    public function testExecuteReturnsFalseForNegativeId(): void
    {
        $this->repository->expects($this->never())->method('updateStatus');

        $result = $this->useCase->execute(-1, 3);

        $this->assertFalse($result);
    }

    public function testExecuteThrowsExceptionForInvalidStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->useCase->execute(42, 10); // Invalid status
    }

    /**
     * @dataProvider validStatusProvider
     */
    public function testExecuteAcceptsValidStatuses(int $status): void
    {
        $this->repository->method('updateStatus')->willReturn(true);

        $result = $this->useCase->execute(42, $status);

        $this->assertTrue($result);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function validStatusProvider(): array
    {
        return [
            'status 1' => [1],
            'status 2' => [2],
            'status 3' => [3],
            'status 4' => [4],
            'status 5' => [5],
            'status 98 (ignored)' => [98],
            'status 99 (well-known)' => [99],
        ];
    }

    // =========================================================================
    // advance() Tests
    // =========================================================================

    public function testAdvanceMovesToNextStatus(): void
    {
        $term = $this->createTestTerm(42, 2);

        $this->repository->method('find')->willReturn($term);
        $this->repository->expects($this->once())
            ->method('updateStatus')
            ->with(42, 3)
            ->willReturn(true);

        $result = $this->useCase->advance(42);

        $this->assertTrue($result);
    }

    public function testAdvanceReturnsFalseWhenTermNotFound(): void
    {
        $this->repository->method('find')->willReturn(null);
        $this->repository->expects($this->never())->method('updateStatus');

        $result = $this->useCase->advance(999);

        $this->assertFalse($result);
    }

    public function testAdvanceReturnsFalseWhenAlreadyAtMaxStatus(): void
    {
        $term = $this->createTestTerm(42, 5); // Already at max learning status

        $this->repository->method('find')->willReturn($term);
        $this->repository->expects($this->never())->method('updateStatus');

        $result = $this->useCase->advance(42);

        $this->assertFalse($result);
    }

    // =========================================================================
    // decrease() Tests
    // =========================================================================

    public function testDecreaseMovesToPreviousStatus(): void
    {
        $term = $this->createTestTerm(42, 3);

        $this->repository->method('find')->willReturn($term);
        $this->repository->expects($this->once())
            ->method('updateStatus')
            ->with(42, 2)
            ->willReturn(true);

        $result = $this->useCase->decrease(42);

        $this->assertTrue($result);
    }

    public function testDecreaseReturnsFalseWhenTermNotFound(): void
    {
        $this->repository->method('find')->willReturn(null);
        $this->repository->expects($this->never())->method('updateStatus');

        $result = $this->useCase->decrease(999);

        $this->assertFalse($result);
    }

    public function testDecreaseReturnsFalseWhenAlreadyAtMinStatus(): void
    {
        $term = $this->createTestTerm(42, 1); // Already at min status

        $this->repository->method('find')->willReturn($term);
        $this->repository->expects($this->never())->method('updateStatus');

        $result = $this->useCase->decrease(42);

        $this->assertFalse($result);
    }

    // =========================================================================
    // executeMultiple() Tests
    // =========================================================================

    public function testExecuteMultipleUpdatesMultipleTerms(): void
    {
        $this->repository->expects($this->once())
            ->method('updateStatusMultiple')
            ->with([1, 2, 3], 5)
            ->willReturn(3);

        $result = $this->useCase->executeMultiple([1, 2, 3], 5);

        $this->assertEquals(3, $result);
    }

    public function testExecuteMultipleReturnsZeroForEmptyArray(): void
    {
        $this->repository->expects($this->never())->method('updateStatusMultiple');

        $result = $this->useCase->executeMultiple([], 5);

        $this->assertEquals(0, $result);
    }

    public function testExecuteMultipleThrowsExceptionForInvalidStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->useCase->executeMultiple([1, 2, 3], 10);
    }

    // =========================================================================
    // Convenience Methods Tests
    // =========================================================================

    public function testMarkAsIgnored(): void
    {
        $this->repository->expects($this->once())
            ->method('updateStatus')
            ->with(42, TermStatus::IGNORED)
            ->willReturn(true);

        $result = $this->useCase->markAsIgnored(42);

        $this->assertTrue($result);
    }

    public function testMarkAsWellKnown(): void
    {
        $this->repository->expects($this->once())
            ->method('updateStatus')
            ->with(42, TermStatus::WELL_KNOWN)
            ->willReturn(true);

        $result = $this->useCase->markAsWellKnown(42);

        $this->assertTrue($result);
    }

    public function testMarkAsLearned(): void
    {
        $this->repository->expects($this->once())
            ->method('updateStatus')
            ->with(42, TermStatus::LEARNED)
            ->willReturn(true);

        $result = $this->useCase->markAsLearned(42);

        $this->assertTrue($result);
    }
}
