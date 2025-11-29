/**
 * Feed multi-load page functionality.
 *
 * Handles collecting checked feed checkboxes and submitting them for
 * batch updates, replacing inline jQuery with TypeScript.
 *
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { setLang } from '../core/language_settings';

/**
 * Collect all checked checkboxes and join their values into a hidden field.
 * This is used to submit multiple feed IDs for batch operations.
 *
 * @param formName - Name of the form containing the checkboxes
 * @param hiddenFieldId - ID of the hidden field to populate
 */
export function collectCheckedValues(formName: string, hiddenFieldId: string): void {
  const form = document.forms.namedItem(formName);
  if (!form) return;

  const hiddenField = document.getElementById(hiddenFieldId) as HTMLInputElement | null;
  if (!hiddenField) return;

  const checkboxes = form.querySelectorAll<HTMLInputElement>('input[type="checkbox"]:checked');
  const values = Array.from(checkboxes)
    .map(cb => cb.value)
    .filter(val => val !== ''); // Filter out empty values

  hiddenField.value = values.join(', ');
}

/**
 * Initialize multi-load form event handlers.
 * Sets up the submit button to collect all checked feeds before submission.
 */
export function initFeedMultiLoad(): void {
  const form1 = document.forms.namedItem('form1');
  if (!form1) return;

  // Language filter select
  const filterLang = form1.querySelector<HTMLSelectElement>('[data-action="filter-language"]');
  if (filterLang) {
    filterLang.addEventListener('change', () => {
      const url = filterLang.dataset.url || '/feeds/edit?multi_load_feed=1&page=1';
      setLang(filterLang, url);
    });
  }

  // Mark action button - collect checked values before form submission
  const markActionButton = document.getElementById('markaction') as HTMLButtonElement | null;
  if (markActionButton) {
    markActionButton.addEventListener('click', () => {
      collectCheckedValues('form1', 'map');
    });
  }

  // Cancel button
  const cancelButton = form1.querySelector<HTMLButtonElement>('[data-action="cancel"]');
  if (cancelButton) {
    cancelButton.addEventListener('click', (e) => {
      e.preventDefault();
      const url = cancelButton.dataset.url || '/feeds?selected_feed=0';
      location.href = url;
    });
  }
}

// Auto-initialize on DOM ready if on multi-load page
document.addEventListener('DOMContentLoaded', () => {
  // Check if this is the multi-load page by looking for the #map hidden field
  if (document.getElementById('map')) {
    initFeedMultiLoad();
  }
});

// Export to window for potential external use
declare global {
  interface Window {
    collectCheckedValues: typeof collectCheckedValues;
    initFeedMultiLoad: typeof initFeedMultiLoad;
  }
}

window.collectCheckedValues = collectCheckedValues;
window.initFeedMultiLoad = initFeedMultiLoad;
