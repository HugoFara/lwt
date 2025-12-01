/**
 * UI Utilities - DOM manipulation, tooltips, and form wrapping
 *
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 */

import $ from 'jquery';
import { LWT_DATA } from './lwt_state';
import { check } from '../forms/form_validation';
import { changeImprAnnText, changeImprAnnRadio, do_ajax_show_similar_terms } from '../terms/term_operations';
import { readRawTextAloud } from './user_interactions';
import { do_ajax_save_setting } from './ajax_utilities';
import { initInlineEdit } from '../ui/inline_edit';
import { initTermTags, initTextTags } from '../ui/tagify_tags';
import { fetchTermTags, fetchTextTags } from './app_data';

// Extend jQuery with custom methods
declare global {
  interface JQuery {
    serializeObject(): Record<string, unknown>;
  }
}



/**
 * Helper to safely get an HTML attribute value as a string.
 */
function getAttr($el: JQuery, attr: string): string {
  const val = $el.attr(attr);
  return typeof val === 'string' ? val : '';
}

/**
 * Initialize native resizable behavior for the right frames panel.
 * Replaces jQuery UI resizable with a custom implementation.
 * Creates a drag handle on the west (left) side of #frames-r.
 */
function initFrameResizable(): void {
  const framesR = document.getElementById('frames-r');
  const framesL = document.getElementById('frames-l');
  if (!framesR || !framesL) return;

  // Create the resize handle
  const handle = document.createElement('div');
  handle.className = 'resize-handle-w';
  handle.style.cssText = `
    position: absolute;
    left: 0;
    top: 0;
    width: 8px;
    height: 100%;
    cursor: ew-resize;
    background: transparent;
    z-index: 100;
  `;
  framesR.style.position = 'fixed';
  framesR.appendChild(handle);

  let isResizing = false;
  let startX = 0;
  let startLeft = 0;

  const onMouseDown = (e: MouseEvent): void => {
    isResizing = true;
    startX = e.clientX;
    startLeft = framesR.offsetLeft;
    document.body.style.cursor = 'ew-resize';
    document.body.style.userSelect = 'none';
    e.preventDefault();
  };

  const onMouseMove = (e: MouseEvent): void => {
    if (!isResizing) return;
    const deltaX = e.clientX - startX;
    const newLeft = startLeft + deltaX;
    // Apply position change during drag
    framesR.style.left = newLeft + 'px';
    framesR.style.width = `calc(100% - ${newLeft}px)`;
    framesL.style.width = (newLeft - 20) + 'px';
  };

  const onMouseUp = (): void => {
    if (!isResizing) return;
    isResizing = false;
    document.body.style.cursor = '';
    document.body.style.userSelect = '';
    // Save the setting when resize stops
    const leftWidth = framesL.offsetWidth;
    const windowWidth = window.innerWidth;
    const percent = Math.round((leftWidth / windowWidth) * 100);
    do_ajax_save_setting('set-text-l-framewidth-percent', String(percent));
  };

  handle.addEventListener('mousedown', onMouseDown);
  document.addEventListener('mousemove', onMouseMove);
  document.addEventListener('mouseup', onMouseUp);
}

/**
 * Enable or disable the mark action button based on checked items.
 * Enables the button if at least one checkbox with class 'markcheck' is checked.
 */
export function markClick(): void {
  if ($('input.markcheck:checked').length > 0) {
    $('#markaction').removeAttr('disabled');
  } else {
    $('#markaction').attr('disabled', 'disabled');
  }
}

/**
 * Show a confirmation dialog for delete operations.
 *
 * @returns true if user confirmed deletion, false otherwise
 */
export function confirmDelete(): boolean {
  return confirm('CONFIRM\n\nAre you sure you want to delete?');
}

/**
 * Enable/disable words hint.
 * Function called when clicking on "Show All".
 */
