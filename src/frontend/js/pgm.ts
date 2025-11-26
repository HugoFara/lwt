/**
 * LWT Javascript functions
 *
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   1.6.16-fork
 *
 * "Learning with Texts" (LWT) is free and unencumbered software
 * released into the PUBLIC DOMAIN.
 */

import type { WordStatus } from './types/globals';

// Declare external functions that are defined elsewhere
declare function markClick(): void;
declare function showRightFrames(url1?: string, url2?: string): void;

/**************************************************************
 * Other JS utility functions
 **************************************************************/

/**
 * Return the name of a given status.
 *
 * @param status Status number (int<1, 5>|98|99)
 * @returns Status name
 */
export function getStatusName(status: number | string): string {
  const statuses: Record<string, WordStatus> = window.STATUSES || {};
  return statuses[status] ? statuses[status].name : 'Unknown';
}

/**
 * Return the abbreviation of a status
 *
 * @param status Status number (int<1, 5>|98|99)
 * @returns Abbreviation
 */
export function getStatusAbbr(status: number | string): string {
  const statuses: Record<string, WordStatus> = window.STATUSES || {};
  return statuses[status] ? statuses[status].abbr : '?';
}

/**
 * Translate a sentence.
 *
 * @param url     Translation URL with "{term}" marking the interesting term
 * @param sentctl Textarea contaning sentence
 */
export function translateSentence(url: string, sentctl: HTMLTextAreaElement | undefined): void {
  if (sentctl !== undefined && url !== '') {
    const text = sentctl.value;
    if (typeof text === 'string') {
      showRightFrames(undefined, createTheDictUrl(url, text.replace(/[{}]/g, '')));
    }
  }
}

/**
 * Translate a sentence.
 *
 * @param url     Translation URL with "{term}" marking the interesting term
 * @param sentctl Textarea contaning sentence
 */
export function translateSentence2(url: string, sentctl: HTMLTextAreaElement | undefined): void {
  if (typeof sentctl !== 'undefined' && url !== '') {
    const text = sentctl.value;
    if (typeof text === 'string') {
      const finalurl = createTheDictUrl(url, text.replace(/[{}]/g, ''));
      owin(finalurl);
    }
  }
}

/**
 * Open a new window with the translation of the word.
 *
 * @param url     Dictionary URL
 * @param wordctl Textarea containing word to translate.
 */
export function translateWord(url: string, wordctl: HTMLInputElement | undefined): void {
  if (wordctl !== undefined && url !== '') {
    const text = wordctl.value;
    if (typeof text === 'string') {
      showRightFrames(undefined, createTheDictUrl(url, text));
    }
  }
}

/**
 * Open a new window with the translation of the word.
 *
 * @param url     Dictionary URL
 * @param wordctl Textarea containing word to translate.
 */
export function translateWord2(url: string, wordctl: HTMLInputElement | undefined): void {
  if (wordctl !== undefined && url !== '') {
    const text = wordctl.value;
    if (typeof text === 'string') {
      owin(createTheDictUrl(url, text));
    }
  }
}

/**
 * Open a new window with the translation of the word.
 *
 * @param url Dictionary URL
 * @param word Word to translate.
 */
export function translateWord3(url: string, word: string): void {
  owin(createTheDictUrl(url, word));
}

/**
 * Get the language name from the Google Translate URL.
 *
 * @param wblink3 Google Translate Dictionary URL
 * @returns Language name
 *
 * @since 2.7.0 Also works with a LibreTranslate URL
 */
export function getLangFromDict(wblink3: string): string {
  if (wblink3.trim() === '') {
    return '';
  }
  // Replace pop-up marker '*'
  if (wblink3.startsWith('*')) {
    wblink3 = wblink3.substring(1);
  }
  if (wblink3.startsWith('trans.php') || wblink3.startsWith('ggl.php')) {
    wblink3 = 'http://' + wblink3;
  }
  const dictUrl = new URL(wblink3);
  const urlParams = dictUrl.searchParams;
  if (urlParams.get('lwt_translator') === 'libretranslate') {
    return urlParams.get('source') || '';
  }
  // Fallback to Google Translate
  return urlParams.get('sl') || '';
}

