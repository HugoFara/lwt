<?php

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Infrastructure\Anki;

use PDO;
use RuntimeException;
use ZipArchive;

/**
 * Builds a .apkg file (zip containing a schema-11 SQLite collection) from
 * LWT term data.
 *
 * Status mapping for `cards.queue`:
 *   - LWT 1..5:  queue=0 (new), due=position
 *   - LWT 98:    queue=-1 (suspended)  — "ignored"
 *   - LWT 99:    queue=-1 (suspended)  — "well-known"
 *
 * SRS scheduling state (interval, ease, due-date for review cards) is
 * intentionally not exported in this version; see Lemmatization-style follow-up.
 */
final class ApkgWriter
{
    /** Anki's special "ignored" deck id; we use one deck per language instead. */
    private const COLLECTION_CREATION_TIMESTAMP = 1577836800; // 2020-01-01 UTC

    /**
     * @param non-empty-list<ApkgNote> $notes
     */
    public function write(string $outputPath, ApkgDeck $deck, array $notes): void
    {
        $colDb = tempnam(sys_get_temp_dir(), 'lwt_apkg_');
        if ($colDb === false) {
            throw new RuntimeException('Could not allocate temp file for collection');
        }
        // PDO needs to create a fresh sqlite, so unlink the empty file first.
        unlink($colDb);

        try {
            $this->buildCollection($colDb, $deck, $notes);
            $this->packageZip($outputPath, $colDb);
        } finally {
            if (is_file($colDb)) {
                unlink($colDb);
            }
        }
    }

    /**
     * @param non-empty-list<ApkgNote> $notes
     */
    private function buildCollection(string $dbPath, ApkgDeck $deck, array $notes): void
    {
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA journal_mode=DELETE');
        $pdo->beginTransaction();

        foreach (AnkiSchema::createStatements() as $stmt) {
            $pdo->exec($stmt);
        }

        $nowSeconds = time();
        $nowMillis = (int) (microtime(true) * 1000);

        $decks = [
            '1' => AnkiSchema::defaultDeck(),
            (string) $deck->id => AnkiSchema::buildDeck($deck->id, $deck->name),
        ];
        $models = [
            (string) AnkiSchema::NOTETYPE_ID => AnkiSchema::buildNotetype($deck->id, $nowSeconds),
        ];

        $pdo->exec('DELETE FROM col');
        $insertCol = $pdo->prepare(
            'INSERT INTO col (id, crt, mod, scm, ver, dty, usn, ls, conf, models, decks, dconf, tags) '
            . 'VALUES (1, :crt, :mod, :scm, :ver, 0, 0, 0, :conf, :models, :decks, :dconf, :tags)'
        );
        $insertCol->execute([
            ':crt' => self::COLLECTION_CREATION_TIMESTAMP,
            ':mod' => $nowMillis,
            ':scm' => $nowMillis,
            ':ver' => AnkiSchema::SCHEMA_VERSION,
            ':conf' => $this->jsonEncode(AnkiSchema::defaultConf()),
            ':models' => $this->jsonEncode($models),
            ':decks' => $this->jsonEncode($decks),
            ':dconf' => $this->jsonEncode(AnkiSchema::defaultDeckConfig()),
            ':tags' => $this->jsonEncode((object) []),
        ]);

        $insertNote = $pdo->prepare(
            'INSERT INTO notes (id, guid, mid, mod, usn, tags, flds, sfld, csum, flags, data) '
            . 'VALUES (:id, :guid, :mid, :mod, -1, :tags, :flds, :sfld, :csum, 0, \'\')'
        );
        $insertCard = $pdo->prepare(
            'INSERT INTO cards (id, nid, did, ord, mod, usn, type, queue, due, ivl, factor, '
            . 'reps, lapses, left, odue, odid, flags, data) '
            . 'VALUES (:id, :nid, :did, 0, :mod, -1, 0, :queue, :due, 0, 0, 0, 0, 0, 0, 0, 0, \'\')'
        );

        $position = 1;
        $nextId = $nowMillis;
        foreach ($notes as $note) {
            $noteId = $nextId++;
            $cardId = $nextId++;

            $fields = [
                $note->term,
                $note->translation,
                $note->romanization,
                $note->notes,
                $note->lwtIdField(),
            ];
            $flds = implode(AnkiSchema::FIELD_SEPARATOR, $fields);

            $insertNote->execute([
                ':id' => $noteId,
                ':guid' => $note->guid(),
                ':mid' => AnkiSchema::NOTETYPE_ID,
                ':mod' => $nowSeconds,
                ':tags' => $this->encodeTags($note->tags),
                ':flds' => $flds,
                ':sfld' => $note->term,
                ':csum' => AnkiSchema::fieldChecksum($note->term),
            ]);

            $insertCard->execute([
                ':id' => $cardId,
                ':nid' => $noteId,
                ':did' => $deck->id,
                ':mod' => $nowSeconds,
                ':queue' => $note->suspended ? -1 : 0,
                ':due' => $position++,
            ]);
        }

        $pdo->commit();
        unset($pdo);
    }

    private function packageZip(string $outputPath, string $collectionDbPath): void
    {
        if (file_exists($outputPath)) {
            unlink($outputPath);
        }
        $zip = new ZipArchive();
        if ($zip->open($outputPath, ZipArchive::CREATE) !== true) {
            throw new RuntimeException("Could not create zip at {$outputPath}");
        }
        // Modern clients read collection.anki21; older ones see the same file
        // under the legacy name. Writing both keeps us compatible with both.
        $contents = file_get_contents($collectionDbPath);
        if ($contents === false) {
            throw new RuntimeException('Failed to read intermediate collection');
        }
        $zip->addFromString('collection.anki21', $contents);
        $zip->addFromString('collection.anki2', $contents);
        $zip->addFromString('media', '{}');
        $zip->close();
    }

    /**
     * Anki tag format: " tag1 tag2 " (leading + trailing space, single-space
     * separated, empty string when no tags).
     *
     * @param list<string> $tags
     */
    private function encodeTags(array $tags): string
    {
        $cleaned = [];
        foreach ($tags as $tag) {
            $tag = trim(str_replace([' ', "\t", "\n"], '_', $tag));
            if ($tag !== '') {
                $cleaned[] = $tag;
            }
        }
        if ($cleaned === []) {
            return '';
        }
        return ' ' . implode(' ', $cleaned) . ' ';
    }

    /**
     * @param array<array-key, mixed>|object $data
     */
    private function jsonEncode($data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('Failed to encode JSON for Anki collection');
        }
        return $json;
    }
}
