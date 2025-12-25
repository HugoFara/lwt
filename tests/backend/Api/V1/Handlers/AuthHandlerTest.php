<?php declare(strict_types=1);
namespace Lwt\Tests\Api\V1\Handlers;

require_once __DIR__ . '/../../../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Api\V1\Handlers\AuthHandler;
use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Shared\Infrastructure\Database\Configuration;
use Lwt\Shared\Infrastructure\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../../../src/backend/Api/V1/ApiV1.php';
require_once __DIR__ . '/../../../../../src/backend/Api/V1/Handlers/AuthHandler.php';

/**
 * Unit tests for the AuthHandler class.
 *
 * Tests API authentication operations including login, registration,
 * token refresh, and logout.
 */
class AuthHandlerTest extends TestCase
{
    private static bool $dbConnected = false;
    private AuthHandler $handler;
    private static string $testUsername = 'api_test_user_';
    private static int $testCounter = 0;

    public static function setUpBeforeClass(): void
    {
        $config = EnvLoader::getDatabaseConfig();
        $testDbname = "test_" . $config['dbname'];

        if (!Globals::getDbConnection()) {
            try {
                $connection = Configuration::connect(
                    $config['server'],
                    $config['userid'],
                    $config['passwd'],
                    $testDbname,
                    $config['socket'] ?? ''
                );
                Globals::setDbConnection($connection);
                self::$dbConnected = true;
            } catch (\Exception $e) {
                self::$dbConnected = false;
            }
        } else {
            self::$dbConnected = true;
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new AuthHandler();
        self::$testCounter++;
    }

    protected function tearDown(): void
    {
        // Clean up test users
        if (self::$dbConnected) {
            $prefix = '';
            Connection::query("DELETE FROM {$prefix}users WHERE UsUsername LIKE 'api_test_user_%'");
        }
        Globals::setCurrentUserId(null);
        parent::tearDown();
    }

    // ===== Class structure tests =====

    /**
     * Test that AuthHandler class has the required methods.
     */
    public function testClassHasRequiredMethods(): void
    {
        $reflection = new \ReflectionClass(AuthHandler::class);

        // API formatter methods
        $this->assertTrue($reflection->hasMethod('formatLogin'));
        $this->assertTrue($reflection->hasMethod('formatRegister'));
        $this->assertTrue($reflection->hasMethod('formatRefresh'));
        $this->assertTrue($reflection->hasMethod('formatLogout'));
        $this->assertTrue($reflection->hasMethod('formatMe'));

        // Auth validation methods
        $this->assertTrue($reflection->hasMethod('validateBearerToken'));
        $this->assertTrue($reflection->hasMethod('validateSession'));
        $this->assertTrue($reflection->hasMethod('isAuthenticated'));
        $this->assertTrue($reflection->hasMethod('getAuthService'));
    }

    // ===== Registration tests =====

    /**
     * Test registration with valid data.
     */
    public function testFormatRegisterWithValidData(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $username = self::$testUsername . self::$testCounter;
        $result = $this->handler->formatRegister([
            'username' => $username,
            'email' => $username . '@test.com',
            'password' => 'SecureP@ss123!',
            'password_confirm' => 'SecureP@ss123!'
        ]);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals($username, $result['user']['username']);
    }

    /**
     * Test registration with missing username.
     */
    public function testFormatRegisterMissingUsername(): void
    {
        $result = $this->handler->formatRegister([
            'email' => 'test@test.com',
            'password' => 'SecureP@ss123!',
            'password_confirm' => 'SecureP@ss123!'
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Username', $result['error']);
    }

    /**
     * Test registration with missing email.
     */
    public function testFormatRegisterMissingEmail(): void
    {
        $result = $this->handler->formatRegister([
            'username' => 'testuser',
            'password' => 'SecureP@ss123!',
            'password_confirm' => 'SecureP@ss123!'
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Email', $result['error']);
    }

    /**
     * Test registration with missing password.
     */
    public function testFormatRegisterMissingPassword(): void
    {
        $result = $this->handler->formatRegister([
            'username' => 'testuser',
            'email' => 'test@test.com'
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Password', $result['error']);
    }

    /**
     * Test registration with password mismatch.
     */
    public function testFormatRegisterPasswordMismatch(): void
    {
        $result = $this->handler->formatRegister([
            'username' => 'testuser',
            'email' => 'test@test.com',
            'password' => 'SecureP@ss123!',
            'password_confirm' => 'DifferentPassword!'
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('match', $result['error']);
    }

    /**
     * Test registration with invalid email format.
     */
    public function testFormatRegisterInvalidEmail(): void
    {
        $result = $this->handler->formatRegister([
            'username' => 'testuser',
            'email' => 'not-an-email',
            'password' => 'SecureP@ss123!',
            'password_confirm' => 'SecureP@ss123!'
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('email', strtolower($result['error']));
    }

    /**
     * Test registration with invalid username format.
     */
    public function testFormatRegisterInvalidUsername(): void
    {
        $result = $this->handler->formatRegister([
            'username' => 'ab', // Too short
            'email' => 'test@test.com',
            'password' => 'SecureP@ss123!',
            'password_confirm' => 'SecureP@ss123!'
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Username', $result['error']);
    }

    // ===== Login tests =====

    /**
     * Test login with valid credentials.
     */
    public function testFormatLoginWithValidCredentials(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $username = self::$testUsername . self::$testCounter;
        $password = 'SecureP@ss123!';

        // First register a user
        $this->handler->formatRegister([
            'username' => $username,
            'email' => $username . '@test.com',
            'password' => $password,
            'password_confirm' => $password
        ]);

        // Logout to clear session
        $this->handler->formatLogout();

        // Now try to login
        $result = $this->handler->formatLogin([
            'username' => $username,
            'password' => $password
        ]);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('user', $result);
    }

    /**
     * Test login with email instead of username.
     */
    public function testFormatLoginWithEmail(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $username = self::$testUsername . self::$testCounter;
        $email = $username . '@test.com';
        $password = 'SecureP@ss123!';

        // Register user
        $this->handler->formatRegister([
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'password_confirm' => $password
        ]);

        $this->handler->formatLogout();

        // Login with email
        $result = $this->handler->formatLogin([
            'email' => $email,
            'password' => $password
        ]);

        $this->assertTrue($result['success']);
    }

    /**
     * Test login with missing credentials.
     */
    public function testFormatLoginMissingCredentials(): void
    {
        $result = $this->handler->formatLogin([]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('required', $result['error']);
    }

    /**
     * Test login with invalid credentials.
     */
    public function testFormatLoginInvalidCredentials(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->handler->formatLogin([
            'username' => 'nonexistent_user',
            'password' => 'wrongpassword'
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // ===== Me endpoint tests =====

    /**
     * Test formatMe returns error when not authenticated.
     */
    public function testFormatMeNotAuthenticated(): void
    {
        Globals::setCurrentUserId(null);
        $result = $this->handler->formatMe();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('authenticated', strtolower($result['error']));
    }

    /**
     * Test formatMe returns user info when authenticated.
     */
    public function testFormatMeWhenAuthenticated(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $username = self::$testUsername . self::$testCounter;
        $password = 'SecureP@ss123!';

        // Register and stay logged in
        $this->handler->formatRegister([
            'username' => $username,
            'email' => $username . '@test.com',
            'password' => $password,
            'password_confirm' => $password
        ]);

        $result = $this->handler->formatMe();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals($username, $result['user']['username']);
    }

    // ===== Logout tests =====

    /**
     * Test logout always succeeds.
     */
    public function testFormatLogoutAlwaysSucceeds(): void
    {
        $result = $this->handler->formatLogout();

        $this->assertTrue($result['success']);
    }

    /**
     * Test logout clears user context.
     */
    public function testFormatLogoutClearsUserContext(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $username = self::$testUsername . self::$testCounter;
        $password = 'SecureP@ss123!';

        // Register (sets user context)
        $this->handler->formatRegister([
            'username' => $username,
            'email' => $username . '@test.com',
            'password' => $password,
            'password_confirm' => $password
        ]);

        // Verify user is set
        $this->assertNotNull(Globals::getCurrentUserId());

        // Logout
        $this->handler->formatLogout();

        // Verify user is cleared
        $this->assertNull(Globals::getCurrentUserId());
    }

    // ===== Refresh tests =====

    /**
     * Test refresh returns error when not authenticated.
     */
    public function testFormatRefreshNotAuthenticated(): void
    {
        Globals::setCurrentUserId(null);
        $result = $this->handler->formatRefresh();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('authenticated', strtolower($result['error']));
    }

    /**
     * Test refresh returns new token when authenticated.
     */
    public function testFormatRefreshWhenAuthenticated(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $username = self::$testUsername . self::$testCounter;
        $password = 'SecureP@ss123!';

        // Register (authenticates user)
        $registerResult = $this->handler->formatRegister([
            'username' => $username,
            'email' => $username . '@test.com',
            'password' => $password,
            'password_confirm' => $password
        ]);

        $oldToken = $registerResult['token'];

        // Refresh token
        $result = $this->handler->formatRefresh();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('token', $result);
        // New token should be different from old token
        $this->assertNotEquals($oldToken, $result['token']);
    }

    // ===== Authentication helper tests =====

    /**
     * Test isAuthenticated returns false when not authenticated.
     */
    public function testIsAuthenticatedReturnsFalseWhenNotAuthenticated(): void
    {
        Globals::setCurrentUserId(null);
        $_SERVER['HTTP_AUTHORIZATION'] = '';

        // Create a fresh handler to test
        $handler = new AuthHandler();
        $this->assertFalse($handler->isAuthenticated());
    }

    /**
     * Test getAuthService returns AuthService instance.
     */
    public function testGetAuthServiceReturnsInstance(): void
    {
        $authService = $this->handler->getAuthService();

        $this->assertInstanceOf(\Lwt\Services\AuthService::class, $authService);
    }

    // ===== User data format tests =====

    /**
     * Test user data has required fields.
     */
    public function testUserDataHasRequiredFields(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $username = self::$testUsername . self::$testCounter;
        $password = 'SecureP@ss123!';

        $result = $this->handler->formatRegister([
            'username' => $username,
            'email' => $username . '@test.com',
            'password' => $password,
            'password_confirm' => $password
        ]);

        $this->assertTrue($result['success']);
        $user = $result['user'];

        $this->assertArrayHasKey('id', $user);
        $this->assertArrayHasKey('username', $user);
        $this->assertArrayHasKey('email', $user);
        $this->assertArrayHasKey('role', $user);
        $this->assertArrayHasKey('created', $user);
        $this->assertArrayHasKey('last_login', $user);
        $this->assertArrayHasKey('has_wordpress', $user);
    }
}
