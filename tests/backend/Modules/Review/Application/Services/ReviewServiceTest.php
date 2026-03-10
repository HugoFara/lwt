<?php

/**
 * Unit tests for ReviewService.
 *
 * PHP version 8.1
 *
 * @category Testing
 * @package  Lwt\Tests\Modules\Review\Application\Services
 * @license  Unlicense <http://unlicense.org/>
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Tests\Modules\Review\Application\Services;

use Lwt\Modules\Review\Application\Services\ReviewService;
use Lwt\Modules\Review\Infrastructure\SessionStateManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ReviewService.
 *
 * Tests pure logic methods directly and session-dependent methods
 * via mocked SessionStateManager. DB-dependent methods are skipped
 * when no database connection is available.
 *
 * @since 3.0.0
 */
class ReviewServiceTest extends TestCase
{
    private ReviewService $service;
    private SessionStateManager&MockObject $sessionManager;

    protected function setUp(): void
    {
        $this->sessionManager = $this->createMock(SessionStateManager::class);
        $this->service = new ReviewService(null, $this->sessionManager);
    }

    // =========================================================================
    // getReviewIdentifier()
    // =========================================================================

    #[Test]
    public function getReviewIdentifierWithSelection2ReturnsWords(): void
    {
        $result = $this->service->getReviewIdentifier(2, '(10,20,30)', null, null);
        $this->assertSame('words', $result[0]);
        $this->assertSame([10, 20, 30], $result[1]);
    }

    #[Test]
    public function getReviewIdentifierWithSelection3ReturnsTexts(): void
    {
        $result = $this->service->getReviewIdentifier(3, '(5,15)', null, null);
        $this->assertSame('texts', $result[0]);
        $this->assertSame([5, 15], $result[1]);
    }

    #[Test]
    public function getReviewIdentifierWithSelection1ReturnsEmpty(): void
    {
        $result = $this->service->getReviewIdentifier(1, '(10)', null, null);
        $this->assertSame('', $result[0]);
        $this->assertSame('', $result[1]);
    }

    #[Test]
    public function getReviewIdentifierWithLangReturnsLang(): void
    {
        $result = $this->service->getReviewIdentifier(null, null, 7, null);
        $this->assertSame('lang', $result[0]);
        $this->assertSame(7, $result[1]);
    }

    #[Test]
    public function getReviewIdentifierWithTextReturnsText(): void
    {
        $result = $this->service->getReviewIdentifier(null, null, null, 42);
        $this->assertSame('text', $result[0]);
        $this->assertSame(42, $result[1]);
    }

    #[Test]
    public function getReviewIdentifierWithAllNullReturnsEmpty(): void
    {
        $result = $this->service->getReviewIdentifier(null, null, null, null);
        $this->assertSame('', $result[0]);
        $this->assertSame('', $result[1]);
    }

    #[Test]
    public function getReviewIdentifierSelectionTakesPriorityOverLang(): void
    {
        $result = $this->service->getReviewIdentifier(2, '(1)', 5, null);
        $this->assertSame('words', $result[0]);
    }

    #[Test]
    public function getReviewIdentifierLangTakesPriorityOverText(): void
    {
        $result = $this->service->getReviewIdentifier(null, null, 3, 10);
        $this->assertSame('lang', $result[0]);
        $this->assertSame(3, $result[1]);
    }

    #[Test]
    public function getReviewIdentifierSingleWordId(): void
    {
        $result = $this->service->getReviewIdentifier(2, '99', null, null);
        $this->assertSame('words', $result[0]);
        $this->assertSame([99], $result[1]);
    }

    #[Test]
    public function getReviewIdentifierTrimsParentheses(): void
    {
        $result = $this->service->getReviewIdentifier(3, '(1,2,3)', null, null);
        $this->assertSame([1, 2, 3], $result[1]);
    }

    // =========================================================================
    // getReviewSql()
    // =========================================================================

    #[Test]
    public function getReviewSqlWordsReturnsSqlWithPlaceholders(): void
    {
        $result = $this->service->getReviewSql('words', [10, 20]);
        $this->assertArrayHasKey('sql', $result);
        $this->assertArrayHasKey('params', $result);
        $this->assertStringContainsString('WoID IN (?,?)', $result['sql']);
        $this->assertSame([10, 20], $result['params']);
    }

