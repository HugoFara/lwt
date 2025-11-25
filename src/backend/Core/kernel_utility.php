<?php
/**
 * \file
 * \brief Core utility functions that do not require a complete session.
 *
 * PHP version 8.1
 *
 * @package Lwt
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-kernel-utility.html
 * @since   2.0.3-fork
 */

 require __DIR__ . '/settings.php';

/**
 * @var string Version of this current LWT application.
 */
 define('LWT_APP_VERSION', '2.10.0-fork');

 /**
  * @var string Date of the lastest published release of LWT
  */
 define('LWT_RELEASE_DATE', "2024-04-01");

/**
 * Return LWT version for humans
 *
 * Version is hardcoded in this function.
 * For instance 1.6.31 (October 03 2016)
 *
 * @global bool $debug If true adds a red "DEBUG"
 *
 * @return string Version number HTML-formatted
 *
 * @psalm-return '2.9.1-fork (December 29 2023) <span class="red">DEBUG</span>'|'2.9.1-fork (December 29 2023)'
 */
function get_version(): string
{
    global $debug;
    $formattedDate = date("F d Y", strtotime(LWT_RELEASE_DATE));
    $version = LWT_APP_VERSION . " ($formattedDate)";
    if ($debug) {
        $version .= ' <span class="red">DEBUG</span>';
    }
    return $version;
}

/**
 * Return a machine readable version number.
 *
 * @return string Machine-readable version, for instance v001.006.031 for version 1.6.31.
 */
function get_version_number(): string
{
    $r = 'v';
    $v = get_version();
    // Escape any detail like "-fork"
    $v = preg_replace('/-\w+\d*/', '', $v);
    $pos = strpos($v, ' ', 0);
    if ($pos === false) {
        my_die('Wrong version: '. $v);
    }
    $vn = preg_split("/[.]/", substr($v, 0, $pos));
    if (count($vn) < 3) {
        my_die('Wrong version: '. $v);
    }
    for ($i=0; $i<3; $i++) {
        $r .= substr('000' . $vn[$i], -3);
    }
    return $r;
}

/**
 * Escape special HTML characters.
 *
 * @param  string $s String to escape.
 * @return string htmlspecialchars($s, ENT_COMPAT, "UTF-8");
 */
function tohtml($s)
{
    if (!isset($s)) {
        return '';
    }
    return htmlspecialchars($s, ENT_COMPAT, "UTF-8");
}


/**
 * Echo debugging informations.
 */
function showRequest(): void
{
    $olderr = error_reporting(0);
    echo "<pre>** DEBUGGING **********************************\n";
    echo '$GLOBALS...';
    print_r($GLOBALS);
    echo 'get_version_number()...';
    echo get_version_number() . "\n";
    echo 'get_magic_quotes_gpc()...';
    echo "NOT EXISTS (FALSE)\n";
    echo "********************************** DEBUGGING **</pre>";
    error_reporting($olderr);
}

/**
 * Get the time since the last call
 *
 * @return float Time sonce last call
 */
function get_execution_time()
{
    static $microtime_start = null;
    if ($microtime_start === null) {
        $microtime_start = microtime(true);
        return 0.0;
    }
    return microtime(true) - $microtime_start;
}

/**
 * Reload $setting_data if necessary
 *
 * @return array $setting_data
 */
