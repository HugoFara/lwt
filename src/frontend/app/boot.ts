/**
 * Shared bootstrap for every bundled ("Model B") page.
 *
 * Each page's TS entry (connect/library/read) injects its runtime config blob,
 * then calls {@link bootAppPage}, which:
 *   1. gates pages that need a session (bounce to connect when no token),
 *   2. routes a mid-session `lwt:auth-expired` back to the local connect page
 *      (instead of main.ts's server-relative `/connect`, which 404s here),
 *   3. installs the link router (server hrefs -> local pages / remote UI),
 *   4. hands off to the existing app bundle (`src/frontend/js/main.ts`), which
 *      reads `<meta name="lwt-modules">`, boots i18n, and starts Alpine.
 *
 * Server URL + bearer token already live in localStorage (set by the connect
 * flow), and `@shared/api/client` reads them automatically — so once connected,
 * every page talks to the chosen remote server with no extra wiring.
 *
 * @license Unlicense <http://unlicense.org/>
 */

import { getAuthToken, isAuthOptional } from '@shared/api/client';
import { installLinkRouter, pageUrl } from './router';

export interface BootOptions {
  /** Surfaces require a signed-in session; the connect page does not. */
  requireAuth: boolean;
}

/**
 * Inject (or replace) a JSON config blob the way the PHP views emit it, so the
 * Alpine components read their runtime config from the same `<script>` ids.
 */
export function injectConfig(id: string, config: unknown): void {
  let el = document.getElementById(id);
  if (!el) {
    const script = document.createElement('script');
    script.type = 'application/json';
    script.id = id;
    document.body.appendChild(script);
    el = script;
  }
  el.textContent = JSON.stringify(config);
}

/** Replace `{{TEXT_ID}}` / `{{LANG_ID}}` tokens left in prerendered hrefs. */
export function fillIdTokens(textId: number, langId: number): void {
  document.querySelectorAll<HTMLAnchorElement>('a[href*="{{"]').forEach((a) => {
    const href = a.getAttribute('href');
    if (!href) return;
    a.setAttribute(
      'href',
      href.replace(/\{\{TEXT_ID\}\}/g, String(textId)).replace(/\{\{LANG_ID\}\}/g, String(langId))
    );
  });
}

export async function bootAppPage(opts: BootOptions): Promise<void> {
  // 1. Auth gate. A bearer token (multi-user) OR a server known to need no
  //    login (single-user self-host, see probeAuthRequirement) both pass.
  if (opts.requireAuth && !getAuthToken() && !isAuthOptional()) {
    window.location.replace(pageUrl.connect());
    return;
  }

  // 2. Re-route auth-expired to the local connect page. Registered before
  //    main.ts adds its own (server-relative) handler; stopImmediatePropagation
  //    keeps that one — which would 404 in the bundle — from also firing.
  document.addEventListener('lwt:auth-expired', (event) => {
    event.stopImmediatePropagation();
    window.location.replace(pageUrl.connect());
  });

  // 3. Rewrite in-app links.
  installLinkRouter();

  // 4. Hand off to the shared app bundle (reads the static lwt-modules meta,
  //    boots i18n from the API/cache, starts Alpine). Dynamic import so this
  //    setup runs first.
  await import('@/main');
}
