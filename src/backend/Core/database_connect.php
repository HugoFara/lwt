<?php

/**
 * \file
 * \brief Connects to the database and check its state.
 *
 * PHP version 8.1
 *
 * @category Database
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-database-connect.html
 */

require_once __DIR__ . "/kernel_utility.php";
require_once __DIR__ . "/EnvLoader.php";

use Lwt\Core\LWT_Globals;
use Lwt\Core\EnvLoader;

/**
 * Load database configuration from .env file.
 *
 * @return void Sets $DB_SERVER, $DB_USERID, $DB_PASSWD, $DB_NAME, $DB_SOCKET, $tbpref globals
 *
 * @since 3.0.0
 */
function loadDatabaseConfiguration(): void
{
    // Define variables in global scope (uppercase for globals)
    global $DB_SERVER, $DB_USERID, $DB_PASSWD, $DB_NAME, $DB_SOCKET, $tbpref;

    $envPath = __DIR__ . "/../../../.env";

    // Load .env file
    if (EnvLoader::load($envPath)) {
        $config = EnvLoader::getDatabaseConfig();
        $DB_SERVER = $config['server'];
        $DB_USERID = $config['userid'];
        $DB_PASSWD = $config['passwd'];
        $DB_NAME = $config['dbname'];
        $DB_SOCKET = $config['socket'];
        // Only set $tbpref if explicitly configured in .env
        if ($config['tbpref'] !== null) {
            $tbpref = $config['tbpref'];
        }
        return;
    }

    // No configuration found - use defaults, connection function will show proper error
    $DB_SERVER = 'localhost';
    $DB_USERID = 'root';
    $DB_PASSWD = '';
    $DB_NAME = 'learning-with-texts';
    $DB_SOCKET = '';
}

// Load configuration
loadDatabaseConfiguration();

/**
 * Do a SQL query to the database.
 * It is a wrapper for mysqli_query function.
 *
 * @param string $sql Query using SQL syntax
 *
 * @global mysqli $DBCONNECTION Connection to the database
 *
 * @return mysqli_result|true
 */
