/**
 * Keyboard navigation and shortcuts for text reading.
 * Handles all keyboard events during text reading mode.
 *
 * @license Unlicense <http://unlicense.org/>
 */

import { getLangFromDict, createTheDictUrl } from '../legacy/pgm';
import { speechDispatcher } from '../legacy/user_interactions';
import { getAttr } from './text_annotations';

// Declare external functions
declare function showRightFrames(url1?: string, url2?: string): void;
declare function owin(url: string): Window | null;
declare function cClick(): void;
declare function get_position_from_id(id: string): number;

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

// Extend JQuery for scrollTo plugin
declare global {
  interface JQuery {
    scrollTo(target: unknown, options?: { axis?: string; offset?: number }): JQuery;
  }
}

// Audio controller type for frames
interface AudioController {
  newPosition: (p: number) => void;
}

interface LwtFrameH extends Window {
  lwt_audio_controller: AudioController;
}

interface FramesWithH {
  h: LwtFrameH;
}

/**
 * Handle keyboard events during text reading.
 * ESC key resets reading position, arrow keys navigate between words.
 *
 * @param e jQuery keyboard event
 * @returns false to prevent default behavior, true otherwise
 */
export function keydown_event_do_text_text(e: JQuery.KeyDownEvent): boolean {
  if (e.which === 27) { // esc = reset all
    LWT_DATA.text.reading_position = -1;
    $('span.uwordmarked').removeClass('uwordmarked');
    $('span.kwordmarked').removeClass('kwordmarked');
    cClick();
    return false;
  }

  if (e.which === 13) { // return = edit next unknown word
    $('span.uwordmarked').removeClass('uwordmarked');
    const unknownwordlist = $('span.status0.word:not(.hide):first');
    if (unknownwordlist.length === 0) return false;
    $(window).scrollTo(unknownwordlist, { axis: 'y', offset: -150 });
    unknownwordlist.addClass('uwordmarked').trigger('click');
    cClick();
    return false;
  }

  const knownwordlist = $(
    'span.word:not(.hide):not(.status0)' + LWT_DATA.settings.word_status_filter +
      ',span.mword:not(.hide)' + LWT_DATA.settings.word_status_filter
  );
  const l_knownwordlist = knownwordlist.length;
  if (l_knownwordlist === 0) return true;

  // the following only for a non-zero known words list
  let curr: JQuery;
  let ann: string;

  if (e.which === 36) { // home : known word navigation -> first
    $('span.kwordmarked').removeClass('kwordmarked');
    LWT_DATA.text.reading_position = 0;
    curr = knownwordlist.eq(LWT_DATA.text.reading_position);
    curr.addClass('kwordmarked');
    $(window).scrollTo(curr, { axis: 'y', offset: -150 });
    ann = getAttr(curr, 'data_ann');
    showRightFrames(
      'show_word.php?wid=' + getAttr(curr, 'data_wid') + '&ann=' +
        encodeURIComponent(ann)
    );
    return false;
  }
  if (e.which === 35) { // end : known word navigation -> last
    $('span.kwordmarked').removeClass('kwordmarked');
    LWT_DATA.text.reading_position = l_knownwordlist - 1;
    curr = knownwordlist.eq(LWT_DATA.text.reading_position);
    curr.addClass('kwordmarked');
    $(window).scrollTo(curr, { axis: 'y', offset: -150 });
    ann = getAttr(curr, 'data_ann');
    showRightFrames(
      'show_word.php?wid=' + getAttr(curr, 'data_wid') + '&ann=' +
        encodeURIComponent(ann)
    );
    return false;
  }
  if (e.which === 37) { // left : known word navigation
    const marked = $('span.kwordmarked');
    let currid: number;
    if (marked.length === 0) {
      currid = 100000000;
    } else {
      const markedId = marked.attr('id');
      currid = get_position_from_id(typeof markedId === 'string' ? markedId : '');
    }
    $('span.kwordmarked').removeClass('kwordmarked');
    LWT_DATA.text.reading_position = l_knownwordlist - 1;
    for (let i = l_knownwordlist - 1; i >= 0; i--) {
      const itemId = knownwordlist.eq(i).attr('id');
      const iid = get_position_from_id(typeof itemId === 'string' ? itemId : '');
      if (iid < currid) {
        LWT_DATA.text.reading_position = i;
        break;
      }
    }
    curr = knownwordlist.eq(LWT_DATA.text.reading_position);
    curr.addClass('kwordmarked');
    $(window).scrollTo(curr, { axis: 'y', offset: -150 });
    ann = getAttr(curr, 'data_ann');
    showRightFrames(
      'show_word.php?wid=' + getAttr(curr, 'data_wid') + '&ann=' +
        encodeURIComponent(ann)
    );
    return false;
  }
  if (e.which === 39 || e.which === 32) { // space /right : known word navigation
    const marked = $('span.kwordmarked');
    let currid: number;
    if (marked.length === 0) {
      currid = -1;
    } else {
      const markedId = marked.attr('id');
      currid = get_position_from_id(typeof markedId === 'string' ? markedId : '');
    }
    $('span.kwordmarked').removeClass('kwordmarked');
    LWT_DATA.text.reading_position = 0;
    for (let i = 0; i < l_knownwordlist; i++) {
      const itemId = knownwordlist.eq(i).attr('id');
      const iid = get_position_from_id(typeof itemId === 'string' ? itemId : '');
      if (iid > currid) {
        LWT_DATA.text.reading_position = i;
        break;
      }
    }

    curr = knownwordlist.eq(LWT_DATA.text.reading_position);
    curr.addClass('kwordmarked');
    $(window).scrollTo(curr, { axis: 'y', offset: -150 });
    ann = getAttr(curr, 'data_ann');
    showRightFrames(
      'show_word.php?wid=' + getAttr(curr, 'data_wid') + '&ann=' +
        encodeURIComponent(ann)
    );
    return false;
  }

  if ((!$('.kwordmarked, .uwordmarked')[0]) && $('.hword:hover')[0]) {
    curr = $('.hword:hover');
  } else {
    if (LWT_DATA.text.reading_position < 0 || LWT_DATA.text.reading_position >= l_knownwordlist) return true;
    curr = knownwordlist.eq(LWT_DATA.text.reading_position);
  }
  const wid = getAttr(curr, 'data_wid');
  const ord = getAttr(curr, 'data_order');
  const stat = getAttr(curr, 'data_status');
  const txt = curr.hasClass('mwsty') ? getAttr(curr, 'data_text') : curr.text();
  let dict = '';

  for (let i = 1; i <= 5; i++) {
    if (e.which === (48 + i) || e.which === (96 + i)) { // 1,.. : status=i
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
        showRightFrames(
          'set_word_on_hover.php?text=' + txt + '&tid=' + LWT_DATA.text.id + '&status=' + statusVal
        );
      } else {
        showRightFrames(
          'set_word_status.php?wid=' + wid + '&tid=' + LWT_DATA.text.id + '&ord=' + ord +
            '&status=' + i
        );
        return false;
      }
    }
  }
  if (e.which === 73) { // I : status=98
    if (stat === '0') {
      showRightFrames(
        'set_word_on_hover.php?text=' + txt + '&tid=' + LWT_DATA.text.id +
          '&status=98'
      );
    } else {
      showRightFrames(
        'set_word_status.php?wid=' + wid + '&tid=' + LWT_DATA.text.id +
          '&ord=' + ord + '&status=98'
      );
      return false;
    }
  }
  if (e.which === 87) { // W : status=99
    if (stat === '0') {
      showRightFrames(
        'set_word_on_hover.php?text=' + txt + '&tid=' + LWT_DATA.text.id + '&status=99'
      );
    } else {
      showRightFrames(
        'set_word_status.php?wid=' + wid + '&tid=' + LWT_DATA.text.id + '&ord=' + ord +
          '&status=99'
      );
    }
    return false;
  }
  if (e.which === 80) { // P : pronounce term
    speechDispatcher(txt, LWT_DATA.language.id);
    return false;
  }
  if (e.which === 84) { // T : translate sentence
    let popup = false;
    let dict_link = LWT_DATA.language.translator_link;
    if (LWT_DATA.language.translator_link.startsWith('*')) {
      popup = true;
      dict_link = dict_link.substring(1);
    }
    if (dict_link.startsWith('ggl.php')) {
      dict_link = 'http://' + dict_link;
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
    if (popup) {
      owin('trans.php?x=1&i=' + ord + '&t=' + LWT_DATA.text.id);
    } else if (open_url) {
      showRightFrames(undefined, 'trans.php?x=1&i=' + ord + '&t=' + LWT_DATA.text.id);
    }
    return false;
  }
  if (e.which === 65) { // A : set audio pos.
    let p = parseInt(getAttr(curr, 'data_pos') || '0', 10);
    const t = parseInt($('#totalcharcount').text(), 10);
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
  if (e.which === 71) { //  G : edit term and open GTr
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
        showRightFrames(undefined, createTheDictUrl(target_url, txt));
      }
    }, 10);
  }
  if (e.which === 69 || e.which === 71) { //  E / G: edit term
    let url = '';
    if (curr.hasClass('mword')) {
      url = 'edit_mword.php?wid=' + wid + '&len=' + getAttr(curr, 'data_code') +
        '&tid=' + LWT_DATA.text.id + '&ord=' + ord + dict;
    } else if (stat === '0') {
      url = 'edit_word.php?wid=&tid=' + LWT_DATA.text.id + '&ord=' + ord + dict;
    } else {
      url = 'edit_word.php?wid=' + wid + '&tid=' + LWT_DATA.text.id + '&ord=' + ord + dict;
    }
    showRightFrames(url);
    return false;
  }
  return true;
}

// Expose globally for backward compatibility with PHP templates
if (typeof window !== 'undefined') {
  const w = window as unknown as Record<string, unknown>;
  w.keydown_event_do_text_text = keydown_event_do_text_text;
}
