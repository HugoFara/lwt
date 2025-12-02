/**
 * Feed Wizard Step 4 - Edit Options interactions.
 *
 * @license Unlicense
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @since   3.0.0 Extracted from PHP inline scripts
 */

import { iconHtml } from '../ui/icons';

/**
 * Configuration for wizard step 4.
 */
export interface WizardStep4Config {
  editFeedId: number | null;
}

/**
 * Helper to get input value
 */
function getInputValue(selector: string): string {
  const el = document.querySelector<HTMLInputElement | HTMLSelectElement>(selector);
  return el?.value || '';
}

/**
 * Helper to set input value
 */
function setInputValue(selector: string, value: string): void {
  const el = document.querySelector<HTMLInputElement>(selector);
  if (el) el.value = value;
}

/**
 * Object containing wizard step 4 edit options interactions.
 */
export const lwt_wizard_step4 = {
  /**
   * Build the options string from form checkboxes.
   *
   * @returns The options string for NfOptions field
   */
  buildOptionsString: function (): string {
    const editTextChecked = document.querySelector<HTMLInputElement>('[name="edit_text"]:checked');
    let str = editTextChecked ? 'edit_text=1,' : '';

    document.querySelectorAll<HTMLInputElement>('[name^="c_"]').forEach((checkbox) => {
      if (checkbox.checked) {
        const parent = checkbox.parentElement;
        if (!parent) return;

        const textInput = parent.querySelector<HTMLInputElement>('input[type="text"]');
        const inputName = textInput?.name || '';
        const inputValue = textInput?.value || '';

        str += inputName + '=' + inputValue;

        if (checkbox.name === 'c_autoupdate') {
          const select = parent.querySelector<HTMLSelectElement>('select');
          str += (select?.value || '') + ',';
        } else {
          str += ',';
        }
      }
    });

    const articleSource = getInputValue('input[name="article_source"]');
    if (articleSource !== '') {
      str += 'article_source=' + articleSource;
    }

    return str;
  },

  /**
   * Handle checkbox change - toggle associated inputs.
   */
  handleCheckboxChange: function (this: HTMLInputElement): void {
    const parent = this.parentElement;
    if (!parent) return;

    const textInput = parent.querySelector<HTMLInputElement>('input[type="text"]');
    const select = parent.querySelector<HTMLSelectElement>('select');

    if (this.checked) {
      if (textInput) {
        textInput.disabled = false;
        textInput.classList.add('notempty');
      }
      if (select) {
        select.disabled = false;
      }
    } else {
      if (textInput) {
        textInput.disabled = true;
        textInput.classList.remove('notempty');
      }
      if (select) {
        select.disabled = true;
      }
    }
  },

  /**
   * Handle submit button click - build options string.
   */
  handleSubmit: function (): void {
    const str = lwt_wizard_step4.buildOptionsString();
    setInputValue('input[name="NfOptions"]', str);
  },

  /**
   * Handle back button click - navigate to step 3 with current options.
   */
  clickBack: function (): boolean {
    const editTextChecked = document.querySelector<HTMLInputElement>('[name="edit_text"]:checked');
    let str = editTextChecked ? 'edit_text=1,' : '';

    document.querySelectorAll<HTMLInputElement>('[name^="c_"]').forEach((checkbox) => {
      if (checkbox.checked) {
        const parent = checkbox.parentElement;
        if (!parent) return;

        const textInput = parent.querySelector<HTMLInputElement>('input[type="text"]');
        const inputName = textInput?.name || '';
        const inputValue = textInput?.value || '';

        str += inputName + '=' + inputValue;

        if (checkbox.name === 'c_autoupdate') {
          const select = parent.querySelector<HTMLSelectElement>('select');
          str += (select?.value || '') + ',';
        } else {
          str += ',';
        }
      }
    });

    location.href = '/feeds/wizard?step=3&NfOptions=' + str +
      '&NfLgID=' + getInputValue('select[name="NfLgID"]') +
      '&NfName=' + getInputValue('input[name="NfName"]');

    return false;
  },

  /**
   * Set up the page for edit mode if editing existing feed.
   *
   * @param editFeedId - The ID of the feed being edited, or null for new feed
   */
  setupEditMode: function (editFeedId: number | null): void {
    if (editFeedId) {
      const saveFeedInput = document.querySelector<HTMLInputElement>('input[name="save_feed"]');
      if (saveFeedInput) {
        saveFeedInput.name = 'update_feed';
      }
      const submitInput = document.querySelector<HTMLInputElement>('input[type="submit"]');
      if (submitInput) {
        submitInput.value = 'Update';
      }
    }
  },

  /**
   * Set up the page header.
   */
  setupHeader: function (): void {
    const h1Elements = document.querySelectorAll('h1');
    const lastH1 = h1Elements[h1Elements.length - 1];
    if (lastH1) {
      lastH1.innerHTML =
        'Feed Wizard | Step 4 - Edit Options ' +
        '<a href="/docs/guide/how-to-use" target="_blank">' +
        iconHtml('question-frame', { alt: 'Help', title: 'Help' }) + '</a>';
      lastH1.style.textAlign = 'center';
    }
  }
};

/**
 * Initialize wizard step 4 with configuration.
 *
 * @param config - Configuration for the wizard step
 */
export function initWizardStep4(config: WizardStep4Config): void {
  // Set up edit mode if editing existing feed
  lwt_wizard_step4.setupEditMode(config.editFeedId);

  // Set up the page header
  lwt_wizard_step4.setupHeader();
}

/**
 * Initialize event delegation for wizard step 4.
 */
function initWizardStep4Events(): void {
  // Handle checkbox changes for option fields
  document.addEventListener('change', (e) => {
    const target = e.target as HTMLInputElement;
    if (target.matches('[name^="c_"]')) {
      lwt_wizard_step4.handleCheckboxChange.call(target);
    }
  });

  // Handle submit button click
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement;
    if (target.closest('[data-action="wizard-step4-submit"]')) {
      lwt_wizard_step4.handleSubmit();
    }
  });

  // Handle back button click
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement;
    if (target.closest('[data-action="wizard-step4-back"]')) {
      lwt_wizard_step4.clickBack();
    }
  });

  // Handle cancel button click
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement;
    if (target.closest('[data-action="wizard-step4-cancel"]')) {
      location.href = '/feeds/edit?del_wiz=1';
    }
  });
}

/**
 * Auto-initialize wizard step 4 from config element.
 * Detects the wizard-step4-config element's data attributes and initializes.
 */
function autoInitWizardStep4(): void {
  const configEl = document.getElementById('wizard-step4-config');
  if (!configEl) {
    return;
  }

  const editFeedId = configEl.dataset.editFeedId;
  initWizardStep4({
    editFeedId: editFeedId ? parseInt(editFeedId, 10) : null
  });
}

// Auto-initialize event handlers and step 4
document.addEventListener('DOMContentLoaded', () => {
  initWizardStep4Events();
  autoInitWizardStep4();
});

// Expose to window for backward compatibility
(window as unknown as Record<string, unknown>).lwt_wizard_step4 = lwt_wizard_step4;
(window as unknown as Record<string, unknown>).initWizardStep4 = initWizardStep4;
