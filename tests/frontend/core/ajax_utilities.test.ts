/**
 * Tests for ajax_utilities.ts - AJAX utilities for LWT
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import $ from 'jquery';
import {
  do_ajax_save_setting,
  scrollToAnchor,
  get_position_from_id,
  quick_select_to_input
} from '../../../src/frontend/js/core/ajax_utilities';

// Make jQuery available globally
(global as any).$ = $;
(global as any).jQuery = $;

describe('ajax_utilities.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // do_ajax_save_setting Tests
  // ===========================================================================

  describe('do_ajax_save_setting', () => {
    it('makes POST request to settings API', () => {
      const postSpy = vi.spyOn($, 'post').mockImplementation(() => ({} as any));

      do_ajax_save_setting('theme', 'dark');

      expect(postSpy).toHaveBeenCalledWith(
        'api.php/v1/settings',
        {
          key: 'theme',
          value: 'dark'
        }
      );
    });

    it('sends correct key-value pair', () => {
      const postSpy = vi.spyOn($, 'post').mockImplementation(() => ({} as any));

      do_ajax_save_setting('language_id', '5');

      expect(postSpy).toHaveBeenCalledWith(
        'api.php/v1/settings',
        expect.objectContaining({
          key: 'language_id',
          value: '5'
        })
      );
    });

    it('handles empty values', () => {
      const postSpy = vi.spyOn($, 'post').mockImplementation(() => ({} as any));

      do_ajax_save_setting('filter', '');

      expect(postSpy).toHaveBeenCalledWith(
        'api.php/v1/settings',
        expect.objectContaining({
          key: 'filter',
          value: ''
        })
      );
    });

    it('handles special characters in values', () => {
      const postSpy = vi.spyOn($, 'post').mockImplementation(() => ({} as any));

      do_ajax_save_setting('query', 'test&value=something');

      expect(postSpy).toHaveBeenCalledWith(
        'api.php/v1/settings',
        expect.objectContaining({
          key: 'query',
          value: 'test&value=something'
        })
      );
    });
  });

  // ===========================================================================
  // scrollToAnchor Tests
  // ===========================================================================

  describe('scrollToAnchor', () => {
    it('sets location hash to anchor ID', () => {
      // The function sets document.location.href = '#' + aid
      // In jsdom this will update location.hash
      scrollToAnchor('section1');

      expect(document.location.hash).toBe('#section1');
    });

    it('handles anchor with special characters', () => {
      scrollToAnchor('section-1_test');

      expect(document.location.hash).toBe('#section-1_test');
    });

    it('handles empty anchor ID', () => {
      scrollToAnchor('');

      expect(document.location.hash).toBe('');
    });

    it('handles numeric anchor ID', () => {
      scrollToAnchor('123');

      expect(document.location.hash).toBe('#123');
    });
  });

  // ===========================================================================
  // get_position_from_id Tests
  // ===========================================================================

  describe('get_position_from_id', () => {
    it('extracts position from standard ID format', () => {
      // Formula: arr[1] * 10 + 10 - arr[2]
      // ID-3-1 => 3 * 10 + 10 - 1 = 39
      const result = get_position_from_id('ID-3-1');

      expect(result).toBe(39);
    });

    it('calculates correctly for various IDs', () => {
      // ID-5-2 => 5 * 10 + 10 - 2 = 58
      expect(get_position_from_id('ID-5-2')).toBe(58);

      // ID-10-5 => 10 * 10 + 10 - 5 = 105
      expect(get_position_from_id('ID-10-5')).toBe(105);

      // ID-0-1 => 0 * 10 + 10 - 1 = 9
      expect(get_position_from_id('ID-0-1')).toBe(9);
    });

    it('returns -1 for undefined input', () => {
      const result = get_position_from_id(undefined as unknown as string);

      expect(result).toBe(-1);
    });

    it('handles ID with larger numbers', () => {
      // ID-100-9 => 100 * 10 + 10 - 9 = 1001
      const result = get_position_from_id('ID-100-9');

      expect(result).toBe(1001);
    });

    it('returns NaN for malformed ID', () => {
      const result = get_position_from_id('invalid');

      expect(result).toBeNaN();
    });

    it('returns NaN for ID with non-numeric parts', () => {
      const result = get_position_from_id('ID-abc-xyz');

      expect(result).toBeNaN();
    });

    it('handles ID with extra parts', () => {
      // Only uses first 3 parts split by '-'
      // ID-5-3-extra => 5 * 10 + 10 - 3 = 57
      const result = get_position_from_id('ID-5-3-extra');

      expect(result).toBe(57);
    });

    it('handles empty string', () => {
      const result = get_position_from_id('');

      // Empty split gives [''], arr[1] is undefined => NaN
      expect(result).toBeNaN();
    });
  });

  // ===========================================================================
  // quick_select_to_input Tests
  // ===========================================================================

  describe('quick_select_to_input', () => {
    it('assigns selected option value to input', () => {
      document.body.innerHTML = `
        <select id="quick-select">
          <option value="">Select...</option>
          <option value="option1" selected>Option 1</option>
          <option value="option2">Option 2</option>
        </select>
        <input type="text" id="target-input" value="" />
      `;

      const selectElem = document.getElementById('quick-select') as HTMLSelectElement;
      const inputElem = document.getElementById('target-input') as HTMLInputElement;

      quick_select_to_input(selectElem, inputElem);

      expect(inputElem.value).toBe('option1');
      expect(selectElem.value).toBe('');
    });

    it('does not change input when selected value is empty', () => {
      document.body.innerHTML = `
        <select id="quick-select">
          <option value="" selected>Select...</option>
          <option value="option1">Option 1</option>
        </select>
        <input type="text" id="target-input" value="original" />
      `;

      const selectElem = document.getElementById('quick-select') as HTMLSelectElement;
      const inputElem = document.getElementById('target-input') as HTMLInputElement;

      quick_select_to_input(selectElem, inputElem);

      expect(inputElem.value).toBe('original');
      expect(selectElem.value).toBe('');
    });

    it('resets select to empty after transfer', () => {
      document.body.innerHTML = `
        <select id="quick-select">
          <option value="">Select...</option>
          <option value="test" selected>Test</option>
        </select>
        <input type="text" id="target-input" />
      `;

      const selectElem = document.getElementById('quick-select') as HTMLSelectElement;
      const inputElem = document.getElementById('target-input') as HTMLInputElement;

      quick_select_to_input(selectElem, inputElem);

      expect(selectElem.value).toBe('');
    });

    it('overwrites existing input value', () => {
      document.body.innerHTML = `
        <select id="quick-select">
          <option value="new-value" selected>New</option>
        </select>
        <input type="text" id="target-input" value="old-value" />
      `;

      const selectElem = document.getElementById('quick-select') as HTMLSelectElement;
      const inputElem = document.getElementById('target-input') as HTMLInputElement;

      quick_select_to_input(selectElem, inputElem);

      expect(inputElem.value).toBe('new-value');
    });

    it('handles select with special characters in value', () => {
      document.body.innerHTML = `
        <select id="quick-select">
          <option value="test&value" selected>Test</option>
        </select>
        <input type="text" id="target-input" />
      `;

      const selectElem = document.getElementById('quick-select') as HTMLSelectElement;
      const inputElem = document.getElementById('target-input') as HTMLInputElement;

      quick_select_to_input(selectElem, inputElem);

      expect(inputElem.value).toBe('test&value');
    });

    it('handles select with numeric value', () => {
      document.body.innerHTML = `
        <select id="quick-select">
          <option value="42" selected>Forty Two</option>
        </select>
        <input type="text" id="target-input" />
      `;

      const selectElem = document.getElementById('quick-select') as HTMLSelectElement;
      const inputElem = document.getElementById('target-input') as HTMLInputElement;

      quick_select_to_input(selectElem, inputElem);

      expect(inputElem.value).toBe('42');
    });

    it('handles select with Unicode value', () => {
      document.body.innerHTML = `
        <select id="quick-select">
          <option value="日本語" selected>Japanese</option>
        </select>
        <input type="text" id="target-input" />
      `;

      const selectElem = document.getElementById('quick-select') as HTMLSelectElement;
      const inputElem = document.getElementById('target-input') as HTMLInputElement;

      quick_select_to_input(selectElem, inputElem);

      expect(inputElem.value).toBe('日本語');
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('do_ajax_save_setting handles Unicode keys and values', () => {
      const postSpy = vi.spyOn($, 'post').mockImplementation(() => ({} as any));

      do_ajax_save_setting('설정', '한국어');

      expect(postSpy).toHaveBeenCalledWith(
        'api.php/v1/settings',
        expect.objectContaining({
          key: '설정',
          value: '한국어'
        })
      );
    });

    it('get_position_from_id handles single hyphen', () => {
      // 'ID-5' => arr[1]=5, arr[2]=undefined => 5*10+10-NaN = NaN
      const result = get_position_from_id('ID-5');

      expect(result).toBeNaN();
    });

    it('quick_select_to_input handles whitespace-only value', () => {
      document.body.innerHTML = `
        <select id="quick-select">
          <option value="   " selected>Spaces</option>
        </select>
        <input type="text" id="target-input" />
      `;

      const selectElem = document.getElementById('quick-select') as HTMLSelectElement;
      const inputElem = document.getElementById('target-input') as HTMLInputElement;

      quick_select_to_input(selectElem, inputElem);

      // Whitespace is not empty string, so it should be assigned
      expect(inputElem.value).toBe('   ');
    });
  });
});
