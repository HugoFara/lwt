<?php declare(strict_types=1);

namespace Lwt\Tests\Modules\User\Infrastructure;

use Lwt\Modules\User\Infrastructure\AuthFormDataManager;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AuthFormDataManager.
 *
 * Tests form field persistence for authentication forms.
 *
 * @covers \Lwt\Modules\User\Infrastructure\AuthFormDataManager
 */
class AuthFormDataManagerTest extends TestCase
{
    private AuthFormDataManager $manager;

    protected function setUp(): void
    {
        // Clear any existing session data
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];

        $this->manager = new AuthFormDataManager();
    }

    protected function tearDown(): void
    {
        // Clean up session after each test
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    // ===================================
    // USERNAME TESTS
    // ===================================

    public function testGetUsernameReturnsEmptyWhenNotSet(): void
    {
        $this->assertSame('', $this->manager->getUsername());
    }

    public function testSetAndGetUsername(): void
    {
        $this->manager->setUsername('testuser');
        $this->assertSame('testuser', $this->manager->getUsername());
    }

    public function testClearUsername(): void
    {
        $this->manager->setUsername('testuser');
        $this->manager->clearUsername();
        $this->assertSame('', $this->manager->getUsername());
    }

    public function testGetAndClearUsername(): void
    {
        $this->manager->setUsername('testuser');
        $username = $this->manager->getAndClearUsername();

        $this->assertSame('testuser', $username);
        $this->assertSame('', $this->manager->getUsername());
    }

    public function testGetUsernameReturnsEmptyForNonStringValue(): void
    {
        $_SESSION['auth_username'] = 12345;
        $this->assertSame('', $this->manager->getUsername());
    }

    // ===================================
    // EMAIL TESTS
    // ===================================

    public function testGetEmailReturnsEmptyWhenNotSet(): void
    {
        $this->assertSame('', $this->manager->getEmail());
    }

    public function testSetAndGetEmail(): void
    {
        $this->manager->setEmail('test@example.com');
        $this->assertSame('test@example.com', $this->manager->getEmail());
    }

    public function testClearEmail(): void
    {
        $this->manager->setEmail('test@example.com');
        $this->manager->clearEmail();
        $this->assertSame('', $this->manager->getEmail());
    }

    public function testGetAndClearEmail(): void
    {
        $this->manager->setEmail('test@example.com');
        $email = $this->manager->getAndClearEmail();

        $this->assertSame('test@example.com', $email);
        $this->assertSame('', $this->manager->getEmail());
    }

    public function testGetEmailReturnsEmptyForNonStringValue(): void
    {
        $_SESSION['auth_email'] = ['not', 'a', 'string'];
        $this->assertSame('', $this->manager->getEmail());
    }

    // ===================================
    // REDIRECT URL TESTS
    // ===================================

    public function testGetRedirectUrlReturnsDefaultWhenNotSet(): void
    {
        $this->assertSame('/', $this->manager->getRedirectUrl());
        $this->assertSame('/custom', $this->manager->getRedirectUrl('/custom'));
    }

    public function testSetAndGetRedirectUrl(): void
    {
        $this->manager->setRedirectUrl('/dashboard');
        $this->assertSame('/dashboard', $this->manager->getRedirectUrl());
    }

    public function testClearRedirectUrl(): void
    {
        $this->manager->setRedirectUrl('/dashboard');
        $this->manager->clearRedirectUrl();
        $this->assertSame('/', $this->manager->getRedirectUrl());
    }

    public function testGetAndClearRedirectUrl(): void
    {
        $this->manager->setRedirectUrl('/dashboard');
        $url = $this->manager->getAndClearRedirectUrl();

        $this->assertSame('/dashboard', $url);
        $this->assertSame('/', $this->manager->getRedirectUrl());
    }

    public function testGetAndClearRedirectUrlReturnsDefaultWhenNotSet(): void
    {
        $url = $this->manager->getAndClearRedirectUrl('/home');
        $this->assertSame('/home', $url);
    }

    public function testGetRedirectUrlReturnsDefaultForNonStringValue(): void
    {
        $_SESSION['auth_redirect'] = null;
        $this->assertSame('/default', $this->manager->getRedirectUrl('/default'));
    }

    // ===================================
    // PASSWORD EMAIL TESTS
    // ===================================

    public function testGetPasswordEmailReturnsEmptyWhenNotSet(): void
    {
        $this->assertSame('', $this->manager->getPasswordEmail());
    }

    public function testSetAndGetPasswordEmail(): void
    {
        $this->manager->setPasswordEmail('reset@example.com');
        $this->assertSame('reset@example.com', $this->manager->getPasswordEmail());
    }

    public function testClearPasswordEmail(): void
    {
        $this->manager->setPasswordEmail('reset@example.com');
        $this->manager->clearPasswordEmail();
        $this->assertSame('', $this->manager->getPasswordEmail());
    }

    public function testGetAndClearPasswordEmail(): void
    {
        $this->manager->setPasswordEmail('reset@example.com');
        $email = $this->manager->getAndClearPasswordEmail();

        $this->assertSame('reset@example.com', $email);
        $this->assertSame('', $this->manager->getPasswordEmail());
    }

    public function testGetPasswordEmailReturnsEmptyForNonStringValue(): void
    {
        $_SESSION['password_email'] = false;
        $this->assertSame('', $this->manager->getPasswordEmail());
    }

    // ===================================
    // CLEAR ALL TESTS
    // ===================================

    public function testClearAllRemovesAllFormData(): void
    {
        $this->manager->setUsername('testuser');
        $this->manager->setEmail('test@example.com');
        $this->manager->setRedirectUrl('/dashboard');
        $this->manager->setPasswordEmail('reset@example.com');

        $this->manager->clearAll();

        $this->assertSame('', $this->manager->getUsername());
        $this->assertSame('', $this->manager->getEmail());
        $this->assertSame('/', $this->manager->getRedirectUrl());
        $this->assertSame('', $this->manager->getPasswordEmail());
    }

    public function testClearAllDoesNotErrorWhenNoData(): void
    {
        // Should not throw any exceptions
        $this->manager->clearAll();
        $this->assertSame('', $this->manager->getUsername());
    }

    // ===================================
    // SESSION PERSISTENCE TESTS
    // ===================================

    public function testDataPersistsAcrossManagerInstances(): void
    {
        $this->manager->setUsername('persisteduser');
        $this->manager->setEmail('persisted@example.com');
        $this->manager->setRedirectUrl('/persisted');
        $this->manager->setPasswordEmail('persist-reset@example.com');

        // Create new manager instance (simulates new request)
        $newManager = new AuthFormDataManager();

        $this->assertSame('persisteduser', $newManager->getUsername());
        $this->assertSame('persisted@example.com', $newManager->getEmail());
        $this->assertSame('/persisted', $newManager->getRedirectUrl());
        $this->assertSame('persist-reset@example.com', $newManager->getPasswordEmail());
    }

    // ===================================
    // INTEGRATION TESTS
    // ===================================

    public function testTypicalLoginFormWorkflow(): void
    {
        // User submits login form with invalid credentials
        $this->manager->setUsername('wronguser');

        // Create new instance (page reload after redirect)
        $newManager = new AuthFormDataManager();

        // View retrieves and clears username for form repopulation
        $username = $newManager->getAndClearUsername();

        $this->assertSame('wronguser', $username);
        $this->assertSame('', $newManager->getUsername()); // Should be cleared
    }

    public function testTypicalRegistrationFormWorkflow(): void
    {
        // User submits registration form with error
        $this->manager->setUsername('newuser');
        $this->manager->setEmail('newuser@example.com');

        // Create new instance (page reload after redirect)
        $newManager = new AuthFormDataManager();

        // View retrieves and clears form data
        $username = $newManager->getAndClearUsername();
        $email = $newManager->getAndClearEmail();

        $this->assertSame('newuser', $username);
        $this->assertSame('newuser@example.com', $email);

        // Should be cleared
        $this->assertSame('', $newManager->getUsername());
        $this->assertSame('', $newManager->getEmail());
    }

    public function testTypicalPasswordResetWorkflow(): void
    {
        // User submits forgot password form
        $this->manager->setPasswordEmail('forgot@example.com');

        // Create new instance
        $newManager = new AuthFormDataManager();

        // View retrieves and clears email
        $email = $newManager->getAndClearPasswordEmail();

        $this->assertSame('forgot@example.com', $email);
        $this->assertSame('', $newManager->getPasswordEmail());
    }

    public function testLoginWithRedirectWorkflow(): void
    {
        // User tries to access protected page
        $this->manager->setRedirectUrl('/protected/page');

        // User logs in
        // After login, retrieve the intended destination
        $redirect = $this->manager->getAndClearRedirectUrl('/');

        $this->assertSame('/protected/page', $redirect);
        $this->assertSame('/', $this->manager->getRedirectUrl());
    }
}
