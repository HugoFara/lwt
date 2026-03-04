<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Admin\UseCases\UserManagement;

use DateTimeImmutable;
use Lwt\Modules\Admin\Application\UseCases\UserManagement\CreateUser;
use Lwt\Modules\Admin\Application\UseCases\UserManagement\DeleteUser;
use Lwt\Modules\Admin\Application\UseCases\UserManagement\ListUsers;
use Lwt\Modules\Admin\Application\UseCases\UserManagement\ToggleUserRole;
use Lwt\Modules\Admin\Application\UseCases\UserManagement\ToggleUserStatus;
use Lwt\Modules\Admin\Application\UseCases\UserManagement\UpdateUser;
use Lwt\Modules\User\Application\Services\PasswordHasher;
use Lwt\Modules\User\Domain\User;
use Lwt\Modules\User\Domain\UserRepositoryInterface;
use Lwt\Modules\User\Infrastructure\MySqlUserRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserManagementUseCaseTest extends TestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private MySqlUserRepository&MockObject $mysqlUserRepository;
    private PasswordHasher&MockObject $passwordHasher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->mysqlUserRepository = $this->createMock(MySqlUserRepository::class);
        $this->passwordHasher = $this->createMock(PasswordHasher::class);
    }

    private function makeUser(
        int $id = 1,
        string $username = 'testuser',
        string $email = 'test@example.com',
        string $role = User::ROLE_USER,
        bool $isActive = true
    ): User {
        return User::reconstitute(
            $id,
            $username,
            $email,
            'hashed_password',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            new DateTimeImmutable('2024-01-01'),
            null,
            $isActive,
            $role
        );
    }

    // ===== ListUsers =====

    public function testListUsersDefaultPagination(): void
    {
        $this->mysqlUserRepository->expects($this->once())
            ->method('findPaginated')
            ->with(1, 20, 'UsUsername', 'ASC')
            ->willReturn([
                'items' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => 20,
                'total_pages' => 0,
            ]);

        $this->mysqlUserRepository->expects($this->once())
            ->method('getStatistics')
            ->willReturn(['total' => 0, 'active' => 0, 'inactive' => 0, 'admins' => 0]);

        $useCase = new ListUsers($this->mysqlUserRepository);
        $result = $useCase->execute();

        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('statistics', $result);
    }

    public function testListUsersSafeSortFallback(): void
    {
        $this->mysqlUserRepository->expects($this->once())
            ->method('findPaginated')
            ->with(1, 20, 'UsUsername', 'ASC')
            ->willReturn([
                'items' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => 20,
                'total_pages' => 0,
            ]);

        $this->mysqlUserRepository->method('getStatistics')
            ->willReturn(['total' => 0, 'active' => 0, 'inactive' => 0, 'admins' => 0]);

        $useCase = new ListUsers($this->mysqlUserRepository);
        // Invalid sort column falls back to default
        $result = $useCase->execute(1, 20, 'invalid_column', 'ASC');

        $this->assertArrayHasKey('items', $result);
    }

    public function testListUsersSearchDelegation(): void
    {
        $user = $this->makeUser();
        $this->mysqlUserRepository->expects($this->once())
            ->method('search')
            ->with('test', 500)
            ->willReturn([$user]);

        $this->mysqlUserRepository->expects($this->once())
            ->method('getStatistics')
            ->willReturn(['total' => 5, 'active' => 4, 'inactive' => 1, 'admins' => 1]);

        $useCase = new ListUsers($this->mysqlUserRepository);
        $result = $useCase->execute(1, 20, 'username', 'ASC', 'test');

        $this->assertCount(1, $result['items']);
        $this->assertEquals(1, $result['total']);
    }

    public function testListUsersDirectionNormalization(): void
    {
        $this->mysqlUserRepository->expects($this->once())
            ->method('findPaginated')
            ->with(1, 20, 'UsEmail', 'DESC')
            ->willReturn([
                'items' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => 20,
                'total_pages' => 0,
            ]);

        $this->mysqlUserRepository->method('getStatistics')
            ->willReturn([]);

        $useCase = new ListUsers($this->mysqlUserRepository);
        $result = $useCase->execute(1, 20, 'email', 'DESC');

        $this->assertArrayHasKey('items', $result);
    }

    // ===== CreateUser =====

    public function testCreateUserSuccess(): void
    {
        $this->passwordHasher->method('validateStrength')
            ->willReturn(['valid' => true, 'errors' => []]);
        $this->passwordHasher->method('hash')
            ->willReturn('hashed_pw');

        $this->userRepository->method('usernameExists')->willReturn(false);
        $this->userRepository->method('emailExists')->willReturn(false);
        $this->userRepository->method('save')->willReturn(42);

        $useCase = new CreateUser($this->userRepository, $this->passwordHasher);
        $result = $useCase->execute('newuser', 'new@example.com', 'StrongPass123!');

        $this->assertTrue($result['success']);
        $this->assertEquals(42, $result['user_id']);
    }

    public function testCreateUserDuplicateUsername(): void
    {
        $this->passwordHasher->method('validateStrength')
            ->willReturn(['valid' => true, 'errors' => []]);

        $this->userRepository->method('usernameExists')->willReturn(true);
        $this->userRepository->method('emailExists')->willReturn(false);

        $useCase = new CreateUser($this->userRepository, $this->passwordHasher);
        $result = $useCase->execute('existing', 'new@example.com', 'StrongPass123!');

        $this->assertFalse($result['success']);
        $this->assertContains('Username already exists', $result['errors']);
    }

    public function testCreateUserDuplicateEmail(): void
    {
        $this->passwordHasher->method('validateStrength')
            ->willReturn(['valid' => true, 'errors' => []]);

        $this->userRepository->method('usernameExists')->willReturn(false);
        $this->userRepository->method('emailExists')->willReturn(true);

        $useCase = new CreateUser($this->userRepository, $this->passwordHasher);
        $result = $useCase->execute('newuser', 'existing@example.com', 'StrongPass123!');

        $this->assertFalse($result['success']);
        $this->assertContains('Email already exists', $result['errors']);
    }

    public function testCreateUserWeakPassword(): void
    {
        $this->passwordHasher->method('validateStrength')
            ->willReturn(['valid' => false, 'errors' => ['Password too short']]);

        $this->userRepository->method('usernameExists')->willReturn(false);
        $this->userRepository->method('emailExists')->willReturn(false);

        $useCase = new CreateUser($this->userRepository, $this->passwordHasher);
        $result = $useCase->execute('newuser', 'new@example.com', 'weak');

        $this->assertFalse($result['success']);
        $this->assertContains('Password too short', $result['errors']);
    }

    public function testCreateUserWithAdminRole(): void
    {
        $this->passwordHasher->method('validateStrength')
            ->willReturn(['valid' => true, 'errors' => []]);
        $this->passwordHasher->method('hash')
            ->willReturn('hashed_pw');

        $this->userRepository->method('usernameExists')->willReturn(false);
        $this->userRepository->method('emailExists')->willReturn(false);

        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $user) {
                return $user->isAdmin();
            }))
            ->willReturn(43);

        $useCase = new CreateUser($this->userRepository, $this->passwordHasher);
        $result = $useCase->execute('adminuser', 'admin@example.com', 'StrongPass123!', User::ROLE_ADMIN);

        $this->assertTrue($result['success']);
    }

    public function testCreateUserInactive(): void
    {
        $this->passwordHasher->method('validateStrength')
            ->willReturn(['valid' => true, 'errors' => []]);
        $this->passwordHasher->method('hash')
            ->willReturn('hashed_pw');

        $this->userRepository->method('usernameExists')->willReturn(false);
        $this->userRepository->method('emailExists')->willReturn(false);

        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $user) {
                return !$user->isActive();
            }))
            ->willReturn(44);

        $useCase = new CreateUser($this->userRepository, $this->passwordHasher);
        $result = $useCase->execute('inactiveuser', 'inactive@example.com', 'StrongPass123!', User::ROLE_USER, false);

        $this->assertTrue($result['success']);
    }

    // ===== UpdateUser =====

    public function testUpdateUserSuccess(): void
    {
        $user = $this->makeUser(10, 'oldname', 'old@example.com');

        $this->userRepository->method('find')->with(10)->willReturn($user);
        $this->userRepository->method('usernameExists')->willReturn(false);
        $this->userRepository->method('emailExists')->willReturn(false);
        $this->userRepository->expects($this->once())->method('save');

        $useCase = new UpdateUser($this->userRepository, $this->passwordHasher);
        $result = $useCase->execute(10, 99, 'newname', 'new@example.com');

        $this->assertTrue($result['success']);
    }

    public function testUpdateUserNotFound(): void
    {
        $this->userRepository->method('find')->willReturn(null);

        $useCase = new UpdateUser($this->userRepository, $this->passwordHasher);
        $result = $useCase->execute(999, 1, 'name', 'email@example.com');

        $this->assertFalse($result['success']);
        $this->assertContains('User not found', $result['errors']);
    }

    public function testUpdateUserSelfDemoteGuard(): void
    {
        $user = $this->makeUser(5, 'admin', 'admin@example.com', User::ROLE_ADMIN);

        $this->userRepository->method('find')->with(5)->willReturn($user);
        $this->userRepository->method('usernameExists')->willReturn(false);
        $this->userRepository->method('emailExists')->willReturn(false);

        $useCase = new UpdateUser($this->userRepository, $this->passwordHasher);
        // Admin 5 tries to change own role to user
        $result = $useCase->execute(5, 5, 'admin', 'admin@example.com', '', User::ROLE_USER, true);

        $this->assertFalse($result['success']);
        $this->assertContains('Cannot demote yourself from admin', $result['errors']);
    }

    public function testUpdateUserSelfDeactivateGuard(): void
    {
        $user = $this->makeUser(5, 'admin', 'admin@example.com', User::ROLE_ADMIN);

        $this->userRepository->method('find')->with(5)->willReturn($user);
        $this->userRepository->method('usernameExists')->willReturn(false);
        $this->userRepository->method('emailExists')->willReturn(false);

        $useCase = new UpdateUser($this->userRepository, $this->passwordHasher);
        $result = $useCase->execute(5, 5, 'admin', 'admin@example.com', '', User::ROLE_ADMIN, false);

        $this->assertFalse($result['success']);
        $this->assertContains('Cannot deactivate your own account', $result['errors']);
    }

    public function testUpdateUserSkipPasswordWhenEmpty(): void
    {
        $user = $this->makeUser(10, 'user', 'user@example.com');

        $this->userRepository->method('find')->with(10)->willReturn($user);
        $this->userRepository->method('usernameExists')->willReturn(false);
        $this->userRepository->method('emailExists')->willReturn(false);
        $this->userRepository->expects($this->once())->method('save');

        // Password hasher should NOT be called
        $this->passwordHasher->expects($this->never())->method('hash');

        $useCase = new UpdateUser($this->userRepository, $this->passwordHasher);
        $result = $useCase->execute(10, 99, 'user', 'user@example.com', '');

        $this->assertTrue($result['success']);
    }

    public function testUpdateUserWithNewPassword(): void
    {
        $user = $this->makeUser(10, 'user', 'user@example.com');

        $this->userRepository->method('find')->with(10)->willReturn($user);
        $this->userRepository->method('usernameExists')->willReturn(false);
        $this->userRepository->method('emailExists')->willReturn(false);
        $this->userRepository->expects($this->once())->method('save');

        $this->passwordHasher->method('validateStrength')
            ->willReturn(['valid' => true, 'errors' => []]);
        $this->passwordHasher->expects($this->once())
            ->method('hash')
            ->with('NewPassword123!')
            ->willReturn('new_hashed_pw');

        $useCase = new UpdateUser($this->userRepository, $this->passwordHasher);
        $result = $useCase->execute(10, 99, 'user', 'user@example.com', 'NewPassword123!');

        $this->assertTrue($result['success']);
    }

    public function testUpdateUserDuplicateUsername(): void
    {
        $user = $this->makeUser(10, 'user', 'user@example.com');

        $this->userRepository->method('find')->with(10)->willReturn($user);
        $this->userRepository->method('usernameExists')
            ->with('taken', 10)
            ->willReturn(true);
        $this->userRepository->method('emailExists')->willReturn(false);

        $useCase = new UpdateUser($this->userRepository, $this->passwordHasher);
        $result = $useCase->execute(10, 99, 'taken', 'user@example.com');

        $this->assertFalse($result['success']);
        $this->assertContains('Username already exists', $result['errors']);
    }

    // ===== DeleteUser =====

    public function testDeleteUserSuccess(): void
    {
        $user = $this->makeUser(10);

        $this->userRepository->method('find')->with(10)->willReturn($user);
        $this->userRepository->expects($this->once())->method('delete')->with(10);

        $useCase = new DeleteUser($this->userRepository);
        $result = $useCase->execute(10, 99);

        $this->assertTrue($result['success']);
    }

    public function testDeleteUserSelfDeleteGuard(): void
    {
        $useCase = new DeleteUser($this->userRepository);
        $result = $useCase->execute(5, 5);

        $this->assertFalse($result['success']);
        $this->assertEquals('Cannot delete your own account', $result['error']);
    }

    public function testDeleteUserNotFound(): void
    {
        $this->userRepository->method('find')->willReturn(null);

        $useCase = new DeleteUser($this->userRepository);
        $result = $useCase->execute(999, 1);

        $this->assertFalse($result['success']);
        $this->assertEquals('User not found', $result['error']);
    }

    // ===== ToggleUserStatus =====

    public function testActivateUserSuccess(): void
    {
        $user = $this->makeUser(10, 'user', 'user@example.com', User::ROLE_USER, false);

        $this->userRepository->method('find')->with(10)->willReturn($user);
        $this->userRepository->expects($this->once())->method('activate')->with(10);

        $useCase = new ToggleUserStatus($this->userRepository);
        $result = $useCase->activate(10, 99);

        $this->assertTrue($result['success']);
    }

    public function testDeactivateUserSuccess(): void
    {
        $user = $this->makeUser(10);

        $this->userRepository->method('find')->with(10)->willReturn($user);
        $this->userRepository->expects($this->once())->method('deactivate')->with(10);

        $useCase = new ToggleUserStatus($this->userRepository);
        $result = $useCase->deactivate(10, 99);

        $this->assertTrue($result['success']);
    }

    public function testDeactivateSelfGuard(): void
    {
        $useCase = new ToggleUserStatus($this->userRepository);
        $result = $useCase->deactivate(5, 5);

        $this->assertFalse($result['success']);
        $this->assertEquals('Cannot deactivate your own account', $result['error']);
    }

    public function testActivateUserNotFound(): void
    {
        $this->userRepository->method('find')->willReturn(null);

        $useCase = new ToggleUserStatus($this->userRepository);
        $result = $useCase->activate(999, 1);

        $this->assertFalse($result['success']);
        $this->assertEquals('User not found', $result['error']);
    }

    public function testDeactivateUserNotFound(): void
    {
        $this->userRepository->method('find')->willReturn(null);

        $useCase = new ToggleUserStatus($this->userRepository);
        $result = $useCase->deactivate(999, 1);

        $this->assertFalse($result['success']);
        $this->assertEquals('User not found', $result['error']);
    }

    // ===== ToggleUserRole =====

    public function testPromoteUserSuccess(): void
    {
        $user = $this->makeUser(10);

        $this->mysqlUserRepository->method('find')->with(10)->willReturn($user);
        $this->mysqlUserRepository->expects($this->once())
            ->method('updateRole')
            ->with(10, User::ROLE_ADMIN);

        $useCase = new ToggleUserRole($this->mysqlUserRepository);
        $result = $useCase->promote(10, 99);

        $this->assertTrue($result['success']);
    }

    public function testDemoteUserSuccess(): void
    {
        $user = $this->makeUser(10, 'admin', 'admin@example.com', User::ROLE_ADMIN);

        $this->mysqlUserRepository->method('find')->with(10)->willReturn($user);
        $this->mysqlUserRepository->expects($this->once())
            ->method('updateRole')
            ->with(10, User::ROLE_USER);

        $useCase = new ToggleUserRole($this->mysqlUserRepository);
        $result = $useCase->demote(10, 99);

        $this->assertTrue($result['success']);
    }

    public function testDemoteSelfGuard(): void
    {
        $useCase = new ToggleUserRole($this->mysqlUserRepository);
        $result = $useCase->demote(5, 5);

        $this->assertFalse($result['success']);
        $this->assertEquals('Cannot demote yourself from admin', $result['error']);
    }

    public function testPromoteUserNotFound(): void
    {
        $this->mysqlUserRepository->method('find')->willReturn(null);

        $useCase = new ToggleUserRole($this->mysqlUserRepository);
        $result = $useCase->promote(999, 1);

        $this->assertFalse($result['success']);
        $this->assertEquals('User not found', $result['error']);
    }

    public function testDemoteUserNotFound(): void
    {
        $this->mysqlUserRepository->method('find')->willReturn(null);

        $useCase = new ToggleUserRole($this->mysqlUserRepository);
        $result = $useCase->demote(999, 1);

        $this->assertFalse($result['success']);
        $this->assertEquals('User not found', $result['error']);
    }
}
