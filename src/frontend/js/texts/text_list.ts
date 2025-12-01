/**
 * Text List - Filtering, sorting, and actions for text list pages.
 *
 * This module handles the interactive elements on text list pages
 * (both active texts and archived texts), including:
 * - Language filter
 * - Query/search filter
 * - Tag filters
 * - Sort order
 * - Multi-select actions
 *
 * @license unlicense
 * @since   3.0.0
 */

import { setLang, resetAll } from '../core/language_settings';
import { selectToggle, multiActionGo } from '../forms/bulk_actions';
import { confirmDelete } from '../core/ui_utilities';

/**
 * Get the base URL for the current text list page.
 * Detects whether we're on active texts (/texts) or archived texts (/text/archived).
 */
function getBaseUrl(): string {
  const form = document.querySelector<HTMLFormElement>('form[name="form1"]');
  if (!form) return '/texts';

  // Check for data attribute first
  const baseUrl = form.dataset.baseUrl;
  if (baseUrl) return baseUrl;

  // Detect from page context
  if (window.location.pathname.includes('/text/archived')) {
    return '/text/archived';
  }
  return '/texts';
}

/**
 * Navigate to URL with query parameter.
 */
function navigateWithParam(param: string, value: string): void {
  const baseUrl = getBaseUrl();
  location.href = `${baseUrl}?page=1&${param}=${encodeURIComponent(value)}`;
}

/**
 * Handle language filter change.
 */
function handleLanguageChange(e: Event): void {
  const select = e.target as HTMLSelectElement;
  const baseUrl = getBaseUrl();
  setLang(select, baseUrl);
}

/**
 * Handle query mode change (title, text, or both).
 */
function handleQueryModeChange(e: Event): void {
  const form = document.querySelector<HTMLFormElement>('form[name="form1"]');
  if (!form) return;

  const queryInput = form.querySelector<HTMLInputElement>('[name="query"]');
  const modeSelect = e.target as HTMLSelectElement;

  const val = queryInput?.value || '';
  const mode = modeSelect.value;
  const baseUrl = getBaseUrl();

  location.href = `${baseUrl}?page=1&query=${encodeURIComponent(val)}&query_mode=${mode}`;
}

/**
 * Handle filter button click.
 */
function handleFilterClick(): void {
  const form = document.querySelector<HTMLFormElement>('form[name="form1"]');
  if (!form) return;

  const queryInput = form.querySelector<HTMLInputElement>('[name="query"]');
  const val = encodeURIComponent(queryInput?.value || '');
  const baseUrl = getBaseUrl();

  location.href = `${baseUrl}?page=1&query=${val}`;
}

/**
 * Handle clear filter button click.
 */
function handleClearClick(): void {
  const baseUrl = getBaseUrl();
  location.href = `${baseUrl}?page=1&query=`;
}

/**
 * Handle tag filter change.
 */
function handleTagChange(e: Event): void {
  const select = e.target as HTMLSelectElement;
  const tagNum = select.dataset.tagNum || '1';
  const val = select.value;

  navigateWithParam(`tag${tagNum}`, val);
}

/**
 * Handle tag operator (AND/OR) change.
 */
function handleTagOperatorChange(e: Event): void {
  const select = e.target as HTMLSelectElement;
  navigateWithParam('tag12', select.value);
}

/**
 * Handle sort order change.
 */
function handleSortChange(e: Event): void {
  const select = e.target as HTMLSelectElement;
  navigateWithParam('sort', select.value);
}

/**
 * Handle reset all filters.
 */
function handleResetAll(): void {
  const baseUrl = getBaseUrl();
  resetAll(baseUrl);
}

/**
 * Handle delete confirmation with navigation.
 */
function handleConfirmDelete(e: Event): void {
  e.preventDefault();
  const target = e.currentTarget as HTMLElement;
  const url = target.dataset.url;

  if (url && confirmDelete()) {
    location.href = url;
  }
}

/**
 * Handle mark all/none buttons.
 */
function handleMarkToggle(e: Event): void {
  const button = e.currentTarget as HTMLButtonElement;
  const markAll = button.dataset.markAll === 'true';
  selectToggle(markAll, 'form2');
}

/**
 * Handle multi-action select change.
 */
function handleMultiAction(e: Event): void {
  const form = document.querySelector<HTMLFormElement>('form[name="form2"]');
  const select = e.target as HTMLSelectElement;
  if (form) {
    multiActionGo(form, select);
  }
}

/**
 * Initialize text list page interactions.
 * Uses data attributes to bind event handlers instead of inline onclick.
 */
export function initTextList(): void {
  const form1 = document.querySelector<HTMLFormElement>('form[name="form1"]');
  if (!form1) return;

  // Prevent form submission (use button click instead)
  form1.addEventListener('submit', (e) => {
    e.preventDefault();
    const queryButton = form1.querySelector<HTMLButtonElement>('[data-action="filter"]');
    if (queryButton) {
      queryButton.click();
    }
    return false;
  });

  // Use event delegation on document for dynamically added elements
  document.addEventListener('change', (e) => {
    const target = e.target as HTMLElement;
    if (!target.matches('[data-action]')) return;

    const action = target.dataset.action;
    switch (action) {
      case 'filter-language':
        handleLanguageChange(e);
        break;
      case 'filter-query-mode':
        handleQueryModeChange(e);
        break;
      case 'filter-tag':
        handleTagChange(e);
        break;
      case 'filter-tag-operator':
        handleTagOperatorChange(e);
        break;
      case 'sort':
        handleSortChange(e);
        break;
      case 'multi-action':
        handleMultiAction(e);
        break;
    }
  });

  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement;
    const actionEl = target.closest('[data-action]') as HTMLElement | null;
    if (!actionEl) return;

    const action = actionEl.dataset.action;
    switch (action) {
      case 'filter':
        handleFilterClick();
        break;
      case 'clear-filter':
        handleClearClick();
        break;
      case 'reset-all':
        handleResetAll();
        break;
      case 'confirm-delete':
        handleConfirmDelete(e);
        break;
      case 'mark-toggle':
        handleMarkToggle(e);
        break;
    }
  });
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', initTextList);
