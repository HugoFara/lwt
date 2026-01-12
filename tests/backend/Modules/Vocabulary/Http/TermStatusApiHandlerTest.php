<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Http;

use Lwt\Modules\Vocabulary\Http\TermStatusApiHandler;
use Lwt\Modules\Vocabulary\Application\VocabularyFacade;
use Lwt\Modules\Vocabulary\Domain\Term;
use Lwt\Modules\Vocabulary\Domain\ValueObject\TermId;
use Lwt\Modules\Vocabulary\Domain\ValueObject\TermStatus;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for TermStatusApiHandler.
 *
 * Tests term status API operations including update, increment, and bulk operations.
 */
class TermStatusApiHandlerTest extends TestCase
{
    /** @var VocabularyFacade&MockObject */
    private VocabularyFacade $facade;

    private TermStatusApiHandler $handler;

    protected function setUp(): void
    {
        $this->facade = $this->createMock(VocabularyFacade::class);
        $this->handler = new TermStatusApiHandler($this->facade);
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    public function testConstructorCreatesValidHandler(): void
    {
        $this->assertInstanceOf(TermStatusApiHandler::class, $this->handler);
    }

    public function testConstructorAcceptsNullParameter(): void
    {
        $handler = new TermStatusApiHandler(null);
        $this->assertInstanceOf(TermStatusApiHandler::class, $handler);
    }

    // =========================================================================
    // updateStatus tests
    // =========================================================================

    public function testUpdateStatusReturnsErrorForInvalidStatus(): void
    {
        $result = $this->handler->updateStatus(1, 100);

        $this->assertFalse($result['success']);
        $this->assertSame('Invalid status', $result['error']);
    }

    public function testUpdateStatusReturnsErrorForZeroStatus(): void
    {
        $result = $this->handler->updateStatus(1, 0);

        $this->assertFalse($result['success']);
        $this->assertSame('Invalid status', $result['error']);
    }

    public function testUpdateStatusReturnsErrorForNegativeStatus(): void
    {
        $result = $this->handler->updateStatus(1, -1);

        $this->assertFalse($result['success']);
        $this->assertSame('Invalid status', $result['error']);
    }

    public function testUpdateStatusReturnsErrorForSixStatus(): void
    {
        $result = $this->handler->updateStatus(1, 6);

        $this->assertFalse($result['success']);
        $this->assertSame('Invalid status', $result['error']);
    }

    public function testUpdateStatusAcceptsValidStatuses(): void
    {
        $validStatuses = [1, 2, 3, 4, 5, 98, 99];

        foreach ($validStatuses as $status) {
            $this->facade->method('updateStatus')
                ->willReturn(true);

            $result = $this->handler->updateStatus(1, $status);

            $this->assertTrue($result['success']);
            $this->assertSame($status, $result['status']);
        }
    }

    public function testUpdateStatusReturnsErrorWhenFacadeFails(): void
    {
        $this->facade->method('updateStatus')
            ->willReturn(false);

        $result = $this->handler->updateStatus(1, 3);

        $this->assertFalse($result['success']);
        $this->assertSame('Failed to update status', $result['error']);
    }

    public function testUpdateStatusCallsFacadeWithCorrectParams(): void
    {
        $this->facade->expects($this->once())
            ->method('updateStatus')
            ->with(123, 4)
            ->willReturn(true);

        $this->handler->updateStatus(123, 4);
    }

    // =========================================================================
    // incrementStatus tests
    // =========================================================================

    public function testIncrementStatusReturnsErrorWhenTermNotFound(): void
    {
        $this->facade->method('getTerm')
            ->willReturn(null);

        $result = $this->handler->incrementStatus(999, true);

        $this->assertFalse($result['success']);
        $this->assertSame('Term not found', $result['error']);
    }

    public function testIncrementStatusCallsAdvanceStatusWhenUp(): void
    {
        $term = $this->createMockTerm(1, 2);
        $updatedTerm = $this->createMockTerm(1, 3);

        $this->facade->method('getTerm')
            ->willReturnOnConsecutiveCalls($term, $updatedTerm);
        $this->facade->expects($this->once())
            ->method('advanceStatus')
            ->with(1)
            ->willReturn(true);

        $result = $this->handler->incrementStatus(1, true);

        $this->assertTrue($result['success']);
        $this->assertSame(3, $result['status']);
    }

    public function testIncrementStatusCallsDecreaseStatusWhenDown(): void
    {
        $term = $this->createMockTerm(1, 3);
        $updatedTerm = $this->createMockTerm(1, 2);

        $this->facade->method('getTerm')
            ->willReturnOnConsecutiveCalls($term, $updatedTerm);
        $this->facade->expects($this->once())
            ->method('decreaseStatus')
            ->with(1)
            ->willReturn(true);

        $result = $this->handler->incrementStatus(1, false);

        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['status']);
    }

    public function testIncrementStatusReturnsErrorWhenFacadeFails(): void
    {
        $term = $this->createMockTerm(1, 2);

        $this->facade->method('getTerm')
            ->willReturn($term);
        $this->facade->method('advanceStatus')
            ->willReturn(false);

        $result = $this->handler->incrementStatus(1, true);

        $this->assertFalse($result['success']);
        $this->assertSame('Failed to update status', $result['error']);
    }

    // =========================================================================
    // bulkUpdateStatus tests
    // =========================================================================

