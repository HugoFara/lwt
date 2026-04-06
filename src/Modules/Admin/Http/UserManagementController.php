<?php

declare(strict_types=1);

namespace Lwt\Modules\Admin\Http;

use Lwt\Shared\Http\BaseController;
use Lwt\Shared\Infrastructure\Globals;
use Lwt\Modules\Admin\Application\UseCases\UserManagement\ListUsers;
use Lwt\Modules\Admin\Application\UseCases\UserManagement\CreateUser;
use Lwt\Modules\Admin\Application\UseCases\UserManagement\UpdateUser;
use Lwt\Modules\Admin\Application\UseCases\UserManagement\DeleteUser;
use Lwt\Modules\Admin\Application\UseCases\UserManagement\ToggleUserStatus;
use Lwt\Modules\Admin\Application\UseCases\UserManagement\ToggleUserRole;
use Lwt\Modules\User\Domain\UserRepositoryInterface;

class UserManagementController extends BaseController
{
    private ListUsers $listUsers;
    private CreateUser $createUser;
    private UpdateUser $updateUser;
    private DeleteUser $deleteUser;
    private ToggleUserStatus $toggleUserStatus;
    private ToggleUserRole $toggleUserRole;
    private UserRepositoryInterface $userRepository;
    private string $viewPath;

    public function __construct(
        ListUsers $listUsers,
        CreateUser $createUser,
        UpdateUser $updateUser,
        DeleteUser $deleteUser,
        ToggleUserStatus $toggleUserStatus,
        ToggleUserRole $toggleUserRole,
        UserRepositoryInterface $userRepository
    ) {
        parent::__construct();
        $this->listUsers = $listUsers;
        $this->createUser = $createUser;
        $this->updateUser = $updateUser;
        $this->deleteUser = $deleteUser;
        $this->toggleUserStatus = $toggleUserStatus;
        $this->toggleUserRole = $toggleUserRole;
        $this->userRepository = $userRepository;
        $this->viewPath = __DIR__ . '/../Views/users/';
    }

    /**
     * List users with pagination and search.
     *
     * @psalm-suppress UnusedVariable, UnresolvableInclude
     */
    public function index(array $params): void
    {
        $page = $this->paramInt('page', 1, 1);
        $perPage = $this->paramInt('per_page', 20, 1, 100);
        $sortBy = $this->param('sort', 'username');
        $direction = $this->param('dir', 'ASC');
        $search = $this->param('search');
        $currentAdminId = Globals::getCurrentUserId();

        $data = $this->listUsers->execute(
            $page ?? 1,
            $perPage ?? 20,
            $sortBy,
            $direction,
            $search
        );
        $data['current_admin_id'] = $currentAdminId;
        $data['search'] = $search;
        $data['sort'] = $sortBy;
        $data['dir'] = $direction;

        $message = $this->param('message');

        $this->render('User Management', true);
        $this->message($message, true);
        include $this->viewPath . 'list.php';
        $this->endRender();
    }

    /**
     * Show create form or process new user creation.
     *
     * @psalm-suppress UnusedVariable, UnresolvableInclude, InvalidReturnStatement
     */
    public function create(array $params): void
    {
        if ($this->isPost()) {
            $result = $this->createUser->execute(
                $this->param('username'),
                $this->param('email'),
                $this->param('password'),
                $this->param('role', 'user'),
                $this->hasParam('is_active')
            );

            if ($result['success']) {
                $this->redirect('/admin/users?message=' . urlencode(__('admin.users.flash.created')))->send();
                return;
            }

            $errors = $result['errors'] ?? [];
            $formData = [
                'username' => $this->param('username'),
                'email' => $this->param('email'),
                'role' => $this->param('role', 'user'),
                'is_active' => $this->hasParam('is_active'),
            ];

            $isEdit = false;
            $user = null;
            $currentAdminId = Globals::getCurrentUserId();

            $this->render('Create User', true);
            $this->message(__('admin.users.flash.error_prefix', ['message' => implode('. ', $errors)]), false);
            include $this->viewPath . 'form.php';
            $this->endRender();
            return;
        }

        $isEdit = false;
        $user = null;
        $errors = [];
        $formData = ['username' => '', 'email' => '', 'role' => 'user', 'is_active' => true];
        $currentAdminId = Globals::getCurrentUserId();

        $this->render('Create User', true);
        include $this->viewPath . 'form.php';
        $this->endRender();
    }

