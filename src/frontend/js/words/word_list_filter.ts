/**
 * Word list filter page functionality.
 *
 * Handles event delegation for the word list filter/management page,
 * replacing inline onclick/onchange handlers with data attributes.
 *
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { setLang, resetAll } from '../core/language_settings';

/**
 * Base URL for word list pages.
 */
const BASE_URL = '/words/edit';

/**
 * Navigate to word list with updated query parameters.
 *
 * @param params Query parameters to set (merged with page=1)
 */
function navigateWithParams(params: Record<string, string>): void {
  const searchParams = new URLSearchParams({ page: '1', ...params });
  location.href = `${BASE_URL}?${searchParams.toString()}`;
}

/**
 * Initialize word list filter event handlers.
 * Uses event delegation to handle all filter interactions.
 */
export function initWordListFilter(): void {
  const form1 = document.forms.namedItem('form1');
  if (!form1) return;

  // Prevent default form submission (handled by query button)
  form1.addEventListener('submit', (e) => {
    e.preventDefault();
    const queryButton = form1.querySelector<HTMLButtonElement>('[data-action="filter-query"]');
    queryButton?.click();
  });

  // Reset All button
  const resetButton = form1.querySelector<HTMLButtonElement>('[data-action="reset-all"]');
  if (resetButton) {
    resetButton.addEventListener('click', (e) => {
      e.preventDefault();
      resetAll(BASE_URL);
    });
  }

  // Language filter select
  const filterLang = form1.querySelector<HTMLSelectElement>('[data-action="filter-language"]');
  if (filterLang) {
    filterLang.addEventListener('change', () => {
      setLang(filterLang, BASE_URL);
    });
  }

  // Text/Tag mode select
  const textModeSelect = form1.querySelector<HTMLSelectElement>('[data-action="text-mode"]');
  if (textModeSelect) {
    textModeSelect.addEventListener('change', () => {
      const val = textModeSelect.value;
      navigateWithParams({ texttag: '', text: '', text_mode: val });
    });
  }

  // Text filter select
  const textSelect = form1.querySelector<HTMLSelectElement>('[data-action="filter-text"]');
  if (textSelect) {
    textSelect.addEventListener('change', () => {
      navigateWithParams({ text: textSelect.value });
    });
  }

  // Status filter select
  const statusSelect = form1.querySelector<HTMLSelectElement>('[data-action="filter-status"]');
  if (statusSelect) {
    statusSelect.addEventListener('change', () => {
      navigateWithParams({ status: statusSelect.value });
    });
  }

  // Query mode select
  const queryModeSelect = form1.querySelector<HTMLSelectElement>('[data-action="query-mode"]');
  if (queryModeSelect) {
    queryModeSelect.addEventListener('change', () => {
      const queryInput = form1.querySelector<HTMLInputElement>('[name="query"]');
      const val = queryInput?.value || '';
      const mode = queryModeSelect.value;
      navigateWithParams({ query: val, query_mode: mode });
    });
  }

  // Query filter button
  const queryButton = form1.querySelector<HTMLButtonElement>('[data-action="filter-query"]');
  if (queryButton) {
    queryButton.addEventListener('click', (e) => {
      e.preventDefault();
      const queryInput = form1.querySelector<HTMLInputElement>('[name="query"]');
      const queryModeSelect = form1.querySelector<HTMLSelectElement>('[name="query_mode"]');
      const val = encodeURIComponent(queryInput?.value || '');
      const mode = queryModeSelect?.value || 'term,rom,transl';
      location.href = `${BASE_URL}?page=1&query=${val}&query_mode=${mode}`;
    });
  }

  // Query clear button
  const clearButton = form1.querySelector<HTMLButtonElement>('[data-action="clear-query"]');
  if (clearButton) {
    clearButton.addEventListener('click', (e) => {
      e.preventDefault();
      navigateWithParams({ query: '' });
    });
  }

  // Tag #1 filter select
  const tag1Select = form1.querySelector<HTMLSelectElement>('[data-action="filter-tag1"]');
  if (tag1Select) {
    tag1Select.addEventListener('change', () => {
      navigateWithParams({ tag1: tag1Select.value });
    });
  }

  // Tag logic (AND/OR) select
  const tag12Select = form1.querySelector<HTMLSelectElement>('[data-action="filter-tag12"]');
  if (tag12Select) {
    tag12Select.addEventListener('change', () => {
      navigateWithParams({ tag12: tag12Select.value });
    });
  }

  // Tag #2 filter select
  const tag2Select = form1.querySelector<HTMLSelectElement>('[data-action="filter-tag2"]');
  if (tag2Select) {
    tag2Select.addEventListener('change', () => {
      navigateWithParams({ tag2: tag2Select.value });
    });
  }

  // Sort order select
  const sortSelect = form1.querySelector<HTMLSelectElement>('[data-action="sort"]');
  if (sortSelect) {
    sortSelect.addEventListener('change', () => {
      navigateWithParams({ sort: sortSelect.value });
    });
  }
}

/**
 * Check if the current page is the word list filter page.
 */
function isWordListFilterPage(): boolean {
  const form1 = document.forms.namedItem('form1');
  if (!form1) return false;

  // Check for characteristic elements of the word list filter
  const hasLanguageFilter = form1.querySelector('[data-action="filter-language"]') !== null;
  const hasStatusFilter = form1.querySelector('[data-action="filter-status"]') !== null;

  return hasLanguageFilter && hasStatusFilter;
}

// Auto-initialize on DOM ready if on word list filter page
document.addEventListener('DOMContentLoaded', () => {
  if (isWordListFilterPage()) {
    initWordListFilter();
  }
});

// Export to window for potential external use
declare global {
  interface Window {
    initWordListFilter: typeof initWordListFilter;
  }
}

window.initWordListFilter = initWordListFilter;
