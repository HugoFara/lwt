/**
 * LWT State Management - Core data structures and legacy globals
 *
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 */

// Import types from globals.d.ts to ensure consistency
import type { LwtData, LwtLanguage, LwtText, LwtWord, LwtTest, LwtSettings } from '../types/globals.d';

// Re-export types for backward compatibility
export type { LwtLanguage, LwtText, LwtWord, LwtTest, LwtSettings };

// LwtDataInterface is now an alias to LwtData for consistency
export type LwtDataInterface = LwtData;

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
    // annotations can be either a number or a Record
    annotations: 0
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


