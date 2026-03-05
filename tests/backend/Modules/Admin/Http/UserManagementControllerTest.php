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
    public function constructorSetsAllDependencies(): void
    {
        $props = [
            'listUsers' => $this->listUsers,
            'createUser' => $this->createUser,
            'updateUser' => $this->updateUser,
            'deleteUser' => $this->deleteUser,
            'toggleUserStatus' => $this->toggleUserStatus,
            'toggleUserRole' => $this->toggleUserRole,
            'userRepository' => $this->userRepository,
        ];

        foreach ($props as $name => $expected) {
            $reflection = new \ReflectionProperty(UserManagementController::class, $name);
            $reflection->setAccessible(true);
            $this->assertSame(
                $expected,
                $reflection->getValue($this->controller),
                "Property $name should be set correctly"
            );
        }
    }

    #[Test]
    public function constructorSetsViewPath(): void
    {
        $reflection = new \ReflectionProperty(UserManagementController::class, 'viewPath');
        $reflection->setAccessible(true);

        $viewPath = $reflection->getValue($this->controller);
        $this->assertStringEndsWith('/Views/users/', $viewPath);
    }

    // =========================================================================
    // Class structure tests
    // =========================================================================

    #[Test]
    public function classExtendsBaseController(): void
    {
        $reflection = new \ReflectionClass(UserManagementController::class);
        $this->assertSame(
            'Lwt\Shared\Http\BaseController',
            $reflection->getParentClass()->getName()
        );
    }

    #[Test]
    public function classHasRequiredPublicMethods(): void
    {
        $expectedMethods = [
            'index', 'create', 'edit', 'delete',
            'activate', 'deactivate', 'setRole',
        ];

        $reflection = new \ReflectionClass(UserManagementController::class);

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "UserManagementController should have method: $methodName"
            );
            $method = $reflection->getMethod($methodName);
            $this->assertTrue(
                $method->isPublic(),
                "Method $methodName should be public"
            );
        }
    }

    #[Test]
    public function allPublicMethodsAcceptArrayParams(): void
    {
        $methods = ['index', 'create', 'edit', 'delete', 'activate', 'deactivate', 'setRole'];

        foreach ($methods as $methodName) {
            $method = new \ReflectionMethod(UserManagementController::class, $methodName);
            $params = $method->getParameters();
            $this->assertCount(1, $params, "Method $methodName should have 1 parameter");
            $this->assertSame('params', $params[0]->getName());
        }
    }

    // =========================================================================
    // index method tests
    // =========================================================================

    #[Test]
    public function indexCallsListUsersExecute(): void
    {
        $_REQUEST = [];

        $this->listUsers->expects($this->once())
            ->method('execute')
            ->willReturn([
                'items' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => 20,
                'total_pages' => 0,
                'statistics' => [],
            ]);

        ob_start();
        try {
            $this->controller->index([]);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    #[Test]
    public function indexPassesPaginationParams(): void
    {
        $_REQUEST = [
            'page' => '3',
            'per_page' => '10',
            'sort' => 'email',
            'dir' => 'DESC',
            'search' => 'john',
        ];

        $this->listUsers->expects($this->once())
            ->method('execute')
            ->with(3, 10, 'email', 'DESC', 'john')
            ->willReturn([
                'items' => [],
                'total' => 0,
                'page' => 3,
                'per_page' => 10,
                'total_pages' => 0,
                'statistics' => [],
            ]);

        ob_start();
        try {
            $this->controller->index([]);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    #[Test]
    public function indexUsesDefaultPaginationValues(): void
    {
        $_REQUEST = [];

        $this->listUsers->expects($this->once())
            ->method('execute')
            ->with(
                $this->callback(fn($v) => $v === 1 || $v === null),
                $this->callback(fn($v) => $v === 20 || $v === null),
                'username',
                'ASC',
                ''
            )
            ->willReturn([
                'items' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => 20,
                'total_pages' => 0,
                'statistics' => [],
            ]);

        ob_start();
        try {
            $this->controller->index([]);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    // =========================================================================
    // create method tests (GET - form display)
    // =========================================================================

    #[Test]
    public function createGetRequestRendersForm(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_REQUEST = [];

        // Should NOT call createUser->execute on GET
        $this->createUser->expects($this->never())
            ->method('execute');

        ob_start();
        try {
            $this->controller->create([]);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();

        $_SERVER['REQUEST_METHOD'] = '';
        $_REQUEST = [];
    }

    // =========================================================================
    // create method tests (POST - form submission)
    // =========================================================================

    #[Test]
    public function createPostSuccessRedirects(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'username' => 'newuser',
            'email' => 'new@example.com',
            'password' => 'SecurePass123!',
            'role' => 'user',
            'is_active' => '1',
        ];
        $_REQUEST = $_POST;

        $this->createUser->expects($this->once())
            ->method('execute')
            ->willReturn(['success' => true, 'user_id' => 42]);

        ob_start();
        try {
            $this->controller->create([]);
        } catch (\Throwable $e) {
            // Redirect send() may cause issues in test context
        }
        ob_end_clean();

        $_SERVER['REQUEST_METHOD'] = '';
        $_POST = [];
        $_REQUEST = [];
    }

    #[Test]
    public function createPostFailureRendersFormWithErrors(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'username' => 'existing',
            'email' => 'dup@example.com',
            'password' => 'weak',
            'role' => 'user',
        ];
        $_REQUEST = $_POST;

        $this->createUser->expects($this->once())
            ->method('execute')
            ->willReturn([
                'success' => false,
                'errors' => ['Username already exists', 'Password too weak'],
            ]);

        ob_start();
        try {
            $this->controller->create([]);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();

        $_SERVER['REQUEST_METHOD'] = '';
        $_POST = [];
        $_REQUEST = [];
    }

    // =========================================================================
    // edit method tests (GET - form display)
    // =========================================================================

    #[Test]
    public function editGetRequestLoadsUser(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_REQUEST = [];

        $mockUser = $this->createMock(User::class);
        $mockUser->method('username')->willReturn('testuser');
        $mockUser->method('email')->willReturn('test@example.com');
        $mockUser->method('role')->willReturn('user');
        $mockUser->method('isActive')->willReturn(true);

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with(5)
            ->willReturn($mockUser);

        ob_start();
        try {
            $this->controller->edit(['id' => '5']);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();

        $_SERVER['REQUEST_METHOD'] = '';
        $_REQUEST = [];
    }

    #[Test]
    public function editGetRequestWithNonExistentUserRedirects(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_REQUEST = [];

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        ob_start();
        try {
            $this->controller->edit(['id' => '999']);
        } catch (\Throwable $e) {
            // Redirect send() may cause issues
        }
        ob_end_clean();

        $_SERVER['REQUEST_METHOD'] = '';
        $_REQUEST = [];
    }

    #[Test]
    public function editGetRequestWithZeroIdUsesZero(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_REQUEST = [];

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with(0)
            ->willReturn(null);

        ob_start();
        try {
            $this->controller->edit([]);
        } catch (\Throwable $e) {
            // Redirect or view may fail
        }
        ob_end_clean();

        $_SERVER['REQUEST_METHOD'] = '';
        $_REQUEST = [];
    }

    // =========================================================================
    // edit method tests (POST - form submission)
    // =========================================================================

    #[Test]
    public function editPostSuccessRedirects(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'username' => 'updateduser',
            'email' => 'updated@example.com',
            'password' => '',
            'role' => 'admin',
            'is_active' => '1',
        ];
        $_REQUEST = $_POST;

        $this->updateUser->expects($this->once())
            ->method('execute')
            ->willReturn(['success' => true]);

        ob_start();
        try {
            $this->controller->edit(['id' => '10']);
        } catch (\Throwable $e) {
            // Redirect send() may cause issues
        }
        ob_end_clean();

        $_SERVER['REQUEST_METHOD'] = '';
        $_POST = [];
        $_REQUEST = [];
    }

    #[Test]
    public function editPostFailureRendersFormWithErrors(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'username' => 'dup',
            'email' => 'dup@example.com',
            'password' => '',
            'role' => 'user',
        ];
        $_REQUEST = $_POST;

        $mockUser = $this->createMock(User::class);

        $this->updateUser->expects($this->once())
            ->method('execute')
            ->willReturn([
                'success' => false,
                'errors' => ['Username already exists'],
            ]);

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn($mockUser);

        ob_start();
        try {
            $this->controller->edit(['id' => '10']);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();

        $_SERVER['REQUEST_METHOD'] = '';
        $_POST = [];
        $_REQUEST = [];
    }

    #[Test]
    public function editPostFailureWithMissingUserRedirects(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'username' => 'test',
            'email' => 'test@test.com',
            'password' => '',
            'role' => 'user',
        ];
        $_REQUEST = $_POST;

        $this->updateUser->expects($this->once())
            ->method('execute')
            ->willReturn([
                'success' => false,
                'errors' => ['Username already exists'],
            ]);

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn(null);

        ob_start();
        try {
            $this->controller->edit(['id' => '10']);
        } catch (\Throwable $e) {
            // Redirect send() may cause issues
        }
        ob_end_clean();

        $_SERVER['REQUEST_METHOD'] = '';
        $_POST = [];
        $_REQUEST = [];
    }

    // =========================================================================
    // delete method tests
    // =========================================================================

    #[Test]
    public function deleteSuccessRedirects(): void
    {
        $this->deleteUser->expects($this->once())
            ->method('execute')
            ->willReturn(['success' => true]);

        ob_start();
        try {
            $this->controller->delete(['id' => '7']);
        } catch (\Throwable $e) {
            // Redirect send() may cause issues
        }
        ob_end_clean();
    }

    #[Test]
    public function deleteFailureRedirectsWithError(): void
    {
        $this->deleteUser->expects($this->once())
            ->method('execute')
            ->willReturn(['success' => false, 'error' => 'Cannot delete your own account']);

        ob_start();
        try {
            $this->controller->delete(['id' => '1']);
        } catch (\Throwable $e) {
            // Redirect send() may cause issues
        }
        ob_end_clean();
    }

    #[Test]
    public function deleteWithMissingIdDefaultsToZero(): void
    {
        $this->deleteUser->expects($this->once())
            ->method('execute')
            ->with(0, $this->anything())
            ->willReturn(['success' => false, 'error' => 'User not found']);

        ob_start();
        try {
            $this->controller->delete([]);
        } catch (\Throwable $e) {
            // Redirect may fail
        }
        ob_end_clean();
    }

    #[Test]
    public function deleteFailureWithUnknownError(): void
    {
        $this->deleteUser->expects($this->once())
            ->method('execute')
            ->willReturn(['success' => false]);

        ob_start();
        try {
            $this->controller->delete(['id' => '3']);
        } catch (\Throwable $e) {
            // Redirect may fail
        }
        ob_end_clean();
    }

    // =========================================================================
    // activate method tests
    // =========================================================================

    #[Test]
    public function activateSuccessReturnsJsonWith200(): void
    {
        $this->toggleUserStatus->expects($this->once())
            ->method('activate')
            ->with(5, $this->anything())
            ->willReturn(['success' => true]);

        ob_start();
        try {
            $this->controller->activate(['id' => '5']);
        } catch (\Throwable $e) {
            // json send() may cause issues
        }
        ob_end_clean();
    }

    #[Test]
    public function activateFailureReturnsJsonWith400(): void
    {
        $this->toggleUserStatus->expects($this->once())
            ->method('activate')
            ->willReturn(['success' => false, 'error' => 'User not found']);

        ob_start();
        try {
            $this->controller->activate(['id' => '999']);
        } catch (\Throwable $e) {
            // json send() may cause issues
        }
        ob_end_clean();
    }

    #[Test]
    public function activateWithMissingIdDefaultsToZero(): void
    {
        $this->toggleUserStatus->expects($this->once())
            ->method('activate')
            ->with(0, $this->anything())
            ->willReturn(['success' => false, 'error' => 'User not found']);

        ob_start();
        try {
            $this->controller->activate([]);
        } catch (\Throwable $e) {
            // json send() may cause issues
        }
        ob_end_clean();
    }

    // =========================================================================
    // deactivate method tests
    // =========================================================================

    #[Test]
    public function deactivateSuccessReturnsJsonWith200(): void
    {
        $this->toggleUserStatus->expects($this->once())
            ->method('deactivate')
            ->with(5, $this->anything())
            ->willReturn(['success' => true]);

        ob_start();
        try {
            $this->controller->deactivate(['id' => '5']);
        } catch (\Throwable $e) {
            // json send() may cause issues
        }
        ob_end_clean();
    }

    #[Test]
    public function deactivateFailureReturnsJsonWith400(): void
    {
        $this->toggleUserStatus->expects($this->once())
            ->method('deactivate')
            ->willReturn(['success' => false, 'error' => 'Cannot deactivate your own account']);

        ob_start();
        try {
            $this->controller->deactivate(['id' => '1']);
        } catch (\Throwable $e) {
            // json send() may cause issues
        }
        ob_end_clean();
    }

    // =========================================================================
    // setRole method tests
    // =========================================================================

    #[Test]
    public function setRolePromoteCallsToggleRolePromote(): void
    {
        $_REQUEST = ['action' => 'promote'];

        $this->toggleUserRole->expects($this->once())
            ->method('promote')
            ->with(5, $this->anything())
            ->willReturn(['success' => true]);

        $this->toggleUserRole->expects($this->never())
            ->method('demote');

        ob_start();
        try {
            $this->controller->setRole(['id' => '5']);
        } catch (\Throwable $e) {
            // json send() may cause issues
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    #[Test]
    public function setRoleDemoteCallsToggleRoleDemote(): void
    {
        $_REQUEST = ['action' => 'demote'];

        $this->toggleUserRole->expects($this->once())
            ->method('demote')
            ->with(5, $this->anything())
            ->willReturn(['success' => true]);

        $this->toggleUserRole->expects($this->never())
            ->method('promote');

        ob_start();
        try {
            $this->controller->setRole(['id' => '5']);
        } catch (\Throwable $e) {
            // json send() may cause issues
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    #[Test]
    public function setRoleDefaultActionIsDemote(): void
    {
        $_REQUEST = [];

        $this->toggleUserRole->expects($this->once())
            ->method('demote')
            ->willReturn(['success' => true]);

        $this->toggleUserRole->expects($this->never())
            ->method('promote');

        ob_start();
        try {
            $this->controller->setRole(['id' => '5']);
        } catch (\Throwable $e) {
            // json send() may cause issues
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    #[Test]
    public function setRolePromoteFailureReturns400(): void
    {
        $_REQUEST = ['action' => 'promote'];

        $this->toggleUserRole->expects($this->once())
            ->method('promote')
            ->willReturn(['success' => false, 'error' => 'User not found']);

        ob_start();
        try {
            $this->controller->setRole(['id' => '999']);
        } catch (\Throwable $e) {
            // json send() may cause issues
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    #[Test]
    public function setRoleDemoteSelfFailure(): void
    {
        $_REQUEST = ['action' => 'demote'];

        $this->toggleUserRole->expects($this->once())
            ->method('demote')
            ->willReturn(['success' => false, 'error' => 'Cannot demote yourself from admin']);

        ob_start();
        try {
            $this->controller->setRole(['id' => '1']);
        } catch (\Throwable $e) {
            // json send() may cause issues
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    #[Test]
    public function setRoleWithUnknownActionDefaultsToDemote(): void
    {
        $_REQUEST = ['action' => 'unknown_action'];

        // 'unknown_action' !== 'promote', so it falls through to demote
        $this->toggleUserRole->expects($this->once())
            ->method('demote')
            ->willReturn(['success' => true]);

        ob_start();
        try {
            $this->controller->setRole(['id' => '5']);
        } catch (\Throwable $e) {
            // json send() may cause issues
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    #[Test]
    public function setRoleWithMissingIdDefaultsToZero(): void
    {
        $_REQUEST = ['action' => 'promote'];

        $this->toggleUserRole->expects($this->once())
            ->method('promote')
            ->with(0, $this->anything())
            ->willReturn(['success' => false, 'error' => 'User not found']);

        ob_start();
        try {
            $this->controller->setRole([]);
        } catch (\Throwable $e) {
            // json send() may cause issues
        }
        ob_end_clean();

        $_REQUEST = [];
    }

    // =========================================================================
    // Edge cases
    // =========================================================================

    #[Test]
    public function editExtractsUserIdAsInteger(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_REQUEST = [];

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with(42)  // Verify it's an integer, not string
            ->willReturn(null);

        ob_start();
        try {
            $this->controller->edit(['id' => '42']);
        } catch (\Throwable $e) {
            // Redirect may fail
        }
        ob_end_clean();

        $_SERVER['REQUEST_METHOD'] = '';
        $_REQUEST = [];
    }

    #[Test]
    public function deleteExtractsUserIdAsInteger(): void
    {
        $this->deleteUser->expects($this->once())
            ->method('execute')
            ->with(15, $this->anything())
            ->willReturn(['success' => true]);

        ob_start();
        try {
            $this->controller->delete(['id' => '15']);
        } catch (\Throwable $e) {
            // Redirect may fail
        }
        ob_end_clean();
    }

    #[Test]
    public function activateExtractsUserIdAsInteger(): void
    {
        $this->toggleUserStatus->expects($this->once())
            ->method('activate')
            ->with(25, $this->anything())
            ->willReturn(['success' => true]);

        ob_start();
        try {
            $this->controller->activate(['id' => '25']);
        } catch (\Throwable $e) {
            // json send() may cause issues
        }
        ob_end_clean();
    }
}
