/**
 * Tests for test_mode.ts - Event handlers for vocabulary testing
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  handleTestWordClick,
  handleTestKeydown
} from '../../../src/frontend/js/modules/review/pages/test_mode';

// Mock dependencies
vi.mock('../../../src/frontend/js/modules/vocabulary/services/word_popup_interface', () => ({
  showTestWordPopup: vi.fn()
}));

vi.mock('../../../src/frontend/js/modules/text/pages/reading/frame_management', () => ({
  loadModalFrame: vi.fn(),
  cleanupRightFrames: vi.fn()
}));

import { showTestWordPopup } from '../../../src/frontend/js/modules/vocabulary/services/word_popup_interface';
import { loadModalFrame, cleanupRightFrames } from '../../../src/frontend/js/modules/text/pages/reading/frame_management';
import {
  setCurrentWordId,
  setTestSolution,
  setAnswerOpened,
  resetTestState
} from '../../../src/frontend/js/modules/review/stores/test_state';
import {
  setDictionaryLinks,
  resetLanguageConfig
} from '../../../src/frontend/js/modules/language/stores/language_config';

describe('test_mode.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();

    // Initialize test state
    setCurrentWordId(123);
    setTestSolution('Test Solution');
    setAnswerOpened(false);

    // Initialize dictionary links
    setDictionaryLinks({
      dict1: 'http://dict1.com',
      dict2: 'http://dict2.com',
      translator: 'http://translate.com'
    });
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
    resetTestState();
    resetLanguageConfig();
  });

  // ===========================================================================
  // handleTestWordClick Tests
  // ===========================================================================

  describe('handleTestWordClick', () => {
    it('calls showTestWordPopup with word data attributes', () => {
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
      handleTestWordClick.call(wordEl as HTMLElement);

      expect(showTestWordPopup).toHaveBeenCalledWith(
        'http://dict1.com',
        'http://dict2.com',
        'http://translate.com',
        '456',
        'Hello',
        'Bonjour',
        'helo',
        '2',
        'Hello world',
        5
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
      handleTestWordClick.call(wordEl as HTMLElement);

      expect(document.querySelector('.todo')?.textContent).toBe('Test Solution');
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
      const result = handleTestWordClick.call(wordEl as HTMLElement);

      expect(result).toBe(false);
    });

    it('handles missing data attributes gracefully', () => {
      document.body.innerHTML = `
        <span class="word">NoData</span>
        <span class="todo"></span>
      `;

      const wordEl = document.querySelector('.word')!;

      expect(() => {
        handleTestWordClick.call(wordEl as HTMLElement);
      }).not.toThrow();

      expect(showTestWordPopup).toHaveBeenCalledWith(
        'http://dict1.com',
        'http://dict2.com',
        'http://translate.com',
        '',
        '',
        '',
        '',
        '',
        '',
        NaN  // parseInt on empty string
      );
    });
  });

  // ===========================================================================
  // handleTestKeydown Tests
  // ===========================================================================

  describe('handleTestKeydown', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <span class="word" data_wid="123" data_status="2">Test</span>
      `;
    });

    /**
     * Helper to create native KeyboardEvent with deprecated keyCode/which support.
     */
    function createKeyEvent(key: string, keyCode: number): KeyboardEvent {
      const event = new KeyboardEvent('keydown', {
        key,
        keyCode,
        which: keyCode,
        bubbles: true
      } as KeyboardEventInit);
      // TypeScript doesn't allow `which` in KeyboardEventInit, but JSDOM supports it
      Object.defineProperty(event, 'which', { value: keyCode });
      return event;
    }

    describe('Space key', () => {
      it('shows solution when answer not opened', () => {
        setAnswerOpened(false);

        const event = createKeyEvent(' ', 32);
        const result = handleTestKeydown(event);

        expect(cleanupRightFrames).toHaveBeenCalled();
        expect(loadModalFrame).toHaveBeenCalledWith('/word/show?wid=123&ann=');
        expect(result).toBe(false);
      });

      it('does nothing when answer already opened', () => {
        setAnswerOpened(true);

        const event = createKeyEvent(' ', 32);
        const result = handleTestKeydown(event);

        expect(cleanupRightFrames).not.toHaveBeenCalled();
        expect(result).toBe(true);
      });
    });

    describe('Escape key', () => {
      it('skips term without changing status', () => {
        const event = createKeyEvent('Escape', 27);
        const result = handleTestKeydown(event);

        expect(loadModalFrame).toHaveBeenCalledWith(
          expect.stringContaining('/word/set-test-status?wid=123&status=2')
        );
        expect(result).toBe(false);
      });
    });

    describe('I key', () => {
      it('sets status to ignored (98)', () => {
        const event = createKeyEvent('I', 73);
        const result = handleTestKeydown(event);

        expect(loadModalFrame).toHaveBeenCalledWith('/word/set-test-status?wid=123&status=98');
        expect(result).toBe(false);
      });
    });

    describe('W key', () => {
      it('sets status to well-known (99)', () => {
        const event = createKeyEvent('W', 87);
        const result = handleTestKeydown(event);

        expect(loadModalFrame).toHaveBeenCalledWith('/word/set-test-status?wid=123&status=99');
        expect(result).toBe(false);
      });
    });

    describe('E key', () => {
      it('opens edit word form', () => {
        const event = createKeyEvent('E', 69);
        const result = handleTestKeydown(event);

        expect(loadModalFrame).toHaveBeenCalledWith('/word/edit-term?wid=123');
        expect(result).toBe(false);
      });
    });

    describe('Arrow Up key (requires answer opened)', () => {
      it('does nothing when answer not opened', () => {
        setAnswerOpened(false);

        const event = createKeyEvent('ArrowUp', 38);
        const result = handleTestKeydown(event);

        expect(loadModalFrame).not.toHaveBeenCalled();
        expect(result).toBe(true);
      });

      it('increases status when answer opened', () => {
        setAnswerOpened(true);

        const event = createKeyEvent('ArrowUp', 38);
        const result = handleTestKeydown(event);

        expect(loadModalFrame).toHaveBeenCalledWith('/word/set-test-status?wid=123&stchange=1');
        expect(result).toBe(false);
      });
    });

    describe('Arrow Down key (requires answer opened)', () => {
      it('does nothing when answer not opened', () => {
        setAnswerOpened(false);

        const event = createKeyEvent('ArrowDown', 40);
        const result = handleTestKeydown(event);

        expect(loadModalFrame).not.toHaveBeenCalled();
        expect(result).toBe(true);
      });

      it('decreases status when answer opened', () => {
        setAnswerOpened(true);

        const event = createKeyEvent('ArrowDown', 40);
        const result = handleTestKeydown(event);

        expect(loadModalFrame).toHaveBeenCalledWith('/word/set-test-status?wid=123&stchange=-1');
        expect(result).toBe(false);
      });
    });

    describe('Number keys 1-5 (requires answer opened)', () => {
      it.each([
        ['1', 49, 1],
        ['2', 50, 2],
        ['3', 51, 3],
        ['4', 52, 4],
        ['5', 53, 5]
      ])('sets status to %i when %i key pressed', (key, keyCode, expectedStatus) => {
        setAnswerOpened(true);

        const event = createKeyEvent(key, keyCode);
        const result = handleTestKeydown(event);

        expect(loadModalFrame).toHaveBeenCalledWith(`/word/set-test-status?wid=123&status=${expectedStatus}`);
        expect(result).toBe(false);
      });

      it('does nothing when answer not opened', () => {
        setAnswerOpened(false);

        const event = createKeyEvent('1', 49);
        const result = handleTestKeydown(event);

        expect(loadModalFrame).not.toHaveBeenCalled();
        expect(result).toBe(true);
      });

      it.each([
        ['1', 97, 1],  // numpad 1
        ['2', 98, 2],  // numpad 2
        ['3', 99, 3],  // numpad 3
        ['4', 100, 4], // numpad 4
        ['5', 101, 5]  // numpad 5
      ])('handles numpad key %s (keyCode %i)', (key, keyCode, expectedStatus) => {
        setAnswerOpened(true);

        const event = createKeyEvent(key, keyCode);
        const result = handleTestKeydown(event);

        expect(loadModalFrame).toHaveBeenCalledWith(`/word/set-test-status?wid=123&status=${expectedStatus}`);
        expect(result).toBe(false);
      });
    });

    describe('Unhandled keys', () => {
      it('returns true for unhandled keys', () => {
        const event = createKeyEvent('A', 65);
        const result = handleTestKeydown(event);

        expect(loadModalFrame).not.toHaveBeenCalled();
        expect(result).toBe(true);
      });
    });
  });
});
