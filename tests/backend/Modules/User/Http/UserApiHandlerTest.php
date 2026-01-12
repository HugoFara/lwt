<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\User\Http;

use Lwt\Core\Entity\User;
use Lwt\Core\Exception\AuthException;
use Lwt\Modules\User\Http\UserApiHandler;
use Lwt\Modules\User\Application\UserFacade;
use Lwt\Shared\Domain\ValueObjects\UserId;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for UserApiHandler.
 *
 * Tests user authentication API operations including login, register, logout, and token management.
 */
class UserApiHandlerTest extends TestCase
{
    /** @var UserFacade&MockObject */
    private UserFacade $facade;

    private UserApiHandler $handler;

    protected function setUp(): void
    {
        $this->facade = $this->createMock(UserFacade::class);
        $this->handler = new UserApiHandler($this->facade);
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    public function testConstructorCreatesValidHandler(): void
    {
        $this->assertInstanceOf(UserApiHandler::class, $this->handler);
    }

    public function testConstructorAcceptsNullParameter(): void
    {
        $handler = new UserApiHandler(null);
        $this->assertInstanceOf(UserApiHandler::class, $handler);
    }

    public function testGetUserFacadeReturnsFacade(): void
    {
        $result = $this->handler->getUserFacade();
        $this->assertSame($this->facade, $result);
    }

    // =========================================================================
    // formatLogin tests
    // =========================================================================

    public function testFormatLoginReturnsErrorForMissingUsername(): void
    {
        $result = $this->handler->formatLogin(['password' => 'secret']);

        $this->assertFalse($result['success']);
        $this->assertEquals('Username/email and password are required', $result['error']);
    }

    public function testFormatLoginReturnsErrorForMissingPassword(): void
    {
        $result = $this->handler->formatLogin(['username' => 'testuser']);

        $this->assertFalse($result['success']);
        $this->assertEquals('Username/email and password are required', $result['error']);
    }

    public function testFormatLoginReturnsErrorForEmptyCredentials(): void
    {
        $result = $this->handler->formatLogin(['username' => '', 'password' => '']);

        $this->assertFalse($result['success']);
        $this->assertEquals('Username/email and password are required', $result['error']);
    }

    public function testFormatLoginAcceptsEmailParameter(): void
    {
        $this->facade->method('login')
            ->willThrowException(new AuthException('Invalid credentials'));

        $result = $this->handler->formatLogin(['email' => 'test@example.com', 'password' => 'secret']);

        // Should reach login, not return missing credential error
        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid credentials', $result['error']);
    }

    public function testFormatLoginReturnsErrorOnAuthException(): void
    {
        $this->facade->method('login')
            ->willThrowException(new AuthException('Invalid credentials'));

        $result = $this->handler->formatLogin(['username' => 'testuser', 'password' => 'wrongpass']);

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid credentials', $result['error']);
    }

    public function testFormatLoginReturnsSuccessWithToken(): void
    {
        $user = $this->createMockUser(1, 'testuser', 'test@example.com');

        $this->facade->method('login')
            ->with('testuser', 'password123')
            ->willReturn($user);

        $this->facade->method('generateApiToken')
            ->with(1)
            ->willReturn('test-api-token');

        $result = $this->handler->formatLogin(['username' => 'testuser', 'password' => 'password123']);

        $this->assertTrue($result['success']);
        $this->assertEquals('test-api-token', $result['token']);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals(1, $result['user']['id']);
        $this->assertEquals('testuser', $result['user']['username']);
    }

    public function testFormatLoginCallsFacadeWithCorrectParams(): void
    {
        $user = $this->createMockUser(1, 'myuser', 'my@email.com');

        $this->facade->expects($this->once())
            ->method('login')
            ->with('myuser', 'mypassword')
            ->willReturn($user);

        $this->facade->method('generateApiToken')->willReturn('token');

        $this->handler->formatLogin(['username' => 'myuser', 'password' => 'mypassword']);
    }

    // =========================================================================
    // formatRegister tests
    // =========================================================================

    public function testFormatRegisterReturnsErrorForMissingUsername(): void
    {
        $result = $this->handler->formatRegister([
            'email' => 'test@example.com',
            'password' => 'secret',
            'password_confirm' => 'secret'
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Username is required', $result['error']);
    }

    public function testFormatRegisterReturnsErrorForEmptyUsername(): void
    {
        $result = $this->handler->formatRegister([
            'username' => '   ',
            'email' => 'test@example.com',
            'password' => 'secret',
            'password_confirm' => 'secret'
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Username is required', $result['error']);
    }

    public function testFormatRegisterReturnsErrorForMissingEmail(): void
    {
        $result = $this->handler->formatRegister([
            'username' => 'testuser',
            'password' => 'secret',
            'password_confirm' => 'secret'
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Email is required', $result['error']);
    }

    public function testFormatRegisterReturnsErrorForMissingPassword(): void
    {
        $result = $this->handler->formatRegister([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password_confirm' => 'secret'
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Password is required', $result['error']);
    }

    public function testFormatRegisterReturnsErrorForPasswordMismatch(): void
    {
        $result = $this->handler->formatRegister([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'secret1',
            'password_confirm' => 'secret2'
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Passwords do not match', $result['error']);
    }

    public function testFormatRegisterReturnsErrorForInvalidEmail(): void
    {
        $result = $this->handler->formatRegister([
            'username' => 'testuser',
            'email' => 'not-an-email',
            'password' => 'secret',
            'password_confirm' => 'secret'
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid email format', $result['error']);
    }

    /**
     * @dataProvider invalidUsernameProvider
     */
    public function testFormatRegisterReturnsErrorForInvalidUsername(string $username): void
    {
        $result = $this->handler->formatRegister([
            'username' => $username,
            'email' => 'test@example.com',
            'password' => 'secret',
            'password_confirm' => 'secret'
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Username must be', $result['error']);
    }

    public static function invalidUsernameProvider(): array
    {
        return [
            'too short' => ['ab'],
            'contains space' => ['user name'],
            'contains special char' => ['user@name'],
            'contains hyphen' => ['user-name'],
            'too long' => [str_repeat('a', 51)],
        ];
    }

    /**
     * @dataProvider validUsernameProvider
     */
    public function testFormatRegisterAcceptsValidUsername(string $username): void
    {
        $user = $this->createMockUser(1, $username, 'test@example.com');

        $this->facade->method('register')->willReturn($user);
        $this->facade->method('generateApiToken')->willReturn('token');

        $result = $this->handler->formatRegister([
            'username' => $username,
            'email' => 'test@example.com',
            'password' => 'secret',
            'password_confirm' => 'secret'
        ]);

        $this->assertTrue($result['success']);
    }

    public static function validUsernameProvider(): array
    {
        return [
            'alphanumeric' => ['testuser123'],
            'with underscore' => ['test_user'],
            'all caps' => ['TESTUSER'],
            'mixed case' => ['TestUser_123'],
            'minimum length' => ['abc'],
            'maximum length' => [str_repeat('a', 50)],
        ];
    }

    public function testFormatRegisterReturnsSuccessWithToken(): void
    {
        $user = $this->createMockUser(1, 'newuser', 'new@example.com');

        $this->facade->method('register')
            ->with('newuser', 'new@example.com', 'password123')
            ->willReturn($user);

        $this->facade->method('generateApiToken')
            ->with(1)
            ->willReturn('new-api-token');

        $result = $this->handler->formatRegister([
            'username' => 'newuser',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirm' => 'password123'
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('new-api-token', $result['token']);
        $this->assertArrayHasKey('user', $result);
    }

    public function testFormatRegisterHandlesInvalidArgumentException(): void
    {
        $this->facade->method('register')
            ->willThrowException(new \InvalidArgumentException('Username already exists'));

        $result = $this->handler->formatRegister([
            'username' => 'existinguser',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirm' => 'password123'
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Username already exists', $result['error']);
    }

    public function testFormatRegisterHandlesRuntimeException(): void
    {
        $this->facade->method('register')
            ->willThrowException(new \RuntimeException('Database error'));

        $result = $this->handler->formatRegister([
            'username' => 'newuser',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirm' => 'password123'
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Registration failed. Please try again.', $result['error']);
    }

    public function testFormatRegisterSetsCurrentUser(): void
    {
        $user = $this->createMockUser(1, 'newuser', 'new@example.com');

        $this->facade->method('register')->willReturn($user);
        $this->facade->method('generateApiToken')->willReturn('token');

        $this->facade->expects($this->once())
            ->method('setCurrentUser')
            ->with($user);

        $this->handler->formatRegister([
            'username' => 'newuser',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirm' => 'password123'
        ]);
    }

    // =========================================================================
    // formatRefresh tests
    // =========================================================================

    public function testFormatRefreshReturnsErrorWhenNotAuthenticated(): void
    {
        $this->facade->method('getCurrentUser')
            ->willReturn(null);

        $result = $this->handler->formatRefresh();

        $this->assertFalse($result['success']);
        $this->assertEquals('Not authenticated', $result['error']);
    }

    public function testFormatRefreshReturnsNewToken(): void
    {
        $user = $this->createMockUser(1, 'testuser', 'test@example.com');

        $this->facade->method('getCurrentUser')
            ->willReturn($user);

        $this->facade->expects($this->once())
            ->method('invalidateApiToken')
            ->with(1);

        $this->facade->expects($this->once())
            ->method('generateApiToken')
            ->with(1)
            ->willReturn('new-token');

        $result = $this->handler->formatRefresh();

        $this->assertTrue($result['success']);
        $this->assertEquals('new-token', $result['token']);
    }

    public function testFormatRefreshHandlesException(): void
    {
        $user = $this->createMockUser(1, 'testuser', 'test@example.com');

        $this->facade->method('getCurrentUser')
            ->willReturn($user);

        $this->facade->method('invalidateApiToken')
            ->willThrowException(new \Exception('Token error'));

        $result = $this->handler->formatRefresh();

        $this->assertFalse($result['success']);
        $this->assertEquals('Failed to refresh token', $result['error']);
    }

    // =========================================================================
    // formatLogout tests
    // =========================================================================

    public function testFormatLogoutReturnsSuccess(): void
    {
        $this->facade->method('getCurrentUser')
            ->willReturn(null);

        $this->facade->expects($this->once())
            ->method('logout');

        $result = $this->handler->formatLogout();

        $this->assertTrue($result['success']);
    }

    public function testFormatLogoutInvalidatesTokenWhenUserExists(): void
    {
        $user = $this->createMockUser(1, 'testuser', 'test@example.com');

        $this->facade->method('getCurrentUser')
            ->willReturn($user);

        $this->facade->expects($this->once())
            ->method('invalidateApiToken')
            ->with(1);

        $this->facade->expects($this->once())
            ->method('logout');

        $result = $this->handler->formatLogout();

        $this->assertTrue($result['success']);
    }

    // =========================================================================
    // formatMe tests
    // =========================================================================

    public function testFormatMeReturnsErrorWhenNotAuthenticated(): void
    {
        $this->facade->method('getCurrentUser')
            ->willReturn(null);

        $result = $this->handler->formatMe();

        $this->assertFalse($result['success']);
        $this->assertEquals('Not authenticated', $result['error']);
    }

    public function testFormatMeReturnsUserData(): void
    {
        $user = $this->createMockUser(1, 'testuser', 'test@example.com');

        $this->facade->method('getCurrentUser')
            ->willReturn($user);

        $result = $this->handler->formatMe();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals(1, $result['user']['id']);
        $this->assertEquals('testuser', $result['user']['username']);
        $this->assertEquals('test@example.com', $result['user']['email']);
    }

    // =========================================================================
    // validateBearerToken tests
    // =========================================================================

    public function testValidateBearerTokenReturnsNullForEmptyHeader(): void
    {
        // Clear authorization header
        unset($_SERVER['HTTP_AUTHORIZATION']);

        $result = $this->handler->validateBearerToken();

        $this->assertNull($result);
    }

    public function testValidateBearerTokenReturnsNullForInvalidFormat(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Basic sometoken';

        $result = $this->handler->validateBearerToken();

        $this->assertNull($result);
    }

    public function testValidateBearerTokenExtractsAndValidatesToken(): void
    {
        $user = $this->createMockUser(1, 'testuser', 'test@example.com');
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer valid-token';

        $this->facade->expects($this->once())
            ->method('validateApiToken')
            ->with('valid-token')
            ->willReturn($user);

        $this->facade->expects($this->once())
            ->method('setCurrentUser')
            ->with($user);

        $result = $this->handler->validateBearerToken();

        $this->assertSame($user, $result);
    }

    public function testValidateBearerTokenReturnsNullForInvalidToken(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer invalid-token';

        $this->facade->method('validateApiToken')
            ->with('invalid-token')
            ->willReturn(null);

        $result = $this->handler->validateBearerToken();

        $this->assertNull($result);
    }

    public function testValidateBearerTokenIsCaseInsensitive(): void
    {
        $user = $this->createMockUser(1, 'testuser', 'test@example.com');
        $_SERVER['HTTP_AUTHORIZATION'] = 'BEARER valid-token';

        $this->facade->method('validateApiToken')->willReturn($user);

        $result = $this->handler->validateBearerToken();

        $this->assertNotNull($result);
    }

    // =========================================================================
    // validateSession tests
    // =========================================================================

    public function testValidateSessionDelegatesToFacade(): void
    {
        $this->facade->expects($this->once())
            ->method('validateSession')
            ->willReturn(true);

        $result = $this->handler->validateSession();

        $this->assertTrue($result);
    }

    public function testValidateSessionReturnsFalse(): void
    {
        $this->facade->method('validateSession')
            ->willReturn(false);

        $result = $this->handler->validateSession();

        $this->assertFalse($result);
    }

    // =========================================================================
    // isAuthenticated tests
    // =========================================================================

    public function testIsAuthenticatedReturnsTrueForValidToken(): void
    {
        $user = $this->createMockUser(1, 'testuser', 'test@example.com');
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer valid-token';

        $this->facade->method('validateApiToken')->willReturn($user);

        $result = $this->handler->isAuthenticated();

        $this->assertTrue($result);
    }

    public function testIsAuthenticatedFallsBackToSession(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);

        $this->facade->method('validateSession')
            ->willReturn(true);

        $result = $this->handler->isAuthenticated();

        $this->assertTrue($result);
    }

    public function testIsAuthenticatedReturnsFalseWhenBothFail(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);

        $this->facade->method('validateSession')
            ->willReturn(false);

        $result = $this->handler->isAuthenticated();

        $this->assertFalse($result);
    }

    // =========================================================================
    // Helper methods
    // =========================================================================

    /**
     * Create a mock User object.
     *
     * @param int    $id       User ID
     * @param string $username Username
     * @param string $email    Email
     *
     * @return User&MockObject
     */
    private function createMockUser(int $id, string $username, string $email): User
    {
        $userId = UserId::fromInt($id);
        $created = new \DateTimeImmutable('2024-01-01');

        $user = $this->createMock(User::class);
        $user->method('id')->willReturn($userId);
        $user->method('username')->willReturn($username);
        $user->method('email')->willReturn($email);
        $user->method('role')->willReturn('user');
        $user->method('created')->willReturn($created);
        $user->method('lastLogin')->willReturn(null);
        $user->method('apiTokenExpires')->willReturn(null);
        $user->method('wordPressId')->willReturn(null);

        return $user;
    }

    protected function tearDown(): void
    {
        // Clean up global state
        unset($_SERVER['HTTP_AUTHORIZATION']);
        parent::tearDown();
    }
}
