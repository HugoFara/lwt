<?php

declare(strict_types=1);

/**
 * Google OAuth Controller
 *
 * Controller for Google OAuth integration endpoints.
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
use Lwt\Modules\User\Application\Services\GoogleAuthService;
use Lwt\Shared\UI\Helpers\FormHelper;

/**
 * Controller for Google OAuth integration.
 *
 * Handles:
 * - OAuth start (redirect to Google)
 * - OAuth callback (handle Google response)
 * - Account linking confirmation
 *
 * @since 3.0.0
 */
class GoogleController extends BaseController
{
    /**
     * @var GoogleAuthService Google auth service instance
     */
    protected GoogleAuthService $googleAuthService;

    /**
     * Create a new GoogleController.
     *
     * @param GoogleAuthService $googleAuthService Google auth service
     */
    public function __construct(GoogleAuthService $googleAuthService)
    {
        parent::__construct();
        $this->googleAuthService = $googleAuthService;
    }

    /**
     * Start Google OAuth flow.
     *
     * GET /google/start
     *
     * @param array<string, mixed> $params Route parameters
     *
     * @return void
     */
    public function start(array $params): void
    {
        if (!$this->googleAuthService->isConfigured()) {
            throw new AuthException('Google OAuth is not configured.');
        }

        $linkMode = $this->param('link') === '1';
        $authUrl = $this->googleAuthService->getAuthorizationUrl($linkMode);

        $this->redirect($authUrl);
    }

    /**
     * Handle Google OAuth callback.
     *
     * GET /google/callback
     *
     * @param array<string, mixed> $params Route parameters
     *
     * @return void
     */
    public function callback(array $params): void
    {
        if (!$this->googleAuthService->isConfigured()) {
            throw new AuthException('Google OAuth is not configured.');
        }

        $code = $this->param('code');
        $state = $this->param('state');
        $error = $this->param('error');

        // Handle user cancellation or errors from Google
        if (!empty($error)) {
            $_SESSION['auth_error'] = 'Google login was cancelled.';
            $this->redirect('/login');
        }

        if (empty($code)) {
            $_SESSION['auth_error'] = 'Invalid response from Google.';
            $this->redirect('/login');
        }

        $result = $this->googleAuthService->handleCallback($code, $state);

        if (!$result['success'] && $result['error'] !== null) {
            $_SESSION['auth_error'] = $result['error'];
        }

        if ($result['success'] && $result['user'] !== null) {
            // Session already regenerated in service
            $_SESSION['auth_success'] = 'Welcome! You are now logged in with Google.';
        }

        $this->redirect($result['redirect']);
    }

    /**
     * Show account linking confirmation page.
     *
     * GET /google/link-confirm
     *
     * @param array<string, mixed> $params Route parameters
     *
     * @return void
     */
    public function linkConfirm(array $params): void
    {
        $pendingLink = $this->googleAuthService->getPendingLinkData();

        if ($pendingLink === null) {
            $this->redirect('/login');
        }

        $email = $pendingLink['email'];

        /** @var mixed $sessionError */
        $sessionError = $_SESSION['auth_error'] ?? null;
        $error = is_string($sessionError) ? $sessionError : null;
        unset($_SESSION['auth_error']);

        $this->render('Link Google Account', false);
        require __DIR__ . '/../Views/google_link_confirm.php';
        $this->endRender();
    }

    /**
     * Process account linking confirmation.
     *
     * POST /google/link-confirm
     *
     * @param array<string, mixed> $params Route parameters
     *
     * @return void
     */
    public function processLinkConfirm(array $params): void
    {
        $pendingLink = $this->googleAuthService->getPendingLinkData();

        if ($pendingLink === null) {
            $this->redirect('/login');
        }

        $password = $this->post('password');
        $action = $this->post('action');

        if ($action === 'cancel') {
            $this->googleAuthService->clearPendingLinkData();
            $this->redirect('/login');
        }

        // Verify password and link accounts
        try {
            $user = $this->googleAuthService->getUserFacade()
                ->login($pendingLink['email'], $password);

            $this->googleAuthService->linkGoogleToUser($pendingLink['google_id'], $user);
            $this->googleAuthService->clearPendingLinkData();

            // Set up session
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            $_SESSION['LWT_USER_ID'] = $user->id()->toInt();

            $_SESSION['auth_success'] = 'Google account linked successfully!';
            $this->redirect('/');
        } catch (AuthException $e) {
            $_SESSION['auth_error'] = 'Invalid password. Please try again.';
            $this->redirect('/google/link-confirm');
        }
    }
}
