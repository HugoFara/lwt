/**
 * Client-side navigation for the bundled ("Model B") client.
 *
 * In the bundled app there is no PHP server serving pages: the reader, library
 * and connect surfaces are static HTML shipped in the APK and served from the
 * WebView root. The existing LWT frontend, however, emits server-relative links
 * (e.g. `/text/42/read`, `/texts`, `/connect`). This module rewrites those at
 * click time:
 *
 *   - links to a surface we bundle      -> the local page (read/library/connect)
 *   - other in-app server links         -> the *remote* server's web UI
 *     (edit, review, imports, settings…), so they keep working via the WebView
 *     even though they are not bundled yet (the hybrid that the roadmap calls
 *     "partition bundled-vs-WebView routes")
 *   - absolute URLs / target=_blank     -> left untouched (open as-is)
 *
 * @license Unlicense <http://unlicense.org/>
 */

import { getApiServer } from '@shared/api/client';

/** Builders for the bundled pages (relative to the current page at the root). */
export const pageUrl = {
  connect(): string {
    return 'index.html';
  },
  library(): string {
    return 'library.html';
  },
  read(textId: number | string, langId?: number | string): string {
    const lang = langId ? `&lang=${encodeURIComponent(String(langId))}` : '';
    return `read.html?text=${encodeURIComponent(String(textId))}${lang}`;
  },
  review(params: URLSearchParams): string {
    const query = params.toString();
    return query ? `review.html?${query}` : 'review.html';
  }
};

/**
 * Map a server-relative path to a bundled page URL, or null if no bundled page
 * covers it (caller falls back to the remote server).
 */
export function bundledPageFor(path: string): string | null {
  // Strip query/hash for matching; keep the original for param extraction.
  const [pathname, query = ''] = path.split('?');

  if (pathname === '/' || pathname === '/texts' || pathname === '/index.php') {
    return pageUrl.library();
  }
  if (pathname === '/connect') {
    return pageUrl.connect();
  }
  // /text/{id}/read
  const readMatch = pathname.match(/^\/text\/(\d+)\/read$/);
  if (readMatch) {
    const params = new URLSearchParams(query);
    return pageUrl.read(readMatch[1], params.get('lang') ?? undefined);
  }
  // /review[?text=|lang=|selection=] — carry the selection params through.
  if (pathname === '/review') {
    const params = new URLSearchParams();
    for (const key of ['text', 'lang', 'selection']) {
      const value = new URLSearchParams(query).get(key);
      if (value) params.set(key, value);
    }
    return pageUrl.review(params);
  }
  return null;
}

/** True for links that should never be intercepted (external, new-tab, etc.). */
function isExternalLink(anchor: HTMLAnchorElement, href: string): boolean {
  if (anchor.target && anchor.target !== '' && anchor.target !== '_self') {
    return true;
  }
  if (anchor.hasAttribute('download')) {
    return true;
  }
  // Absolute URL to somewhere other than our own (bundle) origin.
  if (/^[a-z]+:\/\//i.test(href) || href.startsWith('//')) {
    try {
      return new URL(href, window.location.href).origin !== window.location.origin;
    } catch {
      return true;
    }
  }
  if (href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) {
    return true;
  }
  return false;
}

/** Resolve an absolute href to a server-relative path ("/text/1/read?x=y"). */
function toServerPath(href: string): string {
  try {
    const u = new URL(href, window.location.href);
    return u.pathname + u.search + u.hash;
  } catch {
    return href;
  }
}

/**
 * Install a single delegated click handler that rewrites in-app navigation.
 * Idempotent: safe to call once per page.
 */
export function installLinkRouter(): void {
  document.addEventListener(
    'click',
    (event) => {
      if (event.defaultPrevented || event.button !== 0) return;
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

      const anchor = (event.target as Element | null)?.closest('a');
      if (!anchor) return;

      const href = anchor.getAttribute('href');
      if (href === null || href === '' || isExternalLink(anchor, href)) return;

      const path = toServerPath(href);
      const bundled = bundledPageFor(path);

      if (bundled) {
        event.preventDefault();
        window.location.assign(bundled);
        return;
      }

      // Not bundled: hand off to the remote server's web UI if one is known.
      const server = getApiServer();
      if (server && path.startsWith('/')) {
        event.preventDefault();
        window.location.assign(server.replace(/\/+$/, '') + path);
      }
      // No server configured: leave the link to default behavior.
    },
    // Capture so we run before component handlers on plain anchors.
    true
  );
}
