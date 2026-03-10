<?php

/**
 * Word List Service - Business logic for word list/edit operations
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Vocabulary\Application\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Application\Services;

use Lwt\Shared\Infrastructure\Globals;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\DB;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Shared\Infrastructure\Database\Maintenance;
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;
use Lwt\Modules\Vocabulary\Application\Helpers\StatusHelper;
use Lwt\Modules\Vocabulary\Application\Services\ExportService;
use Lwt\Modules\Language\Application\LanguageFacade;

/**
 * Service class for managing word list operations.
 *
 * Handles filtering, pagination, bulk operations on words list.
 *
 * @category   Lwt
 * @package    Lwt\Modules\Vocabulary\Application\Services
 * @author     HugoFara <hugo.farajallah@protonmail.com>
 * @license    Unlicense <http://unlicense.org/>
 * @link       https://hugofara.github.io/lwt/developer/api
 * @since      3.0.0
 */
class WordListService
{
    /**
     * Build query condition for language filter.
     *
     * @param string $langId Language ID
     *
     * @return string SQL condition
     */
    public function buildLangCondition(string $langId, ?array &$params = null): string
    {
        if ($langId == '') {
            return '';
        }
        if ($params !== null) {
            $params[] = (int)$langId;
            return ' and WoLgID = ?';
        }
        return ' and WoLgID=' . (int)$langId;
    }

    /**
     * Build query condition for status filter.
     *
     * @param string $status Status code
     *
     * @return string SQL condition
     */
    public function buildStatusCondition(string $status): string
    {
        if ($status == '') {
            return '';
        }
        return ' and ' . StatusHelper::makeCondition('WoStatus', (int)$status);
    }

    /**
     * Build query condition for search query with prepared statement parameters.
     *
     * NOTE: When upgrading calling code, pass a $params array by reference to get
     * parameterized queries. For backward compatibility, if $params is null,
     * this returns old-style SQL with embedded values (using mysqli_real_escape_string).
     *
     * @param string     $query     Search query
     * @param string     $queryMode Query mode (term, rom, transl, etc.)
     * @param string     $regexMode Regex mode ('' or 'r')
     * @param array|null &$params   Optional: Reference to params array for prepared statements
     *
     * @return string SQL condition (with ? placeholders if $params provided, or embedded values if not)
     */
    public function buildQueryCondition(
        string $query,
        string $queryMode,
        string $regexMode,
        ?array &$params = null
    ): string {
        if ($query === '') {
            return '';
        }

        /** @var string $queryValue */
        $queryValue = ($regexMode == '') ?
            str_replace("*", "%", mb_strtolower($query, 'UTF-8')) :
            $query;

        $op = $regexMode . 'like';

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

        // If $params is provided, use prepared statements with ? placeholders
        if ($params !== null) {
            $conditions = [];
            foreach ($fields as $field) {
                $conditions[] = "{$field} {$op} ?";
                $params[] = $queryValue;
            }
            return ' and (' . implode(' or ', $conditions) . ')';
        }

        // Backward compatibility: build old-style SQL with embedded values
        // Using mysqli_real_escape_string directly instead of Escaping::toSqlSyntax()
        $dbConn = Globals::getDbConnection();
        if ($dbConn === null) {
            return '';
        }
        $escapedValue = "'" . (string) mysqli_real_escape_string($dbConn, $queryValue) . "'";

        $whQuery = "{$op} {$escapedValue}";

        switch ($queryMode) {
            case 'term,rom,transl':
                return " and (WoText $whQuery or IFNULL(WoRomanization,'*') $whQuery or WoTranslation $whQuery)";
            case 'term,rom':
                return " and (WoText $whQuery or IFNULL(WoRomanization,'*') $whQuery)";
            case 'rom,transl':
                return " and (IFNULL(WoRomanization,'*') $whQuery or WoTranslation $whQuery)";
            case 'term,transl':
                return " and (WoText $whQuery or WoTranslation $whQuery)";
            case 'term':
                return " and (WoText $whQuery)";
            case 'rom':
                return " and (IFNULL(WoRomanization,'*') $whQuery)";
            case 'transl':
                return " and (WoTranslation $whQuery)";
            default:
                return " and (WoText $whQuery or IFNULL(WoRomanization,'*') $whQuery or WoTranslation $whQuery)";
        }
    }

