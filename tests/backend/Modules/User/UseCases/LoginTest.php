<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\User\UseCases;

use DateTimeImmutable;
use Lwt\Modules\User\Domain\User;
use Lwt\Core\Exception\AuthException;
use Lwt\Modules\User\Application\Services\PasswordHasher;
use Lwt\Modules\User\Application\UseCases\Login;
use Lwt\Modules\User\Domain\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Login use case.
 *
 * Note: Session handling is tested indirectly as it depends on PHP session functions.
 * The core authentication logic is tested thoroughly here.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class LoginTest extends TestCase
{
    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $repository;

    /** @var PasswordHasher&MockObject */
    private PasswordHasher $passwordHasher;

    private Login $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(PasswordHasher::class);
        $this->useCase = new Login($this->repository, $this->passwordHasher);
    }

    private function createUser(
        int $id,
        string $username = 'testuser',
        string $email = 'test@example.com',
        string $passwordHash = 'hashed_password',
        bool $isActive = true
    ): User {
        return User::reconstitute(
            id: $id,
            username: $username,
            email: $email,
            passwordHash: $passwordHash,
            apiToken: null,
            apiTokenExpires: null,
            rememberToken: null,
            rememberTokenExpires: null,
            passwordResetToken: null,
            passwordResetTokenExpires: null,
            wordPressId: null,
            googleId: null,
            microsoftId: null,
            created: new DateTimeImmutable('2024-01-01'),
            lastLogin: null,
            isActive: $isActive,
            role: User::ROLE_USER
        );
    }

    // =========================================================================
    // execute() Tests - Authentication Logic
    // =========================================================================

    public function testExecuteAuthenticatesWithUsername(): void
    {
        $user = $this->createUser(1, 'testuser');

        $this->repository->expects($this->once())
            ->method('findByUsername')
            ->with('testuser')
            ->willReturn($user);

        $this->passwordHasher->expects($this->once())
            ->method('verify')
            ->with('correct_password', 'hashed_password')
            ->willReturn(true);

        $this->passwordHasher->method('needsRehash')
            ->willReturn(false);

        $this->repository->method('save');

        $result = $this->useCase->execute('testuser', 'correct_password');

        $this->assertEquals(1, $result->id()->toInt());
        $this->assertEquals('testuser', $result->username());
    }

    public function testExecuteAuthenticatesWithEmail(): void
    {
        $user = $this->createUser(1, 'testuser', 'test@example.com');

        // Username lookup returns null
        $this->repository->expects($this->once())
            ->method('findByUsername')
            ->with('test@example.com')
            ->willReturn(null);

        // Email lookup returns user
        $this->repository->expects($this->once())
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn($user);

        $this->passwordHasher->method('verify')
            ->willReturn(true);

        $this->passwordHasher->method('needsRehash')
            ->willReturn(false);

        $this->repository->method('save');

        $result = $this->useCase->execute('test@example.com', 'password');

        $this->assertEquals('test@example.com', $result->email());
    }

    public function testExecuteThrowsExceptionForNonExistentUser(): void
    {
        $this->repository->method('findByUsername')
            ->willReturn(null);

        $this->repository->method('findByEmail')
            ->willReturn(null);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Invalid username or password');

        $this->useCase->execute('nonexistent', 'password');
    }

    public function testExecuteThrowsExceptionForInactiveUser(): void
    {
        $inactiveUser = $this->createUser(1, 'testuser', 'test@example.com', 'hash', false);

        $this->repository->method('findByUsername')
            ->willReturn($inactiveUser);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('account has been disabled');

        $this->useCase->execute('testuser', 'password');
    }

    public function testExecuteThrowsExceptionForWrongPassword(): void
    {
        $user = $this->createUser(1);

        $this->repository->method('findByUsername')
            ->willReturn($user);

        $this->passwordHasher->expects($this->once())
            ->method('verify')
            ->with('wrong_password', 'hashed_password')
            ->willReturn(false);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Invalid username or password');

        $this->useCase->execute('testuser', 'wrong_password');
    }

    public function testExecuteThrowsExceptionForUserWithNullPassword(): void
    {
        // User created via OAuth might have no password
        $oauthUser = User::reconstitute(
            id: 1,
            username: 'oauthuser',
            email: 'oauth@example.com',
            passwordHash: null,  // No password
            apiToken: null,
            apiTokenExpires: null,
            rememberToken: null,
            rememberTokenExpires: null,
            passwordResetToken: null,
            passwordResetTokenExpires: null,
            wordPressId: null,
            googleId: 'google123',
            microsoftId: null,
            created: new DateTimeImmutable('2024-01-01'),
            lastLogin: null,
            isActive: true,
            role: User::ROLE_USER
        );

        $this->repository->method('findByUsername')
            ->willReturn($oauthUser);

        $this->expectException(AuthException::class);

        $this->useCase->execute('oauthuser', 'password');
    }

    // =========================================================================
    // Password Rehashing Tests
    // =========================================================================

    public function testExecuteRehashesPasswordWhenNeeded(): void
    {
        $user = $this->createUser(1);

        $this->repository->method('findByUsername')
            ->willReturn($user);

        $this->passwordHasher->method('verify')
            ->willReturn(true);

        $this->passwordHasher->expects($this->once())
            ->method('needsRehash')
            ->with('hashed_password')
            ->willReturn(true);

        $this->passwordHasher->expects($this->once())
            ->method('hash')
            ->with('password123')
            ->willReturn('new_stronger_hash');

        // Should save twice: once for rehash, once for login record
        $this->repository->expects($this->exactly(2))
            ->method('save')
            ->with($user);

        $this->useCase->execute('testuser', 'password123');
    }

    public function testExecuteDoesNotRehashWhenNotNeeded(): void
    {
        $user = $this->createUser(1);

        $this->repository->method('findByUsername')
            ->willReturn($user);

        $this->passwordHasher->method('verify')
            ->willReturn(true);

        $this->passwordHasher->method('needsRehash')
            ->willReturn(false);

        $this->passwordHasher->expects($this->never())
            ->method('hash');

        // Should save only once for login record
        $this->repository->expects($this->once())
            ->method('save');

        $this->useCase->execute('testuser', 'password');
    }

    // =========================================================================
    // Login Recording Tests
    // =========================================================================

    public function testExecuteRecordsLogin(): void
    {
        $user = $this->createUser(1);
        $capturedUser = null;

        $this->repository->method('findByUsername')
            ->willReturn($user);

        $this->passwordHasher->method('verify')
            ->willReturn(true);

        $this->passwordHasher->method('needsRehash')
            ->willReturn(false);

        $this->repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (User $u) use (&$capturedUser) {
                $capturedUser = $u;
                return 1;
            });

        $this->useCase->execute('testuser', 'password');

        // lastLogin should be updated
        $this->assertNotNull($capturedUser->lastLogin());
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testExecuteVerifiesPasswordBeforeCheckingRehash(): void
    {
        $user = $this->createUser(1);

        $this->repository->method('findByUsername')
            ->willReturn($user);

        // Password verification should happen first
        $this->passwordHasher->expects($this->once())
            ->method('verify')
            ->willReturn(false);

        // Should not check needsRehash if password is wrong
        $this->passwordHasher->expects($this->never())
            ->method('needsRehash');

        $this->expectException(AuthException::class);

        $this->useCase->execute('testuser', 'wrong');
    }

    public function testExecuteChecksCanLoginBeforePasswordVerification(): void
    {
        $inactiveUser = $this->createUser(1, 'testuser', 'test@example.com', 'hash', false);

        $this->repository->method('findByUsername')
            ->willReturn($inactiveUser);

        // Should not verify password for disabled account
        $this->passwordHasher->expects($this->never())
            ->method('verify');

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('disabled');

        $this->useCase->execute('testuser', 'password');
    }

    public function testExecuteReturnsUserOnSuccess(): void
    {
        $user = $this->createUser(42, 'myuser', 'my@email.com');

        $this->repository->method('findByUsername')
            ->willReturn($user);

        $this->passwordHasher->method('verify')
            ->willReturn(true);

        $this->passwordHasher->method('needsRehash')
            ->willReturn(false);

        $this->repository->method('save');

        $result = $this->useCase->execute('myuser', 'password');

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(42, $result->id()->toInt());
        $this->assertEquals('myuser', $result->username());
        $this->assertEquals('my@email.com', $result->email());
    }
}
