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
use Lwt\Services\LanguageService;
use Lwt\Services\LanguageDefinitions;
use Lwt\Services\MobileService;
use Lwt\View\Helper\PageLayoutHelper;

require_once __DIR__ . '/../Services/TestService.php';
require_once __DIR__ . '/../Services/MobileService.php';
require_once __DIR__ . '/../Services/LanguageService.php';
require_once __DIR__ . '/../Services/LanguageDefinitions.php';
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
    private MobileService $mobileService;

    public function __construct()
    {
        parent::__construct();
        $this->testService = new TestService();
        $this->mobileService = new MobileService();
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
            return;
        }

        $this->renderTestPage();
    }

    /**
     * Set test status (AJAX endpoint for status changes during tests).
     *
     * @param array $params Route parameters
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     *
     * @deprecated 3.0.0 Use PUT /api/v1/review/status instead.
     *             This endpoint is kept for backward compatibility with frame-based mode.
     *             The frontend now uses API mode by default (see setUseApiMode in text_events.ts).
     */
    public function setStatus(array $params): void
    {
        $wid = (int) $this->param('wid');
        $status = $this->param('status');
        $stchange = $this->param('stchange');
        $useAjax = $this->hasParam('ajax');

        if (!is_numeric($status) && !is_numeric($stchange)) {
            ErrorHandler::die('status or stchange should be specified!');
        }

        // Get old status
        $oldStatus = (int) $this->getValue(
            "SELECT WoStatus AS value FROM {$this->tbpref}words WHERE WoID = $wid"
        );

        // Calculate new status
        if (is_numeric($stchange)) {
            $statusChange = (int) $stchange;
            $newStatus = $this->testService->calculateNewStatus($oldStatus, $statusChange);
        } else {
            $newStatus = (int) $status;
            $statusChange = $this->testService->calculateStatusChange($oldStatus, $newStatus);
        }

        // Update status and get scores
        $result = $this->testService->updateWordStatus($wid, $newStatus);

        // Get word text
        $wordText = $this->testService->getWordText($wid) ?? '';

        // Update session progress
        $testStatus = $this->testService->updateSessionProgress($statusChange);

        // Render result - prepare variables for views
        $oldStatus = $result['oldStatus'];
        $newStatus = $result['newStatus'];
        $oldScore = $result['oldScore'];
        $newScore = $result['newScore'];

        include __DIR__ . '/../Views/Test/status_change_result.php';

        // Render status change config
        $wordId = $wid;
        $ajax = $useAjax;
        $waitTime = $this->testService->getWaitingTime();

        include __DIR__ . '/../Views/Test/status_change_config.php';

        PageLayoutHelper::renderPageEnd();
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

        $testsql = $this->testService->getTestSql($identifier[0], $identifier[1]);

        // Validate single language
        $validation = $this->testService->validateTestSelection($testsql);
        if (!$validation['valid']) {
            echo '<p>Sorry - ' . $validation['error'] . '</p>';
            exit();
        }

        // Get language settings
        $langIdFromSql = $this->testService->getLanguageIdFromTestSql($testsql);
        if ($langIdFromSql === null) {
            include __DIR__ . '/../Views/Test/no_terms.php';
            PageLayoutHelper::renderPageEnd();
            exit();
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
     * @return void
     */
    private function renderTestPage(): void
    {
        PageLayoutHelper::renderPageStartNobody('Test', 'full-width');

        if ($this->mobileService->isMobile()) {
            $this->renderMobileTestPage();
        } else {
            $this->renderDesktopTestPage();
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Render mobile test page.
     *
     * @return void
     */
    private function renderMobileTestPage(): void
    {
        $langId = $this->param('lang') !== '' ? (int) $this->param('lang') : null;
        $textId = $this->param('text') !== '' ? (int) $this->param('text') : null;
        $selection = $this->param('selection') !== '' ? (int) $this->param('selection') : null;
        $sessTestsql = $_SESSION['testsql'] ?? null;

        $language = $this->testService->getL2LanguageName(
            $langId,
            $textId,
            $selection,
            $sessTestsql
        );

        echo '<div class="test-container">';
        echo '<div id="frame-h">';
        $this->header([]);
        echo '</div>';
        echo '<hr />';
        echo '<div id="frame-l">';

        if ($this->param('type') === 'table') {
            $this->tableTest([]);
        } else {
            $this->renderTestContent();
        }

        echo '</div>';
        echo '</div>';

        $this->renderRightFrames();
        $this->renderAudioElements();
    }

    /**
     * Render desktop test page.
     *
     * @return void
     */
    private function renderDesktopTestPage(): void
    {
        $frameWidth = (int) \Lwt\Database\Settings::getWithDefault('set-text-l-framewidth-percent');
        $langId = $this->param('lang') !== '' ? (int) $this->param('lang') : null;
        $textId = $this->param('text') !== '' ? (int) $this->param('text') : null;
        $selection = $this->param('selection') !== '' ? (int) $this->param('selection') : null;
        $sessTestsql = $_SESSION['testsql'] ?? null;

        $language = $this->testService->getL2LanguageName(
            $langId,
            $textId,
            $selection,
            $sessTestsql
        );

        echo '<div id="frames-l" style="width:' . $frameWidth . '%">';
        echo '<div id="frame-h">';
        $this->header([]);
        echo '</div>';
        echo '<hr />';
        echo '<div id="frame-l">';

        if ($this->param('type') === 'table') {
            $this->tableTest([]);
        } else {
            $this->renderTestContent();
        }

        echo '</div>';
        echo '</div>';

        echo '<div id="frames-r" class="test-frames-right" style="width:' .
            (97 - $frameWidth) . '%">';
        echo '<iframe src="empty.html" scrolling="auto" name="ro" class="test-iframe">';
        echo 'Your browser doesn\'t support iFrames, update it!';
        echo '</iframe>';
        echo '<iframe src="empty.html" scrolling="auto" name="ru" class="test-iframe">';
        echo 'Your browser doesn\'t support iFrames, update it!';
        echo '</iframe>';
        echo '</div>';

        $this->renderAudioElements();
    }

    /**
     * Render test content (AJAX-based word tests).
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    private function renderTestContent(): void
    {
        $langId = $this->param('lang') !== '' ? (int) $this->param('lang') : null;
        $textId = $this->param('text') !== '' ? (int) $this->param('text') : null;
        $selection = $this->param('selection') !== '' ? (int) $this->param('selection') : null;
        $sessTestsql = $_SESSION['testsql'] ?? null;

        $identifier = $this->testService->getTestIdentifier(
            $selection,
            $sessTestsql,
            $langId,
            $textId
        );

        if ($identifier[0] === '') {
            ErrorHandler::die("do_test_test.php called with wrong parameters");
            return;
        }

        $testsql = $this->testService->getTestSql($identifier[0], $identifier[1]);
        $testType = $this->testService->clampTestType((int) $this->param('type', '1'));
        $wordMode = $this->testService->isWordMode($testType);
        $baseType = $this->testService->getBaseTestType($testType);

        // Get counts
        $counts = $this->testService->getTestCounts($testsql);
        $remaining = $counts['due'];

        // Get language settings
        $langIdFromSql = $this->testService->getLanguageIdFromTestSql($testsql);
        if ($langIdFromSql === null) {
            include __DIR__ . '/../Views/Test/no_terms.php';
            return;
        }

        $langSettings = $this->testService->getLanguageSettings($langIdFromSql);

        // Render footer first
        $sessionData = $this->testService->getTestSessionData();
        $wrong = $sessionData['wrong'];
        $correct = $sessionData['correct'];

        include __DIR__ . '/../Views/Test/footer.php';

        // Render test term area
        include __DIR__ . '/../Views/Test/test_term_area.php';

        // Render finished message (hidden for AJAX)
        $tomorrowCount = $this->testService->getTomorrowTestCount($testsql);
        $totalTests = $counts['due'];
        $tomorrowTests = $tomorrowCount;
        $hidden = true;

        include __DIR__ . '/../Views/Test/test_finished.php';

        echo '</div>';

        // Render interaction globals
        $languageService = new LanguageService();
        $langCode = $languageService->getLanguageCode($langIdFromSql, LanguageDefinitions::getAll());
        $dict1Uri = $langSettings['dict1Uri'];
        $dict2Uri = $langSettings['dict2Uri'];
        $translateUri = $langSettings['translateUri'];
        $langId = $langIdFromSql;

        include __DIR__ . '/../Views/Test/test_interaction_globals.php';

        // Render AJAX test JavaScript config
        $reviewData = [
            'total_tests' => $remaining,
            'test_key' => $identifier[0],
            'selection' => $identifier[1],
            'word_mode' => $wordMode,
            'lg_id' => $langIdFromSql,
            'word_regex' => $langSettings['regexWord'],
            'type' => $baseType
        ];
        $waitTime = $this->testService->getEditFrameWaitingTime();
        $startTime = $sessionData['start'];

        include __DIR__ . '/../Views/Test/ajax_test_config.php';
    }

    /**
     * Render right frames for mobile.
     *
     * @return void
     */
    private function renderRightFrames(): void
    {
        echo '<div id="frames-r" class="test-frames-right-mobile" data-action="hide-right-frames">';
        echo '<div class="test-frames-mobile-inner">';
        echo '<iframe src="empty.html" scrolling="auto" name="ro" class="test-iframe">';
        echo 'Your browser doesn\'t support iFrames, update it!';
        echo '</iframe>';
        echo '<iframe src="empty.html" scrolling="auto" name="ru" class="test-iframe">';
        echo 'Your browser doesn\'t support iFrames, update it!';
        echo '</iframe>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render audio elements for test feedback.
     *
     * @return void
     */
    private function renderAudioElements(): void
    {
        echo '<audio id="success_sound">';
        echo '<source src="';
        \print_file_path("sounds/success.mp3");
        echo '" type="audio/mpeg" />';
        echo 'Your browser does not support audio element!';
        echo '</audio>';
        echo '<audio id="failure_sound">';
        echo '<source src="';
        \print_file_path("sounds/failure.mp3");
        echo '" type="audio/mpeg" />';
        echo 'Your browser does not support audio element!';
        echo '</audio>';
    }
}
