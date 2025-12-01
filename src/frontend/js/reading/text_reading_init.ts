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

import $ from 'jquery';
import { getLangFromDict } from '../terms/dictionary';
import { prepareTextInteractions } from './text_events';
import { goToLastPosition, saveReadingPosition, saveAudioPosition, readRawTextAloud } from '../core/user_interactions';
import { getAudioPlayer } from '../media/html5_audio_player';
import { LWT_DATA } from '../core/lwt_state';
import { initNativeTooltips } from '../ui/native_tooltip';

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

  const langFromDict = typeof getLangFromDict === 'function'
    ? getLangFromDict(LWT_DATA.language?.translator_link || '')
    : '';

  text_reader = {
    text: config.phoneticText || '',
    lang: langFromDict || config.languageCode || '',
    rate: 0.8
  };

  if (typeof LWT_DATA !== 'undefined' && LWT_DATA.language) {
    LWT_DATA.language.ttsVoiceApi = config.voiceApi || '';
  }

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
  const langFromDict = typeof getLangFromDict === 'function'
    ? getLangFromDict(LWT_DATA.language?.translator_link || '')
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

  // Check for HTML5 audio player first (Vite mode)
  if (typeof getAudioPlayer === 'function') {
    const player = getAudioPlayer();
    if (player) {
      saveAudioPosition(textId, player.getCurrentTime());
      return;
    }
  }
  // Fall back to jPlayer (legacy mode)
  if (typeof $ !== 'undefined' && $('#jquery_jplayer_1').length > 0) {
    const jPlayerData = $('#jquery_jplayer_1').data('jPlayer');
    if (jPlayerData && jPlayerData.status) {
      saveAudioPosition(textId, jPlayerData.status.currentTime);
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
  // First position from the top
  const firstWord = $('.wsty').not('.hide').eq(0);
  if (firstWord.length === 0) {
    return;
  }
  const topPos = ($(window).scrollTop() || 0) - (firstWord.height() || 0);
  $('.wsty').not('.hide').each(function () {
    const offset = $(this).offset();
    if (offset && offset.top >= topPos) {
      const dataPos = $(this).attr('data_pos');
      pos = parseInt(dataPos || '0', 10);
      return false; // Break the loop
    }
  });
  saveReadingPosition(LWT_DATA.text.id, pos);
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
  // Load and merge config into LWT_DATA
  const config = loadTextReadingConfig();
  if (config) {
    mergeGlobals(config as unknown as Record<string, unknown>);
  }

  // Set LANG global
  if (typeof getLangFromDict === 'function' && LWT_DATA.language?.translator_link) {
    window.LANG = getLangFromDict(LWT_DATA.language.translator_link);
  }

  // Reset reading position (will be set by goToLastPosition)
  if (LWT_DATA.text) {
    LWT_DATA.text.reading_position = -1;
  }

  // Initialize test answer state if test object exists
  if (LWT_DATA.test) {
    LWT_DATA.test.answer_opened = false;
  }

  // Set the language of the current frame
  if (window.LANG && window.LANG !== LWT_DATA.language?.translator_link) {
    $('html').attr('lang', window.LANG);
  }

  // Initialize native tooltips if enabled
  if (LWT_DATA.settings?.jQuery_tooltip) {
    $(function () {
      const thetext = document.getElementById('thetext');
      if (thetext) {
        initNativeTooltips(thetext);
      }
    });
  }

  // Set up event handlers
  $(document).ready(prepareTextInteractions);
  $(document).ready(goToLastPosition);
  $(window).on('beforeunload', saveCurrentPosition);
}

/**
 * Initialize the text reading header (TTS button, audio save).
 * Called when the header view is ready.
 */
export function initTextReadingHeader(): void {
  // Set up beforeunload handler for audio position
  $(window).on('beforeunload', saveTextStatus);

  // Initialize TTS
  initTTS();

  // Bind click handler for TTS button
  $(document).ready(function () {
    $('#readTextButton').on('click', toggleReading);
  });
}

/**
 * Auto-initialize based on page context.
 * Detects which page we're on and initializes accordingly.
 */
export function autoInit(): void {
  // Check if we're on the text reading page (detect by config or legacy globals)
  const hasTextReadingConfig = document.getElementById('text-reading-config') !== null;
  if ($('#thetext').length > 0 && (hasTextReadingConfig || window.new_globals)) {
    initTextReading();
  }

  // Check if we have header TTS data (detect by config or legacy globals)
  const hasHeaderConfig = document.getElementById('text-header-config') !== null;
  if (hasHeaderConfig || typeof window._lwtPhoneticText !== 'undefined') {
    initTextReadingHeader();
  }
}

// Auto-initialize when DOM is ready (if Vite is already loaded)
$(function () {
  if (window.LWT_VITE_LOADED) {
    autoInit();
  }
});
