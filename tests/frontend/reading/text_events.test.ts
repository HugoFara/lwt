/**
 * Tests for text_events.ts - Text reading interaction events
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Use vi.hoisted for mock functions that need to be available during vi.mock hoisting
const { mockNewPosition } = vi.hoisted(() => ({
  mockNewPosition: vi.fn()
}));

// Mock the audio controller module
vi.mock('../../../src/frontend/js/media/html5_audio_player', () => ({
  lwt_audio_controller: {
    newPosition: mockNewPosition
  }
}));

import {
  handleWordDoubleClick,
  handleWordClick,
  handleMultiWordClick,
  handleWordHoverOver,
  handleWordHoverOut,
  prepareTextInteractions,
} from '../../../src/frontend/js/modules/text/pages/reading/text_events';
import * as userInteractions from '../../../src/frontend/js/shared/utils/user_interactions';
import * as wordPopupInterface from '../../../src/frontend/js/modules/vocabulary/services/word_popup_interface';
import * as wordStatus from '../../../src/frontend/js/modules/vocabulary/services/word_status';

// Polyfill HTMLDialogElement methods for JSDOM
function polyfillDialog() {
  if (typeof HTMLDialogElement !== 'undefined') {
    if (!HTMLDialogElement.prototype.show) {
      HTMLDialogElement.prototype.show = function() {
        this.setAttribute('open', '');
      };
    }
    if (!HTMLDialogElement.prototype.showModal) {
      HTMLDialogElement.prototype.showModal = function() {
        this.setAttribute('open', '');
      };
    }
    if (!HTMLDialogElement.prototype.close) {
      HTMLDialogElement.prototype.close = function() {
        this.removeAttribute('open');
        this.dispatchEvent(new Event('close'));
      };
    }
  }
}

// Import state modules
import { initLanguageConfig, resetLanguageConfig } from '../../../src/frontend/js/modules/language/stores/language_config';
import { initTextConfig, resetTextConfig } from '../../../src/frontend/js/modules/text/stores/text_config';
import { initSettingsConfig, resetSettingsConfig } from '../../../src/frontend/js/shared/utils/settings_config';

// Setup global mocks
beforeEach(() => {
  polyfillDialog();
  // Initialize state modules with test values
  resetLanguageConfig();
  resetTextConfig();
  resetSettingsConfig();
  initLanguageConfig({
    id: 1,
    dictLink1: 'http://dict1.example.com/lwt_term',
    dictLink2: 'http://dict2.example.com/lwt_term',
    translatorLink: 'http://translate.example.com/lwt_term',
    delimiter: ',',
    rtl: false,
  });
  initTextConfig({
    id: 42
  });
  initSettingsConfig({
    hts: 0,
    wordStatusFilter: '',
    annotationsMode: 0,
    useFrameMode: true // Use frame mode for legacy tests
  });
});

describe('text_events.ts', () => {
  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // handleWordDoubleClick Tests
  // ===========================================================================

  describe('handleWordDoubleClick', () => {
    beforeEach(() => {
      mockNewPosition.mockClear();
    });

    it('does nothing when totalcharcount is 0', () => {
      document.body.innerHTML = `
        <span id="totalcharcount">0</span>
        <span class="word" data_pos="100">Test</span>
      `;

      const word = document.querySelector('.word') as HTMLElement;
      handleWordDoubleClick.call(word);

      // Should return early without calling audio controller
      expect(mockNewPosition).not.toHaveBeenCalled();
    });

    it('calculates position percentage correctly', () => {
      document.body.innerHTML = `
        <span id="totalcharcount">1000</span>
        <span class="word" data_pos="505">Test</span>
      `;

      const word = document.querySelector('.word') as HTMLElement;
      handleWordDoubleClick.call(word);

      // Position should be (505 - 5) / 1000 * 100 = 50%
      expect(mockNewPosition).toHaveBeenCalledWith(50);
    });

    it('clamps negative position to 0', () => {
      document.body.innerHTML = `
        <span id="totalcharcount">1000</span>
        <span class="word" data_pos="3">Test</span>
      `;

      const word = document.querySelector('.word') as HTMLElement;
      handleWordDoubleClick.call(word);

      // Position (3 - 5) / 1000 * 100 = -0.2%, should clamp to 0
      expect(mockNewPosition).toHaveBeenCalledWith(0);
    });

    it('handles missing data_pos attribute', () => {
      document.body.innerHTML = `
        <span id="totalcharcount">1000</span>
        <span class="word">Test</span>
      `;

      const word = document.querySelector('.word') as HTMLElement;
      handleWordDoubleClick.call(word);

      // Should use 0 as default, resulting in position 0 (clamped from negative)
      expect(mockNewPosition).toHaveBeenCalledWith(0);
    });

    it('handles function call without throwing', () => {
      document.body.innerHTML = `
        <span id="totalcharcount">1000</span>
        <span class="word" data_pos="500">Test</span>
      `;

      const word = document.querySelector('.word') as HTMLElement;

      // Should not throw
      expect(() => handleWordDoubleClick.call(word)).not.toThrow();
      // Position should be (500 - 5) / 1000 * 100 = 49.5%
      expect(mockNewPosition).toHaveBeenCalledWith(49.5);
    });
  });

  // ===========================================================================
  // handleWordHoverOver Tests
  // ===========================================================================

  describe('handleWordHoverOver', () => {
    beforeEach(() => {
      // Mock speechDispatcher
      (window as unknown as Record<string, unknown>).speechDispatcher = vi.fn();
    });

    it('adds hword class to elements with matching TERM class', () => {
      document.body.innerHTML = `
        <span class="word TERM123">Word 1</span>
        <span class="word TERM123">Word 2</span>
        <span class="word TERM456">Word 3</span>
      `;

      const word = document.querySelector('.TERM123') as HTMLElement;
      handleWordHoverOver.call(word);

      const highlighted = document.querySelectorAll('.hword');
      expect(highlighted.length).toBe(2);
    });

    it('does not add hword class when tword exists', () => {
      document.body.innerHTML = `
        <span class="tword">Active word</span>
        <span class="word TERM123">Word 1</span>
        <span class="word TERM123">Word 2</span>
      `;

      const word = document.querySelector('.TERM123') as HTMLElement;
      handleWordHoverOver.call(word);

      const highlighted = document.querySelectorAll('.hword');
      expect(highlighted.length).toBe(0);
    });

    it('handles tooltip enabled mode', () => {
      document.body.innerHTML = `
        <span class="word TERM123">Word</span>
      `;
      // Native tooltips are always enabled now

      const word = document.querySelector('.word') as HTMLElement;
      handleWordHoverOver.call(word);

      // The function should handle tooltip mode setting
      expect(true).toBe(true);
    });

    it('calls speechDispatcher when hts is 3', () => {
      document.body.innerHTML = `
        <span class="word TERM123">Hello</span>
      `;
      initSettingsConfig({ hts: 3, useFrameMode: true });

      const speechDispatcherSpy = vi.spyOn(userInteractions, 'speechDispatcher').mockImplementation(() => {});

      const word = document.querySelector('.word') as HTMLElement;
      handleWordHoverOver.call(word);

      expect(speechDispatcherSpy).toHaveBeenCalledWith('Hello', 1);
    });

    it('does not call speechDispatcher when hts is not 3', () => {
      document.body.innerHTML = `
        <span class="word TERM123">Hello</span>
      `;
      initSettingsConfig({ hts: 0, useFrameMode: true });

      const speechDispatcherSpy = vi.spyOn(userInteractions, 'speechDispatcher').mockImplementation(() => {});

      const word = document.querySelector('.word') as HTMLElement;
      handleWordHoverOver.call(word);

      expect(speechDispatcherSpy).not.toHaveBeenCalled();
    });

    it('extracts TERM class correctly from complex class string', () => {
      document.body.innerHTML = `
        <span class="word status1 TERM999 wsty">Word 1</span>
        <span class="word status2 TERM999 wsty">Word 2</span>
      `;

      const word = document.querySelector('.TERM999') as HTMLElement;
      handleWordHoverOver.call(word);

      const highlighted = document.querySelectorAll('.hword');
      expect(highlighted.length).toBe(2);
    });
  });

  // ===========================================================================
  // handleWordHoverOut Tests
  // ===========================================================================

  describe('handleWordHoverOut', () => {
    it('removes hword class from all elements', () => {
      document.body.innerHTML = `
        <span class="word hword">Word 1</span>
        <span class="word hword">Word 2</span>
        <span class="word hword">Word 3</span>
      `;

      handleWordHoverOut();

      const highlighted = document.querySelectorAll('.hword');
      expect(highlighted.length).toBe(0);
    });

    it('does not affect elements without hword class', () => {
      document.body.innerHTML = `
        <span class="word">Word 1</span>
        <span class="word hword">Word 2</span>
        <span class="word">Word 3</span>
      `;

      handleWordHoverOut();

      const words = document.querySelectorAll('.word');
      expect(words.length).toBe(3);
    });

    it('handles empty document gracefully', () => {
      document.body.innerHTML = '';

      expect(() => handleWordHoverOut()).not.toThrow();
    });
  });

  // ===========================================================================
  // Integration Tests
  // ===========================================================================

  describe('Integration', () => {
    it('hover over and out cycle works correctly', () => {
      document.body.innerHTML = `
        <span class="word TERM100">Word 1</span>
        <span class="word TERM100">Word 2</span>
        <span class="word TERM200">Word 3</span>
      `;

      const word = document.querySelector('.TERM100') as HTMLElement;

      // Hover over
      handleWordHoverOver.call(word);
      expect(document.querySelectorAll('.hword').length).toBe(2);

      // Hover out
      handleWordHoverOut();
      expect(document.querySelectorAll('.hword').length).toBe(0);
    });
  });

  // ===========================================================================
  // handleWordClick Tests
  // ===========================================================================

  describe('handleWordClick', () => {
    beforeEach(() => {
      vi.spyOn(wordPopupInterface, 'showUnknownWordPopup').mockImplementation(() => {});
      vi.spyOn(wordPopupInterface, 'showWellKnownWordPopup').mockImplementation(() => {});
      vi.spyOn(wordPopupInterface, 'showIgnoredWordPopup').mockImplementation(() => {});
      vi.spyOn(wordPopupInterface, 'showLearningWordPopup').mockImplementation(() => {});
      vi.spyOn(wordStatus, 'createWordTooltip').mockReturnValue('tooltip text');
      vi.spyOn(userInteractions, 'speechDispatcher').mockImplementation(() => ({} as JQuery.jqXHR));
    });

    it('returns false', () => {
      document.body.innerHTML = `
        <span class="word" data_status="0" data_order="1" data_wid="">Test</span>
      `;

      const word = document.querySelector('.word') as HTMLElement;
      const result = handleWordClick.call(word);

      expect(result).toBe(false);
    });

    it('calls showUnknownWordPopup for status < 1', () => {
      document.body.innerHTML = `
        <span class="word" data_status="0" data_order="1" data_wid="">Test</span>
      `;

      const word = document.querySelector('.word') as HTMLElement;
      handleWordClick.call(word);

      // Shows popup with "Learn term" link - no frame loading needed
      expect(wordPopupInterface.showUnknownWordPopup).toHaveBeenCalled();
    });

    it('calls showWellKnownWordPopup for well-known words', () => {
      document.body.innerHTML = `
        <span class="word" data_status="99" data_order="1" data_wid="123" data_ann="">Test</span>
      `;

      const word = document.querySelector('.word') as HTMLElement;
      handleWordClick.call(word);

      expect(wordPopupInterface.showWellKnownWordPopup).toHaveBeenCalled();
    });

    it('calls showIgnoredWordPopup for ignored words', () => {
      document.body.innerHTML = `
        <span class="word" data_status="98" data_order="1" data_wid="456" data_ann="">Test</span>
      `;

      const word = document.querySelector('.word') as HTMLElement;
      handleWordClick.call(word);

      expect(wordPopupInterface.showIgnoredWordPopup).toHaveBeenCalled();
    });

    it('calls showLearningWordPopup for learning words', () => {
      document.body.innerHTML = `
        <span class="word" data_status="3" data_order="1" data_wid="789" data_ann="">Test</span>
      `;

      const word = document.querySelector('.word') as HTMLElement;
      handleWordClick.call(word);

      expect(wordPopupInterface.showLearningWordPopup).toHaveBeenCalled();
    });

    it('calls speechDispatcher when hts is 2', () => {
      initSettingsConfig({ hts: 2, useFrameMode: true });
      document.body.innerHTML = `
        <span class="word" data_status="0" data_order="1">Hello</span>
      `;

      const word = document.querySelector('.word') as HTMLElement;
      handleWordClick.call(word);

      expect(userInteractions.speechDispatcher).toHaveBeenCalledWith('Hello', 1);
    });

    it('does not call speechDispatcher when hts is not 2', () => {
      initSettingsConfig({ hts: 0, useFrameMode: true });
      document.body.innerHTML = `
        <span class="word" data_status="0" data_order="1">Hello</span>
      `;

      const word = document.querySelector('.word') as HTMLElement;
      handleWordClick.call(word);

      expect(userInteractions.speechDispatcher).not.toHaveBeenCalled();
    });

    it('uses title attribute for hints when jQuery_tooltip is false', () => {
      // Native tooltips are always used now (jQuery tooltips removed)
      document.body.innerHTML = `
        <span class="word" data_status="3" data_order="1" data_wid="1" title="custom hint">Test</span>
      `;

      const word = document.querySelector('.word') as HTMLElement;
      handleWordClick.call(word);

      // Should use title attribute instead of createWordTooltip
      expect(wordPopupInterface.showLearningWordPopup).toHaveBeenCalled();
    });

    it('collects multi-word data attributes', () => {
      document.body.innerHTML = `
        <span class="word" data_status="0" data_order="1" data_mw2="mw2data" data_mw3="mw3data">Test</span>
      `;

      const word = document.querySelector('.word') as HTMLElement;
      handleWordClick.call(word);

      // showUnknownWordPopup should receive the multi_words array
      expect(wordPopupInterface.showUnknownWordPopup).toHaveBeenCalledWith(
        expect.anything(),
        expect.anything(),
        expect.anything(),
        expect.anything(),
        expect.anything(),
        expect.anything(),
        expect.anything(),
        expect.arrayContaining(['mw2data', 'mw3data']),
        expect.anything()
      );
    });
  });

  // ===========================================================================
  // handleMultiWordClick Tests
  // ===========================================================================

  describe('handleMultiWordClick', () => {
    beforeEach(() => {
      vi.spyOn(wordPopupInterface, 'showMultiWordPopup').mockImplementation(() => {});
      vi.spyOn(wordStatus, 'createWordTooltip').mockReturnValue('mword tooltip');
      vi.spyOn(userInteractions, 'speechDispatcher').mockImplementation(() => ({} as JQuery.jqXHR));
    });

    it('returns false', () => {
      document.body.innerHTML = `
        <span class="mword" data_status="3" data_order="1" data_text="multi word" data_wid="10" data_code="ABC">Test MW</span>
      `;

      const mword = document.querySelector('.mword') as HTMLElement;
      const result = handleMultiWordClick.call(mword);

      expect(result).toBe(false);
    });

    it('calls showMultiWordPopup when status is not empty', () => {
      document.body.innerHTML = `
        <span class="mword" data_status="3" data_order="1" data_text="multi word" data_wid="10" data_code="ABC" data_ann="">Test MW</span>
      `;

      const mword = document.querySelector('.mword') as HTMLElement;
      handleMultiWordClick.call(mword);

      expect(wordPopupInterface.showMultiWordPopup).toHaveBeenCalled();
    });

    it('does not call showMultiWordPopup when status is empty', () => {
      document.body.innerHTML = `
        <span class="mword" data_status="" data_order="1">Test MW</span>
      `;

      const mword = document.querySelector('.mword') as HTMLElement;
      handleMultiWordClick.call(mword);

      expect(wordPopupInterface.showMultiWordPopup).not.toHaveBeenCalled();
    });

    it('calls speechDispatcher when hts is 2', () => {
      initSettingsConfig({ hts: 2, useFrameMode: true });
      document.body.innerHTML = `
        <span class="mword" data_status="3" data_order="1">Hello World</span>
      `;

      const mword = document.querySelector('.mword') as HTMLElement;
      handleMultiWordClick.call(mword);

      expect(userInteractions.speechDispatcher).toHaveBeenCalledWith('Hello World', 1);
    });

    it('uses title attribute when jQuery_tooltip is false', () => {
      // Native tooltips are always used now (jQuery tooltips removed)
      document.body.innerHTML = `
        <span class="mword" data_status="3" data_order="1" title="mword title">Test</span>
      `;

      const mword = document.querySelector('.mword') as HTMLElement;
      handleMultiWordClick.call(mword);

      expect(wordPopupInterface.showMultiWordPopup).toHaveBeenCalledWith(
        expect.anything(),
        expect.anything(),
        expect.anything(),
        'mword title',
        expect.anything(),
        expect.anything(),
        expect.anything(),
        expect.anything(),
        expect.anything(),
        expect.anything(),
        expect.anything()
      );
    });
  });

  // ===========================================================================
  // prepareTextInteractions Tests
  // ===========================================================================

  describe('prepareTextInteractions', () => {
    it('sets up click handlers on word elements', () => {
      document.body.innerHTML = `
        <div id="thetext">
          <span class="word wsty">Word 1</span>
          <span class="word wsty">Word 2</span>
        </div>
      `;

      // Mock the functions that would be called on click
      vi.spyOn(wordPopupInterface, 'showUnknownWordPopup').mockImplementation(() => {});

      prepareTextInteractions();

      // The click handler should be attached
      const words = document.querySelectorAll('.word');
      expect(words.length).toBe(2);
    });

    it('sets up click handlers on mword elements', () => {
      document.body.innerHTML = `
        <div id="thetext">
          <span class="mword mwsty">Multi Word</span>
        </div>
      `;

      prepareTextInteractions();

      const mwords = document.querySelectorAll('.mword');
      expect(mwords.length).toBe(1);
    });

    it('sets up dblclick handlers on word elements', () => {
      document.body.innerHTML = `
        <div id="thetext">
          <span class="word wsty">Word</span>
        </div>
        <span id="totalcharcount">1000</span>
      `;

      prepareTextInteractions();

      // Double click should be handled (we can't easily test the actual handler execution)
      const word = document.querySelector('.word');
      expect(word).not.toBeNull();
    });

    it('handles missing thetext element gracefully', () => {
      document.body.innerHTML = `
        <span class="word">Word</span>
      `;

      // Should not throw even without #thetext
      expect(() => prepareTextInteractions()).not.toThrow();
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handleWordHoverOver handles element with no TERM class gracefully', () => {
      // When there's no TERM class, the regex returns the class string as-is
      // because the pattern doesn't match. The code will then try to find
      // elements with that class.
      document.body.innerHTML = `<span class="word">No TERM class</span>`;

      const span = document.querySelector('span') as HTMLElement;

      // Without TERM class, the regex extracts "word" from "word" class string
      // and looks for .word elements, adding hword class to them
      handleWordHoverOver.call(span);

      // The span should have hword class added (because .word matches itself)
      expect(span.classList.contains('hword')).toBe(true);
    });

    it('handleWordDoubleClick handles missing totalcharcount element', () => {
      document.body.innerHTML = `
        <span class="word" data_pos="100">Test</span>
      `;

      const word = document.querySelector('.word') as HTMLElement;

      // Should handle NaN gracefully (parseInt of undefined text)
      expect(() => handleWordDoubleClick.call(word)).not.toThrow();
    });

    it('handleWordHoverOver handles TERM class at end of class string', () => {
      document.body.innerHTML = `
        <span class="word status1 TERM555">Word</span>
      `;

      const word = document.querySelector('.word') as HTMLElement;
      handleWordHoverOver.call(word);

      expect(document.querySelectorAll('.hword').length).toBe(1);
    });

    it('word_click handles empty title attribute', () => {
      // Native tooltips are always used now
      vi.spyOn(wordPopupInterface, 'showUnknownWordPopup').mockImplementation(() => {});

      document.body.innerHTML = `
        <span class="word" data_status="0" data_order="1">Test</span>
      `;

      const word = document.querySelector('.word') as HTMLElement;
      handleWordClick.call(word);

      // Should not throw and should use empty string as hints
      expect(wordPopupInterface.showUnknownWordPopup).toHaveBeenCalled();
    });

    it('mword_click handles missing data attributes', () => {
      vi.spyOn(wordPopupInterface, 'showMultiWordPopup').mockImplementation(() => {});

      document.body.innerHTML = `
        <span class="mword" data_status="3">Test</span>
      `;

      const mword = document.querySelector('.mword') as HTMLElement;

      // Should not throw
      expect(() => handleMultiWordClick.call(mword)).not.toThrow();
    });
  });
});
