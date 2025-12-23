/**
 * Language Configuration Module - Provides type-safe access to language settings.
 *
 * This module replaces direct LWT_DATA.language access with explicit functions.
 * Configuration is loaded once from DOM data attributes or initial config.
 *
 * For backward compatibility, getter functions fall back to reading from
 * the legacy LWT_DATA global when this module hasn't been initialized.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since 3.1.0
 */

// Import LWT_DATA type from globals
import type { LwtData } from '../types/globals.d';

export interface LanguageConfig {
  id: number;
  name?: string;
  dictLink1: string;
  dictLink2: string;
  translatorLink: string;
  delimiter: string;
  wordParsing: number | string;
  rtl: boolean;
  ttsVoiceApi: string;
}

const defaultConfig: LanguageConfig = {
  id: 0,
  dictLink1: '',
  dictLink2: '',
  translatorLink: '',
  delimiter: '',
  wordParsing: '',
  rtl: false,
  ttsVoiceApi: ''
};

let currentConfig: LanguageConfig = { ...defaultConfig };
let isInitialized = false;

/**
 * Get language config from legacy LWT_DATA for backward compatibility.
 */
function getFromLegacy(): LanguageConfig {
  const lwtData = typeof window !== 'undefined' ? (window as { LWT_DATA?: LwtData }).LWT_DATA : undefined;
  if (lwtData?.language) {
    return {
      id: lwtData.language.id || 0,
      dictLink1: lwtData.language.dict_link1 || '',
      dictLink2: lwtData.language.dict_link2 || '',
      translatorLink: lwtData.language.translator_link || '',
      delimiter: lwtData.language.delimiter || '',
      wordParsing: lwtData.language.word_parsing || '',
      rtl: lwtData.language.rtl || false,
      ttsVoiceApi: lwtData.language.ttsVoiceApi || ''
    };
  }
  return defaultConfig;
}

/**
 * Initialize language configuration from a config object.
 *
 * @param config Partial language configuration
 */
export function initLanguageConfig(config: Partial<LanguageConfig>): void {
  currentConfig = { ...defaultConfig, ...config };
  isInitialized = true;
}

/**
 * Initialize language configuration from DOM data attributes.
 *
 * Looks for a #thetext element with data-lang-* attributes.
 */
export function initLanguageConfigFromDOM(): void {
  const thetext = document.getElementById('thetext');
  if (!thetext) return;

  const config: Partial<LanguageConfig> = {};

  const langId = thetext.dataset.langId;
  if (langId) config.id = parseInt(langId, 10);

  const dictLink1 = thetext.dataset.dictLink1;
  if (dictLink1) config.dictLink1 = dictLink1;

  const dictLink2 = thetext.dataset.dictLink2;
  if (dictLink2) config.dictLink2 = dictLink2;

  const translatorLink = thetext.dataset.translatorLink;
  if (translatorLink) config.translatorLink = translatorLink;

  const delimiter = thetext.dataset.delimiter;
  if (delimiter) config.delimiter = delimiter;

  const rtl = thetext.dataset.rtl;
  if (rtl) config.rtl = rtl === 'true' || rtl === '1';

  const ttsVoiceApi = thetext.dataset.ttsVoiceApi;
  if (ttsVoiceApi) config.ttsVoiceApi = ttsVoiceApi;

  initLanguageConfig(config);
}

/**
 * Get the effective config (module state or legacy fallback).
 */
function getEffectiveConfig(): LanguageConfig {
  return isInitialized ? currentConfig : getFromLegacy();
}

/**
 * Get the current language configuration.
 * Returns a copy to prevent external mutation.
 * Falls back to LWT_DATA if not initialized.
 */
export function getLanguageConfig(): Readonly<LanguageConfig> {
  return { ...getEffectiveConfig() };
}

/**
 * Get the language ID.
 * Falls back to LWT_DATA.language.id if not initialized.
 */
export function getLanguageId(): number {
  return getEffectiveConfig().id;
}

/**
 * Get dictionary links.
 * Falls back to LWT_DATA.language if not initialized.
 */
export function getDictionaryLinks(): { dict1: string; dict2: string; translator: string } {
  const config = getEffectiveConfig();
  return {
    dict1: config.dictLink1,
    dict2: config.dictLink2,
    translator: config.translatorLink
  };
}

/**
 * Check if the language uses right-to-left script.
 * Falls back to LWT_DATA.language.rtl if not initialized.
 */
export function isRtl(): boolean {
  return getEffectiveConfig().rtl;
}

/**
 * Get the term translation delimiter.
 * Falls back to LWT_DATA.language.delimiter if not initialized.
 */
export function getDelimiter(): string {
  return getEffectiveConfig().delimiter;
}

/**
 * Get the TTS voice API identifier.
 * Falls back to LWT_DATA.language.ttsVoiceApi if not initialized.
 */
export function getTtsVoiceApi(): string {
  return getEffectiveConfig().ttsVoiceApi;
}

/**
 * Set the TTS voice API identifier.
 * This is one of the few mutable settings.
 */
export function setTtsVoiceApi(api: string): void {
  currentConfig.ttsVoiceApi = api;
}

/**
 * Check if language config has been initialized.
 */
export function isLanguageConfigInitialized(): boolean {
  return isInitialized;
}

/**
 * Reset to default configuration (for testing).
 */
export function resetLanguageConfig(): void {
  currentConfig = { ...defaultConfig };
  isInitialized = false;
}
