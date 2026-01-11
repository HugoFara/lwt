<?php

/**
 * \file
 * \brief Database maintenance and optimization utilities.
 *
 * PHP version 8.1
 *
 * @category Database
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-database-maintenance.html
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Shared\Infrastructure\Database;

use Lwt\Core\Globals;
use Lwt\Modules\Language\Application\Services\TextParsingService;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;

/**
 * Database maintenance and optimization utilities.
 *
 * Provides methods for optimizing database tables, adjusting auto-increment
 * values, and initializing word counts.
 *
 * @since 3.0.0
 */
class Maintenance
{
    /**
     * Adjust the auto-incrementation in the database.
     *
     * @param string $table Table name (without prefix)
     * @param string $key   Primary key column name
     *
     * @return void
     */
    public static function adjustAutoIncrement(string $table, string $key): void
    {
        $row = QueryBuilder::table($table)
            ->selectRaw('MAX(' . $key . ')+1 AS next_id')
            ->first();
        $val = $row['next_id'] ?? null;
        if (!isset($val)) {
            $val = 1;
        }
        // ALTER TABLE is DDL - use raw SQL with fixed table name
        $sql = 'ALTER TABLE ' . $table . ' AUTO_INCREMENT = ' . $val;
        Connection::query($sql);
    }

    /**
     * Optimize the database.
     *
     * @return void
     */
    public static function optimizeDatabase(): void
    {
        self::adjustAutoIncrement('languages', 'LgID');
        self::adjustAutoIncrement('sentences', 'SeID');
        self::adjustAutoIncrement('texts', 'TxID');
        self::adjustAutoIncrement('words', 'WoID');
        self::adjustAutoIncrement('tags', 'TgID');
        self::adjustAutoIncrement('text_tags', 'T2ID');
        self::adjustAutoIncrement('news_feeds', 'NfID');
        self::adjustAutoIncrement('feed_links', 'FlID');
        // SHOW TABLE STATUS queries physical table names, not logical table names
        // In the new system, tables don't have prefixes - they're just "words", "texts", etc.
        $sql =
        'SHOW TABLE STATUS
        WHERE Engine IN ("MyISAM","Aria") AND (
            (Data_free / Data_length > 0.1 AND Data_free > 102400) OR Data_free > 1048576
        ) AND Name NOT LIKE "\\_%"';
        $rows = Connection::fetchAll($sql);
        foreach ($rows as $row) {
            Connection::execute('OPTIMIZE TABLE ' . (string)$row['Name']);
        }
    }

