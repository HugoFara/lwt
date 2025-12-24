<?php declare(strict_types=1);
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
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\View\Helper\PageLayoutHelper;
use Lwt\View\Helper\SelectOptionsBuilder;

require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../View/Helper/PageLayoutHelper.php';
require_once __DIR__ . '/../View/Helper/SelectOptionsBuilder.php';
require_once __DIR__ . '/../Services/TextStatisticsService.php';
require_once __DIR__ . '/../Services/SentenceService.php';
require_once __DIR__ . '/../Services/AnnotationService.php';
require_once __DIR__ . '/../Services/TextNavigationService.php';
require_once __DIR__ . '/../Services/TextParsingService.php';
require_once __DIR__ . '/../../Modules/Vocabulary/Application/UseCases/FindSimilarTerms.php';
require_once __DIR__ . '/../../Modules/Vocabulary/Application/Services/ExpressionService.php';
require_once __DIR__ . '/../Core/Database/Restore.php';
require_once __DIR__ . '/../Services/MediaService.php';
// LanguageFacade and LanguagePresets loaded via autoloader
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
     * Language facade instance.
     *
     * @var LanguageFacade
     */
    private LanguageFacade $languageService;

    /**
     * Create a new HomeController.
     *
     * @param HomeService    $homeService     Home service for dashboard data
     * @param LanguageFacade $languageService Language facade for language operations
     */
    public function __construct(HomeService $homeService, LanguageFacade $languageService)
    {
        parent::__construct();
        $this->homeService = $homeService;
        $this->languageService = $languageService;
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

        PageLayoutHelper::renderPageStartNobody("Home");
        echo PageLayoutHelper::buildLogo();
        echo '<h1>Learning With Texts (LWT)</h1>
        <h2>Home</h2>';

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