    #[Test]
    public function getReviewSqlTextsSqlContainsWordOccurrences(): void
    {
        $result = $this->service->getReviewSql('texts', [5]);
        $this->assertStringContainsString('word_occurrences', $result['sql']);
        $this->assertStringContainsString('Ti2TxID IN (?)', $result['sql']);
        $this->assertSame([5], $result['params']);
    }

    #[Test]
    public function getReviewSqlLangReturnsSingleParam(): void
    {
        $result = $this->service->getReviewSql('lang', 7);
        $this->assertStringContainsString('WoLgID = ?', $result['sql']);
        $this->assertSame([7], $result['params']);
    }

    #[Test]
    public function getReviewSqlTextReturnsSingleParam(): void
    {
        $result = $this->service->getReviewSql('text', 42);
        $this->assertStringContainsString('Ti2TxID = ?', $result['sql']);
        $this->assertSame([42], $result['params']);
    }

    #[Test]
    public function getReviewSqlInvalidSelectorThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid selector 'invalid'");
        $this->service->getReviewSql('invalid', 1);
    }

    #[Test]
    public function getReviewSqlWordsWithSingleIntCastsToArray(): void
    {
        $result = $this->service->getReviewSql('words', 42);
        $this->assertStringContainsString('WoID IN (?)', $result['sql']);
        $this->assertSame([42], $result['params']);
    }

    #[Test]
    public function getReviewSqlLangWithArrayUsesFirstElement(): void
    {
        $result = $this->service->getReviewSql('lang', [3, 5]);
        $this->assertSame([3], $result['params']);
    }

    #[Test]
    public function getReviewSqlTextWithArrayUsesFirstElement(): void
    {
        $result = $this->service->getReviewSql('text', [10, 20]);
        $this->assertSame([10], $result['params']);
    }

    // =========================================================================
    // buildSelectionReviewSql()
    // =========================================================================

    #[Test]
    public function buildSelectionReviewSqlType2ReturnsWordsSql(): void
    {
        $result = $this->service->buildSelectionReviewSql(2, '10,20,30');
        $this->assertNotNull($result);
        $this->assertStringContainsString('WoID IN', $result['sql']);
        $this->assertSame([10, 20, 30], $result['params']);
    }

    #[Test]
    public function buildSelectionReviewSqlType3ReturnsTextsSql(): void
    {
        $result = $this->service->buildSelectionReviewSql(3, '5,15');
        $this->assertNotNull($result);
        $this->assertStringContainsString('word_occurrences', $result['sql']);
        $this->assertSame([5, 15], $result['params']);
    }

    #[Test]
    public function buildSelectionReviewSqlInvalidTypeReturnsNull(): void
    {
        $result = $this->service->buildSelectionReviewSql(1, '10');
        $this->assertNull($result);
    }

    #[Test]
    public function buildSelectionReviewSqlType4ReturnsNull(): void
    {
        $result = $this->service->buildSelectionReviewSql(4, '10');
        $this->assertNull($result);
    }

    #[Test]
    public function buildSelectionReviewSqlTrimsParentheses(): void
    {
        $result = $this->service->buildSelectionReviewSql(2, '(7,8,9)');
        $this->assertNotNull($result);
        $this->assertSame([7, 8, 9], $result['params']);
    }

    // =========================================================================
    // calculateNewStatus()
    // =========================================================================

    #[Test]
    public function calculateNewStatusIncrementsStatus(): void
    {
        $this->assertSame(3, $this->service->calculateNewStatus(2, 1));
    }

    #[Test]
    public function calculateNewStatusDecrementsStatus(): void
    {
        $this->assertSame(2, $this->service->calculateNewStatus(3, -1));
    }

    #[Test]
    public function calculateNewStatusClampsAtMinimum(): void
    {
        $this->assertSame(1, $this->service->calculateNewStatus(1, -1));
    }

    #[Test]
    public function calculateNewStatusClampsAtMaximum(): void
    {
        $this->assertSame(5, $this->service->calculateNewStatus(5, 1));
    }

    #[Test]
    public function calculateNewStatusNoChange(): void
    {
        $this->assertSame(3, $this->service->calculateNewStatus(3, 0));
    }

    #[Test]
    public function calculateNewStatusLargeNegativeClampsToOne(): void
    {
        $this->assertSame(1, $this->service->calculateNewStatus(2, -10));
    }

    #[Test]
    public function calculateNewStatusLargePositiveClampsToFive(): void
    {
        $this->assertSame(5, $this->service->calculateNewStatus(3, 10));
    }

    // =========================================================================
    // calculateStatusChange()
    // =========================================================================

    #[Test]
    public function calculateStatusChangePositive(): void
    {
        $this->assertSame(1, $this->service->calculateStatusChange(2, 4));
    }

    #[Test]
    public function calculateStatusChangeNegative(): void
    {
        $this->assertSame(-1, $this->service->calculateStatusChange(4, 2));
    }

    #[Test]
    public function calculateStatusChangeNoChange(): void
    {
        $this->assertSame(0, $this->service->calculateStatusChange(3, 3));
    }

    #[Test]
    public function calculateStatusChangeByOne(): void
    {
        $this->assertSame(1, $this->service->calculateStatusChange(1, 2));
        $this->assertSame(-1, $this->service->calculateStatusChange(2, 1));
    }

    // =========================================================================
    // clampReviewType()
    // =========================================================================

    #[Test]
    public function clampReviewTypeInRange(): void
    {
        $this->assertSame(3, $this->service->clampReviewType(3));
    }

    #[Test]
    public function clampReviewTypeBelowMinimum(): void
    {
        $this->assertSame(1, $this->service->clampReviewType(0));
        $this->assertSame(1, $this->service->clampReviewType(-5));
    }

    #[Test]
    public function clampReviewTypeAboveMaximum(): void
    {
        $this->assertSame(5, $this->service->clampReviewType(6));
        $this->assertSame(5, $this->service->clampReviewType(100));
    }

    #[Test]
    public function clampReviewTypeBoundaryValues(): void
    {
        $this->assertSame(1, $this->service->clampReviewType(1));
        $this->assertSame(5, $this->service->clampReviewType(5));
    }

    // =========================================================================
    // isWordMode()
    // =========================================================================

    #[Test]
    public function isWordModeReturnsFalseForTypesOneToThree(): void
    {
        $this->assertFalse($this->service->isWordMode(1));
        $this->assertFalse($this->service->isWordMode(2));
        $this->assertFalse($this->service->isWordMode(3));
    }

    #[Test]
    public function isWordModeReturnsTrueForTypesFourAndFive(): void
    {
        $this->assertTrue($this->service->isWordMode(4));
        $this->assertTrue($this->service->isWordMode(5));
    }

    // =========================================================================
    // getBaseReviewType()
    // =========================================================================

    #[Test]
    public function getBaseReviewTypeForLowTypes(): void
    {
        $this->assertSame(1, $this->service->getBaseReviewType(1));
        $this->assertSame(2, $this->service->getBaseReviewType(2));
        $this->assertSame(3, $this->service->getBaseReviewType(3));
    }

    #[Test]
    public function getBaseReviewTypeForHighTypes(): void
    {
        $this->assertSame(1, $this->service->getBaseReviewType(4));
        $this->assertSame(2, $this->service->getBaseReviewType(5));
    }

    // =========================================================================
    // updateSessionProgress()
    // =========================================================================

    #[Test]
    public function updateSessionProgressCorrectAnswer(): void
    {
        $this->sessionManager->method('getRawSessionData')
            ->willReturn(['start' => 1000, 'total' => 10, 'correct' => 3, 'wrong' => 2]);

        $this->sessionManager->expects($this->once())
            ->method('recordAnswer')
            ->with(true);

        $result = $this->service->updateSessionProgress(1);

        $this->assertSame(10, $result['total']);
        $this->assertSame(2, $result['wrong']);
        $this->assertSame(4, $result['correct']);
        $this->assertSame(4, $result['remaining']);
    }

    #[Test]
    public function updateSessionProgressWrongAnswer(): void
    {
        $this->sessionManager->method('getRawSessionData')
            ->willReturn(['start' => 1000, 'total' => 10, 'correct' => 3, 'wrong' => 2]);

        $this->sessionManager->expects($this->once())
            ->method('recordAnswer')
            ->with(false);

        $result = $this->service->updateSessionProgress(-1);

        $this->assertSame(10, $result['total']);
        $this->assertSame(3, $result['wrong']);
        $this->assertSame(3, $result['correct']);
        $this->assertSame(4, $result['remaining']);
    }

    #[Test]
    public function updateSessionProgressNoChangeIsCorrect(): void
    {
        $this->sessionManager->method('getRawSessionData')
            ->willReturn(['start' => 1000, 'total' => 5, 'correct' => 1, 'wrong' => 1]);

        $this->sessionManager->expects($this->once())
            ->method('recordAnswer')
            ->with(true);

        $result = $this->service->updateSessionProgress(0);

        $this->assertSame(2, $result['correct']);
    }

    #[Test]
    public function updateSessionProgressNoRemainingDoesNotRecord(): void
    {
        $this->sessionManager->method('getRawSessionData')
            ->willReturn(['start' => 1000, 'total' => 5, 'correct' => 3, 'wrong' => 2]);

        $this->sessionManager->expects($this->never())
            ->method('recordAnswer');

        $result = $this->service->updateSessionProgress(1);

        $this->assertSame(0, $result['remaining']);
        $this->assertSame(3, $result['correct']);
        $this->assertSame(2, $result['wrong']);
    }

    // =========================================================================
    // initializeReviewSession()
    // =========================================================================

    #[Test]
    public function initializeReviewSessionCreatesNewSessionWhenNoneExists(): void
    {
        $this->sessionManager->method('getSession')->willReturn(null);

        $this->sessionManager->expects($this->once())
            ->method('saveSession')
            ->with($this->callback(function ($session) {
                return $session instanceof \Lwt\Modules\Review\Domain\ReviewSession
                    && $session->getTotal() === 15
                    && $session->getCorrect() === 0
                    && $session->getWrong() === 0;
            }));

        $this->service->initializeReviewSession(15);
    }

    #[Test]
    public function initializeReviewSessionUpdatesExistingSession(): void
    {
        $existingSession = new \Lwt\Modules\Review\Domain\ReviewSession(1000, 10, 3, 2);
        $this->sessionManager->method('getSession')->willReturn($existingSession);

        $this->sessionManager->expects($this->once())
            ->method('saveSession')
            ->with($this->callback(function ($session) {
                return $session instanceof \Lwt\Modules\Review\Domain\ReviewSession
                    && $session->getTotal() === 20;
            }));

        $this->service->initializeReviewSession(20);
    }

    // =========================================================================
    // getReviewSessionData()
    // =========================================================================

    #[Test]
    public function getReviewSessionDataReturnsFormattedData(): void
    {
        $this->sessionManager->method('getRawSessionData')
            ->willReturn(['start' => 5000, 'total' => 25, 'correct' => 10, 'wrong' => 5]);

        $result = $this->service->getReviewSessionData();

        $this->assertSame(5000, $result['start']);
        $this->assertSame(25, $result['total']);
        $this->assertSame(10, $result['correct']);
        $this->assertSame(5, $result['wrong']);
    }

    #[Test]
    public function getReviewSessionDataContainsAllKeys(): void
    {
        $this->sessionManager->method('getRawSessionData')
            ->willReturn(['start' => 0, 'total' => 0, 'correct' => 0, 'wrong' => 0]);

        $result = $this->service->getReviewSessionData();

        $this->assertArrayHasKey('start', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('correct', $result);
        $this->assertArrayHasKey('wrong', $result);
        $this->assertCount(4, $result);
    }

    // =========================================================================
    // getReviewSql() — edge cases
    // =========================================================================

    #[Test]
    public function getReviewSqlWordsMultiplePlaceholders(): void
    {
        $result = $this->service->getReviewSql('words', [1, 2, 3, 4, 5]);
        $this->assertStringContainsString('?,?,?,?,?', $result['sql']);
        $this->assertCount(5, $result['params']);
    }

    #[Test]
    public function getReviewSqlTextsMultipleIds(): void
    {
        $result = $this->service->getReviewSql('texts', [10, 20, 30]);
        $this->assertStringContainsString('Ti2TxID IN (?,?,?)', $result['sql']);
        $this->assertSame([10, 20, 30], $result['params']);
    }

    #[Test]
    public function getReviewSqlLangWithEmptyArrayUsesZero(): void
    {
        $result = $this->service->getReviewSql('lang', []);
        $this->assertSame([0], $result['params']);
    }

    #[Test]
    public function getReviewSqlTextWithEmptyArrayUsesZero(): void
    {
        $result = $this->service->getReviewSql('text', []);
        $this->assertSame([0], $result['params']);
    }

    // =========================================================================
    // Constructor
    // =========================================================================

    #[Test]
    public function canBeInstantiatedWithMockedDependencies(): void
    {
        $service = new ReviewService(null, $this->sessionManager);
        $this->assertInstanceOf(ReviewService::class, $service);
    }
}
