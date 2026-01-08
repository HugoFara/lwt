/**
 * Form initialization module.
 *
 * Handles automatic setup of form behaviors based on data attributes
 * and configuration passed from PHP via JSON.
 *
 * @license unlicense
 * @since 3.0.0 Extracted from inline PHP scripts
 */

import { lwtFormCheck } from './unloadformcheck';

/**
 * Configuration for text edit form.
 */
interface TextEditFormConfig {
  languageData: Record<string, string>;
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

  const titleEl = document.getElementById('TxTitle');
  const textEl = document.getElementById('TxText');
  if (titleEl) titleEl.setAttribute('lang', langCode);
  if (textEl) textEl.setAttribute('lang', langCode);
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
 * Sets up form change tracking.
 */
export function initWordEditForm(): void {
  lwtFormCheck.askBeforeExit();
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
      // If form is marked as dirty, set dirty state
      if (form.hasAttribute('data-lwt-dirty')) {
        lwtFormCheck.makeDirty();
      }
    }
  });

  // Auto-init forms with validate class that need exit confirmation
  // This handles forms that have class="validate"
  const validateForms = document.querySelectorAll('form.validate');
  if (validateForms.length > 0) {
    let needsFormCheck = false;
    validateForms.forEach((form) => {
      if (!form.hasAttribute('data-lwt-form-init')) {
        form.setAttribute('data-lwt-form-init', 'true');
        needsFormCheck = true;
      }
    });
    if (needsFormCheck) {
      lwtFormCheck.askBeforeExit();
    }
  }

  // Auto-init language selector with data-language-data attribute
  const langConfigEl = document.getElementById('language-data-config');
  if (langConfigEl) {
    try {
      const languageData = JSON.parse(langConfigEl.textContent || '{}') as Record<string, string>;
      const langSelect = document.getElementById('TxLgID');
      if (langSelect) {
        // Apply initial language
        changeTextboxesLanguage(languageData);
        // Set up change handler
        langSelect.addEventListener('change', () => {
          changeTextboxesLanguage(languageData);
        });
      }
    } catch (e) {
      console.error('Failed to parse language-data-config:', e);
    }
  }
}

// Auto-initialize on DOM ready
document.addEventListener('DOMContentLoaded', autoInitializeForms);
