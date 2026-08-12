<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Admin\Http;

use Lwt\Modules\Admin\Http\UserManagementController;
use Lwt\Modules\Admin\Application\UseCases\UserManagement\ListUsers;
use Lwt\Modules\Admin\Application\UseCases\UserManagement\CreateUser;
use Lwt\Modules\Admin\Application\UseCases\UserManagement\UpdateUser;
use Lwt\Modules\Admin\Application\UseCases\UserManagement\DeleteUser;
use Lwt\Modules\Admin\Application\UseCases\UserManagement\ToggleUserStatus;
use Lwt\Modules\Admin\Application\UseCases\UserManagement\ToggleUserRole;
use Lwt\Modules\User\Domain\UserRepositoryInterface;
use Lwt\Modules\User\Domain\User;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for UserManagementController.
 *
 * Tests user listing, creation, editing, deletion, activation/deactivation,
 * and role management for the admin user management interface.
 */
class UserManagementControllerTest extends TestCase
{
    /** @var ListUsers&MockObject */
    private ListUsers $listUsers;

    /** @var CreateUser&MockObject */
    private CreateUser $createUser;

    /** @var UpdateUser&MockObject */
    private UpdateUser $updateUser;

    /** @var DeleteUser&MockObject */
    private DeleteUser $deleteUser;

    /** @var ToggleUserStatus&MockObject */
    private ToggleUserStatus $toggleUserStatus;

    /** @var ToggleUserRole&MockObject */
    private ToggleUserRole $toggleUserRole;

    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $userRepository;

    private UserManagementController $controller;

    protected function setUp(): void
    {
        $this->listUsers = $this->createMock(ListUsers::class);
        $this->createUser = $this->createMock(CreateUser::class);
        $this->updateUser = $this->createMock(UpdateUser::class);
        $this->deleteUser = $this->createMock(DeleteUser::class);
        $this->toggleUserStatus = $this->createMock(ToggleUserStatus::class);
        $this->toggleUserRole = $this->createMock(ToggleUserRole::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);

        $this->controller = new UserManagementController(
            $this->listUsers,
            $this->createUser,
            $this->updateUser,
            $this->deleteUser,
            $this->toggleUserStatus,
            $this->toggleUserRole,
            $this->userRepository
        );
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    #[Test]
    public function constructorCreatesValidController(): void
    {
        $this->assertInstanceOf(UserManagementController::class, $this->controller);
    }

    #[Test]
    public function constructorSetsViewPath(): void
    {
        $reflection = new \ReflectionProperty(UserManagementController::class, 'viewPath');

        $this->assertStringEndsWith('/Views/users/', $reflection->getValue($this->controller));
    }

    // =========================================================================
    // Page routes
    //
    // The controller now only renders scaffolds: rows, statistics and every
    // mutation moved to /api/v1/admin/users, covered by
    // UserManagementApiHandlerTest. What is left to check is that these
    // routes no longer touch the use cases at all.
    // =========================================================================

    #[Test]
    public function classHasOnlyThePageMethods(): void
    {
        $reflection = new \ReflectionClass(UserManagementController::class);

        foreach (['index', 'create', 'edit'] as $name) {
            $this->assertTrue($reflection->hasMethod($name), "should have $name");
        }

        foreach (['delete', 'activate', 'deactivate', 'setRole'] as $name) {
            $this->assertFalse(
                $reflection->hasMethod($name),
                "$name moved to the API handler and should not linger on the controller"
            );
        }
    }

    #[Test]
    public function indexDoesNotQueryUsers(): void
    {
        // The list is fetched by the client; querying here would be dead work
        // on every page load.
        $_REQUEST = [];
        $this->listUsers->expects($this->never())->method('execute');

        $this->renderQuietly(fn() => $this->controller->index([]));
    }

    #[Test]
    public function createDoesNotWrite(): void
    {
        $_REQUEST = ['username' => 'someone', 'email' => 'a@b.c', 'password' => 'secret123'];
        $this->createUser->expects($this->never())->method('execute');

        $this->renderQuietly(fn() => $this->controller->create([]));

        $_REQUEST = [];
    }

    #[Test]
    public function editLooksUpTheUserToFailEarlyOnABadId(): void
    {
        $user = $this->createMock(User::class);
        $user->method('id')->willReturn(\Lwt\Shared\Domain\ValueObjects\UserId::fromInt(7));

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with(7)
            ->willReturn($user);
        $this->updateUser->expects($this->never())->method('execute');

        $this->renderQuietly(fn() => $this->controller->edit(['id' => 7]));
    }

    #[Test]
    public function editDoesNotWrite(): void
    {
        $user = $this->createMock(User::class);
        $user->method('id')->willReturn(\Lwt\Shared\Domain\ValueObjects\UserId::fromInt(7));

        $_REQUEST = ['username' => 'changed', 'role' => 'admin'];
        $this->userRepository->method('find')->willReturn($user);
        $this->updateUser->expects($this->never())->method('execute');

        $this->renderQuietly(fn() => $this->controller->edit(['id' => 7]));

        $_REQUEST = [];
    }

    /**
     * Run a controller method, swallowing view output and any include failure.
     *
     * @param callable $call The controller call
     */
    private function renderQuietly(callable $call): void
    {
        ob_start();
        try {
            $call();
        } catch (\Throwable $e) {
            // Views and redirects are not under test here.
        }
        ob_end_clean();
    }
}
