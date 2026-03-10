<?php

/**
 * \file
 * \brief Shared database operations for text parsing.
 *
 * PHP version 8.1
 *
 * @category Database
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Shared\Infrastructure\Database;

use Lwt\Shared\Infrastructure\Globals;

/**
 * Shared database operations for text parsing.
 *
 * Handles SQL-based text saving, sentence/text-item registration,
 * statistics display, and multi-word expression checking.
 *
 * @since 3.0.0
 */
class TextParsingPersistence
{
    /**
     * Insert a processed text in the data in pure SQL way.
     *
     * @param string $text Preprocessed text to insert
     * @param int    $id   Text ID
     *
     * @return void
     */
    public static function saveWithSql(string $text, int $id): void
    {
        $file_name = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "tmpti.txt";
        $fp = fopen($file_name, 'w');
        if ($fp !== false) {
            fwrite($fp, $text);
            fclose($fp);
        }
        Connection::query("SET @order=0, @sid=1, @count = 0;");
        if ($id > 0) {
            // Get next auto-increment value for accurate TiSeID calculation
            $dbname = Globals::getDatabaseName();
            $sentencesTable = Globals::table('sentences');
            $autoInc = (int)Connection::preparedFetchValue(
                "SELECT AUTO_INCREMENT as value FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?",
                [$dbname, $sentencesTable]
            );
            // Fall back to MAX+1 if AUTO_INCREMENT is not available
            if ($autoInc <= 0) {
                $autoInc = (int)Connection::fetchValue(
                    "SELECT IFNULL(MAX(`SeID`)+1,1) as value FROM sentences"
                    . UserScopedQuery::forTable('sentences')
                );
            }
            Connection::query("SET @sid = $autoInc;");
        }
        // LOAD DATA LOCAL INFILE does not support prepared statements for file path
        // We need to use Connection::query() here, but we escape the file path manually
        $connection = Globals::getDbConnection();
        if ($connection === null) {
            throw new \RuntimeException('Database connection not available');
        }
        $escaped_file_name = (string) mysqli_real_escape_string($connection, $file_name);
        $sql = "LOAD DATA LOCAL INFILE '$escaped_file_name'
        INTO TABLE temp_word_occurrences
        FIELDS TERMINATED BY '\\t' LINES TERMINATED BY '\\n' (@word_count, @term)
        SET
            TiSeID = @sid,
            TiCount = (@count:=@count+CHAR_LENGTH(@term))+1-CHAR_LENGTH(@term),
            TiOrder = IF(
                @term LIKE '%\\r',
                CASE
                    WHEN (@term:=REPLACE(@term,'\\r','')) IS NULL THEN NULL
                    WHEN (@sid:=@sid+1) IS NULL THEN NULL
                    WHEN @count:= 0 IS NULL THEN NULL
                    ELSE @order := @order+1
                END,
                @order := @order+1
            ),
            TiText = @term,
            TiWordCount = @word_count";

        // Try LOAD DATA LOCAL INFILE, fall back to INSERT if it fails
        try {
            Connection::query($sql);
        } catch (\RuntimeException $e) {
            // If LOAD DATA LOCAL INFILE is disabled, use fallback method
            if (strpos($e->getMessage(), 'LOAD DATA LOCAL INFILE is forbidden') !== false) {
                self::saveWithSqlFallback($text, $id);
            } else {
                throw $e;
            }
        }
        unlink($file_name);
    }

