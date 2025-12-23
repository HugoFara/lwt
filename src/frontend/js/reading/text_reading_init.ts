/**
 * Text reading initialization module.
 *
 * Handles initialization of the text reading interface including:
 * - Text-to-speech (TTS) setup
 * - Global LWT_DATA configuration
 * - Reading position saving
 * - Audio position saving
 *
 * @license Unlicense <http://unlicense.org/>
 * @since 3.0.0
 */

import { getLangFromDict } from '../terms/dictionary';
import { prepareTextInteractions } from './text_events';
import { goToLastPosition, saveReadingPosition, saveAudioPosition, readRawTextAloud } from '../core/user_interactions';
import { getAudioPlayer } from '../media/html5_audio_player';
import { initNativeTooltips } from '../ui/native_tooltip';
import { resetReadingPosition } from '../core/reading_state';
import {
  initLanguageConfig,
  getDictionaryLinks,
  setTtsVoiceApi
} from '../core/language_config';
import { initTextConfig, getTextId } from '../core/text_config';
import { initSettingsConfig } from '../core/settings_config';
import { resetAnswer } from '../core/test_state';

// Type definitions for text reader
interface TextReader {
  text: string;
  lang: string;
  rate: number;
}

/**
 * Configuration from text-header-config JSON element.
 */
interface TextHeaderConfig {
  textId: number;
  phoneticText: string;
  languageCode: string;
  voiceApi: string | null;
}

/**
 * Configuration from text-reading-config JSON element.
 */
interface TextReadingConfig {
  LWT_DATA?: {
    language?: Record<string, unknown>;
    text?: Record<string, unknown>;
    settings?: Record<string, unknown>;
  };
}

// Declare the global variables that PHP will set
declare global {
  interface Window {
    // Legacy header view data (for backwards compatibility)
    _lwtPhoneticText?: string;
    _lwtLanguageCode?: string;
    _lwtVoiceApi?: string | null;
    _lwtTextId?: number;
    // Legacy content view data (for backwards compatibility)
    new_globals?: TextReadingConfig;
    // LANG global variable
    LANG?: string;
  }
}

// Text reader state for TTS
let text_reader: TextReader | null = null;

/**
 * Load header configuration from JSON element or legacy window variables.
 */
function loadHeaderConfig(): TextHeaderConfig | null {
  // Try new JSON config first
  const configEl = document.getElementById('text-header-config');
  if (configEl) {
    try {
      return JSON.parse(configEl.textContent || '{}') as TextHeaderConfig;
    } catch (e) {
      console.error('Failed to parse text-header-config:', e);
    }
  }

  // Fall back to legacy window variables
  if (typeof window._lwtPhoneticText !== 'undefined') {
    return {
      textId: window._lwtTextId || 0,
      phoneticText: window._lwtPhoneticText || '',
      languageCode: window._lwtLanguageCode || '',
      voiceApi: window._lwtVoiceApi || null
    };
  }

  return null;
}

/**
 * Initialize TTS (Text-to-Speech) after Vite bundle is loaded.
 * Sets up the text_reader object with phonetic text and language settings.
 */
export function initTTS(): void {
  const config = loadHeaderConfig();
  if (!config) {
    return;
  }

  const dictLinks = getDictionaryLinks();
  const langFromDict = typeof getLangFromDict === 'function'
    ? getLangFromDict(dictLinks.translator || '')
    : '';

  text_reader = {
    text: config.phoneticText || '',
    lang: langFromDict || config.languageCode || '',
    rate: 0.8
  };

  // Update TTS voice API in language config
  setTtsVoiceApi(config.voiceApi || '');

  // Store textId for later use
  window._lwtTextId = config.textId;
}

/**
 * Check browser compatibility and start reading.
 */
