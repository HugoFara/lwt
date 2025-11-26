<?php

/**
 * \file
 * \brief All the files needed for a LWT session.
 *
 * By requiring this file, you start a session, connect to the
 * database and declare a lot of useful functions.
 *
 * PHP version 8.1
 *
 * @package Lwt
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-session-utility.html
 * @since   2.0.3-fork
 */

require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/feeds.php';
require_once __DIR__ . '/tags.php';
require_once __DIR__ . '/ui_helpers.php';
require_once __DIR__ . '/export_helpers.php';
require_once __DIR__ . '/text_helpers.php';

/**
 * Return navigation arrows to previous and next texts.
 *
 * @param int    $textid  ID of the current text
 * @param string $url     Base URL to append before $textid
 * @param bool   $onlyann Restrict to annotated texts only
 * @param string $add     Some content to add before the output
 *
 * @return string Arrows to previous and next texts.
 */
function getPreviousAndNextTextLinks($textid, $url, $onlyann, $add): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $currentlang = validateLang(
        (string) processDBParam("filterlang", 'currentlanguage', '', false)
    );
    $wh_lang = '';
    if ($currentlang != '') {
        $wh_lang = ' AND TxLgID=' . $currentlang;
    }

    $currentquery = (string) processSessParam("query", "currenttextquery", '', false);
    $currentquerymode = (string) processSessParam(
        "query_mode", "currenttextquerymode", 'title,text', false
    );
    $currentregexmode = getSettingWithDefault("set-regex-mode");
    $wh_query = $currentregexmode . 'LIKE ';
    if ($currentregexmode == '') {
        $wh_query .= convert_string_to_sqlsyntax(
            str_replace("*", "%", mb_strtolower($currentquery, 'UTF-8'))
        );
    } else {
        $wh_query .= convert_string_to_sqlsyntax($currentquery);
    }
    switch ($currentquerymode) {
    case 'title,text':
        $wh_query=' AND (TxTitle ' . $wh_query . ' OR TxText ' . $wh_query . ')';
        break;
    case 'title':
        $wh_query=' AND (TxTitle ' . $wh_query . ')';
        break;
    case 'text':
        $wh_query=' AND (TxText ' . $wh_query . ')';
        break;
    }
    if ($currentquery=='') {
        $wh_query = '';
    }

    $currenttag1 = validateTextTag(
        (string) processSessParam("tag1", "currenttexttag1", '', false),
        $currentlang
    );
    $currenttag2 = validateTextTag(
        (string) processSessParam("tag2", "currenttexttag2", '', false),
        $currentlang
    );
    $currenttag12 = (string) processSessParam("tag12", "currenttexttag12", '', false);
    $wh_tag1 = null;
    $wh_tag2 = null;
    if ($currenttag1 == '' && $currenttag2 == '') {
        $wh_tag = '';
    } else {
        if ($currenttag1 != '') {
            if ($currenttag1 == -1) {
                $wh_tag1 = "group_concat(TtT2ID) IS NULL";
            } else {
                $wh_tag1 = "concat('/',group_concat(TtT2ID separator '/'),'/') like '%/" . $currenttag1 . "/%'";
            }
        }
        if ($currenttag2 != '') {
            if ($currenttag2 == -1) {
                $wh_tag2 = "group_concat(TtT2ID) IS NULL";
            }
            else {
                $wh_tag2 = "concat('/',group_concat(TtT2ID separator '/'),'/') like '%/" . $currenttag2 . "/%'";
            }
        }
        if ($currenttag1 != '' && $currenttag2 == '') {
            $wh_tag = " having (" . $wh_tag1 . ') ';
        }
        elseif ($currenttag2 != '' && $currenttag1 == '') {
            $wh_tag = " having (" . $wh_tag2 . ') ';
        } else {
            $wh_tag = " having ((" . $wh_tag1 . ($currenttag12 ? ') AND (' : ') OR (') . $wh_tag2 . ')) ';
        }
    }

    $currentsort = (int) processDBParam("sort", 'currenttextsort', '1', true);
    $sorts = array('TxTitle','TxID desc','TxID asc');
    $lsorts = count($sorts);
    if ($currentsort < 1) {
        $currentsort = 1;
    }
    if ($currentsort > $lsorts) {
        $currentsort = $lsorts;
    }

    if ($onlyann) {
        $sql =
        'SELECT TxID
        FROM (
            (' . $tbpref . 'texts
                LEFT JOIN ' . $tbpref . 'texttags ON TxID = TtTxID
            )
            LEFT JOIN ' . $tbpref . 'tags2 ON T2ID = TtT2ID
        ), ' . $tbpref . 'languages
        WHERE LgID = TxLgID AND LENGTH(TxAnnotatedText) > 0 '
        . $wh_lang . $wh_query . '
        GROUP BY TxID ' . $wh_tag . '
        ORDER BY ' . $sorts[$currentsort-1];
    }
    else {
        $sql =
        'SELECT TxID
        FROM (
            (' . $tbpref . 'texts
                LEFT JOIN ' . $tbpref . 'texttags ON TxID = TtTxID
            )
            LEFT JOIN ' . $tbpref . 'tags2 ON T2ID = TtT2ID
        ), ' . $tbpref . 'languages
        WHERE LgID = TxLgID ' . $wh_lang . $wh_query . '
        GROUP BY TxID ' . $wh_tag . '
        ORDER BY ' . $sorts[$currentsort-1];
    }

    $list = array(0);
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        array_push($list, (int) $record['TxID']);
    }
    mysqli_free_result($res);
    array_push($list, 0);
    $listlen = count($list);
    for ($i=1; $i < $listlen-1; $i++) {
        if($list[$i] == $textid) {
            if ($list[$i-1] !== 0) {
                $title = tohtml(getTextTitle($list[$i-1]));
                $prev = '<a href="' . $url . $list[$i-1] . '" target="_top"><img src="/assets/icons/navigation-180-button.png" title="Previous Text: ' . $title . '" alt="Previous Text: ' . $title . '" /></a>';
            }
            else {
                $prev = '<img src="/assets/icons/navigation-180-button-light.png" title="No Previous Text" alt="No Previous Text" />';
            }
            if ($list[$i+1] !== 0) {
                $title = tohtml(getTextTitle($list[$i+1]));
                $next = '<a href="' . $url . $list[$i+1] .
                '" target="_top"><img src="/assets/icons/navigation-000-button.png" title="Next Text: ' . $title . '" alt="Next Text: ' . $title . '" /></a>';
            }
            else {
                $next = '<img src="/assets/icons/navigation-000-button-light.png" title="No Next Text" alt="No Next Text" />';
            }
            return $add . $prev . ' ' . $next;
        }
    }
    return $add . '<img src="/assets/icons/navigation-180-button-light.png" title="No Previous Text" alt="No Previous Text" />
    <img src="/assets/icons/navigation-000-button-light.png" title="No Next Text" alt="No Next Text" />';
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
 * Return the list of media files found in folder, recursively.
 *
 * @param string $dir Directory to search into.
 *
 * @return array[] All paths found (matching files and folders) in "paths" and folders in "folders".
 *
 * @psalm-return array{paths: array, folders: array}
 */
