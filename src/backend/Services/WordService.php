<?php declare(strict_types=1);
/**
 * Word Service - Business logic for word/term operations
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

require_once __DIR__ . '/SentenceService.php';
require_once __DIR__ . '/ExportService.php';
require_once __DIR__ . '/WordStatusService.php';
require_once __DIR__ . '/ExpressionService.php';

use Lwt\Core\Globals;
use Lwt\Core\StringUtils;
use Lwt\Core\Utils\ErrorHandler;
use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Database\QueryBuilder;
use Lwt\Database\Settings;

/**
 * Service class for managing words/terms.
 *
 * Handles CRUD operations for vocabulary items.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class WordService
{
    private string $tbpref;

    public function __construct()
    {
        $this->tbpref = Globals::getTablePrefix();
    }

    /**
     * Create a new word/term.
     *
     * @param array $data Word data with keys:
     *                    - WoLgID: Language ID
     *                    - WoText: Term text
     *                    - WoStatus: Learning status (1-5, 98, 99)
     *                    - WoTranslation: Translation text
     *                    - WoSentence: Example sentence
     *                    - WoRomanization: Romanization/phonetic
     *
     * @return array{id: int, message: string, success: bool, textlc: string, text: string}
     */
    public function create(array $data): array
    {
        $text = trim(Escaping::prepareTextdata($data['WoText']));
        $textlc = mb_strtolower($text, 'UTF-8');
        $translation = $this->normalizeTranslation($data['WoTranslation'] ?? '');

        try {
            $scoreColumns = WordStatusService::makeScoreRandomInsertUpdate('iv');
            $scoreValues = WordStatusService::makeScoreRandomInsertUpdate('id');

            $sql = "INSERT INTO {$this->tbpref}words (
                    WoLgID, WoTextLC, WoText, WoStatus, WoTranslation,
                    WoSentence, WoRomanization, WoStatusChanged, {$scoreColumns}
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), {$scoreValues})";

            $stmt = Connection::prepare($sql);
            $stmt->bind(
                'ississs',
                $data['WoLgID'],
                $textlc,
                $text,
                $data['WoStatus'],
                $translation,
                ExportService::replaceTabNewline($data['WoSentence'] ?? ''),
                $data['WoRomanization'] ?? ''
            );
            $stmt->execute();
            $wid = (int) $stmt->insertId();

            return [
                'id' => $wid,
                'message' => 'Term saved',
                'success' => true,
                'textlc' => $textlc,
                'text' => $text
            ];
        } catch (\RuntimeException $e) {
            $errorMsg = $e->getMessage();
            if (strpos($errorMsg, 'Duplicate entry') !== false) {
                $message = 'Error: Duplicate entry for "' . $textlc . '"';
            } else {
                $message = 'Error: ' . $errorMsg;
            }

            return [
                'id' => 0,
                'message' => $message,
                'success' => false,
                'textlc' => $textlc,
                'text' => $text
            ];
        }
    }

    /**
     * Update an existing word/term.
     *
     * @param int   $wordId Word ID
     * @param array $data   Word data (same keys as create())
     *
     * @return array{id: int, message: string, success: bool, textlc: string, text: string}
     */
    public function update(int $wordId, array $data): array
    {
        $text = trim(Escaping::prepareTextdata($data['WoText']));
        $textlc = mb_strtolower($text, 'UTF-8');
        $translation = $this->normalizeTranslation($data['WoTranslation'] ?? '');
        $sentence = ExportService::replaceTabNewline($data['WoSentence'] ?? '');
        $roman = $data['WoRomanization'] ?? '';

        $scoreUpdate = WordStatusService::makeScoreRandomInsertUpdate('u');

        if (isset($data['WoOldStatus']) && $data['WoOldStatus'] != $data['WoStatus']) {
            // Status changed - update status and timestamp
            $sql = "UPDATE {$this->tbpref}words SET
                WoText = ?, WoTranslation = ?, WoSentence = ?, WoRomanization = ?,
                WoStatus = ?, WoStatusChanged = NOW(), {$scoreUpdate}
                WHERE WoID = ?";
            Connection::preparedExecute($sql, [$text, $translation, $sentence, $roman, $data['WoStatus'], $wordId]);
        } else {
            // Status unchanged
            $sql = "UPDATE {$this->tbpref}words SET
                WoText = ?, WoTranslation = ?, WoSentence = ?, WoRomanization = ?, {$scoreUpdate}
                WHERE WoID = ?";
            Connection::preparedExecute($sql, [$text, $translation, $sentence, $roman, $wordId]);
        }

        return [
            'id' => $wordId,
            'message' => 'Updated',
            'success' => true,
            'textlc' => $textlc,
            'text' => $text
        ];
    }

    /**
     * Find a word by ID.
     *
     * @param int $wordId Word ID
     *
     * @return array|null Word data or null if not found
     */
    public function findById(int $wordId): ?array
    {
        return Connection::preparedFetchOne(
            "SELECT * FROM {$this->tbpref}words WHERE WoID = ?",
            [$wordId]
        );
    }

    /**
     * Find a word by text and language.
     *
     * @param string $textlc Lowercase text
     * @param int    $langId Language ID
     *
     * @return int|null Word ID or null if not found
     */
    public function findByText(string $textlc, int $langId): ?int
    {
        $id = Connection::preparedFetchValue(
            "SELECT WoID AS value FROM {$this->tbpref}words WHERE WoLgID = ? AND WoTextLC = ?",
            [$langId, $textlc]
        );
        return $id !== null ? (int)$id : null;
    }

    /**
     * Get term data from text items (for reading screen).
     *
     * @param int $textId Text ID
     * @param int $ord    Word order position
     *
     * @return array|null Term data with Ti2Text and Ti2LgID
     */
    public function getTermFromTextItem(int $textId, int $ord): ?array
    {
        return Connection::preparedFetchOne(
            "SELECT Ti2Text, Ti2LgID FROM {$this->tbpref}textitems2
             WHERE Ti2TxID = ? AND Ti2WordCount = 1 AND Ti2Order = ?",
            [$textId, $ord]
        );
    }

    /**
     * Link word to text items after creation.
     *
     * @param int    $wordId Word ID
     * @param int    $langId Language ID
     * @param string $textlc Lowercase text
     *
     * @return void
     */
    public function linkToTextItems(int $wordId, int $langId, string $textlc): void
    {
        Connection::preparedExecute(
            "UPDATE {$this->tbpref}textitems2 SET Ti2WoID = ?
             WHERE Ti2LgID = ? AND LOWER(Ti2Text) = ?",
            [$wordId, $langId, $textlc]
        );
    }

    /**
     * Get language data for a word form.
     *
     * @param int $langId Language ID
     *
     * @return array Language data
     */
    public function getLanguageData(int $langId): array
    {
        // Single query instead of three
        $row = Connection::preparedFetchOne(
            "SELECT LgShowRomanization, LgGoogleTranslateURI, LgName
             FROM {$this->tbpref}languages WHERE LgID = ?",
            [$langId]
        );

        return [
            'showRoman' => (bool) ($row['LgShowRomanization'] ?? false),
            'translateUri' => (string) ($row['LgGoogleTranslateURI'] ?? ''),
            'name' => (string) ($row['LgName'] ?? '')
        ];
    }

    /**
     * Get sentence for a term.
     *
     * @param int    $textId Text ID
     * @param int    $ord    Word order
     * @param string $termlc Lowercase term
     *
     * @return string Sentence with term marked
     */
    public function getSentenceForTerm(int $textId, int $ord, string $termlc): string
    {
        $seid = Connection::preparedFetchValue(
            "SELECT Ti2SeID AS value FROM {$this->tbpref}textitems2
             WHERE Ti2TxID = ? AND Ti2WordCount = 1 AND Ti2Order = ?",
            [$textId, $ord]
        );

        if ($seid === null) {
            return '';
        }

        $sent = \getSentence(
            (int) $seid,
            $termlc,
            (int) Settings::getWithDefault('set-term-sentence-count')
        );

        return ExportService::replaceTabNewline($sent[1] ?? '');
    }

    /**
     * Normalize translation text.
     *
     * @param string $translation Raw translation
     *
     * @return string Normalized translation (empty becomes '*')
     */
    private function normalizeTranslation(string $translation): string
    {
        $translation = trim(ExportService::replaceTabNewline($translation));
        return $translation === '' ? '*' : $translation;
    }

    /**
     * Get word count for a term.
     *
     * @param int $wordId Word ID
     *
     * @return int Word count
     */
    public function getWordCount(int $wordId): int
    {
        return (int) Connection::preparedFetchValue(
            "SELECT WoWordCount AS value FROM {$this->tbpref}words WHERE WoID = ?",
            [$wordId]
        );
    }

    /**
     * Convert text to hex class name for CSS.
     *
     * @param string $text Text to convert
     *
     * @return string Hex class name
     */
    public function textToClassName(string $text): string
    {
        return StringUtils::toClassName(Escaping::prepareTextdata($text));
    }

    /**
     * Delete a word by ID.
     *
     * @param int $wordId Word ID to delete
     *
     * @return string Result message
     */
    public function delete(int $wordId): string
    {
        QueryBuilder::table('words')
            ->where('WoID', '=', $wordId)
            ->deletePrepared();

        // Update text items to unlink the word
        Connection::preparedExecute(
            "UPDATE {$this->tbpref}textitems2 SET Ti2WoID = 0
             WHERE Ti2WordCount = 1 AND Ti2WoID = ?",
            [$wordId]
        );

        // Delete multi-word text items
        QueryBuilder::table('textitems2')
            ->where('Ti2WoID', '=', $wordId)
            ->deletePrepared();

        // Clean up orphaned word tags (complex DELETE with JOIN - keep as-is)
        Connection::execute(
            'DELETE ' . $this->tbpref . 'wordtags FROM (' .
            $this->tbpref . 'wordtags LEFT JOIN ' . $this->tbpref . 'words ON WtWoID = WoID) ' .
            'WHERE WoID IS NULL'
        );

        return 'Deleted';
    }

    /**
     * Delete multiple words by IDs.
     *
     * @param int[] $wordIds Array of word IDs to delete
     *
     * @return int Number of deleted words
     */
    public function deleteMultiple(array $wordIds): int
    {
        if (empty($wordIds)) {
            return 0;
        }

        $ids = array_map('intval', $wordIds);

        $count = QueryBuilder::table('words')
            ->whereIn('WoID', $ids)
            ->deletePrepared();

        // Update text items
        QueryBuilder::table('textitems2')
            ->where('Ti2WordCount', '=', 1)
            ->whereIn('Ti2WoID', $ids)
            ->updatePrepared(['Ti2WoID' => 0]);

        QueryBuilder::table('textitems2')
            ->whereIn('Ti2WoID', $ids)
            ->deletePrepared();

        // Clean up orphaned word tags (complex DELETE with JOIN - keep as-is)
        Connection::execute(
            'DELETE ' . $this->tbpref . 'wordtags FROM (' .
            $this->tbpref . 'wordtags LEFT JOIN ' . $this->tbpref . 'words ON WtWoID = WoID) ' .
            'WHERE WoID IS NULL'
        );

        return $count;
    }

    /**
     * Update status for multiple words.
     *
     * @param int[] $wordIds  Array of word IDs
     * @param int   $status   New status value (1-5, 98, 99)
     * @param bool  $relative If true, change status by +1 or -1
     *
     * @return int Number of updated words
     */
    public function updateStatusMultiple(array $wordIds, int $status, bool $relative = false): int
    {
        if (empty($wordIds)) {
            return 0;
        }

        $ids = array_map('intval', $wordIds);
        $scoreUpdate = WordStatusService::makeScoreRandomInsertUpdate('u');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        if ($relative) {
            if ($status > 0) {
                // Increment status
                $sql = "UPDATE {$this->tbpref}words
                        SET WoStatus = WoStatus + 1, WoStatusChanged = NOW(), {$scoreUpdate}
                        WHERE WoStatus IN (1,2,3,4) AND WoID IN ({$placeholders})";
                return Connection::preparedExecute($sql, $ids);
            } else {
                // Decrement status
                $sql = "UPDATE {$this->tbpref}words
                        SET WoStatus = WoStatus - 1, WoStatusChanged = NOW(), {$scoreUpdate}
                        WHERE WoStatus IN (2,3,4,5) AND WoID IN ({$placeholders})";
                return Connection::preparedExecute($sql, $ids);
            }
        }

        // Absolute status
        $sql = "UPDATE {$this->tbpref}words
                SET WoStatus = ?, WoStatusChanged = NOW(), {$scoreUpdate}
                WHERE WoID IN ({$placeholders})";

        return Connection::preparedExecute($sql, array_merge([$status], $ids));
    }

    /**
     * Update status changed date for multiple words.
     *
     * @param int[] $wordIds Array of word IDs
     *
     * @return int Number of updated words
     */
    public function updateStatusDateMultiple(array $wordIds): int
    {
        if (empty($wordIds)) {
            return 0;
        }

        $ids = array_map('intval', $wordIds);
        $scoreUpdate = WordStatusService::makeScoreRandomInsertUpdate('u');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "UPDATE {$this->tbpref}words
                SET WoStatusChanged = NOW(), {$scoreUpdate}
                WHERE WoID IN ({$placeholders})";

        return Connection::preparedExecute($sql, $ids);
    }

    /**
     * Delete sentences for multiple words.
     *
     * @param int[] $wordIds Array of word IDs
     *
     * @return int Number of updated words
     */
    public function deleteSentencesMultiple(array $wordIds): int
    {
        if (empty($wordIds)) {
            return 0;
        }

        $ids = array_map('intval', $wordIds);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        return Connection::preparedExecute(
            "UPDATE {$this->tbpref}words SET WoSentence = NULL WHERE WoID IN ({$placeholders})",
            $ids
        );
    }

    /**
     * Convert words to lowercase.
     *
     * @param int[] $wordIds Array of word IDs
     *
     * @return int Number of updated words
     */
    public function toLowercaseMultiple(array $wordIds): int
    {
        if (empty($wordIds)) {
            return 0;
        }

        $ids = array_map('intval', $wordIds);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        return Connection::preparedExecute(
            "UPDATE {$this->tbpref}words SET WoText = WoTextLC WHERE WoID IN ({$placeholders})",
            $ids
        );
    }

    /**
     * Capitalize words.
     *
     * @param int[] $wordIds Array of word IDs
     *
     * @return int Number of updated words
     */
    public function capitalizeMultiple(array $wordIds): int
    {
        if (empty($wordIds)) {
            return 0;
        }

        $ids = array_map('intval', $wordIds);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        return Connection::preparedExecute(
            "UPDATE {$this->tbpref}words
             SET WoText = CONCAT(UPPER(LEFT(WoTextLC, 1)), SUBSTRING(WoTextLC, 2))
             WHERE WoID IN ({$placeholders})",
            $ids
        );
    }

    /**
     * Get unknown words in a text (words without a WoID).
     *
     * @param int $textId Text ID
     *
     * @return array<int, array<string, mixed>> Array of rows with Ti2Text and Ti2TextLC columns
     */
    public function getUnknownWordsInText(int $textId): array
    {
        return Connection::preparedFetchAll(
            "SELECT DISTINCT Ti2Text, LOWER(Ti2Text) AS Ti2TextLC
             FROM (
                 {$this->tbpref}textitems2
                 LEFT JOIN {$this->tbpref}words
                 ON LOWER(Ti2Text) = WoTextLC AND Ti2LgID = WoLgID
             )
             WHERE WoID IS NULL AND Ti2WordCount = 1 AND Ti2TxID = ?
             ORDER BY Ti2Order",
            [$textId]
        );
    }

    /**
     * Get the language ID for a text.
     *
     * @param int $textId Text ID
     *
     * @return int|null Language ID or null if not found
     */
    public function getTextLanguageId(int $textId): ?int
    {
        $langId = Connection::preparedFetchValue(
            "SELECT TxLgID AS value FROM {$this->tbpref}texts WHERE TxID = ?",
            [$textId]
        );
        return $langId !== null ? (int)$langId : null;
    }

    /**
     * Create a word with a specific status (for bulk operations like "mark all as known").
     *
     * @param int    $langId Language ID
     * @param string $term   The term text
     * @param string $termlc Lowercase version of the term
     * @param int    $status Status to set (98=ignored, 99=well-known)
     *
     * @return array{id: int, rows: int} Word ID and number of inserted rows
     */
    public function createWithStatus(int $langId, string $term, string $termlc, int $status): array
    {
        // Check if already exists
        $existingId = Connection::preparedFetchValue(
            "SELECT WoID AS value FROM {$this->tbpref}words WHERE WoTextLC = ?",
            [$termlc]
        );

        if ($existingId !== null) {
            return ['id' => (int)$existingId, 'rows' => 0];
        }

        $scoreColumns = WordStatusService::makeScoreRandomInsertUpdate('iv');
        $scoreValues = WordStatusService::makeScoreRandomInsertUpdate('id');

        $sql = "INSERT INTO {$this->tbpref}words (
                WoLgID, WoText, WoTextLC, WoStatus, WoStatusChanged, {$scoreColumns}
            ) VALUES (?, ?, ?, ?, NOW(), {$scoreValues})";

        $wid = Connection::preparedInsert($sql, [$langId, $term, $termlc, $status]);
        return ['id' => (int)$wid, 'rows' => 1];
    }

    /**
     * Link all unlinked text items to their corresponding words.
     *
     * Used after bulk word creation to update Ti2WoID references.
     *
     * @return void
     */
    public function linkAllTextItems(): void
    {
        Connection::execute(
            "UPDATE {$this->tbpref}words
             JOIN {$this->tbpref}textitems2
             ON Ti2WoID = 0 AND LOWER(Ti2Text) = WoTextLC AND Ti2LgID = WoLgID
             SET Ti2WoID = WoID"
        );
    }

    /**
     * Get word IDs based on filter criteria.
     *
     * @param array $filters Filter criteria:
     *                       - langId: Language ID filter
     *                       - textId: Text ID filter (words appearing in text)
     *                       - status: Status filter
     *                       - query: Search query
     *                       - queryMode: Query mode (term, rom, transl, etc.)
     *                       - regexMode: Regex mode
     *                       - tag1: Tag 1 filter
     *                       - tag2: Tag 2 filter
     *                       - tag12: Tag logic (0=OR, 1=AND)
     *
     * @return int[] Array of word IDs
     */
    public function getFilteredWordIds(array $filters): array
    {
        $conditions = ['1=1'];
        $params = [];

        // Language filter
        if (!empty($filters['langId'])) {
            $conditions[] = 'WoLgID = ?';
            $params[] = (int)$filters['langId'];
        }

        // Status filter
        if (!empty($filters['status'])) {
            $conditions[] = \makeStatusCondition('WoStatus', (int)$filters['status']);
        }

        // Query filter - builds LIKE/REGEXP condition with placeholders
        if (!empty($filters['query'])) {
            $regexMode = $filters['regexMode'] ?? '';
            $queryMode = $filters['queryMode'] ?? 'term,rom,transl';
            $queryValue = ($regexMode == '') ?
                str_replace("*", "%", mb_strtolower($filters['query'], 'UTF-8')) :
                $filters['query'];

            $queryCondition = $this->buildQueryConditionWithPlaceholders($queryMode, $regexMode, $params, $queryValue);
            $conditions[] = $queryCondition;
        }

        // Tag filter (uses integer values, already safe via cast)
        $whTag = '';
        if (!empty($filters['tag1']) || !empty($filters['tag2'])) {
            $whTag = $this->buildTagCondition(
                $filters['tag1'] ?? '',
                $filters['tag2'] ?? '',
                $filters['tag12'] ?? ''
            );
        }

        $whereClause = implode(' AND ', $conditions);

        // Build SQL
        if (empty($filters['textId'])) {
            $sql = "SELECT DISTINCT WoID FROM (
                {$this->tbpref}words
                LEFT JOIN {$this->tbpref}wordtags ON WoID = WtWoID
            ) WHERE {$whereClause}
            GROUP BY WoID {$whTag}";
        } else {
            $params[] = (int)$filters['textId'];
            $sql = "SELECT DISTINCT WoID FROM (
                {$this->tbpref}words
                LEFT JOIN {$this->tbpref}wordtags ON WoID = WtWoID
            ), {$this->tbpref}textitems2
            WHERE Ti2LgID = WoLgID AND Ti2WoID = WoID AND Ti2TxID = ?
            AND {$whereClause}
            GROUP BY WoID {$whTag}";
        }

        $results = Connection::preparedFetchAll($sql, $params);
        $ids = [];
        foreach ($results as $record) {
            $ids[] = (int)$record['WoID'];
        }

        return $ids;
    }

    /**
     * Build the query condition for word search with placeholders.
     *
     * @param string   $queryMode  Query mode
     * @param string   $regexMode  Regex mode (empty for LIKE, 'REGEXP' for regex)
     * @param array    &$params    Reference to params array to add values
     * @param string   $queryValue The query value to search for
     *
     * @return string SQL condition with placeholders
     */
    private function buildQueryConditionWithPlaceholders(
        string $queryMode,
        string $regexMode,
        array &$params,
        string $queryValue
    ): string {
        $op = $regexMode === '' ? 'LIKE' : $regexMode . 'LIKE';

        // Map of query modes to fields
        $fieldSets = [
            'term,rom,transl' => ['WoText', "IFNULL(WoRomanization,'*')", 'WoTranslation'],
            'term,rom' => ['WoText', "IFNULL(WoRomanization,'*')"],
            'rom,transl' => ["IFNULL(WoRomanization,'*')", 'WoTranslation'],
            'term,transl' => ['WoText', 'WoTranslation'],
            'term' => ['WoText'],
            'rom' => ["IFNULL(WoRomanization,'*')"],
            'transl' => ['WoTranslation'],
        ];

        $fields = $fieldSets[$queryMode] ?? $fieldSets['term,rom,transl'];

        $conditions = [];
        foreach ($fields as $field) {
            $conditions[] = "{$field} {$op} ?";
            $params[] = $queryValue;
        }

        return '(' . implode(' OR ', $conditions) . ')';
    }

    /**
     * Build the tag condition for word filter.
     *
     * @param string $tag1  Tag 1 filter
     * @param string $tag2  Tag 2 filter
     * @param string $tag12 Tag logic (0=OR, 1=AND)
     *
     * @return string SQL HAVING clause
     */
    private function buildTagCondition(string $tag1, string $tag2, string $tag12): string
    {
        $whTag1 = null;
        $whTag2 = null;

        if ($tag1 != '') {
            if ($tag1 == '-1') {
                $whTag1 = "GROUP_CONCAT(WtTgID) IS NULL";
            } else {
                $whTag1 = "CONCAT('/',GROUP_CONCAT(WtTgID SEPARATOR '/'),'/') LIKE '%/" . (int)$tag1 . "/%'";
            }
        }

        if ($tag2 != '') {
            if ($tag2 == '-1') {
                $whTag2 = "GROUP_CONCAT(WtTgID) IS NULL";
            } else {
                $whTag2 = "CONCAT('/',GROUP_CONCAT(WtTgID SEPARATOR '/'),'/') LIKE '%/" . (int)$tag2 . "/%'";
            }
        }

        if ($whTag1 !== null && $whTag2 === null) {
            return " HAVING ($whTag1)";
        } elseif ($whTag2 !== null && $whTag1 === null) {
            return " HAVING ($whTag2)";
        } elseif ($whTag1 !== null && $whTag2 !== null) {
            $op = $tag12 ? ' AND ' : ' OR ';
            return " HAVING (($whTag1)$op($whTag2))";
        }

        return '';
    }

    /**
     * Update status for a single word.
     *
     * @param int $wordId Word ID
     * @param int $status New status (1-5, 98, 99)
     *
     * @return string Result message
     */
    public function setStatus(int $wordId, int $status): string
    {
        $scoreUpdate = WordStatusService::makeScoreRandomInsertUpdate('u');
        Connection::preparedExecute(
            "UPDATE {$this->tbpref}words SET WoStatus = ?, WoStatusChanged = NOW(), {$scoreUpdate} WHERE WoID = ?",
            [$status, $wordId]
        );
        return 'Status changed';
    }

    /**
     * Get word data including translation and romanization.
     *
     * @param int $wordId Word ID
     *
     * @return array{text: string, translation: string, romanization: string}|null
     */
    public function getWordData(int $wordId): ?array
    {
        $record = Connection::preparedFetchOne(
            "SELECT WoText, WoTranslation, WoRomanization
             FROM {$this->tbpref}words WHERE WoID = ?",
            [$wordId]
        );

        if (!$record) {
            return null;
        }

        return [
            'text' => (string) $record['WoText'],
            'translation' => ExportService::replaceTabNewline($record['WoTranslation']),
            'romanization' => (string) $record['WoRomanization']
        ];
    }

    /**
     * Get word text at a specific position in a text.
     *
     * @param int $textId Text ID
     * @param int $ord    Word order position
     *
     * @return string|null Word text or null if not found
     */
    public function getWordAtPosition(int $textId, int $ord): ?string
    {
        $word = Connection::preparedFetchValue(
            "SELECT Ti2Text AS value
             FROM {$this->tbpref}textitems2
             WHERE Ti2WordCount = 1 AND Ti2TxID = ? AND Ti2Order = ?",
            [$textId, $ord]
        );
        return $word !== null ? (string) $word : null;
    }

    /**
     * Insert a word with a specific status and link to text items.
     *
     * Used for quick insert operations (mark as known/ignored).
     *
     * @param int    $textId Text ID (to get language)
     * @param string $term   Word text
     * @param int    $status Status (98=ignored, 99=well-known)
     *
     * @return array{id: int, term: string, termlc: string, hex: string}
     */
    public function insertWordWithStatus(int $textId, string $term, int $status): array
    {
        $termlc = mb_strtolower($term, 'UTF-8');
        $langId = $this->getTextLanguageId($textId);

        if ($langId === null) {
            throw new \RuntimeException("Text ID $textId not found");
        }

        $scoreColumns = WordStatusService::makeScoreRandomInsertUpdate('iv');
        $scoreValues = WordStatusService::makeScoreRandomInsertUpdate('id');

        $sql = "INSERT INTO {$this->tbpref}words (
                WoLgID, WoText, WoTextLC, WoStatus, WoWordCount, WoStatusChanged, {$scoreColumns}
            ) VALUES (?, ?, ?, ?, 1, NOW(), {$scoreValues})";

        $wid = (int) Connection::preparedInsert($sql, [$langId, $term, $termlc, $status]);

        // Link to text items
        Connection::preparedExecute(
            "UPDATE {$this->tbpref}textitems2 SET Ti2WoID = ?
             WHERE Ti2LgID = ? AND LOWER(Ti2Text) = ?",
            [$wid, $langId, $termlc]
        );

        return [
            'id' => $wid,
            'term' => $term,
            'termlc' => $termlc,
            'hex' => StringUtils::toClassName($termlc)
        ];
    }

    /**
     * Get a single word's text by ID.
     *
     * @param int $wordId Word ID
     *
     * @return string|null Word text or null if not found
     */
    public function getWordText(int $wordId): ?string
    {
        $term = Connection::preparedFetchValue(
            "SELECT WoText AS value FROM {$this->tbpref}words WHERE WoID = ?",
            [$wordId]
        );
        return $term !== null ? (string) $term : null;
    }

    /**
     * Update translation for a word (inline edit).
     *
     * @param int    $wordId Word ID
     * @param string $value  New translation value
     *
     * @return string Updated translation value
     */
    public function updateTranslation(int $wordId, string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            $value = '*';
        }

        Connection::preparedExecute(
            "UPDATE {$this->tbpref}words SET WoTranslation = ? WHERE WoID = ?",
            [ExportService::replaceTabNewline($value), $wordId]
        );

        return (string) Connection::preparedFetchValue(
            "SELECT WoTranslation AS value FROM {$this->tbpref}words WHERE WoID = ?",
            [$wordId]
        );
    }

    /**
     * Update romanization for a word (inline edit).
     *
     * @param int    $wordId Word ID
     * @param string $value  New romanization value
     *
     * @return string Updated romanization value (returns '*' if empty for display)
     */
    public function updateRomanization(int $wordId, string $value): string
    {
        $value = trim($value);
        if ($value === '*') {
            $value = '';
        }

        Connection::preparedExecute(
            "UPDATE {$this->tbpref}words SET WoRomanization = ? WHERE WoID = ?",
            [ExportService::replaceTabNewline($value), $wordId]
        );

        $result = Connection::preparedFetchValue(
            "SELECT WoRomanization AS value FROM {$this->tbpref}words WHERE WoID = ?",
            [$wordId]
        );

        return ($result === '' || $result === null) ? '*' : (string) $result;
    }

    /**
     * Delete a multi-word expression by ID.
     *
     * Deletes the word and its associated text items with word count > 1.
     *
     * @param int $wordId Word ID to delete
     *
     * @return int Number of affected rows
     */
    public function deleteMultiWord(int $wordId): int
    {
        $result = QueryBuilder::table('words')
            ->where('WoID', '=', $wordId)
            ->delete();

        \Lwt\Database\Maintenance::adjustAutoIncrement('words', 'WoID');

        QueryBuilder::table('textitems2')
            ->where('Ti2WordCount', '>', 1)
            ->where('Ti2WoID', '=', $wordId)
            ->delete();

        return $result;
    }

    /**
     * Get full word details for display.
     *
     * @param int $wordId Word ID
     *
     * @return array|null Word details or null if not found
     */
    public function getWordDetails(int $wordId): ?array
    {
        $record = Connection::preparedFetchOne(
            "SELECT WoLgID, WoText, WoTranslation, WoSentence, WoRomanization, WoStatus
             FROM {$this->tbpref}words WHERE WoID = ?",
            [$wordId]
        );

        if (!$record) {
            return null;
        }

        $translation = ExportService::replaceTabNewline($record['WoTranslation']);
        if ($translation === '*') {
            $translation = '';
        }

        return [
            'langId' => (int) $record['WoLgID'],
            'text' => (string) $record['WoText'],
            'translation' => $translation,
            'sentence' => (string) $record['WoSentence'],
            'romanization' => (string) $record['WoRomanization'],
            'status' => (int) $record['WoStatus']
        ];
    }

    /**
     * Get all unknown words in a text (words without a WoID).
     *
     * @param int $textId Text ID
     *
     * @return array<int, array<string, mixed>> Array of rows with Ti2Text and Ti2TextLC columns
     */
    public function getAllUnknownWordsInText(int $textId): array
    {
        return Connection::preparedFetchAll(
            "SELECT DISTINCT Ti2Text, LOWER(Ti2Text) AS Ti2TextLC
             FROM (
                 {$this->tbpref}textitems2
                 LEFT JOIN {$this->tbpref}words
                 ON LOWER(Ti2Text) = WoTextLC AND Ti2LgID = WoLgID
             )
             WHERE WoID IS NULL AND Ti2WordCount = 1 AND Ti2TxID = ?
             ORDER BY Ti2Order",
            [$textId]
        );
    }

    /**
     * Process a single word for the "mark all as well-known" operation.
     *
     * @param int    $status New word status
     * @param string $term   Word text
     * @param string $termlc Lowercase word text
     * @param int    $langId Language ID
     *
     * @return array{int, array{wid: int, hex: string, term: string, status: int}|null} Rows modified and word data
     */
    public function processWordForWellKnown(int $status, string $term, string $termlc, int $langId): array
    {
        $wid = Connection::preparedFetchValue(
            "SELECT WoID AS value FROM {$this->tbpref}words WHERE WoTextLC = ?",
            [$termlc]
        );

        if ($wid !== null) {
            return [0, null];
        }

        try {
            $scoreColumns = WordStatusService::makeScoreRandomInsertUpdate('iv');
            $scoreValues = WordStatusService::makeScoreRandomInsertUpdate('id');

            $sql = "INSERT INTO {$this->tbpref}words (
                    WoLgID, WoText, WoTextLC, WoStatus, WoStatusChanged, {$scoreColumns}
                ) VALUES (?, ?, ?, ?, NOW(), {$scoreValues})";

            $stmt = Connection::prepare($sql);
            $stmt->bindValues([$langId, $term, $termlc, $status]);
            $rows = $stmt->execute();
            $wid = (int) $stmt->insertId();

            if ($rows == 0) {
                \Lwt\View\Helper\PageLayoutHelper::renderMessage(
                    "WARNING: No rows modified!",
                    false
                );
            }

            $wordData = [
                'wid' => $wid,
                'hex' => StringUtils::toClassName($termlc),
                'term' => $term,
                'status' => $status
            ];

            return [$rows, $wordData];
        } catch (\RuntimeException $e) {
            ErrorHandler::die("ERROR: Could not modify words! Message: " . $e->getMessage());
        }
    }

    /**
     * Create a word on hover with optional Google Translate.
     *
     * Used when user hovers and clicks to set a word status directly from the text.
     *
     * @param int    $textId      Text ID
     * @param string $text        Word text
     * @param int    $status      Word status (1-5)
     * @param string $translation Optional translation
     *
     * @return array{
     *     wid: int,
     *     word: string,
     *     wordRaw: string,
     *     translation: string,
     *     status: int,
     *     hex: string
     * }
     */
    public function createOnHover(
        int $textId,
        string $text,
        int $status,
        string $translation = '*'
    ): array {
        $wordlc = mb_strtolower($text, 'UTF-8');

        $langId = (int) Connection::preparedFetchValue(
            "SELECT TxLgID AS value FROM {$this->tbpref}texts WHERE TxID = ?",
            [$textId]
        );

        $scoreColumns = WordStatusService::makeScoreRandomInsertUpdate('iv');
        $scoreValues = WordStatusService::makeScoreRandomInsertUpdate('id');

        $sql = "INSERT INTO {$this->tbpref}words (
                WoLgID, WoTextLC, WoText, WoStatus, WoTranslation, WoSentence,
                WoRomanization, WoStatusChanged, {$scoreColumns}
            ) VALUES (?, ?, ?, ?, ?, '', '', NOW(), {$scoreValues})";

        $wid = (int) Connection::preparedInsert($sql, [$langId, $wordlc, $text, $status, $translation]);

        Connection::preparedExecute(
            "UPDATE {$this->tbpref}textitems2 SET Ti2WoID = ?
             WHERE Ti2LgID = ? AND LOWER(Ti2Text) = ?",
            [$wid, $langId, $wordlc]
        );

        $hex = StringUtils::toClassName(
            Escaping::prepareTextdata($wordlc)
        );

        return [
            'wid' => $wid,
            'word' => $text,
            'wordRaw' => $text,
            'translation' => $translation,
            'status' => $status,
            'hex' => $hex
        ];
    }

    /**
     * Mark all unknown words in a text with a specific status.
     *
     * @param int $textId Text ID
     * @param int $status Status to apply (98=ignored, 99=well-known)
     *
     * @return array{int, array<array{wid: int, hex: string, term: string, status: int}>} Total count and words data
     */
    public function markAllWordsWithStatus(int $textId, int $status): array
    {
        $langId = Connection::preparedFetchValue(
            "SELECT TxLgID AS value FROM {$this->tbpref}texts WHERE TxID = ?",
            [$textId]
        );
        $wordsData = [];
        $count = 0;
        $records = $this->getAllUnknownWordsInText($textId);
        foreach ($records as $record) {
            list($modified_rows, $wordData) = $this->processWordForWellKnown(
                $status,
                $record['Ti2Text'],
                $record['Ti2TextLC'],
                (int) $langId
            );
            if ($wordData !== null) {
                $wordsData[] = $wordData;
            }
            $count += $modified_rows;
        }

        // Associate existing textitems.
        Connection::execute(
            "UPDATE {$this->tbpref}words
            JOIN {$this->tbpref}textitems2
            ON Ti2WoID = 0 AND LOWER(Ti2Text) = WoTextLC AND Ti2LgID = WoLgID
            SET Ti2WoID = WoID",
            ''
        );

        return array($count, $wordsData);
    }

    // ===== Bulk translate methods =====

    /**
     * Save multiple terms in bulk.
     *
     * Used by the bulk translate feature to save multiple words at once.
     *
     * @param array $terms Array of term data, each with keys: lg, text, status, trans
     *
     * @return int The max word ID before insertion (for finding new words)
     */
    public function bulkSaveTerms(array $terms): int
    {
        $max = (int) Connection::fetchValue(
            "SELECT COALESCE(MAX(WoID), 0) AS value FROM {$this->tbpref}words"
        );

        if (empty($terms)) {
            return $max;
        }

        $scoreColumns = WordStatusService::makeScoreRandomInsertUpdate('iv');
        $scoreValues = WordStatusService::makeScoreRandomInsertUpdate('id');

        // Insert each term using prepared statements for safety
        foreach ($terms as $row) {
            $trans = (!isset($row['trans']) || $row['trans'] == '') ? '*' : $row['trans'];
            $textlc = mb_strtolower($row['text'], 'UTF-8');

            $sql = "INSERT INTO {$this->tbpref}words (
                    WoLgID, WoTextLC, WoText, WoStatus, WoTranslation, WoSentence,
                    WoRomanization, WoStatusChanged, {$scoreColumns}
                ) VALUES (?, ?, ?, ?, ?, '', '', NOW(), {$scoreValues})";

            Connection::preparedExecute($sql, [$row['lg'], $textlc, $row['text'], $row['status'], $trans]);
        }

        return $max;
    }

    /**
     * Get newly created words after bulk insert.
     *
     * @param int $maxWoId The max word ID before insertion
     *
     * @return array<int, array<string, mixed>> Array of rows with WoID, WoTextLC, WoStatus, WoTranslation
     */
    public function getNewWordsAfter(int $maxWoId): array
    {
        return Connection::preparedFetchAll(
            "SELECT WoID, WoTextLC, WoStatus, WoTranslation
             FROM {$this->tbpref}words
             WHERE WoID > ?",
            [$maxWoId]
        );
    }

    /**
     * Link newly created words to text items.
     *
     * @param int $maxWoId The max word ID before insertion
     *
     * @return void
     */
    public function linkNewWordsToTextItems(int $maxWoId): void
    {
        Connection::preparedExecute(
            "UPDATE {$this->tbpref}textitems2
             JOIN {$this->tbpref}words
             ON LOWER(Ti2Text) = WoTextLC AND Ti2WordCount = 1 AND Ti2LgID = WoLgID AND WoID > ?
             SET Ti2WoID = WoID",
            [$maxWoId]
        );
    }

    /**
     * Get language dictionary URIs for a text.
     *
     * @param int $textId Text ID
     *
     * @return array{name: string, dict1: string, dict2: string, translate: string}
     */
    public function getLanguageDictionaries(int $textId): array
    {
        $record = Connection::preparedFetchOne(
            "SELECT LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI
             FROM {$this->tbpref}languages, {$this->tbpref}texts
             WHERE LgID = TxLgID AND TxID = ?",
            [$textId]
        );

        return [
            'name' => $record['LgName'] ?? '',
            'dict1' => $record['LgDict1URI'] ?? '',
            'dict2' => $record['LgDict2URI'] ?? '',
            'translate' => $record['LgGoogleTranslateURI'] ?? '',
        ];
    }

    /**
     * Get unknown words for bulk translation with pagination.
     *
     * @param int $textId Text ID
     * @param int $offset Starting position
     * @param int $limit  Number of words to return
     *
     * @return array<int, array<string, mixed>> Array of rows with word, Ti2LgID, pos columns
     */
    public function getUnknownWordsForBulkTranslate(
        int $textId,
        int $offset,
        int $limit
    ): array {
        return Connection::preparedFetchAll(
            "SELECT Ti2Text AS word, Ti2LgID, MIN(Ti2Order) AS pos
             FROM {$this->tbpref}textitems2
             WHERE Ti2WoID = 0 AND Ti2TxID = ? AND Ti2WordCount = 1
             GROUP BY LOWER(Ti2Text)
             ORDER BY pos
             LIMIT ?, ?",
            [$textId, $offset, $limit]
        );
    }

    // ===== Multi-word expression methods =====

    /**
     * Create a new multi-word expression.
     *
     * @param array $data Multi-word data with keys:
     *                    - lgid: Language ID
     *                    - text: Term text
     *                    - textlc: Lowercase term text
     *                    - status: Learning status (1-5)
     *                    - translation: Translation text
     *                    - sentence: Example sentence
     *                    - roman: Romanization/phonetic
     *                    - wordcount: Number of words in expression
     *
     * @return array{id: int, message: string}
     */
    public function createMultiWord(array $data): array
    {
        $scoreColumns = WordStatusService::makeScoreRandomInsertUpdate('iv');
        $scoreValues = WordStatusService::makeScoreRandomInsertUpdate('id');

        $sql = "INSERT INTO {$this->tbpref}words (
                WoLgID, WoTextLC, WoText, WoStatus, WoTranslation, WoSentence,
                WoRomanization, WoWordCount, WoStatusChanged, {$scoreColumns}
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), {$scoreValues})";

        $sentence = ExportService::replaceTabNewline($data['sentence']);

        $wid = (int) Connection::preparedInsert($sql, [
            (int) $data['lgid'],
            $data['textlc'],
            $data['text'],
            (int) $data['status'],
            $data['translation'],
            $sentence,
            $data['roman'],
            (int) $data['wordcount']
        ]);

        \Lwt\Database\Maintenance::initWordCount();
        TagService::saveWordTags($wid);
        (new ExpressionService())->insertExpressions($data['textlc'], (int) $data['lgid'], $wid, (int) $data['wordcount'], 0);

        return [
            'id' => $wid,
            'message' => 'Term saved'
        ];
    }

    /**
     * Update an existing multi-word expression.
     *
     * @param int   $wordId    Word ID
     * @param array $data      Multi-word data (same keys as createMultiWord)
     * @param int   $oldStatus Previous status for comparison
     * @param int   $newStatus New status to set
     *
     * @return array{id: int, message: string, status: int}
     */
    public function updateMultiWord(int $wordId, array $data, int $oldStatus, int $newStatus): array
    {
        $scoreUpdate = WordStatusService::makeScoreRandomInsertUpdate('u');
        $sentence = ExportService::replaceTabNewline($data['sentence']);

        if ($oldStatus != $newStatus) {
            // Status changed - update status and timestamp
            $sql = "UPDATE {$this->tbpref}words SET
                    WoText = ?, WoTranslation = ?, WoSentence = ?, WoRomanization = ?,
                    WoStatus = ?, WoStatusChanged = NOW(), {$scoreUpdate}
                    WHERE WoID = ?";
            Connection::preparedExecute($sql, [
                $data['text'],
                $data['translation'],
                $sentence,
                $data['roman'],
                $newStatus,
                $wordId
            ]);
        } else {
            // Status unchanged
            $sql = "UPDATE {$this->tbpref}words SET
                    WoText = ?, WoTranslation = ?, WoSentence = ?, WoRomanization = ?, {$scoreUpdate}
                    WHERE WoID = ?";
            Connection::preparedExecute($sql, [
                $data['text'],
                $data['translation'],
                $sentence,
                $data['roman'],
                $wordId
            ]);
        }

        TagService::saveWordTags($wordId);

        return [
            'id' => $wordId,
            'message' => 'Updated',
            'status' => $newStatus
        ];
    }

    /**
     * Get multi-word data for editing.
     *
     * @param int $wordId Word ID
     *
     * @return array|null Multi-word data or null if not found
     */
    public function getMultiWordData(int $wordId): ?array
    {
        $record = Connection::preparedFetchOne(
            "SELECT WoText, WoLgID, WoTranslation, WoSentence, WoRomanization, WoStatus
             FROM {$this->tbpref}words WHERE WoID = ?",
            [$wordId]
        );

        if (!$record) {
            return null;
        }

        return [
            'text' => (string) $record['WoText'],
            'lgid' => (int) $record['WoLgID'],
            'translation' => ExportService::replaceTabNewline($record['WoTranslation']),
            'sentence' => ExportService::replaceTabNewline($record['WoSentence']),
            'romanization' => (string) $record['WoRomanization'],
            'status' => (int) $record['WoStatus']
        ];
    }

    /**
     * Get language ID for a text.
     *
     * @param int $textId Text ID
     *
     * @return int|null Language ID or null if not found
     */
    public function getLanguageIdFromText(int $textId): ?int
    {
        $lgid = Connection::preparedFetchValue(
            "SELECT TxLgID AS value FROM {$this->tbpref}texts WHERE TxID = ?",
            [$textId]
        );
        return $lgid !== null ? (int) $lgid : null;
    }

    /**
     * Get sentence ID at a text position.
     *
     * @param int $textId Text ID
     * @param int $ord    Position in text
     *
     * @return int|null Sentence ID or null if not found
     */
    public function getSentenceIdAtPosition(int $textId, int $ord): ?int
    {
        $seid = Connection::preparedFetchValue(
            "SELECT Ti2SeID AS value
             FROM {$this->tbpref}textitems2
             WHERE Ti2TxID = ? AND Ti2Order = ?",
            [$textId, $ord]
        );
        return $seid !== null ? (int) $seid : null;
    }

    /**
     * Get sentence text at a text position.
     *
     * @param int $textId Text ID
     * @param int $ord    Position in text
     *
     * @return string|null Sentence text or null if not found
     */
    public function getSentenceTextAtPosition(int $textId, int $ord): ?string
    {
        $seid = $this->getSentenceIdAtPosition($textId, $ord);
        if ($seid === null) {
            return null;
        }

        $sentence = Connection::preparedFetchValue(
            "SELECT SeText AS value FROM {$this->tbpref}sentences WHERE SeID = ?",
            [$seid]
        );

        return $sentence !== null ? (string) $sentence : null;
    }

    /**
     * Check if romanization should be shown for a text's language.
     *
     * @param int $textId Text ID
     *
     * @return bool Whether to show romanization
     */
    public function shouldShowRomanization(int $textId): bool
    {
        return (bool) Connection::preparedFetchValue(
            "SELECT LgShowRomanization AS value
             FROM {$this->tbpref}languages JOIN {$this->tbpref}texts
             ON TxLgID = LgID
             WHERE TxID = ?",
            [$textId]
        );
    }

    /**
     * Find multi-word by text and language.
     *
     * @param string $textlc Lowercase text
     * @param int    $langId Language ID
     *
     * @return int|null Word ID or null if not found
     */
    public function findMultiWordByText(string $textlc, int $langId): ?int
    {
        $wid = Connection::preparedFetchValue(
            "SELECT WoID AS value FROM {$this->tbpref}words
             WHERE WoLgID = ? AND WoTextLC = ?",
            [$langId, $textlc]
        );
        return $wid !== null ? (int) $wid : null;
    }

    /**
     * Export term data as JSON for JavaScript.
     *
     * @param int    $wordId      Word ID
     * @param string $text        Term text
     * @param string $roman       Romanization
     * @param string $translation Translation with tags
     * @param int    $status      Word status
     *
     * @return string JSON encoded data
     */
    public function exportTermAsJson(
        int $wordId,
        string $text,
        string $roman,
        string $translation,
        int $status
    ): string {
        $data = [
            "woid" => $wordId,
            "text" => $text,
            "romanization" => $roman,
            "translation" => $translation,
            "status" => $status
        ];

        $json = json_encode($data);
        if ($json === false) {
            $json = json_encode(["error" => "Unable to return data."]);
            if ($json === false) {
                throw new \RuntimeException("Unable to return data");
            }
        }
        return $json;
    }
}
