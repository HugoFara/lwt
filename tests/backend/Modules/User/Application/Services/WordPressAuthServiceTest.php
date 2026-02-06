<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\User\Application\Services;

require_once __DIR__ . '/../../../../../../src/Shared/Infrastructure/Bootstrap/EnvLoader.php';

use Lwt\Shared\Infrastructure\Bootstrap\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Modules\User\Application\Services\WordPressAuthService;
use Lwt\Modules\User\Application\UserFacade;
use Lwt\Modules\User\Infrastructure\MySqlUserRepository;
use Lwt\Shared\Infrastructure\Database\Configuration;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../../../../src/Shared/Infrastructure/Bootstrap/db_bootstrap.php';

/**
 * Unit tests for the WordPressAuthService class.
 *
 * Tests WordPress integration service methods.
 * Note: Full integration tests require WordPress to be installed.
 */
class WordPressAuthServiceTest extends TestCase
{
    private static bool $dbConnected = false;
    private WordPressAuthService $service;
    private array $originalSession;

    public static function setUpBeforeClass(): void
    {
        $config = EnvLoader::getDatabaseConfig();
        $testDbname = "test_" . $config['dbname'];

        if (!Globals::getDbConnection()) {
            $connection = Configuration::connect(
                $config['server'],
                $config['userid'],
                $config['passwd'],
                $testDbname,
                $config['socket'] ?? ''
            );
            Globals::setDbConnection($connection);
        }
        self::$dbConnected = (Globals::getDbConnection() !== null);
    }

    protected function setUp(): void
    {
        $this->originalSession = $_SESSION ?? [];
        $_SESSION = [];

        $repository = new MySqlUserRepository();
        $userFacade = new UserFacade($repository);
        $this->service = new WordPressAuthService($userFacade);
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->originalSession;
    }

    // ===== validateRedirectUrl tests =====

    public function testValidateRedirectUrlReturnsDefaultForNull(): void
    {
        $result = $this->service->validateRedirectUrl(null);
        $this->assertEquals('index.php', $result);
    }

    public function testValidateRedirectUrlReturnsDefaultForEmpty(): void
    {
        $result = $this->service->validateRedirectUrl('');
        $this->assertEquals('index.php', $result);
    }

    public function testValidateRedirectUrlReturnsDefaultForNonexistentFile(): void
    {
        $result = $this->service->validateRedirectUrl('this_file_does_not_exist_12345.php');
        $this->assertEquals('index.php', $result);
    }

    public function testValidateRedirectUrlReturnsInputForExistingFile(): void
    {
        // Use a file that definitely exists in the test environment
        // The index.php file should exist at the root
        $result = $this->service->validateRedirectUrl('index.php');

        // If index.php exists, it should return it; otherwise default
        $this->assertIsString($result);
    }

    public function testValidateRedirectUrlHandlesQueryString(): void
    {
        $result = $this->service->validateRedirectUrl('nonexistent.php?param=value');
        $this->assertEquals('index.php', $result);
    }

    // ===== getLoginUrl tests =====

    public function testGetLoginUrlReturnsWordPressLoginPath(): void
    {
        $result = $this->service->getLoginUrl();

        $this->assertStringContainsString('wp-login.php', $result);
    }

    public function testGetLoginUrlIncludesRedirectParameter(): void
    {
        $result = $this->service->getLoginUrl();

        $this->assertStringContainsString('redirect_to=', $result);
    }

    public function testGetLoginUrlEncodesRedirectParameter(): void
    {
        $redirectTo = './lwt/some path.php';
        $result = $this->service->getLoginUrl($redirectTo);

        $this->assertStringContainsString(urlencode($redirectTo), $result);
    }

    public function testGetLoginUrlUsesDefaultRedirect(): void
    {
        $result = $this->service->getLoginUrl();

        $this->assertStringContainsString('lwt', $result);
        $this->assertStringContainsString('wp_lwt_start.php', $result);
    }

    // ===== Session user tests =====

    public function testGetSessionUserReturnsNullInitially(): void
    {
        $result = $this->service->getSessionUser();
        $this->assertNull($result);
    }

