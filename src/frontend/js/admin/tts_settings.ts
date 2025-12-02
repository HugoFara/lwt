/**
 * TTS Settings - Text-to-Speech settings management.
 *
 * Extracted from Views/Admin/tts_settings.php
 * Manages voice selection, reading rate/pitch, and demo playback.
 *
 * @license Unlicense
 * @since 3.0.0
 */

import { readTextAloud } from '../core/user_interactions';
import { lwtFormCheck } from '../forms/unloadformcheck';
import { getTTSSettingsWithMigration, saveTTSSettings } from '../core/tts_storage';

/**
 * Configuration for TTS settings.
 * Passed from PHP via JSON or data attribute.
 */
export interface TTSSettingsConfig {
  currentLanguageCode: string;
}

/**
 * TTS settings object.
 * Handles voice management and demo playback for Text-to-Speech.
 */
export const ttsSettings = {
  /** Current language being learnt */
  currentLanguage: '',

  /**
   * Initialize with configuration.
   */
  init(config: TTSSettingsConfig): void {
    this.currentLanguage = config.currentLanguageCode;
  },

  /**
   * Auto-set current language from URL parameters.
   */
  autoSetCurrentLanguage(): void {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('lang')) {
      ttsSettings.currentLanguage = urlParams.get('lang') || '';
    }
  },

  /**
   * Get the language country code from the page.
   *
   * @returns Language code (e.g., "en")
   */
  getLanguageCode(): string {
    const el = document.getElementById('get-language') as HTMLSelectElement | null;
    return el?.value || '';
  },

  /**
   * Gather data in the page to read the demo.
   */
  readingDemo(): void {
    const lang = ttsSettings.getLanguageCode();
    const demoEl = document.getElementById('tts-demo') as HTMLInputElement | null;
    const rateEl = document.getElementById('rate') as HTMLInputElement | null;
    const pitchEl = document.getElementById('pitch') as HTMLInputElement | null;
    const voiceEl = document.getElementById('voice') as HTMLSelectElement | null;
    readTextAloud(
      demoEl?.value || '',
      lang,
      parseFloat(rateEl?.value || '1'),
      parseFloat(pitchEl?.value || '1'),
      voiceEl?.value || undefined
    );
  },

  /**
   * Set the Text-to-Speech data using localStorage.
   *
   * @since 3.0.0 Changed from cookies to localStorage
   */
  presetTTSData(): void {
    const langName = ttsSettings.currentLanguage;
    const langEl = document.getElementById('get-language') as HTMLSelectElement | null;
    const voiceEl = document.getElementById('voice') as HTMLSelectElement | null;
    const rateEl = document.getElementById('rate') as HTMLInputElement | null;
    const pitchEl = document.getElementById('pitch') as HTMLInputElement | null;

    if (langEl) langEl.value = langName;

    // Get settings from localStorage (with automatic migration from cookies)
    const settings = getTTSSettingsWithMigration(langName);
    if (voiceEl) voiceEl.value = settings.voice || '';
    if (rateEl) rateEl.value = String(settings.rate ?? 1);
    if (pitchEl) pitchEl.value = String(settings.pitch ?? 1);
  },

  /**
   * Save the current TTS settings to localStorage.
   *
   * @since 3.0.0 New function to save settings client-side
   */
  saveSettings(): void {
    const langEl = document.getElementById('get-language') as HTMLSelectElement | null;
    const voiceEl = document.getElementById('voice') as HTMLSelectElement | null;
    const rateEl = document.getElementById('rate') as HTMLInputElement | null;
    const pitchEl = document.getElementById('pitch') as HTMLInputElement | null;

    const langName = langEl?.value || ttsSettings.currentLanguage;
    if (!langName) {
      console.error('Cannot save TTS settings: no language selected');
      return;
    }

    saveTTSSettings(langName, {
      voice: voiceEl?.value || undefined,
      rate: rateEl ? parseFloat(rateEl.value) : undefined,
      pitch: pitchEl ? parseFloat(pitchEl.value) : undefined
    });
  },

  /**
   * Populate the languages region list.
   */
  populateVoiceList(): void {
    const voices = window.speechSynthesis.getVoices();
    const voiceSelect = document.getElementById('voice') as HTMLSelectElement | null;
    if (!voiceSelect) return;
    voiceSelect.innerHTML = '';
    const languageCode = ttsSettings.getLanguageCode();

    for (let i = 0; i < voices.length; i++) {
      if (voices[i].lang !== languageCode && !voices[i].default) {
        continue;
      }
      const option = document.createElement('option');
      option.textContent = voices[i].name;

      if (voices[i].default) {
        option.textContent += ' -- DEFAULT';
      }

      option.setAttribute('data-lang', voices[i].lang);
      option.setAttribute('data-name', voices[i].name);
      voiceSelect.appendChild(option);
    }
  },

  /**
   * Handle cancel button click.
   */
  clickCancel(): void {
    lwtFormCheck.resetDirty();
    location.href = '/admin/settings';
  }
};

