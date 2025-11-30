/**
 * Term Operations - Translation updates, term editing, and annotations
 *
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 */

import $ from 'jquery';
import { escape_html_chars } from '../core/html_utils';
import { isInt } from '../forms/form_validation';
import { scrollTo } from '../core/hover_intent';

// Interface for lwtFormCheck
interface LwtFormCheck {
  makeDirty: () => void;
}

declare const lwtFormCheck: LwtFormCheck;

/**
 * Helper to safely get an HTML attribute value as a string.
 */
function getAttr($el: JQuery, attr: string): string {
  const val = $el.attr(attr);
  return typeof val === 'string' ? val : '';
}

// Interface for translation data
export interface TransData {
  wid: number | null;
  trans: string;
  ann_index: string;
  term_ord: string;
  term_lc: string;
  lang_id: number;
  translations: string[];
}

/**
 * Set translation and romanization in a form when possible.
 *
 * Mark the form as edited if something was changed.
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

// Extend jQuery with serializeObject
declare global {
  interface JQuery {
    serializeObject(): Record<string, unknown>;
  }
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
    onclick="oewin('/word/edit?` + escape_html_chars(req_arg) + `');">
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
        scrollTo(pagepos);
        $('input.impr-ann-text').on('change', changeImprAnnText);
        $('input.impr-ann-radio').on('change', changeImprAnnRadio);
      }
    }
  );
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
 * Prepare am HTML element that formats the sentences
 *
 * @param sentences    A list of sentences to display.
 * @param targetCtlId The ID of the element that should change value on click
 * @returns A formatted group of sentences
 */
export function display_example_sentences(
  sentences: [string, string][],
  targetCtlId: string
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
    clickable.dataset.action = 'copy-sentence';
    clickable.dataset.target = targetCtlId;
    clickable.dataset.sentence = sentences[i][1];
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
 * Initialize event delegation for sentence-related actions.
 *
 * Handles elements with data-action attributes for sentence operations.
 */
function initSentenceEventDelegation(): void {
  // Handle copy-sentence: copy sentence to textarea
  $(document).on('click', '[data-action="copy-sentence"]', function (this: HTMLElement) {
    const targetId = this.dataset.target;
    const sentence = this.dataset.sentence;
    if (targetId && sentence !== undefined) {
      const target = document.getElementById(targetId) as HTMLTextAreaElement | null;
      if (target) {
        target.value = sentence;
        lwtFormCheck.makeDirty();
      }
    }
  });

  // Handle show-sentences: load and display example sentences
  $(document).on('click', '[data-action="show-sentences"]', function (this: HTMLElement) {
    const lang = parseInt(this.dataset.lang || '0', 10);
    const termlc = this.dataset.termlc || '';
    const targetId = this.dataset.target || '';
    const wid = parseInt(this.dataset.wid || '0', 10);
    if (lang && termlc) {
      do_ajax_show_sentences(lang, termlc, targetId, wid);
    }
  });

  // Handle set-trans-roman: copy translation and romanization from similar terms
  $(document).on('click', '[data-action="set-trans-roman"]', function (this: HTMLElement) {
    const translation = this.dataset.translation || '';
    const romanization = this.dataset.romanization || '';
    setTransRoman(translation, romanization);
  });
}

/**
 * Initialize event delegation for improved text annotation actions.
 *
 * Handles elements with data-action attributes for annotation operations.
 */
function initImprovedTextEventDelegation(): void {
  // Handle erase-field: clear a text input and trigger change event
  $(document).on('click', '[data-action="erase-field"]', function (this: HTMLElement) {
    const target = this.dataset.target;
    if (target) {
      $(target).val('').trigger('change');
    }
  });

  // Handle set-star: set text input to '*' and trigger change event
  $(document).on('click', '[data-action="set-star"]', function (this: HTMLElement) {
    const target = this.dataset.target;
    if (target) {
      $(target).val('*').trigger('change');
    }
  });

  // Handle update-term-translation: update translation for existing term
  $(document).on('click', '[data-action="update-term-translation"]', function (this: HTMLElement) {
    const wid = parseInt(this.dataset.wid || '0', 10);
    const target = this.dataset.target || '';
    if (wid && target) {
      updateTermTranslation(wid, target);
    }
  });

  // Handle add-term-translation: add translation for new term
  $(document).on('click', '[data-action="add-term-translation"]', function (this: HTMLElement) {
    const target = this.dataset.target || '';
    const word = this.dataset.word || '';
    const lang = parseInt(this.dataset.lang || '0', 10);
    if (target && word && lang) {
      addTermTranslation(target, word, lang);
    }
  });

  // Handle reload-impr-text: reload the improved text annotations form
  $(document).on('click', '[data-action="reload-impr-text"]', function () {
    do_ajax_edit_impr_text(0, '', 0);
  });

  // Handle back-to-print-mode: navigate to print/display mode
  $(document).on('click', '[data-action="back-to-print-mode"]', function (this: HTMLElement) {
    const textid = this.dataset.textid || '';
    if (textid) {
      location.href = 'print_impr_text.php?text=' + textid;
    }
  });

  // Handle edit-term-popup: open term editor in popup window
  $(document).on('click', '[data-action="edit-term-popup"]', function (this: HTMLElement) {
    const wid = this.dataset.wid || '';
    const textid = this.dataset.textid || '';
    const ord = this.dataset.ord || '';
    const scrollPos = $(document).scrollTop() || 0;
    if (wid && textid) {
      const url = '/word/edit?fromAnn=' + scrollPos + '&wid=' + wid + '&tid=' + textid + '&ord=' + ord;
      window.oewin(url);
    }
  });
}

// Auto-initialize when DOM is ready
$(document).ready(function () {
  initSentenceEventDelegation();
  initImprovedTextEventDelegation();
});
