<?php

/**
 * Review Service - Business logic for word review operations
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Review\Application\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Review\Application\Services;

use Lwt\Shared\Infrastructure\Globals;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;
use Lwt\Modules\Text\Application\Services\SentenceService;
use Lwt\Modules\Vocabulary\Application\Services\ExportService;
use Lwt\Modules\Vocabulary\Application\Services\TermStatusService;
use Lwt\Modules\Tags\Application\TagsFacade;
use Lwt\Modules\Review\Infrastructure\SessionStateManager;

/**
 * Service class for managing word reviews.
 *
 * Handles test SQL generation, word selection, status updates,
 * and progress tracking for vocabulary testing.
 *
 * @category Lwt
 * @package  Lwt\Modules\Review\Application\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */
class ReviewService
{
    /**
     * Sentence service instance
     *
     * @var SentenceService
     */
    private SentenceService $sentenceService;

    /**
     * Session state manager instance
     *
     * @var SessionStateManager
     */
    private SessionStateManager $sessionManager;

    /**
     * Constructor - initialize dependencies.
     *
     * @param SentenceService|null      $sentenceService Sentence service (optional)
     * @param SessionStateManager|null  $sessionManager  Session state manager (optional)
     */
    public function __construct(
        ?SentenceService $sentenceService = null,
        ?SessionStateManager $sessionManager = null
    ) {
        $this->sentenceService = $sentenceService ?? new SentenceService();
        $this->sessionManager = $sessionManager ?? new SessionStateManager();
    }

    /**
     * Get test identifier from request parameters.
     *
     * @param int|null    $selection    Test is of type selection
     * @param string|null $sessTestsql  SQL string for test
     * @param int|null    $lang         Test is of type language
     * @param int|null    $text         Testing text with ID $text
     *
     * @return array{0: string, 1: int|int[]|string} Selector type and selection value
     */
    public function getReviewIdentifier(
        ?int $selection,
        ?string $sessTestsql,
        ?int $lang,
        ?int $text
    ): array {
        if ($selection !== null && $sessTestsql !== null) {
            $dataStringArray = explode(",", trim($sessTestsql, "()"));
            $dataIntArray = array_map('intval', $dataStringArray);

            switch ($selection) {
                case 2:
                    return ['words', $dataIntArray];
                case 3:
                    return ['texts', $dataIntArray];
                default:
                    // Unknown selection type — reject rather than passing raw SQL
                    return ['', ''];
            }
        }

        if ($lang !== null) {
            return ['lang', $lang];
        }

        if ($text !== null) {
            return ['text', $text];
        }

        return ['', ''];
    }

    /**
     * Get SQL projection for test with prepared statement parameters.
     *
     * @param string    $selector  Type of test ('words', 'texts', 'lang', 'text')
     * @param int|int[] $selection Selection value
     *
     * @return array{sql: string, params: array<int, int>} SQL projection and bound params
     *
     * @throws \InvalidArgumentException If selector is invalid
     */
    public function getReviewSql(string $selector, int|array $selection): array
    {
        switch ($selector) {
            case 'words':
                $ids = is_array($selection) ? $selection : [$selection];
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                /** @var array<int, int> $params */
                $params = array_values(array_map('intval', $ids));
                return ['sql' => " words WHERE WoID IN ($placeholders) ", 'params' => $params];
            case 'texts':
                $ids = is_array($selection) ? $selection : [$selection];
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                /** @var array<int, int> $params */
                $params = array_values(array_map('intval', $ids));
                return [
                    'sql' => " words, word_occurrences WHERE Ti2LgID = WoLgID AND Ti2WoID = WoID AND Ti2TxID IN ($placeholders) ",
                    'params' => $params
                ];
            case 'lang':
                $langId = is_array($selection) ? ($selection[0] ?? 0) : $selection;
                return ['sql' => " words WHERE WoLgID = ? ", 'params' => [$langId]];
            case 'text':
                $textId = is_array($selection) ? ($selection[0] ?? 0) : $selection;
                return [
                    'sql' => " words, word_occurrences WHERE Ti2LgID = WoLgID AND Ti2WoID = WoID AND Ti2TxID = ? ",
                    'params' => [$textId]
                ];
            default:
                throw new \InvalidArgumentException(
                    "Invalid selector '$selector': must be 'words', 'texts', 'lang', or 'text'"
                );
        }
    }

