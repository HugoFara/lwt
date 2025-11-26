/**
 * Type declarations for PHP-injected global variables
 *
 * These types describe the global variables that are injected into
 * the page by PHP scripts before the JavaScript bundle loads.
 */

export interface WordStatus {
  name: string;
  abbr: string;
  score: number;
  color: string;
}

export interface LwtLanguage {
  id: number;
  dict_link1: string;
  dict_link2: string;
  translator_link: string;
  delimiter: string;
  word_parsing: number;
  rtl: boolean;
  ttsVoiceApi: string;
}

export interface LwtText {
  id: number;
  reading_position: number;
  annotations: number;
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
}

export interface LwtData {
  language: LwtLanguage;
  text: LwtText;
  word: LwtWord;
  test: LwtTest;
  settings: LwtSettings;
}

declare global {
  interface Window {
    STATUSES: Record<string, WordStatus>;
    TAGS: Record<string, string>;
    TEXTTAGS: Record<string, string>;
    LWT_DATA: LwtData;
  }

  const STATUSES: Record<string, WordStatus>;
  const TAGS: Record<string, string>;
  const TEXTTAGS: Record<string, string>;
  const LWT_DATA: LwtData;
}
