/**
 * Word Result Initialization - Auto-initializes word result views.
 *
 * Handles initialization of result views after word operations. Each view emits a
 * `<script type="application/json" data-lwt-*-config>` blob that the matching
 * initializer below reads and applies to the reading frame's DOM. The full set of
 * views still using this mechanism:
 *
 * - save_result.php (new word saved in multi-word context)
 * - edit_result.php (word created or updated)
 * - edit_term_result.php (word updated during review)
 * - bulk_save_result.php (bulk translated words saved)
 *
 * Keep this list in sync with the views — a handler with no emitting view is dead
 * code, which is how the delete/insert-wellknown/insert-ignore/delete-multi
 * handlers came to be removed.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since 3.0.0
 */

import { onDomReady } from '@shared/utils/dom_ready';
import {
  updateNewWordInDOM,
  updateExistingWordInDOM,
  completeWordOperation,
  getParentContext,
  updateLearnStatus,
  updateTestWordInDOM,
  updateBulkWordInDOM,
  type BulkWordUpdateParams
} from '../services/word_dom_updates';
import { cleanupRightFrames } from '@modules/text/pages/reading/frame_management';
import { loadTermTranslations, editImprTextInOpener } from '@modules/vocabulary/services/term_operations';
import { escapeHtml } from '@shared/utils/html_utils';

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
    if (termEl) termEl.innerHTML = escapeHtml(config.text);
    if (tranEl) tranEl.innerHTML = escapeHtml(config.translation);
    if (romaEl) romaEl.innerHTML = escapeHtml(config.romanization);
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
    loadTermTranslations(config.fromAnn, config.textlc ?? '', config.wid);
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
onDomReady(autoInitWordResults);
