/**
 * Bulk Translate - Alpine.js component for bulk translation.
 *
 * Handles dictionary lookups, form interactions, and Google Translate
 * integration for bulk translating unknown words.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 * @since   3.1.0 Migrated to Alpine.js component
 */

import Alpine from 'alpinejs';
import { createTheDictUrl, openDictionaryPopup } from '@modules/vocabulary/services/dictionary';
import { selectToggle } from '@shared/forms/bulk_actions';
import { setDictionaryLinks } from '@modules/language/stores/language_config';

declare global {
  interface Window {
    WBLINK?: string;
    googleTranslateElementInit?: (() => void) | ((sourceLanguage: string, targetLanguage: string) => void);
  }
  // Google Translate API types
  const google: {
    translate: {
      TranslateElement: {
        new(config: {
          pageLanguage: string;
          layout: unknown;
          includedLanguages: string;
          autoDisplay: boolean;
        }, elementId: string): unknown;
        InlineLayout: {
          SIMPLE: unknown;
        };
      };
    };
  };
}

/**
 * Configuration for bulk translate component.
 */
export interface BulkTranslateConfig {
  dictionaries: {
    dict1: string;
    dict2: string;
    translate: string;
  };
  sourceLanguage: string;
  targetLanguage: string;
}


/**
 * Bulk translate Alpine component data interface.
 */
export interface BulkTranslateData {
  // Config
  dictConfig: {
    dict1: string;
    dict2: string;
    translator: string;
  };
  sourceLanguage: string;
  targetLanguage: string;

  // State
  isGoogleTranslateReady: boolean;
  submitButtonText: string;
  hasOffset: boolean;

  // Methods
  init(): void;
  setupFormSubmission(): void;
  setupInteractions(): void;
  markAll(): void;
  markNone(): void;
  handleTermToggle(termId: number, checked: boolean): void;
  handleTermToggles(action: string): void;
  clickDictionary(element: HTMLElement): void;
  deleteTranslation(termId: number): void;
  setToLowercase(termId: number): void;
  updateSubmitButton(): void;
  setupGoogleTranslateCallback(): void;
}

/**
 * Alpine.js component for bulk translate functionality.
 */
