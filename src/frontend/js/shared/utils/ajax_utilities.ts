/**
 * AJAX Utilities - Common AJAX operations and settings
 *
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 */

import { SettingsApi } from '@modules/admin/api/settings_api';

/**
 * Save a setting to the database.
 *
 * @param k Setting name as a key
 * @param v Setting value
 */
export function saveSetting(k: string, v: string): void {
  SettingsApi.save(k, v);
}

/**
 * Force the user scrolling to an anchor.
 *
 * @param aid Anchor ID
 */
export function scrollToAnchor(aid: string): void {
  document.location.href = '#' + aid;
}

/**
 * Extract position number from an HTML element ID string.
 *
 * @param id_string HTML element ID containing position information (e.g., "ID-3-42")
 * @returns Position number extracted from the ID, or -1 if undefined/invalid
 */
export function getPositionFromId(id_string: string): number {
  if (typeof id_string === 'undefined') return -1;
  const arr = id_string.split('-');
  return parseInt(arr[1], 10) * 10 + 10 - parseInt(arr[2], 10);
}

/**
 * Assign the display value of a select element to the value element of another input.
 *
 * @param select_elem
 * @param input_elem
 */
export function copySelectValueToInput(select_elem: HTMLSelectElement, input_elem: HTMLInputElement): void {
  const val = select_elem.options[select_elem.selectedIndex].value;
  if (val !== '') { input_elem.value = val; }
  select_elem.value = '';
}

