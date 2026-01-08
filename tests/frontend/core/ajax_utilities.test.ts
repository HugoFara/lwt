/**
 * Tests for ajax_utilities.ts - AJAX utilities for LWT
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  saveSetting,
  scrollToAnchor,
  getPositionFromId,
  copySelectValueToInput
} from '../../../src/frontend/js/shared/utils/ajax_utilities';

// Mock the SettingsApi module
vi.mock('../../../src/frontend/js/modules/admin/api/settings_api', () => ({
  SettingsApi: {
    save: vi.fn().mockResolvedValue({ ok: true, data: {} })
  }
}));

import { SettingsApi } from '../../../src/frontend/js/modules/admin/api/settings_api';

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
  // saveSetting Tests
  // ===========================================================================

  describe('saveSetting', () => {
    it('calls SettingsApi.save with key and value', () => {
      saveSetting('theme', 'dark');

      expect(SettingsApi.save).toHaveBeenCalledWith('theme', 'dark');
    });

    it('sends correct key-value pair', () => {
      saveSetting('language_id', '5');

      expect(SettingsApi.save).toHaveBeenCalledWith('language_id', '5');
    });

    it('handles empty values', () => {
      saveSetting('filter', '');

      expect(SettingsApi.save).toHaveBeenCalledWith('filter', '');
    });

    it('handles special characters in values', () => {
      saveSetting('query', 'test&value=something');

      expect(SettingsApi.save).toHaveBeenCalledWith('query', 'test&value=something');
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
  // getPositionFromId Tests
  // ===========================================================================

  describe('getPositionFromId', () => {
    it('extracts position from standard ID format', () => {
      // Formula: arr[1] * 10 + 10 - arr[2]
      // ID-3-1 => 3 * 10 + 10 - 1 = 39
      const result = getPositionFromId('ID-3-1');

      expect(result).toBe(39);
    });

    it('calculates correctly for various IDs', () => {
      // ID-5-2 => 5 * 10 + 10 - 2 = 58
      expect(getPositionFromId('ID-5-2')).toBe(58);

      // ID-10-5 => 10 * 10 + 10 - 5 = 105
      expect(getPositionFromId('ID-10-5')).toBe(105);

      // ID-0-1 => 0 * 10 + 10 - 1 = 9
      expect(getPositionFromId('ID-0-1')).toBe(9);
    });

    it('returns -1 for undefined input', () => {
      const result = getPositionFromId(undefined as unknown as string);

      expect(result).toBe(-1);
    });

    it('handles ID with larger numbers', () => {
      // ID-100-9 => 100 * 10 + 10 - 9 = 1001
      const result = getPositionFromId('ID-100-9');

      expect(result).toBe(1001);
    });

    it('returns NaN for malformed ID', () => {
      const result = getPositionFromId('invalid');

      expect(result).toBeNaN();
    });

    it('returns NaN for ID with non-numeric parts', () => {
      const result = getPositionFromId('ID-abc-xyz');

      expect(result).toBeNaN();
    });

    it('handles ID with extra parts', () => {
      // Only uses first 3 parts split by '-'
      // ID-5-3-extra => 5 * 10 + 10 - 3 = 57
      const result = getPositionFromId('ID-5-3-extra');

      expect(result).toBe(57);
    });

    it('handles empty string', () => {
      const result = getPositionFromId('');

      // Empty split gives [''], arr[1] is undefined => NaN
      expect(result).toBeNaN();
    });
  });

  // ===========================================================================
  // copySelectValueToInput Tests
  // ===========================================================================

  describe('copySelectValueToInput', () => {
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

      copySelectValueToInput(selectElem, inputElem);

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

      copySelectValueToInput(selectElem, inputElem);

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

      copySelectValueToInput(selectElem, inputElem);

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

      copySelectValueToInput(selectElem, inputElem);

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

      copySelectValueToInput(selectElem, inputElem);

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

      copySelectValueToInput(selectElem, inputElem);

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

      copySelectValueToInput(selectElem, inputElem);

      expect(inputElem.value).toBe('日本語');
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('saveSetting handles Unicode keys and values', () => {
      saveSetting('설정', '한국어');

      expect(SettingsApi.save).toHaveBeenCalledWith('설정', '한국어');
    });

    it('getPositionFromId handles single hyphen', () => {
      // 'ID-5' => arr[1]=5, arr[2]=undefined => 5*10+10-NaN = NaN
      const result = getPositionFromId('ID-5');

      expect(result).toBeNaN();
    });

    it('copySelectValueToInput handles whitespace-only value', () => {
      document.body.innerHTML = `
        <select id="quick-select">
          <option value="   " selected>Spaces</option>
        </select>
        <input type="text" id="target-input" />
      `;

      const selectElem = document.getElementById('quick-select') as HTMLSelectElement;
      const inputElem = document.getElementById('target-input') as HTMLInputElement;

      copySelectValueToInput(selectElem, inputElem);

      // Whitespace is not empty string, so it should be assigned
      expect(inputElem.value).toBe('   ');
    });
  });
});