function get_setting_data()
{
    static $setting_data;
    if (!$setting_data) {
        $setting_data = array(
        'set-text-h-frameheight-no-audio' => array(
            "dft" => '140', "num" => 1, "min" => 10, "max" => 999
        ),
        'set-text-h-frameheight-with-audio' => array(
            "dft" => '200', "num" => 1, "min" => 10, "max" => 999
        ),
        'set-text-l-framewidth-percent' => array(
            "dft" => '60', "num" => 1, "min" => 5, "max" => 95
        ),
        'set-text-r-frameheight-percent' => array(
            "dft" => '37', "num" => 1, "min" => 5, "max" => 95
        ),
        'set-test-h-frameheight' => array(
            "dft" => '140', "num" => 1, "min" => 10, "max" => 999
        ),
        'set-test-l-framewidth-percent' => array(
            "dft" => '50', "num" => 1, "min" => 5, "max" => 95
        ),
        'set-test-r-frameheight-percent' => array(
            "dft" => '50', "num" => 1, "min" => 5, "max" => 95
        ),
        'set-words-to-do-buttons' => array(
            "dft" => '1', "num" => 0
        ),
        'set-tooltip-mode' => array(
            "dft" => '2', "num" => 0
        ),
        'set-display-text-frame-term-translation' => array(
            "dft" => '1', "num" => 0
        ),
        'set-text-frame-annotation-position' => array(
            "dft" => '2', "num" => 0
        ),
        'set-test-main-frame-waiting-time' => array(
            "dft" => '0', "num" => 1, "min" => 0, "max" => 9999
        ),
        'set-test-edit-frame-waiting-time' => array(
            "dft" => '500', "num" => 1, "min" => 0, "max" => 99999999
        ),
        'set-test-sentence-count' => array(
            "dft" => '1', "num" => 0
        ),
        'set-tts' => array(
            "dft" => '1', "num" => 0
        ),
        'set-hts' => array(
            "dft" => '1', "num" => 0
        ),
        'set-term-sentence-count' => array(
            "dft" => '1', "num" => 0
        ),
        'set-archivedtexts-per-page' => array(
            "dft" => '100', "num" => 1, "min" => 1, "max" => 9999
        ),
        'set-texts-per-page' => array(
            "dft" => '10', "num" => 1, "min" => 1, "max" => 9999
        ),
        'set-terms-per-page' => array(
            "dft" => '100', "num" => 1, "min" => 1, "max" => 9999
        ),
        'set-tags-per-page' => array(
            "dft" => '100', "num" => 1, "min" => 1, "max" => 9999
        ),
        'set-articles-per-page' => array(
            "dft" => '10', "num" => 1, "min" => 1, "max" => 9999
        ),
        'set-feeds-per-page' => array(
            "dft" => '50', "num" => 1, "min" => 1, "max" => 9999
        ),
        'set-max-articles-with-text' => array(
            "dft" => '100', "num" => 1, "min" => 1, "max" => 9999
        ),
        'set-max-articles-without-text' => array(
            "dft" => '250', "num" => 1, "min" => 1, "max" => 9999
        ),
        'set-max-texts-per-feed' => array(
            "dft" => '20', "num" => 1, "min" => 1, "max" => 9999
        ),
        'set-ggl-translation-per-page' => array(
            "dft" => '100', "num" => 1, "min" => 1, "max" => 9999
        ),
        'set-regex-mode' => array(
            "dft" => '', "num" => 0
        ),
        'set-theme_dir' => array(
            "dft" => 'themes/default/', "num" => 0
        ),
        'set-text-visit-statuses-via-key' => array(
            "dft" => '', "num" => 0
        ),
        'set-term-translation-delimiters' => array(
            "dft" => '/;|', "num" => 0
        ),
        'set-mobile-display-mode' => array(
            "dft" => '0', "num" => 0
        ),
        'set-similar-terms-count' => array(
            "dft" => '0', "num" => 1, "min" => 0, "max" => 9
        ),
        'set-show-text-word-counts' => array(
            "dft" => '1', "num" => 0
        )
        );
    }
    return $setting_data;
}

/**
 * Remove all spaces from a string.
 *
 * @param string      $s      Input string
 * @param string|bool $remove Do not do anything if empty or false
 *
 * @return string String without spaces if requested.
 */
function remove_spaces($s, $remove)
{
    if (!$remove) {
        return $s;
    }
    // '' enthält &#x200B;
    return str_replace(' ', '', $s);
}

/**
 * Returns path to the MeCab application.
 * MeCab can split Japanese text word by word
 *
 * @param string $mecab_args Arguments to add
 *
 * @return string OS-compatible command
 *
 * @since 2.3.1-fork Much more verifications added
 * @since 2.10.0-fork Support for Mac OS added
 */
function get_mecab_path($mecab_args = ''): string
{
    $os = strtoupper(PHP_OS);
    $mecab_args = escapeshellcmd($mecab_args);
    if (str_starts_with($os, 'LIN') || str_starts_with($os, 'DAR')) {
        if (shell_exec("command -v mecab")) {
            return 'mecab' . $mecab_args;
        }
        my_die(
            "MeCab not detected! " .
            "Please install it or add it to your PATH (see documentation)."
        );
    }
    if (str_starts_with($os, 'WIN')) {
        if (shell_exec('where /R "%ProgramFiles%\\MeCab\\bin" mecab.exe')) {
            return '"%ProgramFiles%\\MeCab\\bin\\mecab.exe"' . $mecab_args;
        }
        if (shell_exec('where /R "%ProgramFiles(x86)%\\MeCab\\bin" mecab.exe')) {
            return '"%ProgramFiles(x86)%\\MeCab\\bin\\mecab.exe"' . $mecab_args;
        }
        if (shell_exec('where mecab.exe')) {
            return 'mecab.exe' . $mecab_args;
        }
        my_die(
            "MeCab not detected! " .
            "Install it or add it to the PATH (see documentation)."
        );
    }
    my_die("Your OS '$os' cannot use MeCab with this version of LWT!");
}


