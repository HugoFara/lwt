<?php

declare(strict_types=1);

namespace Lwt\Modules\Admin\Application\UseCases\UserManagement;

use Lwt\Modules\User\Domain\User;
use Lwt\Modules\User\Domain\UserRepositoryInterface;
use Lwt\Modules\User\Application\Services\PasswordHasher;

class CreateUser
{
    private UserRepositoryInterface $userRepository;
    private PasswordHasher $passwordHasher;

    public function __construct(
        UserRepositoryInterface $userRepository,
        PasswordHasher $passwordHasher
    ) {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * Create a new user.
     *
     * @param string $username Username
     * @param string $email    Email address
     * @param string $password Plain-text password
     * @param string $role     User role (user or admin)
     * @param bool   $isActive Whether the user is active
     *
     * @return array{success: bool, user_id?: int, errors?: string[]}
     */
    public function execute(
        string $username,
        string $email,
        string $password,
        string $role = User::ROLE_USER,
        bool $isActive = true
    ): array {
        $errors = [];

        // Validate password strength
        $strengthResult = $this->passwordHasher->validateStrength($password);
        if (!$strengthResult['valid']) {
            $errors = array_merge($errors, $strengthResult['errors']);
        }

        // Check uniqueness
        if ($this->userRepository->usernameExists($username)) {
            $errors[] = 'Username already exists';
        }

        if ($this->userRepository->emailExists($email)) {
            $errors[] = 'Email already exists';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $user = User::create($username, $email, $this->passwordHasher->hash($password));

            if ($role === User::ROLE_ADMIN) {
                $user->promoteToAdmin();
            }

            if (!$isActive) {
                $user->deactivate();
            }

            $userId = $this->userRepository->save($user);

            return ['success' => true, 'user_id' => $userId];
        } catch (\InvalidArgumentException $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
}