    /**
     * Update the word count for Japanese language (using MeCab only).
     *
     * @param int $japid Japanese language ID
     *
     * @return void
     */
    public static function updateJapaneseWordCount(int $japid): void
    {
        $rows = QueryBuilder::table('words')
            ->select(['WoID', 'WoTextLC'])
            ->where('WoLgID', '=', $japid)
            ->where('WoWordCount', '=', 0)
            ->getPrepared();
        if (empty($rows)) {
            return;
        }

        // STEP 1: write the useful info to a file
        $db_to_mecab = tempnam(sys_get_temp_dir(), "db_to_mecab");
        if ($db_to_mecab === false) {
            throw new \RuntimeException('Failed to create temporary file for MeCab processing');
        }

        try {
            $mecab_args = ' -F %m%t\\t -U %m%t\\t -E \\n ';
            $mecab = (new TextParsingService())->getMecabPath($mecab_args);

            $fp = fopen($db_to_mecab, 'w');
            foreach ($rows as $record) {
                fwrite($fp, (string) $record['WoID'] . "\t" . (string) $record['WoTextLC'] . "\n");
            }
            fclose($fp);

            // STEP 2: process the data with MeCab and refine the output
            $handle = popen($mecab . escapeshellarg($db_to_mecab), "r");
            if (feof($handle)) {
                pclose($handle);
                return;
            }
            $data = array();
            while (!feof($handle)) {
                $row = fgets($handle, 1024);
                $arr = explode("4\t", $row, 2);
                if (isset($arr[1]) && $arr[1] !== '') {
                    // NOTE: Test coverage requires MeCab installation - see tests/backend for integration tests
                    $cnt = substr_count(
                        preg_replace('$[^2678]\\t$u', '', $arr[1]),
                        "\t"
                    );
                    if (empty($cnt)) {
                        $cnt = 1;
                    }
                    $data[] = ['mid' => $arr[0], 'count' => $cnt];
                }
            }
            pclose($handle);
            if (empty($data)) {
                // Nothing to update, quit
                return;
            }


            // STEP 3: edit the database
            // Temporary tables are session-scoped, no prefix needed
            Connection::query(
                "CREATE TEMPORARY TABLE mecab (
                    MID mediumint(8) unsigned NOT NULL,
                    MWordCount tinyint(3) unsigned NOT NULL,
                    PRIMARY KEY (MID)
                ) CHARSET=utf8"
            );

            // Insert data using prepared statements
            $insertSql = "INSERT INTO mecab (MID, MWordCount) VALUES (?, ?)";
            foreach ($data as $entry) {
                Connection::preparedExecute($insertSql, [$entry['mid'], $entry['count']]);
            }

            // UPDATE with JOIN - use raw SQL with fixed table names
            Connection::query(
                "UPDATE words
                JOIN mecab ON MID = WoID
                SET WoWordCount = MWordCount"
            );
            Connection::execute("DROP TABLE mecab");
        } finally {
            if (file_exists($db_to_mecab)) {
                unlink($db_to_mecab);
            }
        }
    }

    /**
     * Initiate the number of words in terms for all languages.
     *
     * Only terms with a word count set to 0 are changed.
     *
     * @return void
     */
    public static function initWordCount(): void
    {
        $sqlarr = array();
        $i = 0;
        $min = 0;
        /**
         * @var array<string, mixed>|null $row ID for the Japanese language using MeCab
         */
        $row = QueryBuilder::table('languages')
            ->selectRaw('GROUP_CONCAT(LgID) AS lang_ids')
            ->whereRaw("UPPER(LgRegexpWordCharacters)='MECAB'")
            ->first();
        /** @var string|int|null $japid */
        $japid = $row !== null ? ($row['lang_ids'] ?? null) : null;

        if ($japid !== null && $japid !== '') {
            self::updateJapaneseWordCount((int)$japid);
        }
        $rows = QueryBuilder::table('words')
            ->select(['WoID', 'WoTextLC', 'LgRegexpWordCharacters', 'LgSplitEachChar'])
            ->join('languages', 'words.WoLgID', '=', 'languages.LgID')
            ->where('WoWordCount', '=', 0)
            ->orderBy('WoID')
            ->getPrepared();
        foreach ($rows as $rec) {
            $splitEachChar = (int) $rec['LgSplitEachChar'];
            $woTextLC = (string) $rec['WoTextLC'];
            $regexpWordChars = (string) $rec['LgRegexpWordCharacters'];
            $woID = (int) $rec['WoID'];

            if ($splitEachChar === 1) {
                $textlc = preg_replace('/([^\s])/u', "$1 ", $woTextLC);
            } else {
                $textlc = $woTextLC;
            }
            $wordCount = preg_match_all(
                '/([' . $regexpWordChars . ']+)/u',
                $textlc ?? '',
                $ma
            );
            // Ensure word count is at least 1 to avoid invalid SQL CASE statement
            if ($wordCount < 1) {
                $wordCount = 1;
            }
            $sqlarr[] = ' WHEN ' . $woID . ' THEN ' . $wordCount;
            if (++$i % 1000 == 0) {
                $max = $woID;
                $sqltext = "UPDATE words
                SET WoWordCount = CASE WoID" . implode(' ', $sqlarr) . "
                END
                WHERE WoWordCount=0 AND WoID BETWEEN $min AND $max";
                Connection::query($sqltext);
                $min = $max;
                $sqlarr = array();
            }
        }
        if (!empty($sqlarr)) {
            $sqltext = "UPDATE words
            SET WoWordCount = CASE WoID" . implode(' ', $sqlarr) . '
            END where WoWordCount=0';
            Connection::query($sqltext);
        }
    }
}
