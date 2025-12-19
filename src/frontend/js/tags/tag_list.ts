/**
 * Tag list page functionality.
 *
 * Handles event delegation for the tag list filter and table,
 * replacing inline onclick/onchange handlers with data attributes.
 * Works for both term tags (/tags) and text tags (/tags/text).
 *
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { selectToggle, multiActionGo, allActionGo } from '../forms/bulk_actions';
import { lwtFormCheck } from '../forms/unloadformcheck';

/**
 * Get the base URL from a data attribute or default.
 */
function getBaseUrl(): string {
  const form1 = document.forms.namedItem('form1');
  const resetButton = form1?.querySelector<HTMLButtonElement>('[data-action="reset-all"]');
  return resetButton?.dataset.baseUrl || '/tags/term';
}

/**
 * Navigate to tag list with updated query parameters.
 *
 * @param params Query parameters to set (merged with page=1)
 */
function navigateWithParams(params: Record<string, string>): void {
  const baseUrl = getBaseUrl();
  const searchParams = new URLSearchParams({ page: '1', ...params });
  location.href = `${baseUrl}?${searchParams.toString()}`;
}

/**
 * Initialize tag list filter event handlers.
 */
function initTagListFilter(): void {
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
      navigateWithParams({ query: '' });
    });
  }

  // Query filter button
  const queryButton = form1.querySelector<HTMLButtonElement>('[data-action="filter-query"]');
  if (queryButton) {
    queryButton.addEventListener('click', (e) => {
      e.preventDefault();
      const queryInput = form1.querySelector<HTMLInputElement>('[name="query"]');
      const val = queryInput?.value || '';
      navigateWithParams({ query: val });
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

  // Sort order select
  const sortSelect = form1.querySelector<HTMLSelectElement>('[data-action="sort"]');
  if (sortSelect) {
    sortSelect.addEventListener('change', () => {
      navigateWithParams({ sort: sortSelect.value });
    });
  }
}

/**
 * Initialize tag list table event handlers.
 */
function initTagListTable(): void {
  const form2 = document.forms.namedItem('form2');
  if (!form2) return;

  // All action select (actions on all records)
  const allActionSelect = form2.querySelector<HTMLSelectElement>('[data-action="all-action"]');
  if (allActionSelect) {
    allActionSelect.addEventListener('change', () => {
      const recno = parseInt(allActionSelect.dataset.recno || '0', 10);
      allActionGo(form2, allActionSelect, recno);
    });
  }

  // Mark All button
  const markAllButton = form2.querySelector<HTMLButtonElement>('[data-action="mark-all"]');
  if (markAllButton) {
    markAllButton.addEventListener('click', (e) => {
      e.preventDefault();
      selectToggle(true, 'form2');
    });
  }

  // Mark None button
  const markNoneButton = form2.querySelector<HTMLButtonElement>('[data-action="mark-none"]');
  if (markNoneButton) {
    markNoneButton.addEventListener('click', (e) => {
      e.preventDefault();
      selectToggle(false, 'form2');
    });
  }

  // Mark action select (actions on marked records)
  const markActionSelect = form2.querySelector<HTMLSelectElement>('[data-action="mark-action"]');
  if (markActionSelect) {
    markActionSelect.addEventListener('change', () => {
      multiActionGo(form2, markActionSelect);
    });
  }
}

/**
 * Initialize tag form event handlers (new/edit forms).
 */
function initTagForm(): void {
  // Initialize form check for forms with lwt-form-check class
  const formCheckForms = document.querySelectorAll<HTMLFormElement>('form.lwt-form-check');
  formCheckForms.forEach((_form) => {
    lwtFormCheck.askBeforeExit();
  });

  // Cancel buttons with data-action="cancel"
  const cancelButtons = document.querySelectorAll<HTMLButtonElement>('[data-action="cancel"]');
  cancelButtons.forEach((button) => {
    button.addEventListener('click', (e) => {
      e.preventDefault();
      const url = button.dataset.url;
      if (url) {
        lwtFormCheck.resetDirty();
        location.href = url;
      }
    });
  });
}

/**
 * Initialize all tag list event handlers.
 */
export function initTagList(): void {
  initTagListFilter();
  initTagListTable();
  initTagForm();
}

/**
 * Check if the current page is a tag list page.
 */
function isTagListPage(): boolean {
  const form1 = document.forms.namedItem('form1');

  // Check for tag list page (has filter form)
  if (form1) {
    const hasResetButton = form1.querySelector('[data-action="reset-all"]') !== null;
    const hasSortSelect = form1.querySelector('[data-action="sort"]') !== null;

    // Also check for form2 with mark actions (table part)
    const form2 = document.forms.namedItem('form2');
    const hasMarkAction = form2?.querySelector('[data-action="mark-action"]') !== null;

    if (hasResetButton && (hasSortSelect || hasMarkAction)) {
      return true;
    }
  }

  // Check for tag form page (new/edit forms)
  const tagForm = document.querySelector('form.lwt-form-check[name="newtag"], form.lwt-form-check[name="edittag"]');
  if (tagForm) {
    return true;
  }

  return false;
}

// Auto-initialize on DOM ready if on tag list page
document.addEventListener('DOMContentLoaded', () => {
  if (isTagListPage()) {
    initTagList();
  }
});

// Export to window for potential external use
declare global {
  interface Window {
    initTagList: typeof initTagList;
  }
}

window.initTagList = initTagList;
