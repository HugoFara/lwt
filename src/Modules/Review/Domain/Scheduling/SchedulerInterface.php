<?php

declare(strict_types=1);

namespace Lwt\Modules\Review\Domain\Scheduling;

use DateTimeImmutable;

/**
 * A spaced-repetition scheduler.
 *
 * The interface exists so the algorithm is swappable: FSRS-6 ships as the
 * implementation, but the legacy Leitner curve could be wrapped behind this
 * too, and a future SM-2 or per-user-optimised variant slots in without
 * touching the use cases.
 */
interface SchedulerInterface
{
    /**
     * Apply a graded review and return the resulting memory state.
     *
     * @param MemoryState|null  $current The term's state, or null if it has
     *                                   never been graded (a first review).
     * @param Rating            $rating  The grade the user gave.
     * @param DateTimeImmutable $now     When the review happened.
     */
    public function review(?MemoryState $current, Rating $rating, DateTimeImmutable $now): SchedulingResult;

    /**
     * Probability of recalling the term right now, in [0, 1].
     *
     * Returns 0.0 for a term that has never been reviewed.
     */
    public function retrievability(?MemoryState $state, DateTimeImmutable $now): float;
}
