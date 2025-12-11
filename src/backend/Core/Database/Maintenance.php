<?php declare(strict_types=1);
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
        $val = Connection::fetchValue(
            'SELECT max(' . $key . ')+1 AS value FROM ' . Globals::getTablePrefix() . $table
        );
        if (!isset($val)) {
            $val = 1;
        }
        $sql = 'ALTER TABLE ' . Globals::getTablePrefix() . $table . ' AUTO_INCREMENT = ' . $val;
        Connection::query($sql);
    }

    /**
     * Optimize the database.
     *
     * @return void
     */
    public static function optimizeDatabase(): void
    {
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
        if (empty(Globals::getTablePrefix())) {
            $sql .= " NOT LIKE '\\_%'";
            $rows = Connection::fetchAll($sql);
        } else {
            $sql .= " LIKE CONCAT(?, '\\_', '%')";
            $rows = Connection::preparedFetchAll($sql, [rtrim(Globals::getTablePrefix(), '_')]);
        }
        foreach ($rows as $row) {
            Connection::execute('OPTIMIZE TABLE ' . $row['Name']);
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
        // STEP 1: write the useful info to a file
        $db_to_mecab = tempnam(sys_get_temp_dir(), Globals::getTablePrefix() . "db_to_mecab");
        $mecab_args = ' -F %m%t\\t -U %m%t\\t -E \\n ';
        $mecab = (new \Lwt\Services\TextParsingService())->getMecabPath($mecab_args);

        $sql = "SELECT WoID, WoTextLC FROM " . Globals::getTablePrefix() . "words
        WHERE WoLgID = ? AND WoWordCount = 0";
        $rows = Connection::preparedFetchAll($sql, [$japid]);
        if (empty($rows)) {
            return;
        }
        $fp = fopen($db_to_mecab, 'w');
        foreach ($rows as $record) {
            fwrite($fp, $record['WoID'] . "\t" . $record['WoTextLC'] . "\n");
        }
        fclose($fp);

        // STEP 2: process the data with MeCab and refine the output
        $handle = popen($mecab . $db_to_mecab, "r");
        if (feof($handle)) {
            pclose($handle);
            unlink($db_to_mecab);
            return;
        }
        $data = array();
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
                $data[] = ['mid' => $arr[0], 'count' => $cnt];
            }
        }
        pclose($handle);
        if (empty($data)) {
            // Nothing to update, quit
            return;
        }


        // STEP 3: edit the database
        Connection::query(
            "CREATE TEMPORARY TABLE " . Globals::getTablePrefix() . "mecab (
                MID mediumint(8) unsigned NOT NULL,
                MWordCount tinyint(3) unsigned NOT NULL,
                PRIMARY KEY (MID)
            ) CHARSET=utf8"
        );

        // Insert data using prepared statements
        $insertSql = "INSERT INTO " . Globals::getTablePrefix() . "mecab (MID, MWordCount) VALUES (?, ?)";
        foreach ($data as $entry) {
            Connection::preparedExecute($insertSql, [$entry['mid'], $entry['count']]);
        }

        Connection::query(
            "UPDATE " . Globals::getTablePrefix() . "words
            JOIN " . Globals::getTablePrefix() . "mecab ON MID = WoID
            SET WoWordCount = MWordCount"
        );
        Connection::execute("DROP TABLE " . Globals::getTablePrefix() . "mecab");

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
        $sqlarr = array();
        $i = 0;
        $min = 0;
        /**
         * @var string|null ID for the Japanese language using MeCab
         */
        $japid = Connection::fetchValue(
            "SELECT group_concat(LgID) value
            FROM " . Globals::getTablePrefix() . "languages
            WHERE UPPER(LgRegexpWordCharacters)='MECAB'"
        );

        if ($japid !== null && $japid !== '') {
            self::updateJapaneseWordCount((int)$japid);
        }
        $sql = "SELECT WoID, WoTextLC, LgRegexpWordCharacters, LgSplitEachChar
        FROM " . Globals::getTablePrefix() . "words, " . Globals::getTablePrefix() . "languages
        WHERE WoWordCount = 0 AND WoLgID = LgID
        ORDER BY WoID";
        $rows = Connection::fetchAll($sql);
        foreach ($rows as $rec) {
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
                $sqltext = "UPDATE  " . Globals::getTablePrefix() . "words
                SET WoWordCount = CASE WoID" . implode(' ', $sqlarr) . "
                END
                WHERE WoWordCount=0 AND WoID BETWEEN $min AND $max";
                Connection::query($sqltext);
                $min = $max;
                $sqlarr = array();
            }
        }
        if (!empty($sqlarr)) {
            $sqltext = "UPDATE " . Globals::getTablePrefix() . "words
            SET WoWordCount = CASE WoID" . implode(' ', $sqlarr) . '
            END where WoWordCount=0';
            Connection::query($sqltext);
        }
    }
}
