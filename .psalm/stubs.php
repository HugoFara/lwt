<?php

/**
 * Psalm stub file for runtime-defined constants and namespaced functions.
 *
 * This file provides type information for:
 * 1. Constants defined at runtime (e.g., LWT_BASE_PATH in index.php)
 * 2. Namespaced functions that Psalm cannot trace through dynamic includes
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

}
