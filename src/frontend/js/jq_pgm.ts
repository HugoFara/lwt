/**
 * Interaction between LWT and jQuery
 *
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 */

import { escape_html_chars } from './pgm';
import { readRawTextAloud } from './user_interactions';

/**
 * Helper to safely get an HTML attribute value as a string.
 *
 * @param $el jQuery element to get attribute from
 * @param attr Name of the attribute to retrieve
 * @returns Attribute value as string, or empty string if undefined
 */
function getAttr($el: JQuery, attr: string): string {
  const val = $el.attr(attr);
  return typeof val === 'string' ? val : '';
}

/**
 * Helper to safely get jQuery element value as a string.
 *
 * @param $el jQuery element to get value from
 * @returns Element value as string, or empty string if undefined
 */
function getVal($el: JQuery): string {
  const val = $el.val();
  return typeof val === 'string' ? val : '';
}

// Declare external functions
declare function run_overlib_test(
  dict1: string, dict2: string, translator: string,
  wid: string, text: string, trans: string, rom: string, status: string, sent: string, todo: string
): void;

// Type definitions
interface LwtLanguage {
  id: number;
  dict_link1: string;
  dict_link2: string;
  translator_link: string;
  delimiter: string;
  word_parsing: string;
  rtl: boolean;
  ttsVoiceApi: string;
}

interface LwtText {
  id: number;
  reading_position: number;
  annotations: Record<string, unknown>;
}

interface LwtWord {
  id: number;
}

interface LwtTest {
  solution: string;
  answer_opened: boolean;
}

interface LwtSettings {
  jQuery_tooltip: boolean;
  hts: number;
  word_status_filter: string;
  annotations_mode?: number;
}

export interface LwtDataInterface {
  language: LwtLanguage;
  text: LwtText;
  word: LwtWord;
  test: LwtTest;
  settings: LwtSettings;
}

// Global LWT_DATA object initialization
export const LWT_DATA: LwtDataInterface = {
  /** Language data */
  language: {
    id: 0,
    /** First dictionary URL */
    dict_link1: '',
    /** Second dictionary URL */
    dict_link2: '',
    /** Translator URL */
    translator_link: '',

    delimiter: '',

    /** Word parsing strategy, usually regular expression or 'mecab' */
    word_parsing: '',

    rtl: false,
    /** Third-party voice API */
    ttsVoiceApi: ''
  },
  text: {
    id: 0,
    reading_position: -1,
    annotations: {}
  },
  word: {
    id: 0
  },
  test: {
    solution: '',
    answer_opened: false
  },
  settings: {
    jQuery_tooltip: false,
    hts: 0,
    word_status_filter: ''
  }
};

// Legacy global variables (deprecated)
/** Word ID, deprecated Since 2.10.0, use LWT_DATA.word.id instead */
export let WID = 0;
/** Text ID (int), deprecated Since 2.10.0, use LWT_DATA.text.id */
export let TID = 0;
/** First dictionary URL, deprecated in 2.10.0 use LWT_DATA.language.dict_link1 */
export let WBLINK1 = '';
/** Second dictionary URL, deprecated in 2.10.0 use LWT_DATA.language.dict_link2 */
export let WBLINK2 = '';
/** Translator URL, deprecated in 2.10.0 use LWT_DATA.language.translator_link */
export let WBLINK3 = '';
/** Right-to-left indicator, deprecated in 2.10.0 use LWT_DATA.language.rtl */
export let RTL = 0;

// Word counts globals
declare let WORDCOUNTS: {
  expr: Record<string, number>;
  expru: Record<string, number>;
  total: Record<string, number>;
  totalu: Record<string, number>;
  stat: Record<string, Record<string, number>>;
  statu: Record<string, Record<string, number>>;
};
declare let SUW: number;
declare let SHOWUNIQUE: number;
declare let TAGS: Record<string, string>;
declare let TEXTTAGS: Record<string, string>;

// Extend jQuery with custom methods
declare global {
  interface JQuery {
    editable(url: string, options: EditableOptions): JQuery;
    tooltip(options?: TooltipOptions): JQuery;
    resizable(options?: ResizableOptions): JQuery;
    tagit(options?: TagitOptions): JQuery;
    serializeObject(): Record<string, unknown>;
    tooltip_wsty_content(): string;
    tooltip_wsty_init(): JQuery;
    scrollTo(target: unknown, options?: ScrollToOptions): JQuery;
  }

  interface JQueryStatic {
    scrollTo(position: number): void;
  }
}

