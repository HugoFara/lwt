<?php declare(strict_types=1);
namespace Lwt\Api\V1\Handlers;

use Lwt\Core\Globals;
use Lwt\Core\StringUtils;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\Escaping;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;
use Lwt\Services\WordService;
use Lwt\Modules\Vocabulary\Application\Services\TermStatusService;
use Lwt\View\Helper\StatusHelper;

require_once __DIR__ . '/../../../Services/WordService.php';

/**
 * Handler for term/word-related API operations.
 *
 * Extracted from api_v1.php lines 27-258.
 */
class TermHandler
{
    private WordService $wordService;

    public function __construct()
    {
        $this->wordService = new WordService();
    }
    /**
     * Add the translation for a new term.
     *
     * @param string $text Associated text
     * @param int    $lang Language ID
     * @param string $data Translation
     *
     * @return array{0: int|string, 1: string}|string [new word ID, lowercase $text] if success, error message otherwise
     */
    public function addNewTermTranslation(string $text, int $lang, string $data): array|string
    {
        $textlc = mb_strtolower($text, 'UTF-8');

        // Insert new word using prepared statement
        $scoreColumns = TermStatusService::makeScoreRandomInsertUpdate('iv');
        $scoreValues = TermStatusService::makeScoreRandomInsertUpdate('id');

        // Use raw SQL for complex INSERT with dynamic columns
        $bindings = [$lang, $textlc, $text, $data, '', ''];
        $sql = "INSERT INTO words (
                WoLgID, WoTextLC, WoText, WoStatus, WoTranslation,
                WoSentence, WoRomanization, WoStatusChanged,
                {$scoreColumns}
            ) VALUES(?, ?, ?, 1, ?, ?, ?, NOW(), {$scoreValues})"
            . UserScopedQuery::forTablePrepared('words', $bindings);

        $stmt = Connection::prepare($sql);
        $stmt->bind('isssss', $lang, $textlc, $text, $data, '', '');
        $affected = $stmt->execute();

        if ($affected != 1) {
            return "Error: $affected rows affected, expected 1!";
        }

        $wid = $stmt->insertId();

        // Update text items using prepared statement
        // textitems2 inherits user context via Ti2TxID -> texts FK
        Connection::preparedExecute(
            "UPDATE textitems2
            SET Ti2WoID = ?
            WHERE Ti2LgID = ? AND LOWER(Ti2Text) = ?",
            [$wid, $lang, $textlc]
        );

