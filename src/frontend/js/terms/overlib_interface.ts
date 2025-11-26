/**
 * LWT Javascript functions
 *
 * @author  HugoFara <HugoFara@users.noreply.github.com>
 * @license Unlicense <http://unlicense.org/>
 */

import {
  escape_html_chars,
  escape_html_chars_2,
  make_tooltip,
  getStatusName,
  getStatusAbbr,
  createTheDictLink,
  createSentLookupLink,
  getLangFromDict
} from '../legacy/pgm';
import { speechDispatcher } from '../legacy/user_interactions';

// Declare external functions from overlib library
declare function overlib(content: string, ...args: unknown[]): boolean;
declare const CAPTION: unknown;

// Declare external functions
declare function showRightFrames(url?: string): void;
declare function confirmDelete(): boolean;
declare function successSound(): void;
declare function failureSound(): void;

// Type for LWT_DATA global
interface LwtLanguage {
  id: number;
}

interface LwtDataGlobal {
  language: LwtLanguage;
}

declare const LWT_DATA: LwtDataGlobal;

/**************************************************************
Global variables for OVERLIB
***************************************************************/

/**
 * OVERLIB text font
 */
export const ol_textfont = '"Lucida Grande",Arial,sans-serif,STHeiti,"Arial Unicode MS",MingLiu';
export const ol_textsize = 3;
export const ol_sticky = 1;
export const ol_captionfont = '"Lucida Grande",Arial,sans-serif,STHeiti,"Arial Unicode MS",MingLiu';
export const ol_captionsize = 3;
export const ol_width = 260;
export const ol_close = 'Close';
export const ol_offsety = 30;
export const ol_offsetx = 3;
export const ol_fgcolor = '#FFFFE8';
export const ol_closecolor = '#FFFFFF';

