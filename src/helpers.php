<?php

declare(strict_types=1);

/**
 * Global helper functions for LWT.
 *
 * These functions are available throughout the application and provide
 * convenient shortcuts for common operations.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

use Lwt\Shared\Infrastructure\Http\UrlUtilities;

if (!function_exists('url')) {
    /**
     * Generate a URL with the application base path prepended.
     *
     * Use this for all internal links to ensure they work correctly
     * when the application is installed in a subdirectory.
     *
     * @param string $path The path to generate URL for (should start with /)
     *
     * @return string The full URL path with base path prepended
     *
     * @example url('/login') returns '/lwt/login' if APP_BASE_PATH=/lwt
     * @example url('/') returns '/lwt' if APP_BASE_PATH=/lwt
     * @example url('/assets/css/main.css') returns '/lwt/assets/css/main.css'
     */
    function url(string $path = '/'): string
    {
        return UrlUtilities::url($path);
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the configured application base path.
     *
     * @return string The base path (e.g., '/lwt') or empty string for root
     */
    function base_path(): string
    {
        return UrlUtilities::getBasePath();
    }
}