/**
 * Find end-of-sentence characters in a sentence using latin alphabet.
 *
 * @param string[] $matches       All the matches from a capturing regex
 * @param string   $noSentenceEnd If different from '', can declare that a string a not the end of a sentence.
 *
 * @return string $matches[0] with ends of sentences marked with \t and \r.
 */
function find_latin_sentence_end($matches, $noSentenceEnd)
{
    // Handle potentially null values in $matches array
    $match6 = $matches[6] ?? '';
    $match7 = $matches[7] ?? '';

    if (!strlen($match6) && strlen($match7) && preg_match('/[a-zA-Z0-9]/', substr($matches[1], -1))) {
        return preg_replace("/[.]/", ".\t", $matches[0]);
    }
    if (is_numeric($matches[1])) {
        if (strlen($matches[1]) < 3) {
            return $matches[0];
        }
    } else if ($matches[3] && (preg_match('/^[B-DF-HJ-NP-TV-XZb-df-hj-np-tv-xz][b-df-hj-np-tv-xzñ]*$/u', $matches[1]) || preg_match('/^[AEIOUY]$/', $matches[1]))
    ) {
        return $matches[0];
    }
    if (preg_match('/[.:]/', $matches[2]) && preg_match('/^[a-z]/', $match7)) {
        return $matches[0];
    }
    if ($noSentenceEnd != '' && preg_match("/^($noSentenceEnd)$/", $matches[0])) {
        return $matches[0];
    }
    return $matches[0] . "\r";
}


/**
 * Make the script crash and prints an error message
 *
 * @param string $text Error text to output
 *
 * @return never
 *
 * @since 2.5.3-fork Add a link to the Discord community
 */
function my_die($text)
{
    echo '</select></p></div><div style="padding: 1em; color:red; font-size:120%; background-color:#CEECF5;">' .
    '<p><b>Fatal Error:</b> ' .
    tohtml($text) .
    "</p></div><hr /><pre>Backtrace:\n\n";
    debug_print_backtrace();
    echo '</pre><hr />
    <p>Signal this issue on
    <a href="https://github.com/HugoFara/lwt/issues/new/choose">GitHub</a> or
    <a href="https://discord.gg/xrkRZR2jtt">Discord</a>.</p>';
    die('</body></html>');
}

/**
 * Display the main menu of navigation as a dropdown
 */
function quickMenu(): void
{
    ?>

<select id="quickmenu" onchange="quickMenuRedirection(value)">
    <option value="" selected="selected">[Menu]</option>
    <option value="index">Home</option>
    <optgroup label="Texts">
        <option value="edit_texts">Texts</option>
        <option value="edit_archivedtexts">Text Archive</option>
        <option value="edit_texttags">Text Tags</option>
        <option value="check_text">Text Check</option>
        <option value="long_text_import">Long Text Import</option>
    </optgroup>
    <option value="edit_languages">Languages</option>
    <optgroup label="Terms">
        <option value="edit_words">Terms</option>
        <option value="edit_tags">Term Tags</option>
        <option value="upload_words">Term Import</option>
    </optgroup>
    <option value="statistics">Statistics</option>
    <option value="rss_import">Newsfeed Import</option>
    <optgroup label="Other">
        <option value="backup_restore">Backup/Restore</option>
        <option value="settings">Settings</option>
        <option value="text_to_speech_settings">Text-to-Speech Settings</option>
        <option value="INFO">Help</option>
    </optgroup>
</select>
    <?php
}

/**
 * Start a page without connecting to the database with a complete header and a non-closed body.
 *
 * @param string $title  Title of the page
 * @param string $addcss Some CSS to be embed in a style tag
 *
 * @global string $tbpref The database table prefix if true
 * @global int    $debug  Show the requests if true
 */
