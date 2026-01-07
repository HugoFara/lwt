/**
 * Dictionary and translation utilities for LWT.
 *
 * Functions for creating dictionary URLs and links, and translating words/sentences.
 *
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   2.10.0-fork Extracted from pgm.ts
 */

// All dictionary functions now open in popup windows.

/**
 * Open a window.
 *
 * @param url URL of the window
 */
export function owin(url: string): Window | null {
  return window.open(
    url,
    'dictwin',
    'width=800, height=400, scrollbars=yes, menubar=no, resizable=yes, status=no'
  );
}

/**
 * Open a window in edit mode.
 *
 * @param url Window URL
 */
export function oewin(url: string): Window | null {
  return window.open(
    url,
    'editwin',
    'width=800, height=600, scrollbars=yes, menubar=no, resizable=yes, status=no'
  );
}

/**
 * Create a dictionary URL.
 *
 * JS alter ego of the createTheDictLink PHP function.
 *
 * Case 1: url without "lwt_term": append term
 * Case 2: url with "lwt_term": substitute term
 *
 * @param u Dictionary URL
 * @param w Term to be inserted in the URL
 * @returns A link to external dictionary to get a translation of the word
 *
 * @since 2.6.0-fork Internals rewrote, do no longer use PHP code.
 * @since 2.7.0-fork Use "lwt_term" placeholder
 * @since 3.1.0 Removed support for deprecated "###" placeholder
 */
export function createTheDictUrl(u: string, w: string): string {
  const url = u.trim();
  const trm = w.trim();
  const encodedTrm = trm === '' ? '+' : encodeURIComponent(trm);

  // Check for lwt_term placeholder
  if (url.includes('lwt_term')) {
    return url.replace('lwt_term', encodedTrm);
  }

  // No placeholder found - append term to URL
  return url + encodedTrm;
}

/**
 * Create an HTML link for a dictionary.
 *
 * @param u Dictionary URL
 * @param w Word or sentence to be translated
 * @param t Text to display
 * @param b Some other text to display before the link
 * @param popup Whether to open in popup window
 * @returns HTML-formatted link
 *
 * @since 3.1.0 Added popup parameter, removed asterisk prefix and lwt_popup URL detection
 */