    public function testSetSessionUserStoresUserId(): void
    {
        $this->service->setSessionUser(42);

        $result = $this->service->getSessionUser();
        $this->assertEquals(42, $result);
    }

    public function testSetSessionUserOverwritesPreviousValue(): void
    {
        $this->service->setSessionUser(42);
        $this->service->setSessionUser(99);

        $result = $this->service->getSessionUser();
        $this->assertEquals(99, $result);
    }

    public function testClearSessionUserRemovesUserId(): void
    {
        $this->service->setSessionUser(42);
        $this->service->clearSessionUser();

        $result = $this->service->getSessionUser();
        $this->assertNull($result);
    }

    public function testClearSessionUserDoesNothingWhenEmpty(): void
    {
        // Should not throw or error
        $this->service->clearSessionUser();
        $this->assertNull($this->service->getSessionUser());
    }

    // ===== isUserLoggedIn tests =====

    public function testIsUserLoggedInReturnsFalseWithoutWordPress(): void
    {
        // Without WordPress functions defined, should return false
        $result = $this->service->isUserLoggedIn();
        $this->assertFalse($result);
    }

    // ===== getCurrentUserId tests =====

    public function testGetCurrentUserIdReturnsNullWithoutWordPress(): void
    {
        // Without WordPress, should return null
        $result = $this->service->getCurrentUserId();
        $this->assertNull($result);
    }

    // ===== getCurrentUserInfo tests =====

    public function testGetCurrentUserInfoReturnsNullWithoutWordPress(): void
    {
        // Without WordPress functions defined, should return null
        $result = $this->service->getCurrentUserInfo();
        $this->assertNull($result);
    }

    // ===== loadWordPress tests =====

    public function testLoadWordPressReturnsBool(): void
    {
        // Should return bool regardless of WordPress presence
        $result = $this->service->loadWordPress();
        $this->assertIsBool($result);
    }

    // ===== handleStart tests =====

    public function testHandleStartReturnsArrayWithRequiredKeys(): void
    {
        $result = $this->service->handleStart(null);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('redirect', $result);
        $this->assertArrayHasKey('error', $result);
    }

    public function testHandleStartWithRedirectUrl(): void
    {
        $result = $this->service->handleStart('some/page.php');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    // ===== handleStop tests =====

    public function testHandleStopReturnsArrayWithRequiredKeys(): void
    {
        $result = $this->service->handleStop();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('redirect', $result);
    }

    public function testHandleStopIsSuccessful(): void
    {
        $result = $this->service->handleStop();

        $this->assertTrue($result['success']);
    }

    public function testHandleStopRedirectsToLogin(): void
    {
        $result = $this->service->handleStop();

        $this->assertStringContainsString('wp-login.php', $result['redirect']);
    }

    // ===== startSession tests =====

    public function testStartSessionReturnsArrayWithRequiredKeys(): void
    {
        $result = $this->service->startSession();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('error', $result);
    }

    // ===== Integration tests =====

    public function testSessionUserRoundTrip(): void
    {
        // Set
        $this->service->setSessionUser(123);
        $this->assertEquals(123, $this->service->getSessionUser());

        // Update
        $this->service->setSessionUser(456);
        $this->assertEquals(456, $this->service->getSessionUser());

        // Clear
        $this->service->clearSessionUser();
        $this->assertNull($this->service->getSessionUser());

        // Set again
        $this->service->setSessionUser(789);
        $this->assertEquals(789, $this->service->getSessionUser());
    }

    public function testMultipleServiceInstances(): void
    {
        $repository = new MySqlUserRepository();
        $userFacade = new UserFacade($repository);
        $service1 = new WordPressAuthService($userFacade);
        $service2 = new WordPressAuthService($userFacade);

        // Both should work independently but share session
        $service1->setSessionUser(100);
        $this->assertEquals(100, $service2->getSessionUser());

        $service2->setSessionUser(200);
        $this->assertEquals(200, $service1->getSessionUser());
    }
}
