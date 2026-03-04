<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\User\UseCases;

use DateTimeImmutable;
use Lwt\Modules\User\Application\UseCases\UpdateProfile;
use Lwt\Modules\User\Domain\User;
use Lwt\Modules\User\Domain\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the UpdateProfile use case.
 */
class UpdateProfileTest extends TestCase
{
    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $repository;

    private UpdateProfile $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->useCase = new UpdateProfile($this->repository);
    }

    /**
     * Create a reconstituted user with a known ID for update tests.
     */
    private function createPersistedUser(
        int $id = 1,
        string $username = 'olduser',
        string $email = 'old@example.com',
        bool $emailVerified = true
    ): User {
        return User::reconstitute(
            $id,
            $username,
            $email,
            'password_hash',
            null,               // apiToken
            null,               // apiTokenExpires
            null,               // rememberToken
            null,               // rememberTokenExpires
            null,               // passwordResetToken
            null,               // passwordResetTokenExpires
            $emailVerified ? new DateTimeImmutable('-1 day') : null, // emailVerifiedAt
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

    public function testNoChangesStillSaves(): void
    {
        $user = $this->createPersistedUser(1, 'olduser', 'old@example.com');

        $this->repository->expects($this->once())
            ->method('save')
            ->with($user);

        $result = $this->useCase->execute($user, 'olduser', 'old@example.com');

        $this->assertFalse($result);
    }

    public function testUsernameChangeChecksUniqueness(): void
    {
        $user = $this->createPersistedUser(1, 'olduser', 'old@example.com');

        $this->repository->expects($this->once())
            ->method('usernameExists')
            ->with('newuser', 1)
            ->willReturn(false);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $savedUser) {
                return $savedUser->username() === 'newuser';
            }));

        $result = $this->useCase->execute($user, 'newuser', 'old@example.com');

        $this->assertFalse($result);
        $this->assertEquals('newuser', $user->username());
    }

    public function testDuplicateUsernameThrows(): void
    {
        $user = $this->createPersistedUser(1, 'olduser', 'old@example.com');

        $this->repository->expects($this->once())
            ->method('usernameExists')
            ->with('takenuser', 1)
            ->willReturn(true);

        $this->repository->expects($this->never())
            ->method('save');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Username is already taken');

        $this->useCase->execute($user, 'takenuser', 'old@example.com');
    }

    public function testEmailChangeMarksUnverified(): void
    {
        $user = $this->createPersistedUser(1, 'olduser', 'old@example.com', true);
        $this->assertTrue($user->isEmailVerified());

        $this->repository->expects($this->once())
            ->method('emailExists')
            ->with('new@example.com', 1)
            ->willReturn(false);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $savedUser) {
                return $savedUser->email() === 'new@example.com'
                    && !$savedUser->isEmailVerified();
            }));

        $result = $this->useCase->execute($user, 'olduser', 'new@example.com');

        $this->assertTrue($result);
        $this->assertEquals('new@example.com', $user->email());
        $this->assertFalse($user->isEmailVerified());
    }

    public function testDuplicateEmailThrows(): void
    {
        $user = $this->createPersistedUser(1, 'olduser', 'old@example.com');

        $this->repository->expects($this->once())
            ->method('emailExists')
            ->with('taken@example.com', 1)
            ->willReturn(true);

        $this->repository->expects($this->never())
            ->method('save');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email is already registered');

        $this->useCase->execute($user, 'olduser', 'taken@example.com');
    }

    public function testBothChanged(): void
    {
        $user = $this->createPersistedUser(1, 'olduser', 'old@example.com', true);

        $this->repository->expects($this->once())
            ->method('usernameExists')
            ->with('newuser', 1)
            ->willReturn(false);

        $this->repository->expects($this->once())
            ->method('emailExists')
            ->with('new@example.com', 1)
            ->willReturn(false);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $savedUser) {
                return $savedUser->username() === 'newuser'
                    && $savedUser->email() === 'new@example.com'
                    && !$savedUser->isEmailVerified();
            }));

        $result = $this->useCase->execute($user, 'newuser', 'new@example.com');

        $this->assertTrue($result);
        $this->assertEquals('newuser', $user->username());
        $this->assertEquals('new@example.com', $user->email());
    }
}