export function bulkTranslateApp(config: BulkTranslateConfig = {
  dictionaries: { dict1: '', dict2: '', translate: '' },
  sourceLanguage: 'en',
  targetLanguage: 'en'
}): BulkTranslateData {
  return {
    // Config
    dictConfig: {
      dict1: config.dictionaries.dict1,
      dict2: config.dictionaries.dict2,
      translator: config.dictionaries.translate
    },
    sourceLanguage: config.sourceLanguage,
    targetLanguage: config.targetLanguage,

    // State
    isGoogleTranslateReady: false,
    submitButtonText: 'Save',
    hasOffset: false,

    /**
     * Initialize the component.
     */
    init(): void {
      // Read config from JSON script tag if available
      const configEl = document.getElementById('bulk-translate-config');
      if (configEl) {
        try {
          const jsonConfig: BulkTranslateConfig = JSON.parse(configEl.textContent || '{}');
          this.dictConfig = {
            dict1: jsonConfig.dictionaries?.dict1 ?? '',
            dict2: jsonConfig.dictionaries?.dict2 ?? '',
            translator: jsonConfig.dictionaries?.translate ?? ''
          };
          this.sourceLanguage = jsonConfig.sourceLanguage ?? 'en';
          this.targetLanguage = jsonConfig.targetLanguage ?? 'en';
        } catch {
          // Invalid JSON, use defaults
        }
      }

      // Check if there's an offset input (for pagination)
      this.hasOffset = document.querySelector('input[name="offset"]') !== null;

      // Set dictionary links in language config for legacy support
      setDictionaryLinks(this.dictConfig);

      // Mark headers as not translatable
      document.querySelectorAll('h3, h4, title').forEach(el => {
        el.classList.add('notranslate');
      });

      // Setup Google Translate callback
      this.setupGoogleTranslateCallback();

      // Set up form submission handler
      this.setupFormSubmission();

      // Set up interactions when page is fully loaded
      window.addEventListener('load', () => this.setupInteractions());
    },


    /**
     * Setup form submission handler.
     */
    setupFormSubmission(): void {
      const form1 = document.querySelector<HTMLFormElement>('[name="form1"]');
      if (form1) {
        form1.addEventListener('submit', () => {
          const currentTranslation = document.querySelector<HTMLElement>('[name="WoTranslation"]');
          if (currentTranslation) {
            currentTranslation.setAttribute('name', currentTranslation.getAttribute('data_name') ?? '');
          }
          return true;
        });
      }
    },

    /**
     * Setup interactions after Google Translate populates.
     */
    setupInteractions(): void {
      // Wait for Google Translate to populate the .trans elements with <font> tags
      const displayTranslations = setInterval(() => {
        const transElements = document.querySelectorAll('.trans');
        const transFontElements = document.querySelectorAll('.trans>font');

        if (transFontElements.length === transElements.length) {
          // Convert translated text to input fields
          transElements.forEach(trans => {
            const txt = trans.textContent || '';
            const cnt = (trans.id || '').replace('Trans_', '');

            trans.classList.add('notranslate');
            trans.innerHTML =
              `<input type="text" name="term[${cnt}][trans]" value="${txt}" maxlength="100" class="respinput">` +
              '<div class="del_trans"></div>';
          });

          // Add dictionary links after each term
          document.querySelectorAll<HTMLElement>('.term').forEach(term => {
            const parent = term.parentElement;
            if (parent) {
              parent.style.position = 'relative';
            }

            const dictLinksHtml =
              '<div class="dict">' +
              (this.dictConfig.dict1 ? '<span class="dict1">D1</span>' : '') +
              (this.dictConfig.dict2 ? '<span class="dict2">D2</span>' : '') +
              (this.dictConfig.translator ? '<span class="dict3">Tr</span>' : '') +
              '</div>';

            term.insertAdjacentHTML('afterend', dictLinksHtml);
          });

          // Clean up Google Translate elements
          document.querySelectorAll('iframe, #google_translate_element').forEach(el => el.remove());

          // Enable all checkboxes and inputs
          selectToggle(true, 'form1');
          document.querySelectorAll<HTMLInputElement | HTMLSelectElement>('[name^=term]').forEach(el => {
            el.disabled = false;
          });

          this.isGoogleTranslateReady = true;
          clearInterval(displayTranslations);
        }
      }, 300);
    },

    /**
     * Mark all terms for saving.
     */
    markAll(): void {
      this.submitButtonText = 'Save';
      const submitBtn = document.querySelector<HTMLInputElement>('input[type="submit"]');
      if (submitBtn) {
        submitBtn.value = 'Save';
      }
      selectToggle(true, 'form1');
      document.querySelectorAll<HTMLInputElement | HTMLSelectElement>('[name^=term]').forEach(el => {
        el.disabled = false;
      });
    },

    /**
     * Unmark all terms.
     */
    markNone(): void {
      this.submitButtonText = this.hasOffset ? 'Next' : 'End';
      const submitBtn = document.querySelector<HTMLInputElement>('input[type="submit"]');
      if (submitBtn) {
        submitBtn.value = this.submitButtonText;
      }
      selectToggle(false, 'form1');
      document.querySelectorAll<HTMLInputElement | HTMLSelectElement>('[name^=term]').forEach(el => {
        el.disabled = true;
      });
    },

    /**
     * Handle individual term checkbox toggle.
     */
    handleTermToggle(termId: number, checked: boolean): void {
      // Select all inputs related to this term
      const relatedInputs = document.querySelectorAll<HTMLInputElement | HTMLSelectElement>(
        `[name="term[${termId}][text]"], [name="term[${termId}][lg]"], [name="term[${termId}][status]"]`
      );
      relatedInputs.forEach(input => {
        input.disabled = !checked;
      });

      const transInput = document.querySelector<HTMLInputElement>(`#Trans_${termId} input`);
      if (transInput) {
        transInput.disabled = !checked;
      }

      this.updateSubmitButton();
    },

    /**
     * Handle bulk term toggles (status changes, lowercase, delete translation).
     */
    handleTermToggles(action: string): void {
      if (action === '6') {
        // Set to lowercase
        document.querySelectorAll<HTMLInputElement>('.markcheck:checked').forEach(checkbox => {
          const checkboxValue = checkbox.value;
          const termSpan = document.querySelector<HTMLElement>(`#Term_${checkboxValue} .term`);
          if (termSpan) {
            const lowerText = (termSpan.textContent || '').toLowerCase();
            termSpan.textContent = lowerText;
            const textInput = document.querySelector<HTMLInputElement>(`#Text_${checkboxValue}`);
            if (textInput) {
              textInput.value = lowerText;
            }
          }
        });
        return;
      }

      if (action === '7') {
        // Delete translation (set to *)
        document.querySelectorAll<HTMLInputElement>('.markcheck:checked').forEach(checkbox => {
          const checkboxValue = checkbox.value;
          const transInput = document.querySelector<HTMLInputElement>(`#Trans_${checkboxValue} input`);
          if (transInput) {
            transInput.value = '*';
          }
        });
        return;
      }

      // Set status for all checked terms
      document.querySelectorAll<HTMLInputElement>('.markcheck:checked').forEach(checkbox => {
        const checkboxValue = checkbox.value;
        const statSelect = document.querySelector<HTMLSelectElement>(`#Stat_${checkboxValue}`);
        if (statSelect) {
          statSelect.value = action;
        }
      });
    },

    /**
     * Handle click on a dictionary link.
     */
    clickDictionary(element: HTMLElement): void {
      let dictLink: string;

      if (element.classList.contains('dict1')) {
        dictLink = this.dictConfig.dict1;
      } else if (element.classList.contains('dict2')) {
        dictLink = this.dictConfig.dict2;
      } else if (element.classList.contains('dict3')) {
        dictLink = this.dictConfig.translator;
      } else {
        return;
      }

      window.WBLINK = dictLink;

      // Strip leading * (popup marker) if present
      if (dictLink.startsWith('*')) {
        dictLink = dictLink.substring(1);
      }

      const parent = element.parentElement;
      const prevSibling = parent?.previousElementSibling;
      const termText = prevSibling?.textContent || '';
      const dictUrl = createTheDictUrl(dictLink, termText);

      openDictionaryPopup(dictUrl);

      // Swap WoTranslation name attributes to track current input
      const currentTranslation = document.querySelector<HTMLElement>('[name="WoTranslation"]');
      if (currentTranslation) {
        currentTranslation.setAttribute('name', currentTranslation.getAttribute('data_name') ?? '');
      }

      const grandparent = parent?.parentElement;
      const nextRow = grandparent?.nextElementSibling;
      const el = nextRow?.firstElementChild as HTMLElement | null;
      if (el) {
        el.setAttribute('data_name', el.getAttribute('name') ?? '');
        el.setAttribute('name', 'WoTranslation');
      }
    },

    /**
     * Delete translation for a term.
     */
    deleteTranslation(termId: number): void {
      const transInput = document.querySelector<HTMLInputElement>(`#Trans_${termId} input`);
      if (transInput) {
        transInput.value = '';
        transInput.focus();
      }
    },

    /**
     * Set term to lowercase.
     */
    setToLowercase(termId: number): void {
      const termSpan = document.querySelector<HTMLElement>(`#Term_${termId} .term`);
      if (termSpan) {
        const lowerText = (termSpan.textContent || '').toLowerCase();
        termSpan.textContent = lowerText;
        const textInput = document.querySelector<HTMLInputElement>(`#Text_${termId}`);
        if (textInput) {
          textInput.value = lowerText;
        }
      }
    },

    /**
     * Update submit button text based on checkbox state.
     */
    updateSubmitButton(): void {
      const checkedCheckboxes = document.querySelectorAll('input[type="checkbox"]:checked');
      if (checkedCheckboxes.length) {
        this.submitButtonText = 'Save';
      } else {
        this.submitButtonText = this.hasOffset ? 'Next' : 'End';
      }
      const submitBtn = document.querySelector<HTMLInputElement>('input[type="submit"]');
      if (submitBtn) {
        submitBtn.value = this.submitButtonText;
      }
    },

    /**
     * Setup Google Translate callback.
     */
    setupGoogleTranslateCallback(): void {
      window.googleTranslateElementInit = () => {
        if (typeof google !== 'undefined' && google.translate) {
          new google.translate.TranslateElement({
            pageLanguage: this.sourceLanguage,
            layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
            includedLanguages: this.targetLanguage,
            autoDisplay: false
          }, 'google_translate_element');
        }
      };
    }
  };
}

// Register Alpine component
if (typeof Alpine !== 'undefined') {
  Alpine.data('bulkTranslateApp', bulkTranslateApp);
}
