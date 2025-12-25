/**
 * Tests for feed_browse_component.ts - Feed browse Alpine component
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock the dependencies before importing the module
vi.mock('../../../src/frontend/js/modules/language/stores/language_settings', () => ({
  setLang: vi.fn(),
  resetAll: vi.fn()
}));

vi.mock('../../../src/frontend/js/shared/forms/bulk_actions', () => ({
  selectToggle: vi.fn()
}));

vi.mock('../../../src/frontend/js/shared/utils/ui_utilities', () => ({
  markClick: vi.fn()
}));

import {
  feedBrowseData,
  type FeedBrowseConfig
} from '../../../src/frontend/js/modules/feed/components/feed_browse_component';
import { setLang, resetAll } from '../../../src/frontend/js/modules/language/stores/language_settings';
import { selectToggle } from '../../../src/frontend/js/shared/forms/bulk_actions';
import { markClick } from '../../../src/frontend/js/shared/utils/ui_utilities';

describe('feed_browse_component.ts', () => {
  let originalLocation: Location;
  let originalOpen: typeof window.open;

  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    // Save original location and window.open
    originalLocation = window.location;
    originalOpen = window.open;
    // Mock location.href
    Object.defineProperty(window, 'location', {
      value: { href: '' },
      writable: true,
      configurable: true
    });
    // Mock window.open
    window.open = vi.fn();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
    // Restore original location and window.open
    Object.defineProperty(window, 'location', {
      value: originalLocation,
      writable: true,
      configurable: true
    });
    window.open = originalOpen;
  });

  // ===========================================================================
  // feedBrowseData Factory Function Tests
  // ===========================================================================

  describe('feedBrowseData', () => {
    it('creates component with default values', () => {
      const component = feedBrowseData();

      expect(component.filterUrl).toBe('/feeds?page=1&selected_feed=0');
      expect(component.resetUrl).toBe('/feeds');
      expect(component.pageBaseUrl).toBe('/feeds');
      expect(component.query).toBe('');
      expect(component.queryMode).toBe('');
    });

    it('creates component with provided config values', () => {
      const config: FeedBrowseConfig = {
        filterUrl: '/custom/filter',
        resetUrl: '/custom/reset',
        pageBaseUrl: '/custom/base',
        currentQuery: 'test query',
        currentQueryMode: 'title'
      };

      const component = feedBrowseData(config);

      expect(component.filterUrl).toBe('/custom/filter');
      expect(component.resetUrl).toBe('/custom/reset');
      expect(component.pageBaseUrl).toBe('/custom/base');
      expect(component.query).toBe('test query');
      expect(component.queryMode).toBe('title');
    });

    it('allows partial config', () => {
      const config: FeedBrowseConfig = {
        currentQuery: 'search term'
      };

      const component = feedBrowseData(config);

      expect(component.filterUrl).toBe('/feeds?page=1&selected_feed=0'); // Default
      expect(component.query).toBe('search term');
    });
  });

  // ===========================================================================
  // init() Method Tests
  // ===========================================================================

  describe('init()', () => {
    it('reads config from JSON script tag', () => {
      document.body.innerHTML = `
        <script type="application/json" id="feed-browse-config">
          {"filterUrl": "/json/filter", "currentQuery": "json query"}
        </script>
      `;

      const component = feedBrowseData();
      component.init();

      expect(component.filterUrl).toBe('/json/filter');
      expect(component.query).toBe('json query');
    });

    it('keeps defaults if no JSON config element exists', () => {
      const component = feedBrowseData();
      component.init();

      expect(component.filterUrl).toBe('/feeds?page=1&selected_feed=0');
      expect(component.query).toBe('');
    });

    it('handles invalid JSON gracefully', () => {
      document.body.innerHTML = `
        <script type="application/json" id="feed-browse-config">
          {invalid json}
        </script>
      `;

      const component = feedBrowseData({ currentQuery: 'original' });

      expect(() => component.init()).not.toThrow();
      expect(component.query).toBe('original');
    });
  });

  // ===========================================================================
  // handleLanguageFilter() Tests
  // ===========================================================================

  describe('handleLanguageFilter()', () => {
    it('calls setLang with select element and filter URL', () => {
      const component = feedBrowseData({ filterUrl: '/custom/filter' });
      const select = document.createElement('select');
      const event = { target: select } as unknown as Event;

      component.handleLanguageFilter(event);

      expect(setLang).toHaveBeenCalledWith(select, '/custom/filter');
    });

    it('uses default filter URL', () => {
      const component = feedBrowseData();
      const select = document.createElement('select');
      const event = { target: select } as unknown as Event;

      component.handleLanguageFilter(event);

      expect(setLang).toHaveBeenCalledWith(select, '/feeds?page=1&selected_feed=0');
    });
  });

  // ===========================================================================
  // handleQueryMode() Tests
  // ===========================================================================

  describe('handleQueryMode()', () => {
    it('navigates to URL with query and mode', () => {
      const component = feedBrowseData({ pageBaseUrl: '/feeds' });
      component.query = 'test query';

      const select = document.createElement('select');
      const option = document.createElement('option');
      option.value = 'content';
      select.appendChild(option);
      select.value = 'content';
      const event = { target: select } as unknown as Event;

      component.handleQueryMode(event);

      expect(window.location.href).toBe('/feeds?page=1&query=test%20query&query_mode=content');
    });

    it('handles empty query', () => {
      const component = feedBrowseData();
      component.query = '';

      const select = document.createElement('select');
      const option = document.createElement('option');
      option.value = 'title';
      select.appendChild(option);
      select.value = 'title';
      const event = { target: select } as unknown as Event;

      component.handleQueryMode(event);

      expect(window.location.href).toBe('/feeds?page=1&query=&query_mode=title');
    });
  });

  // ===========================================================================
  // handleQueryFilter() Tests
  // ===========================================================================

  describe('handleQueryFilter()', () => {
    it('navigates to URL with encoded query', () => {
      const component = feedBrowseData({ pageBaseUrl: '/feeds' });
      component.query = 'test search';

      component.handleQueryFilter();

      expect(window.location.href).toBe('/feeds?page=1&query=test%20search');
    });

    it('handles empty query', () => {
      const component = feedBrowseData();
      component.query = '';

      component.handleQueryFilter();

      expect(window.location.href).toBe('/feeds?page=1&query=');
    });
  });

  // ===========================================================================
  // handleClearQuery() Tests
  // ===========================================================================

  describe('handleClearQuery()', () => {
    it('clears query and navigates', () => {
      const component = feedBrowseData();
      component.query = 'existing query';

      component.handleClearQuery();

      expect(component.query).toBe('');
      expect(window.location.href).toBe('/feeds?page=1&query=');
    });
  });

  // ===========================================================================
  // handleReset() Tests
  // ===========================================================================

  describe('handleReset()', () => {
    it('calls resetAll with configured URL', () => {
      const component = feedBrowseData({ resetUrl: '/custom/reset' });

      component.handleReset();

      expect(resetAll).toHaveBeenCalledWith('/custom/reset');
    });

    it('uses default reset URL', () => {
      const component = feedBrowseData();

      component.handleReset();

      expect(resetAll).toHaveBeenCalledWith('/feeds');
    });
  });

  // ===========================================================================
  // handleFeedSelect() Tests
  // ===========================================================================

  describe('handleFeedSelect()', () => {
    it('navigates to URL with selected feed', () => {
      const component = feedBrowseData({ pageBaseUrl: '/feeds' });
      const select = document.createElement('select');
      const option = document.createElement('option');
      option.value = '5';
      select.appendChild(option);
      select.value = '5';
      const event = { target: select } as unknown as Event;

      component.handleFeedSelect(event);

      expect(window.location.href).toBe('/feeds?page=1&selected_feed=5');
    });
  });

  // ===========================================================================
  // handleSort() Tests
  // ===========================================================================

  describe('handleSort()', () => {
    it('navigates to URL with sort parameter', () => {
      const component = feedBrowseData({ pageBaseUrl: '/feeds' });
      const select = document.createElement('select');
      const option = document.createElement('option');
      option.value = '2';
      select.appendChild(option);
      select.value = '2';
      const event = { target: select } as unknown as Event;

      component.handleSort(event);

      expect(window.location.href).toBe('/feeds?page=1&sort=2');
    });
  });

  // ===========================================================================
  // markAll() and markNone() Tests
  // ===========================================================================

  describe('markAll()', () => {
    it('calls selectToggle with true and form2', () => {
      const component = feedBrowseData();

      component.markAll();

      expect(selectToggle).toHaveBeenCalledWith(true, 'form2');
    });
  });

  describe('markNone()', () => {
    it('calls selectToggle with false and form2', () => {
      const component = feedBrowseData();

      component.markNone();

      expect(selectToggle).toHaveBeenCalledWith(false, 'form2');
    });
  });

  // ===========================================================================
  // openPopup() Tests
  // ===========================================================================

  describe('openPopup()', () => {
    it('opens audio popup with specific dimensions', () => {
      const component = feedBrowseData();

      component.openPopup('http://example.com/audio.mp3', 'audio');

      expect(window.open).toHaveBeenCalledWith(
        'http://example.com/audio.mp3',
        'child',
        'scrollbars,width=650,height=600'
      );
    });

    it('opens external popup without dimensions', () => {
      const component = feedBrowseData();

      component.openPopup('http://example.com/article', 'external');

      expect(window.open).toHaveBeenCalledWith('http://example.com/article');
    });
  });

  // ===========================================================================
  // handleNotFoundClick() Tests
  // ===========================================================================

  describe('handleNotFoundClick()', () => {
    it('replaces not_found element with checkbox on click', () => {
      document.body.innerHTML = `
        <span class="not_found" name="item_123">Error</span>
      `;

      const component = feedBrowseData();
      const span = document.querySelector('.not_found')!;
      const event = { target: span } as unknown as Event;

      component.handleNotFoundClick(event);

      // Span should be replaced with checkbox
      expect(document.querySelector('.not_found')).toBeNull();
      expect(document.querySelector('input[type="checkbox"]')).not.toBeNull();
    });

    it('creates checkbox with correct attributes', () => {
      document.body.innerHTML = `
        <span class="not_found" name="item_456">Error</span>
      `;

      const component = feedBrowseData();
      const span = document.querySelector('.not_found')!;
      component.handleNotFoundClick({ target: span } as unknown as Event);

      const checkbox = document.querySelector('input[type="checkbox"]') as HTMLInputElement;
      expect(checkbox).not.toBeNull();
      expect(checkbox.className).toBe('markcheck');
      expect(checkbox.id).toBe('item_456');
      expect(checkbox.value).toBe('item_456');
      expect(checkbox.name).toBe('marked_items[]');
    });

    it('creates label for checkbox', () => {
      document.body.innerHTML = `
        <span class="not_found" name="item_789">Error</span>
      `;

      const component = feedBrowseData();
      const span = document.querySelector('.not_found')!;
      component.handleNotFoundClick({ target: span } as unknown as Event);

      const label = document.querySelector('label.wrap_checkbox');
      expect(label).not.toBeNull();
      expect(label!.getAttribute('for')).toBe('item_789');
    });

    it('calls markClick on checkbox change', () => {
      document.body.innerHTML = `
        <span class="not_found" name="item_111">Error</span>
      `;

      const component = feedBrowseData();
      const span = document.querySelector('.not_found')!;
      component.handleNotFoundClick({ target: span } as unknown as Event);

      const checkbox = document.querySelector('input[type="checkbox"]') as HTMLInputElement;
      checkbox.dispatchEvent(new Event('change'));

      expect(markClick).toHaveBeenCalled();
    });

    it('ignores clicks on non-not_found elements', () => {
      document.body.innerHTML = `
        <span class="regular_element" name="item_444">Normal</span>
      `;

      const component = feedBrowseData();
      const span = document.querySelector('.regular_element')!;
      component.handleNotFoundClick({ target: span } as unknown as Event);

      // Span should still exist
      expect(document.querySelector('.regular_element')).not.toBeNull();
      expect(document.querySelector('input[type="checkbox"]')).toBeNull();
    });

    it('handles element with empty name attribute', () => {
      document.body.innerHTML = `
        <span class="not_found" name="">Error</span>
      `;

      const component = feedBrowseData();
      const span = document.querySelector('.not_found')!;

      expect(() => {
        component.handleNotFoundClick({ target: span } as unknown as Event);
      }).not.toThrow();

      const checkbox = document.querySelector('input[type="checkbox"]') as HTMLInputElement;
      expect(checkbox.id).toBe('');
    });
  });
});
