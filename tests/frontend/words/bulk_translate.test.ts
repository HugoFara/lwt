/**
 * Tests for bulk_translate.ts - Alpine.js component for bulk translation
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  bulkTranslateApp,
  type BulkTranslateConfig
} from '../../../src/frontend/js/modules/vocabulary/pages/bulk_translate';

// Mock dependencies
vi.mock('../../../src/frontend/js/modules/vocabulary/services/dictionary', () => ({
  createTheDictUrl: vi.fn((url, term) => `${url}?q=${encodeURIComponent(term)}`),
  owin: vi.fn()
}));

vi.mock('../../../src/frontend/js/shared/forms/bulk_actions', () => ({
  selectToggle: vi.fn()
}));

vi.mock('../../../src/frontend/js/modules/language/stores/language_config', () => ({
  setDictionaryLinks: vi.fn(),
  getDictionaryLinks: vi.fn(() => ({
    dict1: 'https://dict1.example.com/',
    dict2: 'https://dict2.example.com/',
    translator: 'https://translate.example.com/'
  })),
  resetLanguageConfig: vi.fn()
}));

import { createTheDictUrl, owin } from '../../../src/frontend/js/modules/vocabulary/services/dictionary';
import { selectToggle } from '../../../src/frontend/js/shared/forms/bulk_actions';
import { setDictionaryLinks } from '../../../src/frontend/js/modules/language/stores/language_config';

describe('bulk_translate.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    vi.useRealTimers();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // bulkTranslateApp Tests
  // ===========================================================================

  describe('bulkTranslateApp', () => {
    it('initializes with default values', () => {
      const component = bulkTranslateApp();

      expect(component.dictConfig.dict1).toBe('');
      expect(component.dictConfig.dict2).toBe('');
      expect(component.dictConfig.translator).toBe('');
      expect(component.sourceLanguage).toBe('en');
      expect(component.targetLanguage).toBe('en');
      expect(component.isGoogleTranslateReady).toBe(false);
      expect(component.submitButtonText).toBe('Save');
      expect(component.hasOffset).toBe(false);
    });

    it('initializes with config values', () => {
      const config: BulkTranslateConfig = {
        dictionaries: {
          dict1: 'https://dict1.com/',
          dict2: 'https://dict2.com/',
          translate: 'https://translate.com/'
        },
        sourceLanguage: 'de',
        targetLanguage: 'en'
      };
      const component = bulkTranslateApp(config);

      expect(component.dictConfig.dict1).toBe('https://dict1.com/');
      expect(component.dictConfig.dict2).toBe('https://dict2.com/');
      expect(component.dictConfig.translator).toBe('https://translate.com/');
      expect(component.sourceLanguage).toBe('de');
      expect(component.targetLanguage).toBe('en');
    });

    describe('init', () => {
      it('reads config from JSON script tag', () => {
        document.body.innerHTML = `
          <script type="application/json" id="bulk-translate-config">
            {"dictionaries": {"dict1": "https://custom-dict.com/", "dict2": "", "translate": ""}, "sourceLanguage": "fr", "targetLanguage": "en"}
          </script>
        `;

        const component = bulkTranslateApp();
        component.init();

        expect(component.dictConfig.dict1).toBe('https://custom-dict.com/');
        expect(component.sourceLanguage).toBe('fr');
        expect(component.targetLanguage).toBe('en');
      });

      it('sets hasOffset when offset input exists', () => {
        document.body.innerHTML = `
          <input name="offset" value="10">
        `;

        const component = bulkTranslateApp();
        component.init();

        expect(component.hasOffset).toBe(true);
      });

      it('marks headers as notranslate', () => {
        document.body.innerHTML = `
          <h3>Title</h3>
          <h4>Subtitle</h4>
        `;

        const component = bulkTranslateApp();
        component.init();

        expect(document.querySelector('h3')!.classList.contains('notranslate')).toBe(true);
        expect(document.querySelector('h4')!.classList.contains('notranslate')).toBe(true);
      });

      it('sets dictionary links in language config', () => {
        const config: BulkTranslateConfig = {
          dictionaries: {
            dict1: 'https://dict1.com/',
            dict2: '',
            translate: ''
          },
          sourceLanguage: 'en',
          targetLanguage: 'en'
        };

        const component = bulkTranslateApp(config);
        component.init();

        expect(setDictionaryLinks).toHaveBeenCalledWith({
          dict1: 'https://dict1.com/',
          dict2: '',
          translator: ''
        });
      });

      it('sets up Google Translate callback', () => {
        const component = bulkTranslateApp();
        component.init();

        expect(window.googleTranslateElementInit).toBeDefined();
      });
    });

    describe('markAll', () => {
      beforeEach(() => {
        document.body.innerHTML = `
          <form name="form1">
            <input type="submit" value="Next">
            <input name="term[1][text]" disabled>
            <input name="term[2][text]" disabled>
          </form>
        `;
      });

      it('sets submit button value to "Save"', () => {
        const component = bulkTranslateApp();
        component.markAll();

        expect((document.querySelector('input[type="submit"]') as HTMLInputElement).value).toBe('Save');
      });

      it('sets submitButtonText to "Save"', () => {
        const component = bulkTranslateApp();
        component.markAll();

        expect(component.submitButtonText).toBe('Save');
      });

      it('calls selectToggle with true', () => {
        const component = bulkTranslateApp();
        component.markAll();

        expect(selectToggle).toHaveBeenCalledWith(true, 'form1');
      });

      it('enables all term inputs', () => {
        const component = bulkTranslateApp();
        component.markAll();

        expect((document.querySelector('[name^="term"]') as HTMLInputElement).disabled).toBe(false);
      });
    });

    describe('markNone', () => {
      beforeEach(() => {
        document.body.innerHTML = `
          <form name="form1">
            <input type="submit" value="Save">
            <input name="term[1][text]">
          </form>
        `;
      });

      it('sets submit button value to "End" when no offset', () => {
        const component = bulkTranslateApp();
        component.hasOffset = false;
        component.markNone();

        expect((document.querySelector('input[type="submit"]') as HTMLInputElement).value).toBe('End');
      });

      it('sets submit button value to "Next" when offset exists', () => {
        const component = bulkTranslateApp();
        component.hasOffset = true;
        component.markNone();

        expect((document.querySelector('input[type="submit"]') as HTMLInputElement).value).toBe('Next');
      });

      it('calls selectToggle with false', () => {
        const component = bulkTranslateApp();
        component.markNone();

        expect(selectToggle).toHaveBeenCalledWith(false, 'form1');
      });

      it('disables all term inputs', () => {
        const component = bulkTranslateApp();
        component.markNone();

        expect((document.querySelector('[name^="term"]') as HTMLInputElement).disabled).toBe(true);
      });
    });

    describe('handleTermToggle', () => {
      beforeEach(() => {
        document.body.innerHTML = `
          <form name="form1">
            <input type="checkbox" class="markcheck" value="1">
            <input name="term[1][text]" value="hello">
            <input name="term[1][lg]" value="1">
            <input name="term[1][status]" value="1">
            <div id="Trans_1"><input value="translation"></div>
            <input type="submit" value="Save">
          </form>
        `;
      });

      it('disables term inputs when unchecked', () => {
        const component = bulkTranslateApp();
        component.handleTermToggle(1, false);

        expect((document.querySelector('[name="term[1][text]"]') as HTMLInputElement).disabled).toBe(true);
        expect((document.querySelector('[name="term[1][lg]"]') as HTMLInputElement).disabled).toBe(true);
        expect((document.querySelector('[name="term[1][status]"]') as HTMLInputElement).disabled).toBe(true);
        expect((document.querySelector('#Trans_1 input') as HTMLInputElement).disabled).toBe(true);
      });

      it('enables term inputs when checked', () => {
        // First disable all
        document.querySelectorAll<HTMLInputElement>('[name^="term"]').forEach(el => el.disabled = true);
        (document.querySelector('#Trans_1 input') as HTMLInputElement).disabled = true;

        const component = bulkTranslateApp();
        component.handleTermToggle(1, true);

        expect((document.querySelector('[name="term[1][text]"]') as HTMLInputElement).disabled).toBe(false);
      });
    });

    describe('handleTermToggles', () => {
      beforeEach(() => {
        document.body.innerHTML = `
          <input type="checkbox" class="markcheck" value="1" checked>
          <input type="checkbox" class="markcheck" value="2" checked>
          <span id="Term_1"><span class="term">HELLO</span></span>
          <span id="Term_2"><span class="term">WORLD</span></span>
          <input id="Text_1" value="HELLO">
          <input id="Text_2" value="WORLD">
          <div id="Trans_1"><input value="translation1"></div>
          <div id="Trans_2"><input value="translation2"></div>
          <select id="Stat_1">
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="99">WKn</option>
            <option value="98">Ign</option>
          </select>
          <select id="Stat_2">
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="99">WKn</option>
            <option value="98">Ign</option>
          </select>
        `;
      });

      it('converts text to lowercase when action is 6', () => {
        const component = bulkTranslateApp();
        component.handleTermToggles('6');

        expect(document.querySelector('#Term_1 .term')!.textContent).toBe('hello');
        expect(document.querySelector('#Term_2 .term')!.textContent).toBe('world');
        expect((document.querySelector('#Text_1') as HTMLInputElement).value).toBe('hello');
        expect((document.querySelector('#Text_2') as HTMLInputElement).value).toBe('world');
      });

      it('sets translation to * when action is 7', () => {
        const component = bulkTranslateApp();
        component.handleTermToggles('7');

        expect((document.querySelector('#Trans_1 input') as HTMLInputElement).value).toBe('*');
        expect((document.querySelector('#Trans_2 input') as HTMLInputElement).value).toBe('*');
      });

      it('sets status for all checked terms when action is 1-5', () => {
        const component = bulkTranslateApp();
        component.handleTermToggles('2');

        expect((document.querySelector('#Stat_1') as HTMLSelectElement).value).toBe('2');
        expect((document.querySelector('#Stat_2') as HTMLSelectElement).value).toBe('2');
      });

      it('only affects checked checkboxes', () => {
        // Uncheck second checkbox
        (document.querySelectorAll<HTMLInputElement>('.markcheck')[1]).checked = false;

        const component = bulkTranslateApp();
        component.handleTermToggles('6');

        expect(document.querySelector('#Term_1 .term')!.textContent).toBe('hello');
        expect(document.querySelector('#Term_2 .term')!.textContent).toBe('WORLD'); // Not changed
      });
    });

    describe('clickDictionary', () => {
      beforeEach(() => {
        document.body.innerHTML = `
          <table>
            <tr>
              <td><span class="term">hello</span></td>
              <td>
                <span class="dict1">D1</span>
                <span class="dict2">D2</span>
                <span class="dict3">Tr</span>
              </td>
            </tr>
            <tr>
              <td><input name="WoTranslation" value=""></td>
            </tr>
          </table>
        `;
      });

      it('uses dict1 config for dict1 class', () => {
        const config: BulkTranslateConfig = {
          dictionaries: {
            dict1: 'https://dict1.example.com/',
            dict2: 'https://dict2.example.com/',
            translate: 'https://translate.example.com/'
          },
          sourceLanguage: 'en',
          targetLanguage: 'en'
        };
        const component = bulkTranslateApp(config);
        const dictSpan = document.querySelector('.dict1') as HTMLElement;

        component.clickDictionary(dictSpan);

        expect(createTheDictUrl).toHaveBeenCalledWith(
          'https://dict1.example.com/',
          expect.any(String)
        );
      });

      it('uses dict2 config for dict2 class', () => {
        const config: BulkTranslateConfig = {
          dictionaries: {
            dict1: 'https://dict1.example.com/',
            dict2: 'https://dict2.example.com/',
            translate: 'https://translate.example.com/'
          },
          sourceLanguage: 'en',
          targetLanguage: 'en'
        };
        const component = bulkTranslateApp(config);
        const dictSpan = document.querySelector('.dict2') as HTMLElement;

        component.clickDictionary(dictSpan);

        expect(createTheDictUrl).toHaveBeenCalledWith(
          'https://dict2.example.com/',
          expect.any(String)
        );
      });

      it('uses translator config for dict3 class', () => {
        const config: BulkTranslateConfig = {
          dictionaries: {
            dict1: 'https://dict1.example.com/',
            dict2: 'https://dict2.example.com/',
            translate: 'https://translate.example.com/'
          },
          sourceLanguage: 'en',
          targetLanguage: 'en'
        };
        const component = bulkTranslateApp(config);
        const dictSpan = document.querySelector('.dict3') as HTMLElement;

        component.clickDictionary(dictSpan);

        expect(createTheDictUrl).toHaveBeenCalledWith(
          'https://translate.example.com/',
          expect.any(String)
        );
      });

      it('does nothing for elements without dict classes', () => {
        const component = bulkTranslateApp();
        const span = document.createElement('span');
        span.className = 'other';

        component.clickDictionary(span);

        expect(createTheDictUrl).not.toHaveBeenCalled();
      });

      it('opens popup for URLs starting with *', () => {
        const config: BulkTranslateConfig = {
          dictionaries: {
            dict1: '*https://popup.example.com/',
            dict2: '',
            translate: ''
          },
          sourceLanguage: 'en',
          targetLanguage: 'en'
        };
        const component = bulkTranslateApp(config);
        const dictSpan = document.querySelector('.dict1') as HTMLElement;

        component.clickDictionary(dictSpan);

        expect(owin).toHaveBeenCalled();
      });
    });

    describe('deleteTranslation', () => {
      it('clears translation input', () => {
        document.body.innerHTML = `
          <div id="Trans_1"><input value="some translation"></div>
        `;

        const component = bulkTranslateApp();
        component.deleteTranslation(1);

        expect((document.querySelector('#Trans_1 input') as HTMLInputElement).value).toBe('');
      });
    });

    describe('setToLowercase', () => {
      it('converts term to lowercase', () => {
        document.body.innerHTML = `
          <span id="Term_1"><span class="term">HELLO</span></span>
          <input id="Text_1" value="HELLO">
        `;

        const component = bulkTranslateApp();
        component.setToLowercase(1);

        expect(document.querySelector('#Term_1 .term')!.textContent).toBe('hello');
        expect((document.querySelector('#Text_1') as HTMLInputElement).value).toBe('hello');
      });
    });

    describe('updateSubmitButton', () => {
      it('sets text to "Save" when checkboxes are checked', () => {
        document.body.innerHTML = `
          <input type="checkbox" checked>
          <input type="submit" value="">
        `;

        const component = bulkTranslateApp();
        component.updateSubmitButton();

        expect(component.submitButtonText).toBe('Save');
        expect((document.querySelector('input[type="submit"]') as HTMLInputElement).value).toBe('Save');
      });

      it('sets text to "End" when no checkboxes checked and no offset', () => {
        document.body.innerHTML = `
          <input type="checkbox">
          <input type="submit" value="">
        `;

        const component = bulkTranslateApp();
        component.hasOffset = false;
        component.updateSubmitButton();

        expect(component.submitButtonText).toBe('End');
      });

      it('sets text to "Next" when no checkboxes checked but offset exists', () => {
        document.body.innerHTML = `
          <input type="checkbox">
          <input type="submit" value="">
        `;

        const component = bulkTranslateApp();
        component.hasOffset = true;
        component.updateSubmitButton();

        expect(component.submitButtonText).toBe('Next');
      });
    });

    describe('setupGoogleTranslateCallback', () => {
      it('sets up googleTranslateElementInit on window', () => {
        const component = bulkTranslateApp();
        component.setupGoogleTranslateCallback();

        expect(window.googleTranslateElementInit).toBeDefined();
        expect(typeof window.googleTranslateElementInit).toBe('function');
      });
    });
  });

  // ===========================================================================
  // Edge Cases Tests
  // ===========================================================================

  describe('edge cases', () => {
    it('handles empty term text in clickDictionary', () => {
      document.body.innerHTML = `
        <td><span class="term"></span></td>
        <td><span class="dict1">D1</span></td>
      `;

      const config: BulkTranslateConfig = {
        dictionaries: { dict1: 'https://dict.com/', dict2: '', translate: '' },
        sourceLanguage: 'en',
        targetLanguage: 'en'
      };
      const component = bulkTranslateApp(config);
      const dictSpan = document.querySelector('.dict1') as HTMLElement;

      expect(() => component.clickDictionary(dictSpan)).not.toThrow();
    });

    it('handles missing parent elements in clickDictionary', () => {
      document.body.innerHTML = `
        <span class="dict1">D1</span>
      `;

      const config: BulkTranslateConfig = {
        dictionaries: { dict1: 'https://dict.com/', dict2: '', translate: '' },
        sourceLanguage: 'en',
        targetLanguage: 'en'
      };
      const component = bulkTranslateApp(config);
      const dictSpan = document.querySelector('.dict1') as HTMLElement;

      expect(() => component.clickDictionary(dictSpan)).not.toThrow();
    });

    it('handles URLs with lwt_popup parameter', () => {
      document.body.innerHTML = `
        <td><span class="term">word</span></td>
        <td><span class="dict1">D1</span></td>
      `;

      const config: BulkTranslateConfig = {
        dictionaries: { dict1: 'https://dict.example.com/?lwt_popup=1', dict2: '', translate: '' },
        sourceLanguage: 'en',
        targetLanguage: 'en'
      };
      const component = bulkTranslateApp(config);
      const dictSpan = document.querySelector('.dict1') as HTMLElement;

      component.clickDictionary(dictSpan);

      expect(owin).toHaveBeenCalled();
    });

    it('handles invalid JSON in config script tag', () => {
      document.body.innerHTML = `
        <script type="application/json" id="bulk-translate-config">
          {invalid json}
        </script>
      `;

      const component = bulkTranslateApp();

      // Should not throw, uses defaults
      expect(() => component.init()).not.toThrow();
      expect(component.dictConfig.dict1).toBe('');
    });
  });
});
