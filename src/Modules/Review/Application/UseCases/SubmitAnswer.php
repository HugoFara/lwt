<?php

declare(strict_types=1);

/**
 * Submit Answer Use Case
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Review\Application\UseCases
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Review\Application\UseCases;

use Lwt\Modules\Review\Domain\ReviewRepositoryInterface;
use Lwt\Modules\Review\Domain\ReviewSession;
use Lwt\Modules\Review\Infrastructure\SessionStateManager;

/**
 * Use case for submitting an answer during review.
 *
 * Updates word status and session progress.
 *
 * @since 3.0.0
 */
class SubmitAnswer
{
    private ReviewRepositoryInterface $repository;
    private SessionStateManager $sessionManager;

    /**
     * Constructor.
     *
     * @param ReviewRepositoryInterface $repository     Review repository
     * @param SessionStateManager|null  $sessionManager Session manager (optional)
     */
    public function __construct(
        ReviewRepositoryInterface $repository,
        ?SessionStateManager $sessionManager = null
    ) {
        $this->repository = $repository;
        $this->sessionManager = $sessionManager ?? new SessionStateManager();
    }

    /**
     * Submit answer with explicit new status.
     *
     * @param int $wordId    Word ID
     * @param int $newStatus New status (1-5, 98, 99)
     *
     * @return array{
     *     success: bool,
     *     oldStatus: int,
     *     newStatus: int,
     *     oldScore: int,
     *     newScore: int,
     *     statusChange: int,
     *     progress: array{total: int, wrong: int, correct: int, remaining: int},
     *     error?: string
     * }
     */
    public function execute(int $wordId, int $newStatus): array
    {
        // Validate status
        if (!$this->isValidStatus($newStatus)) {
            return [
                'success' => false,
                'oldStatus' => 0,
                'newStatus' => 0,
                'oldScore' => 0,
                'newScore' => 0,
                'statusChange' => 0,
                'progress' => ['total' => 0, 'wrong' => 0, 'correct' => 0, 'remaining' => 0],
                'error' => 'Invalid status value'
            ];
        }

        // Update word status
        $result = $this->repository->updateWordStatus($wordId, $newStatus);

        // Calculate status change direction
        $statusChange = $this->calculateStatusChange($result['oldStatus'], $result['newStatus']);

        // Update session progress
        $progress = $this->updateSessionProgress($statusChange);

        return [
            'success' => true,
            'oldStatus' => $result['oldStatus'],
            'newStatus' => $result['newStatus'],
            'oldScore' => $result['oldScore'],
            'newScore' => $result['newScore'],
            'statusChange' => $statusChange,
            'progress' => $progress
        ];
    }

    /**
     * Submit answer with relative status change.
     *
     * @param int $wordId Word ID
     * @param int $change Change amount (+1 or -1)
     *
     * @return array Same as execute()
     */
    public function executeWithChange(int $wordId, int $change): array
    {
        // Get current status
        $currentStatus = $this->repository->getWordStatus($wordId);
        if ($currentStatus === null) {
            return [
                'success' => false,
                'oldStatus' => 0,
                'newStatus' => 0,
                'oldScore' => 0,
                'newScore' => 0,
                'statusChange' => 0,
                'progress' => ['total' => 0, 'wrong' => 0, 'correct' => 0, 'remaining' => 0],
                'error' => 'Word not found'
            ];
        }

        // Calculate new status
        $newStatus = $this->calculateNewStatus($currentStatus, $change);

        return $this->execute($wordId, $newStatus);
    }

    /**
     * Calculate new status based on change direction.
     *
     * @param int $currentStatus Current status
     * @param int $change        Change amount
     *
     * @return int New status
     */
    private function calculateNewStatus(int $currentStatus, int $change): int
    {
        if ($change > 0) {
            // Increment
            $newStatus = $currentStatus + 1;
            if ($newStatus === 6) {
                return 99; // 5 -> 99 (well-known)
            }
            if ($newStatus === 100) {
                return 1; // 99 -> 1 (wrap around)
            }
            return $newStatus;
        }

        // Decrement
        $newStatus = $currentStatus - 1;
        if ($newStatus === 0) {
            return 98; // 1 -> 98 (ignored)
        }
        if ($newStatus === 97) {
            return 5; // 98 -> 5 (wrap around)
        }
        return $newStatus;
    }

    /**
     * Calculate status change direction.
     *
     * @param int $oldStatus Old status
     * @param int $newStatus New status
     *
     * @return int -1, 0, or 1
     */
    private function calculateStatusChange(int $oldStatus, int $newStatus): int
    {
        $diff = $newStatus - $oldStatus;
        if ($diff < 0) {
            return -1;
        }
        if ($diff > 0) {
            return 1;
        }
        return 0;
    }

    /**
     * Check if status is valid.
     *
     * @param int $status Status value
     *
     * @return bool
     */
    private function isValidStatus(int $status): bool
    {
        return in_array($status, [1, 2, 3, 4, 5, 98, 99], true);
    }

    /**
     * Update session progress after answer.
     *
     * @param int $statusChange Status change direction
     *
     * @return array{total: int, wrong: int, correct: int, remaining: int}
     */
    private function updateSessionProgress(int $statusChange): array
    {
        $session = $this->sessionManager->getSession();
        if ($session === null) {
            return ['total' => 0, 'wrong' => 0, 'correct' => 0, 'remaining' => 0];
        }

        $session->recordAnswer($statusChange);
        $this->sessionManager->saveSession($session);

        return [
            'total' => $session->getTotal(),
            'wrong' => $session->getWrong(),
            'correct' => $session->getCorrect(),
            'remaining' => $session->remaining()
        ];
    }
}
