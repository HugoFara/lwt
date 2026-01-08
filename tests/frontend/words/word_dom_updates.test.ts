/**
 * Tests for word_dom_updates.ts - DOM updates for word operations
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  getParentContext,
  getFrameElement,
  updateLearnStatus,
  generateTooltip,
  updateNewWordInDOM,
  updateExistingWordInDOM,
  updateWordStatusInDOM,
  deleteWordFromDOM,
  markWordWellKnownInDOM,
  markWordIgnoredInDOM,
  updateMultiWordInDOM,
  deleteMultiWordFromDOM,
  updateBulkWordInDOM,
  updateHoverSaveInDOM,
  updateTestWordInDOM,
  completeWordOperation,
  type WordUpdateParams,
  type BulkWordUpdateParams
} from '../../../src/frontend/js/modules/vocabulary/services/word_dom_updates';

// Mock dependencies
vi.mock('../../../src/frontend/js/modules/vocabulary/services/word_status', () => ({
  createWordTooltip: vi.fn((word, trans, rom, status) => `${word}|${trans}|${rom}|${status}`)
}));

vi.mock('../../../src/frontend/js/modules/text/pages/reading/frame_management', () => ({
  cleanupRightFrames: vi.fn()
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
  // updateLearnStatus Tests
  // ===========================================================================

  describe('updateLearnStatus', () => {
    it('updates #learnstatus element with content', () => {
      document.body.innerHTML = `<div id="learnstatus">Old content</div>`;

      updateLearnStatus('<span>New content</span>');

      expect(document.querySelector('#learnstatus')!.innerHTML).toBe('<span>New content</span>');
    });

    it('does nothing when element does not exist', () => {
      document.body.innerHTML = '';

      expect(() => updateLearnStatus('content')).not.toThrow();
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
  // updateNewWordInDOM Tests
  // ===========================================================================

  describe('updateNewWordInDOM', () => {
    it('updates elements with matching hex class', () => {
      document.body.innerHTML = `
        <span class="TERM48454c4c4f status0">hello</span>
        <span class="TERM48454c4c4f status0">hello</span>
      `;

      const params: WordUpdateParams = {
        wid: 123,
        status: 2,
        translation: 'bonjour',
        romanization: '',
        text: 'hello',
        hex: '48454c4c4f'
      };

      updateNewWordInDOM(params);

      const elements = document.querySelectorAll('.TERM48454c4c4f');
      elements.forEach(el => {
        expect(el.classList.contains('status0')).toBe(false);
        expect(el.classList.contains('status2')).toBe(true);
        expect(el.classList.contains('word123')).toBe(true);
        expect(el.getAttribute('data_trans')).toBe('bonjour');
        expect(el.getAttribute('data_wid')).toBe('123');
      });
    });

    it('does nothing when hex is not provided', () => {
      document.body.innerHTML = `
        <span class="TERM48454c4c4f status0">hello</span>
      `;

      const params: WordUpdateParams = {
        wid: 123,
        status: 2,
        translation: 'bonjour',
        romanization: '',
        text: 'hello'
      };

      updateNewWordInDOM(params);

      expect(document.querySelector('.TERM48454c4c4f')!.classList.contains('status0')).toBe(true);
    });

    it('sets title attribute with generated tooltip', () => {
      document.body.innerHTML = `
        <span class="TERM48454c4c4f status0">hello</span>
      `;

      const params: WordUpdateParams = {
        wid: 123,
        status: 2,
        translation: 'bonjour',
        romanization: 'bɔ̃ʒuʁ',
        text: 'hello',
        hex: '48454c4c4f'
      };

      updateNewWordInDOM(params);

      // generateTooltip returns formatted tooltip string
      expect(document.querySelector('.TERM48454c4c4f')!.getAttribute('title')).toBe('hello|bonjour|bɔ̃ʒuʁ|2');
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
  // deleteWordFromDOM Tests
  // ===========================================================================

  describe('deleteWordFromDOM', () => {
    it('resets word to status0', () => {
      document.body.innerHTML = `
        <span class="word789 status3" data_trans="old trans" data_rom="old rom" data_wid="789" data_img="img.png">word</span>
      `;

      deleteWordFromDOM(789, 'word');

      const element = document.querySelector('span')!;
      expect(element.classList.contains('word789')).toBe(false);
      expect(element.classList.contains('status3')).toBe(false);
      expect(element.classList.contains('status0')).toBe(true);
      expect(element.getAttribute('data_status')).toBe('0');
      expect(element.getAttribute('data_trans')).toBe('');
      expect(element.getAttribute('data_rom')).toBe('');
      expect(element.getAttribute('data_wid')).toBe('');
      expect(element.getAttribute('data_img')).toBeNull();
    });

    it('removes all status classes', () => {
      document.body.innerHTML = `
        <span class="word789 status99 status98 status1 status2 status3 status4 status5">word</span>
      `;

      deleteWordFromDOM(789, 'word');

      const element = document.querySelector('span')!;
      expect(element.classList.contains('status99')).toBe(false);
      expect(element.classList.contains('status98')).toBe(false);
      expect(element.classList.contains('status1')).toBe(false);
      expect(element.classList.contains('status2')).toBe(false);
      expect(element.classList.contains('status3')).toBe(false);
      expect(element.classList.contains('status4')).toBe(false);
      expect(element.classList.contains('status5')).toBe(false);
      expect(element.classList.contains('status0')).toBe(true);
    });
  });

  // ===========================================================================
  // markWordWellKnownInDOM Tests
  // ===========================================================================

  describe('markWordWellKnownInDOM', () => {
    it('marks word as well-known (status 99)', () => {
      document.body.innerHTML = `
        <div id="frame-l">
          <span class="TERM48454c4c4f status0">hello</span>
        </div>
      `;

      markWordWellKnownInDOM(111, '48454c4c4f', 'hello');

      const element = document.querySelector('.TERM48454c4c4f')!;
      expect(element.classList.contains('status0')).toBe(false);
      expect(element.classList.contains('status99')).toBe(true);
      expect(element.classList.contains('word111')).toBe(true);
      expect(element.getAttribute('data_status')).toBe('99');
      expect(element.getAttribute('data_wid')).toBe('111');
    });

    it('does nothing when frame-l does not exist', () => {
      document.body.innerHTML = `
        <span class="TERM48454c4c4f status0">hello</span>
      `;

      markWordWellKnownInDOM(111, '48454c4c4f', 'hello');

      expect(document.querySelector('.TERM48454c4c4f')!.classList.contains('status0')).toBe(true);
    });
  });

  // ===========================================================================
  // markWordIgnoredInDOM Tests
  // ===========================================================================

  describe('markWordIgnoredInDOM', () => {
    it('marks word as ignored (status 98)', () => {
      document.body.innerHTML = `
        <div id="frame-l">
          <span class="TERM48454c4c4f status0">hello</span>
        </div>
      `;

      markWordIgnoredInDOM(222, '48454c4c4f', 'hello');

      const element = document.querySelector('.TERM48454c4c4f')!;
      expect(element.classList.contains('status0')).toBe(false);
      expect(element.classList.contains('status98')).toBe(true);
      expect(element.classList.contains('word222')).toBe(true);
      expect(element.getAttribute('data_status')).toBe('98');
      expect(element.getAttribute('data_wid')).toBe('222');
    });
  });

  // ===========================================================================
  // updateMultiWordInDOM Tests
  // ===========================================================================

  describe('updateMultiWordInDOM', () => {
    it('updates multi-word expression attributes', () => {
      document.body.innerHTML = `
        <span class="word333 status2">hello world</span>
      `;

      updateMultiWordInDOM(333, 'hello world', 'bonjour monde', 'rom', 4, 2);

      const element = document.querySelector('.word333')!;
      expect(element.classList.contains('status2')).toBe(false);
      expect(element.classList.contains('status4')).toBe(true);
      expect(element.getAttribute('data_trans')).toBe('bonjour monde');
      expect(element.getAttribute('data_rom')).toBe('rom');
      expect(element.getAttribute('data_status')).toBe('4');
    });
  });

  // ===========================================================================
  // deleteMultiWordFromDOM Tests
  // ===========================================================================

  describe('deleteMultiWordFromDOM', () => {
    it('removes multi-word elements and shows sub-words', () => {
      document.body.innerHTML = `
        <div id="sentence1">
          <span class="word444">hello world</span>
          <span class="hide">hello</span>
          <span class="hide">world</span>
        </div>
      `;

      deleteMultiWordFromDOM(444, false);

      expect(document.querySelectorAll('.word444').length).toBe(0);
      expect(document.querySelectorAll('.hide').length).toBe(0);
    });

    it('removes multi-word elements when showAll is true', () => {
      document.body.innerHTML = `
        <div id="sentence1">
          <span class="word444">hello world</span>
          <span class="hide">hello</span>
        </div>
      `;

      deleteMultiWordFromDOM(444, true);

      expect(document.querySelectorAll('.word444').length).toBe(0);
      // When showAll is true, hidden elements are not unhidden
      expect(document.querySelectorAll('.hide').length).toBe(1);
    });
  });

  // ===========================================================================
  // updateBulkWordInDOM Tests
  // ===========================================================================

  describe('updateBulkWordInDOM', () => {
    it('updates word from bulk translate', () => {
      document.body.innerHTML = `
        <span class="TERM48454c4c4f status0">hello</span>
      `;

      const term: BulkWordUpdateParams = {
        WoID: 555,
        WoTextLC: 'hello',
        WoStatus: 3,
        translation: 'bonjour',
        hex: '48454c4c4f'
      };

      updateBulkWordInDOM(term, true);

      const element = document.querySelector('.TERM48454c4c4f')!;
      expect(element.classList.contains('status0')).toBe(false);
      expect(element.classList.contains('status3')).toBe(true);
      expect(element.classList.contains('word555')).toBe(true);
      expect(element.getAttribute('data_wid')).toBe('555');
      expect(element.getAttribute('data_trans')).toBe('bonjour');
    });

    it('sets empty title when useTooltip is false', () => {
      document.body.innerHTML = `
        <span class="TERM48454c4c4f status0" title="old title">hello</span>
      `;

      const term: BulkWordUpdateParams = {
        WoID: 555,
        WoTextLC: 'hello',
        WoStatus: 3,
        translation: 'bonjour',
        hex: '48454c4c4f'
      };

      updateBulkWordInDOM(term, false);

      expect(document.querySelector('.TERM48454c4c4f')!.getAttribute('title')).toBe('');
    });
  });

  // ===========================================================================
  // updateHoverSaveInDOM Tests
  // ===========================================================================

  describe('updateHoverSaveInDOM', () => {
    it('updates word after hover save operation', () => {
      document.body.innerHTML = `
        <span class="TERM48454c4c4f status0">hello</span>
      `;

      updateHoverSaveInDOM(666, '48454c4c4f', 1, 'quick trans', 'hello');

      const element = document.querySelector('.TERM48454c4c4f')!;
      expect(element.classList.contains('status0')).toBe(false);
      expect(element.classList.contains('status1')).toBe(true);
      expect(element.classList.contains('word666')).toBe(true);
      expect(element.getAttribute('data_trans')).toBe('quick trans');
      expect(element.getAttribute('data_wid')).toBe('666');
    });

    it('sets title with generated tooltip', () => {
      document.body.innerHTML = `
        <span class="TERM48454c4c4f status0">hello</span>
      `;

      updateHoverSaveInDOM(666, '48454c4c4f', 1, 'quick trans', 'hello');

      // Title is set with formatted tooltip
      expect(document.querySelector('.TERM48454c4c4f')!.getAttribute('title')).toBe('hello|quick trans||1');
    });
  });

  // ===========================================================================
  // updateTestWordInDOM Tests
  // ===========================================================================

  describe('updateTestWordInDOM', () => {
    it('updates word data attributes for test results', () => {
      document.body.innerHTML = `
        <span class="word777">test word</span>
      `;

      updateTestWordInDOM(777, 'test word', 'new trans', 'new rom', 5);

      const element = document.querySelector('.word777')!;
      expect(element.getAttribute('data_text')).toBe('test word');
      expect(element.getAttribute('data_trans')).toBe('new trans');
      expect(element.getAttribute('data_rom')).toBe('new rom');
      expect(element.getAttribute('data_status')).toBe('5');
    });
  });

  // ===========================================================================
  // completeWordOperation Tests
  // ===========================================================================

  describe('completeWordOperation', () => {
    it('updates learn status and cleans up frames', async () => {
      const { cleanupRightFrames } = await import('../../../src/frontend/js/modules/text/pages/reading/frame_management');

      document.body.innerHTML = `<div id="learnstatus">old</div>`;

      completeWordOperation('<span>5 words to learn</span>');

      expect(document.querySelector('#learnstatus')!.innerHTML).toBe('<span>5 words to learn</span>');
      expect(cleanupRightFrames).toHaveBeenCalled();
    });

    it('skips cleanup when shouldCleanup is false', async () => {
      const { cleanupRightFrames } = await import('../../../src/frontend/js/modules/text/pages/reading/frame_management');

      document.body.innerHTML = `<div id="learnstatus">old</div>`;

      completeWordOperation('content', false);

      expect(cleanupRightFrames).not.toHaveBeenCalled();
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

    it('handles missing annotation data in deleteWordFromDOM', () => {
      document.body.innerHTML = `
        <span class="word999 status3">word</span>
      `;

      deleteWordFromDOM(999, 'word');

      // Should not throw even without data_ann attribute
      expect(document.querySelectorAll('.word999').length).toBe(0);
      expect(document.querySelectorAll('.status0').length).toBe(1);
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
