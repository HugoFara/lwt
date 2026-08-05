<?php

declare(strict_types=1);

namespace Lwt\Modules\Review\Domain\Scheduling;

/**
 * What a scheduler returns for one graded review: the term's new memory state
 * plus the interval it was scheduled with.
 *
 * The interval is carried separately because it is what {@see review_log}
 * records (`RlScheduledDays`) and what Anki's `cards.ivl` expects — deriving it
 * back out of the due date would lose the scheduler's own rounding.
 */
final class SchedulingResult
{
    public function __construct(
        public readonly MemoryState $state,
        public readonly int $intervalDays,
        public readonly int $elapsedDays,
    ) {
    }
}
