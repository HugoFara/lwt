/**
 * Test Mode - Event handlers for vocabulary testing
 *
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 */

import { showTestWordPopup } from '@modules/vocabulary/services/word_popup_interface';
import { loadModalFrame, cleanupRightFrames } from '@modules/text/pages/reading/frame_management';
import { getCurrentWordId, getTestSolution, isAnswerOpened, openAnswer } from '@modules/review/stores/test_state';
import { getDictionaryLinks } from '@modules/language/stores/language_config';

/**
 * Helper to safely get an HTML attribute value as a string.
 */
function getAttr(el: HTMLElement, attr: string): string {
  return el.getAttribute(attr) || '';
}

/**
 * Prepare a dialog when the user clicks a word during a test.
 *
 * @returns false
 */
export function handleTestWordClick(this: HTMLElement): boolean {
  const dictLinks = getDictionaryLinks();
  showTestWordPopup(
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
    todoEl.textContent = getTestSolution();
  }
  return false;
}

/**
 * Handle keyboard interaction when testing a word.
 *
 * @param e A keystroke object
 * @returns true if nothing was done, false otherwise
 */
export function handleTestKeydown(e: KeyboardEvent): boolean {
  const wordEl = document.querySelector('.word') as HTMLElement | null;
  const wordId = getCurrentWordId();

  if ((e.key === ' ' || e.key === 'Space' || e.which === 32) && !isAnswerOpened()) {
    // space : show solution
    wordEl?.click();
    cleanupRightFrames();
    loadModalFrame('/word/show?wid=' + wordEl?.getAttribute('data_wid') + '&ann=');
    openAnswer();
    return false;
  }
  if (e.key === 'Escape' || e.which === 27) {
    // esc : skip term, don't change status
    loadModalFrame(
      '/word/set-test-status?wid=' + wordId +
      '&status=' + wordEl?.getAttribute('data_status')
    );
    return false;
  }
  if (e.key === 'I' || e.key === 'i' || e.which === 73) {
    // I : ignore, status=98
    loadModalFrame('/word/set-test-status?wid=' + wordId + '&status=98');
    return false;
  }
  if (e.key === 'W' || e.key === 'w' || e.which === 87) {
    // W : well known, status=99
    loadModalFrame('/word/set-test-status?wid=' + wordId + '&status=99');
    return false;
  }
  if (e.key === 'E' || e.key === 'e' || e.which === 69) {
    // E : edit
    loadModalFrame('/word/edit-term?wid=' + wordId);
    return false;
  }
  // The next interactions should only be available with displayed solution
  if (!isAnswerOpened()) { return true; }
  if (e.key === 'ArrowUp' || e.which === 38) {
    // up : status+1
    loadModalFrame('/word/set-test-status?wid=' + wordId + '&stchange=1');
    return false;
  }
  if (e.key === 'ArrowDown' || e.which === 40) {
    // down : status-1
    loadModalFrame('/word/set-test-status?wid=' + wordId + '&stchange=-1');
    return false;
  }
  for (let i = 0; i < 5; i++) {
    if (e.which === (49 + i) || e.which === (97 + i) || e.key === String(i + 1)) {
      // 1,.. : status=i
      loadModalFrame(
        '/word/set-test-status?wid=' + wordId + '&status=' + (i + 1)
      );
      return false;
    }
  }
  return true;
}
