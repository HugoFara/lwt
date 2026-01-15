/**
 * Tests for feed_multi_load_component.ts - Feed multi-load Alpine component
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock the language_settings module
vi.mock('../../../src/frontend/js/modules/language/stores/language_settings', () => ({
  setLang: vi.fn()
}));

import {
  feedMultiLoadData,
  FeedMultiLoadConfig
} from '../../../src/frontend/js/modules/feed/components/feed_multi_load_component';
import { setLang } from '../../../src/frontend/js/modules/language/stores/language_settings';

describe('feed_multi_load_component.ts', () => {
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
  // feedMultiLoadData Factory Function Tests
  // ===========================================================================

  describe('feedMultiLoadData', () => {
    it('creates component with default values', () => {
      const component = feedMultiLoadData();

      expect(component.cancelUrl).toBe('/feeds');
      expect(component.filterUrl).toBe('/feeds/multi-load');
    });

    it('creates component with provided config values', () => {
      const config: FeedMultiLoadConfig = {
        cancelUrl: '/custom/cancel',
        filterUrl: '/custom/filter'
      };

      const component = feedMultiLoadData(config);

      expect(component.cancelUrl).toBe('/custom/cancel');
      expect(component.filterUrl).toBe('/custom/filter');
    });

    it('allows partial config', () => {
      const config: FeedMultiLoadConfig = {
        cancelUrl: '/custom/cancel'
      };

      const component = feedMultiLoadData(config);

      expect(component.cancelUrl).toBe('/custom/cancel');
      expect(component.filterUrl).toBe('/feeds/multi-load');
    });
  });

  // ===========================================================================
  // init() Method Tests
  // ===========================================================================

  describe('init()', () => {
    it('reads config from JSON script tag', () => {
      document.body.innerHTML = `
        <script type="application/json" id="feed-multi-load-config">
          {"cancelUrl": "/json/cancel", "filterUrl": "/json/filter"}
        </script>
      `;

      const component = feedMultiLoadData();
      component.init();

      expect(component.cancelUrl).toBe('/json/cancel');
      expect(component.filterUrl).toBe('/json/filter');
    });

    it('keeps defaults if no JSON config element exists', () => {
      const component = feedMultiLoadData();
      component.init();

      expect(component.cancelUrl).toBe('/feeds');
      expect(component.filterUrl).toBe('/feeds/multi-load');
    });

    it('handles invalid JSON gracefully', () => {
      document.body.innerHTML = `
        <script type="application/json" id="feed-multi-load-config">
          {invalid json}
        </script>
      `;

      const component = feedMultiLoadData();

      expect(() => component.init()).not.toThrow();
      expect(component.cancelUrl).toBe('/feeds');
    });
  });

  // ===========================================================================
  // markAll() and markNone() Tests
  // ===========================================================================

  describe('markAll()', () => {
    it('checks all markcheck checkboxes in form', () => {
      document.body.innerHTML = `
        <div id="container">
          <form>
            <input type="checkbox" class="markcheck" value="1" />
            <input type="checkbox" class="markcheck" value="2" />
            <input type="checkbox" class="markcheck" value="3" />
          </form>
        </div>
      `;

      const component = feedMultiLoadData();
      const container = document.getElementById('container')!;

      // Mock $el
      (component as unknown as { $el: HTMLElement }).$el = container;

      component.markAll();

      const checkboxes = document.querySelectorAll<HTMLInputElement>('.markcheck');
      checkboxes.forEach(cb => {
        expect(cb.checked).toBe(true);
      });
    });

    it('does nothing when form not found', () => {
      document.body.innerHTML = '<div id="container"></div>';

      const component = feedMultiLoadData();
      const container = document.getElementById('container')!;

      (component as unknown as { $el: HTMLElement }).$el = container;

      expect(() => component.markAll()).not.toThrow();
    });
  });

  describe('markNone()', () => {
    it('unchecks all markcheck checkboxes in form', () => {
      document.body.innerHTML = `
        <div id="container">
          <form>
            <input type="checkbox" class="markcheck" value="1" checked />
            <input type="checkbox" class="markcheck" value="2" checked />
            <input type="checkbox" class="markcheck" value="3" checked />
          </form>
        </div>
      `;

      const component = feedMultiLoadData();
      const container = document.getElementById('container')!;

      (component as unknown as { $el: HTMLElement }).$el = container;

      component.markNone();

      const checkboxes = document.querySelectorAll<HTMLInputElement>('.markcheck');
      checkboxes.forEach(cb => {
        expect(cb.checked).toBe(false);
      });
    });
  });

  // ===========================================================================
  // collectAndSubmit() Tests
  // ===========================================================================

  describe('collectAndSubmit()', () => {
    it('collects checked checkbox values into hidden field', () => {
      document.body.innerHTML = `
        <div id="container">
          <form>
            <input type="checkbox" value="1" checked />
            <input type="checkbox" value="2" />
            <input type="checkbox" value="3" checked />
            <input type="hidden" id="map" value="" />
          </form>
        </div>
      `;

      const component = feedMultiLoadData();
      const container = document.getElementById('container')!;

      (component as unknown as { $el: HTMLElement }).$el = container;

      component.collectAndSubmit();

      const hiddenField = document.getElementById('map') as HTMLInputElement;
      expect(hiddenField.value).toBe('1, 3');
    });

    it('filters out empty checkbox values', () => {
      document.body.innerHTML = `
        <div id="container">
          <form>
            <input type="checkbox" value="" checked />
            <input type="checkbox" value="1" checked />
            <input type="checkbox" value="2" checked />
            <input type="hidden" id="map" value="" />
          </form>
        </div>
      `;

      const component = feedMultiLoadData();
      const container = document.getElementById('container')!;

      (component as unknown as { $el: HTMLElement }).$el = container;

      component.collectAndSubmit();

      const hiddenField = document.getElementById('map') as HTMLInputElement;
      expect(hiddenField.value).toBe('1, 2');
    });

    it('returns empty string when no checkboxes checked', () => {
      document.body.innerHTML = `
        <div id="container">
          <form>
            <input type="checkbox" value="1" />
            <input type="checkbox" value="2" />
            <input type="hidden" id="map" value="previous" />
          </form>
        </div>
      `;

      const component = feedMultiLoadData();
      const container = document.getElementById('container')!;

      (component as unknown as { $el: HTMLElement }).$el = container;

      component.collectAndSubmit();

      const hiddenField = document.getElementById('map') as HTMLInputElement;
      expect(hiddenField.value).toBe('');
    });

    it('does nothing when form not found', () => {
      document.body.innerHTML = '<div id="container"></div>';

      const component = feedMultiLoadData();
      const container = document.getElementById('container')!;

      (component as unknown as { $el: HTMLElement }).$el = container;

      expect(() => component.collectAndSubmit()).not.toThrow();
    });

    it('does nothing when hidden field not found', () => {
      document.body.innerHTML = `
        <div id="container">
          <form>
            <input type="checkbox" value="1" checked />
          </form>
        </div>
      `;

      const component = feedMultiLoadData();
      const container = document.getElementById('container')!;

      (component as unknown as { $el: HTMLElement }).$el = container;

      expect(() => component.collectAndSubmit()).not.toThrow();
    });
  });

  // ===========================================================================
  // handleLanguageFilter() Tests
  // ===========================================================================

  describe('handleLanguageFilter()', () => {
    it('calls setLang with select element and filter URL', () => {
      const component = feedMultiLoadData({
        filterUrl: '/custom/filter'
      });

      const select = document.createElement('select');
      const event = { target: select } as unknown as Event;

      component.handleLanguageFilter(event);

      expect(setLang).toHaveBeenCalledWith(select, '/custom/filter');
    });

    it('uses default filter URL', () => {
      const component = feedMultiLoadData();

      const select = document.createElement('select');
      const event = { target: select } as unknown as Event;

      component.handleLanguageFilter(event);

      expect(setLang).toHaveBeenCalledWith(select, '/feeds/multi-load');
    });
  });

  // ===========================================================================
  // cancel() Tests
  // ===========================================================================

  describe('cancel()', () => {
    it('navigates to cancel URL', () => {
      const component = feedMultiLoadData({
        cancelUrl: '/custom/cancel'
      });

      component.cancel();

      expect(window.location.href).toBe('/custom/cancel');
    });

    it('uses default cancel URL', () => {
      const component = feedMultiLoadData();

      component.cancel();

      expect(window.location.href).toBe('/feeds');
    });
  });
});
