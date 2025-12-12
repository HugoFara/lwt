<?php declare(strict_types=1);
/**
 * \file
 * \brief String manipulation utilities.
 *
 * Functions for string encoding, escaping, and transformation.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Core\Utils
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-string-utilities.html
 * @since    3.0.0
 */

namespace Lwt\Core\Utils {

    require_once __DIR__ . '/../Database/Settings.php';

    use Lwt\Database\Settings;

    /**
     * Remove soft hyphens from a string.
     *
     * @param string $str Input string
     *
     * @return array|string String without soft hyphens
     *
     * @psalm-return array<string>|string
     */
    function removeSoftHyphens(string $str): array|string
    {
        return \str_replace('­', '', $str);  // first '..' contains Softhyphen 0xC2 0xAD
    }

    /**
     * Create a counter string with total (e.g., "01/10").
     *
     * @param int $max Total count
     * @param int $num Current number
     *
     * @return string Formatted counter string
     */
    function makeCounterWithTotal(int $max, int $num): string
    {
        if ($max == 1) {
            return '';
        }
        if ($max < 10) {
            return $num . "/" . $max;
        }
        return \substr(
            \str_repeat("0", \strlen((string)$max)) . $num,
            -\strlen((string)$max)
        ) . "/" . $max;
    }

    /**
     * Encode a URI string.
     *
     * @param string $url URL to encode
     *
     * @return string Encoded URL
     */
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
        return \strtr(\rawurlencode($url), \array_merge($reserved, $unescaped, $score));
    }

    /**
     * Echo the path of a file using the theme directory.
     *
     * @param string $filename Filename
     *
     * @return void
     */
    function printFilePath($filename): void
    {
        echo getFilePath($filename);
    }

    /**
     * Get the path of a file using the theme directory.
     *
     * Maps legacy paths to new asset locations:
     * - css/* -> assets/css/*
     * - icn/* -> assets/icons/*
     * - img/* -> assets/images/*
     * - js/* -> assets/js/*
     *
     * @param string $filename Filename
     *
     * @return string File path if it exists, otherwise the filename
     */
    function getFilePath($filename): string
    {
        // Legacy path mappings
        $mappings = [
            'css/' => 'assets/css/',
            'icn/' => 'assets/icons/',
            'img/' => 'assets/images/',
            'js/' => 'assets/js/',
            'sounds/' => 'assets/sounds/',
        ];

        // Normalize the path (remove leading slash if present)
        $normalizedPath = \ltrim($filename, '/');

        // Apply legacy path mappings
        foreach ($mappings as $oldPrefix => $newPrefix) {
            if (\str_starts_with($normalizedPath, $oldPrefix)) {
                $normalizedPath = $newPrefix . \substr($normalizedPath, \strlen($oldPrefix));
                break;
            }
        }

        // Check if theme has an override for this file (for CSS/icons)
        $themeDir = Settings::getWithDefault('set-theme-dir');
        if ($themeDir) {
            $basename = \basename($normalizedPath);
            $themePath = $themeDir . $basename;
            if (\file_exists($themePath)) {
                // Return absolute path for clean URL compatibility
                return '/' . $themePath;
            }
        }

        // Check if the file exists at the normalized path
        if (\file_exists($normalizedPath)) {
            return '/' . $normalizedPath;
        }

        // Return the normalized path even if file doesn't exist
        // (let the browser/router handle 404)
        return '/' . $normalizedPath;
    }

    /**
     * Remove all spaces from a string.
     *
     * @param string      $s      Input string
     * @param string|bool $remove Do not do anything if empty or false
     *
     * @return string String without spaces if requested.
     */
    function removeSpaces($s, $remove)
    {
        if (!$remove) {
            return $s;
        }
        // '' enthält &#x200B;
        return \str_replace(' ', '', $s);
    }

    /**
     * Replace the first occurence of $needle in $haystack by $replace.
     *
     * @param string $needle   Text to replace
     * @param string $replace  Text to replace by
     * @param string $haystack Input string
     *
     * @return string String with replaced text
     */
    function strReplaceFirst($needle, $replace, $haystack)
    {
        if ($needle === '') {
            return $haystack;
        }
        $pos = \strpos($haystack, $needle);
        if ($pos !== false) {
            return \substr_replace($haystack, $replace, $pos, \strlen($needle));
        }
        return $haystack;
    }
}

// =============================================================================
// Global function aliases for backward compatibility
// =============================================================================

namespace {
    if (!\function_exists('makeCounterWithTotal')) {
        /**
         * @deprecated Use \Lwt\Core\Utils\makeCounterWithTotal() instead
         */
        function makeCounterWithTotal(int $max, int $num): string
        {
            return \Lwt\Core\Utils\makeCounterWithTotal($max, $num);
        }
    }
}