interface EditableOptions {
  type: string;
  indicator: string;
  tooltip: string;
  submit: string;
  cancel: string;
  rows?: number;
  cols?: number;
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

interface ScrollToOptions {
  axis?: string;
  offset?: number;
}

// Interface for lwtFormCheck
interface LwtFormCheck {
  makeDirty: () => void;
}

declare const lwtFormCheck: LwtFormCheck;

/**
 * Set translation and romanization in a form when possible.
 *
 * Marj the form as edited if something was changed.
 *
 * @param tra Translation
 * @param rom Romanization
 */
export function setTransRoman(tra: string, rom: string): void {
  let form_changed = false;
  if ($('textarea[name="WoTranslation"]').length === 1) {
    $('textarea[name="WoTranslation"]').val(tra);
    form_changed = true;
  }
  if ($('input[name="WoRomanization"]').length === 1) {
    $('input[name="WoRomanization"]').val(rom);
    form_changed = true;
  }
  if (form_changed) { lwtFormCheck.makeDirty(); }
}

/**
 * Return whether characters are outside the multilingual plane.
 *
 * @param s Input string
 * @returns true is some characters are outside the plane
 */
export function containsCharacterOutsideBasicMultilingualPlane(s: string): boolean {
  return /[\uD800-\uDFFF]/.test(s);
}

/**
 * Alert if characters are outside the multilingual plane.
 *
 * @param s Input string
 * @param info Info about the field
 * @returns 1 if characters are outside the plane, 0 otherwise
 */
export function alertFirstCharacterOutsideBasicMultilingualPlane(s: string, info: string): number {
  if (!containsCharacterOutsideBasicMultilingualPlane(s)) {
    return 0;
  }
  const match = /[\uD800-\uDFFF]/.exec(s);
  if (!match) return 0;
  alert(
    'ERROR\n\nText "' + info + '" contains invalid character(s) ' +
    '(in the Unicode Supplementary Multilingual Planes, > U+FFFF) like emojis ' +
    'or very rare characters.\n\nFirst invalid character: "' +
    s.substring(match.index, match.index + 2) + '" at position ' +
    (match.index + 1) + '.\n\n' +
    'More info: https://en.wikipedia.org/wiki/Plane_(Unicode)\n\n' +
    'Please remove this/these character(s) and try again.'
  );
  return 1;
}

/**
 * Return the memory size of an UTF8 string.
 *
 * @param s String to evaluate
 * @returns Size in bytes
 */
export function getUTF8Length(s: string): number {
  return (new Blob([String(s)])).size;
}

/**
 * Force the user scrolling to an anchor.
 *
 * @param aid Anchor ID
 */
export function scrollToAnchor(aid: string): void {
  document.location.href = '#' + aid;
}

/**
 * Set an existing translation as annotation for a term.
 *
 * @param textid Text ID
 * @param elem_name Name of the element of which to change annotation (e. g.: "rg1")
 * @param form_data All the data from the form (e. g. {"rg0": "foo", "rg1": "bar"})
 */
export function do_ajax_save_impr_text(textid: number, elem_name: string, form_data: string): void {
  const idwait = '#wait' + elem_name.substring(2);
  $(idwait).html('<img src="icn/waiting2.gif" />');
  // elem: "rg2", form_data: {"rg2": "translation"}
  $.post(
    'api.php/v1/texts/' + textid + '/annotation',
    {
      elem: elem_name,
      data: form_data
    },
    function (data: { error?: string }) {
      $(idwait).html('<img src="icn/empty.gif" />');
      if ('error' in data) {
        alert(
          'Saving your changes failed, please reload the page and try again! ' +
          'Error message: "' + data.error + '".'
        );
      }
    },
    'json'
  );
}

/**
 * Change the annotation for a term by setting its text.
 */
export function changeImprAnnText(this: HTMLElement): void {
  $(this).prev('input:radio').attr('checked', 'checked');
  const textid = parseInt(getAttr($('#editimprtextdata'), 'data_id') || '0', 10);
  const elem_name = getAttr($(this), 'name');
  const form_data = JSON.stringify($('form').serializeObject());
  do_ajax_save_impr_text(textid, elem_name, form_data);
}

/**
 * Change the annotation for a term by setting its text.
 */
export function changeImprAnnRadio(this: HTMLElement): void {
  const textid = parseInt(getAttr($('#editimprtextdata'), 'data_id') || '0', 10);
  const elem_name = getAttr($(this), 'name');
  const form_data = JSON.stringify($('form').serializeObject());
  do_ajax_save_impr_text(textid, elem_name, form_data);
}

/**
 * Update a word translation.
 *
 * @param wordid Word ID
 * @param txid   Text HTML ID or unique HTML selector
 */
export function updateTermTranslation(wordid: number, txid: string): void {
  const translation = ($(txid).val() as string).trim();
  const pagepos = $(document).scrollTop() || 0;
  if (translation === '' || translation === '*') {
    alert('Text Field is empty or = \'*\'!');
    return;
  }
  const request = {
    translation
  };
  const failure = 'Updating translation of term failed!' +
  'Please reload page and try again.';
  $.post(
    'api.php/v1/terms/' + wordid + '/translations',
    request,
    function (d: { error?: string; update?: string } | '') {
      if (d === '') {
        alert(failure);
        return;
      }
      if (typeof d === 'object' && 'error' in d) {
        alert(failure + '\n' + d.error);
        return;
      }
      if (typeof d === 'object' && d.update) {
        do_ajax_edit_impr_text(pagepos, d.update, wordid);
      }
    },
    'json'
  );
}

/**
 * Add (new word) a word translation.
 *
 * @param txid   Text HTML ID or unique HTML selector
 * @param word   Word text
 * @param lang   Language ID
 */
export function addTermTranslation(txid: string, word: string, lang: number): void {
  const translation = ($(txid).val() as string).trim();
  const pagepos = $(document).scrollTop() || 0;
  if (translation === '' || translation === '*') {
    alert('Text Field is empty or = \'*\'!');
    return;
  }
  const failure = 'Adding translation to term failed!' +
  'Please reload page and try again.';
  $.post(
    'api.php/v1/terms/new',
    {
      translation,
      term_text: word,
      lg_id: lang
    },
    function (d: { error?: string; add?: string; term_id?: number } | '') {
      if (d === '') {
        alert(failure);
        return;
      }
      if (typeof d === 'object' && 'error' in d) {
        alert(failure + '\n' + d.error);
        return;
      }
      if (typeof d === 'object' && d.add && d.term_id !== undefined) {
        do_ajax_edit_impr_text(pagepos, d.add, d.term_id);
      }
    },
    'json'
  );
}

/**
 * Set a new status for a word in the test table.
 *
 * @param wordid Word ID
 * @param up     true if status should be increased, false otherwise
 */
export function changeTableTestStatus(wordid: string, up: boolean): void {
  const status_change = up ? 'up' : 'down';
  const wid = parseInt(wordid, 10);
  $.post(
    'api.php/v1/terms/' + wid + '/status/' + status_change,
    {},
    function (data: { increment?: string; error?: string } | '') {
      if (data === '' || (typeof data === 'object' && 'error' in data)) {
        return;
      }
      if (typeof data === 'object' && data.increment) {
        $('#STAT' + wordid).html(data.increment);
      }
    },
    'json'
  );
}

/**
 * Check if there is no problem with the text.
 *
 * @returns true if all checks were successfull
 */
export function check(): boolean {
  let count = 0;
  $('.notempty').each(function () {
    if (($(this).val() as string).trim() === '') count++;
  });
  if (count > 0) {
    alert('ERROR\n\n' + count + ' field(s) - marked with * - must not be empty!');
    return false;
  }
  count = 0;
  $('input.checkurl').each(function () {
    if (($(this).val() as string).trim().length > 0) {
      const val = ($(this).val() as string).trim();
      if ((val.indexOf('http://') !== 0) &&
      (val.indexOf('https://') !== 0) &&
      (val.indexOf('#') !== 0)) {
        alert(
          'ERROR\n\nField "' + $(this).attr('data_info') +
          '" must start with "http://" or "https://" if not empty.'
        );
        count++;
      }
    }
  });
  // Note: as of LWT 2.9.0, no field with "checkregexp" property is found in the code base
  $('input.checkregexp').each(function () {
    const regexp = ($(this).val() as string).trim();
    if (regexp.length > 0) {
      $.ajax({
        type: 'POST',
        url: 'inc/ajax.php',
        data: {
          action: '',
          action_type: 'check_regexp',
          regex: regexp
        },
        async: false
      }).always(function (data: string) {
        if (data !== '') {
          alert(data);
          count++;
        }
      });
    }
  });
  // To enable limits of custom feed texts/articl.
  // change the following «input[class*="max_int_"]» into «input[class*="maxint_"]»
  $('input[class*="max_int_"]').each(function () {
    const classAttr = getAttr($(this), 'class');
    const maxvalue = parseInt(classAttr.replace(/.*maxint_([0-9]+).*/, '$1'), 10);
    if (($(this).val() as string).trim().length > 0) {
      if (parseInt($(this).val() as string, 10) > maxvalue) {
        alert(
          'ERROR\n\n Max Value of Field "' + $(this).attr('data_info') +
          '" is ' + maxvalue
        );
        count++;
      }
    }
  });
  // Check that the Google Translate field is of good type
  $('input.checkdicturl').each(function () {
    const translate_input = ($(this).val() as string).trim();
    if (translate_input.length > 0) {
      let refinned = translate_input;
      if (translate_input.startsWith('*')) {
        refinned = translate_input.substring(1);
      }
      if (!/^https?:\/\//.test(refinned)) {
        refinned = 'http://' + refinned;
      }
      try {
        new URL(refinned);
      } catch (err) {
        if (err instanceof TypeError) {
          alert(
            'ERROR\n\nField "' + $(this).attr('data_info') +
            '" should be an URL if not empty.'
          );
          count++;
        }
      }
    }
  });
  $('input.posintnumber').each(function () {
    if (($(this).val() as string).trim().length > 0) {
      const val = ($(this).val() as string).trim();
      if (!(isInt(val) && (parseInt(val, 10) > 0))) {
        alert(
          'ERROR\n\nField "' + $(this).attr('data_info') +
          '" must be an integer number > 0.'
        );
        count++;
      }
    }
  });
  $('input.zeroposintnumber').each(function () {
    if (($(this).val() as string).trim().length > 0) {
      const val = ($(this).val() as string).trim();
      if (!(isInt(val) && (parseInt(val, 10) >= 0))) {
        alert(
          'ERROR\n\nField "' + $(this).attr('data_info') +
          '" must be an integer number >= 0.'
        );
        count++;
      }
    }
  });
  $('input.checkoutsidebmp').each(function () {
    const val = getVal($(this));
    if (val.trim().length > 0) {
      if (containsCharacterOutsideBasicMultilingualPlane(val)) {
        count += alertFirstCharacterOutsideBasicMultilingualPlane(
          val, getAttr($(this), 'data_info')
        );
      }
    }
  });
  $('textarea.checklength').each(function () {
    const $el = $(this);
    const maxLength = parseInt(getAttr($el, 'data_maxlength') || '0', 10);
    const val = getVal($el);
    if (val.trim().length > maxLength) {
      alert(
        'ERROR\n\nText is too long in field "' + getAttr($el, 'data_info') +
        '", please make it shorter! (Maximum length: ' +
        getAttr($el, 'data_maxlength') + ' char.)'
      );
      count++;
    }
  });
  $('textarea.checkoutsidebmp').each(function () {
    const val = getVal($(this));
    if (containsCharacterOutsideBasicMultilingualPlane(val)) {
      count += alertFirstCharacterOutsideBasicMultilingualPlane(
        val, getAttr($(this), 'data_info')
      );
    }
  });
  $('textarea.checkbytes').each(function () {
    const $el = $(this);
    const maxLength = parseInt(getAttr($el, 'data_maxlength') || '0', 10);
    const val = getVal($el);
    if (getUTF8Length(val.trim()) > maxLength) {
      alert(
        'ERROR\n\nText is too long in field "' + getAttr($el, 'data_info') +
        '", please make it shorter! (Maximum length: ' +
        getAttr($el, 'data_maxlength') + ' bytes.)'
      );
      count++;
    }
  });
  $('input.noblanksnocomma').each(function () {
    const val = $(this).val() as string;
    if (val.indexOf(' ') > 0 || val.indexOf(',') > 0) {
      alert(
        'ERROR\n\nNo spaces or commas allowed in field "' +
        $(this).attr('data_info') + '", please remove!'
      );
      count++;
    }
  });
  return (count === 0);
}

/**
 * Check if a string represents a valid integer.
 *
 * @param value String value to check
 * @returns true if the value is a valid integer, false otherwise
 */
export function isInt(value: string): boolean {
  for (let i = 0; i < value.length; i++) {
    if ((value.charAt(i) < '0') || (value.charAt(i) > '9')) {
      return false;
    }
  }
  return true;
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
    showRightFrames(
      'set_text_mode.php?mode=' + showAll + '&showLearning=' + showLeaning +
      '&text=' + text
    );
  }, 500);
  setTimeout(function () { window.location.reload(); }, 4000);
}

