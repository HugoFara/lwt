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

namespace Lwt\Database;

use Lwt\Core\Globals;

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
        $tbpref = Globals::getTablePrefix();
        $val = get_first_value(
            'SELECT max(' . $key . ')+1 AS value FROM ' . $tbpref . $table
        );
        if (!isset($val)) {
            $val = 1;
        }
        $sql = 'ALTER TABLE ' . $tbpref . $table . ' AUTO_INCREMENT = ' . $val;
        do_mysqli_query($sql);
    }

    /**
     * Optimize the database.
     *
     * @return void
     */
    public static function optimizeDatabase(): void
    {
        $tbpref = Globals::getTablePrefix();
        self::adjustAutoIncrement('archivedtexts', 'AtID');
        self::adjustAutoIncrement('languages', 'LgID');
        self::adjustAutoIncrement('sentences', 'SeID');
        self::adjustAutoIncrement('texts', 'TxID');
        self::adjustAutoIncrement('words', 'WoID');
        self::adjustAutoIncrement('tags', 'TgID');
        self::adjustAutoIncrement('tags2', 'T2ID');
        self::adjustAutoIncrement('newsfeeds', 'NfID');
        self::adjustAutoIncrement('feedlinks', 'FlID');
        $sql =
        'SHOW TABLE STATUS
        WHERE Engine IN ("MyISAM","Aria") AND (
            (Data_free / Data_length > 0.1 AND Data_free > 102400) OR Data_free > 1048576
        ) AND Name';
        if (empty($tbpref)) {
            $sql .= " NOT LIKE '\\_%'";
        } else {
            $sql .= " LIKE " . Escaping::toSqlSyntax(rtrim($tbpref, '_')) . "'\\_%" . "'";
        }
        $res = do_mysqli_query($sql);
        if ($res === false || $res === true) {
            return;
        }
        while ($row = mysqli_fetch_assoc($res)) {
            runsql('OPTIMIZE TABLE ' . $row['Name'], '');
        }
        mysqli_free_result($res);
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
        $tbpref = Globals::getTablePrefix();

        // STEP 1: write the useful info to a file
        $db_to_mecab = tempnam(sys_get_temp_dir(), "{$tbpref}db_to_mecab");
        $mecab_args = ' -F %m%t\\t -U %m%t\\t -E \\n ';
        $mecab = get_mecab_path($mecab_args);

        $sql = "SELECT WoID, WoTextLC FROM {$tbpref}words
        WHERE WoLgID = $japid AND WoWordCount = 0";
        $res = do_mysqli_query($sql);
        if ($res === false || $res === true) {
            return;
        }
        $fp = fopen($db_to_mecab, 'w');
        while ($record = mysqli_fetch_assoc($res)) {
            fwrite($fp, $record['WoID'] . "\t" . $record['WoTextLC'] . "\n");
        }
        mysqli_free_result($res);
        fclose($fp);

        // STEP 2: process the data with MeCab and refine the output
        $handle = popen($mecab . $db_to_mecab, "r");
        if (feof($handle)) {
            pclose($handle);
            unlink($db_to_mecab);
            return;
        }
        $sql = "INSERT INTO {$tbpref}mecab (MID, MWordCount) values";
        $values = array();
        while (!feof($handle)) {
            $row = fgets($handle, 1024);
            $arr = explode("4\t", $row, 2);
            if (isset($arr[1]) && $arr[1] !== '') {
                //TODO Add tests
                $cnt = substr_count(
                    preg_replace('$[^2678]\\t$u', '', $arr[1]),
                    "\t"
                );
                if (empty($cnt)) {
                    $cnt = 1;
                }
                $values[] = "(" . Escaping::toSqlSyntax($arr[0]) . ", $cnt)";
            }
        }
        pclose($handle);
        if (empty($values)) {
            // Nothing to update, quit
            return;
        }
        $sql .= join(",", $values);


        // STEP 3: edit the database
        do_mysqli_query(
            "CREATE TEMPORARY TABLE {$tbpref}mecab (
                MID mediumint(8) unsigned NOT NULL,
                MWordCount tinyint(3) unsigned NOT NULL,
                PRIMARY KEY (MID)
            ) CHARSET=utf8"
        );

        do_mysqli_query($sql);
        do_mysqli_query(
            "UPDATE {$tbpref}words
            JOIN {$tbpref}mecab ON MID = WoID
            SET WoWordCount = MWordCount"
        );
        do_mysqli_query("DROP TABLE {$tbpref}mecab");

        unlink($db_to_mecab);
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
        $tbpref = Globals::getTablePrefix();
        $sqlarr = array();
        $i = 0;
        $min = 0;
        /**
         * @var string|null ID for the Japanese language using MeCab
         */
        $japid = get_first_value(
            "SELECT group_concat(LgID) value
            FROM {$tbpref}languages
            WHERE UPPER(LgRegexpWordCharacters)='MECAB'"
        );

        if ($japid !== null && $japid !== '') {
            self::updateJapaneseWordCount((int)$japid);
        }
        $sql = "SELECT WoID, WoTextLC, LgRegexpWordCharacters, LgSplitEachChar
        FROM {$tbpref}words, {$tbpref}languages
        WHERE WoWordCount = 0 AND WoLgID = LgID
        ORDER BY WoID";
        $result = do_mysqli_query($sql);
        if ($result === false || $result === true) {
            return;
        }
        while (($rec = mysqli_fetch_assoc($result)) !== false) {
            if ((int)$rec['LgSplitEachChar'] === 1) {
                $textlc = preg_replace('/([^\s])/u', "$1 ", $rec['WoTextLC']);
            } else {
                $textlc = $rec['WoTextLC'];
            }
            $wordCount = preg_match_all(
                '/([' . $rec['LgRegexpWordCharacters'] . ']+)/u',
                $textlc,
                $ma
            );
            // Ensure word count is at least 1 to avoid invalid SQL CASE statement
            if ($wordCount < 1) {
                $wordCount = 1;
            }
            $sqlarr[] = ' WHEN ' . $rec['WoID'] . ' THEN ' . $wordCount;
            if (++$i % 1000 == 0) {
                $max = $rec['WoID'];
                $sqltext = "UPDATE  {$tbpref}words
                SET WoWordCount = CASE WoID" . implode(' ', $sqlarr) . "
                END
                WHERE WoWordCount=0 AND WoID BETWEEN $min AND $max";
                do_mysqli_query($sqltext);
                $min = $max;
                $sqlarr = array();
            }
        }
        mysqli_free_result($result);
        if (!empty($sqlarr)) {
            $sqltext = "UPDATE {$tbpref}words
            SET WoWordCount = CASE WoID" . implode(' ', $sqlarr) . '
            END where WoWordCount=0';
            do_mysqli_query($sqltext);
        }
    }
}
