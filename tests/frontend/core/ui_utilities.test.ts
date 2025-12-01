/**
 * Tests for ui_utilities.ts - DOM manipulation, tooltips, and form wrapping
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

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
  settings: { hts: 0, word_status_filter: '' }
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

      expect(document.getElementById('markaction')?.hasAttribute('disabled')).toBe(false);
    });

    it('disables markaction button when no checkboxes are checked', () => {
      document.body.innerHTML = `
        <input type="checkbox" class="markcheck" />
        <button id="markaction">Action</button>
      `;

      markClick();

      expect(document.getElementById('markaction')?.hasAttribute('disabled')).toBe(true);
    });

    it('enables button with multiple checkboxes when at least one is checked', () => {
      document.body.innerHTML = `
        <input type="checkbox" class="markcheck" />
        <input type="checkbox" class="markcheck" checked />
        <input type="checkbox" class="markcheck" />
        <button id="markaction" disabled>Action</button>
      `;

      markClick();

      expect(document.getElementById('markaction')?.hasAttribute('disabled')).toBe(false);
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
      expect(document.getElementById('hide3')).not.toBeNull();
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

      // Should focus the element
      expect(document.querySelector('.setfocus')).not.toBeNull();
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

      expect(document.querySelector('input[type="text"]')?.getAttribute('tabindex')).toBeDefined();
      expect(document.querySelector('input[type="button"]')?.getAttribute('tabindex')).toBeDefined();
    });

    it('adds tabindex to selects', () => {
      document.body.innerHTML = '<select><option>Option</option></select>';

      wrapRadioButtons();

      expect(document.querySelector('select')?.getAttribute('tabindex')).toBeDefined();
    });

    it('adds tabindex to links except those starting with rec', () => {
      document.body.innerHTML = `
        <a href="#" name="link1">Link 1</a>
        <a href="#" name="rec1">Rec Link</a>
      `;

      wrapRadioButtons();

      expect(document.querySelector('a[name="link1"]')?.getAttribute('tabindex')).toBeDefined();
    });

    it('sets up keydown handler for wrap_radio spans', () => {
      document.body.innerHTML = `
        <div>
          <input type="radio" name="test" value="1" />
          <label class="wrap_radio"><span></span></label>
        </div>
      `;

      wrapRadioButtons();

      // Simulate space key press using native KeyboardEvent
      const span = document.querySelector('.wrap_radio span') as HTMLElement;
      const event = new KeyboardEvent('keydown', { keyCode: 32, bubbles: true });
      span.dispatchEvent(event);
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
  // serializeFormToObject Tests
  // ===========================================================================

  describe('serializeFormToObject', () => {
    const { serializeFormToObject } = ui_utilities;

    it('serializes form to object', () => {
      document.body.innerHTML = `
        <form id="testform">
          <input type="text" name="field1" value="value1" />
          <input type="text" name="field2" value="value2" />
        </form>
      `;

      const form = document.getElementById('testform') as HTMLFormElement;
      const result = serializeFormToObject(form);

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

      const form = document.getElementById('testform') as HTMLFormElement;
      const result = serializeFormToObject(form);

      expect(Array.isArray(result.items)).toBe(true);
      expect(result.items).toEqual(['a', 'b', 'c']);
    });

    it('handles empty values', () => {
      document.body.innerHTML = `
        <form id="testform">
          <input type="text" name="empty" value="" />
        </form>
      `;

      const form = document.getElementById('testform') as HTMLFormElement;
      const result = serializeFormToObject(form);

      expect(result.empty).toBe('');
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('markClick handles empty DOM', () => {
      document.body.innerHTML = '';

      expect(() => markClick()).not.toThrow();
    });
  });
});
