/**
 * Language Wizard Modal Component - Alpine.js component for language wizard modal.
 *
 * Provides a modal dialog for quickly setting up new languages by selecting
 * native (L1) and study (L2) languages, then auto-populating form fields.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import { initIcons } from '@shared/icons/lucide_icons';
import { do_ajax_save_setting } from '@shared/utils/ajax_utilities';
import {
  getLanguageStore,
  type LanguageStoreState
} from '../stores/language_store';
import {
  getLanguageFormStore,
  type LanguageFormStoreState
} from '../stores/language_form_store';
// LanguageDefinition available if needed for future wizard features

/**
 * Wizard modal component data interface.
 */
export interface WizardModalComponentData {
  // Store references
  store: LanguageStoreState;
  formStore: LanguageFormStoreState;

  // Wizard state
  l1: string;
  l2: string;
  error: string | null;

  // Computed
  readonly sortedLanguages: string[];
  readonly isValid: boolean;

  // Methods
  init(): void;
  open(): void;
  close(): void;
  handleL1Change(): void;
  apply(): void;
}

/**
 * Refresh Lucide icons after DOM changes.
 */
function refreshIcons(): void {
  setTimeout(() => {
    initIcons();
  }, 0);
}

/**
 * Create the wizard modal component data.
 */
export function wizardModalData(): WizardModalComponentData {
  return {
    store: getLanguageStore(),
    formStore: getLanguageFormStore(),

    l1: '',
    l2: '',
    error: null,

    /**
     * Get sorted list of available language names.
     */
    get sortedLanguages(): string[] {
      const definitions = this.store.definitions;
      if (!definitions) return [];
      return Object.keys(definitions).sort((a, b) =>
        a.localeCompare(b, undefined, { sensitivity: 'base' })
      );
    },

    /**
     * Check if the wizard form is valid.
     */
    get isValid(): boolean {
      return this.l1 !== '' && this.l2 !== '' && this.l1 !== this.l2;
    },

    /**
     * Initialize the component.
     */
    init(): void {
      // Try to restore saved L1 preference from settings
      // The store loads definitions which we need for the dropdown
      refreshIcons();
    },

    /**
     * Open the wizard modal.
     */
    open(): void {
      this.error = null;
      this.l2 = '';
      this.store.openWizardModal();
      refreshIcons();
    },

    /**
     * Close the wizard modal.
     */
    close(): void {
      this.store.closeWizardModal();
    },

    /**
     * Handle L1 (native language) change - save preference.
     */
    handleL1Change(): void {
      if (this.l1) {
        do_ajax_save_setting('currentnativelanguage', this.l1);
      }
    },

    /**
     * Apply the wizard selection - create language with preset values.
     */
    apply(): void {
      // Validate
      if (this.l1 === '') {
        this.error = 'Please choose your native language (L1)!';
        return;
      }
      if (this.l2 === '') {
        this.error = 'Please choose the language you want to study (L2)!';
        return;
      }
      if (this.l1 === this.l2) {
        this.error = 'L1 and L2 languages must be different!';
        return;
      }

      this.error = null;

      // Store wizard data in session storage for the form page to pick up
      sessionStorage.setItem(
        'lwt_language_wizard',
        JSON.stringify({
          l1: this.l1,
          l2: this.l2,
          definitions: this.store.definitions
        })
      );

      // Close the modal and navigate to the form page
      this.close();

      // Navigate to the new language form with wizard flag
      window.location.href = '/languages?new=1&wizard=1';
    }
  };
}

/**
 * Initialize the wizard modal Alpine.js component.
 */
export function initWizardModalComponent(): void {
  Alpine.data('wizardModal', wizardModalData);
}

// Register the component immediately
initWizardModalComponent();

// Expose for global access
declare global {
  interface Window {
    wizardModalData: typeof wizardModalData;
  }
}

window.wizardModalData = wizardModalData;
