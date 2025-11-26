<?php

/**
 * \file
 * \brief Text parsing and processing utilities.
 *
 * PHP version 8.1
 *
 * @category Database
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-text-parsing.html
 * @since    3.0.0
 */

namespace Lwt\Database;

use Lwt\Core\Globals;

/**
 * Text parsing and processing utilities.
 *
 * Provides methods for parsing texts into sentences and words,
 * handling Japanese text with MeCab, and managing text items in the database.
 *
 * @since 3.0.0
 */
class TextParsing
{
    /**
     * Parse a Japanese text using MeCab and add it to the database.
     *
     * @param string $text Text to parse.
     * @param int    $id   Text ID. If $id = -1 print results,
     *                     if $id = -2 return splitted texts
     *
     * @return null|string[] Splitted sentence if $id = -2
     *
     * @psalm-return non-empty-list<string>|null
     */
    public static function parseJapanese(string $text, int $id): ?array
    {
        $tbpref = Globals::getTablePrefix();
        $text = preg_replace('/[ \t]+/u', ' ', $text);
        $text = trim($text);
        if ($id == -1) {
            echo '<div id="check_text" style="margin-right:50px;">
            <h2>Text</h2>
            <p>' . str_replace("\n", "<br /><br />", tohtml($text)) . '</p>';
        } elseif ($id == -2) {
            $text = preg_replace("/[\n]+/u", "\n¶", $text);
            return explode("\n", $text);
        }

        $file_name = tempnam(sys_get_temp_dir(), $tbpref . "tmpti");
        // We use the format "word  num num" for all nodes
        $mecab_args = " -F %m\\t%t\\t%h\\n -U %m\\t%t\\t%h\\n -E EOP\\t3\\t7\\n";
        $mecab_args .= " -o $file_name ";
        $mecab = get_mecab_path($mecab_args);

        // WARNING: \n is converted to PHP_EOL here!
        $handle = popen($mecab, 'w');
        fwrite($handle, $text);
        pclose($handle);

        runsql(
            "CREATE TEMPORARY TABLE IF NOT EXISTS temptextitems2 (
                TiCount smallint(5) unsigned NOT NULL,
                TiSeID mediumint(8) unsigned NOT NULL,
                TiOrder smallint(5) unsigned NOT NULL,
                TiWordCount tinyint(3) unsigned NOT NULL,
                TiText varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
            ) DEFAULT CHARSET=utf8",
            ''
        );
        $handle = fopen($file_name, 'r');
        $mecabed = fread($handle, filesize($file_name));

        fclose($handle);
        $values = array();
        $order = 0;
        $sid = 1;
        if ($id > 0) {
            $sid = (int)get_first_value(
                "SELECT IFNULL(MAX(`SeID`)+1,1) as value
                FROM {$tbpref}sentences"
            );
        }
        $term_type = 0;
        $last_node_type = 0;
        $count = 0;
        $row = array(0, 0, 0, "", 0);
        foreach (explode(PHP_EOL, $mecabed) as $line) {
            if (trim($line) == "") {
                continue;
            }
            list($term, $node_type, $third) = explode(mb_chr(9), $line);
            if ($term_type == 2 || $term == 'EOP' && $third == '7') {
                $sid += 1;
            }
            $row[0] = $sid; // TiSeID
            $row[1] = $count + 1; // TiCount
            $count += mb_strlen($term);
            $last_term_type = $term_type;
            if ($third == '7') {
                if ($term == 'EOP') {
                    $term = '¶';
                }
                $term_type = 2;
            } elseif (in_array($node_type, ['2', '6', '7', '8'])) {
                $term_type = 0;
            } else {
                $term_type = 1;
            }

            // Increase word order:
            // Once if the current or the previous term were words
            // Twice if current or the previous were not of unmanaged type
            $order += (int)($term_type == 0 && $last_term_type == 0) +
            (int)($term_type != 1 || $last_term_type != 1);
            $row[2] = $order; // TiOrder
            $row[3] = Escaping::toSqlSyntaxNoTrimNoNull($term); // TiText
            $row[4] = $term_type == 0 ? 1 : 0; // TiWordCount
            $values[] = $row;
            // Special case for kazu (numbers)
            if ($last_node_type == 8 && $node_type == 8) {
                $lastKey = array_key_last($values);
                if ($lastKey !== null) {
                    // Concatenate the previous value with the current term
                    $values[$lastKey - 1][3] = Escaping::toSqlSyntaxNoTrimNoNull(
                        str_replace("'", '', $values[$lastKey - 1][3]) . $term
                    );
                }
                // Remove last element to avoid repetition
                array_pop($values);
            }
            $last_node_type = $node_type;
        }

