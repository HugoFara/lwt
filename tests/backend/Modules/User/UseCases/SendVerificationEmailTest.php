<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\User\UseCases;

use Lwt\Modules\User\Application\Services\EmailService;
use Lwt\Modules\User\Application\Services\TokenHasher;
use Lwt\Modules\User\Application\UseCases\SendVerificationEmail;
use Lwt\Modules\User\Domain\User;
use Lwt\Modules\User\Domain\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the SendVerificationEmail use case.
 */
class SendVerificationEmailTest extends TestCase
{
    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $repository;

    /** @var TokenHasher&MockObject */
    private TokenHasher $tokenHasher;

    /** @var EmailService&MockObject */
    private EmailService $emailService;

    private SendVerificationEmail $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->tokenHasher = $this->createMock(TokenHasher::class);
        $this->emailService = $this->createMock(EmailService::class);
        $this->useCase = new SendVerificationEmail(
            $this->repository,
            $this->tokenHasher,
            $this->emailService
        );
    }

    // =========================================================================
    // execute() Tests
    // =========================================================================

    public function testAlreadyVerifiedUserSkipped(): void
    {
        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('isEmailVerified')
            ->willReturn(true);

        // Should not attempt to save or send email
        $this->repository->expects($this->never())
            ->method('save');

        $this->emailService->expects($this->never())
            ->method('sendVerificationEmail');

        $result = $this->useCase->execute($user);

        $this->assertTrue($result);
    }

    public function testAutoVerifiesWhenEmailDisabled(): void
    {
        $user = User::create('testuser', 'test@example.com', 'hashed_password');

        $this->emailService->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $savedUser) {
                return $savedUser->isEmailVerified();
            }));

        // Should not attempt to generate tokens or send email
        $this->tokenHasher->expects($this->never())
            ->method('generate');

        $this->emailService->expects($this->never())
            ->method('sendVerificationEmail');

        $result = $this->useCase->execute($user);

        $this->assertTrue($result);
        $this->assertTrue($user->isEmailVerified());
    }

    public function testSendsEmailWhenEnabled(): void
    {
        $user = User::create('testuser', 'test@example.com', 'hashed_password');

        $this->emailService->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->tokenHasher->expects($this->once())
            ->method('generate')
            ->with(32)
            ->willReturn('plaintext_token_abc');

        $this->tokenHasher->expects($this->once())
            ->method('hash')
            ->with('plaintext_token_abc')
            ->willReturn('hashed_token_xyz');

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(User::class));

        $this->emailService->expects($this->once())
            ->method('sendVerificationEmail')
            ->with(
                'test@example.com',
                'testuser',
                'plaintext_token_abc',
                $this->isInstanceOf(\DateTimeImmutable::class)
            )
            ->willReturn(true);

        $result = $this->useCase->execute($user);

        $this->assertTrue($result);
    }

    public function testEmailFailureDoesNotPreventSuccess(): void
    {
        $user = User::create('testuser', 'test@example.com', 'hashed_password');

        $this->emailService->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->tokenHasher->method('generate')
            ->willReturn('plaintext_token');

        $this->tokenHasher->method('hash')
            ->willReturn('hashed_token');

        $this->repository->expects($this->once())
            ->method('save');

        $this->emailService->expects($this->once())
            ->method('sendVerificationEmail')
            ->willThrowException(new \RuntimeException('SMTP connection failed'));

        $result = $this->useCase->execute($user);

        $this->assertTrue($result);
    }

    public function testTokenIsHashedBeforeStorage(): void
    {
        $user = User::create('testuser', 'test@example.com', 'hashed_password');

        $this->emailService->method('isEnabled')
            ->willReturn(true);

        $this->tokenHasher->expects($this->once())
            ->method('generate')
            ->with(32)
            ->willReturn('raw_plaintext_token');

        $this->tokenHasher->expects($this->once())
            ->method('hash')
            ->with('raw_plaintext_token')
            ->willReturn('sha256_hashed_result');

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $savedUser) {
                // The hashed token should be stored on the user entity
                return $savedUser->emailVerificationToken() === 'sha256_hashed_result';
            }));

        $this->emailService->method('sendVerificationEmail')
            ->willReturn(true);

        $this->useCase->execute($user);
    }
}
