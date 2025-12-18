<?php declare(strict_types=1);
/**
 * \file
 * \brief Start a PHP session.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Core\Bootstrap
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-start-session.html
 * @since   2.0.3-fork
 */

namespace Lwt\Core\Bootstrap;

// Core utilities (replaces kernel_utility.php)
require_once __DIR__ . '/../Globals.php';
require_once __DIR__ . '/../Utils/error_handling.php';

use Lwt\Core\Globals;
use Lwt\Core\Utils\ErrorHandler;

// Initialize globals (this was previously done in settings.php)
Globals::initialize();

/**
 * Starts or not the error reporting.
 *
 * @param bool $displayErrors True to start error reporting for ALL errors
 *
 * @return void
 */
function setErrorReporting(bool $displayErrors): void
{
    if ($displayErrors) {
        @\error_reporting(E_ALL);
        @\ini_set('display_errors', '1');
        @\ini_set('display_startup_errors', '1');
    } else {
        @\error_reporting(0);
        @\ini_set('display_errors', '0');
        @\ini_set('display_startup_errors', '0');
    }
}

/**
 * Set configuration values as script limit time and such...
 *
 * @return void
 */
function setConfigurationOptions(): void
{
    // Set script time limit
    @\ini_set('max_execution_time', '600');  // 10 min.
    @\set_time_limit(600);  // 10 min.

    @\ini_set('memory_limit', '999M');
}

/**
 * Detect if the current request is over HTTPS.
 *
 * Checks multiple indicators to handle proxies and load balancers.
 *
 * @return bool True if the connection is secure
 */
function isSecureConnection(): bool
{
    // Direct HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    // Behind a proxy/load balancer that terminates SSL
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        return true;
    }
    // Standard HTTPS port
    if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
        return true;
    }
    return false;
}

/**
 * Configure secure session cookie parameters.
 *
 * Sets HttpOnly, Secure, and SameSite flags to protect against:
 * - XSS attacks (HttpOnly prevents JavaScript access)
 * - Man-in-the-middle attacks (Secure ensures HTTPS-only transmission)
 * - CSRF attacks (SameSite restricts cross-site cookie sending)
 *
 * @return void
 */
function configureSessionCookie(): void
{
    $isSecure = isSecureConnection();

    \session_set_cookie_params([
        'lifetime' => 0,           // Session cookie (expires when browser closes)
        'path' => '/',             // Available across entire domain
        'domain' => '',            // Current domain only
        'secure' => $isSecure,     // Only send over HTTPS when available
        'httponly' => true,        // Prevent JavaScript access (XSS protection)
        'samesite' => 'Lax'        // CSRF protection while allowing normal navigation
    ]);
}

/**
 * Start the session and checks for its sanity.
 *
 * @return void
 */
function startSession(): void
{
    // Configure secure cookie parameters before starting session
    configureSessionCookie();

    // session isn't started
    $err = @\session_start();
    if ($err === false) {
        ErrorHandler::die('SESSION error (Impossible to start a PHP session)');
    }
    if (\session_id() == '') {
        ErrorHandler::die('SESSION ID empty (Impossible to start a PHP session)');
    }
    if (!isset($_SESSION)) {
        ErrorHandler::die('SESSION array not set (Impossible to start a PHP session)');
    }
}

/**
 * Launch a new session for WordPress.
 *
 * @return void
 */
function startSessionMain(): void
{
    setErrorReporting(Globals::shouldDisplayErrors());
    setConfigurationOptions();
    // Start a PHP session if not one already exists
    if (\session_id() == '') {
        startSession();
    }
}

startSessionMain();