/**
 * Handle Enter key press in textareas to trigger form submission.
 *
 * @param event jQuery keyboard event
 * @returns false to prevent default behavior if Enter was pressed and form is valid, true otherwise
 */
export function textareaKeydown(event: JQuery.KeyDownEvent): boolean {
  if (event.keyCode && event.keyCode === 13) {
    if (check()) { $('input:submit').last().trigger('click'); }
    return false;
  } else {
    return true;
  }
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

// Declare showRightFrames function
declare function showRightFrames(url1?: string, url2?: string): void;
declare function cleanupRightFrames(): void;

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

/**
 * Extract position number from an HTML element ID string.
 *
 * @param id_string HTML element ID containing position information (e.g., "ID-3-42")
 * @returns Position number extracted from the ID, or -1 if undefined/invalid
 */
export function get_position_from_id(id_string: string): number {
  if (typeof id_string === 'undefined') return -1;
  const arr = id_string.split('-');
  return parseInt(arr[1], 10) * 10 + 10 - parseInt(arr[2], 10);
}

/**
 * Save a setting to the database.
 *
 * @param k Setting name as a key
 * @param v Setting value
 */
export function do_ajax_save_setting(k: string, v: string): void {
  $.post(
    'api.php/v1/settings',
    {
      key: k,
      value: v
    }
  );
}

/**
 * Assign the display value of a select element to the value element of another input.
 *
 * @param select_elem
 * @param input_elem
 */
export function quick_select_to_input(select_elem: HTMLSelectElement, input_elem: HTMLInputElement): void {
  const val = select_elem.options[select_elem.selectedIndex].value;
  if (val !== '') { input_elem.value = val; }
  select_elem.value = '';
}

/**
 * Return an HTML group of options to add to a select field.
 *
 * @param paths     All paths (files and folders)
 * @param folders   Folders paths, should be a subset of paths
 * @param base_path Base path for LWT to append
 *
 * @returns List of options to append to the select.
 *
 * @since 2.9.1-fork Base path is no longer used
 */
export function select_media_path(
  paths: string[],
  folders: string[],
  base_path: string
): HTMLOptionElement[] {
  const options: HTMLOptionElement[] = [];
  let temp_option = document.createElement('option');
  temp_option.value = '';
  temp_option.text = '[Choose...]';
  options.push(temp_option);
  for (let i = 0; i < paths.length; i++) {
    temp_option = document.createElement('option');
    if (folders.includes(paths[i])) {
      temp_option.setAttribute('disabled', 'disabled');
      temp_option.text = '-- Directory: ' + paths[i] + '--';
    } else {
      temp_option.value = paths[i];
      temp_option.text = paths[i];
    }
    options.push(temp_option);
  }
  return options;
}

interface MediaSelectResponse {
  error?: 'not_a_directory' | 'does_not_exist' | string;
  base_path?: string;
  paths?: string[];
  folders?: string[];
}

/**
 * Process the received data from media selection query
 *
 * @param data Received data as a JSON object
 */
export function media_select_receive_data(data: MediaSelectResponse): void {
  $('#mediaSelectLoadingImg').css('display', 'none');
  if (data.error !== undefined) {
    let msg: string;
    if (data.error === 'not_a_directory') {
      msg = '[Error: "../' + data.base_path + '/media" exists, but it is not a directory.]';
    } else if (data.error === 'does_not_exist') {
      msg = '[Directory "../' + data.base_path + '/media" does not yet exist.]';
    } else {
      msg = '[Unknown error!]';
    }
    $('#mediaSelectErrorMessage').text(msg);
    $('#mediaSelectErrorMessage').css('display', 'inherit');
  } else {
    const options = select_media_path(data.paths || [], data.folders || [], data.base_path || '');
    $('#mediaselect select').empty();
    for (let i = 0; i < options.length; i++) {
      $('#mediaselect select').append(options[i]);
    }
    $('#mediaselect select').css('display', 'inherit');
  }
}

/**
 * Perform an AJAX query to retrieve and display the media files path.
 */
export function do_ajax_update_media_select(): void {
  $('#mediaSelectErrorMessage').css('display', 'none');
  $('#mediaselect select').css('display', 'none');
  $('#mediaSelectLoadingImg').css('display', 'inherit');
  $.getJSON(
    'api.php/v1/media-files',
    {},
    media_select_receive_data
  );
}

/**
 * Prepare am HTML element that formats the sentences
 *
 * @param sentences    A list of sentences to display.
 * @param click_target The selector for the element that should change value on click
 * @returns A formatted group of sentences
 */
export function display_example_sentences(
  sentences: [string, string][],
  click_target: string
): HTMLDivElement {
  let img: HTMLImageElement, clickable: HTMLSpanElement, parentDiv: HTMLDivElement;
  const outElement = document.createElement('div');
  for (let i = 0; i < sentences.length; i++) {
    // Add the checkbox
    img = document.createElement('img');
    img.src = 'icn/tick-button.png';
    img.title = 'Choose';
    // Clickable element
    clickable = document.createElement('span');
    clickable.classList.add('click');
    // Doesn't feel the right way to do it
    clickable.setAttribute(
      'onclick',
      '{' + click_target + ".value = '" + sentences[i][1].replace(/'/g, "\\'") +
      "';lwtFormCheck.makeDirty();}"
    );
    clickable.appendChild(img);
    // Create parent
    parentDiv = document.createElement('div');
    parentDiv.appendChild(clickable);
    parentDiv.innerHTML += '&nbsp; ' + sentences[i][0];
    // Add to the output
    outElement.appendChild(parentDiv);
  }
  return outElement;
}

/**
 * Prepare am HTML element that formats the sentences
 *
 * @param sentences    A list of sentences to display.
 * @param ctl The selector for the element that should change value on click
 */
export function change_example_sentences_zone(sentences: [string, string][], ctl: string): void {
  $('#exsent-waiting').css('display', 'none');
  $('#exsent-sentences').css('display', 'inherit');
  const new_element = display_example_sentences(sentences, ctl);
  $('#exsent-sentences').append(new_element);
}

/**
 * Get and display the sentences containing specific word.
 *
 * @param lang Language ID
 * @param word Term text (the looked for term)
 * @param ctl  Selector for the element to edit on click
 * @param woid Term id (word or multi-word)
 */
export function do_ajax_show_sentences(lang: number, word: string, ctl: string, woid: number | string): void {
  $('#exsent-interactable').css('display', 'none');
  $('#exsent-waiting').css('display', 'inherit');

  if (isInt(String(woid)) && woid !== -1) {
    $.getJSON(
      'api.php/v1/sentences-with-term/' + woid,
      {
        lg_id: lang,
        word_lc: word
      },
      (data: [string, string][]) => change_example_sentences_zone(data, ctl)
    );
  } else {
    const query: { lg_id: number; word_lc: string; advanced_search?: boolean } = {
      lg_id: lang,
      word_lc: word
    };
    if (parseInt(String(woid), 10) === -1) {
      query.advanced_search = true;
    }
    $.getJSON(
      'api.php/v1/sentences-with-term',
      query,
      (data: [string, string][]) => change_example_sentences_zone(data, ctl)
    );
  }
}

/**
 * Send an AJAX request to get similar terms to a term.
 *
 * @param lg_id Language ID
 * @param word_text Text to match
 * @returns Request used
 */
export function do_ajax_req_sim_terms(lg_id: number, word_text: string): JQuery.jqXHR<{ similar_terms: string }> {
  return $.getJSON(
    'api.php/v1/similar-terms',
    {
      lg_id: lg_id,
      term: word_text
    }
  );
}

/**
 * Display the terms similar to a specific term with AJAX.
 */
export function do_ajax_show_similar_terms(): void {
  $('#simwords').html('<img src="icn/waiting2.gif" />');
  do_ajax_req_sim_terms(
    parseInt($('#langfield').val() as string, 10), $('#wordfield').val() as string
  )
    .done(
      function (data: { similar_terms: string }) {
        $('#simwords').html(data.similar_terms);
      }
    ).fail(
      function (data: unknown) {
        console.log(data);
      }
    );
}

/**
 * Update WORDCOUNTS in with an AJAX request.
 */
export function do_ajax_word_counts(): void {
  const t = $('.markcheck').map(function () {
    return $(this).val();
  })
    .get().join(',');
  $.getJSON(
    'api.php/v1/texts/statistics',
    {
      texts_id: t
    },
    function (data: typeof WORDCOUNTS) {
      (window as unknown as { WORDCOUNTS: typeof WORDCOUNTS }).WORDCOUNTS = data;
      word_count_click();
      $('.barchart').removeClass('hide');
    }
  );
}

/**
 * Set a unique item in barchart to reflect how many words are known.
 */
export function set_barchart_item(this: HTMLElement): void {
  const idAttr = getAttr($(this).find('span').first(), 'id');
  const id = idAttr.split('_')[2] || '';
  /** @var v Number of terms in the text */
  let v: number;
  if (SUW & 16) {
    v = parseInt(String(WORDCOUNTS.expru[id] || 0), 10) +
    parseInt(String(WORDCOUNTS.totalu[id]), 10);
  } else {
    v = parseInt(String(WORDCOUNTS.expr[id] || 0), 10) +
    parseInt(String(WORDCOUNTS.total[id]), 10);
  }
  $(this).children('li').each(function () {
    /** Word count in the category */
    let cat_word_count = parseInt($(this).children('span').text(), 10);
    // Avoid to put 0 in logarithm
    cat_word_count += 1;
    v += 1;
    const h = 25 - Math.log(cat_word_count) / Math.log(v) * 25;
    $(this).css('border-top-width', h + 'px');
  });
}

/**
 * Set the number of words known in a text (in edit_texts.php main page).
 */
export function set_word_counts(): void {
  $.each(WORDCOUNTS.totalu, function (key: string, value: number) {
    let knownu = 0, known = 0, todo: number, stat0: number;
    const expr = WORDCOUNTS.expru[key] ? parseInt(String((SUW & 2) ? WORDCOUNTS.expru[key] : WORDCOUNTS.expr[key]), 10) : 0;
    if (!WORDCOUNTS.stat[key]) {
      WORDCOUNTS.statu[key] = WORDCOUNTS.stat[key] = {};
    }
    $('#total_' + key).html(String((SUW & 1) ? value : WORDCOUNTS.total[key]));
    $.each(WORDCOUNTS.statu[key], function (k: string, v: number) {
      if (SUW & 8) { $('#stat_' + k + '_' + key).html(String(v)); }
      knownu += parseInt(String(v), 10);
    });
    $.each(WORDCOUNTS.stat[key], function (k: string, v: number) {
      if (!(SUW & 8)) { $('#stat_' + k + '_' + key).html(String(v)); }
      known += parseInt(String(v), 10);
    });
    $('#saved_' + key).html(known ? (String((SUW & 2 ? knownu : known) - expr) + '+' + expr) : '0');
    if (SUW & 4) {
      todo = parseInt(String(value), 10) + parseInt(String(WORDCOUNTS.expru[key] || 0), 10) - parseInt(String(knownu), 10);
    } else {
      todo = parseInt(String(WORDCOUNTS.total[key]), 10) + parseInt(String(WORDCOUNTS.expr[key] || 0), 10) - parseInt(String(known), 10);
    }
    $('#todo_' + key).html(String(todo));

    // added unknown percent
    let unknowncount: number, unknownpercent: number;
    if (SUW & 8) {
      unknowncount = parseInt(String(value), 10) + parseInt(String(WORDCOUNTS.expru[key] || 0), 10) - parseInt(String(knownu), 10);
      unknownpercent = Math.round(unknowncount * 10000 / (knownu + unknowncount)) / 100;
    } else {
      unknowncount = parseInt(String(WORDCOUNTS.total[key]), 10) + parseInt(String(WORDCOUNTS.expr[key] || 0), 10) - parseInt(String(known), 10);
      unknownpercent = Math.round(unknowncount * 10000 / (known + unknowncount)) / 100;
    }
    $('#unknownpercent_' + key).html(unknownpercent === 0 ? '0' : unknownpercent.toFixed(2));
    // end here

    if (SUW & 16) {
      stat0 = parseInt(String(value), 10) + parseInt(String(WORDCOUNTS.expru[key] || 0), 10) - parseInt(String(knownu), 10);
    } else {
      stat0 = parseInt(String(WORDCOUNTS.total[key]), 10) + parseInt(String(WORDCOUNTS.expr[key] || 0), 10) - parseInt(String(known), 10);
    }
    $('#stat_0_' + key).html(String(stat0));
  });
  $('.barchart').each(set_barchart_item);
}

/**
 * Handle the click event to switch between total and
 * unique words count in edit_texts.php.
 */
export function word_count_click(): void {
  $('.wc_cont').children().each(function () {
    if (parseInt(getAttr($(this), 'data_wo_cnt') || '0', 10) === 1) {
      $(this).html('u');
    } else {
      $(this).html('t');
    }
    (window as unknown as { SUW: number }).SUW = (parseInt(getAttr($('#chart'), 'data_wo_cnt') || '0', 10) << 4) +
    (parseInt(getAttr($('#unknownpercent'), 'data_wo_cnt') || '0', 10) << 3) +
    (parseInt(getAttr($('#unknown'), 'data_wo_cnt') || '0', 10) << 2) +
    (parseInt(getAttr($('#saved'), 'data_wo_cnt') || '0', 10) << 1) +
    (parseInt(getAttr($('#total'), 'data_wo_cnt') || '0', 10));
    set_word_counts();
  });
}

interface TransData {
  wid: number | null;
  trans: string;
  ann_index: string;
  term_ord: string;
  term_lc: string;
  lang_id: number;
  translations: string[];
}

/**
 * Create a radio button with a candidate choice for a term annotation.
 *
 * @param curr_trans Current anotation (translation) set for the term
 * @param trans_data All the useful data for the term
 * @returns An HTML-formatted option
 */
export function translation_radio(curr_trans: string, trans_data: TransData): string {
  if (trans_data.wid === null) {
    return '';
  }
  const trim_trans = curr_trans.trim();
  if (trim_trans === '*' || trim_trans === '') {
    return '';
  }
  const set = trim_trans === trans_data.trans;
  const option = `<span class="nowrap">
    <input class="impr-ann-radio" ` +
      (set ? 'checked="checked" ' : '') + 'type="radio" name="rg' +
      trans_data.ann_index + '" value="' + escape_html_chars(trim_trans) + `" />
          &nbsp; ` + escape_html_chars(trim_trans) + `
  </span>
  <br />`;
  return option;
}

/**
 * When a term translation is edited, recreate it's annotations.
 *
 * @param trans_data Useful data for this term
 * @param text_id    Text ID
 */
export function edit_term_ann_translations(trans_data: TransData, text_id: number): void {
  const widset = trans_data.wid !== null;
  // First create a link to edit the word in a new window
  let edit_word_link: string;
  if (widset) {
    const req_arg = $.param({
      fromAnn: '$(document).scrollTop()',
      wid: trans_data.wid,
      ord: trans_data.term_ord,
      tid: text_id
    });
    edit_word_link = `<a name="rec${trans_data.ann_index}"></a>
    <span class="click"
    onclick="oewin('edit_word.php?` + escape_html_chars(req_arg) + `');">
          <img src="icn/sticky-note--pencil.png" title="Edit Term" alt="Edit Term" />
      </span>`;
  } else {
    edit_word_link = '&nbsp;';
  }
  $(`#editlink${trans_data.ann_index}`).html(edit_word_link);
  // Now edit translations (if necessary)
  let translations_list = '';
  trans_data.translations.forEach(
    function (candidate_trans: string) {
      translations_list += translation_radio(candidate_trans, trans_data);
    }
  );

  const select_last = trans_data.translations.length === 0;
  const curr_trans = trans_data.trans || '';
  // Empty radio button and text field after the list of translations
  translations_list += `<span class="nowrap">
  <input class="impr-ann-radio" type="radio" name="rg${trans_data.ann_index}" ` +
  (select_last ? 'checked="checked" ' : '') + `value="" />
  &nbsp;
  <input class="impr-ann-text" type="text" name="tx${trans_data.ann_index}` +
    `" id="tx${trans_data.ann_index}" value="` +
    (select_last ? escape_html_chars(curr_trans) : '') +
  `" maxlength="50" size="40" />
   &nbsp;
  <img class="click" src="icn/eraser.png" title="Erase Text Field"
  alt="Erase Text Field"
  onclick="$('#tx${trans_data.ann_index}').val('').trigger('change');" />
    &nbsp;
  <img class="click" src="icn/star.png" title="* (Set to Term)"
  alt="* (Set to Term)"
  onclick="$('#tx${trans_data.ann_index}').val('*').trigger('change');" />
  &nbsp;`;
  // Add the "plus button" to add a translation
  if (widset) {
    translations_list +=
    `<img class="click" src="icn/plus-button.png"
    title="Save another translation to existent term"
    alt="Save another translation to existent term"
    onclick="updateTermTranslation(${trans_data.wid}, ` +
      `'#tx${trans_data.ann_index}');" />`;
  } else {
    translations_list +=
    `<img class="click" src="icn/plus-button.png"
    title="Save translation to new term"
    alt="Save translation to new term"
    onclick="addTermTranslation('#tx${trans_data.ann_index}',` +
      `${trans_data.term_lc},${trans_data.lang_id});" />`;
  }
  translations_list += `&nbsp;&nbsp;
  <span id="wait${trans_data.ann_index}">
      <img src="icn/empty.gif" />
  </span>
  </span>`;
  $(`#transsel${trans_data.ann_index}`).html(translations_list);
}

/**
 * Load the possible translations for a word.
 *
 * @param pagepos Position to scroll to
 * @param word    Word in lowercase to get annotations
 * @param term_id Term ID
 *
 * @since 2.9.0 The new parameter $wid is now necessary
 */
export function do_ajax_edit_impr_text(pagepos: number, word: string, term_id: number): void {
  // Special case, on empty word reload the main annotations form
  if (word === '') {
    $('#editimprtextdata').html('<img src="icn/waiting2.gif" />');
    location.reload();
    return;
  }
  // Load the possible translations for a word
  const textid = parseInt(getAttr($('#editimprtextdata'), 'data_id') || '0', 10);
  $.getJSON(
    'api.php/v1/terms/' + term_id + '/translations',
    {
      text_id: textid,
      term_lc: word
    },
    function (data: TransData & { error?: string }) {
      if ('error' in data) {
        alert(data.error);
      } else {
        edit_term_ann_translations(data, textid);
        $.scrollTo(pagepos);
        $('input.impr-ann-text').on('change', changeImprAnnText);
        $('input.impr-ann-radio').on('change', changeImprAnnRadio);
      }
    }
  );
}

/**
 * Show the right frames if found, and can load an URL in those frames
 *
 * @param roUrl Upper-right frame URL to load
 * @param ruUrl Lower-right frame URL to load
 * @returns true if frames were found, false otherwise
 */
export function showRightFramesImpl(roUrl?: string, ruUrl?: string): boolean {
  if (roUrl !== undefined) {
    top!.frames['ro' as unknown as number].location.href = roUrl;
  }
  if (ruUrl !== undefined) {
    top!.frames['ru' as unknown as number].location.href = ruUrl;
  }
  if ($('#frames-r').length) {
    $('#frames-r').animate({ right: '5px' });
    return true;
  }
  return false;
}

/**
 * Hide the right frames if found.
 *
 * @returns true if frames were found, false otherwise
 */
export function hideRightFrames(): boolean {
  if ($('#frames-r').length) {
    $('#frames-r').animate({ right: '-100%' });
    return true;
  }
  return false;
}

/**
 * Hide the right frame and any popups.
 *
 * Called from several places: insert_word_ignore.php,
 * set_word_status.php, delete_word.php, etc.
 */
export function cleanupRightFramesImpl(): void {
  const mytimeout = function () {
    const rf = window.parent.document.getElementById('frames-r');
    rf?.click();
  };
  window.parent.setTimeout(mytimeout, 800);

  window.parent.document.getElementById('frame-l')?.focus();
  const parentCClick = (window.parent as unknown as { cClick: () => void }).cClick;
  window.parent.setTimeout(parentCClick, 100);
}

/**
 * Play the success sound.
 *
 * @returns Promise on the status of sound
 */
export function successSound(): Promise<void> {
  (document.getElementById('success_sound') as HTMLAudioElement)?.pause();
  (document.getElementById('failure_sound') as HTMLAudioElement)?.pause();
  return (document.getElementById('success_sound') as HTMLAudioElement)?.play();
}

/**
 * Play the failure sound.
 *
 * @returns Promise on the status of sound
 */
export function failureSound(): Promise<void> {
  (document.getElementById('success_sound') as HTMLAudioElement)?.pause();
  (document.getElementById('failure_sound') as HTMLAudioElement)?.pause();
  return (document.getElementById('failure_sound') as HTMLAudioElement)?.play();
}

export const lwt = {

  /**
   * Prepare the action so that a click switches between
   * unique word count and total word count.
   */
  prepare_word_count_click: function (): void {
    $('#total,#saved,#unknown,#chart,#unknownpercent')
      .on('click', function (event) {
        $(this).attr('data_wo_cnt', String(parseInt(getAttr($(this), 'data_wo_cnt') || '0', 10) ^ 1));
        word_count_click();
        event.stopImmediatePropagation();
      }).attr('title', 'u: Unique Word Counts\nt: Total  Word  Counts');
    do_ajax_word_counts();
  },

  /**
   * Save the settings about unique/total words count.
   */
  save_text_word_count_settings: function (): void {
    if (SUW === SHOWUNIQUE) {
      return;
    }
    const a = getAttr($('#total'), 'data_wo_cnt') +
      getAttr($('#saved'), 'data_wo_cnt') +
      getAttr($('#unknown'), 'data_wo_cnt') +
      getAttr($('#unknownpercent'), 'data_wo_cnt') +
      getAttr($('#chart'), 'data_wo_cnt');
    do_ajax_save_setting('set-show-text-word-counts', a);
  }
};

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
  $('textarea.textarea-noreturn').on('keydown', textareaKeydown);
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

// Expose globally for backward compatibility with PHP templates
if (typeof window !== 'undefined') {
  const w = window as unknown as Record<string, unknown>;
  w.LWT_DATA = LWT_DATA;
  w.WID = WID;
  w.TID = TID;
  w.WBLINK1 = WBLINK1;
  w.WBLINK2 = WBLINK2;
  w.WBLINK3 = WBLINK3;
  w.RTL = RTL;
  w.setTransRoman = setTransRoman;
  w.containsCharacterOutsideBasicMultilingualPlane = containsCharacterOutsideBasicMultilingualPlane;
  w.alertFirstCharacterOutsideBasicMultilingualPlane = alertFirstCharacterOutsideBasicMultilingualPlane;
  w.getUTF8Length = getUTF8Length;
  w.scrollToAnchor = scrollToAnchor;
  w.do_ajax_save_impr_text = do_ajax_save_impr_text;
  w.changeImprAnnText = changeImprAnnText;
  w.changeImprAnnRadio = changeImprAnnRadio;
  w.updateTermTranslation = updateTermTranslation;
  w.addTermTranslation = addTermTranslation;
  w.changeTableTestStatus = changeTableTestStatus;
  w.check = check;
  w.isInt = isInt;
  w.markClick = markClick;
  w.confirmDelete = confirmDelete;
  w.showAllwordsClick = showAllwordsClick;
  w.textareaKeydown = textareaKeydown;
  w.noShowAfter3Secs = noShowAfter3Secs;
  w.setTheFocus = setTheFocus;
  w.word_click_event_do_test_test = word_click_event_do_test_test;
  w.keydown_event_do_test_test = keydown_event_do_test_test;
  w.get_position_from_id = get_position_from_id;
  w.do_ajax_save_setting = do_ajax_save_setting;
  w.quick_select_to_input = quick_select_to_input;
  w.select_media_path = select_media_path;
  w.media_select_receive_data = media_select_receive_data;
  w.do_ajax_update_media_select = do_ajax_update_media_select;
  w.display_example_sentences = display_example_sentences;
  w.change_example_sentences_zone = change_example_sentences_zone;
  w.do_ajax_show_sentences = do_ajax_show_sentences;
  w.do_ajax_req_sim_terms = do_ajax_req_sim_terms;
  w.do_ajax_show_similar_terms = do_ajax_show_similar_terms;
  w.do_ajax_word_counts = do_ajax_word_counts;
  w.set_barchart_item = set_barchart_item;
  w.set_word_counts = set_word_counts;
  w.word_count_click = word_count_click;
  w.translation_radio = translation_radio;
  w.edit_term_ann_translations = edit_term_ann_translations;
  w.do_ajax_edit_impr_text = do_ajax_edit_impr_text;
  w.showRightFrames = showRightFramesImpl;
  w.hideRightFrames = hideRightFrames;
  w.cleanupRightFrames = cleanupRightFramesImpl;
  w.successSound = successSound;
  w.failureSound = failureSound;
  w.lwt = lwt;
  w.wrapRadioButtons = wrapRadioButtons;
  w.prepareMainAreas = prepareMainAreas;
}
