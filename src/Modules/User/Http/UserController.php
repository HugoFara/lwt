<?php

/**
 * User Controller
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\User\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\User\Http;

use Lwt\Controllers\BaseController;
use Lwt\Shared\Infrastructure\Exception\AuthException;
use Lwt\Shared\Infrastructure\Globals;
use Lwt\Modules\User\Application\UserFacade;
use Lwt\Modules\User\Infrastructure\AuthFormDataManager;
use Lwt\Shared\Infrastructure\Http\FlashMessageService;

/**
 * Controller for user authentication operations.
 *
 * Handles login, registration, and logout functionality.
 *
 * @since 3.0.0
 */
class UserController extends BaseController
{
    /**
     * User facade instance.
     *
     * @var UserFacade
     */
    private UserFacade $userFacade;

    /**
     * Flash message service.
     *
     * @var FlashMessageService
     */
    private FlashMessageService $flash;

    /**
     * Auth form data manager.
     *
     * @var AuthFormDataManager
     */
    private AuthFormDataManager $formData;

    /**
     * Create a new UserController.
     *
     * @param UserFacade|null          $userFacade User facade (optional for BC)
     * @param FlashMessageService|null $flash      Flash message service
     * @param AuthFormDataManager|null $formData   Form data manager
     */
    public function __construct(
        ?UserFacade $userFacade = null,
        ?FlashMessageService $flash = null,
        ?AuthFormDataManager $formData = null
    ) {
        parent::__construct();
        $this->userFacade = $userFacade ?? $this->createDefaultFacade();
        $this->flash = $flash ?? new FlashMessageService();
        $this->formData = $formData ?? new AuthFormDataManager();
    }

    /**
     * Create a default UserFacade instance.
     *
     * @return UserFacade
     */
    private function createDefaultFacade(): UserFacade
    {
        $repository = new \Lwt\Modules\User\Infrastructure\MySqlUserRepository();
        return new UserFacade($repository);
    }

    /**
     * Display the login form.
     *
     * GET /login
     *
     * @return void
     */
    public function loginForm(): void
    {
        // If already authenticated, redirect to home
        if (Globals::isAuthenticated()) {
            $this->redirect('/');
        }

        // Get flash error messages
        $errorMessages = $this->flash->getByTypeAndClear(FlashMessageService::TYPE_ERROR);
        $error = !empty($errorMessages) ? $errorMessages[0]['message'] : null;

        // Get persisted form data
        $username = $this->formData->getAndClearUsername();

        $this->render('Login', false);
        require __DIR__ . '/../Views/login.php';
        $this->endRender();
    }

    /**
     * Process the login form submission.
     *
     * POST /login
     *
     * @return void
     */
    public function login(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/login');
        }

        $usernameOrEmail = $this->post('username');
        $password = $this->post('password');
        $remember = $this->post('remember') === '1';

        // Basic validation
        if (empty($usernameOrEmail) || empty($password)) {
            $this->flash->error('Please enter your username/email and password');
            $this->formData->setUsername($usernameOrEmail);
            $this->redirect('/login');
        }

