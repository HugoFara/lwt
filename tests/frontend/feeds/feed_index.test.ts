/**
 * Tests for feed_index_component.ts - Feed index Alpine component
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock the dependencies before importing the module
vi.mock('../../../src/frontend/js/modules/language/stores/language_settings', () => ({
  setLang: vi.fn(),
  resetAll: vi.fn()
}));

vi.mock('../../../src/frontend/js/shared/forms/bulk_actions', () => ({
  selectToggle: vi.fn(),
  multiActionGo: vi.fn()
}));

import {
  feedIndexData,
  type FeedIndexConfig
} from '../../../src/frontend/js/modules/feed/components/feed_index_component';
import { setLang, resetAll } from '../../../src/frontend/js/modules/language/stores/language_settings';
import { selectToggle, multiActionGo } from '../../../src/frontend/js/shared/forms/bulk_actions';

describe('feed_index_component.ts', () => {
  let originalLocation: Location;

  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    // Save original location
    originalLocation = window.location;
    // Mock location.href
    Object.defineProperty(window, 'location', {
      value: { href: '' },
      writable: true,
      configurable: true
    });
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
    // Restore original location
    Object.defineProperty(window, 'location', {
      value: originalLocation,
      writable: true,
      configurable: true
    });
  });

  // ===========================================================================
  // feedIndexData Factory Function Tests
  // ===========================================================================

  describe('feedIndexData', () => {
    it('creates component with default values', () => {
      const component = feedIndexData();

      expect(component.resetUrl).toBe('/feeds/edit');
      expect(component.filterUrl).toBe('/feeds/edit?manage_feeds=1');
      expect(component.pageBaseUrl).toBe('/feeds/edit');
      expect(component.query).toBe('');
    });

    it('creates component with provided config values', () => {
      const config: FeedIndexConfig = {
        resetUrl: '/custom/reset',
        filterUrl: '/custom/filter',
        pageBaseUrl: '/custom/base',
        currentQuery: 'test query'
      };

      const component = feedIndexData(config);

      expect(component.resetUrl).toBe('/custom/reset');
      expect(component.filterUrl).toBe('/custom/filter');
      expect(component.pageBaseUrl).toBe('/custom/base');
      expect(component.query).toBe('test query');
    });

    it('allows partial config', () => {
      const config: FeedIndexConfig = {
        currentQuery: 'search term'
      };

      const component = feedIndexData(config);

      expect(component.resetUrl).toBe('/feeds/edit'); // Default
      expect(component.query).toBe('search term');
    });
  });

  // ===========================================================================
  // init() Method Tests
  // ===========================================================================

  describe('init()', () => {
    it('reads config from JSON script tag', () => {
      document.body.innerHTML = `
        <script type="application/json" id="feed-index-config">
          {"resetUrl": "/json/reset", "currentQuery": "json query"}
        </script>
      `;

      const component = feedIndexData();
      component.init();

      expect(component.resetUrl).toBe('/json/reset');
      expect(component.query).toBe('json query');
    });

    it('keeps defaults if no JSON config element exists', () => {
      const component = feedIndexData();
      component.init();

      expect(component.resetUrl).toBe('/feeds/edit');
      expect(component.query).toBe('');
    });

    it('handles invalid JSON gracefully', () => {
      document.body.innerHTML = `
        <script type="application/json" id="feed-index-config">
          {invalid json}
        </script>
      `;

      const component = feedIndexData({ currentQuery: 'original' });

      expect(() => component.init()).not.toThrow();
      expect(component.query).toBe('original');
    });
  });

  // ===========================================================================
  // handleReset() Tests
  // ===========================================================================

  describe('handleReset()', () => {
    it('calls resetAll with configured URL', () => {
      const component = feedIndexData({ resetUrl: '/custom/reset' });

      component.handleReset();

      expect(resetAll).toHaveBeenCalledWith('/custom/reset');
    });

    it('uses default reset URL', () => {
      const component = feedIndexData();

      component.handleReset();

      expect(resetAll).toHaveBeenCalledWith('/feeds/edit');
    });
  });

  // ===========================================================================
  // handleLanguageFilter() Tests
  // ===========================================================================

  describe('handleLanguageFilter()', () => {
    it('calls setLang with select element and filter URL', () => {
      const component = feedIndexData({ filterUrl: '/custom/filter' });
      const select = document.createElement('select');
      const event = { target: select } as unknown as Event;

      component.handleLanguageFilter(event);

      expect(setLang).toHaveBeenCalledWith(select, '/custom/filter');
    });

    it('uses default filter URL', () => {
      const component = feedIndexData();
      const select = document.createElement('select');
      const event = { target: select } as unknown as Event;

      component.handleLanguageFilter(event);

      expect(setLang).toHaveBeenCalledWith(select, '/feeds/edit?manage_feeds=1');
    });
  });

  // ===========================================================================
  // handleQueryFilter() Tests
  // ===========================================================================

  describe('handleQueryFilter()', () => {
    it('navigates to URL with encoded query', () => {
      const component = feedIndexData({ pageBaseUrl: '/feeds/edit' });
      component.query = 'test search';

      component.handleQueryFilter();

      expect(window.location.href).toBe('/feeds/edit?page=1&query=test%20search');
    });

    it('handles empty query', () => {
      const component = feedIndexData();
      component.query = '';

      component.handleQueryFilter();

      expect(window.location.href).toBe('/feeds/edit?page=1&query=');
    });

    it('handles special characters in query', () => {
      const component = feedIndexData();
      component.query = 'test&query=value';

      component.handleQueryFilter();

      expect(window.location.href).toBe('/feeds/edit?page=1&query=test%26query%3Dvalue');
    });
  });

  // ===========================================================================
  // handleClearQuery() Tests
  // ===========================================================================

  describe('handleClearQuery()', () => {
    it('clears query and navigates', () => {
      const component = feedIndexData();
      component.query = 'existing query';

      component.handleClearQuery();

      expect(component.query).toBe('');
      expect(window.location.href).toBe('/feeds/edit?page=1&query=');
    });
  });

  // ===========================================================================
  // markAll() and markNone() Tests
  // ===========================================================================

  describe('markAll()', () => {
    it('calls selectToggle with true and form2', () => {
      const component = feedIndexData();

      component.markAll();

      expect(selectToggle).toHaveBeenCalledWith(true, 'form2');
    });
  });

  describe('markNone()', () => {
    it('calls selectToggle with false and form2', () => {
      const component = feedIndexData();

      component.markNone();

      expect(selectToggle).toHaveBeenCalledWith(false, 'form2');
    });
  });

  // ===========================================================================
  // handleMarkAction() Tests
  // ===========================================================================

  describe('handleMarkAction()', () => {
    it('collects checked checkbox values into hidden field', () => {
      document.body.innerHTML = `
        <div id="container">
          <form name="form1"></form>
          <input type="hidden" id="map" value="" />
          <input type="checkbox" class="markcheck" value="1" checked />
          <input type="checkbox" class="markcheck" value="2" checked />
          <input type="checkbox" class="markcheck" value="3" />
        </div>
      `;

      const component = feedIndexData();
      const container = document.getElementById('container')!;
      (component as unknown as { $el: HTMLElement }).$el = container;

      const select = document.createElement('select');
      select.value = 'del';
      const event = { target: select } as unknown as Event;

      component.handleMarkAction.call(component as any, event);

      const hiddenField = document.getElementById('map') as HTMLInputElement;
      expect(hiddenField.value).toContain('1');
      expect(hiddenField.value).toContain('2');
      expect(hiddenField.value).not.toContain('3');
    });

    it('calls multiActionGo with form and select', () => {
      document.body.innerHTML = `
        <div id="container">
          <form name="form1"></form>
        </div>
      `;

      const component = feedIndexData();
      const container = document.getElementById('container')!;
      (component as unknown as { $el: HTMLElement }).$el = container;

      const select = document.createElement('select');
      const event = { target: select } as unknown as Event;

      component.handleMarkAction.call(component as any, event);

      expect(multiActionGo).toHaveBeenCalled();
    });

    it('handles missing hidden field gracefully', () => {
      document.body.innerHTML = `
        <div id="container">
          <form name="form1"></form>
        </div>
      `;

      const component = feedIndexData();
      const container = document.getElementById('container')!;
      (component as unknown as { $el: HTMLElement }).$el = container;

      const select = document.createElement('select');
      const event = { target: select } as unknown as Event;

      expect(() => {
        component.handleMarkAction.call(component as any, event);
      }).not.toThrow();
    });
  });

  // ===========================================================================
  // handleSort() Tests
  // ===========================================================================

  describe('handleSort()', () => {
    it('navigates to URL with sort parameter', () => {
      const component = feedIndexData({ pageBaseUrl: '/feeds/edit' });
      const select = document.createElement('select');
      const option = document.createElement('option');
      option.value = '2';
      select.appendChild(option);
      select.value = '2';
      const event = { target: select } as unknown as Event;

      component.handleSort(event);

      expect(window.location.href).toBe('/feeds/edit?page=1&sort=2');
    });

    it('encodes special characters in sort value', () => {
      const component = feedIndexData();
      const select = document.createElement('select');
      const option = document.createElement('option');
      option.value = 'name&asc';
      select.appendChild(option);
      select.value = 'name&asc';
      const event = { target: select } as unknown as Event;

      component.handleSort(event);

      expect(window.location.href).toBe('/feeds/edit?page=1&sort=name%26asc');
    });
  });

  // ===========================================================================
  // confirmDelete() Tests
  // ===========================================================================

  describe('confirmDelete()', () => {
    it('navigates to delete URL when confirmed', () => {
      vi.spyOn(window, 'confirm').mockReturnValue(true);

      const component = feedIndexData();

      component.confirmDelete('42');

      expect(window.confirm).toHaveBeenCalledWith('Are you sure?');
      expect(window.location.href).toBe('/feeds/edit?markaction=del&selected_feed=42');
    });

    it('does not navigate when cancelled', () => {
      vi.spyOn(window, 'confirm').mockReturnValue(false);

      const component = feedIndexData();

      component.confirmDelete('42');

      expect(window.confirm).toHaveBeenCalledWith('Are you sure?');
      expect(window.location.href).toBe('');
    });
  });
});