function media_paths_search($dir): array
{
    $is_windows = str_starts_with(strtoupper(PHP_OS), "WIN");
    $mediadir = scandir($dir);
    $formats = array('mp3', 'mp4', 'ogg', 'wav', 'webm');
    $paths = array(
        "paths" => array($dir),
        "folders" => array($dir)
    );
    // For each item in directory
    foreach ($mediadir as $path) {
        if (str_starts_with($path, ".") || is_dir($dir . '/' . $path)) {
            continue;
        }
        // Add files to paths
        if ($is_windows) {
            $encoded = mb_convert_encoding($path, 'UTF-8', 'Windows-1252');
        } else {
            $encoded = $path;
        }
        $ex = strtolower(pathinfo($encoded, PATHINFO_EXTENSION));
        if (in_array($ex, $formats)) {
            $paths["paths"][] = $dir . '/' . $encoded;
        }
    }
    // Do the folder in a second time to get a better ordering
    foreach ($mediadir as $path) {
        if (str_starts_with($path, ".") || !is_dir($dir . '/' . $path)) {
            continue;
        }
        // For each folder, recursive search
        $subfolder_paths = media_paths_search($dir . '/' . $path);
        $paths["folders"] = array_merge($paths["folders"], $subfolder_paths["folders"]);
        $paths["paths"] = array_merge($paths["paths"], $subfolder_paths["paths"]);
    }
    return $paths;
}

