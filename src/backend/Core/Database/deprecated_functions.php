<?php

/**
 * \file
 * \brief Deprecated database functions for backwards compatibility.
 *
 * This file contains deprecated wrapper functions that delegate to the new
 * database classes. These functions are provided for backwards compatibility
 * with existing code. New code should use the classes directly.
 *
 * PHP version 8.1
 *
 * @category Database
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-deprecated-functions.html
 * @since    3.0.0
 *
 * @deprecated 3.0.0 All functions in this file are deprecated. Use the new classes instead.
 */

use Lwt\Core\Globals;
use Lwt\Database\Escaping;
use Lwt\Database\Configuration;
use Lwt\Database\Settings;
use Lwt\Database\Validation;
use Lwt\Database\Maintenance;
use Lwt\Database\TextParsing;
use Lwt\Database\Migrations;

// Ensure core utilities are loaded
require_once __DIR__ . '/../Utils/string_utilities.php';

/**
 * Do a SQL query to the database.
 * It is a wrapper for mysqli_query function.
 *
 * @param string $sql Query using SQL syntax
 *
 * @return mysqli_result|true
 *
 * @deprecated 3.0.0 Use DB::query() or Connection::query() for new code
 */
function do_mysqli_query($sql)
{
    $connection = Globals::getDbConnection();
    $res = mysqli_query($connection, $sql);
    if ($res != false) {
        return $res;
    }

    // Build error message
    $errorMsg = "Fatal Error in SQL Query: " . $sql . "\n" .
                "Error Code & Message: [" . mysqli_errno($connection) . "] " .
                mysqli_error($connection);

    // In testing environment (PHPUnit), throw exception instead of dying
    if (class_exists('PHPUnit\Framework\TestCase', false)) {
        throw new \RuntimeException($errorMsg);
    }

    // In production, output HTML error and die (legacy behavior)
    echo '</select></p></div>
    <div style="padding: 1em; color:red; font-size:120%; background-color:#CEECF5;">' .
    '<p><b>Fatal Error in SQL Query:</b> ' .
    tohtml($sql) .
    '</p>' .
    '<p><b>Error Code &amp; Message:</b> [' .
    mysqli_errno($connection) .
    '] ' .
    tohtml(mysqli_error($connection)) .
    "</p></div><hr /><pre>Backtrace:\n\n";
    debug_print_backtrace();
    echo '</pre><hr />';
    die('</body></html>');
}

/**
 * Run a SQL query, you can specify its behavior and error message.
 *
 * @param string $sql       MySQL query
 * @param string $m         Success phrase to prepend to the number of affected rows
 * @param bool   $sqlerrdie To die on errors (default = TRUE)
 *
 * @return numeric-string Error message if failure, or the number of affected rows
 *
 * @deprecated 3.0.0 Use DB::execute() for new code
 */
function runsql($sql, $m, $sqlerrdie = true): string
{
    $connection = Globals::getDbConnection();
    if ($sqlerrdie) {
        $res = do_mysqli_query($sql);
    } else {
        $res = mysqli_query($connection, $sql);
    }
    if ($res == false) {
        $message = "Error: " . mysqli_error($connection);
    } else {
        $num = mysqli_affected_rows($connection);
        $message = ($m == '') ? (string)$num : $m . ": " . $num;
    }
    return $message;
}


/**
 * Return the record "value" in the first line of the database if found.
 *
 * @param string $sql MySQL query
 *
 * @return float|int|string|null Any returned value from the database.
 *
 * @since 2.6.0-fork Officially return numeric types.
 *
 * @deprecated 3.0.0 Use DB::queryValue() for new code
 */
function get_first_value($sql)
{
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    if ($record !== false && $record !== null && array_key_exists("value", $record)) {
        $d = $record["value"];
    } else {
        $d = null;
    }
    mysqli_free_result($res);
    return $d;
}


/**
 * Replace Windows line return ("\r\n") by Linux ones ("\n").
 *
 * @param string $s Input string
 *
 * @return string Adapted string.
 *
 * @deprecated 3.0.0 Use Escaping::prepareTextdata() for new code
 */
