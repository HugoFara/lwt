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
require_once __DIR__ . '/../Api/V1/Response.php';
require_once __DIR__ . '/../Api/V1/Endpoints.php';
require_once __DIR__ . '/../Api/V1/ApiV1.php';
require_once __DIR__ . '/../Api/V1/Handlers/FeedHandler.php';
require_once __DIR__ . '/../Api/V1/Handlers/ImportHandler.php';
require_once __DIR__ . '/../Api/V1/Handlers/ImprovedTextHandler.php';
require_once __DIR__ . '/../Api/V1/Handlers/LanguageHandler.php';
require_once __DIR__ . '/../Api/V1/Handlers/MediaHandler.php';
require_once __DIR__ . '/../Api/V1/Handlers/ReviewHandler.php';
require_once __DIR__ . '/../Api/V1/Handlers/SettingsHandler.php';
require_once __DIR__ . '/../Api/V1/Handlers/StatisticsHandler.php';
require_once __DIR__ . '/../Api/V1/Handlers/TermHandler.php';
require_once __DIR__ . '/../Api/V1/Handlers/TextHandler.php';

use Lwt\Api\V1\ApiV1;

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
     * Main API v1 endpoint.
     *
     * Uses the new ApiV1 handler class for clean separation of concerns.
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function v1(array $params): void
    {
        ApiV1::handleRequest();
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
