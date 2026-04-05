/**
 * Frontend i18n translator.
 *
 * Translations are injected by the server as a JSON blob in a
 * `<script type="application/json" id="lwt-i18n">` element.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

type TranslationMessages = Record<string, string>;

let messages: TranslationMessages = {};
let initialized = false;

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
