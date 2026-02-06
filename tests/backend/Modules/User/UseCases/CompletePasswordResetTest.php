<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\User\UseCases;

use DateTimeImmutable;
use Lwt\Modules\User\Domain\User;
use Lwt\Modules\User\Application\Services\PasswordHasher;
use Lwt\Modules\User\Application\Services\TokenHasher;
use Lwt\Modules\User\Application\UseCases\CompletePasswordReset;
use Lwt\Modules\User\Domain\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the CompletePasswordReset use case.
 */
class CompletePasswordResetTest extends TestCase
{
    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $repository;

    /** @var TokenHasher&MockObject */
    private TokenHasher $tokenHasher;

    /** @var PasswordHasher&MockObject */
    private PasswordHasher $passwordHasher;

    private CompletePasswordReset $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->tokenHasher = $this->createMock(TokenHasher::class);
        $this->passwordHasher = $this->createMock(PasswordHasher::class);
        $this->useCase = new CompletePasswordReset(
            $this->repository,
            $this->tokenHasher,
            $this->passwordHasher
        );
    }

    private function createUser(
        int $id,
        ?string $resetToken = 'hashed_reset_token',
        ?DateTimeImmutable $resetTokenExpires = null
    ): User {
        return User::reconstitute(
            id: $id,
            username: 'testuser',
            email: 'test@example.com',
            passwordHash: 'old_password_hash',
            apiToken: null,
            apiTokenExpires: null,
            rememberToken: null,
            rememberTokenExpires: null,
            passwordResetToken: $resetToken,
            passwordResetTokenExpires: $resetTokenExpires ?? new DateTimeImmutable('+1 hour'),
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

    public function testExecuteResetsPasswordSuccessfully(): void
    {
        $user = $this->createUser(1);

        $this->tokenHasher->expects($this->once())
            ->method('hash')
            ->with('valid_token')
            ->willReturn('hashed_reset_token');

        $this->repository->expects($this->once())
            ->method('findByPasswordResetToken')
            ->with('hashed_reset_token')
            ->willReturn($user);

        $this->passwordHasher->expects($this->once())
            ->method('validateStrength')
            ->with('NewPassword123!')
            ->willReturn(['valid' => true, 'errors' => []]);

        $this->passwordHasher->expects($this->once())
            ->method('hash')
            ->with('NewPassword123!')
            ->willReturn('new_password_hash');

        $this->repository->expects($this->once())
            ->method('save')
            ->with($user);

        $result = $this->useCase->execute('valid_token', 'NewPassword123!');

        $this->assertTrue($result);
    }

    public function testExecuteReturnsFalseForEmptyToken(): void
    {
        $result = $this->useCase->execute('', 'NewPassword123!');

        $this->assertFalse($result);
    }

    public function testExecuteReturnsFalseForInvalidToken(): void
    {
        $this->tokenHasher->method('hash')
            ->willReturn('some_hash');

        $this->repository->expects($this->once())
            ->method('findByPasswordResetToken')
            ->willReturn(null);

        $result = $this->useCase->execute('invalid_token', 'NewPassword123!');

        $this->assertFalse($result);
    }

    public function testExecuteReturnsFalseForExpiredToken(): void
    {
        $user = $this->createUser(
            id: 1,
            resetToken: 'hashed_token',
            resetTokenExpires: new DateTimeImmutable('-1 hour')  // Expired
        );

        $this->tokenHasher->method('hash')
            ->willReturn('hashed_token');

        $this->repository->method('findByPasswordResetToken')
            ->willReturn($user);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($user);  // Should save to clear expired token

        $result = $this->useCase->execute('token', 'NewPassword123!');

        $this->assertFalse($result);
    }

    public function testExecuteThrowsExceptionForWeakPassword(): void
    {
        $user = $this->createUser(1);

        $this->tokenHasher->method('hash')
            ->willReturn('hashed_reset_token');

        $this->repository->method('findByPasswordResetToken')
            ->willReturn($user);

        $this->passwordHasher->expects($this->once())
            ->method('validateStrength')
            ->with('weak')
            ->willReturn([
                'valid' => false,
                'errors' => ['Password too short']
            ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password too short');

        $this->useCase->execute('valid_token', 'weak');
    }

    public function testExecuteInvalidatesTokenAfterUse(): void
    {
        $user = $this->createUser(1);
        $capturedUser = null;

        $this->tokenHasher->method('hash')
            ->willReturn('hashed_reset_token');

        $this->repository->method('findByPasswordResetToken')
            ->willReturn($user);

        $this->passwordHasher->method('validateStrength')
            ->willReturn(['valid' => true, 'errors' => []]);

        $this->passwordHasher->method('hash')
            ->willReturn('new_hash');

        $this->repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (User $u) use (&$capturedUser) {
                $capturedUser = $u;
                return 1;
            });

        $this->useCase->execute('token', 'NewPassword123!');

        // Token should be invalidated after successful reset
        $this->assertNull($capturedUser->passwordResetToken());
        $this->assertNull($capturedUser->passwordResetTokenExpires());
    }

    public function testExecuteUpdatesPasswordHash(): void
    {
        $user = $this->createUser(1);
        $capturedUser = null;

        $this->tokenHasher->method('hash')
            ->willReturn('hashed_reset_token');

        $this->repository->method('findByPasswordResetToken')
            ->willReturn($user);

        $this->passwordHasher->method('validateStrength')
            ->willReturn(['valid' => true, 'errors' => []]);

        $this->passwordHasher->method('hash')
            ->willReturn('brand_new_password_hash');

        $this->repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (User $u) use (&$capturedUser) {
                $capturedUser = $u;
                return 1;
            });

        $this->useCase->execute('token', 'NewPassword123!');

        $this->assertEquals('brand_new_password_hash', $capturedUser->passwordHash());
    }

    // =========================================================================
    // validateToken() Tests
    // =========================================================================

    public function testValidateTokenReturnsTrueForValidToken(): void
    {
        $user = $this->createUser(1);

        $this->tokenHasher->method('hash')
            ->willReturn('hashed_reset_token');

        $this->repository->method('findByPasswordResetToken')
            ->willReturn($user);

        $result = $this->useCase->validateToken('valid_token');

        $this->assertTrue($result);
    }

    public function testValidateTokenReturnsFalseForEmptyToken(): void
    {
        $result = $this->useCase->validateToken('');

        $this->assertFalse($result);
    }

    public function testValidateTokenReturnsFalseForInvalidToken(): void
    {
        $this->tokenHasher->method('hash')
            ->willReturn('hash');

        $this->repository->method('findByPasswordResetToken')
            ->willReturn(null);

        $result = $this->useCase->validateToken('invalid');

        $this->assertFalse($result);
    }

    public function testValidateTokenReturnsFalseForExpiredToken(): void
    {
        $user = $this->createUser(
            id: 1,
            resetToken: 'hash',
            resetTokenExpires: new DateTimeImmutable('-1 hour')
        );

        $this->tokenHasher->method('hash')
            ->willReturn('hash');

        $this->repository->method('findByPasswordResetToken')
            ->willReturn($user);

        $result = $this->useCase->validateToken('token');

        $this->assertFalse($result);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testExecuteHashesTokenBeforeLookup(): void
    {
        $this->tokenHasher->expects($this->once())
            ->method('hash')
            ->with('plaintext_token')
            ->willReturn('hashed_for_lookup');

        $this->repository->expects($this->once())
            ->method('findByPasswordResetToken')
            ->with('hashed_for_lookup')
            ->willReturn(null);

        $this->useCase->execute('plaintext_token', 'password');
    }

    public function testExecuteClearsExpiredTokenOnFailure(): void
    {
        $user = $this->createUser(
            id: 1,
            resetToken: 'expired_hash',
            resetTokenExpires: new DateTimeImmutable('-1 day')
        );
        $capturedUser = null;

        $this->tokenHasher->method('hash')
            ->willReturn('expired_hash');

        $this->repository->method('findByPasswordResetToken')
            ->willReturn($user);

        $this->repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (User $u) use (&$capturedUser) {
                $capturedUser = $u;
                return 1;
            });

        $this->useCase->execute('token', 'password');

        // Expired token should be cleared
        $this->assertNull($capturedUser->passwordResetToken());
    }

    public function testExecuteDoesNotCallPasswordHasherForInvalidToken(): void
    {
        $this->tokenHasher->method('hash')
            ->willReturn('hash');

        $this->repository->method('findByPasswordResetToken')
            ->willReturn(null);

        $this->passwordHasher->expects($this->never())
            ->method('validateStrength');

        $this->passwordHasher->expects($this->never())
            ->method('hash');

        $this->useCase->execute('invalid', 'password');
    }
}
