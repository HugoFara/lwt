<?php

/**
 * \file
 * \brief String manipulation utilities.
 *
 * Functions for string encoding, escaping, and transformation.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-string-utilities.html
 * @since    2.10.0-fork
 */

/**
 * @return string|string[]
 *
 * @psalm-return array<string>|string
 */
function remove_soft_hyphens(string $str): array|string
{
    return str_replace('­', '', $str);  // first '..' contains Softhyphen 0xC2 0xAD
}

/**
 * @return null|string|string[]
 *
 * @psalm-return array<string>|null|string
 */
function replace_supp_unicode_planes_char(string $s): array|string|null
{
    return preg_replace('/[\x{10000}-\x{10FFFF}]/u', "\xE2\x96\x88", $s);
    /* U+2588 = UTF8: E2 96 88 = FULL BLOCK = ⬛︎  */
}

function makeCounterWithTotal(int $max, int $num): string
{
    if ($max == 1) {
        return '';
    }
    if ($max < 10) {
        return $num . "/" . $max;
    }
    return substr(
        str_repeat("0", strlen((string)$max)) . $num,
        -strlen((string)$max)
    ) . "/" . $max;
}

function encodeURI(string $url): string
{
    $reserved = array(
    '%2D' => '-','%5F' => '_','%2E' => '.','%21' => '!',
    '%2A' => '*', '%27' => "'", '%28' => '(', '%29' => ')'
    );
    $unescaped = array(
    '%3B' => ';','%2C' => ',','%2F' => '/','%3F' => '?','%3A' => ':',
    '%40' => '@','%26' => '&','%3D' => '=','%2B' => '+','%24' => '$'
    );
    $score = array(
    '%23' => '#'
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
function get_file_path($filename): string
{
    $file = getSettingWithDefault('set-theme-dir') . preg_replace('/.*\//', '', $filename);
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
            0,
            1,
            'UTF-8'
        );
    }
    return $sepa;
}

function strToHex(string $string): string
{
    $hex = '';
    for ($i = 0; $i < strlen($string); $i++) {
        $h = dechex(ord($string[$i]));
        if (strlen($h) == 1) {
            $hex .= "0" . $h;
        } else {
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
    for ($i = 0; $i < $length; $i++) {
        $c = mb_substr($string, $i, 1, 'UTF-8');
        $o = ord($c);
        if (
            ($o < 48)
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
