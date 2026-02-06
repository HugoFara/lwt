<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\User\UseCases;

use DateTimeImmutable;
use Lwt\Modules\User\Domain\User;
use Lwt\Modules\User\Application\Services\TokenHasher;
use Lwt\Modules\User\Application\UseCases\GenerateApiToken;
use Lwt\Modules\User\Domain\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the GenerateApiToken use case.
 */
class GenerateApiTokenTest extends TestCase
{
    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $repository;

    /** @var TokenHasher&MockObject */
    private TokenHasher $tokenHasher;

    private GenerateApiToken $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->tokenHasher = $this->createMock(TokenHasher::class);
        $this->useCase = new GenerateApiToken($this->repository, $this->tokenHasher);
    }

    private function createUser(int $id, string $username = 'testuser'): User
    {
        return User::reconstitute(
            id: $id,
            username: $username,
            email: 'test@example.com',
            passwordHash: 'hashed_password',
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
            isActive: true,
            role: User::ROLE_USER
        );
    }

    // =========================================================================
    // execute() Tests
    // =========================================================================

    public function testExecuteGeneratesTokenSuccessfully(): void
    {
        $user = $this->createUser(1);

        $this->repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $this->tokenHasher->expects($this->once())
            ->method('generate')
            ->with(32)
            ->willReturn('plaintext_token_abc123');

        $this->tokenHasher->expects($this->once())
            ->method('hash')
            ->with('plaintext_token_abc123')
            ->willReturn('hashed_token_xyz');

        $this->repository->expects($this->once())
            ->method('save')
            ->with($user);

        $result = $this->useCase->execute(1);

        $this->assertEquals('plaintext_token_abc123', $result);
    }

    public function testExecuteThrowsExceptionForNonExistentUser(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User not found');

        $this->useCase->execute(999);
    }

    public function testExecuteStoresHashedTokenNotPlaintext(): void
    {
        $user = $this->createUser(1);
        $capturedUser = null;

        $this->repository->method('find')
            ->willReturn($user);

        $this->tokenHasher->method('generate')
            ->willReturn('plaintext_token');

        $this->tokenHasher->method('hash')
            ->with('plaintext_token')
            ->willReturn('hashed_token');

        $this->repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (User $u) use (&$capturedUser) {
                $capturedUser = $u;
                return 1;
            });

        $this->useCase->execute(1);

        // The user should have the hashed token, not plaintext
        $this->assertEquals('hashed_token', $capturedUser->apiToken());
    }

    public function testExecuteReturnsPlaintextToken(): void
    {
        $user = $this->createUser(1);

        $this->repository->method('find')
            ->willReturn($user);

        $this->tokenHasher->method('generate')
            ->willReturn('the_plaintext_token');

        $this->tokenHasher->method('hash')
            ->willReturn('some_hash');

        $this->repository->method('save');

        $result = $this->useCase->execute(1);

        // Should return plaintext, not hash
        $this->assertEquals('the_plaintext_token', $result);
    }

    public function testExecuteSetsTokenExpiration(): void
    {
        $user = $this->createUser(1);
        $capturedUser = null;

        $this->repository->method('find')
            ->willReturn($user);

        $this->tokenHasher->method('generate')
            ->willReturn('token');

        $this->tokenHasher->method('hash')
            ->willReturn('hashed');

        $this->repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (User $u) use (&$capturedUser) {
                $capturedUser = $u;
                return 1;
            });

        $this->useCase->execute(1);

        // Token should have an expiration date set (30 days in future)
        $this->assertNotNull($capturedUser->apiTokenExpires());
        $this->assertGreaterThan(new DateTimeImmutable(), $capturedUser->apiTokenExpires());
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testExecuteDoesNotCallHasherIfUserNotFound(): void
    {
        $this->repository->method('find')
            ->willReturn(null);

        $this->tokenHasher->expects($this->never())
            ->method('generate');

        $this->tokenHasher->expects($this->never())
            ->method('hash');

        $this->expectException(\InvalidArgumentException::class);

        $this->useCase->execute(999);
    }

    public function testExecuteGenerates32ByteToken(): void
    {
        $user = $this->createUser(1);

        $this->repository->method('find')
            ->willReturn($user);

        $this->tokenHasher->expects($this->once())
            ->method('generate')
            ->with(32)  // Should request 32 bytes
            ->willReturn('64_char_hex_string');

        $this->tokenHasher->method('hash')
            ->willReturn('hash');

        $this->repository->method('save');

        $this->useCase->execute(1);
    }

    public function testExecuteSavesUserAfterSettingToken(): void
    {
        $user = $this->createUser(1);

        $this->repository->method('find')
            ->willReturn($user);

        $this->tokenHasher->method('generate')
            ->willReturn('token');

        $this->tokenHasher->method('hash')
            ->willReturn('hash');

        // Verify save is called exactly once
        $this->repository->expects($this->once())
            ->method('save')
            ->with($user);

        $this->useCase->execute(1);
    }
}
