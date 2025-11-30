/**
 * Word Result Initialization - Auto-initializes word result views.
 *
 * Handles initialization of result views after word operations:
 * - save_result.php (new word saved)
 * - edit_result.php (word created or updated)
 * - all_wellknown_result.php (mark all as well-known/ignored)
 *
 * @license Unlicense <http://unlicense.org/>
 * @since 3.0.0
 */

import $ from 'jquery';
import {
  updateNewWordInDOM,
  updateExistingWordInDOM,
  completeWordOperation,
  getParentContext,
  updateLearnStatus,
  updateTestWordInDOM
} from './word_dom_updates';
import { make_tooltip } from '../terms/word_status';
import { cleanupRightFrames } from '../reading/frame_management';
import { do_ajax_edit_impr_text } from '../terms/term_operations';
import { escape_html_chars } from '../core/html_utils';

/**
 * Word data for all_wellknown_result view.
 */
interface WellKnownWordData {
  wid: number;
  hex: string;
  term: string;
  status: number;
}

/**
 * Configuration for all_wellknown_result view (mark all as well-known/ignored).
 */
interface AllWellKnownConfig {
  words: WellKnownWordData[];
  useTooltips: boolean;
  todoContent: string;
}

/**
 * Configuration for save_result view (new word saved in multi-word context).
 */
interface SaveResultConfig {
  wid: number;
  status: number;
  translation: string;
  romanization: string;
  text: string;
  hex: string;
  textId: number;
  todoContent: string;
}

/**
 * Configuration for edit_result view (word created or updated).
 */
interface EditResultConfig {
  wid: number;
  status: number;
  oldStatus?: number;
  translation: string;
  romanization: string;
  text: string;
  hex?: string;
  textId: number;
  todoContent: string;
  isNew: boolean;
  fromAnn?: number;
  textlc?: string;
}

/**
 * Configuration for edit_term_result view (word updated during testing).
 */
interface EditTermResultConfig {
  wid: number;
  text: string;
  translation: string;
  translationWithTags: string;
  romanization: string;
  status: number;
  sentence: string;
  statusControlsHtml: string;
}

/**
 * Configuration for hover_save_result view (word saved via hover).
 */
interface HoverSaveResultConfig {
  wid: number;
  hex: string;
  status: number;
  translation: string;
  wordRaw: string;
  todoContent: string;
}

/**
 * Initialize edit_term_result view.
 * Updates the DOM for a word updated during testing (normal or table test).
 */
function initEditTermResult(config: EditTermResultConfig): void {
  const context = getParentContext();
  const wid = config.wid;

  // Check if we're in table test mode by checking parent URL
  let isTableTest = false;
  try {
    isTableTest = window.parent?.location?.href?.includes('type=table') ?? false;
  } catch {
    // Parent access blocked
  }

  if (isTableTest) {
    // Table Test - update table cells
    $(`#STAT${wid}`, context).html(config.statusControlsHtml);
    $(`#TERM${wid}`, context).html(escape_html_chars(config.text));
    $(`#TRAN${wid}`, context).html(escape_html_chars(config.translation));
    $(`#ROMA${wid}`, context).html(escape_html_chars(config.romanization));
    $(`#SENT${wid}`, context).html(config.sentence);
  } else {
    // Normal Test - update word attributes
    updateTestWordInDOM(
      wid,
      config.text,
      config.translationWithTags,
      config.romanization,
      config.status
    );
  }

  cleanupRightFrames();
}

/**
 * Initialize hover_save_result view.
 * Updates the DOM for a word saved via hover interaction.
 */
function initHoverSaveResult(config: HoverSaveResultConfig): void {
  const context = getParentContext();

  let title = '';
  try {
    if (window.parent?.LWT_DATA?.settings?.jQuery_tooltip) {
      title = make_tooltip(config.wordRaw, config.translation, '', String(config.status));
    }
  } catch {
    // Parent access blocked
  }

  $(`.TERM${config.hex}`, context)
    .removeClass('status0')
    .addClass(`status${config.status} word${config.wid}`)
    .attr('data_status', String(config.status))
    .attr('data_wid', String(config.wid))
    .attr('title', title)
    .attr('data_trans', config.translation);

  updateLearnStatus(config.todoContent);
  cleanupRightFrames();
}

/**
 * Initialize all_wellknown_result view.
 * Updates the DOM for all words marked as well-known or ignored.
 */
