/**
 * Keyboard navigation and shortcuts for text reading.
 * Handles all keyboard events during text reading mode.
 *
 * @license Unlicense <http://unlicense.org/>
 */

import { getLangFromDict, createTheDictUrl, owin } from '../terms/dictionary';
import { speechDispatcher } from '../core/user_interactions';
import { getAttrElement } from './text_annotations';
import { cClick } from '../ui/word_popup';
import { loadModalFrame, loadDictionaryFrame } from './frame_management';
import { get_position_from_id } from '../core/ajax_utilities';
import { scrollTo } from '../core/hover_intent';

// Type definitions
interface LwtLanguage {
  id: number;
  dict_link1: string;
  dict_link2: string;
  translator_link: string;
  delimiter: string;
  rtl: boolean;
}

interface LwtText {
  id: number;
  reading_position: number;
  annotations: Record<string, [unknown, string, string]>;
}

interface LwtSettings {
  jQuery_tooltip: boolean;
  hts: number;
  word_status_filter: string;
  annotations_mode: number;
}

interface LwtDataGlobal {
  language: LwtLanguage;
  text: LwtText;
  settings: LwtSettings;
}

declare const LWT_DATA: LwtDataGlobal;

// Audio controller type for frame access
// We only need the newPosition method for seeking audio
interface FramesWithH {
  h: Window & {
    lwt_audio_controller: {
      newPosition: (p: number) => void;
    };
  };
}

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

  if (keyCode === 27) { // esc = reset all
    LWT_DATA.text.reading_position = -1;
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

  const knownwordlist = Array.from(document.querySelectorAll<HTMLElement>(
    'span.word:not(.hide):not(.status0)' + LWT_DATA.settings.word_status_filter +
      ',span.mword:not(.hide)' + LWT_DATA.settings.word_status_filter
  ));
  const l_knownwordlist = knownwordlist.length;
  if (l_knownwordlist === 0) return true;

  // the following only for a non-zero known words list
  let curr: HTMLElement;
  let ann: string;

  if (keyCode === 36) { // home : known word navigation -> first
    removeClassFromAll('span.kwordmarked', 'kwordmarked');
    LWT_DATA.text.reading_position = 0;
    curr = knownwordlist[LWT_DATA.text.reading_position];
    curr.classList.add('kwordmarked');
    scrollTo(curr, { offset: -150 });
    ann = getAttrElement(curr, 'data_ann');
    loadModalFrame(
      'show_word.php?wid=' + getAttrElement(curr, 'data_wid') + '&ann=' +
        encodeURIComponent(ann)
    );
    return false;
  }
  if (keyCode === 35) { // end : known word navigation -> last
    removeClassFromAll('span.kwordmarked', 'kwordmarked');
    LWT_DATA.text.reading_position = l_knownwordlist - 1;
    curr = knownwordlist[LWT_DATA.text.reading_position];
    curr.classList.add('kwordmarked');
    scrollTo(curr, { offset: -150 });
    ann = getAttrElement(curr, 'data_ann');
    loadModalFrame(
      'show_word.php?wid=' + getAttrElement(curr, 'data_wid') + '&ann=' +
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
    LWT_DATA.text.reading_position = l_knownwordlist - 1;
    for (let i = l_knownwordlist - 1; i >= 0; i--) {
      const itemId = knownwordlist[i].id;
      const iid = get_position_from_id(itemId || '');
      if (iid < currid) {
        LWT_DATA.text.reading_position = i;
        break;
      }
    }
    curr = knownwordlist[LWT_DATA.text.reading_position];
    curr.classList.add('kwordmarked');
    scrollTo(curr, { offset: -150 });
    ann = getAttrElement(curr, 'data_ann');
    loadModalFrame(
      'show_word.php?wid=' + getAttrElement(curr, 'data_wid') + '&ann=' +
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
    LWT_DATA.text.reading_position = 0;
    for (let i = 0; i < l_knownwordlist; i++) {
      const itemId = knownwordlist[i].id;
      const iid = get_position_from_id(itemId || '');
      if (iid > currid) {
        LWT_DATA.text.reading_position = i;
        break;
      }
    }

    curr = knownwordlist[LWT_DATA.text.reading_position];
    curr.classList.add('kwordmarked');
    scrollTo(curr, { offset: -150 });
    ann = getAttrElement(curr, 'data_ann');
    loadModalFrame(
      'show_word.php?wid=' + getAttrElement(curr, 'data_wid') + '&ann=' +
        encodeURIComponent(ann)
    );
    return false;
  }

  // Check if there's no marked word but there's a hovered word
  const hasMarkedWord = document.querySelector('.kwordmarked, .uwordmarked');
  const hoveredWord = document.querySelector<HTMLElement>('.hword:hover');
  if (!hasMarkedWord && hoveredWord) {
    curr = hoveredWord;
  } else {
    if (LWT_DATA.text.reading_position < 0 || LWT_DATA.text.reading_position >= l_knownwordlist) return true;
    curr = knownwordlist[LWT_DATA.text.reading_position];
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
          /** @var sl Source language */
          const sl = getLangFromDict(LWT_DATA.language.translator_link);
          const tl = LWT_DATA.language.translator_link.replace(/.*[?&]tl=([a-zA-Z-]*)(&.*)*$/, '$1');
          if (sl !== LWT_DATA.language.translator_link && tl !== LWT_DATA.language.translator_link) {
            statusVal = i + '&sl=' + sl + '&tl=' + tl;
          }
        }
        loadModalFrame(
          'set_word_on_hover.php?text=' + txt + '&tid=' + LWT_DATA.text.id + '&status=' + statusVal
        );
      } else {
        loadModalFrame(
          'set_word_status.php?wid=' + wid + '&tid=' + LWT_DATA.text.id + '&ord=' + ord +
            '&status=' + i
        );
        return false;
      }
    }
  }
  if (keyCode === 73) { // I : status=98
    if (stat === '0') {
      loadModalFrame(
        'set_word_on_hover.php?text=' + txt + '&tid=' + LWT_DATA.text.id +
          '&status=98'
      );
    } else {
      loadModalFrame(
        'set_word_status.php?wid=' + wid + '&tid=' + LWT_DATA.text.id +
          '&ord=' + ord + '&status=98'
      );
      return false;
    }
  }
  if (keyCode === 87) { // W : status=99
    if (stat === '0') {
      loadModalFrame(
        'set_word_on_hover.php?text=' + txt + '&tid=' + LWT_DATA.text.id + '&status=99'
      );
    } else {
      loadModalFrame(
        'set_word_status.php?wid=' + wid + '&tid=' + LWT_DATA.text.id + '&ord=' + ord +
          '&status=99'
      );
    }
    return false;
  }
  if (keyCode === 80) { // P : pronounce term
    speechDispatcher(txt, LWT_DATA.language.id);
    return false;
  }
  if (keyCode === 84) { // T : translate sentence
    let popup = false;
    let dict_link = LWT_DATA.language.translator_link;
    if (LWT_DATA.language.translator_link.startsWith('*')) {
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
    const parentFrames = window.parent as Window & { frames: FramesWithH };
    if (typeof parentFrames.frames.h?.lwt_audio_controller?.newPosition === 'function') {
      parentFrames.frames.h.lwt_audio_controller.newPosition(p);
    } else {
      return true;
    }
    return false;
  }
  if (keyCode === 71) { //  G : edit term and open GTr
    dict = '&nodict';
    setTimeout(function () {
      const target_url = LWT_DATA.language.translator_link;
      let popup = false;
      popup = target_url.startsWith('*');
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
      url = 'edit_mword.php?wid=' + wid + '&len=' + getAttrElement(curr, 'data_code') +
        '&tid=' + LWT_DATA.text.id + '&ord=' + ord + dict;
    } else if (stat === '0') {
      url = '/word/edit?wid=&tid=' + LWT_DATA.text.id + '&ord=' + ord + dict;
    } else {
      url = '/word/edit?wid=' + wid + '&tid=' + LWT_DATA.text.id + '&ord=' + ord + dict;
    }
    loadModalFrame(url);
    return false;
  }
  return true;
}