/**
 * Return a tooltip, a short string describing the word (word, translation,
 * romanization and learning status)
 *
 * @param word   The word
 * @param trans  Translation of the word
 * @param roman  Romanized version
 * @param status Learning status of the word
 * @returns Tooltip for this word
 */
export function make_tooltip(word: string, trans: string, roman: string, status: number | string): string {
  const nl = '\x0d';
  let title = word;
  if (roman !== '') {
    if (title !== '') title += nl;
    title += '▶ ' + roman;
  }
  if (trans !== '' && trans !== '*') {
    if (title !== '') title += nl;
    title += '▶ ' + trans;
  }
  if (title !== '') title += nl;
  title += '▶ ' + getStatusName(status) + ' [' +
    getStatusAbbr(status) + ']';
  return title;
}

/**
 * Escape the HTML characters, with an eventual annotation
 *
 * @param title String to be escaped
 * @param ann   An annotation to show in red
 * @returns Escaped string
 */
export function escape_html_chars_2(title: string, ann: string): string {
  if (ann !== '') {
    const ann2 = escape_html_chars(ann);
    return escape_html_chars(title).replace(ann2,
      '<span style="color:red">' + ann2 + '</span>');
  }
  return escape_html_chars(title);
}

/**
 * Open a window.
 *
 * @param url URL of the window
 */
export function owin(url: string): Window | null {
  return window.open(
    url,
    'dictwin',
    'width=800, height=400, scrollbars=yes, menubar=no, resizable=yes, status=no'
  );
}

/**
 * Open a window in edit mode.
 *
 * @param url Window URL
 */
export function oewin(url: string): Window | null {
  return window.open(
    url,
    'editwin',
    'width=800, height=600, scrollbars=yes, menubar=no, resizable=yes, status=no'
  );
}

/**
 * Create a dictionary URL.
 *
 * JS alter ego of the createTheDictLink PHP function.
 *
 * Case 1: url without any ### or "lwt_term": append term
 * Case 2: url with one ### or "lwt_term": substitute term
 *
 * @param u Dictionary URL
 * @param w Term to be inserted in the URL
 * @returns A link to external dictionary to get a translation of the word
 *
 * @since 2.6.0-fork Internals rewrote, do no longer use PHP code.
 *                   The option putting encoding between ###enc### does no
 *                   longer work. It is deprecated and will be removed.
 * @since 2.7.0-fork Using "###" is deprecated, "lwt_term" recommended instead
 */
