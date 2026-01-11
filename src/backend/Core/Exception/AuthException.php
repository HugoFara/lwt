<?php

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

declare(strict_types=1);

namespace Lwt\Core\Exception;

/**
 * Exception thrown when authentication is required but not present.
 *
 * @since 3.0.0
 */
class AuthException extends LwtException
{
    /**
     * Create a new auth exception with appropriate HTTP status.
     *
     * @param string $message The exception message
     * @param int    $code    The exception code
     */
    public function __construct(string $message = '', int $code = 0)
    {
        parent::__construct($message, $code);
        $this->httpStatusCode = 401; // Unauthorized
        $this->shouldLog = false; // Auth failures don't need logging by default
    }

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
        $exception = new self('This account has been disabled.');
        $exception->httpStatusCode = 403; // Forbidden
        return $exception;
    }

    /**
     * Create an exception for insufficient permissions.
     *
     * @param string $action The action that was denied
     *
     * @return self
     */
    public static function insufficientPermissions(string $action): self
    {
        $exception = new self(
            sprintf('You do not have permission to %s.', $action)
        );
        $exception->httpStatusCode = 403; // Forbidden
        return $exception;
    }

    /**
     * {@inheritDoc}
     */
    public function getUserMessage(): string
    {
        // Auth messages are generally safe to show to users
        return $this->getMessage();
    }
}
