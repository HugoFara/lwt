/**
 * Tests for word_dom_updates.ts - DOM updates for word operations
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  getParentContext,
  getFrameElement,
  generateTooltip,
  updateExistingWordInDOM,
  updateWordStatusInDOM,
  markWordWellKnownInDOM,
  markWordIgnoredInDOM,
  type WordUpdateParams
} from '../../../src/frontend/js/modules/vocabulary/services/word_dom_updates';

// Mock dependencies
vi.mock('../../../src/frontend/js/modules/vocabulary/services/word_status', () => ({
  createWordTooltip: vi.fn((word, trans, rom, status) => `${word}|${trans}|${rom}|${status}`)
}));

import { resetSettingsConfig } from '../../../src/frontend/js/shared/utils/settings_config';

describe('word_dom_updates.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    // Reset parent window mock
    delete (window as any).parent;
    // Initialize settings config
    resetSettingsConfig();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // getParentContext Tests
  // ===========================================================================

  describe('getParentContext', () => {
    it('returns current document when parent is not accessible', () => {
      delete (window as any).parent;

      const result = getParentContext();

      expect(result).toBe(document);
    });

    it('returns parent document when accessible', () => {
      const mockParentDocument = { getElementById: vi.fn() };
      (window as any).parent = { document: mockParentDocument };

      const result = getParentContext();

      expect(result).toBe(mockParentDocument);
    });

    it('falls back to current document on error', () => {
      Object.defineProperty(window, 'parent', {
        get() {
          throw new Error('Cross-origin error');
        },
        configurable: true
      });

      const result = getParentContext();

      expect(result).toBe(document);

      // Clean up
      delete (window as any).parent;
    });
  });

  // ===========================================================================
  // getFrameElement Tests
  // ===========================================================================

  describe('getFrameElement', () => {
    it('returns element from parent context', () => {
      document.body.innerHTML = `<div id="frame-l">Frame L</div>`;

      const result = getFrameElement('frame-l');

      expect(result).toBeTruthy();
      expect(result?.id).toBe('frame-l');
    });

    it('returns null when element does not exist', () => {
      document.body.innerHTML = '';

      const result = getFrameElement('frame-l');

      expect(result).toBeNull();
    });
  });

  // ===========================================================================
  // generateTooltip Tests
  // ===========================================================================

  describe('generateTooltip', () => {
    it('generates tooltip with word, translation, romanization and status', () => {
      const result = generateTooltip('word', 'translation', 'romanization', 1);

      // createWordTooltip is mocked to return formatted string
      expect(result).toBe('word|translation|romanization|1');
    });

    it('handles different status values', () => {
      const result = generateTooltip('test', 'translated', 'rom', 99);

      expect(result).toBe('test|translated|rom|99');
    });
  });

  // ===========================================================================
  // updateExistingWordInDOM Tests
  // ===========================================================================

  describe('updateExistingWordInDOM', () => {
    it('updates elements with matching word ID class', () => {
      document.body.innerHTML = `
        <span class="word123 status1">hello</span>
      `;

      const params: WordUpdateParams = {
        wid: 123,
        status: 3,
        translation: 'updated translation',
        romanization: 'updated rom',
        text: 'hello'
      };

      updateExistingWordInDOM(params, 1);

      const element = document.querySelector('.word123')!;
      expect(element.classList.contains('status1')).toBe(false);
      expect(element.classList.contains('status3')).toBe(true);
      expect(element.getAttribute('data_trans')).toBe('updated translation');
      expect(element.getAttribute('data_rom')).toBe('updated rom');
      expect(element.getAttribute('data_status')).toBe('3');
    });
  });

  // ===========================================================================
  // updateWordStatusInDOM Tests
  // ===========================================================================

  describe('updateWordStatusInDOM', () => {
    it('updates word status in frame-l', () => {
      document.body.innerHTML = `
        <div id="frame-l">
          <span class="word456 status2">word</span>
        </div>
      `;

      updateWordStatusInDOM(456, 4, 'word', 'trans', 'rom');

      const element = document.querySelector('.word456')!;
      expect(element.classList.contains('status2')).toBe(false);
      expect(element.classList.contains('status4')).toBe(true);
      expect(element.getAttribute('data_status')).toBe('4');
    });

    it('removes all status classes before adding new one', () => {
      document.body.innerHTML = `
        <div id="frame-l">
          <span class="word456 status98 status99 status1 status2 status3 status4 status5">word</span>
        </div>
      `;

      updateWordStatusInDOM(456, 3, 'word', 'trans', 'rom');

      const element = document.querySelector('.word456')!;
      expect(element.classList.contains('status98')).toBe(false);
      expect(element.classList.contains('status99')).toBe(false);
      expect(element.classList.contains('status1')).toBe(false);
      expect(element.classList.contains('status2')).toBe(false);
      expect(element.classList.contains('status3')).toBe(true);
      expect(element.classList.contains('status4')).toBe(false);
      expect(element.classList.contains('status5')).toBe(false);
    });

    it('does nothing when frame-l does not exist', () => {
      document.body.innerHTML = `
        <span class="word456 status2">word</span>
      `;

      expect(() => updateWordStatusInDOM(456, 4, 'word', 'trans', 'rom')).not.toThrow();
      expect(document.querySelector('.word456')!.classList.contains('status2')).toBe(true);
    });
  });

  // ===========================================================================
  // markWordWellKnownInDOM Tests
  // ===========================================================================

  describe('markWordWellKnownInDOM', () => {
    it('marks word as well-known (status 99)', () => {
      document.body.innerHTML = `
        <div id="frame-l">
          <span class="status0" data_hex="48454c4c4f">hello</span>
        </div>
      `;

      markWordWellKnownInDOM(111, '48454c4c4f', 'hello');

      const element = document.querySelector('[data_hex="48454c4c4f"]')!;
      expect(element.classList.contains('status0')).toBe(false);
      expect(element.classList.contains('status99')).toBe(true);
      expect(element.classList.contains('word111')).toBe(true);
      expect(element.getAttribute('data_status')).toBe('99');
      expect(element.getAttribute('data_wid')).toBe('111');
    });

    it('does nothing when frame-l does not exist', () => {
      document.body.innerHTML = `
        <span class="status0" data_hex="48454c4c4f">hello</span>
      `;

      markWordWellKnownInDOM(111, '48454c4c4f', 'hello');

      expect(document.querySelector('[data_hex="48454c4c4f"]')!.classList.contains('status0')).toBe(true);
    });
  });

  // ===========================================================================
  // markWordIgnoredInDOM Tests
  // ===========================================================================

  describe('markWordIgnoredInDOM', () => {
    it('marks word as ignored (status 98)', () => {
      document.body.innerHTML = `
        <div id="frame-l">
          <span class="status0" data_hex="48454c4c4f">hello</span>
        </div>
      `;

      markWordIgnoredInDOM(222, '48454c4c4f', 'hello');

      const element = document.querySelector('[data_hex="48454c4c4f"]')!;
      expect(element.classList.contains('status0')).toBe(false);
      expect(element.classList.contains('status98')).toBe(true);
      expect(element.classList.contains('word222')).toBe(true);
      expect(element.getAttribute('data_status')).toBe('98');
      expect(element.getAttribute('data_wid')).toBe('222');
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles string status values', () => {
      document.body.innerHTML = `
        <span class="word888 status1">word</span>
      `;

      const params: WordUpdateParams = {
        wid: 888,
        status: '99',
        translation: 'trans',
        romanization: 'rom',
        text: 'word'
      };

      updateExistingWordInDOM(params, '1');

      expect(document.querySelector('.word888')!.classList.contains('status99')).toBe(true);
      expect(document.querySelector('.word888')!.getAttribute('data_status')).toBe('99');
    });

    it('handles multiple elements with same word ID', () => {
      document.body.innerHTML = `
        <span class="word100 status2">test</span>
        <span class="word100 status2">test</span>
        <span class="word100 status2">test</span>
      `;

      const params: WordUpdateParams = {
        wid: 100,
        status: 4,
        translation: 'updated',
        romanization: 'rom',
        text: 'test'
      };

      updateExistingWordInDOM(params, 2);

      const elements = document.querySelectorAll('.word100');
      expect(elements.length).toBe(3);
      elements.forEach(el => {
        expect(el.classList.contains('status4')).toBe(true);
        expect(el.getAttribute('data_trans')).toBe('updated');
      });
    });
  });
});
