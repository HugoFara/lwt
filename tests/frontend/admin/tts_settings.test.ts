/**
 * Tests for tts_settings.ts - Text-to-Speech settings management
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  ttsSettings,
  ttsSettingsApp,
  initTTSSettings,
  getLanguageCode,
  readingDemo,
  presetTTSData,
  populateVoiceList,
  type TTSSettingsConfig
} from '../../../src/frontend/js/modules/admin/pages/tts_settings';

// Use vi.hoisted to ensure mock function is available during hoisting
const mockGetTTSSettingsWithMigration = vi.hoisted(() => vi.fn());
const mockSaveTTSSettings = vi.hoisted(() => vi.fn());

// Mock dependencies
vi.mock('../../../src/frontend/js/shared/utils/cookies', () => ({
  getCookie: vi.fn()
}));

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
    // Reset ttsSettings state
    ttsSettings.currentLanguage = '';
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // ttsSettingsApp Alpine Component Tests
  // ===========================================================================

  describe('ttsSettingsApp (Alpine component)', () => {
    beforeEach(() => {
      // Mock speechSynthesis
      (window as any).speechSynthesis = {
        getVoices: vi.fn().mockReturnValue([]),
        onvoiceschanged: null
      };
      // Mock location
      delete (window as any).location;
      (window as any).location = new URL('http://localhost/settings');
    });

    it('initializes with default config', () => {
      const app = ttsSettingsApp();

      expect(app.currentLanguage).toBe('');
      expect(app.voices).toEqual([]);
      expect(app.selectedVoice).toBe('');
      expect(app.rate).toBe(1);
      expect(app.pitch).toBe(1);
      expect(app.demoText).toBe('Lorem ipsum dolor sit amet...');
      expect(app.voicesLoading).toBe(true);
    });

    it('initializes with provided config', () => {
      const app = ttsSettingsApp({ currentLanguageCode: 'en-US' });

      expect(app.currentLanguage).toBe('en-US');
    });

    it('auto-sets language from URL parameter', () => {
      (window as any).location = new URL('http://localhost/settings?lang=fr-FR');
      const app = ttsSettingsApp({ currentLanguageCode: 'en-US' });

      app.autoSetCurrentLanguage();

      expect(app.currentLanguage).toBe('fr-FR');
    });

    it('loads saved settings from localStorage', () => {
      mockGetTTSSettingsWithMigration.mockReturnValue({
        voice: 'Google UK English',
        rate: 1.5,
        pitch: 0.8
      });

      const app = ttsSettingsApp({ currentLanguageCode: 'en-GB' });
      app.loadSavedSettings();

      expect(app.selectedVoice).toBe('Google UK English');
      expect(app.rate).toBe(1.5);
      expect(app.pitch).toBe(0.8);
    });

    it('does not load settings when no language is set', () => {
      mockGetTTSSettingsWithMigration.mockReturnValue({
        voice: 'Test Voice',
        rate: 2,
        pitch: 2
      });

      const app = ttsSettingsApp({ currentLanguageCode: '' });
      app.loadSavedSettings();

      expect(app.selectedVoice).toBe('');
      expect(app.rate).toBe(1);
      expect(app.pitch).toBe(1);
    });

    it('populates voice list from speechSynthesis', () => {
      const mockVoices = [
        { name: 'Google US English', lang: 'en-US', default: false },
        { name: 'Google UK English', lang: 'en-US', default: false },
        { name: 'Google French', lang: 'fr-FR', default: false }
      ];
      (window.speechSynthesis.getVoices as any).mockReturnValue(mockVoices);

      const app = ttsSettingsApp({ currentLanguageCode: 'en-US' });
      app.populateVoiceList();

      expect(app.voices.length).toBe(2);
      expect(app.voices[0].name).toBe('Google US English');
      expect(app.voices[1].name).toBe('Google UK English');
    });

    it('shows all voices when no language matches', () => {
      const mockVoices = [
        { name: 'Google US English', lang: 'en-US', default: false },
        { name: 'Google French', lang: 'fr-FR', default: false }
      ];
      (window.speechSynthesis.getVoices as any).mockReturnValue(mockVoices);

      const app = ttsSettingsApp({ currentLanguageCode: 'de-DE' });
      app.populateVoiceList();

      // Should show all voices since none match
      expect(app.voices.length).toBe(2);
    });

    it('includes default voice regardless of language', () => {
      const mockVoices = [
        { name: 'System Default', lang: 'en-US', default: true },
        { name: 'Google French', lang: 'fr-FR', default: false }
      ];
      (window.speechSynthesis.getVoices as any).mockReturnValue(mockVoices);

      const app = ttsSettingsApp({ currentLanguageCode: 'de-DE' });
      app.populateVoiceList();

      // Should include default voice even though language doesn't match
      const defaultVoice = app.voices.find(v => v.isDefault);
      expect(defaultVoice).toBeDefined();
      expect(defaultVoice?.name).toBe('System Default');
    });

    it('plays demo with current settings', async () => {
      const { readTextAloud } = await import('../../../src/frontend/js/shared/utils/user_interactions');

      const app = ttsSettingsApp({ currentLanguageCode: 'en-US' });
      app.demoText = 'Hello world';
      app.rate = 1.2;
      app.pitch = 0.9;
      app.selectedVoice = 'Google US English';

      app.playDemo();

      expect(readTextAloud).toHaveBeenCalledWith(
        'Hello world',
        'en-US',
        1.2,
        0.9,
        'Google US English'
      );
    });

    it('plays demo with undefined voice when none selected', async () => {
      const { readTextAloud } = await import('../../../src/frontend/js/shared/utils/user_interactions');

      const app = ttsSettingsApp({ currentLanguageCode: 'en-US' });
      app.selectedVoice = '';

      app.playDemo();

      expect(readTextAloud).toHaveBeenCalledWith(
        expect.any(String),
        'en-US',
        expect.any(Number),
        expect.any(Number),
        undefined
      );
    });

    it('saves settings to localStorage', () => {
      const app = ttsSettingsApp({ currentLanguageCode: 'en-US' });
      app.selectedVoice = 'Test Voice';
      app.rate = 1.5;
      app.pitch = 0.8;

      app.saveSettings();

      expect(mockSaveTTSSettings).toHaveBeenCalledWith('en-US', {
        voice: 'Test Voice',
        rate: 1.5,
        pitch: 0.8
      });
    });

    it('does not save when no language is set', () => {
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      const app = ttsSettingsApp({ currentLanguageCode: '' });
      app.saveSettings();

      expect(mockSaveTTSSettings).not.toHaveBeenCalled();
      expect(consoleSpy).toHaveBeenCalledWith('Cannot save TTS settings: no language selected');
    });

    it('handles cancel correctly', async () => {
      const { lwtFormCheck } = await import('../../../src/frontend/js/shared/forms/unloadformcheck');

      const originalLocation = window.location;
      delete (window as any).location;
      (window as any).location = { href: '' };

      const app = ttsSettingsApp({ currentLanguageCode: 'en-US' });
      app.cancel();

      expect(lwtFormCheck.resetDirty).toHaveBeenCalled();
      expect(window.location.href).toBe('/admin/settings');

      window.location = originalLocation;
    });

    it('getVoiceDisplayName returns correct label for default voice', () => {
      const app = ttsSettingsApp();

      expect(app.getVoiceDisplayName({ name: 'Test', lang: 'en', isDefault: true }))
        .toBe('Test -- DEFAULT');
    });

    it('getVoiceDisplayName returns name for non-default voice', () => {
      const app = ttsSettingsApp();

      expect(app.getVoiceDisplayName({ name: 'Test', lang: 'en', isDefault: false }))
        .toBe('Test');
    });

    it('onLanguageChange updates voices and reloads settings', () => {
      const mockVoices = [
        { name: 'Google French', lang: 'fr-FR', default: false }
      ];
      (window.speechSynthesis.getVoices as any).mockReturnValue(mockVoices);
      mockGetTTSSettingsWithMigration.mockReturnValue({
        voice: 'Google French',
        rate: 1.2,
        pitch: 1.1
      });

      const app = ttsSettingsApp({ currentLanguageCode: 'en-US' });
      app.currentLanguage = 'fr-FR';
      app.onLanguageChange();

      expect(app.voices.length).toBe(1);
      expect(app.selectedVoice).toBe('Google French');
    });
  });

  // ===========================================================================
  // Legacy ttsSettings Object Tests
  // ===========================================================================

  describe('ttsSettings.init (legacy)', () => {
    it('sets currentLanguage from config', () => {
      const config: TTSSettingsConfig = {
        currentLanguageCode: 'en-US'
      };

      ttsSettings.init(config);

      expect(ttsSettings.currentLanguage).toBe('en-US');
    });

    it('handles empty language code', () => {
      const config: TTSSettingsConfig = {
        currentLanguageCode: ''
      };

      ttsSettings.init(config);

      expect(ttsSettings.currentLanguage).toBe('');
    });
  });

  describe('ttsSettings.autoSetCurrentLanguage (legacy)', () => {
    const originalLocation = window.location;

    beforeEach(() => {
      delete (window as any).location;
    });

    afterEach(() => {
      window.location = originalLocation;
    });

    it('sets currentLanguage from URL lang parameter', () => {
      (window as any).location = new URL('http://localhost/tts?lang=fr-FR');

      ttsSettings.autoSetCurrentLanguage();

      expect(ttsSettings.currentLanguage).toBe('fr-FR');
    });

    it('does not change currentLanguage when lang parameter is missing', () => {
      (window as any).location = new URL('http://localhost/tts');
      ttsSettings.currentLanguage = 'original';

      ttsSettings.autoSetCurrentLanguage();

      expect(ttsSettings.currentLanguage).toBe('original');
    });

    it('handles empty lang parameter', () => {
      (window as any).location = new URL('http://localhost/tts?lang=');

      ttsSettings.autoSetCurrentLanguage();

      expect(ttsSettings.currentLanguage).toBe('');
    });
  });

  describe('ttsSettings.getLanguageCode (legacy)', () => {
    it('returns value from #get-language select', () => {
      document.body.innerHTML = `
        <select id="get-language">
          <option value="en" selected>English</option>
          <option value="fr">French</option>
        </select>
      `;

      const result = ttsSettings.getLanguageCode();

      expect(result).toBe('en');
    });

    it('returns empty string when element does not exist', () => {
      document.body.innerHTML = '';

      const result = ttsSettings.getLanguageCode();

      expect(result).toBe('');
    });

    it('returns empty string when select has no value', () => {
      document.body.innerHTML = `
        <select id="get-language">
          <option value="">Select language</option>
        </select>
      `;

      const result = ttsSettings.getLanguageCode();

      expect(result).toBe('');
    });
  });

  describe('ttsSettings.readingDemo (legacy)', () => {
    it('calls readTextAloud with form values', async () => {
      const { readTextAloud } = await import('../../../src/frontend/js/shared/utils/user_interactions');

      document.body.innerHTML = `
        <select id="get-language">
          <option value="en" selected>English</option>
        </select>
        <input id="tts-demo" value="Hello world">
        <input id="rate" value="1.2">
        <input id="pitch" value="0.9">
        <select id="voice">
          <option value="Google US English" selected>Google US English</option>
        </select>
      `;

      ttsSettings.readingDemo();

      expect(readTextAloud).toHaveBeenCalledWith(
        'Hello world',
        'en',
        1.2,
        0.9,
        'Google US English'
      );
    });

    it('uses default values when form elements are empty', async () => {
      const { readTextAloud } = await import('../../../src/frontend/js/shared/utils/user_interactions');

      document.body.innerHTML = `
        <select id="get-language"><option value="" selected></option></select>
        <input id="tts-demo" value="">
        <input id="rate" value="">
        <input id="pitch" value="">
        <select id="voice"><option value="" selected></option></select>
      `;

      ttsSettings.readingDemo();

      expect(readTextAloud).toHaveBeenCalledWith(
        '',
        '',
        1,
        1,
        undefined
      );
    });
  });

  describe('ttsSettings.presetTTSData (legacy)', () => {
    it('sets form values from localStorage', async () => {
      mockGetTTSSettingsWithMigration.mockReturnValue({
        voice: 'Google US English',
        rate: 1.5,
        pitch: 1.1
      });

      document.body.innerHTML = `
        <select id="get-language">
          <option value="">Select</option>
          <option value="en-US">English US</option>
        </select>
        <select id="voice">
          <option value="">Select</option>
          <option value="Google US English">Google US English</option>
        </select>
        <input id="rate" value="">
        <input id="pitch" value="">
      `;

      ttsSettings.currentLanguage = 'en-US';
      ttsSettings.presetTTSData();

      expect((document.getElementById('get-language') as HTMLSelectElement).value).toBe('en-US');
      expect((document.getElementById('voice') as HTMLSelectElement).value).toBe('Google US English');
      expect((document.getElementById('rate') as HTMLInputElement).value).toBe('1.5');
      expect((document.getElementById('pitch') as HTMLInputElement).value).toBe('1.1');
    });

    it('uses default values when localStorage is empty', async () => {
      mockGetTTSSettingsWithMigration.mockReturnValue({});

      document.body.innerHTML = `
        <select id="get-language">
          <option value="">Select</option>
          <option value="fr">French</option>
        </select>
        <select id="voice"><option value=""></option></select>
        <input id="rate" value="">
        <input id="pitch" value="">
      `;

      ttsSettings.currentLanguage = 'fr';
      ttsSettings.presetTTSData();

      expect((document.getElementById('get-language') as HTMLSelectElement).value).toBe('fr');
      expect((document.getElementById('voice') as HTMLSelectElement).value).toBe('');
      expect((document.getElementById('rate') as HTMLInputElement).value).toBe('1');
      expect((document.getElementById('pitch') as HTMLInputElement).value).toBe('1');
    });
  });

  describe('ttsSettings.populateVoiceList (legacy)', () => {
    beforeEach(() => {
      (window as any).speechSynthesis = {
        getVoices: vi.fn().mockReturnValue([])
      };
    });

    it('populates voice options for matching language', () => {
      const mockVoices = [
        { name: 'Google US English', lang: 'en', default: false },
        { name: 'Google UK English', lang: 'en', default: false },
        { name: 'Google French', lang: 'fr', default: false }
      ];
      (window.speechSynthesis.getVoices as any).mockReturnValue(mockVoices);

      document.body.innerHTML = `
        <select id="get-language">
          <option value="en" selected>English</option>
        </select>
        <select id="voice"></select>
      `;

      ttsSettings.populateVoiceList();

      const options = document.querySelectorAll('#voice option');
      expect(options.length).toBe(2);
      expect(options[0].textContent).toBe('Google US English');
      expect(options[1].textContent).toBe('Google UK English');
    });

    it('marks default voice with label', () => {
      const mockVoices = [
        { name: 'System Default', lang: 'en', default: true },
        { name: 'Google US English', lang: 'en', default: false }
      ];
      (window.speechSynthesis.getVoices as any).mockReturnValue(mockVoices);

      document.body.innerHTML = `
        <select id="get-language">
          <option value="de" selected>German</option>
        </select>
        <select id="voice"></select>
      `;

      ttsSettings.populateVoiceList();

      const options = document.querySelectorAll('#voice option');
      expect(options[0].textContent).toContain('-- DEFAULT');
    });

    it('includes default voice even when language does not match', () => {
      const mockVoices = [
        { name: 'System Default', lang: 'en', default: true },
        { name: 'Google French', lang: 'fr', default: false }
      ];
      (window.speechSynthesis.getVoices as any).mockReturnValue(mockVoices);

      document.body.innerHTML = `
        <select id="get-language">
          <option value="de" selected>German</option>
        </select>
        <select id="voice"></select>
      `;

      ttsSettings.populateVoiceList();

      const options = document.querySelectorAll('#voice option');
      expect(options.length).toBe(1);
      expect(options[0].textContent).toContain('System Default');
    });

    it('sets data attributes on voice options', () => {
      const mockVoices = [
        { name: 'Google US English', lang: 'en-US', default: false }
      ];
      (window.speechSynthesis.getVoices as any).mockReturnValue(mockVoices);

      document.body.innerHTML = `
        <select id="get-language">
          <option value="en-US" selected>English</option>
        </select>
        <select id="voice"></select>
      `;

      ttsSettings.populateVoiceList();

      const option = document.querySelector('#voice option') as HTMLOptionElement;
      expect(option.getAttribute('data-lang')).toBe('en-US');
      expect(option.getAttribute('data-name')).toBe('Google US English');
    });

    it('clears existing options before populating', () => {
      const mockVoices = [
        { name: 'Google US English', lang: 'en', default: false }
      ];
      (window.speechSynthesis.getVoices as any).mockReturnValue(mockVoices);

      document.body.innerHTML = `
        <select id="get-language">
          <option value="en" selected>English</option>
        </select>
        <select id="voice">
          <option value="old">Old Option</option>
        </select>
      `;

      ttsSettings.populateVoiceList();

      const options = document.querySelectorAll('#voice option');
      expect(options.length).toBe(1);
      expect(options[0].textContent).not.toBe('Old Option');
    });
  });

  describe('ttsSettings.clickCancel (legacy)', () => {
    it('resets form dirty state and redirects', async () => {
      const { lwtFormCheck } = await import('../../../src/frontend/js/shared/forms/unloadformcheck');

      const originalLocation = window.location;
      delete (window as any).location;
      (window as any).location = { href: '' };

      ttsSettings.clickCancel();

      expect(lwtFormCheck.resetDirty).toHaveBeenCalled();
      expect(window.location.href).toBe('/admin/settings');

      window.location = originalLocation;
    });
  });

  // ===========================================================================
  // initTTSSettings Tests (legacy)
  // ===========================================================================

  describe('initTTSSettings (legacy)', () => {
    beforeEach(() => {
      (window as any).speechSynthesis = {
        getVoices: vi.fn().mockReturnValue([])
      };
      delete (window as any).location;
      (window as any).location = new URL('http://localhost/tts');
    });

    it('initializes from JSON config element', async () => {
      const { getCookie } = await import('../../../src/frontend/js/shared/utils/cookies');
      (getCookie as any).mockReturnValue('');

      document.body.innerHTML = `
        <script type="application/json" id="tts-settings-config">
          {"currentLanguageCode": "es-ES"}
        </script>
        <select id="get-language"><option value=""></option></select>
        <select id="voice"></select>
        <input id="rate" value="">
        <input id="pitch" value="">
      `;

      initTTSSettings();

      expect(ttsSettings.currentLanguage).toBe('es-ES');
    });

    it('falls back to form data attribute when config element is missing', async () => {
      const { getCookie } = await import('../../../src/frontend/js/shared/utils/cookies');
      (getCookie as any).mockReturnValue('');

      document.body.innerHTML = `
        <form class="validate" data-current-language="de-DE">
          <select id="get-language"><option value=""></option></select>
          <select id="voice"></select>
          <input id="rate" value="">
          <input id="pitch" value="">
        </form>
      `;

      initTTSSettings();

      expect(ttsSettings.currentLanguage).toBe('de-DE');
    });

    it('handles invalid JSON in config element', async () => {
      const { getCookie } = await import('../../../src/frontend/js/shared/utils/cookies');
      (getCookie as any).mockReturnValue('');
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      document.body.innerHTML = `
        <script type="application/json" id="tts-settings-config">
          {invalid json}
        </script>
        <select id="get-language"><option value=""></option></select>
        <select id="voice"></select>
        <input id="rate" value="">
        <input id="pitch" value="">
      `;

      initTTSSettings();

      expect(consoleSpy).toHaveBeenCalled();
      expect(ttsSettings.currentLanguage).toBe('');
    });

    it('sets up language select change listener', async () => {
      const { getCookie } = await import('../../../src/frontend/js/shared/utils/cookies');
      (getCookie as any).mockReturnValue('');

      document.body.innerHTML = `
        <script type="application/json" id="tts-settings-config">
          {"currentLanguageCode": "en"}
        </script>
        <select id="get-language">
          <option value="en" selected>English</option>
          <option value="fr">French</option>
        </select>
        <select id="voice"></select>
        <input id="rate" value="">
        <input id="pitch" value="">
      `;

      initTTSSettings();

      const populateSpy = vi.spyOn(ttsSettings, 'populateVoiceList');
      const languageSelect = document.getElementById('get-language')!;
      languageSelect.dispatchEvent(new Event('change'));

      expect(populateSpy).toHaveBeenCalled();
    });

    it('sets up demo button click listener', async () => {
      const { getCookie } = await import('../../../src/frontend/js/shared/utils/cookies');
      const { readTextAloud } = await import('../../../src/frontend/js/shared/utils/user_interactions');
      (getCookie as any).mockReturnValue('');

      document.body.innerHTML = `
        <script type="application/json" id="tts-settings-config">
          {"currentLanguageCode": "en"}
        </script>
        <select id="get-language"><option value="en" selected></option></select>
        <select id="voice"><option value="test-voice" selected></option></select>
        <input id="tts-demo" value="Test text">
        <input id="rate" value="1">
        <input id="pitch" value="1">
        <button data-action="tts-demo">Demo</button>
      `;

      initTTSSettings();
      const demoButton = document.querySelector('[data-action="tts-demo"]') as HTMLElement;
      demoButton.dispatchEvent(new Event('click', { bubbles: true }));

      expect(readTextAloud).toHaveBeenCalled();
    });

    it('sets up cancel button click listener', async () => {
      const { getCookie } = await import('../../../src/frontend/js/shared/utils/cookies');
      const { lwtFormCheck } = await import('../../../src/frontend/js/shared/forms/unloadformcheck');
      (getCookie as any).mockReturnValue('');

      const originalLocation = window.location;
      delete (window as any).location;
      (window as any).location = { href: '' };

      document.body.innerHTML = `
        <script type="application/json" id="tts-settings-config">
          {"currentLanguageCode": "en"}
        </script>
        <select id="get-language"><option value=""></option></select>
        <select id="voice"></select>
        <input id="rate" value="">
        <input id="pitch" value="">
        <button data-action="tts-cancel">Cancel</button>
      `;

      initTTSSettings();
      const cancelButton = document.querySelector('[data-action="tts-cancel"]') as HTMLElement;
      cancelButton.dispatchEvent(new Event('click', { bubbles: true }));

      expect(lwtFormCheck.resetDirty).toHaveBeenCalled();

      window.location = originalLocation;
    });
  });

  // ===========================================================================
  // Deprecated Function Tests
  // ===========================================================================

  describe('Deprecated Functions', () => {
    beforeEach(() => {
      (window as any).speechSynthesis = {
        getVoices: vi.fn().mockReturnValue([])
      };
    });

    it('getLanguageCode calls ttsSettings.getLanguageCode', () => {
      document.body.innerHTML = `
        <select id="get-language">
          <option value="ja" selected>Japanese</option>
        </select>
      `;

      expect(getLanguageCode()).toBe('ja');
    });

    it('readingDemo calls ttsSettings.readingDemo', async () => {
      const { readTextAloud } = await import('../../../src/frontend/js/shared/utils/user_interactions');

      document.body.innerHTML = `
        <select id="get-language"><option value="en" selected></option></select>
        <input id="tts-demo" value="Test">
        <input id="rate" value="1">
        <input id="pitch" value="1">
        <select id="voice"><option value="" selected></option></select>
      `;

      readingDemo();

      expect(readTextAloud).toHaveBeenCalled();
    });

    it('presetTTSData calls ttsSettings.presetTTSData', async () => {
      const { getCookie } = await import('../../../src/frontend/js/shared/utils/cookies');
      (getCookie as any).mockReturnValue('');

      document.body.innerHTML = `
        <select id="get-language">
          <option value="">Select</option>
          <option value="test">Test</option>
        </select>
        <select id="voice"><option value=""></option></select>
        <input id="rate" value="">
        <input id="pitch" value="">
      `;

      ttsSettings.currentLanguage = 'test';
      presetTTSData();

      expect((document.getElementById('get-language') as HTMLSelectElement).value).toBe('test');
    });

    it('populateVoiceList calls ttsSettings.populateVoiceList', () => {
      document.body.innerHTML = `
        <select id="get-language"><option value="en" selected></option></select>
        <select id="voice"></select>
      `;

      const spy = vi.spyOn(ttsSettings, 'populateVoiceList');
      populateVoiceList();

      expect(spy).toHaveBeenCalled();
    });
  });
});
