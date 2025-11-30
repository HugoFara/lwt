<?php

/**
 * Test Views - HTML/UI rendering for word testing/review
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Views;

use Lwt\Services\LanguageService;
use Lwt\Services\LanguageDefinitions;
use Lwt\Services\ExportService;
use Lwt\View\Helper\PageLayoutHelper;
use Lwt\View\Helper\StatusHelper;
use Lwt\View\Helper\FormHelper;

require_once __DIR__ . '/../View/Helper/PageLayoutHelper.php';
require_once __DIR__ . '/../View/Helper/StatusHelper.php';
require_once __DIR__ . '/../View/Helper/FormHelper.php';
require_once __DIR__ . '/../Services/WordStatusService.php';
require_once __DIR__ . '/../Services/TextNavigationService.php';
require_once __DIR__ . '/../Services/LanguageService.php';
require_once __DIR__ . '/../Services/LanguageDefinitions.php';

/**
 * View class for rendering test/review UI components.
 *
 * Handles all HTML output for test header, test content, test table,
 * and status change pages.
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class TestViews
{
    /**
     * Render the test header navigation row.
     *
     * @param int|null $textId Text ID if testing from a text
     *
     * @return void
     */
    public function renderHeaderRow(?int $textId = null): void
    {
        ?>
<div class="flex-header">
    <div>
        <a href="/texts" target="_top">
            <?php echo PageLayoutHelper::buildLogo(); ?>
        </a>
    </div>
    <?php
        if ($textId !== null) {
            echo '<div>' . \getPreviousAndNextTextLinks(
                $textId,
                '/test?text=',
                false,
                ''
            ) . '</div>';
            ?>
    <div>
        <a href="/text/read?start=<?php echo $textId; ?>" target="_top">
            <img src="/assets/icons/book-open-bookmark.png" title="Read" alt="Read" />
        </a>
        <a href="/text/print-plain?text=<?php echo $textId; ?>" target="_top">
            <img src="/assets/icons/printer.png" title="Print" alt="Print" />
        </a>
        <?php echo \get_annotation_link($textId); ?>
    </div>
            <?php
        }
        ?>
    <div>
        <?php echo PageLayoutHelper::buildQuickMenu(); ?>
    </div>
</div>
        <?php
    }

    /**
     * Render JavaScript for test header.
     *
     * @return void
     *
     * @deprecated 3.0.0 JavaScript is now bundled in the Vite build.
     *             This method is kept for backward compatibility but does nothing.
     */
    public function renderHeaderJs(): void
    {
        // JavaScript is now in src/frontend/js/testing/test_header.ts
        // and auto-initializes via event delegation
    }

    /**
     * Render the test header content with buttons.
     *
     * @param string $title         Page title
     * @param string $property      URL property
     * @param int    $totalDue      Words due today
     * @param int    $totalCount    Total words
     * @param string $languageName  L2 language name
     *
     * @return void
     */
    public function renderHeaderContent(
        string $title,
        string $property,
        int $totalDue,
        int $totalCount,
        string $languageName
    ): void {
        ?>
<h1>TEST - <?php echo \tohtml($title); ?></h1>
<div style="margin: 5px;">
    Word<?php echo $totalCount > 1 ? 's' : ''; ?> due today:
    <?php echo $totalCount; ?>,
    <span class="todosty" id="not-tested-header"><?php echo $totalDue; ?></span> remaining.
</div>
<div class="flex-spaced">
    <div>
        <input type="button" value="..[<?php echo \tohtml($languageName); ?>].."
            data-action="start-word-test" data-test-type="1"
            data-property="<?php echo \tohtml($property); ?>" />
        <input type="button" value="..[L1].."
            data-action="start-word-test" data-test-type="2"
            data-property="<?php echo \tohtml($property); ?>" />
        <input type="button" value="..[-].."
            data-action="start-word-test" data-test-type="3"
            data-property="<?php echo \tohtml($property); ?>" />
    </div>
    <div>
        <input type="button" value="[<?php echo \tohtml($languageName); ?>]"
            data-action="start-word-test" data-test-type="4"
            data-property="<?php echo \tohtml($property); ?>" />
        <input type="button" value="[L1]"
            data-action="start-word-test" data-test-type="5"
            data-property="<?php echo \tohtml($property); ?>" />
    </div>
    <div>
        <input type="button" value="Table"
            data-action="start-test-table"
            data-property="<?php echo \tohtml($property); ?>" />
    </div>
    <div>
        <input type="checkbox" id="utterance-allowed" />
        <label for="utterance-allowed">Read words aloud</label>
    </div>
</div>
        <?php
    }

    /**
     * Render the test footer with progress bar.
     *
     * @param int $remaining Not yet tested count
     * @param int $wrong     Wrong answers count
     * @param int $correct   Correct answers count
     *
     * @return void
     */
    public function renderFooter(int $remaining, int $wrong, int $correct): void
    {
        $total = $wrong + $correct + $remaining;
        $divisor = $total > 0 ? $total / 100.0 : 1.0;
        $lRemaining = round($remaining / $divisor, 0);
        $lWrong = round($wrong / $divisor, 0);
        $lCorrect = round($correct / $divisor, 0);
        ?>
<footer id="footer">
    <span style="margin-left: 15px; margin-right: 15px;">
        <img src="/assets/icons/clock.png" title="Elapsed Time" alt="Elapsed Time" />
        <span id="timer" title="Elapsed Time"></span>
    </span>
    <span style="margin-left: 15px; margin-right: 15px;">
        <img id="not-tested-box" class="borderl"
            src="<?php echo \get_file_path('icn/test_notyet.png'); ?>"
            title="Not yet tested" alt="Not yet tested" height="10"
            width="<?php echo $lRemaining; ?>" /><img
            id="wrong-tests-box" class="bordermiddle"
            src="<?php echo \get_file_path('icn/test_wrong.png'); ?>"
            title="Wrong" alt="Wrong" height="10"
            width="<?php echo $lWrong; ?>" /><img
            id="correct-tests-box" class="borderr"
            src="<?php echo \get_file_path('icn/test_correct.png'); ?>"
            title="Correct" alt="Correct" height="10"
            width="<?php echo $lCorrect; ?>" />
    </span>
    <span style="margin-left: 15px; margin-right: 15px;">
        <span title="Total number of tests" id="total_tests"><?php echo $total; ?></span>
        =
        <span class="todosty" title="Not yet tested" id="not-tested"><?php echo $remaining; ?></span>
        +
        <span class="donewrongsty" title="Wrong" id="wrong-tests"><?php echo $wrong; ?></span>
        +
        <span class="doneoksty" title="Correct" id="correct-tests"><?php echo $correct; ?></span>
    </span>
</footer>
        <?php
    }

    /**
     * Render test finished message.
     *
     * @param int  $totalTests    Total tests done
     * @param int  $tomorrowTests Tests due tomorrow
     * @param bool $hidden        Whether to hide initially (for AJAX)
     *
     * @return void
     */
    public function renderTestFinished(int $totalTests, int $tomorrowTests, bool $hidden = false): void
    {
        $display = $hidden ? 'none' : 'inherit';
        ?>
<p id="test-finished-area" class="center" style="display: <?php echo $display; ?>;">
    <img src="/assets/images/ok.png" alt="Done!" />
    <br /><br />
    <span class="red2">
        <span id="tests-done-today">
            Nothing <?php echo $totalTests > 0 ? 'more ' : ''; ?>to test here!
        </span>
        <br /><br />
        <span id="tests-tomorrow">
            Tomorrow you'll find here <?php echo $tomorrowTests; ?>
            test<?php echo $tomorrowTests == 1 ? '' : 's'; ?>!
        </span>
    </span>
</p>
        <?php
    }

    /**
     * Render status change result page.
     *
     * @param int    $wordText  Word text
     * @param int    $oldStatus Previous status
     * @param int    $newStatus New status
     * @param int    $oldScore  Previous score
     * @param int    $newScore  New score
     *
     * @return void
     */
    public function renderStatusChangeResult(
        string $wordText,
        int $oldStatus,
        int $newStatus,
        int $oldScore,
        int $newScore
    ): void {
        PageLayoutHelper::renderPageStart("Term: " . $wordText, false);

        if ($oldStatus == $newStatus) {
            echo '<p>Status ' . StatusHelper::buildColoredMessage($newStatus, StatusHelper::getName($newStatus), StatusHelper::getAbbr($newStatus)) . ' not changed.</p>';
        } else {
            echo '<p>Status changed from ' . StatusHelper::buildColoredMessage($oldStatus, StatusHelper::getName($oldStatus), StatusHelper::getAbbr($oldStatus)) .
                ' to ' . StatusHelper::buildColoredMessage($newStatus, StatusHelper::getName($newStatus), StatusHelper::getAbbr($newStatus)) . '.</p>';
        }

        echo "<p>Old score was $oldScore, new score is now $newScore.</p>";
    }

    /**
     * Render JavaScript for status change page.
     *
     * @param int   $wordId       Word ID
     * @param int   $newStatus    New status
     * @param int   $statusChange Status change direction
     * @param array $testStatus   Test progress data
     * @param bool  $ajax         Whether using AJAX mode
     * @param int   $waitTime     Wait time before reload
     *
     * @return void
     */
    public function renderStatusChangeJs(
        int $wordId,
        int $newStatus,
        int $statusChange,
        array $testStatus,
        bool $ajax,
        int $waitTime
    ): void {
        ?>
<script type="text/javascript">
    // Call the bundled handleStatusChangeResult function
    handleStatusChangeResult(
        <?php echo json_encode($wordId); ?>,
        <?php echo json_encode($newStatus); ?>,
        <?php echo json_encode($statusChange); ?>,
        <?php echo json_encode($testStatus); ?>,
        <?php echo json_encode($ajax); ?>,
        <?php echo json_encode($waitTime); ?>
    );
</script>
        <?php
    }

    /**
     * Render table test settings checkboxes.
     *
     * @param array $settings Settings array with keys: edit, status, term, trans, rom, sentence
     *
     * @return void
     */
    public function renderTableTestSettings(array $settings): void
    {
        ?>
<p>
    <input type="checkbox" id="cbEdit" <?php echo FormHelper::getChecked($settings['edit']); ?> />
    Edit
    <input type="checkbox" id="cbStatus" <?php echo FormHelper::getChecked($settings['status']); ?> />
    Status
    <input type="checkbox" id="cbTerm" <?php echo FormHelper::getChecked($settings['term']); ?> />
    Term
    <input type="checkbox" id="cbTrans" <?php echo FormHelper::getChecked($settings['trans']); ?> />
    Translation
    <input type="checkbox" id="cbRom" <?php echo FormHelper::getChecked($settings['rom']); ?> />
    Romanization
    <input type="checkbox" id="cbSentence" <?php echo FormHelper::getChecked($settings['sentence']); ?> />
    Sentence
</p>
        <?php
    }

    /**
     * Render table test JavaScript.
     *
     * @return void
     *
     * @deprecated 3.0.0 JavaScript is now bundled in the Vite build.
     *             This method is kept for backward compatibility but does nothing.
     */
    public function renderTableTestJs(): void
    {
        // JavaScript is now in src/frontend/js/testing/test_table.ts
        // and auto-initializes when checkboxes exist
    }

    /**
     * Render table test header row.
     *
     * @return void
     */
    public function renderTableTestHeader(): void
    {
        ?>
<tr>
    <th class="th1">Ed</th>
    <th class="th1 clickable">Status</th>
    <th class="th1 clickable">Term</th>
    <th class="th1 clickable">Translation</th>
    <th class="th1 clickable">Romanization</th>
    <th class="th1 clickable">Sentence</th>
</tr>
        <?php
    }

    /**
     * Render a single table test row.
     *
     * @param array  $word      Word record
     * @param string $regexWord Regex for word characters
     * @param int    $textSize  Text size percentage
     * @param bool   $rtl       Right-to-left script
     *
     * @return void
     */
    public function renderTableTestRow(
        array $word,
        string $regexWord,
        int $textSize,
        bool $rtl
    ): void {
        $span1 = $rtl ? '<span dir="rtl">' : '';
        $span2 = $rtl ? '</span>' : '';

        $sent = \tohtml(ExportService::replaceTabNewline($word['WoSentence'] ?? ''));
        $sent1 = str_replace(
            "{",
            ' <b>[',
            str_replace(
                "}",
                ']</b> ',
                ExportService::maskTermInSentence($sent, $regexWord)
            )
        );
        ?>
<tr>
    <td class="td1 center" nowrap="nowrap">
        <a href="edit_tword.php?wid=<?php echo $word['WoID']; ?>" target="ro"
            onclick="showRightFrames();">
            <img src="/assets/icons/sticky-note--pencil.png" title="Edit Term" alt="Edit Term" />
        </a>
    </td>
    <td class="td1 center" nowrap="nowrap">
        <span id="STAT<?php echo $word['WoID']; ?>">
            <?php echo StatusHelper::buildTestTableControls(
                $word['Score'],
                $word['WoStatus'],
                $word['WoID'],
                StatusHelper::getAbbr($word['WoStatus']),
                \get_file_path('assets/icons/placeholder.png')
            ); ?>
        </span>
    </td>
    <td class="td1 center" style="font-size:<?php echo $textSize; ?>%;">
        <?php echo $span1; ?>
        <span id="TERM<?php echo $word['WoID']; ?>">
            <?php echo \tohtml($word['WoText']); ?>
        </span>
        <?php echo $span2; ?>
    </td>
    <td class="td1 center">
        <span id="TRAN<?php echo $word['WoID']; ?>">
            <?php echo \tohtml($word['WoTranslation']); ?>
        </span>
    </td>
    <td class="td1 center">
        <span id="ROMA<?php echo $word['WoID']; ?>">
            <?php echo \tohtml($word['WoRomanization'] ?? ''); ?>
        </span>
    </td>
    <td class="td1 center" style="color:#000;">
        <?php echo $span1; ?>
        <span id="SENT<?php echo $word['WoID']; ?>"><?php echo $sent1; ?></span>
        <?php echo $span2; ?>
    </td>
</tr>
        <?php
    }

    /**
     * Render test term area (for AJAX tests).
     *
     * @param array $langSettings Language settings
     *
     * @return void
     */
    public function renderTestTermArea(array $langSettings): void
    {
        ?>
<div id="body">
    <p id="term-test"
       dir="<?php echo $langSettings['rtl'] ? 'rtl' : 'ltr'; ?>"
       style="<?php echo $langSettings['removeSpaces'] ? 'word-break:break-all;' : ''; ?>
              font-size: <?php echo $langSettings['textSize']; ?>%;
              line-height: 1.4;
              text-align: center;
              margin-bottom: 300px;">
    </p>
        <?php
    }

    /**
     * Render test interaction globals JavaScript.
     *
     * @param string $dict1Uri      Dictionary 1 URI
     * @param string $dict2Uri      Dictionary 2 URI
     * @param string $translateUri  Translator URI
     * @param int    $langId        Language ID
     *
     * @return void
     */
    public function renderTestInteractionGlobals(
        string $dict1Uri,
        string $dict2Uri,
        string $translateUri,
        int $langId
    ): void {
        $languageService = new LanguageService();
        $langCode = $languageService->getLanguageCode($langId, LanguageDefinitions::getAll());
        ?>
<script type="text/javascript">
    LWT_DATA.language.id = <?php echo json_encode($langId); ?>;
    LWT_DATA.language.dict_link1 = <?php echo json_encode($dict1Uri); ?>;
    LWT_DATA.language.dict_link2 = <?php echo json_encode($dict2Uri); ?>;
    LWT_DATA.language.translator_link = <?php echo json_encode($translateUri); ?>;
    LANG = <?php echo json_encode($langCode); ?>;
    if (LANG && LANG != LWT_DATA.language.translator_link) {
        $("html").attr('lang', LANG);
    }
    LWT_DATA.test.answer_opened = false;
</script>
        <?php
    }

    /**
     * Render AJAX test JavaScript.
     *
     * @param array $reviewData Review data for JavaScript
     * @param int   $waitTime   Edit frame waiting time
     * @param int   $startTime  Test start time
     *
     * @return void
     */
    public function renderAjaxTestJs(array $reviewData, int $waitTime, int $startTime): void
    {
        $timeData = [
            'wait_time' => $waitTime,
            'time' => time(),
            'start_time' => $startTime,
            'show_timer' => $reviewData['total_tests'] > 0 ? 0 : 1
        ];
        ?>
<script type="text/javascript">
    // Initialize AJAX test using bundled functions
    $(document).ready(function() {
        initAjaxTest(
            <?php echo json_encode($reviewData); ?>,
            <?php echo json_encode($timeData); ?>
        );
    });
</script>
        <?php
    }

    /**
     * Render error message for invalid test parameters.
     *
     * @param string $message Error message
     *
     * @return void
     */
    public function renderError(string $message): void
    {
        \Lwt\View\Helper\PageLayoutHelper::renderMessage($message, true);
    }

    /**
     * Render no terms message.
     *
     * @return void
     */
    public function renderNoTerms(): void
    {
        echo '<p class="center">&nbsp;<br />Sorry - No terms to display or to test at this time.</p>';
    }
}
