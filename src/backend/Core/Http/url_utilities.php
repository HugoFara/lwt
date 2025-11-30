<?php declare(strict_types=1);
/**
 * \file
 * \brief URL handling utilities.
 *
 * Functions for parsing and manipulating URLs, including dictionary URL parsing.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-url-utilities.html
 * @since    3.0.0 Split from kernel_utility.php
 */

namespace Lwt\Core\Http;

/**
 * URL handling utilities.
 *
 * Provides methods for parsing and manipulating URLs, including dictionary URL parsing.
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class UrlUtilities
{
    /**
     * Get the base URL of the application
     *
     * @return string base URL
     */
    public static function urlBase(): string
    {
        // Detect if using HTTPS
        $scheme = 'http';
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $scheme = 'https';
        }

        $url = parse_url("$scheme://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/'));
        $r = ($url["scheme"] ?? $scheme) . "://" . ($url["host"] ?? 'localhost');
        if (isset($url["port"])) {
            $r .= ":" . $url["port"];
        }
        if (isset($url["path"])) {
            $b = basename($url["path"]);
            if (substr($b, -4) == ".php" || substr($b, -4) == ".htm" || substr($b, -5) == ".html") {
                $r .= dirname($url["path"]);
            } else {
                $r .= $url["path"];
            }
        }
        if (substr($r, -1) !== "/") {
            $r .= "/";
        }
        return $r;
    }

    /**
     * Get a two-letter language code from dictionary source language.
     *
     * @param string $url Input URL, usually Google Translate or LibreTranslate
     *
     * @return string The source language code or empty string
     */
    public static function langFromDict(string $url): string
    {
        if ($url == '') {
            return '';
        }
        $query = parse_url($url, PHP_URL_QUERY);
        if ($query === null) {
            return '';
        }
        parse_str($query, $parsed_query);
        if (
            array_key_exists("lwt_translator", $parsed_query)
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
     *
     * @return string The target language code or empty string
     */
    public static function targetLangFromDict(string $url): string
    {
        if ($url == '') {
            return '';
        }
        $query = parse_url($url, PHP_URL_QUERY);
        if ($query === null) {
            return '';
        }
        parse_str($query, $parsed_query);
        if (
            array_key_exists("lwt_translator", $parsed_query)
            && $parsed_query["lwt_translator"] == "libretranslate"
        ) {
            return $parsed_query["target"] ?? "";
        }
        // Fallback to Google Translate
        return $parsed_query["tl"] ?? "";
    }
}