    public function testBulkUpdateStatusReturnsErrorForInvalidStatus(): void
    {
        $result = $this->handler->bulkUpdateStatus([1, 2, 3], 100);

        $this->assertSame(0, $result['count']);
        $this->assertSame('Invalid status', $result['error']);
    }

    public function testBulkUpdateStatusReturnsErrorForEmptyArray(): void
    {
        $result = $this->handler->bulkUpdateStatus([], 3);

        $this->assertSame(0, $result['count']);
        $this->assertSame('No term IDs provided', $result['error']);
    }

    public function testBulkUpdateStatusCallsFacadeWithValidData(): void
    {
        $this->facade->expects($this->once())
            ->method('bulkUpdateStatus')
            ->with([1, 2, 3], 4)
            ->willReturn(3);

        $result = $this->handler->bulkUpdateStatus([1, 2, 3], 4);

        $this->assertSame(3, $result['count']);
        $this->assertArrayNotHasKey('error', $result);
    }

    public function testBulkUpdateStatusReturnsCount(): void
    {
        $this->facade->method('bulkUpdateStatus')
            ->willReturn(5);

        $result = $this->handler->bulkUpdateStatus([1, 2, 3, 4, 5], 99);

        $this->assertSame(5, $result['count']);
    }

    // =========================================================================
    // getNewStatus tests
    // =========================================================================

    public function testGetNewStatusIncrementsNormalStatus(): void
    {
        $this->assertSame(2, $this->handler->getNewStatus(1, true));
        $this->assertSame(3, $this->handler->getNewStatus(2, true));
        $this->assertSame(4, $this->handler->getNewStatus(3, true));
        $this->assertSame(5, $this->handler->getNewStatus(4, true));
        $this->assertSame(99, $this->handler->getNewStatus(5, true));
    }

    public function testGetNewStatusDecrementsNormalStatus(): void
    {
        $this->assertSame(4, $this->handler->getNewStatus(5, false));
        $this->assertSame(3, $this->handler->getNewStatus(4, false));
        $this->assertSame(2, $this->handler->getNewStatus(3, false));
        $this->assertSame(1, $this->handler->getNewStatus(2, false));
        $this->assertSame(98, $this->handler->getNewStatus(1, false));
    }

    public function testGetNewStatusWrapsFromIgnoredIncrement(): void
    {
        // 98 + 1 = 99, then condition currstatus == 99 triggers, returns 1
        $this->assertSame(1, $this->handler->getNewStatus(98, true));
    }

    public function testGetNewStatusWrapsFromWellKnownDecrement(): void
    {
        // 99 - 1 = 98, then condition currstatus == 98 triggers, returns 5
        $this->assertSame(5, $this->handler->getNewStatus(99, false));
    }

    public function testGetNewStatusIncrementsWellKnown(): void
    {
        // 99 + 1 = 100 (no wrap - edge case in implementation)
        $this->assertSame(100, $this->handler->getNewStatus(99, true));
    }

    public function testGetNewStatusDecrementsIgnored(): void
    {
        // 98 - 1 = 97 (no wrap - edge case in implementation)
        $this->assertSame(97, $this->handler->getNewStatus(98, false));
    }

    // =========================================================================
    // formatUpdateStatus tests (thin wrapper)
    // =========================================================================

    public function testFormatUpdateStatusDelegatesToUpdateStatus(): void
    {
        $this->facade->method('updateStatus')
            ->willReturn(true);

        $result = $this->handler->formatUpdateStatus(1, 3);

        $this->assertTrue($result['success']);
    }

    // =========================================================================
    // formatIncrementStatus tests (thin wrapper)
    // =========================================================================

    public function testFormatIncrementStatusDelegatesToIncrementStatus(): void
    {
        $this->facade->method('getTerm')
            ->willReturn(null);

        $result = $this->handler->formatIncrementStatus(1, true);

        $this->assertFalse($result['success']);
    }

    // =========================================================================
    // formatBulkUpdateStatus tests (thin wrapper)
    // =========================================================================

    public function testFormatBulkUpdateStatusDelegatesToBulkUpdateStatus(): void
    {
        $result = $this->handler->formatBulkUpdateStatus([], 3);

        $this->assertSame(0, $result['count']);
    }

    // =========================================================================
    // formatBulkStatus tests (alias)
    // =========================================================================

    public function testFormatBulkStatusIsAliasForFormatBulkUpdateStatus(): void
    {
        $result = $this->handler->formatBulkStatus([], 3);

        $this->assertSame(0, $result['count']);
    }

    // =========================================================================
    // formatGetStatuses tests
    // =========================================================================

    public function testFormatGetStatusesReturnsStatusesArray(): void
    {
        $result = $this->handler->formatGetStatuses();

        $this->assertArrayHasKey('statuses', $result);
        $this->assertIsArray($result['statuses']);
    }

    // =========================================================================
    // Helper methods
    // =========================================================================

    /**
     * Create a mock Term object.
     *
     * @param int $id     Term ID
     * @param int $status Status
     *
     * @return Term&MockObject
     */
    private function createMockTerm(int $id, int $status): Term
    {
        // Use real value objects since they are final readonly
        $termId = TermId::fromInt($id);
        $termStatus = TermStatus::fromInt($status);

        $term = $this->createMock(Term::class);
        $term->method('id')->willReturn($termId);
        $term->method('status')->willReturn($termStatus);

        return $term;
    }
}
