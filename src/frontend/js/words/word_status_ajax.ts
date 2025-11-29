/**
 * Word Status AJAX - Functions for updating word status via AJAX
 *
 * Handles status change requests and DOM updates for the reading interface.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import $ from 'jquery';
import { updateWordStatusInDOM, updateLearnStatus } from './word_dom_updates';
import { cleanupRightFrames } from '../reading/frame_management';

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
  $('#status_change_log').text('Word status update failed!');
  cleanupRightFrames();
}

/**
 * Apply word status update to the DOM after successful AJAX call.
 *
 * @param data Word status update data
 */
export function applyWordUpdate(data: WordStatusUpdateData): void {
  $('#status_change_log').text(`Term status changed to ${data.status}`);

  updateWordStatusInDOM(
    data.wid,
    data.status,
    data.term,
    data.translation,
    data.romanization
  );

  const frameH = window.parent?.document?.getElementById('frame-h');
  if (frameH) {
    $('#learnstatus', frameH).html(data.todoContent);
  }

  cleanupRightFrames();
}

/**
 * Send AJAX request to update word status.
 *
 * @param data Word status update data
 */
export function updateWordStatusAjax(data: WordStatusUpdateData): void {
  $.post(
    `api.php/v1/terms/${data.wid}/status/${data.status}`,
    {},
    function (response: '' | { error?: string }) {
      if (response === '' || (typeof response === 'object' && 'error' in response)) {
        wordUpdateError();
      } else {
        applyWordUpdate(data);
      }
    },
    'json'
  );
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
