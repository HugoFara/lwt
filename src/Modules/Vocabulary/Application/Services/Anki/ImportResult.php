<?php

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Application\Services\Anki;

/**
 * Outcome of an .apkg import.
 *
 * @phpstan-immutable
 */
final class ImportResult
{
    public function __construct(
        public readonly int $totalNotes,
        public readonly int $updated,
        public readonly int $unchanged,
        public readonly int $skippedUnknown,
        public readonly int $skippedMissing,
        public readonly int $statusSetToIgnored,
        public readonly int $tagsChanged,
    ) {
    }
}
