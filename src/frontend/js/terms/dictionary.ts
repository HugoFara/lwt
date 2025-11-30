/**
 * Dictionary and translation utilities for LWT.
 *
 * Functions for creating dictionary URLs and links, and translating words/sentences.
 *
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   2.10.0-fork Extracted from pgm.ts
 */

import $ from 'jquery';
import { escape_apostrophes } from '../core/html_utils';
import { showRightFrames } from '../reading/frame_management';

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
 * Case 1: url without any ### or "lwt_term": append term
 * Case 2: url with one ### or "lwt_term": substitute term
 *
 * @param u Dictionary URL
 * @param w Term to be inserted in the URL
 * @returns A link to external dictionary to get a translation of the word
 *
 * @since 2.6.0-fork Internals rewrote, do no longer use PHP code.
 *                   The option putting encoding between ###enc### does no
 *                   longer work. It is deprecated and will be removed.
 * @since 2.7.0-fork Using "###" is deprecated, "lwt_term" recommended instead
 */
export function createTheDictUrl(u: string, w: string): string {
  const url = u.trim();
  const trm = w.trim();
  const term_elem = url.match(/lwt_term|###/);
  // No ###/lwt_term found
  if (term_elem === null) {
    return url + encodeURIComponent(trm);
  }
  const pos = url.indexOf(term_elem[0]);
  // ###/lwt_term found
  const pos2 = url.indexOf('###', pos + 1);
  if (pos2 === -1) {
    // 1 ###/lwt_term found
    return url.replace(term_elem[0], trm === '' ? '+' : encodeURIComponent(trm));
  }
  // 2 ### found
  // Get encoding
  const enc = url.substring(
    pos + term_elem[0].length, pos2 - pos - term_elem[0].length
  ).trim();
  console.warn(
    "Trying to use encoding '" + enc + "'. This feature is abandonned since " +
    '2.6.0-fork. Using default UTF-8.'
  );
  let output = url.substring(0, pos) + encodeURIComponent(trm);
  if (pos2 + 3 < url.length) {
    output += url.substring(pos2 + 3);
  }
  return output;
}

/**
 * Create an HTML link for a dictionary.
 *
 * @param u Dictionary URL
 * @param w Word or sentence to be translated
 * @param t Text to display
 * @param b Some other text to display before the link
 * @returns HTML-formatted link
 */
export function createTheDictLink(u: string, w: string, t: string, b: string): string {
  let url = u.trim();
  let popup = false;
  const trm = w.trim();
  const txt = t.trim();
  const txtbefore = b.trim();
  let r = '';
  if (url === '' || txt === '') {
    return r;
  }
  if (url.startsWith('*')) {
    url = url.substring(1);
    popup = true;
  }
  try {
    const final_url = new URL(url);
    popup = popup || final_url.searchParams.has('lwt_popup');
  } catch (err) {
    if (!(err instanceof TypeError)) {
      throw err;
    }
  }
  if (popup) {
    r = ' ' + txtbefore +
      ' <span class="click" onclick="owin(\'' +
      createTheDictUrl(url, escape_apostrophes(trm)) +
      '\');">' + txt + '</span> ';
  } else {
    r = ' ' + txtbefore +
      ' <a href="' + createTheDictUrl(url, trm) +
      '" target="ru" onclick="showRightFrames();">' + txt + '</a> ';
  }
  return r;
}

/**
 * Create a sentence lookup link.
 *
 * @param torder Text order
 * @param txid   Text ID
 * @param url    Translator URL
 * @param txt    Word text
 * @returns HTML-formatted link.
 */
export function createSentLookupLink(torder: number, txid: number, url: string, txt: string): string {
  url = url.trim();
  txt = txt.trim();
  let popup = false;
  let external = false;
  const target_url = 'trans.php?x=1&i=' + torder + '&t=' + txid;
  if (url === '' || txt === '') {
    return '';
  }
  if (url.startsWith('*')) {
    url = url.substring(1);
    popup = true;
  }
  try {
    const final_url = new URL(url);
    popup = popup || final_url.searchParams.has('lwt_popup');
    external = true;
  } catch (err) {
    if (!(err instanceof TypeError)) {
      throw err;
    }
  }
  if (popup) {
    return ' <span class="click" onclick="owin(\'' + target_url + '\');">' +
      txt + '</span> ';
  }
  if (external) {
    return ' <a href="' + target_url + '" target="ru" onclick="showRightFrames();">' +
      txt + '</a> ';
  }
  return '';
}

/**
 * Get the language name from the Google Translate URL.
 *
 * @param wblink3 Google Translate Dictionary URL
 * @returns Language name
 *
 * @since 2.7.0 Also works with a LibreTranslate URL
 */
export function getLangFromDict(wblink3: string): string {
  if (wblink3.trim() === '') {
    return '';
  }
  // Replace pop-up marker '*'
  if (wblink3.startsWith('*')) {
    wblink3 = wblink3.substring(1);
  }
  if (wblink3.startsWith('trans.php') || wblink3.startsWith('ggl.php')) {
    wblink3 = 'http://' + wblink3;
  }
  const dictUrl = new URL(wblink3);
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
      showRightFrames(undefined, createTheDictUrl(url, text.replace(/[{}]/g, '')));
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
      showRightFrames(undefined, createTheDictUrl(url, text));
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
  // Handle dict-popup: open dictionary in popup window
  $(document).on('click', '[data-action="dict-popup"]', function (this: HTMLElement) {
    const url = this.dataset.url;
    if (url) {
      owin(url);
    }
  });

  // Handle dict-frame: open dictionary in right frame
  $(document).on('click', '[data-action="dict-frame"]', function () {
    showRightFrames();
  });

  // Handle dict-auto-popup: auto-open dictionary in popup on page load
  $('[data-action="dict-auto-popup"]').each(function (this: HTMLElement) {
    const url = this.dataset.url;
    if (url) {
      owin(url);
    }
  });

  // Handle dict-auto-frame: auto-open dictionary in frame on page load
  $('[data-action="dict-auto-frame"]').each(function (this: HTMLElement) {
    const url = this.dataset.url;
    if (url && top?.frames) {
      const ruFrame = top.frames['ru' as unknown as number] as Window | undefined;
      if (ruFrame) {
        ruFrame.location.href = url;
      }
    }
  });

  // Handle translate-word: translate word in iframe
  $(document).on('click', '[data-action="translate-word"]', function (this: HTMLElement) {
    const url = this.dataset.url;
    const wordctlId = this.dataset.wordctl;
    if (url && wordctlId) {
      const wordctl = document.getElementById(wordctlId) as HTMLInputElement | null;
      translateWord(url, wordctl ?? undefined);
    }
  });

  // Handle translate-word-popup: translate word in popup
  $(document).on('click', '[data-action="translate-word-popup"]', function (this: HTMLElement) {
    const url = this.dataset.url;
    const wordctlId = this.dataset.wordctl;
    if (url && wordctlId) {
      const wordctl = document.getElementById(wordctlId) as HTMLInputElement | null;
      translateWord2(url, wordctl ?? undefined);
    }
  });

  // Handle translate-word-direct: translate word directly (word in data attribute)
  $(document).on('click', '[data-action="translate-word-direct"]', function (this: HTMLElement) {
    const url = this.dataset.url;
    const word = this.dataset.word;
    if (url && word) {
      translateWord3(url, word);
    }
  });

  // Handle translate-sentence: translate sentence in iframe
  $(document).on('click', '[data-action="translate-sentence"]', function (this: HTMLElement) {
    const url = this.dataset.url;
    const sentctlId = this.dataset.sentctl;
    if (url && sentctlId) {
      const sentctl = document.getElementById(sentctlId) as HTMLTextAreaElement | null;
      translateSentence(url, sentctl ?? undefined);
    }
  });

  // Handle translate-sentence-popup: translate sentence in popup
  $(document).on('click', '[data-action="translate-sentence-popup"]', function (this: HTMLElement) {
    const url = this.dataset.url;
    const sentctlId = this.dataset.sentctl;
    if (url && sentctlId) {
      const sentctl = document.getElementById(sentctlId) as HTMLTextAreaElement | null;
      translateSentence2(url, sentctl ?? undefined);
    }
  });
}

// Auto-initialize when DOM is ready
$(document).ready(function () {
  initDictionaryEventDelegation();
});
