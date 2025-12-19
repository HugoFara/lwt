/**
 * Word status utility functions for LWT.
 *
 * Functions for working with word learning status (1-5, 98=ignored, 99=well-known).
 *
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   2.10.0-fork Extracted from pgm.ts
 */

import { statuses } from '../core/app_data';

/**
 * Return the name of a given status.
 *
 * @param status Status number (int<1, 5>|98|99)
 * @returns Status name
 */
export function getStatusName(status: number | string): string {
  const key = typeof status === 'string' ? parseInt(status, 10) : status;
  return statuses[key] ? statuses[key].name : 'Unknown';
}

/**
 * Return the abbreviation of a status
 *
 * @param status Status number (int<1, 5>|98|99)
 * @returns Abbreviation
 */
export function getStatusAbbr(status: number | string): string {
  const key = typeof status === 'string' ? parseInt(status, 10) : status;
  return statuses[key] ? statuses[key].abbr : '?';
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