    /**
     * Validate test selection (check single language).
     *
     * @param string             $reviewsql SQL projection string with ? placeholders
     * @param array<int, int>    $params    Bound parameters for the SQL
     *
     * @return array{valid: bool, langCount: int, error: string|null}
     */
    public function validateReviewSelection(string $reviewsql, array $params = []): array
    {
        $langCount = (int) Connection::preparedFetchValue(
            "SELECT COUNT(DISTINCT WoLgID) AS cnt FROM $reviewsql",
            $params,
            'cnt'
        );

        if ($langCount > 1) {
            return [
                'valid' => false,
                'langCount' => $langCount,
                'error' => "The selected terms are in $langCount languages, " .
                    "but tests are only possible in one language at a time."
            ];
        }

        return [
            'valid' => true,
            'langCount' => $langCount,
            'error' => null
        ];
    }

    /**
     * Get language name for test.
     *
     * @param int|null    $lang      Language ID
     * @param int|null    $text      Text ID
     * @param int|null    $selection Selection type
     * @param string|null $reviewsql   Test SQL for selection
     *
     * @return string Language name or 'L2' as default
     */
    public function getL2LanguageName(
        ?int $lang,
        ?int $text,
        ?int $selection = null,
        ?string $reviewsql = null
    ): string {
        if ($lang !== null) {
            /** @var mixed $nameRaw */
            $nameRaw = QueryBuilder::table('languages')
                ->where('LgID', '=', $lang)
                ->valuePrepared('LgName');
            return is_string($nameRaw) ? $nameRaw : 'L2';
        }

        if ($text !== null) {
            $row = QueryBuilder::table('texts')
                ->select(['LgName'])
                ->join('languages', 'TxLgID', '=', 'LgID')
                ->where('TxID', '=', $text)
                ->firstPrepared();
            /** @var mixed $nameRawFromRow */
            $nameRawFromRow = $row['LgName'] ?? null;
            return is_string($nameRawFromRow) ? $nameRawFromRow : 'L2';
        }

        if ($selection !== null && $reviewsql !== null) {
            $result = $this->buildSelectionReviewSql($selection, $reviewsql);
            if ($result !== null) {
                $validation = $this->validateReviewSelection($result['sql'], $result['params']);
                if ($validation['langCount'] == 1) {
                    $bindings = [];
                    $userScope = UserScopedQuery::forTablePrepared('words', $bindings);
                    /** @var mixed $nameRawFromQuery */
                    $nameRawFromQuery = Connection::preparedFetchValue(
                        "SELECT LgName
                        FROM languages, {$result['sql']} AND LgID = WoLgID"
                        . $userScope . "
                        LIMIT 1",
                        array_merge($result['params'], $bindings),
                        'LgName'
                    );
                    return is_string($nameRawFromQuery) ? $nameRawFromQuery : 'L2';
                }
            }
        }

        return 'L2';
    }

    /**
     * Build test SQL from selection with prepared statement parameters.
     *
     * @param int    $selectionType Selection type (2=words, 3=texts)
     * @param string $selectionData Comma-separated IDs
     *
     * @return array{sql: string, params: array<int, int>}|null SQL and params, or null
     */
    public function buildSelectionReviewSql(int $selectionType, string $selectionData): ?array
    {
        $dataStringArray = explode(",", trim($selectionData, "()"));
        $dataIntArray = array_map('intval', $dataStringArray);
        switch ($selectionType) {
            case 2:
                return $this->getReviewSql('words', $dataIntArray);
            case 3:
                return $this->getReviewSql('texts', $dataIntArray);
            default:
                return null;
        }
    }

    /**
     * Get test counts (due and total).
     *
     * @param string          $reviewsql SQL projection string with ? placeholders
     * @param array<int, int|string> $params    Bound parameters for the SQL
     *
     * @return array{due: int, total: int}
     */
    public function getReviewCounts(string $reviewsql, array $params = []): array
    {
        $due = (int) Connection::preparedFetchValue(
            "SELECT COUNT(DISTINCT WoID) AS cnt
            FROM $reviewsql AND WoStatus BETWEEN 1 AND 5
            AND WoTranslation != '' AND WoTranslation != '*' AND WoTodayScore < 0",
            $params,
            'cnt'
        );

        $total = (int) Connection::preparedFetchValue(
            "SELECT COUNT(DISTINCT WoID) AS cnt
            FROM $reviewsql AND WoStatus BETWEEN 1 AND 5
            AND WoTranslation != '' AND WoTranslation != '*'",
            $params,
            'cnt'
        );

        return ['due' => $due, 'total' => $total];
    }

