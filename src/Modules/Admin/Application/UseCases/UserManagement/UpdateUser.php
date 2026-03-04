<?php

declare(strict_types=1);

namespace Lwt\Modules\Admin\Application\UseCases\UserManagement;

use Lwt\Modules\User\Domain\User;
use Lwt\Modules\User\Domain\UserRepositoryInterface;
use Lwt\Modules\User\Application\Services\PasswordHasher;

class UpdateUser
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
     * Update an existing user.
     *
     * @param int      $userId         The user ID to update
     * @param int      $currentAdminId The ID of the admin performing the update
     * @param string   $username       New username
     * @param string   $email          New email
     * @param string   $password       New password (empty to skip)
     * @param string   $role           New role
     * @param bool     $isActive       New active status
     *
     * @return array{success: bool, errors?: string[]}
     */
    public function execute(
        int $userId,
        int $currentAdminId,
        string $username,
        string $email,
        string $password = '',
        string $role = User::ROLE_USER,
        bool $isActive = true
    ): array {
        $errors = [];
        $isSelf = ($userId === $currentAdminId);

        $user = $this->userRepository->find($userId);
        if ($user === null) {
            return ['success' => false, 'errors' => ['User not found']];
        }

        // Self-protection: cannot demote or deactivate yourself
        if ($isSelf && $role !== User::ROLE_ADMIN) {
            $errors[] = 'Cannot demote yourself from admin';
        }

        if ($isSelf && !$isActive) {
            $errors[] = 'Cannot deactivate your own account';
        }

        // Uniqueness checks (exclude current user)
        if ($this->userRepository->usernameExists($username, $userId)) {
            $errors[] = 'Username already exists';
        }

        if ($this->userRepository->emailExists($email, $userId)) {
            $errors[] = 'Email already exists';
        }

        // Validate password if provided
        if ($password !== '') {
            $strengthResult = $this->passwordHasher->validateStrength($password);
            if (!$strengthResult['valid']) {
                $errors = array_merge($errors, $strengthResult['errors']);
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $user->changeUsername($username);
            $user->changeEmail($email);

            if ($password !== '') {
                $user->changePassword($this->passwordHasher->hash($password));
            }

            if (!$isSelf) {
                if ($role === User::ROLE_ADMIN) {
                    $user->promoteToAdmin();
                } else {
                    $user->demoteFromAdmin();
                }

                if ($isActive) {
                    $user->activate();
                } else {
                    $user->deactivate();
                }
            }

            $this->userRepository->save($user);

            return ['success' => true];
        } catch (\InvalidArgumentException $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
}
