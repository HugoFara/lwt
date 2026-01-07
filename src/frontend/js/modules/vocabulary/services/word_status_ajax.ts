/**
 * Word Status AJAX - Functions for updating word status via AJAX
 *
 * Handles status change requests and DOM updates for the reading interface.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { TermsApi } from '@modules/vocabulary/api/terms_api';
import { updateWordStatusInDOM } from '../services/word_dom_updates';
import { cleanupRightFrames } from '@modules/text/pages/reading/frame_management';

export interface WordStatusUpdateData {
  wid: number;
  status: number;
  term: string;
  translation: string;
  romanization: string;
  todoContent: string;
}

/**
 * Display error message for failed word status update.
 */
export function wordUpdateError(): void {
  const logEl = document.getElementById('status_change_log');
  if (logEl) {
    logEl.textContent = 'Word status update failed!';
  }
  cleanupRightFrames();
}

/**
 * Apply word status update to the DOM after successful AJAX call.
 *
 * @param data Word status update data
 */
export function applyWordUpdate(data: WordStatusUpdateData): void {
  const logEl = document.getElementById('status_change_log');
  if (logEl) {
    logEl.textContent = `Term status changed to ${data.status}`;
  }

  updateWordStatusInDOM(
    data.wid,
    data.status,
    data.term,
    data.translation,
    data.romanization
  );

  const frameH = window.parent?.document?.getElementById('frame-h');
  if (frameH) {
    const learnStatus = frameH.querySelector('#learnstatus');
    if (learnStatus) {
      learnStatus.innerHTML = data.todoContent;
    }
  }

  cleanupRightFrames();
}

/**
 * Send AJAX request to update word status.
 *
 * @param data Word status update data
 */
export async function updateWordStatusAjax(data: WordStatusUpdateData): Promise<void> {
  const response = await TermsApi.setStatus(data.wid, data.status);

  if (response.error) {
    wordUpdateError();
  } else {
    applyWordUpdate(data);
  }
}

/**
 * Initialize word status change from result view.
 * Called from status_result.php after page load.
 *
 * @param config Configuration object with word data
 */
export function initWordStatusChange(config: WordStatusUpdateData): void {
  updateWordStatusAjax(config);
}

/**
 * Auto-initialize word status change from JSON config element.
 * Reads configuration from #word-status-config and triggers the update.
 */
function autoInitWordStatusChange(): void {
  const configEl = document.getElementById('word-status-config');
  if (!configEl) {
    return;
  }

  try {
    const config: WordStatusUpdateData = JSON.parse(configEl.textContent || '{}');
    initWordStatusChange(config);
  } catch {
    // Config parse failed, page may not be status result
  }
}

// Auto-initialize on DOMContentLoaded
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', autoInitWordStatusChange);
} else {
  autoInitWordStatusChange();
}
