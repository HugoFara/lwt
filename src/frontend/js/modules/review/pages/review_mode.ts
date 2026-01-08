/**
 * Review Mode - Event handlers for vocabulary review
 *
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 */

import { showReviewWordPopup } from '@modules/vocabulary/services/word_popup_interface';
import { cleanupRightFrames } from '@modules/text/pages/reading/frame_management';
import { getCurrentWordId, getReviewSolution, isAnswerOpened, openAnswer } from '@modules/review/stores/review_state';
import { getDictionaryLinks } from '@modules/language/stores/language_config';
import { ReviewApi } from '@modules/review/api/review_api';

/**
 * Helper to safely get an HTML attribute value as a string.
 */
function getAttr(el: HTMLElement, attr: string): string {
  return el.getAttribute(attr) || '';
}

/**
 * Update review status and reload to next word.
 *
 * @param wordId Word ID
 * @param status New status (or undefined to keep current)
 * @param change Status change amount (+1 or -1)
 */
async function updateReviewStatusAndReload(
  wordId: number,
  status?: number,
  change?: number
): Promise<void> {
  await ReviewApi.updateStatus(wordId, status, change);
  // Reload the page to show next review word
  window.location.reload();
}

/**
 * Prepare a dialog when the user clicks a word during a review.
 *
 * @returns false
 */
export function handleReviewWordClick(this: HTMLElement): boolean {
  const dictLinks = getDictionaryLinks();
  showReviewWordPopup(
    dictLinks.dict1, dictLinks.dict2, dictLinks.translator,
    getAttr(this, 'data_wid'),
    getAttr(this, 'data_text'),
    getAttr(this, 'data_trans'),
    getAttr(this, 'data_rom'),
    getAttr(this, 'data_status'),
    getAttr(this, 'data_sent'),
    parseInt(getAttr(this, 'data_todo'), 10)
  );
  const todoEl = document.querySelector('.todo');
  if (todoEl) {
    todoEl.textContent = getReviewSolution();
  }
  return false;
}

/**
 * Handle keyboard interaction when reviewing a word.
 *
 * @param e A keystroke object
 * @returns true if nothing was done, false otherwise
 */
export function handleReviewKeydown(e: KeyboardEvent): boolean {
  const wordEl = document.querySelector('.word') as HTMLElement | null;
  const wordId = getCurrentWordId();

  if ((e.key === ' ' || e.key === 'Space' || e.which === 32) && !isAnswerOpened()) {
    // space : show solution - click word to show popup and open answer
    wordEl?.click();
    cleanupRightFrames();
    openAnswer();
    return false;
  }

  if (e.key === 'Escape' || e.which === 27) {
    // esc : skip term, don't change status - just reload to next word
    const currentStatus = parseInt(wordEl?.getAttribute('data_status') || '1', 10);
    updateReviewStatusAndReload(wordId, currentStatus);
    return false;
  }

  if (e.key === 'I' || e.key === 'i' || e.which === 73) {
    // I : ignore, status=98
    updateReviewStatusAndReload(wordId, 98);
    return false;
  }

  if (e.key === 'W' || e.key === 'w' || e.which === 87) {
    // W : well known, status=99
    updateReviewStatusAndReload(wordId, 99);
    return false;
  }

  if (e.key === 'E' || e.key === 'e' || e.which === 69) {
    // E : edit - navigate to edit page
    window.location.href = '/word/edit-term?wid=' + wordId;
    return false;
  }

  // The next interactions should only be available with displayed solution
  if (!isAnswerOpened()) { return true; }

  if (e.key === 'ArrowUp' || e.which === 38) {
    // up : status+1
    updateReviewStatusAndReload(wordId, undefined, 1);
    return false;
  }

  if (e.key === 'ArrowDown' || e.which === 40) {
    // down : status-1
    updateReviewStatusAndReload(wordId, undefined, -1);
    return false;
  }

  for (let i = 0; i < 5; i++) {
    if (e.which === (49 + i) || e.which === (97 + i) || e.key === String(i + 1)) {
      // 1,.. : status=i
      updateReviewStatusAndReload(wordId, i + 1);
      return false;
    }
  }

  return true;
}