function pagestart_kernel_nobody($title, $addcss=''): void
{
    global $tbpref, $debug;
    @header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
    @header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    @header('Cache-Control: no-cache, must-revalidate, max-age=0');
    @header('Pragma: no-cache');
    ?><!DOCTYPE html>
    <?php
    echo '<html lang="en">';
    ?>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <!--
        <?php echo file_get_contents("UNLICENSE.md");?>
    -->
    <meta name="viewport" content="width=900" />
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon"/>
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon-57x57.png" />
    <link rel="apple-touch-icon" sizes="72x72" href="/assets/images/apple-touch-icon-72x72.png" />
    <link rel="apple-touch-icon" sizes="114x114" href="/assets/images/apple-touch-icon-114x114.png" />
    <link rel="apple-touch-startup-image" href="/assets/images/apple-touch-startup.png" />
    <meta name="apple-mobile-web-app-capable" content="yes" />

    <link rel="stylesheet" type="text/css" href="/assets/css/jquery-ui.css" />
    <link rel="stylesheet" type="text/css" href="/assets/css/jquery.tagit.css" />
    <link rel="stylesheet" type="text/css" href="/assets/css/styles.css" />
    <link rel="stylesheet" type="text/css" href="/assets/css/feed_wizard.css" />
    <style type="text/css">
        <?php echo $addcss . "\n"; ?>
    </style>

    <script type="text/javascript" src="/assets/js/jquery.js" charset="utf-8"></script>
    <script type="text/javascript" src="/assets/js/jquery.scrollTo.min.js" charset="utf-8"></script>
    <script type="text/javascript" src="/assets/js/jquery-ui.min.js"  charset="utf-8"></script>
    <script type="text/javascript" src="/assets/js/jquery.jeditable.mini.js" charset="utf-8"></script>
    <script type="text/javascript" src="/assets/js/tag-it.js" charset="utf-8"></script>
    <script type="text/javascript" src="/assets/js/overlib/overlib_mini.js" charset="utf-8"></script>
    <!-- URLBASE : "<?php echo tohtml(url_base()); ?>" -->
    <!-- TBPREF  : "<?php echo tohtml($tbpref);  ?>" -->
    <script type="text/javascript">
        //<![CDATA[
        var STATUSES = <?php echo json_encode(get_statuses()); ?>;
        //]]>
    </script>

    <title>LWT :: <?php echo tohtml($title); ?></title>
</head>
    <?php
    echo '<body>';
    ?>
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
    <?php
    flush();
    if ($debug) {
        showRequest();
    }
}

/**
 * Add a closing body tag.
 *
 * @global bool $debug Show the requests if true
 * @global float $dspltime Total execution time since the PHP session started
 */
function pageend(): void
{
    global $debug, $dspltime;
    if ($debug) {
        showRequest();
    }
    if ($dspltime) {
        echo "\n<p class=\"smallgray2\">" .
        round(get_execution_time(), 5) . " secs</p>\n";
    }
    echo '</body></html>';
}

/**
 * Debug function only.
 *
 * @param mixed  $var  A printable variable to debug
 * @param string $text Echoed text in HTML page
 *
 * @global bool $debug This functions doesn't do anything is $debug is false.
 */
function echodebug($var,$text): void
{
    global $debug;
    if ($debug) {
        echo "<pre> **DEBUGGING** " . tohtml($text) . ' = [[[';
        print_r($var);
        echo "]]]\n--------------</pre>";
    }
}


/**
 * Return an associative array of all possible statuses
 *
 * @return array<int<1, 5>|98|99, array{string, string}>
 * Statuses, keys are 1, 2, 3, 4, 5, 98, 99.
 * Values are associative arrays of keys abbr and name
 */
function get_statuses()
{
    static $statuses;
    if (!$statuses) {
        $statuses = array(
        1 => array("abbr" =>   "1", "name" => "Learning"),
        2 => array("abbr" =>   "2", "name" => "Learning"),
        3 => array("abbr" =>   "3", "name" => "Learning"),
        4 => array("abbr" =>   "4", "name" => "Learning"),
        5 => array("abbr" =>   "5", "name" => "Learned"),
        99 => array("abbr" => "WKn", "name" => "Well Known"),
        98 => array("abbr" => "Ign", "name" => "Ignored"),
        );
    }
    return $statuses;
}

/**
 * Replace the first occurence of $needle in $haystack by $replace
 *
 * @param  string $needle   Text to replace
 * @param  string $replace  Text to replace by
 * @param  string $haystack Input string
 * @return string String with replaced text
 */
function str_replace_first($needle, $replace, $haystack)
{
    if ($needle === '') {
        return $haystack;
    }
    $pos = strpos($haystack, $needle);
    if ($pos !== false) {
        return substr_replace($haystack, $replace, $pos, strlen($needle));
    }
    return $haystack;
}

