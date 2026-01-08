<?php declare(strict_types=1);
/**
 * Review Controller
 *
 * HTTP controller for word review interface.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Review\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/src-modules-review-http-reviewcontroller.html
 * @since    3.0.0
 */

namespace Lwt\Modules\Review\Http;

use Lwt\Controllers\BaseController;
use Lwt\Core\Exception\ValidationException;
use Lwt\Modules\Review\Application\ReviewFacade;
use Lwt\Modules\Review\Domain\ReviewConfiguration;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Modules\Language\Infrastructure\LanguagePresets;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

require_once __DIR__ . '/../../../backend/Controllers/BaseController.php';
// LanguageFacade loaded via autoloader
require_once __DIR__ . '/../../Text/Application/Services/TextNavigationService.php';
require_once __DIR__ . '/../../Text/Application/Services/AnnotationService.php';
require_once __DIR__ . '/../../../Shared/UI/Helpers/PageLayoutHelper.php';
require_once __DIR__ . '/../../../backend/View/Helper/StatusHelper.php';
require_once __DIR__ . '/../../../Shared/UI/Helpers/FormHelper.php';
require_once __DIR__ . '/../../../backend/Core/Bootstrap/start_session.php';

/**
 * Controller for word review interface.
 *
 * Handles:
 * - Review index (main review interface)
 * - Review header display
 * - Review status updates
 * - Table reviews
 *
 * @category Lwt
 * @package  Lwt\Modules\Review\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class ReviewController extends BaseController
{
    private ReviewFacade $reviewFacade;
    private LanguageFacade $languageService;

    /**
     * Create a new ReviewController.
     *
     * @param ReviewFacade|null   $reviewFacade    Review facade (optional for BC)
     * @param LanguageFacade|null $languageService Language facade (optional for BC)
     */
    public function __construct(
        ?ReviewFacade $reviewFacade = null,
        ?LanguageFacade $languageService = null
    ) {
        parent::__construct();
        $this->reviewFacade = $reviewFacade ?? new ReviewFacade();
        $this->languageService = $languageService ?? new LanguageFacade();
    }

    /**
     * Review index page (main entry point).
     *
     * Routes to appropriate review type based on parameters.
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function index(array $params): void
    {
        $property = $this->getReviewProperty();

        if ($property === '') {
            $this->redirect('/text/edit');
        }

        $this->renderReviewPage();
    }

    /**
     * Render review header frame.
     *
     * @param array $params Route parameters
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    public function header(array $params): void
    {
        $langId = $this->param('lang') !== '' ? (int) $this->param('lang') : null;
        $textId = $this->param('text') !== '' ? (int) $this->param('text') : null;
        $selection = $this->param('selection') !== '' ? (int) $this->param('selection') : null;
        $sessTestsql = $_SESSION['testsql'] ?? null;

        $testData = $this->reviewFacade->getTestDataFromParams(
            $selection,
            $sessTestsql,
            $langId,
            $textId
        );

        if ($testData === null) {
            throw ValidationException::forField(
                'parameters',
                'Review header requires valid lang, text, or selection parameter'
            )->setHttpStatusCode(400);
        }

        $languageName = $this->reviewFacade->getL2LanguageName(
            $langId,
            $textId,
            $selection,
            $sessTestsql
        );

        // Initialize session
        $this->reviewFacade->initializeReviewSession($testData['counts']['due']);

        // Render header views
        include __DIR__ . '/../Views/header.php';

        // Prepare variables for header content
        $title = $testData['title'];
        $property = $testData['property'];
        $totalDue = $testData['counts']['due'];
        $totalCount = $testData['counts']['total'];

        include __DIR__ . '/../Views/header_content.php';
    }

    /**
     * Render table review.
     *
     * @param array $params Route parameters
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    public function tableReview(array $params): void
    {
        $langId = $this->param('lang') !== '' ? (int) $this->param('lang') : null;
        $textId = $this->param('text') !== '' ? (int) $this->param('text') : null;
        $selection = $this->param('selection') !== '' ? (int) $this->param('selection') : null;
        $sessTestsql = $_SESSION['testsql'] ?? null;

        // Get review SQL
        $identifier = $this->reviewFacade->getReviewIdentifier(
            $selection,
            $sessTestsql,
            $langId,
            $textId
        );

        if ($identifier[0] === '') {
            throw ValidationException::forField(
                'parameters',
                'Review table requires valid lang, text, or selection parameter'
            )->setHttpStatusCode(400);
        }

        /** @psalm-suppress InvalidScalarArgument */
        $testsql = $this->reviewFacade->getReviewSql($identifier[0], $identifier[1]);

        if ($testsql === null) {
            echo '<p>Sorry - Unable to generate review SQL</p>';
            return;
        }

        // Validate single language
        $validation = $this->reviewFacade->validateReviewSelection($testsql);
        if (!$validation['valid']) {
            echo '<p>Sorry - ' . ($validation['error'] ?? 'Unknown error') . '</p>';
            return;
        }

        // Get language settings
        $langIdFromSql = $this->reviewFacade->getLanguageIdFromTestSql($testsql);
        if ($langIdFromSql === null) {
            include __DIR__ . '/../Views/no_terms.php';
            PageLayoutHelper::renderPageEnd();
            return;
        }

        $langSettings = $this->reviewFacade->getLanguageSettings($langIdFromSql);
        $textSize = round((($langSettings['textSize'] ?? 100) - 100) / 2, 0) + 100;

        // Render table settings
        $settings = $this->reviewFacade->getTableReviewSettings();
        include __DIR__ . '/../Views/table_review_settings.php';

        echo '<table class="sortable tab2 table-test" cellspacing="0" cellpadding="5">';
        include __DIR__ . '/../Views/table_review_header.php';

        // Render table rows
        $words = $this->reviewFacade->getTableReviewWords($testsql);
        $regexWord = $langSettings['regexWord'] ?? '';
        $rtl = $langSettings['rtl'] ?? false;

        if ($words instanceof \mysqli_result) {
            while ($word = mysqli_fetch_assoc($words)) {
                include __DIR__ . '/../Views/table_review_row.php';
            }
            mysqli_free_result($words);
        }

        echo '</table>';
    }

    /**
     * Get review property from request parameters.
     *
     * @return string URL property string
     */
    private function getReviewProperty(): string
    {
        $selection = $this->param('selection');
        if ($selection !== '' && isset($_SESSION['testsql'])) {
            return "selection=" . $selection;
        }
        $lang = $this->param('lang');
        if ($lang !== '') {
            return "lang=" . $lang;
        }
        $text = $this->param('text');
        if ($text !== '') {
            return "text=" . $text;
        }
        return '';
    }

    /**
     * Render the main review page.
     *
     * Modern interface with reactive state management and no iframes.
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    private function renderReviewPage(): void
    {
        $langId = $this->param('lang') !== '' ? (int) $this->param('lang') : null;
        $textId = $this->param('text') !== '' ? (int) $this->param('text') : null;
        $selection = $this->param('selection') !== '' ? (int) $this->param('selection') : null;
        $sessTestsql = $_SESSION['testsql'] ?? null;
        $testTypeParam = $this->param('type', '1');
        $isTableMode = $testTypeParam === 'table';

        // Get review data
        $testData = $this->reviewFacade->getTestDataFromParams(
            $selection,
            $sessTestsql,
            $langId,
            $textId
        );

        if ($testData === null) {
            $this->redirect('/text/edit');
        }

        // Get review identifier
        $identifier = $this->reviewFacade->getReviewIdentifier(
            $selection,
            $sessTestsql,
            $langId,
            $textId
        );

        if ($identifier[0] === '') {
            $this->redirect('/text/edit');
        }

        /** @psalm-suppress InvalidScalarArgument */
        $testsql = $this->reviewFacade->getReviewSql($identifier[0], $identifier[1]);
        if ($testsql === null) {
            $this->redirect('/text/edit');
        }

        $testType = $isTableMode ? 1 : $this->reviewFacade->clampTestType((int) $testTypeParam);
        $wordMode = $this->reviewFacade->isWordMode($testType);
        $baseType = $this->reviewFacade->getBaseTestType($testType);

        // Get language settings
        $langIdFromSql = $this->reviewFacade->getLanguageIdFromTestSql($testsql);
        if ($langIdFromSql === null) {
            PageLayoutHelper::renderPageStartNobody('Review', 'full-width');
            include __DIR__ . '/../Views/no_terms.php';
            PageLayoutHelper::renderPageEnd();
            return;
        }

        $langSettings = $this->reviewFacade->getLanguageSettings($langIdFromSql);

        // Get language code for TTS
        $langCode = $this->languageService->getLanguageCode(
            $langIdFromSql,
            LanguagePresets::getAll()
        );

        // Initialize session
        $this->reviewFacade->initializeReviewSession($testData['counts']['due']);
        $sessionData = $this->reviewFacade->getReviewSessionData();

        // Build config for JavaScript
        $config = [
            'reviewKey' => $identifier[0],
            'selection' => is_array($identifier[1])
                ? implode(',', $identifier[1])
                : (string) $identifier[1],
            'reviewType' => $baseType,
            'isTableMode' => $isTableMode,
            'wordMode' => $wordMode,
            'langId' => $langIdFromSql,
            'wordRegex' => $langSettings['regexWord'] ?? '',
            'langSettings' => [
                'name' => $langSettings['name'] ?? '',
                'dict1Uri' => $langSettings['dict1Uri'] ?? '',
                'dict2Uri' => $langSettings['dict2Uri'] ?? '',
                'translateUri' => $langSettings['translateUri'] ?? '',
                'textSize' => $langSettings['textSize'] ?? 100,
                'rtl' => $langSettings['rtl'] ?? false,
                'langCode' => $langCode
            ],
            'progress' => [
                'total' => $testData['counts']['due'],
                'remaining' => $testData['counts']['due'],
                'wrong' => 0,
                'correct' => 0
            ],
            'timer' => [
                'startTime' => $sessionData['start'],
                'serverTime' => time()
            ],
            'title' => $testData['title'],
            'property' => $testData['property']
        ];

        PageLayoutHelper::renderPageStartNobody('Review', 'full-width');
        include __DIR__ . '/../Views/review_desktop.php';
        PageLayoutHelper::renderPageEnd();
    }
}
