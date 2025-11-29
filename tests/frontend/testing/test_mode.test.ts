/**
 * Tests for test_mode.ts - Event handlers for vocabulary testing
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import $ from 'jquery';
import {
  word_click_event_do_test_test,
  keydown_event_do_test_test
} from '../../../src/frontend/js/testing/test_mode';

// Mock dependencies
vi.mock('../../../src/frontend/js/core/lwt_state', () => ({
  LWT_DATA: {
    language: {
      dict_link1: 'http://dict1.com',
      dict_link2: 'http://dict2.com',
      translator_link: 'http://translate.com'
    },
    word: {
      id: '123'
    },
    test: {
      solution: 'Test Solution',
      answer_opened: false
    }
  }
}));

vi.mock('../../../src/frontend/js/terms/overlib_interface', () => ({
  run_overlib_test: vi.fn()
}));

vi.mock('../../../src/frontend/js/reading/frame_management', () => ({
  showRightFrames: vi.fn(),
  cleanupRightFrames: vi.fn()
}));

import { LWT_DATA } from '../../../src/frontend/js/core/lwt_state';
import { run_overlib_test } from '../../../src/frontend/js/terms/overlib_interface';
import { showRightFrames, cleanupRightFrames } from '../../../src/frontend/js/reading/frame_management';

describe('test_mode.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    // Reset test state
    LWT_DATA.test.answer_opened = false;
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // word_click_event_do_test_test Tests
  // ===========================================================================

  describe('word_click_event_do_test_test', () => {
    it('calls run_overlib_test with word data attributes', () => {
      document.body.innerHTML = `
        <span class="word"
          data_wid="456"
          data_text="Hello"
          data_trans="Bonjour"
          data_rom="helo"
          data_status="2"
          data_sent="Hello world"
          data_todo="5"
        >Hello</span>
        <span class="todo"></span>
      `;

      const wordEl = document.querySelector('.word')!;
      word_click_event_do_test_test.call(wordEl as HTMLElement);

      expect(run_overlib_test).toHaveBeenCalledWith(
        'http://dict1.com',
        'http://dict2.com',
        'http://translate.com',
        '456',
        'Hello',
        'Bonjour',
        'helo',
        '2',
        'Hello world',
        5,
        null
      );
    });

    it('updates .todo element with solution', () => {
      document.body.innerHTML = `
        <span class="word"
          data_wid="1"
          data_text="Test"
          data_trans=""
          data_rom=""
          data_status="1"
          data_sent=""
          data_todo="1"
        >Test</span>
        <span class="todo">Previous</span>
      `;

      const wordEl = document.querySelector('.word')!;
      word_click_event_do_test_test.call(wordEl as HTMLElement);

      expect($('.todo').text()).toBe('Test Solution');
    });

    it('returns false', () => {
      document.body.innerHTML = `
        <span class="word"
          data_wid="1"
          data_text="Test"
          data_trans=""
          data_rom=""
          data_status="1"
          data_sent=""
          data_todo="1"
        >Test</span>
        <span class="todo"></span>
      `;

      const wordEl = document.querySelector('.word')!;
      const result = word_click_event_do_test_test.call(wordEl as HTMLElement);

      expect(result).toBe(false);
    });

    it('handles missing data attributes gracefully', () => {
      document.body.innerHTML = `
        <span class="word">NoData</span>
        <span class="todo"></span>
      `;

      const wordEl = document.querySelector('.word')!;

      expect(() => {
        word_click_event_do_test_test.call(wordEl as HTMLElement);
      }).not.toThrow();

      expect(run_overlib_test).toHaveBeenCalledWith(
        'http://dict1.com',
        'http://dict2.com',
        'http://translate.com',
        '',
        '',
        '',
        '',
        '',
        '',
        NaN,  // parseInt on empty string
        null
      );
    });
  });

  // ===========================================================================
  // keydown_event_do_test_test Tests
  // ===========================================================================

  describe('keydown_event_do_test_test', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <span class="word" data_wid="123" data_status="2">Test</span>
      `;
    });

    describe('Space key', () => {
      it('shows solution when answer not opened', () => {
        LWT_DATA.test.answer_opened = false;

        const event = $.Event('keydown', { key: 'Space', which: 32 }) as JQuery.KeyDownEvent;
        const result = keydown_event_do_test_test(event);

        expect(cleanupRightFrames).toHaveBeenCalled();
        expect(showRightFrames).toHaveBeenCalledWith('show_word.php?wid=123&ann=');
        expect(LWT_DATA.test.answer_opened).toBe(true);
        expect(result).toBe(false);
      });

      it('does nothing when answer already opened', () => {
        LWT_DATA.test.answer_opened = true;

        const event = $.Event('keydown', { key: 'Space', which: 32 }) as JQuery.KeyDownEvent;
        const result = keydown_event_do_test_test(event);

        expect(cleanupRightFrames).not.toHaveBeenCalled();
        expect(result).toBe(true);
      });
    });

    describe('Escape key', () => {
      it('skips term without changing status', () => {
        const event = $.Event('keydown', { key: 'Escape', which: 27 }) as JQuery.KeyDownEvent;
        const result = keydown_event_do_test_test(event);

        expect(showRightFrames).toHaveBeenCalledWith(
          expect.stringContaining('set_test_status.php?wid=123&status=2')
        );
        expect(result).toBe(false);
      });
    });

    describe('I key', () => {
      it('sets status to ignored (98)', () => {
        const event = $.Event('keydown', { key: 'I', which: 73 }) as JQuery.KeyDownEvent;
        const result = keydown_event_do_test_test(event);

        expect(showRightFrames).toHaveBeenCalledWith('set_test_status.php?wid=123&status=98');
        expect(result).toBe(false);
      });
    });

    describe('W key', () => {
      it('sets status to well-known (99)', () => {
        const event = $.Event('keydown', { key: 'W', which: 87 }) as JQuery.KeyDownEvent;
        const result = keydown_event_do_test_test(event);

        expect(showRightFrames).toHaveBeenCalledWith('set_test_status.php?wid=123&status=99');
        expect(result).toBe(false);
      });
    });

    describe('E key', () => {
      it('opens edit word form', () => {
        const event = $.Event('keydown', { key: 'E', which: 69 }) as JQuery.KeyDownEvent;
        const result = keydown_event_do_test_test(event);

        expect(showRightFrames).toHaveBeenCalledWith('edit_tword.php?wid=123');
        expect(result).toBe(false);
      });
    });

    describe('Arrow Up key (requires answer opened)', () => {
      it('does nothing when answer not opened', () => {
        LWT_DATA.test.answer_opened = false;

        const event = $.Event('keydown', { key: 'ArrowUp', which: 38 }) as JQuery.KeyDownEvent;
        const result = keydown_event_do_test_test(event);

        expect(showRightFrames).not.toHaveBeenCalled();
        expect(result).toBe(true);
      });

      it('increases status when answer opened', () => {
        LWT_DATA.test.answer_opened = true;

        const event = $.Event('keydown', { key: 'ArrowUp', which: 38 }) as JQuery.KeyDownEvent;
        const result = keydown_event_do_test_test(event);

        expect(showRightFrames).toHaveBeenCalledWith('set_test_status.php?wid=123&stchange=1');
        expect(result).toBe(false);
      });
    });

    describe('Arrow Down key (requires answer opened)', () => {
      it('does nothing when answer not opened', () => {
        LWT_DATA.test.answer_opened = false;

        const event = $.Event('keydown', { key: 'ArrowDown', which: 40 }) as JQuery.KeyDownEvent;
        const result = keydown_event_do_test_test(event);

        expect(showRightFrames).not.toHaveBeenCalled();
        expect(result).toBe(true);
      });

      it('decreases status when answer opened', () => {
        LWT_DATA.test.answer_opened = true;

        const event = $.Event('keydown', { key: 'ArrowDown', which: 40 }) as JQuery.KeyDownEvent;
        const result = keydown_event_do_test_test(event);

        expect(showRightFrames).toHaveBeenCalledWith('set_test_status.php?wid=123&stchange=-1');
        expect(result).toBe(false);
      });
    });

    describe('Number keys 1-5 (requires answer opened)', () => {
      it.each([
        [1, 49, 1],
        [2, 50, 2],
        [3, 51, 3],
        [4, 52, 4],
        [5, 53, 5]
      ])('sets status to %i when %i key pressed', (_num, keyCode, expectedStatus) => {
        LWT_DATA.test.answer_opened = true;

        const event = $.Event('keydown', { which: keyCode }) as JQuery.KeyDownEvent;
        const result = keydown_event_do_test_test(event);

        expect(showRightFrames).toHaveBeenCalledWith(`set_test_status.php?wid=123&status=${expectedStatus}`);
        expect(result).toBe(false);
      });

      it('does nothing when answer not opened', () => {
        LWT_DATA.test.answer_opened = false;

        const event = $.Event('keydown', { which: 49 }) as JQuery.KeyDownEvent;  // 1 key
        const result = keydown_event_do_test_test(event);

        expect(showRightFrames).not.toHaveBeenCalled();
        expect(result).toBe(true);
      });

      it.each([
        [1, 97, 1],  // numpad 1
        [2, 98, 2],  // numpad 2
        [3, 99, 3],  // numpad 3
        [4, 100, 4], // numpad 4
        [5, 101, 5]  // numpad 5
      ])('handles numpad key %i (keyCode %i)', (_num, keyCode, expectedStatus) => {
        LWT_DATA.test.answer_opened = true;

        const event = $.Event('keydown', { which: keyCode }) as JQuery.KeyDownEvent;
        const result = keydown_event_do_test_test(event);

        expect(showRightFrames).toHaveBeenCalledWith(`set_test_status.php?wid=123&status=${expectedStatus}`);
        expect(result).toBe(false);
      });
    });

    describe('Unhandled keys', () => {
      it('returns true for unhandled keys', () => {
        const event = $.Event('keydown', { key: 'A', which: 65 }) as JQuery.KeyDownEvent;
        const result = keydown_event_do_test_test(event);

        expect(showRightFrames).not.toHaveBeenCalled();
        expect(result).toBe(true);
      });
    });
  });
});
