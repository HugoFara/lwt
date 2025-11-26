/**
 * LWT State Management - Core data structures and legacy globals
 *
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 */

// Type definitions
export interface LwtLanguage {
  id: number;
  dict_link1: string;
  dict_link2: string;
  translator_link: string;
  delimiter: string;
  word_parsing: string;
  rtl: boolean;
  ttsVoiceApi: string;
}

export interface LwtText {
  id: number;
  reading_position: number;
  annotations: Record<string, unknown>;
}

export interface LwtWord {
  id: number;
}

export interface LwtTest {
  solution: string;
  answer_opened: boolean;
}

export interface LwtSettings {
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

