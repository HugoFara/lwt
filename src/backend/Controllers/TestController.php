<?php declare(strict_types=1);
/**
 * \file
 * \brief Test Controller - Word testing/review interface
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/src-php-controllers-testcontroller.html
 * @since   3.0.0
 */

namespace Lwt\Controllers;

use Lwt\Core\Utils\ErrorHandler;
use Lwt\Services\TestService;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Modules\Language\Infrastructure\LanguagePresets;
use Lwt\View\Helper\PageLayoutHelper;

require_once __DIR__ . '/../Services/TestService.php';
// LanguageFacade and LanguagePresets loaded via autoloader
require_once __DIR__ . '/../Services/TextNavigationService.php';
require_once __DIR__ . '/../Services/AnnotationService.php';
require_once __DIR__ . '/../View/Helper/PageLayoutHelper.php';
require_once __DIR__ . '/../View/Helper/StatusHelper.php';
require_once __DIR__ . '/../View/Helper/FormHelper.php';
require_once __DIR__ . '/../Core/Bootstrap/start_session.php';

/**
 * Controller for word testing/review interface.
 *
 * Handles:
 * - Test index (main testing interface)
 * - Test header display
 * - Test status updates
 * - Table tests
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class TestController extends BaseController
{
    private TestService $testService;
    private LanguageFacade $languageService;

    /**
     * Create a new TestController.
     *
     * @param TestService|null    $testService     Test service (optional for BC)
     * @param LanguageFacade|null $languageService Language facade (optional for BC)
     */
    public function __construct(
        ?TestService $testService = null,
        ?LanguageFacade $languageService = null
    ) {
        parent::__construct();
        $this->testService = $testService ?? new TestService();
        $this->languageService = $languageService ?? new LanguageFacade();
    }

    /**
     * Test index page (main entry point).
     *
     * Routes to appropriate test type based on parameters.
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function index(array $params): void
    {
        $property = $this->getTestProperty();

        if ($property === '') {
            $this->redirect('/text/edit');
        }

        $this->renderTestPage();
    }

    /**
     * Render test header frame.
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

        $testData = $this->testService->getTestDataFromParams(
            $selection,
            $sessTestsql,
            $langId,
            $textId
        );

        if ($testData === null) {
            PageLayoutHelper::renderPageStart('Request Error!', true);
            ErrorHandler::die("do_test_header.php called with wrong parameters");
            return;
        }

        $languageName = $this->testService->getL2LanguageName(
            $langId,
            $textId,
            $selection,
            $sessTestsql
        );

        // Initialize session
        $this->testService->initializeTestSession($testData['counts']['due']);

        // Render header views
        include __DIR__ . '/../Views/Test/header.php';

        // Prepare variables for header content
        $title = $testData['title'];
        $property = $testData['property'];
        $totalDue = $testData['counts']['due'];
        $totalCount = $testData['counts']['total'];

        include __DIR__ . '/../Views/Test/header_content.php';
    }

    /**
     * Render table test.
     *
     * @param array $params Route parameters
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    public function tableTest(array $params): void
    {
        $langId = $this->param('lang') !== '' ? (int) $this->param('lang') : null;
        $textId = $this->param('text') !== '' ? (int) $this->param('text') : null;
        $selection = $this->param('selection') !== '' ? (int) $this->param('selection') : null;
        $sessTestsql = $_SESSION['testsql'] ?? null;

        // Get test SQL
        $identifier = $this->testService->getTestIdentifier(
            $selection,
            $sessTestsql,
            $langId,
            $textId
        );

        if ($identifier[0] === '') {
            ErrorHandler::die("do_test_table.php called with wrong parameters");
            return;
        }

        /** @psalm-suppress InvalidScalarArgument - $identifier[1] type depends on $identifier[0] */
        $testsql = $this->testService->getTestSql($identifier[0], $identifier[1]);

        // Validate single language
        $validation = $this->testService->validateTestSelection($testsql);
        if (!$validation['valid']) {
            echo '<p>Sorry - ' . $validation['error'] . '</p>';
            return;
        }

        // Get language settings
        $langIdFromSql = $this->testService->getLanguageIdFromTestSql($testsql);
        if ($langIdFromSql === null) {
            include __DIR__ . '/../Views/Test/no_terms.php';
            PageLayoutHelper::renderPageEnd();
            return;
        }

        $langSettings = $this->testService->getLanguageSettings($langIdFromSql);
        $textSize = round((($langSettings['textSize'] ?? 100) - 100) / 2, 0) + 100;

        // Render table settings
        $settings = $this->testService->getTableTestSettings();
        include __DIR__ . '/../Views/Test/table_test_settings.php';

        echo '<table class="sortable tab2 table-test" cellspacing="0" cellpadding="5">';
        include __DIR__ . '/../Views/Test/table_test_header.php';

        // Render table rows
        $words = $this->testService->getTableTestWords($testsql);
        $regexWord = $langSettings['regexWord'] ?? '';
        $rtl = $langSettings['rtl'] ?? false;

        while ($word = mysqli_fetch_assoc($words)) {
            include __DIR__ . '/../Views/Test/table_test_row.php';
        }
        mysqli_free_result($words);

        echo '</table>';
    }

    /**
     * Get test property from request parameters.
     *
     * @return string URL property string
     */
    private function getTestProperty(): string
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
     * Render the main test page.
     *
     * Modern interface with reactive state management and no iframes.
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    private function renderTestPage(): void
    {
        $langId = $this->param('lang') !== '' ? (int) $this->param('lang') : null;
        $textId = $this->param('text') !== '' ? (int) $this->param('text') : null;
        $selection = $this->param('selection') !== '' ? (int) $this->param('selection') : null;
        $sessTestsql = $_SESSION['testsql'] ?? null;
        $testTypeParam = $this->param('type', '1');
        $isTableMode = $testTypeParam === 'table';

        // Get test data
        $testData = $this->testService->getTestDataFromParams(
            $selection,
            $sessTestsql,
            $langId,
            $textId
        );

        if ($testData === null) {
            $this->redirect('/text/edit');
        }

        // Get test identifier
        $identifier = $this->testService->getTestIdentifier(
            $selection,
            $sessTestsql,
            $langId,
            $textId
        );

        if ($identifier[0] === '') {
            $this->redirect('/text/edit');
        }

        /** @psalm-suppress InvalidScalarArgument - $identifier[1] type depends on $identifier[0] */
        $testsql = $this->testService->getTestSql($identifier[0], $identifier[1]);
        $testType = $isTableMode ? 1 : $this->testService->clampTestType((int) $testTypeParam);
        $wordMode = $this->testService->isWordMode($testType);
        $baseType = $this->testService->getBaseTestType($testType);

        // Get language settings
        $langIdFromSql = $this->testService->getLanguageIdFromTestSql($testsql);
        if ($langIdFromSql === null) {
            PageLayoutHelper::renderPageStartNobody('Test', 'full-width');
            include __DIR__ . '/../Views/Test/no_terms.php';
            PageLayoutHelper::renderPageEnd();
            return;
        }

        $langSettings = $this->testService->getLanguageSettings($langIdFromSql);

        // Get language code for TTS
        $langCode = $this->languageService->getLanguageCode(
            $langIdFromSql,
            LanguagePresets::getAll()
        );

        // Initialize session
        $this->testService->initializeTestSession($testData['counts']['due']);
        $sessionData = $this->testService->getTestSessionData();

        // Build config for JavaScript
        $config = [
            'testKey' => $identifier[0],
            'selection' => is_array($identifier[1])
                ? implode(',', $identifier[1])
                : (string) $identifier[1],
            'testType' => $baseType,
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

        PageLayoutHelper::renderPageStartNobody('Test', 'full-width');
        include __DIR__ . '/../Views/Test/test_desktop.php';
        PageLayoutHelper::renderPageEnd();
    }

}
