/**
 * Text Check Display - Display word statistics after text parsing.
 *
 * Handles the display of word lists, expression lists, and non-word lists
 * after a text has been checked/parsed. Used by the text check functionality.
 *
 * @license unlicense
 * @since   3.0.0
 */

import { onDomReady } from '@shared/utils/dom_ready';

/**
 * Word entry with text, count, and optional translation.
 * [word, count, translation]
 */
type WordEntry = [string, number, string];

/**
 * Non-word entry with text and count.
 * [text, count]
 */
type NonWordEntry = [string, string];

/**
 * Configuration passed from PHP via JSON.
 */
interface TextCheckConfig {
  words: WordEntry[];
  nonWords: NonWordEntry[];
  multiWords: WordEntry[];
  rtlScript: boolean;
}

// Global variables for backwards compatibility with legacy code
declare global {
  interface Window {
    WORDS?: WordEntry[];
    NOWORDS?: NonWordEntry[];
    MWORDS?: WordEntry[];
  }
}

/**
 * Display the word statistics in the check_text container.
 *
 * @param words - Array of [word, count, translation] tuples
 * @param multiWords - Array of [word, count, translation] tuples for multi-word expressions
 * @param nonWords - Array of [text, count] tuples for non-word items
 */
export function displayStatistics(
  words: WordEntry[],
  multiWords: WordEntry[],
  nonWords: NonWordEntry[]
): void {
  let h = '<h4>Word List <span class="has-text-danger has-text-weight-bold">(red = already saved)</span></h4>' +
    '<ul class="wordlist">';

  words.forEach((v) => {
    h += '<li><span' + (v[2] === '' ? '' : ' class="has-text-danger has-text-weight-bold"') + '>[' + v[0] + '] — ' +
      v[1] + (v[2] === '' ? '' : ' — ' + v[2]) + '</span></li>';
  });

  h += '</ul><p>TOTAL: ' + words.length +
    '</p><h4>Expression List</span></h4><ul class="expressionlist">';

  multiWords.forEach((v) => {
    h += '<li><span>[' + v[0] + '] — ' + v[1] +
      (v[2] === '' ? '' : ' — ' + v[2]) + '</span></li>';
  });

  h += '</ul><p>TOTAL: ' + multiWords.length +
    '</p><h4>Non-Word List</span></h4><ul class="nonwordlist">';

  nonWords.forEach((v) => {
    h += '<li>[' + v[0] + '] — ' + v[1] + '</li>';
  });

  h += '</ul><p>TOTAL: ' + nonWords.length + '</p>';

  const checkTextEl = document.getElementById('check_text');
  if (checkTextEl) {
    checkTextEl.insertAdjacentHTML('beforeend', h);
  }
}

/**
 * Apply RTL direction to list items if the language is right-to-left.
 *
 * @param rtlScript - Whether the language uses RTL script
 */
function applyRtlIfNeeded(rtlScript: boolean): void {
  if (rtlScript) {
    document.querySelectorAll('li').forEach(li => {
      li.setAttribute('dir', 'rtl');
    });
  }
}

/**
 * Initialize the text check display from JSON configuration.
 * Reads configuration from a script element with id="text-check-config".
 * Words and non-words come from text-check-words-config (set by checkValid).
 * Multi-words and RTL setting come from text-check-config (set by displayStatistics).
 */
export function initTextCheckDisplay(): void {
  const configEl = document.getElementById('text-check-config');
  if (!configEl) {
    // Try legacy global variables for backwards compatibility
    if (window.WORDS !== undefined) {
      displayStatistics(
        window.WORDS,
        window.MWORDS || [],
        window.NOWORDS || []
      );
    }
    return;
  }

  try {
    const config: TextCheckConfig = JSON.parse(configEl.textContent || '{}');

    // Apply RTL if needed
    applyRtlIfNeeded(config.rtlScript);

    // Get words and non-words from global variables (set by initTextCheckWords)
    // or from the config if provided
    const words = (config.words && config.words.length > 0) ? config.words : (window.WORDS || []);
    const nonWords = (config.nonWords && config.nonWords.length > 0) ? config.nonWords : (window.NOWORDS || []);
    const multiWords = config.multiWords || [];

    // Display the statistics
    displayStatistics(words, multiWords, nonWords);

    // Also set global variables for any legacy code that might need them
    window.WORDS = words;
    window.MWORDS = multiWords;
    window.NOWORDS = nonWords;
  } catch (e) {
    console.error('Failed to parse text-check-config:', e);
  }
}

/**
 * Initialize just the word data (used by checkValid).
 * Reads from text-check-words-config element.
 */
export function initTextCheckWords(): void {
  const configEl = document.getElementById('text-check-words-config');
  if (!configEl) {
    return;
  }

  try {
    const config = JSON.parse(configEl.textContent || '{}') as {
      words: WordEntry[];
      nonWords: NonWordEntry[];
    };

    // Set global variables for displayStatistics to use later
    window.WORDS = config.words || [];
    window.NOWORDS = config.nonWords || [];
  } catch (e) {
    console.error('Failed to parse text-check-words-config:', e);
  }
}

// Auto-initialize on document ready
onDomReady(() => {
  // Initialize words data first (from checkValid)
  initTextCheckWords();

  // Then initialize full display (from displayStatistics)
  initTextCheckDisplay();
});
