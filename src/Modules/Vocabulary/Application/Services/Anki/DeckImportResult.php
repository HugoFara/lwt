<?php

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Application\Services\Anki;

/**
 * Outcome of importing a foreign Anki deck.
 *
 * Every note is accounted for in exactly one bucket, so the totals add up and
 * the user can see why a big deck produced fewer terms than they expected.
 */
final class DeckImportResult
{
    /**
     * @param int                $totalNotes    Notes read for the chosen notetype
     * @param int                $created       New LWT terms created
     * @param int                $skippedExisting Terms LWT already had
     * @param int                $skippedEmpty  Notes whose term field was blank
     * @param int                $skippedTooLong Terms exceeding the WoText column
     * @param array<int, int>    $statusCounts  status => number of terms created
     * @param list<string>       $samples       A few created terms, for the summary
     */
    public function __construct(
        public readonly int $totalNotes,
        public readonly int $created,
        public readonly int $skippedExisting,
        public readonly int $skippedEmpty,
        public readonly int $skippedTooLong,
        public readonly array $statusCounts,
        public readonly array $samples,
    ) {
    }
}