        // Add parenthesis around each element
        $formatted_string = array();
        foreach ($values as $key => $value) {
            $formatted_string[$key] = "(" . implode(",", $value) . ")";
        }
        do_mysqli_query(
            "INSERT INTO temptextitems2 (
                TiSeID, TiCount, TiOrder, TiText, TiWordCount
            ) VALUES " . implode(',', $formatted_string)
        );
        // Delete elements TiOrder=@order
        do_mysqli_query("DELETE FROM temptextitems2 WHERE TiOrder=$order");
        do_mysqli_query(
            "INSERT INTO {$tbpref}temptextitems (
                TiCount, TiSeID, TiOrder, TiWordCount, TiText
            )
            SELECT MIN(TiCount) s, TiSeID, TiOrder, TiWordCount,
            group_concat(TiText ORDER BY TiCount SEPARATOR '')
            FROM temptextitems2
            GROUP BY TiOrder"
        );
        do_mysqli_query("DROP TABLE temptextitems2");
        unlink($file_name);
        return null;
    }

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
        $tbpref = Globals::getTablePrefix();
        $file_name = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $tbpref . "tmpti.txt";
        $fp = fopen($file_name, 'w');
        fwrite($fp, $text);
        fclose($fp);
        do_mysqli_query("SET @order=0, @sid=1, @count = 0;");
        if ($id > 0) {
            do_mysqli_query(
                "SET @sid=(SELECT ifnull(max(`SeID`)+1,1) FROM `{$tbpref}sentences`);"
            );
        }
        $sql = "LOAD DATA LOCAL INFILE " . Escaping::toSqlSyntax($file_name) . "
        INTO TABLE {$tbpref}temptextitems
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
            do_mysqli_query($sql);
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
    private static function saveWithSqlFallback(string $text, int $id): void
    {
        $tbpref = Globals::getTablePrefix();
        $connection = Globals::getDbConnection();

        // Get starting sentence ID
        if ($id > 0) {
            $result = do_mysqli_query(
                "SELECT ifnull(max(`SeID`)+1,1) as maxid FROM `{$tbpref}sentences`"
            );
            if ($result === false || $result === true) {
                $sid = 1;
            } else {
                $row = mysqli_fetch_assoc($result);
                $sid = $row ? (int)$row['maxid'] : 1;
            }
        } else {
            $sid = 1;
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

            $escaped_term = mysqli_real_escape_string($connection, $term);

            do_mysqli_query(
                "INSERT INTO `{$tbpref}temptextitems` 
                (TiSeID, TiCount, TiOrder, TiText, TiWordCount) 
                VALUES ($sid, $current_count, $order, '$escaped_term', $word_count)"
            );
        }
    }

    /**
     * Parse a text using the default tools. It is a not-japanese text.
     *
     * @param string $text Text to parse
     * @param int    $id   Text ID. If $id == -2, only split the text.
     * @param int    $lid  Language ID.
     *
     * @return null|string[] If $id == -2 return a splitted version of the text.
     *
     * @psalm-return non-empty-list<string>|null
     */
    public static function parseStandard(string $text, int $id, int $lid): ?array
    {
        $tbpref = Globals::getTablePrefix();
        $sql = "SELECT * FROM {$tbpref}languages WHERE LgID=$lid";
        $res = do_mysqli_query($sql);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);

        // Return null if language not found
        if ($record === false || $record === null) {
            return null;
        }

        $removeSpaces = (string)$record['LgRemoveSpaces'];
        $splitSentence = (string)$record['LgRegexpSplitSentences'];
        $noSentenceEnd = (string)$record['LgExceptionsSplitSentences'];
        $termchar = (string)$record['LgRegexpWordCharacters'];
        $rtlScript = $record['LgRightToLeft'];
        // Split text paragraphs using " ¶" symbol
        $text = str_replace("\n", " ¶", $text);
        $text = trim($text);
        if ((int)$record['LgSplitEachChar'] === 1) {
            $text = preg_replace('/([^\s])/u', "$1\t", $text);
        }
        $text = preg_replace('/\s+/u', ' ', $text);
        if ($id == -1) {
            echo "<div id=\"check_text\" style=\"margin-right:50px;\">
            <h4>Text</h4>
            <p " . ($rtlScript ? 'dir="rtl"' : '') . ">" .
            str_replace("¶", "<br /><br />", tohtml($text)) .
            "</p>";
        }
        // "\r" => Sentence delimiter, "\t" and "\n" => Word delimiter
        $text = preg_replace_callback(
            "/(\S+)\s*((\.+)|([$splitSentence]))([]'`\"”)‘’‹›“„«»』」]*)(?=(\s*)(\S+|$))/u",
            // Arrow functions were introduced in PHP 7.4
            //fn ($matches) => find_latin_sentence_end($matches, $noSentenceEnd)
            function ($matches) use ($noSentenceEnd) {
                return \find_latin_sentence_end($matches, $noSentenceEnd);
            },
            $text
        );
        // Paragraph delimiters become a combination of ¶ and carriage return \r
        $text = str_replace(array("¶"," ¶"), array("¶\r","\r¶"), $text);
        $text = preg_replace(
            array(
                '/([^' . $termchar . '])/u',
                '/\n([' . $splitSentence . '][\'`"”)\]‘’‹›“„«»』」]*)\n\t/u',
                '/([0-9])[\n]([:.,])[\n]([0-9])/u'
            ),
            array("\n$1\n", "$1", "$1$2$3"),
            $text
        );
        if ($id == -2) {
            $text = remove_spaces(
                str_replace(
                    array("\r\r", "\t", "\n"),
                    array("\r", "", ""),
                    $text
                ),
                $removeSpaces
            );
            return explode("\r", $text);
        }


        $text = trim(
            preg_replace(
                array(
                    "/\r(?=[]'`\"”)‘’‹›“„«»』」 ]*\r)/u",
                    '/[\n]+\r/u',
                    '/\r([^\n])/u',
                    "/\n[.](?![]'`\"”)‘’‹›“„«»』」]*\r)/u",
                    "/(\n|^)(?=.?[$termchar][^\n]*\n)/u"
                ),
                array(
                    "",
                    "\r",
                    "\r\n$1",
                    ".\n",
                    "\n1\t"
                ),
                str_replace(array("\t", "\n\n"), array("\n", ""), $text)
            )
        );
        $text = remove_spaces(
            preg_replace("/(\n|^)(?!1\t)/u", "\n0\t", $text),
            $removeSpaces
        );
        // It is faster to write to a file and let SQL do its magic, but may run into
        // security restrictions
        $use_local_infile = in_array(
            get_first_value("SELECT @@GLOBAL.local_infile as value"),
            array(1, '1', 'ON')
        );
        if ($use_local_infile) {
            self::saveWithSql($text, $id);
        } else {
            $values = array();
            $order = 0;
            $sid = 1;
            if ($id > 0) {
                $sid = (int)get_first_value(
                    "SELECT IFNULL(MAX(`SeID`)+1,1) as value
                    FROM {$tbpref}sentences"
                );
            }
            $count = 0;
            $row = array(0, 0, 0, "", 0);
            foreach (explode("\n", $text) as $line) {
                if (trim($line) == "") {
                    continue;
                }
                list($word_count, $term) = explode("\t", $line);
                $row[0] = $sid; // TiSeID
                $row[1] = $count + 1; // TiCount
                $count += mb_strlen($term);
                if (str_ends_with($term, "\r")) {
                    $term = str_replace("\r", '', $term);
                    $sid++;
                    $count = 0;
                }
                $row[2] = ++$order; // TiOrder
                $row[3] = Escaping::toSqlSyntaxNoTrimNoNull($term); // TiText
                $row[4] = (int)$word_count; // TiWordCount
                $values[] = "(" . implode(",", $row) . ")";
            }
            do_mysqli_query(
                "INSERT INTO {$tbpref}temptextitems (
                    TiSeID, TiCount, TiOrder, TiText, TiWordCount
                ) VALUES " . implode(',', $values)
            );
        }
        return null;
    }


    /**
     * Pre-parse the input text before a definitive parsing by a specialized parser.
     *
     * @param string $text Text to parse
     * @param int    $id   Text ID
     * @param int    $lid  Language ID
     *
     * @return null|string[] If $id = -2 return a splitted version of the text
     *
     * @psalm-return non-empty-list<string>|null
     */
    public static function prepare(string $text, int $id, int $lid): ?array
    {
        $tbpref = Globals::getTablePrefix();
        $sql = "SELECT * FROM {$tbpref}languages WHERE LgID = $lid";
        $res = do_mysqli_query($sql);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);

        // Return null if language not found
        if ($record === false || $record === null) {
            return null;
        }

        $termchar = (string)$record['LgRegexpWordCharacters'];
        $replace = explode("|", (string) $record['LgCharacterSubstitutions']);
        $text = Escaping::prepareTextdata($text);
        //if(is_callable('normalizer_normalize')) $s = normalizer_normalize($s);
        do_mysqli_query('TRUNCATE TABLE ' . $tbpref . 'temptextitems');

        // because of sentence special characters
        $text = str_replace(array('}', '{'), array(']', '['), $text);
        foreach ($replace as $value) {
            $fromto = explode("=", trim($value));
            if (count($fromto) >= 2) {
                $text = str_replace(trim($fromto[0]), trim($fromto[1]), $text);
            }
        }

        if ('MECAB' == strtoupper(trim($termchar))) {
            return self::parseJapanese($text, $id);
        }
        return self::parseStandard($text, $id, $lid);
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
        $tbpref = Globals::getTablePrefix();
        $wo = $nw = array();
        $res = do_mysqli_query(
            'SELECT GROUP_CONCAT(TiText order by TiOrder SEPARATOR "")
            Sent FROM ' . $tbpref . 'temptextitems group by TiSeID'
        );
        if ($res !== false && $res !== true) {
            echo '<h4>Sentences</h4><ol>';
            while ($record = mysqli_fetch_assoc($res)) {
                echo "<li>" . tohtml($record['Sent']) . "</li>";
            }
            mysqli_free_result($res);
        }
        echo '</ol>';
        $res = do_mysqli_query(
            "SELECT count(`TiOrder`) cnt, if(0=TiWordCount,0,1) as len,
            LOWER(TiText) as word, WoTranslation
            FROM {$tbpref}temptextitems
            LEFT JOIN {$tbpref}words ON lower(TiText)=WoTextLC AND WoLgID=$lid
            GROUP BY lower(TiText)"
        );
        while ($record = mysqli_fetch_assoc($res)) {
            if ($record['len'] == 1) {
                $wo[] = array(
                    tohtml($record['word']),
                    $record['cnt'],
                    tohtml($record['WoTranslation'])
                );
            } else {
                $nw[] = array(
                    tohtml((string)$record['word']),
                    tohtml((string)$record['cnt'])
                );
            }
        }
        mysqli_free_result($res);
        echo '<script type="text/javascript">
        WORDS = ', json_encode($wo), ';
        NOWORDS = ', json_encode($nw), ';
        </script>';
    }


    /**
     * Append sentences and text items in the database.
     *
     * @param int  $tid          ID of text from which insert data
     * @param int  $lid          ID of the language of the text
     * @param bool $hasmultiword Set to true to insert multi-words as well.
     *
     * @return void
     */
    public static function registerSentencesTextItems(int $tid, int $lid, bool $hasmultiword): void
    {
        $tbpref = Globals::getTablePrefix();

        $sql = '';
        // Text has multi-words, add them to the query
        if ($hasmultiword) {
            $sql = "SELECT WoID, $lid, $tid, sent, TiOrder - (2*(n-1)) TiOrder,
            n TiWordCount, word
            FROM {$tbpref}tempexprs
            JOIN {$tbpref}words
            ON WoTextLC = lword AND WoWordCount = n
            WHERE lword IS NOT NULL AND WoLgID = $lid
            UNION ALL ";
        }

        // Insert text items (and eventual multi-words)
        do_mysqli_query(
            "INSERT INTO {$tbpref}textitems2 (
                Ti2WoID, Ti2LgID, Ti2TxID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text
            ) $sql
            SELECT WoID, $lid, $tid, TiSeID, TiOrder, TiWordCount, TiText
            FROM {$tbpref}temptextitems
            LEFT JOIN {$tbpref}words
            ON LOWER(TiText) = WoTextLC AND TiWordCount=1 AND WoLgID = $lid
            ORDER BY TiOrder, TiWordCount"
        );

        // Add new sentences
        do_mysqli_query('SET @i=0;');
        do_mysqli_query(
            "INSERT INTO {$tbpref}sentences (
                SeLgID, SeTxID, SeOrder, SeFirstPos, SeText
            ) SELECT
            $lid,
            $tid,
            @i:=@i+1,
            MIN(IF(TiWordCount=0, TiOrder+1, TiOrder)),
            GROUP_CONCAT(TiText ORDER BY TiOrder SEPARATOR \"\")
            FROM {$tbpref}temptextitems
            GROUP BY TiSeID"
        );
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
        $tbpref = Globals::getTablePrefix();

        $mw = array();
        if ($multiwords) {
            $res = do_mysqli_query(
                "SELECT COUNT(WoID) cnt, n as len,
                LOWER(WoText) AS word, WoTranslation
                FROM {$tbpref}tempexprs
                JOIN {$tbpref}words
                ON WoTextLC = lword AND WoWordCount = n
                WHERE lword IS NOT NULL AND WoLgID = $lid
                GROUP BY WoID ORDER BY WoTextLC"
            );
            while ($record = mysqli_fetch_assoc($res)) {
                $mw[] = array(
                    tohtml((string)$record['word']),
                    $record['cnt'],
                    tohtml((string)$record['WoTranslation'])
                );
            }
            mysqli_free_result($res);
        }
        ?>
