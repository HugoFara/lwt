/**
 * Language settings utilities.
 *
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   2.10.0-fork Extracted from legacy/pgm.ts
 */

import { loadModalFrame } from '../reading/frame_management';

/**
 * Statistics for a text showing word status counts.
 */
export interface TextStats {
  unknown: number;
  s1: number;
  s2: number;
  s3: number;
  s4: number;
  s5: number;
  s98: number;
  s99: number;
  total: number;
}

/**
 * Response from the settings API when changing language.
 */
export interface LanguageChangeResponse {
  message?: string;
  error?: string;
  last_text?: {
    id: number;
    title: string;
    language_id: number;
    language_name: string;
    annotated: boolean;
    stats?: TextStats;
  } | null;
}

/**
 * Custom event dispatched when language changes via AJAX.
 */
export interface LanguageChangedEvent extends CustomEvent {
  detail: {
    languageId: string;
    languageName: string;
    response: LanguageChangeResponse;
  };
}

/**
 * Set the current language via redirect.
 *
 * @param ctl Current language selector element
 * @param url URL to redirect to
 */
export function setLang(ctl: HTMLSelectElement, url: string): void {
  location.href = '/admin/save-setting?k=currentlanguage&v=' +
    ctl.options[ctl.selectedIndex].value +
    '&u=' + url;
}

/**
 * Set the current language via AJAX (no page refresh).
 *
 * @param languageId The language ID to set
 * @returns Promise with the API response
 */
export async function setLangAsync(languageId: string): Promise<LanguageChangeResponse> {
  const response = await fetch('/api/v1/settings', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      key: 'currentlanguage',
      value: languageId
    })
  });

  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }

  return response.json();
}

/**
 * Initialize event delegation for language setting elements.
 *
 * Handles elements with data-action="set-lang".
 * Uses AJAX when data-ajax="true" is present.
 */
function initSetLangEventDelegation(): void {
  document.addEventListener('change', async function (e) {
    const target = e.target as HTMLElement;
    if (target.matches('[data-action="set-lang"]')) {
      const selectEl = target as HTMLSelectElement;
      const useAjax = selectEl.dataset.ajax === 'true';
      const redirectUrl = selectEl.dataset.redirect || '/';
      const languageId = selectEl.options[selectEl.selectedIndex].value;
      const languageName = selectEl.options[selectEl.selectedIndex].text;

      if (useAjax) {
        try {
          const response = await setLangAsync(languageId);

          // Dispatch custom event for components to react to
          const event = new CustomEvent('lwt:languageChanged', {
            detail: {
              languageId,
              languageName,
              response
            }
          }) as LanguageChangedEvent;
          document.dispatchEvent(event);
        } catch (error) {
          console.error('Failed to change language:', error);
          // Fall back to redirect
          setLang(selectEl, redirectUrl);
        }
      } else {
        setLang(selectEl, redirectUrl);
      }
    }
  });
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
  initSetLangEventDelegation();
});

/**
 * Reset current language to default.
 *
 * @param url URL to redirect to
 */
export function resetAll(url: string): void {
  location.href = '/admin/save-setting?k=currentlanguage&v=&u=' + url;
}

/**
 * Prepare a window to make all words from a text well-known
 *
 * @param t Text ID
 */
export function iknowall(t: string | number): void {
  const answer = confirm('Are you sure?');
  if (answer) {
    loadModalFrame('all_words_wellknown.php?text=' + t);
  }
}

/**
 * Check is the table prefix is a valid alphanumeric character.
 * Create an alert if not.
 *
 * @param p Table prefix
 * @returns true is the prefix is valid
 */
export function check_table_prefix(p: string): boolean {
  const re = /^[_a-zA-Z0-9]*$/;
  const r = p.length <= 20 && p.length > 0 && re.test(p);
  if (!r) {
    alert(
      'Table Set Name (= Table Prefix) must' +
      '\ncontain 1 to 20 characters (only 0-9, a-z, A-Z and _).' +
      '\nPlease correct your input.'
    );
  }
  return r;
}
