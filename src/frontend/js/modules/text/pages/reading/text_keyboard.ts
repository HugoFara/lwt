/**
 * Keyboard navigation and shortcuts for text reading.
 * Handles all keyboard events during text reading mode.
 *
 * @license Unlicense <http://unlicense.org/>
 */

import { getLangFromDict, createTheDictUrl, owin } from '@modules/vocabulary/services/dictionary';
import { speechDispatcher } from '@shared/utils/user_interactions';
import { getAttrElement } from './text_annotations';
import { closePopup } from '@modules/vocabulary/components/word_popup';
import { loadDictionaryFrame } from '@modules/text/pages/reading/frame_management';
import { getPositionFromId } from '@shared/utils/ajax_utilities';
import { scrollTo } from '@shared/utils/hover_intent';
import {
  getReadingPosition,
  setReadingPosition,
  resetReadingPosition
} from '@modules/text/stores/reading_state';
import {
  getLanguageId,
  getDictionaryLinks,
  getSourceLang
} from '@modules/language/stores/language_config';
import { getTextId } from '@modules/text/stores/text_config';
import { getWordStatusFilter } from '@shared/utils/settings_config';
import { lwt_audio_controller } from '@/media/html5_audio_player';
import { TermsApi } from '@modules/vocabulary/api/terms_api';
import {
  updateExistingWordInDOM,
  markWordWellKnownInDOM,
  markWordIgnoredInDOM
} from '@modules/vocabulary/services/word_dom_updates';
import { getWordStore } from '@modules/vocabulary/stores/word_store';
import { getWordFormStore } from '@modules/vocabulary/stores/word_form_store';

/**
 * Remove a class from all elements matching a selector.
 *
 * @param selector CSS selector
 * @param className Class to remove
 */
function removeClassFromAll(selector: string, className: string): void {
  document.querySelectorAll(selector).forEach((el) => {
    el.classList.remove(className);
  });
}

/**
 * Navigate to a word and click it to show info.
 *
 * @param wordEl The word element to navigate to
 */
function navigateToWord(wordEl: HTMLElement): void {
  scrollTo(wordEl, { offset: -150 });
  // Click to trigger word popup/modal
  wordEl.click();
}

/**
 * Set status for an existing word via API.
 *
 * @param wordId Word ID
 * @param status New status
 * @param wordEl Word element for DOM updates
 */
async function setWordStatusViaApi(
  wordId: number,
  status: number,
  wordEl: HTMLElement
): Promise<void> {
  const response = await TermsApi.setStatus(wordId, status);
  if (response.error) {
    console.error('Failed to set word status:', response.error);
    return;
  }

  // Update DOM
  const text = wordEl.textContent || '';
  const translation = wordEl.getAttribute('data_trans') || '';
  const romanization = wordEl.getAttribute('data_rom') || '';
  const oldStatus = wordEl.getAttribute('data_status') || '0';

  updateExistingWordInDOM(
    { wid: wordId, status, translation, romanization, text },
    oldStatus
  );
}

/**
 * Create a quick word (ignored or well-known) via API.
 *
 * @param textId Text ID
 * @param position Word position
 * @param status Status (98 or 99)
 * @param hex Hex identifier
 * @param text Word text
 */
async function createQuickWordViaApi(
  textId: number,
  position: number,
  status: 98 | 99,
  hex: string,
  text: string
): Promise<void> {
  const response = await TermsApi.createQuick(textId, position, status);
  if (response.error || !response.data?.term_id) {
    console.error('Failed to create word:', response.error);
    return;
  }

  const wordId = response.data.term_id;

  // Update DOM
  if (status === 99) {
    markWordWellKnownInDOM(wordId, hex, text);
  } else {
    markWordIgnoredInDOM(wordId, hex, text);
  }
}

/**
 * Open word edit form via Alpine.js store.
 *
 * @param textId Text ID
 * @param position Word position
 * @param wordId Word ID (optional for new words)
 */
function openWordEditForm(textId: number, position: number, wordId?: number): void {
  try {
    const wordStore = getWordStore();
    const formStore = getWordFormStore();

    if (wordStore && formStore) {
      // Load the form and open modal
      formStore.loadForEdit(textId, position, wordId);
      wordStore.isModalOpen = true;
    }
  } catch {
    // Alpine stores not available, fall back to navigation
    const params = new URLSearchParams({
      tid: String(textId),
      ord: String(position)
    });
    if (wordId) {
      params.set('wid', String(wordId));
    }
    window.location.href = '/word/edit?' + params.toString();
  }
}

