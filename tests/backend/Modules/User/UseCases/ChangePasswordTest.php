<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\User\UseCases;

use DateTimeImmutable;
use Lwt\Modules\User\Application\Services\PasswordHasher;
use Lwt\Modules\User\Application\UseCases\ChangePassword;
use Lwt\Modules\User\Domain\User;
use Lwt\Modules\User\Domain\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the ChangePassword use case.
 */
class ChangePasswordTest extends TestCase
{
    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $repository;

    /** @var PasswordHasher&MockObject */
    private PasswordHasher $passwordHasher;

    private ChangePassword $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(PasswordHasher::class);
        $this->useCase = new ChangePassword($this->repository, $this->passwordHasher);
    }

    /**
     * Create a reconstituted user with a known password hash.
     */
    private function createUserWithPassword(string $passwordHash = 'existing_hash'): User
    {
        return User::reconstitute(
            1,
            'testuser',
            'test@example.com',
            $passwordHash,
            null,               // apiToken
            null,               // apiTokenExpires
            null,               // rememberToken
            null,               // rememberTokenExpires
            null,               // passwordResetToken
            null,               // passwordResetTokenExpires
            new DateTimeImmutable('-1 day'), // emailVerifiedAt
            null,               // emailVerificationToken
            null,               // emailVerificationTokenExpires
            null,               // wordPressId
            null,               // googleId
            null,               // microsoftId
            new DateTimeImmutable('-30 days'), // created
            null,               // lastLogin
            true,               // isActive
            'user'              // role
        );
    }

    // =========================================================================
    // execute() Tests
    // =========================================================================

    public function testWrongCurrentPasswordThrows(): void
    {
        $user = $this->createUserWithPassword('existing_hash');

        $this->passwordHasher->expects($this->once())
            ->method('verify')
            ->with('wrong_password', 'existing_hash')
            ->willReturn(false);

        // Should not proceed to validate strength or save
        $this->passwordHasher->expects($this->never())
            ->method('validateStrength');

        $this->repository->expects($this->never())
            ->method('save');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Current password is incorrect');

        $this->useCase->execute($user, 'wrong_password', 'NewPassword123!');
    }

    public function testWeakNewPasswordThrows(): void
    {
        $user = $this->createUserWithPassword('existing_hash');

        $this->passwordHasher->expects($this->once())
            ->method('verify')
            ->with('correct_password', 'existing_hash')
            ->willReturn(true);

        $this->passwordHasher->expects($this->once())
            ->method('validateStrength')
            ->with('weak')
            ->willReturn([
                'valid' => false,
                'errors' => ['Password must be at least 8 characters']
            ]);

        // Should not proceed to hash or save
        $this->passwordHasher->expects($this->never())
            ->method('hash');

        $this->repository->expects($this->never())
            ->method('save');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters');

        $this->useCase->execute($user, 'correct_password', 'weak');
    }

    public function testSuccessfulPasswordChange(): void
    {
        $user = $this->createUserWithPassword('existing_hash');

        $this->passwordHasher->expects($this->once())
            ->method('verify')
            ->with('correct_password', 'existing_hash')
            ->willReturn(true);

        $this->passwordHasher->expects($this->once())
            ->method('validateStrength')
            ->with('NewStrongPass123!')
            ->willReturn(['valid' => true, 'errors' => []]);

        $this->passwordHasher->expects($this->once())
            ->method('hash')
            ->with('NewStrongPass123!')
            ->willReturn('new_hashed_password');

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $savedUser) {
                return $savedUser->passwordHash() === 'new_hashed_password';
            }));

        $this->useCase->execute($user, 'correct_password', 'NewStrongPass123!');

        $this->assertEquals('new_hashed_password', $user->passwordHash());
    }

    public function testCurrentPasswordVerifiedFirst(): void
    {
        $user = $this->createUserWithPassword('existing_hash');

        $this->passwordHasher->expects($this->once())
            ->method('verify')
            ->with('wrong_password', 'existing_hash')
            ->willReturn(false);

        // validateStrength should never be called if current password is wrong
        $this->passwordHasher->expects($this->never())
            ->method('validateStrength');

        $this->passwordHasher->expects($this->never())
            ->method('hash');

        $this->repository->expects($this->never())
            ->method('save');

        $this->expectException(\InvalidArgumentException::class);

        $this->useCase->execute($user, 'wrong_password', 'NewStrongPass123!');
    }
}
