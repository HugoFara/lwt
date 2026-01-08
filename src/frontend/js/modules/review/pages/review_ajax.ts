/**
 * Review AJAX - AJAX-based vocabulary review functionality.
 *
 * @license Unlicense
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @since   3.0.0 Extracted from PHP inline scripts
 */

import { closePopup } from '@modules/vocabulary/components/word_popup';
import { speechDispatcher } from '@shared/utils/user_interactions';
import { handleReviewWordClick, handleReviewKeydown } from './review_mode';
import { startElapsedTimer } from '../utils/elapsed_timer';
import { ReviewApi } from '@modules/review/api/review_api';
import { setCurrentWordId, setReviewSolution, setAnswerOpened } from '@modules/review/stores/review_state';
import { getLanguageId, initLanguageConfig } from '@modules/language/stores/language_config';

// Interface for review data
interface ReviewData {
  review_key: string;
  selection: string;
  word_mode: number;
  language_id: number;
  word_regex: string;
  type: number;
  count: number;
  total_reviews: number;
}

// Interface for current review word
interface CurrentReview {
  term_id: number;
  solution: string;
  group: string;
  term_text: string;
}

// Interface for time configuration
interface TimeData {
  wait_time: number;
  time: number;
  start_time: number;
  show_timer: number;
}

// Interface for review status
interface ReviewStatus {
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
  document.querySelectorAll('.word').forEach(el => {
    el.addEventListener('click', function () {
      speechDispatcher(termText, langId);
    });
  });
}

/**
 * Insert a new word into the review area.
 *
 * @param wordId Word ID
 * @param solution The solution text
 * @param group HTML content for the term
 */
export function insertNewWord(wordId: number, solution: string, group: string): void {
  setReviewSolution(solution);
  setCurrentWordId(wordId);

  const termReviewEl = document.getElementById('term-review');
  if (termReviewEl) {
    termReviewEl.innerHTML = group;
  }

  document.addEventListener('keydown', handleReviewKeydown);
  document.querySelectorAll('.word').forEach(el => {
    el.addEventListener('click', handleReviewWordClick);
  });
}

/**
 * Display the review finished message.
 *
 * @param totalReviews Total number of reviews completed
 */
export function doReviewFinished(totalReviews: number): void {
  const termReviewEl = document.getElementById('term-review');
  const reviewFinishedEl = document.getElementById('review-finished-area');
  const reviewsDoneTodayEl = document.getElementById('reviews-done-today');
  const reviewsTomorrowEl = document.getElementById('reviews-tomorrow');

  if (termReviewEl) {
    termReviewEl.style.display = 'none';
  }
  if (reviewFinishedEl) {
    reviewFinishedEl.style.display = 'inherit';
  }
  if (reviewsDoneTodayEl) {
    reviewsDoneTodayEl.textContent = 'Nothing ' + (totalReviews > 0 ? 'more ' : '') + 'to review here!';
  }
  if (reviewsTomorrowEl) {
    reviewsTomorrowEl.style.display = 'none';
  }
}

/**
 * Handle the response from the next word query.
 *
 * @param currentReview Current review word data
 * @param totalReviews Total number of reviews
 * @param reviewKey Review session key
 * @param selection Review selection criteria
 */
export async function reviewQueryHandler(
  currentReview: CurrentReview,
  totalReviews: number,
  reviewKey: string,
  selection: string
): Promise<void> {
  if (currentReview.term_id === 0) {
    doReviewFinished(totalReviews);
    const response = await ReviewApi.getTomorrowCount(reviewKey, selection);
    if (response.data?.count) {
      const reviewsTomorrowEl = document.getElementById('reviews-tomorrow');
      if (reviewsTomorrowEl) {
        reviewsTomorrowEl.style.display = 'inherit';
        reviewsTomorrowEl.textContent =
          "Tomorrow you'll find here " + response.data.count +
          ' review' + (response.data.count < 2 ? '' : 's') + '!';
      }
    }
  } else {
    insertNewWord(
      currentReview.term_id,
      currentReview.solution,
      currentReview.group
    );
    const utteranceCheckbox = document.getElementById('utterance-allowed') as HTMLInputElement | null;
    if (utteranceCheckbox?.checked) {
      prepareWordReading(currentReview.term_text, getLanguageId());
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
    reviewKey: reviewData.review_key,
    selection: reviewData.selection,
    wordMode: reviewData.word_mode === 1,
    lgId: reviewData.language_id,
    wordRegex: reviewData.word_regex,
    type: reviewData.type
  });

  if (response.data) {
    const data: CurrentReview = {
      term_id: typeof response.data.term_id === 'string'
        ? parseInt(response.data.term_id, 10)
        : response.data.term_id,
      solution: response.data.solution || '',
      group: response.data.group,
      term_text: response.data.term_text
    };
    await reviewQueryHandler(
      data, reviewData.count, reviewData.review_key, reviewData.selection
    );
  }
}

/**
 * Get a new word for reviewing.
 *
 * @param reviewData Review session data
 */
export function getNewWord(reviewData?: ReviewData): void {
  if (reviewData) {
    queryNextTerm(reviewData);
  }
  closePopup();
}

/**
 * Initialize the review timer.
 *
 * @param timeData Time configuration data
 */
