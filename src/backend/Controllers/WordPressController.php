<?php

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

/**
 * Controller for WordPress integration.
 *
 * Handles:
 * - WordPress start
 * - WordPress stop
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
     * WordPress start (replaces wordpress_start.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function start(array $params): void
    {
        include __DIR__ . '/../Legacy/wordpress_start.php';
    }

    /**
     * WordPress stop (replaces wordpress_stop.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function stop(array $params): void
    {
        include __DIR__ . '/../Legacy/wordpress_stop.php';
    }
}
