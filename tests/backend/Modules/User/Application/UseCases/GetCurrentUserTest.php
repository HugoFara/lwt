<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\User\Application\UseCases;

use DateTimeImmutable;
use Lwt\Modules\User\Application\UseCases\GetCurrentUser;
use Lwt\Modules\User\Domain\User;
use Lwt\Modules\User\Domain\UserRepositoryInterface;
use Lwt\Shared\Infrastructure\Globals;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the GetCurrentUser use case.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class GetCurrentUserTest extends TestCase
{
    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $repository;

    private GetCurrentUser $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->useCase = new GetCurrentUser($this->repository);
    }

    protected function tearDown(): void
    {
        Globals::setCurrentUserId(null);
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
    public function executeReturnsUserWhenAuthenticated(): void
    {
        $user = $this->createUser(42, 'alice');
        Globals::setCurrentUserId(42);

        $this->repository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($user);

        $result = $this->useCase->execute();

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(42, $result->id()->toInt());
        $this->assertEquals('alice', $result->username());
    }

    // =========================================================================
    // execute() - No Current User
    // =========================================================================

    #[Test]
    public function executeReturnsNullWhenNoCurrentUserId(): void
    {
        Globals::setCurrentUserId(null);

        $this->repository->expects($this->never())
            ->method('find');

        $result = $this->useCase->execute();

        $this->assertNull($result);
    }

    // =========================================================================
    // execute() - Repository Errors
    // =========================================================================

    #[Test]
    public function executeReturnsNullWhenRepositoryThrowsRuntimeException(): void
    {
        Globals::setCurrentUserId(10);

        $this->repository->expects($this->once())
            ->method('find')
            ->with(10)
            ->willThrowException(new \RuntimeException('DB error'));

        // Redirect error_log to a temp file to prevent PHPUnit from
        // capturing stderr output as a test error (Windows CI issue)
        $previousLog = ini_set('error_log', tempnam(sys_get_temp_dir(), 'phpunit'));
        try {
            $result = $this->useCase->execute();
        } finally {
            ini_set('error_log', $previousLog !== false ? $previousLog : '');
        }

        $this->assertNull($result);
    }

    #[Test]
    public function executeReturnsNullWhenRepositoryReturnsNull(): void
    {
        Globals::setCurrentUserId(999);

        $this->repository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->useCase->execute();

        $this->assertNull($result);
    }

    // =========================================================================
    // Caching Behavior
    // =========================================================================

    #[Test]
    public function executeReturnsCachedUserOnSecondCall(): void
    {
        $user = $this->createUser(1);
        Globals::setCurrentUserId(1);

        $this->repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $firstResult = $this->useCase->execute();
        $secondResult = $this->useCase->execute();

        $this->assertSame($firstResult, $secondResult);
    }

    #[Test]
    public function executeDoesNotQueryRepositoryWhenCached(): void
    {
        $user = $this->createUser(5);
        Globals::setCurrentUserId(5);

        // Repository should only be called once
        $this->repository->expects($this->once())
            ->method('find')
            ->willReturn($user);

        $this->useCase->execute();
        $this->useCase->execute();
        $this->useCase->execute();
    }

    // =========================================================================
    // clearCache()
    // =========================================================================

    #[Test]
    public function clearCacheForcesNewRepositoryLookup(): void
    {
        $user = $this->createUser(1);
        Globals::setCurrentUserId(1);

        $this->repository->expects($this->exactly(2))
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $this->useCase->execute();
        $this->useCase->clearCache();
        $this->useCase->execute();
    }

    #[Test]
    public function clearCacheAllowsNullResultAfterPreviousUser(): void
    {
        $user = $this->createUser(1);
        Globals::setCurrentUserId(1);

        $this->repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $firstResult = $this->useCase->execute();
        $this->assertNotNull($firstResult);

        $this->useCase->clearCache();
        Globals::setCurrentUserId(null);

        $secondResult = $this->useCase->execute();
        $this->assertNull($secondResult);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    #[Test]
    public function executeDoesNotCacheNullResult(): void
    {
        Globals::setCurrentUserId(null);

        $result1 = $this->useCase->execute();
        $this->assertNull($result1);

        // Now set a user and verify it queries the repository
        $user = $this->createUser(7);
        Globals::setCurrentUserId(7);

        $this->repository->expects($this->once())
            ->method('find')
            ->with(7)
            ->willReturn($user);

        $result2 = $this->useCase->execute();
        $this->assertNotNull($result2);
        $this->assertEquals(7, $result2->id()->toInt());
    }
}