/**
 * Return the paths for all media files.
 *
 * @return array Paths of media files, in the form array<string, string>
 */
function get_media_paths(): array
{
    $answer = array(
        "base_path" => basename(getcwd())
    );
    if (!file_exists('media')) {
        $answer["error"] = "does_not_exist";
    } else if (!is_dir('media')) {
        $answer["error"] = "not_a_directory";
    } else {
        $paths = media_paths_search('media');
        $answer["paths"] = $paths["paths"];
        $answer["folders"] = $paths["folders"];
    }
    return $answer;
}

/**
 * Get the different options to display as acceptable media files.
 *
 * @param string $dir Directory containing files
 *
 * @return string HTML-formatted OPTION tags
 */
function selectmediapathoptions($dir): string
{
    $r = "";
    //$r = '<option disabled="disabled">-- Directory: ' . tohtml($dir) . ' --</option>';
    $options = media_paths_search($dir);
    foreach ($options["paths"] as $op) {
        if (in_array($op, $options["folders"])) {
            $r .= '<option disabled="disabled">-- Directory: ' . tohtml($op) . '--</option>';
        } else {
            $r .= '<option value="' . tohtml($op) . '">' . tohtml($op) . '</option>';
        }
    }
    return $r;
}

/**
 * Select the path for a media (audio or video).
 *
 * @param string $f HTML field name for media string in form. Will be used as this.form.[$f] in JS.
 *
 * @return string HTML-formatted string for media selection
 */
function selectmediapath($f): string
{
    $media = get_media_paths();
    $r = '<p>
        YouTube, Dailymotion, Vimeo or choose a file in "../' . $media["base_path"] . '/media"
        <br />
        (only mp3, mp4, ogg, wav, webm files shown):
    </p>
    <p style="display: none;" id="mediaSelectErrorMessage"></p>
    <img style="float: right; display: none;" id="mediaSelectLoadingImg" src="/assets/icons/waiting2.gif" />
    <select name="Dir" style="display: none; width: 200px;"
    onchange="{val=this.form.Dir.options[this.form.Dir.selectedIndex].value; if (val != \'\') this.form.'
        . $f . '.value = val; this.form.Dir.value=\'\';}">
    </select>
    <span class="click" onclick="do_ajax_update_media_select();" style="margin-left: 16px;">
        <img src="/assets/icons/arrow-circle-135.png" title="Refresh Media Selection" alt="Refresh Media Selection" />
        Refresh
    </span>
    <script type="text/javascript">
        // Populate fields with data
        media_select_receive_data(' . json_encode($media) . ');
    </script>';
    return $r;
}

/**
 * @return string|string[]
 *
 * @psalm-return array<string>|string
 */
function remove_soft_hyphens($str): array|string
{
    return str_replace('­', '', $str);  // first '..' contains Softhyphen 0xC2 0xAD
}

/**
 * @return null|string|string[]
 *
 * @psalm-return array<string>|null|string
 */
function replace_supp_unicode_planes_char($s): array|string|null
{
    return preg_replace('/[\x{10000}-\x{10FFFF}]/u', "\xE2\x96\x88", $s);
    /* U+2588 = UTF8: E2 96 88 = FULL BLOCK = ⬛︎  */
}

function makeCounterWithTotal($max, $num): string
{
    if ($max == 1) {
        return '';
    }
    if ($max < 10) {
        return $num . "/" . $max;
    }
    return substr(
        str_repeat("0", strlen($max)) . $num,
        -strlen($max)
    ) . "/" . $max;
}

function encodeURI($url): string
{
    $reserved = array(
    '%2D'=>'-','%5F'=>'_','%2E'=>'.','%21'=>'!',
    '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')'
    );
    $unescaped = array(
    '%3B'=>';','%2C'=>',','%2F'=>'/','%3F'=>'?','%3A'=>':',
    '%40'=>'@','%26'=>'&','%3D'=>'=','%2B'=>'+','%24'=>'$'
    );
    $score = array(
    '%23'=>'#'
    );
    return strtr(rawurlencode($url), array_merge($reserved, $unescaped, $score));
}

/**
 * Echo the path of a file using the theme directory. Echo the base file name of
 * file is not found
 *
 * @param string $filename Filename
 */
function print_file_path($filename): void
{
    echo get_file_path($filename);
}

