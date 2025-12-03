<?php declare(strict_types=1);
/**
 * Dictionary Service - Dictionary link generation utilities
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Services;

require_once __DIR__ . '/../Core/Utils/string_utilities.php';

use Lwt\Core\Globals;
use Lwt\Database\Connection;

/**
 * Service class for dictionary link operations.
 *
 * Functions for creating and formatting dictionary lookup URLs.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class DictionaryService
{
    private string $tbpref;

    public function __construct()
    {
        $this->tbpref = Globals::getTablePrefix();
    }

    /**
     * Create and verify a dictionary URL link.
     *
     * Case 1: url without any ### or lwt_term: append UTF-8-term
     * Case 2: url with one ### or lwt_term: substitute UTF-8-term
     * Case 3: url with two (###|lwt_term)enc###: unsupported encoding changed,
     *         abandoned since 2.6.0-fork
     *
     * @param string $u Dictionary URL. It may contain 'lwt_term' that will get parsed
     * @param string $t Text that substitutes the 'lwt_term'
     *
     * @return string Dictionary link formatted
     *
     * @since 2.7.0-fork It is recommended to use "lwt_term" instead of "###"
     */
    public static function createTheDictLink(string $u, string $t): string
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
     * Returns dictionary links formatted as HTML.
     *
     * @param int    $lang      Language ID
     * @param string $word      Word to look up
     * @param string $sentctlid ID of the sentence textarea element
     * @param bool   $openfirst True if we should open right frames with translation first
     *
     * @return string HTML-formatted interface
     */
    public function createDictLinksInEditWin(int $lang, string $word, string $sentctlid, bool $openfirst): string
    {
        $sql = 'SELECT LgDict1URI, LgDict2URI, LgGoogleTranslateURI
        FROM ' . $this->tbpref . 'languages
        WHERE LgID = ' . $lang;
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        $wb1 = isset($record['LgDict1URI']) ? (string) $record['LgDict1URI'] : "";
        $wb2 = isset($record['LgDict2URI']) ? (string) $record['LgDict2URI'] : "";
        $wb3 = isset($record['LgGoogleTranslateURI']) ?
        (string) $record['LgGoogleTranslateURI'] : "";
        mysqli_free_result($res);
        $r = '';
        $dictUrl = self::createTheDictLink($wb1, $word);
        if ($openfirst) {
            // Use data attribute for auto-init instead of inline script
            $popup = str_starts_with($wb1, '*') || str_contains($wb1, 'lwt_popup=');
            $action = $popup ? 'dict-auto-popup' : 'dict-auto-frame';
            $r .= '<span class="dict-auto-init" data-action="' . $action . '" data-url="' .
            htmlspecialchars($dictUrl, ENT_QUOTES, 'UTF-8') . '"></span>';
        }
        $r .= 'Lookup Term: ';
        $r .= $this->makeOpenDictStr($dictUrl, "Dict1");
        if ($wb2 != "") {
            $r .= $this->makeOpenDictStr(self::createTheDictLink($wb2, $word), "Dict2");
        }
        if ($wb3 != "") {
            $r .= $this->makeOpenDictStr(self::createTheDictLink($wb3, $word), "Translator") .
            ' | ' .
            $this->makeOpenDictStrDynSent($wb3, $sentctlid, "Translate sentence");
        }
        return $r;
    }

    /**
     * Create a dictionary open URL from a pseudo-URL.
     *
     * @param string $url An URL, starting with a "*" is deprecated.
     *                    * If it contains a "popup" query, open in new window
     *                    * Otherwise open in iframe
     * @param string $txt Clickable text to display
     *
     * @return string HTML-formatted string
     */
    public function makeOpenDictStr(string $url, string $txt): string
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
            $r = ' <span class="click" data-action="dict-popup" data-url="' .
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' .
            htmlspecialchars($txt, ENT_QUOTES, 'UTF-8') .
            '</span> ';
        } else {
            $r = ' <a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') .
            '" target="ru" data-action="dict-frame">' .
            htmlspecialchars($txt, ENT_QUOTES, 'UTF-8') . '</a> ';
        }
        return $r;
    }

    /**
     * Create a dictionary open URL from a pseudo-URL for dynamic sentence.
     *
     * @param string $url       A string containing at least a URL
     *                          * If it contains the query "lwt_popup", open in Popup
     *                          * Starts with a '*': open in pop-up window (deprecated)
     *                          * Otherwise open in iframe
     * @param string $sentctlid ID of the textarea element containing the sentence
     * @param string $txt       Clickable text to display
     *
     * @return string HTML-formatted string
     *
     * @since 2.7.0-fork Supports LibreTranslate, using other string that proper URL is
     *                   deprecated.
     */
    public function makeOpenDictStrDynSent(string $url, string $sentctlid, string $txt): string
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
        $action = $popup ? 'translate-sentence-popup' : 'translate-sentence';
        return '<span class="click" data-action="' . $action . '" ' .
        'data-url="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" ' .
        'data-sentctl="' . htmlspecialchars($sentctlid, ENT_QUOTES, 'UTF-8') . '">' .
        htmlspecialchars($txt, ENT_QUOTES, 'UTF-8') . '</span>';
    }

    /**
     * Returns dictionary links formatted as HTML (alternate version).
     *
     * @param int    $lang      Language ID
     * @param string $sentctlid ID of the sentence textarea element
     * @param string $wordctlid ID of the word input element
     *
     * @return string HTML formatted interface
     */
    public function createDictLinksInEditWin2(int $lang, string $sentctlid, string $wordctlid): string
    {
        $sql = "SELECT LgDict1URI, LgDict2URI, LgGoogleTranslateURI
        FROM " . $this->tbpref . "languages WHERE LgID = $lang";
        $res = Connection::query($sql);
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
        <span class="click" data-action="translate-word-popup" ' .
        'data-url="' . htmlspecialchars($wb1, ENT_QUOTES, 'UTF-8') . '" ' .
        'data-wordctl="' . htmlspecialchars($wordctlid, ENT_QUOTES, 'UTF-8') . '">Dict1</span> ';
        if ($wb2 != "") {
            $r .= '<span class="click" data-action="translate-word-popup" ' .
            'data-url="' . htmlspecialchars($wb2, ENT_QUOTES, 'UTF-8') . '" ' .
            'data-wordctl="' . htmlspecialchars($wordctlid, ENT_QUOTES, 'UTF-8') . '">Dict2</span> ';
        }
        if ($wb3 != "") {
            $sent_mode = substr($wb3, 0, 7) == 'ggl.php' ||
            str_ends_with(parse_url($wb3, PHP_URL_PATH) ?? '', '/ggl.php');
            $sentUrl = $sent_mode ? str_replace('?', '?sent=1&', $wb3) : $wb3;
            $r .= '<span class="click" data-action="translate-word-popup" ' .
            'data-url="' . htmlspecialchars($wb3, ENT_QUOTES, 'UTF-8') . '" ' .
            'data-wordctl="' . htmlspecialchars($wordctlid, ENT_QUOTES, 'UTF-8') . '">Translator</span>
             | <span class="click" data-action="translate-sentence-popup" ' .
            'data-url="' . htmlspecialchars($sentUrl, ENT_QUOTES, 'UTF-8') . '" ' .
            'data-sentctl="' . htmlspecialchars($sentctlid, ENT_QUOTES, 'UTF-8') . '">Translate sentence</span>';
        }
        return $r;
    }

    /**
     * Make dictionary links HTML.
     *
     * @param int    $lang Language ID
     * @param string $word The word to translate
     *
     * @return string HTML formatted links
     */
    public function makeDictLinks(int $lang, string $word): string
    {
        $sql = 'SELECT LgDict1URI, LgDict2URI, LgGoogleTranslateURI
        FROM ' . $this->tbpref . 'languages WHERE LgID = ' . $lang;
        $res = Connection::query($sql);
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
        $escapedWord = htmlspecialchars($word, ENT_QUOTES, 'UTF-8');
        $r = '<span class="smaller">';
        $r .= '<span class="click" data-action="translate-word-direct" ' .
        'data-url="' . htmlspecialchars($wb1, ENT_QUOTES, 'UTF-8') . '" ' .
        'data-word="' . $escapedWord . '">[1]</span> ';
        if ($wb2 != "") {
            $r .= '<span class="click" data-action="translate-word-direct" ' .
            'data-url="' . htmlspecialchars($wb2, ENT_QUOTES, 'UTF-8') . '" ' .
            'data-word="' . $escapedWord . '">[2]</span> ';
        }
        if ($wb3 != "") {
            $r .= '<span class="click" data-action="translate-word-direct" ' .
            'data-url="' . htmlspecialchars($wb3, ENT_QUOTES, 'UTF-8') . '" ' .
            'data-word="' . $escapedWord . '">[G]</span>';
        }
        $r .= '</span>';
        return $r;
    }

    /**
     * Create dictionary links for edit window (version 3).
     *
     * @param int    $lang      Language ID
     * @param string $sentctlid ID of the sentence textarea element
     * @param string $wordctlid ID of the word input element
     *
     * @return string HTML formatted interface
     */
    public function createDictLinksInEditWin3(int $lang, string $sentctlid, string $wordctlid): string
    {
        $sql = "SELECT LgDict1URI, LgDict2URI, LgGoogleTranslateURI
        FROM " . $this->tbpref . "languages WHERE LgID = $lang";
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);

        $wb1 = isset($record['LgDict1URI']) ? (string) $record['LgDict1URI'] : "";
        $popup1 = false;
        if (substr($wb1, 0, 1) == '*') {
            $wb1 = substr($wb1, 1);
            $popup1 = true;
        }
        $popup1 = $popup1 || str_contains($wb1, "lwt_popup=");

        $wb2 = isset($record['LgDict2URI']) ? (string) $record['LgDict2URI'] : "";
        $popup2 = false;
        if (substr($wb2, 0, 1) == '*') {
            $wb2 = substr($wb2, 1);
            $popup2 = true;
        }
        $popup2 = $popup2 || str_contains($wb2, "lwt_popup=");

        $wb3 = isset($record['LgGoogleTranslateURI']) ?
        (string) $record['LgGoogleTranslateURI'] : "";
        $popup3 = false;
        if (substr($wb3, 0, 1) == '*') {
            $wb3 = substr($wb3, 1);
            $popup3 = true;
        }
        $parsed_url = parse_url($wb3);
        if ($wb3 != '' && $parsed_url === false) {
            $prefix = 'http://';
            $parsed_url = parse_url($prefix . $wb3);
        }
        if (is_array($parsed_url) && array_key_exists('query', $parsed_url)) {
            parse_str($parsed_url['query'], $url_query);
            $popup3 = $popup3 || array_key_exists('lwt_popup', $url_query);
        }
        $sentUrl = (str_ends_with($parsed_url['path'] ?? '', "/ggl.php")) ?
            str_replace('?', '?sent=1&', $wb3) : $wb3;

        mysqli_free_result($res);
        $r = '';
        $r .= 'Lookup Term: ';
        $action1 = $popup1 ? 'translate-word-popup' : 'translate-word';
        $r .= '<span class="click" data-action="' . $action1 . '" ' .
        'data-url="' . htmlspecialchars($wb1, ENT_QUOTES, 'UTF-8') . '" ' .
        'data-wordctl="' . htmlspecialchars($wordctlid, ENT_QUOTES, 'UTF-8') . '">
        Dict1</span> ';
        if ($wb2 != "") {
            $action2 = $popup2 ? 'translate-word-popup' : 'translate-word';
            $r .= '<span class="click" data-action="' . $action2 . '" ' .
            'data-url="' . htmlspecialchars($wb2, ENT_QUOTES, 'UTF-8') . '" ' .
            'data-wordctl="' . htmlspecialchars($wordctlid, ENT_QUOTES, 'UTF-8') . '">
            Dict2</span> ';
        }
        if ($wb3 != "") {
            $action3 = $popup3 ? 'translate-word-popup' : 'translate-word';
            $action4 = $popup3 ? 'translate-sentence-popup' : 'translate-sentence';
            $r .= '<span class="click" data-action="' . $action3 . '" ' .
            'data-url="' . htmlspecialchars($wb3, ENT_QUOTES, 'UTF-8') . '" ' .
            'data-wordctl="' . htmlspecialchars($wordctlid, ENT_QUOTES, 'UTF-8') . '">
            Translator</span> |
            <span class="click" data-action="' . $action4 . '" ' .
            'data-url="' . htmlspecialchars($sentUrl, ENT_QUOTES, 'UTF-8') . '" ' .
            'data-sentctl="' . htmlspecialchars($sentctlid, ENT_QUOTES, 'UTF-8') . '">
            Translate sentence</span>';
        }
        return $r;
    }

    /**
     * Get dictionary URIs for a language.
     *
     * @param int $langId Language ID
     *
     * @return array{dict1: string, dict2: string, translator: string}
     */
    public function getLanguageDictionaries(int $langId): array
    {
        $sql = 'SELECT LgDict1URI, LgDict2URI, LgGoogleTranslateURI
        FROM ' . $this->tbpref . 'languages WHERE LgID = ' . $langId;
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);

        return [
            'dict1' => $record['LgDict1URI'] ?? '',
            'dict2' => $record['LgDict2URI'] ?? '',
            'translator' => $record['LgGoogleTranslateURI'] ?? '',
        ];
    }
}
