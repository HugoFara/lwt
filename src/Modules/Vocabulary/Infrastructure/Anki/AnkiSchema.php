<?php

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Infrastructure\Anki;

/**
 * Anki collection.anki21 schema (legacy schema 11).
 *
 * Anki downgrades to schema 11 for export so every client can read the
 * resulting .apkg. Tables, columns, indexes, and the JSON shapes for the
 * `col` row come from the upstream Rust source (rslib/src/storage/schema11.sql,
 * rslib/src/notetype/schema11.rs) cross-checked against a real .apkg dump.
 */
final class AnkiSchema
{
    public const SCHEMA_VERSION = 11;
    public const FIELD_SEPARATOR = "\x1f";
    public const NOTETYPE_ID = 1607392319000;
    public const NOTETYPE_NAME = 'LWT Term';
    public const TEMPLATE_NAME = 'Term -> Translation';
    public const FIELD_TERM = 'Term';
    public const FIELD_TRANSLATION = 'Translation';
    public const FIELD_ROMANIZATION = 'Romanization';
    public const FIELD_NOTES = 'Notes';
    public const FIELD_LWT_ID = 'LwtId';

    /**
     * Sequence of CREATE statements to build a fresh collection.anki21.
     *
     * @return list<string>
     */
    public static function createStatements(): array
    {
        return [
            'CREATE TABLE col ('
                . 'id integer PRIMARY KEY, crt integer NOT NULL, mod integer NOT NULL, '
                . 'scm integer NOT NULL, ver integer NOT NULL, dty integer NOT NULL, '
                . 'usn integer NOT NULL, ls integer NOT NULL, conf text NOT NULL, '
                . 'models text NOT NULL, decks text NOT NULL, dconf text NOT NULL, '
                . 'tags text NOT NULL)',
            'CREATE TABLE notes ('
                . 'id integer PRIMARY KEY, guid text NOT NULL, mid integer NOT NULL, '
                . 'mod integer NOT NULL, usn integer NOT NULL, tags text NOT NULL, '
                . 'flds text NOT NULL, sfld integer NOT NULL, csum integer NOT NULL, '
                . 'flags integer NOT NULL, data text NOT NULL)',
            'CREATE TABLE cards ('
                . 'id integer PRIMARY KEY, nid integer NOT NULL, did integer NOT NULL, '
                . 'ord integer NOT NULL, mod integer NOT NULL, usn integer NOT NULL, '
                . 'type integer NOT NULL, queue integer NOT NULL, due integer NOT NULL, '
                . 'ivl integer NOT NULL, factor integer NOT NULL, reps integer NOT NULL, '
                . 'lapses integer NOT NULL, left integer NOT NULL, odue integer NOT NULL, '
                . 'odid integer NOT NULL, flags integer NOT NULL, data text NOT NULL)',
            'CREATE TABLE revlog ('
                . 'id integer PRIMARY KEY, cid integer NOT NULL, usn integer NOT NULL, '
                . 'ease integer NOT NULL, ivl integer NOT NULL, lastIvl integer NOT NULL, '
                . 'factor integer NOT NULL, time integer NOT NULL, type integer NOT NULL)',
            'CREATE TABLE graves ('
                . 'usn integer NOT NULL, oid integer NOT NULL, type integer NOT NULL)',
            'CREATE INDEX ix_notes_usn ON notes (usn)',
            'CREATE INDEX ix_cards_usn ON cards (usn)',
            'CREATE INDEX ix_revlog_usn ON revlog (usn)',
            'CREATE INDEX ix_cards_nid ON cards (nid)',
            'CREATE INDEX ix_cards_sched ON cards (did, queue, due)',
            'CREATE INDEX ix_revlog_cid ON revlog (cid)',
            'CREATE INDEX ix_notes_csum ON notes (csum)',
        ];
    }

    /**
     * Default `col.conf` JSON used by every fresh collection.
     *
     * @return array<string, mixed>
     */
    public static function defaultConf(): array
    {
        return [
            'activeDecks' => [1],
            'addToCur' => true,
            'collapseTime' => 1200,
            'curDeck' => 1,
            'curModel' => (string) self::NOTETYPE_ID,
            'dueCounts' => true,
            'estTimes' => true,
            'newBury' => true,
            'newSpread' => 0,
            'nextPos' => 1,
            'sortBackwards' => false,
            'sortType' => 'noteFld',
            'timeLim' => 0,
        ];
    }

    /**
     * Default deck configuration (`col.dconf`) — id 1, the deck preset every
     * other deck inherits from.
     *
     * @return array<array-key, mixed>
     */
    public static function defaultDeckConfig(): array
    {
        return [
            '1' => [
                'autoplay' => true,
                'id' => 1,
                'lapse' => [
                    'delays' => [10],
                    'leechAction' => 0,
                    'leechFails' => 8,
                    'minInt' => 1,
                    'mult' => 0,
                ],
                'maxTaken' => 60,
                'mod' => 0,
                'name' => 'Default',
                'new' => [
                    'bury' => true,
                    'delays' => [1, 10],
                    'initialFactor' => 2500,
                    'ints' => [1, 4, 7],
                    'order' => 1,
                    'perDay' => 20,
                    'separate' => true,
                ],
                'replayq' => true,
                'rev' => [
                    'bury' => true,
                    'ease4' => 1.3,
                    'fuzz' => 0.05,
                    'ivlFct' => 1,
                    'maxIvl' => 36500,
                    'minSpace' => 1,
                    'perDay' => 100,
                ],
                'timer' => 0,
                'usn' => 0,
            ],
        ];
    }

