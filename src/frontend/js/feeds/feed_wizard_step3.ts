/**
 * Feed Wizard Step 3 - Filter Text interactions.
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
  function xpathQuery(expression: string, context?: Node): HTMLElement[];
}

// Initialize global filter_Array if not already defined
if (typeof window.filter_Array === 'undefined') {
  window.filter_Array = [];
}

/**
 * Helper to get all descendants of an element including itself.
 */
function getAllDescendantsAndSelf(el: Element): Element[] {
  const result: Element[] = [el];
  const descendants = el.querySelectorAll('*');
  descendants.forEach(d => result.push(d));
  return result;
}

/**
 * Helper to get all following siblings and their descendants.
 */
function getNextAllWithDescendants(el: Element): Element[] {
  const result: Element[] = [];
  let sibling = el.nextElementSibling;
  while (sibling) {
    result.push(...getAllDescendantsAndSelf(sibling));
    sibling = sibling.nextElementSibling;
  }
  return result;
}

/**
 * Configuration for wizard step 3.
 */
export interface WizardStep3Config {
  articleSelector: string;
  hideImages: boolean;
  isMinimized: boolean;
}

/**
 * Object containing wizard step 3 filter interactions.
 */
export const lwt_wizard_filter = {
  /**
   * Update the filter array based on article selector.
   * Adds lwt_filtered_text class to elements outside the article section.
   *
   * @param articleSelector - The XPath selector for the article section
   */
  updateFilterArray: function (articleSelector: string): void {
    const articleSection = articleSelector.trim();
    if (articleSection === '') {
      alert('Article section is empty!');
      return;
    }

    const lwtHeader = document.getElementById('lwt_header');
    if (!lwtHeader) return;

    // Get all elements after lwt_header and their descendants
    const allAfterHeader = getNextAllWithDescendants(lwtHeader);

    // Get elements matching the article selector via xpathQuery
    const articleElements = window.xpathQuery(articleSection);
    const articleSet = new Set<Element>();
    articleElements.forEach(el => {
      getAllDescendantsAndSelf(el).forEach(d => articleSet.add(d));
    });

    // Get header elements to exclude
    const headerSet = new Set<Element>();
    getAllDescendantsAndSelf(lwtHeader).forEach(d => headerSet.add(d));

    // Filter elements not in article or header
    allAfterHeader.forEach(el => {
      if (!articleSet.has(el) && !headerSet.has(el)) {
        el.classList.add('lwt_filtered_text');
        window.filter_Array.push(el as HTMLElement);
      }
    });
  },

  /**
   * Hide all images except those in the header.
   */
  hideImages: function (): void {
    const lwtHeader = document.getElementById('lwt_header');
    const headerImages = lwtHeader ? new Set(lwtHeader.querySelectorAll('img')) : new Set();
    document.querySelectorAll<HTMLImageElement>('img').forEach(img => {
      if (!headerImages.has(img)) {
        img.style.display = 'none';
      }
    });
  },

  /**
   * Cancel advanced selection mode.
   */
  clickCancel: function (): boolean {
    const advEl = document.getElementById('adv');
    const lwtLastEl = document.getElementById('lwt_last');
    const lwtHeaderEl = document.getElementById('lwt_header');

    if (advEl) {
      advEl.style.display = 'none';
    }
    if (lwtLastEl && lwtHeaderEl) {
      lwtLastEl.style.marginTop = (lwtHeaderEl.offsetHeight || 0) + 'px';
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
    // Remove empty class attributes
    document.querySelectorAll('[class=""]').forEach(el => {
      el.removeAttribute('class');
    });
    const getButton = document.getElementById('get_button') as HTMLButtonElement | null;
    if (getButton) {
      getButton.disabled = true;
    }
    const markAction = document.getElementById('mark_action');
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
  changeHideImages: function (this: HTMLSelectElement): boolean {
    const lwtHeader = document.getElementById('lwt_header');
    const headerImages = lwtHeader ? new Set(lwtHeader.querySelectorAll('img')) : new Set();

    if (this.value === 'no') {
      document.querySelectorAll<HTMLImageElement>('img').forEach(img => {
        if (!headerImages.has(img)) {
          img.style.display = '';
        }
      });
    } else {
      document.querySelectorAll<HTMLImageElement>('img').forEach(img => {
        if (!headerImages.has(img)) {
          img.style.display = 'none';
        }
      });
    }
    return false;
  },

  /**
   * Handle feed selection change - submit form with current HTML.
   */
  changeSelectedFeed: function (): void {
    const lwtSelEl = document.getElementById('lwt_sel');
    const htmlInput = document.querySelector<HTMLInputElement>('input[name="html"]');
    if (htmlInput && lwtSelEl) {
      htmlInput.value = lwtSelEl.innerHTML || '';
    }
    (document as Document & { lwt_form1: HTMLFormElement }).lwt_form1.submit();
  },

  /**
   * Navigate back to step 2 with current settings.
   */
  clickBack: function (): boolean {
    const maximEl = document.getElementById('maxim') as HTMLInputElement | null;
    const lwtSelEl = document.getElementById('lwt_sel');
    const selectModeEl = document.querySelector<HTMLSelectElement>('select[name="select_mode"]');
    const hideImagesEl = document.querySelector<HTMLSelectElement>('select[name="hide_images"]');

    location.href = '/feeds/wizard?step=2&article_tags=1&maxim=' +
      (maximEl?.value || '') + '&filter_tags=' +
      encodeURIComponent(lwtSelEl?.innerHTML || '') + '&select_mode=' +
      encodeURIComponent(selectModeEl?.value || '') +
      '&hide_images=' +
      encodeURIComponent(hideImagesEl?.value || '');
    return false;
  },

  /**
   * Toggle min/max state of the wizard container.
   */
  clickMinMax: function (): boolean {
    const lwtContainer = document.getElementById('lwt_container');
    const maximInput = document.querySelector<HTMLInputElement>('input[name="maxim"]');
    const lwtLastEl = document.getElementById('lwt_last');
    const lwtHeaderEl = document.getElementById('lwt_header');

    if (lwtContainer) {
      const isHidden = lwtContainer.style.display === 'none';
      lwtContainer.style.display = isHidden ? '' : 'none';
      if (maximInput) {
        maximInput.value = isHidden ? '1' : '0';
      }
    }
    if (lwtLastEl && lwtHeaderEl) {
      lwtLastEl.style.marginTop = (lwtHeaderEl.offsetHeight || 0) + 'px';
    }
    return false;
  },

  /**
   * Set maxim state - minimize the container.
   */
  setMaxim: function (): void {
    const lwtContainer = document.getElementById('lwt_container');
    const maximInput = document.querySelector<HTMLInputElement>('input[name="maxim"]');
    const lwtLastEl = document.getElementById('lwt_last');
    const lwtHeaderEl = document.getElementById('lwt_header');

    if (lwtContainer) {
      lwtContainer.style.display = 'none';
    }
    if (lwtLastEl && lwtHeaderEl) {
      lwtLastEl.style.marginTop = (lwtHeaderEl.offsetHeight || 0) + 'px';
    }
    if (maximInput) {
      maximInput.value = lwtContainer?.style.display === 'none' ? '0' : '1';
    }
  }
};

/**
 * Initialize wizard step 3 with configuration.
 *
 * @param config - Configuration for the wizard step
 */
export function initWizardStep3(config: WizardStep3Config): void {
  // Store extend_adv_xpath for use (it's used by jq_feedwizard)
  (window as unknown as Record<string, unknown>).extend_adv_xpath = extend_adv_xpath;

  // Initialize filter array
  window.filter_Array = [];

  // Prepare interactions from jq_feedwizard
  lwt_feed_wizard.prepareInteractions();

  // Hide images if configured
  if (config.hideImages) {
    lwt_wizard_filter.hideImages();
  }

  // Update filter array with article selector
  lwt_wizard_filter.updateFilterArray(config.articleSelector);

  // Set minimized state if configured
  if (config.isMinimized) {
    lwt_wizard_filter.setMaxim();
  }
}

/**
 * Initialize event delegation for wizard step 3 buttons.
 */
function initWizardStep3Events(): void {
  document.addEventListener('click', function (e) {
    const target = e.target as HTMLElement;
    const actionEl = target.closest('[data-action]') as HTMLElement | null;
    if (!actionEl) return;

    const action = actionEl.dataset.action;

    // Cancel button in advanced selection
    if (action === 'wizard-step3-cancel') {
      lwt_wizard_filter.clickCancel();
    }

    // Back button
    if (action === 'wizard-step3-back') {
      lwt_wizard_filter.clickBack();
    }

    // Min/max button
    if (action === 'wizard-step3-minmax') {
      lwt_wizard_filter.clickMinMax();
    }

    // Settings dialog open
    if (action === 'wizard-settings-open') {
      const settingsEl = document.getElementById('settings');
      if (settingsEl) {
        settingsEl.style.display = '';
      }
    }

    // Settings dialog close
    if (action === 'wizard-settings-close') {
      const settingsEl = document.getElementById('settings');
      if (settingsEl) {
        settingsEl.style.display = 'none';
      }
    }
  });

  document.addEventListener('change', function (e) {
    const target = e.target as HTMLElement;
    const action = target.dataset.action;

    // Selection mode change
    if (action === 'wizard-step3-select-mode') {
      lwt_wizard_filter.changeSelectMode();
    }

    // Hide images change
    if (action === 'wizard-step3-hide-images' && target instanceof HTMLSelectElement) {
      lwt_wizard_filter.changeHideImages.call(target);
    }

    // Selected feed change
    if (action === 'wizard-step3-selected-feed') {
      lwt_wizard_filter.changeSelectedFeed();
    }
  });
}

/**
 * Auto-initialize wizard step 3 from config element.
 * Detects the wizard-step3-config element's data attributes and initializes.
 */
function autoInitWizardStep3(): void {
  const configEl = document.getElementById('wizard-step3-config');
  if (!configEl || typeof configEl.dataset.hideImages === 'undefined') {
    return;
  }

  initWizardStep3({
    articleSelector: configEl.dataset.articleSelector || '',
    hideImages: configEl.dataset.hideImages === 'true',
    isMinimized: configEl.dataset.isMinimized === 'true'
  });
}

// Auto-initialize event handlers and step 3
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function () {
    initWizardStep3Events();
    autoInitWizardStep3();
  });
} else {
  initWizardStep3Events();
  autoInitWizardStep3();
}

// Expose to window for backward compatibility
(window as unknown as Record<string, unknown>).lwt_wizard_filter = lwt_wizard_filter;
(window as unknown as Record<string, unknown>).initWizardStep3 = initWizardStep3;
