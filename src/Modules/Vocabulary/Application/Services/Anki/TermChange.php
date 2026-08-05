<?php

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Application\Services\Anki;

/**
 * Per-term diff produced by ApkgTermMapper::applyNoteToTerm. The orchestrator
 * folds these into ImportResult counters; tests assert on individual fields.
 *
 * Tag changes aren't included — tags are persisted separately by the
 * orchestrator (TermTagService writes), so the orchestrator tracks tag deltas
 * itself.
 *
 * @phpstan-immutable
 */
final class TermChange
{
    public function __construct(
        public readonly bool $translationChanged,
        public readonly bool $romanizationChanged,
        public readonly bool $notesChanged,
        public readonly bool $statusChangedToIgnored,
    ) {
    }

    public function anyFieldChanged(): bool
    {
        return $this->translationChanged
            || $this->romanizationChanged
            || $this->notesChanged
            || $this->statusChangedToIgnored;
    }
}
