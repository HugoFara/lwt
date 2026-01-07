/**
 * Feed Index/Management Alpine Component
 *
 * Handles event delegation for the feeds management page,
 * replacing inline onclick/onchange handlers with Alpine.js reactive patterns.
 *
 * @license Unlicense
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import { setLang, resetAll } from '@modules/language/stores/language_settings';
import { selectToggle, multiActionGo } from '@shared/forms/bulk_actions';

/**
 * Configuration for the feed index component.
 */
export interface FeedIndexConfig {
  resetUrl?: string;
  filterUrl?: string;
  pageBaseUrl?: string;
  currentQuery?: string;
}

/**
 * Feed index Alpine component data interface.
 */
export interface FeedIndexData {
  // Config
  resetUrl: string;
  filterUrl: string;
  pageBaseUrl: string;
  query: string;

  // Methods
  init(): void;
  handleReset(): void;
  handleLanguageFilter(event: Event): void;
  handleQueryFilter(): void;
  handleClearQuery(): void;
  markAll(): void;
  markNone(): void;
  handleMarkAction(event: Event): void;
  handleSort(event: Event): void;
  confirmDelete(feedId: string): void;
}

/**
 * Alpine component context type with $el magic property.
 */
type AlpineContext = FeedIndexData & { $el: HTMLElement };

/**
 * Create the feed index Alpine component.
 *
 * @param config - Initial configuration from PHP
 * @returns Alpine component data object
 */
export function feedIndexData(config: FeedIndexConfig = {}): FeedIndexData {
  return {
    resetUrl: config.resetUrl ?? '/feeds/edit',
    filterUrl: config.filterUrl ?? '/feeds/edit?manage_feeds=1',
    pageBaseUrl: config.pageBaseUrl ?? '/feeds/edit',
    query: config.currentQuery ?? '',

    /**
     * Initialize the component.
     * Reads config from JSON script tag if available.
     */
    init(): void {
      const configEl = document.getElementById('feed-index-config');
      if (configEl) {
        try {
          const jsonConfig = JSON.parse(configEl.textContent || '{}') as FeedIndexConfig;
          this.resetUrl = jsonConfig.resetUrl ?? this.resetUrl;
          this.filterUrl = jsonConfig.filterUrl ?? this.filterUrl;
          this.pageBaseUrl = jsonConfig.pageBaseUrl ?? this.pageBaseUrl;
          this.query = jsonConfig.currentQuery ?? this.query;
        } catch {
          // Invalid JSON, use defaults
        }
      }
    },

    /**
     * Handle reset all button click.
     */
    handleReset(): void {
      resetAll(this.resetUrl);
    },

    /**
     * Handle language filter change.
     */
    handleLanguageFilter(event: Event): void {
      const select = event.target as HTMLSelectElement;
      setLang(select, this.filterUrl);
    },

    /**
     * Handle query filter form submission.
     */
    handleQueryFilter(): void {
      const val = encodeURIComponent(this.query);
      location.href = `${this.pageBaseUrl}?page=1&query=${val}`;
    },

    /**
     * Handle clear query button click.
     */
    handleClearQuery(): void {
      this.query = '';
      location.href = `${this.pageBaseUrl}?page=1&query=`;
    },

    /**
     * Mark all checkboxes.
     */
    markAll(): void {
      selectToggle(true, 'form2');
    },

    /**
     * Unmark all checkboxes.
     */
    markNone(): void {
      selectToggle(false, 'form2');
    },

    /**
     * Handle mark action select change.
     */
    handleMarkAction(this: AlpineContext, event: Event): void {
      const select = event.target as HTMLSelectElement;

      // Collect checked checkbox values into hidden field
      const hiddenField = document.getElementById('map') as HTMLInputElement | null;
      if (hiddenField) {
        const checkedInputs = document.querySelectorAll<HTMLInputElement>('input.markcheck:checked');
        const checkedValues = Array.from(checkedInputs)
          .map(input => input.value)
          .join(', ');
        hiddenField.value = checkedValues;
      }

      // Get the form containing this component
      const form = this.$el.querySelector('form[name="form1"]') ??
                   this.$el.closest('form') ??
                   document.forms.namedItem('form1');
      if (form) {
        multiActionGo(form as HTMLFormElement, select);
      }
    },

    /**
     * Handle sort select change.
     */
    handleSort(event: Event): void {
      const select = event.target as HTMLSelectElement;
      const val = select.value;
      location.href = `${this.pageBaseUrl}?page=1&sort=${encodeURIComponent(val)}`;
    },

    /**
     * Confirm and execute feed deletion.
     */
    confirmDelete(feedId: string): void {
      if (confirm('Are you sure?')) {
        location.href = `/feeds/edit?markaction=del&selected_feed=${feedId}`;
      }
    }
  };
}

/**
 * Initialize the feed index Alpine component.
 */
export function initFeedIndexAlpine(): void {
  Alpine.data('feedIndex', feedIndexData);
}

// Register immediately (before Alpine.start())
initFeedIndexAlpine();

// Export to window for backward compatibility
declare global {
  interface Window {
    feedIndexData: typeof feedIndexData;
  }
}

window.feedIndexData = feedIndexData;
