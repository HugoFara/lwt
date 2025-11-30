<?php declare(strict_types=1);
/**
 * Text Parsing Service - Text parsing utilities.
 *
 * Functions for parsing text, including MeCab integration for Japanese
 * and sentence boundary detection for Latin scripts.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0 Migrated from Core/Text/text_parsing.php
 */

namespace Lwt\Services {

use Lwt\Core\Utils\ErrorHandler;

/**
 * Service class for text parsing operations.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class TextParsingService
{
    /**
     * Returns path to the MeCab application.
     * MeCab can split Japanese text word by word
     *
     * @param string $mecabArgs Arguments to add
     *
     * @return string OS-compatible command
     *
     * @since 2.3.1-fork Much more verifications added
     * @since 3.0.0 Support for Mac OS added
     */
    public function getMecabPath(string $mecabArgs = ''): string
    {
        $os = strtoupper(PHP_OS);
        $mecabArgs = escapeshellcmd($mecabArgs);
        if (str_starts_with($os, 'LIN') || str_starts_with($os, 'DAR')) {
            if (shell_exec("command -v mecab")) {
                return 'mecab' . $mecabArgs;
            }
            ErrorHandler::die(
                "MeCab not detected! " .
                "Please install it or add it to your PATH (see documentation)."
            );
        }
        if (str_starts_with($os, 'WIN')) {
            if (shell_exec('where /R "%ProgramFiles%\\MeCab\\bin" mecab.exe')) {
                return '"%ProgramFiles%\\MeCab\\bin\\mecab.exe"' . $mecabArgs;
            }
            if (shell_exec('where /R "%ProgramFiles(x86)%\\MeCab\\bin" mecab.exe')) {
                return '"%ProgramFiles(x86)%\\MeCab\\bin\\mecab.exe"' . $mecabArgs;
            }
            if (shell_exec('where mecab.exe')) {
                return 'mecab.exe' . $mecabArgs;
            }
            ErrorHandler::die(
                "MeCab not detected! " .
                "Install it or add it to the PATH (see documentation)."
            );
        }
        ErrorHandler::die("Your OS '$os' cannot use MeCab with this version of LWT!");
    }

    /**
     * Find end-of-sentence characters in a sentence using latin alphabet.
     *
     * @param string[] $matches       All the matches from a capturing regex
     * @param string   $noSentenceEnd If different from '', can declare that a string is not the end of a sentence.
     *
     * @return string $matches[0] with ends of sentences marked with \t and \r.
     */
    public function findLatinSentenceEnd(array $matches, string $noSentenceEnd): string
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
        } elseif (
            $matches[3] && (preg_match('/^[B-DF-HJ-NP-TV-XZb-df-hj-np-tv-xz][b-df-hj-np-tv-xzñ]*$/u', $matches[1]) || preg_match('/^[AEIOUY]$/', $matches[1]))
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
}

} // End namespace Lwt\Services

namespace {

// =============================================================================
// GLOBAL FUNCTION WRAPPERS (for backward compatibility)
// =============================================================================

use Lwt\Services\TextParsingService;

/**
 * Returns path to the MeCab application.
 * MeCab can split Japanese text word by word
 *
 * @param string $mecab_args Arguments to add
 *
 * @return string OS-compatible command
 *
 * @see TextParsingService::getMecabPath()
 */
function get_mecab_path(string $mecab_args = ''): string
{
    $service = new TextParsingService();
    return $service->getMecabPath($mecab_args);
}

/**
 * Find end-of-sentence characters in a sentence using latin alphabet.
 *
 * @param string[] $matches       All the matches from a capturing regex
 * @param string   $noSentenceEnd If different from '', can declare that a string is not the end of a sentence.
 *
 * @return string $matches[0] with ends of sentences marked with \t and \r.
 *
 * @see TextParsingService::findLatinSentenceEnd()
 */
function find_latin_sentence_end(array $matches, string $noSentenceEnd): string
{
    $service = new TextParsingService();
    return $service->findLatinSentenceEnd($matches, $noSentenceEnd);
}

} // End global namespace
