<?php declare(strict_types=1);
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
            if (isset($record['ip'])) {
                $ips[] = $record['ip'];
            }
            if (isset($record['ipv6'])) {
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
        if ($query === null) {
            return '';
        }
        parse_str($query, $parsed_query);
        if (
            array_key_exists("lwt_translator", $parsed_query)
            && $parsed_query["lwt_translator"] == "libretranslate"
        ) {
            return $parsed_query["source"] ?? "";
        }
        // Fallback to Google Translate
        return $parsed_query["sl"] ?? "";
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
        if ($query === null) {
            return '';
        }
        parse_str($query, $parsed_query);
        if (
            array_key_exists("lwt_translator", $parsed_query)
            && $parsed_query["lwt_translator"] == "libretranslate"
        ) {
            return $parsed_query["target"] ?? "";
        }
        // Fallback to Google Translate
        return $parsed_query["tl"] ?? "";
    }
}
