<?php declare(strict_types=1);
/**
 * Authentication Service - Business logic for user authentication
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

use DateTimeImmutable;
use Lwt\Core\Entity\User;
use Lwt\Core\Entity\ValueObject\UserId;
use Lwt\Core\Exception\AuthException;
use Lwt\Core\Globals;
use Lwt\Database\Connection;

require_once __DIR__ . '/../Core/Entity/ValueObject/UserId.php';
require_once __DIR__ . '/../Core/Entity/User.php';
require_once __DIR__ . '/../Core/Exception/AuthException.php';
require_once __DIR__ . '/PasswordService.php';

/**
 * Service class for user authentication.
 *
 * Handles user registration, login, logout, session management,
 * and API token authentication.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class AuthService
{
    /**
     * Session key for storing the user ID.
     */
    private const SESSION_USER_ID = 'LWT_USER_ID';

    /**
     * Session key for storing the session token (for CSRF protection).
     */
    private const SESSION_TOKEN = 'LWT_SESSION_TOKEN';

    /**
     * API token expiration time in seconds (default: 30 days).
     */
    private const API_TOKEN_EXPIRATION = 30 * 24 * 60 * 60;

    /**
     * Password service instance.
     *
     * @var PasswordService
     */
    private PasswordService $passwordService;

    /**
     * Current authenticated user (cached).
     *
     * @var User|null
     */
    private ?User $currentUser = null;

    /**
     * Create a new AuthService.
     *
     * @param PasswordService|null $passwordService Optional password service
     */
    public function __construct(?PasswordService $passwordService = null)
    {
        $this->passwordService = $passwordService ?? new PasswordService();
    }

    /**
     * Register a new user.
     *
     * @param string $username The username
     * @param string $email    The email address
     * @param string $password The plain-text password
     *
     * @return User The created user
     *
     * @throws \InvalidArgumentException If validation fails
     * @throws \RuntimeException If registration fails
     */
    public function register(string $username, string $email, string $password): User
    {
        // Validate password strength
        $validation = $this->passwordService->validateStrength($password);
        if (!$validation['valid']) {
            throw new \InvalidArgumentException(implode('. ', $validation['errors']));
        }

        // Check if username already exists
        if ($this->findUserByUsername($username) !== null) {
            throw new \InvalidArgumentException('Username is already taken');
        }

        // Check if email already exists
        if ($this->findUserByEmail($email) !== null) {
            throw new \InvalidArgumentException('Email is already registered');
        }

        // Hash the password
        $passwordHash = $this->passwordService->hash($password);

        // Create the user entity
        $user = User::create($username, $email, $passwordHash);

        // Persist to database
        $this->saveUser($user);

        return $user;
    }

    /**
     * Authenticate a user with username/email and password.
     *
     * @param string $usernameOrEmail The username or email
     * @param string $password        The plain-text password
     *
     * @return User The authenticated user
     *
     * @throws AuthException If authentication fails
     */
    public function login(string $usernameOrEmail, string $password): User
    {
        // Find user by username or email
        $user = $this->findUserByUsername($usernameOrEmail)
            ?? $this->findUserByEmail($usernameOrEmail);

        if ($user === null) {
            throw AuthException::invalidCredentials();
        }

        // Check if account is active
        if (!$user->canLogin()) {
            throw AuthException::accountDisabled();
        }

        // Verify password
        $passwordHash = $user->passwordHash();
        if ($passwordHash === null || !$this->passwordService->verify($password, $passwordHash)) {
            throw AuthException::invalidCredentials();
        }

        // Check if password needs rehashing
        if ($this->passwordService->needsRehash($passwordHash)) {
            $newHash = $this->passwordService->hash($password);
            $user->changePassword($newHash);
            $this->updateUser($user);
        }

        // Record login
        $user->recordLogin();
        $this->updateUser($user);

        // Set up session
        $this->createSession($user);

        return $user;
    }

    /**
     * Log out the current user.
     *
     * @return void
     */
    public function logout(): void
    {
        $this->currentUser = null;
        Globals::setCurrentUserId(null);
        $this->destroySession();
    }

    /**
     * Get the currently authenticated user.
     *
     * @return User|null The current user or null if not authenticated
     */
    public function getCurrentUser(): ?User
    {
        if ($this->currentUser !== null) {
            return $this->currentUser;
        }

        $userId = Globals::getCurrentUserId();
        if ($userId === null) {
            return null;
        }

        $this->currentUser = $this->findUserById($userId);
        return $this->currentUser;
    }

    /**
     * Set the current user (for session restoration).
     *
     * @param User $user The user to set as current
     *
     * @return void
     */
    public function setCurrentUser(User $user): void
    {
        $this->currentUser = $user;
        Globals::setCurrentUserId($user->id()->toInt());
    }

    /**
     * Validate the current session.
     *
     * @return bool True if the session is valid
     */
    public function validateSession(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (!isset($_SESSION[self::SESSION_USER_ID])) {
            return false;
        }

        $userId = (int) $_SESSION[self::SESSION_USER_ID];
        $user = $this->findUserById($userId);

        if ($user === null || !$user->canLogin()) {
            $this->destroySession();
            return false;
        }

        // Restore user context
        $this->setCurrentUser($user);
        return true;
    }

    /**
     * Generate a new API token for a user.
     *
     * @param int $userId The user ID
     *
     * @return string The generated API token
     *
     * @throws \InvalidArgumentException If user not found
     */
    public function generateApiToken(int $userId): string
    {
        $user = $this->findUserById($userId);
        if ($user === null) {
            throw new \InvalidArgumentException('User not found');
        }

        $token = $this->passwordService->generateToken(32);
        $expires = new DateTimeImmutable('+' . self::API_TOKEN_EXPIRATION . ' seconds');

        $user->setApiToken($token, $expires);
        $this->updateUser($user);

        return $token;
    }

    /**
     * Validate an API token and return the associated user.
     *
     * @param string $token The API token to validate
     *
     * @return User|null The user if token is valid, null otherwise
     */
    public function validateApiToken(string $token): ?User
    {
        $user = $this->findUserByApiToken($token);

        if ($user === null) {
            return null;
        }

        if (!$user->hasValidApiToken()) {
            return null;
        }

        if (!$user->canLogin()) {
            return null;
        }

        return $user;
    }

    /**
     * Invalidate a user's API token.
     *
     * @param int $userId The user ID
     *
     * @return void
     */
    public function invalidateApiToken(int $userId): void
    {
        $user = $this->findUserById($userId);
        if ($user !== null) {
            $user->invalidateApiToken();
            $this->updateUser($user);
        }
    }

    /**
     * Find or create a user from WordPress integration.
     *
     * @param int    $wpUserId The WordPress user ID
     * @param string $username The WordPress username
     * @param string $email    The WordPress email
     *
     * @return User The found or created user
     */
    public function findOrCreateWordPressUser(
        int $wpUserId,
        string $username,
        string $email
    ): User {
        // First, try to find by WordPress ID
        $user = $this->findUserByWordPressId($wpUserId);
        if ($user !== null) {
            return $user;
        }

        // Try to find by email and link
        $user = $this->findUserByEmail($email);
        if ($user !== null) {
            $user->linkWordPress($wpUserId);
            $this->updateUser($user);
            return $user;
        }

        // Create a new user from WordPress
        $user = User::createFromWordPress($wpUserId, $username, $email);
        $this->saveUser($user);

        return $user;
    }

    // =========================================================================
    // Session Management (private)
    // =========================================================================

    /**
     * Create a session for the authenticated user.
     *
     * @param User $user The authenticated user
     *
     * @return void
     */
    private function createSession(User $user): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        $_SESSION[self::SESSION_USER_ID] = $user->id()->toInt();
        $_SESSION[self::SESSION_TOKEN] = $this->passwordService->generateToken(16);

        // Set user context in Globals
        $this->setCurrentUser($user);
    }

    /**
     * Destroy the current session.
     *
     * @return void
     */
    private function destroySession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name() ?: 'PHPSESSID',
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    // =========================================================================
    // Database Operations (private)
    // =========================================================================

    /**
     * Get the users table name with prefix.
     *
     * @return string
     */
    private function usersTable(): string
    {
        return Globals::getTablePrefix() . 'users';
    }

    /**
     * Save a new user to the database.
     *
     * @param User $user The user to save
     *
     * @return void
     *
     * @throws \RuntimeException If save fails
     */
    private function saveUser(User $user): void
    {
        $table = $this->usersTable();

        $sql = "INSERT INTO {$table} (
            UsUsername, UsEmail, UsPasswordHash, UsApiToken, UsApiTokenExpires,
            UsWordPressId, UsCreated, UsLastLogin, UsIsActive, UsRole
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = [
            $user->username(),
            $user->email(),
            $user->passwordHash(),
            $user->apiToken(),
            $user->apiTokenExpires()?->format('Y-m-d H:i:s'),
            $user->wordPressId(),
            $user->created()->format('Y-m-d H:i:s'),
            $user->lastLogin()?->format('Y-m-d H:i:s'),
            $user->isActive() ? 1 : 0,
            $user->role(),
        ];

        $insertId = Connection::preparedInsert($sql, $params);
        $insertIdInt = (int) $insertId;

        if ($insertIdInt === 0) {
            throw new \RuntimeException('Failed to save user');
        }

        $user->setId(UserId::fromInt($insertIdInt));
    }

    /**
     * Update an existing user in the database.
     *
     * @param User $user The user to update
     *
     * @return void
     */
    private function updateUser(User $user): void
    {
        $table = $this->usersTable();

        $sql = "UPDATE {$table} SET
            UsUsername = ?,
            UsEmail = ?,
            UsPasswordHash = ?,
            UsApiToken = ?,
            UsApiTokenExpires = ?,
            UsWordPressId = ?,
            UsLastLogin = ?,
            UsIsActive = ?,
            UsRole = ?
            WHERE UsID = ?";

        $params = [
            $user->username(),
            $user->email(),
            $user->passwordHash(),
            $user->apiToken(),
            $user->apiTokenExpires()?->format('Y-m-d H:i:s'),
            $user->wordPressId(),
            $user->lastLogin()?->format('Y-m-d H:i:s'),
            $user->isActive() ? 1 : 0,
            $user->role(),
            $user->id()->toInt(),
        ];

        Connection::preparedExecute($sql, $params);
    }

    /**
     * Find a user by ID.
     *
     * @param int $id The user ID
     *
     * @return User|null The user or null if not found
     *
     * @psalm-suppress UnusedParam - Psalm false positive, $id is used in query
     */
    private function findUserById(int $id): ?User
    {
        $table = $this->usersTable();
        $sql = "SELECT * FROM {$table} WHERE UsID = ? LIMIT 1";
        $row = Connection::preparedFetchOne($sql, [$id]);

        return $row ? $this->hydrateUser($row) : null;
    }

    /**
     * Find a user by username.
     *
     * @param string $username The username
     *
     * @return User|null The user or null if not found
     *
     * @psalm-suppress UnusedParam - Psalm false positive, $username is used in query
     */
    private function findUserByUsername(string $username): ?User
    {
        $table = $this->usersTable();
        $sql = "SELECT * FROM {$table} WHERE UsUsername = ? LIMIT 1";
        $row = Connection::preparedFetchOne($sql, [$username]);

        return $row ? $this->hydrateUser($row) : null;
    }

    /**
     * Find a user by email.
     *
     * @param string $email The email address
     *
     * @return User|null The user or null if not found
     *
     * @psalm-suppress UnusedParam - Psalm false positive, $email is used in query
     */
    private function findUserByEmail(string $email): ?User
    {
        $table = $this->usersTable();
        $sql = "SELECT * FROM {$table} WHERE UsEmail = ? LIMIT 1";
        $row = Connection::preparedFetchOne($sql, [strtolower($email)]);

        return $row ? $this->hydrateUser($row) : null;
    }

    /**
     * Find a user by API token.
     *
     * @param string $token The API token
     *
     * @return User|null The user or null if not found
     *
     * @psalm-suppress UnusedParam - Psalm false positive, $token is used in query
     */
    private function findUserByApiToken(string $token): ?User
    {
        $table = $this->usersTable();
        $sql = "SELECT * FROM {$table} WHERE UsApiToken = ? LIMIT 1";
        $row = Connection::preparedFetchOne($sql, [$token]);

        return $row ? $this->hydrateUser($row) : null;
    }

    /**
     * Find a user by WordPress ID.
     *
     * @param int $wpUserId The WordPress user ID
     *
     * @return User|null The user or null if not found
     *
     * @psalm-suppress UnusedParam - Psalm false positive, $wpUserId is used in query
     */
    private function findUserByWordPressId(int $wpUserId): ?User
    {
        $table = $this->usersTable();
        $sql = "SELECT * FROM {$table} WHERE UsWordPressId = ? LIMIT 1";
        $row = Connection::preparedFetchOne($sql, [$wpUserId]);

        return $row ? $this->hydrateUser($row) : null;
    }

    /**
     * Hydrate a User entity from a database row.
     *
     * @param array<string, mixed> $row The database row
     *
     * @return User The hydrated user
     */
    private function hydrateUser(array $row): User
    {
        return User::reconstitute(
            (int) $row['UsID'],
            (string) $row['UsUsername'],
            (string) $row['UsEmail'],
            $row['UsPasswordHash'] !== null ? (string) $row['UsPasswordHash'] : null,
            $row['UsApiToken'] !== null ? (string) $row['UsApiToken'] : null,
            $row['UsApiTokenExpires'] !== null
                ? new DateTimeImmutable($row['UsApiTokenExpires'])
                : null,
            $row['UsWordPressId'] !== null ? (int) $row['UsWordPressId'] : null,
            new DateTimeImmutable($row['UsCreated']),
            $row['UsLastLogin'] !== null
                ? new DateTimeImmutable($row['UsLastLogin'])
                : null,
            (bool) $row['UsIsActive'],
            (string) $row['UsRole']
        );
    }
}