export function createTheDictUrl(u: string, w: string): string {
  const url = u.trim();
  const trm = w.trim();
  const term_elem = url.match(/lwt_term|###/);
  // No ###/lwt_term found
  if (term_elem === null) {
    return url + encodeURIComponent(trm);
  }
  const pos = url.indexOf(term_elem[0]);
  // ###/lwt_term found
  const pos2 = url.indexOf('###', pos + 1);
  if (pos2 === -1) {
    // 1 ###/lwt_term found
    return url.replace(term_elem[0], trm === '' ? '+' : encodeURIComponent(trm));
  }
  // 2 ### found
  // Get encoding
  const enc = url.substring(
    pos + term_elem[0].length, pos2 - pos - term_elem[0].length
  ).trim();
  console.warn(
    "Trying to use encoding '" + enc + "'. This feature is abandonned since " +
    '2.6.0-fork. Using default UTF-8.'
  );
  let output = url.substring(0, pos) + encodeURIComponent(trm);
  if (pos2 + 3 < url.length) {
    output += url.substring(pos2 + 3);
  }
  return output;
}

/**
 * Create an HTML link for a dictionary.
 *
 * @param u Dictionary URL
 * @param w Word or sentence to be translated
 * @param t Text to display
 * @param b Some other text to display before the link
 * @returns HTML-formatted link
 */
export function createTheDictLink(u: string, w: string, t: string, b: string): string {
  let url = u.trim();
  let popup = false;
  const trm = w.trim();
  const txt = t.trim();
  const txtbefore = b.trim();
  let r = '';
  if (url === '' || txt === '') {
    return r;
  }
  if (url.startsWith('*')) {
    url = url.substring(1);
    popup = true;
  }
  try {
    const final_url = new URL(url);
    popup = popup || final_url.searchParams.has('lwt_popup');
  } catch (err) {
    if (!(err instanceof TypeError)) {
      throw err;
    }
  }
  if (popup) {
    r = ' ' + txtbefore +
      ' <span class="click" onclick="owin(\'' +
      createTheDictUrl(url, escape_apostrophes(trm)) +
      '\');">' + txt + '</span> ';
  } else {
    r = ' ' + txtbefore +
      ' <a href="' + createTheDictUrl(url, trm) +
      '" target="ru" onclick="showRightFrames();">' + txt + '</a> ';
  }
  return r;
}

/**
 * Create a sentence lookup link.
 *
 * @param torder Text order
 * @param txid   Text ID
 * @param url    Translator URL
 * @param txt    Word text
 * @returns HTML-formatted link.
 */
export function createSentLookupLink(torder: number, txid: number, url: string, txt: string): string {
  url = url.trim();
  txt = txt.trim();
  let r = '';
  let popup = false;
  let external = false;
  const target_url = 'trans.php?x=1&i=' + torder + '&t=' + txid;
  if (url === '' || txt === '') {
    return r;
  }
  if (url.startsWith('*')) {
    url = url.substring(1);
    popup = true;
  }
  try {
    const final_url = new URL(url);
    popup = popup || final_url.searchParams.has('lwt_popup');
    external = true;
  } catch (err) {
    if (!(err instanceof TypeError)) {
      throw err;
    }
  }
  if (popup) {
    return ' <span class="click" onclick="owin(\'' + target_url + '\');">' +
      txt + '</span> ';
  }
  if (external) {
    return ' <a href="' + target_url + '" target="ru" onclick="showRightFrames();">' +
      txt + '</a> ';
  }
  return r;
}

/**
 * Replace html characters with encodings
 *
 * See https://stackoverflow.com/questions/1787322/what-is-the-htmlspecialchars-equivalent-in-javascript
 *
 * @param s String to be escaped
 * @returns Escaped string
 */
export function escape_html_chars(s: string): string {
  const map: Record<string, string> = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
    '\x0d': '<br />' // This one inserts HTML, delete? (2.9.0)
  };

  return s.replace(/[&<>"'\x0d]/g, function (m) { return map[m]; });
}

/**
 * Escape only single apostrophe ("'") from string
 *
 * @param s String to be escaped
 * @returns Escaped string
 */
export function escape_apostrophes(s: string): string {
  return s.replace(/'/g, '\\\'');
}

export function selectToggle(toggle: boolean, form: string): void {
  const myForm = document.forms[form as unknown as number] as HTMLFormElement;
  for (let i = 0; i < myForm.length; i++) {
    const element = myForm.elements[i] as HTMLInputElement;
    if (toggle) {
      element.checked = true;
    } else {
      element.checked = false;
    }
  }
  markClick();
}

interface FormWithData extends HTMLFormElement {
  data: HTMLInputElement;
}

export function multiActionGo(f: FormWithData | undefined, sel: HTMLSelectElement | undefined): void {
  if (f !== undefined && sel !== undefined) {
    const v = sel.value;
    const t = sel.options[sel.selectedIndex].text;
    if (typeof v === 'string') {
      if (v === 'addtag' || v === 'deltag') {
        let notok = true;
        let answer: string | null = '';
        while (notok) {
          answer = prompt(
            '*** ' + t + ' ***' +
            '\n\n*** ' + $('input.markcheck:checked').length +
            ' Record(s) will be affected ***' +
            '\n\nPlease enter one tag (20 char. max., no spaces, no commas -- ' +
            'or leave empty to cancel:',
            answer || ''
          );
          if (answer === null) answer = '';
          if (answer.indexOf(' ') > 0 || answer.indexOf(',') > 0) {
            alert('Please no spaces or commas!');
          } else if (answer.length > 20) {
            alert('Please no tags longer than 20 char.!');
          } else {
            notok = false;
          }
        }
        if (answer !== '') {
          f.data.value = answer;
          f.submit();
        }
      } else if (
        v === 'del' || v === 'smi1' || v === 'spl1' || v === 's1' || v === 's5' ||
        v === 's98' || v === 's99' || v === 'today' || v === 'delsent' ||
        v === 'lower' || v === 'cap'
      ) {
        const answer = confirm(
          '*** ' + t + ' ***\n\n*** ' + $('input.markcheck:checked').length +
          ' Record(s) will be affected ***\n\nAre you sure?'
        );
        if (answer) {
          f.submit();
        }
      } else {
        f.submit();
      }
    }
    sel.value = '';
  }
}

export function allActionGo(f: FormWithData | undefined, sel: HTMLSelectElement | undefined, n: number): void {
  if (typeof f !== 'undefined' && typeof sel !== 'undefined') {
    const v = sel.value;
    const t = sel.options[sel.selectedIndex].text;
    if (typeof v === 'string') {
      if (v === 'addtagall' || v === 'deltagall') {
        let notok = true;
        let answer: string | null = '';
        while (notok) {
          answer = prompt(
            'THIS IS AN ACTION ON ALL RECORDS\n' +
            'ON ALL PAGES OF THE CURRENT QUERY!\n\n' +
            '*** ' + t + ' ***\n\n*** ' + n + ' Record(s) will be affected ***\n\n' +
            'Please enter one tag (20 char. max., no spaces, no commas -- ' +
            'or leave empty to cancel:',
            answer || ''
          );
          if (answer === null) answer = '';
          if (answer.indexOf(' ') > 0 || answer.indexOf(',') > 0) {
            alert('Please no spaces or commas!');
          } else if (answer.length > 20) {
            alert('Please no tags longer than 20 char.!');
          } else {
            notok = false;
          }
        }
        if (answer !== '') {
          f.data.value = answer;
          f.submit();
        }
      } else if (
        v === 'delall' || v === 'smi1all' || v === 'spl1all' || v === 's1all' ||
        v === 's5all' || v === 's98all' || v === 's99all' || v === 'todayall' ||
        v === 'delsentall' || v === 'capall' || v === 'lowerall'
      ) {
        const answer = confirm(
          'THIS IS AN ACTION ON ALL RECORDS\nON ALL PAGES OF THE CURRENT QUERY!\n\n' +
          '*** ' + t + ' ***\n\n*** ' + n + ' Record(s) will be affected ***\n\n' +
          'ARE YOU SURE?'
        );
        if (answer) {
          f.submit();
        }
      } else {
        f.submit();
      }
    }
    sel.value = '';
  }
}

/**
 * Check if cookies are enabled by setting a cookie.
 *
 * @returns true if cookies are enabled, false otherwise
 */
export function areCookiesEnabled(): boolean {
  setCookie('test', 'none', 0, '/', '', false);
  let cookie_set: boolean;
  if (getCookie('test')) {
    cookie_set = true;
    deleteCookie('test', '/', '');
  } else {
    cookie_set = false;
  }
  return cookie_set;
}

/**
 * Set the current language.
 *
 * @param ctl Current language selector element
 * @param url URL to redirect to
 */
export function setLang(ctl: HTMLSelectElement, url: string): void {
  location.href = 'inc/save_setting_redirect.php?k=currentlanguage&v=' +
    ctl.options[ctl.selectedIndex].value +
    '&u=' + url;
}

/**
 * Reset current language to default.
 *
 * @param url URL to redirect to
 */
export function resetAll(url: string): void {
  location.href = 'inc/save_setting_redirect.php?k=currentlanguage&v=&u=' + url;
}

/**
 * Get a specific cookie by its name.
 *
 * @param check_name Cookie name
 * @returns Value of the cookie if found, null otherwise
 *
 * @since 2.6.0-fork Use decodeURIComponent instead of deprecated unescape
 */
export function getCookie(check_name: string): string | null {
  const a_all_cookies = document.cookie.split(';');
  let a_temp_cookie: string[];
  let cookie_name = '';
  let cookie_value = '';

  for (let i = 0; i < a_all_cookies.length; i++) {
    a_temp_cookie = a_all_cookies[i].split('=');
    cookie_name = a_temp_cookie[0].replace(/^\s+|\s+$/g, '');
    if (cookie_name === check_name) {
      if (a_temp_cookie.length > 1) {
        cookie_value = decodeURIComponent(
          a_temp_cookie[1].replace(/^\s+|\s+$/g, '')
        );
      }
      return cookie_value;
    }
  }
  return null;
}

/**
 * Set a new cookie.
 *
 * @param name    Name of the cookie
 * @param value   Cookie value
 * @param expires Number of DAYS before the cookie expires.
 * @param path    Cookie path
 * @param domain  Cookie domain
 * @param secure  If it should only be sent through secure connection
 *
 * @since 2.6.0-fork Use encodeURIComponent instead of deprecated escape
 */
export function setCookie(
  name: string,
  value: string,
  expires: number,
  path: string,
  domain: string,
  secure: boolean
): void {
  const today = new Date();
  today.setTime(today.getTime());
  let expiresMs = 0;
  if (expires) {
    expiresMs = expires * 1000 * 60 * 60 * 24;
  }
  const expires_date = new Date(today.getTime() + expiresMs);
  document.cookie = name + '=' + encodeURIComponent(value) +
    (expires ? ';expires=' + expires_date.toUTCString() : '') +
    (path ? ';path=' + path : '') +
    (domain ? ';domain=' + domain : '') +
    (secure ? ';secure' : '');
}

/**
 * Delete a cookie.
 *
 * @param name   Cookie name
 * @param path   Cookie path
 * @param domain Cookie domain
 */
export function deleteCookie(name: string, path: string, domain: string): void {
  if (getCookie(name)) {
    document.cookie = name + '=' +
      (path ? ';path=' + path : '') +
      (domain ? ';domain=' + domain : '') +
      ';expires=Thu, 01-Jan-1970 00:00:01 GMT';
  }
}

/**
 * Prepare a window to make all words from a text well-known
 *
 * @param t Text ID
 */
export function iknowall(t: string | number): void {
  const answer = confirm('Are you sure?');
  if (answer) {
    showRightFrames('all_words_wellknown.php?text=' + t);
  }
}

/**
 * Check is the table prefix is a valid alphanumeric character.
 * Create an alert if not.
 *
 * @param p Table prefix
 * @returns true is the prefix is valid
 */
export function check_table_prefix(p: string): boolean {
  const re = /^[_a-zA-Z0-9]*$/;
  const r = p.length <= 20 && p.length > 0 && re.test(p);
  if (!r) {
    alert(
      'Table Set Name (= Table Prefix) must' +
      '\ncontain 1 to 20 characters (only 0-9, a-z, A-Z and _).' +
      '\nPlease correct your input.'
    );
  }
  return r;
}

// Expose globally for backward compatibility with PHP templates
if (typeof window !== 'undefined') {
  const w = window as unknown as Record<string, unknown>;
  w.getStatusName = getStatusName;
  w.getStatusAbbr = getStatusAbbr;
  w.translateSentence = translateSentence;
  w.translateSentence2 = translateSentence2;
  w.translateWord = translateWord;
  w.translateWord2 = translateWord2;
  w.translateWord3 = translateWord3;
  w.getLangFromDict = getLangFromDict;
  w.make_tooltip = make_tooltip;
  w.escape_html_chars_2 = escape_html_chars_2;
  w.owin = owin;
  w.oewin = oewin;
  w.createTheDictUrl = createTheDictUrl;
  w.createTheDictLink = createTheDictLink;
  w.createSentLookupLink = createSentLookupLink;
  w.escape_html_chars = escape_html_chars;
  w.escape_apostrophes = escape_apostrophes;
  w.selectToggle = selectToggle;
  w.multiActionGo = multiActionGo;
  w.allActionGo = allActionGo;
  w.areCookiesEnabled = areCookiesEnabled;
  w.setLang = setLang;
  w.resetAll = resetAll;
  w.getCookie = getCookie;
  w.setCookie = setCookie;
  w.deleteCookie = deleteCookie;
  w.iknowall = iknowall;
  w.check_table_prefix = check_table_prefix;
}
