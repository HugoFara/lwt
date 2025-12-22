/**
 * Multi-word selection for text reading.
 * Handles the creation of multi-word expressions using native text selection.
 *
 * Users can select text normally (click and drag), and if multiple words
 * are selected, the multi-word modal opens automatically.
 *
 * @license Unlicense <http://unlicense.org/>
 */

import Alpine from 'alpinejs';
import { loadModalFrame } from './frame_management';
import type { MultiWordFormStoreState } from './stores/multi_word_form_store';

/**
 * Get text ID from URL (fallback when LWT_DATA is not available).
 */
function getTextIdFromUrl(): number {
  // Try to get from URL path: /text/read/123
  const pathMatch = window.location.pathname.match(/\/text\/read\/(\d+)/);
  if (pathMatch) {
    return parseInt(pathMatch[1], 10);
  }
  // Try to get from query string: ?text=123 or ?tid=123 or ?start=123
  const params = new URLSearchParams(window.location.search);
  const textParam = params.get('text') || params.get('tid') || params.get('start');
  if (textParam) {
    return parseInt(textParam, 10);
  }
  return 0;
}

/**
 * Get the text ID from LWT_DATA or URL.
 */
function getTextId(): number {
  if (typeof window !== 'undefined') {
    const win = window as unknown as { LWT_DATA?: { text?: { id?: number } } };
    if (win.LWT_DATA?.text?.id) {
      return win.LWT_DATA.text.id;
    }
  }
  return getTextIdFromUrl();
}

/**
 * Find all word elements (.wsty) within a selection range.
 * Returns words in document order.
 */
function getSelectedWords(container: HTMLElement): HTMLElement[] {
  const selection = window.getSelection();
  if (!selection || selection.isCollapsed || selection.rangeCount === 0) {
    return [];
  }

  const range = selection.getRangeAt(0);
  const words: HTMLElement[] = [];

  // Get all word elements in the container
  const allWords = container.querySelectorAll<HTMLElement>('.wsty');

  for (const word of allWords) {
    // Check if this word intersects with the selection range
    if (range.intersectsNode(word)) {
      words.push(word);
    }
  }

  return words;
}

/**
 * Get the combined text from selected word elements.
 * Includes punctuation/spaces between words.
 */
function getSelectedText(words: HTMLElement[]): string {
  if (words.length === 0) return '';
  if (words.length === 1) return words[0].textContent || '';

  // Get the range of positions
  const firstPos = parseInt(words[0].getAttribute('data_order') || '0', 10);
  const lastWord = words[words.length - 1];
  const lastPos = parseInt(lastWord.getAttribute('data_order') || '0', 10);
  const lastWordCount = parseInt(lastWord.getAttribute('data_code') || '1', 10);
  const endPos = lastPos + (lastWordCount > 1 ? lastWordCount * 2 - 1 : 0);

  // Find all elements between first and last position
  let text = '';
  const container = words[0].closest('[id^="sent_"]');
  if (!container) {
    // Fallback: just concatenate word texts
    return words.map(w => w.textContent || '').join('');
  }

  // Get all elements with IDs in the range
  for (let pos = firstPos; pos <= endPos; pos++) {
    // Try to find element at this position (could be word or punctuation)
    const el = container.querySelector(`[id^="ID-${pos}-"]`);
    if (el) {
      text += el.textContent || '';
    }
  }

  return text || words.map(w => w.textContent || '').join('');
}

/**
 * Handle text selection for multi-word creation.
 * Called on mouseup to check if user selected multiple words.
 *
 * @param container The text container element (#thetext)
 */
export function handleTextSelection(container: HTMLElement): void {
  const selectedWords = getSelectedWords(container);

  // Clear selection after processing
  const clearSelection = () => {
    window.getSelection()?.removeAllRanges();
  };

  // Need at least 2 words for multi-word
  if (selectedWords.length < 2) {
    return;
  }

  // Get the selected text
  const text = getSelectedText(selectedWords);

  if (text.length > 250) {
    alert('Selected text is too long!!!');
    clearSelection();
    return;
  }

  // Get the first word's position
  const firstWord = selectedWords[0];
  const position = parseInt(firstWord.getAttribute('data_order') || '0', 10);

  // Get text ID
  const textId = getTextId();

  // Open multi-word modal via Alpine.js store
  const store = Alpine.store('multiWordForm') as MultiWordFormStoreState;
  if (store && typeof store.loadForEdit === 'function') {
    store.loadForEdit(textId, position, text, selectedWords.length);
  } else {
    // Fallback to frame-based editing
    const params = new URLSearchParams({
      tid: String(textId),
      ord: String(position),
      txt: text
    });
    loadModalFrame('/word/edit?' + params.toString());
  }

  clearSelection();
}

/**
 * Set up multi-word selection on a container.
 * Listens for mouseup events and checks for text selection.
 *
 * @param container The text container element (#thetext)
 */
export function setupMultiWordSelection(container: HTMLElement): void {
  container.addEventListener('mouseup', () => {
    // Small delay to ensure selection is complete
    setTimeout(() => handleTextSelection(container), 10);
  });
}

// Legacy exports for backwards compatibility
// These are no longer used but kept to avoid breaking imports
export const mwordDragNDrop = {
  context: undefined as HTMLElement | undefined,
  stopInteraction: () => {}
};

export function mword_drag_n_drop_select(): void {
  // No longer used - selection is handled via native text selection
}

export function mword_touch_select(): void {
  // No longer used - selection is handled via native text selection
}