/**
 * Get the path of a file using the theme directory
 *
 * @param string $filename Filename
 *
 * @return string File path if it exists, otherwise the filename
 */
function get_file_path($filename)
{
    $file = getSettingWithDefault('set-theme-dir').preg_replace('/.*\//', '', $filename);
    if (file_exists($file)) {
        // Return absolute path for clean URL compatibility
        return '/' . $file;
    }
    // Return absolute path for clean URL compatibility
    return '/' . ltrim($filename, '/');
}

function get_sepas()
{
    static $sepa;
    if (!$sepa) {
        $sepa = preg_quote(getSettingWithDefault('set-term-translation-delimiters'), '/');
    }
    return $sepa;
}

function get_first_sepa()
{
    static $sepa;
    if (!$sepa) {
        $sepa = mb_substr(
            getSettingWithDefault('set-term-translation-delimiters'),
            0, 1, 'UTF-8'
        );
    }
    return $sepa;
}

/**
 * Get a session value and update it if necessary.
 *
 * @param string     $reqkey  If in $_REQUEST, update the session with $_REQUEST[$reqkey]
 * @param string     $sesskey Field of the session to get or update
 * @param string|int $default Default value to return
 * @param bool       $isnum   If true, convert the result to an int
 *
 * @return string|int The required data unless $isnum is specified
 */
function processSessParam($reqkey, $sesskey, $default, $isnum)
{
    if (isset($_REQUEST[$reqkey])) {
        $reqdata = trim($_REQUEST[$reqkey]);
        $_SESSION[$sesskey] = $reqdata;
        $result = $reqdata;
    } elseif(isset($_SESSION[$sesskey])) {
        $result = $_SESSION[$sesskey];
    } else {
        $result = $default;
    }
    if ($isnum) {
        $result = (int)$result;
    }
    return $result;
}

/**
 * Get a database value and update it if necessary.
 *
 * @param string $reqkey  If in $_REQUEST, update the database with $_REQUEST[$reqkey]
 * @param string $dbkey   Field of the database to get or update
 * @param string $default Default value to return
 * @param bool   $isnum   If true, convert the result to an int
 *
 * @return string|int The string data unless $isnum is specified
 */
function processDBParam($reqkey, $dbkey, $default, $isnum)
{
    $dbdata = getSetting($dbkey);
    if (isset($_REQUEST[$reqkey])) {
        $reqdata = trim($_REQUEST[$reqkey]);
        saveSetting($dbkey, $reqdata);
        $result = $reqdata;
    } elseif ($dbdata != '') {
        $result = $dbdata;
    } else {
        $result = $default;
    }
    if ($isnum) {
        $result = (int)$result;
    }
    return $result;
}

function getWordTagList($wid, $before=' ', $brack=1, $tohtml=1): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $lbrack = $rbrack = '';
    if ($brack) {
        $lbrack = "[";
        $rbrack = "]";
    }
    $r = get_first_value(
        "SELECT IFNULL(
            GROUP_CONCAT(DISTINCT TgText ORDER BY TgText separator ', '),
            ''
        ) AS value
        FROM (
            (
                {$tbpref}words
                LEFT JOIN {$tbpref}wordtags
                ON WoID = WtWoID
            )
            LEFT JOIN {$tbpref}tags
            ON TgID = WtTgID
        )
        WHERE WoID = $wid"
    );
    if ($r != '') {
        $r = $before . $lbrack . $r . $rbrack;
    }
    if ($tohtml) {
        $r = tohtml($r);
    }
    return $r;
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

/* Functions relative to word tests. */

/**
 * Create a projection operator do perform word test.
 *
 * @param string    $key   Type of test.
 *                         - 'words': selection from words
 *                         - 'texts': selection from texts
 *                         - 'lang': selection from language
 *                         - 'text': selection from single text
 * @param array|int $value Object to select.
 *
 * @return null|string SQL projection necessary
 */
