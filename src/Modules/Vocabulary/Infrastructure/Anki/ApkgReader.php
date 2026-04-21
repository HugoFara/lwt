<?php

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Infrastructure\Anki;

use PDO;
use RuntimeException;
use ZipArchive;

/**
 * Reads an .apkg file and yields ApkgNote objects.
 *
 * Resolves field positions by name from the note type definition rather than
 * hard-coded indexes, so .apkg files modified in Anki (where the user might
 * reorder fields) still parse correctly. Unknown notes (no LWT id field, or a
 * guid not in our prefix) are returned without an `lwtTermId`-assertable id —
 * the caller decides what to do with them.
 */
final class ApkgReader
{
    /**
     * @return list<ApkgNote>
     */
    public function read(string $apkgPath): array
    {
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
            throw new RuntimeException('No collection.anki21 or collection.anki2 found in APKG');
        }

        $contents = $zip->getFromName($collectionName);
        $zip->close();
        if ($contents === false) {
            throw new RuntimeException("Failed to extract {$collectionName} from APKG");
        }

        $tmpDb = tempnam(sys_get_temp_dir(), 'lwt_apkg_read_');
        if ($tmpDb === false) {
            throw new RuntimeException('Could not allocate temp file');
        }
        file_put_contents($tmpDb, $contents);

        try {
            return $this->readCollection($tmpDb);
        } finally {
            unlink($tmpDb);
        }
    }

    /**
     * @return list<ApkgNote>
     */
    private function readCollection(string $dbPath): array
    {
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $modelsStmt = $pdo->query('SELECT models FROM col');
        if ($modelsStmt === false) {
            throw new RuntimeException('Could not read col.models');
        }
        $modelsJson = $modelsStmt->fetchColumn();
        if (!is_string($modelsJson)) {
            throw new RuntimeException('Missing col.models');
        }
        /** @var mixed $modelsDecoded */
        $modelsDecoded = json_decode($modelsJson, true);
        if (!is_array($modelsDecoded)) {
            throw new RuntimeException('Could not decode col.models');
        }

        $fieldOrdsByMid = $this->buildFieldOrdMap($modelsDecoded);
        $suspendedByNote = $this->loadSuspendedNotes($pdo);

        $noteStmt = $pdo->query('SELECT id, guid, mid, tags, flds FROM notes');
        if ($noteStmt === false) {
            throw new RuntimeException('Could not read notes');
        }

        /** @var list<ApkgNote> $out */
        $out = [];
        foreach ($noteStmt as $row) {
            if (!is_array($row)) {
                continue;
            }
            $mid = isset($row['mid']) ? (int) $row['mid'] : 0;
            $ords = $fieldOrdsByMid[$mid] ?? [];
            if ($ords === []) {
                continue;
            }
            $fields = explode(AnkiSchema::FIELD_SEPARATOR, isset($row['flds']) ? (string) $row['flds'] : '');

            $get = static function (string $name) use ($fields, $ords): string {
                $ord = $ords[$name] ?? null;
                return $ord !== null && isset($fields[$ord]) ? $fields[$ord] : '';
            };

            $guid = isset($row['guid']) ? (string) $row['guid'] : '';
            $lwtIdFromGuid = ApkgNote::lwtIdFromGuid($guid);
            $lwtIdField = $get(AnkiSchema::FIELD_LWT_ID);
            $lwtId = ctype_digit($lwtIdField) ? (int) $lwtIdField : ($lwtIdFromGuid ?? 0);

            $out[] = new ApkgNote(
                lwtTermId: $lwtId,
                term: $get(AnkiSchema::FIELD_TERM),
                translation: $get(AnkiSchema::FIELD_TRANSLATION),
                romanization: $get(AnkiSchema::FIELD_ROMANIZATION),
                notes: $get(AnkiSchema::FIELD_NOTES),
                tags: $this->decodeTags(isset($row['tags']) ? (string) $row['tags'] : ''),
                suspended: isset($suspendedByNote[isset($row['id']) ? (int) $row['id'] : 0]),
            );
        }
        return $out;
    }

    /**
     * @param array<array-key, mixed> $models
     * @return array<int, array<string, int>>
     */
    private function buildFieldOrdMap(array $models): array
    {
        $out = [];
        foreach ($models as $mid => $model) {
            if (!is_array($model) || !isset($model['flds']) || !is_array($model['flds'])) {
                continue;
            }
            $ords = [];
            /** @var mixed $fld */
            foreach ($model['flds'] as $fld) {
                if (!is_array($fld) || !isset($fld['name'], $fld['ord'])) {
                    continue;
                }
                $ords[(string) $fld['name']] = (int) $fld['ord'];
            }
            $out[(int) $mid] = $ords;
        }
        return $out;
    }

    /**
     * @return array<int, true>
     */
    private function loadSuspendedNotes(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT nid, queue FROM cards');
        if ($stmt === false) {
            throw new RuntimeException('Could not read cards');
        }
        $out = [];
        foreach ($stmt as $row) {
            if (!is_array($row)) {
                continue;
            }
            if (isset($row['queue']) && (int) $row['queue'] === -1) {
                $out[isset($row['nid']) ? (int) $row['nid'] : 0] = true;
            }
        }
        return $out;
    }

    /**
     * @return list<string>
     */
    private function decodeTags(string $raw): array
    {
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return [];
        }
        $parts = preg_split('/\s+/', $trimmed);
        return $parts === false ? [] : $parts;
    }
}