    /**
     * Show edit form or process user update.
     *
     * @psalm-suppress UnusedVariable, UnresolvableInclude, InvalidReturnStatement
     */
    public function edit(array $params): void
    {
        $userId = (int) ($params['id'] ?? 0);
        $currentAdminId = Globals::getCurrentUserId() ?? 0;

        if ($this->isPost()) {
            $result = $this->updateUser->execute(
                $userId,
                $currentAdminId,
                $this->param('username'),
                $this->param('email'),
                $this->param('password'),
                $this->param('role', 'user'),
                $this->hasParam('is_active')
            );

            if ($result['success']) {
                $this->redirect('/admin/users?message=' . urlencode(__('admin.users.flash.updated')))->send();
                return;
            }

            $errors = $result['errors'] ?? [];
            $user = $this->userRepository->find($userId);

            if ($user === null) {
                $this->redirect(
                    '/admin/users?message=' . urlencode(__('admin.users.flash.not_found'))
                )->send();
                return;
            }

            $formData = [
                'username' => $this->param('username'),
                'email' => $this->param('email'),
                'role' => $this->param('role', 'user'),
                'is_active' => $this->hasParam('is_active'),
            ];
            $isEdit = true;

            $this->render('Edit User', true);
            $this->message(__('admin.users.flash.error_prefix', ['message' => implode('. ', $errors)]), false);
            include $this->viewPath . 'form.php';
            $this->endRender();
            return;
        }

        $user = $this->userRepository->find($userId);
        if ($user === null) {
            $this->redirect('/admin/users?message=' . urlencode(__('admin.users.flash.not_found')))->send();
            return;
        }

        $isEdit = true;
        $errors = [];
        $formData = [
            'username' => $user->username(),
            'email' => $user->email(),
            'role' => $user->role(),
            'is_active' => $user->isActive(),
        ];

        $this->render('Edit User', true);
        include $this->viewPath . 'form.php';
        $this->endRender();
    }

    /**
     * Delete a user (POST only).
     */
    public function delete(array $params): void
    {
        $userId = (int) ($params['id'] ?? 0);
        $currentAdminId = Globals::getCurrentUserId() ?? 0;

        $result = $this->deleteUser->execute($userId, $currentAdminId);

        if ($result['success']) {
            $this->redirect('/admin/users?message=' . urlencode(__('admin.users.flash.deleted')))->send();
        } else {
            $error = $result['error'] ?? __('admin.users.flash.unknown_error');
            $this->redirect(
                '/admin/users?message=' . urlencode(__('admin.users.flash.error_prefix', ['message' => $error]))
            )->send();
        }
    }

    /**
     * Activate a user (POST, JSON response).
     */
    public function activate(array $params): void
    {
        $userId = (int) ($params['id'] ?? 0);
        $currentAdminId = Globals::getCurrentUserId() ?? 0;

        $result = $this->toggleUserStatus->activate($userId, $currentAdminId);
        $this->json($result, $result['success'] ? 200 : 400)->send();
    }

    /**
     * Deactivate a user (POST, JSON response).
     */
    public function deactivate(array $params): void
    {
        $userId = (int) ($params['id'] ?? 0);
        $currentAdminId = Globals::getCurrentUserId() ?? 0;

        $result = $this->toggleUserStatus->deactivate($userId, $currentAdminId);
        $this->json($result, $result['success'] ? 200 : 400)->send();
    }

    /**
     * Set user role (POST, JSON response).
     */
    public function setRole(array $params): void
    {
        $userId = (int) ($params['id'] ?? 0);
        $currentAdminId = Globals::getCurrentUserId() ?? 0;
        $action = $this->param('action', 'demote');

        if ($action === 'promote') {
            $result = $this->toggleUserRole->promote($userId, $currentAdminId);
        } else {
            $result = $this->toggleUserRole->demote($userId, $currentAdminId);
        }

        $this->json($result, $result['success'] ? 200 : 400)->send();
    }
}