/**
 * Initialize TTS settings from the page.
 */
export function initTTSSettings(): void {
  const configEl = document.getElementById('tts-settings-config');
  let config: TTSSettingsConfig;

  if (configEl) {
    try {
      config = JSON.parse(configEl.textContent || '{}');
    } catch (e) {
      console.error('Failed to parse tts-settings-config:', e);
      config = { currentLanguageCode: '' };
    }
  } else {
    // Fallback: try to get from data attribute on form
    const form = document.querySelector('form.validate');
    config = {
      currentLanguageCode: form?.getAttribute('data-current-language') || ''
    };
  }

  ttsSettings.init(config);
  ttsSettings.autoSetCurrentLanguage();
  ttsSettings.presetTTSData();
  ttsSettings.populateVoiceList();

  // Set up event listeners
  const languageSelect = document.getElementById('get-language');
  if (languageSelect) {
    languageSelect.addEventListener('change', () => ttsSettings.populateVoiceList());
  }

  const readButton = document.querySelector('[data-action="tts-demo"]');
  if (readButton) {
    readButton.addEventListener('click', () => ttsSettings.readingDemo());
  }

  const cancelButton = document.querySelector('[data-action="tts-cancel"]');
  if (cancelButton) {
    cancelButton.addEventListener('click', () => ttsSettings.clickCancel());
  }

  // Handle form submission - save to localStorage
  const form = document.querySelector('form.validate') as HTMLFormElement | null;
  if (form) {
    form.addEventListener('submit', (e) => {
      // Save settings to localStorage before form submits
      ttsSettings.saveSettings();
      // Let the form continue to submit (shows success message)
    });
  }
}

// Auto-initialize on DOM ready if config element or TTS form is present
document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('tts-settings-config') ||
      document.querySelector('form.validate select#get-language')) {
    initTTSSettings();
  }
});

// Deprecated wrapper functions for backward compatibility
/**
 * @deprecated Since 2.10.0-fork, use ttsSettings.getLanguageCode()
 */
export function getLanguageCode(): string {
  return ttsSettings.getLanguageCode();
}

/**
 * @deprecated Since 2.10.0-fork, use ttsSettings.readingDemo()
 */
export function readingDemo(): void {
  return ttsSettings.readingDemo();
}

/**
 * @deprecated Since 2.10.0-fork, use ttsSettings.presetTTSData()
 */
export function presetTTSData(): void {
  return ttsSettings.presetTTSData();
}

/**
 * @deprecated Since 2.10.0-fork, use ttsSettings.populateVoiceList()
 */
export function populateVoiceList(): void {
  return ttsSettings.populateVoiceList();
}

/**
 * Save TTS settings to localStorage.
 * @since 3.0.0
 */
export function saveSettings(): void {
  return ttsSettings.saveSettings();
}
