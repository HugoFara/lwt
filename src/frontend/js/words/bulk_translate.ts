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
import { createTheDictUrl, owin } from '../terms/dictionary';
import { selectToggle } from '../forms/bulk_actions';
import { getDictionaryLinks, setDictionaryLinks } from '../core/language_config';

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
 * Term data interface.
 */
interface TermData {
  id: number;
  text: string;
  checked: boolean;
  translation: string;
  status: number;
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
  clearRightFrame(): void;
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

      // Clear right frame on load
      this.clearRightFrame();

      // Set up form submission handler
      this.setupFormSubmission();

      // Set up interactions when page is fully loaded
      window.addEventListener('load', () => this.setupInteractions());
    },

    /**
     * Clear the right frame.
     */
    clearRightFrame(): void {
      try {
        window.parent.frames['ru' as unknown as number].location.href = 'empty.html';
      } catch {
        // Frame access denied
      }
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
          this.clearRightFrame();
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

      let isPopup = false;
      if (dictLink.startsWith('*')) {
        isPopup = true;
        dictLink = dictLink.substring(1);
      }

      try {
        const finalUrl = new URL(dictLink);
        isPopup = isPopup || finalUrl.searchParams.has('lwt_popup');
      } catch (err) {
        if (!(err instanceof TypeError)) {
          throw err;
        }
      }

      const parent = element.parentElement;
      const prevSibling = parent?.previousElementSibling;
      const termText = prevSibling?.textContent || '';
      const dictUrl = createTheDictUrl(dictLink, termText);

      if (isPopup) {
        owin(dictUrl);
      } else {
        try {
          window.parent.frames['ru' as unknown as number].location.href = dictUrl;
        } catch {
          // Frame access denied, try opening in new window
          owin(dictUrl);
        }
      }

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
      const self = this;
      window.googleTranslateElementInit = function() {
        if (typeof google !== 'undefined' && google.translate) {
          new google.translate.TranslateElement({
            pageLanguage: self.sourceLanguage,
            layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
            includedLanguages: self.targetLanguage,
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

// ============================================================================
// Legacy API - For backward compatibility
// ============================================================================

/**
 * Handle click on a dictionary link in bulk translate form.
 * @deprecated Since 3.1.0, use bulkTranslateApp() Alpine component
 */
export function clickDictionary(element: HTMLElement): void {
  const dictLinks = getDictionaryLinks();
  let dictLink: string;

  if (element.classList.contains('dict1')) {
    dictLink = dictLinks.dict1;
  } else if (element.classList.contains('dict2')) {
    dictLink = dictLinks.dict2;
  } else if (element.classList.contains('dict3')) {
    dictLink = dictLinks.translator;
  } else {
    return;
  }

  window.WBLINK = dictLink;

  let isPopup = false;
  if (dictLink.startsWith('*')) {
    isPopup = true;
    dictLink = dictLink.substring(1);
  }

  try {
    const finalUrl = new URL(dictLink);
    isPopup = isPopup || finalUrl.searchParams.has('lwt_popup');
  } catch (err) {
    if (!(err instanceof TypeError)) {
      throw err;
    }
  }

  const parent = element.parentElement;
  const prevSibling = parent?.previousElementSibling;
  const termText = prevSibling?.textContent || '';
  const dictUrl = createTheDictUrl(dictLink, termText);

  if (isPopup) {
    owin(dictUrl);
  } else {
    try {
      window.parent.frames['ru' as unknown as number].location.href = dictUrl;
    } catch {
      // Frame access denied, try opening in new window
      owin(dictUrl);
    }
  }

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
}

/**
 * Set up form interactions after page load.
 * @deprecated Since 3.1.0, use bulkTranslateApp() Alpine component
 */
export function bulkInteractions(): void {
  // Form submission - restore original input names and clear frame
  const form1 = document.querySelector<HTMLFormElement>('[name="form1"]');
  if (form1) {
    form1.addEventListener('submit', () => {
      const currentTranslation = document.querySelector<HTMLElement>('[name="WoTranslation"]');
      if (currentTranslation) {
        currentTranslation.setAttribute('name', currentTranslation.getAttribute('data_name') ?? '');
      }

      try {
        window.parent.frames['ru' as unknown as number].location.href = 'empty.html';
      } catch {
        // Frame access denied, continue with submission
      }

      return true;
    });
  }

  // Dictionary and delete handlers using event delegation
  document.querySelectorAll('td').forEach(td => {
    td.addEventListener('click', (e) => {
      const target = e.target as HTMLElement;

      // Dictionary click
      const dictSpan = target.closest<HTMLElement>('span.dict1, span.dict2, span.dict3');
      if (dictSpan) {
        clickDictionary(dictSpan);
        return;
      }

      // Delete translation click
      if (target.classList.contains('del_trans')) {
        const prevInput = target.previousElementSibling as HTMLInputElement | null;
        if (prevInput) {
          prevInput.value = '';
          prevInput.focus();
        }
      }
    });
  });

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
      const dictConfig = getDictionaryLinks();
      document.querySelectorAll<HTMLElement>('.term').forEach(term => {
        const parent = term.parentElement;
        if (parent) {
          parent.style.position = 'relative';
        }

        const dictLinksHtml =
          '<div class="dict">' +
          (dictConfig.dict1 ? '<span class="dict1">D1</span>' : '') +
          (dictConfig.dict2 ? '<span class="dict2">D2</span>' : '') +
          (dictConfig.translator ? '<span class="dict3">Tr</span>' : '') +
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

      clearInterval(displayTranslations);
    }
  }, 300);
}

/**
 * Set up checkbox change handlers.
 * @deprecated Since 3.1.0, use bulkTranslateApp() Alpine component
 */
export function bulkCheckbox(): void {
  // Clear right frame on load
  try {
    window.parent.frames['ru' as unknown as number].location.href = 'empty.html';
  } catch {
    // Frame access denied
  }

  document.querySelectorAll<HTMLInputElement>('input[type="checkbox"]').forEach(checkbox => {
    checkbox.addEventListener('change', () => {
      const v = parseInt(checkbox.value, 10);

      // Select all inputs related to this term
      const relatedInputs = document.querySelectorAll<HTMLInputElement | HTMLSelectElement>(
        `[name="term[${v}][text]"], [name="term[${v}][lg]"], [name="term[${v}][status]"]`
      );
      relatedInputs.forEach(input => {
        input.disabled = !checkbox.checked;
      });

      const transInput = document.querySelector<HTMLInputElement>(`#Trans_${v} input`);
      if (transInput) {
        transInput.disabled = !checkbox.checked;
      }

      // Update submit button text based on state
      const checkedCheckboxes = document.querySelectorAll('input[type="checkbox"]:checked');
      if (checkedCheckboxes.length) {
        let operationOption: string;
        if (checkbox.checked) {
          operationOption = 'Save';
        } else if (document.querySelector('input[name="offset"]')) {
          operationOption = 'Next';
        } else {
          operationOption = 'End';
        }
        const submitBtn = document.querySelector<HTMLInputElement>('input[type="submit"]');
        if (submitBtn) {
          submitBtn.value = operationOption;
        }
      }
    });
  });
}

/**
 * Initialize Google Translate widget.
 * @deprecated Since 3.1.0, use bulkTranslateApp() Alpine component
 */
export function googleTranslateElementInit(sourceLanguage: string, targetLanguage: string): void {
  if (typeof google !== 'undefined' && google.translate) {
    new google.translate.TranslateElement({
      pageLanguage: sourceLanguage,
      layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
      includedLanguages: targetLanguage,
      autoDisplay: false
    }, 'google_translate_element');
  }
}

/**
 * Mark all terms for saving.
 * @deprecated Since 3.1.0, use bulkTranslateApp() Alpine component
 */
export function markAll(): void {
  const submitBtn = document.querySelector<HTMLInputElement>('input[type="submit"]');
  if (submitBtn) {
    submitBtn.value = 'Save';
  }
  selectToggle(true, 'form1');
  document.querySelectorAll<HTMLInputElement | HTMLSelectElement>('[name^=term]').forEach(el => {
    el.disabled = false;
  });
}

/**
 * Unmark all terms.
 * @deprecated Since 3.1.0, use bulkTranslateApp() Alpine component
 */
export function markNone(): void {
  const submitValue = document.querySelector('input[name^=offset]') ? 'Next' : 'End';
  const submitBtn = document.querySelector<HTMLInputElement>('input[type="submit"]');
  if (submitBtn) {
    submitBtn.value = submitValue;
  }
  selectToggle(false, 'form1');
  document.querySelectorAll<HTMLInputElement | HTMLSelectElement>('[name^=term]').forEach(el => {
    el.disabled = true;
  });
}

/**
 * Handle changes to the bulk action select.
 * @deprecated Since 3.1.0, use bulkTranslateApp() Alpine component
 */
export function changeTermToggles(selectEl: HTMLSelectElement): boolean {
  const v = selectEl.value;

  if (v === '6') {
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
    selectEl.selectedIndex = 0;
    return false;
  }

  if (v === '7') {
    // Delete translation (set to *)
    document.querySelectorAll<HTMLInputElement>('.markcheck:checked').forEach(checkbox => {
      const checkboxValue = checkbox.value;
      const transInput = document.querySelector<HTMLInputElement>(`#Trans_${checkboxValue} input`);
      if (transInput) {
        transInput.value = '*';
      }
    });
    selectEl.selectedIndex = 0;
    return false;
  }

  // Set status for all checked terms
  document.querySelectorAll<HTMLInputElement>('.markcheck:checked').forEach(checkbox => {
    const checkboxValue = checkbox.value;
    const statSelect = document.querySelector<HTMLSelectElement>(`#Stat_${checkboxValue}`);
    if (statSelect) {
      statSelect.value = v;
    }
  });
  selectEl.selectedIndex = 0;
  return false;
}

/**
 * Initialize bulk translate page.
 * @deprecated Since 3.1.0, use bulkTranslateApp() Alpine component
 */
export function initBulkTranslate(dictionaries: {
  dict1: string;
  dict2: string;
  translate: string;
}): void {
  // Set dictionary links in language config
  setDictionaryLinks({
    dict1: dictionaries.dict1,
    dict2: dictionaries.dict2,
    translator: dictionaries.translate
  });

  // Mark headers as not translatable
  document.querySelectorAll('h3, h4, title').forEach(el => {
    el.classList.add('notranslate');
  });

  // Set up interactions when page is fully loaded
  window.addEventListener('load', bulkInteractions);

  // Set up checkbox handlers when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bulkCheckbox);
  } else {
    bulkCheckbox();
  }
}

/**
 * Auto-initialize bulk translate from JSON config element.
 * @deprecated Since 3.1.0, use bulkTranslateApp() Alpine component
 */
function autoInitBulkTranslate(): void {
  const configEl = document.getElementById('bulk-translate-config');
  if (!configEl) {
    return;
  }

  try {
    const config: BulkTranslateConfig = JSON.parse(configEl.textContent || '{}');
    initBulkTranslate(config.dictionaries);

    // Set up Google Translate callback
    window.googleTranslateElementInit = function() {
      googleTranslateElementInit(config.sourceLanguage, config.targetLanguage);
    };
  } catch {
    // Config parse failed, page may not be bulk translate form
  }
}

/**
 * Initialize event delegation for bulk translate form controls.
 * @deprecated Since 3.1.0, use bulkTranslateApp() Alpine component
 */
function initBulkTranslateEvents(): void {
  // Event delegation for click events
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement;

    // Mark All button
    if (target.closest('[data-action="bulk-mark-all"]')) {
      e.preventDefault();
      markAll();
      return;
    }

    // Mark None button
    if (target.closest('[data-action="bulk-mark-none"]')) {
      e.preventDefault();
      markNone();
    }
  });

  // Term toggles select (status changes, lowercase, delete translation)
  document.addEventListener('change', (e) => {
    const target = e.target as HTMLElement;
    if (target.matches('[data-action="bulk-term-toggles"]')) {
      changeTermToggles(target as HTMLSelectElement);
    }
  });
}

// Auto-initialize on document ready (legacy support)
document.addEventListener('DOMContentLoaded', () => {
  autoInitBulkTranslate();
  initBulkTranslateEvents();
});

// Export to window for potential external use
declare global {
  interface Window {
    bulkTranslateApp: typeof bulkTranslateApp;
    clickDictionary: typeof clickDictionary;
    bulkInteractions: typeof bulkInteractions;
    bulkCheckbox: typeof bulkCheckbox;
    markAll: typeof markAll;
    markNone: typeof markNone;
    changeTermToggles: typeof changeTermToggles;
    initBulkTranslate: typeof initBulkTranslate;
  }
}

window.bulkTranslateApp = bulkTranslateApp;
window.clickDictionary = clickDictionary;
window.bulkInteractions = bulkInteractions;
window.bulkCheckbox = bulkCheckbox;
// Note: googleTranslateElementInit is set dynamically by initBulkTranslate or bulkTranslateApp
window.markAll = markAll;
window.markNone = markNone;
window.changeTermToggles = changeTermToggles;
window.initBulkTranslate = initBulkTranslate;
