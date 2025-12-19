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
require_once __DIR__ . '/SessionBootstrap.php';

use Lwt\Core\Globals;

// Initialize globals (this was previously done in settings.php)
Globals::initialize();

/**
 * Starts or not the error reporting.
 *
 * @param bool $displayErrors True to start error reporting for ALL errors
 *
 * @return void
 *
 * @deprecated Use SessionBootstrap::setErrorReporting() instead
 */
function setErrorReporting(bool $displayErrors): void
{
    SessionBootstrap::setErrorReporting($displayErrors);
}

/**
 * Set configuration values as script limit time and such...
 *
 * @return void
 *
 * @deprecated Use SessionBootstrap::setConfigurationOptions() instead
 */
function setConfigurationOptions(): void
{
    SessionBootstrap::setConfigurationOptions();
}

/**
 * Detect if the current request is over HTTPS.
 *
 * Checks multiple indicators to handle proxies and load balancers.
 *
 * @return bool True if the connection is secure
 *
 * @deprecated Use SessionBootstrap::isSecureConnection() instead
 */
function isSecureConnection(): bool
{
    return SessionBootstrap::isSecureConnection();
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
 *
 * @deprecated Use SessionBootstrap::configureSessionCookie() instead
 */
function configureSessionCookie(): void
{
    SessionBootstrap::configureSessionCookie();
}

/**
 * Start the session and checks for its sanity.
 *
 * @return void
 *
 * @deprecated Use SessionBootstrap::startSession() instead
 */
function startSession(): void
{
    SessionBootstrap::startSession();
}

/**
 * Launch a new session for WordPress.
 *
 * @return void
 *
 * @deprecated Use SessionBootstrap::bootstrap() instead
 */
function startSessionMain(): void
{
    SessionBootstrap::bootstrap();
}

SessionBootstrap::bootstrap();
