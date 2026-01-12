/**
 * Tests for shared/pwa/register.ts - Service Worker registration
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock the url utility
vi.mock('../../../src/frontend/js/shared/utils/url', () => ({
  url: vi.fn((path: string) => `/lwt${path}`),
}));

// Store original navigator
const originalNavigator = globalThis.navigator;
const originalServiceWorker = originalNavigator?.serviceWorker;

// Mock ServiceWorkerRegistration
interface MockServiceWorker {
  state: string;
  addEventListener: ReturnType<typeof vi.fn>;
  postMessage: ReturnType<typeof vi.fn>;
}

interface MockServiceWorkerRegistration {
  installing: MockServiceWorker | null;
  waiting: MockServiceWorker | null;
  active: MockServiceWorker | null;
  addEventListener: ReturnType<typeof vi.fn>;
  unregister: ReturnType<typeof vi.fn>;
}

describe('shared/pwa/register.ts', () => {
  let mockRegistration: MockServiceWorkerRegistration;
  let mockServiceWorker: {
    register: ReturnType<typeof vi.fn>;
    controller: MockServiceWorker | null;
    addEventListener: ReturnType<typeof vi.fn>;
  };
  let dispatchEventSpy: ReturnType<typeof vi.spyOn>;
  let consoleLogSpy: ReturnType<typeof vi.spyOn>;
  let consoleErrorSpy: ReturnType<typeof vi.spyOn>;
  let addEventListenerSpy: ReturnType<typeof vi.spyOn>;

  beforeEach(() => {
    vi.resetModules();
    vi.clearAllMocks();

    mockRegistration = {
      installing: null,
      waiting: null,
      active: null,
      addEventListener: vi.fn(),
      unregister: vi.fn().mockResolvedValue(true),
    };

    mockServiceWorker = {
      register: vi.fn().mockResolvedValue(mockRegistration),
      controller: null,
      addEventListener: vi.fn(),
    };

    // Mock navigator.serviceWorker
    Object.defineProperty(navigator, 'serviceWorker', {
      value: mockServiceWorker,
      writable: true,
      configurable: true,
    });

    // Mock navigator.onLine
    Object.defineProperty(navigator, 'onLine', {
      value: true,
      writable: true,
      configurable: true,
    });

    // Mock document.readyState
    Object.defineProperty(document, 'readyState', {
      value: 'complete',
      writable: true,
      configurable: true,
    });

    dispatchEventSpy = vi.spyOn(window, 'dispatchEvent').mockImplementation(() => true);
    addEventListenerSpy = vi.spyOn(window, 'addEventListener');
    consoleLogSpy = vi.spyOn(console, 'log').mockImplementation(() => {});
    consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
  });

  afterEach(() => {
    dispatchEventSpy.mockRestore();
    addEventListenerSpy.mockRestore();
    consoleLogSpy.mockRestore();
    consoleErrorSpy.mockRestore();

    // Restore original navigator
    if (originalServiceWorker) {
      Object.defineProperty(navigator, 'serviceWorker', {
        value: originalServiceWorker,
        writable: true,
        configurable: true,
      });
    }
  });

  // ===========================================================================
  // isServiceWorkerSupported Tests
  // ===========================================================================

  describe('isServiceWorkerSupported', () => {
    it('returns true when serviceWorker is available', async () => {
      const { isServiceWorkerSupported } = await import(
        '../../../src/frontend/js/shared/pwa/register'
      );

      expect(isServiceWorkerSupported()).toBe(true);
    });

    it('returns false when serviceWorker is not available', async () => {
      // Delete the serviceWorker property entirely - 'in' operator checks property existence
      delete (navigator as any).serviceWorker;

      vi.resetModules();
      const { isServiceWorkerSupported } = await import(
        '../../../src/frontend/js/shared/pwa/register'
      );

      expect(isServiceWorkerSupported()).toBe(false);
    });
  });

  // ===========================================================================
  // registerServiceWorker Tests
  // ===========================================================================

  describe('registerServiceWorker', () => {
    it('registers service worker with correct URL and scope', async () => {
      const { registerServiceWorker } = await import(
        '../../../src/frontend/js/shared/pwa/register'
      );

      await registerServiceWorker();

      expect(mockServiceWorker.register).toHaveBeenCalledWith('/lwt/sw.js', {
        scope: '/lwt/',
      });
    });

    it('returns registration on success', async () => {
      const { registerServiceWorker } = await import(
        '../../../src/frontend/js/shared/pwa/register'
      );

      const result = await registerServiceWorker();

      expect(result).toBe(mockRegistration);
      expect(consoleLogSpy).toHaveBeenCalledWith(
        '[PWA] Service worker registered successfully'
      );
    });

    it('returns null when service workers not supported', async () => {
      // Delete the serviceWorker property entirely
      delete (navigator as any).serviceWorker;

      vi.resetModules();
      const { registerServiceWorker } = await import(
        '../../../src/frontend/js/shared/pwa/register'
      );

      const result = await registerServiceWorker();

      expect(result).toBeNull();
      expect(consoleLogSpy).toHaveBeenCalledWith(
        '[PWA] Service workers not supported'
      );
    });

    it('returns null and logs error on registration failure', async () => {
      mockServiceWorker.register.mockRejectedValue(new Error('Registration failed'));

      vi.resetModules();
      const { registerServiceWorker } = await import(
        '../../../src/frontend/js/shared/pwa/register'
      );

      const result = await registerServiceWorker();

      expect(result).toBeNull();
      expect(consoleErrorSpy).toHaveBeenCalledWith(
        '[PWA] Service worker registration failed:',
        expect.any(Error)
      );
    });

    it('sets up updatefound listener', async () => {
      const { registerServiceWorker } = await import(
        '../../../src/frontend/js/shared/pwa/register'
      );

      await registerServiceWorker();

      expect(mockRegistration.addEventListener).toHaveBeenCalledWith(
        'updatefound',
        expect.any(Function)
      );
    });

    it('sets up controllerchange listener', async () => {
      const { registerServiceWorker } = await import(
        '../../../src/frontend/js/shared/pwa/register'
      );

      await registerServiceWorker();

      expect(mockServiceWorker.addEventListener).toHaveBeenCalledWith(
        'controllerchange',
        expect.any(Function)
      );
    });

    it('dispatches pwa:updateavailable when new worker is installed', async () => {
      const mockNewWorker: MockServiceWorker = {
        state: 'installing',
        addEventListener: vi.fn(),
        postMessage: vi.fn(),
      };

      mockRegistration.addEventListener.mockImplementation((event, handler) => {
        if (event === 'updatefound') {
          mockRegistration.installing = mockNewWorker;
          handler();
        }
      });

      mockNewWorker.addEventListener.mockImplementation((event, handler) => {
        if (event === 'statechange') {
          mockNewWorker.state = 'installed';
          mockServiceWorker.controller = { state: 'activated' } as any;
          handler();
        }
      });

      vi.resetModules();
      const { registerServiceWorker } = await import(
        '../../../src/frontend/js/shared/pwa/register'
      );

      await registerServiceWorker();

      expect(dispatchEventSpy).toHaveBeenCalledWith(
        expect.objectContaining({ type: 'pwa:updateavailable' })
      );
    });

    it('dispatches pwa:controllerchange when controller changes', async () => {
      mockServiceWorker.addEventListener.mockImplementation((event, handler) => {
        if (event === 'controllerchange') {
          handler();
        }
      });

      vi.resetModules();
      const { registerServiceWorker } = await import(
        '../../../src/frontend/js/shared/pwa/register'
      );

      await registerServiceWorker();

      expect(dispatchEventSpy).toHaveBeenCalledWith(
        expect.objectContaining({ type: 'pwa:controllerchange' })
      );
    });
  });

  // ===========================================================================
  // unregisterServiceWorker Tests
  // ===========================================================================

  describe('unregisterServiceWorker', () => {
    it('returns false when no registration exists', async () => {
      // Delete serviceWorker to prevent auto-registration on import
      delete (navigator as any).serviceWorker;

      vi.resetModules();
      const { unregisterServiceWorker } = await import(
        '../../../src/frontend/js/shared/pwa/register'
      );

      const result = await unregisterServiceWorker();

      expect(result).toBe(false);
    });

    it('unregisters and returns true on success', async () => {
      vi.resetModules();
      const { registerServiceWorker, unregisterServiceWorker } = await import(
        '../../../src/frontend/js/shared/pwa/register'
      );

      await registerServiceWorker();
      const result = await unregisterServiceWorker();

      expect(result).toBe(true);
      expect(mockRegistration.unregister).toHaveBeenCalled();
      expect(consoleLogSpy).toHaveBeenCalledWith('[PWA] Service worker unregistered');
    });

    it('returns false on unregister failure', async () => {
      mockRegistration.unregister.mockRejectedValue(new Error('Unregister failed'));

      vi.resetModules();
      const { registerServiceWorker, unregisterServiceWorker } = await import(
        '../../../src/frontend/js/shared/pwa/register'
      );

      await registerServiceWorker();
      const result = await unregisterServiceWorker();

      expect(result).toBe(false);
      expect(consoleErrorSpy).toHaveBeenCalledWith(
        '[PWA] Failed to unregister service worker:',
        expect.any(Error)
      );
    });
  });

  // ===========================================================================
  // skipWaiting Tests
  // ===========================================================================

  describe('skipWaiting', () => {
    it('posts SKIP_WAITING message to waiting worker', async () => {
      const mockWaitingWorker: MockServiceWorker = {
        state: 'installed',
        addEventListener: vi.fn(),
        postMessage: vi.fn(),
      };

      vi.resetModules();
      const { registerServiceWorker, skipWaiting } = await import(
        '../../../src/frontend/js/shared/pwa/register'
      );

      await registerServiceWorker();
      // Simulate a waiting worker
      mockRegistration.waiting = mockWaitingWorker;

      skipWaiting();

      expect(mockWaitingWorker.postMessage).toHaveBeenCalledWith({
        type: 'SKIP_WAITING',
      });
    });

    it('does nothing when no waiting worker', async () => {
      vi.resetModules();
      const { registerServiceWorker, skipWaiting } = await import(
        '../../../src/frontend/js/shared/pwa/register'
      );

      await registerServiceWorker();
      mockRegistration.waiting = null;

      // Should not throw
      expect(() => skipWaiting()).not.toThrow();
    });
  });

  // ===========================================================================
  // cacheUrls Tests
  // ===========================================================================

  describe('cacheUrls', () => {
    it('posts CACHE_URLS message to controller', async () => {
      const mockController: MockServiceWorker = {
        state: 'activated',
        addEventListener: vi.fn(),
        postMessage: vi.fn(),
      };
      mockServiceWorker.controller = mockController;

      vi.resetModules();
      const { cacheUrls } = await import(
        '../../../src/frontend/js/shared/pwa/register'
      );

      cacheUrls(['/page1.html', '/page2.html']);

      expect(mockController.postMessage).toHaveBeenCalledWith({
        type: 'CACHE_URLS',
        urls: ['/page1.html', '/page2.html'],
      });
    });

    it('does nothing when no controller', async () => {
      mockServiceWorker.controller = null;

      vi.resetModules();
      const { cacheUrls } = await import(
        '../../../src/frontend/js/shared/pwa/register'
      );

      // Should not throw
      expect(() => cacheUrls(['/test'])).not.toThrow();
    });
  });

  // ===========================================================================
  // clearCache Tests
  // ===========================================================================

  describe('clearCache', () => {
    it('posts CLEAR_CACHE message to controller', async () => {
      const mockController: MockServiceWorker = {
        state: 'activated',
        addEventListener: vi.fn(),
        postMessage: vi.fn(),
      };
      mockServiceWorker.controller = mockController;

      vi.resetModules();
      const { clearCache } = await import(
        '../../../src/frontend/js/shared/pwa/register'
      );

      clearCache();

      expect(mockController.postMessage).toHaveBeenCalledWith({
        type: 'CLEAR_CACHE',
      });
    });

    it('does nothing when no controller', async () => {
      mockServiceWorker.controller = null;

      vi.resetModules();
      const { clearCache } = await import(
        '../../../src/frontend/js/shared/pwa/register'
      );

      // Should not throw
      expect(() => clearCache()).not.toThrow();
    });
  });

  // ===========================================================================
  // isUpdateAvailable Tests
  // ===========================================================================

  describe('isUpdateAvailable', () => {
    it('returns false initially', async () => {
      vi.resetModules();
      const { isUpdateAvailable } = await import(
        '../../../src/frontend/js/shared/pwa/register'
      );

      expect(isUpdateAvailable()).toBe(false);
    });
  });

  // ===========================================================================
  // isOffline Tests
  // ===========================================================================

  describe('isOffline', () => {
    it('returns false when online', async () => {
      Object.defineProperty(navigator, 'onLine', { value: true });

      vi.resetModules();
      const { isOffline } = await import(
        '../../../src/frontend/js/shared/pwa/register'
      );

      expect(isOffline()).toBe(false);
    });

    it('returns true when offline', async () => {
      Object.defineProperty(navigator, 'onLine', { value: false });

      vi.resetModules();
      const { isOffline } = await import(
        '../../../src/frontend/js/shared/pwa/register'
      );

      expect(isOffline()).toBe(true);
    });
  });

  // ===========================================================================
  // getRegistration Tests
  // ===========================================================================

  describe('getRegistration', () => {
    it('returns null before registration', async () => {
      // Delete serviceWorker to prevent auto-registration on import
      delete (navigator as any).serviceWorker;

      vi.resetModules();
      const { getRegistration } = await import(
        '../../../src/frontend/js/shared/pwa/register'
      );

      expect(getRegistration()).toBeNull();
    });

    it('returns registration after successful registration', async () => {
      vi.resetModules();
      const { registerServiceWorker, getRegistration } = await import(
        '../../../src/frontend/js/shared/pwa/register'
      );

      await registerServiceWorker();

      expect(getRegistration()).toBe(mockRegistration);
    });
  });

  // ===========================================================================
  // initPWA Tests
  // ===========================================================================

  describe('initPWA', () => {
    it('sets up connectivity listeners', async () => {
      vi.resetModules();
      await import('../../../src/frontend/js/shared/pwa/register');

      expect(addEventListenerSpy).toHaveBeenCalledWith('online', expect.any(Function));
      expect(addEventListenerSpy).toHaveBeenCalledWith('offline', expect.any(Function));
    });

    it('registers service worker after page load when loading', async () => {
      Object.defineProperty(document, 'readyState', { value: 'loading' });

      vi.resetModules();
      await import('../../../src/frontend/js/shared/pwa/register');

      expect(addEventListenerSpy).toHaveBeenCalledWith('load', expect.any(Function));
    });

    it('registers service worker immediately when page already loaded', async () => {
      Object.defineProperty(document, 'readyState', { value: 'complete' });

      vi.resetModules();
      await import('../../../src/frontend/js/shared/pwa/register');

      // Should register immediately, not wait for load event
      expect(mockServiceWorker.register).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // Global Exposure Tests
  // ===========================================================================

  describe('Global Exposure', () => {
    it('exposes LWT_PWA utilities on window', async () => {
      vi.resetModules();
      await import('../../../src/frontend/js/shared/pwa/register');

      expect(window.LWT_PWA).toBeDefined();
      expect(typeof window.LWT_PWA.isOffline).toBe('function');
      expect(typeof window.LWT_PWA.isUpdateAvailable).toBe('function');
      expect(typeof window.LWT_PWA.skipWaiting).toBe('function');
      expect(typeof window.LWT_PWA.clearCache).toBe('function');
      expect(typeof window.LWT_PWA.cacheUrls).toBe('function');
      expect(typeof window.LWT_PWA.unregister).toBe('function');
    });
  });

  // ===========================================================================
  // Connectivity Event Handler Tests
  // ===========================================================================

  describe('Connectivity Event Handlers', () => {
    it('dispatches pwa:online event when going online', async () => {
      vi.resetModules();
      await import('../../../src/frontend/js/shared/pwa/register');

      const onlineCall = addEventListenerSpy.mock.calls.find(
        (call) => call[0] === 'online'
      );
      expect(onlineCall).toBeDefined();

      const handler = onlineCall![1] as () => void;
      handler();

      expect(dispatchEventSpy).toHaveBeenCalledWith(
        expect.objectContaining({ type: 'pwa:online' })
      );
    });

    it('dispatches pwa:offline event when going offline', async () => {
      vi.resetModules();
      await import('../../../src/frontend/js/shared/pwa/register');

      const offlineCall = addEventListenerSpy.mock.calls.find(
        (call) => call[0] === 'offline'
      );
      expect(offlineCall).toBeDefined();

      const handler = offlineCall![1] as () => void;
      handler();

      expect(dispatchEventSpy).toHaveBeenCalledWith(
        expect.objectContaining({ type: 'pwa:offline' })
      );
    });
  });
});
