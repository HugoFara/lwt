<?php declare(strict_types=1);
/**
 * User Facade
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\User\Application
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\User\Application;

use Lwt\Core\Entity\User;
use Lwt\Core\Exception\AuthException;
use Lwt\Core\Globals;
use Lwt\Modules\User\Application\Services\PasswordHasher;
use Lwt\Modules\User\Application\Services\TokenHasher;
use Lwt\Modules\User\Application\UseCases\GenerateApiToken;
use Lwt\Modules\User\Application\UseCases\GetCurrentUser;
use Lwt\Modules\User\Application\UseCases\Login;
use Lwt\Modules\User\Application\UseCases\Logout;
use Lwt\Modules\User\Application\UseCases\Register;
use Lwt\Modules\User\Application\UseCases\ValidateApiToken;
use Lwt\Modules\User\Application\UseCases\ValidateSession;
use Lwt\Modules\User\Domain\UserRepositoryInterface;

/**
 * Facade providing unified interface to User module.
 *
 * This facade wraps the use cases to provide a similar interface
 * to the original AuthService for gradual migration.
 *
 * @since 3.0.0
 */
class UserFacade
{
    /**
     * User repository.
     *
     * @var UserRepositoryInterface
     */
    private UserRepositoryInterface $repository;

    /**
     * Password hasher.
     *
     * @var PasswordHasher
     */
    private PasswordHasher $passwordHasher;

    /**
     * Token hasher for API and remember tokens.
     *
     * @var TokenHasher
     */
    private TokenHasher $tokenHasher;

    // Use cases (lazily initialized)
    private ?Login $loginUseCase = null;
    private ?Register $registerUseCase = null;
    private ?Logout $logoutUseCase = null;
    private ?ValidateSession $validateSessionUseCase = null;
    private ?GetCurrentUser $getCurrentUserUseCase = null;
    private ?GenerateApiToken $generateApiTokenUseCase = null;
    private ?ValidateApiToken $validateApiTokenUseCase = null;

    /**
     * Constructor.
     *
     * @param UserRepositoryInterface $repository     User repository
     * @param PasswordHasher|null     $passwordHasher Password hasher
     * @param TokenHasher|null        $tokenHasher    Token hasher
     */
    public function __construct(
        UserRepositoryInterface $repository,
        ?PasswordHasher $passwordHasher = null,
        ?TokenHasher $tokenHasher = null
    ) {
        $this->repository = $repository;
        $this->passwordHasher = $passwordHasher ?? new PasswordHasher();
        $this->tokenHasher = $tokenHasher ?? new TokenHasher();
    }

    // =========================================================================
    // Authentication Operations
    // =========================================================================

    /**
     * Authenticate a user with username/email and password.
     *
     * @param string $usernameOrEmail Username or email
     * @param string $password        Plain-text password
     *
     * @return User The authenticated user
     *
     * @throws AuthException If authentication fails
     */
    public function login(string $usernameOrEmail, string $password): User
    {
        return $this->getLoginUseCase()->execute($usernameOrEmail, $password);
    }

    /**
     * Register a new user.
     *
     * @param string $username Username
     * @param string $email    Email address
     * @param string $password Plain-text password
     *
     * @return User The created user
     *
     * @throws \InvalidArgumentException If validation fails
     * @throws \RuntimeException If registration fails
     */
    public function register(string $username, string $email, string $password): User
    {
        return $this->getRegisterUseCase()->execute($username, $email, $password);
    }

    /**
     * Log out the current user.
     *
     * @return void
     */
    public function logout(): void
    {
        $this->getLogoutUseCase()->execute();
        $this->getCurrentUserUseCase?->clearCache();
    }

    /**
     * Validate the current session.
     *
     * @return bool True if the session is valid
     */
    public function validateSession(): bool
    {
        return $this->getValidateSessionUseCase()->execute() !== null;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return User|null The current user or null if not authenticated
     */
    public function getCurrentUser(): ?User
    {
        return $this->getGetCurrentUserUseCase()->execute();
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
        Globals::setCurrentUserId($user->id()->toInt());
        $this->getCurrentUserUseCase?->clearCache();
    }

    // =========================================================================
    // API Token Operations
    // =========================================================================

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
        return $this->getGenerateApiTokenUseCase()->execute($userId);
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
        return $this->getValidateApiTokenUseCase()->execute($token);
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
        $user = $this->repository->find($userId);
        if ($user !== null) {
            $user->invalidateApiToken();
            $this->repository->save($user);
        }
    }

    // =========================================================================
    // Remember Token Operations
    // =========================================================================

    /**
     * Set a remember-me token for a user.
     *
     * Returns the plaintext token but stores only the hash for security.
     *
     * @param int $userId The user ID
     * @param int $days   Number of days until expiration (default: 30)
     *
     * @return string The generated remember token (plaintext)
     *
     * @throws \InvalidArgumentException If user not found
     */
    public function setRememberToken(int $userId, int $days = 30): string
    {
        $user = $this->repository->find($userId);
        if ($user === null) {
            throw new \InvalidArgumentException("User not found: {$userId}");
        }

        // Generate plaintext token and hash for storage
        $plaintextToken = $this->tokenHasher->generate(32);
        $hashedToken = $this->tokenHasher->hash($plaintextToken);
        $expires = new \DateTimeImmutable("+{$days} days");

        // Store the hash, not the plaintext
        $user->setRememberToken($hashedToken, $expires);
        $this->repository->save($user);

        // Return plaintext to user (for cookie storage)
        return $plaintextToken;
    }

