<?php

/**
 * Psalm stub file for runtime-defined constants and global function wrappers.
 *
 * This file provides type information for:
 * 1. Constants defined at runtime (e.g., LWT_BASE_PATH in index.php)
 * 2. Global function wrappers that call namespaced implementations
 * 3. Namespaced functions that Psalm cannot trace through dynamic includes
 *
 * The namespaced implementations exist in the codebase and Psalm can trace them.
 * These stubs are for the global-namespace backward-compatibility wrappers.
 *
 * @psalm-suppress UnusedClass
 * @psalm-suppress UnusedMethod
 */

// =============================================================================
// NAMESPACED FUNCTIONS
// These are defined in files that Psalm cannot trace through dynamic includes
// =============================================================================

namespace Lwt\Core\Utils {
    /**
     * @return void
     */
    function showRequest(): void {}

    /**
     * @return float
     */
    function getExecutionTime(): float {}

    /**
     * @param mixed $var
     * @param string $text
     * @return void
     */
    function echodebug($var, $text): void {}

    /**
     * @param string $str
     * @return array|string
     */
    function remove_soft_hyphens(string $str): array|string {}

    /**
     * @param string $s
     * @return array|string|null
     */
    function replace_supp_unicode_planes_char(string $s): array|string|null {}

    /**
     * @param int $max
     * @param int $num
     * @return string
     */
    function makeCounterWithTotal(int $max, int $num): string {}

    /**
     * @param string $url
     * @return string
     */
    function encodeURI(string $url): string {}

    /**
     * @param string $filename
     * @return void
     */
    function print_file_path($filename): void {}

    /**
     * @param string $filename
     * @return string
     */
    function get_file_path($filename): string {}

    /**
     * @return string
     */
    function get_sepas(): string {}

    /**
     * @return string
     */
    function get_first_sepa(): string {}

    /**
     * @param string $string
     * @return string
     */
    function strToHex(string $string): string {}

    /**
     * @param string $string
     * @return string
     */
    function strToClassName($string): string {}

    /**
     * @param string $s
     * @param mixed $remove
     * @return string
     */
    function remove_spaces($s, $remove): string {}

    /**
     * @param string $needle
     * @param string $replace
     * @param string $haystack
     * @return string
     */
    function str_replace_first($needle, $replace, $haystack): string {}
}

namespace Lwt\Core {
    /**
     * @return string
     */
    function getVersion(): string {}

    /**
     * @return string
     */
    function getVersionNumber(): string {}
}

// =============================================================================
// GLOBAL NAMESPACE
// =============================================================================

