/**
 * Tests for language_form.ts - Language form configuration
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import $ from 'jquery';
import {
  languageForm,
  checkTranslatorChanged,
  checkLanguageForm,
  checkDuplicateLanguage,
  initLanguageForm,
  type LanguageFormConfig
} from '../../../src/frontend/js/languages/language_form';

// Make jQuery available globally
(global as any).$ = $;
(global as any).jQuery = $;

// Mock dependencies
vi.mock('../../../src/frontend/js/terms/translation_api', () => ({
  getLibreTranslateTranslation: vi.fn().mockResolvedValue('translated')
}));

vi.mock('../../../src/frontend/js/core/user_interactions', () => ({
  deepFindValue: vi.fn((obj, key) => {
    // Simple implementation for testing
    if (typeof obj !== 'object' || obj === null) return null;
    if (key in obj) return obj[key];
    for (const k in obj) {
      const result = (vi.mocked as any).deepFindValue?.(obj[k], key);
      if (result !== null) return result;
    }
    return null;
  }),
  readTextWithExternal: vi.fn()
}));

vi.mock('../../../src/frontend/js/forms/unloadformcheck', () => ({
  lwtFormCheck: {
    resetDirty: vi.fn(),
    askBeforeExit: vi.fn()
  }
}));

describe('language_form.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    // Reset languageForm state
    languageForm.languageId = 0;
    languageForm.languageName = '';
    languageForm.langDefs = {};
    languageForm.allLanguages = {};
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // languageForm.init Tests
  // ===========================================================================

  describe('languageForm.init', () => {
    it('sets languageId from config', () => {
      const config: LanguageFormConfig = {
        languageId: 5,
        languageName: 'Spanish',
        sourceLg: 'es',
        targetLg: 'en',
        languageDefs: {},
        allLanguages: {}
      };

      languageForm.init(config);

      expect(languageForm.languageId).toBe(5);
    });

    it('sets languageName from config', () => {
      const config: LanguageFormConfig = {
        languageId: 1,
        languageName: 'French',
        sourceLg: 'fr',
        targetLg: 'en',
        languageDefs: {},
        allLanguages: {}
      };

      languageForm.init(config);

      expect(languageForm.languageName).toBe('French');
    });

    it('sets langDefs from config', () => {
      const langDefs = {
        'Japanese': ['ja', 'Japanese', false, '\\p{Han}\\p{Hiragana}\\p{Katakana}', '', false, false, false] as [string, string, boolean, string, string, boolean, boolean, boolean]
      };

      const config: LanguageFormConfig = {
        languageId: 1,
        languageName: '',
        sourceLg: 'auto',
        targetLg: 'en',
        languageDefs: langDefs,
        allLanguages: {}
      };

      languageForm.init(config);

      expect(languageForm.langDefs).toEqual(langDefs);
    });

    it('sets allLanguages from config', () => {
      const allLanguages = { 'English': 1, 'Spanish': 2 };

      const config: LanguageFormConfig = {
        languageId: 1,
        languageName: '',
        sourceLg: 'auto',
        targetLg: 'en',
        languageDefs: {},
        allLanguages
      };

      languageForm.init(config);

      expect(languageForm.allLanguages).toEqual(allLanguages);
    });

    it('calls reloadDictURLs with source and target languages', () => {
      const spy = vi.spyOn(languageForm, 'reloadDictURLs');

      const config: LanguageFormConfig = {
        languageId: 1,
        languageName: '',
        sourceLg: 'de',
        targetLg: 'fr',
        languageDefs: {},
        allLanguages: {}
      };

      languageForm.init(config);

      expect(spy).toHaveBeenCalledWith('de', 'fr');
    });
  });

  // ===========================================================================
  // languageForm.reloadDictURLs Tests
  // ===========================================================================

  describe('languageForm.reloadDictURLs', () => {
    const originalLocation = window.location;

    beforeEach(() => {
      delete (window as any).location;
      (window as any).location = {
        href: 'http://localhost/languages/edit/1'
      };
    });

    afterEach(() => {
      window.location = originalLocation;
    });

    it('does not throw when called', () => {
      expect(() => languageForm.reloadDictURLs('en', 'es')).not.toThrow();
    });

    it('uses default values when called without arguments', () => {
      expect(() => languageForm.reloadDictURLs()).not.toThrow();
    });
  });

  // ===========================================================================
  // languageForm.checkLanguageChanged Tests
  // ===========================================================================

  describe('languageForm.checkLanguageChanged', () => {
    it('shows MeCab option for Japanese', () => {
      document.body.innerHTML = `
        <form name="lg_form">
          <select name="LgRegexpAlt" style="display: none;"></select>
        </form>
      `;

      languageForm.checkLanguageChanged('Japanese');

      const regexpAlt = document.querySelector('[name="LgRegexpAlt"]') as HTMLSelectElement;
      expect(regexpAlt.style.display).toBe('block');
    });

    it('hides MeCab option for non-Japanese languages', () => {
      document.body.innerHTML = `
        <form name="lg_form">
          <select name="LgRegexpAlt" style="display: block;"></select>
        </form>
      `;

      languageForm.checkLanguageChanged('English');

      const regexpAlt = document.querySelector('[name="LgRegexpAlt"]') as HTMLSelectElement;
      expect(regexpAlt.style.display).toBe('none');
    });

    it('handles missing form gracefully', () => {
      document.body.innerHTML = '';

      expect(() => languageForm.checkLanguageChanged('Japanese')).not.toThrow();
    });
  });

  // ===========================================================================
  // languageForm.multiWordsTranslateChange Tests
  // ===========================================================================

  describe('languageForm.multiWordsTranslateChange', () => {
    const originalLocation = window.location;

    beforeEach(() => {
      delete (window as any).location;
      (window as any).location = {
        href: 'http://localhost//languages/edit/1'
      };

      document.body.innerHTML = `
        <form name="lg_form">
          <input name="LgGoogleTranslateURI" value="">
        </form>
        <div id="LgTranslatorKeyWrapper" style="display: none;"></div>
      `;
    });

    afterEach(() => {
      window.location = originalLocation;
    });

    it('sets Google Translate URL for google_translate option', () => {
      languageForm.reloadDictURLs('en', 'es');
      languageForm.multiWordsTranslateChange('google_translate');

      const input = document.querySelector('[name="LgGoogleTranslateURI"]') as HTMLInputElement;
      expect(input.value).toContain('translate.google.com');
    });

    it('sets LibreTranslate URL and shows key wrapper for libretranslate option', () => {
      languageForm.reloadDictURLs('en', 'es');
      languageForm.multiWordsTranslateChange('libretranslate');

      const input = document.querySelector('[name="LgGoogleTranslateURI"]') as HTMLInputElement;
      expect(input.value).toContain('localhost:5000');
      // $.css('display', 'inherit') may be normalized by browser to 'block' or similar
      expect($('#LgTranslatorKeyWrapper').css('display')).not.toBe('none');
    });

    it('sets ggl URL for ggl option', () => {
      languageForm.reloadDictURLs('en', 'es');
      languageForm.multiWordsTranslateChange('ggl');

      const input = document.querySelector('[name="LgGoogleTranslateURI"]') as HTMLInputElement;
      expect(input.value).toContain('ggl.php');
    });

    it('sets glosbe URL for glosbe option', () => {
      languageForm.multiWordsTranslateChange('glosbe');

      const input = document.querySelector('[name="LgGoogleTranslateURI"]') as HTMLInputElement;
      expect(input.value).toContain('glosbe.php');
    });

    it('hides key wrapper for non-libretranslate options', () => {
      $('#LgTranslatorKeyWrapper').css('display', 'inherit');

      languageForm.reloadDictURLs('en', 'es');
      languageForm.multiWordsTranslateChange('google_translate');

      expect($('#LgTranslatorKeyWrapper').css('display')).toBe('none');
    });
  });

  // ===========================================================================
  // languageForm.changeLanguageTextSize Tests
  // ===========================================================================

  describe('languageForm.changeLanguageTextSize', () => {
    it('sets font size on example element', () => {
      document.body.innerHTML = `
        <div id="LgTextSizeExample">Sample text</div>
      `;

      languageForm.changeLanguageTextSize(150);

      expect($('#LgTextSizeExample').css('font-size')).toBe('150%');
    });

    it('handles string values', () => {
      document.body.innerHTML = `
        <div id="LgTextSizeExample">Sample text</div>
      `;

      languageForm.changeLanguageTextSize('200');

      expect($('#LgTextSizeExample').css('font-size')).toBe('200%');
    });
  });

  // ===========================================================================
  // languageForm.wordCharChange Tests
  // ===========================================================================

  describe('languageForm.wordCharChange', () => {
    it('sets mecab value for mecab option', () => {
      document.body.innerHTML = `
        <form name="lg_form">
          <input name="LgRegexpWordCharacters" value="">
        </form>
      `;

      languageForm.wordCharChange('mecab');

      const input = document.querySelector('[name="LgRegexpWordCharacters"]') as HTMLInputElement;
      expect(input.value).toBe('mecab');
    });

    it('sets regexp value from langDefs for regexp option', () => {
      languageForm.languageName = 'Japanese';
      languageForm.langDefs = {
        'Japanese': ['ja', 'Japanese', false, '\\p{Han}', '', false, false, false]
      };

      document.body.innerHTML = `
        <form name="lg_form">
          <input name="LgRegexpWordCharacters" value="">
        </form>
      `;

      languageForm.wordCharChange('regexp');

      const input = document.querySelector('[name="LgRegexpWordCharacters"]') as HTMLInputElement;
      expect(input.value).toBe('\\p{Han}');
    });
  });

  // ===========================================================================
  // languageForm.addPopUpOption Tests
  // ===========================================================================

  describe('languageForm.addPopUpOption', () => {
    it('adds lwt_popup parameter when checked is true', () => {
      const result = languageForm.addPopUpOption(
        'https://example.com/dict?q=test',
        true
      );

      expect(result).toContain('lwt_popup=true');
    });

    it('removes lwt_popup parameter when checked is false', () => {
      const result = languageForm.addPopUpOption(
        'https://example.com/dict?q=test&lwt_popup=true',
        false
      );

      expect(result).not.toContain('lwt_popup');
    });

    it('handles URL with leading asterisk', () => {
      const result = languageForm.addPopUpOption(
        '*https://example.com/dict?q=test',
        true
      );

      expect(result).toContain('lwt_popup=true');
      expect(result).not.toContain('*');
    });

    it('returns URL unchanged when popup already present and checked is true', () => {
      const url = 'https://example.com/dict?q=test&lwt_popup=true';
      const result = languageForm.addPopUpOption(url, true);

      expect(result).toBe(url);
    });

    it('returns URL unchanged when popup not present and checked is false', () => {
      const url = 'https://example.com/dict?q=test';
      const result = languageForm.addPopUpOption(url, false);

      expect(result).toBe(url);
    });

    it('returns original string for invalid URLs', () => {
      const result = languageForm.addPopUpOption('not-a-url', true);

      expect(result).toBe('not-a-url');
    });
  });

  // ===========================================================================
  // languageForm.changePopUpState Tests
  // ===========================================================================

  describe('languageForm.changePopUpState', () => {
    it('updates LgDict1URI when LgDict1PopUp is changed', () => {
      document.body.innerHTML = `
        <form name="lg_form">
          <input name="LgDict1URI" value="https://dict.com?q=test">
          <input type="checkbox" name="LgDict1PopUp">
        </form>
      `;

      const checkbox = document.querySelector('[name="LgDict1PopUp"]') as HTMLInputElement;
      checkbox.checked = true;

      languageForm.changePopUpState(checkbox);

      const input = document.querySelector('[name="LgDict1URI"]') as HTMLInputElement;
      expect(input.value).toContain('lwt_popup');
    });

    it('updates LgDict2URI when LgDict2PopUp is changed', () => {
      document.body.innerHTML = `
        <form name="lg_form">
          <input name="LgDict2URI" value="https://dict2.com?q=test">
          <input type="checkbox" name="LgDict2PopUp">
        </form>
      `;

      const checkbox = document.querySelector('[name="LgDict2PopUp"]') as HTMLInputElement;
      checkbox.checked = true;

      languageForm.changePopUpState(checkbox);

      const input = document.querySelector('[name="LgDict2URI"]') as HTMLInputElement;
      expect(input.value).toContain('lwt_popup');
    });

    it('updates LgGoogleTranslateURI when LgGoogleTranslatePopUp is changed', () => {
      document.body.innerHTML = `
        <form name="lg_form">
          <input name="LgGoogleTranslateURI" value="https://translate.com?q=test">
          <input type="checkbox" name="LgGoogleTranslatePopUp">
        </form>
      `;

      const checkbox = document.querySelector('[name="LgGoogleTranslatePopUp"]') as HTMLInputElement;
      checkbox.checked = true;

      languageForm.changePopUpState(checkbox);

      const input = document.querySelector('[name="LgGoogleTranslateURI"]') as HTMLInputElement;
      expect(input.value).toContain('lwt_popup');
    });
  });

  // ===========================================================================
  // languageForm.checkDictionaryChanged Tests
  // ===========================================================================

  describe('languageForm.checkDictionaryChanged', () => {
    it('sets popup checkbox when URL contains lwt_popup', () => {
      document.body.innerHTML = `
        <form name="lg_form">
          <input name="LgDict1URI" value="https://dict.com?q=test&lwt_popup=true">
          <input type="checkbox" name="LgDict1PopUp">
        </form>
      `;

      const input = document.querySelector('[name="LgDict1URI"]') as HTMLInputElement;
      languageForm.checkDictionaryChanged(input);

      const checkbox = document.querySelector('[name="LgDict1PopUp"]') as HTMLInputElement;
      expect(checkbox.checked).toBe(true);
    });

    it('handles URL with leading asterisk', () => {
      document.body.innerHTML = `
        <form name="lg_form">
          <input name="LgDict1URI" value="*https://dict.com?q=test">
          <input type="checkbox" name="LgDict1PopUp">
        </form>
      `;

      const input = document.querySelector('[name="LgDict1URI"]') as HTMLInputElement;
      languageForm.checkDictionaryChanged(input);

      expect(input.value).toBe('https://dict.com?q=test');
      const checkbox = document.querySelector('[name="LgDict1PopUp"]') as HTMLInputElement;
      expect(checkbox.checked).toBe(true);
    });

    it('does nothing when input is empty', () => {
      document.body.innerHTML = `
        <form name="lg_form">
          <input name="LgDict1URI" value="">
          <input type="checkbox" name="LgDict1PopUp">
        </form>
      `;

      const input = document.querySelector('[name="LgDict1URI"]') as HTMLInputElement;
      languageForm.checkDictionaryChanged(input);

      const checkbox = document.querySelector('[name="LgDict1PopUp"]') as HTMLInputElement;
      expect(checkbox.checked).toBe(false);
    });
  });

  // ===========================================================================
  // languageForm.checkTranslatorType Tests
  // ===========================================================================

  describe('languageForm.checkTranslatorType', () => {
    it('selects libretranslate when URL has lwt_translator=libretranslate', () => {
      document.body.innerHTML = `
        <select id="translatorType">
          <option value="google_translate">Google Translate</option>
          <option value="libretranslate">LibreTranslate</option>
        </select>
      `;

      const select = document.getElementById('translatorType') as HTMLSelectElement;
      languageForm.checkTranslatorType(
        'http://localhost:5000?lwt_translator=libretranslate&q=test',
        select
      );

      expect(select.value).toBe('libretranslate');
    });

    it('selects google_translate for other URLs', () => {
      document.body.innerHTML = `
        <select id="translatorType">
          <option value="google_translate">Google Translate</option>
          <option value="libretranslate">LibreTranslate</option>
        </select>
      `;

      const select = document.getElementById('translatorType') as HTMLSelectElement;
      languageForm.checkTranslatorType(
        'https://translate.google.com?q=test',
        select
      );

      expect(select.value).toBe('google_translate');
    });

    it('handles invalid URL gracefully', () => {
      document.body.innerHTML = `
        <select id="translatorType">
          <option value="google_translate" selected>Google Translate</option>
          <option value="libretranslate">LibreTranslate</option>
        </select>
      `;

      const select = document.getElementById('translatorType') as HTMLSelectElement;

      expect(() => languageForm.checkTranslatorType('not-a-url', select)).not.toThrow();
    });
  });

  // ===========================================================================
  // languageForm.checkWordChar Tests
  // ===========================================================================

  describe('languageForm.checkWordChar', () => {
    it('sets regexp option for non-mecab values', () => {
      document.body.innerHTML = `
        <form name="lg_form">
          <select name="LgRegexpAlt">
            <option value="regexp">Regexp</option>
            <option value="mecab">MeCab</option>
          </select>
        </form>
      `;

      languageForm.checkWordChar('\\p{L}');

      const select = document.querySelector('[name="LgRegexpAlt"]') as HTMLSelectElement;
      expect(select.value).toBe('regexp');
    });

    it('sets mecab option for mecab value', () => {
      document.body.innerHTML = `
        <form name="lg_form">
          <select name="LgRegexpAlt">
            <option value="regexp">Regexp</option>
            <option value="mecab">MeCab</option>
          </select>
        </form>
      `;

      languageForm.checkWordChar('mecab');

      const select = document.querySelector('[name="LgRegexpAlt"]') as HTMLSelectElement;
      expect(select.value).toBe('mecab');
    });
  });

  // ===========================================================================
  // languageForm.checkVoiceAPI Tests
  // ===========================================================================

  describe('languageForm.checkVoiceAPI', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <div id="voice-api-message-zone" style="display: none;"></div>
      `;
    });

    it('returns true and hides message for empty value', () => {
      const result = languageForm.checkVoiceAPI('');

      expect(result).toBe(true);
      expect($('#voice-api-message-zone').css('display')).toBe('none');
    });

    it('returns false when lwt_term is missing', () => {
      const result = languageForm.checkVoiceAPI('{"url": "http://api.com"}');

      expect(result).toBe(false);
      expect($('#voice-api-message-zone').text()).toContain('lwt_term');
    });

    it('returns false for invalid JSON', () => {
      const result = languageForm.checkVoiceAPI('lwt_term {invalid json}');

      expect(result).toBe(false);
      expect($('#voice-api-message-zone').text()).toContain('JSON');
    });

    it('returns true for valid JSON with lwt_term', async () => {
      const { deepFindValue } = await import('../../../src/frontend/js/core/user_interactions');
      (deepFindValue as any).mockReturnValue('lwt_term');

      const result = languageForm.checkVoiceAPI('{"text": "lwt_term"}');

      expect(result).toBe(true);
    });
  });

  // ===========================================================================
  // checkDuplicateLanguage Tests
  // ===========================================================================

  describe('checkDuplicateLanguage', () => {
    beforeEach(() => {
      vi.spyOn(window, 'alert').mockImplementation(() => {});
    });

    it('returns true when language does not exist', () => {
      languageForm.languageId = 0;
      languageForm.allLanguages = { 'English': 1 };

      document.body.innerHTML = `
        <input id="LgName" value="Spanish">
      `;

      const result = checkDuplicateLanguage();

      expect(result).toBe(true);
    });

    it('returns true when editing existing language with same name', () => {
      languageForm.languageId = 1;
      languageForm.allLanguages = { 'English': 1 };

      document.body.innerHTML = `
        <input id="LgName" value="English">
      `;

      const result = checkDuplicateLanguage();

      expect(result).toBe(true);
    });

    it('returns false when creating duplicate language', () => {
      languageForm.languageId = 0;
      languageForm.allLanguages = { 'English': 1 };

      document.body.innerHTML = `
        <input id="LgName" value="English">
      `;

      const result = checkDuplicateLanguage();

      expect(result).toBe(false);
      expect(window.alert).toHaveBeenCalled();
    });

    it('accepts custom language ID and languages map', () => {
      document.body.innerHTML = `
        <input id="LgName" value="French">
      `;

      const result = checkDuplicateLanguage(2, { 'French': 2 });

      expect(result).toBe(true);
    });
  });

  // ===========================================================================
  // checkTranslatorChanged Tests
  // ===========================================================================

  describe('checkTranslatorChanged', () => {
    it('calls checkTranslatorStatus and checkDictionaryChanged', () => {
      const statusSpy = vi.spyOn(languageForm, 'checkTranslatorStatus');
      const dictSpy = vi.spyOn(languageForm, 'checkDictionaryChanged');

      document.body.innerHTML = `
        <form name="lg_form">
          <input name="LgGoogleTranslateURI" value="https://translate.com">
          <input type="checkbox" name="LgGoogleTranslatePopUp">
          <select name="LgTranslatorName">
            <option value="google_translate">Google</option>
          </select>
        </form>
      `;

      const input = document.querySelector('[name="LgGoogleTranslateURI"]') as HTMLInputElement;
      checkTranslatorChanged(input);

      expect(statusSpy).toHaveBeenCalledWith('https://translate.com');
      expect(dictSpy).toHaveBeenCalledWith(input);
    });
  });

  // ===========================================================================
  // checkLanguageForm Tests
  // ===========================================================================

  describe('checkLanguageForm', () => {
    it('calls check functions for all form elements', () => {
      const langChangeSpy = vi.spyOn(languageForm, 'checkLanguageChanged');
      const dictChangeSpy = vi.spyOn(languageForm, 'checkDictionaryChanged');
      const wordCharSpy = vi.spyOn(languageForm, 'checkWordChar');

      document.body.innerHTML = `
        <form name="lg_form">
          <input name="LgName" value="Japanese">
          <input name="LgDict1URI" value="https://dict1.com">
          <input name="LgDict2URI" value="https://dict2.com">
          <input name="LgGoogleTranslateURI" value="https://translate.com">
          <input name="LgRegexpWordCharacters" value="mecab">
          <input type="checkbox" name="LgDict1PopUp">
          <input type="checkbox" name="LgDict2PopUp">
          <input type="checkbox" name="LgGoogleTranslatePopUp">
          <select name="LgTranslatorName">
            <option value="google_translate">Google</option>
          </select>
          <select name="LgRegexpAlt">
            <option value="mecab">MeCab</option>
          </select>
        </form>
      `;

      const form = document.querySelector('[name="lg_form"]') as HTMLFormElement;
      checkLanguageForm(form);

      expect(langChangeSpy).toHaveBeenCalledWith('Japanese');
      expect(dictChangeSpy).toHaveBeenCalled();
      expect(wordCharSpy).toHaveBeenCalledWith('mecab');
    });
  });

  // ===========================================================================
  // initLanguageForm Tests
  // ===========================================================================

  describe('initLanguageForm', () => {
    it('initializes from JSON config element', () => {
      document.body.innerHTML = `
        <script type="application/json" id="language-form-config">
          {
            "languageId": 3,
            "languageName": "German",
            "sourceLg": "de",
            "targetLg": "en",
            "languageDefs": {},
            "allLanguages": {"German": 3}
          }
        </script>
        <form name="lg_form">
          <input name="LgName" value="German">
        </form>
      `;

      initLanguageForm();

      expect(languageForm.languageId).toBe(3);
      expect(languageForm.languageName).toBe('German');
    });

    it('does nothing when config element is missing', () => {
      document.body.innerHTML = `
        <form name="lg_form">
          <input name="LgName" value="German">
        </form>
      `;

      const initSpy = vi.spyOn(languageForm, 'init');
      initLanguageForm();

      expect(initSpy).not.toHaveBeenCalled();
    });

    it('handles invalid JSON in config element', () => {
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      document.body.innerHTML = `
        <script type="application/json" id="language-form-config">
          {invalid json}
        </script>
        <form name="lg_form"></form>
      `;

      initLanguageForm();

      expect(consoleSpy).toHaveBeenCalled();
    });

    it('sets up form submit handler for duplicate check', () => {
      languageForm.allLanguages = { 'English': 1 };
      vi.spyOn(window, 'alert').mockImplementation(() => {});

      document.body.innerHTML = `
        <script type="application/json" id="language-form-config">
          {
            "languageId": 0,
            "languageName": "",
            "sourceLg": "auto",
            "targetLg": "en",
            "languageDefs": {},
            "allLanguages": {"English": 1}
          }
        </script>
        <form name="lg_form">
          <input id="LgName" name="LgName" value="English">
          <button type="submit">Save</button>
        </form>
      `;

      initLanguageForm();

      const form = document.querySelector('[name="lg_form"]') as HTMLFormElement;
      const submitEvent = new Event('submit', { cancelable: true });
      form.dispatchEvent(submitEvent);

      expect(submitEvent.defaultPrevented).toBe(true);
    });

    it('sets up cancel button handler', async () => {
      const { lwtFormCheck } = await import('../../../src/frontend/js/forms/unloadformcheck');

      const originalLocation = window.location;
      delete (window as any).location;
      (window as any).location = { href: '' };

      document.body.innerHTML = `
        <script type="application/json" id="language-form-config">
          {
            "languageId": 1,
            "languageName": "Test",
            "sourceLg": "auto",
            "targetLg": "en",
            "languageDefs": {},
            "allLanguages": {}
          }
        </script>
        <form name="lg_form">
          <input name="LgName" value="Test">
        </form>
        <button data-action="cancel-form" data-redirect="/my-languages">Cancel</button>
      `;

      initLanguageForm();

      const cancelBtn = document.querySelector('[data-action="cancel-form"]') as HTMLButtonElement;
      cancelBtn.click();

      expect(lwtFormCheck.resetDirty).toHaveBeenCalled();
      expect(window.location.href).toBe('/my-languages');

      window.location = originalLocation;
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles missing form elements gracefully', () => {
      document.body.innerHTML = '<form name="lg_form"></form>';

      const form = document.querySelector('[name="lg_form"]') as HTMLFormElement;

      expect(() => checkLanguageForm(form)).not.toThrow();
    });

    it('checkTranslatorStatus handles URLs starting with asterisk', () => {
      const spy = vi.spyOn(languageForm, 'checkLibreTranslateStatus');

      languageForm.checkTranslatorStatus('*http://localhost:5000?lwt_translator=libretranslate');

      expect(spy).toHaveBeenCalled();
    });

    it('checkTranslatorStatus handles invalid URLs gracefully', () => {
      expect(() => languageForm.checkTranslatorStatus('not-a-valid-url')).not.toThrow();
    });

    it('displayLibreTranslateError shows error message', () => {
      document.body.innerHTML = `
        <div id="translator_status"></div>
      `;

      languageForm.displayLibreTranslateError('Connection refused');

      expect($('#translator_status').html()).toContain('LibreTranslate');
      expect($('#translator_status').html()).toContain('Connection refused');
    });
  });
});
