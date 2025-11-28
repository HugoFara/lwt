<?php

/**
 * \file
 * \brief Feeds Controller - RSS feed management
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/src-php-controllers-feedscontroller.html
 * @since   3.0.0
 */

namespace Lwt\Controllers;

use Lwt\Services\FeedService;

/**
 * Controller for RSS feed management.
 *
 * Handles:
 * - Feed listing and browsing
 * - Feed editing (add/edit/delete)
 * - Feed import wizard
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class FeedsController extends BaseController
{
    /**
     * @var FeedService Feed service instance
     */
    private FeedService $feedService;

    /**
     * Constructor - initialize feed service.
     */
    public function __construct()
    {
        $this->feedService = new FeedService();
    }

    /**
     * Feeds index page (replaces feeds_index.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function index(array $params): void
    {
        require_once __DIR__ . '/../Legacy/feeds_index.php';
        \Lwt\Interface\Do_Feeds\do_page($this->feedService);
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
        require_once __DIR__ . '/../Legacy/feeds_edit.php';
        \Lwt\Interface\Edit_Feeds\doPage($this->feedService);
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
        require_once __DIR__ . '/../Legacy/feeds_wizard.php';
        \Lwt\Interface\Feed_Wizard\doPage($this->feedService);
    }
}