namespace {

// =============================================================================
// CONSTANTS (defined at runtime in index.php)
// =============================================================================

define('LWT_BASE_PATH', __DIR__ . '/..');

// =============================================================================
// GLOBAL FUNCTION WRAPPERS
// These are backward-compatibility wrappers defined in various files.
// They delegate to namespaced implementations.
// =============================================================================

// ---------------------------------------------------------------------------
// From string_utilities.php (global namespace section)
// ---------------------------------------------------------------------------

/**
 * @param string $s
 * @param mixed $remove Value used in boolean context
 * @return string
 * @see \Lwt\Core\Utils\remove_spaces()
 */
function remove_spaces($s, $remove): string {}

/**
 * @param string $needle
 * @param string $replace
 * @param string $haystack
 * @return string
 * @see \Lwt\Core\Utils\str_replace_first()
 */
function str_replace_first($needle, $replace, $haystack): string {}

/**
 * @param string $string
 * @return string
 * @see \Lwt\Core\StringUtils::toClassName()
 */
function strToClassName($string): string {}

/**
 * @param string $filename
 * @return string
 * @see \Lwt\Core\Utils\get_file_path()
 */
function get_file_path($filename): string {}

/**
 * @param string $filename
 * @return void
 * @see \Lwt\Core\Utils\print_file_path()
 */
function print_file_path($filename): void {}

/**
 * @param string $url
 * @return string
 * @see \Lwt\Core\Utils\encodeURI()
 */
function encodeURI($url): string {}

/**
 * @return string
 * @see \Lwt\Core\Utils\get_sepas()
 */
function get_sepas(): string {}

/**
 * @return string
 * @see \Lwt\Core\Utils\get_first_sepa()
 */
function get_first_sepa(): string {}

/**
 * @param string $string
 * @return string
 * @see \Lwt\Core\Utils\strToHex()
 */
function strToHex(string $string): string {}

// ---------------------------------------------------------------------------
// From TextParsingService.php (global namespace section)
// ---------------------------------------------------------------------------

/**
 * @param string $mecab_args
 * @return string
 * @see \Lwt\Services\TextParsingService::getMecabPath()
 */
function get_mecab_path($mecab_args = ''): string {}

/**
 * @param array $matches
 * @param string $noSentenceEnd
 * @return string
 * @see \Lwt\Services\TextParsingService::findLatinSentenceEnd()
 */
function find_latin_sentence_end($matches, $noSentenceEnd): string {}

// ---------------------------------------------------------------------------
// From MediaService.php (global namespace section)
// ---------------------------------------------------------------------------

/**
 * @param string $dir
 * @return array{paths: string[], folders: string[]}
 */
function mediaPathsSearch(string $dir): array {}

/**
 * @return array{base_path: string, paths?: string[], folders?: string[], error?: string}
 */
function getMediaPaths(): array {}

/**
 * @param string $dir
 * @return string
 */
function selectmediapathoptions(string $dir): string {}

/**
 * @param string $f
 * @return string
 */
function selectmediapath(string $f): string {}

/**
 * @param string $path
 * @param int $offset
 * @return void
 */
function makeMediaPlayer(string $path, int $offset = 0): void {}

/**
 * @param string $path
 * @param int $offset
 * @return void
 */
function makeVideoPlayer(string $path, int $offset = 0): void {}

/**
 * @param string $audio
 * @param int $offset
 * @return void
 */
function makeAudioPlayer(string $audio, int $offset = 0): void {}

/**
 * @param string $audio
 * @param int $offset
 * @param bool $repeatMode
 * @param int $currentplayerseconds
 * @param int $currentplaybackrate
 * @return void
 */
function makeHtml5AudioPlayer(
    string $audio,
    int $offset,
    bool $repeatMode,
    int $currentplayerseconds,
    int $currentplaybackrate
): void {}

// ---------------------------------------------------------------------------
// From ExportService.php / export_helpers.php
// ---------------------------------------------------------------------------

/**
 * @param int $wid
 * @param string $before
 * @param int $brack
 * @param int $tohtml
 * @return string
 */
function getWordTagList(int $wid, string $before = ' ', int $brack = 1, int $tohtml = 1): string {}

/**
 * @param string $s
 * @param string $regexword
 * @return string
 */
function maskTermInSentence($s, $regexword): string {}

/**
 * @param string $s
 * @return string
 */
function replTabNl($s): string {}

/**
 * @param string $ann
 * @return string|false
 */
function annotationToJson($ann): string|false {}

// ---------------------------------------------------------------------------
// From vite_helper.php
// ---------------------------------------------------------------------------

/**
 * @return bool
 */
function should_use_vite(): bool {}

// ---------------------------------------------------------------------------
// From text_navigation.php / TextNavigationService.php
// ---------------------------------------------------------------------------

/**
 * @param int $textId
 * @param string $urlPath
 * @param bool $showTitle
 * @param string $addParam
 * @return string
 */
function getPreviousAndNextTextLinks(int $textId, string $urlPath, bool $showTitle, string $addParam): string {}

/**
 * @param int $textId
 * @return string
 */
function getAnnotationLink(int $textId): string {}

// ---------------------------------------------------------------------------
// From SelectOptionsBuilder / UI helpers
// These are called from Views without full namespace qualification
// ---------------------------------------------------------------------------

/**
 * @param mixed $v
 * @return string
 */
function get_seconds_selectoptions($v): string {}

/**
 * @param mixed $v
 * @return string
 */
function get_playbackrate_selectoptions($v): string {}

/**
 * @param mixed $value
 * @return string
 */
function get_checked($value): string {}

/**
 * @param mixed $value
 * @param mixed $selval
 * @return string
 */
function get_selected($value, $selval): string {}

/**
 * @param mixed $v
 * @param bool $all
 * @param bool $not9899
 * @param bool $off
 * @return string
 */
function get_wordstatus_selectoptions($v, $all, $not9899, $off = true): string {}

/**
 * @param mixed $v
 * @return string
 */
function get_tagsort_selectoptions($v): string {}

// ---------------------------------------------------------------------------
// From TagService / tags.php
// ---------------------------------------------------------------------------

/**
 * @param int $refresh
 * @return array<int, string>
 */
function get_tags($refresh = 0): array {}

/**
 * @param int $refresh
 * @return array<int, string>
 */
function get_texttags($refresh = 0): array {}

// ---------------------------------------------------------------------------
// From status/scoring utilities
// ---------------------------------------------------------------------------

/**
 * @return array<int, array{name: string, abbr: string}>
 */
function get_statuses(): array {}

/**
 * @param string $fieldname
 * @param int $statusrange
 * @return string
 */
function makeStatusCondition($fieldname, $statusrange): string {}

/**
 * @param int $currstatus
 * @param int $statusrange
 * @return bool
 */
function checkStatusRange($currstatus, $statusrange): bool {}

/**
 * @param string $type
 * @return string
 */
function make_score_random_insert_update($type): string {}

/**
 * @param int $method
 * @return string
 */
function getsqlscoreformula($method): string {}

} // end global namespace