/**
 * Handle keyboard events during text reading.
 * ESC key resets reading position, arrow keys navigate between words.
 *
 * @param e Keyboard event
 * @returns false to prevent default behavior, true otherwise
 */
export function handleTextKeydown(e: KeyboardEvent): boolean {
  const keyCode = e.which || e.keyCode;
  const textId = getTextId();
  const dictLinks = getDictionaryLinks();

  if (keyCode === 27) { // esc = reset all
    resetReadingPosition();
    removeClassFromAll('span.uwordmarked', 'uwordmarked');
    removeClassFromAll('span.kwordmarked', 'kwordmarked');
    closePopup();
    return false;
  }

  if (keyCode === 13) { // return = edit next unknown word
    removeClassFromAll('span.uwordmarked', 'uwordmarked');
    const unknownWord = document.querySelector<HTMLElement>(
      'span.status0.word:not(.hide)'
    );
    if (!unknownWord) return false;
    scrollTo(unknownWord, { offset: -150 });
    unknownWord.classList.add('uwordmarked');
    unknownWord.click();
    closePopup();
    return false;
  }

  const wordStatusFilter = getWordStatusFilter();
  const knownwordlist = Array.from(document.querySelectorAll<HTMLElement>(
    'span.word:not(.hide):not(.status0)' + wordStatusFilter +
      ',span.mword:not(.hide)' + wordStatusFilter
  ));
  const l_knownwordlist = knownwordlist.length;
  if (l_knownwordlist === 0) return true;

  // the following only for a non-zero known words list
  let curr: HTMLElement;
  let readingPos: number;

  if (keyCode === 36) { // home : known word navigation -> first
    removeClassFromAll('span.kwordmarked', 'kwordmarked');
    setReadingPosition(0);
    curr = knownwordlist[0];
    curr.classList.add('kwordmarked');
    navigateToWord(curr);
    return false;
  }
  if (keyCode === 35) { // end : known word navigation -> last
    removeClassFromAll('span.kwordmarked', 'kwordmarked');
    setReadingPosition(l_knownwordlist - 1);
    curr = knownwordlist[l_knownwordlist - 1];
    curr.classList.add('kwordmarked');
    navigateToWord(curr);
    return false;
  }
  if (keyCode === 37) { // left : known word navigation
    const marked = document.querySelector<HTMLElement>('span.kwordmarked');
    let currid: number;
    if (!marked) {
      currid = 100000000;
    } else {
      const markedId = marked.id;
      currid = getPositionFromId(markedId || '');
    }
    removeClassFromAll('span.kwordmarked', 'kwordmarked');
    readingPos = l_knownwordlist - 1;
    for (let i = l_knownwordlist - 1; i >= 0; i--) {
      const itemId = knownwordlist[i].id;
      const iid = getPositionFromId(itemId || '');
      if (iid < currid) {
        readingPos = i;
        break;
      }
    }
    setReadingPosition(readingPos);
    curr = knownwordlist[readingPos];
    curr.classList.add('kwordmarked');
    navigateToWord(curr);
    return false;
  }
  if (keyCode === 39 || keyCode === 32) { // space /right : known word navigation
    const marked = document.querySelector<HTMLElement>('span.kwordmarked');
    let currid: number;
    if (!marked) {
      currid = -1;
    } else {
      const markedId = marked.id;
      currid = getPositionFromId(markedId || '');
    }
    removeClassFromAll('span.kwordmarked', 'kwordmarked');
    readingPos = 0;
    for (let i = 0; i < l_knownwordlist; i++) {
      const itemId = knownwordlist[i].id;
      const iid = getPositionFromId(itemId || '');
      if (iid > currid) {
        readingPos = i;
        break;
      }
    }
    setReadingPosition(readingPos);

    curr = knownwordlist[readingPos];
    curr.classList.add('kwordmarked');
    navigateToWord(curr);
    return false;
  }

  // Check if there's no marked word but there's a hovered word
  const hasMarkedWord = document.querySelector('.kwordmarked, .uwordmarked');
  const hoveredWord = document.querySelector<HTMLElement>('.hword:hover');
  readingPos = getReadingPosition();
  if (!hasMarkedWord && hoveredWord) {
    curr = hoveredWord;
  } else {
    if (readingPos < 0 || readingPos >= l_knownwordlist) return true;
    curr = knownwordlist[readingPos];
  }
  const wid = getAttrElement(curr, 'data_wid');
  const ord = getAttrElement(curr, 'data_order');
  const stat = getAttrElement(curr, 'data_status');
  const hex = getAttrElement(curr, 'data_hex') ||
    curr.className.match(/TERM([a-f0-9]+)/)?.[1] || '';
  const txt = curr.classList.contains('mwsty')
    ? getAttrElement(curr, 'data_text')
    : (curr.textContent || '');

  // Status keys 1-5
  for (let i = 1; i <= 5; i++) {
    if (keyCode === (48 + i) || keyCode === (96 + i)) { // 1,.. : status=i
      if (stat === '0') {
        // New word - open edit form with pre-set status
        openWordEditForm(textId, parseInt(ord, 10));
      } else {
        // Existing word - set status via API
        setWordStatusViaApi(parseInt(wid, 10), i, curr);
      }
      return false;
    }
  }

  if (keyCode === 73) { // I : status=98 (ignored)
    if (stat === '0') {
      // New word - create as ignored via API
      createQuickWordViaApi(textId, parseInt(ord, 10), 98, hex, txt);
    } else {
      // Existing word - set status via API
      setWordStatusViaApi(parseInt(wid, 10), 98, curr);
    }
    return false;
  }

  if (keyCode === 87) { // W : status=99 (well-known)
    if (stat === '0') {
      // New word - create as well-known via API
      createQuickWordViaApi(textId, parseInt(ord, 10), 99, hex, txt);
    } else {
      // Existing word - set status via API
      setWordStatusViaApi(parseInt(wid, 10), 99, curr);
    }
    return false;
  }

  if (keyCode === 80) { // P : pronounce term
    speechDispatcher(txt, getLanguageId());
    return false;
  }

  if (keyCode === 84) { // T : translate sentence
    let popup = false;
    let dict_link = dictLinks.translator;
    if (dictLinks.translator.startsWith('*')) {
      popup = true;
      dict_link = dict_link.substring(1);
    }
    let open_url = true;
    try {
      const final_url = new URL(dict_link);
      popup = popup || final_url.searchParams.has('lwt_popup');
    } catch (err) {
      if (err instanceof TypeError) {
        open_url = false;
      }
    }
    // Use the translator URL directly with the current word
    const translatorUrl = createTheDictUrl(dict_link, txt);
    if (popup) {
      owin(translatorUrl);
    } else if (open_url) {
      loadDictionaryFrame(translatorUrl);
    }
    return false;
  }

  if (keyCode === 65) { // A : set audio pos.
    let p = parseInt(getAttrElement(curr, 'data_pos') || '0', 10);
    const totalCharEl = document.getElementById('totalcharcount');
    const t = parseInt(totalCharEl?.textContent || '0', 10);
    if (t === 0) { return true; }
    p = 100 * (p - 5) / t;
    if (p < 0) p = 0;
    lwt_audio_controller.newPosition(p);
    return false;
  }

  if (keyCode === 71) { // G : edit term and open GTr (translator)
    const target_url = dictLinks.translator;
    setTimeout(function () {
      let popup = target_url.startsWith('*');
      try {
        const final_url = new URL(target_url);
        popup = popup || final_url.searchParams.has('lwt_popup');
      } catch (err) {
        if (!(err instanceof TypeError)) {
          throw err;
        }
      }
      if (popup) {
        owin(createTheDictUrl(target_url, txt));
      } else {
        loadDictionaryFrame(createTheDictUrl(target_url, txt));
      }
    }, 10);
    // Also open edit form (fall through to E key handler below)
  }

  if (keyCode === 69 || keyCode === 71) { // E / G: edit term
    const wordId = wid ? parseInt(wid, 10) : undefined;
    openWordEditForm(textId, parseInt(ord, 10), wordId);
    return false;
  }

  return true;
}

