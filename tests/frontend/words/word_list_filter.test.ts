/**
 * Tests for word_list_filter.ts - Word list filter Alpine component
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { wordListFilterApp } from '../../../src/frontend/js/modules/vocabulary/stores/word_list_filter';

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
  // wordListFilterApp Component Tests
  // ===========================================================================

  describe('wordListFilterApp', () => {
    it('creates component with default config', () => {
      const component = wordListFilterApp();

      expect(component.baseUrl).toBe('/words/edit');
      expect(component.query).toBe('');
      expect(component.queryMode).toBe('term,rom,transl');
    });

    it('creates component with custom config', () => {
      const component = wordListFilterApp({
        baseUrl: '/custom/path',
        currentQuery: 'test',
        currentQueryMode: 'term'
      });

      expect(component.baseUrl).toBe('/custom/path');
      expect(component.query).toBe('test');
      expect(component.queryMode).toBe('term');
    });

    it('init reads config from JSON script tag', () => {
      document.body.innerHTML = `
        <script type="application/json" id="word-list-filter-config">
          {"baseUrl": "/api/words", "currentQuery": "hello", "currentQueryMode": "transl"}
        </script>
      `;

      const component = wordListFilterApp();
      component.init();

      expect(component.baseUrl).toBe('/api/words');
      expect(component.query).toBe('hello');
      expect(component.queryMode).toBe('transl');
    });

    it('init handles missing config element gracefully', () => {
      const component = wordListFilterApp({ currentQuery: 'keep' });
      component.init();

      expect(component.query).toBe('keep');
    });

    it('init handles invalid JSON in config element', () => {
      document.body.innerHTML = `
        <script type="application/json" id="word-list-filter-config">
          invalid json
        </script>
      `;

      const component = wordListFilterApp({ currentQuery: 'default' });
      component.init();

      expect(component.query).toBe('default');
    });
  });

  // ===========================================================================
  // Navigation Tests
  // ===========================================================================

  describe('navigateWithParams', () => {
    it('navigates to baseUrl with params and page=1', () => {
      const component = wordListFilterApp();
      component.navigateWithParams({ status: '99' });

      expect(window.location.href).toContain('/words/edit?');
      expect(window.location.href).toContain('page=1');
      expect(window.location.href).toContain('status=99');
    });

    it('uses custom baseUrl', () => {
      const component = wordListFilterApp({ baseUrl: '/custom/path' });
      component.navigateWithParams({ query: 'test' });

      expect(window.location.href).toContain('/custom/path?');
    });
  });

  // ===========================================================================
  // Language Filter Tests
  // ===========================================================================

  describe('handleLanguageChange', () => {
    it('calls setLang with select and baseUrl', () => {
      const component = wordListFilterApp();
      const select = document.createElement('select');
      const event = { target: select } as unknown as Event;

      component.handleLanguageChange(event);

      expect(setLang).toHaveBeenCalledWith(select, '/words/edit');
    });
  });

  // ===========================================================================
  // Text Mode Tests
  // ===========================================================================

  describe('handleTextModeChange', () => {
    it('navigates with text_mode and clears texttag and text', () => {
      const component = wordListFilterApp();
      const select = document.createElement('select');
      const option = document.createElement('option');
      option.value = '1';
      select.appendChild(option);
      select.value = '1';
      const event = { target: select } as unknown as Event;

      component.handleTextModeChange(event);

      expect(window.location.href).toContain('text_mode=1');
      expect(window.location.href).toContain('texttag=');
      expect(window.location.href).toContain('text=');
    });
  });

  // ===========================================================================
  // Text Filter Tests
  // ===========================================================================

  describe('handleTextChange', () => {
    it('navigates with text parameter', () => {
      const component = wordListFilterApp();
      const select = document.createElement('select');
      const option = document.createElement('option');
      option.value = '5';
      select.appendChild(option);
      select.value = '5';
      const event = { target: select } as unknown as Event;

      component.handleTextChange(event);

      expect(window.location.href).toContain('text=5');
    });
  });

  // ===========================================================================
  // Status Filter Tests
  // ===========================================================================

  describe('handleStatusChange', () => {
    it('navigates with status parameter', () => {
      const component = wordListFilterApp();
      const select = document.createElement('select');
      const option = document.createElement('option');
      option.value = '99';
      select.appendChild(option);
      select.value = '99';
      const event = { target: select } as unknown as Event;

      component.handleStatusChange(event);

      expect(window.location.href).toContain('status=99');
    });
  });

  // ===========================================================================
  // Query Mode Tests
  // ===========================================================================

  describe('handleQueryModeChange', () => {
    it('navigates with query and query_mode', () => {
      const component = wordListFilterApp({ currentQuery: 'hello' });
      const select = document.createElement('select');
      const option = document.createElement('option');
      option.value = 'term';
      select.appendChild(option);
      select.value = 'term';
      const event = { target: select } as unknown as Event;

      component.handleQueryModeChange(event);

      expect(window.location.href).toContain('query=hello');
      expect(window.location.href).toContain('query_mode=term');
    });
  });

  // ===========================================================================
  // Query Filter Tests
  // ===========================================================================

  describe('handleQueryFilter', () => {
    it('navigates with encoded query', () => {
      const component = wordListFilterApp();
      component.query = 'hello world';
      component.queryMode = 'term,transl';

      component.handleQueryFilter();

      expect(window.location.href).toContain('query=hello%20world');
      expect(window.location.href).toContain('query_mode=term%2Ctransl');
    });

    it('handles special characters in query', () => {
      const component = wordListFilterApp();
      component.query = 'café & résumé';

      component.handleQueryFilter();

      expect(window.location.href).toContain('caf%C3%A9');
    });
  });

  // ===========================================================================
  // Clear Query Tests
  // ===========================================================================

  describe('handleClearQuery', () => {
    it('clears query and navigates', () => {
      const component = wordListFilterApp({ currentQuery: 'to be cleared' });

      component.handleClearQuery();

      expect(component.query).toBe('');
      expect(window.location.href).toContain('query=');
    });
  });

  // ===========================================================================
  // Tag Filter Tests
  // ===========================================================================

  describe('handleTag1Change', () => {
    it('navigates with tag1 parameter', () => {
      const component = wordListFilterApp();
      const select = document.createElement('select');
      const option = document.createElement('option');
      option.value = 'verb';
      select.appendChild(option);
      select.value = 'verb';
      const event = { target: select } as unknown as Event;

      component.handleTag1Change(event);

      expect(window.location.href).toContain('tag1=verb');
    });
  });

  describe('handleTag12Change', () => {
    it('navigates with tag12 parameter', () => {
      const component = wordListFilterApp();
      const select = document.createElement('select');
      const option = document.createElement('option');
      option.value = '1';
      select.appendChild(option);
      select.value = '1';
      const event = { target: select } as unknown as Event;

      component.handleTag12Change(event);

      expect(window.location.href).toContain('tag12=1');
    });
  });

  describe('handleTag2Change', () => {
    it('navigates with tag2 parameter', () => {
      const component = wordListFilterApp();
      const select = document.createElement('select');
      const option = document.createElement('option');
      option.value = 'noun';
      select.appendChild(option);
      select.value = 'noun';
      const event = { target: select } as unknown as Event;

      component.handleTag2Change(event);

      expect(window.location.href).toContain('tag2=noun');
    });
  });

  // ===========================================================================
  // Sort Order Tests
  // ===========================================================================

  describe('handleSortChange', () => {
    it('navigates with sort parameter', () => {
      const component = wordListFilterApp();
      const select = document.createElement('select');
      const option = document.createElement('option');
      option.value = '3';
      select.appendChild(option);
      select.value = '3';
      const event = { target: select } as unknown as Event;

      component.handleSortChange(event);

      expect(window.location.href).toContain('sort=3');
    });
  });

  // ===========================================================================
  // Reset Tests
  // ===========================================================================

  describe('handleReset', () => {
    it('calls resetAll with baseUrl', () => {
      const component = wordListFilterApp({ baseUrl: '/custom/url' });

      component.handleReset();

      expect(resetAll).toHaveBeenCalledWith('/custom/url');
    });
  });

  // ===========================================================================
  // Window Export Tests
  // ===========================================================================

  describe('Window export', () => {
    it('exports wordListFilterApp to window', () => {
      expect(typeof window.wordListFilterApp).toBe('function');
    });
  });
});
