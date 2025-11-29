/**
 * Tests for word_list_table.ts - Word list table functionality
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { initWordListTable } from '../../../src/frontend/js/words/word_list_table';

// Mock bulk_actions module
vi.mock('../../../src/frontend/js/forms/bulk_actions', () => ({
  selectToggle: vi.fn(),
  multiActionGo: vi.fn(),
  allActionGo: vi.fn()
}));

import { selectToggle, multiActionGo, allActionGo } from '../../../src/frontend/js/forms/bulk_actions';

describe('word_list_table.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // initWordListTable Tests
  // ===========================================================================

  describe('initWordListTable', () => {
    it('returns early when form2 does not exist', () => {
      document.body.innerHTML = '<div>No form here</div>';

      expect(() => initWordListTable()).not.toThrow();
    });

    it('does not throw when form2 has no action elements', () => {
      document.body.innerHTML = `
        <form name="form2">
          <input type="text" />
        </form>
      `;

      expect(() => initWordListTable()).not.toThrow();
    });
  });

  // ===========================================================================
  // All Action Select Tests
  // ===========================================================================

  describe('All action select', () => {
    it('calls allActionGo on change', () => {
      document.body.innerHTML = `
        <form name="form2">
          <select data-action="all-action" data-recno="25">
            <option value="">Select</option>
            <option value="delall">Delete All</option>
          </select>
        </form>
      `;

      initWordListTable();

      const allActionSelect = document.querySelector('[data-action="all-action"]') as HTMLSelectElement;
      allActionSelect.value = 'delall';
      allActionSelect.dispatchEvent(new Event('change'));

      expect(allActionGo).toHaveBeenCalledWith(
        document.forms.namedItem('form2'),
        allActionSelect,
        25
      );
    });

    it('parses recno from data attribute', () => {
      document.body.innerHTML = `
        <form name="form2">
          <select data-action="all-action" data-recno="100">
            <option value="exportall">Export All</option>
          </select>
        </form>
      `;

      initWordListTable();

      const allActionSelect = document.querySelector('[data-action="all-action"]') as HTMLSelectElement;
      allActionSelect.dispatchEvent(new Event('change'));

      expect(allActionGo).toHaveBeenCalledWith(
        expect.any(HTMLFormElement),
        allActionSelect,
        100
      );
    });

    it('defaults to 0 when recno is missing', () => {
      document.body.innerHTML = `
        <form name="form2">
          <select data-action="all-action">
            <option value="delall">Delete All</option>
          </select>
        </form>
      `;

      initWordListTable();

      const allActionSelect = document.querySelector('[data-action="all-action"]') as HTMLSelectElement;
      allActionSelect.dispatchEvent(new Event('change'));

      expect(allActionGo).toHaveBeenCalledWith(
        expect.any(HTMLFormElement),
        allActionSelect,
        0
      );
    });

    it('handles non-numeric recno', () => {
      document.body.innerHTML = `
        <form name="form2">
          <select data-action="all-action" data-recno="invalid">
            <option value="delall">Delete All</option>
          </select>
        </form>
      `;

      initWordListTable();

      const allActionSelect = document.querySelector('[data-action="all-action"]') as HTMLSelectElement;
      allActionSelect.dispatchEvent(new Event('change'));

      expect(allActionGo).toHaveBeenCalledWith(
        expect.any(HTMLFormElement),
        allActionSelect,
        NaN
      );
    });
  });

  // ===========================================================================
  // Mark All Button Tests
  // ===========================================================================

  describe('Mark All button', () => {
    it('calls selectToggle with true on click', () => {
      document.body.innerHTML = `
        <form name="form2">
          <button data-action="mark-all">Mark All</button>
        </form>
      `;

      initWordListTable();

      const markAllButton = document.querySelector('[data-action="mark-all"]') as HTMLButtonElement;
      markAllButton.click();

      expect(selectToggle).toHaveBeenCalledWith(true, 'form2');
    });

    it('prevents default action on click', () => {
      document.body.innerHTML = `
        <form name="form2">
          <button data-action="mark-all">Mark All</button>
        </form>
      `;

      initWordListTable();

      const markAllButton = document.querySelector('[data-action="mark-all"]') as HTMLButtonElement;
      const clickEvent = new MouseEvent('click', { cancelable: true });
      markAllButton.dispatchEvent(clickEvent);

      expect(clickEvent.defaultPrevented).toBe(true);
    });
  });

  // ===========================================================================
  // Mark None Button Tests
  // ===========================================================================

  describe('Mark None button', () => {
    it('calls selectToggle with false on click', () => {
      document.body.innerHTML = `
        <form name="form2">
          <button data-action="mark-none">Mark None</button>
        </form>
      `;

      initWordListTable();

      const markNoneButton = document.querySelector('[data-action="mark-none"]') as HTMLButtonElement;
      markNoneButton.click();

      expect(selectToggle).toHaveBeenCalledWith(false, 'form2');
    });

    it('prevents default action on click', () => {
      document.body.innerHTML = `
        <form name="form2">
          <button data-action="mark-none">Mark None</button>
        </form>
      `;

      initWordListTable();

      const markNoneButton = document.querySelector('[data-action="mark-none"]') as HTMLButtonElement;
      const clickEvent = new MouseEvent('click', { cancelable: true });
      markNoneButton.dispatchEvent(clickEvent);

      expect(clickEvent.defaultPrevented).toBe(true);
    });
  });

  // ===========================================================================
  // Mark Action Select Tests
  // ===========================================================================

  describe('Mark action select', () => {
    it('calls multiActionGo on change', () => {
      document.body.innerHTML = `
        <form name="form2">
          <select data-action="mark-action">
            <option value="">Select</option>
            <option value="del">Delete</option>
          </select>
        </form>
      `;

      initWordListTable();

      const markActionSelect = document.querySelector('[data-action="mark-action"]') as HTMLSelectElement;
      markActionSelect.value = 'del';
      markActionSelect.dispatchEvent(new Event('change'));

      expect(multiActionGo).toHaveBeenCalledWith(
        document.forms.namedItem('form2'),
        markActionSelect
      );
    });
  });

  // ===========================================================================
  // Wait Info Tests
  // ===========================================================================

  describe('Wait info element', () => {
    it('hides waitinfo element on init', () => {
      document.body.innerHTML = `
        <div id="waitinfo">Loading...</div>
        <form name="form2"></form>
      `;

      initWordListTable();

      const waitInfo = document.getElementById('waitinfo');
      expect(waitInfo?.classList.contains('hide')).toBe(true);
    });

    it('does not throw when waitinfo element is missing', () => {
      document.body.innerHTML = `
        <form name="form2"></form>
      `;

      expect(() => initWordListTable()).not.toThrow();
    });
  });

  // ===========================================================================
  // Window Export Tests
  // ===========================================================================

  describe('Window export', () => {
    it('exports initWordListTable to window', () => {
      expect(typeof window.initWordListTable).toBe('function');
    });
  });

  // ===========================================================================
  // Integration Tests
  // ===========================================================================

  describe('Integration', () => {
    it('initializes all action handlers in a complete form', () => {
      document.body.innerHTML = `
        <div id="waitinfo">Loading...</div>
        <form name="form2">
          <select data-action="all-action" data-recno="50">
            <option value="delall">Delete All</option>
          </select>
          <button data-action="mark-all">Mark All</button>
          <button data-action="mark-none">Mark None</button>
          <select data-action="mark-action">
            <option value="del">Delete</option>
          </select>
        </form>
      `;

      initWordListTable();

      // Trigger all actions
      (document.querySelector('[data-action="all-action"]') as HTMLSelectElement).dispatchEvent(new Event('change'));
      (document.querySelector('[data-action="mark-all"]') as HTMLButtonElement).click();
      (document.querySelector('[data-action="mark-none"]') as HTMLButtonElement).click();
      (document.querySelector('[data-action="mark-action"]') as HTMLSelectElement).dispatchEvent(new Event('change'));

      expect(allActionGo).toHaveBeenCalled();
      expect(selectToggle).toHaveBeenCalledWith(true, 'form2');
      expect(selectToggle).toHaveBeenCalledWith(false, 'form2');
      expect(multiActionGo).toHaveBeenCalled();
      expect(document.getElementById('waitinfo')?.classList.contains('hide')).toBe(true);
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles multiple calls to initWordListTable', () => {
      document.body.innerHTML = `
        <form name="form2">
          <button data-action="mark-all">Mark All</button>
        </form>
      `;

      initWordListTable();
      initWordListTable();

      const markAllButton = document.querySelector('[data-action="mark-all"]') as HTMLButtonElement;
      markAllButton.click();

      // Should be called twice (once per init)
      expect(selectToggle).toHaveBeenCalledTimes(2);
    });

    it('handles form with mixed valid and invalid elements', () => {
      document.body.innerHTML = `
        <form name="form2">
          <button data-action="mark-all">Mark All</button>
          <button data-action="unknown-action">Unknown</button>
          <select data-action="invalid-action">
            <option value="test">Test</option>
          </select>
        </form>
      `;

      expect(() => initWordListTable()).not.toThrow();

      const markAllButton = document.querySelector('[data-action="mark-all"]') as HTMLButtonElement;
      markAllButton.click();

      expect(selectToggle).toHaveBeenCalled();
    });
  });
});
