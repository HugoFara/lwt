/**
 * Word DOM Updates - Functions to update word elements in the reading frame
 *
 * This module contains functions to update word status, translations, and
 * other attributes in the DOM when words are saved, updated, or deleted.
 * These functions are called from result views after word operations complete.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { createWordTooltip } from '@modules/vocabulary/services/word_status';
import { cleanupRightFrames } from '@modules/text/pages/reading/frame_management';

/**
 * Get the parent document context (for frame-based layouts).
 * Falls back to current document if parent is not accessible.
 */
export function getParentContext(): Document {
  try {
    return window.parent?.document ?? document;
  } catch {
    return document;
  }
}

/**
 * Get a specific frame element from the parent context.
 *
 * @param frameId The ID of the frame element (e.g., 'frame-l', 'frame-h')
 */
export function getFrameElement(frameId: string): HTMLElement | null {
  const context = getParentContext();
  return context.getElementById(frameId);
}

/**
 * Update the learn status counter in the header frame.
 *
 * @param content The HTML content to display in the learn status element
 */
export function updateLearnStatus(content: string): void {
  const context = getParentContext();
  const learnStatus = context.getElementById('learnstatus');
  if (learnStatus) {
    learnStatus.innerHTML = content;
  }
}

/**
 * Generate a native tooltip for a word.
 *
 * @param word The word text
 * @param translation The translation
 * @param romanization The romanization
 * @param status The word status
 * @returns The tooltip string
 */
export function generateTooltip(
  word: string,
  translation: string,
  romanization: string,
  status: number | string
): string {
  return createWordTooltip(word, translation, romanization, status);
}

export interface WordUpdateParams {
  wid: number;
  status: number | string;
  translation: string;
  romanization: string;
  text: string;
  hex?: string;
}

/**
 * Update a new word in the DOM (word that was just created).
 * Transforms status0 elements with the term's hex class to the saved word state.
 *
 * @param params Word update parameters
 */
export function updateNewWordInDOM(params: WordUpdateParams): void {
  const { wid, status, translation, romanization, text, hex } = params;
  if (!hex) return;

  const context = getParentContext();
  const title = generateTooltip(text, translation, romanization, status);

  context.querySelectorAll<HTMLElement>(`[data_hex="${hex}"]`).forEach(el => {
    el.classList.remove('status0');
    el.classList.add(`word${wid}`, `status${status}`);
    el.setAttribute('data_trans', translation);
    el.setAttribute('data_rom', romanization);
    el.setAttribute('data_status', String(status));
    el.setAttribute('data_wid', String(wid));
    el.title = title;
  });
}

/**
 * Update an existing word in the DOM (word that was modified).
 * Updates elements with the word's ID class.
 *
 * @param params Word update parameters
 * @param oldStatus The previous status value
 */
export function updateExistingWordInDOM(params: WordUpdateParams, oldStatus: number | string): void {
  const { wid, status, translation, romanization, text } = params;
  const context = getParentContext();
  const title = generateTooltip(text, translation, romanization, status);

  context.querySelectorAll<HTMLElement>(`.word${wid}`).forEach(el => {
    el.classList.remove(`status${oldStatus}`);
    el.classList.add(`status${status}`);
    el.setAttribute('data_trans', translation);
    el.setAttribute('data_rom', romanization);
    el.setAttribute('data_status', String(status));
    el.title = title;
  });
}

/**
 * Update word status in the DOM without changing translation/romanization.
 *
 * @param wid Word ID
 * @param status New status
 * @param word Word text
 * @param translation Translation text
 * @param romanization Romanization text
 */
export function updateWordStatusInDOM(
  wid: number,
  status: number | string,
  word: string,
  translation: string,
  romanization: string
): void {
  const frameL = getFrameElement('frame-l');
  if (!frameL) return;

  const title = generateTooltip(word, translation, romanization, status);

  frameL.querySelectorAll<HTMLElement>(`.word${wid}`).forEach(el => {
    el.classList.remove('status98', 'status99', 'status1', 'status2', 'status3', 'status4', 'status5');
    el.classList.add(`status${status}`);
    el.setAttribute('data_status', String(status));
    el.title = title;
  });
}

/**
 * Mark a word as well-known (status 99) in the DOM.
 *
 * @param wid Word ID
 * @param hex Hex class identifier for the term
 * @param term Term text
 */
