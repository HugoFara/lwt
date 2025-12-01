/**
 * Test Mode - Event handlers for vocabulary testing
 *
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 */

import { LWT_DATA } from '../core/lwt_state';
import { run_overlib_test } from '../terms/overlib_interface';
import { showRightFrames, cleanupRightFrames } from '../reading/frame_management';

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
export function word_click_event_do_test_test(this: HTMLElement): boolean {
  run_overlib_test(
    LWT_DATA.language.dict_link1, LWT_DATA.language.dict_link2, LWT_DATA.language.translator_link,
    getAttr(this, 'data_wid'),
    getAttr(this, 'data_text'),
    getAttr(this, 'data_trans'),
    getAttr(this, 'data_rom'),
    getAttr(this, 'data_status'),
    getAttr(this, 'data_sent'),
    parseInt(getAttr(this, 'data_todo'), 10),
    null // oldstat - unused legacy parameter
  );
  const todoEl = document.querySelector('.todo');
  if (todoEl) {
    todoEl.textContent = LWT_DATA.test.solution;
  }
  return false;
}

/**
 * Handle keyboard interaction when testing a word.
 *
 * @param e A keystroke object
 * @returns true if nothing was done, false otherwise
 */
export function keydown_event_do_test_test(e: KeyboardEvent): boolean {
  const wordEl = document.querySelector('.word') as HTMLElement | null;

  if ((e.key === ' ' || e.key === 'Space' || e.which === 32) && !LWT_DATA.test.answer_opened) {
    // space : show solution
    wordEl?.click();
    cleanupRightFrames();
    showRightFrames('show_word.php?wid=' + wordEl?.getAttribute('data_wid') + '&ann=');
    LWT_DATA.test.answer_opened = true;
    return false;
  }
  if (e.key === 'Escape' || e.which === 27) {
    // esc : skip term, don't change status
    showRightFrames(
      'set_test_status.php?wid=' + LWT_DATA.word.id +
      '&status=' + wordEl?.getAttribute('data_status')
    );
    return false;
  }
  if (e.key === 'I' || e.key === 'i' || e.which === 73) {
    // I : ignore, status=98
    showRightFrames('set_test_status.php?wid=' + LWT_DATA.word.id + '&status=98');
    return false;
  }
  if (e.key === 'W' || e.key === 'w' || e.which === 87) {
    // W : well known, status=99
    showRightFrames('set_test_status.php?wid=' + LWT_DATA.word.id + '&status=99');
    return false;
  }
  if (e.key === 'E' || e.key === 'e' || e.which === 69) {
    // E : edit
    showRightFrames('/word/edit-term?wid=' + LWT_DATA.word.id);
    return false;
  }
  // The next interactions should only be available with displayed solution
  if (!LWT_DATA.test.answer_opened) { return true; }
  if (e.key === 'ArrowUp' || e.which === 38) {
    // up : status+1
    showRightFrames('set_test_status.php?wid=' + LWT_DATA.word.id + '&stchange=1');
    return false;
  }
  if (e.key === 'ArrowDown' || e.which === 40) {
    // down : status-1
    showRightFrames('set_test_status.php?wid=' + LWT_DATA.word.id + '&stchange=-1');
    return false;
  }
  for (let i = 0; i < 5; i++) {
    if (e.which === (49 + i) || e.which === (97 + i) || e.key === String(i + 1)) {
      // 1,.. : status=i
      showRightFrames(
        'set_test_status.php?wid=' + LWT_DATA.word.id + '&status=' + (i + 1)
      );
      return false;
    }
  }
  return true;
}
