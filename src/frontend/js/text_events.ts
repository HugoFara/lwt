/**
 * Interactions and user events on text reading only.
 * Main module that coordinates text reading functionality.
 *
 * @license Unlicense <http://unlicense.org/>
 */

import { make_tooltip } from './pgm';
import { speechDispatcher } from './user_interactions';
import {
  getAttr,
  word_each_do_text_text,
  mword_each_do_text_text
} from './text_annotations';
import { keydown_event_do_text_text } from './text_keyboard';
import {
  mwordDragNDrop,
  mword_drag_n_drop_select
} from './text_multiword_selection';

// Re-export for backward compatibility
export {
  getAttr,
  word_each_do_text_text,
  mword_each_do_text_text
} from './text_annotations';
export { keydown_event_do_text_text } from './text_keyboard';
export {
  mwordDragNDrop,
  mword_drag_n_drop_select
} from './text_multiword_selection';

// Declare external functions
declare function run_overlib_status_unknown(
  dict1: string, dict2: string, translator: string, hints: string,
  textId: number, order: string, text: string, multiWords: (string | undefined)[], rtl: boolean
): void;
declare function run_overlib_status_99(
  dict1: string, dict2: string, translator: string, hints: string,
  textId: number, order: string, text: string, wid: string, multiWords: (string | undefined)[], rtl: boolean, ann: string
): void;
declare function run_overlib_status_98(
  dict1: string, dict2: string, translator: string, hints: string,
  textId: number, order: string, text: string, wid: string, multiWords: (string | undefined)[], rtl: boolean, ann: string
): void;
declare function run_overlib_status_1_to_5(
  dict1: string, dict2: string, translator: string, hints: string,
  textId: number, order: string, text: string, wid: string, status: string, multiWords: (string | undefined)[], rtl: boolean, ann: string
): void;
declare function run_overlib_multiword(
  dict1: string, dict2: string, translator: string, hints: string,
  textId: number, order: string, text: string, wid: string, status: string, code: string, ann: string
): void;
declare function showRightFrames(url1?: string, url2?: string): void;

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

// Extend JQuery for hoverIntent plugin
interface HoverIntentOptions {
  over: (this: HTMLElement) => void;
  out: (this: HTMLElement) => void;
  sensitivity?: number;
  interval?: number;
  selector?: string;
}

