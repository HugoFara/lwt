/**
 * Language Configuration Module - Provides type-safe access to language settings.
 *
 * This module provides explicit functions to access language configuration.
 * Configuration is loaded once from DOM data attributes or initial config.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since 3.1.0
 */

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
 * Get the current language configuration.
 * Returns a copy to prevent external mutation.
 */
export function getLanguageConfig(): Readonly<LanguageConfig> {
  return { ...currentConfig };
}

/**
 * Get the language ID.
 */
export function getLanguageId(): number {
  return currentConfig.id;
}

/**
 * Get dictionary links.
 */
export function getDictionaryLinks(): { dict1: string; dict2: string; translator: string } {
  return {
    dict1: currentConfig.dictLink1,
    dict2: currentConfig.dictLink2,
    translator: currentConfig.translatorLink
  };
}

/**
 * Check if the language uses right-to-left script.
 */
export function isRtl(): boolean {
  return currentConfig.rtl;
}

/**
 * Get the term translation delimiter.
 */
export function getDelimiter(): string {
  return currentConfig.delimiter;
}

/**
 * Get the TTS voice API identifier.
 */
export function getTtsVoiceApi(): string {
  return currentConfig.ttsVoiceApi;
}

/**
 * Set the TTS voice API identifier.
 * This is one of the few mutable settings.
 */
export function setTtsVoiceApi(api: string): void {
  currentConfig.ttsVoiceApi = api;
  isInitialized = true;
}

/**
 * Set dictionary links.
 * Used when initializing from page-specific configuration.
 */
export function setDictionaryLinks(links: { dict1?: string; dict2?: string; translator?: string }): void {
  if (links.dict1 !== undefined) {
    currentConfig.dictLink1 = links.dict1;
  }
  if (links.dict2 !== undefined) {
    currentConfig.dictLink2 = links.dict2;
  }
  if (links.translator !== undefined) {
    currentConfig.translatorLink = links.translator;
  }
  isInitialized = true;
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
