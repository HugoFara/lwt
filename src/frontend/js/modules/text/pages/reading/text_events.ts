/**
 * Interactions and user events on text reading only.
 * Main module that coordinates text reading functionality.
 *
 * This module supports two modes of operation:
 * 1. Legacy frame-based mode (default) - uses iframe navigation for word operations
 * 2. API-based mode - uses REST API calls with in-page updates
 *
 * The mode can be switched using setUseApiMode().
 *
 * @license Unlicense <http://unlicense.org/>
 */

import { speechDispatcher } from '@shared/utils/user_interactions';
import { hoverIntent } from '@shared/utils/hover_intent';
import {
  word_each_do_text_text,
  mword_each_do_text_text
} from './text_annotations';
import { keydown_event_do_text_text } from './text_keyboard';
import { setupMultiWordSelection } from './text_multiword_selection';
import { loadModalFrame } from '@modules/text/pages/reading/frame_management';
import {
  run_overlib_status_unknown,
  run_overlib_status_99,
  run_overlib_status_98,
  run_overlib_status_1_to_5,
  run_overlib_multiword,
  buildKnownWordPopupContent,
  buildUnknownWordPopupContent,
  overlib
} from '@modules/vocabulary/services/overlib_interface';
import { getContextFromElement } from '@modules/vocabulary/services/word_actions';
import {
  getLanguageId,
  getDictionaryLinks,
  isRtl
} from '@modules/language/stores/language_config';
import { getTextId } from '@modules/text/stores/text_config';
import {
  isTtsOnHover,
  isTtsOnClick,
  isFrameModeEnabled
} from '@shared/utils/settings_config';

// Re-export from submodules
export {
  getAttr,
  word_each_do_text_text,
  mword_each_do_text_text
} from './text_annotations';
export { keydown_event_do_text_text } from './text_keyboard';
export {
  mwordDragNDrop,
  mword_drag_n_drop_select,
  mword_touch_select,
  setupMultiWordSelection,
  handleTextSelection
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
} from '@modules/vocabulary/services/word_actions';

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
 * configure frame mode in settings.
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
 * or if frame mode is enabled in settings.
 */