<script type="text/javascript">
    MWORDS = <?php echo json_encode($mw) ?>;
    if (<?php echo json_encode($rtlScript); ?>) {
        $(function() {
            $("li").attr("dir", "rtl");
        });
    }
    function displayStatistics() {
        let h = '<h4>Word List <span class="red2">(red = already saved)</span></h4>' +
        '<ul class="wordlist">';
        $.each(
            WORDS,
            function (k,v) {
                h += '<li><span' + (v[2]==""?"":'class="red2"') + '>[' + v[0] + '] — '
                + v[1] + (v[2]==""?"":' — ' + v[2]) + '</span></li>';
            }
        );
        h += '</ul><p>TOTAL: ' + WORDS.length
        + '</p><h4>Expression List</span></h4><ul class="expressionlist">';
        $.each(MWORDS, function (k,v) {
            h+= '<li><span>[' + v[0] + '] — ' + v[1] +
            (v[2]==""?"":'— ' + v[2]) + '</span></li>';
        });
        h += '</ul><p>TOTAL: ' + MWORDS.length +
        '</p><h4>Non-Word List</span></h4><ul class="nonwordlist">';
        $.each(NOWORDS, function(k,v) {
            h+= '<li>[' + v[0] + '] — ' + v[1] + '</li>';
        });
        h += '</ul><p>TOTAL: ' + NOWORDS.length + '</p>'
        $('#check_text').append(h);
    }

    displayStatistics();
