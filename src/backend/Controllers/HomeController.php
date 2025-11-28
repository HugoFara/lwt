<?php

/**
 * \file
 * \brief Home Controller - Dashboard and home page
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/src-php-controllers-homecontroller.html
 * @since   3.0.0
 */

namespace Lwt\Controllers;

use Lwt\Services\HomeService;

require_once __DIR__ . '/../Services/HomeService.php';

/**
 * Controller for home/dashboard page.
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class HomeController extends BaseController
{
    /**
     * Home service instance.
     *
     * @var HomeService
     */
    private HomeService $homeService;

    /**
     * Initialize controller with HomeService.
     */
    public function __construct()
    {
        parent::__construct();
        $this->homeService = new HomeService();
    }

    /**
     * Home page (replaces home.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function index(array $params): void
    {
        include __DIR__ . '/../Legacy/home.php';
    }

    /**
     * Get the HomeService instance.
     *
     * @return HomeService
     */
    public function getHomeService(): HomeService
    {
        return $this->homeService;
    }
}
