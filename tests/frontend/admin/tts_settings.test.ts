/**
 * Tests for tts_settings.ts - Text-to-Speech settings Alpine.js component
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  ttsSettingsApp,
  type TTSSettingsConfig
} from '../../../src/frontend/js/modules/admin/pages/tts_settings';

// Use vi.hoisted to ensure mock function is available during hoisting
const mockGetTTSSettingsWithMigration = vi.hoisted(() => vi.fn());
const mockSaveTTSSettings = vi.hoisted(() => vi.fn());

// Mock dependencies
vi.mock('../../../src/frontend/js/shared/utils/tts_storage', () => ({
  getTTSSettingsWithMigration: mockGetTTSSettingsWithMigration,
  saveTTSSettings: mockSaveTTSSettings
}));

vi.mock('../../../src/frontend/js/shared/utils/user_interactions', () => ({
  readTextAloud: vi.fn()
}));

vi.mock('../../../src/frontend/js/shared/forms/unloadformcheck', () => ({
  lwtFormCheck: {
    resetDirty: vi.fn(),
    askBeforeExit: vi.fn()
  }
}));

describe('tts_settings.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();

    // Mock speechSynthesis
    (window as any).speechSynthesis = {
      getVoices: vi.fn().mockReturnValue([]),
      onvoiceschanged: null
    };

    // Reset URL
    Object.defineProperty(window, 'location', {
      value: { search: '', href: '' },
      writable: true
    });

    // Reset localStorage mock
    mockGetTTSSettingsWithMigration.mockReturnValue({});
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ===========================================================================
  // ttsSettingsApp Tests
  // ===========================================================================

  describe('ttsSettingsApp', () => {
    it('initializes with default config', () => {
      const component = ttsSettingsApp();

      expect(component.currentLanguage).toBe('');
      expect(component.voices).toEqual([]);
      expect(component.selectedVoice).toBe('');
      expect(component.rate).toBe(1);
      expect(component.pitch).toBe(1);
      expect(component.voicesLoading).toBe(true);
    });

    it('initializes with provided config', () => {
      const config: TTSSettingsConfig = { currentLanguageCode: 'en' };
      const component = ttsSettingsApp(config);

      expect(component.currentLanguage).toBe('en');
    });

    describe('autoSetCurrentLanguage', () => {
      it('sets language from URL parameter', () => {
        Object.defineProperty(window, 'location', {
          value: { search: '?lang=de' },
          writable: true
        });

        const component = ttsSettingsApp();
        component.autoSetCurrentLanguage();

        expect(component.currentLanguage).toBe('de');
      });

      it('does not change language when lang parameter is missing', () => {
        Object.defineProperty(window, 'location', {
          value: { search: '' },
          writable: true
        });

        const config: TTSSettingsConfig = { currentLanguageCode: 'en' };
        const component = ttsSettingsApp(config);
        component.autoSetCurrentLanguage();

        expect(component.currentLanguage).toBe('en');
      });
    });

    describe('loadSavedSettings', () => {
      it('loads settings from localStorage when language is set', () => {
        mockGetTTSSettingsWithMigration.mockReturnValue({
          voice: 'Google UK English Male',
          rate: 1.5,
          pitch: 0.8
        });

        const config: TTSSettingsConfig = { currentLanguageCode: 'en' };
        const component = ttsSettingsApp(config);
        component.loadSavedSettings();

        expect(mockGetTTSSettingsWithMigration).toHaveBeenCalledWith('en');
        expect(component.selectedVoice).toBe('Google UK English Male');
        expect(component.rate).toBe(1.5);
        expect(component.pitch).toBe(0.8);
      });

      it('does not load settings when language is empty', () => {
        const component = ttsSettingsApp();
        component.loadSavedSettings();

        expect(mockGetTTSSettingsWithMigration).not.toHaveBeenCalled();
      });
    });

    describe('populateVoiceList', () => {
      it('filters voices by current language', () => {
        (window as any).speechSynthesis = {
          getVoices: vi.fn().mockReturnValue([
            { name: 'English Voice', lang: 'en', default: false },
            { name: 'German Voice', lang: 'de', default: false },
            { name: 'Default Voice', lang: 'en', default: true }
          ])
        };

        const config: TTSSettingsConfig = { currentLanguageCode: 'en' };
        const component = ttsSettingsApp(config);
        component.populateVoiceList();

        expect(component.voices.length).toBe(2);
        expect(component.voices.some(v => v.name === 'English Voice')).toBe(true);
        expect(component.voices.some(v => v.name === 'Default Voice')).toBe(true);
        expect(component.voices.some(v => v.name === 'German Voice')).toBe(false);
      });

      it('includes default voice even when language does not match', () => {
        (window as any).speechSynthesis = {
          getVoices: vi.fn().mockReturnValue([
            { name: 'Default Voice', lang: 'en', default: true }
          ])
        };

        const config: TTSSettingsConfig = { currentLanguageCode: 'de' };
        const component = ttsSettingsApp(config);
        component.populateVoiceList();

        expect(component.voices.some(v => v.isDefault)).toBe(true);
      });

      it('shows all voices when no matching voices found', () => {
        (window as any).speechSynthesis = {
          getVoices: vi.fn().mockReturnValue([
            { name: 'English Voice', lang: 'en', default: false }
          ])
        };

        const config: TTSSettingsConfig = { currentLanguageCode: 'de' };
        const component = ttsSettingsApp(config);
        component.populateVoiceList();

        // When no matching voices, shows all
        expect(component.voices.length).toBe(1);
        expect(component.voices[0].name).toBe('English Voice');
      });
    });

    describe('onLanguageChange', () => {
      it('repopulates voices and reloads settings', () => {
        const config: TTSSettingsConfig = { currentLanguageCode: 'en' };
        const component = ttsSettingsApp(config);

        const populateSpy = vi.spyOn(component, 'populateVoiceList');
        const loadSpy = vi.spyOn(component, 'loadSavedSettings');

        component.onLanguageChange();

        expect(populateSpy).toHaveBeenCalled();
        expect(loadSpy).toHaveBeenCalled();
      });
    });

    describe('playDemo', () => {
      it('calls readTextAloud with current settings', async () => {
        const { readTextAloud } = await import('../../../src/frontend/js/shared/utils/user_interactions');

        const config: TTSSettingsConfig = { currentLanguageCode: 'en' };
        const component = ttsSettingsApp(config);
        component.selectedVoice = 'Test Voice';
        component.rate = 1.5;
        component.pitch = 0.9;
        component.demoText = 'Test text';

        component.playDemo();

        expect(readTextAloud).toHaveBeenCalledWith(
          'Test text',
          'en',
          1.5,
          0.9,
          'Test Voice'
        );
      });

      it('passes undefined voice when none selected', async () => {
        const { readTextAloud } = await import('../../../src/frontend/js/shared/utils/user_interactions');

        const config: TTSSettingsConfig = { currentLanguageCode: 'en' };
        const component = ttsSettingsApp(config);
        component.selectedVoice = '';

        component.playDemo();

        expect(readTextAloud).toHaveBeenCalledWith(
          expect.any(String),
          'en',
          expect.any(Number),
          expect.any(Number),
          undefined
        );
      });
    });

    describe('saveSettings', () => {
      it('saves settings to localStorage', () => {
        const config: TTSSettingsConfig = { currentLanguageCode: 'en' };
        const component = ttsSettingsApp(config);
        component.selectedVoice = 'Test Voice';
        component.rate = 1.2;
        component.pitch = 0.8;

        component.saveSettings();

        expect(mockSaveTTSSettings).toHaveBeenCalledWith('en', {
          voice: 'Test Voice',
          rate: 1.2,
          pitch: 0.8
        });
      });

      it('does not save when no language is set', () => {
        const component = ttsSettingsApp();

        component.saveSettings();

        expect(mockSaveTTSSettings).not.toHaveBeenCalled();
      });
    });

    describe('cancel', () => {
      it('resets form dirty state and redirects', async () => {
        const { lwtFormCheck } = await import('../../../src/frontend/js/shared/forms/unloadformcheck');

        const originalLocation = window.location;
        Object.defineProperty(window, 'location', {
          value: { href: '' },
          writable: true
        });

        const component = ttsSettingsApp();
        component.cancel();

        expect(lwtFormCheck.resetDirty).toHaveBeenCalled();
        expect(window.location.href).toBe('/admin/settings');

        window.location = originalLocation;
      });
    });

    describe('getVoiceDisplayName', () => {
      it('returns name with DEFAULT label for default voice', () => {
        const component = ttsSettingsApp();
        const voice = { name: 'Test Voice', lang: 'en', isDefault: true };

        expect(component.getVoiceDisplayName(voice)).toBe('Test Voice -- DEFAULT');
      });

      it('returns plain name for non-default voice', () => {
        const component = ttsSettingsApp();
        const voice = { name: 'Test Voice', lang: 'en', isDefault: false };

        expect(component.getVoiceDisplayName(voice)).toBe('Test Voice');
      });
    });
  });
});
