/**
 * Tests for ui/navbar.ts - Navbar Alpine.js component
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { navbarData, initNavbarAlpine } from '../../../src/frontend/js/ui/navbar';

describe('ui/navbar.ts', () => {
  let originalLocation: Location;

  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();

    // Mock window.location
    originalLocation = window.location;
    delete (window as { location?: Location }).location;
    window.location = { href: '' } as Location;
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
    window.location = originalLocation;
  });

  // ===========================================================================
  // navbarData Tests
  // ===========================================================================

  describe('navbarData', () => {
    it('returns object with expected properties', () => {
      const data = navbarData();

      expect(data).toHaveProperty('isOpen');
      expect(data).toHaveProperty('activeDropdown');
      expect(data).toHaveProperty('init');
      expect(data).toHaveProperty('toggle');
      expect(data).toHaveProperty('close');
      expect(data).toHaveProperty('toggleDropdown');
      expect(data).toHaveProperty('closeDropdowns');
      expect(data).toHaveProperty('navigate');
    });

    it('initializes isOpen as false', () => {
      const data = navbarData();

      expect(data.isOpen).toBe(false);
    });

    it('initializes activeDropdown as null', () => {
      const data = navbarData();

      expect(data.activeDropdown).toBeNull();
    });
  });

  // ===========================================================================
  // toggle Tests
  // ===========================================================================

  describe('toggle', () => {
    it('toggles isOpen from false to true', () => {
      const data = navbarData();

      data.toggle();

      expect(data.isOpen).toBe(true);
    });

    it('toggles isOpen from true to false', () => {
      const data = navbarData();
      data.isOpen = true;

      data.toggle();

      expect(data.isOpen).toBe(false);
    });

    it('closes dropdowns when closing navbar', () => {
      const data = navbarData();
      data.isOpen = true;
      data.activeDropdown = 'texts';

      data.toggle();

      expect(data.isOpen).toBe(false);
      expect(data.activeDropdown).toBeNull();
    });

    it('does not close dropdowns when opening navbar', () => {
      const data = navbarData();
      data.isOpen = false;
      data.activeDropdown = 'texts';

      data.toggle();

      expect(data.isOpen).toBe(true);
      expect(data.activeDropdown).toBe('texts');
    });
  });

  // ===========================================================================
  // close Tests
  // ===========================================================================

  describe('close', () => {
    it('sets isOpen to false', () => {
      const data = navbarData();
      data.isOpen = true;

      data.close();

      expect(data.isOpen).toBe(false);
    });

    it('closes all dropdowns', () => {
      const data = navbarData();
      data.activeDropdown = 'languages';

      data.close();

      expect(data.activeDropdown).toBeNull();
    });

    it('can be called when already closed', () => {
      const data = navbarData();
      data.isOpen = false;

      expect(() => data.close()).not.toThrow();
      expect(data.isOpen).toBe(false);
    });
  });

  // ===========================================================================
  // toggleDropdown Tests
  // ===========================================================================

  describe('toggleDropdown', () => {
    it('opens dropdown when none is active', () => {
      const data = navbarData();

      data.toggleDropdown('texts');

      expect(data.activeDropdown).toBe('texts');
    });

    it('closes dropdown when same dropdown is clicked', () => {
      const data = navbarData();
      data.activeDropdown = 'texts';

      data.toggleDropdown('texts');

      expect(data.activeDropdown).toBeNull();
    });

    it('switches to different dropdown', () => {
      const data = navbarData();
      data.activeDropdown = 'texts';

      data.toggleDropdown('languages');

      expect(data.activeDropdown).toBe('languages');
    });

    it('handles empty string dropdown name', () => {
      const data = navbarData();

      data.toggleDropdown('');

      expect(data.activeDropdown).toBe('');
    });
  });

  // ===========================================================================
  // closeDropdowns Tests
  // ===========================================================================

  describe('closeDropdowns', () => {
    it('sets activeDropdown to null', () => {
      const data = navbarData();
      data.activeDropdown = 'texts';

      data.closeDropdowns();

      expect(data.activeDropdown).toBeNull();
    });

    it('can be called when no dropdown is active', () => {
      const data = navbarData();
      data.activeDropdown = null;

      expect(() => data.closeDropdowns()).not.toThrow();
      expect(data.activeDropdown).toBeNull();
    });
  });

  // ===========================================================================
  // navigate Tests
  // ===========================================================================

  describe('navigate', () => {
    it('closes navbar before navigating', () => {
      const data = navbarData();
      data.isOpen = true;
      data.activeDropdown = 'texts';

      data.navigate('/texts');

      expect(data.isOpen).toBe(false);
      expect(data.activeDropdown).toBeNull();
    });

    it('sets window.location.href', () => {
      const data = navbarData();

      data.navigate('/languages');

      expect(window.location.href).toBe('/languages');
    });

    it('handles absolute URLs', () => {
      const data = navbarData();

      data.navigate('https://example.com');

      expect(window.location.href).toBe('https://example.com');
    });

    it('handles URLs with query parameters', () => {
      const data = navbarData();

      data.navigate('/texts?lang=1&sort=2');

      expect(window.location.href).toBe('/texts?lang=1&sort=2');
    });
  });

  // ===========================================================================
  // init Tests
  // ===========================================================================

  describe('init', () => {
    it('adds click event listener to document', () => {
      const addEventListenerSpy = vi.spyOn(document, 'addEventListener');
      const data = navbarData();

      data.init();

      expect(addEventListenerSpy).toHaveBeenCalledWith('click', expect.any(Function));
    });

    it('adds keydown event listener to document', () => {
      const addEventListenerSpy = vi.spyOn(document, 'addEventListener');
      const data = navbarData();

      data.init();

      expect(addEventListenerSpy).toHaveBeenCalledWith('keydown', expect.any(Function));
    });

    it('closes navbar on click outside', () => {
      const data = navbarData();
      data.init();
      data.isOpen = true;

      // Create a navbar element
      const navbar = document.createElement('nav');
      navbar.className = 'navbar';
      document.body.appendChild(navbar);

      // Click outside navbar
      const outsideElement = document.createElement('div');
      document.body.appendChild(outsideElement);

      const clickEvent = new MouseEvent('click', { bubbles: true });
      outsideElement.dispatchEvent(clickEvent);

      expect(data.isOpen).toBe(false);
    });

    it('does not close navbar on click inside', () => {
      const data = navbarData();
      data.init();
      data.isOpen = true;

      // Create a navbar element
      const navbar = document.createElement('nav');
      navbar.className = 'navbar';
      document.body.appendChild(navbar);

      // Click inside navbar
      const clickEvent = new MouseEvent('click', { bubbles: true });
      navbar.dispatchEvent(clickEvent);

      expect(data.isOpen).toBe(true);
    });

    it('closes navbar on Escape key', () => {
      const data = navbarData();
      data.init();
      data.isOpen = true;

      const escapeEvent = new KeyboardEvent('keydown', { key: 'Escape' });
      document.dispatchEvent(escapeEvent);

      expect(data.isOpen).toBe(false);
    });

    it('does not close navbar on other keys', () => {
      const data = navbarData();
      data.init();
      data.isOpen = true;

      const enterEvent = new KeyboardEvent('keydown', { key: 'Enter' });
      document.dispatchEvent(enterEvent);

      expect(data.isOpen).toBe(true);
    });
  });

  // ===========================================================================
  // initNavbarAlpine Tests
  // ===========================================================================

  describe('initNavbarAlpine', () => {
    it('does not throw when called', () => {
      expect(() => initNavbarAlpine()).not.toThrow();
    });

    it('can be called multiple times', () => {
      expect(() => {
        initNavbarAlpine();
        initNavbarAlpine();
      }).not.toThrow();
    });
  });

  // ===========================================================================
  // Window Exports Tests
  // ===========================================================================

  describe('Window Exports', () => {
    it('exposes navbarData on window', () => {
      expect(window.navbarData).toBeDefined();
      expect(typeof window.navbarData).toBe('function');
    });

    it('exposes initNavbarAlpine on window', () => {
      expect(window.initNavbarAlpine).toBeDefined();
      expect(typeof window.initNavbarAlpine).toBe('function');
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles multiple rapid toggles', () => {
      const data = navbarData();

      data.toggle();
      data.toggle();
      data.toggle();
      data.toggle();

      expect(data.isOpen).toBe(false);
    });

    it('handles navigate with empty URL', () => {
      const data = navbarData();

      expect(() => data.navigate('')).not.toThrow();
      expect(window.location.href).toBe('');
    });

    it('handles dropdown with special characters in name', () => {
      const data = navbarData();

      data.toggleDropdown('test-dropdown_123');

      expect(data.activeDropdown).toBe('test-dropdown_123');
    });

    it('fresh instance each call', () => {
      const data1 = navbarData();
      const data2 = navbarData();

      data1.isOpen = true;
      data1.activeDropdown = 'test';

      expect(data2.isOpen).toBe(false);
      expect(data2.activeDropdown).toBeNull();
    });
  });
});
