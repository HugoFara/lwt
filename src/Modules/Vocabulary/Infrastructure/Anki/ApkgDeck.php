<?php

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Infrastructure\Anki;

/**
 * Identifies the Anki deck a batch of LWT notes will land in.
 *
 * Convention: one deck per LWT language, named "LWT::{LanguageName}".
 * The double-colon makes it appear as a child deck under "LWT" in Anki's tree.
 *
 * @phpstan-immutable
 */
final class ApkgDeck
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {
    }

    public static function forLanguage(int $languageId, string $languageName): self
    {
        // Stable id derived from language id so subsequent exports land in the
        // same deck (keeps Anki from creating duplicates).
        $id = 1700000000000 + $languageId;
        return new self($id, "LWT::{$languageName}");
    }
}
