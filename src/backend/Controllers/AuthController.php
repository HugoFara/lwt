<?php declare(strict_types=1);
/**
 * Authentication Controller
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Controllers
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Controllers;

use Lwt\Core\Exception\AuthException;
use Lwt\Core\Globals;
use Lwt\Services\AuthService;
use Lwt\Services\PasswordService;

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Services/AuthService.php';
require_once __DIR__ . '/../Services/PasswordService.php';
require_once __DIR__ . '/../Core/Exception/AuthException.php';

/**
 * Controller for authentication operations.
 *
 * Handles login, registration, and logout functionality.
 *
 * @category Lwt
 * @package  Lwt\Controllers
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class AuthController extends BaseController
{
    /**
     * Auth service instance.
     *
     * @var AuthService
     */
    private AuthService $authService;

    /**
     * Create a new AuthController.
     *
     * @param AuthService $authService Auth service for authentication operations
     */
    public function __construct(AuthService $authService)
    {
        parent::__construct();
        $this->authService = $authService;
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
        require __DIR__ . '/../Views/Auth/login.php';
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
            $user = $this->authService->login($usernameOrEmail, $password);

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
        require __DIR__ . '/../Views/Auth/register.php';
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
            $user = $this->authService->register($username, $email, $password);

            // Auto-login after registration
            $this->authService->setCurrentUser($user);

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
        // Clear remember me cookie
        $this->clearRememberCookie();

        // Logout via auth service
        $this->authService->logout();

        // Redirect to login
        $this->redirect('/login');
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
     * Set a "remember me" cookie.
     *
     * @param int $userId The user ID
     *
     * @return void
     */
    private function setRememberCookie(int $userId): void
    {
        $passwordService = new PasswordService();
        $token = $passwordService->generateToken(32);
        $expires = time() + (30 * 24 * 60 * 60); // 30 days

        // Store token in database (would need a remember_tokens table)
        // For now, we'll skip persistent remember me functionality
        // TODO: Implement persistent remember me with token storage

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
