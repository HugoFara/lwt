<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\User\UseCases;

use DateTimeImmutable;
use Lwt\Core\Entity\User;
use Lwt\Modules\User\Application\Services\TokenHasher;
use Lwt\Modules\User\Application\UseCases\ValidateApiToken;
use Lwt\Modules\User\Domain\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the ValidateApiToken use case.
 */
class ValidateApiTokenTest extends TestCase
{
    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $repository;

    /** @var TokenHasher&MockObject */
    private TokenHasher $tokenHasher;

    private ValidateApiToken $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->tokenHasher = $this->createMock(TokenHasher::class);
        $this->useCase = new ValidateApiToken($this->repository, $this->tokenHasher);
    }

    private function createUser(
        int $id,
        bool $isActive = true,
        ?string $apiToken = 'hashed_token',
        ?DateTimeImmutable $apiTokenExpires = null
    ): User {
        return User::reconstitute(
            id: $id,
            username: 'testuser',
            email: 'test@example.com',
            passwordHash: 'hashed_password',
            apiToken: $apiToken,
            apiTokenExpires: $apiTokenExpires ?? new DateTimeImmutable('+1 day'),
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
    // execute() Tests
    // =========================================================================

    public function testExecuteReturnsUserForValidToken(): void
    {
        $user = $this->createUser(1);

        $this->tokenHasher->expects($this->once())
            ->method('hash')
            ->with('plaintext_token')
            ->willReturn('hashed_token');

        $this->repository->expects($this->once())
            ->method('findByApiToken')
            ->with('hashed_token')
            ->willReturn($user);

        $result = $this->useCase->execute('plaintext_token');

        $this->assertNotNull($result);
        $this->assertEquals(1, $result->id()->toInt());
    }

    public function testExecuteReturnsNullForInvalidToken(): void
    {
        $this->tokenHasher->method('hash')
            ->willReturn('some_hash');

        $this->repository->expects($this->once())
            ->method('findByApiToken')
            ->with('some_hash')
            ->willReturn(null);

        $result = $this->useCase->execute('invalid_token');

        $this->assertNull($result);
    }

    public function testExecuteReturnsNullForExpiredToken(): void
    {
        $user = $this->createUser(
            id: 1,
            isActive: true,
            apiToken: 'hashed_token',
            apiTokenExpires: new DateTimeImmutable('-1 day')  // Expired
        );

        $this->tokenHasher->method('hash')
            ->willReturn('hashed_token');

        $this->repository->method('findByApiToken')
            ->willReturn($user);

        $result = $this->useCase->execute('some_token');

        $this->assertNull($result);
    }

    public function testExecuteReturnsNullForInactiveUser(): void
    {
        $user = $this->createUser(
            id: 1,
            isActive: false,
            apiToken: 'hashed_token',
            apiTokenExpires: new DateTimeImmutable('+1 day')
        );

        $this->tokenHasher->method('hash')
            ->willReturn('hashed_token');

        $this->repository->method('findByApiToken')
            ->willReturn($user);

        $result = $this->useCase->execute('some_token');

        $this->assertNull($result);
    }

    public function testExecuteHashesTokenBeforeLookup(): void
    {
        $this->tokenHasher->expects($this->once())
            ->method('hash')
            ->with('my_plaintext_token')
            ->willReturn('my_hashed_token');

        $this->repository->expects($this->once())
            ->method('findByApiToken')
            ->with('my_hashed_token')
            ->willReturn(null);

        $this->useCase->execute('my_plaintext_token');
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testExecuteReturnsNullOnRuntimeException(): void
    {
        $this->tokenHasher->method('hash')
            ->willReturn('hash');

        $this->repository->method('findByApiToken')
            ->willThrowException(new \RuntimeException('Database error'));

        $result = $this->useCase->execute('token');

        $this->assertNull($result);
    }

    public function testExecuteReturnsNullForUserWithNullToken(): void
    {
        $user = $this->createUser(
            id: 1,
            isActive: true,
            apiToken: null,
            apiTokenExpires: null
        );

        $this->tokenHasher->method('hash')
            ->willReturn('hash');

        $this->repository->method('findByApiToken')
            ->willReturn($user);

        $result = $this->useCase->execute('token');

        $this->assertNull($result);
    }

    public function testExecuteReturnsUserWhenAllConditionsMet(): void
    {
        $user = $this->createUser(
            id: 42,
            isActive: true,
            apiToken: 'valid_hash',
            apiTokenExpires: new DateTimeImmutable('+30 days')
        );

        $this->tokenHasher->method('hash')
            ->willReturn('valid_hash');

        $this->repository->method('findByApiToken')
            ->willReturn($user);

        $result = $this->useCase->execute('token');

        $this->assertNotNull($result);
        $this->assertEquals(42, $result->id()->toInt());
        $this->assertTrue($result->isActive());
        $this->assertTrue($result->hasValidApiToken());
    }

    public function testExecuteChecksCanLoginMethod(): void
    {
        // Inactive user can't login
        $inactiveUser = $this->createUser(id: 1, isActive: false);

        $this->tokenHasher->method('hash')
            ->willReturn('hash');

        $this->repository->method('findByApiToken')
            ->willReturn($inactiveUser);

        $result = $this->useCase->execute('token');

        // canLogin() returns false for inactive users
        $this->assertNull($result);
    }
}
