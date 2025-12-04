/**
 * Tests for home_app.ts - Alpine.js home page component
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { homeData, initHomeAlpine } from '../../../src/frontend/js/home/home_app';

describe('home_app.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    localStorage.clear();
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
    localStorage.clear();
  });

  // ===========================================================================
  // homeData Tests
  // ===========================================================================

  describe('homeData', () => {
    describe('initial state', () => {
      it('initializes with empty collapsedMenus array', () => {
        const data = homeData();
        expect(data.collapsedMenus).toEqual([]);
      });

      it('initializes with all warnings hidden', () => {
        const data = homeData();
        expect(data.warnings.phpOutdated.visible).toBe(false);
        expect(data.warnings.cookiesDisabled.visible).toBe(false);
        expect(data.warnings.updateAvailable.visible).toBe(false);
      });

      it('initializes with correct warning types', () => {
        const data = homeData();
        expect(data.warnings.phpOutdated.type).toBe('danger');
        expect(data.warnings.cookiesDisabled.type).toBe('warning');
        expect(data.warnings.updateAvailable.type).toBe('info');
      });
    });

    describe('init', () => {
      it('loads menu state on first visit and collapses all except languages and texts', () => {
        const data = homeData();
        data.init();

        // The code collapses: terms, feeds, admin, settings (not texts or languages)
        expect(data.collapsedMenus).toContain('terms');
        expect(data.collapsedMenus).toContain('feeds');
        expect(data.collapsedMenus).toContain('admin');
        expect(data.collapsedMenus).toContain('settings');
        expect(data.collapsedMenus).not.toContain('languages');
        expect(data.collapsedMenus).not.toContain('texts');
      });

      it('restores saved menu state from localStorage', () => {
        localStorage.setItem('lwt_collapsed_menus', JSON.stringify(['languages', 'texts']));

        const data = homeData();
        data.init();

        expect(data.collapsedMenus).toContain('languages');
        expect(data.collapsedMenus).toContain('texts');
        expect(data.collapsedMenus).not.toContain('terms');
      });
    });

    describe('isCollapsed', () => {
      it('returns true for collapsed menu', () => {
        const data = homeData();
        data.collapsedMenus = ['texts', 'terms'];

        expect(data.isCollapsed('texts')).toBe(true);
        expect(data.isCollapsed('terms')).toBe(true);
      });

      it('returns false for expanded menu', () => {
        const data = homeData();
        data.collapsedMenus = ['texts'];

        expect(data.isCollapsed('languages')).toBe(false);
        expect(data.isCollapsed('terms')).toBe(false);
      });
    });

    describe('toggleMenu', () => {
      it('collapses an expanded menu', () => {
        const data = homeData();
        data.collapsedMenus = [];

        data.toggleMenu('languages');

        expect(data.collapsedMenus).toContain('languages');
      });

      it('expands a collapsed menu', () => {
        const data = homeData();
        data.collapsedMenus = ['languages'];

        data.toggleMenu('languages');

        expect(data.collapsedMenus).not.toContain('languages');
      });

      it('saves state to localStorage after toggle', () => {
        const data = homeData();
        data.collapsedMenus = [];

        data.toggleMenu('texts');

        const saved = JSON.parse(localStorage.getItem('lwt_collapsed_menus') || '[]');
        expect(saved).toContain('texts');
      });
    });

    describe('shouldUpdate', () => {
      describe('returns true when fromVersion < toVersion', () => {
        it('handles major version difference', () => {
          const data = homeData();
          expect(data.shouldUpdate('1.0.0', '2.0.0')).toBe(true);
        });

        it('handles minor version difference', () => {
          const data = homeData();
          expect(data.shouldUpdate('2.5.0', '2.6.0')).toBe(true);
        });

        it('handles patch version difference', () => {
          const data = homeData();
          expect(data.shouldUpdate('2.5.1', '2.5.2')).toBe(true);
        });

        it('handles multiple level difference', () => {
          const data = homeData();
          expect(data.shouldUpdate('1.2.3', '2.0.0')).toBe(true);
        });
      });

      describe('returns false when fromVersion > toVersion', () => {
        it('handles major version difference', () => {
          const data = homeData();
          expect(data.shouldUpdate('3.0.0', '2.0.0')).toBe(false);
        });

        it('handles minor version difference', () => {
          const data = homeData();
          expect(data.shouldUpdate('2.7.0', '2.6.0')).toBe(false);
        });

        it('handles patch version difference', () => {
          const data = homeData();
          expect(data.shouldUpdate('2.5.3', '2.5.2')).toBe(false);
        });
      });

      describe('returns null for equal versions', () => {
        it('handles exact match', () => {
          const data = homeData();
          expect(data.shouldUpdate('2.5.1', '2.5.1')).toBe(null);
        });

        it('handles version with pre-release suffix', () => {
          const data = homeData();
          expect(data.shouldUpdate('2.5.1-beta', '2.5.1-alpha')).toBe(null);
        });
      });

      describe('handles invalid versions', () => {
        it('returns null for invalid fromVersion', () => {
          const data = homeData();
          expect(data.shouldUpdate('invalid', '2.0.0')).toBe(null);
        });

        it('returns null for invalid toVersion', () => {
          const data = homeData();
          expect(data.shouldUpdate('2.0.0', 'invalid')).toBe(null);
        });

        it('returns null for both invalid', () => {
          const data = homeData();
          expect(data.shouldUpdate('invalid', 'also-invalid')).toBe(null);
        });

        it('returns null for empty strings', () => {
          const data = homeData();
          expect(data.shouldUpdate('', '')).toBe(null);
        });
      });

      describe('handles pre-release suffixes', () => {
        it('compares base versions ignoring suffix', () => {
          const data = homeData();
          expect(data.shouldUpdate('2.5.0-beta', '2.6.0')).toBe(true);
        });

        it('handles complex suffixes', () => {
          const data = homeData();
          expect(data.shouldUpdate('2.5.0-alpha.1', '2.5.0-beta.2')).toBe(null);
        });
      });
    });

    describe('checkCookies', () => {
      it('sets warning visible when cookies are disabled', () => {
        // Mock document.cookie to simulate disabled cookies
        const originalCookie = Object.getOwnPropertyDescriptor(document, 'cookie');
        Object.defineProperty(document, 'cookie', {
          get: () => '',
          set: () => {},
          configurable: true
        });

        const data = homeData();
        data.checkCookies();

        expect(data.warnings.cookiesDisabled.visible).toBe(true);
        expect(data.warnings.cookiesDisabled.message).toContain('not enabled');

        // Restore
        if (originalCookie) {
          Object.defineProperty(document, 'cookie', originalCookie);
        }
      });

      it('does not set warning when cookies are enabled', () => {
        // Mock cookies to be working - set and get test cookie
        let cookieStore = '';
        const originalCookie = Object.getOwnPropertyDescriptor(document, 'cookie');
        Object.defineProperty(document, 'cookie', {
          get: () => cookieStore,
          set: (val: string) => {
            // Simulate cookie being set
            if (val.includes('=') && !val.includes('expires=Thu, 01 Jan 1970')) {
              cookieStore = val.split(';')[0];
            } else {
              cookieStore = '';
            }
          },
          configurable: true
        });

        const data = homeData();
        data.checkCookies();

        expect(data.warnings.cookiesDisabled.visible).toBe(false);

        // Restore
        if (originalCookie) {
          Object.defineProperty(document, 'cookie', originalCookie);
        }
      });
    });

    describe('checkPHPVersion', () => {
      it('sets warning visible for PHP version below 8.0.0', () => {
        const data = homeData();
        data.checkPHPVersion('7.4.0');

        expect(data.warnings.phpOutdated.visible).toBe(true);
        expect(data.warnings.phpOutdated.message).toContain('7.4.0');
        expect(data.warnings.phpOutdated.message).toContain('8.0.0');
      });

      it('does not set warning for PHP 8.0.0', () => {
        const data = homeData();
        data.checkPHPVersion('8.0.0');

        expect(data.warnings.phpOutdated.visible).toBe(false);
      });

      it('does not set warning for PHP above 8.0.0', () => {
        const data = homeData();
        data.checkPHPVersion('8.2.0');

        expect(data.warnings.phpOutdated.visible).toBe(false);
      });
    });

    describe('checkLWTUpdate', () => {
      it('sets update warning when newer version available', async () => {
        const fetchSpy = vi.spyOn(global, 'fetch').mockResolvedValue({
          json: () => Promise.resolve({ tag_name: '3.0.0' })
        } as Response);

        const data = homeData();
        data.checkLWTUpdate('2.5.0');

        expect(fetchSpy).toHaveBeenCalledWith(
          'https://api.github.com/repos/hugofara/lwt/releases/latest'
        );

        // Wait for async update
        await vi.waitFor(() => {
          expect(data.warnings.updateAvailable.visible).toBe(true);
        });

        expect(data.warnings.updateAvailable.message).toContain('3.0.0');

        fetchSpy.mockRestore();
      });

      it('does not set warning when version is current', async () => {
        const fetchSpy = vi.spyOn(global, 'fetch').mockResolvedValue({
          json: () => Promise.resolve({ tag_name: '2.5.0' })
        } as Response);

        const data = homeData();
        data.checkLWTUpdate('2.5.0');

        // Wait for promise to complete
        await new Promise(resolve => setTimeout(resolve, 10));

        expect(data.warnings.updateAvailable.visible).toBe(false);

        fetchSpy.mockRestore();
      });

      it('handles API errors gracefully', async () => {
        const fetchSpy = vi.spyOn(global, 'fetch').mockRejectedValue(new Error('Network error'));

        const data = homeData();
        expect(() => data.checkLWTUpdate('2.5.0')).not.toThrow();

        // Wait for promise to complete
        await new Promise(resolve => setTimeout(resolve, 10));

        expect(data.warnings.updateAvailable.visible).toBe(false);

        fetchSpy.mockRestore();
      });
    });

    describe('initWarnings', () => {
      it('reads config from JSON element and calls all check functions', () => {
        document.body.innerHTML = `
          <script id="home-warnings-config" type="application/json">
            {"phpVersion": "7.4.0", "lwtVersion": "2.5.0"}
          </script>
        `;

        const fetchSpy = vi.spyOn(global, 'fetch').mockResolvedValue({
          json: () => Promise.resolve({ tag_name: '2.5.0' })
        } as Response);

        const data = homeData();
        data.initWarnings();

        // PHP version check should trigger warning for 7.4.0
        expect(data.warnings.phpOutdated.visible).toBe(true);

        fetchSpy.mockRestore();
      });

      it('does nothing when config element is missing', () => {
        document.body.innerHTML = '';

        const data = homeData();

        expect(() => data.initWarnings()).not.toThrow();
        expect(data.warnings.phpOutdated.visible).toBe(false);
      });

      it('handles invalid JSON gracefully', () => {
        document.body.innerHTML = `
          <script id="home-warnings-config" type="application/json">
            invalid json {
          </script>
        `;

        const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

        const data = homeData();
        data.initWarnings();

        expect(consoleSpy).toHaveBeenCalledWith(
          'Failed to parse home warnings config:',
          expect.any(Error)
        );

        consoleSpy.mockRestore();
      });
    });
  });

  // ===========================================================================
  // initHomeAlpine Tests
  // ===========================================================================

  describe('initHomeAlpine', () => {
    it('registers homeApp component with Alpine', () => {
      // Note: In a real test environment, we'd verify Alpine.data was called
      // For now, just ensure it doesn't throw
      expect(() => initHomeAlpine()).not.toThrow();
    });
  });

  // ===========================================================================
  // Window Exports Tests
  // ===========================================================================

  describe('window exports', () => {
    it('exports homeData to window', async () => {
      await import('../../../src/frontend/js/home/home_app');

      expect(typeof window.homeData).toBe('function');
    });

    it('exports initHomeAlpine to window', async () => {
      await import('../../../src/frontend/js/home/home_app');

      expect(typeof window.initHomeAlpine).toBe('function');
    });
  });
});
