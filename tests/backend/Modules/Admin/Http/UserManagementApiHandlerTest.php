<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Admin\Http;

use Lwt\Modules\Admin\Application\UseCases\UserManagement\CreateUser;
use Lwt\Modules\Admin\Application\UseCases\UserManagement\DeleteUser;
use Lwt\Modules\Admin\Application\UseCases\UserManagement\ListUsers;
use Lwt\Modules\Admin\Application\UseCases\UserManagement\ToggleUserRole;
use Lwt\Modules\Admin\Application\UseCases\UserManagement\ToggleUserStatus;
use Lwt\Modules\Admin\Application\UseCases\UserManagement\UpdateUser;
use Lwt\Modules\Admin\Http\UserManagementApiHandler;
use Lwt\Modules\User\Domain\UserRepositoryInterface;
use Lwt\Shared\Infrastructure\Globals;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The admin gate on the user-management routes.
 *
 * `ApiV1::validateAuth()` checks only that the caller is *authenticated*.
 * These routes create accounts and grant the admin role, so without the
 * handler's own check any logged-in user could promote themselves. The pages
 * these routes replaced sat behind `AdminMiddleware`; this is the equivalent.
 *
 * The use cases are mocked with `expects(never())` so a leak shows up as a
 * call that should not have happened, not merely as a wrong status code.
 */
#[CoversClass(UserManagementApiHandler::class)]
class UserManagementApiHandlerTest extends TestCase
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

    private UserManagementApiHandler $handler;

    protected function setUp(): void
    {
        $this->listUsers = $this->createMock(ListUsers::class);
        $this->createUser = $this->createMock(CreateUser::class);
        $this->updateUser = $this->createMock(UpdateUser::class);
        $this->deleteUser = $this->createMock(DeleteUser::class);
        $this->toggleUserStatus = $this->createMock(ToggleUserStatus::class);
        $this->toggleUserRole = $this->createMock(ToggleUserRole::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);

        $this->handler = new UserManagementApiHandler(
            $this->listUsers,
            $this->createUser,
            $this->updateUser,
            $this->deleteUser,
            $this->toggleUserStatus,
            $this->toggleUserRole,
            $this->userRepository
        );
    }

    protected function tearDown(): void
    {
        Globals::setMultiUserEnabled(false);
        Globals::setCurrentUserIsAdmin(false);
    }

    /** No use case may run for a non-admin caller. */
    private function expectNoWork(): void
    {
        $this->listUsers->expects($this->never())->method('execute');
        $this->createUser->expects($this->never())->method('execute');
        $this->updateUser->expects($this->never())->method('execute');
        $this->deleteUser->expects($this->never())->method('execute');
        $this->toggleUserStatus->expects($this->never())->method('activate');
        $this->toggleUserStatus->expects($this->never())->method('deactivate');
        $this->toggleUserRole->expects($this->never())->method('promote');
        $this->toggleUserRole->expects($this->never())->method('demote');
        $this->userRepository->expects($this->never())->method('find');
    }

    /**
     * Every route, as a (method, fragments) pair.
     *
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function everyRoute(): array
    {
        return [
            'list'        => ['routeGet', ['admin', 'users']],
            'read one'    => ['routeGet', ['admin', 'users', '4']],
            'create'      => ['routePost', ['admin', 'users']],
            'update'      => ['routePut', ['admin', 'users', '4']],
            'set role'    => ['routePut', ['admin', 'users', '4', 'role']],
            'set status'  => ['routePut', ['admin', 'users', '4', 'status']],
            'delete'      => ['routeDelete', ['admin', 'users', '4']],
        ];
    }

    /**
     * @param string       $route     Router method
     * @param list<string> $fragments URL fragments
     */
    #[Test]
    #[DataProvider('everyRoute')]
    public function aNonAdminIsRefusedOnEveryRoute(string $route, array $fragments): void
    {
        Globals::setMultiUserEnabled(true);
        Globals::setCurrentUserIsAdmin(false);
        $this->expectNoWork();

        $response = $this->handler->$route($fragments, ['role' => 'admin', 'is_active' => true]);

        $this->assertSame(
            403,
            $response->getStatusCode(),
            "$route must refuse a non-admin caller; these routes can grant the admin role."
        );
    }

    /**
     * @param string       $route     Router method
     * @param list<string> $fragments URL fragments
     */
    #[Test]
    #[DataProvider('everyRoute')]
    public function anAdminIsNotRefused(string $route, array $fragments): void
    {
        Globals::setMultiUserEnabled(true);
        Globals::setCurrentUserIsAdmin(true);

        $this->listUsers->method('execute')->willReturn([
            'items' => [], 'total' => 0, 'page' => 1,
            'per_page' => 20, 'total_pages' => 0, 'statistics' => [],
        ]);
        $this->createUser->method('execute')->willReturn(['success' => true, 'user_id' => 1]);
        $this->updateUser->method('execute')->willReturn(['success' => true]);
        $this->deleteUser->method('execute')->willReturn(['success' => true]);
        $this->toggleUserStatus->method('activate')->willReturn(['success' => true]);
        $this->toggleUserStatus->method('deactivate')->willReturn(['success' => true]);
        $this->toggleUserRole->method('promote')->willReturn(['success' => true]);
        $this->toggleUserRole->method('demote')->willReturn(['success' => true]);
        $this->userRepository->method('find')->willReturn(null);

        $response = $this->handler->$route($fragments, ['role' => 'admin', 'is_active' => true]);

        $this->assertNotSame(403, $response->getStatusCode());
    }

    #[Test]
    public function singleUserModeSkipsTheRoleCheck(): void
    {
        // AdminMiddleware does the same: with multi-user off there are no
        // roles, and the sole user is implicitly the administrator.
        Globals::setMultiUserEnabled(false);
        Globals::setCurrentUserIsAdmin(false);

        $this->listUsers->expects($this->once())->method('execute')->willReturn([
            'items' => [], 'total' => 0, 'page' => 1,
            'per_page' => 20, 'total_pages' => 0, 'statistics' => [],
        ]);

        $response = $this->handler->routeGet(['admin', 'users'], []);

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function aPathUnderAdminThatIsNotUsersIs404(): void
    {
        Globals::setMultiUserEnabled(true);
        Globals::setCurrentUserIsAdmin(true);
        $this->expectNoWork();

        $response = $this->handler->routeGet(['admin', 'something-else'], []);

        $this->assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function theRoleCheckRunsBeforeThePathCheck(): void
    {
        // Otherwise a non-admin could probe which admin sub-resources exist.
        Globals::setMultiUserEnabled(true);
        Globals::setCurrentUserIsAdmin(false);

        $response = $this->handler->routeGet(['admin', 'something-else'], []);

        $this->assertSame(403, $response->getStatusCode());
    }

    #[Test]
    public function updatingWithoutAnIdIs404(): void
    {
        Globals::setMultiUserEnabled(true);
        Globals::setCurrentUserIsAdmin(true);
        $this->updateUser->expects($this->never())->method('execute');

        $response = $this->handler->routePut(['admin', 'users'], []);

        $this->assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function anUnknownSubPathIs404(): void
    {
        Globals::setMultiUserEnabled(true);
        Globals::setCurrentUserIsAdmin(true);
        $this->updateUser->expects($this->never())->method('execute');

        $response = $this->handler->routePut(['admin', 'users', '4', 'nonsense'], []);

        $this->assertSame(404, $response->getStatusCode());
    }
}