function prepare_textdata($s): string
{
    return Escaping::prepareTextdata($s);
}

/**
 * Prepare text data for JavaScript output.
 *
 * @param string $s Input string
 *
 * @return string Escaped string safe for JavaScript
 *
 * @deprecated 3.0.0 Use Escaping::prepareTextdataJs() for new code
 */
function prepare_textdata_js(string $s): string
{
    return Escaping::prepareTextdataJs($s);
}


/**
 * Prepares a string to be properly recognized as a string by SQL.
 *
 * @param string $data Input string
 *
 * @return string Properly escaped and trimmed string. "NULL" if the input string is empty.
 *
 * @deprecated 3.0.0 Use DB::escapeOrNull() for new code
 */
function convert_string_to_sqlsyntax($data): string
{
    return Escaping::toSqlSyntax($data);
}

/**
 * Prepares a string to be properly recognized as a string by SQL.
 *
 * @param string $data Input string
 *
 * @return string Properly escaped and trimmed string
 *
 * @deprecated 3.0.0 Use DB::escapeString() for new code
 */
function convert_string_to_sqlsyntax_nonull($data): string
{
    return Escaping::toSqlSyntaxNoNull($data);
}

/**
 * Prepares a string to be properly recognized as a string by SQL.
 *
 * @param string $data Input string
 *
 * @return string Properly escaped string
 *
 * @deprecated 3.0.0 Use DB::escapeString() for new code
 */
function convert_string_to_sqlsyntax_notrim_nonull($data): string
{
    return Escaping::toSqlSyntaxNoTrimNoNull($data);
}

/**
 * Convert regular expression to SQL syntax.
 *
 * @param string $input Regular expression pattern
 *
 * @return string SQL-compatible pattern
 *
 * @deprecated 3.0.0 Use Escaping::regexpToSqlSyntax() for new code
 */
function convert_regexp_to_sqlsyntax(string $input): string
{
    return Escaping::regexpToSqlSyntax($input);
}

/**
 * Validate a language ID
 *
 * @param string $currentlang Language ID to validate
 *
 * @return string '' if the language is not valid, $currentlang otherwise
 *
 * @deprecated 3.0.0 Use Validation::language() for new code
 */
function validateLang($currentlang): string
{
    return Validation::language($currentlang);
}

/**
 * Validate a text ID
 *
 * @param string $currenttext Text ID to validate
 *
 * @return string '' if the text is not valid, $currenttext otherwise
 *
 * @deprecated 3.0.0 Use Validation::text() for new code
 */
function validateText(string $currenttext): string
{
    return Validation::text($currenttext);
}

/**
 * Validate a tag ID for a given language.
 *
 * @param string $currenttag Tag ID to validate
 * @param string $currentlang Language ID
 *
 * @return string '' if invalid, $currenttag otherwise
 *
 * @deprecated 3.0.0 Use Validation::tag() for new code
 */
function validateTag(string $currenttag, string $currentlang): string
{
    return Validation::tag($currenttag, $currentlang);
}

/**
 * Validate an archived text tag ID.
 *
 * @param string $currenttag Tag ID to validate
 * @param string $currentlang Language ID
 *
 * @return string '' if invalid, $currenttag otherwise
 *
 * @deprecated 3.0.0 Use Validation::archTextTag() for new code
 */
function validateArchTextTag(string $currenttag, string $currentlang): string
{
    return Validation::archTextTag($currenttag, $currentlang);
}

/**
 * Validate a text tag ID.
 *
 * @param string $currenttag Tag ID to validate
 * @param string $currentlang Language ID
 *
 * @return string '' if invalid, $currenttag otherwise
 *
 * @deprecated 3.0.0 Use Validation::textTag() for new code
 */
function validateTextTag(string $currenttag, string $currentlang): string
{
    return Validation::textTag($currenttag, $currentlang);
}

