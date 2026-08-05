<?php

declare(strict_types=1);

namespace Lwt\Modules\Review\Domain\Scheduling;

use DateTimeImmutable;

/**
 * A term's FSRS memory state: the whole of what the scheduler needs to decide
 * when the term is next due.
 *
 * Immutable — a review produces a new instance rather than mutating this one,
 * which keeps {@see SchedulerInterface} implementations pure and trivially
 * testable against the reference vectors.
 */
final class MemoryState
{
    public function __construct(
        public readonly float $stability,
        public readonly float $difficulty,
        public readonly DateTimeImmutable $due,
        public readonly ?DateTimeImmutable $lastReview = null,
        public readonly int $reps = 0,
        public readonly int $lapses = 0,
        public readonly SchedulingState $state = SchedulingState::New,
    ) {
    }

    /**
     * Whole days elapsed since the last review, floored at 0.
     *
     * FSRS works in whole days; a same-day repeat yields 0, which is what
     * routes the update through the short-term stability formula.
     */
    public function elapsedDays(DateTimeImmutable $now): int
    {
        if ($this->lastReview === null) {
            return 0;
        }

        $days = (int) $this->lastReview->diff($now)->days;

        return max(0, $days);
    }

    /**
     * Whether this term is due for review at the given moment.
     */
    public function isDue(DateTimeImmutable $now): bool
    {
        return $this->due <= $now;
    }
}
