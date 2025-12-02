<?php declare(strict_types=1);
namespace Lwt\Api\V1\Handlers;

use Lwt\Core\Globals;
use Lwt\Core\StringUtils;
use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Services\WordService;
use Lwt\Services\WordStatusService;

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
        $tbpref = Globals::getTablePrefix();
        $textlc = mb_strtolower($text, 'UTF-8');

        // Insert new word using prepared statement
        $scoreColumns = WordStatusService::makeScoreRandomInsertUpdate('iv');
        $scoreValues = WordStatusService::makeScoreRandomInsertUpdate('id');

        $sql = "INSERT INTO {$tbpref}words (
                WoLgID, WoTextLC, WoText, WoStatus, WoTranslation,
                WoSentence, WoRomanization, WoStatusChanged,
                {$scoreColumns}
            ) VALUES(?, ?, ?, 1, ?, ?, ?, NOW(), {$scoreValues})";

        $stmt = Connection::prepare($sql);
        $stmt->bind('isssss', $lang, $textlc, $text, $data, '', '');
        $affected = $stmt->execute();

        if ($affected != 1) {
            return "Error: $affected rows affected, expected 1!";
        }

        $wid = $stmt->insertId();

        // Update text items using prepared statement
        Connection::preparedExecute(
            "UPDATE {$tbpref}textitems2
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
        $tbpref = Globals::getTablePrefix();

        $oldtrans = (string) Connection::preparedFetchValue(
            "SELECT WoTranslation AS value FROM {$tbpref}words WHERE WoID = ?",
            [$wid]
        );

        $oldtransarr = preg_split('/[' . StringUtils::getSeparators() . ']/u', $oldtrans);
        if ($oldtransarr === false) {
            return (string) Connection::preparedFetchValue(
                "SELECT WoTextLC AS value FROM {$tbpref}words WHERE WoID = ?",
                [$wid]
            );
        }
        array_walk($oldtransarr, '\trim_value');

        if (!in_array($newTrans, $oldtransarr)) {
            if (trim($oldtrans) == '' || trim($oldtrans) == '*') {
                $oldtrans = $newTrans;
            } else {
                $oldtrans .= ' ' . \get_first_sepa() . ' ' . $newTrans;
            }
            Connection::preparedExecute(
                "UPDATE {$tbpref}words SET WoTranslation = ? WHERE WoID = ?",
                [$oldtrans, $wid]
            );
        }

        return (string) Connection::preparedFetchValue(
            "SELECT WoTextLC AS value FROM {$tbpref}words WHERE WoID = ?",
            [$wid]
        );
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
        $tbpref = Globals::getTablePrefix();

        $cntWords = (int) Connection::preparedFetchValue(
            "SELECT COUNT(WoID) AS value FROM {$tbpref}words WHERE WoID = ?",
            [$wid]
        );

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
        $tbpref = Globals::getTablePrefix();
        $scoreUpdate = WordStatusService::makeScoreRandomInsertUpdate('u');

        return Connection::preparedExecute(
            "UPDATE {$tbpref}words
            SET WoStatus = ?, WoStatusChanged = NOW(), {$scoreUpdate}
            WHERE WoID = ?",
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
        $tbpref = Globals::getTablePrefix();

        if (($currstatus >= 1 && $currstatus <= 5) || $currstatus == 99 || $currstatus == 98) {
            $m1 = $this->setWordStatus($wid, $currstatus);
            if ($m1 == 1) {
                $currstatus = Connection::preparedFetchValue(
                    "SELECT WoStatus AS value FROM {$tbpref}words WHERE WoID = ?",
                    [$wid]
                );
                if (!isset($currstatus)) {
                    return null;
                }
                return \make_status_controls_test_table(1, (int)$currstatus, $wid);
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
        $tbpref = Globals::getTablePrefix();

        $tempstatus = Connection::preparedFetchValue(
            "SELECT WoStatus AS value FROM {$tbpref}words WHERE WoID = ?",
            [$wid]
        );

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
        $tbpref = Globals::getTablePrefix();

        // Check if term exists
        $exists = Connection::preparedFetchValue(
            "SELECT COUNT(WoID) AS value FROM {$tbpref}words WHERE WoID = ?",
            [$termId]
        );

        if ((int)$exists === 0) {
            return ['deleted' => false, 'error' => 'Term not found'];
        }

        // Get word count to determine if multi-word
        $wordCount = (int) Connection::preparedFetchValue(
            "SELECT WoWordCount AS value FROM {$tbpref}words WHERE WoID = ?",
            [$termId]
        );

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
        $tbpref = Globals::getTablePrefix();

        $record = Connection::preparedFetchOne(
            "SELECT WoID, WoText, WoTextLC, WoTranslation, WoRomanization, WoStatus, WoLgID
             FROM {$tbpref}words WHERE WoID = ?",
            [$termId]
        );

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
     * @return array{id: int, text: string, textLc: string, translation: string, romanization: string, status: int, langId: int, sentence: string, tags: array<string>, statusLabel: string}|array{error: string}
     */
    public function getTermDetails(int $termId, ?string $ann = null): array
    {
        $tbpref = Globals::getTablePrefix();

        $record = Connection::preparedFetchOne(
            "SELECT WoID, WoText, WoTextLC, WoTranslation, WoRomanization,
                    WoStatus, WoLgID, WoSentence
             FROM {$tbpref}words WHERE WoID = ?",
            [$termId]
        );

        if ($record === null) {
            return ['error' => 'Term not found'];
        }

        // Get tags for the word
        $tagsResult = Connection::preparedFetchAll(
            "SELECT TgText FROM {$tbpref}wordtags
             JOIN {$tbpref}tags ON TgID = WtTgID
             WHERE WtWoID = ?
             ORDER BY TgText",
            [$termId]
        );
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
            'tags' => $tags,
            'statusLabel' => WordStatusService::getStatusName((int)$record['WoStatus'])
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
        $tbpref = Globals::getTablePrefix();

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
            $wordCount = (int) Connection::preparedFetchValue(
                "SELECT WoWordCount AS value FROM {$tbpref}words WHERE WoID = ?",
                [$wordId]
            );

            return [
                'id' => $wordId,
                'text' => $data['text'],
                'textLc' => mb_strtolower($data['text'], 'UTF-8'),
                'translation' => $data['translation'],
                'romanization' => $data['romanization'],
                'sentence' => $data['sentence'],
                'status' => $data['status'],
                'langId' => $data['lgid'],
                'wordCount' => $wordCount,
                'isNew' => false
            ];
        }

        // New multi-word expression
        if ($text === null || $text === '') {
            return ['error' => 'Multi-word text is required for new expressions'];
        }

        // Get sentence at position
        $sentence = $this->wordService->getSentenceTextAtPosition($textId, $position);

        // Count words in the text
        $wordCount = count(preg_split('/\s+/', trim($text)) ?: []);

        return [
            'id' => null,
            'text' => $text,
            'textLc' => mb_strtolower($text, 'UTF-8'),
            'translation' => '',
            'romanization' => '',
            'sentence' => $sentence ?? '',
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
                'roman' => $data['romanization'] ?? '',
                'wordcount' => $wordCount
            ]);

            return [
                'term_id' => $result['id'],
                'term_lc' => $textLc,
                'hex' => strToClassName($textLc)
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Update an existing multi-word expression.
     *
     * @param int   $termId Term ID
     * @param array $data   Multi-word data (translation, romanization, sentence, status)
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
        $tbpref = Globals::getTablePrefix();

        // Get language ID and settings from text
        $textData = Connection::preparedFetchOne(
            "SELECT TxLgID, TxTitle FROM {$tbpref}texts WHERE TxID = ?",
            [$textId]
        );

        if ($textData === null) {
            return ['error' => 'Text not found'];
        }

        $langId = (int) $textData['TxLgID'];

        // Get language settings
        $langData = Connection::preparedFetchOne(
            "SELECT LgName, LgShowRomanization, LgGoogleTranslateURI
             FROM {$tbpref}languages WHERE LgID = ?",
            [$langId]
        );

        if ($langData === null) {
            return ['error' => 'Language not found'];
        }

        // Get all term tags for autocomplete
        $allTags = \Lwt\Services\TagService::getAllTermTags();

        // Build language info
        $language = [
            'id' => $langId,
            'name' => (string) $langData['LgName'],
            'showRomanization' => (bool) $langData['LgShowRomanization'],
            'translateUri' => (string) ($langData['LgGoogleTranslateURI'] ?? '')
        ];

        // If word ID provided, get existing term data
        if ($wordId !== null && $wordId > 0) {
            $termData = Connection::preparedFetchOne(
                "SELECT WoID, WoText, WoTextLC, WoTranslation, WoRomanization,
                        WoSentence, WoStatus, WoLgID
                 FROM {$tbpref}words WHERE WoID = ?",
                [$wordId]
            );

            if ($termData === null) {
                return ['error' => 'Term not found'];
            }

            // Get tags for the word
            $tagsResult = Connection::preparedFetchAll(
                "SELECT TgText FROM {$tbpref}wordtags
                 JOIN {$tbpref}tags ON TgID = WtTgID
                 WHERE WtWoID = ?
                 ORDER BY TgText",
                [$wordId]
            );
            $tags = array_map(fn($row) => (string)$row['TgText'], $tagsResult);

            $term = [
                'id' => (int) $termData['WoID'],
                'text' => (string) $termData['WoText'],
                'textLc' => (string) $termData['WoTextLC'],
                'hex' => strToClassName((string) $termData['WoTextLC']),
                'translation' => (string) $termData['WoTranslation'],
                'romanization' => (string) $termData['WoRomanization'],
                'sentence' => (string) $termData['WoSentence'],
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
            'hex' => strToClassName($textLc),
            'translation' => '',
            'romanization' => '',
            'sentence' => $sentence ?? '',
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
        $tbpref = Globals::getTablePrefix();
        $similarService = new \Lwt\Services\SimilarTermsService();
        $similarIds = $similarService->getSimilarTerms($langId, $termLc, 10, 0.33);

        $result = [];
        foreach ($similarIds as $termId) {
            if ($excludeId !== null && $termId === $excludeId) {
                continue;
            }

            $record = Connection::preparedFetchOne(
                "SELECT WoID, WoText, WoTranslation, WoStatus
                 FROM {$tbpref}words WHERE WoID = ?",
                [$termId]
            );

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
     *                    - status: Status (1-5, default: 1)
     *                    - tags: Array of tag names (optional)
     *
     * @return array{success?: bool, term?: array, error?: string}
     */
    public function createTermFull(array $data): array
    {
        $tbpref = Globals::getTablePrefix();
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

        // Insert the word
        $scoreColumns = WordStatusService::makeScoreRandomInsertUpdate('iv');
        $scoreValues = WordStatusService::makeScoreRandomInsertUpdate('id');

        $sql = "INSERT INTO {$tbpref}words (
                WoLgID, WoTextLC, WoText, WoStatus, WoTranslation,
                WoSentence, WoRomanization, WoStatusChanged,
                {$scoreColumns}
            ) VALUES(?, ?, ?, ?, ?, ?, ?, NOW(), {$scoreValues})";

        $stmt = Connection::prepare($sql);
        $stmt->bind('ississs', $langId, $textLc, $wordText, $status, $translation, $sentence, $romanization);
        $affected = $stmt->execute();

        if ($affected != 1) {
            return ['error' => 'Failed to create term'];
        }

        $wordId = $stmt->insertId();

        // Update text items to link to this word
        Connection::preparedExecute(
            "UPDATE {$tbpref}textitems2
             SET Ti2WoID = ?
             WHERE Ti2LgID = ? AND LOWER(Ti2Text) = ?",
            [$wordId, $langId, $textLc]
        );

        // Save tags if provided
        $tags = $data['tags'] ?? [];
        if (!empty($tags) && is_array($tags)) {
            \Lwt\Services\TagService::saveWordTagsFromArray($wordId, $tags);
        }

        // Return complete term data
        return [
            'success' => true,
            'term' => [
                'id' => $wordId,
                'text' => $wordText,
                'textLc' => $textLc,
                'hex' => strToClassName($textLc),
                'translation' => $translation === '*' ? '' : $translation,
                'romanization' => $romanization,
                'sentence' => $sentence,
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
     *                      - status: Status (1-5)
     *                      - tags: Array of tag names (optional)
     *
     * @return array{success?: bool, term?: array, error?: string}
     */
    public function updateTermFull(int $termId, array $data): array
    {
        $tbpref = Globals::getTablePrefix();

        // Get existing term data
        $existing = Connection::preparedFetchOne(
            "SELECT WoID, WoText, WoTextLC, WoLgID, WoStatus
             FROM {$tbpref}words WHERE WoID = ?",
            [$termId]
        );

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

        // Update the word
        $scoreUpdate = WordStatusService::makeScoreRandomInsertUpdate('u');

        Connection::preparedExecute(
            "UPDATE {$tbpref}words SET
             WoTranslation = ?,
             WoRomanization = ?,
             WoSentence = ?,
             WoStatus = ?,
             WoStatusChanged = NOW(),
             {$scoreUpdate}
             WHERE WoID = ?",
            [$translation, $romanization, $sentence, $status, $termId]
        );

        // Save tags if provided
        $tags = [];
        if (isset($data['tags']) && is_array($data['tags'])) {
            $tags = $data['tags'];
            \Lwt\Services\TagService::saveWordTagsFromArray($termId, $tags);
        }

        // Return complete term data
        return [
            'success' => true,
            'term' => [
                'id' => $termId,
                'text' => (string) $existing['WoText'],
                'textLc' => (string) $existing['WoTextLC'],
                'hex' => strToClassName((string) $existing['WoTextLC']),
                'translation' => $translation === '*' ? '' : $translation,
                'romanization' => $romanization,
                'sentence' => $sentence,
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
}