function do_test_test_get_projection($key, $value): string|null
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $testsql = null;
    switch ($key)
    {
    case 'words':
        // Test words in a list of words ID
        $id_string = implode(",", $value);
        $testsql = " {$tbpref}words WHERE WoID IN ($id_string) ";
        $cntlang = get_first_value(
            "SELECT COUNT(DISTINCT WoLgID) AS value
                FROM $testsql"
        );
        if ($cntlang > 1) {
            echo "<p>Sorry - The selected terms are in $cntlang languages," .
            " but tests are only possible in one language at a time.</p>";
            exit();
        }
        break;
    case 'texts':
        // Test text items from a list of texts ID
        $id_string = implode(",", $value);
        $testsql = " {$tbpref}words, {$tbpref}textitems2
            WHERE Ti2LgID = WoLgID AND Ti2WoID = WoID AND Ti2TxID IN ($id_string) ";
        $cntlang = get_first_value(
            "SELECT COUNT(DISTINCT WoLgID) AS value
            FROM $testsql"
        );
        if ($cntlang > 1) {
            echo "<p>Sorry - The selected terms are in $cntlang languages," .
            " but tests are only possible in one language at a time.</p>";
            exit();
        }
        break;
    case 'lang':
        // Test words from a specific language
        $testsql = " {$tbpref}words WHERE WoLgID = $value ";
        break;
    case 'text':
        // Test text items from a specific text ID
        $testsql = " {$tbpref}words, {$tbpref}textitems2
            WHERE Ti2LgID = WoLgID AND Ti2WoID = WoID AND Ti2TxID = $value ";
        break;
    default:
        my_die("do_test_test.php called with wrong parameters");
    }
    return $testsql;
}

/**
 * Prepare the SQL when the text is a selection.
 *
 * @param int    $selection_type. 2 is words selection and 3 is terms selection.
 * @param string $selection_data  Comma separated ID of elements to test.
 *
 * @return null|string SQL formatted string suitable to projection (inserted in a "FROM ")
 */
function do_test_test_from_selection($selection_type, $selection_data): string|null
{
    $data_string_array = explode(",", trim($selection_data, "()"));
    $data_int_array = array_map('intval', $data_string_array);
    switch ((int)$selection_type) {
    case 2:
        $test_sql = do_test_test_get_projection('words', $data_int_array);
        break;
    case 3:
        $test_sql = do_test_test_get_projection('texts', $data_int_array);
        break;
    default:
        $test_sql = $selection_data;
        $cntlang = get_first_value(
            "SELECT COUNT(DISTINCT WoLgID) AS value
                FROM $test_sql"
        );
        if ($cntlang > 1) {
            echo "<p>Sorry - The selected terms are in $cntlang languages," .
            " but tests are only possible in one language at a time.</p>";
            exit();
        }
    }
    return $test_sql;
}

/// Returns options for an HTML dropdown to choose a text along a criterion

/**
 * Create and verify a dictionary URL link
 *
 * Case 1: url without any ### or lwt_term: append UTF-8-term
 * Case 2: url with one ### or lwt_term: substitute UTF-8-term
 * Case 3: url with two (###|lwt_term)enc###: unsupported encoding changed,
 *         abandonned since 2.6.0-fork
 *
 * @param string $u Dictionary URL. It may contain 'lwt_term' that will get parsed
 * @param string $t Text that substite the 'lwt_term'
 *
 * @return string Dictionary link formatted
 *
 * @since 2.7.0-fork It is recommended to use "lwt_term" instead of "###"
 */

function createTheDictLink($u, $t)
{
    $url = trim($u);
    $trm = trim($t);
    // No ###|lwt_term found
    if (preg_match("/lwt_term|###/", $url, $matches) == false) {
        $r = $url . urlencode($trm);
        return $r;
    }
    $pos = stripos($url, $matches[0]);
    // ###|lwt_term found
    $pos2 = stripos($url, '###', $pos + 1);
    if ($pos2 === false) {
        // 1 ###|lwt_term found
        return str_replace($matches[0], ($trm == '' ? '+' : urlencode($trm)), $url);
    }
    // 2 ### found
    // Get encoding
    $enc = trim(
        substr(
            $url, $pos + mb_strlen($matches[0]), $pos2 - $pos - mb_strlen($matches[0])
        )
    );
    $r = substr($url, 0, $pos);
    $r .= urlencode(mb_convert_encoding($trm, $enc, 'UTF-8'));
    if ($pos2+3 < strlen($url)) {
        $r .= substr($url, $pos2 + 3);
    }
    return $r;
}

/**
 * Returns dictionnary links formatted as HTML.
 *
 * @param int    $lang      Language ID
 * @param string $word
 * @param string $sentctljs
 * @param bool   $openfirst True if we should open right frames with translation
 *                          first
 *
 * @return string HTML-formatted interface
 */
