/**
 * Tests for word_upload.ts - Alpine.js components for word import
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  wordUploadFormApp,
  wordUploadResultApp,
  type WordUploadFormConfig,
  type UploadResultConfig
} from '../../../src/frontend/js/modules/vocabulary/pages/word_upload';

describe('word_upload.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // wordUploadFormApp Tests
  // ===========================================================================

  describe('wordUploadFormApp', () => {
    it('initializes with default values', () => {
      const component = wordUploadFormApp();

      expect(component.importMode).toBe(0);
      expect(component.showDelimiter).toBe(false);
    });

    it('initializes with config values', () => {
      const config: WordUploadFormConfig = { initialMode: 4 };
      const component = wordUploadFormApp(config);

      expect(component.importMode).toBe(4);
    });

    it('init sets showDelimiter based on importMode', () => {
      const config: WordUploadFormConfig = { initialMode: 5 };
      const component = wordUploadFormApp(config);
      component.init();

      expect(component.showDelimiter).toBe(true);
    });

    describe('updateImportMode', () => {
      it('shows delimiter for mode 4', () => {
        const component = wordUploadFormApp();
        component.updateImportMode(4);

        expect(component.importMode).toBe(4);
        expect(component.showDelimiter).toBe(true);
      });

      it('shows delimiter for mode 5', () => {
        const component = wordUploadFormApp();
        component.updateImportMode(5);

        expect(component.importMode).toBe(5);
        expect(component.showDelimiter).toBe(true);
      });

      it('hides delimiter for modes 0-3', () => {
        const component = wordUploadFormApp();
        component.updateImportMode(4); // First show it

        component.updateImportMode(0);
        expect(component.showDelimiter).toBe(false);

        component.updateImportMode(1);
        expect(component.showDelimiter).toBe(false);

        component.updateImportMode(2);
        expect(component.showDelimiter).toBe(false);

        component.updateImportMode(3);
        expect(component.showDelimiter).toBe(false);
      });

      it('handles string values', () => {
        const component = wordUploadFormApp();
        component.updateImportMode('4');

        expect(component.importMode).toBe(4);
        expect(component.showDelimiter).toBe(true);
      });
    });
  });

  // ===========================================================================
  // wordUploadResultApp Tests
  // ===========================================================================

  describe('wordUploadResultApp', () => {
    it('initializes with default values', () => {
      const component = wordUploadResultApp();

      expect(component.lastUpdate).toBe('');
      expect(component.rtl).toBe(false);
      expect(component.recno).toBe(0);
      expect(component.currentPage).toBe(1);
      expect(component.totalPages).toBe(1);
      expect(component.terms).toEqual([]);
      expect(component.isLoading).toBe(false);
      expect(component.hasTerms).toBe(false);
    });

    it('initializes with config values', () => {
      const config: UploadResultConfig = {
        lastUpdate: '2024-01-01',
        rtl: true,
        recno: 10
      };
      const component = wordUploadResultApp(config);

      expect(component.lastUpdate).toBe('2024-01-01');
      expect(component.rtl).toBe(true);
      expect(component.recno).toBe(10);
    });

    describe('init', () => {
      it('sets hasTerms based on recno', () => {
        const component = wordUploadResultApp({ lastUpdate: '', rtl: false, recno: 5 });

        // Mock fetch
        global.fetch = vi.fn(() =>
          Promise.resolve({
            ok: true,
            json: () => Promise.resolve({ navigation: { current_page: 1, total_pages: 1 }, terms: [] })
          } as Response)
        );

        component.init();

        expect(component.hasTerms).toBe(true);
      });

      it('does not load page when recno is 0', () => {
        const component = wordUploadResultApp({ lastUpdate: '', rtl: false, recno: 0 });

        global.fetch = vi.fn();
        component.init();

        expect(component.hasTerms).toBe(false);
        expect(global.fetch).not.toHaveBeenCalled();
      });
    });

    describe('loadPage', () => {
      it('fetches terms from API', async () => {
        const mockResponse = {
          navigation: { current_page: 1, total_pages: 3 },
          terms: [{ WoID: 1, WoText: 'test', WoTranslation: 'test', WoRomanization: '', WoSentence: '', WoStatus: 1, SentOK: 1, taglist: '' }]
        };

        global.fetch = vi.fn(() =>
          Promise.resolve({
            ok: true,
            json: () => Promise.resolve(mockResponse)
          } as Response)
        );

        const component = wordUploadResultApp({ lastUpdate: '2024-01-01', rtl: false, recno: 10 });
        await component.loadPage(1);

        expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining('/api/v1/terms/imported'));
        expect(component.currentPage).toBe(1);
        expect(component.totalPages).toBe(3);
        expect(component.terms.length).toBe(1);
        expect(component.hasTerms).toBe(true);
      });

      it('handles empty response', async () => {
        const component = wordUploadResultApp({ lastUpdate: '', rtl: false, recno: 0 });
        await component.loadPage(1);

        expect(component.hasTerms).toBe(false);
      });

      it('handles fetch errors', async () => {
        vi.spyOn(console, 'error').mockImplementation(() => {}); // Suppress expected error
        global.fetch = vi.fn(() => Promise.reject(new Error('Network error')));

        const component = wordUploadResultApp({ lastUpdate: '2024-01-01', rtl: false, recno: 10 });
        await component.loadPage(1);

        expect(component.hasTerms).toBe(false);
        expect(component.isLoading).toBe(false);
      });
    });

    describe('navigation methods', () => {
      let component: ReturnType<typeof wordUploadResultApp>;

      beforeEach(() => {
        component = wordUploadResultApp({ lastUpdate: '2024-01-01', rtl: false, recno: 100 });
        component.totalPages = 5;
        component.currentPage = 3;
      });

      it('goToPage loads the specified page', () => {
        const loadPageSpy = vi.spyOn(component, 'loadPage').mockImplementation(() => Promise.resolve());

        component.goToPage(2);
        expect(loadPageSpy).toHaveBeenCalledWith(2);
      });

      it('goToPage ignores invalid page numbers', () => {
        const loadPageSpy = vi.spyOn(component, 'loadPage').mockImplementation(() => Promise.resolve());

        component.goToPage(0);
        expect(loadPageSpy).not.toHaveBeenCalled();

        component.goToPage(6);
        expect(loadPageSpy).not.toHaveBeenCalled();
      });

      it('goFirst goes to page 1', () => {
        const loadPageSpy = vi.spyOn(component, 'loadPage').mockImplementation(() => Promise.resolve());

        component.goFirst();
        expect(loadPageSpy).toHaveBeenCalledWith(1);
      });

      it('goPrev goes to previous page', () => {
        const loadPageSpy = vi.spyOn(component, 'loadPage').mockImplementation(() => Promise.resolve());

        component.goPrev();
        expect(loadPageSpy).toHaveBeenCalledWith(2);
      });

      it('goNext goes to next page', () => {
        const loadPageSpy = vi.spyOn(component, 'loadPage').mockImplementation(() => Promise.resolve());

        component.goNext();
        expect(loadPageSpy).toHaveBeenCalledWith(4);
      });

      it('goLast goes to last page', () => {
        const loadPageSpy = vi.spyOn(component, 'loadPage').mockImplementation(() => Promise.resolve());

        component.goLast();
        expect(loadPageSpy).toHaveBeenCalledWith(5);
      });
    });

    describe('formatTermRow', () => {
      it('formats term with all fields', () => {
        const component = wordUploadResultApp({ lastUpdate: '', rtl: false, recno: 1 });
        const term = {
          WoID: 1,
          WoText: 'hello',
          WoTranslation: 'hola',
          WoRomanization: 'helo',
          WoSentence: 'Hello world',
          WoStatus: 1,
          SentOK: 1,
          taglist: 'tag1, tag2'
        };

        const html = component.formatTermRow(term);

        expect(html).toContain('hello');
        expect(html).toContain('hola');
        expect(html).toContain('helo');
        expect(html).toContain('tag1');
        expect(html).toContain('tag2');
      });

      it('applies RTL direction when enabled', () => {
        const component = wordUploadResultApp({ lastUpdate: '', rtl: true, recno: 1 });
        const term = {
          WoID: 1,
          WoText: 'مرحبا',
          WoTranslation: 'Hello',
          WoRomanization: '',
          WoSentence: '',
          WoStatus: 1,
          SentOK: 0,
          taglist: ''
        };

        const html = component.formatTermRow(term);

        expect(html).toContain('dir="rtl"');
      });

      it('shows asterisk for empty romanization', () => {
        const component = wordUploadResultApp({ lastUpdate: '', rtl: false, recno: 1 });
        const term = {
          WoID: 1,
          WoText: 'test',
          WoTranslation: 'test',
          WoRomanization: '',
          WoSentence: '',
          WoStatus: 1,
          SentOK: 0,
          taglist: ''
        };

        const html = component.formatTermRow(term);

        expect(html).toContain('>*<');
      });

      it('shows check icon for valid sentence', () => {
        const component = wordUploadResultApp({ lastUpdate: '', rtl: false, recno: 1 });
        const term = {
          WoID: 1,
          WoText: 'test',
          WoTranslation: 'test',
          WoRomanization: '',
          WoSentence: 'Test sentence',
          WoStatus: 1,
          SentOK: 1,
          taglist: ''
        };

        const html = component.formatTermRow(term);

        expect(html).toContain('data-lucide="check"');
      });

      it('shows x icon for invalid sentence', () => {
        const component = wordUploadResultApp({ lastUpdate: '', rtl: false, recno: 1 });
        const term = {
          WoID: 1,
          WoText: 'test',
          WoTranslation: 'test',
          WoRomanization: '',
          WoSentence: '',
          WoStatus: 1,
          SentOK: 0,
          taglist: ''
        };

        const html = component.formatTermRow(term);

        expect(html).toContain('data-lucide="x"');
      });
    });

    describe('getStatusInfo', () => {
      it('returns status info for valid status', () => {
        const component = wordUploadResultApp();
        const info = component.getStatusInfo(99);

        expect(info.abbr).toBe('WKn');
        expect(info.name).toBe('Well Known');
      });

      it('returns unknown for invalid status', () => {
        const component = wordUploadResultApp();
        const info = component.getStatusInfo(999);

        expect(info.abbr).toBe('?');
        expect(info.name).toBe('Unknown');
      });
    });
  });
});
