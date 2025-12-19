/**
 * Feed index/management page functionality.
 *
 * Handles event delegation for the feeds management page, replacing inline
 * onclick/onchange handlers with data attributes.
 *
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { setLang, resetAll } from '../core/language_settings';
import { selectToggle, multiActionGo } from '../forms/bulk_actions';

/**
 * Initialize feed index page event handlers.
 * Uses event delegation to handle all interactions.
 */
export function initFeedIndex(): void {
  const form1 = document.forms.namedItem('form1');
  const form2 = document.forms.namedItem('form2');
  if (!form1) return;

  // Prevent form submission (handled by query button)
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
      const url = resetButton.dataset.url || '/feeds/edit';
      resetAll(url);
    });
  }

  // Language filter select
  const filterLang = form1.querySelector<HTMLSelectElement>('[data-action="filter-language"]');
  if (filterLang) {
    filterLang.addEventListener('change', () => {
      const url = filterLang.dataset.url || '/feeds/edit?manage_feeds=1';
      setLang(filterLang, url);
    });
  }

  // Query filter button
  const queryButton = form1.querySelector<HTMLButtonElement>('[data-action="filter-query"]');
  if (queryButton) {
    queryButton.addEventListener('click', (e) => {
      e.preventDefault();
      const queryInput = form1.querySelector<HTMLInputElement>('[name="query"]');
      const val = encodeURIComponent(queryInput?.value || '');
      location.href = `/feeds/edit?page=1&query=${val}`;
    });
  }

  // Query clear button
  const clearButton = form1.querySelector<HTMLButtonElement>('[data-action="clear-query"]');
  if (clearButton) {
    clearButton.addEventListener('click', (e) => {
      e.preventDefault();
      location.href = '/feeds/edit?page=1&query=';
    });
  }

  // Mark All button
  const markAllButton = form1.querySelector<HTMLButtonElement>('[data-action="mark-all"]');
  if (markAllButton) {
    markAllButton.addEventListener('click', (e) => {
      e.preventDefault();
      selectToggle(true, 'form2');
    });
  }

  // Mark None button
  const markNoneButton = form1.querySelector<HTMLButtonElement>('[data-action="mark-none"]');
  if (markNoneButton) {
    markNoneButton.addEventListener('click', (e) => {
      e.preventDefault();
      selectToggle(false, 'form2');
    });
  }

  // Mark action select (multi-action dropdown)
  const markActionSelect = form1.querySelector<HTMLSelectElement>('[data-action="mark-action"]');
  if (markActionSelect) {
    markActionSelect.addEventListener('change', () => {
      // Collect checked checkbox values into hidden field
      const hiddenField = document.getElementById('map') as HTMLInputElement | null;
      if (hiddenField) {
        const checkedInputs = document.querySelectorAll<HTMLInputElement>('input:checked');
        const checkedValues = Array.from(checkedInputs)
          .map(input => input.value)
          .join(', ');
        hiddenField.value = checkedValues;
      }
      // Trigger multi-action
      multiActionGo(form1, markActionSelect);
    });
  }

  // Sort select
  const sortSelect = form1.querySelector<HTMLSelectElement>('[data-action="sort"]');
  if (sortSelect) {
    sortSelect.addEventListener('change', () => {
      const val = sortSelect.value;
      location.href = `/feeds/edit?page=1&sort=${encodeURIComponent(val)}`;
    });
  }

  // Delete confirmation clicks (using event delegation on form2)
  if (form2) {
    form2.addEventListener('click', (e) => {
      const target = e.target as HTMLElement;
      const deleteSpan = target.closest('[data-action="delete-feed"]') as HTMLElement | null;
      if (deleteSpan) {
        e.preventDefault();
        const feedId = deleteSpan.dataset.feedId;
        if (feedId && confirm('Are you sure?')) {
          location.href = `/feeds/edit?markaction=del&selected_feed=${feedId}`;
        }
      }
    });
  }
}

// Auto-initialize on DOM ready if on feed index page
document.addEventListener('DOMContentLoaded', () => {
  // Check if this is the feed index page by looking for specific elements
  const form1 = document.forms.namedItem('form1');
  const markActionSelect = form1?.querySelector('[data-action="mark-action"]');
  if (markActionSelect) {
    initFeedIndex();
  }
});

// Export to window for potential external use
declare global {
  interface Window {
    initFeedIndex: typeof initFeedIndex;
  }
}

window.initFeedIndex = initFeedIndex;
