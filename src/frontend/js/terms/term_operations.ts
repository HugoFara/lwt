/**
 * Term Operations - Translation updates, term editing, and annotations
 *
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 */

import { escape_html_chars } from '../core/html_utils';
import { isInt } from '../forms/form_validation';
import { scrollTo } from '../core/hover_intent';
import { apiPost, apiGet } from '../core/api_client';
import { TermsApi } from '../api/terms';
import { iconHtml, spinnerHtml } from '../ui/icons';
import { oewin } from './dictionary';

// Interface for lwtFormCheck
interface LwtFormCheck {
  makeDirty: () => void;
}

declare const lwtFormCheck: LwtFormCheck;

/**
 * Helper to safely get an HTML attribute value as a string.
 */
function getAttr(el: Element | null, attr: string): string {
  if (!el) return '';
  const val = el.getAttribute(attr);
  return val !== null ? val : '';
}

/**
 * Serialize a form to an object (replacement for jQuery's serializeObject).
 */
function serializeFormToObject(form: HTMLFormElement): Record<string, string> {
  const result: Record<string, string> = {};
  const formData = new FormData(form);
  formData.forEach((value, key) => {
    result[key] = value.toString();
  });
  return result;
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
  const translationEl = document.querySelector<HTMLTextAreaElement>('textarea[name="WoTranslation"]');
  if (translationEl) {
    translationEl.value = tra;
    form_changed = true;
  }
  const romanizationEl = document.querySelector<HTMLInputElement>('input[name="WoRomanization"]');
  if (romanizationEl) {
    romanizationEl.value = rom;
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
export async function do_ajax_save_impr_text(textid: number, elem_name: string, form_data: string): Promise<void> {
  const waitEl = document.getElementById('wait' + elem_name.substring(2));
  if (waitEl) {
    waitEl.innerHTML = spinnerHtml();
  }

  const response = await apiPost<{ error?: string }>(
    `/texts/${textid}/annotation`,
    { elem: elem_name, data: form_data }
  );

  if (waitEl) {
    waitEl.innerHTML = iconHtml('empty');
  }
  if (response.error || response.data?.error) {
    alert(
      'Saving your changes failed, please reload the page and try again! ' +
      'Error message: "' + (response.error || response.data?.error) + '".'
    );
  }
}


/**
 * Change the annotation for a term by setting its text.
 */
export function changeImprAnnText(this: HTMLElement): void {
  const prevRadio = this.previousElementSibling as HTMLInputElement | null;
  if (prevRadio && prevRadio.matches('input[type="radio"]')) {
    prevRadio.checked = true;
  }
  const textid = parseInt(getAttr(document.getElementById('editimprtextdata'), 'data_id') || '0', 10);
  const elem_name = this.getAttribute('name') || '';
  const form = document.querySelector('form');
  const form_data = form ? JSON.stringify(serializeFormToObject(form)) : '{}';
  do_ajax_save_impr_text(textid, elem_name, form_data);
}

/**
 * Change the annotation for a term by setting its text.
 */
export function changeImprAnnRadio(this: HTMLElement): void {
  const textid = parseInt(getAttr(document.getElementById('editimprtextdata'), 'data_id') || '0', 10);
  const elem_name = this.getAttribute('name') || '';
  const form = document.querySelector('form');
  const form_data = form ? JSON.stringify(serializeFormToObject(form)) : '{}';
  do_ajax_save_impr_text(textid, elem_name, form_data);
}

/**
 * Update a word translation.
 *
 * @param wordid Word ID
 * @param txid   Text HTML ID or unique HTML selector
 */
export async function updateTermTranslation(wordid: number, txid: string): Promise<void> {
  const el = document.querySelector<HTMLInputElement | HTMLTextAreaElement>(txid);
  const translation = (el?.value || '').trim();
  const pagepos = window.scrollY || document.documentElement.scrollTop || 0;
  if (translation === '' || translation === '*') {
    alert('Text Field is empty or = \'*\'!');
    return;
  }
  const failure = 'Updating translation of term failed!' +
  'Please reload page and try again.';

  const response = await TermsApi.updateTranslation(wordid, translation);

  if (response.error) {
    alert(failure + '\n' + response.error);
    return;
  }
  if (response.data?.update) {
    do_ajax_edit_impr_text(pagepos, response.data.update, wordid);
  }
}

/**
 * Add (new word) a word translation.
 *
 * @param txid   Text HTML ID or unique HTML selector
 * @param word   Word text
 * @param lang   Language ID
 */
export async function addTermTranslation(txid: string, word: string, lang: number): Promise<void> {
  const el = document.querySelector<HTMLInputElement | HTMLTextAreaElement>(txid);
  const translation = (el?.value || '').trim();
  const pagepos = window.scrollY || document.documentElement.scrollTop || 0;
  if (translation === '' || translation === '*') {
    alert('Text Field is empty or = \'*\'!');
    return;
  }
  const failure = 'Adding translation to term failed!' +
  'Please reload page and try again.';

  const response = await TermsApi.addWithTranslation(word, lang, translation);

  if (response.error) {
    alert(failure + '\n' + response.error);
    return;
  }
  if (response.data?.add && response.data?.term_id !== undefined) {
    do_ajax_edit_impr_text(pagepos, response.data.add, response.data.term_id);
  }
}

/**
 * Set a new status for a word in the test table.
 *
 * @param wordid Word ID
 * @param up     true if status should be increased, false otherwise
 */
export async function changeTableTestStatus(wordid: string, up: boolean): Promise<void> {
  const wid = parseInt(wordid, 10);
  const response = await TermsApi.incrementStatus(wid, up ? 'up' : 'down');

  if (response.error) {
    return;
  }
  if (response.data?.increment) {
    const statEl = document.getElementById('STAT' + wordid);
    if (statEl) {
      statEl.innerHTML = response.data.increment;
    }
  }
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
    const params = new URLSearchParams({
      fromAnn: String(window.scrollY || document.documentElement.scrollTop || 0),
      wid: String(trans_data.wid),
      ord: trans_data.term_ord,
      tid: String(text_id)
    });
    edit_word_link = `<a name="rec${trans_data.ann_index}"></a>
    <span class="click"
    onclick="oewin('/word/edit?` + escape_html_chars(params.toString()) + `');">
          ${iconHtml('sticky-note--pencil', { title: 'Edit Term', alt: 'Edit Term' })}
      </span>`;
  } else {
    edit_word_link = '&nbsp;';
  }
  const editLinkEl = document.getElementById(`editlink${trans_data.ann_index}`);
  if (editLinkEl) {
    editLinkEl.innerHTML = edit_word_link;
  }
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
  <span class="click" data-action="erase-field" data-target="#tx${trans_data.ann_index}">
    ${iconHtml('eraser', { title: 'Erase Text Field', alt: 'Erase Text Field' })}
  </span>
    &nbsp;
  <span class="click" data-action="set-star" data-target="#tx${trans_data.ann_index}">
    ${iconHtml('star', { title: '* (Set to Term)', alt: '* (Set to Term)' })}
  </span>
  &nbsp;`;
  // Add the "plus button" to add a translation
  if (widset) {
    translations_list +=
    `<span class="click" onclick="updateTermTranslation(${trans_data.wid}, '#tx${trans_data.ann_index}');">
      ${iconHtml('plus-button', { title: 'Save another translation to existent term', alt: 'Save another translation to existent term' })}
    </span>`;
  } else {
    translations_list +=
    `<span class="click" onclick="addTermTranslation('#tx${trans_data.ann_index}',${trans_data.term_lc},${trans_data.lang_id});">
      ${iconHtml('plus-button', { title: 'Save translation to new term', alt: 'Save translation to new term' })}
    </span>`;
  }
  translations_list += `&nbsp;&nbsp;
  <span id="wait${trans_data.ann_index}">
      ${iconHtml('empty')}
  </span>
  </span>`;
  const transselEl = document.getElementById(`transsel${trans_data.ann_index}`);
  if (transselEl) {
    transselEl.innerHTML = translations_list;
  }
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
export async function do_ajax_edit_impr_text(pagepos: number, word: string, term_id: number): Promise<void> {
  const editImprTextDataEl = document.getElementById('editimprtextdata');
  // Special case, on empty word reload the main annotations form
  if (word === '') {
    if (editImprTextDataEl) {
      editImprTextDataEl.innerHTML = spinnerHtml();
    }
    location.reload();
    return;
  }
  // Load the possible translations for a word
  const textid = parseInt(getAttr(editImprTextDataEl, 'data_id') || '0', 10);

  const response = await apiGet<TransData & { error?: string }>(
    `/terms/${term_id}/translations`,
    { text_id: textid, term_lc: word }
  );

  if (response.error || response.data?.error) {
    alert(response.error || response.data?.error);
  } else if (response.data) {
    edit_term_ann_translations(response.data, textid);
    scrollTo(pagepos);
    document.querySelectorAll<HTMLInputElement>('input.impr-ann-text').forEach(el => {
      el.addEventListener('change', changeImprAnnText);
    });
    document.querySelectorAll<HTMLInputElement>('input.impr-ann-radio').forEach(el => {
      el.addEventListener('change', changeImprAnnRadio);
    });
  }
}

/**
 * Send an AJAX request to get similar terms to a term.
 *
 * @param lg_id Language ID
 * @param word_text Text to match
 * @returns Promise with similar terms data
 */
export async function do_ajax_req_sim_terms(lg_id: number, word_text: string): Promise<{ similar_terms: string } | null> {
  const response = await apiGet<{ similar_terms: string }>(
    '/similar-terms',
    { lg_id, term: word_text }
  );
  return response.data || null;
}

/**
 * Display the terms similar to a specific term with AJAX.
 */
export async function do_ajax_show_similar_terms(): Promise<void> {
  const simwordsEl = document.getElementById('simwords');
  if (simwordsEl) {
    simwordsEl.innerHTML = spinnerHtml();
  }

  const langfieldEl = document.getElementById('langfield') as HTMLInputElement | HTMLSelectElement | null;
  const wordfieldEl = document.getElementById('wordfield') as HTMLInputElement | null;
  const data = await do_ajax_req_sim_terms(
    parseInt(langfieldEl?.value || '0', 10),
    wordfieldEl?.value || ''
  );

  if (data && simwordsEl) {
    simwordsEl.innerHTML = data.similar_terms;
  } else {
    console.log('Failed to load similar terms');
  }
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
  let clickable: HTMLSpanElement, parentDiv: HTMLDivElement;
  const outElement = document.createElement('div');
  for (let i = 0; i < sentences.length; i++) {
    // Clickable element with Lucide icon
    clickable = document.createElement('span');
    clickable.classList.add('click');
    clickable.dataset.action = 'copy-sentence';
    clickable.dataset.target = targetCtlId;
    clickable.dataset.sentence = sentences[i][1];
    clickable.innerHTML = iconHtml('tick-button', { title: 'Choose' });
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
  const waitingEl = document.getElementById('exsent-waiting');
  const sentencesEl = document.getElementById('exsent-sentences');
  if (waitingEl) {
    waitingEl.style.display = 'none';
  }
  if (sentencesEl) {
    sentencesEl.style.display = 'inherit';
    const new_element = display_example_sentences(sentences, ctl);
    sentencesEl.appendChild(new_element);
  }
}

/**
 * Get and display the sentences containing specific word.
 *
 * @param lang Language ID
 * @param word Term text (the looked for term)
 * @param ctl  Selector for the element to edit on click
 * @param woid Term id (word or multi-word)
 */
export async function do_ajax_show_sentences(lang: number, word: string, ctl: string, woid: number | string): Promise<void> {
  const interactableEl = document.getElementById('exsent-interactable');
  const waitingEl = document.getElementById('exsent-waiting');
  if (interactableEl) {
    interactableEl.style.display = 'none';
  }
  if (waitingEl) {
    waitingEl.style.display = 'inherit';
  }

  let response;
  if (isInt(String(woid)) && woid !== -1) {
    response = await apiGet<[string, string][]>(
      `/sentences-with-term/${woid}`,
      { lg_id: lang, word_lc: word }
    );
  } else {
    const params: { lg_id: number; word_lc: string; advanced_search?: boolean } = {
      lg_id: lang,
      word_lc: word
    };
    if (parseInt(String(woid), 10) === -1) {
      params.advanced_search = true;
    }
    response = await apiGet<[string, string][]>('/sentences-with-term', params);
  }

  if (response.data) {
    change_example_sentences_zone(response.data, ctl);
  }
}

/**
 * Initialize event delegation for sentence-related actions.
 *
 * Handles elements with data-action attributes for sentence operations.
 */
function initSentenceEventDelegation(): void {
  document.addEventListener('click', function (e) {
    const target = e.target as HTMLElement;
    const actionEl = target.closest('[data-action]') as HTMLElement | null;
    if (!actionEl) return;

    const action = actionEl.dataset.action;

    // Handle copy-sentence: copy sentence to textarea
    if (action === 'copy-sentence') {
      const targetId = actionEl.dataset.target;
      const sentence = actionEl.dataset.sentence;
      if (targetId && sentence !== undefined) {
        const targetEl = document.getElementById(targetId) as HTMLTextAreaElement | null;
        if (targetEl) {
          targetEl.value = sentence;
          lwtFormCheck.makeDirty();
        }
      }
    }

    // Handle show-sentences: load and display example sentences
    if (action === 'show-sentences') {
      const lang = parseInt(actionEl.dataset.lang || '0', 10);
      const termlc = actionEl.dataset.termlc || '';
      const targetId = actionEl.dataset.target || '';
      const wid = parseInt(actionEl.dataset.wid || '0', 10);
      if (lang && termlc) {
        do_ajax_show_sentences(lang, termlc, targetId, wid);
      }
    }

    // Handle set-trans-roman: copy translation and romanization from similar terms
    if (action === 'set-trans-roman') {
      const translation = actionEl.dataset.translation || '';
      const romanization = actionEl.dataset.romanization || '';
      setTransRoman(translation, romanization);
    }
  });
}

/**
 * Initialize event delegation for improved text annotation actions.
 *
 * Handles elements with data-action attributes for annotation operations.
 */
function initImprovedTextEventDelegation(): void {
  document.addEventListener('click', function (e) {
    const target = e.target as HTMLElement;
    const actionEl = target.closest('[data-action]') as HTMLElement | null;
    if (!actionEl) return;

    const action = actionEl.dataset.action;

    // Handle erase-field: clear a text input and trigger change event
    if (action === 'erase-field') {
      const targetSelector = actionEl.dataset.target;
      if (targetSelector) {
        const inputEl = document.querySelector<HTMLInputElement>(targetSelector);
        if (inputEl) {
          inputEl.value = '';
          inputEl.dispatchEvent(new Event('change', { bubbles: true }));
        }
      }
    }

    // Handle set-star: set text input to '*' and trigger change event
    if (action === 'set-star') {
      const targetSelector = actionEl.dataset.target;
      if (targetSelector) {
        const inputEl = document.querySelector<HTMLInputElement>(targetSelector);
        if (inputEl) {
          inputEl.value = '*';
          inputEl.dispatchEvent(new Event('change', { bubbles: true }));
        }
      }
    }

    // Handle update-term-translation: update translation for existing term
    if (action === 'update-term-translation') {
      const wid = parseInt(actionEl.dataset.wid || '0', 10);
      const targetSelector = actionEl.dataset.target || '';
      if (wid && targetSelector) {
        updateTermTranslation(wid, targetSelector);
      }
    }

    // Handle add-term-translation: add translation for new term
    if (action === 'add-term-translation') {
      const targetSelector = actionEl.dataset.target || '';
      const word = actionEl.dataset.word || '';
      const lang = parseInt(actionEl.dataset.lang || '0', 10);
      if (targetSelector && word && lang) {
        addTermTranslation(targetSelector, word, lang);
      }
    }

    // Handle reload-impr-text: reload the improved text annotations form
    if (action === 'reload-impr-text') {
      do_ajax_edit_impr_text(0, '', 0);
    }

    // Handle back-to-print-mode: navigate to print/display mode
    if (action === 'back-to-print-mode') {
      const textid = actionEl.dataset.textid || '';
      if (textid) {
        location.href = 'print_impr_text.php?text=' + textid;
      }
    }

    // Handle edit-term-popup: open term editor in popup window
    if (action === 'edit-term-popup') {
      const wid = actionEl.dataset.wid || '';
      const textid = actionEl.dataset.textid || '';
      const ord = actionEl.dataset.ord || '';
      const scrollPos = window.scrollY || document.documentElement.scrollTop || 0;
      if (wid && textid) {
        const url = '/word/edit?fromAnn=' + scrollPos + '&wid=' + wid + '&tid=' + textid + '&ord=' + ord;
        oewin(url);
      }
    }
  });
}

// Listen for cross-window improved text edit events
interface ImprTextEditEvent extends CustomEvent {
  detail: {
    pagepos: number;
    word: string;
    termId: number;
  };
}

document.addEventListener('lwt-edit-impr-text', ((e: ImprTextEditEvent) => {
  do_ajax_edit_impr_text(e.detail.pagepos, e.detail.word, e.detail.termId);
}) as EventListener);

/**
 * Trigger improved text edit in opener window via custom event.
 * Use this from popup windows instead of accessing window.opener.do_ajax_edit_impr_text.
 */
export function editImprTextInOpener(pagepos: number, word: string, termId: number): void {
  try {
    if (window.opener && window.opener !== window) {
      window.opener.document.dispatchEvent(new CustomEvent('lwt-edit-impr-text', {
        detail: { pagepos, word, termId }
      }));
    }
  } catch {
    // Opener access may be blocked by same-origin policy, ignore
  }
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function () {
    initSentenceEventDelegation();
    initImprovedTextEventDelegation();
  });
} else {
  initSentenceEventDelegation();
  initImprovedTextEventDelegation();
}