declare global {
  interface JQuery {
    hoverIntent(options: HoverIntentOptions): JQuery;
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
 * Handle double-click event on a word to jump to its position in audio/video.
 * Calculates the position in the text and seeks the media player accordingly.
 *
 * @param this The HTML element (word) that was double-clicked
 */
export function word_dblclick_event_do_text_text(this: HTMLElement): void {
  const $this = $(this);
  const t = parseInt($('#totalcharcount').text(), 10);
  if (t === 0) { return; }
  let p = 100 * (parseInt(getAttr($this, 'data_pos') || '0', 10) - 5) / t;
  if (p < 0) { p = 0; }
  const parentFrames = window.parent as Window & { frames: FramesWithH };
  if (typeof parentFrames.frames.h?.lwt_audio_controller?.newPosition === 'function') {
    parentFrames.frames.h.lwt_audio_controller.newPosition(p);
  }
}

/**
 * Do a word edition window. Usually called when the user clicks on a word.
 *
 * @since 2.9.10-fork Read word aloud if LWT_DATA.settings.hts equals 2.
 *
 * @returns false
 */
export function word_click_event_do_text_text(this: HTMLElement): boolean {
  const $this = $(this);
  const status = getAttr($this, 'data_status');
  const ann = getAttr($this, 'data_ann');

  let hints: string;
  if (LWT_DATA.settings.jQuery_tooltip) {
    hints = make_tooltip(
      $this.text(), getAttr($this, 'data_trans'), getAttr($this, 'data_rom'), status
    );
  } else {
    const titleAttr = $this.attr('title');
    hints = typeof titleAttr === 'string' ? titleAttr : '';
  }

  // Get multi-words containing word
  const multi_words: (string | undefined)[] = Array(7);
  for (let i = 0; i < 7; i++) {
    // Start from 2 as multi-words have at least two elements
    const mwAttr = $this.attr('data_mw' + (i + 2));
    multi_words[i] = typeof mwAttr === 'string' ? mwAttr : undefined;
  }
  const statusNum = parseInt(status || '0', 10);
  const order = getAttr($this, 'data_order');
  const wid = getAttr($this, 'data_wid');

  if (statusNum < 1) {
    run_overlib_status_unknown(
      LWT_DATA.language.dict_link1, LWT_DATA.language.dict_link2, LWT_DATA.language.translator_link, hints,
      LWT_DATA.text.id, order, $this.text(), multi_words, LWT_DATA.language.rtl
    );
    showRightFrames(
      'edit_word.php?tid=' + LWT_DATA.text.id + '&ord=' + order + '&wid='
    );
  } else if (statusNum === 99) {
    run_overlib_status_99(
      LWT_DATA.language.dict_link1, LWT_DATA.language.dict_link2, LWT_DATA.language.translator_link, hints,
      LWT_DATA.text.id, order,
      $this.text(), wid, multi_words, LWT_DATA.language.rtl, ann
    );
  } else if (statusNum === 98) {
    run_overlib_status_98(
      LWT_DATA.language.dict_link1, LWT_DATA.language.dict_link2, LWT_DATA.language.translator_link, hints,
      LWT_DATA.text.id, order,
      $this.text(), wid, multi_words, LWT_DATA.language.rtl, ann
    );
  } else {
    run_overlib_status_1_to_5(
      LWT_DATA.language.dict_link1, LWT_DATA.language.dict_link2, LWT_DATA.language.translator_link, hints,
      LWT_DATA.text.id, order,
      $this.text(), wid, status, multi_words, LWT_DATA.language.rtl, ann
    );
  }
  if (LWT_DATA.settings.hts === 2) {
    speechDispatcher($this.text(), LWT_DATA.language.id);
  }
  return false;
}

/**
 * Handle click event on a multi-word expression to display its details.
 * Shows the word overlay with dictionary links and translation options.
 *
 * @param this The HTML element (multi-word) that was clicked
 * @returns false to prevent default behavior
 */
export function mword_click_event_do_text_text(this: HTMLElement): boolean {
  const $this = $(this);
  const status = getAttr($this, 'data_status');
  if (status !== '') {
    const ann = getAttr($this, 'data_ann');
    let hints: string;
    if (LWT_DATA.settings.jQuery_tooltip) {
      hints = make_tooltip(
        $this.text(),
        getAttr($this, 'data_trans'),
        getAttr($this, 'data_rom'),
        status
      );
    } else {
      const titleAttr = $this.attr('title');
      hints = typeof titleAttr === 'string' ? titleAttr : '';
    }
    run_overlib_multiword(
      LWT_DATA.language.dict_link1, LWT_DATA.language.dict_link2, LWT_DATA.language.translator_link,
      hints,
      LWT_DATA.text.id, getAttr($this, 'data_order'), getAttr($this, 'data_text'),
      getAttr($this, 'data_wid'), status, getAttr($this, 'data_code'), ann
    );
  }
  if (LWT_DATA.settings.hts === 2) {
    speechDispatcher($this.text(), LWT_DATA.language.id);
  }
  return false;
}

/**
 * Handle mouse hover over a word to highlight all instances of the same term.
 * Also triggers text-to-speech if enabled (HTS setting = 3).
 *
 * @param this The HTML element being hovered over
 */
export function word_hover_over(this: HTMLElement): void {
  if (!$('.tword')[0]) {
    const classAttrVal = $(this).attr('class');
    const classAttr = typeof classAttrVal === 'string' ? classAttrVal : '';
    const v = classAttr.replace(/.*(TERM[^ ]*)( .*)*/, '$1');
    $('.' + v).addClass('hword');
    if (LWT_DATA.settings.jQuery_tooltip) {
      $(this).trigger('mouseover');
    }
    if (LWT_DATA.settings.hts === 3) {
      speechDispatcher($(this).text(), LWT_DATA.language.id);
    }
  }
}

/**
 * Handle mouse hover out from a word to remove highlighting.
 * Cleans up tooltip elements and removes the 'hword' class.
 */
export function word_hover_out(): void {
  $('.hword').removeClass('hword');
  if (LWT_DATA.settings.jQuery_tooltip) {
    $('.ui-helper-hidden-accessible>div[style]').remove();
  }
}

/**
 * Prepare the interaction events with the text.
 *
 * @since 2.0.3-fork
 */
export function prepareTextInteractions(): void {
  $('.word').each(word_each_do_text_text);
  $('.mword').each(mword_each_do_text_text);
  $('.word').on('click', word_click_event_do_text_text);
  $('#thetext').on('selectstart', 'span', function() { return false; }).on(
    'mousedown', '.wsty',
    { annotation: LWT_DATA.settings.annotations_mode },
    mword_drag_n_drop_select);
  $('#thetext').on('click', '.mword', mword_click_event_do_text_text);
  $('.word').on('dblclick', word_dblclick_event_do_text_text);
  $('#thetext').on('dblclick', '.mword', word_dblclick_event_do_text_text);
  $(document).on('keydown', keydown_event_do_text_text);
  $('#thetext').hoverIntent(
    {
      over: word_hover_over,
      out: word_hover_out,
      interval: 150,
      selector: '.wsty,.mwsty'
    }
  );
}

// Expose globally for backward compatibility with PHP templates
if (typeof window !== 'undefined') {
  const w = window as unknown as Record<string, unknown>;
  w.word_each_do_text_text = word_each_do_text_text;
  w.mword_each_do_text_text = mword_each_do_text_text;
  w.word_dblclick_event_do_text_text = word_dblclick_event_do_text_text;
  w.word_click_event_do_text_text = word_click_event_do_text_text;
  w.mword_click_event_do_text_text = mword_click_event_do_text_text;
  w.mwordDragNDrop = mwordDragNDrop;
  w.mword_drag_n_drop_select = mword_drag_n_drop_select;
  w.word_hover_over = word_hover_over;
  w.word_hover_out = word_hover_out;
  w.keydown_event_do_text_text = keydown_event_do_text_text;
  w.prepareTextInteractions = prepareTextInteractions;
}
