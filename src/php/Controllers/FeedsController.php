<?php

/**
 * \file
 * \brief Feeds Controller - RSS feed management
 *
 * PHP version 8.1
 *
 * @package Lwt
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/src-php-controllers-feedscontroller.html
 * @since   3.0.0
 */

namespace Lwt\Controllers;

/**
 * Controller for RSS feed management.
 *
 * Handles:
 * - Feed listing and browsing
 * - Feed editing (add/edit/delete)
 * - Feed import wizard
 */
class FeedsController extends BaseController
{
    /**
     * Feeds index page (replaces feeds_index.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function index(array $params): void
    {
        include __DIR__ . '/../Legacy/feeds_index.php';
    }

    /**
     * Edit feeds page (replaces feeds_edit.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function edit(array $params): void
    {
        include __DIR__ . '/../Legacy/feeds_edit.php';
    }

    /**
     * Feed wizard page (replaces feeds_wizard.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function wizard(array $params): void
    {
        include __DIR__ . '/../Legacy/feeds_wizard.php';
    }
}
