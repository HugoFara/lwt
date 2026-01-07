<?php declare(strict_types=1);
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

namespace Lwt\Modules\User\Http;

use Lwt\Controllers\BaseController;
use Lwt\Core\Exception\AuthException;
use Lwt\Core\Globals;
use Lwt\Modules\User\Application\UserFacade;

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
     * Create a new UserController.
     *
     * @param UserFacade|null $userFacade User facade (optional for BC)
     */
    public function __construct(?UserFacade $userFacade = null)
    {
        parent::__construct();
        $this->userFacade = $userFacade ?? $this->createDefaultFacade();
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

        $error = null;
        $username = '';

        // Check for flash message
        if (isset($_SESSION['auth_error'])) {
            $error = $_SESSION['auth_error'];
            unset($_SESSION['auth_error']);
        }
        if (isset($_SESSION['auth_username'])) {
            $username = $_SESSION['auth_username'];
            unset($_SESSION['auth_username']);
        }

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
            $this->setFlashError('Please enter your username/email and password');
            $_SESSION['auth_username'] = $usernameOrEmail;
            $this->redirect('/login');
        }

        try {
            $user = $this->userFacade->login($usernameOrEmail, $password);

            // Set remember me cookie if requested
            if ($remember) {
                $this->setRememberCookie($user->id()->toInt());
            }

            // Redirect to intended URL or home
            $redirectTo = $_SESSION['auth_redirect'] ?? '/';
            unset($_SESSION['auth_redirect']);
            $this->redirect($redirectTo);
        } catch (AuthException $e) {
            $this->setFlashError($e->getMessage());
            $_SESSION['auth_username'] = $usernameOrEmail;
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

        $error = null;
        $username = '';
        $email = '';

        // Check for flash messages
        if (isset($_SESSION['auth_error'])) {
            $error = $_SESSION['auth_error'];
            unset($_SESSION['auth_error']);
        }
        if (isset($_SESSION['auth_username'])) {
            $username = $_SESSION['auth_username'];
            unset($_SESSION['auth_username']);
        }
        if (isset($_SESSION['auth_email'])) {
            $email = $_SESSION['auth_email'];
            unset($_SESSION['auth_email']);
        }

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
        $_SESSION['auth_username'] = $username;
        $_SESSION['auth_email'] = $email;

        // Basic validation
        if (empty($username) || empty($email) || empty($password)) {
            $this->setFlashError('Please fill in all required fields');
            $this->redirect('/register');
        }

        // Password confirmation
        if ($password !== $passwordConfirm) {
            $this->setFlashError('Passwords do not match');
            $this->redirect('/register');
        }

        try {
            $user = $this->userFacade->register($username, $email, $password);

            // Auto-login after registration
            $this->userFacade->setCurrentUser($user);

            // Clear stored form data
            unset($_SESSION['auth_username'], $_SESSION['auth_email']);

            // Redirect to home with success message
            $_SESSION['auth_success'] = 'Account created successfully. Welcome to LWT!';
            $this->redirect('/');
        } catch (\InvalidArgumentException $e) {
            $this->setFlashError($e->getMessage());
            $this->redirect('/register');
        } catch (\RuntimeException $e) {
            $this->setFlashError('Registration failed. Please try again.');
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

        $error = null;
        $success = null;
        $email = '';

        if (isset($_SESSION['password_error'])) {
            $error = $_SESSION['password_error'];
            unset($_SESSION['password_error']);
        }
        if (isset($_SESSION['password_success'])) {
            $success = $_SESSION['password_success'];
            unset($_SESSION['password_success']);
        }
        if (isset($_SESSION['password_email'])) {
            $email = $_SESSION['password_email'];
            unset($_SESSION['password_email']);
        }

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
            $_SESSION['password_error'] = 'Please enter your email address';
            $this->redirect('/password/forgot');
        }

        // Always show success message (prevents email enumeration)
        $this->userFacade->requestPasswordReset($email);

        $_SESSION['password_success'] = 'If an account exists with that email, you will receive a password reset link shortly.';
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
        $error = null;

        if (empty($token)) {
            $_SESSION['password_error'] = 'Invalid or missing reset token';
            $this->redirect('/password/forgot');
        }

        // Validate token before showing form
        if (!$this->userFacade->validatePasswordResetToken($token)) {
            $_SESSION['password_error'] = 'This password reset link has expired or is invalid. Please request a new one.';
            $this->redirect('/password/forgot');
        }

        if (isset($_SESSION['password_error'])) {
            $error = $_SESSION['password_error'];
            unset($_SESSION['password_error']);
        }

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
            $_SESSION['password_error'] = 'Invalid reset token';
            $this->redirect('/password/forgot');
        }

        if (empty($password)) {
            $_SESSION['password_error'] = 'Please enter a new password';
            $this->redirect('/password/reset?token=' . urlencode($token));
        }

        if ($password !== $passwordConfirm) {
            $_SESSION['password_error'] = 'Passwords do not match';
            $this->redirect('/password/reset?token=' . urlencode($token));
        }

        try {
            $success = $this->userFacade->completePasswordReset($token, $password);

            if ($success) {
                $_SESSION['auth_success'] = 'Your password has been reset successfully. Please log in with your new password.';
                $this->redirect('/login');
            } else {
                $_SESSION['password_error'] = 'This password reset link has expired or is invalid. Please request a new one.';
                $this->redirect('/password/forgot');
            }
        } catch (\InvalidArgumentException $e) {
            $_SESSION['password_error'] = $e->getMessage();
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
     * Set a flash error message.
     *
     * @param string $message The error message
     *
     * @return void
     */
    private function setFlashError(string $message): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['auth_error'] = $message;
    }

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
