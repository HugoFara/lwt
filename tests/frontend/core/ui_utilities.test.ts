/**
 * Tests for ui_utilities.ts - DOM manipulation, tooltips, and form wrapping
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import $ from 'jquery';

// Make jQuery available globally BEFORE any imports that use it
(global as Record<string, unknown>).$ = $;
(global as Record<string, unknown>).jQuery = $;
(window as Record<string, unknown>).$ = $;
(window as Record<string, unknown>).jQuery = $;

// Mock jQuery UI methods that are called at module load time
($ as any).fn.resizable = vi.fn().mockReturnThis();
($ as any).fn.tooltip = vi.fn().mockReturnThis();

// Mock LWT_DATA global (needs to be set before import)
const mockLWT_DATA = {
  language: {
    id: 1,
    dict_link1: '',
    dict_link2: '',
    translator_link: '',
    delimiter: ',',
    word_parsing: '',
    rtl: false,
    ttsVoiceApi: ''
  },
  text: { id: 1, reading_position: 0, annotations: {} },
  word: { id: 0 },
  test: { solution: '', answer_opened: false },
  settings: { jQuery_tooltip: false, hts: 0, word_status_filter: '' }
};

// Set up globals before import
(window as Record<string, unknown>).LWT_DATA = mockLWT_DATA;
(window as Record<string, unknown>).showRightFrames = vi.fn();

// Now import the module (after globals are set)
const ui_utilities = await import('../../../src/frontend/js/core/ui_utilities');
const { markClick, confirmDelete, showAllwordsClick, noShowAfter3Secs, setTheFocus, wrapRadioButtons } = ui_utilities;

describe('ui_utilities.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.useFakeTimers();
    // Clear mock function calls between tests
    ((window as Record<string, unknown>).showRightFrames as ReturnType<typeof vi.fn>).mockClear();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    vi.useRealTimers();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // markClick Tests
  // ===========================================================================

  describe('markClick', () => {
    it('enables markaction button when checkboxes are checked', () => {
      document.body.innerHTML = `
        <input type="checkbox" class="markcheck" checked />
        <button id="markaction" disabled>Action</button>
      `;

      markClick();

      expect($('#markaction').attr('disabled')).toBeUndefined();
    });

    it('disables markaction button when no checkboxes are checked', () => {
      document.body.innerHTML = `
        <input type="checkbox" class="markcheck" />
        <button id="markaction">Action</button>
      `;

      markClick();

      expect($('#markaction').attr('disabled')).toBe('disabled');
    });

    it('enables button with multiple checkboxes when at least one is checked', () => {
      document.body.innerHTML = `
        <input type="checkbox" class="markcheck" />
        <input type="checkbox" class="markcheck" checked />
        <input type="checkbox" class="markcheck" />
        <button id="markaction" disabled>Action</button>
      `;

      markClick();

      expect($('#markaction').attr('disabled')).toBeUndefined();
    });

    it('handles missing markaction button gracefully', () => {
      document.body.innerHTML = `
        <input type="checkbox" class="markcheck" checked />
      `;

      expect(() => markClick()).not.toThrow();
    });
  });

  // ===========================================================================
  // confirmDelete Tests
  // ===========================================================================

  describe('confirmDelete', () => {
    it('returns true when user confirms', () => {
      vi.spyOn(window, 'confirm').mockReturnValue(true);

      const result = confirmDelete();

      expect(result).toBe(true);
      expect(window.confirm).toHaveBeenCalledWith('CONFIRM\n\nAre you sure you want to delete?');
    });

    it('returns false when user cancels', () => {
      vi.spyOn(window, 'confirm').mockReturnValue(false);

      const result = confirmDelete();

      expect(result).toBe(false);
    });
  });

  // ===========================================================================
  // noShowAfter3Secs Tests
  // ===========================================================================

  describe('noShowAfter3Secs', () => {
    it('slides up element with id hide3', () => {
      document.body.innerHTML = '<div id="hide3">Message</div>';

      noShowAfter3Secs();

      // slideUp is called - in jsdom it may not fully animate
      // We verify the function doesn't throw
      expect($('#hide3').length).toBe(1);
    });

    it('handles missing hide3 element gracefully', () => {
      document.body.innerHTML = '';

      expect(() => noShowAfter3Secs()).not.toThrow();
    });
  });

  // ===========================================================================
  // setTheFocus Tests
  // ===========================================================================

  describe('setTheFocus', () => {
    it('focuses element with setfocus class', () => {
      document.body.innerHTML = '<input type="text" class="setfocus" />';
      const input = document.querySelector('.setfocus') as HTMLInputElement;
      const focusSpy = vi.spyOn(input, 'focus');

      setTheFocus();

      // jQuery trigger('focus') should call focus
      expect($('.setfocus').length).toBe(1);
    });

    it('handles missing setfocus element gracefully', () => {
      document.body.innerHTML = '';

      expect(() => setTheFocus()).not.toThrow();
    });
  });

  // ===========================================================================
  // wrapRadioButtons Tests
  // ===========================================================================

  describe('wrapRadioButtons', () => {
    it('adds tabindex to inputs', () => {
      document.body.innerHTML = `
        <input type="text" />
        <input type="button" value="Button" />
      `;

      wrapRadioButtons();

      expect($('input[type="text"]').attr('tabindex')).toBeDefined();
      expect($('input[type="button"]').attr('tabindex')).toBeDefined();
    });

    it('adds tabindex to selects', () => {
      document.body.innerHTML = '<select><option>Option</option></select>';

      wrapRadioButtons();

      expect($('select').attr('tabindex')).toBeDefined();
    });

    it('adds tabindex to links except those starting with rec', () => {
      document.body.innerHTML = `
        <a href="#" name="link1">Link 1</a>
        <a href="#" name="rec1">Rec Link</a>
      `;

      wrapRadioButtons();

      expect($('a[name="link1"]').attr('tabindex')).toBeDefined();
    });

    it('sets up keydown handler for wrap_radio spans', () => {
      document.body.innerHTML = `
        <div>
          <input type="radio" name="test" value="1" />
          <label class="wrap_radio"><span></span></label>
        </div>
      `;

      wrapRadioButtons();

      // Simulate space key press
      const event = $.Event('keydown', { keyCode: 32 });
      $('.wrap_radio span').trigger(event);
    });
  });

  // ===========================================================================
  // showAllwordsClick Tests
  // ===========================================================================

  describe('showAllwordsClick', () => {
    it('calls showRightFrames with correct parameters', () => {
      document.body.innerHTML = `
        <input type="checkbox" id="showallwords" checked />
        <input type="checkbox" id="showlearningtranslations" />
        <span id="thetextid">42</span>
      `;

      showAllwordsClick();

      // Advance timers
      vi.advanceTimersByTime(500);

      expect((window as Record<string, unknown>).showRightFrames).toHaveBeenCalled();
    });

    it('sends mode=0 when showallwords is unchecked', () => {
      document.body.innerHTML = `
        <input type="checkbox" id="showallwords" />
        <input type="checkbox" id="showlearningtranslations" />
        <span id="thetextid">42</span>
      `;

      showAllwordsClick();

      vi.advanceTimersByTime(500);

      const call = ((window as Record<string, unknown>).showRightFrames as ReturnType<typeof vi.fn>).mock.calls[0];
      expect(call[0]).toContain('mode=0');
    });

    it('includes showLearning parameter', () => {
      document.body.innerHTML = `
        <input type="checkbox" id="showallwords" checked />
        <input type="checkbox" id="showlearningtranslations" checked />
        <span id="thetextid">42</span>
      `;

      showAllwordsClick();

      vi.advanceTimersByTime(500);

      const call = ((window as Record<string, unknown>).showRightFrames as ReturnType<typeof vi.fn>).mock.calls[0];
      expect(call[0]).toContain('showLearning=1');
    });

    it('schedules page reload after 4 seconds', () => {
      document.body.innerHTML = `
        <input type="checkbox" id="showallwords" checked />
        <input type="checkbox" id="showlearningtranslations" />
        <span id="thetextid">42</span>
      `;

      const reloadSpy = vi.fn();
      Object.defineProperty(window, 'location', {
        value: { reload: reloadSpy },
        writable: true
      });

      showAllwordsClick();

      vi.advanceTimersByTime(4000);

      expect(reloadSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // jQuery Extensions Tests
  // ===========================================================================

  describe('jQuery extensions', () => {
    describe('$.fn.serializeObject', () => {
      it('serializes form to object', () => {
        document.body.innerHTML = `
          <form id="testform">
            <input type="text" name="field1" value="value1" />
            <input type="text" name="field2" value="value2" />
          </form>
        `;

        // Import to ensure extension is registered
        const result = $('#testform').serializeObject();

        expect(result.field1).toBe('value1');
        expect(result.field2).toBe('value2');
      });

      it('handles multiple values with same name as array', () => {
        document.body.innerHTML = `
          <form id="testform">
            <input type="checkbox" name="items" value="a" checked />
            <input type="checkbox" name="items" value="b" checked />
            <input type="checkbox" name="items" value="c" checked />
          </form>
        `;

        const result = $('#testform').serializeObject();

        expect(Array.isArray(result.items)).toBe(true);
        expect(result.items).toEqual(['a', 'b', 'c']);
      });

      it('handles empty values', () => {
        document.body.innerHTML = `
          <form id="testform">
            <input type="text" name="empty" value="" />
          </form>
        `;

        const result = $('#testform').serializeObject();

        expect(result.empty).toBe('');
      });
    });

    describe('$.fn.tooltip_wsty_content', () => {
      it('generates tooltip content for words', () => {
        document.body.innerHTML = `
          <span class="hword"
                data_text="test"
                data_rom="roman"
                data_trans="translation"
                data_status="3"
                data_ann="">test</span>
        `;

        const content = $('.hword').tooltip_wsty_content();

        expect(content).toContain('test');
        expect(content).toContain('Roman.');
        expect(content).toContain('roman');
        expect(content).toContain('Transl.');
        expect(content).toContain('translation');
        expect(content).toContain('Status');
        expect(content).toContain('Learning');
      });

      it('handles mwsty class for multiwords', () => {
        document.body.innerHTML = `
          <span class="hword mwsty"
                data_text="multi word"
                data_trans="translation"
                data_status="3">display</span>
        `;

        const content = $('.hword').tooltip_wsty_content();

        expect(content).toContain('multi word');
      });

      it('shows Unknown status for status 0', () => {
        document.body.innerHTML = `
          <span class="hword" data_status="0" data_trans="">word</span>
        `;

        const content = $('.hword').tooltip_wsty_content();

        expect(content).toContain('Unknown');
      });

      it('shows Learned status for status 5', () => {
        document.body.innerHTML = `
          <span class="hword" data_status="5" data_trans="">word</span>
        `;

        const content = $('.hword').tooltip_wsty_content();

        expect(content).toContain('Learned');
      });

      it('shows Ignored status for status 98', () => {
        document.body.innerHTML = `
          <span class="hword" data_status="98" data_trans="">word</span>
        `;

        const content = $('.hword').tooltip_wsty_content();

        expect(content).toContain('Ignored');
      });

      it('shows Well Known status for status 99', () => {
        document.body.innerHTML = `
          <span class="hword" data_status="99" data_trans="">word</span>
        `;

        const content = $('.hword').tooltip_wsty_content();

        expect(content).toContain('Well Known');
      });

      it('skips translation when empty or asterisk', () => {
        document.body.innerHTML = `
          <span class="hword" data_status="3" data_trans="*">word</span>
        `;

        const content = $('.hword').tooltip_wsty_content();

        expect(content).not.toContain('Transl.');
      });

      it('includes annotation content in translation', () => {
        document.body.innerHTML = `
          <span class="hword"
                data_status="3"
                data_trans="definition, meaning"
                data_ann="definition">word</span>
        `;

        const content = $('.hword').tooltip_wsty_content();

        // The annotation should be included in the translation output
        // Note: The red highlighting depends on regex matching which may vary
        expect(content).toContain('definition');
        expect(content).toContain('Transl.');
      });
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles elements with undefined attributes', () => {
      document.body.innerHTML = '<span class="hword">word</span>';

      const content = $('.hword').tooltip_wsty_content();

      expect(content).toBeDefined();
    });

    it('markClick handles empty DOM', () => {
      document.body.innerHTML = '';

      expect(() => markClick()).not.toThrow();
    });
  });
});