function createDictLinksInEditWin($lang, $word, $sentctljs, $openfirst): string
{
    $sql = 'SELECT LgDict1URI, LgDict2URI, LgGoogleTranslateURI
    FROM ' . \Lwt\Core\LWT_Globals::table('languages') . '
    WHERE LgID = ' . $lang;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $wb1 = isset($record['LgDict1URI']) ? $record['LgDict1URI'] : "";
    $wb2 = isset($record['LgDict2URI']) ? $record['LgDict2URI'] : "";
    $wb3 = isset($record['LgGoogleTranslateURI']) ?
    $record['LgGoogleTranslateURI'] : "";
    mysqli_free_result($res);
    $r ='';
    if ($openfirst) {
        $r .= '<script type="text/javascript">';
        $r .= "\n//<![CDATA[\n";
        $r .= makeOpenDictStrJS(createTheDictLink($wb1, $word));
        $r .= "//]]>\n</script>\n";
    }
    $r .= 'Lookup Term: ';
    $r .= makeOpenDictStr(createTheDictLink($wb1, $word), "Dict1");
    if ($wb2 != "") {
        $r .= makeOpenDictStr(createTheDictLink($wb2, $word), "Dict2");
    }
    if ($wb3 != "") {
        $r .= makeOpenDictStr(createTheDictLink($wb3, $word), "Translator") .
        ' | ' .
        makeOpenDictStrDynSent($wb3, $sentctljs, "Translate sentence");
    }
    return $r;
}

/**
 * Create a dictionnary open URL from an pseudo-URL
 *
 * @param string $url An URL, starting with a "*" is deprecated.
 *                    * If it contains a "popup" query, open in new window
 *                    * Otherwise open in iframe
 * @param string $txt Clickable text to display
 *
 * @return string HTML-formatted string
 */
function makeOpenDictStr($url, $txt): string
{
    $r = '';
    if ($url == '' || $txt == '') {
        return $r;
    }
    $popup = false;
    if (str_starts_with($url, '*')) {
        $url = substr($url, 1);
        $popup = true;
    }
    if (!$popup) {
        $query = parse_url($url, PHP_URL_QUERY);
        if ($query !== false && $query !== null) {
            parse_str($query, $url_query);
            $popup = array_key_exists('lwt_popup', $url_query);
        }
    }
    if ($popup) {
        $r = ' <span class="click" onclick="owin(' .
        prepare_textdata_js($url) . ');">' .
        tohtml($txt) .
        '</span> ';
    } else {
        $r = ' <a href="' . $url .
        '" target="ru" onclick="showRightFrames();">' .
        tohtml($txt) . '</a> ';
    }
    return $r;
}

function makeOpenDictStrJS($url): string
{
    $r = '';
    if ($url != '') {
        $popup = false;
        if (str_starts_with($url, "*")) {
            $url = substr($url, 1);
            $popup = true;
        }
        $query = parse_url($url, PHP_URL_QUERY);
        if ($query !== false && $query !== null) {
            parse_str($query, $url_query);
            $popup = $popup || array_key_exists('lwt_popup', $url_query);
        }
        if ($popup) {
            $r = "owin(" . prepare_textdata_js($url) . ");\n";
        } else {
            $r = "top.frames['ru'].location.href=" . prepare_textdata_js($url) . ";\n";
        }
    }
    return $r;
}

/**
 * Create a dictionnary open URL from an pseudo-URL
 *
 * @param string $url       A string containing at least a URL
 *                          * If it contains the query "lwt_popup", open in Popup
 *                          * Starts with a '*': open in pop-up window (deprecated)
 *                          * Otherwise open in iframe
 * @param string $sentctljs Clickable text to display
 * @param string $txt       Clickable text to display
 *
 * @return string HTML-formatted string
 *
 * @since 2.7.0-fork Supports LibreTranslate, using other string that proper URL is
 *                   deprecated.
 */
