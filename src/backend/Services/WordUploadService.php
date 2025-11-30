<?php declare(strict_types=1);
/**
 * Word Upload Service - Business logic for importing terms from files or text
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
use Lwt\Database\Escaping;
use Lwt\Database\Settings;
use Lwt\Database\Maintenance;
use Lwt\Database\TextParsing;

require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/WordStatusService.php';
require_once __DIR__ . '/ExpressionService.php';

/**
 * Service class for importing words/terms from files or text input.
 *
 * Handles parsing and importing terms in various formats (CSV, TSV, hash-delimited).
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class WordUploadService
{
    private string $tbpref;

    public function __construct()
    {
        $this->tbpref = Globals::getTablePrefix();
    }

    /**
     * Get language data for a specific language.
     *
     * @param int $langId Language ID
     *
     * @return array|null Language data or null if not found
     */
    public function getLanguageData(int $langId): ?array
    {
        $sql = "SELECT * FROM {$this->tbpref}languages WHERE LgID = $langId";
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        return $record ?: null;
    }

    /**
     * Check if local infile is enabled in MySQL and PHP.
     *
     * Note: Even if MySQL server has local_infile enabled, PHP might not allow it.
     * We check both the server setting and the PHP mysqli setting.
     *
     * @return bool True if local_infile is enabled on both server and client
     */
    public function isLocalInfileEnabled(): bool
    {
        // Check MySQL server setting
        $serverValue = Connection::fetchValue("SELECT @@GLOBAL.local_infile as value");
        if (!in_array($serverValue, [1, '1', 'ON'])) {
            return false;
        }

        // Check PHP mysqli setting
        if (!ini_get('mysqli.allow_local_infile')) {
            return false;
        }

        return true;
    }

    /**
     * Parse column mapping from request.
     *
     * @param array $columns Column assignments from form (Col1-Col5)
     * @param bool  $removeSpaces Whether language removes spaces
     *
     * @return array{columns: array, fields: array}
     */
    public function parseColumnMapping(array $columns, bool $removeSpaces): array
    {
        /** @var array<int, string> $col */
        $col = [];
        $fields = ["txt" => 0, "tr" => 0, "ro" => 0, "se" => 0, "tl" => 0];

        // Remove duplicates and keep unique
        $columns = array_unique($columns);

        $max = max(array_keys($columns));
        for ($j = 1; $j <= $max; $j++) {
            if (!isset($columns[$j])) {
                $col[$j] = '@dummy';
            } else {
                switch ($columns[$j]) {
                    case 'w':
                        $col[$j] = $removeSpaces ? '@wotext' : 'WoText';
                        $fields["txt"] = $j;
                        break;
                    case 't':
                        $col[$j] = 'WoTranslation';
                        $fields["tr"] = $j;
                        break;
                    case 'r':
                        $col[$j] = 'WoRomanization';
                        $fields["ro"] = $j;
                        break;
                    case 's':
                        $col[$j] = 'WoSentence';
                        $fields["se"] = $j;
                        break;
                    case 'g':
                        $col[$j] = '@taglist';
                        $fields["tl"] = $j;
                        break;
                    case 'x':
                        if ($j == $max) {
                            unset($col[$j]);
                        } else {
                            $col[$j] = '@dummy';
                        }
                        break;
                }
            }
        }

        return ['columns' => $col, 'fields' => $fields];
    }

    /**
     * Get delimiter character from tab type.
     *
     * @param string $tabType Tab type (c, t, h)
     *
     * @return string Delimiter character
     */
    public function getDelimiter(string $tabType): string
    {
        return match ($tabType) {
            'c' => ',',
            'h' => '#',
            default => "\t",
        };
    }

    /**
     * Get delimiter for SQL LOAD DATA statement.
     *
     * @param string $tabType Tab type (c, t, h)
     *
     * @return string SQL delimiter string
     */
    public function getSqlDelimiter(string $tabType): string
    {
        return match ($tabType) {
            'c' => ',',
            'h' => '#',
            default => "\\t",
        };
    }

    /**
     * Create a temporary file from text input.
     *
     * @param string $content Text content to write
     *
     * @return string Path to temporary file
     */
    public function createTempFile(string $content): string
    {
        $fileName = tempnam(sys_get_temp_dir(), "LWT");
        $temp = fopen($fileName, "w");
        fwrite($temp, Escaping::prepareTextdata($content));
        fclose($temp);
        return $fileName;
    }

    /**
     * Import terms using simple import (no tags, no overwrite).
     *
     * @param int    $langId        Language ID
     * @param array  $fields        Field indexes
     * @param string $columnsClause SQL columns clause
     * @param string $delimiter     Field delimiter
     * @param string $fileName      Path to input file
     * @param int    $status        Word status
     * @param bool   $ignoreFirst   Ignore first line
     *
     * @return void
     */
    public function importSimple(
        int $langId,
        array $fields,
        string $columnsClause,
        string $delimiter,
        string $fileName,
        int $status,
        bool $ignoreFirst
    ): void {
        $removeSpaces = (bool) Connection::fetchValue(
            "SELECT LgRemoveSpaces AS value FROM {$this->tbpref}languages WHERE LgID = $langId"
        );

        if ($this->isLocalInfileEnabled()) {
            $this->importSimpleWithLoadData(
                $langId,
                $removeSpaces,
                $columnsClause,
                $delimiter,
                $fileName,
                $status,
                $ignoreFirst
            );
        } else {
            $this->importSimpleWithPHP(
                $langId,
                $fields,
                $removeSpaces,
                $delimiter,
                $fileName,
                $status,
                $ignoreFirst
            );
        }
    }

    /**
     * Import terms using LOAD DATA LOCAL INFILE.
     *
     * @param int    $langId        Language ID
     * @param bool   $removeSpaces  Whether to remove spaces
     * @param string $columnsClause SQL columns clause
     * @param string $delimiter     Field delimiter
     * @param string $fileName      Path to input file
     * @param int    $status        Word status
     * @param bool   $ignoreFirst   Ignore first line
     *
     * @return void
     */
    private function importSimpleWithLoadData(
        int $langId,
        bool $removeSpaces,
        string $columnsClause,
        string $delimiter,
        string $fileName,
        int $status,
        bool $ignoreFirst
    ): void {
        $sql = "LOAD DATA LOCAL INFILE " . Escaping::toSqlSyntax($fileName) .
            " IGNORE INTO TABLE {$this->tbpref}words
            FIELDS TERMINATED BY '$delimiter' ENCLOSED BY '\"' LINES TERMINATED BY '\\n' " .
            ($ignoreFirst ? "IGNORE 1 LINES " : "") .
            "$columnsClause
            SET WoLgID = $langId, " .
            ($removeSpaces ?
                'WoTextLC = LOWER(REPLACE(@wotext," ","")), WoText = REPLACE(@wotext, " ", "")' :
                'WoTextLC = LOWER(WoText)') . ",
            WoStatus = $status, WoStatusChanged = NOW(), " .
            WordStatusService::makeScoreRandomInsertUpdate('u');

        Connection::execute($sql);
    }

    /**
     * Import terms using PHP parsing (fallback when LOAD DATA not available).
     *
     * @param int    $langId       Language ID
     * @param array  $fields       Field indexes
     * @param bool   $removeSpaces Whether to remove spaces
     * @param string $delimiter    Field delimiter
     * @param string $fileName     Path to input file
     * @param int    $status       Word status
     * @param bool   $ignoreFirst  Ignore first line
     *
     * @return void
     */
    private function importSimpleWithPHP(
        int $langId,
        array $fields,
        bool $removeSpaces,
        string $delimiter,
        string $fileName,
        int $status,
        bool $ignoreFirst
    ): void {
        $handle = fopen($fileName, 'r');
        $dataText = fread($handle, filesize($fileName));
        fclose($handle);

        $values = [];
        $i = 0;

        foreach (explode(PHP_EOL, $dataText) as $line) {
            if ($i++ == 0 && $ignoreFirst) {
                continue;
            }

            if (empty(trim($line))) {
                continue;
            }

            $row = [];
            $parsedLine = explode($delimiter, $line);

            if (!isset($parsedLine[$fields["txt"] - 1])) {
                continue;
            }

            $wotext = $parsedLine[$fields["txt"] - 1];

            // Fill WoText and WoTextLC
            if ($removeSpaces) {
                $row[] = str_replace(" ", "", $wotext);
                $row[] = mb_strtolower(str_replace(" ", "", $wotext));
            } else {
                $row[] = $wotext;
                $row[] = mb_strtolower($wotext);
            }

            if ($fields["tr"] != 0 && isset($parsedLine[$fields["tr"] - 1])) {
                $row[] = $parsedLine[$fields["tr"] - 1];
            }
            if ($fields["ro"] != 0 && isset($parsedLine[$fields["ro"] - 1])) {
                $row[] = $parsedLine[$fields["ro"] - 1];
            }
            if ($fields["se"] != 0 && isset($parsedLine[$fields["se"] - 1])) {
                $row[] = $parsedLine[$fields["se"] - 1];
            }

            $row = array_map([Escaping::class, 'toSqlSyntax'], $row);
            $row = array_merge(
                $row,
                [
                    (string)$langId, (string)$status, "NOW()",
                    WordStatusService::SCORE_FORMULA_TODAY, WordStatusService::SCORE_FORMULA_TOMORROW, "RAND()"
                ]
            );
            $values[] = "(" . implode(",", $row) . ")";
        }

        if (!empty($values)) {
            Connection::query(
                "INSERT INTO {$this->tbpref}words(
                    WoText, WoTextLC, " .
                    ($fields["tr"] != 0 ? 'WoTranslation, ' : '') .
                    ($fields["ro"] != 0 ? 'WoRomanization, ' : '') .
                    ($fields["se"] != 0 ? 'WoSentence, ' : '') .
                    "WoLgID, WoStatus, WoStatusChanged,
                    WoTodayScore, WoTomorrowScore, WoRandom
                )
                VALUES " . implode(',', $values)
            );
        }
    }

    /**
     * Import terms with complete processing (handles tags, overwrite modes).
     *
     * @param int    $langId        Language ID
     * @param array  $fields        Field indexes
     * @param string $columnsClause SQL columns clause
     * @param string $delimiter     Field delimiter
     * @param string $fileName      Path to input file
     * @param int    $status        Word status
     * @param int    $overwrite     Overwrite mode
     * @param bool   $ignoreFirst   Ignore first line
     * @param string $translDelim   Translation delimiter
     * @param string $tabType       Tab type (c, t, h)
     *
     * @return void
     */
    public function importComplete(
        int $langId,
        array $fields,
        string $columnsClause,
        string $delimiter,
        string $fileName,
        int $status,
        int $overwrite,
        bool $ignoreFirst,
        string $translDelim,
        string $tabType
    ): void {
        $removeSpaces = (bool) Connection::fetchValue(
            "SELECT LgRemoveSpaces AS value FROM {$this->tbpref}languages WHERE LgID = $langId"
        );

        $this->initTempTables();

        if ($this->isLocalInfileEnabled()) {
            $this->loadDataToTempTable(
                $removeSpaces,
                $fields,
                $columnsClause,
                $delimiter,
                $fileName,
                $ignoreFirst
            );
        } else {
            $this->loadDataToTempTableWithPHP(
                $removeSpaces,
                $fields,
                $delimiter,
                $fileName,
                $ignoreFirst
            );
        }

        // Handle translation merging for overwrite modes 4 and 5
        if ($overwrite > 3) {
            $this->handleTranslationMerge($langId, $translDelim, $tabType);
        }

        // Execute the main import/update query
        $this->executeMainImportQuery($langId, $fields, $status, $overwrite);

        // Handle tags if tag list field is specified
        if ($fields["tl"] != 0) {
            $this->handleTagsImport($langId);
        }

        $this->cleanupTempTables();
    }

    /**
     * Initialize temporary tables for import.
     *
     * @return void
     */
    private function initTempTables(): void
    {
        // Try to increase heap table size for better performance
        // This requires SUPER privileges, so we gracefully handle failures
        try {
            Connection::execute('SET GLOBAL max_heap_table_size = 1024 * 1024 * 1024 * 2');
        } catch (\Exception $e) {
            // Ignore - this is an optimization, not a requirement
        }
        Connection::execute(
            "CREATE TEMPORARY TABLE IF NOT EXISTS {$this->tbpref}numbers(
                n tinyint(3) unsigned NOT NULL
            )"
        );
        Connection::execute(
            "INSERT IGNORE INTO {$this->tbpref}numbers(n) VALUES ('1'),('2'),('3'),
            ('4'),('5'),('6'),('7'),('8'),('9')"
        );
    }

    /**
     * Load data into temporary table using LOAD DATA.
     *
     * @param bool   $removeSpaces  Whether to remove spaces
     * @param array  $fields        Field indexes
     * @param string $columnsClause SQL columns clause
     * @param string $delimiter     Field delimiter
     * @param string $fileName      Path to input file
     * @param bool   $ignoreFirst   Ignore first line
     *
     * @return void
     */
    private function loadDataToTempTable(
        bool $removeSpaces,
        array $fields,
        string $columnsClause,
        string $delimiter,
        string $fileName,
        bool $ignoreFirst
    ): void {
        $sql = "LOAD DATA LOCAL INFILE " . Escaping::toSqlSyntax($fileName) .
            " INTO TABLE {$this->tbpref}tempwords
            FIELDS TERMINATED BY '$delimiter' ENCLOSED BY '\"' LINES TERMINATED BY '\\n' " .
            ($ignoreFirst ? "IGNORE 1 LINES " : "") .
            "$columnsClause SET " .
            ($removeSpaces ?
                'WoTextLC = LOWER(REPLACE(@wotext," ","")), WoText = REPLACE(@wotext," ","")' :
                'WoTextLC = LOWER(WoText)');

        if ($fields["tl"] != 0) {
            $sql .= ', WoTaglist = REPLACE(@taglist, " ", ",")';
        }

        Connection::execute($sql);
    }

    /**
     * Load data into temporary table using PHP (fallback).
     *
     * @param bool   $removeSpaces Whether to remove spaces
     * @param array  $fields       Field indexes
     * @param string $delimiter    Field delimiter
     * @param string $fileName     Path to input file
     * @param bool   $ignoreFirst  Ignore first line
     *
     * @return void
     */
    private function loadDataToTempTableWithPHP(
        bool $removeSpaces,
        array $fields,
        string $delimiter,
        string $fileName,
        bool $ignoreFirst
    ): void {
        $handle = fopen($fileName, 'r');
        $dataText = fread($handle, filesize($fileName));
        fclose($handle);

        $values = [];
        $i = 0;

        foreach (explode(PHP_EOL, $dataText) as $line) {
            if ($i++ == 0 && $ignoreFirst) {
                continue;
            }

            if (empty(trim($line))) {
                continue;
            }

            $row = [];
            $parsedLine = explode($delimiter, $line);

            if (!isset($parsedLine[$fields["txt"] - 1])) {
                continue;
            }

            $wotext = $parsedLine[$fields["txt"] - 1];

            // Fill WoText and WoTextLC
            if ($removeSpaces) {
                $row[] = str_replace(" ", "", $wotext);
                $row[] = mb_strtolower(str_replace(" ", "", $wotext));
            } else {
                $row[] = $wotext;
                $row[] = mb_strtolower($wotext);
            }

            if ($fields["tr"] != 0 && isset($parsedLine[$fields["tr"] - 1])) {
                $row[] = $parsedLine[$fields["tr"] - 1];
            }
            if ($fields["ro"] != 0 && isset($parsedLine[$fields["ro"] - 1])) {
                $row[] = $parsedLine[$fields["ro"] - 1];
            }
            if ($fields["se"] != 0 && isset($parsedLine[$fields["se"] - 1])) {
                $row[] = $parsedLine[$fields["se"] - 1];
            }
            if ($fields["tl"] != 0 && isset($parsedLine[$fields["tl"] - 1])) {
                $row[] = str_replace(" ", ",", $parsedLine[$fields['tl'] - 1]);
            }

            $values[] = "(" . implode(
                ",",
                array_map([Escaping::class, 'toSqlSyntax'], $row)
            ) . ")";
        }

        if (!empty($values)) {
            Connection::query(
                "INSERT INTO {$this->tbpref}tempwords(
                    WoText, WoTextLC" .
                    ($fields["tr"] != 0 ? ', WoTranslation' : '') .
                    ($fields["ro"] != 0 ? ', WoRomanization' : '') .
                    ($fields["se"] != 0 ? ', WoSentence' : '') .
                    ($fields["tl"] != 0 ? ", WoTaglist" : "") .
                ")
                VALUES " . implode(',', $values)
            );
        }
    }

    /**
     * Handle translation merging for overwrite modes 4 and 5.
     *
     * @param int    $langId      Language ID
     * @param string $translDelim Translation delimiter from import
     * @param string $tabType     Tab type (c, t, h)
     *
     * @return void
     */
    private function handleTranslationMerge(int $langId, string $translDelim, string $tabType): void
    {
        Connection::execute(
            "CREATE TEMPORARY TABLE IF NOT EXISTS {$this->tbpref}merge_words(
                MID mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
                MText varchar(250) NOT NULL,
                MTranslation varchar(250) NOT NULL,
                PRIMARY KEY (MID),
                UNIQUE KEY (MText, MTranslation)
            ) DEFAULT CHARSET=utf8"
        );

        $wosep = Settings::getWithDefault('set-term-translation-delimiters');
        if (empty($wosep)) {
            $wosep = match ($tabType) {
                'h' => '#',
                'c' => ',',
                default => "\t",
            };
        }

        $seplen = mb_strlen($wosep, 'UTF-8');
        $woTrRepl = $this->tbpref . 'words.WoTranslation';
        for ($i = 1; $i < $seplen; $i++) {
            $woTrRepl = 'REPLACE(' . $woTrRepl . ', ' .
                Escaping::toSqlSyntax($wosep[$i]) . ', ' .
                Escaping::toSqlSyntax($wosep[0]) . ')';
        }

        // Insert existing translations
        Connection::execute(
            "INSERT IGNORE INTO {$this->tbpref}merge_words(MText,MTranslation)
            SELECT b.WoTextLC,
            trim(
                SUBSTRING_INDEX(
                    SUBSTRING_INDEX(b.WoTranslation, " . Escaping::toSqlSyntax($wosep[0]) . ", {$this->tbpref}numbers.n),
                    " . Escaping::toSqlSyntax($wosep[0]) . ", -1
                )
            ) name
            FROM {$this->tbpref}numbers
            INNER JOIN (
                SELECT {$this->tbpref}words.WoTextLC as WoTextLC, $woTrRepl as WoTranslation
                FROM {$this->tbpref}tempwords
                LEFT JOIN {$this->tbpref}words
                ON {$this->tbpref}words.WoTextLC = {$this->tbpref}tempwords.WoTextLC
                    AND {$this->tbpref}words.WoTranslation != '*'
                    AND {$this->tbpref}words.WoLgID = $langId
            ) b
            ON CHAR_LENGTH(b.WoTranslation)-CHAR_LENGTH(REPLACE(b.WoTranslation, " .
                Escaping::toSqlSyntax($wosep[0]) . ", ''))>= {$this->tbpref}numbers.n-1
            ORDER BY b.WoTextLC, n"
        );

        // Handle import delimiter
        $tesep = $translDelim;
        if (empty($tesep)) {
            $tesep = match ($tabType) {
                'h' => '#',
                'c' => ',',
                default => "\t",
            };
        }

        $seplen = mb_strlen($tesep, 'UTF-8');
        $woTrRepl = $this->tbpref . 'tempwords.WoTranslation';
        for ($i = 1; $i < $seplen; $i++) {
            $woTrRepl = 'REPLACE(' . $woTrRepl . ', ' .
                Escaping::toSqlSyntax($tesep[$i]) . ', ' .
                Escaping::toSqlSyntax($tesep[0]) . ')';
        }

        // Insert new translations
        Connection::execute(
            "INSERT IGNORE INTO {$this->tbpref}merge_words(MText,MTranslation)
            SELECT {$this->tbpref}tempwords.WoTextLC,
            trim(
                SUBSTRING_INDEX(
                    SUBSTRING_INDEX($woTrRepl," . Escaping::toSqlSyntax($tesep[0]) . ",
                        {$this->tbpref}numbers.n
                    ), " . Escaping::toSqlSyntax($tesep[0]) . ", -1
                )
            ) name
            FROM {$this->tbpref}numbers
            INNER JOIN {$this->tbpref}tempwords
            ON CHAR_LENGTH({$this->tbpref}tempwords.WoTranslation)-CHAR_LENGTH(REPLACE($woTrRepl, " .
                Escaping::toSqlSyntax($tesep[0]) . ", ''))>= {$this->tbpref}numbers.n-1
            ORDER BY {$this->tbpref}tempwords.WoTextLC, n"
        );

        // Determine separator for output
        if ($wosep[0] == ',' || $wosep[0] == ';') {
            $wosep = $wosep[0] . ' ';
        } else {
            $wosep = ' ' . $wosep[0] . ' ';
        }

        // Update tempwords with merged translations
        Connection::execute(
            "UPDATE {$this->tbpref}tempwords
            LEFT JOIN (
                SELECT MText, GROUP_CONCAT(trim(MTranslation)
                    ORDER BY MID
                    SEPARATOR " . Escaping::toSqlSyntaxNoTrimNoNull($wosep) . "
                ) AS Translation
                FROM {$this->tbpref}merge_words
                GROUP BY MText
            ) A
            ON MText=WoTextLC
            SET WoTranslation = Translation"
        );

        Connection::execute("DROP TABLE {$this->tbpref}merge_words");
    }

    /**
     * Execute the main import/update query based on overwrite mode.
     *
     * @param int   $langId    Language ID
     * @param array $fields    Field indexes
     * @param int   $status    Word status
     * @param int   $overwrite Overwrite mode (0-5)
     *
     * @return void
     */
    private function executeMainImportQuery(int $langId, array $fields, int $status, int $overwrite): void
    {
        if ($overwrite != 3 && $overwrite != 5) {
            $sql = "INSERT " . ($overwrite != 0 ? '' : 'IGNORE ') .
                " INTO {$this->tbpref}words (
                    WoTextLC, WoText, WoTranslation, WoRomanization, WoSentence,
                    WoStatus, WoStatusChanged, WoLgID,
                    " . WordStatusService::makeScoreRandomInsertUpdate('iv') . "
                )
                SELECT *, $langId as LgID, " . WordStatusService::makeScoreRandomInsertUpdate('id') . "
                FROM (
                    SELECT WoTextLC, WoText, WoTranslation, WoRomanization,
                    WoSentence, $status AS WoStatus,
                    NOW() AS WoStatusChanged
                    FROM {$this->tbpref}tempwords
                ) AS tw";

            if ($overwrite == 1 || $overwrite == 4) {
                $sql .= " ON DUPLICATE KEY UPDATE " .
                    ($fields["tr"] ? "{$this->tbpref}words.WoTranslation = tw.WoTranslation, " : "") .
                    ($fields["ro"] ? "{$this->tbpref}words.WoRomanization = tw.WoRomanization, " : '') .
                    ($fields["se"] ? "{$this->tbpref}words.WoSentence = tw.WoSentence, " : '') .
                    "{$this->tbpref}words.WoStatus = tw.WoStatus,
                    {$this->tbpref}words.WoStatusChanged = tw.WoStatusChanged";
            }

            if ($overwrite == 2) {
                $sql .= " ON DUPLICATE KEY UPDATE
                    {$this->tbpref}words.WoTranslation = CASE
                        WHEN {$this->tbpref}words.WoTranslation = \"*\" THEN tw.WoTranslation
                        ELSE {$this->tbpref}words.WoTranslation
                    END,
                    {$this->tbpref}words.WoRomanization = CASE
                        WHEN {$this->tbpref}words.WoRomanization IS NULL THEN tw.WoRomanization
                        ELSE {$this->tbpref}words.WoRomanization
                    END,
                    {$this->tbpref}words.WoSentence = CASE
                        WHEN {$this->tbpref}words.WoSentence IS NULL THEN tw.WoSentence
                        ELSE {$this->tbpref}words.WoSentence
                    END,
                    {$this->tbpref}words.WoStatusChanged = CASE
                        WHEN {$this->tbpref}words.WoSentence IS NULL OR {$this->tbpref}words.WoRomanization IS NULL OR {$this->tbpref}words.WoTranslation = \"*\"
                        THEN tw.WoStatusChanged
                        ELSE {$this->tbpref}words.WoStatusChanged
                    END";
            }
        } else {
            // Overwrite modes 3 and 5: only update existing, don't insert new
            $sql = "UPDATE {$this->tbpref}words AS a
                JOIN {$this->tbpref}tempwords AS b
                ON a.WoTextLC = b.WoTextLC SET
                a.WoTranslation = CASE
                    WHEN b.WoTranslation = '' OR b.WoTranslation = '*' THEN a.WoTranslation
                    ELSE b.WoTranslation
                END,
                a.WoRomanization = CASE
                    WHEN b.WoRomanization IS NULL OR b.WoRomanization = '' THEN a.WoRomanization
                    ELSE b.WoRomanization
                END,
                a.WoSentence = CASE
                    WHEN b.WoSentence IS NULL OR b.WoSentence = '' THEN a.WoSentence
                    ELSE b.WoSentence
                END,
                a.WoStatusChanged = CASE
                    WHEN (b.WoTranslation = '' OR b.WoTranslation = '*') AND (b.WoRomanization IS NULL OR b.WoRomanization = '') AND (b.WoSentence IS NULL OR b.WoSentence = '')
                    THEN a.WoStatusChanged
                    ELSE NOW()
                END";
        }

        Connection::execute($sql);
    }

    /**
     * Handle tags import.
     *
     * @param int $langId Language ID
     *
     * @return void
     */
    private function handleTagsImport(int $langId): void
    {
        // Insert new tags
        Connection::execute(
            "INSERT IGNORE INTO {$this->tbpref}tags (TgText)
            SELECT name FROM (
                SELECT {$this->tbpref}tempwords.WoTextLC,
                SUBSTRING_INDEX(
                    SUBSTRING_INDEX(
                        {$this->tbpref}tempwords.WoTaglist, ',',
                        {$this->tbpref}numbers.n
                    ), ',', -1) name
                FROM {$this->tbpref}numbers
                INNER JOIN {$this->tbpref}tempwords
                ON CHAR_LENGTH({$this->tbpref}tempwords.WoTaglist)-CHAR_LENGTH(REPLACE({$this->tbpref}tempwords.WoTaglist, ',', ''))>={$this->tbpref}numbers.n-1
                ORDER BY WoTextLC, n) A"
        );

        // Link words to tags
        Connection::execute("INSERT IGNORE INTO {$this->tbpref}wordtags
            SELECT WoID, TgID
            FROM (
                SELECT {$this->tbpref}tempwords.WoTextLC, SUBSTRING_INDEX(
                    SUBSTRING_INDEX(
                        {$this->tbpref}tempwords.WoTaglist, ',', {$this->tbpref}numbers.n
                    ), ',', -1) name
                FROM {$this->tbpref}numbers
                INNER JOIN {$this->tbpref}tempwords
                ON CHAR_LENGTH({$this->tbpref}tempwords.WoTaglist)-CHAR_LENGTH(REPLACE({$this->tbpref}tempwords.WoTaglist, ',', ''))>={$this->tbpref}numbers.n-1
                ORDER BY WoTextLC, n
            ) A, {$this->tbpref}tags, {$this->tbpref}words
            WHERE name=TgText AND A.WoTextLC={$this->tbpref}words.WoTextLC AND WoLgID=$langId");

        TagService::getAllTermTags(true);
    }

    /**
     * Cleanup temporary tables.
     *
     * @return void
     */
    private function cleanupTempTables(): void
    {
        Connection::execute("DROP TABLE IF EXISTS {$this->tbpref}numbers");
        Connection::execute("TRUNCATE {$this->tbpref}tempwords");
    }

    /**
     * Import tags only (no terms).
     *
     * @param array  $fields      Field indexes
     * @param string $tabType     Tab type (c, t, h)
     * @param string $fileName    Path to input file
     * @param bool   $ignoreFirst Ignore first line
     *
     * @return void
     */
    public function importTagsOnly(array $fields, string $tabType, string $fileName, bool $ignoreFirst): void
    {
        $columns = '';
        for ($j = 1; $j <= $fields["tl"]; $j++) {
            $columns .= ($j == 1 ? '(' : ',') . ($j == $fields["tl"] ? '@taglist' : '@dummy');
        }
        $columns .= ')';

        $delimiter = ' ' . $this->getSqlDelimiter($tabType);

        if ($this->isLocalInfileEnabled()) {
            $sql = "LOAD DATA LOCAL INFILE " . Escaping::toSqlSyntax($fileName) .
                " IGNORE INTO TABLE {$this->tbpref}tempwords
                FIELDS TERMINATED BY '$delimiter' ENCLOSED BY '\"' LINES TERMINATED BY '\\n' " .
                ($ignoreFirst ? "IGNORE 1 LINES " : "") .
                "$columns
                SET WoTextLC = REPLACE(@taglist, ' ', ',')";
            Connection::execute($sql);
        } else {
            $handle = fopen($fileName, 'r');
            $dataText = fread($handle, filesize($fileName));
            fclose($handle);

            $texts = [];
            $i = 0;
            $realDelimiter = $this->getDelimiter($tabType);

            foreach (explode(PHP_EOL, $dataText) as $line) {
                if ($i++ == 0 && $ignoreFirst) {
                    continue;
                }

                if (empty(trim($line))) {
                    continue;
                }

                $parts = explode($realDelimiter, $line);
                if (!isset($parts[$fields["tl"] - 1])) {
                    continue;
                }

                $tags = $parts[$fields["tl"] - 1];
                $tags = str_replace(' ', ',', $tags);
                $texts[] = "(" . Escaping::toSqlSyntax($tags) . ")";
            }

            if (!empty($texts)) {
                Connection::query(
                    "INSERT INTO {$this->tbpref}tempwords(WoTextLC)
                    VALUES " . implode(',', $texts)
                );
            }
        }

        // Create numbers table and insert tags
        Connection::execute(
            "CREATE TEMPORARY TABLE IF NOT EXISTS {$this->tbpref}numbers(
                n tinyint(3) unsigned NOT NULL
            )");
        Connection::execute("INSERT IGNORE INTO {$this->tbpref}numbers(n) VALUES ('1'),('2'),('3'),
            ('4'),('5'),('6'),('7'),('8'),('9')");

        Connection::execute("INSERT IGNORE INTO {$this->tbpref}tags (TgText)
            SELECT NAME FROM (
                SELECT SUBSTRING_INDEX(
                    SUBSTRING_INDEX(
                        {$this->tbpref}tempwords.WoTextLC, ',', {$this->tbpref}numbers.n
                    ), ',', -1) name
                FROM {$this->tbpref}numbers
                INNER JOIN {$this->tbpref}tempwords
                ON CHAR_LENGTH({$this->tbpref}tempwords.WoTextLC)-CHAR_LENGTH(REPLACE({$this->tbpref}tempwords.WoTextLC, ',', ''))>= {$this->tbpref}numbers.n-1
                ORDER BY WoTextLC, n) A");

        $this->cleanupTempTables();
        TagService::getAllTermTags(true);
    }

    /**
     * Handle multi-word expressions after import.
     *
     * @param int    $langId     Language ID
     * @param string $lastUpdate Last update timestamp
     *
     * @return void
     */
    public function handleMultiwords(int $langId, string $lastUpdate): void
    {
        $mwords = (int) Connection::fetchValue(
            "SELECT count(*) AS value FROM {$this->tbpref}words
            WHERE WoWordCount > 1 AND WoCreated > " . Escaping::toSqlSyntax($lastUpdate)
        );

        if ($mwords > 40) {
            // Bulk update: delete and recreate all text items
            Connection::execute("DELETE FROM {$this->tbpref}sentences WHERE SeLgID = $langId"
            );
            Connection::execute(
                "DELETE FROM {$this->tbpref}textitems2 WHERE Ti2LgID = $langId"
            );
            Maintenance::adjustAutoIncrement('sentences', 'SeID');

            $sql = "SELECT TxID, TxText FROM {$this->tbpref}texts
                WHERE TxLgID = $langId ORDER BY TxID";
            $res = Connection::query($sql);
            while ($record = mysqli_fetch_assoc($res)) {
                $txtid = (int) $record["TxID"];
                $txttxt = (string) $record["TxText"];
                TextParsing::splitCheck($txttxt, $langId, $txtid);
            }
            mysqli_free_result($res);
        } elseif ($mwords > 0) {
            // Update individual multi-word expressions
            $sqlarr = [];
            $res = Connection::query(
                "SELECT WoID, WoTextLC, WoWordCount
                FROM {$this->tbpref}words
                WHERE WoWordCount > 1 AND WoCreated > " . Escaping::toSqlSyntax($lastUpdate)
            );
            while ($record = mysqli_fetch_assoc($res)) {
                $len = (int) $record['WoWordCount'];
                $wid = (int) $record['WoID'];
                $textlc = (string) $record['WoTextLC'];
                $expressionService = new ExpressionService();
                $sqlarr[] = $expressionService->insertExpressions($textlc, $langId, $wid, $len, 2);
            }
            mysqli_free_result($res);
            $sqlarr = array_filter($sqlarr);

            if (!empty($sqlarr)) {
                $sqltext = "INSERT INTO {$this->tbpref}textitems2 (
                    Ti2WoID, Ti2LgID, Ti2TxID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text
                ) VALUES " . rtrim(implode(',', $sqlarr), ',');
                Connection::query($sqltext);
            }
        }
    }

    /**
     * Get the last word status change timestamp.
     *
     * @return string|null Last update timestamp
     */
    public function getLastWordUpdate(): ?string
    {
        return Connection::fetchValue(
            "SELECT max(WoStatusChanged) AS value FROM {$this->tbpref}words"
        );
    }

    /**
     * Link imported words to text items.
     *
     * @return void
     */
    public function linkWordsToTextItems(): void
    {
        Connection::execute(
            "UPDATE {$this->tbpref}words
            JOIN {$this->tbpref}textitems2
            ON WoWordCount=1 AND Ti2WoID=0 AND lower(Ti2Text)=WoTextLC AND Ti2LgID = WoLgID
            SET Ti2WoID=WoID");
    }

    /**
     * Count imported terms.
     *
     * @param string $lastUpdate Last update timestamp
     *
     * @return int Number of imported terms
     */
    public function countImportedTerms(string $lastUpdate): int
    {
        return (int) Connection::fetchValue(
            "SELECT count(*) AS value FROM {$this->tbpref}words
            WHERE WoStatusChanged > " . Escaping::toSqlSyntax($lastUpdate)
        );
    }

    /**
     * Get imported terms for display.
     *
     * @param string $lastUpdate Last update timestamp
     * @param int    $offset     Offset for pagination
     * @param int    $limit      Limit for pagination
     *
     * @return array Imported terms data
     */
    public function getImportedTerms(string $lastUpdate, int $offset, int $limit): array
    {
        $sql = "SELECT w.WoID, w.WoText, w.WoTextLC, w.WoTranslation,
                w.WoRomanization, w.WoSentence, w.WoStatus,
                GROUP_CONCAT(t.TgText ORDER BY t.TgText SEPARATOR ', ') as taglist,
                CASE WHEN w.WoSentence != '' AND w.WoSentence LIKE CONCAT('%{', w.WoText, '}%')
                    THEN 1 ELSE 0 END as SentOK
            FROM {$this->tbpref}words w
            LEFT JOIN {$this->tbpref}wordtags wt ON w.WoID = wt.WtWoID
            LEFT JOIN {$this->tbpref}tags t ON wt.WtTgID = t.TgID
            WHERE w.WoStatusChanged > " . Escaping::toSqlSyntax($lastUpdate) . "
            GROUP BY w.WoID
            ORDER BY w.WoText
            LIMIT $offset, $limit";

        $result = Connection::query($sql);
        $terms = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $terms[] = $row;
        }
        mysqli_free_result($result);

        return $terms;
    }

    /**
     * Get right-to-left setting for a language.
     *
     * @param int $langId Language ID
     *
     * @return bool True if language is RTL
     */
    public function isRightToLeft(int $langId): bool
    {
        return (bool) Connection::fetchValue(
            "SELECT LgRightToLeft AS value FROM {$this->tbpref}languages WHERE LgID = $langId"
        );
    }
}
