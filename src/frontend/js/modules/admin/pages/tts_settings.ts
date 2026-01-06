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
import { readTextAloud } from '@shared/utils/user_interactions';
import { getTTSSettingsWithMigration, saveTTSSettings } from '@shared/utils/tts_storage';
import { lwtFormCheck } from '@shared/forms/unloadformcheck';

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
