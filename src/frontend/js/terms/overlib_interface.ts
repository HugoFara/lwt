/**
 * LWT Javascript functions
 *
 * Word popup interface - provides popup dialogs for word interactions.
 *
 * This module provides two approaches for word operations:
 * 1. Legacy frame-based approach (make_popup_link_* functions)
 * 2. Modern API-based approach (create*Button functions)
 *
 * The API-based approach uses the TermsApi/ReviewApi instead of frame navigation.
 *
 * @author  HugoFara <HugoFara@users.noreply.github.com>
 * @license Unlicense <http://unlicense.org/>
 */

import Alpine from 'alpinejs';
import { escape_html_chars, escape_html_chars_2 } from '../core/html_utils';
import { make_tooltip, getStatusName, getStatusAbbr } from './word_status';
import { createTheDictLink, createSentLookupLink } from './dictionary';
import { TermsApi, type TermDetails } from '../api/terms';
import { iconHtml, createIcon, initLucideIcons } from '../ui/icons';
import { showRightFramesPanel } from '../reading/frame_management';
import { speechDispatcher } from '../core/user_interactions';
import type { MultiWordFormStoreState } from '../reading/stores/multi_word_form_store';
import { parseInlineMarkdown } from '../core/inline_markdown';

// Import the popup system
import { overlib } from '../ui/word_popup';

// Import API-based word actions
import {
  changeWordStatus,
  deleteWord,
  markWellKnown,
  markIgnored,
  incrementWordStatus,
  type WordActionContext
} from '../reading/word_actions';

// Re-export for backward compatibility
export { overlib, cClick, nd } from '../ui/word_popup';

// Note: The following functions are used in HTML string templates (onclick handlers)
// and accessed via window at runtime: showRightFramesPanel, confirmDelete, successSound, failureSound
// They are exported to window in globals.ts

// Type for LWT_DATA global
interface LwtLanguage {
  id: number;
}

interface LwtDataGlobal {
  language: LwtLanguage;
}

declare const LWT_DATA: LwtDataGlobal;

/**************************************************************
 * Modern API-based popup content generators
 *
 * These functions create DOM elements with event handlers
 * that use the API instead of frame navigation.
 ***************************************************************/

/**
 * Create a status change button that uses API call.
 *
 * @param context Word action context
 * @param newStatus The status to change to
 * @param options Button options
 * @returns HTMLButtonElement with click handler
 */
export function createStatusChangeButton(
  context: WordActionContext,
  newStatus: number,
  options: { showAbbr?: boolean; className?: string } = {}
): HTMLButtonElement {
  const btn = document.createElement('button');
  btn.type = 'button';
  btn.className = options.className || 'lwt-status-btn';
  btn.title = getStatusName(newStatus);

  if (context.status === newStatus) {
    btn.textContent = '◆';
    btn.disabled = true;
    btn.classList.add('lwt-status-btn--current');
  } else {
    btn.textContent = options.showAbbr !== false ? `[${getStatusAbbr(newStatus)}]` : getStatusAbbr(newStatus);
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      e.stopPropagation();
      btn.disabled = true;
      await changeWordStatus(context, newStatus);
    });
  }

  return btn;
}

/**
 * Create all status change buttons (1-5, 99, 98).
 *
 * @param context Word action context
 * @returns DocumentFragment with all status buttons
 */
export function createStatusButtonsAll(context: WordActionContext): DocumentFragment {
  const fragment = document.createDocumentFragment();

  const label = document.createElement('span');
  label.textContent = 'St: ';
  fragment.appendChild(label);

  // Status 1-5
  for (let s = 1; s <= 5; s++) {
    fragment.appendChild(createStatusChangeButton(context, s));
    fragment.appendChild(document.createTextNode(' '));
  }

  // Status 99 (well-known) and 98 (ignored)
  fragment.appendChild(createStatusChangeButton(context, 99));
  fragment.appendChild(document.createTextNode(' '));
  fragment.appendChild(createStatusChangeButton(context, 98));

  return fragment;
}

/**
 * Create a delete term button that uses API call.
 *
 * @param context Word action context
 * @param confirm Whether to show confirmation dialog
 * @returns HTMLButtonElement with click handler
 */
export function createDeleteButton(
  context: WordActionContext,
  confirm: boolean = true
): HTMLButtonElement {
  const btn = document.createElement('button');
  btn.type = 'button';
  btn.className = 'lwt-action-btn lwt-action-btn--delete';
  btn.textContent = 'Delete term';

  btn.addEventListener('click', async (e) => {
    e.preventDefault();
    e.stopPropagation();

    if (confirm && !window.confirm('Are you sure you want to delete this term?')) {
      return;
    }

    btn.disabled = true;
    await deleteWord(context);
  });

  return btn;
}

/**
 * Create a "mark as well-known" button that uses API call.
 *
 * @param context Word action context
 * @returns HTMLButtonElement with click handler
 */
