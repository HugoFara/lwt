/**
 * Service Worker for LWT PWA
 *
 * Provides offline support through app shell caching and
 * network-first strategy for API requests.
 *
 * @author  HugoFara <Hugo.Farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

/// <reference lib="webworker" />

// Extend ServiceWorkerGlobalScope for TypeScript
declare let self: ServiceWorkerGlobalScope;
export type {}; // Make this a module to avoid global scope issues

const sw = self;

// Cache version - increment to invalidate old caches
const CACHE_VERSION = 'v1';
const STATIC_CACHE = `lwt-static-${CACHE_VERSION}`;
const RUNTIME_CACHE = `lwt-runtime-${CACHE_VERSION}`;

// App shell files to pre-cache (critical for offline)
const APP_SHELL: string[] = [
  '/',
  '/offline.html',
  '/assets/manifest.json',
  '/assets/images/lwt_icon.png',
  '/assets/images/lwt_icon_big.png',
  '/favicon.ico',
];

// URL patterns that should use network-first strategy
const NETWORK_FIRST_PATTERNS: RegExp[] = [
  /\/api\//,           // All API requests
  /\/text\/read/,      // Reading interface (needs fresh data)
  /\/review\//,        // Review pages
];

// URL patterns to never cache
const NEVER_CACHE_PATTERNS: RegExp[] = [
  /\/api\/v1\/tts\//,  // TTS audio responses
  /\.php\?/,           // PHP with query strings (dynamic)
];

/**
 * Check if a URL should use network-first strategy
 */
function shouldNetworkFirst(url: string): boolean {
  return NETWORK_FIRST_PATTERNS.some(pattern => pattern.test(url));
}

/**
 * Check if a URL should never be cached
 */
function shouldNeverCache(url: string): boolean {
  return NEVER_CACHE_PATTERNS.some(pattern => pattern.test(url));
}

/**
 * Check if the request is for a static asset
 */
function isStaticAsset(url: string): boolean {
  return /\.(js|css|png|jpg|jpeg|gif|svg|ico|woff2?|ttf|eot)(\?.*)?$/.test(url);
}

/**
 * Install event - cache app shell
 */
sw.addEventListener('install', (event: ExtendableEvent) => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => {
        console.log('[SW] Pre-caching app shell');
        // Use addAll for critical resources, but don't fail if some are missing
        return Promise.allSettled(
          APP_SHELL.map(url =>
            cache.add(url).catch(err => {
              console.warn(`[SW] Failed to cache ${url}:`, err);
            })
          )
        );
      })
      .then(() => {
        // Skip waiting to activate immediately
        return sw.skipWaiting();
      })
  );
});

/**
 * Activate event - clean up old caches
 */
sw.addEventListener('activate', (event: ExtendableEvent) => {
  event.waitUntil(
    caches.keys()
      .then(cacheNames => {
        return Promise.all(
          cacheNames
            .filter(cacheName => {
              // Delete old versioned caches
              return cacheName.startsWith('lwt-') &&
                     cacheName !== STATIC_CACHE &&
                     cacheName !== RUNTIME_CACHE;
            })
            .map(cacheName => {
              console.log('[SW] Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            })
        );
      })
      .then(() => {
        // Take control of all pages immediately
        return sw.clients.claim();
      })
  );
});

/**
 * Fetch event - serve from cache or network
 */
sw.addEventListener('fetch', (event: FetchEvent) => {
  const { request } = event;
  const url = request.url;

  // Only handle GET requests
  if (request.method !== 'GET') {
    return;
  }

  // Skip non-http(s) requests (e.g., chrome-extension://)
  if (!url.startsWith('http')) {
    return;
  }

  // Never cache certain URLs
  if (shouldNeverCache(url)) {
    event.respondWith(fetch(request));
    return;
  }

  // Network-first strategy for dynamic content
  if (shouldNetworkFirst(url)) {
    event.respondWith(networkFirst(request));
    return;
  }

  // Cache-first strategy for static assets
  if (isStaticAsset(url)) {
    event.respondWith(cacheFirst(request, STATIC_CACHE));
    return;
  }

  // Stale-while-revalidate for HTML pages
  event.respondWith(staleWhileRevalidate(request));
});

/**
 * Cache-first strategy
 * Try cache first, fall back to network
 */
async function cacheFirst(request: Request, cacheName: string): Promise<Response> {
  const cachedResponse = await caches.match(request);
  if (cachedResponse) {
    return cachedResponse;
  }

  try {
    const networkResponse = await fetch(request);
    // Cache successful responses
    if (networkResponse.ok) {
      const cache = await caches.open(cacheName);
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  } catch (error) {
    // Return offline page for navigation requests
    if (request.mode === 'navigate') {
      const offlinePage = await caches.match('/offline.html');
      if (offlinePage) {
        return offlinePage;
      }
    }
    throw error;
  }
}

/**
 * Network-first strategy
 * Try network first, fall back to cache
 */
async function networkFirst(request: Request): Promise<Response> {
  try {
    const networkResponse = await fetch(request);
    // Cache successful responses
    if (networkResponse.ok) {
      const cache = await caches.open(RUNTIME_CACHE);
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  } catch (error) {
    // Try cache on network failure
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }

    // Return offline page for navigation requests
    if (request.mode === 'navigate') {
      const offlinePage = await caches.match('/offline.html');
      if (offlinePage) {
        return offlinePage;
      }
    }

    throw error;
  }
}

/**
 * Stale-while-revalidate strategy
 * Return cached version immediately, update cache in background
 */
async function staleWhileRevalidate(request: Request): Promise<Response> {
  const cache = await caches.open(RUNTIME_CACHE);
  const cachedResponse = await cache.match(request);

  // Fetch from network in background
  const fetchPromise = fetch(request)
    .then(networkResponse => {
      if (networkResponse.ok) {
        cache.put(request, networkResponse.clone());
      }
      return networkResponse;
    })
    .catch(async () => {
      // On network error, return offline page for navigation
      if (request.mode === 'navigate') {
        return caches.match('/offline.html');
      }
      return null;
    });

  // Return cached response immediately, or wait for network
  if (cachedResponse) {
    return cachedResponse;
  }

  const networkResponse = await fetchPromise;
  if (networkResponse) {
    return networkResponse;
  }

  // Last resort - offline page
  const offlinePage = await caches.match('/offline.html');
  if (offlinePage) {
    return offlinePage;
  }

  return new Response('Offline', { status: 503 });
}

/**
 * Message handler for cache management
 */
sw.addEventListener('message', (event: ExtendableMessageEvent) => {
  if (event.data?.type === 'SKIP_WAITING') {
    sw.skipWaiting();
  }

  if (event.data?.type === 'CACHE_URLS') {
    const urls = event.data.urls as string[];
    event.waitUntil(
      caches.open(RUNTIME_CACHE).then(cache => {
        return Promise.all(
          urls.map(url =>
            cache.add(url).catch(err => {
              console.warn(`[SW] Failed to cache ${url}:`, err);
            })
          )
        );
      })
    );
  }

  if (event.data?.type === 'CLEAR_CACHE') {
    event.waitUntil(
      caches.keys().then(cacheNames => {
        return Promise.all(
          cacheNames
            .filter(name => name.startsWith('lwt-'))
            .map(name => caches.delete(name))
        );
      })
    );
  }
});
