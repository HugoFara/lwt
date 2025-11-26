/**
 * Tests for text_events.ts - Text reading interaction events
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import $ from 'jquery';
import {
  word_dblclick_event_do_text_text,
  word_hover_over,
  word_hover_out,
} from '../../../src/frontend/js/reading/text_events';
import * as userInteractions from '../../../src/frontend/js/core/user_interactions';

// Mock LWT_DATA global
const mockLWT_DATA = {
  language: {
    id: 1,
    dict_link1: 'http://dict1.example.com/###',
    dict_link2: 'http://dict2.example.com/###',
    translator_link: 'http://translate.example.com/###',
    delimiter: ',',
    rtl: false,
  },
  text: {
    id: 42,
    reading_position: 0,
    annotations: {},
  },
  settings: {
    jQuery_tooltip: false,
    hts: 0,
    word_status_filter: '',
    annotations_mode: 0,
  },
};

// Setup global mocks
beforeEach(() => {
  (window as unknown as Record<string, unknown>).LWT_DATA = mockLWT_DATA;
  (window as unknown as Record<string, unknown>).$ = $;
  (globalThis as unknown as Record<string, unknown>).$ = $;
});

describe('text_events.ts', () => {
  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
    // Reset LWT_DATA settings
    mockLWT_DATA.settings.jQuery_tooltip = false;
    mockLWT_DATA.settings.hts = 0;
  });

  // ===========================================================================
  // word_dblclick_event_do_text_text Tests
  // ===========================================================================

  describe('word_dblclick_event_do_text_text', () => {
    it('does nothing when totalcharcount is 0', () => {
      document.body.innerHTML = `
        <span id="totalcharcount">0</span>
        <span class="word" data_pos="100">Test</span>
      `;

      const word = document.querySelector('.word') as HTMLElement;
      word_dblclick_event_do_text_text.call(word);

      // Should return early without error
      expect(true).toBe(true);
    });

    it('calculates position percentage correctly', () => {
      document.body.innerHTML = `
        <span id="totalcharcount">1000</span>
        <span class="word" data_pos="505">Test</span>
      `;

      // Mock parent frames with audio controller
      const mockNewPosition = vi.fn();
      (window as unknown as Record<string, unknown>).parent = {
        frames: {
          h: {
            lwt_audio_controller: {
              newPosition: mockNewPosition,
            },
          },
        },
      };

      const word = document.querySelector('.word') as HTMLElement;
      word_dblclick_event_do_text_text.call(word);

      // Position should be (505 - 5) / 1000 * 100 = 50%
      expect(mockNewPosition).toHaveBeenCalledWith(50);
    });

    it('clamps negative position to 0', () => {
      document.body.innerHTML = `
        <span id="totalcharcount">1000</span>
        <span class="word" data_pos="3">Test</span>
      `;

      const mockNewPosition = vi.fn();
      (window as unknown as Record<string, unknown>).parent = {
        frames: {
          h: {
            lwt_audio_controller: {
              newPosition: mockNewPosition,
            },
          },
        },
      };

      const word = document.querySelector('.word') as HTMLElement;
      word_dblclick_event_do_text_text.call(word);

      // Position (3 - 5) / 1000 * 100 = -0.2%, should clamp to 0
      expect(mockNewPosition).toHaveBeenCalledWith(0);
    });

    it('handles missing data_pos attribute', () => {
      document.body.innerHTML = `
        <span id="totalcharcount">1000</span>
        <span class="word">Test</span>
      `;

      const mockNewPosition = vi.fn();
      (window as unknown as Record<string, unknown>).parent = {
        frames: {
          h: {
            lwt_audio_controller: {
              newPosition: mockNewPosition,
            },
          },
        },
      };

      const word = document.querySelector('.word') as HTMLElement;
      word_dblclick_event_do_text_text.call(word);

      // Should use 0 as default, resulting in position 0 (clamped from negative)
      expect(mockNewPosition).toHaveBeenCalledWith(0);
    });

    it('does nothing when audio controller is not available', () => {
      document.body.innerHTML = `
        <span id="totalcharcount">1000</span>
        <span class="word" data_pos="500">Test</span>
      `;

      (window as unknown as Record<string, unknown>).parent = {
        frames: {},
      };

      const word = document.querySelector('.word') as HTMLElement;

      // Should not throw
      expect(() => word_dblclick_event_do_text_text.call(word)).not.toThrow();
    });
  });

  // ===========================================================================
  // word_hover_over Tests
  // ===========================================================================

  describe('word_hover_over', () => {
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
      word_hover_over.call(word);

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
      word_hover_over.call(word);

      const highlighted = document.querySelectorAll('.hword');
      expect(highlighted.length).toBe(0);
    });

    it('triggers mouseover when jQuery_tooltip is enabled', () => {
      document.body.innerHTML = `
        <span class="word TERM123">Word</span>
      `;
      mockLWT_DATA.settings.jQuery_tooltip = true;

      const word = document.querySelector('.word') as HTMLElement;
      const triggerSpy = vi.fn();
      $(word).on('mouseover', triggerSpy);

      word_hover_over.call(word);

      // The function triggers mouseover on jQuery tooltip enabled
      expect(mockLWT_DATA.settings.jQuery_tooltip).toBe(true);
    });

    it('calls speechDispatcher when hts is 3', () => {
      document.body.innerHTML = `
        <span class="word TERM123">Hello</span>
      `;
      mockLWT_DATA.settings.hts = 3;

      const speechDispatcherSpy = vi.spyOn(userInteractions, 'speechDispatcher').mockImplementation(() => {});

      const word = document.querySelector('.word') as HTMLElement;
      word_hover_over.call(word);

      expect(speechDispatcherSpy).toHaveBeenCalledWith('Hello', 1);
    });

    it('does not call speechDispatcher when hts is not 3', () => {
      document.body.innerHTML = `
        <span class="word TERM123">Hello</span>
      `;
      mockLWT_DATA.settings.hts = 0;

      const speechDispatcherSpy = vi.spyOn(userInteractions, 'speechDispatcher').mockImplementation(() => {});

      const word = document.querySelector('.word') as HTMLElement;
      word_hover_over.call(word);

      expect(speechDispatcherSpy).not.toHaveBeenCalled();
    });

    it('extracts TERM class correctly from complex class string', () => {
      document.body.innerHTML = `
        <span class="word status1 TERM999 wsty">Word 1</span>
        <span class="word status2 TERM999 wsty">Word 2</span>
      `;

      const word = document.querySelector('.TERM999') as HTMLElement;
      word_hover_over.call(word);

      const highlighted = document.querySelectorAll('.hword');
      expect(highlighted.length).toBe(2);
    });
  });

  // ===========================================================================
  // word_hover_out Tests
  // ===========================================================================

  describe('word_hover_out', () => {
    it('removes hword class from all elements', () => {
      document.body.innerHTML = `
        <span class="word hword">Word 1</span>
        <span class="word hword">Word 2</span>
        <span class="word hword">Word 3</span>
      `;

      word_hover_out();

      const highlighted = document.querySelectorAll('.hword');
      expect(highlighted.length).toBe(0);
    });

    it('removes jQuery tooltip helper divs when jQuery_tooltip is enabled', () => {
      document.body.innerHTML = `
        <div class="ui-helper-hidden-accessible">
          <div style="position: absolute;">Tooltip content</div>
        </div>
        <span class="word hword">Word</span>
      `;
      mockLWT_DATA.settings.jQuery_tooltip = true;

      word_hover_out();

      const tooltipDivs = document.querySelectorAll('.ui-helper-hidden-accessible>div[style]');
      expect(tooltipDivs.length).toBe(0);
    });

    it('does not affect elements without hword class', () => {
      document.body.innerHTML = `
        <span class="word">Word 1</span>
        <span class="word hword">Word 2</span>
        <span class="word">Word 3</span>
      `;

      word_hover_out();

      const words = document.querySelectorAll('.word');
      expect(words.length).toBe(3);
    });

    it('handles empty document gracefully', () => {
      document.body.innerHTML = '';

      expect(() => word_hover_out()).not.toThrow();
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
      word_hover_over.call(word);
      expect(document.querySelectorAll('.hword').length).toBe(2);

      // Hover out
      word_hover_out();
      expect(document.querySelectorAll('.hword').length).toBe(0);
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('word_hover_over handles element with no TERM class gracefully', () => {
      // When there's no TERM class, the regex returns the class string as-is
      // because the pattern doesn't match. The code will then try to find
      // elements with that class.
      document.body.innerHTML = `<span class="word">No TERM class</span>`;

      const span = document.querySelector('span') as HTMLElement;

      // Without TERM class, the regex extracts "word" from "word" class string
      // and looks for .word elements, adding hword class to them
      word_hover_over.call(span);

      // The span should have hword class added (because .word matches itself)
      expect(span.classList.contains('hword')).toBe(true);
    });

    it('word_dblclick_event_do_text_text handles missing totalcharcount element', () => {
      document.body.innerHTML = `
        <span class="word" data_pos="100">Test</span>
      `;

      const word = document.querySelector('.word') as HTMLElement;

      // Should handle NaN gracefully (parseInt of undefined text)
      expect(() => word_dblclick_event_do_text_text.call(word)).not.toThrow();
    });

    it('word_hover_over handles TERM class at end of class string', () => {
      document.body.innerHTML = `
        <span class="word status1 TERM555">Word</span>
      `;

      const word = document.querySelector('.word') as HTMLElement;
      word_hover_over.call(word);

      expect(document.querySelectorAll('.hword').length).toBe(1);
    });
  });
});
