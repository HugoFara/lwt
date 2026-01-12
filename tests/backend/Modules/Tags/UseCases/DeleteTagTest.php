<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Tags\UseCases;

use Lwt\Modules\Tags\Application\UseCases\DeleteTag;
use Lwt\Modules\Tags\Domain\TagAssociationInterface;
use Lwt\Modules\Tags\Domain\TagRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the DeleteTag use case.
 */
class DeleteTagTest extends TestCase
{
    /** @var TagRepositoryInterface&MockObject */
    private TagRepositoryInterface $repository;

    /** @var TagAssociationInterface&MockObject */
    private TagAssociationInterface $association;

    private DeleteTag $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(TagRepositoryInterface::class);
        $this->association = $this->createMock(TagAssociationInterface::class);
        $this->useCase = new DeleteTag($this->repository, $this->association);
    }

    // =========================================================================
    // execute() Tests - Single Deletion
    // =========================================================================

    public function testExecuteDeletesTagSuccessfully(): void
    {
        $this->repository->expects($this->once())
            ->method('delete')
            ->with(1)
            ->willReturn(true);

        $this->association->expects($this->once())
            ->method('cleanupOrphanedLinks');

        $result = $this->useCase->execute(1);

        $this->assertTrue($result);
    }

    public function testExecuteReturnsFalseWhenTagNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('delete')
            ->with(999)
            ->willReturn(false);

        $this->association->expects($this->never())
            ->method('cleanupOrphanedLinks');

        $result = $this->useCase->execute(999);

        $this->assertFalse($result);
    }

    public function testExecuteDoesNotCleanupOnFailedDeletion(): void
    {
        $this->repository->method('delete')
            ->willReturn(false);

        $this->association->expects($this->never())
            ->method('cleanupOrphanedLinks');

        $this->useCase->execute(1);
    }

    // =========================================================================
    // executeWithResult() Tests
    // =========================================================================

    public function testExecuteWithResultReturnsSuccessOnDelete(): void
    {
        $this->repository->method('delete')
            ->willReturn(true);

        $this->association->method('cleanupOrphanedLinks');

        $result = $this->useCase->executeWithResult(1);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['count']);
    }

    public function testExecuteWithResultReturnsFailureOnNotFound(): void
    {
        $this->repository->method('delete')
            ->willReturn(false);

        $result = $this->useCase->executeWithResult(999);

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['count']);
    }

    // =========================================================================
    // executeMultiple() Tests
    // =========================================================================

    public function testExecuteMultipleDeletesTagsSuccessfully(): void
    {
        $this->repository->expects($this->once())
            ->method('deleteMultiple')
            ->with([1, 2, 3])
            ->willReturn(3);

        $this->association->expects($this->once())
            ->method('cleanupOrphanedLinks');

        $count = $this->useCase->executeMultiple([1, 2, 3]);

        $this->assertEquals(3, $count);
    }

    public function testExecuteMultipleReturnsZeroForEmptyArray(): void
    {
        $this->repository->expects($this->never())
            ->method('deleteMultiple');

        $this->association->expects($this->never())
            ->method('cleanupOrphanedLinks');

        $count = $this->useCase->executeMultiple([]);

        $this->assertEquals(0, $count);
    }

    public function testExecuteMultipleHandlesPartialDeletion(): void
    {
        $this->repository->method('deleteMultiple')
            ->with([1, 2, 999])
            ->willReturn(2);

        $this->association->expects($this->once())
            ->method('cleanupOrphanedLinks');

        $count = $this->useCase->executeMultiple([1, 2, 999]);

        $this->assertEquals(2, $count);
    }

    public function testExecuteMultipleDoesNotCleanupWhenNoneDeleted(): void
    {
        $this->repository->method('deleteMultiple')
            ->willReturn(0);

        $this->association->expects($this->never())
            ->method('cleanupOrphanedLinks');

        $count = $this->useCase->executeMultiple([999, 998]);

        $this->assertEquals(0, $count);
    }

    // =========================================================================
    // executeMultipleWithResult() Tests
    // =========================================================================

    public function testExecuteMultipleWithResultReturnsSuccess(): void
    {
        $this->repository->method('deleteMultiple')
            ->willReturn(3);

        $this->association->method('cleanupOrphanedLinks');

        $result = $this->useCase->executeMultipleWithResult([1, 2, 3]);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['count']);
    }

    public function testExecuteMultipleWithResultReturnsFailureWhenNoneDeleted(): void
    {
        $this->repository->method('deleteMultiple')
            ->willReturn(0);

        $result = $this->useCase->executeMultipleWithResult([999]);

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['count']);
    }

    public function testExecuteMultipleWithResultReturnsFailureForEmptyArray(): void
    {
        $result = $this->useCase->executeMultipleWithResult([]);

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['count']);
    }

    // =========================================================================
    // executeAll() Tests
    // =========================================================================

    public function testExecuteAllDeletesAllTags(): void
    {
        $this->repository->expects($this->once())
            ->method('deleteAll')
            ->with('')
            ->willReturn(10);

        $this->association->expects($this->once())
            ->method('cleanupOrphanedLinks');

        $count = $this->useCase->executeAll();

        $this->assertEquals(10, $count);
    }

    public function testExecuteAllWithQueryFilter(): void
    {
        $this->repository->expects($this->once())
            ->method('deleteAll')
            ->with('test*')
            ->willReturn(5);

        $this->association->expects($this->once())
            ->method('cleanupOrphanedLinks');

        $count = $this->useCase->executeAll('test*');

        $this->assertEquals(5, $count);
    }

    public function testExecuteAllReturnsZeroWhenNoMatches(): void
    {
        $this->repository->method('deleteAll')
            ->willReturn(0);

        $this->association->expects($this->never())
            ->method('cleanupOrphanedLinks');

        $count = $this->useCase->executeAll('nomatch*');

        $this->assertEquals(0, $count);
    }

    // =========================================================================
    // executeAllWithResult() Tests
    // =========================================================================

    public function testExecuteAllWithResultReturnsSuccess(): void
    {
        $this->repository->method('deleteAll')
            ->willReturn(5);

        $this->association->method('cleanupOrphanedLinks');

        $result = $this->useCase->executeAllWithResult('filter*');

        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['count']);
    }

    public function testExecuteAllWithResultReturnsFailureWhenNoMatches(): void
    {
        $this->repository->method('deleteAll')
            ->willReturn(0);

        $result = $this->useCase->executeAllWithResult('nomatch*');

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['count']);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testExecuteWithZeroId(): void
    {
        $this->repository->method('delete')
            ->with(0)
            ->willReturn(false);

        $result = $this->useCase->execute(0);

        $this->assertFalse($result);
    }

    public function testExecuteMultipleWithDuplicateIds(): void
    {
        $this->repository->expects($this->once())
            ->method('deleteMultiple')
            ->with([1, 1, 2])
            ->willReturn(2);

        $this->association->method('cleanupOrphanedLinks');

        $count = $this->useCase->executeMultiple([1, 1, 2]);

        $this->assertEquals(2, $count);
    }

    public function testExecuteAllWithEmptyQuery(): void
    {
        $this->repository->expects($this->once())
            ->method('deleteAll')
            ->with('');

        $this->repository->method('deleteAll')
            ->willReturn(0);

        $this->useCase->executeAll('');
    }

    public function testExecuteAllWithWildcardQuery(): void
    {
        $this->repository->expects($this->once())
            ->method('deleteAll')
            ->with('*')
            ->willReturn(10);

        $this->association->method('cleanupOrphanedLinks');

        $count = $this->useCase->executeAll('*');

        $this->assertEquals(10, $count);
    }
}
