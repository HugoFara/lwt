/**
 * Tests for core utility functions
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  getStatusName,
  getStatusAbbr,
  make_tooltip,
} from '../../../src/frontend/js/terms/word_status';
import {
  getLangFromDict,
  createTheDictUrl,
  createTheDictLink,
  createSentLookupLink,
  owin,
  oewin,
} from '../../../src/frontend/js/terms/dictionary';
import {
  escape_html_chars,
  escape_html_chars_2,
  escape_apostrophes,
} from '../../../src/frontend/js/core/html_utils';
import {
  getCookie,
  setCookie,
  deleteCookie,
  areCookiesEnabled,
} from '../../../src/frontend/js/core/cookies';
import {
  check_table_prefix,
} from '../../../src/frontend/js/core/language_settings';

// Note: STATUSES is now hardcoded in app_data.ts, no need to mock
describe('pgm.ts', () => {
  beforeEach(() => {
    // No mocking needed - STATUSES is now directly imported from app_data.ts
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ===========================================================================
  // Status Functions Tests
  // ===========================================================================

  describe('getStatusName', () => {
    it('returns correct status name for valid status', () => {
      // Statuses 1-4 are "Learning", 5 is "Learned"
      expect(getStatusName(1)).toBe('Learning');
      expect(getStatusName('1')).toBe('Learning');
      expect(getStatusName(5)).toBe('Learned');
      expect(getStatusName(98)).toBe('Ignored');
      expect(getStatusName(99)).toBe('Well Known');
    });

    it('returns Unknown for invalid status', () => {
      expect(getStatusName(100)).toBe('Unknown');
      expect(getStatusName('invalid')).toBe('Unknown');
    });

    it('handles string status numbers', () => {
      expect(getStatusName('2')).toBe('Learning');
      expect(getStatusName('98')).toBe('Ignored');
    });
  });

  describe('getStatusAbbr', () => {
    it('returns correct abbreviation for valid status', () => {
      expect(getStatusAbbr(1)).toBe('1');
      expect(getStatusAbbr(98)).toBe('Ign');
      expect(getStatusAbbr(99)).toBe('WKn');
    });

    it('returns ? for invalid status', () => {
      expect(getStatusAbbr(100)).toBe('?');
      expect(getStatusAbbr('invalid')).toBe('?');
    });
  });

  // ===========================================================================
  // URL and Dictionary Functions Tests
  // ===========================================================================

  describe('getLangFromDict', () => {
    it('returns empty string for empty URL', () => {
      expect(getLangFromDict('')).toBe('');
      expect(getLangFromDict('   ')).toBe('');
    });

    it('handles Google Translate URL', () => {
      // Asterisk prefix is no longer used - popup is stored in database
      const url = 'http://translate.google.com?sl=en&tl=fr';
      expect(getLangFromDict(url)).toBe('en');
    });

    it('extracts source language from Google Translate URL', () => {
      const url = 'http://translate.google.com?sl=de&tl=en';
      expect(getLangFromDict(url)).toBe('de');
    });

    it('extracts source language from LibreTranslate URL', () => {
      const url = 'http://localhost:5000?lwt_translator=libretranslate&source=es&target=en';
      expect(getLangFromDict(url)).toBe('es');
    });

    it('returns empty for invalid relative URLs', () => {
      // Old trans.php and ggl.php gateway URLs are no longer supported
      // The function now requires valid absolute URLs
      const url1 = 'trans.php?sl=ja&tl=en';
      expect(getLangFromDict(url1)).toBe('');

      const url2 = 'ggl.php?sl=ko&tl=en';
      expect(getLangFromDict(url2)).toBe('');
    });

    it('returns empty string when no language param found', () => {
      const url = 'http://example.com?foo=bar';
      expect(getLangFromDict(url)).toBe('');
    });
  });

  describe('createTheDictUrl', () => {
    it('appends term when no placeholder in URL', () => {
      const result = createTheDictUrl('http://dict.com/search?q=', 'hello');
      expect(result).toBe('http://dict.com/search?q=hello');
    });

    it('replaces lwt_term placeholder', () => {
      const result = createTheDictUrl('http://dict.com/search?q=lwt_term', 'world');
      expect(result).toBe('http://dict.com/search?q=world');
    });

    it('replaces lwt_term placeholder', () => {
      const result = createTheDictUrl('http://dict.com/search?q=lwt_term', 'test');
      expect(result).toBe('http://dict.com/search?q=test');
    });

    it('encodes special characters', () => {
      const result = createTheDictUrl('http://dict.com/search?q=', 'hello world');
      expect(result).toBe('http://dict.com/search?q=hello%20world');
    });

    it('handles empty term', () => {
      const result = createTheDictUrl('http://dict.com/search?q=lwt_term', '');
      expect(result).toBe('http://dict.com/search?q=+');
    });

    it('trims URL and term', () => {
      const result = createTheDictUrl('  http://dict.com/  ', '  test  ');
      expect(result).toBe('http://dict.com/test');
    });

    it('handles URL with lwt_term in path', () => {
      // Simple replacement of lwt_term placeholder
      const result = createTheDictUrl('http://dict.com/search/lwt_term/details', 'test');
      expect(result).toBe('http://dict.com/search/test/details');
    });
  });

  describe('createTheDictLink', () => {
    it('returns empty string for empty URL', () => {
      expect(createTheDictLink('', 'word', 'Link', '')).toBe('');
    });

    it('returns empty string for empty text', () => {
      expect(createTheDictLink('http://dict.com', 'word', '', '')).toBe('');
    });

    it('creates regular link for normal URL', () => {
      const result = createTheDictLink('http://dict.com?q=lwt_term', 'hello', 'Translate', '');
      expect(result).toContain('<a href=');
      expect(result).toContain('Translate');
      expect(result).toContain('target="ru"');
    });

    it('creates popup span when popup=true', () => {
      // Popup is now determined by boolean parameter, not URL prefix
      const result = createTheDictLink('http://dict.com?q=lwt_term', 'hello', 'Translate', '', true);
      expect(result).toContain('<span class="click"');
      expect(result).toContain('data-action="dict-popup"');
      expect(result).toContain('data-url=');
    });

    it('creates regular link when popup=false', () => {
      const result = createTheDictLink('http://dict.com?q=lwt_term', 'hello', 'Translate', '', false);
      expect(result).toContain('<a href=');
      expect(result).toContain('target="ru"');
    });

    it('includes txtbefore content', () => {
      const result = createTheDictLink('http://dict.com', 'word', 'Link', 'Before:');
      expect(result).toContain('Before:');
    });
  });

  describe('createSentLookupLink', () => {
    it('returns empty string for empty URL', () => {
      expect(createSentLookupLink(1, 1, '', 'Translate')).toBe('');
    });

    it('returns empty string for empty text', () => {
      expect(createSentLookupLink(1, 1, 'http://trans.com', '')).toBe('');
    });

    it('creates popup span when popup=true', () => {
      // Popup is now determined by boolean parameter, not URL prefix
      const result = createSentLookupLink(10, 5, 'http://trans.com', 'Translate', true);
      expect(result).toContain('<span class="click"');
      expect(result).toContain('http://trans.com');
    });

    it('creates regular link for external URL', () => {
      const result = createSentLookupLink(10, 5, 'http://trans.com', 'Translate');
      // Now uses the translator URL directly instead of trans.php
      expect(result).toContain('<a href="http://trans.com"');
      expect(result).toContain('target="ru"');
    });
  });

  // ===========================================================================
  // HTML Escape Functions Tests
  // ===========================================================================

  describe('escape_html_chars', () => {
    it('escapes ampersand', () => {
      expect(escape_html_chars('a & b')).toBe('a &amp; b');
    });

    it('escapes less than', () => {
      expect(escape_html_chars('a < b')).toBe('a &lt; b');
    });

    it('escapes greater than', () => {
      expect(escape_html_chars('a > b')).toBe('a &gt; b');
    });

    it('escapes double quotes', () => {
      expect(escape_html_chars('say "hello"')).toBe('say &quot;hello&quot;');
    });

    it('escapes single quotes', () => {
      expect(escape_html_chars("it's")).toBe('it&#039;s');
    });

    it('converts carriage return to br', () => {
      expect(escape_html_chars('line1\x0dline2')).toBe('line1<br />line2');
    });

    it('handles multiple special characters', () => {
      expect(escape_html_chars('<a & "b">')).toBe('&lt;a &amp; &quot;b&quot;&gt;');
    });

    it('handles empty string', () => {
      expect(escape_html_chars('')).toBe('');
    });
  });

  describe('escape_html_chars_2', () => {
    it('escapes HTML and highlights annotation', () => {
      const result = escape_html_chars_2('hello world', 'world');
      expect(result).toContain('<span style="color:red">world</span>');
      expect(result).toContain('hello');
    });

    it('handles empty annotation', () => {
      const result = escape_html_chars_2('hello world', '');
      expect(result).toBe('hello world');
    });

    it('escapes both title and annotation', () => {
      const result = escape_html_chars_2('a & b', '&');
      expect(result).toContain('<span style="color:red">&amp;</span>');
    });
  });

  describe('escape_apostrophes', () => {
    it('escapes single apostrophes', () => {
      expect(escape_apostrophes("it's")).toBe("it\\'s");
    });

    it('escapes multiple apostrophes', () => {
      expect(escape_apostrophes("don't won't")).toBe("don\\'t won\\'t");
    });

    it('handles string without apostrophes', () => {
      expect(escape_apostrophes('hello world')).toBe('hello world');
    });
  });

  // ===========================================================================
  // Tooltip Function Tests
  // ===========================================================================

  describe('make_tooltip', () => {
    it('creates tooltip with word only', () => {
      const result = make_tooltip('hello', '', '', 1);
      expect(result).toContain('hello');
      expect(result).toContain('Learning');
    });

    it('includes romanization when provided', () => {
      const result = make_tooltip('日本語', '', 'nihongo', 1);
      expect(result).toContain('▶ nihongo');
    });

    it('includes translation when provided', () => {
      const result = make_tooltip('bonjour', 'hello', '', 1);
      expect(result).toContain('▶ hello');
    });

    it('excludes translation when it is *', () => {
      const result = make_tooltip('word', '*', '', 1);
      expect(result).not.toContain('▶ *');
    });

    it('includes status name and abbreviation', () => {
      const result = make_tooltip('word', 'trans', '', 98);
      expect(result).toContain('Ignored');
      expect(result).toContain('[Ign]');
    });

    it('handles all fields populated', () => {
      const result = make_tooltip('日本', 'Japan', 'nihon', 5);
      expect(result).toContain('日本');
      expect(result).toContain('▶ nihon');
      expect(result).toContain('▶ Japan');
      expect(result).toContain('Learned');
    });
  });

  // ===========================================================================
  // Window Functions Tests
  // ===========================================================================

  describe('owin', () => {
    it('opens a window with correct parameters', () => {
      const mockOpen = vi.spyOn(window, 'open').mockReturnValue(null);

      owin('http://example.com');

      expect(mockOpen).toHaveBeenCalledWith(
        'http://example.com',
        'dictwin',
        expect.stringContaining('width=800')
      );
    });
  });

  describe('oewin', () => {
    it('opens an edit window with correct parameters', () => {
      const mockOpen = vi.spyOn(window, 'open').mockReturnValue(null);

      oewin('http://example.com/edit');

      expect(mockOpen).toHaveBeenCalledWith(
        'http://example.com/edit',
        'editwin',
        expect.stringContaining('height=600')
      );
    });
  });

  // ===========================================================================
  // Cookie Functions Tests
  // ===========================================================================

  describe('Cookie functions', () => {
    beforeEach(() => {
      // Clear all cookies before each test
      document.cookie.split(';').forEach((c) => {
        document.cookie = c
          .replace(/^ +/, '')
          .replace(/=.*/, '=;expires=Thu, 01 Jan 1970 00:00:00 GMT');
      });
    });

    describe('setCookie', () => {
      it('sets a cookie with basic parameters', () => {
        setCookie('testCookie', 'testValue', 1, '/', '', false);
        expect(document.cookie).toContain('testCookie=testValue');
      });

      it('encodes special characters', () => {
        setCookie('test', 'hello world', 1, '/', '', false);
        expect(document.cookie).toContain('test=hello%20world');
      });
    });

    describe('getCookie', () => {
      it('retrieves existing cookie', () => {
        document.cookie = 'myCookie=myValue';
        expect(getCookie('myCookie')).toBe('myValue');
      });

      it('returns null for non-existent cookie', () => {
        expect(getCookie('nonExistent')).toBeNull();
      });

      it('handles URL-encoded values', () => {
        document.cookie = 'encoded=hello%20world';
        expect(getCookie('encoded')).toBe('hello world');
      });

      it('handles cookies with empty values', () => {
        document.cookie = 'empty=';
        expect(getCookie('empty')).toBe('');
      });
    });

    describe('deleteCookie', () => {
      it('deletes an existing cookie', () => {
        document.cookie = 'toDelete=value';
        deleteCookie('toDelete', '/', '');
        expect(getCookie('toDelete')).toBeNull();
      });

      it('handles deleting non-existent cookie', () => {
        // Should not throw
        expect(() => deleteCookie('nonExistent', '/', '')).not.toThrow();
      });
    });

    describe('areCookiesEnabled', () => {
      it('returns true when cookies are enabled', () => {
        expect(areCookiesEnabled()).toBe(true);
      });
    });
  });

  // ===========================================================================
  // Validation Functions Tests
  // ===========================================================================

  describe('check_table_prefix', () => {
    let alertSpy: ReturnType<typeof vi.spyOn>;

    beforeEach(() => {
      alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});
    });

    it('returns true for valid alphanumeric prefix', () => {
      expect(check_table_prefix('myprefix')).toBe(true);
      expect(alertSpy).not.toHaveBeenCalled();
    });

    it('returns true for prefix with underscores', () => {
      expect(check_table_prefix('my_prefix_1')).toBe(true);
    });

    it('returns true for prefix with numbers', () => {
      expect(check_table_prefix('prefix123')).toBe(true);
    });

    it('returns false for empty prefix', () => {
      expect(check_table_prefix('')).toBe(false);
      expect(alertSpy).toHaveBeenCalled();
    });

    it('returns false for prefix longer than 20 characters', () => {
      expect(check_table_prefix('a'.repeat(21))).toBe(false);
      expect(alertSpy).toHaveBeenCalled();
    });

    it('returns false for prefix with special characters', () => {
      expect(check_table_prefix('prefix!')).toBe(false);
      expect(check_table_prefix('prefix-name')).toBe(false);
      expect(check_table_prefix('prefix.name')).toBe(false);
    });

    it('returns true for single character prefix', () => {
      expect(check_table_prefix('a')).toBe(true);
    });

    it('returns true for exactly 20 character prefix', () => {
      expect(check_table_prefix('a'.repeat(20))).toBe(true);
    });
  });
});