/**
 * Convert a setting to 0 or 1
 *
 * @param string     $key The input value
 * @param string|int $dft Default value to use, should be convertible to string
 *
 * @return int
 *
 * @psalm-return 0|1
 *
 * @deprecated 3.0.0 Use Settings::getZeroOrOne() for new code
 */
function getSettingZeroOrOne($key, $dft): int
{
    return Settings::getZeroOrOne($key, $dft);
}

/**
 * Get a setting from the database. It can also check for its validity.
 *
 * @param  string $key Setting key. If $key is 'currentlanguage' or
 *                     'currenttext', we validate language/text.
 * @return string $val Value in the database if found, or an empty string
 *
 * @deprecated 3.0.0 Use Settings::get() for new code
 */
function getSetting($key)
{
    return Settings::get($key);
}

/**
 * Get the settings value for a specific key. Return a default value when possible
 *
 * @param string $key Settings key
 *
 * @return string Requested setting, or default value, or ''
 *
 * @deprecated 3.0.0 Use Settings::getWithDefault() for new code
 */
function getSettingWithDefault($key)
{
    return Settings::getWithDefault($key);
}

/**
 * Save the setting identified by a key with a specific value.
 *
 * @param string $k Setting key
 * @param mixed  $v Setting value, will get converted to string
 *
 * @return string Success message (starts by "OK: "), or error message
 *
 * @since 2.9.0 Success message starts by "OK: "
 *
 * @deprecated 3.0.0 Use Settings::save() for new code
 */
function saveSetting(string $k, mixed $v)
{
    return Settings::save($k, $v);
}

/**
 * Check if the _lwtgeneral table exists, create it if not.
 *
 * @deprecated 3.0.0 Use Settings::lwtTableCheck() for new code
 */
function LWTTableCheck(): void
{
    Settings::lwtTableCheck();
}

/**
 * Set a value in the _lwtgeneral table.
 *
 * @param string $key Setting key
 * @param string $val Setting value
 *
 * @deprecated 3.0.0 Use Settings::lwtTableSet() for new code
 */
function LWTTableSet(string $key, string $val): void
{
    Settings::lwtTableSet($key, $val);
}

/**
 * Get a value from the _lwtgeneral table.
 *
 * @param string $key Setting key
 *
 * @return string Setting value or empty string if not found
 *
 * @deprecated 3.0.0 Use Settings::lwtTableGet() for new code
 */
function LWTTableGet(string $key): string
{
    return Settings::lwtTableGet($key);
}

/**
 * Adjust the auto-incrementation in the database.
 *
 * @param string $table Table name (without prefix)
 * @param string $key   Primary key column name
 *
 * @deprecated 3.0.0 Use Maintenance::adjustAutoIncrement() for new code
 */
function adjust_autoincr($table, $key): void
{
    Maintenance::adjustAutoIncrement($table, $key);
}

/**
 * Optimize the database.
 *
 * @deprecated 3.0.0 Use Maintenance::optimizeDatabase() for new code
 */
function optimizedb(): void
{
    Maintenance::optimizeDatabase();
}

/**
 * Update the word count for Japanese language (using MeCab only).
 *
 * @param int $japid Japanese language ID
 *
 * @return void
 *
 * @deprecated 3.0.0 Use Maintenance::updateJapaneseWordCount() for new code
 */
function update_japanese_word_count($japid)
{
    Maintenance::updateJapaneseWordCount($japid);
}

/**
 * Initiate the number of words in terms for all languages.
 *
 * Only terms with a word count set to 0 are changed.
 *
 * @return void
 *
 * @deprecated 3.0.0 Use Maintenance::initWordCount() for new code
 */
function init_word_count(): void
{
    Maintenance::initWordCount();
}

/**
 * Initiate the number of words in terms for all languages
 *
 * Only terms with a word count set to 0 are changed.
 *
 * @return void
 *
 * @global string $tbpref Database table prefix
 *
 * @deprecated Use init_word_count: same effect, but more logical name. Will be
 * removed in version 3.0.0.
 */
