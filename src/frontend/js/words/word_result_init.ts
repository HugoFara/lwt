/**
 * Word Result Initialization - Auto-initializes word result views.
 *
 * Handles initialization of result views after word operations:
 * - save_result.php (new word saved)
 * - edit_result.php (word created or updated)
 *
 * @license Unlicense <http://unlicense.org/>
 * @since 3.0.0
 */

import $ from 'jquery';
import {
  updateNewWordInDOM,
  updateExistingWordInDOM,
  completeWordOperation
} from './word_dom_updates';
import { do_ajax_edit_impr_text } from '../terms/term_operations';

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