export function markWordWellKnownInDOM(wid: number, hex: string, term: string): void {
  const frameL = getFrameElement('frame-l');
  if (!frameL) return;

  const title = createWordTooltip(term, '*', '', '99');

  frameL.querySelectorAll<HTMLElement>(`[data_hex="${hex}"]`).forEach(el => {
    el.classList.remove('status0');
    el.classList.add('status99', `word${wid}`);
    el.setAttribute('data_status', '99');
    el.setAttribute('data_wid', String(wid));
    el.title = title;
  });
}

/**
 * Mark a word as ignored (status 98) in the DOM.
 *
 * @param wid Word ID
 * @param hex Hex class identifier for the term
 * @param term Term text
 */
export function markWordIgnoredInDOM(wid: number, hex: string, term: string): void {
  const frameL = getFrameElement('frame-l');
  if (!frameL) return;

  const title = createWordTooltip(term, '*', '', '98');

  frameL.querySelectorAll<HTMLElement>(`[data_hex="${hex}"]`).forEach(el => {
    el.classList.remove('status0');
    el.classList.add('status98', `word${wid}`);
    el.setAttribute('data_status', '98');
    el.setAttribute('data_wid', String(wid));
    el.title = title;
  });
}

/**
 * Update a multi-word expression in the DOM.
 *
 * @param wid Word ID
 * @param text Term text
 * @param translation Translation
 * @param romanization Romanization
 * @param status New status
 * @param oldStatus Previous status
 */
export function updateMultiWordInDOM(
  wid: number,
  text: string,
  translation: string,
  romanization: string,
  status: number | string,
  oldStatus: number | string
): void {
  const context = getParentContext();
  const title = generateTooltip(text, translation, romanization, status);

  context.querySelectorAll<HTMLElement>(`.word${wid}`).forEach(el => {
    el.setAttribute('data_trans', translation);
    el.setAttribute('data_rom', romanization);
    el.title = title;
    el.classList.remove(`status${oldStatus}`);
    el.classList.add(`status${status}`);
    el.setAttribute('data_status', String(status));
  });
}

export interface BulkWordUpdateParams {
  WoID: number;
  WoTextLC: string;
  WoStatus: number | string;
  translation: string;
  hex: string;
}

/**
 * Update a word from bulk translate operation in the DOM.
 *
 * @param term The term data
 * @param useTooltip Whether to generate tooltips
 */
export function updateBulkWordInDOM(term: BulkWordUpdateParams, useTooltip: boolean): void {
  const context = getParentContext();

  context.querySelectorAll<HTMLElement>(`[data_hex="${term.hex}"]`).forEach(el => {
    el.classList.remove('status0');
    el.classList.add(`status${term.WoStatus}`, `word${term.WoID}`);
    el.setAttribute('data_wid', String(term.WoID));
    el.setAttribute('data_status', String(term.WoStatus));
    el.setAttribute('data_trans', term.translation);

    if (useTooltip) {
      el.title = createWordTooltip(
        el.textContent || '',
        el.getAttribute('data_trans') ?? '',
        el.getAttribute('data_rom') ?? '',
        el.getAttribute('data_status') ?? '0'
      );
    } else {
      el.title = '';
    }
  });
}

/**
 * Update word data attributes for test result views.
 *
 * @param wid Word ID
 * @param text Word text
 * @param translation Translation
 * @param romanization Romanization
 * @param status Status
 */
export function updateTestWordInDOM(
  wid: number,
  text: string,
  translation: string,
  romanization: string,
  status: number | string
): void {
  const context = getParentContext();

  context.querySelectorAll<HTMLElement>(`.word${wid}`).forEach(el => {
    el.setAttribute('data_text', text);
    el.setAttribute('data_trans', translation);
    el.setAttribute('data_rom', romanization);
    el.setAttribute('data_status', String(status));
  });
}

/**
 * Complete a word operation by updating learn status and cleaning up.
 *
 * @param todoContent HTML content for the learn status counter
 * @param shouldCleanup Whether to call cleanupRightFrames
 */
export function completeWordOperation(todoContent: string, shouldCleanup: boolean = true): void {
  updateLearnStatus(todoContent);
  if (shouldCleanup) {
    cleanupRightFrames();
  }
}
