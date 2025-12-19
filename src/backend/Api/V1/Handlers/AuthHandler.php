<?php declare(strict_types=1);
/**
 * API Authentication Handler.
 *
 * Handles API authentication endpoints including login, registration,
 * token refresh, and logout.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Api\V1\Handlers
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Api\V1\Handlers;

use Lwt\Core\Exception\AuthException;
use Lwt\Services\AuthService;

require_once __DIR__ . '/../../../Services/AuthService.php';

/**
 * Handler for API authentication operations.
 *
 * Provides endpoints for:
 * - POST /api/v1/auth/login - Authenticate and get token
 * - POST /api/v1/auth/register - Create account and get token
 * - POST /api/v1/auth/refresh - Refresh API token
 * - POST /api/v1/auth/logout - Invalidate token
 * - GET /api/v1/auth/me - Get current user info
 *
 * @category Lwt
 * @package  Lwt\Api\V1\Handlers
 * @since    3.0.0
 */
class AuthHandler
{
    /**
     * @var AuthService Authentication service instance
     */
    private AuthService $authService;

    /**
     * Constructor - initialize auth service.
     */
    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * Handle user login and return API token.
     *
     * @param array{username?: string, email?: string, password?: string} $params Login credentials
     *
     * @return array<string, mixed>
     */
    public function formatLogin(array $params): array
    {
        $usernameOrEmail = $params['username'] ?? $params['email'] ?? '';
        $password = $params['password'] ?? '';

        if (empty($usernameOrEmail) || empty($password)) {
            return [
                'success' => false,
                'error' => 'Username/email and password are required'
            ];
        }

        try {
            $user = $this->authService->login($usernameOrEmail, $password);

            // Generate API token for the authenticated user
            $token = $this->authService->generateApiToken($user->id()->toInt());
            $expiresAt = $user->apiTokenExpires();

            return [
                'success' => true,
                'token' => $token,
                'expires_at' => $expiresAt?->format('c'),
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
     * @param array{username?: string, email?: string, password?: string, password_confirm?: string} $params Registration data
     *
     * @return array<string, mixed>
     */
    public function formatRegister(array $params): array
    {
        $username = trim($params['username'] ?? '');
        $email = trim($params['email'] ?? '');
        $password = $params['password'] ?? '';
        $passwordConfirm = $params['password_confirm'] ?? '';

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
            $user = $this->authService->register($username, $email, $password);

            // Set the current user context after registration
            $this->authService->setCurrentUser($user);

            // Generate API token for the new user
            $token = $this->authService->generateApiToken($user->id()->toInt());

            // Reload user to get token expiration
            $expiresAt = $user->apiTokenExpires();

            return [
                'success' => true,
                'token' => $token,
                'expires_at' => $expiresAt?->format('c'),
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
        $user = $this->authService->getCurrentUser();

        if ($user === null) {
            return [
                'success' => false,
                'error' => 'Not authenticated'
            ];
        }

        try {
            // Invalidate old token and generate new one
            $this->authService->invalidateApiToken($user->id()->toInt());
            $token = $this->authService->generateApiToken($user->id()->toInt());

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
        $user = $this->authService->getCurrentUser();

        if ($user !== null) {
            $this->authService->invalidateApiToken($user->id()->toInt());
        }

        $this->authService->logout();

        return ['success' => true];
    }

    /**
     * Get current authenticated user information.
     *
     * @return array{success: bool, user?: array, error?: string}
     */
    public function formatMe(): array
    {
        $user = $this->authService->getCurrentUser();

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
     * @param \Lwt\Core\Entity\User $user The user entity
     *
     * @return array{id: int, username: string, email: string, role: string, created: string, last_login: ?string, has_wordpress: bool}
     */
    private function formatUserData(\Lwt\Core\Entity\User $user): array
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
     * @return \Lwt\Core\Entity\User|null The authenticated user or null
     */
    public function validateBearerToken(): ?\Lwt\Core\Entity\User
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        // Also check for Apache-specific header
        if (empty($authHeader) && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (empty($authHeader)) {
            return null;
        }

        // Extract Bearer token
        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return null;
        }

        $token = $matches[1];

        // Validate token
        $user = $this->authService->validateApiToken($token);

        if ($user !== null) {
            // Set up user context
            $this->authService->setCurrentUser($user);
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
        return $this->authService->validateSession();
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
     * Get the AuthService instance.
     *
     * Useful for access to additional auth functionality.
     *
     * @return AuthService
     */
    public function getAuthService(): AuthService
    {
        return $this->authService;
    }
}
