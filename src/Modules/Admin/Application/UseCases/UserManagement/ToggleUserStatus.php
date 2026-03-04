<?php

declare(strict_types=1);

namespace Lwt\Modules\Admin\Application\UseCases\UserManagement;

use Lwt\Modules\User\Domain\UserRepositoryInterface;

class ToggleUserStatus
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Activate a user.
     *
     * @param int $userId         The user to activate
     * @param int $currentAdminId The admin performing the action
     *
     * @return array{success: bool, error?: string}
     */
    public function activate(int $userId, int $currentAdminId): array
    {
        $user = $this->userRepository->find($userId);
        if ($user === null) {
            return ['success' => false, 'error' => 'User not found'];
        }

        $this->userRepository->activate($userId);

        return ['success' => true];
    }

    /**
     * Deactivate a user.
     *
     * @param int $userId         The user to deactivate
     * @param int $currentAdminId The admin performing the action
     *
     * @return array{success: bool, error?: string}
     */
    public function deactivate(int $userId, int $currentAdminId): array
    {
        if ($userId === $currentAdminId) {
            return ['success' => false, 'error' => 'Cannot deactivate your own account'];
        }

        $user = $this->userRepository->find($userId);
        if ($user === null) {
            return ['success' => false, 'error' => 'User not found'];
        }

        $this->userRepository->deactivate($userId);

        return ['success' => true];
    }
}