    /**
     * Get tomorrow's test count.
     *
     * @param string          $reviewsql SQL projection string with ? placeholders
     * @param array<int, int|string> $params    Bound parameters for the SQL
     *
     * @return int Number of tests due tomorrow
     */
    public function getTomorrowReviewCount(string $reviewsql, array $params = []): int
    {
        return (int) Connection::preparedFetchValue(
            "SELECT COUNT(DISTINCT WoID) AS cnt
            FROM $reviewsql AND WoStatus BETWEEN 1 AND 5
            AND WoTranslation != '' AND WoTranslation != '*' AND WoTomorrowScore < 0",
            $params,
            'cnt'
        );
    }

    /**
     * Get the next word to test.
     *
     * @param string          $reviewsql SQL projection string with ? placeholders
     * @param array<int, int|string> $params    Bound parameters for the SQL
     *
     * @return array|null Word record or null if none available
     */
    public function getNextWord(string $reviewsql, array $params = []): ?array
    {
        $pass = 0;
        while ($pass < 2) {
            $pass++;
            $sql = "SELECT DISTINCT WoID, WoText, WoTextLC, WoTranslation,
                WoRomanization, WoSentence, WoLgID,
                (IFNULL(WoSentence, '') NOT LIKE CONCAT('%{', WoText, '}%')) AS notvalid,
                WoStatus,
                DATEDIFF(NOW(), WoStatusChanged) AS Days, WoTodayScore AS Score
                FROM $reviewsql AND WoStatus BETWEEN 1 AND 5
                AND WoTranslation != '' AND WoTranslation != '*' AND WoTodayScore < 0 " .
                ($pass == 1 ? 'AND WoRandom > RAND()' : '') . '
                ORDER BY WoTodayScore, WoRandom
                LIMIT 1';

            $rows = Connection::preparedFetchAll($sql, $params);
            $record = $rows[0] ?? null;

            if ($record !== null) {
                return $record;
            }
        }
        return null;
    }

    /**
     * Get sentence containing the word for testing.
     *
     * @param int    $wordId Word ID
     * @param string $wordlc Lowercase word text
     *
     * @return array{sentence: string|null, found: bool}
     */
    public function getSentenceForWord(int $wordId, string $wordlc): array
    {
        // Find sentence with at least 70% known words
        // This is a complex query with subqueries - using raw SQL
        // word_occurrences inherits user context via Ti2TxID -> texts FK, so no user_id needed
        $sql = "SELECT DISTINCT ti.Ti2SeID AS SeID,
            1 - IFNULL(sUnknownCount.c, 0) / sWordCount.c AS KnownRatio
            FROM word_occurrences ti
            JOIN (
                SELECT t.Ti2SeID, COUNT(*) AS c
                FROM word_occurrences t
                WHERE t.Ti2WordCount = 1
                GROUP BY t.Ti2SeID
            ) AS sWordCount ON sWordCount.Ti2SeID = ti.Ti2SeID
            LEFT JOIN (
                SELECT t.Ti2SeID, COUNT(*) AS c
                FROM word_occurrences t
                WHERE t.Ti2WordCount = 1 AND t.Ti2WoID IS NULL
                GROUP BY t.Ti2SeID
            ) AS sUnknownCount ON sUnknownCount.Ti2SeID = ti.Ti2SeID
            WHERE ti.Ti2WoID = ?
            ORDER BY KnownRatio < 0.7, RAND()
            LIMIT 1";

        $rows = Connection::preparedFetchAll($sql, [$wordId]);
        $record = $rows[0] ?? null;

        if ($record === null) {
            return ['sentence' => null, 'found' => false];
        }

        $seid = (int) $record['SeID'];
        $sentenceCount = (int) Settings::getWithDefault('set-test-sentence-count');
        list($_, $sentence) = $this->sentenceService->formatSentence($seid, $wordlc, $sentenceCount);

        return ['sentence' => $sentence, 'found' => true];
    }

