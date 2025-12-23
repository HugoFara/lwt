<?php declare(strict_types=1);
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
require_once __DIR__ . '/../Services/TranslationService.php';
require_once __DIR__ . '/../Api/V1/Response.php';
require_once __DIR__ . '/../Api/V1/Endpoints.php';
require_once __DIR__ . '/../Api/V1/ApiV1.php';
require_once __DIR__ . '/../Api/V1/Handlers/FeedHandler.php';
require_once __DIR__ . '/../Api/V1/Handlers/ImportHandler.php';
require_once __DIR__ . '/../Api/V1/Handlers/ImprovedTextHandler.php';
// LanguageApiHandler now in Modules/Language/Http/
require_once __DIR__ . '/../Api/V1/Handlers/MediaHandler.php';
require_once __DIR__ . '/../Api/V1/Handlers/ReviewHandler.php';
require_once __DIR__ . '/../Api/V1/Handlers/SettingsHandler.php';
require_once __DIR__ . '/../Api/V1/Handlers/StatisticsHandler.php';
require_once __DIR__ . '/../Api/V1/Handlers/TermHandler.php';
require_once __DIR__ . '/../Api/V1/Handlers/TextHandler.php';

use Lwt\Api\V1\ApiV1;
use Lwt\Services\TranslationService;

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
     * @var TranslationController
     */
    protected TranslationController $translationController;

    /**
     * Initialize the controller with dependencies.
     *
     * @param TranslationController|null $translationController Translation controller (optional for BC)
     */
    public function __construct(?TranslationController $translationController = null)
    {
        parent::__construct();
        $this->translationController = $translationController ?? new TranslationController(new TranslationService());
    }

    /**
     * Get the translation controller.
     *
     * @return TranslationController
     */
    protected function getTranslationController(): TranslationController
    {
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
