/**
 * Tests for texts_grouped_app.ts - Texts grouped by language Alpine component
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock Alpine.js
vi.mock('alpinejs', () => ({
  default: {
    data: vi.fn()
  }
}));

// Mock lucide icons
vi.mock('../../../src/frontend/js/shared/icons/lucide_icons', () => ({
  initIcons: vi.fn()
}));

// Mock API client
vi.mock('../../../src/frontend/js/shared/api/client', () => ({
  apiGet: vi.fn()
}));

// Mock TextsApi
vi.mock('../../../src/frontend/js/modules/text/api/texts_api', () => ({
  TextsApi: {
    getStatistics: vi.fn().mockResolvedValue({ data: {} })
  }
}));

// Mock ui_utilities
vi.mock('../../../src/frontend/js/shared/utils/ui_utilities', () => ({
  confirmDelete: vi.fn(() => false)
}));

import Alpine from 'alpinejs';
import { textsGroupedData, initTextsGroupedAlpine } from '../../../src/frontend/js/modules/text/pages/texts_grouped_app';
import { apiGet } from '../../../src/frontend/js/shared/api/client';
import { confirmDelete } from '../../../src/frontend/js/shared/utils/ui_utilities';

describe('texts_grouped_app.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    localStorage.clear();
    vi.useFakeTimers();

    // Default mock responses
    (apiGet as ReturnType<typeof vi.fn>).mockResolvedValue({
      data: { languages: [], texts: [], pagination: { current_page: 1, per_page: 10, total: 0, total_pages: 0 } }
    });
  });

  afterEach(() => {
    vi.restoreAllMocks();
    vi.useRealTimers();
    document.body.innerHTML = '';
    localStorage.clear();
  });

  // ===========================================================================
  // textsGroupedData Factory Tests
  // ===========================================================================

  describe('textsGroupedData', () => {
    it('creates component with default values', () => {
      const component = textsGroupedData();

      expect(component.loading).toBe(true);
      expect(component.languages).toEqual([]);
      expect(component.collapsedLanguages).toEqual([]);
      expect(component.sort).toBe(1);
    });

    it('reads activeLanguageId from config', () => {
      document.body.innerHTML = `
        <script id="texts-grouped-config" type="application/json">
          {"activeLanguageId": 5}
        </script>
      `;

      const component = textsGroupedData();

      expect(component.activeLanguageId).toBe(5);
    });

    it('handles missing config gracefully', () => {
      const component = textsGroupedData();

      expect(component.activeLanguageId).toBe(0);
    });
  });

  // ===========================================================================
  // isCollapsed Tests
  // ===========================================================================

  describe('isCollapsed', () => {
    it('returns false when language is not collapsed', () => {
      const component = textsGroupedData();
      component.collapsedLanguages = [1, 2];

      expect(component.isCollapsed(3)).toBe(false);
    });

    it('returns true when language is collapsed', () => {
      const component = textsGroupedData();
      component.collapsedLanguages = [1, 2, 3];

      expect(component.isCollapsed(2)).toBe(true);
    });
  });

  // ===========================================================================
  // toggleLanguage Tests
  // ===========================================================================

  describe('toggleLanguage', () => {
    it('expands collapsed language', async () => {
      const component = textsGroupedData();
      component.collapsedLanguages = [1, 2, 3];
      component.languageStates = new Map([[2, {
        texts: [],
        stats: new Map(),
        pagination: { current_page: 0, per_page: 10, total: 0, total_pages: 0 },
        loading: false,
        marked: new Set()
      }]]);

      await component.toggleLanguage(2);

      expect(component.collapsedLanguages).not.toContain(2);
    });

    it('collapses expanded language', async () => {
      const component = textsGroupedData();
      component.collapsedLanguages = [1, 3];

      await component.toggleLanguage(2);

      expect(component.collapsedLanguages).toContain(2);
    });

    it('saves collapse state to localStorage', async () => {
      const component = textsGroupedData();
      component.collapsedLanguages = [1];

      await component.toggleLanguage(2);

      const stored = localStorage.getItem('lwt_collapsed_languages');
      expect(stored).not.toBeNull();
      expect(JSON.parse(stored!)).toContain(2);
    });
  });

  // ===========================================================================
  // saveCollapseState Tests
  // ===========================================================================

  describe('saveCollapseState', () => {
    it('saves collapsed languages to localStorage', () => {
      const component = textsGroupedData();
      component.collapsedLanguages = [1, 2, 3];

      component.saveCollapseState();

      const stored = localStorage.getItem('lwt_collapsed_languages');
      expect(JSON.parse(stored!)).toEqual([1, 2, 3]);
    });
  });

  // ===========================================================================
  // loadCollapseState Tests
  // ===========================================================================

  describe('loadCollapseState', () => {
    it('loads collapsed languages from localStorage', () => {
      localStorage.setItem('lwt_collapsed_languages', JSON.stringify([4, 5, 6]));

      const component = textsGroupedData();
      component.loadCollapseState();

      expect(component.collapsedLanguages).toEqual([4, 5, 6]);
    });

    it('uses empty array when localStorage is empty', () => {
      const component = textsGroupedData();
      component.loadCollapseState();

      expect(component.collapsedLanguages).toEqual([]);
    });

    it('handles invalid JSON gracefully', () => {
      localStorage.setItem('lwt_collapsed_languages', 'invalid json');

      const component = textsGroupedData();
      component.loadCollapseState();

      expect(component.collapsedLanguages).toEqual([]);
    });
  });

  // ===========================================================================
  // getTextsForLanguage Tests
  // ===========================================================================

  describe('getTextsForLanguage', () => {
    it('returns texts for language', () => {
      const component = textsGroupedData();
      const texts = [{ id: 1, title: 'Test' }];
      component.languageStates = new Map([[1, {
        texts: texts as never[],
        stats: new Map(),
        pagination: { current_page: 1, per_page: 10, total: 1, total_pages: 1 },
        loading: false,
        marked: new Set()
      }]]);

      expect(component.getTextsForLanguage(1)).toEqual(texts);
    });

    it('returns empty array for unknown language', () => {
      const component = textsGroupedData();

      expect(component.getTextsForLanguage(999)).toEqual([]);
    });
  });

  // ===========================================================================
  // hasMoreTexts Tests
  // ===========================================================================

  describe('hasMoreTexts', () => {
    it('returns true when more pages available', () => {
      const component = textsGroupedData();
      component.languageStates = new Map([[1, {
        texts: [],
        stats: new Map(),
        pagination: { current_page: 1, per_page: 10, total: 25, total_pages: 3 },
        loading: false,
        marked: new Set()
      }]]);

      expect(component.hasMoreTexts(1)).toBe(true);
    });

    it('returns false when on last page', () => {
      const component = textsGroupedData();
      component.languageStates = new Map([[1, {
        texts: [],
        stats: new Map(),
        pagination: { current_page: 3, per_page: 10, total: 25, total_pages: 3 },
        loading: false,
        marked: new Set()
      }]]);

      expect(component.hasMoreTexts(1)).toBe(false);
    });

    it('returns false for unknown language', () => {
      const component = textsGroupedData();

      expect(component.hasMoreTexts(999)).toBe(false);
    });
  });

  // ===========================================================================
  // Selection Methods Tests
  // ===========================================================================

  describe('selection methods', () => {
    let component: ReturnType<typeof textsGroupedData>;

    beforeEach(() => {
      component = textsGroupedData();
      component.languageStates = new Map([[1, {
        texts: [{ id: 10 }, { id: 20 }, { id: 30 }] as never[],
        stats: new Map(),
        pagination: { current_page: 1, per_page: 10, total: 3, total_pages: 1 },
        loading: false,
        marked: new Set()
      }]]);
    });

    describe('markAll', () => {
      it('marks all texts when checked', () => {
        component.markAll(1, true);

        expect(component.isMarked(1, 10)).toBe(true);
        expect(component.isMarked(1, 20)).toBe(true);
        expect(component.isMarked(1, 30)).toBe(true);
      });

      it('clears marks when unchecked', () => {
        component.markAll(1, true);
        component.markAll(1, false);

        expect(component.isMarked(1, 10)).toBe(false);
        expect(component.isMarked(1, 20)).toBe(false);
      });
    });

    describe('toggleMark', () => {
      it('marks text when checked', () => {
        component.toggleMark(1, 10, true);

        expect(component.isMarked(1, 10)).toBe(true);
      });

      it('unmarks text when unchecked', () => {
        component.toggleMark(1, 10, true);
        component.toggleMark(1, 10, false);

        expect(component.isMarked(1, 10)).toBe(false);
      });
    });

    describe('isMarked', () => {
      it('returns false for unmarked text', () => {
        expect(component.isMarked(1, 10)).toBe(false);
      });

      it('returns true for marked text', () => {
        component.toggleMark(1, 10, true);

        expect(component.isMarked(1, 10)).toBe(true);
      });
    });

    describe('hasMarkedInLanguage', () => {
      it('returns false when nothing marked', () => {
        expect(component.hasMarkedInLanguage(1)).toBe(false);
      });

      it('returns true when something marked', () => {
        component.toggleMark(1, 10, true);

        expect(component.hasMarkedInLanguage(1)).toBe(true);
      });
    });

    describe('getMarkedIds', () => {
      it('returns array of marked ids', () => {
        component.toggleMark(1, 10, true);
        component.toggleMark(1, 30, true);

        const marked = component.getMarkedIds(1);
        expect(marked).toContain(10);
        expect(marked).toContain(30);
        expect(marked).not.toContain(20);
      });

      it('returns empty array for unknown language', () => {
        expect(component.getMarkedIds(999)).toEqual([]);
      });
    });

    describe('getMarkedCount', () => {
      it('returns count of marked items', () => {
        component.toggleMark(1, 10, true);
        component.toggleMark(1, 30, true);

        expect(component.getMarkedCount(1)).toBe(2);
      });

      it('returns 0 for unknown language', () => {
        expect(component.getMarkedCount(999)).toBe(0);
      });
    });
  });

  // ===========================================================================
  // parseTags Tests
  // ===========================================================================

  describe('parseTags', () => {
    it('parses comma-separated tags', () => {
      const component = textsGroupedData();

      expect(component.parseTags('tag1, tag2, tag3')).toEqual(['tag1', 'tag2', 'tag3']);
    });

    it('trims whitespace', () => {
      const component = textsGroupedData();

      expect(component.parseTags('  tag1  ,  tag2  ')).toEqual(['tag1', 'tag2']);
    });

    it('returns empty array for empty string', () => {
      const component = textsGroupedData();

      expect(component.parseTags('')).toEqual([]);
    });

    it('returns empty array for whitespace only', () => {
      const component = textsGroupedData();

      expect(component.parseTags('   ')).toEqual([]);
    });

    it('filters out empty tags', () => {
      const component = textsGroupedData();

      expect(component.parseTags('tag1,,tag2')).toEqual(['tag1', 'tag2']);
    });
  });

  // ===========================================================================
  // handleDelete Tests
  // ===========================================================================

  describe('handleDelete', () => {
    it('prevents default event', () => {
      const component = textsGroupedData();
      const event = { preventDefault: vi.fn() } as unknown as Event;

      component.handleDelete(event, '/delete/1');

      expect(event.preventDefault).toHaveBeenCalled();
    });

    it('shows confirmation dialog', () => {
      const component = textsGroupedData();
      const event = { preventDefault: vi.fn() } as unknown as Event;

      component.handleDelete(event, '/delete/1');

      expect(confirmDelete).toHaveBeenCalled();
    });

    it('navigates when confirmed', () => {
      (confirmDelete as ReturnType<typeof vi.fn>).mockReturnValue(true);
      const originalLocation = window.location;
      delete (window as { location?: Location }).location;
      window.location = { href: '' } as Location;

      const component = textsGroupedData();
      const event = { preventDefault: vi.fn() } as unknown as Event;

      component.handleDelete(event, '/delete/1');

      expect(window.location.href).toBe('/delete/1');

      window.location = originalLocation;
    });

    it('does not navigate when cancelled', () => {
      (confirmDelete as ReturnType<typeof vi.fn>).mockReturnValue(false);

      const component = textsGroupedData();
      const event = { preventDefault: vi.fn() } as unknown as Event;

      component.handleDelete(event, '/delete/1');

      // No navigation should occur
    });
  });

  // ===========================================================================
  // handleSortChange Tests
  // ===========================================================================

  describe('handleSortChange', () => {
    it('updates sort value', () => {
      const component = textsGroupedData();
      const event = { target: { value: '3' } } as unknown as Event;

      component.handleSortChange(event);

      expect(component.sort).toBe(3);
    });

    it('defaults to 1 for invalid value', () => {
      const component = textsGroupedData();
      const event = { target: { value: 'invalid' } } as unknown as Event;

      component.handleSortChange(event);

      expect(component.sort).toBe(1);
    });
  });

  // ===========================================================================
  // Safe Stats Accessors Tests
  // ===========================================================================

  describe('safe stats accessors', () => {
    let component: ReturnType<typeof textsGroupedData>;

    beforeEach(() => {
      component = textsGroupedData();
      component.languageStates = new Map([[1, {
        texts: [],
        stats: new Map([[10, {
          total: 100,
          saved: 50,
          unknown: 25,
          unknownPercent: 25,
          statusCounts: {}
        }]]),
        pagination: { current_page: 1, per_page: 10, total: 1, total_pages: 1 },
        loading: false,
        marked: new Set()
      }]]);
    });

    it('getStatTotal returns total', () => {
      expect(component.getStatTotal(1, 10)).toBe('100');
    });

    it('getStatSaved returns saved', () => {
      expect(component.getStatSaved(1, 10)).toBe('50');
    });

    it('getStatUnknown returns unknown', () => {
      expect(component.getStatUnknown(1, 10)).toBe('25');
    });

    it('getStatUnknownPercent returns percent', () => {
      expect(component.getStatUnknownPercent(1, 10)).toBe('25%');
    });

    it('returns dash for missing stats', () => {
      expect(component.getStatTotal(1, 999)).toBe('-');
      expect(component.getStatSaved(1, 999)).toBe('-');
      expect(component.getStatUnknown(1, 999)).toBe('-');
      expect(component.getStatUnknownPercent(1, 999)).toBe('-');
    });
  });

  // ===========================================================================
  // initTextsGroupedAlpine Tests
  // ===========================================================================

  describe('initTextsGroupedAlpine', () => {
    it('registers textsGroupedApp component with Alpine', () => {
      initTextsGroupedAlpine();

      expect(Alpine.data).toHaveBeenCalledWith('textsGroupedApp', textsGroupedData);
    });
  });

  // ===========================================================================
  // Global Window Exposure Tests
  // ===========================================================================

  describe('global window exposure', () => {
    it('exposes textsGroupedData on window', () => {
      expect(typeof window.textsGroupedData).toBe('function');
    });
  });
});