function set_word_count()
{
    init_word_count();
}

/**
 * Parse a Japanese text using MeCab and add it to the database.
 *
 * @param string $text Text to parse.
 * @param int    $id   Text ID. If $id = -1 print results,
 *                     if $id = -2 return splitted texts
 *
 * @return null|string[] Splitted sentence if $id = -2
 *
 * @since 2.5.1-fork Works even if LOAD DATA LOCAL INFILE operator is disabled.
 * @since 2.6.0-fork Use PHP instead of SQL, slower but works better.
 *
 * @global string $tbpref Database table prefix
 *
 * @psalm-return non-empty-list<string>|null
 *
 * @deprecated 3.0.0 Use TextParsing::parseJapanese() for new code
 */
function parse_japanese_text($text, $id): ?array
{
    return TextParsing::parseJapanese($text, $id);
}

/**
 * Insert a processed text in the data in pure SQL way.
 *
 * @param string $text Preprocessed text to insert
 * @param int    $id   Text ID
 *
 * @return void
 *
 * @global string $tbpref Database table prefix
 *
 * @deprecated 3.0.0 Use TextParsing::saveWithSql() for new code
 */
function save_processed_text_with_sql($text, $id): void
{
    TextParsing::saveWithSql($text, $id);
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
 * @since 2.5.1-fork Works even if LOAD DATA LOCAL INFILE operator is disabled.
 *
 * @global string $tbpref Database table prefix
 *
 * @psalm-return non-empty-list<string>|null
 *
 * @deprecated 3.0.0 Use TextParsing::parseStandard() for new code
 */
function parse_standard_text($text, $id, $lid): ?array
{
    return TextParsing::parseStandard($text, $id, $lid);
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
 * @global string $tbpref Database table prefix
 *
 * @psalm-return non-empty-list<string>|null
 *
 * @deprecated 3.0.0 Use TextParsing::prepare() for new code
 */
function prepare_text_parsing($text, $id, $lid): ?array
{
    return TextParsing::prepare($text, $id, $lid);
}

/**
 * Echo the sentences in a text. Prepare JS data for words and word count.
 *
 * @param int $lid Language ID
 *
 * @global string $tbpref Database table prefix
 *
 * @return void
 *
 * @deprecated 3.0.0 Use TextParsing::checkValid() for new code
 */
function check_text_valid($lid)
{
    TextParsing::checkValid($lid);
}


/**
 * Append sentences and text items in the database.
 *
 * @param int  $tid          ID of text from which insert data
 * @param int  $lid          ID of the language of the text
 * @param bool $hasmultiword Set to true to insert multi-words as well.
 *
 * @return void
 *
 * @global string $tbpref Database table prefix
 *
 * @deprecated 3.0.0 Use TextParsing::registerSentencesTextItems() for new code
 */
function registerSentencesTextItems($tid, $lid, $hasmultiword)
{
    TextParsing::registerSentencesTextItems($tid, $lid, $hasmultiword);
}

/**
 * Append sentences and text items in the database.
 *
 * @param int    $id   New default text ID
 * @param int    $lid  New default language ID
 * @param string $_sql Unnused since 2.10.0. Will be removed in 3.0.0.
 *
 * @return void
 *
 * @global string $tbpref Database table prefix
 *
 * @deprecated Since 2.10.0, use registerSentencesTextItems instead
 */
function update_default_values($id, $lid, $_sql)
{
    $tbpref = Globals::getTablePrefix();
    $hasmultiword = false;

    // Get multi-word count
    $res = do_mysqli_query(
        "SELECT DISTINCT(WoWordCount)
        FROM {$tbpref}words
        WHERE WoLgID = $lid AND WoWordCount > 1"
    );
    // Text has multi-words
    if (mysqli_fetch_assoc($res) !== false) {
        $hasmultiword = true;
    }
    mysqli_free_result($res);
    registerSentencesTextItems($id, $lid, $hasmultiword);
}


/**
 * Display statistics about a text.
 *
 * @param int  $lid        Language ID
 * @param bool $rtlScript  true if language is right-to-left
 * @param bool $multiwords Display if text has multi-words
 *
 * @return void
 *
 * @deprecated 3.0.0 Use TextParsing::displayStatistics() for new code
 */
function displayTextStatistics($lid, $rtlScript, $multiwords)
{
    TextParsing::displayStatistics($lid, $rtlScript, $multiwords);
}

/**
 * Check a language that contains expressions.
 *
 * @param int[] $wl All the different expression length in the language.
 *
 * @global string $tbpref Database table prefix
 *
 * @deprecated 3.0.0 Use TextParsing::checkExpressions() for new code
 */
function checkExpressions($wl): void
{
    TextParsing::checkExpressions($wl);
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
 * @global string $tbpref Database table prefix
 *
 * @return null|string[] The sentence array if $id = -2
 *
 * @psalm-return non-empty-list<string>|null
 *
 * @deprecated 3.0.0 Use TextParsing::splitCheck() for new code
 */
function splitCheckText($text, $lid, $id)
{
    return TextParsing::splitCheck($text, $lid, $id);
}


/**
 * Reparse all texts in order.
 *
 * @global string $tbpref Database table prefix
 *
 * @deprecated 3.0.0 Use Migrations::reparseAllTexts() for new code
 */
function reparse_all_texts(): void
{
    Migrations::reparseAllTexts();
}

/**
 * Update the database if it is using an outdate version.
 *
 * @return void
 *
 * @since 2.10.0-fork Migrations are defined thourgh SQL, and not directly here
 * @since 3.0.0 Parameters removed in favor of Globals
 *
 * @deprecated 3.0.0 Use Migrations::update() for new code
 */
function update_database(): void
{
    Migrations::update();
}


/**
 * Add a prefix to table in a SQL query string.
 *
 * @param string $sql_line SQL string to prefix.
 * @param string $prefix   Prefix to add
 *
 * @deprecated 3.0.0 Use Migrations::prefixQuery() for new code
 */
function prefixSQLQuery($sql_line, $prefix): string
{
    return Migrations::prefixQuery($sql_line, $prefix);
}

/**
 * Check and/or update the database.
 *
 * @since 2.10.0 Use confiduration files instead of containing all the data.
 * @since 3.0.0 Parameters removed in favor of Globals
 *
 * @deprecated 3.0.0 Use Migrations::checkAndUpdate() for new code
 */
function check_update_db(): void
{
    Migrations::checkAndUpdate();
}


/**
 * Make the connection to the database.
 *
 * @param string $server Server name
 * @param string $userid Database user ID
 * @param string $passwd User password
 * @param string $dbname Database name
 * @param string $socket Database socket
 *
 * @return mysqli Connection to the database
 *
 * @psalm-suppress UndefinedDocblockClass
 *
 * @since 2.6.0-fork Use mysqli_init and mysql_real_connect instead of deprecated mysql_connect
 * @since 2.6.0-fork Tries to allow local infiles for the connection.
 * @since 2.9.0 Can accept a $socket as an optional argument
 *
 * @deprecated 3.0.0 Use Configuration::connect() for new code
 */
function connect_to_database($server, $userid, $passwd, $dbname, $socket = "")
{
    return Configuration::connect($server, $userid, $passwd, $dbname, $socket);
}

/**
 * Get the prefixes for the database.
 *
 * Is $tbpref set in .env? Take it and $fixed_tbpref=1.
 * If not: $fixed_tbpref=0. Is it set in table "_lwtgeneral"? Take it.
 * If not: Use $tbpref = '' (no prefix, old/standard behaviour).
 *
 * @param \mysqli $dbconnection Database connection
 *
 * @return (bool|string)[]
 *
 * @psalm-return list{string, bool}
 *
 * @deprecated 3.0.0 Use Configuration::getPrefix() for new code
 */
function getDatabasePrefix($dbconnection): array
{
    return Configuration::getPrefix($dbconnection);
}

/**
 * Return the last inserted ID in the database
 *
 * @return int
 *
 * @since 2.6.0-fork Officially returns a int in documentation, as it was the case
 */
function get_last_key()
{
    return (int)get_first_value('SELECT LAST_INSERT_ID() AS value');
}

/**
 * Return all different database prefixes that are in use.
 *
 * @return string[] A list of prefixes.
 *
 * @psalm-return list<string>
 */
function getprefixes(): array
{
    $prefix = array();
    $res = do_mysqli_query(
        str_replace(
            '_',
            "\\_",
            "SHOW TABLES LIKE " . convert_string_to_sqlsyntax_nonull('%_settings')
        )
    );
    while ($row = mysqli_fetch_row($res)) {
        $prefix[] = substr((string) $row[0], 0, -9);
    }
    mysqli_free_result($res);
    return $prefix;
}

/**
 * Get the prefixes for the database.
 *
 * Is $tbpref set in .env? Take it and $fixed_tbpref=1.
 * If not: $fixed_tbpref=0. Is it set in table "_lwtgeneral"? Take it.
 * If not: Use $tbpref = '' (no prefix, old/standard behaviour).
 *
 * @param string|null $tbpref Temporary database table prefix
 *
 * @return 0|1 Table Prefix is fixed, no changes possible.
 *
 * @deprecated Since 2.10.0-fork, use getDatabasePrefix instead
 */
function get_database_prefixes(&$tbpref)
{
    list($tbpref, $fixed_tbpref) = getDatabasePrefix(Globals::getDbConnection());
    return (int) $fixed_tbpref;
}

/**
 * Check a text and display statistics about it.
 *
 * @param string $sql
 * @param bool   $rtlScript true if language is right-to-left
 * @param int[]  $wl        Words lengths
 *
 * @return void
 *
 * @deprecated Use displayTextStatistics instead. Will be removed in 3.0.0.
 */
function check_text($sql, $rtlScript, $wl)
{
    $mw = array();
    if (!empty($wl)) {
        $res = do_mysqli_query($sql);
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
        let h='<h4>Word List <span class="red2">(red = already saved)</span></h4>' +
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
 * @param int    $id     Text ID
 * @param int    $lid    Language ID
 * @param int[]  $wl     Word length
 * @param int    $wl_max Maximum word length
 * @param string $mw_sql SQL-formatted string
 *
 * @return string SQL-formatted query string
 *
 * @global string $tbpref Database table prefix
 *
 * @deprecated Since 2.10.0-fork use checkExpressions. It does not modify SQL globals.
 */
function check_text_with_expressions($id, $lid, $wl, $wl_max, $mw_sql): string
{
    $tbpref = Globals::getTablePrefix();

    $set_wo_sql = $set_wo_sql_2 = $del_wo_sql = $init_var = '';
    do_mysqli_query('SET GLOBAL max_heap_table_size = 1024 * 1024 * 1024 * 2');
    do_mysqli_query('SET GLOBAL tmp_table_size = 1024 * 1024 * 1024 * 2');
    // For all possible multi-words length,
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
    if ($id > 0) {
        $sql = 'SELECT straight_join WoID, sent, TiOrder - (2*(n-1)) TiOrder,
        n TiWordCount,word';
    } else {
        $sql = 'SELECT straight_join count(WoID) cnt, n as len,
        lower(WoText) as word, WoTranslation';
    }
    $sql .=
    " FROM (
        SELECT straight_join
        if(@se_id=TiSeID and @ti_or=TiOrder,
            if((@ti_or:=TiOrder+@a0) is null,TiSeID,TiSeID),
            if(
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
        n FROM {$tbpref}numbers , {$tbpref}temptextitems
    ) ti,
    {$tbpref}words
    WHERE lword IS NOT NULL AND WoLgID=$lid AND
    WoTextLC=lword AND WoWordCount=n";
    $sql .= ($id > 0) ? ' UNION ALL ' : ' GROUP BY WoID ORDER BY WoTextLC';
    return $sql;
}
