/**
 * Tests for tts_settings.ts - Text-to-Speech settings management
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  ttsSettings,
  initTTSSettings,
  getLanguageCode,
  readingDemo,
  presetTTSData,
  populateVoiceList,
  type TTSSettingsConfig
} from '../../../src/frontend/js/admin/tts_settings';

// Mock dependencies
vi.mock('../../../src/frontend/js/core/cookies', () => ({
  getCookie: vi.fn()
}));

vi.mock('../../../src/frontend/js/core/user_interactions', () => ({
  readTextAloud: vi.fn()
}));

vi.mock('../../../src/frontend/js/forms/unloadformcheck', () => ({
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
  // ttsSettings.init Tests
  // ===========================================================================

  describe('ttsSettings.init', () => {
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

  // ===========================================================================
  // ttsSettings.autoSetCurrentLanguage Tests
  // ===========================================================================

  describe('ttsSettings.autoSetCurrentLanguage', () => {
    const originalLocation = window.location;

    beforeEach(() => {
      // Mock window.location
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

  // ===========================================================================
  // ttsSettings.getLanguageCode Tests
  // ===========================================================================

  describe('ttsSettings.getLanguageCode', () => {
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

  // ===========================================================================
  // ttsSettings.readingDemo Tests
  // ===========================================================================

  describe('ttsSettings.readingDemo', () => {
    it('calls readTextAloud with form values', async () => {
      const { readTextAloud } = await import('../../../src/frontend/js/core/user_interactions');

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
      const { readTextAloud } = await import('../../../src/frontend/js/core/user_interactions');

      document.body.innerHTML = `
        <select id="get-language"><option value="" selected></option></select>
        <input id="tts-demo" value="">
        <input id="rate" value="">
        <input id="pitch" value="">
        <select id="voice"><option value="" selected></option></select>
      `;

      ttsSettings.readingDemo();

      // When rate/pitch inputs are empty, parseFloat('') || '1' gives '1', so parseFloat('1') = 1
      expect(readTextAloud).toHaveBeenCalledWith(
        '',
        '',
        1,
        1,
        undefined
      );
    });
  });

  // ===========================================================================
  // ttsSettings.presetTTSData Tests
  // ===========================================================================

  describe('ttsSettings.presetTTSData', () => {
    it('sets form values from cookies', async () => {
      const { getCookie } = await import('../../../src/frontend/js/core/cookies');
      (getCookie as any).mockImplementation((key: string) => {
        const cookies: Record<string, string> = {
          'tts[en-USRegName]': 'Google US English',
          'tts[en-USRate]': '1.5',
          'tts[en-USPitch]': '1.1'
        };
        return cookies[key] || '';
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

    it('uses default values when cookies are empty', async () => {
      const { getCookie } = await import('../../../src/frontend/js/core/cookies');
      (getCookie as any).mockReturnValue('');

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

  // ===========================================================================
  // ttsSettings.populateVoiceList Tests
  // ===========================================================================

  describe('ttsSettings.populateVoiceList', () => {
    beforeEach(() => {
      // Mock speechSynthesis
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

      // Default voice is always included regardless of language
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
      // Only default voice should be included (doesn't match 'de')
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

  // ===========================================================================
  // ttsSettings.clickCancel Tests
  // ===========================================================================

  describe('ttsSettings.clickCancel', () => {
    it('resets form dirty state and redirects', async () => {
      const { lwtFormCheck } = await import('../../../src/frontend/js/forms/unloadformcheck');

      // Mock location.href
      const originalLocation = window.location;
      delete (window as any).location;
      (window as any).location = { href: '' };

      ttsSettings.clickCancel();

      expect(lwtFormCheck.resetDirty).toHaveBeenCalled();
      expect(window.location.href).toBe('/admin/settings/tts');

      window.location = originalLocation;
    });
  });

  // ===========================================================================
  // initTTSSettings Tests
  // ===========================================================================

  describe('initTTSSettings', () => {
    beforeEach(() => {
      // Mock speechSynthesis
      (window as any).speechSynthesis = {
        getVoices: vi.fn().mockReturnValue([])
      };
      // Mock location
      delete (window as any).location;
      (window as any).location = new URL('http://localhost/tts');
    });

    it('initializes from JSON config element', async () => {
      const { getCookie } = await import('../../../src/frontend/js/core/cookies');
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
      const { getCookie } = await import('../../../src/frontend/js/core/cookies');
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
      const { getCookie } = await import('../../../src/frontend/js/core/cookies');
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
      const { getCookie } = await import('../../../src/frontend/js/core/cookies');
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
      // Dispatch native event to trigger the change listener
      const languageSelect = document.getElementById('get-language')!;
      languageSelect.dispatchEvent(new Event('change'));

      expect(populateSpy).toHaveBeenCalled();
    });

    it('sets up demo button click listener', async () => {
      const { getCookie } = await import('../../../src/frontend/js/core/cookies');
      const { readTextAloud } = await import('../../../src/frontend/js/core/user_interactions');
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
      const { getCookie } = await import('../../../src/frontend/js/core/cookies');
      const { lwtFormCheck } = await import('../../../src/frontend/js/forms/unloadformcheck');
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
      const { readTextAloud } = await import('../../../src/frontend/js/core/user_interactions');

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
      const { getCookie } = await import('../../../src/frontend/js/core/cookies');
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
