<?php

/**
 * \file
 * \brief Text parsing utilities.
 *
 * Functions for parsing text, including MeCab integration for Japanese
 * and sentence boundary detection for Latin scripts.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-text-parsing.html
 * @since    3.0.0 Split from kernel_utility.php
 */

/**
 * Returns path to the MeCab application.
 * MeCab can split Japanese text word by word
 *
 * @param string $mecab_args Arguments to add
 *
 * @return string OS-compatible command
 *
 * @since 2.3.1-fork Much more verifications added
 * @since 3.0.0 Support for Mac OS added
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