    /**
     * Get language settings for test display.
     *
     * @param int $langId Language ID
     *
     * @return array Language settings
     */
    public function getLanguageSettings(int $langId): array
    {
        $record = QueryBuilder::table('languages')
            ->select(['LgName', 'LgDict1URI', 'LgDict2URI', 'LgGoogleTranslateURI',
                'LgTextSize', 'LgRemoveSpaces', 'LgRegexpWordCharacters', 'LgRightToLeft',
                'LgTTSVoiceAPI'])
            ->where('LgID', '=', $langId)
            ->firstPrepared();

        if ($record === null) {
            return [];
        }

        return [
            'name' => $record['LgName'],
            'dict1Uri' => $record['LgDict1URI'] ?? '',
            'dict2Uri' => $record['LgDict2URI'] ?? '',
            'translateUri' => $record['LgGoogleTranslateURI'] ?? '',
            'textSize' => (int) $record['LgTextSize'],
            'removeSpaces' => (bool) $record['LgRemoveSpaces'],
            'regexWord' => $record['LgRegexpWordCharacters'],
            'rtl' => (bool) $record['LgRightToLeft'],
            'ttsVoiceApi' => $record['LgTTSVoiceAPI'] ?? null
        ];
    }

    /**
     * Get the language ID from test SQL.
     *
     * @param string          $reviewsql Test SQL projection with ? placeholders
     * @param array<int, int|string> $params    Bound parameters for the SQL
     *
     * @return int|null Language ID or null
     */
    public function getLanguageIdFromReviewSql(string $reviewsql, array $params = []): ?int
    {
        /** @var mixed $langIdRaw */
        $langIdRaw = Connection::preparedFetchValue(
            "SELECT WoLgID FROM $reviewsql LIMIT 1",
            $params,
            'WoLgID'
        );
        return is_numeric($langIdRaw) ? (int) $langIdRaw : null;
    }

    /**
     * Update word status during test.
     *
     * @param int $wordId    Word ID
     * @param int $newStatus New status (1-5)
     *
     * @return array{oldStatus: int, newStatus: int, oldScore: int, newScore: int}
     */
    public function updateWordStatus(int $wordId, int $newStatus): array
    {
        $oldStatus = (int) QueryBuilder::table('words')
            ->where('WoID', '=', $wordId)
            ->valuePrepared('WoStatus');

        $oldScore = (int) QueryBuilder::table('words')
            ->where('WoID', '=', $wordId)
            ->valuePrepared('GREATEST(0, ROUND(WoTodayScore, 0))');

        // Complex UPDATE with dynamic score calculation
        Connection::preparedExecute(
            "UPDATE words
            SET WoStatus = ?, WoStatusChanged = NOW(), " .
            TermStatusService::makeScoreRandomInsertUpdate('u') . "
            WHERE WoID = ?",
            [$newStatus, $wordId]
        );

        $newScore = (int) QueryBuilder::table('words')
            ->where('WoID', '=', $wordId)
            ->valuePrepared('GREATEST(0, ROUND(WoTodayScore, 0))');

        return [
            'oldStatus' => $oldStatus,
            'newStatus' => $newStatus,
            'oldScore' => $oldScore,
            'newScore' => $newScore
        ];
    }

    /**
     * Calculate new status based on status change direction.
     *
     * @param int $oldStatus Current status
     * @param int $change    Change amount (+1 or -1)
     *
     * @return int New status (clamped to 1-5)
     */
    public function calculateNewStatus(int $oldStatus, int $change): int
    {
        $newStatus = $oldStatus + $change;
        return max(1, min(5, $newStatus));
    }

    /**
     * Calculate status change direction.
     *
     * @param int $oldStatus Old status
     * @param int $newStatus New status
     *
     * @return int -1, 0, or 1
     */
    public function calculateStatusChange(int $oldStatus, int $newStatus): int
    {
        $diff = $newStatus - $oldStatus;
        if ($diff < 0) {
            return -1;
        }
        if ($diff > 0) {
            return 1;
        }
        return 0;
    }

    /**
     * Get word text by ID.
     *
     * @param int $wordId Word ID
     *
     * @return string|null Word text or null
     */
    public function getWordText(int $wordId): ?string
    {
        /** @var mixed $textRaw */
        $textRaw = QueryBuilder::table('words')
            ->where('WoID', '=', $wordId)
            ->valuePrepared('WoText');
        return is_string($textRaw) ? $textRaw : null;
    }

    /**
     * Clamp test type to valid range.
     *
     * @param int $testType Raw test type
     *
     * @return int Test type clamped to 1-5
     */
    public function clampReviewType(int $testType): int
    {
        return max(1, min(5, $testType));
    }

    /**
     * Check if test type is word mode (no sentence).
     *
     * @param int $testType Test type
     *
     * @return bool True if word mode (type > 3)
     */
    public function isWordMode(int $testType): bool
    {
        return $testType > 3;
    }

