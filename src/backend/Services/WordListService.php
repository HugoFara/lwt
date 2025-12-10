<?php declare(strict_types=1);
/**
 * Word List Service - Business logic for word list/edit operations
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
use Lwt\Database\Maintenance;
use Lwt\View\Helper\StatusHelper;
use Lwt\Services\ExportService;

require_once __DIR__ . '/LanguageService.php';
require_once __DIR__ . '/WordStatusService.php';
require_once __DIR__ . '/ExpressionService.php';
require_once __DIR__ . '/../View/Helper/StatusHelper.php';

/**
 * Service class for managing word list operations.
 *
 * Handles filtering, pagination, bulk operations on words list.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class WordListService
{
    private string $tbpref;

    public function __construct()
    {
        $this->tbpref = Globals::getTablePrefix();
    }

    /**
     * Build query condition for language filter.
     *
     * @param string $langId Language ID
     *
     * @return string SQL condition
     */
    public function buildLangCondition(string $langId): string
    {
        return ($langId != '') ? (' and WoLgID=' . $langId) : '';
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
    public function buildQueryCondition(string $query, string $queryMode, string $regexMode, ?array &$params = null): string
    {
        if ($query === '') {
            return '';
        }

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
        $escapedValue = "'" . mysqli_real_escape_string(
            Globals::getDbConnection(),
            $queryValue
        ) . "'";

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
     * @param string $tag1  First tag ID
     * @param string $tag2  Second tag ID
     * @param string $tag12 Tag logic (0=OR, 1=AND)
     *
     * @return string SQL HAVING clause
     */
    public function buildTagCondition(string $tag1, string $tag2, string $tag12): string
    {
        if ($tag1 == '' && $tag2 == '') {
            return '';
        }

        $whTag1 = null;
        $whTag2 = null;

        if ($tag1 != '') {
            if ($tag1 == '-1') {
                $whTag1 = "group_concat(WtTgID) IS NULL";
            } else {
                $whTag1 = "concat('/',group_concat(WtTgID separator '/'),'/') like '%/" . $tag1 . "/%'";
            }
        }

        if ($tag2 != '') {
            if ($tag2 == '-1') {
                $whTag2 = "group_concat(WtTgID) IS NULL";
            } else {
                $whTag2 = "concat('/',group_concat(WtTgID separator '/'),'/') like '%/" . $tag2 . "/%'";
            }
        }

        if ($whTag1 !== null && $whTag2 === null) {
            return " having (" . $whTag1 . ') ';
        } elseif ($whTag2 !== null && $whTag1 === null) {
            return " having (" . $whTag2 . ') ';
        } else {
            return " having ((" . $whTag1 . ($tag12 ? ') AND (' : ') OR (') . $whTag2 . ")) ";
        }
    }

    /**
     * Count words matching the filter criteria.
     *
     * @param string $textId  Text ID filter
     * @param string $whLang  Language condition
     * @param string $whStat  Status condition
     * @param string $whQuery Query condition
     * @param string $whTag   Tag condition
     *
     * @return int Number of matching words
     */
    public function countWords(
        string $textId,
        string $whLang,
        string $whStat,
        string $whQuery,
        string $whTag
    ): int {
        if ($textId == '') {
            $sql = 'select count(*) as value from (select WoID from (' .
                $this->tbpref . 'words left JOIN ' . $this->tbpref .
                'wordtags ON WoID = WtWoID) where (1=1) ' .
                $whLang . $whStat . $whQuery . ' group by WoID ' . $whTag . ') as dummy';
        } else {
            $sql = 'select count(*) as value from (select WoID from (' .
                $this->tbpref . 'words left JOIN ' . $this->tbpref .
                'wordtags ON WoID = WtWoID), ' . $this->tbpref .
                'textitems2 where Ti2LgID = WoLgID and Ti2WoID = WoID and Ti2TxID in (' .
                $textId . ')' . $whLang . $whStat . $whQuery .
                ' group by WoID ' . $whTag . ') as dummy';
        }
        return (int) Connection::fetchValue($sql);
    }

    /**
     * Get words list for display.
     *
     * @param array $filters Filter parameters
     * @param int   $sort    Sort column index
     * @param int   $page    Page number
     * @param int   $perPage Items per page
     *
     * @return \mysqli_result|bool Query result
     */
    public function getWordsList(array $filters, int $sort, int $page, int $perPage)
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

        $offset = ($page - 1) * $perPage;
        $limit = "LIMIT $offset, $perPage";

        $whLang = $filters['whLang'] ?? '';
        $whStat = $filters['whStat'] ?? '';
        $whQuery = $filters['whQuery'] ?? '';
        $whTag = $filters['whTag'] ?? '';
        $textId = $filters['textId'] ?? '';

        if ($sort == 7) {
            // Sort by word count in texts
            return $this->getWordsListWithWordCount($filters, $sorts[$sort - 1], $limit);
        }

        if ($textId == '') {
            if ($whTag == '') {
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
                        from ' . $this->tbpref . 'words, ' . $this->tbpref . 'languages
                        where WoLgID = LgID ' . $whLang . $whStat . $whQuery . '
                        group by WoID
                        order by ' . $sorts[$sort - 1] . ' ' . $limit . ') AS AA
                        left JOIN ' . $this->tbpref . 'wordtags ON WoID = WtWoID
                        left join ' . $this->tbpref . 'tags on TgID = WtTgID
                        group by WoID
                        order by ' . $sorts[$sort - 1];
            } else {
                $sql = 'select WoID, WoText, WoTranslation, WoRomanization, WoSentence,
                        ifnull(WoSentence,\'\') like concat(\'%{\',WoText,\'}%\') as SentOK,
                        WoStatus, LgName, LgRightToLeft, LgGoogleTranslateURI,
                        DATEDIFF( NOW( ) , WoStatusChanged ) AS Days, WoTodayScore AS Score,
                        WoTomorrowScore AS Score2,
                        ifnull(group_concat(distinct TgText order by TgText separator \',\'),\'\') as taglist
                        from ((' . $this->tbpref . 'words left JOIN ' . $this->tbpref .
                        'wordtags ON WoID = WtWoID) left join ' . $this->tbpref .
                        'tags on TgID = WtTgID), ' . $this->tbpref . 'languages
                        where WoLgID = LgID ' . $whLang . $whStat . $whQuery .
                        ' group by WoID ' . $whTag . ' order by ' . $sorts[$sort - 1] . ' ' . $limit;
            }
        } else {
            $sql = 'select distinct WoID, WoText, WoTranslation, WoRomanization,
                    WoSentence, ifnull(WoSentence,\'\') like \'%{%}%\' as SentOK, WoStatus,
                    LgName, LgRightToLeft, LgGoogleTranslateURI,
                    DATEDIFF( NOW( ) , WoStatusChanged ) AS Days, WoTodayScore AS Score,
                    WoTomorrowScore AS Score2,
                    ifnull(group_concat(distinct TgText order by TgText separator \',\'),\'\') as taglist
                    from ((' . $this->tbpref . 'words
                    left JOIN ' . $this->tbpref . 'wordtags ON WoID = WtWoID)
                    left join ' . $this->tbpref . 'tags on TgID = WtTgID), ' .
                    $this->tbpref . 'languages, ' . $this->tbpref . 'textitems2
                    where Ti2LgID = WoLgID and Ti2WoID = WoID and Ti2TxID in (' .
                    $textId . ') and WoLgID = LgID ' . $whLang . $whStat . $whQuery . '
                    group by WoID ' . $whTag . '
                    order by ' . $sorts[$sort - 1] . ' ' . $limit;
        }

        return Connection::query($sql);
    }

    /**
     * Get words list with word count (for sort option 7).
     *
     * @param array  $filters  Filter parameters
     * @param string $sortExpr Sort expression
     * @param string $limit    LIMIT clause
     *
     * @return \mysqli_result|bool Query result
     */
    private function getWordsListWithWordCount(array $filters, string $sortExpr, string $limit)
    {
        $whLang = $filters['whLang'] ?? '';
        $whStat = $filters['whStat'] ?? '';
        $whQuery = $filters['whQuery'] ?? '';
        $whTag = $filters['whTag'] ?? '';
        $textId = $filters['textId'] ?? '';

        if ($textId != '') {
            $sql = 'select WoID, count(WoID) AS textswordcount, WoText, WoTranslation,
                    WoRomanization, WoSentence,
                    ifnull(WoSentence,\'\') like concat(\'%{\',WoText,\'}%\') as SentOK,
                    WoStatus, LgName, LgRightToLeft, LgGoogleTranslateURI,
                    DATEDIFF( NOW( ) , WoStatusChanged ) AS Days, WoTodayScore AS Score,
                    WoTomorrowScore AS Score2,
                    ifnull(group_concat(distinct TgText order by TgText separator \',\'),\'\') as taglist,
                    WoTextLC, WoTodayScore
                    from ((' . $this->tbpref . 'words left JOIN ' . $this->tbpref .
                    'wordtags ON WoID = WtWoID)
                    left join ' . $this->tbpref . 'tags on TgID = WtTgID), ' .
                    $this->tbpref . 'languages, ' . $this->tbpref . 'textitems2
                    where Ti2LgID = WoLgID and Ti2WoID = WoID and WoLgID = LgID
                    and Ti2TxID in (' . $textId . ') ' .
                    $whLang . $whStat . $whQuery . ' group by WoID ' . $whTag .
                    ' order by ' . $sortExpr . ' ' . $limit;
        } else {
            $sql = 'select WoID, 0 AS textswordcount, WoText, WoTranslation,
                    WoRomanization, WoSentence,
                    ifnull(WoSentence,\'\') like concat(\'%{\',WoText,\'}%\') as SentOK,
                    WoStatus, LgName, LgRightToLeft, LgGoogleTranslateURI,
                    DATEDIFF( NOW( ) , WoStatusChanged ) AS Days, WoTodayScore AS Score,
                    WoTomorrowScore AS Score2,
                    ifnull(group_concat(distinct TgText order by TgText separator \',\'),\'\') as taglist,
                    WoTextLC, WoTodayScore
                    from ((' . $this->tbpref . 'words left JOIN ' . $this->tbpref .
                    'wordtags ON WoID = WtWoID)
                    left join ' . $this->tbpref . 'tags on TgID = WtTgID), ' .
                    $this->tbpref . 'languages
                    where WoLgID = LgID and WoID NOT IN (SELECT DISTINCT Ti2WoID
                    from ' . $this->tbpref . 'textitems2 where Ti2LgID = LgID) ' .
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
                    from ((' . $this->tbpref . 'words left JOIN ' . $this->tbpref .
                    'wordtags ON WoID = WtWoID)
                    left join ' . $this->tbpref . 'tags on TgID = WtTgID), ' .
                    $this->tbpref . 'languages, ' . $this->tbpref . 'textitems2
                    where Ti2LgID = WoLgID and Ti2WoID = WoID and WoLgID = LgID ' .
                    $whLang . $whStat . $whQuery . ' group by WoID ' . $whTag .
                    ' order by ' . $sortExpr . ' ' . $limit;
        }

        return Connection::query($sql);
    }

    /**
     * Delete multiple words by ID list.
     *
     * @param string $idList SQL IN clause with IDs (e.g., "(1,2,3)")
     *
     * @return string Result message
     */
    public function deleteByIdList(string $idList): string
    {
        $message = Connection::execute(
            'delete from ' . $this->tbpref . 'words where WoID in ' . $idList,
            "Deleted"
        );

        Connection::query(
            'update ' . $this->tbpref . 'textitems2
            set Ti2WoID = 0
            where Ti2WordCount = 1 and Ti2WoID in ' . $idList
        );

        Connection::query(
            'delete from ' . $this->tbpref . 'textitems2
            where Ti2WoID in ' . $idList
        );

        Maintenance::adjustAutoIncrement('words', 'WoID');

        Connection::execute(
            "DELETE " . $this->tbpref . "wordtags
            FROM (
                " . $this->tbpref . "wordtags
                LEFT JOIN " . $this->tbpref . "words
                on WtWoID = WoID
            ) WHERE WoID IS NULL",
            ''
        );

        return $message;
    }

    /**
     * Update status for words in ID list.
     *
     * @param string $idList     SQL IN clause with IDs
     * @param int    $newStatus  New status value
     * @param bool   $relative   If true, change by +1 or -1
     * @param string $actionType Type of action (spl1, smi1, s5, s1, s99, s98)
     *
     * @return string Result message
     */
    public function updateStatusByIdList(string $idList, int $newStatus, bool $relative, string $actionType): string
    {
        $scoreUpdate = WordStatusService::makeScoreRandomInsertUpdate('u');

        if ($relative && $newStatus > 0) {
            // Status +1
            return Connection::execute(
                'update ' . $this->tbpref . 'words
                set WoStatus=WoStatus+1, WoStatusChanged = NOW(),' . $scoreUpdate . '
                where WoStatus in (1,2,3,4) and WoID in ' . $idList,
                "Updated Status (+1)"
            );
        } elseif ($relative && $newStatus < 0) {
            // Status -1
            return Connection::execute(
                'update ' . $this->tbpref . 'words
                set WoStatus=WoStatus-1, WoStatusChanged = NOW(),' . $scoreUpdate . '
                where WoStatus in (2,3,4,5) and WoID in ' . $idList,
                "Updated Status (-1)"
            );
        }

        // Absolute status
        return Connection::execute(
            'update ' . $this->tbpref . 'words
            set WoStatus=' . $newStatus . ', WoStatusChanged = NOW(),' . $scoreUpdate . '
            where WoID in ' . $idList,
            "Updated Status (=" . $newStatus . ")"
        );
    }

    /**
     * Update status date to NOW for words in ID list.
     *
     * @param string $idList SQL IN clause with IDs
     *
     * @return string Result message
     */
    public function updateStatusDateByIdList(string $idList): string
    {
        return Connection::execute(
            'update ' . $this->tbpref . 'words
            set WoStatusChanged = NOW(),' . WordStatusService::makeScoreRandomInsertUpdate('u') . '
            where WoID in ' . $idList,
            "Updated Status Date (= Now)"
        );
    }

    /**
     * Delete sentences for words in ID list.
     *
     * @param string $idList SQL IN clause with IDs
     *
     * @return string Result message
     */
    public function deleteSentencesByIdList(string $idList): string
    {
        return Connection::execute(
            'update ' . $this->tbpref . 'words
            set WoSentence = NULL
            where WoID in ' . $idList,
            "Term Sentence(s) deleted"
        );
    }

    /**
     * Convert words to lowercase in ID list.
     *
     * @param string $idList SQL IN clause with IDs
     *
     * @return string Result message
     */
    public function toLowercaseByIdList(string $idList): string
    {
        return Connection::execute(
            'update ' . $this->tbpref . 'words
            set WoText = WoTextLC
            where WoID in ' . $idList,
            "Term(s) set to lowercase"
        );
    }

    /**
     * Capitalize words in ID list.
     *
     * @param string $idList SQL IN clause with IDs
     *
     * @return string Result message
     */
    public function capitalizeByIdList(string $idList): string
    {
        return Connection::execute(
            'update ' . $this->tbpref . 'words
            set WoText = CONCAT(
                UPPER(LEFT(WoTextLC,1)),SUBSTRING(WoTextLC,2)
            )
            where WoID in ' . $idList,
            "Term(s) capitalized"
        );
    }

    /**
     * Get word IDs matching filter criteria (for 'all' actions).
     *
     * @param string $textId  Text ID filter
     * @param string $whLang  Language condition
     * @param string $whStat  Status condition
     * @param string $whQuery Query condition
     * @param string $whTag   Tag condition
     *
     * @return array Array of word IDs
     */
    public function getFilteredWordIds(
        string $textId,
        string $whLang,
        string $whStat,
        string $whQuery,
        string $whTag
    ): array {
        if ($textId == '') {
            $sql = 'select distinct WoID from (
                ' . $this->tbpref . 'words
                left JOIN ' . $this->tbpref . 'wordtags
                ON WoID = WtWoID
            ) where (1=1) ' . $whLang . $whStat . $whQuery . '
            group by WoID ' . $whTag;
        } else {
            $sql = 'select distinct WoID
            from (
                ' . $this->tbpref . 'words
                left JOIN ' . $this->tbpref . 'wordtags ON WoID = WtWoID
            ), ' . $this->tbpref . 'textitems2
            where Ti2LgID = WoLgID and Ti2WoID = WoID and
            Ti2TxID in (' . $textId . ')' . $whLang . $whStat . $whQuery .
            ' group by WoID ' . $whTag;
        }

        $ids = [];
        $res = Connection::query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            $ids[] = (int) $record['WoID'];
        }
        mysqli_free_result($res);

        return $ids;
    }

    /**
     * Delete a single word by ID.
     *
     * @param int $wordId Word ID
     *
     * @return string Result message
     */
    public function deleteSingleWord(int $wordId): string
    {
        Connection::preparedExecute(
            'DELETE FROM ' . $this->tbpref . 'words WHERE WoID = ?',
            [$wordId]
        );
        $message = "Deleted";

        Maintenance::adjustAutoIncrement('words', 'WoID');

        Connection::preparedExecute(
            'UPDATE ' . $this->tbpref . 'textitems2 SET Ti2WoID = 0
            WHERE Ti2WordCount = 1 AND Ti2WoID = ?',
            [$wordId]
        );

        Connection::preparedExecute(
            'DELETE FROM ' . $this->tbpref . 'textitems2 WHERE Ti2WoID = ?',
            [$wordId]
        );

        Connection::execute(
            "DELETE " . $this->tbpref . "wordtags FROM (" .
            $this->tbpref . "wordtags LEFT JOIN " . $this->tbpref .
            "words on WtWoID = WoID) WHERE WoID IS NULL",
            ''
        );

        return $message;
    }

    /**
     * Get Anki export SQL for selected words.
     *
     * @param string $idList  SQL IN clause with IDs
     * @param string $textId  Text ID filter (empty for no filter)
     * @param string $whLang  Language condition
     * @param string $whStat  Status condition
     * @param string $whQuery Query condition
     * @param string $whTag   Tag condition
     *
     * @return string SQL query for export
     */
    public function getAnkiExportSql(
        string $idList,
        string $textId,
        string $whLang,
        string $whStat,
        string $whQuery,
        string $whTag
    ): string {
        if ($idList !== '') {
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
                        ' . $this->tbpref . 'words
                        left JOIN ' . $this->tbpref . 'wordtags
                        ON WoID = WtWoID
                    )
                    left join ' . $this->tbpref . 'tags
                    on TgID = WtTgID
                ),
                ' . $this->tbpref . 'languages
                where WoLgID = LgID AND WoTranslation != \'\' AND
                WoTranslation != \'*\' and
                WoSentence like concat(\'%{\',WoText,\'}%\') and
                WoID in ' . $idList . '
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
                        ' . $this->tbpref . 'words
                        left JOIN ' . $this->tbpref . 'wordtags
                        ON WoID = WtWoID
                    )
                    left join ' . $this->tbpref . 'tags
                    on TgID = WtTgID
                ), ' . $this->tbpref . 'languages
                where WoLgID = LgID AND WoTranslation != \'*\' and
                WoSentence like concat(\'%{\',WoText,\'}%\') ' .
                $whLang . $whStat . $whQuery . '
                group by WoID ' . $whTag;
        }

        return 'select distinct WoID, LgRightToLeft, LgRegexpWordCharacters,
            LgName, WoText, WoTranslation, WoRomanization, WoSentence,
            ifnull(group_concat(distinct TgText order by TgText separator \' \'),\'\') as taglist
            from ((' . $this->tbpref . 'words left JOIN ' . $this->tbpref .
            'wordtags ON WoID = WtWoID) left join ' . $this->tbpref .
            'tags on TgID = WtTgID), ' . $this->tbpref . 'languages, ' .
            $this->tbpref . 'textitems2 where Ti2LgID = WoLgID and Ti2WoID = WoID
            and Ti2TxID in (' . $textId . ') and WoLgID = LgID AND
            WoTranslation != \'*\' and WoSentence like concat(\'%{\',WoText,\'}%\') ' .
            $whLang . $whStat . $whQuery . ' group by WoID ' . $whTag;
    }

    /**
     * Get TSV export SQL for selected words.
     *
     * @param string $idList  SQL IN clause with IDs
     * @param string $textId  Text ID filter (empty for no filter)
     * @param string $whLang  Language condition
     * @param string $whStat  Status condition
     * @param string $whQuery Query condition
     * @param string $whTag   Tag condition
     *
     * @return string SQL query for export
     */
    public function getTsvExportSql(
        string $idList,
        string $textId,
        string $whLang,
        string $whStat,
        string $whQuery,
        string $whTag
    ): string {
        if ($idList !== '') {
            return 'select distinct WoID, LgName, WoText, WoTranslation,
                WoRomanization, WoSentence, WoStatus,
                ifnull(
                    group_concat(
                        distinct TgText order by TgText separator \' \'
                    ), \'\'
                ) as taglist
                from (
                    (
                        ' . $this->tbpref . 'words
                        left JOIN ' . $this->tbpref . 'wordtags
                        ON WoID = WtWoID
                    )
                    left join ' . $this->tbpref . 'tags on TgID = WtTgID
                ), ' . $this->tbpref . 'languages
                where WoLgID = LgID and WoID in ' . $idList . '
                group by WoID';
        }

        if ($textId == '') {
            return 'select distinct WoID, LgName, WoText, WoTranslation,
                WoRomanization, WoSentence, WoStatus,
                ifnull(group_concat(distinct TgText order by TgText separator \' \'),\'\') as taglist
                from ((' . $this->tbpref . 'words left JOIN ' . $this->tbpref .
                'wordtags ON WoID = WtWoID) left join ' . $this->tbpref .
                'tags on TgID = WtTgID), ' . $this->tbpref . 'languages
                where WoLgID = LgID ' . $whLang . $whStat . $whQuery .
                ' group by WoID ' . $whTag;
        }

        return 'select distinct WoID, LgName, WoText, WoTranslation,
            WoRomanization, WoSentence, WoStatus,
            ifnull(group_concat(distinct TgText order by TgText separator \' \'),\'\') as taglist
            from ((' . $this->tbpref . 'words left JOIN ' . $this->tbpref .
            'wordtags ON WoID = WtWoID) left join ' . $this->tbpref .
            'tags on TgID = WtTgID), ' . $this->tbpref . 'languages, ' .
            $this->tbpref . 'textitems2 where Ti2LgID = WoLgID and Ti2WoID = WoID
            and Ti2TxID in (' . $textId . ') and WoLgID = LgID ' .
            $whLang . $whStat . $whQuery . ' group by WoID ' . $whTag;
    }

    /**
     * Get flexible export SQL for selected words.
     *
     * @param string $idList  SQL IN clause with IDs
     * @param string $textId  Text ID filter (empty for no filter)
     * @param string $whLang  Language condition
     * @param string $whStat  Status condition
     * @param string $whQuery Query condition
     * @param string $whTag   Tag condition
     *
     * @return string SQL query for export
     */
    public function getFlexibleExportSql(
        string $idList,
        string $textId,
        string $whLang,
        string $whStat,
        string $whQuery,
        string $whTag
    ): string {
        if ($idList !== '') {
            return 'select distinct WoID, LgName, LgExportTemplate,
                LgRightToLeft, WoText, WoTextLC, WoTranslation,
                WoRomanization, WoSentence, WoStatus,
                ifnull(
                    group_concat(
                        distinct TgText order by TgText separator \' \'),\'\'
                ) as taglist
                from (
                    (
                        ' . $this->tbpref . 'words
                        left JOIN ' . $this->tbpref . 'wordtags
                        ON WoID = WtWoID
                    )
                    left join ' . $this->tbpref . 'tags on TgID = WtTgID
                ), ' . $this->tbpref . 'languages
                where WoLgID = LgID and WoID in ' . $idList . '
                group by WoID';
        }

        if ($textId == '') {
            return 'select distinct WoID, LgName, LgExportTemplate, LgRightToLeft,
                WoText, WoTextLC, WoTranslation, WoRomanization, WoSentence,
                WoStatus,
                ifnull(group_concat(distinct TgText order by TgText separator \' \'),\'\') as taglist
                from ((' . $this->tbpref . 'words left JOIN ' . $this->tbpref .
                'wordtags ON WoID = WtWoID) left join ' . $this->tbpref .
                'tags on TgID = WtTgID), ' . $this->tbpref . 'languages
                where WoLgID = LgID ' . $whLang . $whStat . $whQuery .
                ' group by WoID ' . $whTag;
        }

        return 'select distinct WoID, LgName, LgExportTemplate, LgRightToLeft,
            WoText, WoTextLC, WoTranslation, WoRomanization, WoSentence,
            WoStatus,
            ifnull(group_concat(distinct TgText order by TgText separator \' \'),\'\') as taglist
            from ((' . $this->tbpref . 'words left JOIN ' . $this->tbpref .
            'wordtags ON WoID = WtWoID) left join ' . $this->tbpref .
            'tags on TgID = WtTgID), ' . $this->tbpref . 'languages, ' .
            $this->tbpref . 'textitems2 where Ti2LgID = WoLgID and Ti2WoID = WoID
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
            from (' . $this->tbpref . 'words left JOIN ' . $this->tbpref .
            'wordtags ON WoID = WtWoID)
            where (1=1) ' . $whLang . $whStat . $whQuery .
            ' group by WoID ' . $whTag;
        }

        return 'select distinct WoID
        from (' . $this->tbpref . 'words left JOIN ' . $this->tbpref .
        'wordtags ON WoID = WtWoID), ' . $this->tbpref . 'textitems2
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
        $showRoman = (bool) Connection::preparedFetchValue(
            'SELECT LgShowRomanization AS value FROM ' . $this->tbpref . 'languages WHERE LgID = ?',
            [$langId]
        );

        $languageService = new LanguageService();
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
        $record = Connection::preparedFetchOne(
            'SELECT * FROM ' . $this->tbpref . 'words, ' .
            $this->tbpref . 'languages
            WHERE LgID = WoLgID AND WoID = ?',
            [$wordId]
        );

        if (!$record) {
            return null;
        }

        $transl = ExportService::replaceTabNewline($record['WoTranslation']);
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
            'WoSentence' => ExportService::replaceTabNewline($record['WoSentence'] ?? ''),
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
     * @param array $data Form data
     *
     * @return string Result message
     */
    public function saveNewWord(array $data): string
    {
        $translation = ExportService::replaceTabNewline($data['WoTranslation'] ?? '');
        if ($translation == '') {
            $translation = '*';
        }

        $textLc = mb_strtolower($data["WoText"], 'UTF-8');
        $sentence = ExportService::replaceTabNewline($data["WoSentence"] ?? '');
        $romanization = $data["WoRomanization"] ?? '';

        $wid = Connection::preparedInsert(
            'INSERT INTO ' . $this->tbpref . 'words (WoLgID, WoTextLC, WoText, ' .
            'WoStatus, WoTranslation, WoSentence, WoRomanization, WoStatusChanged,' .
            WordStatusService::makeScoreRandomInsertUpdate('iv') . ') VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ' .
            WordStatusService::makeScoreRandomInsertUpdate('id') . ')',
            [$data["WoLgID"], $textLc, $data["WoText"], $data["WoStatus"], $translation, $sentence, $romanization]
        );

        if ($wid > 0) {
            Maintenance::initWordCount();
            $len = (int)Connection::preparedFetchValue(
                'SELECT WoWordCount AS value FROM ' . $this->tbpref . 'words WHERE WoID = ?',
                [$wid]
            );
            if ($len > 1) {
                (new ExpressionService())->insertExpressions($textLc, (int)$data["WoLgID"], $wid, $len, 1);
            } else {
                Connection::preparedExecute(
                    'UPDATE ' . $this->tbpref . 'textitems2
                    SET Ti2WoID = ?
                    WHERE Ti2LgID = ? AND LOWER(Ti2Text) = ?',
                    [$wid, $data["WoLgID"], $textLc]
                );
            }
            return "Saved";
        }

        return "Error saving word";
    }

    /**
     * Update an existing word.
     *
     * @param array $data Form data
     *
     * @return string Result message
     */
    public function updateWord(array $data): string
    {
        $translation = ExportService::replaceTabNewline($data['WoTranslation'] ?? '');
        if ($translation == '') {
            $translation = '*';
        }

        $textLc = mb_strtolower($data["WoText"], 'UTF-8');
        $sentence = ExportService::replaceTabNewline($data["WoSentence"] ?? '');
        $romanization = $data["WoRomanization"] ?? '';

        $oldstatus = $data["WoOldStatus"];
        $newstatus = $data["WoStatus"];

        if ($oldstatus != $newstatus) {
            $affected = Connection::preparedExecute(
                'UPDATE ' . $this->tbpref . 'words
                SET WoText = ?, WoTextLC = ?, WoTranslation = ?, WoSentence = ?,
                    WoRomanization = ?, WoStatus = ?, WoStatusChanged = NOW(),' .
                WordStatusService::makeScoreRandomInsertUpdate('u') .
                ' WHERE WoID = ?',
                [$data["WoText"], $textLc, $translation, $sentence, $romanization, $newstatus, $data["WoID"]]
            );
        } else {
            $affected = Connection::preparedExecute(
                'UPDATE ' . $this->tbpref . 'words
                SET WoText = ?, WoTextLC = ?, WoTranslation = ?, WoSentence = ?,
                    WoRomanization = ?,' .
                WordStatusService::makeScoreRandomInsertUpdate('u') .
                ' WHERE WoID = ?',
                [$data["WoText"], $textLc, $translation, $sentence, $romanization, $data["WoID"]]
            );
        }

        return $affected > 0 ? "Updated" : "No changes made";
    }
}
