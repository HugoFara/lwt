<?php

declare(strict_types=1);

namespace Lwt\Tests\Services;

use DateTimeImmutable;
use Lwt\Core\Entity\User;
use Lwt\Core\Exception\AuthException;
use Lwt\Core\Globals;
use Lwt\Modules\User\Application\Services\AuthService;
use Lwt\Modules\User\Application\Services\PasswordService;
use Lwt\Modules\User\Infrastructure\MySqlUserRepository;
use Lwt\Shared\Domain\ValueObjects\UserId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for AuthService with mocked repository.
 *
 * These tests use mocks to test business logic without requiring a database.
 */
class AuthServiceWithMocksTest extends TestCase
{
    private AuthService $service;
    private PasswordService $passwordService;
    /** @var MySqlUserRepository&MockObject */
    private MySqlUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        Globals::reset();

        $this->passwordService = new PasswordService();
        $this->repository = $this->createMock(MySqlUserRepository::class);
        $this->service = new AuthService($this->passwordService, $this->repository);
    }

    protected function tearDown(): void
    {
        Globals::reset();
        parent::tearDown();
    }

    private function createActiveUser(int $id = 42, string $username = 'testuser'): User
    {
        $user = User::create($username, $username . '@example.com', 'hashedpassword');
        $user->setId(UserId::fromInt($id));
        return $user;
    }

    // =========================================================================
    // login() Tests
    // =========================================================================

    public function testLoginSucceedsWithValidCredentials(): void
    {
        $password = 'StrongP@ss1';
        $hashedPassword = $this->passwordService->hash($password);

        $user = User::create('testuser', 'test@example.com', $hashedPassword);
        $user->setId(UserId::fromInt(42));

        $this->repository->method('findByUsername')->willReturn($user);
        $this->repository->method('save')->willReturn(42);

        $result = $this->service->login('testuser', $password);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('testuser', $result->username());
    }

    public function testLoginSucceedsWithEmailAsIdentifier(): void
    {
        $password = 'StrongP@ss1';
        $hashedPassword = $this->passwordService->hash($password);

        $user = User::create('testuser', 'test@example.com', $hashedPassword);
        $user->setId(UserId::fromInt(42));

        $this->repository->method('findByUsername')->willReturn(null);
        $this->repository->method('findByEmail')->willReturn($user);
        $this->repository->method('save')->willReturn(42);

        $result = $this->service->login('test@example.com', $password);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('test@example.com', $result->email());
    }

    public function testLoginThrowsExceptionForNonExistentUser(): void
    {
        $this->repository->method('findByUsername')->willReturn(null);
        $this->repository->method('findByEmail')->willReturn(null);

        $this->expectException(AuthException::class);

        $this->service->login('nonexistent', 'password');
    }

    public function testLoginThrowsExceptionForWrongPassword(): void
    {
        $user = User::create('testuser', 'test@example.com', 'differenthash');
        $user->setId(UserId::fromInt(42));

        $this->repository->method('findByUsername')->willReturn($user);

        $this->expectException(AuthException::class);

        $this->service->login('testuser', 'wrongpassword');
    }

    public function testLoginThrowsExceptionForDisabledAccount(): void
    {
        $password = 'StrongP@ss1';
        $hashedPassword = $this->passwordService->hash($password);

        $user = User::create('testuser', 'test@example.com', $hashedPassword);
        $user->setId(UserId::fromInt(42));
        $user->deactivate(); // Disable the account

        $this->repository->method('findByUsername')->willReturn($user);

        $this->expectException(AuthException::class);

        $this->service->login('testuser', $password);
    }

    // =========================================================================
    // register() Tests
    // =========================================================================

    public function testRegisterCreatesNewUser(): void
    {
        $this->repository->method('findByUsername')->willReturn(null);
        $this->repository->method('findByEmail')->willReturn(null);
        $this->repository->method('save')->willReturn(1);

        $result = $this->service->register('newuser', 'new@example.com', 'StrongP@ss1');

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('newuser', $result->username());
        $this->assertEquals('new@example.com', $result->email());
    }

    public function testRegisterThrowsExceptionForWeakPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->register('newuser', 'new@example.com', 'weak');
    }

    public function testRegisterThrowsExceptionForDuplicateUsername(): void
    {
        $existingUser = $this->createActiveUser();

        $this->repository->method('findByUsername')->willReturn($existingUser);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Username is already taken');

        $this->service->register('testuser', 'new@example.com', 'StrongP@ss1');
    }

    public function testRegisterThrowsExceptionForDuplicateEmail(): void
    {
        $existingUser = $this->createActiveUser();

        $this->repository->method('findByUsername')->willReturn(null);
        $this->repository->method('findByEmail')->willReturn($existingUser);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email is already registered');

        $this->service->register('newuser', 'testuser@example.com', 'StrongP@ss1');
    }

    // =========================================================================
    // generateApiToken() Tests
    // =========================================================================

    public function testGenerateApiTokenReturnsToken(): void
    {
        $user = $this->createActiveUser();

        $this->repository->method('find')->willReturn($user);
        $this->repository->method('save')->willReturn(42);

        $token = $this->service->generateApiToken(42);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function testGenerateApiTokenThrowsExceptionForNonExistentUser(): void
    {
        $this->repository->method('find')->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User not found');

        $this->service->generateApiToken(999);
    }

    // =========================================================================
    // validateApiToken() Tests
    // =========================================================================

    public function testValidateApiTokenReturnsUserForValidToken(): void
    {
        $user = $this->createActiveUser();
        $user->setApiToken('validtoken123', new DateTimeImmutable('+30 days'));

        $this->repository->method('findByApiToken')->willReturn($user);

        $result = $this->service->validateApiToken('validtoken123');

        $this->assertInstanceOf(User::class, $result);
    }

    public function testValidateApiTokenReturnsNullForExpiredToken(): void
    {
        $user = $this->createActiveUser();
        $user->setApiToken('expiredtoken', new DateTimeImmutable('-1 day'));

        $this->repository->method('findByApiToken')->willReturn($user);

        $result = $this->service->validateApiToken('expiredtoken');

        $this->assertNull($result);
    }

    public function testValidateApiTokenReturnsNullForDisabledUser(): void
    {
        $user = $this->createActiveUser();
        $user->setApiToken('validtoken', new DateTimeImmutable('+30 days'));
        $user->deactivate();

        $this->repository->method('findByApiToken')->willReturn($user);

        $result = $this->service->validateApiToken('validtoken');

        $this->assertNull($result);
    }

    public function testValidateApiTokenReturnsNullForUnknownToken(): void
    {
        $this->repository->method('findByApiToken')->willReturn(null);

        $result = $this->service->validateApiToken('unknowntoken');

        $this->assertNull($result);
    }

    // =========================================================================
    // invalidateApiToken() Tests
    // =========================================================================

    public function testInvalidateApiTokenClearsToken(): void
    {
        $user = $this->createActiveUser();

        $this->repository->method('find')->willReturn($user);
        $this->repository->expects($this->once())->method('save');

        $this->service->invalidateApiToken(42);

        // Verify the user's token was invalidated
        $this->assertFalse($user->hasValidApiToken());
    }

    public function testInvalidateApiTokenDoesNothingForNonExistentUser(): void
    {
        $this->repository->method('find')->willReturn(null);
        $this->repository->expects($this->never())->method('save');

        // Should not throw
        $this->service->invalidateApiToken(999);
        $this->assertTrue(true); // Assert we got here without exception
    }

    // =========================================================================
    // findOrCreateWordPressUser() Tests
    // =========================================================================

    public function testFindOrCreateWordPressUserReturnsExistingUser(): void
    {
        $user = $this->createActiveUser();

        $this->repository->method('findByWordPressId')->willReturn($user);

        $result = $this->service->findOrCreateWordPressUser(123, 'wpuser', 'wp@example.com');

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(42, $result->id()->toInt());
    }

    public function testFindOrCreateWordPressUserLinksExistingEmailUser(): void
    {
        $user = $this->createActiveUser();

        $this->repository->method('findByWordPressId')->willReturn(null);
        $this->repository->method('findByEmail')->willReturn($user);
        $this->repository->method('save')->willReturn(42);

        $result = $this->service->findOrCreateWordPressUser(123, 'wpuser', 'testuser@example.com');

        $this->assertInstanceOf(User::class, $result);
    }

    public function testFindOrCreateWordPressUserCreatesNewUser(): void
    {
        $this->repository->method('findByWordPressId')->willReturn(null);
        $this->repository->method('findByEmail')->willReturn(null);
        $this->repository->method('save')->willReturn(1);

        $result = $this->service->findOrCreateWordPressUser(123, 'wpuser', 'wp@example.com');

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('wpuser', $result->username());
        $this->assertEquals('wp@example.com', $result->email());
    }

    // =========================================================================
    // getCurrentUser() Tests with cached user
    // =========================================================================

    public function testGetCurrentUserReturnsCachedUser(): void
    {
        $user = $this->createActiveUser();

        $this->service->setCurrentUser($user);

        // Repository should NOT be called since user is cached
        $this->repository->expects($this->never())->method('find');

        $result = $this->service->getCurrentUser();

        $this->assertSame($user, $result);
    }

    public function testGetCurrentUserLoadsFromRepositoryWhenNotCached(): void
    {
        $user = $this->createActiveUser();

        Globals::setCurrentUserId(42);
        $this->repository->method('find')->willReturn($user);

        $result = $this->service->getCurrentUser();

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(42, $result->id()->toInt());
    }
}
