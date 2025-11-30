/**
 * Test AJAX - AJAX-based vocabulary testing functionality.
 *
 * @license Unlicense
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @since   3.0.0 Extracted from PHP inline scripts
 */

import $ from 'jquery';
import { LWT_DATA } from '../core/lwt_state';
import { cClick } from '../ui/word_popup';
import { speechDispatcher } from '../core/user_interactions';
import { word_click_event_do_test_test, keydown_event_do_test_test } from './test_mode';
import { startElapsedTimer } from './elapsed_timer';
import { ReviewApi } from '../api/review';

// Interface for review data
interface ReviewData {
  test_key: string;
  selection: string;
  word_mode: number;
  lg_id: number;
  word_regex: string;
  type: number;
  count: number;
  total_tests: number;
}

// Interface for current test word
interface CurrentTest {
  word_id: number;
  solution: string;
  group: string;
  word_text: string;
}

// Interface for time configuration
interface TimeData {
  wait_time: number;
  time: number;
  start_time: number;
  show_timer: number;
}

// Interface for test status
interface TestStatus {
  total: number;
  remaining: number;
  wrong: number;
  correct: number;
}

/**
 * Prepare word reading functionality for TTS.
 *
 * @param termText The word text to read
 * @param langId The language ID
 */
export function prepareWordReading(termText: string, langId: number): void {
  $('.word').on('click', function () {
    speechDispatcher(termText, langId);
  });
}

/**
 * Insert a new word into the test area.
 *
 * @param wordId Word ID
 * @param solution The solution text
 * @param group HTML content for the term
 */
export function insertNewWord(wordId: number, solution: string, group: string): void {
  LWT_DATA.test.solution = solution;
  LWT_DATA.word.id = wordId;

  $('#term-test').html(group);

  $(document).on('keydown', keydown_event_do_test_test);
  $('.word').on('click', word_click_event_do_test_test);
}

/**
 * Display the test finished message.
 *
 * @param totalTests Total number of tests completed
 */
export function doTestFinished(totalTests: number): void {
  $('#term-test').css('display', 'none');
  $('#test-finished-area').css('display', 'inherit');
  $('#tests-done-today').text(
    'Nothing ' + (totalTests > 0 ? 'more ' : '') + 'to test here!'
  );
  $('#tests-tomorrow').css('display', 'none');
}

/**
 * Handle the response from the next word query.
 *
 * @param currentTest Current test word data
 * @param totalTests Total number of tests
 * @param testKey Test session key
 * @param selection Test selection criteria
 */
export async function testQueryHandler(
  currentTest: CurrentTest,
  totalTests: number,
  testKey: string,
  selection: string
): Promise<void> {
  if (currentTest.word_id === 0) {
    doTestFinished(totalTests);
    const response = await ReviewApi.getTomorrowCount(testKey, selection);
    if (response.data?.count) {
      const testsTomorrowEl = document.getElementById('tests-tomorrow');
      if (testsTomorrowEl) {
        testsTomorrowEl.style.display = 'inherit';
        testsTomorrowEl.textContent =
          "Tomorrow you'll find here " + response.data.count +
          ' test' + (response.data.count < 2 ? '' : 's') + '!';
      }
    }
  } else {
    insertNewWord(
      currentTest.word_id,
      currentTest.solution,
      currentTest.group
    );
    const utteranceCheckbox = document.getElementById('utterance-allowed') as HTMLInputElement | null;
    if (utteranceCheckbox?.checked) {
      prepareWordReading(currentTest.word_text, LWT_DATA.language.id);
    }
  }
}

/**
 * Query the next term from the API.
 *
 * @param reviewData Review session data
 */
export async function queryNextTerm(reviewData: ReviewData): Promise<void> {
  const response = await ReviewApi.getNextWord({
    testKey: reviewData.test_key,
    selection: reviewData.selection,
    wordMode: reviewData.word_mode === 1,
    lgId: reviewData.lg_id,
    wordRegex: reviewData.word_regex,
    type: reviewData.type
  });

  if (response.data) {
    const data: CurrentTest = {
      word_id: typeof response.data.word_id === 'string'
        ? parseInt(response.data.word_id, 10)
        : response.data.word_id,
      solution: response.data.solution || '',
      group: response.data.group,
      word_text: response.data.word_text
    };
    await testQueryHandler(
      data, reviewData.count, reviewData.test_key, reviewData.selection
    );
  }
}

/**
 * Get a new word for testing.
 *
 * @param reviewData Review session data
 */
export function getNewWord(reviewData?: ReviewData): void {
  if (reviewData) {
    queryNextTerm(reviewData);
  }
  cClick();
}

/**
 * Prepare the test frames (clear and start timer).
 *
 * @param timeData Time configuration data
 */
export function prepareTestFrames(timeData: TimeData): void {
  const parentWindow = window.parent as Window & {
    frames: { [key: string]: Window };
  };

  if (parentWindow.frames['ru']) {
    parentWindow.frames['ru'].location.href = 'empty.html';
  }
  if (timeData.wait_time <= 0) {
    if (parentWindow.frames['ro']) {
      parentWindow.frames['ro'].location.href = 'empty.html';
    }
  } else {
    setTimeout(function () {
      if (parentWindow.frames['ro']) {
        parentWindow.frames['ro'].location.href = 'empty.html';
      }
    }, timeData.wait_time);
  }

  // Initialize the elapsed timer
  startElapsedTimer(
    timeData.time, timeData.start_time, 'timer', timeData.show_timer
  );
}

