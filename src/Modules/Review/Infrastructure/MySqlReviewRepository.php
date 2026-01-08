<?php declare(strict_types=1);
/**
 * MySQL Review Repository
 *
 * Infrastructure implementation for review/test persistence.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Review\Infrastructure
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Review\Infrastructure;

use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;
use Lwt\Modules\Review\Domain\ReviewRepositoryInterface;
use Lwt\Modules\Review\Domain\TestConfiguration;
use Lwt\Modules\Review\Domain\TestWord;
use Lwt\Modules\Text\Application\Services\SentenceService;
use Lwt\Modules\Vocabulary\Application\Services\TermStatusService;

/**
 * MySQL implementation of ReviewRepositoryInterface.
 *
 * Handles all database operations for the Review module.
 *
 * @since 3.0.0
 */
class MySqlReviewRepository implements ReviewRepositoryInterface
{
    private SentenceService $sentenceService;

    /**
     * Constructor.
     *
     * @param SentenceService|null $sentenceService Sentence service (optional)
     */
    public function __construct(?SentenceService $sentenceService = null)
    {
        $this->sentenceService = $sentenceService ?? new SentenceService();
    }

    /**
     * {@inheritdoc}
     */
    public function findNextWordForTest(TestConfiguration $config): ?TestWord
    {
        $testsql = $config->toSqlProjection();
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
            if (!$res instanceof \mysqli_result) {
                continue;
            }
            $record = mysqli_fetch_assoc($res);
            mysqli_free_result($res);

            if ($record !== null && $record !== false) {
                return TestWord::fromRecord($record);
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getSentenceForWord(int $wordId, string $wordLc): array
    {
        // Find sentence with at least 70% known words
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
        if (!$res instanceof \mysqli_result) {
            return ['sentence' => null, 'found' => false];
        }
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);

        if ($record === null || $record === false) {
            return ['sentence' => null, 'found' => false];
        }

        $seid = (int) $record['SeID'];
        $sentenceCount = (int) Settings::getWithDefault('set-test-sentence-count');
        list($_, $sentence) = $this->sentenceService->formatSentence($seid, $wordLc, $sentenceCount);

        return ['sentence' => $sentence, 'found' => true];
    }

    /**
     * {@inheritdoc}
     */
    public function getTestCounts(TestConfiguration $config): array
    {
        $testsql = $config->toSqlProjection();

        $due = (int) Connection::fetchValue(
            "SELECT COUNT(DISTINCT WoID) AS cnt
            FROM $testsql AND WoStatus BETWEEN 1 AND 5
            AND WoTranslation != '' AND WoTranslation != '*' AND WoTodayScore < 0",
            'cnt'
        );

        $total = (int) Connection::fetchValue(
            "SELECT COUNT(DISTINCT WoID) AS cnt
            FROM $testsql AND WoStatus BETWEEN 1 AND 5
            AND WoTranslation != '' AND WoTranslation != '*'",
            'cnt'
        );

        return ['due' => $due, 'total' => $total];
    }

    /**
     * {@inheritdoc}
     */
    public function getTomorrowCount(TestConfiguration $config): int
    {
        $testsql = $config->toSqlProjection();

        return (int) Connection::fetchValue(
            "SELECT COUNT(DISTINCT WoID) AS cnt
            FROM $testsql AND WoStatus BETWEEN 1 AND 5
            AND WoTranslation != '' AND WoTranslation != '*' AND WoTomorrowScore < 0",
            'cnt'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTableWords(TestConfiguration $config): array
    {
        $testsql = $config->toSqlProjection();

        $sql = "SELECT DISTINCT WoID, WoText, WoTextLC, WoTranslation, WoRomanization,
            WoSentence, WoLgID, WoStatus, WoTodayScore AS Score,
            DATEDIFF(NOW(), WoStatusChanged) AS Days
            FROM $testsql AND WoStatus BETWEEN 1 AND 5
            AND WoTranslation != '' AND WoTranslation != '*'
            ORDER BY WoTodayScore, WoRandom * RAND()";

        $result = Connection::query($sql);
        $words = [];

        if ($result instanceof \mysqli_result) {
            while ($record = mysqli_fetch_assoc($result)) {
                $words[] = TestWord::fromRecord($record);
            }
            mysqli_free_result($result);
        }

        return $words;
    }

    /**
     * {@inheritdoc}
     */
    public function updateWordStatus(int $wordId, int $newStatus): array
    {
        $oldStatus = (int) QueryBuilder::table('words')
            ->where('WoID', '=', $wordId)
            ->valuePrepared('WoStatus');

        $oldScore = (int) QueryBuilder::table('words')
            ->where('WoID', '=', $wordId)
            ->valuePrepared('GREATEST(0, ROUND(WoTodayScore, 0))');

        // Update with score recalculation
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
     * {@inheritdoc}
     */
    public function getWordStatus(int $wordId): ?int
    {
        $status = QueryBuilder::table('words')
            ->where('WoID', '=', $wordId)
            ->valuePrepared('WoStatus');

        return $status !== null ? (int) $status : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLanguageSettings(int $langId): array
    {
        $record = QueryBuilder::table('languages')
            ->select([
                'LgName', 'LgDict1URI', 'LgDict2URI', 'LgGoogleTranslateURI',
                'LgTextSize', 'LgRemoveSpaces', 'LgRegexpWordCharacters',
                'LgRightToLeft', 'LgTTSVoiceAPI'
            ])
            ->where('LgID', '=', $langId)
            ->firstPrepared();

        if ($record === null) {
            return [
                'name' => '',
                'dict1Uri' => '',
                'dict2Uri' => '',
                'translateUri' => '',
                'textSize' => 100,
                'removeSpaces' => false,
                'regexWord' => '',
                'rtl' => false,
                'ttsVoiceApi' => null
            ];
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
     * {@inheritdoc}
     */
    public function getLanguageIdFromConfig(TestConfiguration $config): ?int
    {
        $testsql = $config->toSqlProjection();

        $langId = Connection::fetchValue(
            "SELECT WoLgID FROM $testsql LIMIT 1",
            'WoLgID'
        );

        return $langId !== null ? (int) $langId : null;
    }

    /**
     * {@inheritdoc}
     */
    public function validateSingleLanguage(TestConfiguration $config): array
    {
        $testsql = $config->toSqlProjection();

        $langCount = (int) Connection::fetchValue(
            "SELECT COUNT(DISTINCT WoLgID) AS cnt FROM $testsql",
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
     * {@inheritdoc}
     */
    public function getLanguageName(TestConfiguration $config): string
    {
        if ($config->testKey === TestConfiguration::KEY_LANG) {
            $name = QueryBuilder::table('languages')
                ->where('LgID', '=', $config->selection)
                ->valuePrepared('LgName');
            return $name !== null ? (string) $name : 'L2';
        }

        if ($config->testKey === TestConfiguration::KEY_TEXT) {
            $row = QueryBuilder::table('texts')
                ->select(['LgName'])
                ->join('languages', 'TxLgID', '=', 'LgID')
                ->where('TxID', '=', $config->selection)
                ->firstPrepared();
            return isset($row['LgName']) ? (string) $row['LgName'] : 'L2';
        }

        // For selection-based tests, get language from first word
        $testsql = $config->toSqlProjection();
        $validation = $this->validateSingleLanguage($config);

        if ($validation['langCount'] === 1) {
            $bindings = [];
            $name = Connection::preparedFetchValue(
                "SELECT LgName
                FROM languages, {$testsql} AND LgID = WoLgID"
                . UserScopedQuery::forTablePrepared('words', $bindings) . "
                LIMIT 1",
                $bindings,
                'LgName'
            );
            return $name !== null ? (string) $name : 'L2';
        }

        return 'L2';
    }

    /**
     * {@inheritdoc}
     */
    public function getWordText(int $wordId): ?string
    {
        $text = QueryBuilder::table('words')
            ->where('WoID', '=', $wordId)
            ->valuePrepared('WoText');

        return $text !== null ? (string) $text : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getTableTestSettings(): array
    {
        return [
            'edit' => Settings::getZeroOrOne('currenttabletestsetting1', 1),
            'status' => Settings::getZeroOrOne('currenttabletestsetting2', 1),
            'term' => Settings::getZeroOrOne('currenttabletestsetting3', 0),
            'trans' => Settings::getZeroOrOne('currenttabletestsetting4', 1),
            'rom' => Settings::getZeroOrOne('currenttabletestsetting5', 0),
            'sentence' => Settings::getZeroOrOne('currenttabletestsetting6', 1),
            'contextRom' => Settings::getZeroOrOne('currenttabletestsetting7', 0),
            'contextTrans' => Settings::getZeroOrOne('currenttabletestsetting8', 0)
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return array{
     *     sentence: string|null,
     *     sentenceId: int|null,
     *     found: bool,
     *     annotations: array<int, array{text: string, romanization: string|null, translation: string|null, isTarget: bool, order: int}>
     * }
     */
    public function getSentenceWithAnnotations(int $wordId, string $wordLc): array
    {
        // First, find the best sentence (same logic as getSentenceForWord)
        $sql = "SELECT DISTINCT ti.Ti2SeID AS SeID, ti.Ti2TxID AS TxID,
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
        if (!$res instanceof \mysqli_result) {
            return ['sentence' => null, 'sentenceId' => null, 'found' => false, 'annotations' => []];
        }
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);

        if ($record === null || $record === false) {
            return ['sentence' => null, 'sentenceId' => null, 'found' => false, 'annotations' => []];
        }

        $seid = (int) $record['SeID'];
        $txid = (int) $record['TxID'];
        $sentenceCount = (int) Settings::getWithDefault('set-test-sentence-count');

        // Get the formatted sentence
        list($_, $sentence) = $this->sentenceService->formatSentence($seid, $wordLc, $sentenceCount);

        // Now fetch all word annotations for this sentence (and surrounding sentences if mode > 1)
        $annotations = $this->fetchSentenceAnnotations($seid, $txid, $sentenceCount, $wordLc);

        return [
            'sentence' => $sentence,
            'sentenceId' => $seid,
            'found' => true,
            'annotations' => $annotations
        ];
    }

    /**
     * Fetch word annotations for a sentence and surrounding context.
     *
     * @param int    $seid          Main sentence ID
     * @param int    $txid          Text ID
     * @param int    $sentenceCount Number of sentences (1=current, 2=prev+current, 3=prev+current+next)
     * @param string $targetWordLc  Lowercase target word text
     *
     * @return array<int, array{text: string, romanization: string|null, translation: string|null, isTarget: bool, order: int}>
     */
    private function fetchSentenceAnnotations(int $seid, int $txid, int $sentenceCount, string $targetWordLc): array
    {
        // Build list of sentence IDs to include
        $sentenceIds = [$seid];

        if ($sentenceCount > 1) {
            // Get previous sentence
            $prevSeid = Connection::fetchValue(
                "SELECT SeID FROM sentences
                WHERE SeID < $seid AND SeTxID = $txid
                AND TRIM(SeText) NOT IN ('¶', '')
                ORDER BY SeID DESC LIMIT 1",
                'SeID'
            );
            if ($prevSeid !== null) {
                array_unshift($sentenceIds, (int) $prevSeid);
            }
        }

        if ($sentenceCount > 2) {
            // Get next sentence
            $nextSeid = Connection::fetchValue(
                "SELECT SeID FROM sentences
                WHERE SeID > $seid AND SeTxID = $txid
                AND TRIM(SeText) NOT IN ('¶', '')
                ORDER BY SeID ASC LIMIT 1",
                'SeID'
            );
            if ($nextSeid !== null) {
                $sentenceIds[] = (int) $nextSeid;
            }
        }

        if (empty($sentenceIds)) {
            return [];
        }

        $seidList = implode(',', $sentenceIds);

        // Fetch all text items with their word data
        $sql = "SELECT ti.Ti2Order, ti.Ti2Text, ti.Ti2WordCount, ti.Ti2WoID,
                w.WoTextLC, w.WoRomanization, w.WoTranslation
            FROM textitems2 ti
            LEFT JOIN words w ON ti.Ti2WoID = w.WoID
            WHERE ti.Ti2SeID IN ($seidList) AND ti.Ti2WordCount < 2
            AND ti.Ti2Text != '¶'
            ORDER BY ti.Ti2Order";

        $result = Connection::query($sql);
        if (!$result instanceof \mysqli_result) {
            return [];
        }

        $annotations = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $order = (int) $row['Ti2Order'];
            $text = (string) $row['Ti2Text'];
            $woId = $row['Ti2WoID'];
            $isTarget = mb_strtolower($text, 'UTF-8') === $targetWordLc;

            // Only include annotation data if the word is known (has a WoID)
            if ($woId !== null) {
                $romanization = $row['WoRomanization'];
                $annotations[$order] = [
                    'text' => $text,
                    'romanization' => ($romanization === null || $romanization === '') ? null : (string)$romanization,
                    'translation' => $this->getFirstTranslation((string)($row['WoTranslation'] ?? '')),
                    'isTarget' => $isTarget,
                    'order' => $order
                ];
            } else {
                // Still track the position for non-word tokens (punctuation, etc.)
                $annotations[$order] = [
                    'text' => $text,
                    'romanization' => null,
                    'translation' => null,
                    'isTarget' => false,
                    'order' => $order
                ];
            }
        }
        mysqli_free_result($result);

        return $annotations;
    }

    /**
     * Get the first translation from a translation string.
     *
     * @param string $trans Full translation string (may contain separators)
     *
     * @return string|null First translation only, or null if empty
     */
    private function getFirstTranslation(string $trans): ?string
    {
        if ($trans === '' || $trans === '*') {
            return null;
        }
        $arr = preg_split('/[' . \Lwt\Core\StringUtils::getSeparators() . ']/u', $trans);
        if ($arr === false) {
            return null;
        }
        $r = trim($arr[0]);
        return $r !== '' && $r !== '*' ? $r : null;
    }
}
