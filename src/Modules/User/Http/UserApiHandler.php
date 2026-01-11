<?php

declare(strict_types=1);

/**
 * User API Handler
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\User\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\User\Http;

use Lwt\Core\Entity\User;
use Lwt\Core\Exception\AuthException;
use Lwt\Modules\User\Application\UserFacade;
use Lwt\Modules\User\Infrastructure\MySqlUserRepository;

/**
 * API handler for user operations.
 *
 * Handles authentication endpoints via REST API.
 *
 * Provides endpoints for:
 * - POST /api/v1/user/login - Authenticate and get token
 * - POST /api/v1/user/register - Create account and get token
 * - POST /api/v1/user/refresh - Refresh API token
 * - POST /api/v1/user/logout - Invalidate token
 * - GET /api/v1/user/me - Get current user info
 *
 * @since 3.0.0
 */
class UserApiHandler
{
    /**
     * User facade instance.
     *
     * @var UserFacade
     */
    private UserFacade $userFacade;

    /**
     * Create a new UserApiHandler.
     *
     * @param UserFacade|null $userFacade User facade (optional for BC)
     */
    public function __construct(?UserFacade $userFacade = null)
    {
        $this->userFacade = $userFacade ?? $this->createDefaultFacade();
    }

    /**
     * Create a default UserFacade instance.
     *
     * @return UserFacade
     */
    private function createDefaultFacade(): UserFacade
    {
        $repository = new MySqlUserRepository();
        return new UserFacade($repository);
    }

