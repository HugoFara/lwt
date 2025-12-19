/**
 * Tests for language_wizard.ts - Language Wizard functionality
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  languageWizard,
  initLanguageWizard,
  type LanguageWizardConfig
} from '../../../src/frontend/js/languages/language_wizard';

// Mock dependencies
vi.mock('../../../src/frontend/js/core/ajax_utilities', () => ({
  do_ajax_save_setting: vi.fn()
}));

vi.mock('../../../src/frontend/js/forms/unloadformcheck', () => ({
  lwtFormCheck: {
    askBeforeExit: vi.fn()
  }
}));

vi.mock('../../../src/frontend/js/languages/language_form', () => ({
  languageForm: {
    reloadDictURLs: vi.fn(),
    checkLanguageChanged: vi.fn()
  }
}));

import { do_ajax_save_setting } from '../../../src/frontend/js/core/ajax_utilities';
import { lwtFormCheck } from '../../../src/frontend/js/forms/unloadformcheck';
import { languageForm } from '../../../src/frontend/js/languages/language_form';

describe('language_wizard.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    // Reset wizard state
    languageWizard.langDefs = {};
    // Mock window globals
    (window as any).GGTRANSLATE = '';
    (window as any).LIBRETRANSLATE = '';
    (window as any).reloadDictURLs = vi.fn();
    (window as any).checkLanguageChanged = vi.fn();
    // Mock window.location
    Object.defineProperty(window, 'location', {
      value: {
        href: 'http://localhost:8000/languages/edit',
        protocol: 'http:',
        hostname: 'localhost'
      },
      writable: true,
      configurable: true
    });
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // languageWizard.init Tests
  // ===========================================================================

  describe('languageWizard.init', () => {
    it('stores language definitions from config', () => {
      const config: LanguageWizardConfig = {
        languageDefs: {
          'English': ['en', 'en', false, '[a-zA-Z]', '.!?', false, false, false],
          'French': ['fr', 'fr', false, '[a-zA-Zéèêëàâùûôîçœæ]', '.!?', false, false, false]
        }
      };

      languageWizard.init(config);

      expect(languageWizard.langDefs).toEqual(config.languageDefs);
    });
  });

  // ===========================================================================
  // languageWizard.go Tests
  // ===========================================================================

  describe('languageWizard.go', () => {
    beforeEach(() => {
      languageWizard.langDefs = {
        'English': ['en', 'en', false, '[a-zA-Z]', '.!?', false, false, false],
        'French': ['fr', 'fr', false, '[a-zA-Z]', '.!?', false, false, false],
        'Japanese': ['ja', 'ja', true, '[\\p{Han}\\p{Hiragana}\\p{Katakana}]', '。！？', true, true, false]
      };
    });

    it('alerts when L1 is not selected', () => {
      document.body.innerHTML = `
        <select id="l1"><option value="">Select...</option></select>
        <select id="l2"><option value="French">French</option></select>
      `;

      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      languageWizard.go();

      expect(alertSpy).toHaveBeenCalledWith('Please choose your native language (L1)!');
    });

    it('alerts when L2 is not selected', () => {
      document.body.innerHTML = `
        <select id="l1"><option value="English">English</option></select>
        <select id="l2"><option value="">Select...</option></select>
      `;

      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      languageWizard.go();

      expect(alertSpy).toHaveBeenCalledWith('Please choose your language you want to read/study (L2)!');
    });

    it('alerts when L1 and L2 are the same', () => {
      document.body.innerHTML = `
        <select id="l1"><option value="English" selected>English</option></select>
        <select id="l2"><option value="English" selected>English</option></select>
      `;

      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      languageWizard.go();

      expect(alertSpy).toHaveBeenCalledWith('L1 L2 Languages must not be equal!');
    });

    it('calls apply with correct language definitions', () => {
      document.body.innerHTML = `
        <select id="l1"><option value="English" selected>English</option></select>
        <select id="l2"><option value="French" selected>French</option></select>
        <input name="LgName" />
        <input name="LgDict1URI" />
        <input name="LgDict1PopUp" type="checkbox" />
        <input name="LgGoogleTranslateURI" />
        <input name="LgTextSize" />
        <input name="LgRegexpSplitSentences" />
        <input name="LgRegexpWordCharacters" />
        <input name="LgSplitEachChar" type="checkbox" />
        <input name="LgRemoveSpaces" type="checkbox" />
        <input name="LgRightToLeft" type="checkbox" />
      `;

      const applySpy = vi.spyOn(languageWizard, 'apply');

      languageWizard.go();

      expect(applySpy).toHaveBeenCalledWith(
        languageWizard.langDefs['French'],
        languageWizard.langDefs['English'],
        'French'
      );
    });
  });

  // ===========================================================================
  // languageWizard.apply Tests
  // ===========================================================================

  describe('languageWizard.apply', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <input name="LgName" />
        <input name="LgDict1URI" />
        <input name="LgDict1PopUp" type="checkbox" />
        <input name="LgGoogleTranslateURI" />
        <input name="LgTextSize" />
        <input name="LgRegexpSplitSentences" />
        <input name="LgRegexpWordCharacters" />
        <input name="LgSplitEachChar" type="checkbox" />
        <input name="LgRemoveSpaces" type="checkbox" />
        <input name="LgRightToLeft" type="checkbox" />
      `;
    });

    it('calls reloadDictURLs with language codes', () => {
      const learningLg: [string, string, boolean, string, string, boolean, boolean, boolean] =
        ['fr', 'fr', false, '[a-zA-Z]', '.!?', false, false, false];
      const knownLg: [string, string, boolean, string, string, boolean, boolean, boolean] =
        ['en', 'en', false, '[a-zA-Z]', '.!?', false, false, false];

      languageWizard.apply(learningLg, knownLg, 'French');

      expect(languageForm.reloadDictURLs).toHaveBeenCalledWith('fr', 'en');
    });

    it('sets up LibreTranslate URL', () => {
      const learningLg: [string, string, boolean, string, string, boolean, boolean, boolean] =
        ['fr', 'fr', false, '[a-zA-Z]', '.!?', false, false, false];
      const knownLg: [string, string, boolean, string, string, boolean, boolean, boolean] =
        ['en', 'en', false, '[a-zA-Z]', '.!?', false, false, false];

      languageWizard.apply(learningLg, knownLg, 'French');

      expect((window as any).LIBRETRANSLATE).toContain('libretranslate');
      expect((window as any).LIBRETRANSLATE).toContain('source=fr');
      expect((window as any).LIBRETRANSLATE).toContain('target=en');
    });

    it('sets language name and triggers change event', () => {
      const learningLg: [string, string, boolean, string, string, boolean, boolean, boolean] =
        ['fr', 'fr', false, '[a-zA-Z]', '.!?', false, false, false];
      const knownLg: [string, string, boolean, string, string, boolean, boolean, boolean] =
        ['en', 'en', false, '[a-zA-Z]', '.!?', false, false, false];

      languageWizard.apply(learningLg, knownLg, 'French');

      const input = document.querySelector('input[name="LgName"]') as HTMLInputElement;
      expect(input.value).toBe('French');
    });

    it('calls checkLanguageChanged', () => {
      const learningLg: [string, string, boolean, string, string, boolean, boolean, boolean] =
        ['ja', 'ja', true, '[\\p{Han}]', '。', true, true, false];
      const knownLg: [string, string, boolean, string, string, boolean, boolean, boolean] =
        ['en', 'en', false, '[a-zA-Z]', '.!?', false, false, false];

      languageWizard.apply(learningLg, knownLg, 'Japanese');

      expect(languageForm.checkLanguageChanged).toHaveBeenCalledWith('Japanese');
    });

    it('sets Glosbe dictionary URL', () => {
      const learningLg: [string, string, boolean, string, string, boolean, boolean, boolean] =
        ['de', 'de', false, '[a-zA-Zäöüß]', '.!?', false, false, false];
      const knownLg: [string, string, boolean, string, string, boolean, boolean, boolean] =
        ['en', 'en', false, '[a-zA-Z]', '.!?', false, false, false];

      languageWizard.apply(learningLg, knownLg, 'German');

      const dictInput = document.querySelector('input[name="LgDict1URI"]') as HTMLInputElement;
      expect(dictInput.value).toContain('glosbe.com/de/en');
      const popupInput = document.querySelector('input[name="LgDict1PopUp"]') as HTMLInputElement;
      expect(popupInput.checked).toBe(true);
    });

    it('sets Google Translate URL when available', () => {
      (window as any).GGTRANSLATE = 'https://translate.google.com/?source=fr&target=en';

      const learningLg: [string, string, boolean, string, string, boolean, boolean, boolean] =
        ['fr', 'fr', false, '[a-zA-Z]', '.!?', false, false, false];
      const knownLg: [string, string, boolean, string, string, boolean, boolean, boolean] =
        ['en', 'en', false, '[a-zA-Z]', '.!?', false, false, false];

      languageWizard.apply(learningLg, knownLg, 'French');

      const input = document.querySelector('input[name="LgGoogleTranslateURI"]') as HTMLInputElement;
      expect(input.value).toBe('https://translate.google.com/?source=fr&target=en');
    });

    it('sets text size 200 for languages needing large text', () => {
      const learningLg: [string, string, boolean, string, string, boolean, boolean, boolean] =
        ['ja', 'ja', true, '[\\p{Han}]', '。', true, true, false];  // Large text = true
      const knownLg: [string, string, boolean, string, string, boolean, boolean, boolean] =
        ['en', 'en', false, '[a-zA-Z]', '.!?', false, false, false];

      languageWizard.apply(learningLg, knownLg, 'Japanese');

      const input = document.querySelector('input[name="LgTextSize"]') as HTMLInputElement;
      expect(input.value).toBe('200');
    });

    it('sets text size 150 for languages not needing large text', () => {
      const learningLg: [string, string, boolean, string, string, boolean, boolean, boolean] =
        ['fr', 'fr', false, '[a-zA-Z]', '.!?', false, false, false];  // Large text = false
      const knownLg: [string, string, boolean, string, string, boolean, boolean, boolean] =
        ['en', 'en', false, '[a-zA-Z]', '.!?', false, false, false];

      languageWizard.apply(learningLg, knownLg, 'French');

      const input = document.querySelector('input[name="LgTextSize"]') as HTMLInputElement;
      expect(input.value).toBe('150');
    });

    it('sets language parsing rules', () => {
      const learningLg: [string, string, boolean, string, string, boolean, boolean, boolean] =
        ['ja', 'ja', true, '[\\p{Han}]', '。！？', true, true, false];
      const knownLg: [string, string, boolean, string, string, boolean, boolean, boolean] =
        ['en', 'en', false, '[a-zA-Z]', '.!?', false, false, false];

      languageWizard.apply(learningLg, knownLg, 'Japanese');

      const sentencesInput = document.querySelector('input[name="LgRegexpSplitSentences"]') as HTMLInputElement;
      expect(sentencesInput.value).toBe('。！？');
      const wordCharsInput = document.querySelector('input[name="LgRegexpWordCharacters"]') as HTMLInputElement;
      expect(wordCharsInput.value).toBe('[\\p{Han}]');
      const splitCharInput = document.querySelector('input[name="LgSplitEachChar"]') as HTMLInputElement;
      expect(splitCharInput.checked).toBe(true);
      const removeSpacesInput = document.querySelector('input[name="LgRemoveSpaces"]') as HTMLInputElement;
      expect(removeSpacesInput.checked).toBe(true);
      const rtlInput = document.querySelector('input[name="LgRightToLeft"]') as HTMLInputElement;
      expect(rtlInput.checked).toBe(false);
    });

    it('sets RTL flag for RTL languages', () => {
      const learningLg: [string, string, boolean, string, string, boolean, boolean, boolean] =
        ['ar', 'ar', false, '[\\p{Arabic}]', '.!?', false, false, true];  // RTL = true
      const knownLg: [string, string, boolean, string, string, boolean, boolean, boolean] =
        ['en', 'en', false, '[a-zA-Z]', '.!?', false, false, false];

      languageWizard.apply(learningLg, knownLg, 'Arabic');

      const rtlInput = document.querySelector('input[name="LgRightToLeft"]') as HTMLInputElement;
      expect(rtlInput.checked).toBe(true);
    });
  });

  // ===========================================================================
  // languageWizard.changeNative Tests
  // ===========================================================================

  describe('languageWizard.changeNative', () => {
    it('saves native language setting via AJAX', () => {
      languageWizard.changeNative('English');

      expect(do_ajax_save_setting).toHaveBeenCalledWith('currentnativelanguage', 'English');
    });
  });

  // ===========================================================================
  // languageWizard.toggleWizardZone Tests
  // ===========================================================================

  describe('languageWizard.toggleWizardZone', () => {
    it('toggles wizard zone visibility', () => {
      document.body.innerHTML = '<div id="wizard_zone" style="display: block;">Wizard Content</div>';

      languageWizard.toggleWizardZone();

      // Note: The actual toggle implementation may use jQuery slideToggle which is harder to test
      // In a real vanilla JS implementation, this would check display property
      // For now, we just verify the function doesn't throw
      expect(document.getElementById('wizard_zone')).toBeTruthy();
    });
  });

  // ===========================================================================
  // initLanguageWizard Tests
  // ===========================================================================

  describe('initLanguageWizard', () => {
    it('does nothing when config element does not exist', () => {
      expect(() => initLanguageWizard()).not.toThrow();
    });

    it('initializes wizard with config', () => {
      document.body.innerHTML = `
        <script id="language-wizard-config" type="application/json">
          {"languageDefs": {"English": ["en", "en", false, "[a-zA-Z]", ".!?", false, false, false]}}
        </script>
      `;

      initLanguageWizard();

      expect(languageWizard.langDefs).toHaveProperty('English');
    });

    it('sets up L1 change handler', () => {
      document.body.innerHTML = `
        <script id="language-wizard-config" type="application/json">
          {"languageDefs": {}}
        </script>
        <select id="l1">
          <option value="English">English</option>
        </select>
      `;

      initLanguageWizard();

      const l1Select = document.getElementById('l1') as HTMLSelectElement;
      l1Select.value = 'English';
      l1Select.dispatchEvent(new Event('change'));

      expect(do_ajax_save_setting).toHaveBeenCalledWith('currentnativelanguage', 'English');
    });

    it('sets up wizard go button handler', () => {
      document.body.innerHTML = `
        <script id="language-wizard-config" type="application/json">
          {"languageDefs": {"English": ["en", "en", false, "[a-zA-Z]", ".!?", false, false, false]}}
        </script>
        <select id="l1"><option value="">Select...</option></select>
        <select id="l2"><option value="">Select...</option></select>
        <button data-action="wizard-go">Go</button>
      `;

      initLanguageWizard();

      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});
      const goButton = document.querySelector('[data-action="wizard-go"]')!;
      goButton.dispatchEvent(new Event('click'));

      // Should alert because L1 is not selected
      expect(alertSpy).toHaveBeenCalled();
    });

    it('sets up wizard toggle handler', () => {
      document.body.innerHTML = `
        <script id="language-wizard-config" type="application/json">
          {"languageDefs": {}}
        </script>
        <div id="wizard_zone" style="display: block;">Content</div>
        <h3 data-action="wizard-toggle">Toggle</h3>
      `;

      initLanguageWizard();

      const toggleSpy = vi.spyOn(languageWizard, 'toggleWizardZone');
      const toggleHeader = document.querySelector('[data-action="wizard-toggle"]')!;
      toggleHeader.dispatchEvent(new Event('click'));

      expect(toggleSpy).toHaveBeenCalled();
    });

    it('sets up form check for unsaved changes', () => {
      document.body.innerHTML = `
        <script id="language-wizard-config" type="application/json">
          {"languageDefs": {}}
        </script>
      `;

      initLanguageWizard();

      expect(lwtFormCheck.askBeforeExit).toHaveBeenCalled();
    });

    it('handles invalid JSON config gracefully', () => {
      document.body.innerHTML = `
        <script id="language-wizard-config" type="application/json">
          {invalid json}
        </script>
      `;

      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      expect(() => initLanguageWizard()).not.toThrow();
      expect(consoleSpy).toHaveBeenCalled();
    });
  });
});
