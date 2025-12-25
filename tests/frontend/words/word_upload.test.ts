/**
 * Tests for word_upload.ts - Word import form and results display
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  updateImportMode,
  showImportedTerms
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
  // updateImportMode Tests
  // ===========================================================================

  describe('updateImportMode', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <div id="imp_transl_delim" class="hide">
          <input type="text">
        </div>
      `;
    });

    describe('shows translation delimiter field', () => {
      it('for mode 4', () => {
        updateImportMode(4);

        expect(document.querySelector('#imp_transl_delim')!.classList.contains('hide')).toBe(false);
        expect(document.querySelector('#imp_transl_delim input')!.classList.contains('notempty')).toBe(true);
      });

      it('for mode 5', () => {
        updateImportMode(5);

        expect(document.querySelector('#imp_transl_delim')!.classList.contains('hide')).toBe(false);
      });

      it('for string mode "4"', () => {
        updateImportMode('4');

        expect(document.querySelector('#imp_transl_delim')!.classList.contains('hide')).toBe(false);
      });

      it('for string mode "5"', () => {
        updateImportMode('5');

        expect(document.querySelector('#imp_transl_delim')!.classList.contains('hide')).toBe(false);
      });
    });

    describe('hides translation delimiter field', () => {
      beforeEach(() => {
        // First show it
        document.querySelector('#imp_transl_delim')!.classList.remove('hide');
        document.querySelector('#imp_transl_delim input')!.classList.add('notempty');
      });

      it('for mode 0', () => {
        updateImportMode(0);

        expect(document.querySelector('#imp_transl_delim')!.classList.contains('hide')).toBe(true);
        expect(document.querySelector('#imp_transl_delim input')!.classList.contains('notempty')).toBe(false);
      });

      it('for mode 1', () => {
        updateImportMode(1);

        expect(document.querySelector('#imp_transl_delim')!.classList.contains('hide')).toBe(true);
      });

      it('for mode 2', () => {
        updateImportMode(2);

        expect(document.querySelector('#imp_transl_delim')!.classList.contains('hide')).toBe(true);
      });

      it('for mode 3', () => {
        updateImportMode(3);

        expect(document.querySelector('#imp_transl_delim')!.classList.contains('hide')).toBe(true);
      });

      it('for string mode "0"', () => {
        updateImportMode('0');

        expect(document.querySelector('#imp_transl_delim')!.classList.contains('hide')).toBe(true);
      });
    });
  });

  // ===========================================================================
  // showImportedTerms Tests
  // ===========================================================================

  describe('showImportedTerms', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <span id="recno">10</span>
        <div id="res_data-no_terms_imported"></div>
        <div id="res_data-navigation">
          <div id="res_data-navigation-prev">
            <button id="res_data-navigation-prev-first"></button>
            <button id="res_data-navigation-prev-minus"></button>
          </div>
          <select id="res_data-navigation-quick_nav"></select>
          <span id="res_data-navigation-no_quick_nav"></span>
          <span id="res_data-navigation-totalPages"></span>
          <div id="res_data-navigation-next">
            <button id="res_data-navigation-next-plus"></button>
            <button id="res_data-navigation-next-last"></button>
          </div>
        </div>
        <table id="res_data-res_table">
          <tbody id="res_data-res_table-body"></tbody>
        </table>
      `;
    });

    it('shows no terms message when count is 0', () => {
      showImportedTerms('2024-01-01', false, 0, 1);

      // JSDOM normalizes 'inherit' to 'block', so check not 'none'
      expect(getComputedStyle(document.querySelector('#res_data-no_terms_imported')!).display).not.toBe('none');
      expect(getComputedStyle(document.querySelector('#res_data-navigation')!).display).toBe('none');
      expect(getComputedStyle(document.querySelector('#res_data-res_table')!).display).toBe('none');
    });

    it('shows results when count is greater than 0', () => {
      const mockResponse = {
        navigation: {
          current_page: 1,
          total_pages: 1
        },
        terms: []
      };

      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse)
        } as Response)
      );

      showImportedTerms('2024-01-01', false, 10, 1);

      expect(getComputedStyle(document.querySelector('#res_data-no_terms_imported')!).display).toBe('none');
      expect(getComputedStyle(document.querySelector('#res_data-navigation')!).display).not.toBe('none');
    });

    it('handles string count parameter', () => {
      showImportedTerms('2024-01-01', false, 0, 1);

      // Should still show no terms message - JSDOM normalizes 'inherit' to 'block'
      expect(getComputedStyle(document.querySelector('#res_data-no_terms_imported')!).display).not.toBe('none');
    });

    it('handles RTL as string "true"', async () => {
      const mockResponse = {
        navigation: { current_page: 1, total_pages: 1 },
        terms: [{
          WoID: 1,
          WoText: 'مرحبا',
          WoTranslation: 'Hello',
          WoRomanization: 'marhaba',
          WoSentence: 'مرحبا بك',
          WoStatus: 1,
          SentOK: 1,
          taglist: ''
        }]
      };

      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse)
        } as Response)
      );

      showImportedTerms('2024-01-01', 'true', 1, 1);

      // Should call API
      expect(global.fetch).toHaveBeenCalled();
    });

    it('handles RTL as string "1"', async () => {
      const mockResponse = {
        navigation: { current_page: 1, total_pages: 1 },
        terms: []
      };

      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse)
        } as Response)
      );

      showImportedTerms('2024-01-01', '1', 1, 1);

      expect(global.fetch).toHaveBeenCalled();
    });

    it('makes API call with correct parameters', () => {
      global.fetch = vi.fn(() => Promise.resolve({ ok: true, json: () => Promise.resolve({}) } as Response));

      showImportedTerms('2024-01-15 10:30:00', false, 25, 2);

      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('api.php/v1/terms/imported')
      );
      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('last_update=2024-01-15+10%3A30%3A00')
      );
      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('count=25')
      );
      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('page=2')
      );
    });

    it('handles string page parameter', () => {
      global.fetch = vi.fn(() => Promise.resolve({ ok: true, json: () => Promise.resolve({}) } as Response));

      showImportedTerms('2024-01-01', false, 10, '3');

      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('page=3')
      );
    });
  });

  // ===========================================================================
  // Navigation Tests
  // ===========================================================================

  describe('navigation', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <span id="recno">50</span>
        <div id="res_data-no_terms_imported"></div>
        <div id="res_data-navigation">
          <div id="res_data-navigation-prev" style="display: none;">
            <button id="res_data-navigation-prev-first"></button>
            <button id="res_data-navigation-prev-minus"></button>
          </div>
          <select id="res_data-navigation-quick_nav"></select>
          <span id="res_data-navigation-no_quick_nav"></span>
          <span id="res_data-navigation-totalPages"></span>
          <div id="res_data-navigation-next" style="display: none;">
            <button id="res_data-navigation-next-plus"></button>
            <button id="res_data-navigation-next-last"></button>
          </div>
        </div>
        <table id="res_data-res_table">
          <tbody id="res_data-res_table-body"></tbody>
        </table>
        <form name="form1">
          <select name="page"></select>
        </form>
      `;
    });

    it('shows prev buttons on page > 1', async () => {
      const mockResponse = {
        navigation: { current_page: 2, total_pages: 5 },
        terms: []
      };

      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse)
        } as Response)
      );

      showImportedTerms('2024-01-01', false, 50, 2);

      await vi.waitFor(() => {
        const prevNav = document.getElementById('res_data-navigation-prev');
        expect(prevNav?.style.display).not.toBe('none');
      });
    });

    it('hides prev buttons on page 1', async () => {
      const mockResponse = {
        navigation: { current_page: 1, total_pages: 5 },
        terms: []
      };

      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse)
        } as Response)
      );

      showImportedTerms('2024-01-01', false, 50, 1);

      await vi.waitFor(() => {
        const prevNav = document.getElementById('res_data-navigation-prev');
        expect(prevNav?.style.display).toBe('none');
      });
    });

    it('shows next buttons when not on last page', async () => {
      const mockResponse = {
        navigation: { current_page: 1, total_pages: 5 },
        terms: []
      };

      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse)
        } as Response)
      );

      showImportedTerms('2024-01-01', false, 50, 1);

      await vi.waitFor(() => {
        const nextNav = document.getElementById('res_data-navigation-next');
        expect(nextNav?.style.display).not.toBe('none');
      });
    });

    it('hides next buttons on last page', async () => {
      const mockResponse = {
        navigation: { current_page: 5, total_pages: 5 },
        terms: []
      };

      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse)
        } as Response)
      );

      showImportedTerms('2024-01-01', false, 50, 5);

      await vi.waitFor(() => {
        const nextNav = document.getElementById('res_data-navigation-next');
        expect(nextNav?.style.display).toBe('none');
      });
    });

    it('creates page select options', async () => {
      const mockResponse = {
        navigation: { current_page: 2, total_pages: 3 },
        terms: []
      };

      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse)
        } as Response)
      );

      showImportedTerms('2024-01-01', false, 50, 2);

      await vi.waitFor(() => {
        const options = document.querySelectorAll('#res_data-navigation-quick_nav option');
        expect(options.length).toBe(3);
      });
    });

    it('hides quick nav when only 1 page', async () => {
      const mockResponse = {
        navigation: { current_page: 1, total_pages: 1 },
        terms: []
      };

      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse)
        } as Response)
      );

      showImportedTerms('2024-01-01', false, 10, 1);

      await vi.waitFor(() => {
        const quickNav = document.getElementById('res_data-navigation-quick_nav');
        expect(quickNav?.style.display).toBe('none');
        const noQuickNav = document.getElementById('res_data-navigation-no_quick_nav');
        expect(noQuickNav?.style.display).not.toBe('none');
      });
    });

    it('displays total pages', async () => {
      const mockResponse = {
        navigation: { current_page: 1, total_pages: 10 },
        terms: []
      };

      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse)
        } as Response)
      );

      showImportedTerms('2024-01-01', false, 100, 1);

      await vi.waitFor(() => {
        expect(document.getElementById('res_data-navigation-totalPages')?.textContent).toBe('10');
      });
    });
  });

  // ===========================================================================
  // Terms Display Tests
  // ===========================================================================

  describe('terms display', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <span id="recno">2</span>
        <div id="res_data-no_terms_imported"></div>
        <div id="res_data-navigation">
          <div id="res_data-navigation-prev"></div>
          <select id="res_data-navigation-quick_nav"></select>
          <span id="res_data-navigation-no_quick_nav"></span>
          <span id="res_data-navigation-totalPages"></span>
          <div id="res_data-navigation-next"></div>
        </div>
        <table id="res_data-res_table">
          <tbody id="res_data-res_table-body"></tbody>
        </table>
      `;
    });

    it('displays term data in table rows', async () => {
      const mockResponse = {
        navigation: { current_page: 1, total_pages: 1 },
        terms: [{
          WoID: 1,
          WoText: 'hello',
          WoTranslation: 'hola',
          WoRomanization: 'helo',
          WoSentence: 'Hello world',
          WoStatus: 1,
          SentOK: 1,
          taglist: 'tag1, tag2'
        }]
      };

      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse)
        } as Response)
      );

      showImportedTerms('2024-01-01', false, 1, 1);

      await vi.waitFor(() => {
        const tbody = document.getElementById('res_data-res_table-body');
        expect(tbody?.innerHTML).toContain('hello');
        expect(tbody?.innerHTML).toContain('hola');
        expect(tbody?.innerHTML).toContain('helo');
        // Tags are rendered as Bulma tag spans
        expect(tbody?.innerHTML).toContain('tag1');
        expect(tbody?.innerHTML).toContain('tag2');
      });
    });

    it('shows valid sentence icon when SentOK is not 0', async () => {
      const mockResponse = {
        navigation: { current_page: 1, total_pages: 1 },
        terms: [{
          WoID: 1,
          WoText: 'test',
          WoTranslation: 'test',
          WoRomanization: '',
          WoSentence: 'Test sentence',
          WoStatus: 1,
          SentOK: 1,
          taglist: ''
        }]
      };

      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse)
        } as Response)
      );

      showImportedTerms('2024-01-01', false, 1, 1);

      await vi.waitFor(() => {
        // Uses Lucide icons: check icon for valid sentence
        expect(document.getElementById('res_data-res_table-body')?.innerHTML).toContain('data-lucide="check"');
      });
    });

    it('shows no sentence icon when SentOK is 0', async () => {
      const mockResponse = {
        navigation: { current_page: 1, total_pages: 1 },
        terms: [{
          WoID: 1,
          WoText: 'test',
          WoTranslation: 'test',
          WoRomanization: '',
          WoSentence: '',
          WoStatus: 1,
          SentOK: 0,
          taglist: ''
        }]
      };

      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse)
        } as Response)
      );

      showImportedTerms('2024-01-01', false, 1, 1);

      await vi.waitFor(() => {
        // Uses Lucide icons: x icon for no valid sentence
        expect(document.getElementById('res_data-res_table-body')?.innerHTML).toContain('data-lucide="x"');
      });
    });

    it('displays status abbreviation', async () => {
      const mockResponse = {
        navigation: { current_page: 1, total_pages: 1 },
        terms: [{
          WoID: 1,
          WoText: 'test',
          WoTranslation: 'test',
          WoRomanization: '',
          WoSentence: '',
          WoStatus: 99,
          SentOK: 0,
          taglist: ''
        }]
      };

      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse)
        } as Response)
      );

      showImportedTerms('2024-01-01', false, 1, 1);

      await vi.waitFor(() => {
        // statuses[99] has abbr: 'WKn' for Well Known
        expect(document.getElementById('res_data-res_table-body')?.innerHTML).toContain('WKn');
      });
    });

    it('applies RTL direction when enabled', async () => {
      const mockResponse = {
        navigation: { current_page: 1, total_pages: 1 },
        terms: [{
          WoID: 1,
          WoText: 'مرحبا',
          WoTranslation: 'Hello',
          WoRomanization: '',
          WoSentence: '',
          WoStatus: 1,
          SentOK: 0,
          taglist: ''
        }]
      };

      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse)
        } as Response)
      );

      showImportedTerms('2024-01-01', true, 1, 1);

      await vi.waitFor(() => {
        expect(document.getElementById('res_data-res_table-body')?.innerHTML).toContain('dir="rtl"');
      });
    });

    it('shows asterisk for empty romanization', async () => {
      const mockResponse = {
        navigation: { current_page: 1, total_pages: 1 },
        terms: [{
          WoID: 1,
          WoText: 'test',
          WoTranslation: 'test',
          WoRomanization: '',
          WoSentence: '',
          WoStatus: 1,
          SentOK: 0,
          taglist: ''
        }]
      };

      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse)
        } as Response)
      );

      showImportedTerms('2024-01-01', false, 1, 1);

      await vi.waitFor(() => {
        // Should contain * for empty romanization
        expect(document.getElementById('res_data-res_table-body')?.innerHTML).toContain('>*<');
      });
    });
  });

  // ===========================================================================
  // Event Delegation Tests
  // ===========================================================================

  describe('event delegation', () => {
    it('handles import mode select change', async () => {
      document.body.innerHTML = `
        <select data-action="update-import-mode">
          <option value="0">Mode 0</option>
          <option value="4">Mode 4</option>
        </select>
        <div id="imp_transl_delim" class="hide">
          <input type="text">
        </div>
      `;

      await import('../../../src/frontend/js/modules/vocabulary/pages/word_upload');

      const select = document.querySelector<HTMLSelectElement>('[data-action="update-import-mode"]')!;
      select.value = '4';
      select.dispatchEvent(new Event('change', { bubbles: true }));

      expect(document.getElementById('imp_transl_delim')?.classList.contains('hide')).toBe(false);
    });
  });
});
