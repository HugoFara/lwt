/**
 * Tests for test_ajax.ts - AJAX-based vocabulary testing functionality
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import $ from 'jquery';
import {
  prepareWordReading,
  insertNewWord,
  doTestFinished,
  testQueryHandler,
  updateTestsCount,
  ajaxReloader,
  pageReloader,
  handleStatusChangeResult
} from '../../../src/frontend/js/testing/test_ajax';

// Mock dependencies
vi.mock('../../../src/frontend/js/core/lwt_state', () => ({
  LWT_DATA: {
    test: { solution: '' },
    word: { id: 0 },
    language: { id: 1 }
  }
}));

vi.mock('../../../src/frontend/js/ui/word_popup', () => ({
  cClick: vi.fn()
}));

vi.mock('../../../src/frontend/js/core/user_interactions', () => ({
  speechDispatcher: vi.fn()
}));

vi.mock('../../../src/frontend/js/testing/test_mode', () => ({
  word_click_event_do_test_test: vi.fn(),
  keydown_event_do_test_test: vi.fn()
}));

vi.mock('../../../src/frontend/js/testing/elapsed_timer', () => ({
  startElapsedTimer: vi.fn()
}));

import { speechDispatcher } from '../../../src/frontend/js/core/user_interactions';

describe('test_ajax.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    vi.useFakeTimers();
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

      $('.word').trigger('click');

      expect(speechDispatcher).toHaveBeenCalledWith('hello', 1);
    });

    it('works with multiple word elements', () => {
      document.body.innerHTML = `
        <span class="word">word1</span>
        <span class="word">word2</span>
      `;

      prepareWordReading('test', 2);

      $('.word').eq(0).trigger('click');
      $('.word').eq(1).trigger('click');

      expect(speechDispatcher).toHaveBeenCalledTimes(2);
    });
  });

  // ===========================================================================
  // insertNewWord Tests
  // ===========================================================================

  describe('insertNewWord', () => {
    it('updates the term-test element with group HTML', () => {
      document.body.innerHTML = `
        <div id="term-test"></div>
      `;

      insertNewWord(123, 'solution text', '<span>Word content</span>');

      expect($('#term-test').html()).toContain('Word content');
    });
  });

  // ===========================================================================
  // doTestFinished Tests
  // ===========================================================================

  describe('doTestFinished', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <div id="term-test">Test area</div>
        <div id="test-finished-area" style="display: none;"></div>
        <span id="tests-done-today"></span>
        <div id="tests-tomorrow">Tomorrow content</div>
      `;
    });

    it('hides term-test area', () => {
      doTestFinished(10);

      expect($('#term-test').css('display')).toBe('none');
    });

    it('shows test-finished-area', () => {
      doTestFinished(10);

      // JSDOM normalizes 'inherit' to 'block'
      expect($('#test-finished-area').css('display')).not.toBe('none');
    });

    it('shows "Nothing more to test" when totalTests > 0', () => {
      doTestFinished(5);

      expect($('#tests-done-today').text()).toContain('Nothing more to test here!');
    });

    it('shows "Nothing to test" when totalTests is 0', () => {
      doTestFinished(0);

      expect($('#tests-done-today').text()).toBe('Nothing to test here!');
    });

    it('hides tests-tomorrow section initially', () => {
      doTestFinished(10);

      expect($('#tests-tomorrow').css('display')).toBe('none');
    });
  });

  // ===========================================================================
  // testQueryHandler Tests
  // ===========================================================================

  describe('testQueryHandler', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <div id="term-test"></div>
        <div id="test-finished-area" style="display: none;"></div>
        <span id="tests-done-today"></span>
        <div id="tests-tomorrow" style="display: none;"></div>
        <input id="utterance-allowed" type="checkbox">
      `;
    });

    it('calls doTestFinished when word_id is 0', () => {
      const mockGetJSON = vi.fn();
      vi.spyOn($, 'getJSON').mockImplementation(mockGetJSON);

      testQueryHandler(
        { word_id: 0, solution: '', group: '', word_text: '' },
        10,
        'test_key',
        'selection'
      );

      expect($('#term-test').css('display')).toBe('none');
    });

    it('inserts new word when word_id is not 0', () => {
      testQueryHandler(
        { word_id: 123, solution: 'sol', group: '<span>Group</span>', word_text: 'word' },
        10,
        'test_key',
        'selection'
      );

      expect($('#term-test').html()).toContain('Group');
    });

    it('prepares word reading when utterance-allowed is checked', () => {
      $('input#utterance-allowed').prop('checked', true);

      testQueryHandler(
        { word_id: 123, solution: 'sol', group: '<span class="word">Word</span>', word_text: 'word' },
        10,
        'test_key',
        'selection'
      );

      // Click the word to trigger speech
      $('.word').trigger('click');
      expect(speechDispatcher).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // updateTestsCount Tests
  // ===========================================================================

  describe('updateTestsCount', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <div id="not-tested-box" style="width: 50%;"></div>
        <div id="wrong-tests-box" style="width: 20%;"></div>
        <div id="correct-tests-box" style="width: 30%;"></div>
        <span id="not-tested-header">50</span>
        <span id="not-tested">50</span>
        <span id="wrong-tests">20</span>
        <span id="correct-tests">30</span>
      `;
    });

    it('updates test count text elements', () => {
      updateTestsCount(
        { total: 100, remaining: 40, wrong: 25, correct: 35 },
        document
      );

      expect($('#not-tested-header').text()).toBe('40');
      expect($('#not-tested').text()).toBe('40');
      expect($('#wrong-tests').text()).toBe('25');
      expect($('#correct-tests').text()).toBe('35');
    });

    it('handles zero total gracefully', () => {
      expect(() => {
        updateTestsCount(
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
        <div id="not-tested-box"></div>
        <div id="wrong-tests-box"></div>
        <div id="correct-tests-box"></div>
        <span id="not-tested-header"></span>
        <span id="not-tested"></span>
        <span id="wrong-tests"></span>
        <span id="correct-tests"></span>
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
