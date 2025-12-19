<?php declare(strict_types=1);
/**
 * \file
 * \brief WordPress Controller - WordPress integration
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/src-php-controllers-wordpresscontroller.html
 * @since   3.0.0
 */

namespace Lwt\Controllers;

use Lwt\Core\Utils\ErrorHandler;
use Lwt\Services\WordPressService;

/**
 * Controller for WordPress integration.
 *
 * Handles:
 * - WordPress start (login flow)
 * - WordPress stop (logout flow)
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class WordPressController extends BaseController
{
    /**
     * @var WordPressService WordPress service instance
     */
    protected WordPressService $wordPressService;

    /**
     * Create a new WordPressController.
     *
     * @param WordPressService $wordPressService WordPress service for import operations
     */
    public function __construct(WordPressService $wordPressService)
    {
        parent::__construct();
        $this->wordPressService = $wordPressService;
    }

    /**
     * Get the WordPress service instance.
     *
     * @return WordPressService
     */
    public function getWordPressService(): WordPressService
    {
        return $this->wordPressService;
    }

    /**
     * WordPress start - handle login flow (replaces wordpress_start.php)
     *
     * To start LWT with WordPress login, use this URL:
     * http://...path-to-wp-blog.../lwt/wp_lwt_start.php
     *
     * Cookies must be enabled. A session cookie will be set.
     * The LWT installation must be in sub directory "lwt" under
     * the WordPress main directory.
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function start(array $params): void
    {
        $redirectUrl = $this->param('rd');

        $result = $this->wordPressService->handleStart($redirectUrl);

        if (!$result['success'] && $result['error'] !== null) {
            require_once __DIR__ . '/../Core/Utils/error_handling.php';
            ErrorHandler::die($result['error']);
        }

        $this->redirect($result['redirect']);
    }

    /**
     * WordPress stop - handle logout flow (replaces wordpress_stop.php)
     *
     * To properly log out from both WordPress and LWT, use:
     * http://...path-to-wp-blog.../lwt/wp_lwt_stop.php
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function stop(array $params): void
    {
        $result = $this->wordPressService->handleStop();

        $this->redirect($result['redirect']);
    }
}
