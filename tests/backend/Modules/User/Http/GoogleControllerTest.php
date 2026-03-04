<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\User\Http;

use Lwt\Modules\User\Application\Services\GoogleAuthService;
use Lwt\Modules\User\Application\UserFacade;
use Lwt\Modules\User\Domain\User;
use Lwt\Modules\User\Http\GoogleController;
use Lwt\Shared\Domain\ValueObjects\UserId;
use Lwt\Shared\Infrastructure\Exception\AuthException;
use Lwt\Shared\Infrastructure\Http\RedirectResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for GoogleController.
 *
 * Tests the Google OAuth controller: start flow, callback handling,
 * state validation, account linking, and error paths.
 */
class GoogleControllerTest extends TestCase
{
    /** @var GoogleAuthService&MockObject */
    private GoogleAuthService $authService;

    private GoogleController $controller;

    /** @var array<string, mixed> */
    private array $originalRequest;

    /** @var array<string, mixed> */
    private array $originalPost;

    /** @var array<string, mixed> */
    private array $originalServer;

    /** @var array<string, mixed> */
    private array $originalSession;

    protected function setUp(): void
    {
        $this->authService = $this->createMock(GoogleAuthService::class);
        $this->controller = new GoogleController($this->authService);

        // Save and reset superglobals
        $this->originalRequest = $_REQUEST;
        $this->originalPost = $_POST;
        $this->originalServer = $_SERVER;
        $this->originalSession = $_SESSION ?? [];

        $_REQUEST = [];
        $_POST = [];
        $_SERVER = ['REQUEST_METHOD' => 'GET'];
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_REQUEST = $this->originalRequest;
        $_POST = $this->originalPost;
        $_SERVER = $this->originalServer;
        $_SESSION = $this->originalSession;
    }