function do_mysqli_query($sql)
{
    // @deprecated 3.0.0 Use LWT_Globals::getDbConnection() instead of global $DBCONNECTION
    global $DBCONNECTION;
    $res = mysqli_query($DBCONNECTION, $sql);
    if ($res != false) {
        return $res;
    }
    echo '</select></p></div>
    <div style="padding: 1em; color:red; font-size:120%; background-color:#CEECF5;">' .
    '<p><b>Fatal Error in SQL Query:</b> ' .
    tohtml($sql) .
    '</p>' .
    '<p><b>Error Code &amp; Message:</b> [' .
    mysqli_errno($DBCONNECTION) .
    '] ' .
    tohtml(mysqli_error($DBCONNECTION)) .
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
 * @return string Error message if failure, or the number of affected rows
 */
function runsql($sql, $m, $sqlerrdie = true): string
{
    if ($sqlerrdie) {
        $res = do_mysqli_query($sql);
    } else {
        $res = mysqli_query($GLOBALS['DBCONNECTION'], $sql);
    }
    if ($res == false) {
        $message = "Error: " . mysqli_error($GLOBALS['DBCONNECTION']);
    } else {
        $num = mysqli_affected_rows($GLOBALS['DBCONNECTION']);
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
 */
function get_first_value($sql)
{
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    if ($record && array_key_exists("value", $record)) {
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
 */
function prepare_textdata($s): string
{
    return str_replace("\r\n", "\n", $s);
}

// -------------------------------------------------------------

function prepare_textdata_js($s): string
{
    $s = convert_string_to_sqlsyntax($s);
    if ($s == "NULL") {
        return "''";
    }
    return str_replace("'", "\\'", $s);
}


/**
 * Prepares a string to be properly recognized as a string by SQL.
 *
 * @param string $data Input string
 *
 * @return string Properly escaped and trimmed string. "NULL" if the input string is empty.
 *
 * @global $DBCONNECTION
 *
 * @deprecated-global 3.0.0 The global $DBCONNECTION usage is deprecated.
 *                          Use LWT_Globals::getDbConnection() for new code.
 */
function convert_string_to_sqlsyntax($data): string
{
    // @deprecated 3.0.0 Use LWT_Globals::getDbConnection() instead of global $DBCONNECTION
    global $DBCONNECTION;
    $result = "NULL";
    $data = trim(prepare_textdata($data));
    if ($data != "") {
        $result = "'".mysqli_real_escape_string($DBCONNECTION, $data)."'";
    }
    return $result;
}

/**
 * Prepares a string to be properly recognized as a string by SQL.
 *
 * @param string $data Input string
 *
 * @return string Properly escaped and trimmed string
 */
function convert_string_to_sqlsyntax_nonull($data): string
{
    $data = trim(prepare_textdata($data));
    return  "'" . mysqli_real_escape_string($GLOBALS['DBCONNECTION'], $data) . "'";
}

/**
 * Prepares a string to be properly recognized as a string by SQL.
 *
 * @param string $data Input string
 *
 * @return string Properly escaped string
 */
function convert_string_to_sqlsyntax_notrim_nonull($data): string
{
    return "'" .
    mysqli_real_escape_string($GLOBALS['DBCONNECTION'], prepare_textdata($data)) .
    "'";
}

// -------------------------------------------------------------

function convert_regexp_to_sqlsyntax($input): string
{
    $output = preg_replace_callback(
        "/\\\\x\{([\da-z]+)\}/ui",
        function ($a) {
            $num = $a[1];
            $dec = hexdec($num);
            return "&#$dec;";
        },
        preg_replace(
            array('/\\\\(?![-xtfrnvup])/u','/(?<=[[^])[\\\\]-/u'),
            array('','-'),
            $input
        )
    );
    return convert_string_to_sqlsyntax_nonull(
        html_entity_decode($output, ENT_NOQUOTES, 'UTF-8')
    );
}

/**
 * Validate a language ID
 *
 * @param string $currentlang Language ID to validate
 *
 * @return string '' if the language is not valid, $currentlang otherwise
 *
 * @global string $tbpref Table name prefix
 *
 * @deprecated-global 3.0.0 The global $tbpref usage is deprecated.
 *                          Use LWT_Globals::getTablePrefix() for new code.
 */
function validateLang($currentlang): string
{
    // @deprecated 3.0.0 Use LWT_Globals::getTablePrefix() instead of global $tbpref
    global $tbpref;
    if ($currentlang == '' || !is_numeric($currentlang)) {
        return '';
    }
    // Cast to integer for safety against SQL injection
    $currentlang_int = (int)$currentlang;
    $sql_string = 'SELECT count(LgID) AS value
    FROM ' . $tbpref . 'languages
    WHERE LgID=' . $currentlang_int;
    if (get_first_value($sql_string) == 0) {
        return '';
    }
    return (string)$currentlang_int;
}

/**
 * Validate a text ID
 *
 * @param string $currenttext Text ID to validate
 *
 * @return string '' if the text is not valid, $currenttext otherwise
 *
 * @global string $tbpref Table name prefix
 */
function validateText($currenttext): string
{
    global $tbpref;
    if ($currenttext == '' || !is_numeric($currenttext)) {
        return '';
    }
    // Cast to integer for safety against SQL injection
    $currenttext_int = (int)$currenttext;
    $sql_string = 'SELECT count(TxID) AS value
    FROM ' . $tbpref . 'texts WHERE TxID=' .
    $currenttext_int;
    if (get_first_value($sql_string) == 0) {
        return '';
    }
    return (string)$currenttext_int;
}

// -------------------------------------------------------------

function validateTag($currenttag,$currentlang)
{
    global $tbpref;
    if ($currenttag != '' && $currenttag != -1) {
        // Sanitize inputs to prevent SQL injection
        if (!is_numeric($currenttag)) {
            return '';
        }
        $currenttag_int = (int)$currenttag;

        $lang_condition = '';
        if ($currentlang != '') {
            if (!is_numeric($currentlang)) {
                return '';
            }
            $currentlang_int = (int)$currentlang;
            $lang_condition = " AND WoLgID = " . $currentlang_int;
        }

        $sql = "SELECT (
            " . $currenttag_int . " IN (
                SELECT TgID
                FROM {$tbpref}words, {$tbpref}tags, {$tbpref}wordtags
                WHERE TgID = WtTgID AND WtWoID = WoID" .
                $lang_condition .
                " group by TgID order by TgText
            )
        ) AS value";
        /*if ($currentlang == '') {
            $sql = "SELECT (
                $currenttag in (
                    select TgID from {$tbpref}words,
                    {$tbpref}tags,
                    {$tbpref}wordtags
                    where TgID = WtTgID and WtWoID = WoID
                    group by TgID
                    order by TgText
                    )
                ) as value";
        } else {
            $sql = "SELECT (
                $currenttag in (
                    select TgID
                    from {$tbpref}words, {$tbpref}tags,
                    {$tbpref}wordtags
                    where TgID = WtTgID and WtWoID = WoID and WoLgID = $currentlang
                    group by TgID order by TgText
                )
                ) as value";
        }*/
        $r = get_first_value($sql);
        if ($r == 0) {
            $currenttag = '';
        }
    }
    return $currenttag;
}

// -------------------------------------------------------------

function validateArchTextTag($currenttag,$currentlang)
{
    global $tbpref;
    if ($currenttag != '' && $currenttag != -1) {
        // Sanitize inputs to prevent SQL injection
        if (!is_numeric($currenttag)) {
            return '';
        }
        $currenttag_int = (int)$currenttag;

        if ($currentlang == '') {
            $sql = "select (
                " . $currenttag_int . " in (
                    select T2ID
                    from {$tbpref}archivedtexts,
                    {$tbpref}tags2,
                    {$tbpref}archtexttags
                    where T2ID = AgT2ID and AgAtID = AtID
                    group by T2ID order by T2Text
                )
            ) as value";
        }
        else {
            if (!is_numeric($currentlang)) {
                return '';
            }
            $currentlang_int = (int)$currentlang;
            $sql = "select (
                " . $currenttag_int . " in (
                    select T2ID
                    from {$tbpref}archivedtexts,
                    {$tbpref}tags2,
                    {$tbpref}archtexttags
                    where T2ID = AgT2ID and AgAtID = AtID and AtLgID = " . $currentlang_int . "
                    group by T2ID order by T2Text
                )
            ) as value";
        }
        $r = get_first_value($sql);
        if ($r == 0 ) {
            $currenttag = '';
        }
    }
    return $currenttag;
}

// -------------------------------------------------------------

function validateTextTag($currenttag,$currentlang)
{
    global $tbpref;
    if ($currenttag != '' && $currenttag != -1) {
        if ($currentlang == '') {
            $sql = "select (
                $currenttag in (
                    select T2ID
                    from {$tbpref}texts, {$tbpref}tags2, {$tbpref}texttags
                    where T2ID = TtT2ID and TtTxID = TxID
                    group by T2ID
                    order by T2Text
                )
            ) as value";
        } else {
            $sql = "select (
                $currenttag in (
                    select T2ID
                    from {$tbpref}texts, {$tbpref}tags2, {$tbpref}texttags
                    where T2ID = TtT2ID and TtTxID = TxID and TxLgID = $currentlang
                    group by T2ID order by T2Text
                )
            ) as value";
        }
        $r = get_first_value($sql);
        if ($r == 0 ) {
            $currenttag = '';
        }
    }
    return $currenttag;
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
 */
function getSettingZeroOrOne($key, $dft): int
{
    $r = getSetting($key);
    $r = ($r == '' ? $dft : (((int)$r !== 0) ? 1 : 0));
    return (int)$r;
}

/**
 * Get a setting from the database. It can also check for its validity.
 *
 * @param  string $key Setting key. If $key is 'currentlanguage' or
 *                     'currenttext', we validate language/text.
 * @return string $val Value in the database if found, or an empty string
 * @global string $tbpref Table name prefix
 *
 * @deprecated-global 3.0.0 The global $tbpref usage is deprecated.
 *                          Use LWT_Globals::getTablePrefix() for new code.
 */
function getSetting($key)
{
    // @deprecated 3.0.0 Use LWT_Globals::getTablePrefix() instead of global $tbpref
    global $tbpref;
    $val = get_first_value(
        'SELECT StValue AS value
        FROM ' . $tbpref . 'settings
        WHERE StKey = ' . convert_string_to_sqlsyntax($key)
    );
    if (isset($val)) {
        $val = trim((string) $val);
        if ($key == 'currentlanguage') {
            $val = validateLang($val);
        }
        if ($key == 'currenttext') {
            $val = validateText($val);
        }
        return $val;
    }
    return '';
}

/**
 * Get the settings value for a specific key. Return a default value when possible
 *
 * @param string $key Settings key
 *
 * @return string Requested setting, or default value, or ''
 *
 * @global string $tbpref Table name prefix
 */
function getSettingWithDefault($key)
{
    global $tbpref;
    $dft = get_setting_data();
    $val = (string) get_first_value(
        'SELECT StValue AS value
         FROM ' . $tbpref . 'settings
         WHERE StKey = ' . convert_string_to_sqlsyntax($key)
    );
    if ($val != '') {
        return trim($val);
    }
    if (isset($dft[$key])) {
        return $dft[$key]['dft'];
    }
    return '';

}

/**
 * Save the setting identified by a key with a specific value.
 *
 * @param string $k Setting key
 * @param mixed  $v Setting value, will get converted to string
 *
 * @global string $tbpref Table name prefix
 *
 * @return string Success message (starts by "OK: "), or error message
 *
 * @since 2.9.0 Success message starts by "OK: "
 */
function saveSetting($k, $v)
{
    global $tbpref;
    $dft = get_setting_data();
    if (!isset($v)) {
        return 'Value is not set!';
    }
    if ($v === '') {
        return 'Value is an empty string!';
    }
    runsql(
        "DELETE FROM {$tbpref}settings
        WHERE StKey = " . convert_string_to_sqlsyntax($k),
        ''
    );
    if (isset($dft[$k]) && $dft[$k]['num']) {
        $v = (int)$v;
        if ($v < $dft[$k]['min']) {
            $v = $dft[$k]['dft'];
        }
        if ($v > $dft[$k]['max']) {
            $v = $dft[$k]['dft'];
        }
    }
    $dum = runsql(
        "INSERT INTO {$tbpref}settings (StKey, StValue) VALUES(" .
        convert_string_to_sqlsyntax($k) . ', ' .
        convert_string_to_sqlsyntax($v) . ')',
        ''
    );
    if (is_numeric($dum)) {
        return "OK: $dum rows changed";
    }
    return $dum;
}

/**
 * Check if the _lwtgeneral table exists, create it if not.
 */
function LWTTableCheck(): void
{
    if (mysqli_num_rows(do_mysqli_query("SHOW TABLES LIKE '\\_lwtgeneral'")) == 0) {
        runsql(
            "CREATE TABLE IF NOT EXISTS _lwtgeneral (
                LWTKey varchar(40) NOT NULL,
                LWTValue varchar(40) DEFAULT NULL,
                PRIMARY KEY (LWTKey)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8",
            ''
        );
        if (mysqli_num_rows(
            do_mysqli_query("SHOW TABLES LIKE '\\_lwtgeneral'")
        ) == 0
        ) {
            my_die("Unable to create table '_lwtgeneral'!");
        }
    }
}

// -------------------------------------------------------------

function LWTTableSet($key, $val): void
{
    LWTTableCheck();
    runsql(
        "INSERT INTO _lwtgeneral (LWTKey, LWTValue) VALUES (
            " . convert_string_to_sqlsyntax($key) . ",
            " . convert_string_to_sqlsyntax($val) . "
        ) ON DUPLICATE KEY UPDATE LWTValue = " . convert_string_to_sqlsyntax($val),
        ''
    );
}

