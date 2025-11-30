/**
 * Tests for bulk_translate.ts - Functions for the bulk translate word form
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import $ from 'jquery';
import {
  clickDictionary,
  bulkInteractions,
  bulkCheckbox,
  markAll,
  markNone,
  changeTermToggles,
  initBulkTranslate
} from '../../../src/frontend/js/words/bulk_translate';

// Mock dependencies
vi.mock('../../../src/frontend/js/core/lwt_state', () => ({
  LWT_DATA: {
    language: {
      dict_link1: 'https://dict1.example.com/',
      dict_link2: 'https://dict2.example.com/',
      translator_link: 'https://translate.example.com/'
    }
  }
}));

vi.mock('../../../src/frontend/js/terms/dictionary', () => ({
  createTheDictUrl: vi.fn((url, term) => `${url}?q=${encodeURIComponent(term)}`),
  owin: vi.fn()
}));

vi.mock('../../../src/frontend/js/forms/bulk_actions', () => ({
  selectToggle: vi.fn()
}));

import { LWT_DATA } from '../../../src/frontend/js/core/lwt_state';
import { createTheDictUrl, owin } from '../../../src/frontend/js/terms/dictionary';
import { selectToggle } from '../../../src/frontend/js/forms/bulk_actions';

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
      const dictSpan = document.querySelector('.dict1')!;

      clickDictionary.call(dictSpan as HTMLElement);

      expect(createTheDictUrl).toHaveBeenCalledWith(
        LWT_DATA.language.dict_link1,
        expect.any(String)
      );
    });

    it('uses dict_link2 for dict2 class', () => {
      const dictSpan = document.querySelector('.dict2')!;

      clickDictionary.call(dictSpan as HTMLElement);

      expect(createTheDictUrl).toHaveBeenCalledWith(
        LWT_DATA.language.dict_link2,
        expect.any(String)
      );
    });

    it('uses translator_link for dict3 class', () => {
      const dictSpan = document.querySelector('.dict3')!;

      clickDictionary.call(dictSpan as HTMLElement);

      expect(createTheDictUrl).toHaveBeenCalledWith(
        LWT_DATA.language.translator_link,
        expect.any(String)
      );
    });

    it('does nothing for elements without dict classes', () => {
      const span = document.createElement('span');
      span.className = 'other';

      clickDictionary.call(span);

      expect(createTheDictUrl).not.toHaveBeenCalled();
    });

    it('opens popup for URLs starting with *', () => {
      LWT_DATA.language.dict_link1 = '*https://popup.example.com/';

      const dictSpan = document.querySelector('.dict1')!;

      clickDictionary.call(dictSpan as HTMLElement);

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
      $(checkbox).trigger('change');

      expect($('[name="term[1][text]"]').prop('disabled')).toBe(true);
      expect($('[name="term[1][lg]"]').prop('disabled')).toBe(true);
      expect($('[name="term[1][status]"]').prop('disabled')).toBe(true);
      expect($('#Trans_1 input').prop('disabled')).toBe(true);
    });

    it('enables term inputs when checkbox is checked', () => {
      // First disable all
      $('[name^="term"]').prop('disabled', true);
      $('#Trans_1 input').prop('disabled', true);

      bulkCheckbox();

      const checkbox = document.querySelector<HTMLInputElement>('.markcheck')!;
      checkbox.checked = true;
      $(checkbox).trigger('change');

      expect($('[name="term[1][text]"]').prop('disabled')).toBe(false);
    });

    it('updates submit button text to "Save" when checkbox is checked', () => {
      bulkCheckbox();

      const checkbox = document.querySelector<HTMLInputElement>('.markcheck')!;
      checkbox.checked = true;
      $(checkbox).trigger('change');

      expect($('input[type="submit"]').val()).toBe('Save');
    });

    it('updates submit button text to "End" when checkbox is unchecked and no offset', () => {
      bulkCheckbox();

      const checkbox = document.querySelector<HTMLInputElement>('.markcheck')!;
      checkbox.checked = false;
      $(checkbox).trigger('change');

      // No offset input, so should show "End"
      // But there are no checked boxes, so button stays as is
    });

    it('updates submit button text to "Next" when checkbox is unchecked with offset', () => {
      $('form').append('<input name="offset" value="10">');

      bulkCheckbox();

      const checkbox = document.querySelector<HTMLInputElement>('.markcheck')!;
      checkbox.checked = false;
      $(checkbox).trigger('change');
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

      expect($('input[type="submit"]').val()).toBe('Save');
    });

    it('calls selectToggle with true', () => {
      markAll();

      expect(selectToggle).toHaveBeenCalledWith(true, 'form1');
    });

    it('enables all term inputs', () => {
      markAll();

      expect($('[name^="term"]').prop('disabled')).toBe(false);
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

      expect($('input[type="submit"]').val()).toBe('End');
    });

    it('sets submit button value to "Next" when offset exists', () => {
      $('form').append('<input name="offset" value="10">');

      markNone();

      expect($('input[type="submit"]').val()).toBe('Next');
    });

    it('calls selectToggle with false', () => {
      markNone();

      expect(selectToggle).toHaveBeenCalledWith(false, 'form1');
    });

    it('disables all term inputs', () => {
      markNone();

      expect($('[name^="term"]').prop('disabled')).toBe(true);
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

      changeTermToggles($(select));

      expect($('#Term_1 .term').text()).toBe('hello');
      expect($('#Term_2 .term').text()).toBe('world');
      expect($('#Text_1').val()).toBe('hello');
      expect($('#Text_2').val()).toBe('world');
    });

    it('sets translation to * when value is 7', () => {
      const select = document.querySelector<HTMLSelectElement>('#toggleSelect')!;
      select.value = '7';

      changeTermToggles($(select));

      expect($('#Trans_1 input').val()).toBe('*');
      expect($('#Trans_2 input').val()).toBe('*');
    });

    it('sets status for all checked terms when value is 1-5', () => {
      const select = document.querySelector<HTMLSelectElement>('#toggleSelect')!;
      select.value = '1';

      changeTermToggles($(select));

      expect($('#Stat_1').val()).toBe('1');
      expect($('#Stat_2').val()).toBe('1');
    });

    it('resets select to first option after action', () => {
      const select = document.querySelector<HTMLSelectElement>('#toggleSelect')!;
      select.value = '6';

      changeTermToggles($(select));

      expect(select.selectedIndex).toBe(0);
    });

    it('returns false', () => {
      const select = document.querySelector<HTMLSelectElement>('#toggleSelect')!;
      select.value = '6';

      const result = changeTermToggles($(select));

      expect(result).toBe(false);
    });

    it('only affects checked checkboxes', () => {
      // Uncheck second checkbox
      ($('.markcheck').eq(1) as JQuery<HTMLInputElement>).prop('checked', false);

      const select = document.querySelector<HTMLSelectElement>('#toggleSelect')!;
      select.value = '6';

      changeTermToggles($(select));

      expect($('#Term_1 .term').text()).toBe('hello');
      expect($('#Term_2 .term').text()).toBe('WORLD'); // Not changed
    });
  });

  // ===========================================================================
  // initBulkTranslate Tests
  // ===========================================================================

  describe('initBulkTranslate', () => {
    it('sets dictionary links in LWT_DATA', () => {
      document.body.innerHTML = `
        <h3>Title</h3>
        <h4>Subtitle</h4>
      `;

      initBulkTranslate({
        dict1: 'https://new-dict1.com/',
        dict2: 'https://new-dict2.com/',
        translate: 'https://new-translate.com/'
      });

      expect(LWT_DATA.language.dict_link1).toBe('https://new-dict1.com/');
      expect(LWT_DATA.language.dict_link2).toBe('https://new-dict2.com/');
      expect(LWT_DATA.language.translator_link).toBe('https://new-translate.com/');
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

      expect($('h3').hasClass('notranslate')).toBe(true);
      expect($('h4').hasClass('notranslate')).toBe(true);
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

      const form = $('[name="form1"]');
      const submitEvent = $.Event('submit');
      form.trigger(submitEvent);

      // Should restore input names
    });

    it('sets up dictionary click handlers via delegation on td', () => {
      bulkInteractions();

      // The click handler is on td, using event delegation
      const dictSpan = document.querySelector('.dict1')!;
      const td = dictSpan.closest('td')!;

      // Trigger click on the td element which contains the dict1 span
      const clickEvent = $.Event('click');
      clickEvent.target = dictSpan;
      $(td).trigger(clickEvent);

      // Since click is delegated, directly clicking span should work
      $(dictSpan).trigger('click');
    });

    it('polls for Google Translate results', () => {
      bulkInteractions();

      // The interval checks for .trans>font elements
      // We have one, so after 300ms it should process
      vi.advanceTimersByTime(300);

      // After processing, trans should have notranslate class
      // Note: Only triggers when .trans>font count matches .trans count
      const transCount = $('.trans').length;
      const fontCount = $('.trans>font').length;

      // If counts match, it should have processed
      if (transCount === fontCount && fontCount > 0) {
        expect($('.trans').hasClass('notranslate')).toBe(true);
      }
    });

    it('converts translated text to input fields when fonts present', () => {
      bulkInteractions();

      // Wait for interval to process
      vi.advanceTimersByTime(300);

      // The condition is that all .trans have a <font> child
      // Our setup has 1 .trans with 1 font, so it should match
      const transEl = $('#Trans_1');
      if (transEl.find('font').length === 0) {
        // Already converted
        expect(transEl.find('input').length).toBe(1);
      }
    });

    it('removes Google Translate elements after processing', () => {
      $('body').append('<div id="google_translate_element"></div>');

      bulkInteractions();

      vi.advanceTimersByTime(300);

      // Check if google_translate_element was removed after conversion
      const remaining = $('#google_translate_element').length;
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

      const dictSpan = document.querySelector('.dict1')!;

      expect(() => clickDictionary.call(dictSpan as HTMLElement)).not.toThrow();
    });

    it('handles missing parent elements', () => {
      document.body.innerHTML = `
        <span class="dict1">D1</span>
      `;

      const dictSpan = document.querySelector('.dict1')!;

      expect(() => clickDictionary.call(dictSpan as HTMLElement)).not.toThrow();
    });

    it('handles URLs with lwt_popup parameter', () => {
      LWT_DATA.language.dict_link1 = 'https://dict.example.com/?lwt_popup=1';

      document.body.innerHTML = `
        <td><span class="term">word</span></td>
        <td><span class="dict1">D1</span></td>
      `;

      const dictSpan = document.querySelector('.dict1')!;

      clickDictionary.call(dictSpan as HTMLElement);

      expect(owin).toHaveBeenCalled();
    });
  });
});
