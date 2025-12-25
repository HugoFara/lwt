/**
 * Tests for word_list_filter.ts - Word list filter page functionality
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { initWordListFilter } from '../../../src/frontend/js/modules/vocabulary/stores/word_list_filter';

// Mock language_settings module
vi.mock('../../../src/frontend/js/modules/language/stores/language_settings', () => ({
  setLang: vi.fn(),
  resetAll: vi.fn()
}));

import { setLang, resetAll } from '../../../src/frontend/js/modules/language/stores/language_settings';

describe('word_list_filter.ts', () => {
  // Store original location
  const originalLocation = window.location;

  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();

    // Mock location.href
    delete (window as any).location;
    (window as any).location = {
      href: 'http://localhost/words/edit',
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
  // initWordListFilter Tests
  // ===========================================================================

  describe('initWordListFilter', () => {
    it('returns early when form1 does not exist', () => {
      document.body.innerHTML = '<div>No form here</div>';

      expect(() => initWordListFilter()).not.toThrow();
    });

    it('prevents default form submission', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button type="submit" data-action="filter-query">Query</button>
        </form>
      `;

      initWordListFilter();

      const form = document.forms.namedItem('form1') as HTMLFormElement;
      const submitEvent = new Event('submit', { cancelable: true });
      form.dispatchEvent(submitEvent);

      expect(submitEvent.defaultPrevented).toBe(true);
    });

    it('clicks query button on form submission', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button type="submit" data-action="filter-query">Query</button>
        </form>
      `;

      initWordListFilter();

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
    it('calls resetAll with BASE_URL on click', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="reset-all">Reset All</button>
        </form>
      `;

      initWordListFilter();

      const resetButton = document.querySelector('[data-action="reset-all"]') as HTMLButtonElement;
      resetButton.click();

      expect(resetAll).toHaveBeenCalledWith('/words/edit');
    });

    it('prevents default action on click', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="reset-all">Reset All</button>
        </form>
      `;

      initWordListFilter();

      const resetButton = document.querySelector('[data-action="reset-all"]') as HTMLButtonElement;
      const clickEvent = new MouseEvent('click', { cancelable: true });
      resetButton.dispatchEvent(clickEvent);

      expect(clickEvent.defaultPrevented).toBe(true);
    });
  });

  // ===========================================================================
  // Language Filter Tests
  // ===========================================================================

  describe('Language filter select', () => {
    it('calls setLang on change', () => {
      document.body.innerHTML = `
        <form name="form1">
          <select data-action="filter-language">
            <option value="1">English</option>
            <option value="2">French</option>
          </select>
        </form>
      `;

      initWordListFilter();

      const langSelect = document.querySelector('[data-action="filter-language"]') as HTMLSelectElement;
      langSelect.value = '2';
      langSelect.dispatchEvent(new Event('change'));

      expect(setLang).toHaveBeenCalledWith(langSelect, '/words/edit');
    });
  });

  // ===========================================================================
  // Text Mode Select Tests
  // ===========================================================================

  describe('Text mode select', () => {
    it('navigates with text_mode parameter on change', () => {
      document.body.innerHTML = `
        <form name="form1">
          <select data-action="text-mode">
            <option value="0">Texts</option>
            <option value="1">Tags</option>
          </select>
        </form>
      `;

      initWordListFilter();

      const textModeSelect = document.querySelector('[data-action="text-mode"]') as HTMLSelectElement;
      textModeSelect.value = '1';
      textModeSelect.dispatchEvent(new Event('change'));

      expect(window.location.href).toContain('page=1');
      expect(window.location.href).toContain('text_mode=1');
      expect(window.location.href).toContain('texttag=');
      expect(window.location.href).toContain('text=');
    });

    it('clears texttag and text when changing mode', () => {
      document.body.innerHTML = `
        <form name="form1">
          <select data-action="text-mode">
            <option value="1">Tags</option>
          </select>
        </form>
      `;

      initWordListFilter();

      const textModeSelect = document.querySelector('[data-action="text-mode"]') as HTMLSelectElement;
      textModeSelect.dispatchEvent(new Event('change'));

      expect(window.location.href).toContain('texttag=&text=');
    });
  });

  // ===========================================================================
  // Text Filter Select Tests
  // ===========================================================================

  describe('Text filter select', () => {
    it('navigates with text parameter on change', () => {
      document.body.innerHTML = `
        <form name="form1">
          <select data-action="filter-text">
            <option value="">All</option>
            <option value="5">My Text</option>
          </select>
        </form>
      `;

      initWordListFilter();

      const textSelect = document.querySelector('[data-action="filter-text"]') as HTMLSelectElement;
      textSelect.value = '5';
      textSelect.dispatchEvent(new Event('change'));

      expect(window.location.href).toContain('text=5');
      expect(window.location.href).toContain('page=1');
    });
  });

  // ===========================================================================
  // Status Filter Select Tests
  // ===========================================================================

  describe('Status filter select', () => {
    it('navigates with status parameter on change', () => {
      document.body.innerHTML = `
        <form name="form1">
          <select data-action="filter-status">
            <option value="">All</option>
            <option value="1">Learning</option>
            <option value="99">Well Known</option>
          </select>
        </form>
      `;

      initWordListFilter();

      const statusSelect = document.querySelector('[data-action="filter-status"]') as HTMLSelectElement;
      statusSelect.value = '99';
      statusSelect.dispatchEvent(new Event('change'));

      expect(window.location.href).toContain('status=99');
      expect(window.location.href).toContain('page=1');
    });
  });

  // ===========================================================================
  // Query Mode Select Tests
  // ===========================================================================

  describe('Query mode select', () => {
    it('navigates with query and query_mode on change', () => {
      document.body.innerHTML = `
        <form name="form1">
          <input type="text" name="query" value="test word" />
          <select data-action="query-mode">
            <option value="term,rom,transl">All fields</option>
            <option value="term">Term only</option>
          </select>
        </form>
      `;

      initWordListFilter();

      const queryModeSelect = document.querySelector('[data-action="query-mode"]') as HTMLSelectElement;
      queryModeSelect.value = 'term';
      queryModeSelect.dispatchEvent(new Event('change'));

      expect(window.location.href).toContain('query=test+word');
      expect(window.location.href).toContain('query_mode=term');
    });

    it('uses empty string when query input is missing', () => {
      document.body.innerHTML = `
        <form name="form1">
          <select data-action="query-mode">
            <option value="term">Term only</option>
          </select>
        </form>
      `;

      initWordListFilter();

      const queryModeSelect = document.querySelector('[data-action="query-mode"]') as HTMLSelectElement;
      queryModeSelect.dispatchEvent(new Event('change'));

      expect(window.location.href).toContain('query=');
    });
  });

  // ===========================================================================
  // Query Filter Button Tests
  // ===========================================================================

  describe('Query filter button', () => {
    it('navigates with encoded query value on click', () => {
      document.body.innerHTML = `
        <form name="form1">
          <input type="text" name="query" value="hello world" />
          <select name="query_mode">
            <option value="term,rom,transl">All</option>
          </select>
          <button data-action="filter-query">Query</button>
        </form>
      `;

      initWordListFilter();

      const queryButton = document.querySelector('[data-action="filter-query"]') as HTMLButtonElement;
      queryButton.click();

      expect(window.location.href).toContain('query=hello%20world');
      expect(window.location.href).toContain('query_mode=term,rom,transl');
    });

    it('uses default query_mode when select is missing', () => {
      document.body.innerHTML = `
        <form name="form1">
          <input type="text" name="query" value="test" />
          <button data-action="filter-query">Query</button>
        </form>
      `;

      initWordListFilter();

      const queryButton = document.querySelector('[data-action="filter-query"]') as HTMLButtonElement;
      queryButton.click();

      expect(window.location.href).toContain('query_mode=term,rom,transl');
    });

    it('prevents default action on click', () => {
      document.body.innerHTML = `
        <form name="form1">
          <input type="text" name="query" value="" />
          <button data-action="filter-query">Query</button>
        </form>
      `;

      initWordListFilter();

      const queryButton = document.querySelector('[data-action="filter-query"]') as HTMLButtonElement;
      const clickEvent = new MouseEvent('click', { cancelable: true });
      queryButton.dispatchEvent(clickEvent);

      expect(clickEvent.defaultPrevented).toBe(true);
    });

    it('handles special characters in query', () => {
      document.body.innerHTML = `
        <form name="form1">
          <input type="text" name="query" value="café & résumé" />
          <select name="query_mode"><option value="term">Term</option></select>
          <button data-action="filter-query">Query</button>
        </form>
      `;

      initWordListFilter();

      const queryButton = document.querySelector('[data-action="filter-query"]') as HTMLButtonElement;
      queryButton.click();

      expect(window.location.href).toContain('caf%C3%A9');
      expect(window.location.href).toContain('r%C3%A9sum%C3%A9');
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

      initWordListFilter();

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

      initWordListFilter();

      const clearButton = document.querySelector('[data-action="clear-query"]') as HTMLButtonElement;
      const clickEvent = new MouseEvent('click', { cancelable: true });
      clearButton.dispatchEvent(clickEvent);

      expect(clickEvent.defaultPrevented).toBe(true);
    });
  });

  // ===========================================================================
  // Tag Filter Tests
  // ===========================================================================

  describe('Tag #1 filter select', () => {
    it('navigates with tag1 parameter on change', () => {
      document.body.innerHTML = `
        <form name="form1">
          <select data-action="filter-tag1">
            <option value="">All</option>
            <option value="verb">Verb</option>
          </select>
        </form>
      `;

      initWordListFilter();

      const tag1Select = document.querySelector('[data-action="filter-tag1"]') as HTMLSelectElement;
      tag1Select.value = 'verb';
      tag1Select.dispatchEvent(new Event('change'));

      expect(window.location.href).toContain('tag1=verb');
    });
  });

  describe('Tag logic select', () => {
    it('navigates with tag12 parameter on change', () => {
      document.body.innerHTML = `
        <form name="form1">
          <select data-action="filter-tag12">
            <option value="0">AND</option>
            <option value="1">OR</option>
          </select>
        </form>
      `;

      initWordListFilter();

      const tag12Select = document.querySelector('[data-action="filter-tag12"]') as HTMLSelectElement;
      tag12Select.value = '1';
      tag12Select.dispatchEvent(new Event('change'));

      expect(window.location.href).toContain('tag12=1');
    });
  });

  describe('Tag #2 filter select', () => {
    it('navigates with tag2 parameter on change', () => {
      document.body.innerHTML = `
        <form name="form1">
          <select data-action="filter-tag2">
            <option value="">All</option>
            <option value="noun">Noun</option>
          </select>
        </form>
      `;

      initWordListFilter();

      const tag2Select = document.querySelector('[data-action="filter-tag2"]') as HTMLSelectElement;
      tag2Select.value = 'noun';
      tag2Select.dispatchEvent(new Event('change'));

      expect(window.location.href).toContain('tag2=noun');
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
            <option value="1">Term A-Z</option>
            <option value="2">Term Z-A</option>
            <option value="3">Status</option>
          </select>
        </form>
      `;

      initWordListFilter();

      const sortSelect = document.querySelector('[data-action="sort"]') as HTMLSelectElement;
      sortSelect.value = '3';
      sortSelect.dispatchEvent(new Event('change'));

      expect(window.location.href).toContain('sort=3');
      expect(window.location.href).toContain('page=1');
    });
  });

  // ===========================================================================
  // Window Export Tests
  // ===========================================================================

  describe('Window export', () => {
    it('exports initWordListFilter to window', () => {
      expect(typeof window.initWordListFilter).toBe('function');
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
      `;

      expect(() => initWordListFilter()).not.toThrow();
    });

    it('handles multiple calls to initWordListFilter', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="reset-all">Reset</button>
        </form>
      `;

      initWordListFilter();
      initWordListFilter();

      const resetButton = document.querySelector('[data-action="reset-all"]') as HTMLButtonElement;
      resetButton.click();

      // Should only call resetAll twice (once per init)
      expect(resetAll).toHaveBeenCalledTimes(2);
    });

    it('handles empty query input value', () => {
      document.body.innerHTML = `
        <form name="form1">
          <input type="text" name="query" value="" />
          <select name="query_mode"><option value="term">Term</option></select>
          <button data-action="filter-query">Query</button>
        </form>
      `;

      initWordListFilter();

      const queryButton = document.querySelector('[data-action="filter-query"]') as HTMLButtonElement;
      queryButton.click();

      expect(window.location.href).toContain('query=');
    });
  });
});
