/**
 * Feed Wizard Step 2 - Select Article Text interactions.
 *
 * @license Unlicense
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @since   3.0.0 Extracted from PHP inline scripts
 */

import { extend_adv_xpath, lwt_feed_wizard } from './jq_feedwizard';

// Declare filter_Array as a global variable
declare global {
  // eslint-disable-next-line no-var
  var filter_Array: HTMLElement[];
  interface HTMLElement {
    get_adv_xpath?: () => void;
  }
}

// Initialize global filter_Array if not already defined
if (typeof window.filter_Array === 'undefined') {
  window.filter_Array = [];
}

/**
 * Helper to get element by ID
 */
function getEl(id: string): HTMLElement | null {
  return document.getElementById(id);
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
 * Object containing wizard step 2 test/selection interactions.
 */
export const lwt_wiz_select_test = {
  /**
   * Cancel advanced selection mode.
   */
  clickCancel: function (): boolean {
    const adv = getEl('adv');
    const lwtLast = getEl('lwt_last');
    const lwtHeader = getEl('lwt_header');
    if (adv) adv.style.display = 'none';
    if (lwtLast && lwtHeader) {
      lwtLast.style.marginTop = (lwtHeader.offsetHeight || 0) + 'px';
    }
    return false;
  },

  /**
   * Reset selection mode - clear all marked text and reset UI.
   */
  changeSelectMode: function (): boolean {
    document.querySelectorAll('.lwt_marked_text').forEach(el => {
      el.classList.remove('lwt_marked_text');
    });
    document.querySelectorAll('[class=""]').forEach(el => {
      el.removeAttribute('class');
    });
    const getButton = getEl('get_button') as HTMLButtonElement | null;
    if (getButton) getButton.disabled = true;
    const markAction = getEl('mark_action');
    if (markAction) {
      markAction.innerHTML = '';
      const option = document.createElement('option');
      option.value = '';
      option.textContent = '[Click On Text]';
      markAction.appendChild(option);
    }
    return false;
  },

  /**
   * Toggle image visibility based on select value.
   */
  changeHideImage: function (this: HTMLSelectElement): boolean {
    const lwtHeader = getEl('lwt_header');
    const headerElements = lwtHeader ? Array.from(lwtHeader.querySelectorAll('*')) : [];
    document.querySelectorAll<HTMLImageElement>('img').forEach(img => {
      if (!headerElements.includes(img)) {
        img.style.display = this.value === 'no' ? '' : 'none';
      }
    });
    return false;
  },

  /**
   * Navigate back to step 1 with current settings.
   */
  clickBack: function (): boolean {
    location.href = '/feeds/wizard?step=1&select_mode=' +
      encodeURIComponent(getInputValue('select[name="select_mode"]')) +
      '&hide_images=' +
      encodeURIComponent(getInputValue('select[name="hide_images"]'));
    return false;
  },

  /**
   * Toggle min/max state of the wizard container.
   */
  clickMinMax: function (): boolean {
    const lwtContainer = getEl('lwt_container');
    const lwtLast = getEl('lwt_last');
    const lwtHeader = getEl('lwt_header');
    const maximInput = document.querySelector<HTMLInputElement>('input[name="maxim"]');

    if (lwtContainer) {
      const isHidden = lwtContainer.style.display === 'none';
      lwtContainer.style.display = isHidden ? '' : 'none';
      if (maximInput) {
        maximInput.value = isHidden ? '1' : '0';
      }
    }
    if (lwtLast && lwtHeader) {
      lwtLast.style.marginTop = (lwtHeader.offsetHeight || 0) + 'px';
    }
    return false;
  },

  /**
   * Handle feed selection change - submit form with current HTML.
   */
  changeSelectedFeed: function (): void {
    const lwtSel = getEl('lwt_sel');
    const htmlInput = document.querySelector<HTMLInputElement>('input[name="html"]');
    if (htmlInput && lwtSel) {
      htmlInput.value = lwtSel.innerHTML || '';
    }
    (document as Document & { lwt_form1: HTMLFormElement }).lwt_form1.submit();
  },

  /**
   * Handle article section change - submit form with current HTML.
   */
  changeArticleSection: function (): void {
    const lwtSel = getEl('lwt_sel');
    const htmlInput = document.querySelector<HTMLInputElement>('input[name="html"]');
    if (htmlInput && lwtSel) {
      htmlInput.value = lwtSel.innerHTML || '';
    }
    (document as Document & { lwt_form1: HTMLFormElement }).lwt_form1.submit();
  },

  /**
   * Set maxim state - minimize the container.
   */
  setMaxim: function (): void {
    const lwtContainer = getEl('lwt_container');
    const lwtLast = getEl('lwt_last');
    const lwtHeader = getEl('lwt_header');
    const maximInput = document.querySelector<HTMLInputElement>('input[name="maxim"]');

    if (lwtContainer) {
      lwtContainer.style.display = 'none';
    }
    if (lwtLast && lwtHeader) {
      lwtLast.style.marginTop = (lwtHeader.offsetHeight || 0) + 'px';
    }
    if (maximInput) {
      maximInput.value = lwtContainer?.style.display === 'none' ? '0' : '1';
    }
  }
};

/**
 * Initialize wizard step 2 with configuration.
 *
 * @param hideImages Whether to hide images initially
 * @param isMinimized Whether to start minimized
 */
export function initWizardStep2(hideImages: boolean, isMinimized: boolean): void {
  // Extend HTMLElement prototype with get_adv_xpath
  HTMLElement.prototype.get_adv_xpath = extend_adv_xpath;

  // Initialize filter array
  window.filter_Array = [];

  // Prepare interactions from jq_feedwizard
  lwt_feed_wizard.prepareInteractions();

  // Hide images if configured
  if (hideImages) {
    const lwtHeader = getEl('lwt_header');
    const headerElements = lwtHeader ? Array.from(lwtHeader.querySelectorAll('*')) : [];
    document.querySelectorAll<HTMLImageElement>('img').forEach(img => {
      if (!headerElements.includes(img)) {
        img.style.display = 'none';
      }
    });
  }

  // Set minimized state if configured
  if (isMinimized) {
    lwt_wiz_select_test.setMaxim();
  }
}

/**
 * Initialize event delegation for wizard step 2 buttons.
 */
function initWizardStep2Events(): void {
  // Cancel button in advanced selection
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement;
    if (target.closest('[data-action="wizard-cancel"]')) {
      lwt_wiz_select_test.clickCancel();
    }
  });

  // Selection mode change
  document.addEventListener('change', (e) => {
    const target = e.target as HTMLElement;
    if (target.closest('[data-action="wizard-select-mode"]')) {
      lwt_wiz_select_test.changeSelectMode();
    }
  });

  // Hide images change
  document.addEventListener('change', (e) => {
    const target = e.target as HTMLSelectElement;
    if (target.matches('[data-action="wizard-hide-images"]')) {
      lwt_wiz_select_test.changeHideImage.call(target);
    }
  });

  // Back button
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement;
    if (target.closest('[data-action="wizard-back"]')) {
      lwt_wiz_select_test.clickBack();
    }
  });

  // Min/max button
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement;
    if (target.closest('[data-action="wizard-minmax"]')) {
      lwt_wiz_select_test.clickMinMax();
    }
  });

  // Selected feed change
  document.addEventListener('change', (e) => {
    const target = e.target as HTMLElement;
    if (target.closest('[data-action="wizard-selected-feed"]')) {
      lwt_wiz_select_test.changeSelectedFeed();
    }
  });

  // Article section change
  document.addEventListener('change', (e) => {
    const target = e.target as HTMLElement;
    if (target.closest('[data-action="wizard-article-section"]')) {
      lwt_wiz_select_test.changeArticleSection();
    }
  });

  // Settings open button
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement;
    if (target.closest('[data-action="wizard-settings-open"]')) {
      e.preventDefault();
      const settings = getEl('settings');
      if (settings) settings.style.display = '';
    }
  });

  // Settings close button
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement;
    if (target.closest('[data-action="wizard-settings-close"]')) {
      e.preventDefault();
      const settings = getEl('settings');
      if (settings) settings.style.display = 'none';
    }
  });

  // Cancel wizard button (delete wizard and redirect)
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement;
    if (target.closest('[data-action="wizard-delete-cancel"]')) {
      e.preventDefault();
      location.href = '/feeds/edit?del_wiz=1';
    }
  });
}

/**
 * Auto-initialize wizard step 2 from config element.
 * Detects the lwt_header element's data attributes and initializes.
 */
function autoInitWizardStep2(): void {
  const header = document.getElementById('lwt_header');
  if (!header || !header.dataset.hideImages) {
    return;
  }

  initWizardStep2(
    header.dataset.hideImages === 'true',
    header.dataset.isMinimized === 'true'
  );
}

// Auto-initialize event handlers and step 2
document.addEventListener('DOMContentLoaded', () => {
  initWizardStep2Events();
  autoInitWizardStep2();
});

// Expose to window for backward compatibility
(window as unknown as Record<string, unknown>).lwt_wiz_select_test = lwt_wiz_select_test;
(window as unknown as Record<string, unknown>).initWizardStep2 = initWizardStep2;