export function showAllwordsClick(): void {
  const showAll = $('#showallwords').prop('checked') ? '1' : '0';
  const showLeaning = $('#showlearningtranslations').prop('checked') ? '1' : '0';
  const text = $('#thetextid').text();
  // Timeout necessary because the button is clicked on the left (would hide frames)
  setTimeout(function () {
    (window as unknown as { showRightFrames: (url: string) => void }).showRightFrames(
      'set_text_mode.php?mode=' + showAll + '&showLearning=' + showLeaning +
      '&text=' + text
    );
  }, 500);
  setTimeout(function () { window.location.reload(); }, 4000);
}

/**
 * Slide up animation using CSS transitions.
 * Hides an element by animating its height to 0.
 *
 * @param element The element to animate
 * @param duration Animation duration in milliseconds (default 400)
 * @param callback Optional callback when animation completes
 */
function slideUp(element: HTMLElement, duration = 400, callback?: () => void): void {
  // Set initial height explicitly for transition
  element.style.height = element.offsetHeight + 'px';
  element.style.overflow = 'hidden';
  element.style.transition = `height ${duration}ms ease-out, padding ${duration}ms ease-out, margin ${duration}ms ease-out`;

  // Force reflow to ensure initial height is applied
  element.offsetHeight;

  // Animate to 0
  element.style.height = '0';
  element.style.paddingTop = '0';
  element.style.paddingBottom = '0';
  element.style.marginTop = '0';
  element.style.marginBottom = '0';

  // Clean up after animation
  setTimeout(() => {
    element.style.display = 'none';
    // Reset inline styles
    element.style.height = '';
    element.style.overflow = '';
    element.style.transition = '';
    element.style.paddingTop = '';
    element.style.paddingBottom = '';
    element.style.marginTop = '';
    element.style.marginBottom = '';
    if (callback) callback();
  }, duration);
}

/**
 * Hide the 'nodata' message after 3 seconds.
 * Used to automatically dismiss status messages.
 */
export function noShowAfter3Secs(): void {
  const element = document.getElementById('hide3');
  if (element) {
    slideUp(element);
  }
}

/**
 * Auto-dismiss messages with the 'hide_message' class.
 * Messages fade out after 2.5 seconds with a 1 second animation.
 */
export function initHideMessages(): void {
  const elements = document.querySelectorAll<HTMLElement>('.hide_message');
  elements.forEach(element => {
    setTimeout(() => {
      slideUp(element, 1000);
    }, 2500);
  });
}

/**
 * Set the focus on an element with the "focus" class.
 */
export function setTheFocus(): void {
  $('.setfocus')
    .trigger('focus')
    .trigger('select');
}

// Present data in a handy way, for instance in a form
$.fn.serializeObject = function (this: JQuery): Record<string, unknown> {
  const o: Record<string, unknown> = {};
  const a = this.serializeArray();
  $.each(a, function () {
    if (o[this.name] !== undefined) {
      if (!Array.isArray(o[this.name])) {
        o[this.name] = [o[this.name]];
      }
      (o[this.name] as unknown[]).push(this.value || '');
    } else {
      o[this.name] = this.value || '';
    }
  });
  return o;
};

/**
 * Wrap the radio buttons into stylised elements.
 */
export function wrapRadioButtons(): void {
  $(
    ':input,.wrap_checkbox span,.wrap_radio span,a:not([name^=rec]),select,' +
    '#mediaselect span.click,#forwbutt,#backbutt'
  ).each(function (i) { $(this).attr('tabindex', i + 1); });
  $('.wrap_radio span').on('keydown', function (e) {
    if (e.keyCode === 32) {
      $(this).parent().parent().find('input[type=radio]').trigger('click');
      return false;
    }
  });
}

/**
 * Do a lot of different DOM manipulations
 */
