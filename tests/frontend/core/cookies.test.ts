/**
 * Tests for core/cookies.ts - Cookie utility functions
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  getCookie,
  setCookie,
  deleteCookie,
  areCookiesEnabled
} from '../../../src/frontend/js/core/cookies';

describe('core/cookies.ts', () => {
  beforeEach(() => {
    // Clear all cookies before each test
    document.cookie.split(';').forEach(cookie => {
      const name = cookie.split('=')[0].trim();
      document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`;
    });
    vi.clearAllMocks();
  });

  afterEach(() => {
    // Clean up cookies after each test
    document.cookie.split(';').forEach(cookie => {
      const name = cookie.split('=')[0].trim();
      document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`;
    });
    vi.restoreAllMocks();
  });

  // ===========================================================================
  // getCookie Tests
  // ===========================================================================

  describe('getCookie', () => {
    it('returns null for non-existent cookie', () => {
      const result = getCookie('nonexistent');

      expect(result).toBeNull();
    });

    it('returns value for existing cookie', () => {
      document.cookie = 'test_cookie=test_value';

      const result = getCookie('test_cookie');

      expect(result).toBe('test_value');
    });

    it('returns empty string for cookie with empty value', () => {
      document.cookie = 'empty_cookie=';

      const result = getCookie('empty_cookie');

      expect(result).toBe('');
    });

    it('handles cookies with spaces around name', () => {
      document.cookie = '  spaced_cookie  =spaced_value';

      const result = getCookie('spaced_cookie');

      expect(result).toBe('spaced_value');
    });

    it('handles URL-encoded cookie values', () => {
      document.cookie = 'encoded_cookie=' + encodeURIComponent('hello world');

      const result = getCookie('encoded_cookie');

      expect(result).toBe('hello world');
    });

    it('handles special characters in cookie value', () => {
      const specialValue = 'hello=world&foo=bar';
      document.cookie = 'special_cookie=' + encodeURIComponent(specialValue);

      const result = getCookie('special_cookie');

      expect(result).toBe(specialValue);
    });

    it('returns correct cookie when multiple cookies exist', () => {
      document.cookie = 'first=1';
      document.cookie = 'second=2';
      document.cookie = 'third=3';

      expect(getCookie('first')).toBe('1');
      expect(getCookie('second')).toBe('2');
      expect(getCookie('third')).toBe('3');
    });

    it('does not match partial cookie names', () => {
      document.cookie = 'my_long_cookie_name=value';

      const result = getCookie('my_long_cookie');

      expect(result).toBeNull();
    });
  });

  // ===========================================================================
  // setCookie Tests
  // ===========================================================================

  describe('setCookie', () => {
    it('sets a basic cookie', () => {
      setCookie('basic', 'value', 0, '', '', false);

      expect(getCookie('basic')).toBe('value');
    });

    it('sets cookie with expiration', () => {
      setCookie('expiring', 'value', 7, '/', '', false);

      expect(getCookie('expiring')).toBe('value');
    });

    it('sets cookie with path', () => {
      // In jsdom, setting a cookie with a non-matching path may not be visible
      // Test that the function doesn't throw when path is provided
      expect(() => setCookie('pathed', 'value', 0, '/test', '', false)).not.toThrow();
    });

    it('encodes special characters in value', () => {
      const specialValue = 'hello world & more';
      setCookie('special', specialValue, 0, '', '', false);

      expect(getCookie('special')).toBe(specialValue);
    });

    it('handles empty value', () => {
      setCookie('empty', '', 0, '', '', false);

      expect(getCookie('empty')).toBe('');
    });

    it('overwrites existing cookie', () => {
      setCookie('overwrite', 'original', 0, '', '', false);
      setCookie('overwrite', 'updated', 0, '', '', false);

      expect(getCookie('overwrite')).toBe('updated');
    });

    it('handles unicode characters', () => {
      const unicodeValue = '日本語テスト';
      setCookie('unicode', unicodeValue, 0, '', '', false);

      expect(getCookie('unicode')).toBe(unicodeValue);
    });

    it('sets cookie without expiration when expires is 0', () => {
      setCookie('session', 'value', 0, '', '', false);

      // Session cookie should be set
      expect(getCookie('session')).toBe('value');
    });
  });

  // ===========================================================================
  // deleteCookie Tests
  // ===========================================================================

  describe('deleteCookie', () => {
    it('deletes an existing cookie', () => {
      document.cookie = 'to_delete=value';
      expect(getCookie('to_delete')).toBe('value');

      deleteCookie('to_delete', '/', '');

      expect(getCookie('to_delete')).toBeNull();
    });

    it('does nothing for non-existent cookie', () => {
      // Should not throw
      expect(() => deleteCookie('nonexistent', '/', '')).not.toThrow();
    });

    it('only deletes specified cookie', () => {
      document.cookie = 'keep=value1';
      document.cookie = 'delete=value2';

      deleteCookie('delete', '/', '');

      expect(getCookie('keep')).toBe('value1');
      expect(getCookie('delete')).toBeNull();
    });
  });

  // ===========================================================================
  // areCookiesEnabled Tests
  // ===========================================================================

  describe('areCookiesEnabled', () => {
    it('returns true when cookies are enabled', () => {
      const result = areCookiesEnabled();

      expect(result).toBe(true);
    });

    it('cleans up test cookie after checking', () => {
      areCookiesEnabled();

      expect(getCookie('test')).toBeNull();
    });
  });

  // ===========================================================================
  // Integration Tests
  // ===========================================================================

  describe('Integration', () => {
    it('set then get returns same value', () => {
      const testValue = 'integration_test_value';
      setCookie('integration', testValue, 1, '/', '', false);

      const result = getCookie('integration');

      expect(result).toBe(testValue);
    });

    it('set, delete, get returns null', () => {
      setCookie('lifecycle', 'value', 1, '/', '', false);
      expect(getCookie('lifecycle')).toBe('value');

      deleteCookie('lifecycle', '/', '');

      expect(getCookie('lifecycle')).toBeNull();
    });

    it('handles multiple cookies with similar names', () => {
      setCookie('prefix', 'prefix_value', 0, '', '', false);
      setCookie('prefix_extended', 'extended_value', 0, '', '', false);
      setCookie('other_prefix', 'other_value', 0, '', '', false);

      expect(getCookie('prefix')).toBe('prefix_value');
      expect(getCookie('prefix_extended')).toBe('extended_value');
      expect(getCookie('other_prefix')).toBe('other_value');
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles very long cookie values', () => {
      const longValue = 'x'.repeat(4000);
      setCookie('long', longValue, 0, '', '', false);

      expect(getCookie('long')).toBe(longValue);
    });

    it('handles cookie names with underscores', () => {
      setCookie('my_long_cookie_name', 'value', 0, '', '', false);

      expect(getCookie('my_long_cookie_name')).toBe('value');
    });

    it('handles cookie names with numbers', () => {
      setCookie('cookie123', 'value', 0, '', '', false);

      expect(getCookie('cookie123')).toBe('value');
    });

    it('handles equals sign in cookie value', () => {
      const valueWithEquals = 'key=value';
      setCookie('equals', valueWithEquals, 0, '', '', false);

      expect(getCookie('equals')).toBe(valueWithEquals);
    });

    it('handles semicolon in cookie value (encoded)', () => {
      const valueWithSemicolon = 'hello;world';
      setCookie('semicolon', valueWithSemicolon, 0, '', '', false);

      expect(getCookie('semicolon')).toBe(valueWithSemicolon);
    });
  });

  // ===========================================================================
  // Global Window Exports
  // ===========================================================================

  describe('Window Exports', () => {
    it('exposes getCookie on window', () => {
      expect(window.getCookie).toBeDefined();
      expect(typeof window.getCookie).toBe('function');
    });

    it('exposes setCookie on window', () => {
      expect(window.setCookie).toBeDefined();
      expect(typeof window.setCookie).toBe('function');
    });

    it('exposes deleteCookie on window', () => {
      expect(window.deleteCookie).toBeDefined();
      expect(typeof window.deleteCookie).toBe('function');
    });

    it('exposes areCookiesEnabled on window', () => {
      expect(window.areCookiesEnabled).toBeDefined();
      expect(typeof window.areCookiesEnabled).toBe('function');
    });
  });
});
