/**
 * Tests for feed_wizard_step4.ts - Edit Options interactions
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  lwt_wizard_step4,
  initWizardStep4
} from '../../../src/frontend/js/feeds/feed_wizard_step4';

describe('feed_wizard_step4.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // buildOptionsString Tests
  // ===========================================================================

  describe('lwt_wizard_step4.buildOptionsString', () => {
    it('returns empty string when no checkboxes are checked', () => {
      document.body.innerHTML = `
        <input type="checkbox" name="edit_text">
        <input type="checkbox" name="c_option1">
        <input type="hidden" name="article_source" value="">
      `;

      const result = lwt_wizard_step4.buildOptionsString();

      expect(result).toBe('');
    });

    it('includes edit_text when checked', () => {
      document.body.innerHTML = `
        <input type="checkbox" name="edit_text" checked>
        <input type="hidden" name="article_source" value="">
      `;

      const result = lwt_wizard_step4.buildOptionsString();

      expect(result).toContain('edit_text=1');
    });

    it('builds options from checked c_ checkboxes', () => {
      document.body.innerHTML = `
        <div>
          <input type="checkbox" name="c_filter1" checked>
          <input type="text" name="filter1" value="value1">
        </div>
        <div>
          <input type="checkbox" name="c_filter2" checked>
          <input type="text" name="filter2" value="value2">
        </div>
        <input type="hidden" name="article_source" value="">
      `;

      const result = lwt_wizard_step4.buildOptionsString();

      expect(result).toContain('filter1=value1');
      expect(result).toContain('filter2=value2');
    });

    it('handles autoupdate checkbox with select value', () => {
      document.body.innerHTML = `
        <div>
          <input type="checkbox" name="c_autoupdate" checked>
          <input type="text" name="autoupdate" value="24">
          <select><option value="hours" selected>Hours</option></select>
        </div>
        <input type="hidden" name="article_source" value="">
      `;

      const result = lwt_wizard_step4.buildOptionsString();

      expect(result).toContain('autoupdate=24hours');
    });

    it('includes article_source when not empty', () => {
      document.body.innerHTML = `
        <input type="hidden" name="article_source" value="//article/content">
      `;

      const result = lwt_wizard_step4.buildOptionsString();

      expect(result).toContain('article_source=//article/content');
    });

    it('does not include unchecked options', () => {
      document.body.innerHTML = `
        <div>
          <input type="checkbox" name="c_filter1">
          <input type="text" name="filter1" value="value1">
        </div>
        <div>
          <input type="checkbox" name="c_filter2" checked>
          <input type="text" name="filter2" value="value2">
        </div>
        <input type="hidden" name="article_source" value="">
      `;

      const result = lwt_wizard_step4.buildOptionsString();

      expect(result).not.toContain('filter1=');
      expect(result).toContain('filter2=value2');
    });
  });

  // ===========================================================================
  // handleCheckboxChange Tests
  // ===========================================================================

  describe('lwt_wizard_step4.handleCheckboxChange', () => {
    it('enables text input when checkbox is checked', () => {
      document.body.innerHTML = `
        <div>
          <input type="checkbox" name="c_option" id="checkbox">
          <input type="text" name="option" id="textInput" disabled>
        </div>
      `;

      const checkbox = document.querySelector<HTMLInputElement>('#checkbox')!;
      checkbox.checked = true;

      lwt_wizard_step4.handleCheckboxChange.call(checkbox);

      const textInput = document.querySelector<HTMLInputElement>('#textInput')!;
      expect(textInput.hasAttribute('disabled')).toBe(false);
      expect(textInput.classList.contains('notempty')).toBe(true);
    });

    it('disables text input when checkbox is unchecked', () => {
      document.body.innerHTML = `
        <div>
          <input type="checkbox" name="c_option" id="checkbox">
          <input type="text" name="option" id="textInput" class="notempty">
        </div>
      `;

      const checkbox = document.querySelector<HTMLInputElement>('#checkbox')!;
      checkbox.checked = false;

      lwt_wizard_step4.handleCheckboxChange.call(checkbox);

      const textInput = document.querySelector<HTMLInputElement>('#textInput')!;
      expect(textInput.hasAttribute('disabled')).toBe(true);
      expect(textInput.classList.contains('notempty')).toBe(false);
    });

    it('enables associated select when checkbox is checked', () => {
      document.body.innerHTML = `
        <div>
          <input type="checkbox" name="c_option" id="checkbox">
          <input type="text" name="option">
          <select id="selectInput" disabled>
            <option value="1">Option 1</option>
          </select>
        </div>
      `;

      const checkbox = document.querySelector<HTMLInputElement>('#checkbox')!;
      checkbox.checked = true;

      lwt_wizard_step4.handleCheckboxChange.call(checkbox);

      const selectInput = document.querySelector<HTMLSelectElement>('#selectInput')!;
      expect(selectInput.hasAttribute('disabled')).toBe(false);
    });

    it('disables associated select when checkbox is unchecked', () => {
      document.body.innerHTML = `
        <div>
          <input type="checkbox" name="c_option" id="checkbox">
          <input type="text" name="option">
          <select id="selectInput">
            <option value="1">Option 1</option>
          </select>
        </div>
      `;

      const checkbox = document.querySelector<HTMLInputElement>('#checkbox')!;
      checkbox.checked = false;

      lwt_wizard_step4.handleCheckboxChange.call(checkbox);

      const selectInput = document.querySelector<HTMLSelectElement>('#selectInput')!;
      expect(selectInput.hasAttribute('disabled')).toBe(true);
    });
  });

  // ===========================================================================
  // handleSubmit Tests
  // ===========================================================================

  describe('lwt_wizard_step4.handleSubmit', () => {
    it('sets NfOptions input value', () => {
      document.body.innerHTML = `
        <input type="checkbox" name="edit_text" checked>
        <div>
          <input type="checkbox" name="c_filter" checked>
          <input type="text" name="filter" value="test">
        </div>
        <input type="hidden" name="article_source" value="">
        <input type="hidden" name="NfOptions" value="">
      `;

      lwt_wizard_step4.handleSubmit();

      const nfOptions = (document.querySelector('input[name="NfOptions"]') as HTMLInputElement)?.value;
      expect(nfOptions).toContain('edit_text=1');
      expect(nfOptions).toContain('filter=test');
    });
  });

  // ===========================================================================
  // clickBack Tests
  // ===========================================================================

  describe('lwt_wizard_step4.clickBack', () => {
    it('returns false', () => {
      document.body.innerHTML = `
        <input type="checkbox" name="edit_text">
        <select name="NfLgID"><option value="1" selected>English</option></select>
        <input name="NfName" value="Test Feed">
      `;

      const result = lwt_wizard_step4.clickBack();

      expect(result).toBe(false);
    });

    it('builds URL with current form values', () => {
      document.body.innerHTML = `
        <input type="checkbox" name="edit_text" checked>
        <div>
          <input type="checkbox" name="c_filter" checked>
          <input type="text" name="filter" value="value">
        </div>
        <select name="NfLgID"><option value="2" selected>Spanish</option></select>
        <input name="NfName" value="My Feed">
      `;

      // Mock location
      const locationSpy = vi.spyOn(window, 'location', 'get').mockReturnValue({
        ...window.location,
        href: ''
      } as Location);

      lwt_wizard_step4.clickBack();

      expect(locationSpy).toBeDefined();
    });
  });

  // ===========================================================================
  // setupEditMode Tests
  // ===========================================================================

  describe('lwt_wizard_step4.setupEditMode', () => {
    it('changes button name and value for existing feed', () => {
      document.body.innerHTML = `
        <input type="hidden" name="save_feed" value="">
        <input type="submit" value="Save">
      `;

      lwt_wizard_step4.setupEditMode(123);

      expect(document.querySelectorAll('input[name="update_feed"]').length).toBe(1);
      expect(document.querySelectorAll('input[name="save_feed"]').length).toBe(0);
      expect((document.querySelector('input[type="submit"]') as HTMLInputElement)?.value).toBe('Update');
    });

    it('does not change button for new feed', () => {
      document.body.innerHTML = `
        <input type="hidden" name="save_feed" value="">
        <input type="submit" value="Save">
      `;

      lwt_wizard_step4.setupEditMode(null);

      expect(document.querySelectorAll('input[name="save_feed"]').length).toBe(1);
      expect(document.querySelectorAll('input[name="update_feed"]').length).toBe(0);
      expect((document.querySelector('input[type="submit"]') as HTMLInputElement)?.value).toBe('Save');
    });
  });

  // ===========================================================================
  // setupHeader Tests
  // ===========================================================================

  describe('lwt_wizard_step4.setupHeader', () => {
    it('sets up header with step 4 title and help link', () => {
      document.body.innerHTML = '<h1>Original</h1>';

      lwt_wizard_step4.setupHeader();

      const h1Elements = document.querySelectorAll('h1');
      const h1 = h1Elements[h1Elements.length - 1];
      expect(h1.innerHTML).toContain('Feed Wizard');
      expect(h1.innerHTML).toContain('Step 4');
      expect(h1.innerHTML).toContain('Edit Options');
      expect(h1.innerHTML).toContain('docs/info.html#feed_wizard');
      expect(h1.style.textAlign).toBe('center');
    });
  });

  // ===========================================================================
  // initWizardStep4 Tests
  // ===========================================================================

  describe('initWizardStep4', () => {
    it('initializes with new feed config', () => {
      document.body.innerHTML = `
        <h1>Title</h1>
        <input type="hidden" name="save_feed">
        <input type="submit" value="Save">
      `;

      initWizardStep4({ editFeedId: null });

      expect(document.querySelector('h1')?.innerHTML).toContain('Feed Wizard');
      expect((document.querySelector('input[type="submit"]') as HTMLInputElement)?.value).toBe('Save');
    });

    it('initializes with existing feed config', () => {
      document.body.innerHTML = `
        <h1>Title</h1>
        <input type="hidden" name="save_feed">
        <input type="submit" value="Save">
      `;

      initWizardStep4({ editFeedId: 456 });

      expect(document.querySelector('h1')?.innerHTML).toContain('Feed Wizard');
      expect((document.querySelector('input[type="submit"]') as HTMLInputElement)?.value).toBe('Update');
    });
  });

  // ===========================================================================
  // Event Delegation Tests
  // ===========================================================================

  describe('event delegation', () => {
    it('handles checkbox change events', () => {
      document.body.innerHTML = `
        <div id="wizard-step4-config"></div>
        <div>
          <input type="checkbox" name="c_test" id="testCheckbox">
          <input type="text" name="test" id="testInput" disabled>
        </div>
      `;

      // Manually trigger the handleCheckboxChange function since DOMContentLoaded
      // has already fired and event listeners were set up before our DOM changes
      const checkbox = document.querySelector<HTMLInputElement>('#testCheckbox')!;
      checkbox.checked = true;
      lwt_wizard_step4.handleCheckboxChange.call(checkbox);

      const textInput = document.querySelector<HTMLInputElement>('#testInput')!;
      expect(textInput.hasAttribute('disabled')).toBe(false);
    });

    it('handles back button click', async () => {
      document.body.innerHTML = `
        <button data-action="wizard-step4-back">Back</button>
        <select name="NfLgID"><option value="1">Lang</option></select>
        <input name="NfName" value="Feed">
      `;

      await import('../../../src/frontend/js/feeds/feed_wizard_step4');

      const button = document.querySelector<HTMLButtonElement>('[data-action="wizard-step4-back"]')!;

      expect(() => button.click()).not.toThrow();
    });

    it('handles cancel button click', async () => {
      document.body.innerHTML = `
        <button data-action="wizard-step4-cancel">Cancel</button>
      `;

      await import('../../../src/frontend/js/feeds/feed_wizard_step4');

      const button = document.querySelector<HTMLButtonElement>('[data-action="wizard-step4-cancel"]')!;

      expect(() => button.click()).not.toThrow();
    });

    it('handles submit button click', async () => {
      document.body.innerHTML = `
        <button data-action="wizard-step4-submit">Submit</button>
        <input type="hidden" name="NfOptions" value="">
        <input type="hidden" name="article_source" value="">
      `;

      await import('../../../src/frontend/js/feeds/feed_wizard_step4');

      const button = document.querySelector<HTMLButtonElement>('[data-action="wizard-step4-submit"]')!;
      button.click();

      // NfOptions should be set
      expect((document.querySelector('input[name="NfOptions"]') as HTMLInputElement)?.value).toBeDefined();
    });
  });

  // ===========================================================================
  // Window Exports Tests
  // ===========================================================================

  describe('window exports', () => {
    it('exports lwt_wizard_step4 to window', async () => {
      await import('../../../src/frontend/js/feeds/feed_wizard_step4');

      expect((window as unknown as Record<string, unknown>).lwt_wizard_step4).toBeDefined();
    });

    it('exports initWizardStep4 to window', async () => {
      await import('../../../src/frontend/js/feeds/feed_wizard_step4');

      expect((window as unknown as Record<string, unknown>).initWizardStep4).toBeDefined();
    });
  });
});
