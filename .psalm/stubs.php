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
     * @param string $needle
     * @param string $replace
     * @param string $haystack
     * @return string
     */
    function strReplaceFirst($needle, $replace, $haystack): string {}
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
// From TextParsingService.php (global namespace section)
// ---------------------------------------------------------------------------

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
