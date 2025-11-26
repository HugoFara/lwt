/**
 * Word status utility functions for LWT.
 *
 * Functions for working with word learning status (1-5, 98=ignored, 99=well-known).
 *
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   2.10.0-fork Extracted from pgm.ts
 */

import type { WordStatus } from './types/globals';

/**
 * Return the name of a given status.
 *
 * @param status Status number (int<1, 5>|98|99)
 * @returns Status name
 */
export function getStatusName(status: number | string): string {
  const statuses: Record<string, WordStatus> = window.STATUSES || {};
  return statuses[status] ? statuses[status].name : 'Unknown';
}

/**
 * Return the abbreviation of a status
 *
 * @param status Status number (int<1, 5>|98|99)
 * @returns Abbreviation
 */
export function getStatusAbbr(status: number | string): string {
  const statuses: Record<string, WordStatus> = window.STATUSES || {};
  return statuses[status] ? statuses[status].abbr : '?';
}

/**
 * Return a tooltip, a short string describing the word (word, translation,
 * romanization and learning status)
 *
 * @param word   The word
 * @param trans  Translation of the word
 * @param roman  Romanized version
 * @param status Learning status of the word
 * @returns Tooltip for this word
 */
export function make_tooltip(word: string, trans: string, roman: string, status: number | string): string {
  const nl = '\x0d';
  let title = word;
  if (roman !== '') {
    if (title !== '') title += nl;
    title += '▶ ' + roman;
  }
  if (trans !== '' && trans !== '*') {
    if (title !== '') title += nl;
    title += '▶ ' + trans;
  }
  if (title !== '') title += nl;
  title += '▶ ' + getStatusName(status) + ' [' +
    getStatusAbbr(status) + ']';
  return title;
}

// Expose globally for backward compatibility with PHP templates
if (typeof window !== 'undefined') {
  const w = window as unknown as Record<string, unknown>;
  w.getStatusName = getStatusName;
  w.getStatusAbbr = getStatusAbbr;
  w.make_tooltip = make_tooltip;
}
