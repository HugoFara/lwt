<?php

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
use Lwt\Services\MobileService;
use Lwt\Views\TestViews;
use Lwt\View\Helper\PageLayoutHelper;

require_once __DIR__ . '/../Services/TestService.php';
require_once __DIR__ . '/../Services/MobileService.php';
require_once __DIR__ . '/../Views/TestViews.php';
require_once __DIR__ . '/../View/Helper/PageLayoutHelper.php';
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
    private TestViews $testViews;
    private MobileService $mobileService;

    public function __construct()
    {
        parent::__construct();
        $this->testService = new TestService();
        $this->testViews = new TestViews();
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
     */
    public function setStatus(array $params): void
    {
        $wid = (int) $this->param('wid');
        $status = $this->param('status');
        $stchange = $this->param('stchange');
        $useAjax = isset($_REQUEST['ajax']);

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

        // Render result
        $this->testViews->renderStatusChangeResult(
            $wordText,
            $result['oldStatus'],
            $result['newStatus'],
            $result['oldScore'],
            $result['newScore']
        );

        $this->testViews->renderStatusChangeJs(
            $wid,
            $result['newStatus'],
            $statusChange,
            $testStatus,
            $useAjax,
            $this->testService->getWaitingTime()
        );

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Render test header frame.
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function header(array $params): void
    {
        $langId = $this->param('lang') !== null ? (int) $this->param('lang') : null;
        $textId = $this->param('text') !== null ? (int) $this->param('text') : null;
        $selection = $this->param('selection') !== null ? (int) $this->param('selection') : null;
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

        // Render header
        $this->testViews->renderHeaderJs();
        $this->testViews->renderHeaderRow($textId);
        $this->testViews->renderHeaderContent(
            $testData['title'],
            $testData['property'],
            $testData['counts']['due'],
            $testData['counts']['total'],
            $languageName
        );
    }

    /**
     * Render table test.
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function tableTest(array $params): void
    {
        $langId = $this->param('lang') !== null ? (int) $this->param('lang') : null;
        $textId = $this->param('text') !== null ? (int) $this->param('text') : null;
        $selection = $this->param('selection') !== null ? (int) $this->param('selection') : null;
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
            $this->testViews->renderNoTerms();
            PageLayoutHelper::renderPageEnd();
            exit();
        }

        $langSettings = $this->testService->getLanguageSettings($langIdFromSql);
        $textSize = round((($langSettings['textSize'] ?? 100) - 100) / 2, 0) + 100;

        // Render table
        $settings = $this->testService->getTableTestSettings();
        $this->testViews->renderTableTestJs();
        $this->testViews->renderTableTestSettings($settings);

        echo '<table class="sortable tab2 table-test" cellspacing="0" cellpadding="5">';
        $this->testViews->renderTableTestHeader();

        $words = $this->testService->getTableTestWords($testsql);
        while ($word = mysqli_fetch_assoc($words)) {
            $this->testViews->renderTableTestRow(
                $word,
                $langSettings['regexWord'] ?? '',
                (int) $textSize,
                $langSettings['rtl'] ?? false
            );
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
        if (isset($_REQUEST['selection']) && isset($_SESSION['testsql'])) {
            return "selection=" . $_REQUEST['selection'];
        }
        if (isset($_REQUEST['lang'])) {
            return "lang=" . $_REQUEST['lang'];
        }
        if (isset($_REQUEST['text'])) {
            return "text=" . $_REQUEST['text'];
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
        $langId = $this->param('lang') !== null ? (int) $this->param('lang') : null;
        $textId = $this->param('text') !== null ? (int) $this->param('text') : null;
        $selection = $this->param('selection') !== null ? (int) $this->param('selection') : null;
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
        $langId = $this->param('lang') !== null ? (int) $this->param('lang') : null;
        $textId = $this->param('text') !== null ? (int) $this->param('text') : null;
        $selection = $this->param('selection') !== null ? (int) $this->param('selection') : null;
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
     */
    private function renderTestContent(): void
    {
        $langId = $this->param('lang') !== null ? (int) $this->param('lang') : null;
        $textId = $this->param('text') !== null ? (int) $this->param('text') : null;
        $selection = $this->param('selection') !== null ? (int) $this->param('selection') : null;
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
        $testType = $this->testService->clampTestType((int) $this->param('type', 1));
        $wordMode = $this->testService->isWordMode($testType);
        $baseType = $this->testService->getBaseTestType($testType);

        // Get counts
        $counts = $this->testService->getTestCounts($testsql);
        $remaining = $counts['due'];

        // Get language settings
        $langIdFromSql = $this->testService->getLanguageIdFromTestSql($testsql);
        if ($langIdFromSql === null) {
            $this->testViews->renderNoTerms();
            return;
        }

        $langSettings = $this->testService->getLanguageSettings($langIdFromSql);

        // Render footer first
        $sessionData = $this->testService->getTestSessionData();
        $this->testViews->renderFooter(
            $remaining,
            $sessionData['wrong'],
            $sessionData['correct']
        );

        // Render test term area
        $this->testViews->renderTestTermArea($langSettings);

        // Render finished message (hidden for AJAX)
        $tomorrowCount = $this->testService->getTomorrowTestCount($testsql);
        $this->testViews->renderTestFinished($counts['due'], $tomorrowCount, true);

        echo '</div>';

        // Render interaction globals
        $this->testViews->renderTestInteractionGlobals(
            $langSettings['dict1Uri'],
            $langSettings['dict2Uri'],
            $langSettings['translateUri'],
            $langIdFromSql
        );

        // Render AJAX test JavaScript
        $reviewData = [
            'total_tests' => $remaining,
            'test_key' => $identifier[0],
            'selection' => $identifier[1],
            'word_mode' => $wordMode,
            'lg_id' => $langIdFromSql,
            'word_regex' => $langSettings['regexWord'],
            'type' => $baseType
        ];

        $this->testViews->renderAjaxTestJs(
            $reviewData,
            $this->testService->getEditFrameWaitingTime(),
            $sessionData['start']
        );
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
