<?php declare(strict_types=1);
/**
 * Authentication Exception
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Core\Exception
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Core\Exception;

use RuntimeException;

/**
 * Exception thrown when authentication is required but not present.
 *
 * @since 3.0.0
 */
class AuthException extends RuntimeException
{
    /**
     * Create an exception for missing user context.
     *
     * @return self
     */
    public static function userNotAuthenticated(): self
    {
        return new self('User is not authenticated. Please log in.');
    }

    /**
     * Create an exception for invalid credentials.
     *
     * @return self
     */
    public static function invalidCredentials(): self
    {
        return new self('Invalid username or password.');
    }

    /**
     * Create an exception for expired session.
     *
     * @return self
     */
    public static function sessionExpired(): self
    {
        return new self('Session has expired. Please log in again.');
    }

    /**
     * Create an exception for invalid API token.
     *
     * @return self
     */
    public static function invalidApiToken(): self
    {
        return new self('Invalid or expired API token.');
    }

    /**
     * Create an exception for account disabled.
     *
     * @return self
     */
    public static function accountDisabled(): self
    {
        return new self('This account has been disabled.');
    }
}
