<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\User\UseCases;

use DateTimeImmutable;
use Lwt\Modules\User\Domain\User;
use Lwt\Modules\User\Application\Services\EmailService;
use Lwt\Modules\User\Application\Services\TokenHasher;
use Lwt\Modules\User\Application\UseCases\RequestPasswordReset;
use Lwt\Modules\User\Domain\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the RequestPasswordReset use case.
 */
class RequestPasswordResetTest extends TestCase
{
    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $repository;

    /** @var TokenHasher&MockObject */
    private TokenHasher $tokenHasher;

    /** @var EmailService&MockObject */
    private EmailService $emailService;

    private RequestPasswordReset $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->tokenHasher = $this->createMock(TokenHasher::class);
        $this->emailService = $this->createMock(EmailService::class);
        $this->useCase = new RequestPasswordReset(
            $this->repository,
            $this->tokenHasher,
            $this->emailService
        );
    }

    private function createUser(int $id, bool $isActive = true): User
    {
        return User::reconstitute(
            id: $id,
            username: 'testuser',
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
            isActive: $isActive,
            role: User::ROLE_USER
        );
    }

    // =========================================================================
    // execute() Tests
    // =========================================================================

    public function testExecuteReturnsTrueForValidEmail(): void
    {
        $user = $this->createUser(1);

        $this->repository->expects($this->once())
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn($user);

        $this->tokenHasher->method('generate')
            ->willReturn('plaintext_token');

        $this->tokenHasher->method('hash')
            ->willReturn('hashed_token');

        $this->repository->method('save');
        $this->emailService->method('sendPasswordResetEmail');

        $result = $this->useCase->execute('test@example.com');

        $this->assertTrue($result);
    }

    public function testExecuteReturnsTrueForNonExistentEmail(): void
    {
        // Silent fail to prevent email enumeration
        $this->repository->expects($this->once())
            ->method('findByEmail')
            ->willReturn(null);

        $result = $this->useCase->execute('nonexistent@example.com');

        $this->assertTrue($result);
    }

    public function testExecuteReturnsTrueForInactiveAccount(): void
    {
        // Don't allow reset for inactive accounts but don't reveal this
        $inactiveUser = $this->createUser(1, false);

        $this->repository->method('findByEmail')
            ->willReturn($inactiveUser);

        $result = $this->useCase->execute('test@example.com');

        $this->assertTrue($result);
    }

    public function testExecuteGeneratesTokenAndSendsEmail(): void
    {
        $user = $this->createUser(1);

        $this->repository->method('findByEmail')
            ->willReturn($user);

        $this->tokenHasher->expects($this->once())
            ->method('generate')
            ->with(32)
            ->willReturn('plaintext_reset_token');

        $this->tokenHasher->expects($this->once())
            ->method('hash')
            ->with('plaintext_reset_token')
            ->willReturn('hashed_reset_token');

        $this->repository->expects($this->once())
            ->method('save')
            ->with($user);

        $this->emailService->expects($this->once())
            ->method('sendPasswordResetEmail')
            ->with(
                'test@example.com',
                'testuser',
                'plaintext_reset_token',
                $this->isInstanceOf(DateTimeImmutable::class)
            );

        $this->useCase->execute('test@example.com');
    }

    public function testExecuteStoresHashedTokenNotPlaintext(): void
    {
        $user = $this->createUser(1);
        $capturedUser = null;

        $this->repository->method('findByEmail')
            ->willReturn($user);

        $this->tokenHasher->method('generate')
            ->willReturn('plaintext');

        $this->tokenHasher->method('hash')
            ->with('plaintext')
            ->willReturn('hashed_for_storage');

        $this->repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (User $u) use (&$capturedUser) {
                $capturedUser = $u;
                return 1;
            });

        $this->emailService->method('sendPasswordResetEmail');

        $this->useCase->execute('test@example.com');

        // Token stored in user should be hashed
        $this->assertEquals('hashed_for_storage', $capturedUser->passwordResetToken());
    }

    public function testExecuteNormalizesEmailInput(): void
    {
        $this->repository->expects($this->once())
            ->method('findByEmail')
            ->with('test@example.com')  // Should be trimmed and lowercased
            ->willReturn(null);

        $this->useCase->execute('  TEST@EXAMPLE.COM  ');
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testExecuteDoesNotSendEmailForNonExistentUser(): void
    {
        $this->repository->method('findByEmail')
            ->willReturn(null);

        $this->emailService->expects($this->never())
            ->method('sendPasswordResetEmail');

        $this->useCase->execute('nonexistent@example.com');
    }

    public function testExecuteDoesNotSendEmailForInactiveUser(): void
    {
        $inactiveUser = $this->createUser(1, false);

        $this->repository->method('findByEmail')
            ->willReturn($inactiveUser);

        $this->emailService->expects($this->never())
            ->method('sendPasswordResetEmail');

        $this->useCase->execute('test@example.com');
    }

    public function testExecuteHandlesEmailServiceException(): void
    {
        $user = $this->createUser(1);

        $this->repository->method('findByEmail')
            ->willReturn($user);

        $this->tokenHasher->method('generate')
            ->willReturn('token');

        $this->tokenHasher->method('hash')
            ->willReturn('hash');

        $this->repository->method('save');

        $this->emailService->method('sendPasswordResetEmail')
            ->willThrowException(new \RuntimeException('Email server down'));

        // Should not throw, should return true (silent fail)
        $result = $this->useCase->execute('test@example.com');

        $this->assertTrue($result);
    }

    public function testExecuteSetsTokenExpiration(): void
    {
        $user = $this->createUser(1);
        $capturedUser = null;

        $this->repository->method('findByEmail')
            ->willReturn($user);

        $this->tokenHasher->method('generate')
            ->willReturn('token');

        $this->tokenHasher->method('hash')
            ->willReturn('hash');

        $this->repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (User $u) use (&$capturedUser) {
                $capturedUser = $u;
                return 1;
            });

        $this->emailService->method('sendPasswordResetEmail');

        $this->useCase->execute('test@example.com');

        // Token should expire in 1 hour
        $expires = $capturedUser->passwordResetTokenExpires();
        $this->assertNotNull($expires);
        $this->assertGreaterThan(new DateTimeImmutable(), $expires);
        $this->assertLessThan(new DateTimeImmutable('+2 hours'), $expires);
    }
}
