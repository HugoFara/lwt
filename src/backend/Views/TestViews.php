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
     */
    public function renderHeaderJs(): void
    {
        ?>
<script type="text/javascript">
    function setUtteranceSetting() {
        const utterancechecked = JSON.parse(
            localStorage.getItem('review-utterance-allowed')
        );
        const utterancecheckbox = document.getElementById('utterance-allowed');

        utterancecheckbox.checked = utterancechecked;
        utterancecheckbox.addEventListener('change', function() {
            localStorage.setItem(
                'review-utterance-allowed',
                utterancecheckbox.checked
            );
        });
    }

    function resetFrames() {
        parent.frames['ro'].location.href = 'empty.html';
        parent.frames['ru'].location.href = 'empty.html';
    }

    function startWordTest(type, property) {
        resetFrames();
        window.location.href = '/test?type=' + type + '&' + property;
    }

    function startTestTable(property) {
        resetFrames();
        window.location.href = '/test?type=table&' + property;
    }

    $(setUtteranceSetting)
</script>
        <?php
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
            onclick="startWordTest(1, '<?php echo $property; ?>')" />
        <input type="button" value="..[L1].."
            onclick="startWordTest(2, '<?php echo $property; ?>')" />
        <input type="button" value="..[-].."
            onclick="startWordTest(3, '<?php echo $property; ?>')" />
    </div>
    <div>
        <input type="button" value="[<?php echo \tohtml($languageName); ?>]"
            onclick="startWordTest(4, '<?php echo $property; ?>')" />
        <input type="button" value="[L1]"
            onclick="startWordTest(5, '<?php echo $property; ?>')" />
    </div>
    <div>
        <input type="button" value="Table"
            onclick="startTestTable('<?php echo $property; ?>')" />
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
    const context = window.parent;
    $('.word<?php echo $wordId; ?>', context.document)
        .removeClass('todo todosty')
        .addClass('done<?php echo ($statusChange >= 0 ? 'ok' : 'wrong'); ?>sty')
        .attr('data_status', '<?php echo $newStatus; ?>')
        .attr('data_todo', '0');

    const waittime = <?php echo json_encode($waitTime); ?> + 500;

    function page_reloader(waittime, target) {
        if (waittime <= 0) {
            target.location.reload();
        } else {
            setTimeout(window.location.reload.bind(target.location), waittime);
        }
    }

    function update_tests_count(tests_status, cont_document) {
        let width_divisor = .01;
        if (tests_status["total"] > 0) {
            width_divisor = tests_status["total"] / 100;
        }

        $("#not-tested-box", cont_document).width(tests_status["remaining"] / width_divisor);
        $("#wrong-tests-box", cont_document).width(tests_status["wrong"] / width_divisor);
        $("#correct-tests-box", cont_document).width(tests_status["correct"] / width_divisor);

        $("#not-tested-header", cont_document).text(tests_status["remaining"]);
        $("#not-tested", cont_document).text(tests_status["remaining"]);
        $("#wrong-tests", cont_document).text(tests_status["wrong"]);
        $("#correct-tests", cont_document).text(tests_status["correct"]);
    }

    function ajax_reloader(waittime, target, tests_status) {
        if (waittime <= 0) {
            context.get_new_word();
        } else {
            setTimeout(target.get_new_word, waittime);
        }
    }

    if (<?php echo json_encode($ajax); ?>) {
        update_tests_count(<?php echo json_encode($testStatus); ?>, context.document);
        ajax_reloader(waittime, context);
    } else {
        page_reloader(waittime, context);
    }
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
     */
    public function renderTableTestJs(): void
    {
        ?>
<script type="text/javascript">
//<![CDATA[
$(document).ready(function() {
    $('#cbEdit').change(function() {
        if ($('#cbEdit').is(':checked')) {
            $('td:nth-child(1),th:nth-child(1)').show();
            do_ajax_save_setting('currenttabletestsetting1', '1');
        } else {
            $('td:nth-child(1),th:nth-child(1)').hide();
            do_ajax_save_setting('currenttabletestsetting1', '0');
        }
        $('th,td').css('border-top-left-radius', '').css('border-bottom-left-radius', '');
        $('th:visible').eq(0).css('border-top-left-radius', 'inherit')
            .css('border-bottom-left-radius', '0px');
        $('tr:last-child>td:visible').eq(0).css('border-bottom-left-radius', 'inherit');
    });

    $('#cbStatus').change(function() {
        if ($('#cbStatus').is(':checked')) {
            $('td:nth-child(2),th:nth-child(2)').show();
            do_ajax_save_setting('currenttabletestsetting2', '1');
        } else {
            $('td:nth-child(2),th:nth-child(2)').hide();
            do_ajax_save_setting('currenttabletestsetting2', '0');
        }
        $('th,td').css('border-top-left-radius', '').css('border-bottom-left-radius', '');
        $('th:visible').eq(0).css('border-top-left-radius', 'inherit')
            .css('border-bottom-left-radius', '0px');
        $('tr:last-child>td:visible').eq(0).css('border-bottom-left-radius', 'inherit');
    });

    $('#cbTerm').change(function() {
        if ($('#cbTerm').is(':checked')) {
            $('td:nth-child(3)').css('color', 'black').css('cursor', 'auto');
            do_ajax_save_setting('currenttabletestsetting3', '1');
        } else {
            $('td:nth-child(3)').css('color', 'white').css('cursor', 'pointer');
            do_ajax_save_setting('currenttabletestsetting3', '0');
        }
    });

    $('#cbTrans').change(function() {
        if ($('#cbTrans').is(':checked')) {
            $('td:nth-child(4)').css('color', 'black').css('cursor', 'auto');
            do_ajax_save_setting('currenttabletestsetting4', '1');
        } else {
            $('td:nth-child(4)').css('color', 'white').css('cursor', 'pointer');
            do_ajax_save_setting('currenttabletestsetting4', '0');
        }
    });

    $('#cbRom').change(function() {
        if ($('#cbRom').is(':checked')) {
            $('td:nth-child(5),th:nth-child(5)').show();
            do_ajax_save_setting('currenttabletestsetting5', '1');
        } else {
            $('td:nth-child(5),th:nth-child(5)').hide();
            do_ajax_save_setting('currenttabletestsetting5', '0');
        }
        $('th,td').css('border-top-right-radius', '').css('border-bottom-right-radius', '');
        $('th:visible:last').css('border-top-right-radius', 'inherit');
        $('tr:last-child>td:visible:last').css('border-bottom-right-radius', 'inherit');
    });

    $('#cbSentence').change(function() {
        if ($('#cbSentence').is(':checked')) {
            $('td:nth-child(6),th:nth-child(6)').show();
            do_ajax_save_setting('currenttabletestsetting6', '1');
        } else {
            $('td:nth-child(6),th:nth-child(6)').hide();
            do_ajax_save_setting('currenttabletestsetting6', '0');
        }
        $('th,td').css('border-top-right-radius', '').css('border-bottom-right-radius', '');
        $('th:visible:last').css('border-top-right-radius', 'inherit');
        $('tr:last-child>td:visible:last').css('border-bottom-right-radius', 'inherit');
    });

    $('td').on('click', function() {
        $(this).css('color', 'black').css('cursor', 'auto');
    });

    $('td').css('background-color', 'white');

    $('#cbEdit').change();
    $('#cbStatus').change();
    $('#cbTerm').change();
    $('#cbTrans').change();
    $('#cbRom').change();
    $('#cbSentence').change();
});
//]]>
</script>
        <?php
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
    function get_new_word(review_data) {
        query_next_term(review_data);
        cClick();
    }

    $(function() {
        get_new_word(<?php echo json_encode($reviewData); ?>)
    });

    function prepare_test_frames() {
        const time_data = <?php echo json_encode($timeData); ?>;

        window.parent.frames['ru'].location.href = 'empty.html';
        if (time_data.wait_time <= 0) {
            window.parent.frames['ro'].location.href = 'empty.html';
        } else {
            setTimeout(
                'window.parent.frames["ro"].location.href="empty.html";',
                time_data.wait_time
            );
        }
        new CountUp(
            time_data.time, time_data.start_time, 'timer', time_data.show_timer
        );
    }

    function prepareWordReading(term_text, lang_id) {
        $('.word').on('click', function() {
            speechDispatcher(term_text, lang_id)
        });
    }

    function insert_new_word(word_id, solution, group) {
        LWT_DATA.test.solution = solution;
        LWT_DATA.word.id = word_id;

        $('#term-test').html(group);

        $(document).on('keydown', keydown_event_do_test_test);
        $('.word').on('click', word_click_event_do_test_test);
    }

    function test_query_handler(current_test, total_tests, test_key, selection) {
        if (current_test['word_id'] == 0) {
            do_test_finished(total_tests);
            $.getJSON(
                'api.php/v1/review/tomorrow-count',
                { test_key: test_key, selection: selection },
                function(tomorrow_test) {
                    if (tomorrow_test.count) {
                        $('#tests-tomorrow').css("display", "inherit");
                        $('#tests-tomorrow').text(
                            "Tomorrow you'll find here " + tomorrow_test.count +
                            ' test' + (tomorrow_test.count < 2 ? '' : 's') + "!"
                        );
                    }
                }
            );
        } else {
            insert_new_word(
                current_test.word_id,
                current_test.solution,
                current_test.group
            );
            if ($('#utterance-allowed').prop('checked')) {
                prepareWordReading(current_test.word_text, LWT_DATA.language.id);
            };
        }
    }

    function query_next_term(review_data) {
        $.getJSON(
            'api.php/v1/review/next-word',
            {
                test_key: review_data.test_key,
                selection: review_data.selection,
                word_mode: review_data.word_mode,
                lg_id: review_data.lg_id,
                word_regex: review_data.word_regex,
                type: review_data.type
            }
        )
        .done(function(data) {
            test_query_handler(
                data, review_data.count, review_data.test_key, review_data.selection
            );
        });
    }

    function do_test_finished(total_tests) {
        $('#term-test').css("display", "none");
        $('#test-finished-area').css("display", "inherit");
        $('#tests-done-today').text(
            "Nothing " + (total_tests > 0 ? 'more ' : '') + "to test here!"
        );
        $('#tests-tomorrow').css("display", "none");
    }

    $(document).ready(prepare_test_frames);
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
