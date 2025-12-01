/**
 * Word Actions Module - Handles word operations via API instead of frame navigation.
 *
 * This module provides functions to change word status, delete words, and create
 * quick terms (well-known/ignored) using the REST API instead of navigating frames.
 *
 * Part of Phase 4: Frame Architecture Removal
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { TermsApi } from '../api/terms';
import { ReviewApi } from '../api/review';
import {
  updateWordStatusInDOM,
  deleteWordFromDOM,
  markWordWellKnownInDOM,
  markWordIgnoredInDOM,
  updateLearnStatus
} from '../words/word_dom_updates';
import { cleanupRightFrames, successSound, failureSound } from './frame_management';
import { cClick } from '../ui/word_popup';
import { showResultPanel, hideResultPanel, showErrorInPanel } from '../ui/result_panel';

/**
 * Context for word actions - contains all data needed to perform operations.
 */
export interface WordActionContext {
  /** Text ID containing the word */
  textId: number;
  /** Word ID (undefined for unknown words) */
  wordId?: number;
  /** Word position/order in text */
  position: number;
  /** The word text itself */
  text: string;
  /** Hex class identifier for the term (for DOM updates) */
  hex?: string;
  /** Current word status */
  status?: number;
  /** Word translation */
  translation?: string;
  /** Word romanization */
  romanization?: string;
}

/**
 * Result of a word action operation.
 */
export interface WordActionResult {
  success: boolean;
  error?: string;
  newStatus?: number;
  wordId?: number;
}

/**
 * Change word status via API and update DOM.
 *
 * @param context Word context containing IDs and current data
 * @param newStatus The new status to set (1-5, 98, 99)
 * @returns Promise with operation result
 */
export async function changeWordStatus(
  context: WordActionContext,
  newStatus: number
): Promise<WordActionResult> {
  if (!context.wordId) {
    return { success: false, error: 'No word ID for status change' };
  }

  const response = await TermsApi.setStatus(context.wordId, newStatus);

  if (response.error) {
    showErrorInPanel(response.error);
    return { success: false, error: response.error };
  }

  // Update DOM to reflect new status
  updateWordStatusInDOM(
    context.wordId,
    newStatus,
    context.text,
    context.translation || '',
    context.romanization || ''
  );

  // Show success message in result panel
  showResultPanel(
    `Status changed to ${getStatusLabel(newStatus)}`,
    { autoClose: true, duration: 1500 }
  );

  // Close popup after status change
  cClick();

  return { success: true, newStatus };
}

/**
 * Increment or decrement word status via API.
 * Used primarily in test/review mode.
 *
 * @param context Word context
 * @param direction 'up' to increment, 'down' to decrement
 * @returns Promise with operation result
 */
export async function incrementWordStatus(
  context: WordActionContext,
  direction: 'up' | 'down'
): Promise<WordActionResult> {
  if (!context.wordId) {
    return { success: false, error: 'No word ID for status increment' };
  }

  const response = await TermsApi.incrementStatus(context.wordId, direction);

  if (response.error) {
    showErrorInPanel(response.error);
    return { success: false, error: response.error };
  }

  // Play appropriate sound
  if (direction === 'up') {
    successSound();
  } else {
    failureSound();
  }

  // Update learn status counter if returned
  if (response.data?.increment) {
    updateLearnStatus(response.data.increment);
  }

  return {
    success: true,
    newStatus: response.data?.set
  };
}

/**
 * Delete a word via API and update DOM.
 *
 * @param context Word context
 * @returns Promise with operation result
 */
export async function deleteWord(
  context: WordActionContext
): Promise<WordActionResult> {
  if (!context.wordId) {
    return { success: false, error: 'No word ID for deletion' };
  }

  const response = await TermsApi.delete(context.wordId);

  if (response.error) {
    showErrorInPanel(response.error);
    return { success: false, error: response.error };
  }

  // Update DOM to reset word to unknown state
  deleteWordFromDOM(context.wordId, context.text);

  showResultPanel('Term deleted', { autoClose: true, duration: 1500 });
  cClick();

  return { success: true };
}

/**
 * Mark an unknown word as well-known (status 99) via API.
 *
 * @param context Word context (must include hex for DOM update)
 * @returns Promise with operation result
 */
