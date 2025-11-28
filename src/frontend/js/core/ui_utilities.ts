/**
 * UI Utilities - DOM manipulation, tooltips, and form wrapping
 *
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 */

import $ from 'jquery';
import { LWT_DATA } from './lwt_state';
import { check, containsCharacterOutsideBasicMultilingualPlane } from '../forms/form_validation';
import { changeImprAnnText, changeImprAnnRadio, do_ajax_show_similar_terms } from '../terms/term_operations';
import { readRawTextAloud } from './user_interactions';
import { do_ajax_save_setting } from './ajax_utilities';
import { initInlineEdit } from '../ui/inline_edit';

// Extend jQuery with custom methods
declare global {
  interface JQuery {
    tooltip(options?: TooltipOptions): JQuery;
    resizable(options?: ResizableOptions): JQuery;
    tagit(options?: TagitOptions): JQuery;
    serializeObject(): Record<string, unknown>;
    tooltip_wsty_content(): string;
    tooltip_wsty_init(): JQuery;
  }
}

interface TooltipOptions {
  position?: { my: string; at: string; collision: string };
  items?: string;
  show?: { easing: string };
  content?: () => string;
}

interface ResizableOptions {
  handles?: string;
  stop?: (event: unknown, ui: { position: { left: number } }) => void;
}

interface TagitOptions {
  beforeTagAdded?: (event: unknown, ui: { tag: JQuery }) => boolean;
  availableTags?: Record<string, string>;
  fieldName?: string;
}

declare let TAGS: Record<string, string>;
declare let TEXTTAGS: Record<string, string>;

/**
 * Helper to safely get an HTML attribute value as a string.
 */
function getAttr($el: JQuery, attr: string): string {
  const val = $el.attr(attr);
  return typeof val === 'string' ? val : '';
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
 * Hide the 'nodata' message after 3 seconds.
 * Used to automatically dismiss status messages.
 */
export function noShowAfter3Secs(): void {
  $('#hide3').slideUp();
}

/**
 * Set the focus on an element with the "focus" class.
 */
export function setTheFocus(): void {
  $('.setfocus')
    .trigger('focus')
    .trigger('select');
}

// Extend jQuery with tooltip_wsty_content
$.fn.extend({
  tooltip_wsty_content: function (this: JQuery): string {
    const re = new RegExp('([' + LWT_DATA.language.delimiter + '])(?! )', 'g');
    let title = '';
    const dataText = getAttr($(this), 'data_text');
    if ($(this).hasClass('mwsty')) {
      title = "<p><b style='font-size:120%'>" + dataText + '</b></p>';
    } else {
      title = "<p><b style='font-size:120%'>" + $(this).text() + '</b></p>';
    }
    const roman = getAttr($(this), 'data_rom');
    const transAttr = getAttr($(this), 'data_trans');
    let trans = transAttr.replace(re, '$1 ');
    let statname = '';
    const status = parseInt(getAttr($(this), 'data_status') || '0', 10);
    if (status === 0) statname = 'Unknown [?]';
    else if (status < 5) statname = 'Learning [' + status + ']';
    if (status === 5) statname = 'Learned [5]';
    if (status === 98) statname = 'Ignored [Ign]';
    if (status === 99) statname = 'Well Known [WKn]';
    if (roman !== '') {
      title += '<p><b>Roman.</b>: ' + roman + '</p>';
    }
    if (trans !== '' && trans !== '*') {
      const annAttr = getAttr($(this), 'data_ann');
      if (annAttr) {
        const ann = annAttr;
        if (ann !== '' && ann !== '*') {
          const re2 = new RegExp(
            '(.*[' + LWT_DATA.language.delimiter + '][ ]{0,1}|^)(' +
            ann.replace(/[-/\\^$*+?.()|[\]{}]/g, '\\$&') + ')($|[ ]{0,1}[' +
            LWT_DATA.language.delimiter + '].*$| \\[.*$)',
            ''
          );
          trans = trans.replace(re2, '$1<span style="color:red">$2</span>$3');
        }
      }
      title += '<p><b>Transl.</b>: ' + trans + '</p>';
    }
    title += '<p><b>Status</b>: <span class="status' + status + '">' + statname +
    '</span></p>';
    return title;
  }
});

$.fn.extend({
  tooltip_wsty_init: function (this: JQuery): JQuery {
    $(this).tooltip({
      position: { my: 'left top+10', at: 'left bottom', collision: 'flipfit' },
      items: '.hword',
      show: { easing: 'easeOutCirc' },
      content: function () { return $(this).tooltip_wsty_content(); }
    });
    return this;
  }
});

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
  $('.edit_area').editable('inline_edit.php',
    {
      type: 'textarea',
      indicator: '<img src="icn/indicator.gif">',
      tooltip: 'Click to edit...',
      submit: 'Save',
      cancel: 'Cancel',
      rows: 3,
      cols: 35
    }
  );
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
  // Resizable from right frames
  $('#frames-r').resizable({
    handles: 'w',
    stop: function (_event, ui) {
      // Resize left frames
      $('#frames-l').css('width', ui.position.left - 20);
      // Save settings
      do_ajax_save_setting(
        'set-text-l-framewidth-percent',
        String(Math.round($('#frames-l').width()! / $(window).width()! * 100))
      );
    }
  });
  $('#termtags').tagit(
    {
      beforeTagAdded: function (_event, ui) {
        return !containsCharacterOutsideBasicMultilingualPlane(ui.tag.text());
      },
      availableTags: TAGS,
      fieldName: 'TermTags[TagList][]'
    }
  );
  $('#texttags').tagit(
    {
      beforeTagAdded: function (_event, ui) {
        return !containsCharacterOutsideBasicMultilingualPlane(ui.tag.text());
      },
      availableTags: TEXTTAGS,
      fieldName: 'TextTags[TagList][]'
    }
  );
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
}

$(window).on('load', wrapRadioButtons);

$(document).ready(prepareMainAreas);

