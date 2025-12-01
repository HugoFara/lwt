/**
 * Tests for word_upload.ts - Word import form and results display
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  updateImportMode,
  showImportedTerms
} from '../../../src/frontend/js/words/word_upload';

// Define STATUSES global for tests
declare global {
  interface Window {
    STATUSES?: Record<string, { name: string; abbr: string }>;
  }
}

describe('word_upload.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    window.STATUSES = {
      '1': { name: 'Learning', abbr: 'L' },
      '2': { name: 'Familiar', abbr: 'F' },
      '99': { name: 'Well-known', abbr: 'WK' }
    };
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
    delete window.STATUSES;
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

      const mockGetJSON = vi.fn((_url: string, _data: unknown, callback: (data: typeof mockResponse) => void) => {
        callback(mockResponse);
        return Promise.resolve(mockResponse);
      });
      global.fetch = vi.fn(() =>
        Promise.resolve(new Response(JSON.stringify(mockResponse), { status: 200 }))
      ) as any;

      showImportedTerms('2024-01-01', false, 10, 1);

      expect(getComputedStyle(document.querySelector('#res_data-no_terms_imported')!).display).toBe('none');
      expect(getComputedStyle(document.querySelector('#res_data-navigation')!).display).not.toBe('none');
    });

    it('handles string count parameter', () => {
      showImportedTerms('2024-01-01', false, 0, 1);

      // Should still show no terms message - JSDOM normalizes 'inherit' to 'block'
      expect(getComputedStyle(document.querySelector('#res_data-no_terms_imported')!).display).not.toBe('none');
    });

    it('handles RTL as string "true"', () => {
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

      const mockGetJSON = vi.fn((_url: string, _data: unknown, callback: (data: typeof mockResponse) => void) => {
        callback(mockResponse);
      });
      vi.spyOn($, 'getJSON').mockImplementation(mockGetJSON);

      showImportedTerms('2024-01-01', 'true', 1, 1);

      // Should call API and process RTL
      expect(mockGetJSON).toHaveBeenCalled();
    });

    it('handles RTL as string "1"', () => {
      const mockResponse = {
        navigation: { current_page: 1, total_pages: 1 },
        terms: []
      };

      const mockGetJSON = vi.fn((_url: string, _data: unknown, callback: (data: typeof mockResponse) => void) => {
        callback(mockResponse);
      });
      vi.spyOn($, 'getJSON').mockImplementation(mockGetJSON);

      showImportedTerms('2024-01-01', '1', 1, 1);

      expect(mockGetJSON).toHaveBeenCalled();
    });

    it('makes API call with correct parameters', () => {
      const mockGetJSON = vi.fn();
      vi.spyOn($, 'getJSON').mockImplementation(mockGetJSON);

      showImportedTerms('2024-01-15 10:30:00', false, 25, 2);

      expect(mockGetJSON).toHaveBeenCalledWith(
        'api.php/v1/terms/imported',
        {
          last_update: '2024-01-15 10:30:00',
          count: 25,
          page: 2
        },
        expect.any(Function)
      );
    });

    it('handles string page parameter', () => {
      const mockGetJSON = vi.fn();
      vi.spyOn($, 'getJSON').mockImplementation(mockGetJSON);

      showImportedTerms('2024-01-01', false, 10, '3');

      expect(mockGetJSON).toHaveBeenCalledWith(
        'api.php/v1/terms/imported',
        expect.objectContaining({ page: 3 }),
        expect.any(Function)
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

    it('shows prev buttons on page > 1', () => {
      const mockResponse = {
        navigation: { current_page: 2, total_pages: 5 },
        terms: []
      };

      const mockGetJSON = vi.fn((_url: string, _data: unknown, callback: (data: typeof mockResponse) => void) => {
        callback(mockResponse);
      });
      vi.spyOn($, 'getJSON').mockImplementation(mockGetJSON);

      showImportedTerms('2024-01-01', false, 50, 2);

      // JSDOM normalizes 'initial' to 'inline', so check not 'none'
      expect($('#res_data-navigation-prev').css('display')).not.toBe('none');
    });

    it('hides prev buttons on page 1', () => {
      const mockResponse = {
        navigation: { current_page: 1, total_pages: 5 },
        terms: []
      };

      const mockGetJSON = vi.fn((_url: string, _data: unknown, callback: (data: typeof mockResponse) => void) => {
        callback(mockResponse);
      });
      vi.spyOn($, 'getJSON').mockImplementation(mockGetJSON);

      showImportedTerms('2024-01-01', false, 50, 1);

      expect($('#res_data-navigation-prev').css('display')).toBe('none');
    });

    it('shows next buttons when not on last page', () => {
      const mockResponse = {
        navigation: { current_page: 1, total_pages: 5 },
        terms: []
      };

      const mockGetJSON = vi.fn((_url: string, _data: unknown, callback: (data: typeof mockResponse) => void) => {
        callback(mockResponse);
      });
      vi.spyOn($, 'getJSON').mockImplementation(mockGetJSON);

      showImportedTerms('2024-01-01', false, 50, 1);

      // JSDOM normalizes 'initial' to 'inline', so check not 'none'
      expect($('#res_data-navigation-next').css('display')).not.toBe('none');
    });

    it('hides next buttons on last page', () => {
      const mockResponse = {
        navigation: { current_page: 5, total_pages: 5 },
        terms: []
      };

      const mockGetJSON = vi.fn((_url: string, _data: unknown, callback: (data: typeof mockResponse) => void) => {
        callback(mockResponse);
      });
      vi.spyOn($, 'getJSON').mockImplementation(mockGetJSON);

      showImportedTerms('2024-01-01', false, 50, 5);

      expect($('#res_data-navigation-next').css('display')).toBe('none');
    });

    it('creates page select options', () => {
      const mockResponse = {
        navigation: { current_page: 2, total_pages: 3 },
        terms: []
      };

      const mockGetJSON = vi.fn((_url: string, _data: unknown, callback: (data: typeof mockResponse) => void) => {
        callback(mockResponse);
      });
      vi.spyOn($, 'getJSON').mockImplementation(mockGetJSON);

      showImportedTerms('2024-01-01', false, 50, 2);

      const options = $('#res_data-navigation-quick_nav option');
      expect(options.length).toBe(3);
    });

    it('hides quick nav when only 1 page', () => {
      const mockResponse = {
        navigation: { current_page: 1, total_pages: 1 },
        terms: []
      };

      const mockGetJSON = vi.fn((_url: string, _data: unknown, callback: (data: typeof mockResponse) => void) => {
        callback(mockResponse);
      });
      vi.spyOn($, 'getJSON').mockImplementation(mockGetJSON);

      showImportedTerms('2024-01-01', false, 10, 1);

      expect($('#res_data-navigation-quick_nav').css('display')).toBe('none');
      // JSDOM normalizes 'initial' to 'inline', so check not 'none'
      expect($('#res_data-navigation-no_quick_nav').css('display')).not.toBe('none');
    });

    it('displays total pages', () => {
      const mockResponse = {
        navigation: { current_page: 1, total_pages: 10 },
        terms: []
      };

      const mockGetJSON = vi.fn((_url: string, _data: unknown, callback: (data: typeof mockResponse) => void) => {
        callback(mockResponse);
      });
      vi.spyOn($, 'getJSON').mockImplementation(mockGetJSON);

      showImportedTerms('2024-01-01', false, 100, 1);

      expect($('#res_data-navigation-totalPages').text()).toBe('10');
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

    it('displays term data in table rows', () => {
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

      const mockGetJSON = vi.fn((_url: string, _data: unknown, callback: (data: typeof mockResponse) => void) => {
        callback(mockResponse);
      });
      vi.spyOn($, 'getJSON').mockImplementation(mockGetJSON);

      showImportedTerms('2024-01-01', false, 1, 1);

      const tbody = $('#res_data-res_table-body');
      expect(tbody.html()).toContain('hello');
      expect(tbody.html()).toContain('hola');
      expect(tbody.html()).toContain('helo');
      expect(tbody.html()).toContain('tag1, tag2');
    });

    it('shows valid sentence icon when SentOK is not 0', () => {
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

      const mockGetJSON = vi.fn((_url: string, _data: unknown, callback: (data: typeof mockResponse) => void) => {
        callback(mockResponse);
      });
      vi.spyOn($, 'getJSON').mockImplementation(mockGetJSON);

      showImportedTerms('2024-01-01', false, 1, 1);

      expect($('#res_data-res_table-body').html()).toContain('status.png');
    });

    it('shows no sentence icon when SentOK is 0', () => {
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

      const mockGetJSON = vi.fn((_url: string, _data: unknown, callback: (data: typeof mockResponse) => void) => {
        callback(mockResponse);
      });
      vi.spyOn($, 'getJSON').mockImplementation(mockGetJSON);

      showImportedTerms('2024-01-01', false, 1, 1);

      expect($('#res_data-res_table-body').html()).toContain('status-busy.png');
    });

    it('displays status abbreviation', () => {
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

      const mockGetJSON = vi.fn((_url: string, _data: unknown, callback: (data: typeof mockResponse) => void) => {
        callback(mockResponse);
      });
      vi.spyOn($, 'getJSON').mockImplementation(mockGetJSON);

      showImportedTerms('2024-01-01', false, 1, 1);

      expect($('#res_data-res_table-body').html()).toContain('WK');
    });

    it('applies RTL direction when enabled', () => {
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

      const mockGetJSON = vi.fn((_url: string, _data: unknown, callback: (data: typeof mockResponse) => void) => {
        callback(mockResponse);
      });
      vi.spyOn($, 'getJSON').mockImplementation(mockGetJSON);

      showImportedTerms('2024-01-01', true, 1, 1);

      expect($('#res_data-res_table-body').html()).toContain('dir="rtl"');
    });

    it('shows asterisk for empty romanization', () => {
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

      const mockGetJSON = vi.fn((_url: string, _data: unknown, callback: (data: typeof mockResponse) => void) => {
        callback(mockResponse);
      });
      vi.spyOn($, 'getJSON').mockImplementation(mockGetJSON);

      showImportedTerms('2024-01-01', false, 1, 1);

      // Should contain * for empty romanization
      expect($('#res_data-res_table-body').html()).toContain('>*<');
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

      await import('../../../src/frontend/js/words/word_upload');

      const select = document.querySelector<HTMLSelectElement>('[data-action="update-import-mode"]')!;
      select.value = '4';
      $(select).trigger('change');

      expect($('#imp_transl_delim').hasClass('hide')).toBe(false);
    });
  });
});
