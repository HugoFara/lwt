/**
 * TTS Settings - Text-to-Speech settings management as Alpine.js component.
 *
 * Manages voice selection, reading rate/pitch, and demo playback.
 *
 * @license Unlicense
 * @since 3.0.0
 * @since 3.1.0 Migrated to Alpine.js component
 */

import Alpine from 'alpinejs';
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
 * Voice option interface for type safety.
 */
interface VoiceOption {
  name: string;
  lang: string;
  isDefault: boolean;
}

/**
 * Alpine.js component for TTS settings management.
 * Replaces the vanilla JS ttsSettings object.
 */
export function ttsSettingsApp(config: TTSSettingsConfig = { currentLanguageCode: '' }) {
  return {
    /** Current language being learnt */
    currentLanguage: config.currentLanguageCode,

    /** Available voice options */
    voices: [] as VoiceOption[],

    /** Selected voice name */
    selectedVoice: '',

    /** Reading rate (0.5-2) */
    rate: 1,

    /** Pitch (0-2) */
    pitch: 1,

    /** Demo text for testing */
    demoText: 'Lorem ipsum dolor sit amet...',

    /** Whether voices are loading */
    voicesLoading: true,

    /**
     * Initialize the component.
     */
    init() {
      // Auto-set language from URL if present
      this.autoSetCurrentLanguage();

      // Load saved settings from localStorage
      this.loadSavedSettings();

      // Populate voices (may need to wait for speechSynthesis)
      this.initVoices();
    },

    /**
     * Auto-set current language from URL parameters.
     */
    autoSetCurrentLanguage() {
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.has('lang')) {
        this.currentLanguage = urlParams.get('lang') || '';
      }
    },

    /**
     * Load saved TTS settings from localStorage.
     */
    loadSavedSettings() {
      if (!this.currentLanguage) return;

      const settings = getTTSSettingsWithMigration(this.currentLanguage);
      if (settings.voice) this.selectedVoice = settings.voice;
      if (settings.rate !== undefined) this.rate = settings.rate;
      if (settings.pitch !== undefined) this.pitch = settings.pitch;
    },

    /**
     * Initialize voice list from speechSynthesis API.
     */
    initVoices() {
      if (typeof window.speechSynthesis === 'undefined') {
        this.voicesLoading = false;
        return;
      }

      // Voices may not be immediately available
      const loadVoices = () => {
        this.populateVoiceList();
        this.voicesLoading = false;
      };

      // Try immediately
      if (window.speechSynthesis.getVoices().length > 0) {
        loadVoices();
      } else {
        // Wait for voices to load
        window.speechSynthesis.onvoiceschanged = loadVoices;
      }
    },

    /**
     * Populate the voice list based on current language.
     */
    populateVoiceList() {
      const voices = window.speechSynthesis.getVoices();
      this.voices = [];

      for (const voice of voices) {
        if (voice.lang !== this.currentLanguage && !voice.default) {
          continue;
        }
        this.voices.push({
          name: voice.name,
          lang: voice.lang,
          isDefault: voice.default
        });
      }

      // If no matching voices, show all available
      if (this.voices.length === 0) {
        for (const voice of voices) {
          this.voices.push({
            name: voice.name,
            lang: voice.lang,
            isDefault: voice.default
          });
        }
      }
    },

    /**
     * Handle language selection change.
     */
    onLanguageChange() {
      this.populateVoiceList();
      this.loadSavedSettings();
    },

    /**
     * Play demo text with current settings.
     */
    playDemo() {
      readTextAloud(
        this.demoText,
        this.currentLanguage,
        this.rate,
        this.pitch,
        this.selectedVoice || undefined
      );
    },

    /**
     * Save current settings to localStorage.
     */
    saveSettings() {
      if (!this.currentLanguage) {
        console.error('Cannot save TTS settings: no language selected');
        return;
      }

      saveTTSSettings(this.currentLanguage, {
        voice: this.selectedVoice || undefined,
        rate: this.rate,
        pitch: this.pitch
      });
    },

    /**
     * Handle cancel - reset form and redirect.
     */
    cancel() {
      lwtFormCheck.resetDirty();
      location.href = '/admin/settings';
    },

    /**
     * Get display name for a voice (with DEFAULT label if applicable).
     */
    getVoiceDisplayName(voice: VoiceOption): string {
      return voice.isDefault ? `${voice.name} -- DEFAULT` : voice.name;
    }
  };
}

