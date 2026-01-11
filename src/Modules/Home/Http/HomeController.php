<?php

/**
 * Home Controller - Dashboard and home page
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Home\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Home\Http;

use Lwt\Controllers\BaseController;
use Lwt\Modules\Home\Application\HomeFacade;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

/**
 * Controller for home/dashboard page.
 *
 * @since 3.0.0
 */
class HomeController extends BaseController
{
    private HomeFacade $homeFacade;
    private LanguageFacade $languageFacade;

    /**
     * Create a new HomeController.
     *
     * @param HomeFacade     $homeFacade     Home facade for dashboard data
     * @param LanguageFacade $languageFacade Language facade for language operations
     */
    public function __construct(HomeFacade $homeFacade, LanguageFacade $languageFacade)
    {
        parent::__construct();
        $this->homeFacade = $homeFacade;
        $this->languageFacade = $languageFacade;
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
        $dashboardData = $this->homeFacade->getDashboardData();

        /** @psalm-suppress UnusedVariable - Used by included view */
        $homeFacade = $this->homeFacade;

        /** @psalm-suppress UnusedVariable - Used by included view */
        $languages = $this->languageFacade->getLanguagesForSelect();

        PageLayoutHelper::renderPageStartNobody("Home");
        echo PageLayoutHelper::buildLogo();
        echo '<h1>Learning With Texts (LWT)</h1>
        <h2>Home</h2>';

        include __DIR__ . '/../Views/index.php';

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Get the HomeFacade instance.
     *
     * @return HomeFacade
     */
    public function getHomeFacade(): HomeFacade
    {
        return $this->homeFacade;
    }
}
