<?php

/**
 * \file
 * \brief API Controller - REST API endpoints
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/src-php-controllers-apicontroller.html
 * @since   3.0.0
 */

namespace Lwt\Controllers;

/**
 * Controller for REST API endpoints.
 *
 * Handles:
 * - Main REST API (v1)
 * - Translation APIs (Google, Glosbe)
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class ApiController extends BaseController
{
    /**
     * Main API v1 endpoint (replaces api_v1.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function v1(array $params): void
    {
        include __DIR__ . '/../Legacy/api_v1.php';
    }

    /**
     * Translation endpoint (replaces api_translate.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function translate(array $params): void
    {
        include __DIR__ . '/../Legacy/api_translate.php';
    }

    /**
     * Google translate endpoint (replaces api_google.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function google(array $params): void
    {
        include __DIR__ . '/../Legacy/api_google.php';
    }

    /**
     * Glosbe API endpoint (replaces api_glosbe.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function glosbe(array $params): void
    {
        include __DIR__ . '/../Legacy/api_glosbe.php';
    }
}
