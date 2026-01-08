/**
 * Keyboard navigation and shortcuts for text reading.
 * Handles all keyboard events during text reading mode.
 *
 * @license Unlicense <http://unlicense.org/>
 */

import { getLangFromDict, createTheDictUrl, owin } from '@modules/vocabulary/services/dictionary';
import { speechDispatcher } from '@shared/utils/user_interactions';
import { getAttrElement } from './text_annotations';
import { cClick } from '@modules/vocabulary/components/word_popup';
import { loadModalFrame, loadDictionaryFrame } from '@modules/text/pages/reading/frame_management';
import { get_position_from_id } from '@shared/utils/ajax_utilities';
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
 * Handle keyboard events during text reading.
 * ESC key resets reading position, arrow keys navigate between words.
 *
 * @param e Keyboard event
 * @returns false to prevent default behavior, true otherwise
 */
export function keydown_event_do_text_text(e: KeyboardEvent): boolean {
  const keyCode = e.which || e.keyCode;
  const textId = getTextId();
  const dictLinks = getDictionaryLinks();

  if (keyCode === 27) { // esc = reset all
    resetReadingPosition();
    removeClassFromAll('span.uwordmarked', 'uwordmarked');
    removeClassFromAll('span.kwordmarked', 'kwordmarked');
    cClick();
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
    cClick();
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
  let ann: string;
  let readingPos: number;

  if (keyCode === 36) { // home : known word navigation -> first
    removeClassFromAll('span.kwordmarked', 'kwordmarked');
    setReadingPosition(0);
    curr = knownwordlist[0];
    curr.classList.add('kwordmarked');
    scrollTo(curr, { offset: -150 });
    ann = getAttrElement(curr, 'data_ann');
    loadModalFrame(
      '/word/show?wid=' + getAttrElement(curr, 'data_wid') + '&ann=' +
        encodeURIComponent(ann)
    );
    return false;
  }
  if (keyCode === 35) { // end : known word navigation -> last
    removeClassFromAll('span.kwordmarked', 'kwordmarked');
    setReadingPosition(l_knownwordlist - 1);
    curr = knownwordlist[l_knownwordlist - 1];
    curr.classList.add('kwordmarked');
    scrollTo(curr, { offset: -150 });
    ann = getAttrElement(curr, 'data_ann');
    loadModalFrame(
      '/word/show?wid=' + getAttrElement(curr, 'data_wid') + '&ann=' +
        encodeURIComponent(ann)
    );
    return false;
  }
  if (keyCode === 37) { // left : known word navigation
    const marked = document.querySelector<HTMLElement>('span.kwordmarked');
    let currid: number;
    if (!marked) {
      currid = 100000000;
    } else {
      const markedId = marked.id;
      currid = get_position_from_id(markedId || '');
    }
    removeClassFromAll('span.kwordmarked', 'kwordmarked');
    readingPos = l_knownwordlist - 1;
    for (let i = l_knownwordlist - 1; i >= 0; i--) {
      const itemId = knownwordlist[i].id;
      const iid = get_position_from_id(itemId || '');
      if (iid < currid) {
        readingPos = i;
        break;
      }
    }
    setReadingPosition(readingPos);
    curr = knownwordlist[readingPos];
    curr.classList.add('kwordmarked');
    scrollTo(curr, { offset: -150 });
    ann = getAttrElement(curr, 'data_ann');
    loadModalFrame(
      '/word/show?wid=' + getAttrElement(curr, 'data_wid') + '&ann=' +
        encodeURIComponent(ann)
    );
    return false;
  }
  if (keyCode === 39 || keyCode === 32) { // space /right : known word navigation
    const marked = document.querySelector<HTMLElement>('span.kwordmarked');
    let currid: number;
    if (!marked) {
      currid = -1;
    } else {
      const markedId = marked.id;
      currid = get_position_from_id(markedId || '');
    }
    removeClassFromAll('span.kwordmarked', 'kwordmarked');
    readingPos = 0;
    for (let i = 0; i < l_knownwordlist; i++) {
      const itemId = knownwordlist[i].id;
      const iid = get_position_from_id(itemId || '');
      if (iid > currid) {
        readingPos = i;
        break;
      }
    }
    setReadingPosition(readingPos);

    curr = knownwordlist[readingPos];
    curr.classList.add('kwordmarked');
    scrollTo(curr, { offset: -150 });
    ann = getAttrElement(curr, 'data_ann');
    loadModalFrame(
      '/word/show?wid=' + getAttrElement(curr, 'data_wid') + '&ann=' +
        encodeURIComponent(ann)
    );
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
  const txt = curr.classList.contains('mwsty')
    ? getAttrElement(curr, 'data_text')
    : (curr.textContent || '');
  let dict = '';

  for (let i = 1; i <= 5; i++) {
    if (keyCode === (48 + i) || keyCode === (96 + i)) { // 1,.. : status=i
      if (stat === '0') {
        let statusVal: string | number = i;
        if (i === 1) {
          // Prefer sourceLang from config, fall back to parsing translator URL
          const sl = getSourceLang() || getLangFromDict(dictLinks.translator);
          const tl = dictLinks.translator.replace(/.*[?&]tl=([a-zA-Z-]*)(&.*)*$/, '$1');
          if (sl && sl !== dictLinks.translator && tl !== dictLinks.translator) {
            statusVal = i + '&sl=' + sl + '&tl=' + tl;
          }
        }
        loadModalFrame(
          '/vocabulary/term-hover?text=' + txt + '&tid=' + textId + '&status=' + statusVal
        );
      } else {
        loadModalFrame(
          '/word/set-status?wid=' + wid + '&tid=' + textId + '&ord=' + ord +
            '&status=' + i
        );
        return false;
      }
    }
  }
  if (keyCode === 73) { // I : status=98
    if (stat === '0') {
      loadModalFrame(
        '/vocabulary/term-hover?text=' + txt + '&tid=' + textId +
          '&status=98'
      );
    } else {
      loadModalFrame(
        '/word/set-status?wid=' + wid + '&tid=' + textId +
          '&ord=' + ord + '&status=98'
      );
      return false;
    }
  }
  if (keyCode === 87) { // W : status=99
    if (stat === '0') {
      loadModalFrame(
        '/vocabulary/term-hover?text=' + txt + '&tid=' + textId + '&status=99'
      );
    } else {
      loadModalFrame(
        '/word/set-status?wid=' + wid + '&tid=' + textId + '&ord=' + ord +
          '&status=99'
      );
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
    let final_url: URL | undefined;
    try {
      final_url = new URL(dict_link);
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
  if (keyCode === 71) { //  G : edit term and open GTr
    dict = '&nodict';
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
  }
  if (keyCode === 69 || keyCode === 71) { //  E / G: edit term
    let url = '';
    if (curr.classList.contains('mword')) {
      url = '/word/edit-multi?wid=' + wid + '&len=' + getAttrElement(curr, 'data_code') +
        '&tid=' + textId + '&ord=' + ord + dict;
    } else if (stat === '0') {
      url = '/word/edit?wid=&tid=' + textId + '&ord=' + ord + dict;
    } else {
      url = '/word/edit?wid=' + wid + '&tid=' + textId + '&ord=' + ord + dict;
    }
    loadModalFrame(url);
    return false;
  }
  return true;
}

