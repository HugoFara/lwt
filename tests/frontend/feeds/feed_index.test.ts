/**
 * Tests for feed_index.ts - Feed index/management page functionality
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { initFeedIndex } from '../../../src/frontend/js/feeds/feed_index';

// Mock the dependencies
vi.mock('../../../src/frontend/js/core/language_settings', () => ({
  setLang: vi.fn(),
  resetAll: vi.fn()
}));

vi.mock('../../../src/frontend/js/forms/bulk_actions', () => ({
  selectToggle: vi.fn(),
  multiActionGo: vi.fn()
}));

import { setLang, resetAll } from '../../../src/frontend/js/core/language_settings';
import { selectToggle, multiActionGo } from '../../../src/frontend/js/forms/bulk_actions';

describe('feed_index.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    // Mock location.href
    Object.defineProperty(window, 'location', {
      value: { href: '' },
      writable: true
    });
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // initFeedIndex Tests
  // ===========================================================================

  describe('initFeedIndex', () => {
    it('does nothing when form1 does not exist', () => {
      expect(() => initFeedIndex()).not.toThrow();
    });

    it('prevents form submission and triggers query button click', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="filter-query">Filter</button>
        </form>
      `;

      initFeedIndex();

      const form = document.forms.namedItem('form1')!;
      const filterButton = document.querySelector<HTMLButtonElement>('[data-action="filter-query"]')!;
      const buttonClickSpy = vi.spyOn(filterButton, 'click');

      const event = new Event('submit', { cancelable: true });
      form.dispatchEvent(event);

      expect(event.defaultPrevented).toBe(true);
      expect(buttonClickSpy).toHaveBeenCalled();
    });

    it('sets up reset all button handler', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="reset-all" data-url="/feeds/edit">Reset</button>
        </form>
      `;

      initFeedIndex();

      const button = document.querySelector<HTMLButtonElement>('[data-action="reset-all"]')!;
      button.click();

      expect(resetAll).toHaveBeenCalledWith('/feeds/edit');
    });

    it('uses default URL for reset all if not specified', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="reset-all">Reset</button>
        </form>
      `;

      initFeedIndex();

      const button = document.querySelector<HTMLButtonElement>('[data-action="reset-all"]')!;
      button.click();

      expect(resetAll).toHaveBeenCalledWith('/feeds/edit');
    });

    it('sets up language filter change handler', () => {
      document.body.innerHTML = `
        <form name="form1">
          <select data-action="filter-language" data-url="/feeds/edit?manage_feeds=1">
            <option value="1">English</option>
          </select>
        </form>
      `;

      initFeedIndex();

      const select = document.querySelector<HTMLSelectElement>('[data-action="filter-language"]')!;
      select.dispatchEvent(new Event('change'));

      expect(setLang).toHaveBeenCalledWith(select, '/feeds/edit?manage_feeds=1');
    });

    it('uses default URL for language filter if not specified', () => {
      document.body.innerHTML = `
        <form name="form1">
          <select data-action="filter-language">
            <option value="1">English</option>
          </select>
        </form>
      `;

      initFeedIndex();

      const select = document.querySelector<HTMLSelectElement>('[data-action="filter-language"]')!;
      select.dispatchEvent(new Event('change'));

      expect(setLang).toHaveBeenCalledWith(select, '/feeds/edit?manage_feeds=1');
    });

    it('sets up query filter button handler', () => {
      document.body.innerHTML = `
        <form name="form1">
          <input type="text" name="query" value="test search" />
          <button data-action="filter-query">Filter</button>
        </form>
      `;

      initFeedIndex();

      const button = document.querySelector<HTMLButtonElement>('[data-action="filter-query"]')!;
      button.click();

      expect(window.location.href).toBe('/feeds/edit?page=1&query=test%20search');
    });

    it('sets up query clear button handler', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="clear-query">Clear</button>
        </form>
      `;

      initFeedIndex();

      const button = document.querySelector<HTMLButtonElement>('[data-action="clear-query"]')!;
      button.click();

      expect(window.location.href).toBe('/feeds/edit?page=1&query=');
    });

    it('sets up mark all button handler', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="mark-all">Mark All</button>
        </form>
        <form name="form2"></form>
      `;

      initFeedIndex();

      const button = document.querySelector<HTMLButtonElement>('[data-action="mark-all"]')!;
      button.click();

      expect(selectToggle).toHaveBeenCalledWith(true, 'form2');
    });

    it('sets up mark none button handler', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="mark-none">Mark None</button>
        </form>
        <form name="form2"></form>
      `;

      initFeedIndex();

      const button = document.querySelector<HTMLButtonElement>('[data-action="mark-none"]')!;
      button.click();

      expect(selectToggle).toHaveBeenCalledWith(false, 'form2');
    });

    it('sets up mark action select handler', () => {
      document.body.innerHTML = `
        <form name="form1">
          <select data-action="mark-action">
            <option value="">Select action</option>
            <option value="del">Delete</option>
          </select>
          <input type="hidden" id="map" value="" />
        </form>
        <input type="checkbox" class="markcheck" value="1" checked />
        <input type="checkbox" class="markcheck" value="2" checked />
      `;

      initFeedIndex();

      const select = document.querySelector<HTMLSelectElement>('[data-action="mark-action"]')!;
      select.value = 'del';
      select.dispatchEvent(new Event('change'));

      // Hidden field should be populated with checked values
      const hiddenField = document.getElementById('map') as HTMLInputElement;
      expect(hiddenField.value).toContain('1');
      expect(hiddenField.value).toContain('2');

      // multiActionGo should be called
      expect(multiActionGo).toHaveBeenCalled();
    });

    it('sets up sort select handler', () => {
      document.body.innerHTML = `
        <form name="form1">
          <select data-action="sort">
            <option value="1">Newest</option>
            <option value="2">Oldest</option>
          </select>
        </form>
      `;

      initFeedIndex();

      const select = document.querySelector<HTMLSelectElement>('[data-action="sort"]')!;
      select.value = '2';
      select.dispatchEvent(new Event('change'));

      expect(window.location.href).toBe('/feeds/edit?page=1&sort=2');
    });

    it('sets up delete feed handler with confirmation', () => {
      document.body.innerHTML = `
        <form name="form1"></form>
        <form name="form2">
          <span data-action="delete-feed" data-feed-id="42">Delete</span>
        </form>
      `;

      // Mock confirm
      const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(true);

      initFeedIndex();

      const deleteSpan = document.querySelector<HTMLElement>('[data-action="delete-feed"]')!;
      deleteSpan.click();

      expect(confirmSpy).toHaveBeenCalledWith('Are you sure?');
      expect(window.location.href).toBe('/feeds/edit?markaction=del&selected_feed=42');
    });

    it('does not delete feed if confirmation is cancelled', () => {
      document.body.innerHTML = `
        <form name="form1"></form>
        <form name="form2">
          <span data-action="delete-feed" data-feed-id="42">Delete</span>
        </form>
      `;

      vi.spyOn(window, 'confirm').mockReturnValue(false);

      initFeedIndex();

      const deleteSpan = document.querySelector<HTMLElement>('[data-action="delete-feed"]')!;
      deleteSpan.click();

      expect(window.location.href).toBe('');
    });

    it('handles empty query input', () => {
      document.body.innerHTML = `
        <form name="form1">
          <input type="text" name="query" value="" />
          <button data-action="filter-query">Filter</button>
        </form>
      `;

      initFeedIndex();

      const button = document.querySelector<HTMLButtonElement>('[data-action="filter-query"]')!;
      button.click();

      expect(window.location.href).toBe('/feeds/edit?page=1&query=');
    });

    it('handles missing hidden map field', () => {
      document.body.innerHTML = `
        <form name="form1">
          <select data-action="mark-action">
            <option value="">Select action</option>
            <option value="del">Delete</option>
          </select>
        </form>
      `;

      initFeedIndex();

      const select = document.querySelector<HTMLSelectElement>('[data-action="mark-action"]')!;
      select.value = 'del';

      expect(() => {
        select.dispatchEvent(new Event('change'));
      }).not.toThrow();
    });

    it('ignores delete clicks outside form2', () => {
      document.body.innerHTML = `
        <form name="form1"></form>
        <form name="form2"></form>
        <span data-action="delete-feed" data-feed-id="99">Delete Outside</span>
      `;

      initFeedIndex();

      const deleteSpan = document.querySelector<HTMLElement>('[data-action="delete-feed"]')!;
      deleteSpan.click();

      // Should not redirect since click is outside form2
      expect(window.location.href).toBe('');
    });
  });
});
