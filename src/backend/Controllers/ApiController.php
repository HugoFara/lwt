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

require_once __DIR__ . '/TranslationController.php';

/**
 * Controller for REST API endpoints.
 *
 * Handles:
 * - Main REST API (v1)
 * - Translation APIs (delegated to TranslationController)
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
     * Translation controller for handling translation endpoints
     *
     * @var TranslationController|null
     */
    protected ?TranslationController $translationController = null;

    /**
     * Get or create the translation controller.
     *
     * @return TranslationController
     */
    protected function getTranslationController(): TranslationController
    {
        if ($this->translationController === null) {
            $this->translationController = new TranslationController();
        }
        return $this->translationController;
    }

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
     * Delegates to TranslationController for proper MVC handling.
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function translate(array $params): void
    {
        $this->getTranslationController()->translate($params);
    }

    /**
     * Google translate endpoint (replaces api_google.php)
     *
     * Delegates to TranslationController for proper MVC handling.
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function google(array $params): void
    {
        $this->getTranslationController()->google($params);
    }

    /**
     * Glosbe API endpoint (replaces api_glosbe.php)
     *
     * Delegates to TranslationController for proper MVC handling.
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function glosbe(array $params): void
    {
        $this->getTranslationController()->glosbe($params);
    }
}