    /**
     * Get base test type (removes word mode offset).
     *
     * @param int $testType Test type
     *
     * @return int Base test type (1-3)
     */
    public function getBaseReviewType(int $testType): int
    {
        return $testType > 3 ? $testType - 3 : $testType;
    }

    /**
     * Get table test settings.
     *
     * @return array{edit: int, status: int, term: int, trans: int, rom: int, sentence: int}
     */
    public function getTableReviewSettings(): array
    {
        return [
            'edit' => Settings::getZeroOrOne('currenttabletestsetting1', 1),
            'status' => Settings::getZeroOrOne('currenttabletestsetting2', 1),
            'term' => Settings::getZeroOrOne('currenttabletestsetting3', 0),
            'trans' => Settings::getZeroOrOne('currenttabletestsetting4', 1),
            'rom' => Settings::getZeroOrOne('currenttabletestsetting5', 0),
            'sentence' => Settings::getZeroOrOne('currenttabletestsetting6', 1)
        ];
    }

    /**
     * Get words for table test.
     *
     * @param string          $reviewsql SQL projection string with ? placeholders
     * @param array<int, int|string> $params    Bound parameters for the SQL
     *
     * @return array<int, array<string, mixed>> Query results as array
     */
    public function getTableReviewWords(string $reviewsql, array $params = []): array
    {
        $sql = "SELECT DISTINCT WoID, WoText, WoTranslation, WoRomanization,
            WoSentence, WoStatus, WoTodayScore AS Score
            FROM $reviewsql AND WoStatus BETWEEN 1 AND 5
            AND WoTranslation != '' AND WoTranslation != '*'
            ORDER BY WoTodayScore, WoRandom * RAND()";

        return Connection::preparedFetchAll($sql, $params);
    }

    /**
     * Get test data from request parameters.
     *
     * @param int|null    $selection    Selection type
     * @param string|null $sessTestsql  Session test SQL
     * @param int|null    $langId       Language ID
     * @param int|null    $textId       Text ID
     *
     * @return array{title: string, property: string, reviewsql: string, reviewParams: array<int, int>, counts: array{due: int, total: int}}|null
     */
    public function getReviewDataFromParams(
        ?int $selection,
        ?string $sessTestsql,
        ?int $langId,
        ?int $textId
    ): ?array {
        $title = '';
        $property = '';
        $reviewsql = '';
        /** @var array<int, int> $reviewParams */
        $reviewParams = [];

        if ($selection !== null && $sessTestsql !== null) {
            $property = "selection=$selection";
            $result = $this->buildSelectionReviewSql($selection, $sessTestsql);

            if ($result === null) {
                return null;
            }
            $reviewsql = $result['sql'];
            $reviewParams = $result['params'];

            $validation = $this->validateReviewSelection($reviewsql, $reviewParams);
            if (!$validation['valid']) {
                return null;
            }

            $bindings = [];
            $userScope = UserScopedQuery::forTablePrepared('words', $bindings);
            $totalCount = (int) Connection::preparedFetchValue(
                "SELECT COUNT(DISTINCT WoID) AS cnt FROM $reviewsql" . $userScope,
                array_merge($reviewParams, $bindings),
                'cnt'
            );
            $title = 'Selected ' . $totalCount . ' Term' . ($totalCount < 2 ? '' : 's');

            $bindings2 = [];
            $userScope2 = UserScopedQuery::forTablePrepared('words', $bindings2);
            /** @var mixed $langNameRaw */
            $langNameRaw = Connection::preparedFetchValue(
                "SELECT LgName
                FROM languages, {$reviewsql} AND LgID = WoLgID"
                . $userScope2 . "
                LIMIT 1",
                array_merge($reviewParams, $bindings2),
                'LgName'
            );
            $langName = is_string($langNameRaw) ? $langNameRaw : null;
            if ($langName !== null && $langName !== '') {
                $title .= ' IN ' . $langName;
            }
        } elseif ($langId !== null) {
            $property = "lang=$langId";
            $reviewsql = " words WHERE WoLgID = ? ";
            $reviewParams = [$langId];

            /** @var mixed $langNameRawFromLang */
            $langNameRawFromLang = QueryBuilder::table('languages')
                ->where('LgID', '=', $langId)
                ->valuePrepared('LgName');
            $langName = is_string($langNameRawFromLang) ? $langNameRawFromLang : 'Unknown';
            $title = "All Terms in " . $langName;
        } elseif ($textId !== null) {
            $property = "text=$textId";
            $reviewsql = " words, word_occurrences WHERE Ti2LgID = WoLgID AND Ti2WoID = WoID AND Ti2TxID = ? ";
            $reviewParams = [$textId];

            /** @var mixed $titleRaw */
            $titleRaw = QueryBuilder::table('texts')
                ->where('TxID', '=', $textId)
                ->valuePrepared('TxTitle');
            $title = is_string($titleRaw) ? $titleRaw : 'Unknown Text';

            Settings::save('currenttext', (string) $textId);
        } else {
            return null;
        }

        $counts = $this->getReviewCounts($reviewsql, $reviewParams);

        return [
            'title' => $title,
            'property' => $property,
            'reviewsql' => $reviewsql,
            'reviewParams' => $reviewParams,
            'counts' => $counts
        ];
    }

