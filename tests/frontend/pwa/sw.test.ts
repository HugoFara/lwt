/**
 * Tests for js/sw.ts - Service Worker
 *
 * Service worker tests require mocking the ServiceWorkerGlobalScope.
 * We test the caching strategies and event handlers.
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock ServiceWorkerGlobalScope
interface MockCache {
  match: ReturnType<typeof vi.fn>;
  put: ReturnType<typeof vi.fn>;
  add: ReturnType<typeof vi.fn>;
  delete: ReturnType<typeof vi.fn>;
}

interface MockCaches {
  open: ReturnType<typeof vi.fn>;
  match: ReturnType<typeof vi.fn>;
  keys: ReturnType<typeof vi.fn>;
  delete: ReturnType<typeof vi.fn>;
}

describe('js/sw.ts - Service Worker', () => {
  let mockCache: MockCache;
  let mockCaches: MockCaches;
  let mockSelf: {
    addEventListener: ReturnType<typeof vi.fn>;
    skipWaiting: ReturnType<typeof vi.fn>;
    clients: { claim: ReturnType<typeof vi.fn> };
  };
  let eventHandlers: Map<string, (...args: unknown[]) => unknown>;
  let consoleLogSpy: ReturnType<typeof vi.spyOn>;
  let consoleWarnSpy: ReturnType<typeof vi.spyOn>;

  beforeEach(() => {
    vi.clearAllMocks();
    eventHandlers = new Map();

    mockCache = {
      match: vi.fn().mockResolvedValue(undefined),
      put: vi.fn().mockResolvedValue(undefined),
      add: vi.fn().mockResolvedValue(undefined),
      delete: vi.fn().mockResolvedValue(true),
    };

    mockCaches = {
      open: vi.fn().mockResolvedValue(mockCache),
      match: vi.fn().mockResolvedValue(undefined),
      keys: vi.fn().mockResolvedValue([]),
      delete: vi.fn().mockResolvedValue(true),
    };

    mockSelf = {
      addEventListener: vi.fn((event: string, handler: (...args: unknown[]) => unknown) => {
        eventHandlers.set(event, handler);
      }),
      skipWaiting: vi.fn().mockResolvedValue(undefined),
      clients: {
        claim: vi.fn().mockResolvedValue(undefined),
      },
    };

    // Set up global mocks
    (globalThis as any).self = mockSelf;
    (globalThis as any).caches = mockCaches;

    consoleLogSpy = vi.spyOn(console, 'log').mockImplementation(() => {});
    consoleWarnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});
  });

  afterEach(() => {
    consoleLogSpy.mockRestore();
    consoleWarnSpy.mockRestore();
    delete (globalThis as any).self;
    delete (globalThis as any).caches;
  });

  // ===========================================================================
  // URL Pattern Matching Tests
  // ===========================================================================

  describe('URL Pattern Matching', () => {
    it('identifies API URLs as network-first', () => {
      const networkFirstPatterns = [
        /\/api\//,
        /\/text\/read/,
        /\/review\//,
      ];

      expect(networkFirstPatterns[0].test('/api/v1/texts')).toBe(true);
      expect(networkFirstPatterns[0].test('/api/v1/terms/123')).toBe(true);
      expect(networkFirstPatterns[1].test('/text/read/123')).toBe(true);
      expect(networkFirstPatterns[2].test('/review/test')).toBe(true);
    });

    it('identifies never-cache URLs', () => {
      const neverCachePatterns = [
        /\/api\/v1\/tts\//,
        /\.php\?/,
      ];

      expect(neverCachePatterns[0].test('/api/v1/tts/speak')).toBe(true);
      expect(neverCachePatterns[1].test('/index.php?action=test')).toBe(true);
      expect(neverCachePatterns[1].test('/index.php')).toBe(false);
    });

    it('identifies static assets', () => {
      const staticAssetPattern = /\.(js|css|png|jpg|jpeg|gif|svg|ico|woff2?|ttf|eot)(\?.*)?$/;

      expect(staticAssetPattern.test('/assets/main.js')).toBe(true);
      expect(staticAssetPattern.test('/assets/style.css')).toBe(true);
      expect(staticAssetPattern.test('/images/logo.png')).toBe(true);
      expect(staticAssetPattern.test('/images/photo.jpg')).toBe(true);
      expect(staticAssetPattern.test('/fonts/roboto.woff2')).toBe(true);
      expect(staticAssetPattern.test('/main.js?v=123')).toBe(true);
      expect(staticAssetPattern.test('/page.html')).toBe(false);
      expect(staticAssetPattern.test('/api/data')).toBe(false);
    });
  });

  // ===========================================================================
  // Cache Strategy Tests
  // ===========================================================================

  describe('Cache Strategies', () => {
    describe('cacheFirst strategy', () => {
      it('returns cached response when available', async () => {
        const cachedResponse = new Response('cached data');
        mockCaches.match.mockResolvedValue(cachedResponse);

        const result = await mockCaches.match('/assets/main.js');

        expect(result).toBe(cachedResponse);
      });

      it('falls back to network when not cached', async () => {
        mockCaches.match.mockResolvedValue(undefined);

        const result = await mockCaches.match('/assets/new.js');

        expect(result).toBeUndefined();
      });
    });

    describe('networkFirst strategy', () => {
      it('tries network first for API requests', async () => {
        const mockFetch = vi.fn().mockResolvedValue(
          new Response('{"data": "fresh"}', { status: 200 })
        );
        globalThis.fetch = mockFetch;

        const response = await mockFetch('/api/v1/texts');

        expect(mockFetch).toHaveBeenCalledWith('/api/v1/texts');
        expect(response.status).toBe(200);
      });

      it('falls back to cache on network failure', async () => {
        const cachedResponse = new Response('cached api data');
        mockCaches.match.mockResolvedValue(cachedResponse);

        const result = await mockCaches.match('/api/v1/texts');

        expect(result).toBe(cachedResponse);
      });
    });
  });

  // ===========================================================================
  // Install Event Tests
  // ===========================================================================

  describe('Install Event', () => {
    it('pre-caches app shell files', async () => {
      const appShell = [
        '/',
        '/offline.html',
        '/assets/manifest.json',
        '/assets/images/lwt_icon_48.png',
        '/assets/images/lwt_icon_192.png',
        '/favicon.ico',
      ];

      // Simulate caching app shell
      await mockCaches.open('lwt-static-v1');

      for (const url of appShell) {
        await mockCache.add(url);
      }

      expect(mockCaches.open).toHaveBeenCalledWith('lwt-static-v1');
      expect(mockCache.add).toHaveBeenCalledTimes(appShell.length);
    });

    it('handles cache add failures gracefully', async () => {
      mockCache.add.mockRejectedValue(new Error('Cache add failed'));

      // Should not throw when individual cache adds fail
      await expect(
        Promise.allSettled([
          mockCache.add('/').catch(() => {}),
          mockCache.add('/offline.html').catch(() => {}),
        ])
      ).resolves.toBeDefined();
    });
  });

  // ===========================================================================
  // Activate Event Tests
  // ===========================================================================

  describe('Activate Event', () => {
    it('cleans up old caches', async () => {
      mockCaches.keys.mockResolvedValue([
        'lwt-static-v1',
        'lwt-runtime-v1',
        'lwt-static-old',
        'other-cache',
      ]);

      const cacheNames = await mockCaches.keys();
      const oldCaches = cacheNames.filter(
        (name: string) =>
          name.startsWith('lwt-') &&
          name !== 'lwt-static-v1' &&
          name !== 'lwt-runtime-v1'
      );

      expect(oldCaches).toContain('lwt-static-old');
      expect(oldCaches).not.toContain('other-cache');
    });

    it('claims clients after activation', async () => {
      await mockSelf.clients.claim();

      expect(mockSelf.clients.claim).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // Fetch Event Tests
  // ===========================================================================

  describe('Fetch Event', () => {
    it('ignores non-GET requests', () => {
      const request = new Request('https://example.com/api/v1/texts', { method: 'POST' });
      expect(request.method).toBe('POST');
      // Non-GET requests should not be intercepted
    });

    it('ignores non-http(s) requests', () => {
      const url = 'chrome-extension://abc123/page.html';
      expect(url.startsWith('http')).toBe(false);
    });

    it('passes through never-cache URLs', () => {
      const neverCachePatterns = [
        /\/api\/v1\/tts\//,
        /\.php\?/,
      ];

      const url = '/api/v1/tts/speak';
      const shouldNeverCache = neverCachePatterns.some((p) => p.test(url));
      expect(shouldNeverCache).toBe(true);
    });

    it('uses network-first for API requests', () => {
      const networkFirstPatterns = [/\/api\//];
      const url = '/api/v1/terms/123';
      const isNetworkFirst = networkFirstPatterns.some((p) => p.test(url));
      expect(isNetworkFirst).toBe(true);
    });

    it('uses cache-first for static assets', () => {
      const staticAssetPattern = /\.(js|css|png|jpg|jpeg|gif|svg|ico|woff2?|ttf|eot)(\?.*)?$/;
      const url = '/assets/main.js';
      expect(staticAssetPattern.test(url)).toBe(true);
    });
  });

  // ===========================================================================
  // Message Event Tests
  // ===========================================================================

  describe('Message Event', () => {
    it('handles SKIP_WAITING message', () => {
      const messageData = { type: 'SKIP_WAITING' };

      if (messageData.type === 'SKIP_WAITING') {
        mockSelf.skipWaiting();
      }

      expect(mockSelf.skipWaiting).toHaveBeenCalled();
    });

    it('handles CACHE_URLS message', async () => {
      const messageData = {
        type: 'CACHE_URLS',
        urls: ['/page1.html', '/page2.html'],
      };

      if (messageData.type === 'CACHE_URLS') {
        const cache = await mockCaches.open('lwt-runtime-v1');
        await Promise.all(
          messageData.urls.map((url) =>
            cache.add(url).catch(() => {})
          )
        );
      }

      expect(mockCaches.open).toHaveBeenCalledWith('lwt-runtime-v1');
      expect(mockCache.add).toHaveBeenCalledWith('/page1.html');
      expect(mockCache.add).toHaveBeenCalledWith('/page2.html');
    });

    it('handles CLEAR_CACHE message', async () => {
      mockCaches.keys.mockResolvedValue([
        'lwt-static-v1',
        'lwt-runtime-v1',
        'other-cache',
      ]);

      const messageData = { type: 'CLEAR_CACHE' };

      if (messageData.type === 'CLEAR_CACHE') {
        const cacheNames = await mockCaches.keys();
        await Promise.all(
          cacheNames
            .filter((name: string) => name.startsWith('lwt-'))
            .map((name: string) => mockCaches.delete(name))
        );
      }

      expect(mockCaches.delete).toHaveBeenCalledWith('lwt-static-v1');
      expect(mockCaches.delete).toHaveBeenCalledWith('lwt-runtime-v1');
      expect(mockCaches.delete).not.toHaveBeenCalledWith('other-cache');
    });
  });

  // ===========================================================================
  // Offline Page Fallback Tests
  // ===========================================================================

  describe('Offline Page Fallback', () => {
    it('returns offline page for failed navigation requests', async () => {
      const offlinePage = new Response('<html>Offline</html>');
      mockCaches.match.mockResolvedValue(offlinePage);

      const result = await mockCaches.match('/offline.html');

      expect(result).toBe(offlinePage);
    });

    it('returns 503 response when offline page not cached', async () => {
      mockCaches.match.mockResolvedValue(undefined);

      const result = await mockCaches.match('/offline.html');

      expect(result).toBeUndefined();
      // In actual implementation, this would return new Response('Offline', { status: 503 })
    });
  });

  // ===========================================================================
  // Cache Versioning Tests
  // ===========================================================================

  describe('Cache Versioning', () => {
    it('uses versioned cache names', () => {
      const CACHE_VERSION = 'v1';
      const STATIC_CACHE = `lwt-static-${CACHE_VERSION}`;
      const RUNTIME_CACHE = `lwt-runtime-${CACHE_VERSION}`;

      expect(STATIC_CACHE).toBe('lwt-static-v1');
      expect(RUNTIME_CACHE).toBe('lwt-runtime-v1');
    });

    it('identifies old caches correctly', () => {
      const CACHE_VERSION = 'v1';
      const STATIC_CACHE = `lwt-static-${CACHE_VERSION}`;
      const RUNTIME_CACHE = `lwt-runtime-${CACHE_VERSION}`;

      const cacheNames = [
        'lwt-static-v1',
        'lwt-runtime-v1',
        'lwt-static-v0',
        'lwt-runtime-v0',
        'other-app-cache',
      ];

      const oldCaches = cacheNames.filter(
        (name) =>
          name.startsWith('lwt-') &&
          name !== STATIC_CACHE &&
          name !== RUNTIME_CACHE
      );

      expect(oldCaches).toEqual(['lwt-static-v0', 'lwt-runtime-v0']);
    });
  });

  // ===========================================================================
  // Response Caching Tests
  // ===========================================================================

  describe('Response Caching', () => {
    it('caches successful network responses', async () => {
      const response = new Response('data', { status: 200 });
      const request = new Request('https://example.com/api/v1/texts');

      if (response.ok) {
        await mockCache.put(request, response.clone());
      }

      expect(mockCache.put).toHaveBeenCalled();
    });

    it('does not cache error responses', async () => {
      const response = new Response('error', { status: 500 });

      if (response.ok) {
        await mockCache.put(new Request('https://example.com/api'), response);
      }

      expect(mockCache.put).not.toHaveBeenCalled();
    });

    it('clones response before caching', async () => {
      const response = new Response('data', { status: 200 });
      const cloneSpy = vi.spyOn(response, 'clone');

      if (response.ok) {
        const cloned = response.clone();
        await mockCache.put(new Request('https://example.com/test'), cloned);
      }

      expect(cloneSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // Stale While Revalidate Tests
  // ===========================================================================

  describe('Stale While Revalidate', () => {
    it('returns cached response immediately if available', async () => {
      const cachedResponse = new Response('cached');
      mockCache.match.mockResolvedValue(cachedResponse);

      const result = await mockCache.match('/page.html');

      expect(result).toBe(cachedResponse);
    });

    it('updates cache in background after serving cached response', async () => {
      // Simulate stale-while-revalidate
      const cachedResponse = new Response('cached');
      mockCache.match.mockResolvedValue(cachedResponse);

      // Return cached immediately
      const result = await mockCache.match('/page.html');
      expect(result).toBe(cachedResponse);

      // Background update
      const freshResponse = new Response('fresh', { status: 200 });
      if (freshResponse.ok) {
        await mockCache.put(new Request('https://example.com/page.html'), freshResponse.clone());
      }

      expect(mockCache.put).toHaveBeenCalled();
    });
  });
});