function initReading(): void {
  if (!('speechSynthesis' in window)) {
    alert('Your browser does not support speechSynthesis!');
    return;
  }
  if (!text_reader) {
    initTTS();
  }
  if (!text_reader) {
    return;
  }
  const dictLinks = getDictionaryLinks();
  const langFromDict = typeof getLangFromDict === 'function'
    ? getLangFromDict(dictLinks.translator || '')
    : '';
  const lang = langFromDict || text_reader.lang;
  if (typeof readRawTextAloud === 'function') {
    readRawTextAloud(text_reader.text, lang);
  }
}

/**
 * Start and stop the reading feature (TTS toggle).
 */
export function toggleReading(): void {
  const synth = window.speechSynthesis;
  if (synth === undefined) {
    alert('Your browser does not support speechSynthesis!');
    return;
  }
  if (synth.speaking) {
    synth.cancel();
  } else {
    initReading();
  }
}

/**
 * Save text status (audio position) when leaving the page.
 */
export function saveTextStatus(): void {
  const textId = window._lwtTextId;
  if (typeof textId === 'undefined') {
    return;
  }

  // Use HTML5 audio player
  if (typeof getAudioPlayer === 'function') {
    const player = getAudioPlayer();
    if (player) {
      saveAudioPosition(textId, player.getCurrentTime());
    }
  }
}

/**
 * Deep merge PHP globals into window.LWT_DATA.
 * This merges values from new_globals into existing LWT_DATA.
 *
 * @param newGlobals Object containing values to merge
 */
function mergeGlobals(newGlobals: Record<string, unknown>): void {
  // Use type assertion via unknown to work around strict type checking
  const win = window as unknown as Record<string, unknown>;
  for (const key in newGlobals) {
    if (typeof win[key] === 'undefined') {
      win[key] = newGlobals[key];
    } else if (typeof newGlobals[key] === 'object' && newGlobals[key] !== null) {
      const subObj = newGlobals[key] as Record<string, unknown>;
      for (const subkey1 in subObj) {
        const parentObj = win[key] as Record<string, unknown>;
        if (typeof parentObj[subkey1] === 'undefined') {
          parentObj[subkey1] = subObj[subkey1];
        } else if (typeof subObj[subkey1] === 'object' && subObj[subkey1] !== null) {
          const subSubObj = subObj[subkey1] as Record<string, unknown>;
          for (const subkey2 in subSubObj) {
            (parentObj[subkey1] as Record<string, unknown>)[subkey2] = subSubObj[subkey2];
          }
        } else {
          parentObj[subkey1] = subObj[subkey1];
        }
      }
    } else {
      win[key] = newGlobals[key];
    }
  }
}

/**
 * Save the current reading position.
 */
function saveCurrentPosition(): void {
  let pos = 0;
  // First position from the top - find first visible word
  const visibleWords = document.querySelectorAll<HTMLElement>('.wsty:not(.hide)');
  if (visibleWords.length === 0) {
    return;
  }
  const firstWord = visibleWords[0];
  const topPos = window.scrollY - (firstWord.offsetHeight || 0);

  for (const word of visibleWords) {
    const rect = word.getBoundingClientRect();
    const offsetTop = rect.top + window.scrollY;
    if (offsetTop >= topPos) {
      const dataPos = word.getAttribute('data_pos');
      pos = parseInt(dataPos || '0', 10);
      break;
    }
  }
  saveReadingPosition(getTextId(), pos);
}

/**
 * Load text reading configuration from JSON element or legacy window variable.
 */
function loadTextReadingConfig(): TextReadingConfig | null {
  // Try new JSON config first
  const configEl = document.getElementById('text-reading-config');
  if (configEl) {
    try {
      return JSON.parse(configEl.textContent || '{}') as TextReadingConfig;
    } catch (e) {
      console.error('Failed to parse text-reading-config:', e);
    }
  }

  // Fall back to legacy window variable
  if (window.new_globals) {
    return window.new_globals;
  }

  return null;
}

/**
 * Initialize the text reading interface.
 * Called after LWT Vite bundle is loaded.
 */