// Register Alpine component
if (typeof Alpine !== 'undefined') {
  Alpine.data('ttsSettingsApp', ttsSettingsApp);
}

// ============================================================================
// Legacy API - Deprecated wrapper for backward compatibility
// ============================================================================

/**
 * Legacy TTS settings object.
 * @deprecated Since 3.1.0, use ttsSettingsApp() Alpine component
 */
export const ttsSettings = {
  /** Current language being learnt */
  currentLanguage: '',

  /**
   * Initialize with configuration.
   * @deprecated Since 3.1.0, use ttsSettingsApp() Alpine component
   */
  init(config: TTSSettingsConfig): void {
    this.currentLanguage = config.currentLanguageCode;
  },

  /**
   * Auto-set current language from URL parameters.
   * @deprecated Since 3.1.0, use ttsSettingsApp() Alpine component
   */
  autoSetCurrentLanguage(): void {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('lang')) {
      this.currentLanguage = urlParams.get('lang') || '';
    }
  },

  /**
   * Get the language country code from the page.
   * @deprecated Since 3.1.0, use ttsSettingsApp() Alpine component
   */
  getLanguageCode(): string {
    const el = document.getElementById('get-language') as HTMLSelectElement | null;
    return el?.value || '';
  },

  /**
   * Gather data in the page to read the demo.
   * @deprecated Since 3.1.0, use ttsSettingsApp() Alpine component
   */
  readingDemo(): void {
    const lang = this.getLanguageCode();
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
   * @deprecated Since 3.1.0, use ttsSettingsApp() Alpine component
   */
  presetTTSData(): void {
    const langName = this.currentLanguage;
    const langEl = document.getElementById('get-language') as HTMLSelectElement | null;
    const voiceEl = document.getElementById('voice') as HTMLSelectElement | null;
    const rateEl = document.getElementById('rate') as HTMLInputElement | null;
    const pitchEl = document.getElementById('pitch') as HTMLInputElement | null;

    if (langEl) langEl.value = langName;

    const settings = getTTSSettingsWithMigration(langName);
    if (voiceEl) voiceEl.value = settings.voice || '';
    if (rateEl) rateEl.value = String(settings.rate ?? 1);
    if (pitchEl) pitchEl.value = String(settings.pitch ?? 1);
  },

  /**
   * Save the current TTS settings to localStorage.
   * @deprecated Since 3.1.0, use ttsSettingsApp() Alpine component
   */
  saveSettings(): void {
    const langEl = document.getElementById('get-language') as HTMLSelectElement | null;
    const voiceEl = document.getElementById('voice') as HTMLSelectElement | null;
    const rateEl = document.getElementById('rate') as HTMLInputElement | null;
    const pitchEl = document.getElementById('pitch') as HTMLInputElement | null;

    const langName = langEl?.value || this.currentLanguage;
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
   * @deprecated Since 3.1.0, use ttsSettingsApp() Alpine component
   */
  populateVoiceList(): void {
    const voices = window.speechSynthesis.getVoices();
    const voiceSelect = document.getElementById('voice') as HTMLSelectElement | null;
    if (!voiceSelect) return;
    voiceSelect.innerHTML = '';
    const languageCode = this.getLanguageCode();

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
   * @deprecated Since 3.1.0, use ttsSettingsApp() Alpine component
   */
  clickCancel(): void {
    lwtFormCheck.resetDirty();
    location.href = '/admin/settings';
  }
};

/**
 * Initialize TTS settings from the page.
 * @deprecated Since 3.1.0, use ttsSettingsApp() Alpine component with x-data
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
    const form = document.querySelector('form.validate');
    config = {
      currentLanguageCode: form?.getAttribute('data-current-language') || ''
    };
  }

  ttsSettings.init(config);
  ttsSettings.autoSetCurrentLanguage();
  ttsSettings.presetTTSData();
  ttsSettings.populateVoiceList();

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

  const form = document.querySelector('form.validate') as HTMLFormElement | null;
  if (form) {
    form.addEventListener('submit', () => {
      ttsSettings.saveSettings();
    });
  }
}

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
 * @deprecated Since 3.1.0, use ttsSettingsApp() Alpine component
 */
export function saveSettings(): void {
  return ttsSettings.saveSettings();
}
