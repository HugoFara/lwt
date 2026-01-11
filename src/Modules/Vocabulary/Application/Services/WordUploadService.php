<?php

/**
 * Word Upload Service - Business logic for importing terms from files or text
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Vocabulary\Application\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Application\Services;

use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\Escaping;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Shared\Infrastructure\Database\Maintenance;
use Lwt\Shared\Infrastructure\Database\TextParsing;
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;
use Lwt\Modules\Tags\Application\TagsFacade;

/**
 * Service class for importing words/terms from files or text input.
 *
 * Handles parsing and importing terms in various formats (CSV, TSV, hash-delimited).
 *
 * @category Lwt
 * @package  Lwt\Modules\Vocabulary\Application\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class WordUploadService
{
    /**
     * Maximum number of rows to insert in a single batch.
     * Keeps memory usage reasonable for large imports.
     */
    private const BATCH_SIZE = 500;

    /**
     * Get language data for a specific language.
     *
     * @param int $langId Language ID
     *
     * @return array|null Language data or null if not found
     */
    public function getLanguageData(int $langId): ?array
    {
        return QueryBuilder::table('languages')
            ->where('LgID', '=', $langId)
            ->firstPrepared();
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
        /** @var int|string|null $serverValue */
        $serverValue = Connection::fetchValue("SELECT @@GLOBAL.local_infile as value");
        if (!in_array($serverValue, [1, '1', 'ON'])) {
            return false;
        }

        // Check PHP mysqli setting
        $phpValue = ini_get('mysqli.allow_local_infile');
        if ($phpValue === false || $phpValue === '' || $phpValue === '0') {
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
     * @return array{columns: array<int, string>, fields: array{txt: int, tr: int, ro: int, se: int, tl: int}}
     */
    public function parseColumnMapping(array $columns, bool $removeSpaces): array
    {
        /** @var array<int, string> $col */
        $col = [];
        $fields = ["txt" => 0, "tr" => 0, "ro" => 0, "se" => 0, "tl" => 0];

        // Remove duplicates and keep unique
        $columns = array_unique($columns);

        $keys = array_keys($columns);
        $max = count($keys) > 0 ? max($keys) : 0;
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
        if ($fileName === false) {
            throw new \RuntimeException('Failed to create temporary file for import');
        }
        $temp = fopen($fileName, "w");
        if ($temp === false) {
            throw new \RuntimeException('Failed to open temporary file for writing');
        }
        fwrite($temp, Escaping::prepareTextdata($content));
        fclose($temp);
        return $fileName;
    }

    /**
     * Import terms using simple import (no tags, no overwrite).
     *
     * @param int    $langId        Language ID
     * @param array{txt: int, tr: int, ro: int, se: int, tl?: int} $fields Field indexes
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
        $removeSpaces = (bool) QueryBuilder::table('languages')
            ->where('LgID', '=', $langId)
            ->valuePrepared('LgRemoveSpaces');

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
        $bindings = [$fileName, $langId, $status];
        $sql = "LOAD DATA LOCAL INFILE ?
            IGNORE INTO TABLE words
            FIELDS TERMINATED BY '$delimiter' ENCLOSED BY '\"' LINES TERMINATED BY '\\n' " .
            ($ignoreFirst ? "IGNORE 1 LINES " : "") .
            "$columnsClause
            SET WoLgID = ?, " .
            ($removeSpaces ?
                'WoTextLC = LOWER(REPLACE(@wotext," ","")), WoText = REPLACE(@wotext, " ", "")' :
                'WoTextLC = LOWER(WoText)') . ",
            WoStatus = ?, WoStatusChanged = NOW(), " .
            TermStatusService::makeScoreRandomInsertUpdate('u');

        $stmt = Connection::prepare($sql);
        $stmt->bind('sis', $fileName, $langId, $status);
        $stmt->execute();
    }

    /**
     * Import terms using PHP parsing (fallback when LOAD DATA not available).
     * Uses chunked batch inserts to handle large files without excessive memory.
     *
     * @param int                                  $langId       Language ID
     * @param array{txt: int, tr: int, ro: int, se: int, tl?: int} $fields Field indexes
     * @param bool                                 $removeSpaces Whether to remove spaces
     * @param string                               $delimiter    Field delimiter
     * @param string                               $fileName     Path to input file
     * @param int                                  $status       Word status
     * @param bool                                 $ignoreFirst  Ignore first line
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
        if ($delimiter === '') {
            return;
        }
        $handle = fopen($fileName, 'r');
        if ($handle === false) {
            return;
        }

        /** @var list<list<int|string>> $rows */
        $rows = [];
        $lineNum = 0;

        while (($line = fgets($handle)) !== false) {
            if ($lineNum++ == 0 && $ignoreFirst) {
                continue;
            }

            $line = rtrim($line, "\r\n");
            if (empty(trim($line))) {
                continue;
            }

            /** @var list<string> $parsedLine */
            $parsedLine = explode($delimiter, $line);

            $txtIdx = $fields["txt"] - 1;
            if (!isset($parsedLine[$txtIdx])) {
                continue;
            }

            $wotext = $parsedLine[$txtIdx];

            /** @var list<int|string> $row */
            $row = [];
            // Fill WoText and WoTextLC
            if ($removeSpaces) {
                $row[] = str_replace(" ", "", $wotext);
                $row[] = mb_strtolower(str_replace(" ", "", $wotext));
            } else {
                $row[] = $wotext;
                $row[] = mb_strtolower($wotext);
            }

            $trIdx = $fields["tr"] - 1;
            $roIdx = $fields["ro"] - 1;
            $seIdx = $fields["se"] - 1;

            if ($fields["tr"] != 0 && isset($parsedLine[$trIdx])) {
                $row[] = $parsedLine[$trIdx];
            }
            if ($fields["ro"] != 0 && isset($parsedLine[$roIdx])) {
                $row[] = $parsedLine[$roIdx];
            }
            if ($fields["se"] != 0 && isset($parsedLine[$seIdx])) {
                $row[] = $parsedLine[$seIdx];
            }

            $row[] = $langId;
            $row[] = $status;

            $rows[] = $row;

            // Execute batch when we reach the batch size
            if (count($rows) >= self::BATCH_SIZE) {
                $this->executeSimpleImportBatch($rows, $fields);
                $rows = [];
            }
        }

        fclose($handle);

        // Execute remaining rows
        if (!empty($rows)) {
            $this->executeSimpleImportBatch($rows, $fields);
        }
    }

    /**
     * Execute a batch insert for simple import.
     *
     * @param list<list<int|string>> $rows   Array of row data
     * @param array{txt: int, tr: int, ro: int, se: int, tl?: int} $fields Field indexes
     *
     * @return void
     */
    private function executeSimpleImportBatch(array $rows, array $fields): void
    {
        if (empty($rows)) {
            return;
        }

        $userId = UserScopedQuery::getUserIdForInsert('words');

        // Build placeholder string for one row
        $rowPlaceholders = '(?, ?';  // WoText, WoTextLC
        if ($fields["tr"] != 0) {
            $rowPlaceholders .= ', ?';
        }
        if ($fields["ro"] != 0) {
            $rowPlaceholders .= ', ?';
        }
        if ($fields["se"] != 0) {
            $rowPlaceholders .= ', ?';
        }
        $rowPlaceholders .= ', ?, ?, NOW(), ' .  // WoLgID, WoStatus, WoStatusChanged
            TermStatusService::SCORE_FORMULA_TODAY . ', ' .
            TermStatusService::SCORE_FORMULA_TOMORROW . ', RAND()';

        if ($userId !== null) {
            $rowPlaceholders .= ', ?';
        }
        $rowPlaceholders .= ')';

        $placeholders = array_fill(0, count($rows), $rowPlaceholders);
        /** @var list<int|string> $params */
        $params = [];
        foreach ($rows as $row) {
            foreach ($row as $value) {
                $params[] = $value;
            }
            if ($userId !== null) {
                $params[] = $userId;
            }
        }

        $sql = "INSERT IGNORE INTO words(
                WoText, WoTextLC, " .
                ($fields["tr"] != 0 ? 'WoTranslation, ' : '') .
                ($fields["ro"] != 0 ? 'WoRomanization, ' : '') .
                ($fields["se"] != 0 ? 'WoSentence, ' : '') .
                "WoLgID, WoStatus, WoStatusChanged,
                WoTodayScore, WoTomorrowScore, WoRandom"
                . UserScopedQuery::insertColumn('words')
            . ")
            VALUES " . implode(',', $placeholders);

        Connection::preparedExecute($sql, $params);
    }

    /**
     * Import terms with complete processing (handles tags, overwrite modes).
     *
     * @param int    $langId        Language ID
     * @param array{txt: int, tr: int, ro: int, se: int, tl: int} $fields Field indexes
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
        $removeSpaces = (bool) QueryBuilder::table('languages')
            ->where('LgID', '=', $langId)
            ->valuePrepared('LgRemoveSpaces');

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
        Connection::execute(
            "CREATE TEMPORARY TABLE IF NOT EXISTS numbers(
                n tinyint(3) unsigned NOT NULL
            )"
        );
        Connection::execute(
            "INSERT IGNORE INTO numbers(n) VALUES ('1'),('2'),('3'),
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
        $sql = "LOAD DATA LOCAL INFILE ?
            INTO TABLE temp_words
            FIELDS TERMINATED BY '$delimiter' ENCLOSED BY '\"' LINES TERMINATED BY '\\n' " .
            ($ignoreFirst ? "IGNORE 1 LINES " : "") .
            "$columnsClause SET " .
            ($removeSpaces ?
                'WoTextLC = LOWER(REPLACE(@wotext," ","")), WoText = REPLACE(@wotext," ","")' :
                'WoTextLC = LOWER(WoText)');

        if ($fields["tl"] != 0) {
            $sql .= ', WoTaglist = REPLACE(@taglist, " ", ",")';
        }

        $stmt = Connection::prepare($sql);
        $stmt->bind('s', $fileName);
        $stmt->execute();
    }

    /**
     * Load data into temporary table using PHP (fallback).
     * Uses chunked batch inserts to handle large files without excessive memory.
     *
     * @param bool   $removeSpaces Whether to remove spaces
     * @param array{txt: int, tr: int, ro: int, se: int, tl: int} $fields Field indexes
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
        if ($delimiter === '') {
            return;
        }
        $handle = fopen($fileName, 'r');
        if ($handle === false) {
            return;
        }

        /** @var list<list<string>> $rows */
        $rows = [];
        $lineNum = 0;

        while (($line = fgets($handle)) !== false) {
            if ($lineNum++ == 0 && $ignoreFirst) {
                continue;
            }

            $line = rtrim($line, "\r\n");
            if (empty(trim($line))) {
                continue;
            }

            /** @var list<string> $parsedLine */
            $parsedLine = explode($delimiter, $line);

            $txtIdx = $fields["txt"] - 1;
            if (!isset($parsedLine[$txtIdx])) {
                continue;
            }

            $wotext = $parsedLine[$txtIdx];

            /** @var list<string> $row */
            $row = [];
            // Fill WoText and WoTextLC
            if ($removeSpaces) {
                $row[] = str_replace(" ", "", $wotext);
                $row[] = mb_strtolower(str_replace(" ", "", $wotext));
            } else {
                $row[] = $wotext;
                $row[] = mb_strtolower($wotext);
            }

            $trIdx = $fields["tr"] - 1;
            $roIdx = $fields["ro"] - 1;
            $seIdx = $fields["se"] - 1;
            $tlIdx = $fields["tl"] - 1;

            if ($fields["tr"] != 0 && isset($parsedLine[$trIdx])) {
                $row[] = $parsedLine[$trIdx];
            }
            if ($fields["ro"] != 0 && isset($parsedLine[$roIdx])) {
                $row[] = $parsedLine[$roIdx];
            }
            if ($fields["se"] != 0 && isset($parsedLine[$seIdx])) {
                $row[] = $parsedLine[$seIdx];
            }
            if ($fields["tl"] != 0 && isset($parsedLine[$tlIdx])) {
                $row[] = str_replace(" ", ",", $parsedLine[$tlIdx]);
            }

            $rows[] = $row;

            // Execute batch when we reach the batch size
            if (count($rows) >= self::BATCH_SIZE) {
                $this->executeTempTableBatch($rows, $fields);
                $rows = [];
            }
        }

        fclose($handle);

        // Execute remaining rows
        if (!empty($rows)) {
            $this->executeTempTableBatch($rows, $fields);
        }
    }

    /**
     * Execute a batch insert for temp table import.
     *
     * @param list<list<string>> $rows   Array of row data
     * @param array{txt: int, tr: int, ro: int, se: int, tl: int} $fields Field indexes
     *
     * @return void
     */
    private function executeTempTableBatch(array $rows, array $fields): void
    {
        if (empty($rows)) {
            return;
        }

        // Build placeholder string for one row
        $rowPlaceholders = '(?, ?';  // WoText, WoTextLC
        if ($fields["tr"] != 0) {
            $rowPlaceholders .= ', ?';
        }
        if ($fields["ro"] != 0) {
            $rowPlaceholders .= ', ?';
        }
        if ($fields["se"] != 0) {
            $rowPlaceholders .= ', ?';
        }
        if ($fields["tl"] != 0) {
            $rowPlaceholders .= ', ?';
        }
        $rowPlaceholders .= ')';

        $placeholders = array_fill(0, count($rows), $rowPlaceholders);
        /** @var list<string> $params */
        $params = [];
        foreach ($rows as $row) {
            foreach ($row as $value) {
                $params[] = $value;
            }
        }

        $sql = "INSERT INTO temp_words(
                WoText, WoTextLC" .
                ($fields["tr"] != 0 ? ', WoTranslation' : '') .
                ($fields["ro"] != 0 ? ', WoRomanization' : '') .
                ($fields["se"] != 0 ? ', WoSentence' : '') .
                ($fields["tl"] != 0 ? ", WoTaglist" : "") .
            ")
            VALUES " . implode(',', $placeholders);

        Connection::preparedExecute($sql, $params);
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
            "CREATE TEMPORARY TABLE IF NOT EXISTS merge_words(
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
        $woTrRepl = 'words.WoTranslation';
        $replaceParams = [];
        for ($i = 1; $i < $seplen; $i++) {
            $woTrRepl = 'REPLACE(' . $woTrRepl . ', ?, ?)';
            $replaceParams[] = $wosep[$i];
            $replaceParams[] = $wosep[0];
        }

        // Insert existing translations
        $params = array_merge(
            $replaceParams,
            [$wosep[0], $wosep[0], $langId, $wosep[0]]
        );

        $bindings = $params;
        $sql = "INSERT IGNORE INTO merge_words(MText,MTranslation)
            SELECT b.WoTextLC,
            trim(
                SUBSTRING_INDEX(
                    SUBSTRING_INDEX(b.WoTranslation, ?, numbers.n),
                    ?, -1
                )
            ) name
            FROM numbers
            INNER JOIN (
                SELECT words.WoTextLC as WoTextLC, $woTrRepl as WoTranslation
                FROM temp_words
                LEFT JOIN words
                ON words.WoTextLC = temp_words.WoTextLC
                    AND words.WoTranslation != '*'
                    AND words.WoLgID = ?
            ) b
            ON CHAR_LENGTH(b.WoTranslation)-CHAR_LENGTH(REPLACE(b.WoTranslation, ?, ''))>= numbers.n-1
            ORDER BY b.WoTextLC, n"
            . UserScopedQuery::forTablePrepared('words', $bindings);

        $stmt = Connection::prepare($sql);
        $stmt->bindValues($params);
        $stmt->execute();

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
        $woTrRepl = 'temp_words.WoTranslation';
        $replaceParams2 = [];
        for ($i = 1; $i < $seplen; $i++) {
            $woTrRepl = 'REPLACE(' . $woTrRepl . ', ?, ?)';
            $replaceParams2[] = $tesep[$i];
            $replaceParams2[] = $tesep[0];
        }

        // Insert new translations
        $params2 = array_merge(
            $replaceParams2,
            [$tesep[0], $tesep[0], $tesep[0]]
        );

        $stmt = Connection::prepare(
            "INSERT IGNORE INTO merge_words(MText,MTranslation)
            SELECT temp_words.WoTextLC,
            trim(
                SUBSTRING_INDEX(
                    SUBSTRING_INDEX($woTrRepl, ?,
                        numbers.n
                    ), ?, -1
                )
            ) name
            FROM numbers
            INNER JOIN temp_words
            ON CHAR_LENGTH(temp_words.WoTranslation)-CHAR_LENGTH(REPLACE($woTrRepl, ?, ''))>= numbers.n-1
            ORDER BY temp_words.WoTextLC, n"
        );
        $stmt->bindValues($params2);
        $stmt->execute();

        // Determine separator for output
        if ($wosep[0] == ',' || $wosep[0] == ';') {
            $wosep = $wosep[0] . ' ';
        } else {
            $wosep = ' ' . $wosep[0] . ' ';
        }

        // Update temp_words with merged translations
        Connection::preparedExecute(
            "UPDATE temp_words
            LEFT JOIN (
                SELECT MText, GROUP_CONCAT(trim(MTranslation)
                    ORDER BY MID
                    SEPARATOR ?
                ) AS Translation
                FROM merge_words
                GROUP BY MText
            ) A
            ON MText=WoTextLC
            SET WoTranslation = Translation",
            [$wosep]
        );

        Connection::execute("DROP TABLE merge_words");
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
                " INTO words (
                    WoTextLC, WoText, WoTranslation, WoRomanization, WoSentence,
                    WoStatus, WoStatusChanged, WoLgID,
                    " . TermStatusService::makeScoreRandomInsertUpdate('iv') . "
                )
                SELECT *, $langId as LgID, " . TermStatusService::makeScoreRandomInsertUpdate('id') . "
                FROM (
                    SELECT WoTextLC, WoText, WoTranslation, WoRomanization,
                    WoSentence, $status AS WoStatus,
                    NOW() AS WoStatusChanged
                    FROM temp_words
                ) AS tw";

            if ($overwrite == 1 || $overwrite == 4) {
                $sql .= " ON DUPLICATE KEY UPDATE " .
                    ($fields["tr"] ? "words.WoTranslation = tw.WoTranslation, " : "") .
                    ($fields["ro"] ? "words.WoRomanization = tw.WoRomanization, " : '') .
                    ($fields["se"] ? "words.WoSentence = tw.WoSentence, " : '') .
                    "words.WoStatus = tw.WoStatus,
                    words.WoStatusChanged = tw.WoStatusChanged";
            }

            if ($overwrite == 2) {
                $sql .= " ON DUPLICATE KEY UPDATE
                    words.WoTranslation = CASE
                        WHEN words.WoTranslation = \"*\" THEN tw.WoTranslation
                        ELSE words.WoTranslation
                    END,
                    words.WoRomanization = CASE
                        WHEN words.WoRomanization IS NULL THEN tw.WoRomanization
                        ELSE words.WoRomanization
                    END,
                    words.WoSentence = CASE
                        WHEN words.WoSentence IS NULL THEN tw.WoSentence
                        ELSE words.WoSentence
                    END,
                    words.WoStatusChanged = CASE
                        WHEN words.WoSentence IS NULL OR words.WoRomanization IS NULL OR words.WoTranslation = \"*\"
                        THEN tw.WoStatusChanged
                        ELSE words.WoStatusChanged
                    END";
            }
        } else {
            // Overwrite modes 3 and 5: only update existing, don't insert new
            $bindings = [];
            $sql = "UPDATE words AS a
                JOIN temp_words AS b
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
                    WHEN (b.WoTranslation = '' OR b.WoTranslation = '*')
                        AND (b.WoRomanization IS NULL OR b.WoRomanization = '')
                        AND (b.WoSentence IS NULL OR b.WoSentence = '')
                    THEN a.WoStatusChanged
                    ELSE NOW()
                END"
                . UserScopedQuery::forTablePrepared('words', $bindings, 'a');
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
            "INSERT IGNORE INTO tags (TgText)
            SELECT name FROM (
                SELECT temp_words.WoTextLC,
                SUBSTRING_INDEX(
                    SUBSTRING_INDEX(
                        temp_words.WoTaglist, ',',
                        numbers.n
                    ), ',', -1) name
                FROM numbers
                INNER JOIN temp_words
                ON CHAR_LENGTH(temp_words.WoTaglist)-CHAR_LENGTH(REPLACE(temp_words.WoTaglist, ',', ''))>= numbers.n-1
                ORDER BY WoTextLC, n) A"
        );

        // Link words to tags
        $bindings = [$langId];
        $sql = "INSERT IGNORE INTO word_tag_map
            SELECT WoID, TgID
            FROM (
                SELECT temp_words.WoTextLC, SUBSTRING_INDEX(
                    SUBSTRING_INDEX(
                        temp_words.WoTaglist, ',', numbers.n
                    ), ',', -1) name
                FROM numbers
                INNER JOIN temp_words
                ON CHAR_LENGTH(temp_words.WoTaglist)-CHAR_LENGTH(REPLACE(temp_words.WoTaglist, ',', ''))>= numbers.n-1
                ORDER BY WoTextLC, n
            ) A, tags, words
            WHERE name=TgText AND A.WoTextLC=words.WoTextLC AND WoLgID=?"
            . UserScopedQuery::forTablePrepared('words', $bindings);

        Connection::preparedExecute($sql, [$langId]);

        TagsFacade::getAllTermTags(true);
    }

    /**
     * Cleanup temporary tables.
     *
     * @return void
     */
    private function cleanupTempTables(): void
    {
        Connection::execute("DROP TABLE IF EXISTS numbers");
        QueryBuilder::table('temp_words')->truncate();
    }

    /**
     * Import tags only (no terms).
     *
     * @param array{tl: int} $fields      Field indexes
     * @param string         $tabType     Tab type (c, t, h)
     * @param string         $fileName    Path to input file
     * @param bool           $ignoreFirst Ignore first line
     *
     * @return void
     */
    public function importTagsOnly(array $fields, string $tabType, string $fileName, bool $ignoreFirst): void
    {
        $columns = '';
        $tlField = $fields["tl"];
        for ($j = 1; $j <= $tlField; $j++) {
            $columns .= ($j == 1 ? '(' : ',') . ($j == $fields["tl"] ? '@taglist' : '@dummy');
        }
        $columns .= ')';

        $delimiter = ' ' . $this->getSqlDelimiter($tabType);

        if ($this->isLocalInfileEnabled()) {
            $sql = "LOAD DATA LOCAL INFILE ?
                IGNORE INTO TABLE temp_words
                FIELDS TERMINATED BY '$delimiter' ENCLOSED BY '\"' LINES TERMINATED BY '\\n' " .
                ($ignoreFirst ? "IGNORE 1 LINES " : "") .
                "$columns
                SET WoTextLC = REPLACE(@taglist, ' ', ',')";
            $stmt = Connection::prepare($sql);
            $stmt->bind('s', $fileName);
            $stmt->execute();
        } else {
            $handle = fopen($fileName, 'r');
            if ($handle === false) {
                return;
            }
            $fileSize = filesize($fileName);
            if ($fileSize === false || $fileSize === 0) {
                fclose($handle);
                return;
            }
            $dataText = fread($handle, $fileSize);
            fclose($handle);
            if ($dataText === false) {
                return;
            }

            $params = [];
            $placeholders = [];
            $i = 0;
            $realDelimiter = $this->getDelimiter($tabType);
            if ($realDelimiter === '') {
                return;
            }

            foreach (explode(PHP_EOL, $dataText) as $line) {
                if ($i++ == 0 && $ignoreFirst) {
                    continue;
                }

                if (empty(trim($line))) {
                    continue;
                }

                /** @var list<string> $parts */
                $parts = explode($realDelimiter, $line);
                $tlIdx = $tlField - 1;
                if (!isset($parts[$tlIdx])) {
                    continue;
                }

                $tags = $parts[$tlIdx];
                $tags = str_replace(' ', ',', $tags);
                $params[] = $tags;
                $placeholders[] = "(?)";
            }

            if (!empty($placeholders)) {
                $sql = "INSERT INTO temp_words(WoTextLC)
                    VALUES " . implode(',', $placeholders);
                Connection::preparedExecute($sql, $params);
            }
        }

        // Create numbers table and insert tags
        Connection::execute(
            "CREATE TEMPORARY TABLE IF NOT EXISTS numbers(
                n tinyint(3) unsigned NOT NULL
            )"
        );
        Connection::execute("INSERT IGNORE INTO numbers(n) VALUES ('1'),('2'),('3'),
            ('4'),('5'),('6'),('7'),('8'),('9')");

        Connection::execute("INSERT IGNORE INTO tags (TgText)
            SELECT NAME FROM (
                SELECT SUBSTRING_INDEX(
                    SUBSTRING_INDEX(
                        temp_words.WoTextLC, ',', numbers.n
                    ), ',', -1) name
                FROM numbers
                INNER JOIN temp_words
                ON CHAR_LENGTH(temp_words.WoTextLC)-CHAR_LENGTH(REPLACE(temp_words.WoTextLC, ',', ''))>= numbers.n-1
                ORDER BY WoTextLC, n) A");

        $this->cleanupTempTables();
        TagsFacade::getAllTermTags(true);
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
        $mwords = QueryBuilder::table('words')
            ->where('WoWordCount', '>', 1)
            ->where('WoCreated', '>', $lastUpdate)
            ->countPrepared();

        if ($mwords > 40) {
            // Bulk update: delete and recreate all text items
            QueryBuilder::table('sentences')
                ->where('SeLgID', '=', $langId)
                ->delete();
            QueryBuilder::table('word_occurrences')
                ->where('Ti2LgID', '=', $langId)
                ->delete();
            Maintenance::adjustAutoIncrement('sentences', 'SeID');

            $rows = QueryBuilder::table('texts')
                ->select(['TxID', 'TxText'])
                ->where('TxLgID', '=', $langId)
                ->orderBy('TxID')
                ->getPrepared();
            foreach ($rows as $record) {
                $txtid = (int) $record["TxID"];
                $txttxt = (string) $record["TxText"];
                TextParsing::parseAndSave($txttxt, $langId, $txtid);
            }
        } elseif ($mwords > 0) {
            // Update individual multi-word expressions
            $allPlaceholders = [];
            $allParams = [];
            $rows = QueryBuilder::table('words')
                ->select(['WoID', 'WoTextLC', 'WoWordCount'])
                ->where('WoWordCount', '>', 1)
                ->where('WoCreated', '>', $lastUpdate)
                ->getPrepared();
            foreach ($rows as $record) {
                $len = (int) $record['WoWordCount'];
                $wid = (int) $record['WoID'];
                $textlc = (string) $record['WoTextLC'];
                $expressionService = new ExpressionService();
                $result = $expressionService->insertExpressions($textlc, $langId, $wid, $len, 2);
                if ($result !== null) {
                    $allPlaceholders = array_merge($allPlaceholders, $result['placeholders']);
                    $allParams = array_merge($allParams, $result['params']);
                }
            }

            if (!empty($allPlaceholders)) {
                $sql = "INSERT INTO word_occurrences (
                    Ti2WoID, Ti2LgID, Ti2TxID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text
                ) VALUES " . implode(',', $allPlaceholders);
                Connection::preparedExecute($sql, $allParams);
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
        $result = QueryBuilder::table('words')
            ->select(['MAX(WoStatusChanged) AS max_date'])
            ->first();
        return $result !== null ? (string)$result['max_date'] : null;
    }

    /**
     * Link imported words to text items.
     *
     * @return void
     */
    public function linkWordsToTextItems(): void
    {
        $bindings = [];
        $sql = "UPDATE words
            JOIN word_occurrences
            ON WoWordCount=1 AND Ti2WoID IS NULL AND lower(Ti2Text)=WoTextLC AND Ti2LgID = WoLgID
            SET Ti2WoID=WoID"
            . UserScopedQuery::forTablePrepared('words', $bindings);
        Connection::execute($sql);
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
        return QueryBuilder::table('words')
            ->where('WoStatusChanged', '>', $lastUpdate)
            ->countPrepared();
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
        $bindings = [$lastUpdate, $offset, $limit];
        $sql = "SELECT w.WoID, w.WoText, w.WoTextLC, w.WoTranslation,
                w.WoRomanization, w.WoSentence, w.WoStatus,
                GROUP_CONCAT(t.TgText ORDER BY t.TgText SEPARATOR ', ') as taglist,
                CASE WHEN w.WoSentence != '' AND w.WoSentence LIKE CONCAT('%{', w.WoText, '}%')
                    THEN 1 ELSE 0 END as SentOK
            FROM words w
            LEFT JOIN word_tag_map wt ON w.WoID = wt.WtWoID
            LEFT JOIN tags t ON wt.WtTgID = t.TgID
            WHERE w.WoStatusChanged > ?
            GROUP BY w.WoID
            ORDER BY w.WoText
            LIMIT ?, ?"
            . UserScopedQuery::forTablePrepared('words', $bindings, 'w');

        return Connection::preparedFetchAll($sql, [$lastUpdate, $offset, $limit]);
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
        return (bool) QueryBuilder::table('languages')
            ->where('LgID', '=', $langId)
            ->valuePrepared('LgRightToLeft');
    }
}