export function createWellKnownButton(context: WordActionContext): HTMLButtonElement {
  const btn = document.createElement('button');
  btn.type = 'button';
  btn.className = 'lwt-action-btn lwt-action-btn--wellknown';
  btn.textContent = 'I know this term well';

  btn.addEventListener('click', async (e) => {
    e.preventDefault();
    e.stopPropagation();
    btn.disabled = true;
    await markWellKnown(context);
  });

  return btn;
}

/**
 * Create an "ignore term" button that uses API call.
 *
 * @param context Word action context
 * @returns HTMLButtonElement with click handler
 */
export function createIgnoreButton(context: WordActionContext): HTMLButtonElement {
  const btn = document.createElement('button');
  btn.type = 'button';
  btn.className = 'lwt-action-btn lwt-action-btn--ignore';
  btn.textContent = 'Ignore this term';

  btn.addEventListener('click', async (e) => {
    e.preventDefault();
    e.stopPropagation();
    btn.disabled = true;
    await markIgnored(context);
  });

  return btn;
}

/**
 * Create test mode status change buttons (Got it / Oops).
 *
 * @param context Word action context
 * @returns DocumentFragment with test buttons
 */
export function createTestStatusButtons(context: WordActionContext): DocumentFragment {
  const fragment = document.createDocumentFragment();
  const s = context.status || 1;

  // Only show if status is 1-5
  if (s >= 1 && s <= 5) {
    const nextUp = Math.min(s + 1, 5);
    const nextDown = Math.max(s - 1, 1);

    // "Got it" button
    const gotItBtn = document.createElement('button');
    gotItBtn.type = 'button';
    gotItBtn.className = 'lwt-test-btn lwt-test-btn--success';
    gotItBtn.innerHTML = `${iconHtml('thumb-up', { alt: 'Got it!' })} Got it! [${s} ▶ ${nextUp}]`;

    gotItBtn.addEventListener('click', async (e) => {
      e.preventDefault();
      e.stopPropagation();
      gotItBtn.disabled = true;
      await incrementWordStatus(context, 'up');
    });

    fragment.appendChild(gotItBtn);
    fragment.appendChild(document.createElement('hr'));

    // "Oops" button
    const oopsBtn = document.createElement('button');
    oopsBtn.type = 'button';
    oopsBtn.className = 'lwt-test-btn lwt-test-btn--failure';
    oopsBtn.innerHTML = `${iconHtml('thumb', { alt: 'Oops!' })} Oops! [${s} ▶ ${nextDown}]`;

    oopsBtn.addEventListener('click', async (e) => {
      e.preventDefault();
      e.stopPropagation();
      oopsBtn.disabled = true;
      await incrementWordStatus(context, 'down');
    });

    fragment.appendChild(oopsBtn);
    fragment.appendChild(document.createElement('hr'));
  }

  return fragment;
}

/**
 * Build complete popup content for a known word (status 1-5) using API-based buttons.
 *
 * @param context Word action context
 * @param dictLinks Dictionary link URLs
 * @param multiWords Multi-word expressions containing this word
 * @param rtl Right-to-left indicator
 * @returns HTMLElement containing popup content
 */
export function buildKnownWordPopupContent(
  context: WordActionContext,
  dictLinks: { dict1: string; dict2: string; translator: string },
  multiWords: (string | undefined)[],
  rtl: boolean
): HTMLElement {
  const container = document.createElement('div');
  container.className = 'lwt-word-popup-content';

  // Audio button
  container.appendChild(createAudioElement(context.text));

  // Status buttons
  const statusRow = document.createElement('div');
  statusRow.className = 'lwt-popup-row';
  statusRow.appendChild(createStatusButtonsAll(context));
  container.appendChild(statusRow);

  // Action buttons row
  const actionsRow = document.createElement('div');
  actionsRow.className = 'lwt-popup-row';

  // Edit link (still uses frame for complex form)
  const editLink = document.createElement('a');
  editLink.href = `/word/edit?tid=${context.textId}&ord=${context.position}&wid=${context.wordId}`;
  editLink.target = 'ro';
  editLink.textContent = 'Edit term';
  editLink.onclick = () => { showRightFramesPanel(); };
  actionsRow.appendChild(editLink);

  actionsRow.appendChild(document.createTextNode(' | '));
  actionsRow.appendChild(createDeleteButton(context));

  container.appendChild(actionsRow);

  // Multi-word expressions
  if (multiWords.some(mw => mw)) {
    const mwRow = document.createElement('div');
    mwRow.className = 'lwt-popup-row';
    mwRow.innerHTML = make_overlib_link_new_multiword(
      context.textId,
      context.position,
      multiWords,
      rtl
    );
    container.appendChild(mwRow);
  }

  // Dictionary links
  const dictRow = document.createElement('div');
  dictRow.className = 'lwt-popup-row';
  dictRow.innerHTML = make_overlib_link_wb(
    dictLinks.dict1,
    dictLinks.dict2,
    dictLinks.translator,
    context.text,
    context.textId,
    context.position
  );
  container.appendChild(dictRow);

  return container;
}