    /**
     * Update session progress after test.
     *
     * @param int $statusChange Status change direction (-1, 0, or 1)
     *
     * @return array{total: int, wrong: int, correct: int, remaining: int}
     */
    public function updateSessionProgress(int $statusChange): array
    {
        $sessionData = $this->sessionManager->getRawSessionData();
        $total = $sessionData['total'];
        $wrong = $sessionData['wrong'];
        $correct = $sessionData['correct'];
        $remaining = $total - $correct - $wrong;

        if ($remaining > 0) {
            $isCorrect = $statusChange >= 0;
            $this->sessionManager->recordAnswer($isCorrect);

            if ($isCorrect) {
                $correct++;
            } else {
                $wrong++;
            }
            $remaining--;
        }

        return [
            'total' => $total,
            'wrong' => $wrong,
            'correct' => $correct,
            'remaining' => $remaining
        ];
    }

    /**
     * Initialize review session.
     *
     * @param int $totalDue Total words due for review
     *
     * @return void
     */
    public function initializeReviewSession(int $totalDue): void
    {
        $session = $this->sessionManager->getSession();
        if ($session !== null) {
            // Update existing session with new total
            $newSession = new \Lwt\Modules\Review\Domain\ReviewSession(
                time() + 2,
                $totalDue,
                0,
                0
            );
            $this->sessionManager->saveSession($newSession);
        } else {
            // Create new session
            $newSession = new \Lwt\Modules\Review\Domain\ReviewSession(
                time() + 2,
                $totalDue,
                0,
                0
            );
            $this->sessionManager->saveSession($newSession);
        }
    }

    /**
     * Get review session data.
     *
     * @return array{start: int, correct: int, wrong: int, total: int}
     */
    public function getReviewSessionData(): array
    {
        $data = $this->sessionManager->getRawSessionData();
        return [
            'start' => $data['start'],
            'correct' => $data['correct'],
            'wrong' => $data['wrong'],
            'total' => $data['total']
        ];
    }

    /**
     * Get test solution text.
     *
     * @param int                  $testType Test type (1-5)
     * @param array<string, mixed> $wordData Word record data
     * @param bool                 $wordMode Whether in word mode (no sentence)
     * @param string               $wordText Word text for display
     *
     * @return string Solution text
     */
    public function getTestSolution(
        int $testType,
        array $wordData,
        bool $wordMode,
        string $wordText
    ): string {
        $baseType = $this->getBaseReviewType($testType);

        if ($baseType == 1) {
            $tagList = TagsFacade::getWordTagList((int) $wordData['WoID'], false);
            $tagFormatted = $tagList !== '' ? ' [' . $tagList . ']' : '';
            $translation = isset($wordData['WoTranslation']) && is_string($wordData['WoTranslation'])
                ? $wordData['WoTranslation']
                : '';
            $trans = ExportService::replaceTabNewline($translation) . $tagFormatted;
            return $wordMode ? $trans : "[$trans]";
        }

        return $wordText;
    }

    /**
     * Get waiting time setting.
     *
     * @return int Waiting time in milliseconds
     */
    public function getWaitingTime(): int
    {
        return (int) Settings::getWithDefault('set-test-main-frame-waiting-time');
    }

    /**
     * Get edit frame waiting time setting.
     *
     * @return int Waiting time in milliseconds
     */
    public function getEditFrameWaitingTime(): int
    {
        return (int) Settings::getWithDefault('set-test-edit-frame-waiting-time');
    }
}
