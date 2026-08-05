<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Infrastructure\Anki;

use Lwt\Modules\Vocabulary\Infrastructure\Anki\AnkiSchema;
use PDO;
use ZipArchive;

/**
 * Builds .apkg files that look like decks made *in Anki*, for testing the
 * foreign-deck importer.
 *
 * Deliberately does not reuse ApkgWriter: that writes LWT's own notetype and
 * `lwt-` guids, which is precisely the case the importer must not depend on.
 * These files use arbitrary field names and Anki-style random guids.
 */
final class ForeignDeckBuilder
{
    /** @var list<array{fields: list<string>, tags: string, ivl: int, queue: int, type: int}> */
    private array $notes = [];

    /**
     * @param list<string> $fieldNames
     */
    public function __construct(
        private readonly string $notetypeName = 'Basic',
        private readonly array $fieldNames = ['Front', 'Back'],
        private readonly int $notetypeId = 1500000000000,
    ) {
    }

    /**
     * @param list<string> $fields Values in field order
     */
    public function addNote(
        array $fields,
        string $tags = '',
        int $interval = 0,
        int $queue = 0,
        int $type = 0
    ): self {
        $this->notes[] = [
            'fields' => $fields,
            'tags' => $tags,
            'ivl' => $interval,
            'queue' => $queue,
            'type' => $type,
        ];

        return $this;
    }

    /**
     * A note Anki considers mature (interval past the 21-day threshold).
     *
     * @param list<string> $fields
     */
    public function addMatureNote(array $fields, int $interval = 90, string $tags = ''): self
    {
        return $this->addNote($fields, $tags, $interval, 2, 2);
    }

    /**
     * A note in active learning.
     *
     * @param list<string> $fields
     */
    public function addYoungNote(array $fields, int $interval, string $tags = ''): self
    {
        return $this->addNote($fields, $tags, $interval, 2, 2);
    }

    /**
     * A note the user suspended in Anki.
     *
     * @param list<string> $fields
     */
    public function addSuspendedNote(array $fields, int $interval = 30): self
    {
        return $this->addNote($fields, '', $interval, -1, 2);
    }

    /**
     * Write the .apkg and return its path.
     */
    public function build(string $path): string
    {
        $dbPath = $path . '.collection';
        @unlink($dbPath);

        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        foreach (AnkiSchema::createStatements() as $stmt) {
            $pdo->exec($stmt);
        }

        $flds = [];
        foreach ($this->fieldNames as $ord => $name) {
            $flds[] = [
                'name' => $name, 'ord' => $ord, 'sticky' => false, 'rtl' => false,
                'font' => 'Arial', 'size' => 20, 'media' => [],
            ];
        }

        $models = [
            (string) $this->notetypeId => [
                'id' => $this->notetypeId,
                'name' => $this->notetypeName,
                'type' => 0, 'mod' => 1700000000, 'usn' => -1, 'sortf' => 0, 'did' => 1,
                'tmpls' => [[
                    'name' => 'Card 1', 'ord' => 0,
                    'qfmt' => '{{' . $this->fieldNames[0] . '}}',
                    'afmt' => '{{FrontSide}}', 'did' => null, 'bqfmt' => '', 'bafmt' => '',
                ]],
                'flds' => $flds,
                'css' => '', 'latexPre' => '', 'latexPost' => '', 'req' => [[0, 'any', [0]]],
            ],
        ];

        $pdo->exec(
            'INSERT INTO col (id, crt, mod, scm, ver, dty, usn, ls, conf, models, decks, dconf, tags) VALUES ('
            . '1, 1700000000, 1700000000000, 1700000000000, 11, 0, 0, 0, '
            . $pdo->quote(json_encode(AnkiSchema::defaultConf())) . ', '
            . $pdo->quote((string) json_encode($models)) . ', '
            . $pdo->quote((string) json_encode(['1' => AnkiSchema::defaultDeck()])) . ', '
            . $pdo->quote((string) json_encode(['1' => AnkiSchema::defaultDeckConfig()])) . ", '{}')"
        );

        $nextId = 1700000000000;
        foreach ($this->notes as $note) {
            $nid = $nextId++;
            $cid = $nextId++;
            // An Anki-style guid: random, and crucially without LWT's prefix.
            $guid = rtrim(strtr(base64_encode(pack('N', $nid % 4294967295)), '+/', '-_'), '=');
            $joined = implode(AnkiSchema::FIELD_SEPARATOR, $note['fields']);
            $sortField = $note['fields'][0] ?? '';

            $pdo->exec(
                'INSERT INTO notes (id, guid, mid, mod, usn, tags, flds, sfld, csum, flags, data) VALUES ('
                . $nid . ', ' . $pdo->quote($guid) . ', ' . $this->notetypeId . ', 1700000000, -1, '
                . $pdo->quote($note['tags']) . ', ' . $pdo->quote($joined) . ', '
                . $pdo->quote($sortField) . ', ' . AnkiSchema::fieldChecksum($sortField) . ", 0, '')"
            );
            $pdo->exec(
                'INSERT INTO cards (id, nid, did, ord, mod, usn, type, queue, due, ivl, factor, '
                . 'reps, lapses, left, odue, odid, flags, data) VALUES ('
                . $cid . ', ' . $nid . ', 1, 0, 1700000000, -1, ' . $note['type'] . ', '
                . $note['queue'] . ', 1, ' . $note['ivl'] . ", 2500, 3, 0, 0, 0, 0, 0, '')"
            );
        }
        $pdo = null;

        @unlink($path);
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFile($dbPath, 'collection.anki21');
        $zip->addFromString('media', '{}');
        $zip->close();

        // The zip holds its own copy; the loose collection file is scratch.
        // It is removed by the caller's tearDown along with $path.
        return $path;
    }

    /**
     * Remove the scratch collection file left beside an built .apkg.
     */
    public static function cleanup(string $path): void
    {
        @unlink($path);
        @unlink($path . '.collection');
    }
}
