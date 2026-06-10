<?php

declare(strict_types=1);

namespace Lwt\Shared\Infrastructure\Http;

use Lwt\Shared\Infrastructure\Bootstrap\EnvLoader;

/**
 * Cross-Origin Resource Sharing (CORS) for the REST API.
 *
 * Opt-in by design: with no `CORS_ALLOWED_ORIGINS` configured, no CORS headers
 * are emitted and the API stays same-origin-only — existing installs are
 * unchanged. When the env var lists one or more origins, the request `Origin`
 * is echoed back only if it matches the allow-list exactly (no wildcards).
 *
 * Built for token (Bearer) auth: credentials/cookies are intentionally NOT
 * enabled, so `Access-Control-Allow-Credentials` is never sent. A packaged
 * client (e.g. the Capacitor/F-Droid app) authenticates with an
 * `Authorization: Bearer` header rather than cookies, which sidesteps the
 * cookie-CORS and CSRF complications entirely.
 *
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.1.1
 */
final class Cors
{
    /**
     * Methods advertised to preflight requests. Mirrors the API's accepted
     * verbs plus OPTIONS itself.
     */
    private const ALLOWED_METHODS = 'GET, POST, PUT, DELETE, OPTIONS';

    /**
     * Request headers a cross-origin client may send. `Authorization` carries
     * the Bearer token; `X-CSRF-TOKEN` is accepted for cookie-auth callers.
     */
    private const ALLOWED_HEADERS = 'Content-Type, Authorization, X-CSRF-TOKEN';

    /**
     * How long (seconds) a browser may cache the preflight result.
     */
    private const MAX_AGE = '86400';

    /**
     * Parse the configured allow-list from `CORS_ALLOWED_ORIGINS`
     * (comma-separated). Each entry is trimmed and stripped of a trailing
     * slash so it matches a browser `Origin` header.
     *
     * @return list<string> Exact origins, e.g.
     *                      ['https://app.example', 'capacitor://localhost']
     */
    public static function allowedOrigins(): array
    {
        $raw = EnvLoader::get('CORS_ALLOWED_ORIGINS', '') ?? '';
        if ($raw === '') {
            return [];
        }

        $origins = [];
        foreach (explode(',', $raw) as $entry) {
            $origin = rtrim(trim($entry), '/');
            if ($origin !== '') {
                $origins[] = $origin;
            }
        }

        return $origins;
    }

    /**
     * The request `Origin` if it is allow-listed, otherwise null.
     *
     * Matching is exact (after trailing-slash normalization); this never
     * reflects an arbitrary origin back, which would defeat the purpose of
     * the allow-list.
     */
    public static function resolveOrigin(): ?string
    {
        $rawOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($rawOrigin === '') {
            return null;
        }

        $origin = rtrim($rawOrigin, '/');

        return in_array($origin, self::allowedOrigins(), true) ? $origin : null;
    }

    /**
     * Emit CORS response headers when the request `Origin` is allow-listed.
     *
     * No-op for same-origin requests, disallowed origins, or once headers
     * have already been flushed.
     */
    public static function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        $origin = self::resolveOrigin();
        if ($origin === null) {
            return;
        }

        header('Access-Control-Allow-Origin: ' . $origin);
        // Caches must key on Origin so a response for one allowed origin is
        // never served to another. Append rather than clobber any existing Vary.
        header('Vary: Origin', false);
        header('Access-Control-Allow-Methods: ' . self::ALLOWED_METHODS);
        header('Access-Control-Allow-Headers: ' . self::ALLOWED_HEADERS);
        header('Access-Control-Max-Age: ' . self::MAX_AGE);
    }

    /**
     * Answer a CORS preflight (OPTIONS) request: emit the headers and a 204.
     *
     * @param string $method The request method.
     *
     * @return bool True if this was a preflight and has been fully answered
     *              (the caller should stop processing); false otherwise.
     */
    public static function handlePreflight(string $method): bool
    {
        if (strtoupper($method) !== 'OPTIONS') {
            return false;
        }

        self::sendHeaders();
        http_response_code(204);

        return true;
    }
}
