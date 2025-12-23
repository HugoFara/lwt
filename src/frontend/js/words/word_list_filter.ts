/**
 * Word List Filter - Alpine.js component for word list filtering.
 *
 * Handles all filter interactions for the word list page including
 * language, text, status, tags, query, and sort filters.
 *
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 * @since   3.1.0 Migrated to Alpine.js component
 */

import Alpine from 'alpinejs';
import { setLang, resetAll } from '../core/language_settings';

/**
 * Configuration for word list filter component.
 */
export interface WordListFilterConfig {
  baseUrl?: string;
  currentQuery?: string;
  currentQueryMode?: string;
}

/**
 * Word list filter Alpine component data interface.
 */
export interface WordListFilterData {
  // Configuration
  baseUrl: string;
  query: string;
  queryMode: string;

  // Methods
  init(): void;
  navigateWithParams(params: Record<string, string>): void;
  handleLanguageChange(event: Event): void;
  handleTextModeChange(event: Event): void;
  handleTextChange(event: Event): void;
  handleStatusChange(event: Event): void;
  handleQueryModeChange(event: Event): void;
  handleQueryFilter(): void;
  handleClearQuery(): void;
  handleTag1Change(event: Event): void;
  handleTag12Change(event: Event): void;
  handleTag2Change(event: Event): void;
  handleSortChange(event: Event): void;
  handleReset(): void;
}

/**
 * Alpine.js component for word list filter functionality.
 * Replaces the vanilla JS event delegation pattern.
 */
export function wordListFilterApp(config: WordListFilterConfig = {}): WordListFilterData {
  return {
    // Configuration
    baseUrl: config.baseUrl ?? '/words/edit',
    query: config.currentQuery ?? '',
    queryMode: config.currentQueryMode ?? 'term,rom,transl',

    /**
     * Initialize the component.
     */
    init(): void {
      // Read config from JSON script tag if available
      const configEl = document.getElementById('word-list-filter-config');
      if (configEl) {
        try {
          const jsonConfig = JSON.parse(configEl.textContent || '{}') as WordListFilterConfig;
          this.baseUrl = jsonConfig.baseUrl ?? this.baseUrl;
          this.query = jsonConfig.currentQuery ?? this.query;
          this.queryMode = jsonConfig.currentQueryMode ?? this.queryMode;
        } catch {
          // Invalid JSON, use defaults
        }
      }
    },

    /**
     * Navigate to word list with updated query parameters.
     */
    navigateWithParams(params: Record<string, string>): void {
      const searchParams = new URLSearchParams({ page: '1', ...params });
      location.href = `${this.baseUrl}?${searchParams.toString()}`;
    },

    /**
     * Handle language filter change.
     */
    handleLanguageChange(event: Event): void {
      const select = event.target as HTMLSelectElement;
      setLang(select, this.baseUrl);
    },

    /**
     * Handle text/tag mode change.
     */
    handleTextModeChange(event: Event): void {
      const select = event.target as HTMLSelectElement;
      this.navigateWithParams({ texttag: '', text: '', text_mode: select.value });
    },

    /**
     * Handle text filter change.
     */
    handleTextChange(event: Event): void {
      const select = event.target as HTMLSelectElement;
      this.navigateWithParams({ text: select.value });
    },

    /**
     * Handle status filter change.
     */
    handleStatusChange(event: Event): void {
      const select = event.target as HTMLSelectElement;
      this.navigateWithParams({ status: select.value });
    },

    /**
     * Handle query mode change.
     */
    handleQueryModeChange(event: Event): void {
      const select = event.target as HTMLSelectElement;
      const val = encodeURIComponent(this.query);
      const mode = select.value;
      location.href = `${this.baseUrl}?page=1&query=${val}&query_mode=${encodeURIComponent(mode)}`;
    },

    /**
     * Handle query filter submission.
     */
    handleQueryFilter(): void {
      const val = encodeURIComponent(this.query);
      location.href = `${this.baseUrl}?page=1&query=${val}&query_mode=${encodeURIComponent(this.queryMode)}`;
    },

    /**
     * Handle clear query button.
     */
    handleClearQuery(): void {
      this.query = '';
      this.navigateWithParams({ query: '' });
    },

    /**
     * Handle tag #1 filter change.
     */
    handleTag1Change(event: Event): void {
      const select = event.target as HTMLSelectElement;
      this.navigateWithParams({ tag1: select.value });
    },

    /**
     * Handle tag logic (AND/OR) change.
     */
    handleTag12Change(event: Event): void {
      const select = event.target as HTMLSelectElement;
      this.navigateWithParams({ tag12: select.value });
    },

    /**
     * Handle tag #2 filter change.
     */
    handleTag2Change(event: Event): void {
      const select = event.target as HTMLSelectElement;
      this.navigateWithParams({ tag2: select.value });
    },

    /**
     * Handle sort order change.
     */
    handleSortChange(event: Event): void {
      const select = event.target as HTMLSelectElement;
      this.navigateWithParams({ sort: select.value });
    },

    /**
     * Handle reset all filters.
     */
    handleReset(): void {
      resetAll(this.baseUrl);
    }
  };
}

// Register Alpine component
if (typeof Alpine !== 'undefined') {
  Alpine.data('wordListFilterApp', wordListFilterApp);
}

// ============================================================================
// Legacy API - For backward compatibility
// ============================================================================

/**
 * Base URL for word list pages.
 */
const BASE_URL = '/words/edit';

/**
 * Navigate to word list with updated query parameters.
 * @deprecated Since 3.1.0, use wordListFilterApp() Alpine component
 */
function navigateWithParams(params: Record<string, string>): void {
  const searchParams = new URLSearchParams({ page: '1', ...params });
  location.href = `${BASE_URL}?${searchParams.toString()}`;
}

/**
 * Initialize word list filter event handlers.
 * Uses event delegation to handle all filter interactions.
 * @deprecated Since 3.1.0, use wordListFilterApp() Alpine component
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

// Auto-initialize on DOM ready if on word list filter page (legacy support)
document.addEventListener('DOMContentLoaded', () => {
  if (isWordListFilterPage()) {
    initWordListFilter();
  }
});

// Export to window for potential external use
declare global {
  interface Window {
    initWordListFilter: typeof initWordListFilter;
    wordListFilterApp: typeof wordListFilterApp;
  }
}

window.initWordListFilter = initWordListFilter;
window.wordListFilterApp = wordListFilterApp;
