/**
 * Bulk Translate - Functions for the bulk translate word form
 *
 * Handles dictionary lookups, form interactions, and Google Translate
 * integration for bulk translating unknown words.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { LWT_DATA } from '../core/lwt_state';
import { createTheDictUrl, owin } from '../terms/dictionary';
import { selectToggle } from '../forms/bulk_actions';

declare global {
  interface Window {
    WBLINK?: string;
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
 * Handle click on a dictionary link in bulk translate form.
 * Opens the dictionary for the term in the same row.
 */
export function clickDictionary(element: HTMLElement): void {
  let dictLink: string;

  if (element.classList.contains('dict1')) {
    dictLink = LWT_DATA.language.dict_link1;
  } else if (element.classList.contains('dict2')) {
    dictLink = LWT_DATA.language.dict_link2;
  } else if (element.classList.contains('dict3')) {
    dictLink = LWT_DATA.language.translator_link;
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
 * - Form submission handler
 * - Dictionary click handlers
 * - Translation deletion handlers
 * - Wait for Google Translate to populate, then set up inputs
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
      document.querySelectorAll<HTMLElement>('.term').forEach(term => {
        const parent = term.parentElement;
        if (parent) {
          parent.style.position = 'relative';
        }

        const dictLinks =
          '<div class="dict">' +
          (LWT_DATA.language.dict_link1 ? '<span class="dict1">D1</span>' : '') +
          (LWT_DATA.language.dict_link2 ? '<span class="dict2">D2</span>' : '') +
          (LWT_DATA.language.translator_link ? '<span class="dict3">Tr</span>' : '') +
          '</div>';

        term.insertAdjacentHTML('afterend', dictLinks);
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
 * Controls enabling/disabling of term inputs based on checkbox state.
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
 *
 * @param sourceLanguage Source language code (e.g., 'en')
 * @param targetLanguage Target language code (e.g., 'es')
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
 * Handle changes to the bulk action select (status changes, lowercase, delete translation).
 *
 * @param selectEl The select element
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

interface BulkTranslateConfig {
  dictionaries: {
    dict1: string;
    dict2: string;
    translate: string;
  };
  sourceLanguage: string;
  targetLanguage: string;
}

/**
 * Initialize bulk translate page.
 * Sets up all event handlers for the bulk translate form.
 *
 * @param dictionaries Dictionary URLs configuration
 */
export function initBulkTranslate(dictionaries: {
  dict1: string;
  dict2: string;
  translate: string;
}): void {
  // Set dictionary links in LWT_DATA
  LWT_DATA.language.dict_link1 = dictionaries.dict1;
  LWT_DATA.language.dict_link2 = dictionaries.dict2;
  LWT_DATA.language.translator_link = dictionaries.translate;

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
 * Reads configuration from #bulk-translate-config and sets up handlers.
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

// Auto-initialize on document ready
document.addEventListener('DOMContentLoaded', () => {
  autoInitBulkTranslate();
  initBulkTranslateEvents();
});
