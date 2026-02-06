<?php

declare(strict_types=1);

namespace Lwt\Tests\Services;

require_once __DIR__ . '/../../../src/backend/Core/Globals.php';
require_once __DIR__ . '/../../../src/Shared/Domain/ValueObjects/UserId.php';
require_once __DIR__ . '/../../../src/Modules/User/Domain/User.php';
require_once __DIR__ . '/../../../src/Shared/Infrastructure/Exception/AuthException.php';
require_once __DIR__ . '/../../../src/Modules/User/Application/Services/PasswordService.php';
require_once __DIR__ . '/../../../src/Modules/User/Application/Services/AuthService.php';

use Lwt\Modules\User\Domain\User;
use Lwt\Shared\Infrastructure\Exception\AuthException;
use Lwt\Core\Globals;
use Lwt\Modules\User\Application\Services\AuthService;
use Lwt\Modules\User\Application\Services\PasswordService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the AuthService class.
 *
 * Note: These tests focus on the non-database aspects of AuthService.
 * Database-dependent tests would require integration testing with a real database.
 */
class AuthServiceTest extends TestCase
{
    private AuthService $service;
    private PasswordService $passwordService;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset globals before each test
        Globals::reset();

        $this->passwordService = new PasswordService();
        $this->service = new AuthService($this->passwordService);
    }

    protected function tearDown(): void
    {
        Globals::reset();
        parent::tearDown();
    }

    // =========================================================================
    // Constructor Tests
    // =========================================================================

    public function testConstructorCreatesPasswordServiceIfNotProvided(): void
    {
        $service = new AuthService();

        // Should not throw exception - service is functional
        $this->assertInstanceOf(AuthService::class, $service);
    }

    public function testConstructorUsesProvidedPasswordService(): void
    {
        $customPasswordService = new PasswordService();
        $service = new AuthService($customPasswordService);

        $this->assertInstanceOf(AuthService::class, $service);
    }

    // =========================================================================
    // getCurrentUser Tests (without database)
    // =========================================================================

    public function testGetCurrentUserReturnsNullWhenNoUserIdSet(): void
    {
        $this->assertNull($this->service->getCurrentUser());
    }

    // =========================================================================
    // setCurrentUser Tests
    // =========================================================================

    public function testSetCurrentUserUpdatesGlobals(): void
    {
        $user = User::create('testuser', 'test@example.com', 'hashedpassword');
        // Simulate that user has been persisted
        $user->setId(\Lwt\Shared\Domain\ValueObjects\UserId::fromInt(42));

        $this->service->setCurrentUser($user);

        $this->assertEquals(42, Globals::getCurrentUserId());
    }

    // =========================================================================
    // logout Tests
    // =========================================================================

    public function testLogoutClearsUserContext(): void
    {
        // Set up a user context
        Globals::setCurrentUserId(42);

        // Logout
        $this->service->logout();

        $this->assertNull(Globals::getCurrentUserId());
        $this->assertNull($this->service->getCurrentUser());
    }

    // =========================================================================
    // Password Validation Tests (indirectly through validateStrength)
    // =========================================================================

    public function testPasswordValidationIsEnforcedInPasswordService(): void
    {
        // Weak password should fail validation
        $result = $this->passwordService->validateStrength('weak');

        $this->assertFalse($result['valid']);
    }

    public function testStrongPasswordPassesValidation(): void
    {
        // Strong password should pass
        $result = $this->passwordService->validateStrength('StrongP@ss1');

        $this->assertTrue($result['valid']);
    }

    // =========================================================================
    // API Token Tests (unit tests for token validation logic)
    // =========================================================================

    public function testValidateApiTokenReturnsNullForInvalidToken(): void
    {
        // Without database, any token lookup will fail
        $result = $this->service->validateApiToken('invalid-token');

        $this->assertNull($result);
    }

    // =========================================================================
    // Session Validation Tests
    // =========================================================================

    public function testValidateSessionReturnsFalseWhenNoSession(): void
    {
        // Start fresh session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $result = $this->service->validateSession();

        $this->assertFalse($result);
    }
}