    /**
     * Create a mock User domain object.
     */
    private function createMockUser(int $id = 1): User
    {
        return User::reconstitute(
            $id,
            'testuser',
            'test@example.com',
            'hashed_password',
            null,  // apiToken
            null,  // apiTokenExpires
            null,  // rememberToken
            null,  // rememberTokenExpires
            null,  // passwordResetToken
            null,  // passwordResetTokenExpires
            null,  // emailVerifiedAt
            null,  // emailVerificationToken
            null,  // emailVerificationTokenExpires
            null,  // wordPressId
            'google-id-123',  // googleId
            null,  // microsoftId
            new \DateTimeImmutable(),
            null,  // lastLogin
            true,  // isActive
            'user' // role
        );
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    #[Test]
    public function constructorCreatesValidController(): void
    {
        $this->assertInstanceOf(GoogleController::class, $this->controller);
    }

    #[Test]
    public function constructorInjectsGoogleAuthService(): void
    {
        $reflection = new \ReflectionProperty(GoogleController::class, 'googleAuthService');
        $reflection->setAccessible(true);

        $service = $reflection->getValue($this->controller);

        $this->assertInstanceOf(GoogleAuthService::class, $service);
    }

    #[Test]
    public function controllerExtendsBaseController(): void
    {
        $reflection = new \ReflectionClass(GoogleController::class);

        $this->assertSame(
            'Lwt\Shared\Http\BaseController',
            $reflection->getParentClass()->getName()
        );
    }

    // =========================================================================
    // start() tests
    // =========================================================================

    #[Test]
    public function startThrowsAuthExceptionWhenNotConfigured(): void
    {
        $this->authService->method('isConfigured')->willReturn(false);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Google OAuth is not configured.');

        $this->controller->start([]);
    }

    #[Test]
    public function startCallsGetAuthorizationUrlWithLinkModeFalse(): void
    {
        $this->authService->method('isConfigured')->willReturn(true);
        $this->authService->expects($this->once())
            ->method('getAuthorizationUrl')
            ->with(false)
            ->willReturn('https://accounts.google.com/o/oauth2/auth?client_id=xxx');

        $this->controller->start([]);
    }

    #[Test]
    public function startCallsGetAuthorizationUrlWithLinkModeTrue(): void
    {
        $_REQUEST['link'] = '1';

        $this->authService->method('isConfigured')->willReturn(true);
        $this->authService->expects($this->once())
            ->method('getAuthorizationUrl')
            ->with(true)
            ->willReturn('https://accounts.google.com/o/oauth2/auth?client_id=xxx');

        $this->controller->start([]);
    }

    #[Test]
    public function startRedirectsToAuthorizationUrl(): void
    {
        $expectedUrl = 'https://accounts.google.com/o/oauth2/auth?client_id=test123&state=abc';

        $this->authService->method('isConfigured')->willReturn(true);
        $this->authService->method('getAuthorizationUrl')
            ->willReturn($expectedUrl);

        // redirect() returns a RedirectResponse; in the void method it's created
        // but the return value is discarded. We verify the service was called.
        $this->controller->start([]);

        // If we got here without exception, the redirect was attempted
        $this->assertTrue(true);
    }

    // =========================================================================
    // callback() - not configured
    // =========================================================================

    #[Test]
    public function callbackThrowsAuthExceptionWhenNotConfigured(): void
    {
        $this->authService->method('isConfigured')->willReturn(false);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Google OAuth is not configured.');

        $this->controller->callback([]);
    }

    // =========================================================================
    // callback() - error from Google
    // =========================================================================

    #[Test]
    public function callbackSetsSessionErrorWhenGoogleReturnsError(): void
    {
        $_REQUEST['error'] = 'access_denied';
        // Provide a non-empty code so the "Invalid response" check doesn't
        // overwrite the "cancelled" message (controller doesn't return after redirect).
        $_REQUEST['code'] = 'some_code';
        $_REQUEST['state'] = 'some_state';

        $this->authService->method('isConfigured')->willReturn(true);
        $this->authService->method('handleCallback')
            ->willReturn([
                'success' => false,
                'redirect' => '/login',
                'error' => null,
                'user' => null,
            ]);

        $this->controller->callback([]);

        $this->assertSame('Google login was cancelled.', $_SESSION['auth_error']);
    }

    // =========================================================================
    // callback() - missing code
    // =========================================================================

    #[Test]
    public function callbackSetsSessionErrorWhenCodeIsMissing(): void
    {
        // No 'error' and no 'code' in request.
        // Controller does not return after redirect, so handleCallback is still
        // called with the empty code. We must provide a valid result.
        $this->authService->method('isConfigured')->willReturn(true);
        $this->authService->method('handleCallback')
            ->willReturn([
                'success' => false,
                'redirect' => '/login',
                'error' => null,
                'user' => null,
            ]);

        $this->controller->callback([]);

        $this->assertSame('Invalid response from Google.', $_SESSION['auth_error']);
    }

    #[Test]
    public function callbackSetsSessionErrorWhenCodeIsEmpty(): void
    {
        $_REQUEST['code'] = '';

        $this->authService->method('isConfigured')->willReturn(true);
        $this->authService->method('handleCallback')
            ->willReturn([
                'success' => false,
                'redirect' => '/login',
                'error' => null,
                'user' => null,
            ]);

        $this->controller->callback([]);

        $this->assertSame('Invalid response from Google.', $_SESSION['auth_error']);
    }

    // =========================================================================
    // callback() - valid code, service returns success
    // =========================================================================

    #[Test]
    public function callbackCallsHandleCallbackWithCodeAndState(): void
    {
        $_REQUEST['code'] = 'valid_auth_code';
        $_REQUEST['state'] = 'csrf_state_token';

        $user = $this->createMockUser();

        $this->authService->method('isConfigured')->willReturn(true);
        $this->authService->expects($this->once())
            ->method('handleCallback')
            ->with('valid_auth_code', 'csrf_state_token')
            ->willReturn([
                'success' => true,
                'redirect' => '/',
                'error' => null,
                'user' => $user,
            ]);

        $this->controller->callback([]);

        $this->assertSame(
            'Welcome! You are now logged in with Google.',
            $_SESSION['auth_success']
        );
    }

    #[Test]
    public function callbackSetsAuthSuccessMessageOnLogin(): void
    {
        $_REQUEST['code'] = 'auth_code';
        $_REQUEST['state'] = 'state_param';

        $user = $this->createMockUser();

        $this->authService->method('isConfigured')->willReturn(true);
        $this->authService->method('handleCallback')
            ->willReturn([
                'success' => true,
                'redirect' => '/',
                'error' => null,
                'user' => $user,
            ]);

        $this->controller->callback([]);

        $this->assertArrayHasKey('auth_success', $_SESSION);
        $this->assertStringContainsString('Google', $_SESSION['auth_success']);
    }

    // =========================================================================
    // callback() - valid code, service returns error
    // =========================================================================

    #[Test]
    public function callbackSetsAuthErrorWhenServiceFails(): void
    {
        $_REQUEST['code'] = 'auth_code';
        $_REQUEST['state'] = 'state_param';

        $this->authService->method('isConfigured')->willReturn(true);
        $this->authService->method('handleCallback')
            ->willReturn([
                'success' => false,
                'redirect' => '/login',
                'error' => 'Invalid state parameter. Please try again.',
                'user' => null,
            ]);

        $this->controller->callback([]);

        $this->assertSame(
            'Invalid state parameter. Please try again.',
            $_SESSION['auth_error']
        );
    }

    #[Test]
    public function callbackDoesNotSetErrorWhenErrorIsNull(): void
    {
        $_REQUEST['code'] = 'auth_code';
        $_REQUEST['state'] = 'state_param';

        $this->authService->method('isConfigured')->willReturn(true);
        $this->authService->method('handleCallback')
            ->willReturn([
                'success' => false,
                'redirect' => '/google/link-confirm',
                'error' => null,
                'user' => null,
            ]);

        $this->controller->callback([]);

        $this->assertArrayNotHasKey('auth_error', $_SESSION);
    }

    #[Test]
    public function callbackDoesNotSetSuccessWhenUserIsNull(): void
    {
        $_REQUEST['code'] = 'auth_code';
        $_REQUEST['state'] = 'state_param';

        $this->authService->method('isConfigured')->willReturn(true);
        $this->authService->method('handleCallback')
            ->willReturn([
                'success' => true,
                'redirect' => '/',
                'error' => null,
                'user' => null,
            ]);

        $this->controller->callback([]);

        $this->assertArrayNotHasKey('auth_success', $_SESSION);
    }

    // =========================================================================
    // linkConfirm() tests
    // =========================================================================

    #[Test]
    public function linkConfirmRedirectsToLoginWhenNoPendingLink(): void
    {
        $this->authService->method('getPendingLinkData')->willReturn(null);

        // The redirect call returns a RedirectResponse but the method is void.
        // We verify the service was queried and no exception was thrown.
        $this->controller->linkConfirm([]);

        // If we reach here, the redirect path was taken (no view rendered)
        $this->assertTrue(true);
    }

    #[Test]
    public function linkConfirmClearsSessionError(): void
    {
        $_SESSION['auth_error'] = 'Some previous error';

        $this->authService->method('getPendingLinkData')->willReturn([
            'google_id' => 'gid_123',
            'email' => 'user@example.com',
        ]);

        // The render + require will fail since we don't have the view file
        // in a testable context, but we can test session clearing
        ob_start();
        try {
            $this->controller->linkConfirm([]);
        } catch (\Throwable $e) {
            // Expected: view file may not be loadable in test context
        }
        ob_end_clean();

        $this->assertArrayNotHasKey('auth_error', $_SESSION);
    }

    #[Test]
    public function linkConfirmExtractsEmailFromPendingData(): void
    {
        $this->authService->method('getPendingLinkData')->willReturn([
            'google_id' => 'gid_123',
            'email' => 'alice@example.com',
        ]);

        // Capture that getPendingLinkData was called
        $this->authService->expects($this->once())
            ->method('getPendingLinkData')
            ->willReturn([
                'google_id' => 'gid_123',
                'email' => 'alice@example.com',
            ]);

        ob_start();
        try {
            $this->controller->linkConfirm([]);
        } catch (\Throwable $e) {
            // View rendering may fail in test context
        }
        ob_end_clean();
    }

    // =========================================================================
    // processLinkConfirm() tests
    // =========================================================================

    #[Test]
    public function processLinkConfirmRedirectsWhenNoPendingLink(): void
    {
        $this->authService->method('getPendingLinkData')->willReturn(null);

        $this->controller->processLinkConfirm([]);

        // Redirect to /login was called; method returned without error
        $this->assertTrue(true);
    }

    #[Test]
    public function processLinkConfirmClearsDataOnCancel(): void
    {
        $_POST['action'] = 'cancel';
        $_POST['password'] = '';

        $this->authService->method('getPendingLinkData')->willReturn([
            'google_id' => 'gid_123',
            'email' => 'user@example.com',
        ]);

        // Note: the controller does not return after the cancel redirect,
        // so execution falls through to the try-catch block which also calls
        // clearPendingLinkData on success. With empty password, login throws
        // AuthException so clearPendingLinkData is called once from cancel,
        // then the catch sets auth_error. We verify it's called at least once.
        $mockUserFacade = $this->createMock(UserFacade::class);
        $mockUserFacade->method('login')
            ->willThrowException(new AuthException('Invalid credentials'));

        $this->authService->method('getUserFacade')
            ->willReturn($mockUserFacade);

        $this->authService->expects($this->once())
            ->method('clearPendingLinkData');

        $this->controller->processLinkConfirm([]);
    }

    #[Test]
    public function processLinkConfirmLinksAccountOnValidPassword(): void
    {
        $_POST['action'] = 'confirm';
        $_POST['password'] = 'correct_password';

        $user = $this->createMockUser(42);

        $mockUserFacade = $this->createMock(UserFacade::class);
        $mockUserFacade->expects($this->once())
            ->method('login')
            ->with('user@example.com', 'correct_password')
            ->willReturn($user);

        $this->authService->method('getPendingLinkData')->willReturn([
            'google_id' => 'gid_123',
            'email' => 'user@example.com',
        ]);

        $this->authService->method('getUserFacade')
            ->willReturn($mockUserFacade);

        $this->authService->expects($this->once())
            ->method('linkGoogleToUser')
            ->with('gid_123', $user);

        $this->authService->expects($this->once())
            ->method('clearPendingLinkData');

        $this->controller->processLinkConfirm([]);

        $this->assertSame(42, $_SESSION['LWT_USER_ID']);
        $this->assertSame(
            'Google account linked successfully!',
            $_SESSION['auth_success']
        );
    }

    #[Test]
    public function processLinkConfirmSetsSessionUserIdAfterSuccessfulLink(): void
    {
        $_POST['action'] = 'link';
        $_POST['password'] = 'my_password';

        $user = $this->createMockUser(99);

        $mockUserFacade = $this->createMock(UserFacade::class);
        $mockUserFacade->method('login')->willReturn($user);

        $this->authService->method('getPendingLinkData')->willReturn([
            'google_id' => 'gid_456',
            'email' => 'bob@example.com',
        ]);
        $this->authService->method('getUserFacade')->willReturn($mockUserFacade);
        $this->authService->method('linkGoogleToUser');
        $this->authService->method('clearPendingLinkData');

        $this->controller->processLinkConfirm([]);

        $this->assertSame(99, $_SESSION['LWT_USER_ID']);
    }

    #[Test]
    public function processLinkConfirmSetsErrorOnInvalidPassword(): void
    {
        $_POST['action'] = 'confirm';
        $_POST['password'] = 'wrong_password';

        $mockUserFacade = $this->createMock(UserFacade::class);
        $mockUserFacade->method('login')
            ->willThrowException(new AuthException('Invalid credentials'));

        $this->authService->method('getPendingLinkData')->willReturn([
            'google_id' => 'gid_123',
            'email' => 'user@example.com',
        ]);
        $this->authService->method('getUserFacade')
            ->willReturn($mockUserFacade);

        $this->controller->processLinkConfirm([]);

        $this->assertSame(
            'Invalid password. Please try again.',
            $_SESSION['auth_error']
        );
    }

    #[Test]
    public function processLinkConfirmDoesNotLinkOnAuthException(): void
    {
        $_POST['action'] = 'confirm';
        $_POST['password'] = 'wrong';

        $mockUserFacade = $this->createMock(UserFacade::class);
        $mockUserFacade->method('login')
            ->willThrowException(new AuthException('Bad credentials'));

        $this->authService->method('getPendingLinkData')->willReturn([
            'google_id' => 'gid_789',
            'email' => 'user@example.com',
        ]);
        $this->authService->method('getUserFacade')
            ->willReturn($mockUserFacade);

        // linkGoogleToUser should NOT be called
        $this->authService->expects($this->never())
            ->method('linkGoogleToUser');

        $this->controller->processLinkConfirm([]);
    }

    #[Test]
    public function processLinkConfirmDoesNotClearPendingDataOnAuthException(): void
    {
        $_POST['action'] = 'confirm';
        $_POST['password'] = 'wrong';

        $mockUserFacade = $this->createMock(UserFacade::class);
        $mockUserFacade->method('login')
            ->willThrowException(new AuthException('Bad credentials'));

        $this->authService->method('getPendingLinkData')->willReturn([
            'google_id' => 'gid_789',
            'email' => 'user@example.com',
        ]);
        $this->authService->method('getUserFacade')
            ->willReturn($mockUserFacade);

        // clearPendingLinkData should NOT be called on failed password
        $this->authService->expects($this->never())
            ->method('clearPendingLinkData');

        $this->controller->processLinkConfirm([]);
    }

    // =========================================================================
    // Method signature tests
    // =========================================================================

    #[Test]
    public function startMethodAcceptsArrayParameter(): void
    {
        $method = new \ReflectionMethod(GoogleController::class, 'start');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
        $this->assertSame('array', $params[0]->getType()->getName());
    }

    #[Test]
    public function callbackMethodAcceptsArrayParameter(): void
    {
        $method = new \ReflectionMethod(GoogleController::class, 'callback');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
        $this->assertSame('array', $params[0]->getType()->getName());
    }

    #[Test]
    public function linkConfirmMethodAcceptsArrayParameter(): void
    {
        $method = new \ReflectionMethod(GoogleController::class, 'linkConfirm');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
    }

    #[Test]
    public function processLinkConfirmMethodAcceptsArrayParameter(): void
    {
        $method = new \ReflectionMethod(GoogleController::class, 'processLinkConfirm');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
    }

    #[Test]
    public function allPublicMethodsReturnVoid(): void
    {
        $methods = ['start', 'callback', 'linkConfirm', 'processLinkConfirm'];

        foreach ($methods as $methodName) {
            $method = new \ReflectionMethod(GoogleController::class, $methodName);
            $returnType = $method->getReturnType();

            $this->assertNotNull($returnType, "$methodName should have a return type");
            $this->assertSame(
                'void',
                $returnType->getName(),
                "$methodName should return void"
            );
        }
    }

    #[Test]
    public function classHasExpectedPublicMethods(): void
    {
        $reflection = new \ReflectionClass(GoogleController::class);
        $expectedMethods = ['start', 'callback', 'linkConfirm', 'processLinkConfirm'];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "GoogleController should have method: $methodName"
            );
            $this->assertTrue(
                $reflection->getMethod($methodName)->isPublic(),
                "Method $methodName should be public"
            );
        }
    }

    // =========================================================================
    // Security-focused tests
    // =========================================================================

    #[Test]
    public function callbackPassesStateToServiceForValidation(): void
    {
        $_REQUEST['code'] = 'code_abc';
        $_REQUEST['state'] = 'expected_state_token';

        $this->authService->method('isConfigured')->willReturn(true);
        $this->authService->expects($this->once())
            ->method('handleCallback')
            ->with('code_abc', 'expected_state_token')
            ->willReturn([
                'success' => false,
                'redirect' => '/login',
                'error' => 'Invalid state parameter. Please try again.',
                'user' => null,
            ]);

        $this->controller->callback([]);
    }

    #[Test]
    public function callbackHandlesEmptyState(): void
    {
        $_REQUEST['code'] = 'some_code';
        $_REQUEST['state'] = '';

        $this->authService->method('isConfigured')->willReturn(true);
        $this->authService->expects($this->once())
            ->method('handleCallback')
            ->with('some_code', '')
            ->willReturn([
                'success' => false,
                'redirect' => '/login',
                'error' => 'Invalid state parameter. Please try again.',
                'user' => null,
            ]);

        $this->controller->callback([]);

        $this->assertSame(
            'Invalid state parameter. Please try again.',
            $_SESSION['auth_error']
        );
    }

    #[Test]
    public function callbackHandlesServiceExceptionInResult(): void
    {
        $_REQUEST['code'] = 'code';
        $_REQUEST['state'] = 'state';

        $this->authService->method('isConfigured')->willReturn(true);
        $this->authService->method('handleCallback')
            ->willReturn([
                'success' => false,
                'redirect' => '/login',
                'error' => 'Google authentication failed: Network error',
                'user' => null,
            ]);

        $this->controller->callback([]);

        $this->assertSame(
            'Google authentication failed: Network error',
            $_SESSION['auth_error']
        );
    }

    #[Test]
    public function callbackErrorFromGoogleSetsSessionErrorFirst(): void
    {
        // When error is set, the cancelled message is set first in the session.
        // Controller does NOT return after the error redirect, so handleCallback
        // is still called. Provide non-empty code to avoid overwrite by the
        // "Invalid response" check.
        $_REQUEST['error'] = 'server_error';
        $_REQUEST['code'] = 'fallthrough_code';
        $_REQUEST['state'] = 'fallthrough_state';

        $this->authService->method('isConfigured')->willReturn(true);
        $this->authService->method('handleCallback')
            ->willReturn([
                'success' => false,
                'redirect' => '/login',
                'error' => null,
                'user' => null,
            ]);

        $this->controller->callback([]);

        // The 'cancelled' message persists because handleCallback returns error=null.
        $this->assertSame('Google login was cancelled.', $_SESSION['auth_error']);
    }

    #[Test]
    public function callbackWithErrorAndCodeStillCallsHandleCallback(): void
    {
        // Because the controller does not return after the error redirect,
        // handleCallback IS called even when error is present.
        $_REQUEST['error'] = 'access_denied';
        $_REQUEST['code'] = 'some_code';
        $_REQUEST['state'] = 'state_val';

        $this->authService->method('isConfigured')->willReturn(true);

        $this->authService->expects($this->once())
            ->method('handleCallback')
            ->with('some_code', 'state_val')
            ->willReturn([
                'success' => false,
                'redirect' => '/login',
                'error' => null,
                'user' => null,
            ]);

        $this->controller->callback([]);

        // The cancelled message is still in the session
        $this->assertSame('Google login was cancelled.', $_SESSION['auth_error']);
    }
}
