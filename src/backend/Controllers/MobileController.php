<?php

/**
 * \file
 * \brief Mobile Controller - Mobile interface
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/src-php-controllers-mobilecontroller.html
 * @since   3.0.0
 */

namespace Lwt\Controllers;

/**
 * Controller for mobile interface.
 *
 * Handles:
 * - Mobile index page
 * - Mobile start page
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class MobileController extends BaseController
{
    /**
     * Mobile index page (replaces mobile_index.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function index(array $params): void
    {
        include __DIR__ . '/../Legacy/mobile_index.php';
    }

    /**
     * Mobile start page (replaces mobile_start.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function start(array $params): void
    {
        include __DIR__ . '/../Legacy/mobile_start.php';
    }
}
