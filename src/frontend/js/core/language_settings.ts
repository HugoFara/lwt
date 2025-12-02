/**
 * Language settings utilities.
 *
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   2.10.0-fork Extracted from legacy/pgm.ts
 */

import { loadModalFrame } from '../reading/frame_management';

/**
 * Set the current language.
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
 * Initialize event delegation for language setting elements.
 *
 * Handles elements with data-action="set-lang".
 */
function initSetLangEventDelegation(): void {
  document.addEventListener('change', function (e) {
    const target = e.target as HTMLElement;
    if (target.matches('[data-action="set-lang"]')) {
      const selectEl = target as HTMLSelectElement;
      const redirectUrl = selectEl.dataset.redirect || '/';
      setLang(selectEl, redirectUrl);
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
