/**
 * PWA Service Worker registration for LWT.
 *
 * Handles service worker registration, updates, and provides
 * utilities for cache management.
 *
 * @author  HugoFara <Hugo.Farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { url } from '@shared/utils/url';

/**
 * Service worker registration state
 */
interface SWRegistrationState {
  registration: ServiceWorkerRegistration | null;
  updateAvailable: boolean;
  offline: boolean;
}

/**
 * Global PWA state
 */
const pwaState: SWRegistrationState = {
  registration: null,
  updateAvailable: false,
  offline: !navigator.onLine,
};

/**
 * Check if service workers are supported
 */
export function isServiceWorkerSupported(): boolean {
  return 'serviceWorker' in navigator;
}

/**
 * Register the service worker
 */
export async function registerServiceWorker(): Promise<ServiceWorkerRegistration | null> {
  if (!isServiceWorkerSupported()) {
    console.log('[PWA] Service workers not supported');
    return null;
  }

  try {
    const swUrl = url('/sw.js');
    const registration = await navigator.serviceWorker.register(swUrl, {
      scope: url('/'),
    });

    pwaState.registration = registration;

    // Check for updates
    registration.addEventListener('updatefound', () => {
      const newWorker = registration.installing;
      if (newWorker) {
        newWorker.addEventListener('statechange', () => {
          if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
            // New service worker available
            pwaState.updateAvailable = true;
            dispatchPWAEvent('pwa:updateavailable');
          }
        });
      }
    });

    // Handle controller change (new SW taking over)
    navigator.serviceWorker.addEventListener('controllerchange', () => {
      dispatchPWAEvent('pwa:controllerchange');
    });

    console.log('[PWA] Service worker registered successfully');
    return registration;
  } catch (error) {
    console.error('[PWA] Service worker registration failed:', error);
    return null;
  }
}

/**
 * Unregister the service worker
 */
export async function unregisterServiceWorker(): Promise<boolean> {
  if (!pwaState.registration) {
    return false;
  }

  try {
    const result = await pwaState.registration.unregister();
    if (result) {
      pwaState.registration = null;
      console.log('[PWA] Service worker unregistered');
    }
    return result;
  } catch (error) {
    console.error('[PWA] Failed to unregister service worker:', error);
    return false;
  }
}

/**
 * Skip waiting and activate new service worker
 */
export function skipWaiting(): void {
  if (pwaState.registration?.waiting) {
    pwaState.registration.waiting.postMessage({ type: 'SKIP_WAITING' });
  }
}

/**
 * Request the service worker to cache specific URLs
 */
export function cacheUrls(urls: string[]): void {
  if (navigator.serviceWorker.controller) {
    navigator.serviceWorker.controller.postMessage({
      type: 'CACHE_URLS',
      urls,
    });
  }
}

/**
 * Request the service worker to clear all caches
 */
export function clearCache(): void {
  if (navigator.serviceWorker.controller) {
    navigator.serviceWorker.controller.postMessage({
      type: 'CLEAR_CACHE',
    });
  }
}

/**
 * Check if an update is available
 */
export function isUpdateAvailable(): boolean {
  return pwaState.updateAvailable;
}

/**
 * Check if the app is offline
 */
export function isOffline(): boolean {
  return pwaState.offline;
}

/**
 * Get the current service worker registration
 */
export function getRegistration(): ServiceWorkerRegistration | null {
  return pwaState.registration;
}

/**
 * Dispatch a custom PWA event
 */
function dispatchPWAEvent(eventName: string, detail?: Record<string, unknown>): void {
  window.dispatchEvent(new CustomEvent(eventName, { detail }));
}

/**
 * Set up online/offline event listeners
 */
function setupConnectivityListeners(): void {
  window.addEventListener('online', () => {
    pwaState.offline = false;
    dispatchPWAEvent('pwa:online');
  });

  window.addEventListener('offline', () => {
    pwaState.offline = true;
    dispatchPWAEvent('pwa:offline');
  });
}

/**
 * Initialize PWA functionality
 *
 * This is called automatically when the module is imported.
 * It registers the service worker and sets up connectivity listeners.
 */
export function initPWA(): void {
  // Set up connectivity listeners
  setupConnectivityListeners();

  // Register service worker after page load
  if (document.readyState === 'loading') {
    window.addEventListener('load', () => {
      registerServiceWorker();
    });
  } else {
    registerServiceWorker();
  }
}

// Auto-initialize on import
initPWA();

// Expose PWA utilities globally for debugging
declare global {
  interface Window {
    LWT_PWA: {
      isOffline: typeof isOffline;
      isUpdateAvailable: typeof isUpdateAvailable;
      skipWaiting: typeof skipWaiting;
      clearCache: typeof clearCache;
      cacheUrls: typeof cacheUrls;
      unregister: typeof unregisterServiceWorker;
    };
  }
}

window.LWT_PWA = {
  isOffline,
  isUpdateAvailable,
  skipWaiting,
  clearCache,
  cacheUrls,
  unregister: unregisterServiceWorker,
};
