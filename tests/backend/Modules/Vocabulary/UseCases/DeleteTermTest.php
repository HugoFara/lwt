<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\UseCases;

use Lwt\Shared\Infrastructure\Globals;
use Lwt\Modules\Vocabulary\Application\UseCases\DeleteTerm;
use Lwt\Modules\Vocabulary\Domain\TermRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the DeleteTerm use case.
 */
class DeleteTermTest extends TestCase
{
    /** @var TermRepositoryInterface&MockObject */
    private TermRepositoryInterface $repository;
    private DeleteTerm $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        Globals::reset();
        $this->repository = $this->createMock(TermRepositoryInterface::class);
        $this->useCase = new DeleteTerm($this->repository);
    }

    protected function tearDown(): void
    {
        Globals::reset();
        parent::tearDown();
    }

    // =========================================================================
    // execute() Tests
    // =========================================================================

    public function testExecuteDeletesTermSuccessfully(): void
    {
        $this->repository->expects($this->once())
            ->method('delete')
            ->with(42)
            ->willReturn(true);

        $result = $this->useCase->execute(42);

        $this->assertTrue($result);
    }

    public function testExecuteReturnsFalseWhenTermNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('delete')
            ->with(999)
            ->willReturn(false);

        $result = $this->useCase->execute(999);

        $this->assertFalse($result);
    }

    public function testExecuteReturnsFalseForZeroId(): void
    {
        $this->repository->expects($this->never())->method('delete');

        $result = $this->useCase->execute(0);

        $this->assertFalse($result);
    }

    public function testExecuteReturnsFalseForNegativeId(): void
    {
        $this->repository->expects($this->never())->method('delete');

        $result = $this->useCase->execute(-1);

        $this->assertFalse($result);
    }

    // =========================================================================
    // executeMultiple() Tests
    // =========================================================================

    public function testExecuteMultipleDeletesMultipleTerms(): void
    {
        $this->repository->expects($this->once())
            ->method('deleteMultiple')
            ->with([1, 2, 3])
            ->willReturn(3);

        $result = $this->useCase->executeMultiple([1, 2, 3]);

        $this->assertEquals(3, $result);
    }

    public function testExecuteMultipleReturnsZeroForEmptyArray(): void
    {
        $this->repository->expects($this->never())->method('deleteMultiple');

        $result = $this->useCase->executeMultiple([]);

        $this->assertEquals(0, $result);
    }

    public function testExecuteMultipleReturnsPartialCount(): void
    {
        $this->repository->expects($this->once())
            ->method('deleteMultiple')
            ->with([1, 2, 3])
            ->willReturn(2); // Only 2 of 3 were deleted

        $result = $this->useCase->executeMultiple([1, 2, 3]);

        $this->assertEquals(2, $result);
    }

    // =========================================================================
    // executeWithResult() Tests
    // =========================================================================

    public function testExecuteWithResultReturnsSuccessOnDelete(): void
    {
        $this->repository->method('delete')->willReturn(true);

        $result = $this->useCase->executeWithResult(42);

        $this->assertTrue($result['success']);
        $this->assertNull($result['error']);
    }

    public function testExecuteWithResultReturnsErrorOnNotFound(): void
    {
        $this->repository->method('delete')->willReturn(false);

        $result = $this->useCase->executeWithResult(999);

        $this->assertFalse($result['success']);
        $this->assertEquals('Term not found', $result['error']);
    }

    public function testExecuteWithResultHandlesZeroId(): void
    {
        $result = $this->useCase->executeWithResult(0);

        $this->assertFalse($result['success']);
        $this->assertEquals('Term not found', $result['error']);
    }
}