/**
 * Build complete popup content for an unknown word using API-based buttons.
 *
 * @param context Word action context
 * @param dictLinks Dictionary link URLs
 * @param multiWords Multi-word expressions
 * @param rtl Right-to-left indicator
 * @returns HTMLElement containing popup content
 */
export function buildUnknownWordPopupContent(
  context: WordActionContext,
  dictLinks: { dict1: string; dict2: string; translator: string },
  multiWords: (string | undefined)[],
  rtl: boolean
): HTMLElement {
  const container = document.createElement('div');
  container.className = 'lwt-word-popup-content';

  // Audio button
  container.appendChild(createAudioElement(context.text));

  // Well-known button
  const wkRow = document.createElement('div');
  wkRow.className = 'lwt-popup-row';
  wkRow.appendChild(createWellKnownButton(context));
  container.appendChild(wkRow);

  // Ignore button
  const ignoreRow = document.createElement('div');
  ignoreRow.className = 'lwt-popup-row';
  ignoreRow.appendChild(createIgnoreButton(context));
  container.appendChild(ignoreRow);

  // Multi-word expressions
  if (multiWords.some(mw => mw)) {
    const mwRow = document.createElement('div');
    mwRow.className = 'lwt-popup-row';
    mwRow.innerHTML = make_overlib_link_new_multiword(
      context.textId,
      context.position,
      multiWords,
      rtl
    );
    container.appendChild(mwRow);
  }

  // Dictionary links
  const dictRow = document.createElement('div');
  dictRow.className = 'lwt-popup-row';
  dictRow.innerHTML = make_overlib_link_wb(
    dictLinks.dict1,
    dictLinks.dict2,
    dictLinks.translator,
    context.text,
    context.textId,
    context.position
  );
  container.appendChild(dictRow);

  return container;
}

/**
 * Create an audio play element.
 *
 * @param text Text to read aloud
 * @returns HTMLElement with audio button
 */
function createAudioElement(text: string): HTMLElement {
  const container = document.createElement('div');
  container.className = 'lwt-popup-row lwt-popup-audio';

  const icon = createIcon('speaker-volume', {
    title: 'Click to read!',
    clickable: true
  });
  icon.style.cursor = 'pointer';
  icon.addEventListener('click', () => {
    speechDispatcher(text, LWT_DATA.language.id);
  });

  container.appendChild(icon);

  // Initialize Lucide icons for the new element
  initLucideIcons();

  return container;
}

/**
 * Build a word details panel from API data.
 * Fetches term details via API and renders them as HTML.
 *
 * @param termId Term ID to fetch details for
 * @param ann Optional annotation to highlight in translation
 * @returns Promise resolving to HTMLElement with term details
 */
export async function buildWordDetailsPanel(
  termId: number,
  ann?: string
): Promise<HTMLElement> {
  const container = document.createElement('div');
  container.className = 'lwt-word-details';

  if (!termId) {
    container.innerHTML = '<p class="lwt-error">No term ID provided</p>';
    return container;
  }

  // Show loading state
  container.innerHTML = '<p class="lwt-loading">Loading...</p>';

  const response = await TermsApi.getDetails(termId, ann);

  if (response.error || !response.data) {
    container.innerHTML = `<p class="lwt-error">${response.error || 'Failed to load term details'}</p>`;
    return container;
  }

  const term = response.data;
  container.innerHTML = renderTermDetailsHtml(term);

  return container;
}

/**
 * Render term details as HTML string.
 *
 * @param term Term details object
 * @returns HTML string
 */
function renderTermDetailsHtml(term: TermDetails): string {
  const rows: string[] = [];

  // Term text
  rows.push(`<tr><td class="lwt-label">Term:</td><td class="lwt-value"><b>${escapeHtml(term.text)}</b></td></tr>`);

  // Translation (supports inline Markdown)
  if (term.translation && term.translation !== '*') {
    rows.push(`<tr><td class="lwt-label">Translation:</td><td class="lwt-value"><b>${parseInlineMarkdown(term.translation)}</b></td></tr>`);
  }

  // Notes (supports inline Markdown)
  if (term.notes) {
    rows.push(`<tr><td class="lwt-label">Notes:</td><td class="lwt-value">${parseInlineMarkdown(term.notes)}</td></tr>`);
  }

  // Tags
  if (term.tags && term.tags.length > 0) {
    rows.push(`<tr><td class="lwt-label">Tags:</td><td class="lwt-value">${renderTags(term.tags)}</td></tr>`);
  }

  // Romanization
  if (term.romanization) {
    rows.push(`<tr><td class="lwt-label">Romaniz.:</td><td class="lwt-value">${escapeHtml(term.romanization)}</td></tr>`);
  }

  // Sentence (format {term} markers)
  if (term.sentence) {
    const formattedSentence = term.sentence
      .replace(/\{([^}]+)\}/g, '<b>$1</b>');
    rows.push(`<tr><td class="lwt-label">Sentence:</td><td class="lwt-value">${formattedSentence}</td></tr>`);
  }

  // Status
  rows.push(`<tr><td class="lwt-label">Status:</td><td class="lwt-value status${term.status}">${escapeHtml(term.statusLabel)}</td></tr>`);

  return `<table class="lwt-details-table">${rows.join('')}</table>`;
}