/**************************************************************
 * Helper functions for overlib
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
  const lang = getLangFromDict(wblink3);
  return overlib(
    make_overlib_audio(txt, lang) +
    '<b>' + escape_html_chars_2(hints, ann) + '</b><br/>' +
    make_overlib_link_new_word(txid, torder, wid) + ' | ' +
    make_overlib_link_delete_word(txid, wid) +
    make_overlib_link_new_multiword(txid, torder, multi_words, rtl) + ' <br /> ' +
    make_overlib_link_wb(wblink1, wblink2, wblink3, txt, txid, torder),
    CAPTION,
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
  const lang = getLangFromDict(wblink3);
  return overlib(
    make_overlib_audio(txt, lang) +
    '<b>' + escape_html_chars_2(hints, ann) + '</b><br/> ' +
    make_overlib_link_new_word(txid, torder, wid) + ' | ' +
    make_overlib_link_delete_word(txid, wid) +
    make_overlib_link_new_multiword(txid, torder, multi_words, rtl) + ' <br /> ' +
    make_overlib_link_wb(wblink1, wblink2, wblink3, txt, txid, torder),
    CAPTION,
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
 * @param ann         Unused
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
  _ann: string
): boolean {
  const lang = getLangFromDict(wblink3);
  return overlib(
    '<div>' + make_overlib_audio(txt, lang) + '<span>(Read)</span></div>' +
    make_overlib_link_change_status_all(txid, torder, wid, stat) + ' <br /> ' +
    make_overlib_link_edit_word(txid, torder, wid) + ' | ' +
    make_overlib_link_delete_word(txid, wid) +
    make_overlib_link_new_multiword(txid, torder, multi_words, rtl) + ' <br /> ' +
    make_overlib_link_wb(wblink1, wblink2, wblink3, txt, txid, torder),
    CAPTION,
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
  const lang = getLangFromDict(wblink3);
  return overlib(
    make_overlib_audio(txt, lang) + '<b>' + escape_html_chars(hints) + '</b><br /> ' +
    make_overlib_link_wellknown_word(txid, torder) + ' <br /> ' +
    make_overlib_link_ignore_word(txid, torder) +
    make_overlib_link_new_multiword(txid, torder, multi_words, rtl) + ' <br /> ' +
    make_overlib_link_wb(wblink1, wblink2, wblink3, txt, txid, torder),
    CAPTION,
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
  const lang = getLangFromDict(wblink3);
  return overlib(
    make_overlib_audio(txt, lang) + '<b>' + escape_html_chars_2(hints, ann) + '</b><br /> ' +
    make_overlib_link_change_status_all(txid, torder, wid, stat) + ' <br /> ' +
    make_overlib_link_edit_multiword(txid, torder, wid) + ' | ' +
    make_overlib_link_delete_multiword(txid, wid) + ' <br /> ' +
    make_overlib_link_wb(wblink1, wblink2, wblink3, txt, txid, torder),
    CAPTION,
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
 * @param oldstat Old status, unused
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
  todo: number,
  _oldstat: unknown
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
          '<img src="icn/thumb-up.png" title="Got it!" alt="Got it!" /> Got it! [' +
          cc + ']'
        ) +
        '<hr noshade size=1 />' +
        make_overlib_link_change_status_test(
          wid,
          -1,
          '<img src="icn/thumb.png" title="Oops!" alt="Oops!" /> Oops! [' + ww + ']'
        ) +
        '<hr noshade size=1 />';
    }
    overlib_string +=
      make_overlib_link_change_status_alltest(wid, stat) +
      '</b></center><hr noshade size=1 />';
  }
  overlib_string += '<b>' + escape_html_chars(make_tooltip(txt, trans, roman, String(stat))) +
    '</b><br />' +
    ' <a href="edit_tword.php?wid=' + wid +
    '" target="ro" onclick="showRightFrames();">Edit term</a><br />' +
    createTheDictLink(wblink1, txt, 'Dict1', 'Lookup Term: ') +
    createTheDictLink(wblink2, txt, 'Dict2', '') +
    createTheDictLink(wblink3, txt, 'Trans', '') +
    createTheDictLink(wblink3, sent, 'Trans', '<br />Lookup Sentence:');

  return overlib(overlib_string, CAPTION, 'Got it?');
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
    '&amp;status=' + newstat + '" target="ro" onclick="showRightFrames();">' +
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
    '&amp;status=' + newstat + '&amp;ajax=1" target="ro" onclick="showRightFrames();">' +
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
    '&amp;ajax=1" target="ro" onclick="showRightFrames();' +
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
  return ' <a href="edit_word.php?tid=' + txid +
    '&amp;ord=' + torder +
    '&amp;wid=' + wid + '" target="ro" onclick="showRightFrames();">Learn term</a> ';
}

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
  return ' <a href="edit_mword.php?tid=' + txid +
    '&amp;ord=' + torder +
    '&amp;wid=' + wid + '" target="ro" onclick="showRightFrames();">Edit term</a> ';
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
  return '<a style="color:yellow" href="edit_mword.php?tid=' + txid +
    '&amp;ord=' + torder +
    '&amp;wid=' + wid + '" target="ro" onclick="showRightFrames();">' +
    text + '</a>';
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
  return ' <a href="edit_mword.php?tid=' + txid +
    '&amp;ord=' + torder +
    '&amp;txt=' + txt +
    '" target="ro" onclick="showRightFrames();">' +
    len + '..' + escape_html_chars(txt.substring(2).trim()) + '</a> ';
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
  return ' <a dir="rtl" href="edit_mword.php?tid=' + txid +
    '&amp;ord=' + torder +
    '&amp;txt=' + txt +
    '" target="ro" onclick="showRightFrames();">' +
    len + '..' + escape_html_chars(txt.substring(2).trim()) + '</a> ';
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
  const url = 'edit_word.php?tid=' + txid +
    '&amp;ord=' + torder +
    '&amp;wid=' + wid;
  return ' <a href="' + url +
    ' " target="ro" onclick="showRightFrames()">Edit term</a> ';
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
  return '<a style="color:yellow" href="edit_word.php?tid=' +
    txid + '&amp;ord=' + torder +
    '&amp;wid=' + wid + '" target="ro" onclick="showRightFrames();">' +
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
  return ' <a onclick="showRightFrames(); return confirmDelete();" ' +
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
  return ' <a onclick="showRightFrames(); return confirmDelete();" ' +
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
    '" target="ro" onclick="showRightFrames();">I know this term well</a> ';
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
    '" target="ro" onclick="showRightFrames();">Ignore this term</a> ';
}

/**
 * Create a clickable button to read a word aloud.
 *
 * @param txt  Word to say
 * @param lang Language name (two letters or four letters separated with a
 *             caret)
 * @return HTML-formatted clickable icon
 */
