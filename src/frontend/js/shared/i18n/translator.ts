/**
 * Frontend i18n translator.
 *
 * Two delivery paths, both producing the same flat "namespace.key" => string
 * map:
 *  1. Server-injected blob — a `<script id="lwt-i18n">` element on
 *     server-rendered pages (the default for the web app).
 *  2. API fetch — `GET /api/v1/i18n/{locale}`, cached in localStorage, for a
 *     configurable/offline client that has no server-rendered shell.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { apiGet } from '@shared/api/client';

type TranslationMessages = Record<string, string>;

interface I18nBundle {
  locale: string;
  messages: TranslationMessages;
}

/** localStorage key prefix for cached per-locale API bundles. */
const CACHE_PREFIX = 'lwt.i18n.';

let messages: TranslationMessages = {};
let initialized = false;

/**
 * Merge additional strings into the active message map (later wins).
 *
 * Lets the blob and one or more API bundles coexist — e.g. a server-rendered
 * page that later pulls an extra namespace from the API.
 */
function mergeMessages(extra: TranslationMessages): void {
  messages = { ...messages, ...extra };
  initialized = true;
}

/**
 * Initialize translations from the server-injected JSON blob.
 *
 * Safe to call multiple times — only parses once.
 */
export function initI18n(): void {
  if (initialized) return;
  const el = document.getElementById('lwt-i18n');
  if (el?.textContent) {
    try {
      messages = JSON.parse(el.textContent) as TranslationMessages;
    } catch (e) {
      console.error('Failed to parse i18n data:', e);
    }
  }
  initialized = true;
}

/**
 * Synchronously hydrate strings from a cached API bundle for `locale`.
 *
 * Used at startup by a shell-free client so the first paint has strings
 * without waiting on the network; follow with {@link loadI18nFromApi} to
 * refresh in the background.
 *
 * @param locale - Locale code (e.g. "es")
 * @returns true if a cached bundle was found and applied
 */
export function hydrateI18nFromCache(locale: string): boolean {
  try {
    const raw = localStorage.getItem(CACHE_PREFIX + locale);
    if (!raw) return false;
    mergeMessages(JSON.parse(raw) as TranslationMessages);
    return true;
  } catch {
    return false;
  }
}

/**
 * Fetch the translation bundle for `locale` from the API and cache it.
 *
 * Merges the result into the active strings and persists it in localStorage
 * so a later launch can hydrate synchronously. Never throws — returns false
 * on any transport/parse failure, leaving existing strings intact.
 *
 * @param locale     - Locale code (e.g. "es"); omit to use the server default
 * @param namespaces - Optional namespace allowlist (default: all)
 * @returns true if a bundle was fetched and applied
 */
export async function loadI18nFromApi(
  locale?: string,
  namespaces?: string[]
): Promise<boolean> {
  const path = locale ? `/i18n/${encodeURIComponent(locale)}` : '/i18n';
  const params = namespaces?.length ? { namespaces: namespaces.join(',') } : undefined;

  const res = await apiGet<I18nBundle>(path, params);
  const bundle = res.data;
  if (!bundle?.messages) return false;

  mergeMessages(bundle.messages);

  // Cache under the resolved locale so hydration can find it next launch.
  try {
    localStorage.setItem(
      CACHE_PREFIX + bundle.locale,
      JSON.stringify(bundle.messages)
    );
  } catch {
    // Storage full or unavailable — strings still work for this session.
  }

  return true;
}

/**
 * Translate a dot-notated key with optional parameter interpolation.
 *
 * @param key    - Translation key (e.g. "common.save")
 * @param params - Interpolation parameters (e.g. { count: 5 })
 * @returns Translated string, or the raw key if not found
 */
export function t(key: string, params?: Record<string, string | number>): string {
  if (!initialized) initI18n();

  let text = messages[key] ?? key;

  if (params) {
    for (const [k, v] of Object.entries(params)) {
      text = text.split(`{${k}}`).join(String(v));
    }
  }

  return text;
}