</script>

        <?php
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
        $tbpref = Globals::getTablePrefix();

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
        do_mysqli_query(
            "SET $init_var@a1=0, @a0=0, @se_id=0, @c='', @d=0, @f='', @ti_or=0;"
        );
        // Create a table to store length of each terms
        do_mysqli_query(
            "CREATE TEMPORARY TABLE IF NOT EXISTS {$tbpref}numbers(
                n tinyint(3) unsigned NOT NULL
            );"
        );
        do_mysqli_query("TRUNCATE TABLE {$tbpref}numbers");
        do_mysqli_query(
            "INSERT IGNORE INTO {$tbpref}numbers(n) VALUES (" .
            implode('),(', $wl) .
            ');'
        );
        // Store garbage
        do_mysqli_query(
            "CREATE TABLE IF NOT EXISTS {$tbpref}tempexprs (
                sent mediumint unsigned,
                word varchar(250),
                lword varchar(250),
                TiOrder smallint unsigned,
                n tinyint(3) unsigned NOT NULL
            )"
        );
        do_mysqli_query("TRUNCATE TABLE {$tbpref}tempexprs");
        do_mysqli_query(
            "INSERT IGNORE INTO {$tbpref}tempexprs
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
            FROM {$tbpref}numbers , {$tbpref}temptextitems"
        );
    }

    /**
     * Parse the input text.
     *
     * @param string     $text Text to parse
     * @param string|int $lid  Language ID (LgID from languages table)
     * @param int        $id   References whether the text is new to the database
     *                         $id = -1     => Check, return protocol
     *                         $id = -2     => Only return sentence array
     *                         $id = TextID => Split: insert sentences/textitems entries in DB
     *
     * @return null|string[] The sentence array if $id = -2
     *
     * @psalm-return non-empty-list<string>|null
     */
    public static function splitCheck(string $text, string|int $lid, int $id): ?array
    {
        $tbpref = Globals::getTablePrefix();
        $wl = array();
        $lid = (int) $lid;
        $sql = "SELECT LgRightToLeft FROM {$tbpref}languages WHERE LgID = $lid";
        $res = do_mysqli_query($sql);
        $record = mysqli_fetch_assoc($res);
        // Just checking if LgID exists with ID should be enough
        if ($record == false) {
            my_die("Language data not found: $sql");
        }
        $rtlScript = $record['LgRightToLeft'];
        mysqli_free_result($res);

        if ($id == -2) {
            /*
            Replacement code not created yet

            trigger_error(
                "Using splitCheckText with \$id == -2 is deprecated and won't work in
                LWT 3.0.0. Use format_text instead.",
                E_USER_WARNING
            );*/
            return self::prepare($text, -2, $lid);
        }
        self::prepare($text, $id, $lid);

        // Check text
        if ($id == -1) {
            self::checkValid($lid);
        }

        // Get multi-word count
        $res = do_mysqli_query(
            "SELECT DISTINCT(WoWordCount)
            FROM {$tbpref}words
            WHERE WoLgID = $lid AND WoWordCount > 1"
        );
        while ($record = mysqli_fetch_assoc($res)) {
            $wl[] = (int)$record['WoWordCount'];
        }
        mysqli_free_result($res);
        // Text has multi-words
        if (!empty($wl)) {
            self::checkExpressions($wl);
        }
        // Add sentences and text items to database for a new text
        if ($id > 0) {
            self::registerSentencesTextItems($id, $lid, !empty($wl));
        }

        // Check text
        if ($id == -1) {
            self::displayStatistics($lid, (bool)$rtlScript, !empty($wl));
        }

        do_mysqli_query("TRUNCATE TABLE {$tbpref}temptextitems");
        return null;
    }
}
