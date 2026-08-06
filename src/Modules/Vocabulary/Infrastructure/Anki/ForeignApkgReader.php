<?php

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Infrastructure\Anki;

use PDO;
use RuntimeException;
use ZipArchive;

/**
 * Reads an .apkg that LWT did not produce.
 *
 * {@see ApkgReader} is the round-trip reader: it only understands LWT's own
 * "LWT Term" notetype and matches notes back to terms by `lwt-` guid. A deck
 * the user built in Anki has neither, so every note comes back empty and gets
 * skipped — which is the gap issue #228 actually describes.
 *
 * This reader makes no assumptions about field names. It reports the notetypes
 * present so the user can map them, and returns notes with their fields intact
 * plus the scheduling facts needed to infer how well a word is already known.
 */
final class ForeignApkgReader
{
    /**
     * Notetypes present in the file, most-used first.
     *
     * @return list<ForeignNotetype>
     */
    public function notetypes(string $apkgPath): array
    {
        return $this->withCollection($apkgPath, function (PDO $pdo): array {
            $models = $this->decodeModels($pdo);
            $counts = $this->noteCountsByModel($pdo);

            $out = [];
            foreach ($models as $mid => $model) {
                $out[] = new ForeignNotetype(
                    id: $mid,
                    name: $model['name'],
                    fields: $model['fields'],
                    noteCount: $counts[$mid] ?? 0,
                );
            }

            usort($out, static fn(ForeignNotetype $a, ForeignNotetype $b) => $b->noteCount <=> $a->noteCount);

            return $out;
        });
    }

    /**
     * Every note using one notetype, with fields unmapped.
     *
     * @return list<ForeignNote>
     */
    public function notes(string $apkgPath, int $notetypeId): array
    {
        return $this->withCollection($apkgPath, function (PDO $pdo) use ($notetypeId): array {
            $models = $this->decodeModels($pdo);
            if (!isset($models[$notetypeId])) {
                throw new RuntimeException("Notetype {$notetypeId} not found in this file");
            }

            $fieldNames = $models[$notetypeId]['fields'];
            $cardsByNote = $this->cardStateByNote($pdo);

            $stmt = $pdo->prepare('SELECT id, tags, flds FROM notes WHERE mid = ?');
            $stmt->execute([$notetypeId]);

            $out = [];
            foreach ($stmt as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $values = explode(AnkiSchema::FIELD_SEPARATOR, (string) ($row['flds'] ?? ''));

                $fields = [];
                foreach ($fieldNames as $ord => $name) {
                    $fields[$name] = $values[$ord] ?? '';
                }

                $noteId = (int) ($row['id'] ?? 0);
                $state = $cardsByNote[$noteId] ?? ['interval' => 0, 'suspended' => false, 'new' => true];

                $out[] = new ForeignNote(
                    fields: $fields,
                    tags: $this->decodeTags((string) ($row['tags'] ?? '')),
                    interval: $state['interval'],
                    suspended: $state['suspended'],
                    isNew: $state['new'],
                );
            }

            return $out;
        });
    }

    /**
     * Extract the collection to a temp file and hand a PDO handle to $work.
     *
     * @template T
     * @param callable(PDO): T $work
     * @return T
     */
    private function withCollection(string $apkgPath, callable $work): mixed
    {
        AnkiSchema::assertSqliteAvailable();

        if (!is_file($apkgPath)) {
            throw new RuntimeException("APKG file not found: {$apkgPath}");
        }

        $zip = new ZipArchive();
        if ($zip->open($apkgPath) !== true) {
            throw new RuntimeException("Could not open APKG: {$apkgPath}");
        }

        $collectionName = null;
        foreach (['collection.anki21', 'collection.anki2'] as $candidate) {
            if ($zip->locateName($candidate) !== false) {
                $collectionName = $candidate;
                break;
            }
        }
        if ($collectionName === null) {
            $zip->close();
            throw new RuntimeException(
                'This file contains no Anki collection. Newer Anki exports using the '
                . '.colpkg format or a compressed collection are not supported yet.'
            );
        }

        $contents = $zip->getFromName($collectionName);
        $zip->close();
        if ($contents === false) {
            throw new RuntimeException("Failed to extract {$collectionName} from APKG");
        }

        $tmpDb = tempnam(sys_get_temp_dir(), 'lwt_apkg_foreign_');
        if ($tmpDb === false) {
            throw new RuntimeException('Could not allocate temp file');
        }
        file_put_contents($tmpDb, $contents);

        try {
            $pdo = new PDO('sqlite:' . $tmpDb);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $work($pdo);
        } finally {
            unlink($tmpDb);
        }
    }

