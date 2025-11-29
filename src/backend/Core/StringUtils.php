<?php

/**
 * \file
 * \brief String manipulation utilities.
 *
 * Static methods for string encoding, escaping, and transformation.
 *
 * PHP version 8.1
 *
 * @category Core
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Core;

use Lwt\Database\Settings;

/**
 * String manipulation utilities.
 *
 * Provides static methods for encoding strings, creating CSS class names,
 * and handling translation separators.
 *
 * @since 3.0.0
 */
class StringUtils
{
    /**
     * Cached separators value (preg_quote'd).
     *
     * @var string|null
     */
    private static ?string $separators = null;

    /**
     * Cached first separator character.
     *
     * @var string|null
     */
    private static ?string $firstSeparator = null;

    /**
     * Convert a string to hexadecimal representation.
     *
     * @param string $string String to convert
     *
     * @return string Uppercase hexadecimal string
     */
    public static function toHex(string $string): string
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
     * Escape a string for use as a CSS class name.
     *
     * Escapes everything to "¤xx" (where xx is hex) except:
     * - 0-9 (ASCII 48-57)
     * - a-z (ASCII 97-122)
     * - A-Z (ASCII 65-90)
     * - Unicode characters >= 165 (hex 00A5)
     *
     * @param string $string String to escape
     *
     * @return string CSS-safe class name
     */
    public static function toClassName(string $string): string
    {
        $length = mb_strlen($string, 'UTF-8');
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($string, $i, 1, 'UTF-8');
            $ord = ord($char);
            if (
                ($ord < 48)
                || ($ord > 57 && $ord < 65)
                || ($ord > 90 && $ord < 97)
                || ($ord > 122 && $ord < 165)
            ) {
                $result .= '¤' . self::toHex($char);
            } else {
                $result .= $char;
            }
        }
        return $result;
    }

    /**
     * Get the translation separators pattern for regex use.
     *
     * Returns the separator characters from settings, escaped for use
     * in preg_split and similar functions.
     *
     * @return string Preg-quoted separator characters
     */
    public static function getSeparators(): string
    {
        if (self::$separators === null) {
            self::$separators = preg_quote(
                Settings::getWithDefault('set-term-translation-delimiters'),
                '/'
            );
        }
        return self::$separators;
    }

    /**
     * Get the first translation separator character.
     *
     * @return string First separator character
     */
    public static function getFirstSeparator(): string
    {
        if (self::$firstSeparator === null) {
            self::$firstSeparator = mb_substr(
                Settings::getWithDefault('set-term-translation-delimiters'),
                0,
                1,
                'UTF-8'
            );
        }
        return self::$firstSeparator;
    }

    /**
     * Reset cached separator values.
     *
     * Call this if settings change during runtime.
     *
     * @return void
     */
    public static function resetSeparatorCache(): void
    {
        self::$separators = null;
        self::$firstSeparator = null;
    }
}