export function make_overlib_audio(txt: string, lang: string): string {
  const img = document.createElement('img');
  img.title = 'Click to read!';
  img.src = 'icn/speaker-volume.png';
  img.style.cursor = 'pointer';
  img.setAttribute(
    'onclick',
    "speechDispatcher('" + escape_html_chars(txt) + "', '" + LWT_DATA.language.id + "')"
  );
  return img.outerHTML;
}

// Expose globally for backward compatibility with PHP templates
if (typeof window !== 'undefined') {
  const w = window as unknown as Record<string, unknown>;
  // Global variables
  w.ol_textfont = ol_textfont;
  w.ol_textsize = ol_textsize;
  w.ol_sticky = ol_sticky;
  w.ol_captionfont = ol_captionfont;
  w.ol_captionsize = ol_captionsize;
  w.ol_width = ol_width;
  w.ol_close = ol_close;
  w.ol_offsety = ol_offsety;
  w.ol_offsetx = ol_offsetx;
  w.ol_fgcolor = ol_fgcolor;
  w.ol_closecolor = ol_closecolor;
  // Functions
  w.run_overlib_status_98 = run_overlib_status_98;
  w.run_overlib_status_99 = run_overlib_status_99;
  w.run_overlib_status_1_to_5 = run_overlib_status_1_to_5;
  w.run_overlib_status_unknown = run_overlib_status_unknown;
  w.run_overlib_multiword = run_overlib_multiword;
  w.run_overlib_test = run_overlib_test;
  w.make_overlib_link_new_multiword = make_overlib_link_new_multiword;
  w.make_overlib_link_wb = make_overlib_link_wb;
  w.make_overlib_link_wbnl = make_overlib_link_wbnl;
  w.make_overlib_link_wbnl2 = make_overlib_link_wbnl2;
  w.make_overlib_link_change_status_all = make_overlib_link_change_status_all;
  w.make_overlib_link_change_status_alltest = make_overlib_link_change_status_alltest;
  w.make_overlib_link_change_status = make_overlib_link_change_status;
  w.make_overlib_link_change_status_test2 = make_overlib_link_change_status_test2;
  w.make_overlib_link_change_status_test = make_overlib_link_change_status_test;
  w.make_overlib_link_new_word = make_overlib_link_new_word;
  w.make_overlib_link_edit_multiword = make_overlib_link_edit_multiword;
  w.make_overlib_link_edit_multiword_title = make_overlib_link_edit_multiword_title;
  w.make_overlib_link_create_edit_multiword = make_overlib_link_create_edit_multiword;
  w.make_overlib_link_create_edit_multiword_rtl = make_overlib_link_create_edit_multiword_rtl;
  w.make_overlib_link_edit_word = make_overlib_link_edit_word;
  w.make_overlib_link_edit_word_title = make_overlib_link_edit_word_title;
  w.make_overlib_link_delete_word = make_overlib_link_delete_word;
  w.make_overlib_link_delete_multiword = make_overlib_link_delete_multiword;
  w.make_overlib_link_wellknown_word = make_overlib_link_wellknown_word;
  w.make_overlib_link_ignore_word = make_overlib_link_ignore_word;
  w.make_overlib_audio = make_overlib_audio;
}
