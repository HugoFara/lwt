<?php

declare(strict_types=1);

namespace Lwt\Modules\Admin\Application\UseCases\UserManagement;

use Lwt\Modules\User\Domain\UserRepositoryInterface;

class DeleteUser
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Delete a user.
     *
     * @param int $userId         The user to delete
     * @param int $currentAdminId The admin performing the deletion
     *
     * @return array{success: bool, error?: string}
     */
    public function execute(int $userId, int $currentAdminId): array
    {
        if ($userId === $currentAdminId) {
            return ['success' => false, 'error' => 'Cannot delete your own account'];
        }

        $user = $this->userRepository->find($userId);
        if ($user === null) {
            return ['success' => false, 'error' => 'User not found'];
        }

        $this->userRepository->delete($userId);

        return ['success' => true];
    }
}
