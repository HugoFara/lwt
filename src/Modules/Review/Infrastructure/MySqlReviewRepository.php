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

use Lwt\Database\Connection;
use Lwt\Database\QueryBuilder;
use Lwt\Database\Settings;
use Lwt\Database\UserScopedQuery;
use Lwt\Modules\Review\Domain\ReviewRepositoryInterface;
use Lwt\Modules\Review\Domain\TestConfiguration;
use Lwt\Modules\Review\Domain\TestWord;
use Lwt\Services\SentenceService;
use Lwt\Modules\Vocabulary\Application\Services\TermStatusService;

require_once __DIR__ . '/../../../backend/Services/SentenceService.php';

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
            $record = mysqli_fetch_assoc($res);
            mysqli_free_result($res);

            if ($record) {
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
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);

        if (!$record) {
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

        while ($record = mysqli_fetch_assoc($result)) {
            $words[] = TestWord::fromRecord($record);
        }
        mysqli_free_result($result);

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

        if (!$record) {
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
            'sentence' => Settings::getZeroOrOne('currenttabletestsetting6', 1)
        ];
    }
}
