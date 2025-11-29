<?php

/**
 * \file
 * \brief Home Controller - Dashboard and home page
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/src-php-controllers-homecontroller.html
 * @since   3.0.0
 */

namespace Lwt\Controllers;

use Lwt\Services\HomeService;
use Lwt\Services\LanguageService;
use Lwt\View\Helper\PageLayoutHelper;
use Lwt\View\Helper\SelectOptionsBuilder;

require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../View/Helper/PageLayoutHelper.php';
require_once __DIR__ . '/../View/Helper/SelectOptionsBuilder.php';
require_once __DIR__ . '/../Services/TextStatisticsService.php';
require_once __DIR__ . '/../Services/SentenceService.php';
require_once __DIR__ . '/../Services/AnnotationService.php';
require_once __DIR__ . '/../Services/SimilarTermsService.php';
require_once __DIR__ . '/../Services/TextNavigationService.php';
require_once __DIR__ . '/../Services/TextParsingService.php';
require_once __DIR__ . '/../Services/ExpressionService.php';
require_once __DIR__ . '/../Core/Database/Restore.php';
require_once __DIR__ . '/../Services/MediaService.php';
require_once __DIR__ . '/../Services/LanguageService.php';
require_once __DIR__ . '/../Services/LanguageDefinitions.php';
require_once __DIR__ . '/../Services/HomeService.php';

/**
 * Controller for home/dashboard page.
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class HomeController extends BaseController
{
    /**
     * Home service instance.
     *
     * @var HomeService
     */
    private HomeService $homeService;

    /**
     * Language service instance.
     *
     * @var LanguageService
     */
    private LanguageService $languageService;

    /**
     * Initialize controller with HomeService.
     */
    public function __construct()
    {
        parent::__construct();
        $this->homeService = new HomeService();
        $this->languageService = new LanguageService();
    }

    /**
     * Home page (replaces home.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function index(array $params): void
    {
        /** @psalm-suppress UnusedVariable - Used by included view */
        $dashboardData = $this->homeService->getDashboardData();
        /** @psalm-suppress UnusedVariable - Used by included view */
        $homeService = $this->homeService;
        /** @psalm-suppress UnusedVariable - Used by included view */
        $languages = $this->languageService->getLanguagesForSelect();

        $debug = $dashboardData['is_debug'];

        PageLayoutHelper::renderPageStartNobody(
            "Home",
            "
            body {
                max-width: 1920px;
                margin: 20px;
            }"
        );
        echo PageLayoutHelper::buildLogo();
        echo '<h1>Learning With Texts (LWT)</h1>
        <h2>Home' . ($debug ? ' <span class="red">DEBUG</span>' : '') . '</h2>';

        include __DIR__ . '/../Views/Home/index.php';

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Get the HomeService instance.
     *
     * @return HomeService
     */
    public function getHomeService(): HomeService
    {
        return $this->homeService;
    }
}
