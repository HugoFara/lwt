/**
 * LWT State Management - Core data structures and legacy globals
 *
 * This module provides the legacy LWT_DATA global object for backwards
 * compatibility. New code should use the focused state modules instead:
 *
 * - reading_state.ts - Reading position state
 * - language_config.ts - Language configuration
 * - text_config.ts - Text configuration
 * - settings_config.ts - Application settings
 * - test_state.ts - Test mode state
 *
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 */

// Import types from globals.d.ts to ensure consistency
import type { LwtData, LwtLanguage, LwtText, LwtWord, LwtTest, LwtSettings } from '../types/globals.d';

// Re-export new state modules for easier migration
export * from './reading_state';
export * from './language_config';
export * from './text_config';
export * from './settings_config';
export * from './test_state';

// Re-export types for backward compatibility
export type { LwtLanguage, LwtText, LwtWord, LwtTest, LwtSettings };

// LwtDataInterface is now an alias to LwtData for consistency
export type LwtDataInterface = LwtData;

/**
 * Legacy LWT_DATA global object.
 *
 * @deprecated Use the focused state modules instead:
 * - getReadingPosition() / setReadingPosition() from reading_state
 * - getLanguageConfig() / getLanguageId() from language_config
 * - getTextId() / getAnnotations() from text_config
 * - getHtsMode() / isApiModeEnabled() from settings_config
 * - getTestSolution() / isAnswerOpened() from test_state
 */
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
    hts: 0,
    word_status_filter: ''
  }
};


