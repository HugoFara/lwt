/**
 * Test Mode - Event handlers for vocabulary testing
 *
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 */

import $ from 'jquery';
import { LWT_DATA } from '../core/lwt_state';

// Declare external functions
declare function run_overlib_test(
  dict1: string, dict2: string, translator: string,
  wid: string, text: string, trans: string, rom: string, status: string, sent: string, todo: string
): void;

declare function showRightFrames(url1?: string, url2?: string): void;
declare function cleanupRightFrames(): void;

/**
 * Helper to safely get an HTML attribute value as a string.
 */
function getAttr($el: JQuery, attr: string): string {
  const val = $el.attr(attr);
  return typeof val === 'string' ? val : '';
}

/**
 * Prepare a dialog when the user clicks a word during a test.
 *
 * @returns false
 */
export function word_click_event_do_test_test(this: HTMLElement): boolean {
  const $this = $(this);
  run_overlib_test(
    LWT_DATA.language.dict_link1, LWT_DATA.language.dict_link2, LWT_DATA.language.translator_link,
    getAttr($this, 'data_wid'),
    getAttr($this, 'data_text'),
    getAttr($this, 'data_trans'),
    getAttr($this, 'data_rom'),
    getAttr($this, 'data_status'),
    getAttr($this, 'data_sent'),
    getAttr($this, 'data_todo')
  );
  $('.todo').text(LWT_DATA.test.solution);
  return false;
}

/**
 * Handle keyboard interaction when testing a word.
 *
 * @param e A keystroke object
 * @returns true if nothing was done, false otherwise
 */
export function keydown_event_do_test_test(e: JQuery.KeyDownEvent): boolean {
  if ((e.key === 'Space' || e.which === 32) && !LWT_DATA.test.answer_opened) {
    // space : show solution
    $('.word').trigger('click');
    cleanupRightFrames();
    showRightFrames('show_word.php?wid=' + $('.word').attr('data_wid') + '&ann=');
    LWT_DATA.test.answer_opened = true;
    return false;
  }
  if (e.key === 'Escape' || e.which === 27) {
    // esc : skip term, don't change status
    showRightFrames(
      'set_test_status.php?wid=' + LWT_DATA.word.id +
      '&status=' + $('.word').attr('data_status')
    );
    return false;
  }
  if (e.key === 'I' || e.which === 73) {
    // I : ignore, status=98
    showRightFrames('set_test_status.php?wid=' + LWT_DATA.word.id + '&status=98');
    return false;
  }
  if (e.key === 'W' || e.which === 87) {
    // W : well known, status=99
    showRightFrames('set_test_status.php?wid=' + LWT_DATA.word.id + '&status=99');
    return false;
  }
  if (e.key === 'E' || e.which === 69) {
    // E : edit
    showRightFrames('edit_tword.php?wid=' + LWT_DATA.word.id);
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
    if (e.which === (49 + i) || e.which === (97 + i)) {
      // 1,.. : status=i
      showRightFrames(
        'set_test_status.php?wid=' + LWT_DATA.word.id + '&status=' + (i + 1)
      );
      return false;
    }
  }
  return true;
}