/**
 * Update the test count displays in the header.
 *
 * @param testsStatus Test progress data
 * @param contDocument The document to update (typically parent context)
 */
export function updateTestsCount(testsStatus: TestStatus, contDocument: Document): void {
  let widthDivisor = 0.01;
  if (testsStatus.total > 0) {
    widthDivisor = testsStatus.total / 100;
  }

  $('#not-tested-box', contDocument).width(testsStatus.remaining / widthDivisor);
  $('#wrong-tests-box', contDocument).width(testsStatus.wrong / widthDivisor);
  $('#correct-tests-box', contDocument).width(testsStatus.correct / widthDivisor);

  $('#not-tested-header', contDocument).text(testsStatus.remaining);
  $('#not-tested', contDocument).text(testsStatus.remaining);
  $('#wrong-tests', contDocument).text(testsStatus.wrong);
  $('#correct-tests', contDocument).text(testsStatus.correct);
}

/**
 * Reload using AJAX (get next word).
 *
 * @param waitTime Wait time in milliseconds
 * @param target Window context with get_new_word function
 */
export function ajaxReloader(
  waitTime: number,
  target: Window & { get_new_word?: () => void }
): void {
  if (waitTime <= 0) {
    if (target.get_new_word) {
      target.get_new_word();
    }
  } else {
    setTimeout(function () {
      if (target.get_new_word) {
        target.get_new_word();
      }
    }, waitTime);
  }
}

/**
 * Reload the page after status change.
 *
 * @param waitTime Wait time in milliseconds
 * @param target Window to reload
 */
export function pageReloader(waitTime: number, target: Window): void {
  if (waitTime <= 0) {
    target.location.reload();
  } else {
    setTimeout(function () {
      target.location.reload();
    }, waitTime);
  }
}

/**
 * Handle status change result (update DOM and reload).
 *
 * @param wordId Word ID that was updated
 * @param newStatus New status value
 * @param statusChange Direction of status change (positive or negative)
 * @param testStatus Current test progress
 * @param ajax Whether using AJAX mode
 * @param waitTime Wait time before reload
 */
export function handleStatusChangeResult(
  wordId: number,
  newStatus: number,
  statusChange: number,
  testStatus: TestStatus,
  ajax: boolean,
  waitTime: number
): void {
  const context = window.parent;

  // Update the word element in parent context
  $(`.word${wordId}`, context.document)
    .removeClass('todo todosty')
    .addClass('done' + (statusChange >= 0 ? 'ok' : 'wrong') + 'sty')
    .attr('data_status', String(newStatus))
    .attr('data_todo', '0');

  const adjustedWaitTime = waitTime + 500;

  if (ajax) {
    updateTestsCount(testStatus, context.document);
    ajaxReloader(adjustedWaitTime, context as Window & { get_new_word?: () => void });
  } else {
    pageReloader(adjustedWaitTime, context);
  }
}

/**
 * Initialize AJAX test with review data.
 *
 * @param reviewData Review session data
 * @param timeData Time configuration data
 */
export function initAjaxTest(reviewData: ReviewData, timeData: TimeData): void {
  // Initialize frames and timer
  prepareTestFrames(timeData);

  // Get the first word
  getNewWord(reviewData);
}

/**
 * Initialize test interaction globals (language settings).
 *
 * @param config Configuration from PHP
 */
export function initTestInteractionGlobals(config: {
  langId: number;
  dict1Uri: string;
  dict2Uri: string;
  translateUri: string;
  langCode: string;
}): void {
  LWT_DATA.language.id = config.langId;
  LWT_DATA.language.dict_link1 = config.dict1Uri;
  LWT_DATA.language.dict_link2 = config.dict2Uri;
  LWT_DATA.language.translator_link = config.translateUri;

  // Set html lang attribute if we have a valid language code
  if (config.langCode && config.langCode !== config.translateUri) {
    $('html').attr('lang', config.langCode);
  }

  LWT_DATA.test.answer_opened = false;
}

/**
 * Auto-initialize test views from JSON config elements.
 */
export function autoInitTestViews(): void {
  // Status change result
  const statusChangeConfigEl = document.querySelector<HTMLScriptElement>(
    'script[data-lwt-status-change-result-config]'
  );
  if (statusChangeConfigEl) {
    try {
      const config = JSON.parse(statusChangeConfigEl.textContent || '{}');
      handleStatusChangeResult(
        config.wordId,
        config.newStatus,
        config.statusChange,
        config.testStatus,
        config.ajax,
        config.waitTime
      );
    } catch (e) {
      console.error('Failed to parse status change result config:', e);
    }
  }

  // Test interaction globals
  const testGlobalsConfigEl = document.querySelector<HTMLScriptElement>(
    'script[data-lwt-test-interaction-globals-config]'
  );
  if (testGlobalsConfigEl) {
    try {
      const config = JSON.parse(testGlobalsConfigEl.textContent || '{}');
      initTestInteractionGlobals(config);
    } catch (e) {
      console.error('Failed to parse test interaction globals config:', e);
    }
  }

  // AJAX test initialization
  const ajaxTestConfigEl = document.querySelector<HTMLScriptElement>(
    'script[data-lwt-ajax-test-config]'
  );
  if (ajaxTestConfigEl) {
    try {
      const config = JSON.parse(ajaxTestConfigEl.textContent || '{}');
      initAjaxTest(config.reviewData, config.timeData);
    } catch (e) {
      console.error('Failed to parse ajax test config:', e);
    }
  }
}

// Auto-initialize on DOM ready
$(document).ready(autoInitTestViews);
