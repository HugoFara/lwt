<?php

declare(strict_types=1);

namespace Lwt\Modules\Review\Domain\Scheduling;

/**
 * The four FSRS review grades.
 *
 * Values match Anki's `revlog.ease` so a review can be exported to an .apkg
 * without translation (issue #228).
 */
enum Rating: int
{
    case Again = 1;
    case Hard = 2;
    case Good = 3;
    case Easy = 4;

    /**
     * Map LWT's legacy binary answer onto a grade.
     *
     * The old review UI only distinguished "I knew it" from "I didn't", which
     * is exactly Again/Good. Hard and Easy carry information the binary UI
     * never collected, so they are only ever produced by the graded endpoint.
     *
     * @param bool $correct Whether the user recalled the term
     */
    public static function fromBinary(bool $correct): self
    {
        return $correct ? self::Good : self::Again;
    }

    /**
     * Legacy status nudge for this grade (+1 / -1).
     *
     * Phase 2a keeps WoStatus moving exactly as it did before, so the reading
     * view is unchanged whether a review arrives through the binary or the
     * graded endpoint.
     */
    public function legacyStatusChange(): int
    {
        return $this === self::Again ? -1 : 1;
    }

    /**
     * Whether this grade counts as a successful recall.
     */
    public function isRecall(): bool
    {
        return $this !== self::Again;
    }
}