function makeOpenDictStrDynSent($url, $sentctljs, $txt): string
{
    $r = '';
    if ($url == '') {
        return $r;
    }
    $popup = false;
    if (str_starts_with($url, "*")) {
        $url = substr($url, 1);
        $popup = true;
    }
    $parsed_url = parse_url($url);
    if ($parsed_url === false) {
        $prefix = 'http://';
        $parsed_url = parse_url($prefix . $url);
    }
    parse_str($parsed_url['query'], $url_query);
    $popup = $popup || array_key_exists('lwt_popup', $url_query);
    if (str_starts_with($url, "ggl.php")
        || str_ends_with($parsed_url['path'], "/ggl.php")
    ) {
        $url = str_replace('?', '?sent=1&', $url);
    }
    return '<span class="click" onclick="translateSentence'.($popup ? '2' : '').'(' .
    prepare_textdata_js($url) . ',' . $sentctljs . ');">' .
    tohtml($txt) . '</span>';
}

/**
 * Returns dictionnary links formatted as HTML.
 *
 * @param int    $lang      Language ID
 * @param string $sentctljs
 * @param string $wordctljs
 *
 * @return string HTML formatted interface
 */
function createDictLinksInEditWin2($lang, $sentctljs, $wordctljs): string
{
    $sql = "SELECT LgDict1URI, LgDict2URI, LgGoogleTranslateURI
    FROM " . \Lwt\Core\LWT_Globals::table('languages') . " WHERE LgID = $lang";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $wb1 = isset($record['LgDict1URI']) ? (string) $record['LgDict1URI'] : "";
    if (substr($wb1, 0, 1) == '*') {
        $wb1 = substr($wb1, 1);
    }
    $wb2 = isset($record['LgDict2URI']) ? (string) $record['LgDict2URI'] : "";
    if (substr($wb2, 0, 1) == '*') {
        $wb2 = substr($wb2, 1);
    }
    $wb3 = isset($record['LgGoogleTranslateURI']) ?
    (string) $record['LgGoogleTranslateURI'] : "";
    if (substr($wb3, 0, 1) == '*') {
        $wb3 = substr($wb3, 1);
    }
    mysqli_free_result($res);

    $r = 'Lookup Term:
    <span class="click" onclick="translateWord2(' . prepare_textdata_js($wb1) .
    ',' . $wordctljs . ');">Dict1</span> ';
    if ($wb2 != "") {
        $r .= '<span class="click" onclick="translateWord2(' .
        prepare_textdata_js($wb2) . ',' . $wordctljs . ');">Dict2</span> ';
    }
    if ($wb3 != "") {
        $sent_mode = substr($wb3, 0, 7) == 'ggl.php' ||
        str_ends_with(parse_url($wb3, PHP_URL_PATH), '/ggl.php');
        $r .= '<span class="click" onclick="translateWord2(' .
        prepare_textdata_js($wb3) . ',' . $wordctljs . ');">Translator</span>
         | <span class="click" onclick="translateSentence2(' .
        prepare_textdata_js(
            $sent_mode ?
            str_replace('?', '?sent=1&', $wb3) : $wb3
        ) . ',' . $sentctljs .
         ');">Translate sentence</span>';
    }
    return $r;
}

function makeDictLinks($lang, $wordctljs): string
{
    $sql = 'SELECT LgDict1URI, LgDict2URI, LgGoogleTranslateURI
    FROM ' . \Lwt\Core\LWT_Globals::table('languages') . ' WHERE LgID = ' . $lang;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $wb1 = isset($record['LgDict1URI']) ? (string) $record['LgDict1URI'] : "";
    if (substr($wb1, 0, 1) == '*') {
        $wb1 = substr($wb1, 1);
    }
    $wb2 = isset($record['LgDict2URI']) ? (string) $record['LgDict2URI'] : "";
    if (substr($wb2, 0, 1) == '*') {
        $wb2 = substr($wb2, 1);
    }
    $wb3 = isset($record['LgGoogleTranslateURI']) ?
    (string) $record['LgGoogleTranslateURI'] : "";
    if (substr($wb3, 0, 1) == '*') {
        $wb3 = substr($wb3, 1);
    }
    mysqli_free_result($res);
    $r ='<span class="smaller">';
    $r .= '<span class="click" onclick="translateWord3(' .
    prepare_textdata_js($wb1) . ',' . $wordctljs . ');">[1]</span> ';
    if ($wb2 != "") {
        $r .= '<span class="click" onclick="translateWord3(' .
        prepare_textdata_js($wb2) . ',' . $wordctljs . ');">[2]</span> ';
    }
    if ($wb3 != "") {
        $r .= '<span class="click" onclick="translateWord3(' .
        prepare_textdata_js($wb3) . ',' . $wordctljs . ');">[G]</span>';
    }
    $r .= '</span>';
    return $r;
}