export function isApiModeEnabled(): boolean {
  // Check if frame mode is explicitly requested (opt-out)
  if (isFrameModeEnabled()) {
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
  const totalCharEl = document.getElementById('totalcharcount');
  const t = parseInt(totalCharEl?.textContent || '0', 10);
  if (t === 0) { return; }
  let p = 100 * (parseInt(this.getAttribute('data_pos') || '0', 10) - 5) / t;
  if (p < 0) { p = 0; }
  const parentFrames = window.parent as Window & { frames: FramesWithH };
  if (typeof parentFrames.frames.h?.lwt_audio_controller?.newPosition === 'function') {
    parentFrames.frames.h.lwt_audio_controller.newPosition(p);
  }
}

/**
 * Do a word edition window. Usually called when the user clicks on a word.
 *
 * @since 2.9.10-fork Reads word aloud if hover-to-speak setting equals 2.
 *
 * @returns false
 */
export function word_click_event_do_text_text(this: HTMLElement): boolean {
  const status = this.getAttribute('data_status') || '';
  const ann = this.getAttribute('data_ann') || '';
  const text = this.textContent || '';

  // Native tooltips are now used by default
  const hints = this.getAttribute('title') || '';

  // Get multi-words containing word
  const multi_words: (string | undefined)[] = Array(7);
  for (let i = 0; i < 7; i++) {
    // Start from 2 as multi-words have at least two elements
    const mwAttr = this.getAttribute('data_mw' + (i + 2));
    multi_words[i] = mwAttr !== null ? mwAttr : undefined;
  }
  const statusNum = parseInt(status || '0', 10);
  const order = this.getAttribute('data_order') || '';
  const wid = this.getAttribute('data_wid') || '';

  // Check if we should use API-based mode
  if (isApiModeEnabled()) {
    word_click_event_api_mode(this, statusNum, multi_words);
  } else {
    word_click_event_frame_mode(
      this, statusNum, order, wid, hints, multi_words, ann
    );
  }

  if (isTtsOnHover()) {
    speechDispatcher(text, getLanguageId());
  }
  return false;
}

/**
 * Handle word click in legacy frame mode.
 * Uses iframe navigation for word operations.
 */
function word_click_event_frame_mode(
  element: HTMLElement,
  statusNum: number,
  order: string,
  wid: string,
  hints: string,
  multi_words: (string | undefined)[],
  ann: string
): void {
  const text = element.textContent || '';
  const textId = getTextId();
  const dictLinks = getDictionaryLinks();
  const rtl = isRtl();

  if (statusNum < 1) {
    run_overlib_status_unknown(
      dictLinks.dict1, dictLinks.dict2, dictLinks.translator, hints,
      textId, order, text, multi_words, rtl
    );
    loadModalFrame(
      '/word/edit?tid=' + textId + '&ord=' + order + '&wid='
    );
  } else if (statusNum === 99) {
    run_overlib_status_99(
      dictLinks.dict1, dictLinks.dict2, dictLinks.translator, hints,
      textId, order,
      text, wid, multi_words, rtl, ann
    );
  } else if (statusNum === 98) {
    run_overlib_status_98(
      dictLinks.dict1, dictLinks.dict2, dictLinks.translator, hints,
      textId, order,
      text, wid, multi_words, rtl, ann
    );
  } else {
    run_overlib_status_1_to_5(
      dictLinks.dict1, dictLinks.dict2, dictLinks.translator, hints,
      textId, order,
      text, wid, String(statusNum), multi_words, rtl, ann
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

  // Add text ID from config
  context.textId = getTextId();

  const dictLinks = getDictionaryLinks();
  const rtl = isRtl();

  let content: HTMLElement | string;

  if (statusNum < 1) {
    // Unknown word - show well-known/ignore options
    content = buildUnknownWordPopupContent(
      context,
      dictLinks,
      multi_words,
      rtl
    );

    // Also open the edit form in the right frame for unknown words
    loadModalFrame(
      '/word/edit?tid=' + context.textId + '&ord=' + context.position + '&wid='
    );
  } else if (statusNum === 99 || statusNum === 98) {
    // Well-known or ignored - show edit/delete options
    content = buildKnownWordPopupContent(
      context,
      dictLinks,
      multi_words,
      rtl
    );
  } else {
    // Learning word (1-5) - show status change options
    content = buildKnownWordPopupContent(
      context,
      dictLinks,
      multi_words,
      rtl
    );
  }

  // Show popup with API-based content
  if (typeof content === 'string') {
    overlib(content, 'Word');
  } else {
    overlib(content.outerHTML, 'Word');
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
  const status = this.getAttribute('data_status') || '';
  const text = this.textContent || '';
  if (status !== '') {
    const ann = this.getAttribute('data_ann') || '';
    // Native tooltips are now used by default
    const hints = this.getAttribute('title') || '';
    const dictLinks = getDictionaryLinks();

    run_overlib_multiword(
      dictLinks.dict1, dictLinks.dict2, dictLinks.translator,
      hints,
      getTextId(),
      this.getAttribute('data_order') || '',
      this.getAttribute('data_text') || '',
      this.getAttribute('data_wid') || '',
      status,
      this.getAttribute('data_code') || '',
      ann
    );
  }
  if (isTtsOnHover()) {
    speechDispatcher(text, getLanguageId());
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
  if (!document.querySelector('.tword')) {
    const classAttr = this.className || '';
    const v = classAttr.replace(/.*(TERM[^ ]*)( .*)*/, '$1');
    document.querySelectorAll('.' + v).forEach((el) => {
      el.classList.add('hword');
    });
    if (isTtsOnClick()) {
      speechDispatcher(this.textContent || '', getLanguageId());
    }
  }
}

/**
 * Handle mouse hover out from a word to remove highlighting.
 * Cleans up tooltip elements and removes the 'hword' class.
 */
export function word_hover_out(): void {
  document.querySelectorAll('.hword').forEach((el) => {
    el.classList.remove('hword');
  });
}

/**
 * Prepare the interaction events with the text.
 *
 * @since 2.0.3-fork
 */
export function prepareTextInteractions(): void {
  // Process annotations for words and multi-words
  document.querySelectorAll<HTMLElement>('.word').forEach((el) => {
    word_each_do_text_text.call(el);
  });
  document.querySelectorAll<HTMLElement>('.mword').forEach((el) => {
    mword_each_do_text_text.call(el);
  });

  // Word click events
  document.querySelectorAll<HTMLElement>('.word').forEach((el) => {
    el.addEventListener('click', function(this: HTMLElement) {
      word_click_event_do_text_text.call(this);
    });
  });

  const thetext = document.getElementById('thetext');
  if (thetext) {
    // Multi-word selection via native text selection
    // When user selects multiple words, the multi-word modal opens
    setupMultiWordSelection(thetext);

    // Multi-word click events (delegated)
    thetext.addEventListener('click', (e) => {
      const target = e.target as HTMLElement;
      if (target.classList.contains('mword')) {
        mword_click_event_do_text_text.call(target);
      }
    });

    // Multi-word double-click events (delegated)
    thetext.addEventListener('dblclick', (e) => {
      const target = e.target as HTMLElement;
      if (target.classList.contains('mword')) {
        word_dblclick_event_do_text_text.call(target);
      }
    });

    // Hover intent for words
    hoverIntent(thetext, {
      over: word_hover_over,
      out: word_hover_out,
      interval: 150,
      selector: '.wsty,.mwsty'
    });
  }

  // Word double-click events
  document.querySelectorAll<HTMLElement>('.word').forEach((el) => {
    el.addEventListener('dblclick', function(this: HTMLElement) {
      word_dblclick_event_do_text_text.call(this);
    });
  });

  // Keyboard events
  document.addEventListener('keydown', (e: KeyboardEvent) => {
    if (!keydown_event_do_text_text(e)) {
      e.preventDefault();
    }
  });
}