export function initReviewTimer(timeData: TimeData): void {
  startElapsedTimer(
    timeData.time, timeData.start_time, 'timer', timeData.show_timer
  );
}

/**
 * Update the review count displays in the header.
 *
 * @param reviewsStatus Review progress data
 * @param contDocument The document to update (typically parent context)
 */
export function updateReviewsCount(reviewsStatus: ReviewStatus, contDocument: Document): void {
  let widthDivisor = 0.01;
  if (reviewsStatus.total > 0) {
    widthDivisor = reviewsStatus.total / 100;
  }

  const notReviewedBox = contDocument.getElementById('not-reviewed-box') as HTMLElement | null;
  const wrongReviewsBox = contDocument.getElementById('wrong-reviews-box') as HTMLElement | null;
  const correctReviewsBox = contDocument.getElementById('correct-reviews-box') as HTMLElement | null;
  const notReviewedHeader = contDocument.getElementById('not-reviewed-header');
  const notReviewed = contDocument.getElementById('not-reviewed');
  const wrongReviews = contDocument.getElementById('wrong-reviews');
  const correctReviews = contDocument.getElementById('correct-reviews');

  if (notReviewedBox) {
    notReviewedBox.style.width = (reviewsStatus.remaining / widthDivisor) + 'px';
  }
  if (wrongReviewsBox) {
    wrongReviewsBox.style.width = (reviewsStatus.wrong / widthDivisor) + 'px';
  }
  if (correctReviewsBox) {
    correctReviewsBox.style.width = (reviewsStatus.correct / widthDivisor) + 'px';
  }
  if (notReviewedHeader) {
    notReviewedHeader.textContent = String(reviewsStatus.remaining);
  }
  if (notReviewed) {
    notReviewed.textContent = String(reviewsStatus.remaining);
  }
  if (wrongReviews) {
    wrongReviews.textContent = String(reviewsStatus.wrong);
  }
  if (correctReviews) {
    correctReviews.textContent = String(reviewsStatus.correct);
  }
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
 * @param reviewStatus Current review progress
 * @param ajax Whether using AJAX mode
 * @param waitTime Wait time before reload
 */
export function handleStatusChangeResult(
  wordId: number,
  newStatus: number,
  statusChange: number,
  reviewStatus: ReviewStatus,
  ajax: boolean,
  waitTime: number
): void {
  const context = window.parent;

  // Update the word element in parent context
  const wordEls = context.document.querySelectorAll(`.word${wordId}`);
  wordEls.forEach(el => {
    el.classList.remove('todo', 'todosty');
    el.classList.add('done' + (statusChange >= 0 ? 'ok' : 'wrong') + 'sty');
    el.setAttribute('data_status', String(newStatus));
    el.setAttribute('data_todo', '0');
  });

  const adjustedWaitTime = waitTime + 500;

  if (ajax) {
    updateReviewsCount(reviewStatus, context.document);
    ajaxReloader(adjustedWaitTime, context as Window & { get_new_word?: () => void });
  } else {
    pageReloader(adjustedWaitTime, context);
  }
}

/**
 * Initialize AJAX review with review data.
 *
 * @param reviewData Review session data
 * @param timeData Time configuration data
 */
export function initAjaxReview(reviewData: ReviewData, timeData: TimeData): void {
  // Initialize timer
  initReviewTimer(timeData);

  // Get the first word
  getNewWord(reviewData);
}

/**
 * Initialize review interaction globals (language settings).
 *
 * @param config Configuration from PHP
 */
export function initReviewInteractionGlobals(config: {
  langId: number;
  dict1Uri: string;
  dict2Uri: string;
  translateUri: string;
  langCode: string;
}): void {
  initLanguageConfig({
    id: config.langId,
    dictLink1: config.dict1Uri,
    dictLink2: config.dict2Uri,
    translatorLink: config.translateUri,
    delimiter: '',
    rtl: false
  });

  // Set html lang attribute if we have a valid language code
  if (config.langCode && config.langCode !== config.translateUri) {
    document.documentElement.setAttribute('lang', config.langCode);
  }

  setAnswerOpened(false);
}

/**
 * Auto-initialize review views from JSON config elements.
 */
export function autoInitReviewViews(): void {
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
        config.reviewStatus,
        config.ajax,
        config.waitTime
      );
    } catch (e) {
      console.error('Failed to parse status change result config:', e);
    }
  }

  // Review interaction globals
  const reviewGlobalsConfigEl = document.querySelector<HTMLScriptElement>(
    'script[data-lwt-review-interaction-globals-config]'
  );
  if (reviewGlobalsConfigEl) {
    try {
      const config = JSON.parse(reviewGlobalsConfigEl.textContent || '{}');
      initReviewInteractionGlobals(config);
    } catch (e) {
      console.error('Failed to parse review interaction globals config:', e);
    }
  }

  // AJAX review initialization
  const ajaxReviewConfigEl = document.querySelector<HTMLScriptElement>(
    'script[data-lwt-ajax-review-config]'
  );
  if (ajaxReviewConfigEl) {
    try {
      const config = JSON.parse(ajaxReviewConfigEl.textContent || '{}');
      initAjaxReview(config.reviewData, config.timeData);
    } catch (e) {
      console.error('Failed to parse ajax review config:', e);
    }
  }
}

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', autoInitReviewViews);
} else {
  autoInitReviewViews();
}