    /**
     * Handle user login and return API token.
     *
     * @param array<string, mixed> $params Login credentials (username or email, password)
     *
     * @return array<string, mixed>
     */
    public function formatLogin(array $params): array
    {
        $usernameOrEmail = (string)($params['username'] ?? $params['email'] ?? '');
        $password = (string)($params['password'] ?? '');

        if (empty($usernameOrEmail) || empty($password)) {
            return [
                'success' => false,
                'error' => 'Username/email and password are required'
            ];
        }

        try {
            $user = $this->userFacade->login($usernameOrEmail, $password);

            // Generate API token for the authenticated user
            $token = $this->userFacade->generateApiToken($user->id()->toInt());

            return [
                'success' => true,
                'token' => $token,
                'expires_at' => $user->apiTokenExpires()?->format('c'),
                'user' => $this->formatUserData($user)
            ];
        } catch (AuthException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle user registration and return API token.
     *
     * @param array<string, mixed> $params Registration data (username, email, password, password_confirm)
     *
     * @return array<string, mixed>
     */
    public function formatRegister(array $params): array
    {
        $username = trim((string)($params['username'] ?? ''));
        $email = trim((string)($params['email'] ?? ''));
        $password = (string)($params['password'] ?? '');
        $passwordConfirm = (string)($params['password_confirm'] ?? '');

        // Validate required fields
        if (empty($username)) {
            return ['success' => false, 'error' => 'Username is required'];
        }
        if (empty($email)) {
            return ['success' => false, 'error' => 'Email is required'];
        }
        if (empty($password)) {
            return ['success' => false, 'error' => 'Password is required'];
        }

        // Validate password confirmation
        if ($password !== $passwordConfirm) {
            return ['success' => false, 'error' => 'Passwords do not match'];
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email format'];
        }

        // Validate username format (alphanumeric, underscore, 3-50 chars)
        if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            return [
                'success' => false,
                'error' => 'Username must be 3-50 characters and contain only letters, numbers, and underscores'
            ];
        }

        try {
            $user = $this->userFacade->register($username, $email, $password);

            // Set the current user context after registration
            $this->userFacade->setCurrentUser($user);

            // Generate API token for the new user
            $token = $this->userFacade->generateApiToken($user->id()->toInt());

            return [
                'success' => true,
                'token' => $token,
                'expires_at' => $user->apiTokenExpires()?->format('c'),
                'user' => $this->formatUserData($user)
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        } catch (\RuntimeException $e) {
            return [
                'success' => false,
                'error' => 'Registration failed. Please try again.'
            ];
        }
    }

    /**
     * Refresh the current user's API token.
     *
     * Requires valid authentication (either session or current token).
     *
     * @return array<string, mixed>
     */
    public function formatRefresh(): array
    {
        $user = $this->userFacade->getCurrentUser();

        if ($user === null) {
            return [
                'success' => false,
                'error' => 'Not authenticated'
            ];
        }

        try {
            // Invalidate old token and generate new one
            $this->userFacade->invalidateApiToken($user->id()->toInt());
            $token = $this->userFacade->generateApiToken($user->id()->toInt());

            return [
                'success' => true,
                'token' => $token,
                'expires_at' => $user->apiTokenExpires()?->format('c')
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to refresh token'
            ];
        }
    }

    /**
     * Log out the current user and invalidate their API token.
     *
     * @return array{success: bool, error?: string}
     */
    public function formatLogout(): array
    {
        $user = $this->userFacade->getCurrentUser();

        if ($user !== null) {
            $this->userFacade->invalidateApiToken($user->id()->toInt());
        }

        $this->userFacade->logout();

        return ['success' => true];
    }

    /**
     * Get current authenticated user information.
     *
     * @return array{success: bool, user?: array, error?: string}
     */
    public function formatMe(): array
    {
        $user = $this->userFacade->getCurrentUser();

        if ($user === null) {
            return [
                'success' => false,
                'error' => 'Not authenticated'
            ];
        }

        return [
            'success' => true,
            'user' => $this->formatUserData($user)
        ];
    }

    /**
     * Format user data for API response.
     *
     * @param User $user The user entity
     *
     * @return array{id: int, username: string, email: string, role: string, created: string, last_login: ?string, has_wordpress: bool}
     */
    private function formatUserData(User $user): array
    {
        return [
            'id' => $user->id()->toInt(),
            'username' => $user->username(),
            'email' => $user->email(),
            'role' => $user->role(),
            'created' => $user->created()->format('c'),
            'last_login' => $user->lastLogin()?->format('c'),
            'has_wordpress' => $user->wordPressId() !== null
        ];
    }

    /**
     * Validate API token from Authorization header.
     *
     * This method extracts and validates a Bearer token from the
     * Authorization header. If valid, it sets up the user context.
     *
     * @return User|null The authenticated user or null
     */
    public function validateBearerToken(): ?User
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        // Also check for Apache-specific header
        if ($authHeader === '' && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            /** @var mixed $apacheAuthHeader */
            $apacheAuthHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
            $authHeader = is_string($apacheAuthHeader) ? $apacheAuthHeader : '';
        }

        if ($authHeader === '') {
            return null;
        }

        // Extract Bearer token
        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return null;
        }

        $token = $matches[1];

        // Validate token
        $user = $this->userFacade->validateApiToken($token);

        if ($user !== null) {
            // Set up user context
            $this->userFacade->setCurrentUser($user);
        }

        return $user;
    }

    /**
     * Validate session authentication.
     *
     * Checks if a valid session exists and sets up user context.
     *
     * @return bool True if session is valid
     */
    public function validateSession(): bool
    {
        return $this->userFacade->validateSession();
    }

    /**
     * Check if the current request is authenticated.
     *
     * Tries both token and session authentication.
     *
     * @return bool True if request is authenticated
     */
    public function isAuthenticated(): bool
    {
        // Try bearer token first
        if ($this->validateBearerToken() !== null) {
            return true;
        }

        // Fall back to session
        return $this->validateSession();
    }

    /**
     * Get the UserFacade instance.
     *
     * Useful for access to additional user functionality.
     *
     * @return UserFacade
     */
    public function getUserFacade(): UserFacade
    {
        return $this->userFacade;
    }
}
