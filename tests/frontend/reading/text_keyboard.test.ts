/**
 * Tests for text_keyboard.ts - Keyboard navigation and shortcuts for text reading
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Use vi.hoisted to define mock functions that will be available during vi.mock hoisting
const { mockSpeechDispatcher, mockOwin, mockCClick, mockScrollTo, mockNewPosition, mockTermsApiSetStatus, mockTermsApiCreateQuick } = vi.hoisted(() => ({
  mockSpeechDispatcher: vi.fn(),
  mockOwin: vi.fn(),
  mockCClick: vi.fn(),
  mockScrollTo: vi.fn(),
  mockNewPosition: vi.fn(),
  mockTermsApiSetStatus: vi.fn().mockResolvedValue({ data: { status: 1 } }),
  mockTermsApiCreateQuick: vi.fn().mockResolvedValue({ data: { term_id: 999 } })
}));

// Mock dependencies
vi.mock('../../../src/frontend/js/modules/vocabulary/services/dictionary', () => ({
  getLangFromDict: vi.fn().mockReturnValue('en'),
  createTheDictUrl: vi.fn().mockReturnValue('http://dict.example.com/word'),
  owin: mockOwin
}));

vi.mock('../../../src/frontend/js/shared/utils/user_interactions', () => ({
  speechDispatcher: mockSpeechDispatcher
}));

vi.mock('../../../src/frontend/js/modules/text/pages/reading/text_annotations', () => ({
  getAttrElement: vi.fn((el: HTMLElement, attr: string) => {
    return el.getAttribute(attr) || '';
  })
}));

vi.mock('../../../src/frontend/js/modules/vocabulary/components/word_popup', () => ({
  closePopup: mockCClick
}));


vi.mock('../../../src/frontend/js/modules/vocabulary/api/terms_api', () => ({
  TermsApi: {
    setStatus: mockTermsApiSetStatus,
    createQuick: mockTermsApiCreateQuick
  }
}));

vi.mock('../../../src/frontend/js/modules/vocabulary/services/word_dom_updates', () => ({
  updateExistingWordInDOM: vi.fn(),
  markWordWellKnownInDOM: vi.fn(),
  markWordIgnoredInDOM: vi.fn()
}));

vi.mock('../../../src/frontend/js/modules/vocabulary/stores/word_store', () => ({
  getWordStore: vi.fn(() => ({ isModalOpen: false }))
}));

vi.mock('../../../src/frontend/js/modules/vocabulary/stores/word_form_store', () => ({
  getWordFormStore: vi.fn(() => ({ loadForEdit: vi.fn() }))
}));

vi.mock('../../../src/frontend/js/shared/utils/ajax_utilities', () => ({
  getPositionFromId: vi.fn((id: string) => parseInt(id.replace(/\D/g, ''), 10) || 0)
}));

vi.mock('../../../src/frontend/js/shared/utils/hover_intent', () => ({
  scrollTo: mockScrollTo
}));

vi.mock('../../../src/frontend/js/media/html5_audio_player', () => ({
  lwt_audio_controller: {
    newPosition: mockNewPosition
  }
}));

import { handleTextKeydown } from '../../../src/frontend/js/modules/text/pages/reading/text_keyboard';
import {
  getReadingPosition,
  setReadingPosition,
  resetReadingPosition
} from '../../../src/frontend/js/modules/text/stores/reading_state';
import { initLanguageConfig, resetLanguageConfig, getLanguageId } from '../../../src/frontend/js/modules/language/stores/language_config';
import { initTextConfig, resetTextConfig } from '../../../src/frontend/js/modules/text/stores/text_config';
import { initSettingsConfig, resetSettingsConfig } from '../../../src/frontend/js/shared/utils/settings_config';

/**
 * Helper to create a KeyboardEvent
 */
function createKeyEvent(keyCode: number): KeyboardEvent {
  return new KeyboardEvent('keydown', {
    which: keyCode,
    keyCode: keyCode,
    bubbles: true,
    cancelable: true
  } as KeyboardEventInit);
}

