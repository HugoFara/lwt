<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Home\Http;

use Lwt\Modules\Home\Http\HomeController;
use Lwt\Modules\Language\Application\LanguageFacade;

/**
 * Testable subclass that captures the index() logic without rendering.
 *
 * Overrides index() to call the same facade methods and data processing
 * as the real controller, but stores results instead of rendering HTML.
 * The view include and PageLayoutHelper calls (which use flush() and
 * header()) are skipped to avoid output corruption across tests.
 */
class TestableHomeController extends HomeController
{
    /** @var array|null Dashboard data from the last index() call */
    public ?array $capturedDashboardData = null;

    /** @var array|null Languages from the last index() call */
    public ?array $capturedLanguages = null;

    /** @var array|null lastTextInfo computed in the last index() call */
    public ?array $capturedLastTextInfo = null;

    /** @var bool Whether the text stats branch was entered */
    public bool $textStatsBranchEntered = false;

    /**
     * Override index() to capture logic without rendering.
     *
     * Replicates the controller's data-processing logic (lines 58-97)
     * but skips the PageLayoutHelper + view include (lines 99-107).
     */
    public function index(array $params): void
    {
        $dashboardData = $this->getHomeFacade()->getDashboardData();
        $this->capturedDashboardData = $dashboardData;

        // Access languageFacade via reflection since it's private
        $ref = new \ReflectionProperty(HomeController::class, 'languageFacade');
        $ref->setAccessible(true);
        /** @var LanguageFacade $langFacade */
        $langFacade = $ref->getValue($this);
        $this->capturedLanguages = $langFacade->getLanguagesForSelect();

        // Replicate the text statistics logic from the real controller
        $currenttext = $dashboardData['current_text_id'] ?? null;
        $currentTextInfo = $dashboardData['current_text_info'] ?? null;
        $lastTextInfo = null;

        if ($currentTextInfo !== null && $currenttext !== null) {
            $this->textStatsBranchEntered = true;
            // In the real controller this creates TextStatisticsService
            // and queries DB. We just mark the branch as entered.
        }

        $this->capturedLastTextInfo = $lastTextInfo;
    }
}