export function initTextReading(): void {
  // Load and merge config into LWT_DATA (for backwards compatibility)
  const config = loadTextReadingConfig();
  if (config) {
    mergeGlobals(config as unknown as Record<string, unknown>);

    // Also initialize new state modules from config
    if (config.LWT_DATA) {
      const lwtConfig = config.LWT_DATA;

      // Initialize language config
      if (lwtConfig.language) {
        const lang = lwtConfig.language as Record<string, unknown>;
        initLanguageConfig({
          id: (lang.id as number) || 0,
          dictLink1: (lang.dict_link1 as string) || '',
          dictLink2: (lang.dict_link2 as string) || '',
          translatorLink: (lang.translator_link as string) || '',
          delimiter: (lang.delimiter as string) || '',
          wordParsing: lang.word_parsing as number | string || '',
          rtl: (lang.rtl as boolean) || false,
          ttsVoiceApi: (lang.ttsVoiceApi as string) || ''
        });
      }

      // Initialize text config
      if (lwtConfig.text) {
        const text = lwtConfig.text as Record<string, unknown>;
        initTextConfig({
          id: (text.id as number) || 0,
          annotations: text.annotations as Record<string, [unknown, string, string]> | number || 0
        });
      }

      // Initialize settings config
      if (lwtConfig.settings) {
        const settings = lwtConfig.settings as Record<string, unknown>;
        initSettingsConfig({
          hts: (settings.hts as number) || 0,
          wordStatusFilter: (settings.word_status_filter as string) || '',
          annotationsMode: (settings.annotations_mode as number) || 1,
          useFrameMode: (settings.use_frame_mode as boolean) || false
        });
      }
    }
  }

  // Set LANG global
  const dictLinks = getDictionaryLinks();
  if (typeof getLangFromDict === 'function' && dictLinks.translator) {
    window.LANG = getLangFromDict(dictLinks.translator);
  }

  // Reset reading position (will be set by goToLastPosition)
  resetReadingPosition();

  // Initialize test answer state
  resetAnswer();

  // Set the language of the current frame
  if (window.LANG && window.LANG !== dictLinks.translator) {
    document.documentElement.setAttribute('lang', window.LANG);
  }

  // Initialize native tooltips (always enabled now that jQuery UI tooltips are removed)
  const thetext = document.getElementById('thetext');
  if (thetext) {
    initNativeTooltips(thetext);
  }

  // Set up event handlers (DOM should already be ready at this point)
  prepareTextInteractions();
  goToLastPosition();
  window.addEventListener('beforeunload', saveCurrentPosition);
}

/**
 * Initialize the text reading header (TTS button, audio save).
 * Called when the header view is ready.
 */
export function initTextReadingHeader(): void {
  // Set up beforeunload handler for audio position
  window.addEventListener('beforeunload', saveTextStatus);

  // Initialize TTS
  initTTS();

  // Bind click handler for TTS button
  const readTextButton = document.getElementById('readTextButton');
  if (readTextButton) {
    readTextButton.addEventListener('click', toggleReading);
  }
}

/**
 * Auto-initialize based on page context.
 * Detects which page we're on and initializes accordingly.
 */
export function autoInit(): void {
  // Check if we're on the text reading page (detect by config or legacy globals)
  const hasTextReadingConfig = document.getElementById('text-reading-config') !== null;
  const thetext = document.getElementById('thetext');
  if (thetext && (hasTextReadingConfig || window.new_globals)) {
    initTextReading();
  }

  // Check if we have header TTS data (detect by config or legacy globals)
  const hasHeaderConfig = document.getElementById('text-header-config') !== null;
  if (hasHeaderConfig || typeof window._lwtPhoneticText !== 'undefined') {
    initTextReadingHeader();
  }
}

// Auto-initialize when DOM is ready (if Vite is already loaded)
document.addEventListener('DOMContentLoaded', () => {
  if (window.LWT_VITE_LOADED) {
    autoInit();
  }
});
