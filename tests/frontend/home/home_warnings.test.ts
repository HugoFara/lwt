/**
 * Tests for home_warnings.ts - Home page warnings and version checking
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  shouldUpdate,
  checkCookiesDisabled,
  checkOutdatedPHP,
  checkLWTUpdate,
  initHomeWarnings,
  initHomeWarningsFromConfig
} from '../../../src/frontend/js/home/home_warnings';

// Mock cookies module
vi.mock('../../../src/frontend/js/core/cookies', () => ({
  areCookiesEnabled: vi.fn()
}));

import { areCookiesEnabled } from '../../../src/frontend/js/core/cookies';

describe('home_warnings.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // shouldUpdate Tests
  // ===========================================================================

  describe('shouldUpdate', () => {
    describe('returns true when fromVersion < toVersion', () => {
      it('handles major version difference', () => {
        expect(shouldUpdate('1.0.0', '2.0.0')).toBe(true);
      });

      it('handles minor version difference', () => {
        expect(shouldUpdate('2.5.0', '2.6.0')).toBe(true);
      });

      it('handles patch version difference', () => {
        expect(shouldUpdate('2.5.1', '2.5.2')).toBe(true);
      });

      it('handles multiple level difference', () => {
        expect(shouldUpdate('1.2.3', '2.0.0')).toBe(true);
      });
    });

    describe('returns false when fromVersion > toVersion', () => {
      it('handles major version difference', () => {
        expect(shouldUpdate('3.0.0', '2.0.0')).toBe(false);
      });

      it('handles minor version difference', () => {
        expect(shouldUpdate('2.7.0', '2.6.0')).toBe(false);
      });

      it('handles patch version difference', () => {
        expect(shouldUpdate('2.5.3', '2.5.2')).toBe(false);
      });
    });

    describe('returns null for equal versions', () => {
      it('handles exact match', () => {
        expect(shouldUpdate('2.5.1', '2.5.1')).toBe(null);
      });

      it('handles version with pre-release suffix', () => {
        expect(shouldUpdate('2.5.1-beta', '2.5.1-alpha')).toBe(null);
      });
    });

    describe('handles invalid versions', () => {
      it('returns null for invalid fromVersion', () => {
        expect(shouldUpdate('invalid', '2.0.0')).toBe(null);
      });

      it('returns null for invalid toVersion', () => {
        expect(shouldUpdate('2.0.0', 'invalid')).toBe(null);
      });

      it('returns null for both invalid', () => {
        expect(shouldUpdate('invalid', 'also-invalid')).toBe(null);
      });

      it('returns null for empty strings', () => {
        expect(shouldUpdate('', '')).toBe(null);
      });
    });

    describe('handles pre-release suffixes', () => {
      it('compares base versions ignoring suffix', () => {
        expect(shouldUpdate('2.5.0-beta', '2.6.0')).toBe(true);
      });

      it('handles complex suffixes', () => {
        expect(shouldUpdate('2.5.0-alpha.1', '2.5.0-beta.2')).toBe(null);
      });
    });
  });

  // ===========================================================================
  // checkCookiesDisabled Tests
  // ===========================================================================

  describe('checkCookiesDisabled', () => {
    it('displays warning when cookies are disabled', () => {
      document.body.innerHTML = '<div id="cookies_disabled"></div>';
      vi.mocked(areCookiesEnabled).mockReturnValue(false);

      checkCookiesDisabled();

      expect(document.getElementById('cookies_disabled')?.innerHTML).toBe(
        '*** Cookies are not enabled! Please enable them! ***'
      );
    });

    it('does not display warning when cookies are enabled', () => {
      document.body.innerHTML = '<div id="cookies_disabled">Original</div>';
      vi.mocked(areCookiesEnabled).mockReturnValue(true);

      checkCookiesDisabled();

      expect(document.getElementById('cookies_disabled')?.innerHTML).toBe('Original');
    });

    it('handles missing element gracefully', () => {
      document.body.innerHTML = '';
      vi.mocked(areCookiesEnabled).mockReturnValue(false);

      expect(() => checkCookiesDisabled()).not.toThrow();
    });
  });

  // ===========================================================================
  // checkOutdatedPHP Tests
  // ===========================================================================

  describe('checkOutdatedPHP', () => {
    it('displays warning for PHP version below 8.0.0', () => {
      document.body.innerHTML = '<div id="php_update_required"></div>';

      checkOutdatedPHP('7.4.0');

      const element = document.getElementById('php_update_required');
      expect(element?.innerHTML).toContain('7.4.0');
      expect(element?.innerHTML).toContain('8.0.0');
      expect(element?.innerHTML).toContain('Please update');
    });

    it('does not display warning for PHP 8.0.0', () => {
      document.body.innerHTML = '<div id="php_update_required">Original</div>';

      checkOutdatedPHP('8.0.0');

      expect(document.getElementById('php_update_required')?.innerHTML).toBe('Original');
    });

    it('does not display warning for PHP above 8.0.0', () => {
      document.body.innerHTML = '<div id="php_update_required">Original</div>';

      checkOutdatedPHP('8.2.0');

      expect(document.getElementById('php_update_required')?.innerHTML).toBe('Original');
    });

    it('handles missing element gracefully', () => {
      document.body.innerHTML = '';

      expect(() => checkOutdatedPHP('7.4.0')).not.toThrow();
    });
  });

  // ===========================================================================
  // checkLWTUpdate Tests
  // ===========================================================================

  describe('checkLWTUpdate', () => {
    it('displays update notification when newer version available', async () => {
      document.body.innerHTML = '<div id="lwt_new_version"></div>';

      const fetchSpy = vi.spyOn(global, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({ tag_name: '3.0.0' })
      } as Response);

      checkLWTUpdate('2.5.0');

      expect(fetchSpy).toHaveBeenCalledWith(
        'https://api.github.com/repos/hugofara/lwt/releases/latest'
      );

      // Wait for async update
      await vi.waitFor(() => {
        expect(document.getElementById('lwt_new_version')?.innerHTML).toContain('3.0.0');
      });

      fetchSpy.mockRestore();
    });

    it('does not display notification when version is current', async () => {
      document.body.innerHTML = '<div id="lwt_new_version">Original</div>';

      const fetchSpy = vi.spyOn(global, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({ tag_name: '2.5.0' })
      } as Response);

      checkLWTUpdate('2.5.0');

      // Wait for promise to complete
      await new Promise(resolve => setTimeout(resolve, 10));

      // The content shouldn't change since versions are equal
      expect(document.getElementById('lwt_new_version')?.innerHTML).toBe('Original');

      fetchSpy.mockRestore();
    });

    it('handles API errors gracefully', async () => {
      document.body.innerHTML = '<div id="lwt_new_version"></div>';

      const fetchSpy = vi.spyOn(global, 'fetch').mockRejectedValue(new Error('Network error'));

      expect(() => checkLWTUpdate('2.5.0')).not.toThrow();
    });
  });

  // ===========================================================================
  // initHomeWarnings Tests
  // ===========================================================================

  describe('initHomeWarnings', () => {
    it('calls all check functions with config', () => {
      document.body.innerHTML = `
        <div id="cookies_disabled"></div>
        <div id="php_update_required"></div>
        <div id="lwt_new_version"></div>
      `;

      vi.mocked(areCookiesEnabled).mockReturnValue(true);

      const fetchSpy = vi.spyOn(global, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({ tag_name: '2.5.0' })
      } as Response);

      initHomeWarnings({
        phpVersion: '8.1.0',
        lwtVersion: '2.5.0'
      });

      expect(areCookiesEnabled).toHaveBeenCalled();
      expect(fetchSpy).toHaveBeenCalled();

      fetchSpy.mockRestore();
    });
  });

  // ===========================================================================
  // initHomeWarningsFromConfig Tests
  // ===========================================================================

  describe('initHomeWarningsFromConfig', () => {
    it('initializes from valid JSON config element', () => {
      document.body.innerHTML = `
        <script id="home-warnings-config" type="application/json">
          {"phpVersion": "8.1.0", "lwtVersion": "2.5.0"}
        </script>
        <div id="cookies_disabled"></div>
        <div id="php_update_required"></div>
        <div id="lwt_new_version"></div>
      `;

      vi.mocked(areCookiesEnabled).mockReturnValue(true);

      const fetchSpy = vi.spyOn(global, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({ tag_name: '2.5.0' })
      } as Response);

      initHomeWarningsFromConfig();

      expect(areCookiesEnabled).toHaveBeenCalled();

      fetchSpy.mockRestore();
    });

    it('does nothing when config element is missing', () => {
      document.body.innerHTML = '';

      vi.mocked(areCookiesEnabled).mockReturnValue(true);

      initHomeWarningsFromConfig();

      expect(areCookiesEnabled).not.toHaveBeenCalled();
    });

    it('handles invalid JSON gracefully', () => {
      document.body.innerHTML = `
        <script id="home-warnings-config" type="application/json">
          invalid json {
        </script>
      `;

      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      initHomeWarningsFromConfig();

      expect(consoleSpy).toHaveBeenCalledWith(
        'Failed to parse home warnings config:',
        expect.any(Error)
      );
    });

    it('handles empty config element', () => {
      document.body.innerHTML = `
        <script id="home-warnings-config" type="application/json"></script>
        <div id="cookies_disabled"></div>
      `;

      vi.mocked(areCookiesEnabled).mockReturnValue(true);

      // Empty content parses as empty object, which is valid
      expect(() => initHomeWarningsFromConfig()).not.toThrow();
    });
  });

  // ===========================================================================
  // Window Exports Tests
  // ===========================================================================

  describe('window exports', () => {
    it('exports shouldUpdate to window', async () => {
      await import('../../../src/frontend/js/home/home_warnings');

      expect(typeof window.shouldUpdate).toBe('function');
    });

    it('exports checkCookiesDisabled to window', async () => {
      await import('../../../src/frontend/js/home/home_warnings');

      expect(typeof window.checkCookiesDisabled).toBe('function');
    });

    it('exports checkOutdatedPHP to window', async () => {
      await import('../../../src/frontend/js/home/home_warnings');

      expect(typeof window.checkOutdatedPHP).toBe('function');
    });

    it('exports checkLWTUpdate to window', async () => {
      await import('../../../src/frontend/js/home/home_warnings');

      expect(typeof window.checkLWTUpdate).toBe('function');
    });

    it('exports initHomeWarnings to window', async () => {
      await import('../../../src/frontend/js/home/home_warnings');

      expect(typeof window.initHomeWarnings).toBe('function');
    });
  });
});
