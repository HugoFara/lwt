<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\User\UseCases;

use Lwt\Modules\User\Application\Services\PasswordHasher;
use Lwt\Modules\User\Application\Services\RecoveryCodeService;
use Lwt\Modules\User\Application\UseCases\ResetPasswordWithRecoveryCode;
use Lwt\Modules\User\Domain\User;
use Lwt\Modules\User\Domain\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the recovery-code password reset use case.
 */
class ResetPasswordWithRecoveryCodeTest extends TestCase
{
    private RecoveryCodeService $codes;
    /** @var array{code: string, hash: string} */
    private array $generated;

    protected function setUp(): void
    {
        $this->codes = new RecoveryCodeService();
        $this->generated = $this->codes->generate();
    }

    /** Build a mock user whose stored recovery hash matches $this->generated. */
    private function userWithHash(?string $hash): User
    {
        $user = $this->createMock(User::class);
        $user->method('recoveryCodeHash')->willReturn($hash);
        return $user;
    }

    private function useCase(UserRepositoryInterface $repo): ResetPasswordWithRecoveryCode
    {
        return new ResetPasswordWithRecoveryCode($repo, $this->codes, new PasswordHasher());
    }

    public function testResetsPasswordAndRotatesCodeOnValidInput(): void
    {
        $user = $this->userWithHash($this->generated['hash']);
        $user->expects($this->once())->method('changePassword');
        $user->expects($this->once())->method('invalidateRememberToken');
        $user->expects($this->once())->method('invalidateApiToken');
        $user->expects($this->once())->method('setRecoveryCodeHash');

        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findByUsername')->with('alice')->willReturn($user);
        $repo->expects($this->once())->method('save');

        $newCode = $this->useCase($repo)->execute('alice', $this->generated['code'], 'NewPass123!');

        $this->assertNotSame('', $newCode);
        // A fresh code is issued (single use).
        $this->assertNotSame($this->generated['code'], $newCode);
    }

    public function testRejectsWrongCode(): void
    {
        $user = $this->userWithHash($this->generated['hash']);
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findByUsername')->willReturn($user);
        $repo->expects($this->never())->method('save');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(ResetPasswordWithRecoveryCode::INVALID_MESSAGE);

        $this->useCase($repo)->execute('alice', 'WRONG-00000-00000-00000', 'NewPass123!');
    }

    public function testRejectsUnknownUser(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findByUsername')->willReturn(null);
        $repo->expects($this->never())->method('save');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(ResetPasswordWithRecoveryCode::INVALID_MESSAGE);

        $this->useCase($repo)->execute('ghost', $this->generated['code'], 'NewPass123!');
    }

    public function testRejectsAccountWithNoRecoveryCode(): void
    {
        $user = $this->userWithHash(null);
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findByUsername')->willReturn($user);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(ResetPasswordWithRecoveryCode::INVALID_MESSAGE);

        $this->useCase($repo)->execute('alice', $this->generated['code'], 'NewPass123!');
    }

    public function testRejectsWeakPasswordEvenWithValidCode(): void
    {
        $user = $this->userWithHash($this->generated['hash']);
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findByUsername')->willReturn($user);
        $repo->expects($this->never())->method('save');

        // Reaches password validation (code is valid), then fails on strength.
        $this->expectException(\InvalidArgumentException::class);

        $this->useCase($repo)->execute('alice', $this->generated['code'], 'weak');
    }
}
