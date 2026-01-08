/**
 * Tests for review_ajax.ts - AJAX-based vocabulary review functionality
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  prepareWordReading,
  insertNewWord,
  doReviewFinished,
  reviewQueryHandler,
  updateReviewsCount,
  ajaxReloader,
  pageReloader,
  handleStatusChangeResult
} from '../../../src/frontend/js/modules/review/pages/review_ajax';

// Mock dependencies - use the actual review_state and language_config modules
import { resetReviewState } from '../../../src/frontend/js/modules/review/stores/review_state';
import { initLanguageConfig, resetLanguageConfig } from '../../../src/frontend/js/modules/language/stores/language_config';

vi.mock('../../../src/frontend/js/modules/vocabulary/components/word_popup', () => ({
  closePopup: vi.fn()
}));

vi.mock('../../../src/frontend/js/shared/utils/user_interactions', () => ({
  speechDispatcher: vi.fn()
}));

vi.mock('../../../src/frontend/js/modules/review/pages/review_mode', () => ({
  handleReviewWordClick: vi.fn(),
  handleReviewKeydown: vi.fn()
}));

vi.mock('../../../src/frontend/js/modules/review/utils/elapsed_timer', () => ({
  startElapsedTimer: vi.fn()
}));

import { speechDispatcher } from '../../../src/frontend/js/shared/utils/user_interactions';

describe('review_ajax.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    vi.useFakeTimers();
    // Initialize state modules
    resetReviewState();
    resetLanguageConfig();
    initLanguageConfig({ id: 1 });
  });

  afterEach(() => {
    vi.restoreAllMocks();
    vi.useRealTimers();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // prepareWordReading Tests
  // ===========================================================================

  describe('prepareWordReading', () => {
    it('sets up click handler for word elements', () => {
      document.body.innerHTML = `
        <span class="word">hello</span>
      `;

      prepareWordReading('hello', 1);

      const wordElement = document.querySelector('.word') as HTMLElement;
      wordElement.dispatchEvent(new Event('click', { bubbles: true }));

      expect(speechDispatcher).toHaveBeenCalledWith('hello', 1);
    });

    it('works with multiple word elements', () => {
      document.body.innerHTML = `
        <span class="word">word1</span>
        <span class="word">word2</span>
      `;

      prepareWordReading('test', 2);

      const wordElements = document.querySelectorAll('.word');
      (wordElements[0] as HTMLElement).dispatchEvent(new Event('click', { bubbles: true }));
      (wordElements[1] as HTMLElement).dispatchEvent(new Event('click', { bubbles: true }));

      expect(speechDispatcher).toHaveBeenCalledTimes(2);
    });
  });

  // ===========================================================================
  // insertNewWord Tests
  // ===========================================================================

  describe('insertNewWord', () => {
    it('updates the term-review element with group HTML', () => {
      document.body.innerHTML = `
        <div id="term-review"></div>
      `;

      insertNewWord(123, 'solution text', '<span>Word content</span>');

      expect(document.getElementById('term-review')?.innerHTML).toContain('Word content');
    });
  });

  // ===========================================================================
  // doReviewFinished Tests
  // ===========================================================================

  describe('doReviewFinished', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <div id="term-review">Review area</div>
        <div id="review-finished-area" style="display: none;"></div>
        <span id="reviews-done-today"></span>
        <div id="reviews-tomorrow">Tomorrow content</div>
      `;
    });

    it('hides term-review area', () => {
      doReviewFinished(10);

      const termReview = document.getElementById('term-review') as HTMLElement;
      expect(termReview.style.display).toBe('none');
    });

    it('shows review-finished-area', () => {
      doReviewFinished(10);

      const finishedArea = document.getElementById('review-finished-area') as HTMLElement;
      // JSDOM normalizes 'inherit' to 'block'
      expect(finishedArea.style.display).not.toBe('none');
    });

    it('shows "Nothing more to review" when totalReviews > 0', () => {
      doReviewFinished(5);

      const doneToday = document.getElementById('reviews-done-today');
      expect(doneToday?.textContent).toContain('Nothing more to review here!');
    });

    it('shows "Nothing to review" when totalReviews is 0', () => {
      doReviewFinished(0);

      const doneToday = document.getElementById('reviews-done-today');
      expect(doneToday?.textContent).toBe('Nothing to review here!');
    });

    it('hides reviews-tomorrow section initially', () => {
      doReviewFinished(10);

      const tomorrow = document.getElementById('reviews-tomorrow') as HTMLElement;
      expect(tomorrow.style.display).toBe('none');
    });
  });

  // ===========================================================================
  // reviewQueryHandler Tests
  // ===========================================================================

  describe('reviewQueryHandler', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <div id="term-review"></div>
        <div id="review-finished-area" style="display: none;"></div>
        <span id="reviews-done-today"></span>
        <div id="reviews-tomorrow" style="display: none;"></div>
        <input id="utterance-allowed" type="checkbox">
      `;
    });

    it('calls doReviewFinished when term_id is 0', async () => {
      await reviewQueryHandler(
        { term_id: 0, solution: '', group: '', term_text: '' },
        10,
        'review_key',
        'selection'
      );

      const termReview = document.getElementById('term-review') as HTMLElement;
      expect(termReview.style.display).toBe('none');
    });

    it('inserts new word when term_id is not 0', async () => {
      await reviewQueryHandler(
        { term_id: 123, solution: 'sol', group: '<span>Group</span>', term_text: 'word' },
        10,
        'review_key',
        'selection'
      );

      expect(document.getElementById('term-review')?.innerHTML).toContain('Group');
    });

    it('prepares word reading when utterance-allowed is checked', async () => {
      const checkbox = document.getElementById('utterance-allowed') as HTMLInputElement;
      checkbox.checked = true;

      await reviewQueryHandler(
        { term_id: 123, solution: 'sol', group: '<span class="word">Word</span>', term_text: 'word' },
        10,
        'review_key',
        'selection'
      );

      // Click the word to trigger speech
      const wordElement = document.querySelector('.word') as HTMLElement;
      wordElement.dispatchEvent(new Event('click', { bubbles: true }));
      expect(speechDispatcher).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // updateReviewsCount Tests
  // ===========================================================================

  describe('updateReviewsCount', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <div id="not-reviewed-box" style="width: 50%;"></div>
        <div id="wrong-reviews-box" style="width: 20%;"></div>
        <div id="correct-reviews-box" style="width: 30%;"></div>
        <span id="not-reviewed-header">50</span>
        <span id="not-reviewed">50</span>
        <span id="wrong-reviews">20</span>
        <span id="correct-reviews">30</span>
      `;
    });

    it('updates review count text elements', () => {
      updateReviewsCount(
        { total: 100, remaining: 40, wrong: 25, correct: 35 },
        document
      );

      expect(document.getElementById('not-reviewed-header')?.textContent).toBe('40');
      expect(document.getElementById('not-reviewed')?.textContent).toBe('40');
      expect(document.getElementById('wrong-reviews')?.textContent).toBe('25');
      expect(document.getElementById('correct-reviews')?.textContent).toBe('35');
    });

    it('handles zero total gracefully', () => {
      expect(() => {
        updateReviewsCount(
          { total: 0, remaining: 0, wrong: 0, correct: 0 },
          document
        );
      }).not.toThrow();
    });
  });

  // ===========================================================================
  // ajaxReloader Tests
  // ===========================================================================

  describe('ajaxReloader', () => {
    it('calls get_new_word immediately when waitTime is 0', () => {
      const mockGetNewWord = vi.fn();
      const target = { get_new_word: mockGetNewWord } as unknown as Window & { get_new_word?: () => void };

      ajaxReloader(0, target);

      expect(mockGetNewWord).toHaveBeenCalled();
    });

    it('calls get_new_word immediately when waitTime is negative', () => {
      const mockGetNewWord = vi.fn();
      const target = { get_new_word: mockGetNewWord } as unknown as Window & { get_new_word?: () => void };

      ajaxReloader(-100, target);

      expect(mockGetNewWord).toHaveBeenCalled();
    });

    it('delays get_new_word call when waitTime is positive', () => {
      const mockGetNewWord = vi.fn();
      const target = { get_new_word: mockGetNewWord } as unknown as Window & { get_new_word?: () => void };

      ajaxReloader(500, target);

      expect(mockGetNewWord).not.toHaveBeenCalled();

      vi.advanceTimersByTime(500);

      expect(mockGetNewWord).toHaveBeenCalled();
    });

    it('handles missing get_new_word function', () => {
      const target = {} as Window & { get_new_word?: () => void };

      expect(() => ajaxReloader(0, target)).not.toThrow();
    });
  });

  // ===========================================================================
  // pageReloader Tests
  // ===========================================================================

  describe('pageReloader', () => {
    it('calls location.reload immediately when waitTime is 0', () => {
      const mockReload = vi.fn();
      const target = {
        location: { reload: mockReload }
      } as unknown as Window;

      pageReloader(0, target);

      expect(mockReload).toHaveBeenCalled();
    });

    it('delays reload when waitTime is positive', () => {
      const mockReload = vi.fn();
      const target = {
        location: { reload: mockReload }
      } as unknown as Window;

      pageReloader(1000, target);

      expect(mockReload).not.toHaveBeenCalled();

      vi.advanceTimersByTime(1000);

      expect(mockReload).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // handleStatusChangeResult Tests
  // ===========================================================================

  describe('handleStatusChangeResult', () => {
    beforeEach(() => {
      // Create a mock parent context
      const parentDoc = document.implementation.createHTMLDocument();
      parentDoc.body.innerHTML = `
        <span class="word123 todo todosty" data_status="1" data_todo="1">Word</span>
        <div id="not-reviewed-box"></div>
        <div id="wrong-reviews-box"></div>
        <div id="correct-reviews-box"></div>
        <span id="not-reviewed-header"></span>
        <span id="not-reviewed"></span>
        <span id="wrong-reviews"></span>
        <span id="correct-reviews"></span>
      `;

      // Mock window.parent
      vi.stubGlobal('parent', {
        document: parentDoc,
        get_new_word: vi.fn()
      });
    });

    afterEach(() => {
      vi.unstubAllGlobals();
    });

    it('adds doneoksty class for positive status change', () => {
      handleStatusChangeResult(
        123,
        2,
        1, // positive
        { total: 100, remaining: 90, wrong: 5, correct: 5 },
        true,
        0
      );

      const wordEl = window.parent.document.querySelector('.word123');
      expect(wordEl?.classList.contains('doneoksty')).toBe(true);
    });

    it('adds donewrongsty class for negative status change', () => {
      handleStatusChangeResult(
        123,
        1,
        -1, // negative
        { total: 100, remaining: 90, wrong: 6, correct: 4 },
        true,
        0
      );

      const wordEl = window.parent.document.querySelector('.word123');
      expect(wordEl?.classList.contains('donewrongsty')).toBe(true);
    });

    it('removes todo and todosty classes', () => {
      handleStatusChangeResult(
        123,
        2,
        1,
        { total: 100, remaining: 90, wrong: 5, correct: 5 },
        true,
        0
      );

      const wordEl = window.parent.document.querySelector('.word123');
      expect(wordEl?.classList.contains('todo')).toBe(false);
      expect(wordEl?.classList.contains('todosty')).toBe(false);
    });

    it('updates data_status and data_todo attributes', () => {
      handleStatusChangeResult(
        123,
        3,
        1,
        { total: 100, remaining: 90, wrong: 5, correct: 5 },
        true,
        0
      );

      const wordEl = window.parent.document.querySelector('.word123');
      expect(wordEl?.getAttribute('data_status')).toBe('3');
      expect(wordEl?.getAttribute('data_todo')).toBe('0');
    });

    it('calls ajaxReloader for ajax mode', () => {
      handleStatusChangeResult(
        123,
        2,
        1,
        { total: 100, remaining: 90, wrong: 5, correct: 5 },
        true, // ajax mode
        0
      );

      // Advance timer for the 500ms added delay
      vi.advanceTimersByTime(500);

      expect((window.parent as { get_new_word: () => void }).get_new_word).toHaveBeenCalled();
    });
  });
});
