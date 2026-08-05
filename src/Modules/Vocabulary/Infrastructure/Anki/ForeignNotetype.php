<?php

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Infrastructure\Anki;

/**
 * A notetype found in an .apkg that LWT did not create.
 *
 * Field names are arbitrary — "Front"/"Back" for Anki's stock Basic notetype,
 * but shared decks use anything ("Expression", "Meaning", "Word", "Reading").
 * LWT cannot guess which field holds the term, so the user maps them; this is
 * what the mapping UI is built from.
 */
final class ForeignNotetype
{
    /**
     * @param int          $id        Anki's model id (`notes.mid`)
     * @param string       $name      Human-readable notetype name
     * @param list<string> $fields    Field names, in Anki's ordinal order
     * @param int          $noteCount How many notes use this notetype
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly array $fields,
        public readonly int $noteCount,
    ) {
    }
}