export function prepareMainAreas(): void {
  // Initialize inline editing for editable areas
  initInlineEdit('.edit_area', {
    url: 'inline_edit.php',
    tooltip: 'Click to edit...',
    submitText: 'Save',
    cancelText: 'Cancel',
    rows: 3,
    cols: 35,
    indicator: '<img src="/assets/icons/indicator.gif" alt="Saving...">'
  });
  $('select').wrap("<label class='wrap_select'></label>");
  $('form').attr('autocomplete', 'off');
  $('input[type="file"]').each(function () {
    if (!$(this).is(':visible')) {
      $(this).before('<button class="button-file">Choose File</button>')
        .after('<span style="position:relative" class="fakefile"></span>')
        .on('change', function () {
          let txt = (this as HTMLInputElement).value.replace('C:\\fakepath\\', '');
          if (txt.length > 85) txt = txt.replace(/.*(.{80})$/, ' ... $1');
          $(this).next().text(txt);
        })
        .on('onmouseout', function () {
          let txt = (this as HTMLInputElement).value.replace('C:\\fakepath\\', '');
          if (txt.length > 85) txt = txt.replace(/.*(.{80})$/, ' ... $1');
          $(this).next().text(txt);
        });
    }
  });
  let cbIndex = 1;
  $('input[type="checkbox"]').each(function () {
    if (typeof $(this).attr('id') === 'undefined') {
      $(this).attr('id', 'cb_' + cbIndex++);
    }
    $(this).after(
      '<label class="wrap_checkbox" for="' + $(this).attr('id') +
      '"><span></span></label>'
    );
  });
  $('span[class*="tts_"]').on('click', function () {
    const classAttr = getAttr($(this), 'class');
    const lg = classAttr.replace(/.*tts_([a-zA-Z-]+).*/, '$1');
    const txt = $(this).text();
    readRawTextAloud(txt, lg);
  });
  $(document).on('mouseup', function () {
    $('button,input[type=button],.wrap_radio span,.wrap_checkbox span')
      .trigger('blur');
  });
  $('.wrap_checkbox span').on('keydown', function (e) {
    if (e.keyCode === 32) {
      $(this).parent().parent().find('input[type=checkbox]').trigger('click');
      return false;
    }
  });
  let rbIndex = 1;
  $('input[type="radio"]').each(function () {
    if (typeof $(this).attr('id') === 'undefined') {
      $(this).attr('id', 'rb_' + rbIndex++);
    }
    $(this).after(
      '<label class="wrap_radio" for="' + $(this).attr('id') +
      '"><span></span></label>'
    );
  });
  $('.button-file').on('click', function () {
    $(this).next('input[type="file"]').trigger('click');
    return false;
  });
  $('input.impr-ann-text').on('change', changeImprAnnText);
  $('input.impr-ann-radio').on('change', changeImprAnnRadio);
  $('form.validate').on('submit', check);
  $('input.markcheck').on('click', markClick);
  $('.confirmdelete').on('click', confirmDelete);
  $('textarea.textarea-noreturn').on('keydown', function (e) {
    if (e.keyCode === 13) {
      if (check()) { $('input:submit').last().trigger('click'); }
      return false;
    }
    return true;
  });
  // Resizable from right frames (native implementation)
  initFrameResizable();
  // Initialize Tagify for term and text tags
  // Tags are fetched from API asynchronously
  if ($('#termtags').length > 0) {
    fetchTermTags().then(tags => {
      if (tags.length > 0) {
        initTermTags(tags);
      }
    });
  }
  if ($('#texttags').length > 0) {
    fetchTextTags().then(tags => {
      if (tags.length > 0) {
        initTextTags(tags);
      }
    });
  }
  markClick();
  setTheFocus();
  if (
    $('#simwords').length > 0 && $('#langfield').length > 0 &&
    $('#wordfield').length > 0
  ) {
    $('#wordfield').on('blur', do_ajax_show_similar_terms);
    do_ajax_show_similar_terms();
  }
  window.setTimeout(noShowAfter3Secs, 3000);
  // Auto-dismiss messages with hide_message class
  initHideMessages();
}

$(window).on('load', wrapRadioButtons);

$(document).ready(prepareMainAreas);