export function createTheDictLink(u: string, w: string, t: string, b: string, popup = false): string {
  const url = u.trim();
  const trm = w.trim();
  const txt = t.trim();
  const txtbefore = b.trim();

  if (url === '' || txt === '') {
    return '';
  }

  const dictUrl = createTheDictUrl(url, trm);
  if (popup) {
    return ' ' + txtbefore +
      ' <span class="click" data-action="dict-popup" data-url="' +
      dictUrl.replace(/"/g, '&quot;') +
      '">' + txt + '</span> ';
  }
  return ' ' + txtbefore +
    ' <a href="' + dictUrl +
    '" target="ru" data-action="dict-frame">' + txt + '</a> ';
}

/**
 * Create a sentence lookup link.
 *
 * Creates an HTML link element for translator lookups. Unlike createTheDictLink,
 * this does not modify the URL with term substitution.
 *
 * @param url    Translator URL
 * @param txt    Link text
 * @param popup  Whether to open in popup window
 * @returns HTML-formatted link.
 *
 * @since 3.1.0 Added popup parameter, removed asterisk prefix and lwt_popup URL detection
 * @since 3.2.0 Removed unused torder and txid parameters
 */
export function createSentLookupLink(url: string, txt: string, popup = false): string {
  url = url.trim();
  txt = txt.trim();
  if (url === '' || txt === '') {
    return '';
  }
  if (popup) {
    return ' <span class="click" data-action="dict-popup" data-url="' +
      url.replace(/"/g, '&quot;') + '">' + txt + '</span> ';
  }
  return ' <a href="' + url + '" target="ru" data-action="dict-frame">' +
    txt + '</a> ';
}

/**
 * Get the source language code from a translator URL.
 *
 * Parses the URL to extract the source language code (e.g., 'sl' parameter
 * for Google Translate, 'source' for LibreTranslate).
 *
 * Note: Prefer using the stored sourceLang from LanguageConfig when available.
 * This function serves as a fallback for backwards compatibility when the
 * database value is not set.
 *
 * @param wblink3 Translator URL (Google Translate or LibreTranslate)
 * @returns Language code (e.g., 'en', 'fr') or empty string if not found
 *
 * @since 2.7.0 Also works with LibreTranslate URLs
 * @since 3.2.0 Clarified as fallback; prefer LanguageConfig.sourceLang
 */
export function getLangFromDict(wblink3: string): string {
  if (wblink3.trim() === '') {
    return '';
  }
  let dictUrl: URL;
  try {
    dictUrl = new URL(wblink3);
  } catch {
    // Invalid URL, return empty
    return '';
  }
  const urlParams = dictUrl.searchParams;
  if (urlParams.get('lwt_translator') === 'libretranslate') {
    return urlParams.get('source') || '';
  }
  // Fallback to Google Translate
  return urlParams.get('sl') || '';
}

/**
 * Translate a sentence.
 *
 * @param url     Translation URL with "{term}" marking the interesting term
 * @param sentctl Textarea contaning sentence
 */
export function translateSentence(url: string, sentctl: HTMLTextAreaElement | undefined): void {
  if (sentctl !== undefined && url !== '') {
    const text = sentctl.value;
    if (typeof text === 'string') {
      owin(createTheDictUrl(url, text.replace(/[{}]/g, '')));
    }
  }
}

/**
 * Translate a sentence.
 *
 * @param url     Translation URL with "{term}" marking the interesting term
 * @param sentctl Textarea contaning sentence
 */
export function translateSentence2(url: string, sentctl: HTMLTextAreaElement | undefined): void {
  if (typeof sentctl !== 'undefined' && url !== '') {
    const text = sentctl.value;
    if (typeof text === 'string') {
      const finalurl = createTheDictUrl(url, text.replace(/[{}]/g, ''));
      owin(finalurl);
    }
  }
}

/**
 * Open a new window with the translation of the word.
 *
 * @param url     Dictionary URL
 * @param wordctl Textarea containing word to translate.
 */
export function translateWord(url: string, wordctl: HTMLInputElement | undefined): void {
  if (wordctl !== undefined && url !== '') {
    const text = wordctl.value;
    if (typeof text === 'string') {
      owin(createTheDictUrl(url, text));
    }
  }
}

/**
 * Open a new window with the translation of the word.
 *
 * @param url     Dictionary URL
 * @param wordctl Textarea containing word to translate.
 */
export function translateWord2(url: string, wordctl: HTMLInputElement | undefined): void {
  if (wordctl !== undefined && url !== '') {
    const text = wordctl.value;
    if (typeof text === 'string') {
      owin(createTheDictUrl(url, text));
    }
  }
}

/**
 * Open a new window with the translation of the word.
 *
 * @param url Dictionary URL
 * @param word Word to translate.
 */
export function translateWord3(url: string, word: string): void {
  owin(createTheDictUrl(url, word));
}

/**
 * Initialize event delegation for dictionary action elements.
 *
 * Handles elements with data-action attributes for dictionary operations.
 */
function initDictionaryEventDelegation(): void {
  // Handle click events using event delegation
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement;

    // Handle dict-popup: open dictionary in popup window
    const dictPopup = target.closest<HTMLElement>('[data-action="dict-popup"]');
    if (dictPopup) {
      const url = dictPopup.dataset.url;
      if (url) {
        owin(url);
      }
      return;
    }

    // Handle dict-frame: open dictionary (legacy action, now opens in popup)
    const dictFrame = target.closest<HTMLAnchorElement>('[data-action="dict-frame"]');
    if (dictFrame) {
      const url = dictFrame.href;
      if (url) {
        e.preventDefault();
        owin(url);
      }
      return;
    }

    // Handle translate-word: translate word in iframe
    const translateWordEl = target.closest<HTMLElement>('[data-action="translate-word"]');
    if (translateWordEl) {
      const url = translateWordEl.dataset.url;
      const wordctlId = translateWordEl.dataset.wordctl;
      if (url && wordctlId) {
        const wordctl = document.getElementById(wordctlId) as HTMLInputElement | null;
        translateWord(url, wordctl ?? undefined);
      }
      return;
    }

    // Handle translate-word-popup: translate word in popup
    const translateWordPopup = target.closest<HTMLElement>('[data-action="translate-word-popup"]');
    if (translateWordPopup) {
      const url = translateWordPopup.dataset.url;
      const wordctlId = translateWordPopup.dataset.wordctl;
      if (url && wordctlId) {
        const wordctl = document.getElementById(wordctlId) as HTMLInputElement | null;
        translateWord2(url, wordctl ?? undefined);
      }
      return;
    }

    // Handle translate-word-direct: translate word directly (word in data attribute)
    const translateWordDirect = target.closest<HTMLElement>('[data-action="translate-word-direct"]');
    if (translateWordDirect) {
      const url = translateWordDirect.dataset.url;
      const word = translateWordDirect.dataset.word;
      if (url && word) {
        translateWord3(url, word);
      }
      return;
    }

    // Handle translate-sentence: translate sentence in iframe
    const translateSentenceEl = target.closest<HTMLElement>('[data-action="translate-sentence"]');
    if (translateSentenceEl) {
      const url = translateSentenceEl.dataset.url;
      const sentctlId = translateSentenceEl.dataset.sentctl;
      if (url && sentctlId) {
        const sentctl = document.getElementById(sentctlId) as HTMLTextAreaElement | null;
        translateSentence(url, sentctl ?? undefined);
      }
      return;
    }

    // Handle translate-sentence-popup: translate sentence in popup
    const translateSentencePopup = target.closest<HTMLElement>('[data-action="translate-sentence-popup"]');
    if (translateSentencePopup) {
      const url = translateSentencePopup.dataset.url;
      const sentctlId = translateSentencePopup.dataset.sentctl;
      if (url && sentctlId) {
        const sentctl = document.getElementById(sentctlId) as HTMLTextAreaElement | null;
        translateSentence2(url, sentctl ?? undefined);
      }
    }
  });

  // Handle dict-auto-popup: auto-open dictionary in popup on page load
  document.querySelectorAll<HTMLElement>('[data-action="dict-auto-popup"]').forEach(el => {
    const url = el.dataset.url;
    if (url) {
      owin(url);
    }
  });

  // Handle dict-auto-frame: auto-open dictionary (legacy action, now opens in popup)
  document.querySelectorAll<HTMLElement>('[data-action="dict-auto-frame"]').forEach(el => {
    const url = el.dataset.url;
    if (url) {
      owin(url);
    }
  });
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  initDictionaryEventDelegation();
});
