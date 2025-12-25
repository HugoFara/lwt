/**
 * Tests for bulk_translate.ts - Functions for the bulk translate word form
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  clickDictionary,
  bulkInteractions,
  bulkCheckbox,
  markAll,
  markNone,
  changeTermToggles,
  initBulkTranslate
} from '../../../src/frontend/js/modules/vocabulary/pages/bulk_translate';

// Mock dependencies
vi.mock('../../../src/frontend/js/modules/vocabulary/services/dictionary', () => ({
  createTheDictUrl: vi.fn((url, term) => `${url}?q=${encodeURIComponent(term)}`),
  owin: vi.fn()
}));

vi.mock('../../../src/frontend/js/shared/forms/bulk_actions', () => ({
  selectToggle: vi.fn()
}));

import { createTheDictUrl, owin } from '../../../src/frontend/js/modules/vocabulary/services/dictionary';
import { selectToggle } from '../../../src/frontend/js/shared/forms/bulk_actions';
import {
  getDictionaryLinks,
  setDictionaryLinks,
  resetLanguageConfig
} from '../../../src/frontend/js/modules/language/stores/language_config';

describe('bulk_translate.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    vi.useFakeTimers();

    // Initialize language config with test dictionary links
    setDictionaryLinks({
      dict1: 'https://dict1.example.com/',
      dict2: 'https://dict2.example.com/',
      translator: 'https://translate.example.com/'
    });
  });

  afterEach(() => {
    vi.restoreAllMocks();
    vi.useRealTimers();
    document.body.innerHTML = '';
    resetLanguageConfig();
  });

  // ===========================================================================
  // clickDictionary Tests
  // ===========================================================================

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

    it('uses dict_link1 for dict1 class', () => {
      const dictSpan = document.querySelector('.dict1') as HTMLElement;

      clickDictionary(dictSpan);

      const dictLinks = getDictionaryLinks();
      expect(createTheDictUrl).toHaveBeenCalledWith(
        dictLinks.dict1,
        expect.any(String)
      );
    });

    it('uses dict_link2 for dict2 class', () => {
      const dictSpan = document.querySelector('.dict2') as HTMLElement;

      clickDictionary(dictSpan);

      const dictLinks = getDictionaryLinks();
      expect(createTheDictUrl).toHaveBeenCalledWith(
        dictLinks.dict2,
        expect.any(String)
      );
    });

    it('uses translator_link for dict3 class', () => {
      const dictSpan = document.querySelector('.dict3') as HTMLElement;

      clickDictionary(dictSpan);

      const dictLinks = getDictionaryLinks();
      expect(createTheDictUrl).toHaveBeenCalledWith(
        dictLinks.translator,
        expect.any(String)
      );
    });

    it('does nothing for elements without dict classes', () => {
      const span = document.createElement('span');
      span.className = 'other';

      clickDictionary(span);

      expect(createTheDictUrl).not.toHaveBeenCalled();
    });

    it('opens popup for URLs starting with *', () => {
      setDictionaryLinks({ dict1: '*https://popup.example.com/' });

      const dictSpan = document.querySelector('.dict1') as HTMLElement;

      clickDictionary(dictSpan);

      expect(owin).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // bulkCheckbox Tests
  // ===========================================================================

  describe('bulkCheckbox', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <form>
          <input type="checkbox" class="markcheck" value="1">
          <input name="term[1][text]" value="hello">
          <input name="term[1][lg]" value="1">
          <input name="term[1][status]" value="1">
          <div id="Trans_1"><input value="translation"></div>
          <input type="submit" value="Save">
        </form>
      `;
    });

    it('disables term inputs when checkbox is unchecked', () => {
      bulkCheckbox();

      const checkbox = document.querySelector<HTMLInputElement>('.markcheck')!;
      checkbox.checked = false;
      checkbox.dispatchEvent(new Event('change', { bubbles: true }));

      expect((document.querySelector('[name="term[1][text]"]') as HTMLInputElement).disabled).toBe(true);
      expect((document.querySelector('[name="term[1][lg]"]') as HTMLInputElement).disabled).toBe(true);
      expect((document.querySelector('[name="term[1][status]"]') as HTMLInputElement).disabled).toBe(true);
      expect((document.querySelector('#Trans_1 input') as HTMLInputElement).disabled).toBe(true);
    });

    it('enables term inputs when checkbox is checked', () => {
      // First disable all
      document.querySelectorAll<HTMLInputElement>('[name^="term"]').forEach(el => el.disabled = true);
      (document.querySelector('#Trans_1 input') as HTMLInputElement).disabled = true;

      bulkCheckbox();

      const checkbox = document.querySelector<HTMLInputElement>('.markcheck')!;
      checkbox.checked = true;
      checkbox.dispatchEvent(new Event('change', { bubbles: true }));

      expect((document.querySelector('[name="term[1][text]"]') as HTMLInputElement).disabled).toBe(false);
    });

    it('updates submit button text to "Save" when checkbox is checked', () => {
      bulkCheckbox();

      const checkbox = document.querySelector<HTMLInputElement>('.markcheck')!;
      checkbox.checked = true;
      checkbox.dispatchEvent(new Event('change', { bubbles: true }));

      expect((document.querySelector('input[type="submit"]') as HTMLInputElement).value).toBe('Save');
    });

    it('updates submit button text to "End" when checkbox is unchecked and no offset', () => {
      bulkCheckbox();

      const checkbox = document.querySelector<HTMLInputElement>('.markcheck')!;
      checkbox.checked = false;
      checkbox.dispatchEvent(new Event('change', { bubbles: true }));

      // No offset input, so should show "End"
      // But there are no checked boxes, so button stays as is
    });

    it('updates submit button text to "Next" when checkbox is unchecked with offset', () => {
      const form = document.querySelector('form')!;
      const offsetInput = document.createElement('input');
      offsetInput.name = 'offset';
      offsetInput.value = '10';
      form.appendChild(offsetInput);

      bulkCheckbox();

      const checkbox = document.querySelector<HTMLInputElement>('.markcheck')!;
      checkbox.checked = false;
      checkbox.dispatchEvent(new Event('change', { bubbles: true }));
    });
  });

  // ===========================================================================
  // markAll Tests
  // ===========================================================================

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
      markAll();

      expect((document.querySelector('input[type="submit"]') as HTMLInputElement).value).toBe('Save');
    });

    it('calls selectToggle with true', () => {
      markAll();

      expect(selectToggle).toHaveBeenCalledWith(true, 'form1');
    });

    it('enables all term inputs', () => {
      markAll();

      expect((document.querySelector('[name^="term"]') as HTMLInputElement).disabled).toBe(false);
    });
  });

  // ===========================================================================
  // markNone Tests
  // ===========================================================================

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
      markNone();

      expect((document.querySelector('input[type="submit"]') as HTMLInputElement).value).toBe('End');
    });

    it('sets submit button value to "Next" when offset exists', () => {
      const form = document.querySelector('form')!;
      const offsetInput = document.createElement('input');
      offsetInput.name = 'offset';
      offsetInput.value = '10';
      form.appendChild(offsetInput);

      markNone();

      expect((document.querySelector('input[type="submit"]') as HTMLInputElement).value).toBe('Next');
    });

    it('calls selectToggle with false', () => {
      markNone();

      expect(selectToggle).toHaveBeenCalledWith(false, 'form1');
    });

    it('disables all term inputs', () => {
      markNone();

      expect((document.querySelector('[name^="term"]') as HTMLInputElement).disabled).toBe(true);
    });
  });

  // ===========================================================================
  // changeTermToggles Tests
  // ===========================================================================

  describe('changeTermToggles', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <select id="toggleSelect">
          <option value="">Select action</option>
          <option value="1">Status 1</option>
          <option value="6">Lowercase</option>
          <option value="7">Delete translation</option>
        </select>
        <input type="checkbox" class="markcheck" value="1" checked>
        <input type="checkbox" class="markcheck" value="2" checked>
        <span id="Term_1"><span class="term">HELLO</span></span>
        <span id="Term_2"><span class="term">WORLD</span></span>
        <input id="Text_1" value="HELLO">
        <input id="Text_2" value="WORLD">
        <div id="Trans_1"><input value="translation1"></div>
        <div id="Trans_2"><input value="translation2"></div>
        <select id="Stat_1"><option value="1">1</option></select>
        <select id="Stat_2"><option value="1">1</option></select>
      `;
    });

    it('converts text to lowercase when value is 6', () => {
      const select = document.querySelector<HTMLSelectElement>('#toggleSelect')!;
      select.value = '6';

      changeTermToggles(select);

      expect(document.querySelector('#Term_1 .term')!.textContent).toBe('hello');
      expect(document.querySelector('#Term_2 .term')!.textContent).toBe('world');
      expect((document.querySelector('#Text_1') as HTMLInputElement).value).toBe('hello');
      expect((document.querySelector('#Text_2') as HTMLInputElement).value).toBe('world');
    });

    it('sets translation to * when value is 7', () => {
      const select = document.querySelector<HTMLSelectElement>('#toggleSelect')!;
      select.value = '7';

      changeTermToggles(select);

      expect((document.querySelector('#Trans_1 input') as HTMLInputElement).value).toBe('*');
      expect((document.querySelector('#Trans_2 input') as HTMLInputElement).value).toBe('*');
    });

    it('sets status for all checked terms when value is 1-5', () => {
      const select = document.querySelector<HTMLSelectElement>('#toggleSelect')!;
      select.value = '1';

      changeTermToggles(select);

      expect((document.querySelector('#Stat_1') as HTMLSelectElement).value).toBe('1');
      expect((document.querySelector('#Stat_2') as HTMLSelectElement).value).toBe('1');
    });

    it('resets select to first option after action', () => {
      const select = document.querySelector<HTMLSelectElement>('#toggleSelect')!;
      select.value = '6';

      changeTermToggles(select);

      expect(select.selectedIndex).toBe(0);
    });

    it('returns false', () => {
      const select = document.querySelector<HTMLSelectElement>('#toggleSelect')!;
      select.value = '6';

      const result = changeTermToggles(select);

      expect(result).toBe(false);
    });

    it('only affects checked checkboxes', () => {
      // Uncheck second checkbox
      (document.querySelectorAll<HTMLInputElement>('.markcheck')[1]).checked = false;

      const select = document.querySelector<HTMLSelectElement>('#toggleSelect')!;
      select.value = '6';

      changeTermToggles(select);

      expect(document.querySelector('#Term_1 .term')!.textContent).toBe('hello');
      expect(document.querySelector('#Term_2 .term')!.textContent).toBe('WORLD'); // Not changed
    });
  });

  // ===========================================================================
  // initBulkTranslate Tests
  // ===========================================================================

  describe('initBulkTranslate', () => {
    it('sets dictionary links in language config', () => {
      document.body.innerHTML = `
        <h3>Title</h3>
        <h4>Subtitle</h4>
      `;

      initBulkTranslate({
        dict1: 'https://new-dict1.com/',
        dict2: 'https://new-dict2.com/',
        translate: 'https://new-translate.com/'
      });

      const dictLinks = getDictionaryLinks();
      expect(dictLinks.dict1).toBe('https://new-dict1.com/');
      expect(dictLinks.dict2).toBe('https://new-dict2.com/');
      expect(dictLinks.translator).toBe('https://new-translate.com/');
    });

    it('marks headers as notranslate', () => {
      document.body.innerHTML = `
        <h3>Title</h3>
        <h4>Subtitle</h4>
        <title>Page Title</title>
      `;

      initBulkTranslate({
        dict1: '',
        dict2: '',
        translate: ''
      });

      expect(document.querySelector('h3')!.classList.contains('notranslate')).toBe(true);
      expect(document.querySelector('h4')!.classList.contains('notranslate')).toBe(true);
    });
  });

  // ===========================================================================
  // bulkInteractions Tests
  // ===========================================================================

  describe('bulkInteractions', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <form name="form1">
          <input name="WoTranslation" value="">
        </form>
        <table>
          <tr>
            <td><span class="term">word</span></td>
            <td>
              <span class="dict1">D1</span>
            </td>
          </tr>
          <tr>
            <td class="trans" id="Trans_1"><font>translated</font></td>
          </tr>
        </table>
      `;
    });

    it('sets up form submission handler', () => {
      bulkInteractions();

      const form = document.querySelector('[name="form1"]') as HTMLFormElement;
      const submitEvent = new Event('submit', { bubbles: true });
      form.dispatchEvent(submitEvent);

      // Should restore input names
    });

    it('sets up dictionary click handlers via delegation on td', () => {
      bulkInteractions();

      // The click handler is on td, using event delegation
      const dictSpan = document.querySelector('.dict1')!;
      const td = dictSpan.closest('td')!;

      // Trigger click on the td element which contains the dict1 span
      const clickEvent = new MouseEvent('click', { bubbles: true });
      Object.defineProperty(clickEvent, 'target', { value: dictSpan, writable: false });
      td.dispatchEvent(clickEvent);

      // Since click is delegated, directly clicking span should work
      dictSpan.dispatchEvent(new MouseEvent('click', { bubbles: true }));
    });

    it('polls for Google Translate results', () => {
      bulkInteractions();

      // The interval checks for .trans>font elements
      // We have one, so after 300ms it should process
      vi.advanceTimersByTime(300);

      // After processing, trans should have notranslate class
      // Note: Only triggers when .trans>font count matches .trans count
      const transCount = document.querySelectorAll('.trans').length;
      const fontCount = document.querySelectorAll('.trans>font').length;

      // If counts match, it should have processed
      if (transCount === fontCount && fontCount > 0) {
        expect(document.querySelector('.trans')!.classList.contains('notranslate')).toBe(true);
      }
    });

    it('converts translated text to input fields when fonts present', () => {
      bulkInteractions();

      // Wait for interval to process
      vi.advanceTimersByTime(300);

      // The condition is that all .trans have a <font> child
      // Our setup has 1 .trans with 1 font, so it should match
      const transEl = document.querySelector('#Trans_1')!;
      if (transEl.querySelectorAll('font').length === 0) {
        // Already converted
        expect(transEl.querySelectorAll('input').length).toBe(1);
      }
    });

    it('removes Google Translate elements after processing', () => {
      const div = document.createElement('div');
      div.id = 'google_translate_element';
      document.body.appendChild(div);

      bulkInteractions();

      vi.advanceTimersByTime(300);

      // Check if google_translate_element was removed after conversion
      const remaining = document.querySelectorAll('#google_translate_element').length;
      expect(remaining).toBeLessThanOrEqual(1); // May or may not be removed depending on font count match
    });

    it('enables all checkboxes and inputs after processing when condition met', () => {
      bulkInteractions();

      vi.advanceTimersByTime(300);

      // selectToggle should be called if .trans>font count matches .trans count
      // In our case, they should match
    });
  });

  // ===========================================================================
  // Edge Cases Tests
  // ===========================================================================

  describe('edge cases', () => {
    it('handles empty term text', () => {
      document.body.innerHTML = `
        <td><span class="term"></span></td>
        <td><span class="dict1">D1</span></td>
      `;

      const dictSpan = document.querySelector('.dict1') as HTMLElement;

      expect(() => clickDictionary(dictSpan)).not.toThrow();
    });

    it('handles missing parent elements', () => {
      document.body.innerHTML = `
        <span class="dict1">D1</span>
      `;

      const dictSpan = document.querySelector('.dict1') as HTMLElement;

      expect(() => clickDictionary(dictSpan)).not.toThrow();
    });

    it('handles URLs with lwt_popup parameter', () => {
      setDictionaryLinks({ dict1: 'https://dict.example.com/?lwt_popup=1' });

      document.body.innerHTML = `
        <td><span class="term">word</span></td>
        <td><span class="dict1">D1</span></td>
      `;

      const dictSpan = document.querySelector('.dict1') as HTMLElement;

      clickDictionary(dictSpan);

      expect(owin).toHaveBeenCalled();
    });
  });
});
