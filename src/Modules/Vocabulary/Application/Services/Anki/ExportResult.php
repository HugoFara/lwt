<?php

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Application\Services\Anki;

/**
 * Outcome of an .apkg export. Surfaced to controllers/CLI for user-facing
 * confirmation messages.
 *
 * @phpstan-immutable
 */
final class ExportResult
{
    public function __construct(
        public readonly string $outputPath,
        public readonly int $languageId,
        public readonly string $languageName,
        public readonly string $deckName,
        public readonly int $noteCount,
        public readonly int $suspendedCount,
    ) {
    }
}
