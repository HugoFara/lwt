/**
 * Text Renderer - Client-side rendering of text words.
 *
 * Renders word tokens as HTML for the text reading view.
 * Generates spans with appropriate classes and data attributes for Alpine.js bindings.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import type { WordData } from './stores/word_store';

/**
 * Render settings for text display.
 */
export interface RenderSettings {
  showAll: boolean;
  showTranslations: boolean;
  rightToLeft: boolean;
  textSize: number;
}

/**
 * Escape HTML special characters.
 */
function escapeHtml(text: string): string {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

/**
 * Build CSS classes for a word span.
 */
function buildWordClasses(word: WordData, showAll: boolean): string {
  const classes: string[] = [];

  // Hidden class
  if (word.hidden) {
    classes.push('hide');
  }

  // Click handler class
  classes.push('click');

  // Word type class
  if (word.wordCount > 1) {
    classes.push('mword');
    classes.push(showAll ? 'mwsty' : 'wsty');
  } else {
    classes.push('word');
    classes.push('wsty');
  }

  // Order class
  classes.push(`order${word.position}`);

  // Word ID class (if word exists)
  if (word.wordId) {
    classes.push(`word${word.wordId}`);
  }

  // Status class
  classes.push(`status${word.status}`);

  // TERM class for hex lookup
  classes.push(`TERM${word.hex}`);

  return classes.join(' ');
}

/**
 * Build data attributes for a word span.
 */
function buildWordDataAttributes(word: WordData): Record<string, string> {
  const attrs: Record<string, string> = {
    'data-hex': word.hex,
    'data-position': String(word.position),
    'data-status': String(word.status),
    'data-order': String(word.position)
  };

  if (word.wordId) {
    attrs['data-wid'] = String(word.wordId);
  }

  if (word.translation) {
    attrs['data-trans'] = word.translation;
  }

  if (word.romanization) {
    attrs['data-rom'] = word.romanization;
  }

  if (word.wordCount > 1) {
    attrs['data-code'] = String(word.wordCount);
    attrs['data-text'] = word.text;
  }

  return attrs;
}

/**
 * Render a single word as HTML.
 */
export function renderWord(word: WordData, settings: RenderSettings): string {
  const spanId = `ID-${word.position}-${word.wordCount}`;

  if (word.isNotWord) {
    // Punctuation or whitespace
    const hiddenClass = word.hidden ? ' hide' : '';
    const text = word.text.replace(/¶/g, '<br />');
    return `<span id="${spanId}" class="${hiddenClass}">${escapeHtml(text)}</span>`;
  }

  // Build classes
  const classes = buildWordClasses(word, settings.showAll);

  // Build data attributes
  const dataAttrs = buildWordDataAttributes(word);
  const dataAttrString = Object.entries(dataAttrs)
    .map(([key, val]) => `${key}="${escapeHtml(val)}"`)
    .join(' ');

  // Text content
  let content: string;
  if (settings.showAll && word.wordCount > 1) {
    // In "show all" mode, multiwords display their word count
    content = String(word.wordCount);
  } else {
    content = escapeHtml(word.text);
  }

  return `<span id="${spanId}" class="${classes}" ${dataAttrString}>${content}</span>`;
}

/**
 * Render all words as HTML, grouped by sentences.
 */
export function renderText(words: WordData[], settings: RenderSettings): string {
  if (words.length === 0) return '';

  const parts: string[] = [];
  let currentSentenceId = -1;
  let sentenceOpen = false;

  for (const word of words) {
    // Handle sentence boundaries
    if (word.sentenceId !== currentSentenceId) {
      if (sentenceOpen) {
        parts.push('</span>');
      }
      currentSentenceId = word.sentenceId;
      parts.push(`<span id="sent_${currentSentenceId}">`);
      sentenceOpen = true;
    }

    // Render the word
    parts.push(renderWord(word, settings));
  }

  // Close last sentence
  if (sentenceOpen) {
    parts.push('</span>');
  }

  return parts.join('');
}

/**
 * Update the status class on all word elements with a given hex.
 */
export function updateWordStatusInDOM(
  hex: string,
  newStatus: number,
  newWordId: number | null = null,
  container: Element = document.body
): void {
  const selector = `.TERM${hex}`;
  const elements = container.querySelectorAll<HTMLElement>(selector);

  elements.forEach(el => {
    // Update status class
    el.className = el.className.replace(/status\d+/g, `status${newStatus}`);

    // Update data attribute
    el.setAttribute('data-status', String(newStatus));

    // Update word ID if provided
    if (newWordId !== null) {
      // Add/update word ID class
      el.className = el.className.replace(/word\d+/g, '');
      if (newWordId > 0) {
        el.classList.add(`word${newWordId}`);
        el.setAttribute('data-wid', String(newWordId));
      } else {
        el.removeAttribute('data-wid');
      }
    }
  });
}

/**
 * Update translation/romanization on word elements.
 */
export function updateWordTranslationInDOM(
  hex: string,
  translation: string,
  romanization: string,
  container: Element = document.body
): void {
  const selector = `.TERM${hex}`;
  const elements = container.querySelectorAll<HTMLElement>(selector);

  elements.forEach(el => {
    if (translation) {
      el.setAttribute('data-trans', translation);
    } else {
      el.removeAttribute('data-trans');
    }

    if (romanization) {
      el.setAttribute('data-rom', romanization);
    } else {
      el.removeAttribute('data-rom');
    }
  });
}

/**
 * Calculate total character count (for annotation display).
 */
export function calculateCharCount(words: WordData[]): number {
  let count = 0;
  for (const word of words) {
    if (!word.isNotWord && word.wordCount === 1) {
      count += word.text.length;
    }
  }
  return count;
}
