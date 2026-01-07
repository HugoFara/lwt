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
import { setLang, resetAll } from '@modules/language/stores/language_settings';

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

// Export to window for potential external use
declare global {
  interface Window {
    wordListFilterApp: typeof wordListFilterApp;
  }
}

window.wordListFilterApp = wordListFilterApp;
