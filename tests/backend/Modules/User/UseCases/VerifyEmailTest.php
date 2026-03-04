<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\User\UseCases;

use DateTimeImmutable;
use Lwt\Modules\User\Application\Services\TokenHasher;
use Lwt\Modules\User\Application\UseCases\VerifyEmail;
use Lwt\Modules\User\Domain\User;
use Lwt\Modules\User\Domain\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the VerifyEmail use case.
 */
class VerifyEmailTest extends TestCase
{
    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $repository;

    /** @var TokenHasher&MockObject */
    private TokenHasher $tokenHasher;

    private VerifyEmail $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->tokenHasher = $this->createMock(TokenHasher::class);
        $this->useCase = new VerifyEmail($this->repository, $this->tokenHasher);
    }

    // =========================================================================
    // execute() Tests
    // =========================================================================

    public function testEmptyTokenReturnsNull(): void
    {
        // Should not attempt any lookup
        $this->tokenHasher->expects($this->never())
            ->method('hash');

        $this->repository->expects($this->never())
            ->method('findByEmailVerificationToken');

        $result = $this->useCase->execute('');

        $this->assertNull($result);
    }

    public function testInvalidTokenReturnsNull(): void
    {
        $this->tokenHasher->expects($this->once())
            ->method('hash')
            ->with('invalid_token')
            ->willReturn('hashed_invalid');

        $this->repository->expects($this->once())
            ->method('findByEmailVerificationToken')
            ->with('hashed_invalid')
            ->willReturn(null);

        $result = $this->useCase->execute('invalid_token');

        $this->assertNull($result);
    }

    public function testExpiredTokenReturnsNull(): void
    {
        $this->tokenHasher->expects($this->once())
            ->method('hash')
            ->with('expired_token')
            ->willReturn('hashed_expired');

        // Reconstitute a user with an expired verification token
        $expiredTime = new DateTimeImmutable('-1 hour');
        $user = User::reconstitute(
            1,
            'testuser',
            'test@example.com',
            'password_hash',
            null,               // apiToken
            null,               // apiTokenExpires
            null,               // rememberToken
            null,               // rememberTokenExpires
            null,               // passwordResetToken
            null,               // passwordResetTokenExpires
            null,               // emailVerifiedAt
            'hashed_expired',   // emailVerificationToken
            $expiredTime,       // emailVerificationTokenExpires (expired)
            null,               // wordPressId
            null,               // googleId
            null,               // microsoftId
            new DateTimeImmutable('-30 days'), // created
            null,               // lastLogin
            true,               // isActive
            'user'              // role
        );

        $this->repository->expects($this->once())
            ->method('findByEmailVerificationToken')
            ->with('hashed_expired')
            ->willReturn($user);

        // Should not save since token is expired
        $this->repository->expects($this->never())
            ->method('save');

        $result = $this->useCase->execute('expired_token');

        $this->assertNull($result);
    }

    public function testValidTokenVerifiesUser(): void
    {
        $this->tokenHasher->expects($this->once())
            ->method('hash')
            ->with('valid_token')
            ->willReturn('hashed_valid');

        // Reconstitute a user with a valid (future) verification token
        $futureTime = new DateTimeImmutable('+23 hours');
        $user = User::reconstitute(
            42,
            'verifyuser',
            'verify@example.com',
            'password_hash',
            null,
            null,
            null,
            null,
            null,
            null,
            null,               // emailVerifiedAt (not yet verified)
            'hashed_valid',     // emailVerificationToken
            $futureTime,        // emailVerificationTokenExpires (still valid)
            null,
            null,
            null,
            new DateTimeImmutable('-7 days'),
            null,
            true,
            'user'
        );

        $this->repository->expects($this->once())
            ->method('findByEmailVerificationToken')
            ->with('hashed_valid')
            ->willReturn($user);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $savedUser) {
                return $savedUser->isEmailVerified();
            }));

        $result = $this->useCase->execute('valid_token');

        $this->assertNotNull($result);
        $this->assertInstanceOf(User::class, $result);
        $this->assertTrue($result->isEmailVerified());
        $this->assertEquals('verifyuser', $result->username());
    }

    public function testTokenIsHashedBeforeLookup(): void
    {
        $this->tokenHasher->expects($this->once())
            ->method('hash')
            ->with('my_plaintext_token')
            ->willReturn('sha256_of_plaintext');

        $this->repository->expects($this->once())
            ->method('findByEmailVerificationToken')
            ->with('sha256_of_plaintext')
            ->willReturn(null);

        $this->useCase->execute('my_plaintext_token');
    }
}
