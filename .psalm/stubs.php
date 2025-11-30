<?php

/**
 * Psalm stub file for global functions.
 *
 * This file provides type information for functions that are defined in
 * files that Psalm cannot trace through dynamic includes.
 *
 * @psalm-suppress UnusedClass
 * @psalm-suppress UnusedMethod
 */

// From version.php (namespaced as Lwt\Core)
namespace Lwt\Core {
    /**
     * @return string
     */
    function get_version(): string {}

    /**
     * @return string
     */
    function get_version_number(): string {}
}

// From debug_utilities.php and string_utilities.php (namespaced as Lwt\Core\Utils)
namespace Lwt\Core\Utils {
    /**
     * @return void
     */
    function showRequest(): void {}

    /**
     * @return float
     */
    function get_execution_time(): float {}

    /**
     * @param mixed $var
     * @param string $text
     * @return void
     */
    function echodebug($var, $text): void {}

    // From string_utilities.php

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
     * @param mixed $s
     * @return string
     */
    function tohtml($s): string {}

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

namespace {
// Constants defined in index.php
define('LWT_BASE_PATH', __DIR__ . '/..');

// From kernel_utility.php

/**
 * @param mixed $s
 * @return string
 */
function tohtml($s): string {}

/**
 * @param string $s
 * @param mixed $remove Value used in boolean context
 * @return string
 */
function remove_spaces($s, $remove): string {}

/**
 * @param string $mecab_args
 * @return string
 */
function get_mecab_path($mecab_args = ''): string {}

// my_die() - removed, use Lwt\Core\Utils\ErrorHandler::die() instead

/**
 * @return void
 */
function quickMenu(): void {}

/**
 * @param string $title
 * @return void
 */
function pagestart_kernel_nobody($title): void {}

/**
 * @return void
 */
function pageend(): void {}

/**
 * @return array<int, array{name: string, abbr: string}>
 */
function get_statuses(): array {}

/**
 * @param string $needle
 * @param string $replace
 * @param string $haystack
 * @return string
 */
function str_replace_first($needle, $replace, $haystack): string {}

/**
 * @param string $ann
 * @return string|false
 */
function annotation_to_json($ann): string|false {}

// getreq() - removed, use Lwt\Core\Http\ParamHelpers::getreq() instead
// getsess() - removed, use Lwt\Core\Http\ParamHelpers::getsess() instead
// url_base() - removed, use Lwt\Core\Http\UrlUtilities::urlBase() instead

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

// error_message_with_hide() - removed, use Lwt\Core\Utils\ErrorHandler::messageWithHide() instead

// langFromDict() - removed, use Lwt\Core\Http\UrlUtilities::langFromDict() instead
// targetLangFromDict() - removed, use Lwt\Core\Http\UrlUtilities::targetLangFromDict() instead

/**
 * @param string $filename
 * @return array<int, string>
 */
function parseSQLFile($filename): array {}

// Migrated from ui_helpers.php to View Helper classes

/**
 * @return void
 */
function echo_lwt_logo(): void {}

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
 * @param string|int|null $v
 * @param string $dt
 * @return string
 */
function get_languages_selectoptions($v, $dt): string {}

/**
 * @param mixed $v
 * @return string
 */
function get_wordstatus_selectoptions($v, $all, $not9899, $off = true): string {}

/**
 * @param int $currentpage
 * @param int $pages
 * @param string $script
 * @param string $formname
 * @return void
 */
function makePager($currentpage, $pages, $script, $formname): void {}

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
 * @param mixed $val
 * @param string $name
 * @return string
 */
function checkTest($val, $name): string {}

/**
 * @param string $title
 * @return void
 */
function framesetheader($title): void {}

/**
 * @param string $title
 * @param bool $close
 * @return void
 */
function pagestart($title, $close): void {}

/**
 * @param string $title
 * @return void
 */
function pagestart_nobody($title): void {}

// From tags.php

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

/**
 * @return string
 */
function get_tagsort_selectoptions($v): string {}

/**
 * @return string
 */
function get_multipletagsactions_selectoptions(): string {}

/**
 * @return string
 */
function get_alltagsactions_selectoptions(): string {}

// From export_helpers.php

/**
 * @param string $path
 * @return string
 */
function get_file_path($path): string {}

/**
 * @param string $path
 * @return void
 */
function print_file_path($path): void {}

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
function mask_term_in_sentence($s, $regexword): string {}

/**
 * @param string $s
 * @return string
 */
function repl_tab_nl($s): string {}

// From session_utility.php
// processDBParam() - removed, use Lwt\Core\Http\ParamHelpers::processDBParam() instead
// processSessParam() - removed, use Lwt\Core\Http\ParamHelpers::processSessParam() instead

// From text_helpers.php (split from session_utility)

/**
 * @param string $className
 * @return string
 */
function strToClassName($className): string {}

// Additional functions from kernel_utility.php

/**
 * @param array $matches
 * @param string $noSentenceEnd
 * @return string
 */
function find_latin_sentence_end($matches, $noSentenceEnd): string {}

// Additional functions from session_utility.php

/**
 * @return string
 */
function get_sepas(): string {}

/**
 * @param string $url
 * @return string
 */
function encodeURI($url): string {}

// From MediaService.php - legacy global functions

/**
 * @param string $dir
 * @return array{paths: string[], folders: string[]}
 */
function media_paths_search(string $dir): array {}

/**
 * @return array{base_path: string, paths?: string[], folders?: string[], error?: string}
 */
function get_media_paths(): array {}

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

/**
 * @param string $audio
 * @param int $offset
 * @param bool $repeatMode
 * @param int $currentplayerseconds
 * @param int $currentplaybackrate
 * @return void
 */
function makeLegacyAudioPlayer(
    string $audio,
    int $offset,
    bool $repeatMode,
    int $currentplayerseconds,
    int $currentplaybackrate
): void {}

// From vite_helper.php

/**
 * @return bool
 */
function should_use_vite(): bool {}

} // end namespace
