<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\User\Application\UseCases;

use DateTimeImmutable;
use Lwt\Modules\User\Application\UseCases\ValidateSession;
use Lwt\Modules\User\Domain\User;
use Lwt\Modules\User\Domain\UserRepositoryInterface;
use Lwt\Shared\Infrastructure\Globals;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the ValidateSession use case.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class ValidateSessionTest extends TestCase
{
    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $repository;

    private ValidateSession $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->useCase = new ValidateSession($this->repository);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        Globals::setCurrentUserId(null);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        parent::tearDown();
    }

    private function createUser(
        int $id,
        string $username = 'testuser',
        string $email = 'test@example.com',
        bool $isActive = true
    ): User {
        return User::reconstitute(
            id: $id,
            username: $username,
            email: $email,
            passwordHash: 'hashed_password',
            apiToken: null,
            apiTokenExpires: null,
            rememberToken: null,
            rememberTokenExpires: null,
            passwordResetToken: null,
            passwordResetTokenExpires: null,
            emailVerifiedAt: null,
            emailVerificationToken: null,
            emailVerificationTokenExpires: null,
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
    // execute() - Happy Path
    // =========================================================================

    #[Test]
    public function executeReturnsUserForValidSession(): void
    {
        session_start();
        $_SESSION['LWT_USER_ID'] = 42;

        $user = $this->createUser(42, 'alice');

        $this->repository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($user);

        $result = $this->useCase->execute();

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(42, $result->id()->toInt());
        $this->assertEquals('alice', $result->username());
    }

    #[Test]
    public function executeRestoresUserContextInGlobals(): void
    {
        session_start();
        $_SESSION['LWT_USER_ID'] = 7;

        $user = $this->createUser(7);

        $this->repository->method('find')
            ->willReturn($user);

        $this->useCase->execute();

        $this->assertEquals(7, Globals::getCurrentUserId());
    }

    // =========================================================================
    // execute() - No Session
    // =========================================================================

    #[Test]
    public function executeReturnsNullWhenNoSessionUserId(): void
    {
        session_start();
        // No LWT_USER_ID in session

        $this->repository->expects($this->never())
            ->method('find');

        $result = $this->useCase->execute();

        $this->assertNull($result);
    }

    #[Test]
    public function executeStartsSessionIfNoneActive(): void
    {
        // Ensure no session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        // No session data means no user ID
        $this->repository->expects($this->never())
            ->method('find');

        $result = $this->useCase->execute();

        $this->assertNull($result);
    }

    // =========================================================================
    // execute() - User Not Found
    // =========================================================================

    #[Test]
    public function executeReturnsNullAndDestroysSessionWhenUserNotFound(): void
    {
        session_start();
        $_SESSION['LWT_USER_ID'] = 999;

        $this->repository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->useCase->execute();

        $this->assertNull($result);
    }

    // =========================================================================
    // execute() - Inactive User
    // =========================================================================

    #[Test]
    public function executeReturnsNullForInactiveUser(): void
    {
        session_start();
        $_SESSION['LWT_USER_ID'] = 3;

        $inactiveUser = $this->createUser(3, 'inactive', 'inactive@test.com', false);

        $this->repository->expects($this->once())
            ->method('find')
            ->with(3)
            ->willReturn($inactiveUser);

        $result = $this->useCase->execute();

        $this->assertNull($result);
    }

    #[Test]
    public function executeDestroysSessionForInactiveUser(): void
    {
        session_start();
        $_SESSION['LWT_USER_ID'] = 3;
        $_SESSION['other_data'] = 'should_be_cleared';

        $inactiveUser = $this->createUser(3, 'inactive', 'inactive@test.com', false);

        $this->repository->method('find')
            ->willReturn($inactiveUser);

        $this->useCase->execute();

        $this->assertEmpty($_SESSION);
    }

    // =========================================================================
    // execute() - Repository Errors
    // =========================================================================

    #[Test]
    public function executeReturnsNullWhenRepositoryThrowsRuntimeException(): void
    {
        session_start();
        $_SESSION['LWT_USER_ID'] = 5;

        $this->repository->expects($this->once())
            ->method('find')
            ->with(5)
            ->willThrowException(new \RuntimeException('Database not initialized'));

        $result = $this->useCase->execute();

        $this->assertNull($result);
    }

    #[Test]
    public function executeDoesNotRestoreUserContextOnRepositoryError(): void
    {
        session_start();
        $_SESSION['LWT_USER_ID'] = 5;
        Globals::setCurrentUserId(null);

        $this->repository->method('find')
            ->willThrowException(new \RuntimeException('DB error'));

        $this->useCase->execute();

        $this->assertNull(Globals::getCurrentUserId());
    }

    // =========================================================================
    // execute() - Session User ID Type Casting
    // =========================================================================

    #[Test]
    public function executeCastsSessionUserIdToInt(): void
    {
        session_start();
        $_SESSION['LWT_USER_ID'] = '42'; // String value in session

        $user = $this->createUser(42);

        $this->repository->expects($this->once())
            ->method('find')
            ->with(42) // Should be cast to int
            ->willReturn($user);

        $result = $this->useCase->execute();

        $this->assertNotNull($result);
        $this->assertEquals(42, $result->id()->toInt());
    }

    // =========================================================================
    // execute() - Does Not Restore Context for Invalid Sessions
    // =========================================================================

    #[Test]
    public function executeDoesNotSetGlobalsUserIdWhenUserNotFound(): void
    {
        session_start();
        $_SESSION['LWT_USER_ID'] = 999;
        Globals::setCurrentUserId(null);

        $this->repository->method('find')
            ->willReturn(null);

        $this->useCase->execute();

        $this->assertNull(Globals::getCurrentUserId());
    }

    #[Test]
    public function executeDoesNotSetGlobalsUserIdForInactiveUser(): void
    {
        session_start();
        $_SESSION['LWT_USER_ID'] = 3;
        Globals::setCurrentUserId(null);

        $inactiveUser = $this->createUser(3, 'inactive', 'inactive@test.com', false);

        $this->repository->method('find')
            ->willReturn($inactiveUser);

        $this->useCase->execute();

        $this->assertNull(Globals::getCurrentUserId());
    }

    // =========================================================================
    // execute() - Admin User
    // =========================================================================

    #[Test]
    public function executeReturnsAdminUser(): void
    {
        session_start();
        $_SESSION['LWT_USER_ID'] = 1;

        $adminUser = User::reconstitute(
            id: 1,
            username: 'admin',
            email: 'admin@example.com',
            passwordHash: 'hashed',
            apiToken: null,
            apiTokenExpires: null,
            rememberToken: null,
            rememberTokenExpires: null,
            passwordResetToken: null,
            passwordResetTokenExpires: null,
            emailVerifiedAt: null,
            emailVerificationToken: null,
            emailVerificationTokenExpires: null,
            wordPressId: null,
            googleId: null,
            microsoftId: null,
            created: new DateTimeImmutable('2024-01-01'),
            lastLogin: null,
            isActive: true,
            role: User::ROLE_ADMIN
        );

        $this->repository->method('find')
            ->willReturn($adminUser);

        $result = $this->useCase->execute();

        $this->assertNotNull($result);
        $this->assertTrue($result->isAdmin());
        $this->assertEquals(1, Globals::getCurrentUserId());
    }
}
