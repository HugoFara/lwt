<?php

/**
 * WordPress Service - Business logic for WordPress integration
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Services;

/**
 * Service class for WordPress integration.
 *
 * Handles WordPress authentication and session management.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class WordPressService
{
    /**
     * Session key for WordPress user ID.
     */
    private const SESSION_KEY = 'LWT-WP-User';

    /**
     * Check if WordPress is available and load it.
     *
     * @return bool True if WordPress was loaded successfully
     */
    public function loadWordPress(): bool
    {
        $wpLoadPath = $this->getWordPressLoadPath();

        if (!file_exists($wpLoadPath)) {
            return false;
        }

        require_once $wpLoadPath;
        return true;
    }

    /**
     * Get the path to WordPress wp-load.php.
     *
     * LWT must be installed in a subdirectory "lwt" under the WordPress main directory.
     *
     * @return string Path to wp-load.php
     */
    private function getWordPressLoadPath(): string
    {
        return dirname(__DIR__, 3) . '/wp-load.php';
    }

    /**
     * Check if the current user is logged into WordPress.
     *
     * @return bool True if user is logged in
     */
    public function isUserLoggedIn(): bool
    {
        if (!function_exists('is_user_logged_in')) {
            return false;
        }
        return \is_user_logged_in();
    }

    /**
     * Get the current WordPress user ID.
     *
     * @return int|null User ID or null if not logged in
     */
    public function getCurrentUserId(): ?int
    {
        if (!$this->isUserLoggedIn()) {
            return null;
        }

        /** @psalm-suppress InvalidGlobal */
        global $current_user;

        if (function_exists('get_currentuserinfo')) {
            \get_currentuserinfo();
        }

        return isset($current_user->ID) ? (int) $current_user->ID : null;
    }

    /**
     * Start a PHP session for LWT-WordPress integration.
     *
     * @return array{success: bool, error: string|null}
     */
    public function startSession(): array
    {
        $started = @session_start();

        if ($started === false) {
            return [
                'success' => false,
                'error' => 'SESSION error (Impossible to start a PHP session)'
            ];
        }

        if (session_id() === '') {
            return [
                'success' => false,
                'error' => 'SESSION ID empty (Impossible to start a PHP session)'
            ];
        }

        if (!isset($_SESSION)) {
            return [
                'success' => false,
                'error' => 'SESSION array not set (Impossible to start a PHP session)'
            ];
        }

        return ['success' => true, 'error' => null];
    }

    /**
     * Store the WordPress user ID in the session.
     *
     * @param int $userId WordPress user ID
     *
     * @return void
     */
    public function setSessionUser(int $userId): void
    {
        $_SESSION[self::SESSION_KEY] = $userId;
    }

    /**
     * Get the WordPress user ID from the session.
     *
     * @return int|null User ID or null if not set
     */
    public function getSessionUser(): ?int
    {
        return isset($_SESSION[self::SESSION_KEY]) ? (int) $_SESSION[self::SESSION_KEY] : null;
    }

    /**
     * Clear the WordPress user from the session.
     *
     * @return void
     */
    public function clearSessionUser(): void
    {
        if (isset($_SESSION[self::SESSION_KEY])) {
            unset($_SESSION[self::SESSION_KEY]);
        }
    }

    /**
     * Validate and sanitize a redirect URL.
     *
     * Ensures the redirect target exists as a local file.
     *
     * @param string|null $redirectUrl The requested redirect URL
     *
     * @return string Safe redirect URL (defaults to 'index.php')
     */
    public function validateRedirectUrl(?string $redirectUrl): string
    {
        if (empty($redirectUrl)) {
            return 'index.php';
        }

        // Extract path before query string and check if file exists
        $path = preg_replace('/^([^?]+).*/', './$1', $redirectUrl);

        if ($path !== null && file_exists($path)) {
            return $redirectUrl;
        }

        return 'index.php';
    }

    /**
     * Get the WordPress login URL with redirect.
     *
     * @param string $redirectTo URL to redirect to after login
     *
     * @return string WordPress login URL
     */
    public function getLoginUrl(string $redirectTo = './lwt/wp_lwt_start.php'): string
    {
        return '../wp-login.php?redirect_to=' . urlencode($redirectTo);
    }

    /**
     * Logout from WordPress and destroy the session.
     *
     * @return void
     */
    public function logout(): void
    {
        // Logout from WordPress
        if (function_exists('wp_logout')) {
            \wp_logout();
        }

        // Destroy the PHP session
        $this->destroySession();
    }

    /**
     * Destroy the current PHP session completely.
     *
     * @return void
     */
    public function destroySession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        session_unset();
        session_destroy();
        session_write_close();

        // Delete session cookie
        if (session_name() !== false) {
            setcookie(session_name(), '', 0, '/');
        }

        // Regenerate session ID only if a session can be started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /**
     * Handle the WordPress start flow.
     *
     * @param string|null $redirectUrl Requested redirect URL
     *
     * @return array{success: bool, redirect: string, error: string|null}
     */
    public function handleStart(?string $redirectUrl = null): array
    {
        // Load WordPress
        if (!$this->loadWordPress()) {
            return [
                'success' => false,
                'redirect' => '',
                'error' => 'WordPress not found'
            ];
        }

        // Check if user is logged in
        if (!$this->isUserLoggedIn()) {
            return [
                'success' => false,
                'redirect' => $this->getLoginUrl('./lwt/wp_lwt_start.php'),
                'error' => null
            ];
        }

        // Get user ID
        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            return [
                'success' => false,
                'redirect' => $this->getLoginUrl('./lwt/wp_lwt_start.php'),
                'error' => null
            ];
        }

        // Start session
        $sessionResult = $this->startSession();
        if (!$sessionResult['success']) {
            return [
                'success' => false,
                'redirect' => '',
                'error' => $sessionResult['error']
            ];
        }

        // Store user in session
        $this->setSessionUser($userId);

        // Validate redirect URL
        $redirectTo = $this->validateRedirectUrl($redirectUrl);

        return [
            'success' => true,
            'redirect' => './' . $redirectTo,
            'error' => null
        ];
    }

    /**
     * Handle the WordPress stop flow.
     *
     * @return array{success: bool, redirect: string}
     */
    public function handleStop(): array
    {
        // Load WordPress (if available)
        $this->loadWordPress();

        // Logout and destroy session
        $this->logout();

        return [
            'success' => true,
            'redirect' => $this->getLoginUrl('./lwt/wp_lwt_start.php')
        ];
    }
}
