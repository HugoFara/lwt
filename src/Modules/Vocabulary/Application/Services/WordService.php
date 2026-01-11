<?php

/**
 * Word Service - Business logic for word/term operations
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Vocabulary\Application\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Application\Services;

use Lwt\Modules\Text\Application\Services\SentenceService;
use Lwt\Modules\Tags\Application\TagsFacade;
use Lwt\Modules\Vocabulary\Domain\Term;
use Lwt\Modules\Vocabulary\Infrastructure\MySqlTermRepository;
use Lwt\Core\StringUtils;
use Lwt\Core\Utils\ErrorHandler;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\Escaping;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;
use Lwt\View\Helper\StatusHelper;

/**
 * Service class for managing words/terms.
 *
 * Handles CRUD operations for vocabulary items.
 *
 * @category Lwt
 * @package  Lwt\Modules\Vocabulary\Application\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class WordService
{
    private ExpressionService $expressionService;
    private SentenceService $sentenceService;
    private MySqlTermRepository $repository;

    /**
     * Constructor - initialize dependencies.
     *
     * @param ExpressionService|null    $expressionService Expression service (optional for BC)
     * @param SentenceService|null      $sentenceService   Sentence service (optional for BC)
     * @param MySqlTermRepository|null  $repository        Term repository (optional for BC)
     */
    public function __construct(
        ?ExpressionService $expressionService = null,
        ?SentenceService $sentenceService = null,
        ?MySqlTermRepository $repository = null
    ) {
        $this->expressionService = $expressionService ?? new ExpressionService();
        $this->sentenceService = $sentenceService ?? new SentenceService();
        $this->repository = $repository ?? new MySqlTermRepository();
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
     *                    - WoNotes: Personal notes
     *                    - WoRomanization: Romanization/phonetic
     *                    - WoLemma: Lemma/base form (optional)
     *
     * @return array{id: int, message: string, success: bool, textlc: string, text: string}
     */
    public function create(array $data): array
    {
        // Extract typed values from data array
        /** @var string $woText */
        $woText = isset($data['WoText']) && is_string($data['WoText']) ? $data['WoText'] : '';
        /** @var string $woTranslation */
        $woTranslation = isset($data['WoTranslation']) && is_string($data['WoTranslation'])
            ? $data['WoTranslation'] : '';
        /** @var string $woSentence */
        $woSentence = isset($data['WoSentence']) && is_string($data['WoSentence'])
            ? $data['WoSentence'] : '';
        /** @var string $woNotes */
        $woNotes = isset($data['WoNotes']) && is_string($data['WoNotes']) ? $data['WoNotes'] : '';
        /** @var string $woRomanization */
        $woRomanization = isset($data['WoRomanization']) && is_string($data['WoRomanization'])
            ? $data['WoRomanization'] : '';
        /** @var string|null $woLemma */
        $woLemma = isset($data['WoLemma']) && is_string($data['WoLemma']) && $data['WoLemma'] !== ''
            ? $data['WoLemma'] : null;

        $text = trim(Escaping::prepareTextdata($woText));
        $textlc = mb_strtolower($text, 'UTF-8');
        $translation = $this->normalizeTranslation($woTranslation);

        // Handle lemma field
        $lemma = $woLemma !== null ? trim($woLemma) : null;
        $lemmaLc = $lemma !== null ? mb_strtolower($lemma, 'UTF-8') : null;

        try {
            $scoreColumns = TermStatusService::makeScoreRandomInsertUpdate('iv');
            $scoreValues = TermStatusService::makeScoreRandomInsertUpdate('id');

            $bindings = [
                $data['WoLgID'],
                $textlc,
                $text,
                $lemma,
                $lemmaLc,
                $data['WoStatus'],
                $translation,
                ExportService::replaceTabNewline($woSentence),
                ExportService::replaceTabNewline($woNotes),
                $woRomanization
            ];
            $sql = "INSERT INTO words (
                    WoLgID, WoTextLC, WoText, WoLemma, WoLemmaLC, WoStatus, WoTranslation,
                    WoSentence, WoNotes, WoRomanization, WoStatusChanged, {$scoreColumns}"
                    . UserScopedQuery::insertColumn('words')
                . ") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), {$scoreValues}"
                    . UserScopedQuery::insertValuePrepared('words', $bindings)
                . ")";

            $wid = (int) Connection::preparedInsert($sql, $bindings);

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
        // Extract typed values from data array
        /** @var string $woText */
        $woText = isset($data['WoText']) && is_string($data['WoText']) ? $data['WoText'] : '';
        /** @var string $woTranslation */
        $woTranslation = isset($data['WoTranslation']) && is_string($data['WoTranslation'])
            ? $data['WoTranslation'] : '';
        /** @var string $woSentence */
        $woSentence = isset($data['WoSentence']) && is_string($data['WoSentence'])
            ? $data['WoSentence'] : '';
        /** @var string $woNotes */
        $woNotes = isset($data['WoNotes']) && is_string($data['WoNotes']) ? $data['WoNotes'] : '';
        /** @var string $woRomanization */
        $woRomanization = isset($data['WoRomanization']) && is_string($data['WoRomanization'])
            ? $data['WoRomanization'] : '';
        /** @var string|null $woLemma */
        $woLemma = isset($data['WoLemma']) && is_string($data['WoLemma']) && $data['WoLemma'] !== ''
            ? $data['WoLemma'] : null;

        $text = trim(Escaping::prepareTextdata($woText));
        $textlc = mb_strtolower($text, 'UTF-8');
        $translation = $this->normalizeTranslation($woTranslation);
        $sentence = ExportService::replaceTabNewline($woSentence);
        $notes = ExportService::replaceTabNewline($woNotes);
        $roman = $woRomanization;

        // Handle lemma field
        $lemma = $woLemma !== null ? trim($woLemma) : null;
        $lemmaLc = $lemma !== null ? mb_strtolower($lemma, 'UTF-8') : null;

        $scoreUpdate = TermStatusService::makeScoreRandomInsertUpdate('u');

        $bindings = [$text, $translation, $sentence, $notes, $roman, $lemma, $lemmaLc];

        if (isset($data['WoOldStatus']) && $data['WoOldStatus'] != $data['WoStatus']) {
            // Status changed - update status and timestamp
            $bindings[] = (int)$data['WoStatus'];
            $bindings[] = $wordId;
            $sql = "UPDATE words SET
                WoText = ?, WoTranslation = ?, WoSentence = ?, WoNotes = ?, WoRomanization = ?,
                WoLemma = ?, WoLemmaLC = ?,
                WoStatus = ?, WoStatusChanged = NOW(), {$scoreUpdate}
                WHERE WoID = ?"
                . UserScopedQuery::forTablePrepared('words', $bindings);
            Connection::preparedExecute($sql, $bindings);
        } else {
            // Status unchanged
            $bindings[] = $wordId;
            $sql = "UPDATE words SET
                WoText = ?, WoTranslation = ?, WoSentence = ?, WoNotes = ?, WoRomanization = ?,
                WoLemma = ?, WoLemmaLC = ?, {$scoreUpdate}
                WHERE WoID = ?"
                . UserScopedQuery::forTablePrepared('words', $bindings);
            Connection::preparedExecute($sql, $bindings);
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
        $term = $this->repository->find($wordId);
        if ($term === null) {
            return null;
        }

        return $this->termEntityToArray($term);
    }

    /**
     * Convert a Term entity to an array format for backward compatibility.
     *
     * @param Term $term Term entity
     *
     * @return array Term data as associative array
     */
    private function termEntityToArray(Term $term): array
    {
        // Preserve null semantics for WoSentence - empty string means null in DB
        $sentence = $term->sentence();
        if ($sentence === '') {
            $sentence = null;
        }

        // Preserve null semantics for WoNotes - empty string means null in DB
        $notes = $term->notes();
        if ($notes === '') {
            $notes = null;
        }

        return [
            'WoID' => $term->id()->toInt(),
            'WoLgID' => $term->languageId()->toInt(),
            'WoText' => $term->text(),
            'WoTextLC' => $term->textLowercase(),
            'WoLemma' => $term->lemma(),
            'WoLemmaLC' => $term->lemmaLc(),
            'WoStatus' => $term->status()->toInt(),
            'WoTranslation' => $term->translation(),
            'WoSentence' => $sentence,
            'WoNotes' => $notes,
            'WoRomanization' => $term->romanization(),
            'WoWordCount' => $term->wordCount(),
            'WoCreated' => $term->createdAt()->format('Y-m-d H:i:s'),
            'WoStatusChanged' => $term->statusChangedAt()->format('Y-m-d H:i:s'),
            'WoTodayScore' => $term->todayScore(),
            'WoTomorrowScore' => $term->tomorrowScore(),
            'WoRandom' => $term->random(),
        ];
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
        $term = $this->repository->findByTextLc($langId, $textlc);
        return $term !== null ? $term->id()->toInt() : null;
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
        // word_occurrences inherits user context via Ti2TxID -> texts FK
        return Connection::preparedFetchOne(
            "SELECT Ti2Text, Ti2LgID FROM word_occurrences
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
        // word_occurrences inherits user context via Ti2TxID -> texts FK
        Connection::preparedExecute(
            "UPDATE word_occurrences SET Ti2WoID = ?
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
        $bindings = [$langId];
        $row = Connection::preparedFetchOne(
            "SELECT LgShowRomanization, LgGoogleTranslateURI, LgName
             FROM languages WHERE LgID = ?"
             . UserScopedQuery::forTablePrepared('languages', $bindings),
            $bindings
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
        // word_occurrences inherits user context via Ti2TxID -> texts FK
        /** @var int|null $seid */
        $seid = Connection::preparedFetchValue(
            "SELECT Ti2SeID FROM word_occurrences
             WHERE Ti2TxID = ? AND Ti2WordCount = 1 AND Ti2Order = ?",
            [$textId, $ord],
            'Ti2SeID'
        );

        if ($seid === null) {
            return '';
        }

        $sent = $this->sentenceService->formatSentence(
            $seid,
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
        $bindings = [$wordId];
        return (int) Connection::preparedFetchValue(
            "SELECT WoWordCount FROM words WHERE WoID = ?"
            . UserScopedQuery::forTablePrepared('words', $bindings),
            $bindings,
            'WoWordCount'
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
        // Delete multi-word text items first (before word deletion triggers FK SET NULL)
        QueryBuilder::table('word_occurrences')
            ->where('Ti2WoID', '=', $wordId)
            ->where('Ti2WordCount', '>', 1)
            ->deletePrepared();

        // Delete the word - FK constraints handle:
        // - Single-word word_occurrences.Ti2WoID set to NULL (ON DELETE SET NULL)
        // - word_tag_map deleted (ON DELETE CASCADE)
        QueryBuilder::table('words')
            ->where('WoID', '=', $wordId)
            ->deletePrepared();

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

        // Delete multi-word text items first (before word deletion triggers FK SET NULL)
        QueryBuilder::table('word_occurrences')
            ->where('Ti2WordCount', '>', 1)
            ->whereIn('Ti2WoID', $ids)
            ->deletePrepared();

        // Delete words - FK constraints handle:
        // - Single-word word_occurrences.Ti2WoID set to NULL (ON DELETE SET NULL)
        // - word_tag_map deleted (ON DELETE CASCADE)
        $count = QueryBuilder::table('words')
            ->whereIn('WoID', $ids)
            ->deletePrepared();

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

        /** @var array<int, int> $ids */
        $ids = array_map('intval', $wordIds);
        $scoreUpdate = TermStatusService::makeScoreRandomInsertUpdate('u');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        if ($relative) {
            if ($status > 0) {
                // Increment status
                $sql = "UPDATE words
                        SET WoStatus = WoStatus + 1, WoStatusChanged = NOW(), {$scoreUpdate}
                        WHERE WoStatus IN (1,2,3,4) AND WoID IN ({$placeholders})"
                        . UserScopedQuery::forTablePrepared('words', $ids);
                return Connection::preparedExecute($sql, $ids);
            } else {
                // Decrement status
                $sql = "UPDATE words
                        SET WoStatus = WoStatus - 1, WoStatusChanged = NOW(), {$scoreUpdate}
                        WHERE WoStatus IN (2,3,4,5) AND WoID IN ({$placeholders})"
                        . UserScopedQuery::forTablePrepared('words', $ids);
                return Connection::preparedExecute($sql, $ids);
            }
        }

        // Absolute status
        /** @var array<int, int> $bindings */
        $bindings = array_merge([$status], $ids);
        $sql = "UPDATE words
                SET WoStatus = ?, WoStatusChanged = NOW(), {$scoreUpdate}
                WHERE WoID IN ({$placeholders})"
                . UserScopedQuery::forTablePrepared('words', $bindings);

        return Connection::preparedExecute($sql, $bindings);
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

        /** @var array<int, int> $ids */
        $ids = array_map('intval', $wordIds);
        $scoreUpdate = TermStatusService::makeScoreRandomInsertUpdate('u');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "UPDATE words
                SET WoStatusChanged = NOW(), {$scoreUpdate}
                WHERE WoID IN ({$placeholders})"
                . UserScopedQuery::forTablePrepared('words', $ids);

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

        /** @var array<int, int> $ids */
        $ids = array_map('intval', $wordIds);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "UPDATE words SET WoSentence = NULL WHERE WoID IN ({$placeholders})"
            . UserScopedQuery::forTablePrepared('words', $ids);

        return Connection::preparedExecute($sql, $ids);
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

        /** @var array<int, int> $ids */
        $ids = array_map('intval', $wordIds);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "UPDATE words SET WoText = WoTextLC WHERE WoID IN ({$placeholders})"
            . UserScopedQuery::forTablePrepared('words', $ids);

        return Connection::preparedExecute($sql, $ids);
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

        /** @var array<int, int> $ids */
        $ids = array_map('intval', $wordIds);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "UPDATE words
             SET WoText = CONCAT(UPPER(LEFT(WoTextLC, 1)), SUBSTRING(WoTextLC, 2))
             WHERE WoID IN ({$placeholders})"
             . UserScopedQuery::forTablePrepared('words', $ids);

        return Connection::preparedExecute($sql, $ids);
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
        // word_occurrences inherits user context via Ti2TxID -> texts FK
        // words has WoUsID - user scope auto-applied
        $bindings = [$textId];
        return Connection::preparedFetchAll(
            "SELECT DISTINCT Ti2Text, LOWER(Ti2Text) AS Ti2TextLC
             FROM (word_occurrences LEFT JOIN words ON LOWER(Ti2Text) = WoTextLC AND Ti2LgID = WoLgID)
             WHERE WoID IS NULL AND Ti2WordCount = 1 AND Ti2TxID = ?
             ORDER BY Ti2Order"
             . UserScopedQuery::forTablePrepared('words', $bindings, 'words'),
            $bindings
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
        $bindings = [$textId];
        /** @var int|null $langId */
        $langId = Connection::preparedFetchValue(
            "SELECT TxLgID FROM texts WHERE TxID = ?"
            . UserScopedQuery::forTablePrepared('texts', $bindings),
            $bindings,
            'TxLgID'
        );
        return $langId;
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
        $bindings = [$termlc];
        $existingId = Connection::preparedFetchValue(
            "SELECT WoID FROM words WHERE WoTextLC = ?"
            . UserScopedQuery::forTablePrepared('words', $bindings),
            $bindings,
            'WoID'
        );

        if ($existingId !== null) {
            return ['id' => (int)$existingId, 'rows' => 0];
        }

        $scoreColumns = TermStatusService::makeScoreRandomInsertUpdate('iv');
        $scoreValues = TermStatusService::makeScoreRandomInsertUpdate('id');

        $bindings = [$langId, $term, $termlc, $status];
        $sql = "INSERT INTO words (
                WoLgID, WoText, WoTextLC, WoStatus, WoStatusChanged, {$scoreColumns}"
                . UserScopedQuery::insertColumn('words')
            . ") VALUES (?, ?, ?, ?, NOW(), {$scoreValues}"
                . UserScopedQuery::insertValuePrepared('words', $bindings)
            . ")";

        $wid = Connection::preparedInsert($sql, $bindings);
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
        // words has WoUsID - user scope auto-applied
        // word_occurrences inherits user context via Ti2TxID -> texts FK
        Connection::execute(
            "UPDATE words
             JOIN word_occurrences
             ON Ti2WoID IS NULL AND LOWER(Ti2Text) = WoTextLC AND Ti2LgID = WoLgID
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
        /** @var array<int, mixed> $params */
        $params = [];

        // Language filter
        if (!empty($filters['langId'])) {
            $conditions[] = 'WoLgID = ?';
            $params[] = (int)$filters['langId'];
        }

        // Status filter
        if (!empty($filters['status'])) {
            $conditions[] = StatusHelper::makeCondition('WoStatus', (int)$filters['status']);
        }

        // Query filter - builds LIKE/REGEXP condition with placeholders
        if (!empty($filters['query'])) {
            $regexMode = (string)($filters['regexMode'] ?? '');
            $queryMode = (string)($filters['queryMode'] ?? 'term,rom,transl');
            $queryValue = ($regexMode == '') ?
                str_replace("*", "%", mb_strtolower((string)$filters['query'], 'UTF-8')) :
                (string)$filters['query'];

            $queryCondition = $this->buildQueryConditionWithPlaceholders($queryMode, $regexMode, $params, $queryValue);
            $conditions[] = $queryCondition;
        }

        // Tag filter (uses integer values, already safe via cast)
        $whTag = '';
        if (!empty($filters['tag1']) || !empty($filters['tag2'])) {
            $whTag = $this->buildTagCondition(
                (string)($filters['tag1'] ?? ''),
                (string)($filters['tag2'] ?? ''),
                (string)($filters['tag12'] ?? '')
            );
        }

        $whereClause = implode(' AND ', $conditions);

        // Build SQL
        if (empty($filters['textId'])) {
            // words has WoUsID - user scope auto-applied
            // word_tag_map inherits user context via WtWoID -> words FK
            $sql = "SELECT DISTINCT WoID FROM (words LEFT JOIN word_tag_map ON WoID = WtWoID)
            WHERE {$whereClause}
            GROUP BY WoID {$whTag}"
            . UserScopedQuery::forTablePrepared('words', $params, 'words');
        } else {
            $params[] = (int)$filters['textId'];
            // word_occurrences inherits user context via Ti2TxID -> texts FK
            $sql = "SELECT DISTINCT WoID FROM (words LEFT JOIN word_tag_map ON WoID = WtWoID), word_occurrences
            WHERE Ti2LgID = WoLgID AND Ti2WoID = WoID AND Ti2TxID = ?
            AND {$whereClause}
            GROUP BY WoID {$whTag}"
            . UserScopedQuery::forTablePrepared('words', $params, 'words');
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
     * @param string            $queryMode  Query mode
     * @param string            $regexMode  Regex mode (empty for LIKE, 'REGEXP' for regex)
     * @param array<int, mixed> &$params    Reference to params array to add values
     * @param string            $queryValue The query value to search for
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
        } elseif ($whTag1 !== null) {
            // At this point $whTag2 is also non-null (Psalm flow analysis)
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
        $scoreUpdate = TermStatusService::makeScoreRandomInsertUpdate('u');
        $bindings = [$status, $wordId];
        $sql = "UPDATE words SET WoStatus = ?, WoStatusChanged = NOW(), {$scoreUpdate} WHERE WoID = ?"
            . UserScopedQuery::forTablePrepared('words', $bindings);
        Connection::preparedExecute($sql, $bindings);
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
        $bindings = [$wordId];
        $record = Connection::preparedFetchOne(
            "SELECT WoText, WoTranslation, WoRomanization
             FROM words WHERE WoID = ?"
             . UserScopedQuery::forTablePrepared('words', $bindings),
            $bindings
        );

        if ($record === null) {
            return null;
        }

        return [
            'text' => (string) $record['WoText'],
            'translation' => ExportService::replaceTabNewline((string)$record['WoTranslation']),
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
        // word_occurrences inherits user context via Ti2TxID -> texts FK
        /** @var string|null $word */
        $word = Connection::preparedFetchValue(
            "SELECT Ti2Text
             FROM word_occurrences
             WHERE Ti2WordCount = 1 AND Ti2TxID = ? AND Ti2Order = ?",
            [$textId, $ord],
            'Ti2Text'
        );
        return $word;
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

        $scoreColumns = TermStatusService::makeScoreRandomInsertUpdate('iv');
        $scoreValues = TermStatusService::makeScoreRandomInsertUpdate('id');

        $bindings = [$langId, $term, $termlc, $status];
        $sql = "INSERT INTO words (
                WoLgID, WoText, WoTextLC, WoStatus, WoWordCount, WoStatusChanged, {$scoreColumns}"
                . UserScopedQuery::insertColumn('words')
            . ") VALUES (?, ?, ?, ?, 1, NOW(), {$scoreValues}"
                . UserScopedQuery::insertValuePrepared('words', $bindings)
            . ")";

        $wid = (int) Connection::preparedInsert($sql, $bindings);

        // Link to text items (word_occurrences inherits user context via Ti2TxID -> texts FK)
        Connection::preparedExecute(
            "UPDATE word_occurrences SET Ti2WoID = ?
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
        $bindings = [$wordId];
        /** @var string|null $term */
        $term = Connection::preparedFetchValue(
            "SELECT WoText FROM words WHERE WoID = ?"
            . UserScopedQuery::forTablePrepared('words', $bindings),
            $bindings,
            'WoText'
        );
        return $term;
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

        $bindings = [ExportService::replaceTabNewline($value), $wordId];
        $sql = "UPDATE words SET WoTranslation = ? WHERE WoID = ?"
            . UserScopedQuery::forTablePrepared('words', $bindings);
        Connection::preparedExecute($sql, $bindings);

        $bindings = [$wordId];
        return (string) Connection::preparedFetchValue(
            "SELECT WoTranslation FROM words WHERE WoID = ?"
            . UserScopedQuery::forTablePrepared('words', $bindings),
            $bindings,
            'WoTranslation'
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

        $bindings = [ExportService::replaceTabNewline($value), $wordId];
        $sql = "UPDATE words SET WoRomanization = ? WHERE WoID = ?"
            . UserScopedQuery::forTablePrepared('words', $bindings);
        Connection::preparedExecute($sql, $bindings);

        $bindings = [$wordId];
        /** @var string|null $result */
        $result = Connection::preparedFetchValue(
            "SELECT WoRomanization FROM words WHERE WoID = ?"
            . UserScopedQuery::forTablePrepared('words', $bindings),
            $bindings,
            'WoRomanization'
        );

        return ($result === '' || $result === null) ? '*' : $result;
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

        \Lwt\Shared\Infrastructure\Database\Maintenance::adjustAutoIncrement('words', 'WoID');

        QueryBuilder::table('word_occurrences')
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
        $bindings = [$wordId];
        $record = Connection::preparedFetchOne(
            "SELECT WoLgID, WoText, WoTranslation, WoSentence, WoNotes, WoRomanization, WoStatus
             FROM words WHERE WoID = ?"
             . UserScopedQuery::forTablePrepared('words', $bindings),
            $bindings
        );

        if ($record === null) {
            return null;
        }

        $translation = ExportService::replaceTabNewline((string)$record['WoTranslation']);
        if ($translation === '*') {
            $translation = '';
        }

        return [
            'langId' => (int) $record['WoLgID'],
            'text' => (string) $record['WoText'],
            'translation' => $translation,
            'sentence' => (string) ($record['WoSentence'] ?? ''),
            'notes' => (string) ($record['WoNotes'] ?? ''),
            'romanization' => (string) ($record['WoRomanization'] ?? ''),
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
        // word_occurrences inherits user context via Ti2TxID -> texts FK
        // words has WoUsID - user scope auto-applied
        $bindings = [$textId];
        return Connection::preparedFetchAll(
            "SELECT DISTINCT Ti2Text, LOWER(Ti2Text) AS Ti2TextLC
             FROM (word_occurrences LEFT JOIN words ON LOWER(Ti2Text) = WoTextLC AND Ti2LgID = WoLgID)
             WHERE WoID IS NULL AND Ti2WordCount = 1 AND Ti2TxID = ?
             ORDER BY Ti2Order"
             . UserScopedQuery::forTablePrepared('words', $bindings, 'words'),
            $bindings
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
        $bindings = [$termlc];
        $wid = Connection::preparedFetchValue(
            "SELECT WoID FROM words WHERE WoTextLC = ?"
            . UserScopedQuery::forTablePrepared('words', $bindings),
            $bindings,
            'WoID'
        );

        if ($wid !== null) {
            return [0, null];
        }

        try {
            $scoreColumns = TermStatusService::makeScoreRandomInsertUpdate('iv');
            $scoreValues = TermStatusService::makeScoreRandomInsertUpdate('id');

            /** @var list<int|string> $bindings */
            $bindings = [$langId, $term, $termlc, $status];
            $sql = "INSERT INTO words (
                    WoLgID, WoText, WoTextLC, WoStatus, WoStatusChanged, {$scoreColumns}"
                    . UserScopedQuery::insertColumn('words')
                . ") VALUES (?, ?, ?, ?, NOW(), {$scoreValues}"
                    . UserScopedQuery::insertValuePrepared('words', $bindings)
                . ")";

            $stmt = Connection::prepare($sql);
            $stmt->bindValues($bindings);
            $rows = $stmt->execute();
            $wid = (int) $stmt->insertId();

            if ($rows == 0) {
                \Lwt\Shared\UI\Helpers\PageLayoutHelper::renderMessage(
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
            throw new \RuntimeException("Could not modify words: " . $e->getMessage(), 0, $e);
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

        $bindings = [$textId];
        $langId = (int) Connection::preparedFetchValue(
            "SELECT TxLgID FROM texts WHERE TxID = ?"
            . UserScopedQuery::forTablePrepared('texts', $bindings),
            $bindings,
            'TxLgID'
        );

        $scoreColumns = TermStatusService::makeScoreRandomInsertUpdate('iv');
        $scoreValues = TermStatusService::makeScoreRandomInsertUpdate('id');

        $bindings = [$langId, $wordlc, $text, $status, $translation];
        $sql = "INSERT INTO words (
                WoLgID, WoTextLC, WoText, WoStatus, WoTranslation, WoSentence,
                WoRomanization, WoStatusChanged, {$scoreColumns}"
                . UserScopedQuery::insertColumn('words')
            . ") VALUES (?, ?, ?, ?, ?, '', '', NOW(), {$scoreValues}"
                . UserScopedQuery::insertValuePrepared('words', $bindings)
            . ")";

        $wid = (int) Connection::preparedInsert($sql, $bindings);

        // word_occurrences inherits user context via Ti2TxID -> texts FK
        Connection::preparedExecute(
            "UPDATE word_occurrences SET Ti2WoID = ?
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
        $bindings = [$textId];
        /** @var int|null $langId */
        $langId = Connection::preparedFetchValue(
            "SELECT TxLgID FROM texts WHERE TxID = ?"
            . UserScopedQuery::forTablePrepared('texts', $bindings),
            $bindings,
            'TxLgID'
        );
        $wordsData = [];
        $count = 0;
        $records = $this->getAllUnknownWordsInText($textId);
        foreach ($records as $record) {
            list($modified_rows, $wordData) = $this->processWordForWellKnown(
                $status,
                (string)$record['Ti2Text'],
                (string)$record['Ti2TextLC'],
                $langId ?? 0
            );
            if ($wordData !== null) {
                $wordsData[] = $wordData;
            }
            $count += $modified_rows;
        }

        // Associate existing textitems
        // words has WoUsID - user scope auto-applied
        // word_occurrences inherits user context via Ti2TxID -> texts FK
        Connection::execute(
            "UPDATE words
            JOIN word_occurrences
            ON Ti2WoID IS NULL AND LOWER(Ti2Text) = WoTextLC AND Ti2LgID = WoLgID
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
     * @param array<int, array{lg: int, text: string, status: int, trans?: string}> $terms Array of term data
     *
     * @return int The max word ID before insertion (for finding new words)
     */
    public function bulkSaveTerms(array $terms): int
    {
        $bindings = [];
        $max = (int) Connection::fetchValue(
            "SELECT COALESCE(MAX(WoID), 0) AS max_id FROM words"
            . UserScopedQuery::forTablePrepared('words', $bindings),
            'max_id'
        );

        if (empty($terms)) {
            return $max;
        }

        $scoreColumns = TermStatusService::makeScoreRandomInsertUpdate('iv');
        $scoreValues = TermStatusService::makeScoreRandomInsertUpdate('id');

        // Insert each term using prepared statements for safety
        foreach ($terms as $row) {
            $trans = (!isset($row['trans']) || $row['trans'] == '') ? '*' : $row['trans'];
            $textlc = mb_strtolower($row['text'], 'UTF-8');

            $bindings = [$row['lg'], $textlc, $row['text'], $row['status'], $trans];
            $sql = "INSERT INTO words (
                    WoLgID, WoTextLC, WoText, WoStatus, WoTranslation, WoSentence,
                    WoRomanization, WoStatusChanged, {$scoreColumns}"
                    . UserScopedQuery::insertColumn('words')
                . ") VALUES (?, ?, ?, ?, ?, '', '', NOW(), {$scoreValues}"
                    . UserScopedQuery::insertValuePrepared('words', $bindings)
                . ")";

            Connection::preparedExecute($sql, $bindings);
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
        $bindings = [$maxWoId];
        return Connection::preparedFetchAll(
            "SELECT WoID, WoTextLC, WoStatus, WoTranslation
             FROM words
             WHERE WoID > ?"
             . UserScopedQuery::forTablePrepared('words', $bindings),
            $bindings
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
        // word_occurrences inherits user context via Ti2TxID -> texts FK
        // words has WoUsID - user scope auto-applied
        Connection::preparedExecute(
            "UPDATE word_occurrences
             JOIN words
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
        // languages has LgUsID - user scope auto-applied
        // texts has TxUsID - user scope auto-applied
        $bindings = [$textId];
        $record = Connection::preparedFetchOne(
            "SELECT LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI
             FROM languages, texts
             WHERE LgID = TxLgID AND TxID = ?"
             . UserScopedQuery::forTablePrepared('languages', $bindings, 'languages')
             . UserScopedQuery::forTablePrepared('texts', $bindings, 'texts'),
            $bindings
        );

        return [
            'name' => (string) ($record['LgName'] ?? ''),
            'dict1' => (string) ($record['LgDict1URI'] ?? ''),
            'dict2' => (string) ($record['LgDict2URI'] ?? ''),
            'translate' => (string) ($record['LgGoogleTranslateURI'] ?? ''),
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
        // word_occurrences inherits user context via Ti2TxID -> texts FK
        return Connection::preparedFetchAll(
            "SELECT Ti2Text AS word, Ti2LgID, MIN(Ti2Order) AS pos
             FROM word_occurrences
             WHERE Ti2WoID IS NULL AND Ti2TxID = ? AND Ti2WordCount = 1
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
     *                    - notes: Personal notes
     *                    - roman: Romanization/phonetic
     *                    - wordcount: Number of words in expression
     *
     * @return array{id: int, message: string}
     */
    public function createMultiWord(array $data): array
    {
        $scoreColumns = TermStatusService::makeScoreRandomInsertUpdate('iv');
        $scoreValues = TermStatusService::makeScoreRandomInsertUpdate('id');

        $sentence = ExportService::replaceTabNewline((string)$data['sentence']);
        $notes = ExportService::replaceTabNewline((string)($data['notes'] ?? ''));

        $bindings = [
            (int) $data['lgid'],
            (string) $data['textlc'],
            (string) $data['text'],
            (int) $data['status'],
            (string) $data['translation'],
            $sentence,
            $notes,
            (string) $data['roman'],
            (int) $data['wordcount']
        ];

        $sql = "INSERT INTO words (
                WoLgID, WoTextLC, WoText, WoStatus, WoTranslation, WoSentence,
                WoNotes, WoRomanization, WoWordCount, WoStatusChanged, {$scoreColumns}"
                . UserScopedQuery::insertColumn('words')
            . ") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), {$scoreValues}"
                . UserScopedQuery::insertValuePrepared('words', $bindings)
            . ")";

        $wid = (int) Connection::preparedInsert($sql, $bindings);

        \Lwt\Shared\Infrastructure\Database\Maintenance::initWordCount();
        TagsFacade::saveWordTagsFromForm($wid);
        $this->expressionService->insertExpressions(
            (string) $data['textlc'],
            (int) $data['lgid'],
            $wid,
            (int) $data['wordcount'],
            0
        );

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
        $scoreUpdate = TermStatusService::makeScoreRandomInsertUpdate('u');
        $sentence = ExportService::replaceTabNewline((string)$data['sentence']);
        $notes = ExportService::replaceTabNewline((string)($data['notes'] ?? ''));

        if ($oldStatus != $newStatus) {
            // Status changed - update status and timestamp
            $bindings = [
                (string)$data['text'],
                (string)$data['translation'],
                $sentence,
                $notes,
                (string)$data['roman'],
                $newStatus,
                $wordId
            ];
            $sql = "UPDATE words SET
                    WoText = ?, WoTranslation = ?, WoSentence = ?, WoNotes = ?, WoRomanization = ?,
                    WoStatus = ?, WoStatusChanged = NOW(), {$scoreUpdate}
                    WHERE WoID = ?"
                    . UserScopedQuery::forTablePrepared('words', $bindings);
            Connection::preparedExecute($sql, $bindings);
        } else {
            // Status unchanged
            $bindings = [
                (string)$data['text'],
                (string)$data['translation'],
                $sentence,
                $notes,
                (string)$data['roman'],
                $wordId
            ];
            $sql = "UPDATE words SET
                    WoText = ?, WoTranslation = ?, WoSentence = ?, WoNotes = ?, WoRomanization = ?, {$scoreUpdate}
                    WHERE WoID = ?"
                    . UserScopedQuery::forTablePrepared('words', $bindings);
            Connection::preparedExecute($sql, $bindings);
        }

        TagsFacade::saveWordTagsFromForm($wordId);

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
        $bindings = [$wordId];
        $record = Connection::preparedFetchOne(
            "SELECT WoText, WoLgID, WoTranslation, WoSentence, WoNotes, WoRomanization, WoStatus
             FROM words WHERE WoID = ?"
             . UserScopedQuery::forTablePrepared('words', $bindings),
            $bindings
        );

        if ($record === null) {
            return null;
        }

        return [
            'text' => (string) $record['WoText'],
            'lgid' => (int) $record['WoLgID'],
            'translation' => ExportService::replaceTabNewline((string)$record['WoTranslation']),
            'sentence' => ExportService::replaceTabNewline((string)($record['WoSentence'] ?? '')),
            'notes' => ExportService::replaceTabNewline((string)($record['WoNotes'] ?? '')),
            'romanization' => (string) ($record['WoRomanization'] ?? ''),
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
        $bindings = [$textId];
        /** @var int|null $lgid */
        $lgid = Connection::preparedFetchValue(
            "SELECT TxLgID FROM texts WHERE TxID = ?"
            . UserScopedQuery::forTablePrepared('texts', $bindings),
            $bindings,
            'TxLgID'
        );
        return $lgid;
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
        // word_occurrences inherits user context via Ti2TxID -> texts FK
        /** @var int|null $seid */
        $seid = Connection::preparedFetchValue(
            "SELECT Ti2SeID
             FROM word_occurrences
             WHERE Ti2TxID = ? AND Ti2Order = ?",
            [$textId, $ord],
            'Ti2SeID'
        );
        return $seid;
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
        // Use SentenceService to get properly formatted sentence text
        // This handles cases where texts weren't properly split into sentences
        // by finding sentence boundaries around the target position
        return $this->sentenceService->getSentenceAtPosition($textId, $ord);
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
        // languages has LgUsID - user scope auto-applied
        // texts has TxUsID - user scope auto-applied
        $bindings = [$textId];
        return (bool) Connection::preparedFetchValue(
            "SELECT LgShowRomanization
             FROM languages JOIN texts ON TxLgID = LgID
             WHERE TxID = ?"
             . UserScopedQuery::forTablePrepared('languages', $bindings, 'languages')
             . UserScopedQuery::forTablePrepared('texts', $bindings, 'texts'),
            $bindings,
            'LgShowRomanization'
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
        $bindings = [$langId, $textlc];
        /** @var int|null $wid */
        $wid = Connection::preparedFetchValue(
            "SELECT WoID FROM words
             WHERE WoLgID = ? AND WoTextLC = ?"
             . UserScopedQuery::forTablePrepared('words', $bindings),
            $bindings,
            'WoID'
        );
        return $wid;
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
