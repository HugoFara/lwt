<?php declare(strict_types=1);
/**
 * \file
 * \brief String manipulation utilities - DEPRECATED wrappers.
 *
 * All functions in this file are deprecated and delegate to StringUtils class.
 * Use Lwt\Core\StringUtils methods directly instead.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Core\Utils
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-string-utilities.html
 * @since    3.0.0
 * @deprecated Use Lwt\Core\StringUtils class methods instead
 */

namespace Lwt\Core\Utils;

require_once __DIR__ . '/../StringUtils.php';

use Lwt\Core\StringUtils;

/**
 * Remove soft hyphens from a string.
 *
 * @param string $str Input string
 *
 * @return string String without soft hyphens
 *
 * @deprecated Use StringUtils::removeSoftHyphens() instead
 */
function removeSoftHyphens(string $str): array|string
{
    return StringUtils::removeSoftHyphens($str);
}

/**
 * Create a counter string with total (e.g., "01/10").
 *
 * @param int $max Total count
 * @param int $num Current number
 *
 * @return string Formatted counter string
 *
 * @deprecated Use StringUtils::makeCounterWithTotal() instead
 */
function makeCounterWithTotal(int $max, int $num): string
{
    return StringUtils::makeCounterWithTotal($max, $num);
}

/**
 * Encode a URI string.
 *
 * @param string $url URL to encode
 *
 * @return string Encoded URL
 *
 * @deprecated Use StringUtils::encodeURI() instead
 */
function encodeURI(string $url): string
{
    return StringUtils::encodeURI($url);
}

/**
 * Echo the path of a file using the theme directory.
 *
 * @param string $filename Filename
 *
 * @return void
 *
 * @deprecated Use StringUtils::printFilePath() instead
 */
function printFilePath($filename): void
{
    StringUtils::printFilePath((string)$filename);
}

/**
 * Get the path of a file using the theme directory.
 *
 * @param string $filename Filename
 *
 * @return string File path if it exists, otherwise the filename
 *
 * @deprecated Use StringUtils::getFilePath() instead
 */
function getFilePath($filename): string
{
    return StringUtils::getFilePath((string)$filename);
}

/**
 * Remove all spaces from a string.
 *
 * @param string          $s      Input string
 * @param string|bool|int $remove Do not do anything if empty, false, or 0
 *
 * @return string String without spaces if requested.
 *
 * @deprecated Use StringUtils::removeSpaces() instead
 */
function removeSpaces($s, $remove): string
{
    return StringUtils::removeSpaces((string)$s, $remove);
}

/**
 * Replace the first occurence of $needle in $haystack by $replace.
 *
 * @param string $needle   Text to replace
 * @param string $replace  Text to replace by
 * @param string $haystack Input string
 *
 * @return string String with replaced text
 *
 * @deprecated Use StringUtils::replaceFirst() instead
 */
function strReplaceFirst($needle, $replace, $haystack): string
{
    return StringUtils::replaceFirst((string)$needle, (string)$replace, (string)$haystack);
}
