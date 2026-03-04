<?php

declare(strict_types=1);

namespace Lwt\Modules\Admin\Application\UseCases\UserManagement;

use Lwt\Modules\User\Domain\User;
use Lwt\Modules\User\Infrastructure\MySqlUserRepository;

class ToggleUserRole
{
    private MySqlUserRepository $userRepository;

    public function __construct(MySqlUserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Promote a user to admin.
     *
     * @param int $userId         The user to promote
     * @param int $currentAdminId The admin performing the action
     *
     * @return array{success: bool, error?: string}
     */
    public function promote(int $userId, int $currentAdminId): array
    {
        $user = $this->userRepository->find($userId);
        if ($user === null) {
            return ['success' => false, 'error' => 'User not found'];
        }

        $this->userRepository->updateRole($userId, User::ROLE_ADMIN);

        return ['success' => true];
    }

    /**
     * Demote a user from admin.
     *
     * @param int $userId         The user to demote
     * @param int $currentAdminId The admin performing the action
     *
     * @return array{success: bool, error?: string}
     */
    public function demote(int $userId, int $currentAdminId): array
    {
        if ($userId === $currentAdminId) {
            return ['success' => false, 'error' => 'Cannot demote yourself from admin'];
        }

        $user = $this->userRepository->find($userId);
        if ($user === null) {
            return ['success' => false, 'error' => 'User not found'];
        }

        $this->userRepository->updateRole($userId, User::ROLE_USER);

        return ['success' => true];
    }
}
