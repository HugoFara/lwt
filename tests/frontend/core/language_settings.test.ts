/**
 * Tests for core/language_settings.ts - Language settings utilities
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Use vi.hoisted to ensure mock function is available during hoisting
const mockLoadModalFrame = vi.hoisted(() => vi.fn());

// Mock dependencies
vi.mock('../../../src/frontend/js/reading/frame_management', () => ({
  loadModalFrame: mockLoadModalFrame
}));

import {
  setLang,
  resetAll,
  iknowall,
  check_table_prefix
} from '../../../src/frontend/js/core/language_settings';

describe('core/language_settings.ts', () => {
  const originalLocation = window.location;

  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();

    // Mock window.location
    delete (window as unknown as { location: Location }).location;
    window.location = {
      ...originalLocation,
      href: 'http://localhost/',
      assign: vi.fn(),
      replace: vi.fn()
    } as unknown as Location;
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
    window.location = originalLocation;
  });

  // ===========================================================================
  // setLang Tests
  // ===========================================================================

  describe('setLang', () => {
    it('redirects to save-setting URL with selected language', () => {
      document.body.innerHTML = `
        <select id="lang-select">
          <option value="1">English</option>
          <option value="2" selected>Spanish</option>
          <option value="3">French</option>
        </select>
      `;
      const select = document.getElementById('lang-select') as HTMLSelectElement;

      setLang(select, '/texts');

      expect(window.location.href).toBe(
        '/admin/save-setting?k=currentlanguage&v=2&u=/texts'
      );
    });

    it('uses first option when selected', () => {
      document.body.innerHTML = `
        <select id="lang-select">
          <option value="1" selected>English</option>
          <option value="2">Spanish</option>
        </select>
      `;
      const select = document.getElementById('lang-select') as HTMLSelectElement;

      setLang(select, '/home');

      expect(window.location.href).toBe(
        '/admin/save-setting?k=currentlanguage&v=1&u=/home'
      );
    });

    it('handles empty value option', () => {
      document.body.innerHTML = `
        <select id="lang-select">
          <option value="" selected>All Languages</option>
          <option value="1">English</option>
        </select>
      `;
      const select = document.getElementById('lang-select') as HTMLSelectElement;

      setLang(select, '/texts');

      expect(window.location.href).toBe(
        '/admin/save-setting?k=currentlanguage&v=&u=/texts'
      );
    });

    it('handles different redirect URLs', () => {
      document.body.innerHTML = `
        <select id="lang-select">
          <option value="5" selected>Japanese</option>
        </select>
      `;
      const select = document.getElementById('lang-select') as HTMLSelectElement;

      setLang(select, '/words/list');

      expect(window.location.href).toContain('u=/words/list');
    });

    it('handles URL with special characters', () => {
      document.body.innerHTML = `
        <select id="lang-select">
          <option value="1" selected>English</option>
        </select>
      `;
      const select = document.getElementById('lang-select') as HTMLSelectElement;

      setLang(select, '/texts?filter=new');

      expect(window.location.href).toContain('u=/texts?filter=new');
    });
  });

  // ===========================================================================
  // resetAll Tests
  // ===========================================================================

  describe('resetAll', () => {
    it('redirects to save-setting with empty language value', () => {
      resetAll('/texts');

      expect(window.location.href).toBe(
        '/admin/save-setting?k=currentlanguage&v=&u=/texts'
      );
    });

    it('uses provided redirect URL', () => {
      resetAll('/home');

      expect(window.location.href).toContain('u=/home');
    });

    it('handles root URL', () => {
      resetAll('/');

      expect(window.location.href).toBe(
        '/admin/save-setting?k=currentlanguage&v=&u=/'
      );
    });

    it('handles complex URL', () => {
      resetAll('/admin/settings?tab=languages');

      expect(window.location.href).toContain('u=/admin/settings?tab=languages');
    });
  });

  // ===========================================================================
  // iknowall Tests
  // ===========================================================================

  describe('iknowall', () => {
    it('shows confirmation dialog', () => {
      const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(true);

      iknowall(1);

      expect(confirmSpy).toHaveBeenCalledWith('Are you sure?');
    });

    it('calls loadModalFrame when confirmed', () => {
      vi.spyOn(window, 'confirm').mockReturnValue(true);

      iknowall(1);

      expect(mockLoadModalFrame).toHaveBeenCalledWith('all_words_wellknown.php?text=1');
    });

    it('does not call loadModalFrame when cancelled', () => {
      vi.spyOn(window, 'confirm').mockReturnValue(false);

      iknowall(1);

      expect(mockLoadModalFrame).not.toHaveBeenCalled();
    });

    it('handles string text ID', () => {
      vi.spyOn(window, 'confirm').mockReturnValue(true);

      iknowall('42');

      expect(mockLoadModalFrame).toHaveBeenCalledWith('all_words_wellknown.php?text=42');
    });

    it('handles numeric text ID', () => {
      vi.spyOn(window, 'confirm').mockReturnValue(true);

      iknowall(123);

      expect(mockLoadModalFrame).toHaveBeenCalledWith('all_words_wellknown.php?text=123');
    });
  });

  // ===========================================================================
  // check_table_prefix Tests
  // ===========================================================================

  describe('check_table_prefix', () => {
    it('returns true for valid alphanumeric prefix', () => {
      const result = check_table_prefix('myprefix');

      expect(result).toBe(true);
    });

    it('returns true for prefix with underscore', () => {
      const result = check_table_prefix('my_prefix');

      expect(result).toBe(true);
    });

    it('returns true for prefix with numbers', () => {
      const result = check_table_prefix('prefix123');

      expect(result).toBe(true);
    });

    it('returns true for single character prefix', () => {
      const result = check_table_prefix('a');

      expect(result).toBe(true);
    });

    it('returns true for 20 character prefix', () => {
      const result = check_table_prefix('a'.repeat(20));

      expect(result).toBe(true);
    });

    it('returns false for empty prefix', () => {
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      const result = check_table_prefix('');

      expect(result).toBe(false);
      expect(alertSpy).toHaveBeenCalled();
    });

    it('returns false for prefix longer than 20 characters', () => {
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      const result = check_table_prefix('a'.repeat(21));

      expect(result).toBe(false);
      expect(alertSpy).toHaveBeenCalled();
    });

    it('returns false for prefix with special characters', () => {
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      const result = check_table_prefix('my-prefix');

      expect(result).toBe(false);
      expect(alertSpy).toHaveBeenCalled();
    });

    it('returns false for prefix with spaces', () => {
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      const result = check_table_prefix('my prefix');

      expect(result).toBe(false);
      expect(alertSpy).toHaveBeenCalled();
    });

    it('returns false for prefix with dots', () => {
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      const result = check_table_prefix('my.prefix');

      expect(result).toBe(false);
      expect(alertSpy).toHaveBeenCalled();
    });

    it('shows error message for invalid prefix', () => {
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      check_table_prefix('invalid!');

      expect(alertSpy).toHaveBeenCalledWith(
        expect.stringContaining('Table Set Name')
      );
    });

    it('allows uppercase letters', () => {
      const result = check_table_prefix('MyPrefix');

      expect(result).toBe(true);
    });

    it('allows mixed case with numbers and underscore', () => {
      const result = check_table_prefix('My_Prefix_123');

      expect(result).toBe(true);
    });

    it('returns false for Unicode characters', () => {
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      const result = check_table_prefix('日本語');

      expect(result).toBe(false);
      expect(alertSpy).toHaveBeenCalled();
    });

    it('returns true for underscore only prefix', () => {
      const result = check_table_prefix('_');

      expect(result).toBe(true);
    });

    it('returns true for prefix starting with underscore', () => {
      const result = check_table_prefix('_myprefix');

      expect(result).toBe(true);
    });

    it('returns true for prefix starting with number', () => {
      const result = check_table_prefix('123prefix');

      expect(result).toBe(true);
    });

    it('does not show alert for valid prefix', () => {
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      check_table_prefix('validprefix');

      expect(alertSpy).not.toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // Event Delegation Tests (if applicable)
  // ===========================================================================

  describe('Event Delegation', () => {
    it('handles data-action="set-lang" on change', async () => {
      document.body.innerHTML = `
        <select data-action="set-lang" data-redirect="/home">
          <option value="1">English</option>
          <option value="2">Spanish</option>
        </select>
      `;

      // Trigger DOMContentLoaded to initialize event delegation
      document.dispatchEvent(new Event('DOMContentLoaded'));

      const select = document.querySelector('select') as HTMLSelectElement;
      select.value = '2';

      // Dispatch change event
      select.dispatchEvent(new Event('change', { bubbles: true }));

      expect(window.location.href).toContain('v=2');
      expect(window.location.href).toContain('u=/home');
    });

    it('uses default redirect when data-redirect is missing', async () => {
      document.body.innerHTML = `
        <select data-action="set-lang">
          <option value="1" selected>English</option>
        </select>
      `;

      document.dispatchEvent(new Event('DOMContentLoaded'));

      const select = document.querySelector('select') as HTMLSelectElement;
      select.dispatchEvent(new Event('change', { bubbles: true }));

      expect(window.location.href).toContain('u=/');
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('setLang handles select with no options', () => {
      document.body.innerHTML = '<select id="empty-select"></select>';
      const select = document.getElementById('empty-select') as HTMLSelectElement;

      // Will throw because selectedIndex returns -1 and options[-1] is undefined
      // This is expected behavior - test documents the behavior
      expect(() => setLang(select, '/test')).toThrow();
    });

    it('check_table_prefix handles boundary length 20', () => {
      const exactLength = 'a'.repeat(20);
      expect(check_table_prefix(exactLength)).toBe(true);

      const tooLong = 'a'.repeat(21);
      vi.spyOn(window, 'alert').mockImplementation(() => {});
      expect(check_table_prefix(tooLong)).toBe(false);
    });

    it('iknowall handles zero text ID', () => {
      vi.spyOn(window, 'confirm').mockReturnValue(true);

      iknowall(0);

      expect(mockLoadModalFrame).toHaveBeenCalledWith('all_words_wellknown.php?text=0');
    });

    it('check_table_prefix handles consecutive underscores', () => {
      const result = check_table_prefix('my__prefix');

      expect(result).toBe(true);
    });

    it('resetAll handles empty string URL', () => {
      resetAll('');

      expect(window.location.href).toContain('u=');
    });
  });
});
