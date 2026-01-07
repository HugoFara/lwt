/**
 * Tests for tag_list.ts - Tag list page functionality
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { initTagList } from '../../../src/frontend/js/modules/tags/pages/tag_list';

// Mock bulk_actions module
vi.mock('../../../src/frontend/js/shared/forms/bulk_actions', () => ({
  selectToggle: vi.fn(),
  multiActionGo: vi.fn(),
  allActionGo: vi.fn()
}));

import { selectToggle, multiActionGo, allActionGo } from '../../../src/frontend/js/shared/forms/bulk_actions';

describe('tag_list.ts', () => {
  // Store original location
  const originalLocation = window.location;

  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();

    // Mock location.href
    delete (window as any).location;
    (window as any).location = {
      href: 'http://localhost/tags/term',
      assign: vi.fn(),
      replace: vi.fn(),
    };
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
    window.location = originalLocation;
  });

  // ===========================================================================
  // initTagList Tests
  // ===========================================================================

  describe('initTagList', () => {
    it('returns early when form1 does not exist', () => {
      document.body.innerHTML = '<div>No form here</div>';

      expect(() => initTagList()).not.toThrow();
    });

    it('initializes both filter and table when both forms exist', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="reset-all" data-base-url="/tags/term">Reset All</button>
        </form>
        <form name="form2">
          <button data-action="mark-all">Mark All</button>
        </form>
      `;

      expect(() => initTagList()).not.toThrow();
    });
  });

  // ===========================================================================
  // Tag List Filter Tests
  // ===========================================================================

  describe('Tag list filter', () => {
    it('prevents default form submission', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="filter-query">Query</button>
        </form>
      `;

      initTagList();

      const form = document.forms.namedItem('form1') as HTMLFormElement;
      const submitEvent = new Event('submit', { cancelable: true });
      form.dispatchEvent(submitEvent);

      expect(submitEvent.defaultPrevented).toBe(true);
    });

    it('clicks query button on form submission', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="filter-query">Query</button>
        </form>
      `;

      initTagList();

      const queryButton = document.querySelector('[data-action="filter-query"]') as HTMLButtonElement;
      const clickSpy = vi.spyOn(queryButton, 'click');

      const form = document.forms.namedItem('form1') as HTMLFormElement;
      form.dispatchEvent(new Event('submit'));

      expect(clickSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // Reset All Button Tests
  // ===========================================================================

  describe('Reset All button', () => {
    it('navigates with empty query on click', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="reset-all" data-base-url="/tags/term">Reset All</button>
        </form>
      `;

      initTagList();

      const resetButton = document.querySelector('[data-action="reset-all"]') as HTMLButtonElement;
      resetButton.click();

      expect(window.location.href).toContain('query=');
      expect(window.location.href).toContain('page=1');
    });

    it('prevents default action on click', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="reset-all">Reset All</button>
        </form>
      `;

      initTagList();

      const resetButton = document.querySelector('[data-action="reset-all"]') as HTMLButtonElement;
      const clickEvent = new MouseEvent('click', { cancelable: true });
      resetButton.dispatchEvent(clickEvent);

      expect(clickEvent.defaultPrevented).toBe(true);
    });

    it('uses custom base URL from data attribute', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="reset-all" data-base-url="/tags/text">Reset All</button>
        </form>
      `;

      initTagList();

      const resetButton = document.querySelector('[data-action="reset-all"]') as HTMLButtonElement;
      resetButton.click();

      expect(window.location.href).toContain('/tags/text');
    });

    it('defaults to /tags/term when data-base-url is missing', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="reset-all">Reset All</button>
        </form>
      `;

      initTagList();

      const resetButton = document.querySelector('[data-action="reset-all"]') as HTMLButtonElement;
      resetButton.click();

      expect(window.location.href).toContain('/tags/term');
    });
  });

  // ===========================================================================
  // Query Filter Button Tests
  // ===========================================================================

  describe('Query filter button', () => {
    it('navigates with query value on click', () => {
      document.body.innerHTML = `
        <form name="form1">
          <input type="text" name="query" value="verb" />
          <button data-action="filter-query">Query</button>
        </form>
      `;

      initTagList();

      const queryButton = document.querySelector('[data-action="filter-query"]') as HTMLButtonElement;
      queryButton.click();

      expect(window.location.href).toContain('query=verb');
      expect(window.location.href).toContain('page=1');
    });

    it('prevents default action on click', () => {
      document.body.innerHTML = `
        <form name="form1">
          <input type="text" name="query" value="" />
          <button data-action="filter-query">Query</button>
        </form>
      `;

      initTagList();

      const queryButton = document.querySelector('[data-action="filter-query"]') as HTMLButtonElement;
      const clickEvent = new MouseEvent('click', { cancelable: true });
      queryButton.dispatchEvent(clickEvent);

      expect(clickEvent.defaultPrevented).toBe(true);
    });

    it('uses empty string when query input is missing', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="filter-query">Query</button>
        </form>
      `;

      initTagList();

      const queryButton = document.querySelector('[data-action="filter-query"]') as HTMLButtonElement;
      queryButton.click();

      expect(window.location.href).toContain('query=');
    });
  });

  // ===========================================================================
  // Query Clear Button Tests
  // ===========================================================================

  describe('Query clear button', () => {
    it('navigates with empty query on click', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="clear-query">Clear</button>
        </form>
      `;

      initTagList();

      const clearButton = document.querySelector('[data-action="clear-query"]') as HTMLButtonElement;
      clearButton.click();

      expect(window.location.href).toContain('query=');
      expect(window.location.href).toContain('page=1');
    });

    it('prevents default action on click', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="clear-query">Clear</button>
        </form>
      `;

      initTagList();

      const clearButton = document.querySelector('[data-action="clear-query"]') as HTMLButtonElement;
      const clickEvent = new MouseEvent('click', { cancelable: true });
      clearButton.dispatchEvent(clickEvent);

      expect(clickEvent.defaultPrevented).toBe(true);
    });
  });

  // ===========================================================================
  // Sort Order Select Tests
  // ===========================================================================

  describe('Sort order select', () => {
    it('navigates with sort parameter on change', () => {
      document.body.innerHTML = `
        <form name="form1">
          <select data-action="sort">
            <option value="1">Tag A-Z</option>
            <option value="2">Tag Z-A</option>
          </select>
        </form>
      `;

      initTagList();

      const sortSelect = document.querySelector('[data-action="sort"]') as HTMLSelectElement;
      sortSelect.value = '2';
      sortSelect.dispatchEvent(new Event('change'));

      expect(window.location.href).toContain('sort=2');
      expect(window.location.href).toContain('page=1');
    });
  });

  // ===========================================================================
  // Tag List Table Tests (form2)
  // ===========================================================================

  describe('Tag list table', () => {
    it('returns early when form2 does not exist', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="reset-all">Reset</button>
        </form>
      `;

      expect(() => initTagList()).not.toThrow();
    });
  });

  // ===========================================================================
  // All Action Select Tests
  // ===========================================================================

  describe('All action select', () => {
    it('calls allActionGo on change', () => {
      document.body.innerHTML = `
        <form name="form1"></form>
        <form name="form2">
          <select data-action="all-action" data-recno="15">
            <option value="">Select</option>
            <option value="delall">Delete All</option>
          </select>
        </form>
      `;

      initTagList();

      const allActionSelect = document.querySelector('[data-action="all-action"]') as HTMLSelectElement;
      allActionSelect.value = 'delall';
      allActionSelect.dispatchEvent(new Event('change'));

      expect(allActionGo).toHaveBeenCalledWith(
        document.forms.namedItem('form2'),
        allActionSelect,
        15
      );
    });

    it('defaults to 0 when recno is missing', () => {
      document.body.innerHTML = `
        <form name="form1"></form>
        <form name="form2">
          <select data-action="all-action">
            <option value="delall">Delete All</option>
          </select>
        </form>
      `;

      initTagList();

      const allActionSelect = document.querySelector('[data-action="all-action"]') as HTMLSelectElement;
      allActionSelect.dispatchEvent(new Event('change'));

      expect(allActionGo).toHaveBeenCalledWith(
        expect.any(HTMLFormElement),
        allActionSelect,
        0
      );
    });
  });

  // ===========================================================================
  // Mark All Button Tests
  // ===========================================================================

  describe('Mark All button', () => {
    it('calls selectToggle with true on click', () => {
      document.body.innerHTML = `
        <form name="form1"></form>
        <form name="form2">
          <button data-action="mark-all">Mark All</button>
        </form>
      `;

      initTagList();

      const markAllButton = document.querySelector('[data-action="mark-all"]') as HTMLButtonElement;
      markAllButton.click();

      expect(selectToggle).toHaveBeenCalledWith(true, 'form2');
    });

    it('prevents default action on click', () => {
      document.body.innerHTML = `
        <form name="form1"></form>
        <form name="form2">
          <button data-action="mark-all">Mark All</button>
        </form>
      `;

      initTagList();

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
        <form name="form1"></form>
        <form name="form2">
          <button data-action="mark-none">Mark None</button>
        </form>
      `;

      initTagList();

      const markNoneButton = document.querySelector('[data-action="mark-none"]') as HTMLButtonElement;
      markNoneButton.click();

      expect(selectToggle).toHaveBeenCalledWith(false, 'form2');
    });

    it('prevents default action on click', () => {
      document.body.innerHTML = `
        <form name="form1"></form>
        <form name="form2">
          <button data-action="mark-none">Mark None</button>
        </form>
      `;

      initTagList();

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
        <form name="form1"></form>
        <form name="form2">
          <select data-action="mark-action">
            <option value="">Select</option>
            <option value="del">Delete</option>
          </select>
        </form>
      `;

      initTagList();

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
  // Window Export Tests
  // ===========================================================================

  describe('Window export', () => {
    it('exports initTagList to window', () => {
      expect(typeof window.initTagList).toBe('function');
    });
  });

  // ===========================================================================
  // Text Tags Page Tests
  // ===========================================================================

  describe('Text tags page', () => {
    it('uses /tags/text base URL for text tags page', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="reset-all" data-base-url="/tags/text">Reset All</button>
          <select data-action="sort">
            <option value="1">Sort</option>
          </select>
        </form>
      `;

      initTagList();

      const sortSelect = document.querySelector('[data-action="sort"]') as HTMLSelectElement;
      sortSelect.dispatchEvent(new Event('change'));

      expect(window.location.href).toContain('/tags/text');
    });
  });

  // ===========================================================================
  // Integration Tests
  // ===========================================================================

  describe('Integration', () => {
    it('initializes all action handlers in a complete page', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="reset-all" data-base-url="/tags/term">Reset All</button>
          <input type="text" name="query" value="test" />
          <button data-action="filter-query">Query</button>
          <button data-action="clear-query">Clear</button>
          <select data-action="sort">
            <option value="1">Sort</option>
          </select>
        </form>
        <form name="form2">
          <select data-action="all-action" data-recno="10">
            <option value="delall">Delete All</option>
          </select>
          <button data-action="mark-all">Mark All</button>
          <button data-action="mark-none">Mark None</button>
          <select data-action="mark-action">
            <option value="del">Delete</option>
          </select>
        </form>
      `;

      initTagList();

      // Trigger filter actions
      (document.querySelector('[data-action="reset-all"]') as HTMLButtonElement).click();
      expect(window.location.href).toContain('query=');

      // Reset location for next test
      window.location.href = 'http://localhost/tags/term';

      (document.querySelector('[data-action="filter-query"]') as HTMLButtonElement).click();
      expect(window.location.href).toContain('query=test');

      // Trigger table actions
      (document.querySelector('[data-action="all-action"]') as HTMLSelectElement).dispatchEvent(new Event('change'));
      expect(allActionGo).toHaveBeenCalled();

      (document.querySelector('[data-action="mark-all"]') as HTMLButtonElement).click();
      expect(selectToggle).toHaveBeenCalledWith(true, 'form2');

      (document.querySelector('[data-action="mark-none"]') as HTMLButtonElement).click();
      expect(selectToggle).toHaveBeenCalledWith(false, 'form2');

      (document.querySelector('[data-action="mark-action"]') as HTMLSelectElement).dispatchEvent(new Event('change'));
      expect(multiActionGo).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles form with no action elements', () => {
      document.body.innerHTML = `
        <form name="form1">
          <input type="text" />
        </form>
        <form name="form2">
          <input type="checkbox" />
        </form>
      `;

      expect(() => initTagList()).not.toThrow();
    });

    it('handles multiple calls to initTagList', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="reset-all">Reset</button>
        </form>
      `;

      initTagList();
      initTagList();

      const resetButton = document.querySelector('[data-action="reset-all"]') as HTMLButtonElement;
      resetButton.click();

      // Location should be updated (both handlers fire)
      expect(window.location.href).toContain('query=');
    });

    it('handles special characters in query', () => {
      document.body.innerHTML = `
        <form name="form1">
          <input type="text" name="query" value="test&value" />
          <button data-action="filter-query">Query</button>
        </form>
      `;

      initTagList();

      const queryButton = document.querySelector('[data-action="filter-query"]') as HTMLButtonElement;
      queryButton.click();

      expect(window.location.href).toContain('test%26value');
    });

    it('handles empty query input value', () => {
      document.body.innerHTML = `
        <form name="form1">
          <input type="text" name="query" value="" />
          <button data-action="filter-query">Query</button>
        </form>
      `;

      initTagList();

      const queryButton = document.querySelector('[data-action="filter-query"]') as HTMLButtonElement;
      queryButton.click();

      expect(window.location.href).toContain('query=');
    });
  });
});
