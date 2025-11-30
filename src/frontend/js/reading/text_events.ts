/**
 * Interactions and user events on text reading only.
 * Main module that coordinates text reading functionality.
 *
 * This module supports two modes of operation:
 * 1. Legacy frame-based mode (default) - uses iframe navigation for word operations
 * 2. API-based mode - uses REST API calls with in-page updates
 *
 * The mode can be switched using setUseApiMode() or by setting
 * LWT_DATA.settings.use_api_mode = true.
 *
 * @license Unlicense <http://unlicense.org/>
 */

import { make_tooltip } from '../terms/word_status';
import { speechDispatcher } from '../core/user_interactions';
import { hoverIntent } from '../core/hover_intent';
import {
  getAttr,
  word_each_do_text_text,
  mword_each_do_text_text
} from './text_annotations';
import { keydown_event_do_text_text } from './text_keyboard';
import { mword_drag_n_drop_select } from './text_multiword_selection';
import { showRightFrames } from './frame_management';
import {
  run_overlib_status_unknown,
  run_overlib_status_99,
  run_overlib_status_98,
  run_overlib_status_1_to_5,
  run_overlib_multiword,
  buildKnownWordPopupContent,
  buildUnknownWordPopupContent,
  overlib,
  CAPTION
} from '../terms/overlib_interface';
import { getContextFromElement, type WordActionContext } from './word_actions';

// Re-export from submodules
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

// Re-export word actions for external use
export {
  changeWordStatus,
  deleteWord,
  markWellKnown,
  markIgnored,
  incrementWordStatus,
  getContextFromElement,
  buildContext,
  type WordActionContext,
  type WordActionResult
} from './word_actions';

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
  /** @deprecated Use use_frame_mode instead. API mode is now default. */
  use_api_mode?: boolean;
  /** If true, use legacy frame-based navigation instead of API mode */
  use_frame_mode?: boolean;
}

interface LwtDataGlobal {
  language: LwtLanguage;
  text: LwtText;
  settings: LwtSettings;
}

declare const LWT_DATA: LwtDataGlobal;

// Module-level flag for API mode (default: true since v3.0.0)
// Can be disabled for backward compatibility with legacy frame-based mode
let useApiMode = true;

/**
 * Enable or disable API-based mode for word operations.
 *
 * API mode is enabled by default since v3.0.0.
 * When enabled, word status changes, deletions, and quick marks
 * will use the REST API instead of frame navigation.
 *
 * To use legacy frame-based mode, call setUseApiMode(false) or
 * set LWT_DATA.settings.use_frame_mode = true.
 *
 * @param enabled Whether to enable API mode
 */
export function setUseApiMode(enabled: boolean): void {
  useApiMode = enabled;
}

/**
 * Check if API-based mode is currently enabled.
 *
 * API mode is the default since v3.0.0.
 * Returns false only if explicitly disabled via setUseApiMode(false)
 * or LWT_DATA.settings.use_frame_mode = true.
 */
export function isApiModeEnabled(): boolean {
  // Check if frame mode is explicitly requested (opt-out)
  if (typeof LWT_DATA !== 'undefined' && LWT_DATA.settings?.use_frame_mode === true) {
    return false;
  }
  return useApiMode;
}

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

  // Check if we should use API-based mode
  if (isApiModeEnabled()) {
    word_click_event_api_mode(this, statusNum, multi_words);
  } else {
    word_click_event_frame_mode(
      $this, statusNum, order, wid, hints, multi_words, ann
    );
  }

  if (LWT_DATA.settings.hts === 2) {
    speechDispatcher($this.text(), LWT_DATA.language.id);
  }
  return false;
}

/**
 * Handle word click in legacy frame mode.
 * Uses iframe navigation for word operations.
 */
function word_click_event_frame_mode(
  $this: JQuery<HTMLElement>,
  statusNum: number,
  order: string,
  wid: string,
  hints: string,
  multi_words: (string | undefined)[],
  ann: string
): void {
  if (statusNum < 1) {
    run_overlib_status_unknown(
      LWT_DATA.language.dict_link1, LWT_DATA.language.dict_link2, LWT_DATA.language.translator_link, hints,
      LWT_DATA.text.id, order, $this.text(), multi_words, LWT_DATA.language.rtl
    );
    showRightFrames(
      '/word/edit?tid=' + LWT_DATA.text.id + '&ord=' + order + '&wid='
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
      $this.text(), wid, String(statusNum), multi_words, LWT_DATA.language.rtl, ann
    );
  }
}

/**
 * Handle word click in API-based mode.
 * Uses REST API calls with in-page DOM updates.
 */
function word_click_event_api_mode(
  element: HTMLElement,
  statusNum: number,
  multi_words: (string | undefined)[]
): void {
  // Build context from element
  const context = getContextFromElement(element);

  // Add text ID from global state
  context.textId = LWT_DATA.text.id;

  const dictLinks = {
    dict1: LWT_DATA.language.dict_link1,
    dict2: LWT_DATA.language.dict_link2,
    translator: LWT_DATA.language.translator_link
  };

  let content: HTMLElement | string;

  if (statusNum < 1) {
    // Unknown word - show well-known/ignore options
    content = buildUnknownWordPopupContent(
      context,
      dictLinks,
      multi_words,
      LWT_DATA.language.rtl
    );

    // Also open the edit form in the right frame for unknown words
    showRightFrames(
      '/word/edit?tid=' + LWT_DATA.text.id + '&ord=' + context.position + '&wid='
    );
  } else if (statusNum === 99 || statusNum === 98) {
    // Well-known or ignored - show edit/delete options
    content = buildKnownWordPopupContent(
      context,
      dictLinks,
      multi_words,
      LWT_DATA.language.rtl
    );
  } else {
    // Learning word (1-5) - show status change options
    content = buildKnownWordPopupContent(
      context,
      dictLinks,
      multi_words,
      LWT_DATA.language.rtl
    );
  }

  // Show popup with API-based content
  if (typeof content === 'string') {
    overlib(content, CAPTION, 'Word');
  } else {
    overlib(content.outerHTML, CAPTION, 'Word');
  }
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
  const thetext = document.getElementById('thetext');
  if (thetext) {
    hoverIntent(thetext, {
      over: word_hover_over,
      out: word_hover_out,
      interval: 150,
      selector: '.wsty,.mwsty'
    });
  }
}