export async function markWellKnown(
  context: WordActionContext
): Promise<WordActionResult> {
  if (!context.hex) {
    return { success: false, error: 'No hex identifier for term' };
  }

  const response = await TermsApi.createQuick(
    context.textId,
    context.position,
    99
  );

  if (response.error) {
    showErrorInPanel(response.error);
    return { success: false, error: response.error };
  }

  const wordId = response.data?.term_id;
  if (wordId) {
    markWordWellKnownInDOM(wordId, context.hex, context.text);
  }

  showResultPanel('Marked as well-known', { autoClose: true, duration: 1500 });
  cClick();

  return { success: true, newStatus: 99, wordId };
}

/**
 * Mark an unknown word as ignored (status 98) via API.
 *
 * @param context Word context (must include hex for DOM update)
 * @returns Promise with operation result
 */
export async function markIgnored(
  context: WordActionContext
): Promise<WordActionResult> {
  if (!context.hex) {
    return { success: false, error: 'No hex identifier for term' };
  }

  const response = await TermsApi.createQuick(
    context.textId,
    context.position,
    98
  );

  if (response.error) {
    showErrorInPanel(response.error);
    return { success: false, error: response.error };
  }

  const wordId = response.data?.term_id;
  if (wordId) {
    markWordIgnoredInDOM(wordId, context.hex, context.text);
  }

  showResultPanel('Marked as ignored', { autoClose: true, duration: 1500 });
  cClick();

  return { success: true, newStatus: 98, wordId };
}

/**
 * Update word status during review/test mode.
 *
 * @param wordId Word ID
 * @param status Explicit status to set, or undefined for increment
 * @param change Status change amount (+1 or -1)
 * @returns Promise with operation result
 */
export async function updateReviewStatus(
  wordId: number,
  status?: number,
  change?: number
): Promise<WordActionResult> {
  const response = await ReviewApi.updateStatus(wordId, status, change);

  if (response.error) {
    showErrorInPanel(response.error);
    return { success: false, error: response.error };
  }

  // Play sound based on change direction
  if (change !== undefined) {
    if (change > 0) {
      successSound();
    } else {
      failureSound();
    }
  }

  return {
    success: true,
    newStatus: response.data?.status
  };
}

/**
 * Get human-readable label for a status value.
 *
 * @param status Status number
 * @returns Status label
 */
function getStatusLabel(status: number): string {
  switch (status) {
    case 1: return 'Level 1 (Learning)';
    case 2: return 'Level 2 (Learning)';
    case 3: return 'Level 3 (Learning)';
    case 4: return 'Level 4 (Learning)';
    case 5: return 'Level 5 (Learned)';
    case 98: return 'Ignored';
    case 99: return 'Well-known';
    default: return `Level ${status}`;
  }
}

/**
 * Extract WordActionContext from a word DOM element.
 *
 * @param element The word element (span with word class)
 * @returns WordActionContext with all available data
 */
export function getContextFromElement(element: HTMLElement): WordActionContext {
  // Extract hex from class name (e.g., 'TERMabc123' -> 'abc123')
  const classAttr = element.getAttribute('class') || '';
  const hexMatch = classAttr.match(/TERM([a-f0-9]+)/i);
  const hex = hexMatch ? hexMatch[1] : undefined;

  // Get text ID from element or closest ancestor
  const textIdAttr = element.getAttribute('data-text-id') ||
    element.closest('[data-text-id]')?.getAttribute('data-text-id') || '0';

  return {
    textId: parseInt(textIdAttr, 10),
    wordId: parseInt(element.getAttribute('data_wid') || '0', 10) || undefined,
    position: parseInt(element.getAttribute('data_order') || element.getAttribute('data_pos') || '0', 10),
    text: element.classList.contains('mwsty')
      ? (element.getAttribute('data_text') || element.textContent || '')
      : (element.textContent || ''),
    hex,
    status: parseInt(element.getAttribute('data_status') || '0', 10),
    translation: element.getAttribute('data_trans') || '',
    romanization: element.getAttribute('data_rom') || ''
  };
}

/**
 * Build word action context from individual values.
 * Useful when creating context from non-DOM sources.
 *
 * @param params Individual context parameters
 * @returns WordActionContext object
 */
export function buildContext(params: {
  textId: number;
  wordId?: number;
  position: number;
  text: string;
  hex?: string;
  status?: number;
  translation?: string;
  romanization?: string;
}): WordActionContext {
  return { ...params };
}
