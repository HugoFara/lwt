<?php declare(strict_types=1);
/**
 * Local Dictionary Service - Business logic for local dictionary management
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Services;

use DateTimeImmutable;
use Lwt\Core\Entity\LocalDictionary;
use Lwt\Core\Globals;
use Lwt\Database\Connection;
use Lwt\Database\QueryBuilder;

/**
 * Service class for managing local dictionaries.
 *
 * Handles CRUD operations for local dictionaries and entries,
 * as well as term lookups.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class LocalDictionaryService
{
    /**
     * Batch size for bulk inserts.
     */
    private const BATCH_SIZE = 1000;

    /**
     * Create a new local dictionary.
     *
     * @param int         $languageId   Language ID
     * @param string      $name         Dictionary name
     * @param string      $sourceFormat Source format (csv, json, stardict)
     * @param string|null $description  Optional description
     *
     * @return int The new dictionary ID
     */
    public function create(
        int $languageId,
        string $name,
        string $sourceFormat = 'csv',
        ?string $description = null
    ): int {
        $userId = Globals::getCurrentUserId();

        $dictionary = LocalDictionary::create($languageId, $name, $sourceFormat, $userId);
        if ($description !== null) {
            $dictionary->setDescription($description);
        }

        $data = [
            'LdLgID' => $dictionary->languageId(),
            'LdName' => $dictionary->name(),
            'LdDescription' => $dictionary->description(),
            'LdSourceFormat' => $dictionary->sourceFormat(),
            'LdEntryCount' => 0,
            'LdPriority' => $dictionary->priority(),
            'LdEnabled' => $dictionary->isEnabled() ? 1 : 0,
            'LdUsID' => $dictionary->userId(),
        ];

        QueryBuilder::table('local_dictionaries')->insertPrepared($data);
        return (int) Connection::lastInsertId();
    }

    /**
     * Get a dictionary by ID.
     *
     * @param int $dictId Dictionary ID
     *
     * @return LocalDictionary|null
     */
    public function getById(int $dictId): ?LocalDictionary
    {
        $record = QueryBuilder::table('local_dictionaries')
            ->where('LdID', '=', $dictId)
            ->firstPrepared();

        if ($record === null) {
            return null;
        }

        return $this->hydrateFromRecord($record);
    }

    /**
     * Get all dictionaries for a language.
     *
     * @param int $languageId Language ID
     *
     * @return LocalDictionary[]
     */
    public function getForLanguage(int $languageId): array
    {
        $records = QueryBuilder::table('local_dictionaries')
            ->where('LdLgID', '=', $languageId)
            ->where('LdEnabled', '=', 1)
            ->orderBy('LdPriority', 'ASC')
            ->getPrepared();

        return array_map([$this, 'hydrateFromRecord'], $records);
    }

    /**
     * Get all dictionaries for a language (including disabled).
     *
     * @param int $languageId Language ID
     *
     * @return LocalDictionary[]
     */
    public function getAllForLanguage(int $languageId): array
    {
        $records = QueryBuilder::table('local_dictionaries')
            ->where('LdLgID', '=', $languageId)
            ->orderBy('LdPriority', 'ASC')
            ->getPrepared();

        return array_map([$this, 'hydrateFromRecord'], $records);
    }

    /**
     * Update a dictionary.
     *
     * @param LocalDictionary $dictionary Dictionary entity
     *
     * @return bool Success
     */
    public function update(LocalDictionary $dictionary): bool
    {
        if ($dictionary->isNew()) {
            return false;
        }

        $data = [
            'LdName' => $dictionary->name(),
            'LdDescription' => $dictionary->description(),
            'LdPriority' => $dictionary->priority(),
            'LdEnabled' => $dictionary->isEnabled() ? 1 : 0,
            'LdEntryCount' => $dictionary->entryCount(),
        ];

        return QueryBuilder::table('local_dictionaries')
            ->where('LdID', '=', $dictionary->id())
            ->updatePrepared($data) > 0;
    }

    /**
     * Delete a dictionary and all its entries.
     *
     * @param int $dictId Dictionary ID
     *
     * @return bool Success
     */
    public function delete(int $dictId): bool
    {
        // Entries are deleted via CASCADE
        return QueryBuilder::table('local_dictionaries')
            ->where('LdID', '=', $dictId)
            ->deletePrepared() > 0;
    }

    /**
     * Look up a term in local dictionaries for a language.
     *
     * @param int    $languageId Language ID
     * @param string $term       Term to look up
     *
     * @return array<array{term: string, definition: string, reading: ?string, pos: ?string, dictionary: string}>
     */
    public function lookup(int $languageId, string $term): array
    {
        $termLc = mb_strtolower(trim($term), 'UTF-8');

        $sql = "SELECT le.LeTerm, le.LeDefinition, le.LeReading, le.LePartOfSpeech, ld.LdName
                FROM " . Globals::table('local_dictionary_entries') . " le
                INNER JOIN " . Globals::table('local_dictionaries') . " ld ON le.LeLdID = ld.LdID
                WHERE ld.LdLgID = ? AND ld.LdEnabled = 1 AND le.LeTermLc = ?
                ORDER BY ld.LdPriority ASC";

        $records = Connection::preparedFetchAll($sql, [$languageId, $termLc]);

        return array_map(function ($row) {
            return [
                'term' => $row['LeTerm'],
                'definition' => $row['LeDefinition'],
                'reading' => $row['LeReading'],
                'pos' => $row['LePartOfSpeech'],
                'dictionary' => $row['LdName'],
            ];
        }, $records);
    }

    /**
     * Look up a term with prefix matching (for autocomplete).
     *
     * @param int    $languageId Language ID
     * @param string $prefix     Term prefix
     * @param int    $limit      Maximum results
     *
     * @return array<array{term: string, definition: string}>
     */
    public function lookupPrefix(int $languageId, string $prefix, int $limit = 10): array
    {
        $prefixLc = mb_strtolower(trim($prefix), 'UTF-8');

        $sql = "SELECT DISTINCT le.LeTerm, le.LeDefinition
                FROM " . Globals::table('local_dictionary_entries') . " le
                INNER JOIN " . Globals::table('local_dictionaries') . " ld ON le.LeLdID = ld.LdID
                WHERE ld.LdLgID = ? AND ld.LdEnabled = 1 AND le.LeTermLc LIKE ?
                ORDER BY le.LeTermLc ASC
                LIMIT ?";

        $records = Connection::preparedFetchAll($sql, [$languageId, $prefixLc . '%', $limit]);

        return array_map(function ($row) {
            return [
                'term' => $row['LeTerm'],
                'definition' => $row['LeDefinition'],
            ];
        }, $records);
    }

    /**
     * Add a single entry to a dictionary.
     *
     * @param int         $dictId     Dictionary ID
     * @param string      $term       Term/headword
     * @param string      $definition Definition
     * @param string|null $reading    Pronunciation/reading
     * @param string|null $pos        Part of speech
     *
     * @return int Entry ID
     */
    public function addEntry(
        int $dictId,
        string $term,
        string $definition,
        ?string $reading = null,
        ?string $pos = null
    ): int {
        $data = [
            'LeLdID' => $dictId,
            'LeTerm' => $term,
            'LeTermLc' => mb_strtolower($term, 'UTF-8'),
            'LeDefinition' => $definition,
            'LeReading' => $reading,
            'LePartOfSpeech' => $pos,
        ];

        QueryBuilder::table('local_dictionary_entries')->insertPrepared($data);
        return (int) Connection::lastInsertId();
    }

    /**
     * Add multiple entries to a dictionary in batches.
     *
     * @param int                                                           $dictId  Dictionary ID
     * @param iterable<array{term: string, definition: string, reading?: ?string, pos?: ?string}> $entries Entries to add
     *
     * @return int Number of entries added
     */
    public function addEntriesBatch(int $dictId, iterable $entries): int
    {
        $batch = [];
        $count = 0;

        foreach ($entries as $entry) {
            $batch[] = [
                'LeLdID' => $dictId,
                'LeTerm' => $entry['term'],
                'LeTermLc' => mb_strtolower($entry['term'], 'UTF-8'),
                'LeDefinition' => $entry['definition'],
                'LeReading' => $entry['reading'] ?? null,
                'LePartOfSpeech' => $entry['pos'] ?? null,
            ];

            if (count($batch) >= self::BATCH_SIZE) {
                $this->insertBatch($batch);
                $count += count($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            $this->insertBatch($batch);
            $count += count($batch);
        }

        // Update entry count
        $this->updateEntryCount($dictId);

        return $count;
    }

    /**
     * Delete all entries from a dictionary.
     *
     * @param int $dictId Dictionary ID
     *
     * @return int Number of entries deleted
     */
    public function clearEntries(int $dictId): int
    {
        $deleted = QueryBuilder::table('local_dictionary_entries')
            ->where('LeLdID', '=', $dictId)
            ->deletePrepared();

        $this->updateEntryCount($dictId);

        return $deleted;
    }

    /**
     * Get entry count for a dictionary.
     *
     * @param int $dictId Dictionary ID
     *
     * @return int
     */
    public function getEntryCount(int $dictId): int
    {
        $result = QueryBuilder::table('local_dictionary_entries')
            ->select(['COUNT(*) as cnt'])
            ->where('LeLdID', '=', $dictId)
            ->firstPrepared();

        return (int) ($result['cnt'] ?? 0);
    }

    /**
     * Get entries for a dictionary (paginated).
     *
     * @param int $dictId  Dictionary ID
     * @param int $page    Page number (1-based)
     * @param int $perPage Entries per page
     *
     * @return array{entries: array, total: int, page: int, perPage: int}
     */
    public function getEntries(int $dictId, int $page = 1, int $perPage = 50): array
    {
        $offset = max(0, ($page - 1) * $perPage);

        $total = $this->getEntryCount($dictId);

        $records = QueryBuilder::table('local_dictionary_entries')
            ->where('LeLdID', '=', $dictId)
            ->orderBy('LeTermLc', 'ASC')
            ->limit($perPage)
            ->offset($offset)
            ->getPrepared();

        $entries = array_map(function ($row) {
            return [
                'id' => (int) $row['LeID'],
                'term' => $row['LeTerm'],
                'definition' => $row['LeDefinition'],
                'reading' => $row['LeReading'],
                'pos' => $row['LePartOfSpeech'],
            ];
        }, $records);

        return [
            'entries' => $entries,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
        ];
    }

    /**
     * Update a single entry.
     *
     * @param int         $entryId    Entry ID
     * @param string      $term       Term
     * @param string      $definition Definition
     * @param string|null $reading    Reading
     * @param string|null $pos        Part of speech
     *
     * @return bool Success
     */
    public function updateEntry(
        int $entryId,
        string $term,
        string $definition,
        ?string $reading = null,
        ?string $pos = null
    ): bool {
        $data = [
            'LeTerm' => $term,
            'LeTermLc' => mb_strtolower($term, 'UTF-8'),
            'LeDefinition' => $definition,
            'LeReading' => $reading,
            'LePartOfSpeech' => $pos,
        ];

        return QueryBuilder::table('local_dictionary_entries')
            ->where('LeID', '=', $entryId)
            ->updatePrepared($data) > 0;
    }

    /**
     * Delete a single entry.
     *
     * @param int $entryId Entry ID
     *
     * @return bool Success
     */
    public function deleteEntry(int $entryId): bool
    {
        // Get dictionary ID first to update count
        $entry = QueryBuilder::table('local_dictionary_entries')
            ->select(['LeLdID'])
            ->where('LeID', '=', $entryId)
            ->firstPrepared();

        if ($entry === null) {
            return false;
        }

        $deleted = QueryBuilder::table('local_dictionary_entries')
            ->where('LeID', '=', $entryId)
            ->deletePrepared() > 0;

        if ($deleted) {
            $this->updateEntryCount((int) $entry['LeLdID']);
        }

        return $deleted;
    }

    /**
     * Check if a language has any local dictionaries.
     *
     * @param int $languageId Language ID
     *
     * @return bool
     */
    public function hasLocalDictionaries(int $languageId): bool
    {
        $result = QueryBuilder::table('local_dictionaries')
            ->select(['COUNT(*) as cnt'])
            ->where('LdLgID', '=', $languageId)
            ->where('LdEnabled', '=', 1)
            ->firstPrepared();

        return ((int) ($result['cnt'] ?? 0)) > 0;
    }

    /**
     * Get the local dictionary mode for a language.
     *
     * @param int $languageId Language ID
     *
     * @return int Mode (0=online only, 1=local first, 2=local only, 3=combined)
     */
    public function getLocalDictMode(int $languageId): int
    {
        $result = QueryBuilder::table('languages')
            ->select(['LgLocalDictMode'])
            ->where('LgID', '=', $languageId)
            ->firstPrepared();

        return (int) ($result['LgLocalDictMode'] ?? 0);
    }

    /**
     * Hydrate a LocalDictionary entity from a database record.
     *
     * @param array<string, mixed> $record Database record
     *
     * @return LocalDictionary
     */
    private function hydrateFromRecord(array $record): LocalDictionary
    {
        return LocalDictionary::reconstitute(
            (int) $record['LdID'],
            (int) $record['LdLgID'],
            (string) $record['LdName'],
            $record['LdDescription'] !== null ? (string) $record['LdDescription'] : null,
            (string) $record['LdSourceFormat'],
            (int) $record['LdEntryCount'],
            (int) $record['LdPriority'],
            (bool) $record['LdEnabled'],
            new DateTimeImmutable($record['LdCreated']),
            $record['LdUsID'] !== null ? (int) $record['LdUsID'] : null
        );
    }

    /**
     * Insert a batch of entries.
     *
     * @param array<array<string, mixed>> $batch Batch of entry data
     *
     * @return void
     */
    private function insertBatch(array $batch): void
    {
        if (empty($batch)) {
            return;
        }

        $columns = ['LeLdID', 'LeTerm', 'LeTermLc', 'LeDefinition', 'LeReading', 'LePartOfSpeech'];
        $placeholders = [];
        $values = [];

        foreach ($batch as $row) {
            $placeholders[] = '(?, ?, ?, ?, ?, ?)';
            $values[] = $row['LeLdID'];
            $values[] = $row['LeTerm'];
            $values[] = $row['LeTermLc'];
            $values[] = $row['LeDefinition'];
            $values[] = $row['LeReading'];
            $values[] = $row['LePartOfSpeech'];
        }

        $sql = "INSERT INTO " . Globals::table('local_dictionary_entries') .
               " (" . implode(', ', $columns) . ") VALUES " .
               implode(', ', $placeholders);

        Connection::preparedExecute($sql, $values);
    }

    /**
     * Update the entry count for a dictionary.
     *
     * @param int $dictId Dictionary ID
     *
     * @return void
     */
    private function updateEntryCount(int $dictId): void
    {
        $count = $this->getEntryCount($dictId);

        QueryBuilder::table('local_dictionaries')
            ->where('LdID', '=', $dictId)
            ->updatePrepared(['LdEntryCount' => $count]);
    }
}
