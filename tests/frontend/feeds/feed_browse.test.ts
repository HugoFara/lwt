/**
 * Tests for feed_browse.ts - Feed browse page interactions
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock the language_settings module
vi.mock('../../../src/frontend/js/core/language_settings', () => ({
  setLang: vi.fn(),
  resetAll: vi.fn()
}));

// Mock ui_utilities module
vi.mock('../../../src/frontend/js/core/ui_utilities', () => ({
  markClick: vi.fn()
}));

import { initFeedBrowse, initNotFoundImages } from '../../../src/frontend/js/feeds/feed_browse';
import { setLang, resetAll } from '../../../src/frontend/js/core/language_settings';
import { markClick } from '../../../src/frontend/js/core/ui_utilities';

describe('feed_browse.ts', () => {
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
  // initFeedBrowse Tests
  // ===========================================================================

  describe('initFeedBrowse', () => {
    it('does nothing when form1 does not exist', () => {
      expect(() => initFeedBrowse()).not.toThrow();
    });

    it('sets up language filter change handler', () => {
      document.body.innerHTML = `
        <form name="form1">
          <select data-action="filter-language" data-url="/feeds?page=1&selected_feed=0">
            <option value="1">English</option>
            <option value="2">French</option>
          </select>
        </form>
      `;

      initFeedBrowse();

      const select = document.querySelector<HTMLSelectElement>('[data-action="filter-language"]')!;
      select.value = '2';
      select.dispatchEvent(new Event('change'));

      expect(setLang).toHaveBeenCalledWith(select, '/feeds?page=1&selected_feed=0');
    });

    it('uses default URL for language filter if not specified', () => {
      document.body.innerHTML = `
        <form name="form1">
          <select data-action="filter-language">
            <option value="1">English</option>
          </select>
        </form>
      `;

      initFeedBrowse();

      const select = document.querySelector<HTMLSelectElement>('[data-action="filter-language"]')!;
      select.dispatchEvent(new Event('change'));

      expect(setLang).toHaveBeenCalledWith(select, '/feeds?page=1&selected_feed=0');
    });

    it('sets up query mode change handler', () => {
      document.body.innerHTML = `
        <form name="form1">
          <input type="text" name="query" value="test query" />
          <select data-action="query-mode">
            <option value="title">Title</option>
            <option value="content">Content</option>
          </select>
        </form>
      `;

      initFeedBrowse();

      const select = document.querySelector<HTMLSelectElement>('[data-action="query-mode"]')!;
      select.value = 'content';
      select.dispatchEvent(new Event('change'));

      expect(window.location.href).toBe('/feeds?page=1&query=test%20query&query_mode=content');
    });

    it('sets up query filter button handler', () => {
      document.body.innerHTML = `
        <form name="form1">
          <input type="text" name="query" value="search term" />
          <button data-action="filter-query">Filter</button>
        </form>
      `;

      initFeedBrowse();

      const button = document.querySelector<HTMLButtonElement>('[data-action="filter-query"]')!;
      button.click();

      expect(window.location.href).toBe('/feeds?page=1&query=search%20term');
    });

    it('sets up query clear button handler', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="clear-query">Clear</button>
        </form>
      `;

      initFeedBrowse();

      const button = document.querySelector<HTMLButtonElement>('[data-action="clear-query"]')!;
      button.click();

      expect(window.location.href).toBe('/feeds?page=1&query=');
    });

    it('sets up reset all button handler', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="reset-all" data-url="/feeds">Reset</button>
        </form>
      `;

      initFeedBrowse();

      const button = document.querySelector<HTMLButtonElement>('[data-action="reset-all"]')!;
      button.click();

      expect(resetAll).toHaveBeenCalledWith('/feeds');
    });

    it('uses default URL for reset all if not specified', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="reset-all">Reset</button>
        </form>
      `;

      initFeedBrowse();

      const button = document.querySelector<HTMLButtonElement>('[data-action="reset-all"]')!;
      button.click();

      expect(resetAll).toHaveBeenCalledWith('/feeds');
    });

    it('sets up feed select change handler', () => {
      document.body.innerHTML = `
        <form name="form1">
          <select data-action="filter-feed">
            <option value="0">All</option>
            <option value="5">Feed 5</option>
          </select>
        </form>
      `;

      initFeedBrowse();

      const select = document.querySelector<HTMLSelectElement>('[data-action="filter-feed"]')!;
      select.value = '5';
      select.dispatchEvent(new Event('change'));

      expect(window.location.href).toBe('/feeds?page=1&selected_feed=5');
    });

    it('sets up sort select change handler', () => {
      document.body.innerHTML = `
        <form name="form1">
          <select data-action="sort">
            <option value="1">Newest</option>
            <option value="2">Oldest</option>
          </select>
        </form>
      `;

      initFeedBrowse();

      const select = document.querySelector<HTMLSelectElement>('[data-action="sort"]')!;
      select.value = '2';
      select.dispatchEvent(new Event('change'));

      expect(window.location.href).toBe('/feeds?page=1&sort=2');
    });

    it('handles empty query input', () => {
      document.body.innerHTML = `
        <form name="form1">
          <input type="text" name="query" value="" />
          <button data-action="filter-query">Filter</button>
        </form>
      `;

      initFeedBrowse();

      const button = document.querySelector<HTMLButtonElement>('[data-action="filter-query"]')!;
      button.click();

      expect(window.location.href).toBe('/feeds?page=1&query=');
    });

    it('handles missing query input for query mode', () => {
      document.body.innerHTML = `
        <form name="form1">
          <select data-action="query-mode">
            <option value="title">Title</option>
          </select>
        </form>
      `;

      initFeedBrowse();

      const select = document.querySelector<HTMLSelectElement>('[data-action="query-mode"]')!;
      select.dispatchEvent(new Event('change'));

      expect(window.location.href).toBe('/feeds?page=1&query=&query_mode=title');
    });
  });

  // ===========================================================================
  // initNotFoundImages Tests
  // ===========================================================================

  describe('initNotFoundImages', () => {
    it('replaces not_found image with checkbox on click', () => {
      document.body.innerHTML = `
        <img class="not_found" name="item_123" />
      `;

      initNotFoundImages();

      const img = document.querySelector('img.not_found')!;
      img.click();

      // Image should be replaced with checkbox
      expect(document.querySelector('img.not_found')).toBeNull();
      expect(document.querySelector('input[type="checkbox"]')).not.toBeNull();
    });

    it('creates checkbox with correct attributes', () => {
      document.body.innerHTML = `
        <img class="not_found" name="item_456" />
      `;

      initNotFoundImages();
      document.querySelector('img.not_found')!.click();

      const checkbox = document.querySelector('input[type="checkbox"]') as HTMLInputElement;
      expect(checkbox).not.toBeNull();
      expect(checkbox.className).toBe('markcheck');
      expect(checkbox.id).toBe('item_456');
      expect(checkbox.value).toBe('item_456');
      expect(checkbox.name).toBe('marked_items[]');
    });

    it('creates label for checkbox', () => {
      document.body.innerHTML = `
        <img class="not_found" name="item_789" />
      `;

      initNotFoundImages();
      document.querySelector('img.not_found')!.click();

      const label = document.querySelector('label.wrap_checkbox');
      expect(label).not.toBeNull();
      expect(label!.getAttribute('for')).toBe('item_789');
    });

    it('calls markClick on checkbox change', () => {
      document.body.innerHTML = `
        <img class="not_found" name="item_111" />
      `;

      initNotFoundImages();
      document.querySelector('img.not_found')!.click();

      const checkbox = document.querySelector('input[type="checkbox"]') as HTMLInputElement;
      checkbox.dispatchEvent(new Event('change'));

      expect(markClick).toHaveBeenCalled();
    });

    it('handles missing markClick gracefully', () => {
      document.body.innerHTML = `
        <img class="not_found" name="item_222" />
      `;

      delete (window as any).markClick;

      initNotFoundImages();
      document.querySelector('img.not_found')!.click();

      const checkbox = document.querySelector('input[type="checkbox"]') as HTMLInputElement;
      expect(() => {
        checkbox.dispatchEvent(new Event('change'));
      }).not.toThrow();
    });

    it('updates tabindex on interactive elements', () => {
      document.body.innerHTML = `
        <input type="text" />
        <img class="not_found" name="item_333" />
        <button>Click</button>
      `;

      initNotFoundImages();
      document.querySelector('img.not_found')!.click();

      // Check that tabindex is set on elements
      const elements = document.querySelectorAll('[tabindex]');
      expect(elements.length).toBeGreaterThan(0);
    });

    it('ignores clicks on non-not_found images', () => {
      document.body.innerHTML = `
        <img class="regular_image" name="item_444" />
      `;

      initNotFoundImages();
      document.querySelector('img.regular_image')!.click();

      // Image should still exist
      expect(document.querySelector('img.regular_image')).not.toBeNull();
      expect(document.querySelector('input[type="checkbox"]')).toBeNull();
    });

    it('handles image with empty name attribute', () => {
      document.body.innerHTML = `
        <img class="not_found" name="" />
      `;

      initNotFoundImages();

      expect(() => {
        document.querySelector('img.not_found')!.click();
      }).not.toThrow();

      const checkbox = document.querySelector('input[type="checkbox"]') as HTMLInputElement;
      expect(checkbox.id).toBe('');
    });
  });
});
