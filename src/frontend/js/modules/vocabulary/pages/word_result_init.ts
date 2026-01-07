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

import {
  updateNewWordInDOM,
  updateExistingWordInDOM,
  completeWordOperation,
  getParentContext,
  updateLearnStatus,
  updateTestWordInDOM,
  deleteWordFromDOM,
  markWordWellKnownInDOM,
  markWordIgnoredInDOM,
  updateMultiWordInDOM,
  deleteMultiWordFromDOM,
  updateBulkWordInDOM,
  type BulkWordUpdateParams
} from '../services/word_dom_updates';
import { make_tooltip } from '@modules/vocabulary/services/word_status';
import { cleanupRightFrames } from '@modules/text/pages/reading/frame_management';
import { do_ajax_edit_impr_text, editImprTextInOpener } from '@modules/vocabulary/services/term_operations';
import { closeParentPopup } from '@modules/vocabulary/components/word_popup';
import { escape_html_chars } from '@shared/utils/html_utils';

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
 * Configuration for delete_result view (word deleted).
 */
interface DeleteResultConfig {
  wid: number;
  term: string;
  todoContent: string;
}

/**
 * Configuration for insert_wellknown_result view (word marked as well-known).
 */
interface InsertWellKnownResultConfig {
  wid: number;
  hex: string;
  term: string;
  todoContent: string;
}

/**
 * Configuration for insert_ignore_result view (word marked as ignored).
 */
interface InsertIgnoreResultConfig {
  wid: number;
  hex: string;
  term: string;
  todoContent: string;
}

/**
 * Configuration for edit_multi_update_result view (multi-word expression updated).
 */
interface EditMultiUpdateResultConfig {
  wid: number;
  text: string;
  translation: string;
  romanization: string;
  status: number;
  oldStatus: number;
}

/**
 * Configuration for delete_multi_result view (multi-word expression deleted).
 */
interface DeleteMultiResultConfig {
  wid: number;
  showAll: boolean;
  todoContent: string;
}

/**
 * Configuration for bulk_save_result view (bulk translated words saved).
 */
interface BulkSaveResultConfig {
  words: BulkWordUpdateParams[];
  useTooltip: boolean;
  cleanUp: boolean;
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
    const statEl = context.getElementById(`STAT${wid}`);
    const termEl = context.getElementById(`TERM${wid}`);
    const tranEl = context.getElementById(`TRAN${wid}`);
    const romaEl = context.getElementById(`ROMA${wid}`);
    const sentEl = context.getElementById(`SENT${wid}`);

    if (statEl) statEl.innerHTML = config.statusControlsHtml;
    if (termEl) termEl.innerHTML = escape_html_chars(config.text);
    if (tranEl) tranEl.innerHTML = escape_html_chars(config.translation);
    if (romaEl) romaEl.innerHTML = escape_html_chars(config.romanization);
    if (sentEl) sentEl.innerHTML = config.sentence;
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

  // Always generate tooltips (jQuery UI tooltips removed, now using native)
  const title = make_tooltip(config.wordRaw, config.translation, '', String(config.status));

  context.querySelectorAll<HTMLElement>(`.TERM${config.hex}`).forEach(el => {
    el.classList.remove('status0');
    el.classList.add(`status${config.status}`, `word${config.wid}`);
    el.setAttribute('data_status', String(config.status));
    el.setAttribute('data_wid', String(config.wid));
    el.title = title;
    el.setAttribute('data_trans', config.translation);
  });

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

    context.querySelectorAll<HTMLElement>(`.TERM${word.hex}`).forEach(el => {
      el.classList.remove('status0');
      el.classList.add(`status${word.status}`, `word${word.wid}`);
      el.setAttribute('data_status', String(word.status));
      el.setAttribute('data_wid', String(word.wid));
      el.title = title;
    });
  });

  updateLearnStatus(config.todoContent);

  // Close popup in parent frame after a delay
  setTimeout(() => closeParentPopup(), 1000);
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
    // Try to update opener window via custom event, fall back to local update
    editImprTextInOpener(config.fromAnn, config.textlc ?? '', config.wid);
    // Also run locally in case opener is same-origin or event didn't work
    do_ajax_edit_impr_text(config.fromAnn, config.textlc ?? '', config.wid);
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
 * Initialize delete_result view.
 * Updates the DOM after a word is deleted (reset to unknown state).
 */
function initDeleteResult(config: DeleteResultConfig): void {
  deleteWordFromDOM(config.wid, config.term);
  completeWordOperation(config.todoContent);
}

/**
 * Initialize insert_wellknown_result view.
 * Updates the DOM after marking a word as well-known (status 99).
 */
