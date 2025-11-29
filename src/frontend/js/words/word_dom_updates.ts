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

import $ from 'jquery';
import { make_tooltip } from '../terms/word_status';
import { cleanupRightFrames } from '../reading/frame_management';
import { LWT_DATA } from '../core/lwt_state';

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
 * Check if jQuery tooltips are enabled in settings.
 */
export function isJQueryTooltipEnabled(): boolean {
  try {
    return window.parent?.LWT_DATA?.settings?.jQuery_tooltip ?? false;
  } catch {
    return LWT_DATA?.settings?.jQuery_tooltip ?? false;
  }
}

/**
 * Update the learn status counter in the header frame.
 *
 * @param content The HTML content to display in the learn status element
 */
export function updateLearnStatus(content: string): void {
  const context = getParentContext();
  const learnStatus = $('#learnstatus', context);
  if (learnStatus.length) {
    learnStatus.html(content);
  }
}

/**
 * Generate a tooltip for a word if jQuery tooltips are enabled.
 *
 * @param word The word text
 * @param translation The translation
 * @param romanization The romanization
 * @param status The word status
 * @returns The tooltip string, or empty string if tooltips are disabled
 */
export function generateTooltip(
  word: string,
  translation: string,
  romanization: string,
  status: number | string
): string {
  if (isJQueryTooltipEnabled()) {
    return '';
  }
  return make_tooltip(word, translation, romanization, status);
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

  $(`.TERM${hex}`, context)
    .removeClass('status0')
    .addClass(`word${wid} status${status}`)
    .attr('data_trans', translation)
    .attr('data_rom', romanization)
    .attr('data_status', String(status))
    .attr('data_wid', String(wid))
    .attr('title', title);
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

  $(`.word${wid}`, context)
    .removeClass(`status${oldStatus}`)
    .addClass(`status${status}`)
    .attr('data_trans', translation)
    .attr('data_rom', romanization)
    .attr('data_status', String(status))
    .attr('title', title);
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

  $(`.word${wid}`, frameL)
    .removeClass('status98 status99 status1 status2 status3 status4 status5')
    .addClass(`status${status}`)
    .attr('data_status', String(status))
    .attr('title', title);
}

/**
 * Delete a word from the DOM (reset to unknown/status0 state).
 *
 * @param wid Word ID
 * @param term Term text
 */
export function deleteWordFromDOM(wid: number, term: string): void {
  const context = getParentContext();
  const elem = $(`.word${wid}`, context);

  let title = '';
  if (!isJQueryTooltipEnabled()) {
    const ann = elem.attr('data_ann') ?? '';
    const trans = elem.attr('data_trans') ?? '';
    const rom = elem.attr('data_rom') ?? '';
    const combinedTrans = ann + (ann ? ' / ' : '') + trans;
    title = make_tooltip(term, combinedTrans, rom, '0');
  }

  elem
    .removeClass('status99 status98 status1 status2 status3 status4 status5 word' + wid)
    .addClass('status0')
    .attr('data_status', '0')
    .attr('data_trans', '')
    .attr('data_rom', '')
    .attr('data_wid', '')
    .attr('title', title)
    .removeAttr('data_img');
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

  const title = make_tooltip(term, '*', '', '99');

  $(`.TERM${hex}`, frameL)
    .removeClass('status0')
    .addClass(`status99 word${wid}`)
    .attr('data_status', '99')
    .attr('data_wid', String(wid))
    .attr('title', title);
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

  const title = make_tooltip(term, '*', '', '98');

  $(`.TERM${hex}`, frameL)
    .removeClass('status0')
    .addClass(`status98 word${wid}`)
    .attr('data_status', '98')
    .attr('data_wid', String(wid))
    .attr('title', title);
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

  $(`.word${wid}`, context)
    .attr('data_trans', translation)
    .attr('data_rom', romanization)
    .attr('title', title)
    .removeClass(`status${oldStatus}`)
    .addClass(`status${status}`)
    .attr('data_status', String(status));
}

/**
 * Delete a multi-word expression from the DOM.
 *
 * @param wid Word ID
 * @param showAll Whether to show all words (affects visibility of sub-words)
 */
export function deleteMultiWordFromDOM(wid: number, showAll: boolean): void {
  const context = getParentContext();

  $(`.word${wid}`, context).each(function () {
    const sid = $(this).parent();
    $(this).remove();

    if (!showAll) {
      sid.find('*').removeClass('hide');
      sid.find('.mword').each(function () {
        if ($(this).not('.hide').length) {
          const code = parseInt($(this).attr('data_code') ?? '0', 10);
          const order = parseInt($(this).attr('data_order') ?? '0', 10);
          const u = code * 2 + order - 1;
          $(this).nextUntil(`[id^="ID-${u}-"]`).addClass('hide');
        }
      });
    }
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

  $(`.TERM${term.hex}`, context)
    .removeClass('status0')
    .addClass(`status${term.WoStatus}`)
    .addClass(`word${term.WoID}`)
    .attr('data_wid', String(term.WoID))
    .attr('data_status', String(term.WoStatus))
    .attr('data_trans', term.translation);

  if (useTooltip) {
    $(`.TERM${term.hex}`, context).each(function () {
      const $el = $(this);
      this.title = make_tooltip(
        $el.text(),
        $el.attr('data_trans') ?? '',
        $el.attr('data_rom') ?? '',
        $el.attr('data_status') ?? '0'
      );
    });
  } else {
    $(`.TERM${term.hex}`, context).attr('title', '');
  }
}

/**
 * Update word for hover save operation.
 *
 * @param wid Word ID
 * @param hex Hex class identifier
 * @param status Word status
 * @param translation Translation text
 * @param wordRaw Raw word text
 */
export function updateHoverSaveInDOM(
  wid: number,
  hex: string,
  status: number | string,
  translation: string,
  wordRaw: string
): void {
  const context = getParentContext();

  let title = '';
  if (isJQueryTooltipEnabled()) {
    title = make_tooltip(wordRaw, translation, '', String(status));
  }

  $(`.TERM${hex}`, context)
    .removeClass('status0')
    .addClass(`status${status} word${wid}`)
    .attr('data_status', String(status))
    .attr('data_wid', String(wid))
    .attr('title', title)
    .attr('data_trans', translation);
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

  $(`.word${wid}`, context)
    .attr('data_text', text)
    .attr('data_trans', translation)
    .attr('data_rom', romanization)
    .attr('data_status', String(status));
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