function createDictLinksInEditWin3($lang, $sentctljs, $wordctljs): string
{
    $sql = "SELECT LgDict1URI, LgDict2URI, LgGoogleTranslateURI
    FROM " . \Lwt\Core\LWT_Globals::table('languages') . " WHERE LgID = $lang";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);

    $wb1 = isset($record['LgDict1URI']) ? (string) $record['LgDict1URI'] : "";
    $popup = false;
    if (substr($wb1, 0, 1) == '*') {
        $wb1 = substr($wb1, 0, 1);
        $popup = true;
    }
    $popup = $popup || str_contains($wb1, "lwt_popup=");
    if ($popup) {
        $f1 = 'translateWord2(' . prepare_textdata_js($wb1);
    } else {
        $f1 = 'translateWord(' . prepare_textdata_js($wb1);
    }

    $wb2 = isset($record['LgDict2URI']) ? (string) $record['LgDict2URI'] : "";
    $popup = false;
    if (substr($wb2, 0, 1) == '*') {
        $wb2 = substr($wb2, 0, 1);
        $popup = true;
    }
    $popup = $popup || str_contains($wb2, "lwt_popup=");
    if ($popup) {
        $f2 = 'translateWord2(' . prepare_textdata_js($wb2);
    } else {
        $f2 = 'translateWord(' . prepare_textdata_js($wb2);
    }

    $wb3 = isset($record['LgGoogleTranslateURI']) ?
    (string) $record['LgGoogleTranslateURI'] : "";
    $popup = false;
    if (substr($wb3, 0, 1) == '*') {
        $wb3 = substr($wb3, 0, 1);
        $popup = true;
    }
    $parsed_url = parse_url($wb3);
    if ($wb3 != '' && $parsed_url === false) {
        $prefix = 'http://';
        $parsed_url = parse_url($prefix . $wb3);
    }
    if (array_key_exists('query', $parsed_url)) {
        parse_str($parsed_url['query'], $url_query);
        $popup = $popup || array_key_exists('lwt_popup', $url_query);
    }
    if ($popup) {
        $f3 = 'translateWord2(' . prepare_textdata_js($wb3);
        $f4 = 'translateSentence2(' . prepare_textdata_js($wb3);
    } else {
        $f3 = 'translateWord(' . prepare_textdata_js($wb3);
        $f4 = 'translateSentence(' . prepare_textdata_js(
            (str_ends_with($parsed_url['path'], "/ggl.php")) ?
            str_replace('?', '?sent=1&', $wb3) : $wb3
        );
    }

    mysqli_free_result($res);
    $r ='';
    $r .= 'Lookup Term: ';
    $r .= '<span class="click" onclick="' . $f1 . ',' . $wordctljs . ');">
    Dict1</span> ';
    if ($wb2 != "") {
        $r .= '<span class="click" onclick="' . $f2 . ',' . $wordctljs . ');">
        Dict2</span> ';
    }
    if ($wb3 != "") {
        $r .= '<span class="click" onclick="' . $f3 . ',' . $wordctljs . ');">
        Translator</span> |
        <span class="click" onclick="' . $f4 . ',' . $sentctljs . ');">
        Translate sentence</span>';
    }
    return $r;
}

function strToHex($string): string
{
    $hex='';
    for ($i=0; $i < strlen($string); $i++)
    {
        $h = dechex(ord($string[$i]));
        if (strlen($h) == 1 ) {
            $hex .= "0" . $h;
        }
        else {
            $hex .= $h;
        }
    }
    return strtoupper($hex);
}

/**
 * Escapes everything to "¤xx" but not 0-9, a-z, A-Z, and unicode >= (hex 00A5, dec 165)
 *
 * @param string $string String to escape
 */
function strToClassName($string): string
{
    $length = mb_strlen($string, 'UTF-8');
    $r = '';
    for ($i=0; $i < $length; $i++)
    {
        $c = mb_substr($string, $i, 1, 'UTF-8');
        $o = ord($c);
        if (($o < 48)
            || ($o > 57 && $o < 65)
            || ($o > 90 && $o < 97)
            || ($o > 122 && $o < 165)
        ) {
            $r .= '¤' . strToHex($c);
        } else {
            $r .= $c;
        }
    }
    return $r;
}