        try {
            $user = $this->userFacade->login($usernameOrEmail, $password);

            // Set remember me cookie if requested
            if ($remember) {
                $this->setRememberCookie($user->id()->toInt());
            }

            // Redirect to intended URL or home
            $redirectTo = $this->formData->getAndClearRedirectUrl('/');
            $this->redirect($redirectTo);
        } catch (AuthException $e) {
            $this->flash->error($e->getMessage());
            $this->formData->setUsername($usernameOrEmail);
            $this->redirect('/login');
        }
    }

    /**
     * Display the registration form.
     *
     * GET /register
     *
     * @return void
     */
    public function registerForm(): void
    {
        // Check if registration is enabled
        if (!$this->isRegistrationEnabled()) {
            $this->redirect('/login');
        }

        // If already authenticated, redirect to home
        if (Globals::isAuthenticated()) {
            $this->redirect('/');
        }

        // Get flash error messages
        $errorMessages = $this->flash->getByTypeAndClear(FlashMessageService::TYPE_ERROR);
        $error = !empty($errorMessages) ? $errorMessages[0]['message'] : null;

        // Get persisted form data
        $username = $this->formData->getAndClearUsername();
        $email = $this->formData->getAndClearEmail();

        $this->render('Register', false);
        require __DIR__ . '/../Views/register.php';
        $this->endRender();
    }

    /**
     * Process the registration form submission.
     *
     * POST /register
     *
     * @return void
     */
    public function register(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/register');
        }

        // Check if registration is enabled
        if (!$this->isRegistrationEnabled()) {
            $this->redirect('/login');
        }

        $username = $this->post('username');
        $email = $this->post('email');
        $password = $this->post('password');
        $passwordConfirm = $this->post('password_confirm');

        // Store form data for repopulation
        $this->formData->setUsername($username);
        $this->formData->setEmail($email);

        // Basic validation
        if (empty($username) || empty($email) || empty($password)) {
            $this->flash->error('Please fill in all required fields');
            $this->redirect('/register');
        }

        // Password confirmation
        if ($password !== $passwordConfirm) {
            $this->flash->error('Passwords do not match');
            $this->redirect('/register');
        }

        try {
            $user = $this->userFacade->register($username, $email, $password);

            // Auto-login after registration
            $this->userFacade->setCurrentUser($user);

            // Clear stored form data
            $this->formData->clearUsername();
            $this->formData->clearEmail();

            // Redirect to home with success message
            $this->flash->success('Account created successfully. Welcome to LWT!');
            $this->redirect('/');
        } catch (\InvalidArgumentException $e) {
            $this->flash->error($e->getMessage());
            $this->redirect('/register');
        } catch (\RuntimeException $e) {
            $this->flash->error('Registration failed. Please try again.');
            $this->redirect('/register');
        }
    }

    /**
     * Log out the current user.
     *
     * GET /logout
     *
     * @return void
     */
    public function logout(): void
    {
        // Invalidate and clear remember me cookie
        $currentUser = $this->userFacade->getCurrentUser();
        if ($currentUser !== null) {
            $this->userFacade->invalidateRememberToken($currentUser->id()->toInt());
        }
        $this->clearRememberCookie();

        // Logout via user facade
        $this->userFacade->logout();

        // Redirect to login
        $this->redirect('/login');
    }

    // =========================================================================
    // Password Reset Methods
    // =========================================================================

    /**
     * Display the forgot password form.
     *
     * GET /password/forgot
     *
     * @return void
     */
    public function forgotPasswordForm(): void
    {
        // If already authenticated, redirect to home
        if (Globals::isAuthenticated()) {
            $this->redirect('/');
        }

        // Get flash messages
        $errorMessages = $this->flash->getByTypeAndClear(FlashMessageService::TYPE_ERROR);
        $error = !empty($errorMessages) ? $errorMessages[0]['message'] : null;

        $successMessages = $this->flash->getByTypeAndClear(FlashMessageService::TYPE_SUCCESS);
        $success = !empty($successMessages) ? $successMessages[0]['message'] : null;

        // Get persisted form data
        $email = $this->formData->getAndClearPasswordEmail();

        $this->render('Forgot Password', false);
        require __DIR__ . '/../Views/forgot_password.php';
        $this->endRender();
    }

    /**
     * Process the forgot password form submission.
     *
     * POST /password/forgot
     *
     * @return void
     */
    public function forgotPassword(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/password/forgot');
        }

        $email = $this->post('email');

        if (empty($email)) {
            $this->flash->error('Please enter your email address');
            $this->redirect('/password/forgot');
        }

        // Always show success message (prevents email enumeration)
        $this->userFacade->requestPasswordReset($email);

        $this->flash->success('If an account exists with that email, you will receive a password reset link shortly.');
        $this->redirect('/password/forgot');
    }

    /**
     * Display the reset password form.
     *
     * GET /password/reset?token=xxx
     *
     * @return void
     */
    public function resetPasswordForm(): void
    {
        // If already authenticated, redirect to home
        if (Globals::isAuthenticated()) {
            $this->redirect('/');
        }

        $token = $this->get('token');

        if (empty($token)) {
            $this->flash->error('Invalid or missing reset token');
            $this->redirect('/password/forgot');
        }

        // Validate token before showing form
        if (!$this->userFacade->validatePasswordResetToken($token)) {
            $this->flash->error('This password reset link has expired or is invalid. Please request a new one.');
            $this->redirect('/password/forgot');
        }

        // Get flash error messages
        $errorMessages = $this->flash->getByTypeAndClear(FlashMessageService::TYPE_ERROR);
        $error = !empty($errorMessages) ? $errorMessages[0]['message'] : null;

        $this->render('Reset Password', false);
        require __DIR__ . '/../Views/reset_password.php';
        $this->endRender();
    }

    /**
     * Process the reset password form submission.
     *
     * POST /password/reset
     *
     * @return void
     */
    public function resetPassword(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/password/forgot');
        }

        $token = $this->post('token');
        $password = $this->post('password');
        $passwordConfirm = $this->post('password_confirm');

        if (empty($token)) {
            $this->flash->error('Invalid reset token');
            $this->redirect('/password/forgot');
        }

        if (empty($password)) {
            $this->flash->error('Please enter a new password');
            $this->redirect('/password/reset?token=' . urlencode($token));
        }

        if ($password !== $passwordConfirm) {
            $this->flash->error('Passwords do not match');
            $this->redirect('/password/reset?token=' . urlencode($token));
        }

        try {
            $success = $this->userFacade->completePasswordReset($token, $password);

            if ($success) {
                $this->flash->success(
                    'Your password has been reset successfully. Please log in with your new password.'
                );
                $this->redirect('/login');
            } else {
                $this->flash->error('This password reset link has expired or is invalid. Please request a new one.');
                $this->redirect('/password/forgot');
            }
        } catch (\InvalidArgumentException $e) {
            $this->flash->error($e->getMessage());
            $this->redirect('/password/reset?token=' . urlencode($token));
        }
    }

    /**
     * Try to restore session from remember-me cookie.
     *
     * This method is called during session bootstrap to check if
     * the user has a valid remember-me cookie and restore their session.
     *
     * @return bool True if session was restored, false otherwise
     */
    public function tryRestoreFromRememberCookie(): bool
    {
        // Check if already authenticated
        if (Globals::isAuthenticated()) {
            return true;
        }

        // Check for remember cookie
        $token = $_COOKIE['lwt_remember'] ?? '';
        if (empty($token)) {
            return false;
        }

        // Validate token and get user
        $user = $this->userFacade->validateRememberToken($token);
        if ($user === null) {
            // Invalid/expired token - clear the cookie
            $this->clearRememberCookie();
            return false;
        }

        // Restore the session
        $this->userFacade->setCurrentUser($user);

        // Regenerate session ID for security
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        // Optionally refresh the token and cookie to extend the session
        $this->setRememberCookie($user->id()->toInt());

        return true;
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Check if user registration is enabled.
     *
     * @return bool
     */
    private function isRegistrationEnabled(): bool
    {
        // For now, always enabled. Can be configured via settings later.
        return true;
    }

    /**
     * Set a "remember me" cookie with persistent token storage.
     *
     * The token is stored in the database and set as a cookie.
     * When the user returns, the token can be validated to restore the session.
     *
     * @param int $userId The user ID
     *
     * @return void
     */
    private function setRememberCookie(int $userId): void
    {
        $days = 30;
        $expires = time() + ($days * 24 * 60 * 60);

        // Generate and store token in database
        $token = $this->userFacade->setRememberToken($userId, $days);

        // Set cookie with secure flags
        setcookie(
            'lwt_remember',
            $token,
            [
                'expires' => $expires,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }

    /**
     * Clear the "remember me" cookie.
     *
     * @return void
     */
    private function clearRememberCookie(): void
    {
        setcookie(
            'lwt_remember',
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }
}