    /**
     * Default deck row (`col.decks` includes id 1 plus any LWT deck added).
     *
     * @return array<string, mixed>
     */
    public static function defaultDeck(): array
    {
        return [
            'collapsed' => false,
            'conf' => 1,
            'desc' => '',
            'dyn' => 0,
            'extendNew' => 10,
            'extendRev' => 50,
            'id' => 1,
            'lrnToday' => [0, 0],
            'mod' => 0,
            'name' => 'Default',
            'newToday' => [0, 0],
            'revToday' => [0, 0],
            'timeToday' => [0, 0],
            'usn' => 0,
        ];
    }

    /**
     * Build an LWT deck row keyed for `col.decks`.
     *
     * @return array<string, mixed>
     */
    public static function buildDeck(int $deckId, string $name): array
    {
        return [
            'collapsed' => false,
            'conf' => 1,
            'desc' => '',
            'dyn' => 0,
            'extendNew' => 0,
            'extendRev' => 50,
            'id' => $deckId,
            'lrnToday' => [0, 0],
            'mod' => 0,
            'name' => $name,
            'newToday' => [0, 0],
            'revToday' => [0, 0],
            'timeToday' => [0, 0],
            'usn' => -1,
        ];
    }

    /**
     * The single LWT note type. Five fields; one card template.
     *
     * @return array<string, mixed>
     */
    public static function buildNotetype(int $defaultDeckId, int $modSeconds): array
    {
        $field = static fn(string $name, int $ord): array => [
            'font' => 'Arial',
            'media' => [],
            'name' => $name,
            'ord' => $ord,
            'rtl' => false,
            'size' => 20,
            'sticky' => false,
        ];

        $qfmt = '<div class="term">{{' . self::FIELD_TERM . '}}</div>'
            . '{{#' . self::FIELD_ROMANIZATION . '}}'
            . '<div class="romanization">{{' . self::FIELD_ROMANIZATION . '}}</div>'
            . '{{/' . self::FIELD_ROMANIZATION . '}}';
        $afmt = '{{FrontSide}}<hr id="answer"/>'
            . '<div class="translation">{{' . self::FIELD_TRANSLATION . '}}</div>'
            . '{{#' . self::FIELD_NOTES . '}}'
            . '<div class="notes">{{' . self::FIELD_NOTES . '}}</div>'
            . '{{/' . self::FIELD_NOTES . '}}';

        $css = '.card{font-family:sans-serif;font-size:24px;text-align:center}'
            . '.term{font-size:32px;font-weight:bold}'
            . '.romanization{color:#888;font-style:italic}'
            . '.translation{margin-top:8px}'
            . '.notes{font-size:16px;color:#555;margin-top:8px}';

        return [
            'css' => $css,
            'did' => $defaultDeckId,
            'flds' => [
                $field(self::FIELD_TERM, 0),
                $field(self::FIELD_TRANSLATION, 1),
                $field(self::FIELD_ROMANIZATION, 2),
                $field(self::FIELD_NOTES, 3),
                $field(self::FIELD_LWT_ID, 4),
            ],
            'id' => (string) self::NOTETYPE_ID,
            'latexPost' => '\\end{document}',
            'latexPre' => "\\documentclass[12pt]{article}\n"
                . "\\special{papersize=3in,5in}\n"
                . "\\usepackage[utf8]{inputenc}\n"
                . "\\usepackage{amssymb,amsmath}\n"
                . "\\pagestyle{empty}\n"
                . "\\setlength{\\parindent}{0in}\n"
                . "\\begin{document}\n",
            'latexsvg' => false,
            'mod' => $modSeconds,
            'name' => self::NOTETYPE_NAME,
            'req' => [[0, 'any', [0]]],
            'sortf' => 0,
            'tags' => [],
            'tmpls' => [
                [
                    'afmt' => $afmt,
                    'bafmt' => '',
                    'bfont' => '',
                    'bqfmt' => '',
                    'bsize' => 0,
                    'did' => null,
                    'name' => self::TEMPLATE_NAME,
                    'ord' => 0,
                    'qfmt' => $qfmt,
                ],
            ],
            'type' => 0,
            'usn' => -1,
            'vers' => [],
        ];
    }

    /**
     * Anki's field checksum: integer value of the first 8 hex chars of the
     * SHA-1 of the stripped first field. Used for duplicate detection.
     */
    public static function fieldChecksum(string $sortField): int
    {
        $stripped = strip_tags($sortField);
        $stripped = html_entity_decode($stripped, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $sha1 = sha1($stripped);
        return (int) hexdec(substr($sha1, 0, 8));
    }
}