    /**
     * Fallback method to insert text data when LOAD DATA LOCAL INFILE is disabled.
     *
     * @param string $text Preprocessed text to insert
     * @param int    $id   Text ID
     *
     * @return void
     */
    public static function saveWithSqlFallback(string $text, int $id): void
    {
        // Get starting sentence ID
        $sid = 1;
        if ($id > 0) {
            // Get next auto-increment value for accurate TiSeID calculation
            $dbname = Globals::getDatabaseName();
            $sentencesTable = Globals::table('sentences');
            $sid = (int)Connection::preparedFetchValue(
                "SELECT AUTO_INCREMENT as value FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?",
                [$dbname, $sentencesTable]
            );
            // Fall back to MAX+1 if AUTO_INCREMENT is not available
            if ($sid <= 0) {
                $sid = (int)Connection::fetchValue(
                    "SELECT IFNULL(MAX(`SeID`)+1,1) as value FROM sentences"
                    . UserScopedQuery::forTable('sentences')
                );
            }
        }

        $lines = explode("\n", $text);
        $order = 0;
        $count = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = explode("\t", $line);
            if (count($parts) < 2) {
                continue;
            }

            $word_count = (int)$parts[0];
            $term = $parts[1];

            // Handle line breaks (increase sentence ID)
            if (substr($term, -1) === "\r") {
                $term = rtrim($term, "\r");
                $order++;
                $count = 0;
                $sid++;
            } else {
                $order++;
            }

            $current_count = $count;
            $count += strlen($term) + 1;

            Connection::preparedExecute(
                "INSERT INTO temp_word_occurrences
                (TiSeID, TiCount, TiOrder, TiText, TiWordCount)
                VALUES (?, ?, ?, ?, ?)",
                [$sid, $current_count, $order, $term, $word_count]
            );
        }
    }

    /**
     * Echo the sentences in a text. Prepare JS data for words and word count.
     *
     * @param int $lid Language ID
     *
     * @return void
     */
    public static function checkValid(int $lid): void
    {
        $wo = $nw = array();
        $sentences = Connection::fetchAll(
            'SELECT GROUP_CONCAT(TiText order by TiOrder SEPARATOR "")
            Sent FROM temp_word_occurrences group by TiSeID'
        );
        echo '<h4>Sentences</h4><ol>';
        foreach ($sentences as $record) {
            echo "<li>" . \htmlspecialchars((string) ($record['Sent'] ?? ''), ENT_QUOTES, 'UTF-8') . "</li>";
        }
        echo '</ol>';
        $bindings = [$lid];
        $rows = Connection::preparedFetchAll(
            "SELECT count(`TiOrder`) cnt, if(0=TiWordCount,0,1) as len,
            LOWER(TiText) as word, WoTranslation
            FROM temp_word_occurrences
            LEFT JOIN words ON lower(TiText)=WoTextLC AND WoLgID=?"
            . UserScopedQuery::forTablePrepared('words', $bindings, '')
            . " GROUP BY lower(TiText)",
            $bindings
        );
        foreach ($rows as $record) {
            if ($record['len'] == 1) {
                $wo[] = array(
                    \htmlspecialchars((string) ($record['word'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    $record['cnt'],
                    \htmlspecialchars((string) ($record['WoTranslation'] ?? ''), ENT_QUOTES, 'UTF-8')
                );
            } else {
                $nw[] = array(
                    htmlspecialchars((string)($record['word'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars((string)($record['cnt'] ?? ''), ENT_QUOTES, 'UTF-8')
                );
            }
        }
        // JavaScript moved to src/frontend/js/texts/text_check_display.ts
        echo '<script type="application/json" id="text-check-words-config">';
        echo json_encode(['words' => $wo, 'nonWords' => $nw]);
        echo '</script>';
    }

    /**
     * Append sentences and text items in the database.
     *
     * TiSeID in temp_word_occurrences is pre-computed to match future SeID values.
     * When parseStandardToDatabase runs with useMaxSeID=true, it sets TiSeID
     * to MAX(SeID)+1, MAX(SeID)+2, etc. When we insert sentences here, they
     * get those exact SeID values via auto-increment, so TiSeID = SeID.
     *
     * @param int  $tid          ID of text from which insert data
     * @param int  $lid          ID of the language of the text
     * @param bool $hasmultiword Set to true to insert multi-words as well.
     *
     * @return void
     */
    public static function registerSentencesTextItems(int $tid, int $lid, bool $hasmultiword): void
    {
        // STEP 1: Insert sentences FIRST to satisfy FK constraint.
        Connection::query('SET @i=0;');
        Connection::preparedExecute(
            "INSERT INTO sentences (
                SeLgID, SeTxID, SeOrder, SeFirstPos, SeText
            ) SELECT
            ?,
            ?,
            @i:=@i+1,
            MIN(IF(TiWordCount=0, TiOrder+1, TiOrder)),
            GROUP_CONCAT(TiText ORDER BY TiOrder SEPARATOR \"\")
            FROM temp_word_occurrences
            GROUP BY TiSeID
            ORDER BY TiSeID",
            [$lid, $tid]
        );

        // STEP 1.5: Align TiSeID with actual SeID values.
        // The pre-computed TiSeID may not match actual AUTO_INCREMENT values,
        // so we update temp_word_occurrences to use the actual SeID from inserted sentences.
        // ROW_NUMBER() maps TiSeID rank to SeOrder, which we JOIN to get SeID.
        Connection::preparedExecute(
            "UPDATE temp_word_occurrences t
            JOIN (
                SELECT TiSeID AS old_seid,
                       ROW_NUMBER() OVER (ORDER BY TiSeID) AS rn
                FROM temp_word_occurrences
                GROUP BY TiSeID
            ) mapping ON t.TiSeID = mapping.old_seid
            JOIN sentences s ON s.SeOrder = mapping.rn AND s.SeTxID = ?
            SET t.TiSeID = s.SeID",
            [$tid]
        );

        // STEP 1.6: Also update tempexprs.sent if multiword expressions exist.
        // tempexprs was populated before sentences were inserted, so its `sent`
        // field also needs to be aligned with actual SeID values.
        if ($hasmultiword) {
            Connection::preparedExecute(
                "UPDATE tempexprs e
                JOIN (
                    SELECT sent AS old_sent,
                           ROW_NUMBER() OVER (ORDER BY sent) AS rn
                    FROM tempexprs
                    WHERE sent IS NOT NULL
                    GROUP BY sent
                ) mapping ON e.sent = mapping.old_sent
                JOIN sentences s ON s.SeOrder = mapping.rn AND s.SeTxID = ?
                SET e.sent = s.SeID",
                [$tid]
            );
        }

        // STEP 2: Insert text items. TiSeID and tempexprs.sent now equal actual SeID.
        if ($hasmultiword) {
            $bindings = [$lid, $tid, $lid, $lid, $tid, $lid];
            $stmt = Connection::prepare(
                "INSERT INTO word_occurrences (
                    Ti2WoID, Ti2LgID, Ti2TxID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text
                ) SELECT WoID, ?, ?, sent, TiOrder - (2*(n-1)) TiOrder,
                n TiWordCount, word
                FROM tempexprs
                JOIN words
                ON WoTextLC = lword AND WoWordCount = n"
                . UserScopedQuery::forTablePrepared('words', $bindings, '')
                . " WHERE lword IS NOT NULL AND WoLgID = ?
                UNION ALL
                SELECT WoID, ?, ?, TiSeID, TiOrder, TiWordCount, TiText
                FROM temp_word_occurrences
                LEFT JOIN words
                ON LOWER(TiText) = WoTextLC AND TiWordCount=1 AND WoLgID = ?"
                . UserScopedQuery::forTablePrepared('words', $bindings, '')
                . " ORDER BY TiOrder, TiWordCount"
            );
            $stmt->bindValues($bindings);
            $stmt->execute();
        } else {
            $bindings = [$lid, $tid, $lid];
            Connection::preparedExecute(
                "INSERT INTO word_occurrences (
                    Ti2WoID, Ti2LgID, Ti2TxID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text
                )
                SELECT WoID, ?, ?, TiSeID, TiOrder, TiWordCount, TiText
                FROM temp_word_occurrences
                LEFT JOIN words
                ON LOWER(TiText) = WoTextLC AND TiWordCount=1 AND WoLgID = ?"
                . UserScopedQuery::forTablePrepared('words', $bindings, '')
                . " ORDER BY TiOrder, TiWordCount",
                $bindings
            );
        }
    }

    /**
     * Display statistics about a text.
     *
     * @param int  $lid        Language ID
     * @param bool $rtlScript  true if language is right-to-left
     * @param bool $multiwords Display if text has multi-words
     *
     * @return void
     */
    public static function displayStatistics(int $lid, bool $rtlScript, bool $multiwords): void
    {
        $mw = array();
        if ($multiwords) {
            $bindings = [$lid];
            $rows = Connection::preparedFetchAll(
                "SELECT COUNT(WoID) cnt, n as len,
                LOWER(WoText) AS word, WoTranslation
                FROM tempexprs
                JOIN words
                ON WoTextLC = lword AND WoWordCount = n"
                . UserScopedQuery::forTablePrepared('words', $bindings, '')
                . " WHERE lword IS NOT NULL AND WoLgID = ?
                GROUP BY WoID ORDER BY WoTextLC",
                $bindings
            );
            foreach ($rows as $record) {
                $mw[] = array(
                    htmlspecialchars((string)($record['word'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    $record['cnt'],
                    htmlspecialchars((string)($record['WoTranslation'] ?? ''), ENT_QUOTES, 'UTF-8')
                );
            }
        }
        // JavaScript moved to src/frontend/js/texts/text_check_display.ts
        echo '<script type="application/json" id="text-check-config">';
        echo json_encode([
            'words' => [], // Will be populated from text-check-words-config
            'multiWords' => $mw,
            'nonWords' => [], // Will be populated from text-check-words-config
            'rtlScript' => $rtlScript
        ]);
        echo '</script>';
    }

    /**
     * Get all multi-word expression lengths for a language.
     *
     * @param int $lid Language ID
     *
     * @return int[] Array of distinct word counts (e.g., [2, 3] for 2-word and 3-word expressions)
     */
    public static function getMultiWordLengths(int $lid): array
    {
        $wl = [];
        $rows = QueryBuilder::table('words')
            ->select(['DISTINCT(WoWordCount) as WoWordCount'])
            ->where('WoLgID', '=', $lid)
            ->where('WoWordCount', '>', 1)
            ->getPrepared();

        foreach ($rows as $record) {
            $wl[] = (int)$record['WoWordCount'];
        }

        return $wl;
    }

    /**
     * Check a language that contains expressions.
     *
     * @param int[] $wl All the different expression length in the language.
     *
     * @return void
     */
    public static function checkExpressions(array $wl): void
    {
        $wl_max = 0;
        $mw_sql = '';
        foreach ($wl as $word_length) {
            if ($wl_max < $word_length) {
                $wl_max = $word_length;
            }
            $mw_sql .= ' WHEN ' . $word_length .
            ' THEN @a' . ($word_length * 2 - 1);
        }
        $set_wo_sql = $set_wo_sql_2 = $del_wo_sql = $init_var = '';
        // For all possible multi-words length
        for ($i = $wl_max * 2 - 1; $i > 1; $i--) {
            $set_wo_sql .= "WHEN (@a$i := @a" . ($i - 1) . ") IS NULL THEN NULL ";
            $set_wo_sql_2 .= "WHEN (@a$i := @a" . ($i - 2) . ") IS NULL THEN NULL ";
            $del_wo_sql .= "WHEN (@a$i := @a0) IS NULL THEN NULL ";
            $init_var .= "@a$i=0,";
        }
        // 2.8.1-fork: @a0 is always 0? @f always '' but necessary to force code execution
        Connection::query(
            "SET $init_var@a1=0, @a0=0, @se_id=0, @c='', @d=0, @f='', @ti_or=0;"
        );
        // Create a table to store length of each terms
        Connection::query(
            "CREATE TEMPORARY TABLE IF NOT EXISTS numbers(
                n tinyint(3) unsigned NOT NULL
            );"
        );
        Connection::execute("TRUNCATE TABLE numbers");
        Connection::query(
            "INSERT IGNORE INTO numbers(n) VALUES (" .
            implode('),(', $wl) .
            ');'
        );
        // Store garbage
        Connection::query(
            "CREATE TABLE IF NOT EXISTS tempexprs (
                sent mediumint unsigned,
                word varchar(250),
                lword varchar(250),
                TiOrder smallint unsigned,
                n tinyint(3) unsigned NOT NULL
            )"
        );
        Connection::execute("TRUNCATE TABLE tempexprs");
        Connection::query(
            "INSERT IGNORE INTO tempexprs
            (sent, word, lword, TiOrder, n)
            -- 2.10.0-fork: straight_join may be irrelevant as the query is less skewed
            SELECT straight_join
            IF(
                @se_id=TiSeID and @ti_or=TiOrder,
                IF((@ti_or:=TiOrder+@a0) is null,TiSeID,TiSeID),
                IF(
                    @se_id=TiSeID,
                    IF(
                        (@d=1) and (0<>TiWordCount),
                        CASE $set_wo_sql_2
                            WHEN (@a1:=TiCount+@a0) IS NULL THEN NULL
                            WHEN (@se_id:=TiSeID+@a0) IS NULL THEN NULL
                            WHEN (@ti_or:=TiOrder+@a0) IS NULL THEN NULL
                            WHEN (@c:=concat(@c,TiText)) IS NULL THEN NULL
                            WHEN (@d:=(0<>TiWordCount)+@a0) IS NULL THEN NULL
                            ELSE TiSeID
                        END,
                        CASE $set_wo_sql
                            WHEN (@a1:=TiCount+@a0) IS NULL THEN NULL
                            WHEN (@se_id:=TiSeID+@a0) IS NULL THEN NULL
                            WHEN (@ti_or:=TiOrder+@a0) IS NULL THEN NULL
                            WHEN (@c:=concat(@c,TiText)) IS NULL THEN NULL
                            WHEN (@d:=(0<>TiWordCount)+@a0) IS NULL THEN NULL
                            ELSE TiSeID
                        END
                    ),
                    CASE $del_wo_sql
                        WHEN (@a1:=TiCount+@a0) IS NULL THEN NULL
                        WHEN (@se_id:=TiSeID+@a0) IS NULL THEN NULL
                        WHEN (@ti_or:=TiOrder+@a0) IS NULL THEN NULL
                        WHEN (@c:=concat(TiText,@f)) IS NULL THEN NULL
                        WHEN (@d:=(0<>TiWordCount)+@a0) IS NULL THEN NULL
                        ELSE TiSeID
                    END
                )
            ) sent,
            if(
                @d=0,
                NULL,
                if(
                    CRC32(@z:=substr(@c,CASE n$mw_sql END))<>CRC32(LOWER(@z)),
                    @z,
                    ''
                )
            ) word,
            if(@d=0 or ''=@z, NULL, lower(@z)) lword,
            TiOrder,
            n
            FROM numbers , temp_word_occurrences"
        );
    }
}