/**
 * Simple HTML escaping helper.
 *
 * @param str String to escape
 * @returns HTML-escaped string
 */
function escapeHtml(str: string): string {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

/**
 * Render tags as tag components.
 *
 * @param tags Array of tag strings
 * @returns HTML string with tags
 */
function renderTags(tags: string[]): string {
  if (!tags || tags.length === 0) {
    return '';
  }
  return '<div class="tags">' +
    tags.map(tag => `<span class="tag is-info is-light is-small">${escapeHtml(tag)}</span>`).join('') +
    '</div>';
}

/**************************************************************
 * Helper functions for word popups
 ***************************************************************/

/**
 * Handle click event on ignored words
 *
 * @param wblink1     First dictionary URL
 * @param wblink2     Second dictionary URL
 * @param wblink3     Google Translate dictionary URL
 * @param hints       Hint for the word
 * @param txid        Text ID
 * @param torder
 * @param txt         Text
 * @param wid         Word ID
 * @param multi_words
 * @param rtl         Right-to-left text indicator
 * @param ann
 * @returns
 */
export function run_overlib_status_98(
  wblink1: string,
  wblink2: string,
  wblink3: string,
  hints: string,
  txid: number,
  torder: string | number,
  txt: string,
  wid: string | number,
  multi_words: (string | undefined)[],
  rtl: boolean,
  ann: string
): boolean {
  return overlib(
    make_overlib_audio(txt) +
    '<b>' + escape_html_chars_2(hints, ann) + '</b><br/>' +
    make_overlib_link_new_word(txid, torder, wid) + ' | ' +
    make_overlib_link_delete_word(txid, wid) +
    make_overlib_link_new_multiword(txid, torder, multi_words, rtl) + ' <br /> ' +
    make_overlib_link_wb(wblink1, wblink2, wblink3, txt, txid, torder),
    'Word'
  );
}

/**
 * Handle click event on well-known words
 *
 * @param wblink1     First dictionary URL
 * @param wblink2     Second dictionary URL
 * @param wblink3     Google Translate dictionary URL
 * @param hints       Hint for the word
 * @param txid        Text ID
 * @param torder
 * @param txt         Text
 * @param wid         Word ID
 * @param multi_words
 * @param rtl         Right-to-left text indicator
 * @param ann
 * @returns
 */
export function run_overlib_status_99(
  wblink1: string,
  wblink2: string,
  wblink3: string,
  hints: string,
  txid: number,
  torder: string | number,
  txt: string,
  wid: string | number,
  multi_words: (string | undefined)[],
  rtl: boolean,
  ann: string
): boolean {
  return overlib(
    make_overlib_audio(txt) +
    '<b>' + escape_html_chars_2(hints, ann) + '</b><br/> ' +
    make_overlib_link_new_word(txid, torder, wid) + ' | ' +
    make_overlib_link_delete_word(txid, wid) +
    make_overlib_link_new_multiword(txid, torder, multi_words, rtl) + ' <br /> ' +
    make_overlib_link_wb(wblink1, wblink2, wblink3, txt, txid, torder),
    'Word'
  );
}

/**
 * Handle click event on learning words (levels 1 to 5)
 *
 * @param wblink1     First dictionary URL
 * @param wblink2     Second dictionary URL
 * @param wblink3     Google Translate dictionary URL
 * @param hints       Hint for the word
 * @param txid        Text ID
 * @param torder
 * @param txt         Text
 * @param wid         Word ID
 * @param stat
 * @param multi_words
 * @param rtl         Right-to-left text indicator
 * @param ann         Annotation
 * @returns
 */
export function run_overlib_status_1_to_5(
  wblink1: string,
  wblink2: string,
  wblink3: string,
  hints: string,
  txid: number,
  torder: string | number,
  txt: string,
  wid: string | number,
  stat: string | number,
  multi_words: (string | undefined)[],
  rtl: boolean,
  ann: string
): boolean {
  return overlib(
    make_overlib_audio(txt) +
    '<b>' + escape_html_chars_2(hints, ann) + '</b><br/>' +
    make_overlib_link_change_status_all(txid, torder, wid, stat) + ' <br /> ' +
    make_overlib_link_edit_word(txid, torder, wid) + ' | ' +
    make_overlib_link_delete_word(txid, wid) +
    make_overlib_link_new_multiword(txid, torder, multi_words, rtl) + ' <br /> ' +
    make_overlib_link_wb(wblink1, wblink2, wblink3, txt, txid, torder),
    make_overlib_link_edit_word_title(
      'Word &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
      txid, torder, wid
    )
  );
}

/**
 * Handle click event on unknown words.
 *
 * @param wblink1     First dictionary URL
 * @param wblink2     Second dictionary URL
 * @param wblink3     Google Translate dictionary URL
 * @param hints       Hint for the word
 * @param txid        Text ID
 * @param torder
 * @param txt         Text
 * @param multi_words
 * @param rtl         1 if right-to-left language
 * @returns
 */
export function run_overlib_status_unknown(
  wblink1: string,
  wblink2: string,
  wblink3: string,
  hints: string,
  txid: number,
  torder: string | number,
  txt: string,
  multi_words: (string | undefined)[],
  rtl: boolean
): boolean {
  return overlib(
    make_overlib_audio(txt) + '<b>' + escape_html_chars(hints) + '</b><br /> ' +
    make_overlib_link_wellknown_word(txid, torder) + ' <br /> ' +
    make_overlib_link_ignore_word(txid, torder) +
    make_overlib_link_new_multiword(txid, torder, multi_words, rtl) + ' <br /> ' +
    make_overlib_link_wb(wblink1, wblink2, wblink3, txt, txid, torder),
    'New Word'
  );
}

/**
 * Handle click event on a multi-word.
 *
 * @param wblink1     First dictionary URL
 * @param wblink2     Second dictionary URL
 * @param wblink3     Google Translate dictionary URL
 * @param hints       Hint for the word
 * @param txid        Text ID
 * @param torder
 * @param txt         Text
 * @param wid         Word ID
 * @param stat        Word status
 * @param wcnt        Word count
 * @param ann         Annotation
 * @returns
 */
export function run_overlib_multiword(
  wblink1: string,
  wblink2: string,
  wblink3: string,
  hints: string,
  txid: number,
  torder: string | number,
  txt: string,
  wid: string | number,
  stat: string | number,
  wcnt: string,
  ann: string
): boolean {
  return overlib(
    make_overlib_audio(txt) + '<b>' + escape_html_chars_2(hints, ann) + '</b><br /> ' +
    make_overlib_link_change_status_all(txid, torder, wid, stat) + ' <br /> ' +
    make_overlib_link_edit_multiword(txid, torder, wid) + ' | ' +
    make_overlib_link_delete_multiword(txid, wid) + ' <br /> ' +
    make_overlib_link_wb(wblink1, wblink2, wblink3, txt, txid, torder),
    make_overlib_link_edit_multiword_title(
      wcnt.trim() + '-Word-Expression', txid, torder, wid
    )
  );
}

/**
 * Make an overlib dialog so that the user can say if he knows the word or not.
 *
 * @param wblink1 Dictionary 1 URI
 * @param wblink2 Dictionary 2 URI
 * @param wblink3 Google Translate URI
 * @param wid     Word ID
 * @param txt     Word text
 * @param trans   Word translation
 * @param roman   Word romanization
 * @param stat    Word learning status
 * @param sent    Lookup sentence in Google Translate
 * @param todo    If 1, the user should say if he knows the word.
 * @returns An overlib object
 */
export function run_overlib_test(
  wblink1: string,
  wblink2: string,
  wblink3: string,
  wid: string | number,
  txt: string,
  trans: string,
  roman: string,
  stat: string | number,
  sent: string,
  todo: number
): boolean {
  const s = parseInt(String(stat), 10);
  let c = s + 1;
  if (c > 5) c = 5;
  let w = s - 1;
  if (w < 1) w = 1;
  let cc: string = stat + ' ▶ ' + c;
  if (c === s) cc = String(c);
  let ww: string = stat + ' ▶ ' + w;
  if (w === s) ww = String(w);
  let overlib_string = '';
  if (todo === 1) {
    overlib_string += '<center><hr noshade size=1 /><b>';
    if (s >= 1 && s <= 5) {
      overlib_string +=
        make_overlib_link_change_status_test(
          wid,
          1,
          iconHtml('thumb-up', { title: 'Got it!', alt: 'Got it!' }) + ' Got it! [' +
          cc + ']'
        ) +
        '<hr noshade size=1 />' +
        make_overlib_link_change_status_test(
          wid,
          -1,
          iconHtml('thumb', { title: 'Oops!', alt: 'Oops!' }) + ' Oops! [' + ww + ']'
        ) +
        '<hr noshade size=1 />';
    }
    overlib_string +=
      make_overlib_link_change_status_alltest(wid, stat) +
      '</b></center><hr noshade size=1 />';
  }
  overlib_string += '<b>' + escape_html_chars(make_tooltip(txt, trans, roman, String(stat))) +
    '</b><br />' +
    ' <a href="/word/edit-term?wid=' + wid +
    '" target="ro" onclick="showRightFramesPanel();">Edit term</a><br />' +
    createTheDictLink(wblink1, txt, 'Dict1', 'Lookup Term: ') +
    createTheDictLink(wblink2, txt, 'Dict2', '') +
    createTheDictLink(wblink3, txt, 'Trans', '') +
    createTheDictLink(wblink3, sent, 'Trans', '<br />Lookup Sentence:');

  return overlib(overlib_string, 'Got it?');
}

/**
 * Return all multiwords
 *
 * @param txid        Text ID
 * @param torder
 * @param multi_words A list of 8 string elements
 * @param rtl         Right-to-left indicator
 *
 * @return All multiwords
 *
 * @since 2.8.0-fork LTR texts were wrongly displayed
 */
export function make_overlib_link_new_multiword(
  txid: number,
  torder: string | number,
  multi_words: (string | undefined)[],
  rtl: boolean
): string {
  // Quit if all multiwords are '' or undefined
  if (multi_words.every((x) => !x)) return '';
  const output: string[] = [];
  if (rtl) {
    for (let i = 7; i > 0; i--) {
      if (multi_words[i]) {
        output.push(make_overlib_link_create_edit_multiword_rtl(
          i + 2, txid, torder, multi_words[i]!
        ));
      }
    }
  } else {
    for (let i = 0; i < 7; i++) {
      if (multi_words[i]) {
        output.push(make_overlib_link_create_edit_multiword(
          i + 2, txid, torder, multi_words[i]!
        ));
      }
    }
  }
  return ' <br />Expr: ' + output.join(' ') + ' ';
}

/**
 * Make link to translations through dictionaries or all sentences lookup.
 *
 * @param wblink1 Dictionary 1 URI
 * @param wblink2 Dictionary 2 URI
 * @param wblink3 Google Translate URI
 * @param txt     Word string
 * @param txid    Text ID
 * @param torder
 * @returns
 */
export function make_overlib_link_wb(
  wblink1: string,
  wblink2: string,
  wblink3: string,
  txt: string,
  txid: number,
  torder: string | number
): string {
  let s =
    createTheDictLink(wblink1, txt, 'Dict1', 'Lookup Term: ') +
    createTheDictLink(wblink2, txt, 'Dict2', '') +
    createTheDictLink(wblink3, txt, 'Trans', '');
  if (Number(torder) > 0 && txid > 0) {
    s += '<br />Lookup Sentence: ' +
      createSentLookupLink(Number(torder), txid, wblink3, 'Trans');
  }
  return s;
}

/**
 * Create a list of links for dictionary translation.
 *
 * @param wblink1 Dictionary 1 URI
 * @param wblink2 Dictionary 2 URI
 * @param wblink3 Google Translate URI
 * @param txt     Word string
 * @param txid    Text ID
 * @param torder
 * @returns HTML-formatted list of dictionaries link, and sentece link
 */
export function make_overlib_link_wbnl(
  wblink1: string,
  wblink2: string,
  wblink3: string,
  txt: string,
  txid: number,
  torder: string | number
): string {
  let s =
    createTheDictLink(wblink1, txt, 'Dict1', 'Term: ') +
    createTheDictLink(wblink2, txt, 'Dict2', '') +
    createTheDictLink(wblink3, txt, 'Trans', '');
  if (Number(torder) > 0 && txid > 0) {
    s += ' | Sentence: ' + createSentLookupLink(Number(torder), txid, wblink3, 'Trans');
  }
  return s;
}

/**
 * Create link to dictionaries.
 *
 * @param wblink1 Dictionary 1 URI
 * @param wblink2 Dictionary 2 URI
 * @param wblink3 Google Translate URI
 * @param txt     Word string
 * @param sent    Complete sentence
 * @returns HTML-formatted list of links
 */
export function make_overlib_link_wbnl2(
  wblink1: string,
  wblink2: string,
  wblink3: string,
  txt: string,
  sent: string
): string {
  let s =
    createTheDictLink(wblink1, txt, 'Dict1', 'Term: ') +
    createTheDictLink(wblink2, txt, 'Dict2', '') +
    createTheDictLink(wblink3, txt, 'Trans', '');
  if (sent !== '') {
    s += createTheDictLink(wblink3, sent, 'Trans', ' | Sentence:');
  }
  return s;
}

/**
 * Change the status of a word multiple time.
 *
 * @param txid Text ID
 * @param torder
 * @param wid Word ID
 * @param oldstat Old word status
 * @returns Multiple links for a new word status.
 */
export function make_overlib_link_change_status_all(
  txid: number,
  torder: string | number,
  wid: string | number,
  oldstat: string | number
): string {
  let result = 'St: ';
  for (let newstat = 1; newstat <= 5; newstat++) {
    result += make_overlib_link_change_status(txid, torder, wid, oldstat, newstat);
  }
  result += make_overlib_link_change_status(txid, torder, wid, oldstat, 99);
  result += make_overlib_link_change_status(txid, torder, wid, oldstat, 98);
  return result;
}

/**
 * Return a list of links to change word status
 *
 * @param wid     Word ID
 * @param oldstat Current status of the word
 * @returns An HTML-formatted list of links.
 */
export function make_overlib_link_change_status_alltest(
  wid: string | number,
  oldstat: string | number
): string {
  let result = '';
  for (let newstat = 1; newstat <= 5; newstat++) {
    result += make_overlib_link_change_status_test2(wid, oldstat, newstat);
  }
  result += make_overlib_link_change_status_test2(wid, oldstat, 99);
  result += make_overlib_link_change_status_test2(wid, oldstat, 98);
  return result;
}

/**
 * Return a link to change the status of a word.
 *
 * @param txid    Text ID
 * @param torder
 * @param wid     Word ID
 * @param oldstat Old word status
 * @param newstat New word status
 * @returns HTML formatted link to change word status
 */
export function make_overlib_link_change_status(
  txid: number,
  torder: string | number,
  wid: string | number,
  oldstat: string | number,
  newstat: number
): string {
  if (Number(oldstat) === newstat) {
    return '<span title="' +
      getStatusName(oldstat) + '">◆</span>';
  }
  return ' <a href="set_word_status.php?tid=' + txid +
    '&amp;ord=' + torder +
    '&amp;wid=' + wid +
    '&amp;status=' + newstat + '" target="ro" onclick="showRightFramesPanel();">' +
    '<span title="' + getStatusName(newstat) + '">[' +
    getStatusAbbr(newstat) + ']</span></a> ';
}

/**
 * Prepare an HTML-formated string containing the new statuses choices
 *
 * @param wid     ID of the word
 * @param oldstat Old status
 * @param newstat New status
 * @returns HTML-formatted link
 */
export function make_overlib_link_change_status_test2(
  wid: string | number,
  oldstat: string | number,
  newstat: number
): string {
  let output = ' <a href="set_test_status.php?wid=' + wid +
    '&amp;status=' + newstat + '&amp;ajax=1" target="ro" onclick="showRightFramesPanel();">' +
    '<span title="' + getStatusName(newstat) + '">[';
  output += (Number(oldstat) === newstat) ? '◆' : getStatusAbbr(newstat);
  output += ']</span></a> ';
  return output;
}

/**
 * Make a link for a word status change
 *
 * @param wid       ID of the word
 * @param plusminus Amplitude of the change (normally 1 or -1)
 * @param text      Text to be embed
 *
 * @returns A tag containing formatted text
 */
export function make_overlib_link_change_status_test(
  wid: string | number,
  plusminus: number,
  text: string
): string {
  return ' <a href="set_test_status.php?wid=' + wid +
    '&amp;stchange=' + plusminus +
    '&amp;ajax=1" target="ro" onclick="showRightFramesPanel();' +
    (plusminus > 0 ? 'successSound()' : 'failureSound()') + ';">' +
    text + '</a> ';
}

/**
 * Make a link to learn a new word.
 *
 *
 * @param txid Text ID
 * @param torder
 * @param wid Word ID
 *
 * @returns
 */
export function make_overlib_link_new_word(
  txid: number,
  torder: string | number,
  wid: string | number
): string {
  return ' <a href="/word/edit?tid=' + txid +
    '&amp;ord=' + torder +
    '&amp;wid=' + wid + '" target="ro" onclick="showRightFramesPanel();">Learn term</a> ';
}

/**
 * Open the multi-word edit modal.
 * Exposed to window for use in onclick handlers.
 *
 * @param textId   Text ID
 * @param position Position in text
 * @param text     Multi-word text (for new expressions)
 * @param wordCount Number of words
 * @param wordId   Word ID (for existing expressions)
 */
export function openMultiWordModal(
  textId: number,
  position: number,
  text: string,
  wordCount: number,
  wordId?: number
): void {
  const store = Alpine.store('multiWordForm') as MultiWordFormStoreState;
  store.loadForEdit(textId, position, text, wordCount, wordId);
}

// Expose to window for onclick handlers in HTML strings
declare global {
  interface Window {
    openMultiWordModal: typeof openMultiWordModal;
  }
}
window.openMultiWordModal = openMultiWordModal;

/**
 * Create a link to edit a multiword.
 *
 * @param txid Text ID
 * @param torder
 * @param wid Word ID
 * @returns
 */
export function make_overlib_link_edit_multiword(
  txid: number,
  torder: string | number,
  wid: string | number
): string {
  return ` <a href="#" onclick="openMultiWordModal(${txid}, ${torder}, '', 0, ${wid}); return false;">Edit term</a> `;
}

/**
 * Create an overlib title for a multiword edition.
 *
 * @param text
 * @param txid
 * @param torder
 * @param wid
 * @returns
 */
export function make_overlib_link_edit_multiword_title(
  text: string,
  txid: number,
  torder: string | number,
  wid: string | number
): string {
  return `<a style="color:yellow" href="#" onclick="openMultiWordModal(${txid}, ${torder}, '', 0, ${wid}); return false;">${text}</a>`;
}

/**
 * Create or edit a multiword with overlib.
 *
 * @param len    Number of words in the multi-word
 * @param txid   Text ID
 * @param torder
 * @param txt    Multi-word text
 * @returns
 */
export function make_overlib_link_create_edit_multiword(
  len: number,
  txid: number,
  torder: string | number,
  txt: string
): string {
  // Escape the text for use in JavaScript string
  const escapedTxt = txt.replace(/'/g, "\\'").replace(/"/g, '\\"');
  return ` <a href="#" onclick="openMultiWordModal(${txid}, ${torder}, '${escapedTxt}', ${len}); return false;">${len}..${escape_html_chars(txt.substring(2).trim())}</a> `;
}

/**
 * Create or edit a right-to-left multiword with overlib.
 *
 * @param len    Number of words in the multi-word
 * @param txid   Text ID
 * @param torder
 * @param txt    Multi-word text
 * @returns
 */
export function make_overlib_link_create_edit_multiword_rtl(
  len: number,
  txid: number,
  torder: string | number,
  txt: string
): string {
  // Escape the text for use in JavaScript string
  const escapedTxt = txt.replace(/'/g, "\\'").replace(/"/g, '\\"');
  return ` <a dir="rtl" href="#" onclick="openMultiWordModal(${txid}, ${torder}, '${escapedTxt}', ${len}); return false;">${len}..${escape_html_chars(txt.substring(2).trim())}</a> `;
}

/**
 * Make a link to edit a word, displaying "Edit term"
 *
 * @param txid
 * @param torder
 * @param wid
 * @returns
 */
export function make_overlib_link_edit_word(
  txid: number,
  torder: string | number,
  wid: string | number
): string {
  const url = '/word/edit?tid=' + txid +
    '&amp;ord=' + torder +
    '&amp;wid=' + wid;
  return ' <a href="' + url +
    ' " target="ro" onclick="showRightFramesPanel()">Edit term</a> ';
}

/**
 * Make a link to edit a word for an overlib title, displaying the word's text.
 *
 * @param text Word text
 * @param txid Text ID
 * @param torder
 * @param wid Word ID
 * @returns HTML-formatted link
 */
export function make_overlib_link_edit_word_title(
  text: string,
  txid: number,
  torder: string | number,
  wid: string | number
): string {
  return '<a style="color:yellow" href="/word/edit?tid=' +
    txid + '&amp;ord=' + torder +
    '&amp;wid=' + wid + '" target="ro" onclick="showRightFramesPanel();">' +
    text + '</a>';
}

/**
 * Make a link to delete a word with overlib.
 *
 * @param txid Text ID
 * @param wid  Word ID
 * @returns HTML-formatted link.
 */
export function make_overlib_link_delete_word(
  txid: number,
  wid: string | number
): string {
  return ' <a onclick="showRightFramesPanel(); return confirmDelete();" ' +
    'href="delete_word.php?wid=' + wid + '&amp;tid=' + txid +
    '" target="ro">Delete term</a> ';
}

/**
 * Make a link to delete a multiword.
 *
 * @param txid Text ID
 * @param wid  Word ID
 * @returns HTML-formatted string
 */
export function make_overlib_link_delete_multiword(
  txid: number,
  wid: string | number
): string {
  return ' <a onclick="showRightFramesPanel(); return confirmDelete();" ' +
    'href="delete_mword.php?wid=' + wid + '&amp;tid=' + txid +
    '" target="ro">Delete term</a> ';
}

/**
 * Return a link to a word well-known.
 *
 * @param txid
 * @param torder
 * @returns HTML link to mark the word well knwown
 */
export function make_overlib_link_wellknown_word(
  txid: number,
  torder: string | number
): string {
  return ' <a href="insert_word_wellknown.php?tid=' +
    txid + '&amp;ord=' + torder +
    '" target="ro" onclick="showRightFramesPanel();">I know this term well</a> ';
}

/**
 * Return a link to ignore a word.
 *
 * @param txid
 * @param torder
 * @returns HTML string to ignore the word
 */
export function make_overlib_link_ignore_word(
  txid: number,
  torder: string | number
): string {
  return ' <a href="insert_word_ignore.php?tid=' + txid +
    '&amp;ord=' + torder +
    '" target="ro" onclick="showRightFramesPanel();">Ignore this term</a> ';
}

/**
 * Create a clickable button to read a word aloud.
 *
 * @param txt Word to say
 * @return HTML-formatted clickable icon
 */
export function make_overlib_audio(txt: string): string {
  const icon = createIcon('speaker-volume', {
    title: 'Click to read!',
    clickable: true
  });
  icon.style.cursor = 'pointer';
  icon.setAttribute(
    'onclick',
    "speechDispatcher('" + escape_html_chars(txt) + "', '" + LWT_DATA.language.id + "')"
  );
  return icon.outerHTML;
}

