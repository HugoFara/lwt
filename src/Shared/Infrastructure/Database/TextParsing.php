<?php declare(strict_types=1);
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

namespace Lwt\Shared\Infrastructure\Database;

use Lwt\Core\Globals;
use Lwt\Core\StringUtils;
use Lwt\Core\Utils\ErrorHandler;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;
use Lwt\Modules\Language\Application\Services\TextParsingService;

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
    // =========================================================================
    // NEW PUBLIC API - Use these methods instead of the deprecated ones
    // =========================================================================

    /**
     * Split text into sentences without database operations.
     *
     * Use this method when you only need to split text into sentences
     * without saving to the database (e.g., for long text splitting).
     *
     * @param string $text Text to parse
     * @param int    $lid  Language ID
     *
     * @return string[] Array of sentences
     *
     * @psalm-return non-empty-list<string>
     */
    public static function splitIntoSentences(string $text, int $lid): array
    {
        $result = self::prepare($text, -2, $lid);
        return $result ?? [''];
    }

    /**
     * Parse text and display preview HTML for validation.
     *
     * Use this method for the text checking UI. Outputs HTML directly
     * to show parsed sentences and word statistics.
     *
     * @param string $text Text to parse
     * @param int    $lid  Language ID
     *
     * @return void
     */
    public static function parseAndDisplayPreview(string $text, int $lid): void
    {
        self::splitCheck($text, $lid, -1);
    }

    /**
     * Parse text and save to database.
     *
     * Use this method when creating or updating texts. Parses the text
     * and inserts sentences and text items into the database.
     *
     * @param string $text   Text to parse
     * @param int    $lid    Language ID
     * @param int    $textId Text ID (must be positive)
     *
     * @return void
     *
     * @throws \InvalidArgumentException If textId is not positive
     */
    public static function parseAndSave(string $text, int $lid, int $textId): void
    {
        if ($textId <= 0) {
            throw new \InvalidArgumentException(
                "Text ID must be positive, got: $textId"
            );
        }
        self::splitCheck($text, $lid, $textId);
    }

    /**
     * Check/preview text and return parsing statistics without saving.
     *
     * Use this method to get text statistics for preview purposes.
     * Does not output any HTML or save to database.
     *
     * @param string $text Text to parse
     * @param int    $lid  Language ID
     *
     * @return array{sentences: int, words: int, unknownPercent: float, preview: string}
     */
    public static function checkText(string $text, int $lid): array
    {
        $settings = self::getLanguageSettings($lid);

        if ($settings === null) {
            return [
                'sentences' => 0,
                'words' => 0,
                'unknownPercent' => 100.0,
                'preview' => ''
            ];
        }

        // Prepare text into temptextitems
        self::prepare($text, -1, $lid);

        // Get sentence count
        $sentences = Connection::fetchAll(
            'SELECT GROUP_CONCAT(TiText ORDER BY TiOrder SEPARATOR "")
            AS Sent FROM temptextitems GROUP BY TiSeID'
        );
        $sentenceCount = count($sentences);

        // Build preview from first few sentences
        $preview = '';
        $previewSentences = array_slice($sentences, 0, 3);
        foreach ($previewSentences as $record) {
            if ($preview !== '') {
                $preview .= ' ';
            }
            $preview .= (string) ($record['Sent'] ?? '');
        }
        if (count($sentences) > 3) {
            $preview .= '...';
        }

        // Get word statistics
        $bindings = [$lid];
        $rows = Connection::preparedFetchAll(
            "SELECT COUNT(`TiOrder`) AS cnt, IF(0=TiWordCount,0,1) AS len,
            LOWER(TiText) AS word, WoTranslation
            FROM temptextitems
            LEFT JOIN words ON LOWER(TiText)=WoTextLC AND WoLgID=?"
            . UserScopedQuery::forTablePrepared('words', $bindings, '')
            . " GROUP BY LOWER(TiText)",
            $bindings
        );

        $totalWords = 0;
        $unknownWords = 0;

        foreach ($rows as $record) {
            if ($record['len'] == 1) {
                $totalWords += (int) $record['cnt'];
                // Word is unknown if it has no translation
                if (empty($record['WoTranslation'])) {
                    $unknownWords += (int) $record['cnt'];
                }
            }
        }

        $unknownPercent = $totalWords > 0
            ? round(($unknownWords / $totalWords) * 100, 1)
            : 100.0;

        // Clean up temptextitems
        QueryBuilder::table('temptextitems')->truncate();

        return [
            'sentences' => $sentenceCount,
            'words' => $totalWords,
            'unknownPercent' => $unknownPercent,
            'preview' => $preview
        ];
    }

    // =========================================================================
    // INTERNAL HELPERS - Japanese text parsing
    // =========================================================================

    /**
     * Split Japanese text into sentences (split-only mode).
     *
     * @param string $text Preprocessed text
     *
     * @return string[] Array of sentences
     *
     * @psalm-return non-empty-list<string>
     */
    private static function splitJapaneseSentences(string $text): array
    {
        $text = preg_replace('/[ \t]+/u', ' ', $text);
        $text = trim($text);
        $text = preg_replace("/[\n]+/u", "\n¶", $text);
        return explode("\n", $text);
    }

    /**
     * Display preview HTML for Japanese text.
     *
     * @param string $text Preprocessed text
     *
     * @return void
     */
    private static function displayJapanesePreview(string $text): void
    {
        $text = preg_replace('/[ \t]+/u', ' ', $text);
        $text = trim($text);
        echo '<div id="check_text" style="margin-right:50px;">
        <h2>Text</h2>
        <p>' . str_replace("\n", "<br /><br />", \htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8')) . '</p>';
    }

    /**
     * Parse Japanese text with MeCab and insert into temptextitems.
     *
     * @param string $text         Preprocessed text
     * @param bool   $useMaxSeID   Whether to query for max sentence ID (true for existing texts)
     *
     * @return void
     */
    private static function parseJapaneseToDatabase(string $text, bool $useMaxSeID): void
    {
        $text = preg_replace('/[ \t]+/u', ' ', $text);
        $text = trim($text);

        $file_name = tempnam(sys_get_temp_dir(), "tmpti");
        // We use the format "word  num num" for all nodes
        $mecab_args = " -F %m\\t%t\\t%h\\n -U %m\\t%t\\t%h\\n -E EOP\\t3\\t7\\n";
        $mecab_args .= " -o $file_name ";
        $mecab = (new TextParsingService())->getMecabPath($mecab_args);

        // WARNING: \n is converted to PHP_EOL here!
        $handle = popen($mecab, 'w');
        fwrite($handle, $text);
        pclose($handle);

        Connection::execute(
            "CREATE TEMPORARY TABLE IF NOT EXISTS temptextitems2 (
                TiCount smallint(5) unsigned NOT NULL,
                TiSeID mediumint(8) unsigned NOT NULL,
                TiOrder smallint(5) unsigned NOT NULL,
                TiWordCount tinyint(3) unsigned NOT NULL,
                TiText varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
            ) DEFAULT CHARSET=utf8"
        );
        $handle = fopen($file_name, 'r');
        $mecabed = fread($handle, filesize($file_name));

        fclose($handle);
        $values = array();
        $order = 0;
        $sid = 1;
        if ($useMaxSeID) {
            $sid = (int)Connection::fetchValue(
                "SELECT IFNULL(MAX(`SeID`)+1,1) as value FROM sentences"
                . UserScopedQuery::forTable('sentences')
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
            $row[3] = $term; // TiText (no escaping needed for prepared statement)
            $row[4] = $term_type == 0 ? 1 : 0; // TiWordCount
            $values[] = $row;
            // Special case for kazu (numbers)
            if ($last_node_type == 8 && $node_type == 8) {
                $lastKey = array_key_last($values);
                // $lastKey is int<0, max> since we just added an element
                // We need at least 2 elements to access previous
                if ($lastKey > 0 && isset($values[$lastKey - 1][3])) {
                    // Concatenate the previous value with the current term
                    $values[$lastKey - 1][3] = $values[$lastKey - 1][3] . $term;
                    // Remove last element to avoid repetition
                    array_pop($values);
                }
            }
            $last_node_type = $node_type;
        }

        // Build multi-row INSERT with prepared statement
        // Generate placeholders for all rows: (?, ?, ?, ?, ?), (?, ?, ?, ?, ?), ...
        $placeholders = array();
        $flatParams = array();
        foreach ($values as $row) {
            $placeholders[] = "(?, ?, ?, ?, ?)";
            // Flatten the row values into a single array for binding
            $flatParams[] = $row[0]; // TiSeID
            $flatParams[] = $row[1]; // TiCount
            $flatParams[] = $row[2]; // TiOrder
            $flatParams[] = $row[3]; // TiText
            $flatParams[] = $row[4]; // TiWordCount
        }

        if (!empty($placeholders)) {
            Connection::preparedExecute(
                "INSERT INTO temptextitems2 (
                    TiSeID, TiCount, TiOrder, TiText, TiWordCount
                ) VALUES " . implode(',', $placeholders),
                $flatParams
            );
        }
        // Delete elements TiOrder=@order
        Connection::preparedExecute(
            "DELETE FROM temptextitems2 WHERE TiOrder=?",
            [$order]
        );
        Connection::query(
            "INSERT INTO temptextitems (
                TiCount, TiSeID, TiOrder, TiWordCount, TiText
            )
            SELECT MIN(TiCount) s, TiSeID, TiOrder, TiWordCount,
            group_concat(TiText ORDER BY TiCount SEPARATOR '')
            FROM temptextitems2
            GROUP BY TiOrder"
        );
        Connection::execute("DROP TABLE temptextitems2");
        unlink($file_name);
    }

    // =========================================================================
    // DEPRECATED METHODS - Kept for backwards compatibility
    // =========================================================================

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
     *
     * @deprecated Use splitIntoSentences(), parseAndDisplayPreview(), or parseAndSave() instead.
     */
    public static function parseJapanese(string $text, int $id): ?array
    {
        if ($id == -2) {
            return self::splitJapaneseSentences($text);
        }

        if ($id == -1) {
            self::displayJapanesePreview($text);
        }

        self::parseJapaneseToDatabase($text, $id > 0);
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
        $file_name = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "tmpti.txt";
        $fp = fopen($file_name, 'w');
        fwrite($fp, $text);
        fclose($fp);
        Connection::query("SET @order=0, @sid=1, @count = 0;");
        if ($id > 0) {
            // Get next auto-increment value for accurate TiSeID calculation
            $dbname = Globals::getDatabaseName();
            $sentencesTable = Globals::table('sentences');
            $autoInc = (int)Connection::fetchValue(
                "SELECT AUTO_INCREMENT as value FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = '$dbname' AND TABLE_NAME = '$sentencesTable'"
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
        $escaped_file_name = mysqli_real_escape_string($connection, $file_name);
        $sql = "LOAD DATA LOCAL INFILE '$escaped_file_name'
        INTO TABLE temptextitems
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
    private static function saveWithSqlFallback(string $text, int $id): void
    {
        // Get starting sentence ID
        $sid = 1;
        if ($id > 0) {
            // Get next auto-increment value for accurate TiSeID calculation
            $dbname = Globals::getDatabaseName();
            $sentencesTable = Globals::table('sentences');
            $sid = (int)Connection::fetchValue(
                "SELECT AUTO_INCREMENT as value FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = '$dbname' AND TABLE_NAME = '$sentencesTable'"
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
                "INSERT INTO temptextitems
                (TiSeID, TiCount, TiOrder, TiText, TiWordCount)
                VALUES (?, ?, ?, ?, ?)",
                [$sid, $current_count, $order, $term, $word_count]
            );
        }
    }

    // =========================================================================
    // INTERNAL HELPERS - Standard text parsing
    // =========================================================================

    /**
     * Get language settings for parsing.
     *
     * @param int $lid Language ID
     *
     * @return array{
     *     removeSpaces: string,
     *     splitSentence: string,
     *     noSentenceEnd: string,
     *     termchar: string,
     *     rtlScript: mixed,
     *     splitEachChar: bool
     * }|null Language settings or null if not found
     */
    private static function getLanguageSettings(int $lid): ?array
    {
        $record = QueryBuilder::table('languages')
            ->where('LgID', '=', $lid)
            ->firstPrepared();

        if ($record === null) {
            return null;
        }

        return [
            'removeSpaces' => (string)$record['LgRemoveSpaces'],
            'splitSentence' => (string)$record['LgRegexpSplitSentences'],
            'noSentenceEnd' => (string)$record['LgExceptionsSplitSentences'],
            'termchar' => (string)$record['LgRegexpWordCharacters'],
            'rtlScript' => $record['LgRightToLeft'],
            'splitEachChar' => ((int)$record['LgSplitEachChar'] === 1),
        ];
    }

    /**
     * Apply initial text transformations (before display preview).
     *
     * @param string $text          Raw text
     * @param bool   $splitEachChar Whether to split each character
     *
     * @return string Text after initial transformations
     */
    private static function applyInitialTransformations(
        string $text,
        bool $splitEachChar
    ): string {
        // Split text paragraphs using " ¶" symbol
        $text = str_replace("\n", " ¶", $text);
        $text = trim($text);
        if ($splitEachChar) {
            $text = preg_replace('/([^\s])/u', "$1\t", $text);
        }
        $text = preg_replace('/\s+/u', ' ', $text);
        return $text;
    }

    /**
     * Apply word-splitting transformations (after display preview).
     *
     * @param string $text          Text after initial transformations
     * @param string $splitSentence Sentence split regex
     * @param string $noSentenceEnd Exception patterns
     * @param string $termchar      Word character regex
     *
     * @return string Preprocessed text ready for parsing
     */
    private static function applyWordSplitting(
        string $text,
        string $splitSentence,
        string $noSentenceEnd,
        string $termchar
    ): string {
        // "\r" => Sentence delimiter, "\t" and "\n" => Word delimiter
        $service = new TextParsingService();
        $text = preg_replace_callback(
            "/(\S+)\s*((\.+)|([$splitSentence]))([]'`\"”)‘’‹›“„«»』」]*)(?=(\s*)(\S+|$))/u",
            fn ($matches) => $service->findLatinSentenceEnd($matches, $noSentenceEnd),
            $text
        );
        // Paragraph delimiters become a combination of ¶ and carriage return \r
        $text = str_replace(array("¶", " ¶"), array("¶\r", "\r¶"), $text);
        $text = preg_replace(
            array(
                '/([^' . $termchar . '])/u',
                '/\n([' . $splitSentence . '][\'`"”)\]‘’‹›“„«»』」]*)\n\t/u',
                '/([0-9])[\n]([:.,])[\n]([0-9])/u'
            ),
            array("\n$1\n", "$1", "$1$2$3"),
            $text
        );

        return $text;
    }

    /**
     * Split standard text into sentences (split-only mode).
     *
     * @param string $text         Preprocessed text
     * @param string $removeSpaces Space removal setting
     *
     * @return string[] Array of sentences
     *
     * @psalm-return non-empty-list<string>
     */
    private static function splitStandardSentences(string $text, string $removeSpaces): array
    {
        $text = StringUtils::removeSpaces(
            str_replace(
                array("\r\r", "\t", "\n"),
                array("\r", "", ""),
                $text
            ),
            $removeSpaces
        );
        return explode("\r", $text);
    }

    /**
     * Display preview HTML for standard text.
     *
     * @param string $text      Preprocessed text (after initial transformations)
     * @param bool   $rtlScript Whether text is right-to-left
     *
     * @return void
     */
    private static function displayStandardPreview(string $text, bool $rtlScript): void
    {
        echo "<div id=\"check_text\" style=\"margin-right:50px;\">
        <h4>Text</h4>
        <p " . ($rtlScript ? 'dir="rtl"' : '') . ">" .
        str_replace("¶", "<br /><br />", \htmlspecialchars($text, ENT_QUOTES, 'UTF-8')) .
        "</p>";
    }

    /**
     * Parse standard text and insert into temptextitems.
     *
     * @param string $text         Preprocessed text
     * @param string $termchar     Word character regex
     * @param string $removeSpaces Space removal setting
     * @param bool   $useMaxSeID   Whether to query for max sentence ID
     *
     * @return void
     */
    private static function parseStandardToDatabase(
        string $text,
        string $termchar,
        string $removeSpaces,
        bool $useMaxSeID
    ): void {
        $text = trim(
            preg_replace(
                array(
                    "/\r(?=[]'`\"”)‘’‹›“„«»』」 ]*\r)/u",
                    '/[\n]+\r/u',
                    '/\r([^\n])/u',
                    "/\n[.](?![]'`\"”)‘’‹›“„«»』」]*\r)/u",
                    "/(\n|^)(?=.?[$termchar][^\n]*(\n|$))/u"
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
        $text = StringUtils::removeSpaces(
            preg_replace("/(\n|^)(?!1\t)/u", "\n0\t", $text),
            $removeSpaces
        );

        // It is faster to write to a file and let SQL do its magic, but may run into
        // security restrictions
        $use_local_infile = in_array(
            Connection::fetchValue("SELECT @@GLOBAL.local_infile as value"),
            array(1, '1', 'ON')
        );
        // For database mode, we use a positive ID placeholder (1) since saveWithSql
        // only checks if id > 0 for sentence ID calculation
        $idForSql = $useMaxSeID ? 1 : 0;
        if ($use_local_infile) {
            self::saveWithSql($text, $idForSql);
        } else {
            $order = 0;
            $sid = 1;
            if ($useMaxSeID) {
                // Get next auto-increment value from table status
                // This is more reliable than MAX(SeID)+1 when there are gaps
                $dbname = Globals::getDatabaseName();
                $sentencesTable = Globals::table('sentences');
                $sid = (int)Connection::fetchValue(
                    "SELECT AUTO_INCREMENT as value FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = '$dbname' AND TABLE_NAME = '$sentencesTable'"
                );
                // Fall back to MAX+1 if AUTO_INCREMENT is not available
                if ($sid <= 0) {
                    $sid = (int)Connection::fetchValue(
                        "SELECT IFNULL(MAX(`SeID`)+1,1) as value FROM sentences"
                        . UserScopedQuery::forTable('sentences')
                    );
                }
            }
            $count = 0;
            $rows = array();
            foreach (explode("\n", $text) as $line) {
                if (trim($line) == "") {
                    continue;
                }
                list($word_count, $term) = explode("\t", $line);
                $tiSeID = $sid; // TiSeID
                $tiCount = $count + 1; // TiCount
                $count += mb_strlen($term);
                if (str_ends_with($term, "\r")) {
                    $term = str_replace("\r", '', $term);
                    $sid++;
                    $count = 0;
                }
                $tiOrder = ++$order; // TiOrder
                $tiWordCount = (int)$word_count; // TiWordCount
                $rows[] = array($tiSeID, $tiCount, $tiOrder, $term, $tiWordCount);
            }

            // Build multi-row INSERT with prepared statement
            if (!empty($rows)) {
                $placeholders = array();
                $flatParams = array();
                foreach ($rows as $row) {
                    $placeholders[] = "(?, ?, ?, ?, ?)";
                    $flatParams = array_merge($flatParams, $row);
                }

                Connection::preparedExecute(
                    "INSERT INTO temptextitems (
                        TiSeID, TiCount, TiOrder, TiText, TiWordCount
                    ) VALUES " . implode(',', $placeholders),
                    $flatParams
                );
            }
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
     *
     * @deprecated Use splitIntoSentences(), parseAndDisplayPreview(), or parseAndSave() instead.
     */
    public static function parseStandard(string $text, int $id, int $lid): ?array
    {
        $settings = self::getLanguageSettings($lid);

        // Return null if language not found
        if ($settings === null) {
            return null;
        }

        // Apply initial transformations (paragraph markers, trim, collapse spaces)
        $text = self::applyInitialTransformations(
            $text,
            $settings['splitEachChar']
        );

        // Preview mode - display HTML BEFORE word splitting
        if ($id == -1) {
            self::displayStandardPreview($text, (bool)$settings['rtlScript']);
        }

        // Apply word-splitting transformations
        $text = self::applyWordSplitting(
            $text,
            $settings['splitSentence'],
            $settings['noSentenceEnd'],
            $settings['termchar']
        );

        // Split-only mode
        if ($id == -2) {
            return self::splitStandardSentences($text, $settings['removeSpaces']);
        }

        // Database insertion (for both preview mode -1 and actual save mode > 0)
        self::parseStandardToDatabase(
            $text,
            $settings['termchar'],
            $settings['removeSpaces'],
            $id > 0
        );

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
     *
     * @deprecated Use splitIntoSentences(), parseAndDisplayPreview(), or parseAndSave() instead.
     */
    public static function prepare(string $text, int $id, int $lid): ?array
    {
        $record = QueryBuilder::table('languages')
            ->where('LgID', '=', $lid)
            ->firstPrepared();

        // Return null if language not found
        if ($record === null) {
            return null;
        }

        $termchar = (string)$record['LgRegexpWordCharacters'];
        $replace = explode("|", (string) $record['LgCharacterSubstitutions']);
        $text = Escaping::prepareTextdata($text);
        //if(is_callable('normalizer_normalize')) $s = normalizer_normalize($s);
        QueryBuilder::table('temptextitems')->truncate();

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
        $wo = $nw = array();
        $sentences = Connection::fetchAll(
            'SELECT GROUP_CONCAT(TiText order by TiOrder SEPARATOR "")
            Sent FROM temptextitems group by TiSeID'
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
            FROM temptextitems
            LEFT JOIN words ON lower(TiText)=WoTextLC AND WoLgID=?"
            . UserScopedQuery::forTablePrepared('words', $bindings, '')
            . " GROUP BY lower(TiText)",
            $bindings
        );
        foreach ($rows as $record) {
            if ($record['len'] == 1) {
                $wo[] = array(
                    \htmlspecialchars($record['word'] ?? '', ENT_QUOTES, 'UTF-8'),
                    $record['cnt'],
                    \htmlspecialchars($record['WoTranslation'] ?? '', ENT_QUOTES, 'UTF-8')
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
     * TiSeID in temptextitems is pre-computed to match future SeID values.
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
        // TiSeID values in temptextitems are pre-computed to match the SeID
        // values these sentences will receive via auto-increment.
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
            FROM temptextitems
            GROUP BY TiSeID
            ORDER BY TiSeID",
            [$lid, $tid]
        );

        // STEP 2: Insert text items. TiSeID directly equals SeID (pre-computed).
        if ($hasmultiword) {
            $bindings = [$lid, $tid, $lid, $lid, $tid, $lid];
            $stmt = Connection::prepare(
                "INSERT INTO textitems2 (
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
                FROM temptextitems
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
                "INSERT INTO textitems2 (
                    Ti2WoID, Ti2LgID, Ti2TxID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text
                )
                SELECT WoID, ?, ?, TiSeID, TiOrder, TiWordCount, TiText
                FROM temptextitems
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
            FROM numbers , temptextitems"
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
     *
     * @psalm-suppress PossiblyUnusedReturnValue Return only used when $id = -2
     *
     * @deprecated Use splitIntoSentences(), parseAndDisplayPreview(), or parseAndSave() instead.
     */
    public static function splitCheck(string $text, string|int $lid, int $id): ?array
    {
        $wl = array();
        $lid = (int) $lid;
        $record = QueryBuilder::table('languages')
            ->select(['LgRightToLeft'])
            ->where('LgID', '=', $lid)
            ->firstPrepared();
        // Just checking if LgID exists with ID should be enough
        if ($record === null) {
            ErrorHandler::die("Language data not found for ID: $lid");
        }
        $rtlScript = $record['LgRightToLeft'];

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
        $rows = QueryBuilder::table('words')
            ->select(['DISTINCT(WoWordCount) as WoWordCount'])
            ->where('WoLgID', '=', $lid)
            ->where('WoWordCount', '>', 1)
            ->getPrepared();
        foreach ($rows as $record) {
            $wl[] = (int)$record['WoWordCount'];
        }
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

        QueryBuilder::table('temptextitems')->truncate();
        return null;
    }
}
