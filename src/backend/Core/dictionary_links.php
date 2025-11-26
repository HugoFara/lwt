<?php

/**
 * \file
 * \brief Dictionary link generation utilities.
 *
 * Functions for creating and formatting dictionary lookup URLs.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-dictionary-links.html
 * @since    3.0.0
 */

require_once __DIR__ . '/database_connect.php';

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
            $url,
            $pos + mb_strlen($matches[0]),
            $pos2 - $pos - mb_strlen($matches[0])
        )
    );
    $r = substr($url, 0, $pos);
    $r .= urlencode(mb_convert_encoding($trm, $enc, 'UTF-8'));
    if ($pos2 + 3 < strlen($url)) {
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
    FROM ' . \Lwt\Core\Globals::table('languages') . '
    WHERE LgID = ' . $lang;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $wb1 = isset($record['LgDict1URI']) ? $record['LgDict1URI'] : "";
    $wb2 = isset($record['LgDict2URI']) ? $record['LgDict2URI'] : "";
    $wb3 = isset($record['LgGoogleTranslateURI']) ?
    $record['LgGoogleTranslateURI'] : "";
    mysqli_free_result($res);
    $r = '';
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

function makeOpenDictStrJS(string $url): string
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
    parse_str($parsed_url['query'] ?? '', $url_query);
    $popup = $popup || array_key_exists('lwt_popup', $url_query);
    if (
        str_starts_with($url, "ggl.php")
        || str_ends_with($parsed_url['path'] ?? '', "/ggl.php")
    ) {
        $url = str_replace('?', '?sent=1&', $url);
    }
    return '<span class="click" onclick="translateSentence' . ($popup ? '2' : '') . '(' .
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
    FROM " . \Lwt\Core\Globals::table('languages') . " WHERE LgID = $lang";
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

function makeDictLinks(int $lang, string $wordctljs): string
{
    $sql = 'SELECT LgDict1URI, LgDict2URI, LgGoogleTranslateURI
    FROM ' . \Lwt\Core\Globals::table('languages') . ' WHERE LgID = ' . $lang;
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
    $r = '<span class="smaller">';
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

function createDictLinksInEditWin3(int $lang, string $sentctljs, string $wordctljs): string
{
    $sql = "SELECT LgDict1URI, LgDict2URI, LgGoogleTranslateURI
    FROM " . \Lwt\Core\Globals::table('languages') . " WHERE LgID = $lang";
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
            (str_ends_with($parsed_url['path'] ?? '', "/ggl.php")) ?
            str_replace('?', '?sent=1&', $wb3) : $wb3
        );
    }

    mysqli_free_result($res);
    $r = '';
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