        return array($wid, $textlc);
    }

    /**
     * Edit the translation for an existing term.
     *
     * @param int    $wid       Word ID
     * @param string $newTrans New translation
     *
     * @return string WoTextLC, lowercase version of the word
     */
    public function editTermTranslation(int $wid, string $newTrans): string
    {
        $oldtrans = (string) QueryBuilder::table('words')
            ->select(['WoTranslation'])
            ->where('WoID', '=', $wid)
            ->valuePrepared('WoTranslation');

        $oldtransarr = preg_split('/[' . StringUtils::getSeparators() . ']/u', $oldtrans);
        if ($oldtransarr === false) {
            return (string) QueryBuilder::table('words')
                ->select(['WoTextLC'])
                ->where('WoID', '=', $wid)
                ->valuePrepared('WoTextLC');
        }
        $oldtransarr = array_map('trim', $oldtransarr);

        if (!in_array($newTrans, $oldtransarr)) {
            if (trim($oldtrans) == '' || trim($oldtrans) == '*') {
                $oldtrans = $newTrans;
            } else {
                $oldtrans .= ' ' . StringUtils::getFirstSeparator() . ' ' . $newTrans;
            }
            QueryBuilder::table('words')
                ->where('WoID', '=', $wid)
                ->updatePrepared(['WoTranslation' => $oldtrans]);
        }

        return (string) QueryBuilder::table('words')
            ->select(['WoTextLC'])
            ->where('WoID', '=', $wid)
            ->valuePrepared('WoTextLC');
    }

    /**
     * Edit term translation if it exists.
     *
     * @param int    $wid       Word ID
     * @param string $newTrans New translation
     *
     * @return string Term in lower case, or error message if term does not exist
     */
    public function checkUpdateTranslation(int $wid, string $newTrans): string
    {
        $cntWords = QueryBuilder::table('words')
            ->where('WoID', '=', $wid)
            ->countPrepared();

        if ($cntWords == 1) {
            return $this->editTermTranslation($wid, $newTrans);
        }
        return "Error: " . $cntWords . " word ID found!";
    }

    /**
     * Force a term to get a new status.
     *
     * @param int $wid    ID of the word to edit
     * @param int $status New status to set
     *
     * @return int Number of affected rows
     */
    public function setWordStatus(int $wid, int $status): int
    {
        $scoreUpdate = TermStatusService::makeScoreRandomInsertUpdate('u');

        // Use raw SQL for dynamic score update
        $bindings = [$status, $wid];
        return Connection::preparedExecute(
            "UPDATE words
            SET WoStatus = ?, WoStatusChanged = NOW(), {$scoreUpdate}
            WHERE WoID = ?"
            . UserScopedQuery::forTablePrepared('words', $bindings),
            [$status, $wid]
        );
    }

    /**
     * Check the consistency of the new status.
     *
     * @param int  $oldstatus Old status
     * @param bool $up        True if status should incremented, false if decrementation needed
     *
     * @return int New status in the good number range (1-5, 98, or 99)
     */
    public function getNewStatus(int $oldstatus, bool $up): int
    {
        $currstatus = $oldstatus;
        if ($up) {
            $currstatus++;
            if ($currstatus == 99) {
                $currstatus = 1;
            } elseif ($currstatus == 6) {
                $currstatus = 99;
            }
        } else {
            $currstatus--;
            if ($currstatus == 98) {
                $currstatus = 5;
            } elseif ($currstatus == 0) {
                $currstatus = 98;
            }
        }
        return $currstatus;
    }

    /**
     * Save the new word status to the database, return the controls.
     *
     * @param int $wid        Word ID
     * @param int $currstatus Current status in the good value range.
     *
     * @return string|null HTML-formatted string with plus/minus controls if a success.
     */
    public function updateWordStatus(int $wid, int $currstatus): ?string
    {
        if (($currstatus >= 1 && $currstatus <= 5) || $currstatus == 99 || $currstatus == 98) {
            $m1 = $this->setWordStatus($wid, $currstatus);
            if ($m1 == 1) {
                $currstatus = QueryBuilder::table('words')
                    ->select(['WoStatus'])
                    ->where('WoID', '=', $wid)
                    ->valuePrepared('WoStatus');
                if (!isset($currstatus)) {
                    return null;
                }
                $statusAbbr = StatusHelper::getAbbr((int)$currstatus);
                return StatusHelper::buildTestTableControls(1, (int)$currstatus, $wid, $statusAbbr);
            }
        }
        return null;
    }

    /**
     * Do a word status change.
     *
     * @param int  $wid Word ID
     * @param bool $up  Should the status be incremeted or decremented
     *
     * @return string HTML-formatted string for increments
     */
    public function incrementTermStatus(int $wid, bool $up): string
    {
        $tempstatus = QueryBuilder::table('words')
            ->select(['WoStatus'])
            ->where('WoID', '=', $wid)
            ->valuePrepared('WoStatus');

        if (!isset($tempstatus)) {
            return '';
        }

        $currstatus = $this->getNewStatus((int)$tempstatus, $up);
        $formatted = $this->updateWordStatus($wid, $currstatus);

        if ($formatted === null) {
            return '';
        }
        return $formatted;
    }

    // =========================================================================
    // API Response Formatters
    // =========================================================================

    /**
     * Format response for updating translation.
     *
     * @param int    $termId     Term ID
     * @param string $translation New translation
     *
     * @return array{update?: string, error?: string}
     */
    public function formatUpdateTranslation(int $termId, string $translation): array
    {
        $result = $this->checkUpdateTranslation($termId, trim($translation));
        if (str_starts_with($result, "Error")) {
            return ["error" => $result];
        }
        return ["update" => $result];
    }

    /**
     * Format response for adding translation.
     *
     * @param string $termText    Term text
     * @param int    $lgId        Language ID
     * @param string $translation Translation
     *
     * @return array{error?: string, add?: string, term_id?: int|string, term_lc?: string}
     */
    public function formatAddTranslation(string $termText, int $lgId, string $translation): array
    {
        $text = trim($termText);
        $result = $this->addNewTermTranslation($text, $lgId, trim($translation));

        if (is_array($result)) {
            return [
                "term_id" => $result[0],
                "term_lc" => $result[1]
            ];
        } elseif ($result == mb_strtolower($text, 'UTF-8')) {
            return ["add" => $result];
        }
        return ["error" => $result];
    }

    /**
     * Format response for incrementing term status.
     *
     * @param int  $termId   Term ID
     * @param bool $statusUp Whether to increment (true) or decrement (false)
     *
     * @return array{increment?: string, error?: string}
     */
    public function formatIncrementStatus(int $termId, bool $statusUp): array
    {
        $result = $this->incrementTermStatus($termId, $statusUp);
        if ($result == '') {
            return ["error" => ''];
        }
        return ["increment" => $result];
    }

    /**
     * Format response for setting term status.
     *
     * @param int $termId Term ID
     * @param int $status New status
     *
     * @return array{set: int}
     */
    public function formatSetStatus(int $termId, int $status): array
    {
        $result = $this->setWordStatus($termId, $status);
        return ["set" => $result];
    }

    // =========================================================================
    // New Phase 2 Methods
    // =========================================================================

    /**
     * Delete a term by ID.
     *
     * @param int $termId Term ID to delete
     *
     * @return array{deleted: bool, error?: string}
     */
    public function deleteTerm(int $termId): array
    {
        // Check if term exists
        $exists = QueryBuilder::table('words')
            ->where('WoID', '=', $termId)
            ->countPrepared();

        if ($exists === 0) {
            return ['deleted' => false, 'error' => 'Term not found'];
        }

        // Get word count to determine if multi-word
        $wordCount = (int) QueryBuilder::table('words')
            ->select(['WoWordCount'])
            ->where('WoID', '=', $termId)
            ->valuePrepared('WoWordCount');

        if ($wordCount > 1) {
            $this->wordService->deleteMultiWord($termId);
        } else {
            $this->wordService->delete($termId);
        }

        return ['deleted' => true];
    }

    /**
     * Create a term quickly with wellknown (99) or ignored (98) status.
     *
     * @param int $textId   Text ID containing the word
     * @param int $ord      Word position (order) in text
     * @param int $status   Status to set (98 for ignored, 99 for well-known)
     *
     * @return array{term_id?: int, term?: string, term_lc?: string, hex?: string, error?: string}
     */
    public function createQuickTerm(int $textId, int $ord, int $status): array
    {
        // Validate status
        if ($status !== 98 && $status !== 99) {
            return ['error' => 'Status must be 98 (ignored) or 99 (well-known)'];
        }

        // Get the word at the position
        $term = $this->wordService->getWordAtPosition($textId, $ord);
        if ($term === null) {
            return ['error' => 'Word not found at position'];
        }

        try {
            $result = $this->wordService->insertWordWithStatus($textId, $term, $status);
            return [
                'term_id' => $result['id'],
                'term' => $result['term'],
                'term_lc' => $result['termlc'],
                'hex' => $result['hex']
            ];
        } catch (\RuntimeException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Update status for multiple terms.
     *
     * @param int[] $termIds Array of term IDs
     * @param int   $status  New status (1-5, 98, 99)
     *
     * @return array{count: int, error?: string}
     */
    public function bulkUpdateStatus(array $termIds, int $status): array
    {
        // Validate status
        if (!in_array($status, [1, 2, 3, 4, 5, 98, 99])) {
            return ['count' => 0, 'error' => 'Invalid status value'];
        }

        $count = $this->wordService->updateStatusMultiple($termIds, $status);
        return ['count' => $count];
    }

    /**
     * Get a term by ID.
     *
     * @param int $termId Term ID
     *
     * @return array{id: int, text: string, textLc: string, translation: string, romanization: string, status: int, langId: int}|array{error: string}
     */
    public function getTerm(int $termId): array
    {
        $record = QueryBuilder::table('words')
            ->select(['WoID', 'WoText', 'WoTextLC', 'WoTranslation', 'WoRomanization', 'WoStatus', 'WoLgID'])
            ->where('WoID', '=', $termId)
            ->firstPrepared();

        if ($record === null) {
            return ['error' => 'Term not found'];
        }

        return [
            'id' => (int)$record['WoID'],
            'text' => (string)$record['WoText'],
            'textLc' => (string)$record['WoTextLC'],
            'translation' => (string)$record['WoTranslation'],
            'romanization' => (string)$record['WoRomanization'],
            'status' => (int)$record['WoStatus'],
            'langId' => (int)$record['WoLgID']
        ];
    }

    // =========================================================================
    // New API Response Formatters
    // =========================================================================

    /**
     * Format response for deleting a term.
     *
     * @param int $termId Term ID
     *
     * @return array{deleted: bool, error?: string}
     */
    public function formatDeleteTerm(int $termId): array
    {
        return $this->deleteTerm($termId);
    }

    /**
     * Format response for quick term creation.
     *
     * @param int $textId   Text ID
     * @param int $position Word position in text
     * @param int $status   Status (98 or 99)
     *
     * @return array{term_id?: int, term?: string, term_lc?: string, hex?: string, error?: string}
     */
    public function formatQuickCreate(int $textId, int $position, int $status): array
    {
        return $this->createQuickTerm($textId, $position, $status);
    }

    /**
     * Format response for bulk status update.
     *
     * @param int[] $termIds Term IDs
     * @param int   $status  New status
     *
     * @return array{count: int, error?: string}
     */
    public function formatBulkStatus(array $termIds, int $status): array
    {
        return $this->bulkUpdateStatus($termIds, $status);
    }

    /**
     * Format response for getting a term.
     *
     * @param int $termId Term ID
     *
     * @return array
     */
    public function formatGetTerm(int $termId): array
    {
        return $this->getTerm($termId);
    }

    /**
     * Get detailed term information including sentence and tags.
     *
     * @param int         $termId Term ID
     * @param string|null $ann    Annotation to highlight in translation
     *
     * @return array{id: int, text: string, textLc: string, translation: string, romanization: string, status: int, langId: int, sentence: string, notes: string, tags: array<string>, statusLabel: string}|array{error: string}
     */
    public function getTermDetails(int $termId, ?string $ann = null): array
    {
        $record = QueryBuilder::table('words')
            ->select(['WoID', 'WoText', 'WoTextLC', 'WoTranslation', 'WoRomanization', 'WoStatus', 'WoLgID', 'WoSentence', 'WoNotes'])
            ->where('WoID', '=', $termId)
            ->firstPrepared();

        if ($record === null) {
            return ['error' => 'Term not found'];
        }

        // Get tags for the word - using JOIN with user-scoped tables
        $tagsResult = QueryBuilder::table('wordtags')
            ->select(['tags.TgText'])
            ->join('tags', 'tags.TgID', '=', 'wordtags.WtTgID')
            ->where('wordtags.WtWoID', '=', $termId)
            ->orderBy('tags.TgText')
            ->getPrepared();
        $tags = array_map(fn($row) => (string)$row['TgText'], $tagsResult);

        // Process translation - highlight annotation if provided
        $translation = (string)$record['WoTranslation'];
        if ($ann !== null && $ann !== '' && $translation !== '' && $translation !== '*') {
            $translation = str_replace($ann, '<b>' . $ann . '</b>', $translation);
        }

        return [
            'id' => (int)$record['WoID'],
            'text' => (string)$record['WoText'],
            'textLc' => (string)$record['WoTextLC'],
            'translation' => $translation,
            'romanization' => (string)$record['WoRomanization'],
            'status' => (int)$record['WoStatus'],
            'langId' => (int)$record['WoLgID'],
            'sentence' => (string)$record['WoSentence'],
            'notes' => (string)($record['WoNotes'] ?? ''),
            'tags' => $tags,
            'statusLabel' => TermStatusService::getStatusName((int)$record['WoStatus'])
        ];
    }

    /**
     * Format response for getting term details.
     *
     * @param int         $termId Term ID
     * @param string|null $ann    Optional annotation to highlight
     *
     * @return array
     */
    public function formatGetTermDetails(int $termId, ?string $ann = null): array
    {
        return $this->getTermDetails($termId, $ann);
    }

    // =========================================================================
    // Multi-word Expression Methods
    // =========================================================================

    /**
     * Get multi-word expression data for editing.
     *
     * @param int         $textId   Text ID
     * @param int         $position Position in text
     * @param string|null $text     Multi-word text (for new expressions)
     * @param int|null    $wordId   Word ID (for existing expressions)
     *
     * @return array Multi-word data or error
     */
    public function getMultiWordForEdit(int $textId, int $position, ?string $text = null, ?int $wordId = null): array
    {
        // Get language ID from text
        $lgid = $this->wordService->getLanguageIdFromText($textId);
        if ($lgid === null) {
            return ['error' => 'Text not found'];
        }

        // If word ID provided, get existing multi-word
        if ($wordId !== null && $wordId > 0) {
            $data = $this->wordService->getMultiWordData($wordId);
            if ($data === null) {
                return ['error' => 'Multi-word expression not found'];
            }

            // Get word count
            $wordCount = (int) QueryBuilder::table('words')
                ->select(['WoWordCount'])
                ->where('WoID', '=', $wordId)
                ->valuePrepared('WoWordCount');

            return [
                'id' => $wordId,
                'text' => $data['text'],
                'textLc' => mb_strtolower($data['text'], 'UTF-8'),
                'translation' => $data['translation'],
                'romanization' => $data['romanization'],
                'sentence' => $data['sentence'],
                'notes' => $data['notes'] ?? '',
                'status' => $data['status'],
                'langId' => $data['lgid'],
                'wordCount' => $wordCount,
                'isNew' => false
            ];
        }

        // Check if text is provided
        if ($text === null || $text === '') {
            return ['error' => 'Multi-word text is required for new expressions'];
        }

        // Try to find existing term by text (case-insensitive)
        $textLc = mb_strtolower($text, 'UTF-8');
        $existingWord = QueryBuilder::table('words')
            ->select(['WoID', 'WoText', 'WoTranslation', 'WoRomanization', 'WoSentence', 'WoStatus', 'WoWordCount'])
            ->where('WoTextLC', '=', $textLc)
            ->where('WoLgID', '=', $lgid)
            ->where('WoWordCount', '>', 1)
            ->firstPrepared();

        if ($existingWord) {
            // Found existing multi-word term
            return [
                'id' => (int) $existingWord['WoID'],
                'text' => $existingWord['WoText'],
                'textLc' => $textLc,
                'translation' => $existingWord['WoTranslation'] ?? '',
                'romanization' => $existingWord['WoRomanization'] ?? '',
                'sentence' => $existingWord['WoSentence'] ?? '',
                'notes' => '',
                'status' => (int) $existingWord['WoStatus'],
                'langId' => $lgid,
                'wordCount' => (int) $existingWord['WoWordCount'],
                'isNew' => false
            ];
        }

        // New multi-word expression
        // Get sentence at position
        $sentence = $this->wordService->getSentenceTextAtPosition($textId, $position);

        // Count words in the text
        $wordCount = count(preg_split('/\s+/', trim($text)) ?: []);

        return [
            'id' => null,
            'text' => $text,
            'textLc' => $textLc,
            'translation' => '',
            'romanization' => '',
            'sentence' => $sentence ?? '',
            'notes' => '',
            'status' => 1,
            'langId' => $lgid,
            'wordCount' => $wordCount,
            'isNew' => true
        ];
    }

    /**
     * Create a new multi-word expression.
     *
     * @param array $data Multi-word data:
     *                    - textId: Text ID
     *                    - position: Position in text
     *                    - text: Multi-word text
     *                    - wordCount: Number of words
     *                    - translation: Translation
     *                    - romanization: Romanization
     *                    - sentence: Example sentence
     *                    - notes: Notes (optional)
     *                    - status: Status (1-5)
     *
     * @return array{term_id?: int, term_lc?: string, hex?: string, error?: string}
     */
    public function createMultiWordTerm(array $data): array
    {
        $textId = (int) ($data['textId'] ?? 0);
        $text = trim($data['text'] ?? '');

        if ($textId === 0 || $text === '') {
            return ['error' => 'Text ID and multi-word text are required'];
        }

        $lgid = $this->wordService->getLanguageIdFromText($textId);
        if ($lgid === null) {
            return ['error' => 'Text not found'];
        }

        $textLc = mb_strtolower($text, 'UTF-8');
        $wordCount = (int) ($data['wordCount'] ?? count(preg_split('/\s+/', $text) ?: []));

        try {
            $result = $this->wordService->createMultiWord([
                'lgid' => $lgid,
                'text' => $text,
                'textlc' => $textLc,
                'status' => (int) ($data['status'] ?? 1),
                'translation' => $data['translation'] ?? '',
                'sentence' => $data['sentence'] ?? '',
                'notes' => $data['notes'] ?? '',
                'roman' => $data['romanization'] ?? '',
                'wordcount' => $wordCount
            ]);

            return [
                'term_id' => $result['id'],
                'term_lc' => $textLc,
                'hex' => StringUtils::toClassName($textLc)
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Update an existing multi-word expression.
     *
     * @param int   $termId Term ID
     * @param array $data   Multi-word data (translation, romanization, sentence, notes, status)
     *
     * @return array{success?: bool, status?: int, error?: string}
     */
    public function updateMultiWordTerm(int $termId, array $data): array
    {
        $existing = $this->wordService->getMultiWordData($termId);
        if ($existing === null) {
            return ['error' => 'Multi-word expression not found'];
        }

        $oldStatus = $existing['status'];
        $newStatus = (int) ($data['status'] ?? $oldStatus);

        try {
            $this->wordService->updateMultiWord($termId, [
                'text' => $existing['text'], // Don't change text
                'translation' => $data['translation'] ?? $existing['translation'],
                'sentence' => $data['sentence'] ?? $existing['sentence'],
                'notes' => $data['notes'] ?? $existing['notes'] ?? '',
                'roman' => $data['romanization'] ?? $existing['romanization']
            ], $oldStatus, $newStatus);

            return [
                'success' => true,
                'status' => $newStatus
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Format response for getting multi-word data for editing.
     *
     * @param int         $textId   Text ID
     * @param int         $position Position in text
     * @param string|null $text     Multi-word text
     * @param int|null    $wordId   Word ID
     *
     * @return array
     */
    public function formatGetMultiWord(int $textId, int $position, ?string $text = null, ?int $wordId = null): array
    {
        return $this->getMultiWordForEdit($textId, $position, $text, $wordId);
    }

    /**
     * Format response for creating a multi-word expression.
     *
     * @param array $data Multi-word data
     *
     * @return array
     */
    public function formatCreateMultiWord(array $data): array
    {
        return $this->createMultiWordTerm($data);
    }

    /**
     * Format response for updating a multi-word expression.
     *
     * @param int   $termId Term ID
     * @param array $data   Multi-word data
     *
     * @return array
     */
    public function formatUpdateMultiWord(int $termId, array $data): array
    {
        return $this->updateMultiWordTerm($termId, $data);
    }

    // =========================================================================
    // Full Term CRUD for Reactive UI
    // =========================================================================

    /**
     * Get term data prepared for editing in modal.
     *
     * @param int      $textId   Text ID
     * @param int      $position Position in text
     * @param int|null $wordId   Word ID (for existing terms)
     *
     * @return array Term data with language settings and similar terms
     */
    public function getTermForEdit(int $textId, int $position, ?int $wordId = null): array
    {
        // Get language ID and settings from text
        $textData = QueryBuilder::table('texts')
            ->select(['TxLgID', 'TxTitle'])
            ->where('TxID', '=', $textId)
            ->firstPrepared();

        if ($textData === null) {
            return ['error' => 'Text not found'];
        }

        $langId = (int) $textData['TxLgID'];

        // Get language settings
        $langData = QueryBuilder::table('languages')
            ->select(['LgName', 'LgShowRomanization', 'LgGoogleTranslateURI'])
            ->where('LgID', '=', $langId)
            ->firstPrepared();

        if ($langData === null) {
            return ['error' => 'Language not found'];
        }

        // Get all term tags for autocomplete
        $allTags = \Lwt\Modules\Tags\Application\TagsFacade::getAllTermTags();

        // Build language info
        $language = [
            'id' => $langId,
            'name' => (string) $langData['LgName'],
            'showRomanization' => (bool) $langData['LgShowRomanization'],
            'translateUri' => (string) ($langData['LgGoogleTranslateURI'] ?? '')
        ];

        // If word ID provided, get existing term data
        if ($wordId !== null && $wordId > 0) {
            $termData = QueryBuilder::table('words')
                ->select(['WoID', 'WoText', 'WoTextLC', 'WoTranslation', 'WoRomanization', 'WoSentence', 'WoNotes', 'WoStatus', 'WoLgID'])
                ->where('WoID', '=', $wordId)
                ->firstPrepared();

            if ($termData === null) {
                return ['error' => 'Term not found'];
            }

            // Get tags for the word
            $tagsResult = QueryBuilder::table('wordtags')
                ->select(['tags.TgText'])
                ->join('tags', 'tags.TgID', '=', 'wordtags.WtTgID')
                ->where('wordtags.WtWoID', '=', $wordId)
                ->orderBy('tags.TgText')
                ->getPrepared();
            $tags = array_map(fn($row) => (string)$row['TgText'], $tagsResult);

            $term = [
                'id' => (int) $termData['WoID'],
                'text' => (string) $termData['WoText'],
                'textLc' => (string) $termData['WoTextLC'],
                'hex' => StringUtils::toClassName((string) $termData['WoTextLC']),
                'translation' => (string) $termData['WoTranslation'],
                'romanization' => (string) $termData['WoRomanization'],
                'sentence' => (string) $termData['WoSentence'],
                'notes' => (string) ($termData['WoNotes'] ?? ''),
                'status' => (int) $termData['WoStatus'],
                'tags' => $tags
            ];

            // Get similar terms
            $similarTerms = $this->getSimilarTermsForEdit($langId, (string) $termData['WoTextLC'], $wordId);

            return [
                'isNew' => false,
                'term' => $term,
                'language' => $language,
                'allTags' => $allTags,
                'similarTerms' => $similarTerms
            ];
        }

        // New term - get word at position
        $wordData = $this->wordService->getWordAtPosition($textId, $position);
        if ($wordData === null) {
            return ['error' => 'Word not found at position'];
        }

        $text = $wordData;
        $textLc = mb_strtolower($text, 'UTF-8');

        // Get sentence at position
        $sentence = $this->wordService->getSentenceTextAtPosition($textId, $position);

        // Mark the term in the sentence with curly braces if not already marked
        if ($sentence !== null && strpos($sentence, '{') === false) {
            // Simple replacement - replace first occurrence of the term
            $sentence = preg_replace(
                '/\b' . preg_quote($text, '/') . '\b/iu',
                '{' . $text . '}',
                $sentence,
                1
            );
        }

        $term = [
            'id' => null,
            'text' => $text,
            'textLc' => $textLc,
            'hex' => StringUtils::toClassName($textLc),
            'translation' => '',
            'romanization' => '',
            'sentence' => $sentence ?? '',
            'notes' => '',
            'status' => 1,
            'tags' => []
        ];

        // Get similar terms for new word
        $similarTerms = $this->getSimilarTermsForEdit($langId, $textLc, null);

        return [
            'isNew' => true,
            'term' => $term,
            'language' => $language,
            'allTags' => $allTags,
            'similarTerms' => $similarTerms
        ];
    }

    /**
     * Get similar terms for the edit form.
     *
     * @param int      $langId  Language ID
     * @param string   $termLc  Term in lowercase
     * @param int|null $excludeId Word ID to exclude (current term)
     *
     * @return array Array of similar terms
     */
    private function getSimilarTermsForEdit(int $langId, string $termLc, ?int $excludeId): array
    {
        $similarService = new \Lwt\Modules\Vocabulary\Application\UseCases\FindSimilarTerms();
        $similarIds = $similarService->execute($langId, $termLc, 10, 0.33);

        $result = [];
        foreach ($similarIds as $termId) {
            if ($excludeId !== null && $termId === $excludeId) {
                continue;
            }

            $record = QueryBuilder::table('words')
                ->select(['WoID', 'WoText', 'WoTranslation', 'WoStatus'])
                ->where('WoID', '=', $termId)
                ->firstPrepared();

            if ($record) {
                $result[] = [
                    'id' => (int) $record['WoID'],
                    'text' => (string) $record['WoText'],
                    'translation' => (string) $record['WoTranslation'],
                    'status' => (int) $record['WoStatus']
                ];
            }
        }

        return $result;
    }

    /**
     * Create a term with full data (translation, romanization, sentence, tags, status).
     *
     * @param array $data Term data:
     *                    - textId: Text ID
     *                    - position: Position in text
     *                    - translation: Translation
     *                    - romanization: Romanization (optional)
     *                    - sentence: Example sentence (optional)
     *                    - notes: Notes (optional)
     *                    - status: Status (1-5, default: 1)
     *                    - tags: Array of tag names (optional)
     *
     * @return array{success?: bool, term?: array, error?: string}
     */
    public function createTermFull(array $data): array
    {
        $textId = (int) ($data['textId'] ?? 0);
        $position = (int) ($data['position'] ?? 0);

        if ($textId === 0) {
            return ['error' => 'Text ID is required'];
        }

        // Get language ID from text
        $langId = $this->wordService->getLanguageIdFromText($textId);
        if ($langId === null) {
            return ['error' => 'Text not found'];
        }

        // Get the word at the position
        $wordText = $this->wordService->getWordAtPosition($textId, $position);
        if ($wordText === null) {
            return ['error' => 'Word not found at position'];
        }

        $textLc = mb_strtolower($wordText, 'UTF-8');
        $status = (int) ($data['status'] ?? 1);

        // Validate status
        if (!in_array($status, [1, 2, 3, 4, 5, 98, 99])) {
            return ['error' => 'Status must be 1-5, 98, or 99'];
        }

        $translation = trim($data['translation'] ?? '');
        if ($translation === '') {
            $translation = '*';
        }

        $romanization = trim($data['romanization'] ?? '');
        $sentence = trim($data['sentence'] ?? '');
        $notes = trim($data['notes'] ?? '');

        // Insert the word
        $scoreColumns = TermStatusService::makeScoreRandomInsertUpdate('iv');
        $scoreValues = TermStatusService::makeScoreRandomInsertUpdate('id');

        // Use raw SQL for complex INSERT with dynamic columns
        $bindings = [$langId, $textLc, $wordText, $status, $translation, $sentence, $notes, $romanization];
        $sql = "INSERT INTO words (
                WoLgID, WoTextLC, WoText, WoStatus, WoTranslation,
                WoSentence, WoNotes, WoRomanization, WoStatusChanged,
                {$scoreColumns}
            ) VALUES(?, ?, ?, ?, ?, ?, ?, ?, NOW(), {$scoreValues})"
            . UserScopedQuery::forTablePrepared('words', $bindings);

        $stmt = Connection::prepare($sql);
        $stmt->bind('ississss', $langId, $textLc, $wordText, $status, $translation, $sentence, $notes, $romanization);
        $affected = $stmt->execute();

        if ($affected != 1) {
            return ['error' => 'Failed to create term'];
        }

        $wordId = $stmt->insertId();

        // Update text items to link to this word
        // textitems2 inherits user context via Ti2TxID -> texts FK
        Connection::preparedExecute(
            "UPDATE textitems2
             SET Ti2WoID = ?
             WHERE Ti2LgID = ? AND LOWER(Ti2Text) = ?",
            [$wordId, $langId, $textLc]
        );

        // Save tags if provided
        $tags = $data['tags'] ?? [];
        if (!empty($tags) && is_array($tags)) {
            \Lwt\Modules\Tags\Application\TagsFacade::saveWordTagsFromArray($wordId, $tags);
        }

        // Return complete term data
        return [
            'success' => true,
            'term' => [
                'id' => $wordId,
                'text' => $wordText,
                'textLc' => $textLc,
                'hex' => StringUtils::toClassName($textLc),
                'translation' => $translation === '*' ? '' : $translation,
                'romanization' => $romanization,
                'sentence' => $sentence,
                'notes' => $notes,
                'status' => $status,
                'tags' => $tags
            ]
        ];
    }

    /**
     * Update a term with full data.
     *
     * @param int   $termId Term ID
     * @param array $data   Term data:
     *                      - translation: Translation
     *                      - romanization: Romanization (optional)
     *                      - sentence: Example sentence (optional)
     *                      - notes: Notes (optional)
     *                      - status: Status (1-5)
     *                      - tags: Array of tag names (optional)
     *
     * @return array{success?: bool, term?: array, error?: string}
     */
    public function updateTermFull(int $termId, array $data): array
    {
        // Get existing term data
        $existing = QueryBuilder::table('words')
            ->select(['WoID', 'WoText', 'WoTextLC', 'WoLgID', 'WoStatus'])
            ->where('WoID', '=', $termId)
            ->firstPrepared();

        if ($existing === null) {
            return ['error' => 'Term not found'];
        }

        $status = (int) ($data['status'] ?? $existing['WoStatus']);

        // Validate status
        if (!in_array($status, [1, 2, 3, 4, 5, 98, 99])) {
            return ['error' => 'Status must be 1-5, 98, or 99'];
        }

        $translation = trim($data['translation'] ?? '');
        if ($translation === '') {
            $translation = '*';
        }

        $romanization = trim($data['romanization'] ?? '');
        $sentence = trim($data['sentence'] ?? '');
        $notes = trim($data['notes'] ?? '');

        // Update the word
        $scoreUpdate = TermStatusService::makeScoreRandomInsertUpdate('u');

        // Use raw SQL for dynamic score update
        $bindings = [$translation, $romanization, $sentence, $notes, $status, $termId];
        Connection::preparedExecute(
            "UPDATE words SET
             WoTranslation = ?,
             WoRomanization = ?,
             WoSentence = ?,
             WoNotes = ?,
             WoStatus = ?,
             WoStatusChanged = NOW(),
             {$scoreUpdate}
             WHERE WoID = ?"
            . UserScopedQuery::forTablePrepared('words', $bindings),
            [$translation, $romanization, $sentence, $notes, $status, $termId]
        );

        // Save tags if provided
        $tags = [];
        if (isset($data['tags']) && is_array($data['tags'])) {
            $tags = $data['tags'];
            \Lwt\Modules\Tags\Application\TagsFacade::saveWordTagsFromArray($termId, $tags);
        }

        // Return complete term data
        return [
            'success' => true,
            'term' => [
                'id' => $termId,
                'text' => (string) $existing['WoText'],
                'textLc' => (string) $existing['WoTextLC'],
                'hex' => StringUtils::toClassName((string) $existing['WoTextLC']),
                'translation' => $translation === '*' ? '' : $translation,
                'romanization' => $romanization,
                'sentence' => $sentence,
                'notes' => $notes,
                'status' => $status,
                'tags' => $tags
            ]
        ];
    }

    /**
     * Format response for getting term data for editing.
     *
     * @param int      $textId   Text ID
     * @param int      $position Position in text
     * @param int|null $wordId   Word ID
     *
     * @return array
     */
    public function formatGetTermForEdit(int $textId, int $position, ?int $wordId = null): array
    {
        return $this->getTermForEdit($textId, $position, $wordId);
    }

    /**
     * Format response for creating a term with full data.
     *
     * @param array $data Term data
     *
     * @return array
     */
    public function formatCreateTermFull(array $data): array
    {
        return $this->createTermFull($data);
    }

    /**
     * Format response for updating a term with full data.
     *
     * @param int   $termId Term ID
     * @param array $data   Term data
     *
     * @return array
     */
    public function formatUpdateTermFull(int $termId, array $data): array
    {
        return $this->updateTermFull($termId, $data);
    }

    // =========================================================================
    // Word List API Methods (Alpine.js SPA)
    // =========================================================================

    /**
     * Get paginated, filtered word list.
     *
     * @param array $params Filter parameters:
     *                      - page: int (default 1)
     *                      - per_page: int (default 50)
     *                      - lang: int|null (language ID filter)
     *                      - status: string|null (status filter code)
     *                      - query: string|null (search query)
     *                      - query_mode: string (term, rom, transl, term,rom,transl)
     *                      - regex_mode: string ('' or 'r')
     *                      - tag1: int|null, tag2: int|null, tag12: int|null
     *                      - text_id: int|null (filter words in specific text)
     *                      - sort: int (1-7)
     *
     * @return array{words: array, pagination: array}
     */
    public function getWordList(array $params): array
    {
        $listService = new \Lwt\Modules\Vocabulary\Application\Services\WordListService();

        // Parse parameters with defaults
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($params['per_page'] ?? 50)));
        $lang = $params['lang'] ?? '';
        $status = $params['status'] ?? '';
        $query = $params['query'] ?? '';
        $queryMode = $params['query_mode'] ?? 'term,rom,transl';
        $regexMode = $params['regex_mode'] ?? '';
        $tag1 = $params['tag1'] ?? '';
        $tag2 = $params['tag2'] ?? '';
        $tag12 = $params['tag12'] ?? '0';
        $textId = $params['text_id'] ?? '';
        $sort = max(1, min(7, (int) ($params['sort'] ?? 1)));

        // Build filter conditions
        $whLang = $listService->buildLangCondition((string) $lang);
        $whStat = $listService->buildStatusCondition((string) $status);
        $whQuery = $listService->buildQueryCondition((string) $query, (string) $queryMode, (string) $regexMode);
        $whTag = $listService->buildTagCondition((string) $tag1, (string) $tag2, (string) $tag12);

        // Get total count
        $total = $listService->countWords(
            (string) $textId,
            $whLang,
            $whStat,
            $whQuery,
            $whTag
        );

        // Calculate pagination
        $totalPages = (int) ceil($total / $perPage);
        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
        }

        // Get words list
        $filters = [
            'whLang' => $whLang,
            'whStat' => $whStat,
            'whQuery' => $whQuery,
            'whTag' => $whTag,
            'textId' => (string) $textId
        ];

        $result = $listService->getWordsList($filters, $sort, $page, $perPage);
        $words = [];

        if ($result) {
            while ($record = mysqli_fetch_assoc($result)) {
                $words[] = $this->formatWordRecord($record, $sort);
            }
            mysqli_free_result($result);
        }

        return [
            'words' => $words,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages
            ]
        ];
    }

    /**
     * Format a word record for API response.
     *
     * @param array $record Database record
     * @param int   $sort   Current sort option
     *
     * @return array Formatted word data
     */
    private function formatWordRecord(array $record, int $sort): array
    {
        $status = (int) $record['WoStatus'];
        $days = (int) ($record['Days'] ?? 0);

        $word = [
            'id' => (int) $record['WoID'],
            'text' => (string) $record['WoText'],
            'translation' => (string) ($record['WoTranslation'] ?? ''),
            'romanization' => (string) ($record['WoRomanization'] ?? ''),
            'sentence' => (string) ($record['WoSentence'] ?? ''),
            'sentenceOk' => (bool) ($record['SentOK'] ?? false),
            'status' => $status,
            'statusAbbr' => \Lwt\View\Helper\StatusHelper::getAbbr($status),
            'statusLabel' => \Lwt\View\Helper\StatusHelper::getName($status),
            'days' => $status > 5 ? '-' : (string) $days,
            'score' => (float) ($record['Score'] ?? 0),
            'score2' => (float) ($record['Score2'] ?? 0),
            'tags' => (string) ($record['taglist'] ?? ''),
            'langId' => 0, // Will be set from LgID if available
            'langName' => (string) ($record['LgName'] ?? ''),
            'rightToLeft' => (bool) ($record['LgRightToLeft'] ?? false),
            'ttsClass' => null
        ];

        // Extract TTS class from Google Translate URI
        $gtUri = $record['LgGoogleTranslateURI'] ?? '';
        if (!empty($gtUri) && strpos($gtUri, '&sl=') !== false) {
            $word['ttsClass'] = 'tts_' . preg_replace('/.*[?&]sl=([a-zA-Z\-]*)(&.*)*$/', '$1', $gtUri);
        }

        // Add text word count for sort option 7
        if ($sort === 7 && isset($record['textswordcount'])) {
            $word['textsWordCount'] = (int) $record['textswordcount'];
        }

        return $word;
    }

    /**
     * Perform bulk action on selected word IDs.
     *
     * @param int[]       $wordIds Array of word IDs
     * @param string      $action  Action code
     * @param string|null $data    Optional data (e.g., tag name)
     *
     * @return array{success: bool, count: int, message: string}
     */
    public function bulkAction(array $wordIds, string $action, ?string $data = null): array
    {
        if (empty($wordIds)) {
            return ['success' => false, 'count' => 0, 'message' => 'No terms selected'];
        }

        $listService = new \Lwt\Modules\Vocabulary\Application\Services\WordListService();

        // Sanitize word IDs
        $wordIds = array_filter(array_map('intval', $wordIds));
        if (empty($wordIds)) {
            return ['success' => false, 'count' => 0, 'message' => 'Invalid term IDs'];
        }

        $idList = '(' . implode(',', $wordIds) . ')';
        $count = count($wordIds);

        switch ($action) {
            case 'del':
                $message = $listService->deleteByIdList($idList);
                break;

            case 'spl1': // Status +1
                $message = $listService->updateStatusByIdList($idList, 1, true, 'spl1');
                break;

            case 'smi1': // Status -1
                $message = $listService->updateStatusByIdList($idList, -1, true, 'smi1');
                break;

            case 's1':
            case 's2':
            case 's3':
            case 's4':
            case 's5':
            case 's98':
            case 's99':
                $status = (int) substr($action, 1);
                $message = $listService->updateStatusByIdList($idList, $status, false, $action);
                break;

            case 'today':
                $message = $listService->updateStatusDateByIdList($idList);
                break;

            case 'delsent':
                $message = $listService->deleteSentencesByIdList($idList);
                break;

            case 'lower':
                $message = $listService->toLowercaseByIdList($idList);
                break;

            case 'cap':
                $message = $listService->capitalizeByIdList($idList);
                break;

            case 'addtag':
                if (empty($data)) {
                    return ['success' => false, 'count' => 0, 'message' => 'Tag name required'];
                }
                $message = \Lwt\Modules\Tags\Application\TagsFacade::addTagToWords($data, $idList);
                break;

            case 'deltag':
                if (empty($data)) {
                    return ['success' => false, 'count' => 0, 'message' => 'Tag name required'];
                }
                $message = \Lwt\Modules\Tags\Application\TagsFacade::removeTagFromWords($data, $idList);
                break;

            default:
                return ['success' => false, 'count' => 0, 'message' => 'Unknown action: ' . $action];
        }

        return ['success' => true, 'count' => $count, 'message' => $message];
    }

    /**
     * Perform action on ALL words matching current filter.
     *
     * @param array       $filters Filter parameters
     * @param string      $action  Action code
     * @param string|null $data    Optional data
     *
     * @return array{success: bool, count: int, message: string}
     */
    public function allAction(array $filters, string $action, ?string $data = null): array
    {
        $listService = new \Lwt\Modules\Vocabulary\Application\Services\WordListService();

        // Build filter conditions from params
        $lang = $filters['lang'] ?? '';
        $status = $filters['status'] ?? '';
        $query = $filters['query'] ?? '';
        $queryMode = $filters['query_mode'] ?? 'term,rom,transl';
        $regexMode = $filters['regex_mode'] ?? '';
        $tag1 = $filters['tag1'] ?? '';
        $tag2 = $filters['tag2'] ?? '';
        $tag12 = $filters['tag12'] ?? '0';
        $textId = $filters['text_id'] ?? '';

        $whLang = $listService->buildLangCondition((string) $lang);
        $whStat = $listService->buildStatusCondition((string) $status);
        $whQuery = $listService->buildQueryCondition((string) $query, (string) $queryMode, (string) $regexMode);
        $whTag = $listService->buildTagCondition((string) $tag1, (string) $tag2, (string) $tag12);

        // Get all word IDs matching the filter
        $wordIds = $listService->getFilteredWordIds(
            (string) $textId,
            $whLang,
            $whStat,
            $whQuery,
            $whTag
        );

        if (empty($wordIds)) {
            return ['success' => false, 'count' => 0, 'message' => 'No terms match the filter'];
        }

        // Remove 'all' suffix from action if present
        $action = preg_replace('/all$/', '', $action);

        return $this->bulkAction($wordIds, $action, $data);
    }

    /**
     * Inline edit translation or romanization.
     *
     * @param int    $termId Term ID
     * @param string $field  Field name ('translation' or 'romanization')
     * @param string $value  New value
     *
     * @return array{success: bool, value: string, error?: string}
     */
    public function inlineEdit(int $termId, string $field, string $value): array
    {
        // Validate field
        if (!in_array($field, ['translation', 'romanization'])) {
            return ['success' => false, 'value' => '', 'error' => 'Invalid field'];
        }

        // Check term exists
        $exists = QueryBuilder::table('words')
            ->where('WoID', '=', $termId)
            ->countPrepared();

        if ($exists === 0) {
            return ['success' => false, 'value' => '', 'error' => 'Term not found'];
        }

        // Prepare value
        $value = trim($value);
        $displayValue = $value;

        if ($field === 'translation') {
            if ($value === '') {
                $value = '*';
                $displayValue = '*';
            }
            QueryBuilder::table('words')
                ->where('WoID', '=', $termId)
                ->updatePrepared(['WoTranslation' => $value]);
        } else {
            // romanization
            if ($value === '') {
                $displayValue = '*';
            }
            QueryBuilder::table('words')
                ->where('WoID', '=', $termId)
                ->updatePrepared(['WoRomanization' => $value]);
        }

        return ['success' => true, 'value' => $displayValue];
    }

    /**
     * Get filter dropdown options.
     *
     * @param int|null $langId Language ID for filtering texts
     *
     * @return array{languages: array, texts: array, tags: array, statuses: array, sorts: array}
     */
    public function getFilterOptions(?int $langId = null): array
    {
        // Get languages
        $languages = [];
        $langResult = QueryBuilder::table('languages')
            ->select(['LgID', 'LgName'])
            ->orderBy('LgName')
            ->getPrepared();
        foreach ($langResult as $row) {
            $languages[] = [
                'id' => (int) $row['LgID'],
                'name' => (string) $row['LgName']
            ];
        }

        // Get texts (optionally filtered by language)
        $texts = [];
        if ($langId !== null && $langId > 0) {
            $textResult = QueryBuilder::table('texts')
                ->select(['TxID', 'TxTitle'])
                ->where('TxLgID', '=', $langId)
                ->orderBy('TxTitle')
                ->getPrepared();
            foreach ($textResult as $row) {
                $texts[] = [
                    'id' => (int) $row['TxID'],
                    'title' => (string) $row['TxTitle']
                ];
            }
        }

        // Get term tags (from tags table - tags2 is for text tags)
        $tags = [];
        $tagResult = QueryBuilder::table('tags')
            ->select(['TgID', 'TgText'])
            ->orderBy('TgText')
            ->getPrepared();
        foreach ($tagResult as $row) {
            $tags[] = [
                'id' => (int) $row['TgID'],
                'name' => (string) $row['TgText']
            ];
        }

        // Static status options
        $statuses = [
            ['value' => '', 'label' => '[All Terms]'],
            ['value' => '1', 'label' => 'Learning (1)'],
            ['value' => '2', 'label' => 'Learning (2)'],
            ['value' => '3', 'label' => 'Learning (3)'],
            ['value' => '4', 'label' => 'Learning (4)'],
            ['value' => '5', 'label' => 'Learned (5)'],
            ['value' => '99', 'label' => 'Well Known (99)'],
            ['value' => '98', 'label' => 'Ignored (98)'],
            ['value' => '12', 'label' => 'Learning (1-2)'],
            ['value' => '13', 'label' => 'Learning (1-3)'],
            ['value' => '14', 'label' => 'Learning (1-4)'],
            ['value' => '15', 'label' => 'Learning (1-5)'],
            ['value' => '599', 'label' => 'Learned (5+99)'],
            ['value' => '34', 'label' => 'Learning (3-4)'],
            ['value' => '35', 'label' => 'Learning (3-5)'],
            ['value' => '24', 'label' => 'Learning (2-4)'],
            ['value' => '25', 'label' => 'Learning (2-5)'],
        ];

        // Static sort options
        $sorts = [
            ['value' => 1, 'label' => 'Term A-Z'],
            ['value' => 2, 'label' => 'Translation A-Z'],
            ['value' => 3, 'label' => 'Newest first'],
            ['value' => 4, 'label' => 'Oldest first'],
            ['value' => 5, 'label' => 'Status'],
            ['value' => 6, 'label' => 'Score'],
            ['value' => 7, 'label' => 'Word count in texts'],
        ];

        return [
            'languages' => $languages,
            'texts' => $texts,
            'tags' => $tags,
            'statuses' => $statuses,
            'sorts' => $sorts
        ];
    }

    // =========================================================================
    // Word List API Response Formatters
    // =========================================================================

    /**
     * Format response for getting word list.
     *
     * @param array $params Filter parameters
     *
     * @return array
     */
    public function formatGetWordList(array $params): array
    {
        return $this->getWordList($params);
    }

    /**
     * Format response for bulk action.
     *
     * @param array       $ids    Word IDs
     * @param string      $action Action code
     * @param string|null $data   Optional data
     *
     * @return array
     */
    public function formatBulkAction(array $ids, string $action, ?string $data = null): array
    {
        return $this->bulkAction($ids, $action, $data);
    }

    /**
     * Format response for all action.
     *
     * @param array       $filters Filter parameters
     * @param string      $action  Action code
     * @param string|null $data    Optional data
     *
     * @return array
     */
    public function formatAllAction(array $filters, string $action, ?string $data = null): array
    {
        return $this->allAction($filters, $action, $data);
    }

    /**
     * Format response for inline edit.
     *
     * @param int    $termId Term ID
     * @param string $field  Field name
     * @param string $value  New value
     *
     * @return array
     */
    public function formatInlineEdit(int $termId, string $field, string $value): array
    {
        return $this->inlineEdit($termId, $field, $value);
    }

    /**
     * Format response for getting filter options.
     *
     * @param int|null $langId Language ID
     *
     * @return array
     */
    public function formatGetFilterOptions(?int $langId = null): array
    {
        return $this->getFilterOptions($langId);
    }
}