// -------------------------------------------------------------

function LWTTableGet($key): string
{
    LWTTableCheck();
    return (string)get_first_value(
        "SELECT LWTValue as value
        FROM _lwtgeneral
        WHERE LWTKey = " . convert_string_to_sqlsyntax($key)
    );
}

/**
 * Adjust the auto-incrementation in the database.
 *
 * @global string $tbpref Database table prefix
 */
function adjust_autoincr($table, $key): void
{
    global $tbpref;
    $val = get_first_value(
        'SELECT max(' . $key .')+1 AS value FROM ' . $tbpref . $table
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
 * @global string $trbpref Table prefix
 */
function optimizedb(): void
{
    global $tbpref;
    adjust_autoincr('archivedtexts', 'AtID');
    adjust_autoincr('languages', 'LgID');
    adjust_autoincr('sentences', 'SeID');
    adjust_autoincr('texts', 'TxID');
    adjust_autoincr('words', 'WoID');
    adjust_autoincr('tags', 'TgID');
    adjust_autoincr('tags2', 'T2ID');
    adjust_autoincr('newsfeeds', 'NfID');
    adjust_autoincr('feedlinks', 'FlID');
    $sql =
    'SHOW TABLE STATUS
    WHERE Engine IN ("MyISAM","Aria") AND (
        (Data_free / Data_length > 0.1 AND Data_free > 102400) OR Data_free > 1048576
    ) AND Name';
    if(empty($tbpref)) {
        $sql.= " NOT LIKE '\_%'";
    }
    else {
        $sql.= " LIKE " . convert_string_to_sqlsyntax(rtrim($tbpref, '_')) . "'\_%'";
    }
    $res = do_mysqli_query($sql);
    while($row = mysqli_fetch_assoc($res)) {
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
 *
 * @global string $tbpref Database table prefix.
 */
function update_japanese_word_count($japid)
{
    global $tbpref;

    // STEP 1: write the useful info to a file
    $db_to_mecab = tempnam(sys_get_temp_dir(), "{$tbpref}db_to_mecab");
    $mecab_args = ' -F %m%t\\t -U %m%t\\t -E \\n ';
    $mecab = get_mecab_path($mecab_args);

    $sql = "SELECT WoID, WoTextLC FROM {$tbpref}words
    WHERE WoLgID = $japid AND WoWordCount = 0";
    $res = do_mysqli_query($sql);
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
        if (!empty($arr[1])) {
            //TODO Add tests
            $cnt = substr_count(
                preg_replace('$[^2678]\t$u', '', $arr[1]),
                "\t"
            );
            if (empty($cnt)) {
                $cnt = 1;
            }
            $values[] = "(" . convert_string_to_sqlsyntax($arr[0]) . ", $cnt)";
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
 *
 * @global string $tbpref Database table prefix
 */
function init_word_count(): void
{
    global $tbpref;
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

    if ($japid) {
        update_japanese_word_count((int)$japid);
    }
    $sql = "SELECT WoID, WoTextLC, LgRegexpWordCharacters, LgSplitEachChar
    FROM {$tbpref}words, {$tbpref}languages
    WHERE WoWordCount = 0 AND WoLgID = LgID
    ORDER BY WoID";
    $result = do_mysqli_query($sql);
    while ($rec = mysqli_fetch_assoc($result)){
        if ($rec['LgSplitEachChar']) {
            $textlc = preg_replace('/([^\s])/u', "$1 ", $rec['WoTextLC']);
        } else {
            $textlc = $rec['WoTextLC'];
        }
        $sqlarr[]= ' WHEN ' . $rec['WoID'] . '
        THEN ' . preg_match_all(
            '/([' . $rec['LgRegexpWordCharacters'] . ']+)/u', $textlc, $ma
        );
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
 */
function parse_japanese_text($text, $id): ?array
{
    global $tbpref;
    $text = preg_replace('/[ \t]+/u', ' ', $text);
    $text = trim($text);
    if ($id == -1) {
        echo '<div id="check_text" style="margin-right:50px;">
        <h2>Text</h2>
        <p>' . str_replace("\n", "<br /><br />", tohtml($text)). '</p>';
    } else if ($id == -2) {
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
        } else if (in_array($node_type, ['2', '6', '7', '8'])) {
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
        $row[3] = convert_string_to_sqlsyntax_notrim_nonull($term); // TiText
        $row[4] = $term_type == 0 ? 1 : 0; // TiWordCount
        $values[] = $row;
        // Special case for kazu (numbers)
        if ($last_node_type == 8 && $node_type == 8) {
            $lastKey = array_key_last($values);
            if ($lastKey !== null) {
                // Concatenate the previous value with the current term
                $values[$lastKey-1][3] = convert_string_to_sqlsyntax_notrim_nonull(
                    str_replace("'", '', $values[$lastKey-1][3]) . $term
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
        $formatted_string[$key] =  "(" . implode(",", $value) . ")";
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
 *
 * @global string $tbpref Database table prefix
 */
function save_processed_text_with_sql($text, $id): void
{
    global $tbpref;
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
    $sql = "LOAD DATA LOCAL INFILE " . convert_string_to_sqlsyntax($file_name) ."
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
    do_mysqli_query($sql);
    unlink($file_name);
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
 */
function parse_standard_text($text, $id, $lid): ?array
{
    global $tbpref;
    $sql = "SELECT * FROM {$tbpref}languages WHERE LgID=$lid";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    mysqli_free_result($res);

    // Return null if language not found
    if (!$record) {
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
    if ($record['LgSplitEachChar']) {
        $text = preg_replace('/([^\s])/u', "$1\t", $text);
    }
    $text = preg_replace('/\s+/u', ' ', $text);
    if ($id == -1) {
        echo "<div id=\"check_text\" style=\"margin-right:50px;\">
        <h4>Text</h4>
        <p " .  ($rtlScript ? 'dir="rtl"' : '') . ">" .
        str_replace("¶", "<br /><br />", tohtml($text)) .
        "</p>";
    }
    // "\r" => Sentence delimiter, "\t" and "\n" => Word delimiter
    $text = preg_replace_callback(
        "/(\S+)\s*((\.+)|([$splitSentence]))([]'`\"”)‘’‹›“„«»』」]*)(?=(\s*)(\S+|$))/u",
        // Arrow functions were introduced in PHP 7.4
        //fn ($matches) => find_latin_sentence_end($matches, $noSentenceEnd)
        function ($matches) use ($noSentenceEnd) {
            return find_latin_sentence_end($matches, $noSentenceEnd);
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
                array("\r\r","\t","\n"), array("\r","",""), $text
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
            str_replace(array("\t","\n\n"), array("\n",""), $text)
        )
    );
    $text = remove_spaces(
        preg_replace("/(\n|^)(?!1\t)/u", "\n0\t", $text), $removeSpaces
    );
    // It is faster to write to a file and let SQL do its magic, but may run into
    // security restrictions
    $use_local_infile = false;
    if (!in_array(
        get_first_value("SELECT @@GLOBAL.local_infile as value"),
        array(1, '1', 'ON')
    )
    ) {
        $use_local_infile = false;
    }
    if ($use_local_infile) {
        save_processed_text_with_sql($text, $id);
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
            $row[3] = convert_string_to_sqlsyntax_notrim_nonull($term); // TiText
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
 * @global string $tbpref Database table prefix
 *
 * @psalm-return non-empty-list<string>|null
 */
function prepare_text_parsing($text, $id, $lid): ?array
{
    global $tbpref;
    $sql = "SELECT * FROM {$tbpref}languages WHERE LgID = $lid";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    mysqli_free_result($res);

    // Return null if language not found
    if (!$record) {
        return null;
    }

    $termchar = (string)$record['LgRegexpWordCharacters'];
    $replace = explode("|", (string) $record['LgCharacterSubstitutions']);
    $text = prepare_textdata($text);
    //if(is_callable('normalizer_normalize')) $s = normalizer_normalize($s);
    do_mysqli_query('TRUNCATE TABLE ' . $tbpref . 'temptextitems');

    // because of sentence special characters
    $text = str_replace(array('}','{'), array(']','['), $text);
    foreach ($replace as $value) {
        $fromto = explode("=", trim($value));
        if (count($fromto) >= 2) {
            $text = str_replace(trim($fromto[0]), trim($fromto[1]), $text);
        }
    }

    if ('MECAB' == strtoupper(trim($termchar))) {
        return parse_japanese_text($text, $id);
    }
    return parse_standard_text($text, $id, $lid);
}

/**
 * Echo the sentences in a text. Prepare JS data for words and word count.
 *
 * @param int $lid Language ID
 *
 * @global string $tbpref Database table prefix
 *
 * @return void
 */
function check_text_valid($lid)
{
    global $tbpref;
    $wo = $nw = array();
    $res = do_mysqli_query(
        'SELECT GROUP_CONCAT(TiText order by TiOrder SEPARATOR "")
        Sent FROM ' . $tbpref . 'temptextitems group by TiSeID'
    );
    echo '<h4>Sentences</h4><ol>';
    while($record = mysqli_fetch_assoc($res)){
        echo "<li>" . tohtml($record['Sent']) . "</li>";
    }
    mysqli_free_result($res);
    echo '</ol>';
    $res = do_mysqli_query(
        "SELECT count(`TiOrder`) cnt, if(0=TiWordCount,0,1) as len,
        LOWER(TiText) as word, WoTranslation
        FROM {$tbpref}temptextitems
        LEFT JOIN {$tbpref}words ON lower(TiText)=WoTextLC AND WoLgID=$lid
        GROUP BY lower(TiText)"
    );
    while ($record = mysqli_fetch_assoc($res)) {
        if ($record['len']==1) {
            $wo[]= array(
                tohtml($record['word']),
                $record['cnt'],
                tohtml($record['WoTranslation'])
            );
        } else{
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
 * @param int    $tid          ID of text from which insert data
 * @param int    $lid          ID of the language of the text
 * @param bool   $hasmultiword Set to true to insert multi-words as well.
 *
 * @return void
 *
 * @global string $tbpref Database table prefix
 */
function registerSentencesTextItems($tid, $lid, $hasmultiword)
{
    global $tbpref;

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
    global $tbpref;
    $hasmultiword = false;

    // Get multi-word count
    $res = do_mysqli_query(
        "SELECT DISTINCT(WoWordCount)
        FROM {$tbpref}words
        WHERE WoLgID = $lid AND WoWordCount > 1"
    );
    // Text has multi-words
    if (mysqli_fetch_assoc($res)){
        $hasmultiword = true;
    }
    mysqli_free_result($res);
    return registerSentencesTextItems($id, $lid, $hasmultiword);
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
 * @global $tbpref
 */
function displayTextStatistics($lid, $rtlScript, $multiwords)
{
    global $tbpref;

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
        while ($record = mysqli_fetch_assoc($res)){
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
                h += '<li><span' + (v[2]==""?"":' class="red2"') + '>[' + v[0] + '] — '
                + v[1] + (v[2]==""?"":' — ' + v[2]) + '</span></li>';
            }
        );
        h += '</ul><p>TOTAL: ' + WORDS.length
        + '</p><h4>Expression List</span></h4><ul class="expressionlist">';
        $.each(MWORDS, function (k,v) {
            h+= '<li><span>[' + v[0] + '] — ' + v[1] +
            (v[2]==""?"":' — ' + v[2]) + '</span></li>';
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
    if(!empty($wl)) {
        $res = do_mysqli_query($sql);
        while($record = mysqli_fetch_assoc($res)){
            $mw[]= array(
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
                h += '<li><span' + (v[2]==""?"":' class="red2"') + '>[' + v[0] + '] — '
                + v[1] + (v[2]==""?"":' — ' + v[2]) + '</span></li>';
            }
            );
        h += '</ul><p>TOTAL: ' + WORDS.length
        + '</p><h4>Expression List</span></h4><ul class="expressionlist">';
        $.each(MWORDS, function (k,v) {
            h+= '<li><span>[' + v[0] + '] — ' + v[1] +
            (v[2]==""?"":' — ' + v[2]) + '</span></li>';
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
 * @global string $tbpref Database table prefix
 */
function checkExpressions($wl): void
{
    global $tbpref;

    $wl_max = 0;
    $mw_sql = '';
    foreach ($wl as $word_length){
        if ($wl_max < $word_length) {
            $wl_max = $word_length;
        }
        $mw_sql .= ' WHEN ' . $word_length .
        ' THEN @a' . ($word_length * 2 - 1);
    }
    $set_wo_sql = $set_wo_sql_2 = $del_wo_sql = $init_var = '';
    // For all possible multi-words length
    for ($i=$wl_max*2 -1; $i>1; $i--) {
        $set_wo_sql .= "WHEN (@a$i := @a".($i-1) . ") IS NULL THEN NULL ";
        $set_wo_sql_2 .= "WHEN (@a$i := @a".($i-2) .") IS NULL THEN NULL ";
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
    global $tbpref;

    $set_wo_sql = $set_wo_sql_2 = $del_wo_sql = $init_var = '';
    do_mysqli_query('SET GLOBAL max_heap_table_size = 1024 * 1024 * 1024 * 2');
    do_mysqli_query('SET GLOBAL tmp_table_size = 1024 * 1024 * 1024 * 2');
    // For all possible multi-words length,
    for ($i=$wl_max*2 -1; $i>1; $i--) {
        $set_wo_sql .= "WHEN (@a$i := @a".($i-1) . ") IS NULL THEN NULL ";
        $set_wo_sql_2 .= "WHEN (@a$i := @a".($i-2) .") IS NULL THEN NULL ";
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
    if ($id>0) {
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
    $sql .= ($id>0) ? ' UNION ALL ' : ' GROUP BY WoID ORDER BY WoTextLC';
    return $sql;
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
 */
function splitCheckText($text, $lid, $id)
{
    global $tbpref;
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
        return prepare_text_parsing($text, -2, $lid);
    }
    prepare_text_parsing($text, $id, $lid);

    // Check text
    if ($id == -1) {
        check_text_valid($lid);
    }

    // Get multi-word count
    $res = do_mysqli_query(
        "SELECT DISTINCT(WoWordCount)
        FROM {$tbpref}words
        WHERE WoLgID = $lid AND WoWordCount > 1"
    );
    while ($record = mysqli_fetch_assoc($res)){
        $wl[] = (int)$record['WoWordCount'];
    }
    mysqli_free_result($res);
    // Text has multi-words
    if (!empty($wl)) {
        checkExpressions($wl);
    }
    // Add sentences and text items to database for a new text
    if ($id > 0) {
        registerSentencesTextItems($id, $lid, !empty($wl));
    }

    // Check text
    if ($id == -1) {
        displayTextStatistics($lid, (bool)$rtlScript, $wl);
    }

    do_mysqli_query("TRUNCATE TABLE {$tbpref}temptextitems");
}


/**
 * Reparse all texts in order.
 *
 * @global string $tbpref Database table prefix
 */
function reparse_all_texts(): void
{
    global $tbpref;
    runsql("TRUNCATE {$tbpref}sentences", '');
    runsql("TRUNCATE {$tbpref}textitems2", '');
    adjust_autoincr('sentences', 'SeID');
    init_word_count();
    $sql = "SELECT TxID, TxLgID FROM {$tbpref}texts";
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        $id = (int) $record['TxID'];
        splitCheckText(
            (string)get_first_value(
                "SELECT TxText AS value
                FROM {$tbpref}texts
                WHERE TxID = $id"
            ),
            (string)$record['TxLgID'], $id
        );
    }
    mysqli_free_result($res);
}

/**
 * Update the database if it is using an outdate version.
 *
 * @param string $dbname Name of the database
 *
 * @global string $tbpref Database table prefix
 * @global 0|1    $debug  Output debug messages.
 *
 * @return void
 *
 * @since 2.10.0-fork Migrations are defined thourgh SQL, and not directly here
 */
function update_database($dbname)
{
    global $tbpref, $debug;

    // DB Version
    $currversion = get_version_number();

    $res = mysqli_query(
        $GLOBALS['DBCONNECTION'],
        "SELECT StValue AS value
        FROM {$tbpref}settings
        WHERE StKey = 'dbversion'"
    );
    if (mysqli_errno($GLOBALS['DBCONNECTION']) != 0) {
        my_die(
            'There is something wrong with your database ' . $dbname .
            '. Please reinstall.'
        );
    }
    $record = mysqli_fetch_assoc($res);
    if ($record) {
        $dbversion = $record["value"];
    } else {
        $dbversion = 'v001000000';
    }
    mysqli_free_result($res);

    // Do DB Updates if tables seem to be old versions

    if ($dbversion < $currversion) {

        if ($debug) {
            echo "<p>DEBUG: check DB collation: ";
        }
        if ('utf8utf8_general_ci' != get_first_value(
            'SELECT concat(default_character_set_name, default_collation_name) AS value
            FROM information_schema.SCHEMATA
            WHERE schema_name = "' . $dbname . '"'
        )
        ) {
            runsql("SET collation_connection = 'utf8_general_ci'", '');
            runsql(
                'ALTER DATABASE `' . $dbname .
                '` CHARACTER SET utf8 COLLATE utf8_general_ci',
                ''
            );
            if ($debug) {
                echo 'changed to utf8_general_ci</p>';
            }
        } else if ($debug) {
            echo 'OK</p>';
        }

        if ($debug) {
            echo "<p>DEBUG: do DB updates: $dbversion --&gt; $currversion</p>";
        }

        $changes = 0;
        $res = do_mysqli_query("SELECT filename FROM _migrations");
        while ($record = mysqli_fetch_assoc($res)) {
            $queries = parseSQLFile(
                __DIR__ . '/../../../db/migrations/' . $record["filename"]
            );
            foreach ($queries as $sql_query) {
                $changes += (int) runsql($sql_query, '', false);
            }
        }

        if ($debug) {
            echo '<p>DEBUG: rebuilding tts</p>';
        }
        runsql(
            "CREATE TABLE IF NOT EXISTS tts (
                TtsID mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
                TtsTxt varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                TtsLc varchar(8) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                PRIMARY KEY (TtsID),
                UNIQUE KEY TtsTxtLC (TtsTxt,TtsLc)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=1",
            ''
        );

        // Set database to current version
        saveSetting('dbversion', $currversion);
        saveSetting('lastscorecalc', '');  // do next section, too
    }
}


/**
 * Add a prefix to table in a SQL query string.
 *
 * @param string $sql_line SQL string to prefix.
 * @param string $prefix   Prefix to add
 */
function prefixSQLQuery($sql_line, $prefix)
{
    if (substr($sql_line, 0, 12) == "INSERT INTO ") {
        return substr($sql_line, 0, 12) . $prefix . substr($sql_line, 12);
    }
    $res = preg_match(
        '/^(?:DROP|CREATE|ALTER) TABLE (?:IF NOT EXISTS )?`?/',
        $sql_line,
        $matches
    );
    if ($res) {
        return $matches[0] . $prefix .
        substr($sql_line, strlen($matches[0]));
    }
    return $sql_line;
}

/**
 * Check and/or update the database.
 *
 * @global mysqli $DBCONNECTION Connection to the database.
 *
 * @since 2.10.0 Use confiduration files instead of containing all the data.
 */
function check_update_db($debug, $tbpref, $dbname): void
{
    $tables = array();

    $res = do_mysqli_query(
        str_replace(
            '_',
            "\\_",
            "SHOW TABLES LIKE " . convert_string_to_sqlsyntax_nonull($tbpref . '%')
        )
    );
    while ($row = mysqli_fetch_row($res)) {
        $tables[] = $row[0];
    }
    mysqli_free_result($res);

    /// counter for cache rebuild
    $count = 0;

    // Rebuild in missing table
    $queries = parseSQLFile(__DIR__ . "/../../../db/schema/baseline.sql");
    foreach ($queries as $query) {
        if (str_contains($query, "_migrations")) {
            // Do not prefix meta tables
            runsql($query, "");
        } else {
            $prefixed_query = prefixSQLQuery($query, $tbpref);
            // Increment count for new tables only
            $count += runsql($prefixed_query, "");
        }
    }

    // Update the database (if necessary)
    update_database($dbname);

    if (!in_array("{$tbpref}textitems2", $tables)) {
        // Add data from the old database system
        if (in_array("{$tbpref}textitems", $tables)) {
            runsql(
                "INSERT INTO {$tbpref}textitems2 (
                    Ti2WoID, Ti2LgID, Ti2TxID, Ti2SeID, Ti2Order, Ti2WordCount,
                    Ti2Text
                )
                SELECT IFNULL(WoID,0), TiLgID, TiTxID, TiSeID, TiOrder,
                CASE WHEN TiIsNotWord = 1 THEN 0 ELSE TiWordCount END as WordCount,
                CASE
                    WHEN STRCMP(TiText COLLATE utf8_bin,TiTextLC)!=0 OR TiWordCount=1
                    THEN TiText
                    ELSE ''
                END AS Text
                FROM {$tbpref}textitems
                LEFT JOIN {$tbpref}words ON TiTextLC=WoTextLC AND TiLgID=WoLgID
                WHERE TiWordCount<2 OR WoID IS NOT NULL",
                ''
            );
            runsql("TRUNCATE {$tbpref}textitems", '');
        }
        $count++;
    }

    if ($count > 0) {
        // Rebuild Text Cache if cache tables new
        if ($debug) {
            echo '<p>DEBUG: rebuilding cache tables</p>';
        }
        reparse_all_texts();
    }


    // Do Scoring once per day, clean Word/Texttags, and optimize db
    $lastscorecalc = getSetting('lastscorecalc');
    $today = date('Y-m-d');
    if ($lastscorecalc != $today) {
        if ($debug) {
            echo '<p>DEBUG: Doing score recalc. Today: ' . $today .
            ' / Last: ' . $lastscorecalc . '</p>';
        }
        runsql(
            "UPDATE {$tbpref}words
            SET " . make_score_random_insert_update('u') ."
            WHERE WoTodayScore>=-100 AND WoStatus<98",
            ''
        );
        runsql(
            "DELETE {$tbpref}wordtags
            FROM ({$tbpref}wordtags LEFT JOIN {$tbpref}tags on WtTgID = TgID)
            WHERE TgID IS NULL",
            ''
        );
        runsql(
            "DELETE {$tbpref}wordtags
            FROM ({$tbpref}wordtags LEFT JOIN {$tbpref}words ON WtWoID = WoID)
            WHERE WoID IS NULL",
            ''
        );
        runsql(
            "DELETE {$tbpref}texttags
            FROM ({$tbpref}texttags LEFT JOIN {$tbpref}tags2 ON TtT2ID = T2ID)
            WHERE T2ID IS NULL",
            ''
        );
        runsql(
            "DELETE {$tbpref}texttags
            FROM ({$tbpref}texttags LEFT JOIN {$tbpref}texts ON TtTxID = TxID)
            WHERE TxID IS NULL",
            ''
        );
        runsql(
            "DELETE {$tbpref}archtexttags
            FROM (
                {$tbpref}archtexttags
                LEFT JOIN {$tbpref}tags2 ON AgT2ID = T2ID
            )
            WHERE T2ID IS NULL",
            ''
        );
        runsql(
            "DELETE {$tbpref}archtexttags
            FROM (
                {$tbpref}archtexttags
                LEFT JOIN {$tbpref}archivedtexts ON AgAtID = AtID
            )
            WHERE AtID IS NULL",
            ''
        );
        optimizedb();
        saveSetting('lastscorecalc', $today);
    }
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
 */
function connect_to_database($server, $userid, $passwd, $dbname, $socket="")
{
    // @ suppresses error messages

    // Necessary since mysqli_report default setting in PHP 8.1+ has changed
    @mysqli_report(MYSQLI_REPORT_OFF);

    $dbconnection = mysqli_init();

    if ($dbconnection === false) {
        my_die(
            'Database connection error. Is MySQL running?
            You can refer to the documentation:
            https://hugofara.github.io/lwt/docs/install.html
            [Error Code: ' . mysqli_connect_errno() .
            ' / Error Message: ' . mysqli_connect_error() . ']'
        );
    }

    @mysqli_options($dbconnection, MYSQLI_OPT_LOCAL_INFILE, 1);

    if ($socket != "") {
        $success = @mysqli_real_connect(
            $dbconnection, $server, $userid, $passwd, $dbname, socket: $socket
        );
    } else {
        $success = @mysqli_real_connect(
            $dbconnection, $server, $userid, $passwd, $dbname
        );
    }

    if (!$success && mysqli_connect_errno() == 1049) {
        // Database unknown, try with generic database
        $success = @mysqli_real_connect(
            $dbconnection, $server, $userid, $passwd
        );

        if (!$success) {
            my_die(
                'DB connect error, connection parameters may be wrong,
                please check your ".env" file.
                You can refer to the documentation:
                https://hugofara.github.io/lwt/docs/install.html
                [Error Code: ' . mysqli_connect_errno() .
                ' / Error Message: ' . mysqli_connect_error() . ']'
            );
        }
        $result = mysqli_query(
            $dbconnection,
            "CREATE DATABASE `$dbname`
            DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci"
        );
        if (!$result) {
            my_die("Failed to create database!");
        }
        mysqli_close($dbconnection);
        $success = @mysqli_real_connect(
            $dbconnection, $server, $userid, $passwd, $dbname
        );
    }

    if (!$success) {
        my_die(
            'DB connect error, connection parameters may be wrong,
            please check your ".env" file.
            You can refer to the documentation:
            https://hugofara.github.io/lwt/docs/install.html
            [Error Code: ' . mysqli_connect_errno() .
            ' / Error Message: ' . mysqli_connect_error() . ']'
        );
    }

    @mysqli_query($dbconnection, "SET NAMES 'utf8'");

    // @mysqli_query($DBCONNECTION, "SET SESSION sql_mode = 'STRICT_ALL_TABLES'");
    @mysqli_query($dbconnection, "SET SESSION sql_mode = ''");
    return $dbconnection;
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
 * @return array Table prefix, and if table prefix should be fixed
 */
function getDatabasePrefix($dbconnection)
{
    global $DBCONNECTION;
    $DBCONNECTION = $dbconnection;
    if (!isset($tbpref)) {
        $fixed_tbpref = false;
        $tbpref = LWTTableGet("current_table_prefix");
    } else {
        $fixed_tbpref = true;
    }

    $len_tbpref = strlen($tbpref);
    if ($len_tbpref > 0) {
        if ($len_tbpref > 20) {
            my_die(
                'Table prefix/set "' . $tbpref .
                '" longer than 20 digits or characters.' .
                ' Please fix DB_TABLE_PREFIX in ".env".'
            );
        }
        for ($i=0; $i < $len_tbpref; $i++) {
            if (strpos(
                "_0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ",
                substr($tbpref, $i, 1)
            ) === false
            ) {
                my_die(
                    'Table prefix/set "' . $tbpref .
                    '" contains characters or digits other than 0-9, a-z, A-Z ' .
                    'or _. Please fix DB_TABLE_PREFIX in ".env".'
                );
            }
        }
    }

    if (!$fixed_tbpref) {
        LWTTableSet("current_table_prefix", $tbpref);
    }

    // IF PREFIX IS NOT '', THEN ADD A '_', TO ENSURE NO IDENTICAL NAMES
    if ($tbpref !== '') {
        $tbpref .= "_";
    }
    return array($tbpref, $fixed_tbpref);
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
    global $DBCONNECTION;
    list($tbpref, $fixed_tbpref) = getDatabasePrefix($DBCONNECTION);
    return (int) $fixed_tbpref;
}

// --------------------  S T A R T  --------------------------- //

// Start Timer
if (LWT_Globals::shouldDisplayTime()) {
    get_execution_time();
}

// Access the global variables set by loadDatabaseConfiguration()
global $DB_SERVER, $DB_USERID, $DB_PASSWD, $DB_NAME, $DB_SOCKET;

/**
 * @var \mysqli $DBCONNECTION Connection to the database
 *
 * @deprecated 3.0.0 Use LWT_Globals::getDbConnection() instead
 * @see LWT_Globals::getDbConnection()
 */
$DBCONNECTION = connect_to_database(
    $DB_SERVER, $DB_USERID, $DB_PASSWD, $DB_NAME, $DB_SOCKET ?? ""
);
// Register with LWT_Globals for new code
LWT_Globals::setDbConnection($DBCONNECTION);

/**
 * @var string $tbpref Database table prefix
 *
 * @deprecated 3.0.0 Use LWT_Globals::getTablePrefix() instead
 * @see LWT_Globals::getTablePrefix()
 */
$tbpref = null;
/**
 * @var int $fixed_tbpref Database prefix is fixed (1) or not (0)
 *
 * @deprecated 3.0.0 Use LWT_Globals::isTablePrefixFixed() instead
 * @see LWT_Globals::isTablePrefixFixed()
 */
$fixed_tbpref = null;
list($tbpref, $bool_fixed_tbpref) = getDatabasePrefix($DBCONNECTION);

// Register with LWT_Globals for new code
LWT_Globals::setTablePrefix($tbpref, (bool) $bool_fixed_tbpref);
LWT_Globals::setDatabaseName($DB_NAME);

// Convert to int, will be removed in LWT 3.0.0
$fixed_tbpref = (int) $bool_fixed_tbpref;

// check/update db
check_update_db(LWT_Globals::getDebug(), $tbpref, $DB_NAME);

?>