describe('text_keyboard.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';

    // Reset and initialize state modules
    resetLanguageConfig();
    resetTextConfig();
    resetSettingsConfig();
    initLanguageConfig({
      id: 1,
      dictLink1: 'http://dict1.example.com/lwt_term',
      dictLink2: 'http://dict2.example.com/lwt_term',
      translatorLink: 'http://translator.example.com/lwt_term',
      delimiter: ',',
      rtl: false
    });
    initTextConfig({
      id: 42
    });
    initSettingsConfig({
      hts: 0,
      wordStatusFilter: ''
    });
    resetReadingPosition();

    // Clear mock function calls between tests
    mockSpeechDispatcher.mockClear();
    mockOwin.mockClear();
    mockCClick.mockClear();
    mockScrollTo.mockClear();
    mockTermsApiSetStatus.mockClear();
    mockTermsApiCreateQuick.mockClear();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
    // Reset all state modules
    resetLanguageConfig();
    resetTextConfig();
    resetSettingsConfig();
    resetReadingPosition();
  });

  // ===========================================================================
  // ESC Key Tests
  // ===========================================================================

  describe('ESC key (27)', () => {
    it('resets reading position to -1', () => {
      setReadingPosition(5);

      const event = createKeyEvent(27);
      const result = handleTextKeydown(event);

      expect(getReadingPosition()).toBe(-1);
      expect(result).toBe(false);
    });

    it('removes uwordmarked and kwordmarked classes', () => {
      document.body.innerHTML = `
        <span class="word uwordmarked">word1</span>
        <span class="word kwordmarked">word2</span>
      `;

      const event = createKeyEvent(27);
      handleTextKeydown(event);

      expect(document.querySelectorAll('.uwordmarked').length).toBe(0);
      expect(document.querySelectorAll('.kwordmarked').length).toBe(0);
    });
  });

  // ===========================================================================
  // RETURN Key Tests
  // ===========================================================================

  describe('RETURN key (13)', () => {
    it('adds uwordmarked class to first unknown word', () => {
      document.body.innerHTML = `
        <span class="word status0">unknown1</span>
        <span class="word status0">unknown2</span>
      `;

      const event = createKeyEvent(13);
      handleTextKeydown(event);

      // RETURN adds uwordmarked to the first unknown word
      expect(document.querySelectorAll('.uwordmarked').length).toBe(1);
      expect(document.querySelector('.word.status0')?.classList.contains('uwordmarked')).toBe(true);
    });

    it('clicks first unknown word (status0)', () => {
      document.body.innerHTML = `
        <span id="w1" class="word status0">unknown1</span>
        <span id="w2" class="word status0">unknown2</span>
      `;

      const clickSpy = vi.fn();
      document.getElementById('w1')?.addEventListener('click', clickSpy);

      const event = createKeyEvent(13);
      handleTextKeydown(event);

      expect(document.getElementById('w1')?.classList.contains('uwordmarked')).toBe(true);
    });

    it('returns false when unknown words exist', () => {
      document.body.innerHTML = `
        <span class="word status0">unknown</span>
      `;

      const event = createKeyEvent(13);
      const result = handleTextKeydown(event);

      expect(result).toBe(false);
    });

    it('returns false when no unknown words exist', () => {
      document.body.innerHTML = `
        <span class="word status1">known</span>
      `;

      const event = createKeyEvent(13);
      const result = handleTextKeydown(event);

      expect(result).toBe(false);
    });
  });

  // ===========================================================================
  // HOME Key Tests
  // ===========================================================================

  describe('HOME key (36)', () => {
    it('navigates to first known word', () => {
      document.body.innerHTML = `
        <span id="w1" class="word status1" data_wid="100" data_ann="">first</span>
        <span id="w2" class="word status2" data_wid="101" data_ann="">second</span>
      `;

      const event = createKeyEvent(36);
      const result = handleTextKeydown(event);

      expect(getReadingPosition()).toBe(0);
      expect(result).toBe(false);
    });

    it('adds kwordmarked class to first word', () => {
      document.body.innerHTML = `
        <span id="w1" class="word status1" data_wid="100" data_ann="">first</span>
        <span id="w2" class="word status2" data_wid="101" data_ann="">second</span>
      `;

      const event = createKeyEvent(36);
      handleTextKeydown(event);

      expect(document.getElementById('w1')?.classList.contains('kwordmarked')).toBe(true);
    });

    it('returns true when no known words exist', () => {
      document.body.innerHTML = `
        <span class="word status0">unknown</span>
      `;

      const event = createKeyEvent(36);
      const result = handleTextKeydown(event);

      expect(result).toBe(true);
    });
  });

  // ===========================================================================
  // END Key Tests
  // ===========================================================================

  describe('END key (35)', () => {
    it('navigates to last known word', () => {
      document.body.innerHTML = `
        <span id="w1" class="word status1" data_wid="100" data_ann="">first</span>
        <span id="w2" class="word status2" data_wid="101" data_ann="">last</span>
      `;

      const event = createKeyEvent(35);
      handleTextKeydown(event);

      expect(document.getElementById('w2')?.classList.contains('kwordmarked')).toBe(true);
    });

    it('sets reading_position to last index', () => {
      document.body.innerHTML = `
        <span id="w1" class="word status1" data_wid="100" data_ann="">first</span>
        <span id="w2" class="word status2" data_wid="101" data_ann="">second</span>
        <span id="w3" class="word status3" data_wid="102" data_ann="">third</span>
      `;

      const event = createKeyEvent(35);
      handleTextKeydown(event);

      expect(getReadingPosition()).toBe(2);
    });
  });

  // ===========================================================================
  // LEFT Arrow Key Tests
  // ===========================================================================

  describe('LEFT arrow key (37)', () => {
    it('navigates to previous word', () => {
      document.body.innerHTML = `
        <span id="w1" class="word status1" data_wid="100" data_ann="">first</span>
        <span id="w2" class="word status2 kwordmarked" data_wid="101" data_ann="">second</span>
      `;

      const event = createKeyEvent(37);
      handleTextKeydown(event);

      expect(document.getElementById('w1')?.classList.contains('kwordmarked')).toBe(true);
      expect(document.getElementById('w2')?.classList.contains('kwordmarked')).toBe(false);
    });

    it('removes kwordmarked from current word', () => {
      document.body.innerHTML = `
        <span id="w1" class="word status1" data_wid="100" data_ann="">first</span>
        <span id="w2" class="word status2 kwordmarked" data_wid="101" data_ann="">second</span>
      `;

      const event = createKeyEvent(37);
      handleTextKeydown(event);

      expect(document.querySelectorAll('.kwordmarked').length).toBe(1);
    });
  });

  // ===========================================================================
  // RIGHT Arrow Key Tests
  // ===========================================================================

  describe('RIGHT arrow key (39)', () => {
    it('navigates to next word', () => {
      document.body.innerHTML = `
        <span id="w1" class="word status1 kwordmarked" data_wid="100" data_ann="">first</span>
        <span id="w2" class="word status2" data_wid="101" data_ann="">second</span>
      `;

      const event = createKeyEvent(39);
      handleTextKeydown(event);

      expect(document.getElementById('w2')?.classList.contains('kwordmarked')).toBe(true);
    });
  });

  // ===========================================================================
  // SPACE Key Tests
  // ===========================================================================

  describe('SPACE key (32)', () => {
    it('navigates to next word (same as RIGHT)', () => {
      document.body.innerHTML = `
        <span id="w1" class="word status1 kwordmarked" data_wid="100" data_ann="">first</span>
        <span id="w2" class="word status2" data_wid="101" data_ann="">second</span>
      `;

      const event = createKeyEvent(32);
      handleTextKeydown(event);

      expect(document.getElementById('w2')?.classList.contains('kwordmarked')).toBe(true);
    });
  });

  // ===========================================================================
  // Number Keys (1-5) Status Change Tests
  // ===========================================================================

  describe('Number keys (1-5) for status change', () => {
    it('changes status of known word via API', () => {
      document.body.innerHTML = `
        <span id="w1" class="word status3 kwordmarked"
              data_wid="100" data_order="5" data_status="3">word</span>
      `;

      setReadingPosition(0);

      const event = createKeyEvent(50); // key '2'
      handleTextKeydown(event);

      // Now uses TermsApi.setStatus instead of loadModalFrame
      expect(mockTermsApiSetStatus).toHaveBeenCalledWith(100, 2);
    });

    it('updates status for a word via keyboard navigation', () => {
      // Note: status0 words are not in the knownwordlist, so keyboard navigation
      // doesn't include them directly. We need to mark a known word first.
      document.body.innerHTML = `
        <span id="w1" class="word status1 kwordmarked" data_wid="100" data_order="5" data_status="1" data_ann="">known</span>
      `;

      setReadingPosition(0);

      // Press number key to change the status of the marked known word
      const event = createKeyEvent(49); // key '1'
      handleTextKeydown(event);

      // Known word gets API call
      expect(mockTermsApiSetStatus).toHaveBeenCalledWith(100, 1);
    });

    it('handles numpad keys (96-100)', () => {
      document.body.innerHTML = `
        <span id="w1" class="word status3 kwordmarked"
              data_wid="100" data_order="5" data_status="3">word</span>
      `;

      setReadingPosition(0);

      const event = createKeyEvent(99); // numpad '3'
      handleTextKeydown(event);

      expect(mockTermsApiSetStatus).toHaveBeenCalledWith(100, 3);
    });
  });

  // ===========================================================================
  // I Key (Ignore) Tests
  // ===========================================================================

  describe('I key (73) - Ignore word', () => {
    it('sets status to 98 for known word via API', () => {
      document.body.innerHTML = `
        <span id="w1" class="word status3 kwordmarked"
              data_wid="100" data_order="5" data_status="3">word</span>
      `;

      setReadingPosition(0);

      const event = createKeyEvent(73);
      handleTextKeydown(event);

      expect(mockTermsApiSetStatus).toHaveBeenCalledWith(100, 98);
    });
  });

  // ===========================================================================
  // W Key (Well-known) Tests
  // ===========================================================================

  describe('W key (87) - Well-known word', () => {
    it('sets status to 99 for known word via API', () => {
      document.body.innerHTML = `
        <span id="w1" class="word status3 kwordmarked"
              data_wid="100" data_order="5" data_status="3">word</span>
      `;

      setReadingPosition(0);

      const event = createKeyEvent(87);
      const result = handleTextKeydown(event);

      expect(mockTermsApiSetStatus).toHaveBeenCalledWith(100, 99);
      expect(result).toBe(false);
    });
  });

  // ===========================================================================
  // P Key (Pronounce) Tests
  // ===========================================================================

  describe('P key (80) - Pronounce', () => {
    it('calls speechDispatcher with word text', () => {
      document.body.innerHTML = `
        <span id="w1" class="word status3 kwordmarked"
              data_wid="100" data_order="5" data_status="3">hello</span>
      `;

      setReadingPosition(0);

      const event = createKeyEvent(80);
      const result = handleTextKeydown(event);

      expect(mockSpeechDispatcher).toHaveBeenCalledWith('hello', getLanguageId());
      expect(result).toBe(false);
    });
  });

  // ===========================================================================
  // T Key (Translate sentence) Tests
  // ===========================================================================

  describe('T key (84) - Translate sentence', () => {
    it('opens translation in popup or frame', () => {
      document.body.innerHTML = `
        <span id="w1" class="word status3 kwordmarked"
              data_wid="100" data_order="5" data_status="3">word</span>
      `;

      setReadingPosition(0);

      const event = createKeyEvent(84);
      const result = handleTextKeydown(event);

      expect(result).toBe(false);
    });

    it('uses popup when translator link starts with *', () => {
      document.body.innerHTML = `
        <span id="w1" class="word status3 kwordmarked"
              data_wid="100" data_order="5" data_status="3">word</span>
      `;

      // Reinitialize with popup translator link (starts with *)
      initLanguageConfig({
        id: 1,
        dictLink1: 'http://dict1.example.com/lwt_term',
        dictLink2: 'http://dict2.example.com/lwt_term',
        translatorLink: '*http://translator.com/lwt_term',
        delimiter: ',',
        rtl: false
      });
      setReadingPosition(0);

      const event = createKeyEvent(84);
      handleTextKeydown(event);

      expect(mockOwin).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // E Key (Edit) Tests
  // ===========================================================================

  describe('E key (69) - Edit term', () => {
    it('opens edit word form for known word', () => {
      document.body.innerHTML = `
        <span id="w1" class="word status3 kwordmarked"
              data_wid="100" data_order="5" data_status="3">word</span>
      `;

      setReadingPosition(0);

      const event = createKeyEvent(69);
      const result = handleTextKeydown(event);

      // Now opens word form via Alpine store
      expect(result).toBe(false);
    });

    it('opens edit form for multiwords', () => {
      document.body.innerHTML = `
        <span id="w1" class="mword status3 kwordmarked"
              data_wid="100" data_order="5" data_status="3" data_code="2">multi word</span>
      `;

      setReadingPosition(0);

      const event = createKeyEvent(69);
      const result = handleTextKeydown(event);

      // Now opens word form via Alpine store
      expect(result).toBe(false);
    });

    it('returns false when opening edit form', () => {
      document.body.innerHTML = `
        <span id="w1" class="word status3 kwordmarked"
              data_wid="100" data_order="5" data_status="3">word</span>
      `;

      setReadingPosition(0);

      const event = createKeyEvent(69);
      const result = handleTextKeydown(event);

      expect(result).toBe(false);
    });
  });

  // ===========================================================================
  // A Key (Audio position) Tests
  // ===========================================================================

  describe('A key (65) - Set audio position', () => {
    it('calls audio controller newPosition and returns false', () => {
      document.body.innerHTML = `
        <span id="w1" class="word status3 kwordmarked"
              data_wid="100" data_pos="50">word</span>
        <span id="totalcharcount">1000</span>
      `;

      setReadingPosition(0);
      mockNewPosition.mockClear();

      const event = createKeyEvent(65);
      const result = handleTextKeydown(event);

      // Position calculation: 100 * (50 - 5) / 1000 = 4.5
      expect(mockNewPosition).toHaveBeenCalledWith(4.5);
      expect(result).toBe(false);
    });

    it('returns true when totalcharcount is 0', () => {
      document.body.innerHTML = `
        <span id="w1" class="word status3 kwordmarked"
              data_wid="100" data_pos="50">word</span>
        <span id="totalcharcount">0</span>
      `;

      setReadingPosition(0);
      mockNewPosition.mockClear();

      const event = createKeyEvent(65);
      const result = handleTextKeydown(event);

      expect(mockNewPosition).not.toHaveBeenCalled();
      expect(result).toBe(true);
    });

    it('clamps negative position to 0', () => {
      document.body.innerHTML = `
        <span id="w1" class="word status3 kwordmarked"
              data_wid="100" data_pos="2">word</span>
        <span id="totalcharcount">1000</span>
      `;

      setReadingPosition(0);
      mockNewPosition.mockClear();

      const event = createKeyEvent(65);
      const result = handleTextKeydown(event);

      // Position calculation: 100 * (2 - 5) / 1000 = -0.3, clamped to 0
      expect(mockNewPosition).toHaveBeenCalledWith(0);
      expect(result).toBe(false);
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    // Note: This test is skipped because the source code uses :hover selector
    // which doesn't work in jsdom
    it.skip('returns true for unhandled keys', () => {
      document.body.innerHTML = `
        <span class="word status1" data_wid="100" data_ann="">word</span>
      `;

      const event = createKeyEvent(88); // 'X' key
      const result = handleTextKeydown(event);

      expect(result).toBe(true);
    });

    it('handles empty word list', () => {
      document.body.innerHTML = '';

      const event = createKeyEvent(39);
      const result = handleTextKeydown(event);

      expect(result).toBe(true);
    });

    // Note: hover tests are skipped because :hover pseudo-selector doesn't work in jsdom
    it.skip('handles hover word when no marked word', () => {
      document.body.innerHTML = `
        <span id="w1" class="hword word status3"
              data_wid="100" data_order="5" data_status="3">hovered</span>
      `;

      // Simulate hover
      document.getElementById('w1')?.classList.add('hword');

      setReadingPosition(-1);

      const event = createKeyEvent(80);
      handleTextKeydown(event);
    });

    // Note: This test is skipped because the source code uses :hover selector
    // which doesn't work in jsdom
    it.skip('respects word_status_filter setting', () => {
      document.body.innerHTML = `
        <span id="w1" class="word status1" data_wid="100" data_ann="">status1</span>
        <span id="w2" class="word status2" data_wid="101" data_ann="">status2</span>
      `;

      // Reinitialize with word status filter
      initSettingsConfig({
        hts: 0,
        wordStatusFilter: ':not(.status2)',
        annotationsMode: 0,
        useFrameMode: false
      });

      const event = createKeyEvent(36);
      handleTextKeydown(event);
    });
  });
});
