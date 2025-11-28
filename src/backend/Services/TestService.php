<?php

/**
 * Test Service - Business logic for word testing/review operations
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
use Lwt\Database\Settings;

require_once __DIR__ . '/../Core/Test/test_helpers.php';

/**
 * Service class for managing word tests/reviews.
 *
 * Handles test SQL generation, word selection, status updates,
 * and progress tracking for vocabulary testing.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class TestService
{
    private string $tbpref;

    public function __construct()
    {
        $this->tbpref = Globals::getTablePrefix();
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
    public function getTestIdentifier(
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
    public function getTestSql(string $selector, int|array $selection): ?string
    {
        return \do_test_test_get_projection($selector, $selection);
    }

    /**
     * Validate test selection (check single language).
     *
     * @param string $testsql SQL projection string
     *
     * @return array{valid: bool, langCount: int, error: string|null}
     */
    public function validateTestSelection(string $testsql): array
    {
        $langCount = (int) Connection::fetchValue(
            "SELECT COUNT(DISTINCT WoLgID) AS value FROM $testsql"
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
     * @param string|null $testsql   Test SQL for selection
     *
     * @return string Language name or 'L2' as default
     */
    public function getL2LanguageName(
        ?int $lang,
        ?int $text,
        ?int $selection = null,
        ?string $testsql = null
    ): string {
        if ($lang !== null) {
            $name = Connection::fetchValue(
                "SELECT LgName AS value FROM {$this->tbpref}languages
                WHERE LgID = $lang LIMIT 1"
            );
            return $name !== null ? (string) $name : 'L2';
        }

        if ($text !== null) {
            $name = Connection::fetchValue(
                "SELECT LgName AS value FROM {$this->tbpref}texts
                JOIN {$this->tbpref}languages ON TxLgID = LgID
                WHERE TxID = $text LIMIT 1"
            );
            return $name !== null ? (string) $name : 'L2';
        }

        if ($selection !== null && $testsql !== null) {
            $testSqlProjection = $this->buildSelectionTestSql($selection, $testsql);
            if ($testSqlProjection !== null) {
                $validation = $this->validateTestSelection($testSqlProjection);
                if ($validation['langCount'] == 1) {
                    $name = Connection::fetchValue(
                        "SELECT LgName AS value
                        FROM {$this->tbpref}languages, {$testSqlProjection} AND LgID = WoLgID
                        LIMIT 1"
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
    public function buildSelectionTestSql(int $selectionType, string $selectionData): ?string
    {
        return \do_test_test_from_selection($selectionType, $selectionData);
    }

    /**
     * Get test counts (due and total).
     *
     * @param string $testsql SQL projection string
     *
     * @return array{due: int, total: int}
     */
    public function getTestCounts(string $testsql): array
    {
        $due = (int) Connection::fetchValue(
            "SELECT COUNT(DISTINCT WoID) AS value
            FROM $testsql AND WoStatus BETWEEN 1 AND 5
            AND WoTranslation != '' AND WoTranslation != '*' AND WoTodayScore < 0"
        );

        $total = (int) Connection::fetchValue(
            "SELECT COUNT(DISTINCT WoID) AS value
            FROM $testsql AND WoStatus BETWEEN 1 AND 5
            AND WoTranslation != '' AND WoTranslation != '*'"
        );

        return ['due' => $due, 'total' => $total];
    }

    /**
     * Get tomorrow's test count.
     *
     * @param string $testsql SQL projection string
     *
     * @return int Number of tests due tomorrow
     */
    public function getTomorrowTestCount(string $testsql): int
    {
        return (int) Connection::fetchValue(
            "SELECT COUNT(DISTINCT WoID) AS value
            FROM $testsql AND WoStatus BETWEEN 1 AND 5
            AND WoTranslation != '' AND WoTranslation != '*' AND WoTomorrowScore < 0"
        );
    }

    /**
     * Get the next word to test.
     *
     * @param string $testsql SQL projection string
     *
     * @return array|null Word record or null if none available
     */
    public function getNextWord(string $testsql): ?array
    {
        $pass = 0;
        while ($pass < 2) {
            $pass++;
            $sql = "SELECT DISTINCT WoID, WoText, WoTextLC, WoTranslation,
                WoRomanization, WoSentence, WoLgID,
                (IFNULL(WoSentence, '') NOT LIKE CONCAT('%{', WoText, '}%')) AS notvalid,
                WoStatus,
                DATEDIFF(NOW(), WoStatusChanged) AS Days, WoTodayScore AS Score
                FROM $testsql AND WoStatus BETWEEN 1 AND 5
                AND WoTranslation != '' AND WoTranslation != '*' AND WoTodayScore < 0 " .
                ($pass == 1 ? 'AND WoRandom > RAND()' : '') . '
                ORDER BY WoTodayScore, WoRandom
                LIMIT 1';

            $res = Connection::query($sql);
            $record = mysqli_fetch_assoc($res);
            mysqli_free_result($res);

            if ($record) {
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
        $sql = "SELECT DISTINCT ti.Ti2SeID AS SeID,
            1 - IFNULL(sUnknownCount.c, 0) / sWordCount.c AS KnownRatio
            FROM {$this->tbpref}textitems2 ti
            JOIN (
                SELECT t.Ti2SeID, COUNT(*) AS c
                FROM {$this->tbpref}textitems2 t
                WHERE t.Ti2WordCount = 1
                GROUP BY t.Ti2SeID
            ) AS sWordCount ON sWordCount.Ti2SeID = ti.Ti2SeID
            LEFT JOIN (
                SELECT t.Ti2SeID, COUNT(*) AS c
                FROM {$this->tbpref}textitems2 t
                WHERE t.Ti2WordCount = 1 AND t.Ti2WoID = 0
                GROUP BY t.Ti2SeID
            ) AS sUnknownCount ON sUnknownCount.Ti2SeID = ti.Ti2SeID
            WHERE ti.Ti2WoID = $wordId
            ORDER BY KnownRatio < 0.7, RAND()
            LIMIT 1";

        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);

        if (!$record) {
            return ['sentence' => null, 'found' => false];
        }

        $seid = $record['SeID'];
        $sentenceCount = (int) Settings::getWithDefault('set-test-sentence-count');
        list($_, $sentence) = \getSentence($seid, $wordlc, $sentenceCount);

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
        $sql = "SELECT LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI,
            LgTextSize, LgRemoveSpaces, LgRegexpWordCharacters, LgRightToLeft,
            LgTTSVoiceAPI
            FROM {$this->tbpref}languages WHERE LgID = $langId";

        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);

        if (!$record) {
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
     * @param string $testsql Test SQL projection
     *
     * @return int|null Language ID or null
     */
    public function getLanguageIdFromTestSql(string $testsql): ?int
    {
        $langId = Connection::fetchValue(
            "SELECT WoLgID AS value FROM $testsql LIMIT 1"
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
        $oldStatus = (int) Connection::fetchValue(
            "SELECT WoStatus AS value FROM {$this->tbpref}words WHERE WoID = $wordId"
        );

        $oldScore = (int) Connection::fetchValue(
            "SELECT GREATEST(0, ROUND(WoTodayScore, 0)) AS value
            FROM {$this->tbpref}words WHERE WoID = $wordId"
        );

        Connection::execute(
            "UPDATE {$this->tbpref}words
            SET WoStatus = $newStatus, WoStatusChanged = NOW(), " .
            \make_score_random_insert_update('u') . "
            WHERE WoID = $wordId"
        );

        $newScore = (int) Connection::fetchValue(
            "SELECT GREATEST(0, ROUND(WoTodayScore, 0)) AS value
            FROM {$this->tbpref}words WHERE WoID = $wordId"
        );

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
        $text = Connection::fetchValue(
            "SELECT WoText AS value FROM {$this->tbpref}words WHERE WoID = $wordId"
        );
        return $text !== null ? (string) $text : null;
    }

    /**
     * Clamp test type to valid range.
     *
     * @param int $testType Raw test type
     *
     * @return int Test type clamped to 1-5
     */
    public function clampTestType(int $testType): int
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
    public function getBaseTestType(int $testType): int
    {
        return $testType > 3 ? $testType - 3 : $testType;
    }

    /**
     * Get table test settings.
     *
     * @return array{edit: int, status: int, term: int, trans: int, rom: int, sentence: int}
     */
    public function getTableTestSettings(): array
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
     * @param string $testsql SQL projection string
     *
     * @return \mysqli_result|false Query result
     */
    public function getTableTestWords(string $testsql)
    {
        $sql = "SELECT DISTINCT WoID, WoText, WoTranslation, WoRomanization,
            WoSentence, WoStatus, WoTodayScore AS Score
            FROM $testsql AND WoStatus BETWEEN 1 AND 5
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
    public function getTestDataFromParams(
        ?int $selection,
        ?string $sessTestsql,
        ?int $langId,
        ?int $textId
    ): ?array {
        $title = '';
        $property = '';
        $testsql = '';

        if ($selection !== null && $sessTestsql !== null) {
            $property = "selection=$selection";
            $testsql = $this->buildSelectionTestSql($selection, $sessTestsql);

            if ($testsql === null) {
                return null;
            }

            $validation = $this->validateTestSelection($testsql);
            if (!$validation['valid']) {
                return null;
            }

            $totalCount = (int) Connection::fetchValue(
                "SELECT COUNT(DISTINCT WoID) AS value FROM $testsql"
            );
            $title = 'Selected ' . $totalCount . ' Term' . ($totalCount < 2 ? '' : 's');

            $langName = Connection::fetchValue(
                "SELECT LgName AS value
                FROM {$this->tbpref}languages, {$testsql} AND LgID = WoLgID
                LIMIT 1"
            );
            if ($langName) {
                $title .= ' IN ' . $langName;
            }
        } elseif ($langId !== null) {
            $property = "lang=$langId";
            $testsql = " {$this->tbpref}words WHERE WoLgID = $langId ";

            $langName = Connection::fetchValue(
                "SELECT LgName AS value FROM {$this->tbpref}languages WHERE LgID = $langId"
            );
            $title = "All Terms in " . ($langName ?? 'Unknown');
        } elseif ($textId !== null) {
            $property = "text=$textId";
            $testsql = " {$this->tbpref}words, {$this->tbpref}textitems2
                WHERE Ti2LgID = WoLgID AND Ti2WoID = WoID AND Ti2TxID = $textId ";

            $title = Connection::fetchValue(
                "SELECT TxTitle AS value FROM {$this->tbpref}texts WHERE TxID = $textId"
            );
            $title = $title ?? 'Unknown Text';

            Settings::save('currenttext', (string) $textId);
        } else {
            return null;
        }

        $counts = $this->getTestCounts($testsql);

        return [
            'title' => $title,
            'property' => $property,
            'testsql' => $testsql,
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
        $total = (int) ($_SESSION['testtotal'] ?? 0);
        $wrong = (int) ($_SESSION['testwrong'] ?? 0);
        $correct = (int) ($_SESSION['testcorrect'] ?? 0);
        $remaining = $total - $correct - $wrong;

        if ($remaining > 0) {
            if ($statusChange >= 0) {
                $correct++;
                $_SESSION['testcorrect'] = $correct;
            } else {
                $wrong++;
                $_SESSION['testwrong'] = $wrong;
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
     * Initialize test session.
     *
     * @param int $totalDue Total words due for testing
     *
     * @return void
     */
    public function initializeTestSession(int $totalDue): void
    {
        $_SESSION['teststart'] = time() + 2;
        $_SESSION['testcorrect'] = 0;
        $_SESSION['testwrong'] = 0;
        $_SESSION['testtotal'] = $totalDue;
    }

    /**
     * Get test session data.
     *
     * @return array{start: int, correct: int, wrong: int, total: int}
     */
    public function getTestSessionData(): array
    {
        return [
            'start' => (int) ($_SESSION['teststart'] ?? 0),
            'correct' => (int) ($_SESSION['testcorrect'] ?? 0),
            'wrong' => (int) ($_SESSION['testwrong'] ?? 0),
            'total' => (int) ($_SESSION['testtotal'] ?? 0)
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
        $baseType = $this->getBaseTestType($testType);

        if ($baseType == 1) {
            $trans = \repl_tab_nl($wordData['WoTranslation']) .
                TagService::getWordTagListFormatted($wordData['WoID'], ' ', true, false);
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
