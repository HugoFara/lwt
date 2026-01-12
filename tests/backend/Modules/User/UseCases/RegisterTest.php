<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\User\UseCases;

use Lwt\Core\Entity\User;
use Lwt\Modules\User\Application\Services\PasswordHasher;
use Lwt\Modules\User\Application\UseCases\Register;
use Lwt\Modules\User\Domain\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Register use case.
 */
class RegisterTest extends TestCase
{
    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $repository;

    /** @var PasswordHasher&MockObject */
    private PasswordHasher $passwordHasher;

    private Register $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(PasswordHasher::class);
        $this->useCase = new Register($this->repository, $this->passwordHasher);
    }

    // =========================================================================
    // execute() Tests
    // =========================================================================

    public function testExecuteCreatesUserSuccessfully(): void
    {
        $this->passwordHasher->expects($this->once())
            ->method('validateStrength')
            ->with('ValidPass123!')
            ->willReturn(['valid' => true, 'errors' => []]);

        $this->repository->expects($this->once())
            ->method('findByUsername')
            ->with('testuser')
            ->willReturn(null);

        $this->repository->expects($this->once())
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn(null);

        $this->passwordHasher->expects($this->once())
            ->method('hash')
            ->with('ValidPass123!')
            ->willReturn('hashed_password');

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(User::class));

        $result = $this->useCase->execute('testuser', 'test@example.com', 'ValidPass123!');

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('testuser', $result->username());
        $this->assertEquals('test@example.com', $result->email());
    }

    public function testExecuteThrowsExceptionForWeakPassword(): void
    {
        $this->passwordHasher->expects($this->once())
            ->method('validateStrength')
            ->with('weak')
            ->willReturn([
                'valid' => false,
                'errors' => ['Password must be at least 8 characters', 'Password must contain a number']
            ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters. Password must contain a number');

        $this->useCase->execute('testuser', 'test@example.com', 'weak');
    }

    public function testExecuteThrowsExceptionForDuplicateUsername(): void
    {
        $this->passwordHasher->method('validateStrength')
            ->willReturn(['valid' => true, 'errors' => []]);

        $existingUser = $this->createMock(User::class);
        $this->repository->expects($this->once())
            ->method('findByUsername')
            ->with('existinguser')
            ->willReturn($existingUser);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Username is already taken');

        $this->useCase->execute('existinguser', 'test@example.com', 'ValidPass123!');
    }

    public function testExecuteThrowsExceptionForDuplicateEmail(): void
    {
        $this->passwordHasher->method('validateStrength')
            ->willReturn(['valid' => true, 'errors' => []]);

        $this->repository->method('findByUsername')
            ->willReturn(null);

        $existingUser = $this->createMock(User::class);
        $this->repository->expects($this->once())
            ->method('findByEmail')
            ->with('existing@example.com')
            ->willReturn($existingUser);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email is already registered');

        $this->useCase->execute('newuser', 'existing@example.com', 'ValidPass123!');
    }

    public function testExecuteChecksPasswordStrengthFirst(): void
    {
        $this->passwordHasher->expects($this->once())
            ->method('validateStrength')
            ->willReturn(['valid' => false, 'errors' => ['Too weak']]);

        // Repository should not be called if password validation fails
        $this->repository->expects($this->never())
            ->method('findByUsername');

        $this->expectException(\InvalidArgumentException::class);

        $this->useCase->execute('testuser', 'test@example.com', 'weak');
    }

    public function testExecuteHashesPasswordBeforeSaving(): void
    {
        $this->passwordHasher->method('validateStrength')
            ->willReturn(['valid' => true, 'errors' => []]);

        $this->repository->method('findByUsername')
            ->willReturn(null);

        $this->repository->method('findByEmail')
            ->willReturn(null);

        $this->passwordHasher->expects($this->once())
            ->method('hash')
            ->with('PlainPassword123!')
            ->willReturn('securely_hashed');

        $this->repository->expects($this->once())
            ->method('save');

        $result = $this->useCase->execute('testuser', 'test@example.com', 'PlainPassword123!');

        // The user entity should have the hashed password
        $this->assertEquals('securely_hashed', $result->passwordHash());
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testExecuteNormalizesEmail(): void
    {
        $this->passwordHasher->method('validateStrength')
            ->willReturn(['valid' => true, 'errors' => []]);

        $this->repository->method('findByUsername')
            ->willReturn(null);

        $this->repository->method('findByEmail')
            ->willReturn(null);

        $this->passwordHasher->method('hash')
            ->willReturn('hashed');

        $this->repository->method('save');

        $result = $this->useCase->execute('testuser', 'TEST@EXAMPLE.COM', 'ValidPass123!');

        // Email should be lowercased by User::create()
        $this->assertEquals('test@example.com', $result->email());
    }

    public function testExecuteTrimsUsername(): void
    {
        $this->passwordHasher->method('validateStrength')
            ->willReturn(['valid' => true, 'errors' => []]);

        $this->repository->method('findByUsername')
            ->willReturn(null);

        $this->repository->method('findByEmail')
            ->willReturn(null);

        $this->passwordHasher->method('hash')
            ->willReturn('hashed');

        $this->repository->method('save');

        $result = $this->useCase->execute('  testuser  ', 'test@example.com', 'ValidPass123!');

        // Username should be trimmed by User::create()
        $this->assertEquals('testuser', $result->username());
    }

    public function testExecuteReturnsNewUser(): void
    {
        $this->passwordHasher->method('validateStrength')
            ->willReturn(['valid' => true, 'errors' => []]);

        $this->repository->method('findByUsername')
            ->willReturn(null);

        $this->repository->method('findByEmail')
            ->willReturn(null);

        $this->passwordHasher->method('hash')
            ->willReturn('hashed');

        $this->repository->method('save');

        $result = $this->useCase->execute('newuser', 'new@example.com', 'ValidPass123!');

        // New users should have isNew() ID and be active
        $this->assertTrue($result->id()->isNew());
        $this->assertTrue($result->isActive());
    }
}