    /**
     * Validate a regex pattern.
     *
     * @param string $pattern The regex pattern to validate
     *
     * @return bool True if valid, false otherwise
     */
    public function validateRegexPattern(string $pattern): bool
    {
        try {
            Connection::preparedFetchValue('SELECT "test" RLIKE ?', [$pattern]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Build tag filter condition.
     *
     * @param string $tag1  First tag ID (must be numeric or empty)
     * @param string $tag2  Second tag ID (must be numeric or empty)
     * @param string $tag12 Tag logic (0=OR, 1=AND)
     *
     * @return string SQL HAVING clause
     */
    public function buildTagCondition(string $tag1, string $tag2, string $tag12, ?array &$params = null): string
    {
        if ($tag1 == '' && $tag2 == '') {
            return '';
        }

        // Sanitize tag IDs to prevent SQL injection - cast to int for safety
        // Non-numeric strings become null and are ignored
        $tag1Int = ($tag1 !== '' && is_numeric($tag1)) ? (int)$tag1 : null;
        $tag2Int = ($tag2 !== '' && is_numeric($tag2)) ? (int)$tag2 : null;

        $whTag1 = null;
        $whTag2 = null;

        if ($tag1Int !== null) {
            if ($tag1Int === -1) {
                $whTag1 = "group_concat(WtTgID) IS NULL";
            } elseif ($params !== null) {
                $whTag1 = "concat('/',group_concat(WtTgID separator '/'),'/') like concat('%/', ?, '/%')";
                $params[] = $tag1Int;
            } else {
                $whTag1 = "concat('/',group_concat(WtTgID separator '/'),'/') like '%/" . $tag1Int . "/%'";
            }
        }

        if ($tag2Int !== null) {
            if ($tag2Int === -1) {
                $whTag2 = "group_concat(WtTgID) IS NULL";
            } elseif ($params !== null) {
                $whTag2 = "concat('/',group_concat(WtTgID separator '/'),'/') like concat('%/', ?, '/%')";
                $params[] = $tag2Int;
            } else {
                $whTag2 = "concat('/',group_concat(WtTgID separator '/'),'/') like '%/" . $tag2Int . "/%'";
            }
        }

        if ($whTag1 !== null && $whTag2 === null) {
            return " having (" . $whTag1 . ') ';
        } elseif ($whTag2 !== null && $whTag1 === null) {
            return " having (" . $whTag2 . ') ';
        } elseif ($whTag1 === null && $whTag2 === null) {
            return '';
        } else {
            return " having ((" . $whTag1 . ($tag12 ? ') AND (' : ') OR (') . $whTag2 . ")) ";
        }
    }

    /**
     * Count words matching the filter criteria.
     *
     * @param string $textId  Text ID filter (comma-separated IDs or empty)
     * @param string $whLang  Language condition (with ? placeholders)
     * @param string $whStat  Status condition
     * @param string $whQuery Query condition (with ? placeholders)
     * @param string $whTag   Tag condition (with ? placeholders)
     * @param array  $params  Merged binding parameters for filters
     *
     * @return int Number of matching words
     */
    public function countWords(
        string $textId,
        string $whLang,
        string $whStat,
        string $whQuery,
        string $whTag,
        array $params = []
    ): int {
        if ($textId == '') {
            $bindings = $params;
            $sql = 'select count(*) as value from (select WoID from (' .
                'words left JOIN word_tag_map' .
                ' ON WoID = WtWoID) where (1=1) ' .
                $whLang . $whStat . $whQuery . ' group by WoID ' . $whTag . ') as dummy';
        } else {
            $bindings = [];
            $textIds = array_map('intval', explode(',', $textId));
            $inClause = Connection::buildPreparedInClause($textIds, $bindings);
            /** @var array<int, mixed> $bindings */
            $bindings = array_values(array_merge($bindings, $params));
            $sql = 'select count(*) as value from (select WoID from (' .
                'words left JOIN word_tag_map' .
                ' ON WoID = WtWoID), word_occurrences' .
                ' where Ti2LgID = WoLgID and Ti2WoID = WoID and Ti2TxID in ' .
                $inClause . $whLang . $whStat . $whQuery .
                ' group by WoID ' . $whTag . ') as dummy';
        }
        return (int) Connection::preparedFetchValue($sql, $bindings);
    }

    /**
     * Get words list for display.
     *
     * @param array{whLang?: string, whStat?: string, whQuery?: string,
     *               whTag?: string, textId?: string, params?: array} $filters Filter parameters
     * @param int   $sort    Sort column index
     * @param int   $page    Page number
     * @param int   $perPage Items per page
     *
     * @return array Array of word records
     */
    public function getWordsList(array $filters, int $sort, int $page, int $perPage): array
    {
        $sorts = [
            'WoTextLC',
            'lower(WoTranslation)',
            'WoID desc',
            'WoID asc',
            'WoStatus, WoTextLC',
            'WoTodayScore',
            'textswordcount desc, WoTextLC asc'
        ];

        $lsorts = count($sorts);
        if ($sort < 1) {
            $sort = 1;
        }
        if ($sort > $lsorts) {
            $sort = $lsorts;
        }

        $whLang = $filters['whLang'] ?? '';
        $whStat = $filters['whStat'] ?? '';
        $whQuery = $filters['whQuery'] ?? '';
        $whTag = $filters['whTag'] ?? '';
        $textId = $filters['textId'] ?? '';
        $filterParams = $filters['params'] ?? [];

        if ($sort == 7) {
            // Sort by word count in texts
            return $this->getWordsListWithWordCount($filters, $sorts[$sort - 1]);
        }

        $offset = ($page - 1) * $perPage;

        if ($textId == '') {
            if ($whTag == '') {
                $bindings = $filterParams;
                $bindings[] = $offset;
                $bindings[] = $perPage;
                $sql = 'select WoID, WoText, WoTranslation, WoRomanization, WoSentence,
                        SentOK, WoStatus, LgName, LgRightToLeft, LgGoogleTranslateURI, Days,
                        WoTodayScore AS Score, WoTomorrowScore AS Score2,
                        ifnull(group_concat(distinct TgText order by TgText separator \',\'),\'\') as taglist
                        from (select WoID, WoTextLC, WoText, WoTranslation, WoRomanization,
                        WoSentence,
                        ifnull(WoSentence,\'\') like concat(\'%{\',WoText,\'}%\') as SentOK,
                        WoStatus, LgName, LgRightToLeft, LgGoogleTranslateURI,
                        DATEDIFF( NOW( ) , WoStatusChanged ) AS Days, WoTodayScore,
                        WoTomorrowScore
                        from words, languages
                        where WoLgID = LgID ' . $whLang . $whStat . $whQuery . '
                        group by WoID
                        order by ' . $sorts[$sort - 1] . ' LIMIT ?, ?) AS AA
                        left JOIN word_tag_map ON WoID = WtWoID
                        left join tags on TgID = WtTgID
                        group by WoID
                        order by ' . $sorts[$sort - 1];
            } else {
                $bindings = $filterParams;
                $bindings[] = $offset;
                $bindings[] = $perPage;
                $sql = 'select WoID, WoText, WoTranslation, WoRomanization, WoSentence,
                        ifnull(WoSentence,\'\') like concat(\'%{\',WoText,\'}%\') as SentOK,
                        WoStatus, LgName, LgRightToLeft, LgGoogleTranslateURI,
                        DATEDIFF( NOW( ) , WoStatusChanged ) AS Days, WoTodayScore AS Score,
                        WoTomorrowScore AS Score2,
                        ifnull(group_concat(distinct TgText order by TgText separator \',\'),\'\') as taglist
                        from ((words left JOIN word_tag_map
                        ON WoID = WtWoID) left join tags
                        on TgID = WtTgID), languages
                        where WoLgID = LgID ' . $whLang . $whStat . $whQuery .
                        ' group by WoID ' . $whTag . ' order by ' . $sorts[$sort - 1] . ' LIMIT ?, ?';
            }
        } else {
            $bindings = [];
            $textIds = array_map('intval', explode(',', $textId));
            $inClause = Connection::buildPreparedInClause($textIds, $bindings);
            /** @var array<int, mixed> $bindings */
            $bindings = array_values(array_merge($bindings, $filterParams));
            $bindings[] = $offset;
            $bindings[] = $perPage;
            $sql = 'select distinct WoID, WoText, WoTranslation, WoRomanization,
                    WoSentence, ifnull(WoSentence,\'\') like \'%{%}%\' as SentOK, WoStatus,
                    LgName, LgRightToLeft, LgGoogleTranslateURI,
                    DATEDIFF( NOW( ) , WoStatusChanged ) AS Days, WoTodayScore AS Score,
                    WoTomorrowScore AS Score2,
                    ifnull(group_concat(distinct TgText order by TgText separator \',\'),\'\') as taglist
                    from ((words
                    left JOIN word_tag_map ON WoID = WtWoID)
                    left join tags on TgID = WtTgID),
                    languages, word_occurrences
                    where Ti2LgID = WoLgID and Ti2WoID = WoID and Ti2TxID in ' .
                    $inClause . ' and WoLgID = LgID ' . $whLang . $whStat . $whQuery . '
                    group by WoID ' . $whTag . '
                    order by ' . $sorts[$sort - 1] . ' LIMIT ?, ?';
        }

        return Connection::preparedFetchAll($sql, $bindings);
    }

    /**
     * Get words list with word count (for sort option 7).
     *
     * @param array{whLang?: string, whStat?: string, whQuery?: string,
     *               whTag?: string, textId?: string, params?: array} $filters Filter parameters
     * @param string $sortExpr Sort expression
     *
     * @return array Array of word records
     */
    private function getWordsListWithWordCount(array $filters, string $sortExpr): array
    {
        $whLang = $filters['whLang'] ?? '';
        $whStat = $filters['whStat'] ?? '';
        $whQuery = $filters['whQuery'] ?? '';
        $whTag = $filters['whTag'] ?? '';
        $textId = $filters['textId'] ?? '';
        $filterParams = $filters['params'] ?? [];

        if ($textId != '') {
            $bindings = [];
            $textIds = array_map('intval', explode(',', $textId));
            $inClause = Connection::buildPreparedInClause($textIds, $bindings);
            /** @var array<int, mixed> $bindings */
            $bindings = array_values(array_merge($bindings, $filterParams));
            $sql = 'select WoID, count(WoID) AS textswordcount, WoText, WoTranslation,
                    WoRomanization, WoSentence,
                    ifnull(WoSentence,\'\') like concat(\'%{\',WoText,\'}%\') as SentOK,
                    WoStatus, LgName, LgRightToLeft, LgGoogleTranslateURI,
                    DATEDIFF( NOW( ) , WoStatusChanged ) AS Days, WoTodayScore AS Score,
                    WoTomorrowScore AS Score2,
                    ifnull(group_concat(distinct TgText order by TgText separator \',\'),\'\') as taglist,
                    WoTextLC, WoTodayScore
                    from ((words left JOIN word_tag_map
                    ON WoID = WtWoID)
                    left join tags on TgID = WtTgID),
                    languages, word_occurrences
                    where Ti2LgID = WoLgID and Ti2WoID = WoID and WoLgID = LgID
                    and Ti2TxID in ' . $inClause . ' ' .
                    $whLang . $whStat . $whQuery . ' group by WoID ' . $whTag .
                    ' order by ' . $sortExpr;
        } else {
            // UNION query: first part = words NOT in any text, second = words with occurrences
            // Both parts share the same filter params, so we need to duplicate them
            $bindings = array_merge($filterParams, $filterParams);
            $sql = 'select WoID, 0 AS textswordcount, WoText, WoTranslation,
                    WoRomanization, WoSentence,
                    ifnull(WoSentence,\'\') like concat(\'%{\',WoText,\'}%\') as SentOK,
                    WoStatus, LgName, LgRightToLeft, LgGoogleTranslateURI,
                    DATEDIFF( NOW( ) , WoStatusChanged ) AS Days, WoTodayScore AS Score,
                    WoTomorrowScore AS Score2,
                    ifnull(group_concat(distinct TgText order by TgText separator \',\'),\'\') as taglist,
                    WoTextLC, WoTodayScore
                    from ((words left JOIN word_tag_map
                    ON WoID = WtWoID)
                    left join tags on TgID = WtTgID),
                    languages
                    where WoLgID = LgID and WoID NOT IN (SELECT DISTINCT Ti2WoID
                    from word_occurrences where Ti2LgID = LgID) ' .
                    $whLang . $whStat . $whQuery . '
                    group by WoID ' . $whTag . '
                    UNION
                    select WoID, count(WoID) AS textswordcount, WoText, WoTranslation,
                    WoRomanization, WoSentence,
                    ifnull(WoSentence,\'\') like concat(\'%{\',WoText,\'}%\') as SentOK,
                    WoStatus, LgName, LgRightToLeft, LgGoogleTranslateURI,
                    DATEDIFF( NOW( ) , WoStatusChanged ) AS Days, WoTodayScore AS Score,
                    WoTomorrowScore AS Score2,
                    ifnull(group_concat(distinct TgText order by TgText separator \',\'),\'\') as taglist,
                    WoTextLC, WoTodayScore
                    from ((words left JOIN word_tag_map
                    ON WoID = WtWoID)
                    left join tags on TgID = WtTgID),
                    languages, word_occurrences
                    where Ti2LgID = WoLgID and Ti2WoID = WoID and WoLgID = LgID ' .
                    $whLang . $whStat . $whQuery . ' group by WoID ' . $whTag .
                    ' order by ' . $sortExpr;
        }

        return Connection::preparedFetchAll($sql, $bindings);
    }

    /**
     * Delete multiple words by ID list.
     *
     * @param int[] $ids Array of word IDs
     *
     * @return string Result message
     */
    public function deleteByIdList(array $ids): string
    {
        $inClause = Connection::buildIntInClause($ids);

        DB::beginTransaction();
        try {
            // Delete multi-word text items first (before word deletion triggers FK SET NULL)
            Connection::query(
                'DELETE FROM word_occurrences
                WHERE Ti2WordCount > 1 AND Ti2WoID in ' . $inClause
            );

            // Delete words - FK constraints handle:
            // - Single-word word_occurrences.Ti2WoID set to NULL (ON DELETE SET NULL)
            // - word_tag_map deleted (ON DELETE CASCADE)
            $message = Connection::execute(
                'DELETE FROM words WHERE WoID in ' . $inClause,
                "Deleted"
            );

            Maintenance::adjustAutoIncrement('words', 'WoID');

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollback();
            throw $e;
        }

        return (string) $message;
    }

    /**
     * Update status for words in ID list.
     *
     * @param int[]  $ids        Array of word IDs
     * @param int    $newStatus  New status value
     * @param bool   $relative   If true, change by +1 or -1
     * @param string $actionType Type of action (spl1, smi1, s5, s1, s99, s98)
     *
     * @return string Result message
     */
    public function updateStatusByIdList(array $ids, int $newStatus, bool $relative, string $actionType): string
    {
        $inClause = Connection::buildIntInClause($ids);
        $scoreUpdate = TermStatusService::makeScoreRandomInsertUpdate('u');

        if ($relative && $newStatus > 0) {
            // Status +1
            return (string) Connection::execute(
                'update words
                set WoStatus=WoStatus+1, WoStatusChanged = NOW(),' . $scoreUpdate . '
                where WoStatus in (1,2,3,4) and WoID in ' . $inClause,
                "Updated Status (+1)"
            );
        } elseif ($relative && $newStatus < 0) {
            // Status -1
            return (string) Connection::execute(
                'update words
                set WoStatus=WoStatus-1, WoStatusChanged = NOW(),' . $scoreUpdate . '
                where WoStatus in (2,3,4,5) and WoID in ' . $inClause,
                "Updated Status (-1)"
            );
        }

        // Absolute status
        return (string) Connection::execute(
            'update words
            set WoStatus=' . $newStatus . ', WoStatusChanged = NOW(),' . $scoreUpdate . '
            where WoID in ' . $inClause,
            "Updated Status (=" . $newStatus . ")"
        );
    }

    /**
     * Update status date to NOW for words in ID list.
     *
     * @param int[] $ids Array of word IDs
     *
     * @return string Result message
     */
    public function updateStatusDateByIdList(array $ids): string
    {
        $inClause = Connection::buildIntInClause($ids);

        return (string) Connection::execute(
            'update words
            set WoStatusChanged = NOW(),' . TermStatusService::makeScoreRandomInsertUpdate('u') . '
            where WoID in ' . $inClause,
            "Updated Status Date (= Now)"
        );
    }

    /**
     * Delete sentences for words in ID list.
     *
     * @param int[] $ids Array of word IDs
     *
     * @return string Result message
     */
    public function deleteSentencesByIdList(array $ids): string
    {
        $inClause = Connection::buildIntInClause($ids);

        return (string) Connection::execute(
            'update words
            set WoSentence = NULL
            where WoID in ' . $inClause,
            "Term Sentence(s) deleted"
        );
    }

    /**
     * Convert words to lowercase in ID list.
     *
     * @param int[] $ids Array of word IDs
     *
     * @return string Result message
     */
    public function toLowercaseByIdList(array $ids): string
    {
        $inClause = Connection::buildIntInClause($ids);

        return (string) Connection::execute(
            'update words
            set WoText = WoTextLC
            where WoID in ' . $inClause,
            "Term(s) set to lowercase"
        );
    }

    /**
     * Capitalize words in ID list.
     *
     * @param int[] $ids Array of word IDs
     *
     * @return string Result message
     */
    public function capitalizeByIdList(array $ids): string
    {
        $inClause = Connection::buildIntInClause($ids);

        return (string) Connection::execute(
            'update words
            set WoText = CONCAT(
                UPPER(LEFT(WoTextLC,1)),SUBSTRING(WoTextLC,2)
            )
            where WoID in ' . $inClause,
            "Term(s) capitalized"
        );
    }

    /**
     * Get word IDs matching filter criteria (for 'all' actions).
     *
     * @param string $textId  Text ID filter (comma-separated IDs or empty)
     * @param string $whLang  Language condition (with ? placeholders)
     * @param string $whStat  Status condition
     * @param string $whQuery Query condition (with ? placeholders)
     * @param string $whTag   Tag condition (with ? placeholders)
     * @param array  $params  Merged binding parameters for filters
     *
     * @return int[] Array of word IDs
     */
    public function getFilteredWordIds(
        string $textId,
        string $whLang,
        string $whStat,
        string $whQuery,
        string $whTag,
        array $params = []
    ): array {
        if ($textId == '') {
            $bindings = $params;
            $sql = 'select distinct WoID from (
                words
                left JOIN word_tag_map
                ON WoID = WtWoID
            ) where (1=1) ' . $whLang . $whStat . $whQuery . '
            group by WoID ' . $whTag;
        } else {
            $bindings = [];
            $textIds = array_map('intval', explode(',', $textId));
            $inClause = Connection::buildPreparedInClause($textIds, $bindings);
            /** @var array<int, mixed> $bindings */
            $bindings = array_values(array_merge($bindings, $params));
            $sql = 'select distinct WoID
            from (
                words
                left JOIN word_tag_map ON WoID = WtWoID
            ), word_occurrences
            where Ti2LgID = WoLgID and Ti2WoID = WoID and
            Ti2TxID in ' . $inClause . $whLang . $whStat . $whQuery .
            ' group by WoID ' . $whTag;
        }

        $records = Connection::preparedFetchAll($sql, $bindings);
        $ids = [];
        foreach ($records as $record) {
            $ids[] = (int) $record['WoID'];
        }

        return $ids;
    }

    /**
     * Delete a single word by ID.
     *
     * @param int $wordId Word ID
     *
     * @return void
     */
    public function deleteSingleWord(int $wordId): void
    {
        // Delete multi-word text items first (before word deletion triggers FK SET NULL)
        Connection::preparedExecute(
            'DELETE FROM word_occurrences WHERE Ti2WordCount > 1 AND Ti2WoID = ?',
            [$wordId]
        );

        // Delete word - FK constraints handle:
        // - Single-word word_occurrences.Ti2WoID set to NULL (ON DELETE SET NULL)
        // - word_tag_map deleted (ON DELETE CASCADE)
        $bindings = [$wordId];
        Connection::preparedExecute(
            'DELETE FROM words WHERE WoID = ?'
            . UserScopedQuery::forTablePrepared('words', $bindings),
            $bindings
        );

        Maintenance::adjustAutoIncrement('words', 'WoID');
    }

    /**
     * Get Anki export SQL for selected words.
     *
     * @param int[]  $ids     Array of word IDs (empty for filter-based export)
     * @param string $textId  Text ID filter (empty for no filter)
     * @param string $whLang  Language condition
     * @param string $whStat  Status condition
     * @param string $whQuery Query condition
     * @param string $whTag   Tag condition
     *
     * @return string SQL query for export
     */
    public function getAnkiExportSql(
        array $ids,
        string $textId,
        string $whLang,
        string $whStat,
        string $whQuery,
        string $whTag
    ): string {
        if (!empty($ids)) {
            $inClause = Connection::buildIntInClause($ids);

            return 'select distinct WoID, LgRightToLeft,
                LgRegexpWordCharacters, LgName, WoText, WoTranslation,
                WoRomanization, WoSentence,
                ifnull(
                    group_concat(
                        distinct TgText
                        order by TgText separator \' \'
                    ),
                    \'\'
                ) as taglist
                from (
                    (
                        words
                        left JOIN word_tag_map
                        ON WoID = WtWoID
                    )
                    left join tags
                    on TgID = WtTgID
                ),
                languages
                where WoLgID = LgID AND WoTranslation != \'\' AND
                WoTranslation != \'*\' and
                WoSentence like concat(\'%{\',WoText,\'}%\') and
                WoID in ' . $inClause . '
                group by WoID';
        }

        if ($textId == '') {
            return 'select distinct WoID, LgRightToLeft, LgRegexpWordCharacters,
                LgName, WoText, WoTranslation, WoRomanization, WoSentence,
                ifnull(
                    group_concat(distinct TgText order by TgText separator \' \'),
                    \'\'
                ) as taglist
                from (
                    (
                        words
                        left JOIN word_tag_map
                        ON WoID = WtWoID
                    )
                    left join tags
                    on TgID = WtTgID
                ), languages
                where WoLgID = LgID AND WoTranslation != \'*\' and
                WoSentence like concat(\'%{\',WoText,\'}%\') ' .
                $whLang . $whStat . $whQuery . '
                group by WoID ' . $whTag;
        }

        return 'select distinct WoID, LgRightToLeft, LgRegexpWordCharacters,
            LgName, WoText, WoTranslation, WoRomanization, WoSentence,
            ifnull(group_concat(distinct TgText order by TgText separator \' \'),\'\') as taglist
            from ((words left JOIN word_tag_map
            ON WoID = WtWoID) left join tags
            on TgID = WtTgID), languages,
            word_occurrences where Ti2LgID = WoLgID and Ti2WoID = WoID
            and Ti2TxID in (' . $textId . ') and WoLgID = LgID AND
            WoTranslation != \'*\' and WoSentence like concat(\'%{\',WoText,\'}%\') ' .
            $whLang . $whStat . $whQuery . ' group by WoID ' . $whTag;
    }

    /**
     * Get TSV export SQL for selected words.
     *
     * @param int[]  $ids     Array of word IDs (empty for filter-based export)
     * @param string $textId  Text ID filter (empty for no filter)
     * @param string $whLang  Language condition
     * @param string $whStat  Status condition
     * @param string $whQuery Query condition
     * @param string $whTag   Tag condition
     *
     * @return string SQL query for export
     */
    public function getTsvExportSql(
        array $ids,
        string $textId,
        string $whLang,
        string $whStat,
        string $whQuery,
        string $whTag
    ): string {
        if (!empty($ids)) {
            $inClause = Connection::buildIntInClause($ids);

            return 'select distinct WoID, LgName, WoText, WoTranslation,
                WoRomanization, WoSentence, WoStatus,
                ifnull(
                    group_concat(
                        distinct TgText order by TgText separator \' \'
                    ), \'\'
                ) as taglist
                from (
                    (
                        words
                        left JOIN word_tag_map
                        ON WoID = WtWoID
                    )
                    left join tags on TgID = WtTgID
                ), languages
                where WoLgID = LgID and WoID in ' . $inClause . '
                group by WoID';
        }

        if ($textId == '') {
            return 'select distinct WoID, LgName, WoText, WoTranslation,
                WoRomanization, WoSentence, WoStatus,
                ifnull(group_concat(distinct TgText order by TgText separator \' \'),\'\') as taglist
                from ((words left JOIN word_tag_map
                ON WoID = WtWoID) left join tags
                on TgID = WtTgID), languages
                where WoLgID = LgID ' . $whLang . $whStat . $whQuery .
                ' group by WoID ' . $whTag;
        }

        return 'select distinct WoID, LgName, WoText, WoTranslation,
            WoRomanization, WoSentence, WoStatus,
            ifnull(group_concat(distinct TgText order by TgText separator \' \'),\'\') as taglist
            from ((words left JOIN word_tag_map
            ON WoID = WtWoID) left join tags
            on TgID = WtTgID), languages,
            word_occurrences where Ti2LgID = WoLgID and Ti2WoID = WoID
            and Ti2TxID in (' . $textId . ') and WoLgID = LgID ' .
            $whLang . $whStat . $whQuery . ' group by WoID ' . $whTag;
    }

    /**
     * Get flexible export SQL for selected words.
     *
     * @param int[]  $ids     Array of word IDs (empty for filter-based export)
     * @param string $textId  Text ID filter (empty for no filter)
     * @param string $whLang  Language condition
     * @param string $whStat  Status condition
     * @param string $whQuery Query condition
     * @param string $whTag   Tag condition
     *
     * @return string SQL query for export
     */
    public function getFlexibleExportSql(
        array $ids,
        string $textId,
        string $whLang,
        string $whStat,
        string $whQuery,
        string $whTag
    ): string {
        if (!empty($ids)) {
            $inClause = Connection::buildIntInClause($ids);

            return 'select distinct WoID, LgName, LgExportTemplate,
                LgRightToLeft, WoText, WoTextLC, WoTranslation,
                WoRomanization, WoSentence, WoStatus,
                ifnull(
                    group_concat(
                        distinct TgText order by TgText separator \' \'),\'\'
                ) as taglist
                from (
                    (
                        words
                        left JOIN word_tag_map
                        ON WoID = WtWoID
                    )
                    left join tags on TgID = WtTgID
                ), languages
                where WoLgID = LgID and WoID in ' . $inClause . '
                group by WoID';
        }

        if ($textId == '') {
            return 'select distinct WoID, LgName, LgExportTemplate, LgRightToLeft,
                WoText, WoTextLC, WoTranslation, WoRomanization, WoSentence,
                WoStatus,
                ifnull(group_concat(distinct TgText order by TgText separator \' \'),\'\') as taglist
                from ((words left JOIN word_tag_map
                ON WoID = WtWoID) left join tags
                on TgID = WtTgID), languages
                where WoLgID = LgID ' . $whLang . $whStat . $whQuery .
                ' group by WoID ' . $whTag;
        }

        return 'select distinct WoID, LgName, LgExportTemplate, LgRightToLeft,
            WoText, WoTextLC, WoTranslation, WoRomanization, WoSentence,
            WoStatus,
            ifnull(group_concat(distinct TgText order by TgText separator \' \'),\'\') as taglist
            from ((words left JOIN word_tag_map
            ON WoID = WtWoID) left join tags
            on TgID = WtTgID), languages,
            word_occurrences where Ti2LgID = WoLgID and Ti2WoID = WoID
            and Ti2TxID in (' . $textId . ') and WoLgID = LgID ' .
            $whLang . $whStat . $whQuery . ' group by WoID ' . $whTag;
    }

    /**
     * Get test SQL for selected words.
     *
     * @param string $idList  SQL IN clause with IDs
     * @param string $textId  Text ID filter (empty for no filter)
     * @param string $whLang  Language condition
     * @param string $whStat  Status condition
     * @param string $whQuery Query condition
     * @param string $whTag   Tag condition
     *
     * @return string SQL query for test word IDs
     */
    public function getTestWordIdsSql(
        string $idList,
        string $textId,
        string $whLang,
        string $whStat,
        string $whQuery,
        string $whTag
    ): string {
        if ($textId == '') {
            return 'select distinct WoID
            from (words left JOIN word_tag_map
            ON WoID = WtWoID)
            where (1=1) ' . $whLang . $whStat . $whQuery .
            ' group by WoID ' . $whTag;
        }

        return 'select distinct WoID
        from (words left JOIN word_tag_map
        ON WoID = WtWoID), word_occurrences
        where Ti2LgID = WoLgID and Ti2WoID = WoID and Ti2TxID in (' .
        $textId . ')' . $whLang . $whStat . $whQuery . '
        group by WoID ' . $whTag;
    }

    /**
     * Get word data for new term form.
     *
     * @param int $langId Language ID
     *
     * @return array Language data for form
     */
    public function getNewTermFormData(int $langId): array
    {
        $bindings = [$langId];
        $showRoman = (bool) Connection::preparedFetchValue(
            'SELECT LgShowRomanization FROM languages WHERE LgID = ?'
            . UserScopedQuery::forTablePrepared('languages', $bindings),
            $bindings,
            'LgShowRomanization'
        );

        $languageService = new LanguageFacade();
        return [
            'showRoman' => $showRoman,
            'scrdir' => $languageService->getScriptDirectionTag($langId),
        ];
    }

    /**
     * Get word data for edit form.
     *
     * @param int $wordId Word ID
     *
     * @return array|null Word data for form or null if not found
     */
    public function getEditFormData(int $wordId): ?array
    {
        $bindings = [$wordId];
        $record = Connection::preparedFetchOne(
            'SELECT * FROM words, languages
            WHERE LgID = WoLgID AND WoID = ?'
            . UserScopedQuery::forTablePrepared('words', $bindings)
            . UserScopedQuery::forTablePrepared('languages', $bindings, 'languages'),
            $bindings
        );

        if ($record === null) {
            return null;
        }

        $transl = ExportService::replaceTabNewline((string)$record['WoTranslation']);
        if ($transl == '*') {
            $transl = '';
        }

        return [
            'WoID' => $record['WoID'],
            'WoLgID' => $record['WoLgID'],
            'WoText' => $record['WoText'],
            'WoTextLC' => $record['WoTextLC'],
            'WoTranslation' => $transl,
            'WoRomanization' => $record['WoRomanization'],
            'WoSentence' => ExportService::replaceTabNewline((string)($record['WoSentence'] ?? '')),
            'WoStatus' => $record['WoStatus'],
            'LgName' => $record['LgName'],
            'LgRightToLeft' => $record['LgRightToLeft'],
            'LgShowRomanization' => $record['LgShowRomanization'],
            'scrdir' => $record['LgRightToLeft'] ? ' dir="rtl" ' : '',
        ];
    }

    /**
     * Save a new word.
     *
     * @param array<string, mixed> $data Form data
     *
     * @return int Word ID of the created word
     *
     * @throws \RuntimeException If word could not be saved
     */
    public function saveNewWord(array $data): int
    {
        $translation = ExportService::replaceTabNewline((string)($data['WoTranslation'] ?? ''));
        if ($translation == '') {
            $translation = '*';
        }

        $textLc = mb_strtolower((string)$data["WoText"], 'UTF-8');
        $sentence = ExportService::replaceTabNewline((string)($data["WoSentence"] ?? ''));
        $romanization = (string)($data["WoRomanization"] ?? '');

        $scoreColumns = TermStatusService::makeScoreRandomInsertUpdate('iv');
        $scoreValues = TermStatusService::makeScoreRandomInsertUpdate('id');

        $bindings = [
            (int)$data["WoLgID"], $textLc, (string)$data["WoText"],
            (int)$data["WoStatus"], $translation, $sentence, $romanization
        ];
        $sql = "INSERT INTO words (WoLgID, WoTextLC, WoText, "
            . "WoStatus, WoTranslation, WoSentence, WoRomanization, WoStatusChanged, {$scoreColumns}"
            . UserScopedQuery::insertColumn('words')
            . ") VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), {$scoreValues}"
            . UserScopedQuery::insertValuePrepared('words', $bindings)
            . ")";

        $wid = Connection::preparedInsert($sql, $bindings);

        if ($wid <= 0) {
            throw new \RuntimeException('Failed to save word');
        }

        Maintenance::initWordCount();
        $bindings = [$wid];
        $len = (int)Connection::preparedFetchValue(
            'SELECT WoWordCount FROM words WHERE WoID = ?'
            . UserScopedQuery::forTablePrepared('words', $bindings),
            $bindings,
            'WoWordCount'
        );
        if ($len > 1) {
            (new ExpressionService())->insertExpressions($textLc, (int)$data["WoLgID"], (int)$wid, $len, 1);
        } else {
            Connection::preparedExecute(
                'UPDATE word_occurrences
                SET Ti2WoID = ?
                WHERE Ti2LgID = ? AND LOWER(Ti2Text) = ?',
                [$wid, (int)$data["WoLgID"], $textLc]
            );
        }

        return (int) $wid;
    }

    /**
     * Update an existing word.
     *
     * @param array<string, mixed> $data Form data
     *
     * @return string Result message
     */
    public function updateWord(array $data): string
    {
        $translation = ExportService::replaceTabNewline((string)($data['WoTranslation'] ?? ''));
        if ($translation == '') {
            $translation = '*';
        }

        $textLc = mb_strtolower((string)$data["WoText"], 'UTF-8');
        $sentence = ExportService::replaceTabNewline((string)($data["WoSentence"] ?? ''));
        $romanization = (string)($data["WoRomanization"] ?? '');

        $oldstatus = (int)$data["WoOldStatus"];
        $newstatus = (int)$data["WoStatus"];

        if ($oldstatus != $newstatus) {
            $bindings = [
                (string)$data["WoText"], $textLc, $translation, $sentence,
                $romanization, $newstatus, (int)$data["WoID"]
            ];
            $affected = Connection::preparedExecute(
                'UPDATE words
                SET WoText = ?, WoTextLC = ?, WoTranslation = ?, WoSentence = ?,
                    WoRomanization = ?, WoStatus = ?, WoStatusChanged = NOW(),' .
                TermStatusService::makeScoreRandomInsertUpdate('u') .
                ' WHERE WoID = ?'
                . UserScopedQuery::forTablePrepared('words', $bindings),
                $bindings
            );
        } else {
            $bindings = [(string)$data["WoText"], $textLc, $translation, $sentence, $romanization, (int)$data["WoID"]];
            $affected = Connection::preparedExecute(
                'UPDATE words
                SET WoText = ?, WoTextLC = ?, WoTranslation = ?, WoSentence = ?,
                    WoRomanization = ?,' .
                TermStatusService::makeScoreRandomInsertUpdate('u') .
                ' WHERE WoID = ?'
                . UserScopedQuery::forTablePrepared('words', $bindings),
                $bindings
            );
        }

        return $affected > 0 ? "Updated" : "No changes made";
    }
}