    /**
     * Notetype id => name + ordered field names.
     *
     * @return array<int, array{name: string, fields: list<string>}>
     */
    private function decodeModels(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT models FROM col LIMIT 1');
        $modelsJson = $stmt === false ? null : $stmt->fetchColumn();
        if (!is_string($modelsJson) || $modelsJson === '') {
            throw new RuntimeException('Could not read col.models');
        }

        /** @var mixed $decoded */
        $decoded = json_decode($modelsJson, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Could not decode col.models');
        }

        $out = [];
        /** @var mixed $model */
        foreach ($decoded as $mid => $model) {
            if (!is_array($model) || !isset($model['flds']) || !is_array($model['flds'])) {
                continue;
            }

            // Anki stores fields with an explicit ordinal; sort by it rather
            // than trusting array order, which shared decks do not guarantee.
            $byOrd = [];
            /** @var mixed $fld */
            foreach ($model['flds'] as $fld) {
                if (!is_array($fld) || !isset($fld['name'], $fld['ord'])) {
                    continue;
                }
                $byOrd[(int) $fld['ord']] = (string) $fld['name'];
            }
            ksort($byOrd);

            if ($byOrd === []) {
                continue;
            }

            $out[(int) $mid] = [
                'name' => isset($model['name']) ? (string) $model['name'] : 'Unnamed notetype',
                'fields' => array_values($byOrd),
            ];
        }

        return $out;
    }

    /**
     * @return array<int, int> notetype id => note count
     */
    private function noteCountsByModel(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT mid, COUNT(*) AS c FROM notes GROUP BY mid');
        if ($stmt === false) {
            return [];
        }

        $out = [];
        /** @var mixed $row */
        foreach ($stmt as $row) {
            if (is_array($row)) {
                $out[(int) ($row['mid'] ?? 0)] = (int) ($row['c'] ?? 0);
            }
        }

        return $out;
    }

    /**
     * Collapse each note's cards into the facts the status mapping needs.
     *
     * A note can have several cards (forward/reverse). The strongest card wins:
     * if the user knows the word in one direction it is worth importing as
     * known, and a note only counts as suspended when every card is.
     *
     * @return array<int, array{interval: int, suspended: bool, new: bool}>
     */
    private function cardStateByNote(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT nid, ivl, queue, type FROM cards');
        if ($stmt === false) {
            return [];
        }

        $out = [];
        foreach ($stmt as $row) {
            if (!is_array($row)) {
                continue;
            }

            $nid = (int) ($row['nid'] ?? 0);
            $ivl = (int) ($row['ivl'] ?? 0);
            $queue = (int) ($row['queue'] ?? 0);
            $type = (int) ($row['type'] ?? 0);

            // Anki stores sub-day learning intervals as negative seconds.
            $intervalDays = $ivl < 0 ? 0 : $ivl;

            if (!isset($out[$nid])) {
                $out[$nid] = ['interval' => 0, 'suspended' => true, 'new' => true];
            }

            $out[$nid]['interval'] = max($out[$nid]['interval'], $intervalDays);
            $out[$nid]['suspended'] = $out[$nid]['suspended'] && $queue === -1;
            $out[$nid]['new'] = $out[$nid]['new'] && $type === 0;
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private function decodeTags(string $tags): array
    {
        $parts = preg_split('/\s+/', trim($tags)) ?: [];

        return array_values(array_filter($parts, static fn(string $t): bool => $t !== ''));
    }
}