/**
 * Convert annotations in a JSON format.
 *
 * @param string $ann Annotations.
 *
 * @return string A JSON-encoded version of the annotations
 */
function annotation_to_json($ann): string|false
{
    if ($ann == '') {
        return "{}";
    }
    $arr = array();
    $items = preg_split('/[\n]/u', $ann);
    foreach ($items as $item) {
        $vals = preg_split('/[\t]/u', $item);
        if (count($vals) > 3 && $vals[0] >= 0 && $vals[2] > 0) {
            $arr[intval($vals[0])-1] = array($vals[1], $vals[2], $vals[3]);
        }
    }
    $json_data = json_encode($arr);
    if ($json_data === false) {
        my_die("Unable to format to JSON");
    }
    return $json_data;
}

/**
 * Get a request when possible. Otherwise, return an empty string.
 *
 * @param  string $s Request key
 * @return string Trimmed request or empty string
 */
function getreq($s)
{
    if (isset($_REQUEST[$s])) {
        return trim($_REQUEST[$s]);
    }
    return '';
}

/**
 * Get a session variable when possible. Otherwise, return an empty string.
 *
 * @param  string $s Session variable key
 * @return string Trimmed sesseion variable or empty string
 */
function getsess($s)
{
    if (isset($_SESSION[$s])) {
        $value = $_SESSION[$s];
        if (is_array($value)) {
            return '';
        }
        return trim((string)$value);
    }
    return '';
}

/**
 * Get the base URL of the application
 *
 * @return string base URL
 */
function url_base(): string
{
    // Detect if using HTTPS
    $scheme = 'http';
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $scheme = 'https';
    }

    $url = parse_url("$scheme://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
    $r = $url["scheme"] . "://" . $url["host"];
    if (isset($url["port"])) {
        $r .= ":" . $url["port"];
    }
    if(isset($url["path"])) {
        $b = basename($url["path"]);
        if (substr($b, -4) == ".php" || substr($b, -4) == ".htm" || substr($b, -5) == ".html") {
            $r .= dirname($url["path"]);
        }
        else {
            $r .= $url["path"];
        }
    }
    if (substr($r, -1) !== "/") {
        $r .= "/";
    }
    return $r;
}


/**
 * Make a random score for a new word.
 *
 * @param 'iv'|'id'|'u'|string $type Type of insertion
 *                                   * 'iv': Keys only (TodayScore, Tomorrow, Random)
 *                                   * 'id': Values only
 *                                   * 'u': Key = value pairs
 *
 * @return string SQL code to use
 */
function make_score_random_insert_update($type): string
{
    // $type='iv'/'id'/'u'
    if ($type == 'iv') {
        return ' WoTodayScore, WoTomorrowScore, WoRandom ';
    }
    if ($type == 'id') {
        return ' ' . getsqlscoreformula(2) . ', ' . getsqlscoreformula(3) . ', RAND() ';
    }
    if ($type == 'u') {
        return ' WoTodayScore = ' . getsqlscoreformula(2) . ', WoTomorrowScore = ' . getsqlscoreformula(3) . ', WoRandom = RAND() ';
    }
    return '';
}

/**
 * SQL formula for computing score.
 *
 * @param int $method Score for tomorrow (2), the day after it (3) or never (any value).
 *
 * @return string SQL score computation string
 *
 * @psalm-return '
        GREATEST(-125, CASE
            WHEN WoStatus > 5 THEN 100
            WHEN WoStatus = 1 THEN ROUND(-7 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 2 THEN ROUND(6.9 - 3.5 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 3 THEN ROUND(20 - 2.3 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 4 THEN ROUND(46.4 - 1.75 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 5 THEN ROUND(100 - 1.4 * DATEDIFF(NOW(),WoStatusChanged))
        END)'|'
        GREATEST(-125, CASE
            WHEN WoStatus > 5 THEN 100
            WHEN WoStatus = 1 THEN ROUND(-7 -7 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 2 THEN ROUND(3.4 - 3.5 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 3 THEN ROUND(17.7 - 2.3 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 4 THEN ROUND(44.65 - 1.75 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 5 THEN ROUND(98.6 - 1.4 * DATEDIFF(NOW(),WoStatusChanged))
        END)'|'0'
 */
