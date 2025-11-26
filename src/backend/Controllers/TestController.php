<?php

/**
 * \file
 * \brief Test Controller - Word testing/review interface
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/src-php-controllers-testcontroller.html
 * @since   3.0.0
 */

namespace Lwt\Controllers;

/**
 * Controller for word testing/review interface.
 *
 * Handles:
 * - Test index (main testing interface)
 * - Test status updates
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class TestController extends BaseController
{
    /**
     * Test index page (replaces test_index.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function index(array $params): void
    {
        include __DIR__ . '/../Legacy/test_index.php';
    }

    /**
     * Set test status (replaces test_set_status.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function setStatus(array $params): void
    {
        include __DIR__ . '/../Legacy/test_set_status.php';
    }
}
