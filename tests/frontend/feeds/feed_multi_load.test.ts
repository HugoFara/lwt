/**
 * Tests for feed_multi_load.ts - Feed multi-load page functionality
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock the language_settings module
vi.mock('../../../src/frontend/js/core/language_settings', () => ({
  setLang: vi.fn()
}));

// Mock ui_utilities to prevent jQuery UI initialization issues
vi.mock('../../../src/frontend/js/core/ui_utilities', () => ({
  markClick: vi.fn()
}));

import {
  collectCheckedValues,
  initFeedMultiLoad
} from '../../../src/frontend/js/feeds/feed_multi_load';
import { setLang } from '../../../src/frontend/js/core/language_settings';

describe('feed_multi_load.ts', () => {
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
  // collectCheckedValues Tests
  // ===========================================================================

  describe('collectCheckedValues', () => {
    it('collects checked checkbox values into hidden field', () => {
      document.body.innerHTML = `
        <form name="form1">
          <input type="checkbox" value="1" checked />
          <input type="checkbox" value="2" />
          <input type="checkbox" value="3" checked />
          <input type="checkbox" value="4" checked />
        </form>
        <input type="hidden" id="map" value="" />
      `;

      collectCheckedValues('form1', 'map');

      const hiddenField = document.getElementById('map') as HTMLInputElement;
      expect(hiddenField.value).toBe('1, 3, 4');
    });

    it('returns empty string when no checkboxes are checked', () => {
      document.body.innerHTML = `
        <form name="form1">
          <input type="checkbox" value="1" />
          <input type="checkbox" value="2" />
        </form>
        <input type="hidden" id="map" value="previous value" />
      `;

      collectCheckedValues('form1', 'map');

      const hiddenField = document.getElementById('map') as HTMLInputElement;
      expect(hiddenField.value).toBe('');
    });

    it('does nothing when form does not exist', () => {
      document.body.innerHTML = `
        <input type="hidden" id="map" value="original" />
      `;

      collectCheckedValues('nonexistent', 'map');

      const hiddenField = document.getElementById('map') as HTMLInputElement;
      expect(hiddenField.value).toBe('original');
    });

    it('does nothing when hidden field does not exist', () => {
      document.body.innerHTML = `
        <form name="form1">
          <input type="checkbox" value="1" checked />
        </form>
      `;

      // Should not throw
      expect(() => collectCheckedValues('form1', 'nonexistent')).not.toThrow();
    });

    it('filters out empty checkbox values', () => {
      document.body.innerHTML = `
        <form name="form1">
          <input type="checkbox" value="" checked />
          <input type="checkbox" value="1" checked />
          <input type="checkbox" value="" checked />
          <input type="checkbox" value="2" checked />
        </form>
        <input type="hidden" id="map" value="" />
      `;

      collectCheckedValues('form1', 'map');

      const hiddenField = document.getElementById('map') as HTMLInputElement;
      expect(hiddenField.value).toBe('1, 2');
    });

    it('handles form with no checkboxes', () => {
      document.body.innerHTML = `
        <form name="form1">
          <input type="text" value="text" />
        </form>
        <input type="hidden" id="map" value="" />
      `;

      collectCheckedValues('form1', 'map');

      const hiddenField = document.getElementById('map') as HTMLInputElement;
      expect(hiddenField.value).toBe('');
    });
  });

  // ===========================================================================
  // initFeedMultiLoad Tests
  // ===========================================================================

  describe('initFeedMultiLoad', () => {
    it('does nothing when form1 does not exist', () => {
      expect(() => initFeedMultiLoad()).not.toThrow();
    });

    it('sets up language filter change handler', () => {
      document.body.innerHTML = `
        <form name="form1">
          <select data-action="filter-language" data-url="/feeds/edit?multi_load_feed=1&page=1">
            <option value="1">English</option>
          </select>
        </form>
      `;

      initFeedMultiLoad();

      const select = document.querySelector<HTMLSelectElement>('[data-action="filter-language"]')!;
      select.dispatchEvent(new Event('change'));

      expect(setLang).toHaveBeenCalledWith(select, '/feeds/edit?multi_load_feed=1&page=1');
    });

    it('uses default URL for language filter if not specified', () => {
      document.body.innerHTML = `
        <form name="form1">
          <select data-action="filter-language">
            <option value="1">English</option>
          </select>
        </form>
      `;

      initFeedMultiLoad();

      const select = document.querySelector<HTMLSelectElement>('[data-action="filter-language"]')!;
      select.dispatchEvent(new Event('change'));

      expect(setLang).toHaveBeenCalledWith(select, '/feeds/edit?multi_load_feed=1&page=1');
    });

    it('sets up mark action button to collect checked values', () => {
      document.body.innerHTML = `
        <form name="form1">
          <input type="checkbox" value="feed_1" checked />
          <input type="checkbox" value="feed_2" checked />
          <input type="hidden" id="map" value="" />
          <button id="markaction">Load Selected</button>
        </form>
      `;

      initFeedMultiLoad();

      const button = document.getElementById('markaction') as HTMLButtonElement;
      button.click();

      const hiddenField = document.getElementById('map') as HTMLInputElement;
      expect(hiddenField.value).toBe('feed_1, feed_2');
    });

    it('sets up cancel button handler', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="cancel" data-url="/feeds?selected_feed=0">Cancel</button>
        </form>
      `;

      initFeedMultiLoad();

      const button = document.querySelector<HTMLButtonElement>('[data-action="cancel"]')!;
      button.click();

      expect(window.location.href).toBe('/feeds?selected_feed=0');
    });

    it('uses default URL for cancel button if not specified', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="cancel">Cancel</button>
        </form>
      `;

      initFeedMultiLoad();

      const button = document.querySelector<HTMLButtonElement>('[data-action="cancel"]')!;
      button.click();

      expect(window.location.href).toBe('/feeds?selected_feed=0');
    });

    it('prevents default on cancel button click', () => {
      document.body.innerHTML = `
        <form name="form1">
          <button data-action="cancel">Cancel</button>
        </form>
      `;

      initFeedMultiLoad();

      const button = document.querySelector<HTMLButtonElement>('[data-action="cancel"]')!;
      const clickEvent = new MouseEvent('click', { cancelable: true, bubbles: true });
      const preventDefaultSpy = vi.spyOn(clickEvent, 'preventDefault');

      button.dispatchEvent(clickEvent);

      expect(preventDefaultSpy).toHaveBeenCalled();
    });

    it('handles missing markaction button', () => {
      document.body.innerHTML = `
        <form name="form1">
          <input type="checkbox" value="1" checked />
        </form>
      `;

      expect(() => initFeedMultiLoad()).not.toThrow();
    });

    it('handles missing map hidden field when markaction clicked', () => {
      document.body.innerHTML = `
        <form name="form1">
          <input type="checkbox" value="1" checked />
          <button id="markaction">Load</button>
        </form>
      `;

      initFeedMultiLoad();

      const button = document.getElementById('markaction') as HTMLButtonElement;
      expect(() => button.click()).not.toThrow();
    });
  });
});