    /**
     * Validate a remember-me token and return the associated user.
     *
     * The provided plaintext token is hashed before lookup.
     *
     * @param string $token The remember token to validate (plaintext)
     *
     * @return User|null The user if token is valid, null otherwise
     */
    public function validateRememberToken(string $token): ?User
    {
        if (empty($token)) {
            return null;
        }

        // Hash the provided token to match what's stored
        $hashedToken = $this->tokenHasher->hash($token);
        $user = $this->repository->findByRememberToken($hashedToken);
        if ($user === null) {
            return null;
        }

        if (!$user->hasValidRememberToken()) {
            return null;
        }

        if (!$user->isActive()) {
            return null;
        }

        return $user;
    }

    /**
     * Invalidate a user's remember-me token.
     *
     * @param int $userId The user ID
     *
     * @return void
     */
    public function invalidateRememberToken(int $userId): void
    {
        $user = $this->repository->find($userId);
        if ($user !== null) {
            $user->invalidateRememberToken();
            $this->repository->save($user);
        }
    }

    // =========================================================================
    // User Lookup Operations
    // =========================================================================

    /**
     * Find a user by ID.
     *
     * @param int $id User ID
     *
     * @return User|null
     */
    public function findById(int $id): ?User
    {
        try {
            return $this->repository->find($id);
        } catch (\RuntimeException $e) {
            return null;
        }
    }

    /**
     * Find a user by username.
     *
     * @param string $username Username
     *
     * @return User|null
     */
    public function findByUsername(string $username): ?User
    {
        try {
            return $this->repository->findByUsername($username);
        } catch (\RuntimeException $e) {
            return null;
        }
    }

    /**
     * Find a user by email.
     *
     * @param string $email Email address
     *
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        try {
            return $this->repository->findByEmail($email);
        } catch (\RuntimeException $e) {
            return null;
        }
    }

    /**
     * Find or create a user from WordPress integration.
     *
     * @param int    $wpUserId WordPress user ID
     * @param string $username WordPress username
     * @param string $email    WordPress email
     *
     * @return User The found or created user
     */
    public function findOrCreateWordPressUser(
        int $wpUserId,
        string $username,
        string $email
    ): User {
        // First, try to find by WordPress ID
        try {
            $user = $this->repository->findByWordPressId($wpUserId);
            if ($user !== null) {
                return $user;
            }
        } catch (\RuntimeException $e) {
            // Continue to try other methods
        }

        // Try to find by email and link
        try {
            $user = $this->repository->findByEmail($email);
            if ($user !== null) {
                $user->linkWordPress($wpUserId);
                $this->repository->save($user);
                return $user;
            }
        } catch (\RuntimeException $e) {
            // Continue to create new user
        }

        // Create a new user from WordPress
        $user = User::createFromWordPress($wpUserId, $username, $email);
        $this->repository->save($user);

        return $user;
    }

    // =========================================================================
    // Password Operations
    // =========================================================================

    /**
     * Validate password strength.
     *
     * @param string $password Password to validate
     *
     * @return array{valid: bool, errors: string[]} Validation result
     */
    public function validatePasswordStrength(string $password): array
    {
        return $this->passwordHasher->validateStrength($password);
    }

    /**
     * Generate a secure random token.
     *
     * @param int<1, max> $length Token length in bytes
     *
     * @return string The generated token
     */
    public function generateToken(int $length = 32): string
    {
        return $this->passwordHasher->generateToken($length);
    }

    // =========================================================================
    // Use Case Getters (Lazy Initialization)
    // =========================================================================

    /**
     * @return Login
     */
    private function getLoginUseCase(): Login
    {
        if ($this->loginUseCase === null) {
            $this->loginUseCase = new Login($this->repository, $this->passwordHasher);
        }
        return $this->loginUseCase;
    }

    /**
     * @return Register
     */
    private function getRegisterUseCase(): Register
    {
        if ($this->registerUseCase === null) {
            $this->registerUseCase = new Register($this->repository, $this->passwordHasher);
        }
        return $this->registerUseCase;
    }

    /**
     * @return Logout
     */
    private function getLogoutUseCase(): Logout
    {
        if ($this->logoutUseCase === null) {
            $this->logoutUseCase = new Logout();
        }
        return $this->logoutUseCase;
    }

    /**
     * @return ValidateSession
     */
    private function getValidateSessionUseCase(): ValidateSession
    {
        if ($this->validateSessionUseCase === null) {
            $this->validateSessionUseCase = new ValidateSession($this->repository);
        }
        return $this->validateSessionUseCase;
    }

    /**
     * @return GetCurrentUser
     */
    private function getGetCurrentUserUseCase(): GetCurrentUser
    {
        if ($this->getCurrentUserUseCase === null) {
            $this->getCurrentUserUseCase = new GetCurrentUser($this->repository);
        }
        return $this->getCurrentUserUseCase;
    }

    /**
     * @return GenerateApiToken
     */
    private function getGenerateApiTokenUseCase(): GenerateApiToken
    {
        if ($this->generateApiTokenUseCase === null) {
            $this->generateApiTokenUseCase = new GenerateApiToken($this->repository, $this->tokenHasher);
        }
        return $this->generateApiTokenUseCase;
    }

    /**
     * @return ValidateApiToken
     */
    private function getValidateApiTokenUseCase(): ValidateApiToken
    {
        if ($this->validateApiTokenUseCase === null) {
            $this->validateApiTokenUseCase = new ValidateApiToken($this->repository, $this->tokenHasher);
        }
        return $this->validateApiTokenUseCase;
    }
}
