<?php

/**
 * CSRF Protection Middleware
 *
 * Validates CSRF tokens on state-changing requests (POST, PUT, DELETE, PATCH).
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Shared\Infrastructure\Routing\Middleware
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Shared\Infrastructure\Routing\Middleware;

use Lwt\Shared\Infrastructure\Globals;

/**
 * Middleware that validates CSRF tokens.
 *
 * Requires valid CSRF token for POST, PUT, DELETE, and PATCH requests.
 * Token must be provided via:
 * - Form field: _csrf_token
 * - Header: X-CSRF-TOKEN
 *
 * GET and OPTIONS requests are exempt.
 * API requests with Bearer tokens are exempt (API tokens serve as CSRF protection).
 *
 * @category Lwt
 * @package  Lwt\Shared\Infrastructure\Routing\Middleware
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class CsrfMiddleware implements MiddlewareInterface
{
    /**
     * Session key for CSRF token.
     */
    private const SESSION_TOKEN = 'LWT_SESSION_TOKEN';

    /**
     * Form field name for CSRF token.
     */
    private const FORM_FIELD = '_csrf_token';

    /**
     * Header name for CSRF token.
     */
    private const HEADER_NAME = 'X-CSRF-TOKEN';

    /**
     * HTTP methods that require CSRF validation.
     *
     * @var array<string>
     */
    private const PROTECTED_METHODS = ['POST', 'PUT', 'DELETE', 'PATCH'];

    /**
     * Handle the incoming request.
     *
     * Validates CSRF token for state-changing requests.
     *
     * @return bool True if validation passes, false if halted
     */
    public function handle(): bool
    {
        // Skip CSRF if multi-user mode is disabled
        if (!Globals::isMultiUserEnabled()) {
            return true;
        }

        // Skip for safe methods (GET, HEAD, OPTIONS)
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (!in_array($method, self::PROTECTED_METHODS, true)) {
            return true;
        }

        // Skip for API requests with Bearer token (token acts as CSRF protection)
        if ($this->hasApiToken()) {
            return true;
        }

        // Validate CSRF token
        if (!$this->validateToken()) {
            $this->handleInvalidToken();
            return false;
        }

        return true;
    }

    /**
     * Check if request has API Bearer token.
     *
     * API tokens serve as CSRF protection since they're not automatically
     * sent by browsers like cookies are.
     *
     * @return bool True if Bearer token present
     */
    private function hasApiToken(): bool
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (empty($authHeader) && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (is_array($headers)) {
                $rawHeader = (string) ($headers['Authorization'] ?? $headers['authorization'] ?? '');
                $authHeader = $rawHeader;
            }
        }

        return str_starts_with(strtolower($authHeader), 'bearer ');
    }

    /**
     * Validate the CSRF token.
     *
     * @return bool True if token is valid
     */
    private function validateToken(): bool
    {
        // Get expected token from session
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $expectedTokenRaw = $_SESSION[self::SESSION_TOKEN] ?? null;
        if (!is_string($expectedTokenRaw) || $expectedTokenRaw === '') {
            return false;
        }

        // Get provided token from request
        $providedToken = $this->extractToken();
        if ($providedToken === null || $providedToken === '') {
            return false;
        }

        // Use timing-safe comparison
        return hash_equals($expectedTokenRaw, $providedToken);
    }

    /**
     * Extract CSRF token from request.
     *
     * Checks form field first, then header.
     *
     * @return string|null The token or null if not found
     */
    private function extractToken(): ?string
    {
        // Check form field (POST data)
        if (isset($_POST[self::FORM_FIELD]) && is_string($_POST[self::FORM_FIELD])) {
            return $_POST[self::FORM_FIELD];
        }

        // Check header
        $headerValue = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (is_string($headerValue) && $headerValue !== '') {
            return $headerValue;
        }

        return null;
    }

    /**
     * Handle invalid or missing CSRF token.
     *
     * @return void
     */
    private function handleInvalidToken(): void
    {
        if ($this->isApiRequest()) {
            $this->sendForbiddenResponse();
        } else {
            $this->sendForbiddenPage();
        }
    }

    /**
     * Check if this is an API request.
     *
     * @return bool True if API request
     */
    private function isApiRequest(): bool
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $parsedUrl = parse_url($path);
        $requestPath = $parsedUrl['path'] ?? '/';

        if (str_starts_with($requestPath, '/api/')) {
            return true;
        }

        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (str_contains($accept, 'application/json')) {
            return true;
        }

        $xRequestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return strtolower($xRequestedWith) === 'xmlhttprequest';
    }

    /**
     * Send 403 Forbidden JSON response.
     *
     * @return never
     */
    private function sendForbiddenResponse(): void
    {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error' => 'Forbidden',
            'message' => 'CSRF token validation failed. Please refresh the page and try again.',
        ]);
        exit;
    }

    /**
     * Send 403 Forbidden HTML page.
     *
     * @return never
     */
    private function sendForbiddenPage(): void
    {
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden - CSRF Token Invalid</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 600px; margin: 100px auto; padding: 20px; }
        h1 { color: #c0392b; }
        a { color: #3498db; }
    </style>
</head>
<body>
    <h1>403 Forbidden</h1>
    <p>Your request could not be processed because the security token was missing or invalid.</p>
    <p>This usually happens when:</p>
    <ul>
        <li>Your session has expired</li>
        <li>You submitted a form from a bookmarked page</li>
        <li>You have cookies disabled</li>
    </ul>
    <p>Use your browser's back button to try again, or <a href="/">return to the home page</a>.</p>
</body>
</html>
HTML;
        exit;
    }

    /**
     * Get the current CSRF token for embedding in forms.
     *
     * Creates a new token if one doesn't exist in the session.
     *
     * @return string The CSRF token
     */
    public static function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        /** @var mixed $sessionValue */
        $sessionValue = $_SESSION[self::SESSION_TOKEN] ?? '';
        $token = is_string($sessionValue) ? $sessionValue : '';
        if ($token === '') {
            $token = bin2hex(random_bytes(32));
            $_SESSION[self::SESSION_TOKEN] = $token;
        }

        return $token;
    }

    /**
     * Generate a hidden form field with the CSRF token.
     *
     * @return string HTML hidden input element
     */
    public static function formField(): string
    {
        $token = htmlspecialchars(self::getToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="' . self::FORM_FIELD . '" value="' . $token . '">';
    }
}
