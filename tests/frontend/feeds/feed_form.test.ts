/**
 * Tests for feed_form.ts - Feed form interactions
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import $ from 'jquery';
import { initFeedForm } from '../../../src/frontend/js/feeds/feed_form';

// Make jQuery available globally
(global as any).$ = $;
(global as any).jQuery = $;

describe('feed_form.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // initFeedForm Tests
  // ===========================================================================

  describe('initFeedForm', () => {
    it('does nothing when NfOptions field is missing', () => {
      document.body.innerHTML = '<form><input type="text" name="other" /></form>';

      expect(() => initFeedForm()).not.toThrow();
    });

    it('initializes when NfOptions field exists', () => {
      document.body.innerHTML = `
        <form class="validate">
          <input type="hidden" name="NfOptions" value="" />
          <input type="checkbox" name="c_filter" />
        </form>
      `;

      expect(() => initFeedForm()).not.toThrow();
    });
  });

  // ===========================================================================
  // Checkbox Change Handler Tests
  // ===========================================================================

  describe('Option Checkbox Change Handler', () => {
    it('enables text input when checkbox is checked', () => {
      document.body.innerHTML = `
        <form class="validate">
          <input type="hidden" name="NfOptions" value="" />
          <div>
            <input type="checkbox" name="c_filter" />
            <input type="text" name="filter" disabled />
          </div>
        </form>
      `;

      initFeedForm();

      const checkbox = document.querySelector<HTMLInputElement>('[name="c_filter"]')!;
      const textInput = document.querySelector<HTMLInputElement>('[name="filter"]')!;

      checkbox.checked = true;
      checkbox.dispatchEvent(new Event('change'));

      expect(textInput.hasAttribute('disabled')).toBe(false);
    });

    it('disables text input when checkbox is unchecked', () => {
      document.body.innerHTML = `
        <form class="validate">
          <input type="hidden" name="NfOptions" value="" />
          <div>
            <input type="checkbox" name="c_filter" checked />
            <input type="text" name="filter" />
          </div>
        </form>
      `;

      initFeedForm();

      const checkbox = document.querySelector<HTMLInputElement>('[name="c_filter"]')!;
      const textInput = document.querySelector<HTMLInputElement>('[name="filter"]')!;

      checkbox.checked = false;
      checkbox.dispatchEvent(new Event('change'));

      expect(textInput.hasAttribute('disabled')).toBe(true);
    });

    it('adds notempty class when checkbox is checked', () => {
      document.body.innerHTML = `
        <form class="validate">
          <input type="hidden" name="NfOptions" value="" />
          <div>
            <input type="checkbox" name="c_filter" />
            <input type="text" name="filter" disabled />
          </div>
        </form>
      `;

      initFeedForm();

      const checkbox = document.querySelector<HTMLInputElement>('[name="c_filter"]')!;
      const textInput = document.querySelector<HTMLInputElement>('[name="filter"]')!;

      checkbox.checked = true;
      checkbox.dispatchEvent(new Event('change'));

      expect(textInput.classList.contains('notempty')).toBe(true);
    });

    it('removes notempty class when checkbox is unchecked', () => {
      document.body.innerHTML = `
        <form class="validate">
          <input type="hidden" name="NfOptions" value="" />
          <div>
            <input type="checkbox" name="c_filter" checked />
            <input type="text" name="filter" class="notempty" />
          </div>
        </form>
      `;

      initFeedForm();

      const checkbox = document.querySelector<HTMLInputElement>('[name="c_filter"]')!;
      const textInput = document.querySelector<HTMLInputElement>('[name="filter"]')!;

      checkbox.checked = false;
      checkbox.dispatchEvent(new Event('change'));

      expect(textInput.classList.contains('notempty')).toBe(false);
    });

    it('enables select when checkbox is checked', () => {
      document.body.innerHTML = `
        <form class="validate">
          <input type="hidden" name="NfOptions" value="" />
          <div>
            <input type="checkbox" name="c_autoupdate" />
            <input type="number" name="autoupdate" disabled />
            <select name="autoupdate_unit" disabled>
              <option value="h">Hours</option>
              <option value="d">Days</option>
            </select>
          </div>
        </form>
      `;

      initFeedForm();

      const checkbox = document.querySelector<HTMLInputElement>('[name="c_autoupdate"]')!;
      const select = document.querySelector<HTMLSelectElement>('[name="autoupdate_unit"]')!;

      checkbox.checked = true;
      checkbox.dispatchEvent(new Event('change'));

      expect(select.hasAttribute('disabled')).toBe(false);
    });

    it('disables select when checkbox is unchecked', () => {
      document.body.innerHTML = `
        <form class="validate">
          <input type="hidden" name="NfOptions" value="" />
          <div>
            <input type="checkbox" name="c_autoupdate" checked />
            <input type="number" name="autoupdate" />
            <select name="autoupdate_unit">
              <option value="h">Hours</option>
            </select>
          </div>
        </form>
      `;

      initFeedForm();

      const checkbox = document.querySelector<HTMLInputElement>('[name="c_autoupdate"]')!;
      const select = document.querySelector<HTMLSelectElement>('[name="autoupdate_unit"]')!;

      checkbox.checked = false;
      checkbox.dispatchEvent(new Event('change'));

      expect(select.hasAttribute('disabled')).toBe(true);
    });

    it('handles number input same as text input', () => {
      document.body.innerHTML = `
        <form class="validate">
          <input type="hidden" name="NfOptions" value="" />
          <div>
            <input type="checkbox" name="c_limit" />
            <input type="number" name="limit" disabled />
          </div>
        </form>
      `;

      initFeedForm();

      const checkbox = document.querySelector<HTMLInputElement>('[name="c_limit"]')!;
      const numberInput = document.querySelector<HTMLInputElement>('[name="limit"]')!;

      checkbox.checked = true;
      checkbox.dispatchEvent(new Event('change'));

      expect(numberInput.hasAttribute('disabled')).toBe(false);
      expect(numberInput.classList.contains('notempty')).toBe(true);
    });
  });

  // ===========================================================================
  // Serialize Feed Options Tests
  // ===========================================================================

  describe('Serialize Feed Options', () => {
    it('serializes checked options on submit button click', () => {
      document.body.innerHTML = `
        <form class="validate">
          <input type="hidden" name="NfOptions" value="" />
          <div>
            <input type="checkbox" name="c_filter" checked />
            <input type="text" name="filter" value="test" />
          </div>
          <button type="submit">Save</button>
        </form>
      `;

      initFeedForm();

      const submitButton = document.querySelector<HTMLButtonElement>('[type="submit"]')!;
      submitButton.click();

      const nfOptions = document.querySelector<HTMLInputElement>('[name="NfOptions"]')!;
      expect(nfOptions.value).toContain('filter=test');
    });

    it('includes edit_text option when checked', () => {
      document.body.innerHTML = `
        <form class="validate">
          <input type="hidden" name="NfOptions" value="" />
          <input type="checkbox" name="edit_text" checked />
          <button type="submit">Save</button>
        </form>
      `;

      initFeedForm();

      const submitButton = document.querySelector<HTMLButtonElement>('[type="submit"]')!;
      submitButton.click();

      const nfOptions = document.querySelector<HTMLInputElement>('[name="NfOptions"]')!;
      expect(nfOptions.value).toContain('edit_text=1');
    });

    it('does not include edit_text when unchecked', () => {
      document.body.innerHTML = `
        <form class="validate">
          <input type="hidden" name="NfOptions" value="" />
          <input type="checkbox" name="edit_text" />
          <button type="submit">Save</button>
        </form>
      `;

      initFeedForm();

      const submitButton = document.querySelector<HTMLButtonElement>('[type="submit"]')!;
      submitButton.click();

      const nfOptions = document.querySelector<HTMLInputElement>('[name="NfOptions"]')!;
      expect(nfOptions.value).not.toContain('edit_text');
    });

    it('includes select value for autoupdate option', () => {
      document.body.innerHTML = `
        <form class="validate">
          <input type="hidden" name="NfOptions" value="" />
          <div>
            <input type="checkbox" name="c_autoupdate" checked />
            <input type="number" name="autoupdate" value="24" />
            <select name="autoupdate_unit">
              <option value="h" selected>Hours</option>
              <option value="d">Days</option>
            </select>
          </div>
          <button type="submit">Save</button>
        </form>
      `;

      initFeedForm();

      const submitButton = document.querySelector<HTMLButtonElement>('[type="submit"]')!;
      submitButton.click();

      const nfOptions = document.querySelector<HTMLInputElement>('[name="NfOptions"]')!;
      expect(nfOptions.value).toContain('autoupdate=24h');
    });

    it('serializes multiple options', () => {
      document.body.innerHTML = `
        <form class="validate">
          <input type="hidden" name="NfOptions" value="" />
          <input type="checkbox" name="edit_text" checked />
          <div>
            <input type="checkbox" name="c_filter" checked />
            <input type="text" name="filter" value="keyword" />
          </div>
          <div>
            <input type="checkbox" name="c_limit" checked />
            <input type="number" name="limit" value="10" />
          </div>
          <button type="submit">Save</button>
        </form>
      `;

      initFeedForm();

      const submitButton = document.querySelector<HTMLButtonElement>('[type="submit"]')!;
      submitButton.click();

      const nfOptions = document.querySelector<HTMLInputElement>('[name="NfOptions"]')!;
      expect(nfOptions.value).toContain('edit_text=1');
      expect(nfOptions.value).toContain('filter=keyword');
      expect(nfOptions.value).toContain('limit=10');
    });

    it('skips unchecked options', () => {
      document.body.innerHTML = `
        <form class="validate">
          <input type="hidden" name="NfOptions" value="" />
          <div>
            <input type="checkbox" name="c_filter" />
            <input type="text" name="filter" value="should-not-appear" />
          </div>
          <div>
            <input type="checkbox" name="c_limit" checked />
            <input type="number" name="limit" value="5" />
          </div>
          <button type="submit">Save</button>
        </form>
      `;

      initFeedForm();

      const submitButton = document.querySelector<HTMLButtonElement>('[type="submit"]')!;
      submitButton.click();

      const nfOptions = document.querySelector<HTMLInputElement>('[name="NfOptions"]')!;
      expect(nfOptions.value).not.toContain('filter=');
      expect(nfOptions.value).toContain('limit=5');
    });

    it('handles empty input values', () => {
      document.body.innerHTML = `
        <form class="validate">
          <input type="hidden" name="NfOptions" value="" />
          <div>
            <input type="checkbox" name="c_filter" checked />
            <input type="text" name="filter" value="" />
          </div>
          <button type="submit">Save</button>
        </form>
      `;

      initFeedForm();

      const submitButton = document.querySelector<HTMLButtonElement>('[type="submit"]')!;
      submitButton.click();

      const nfOptions = document.querySelector<HTMLInputElement>('[name="NfOptions"]')!;
      expect(nfOptions.value).toContain('filter=,');
    });

    it('serializes on form submit event', () => {
      document.body.innerHTML = `
        <form class="validate">
          <input type="hidden" name="NfOptions" value="" />
          <div>
            <input type="checkbox" name="c_filter" checked />
            <input type="text" name="filter" value="via-submit" />
          </div>
          <button type="submit">Save</button>
        </form>
      `;

      initFeedForm();

      const form = document.querySelector<HTMLFormElement>('form.validate')!;
      form.dispatchEvent(new Event('submit'));

      const nfOptions = document.querySelector<HTMLInputElement>('[name="NfOptions"]')!;
      expect(nfOptions.value).toContain('filter=via-submit');
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles checkbox without parent container gracefully', () => {
      document.body.innerHTML = `
        <form class="validate">
          <input type="hidden" name="NfOptions" value="" />
          <input type="checkbox" name="c_orphan" />
        </form>
      `;

      initFeedForm();

      const checkbox = document.querySelector<HTMLInputElement>('[name="c_orphan"]')!;

      // Should not throw even when there are no sibling inputs
      expect(() => {
        checkbox.checked = true;
        checkbox.dispatchEvent(new Event('change'));
      }).not.toThrow();
    });

    it('handles multiple text inputs in parent', () => {
      document.body.innerHTML = `
        <form class="validate">
          <input type="hidden" name="NfOptions" value="" />
          <div>
            <input type="checkbox" name="c_multi" />
            <input type="text" name="input1" disabled />
            <input type="text" name="input2" disabled />
          </div>
        </form>
      `;

      initFeedForm();

      const checkbox = document.querySelector<HTMLInputElement>('[name="c_multi"]')!;

      checkbox.checked = true;
      checkbox.dispatchEvent(new Event('change'));

      const inputs = document.querySelectorAll<HTMLInputElement>('input[type="text"]');
      inputs.forEach(input => {
        expect(input.hasAttribute('disabled')).toBe(false);
        expect(input.classList.contains('notempty')).toBe(true);
      });
    });

    it('handles input without name attribute', () => {
      document.body.innerHTML = `
        <form class="validate">
          <input type="hidden" name="NfOptions" value="" />
          <div>
            <input type="checkbox" name="c_noname" checked />
            <input type="text" value="value-without-name" />
          </div>
          <button type="submit">Save</button>
        </form>
      `;

      initFeedForm();

      const submitButton = document.querySelector<HTMLButtonElement>('[type="submit"]')!;

      // Should not throw
      expect(() => submitButton.click()).not.toThrow();
    });

    it('handles select without value', () => {
      document.body.innerHTML = `
        <form class="validate">
          <input type="hidden" name="NfOptions" value="" />
          <div>
            <input type="checkbox" name="c_autoupdate" checked />
            <input type="number" name="autoupdate" value="12" />
            <select name="autoupdate_unit">
            </select>
          </div>
          <button type="submit">Save</button>
        </form>
      `;

      initFeedForm();

      const submitButton = document.querySelector<HTMLButtonElement>('[type="submit"]')!;
      submitButton.click();

      const nfOptions = document.querySelector<HTMLInputElement>('[name="NfOptions"]')!;
      // Empty select value should result in just the number
      expect(nfOptions.value).toContain('autoupdate=12');
    });
  });
});