function initInsertWellKnownResult(config: InsertWellKnownResultConfig): void {
  markWordWellKnownInDOM(config.wid, config.hex, config.term);
  completeWordOperation(config.todoContent);
}

/**
 * Initialize insert_ignore_result view.
 * Updates the DOM after marking a word as ignored (status 98).
 */
function initInsertIgnoreResult(config: InsertIgnoreResultConfig): void {
  markWordIgnoredInDOM(config.wid, config.hex, config.term);
  completeWordOperation(config.todoContent);
}

/**
 * Initialize edit_multi_update_result view.
 * Updates the DOM after a multi-word expression is updated.
 */
function initEditMultiUpdateResult(config: EditMultiUpdateResultConfig): void {
  updateMultiWordInDOM(
    config.wid,
    config.text,
    config.translation,
    config.romanization,
    config.status,
    config.oldStatus
  );
  cleanupRightFrames();
}

/**
 * Initialize delete_multi_result view.
 * Updates the DOM after a multi-word expression is deleted.
 */
function initDeleteMultiResult(config: DeleteMultiResultConfig): void {
  deleteMultiWordFromDOM(config.wid, config.showAll);
  completeWordOperation(config.todoContent);
}

/**
 * Initialize bulk_save_result view.
 * Updates the DOM after bulk translated words are saved.
 */
function initBulkSaveResult(config: BulkSaveResultConfig): void {
  config.words.forEach((term) => {
    updateBulkWordInDOM(term, config.useTooltip);
  });

  updateLearnStatus(config.todoContent);

  // Remove the "Updating Texts" message
  document.getElementById('displ_message')?.remove();

  if (config.cleanUp) {
    cleanupRightFrames();
  }
}

/**
 * Auto-initialize word result views from JSON config elements.
 */
export function autoInitWordResults(): void {
  // Cleanup frames (for show.php and similar views)
  if (document.querySelector('[data-lwt-cleanup-frames="true"]')) {
    cleanupRightFrames();
  }

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

  // Delete result
  const deleteConfigEl = document.querySelector<HTMLScriptElement>('script[data-lwt-delete-result-config]');
  if (deleteConfigEl) {
    try {
      const config = JSON.parse(deleteConfigEl.textContent || '{}') as DeleteResultConfig;
      initDeleteResult(config);
    } catch (e) {
      console.error('Failed to parse delete result config:', e);
    }
  }

  // Insert well-known result
  const insertWellKnownConfigEl = document.querySelector<HTMLScriptElement>('script[data-lwt-insert-wellknown-result-config]');
  if (insertWellKnownConfigEl) {
    try {
      const config = JSON.parse(insertWellKnownConfigEl.textContent || '{}') as InsertWellKnownResultConfig;
      initInsertWellKnownResult(config);
    } catch (e) {
      console.error('Failed to parse insert wellknown result config:', e);
    }
  }

  // Insert ignore result
  const insertIgnoreConfigEl = document.querySelector<HTMLScriptElement>('script[data-lwt-insert-ignore-result-config]');
  if (insertIgnoreConfigEl) {
    try {
      const config = JSON.parse(insertIgnoreConfigEl.textContent || '{}') as InsertIgnoreResultConfig;
      initInsertIgnoreResult(config);
    } catch (e) {
      console.error('Failed to parse insert ignore result config:', e);
    }
  }

  // Edit multi-word update result
  const editMultiUpdateConfigEl = document.querySelector<HTMLScriptElement>('script[data-lwt-edit-multi-update-result-config]');
  if (editMultiUpdateConfigEl) {
    try {
      const config = JSON.parse(editMultiUpdateConfigEl.textContent || '{}') as EditMultiUpdateResultConfig;
      initEditMultiUpdateResult(config);
    } catch (e) {
      console.error('Failed to parse edit multi update result config:', e);
    }
  }

  // Delete multi-word result
  const deleteMultiConfigEl = document.querySelector<HTMLScriptElement>('script[data-lwt-delete-multi-result-config]');
  if (deleteMultiConfigEl) {
    try {
      const config = JSON.parse(deleteMultiConfigEl.textContent || '{}') as DeleteMultiResultConfig;
      initDeleteMultiResult(config);
    } catch (e) {
      console.error('Failed to parse delete multi result config:', e);
    }
  }

  // Bulk save result
  const bulkSaveConfigEl = document.querySelector<HTMLScriptElement>('script[data-lwt-bulk-save-result-config]');
  if (bulkSaveConfigEl) {
    try {
      const config = JSON.parse(bulkSaveConfigEl.textContent || '{}') as BulkSaveResultConfig;
      initBulkSaveResult(config);
    } catch (e) {
      console.error('Failed to parse bulk save result config:', e);
    }
  }
}

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', autoInitWordResults);
} else {
  autoInitWordResults();
}
