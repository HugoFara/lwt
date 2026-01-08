<?php declare(strict_types=1);
/**
 * Review Service - Business logic for word review operations
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Review\Application\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Review\Application\Services;

use Lwt\Core\Globals;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;

use Lwt\Modules\Text\Application\Services\SentenceService;
use Lwt\Modules\Vocabulary\Application\Services\ExportService;
use Lwt\Modules\Vocabulary\Application\Services\TermStatusService;
use Lwt\Modules\Tags\Application\TagsFacade;

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
 * @link     https://hugofara.github.io/lwt/docs/php/
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
     * Constructor - initialize dependencies.
     *
     * @param SentenceService|null $sentenceService Sentence service (optional)
     */
    public function __construct(?SentenceService $sentenceService = null)
    {
        $this->sentenceService = $sentenceService ?? new SentenceService();
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
                    // Legacy behavior - direct SQL (deprecated)
                    return ['raw_sql', $sessTestsql];
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
     * Get SQL projection for test.
     *
     * @param string    $selector  Type of test ('words', 'texts', 'lang', 'text')
     * @param int|int[] $selection Selection value
     *
     * @return string|null SQL projection string
     */
    public function getReviewSql(string $selector, int|array $selection): ?string
    {
        $reviewsql = null;
        switch ($selector) {
            case 'words':
                // Test words in a list of words ID
                $idString = is_array($selection) ? implode(",", $selection) : (string)$selection;
                $reviewsql = " words WHERE WoID IN ($idString) ";
                // Note: Multi-language validation is done by caller via validateReviewSelection()
                break;
            case 'texts':
                // Test text items from a list of texts ID
                $idString = is_array($selection) ? implode(",", $selection) : (string)$selection;
                $reviewsql = " words, textitems2
                WHERE Ti2LgID = WoLgID AND Ti2WoID = WoID AND Ti2TxID IN ($idString) ";
                // Note: Multi-language validation is done by caller via validateReviewSelection()
                break;
            case 'lang':
                // Test words from a specific language
                $langId = is_array($selection) ? ($selection[0] ?? 0) : $selection;
                $reviewsql = " words WHERE WoLgID = $langId ";
                break;
            case 'text':
                // Test text items from a specific text ID
                $textId = is_array($selection) ? ($selection[0] ?? 0) : $selection;
                $reviewsql = " words, textitems2
                WHERE Ti2LgID = WoLgID AND Ti2WoID = WoID AND Ti2TxID = $textId ";
                break;
            default:
                throw new \InvalidArgumentException(
                    "Invalid selector '$selector': must be 'words', 'texts', 'lang', or 'text'"
                );
        }
        return $reviewsql;
    }

    /**
     * Validate test selection (check single language).
     *
     * @param string $reviewsql SQL projection string
     *
     * @return array{valid: bool, langCount: int, error: string|null}
     */
    public function validateReviewSelection(string $reviewsql): array
    {
        $langCount = (int) Connection::fetchValue(
            "SELECT COUNT(DISTINCT WoLgID) AS cnt FROM $reviewsql",
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
            $name = QueryBuilder::table('languages')
                ->where('LgID', '=', $lang)
                ->valuePrepared('LgName');
            return $name !== null ? (string) $name : 'L2';
        }

        if ($text !== null) {
            $row = QueryBuilder::table('texts')
                ->select(['LgName'])
                ->join('languages', 'TxLgID', '=', 'LgID')
                ->where('TxID', '=', $text)
                ->firstPrepared();
            $name = $row['LgName'] ?? null;
            return $name !== null ? (string) $name : 'L2';
        }

        if ($selection !== null && $reviewsql !== null) {
            $testSqlProjection = $this->buildSelectionReviewSql($selection, $reviewsql);
            if ($testSqlProjection !== null) {
                $validation = $this->validateReviewSelection($testSqlProjection);
                if ($validation['langCount'] == 1) {
                    $bindings = [];
                    $name = Connection::preparedFetchValue(
                        "SELECT LgName
                        FROM languages, {$testSqlProjection} AND LgID = WoLgID"
                        . UserScopedQuery::forTablePrepared('words', $bindings) . "
                        LIMIT 1",
                        $bindings,
                        'LgName'
                    );
                    return $name !== null ? (string) $name : 'L2';
                }
            }
        }

        return 'L2';
    }

    /**
     * Build test SQL from selection.
     *
     * @param int    $selectionType Selection type (2=words, 3=texts)
     * @param string $selectionData Comma-separated IDs
     *
     * @return string|null SQL projection string
     */
    public function buildSelectionReviewSql(int $selectionType, string $selectionData): ?string
    {
        $dataStringArray = explode(",", trim($selectionData, "()"));
        $dataIntArray = array_map('intval', $dataStringArray);
        switch ($selectionType) {
            case 2:
                $testSql = $this->getReviewSql('words', $dataIntArray);
                break;
            case 3:
                $testSql = $this->getReviewSql('texts', $dataIntArray);
                break;
            default:
                // Legacy: raw SQL passed directly
                // Note: Multi-language validation is done by caller via validateReviewSelection()
                $testSql = $selectionData;
        }
        return $testSql;
    }

    /**
     * Get test counts (due and total).
     *
     * @param string $reviewsql SQL projection string
     *
     * @return array{due: int, total: int}
     */
    public function getReviewCounts(string $reviewsql): array
    {
        $due = (int) Connection::fetchValue(
            "SELECT COUNT(DISTINCT WoID) AS cnt
            FROM $reviewsql AND WoStatus BETWEEN 1 AND 5
            AND WoTranslation != '' AND WoTranslation != '*' AND WoTodayScore < 0",
            'cnt'
        );

        $total = (int) Connection::fetchValue(
            "SELECT COUNT(DISTINCT WoID) AS cnt
            FROM $reviewsql AND WoStatus BETWEEN 1 AND 5
            AND WoTranslation != '' AND WoTranslation != '*'",
            'cnt'
        );

        return ['due' => $due, 'total' => $total];
    }

    /**
     * Get tomorrow's test count.
     *
     * @param string $reviewsql SQL projection string
     *
     * @return int Number of tests due tomorrow
     */
    public function getTomorrowReviewCount(string $reviewsql): int
    {
        return (int) Connection::fetchValue(
            "SELECT COUNT(DISTINCT WoID) AS cnt
            FROM $reviewsql AND WoStatus BETWEEN 1 AND 5
            AND WoTranslation != '' AND WoTranslation != '*' AND WoTomorrowScore < 0",
            'cnt'
        );
    }

    /**
     * Get the next word to test.
     *
     * @param string $reviewsql SQL projection string
     *
     * @return array|null Word record or null if none available
     */
    public function getNextWord(string $reviewsql): ?array
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

            $res = Connection::query($sql);
            if ($res instanceof \mysqli_result) {
                $record = mysqli_fetch_assoc($res);
                mysqli_free_result($res);

                if ($record !== null && $record !== false) {
                    return $record;
                }
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
        // textitems2 inherits user context via Ti2TxID -> texts FK, so no user_id needed
        $sql = "SELECT DISTINCT ti.Ti2SeID AS SeID,
            1 - IFNULL(sUnknownCount.c, 0) / sWordCount.c AS KnownRatio
            FROM textitems2 ti
            JOIN (
                SELECT t.Ti2SeID, COUNT(*) AS c
                FROM textitems2 t
                WHERE t.Ti2WordCount = 1
                GROUP BY t.Ti2SeID
            ) AS sWordCount ON sWordCount.Ti2SeID = ti.Ti2SeID
            LEFT JOIN (
                SELECT t.Ti2SeID, COUNT(*) AS c
                FROM textitems2 t
                WHERE t.Ti2WordCount = 1 AND t.Ti2WoID IS NULL
                GROUP BY t.Ti2SeID
            ) AS sUnknownCount ON sUnknownCount.Ti2SeID = ti.Ti2SeID
            WHERE ti.Ti2WoID = $wordId
            ORDER BY KnownRatio < 0.7, RAND()
            LIMIT 1";

        $res = Connection::query($sql);
        $record = null;
        if ($res instanceof \mysqli_result) {
            $record = mysqli_fetch_assoc($res);
            mysqli_free_result($res);
        }

        if ($record === null || $record === false) {
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
     * @param string $reviewsql Test SQL projection
     *
     * @return int|null Language ID or null
     */
    public function getLanguageIdFromReviewSql(string $reviewsql): ?int
    {
        $langId = Connection::fetchValue(
            "SELECT WoLgID FROM $reviewsql LIMIT 1",
            'WoLgID'
        );
        return $langId !== null ? (int) $langId : null;
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

        // Complex UPDATE with dynamic score calculation - use raw SQL
        Connection::execute(
            "UPDATE words
            SET WoStatus = $newStatus, WoStatusChanged = NOW(), " .
            TermStatusService::makeScoreRandomInsertUpdate('u') . "
            WHERE WoID = $wordId"
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
        $text = QueryBuilder::table('words')
            ->where('WoID', '=', $wordId)
            ->valuePrepared('WoText');
        return $text !== null ? (string) $text : null;
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
     * @param string $reviewsql SQL projection string
     *
     * @return \mysqli_result|bool Query result
     */
    public function getTableReviewWords(string $reviewsql): \mysqli_result|bool
    {
        $sql = "SELECT DISTINCT WoID, WoText, WoTranslation, WoRomanization,
            WoSentence, WoStatus, WoTodayScore AS Score
            FROM $reviewsql AND WoStatus BETWEEN 1 AND 5
            AND WoTranslation != '' AND WoTranslation != '*'
            ORDER BY WoTodayScore, WoRandom * RAND()";

        return Connection::query($sql);
    }

    /**
     * Get test data from request parameters.
     *
     * @param int|null    $selection    Selection type
     * @param string|null $sessTestsql  Session test SQL
     * @param int|null    $langId       Language ID
     * @param int|null    $textId       Text ID
     *
     * @return array{title: string, property: string, counts: array{due: int, total: int}}|null
     */
    public function getReviewDataFromParams(
        ?int $selection,
        ?string $sessTestsql,
        ?int $langId,
        ?int $textId
    ): ?array {
        if ($selection !== null && $sessTestsql !== null) {
            $property = "selection=$selection";
            $reviewsql = $this->buildSelectionReviewSql($selection, $sessTestsql);

            if ($reviewsql === null) {
                return null;
            }

            $validation = $this->validateReviewSelection($reviewsql);
            if (!$validation['valid']) {
                return null;
            }

            $bindings = [];
            $totalCount = (int) Connection::preparedFetchValue(
                "SELECT COUNT(DISTINCT WoID) AS cnt FROM $reviewsql"
                    . UserScopedQuery::forTablePrepared('words', $bindings),
                $bindings,
                'cnt'
            );
            $title = 'Selected ' . $totalCount . ' Term' . ($totalCount < 2 ? '' : 's');

            $bindings = [];
            $langName = Connection::preparedFetchValue(
                "SELECT LgName
                FROM languages, {$reviewsql} AND LgID = WoLgID"
                . UserScopedQuery::forTablePrepared('words', $bindings) . "
                LIMIT 1",
                $bindings,
                'LgName'
            );
            if ($langName) {
                $title .= ' IN ' . $langName;
            }
        } elseif ($langId !== null) {
            $property = "lang=$langId";
            $reviewsql = " words WHERE WoLgID = $langId ";

            $langName = QueryBuilder::table('languages')
                ->where('LgID', '=', $langId)
                ->valuePrepared('LgName');
            $title = "All Terms in " . ($langName ?? 'Unknown');
        } elseif ($textId !== null) {
            $property = "text=$textId";
            $reviewsql = " words, textitems2
                WHERE Ti2LgID = WoLgID AND Ti2WoID = WoID AND Ti2TxID = $textId ";

            $title = QueryBuilder::table('texts')
                ->where('TxID', '=', $textId)
                ->valuePrepared('TxTitle');
            $title = $title ?? 'Unknown Text';

            Settings::save('currenttext', (string) $textId);
        } else {
            return null;
        }

        $counts = $this->getReviewCounts($reviewsql);

        return [
            'title' => $title,
            'property' => $property,
            'reviewsql' => $reviewsql,
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
        $total = (int) ($_SESSION['reviewtotal'] ?? 0);
        $wrong = (int) ($_SESSION['reviewwrong'] ?? 0);
        $correct = (int) ($_SESSION['reviewcorrect'] ?? 0);
        $remaining = $total - $correct - $wrong;

        if ($remaining > 0) {
            if ($statusChange >= 0) {
                $correct++;
                $_SESSION['reviewcorrect'] = $correct;
            } else {
                $wrong++;
                $_SESSION['reviewwrong'] = $wrong;
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
        $_SESSION['reviewstart'] = time() + 2;
        $_SESSION['reviewcorrect'] = 0;
        $_SESSION['reviewwrong'] = 0;
        $_SESSION['reviewtotal'] = $totalDue;
    }

    /**
     * Get review session data.
     *
     * @return array{start: int, correct: int, wrong: int, total: int}
     */
    public function getReviewSessionData(): array
    {
        return [
            'start' => (int) ($_SESSION['reviewstart'] ?? 0),
            'correct' => (int) ($_SESSION['reviewcorrect'] ?? 0),
            'wrong' => (int) ($_SESSION['reviewwrong'] ?? 0),
            'total' => (int) ($_SESSION['reviewtotal'] ?? 0)
        ];
    }

    /**
     * Get test solution text.
     *
     * @param int    $testType Test type (1-5)
     * @param array  $wordData Word record data
     * @param bool   $wordMode Whether in word mode (no sentence)
     * @param string $wordText Word text for display
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
            $trans = ExportService::replaceTabNewline($wordData['WoTranslation']) . $tagFormatted;
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
