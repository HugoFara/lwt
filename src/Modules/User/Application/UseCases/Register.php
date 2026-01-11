<?php

/**
 * Register Use Case
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\User\Application\UseCases
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\User\Application\UseCases;

use Lwt\Core\Entity\User;
use Lwt\Modules\User\Application\Services\PasswordHasher;
use Lwt\Modules\User\Domain\UserRepositoryInterface;

/**
 * Use case for user registration.
 *
 * Handles creating new user accounts.
 *
 * @since 3.0.0
 */
class Register
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
     * Create a new Register use case.
     *
     * @param UserRepositoryInterface $repository     User repository
     * @param PasswordHasher|null     $passwordHasher Password hasher
     */
    public function __construct(
        UserRepositoryInterface $repository,
        ?PasswordHasher $passwordHasher = null
    ) {
        $this->repository = $repository;
        $this->passwordHasher = $passwordHasher ?? new PasswordHasher();
    }

    /**
     * Execute the registration.
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
    public function execute(string $username, string $email, string $password): User
    {
        // Validate password strength
        $validation = $this->passwordHasher->validateStrength($password);
        if (!$validation['valid']) {
            throw new \InvalidArgumentException(implode('. ', $validation['errors']));
        }

        // Check if username already exists
        if ($this->repository->findByUsername($username) !== null) {
            throw new \InvalidArgumentException('Username is already taken');
        }

        // Check if email already exists
        if ($this->repository->findByEmail($email) !== null) {
            throw new \InvalidArgumentException('Email is already registered');
        }

        // Hash the password
        $passwordHash = $this->passwordHasher->hash($password);

        // Create the user entity
        $user = User::create($username, $email, $passwordHash);

        // Persist to database
        $this->repository->save($user);

        return $user;
    }
}
