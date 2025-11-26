/**
 * LWT Javascript functions
 *
 * This file re-exports functions from specialized modules for backward compatibility,
 * and contains miscellaneous utility functions.
 *
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   1.6.16-fork
 * @since   2.10.0-fork Split into specialized modules
 *
 * "Learning with Texts" (LWT) is free and unencumbered software
 * released into the PUBLIC DOMAIN.
 */

// Re-export all functions from specialized modules for backward compatibility
export * from './word_status';
export * from './dictionary';
export * from './html_utils';
export * from './cookies';
export * from './bulk_actions';

// Declare external functions that are defined elsewhere
declare function showRightFrames(url1?: string, url2?: string): void;

/**************************************************************
 * Miscellaneous utility functions
 **************************************************************/

/**
 * Set the current language.
 *
 * @param ctl Current language selector element
 * @param url URL to redirect to
 */
export function setLang(ctl: HTMLSelectElement, url: string): void {
  location.href = 'inc/save_setting_redirect.php?k=currentlanguage&v=' +
    ctl.options[ctl.selectedIndex].value +
    '&u=' + url;
}

/**
 * Reset current language to default.
 *
 * @param url URL to redirect to
 */
export function resetAll(url: string): void {
  location.href = 'inc/save_setting_redirect.php?k=currentlanguage&v=&u=' + url;
}

/**
 * Prepare a window to make all words from a text well-known
 *
 * @param t Text ID
 */
export function iknowall(t: string | number): void {
  const answer = confirm('Are you sure?');
  if (answer) {
    showRightFrames('all_words_wellknown.php?text=' + t);
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

// Expose globally for backward compatibility with PHP templates
if (typeof window !== 'undefined') {
  const w = window as unknown as Record<string, unknown>;
  w.setLang = setLang;
  w.resetAll = resetAll;
  w.iknowall = iknowall;
  w.check_table_prefix = check_table_prefix;
}
