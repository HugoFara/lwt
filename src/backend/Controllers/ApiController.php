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

declare(strict_types=1);

namespace Lwt\Controllers;

require_once __DIR__ . '/../Api/V1/Response.php';
require_once __DIR__ . '/../Api/V1/Endpoints.php';
require_once __DIR__ . '/../Api/V1/ApiV1.php';
require_once __DIR__ . '/../Router/Middleware/RateLimitMiddleware.php';
// API handlers now in Modules (loaded via autoloader)

use Lwt\Api\V1\ApiV1;
use Lwt\Router\Middleware\RateLimitMiddleware;

/**
 * Controller for REST API endpoints.
 *
 * Handles:
 * - Main REST API (v1)
 *
 * Note: Translation APIs (/api/translate, /api/google, /api/glosbe) are now
 * handled directly by Lwt\Modules\Dictionary\Http\TranslationController.
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
     * Initialize the controller.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Main API v1 endpoint.
     *
     * Uses the new ApiV1 handler class for clean separation of concerns.
     * Applies rate limiting before processing the request.
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function v1(array $params): void
    {
        // Apply rate limiting before processing API request
        $rateLimiter = new RateLimitMiddleware();
        if (!$rateLimiter->handle()) {
            // Rate limit exceeded - response already sent
            return;
        }

        ApiV1::handleRequest();
    }
}
