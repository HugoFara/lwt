/**
 * Form initialization module.
 *
 * Handles automatic setup of form behaviors based on data attributes
 * and configuration passed from PHP via JSON.
 *
 * @license unlicense
 * @since 3.0.0 Extracted from inline PHP scripts
 */

import $ from 'jquery';
import { lwtFormCheck } from './unloadformcheck';

/**
 * Configuration for text edit form.
 */
interface TextEditFormConfig {
  languageData: Record<string, string>;
}

/**
 * Clear the right frame on unload.
 * Used in word edit forms to clean up the dictionary frame.
 */
export function clearRightFrameOnUnload(): void {
  $(window).on('beforeunload', function () {
    setTimeout(function () {
      if (window.parent && window.parent.frames) {
        const ruFrame = window.parent.frames['ru' as unknown as number];
        if (ruFrame) {
          ruFrame.location.href = 'empty.html';
        }
      }
    }, 0);
  });
}

/**
 * Change the language attribute of text inputs based on selected language.
 * This helps browsers apply appropriate fonts and input methods.
 *
 * @param languageData - Mapping of language ID to language code
 */
export function changeTextboxesLanguage(languageData: Record<string, string>): void {
  const langSelect = document.getElementById('TxLgID') as HTMLSelectElement | null;
  if (!langSelect) return;

  const lid = langSelect.value;
  const langCode = languageData[lid] || '';

  $('#TxTitle').attr('lang', langCode);
  $('#TxText').attr('lang', langCode);
}

/**
 * Initialize the text edit form.
 * Sets up language switching and form change tracking.
 */
export function initTextEditForm(): void {
  const configEl = document.getElementById('text-edit-config');
  if (!configEl) return;

  let config: TextEditFormConfig;
  try {
    config = JSON.parse(configEl.textContent || '{}');
  } catch (e) {
    console.error('Failed to parse text-edit-config:', e);
    return;
  }

  // Set up language change handler using data-action attribute
  const langSelect = document.querySelector('[data-action="change-language"]');
  if (langSelect) {
    langSelect.addEventListener('change', () => {
      changeTextboxesLanguage(config.languageData);
    });

    // Apply initial language
    changeTextboxesLanguage(config.languageData);
  }

  // Set up form change tracking
  lwtFormCheck.askBeforeExit();
}

/**
 * Initialize word edit forms.
 * Sets up form change tracking and right frame cleanup.
 */
export function initWordEditForm(): void {
  lwtFormCheck.askBeforeExit();
  clearRightFrameOnUnload();
}

/**
 * Auto-initialize forms based on data attributes.
 * Called on DOMContentLoaded.
 */
export function autoInitializeForms(): void {
  // Auto-init text edit form if config is present
  if (document.getElementById('text-edit-config')) {
    initTextEditForm();
  }

  // Auto-init forms with data-lwt-form-check attribute
  const formsWithCheck = document.querySelectorAll('form[data-lwt-form-check="true"]');
  formsWithCheck.forEach((form) => {
    if (!form.hasAttribute('data-lwt-form-init')) {
      form.setAttribute('data-lwt-form-init', 'true');
      lwtFormCheck.askBeforeExit();
    }
  });

  // Auto-init forms with data-lwt-clear-frame attribute
  const formsWithClearFrame = document.querySelectorAll('form[data-lwt-clear-frame="true"]');
  if (formsWithClearFrame.length > 0) {
    clearRightFrameOnUnload();
  }

  // Auto-init forms with validate class that need exit confirmation (legacy support)
  // This handles forms that have class="validate" but don't have specific init
  const validateForms = document.querySelectorAll('form.validate');
  if (validateForms.length > 0) {
    // Check if askBeforeExit was already set up
    // We use a data attribute to track this
    validateForms.forEach((form) => {
      if (!form.hasAttribute('data-lwt-form-init')) {
        form.setAttribute('data-lwt-form-init', 'true');
      }
    });
  }
}

// Auto-initialize on DOM ready
document.addEventListener('DOMContentLoaded', autoInitializeForms);