function getsqlscoreformula($method): string
{
    //
    // Formula: {{{2.4^{Status}+Status-Days-1} over Status -2.4} over 0.14325248}

    if ($method == 3) {
        return '
        GREATEST(-125, CASE
            WHEN WoStatus > 5 THEN 100
            WHEN WoStatus = 1 THEN ROUND(-7 -7 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 2 THEN ROUND(3.4 - 3.5 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 3 THEN ROUND(17.7 - 2.3 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 4 THEN ROUND(44.65 - 1.75 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 5 THEN ROUND(98.6 - 1.4 * DATEDIFF(NOW(),WoStatusChanged))
        END)';
    }
    if ($method == 2) {
        return '
        GREATEST(-125, CASE
            WHEN WoStatus > 5 THEN 100
            WHEN WoStatus = 1 THEN ROUND(-7 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 2 THEN ROUND(6.9 - 3.5 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 3 THEN ROUND(20 - 2.3 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 4 THEN ROUND(46.4 - 1.75 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 5 THEN ROUND(100 - 1.4 * DATEDIFF(NOW(),WoStatusChanged))
        END)';
    }
    return '0';
}


/**
 * Display a error message vanishing after a few seconds.
 *
 * @param string $msg    Message to display.
 * @param bool   $noback If true, don't display a button to go back
 *
 * @return string HTML-formatted string for an automating vanishing message.
 */
function error_message_with_hide($msg, $noback): string
{
    if (trim($msg) == '') {
        return '';
    }
    if (substr($msg, 0, 5) == "Error" ) {
        return '<p class="red">*** ' . tohtml($msg) . ' ***' .
        ($noback ?
        '' :
        '<br /><input type="button" value="&lt;&lt; Go back and correct &lt;&lt;" onclick="history.back();" />' ) .
        '</p>';
    }
    return '<p id="hide3" class="msgblue">+++ ' . tohtml($msg) . ' +++</p>';
}

/**
 * Get a two-letter language code from dictionary source language.
 *
 * @param string $url Input URL, usually Google Translate or LibreTranslate
 */
function langFromDict($url)
{
    if ($url == '') {
        return '';
    }
    $query = parse_url($url, PHP_URL_QUERY);
    if ($query === null) {
        return '';
    }
    parse_str($query, $parsed_query);
    if (array_key_exists("lwt_translator", $parsed_query)
        && $parsed_query["lwt_translator"] == "libretranslate"
    ) {
        return $parsed_query["source"] ?? "";
    }
    // Fallback to Google Translate
    return $parsed_query["sl"] ?? "";
}

/**
 * Get a two-letter language code from dictionary target language
 *
 * @param string $url Input URL, usually Google Translate or LibreTranslate
 */
function targetLangFromDict($url)
{
    if ($url == '') {
        return '';
    }
    $query = parse_url($url, PHP_URL_QUERY);
    if ($query === null) {
        return '';
    }
    parse_str($query, $parsed_query);
    if (array_key_exists("lwt_translator", $parsed_query)
        && $parsed_query["lwt_translator"] == "libretranslate"
    ) {
        return $parsed_query["target"] ?? "";
    }
    // Fallback to Google Translate
    return $parsed_query["tl"] ?? "";
}

/**
 * Parse a SQL file by returning an array of the different queries it contains.
 *
 * @param string $filename File name
 *
 * @return array
 */
function parseSQLFile($filename)
{
    $handle = @fopen($filename, 'r');
    if ($handle === false) {
        return array();
    }
    $queries_list = array();
    $curr_content = '';
    while ($stream = fgets($handle)) {
        // Skip comments
        if (str_starts_with($stream, '-- ')) {
            continue;
        }
        // Add stream to accumulator
        $curr_content .= $stream;
        // Get queries
        $queries = explode(';' . PHP_EOL, $curr_content);
        // Replace line by remainders of the last element (incomplete line)
        $curr_content = array_pop($queries);
        //var_dump("queries", $queries);
        foreach ($queries as $query) {
            $queries_list[] = trim($query);
        }
    }
    // Add final query if there's any remaining content
    if (!empty(trim($curr_content))) {
        $queries_list[] = trim($curr_content);
    }
    if (!feof($handle)) {
        // Throw error
    }
    fclose($handle);
    return $queries_list;
}

/*****************
 * Wrappers for PHP <8.0
********************/
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle)
    {
        return substr($haystack, 0, strlen($needle)) == $needle;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle)
    {
        return substr($haystack, strlen($haystack) - strlen($needle)) == $needle;
    }
}

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle)
    {
        return strpos($haystack, $needle) !== false;
    }
}

?>
