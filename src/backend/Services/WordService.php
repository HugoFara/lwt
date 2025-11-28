<?php

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

use Lwt\Core\Globals;
use Lwt\Database\Connection;
use Lwt\Database\Escaping;
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
            Connection::execute(
                'INSERT INTO ' . $this->tbpref . 'words (
                    WoLgID, WoTextLC, WoText, WoStatus, WoTranslation,
                    WoSentence, WoRomanization, WoStatusChanged, ' .
                    \make_score_random_insert_update('iv') . '
                ) VALUES (
                    ' . (int)$data['WoLgID'] . ', ' .
                    Escaping::toSqlSyntax($textlc) . ', ' .
                    Escaping::toSqlSyntax($text) . ', ' .
                    (int)$data['WoStatus'] . ', ' .
                    Escaping::toSqlSyntax($translation) . ', ' .
                    Escaping::toSqlSyntax(\repl_tab_nl($data['WoSentence'] ?? '')) . ', ' .
                    Escaping::toSqlSyntax($data['WoRomanization'] ?? '') . ', NOW(), ' .
                    \make_score_random_insert_update('id') .
                ')'
            );

            $wid = (int)Connection::lastInsertId();

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

        $statusUpdate = '';
        if (isset($data['WoOldStatus']) && $data['WoOldStatus'] != $data['WoStatus']) {
            $statusUpdate = ', WoStatus = ' . (int)$data['WoStatus'] . ', WoStatusChanged = NOW()';
        }

        Connection::execute(
            'UPDATE ' . $this->tbpref . 'words SET
                WoText = ' . Escaping::toSqlSyntax($text) . ',
                WoTranslation = ' . Escaping::toSqlSyntax($translation) . ',
                WoSentence = ' . Escaping::toSqlSyntax(\repl_tab_nl($data['WoSentence'] ?? '')) . ',
                WoRomanization = ' . Escaping::toSqlSyntax($data['WoRomanization'] ?? '') .
                $statusUpdate . ',' .
                \make_score_random_insert_update('u') . '
            WHERE WoID = ' . $wordId
        );

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
        $sql = "SELECT * FROM {$this->tbpref}words WHERE WoID = $wordId";
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        return $record ?: null;
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
        $id = Connection::fetchValue(
            "SELECT WoID AS value FROM {$this->tbpref}words
            WHERE WoLgID = $langId AND WoTextLC = " . Escaping::toSqlSyntax($textlc)
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
        $sql = "SELECT Ti2Text, Ti2LgID FROM {$this->tbpref}textitems2
                WHERE Ti2TxID = $textId AND Ti2WordCount = 1 AND Ti2Order = $ord";
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        return $record ?: null;
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
        Connection::query(
            'UPDATE ' . $this->tbpref . 'textitems2 SET Ti2WoID = ' . $wordId . '
            WHERE Ti2LgID = ' . $langId . ' AND LOWER(Ti2Text) = ' .
            Escaping::toSqlSyntaxNoTrimNoNull($textlc)
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
        $data = [];

        $data['showRoman'] = (bool) Connection::fetchValue(
            "SELECT LgShowRomanization AS value FROM {$this->tbpref}languages WHERE LgID = $langId"
        );

        $data['translateUri'] = (string) Connection::fetchValue(
            "SELECT LgGoogleTranslateURI AS value FROM {$this->tbpref}languages WHERE LgID = $langId"
        );

        $data['name'] = (string) Connection::fetchValue(
            "SELECT LgName AS value FROM {$this->tbpref}languages WHERE LgID = $langId"
        );

        return $data;
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
        $seid = Connection::fetchValue(
            "SELECT Ti2SeID AS value FROM {$this->tbpref}textitems2
            WHERE Ti2TxID = $textId AND Ti2WordCount = 1 AND Ti2Order = $ord"
        );

        if ($seid === null) {
            return '';
        }

        $sent = \getSentence(
            $seid,
            $termlc,
            (int) Settings::getWithDefault('set-term-sentence-count')
        );

        return \repl_tab_nl($sent[1] ?? '');
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
        $translation = trim(\repl_tab_nl($translation));
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
        return (int) Connection::fetchValue(
            "SELECT WoWordCount AS value FROM {$this->tbpref}words WHERE WoID = $wordId"
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
        return \strToClassName(Escaping::prepareTextdata($text));
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
        Connection::execute(
            'DELETE FROM ' . $this->tbpref . 'words WHERE WoID = ' . $wordId
        );

        // Update text items to unlink the word
        Connection::query(
            'UPDATE ' . $this->tbpref . 'textitems2 SET Ti2WoID = 0
             WHERE Ti2WordCount = 1 AND Ti2WoID = ' . $wordId
        );

        // Delete multi-word text items
        Connection::query(
            'DELETE FROM ' . $this->tbpref . 'textitems2 WHERE Ti2WoID = ' . $wordId
        );

        // Clean up orphaned word tags
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

        $list = '(' . implode(',', array_map('intval', $wordIds)) . ')';

        $count = (int) Connection::execute(
            'DELETE FROM ' . $this->tbpref . 'words WHERE WoID IN ' . $list
        );

        // Update text items
        Connection::query(
            'UPDATE ' . $this->tbpref . 'textitems2 SET Ti2WoID = 0
             WHERE Ti2WordCount = 1 AND Ti2WoID IN ' . $list
        );

        Connection::query(
            'DELETE FROM ' . $this->tbpref . 'textitems2 WHERE Ti2WoID IN ' . $list
        );

        // Clean up orphaned word tags
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

        $list = '(' . implode(',', array_map('intval', $wordIds)) . ')';

        if ($relative) {
            if ($status > 0) {
                // Increment status
                return (int) Connection::execute(
                    'UPDATE ' . $this->tbpref . 'words
                     SET WoStatus = WoStatus + 1, WoStatusChanged = NOW(), ' .
                    \make_score_random_insert_update('u') . '
                     WHERE WoStatus IN (1,2,3,4) AND WoID IN ' . $list
                );
            } else {
                // Decrement status
                return (int) Connection::execute(
                    'UPDATE ' . $this->tbpref . 'words
                     SET WoStatus = WoStatus - 1, WoStatusChanged = NOW(), ' .
                    \make_score_random_insert_update('u') . '
                     WHERE WoStatus IN (2,3,4,5) AND WoID IN ' . $list
                );
            }
        }

        // Absolute status
        return (int) Connection::execute(
            'UPDATE ' . $this->tbpref . 'words
             SET WoStatus = ' . $status . ', WoStatusChanged = NOW(), ' .
            \make_score_random_insert_update('u') . '
             WHERE WoID IN ' . $list
        );
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

        $list = '(' . implode(',', array_map('intval', $wordIds)) . ')';

        return (int) Connection::execute(
            'UPDATE ' . $this->tbpref . 'words
             SET WoStatusChanged = NOW(), ' .
            \make_score_random_insert_update('u') . '
             WHERE WoID IN ' . $list
        );
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

        $list = '(' . implode(',', array_map('intval', $wordIds)) . ')';

        return (int) Connection::execute(
            'UPDATE ' . $this->tbpref . 'words SET WoSentence = NULL WHERE WoID IN ' . $list
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

        $list = '(' . implode(',', array_map('intval', $wordIds)) . ')';

        return (int) Connection::execute(
            'UPDATE ' . $this->tbpref . 'words SET WoText = WoTextLC WHERE WoID IN ' . $list
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

        $list = '(' . implode(',', array_map('intval', $wordIds)) . ')';

        return (int) Connection::execute(
            'UPDATE ' . $this->tbpref . 'words
             SET WoText = CONCAT(UPPER(LEFT(WoTextLC, 1)), SUBSTRING(WoTextLC, 2))
             WHERE WoID IN ' . $list
        );
    }

    /**
     * Get unknown words in a text (words without a WoID).
     *
     * @param int $textId Text ID
     *
     * @return \mysqli_result|false Query result with Ti2Text and Ti2TextLC columns
     */
    public function getUnknownWordsInText(int $textId): \mysqli_result|false
    {
        $sql = "SELECT DISTINCT Ti2Text, LOWER(Ti2Text) AS Ti2TextLC
                FROM (
                    {$this->tbpref}textitems2
                    LEFT JOIN {$this->tbpref}words
                    ON LOWER(Ti2Text) = WoTextLC AND Ti2LgID = WoLgID
                )
                WHERE WoID IS NULL AND Ti2WordCount = 1 AND Ti2TxID = $textId
                ORDER BY Ti2Order";
        return Connection::query($sql);
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
        $langId = Connection::fetchValue(
            "SELECT TxLgID AS value FROM {$this->tbpref}texts WHERE TxID = $textId"
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
        $existingId = Connection::fetchValue(
            "SELECT WoID AS value FROM {$this->tbpref}words WHERE WoTextLC = " .
            Escaping::toSqlSyntax($termlc)
        );

        if ($existingId !== null) {
            return ['id' => (int)$existingId, 'rows' => 0];
        }

        Connection::execute(
            "INSERT INTO {$this->tbpref}words (
                WoLgID, WoText, WoTextLC, WoStatus, WoStatusChanged, " .
            \make_score_random_insert_update('iv') . ")
            VALUES (
                $langId, " .
            Escaping::toSqlSyntax($term) . ", " .
            Escaping::toSqlSyntax($termlc) . ", $status, NOW(), " .
            \make_score_random_insert_update('id') . ")"
        );

        $wid = (int)Connection::lastInsertId();
        return ['id' => $wid, 'rows' => 1];
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
        $whLang = '';
        $whStat = '';
        $whQuery = '';
        $whTag = '';

        // Language filter
        if (!empty($filters['langId'])) {
            $whLang = ' AND WoLgID = ' . (int)$filters['langId'];
        }

        // Status filter
        if (!empty($filters['status'])) {
            $whStat = ' AND ' . \makeStatusCondition('WoStatus', (int)$filters['status']);
        }

        // Query filter
        if (!empty($filters['query'])) {
            $regexMode = $filters['regexMode'] ?? '';
            $queryMode = $filters['queryMode'] ?? 'term,rom,transl';
            $queryPattern = $regexMode . 'LIKE ' . Escaping::toSqlSyntax(
                ($regexMode == '') ?
                str_replace("*", "%", mb_strtolower($filters['query'], 'UTF-8')) :
                $filters['query']
            );

            $whQuery = $this->buildQueryCondition($queryMode, $queryPattern);
        }

        // Tag filter
        if (!empty($filters['tag1']) || !empty($filters['tag2'])) {
            $whTag = $this->buildTagCondition(
                $filters['tag1'] ?? '',
                $filters['tag2'] ?? '',
                $filters['tag12'] ?? ''
            );
        }

        // Build SQL
        if (empty($filters['textId'])) {
            $sql = "SELECT DISTINCT WoID FROM (
                {$this->tbpref}words
                LEFT JOIN {$this->tbpref}wordtags ON WoID = WtWoID
            ) WHERE (1=1) $whLang $whStat $whQuery
            GROUP BY WoID $whTag";
        } else {
            $textId = (int)$filters['textId'];
            $sql = "SELECT DISTINCT WoID FROM (
                {$this->tbpref}words
                LEFT JOIN {$this->tbpref}wordtags ON WoID = WtWoID
            ), {$this->tbpref}textitems2
            WHERE Ti2LgID = WoLgID AND Ti2WoID = WoID AND Ti2TxID = $textId
            $whLang $whStat $whQuery
            GROUP BY WoID $whTag";
        }

        $ids = [];
        $res = Connection::query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            $ids[] = (int)$record['WoID'];
        }
        mysqli_free_result($res);

        return $ids;
    }

    /**
     * Build the query condition for word search.
     *
     * @param string $queryMode    Query mode
     * @param string $queryPattern Query pattern
     *
     * @return string SQL condition
     */
    private function buildQueryCondition(string $queryMode, string $queryPattern): string
    {
        switch ($queryMode) {
            case 'term,rom,transl':
                return " AND (WoText $queryPattern OR IFNULL(WoRomanization,'*') $queryPattern OR WoTranslation $queryPattern)";
            case 'term,rom':
                return " AND (WoText $queryPattern OR IFNULL(WoRomanization,'*') $queryPattern)";
            case 'rom,transl':
                return " AND (IFNULL(WoRomanization,'*') $queryPattern OR WoTranslation $queryPattern)";
            case 'term,transl':
                return " AND (WoText $queryPattern OR WoTranslation $queryPattern)";
            case 'term':
                return " AND (WoText $queryPattern)";
            case 'rom':
                return " AND (IFNULL(WoRomanization,'*') $queryPattern)";
            case 'transl':
                return " AND (WoTranslation $queryPattern)";
            default:
                return " AND (WoText $queryPattern OR IFNULL(WoRomanization,'*') $queryPattern OR WoTranslation $queryPattern)";
        }
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
        return (string) Connection::execute(
            sprintf(
                "UPDATE %swords SET WoStatus = %d, WoStatusChanged = NOW(), %s WHERE WoID = %d",
                $this->tbpref,
                $status,
                \make_score_random_insert_update('u'),
                $wordId
            ),
            'Status changed'
        );
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
        $res = Connection::query(
            "SELECT WoText, WoTranslation, WoRomanization
             FROM {$this->tbpref}words WHERE WoID = $wordId"
        );
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);

        if (!$record) {
            return null;
        }

        return [
            'text' => (string) $record['WoText'],
            'translation' => \repl_tab_nl($record['WoTranslation']),
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
        $word = Connection::fetchValue(
            "SELECT Ti2Text AS value
             FROM {$this->tbpref}textitems2
             WHERE Ti2WordCount = 1 AND Ti2TxID = $textId AND Ti2Order = $ord"
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

        Connection::execute(
            "INSERT INTO {$this->tbpref}words (
                WoLgID, WoText, WoTextLC, WoStatus, WoWordCount, WoStatusChanged, " .
            \make_score_random_insert_update('iv') . "
            ) VALUES (
                $langId, " .
            Escaping::toSqlSyntax($term) . ", " .
            Escaping::toSqlSyntax($termlc) . ", $status, 1, NOW(), " .
            \make_score_random_insert_update('id') . "
            )",
            'Term added'
        );

        $wid = (int) Connection::lastInsertId();

        // Link to text items
        Connection::query(
            "UPDATE {$this->tbpref}textitems2
             SET Ti2WoID = $wid
             WHERE Ti2LgID = $langId AND LOWER(Ti2Text) = " . Escaping::toSqlSyntax($termlc)
        );

        return [
            'id' => $wid,
            'term' => $term,
            'termlc' => $termlc,
            'hex' => \strToClassName($termlc)
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
        $term = Connection::fetchValue(
            "SELECT WoText AS value FROM {$this->tbpref}words WHERE WoID = $wordId"
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

        Connection::execute(
            'UPDATE ' . $this->tbpref . 'words SET WoTranslation = ' .
            Escaping::toSqlSyntax(\repl_tab_nl($value)) . ' WHERE WoID = ' . $wordId
        );

        return (string) Connection::fetchValue(
            "SELECT WoTranslation AS value FROM {$this->tbpref}words WHERE WoID = " . $wordId
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

        Connection::execute(
            'UPDATE ' . $this->tbpref . 'words SET WoRomanization = ' .
            Escaping::toSqlSyntax(\repl_tab_nl($value)) . ' WHERE WoID = ' . $wordId
        );

        $result = Connection::fetchValue(
            "SELECT WoRomanization AS value FROM {$this->tbpref}words WHERE WoID = " . $wordId
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
        $result = Connection::execute(
            'DELETE FROM ' . $this->tbpref . 'words WHERE WoID = ' . $wordId
        );

        \Lwt\Database\Maintenance::adjustAutoIncrement('words', 'WoID');

        Connection::execute(
            'DELETE FROM ' . $this->tbpref . 'textitems2 WHERE Ti2WordCount > 1 AND Ti2WoID = ' . $wordId
        );

        return (int) $result;
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
        $sql = 'SELECT WoLgID, WoText, WoTranslation, WoSentence, WoRomanization, WoStatus
                FROM ' . $this->tbpref . 'words WHERE WoID = ' . $wordId;
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);

        if (!$record) {
            return null;
        }

        $translation = \repl_tab_nl($record['WoTranslation']);
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
     * @return \mysqli_result|false Query result with Ti2Text and Ti2TextLC columns
     */
    public function getAllUnknownWordsInText(int $textId): \mysqli_result|false
    {
        $sql = "SELECT DISTINCT Ti2Text, LOWER(Ti2Text) AS Ti2TextLC
        FROM (
            {$this->tbpref}textitems2
            LEFT JOIN {$this->tbpref}words
            ON LOWER(Ti2Text) = WoTextLC AND Ti2LgID = WoLgID
        )
        WHERE WoID IS NULL AND Ti2WordCount = 1 AND Ti2TxID = $textId
        ORDER BY Ti2Order";
        return Connection::query($sql);
    }

    /**
     * Process a single word for the "mark all as well-known" operation.
     *
     * @param int    $status New word status
     * @param string $term   Word text
     * @param string $termlc Lowercase word text
     * @param int    $langId Language ID
     *
     * @return array{int, string} Rows modified and JavaScript code
     */
    public function processWordForWellKnown(int $status, string $term, string $termlc, int $langId): array
    {
        $wid = Connection::fetchValue(
            "SELECT WoID AS value FROM {$this->tbpref}words
            WHERE WoTextLC = " . Escaping::toSqlSyntax($termlc)
        );
        if ($wid !== null) {
            $rows = 0;
        } else {
            $message = Connection::execute(
                "INSERT INTO {$this->tbpref}words (
                    WoLgID, WoText, WoTextLC, WoStatus, WoStatusChanged,"
                    . \make_score_random_insert_update('iv') .
                ")
                VALUES(
                    $langId, " .
                    Escaping::toSqlSyntax($term) . ", " .
                    Escaping::toSqlSyntax($termlc) . ", $status, NOW(), " .
                    \make_score_random_insert_update('id') .
                ")",
                ''
            );
            if (!is_numeric($message)) {
                \my_die("ERROR: Could not modify words! Message: $message");
            }
            if ((int)$message == 0) {
                \error_message_with_hide(
                    "WARNING: No rows modified! Message: $message",
                    false
                );
            }
            $rows = (int) $message;
            $wid = \get_last_key();
        }
        $javascript = '';
        if (Settings::getWithDefault('set-tooltip-mode') == 1 && $rows > 0) {
            $javascript .= "title = make_tooltip(" .
            Escaping::prepareTextdataJs($term) . ", '*', '', '$status');";
        }
        $javascript .= "$('.TERM" . \strToClassName($termlc) . "', context)
        .removeClass('status0')
        .addClass('status$status word$wid')
        .attr('data_status', '$status')
        .attr('data_wid', '$wid')
        .attr('title', title);";
        return array($rows, $javascript);
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
        $word = Escaping::toSqlSyntax($text);
        $wordlc = Escaping::toSqlSyntax(mb_strtolower($text, 'UTF-8'));

        $langId = (int) Connection::fetchValue(
            "SELECT TxLgID AS value FROM {$this->tbpref}texts WHERE TxID = $textId"
        );

        Connection::execute(
            "INSERT INTO {$this->tbpref}words (
                WoLgID, WoTextLC, WoText, WoStatus, WoTranslation, WoSentence,
                WoRomanization, WoStatusChanged," .
                \make_score_random_insert_update('iv') . ") values(
                    $langId, $wordlc, $word, $status, " .
                    Escaping::toSqlSyntax($translation) . ', "", "", NOW(), ' .
                    \make_score_random_insert_update('id') . '
                )',
            "Term saved"
        );

        $wid = (int) Connection::lastInsertId();

        Connection::query(
            "UPDATE {$this->tbpref}textitems2 SET Ti2WoID = $wid
            WHERE Ti2LgID = $langId AND LOWER(Ti2Text) = $wordlc"
        );

        $hex = \strToClassName(
            Escaping::prepareTextdata(mb_strtolower($text, 'UTF-8'))
        );

        return [
            'wid' => $wid,
            'word' => $word,
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
     * @return array{int, string} Total count and JavaScript code
     */
    public function markAllWordsWithStatus(int $textId, int $status): array
    {
        $langId = Connection::fetchValue(
            "SELECT TxLgID AS value
            FROM {$this->tbpref}texts
            WHERE TxID = $textId"
        );
        $javascript = "let title='';";
        $count = 0;
        $res = $this->getAllUnknownWordsInText($textId);
        while ($record = mysqli_fetch_assoc($res)) {
            list($modified_rows, $new_js) = $this->processWordForWellKnown(
                $status,
                $record['Ti2Text'],
                $record['Ti2TextLC'],
                (int) $langId
            );
            $javascript .= $new_js;
            $count += $modified_rows;
        }
        mysqli_free_result($res);

        // Associate existing textitems.
        Connection::execute(
            "UPDATE {$this->tbpref}words
            JOIN {$this->tbpref}textitems2
            ON Ti2WoID = 0 AND LOWER(Ti2Text) = WoTextLC AND Ti2LgID = WoLgID
            SET Ti2WoID = WoID",
            ''
        );

        return array($count, $javascript);
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

        $sqlarr = [];
        foreach ($terms as $row) {
            $trans = (!isset($row['trans']) || $row['trans'] == '') ? '"*"' :
                Escaping::toSqlSyntax($row['trans']);

            $sqlarr[] = '(' .
                Escaping::toSqlSyntax($row['lg']) . ',' .
                Escaping::toSqlSyntax(mb_strtolower($row['text'], 'UTF-8')) . ',' .
                Escaping::toSqlSyntax($row['text']) . ',' .
                Escaping::toSqlSyntax($row['status']) . ',' .
                $trans . ',
                "",
                "",
                NOW(), ' .
                \make_score_random_insert_update('id') .
            ')';
        }

        if (empty($sqlarr)) {
            return $max;
        }

        $sqltext = "INSERT INTO {$this->tbpref}words (
            WoLgID, WoTextLC, WoText, WoStatus, WoTranslation, WoSentence,
            WoRomanization, WoStatusChanged," .
            \make_score_random_insert_update('iv') . "
        ) VALUES " . rtrim(implode(',', $sqlarr), ',');

        Connection::execute($sqltext, '');

        return $max;
    }

    /**
     * Get newly created words after bulk insert.
     *
     * @param int $maxWoId The max word ID before insertion
     *
     * @return \mysqli_result|bool Query result with WoID, WoTextLC, WoStatus, WoTranslation
     */
    public function getNewWordsAfter(int $maxWoId): \mysqli_result|bool
    {
        return Connection::query(
            "SELECT WoID, WoTextLC, WoStatus, WoTranslation
            FROM {$this->tbpref}words
            WHERE WoID > $maxWoId"
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
        Connection::query(
            "UPDATE {$this->tbpref}textitems2
            JOIN {$this->tbpref}words
            ON LOWER(Ti2Text) = WoTextLC AND Ti2WordCount = 1 AND Ti2LgID = WoLgID AND WoID > $maxWoId
            SET Ti2WoID = WoID"
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
        $sql = "SELECT LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI
                FROM {$this->tbpref}languages, {$this->tbpref}texts
                WHERE LgID = TxLgID AND TxID = $textId";
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);

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
     * @return \mysqli_result|bool Query result with word, Ti2LgID, pos columns
     */
    public function getUnknownWordsForBulkTranslate(
        int $textId,
        int $offset,
        int $limit
    ): \mysqli_result|bool {
        return Connection::query(
            "SELECT Ti2Text AS word, Ti2LgID, MIN(Ti2Order) AS pos
            FROM {$this->tbpref}textitems2
            WHERE Ti2WoID = 0 AND Ti2TxID = $textId AND Ti2WordCount = 1
            GROUP BY LOWER(Ti2Text)
            ORDER BY pos
            LIMIT $offset, $limit"
        );
    }
}
