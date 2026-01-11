<?php

/**
 * \file
 * \brief URL handling utilities.
 *
 * Functions for parsing and manipulating URLs, including dictionary URL parsing.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-url-utilities.html
 * @since    3.0.0 Split from kernel_utility.php
 */

declare(strict_types=1);

namespace Lwt\Shared\Infrastructure\Http;

/**
 * URL handling utilities.
 *
 * Provides methods for parsing and manipulating URLs, including dictionary URL parsing.
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class UrlUtilities
{
    /**
     * Cached base path value.
     *
     * @var string|null
     */
    private static ?string $basePath = null;

    /**
     * Get the configured application base path.
     *
     * Returns the APP_BASE_PATH environment variable value, normalized
     * to ensure it starts with / and has no trailing slash.
     *
     * @return string The base path (e.g., '/lwt') or empty string for root
     */
    public static function getBasePath(): string
    {
        if (self::$basePath === null) {
            $envPath = $_ENV['APP_BASE_PATH'] ?? null;
            if ($envPath === null) {
                $envPath = getenv('APP_BASE_PATH');
            }
            $path = is_string($envPath) && $envPath !== '' ? $envPath : '';

            // Normalize: ensure starts with / (if not empty) and no trailing slash
            if ($path !== '') {
                $path = '/' . trim($path, '/');
            }

            self::$basePath = $path;
        }

        return self::$basePath;
    }

    /**
     * Generate a URL with the application base path prepended.
     *
     * Use this for all internal links to ensure they work correctly
     * when the application is installed in a subdirectory.
     *
     * @param string $path The path to generate URL for (must start with /)
     *
     * @return string The full URL path with base path prepended
     *
     * @example url('/login') returns '/lwt/login' if APP_BASE_PATH=/lwt
     * @example url('/assets/css/main.css') returns '/lwt/assets/css/main.css'
     */
    public static function url(string $path): string
    {
        $basePath = self::getBasePath();

        // Ensure path starts with /
        if ($path !== '' && $path[0] !== '/') {
            $path = '/' . $path;
        }

        // Avoid double slashes
        if ($basePath !== '' && $path === '/') {
            return $basePath;
        }

        return $basePath . $path;
    }

    /**
     * Strip the base path from a request URI for route matching.
     *
     * This is used by the Router to normalize incoming requests so that
     * routes like '/' work regardless of the configured APP_BASE_PATH.
     *
     * @param string $requestUri The full request URI
     *
     * @return string The path with base path stripped
     *
     * @example stripBasePath('/lwt/login') returns '/login' if APP_BASE_PATH=/lwt
     * @example stripBasePath('/lwt') returns '/' if APP_BASE_PATH=/lwt
     */
    public static function stripBasePath(string $requestUri): string
    {
        $basePath = self::getBasePath();

        if ($basePath === '') {
            return $requestUri;
        }

        // Check if request starts with base path
        if (str_starts_with($requestUri, $basePath)) {
            $stripped = substr($requestUri, strlen($basePath));
            // Ensure we return at least '/'
            return $stripped === '' ? '/' : $stripped;
        }

        // Request doesn't match base path - return as-is
        // This handles cases like /favicon.ico at the actual root
        return $requestUri;
    }

    /**
     * Reset the cached base path.
     *
     * Useful for testing or when environment changes dynamically.
     *
     * @return void
     */
    public static function resetBasePath(): void
    {
        self::$basePath = null;
    }

    /**
     * Validate that a URL is safe to fetch (not pointing to internal/private IPs).
     *
     * Prevents SSRF attacks by blocking requests to:
     * - Private IP ranges (10.x, 172.16-31.x, 192.168.x)
     * - Loopback addresses (127.x, ::1)
     * - Link-local addresses (169.254.x, fe80::)
     * - Reserved/special addresses
     * - Non-HTTP(S) schemes
     *
     * @param string $url URL to validate
     *
     * @return array{valid: bool, error?: string, resolved_ip?: string}
     */
    public static function validateUrlForFetch(string $url): array
    {
        $url = trim($url);

        // Validate URL structure
        if ($url === '') {
            return ['valid' => false, 'error' => 'Empty URL'];
        }

        $parsed = parse_url($url);
        if ($parsed === false) {
            return ['valid' => false, 'error' => 'Invalid URL format'];
        }

        // Only allow HTTP and HTTPS schemes (check before host requirement)
        $scheme = strtolower($parsed['scheme'] ?? '');
        if ($scheme !== 'http' && $scheme !== 'https') {
            return ['valid' => false, 'error' => 'Only HTTP and HTTPS URLs are allowed'];
        }

        // Host is required for HTTP(S) URLs
        if (!isset($parsed['host']) || $parsed['host'] === '') {
            return ['valid' => false, 'error' => 'Invalid URL format'];
        }

        $host = $parsed['host'];

        // Block common internal hostnames
        $lowerHost = strtolower($host);
        $blockedHostnames = [
            'localhost',
            'localhost.localdomain',
            'ip6-localhost',
            'ip6-loopback',
        ];
        if (in_array($lowerHost, $blockedHostnames, true)) {
            return ['valid' => false, 'error' => 'Internal hostnames are not allowed'];
        }

        // Block .local and .internal TLDs
        if (
            str_ends_with($lowerHost, '.local') ||
            str_ends_with($lowerHost, '.internal') ||
            str_ends_with($lowerHost, '.localhost')
        ) {
            return ['valid' => false, 'error' => 'Internal domain suffixes are not allowed'];
        }

        // Resolve hostname to IP addresses
        $ips = self::resolveHostToIps($host);
        if ($ips === []) {
            return ['valid' => false, 'error' => 'Could not resolve hostname'];
        }

        // Check each resolved IP
        foreach ($ips as $ip) {
            if (!self::isPublicIp($ip)) {
                return [
                    'valid' => false,
                    'error' => 'URL resolves to private/reserved IP address',
                    'resolved_ip' => $ip
                ];
            }
        }

        return ['valid' => true, 'resolved_ip' => $ips[0]];
    }

    /**
     * Resolve a hostname to its IP addresses.
     *
     * @param string $host Hostname to resolve
     *
     * @return array<string> List of IP addresses
     */
    private static function resolveHostToIps(string $host): array
    {
        // If it's already an IP address, return it directly
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        // Resolve DNS
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if ($records === false || $records === []) {
            // Fallback to gethostbyname for simple A record lookup
            $ip = @gethostbyname($host);
            if ($ip !== $host && filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                return [$ip];
            }
            return [];
        }

        $ips = [];
        foreach ($records as $record) {
            if (isset($record['ip']) && is_string($record['ip'])) {
                $ips[] = $record['ip'];
            }
            if (isset($record['ipv6']) && is_string($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        return $ips;
    }

    /**
     * Check if an IP address is a public (non-private, non-reserved) address.
     *
     * @param string $ip IP address to check
     *
     * @return bool True if the IP is public and safe to access
     */
    private static function isPublicIp(string $ip): bool
    {
        // Use PHP's built-in filters to check for private and reserved ranges
        // FILTER_FLAG_NO_PRIV_RANGE blocks: 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16, fc00::/7
        // FILTER_FLAG_NO_RES_RANGE blocks: 0.0.0.0/8, 169.254.0.0/16, 127.0.0.0/8, 240.0.0.0/4,
        //                                  ::1, ::/128, ::ffff:0:0/96, fe80::/10
        $result = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if ($result === false) {
            return false;
        }

        // Additional check for multicast addresses (224.0.0.0/4) which PHP filter doesn't block
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $ipLong = ip2long($ip);
            // Multicast range: 224.0.0.0 - 239.255.255.255 (224.0.0.0/4)
            if ($ipLong >= ip2long('224.0.0.0') && $ipLong <= ip2long('239.255.255.255')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the base URL of the application
     *
     * @return string base URL
     */
    public static function urlBase(): string
    {
        // Detect if using HTTPS
        $scheme = 'http';
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $scheme = 'https';
        }

        $url = parse_url("$scheme://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/'));
        $r = ($url["scheme"] ?? $scheme) . "://" . ($url["host"] ?? 'localhost');
        if (isset($url["port"])) {
            $r .= ":" . $url["port"];
        }
        if (isset($url["path"])) {
            $b = basename($url["path"]);
            if (substr($b, -4) == ".php" || substr($b, -4) == ".htm" || substr($b, -5) == ".html") {
                $r .= dirname($url["path"]);
            } else {
                $r .= $url["path"];
            }
        }
        if (substr($r, -1) !== "/") {
            $r .= "/";
        }
        return $r;
    }

    /**
     * Build a URL with query parameters.
     *
     * Constructs a URL by combining a path with query parameters.
     * Empty/null parameter values are filtered out.
     *
     * @param string               $path   The URL path (will have base path prepended)
     * @param array<string, mixed> $params Query parameters to append
     *
     * @return string The complete URL with query string
     *
     * @example buildUrl('/tags', ['page' => 2, 'query' => 'test']) returns '/tags?page=2&query=test'
     * @example buildUrl('/tags', ['page' => 1, 'query' => '']) returns '/tags?page=1'
     */
    public static function buildUrl(string $path, array $params = []): string
    {
        $url = self::url($path);

        if (empty($params)) {
            return $url;
        }

        // Filter out empty/null values but keep '0' and false
        $filtered = array_filter(
            $params,
            fn($v) => $v !== '' && $v !== null
        );

        if (empty($filtered)) {
            return $url;
        }

        return $url . '?' . http_build_query($filtered);
    }

    /**
     * Get a two-letter language code from dictionary source language.
     *
     * @param string $url Input URL, usually Google Translate or LibreTranslate
     *
     * @return string The source language code or empty string
     */
    public static function langFromDict(string $url): string
    {
        if ($url == '') {
            return '';
        }
        $query = parse_url($url, PHP_URL_QUERY);
        if ($query === null || $query === false) {
            return '';
        }
        parse_str($query, $parsed_query);
        /** @var array<string, string|list<string>> $parsed_query */
        if (
            array_key_exists("lwt_translator", $parsed_query)
            && $parsed_query["lwt_translator"] == "libretranslate"
        ) {
            $source = $parsed_query["source"] ?? "";
            return is_string($source) ? $source : "";
        }
        // Fallback to Google Translate
        $sl = $parsed_query["sl"] ?? "";
        return is_string($sl) ? $sl : "";
    }

    /**
     * Get a two-letter language code from dictionary target language
     *
     * @param string $url Input URL, usually Google Translate or LibreTranslate
     *
     * @return string The target language code or empty string
     */
    public static function targetLangFromDict(string $url): string
    {
        if ($url == '') {
            return '';
        }
        $query = parse_url($url, PHP_URL_QUERY);
        if ($query === null || $query === false) {
            return '';
        }
        parse_str($query, $parsed_query);
        /** @var array<string, string|list<string>> $parsed_query */
        if (
            array_key_exists("lwt_translator", $parsed_query)
            && $parsed_query["lwt_translator"] == "libretranslate"
        ) {
            $target = $parsed_query["target"] ?? "";
            return is_string($target) ? $target : "";
        }
        // Fallback to Google Translate
        $tl = $parsed_query["tl"] ?? "";
        return is_string($tl) ? $tl : "";
    }
}
