<?php

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Infrastructure\Anki;

/**
 * One note from a foreign (non-LWT) Anki deck, with its fields left unmapped.
 *
 * Carries the scheduling facts LWT needs to infer how well the user already
 * knows the word, which is the whole point of importing a deck rather than
 * reclassifying thousands of terms by hand.
 */
final class ForeignNote
{
    /**
     * @param array<string, string> $fields    Field name => value, as stored in Anki
     * @param list<string>          $tags      Anki tags on the note
     * @param int                   $interval  Largest `cards.ivl` across the note's
     *                                         cards, in days. 0 for unseen cards.
     * @param bool                  $suspended Whether every card is suspended
     * @param bool                  $isNew     Whether the note has never been studied
     */
    public function __construct(
        public readonly array $fields,
        public readonly array $tags,
        public readonly int $interval,
        public readonly bool $suspended,
        public readonly bool $isNew,
    ) {
    }

    /**
     * Value of one field, or '' when the notetype has no such field.
     */
    public function field(string $name): string
    {
        return $this->fields[$name] ?? '';
    }
}