function initAllWellKnownResult(config: AllWellKnownConfig): void {
  const context = getParentContext();

  config.words.forEach((word) => {
    let title = '';
    if (config.useTooltips) {
      title = make_tooltip(word.term, '*', '', String(word.status));
    }

    $(`.TERM${word.hex}`, context)
      .removeClass('status0')
      .addClass(`status${word.status} word${word.wid}`)
      .attr('data_status', String(word.status))
      .attr('data_wid', String(word.wid))
      .attr('title', title);
  });

  updateLearnStatus(config.todoContent);

  // Trigger cClick in parent after a delay
  try {
    if (window.parent && typeof window.parent.cClick === 'function') {
      window.parent.setTimeout(window.parent.cClick, 1000);
    }
  } catch {
    // Parent access may be blocked, ignore
  }
}

/**
 * Initialize save_result view.
 * Updates the DOM for a newly saved word.
 */
function initSaveResult(config: SaveResultConfig): void {
  updateNewWordInDOM({
    wid: config.wid,
    status: config.status,
    translation: config.translation,
    romanization: config.romanization,
    text: config.text,
    hex: config.hex
  });

  completeWordOperation(config.todoContent);
}

/**
 * Initialize edit_result view.
 * Updates the DOM for a new or updated word.
 */
function initEditResult(config: EditResultConfig): void {
  // Handle annotation mode (popup window context)
  if (config.fromAnn !== undefined) {
    if (window.opener && typeof window.opener.do_ajax_edit_impr_text === 'function') {
      window.opener.do_ajax_edit_impr_text(
        config.fromAnn,
        config.textlc ?? '',
        config.wid
      );
    } else {
      do_ajax_edit_impr_text(config.fromAnn, config.textlc ?? '', config.wid);
    }
    return;
  }

  // Normal mode: update word in reading frame
  if (config.isNew) {
    updateNewWordInDOM({
      wid: config.wid,
      status: config.status,
      translation: config.translation,
      romanization: config.romanization,
      text: config.text,
      hex: config.hex
    });
  } else {
    updateExistingWordInDOM(
      {
        wid: config.wid,
        status: config.status,
        translation: config.translation,
        romanization: config.romanization,
        text: config.text
      },
      config.oldStatus ?? config.status
    );
  }

  completeWordOperation(config.todoContent);
}

/**
 * Auto-initialize word result views from JSON config elements.
 */
export function autoInitWordResults(): void {
  // All well-known result
  const wellKnownConfigEl = document.querySelector<HTMLScriptElement>('script[data-lwt-all-wellknown-config]');
  if (wellKnownConfigEl) {
    try {
      const config = JSON.parse(wellKnownConfigEl.textContent || '{}') as AllWellKnownConfig;
      initAllWellKnownResult(config);
    } catch (e) {
      console.error('Failed to parse all wellknown result config:', e);
    }
  }

  // Edit term result (testing context)
  const editTermConfigEl = document.querySelector<HTMLScriptElement>('script[data-lwt-edit-term-result-config]');
  if (editTermConfigEl) {
    try {
      const config = JSON.parse(editTermConfigEl.textContent || '{}') as EditTermResultConfig;
      initEditTermResult(config);
    } catch (e) {
      console.error('Failed to parse edit term result config:', e);
    }
  }

  // Hover save result
  const hoverSaveConfigEl = document.querySelector<HTMLScriptElement>('script[data-lwt-hover-save-result-config]');
  if (hoverSaveConfigEl) {
    try {
      const config = JSON.parse(hoverSaveConfigEl.textContent || '{}') as HoverSaveResultConfig;
      initHoverSaveResult(config);
    } catch (e) {
      console.error('Failed to parse hover save result config:', e);
    }
  }

  // Save result
  const saveConfigEl = document.querySelector<HTMLScriptElement>('script[data-lwt-save-result-config]');
  if (saveConfigEl) {
    try {
      const config = JSON.parse(saveConfigEl.textContent || '{}') as SaveResultConfig;
      initSaveResult(config);
    } catch (e) {
      console.error('Failed to parse save result config:', e);
    }
  }

  // Edit result
  const editConfigEl = document.querySelector<HTMLScriptElement>('script[data-lwt-edit-result-config]');
  if (editConfigEl) {
    try {
      const config = JSON.parse(editConfigEl.textContent || '{}') as EditResultConfig;
      initEditResult(config);
    } catch (e) {
      console.error('Failed to parse edit result config:', e);
    }
  }
}

// Auto-initialize on DOM ready
$(document).ready(autoInitWordResults);
