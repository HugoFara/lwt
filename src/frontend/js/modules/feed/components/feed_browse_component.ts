/**
 * Feed Browse Alpine Component
 *
 * Handles event delegation for the feeds browse page,
 * replacing inline onclick/onchange handlers with Alpine.js reactive patterns.
 *
 * @license Unlicense
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import { setLang, resetAll } from '@modules/language/stores/language_settings';
import { selectToggle } from '@shared/forms/bulk_actions';
import { markClick } from '@shared/utils/ui_utilities';

/**
 * Configuration for the feed browse component.
 */
export interface FeedBrowseConfig {
  filterUrl?: string;
  resetUrl?: string;
  pageBaseUrl?: string;
  currentQuery?: string;
  currentQueryMode?: string;
}

/**
 * Feed browse Alpine component data interface.
 */
export interface FeedBrowseData {
  // Config
  filterUrl: string;
  resetUrl: string;
  pageBaseUrl: string;
  query: string;
  queryMode: string;

  // Methods
  init(): void;
  handleLanguageFilter(event: Event): void;
  handleQueryMode(event: Event): void;
  handleQueryFilter(): void;
  handleClearQuery(): void;
  handleReset(): void;
  handleFeedSelect(event: Event): void;
  handleSort(event: Event): void;
  markAll(): void;
  markNone(): void;
  openPopup(url: string, type: string): void;
  handleNotFoundClick(event: Event): void;
}

/**
 * Create the feed browse Alpine component.
 *
 * @param config - Initial configuration from PHP
 * @returns Alpine component data object
 */
export function feedBrowseData(config: FeedBrowseConfig = {}): FeedBrowseData {
  return {
    filterUrl: config.filterUrl ?? '/feeds?page=1&selected_feed=0',
    resetUrl: config.resetUrl ?? '/feeds',
    pageBaseUrl: config.pageBaseUrl ?? '/feeds',
    query: config.currentQuery ?? '',
    queryMode: config.currentQueryMode ?? '',

    /**
     * Initialize the component.
     * Reads config from JSON script tag if available.
     */
    init(): void {
      const configEl = document.getElementById('feed-browse-config');
      if (configEl) {
        try {
          const jsonConfig = JSON.parse(configEl.textContent || '{}') as FeedBrowseConfig;
          this.filterUrl = jsonConfig.filterUrl ?? this.filterUrl;
          this.resetUrl = jsonConfig.resetUrl ?? this.resetUrl;
          this.pageBaseUrl = jsonConfig.pageBaseUrl ?? this.pageBaseUrl;
          this.query = jsonConfig.currentQuery ?? this.query;
          this.queryMode = jsonConfig.currentQueryMode ?? this.queryMode;
        } catch {
          // Invalid JSON, use defaults
        }
      }
    },

    /**
     * Handle language filter change.
     */
    handleLanguageFilter(event: Event): void {
      const select = event.target as HTMLSelectElement;
      setLang(select, this.filterUrl);
    },

    /**
     * Handle query mode change.
     */
    handleQueryMode(event: Event): void {
      const select = event.target as HTMLSelectElement;
      const mode = select.value;
      const val = encodeURIComponent(this.query);
      location.href = `${this.pageBaseUrl}?page=1&query=${val}&query_mode=${encodeURIComponent(mode)}`;
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
     * Handle reset all button click.
     */
    handleReset(): void {
      resetAll(this.resetUrl);
    },

    /**
     * Handle feed select change.
     */
    handleFeedSelect(event: Event): void {
      const select = event.target as HTMLSelectElement;
      const val = select.value;
      location.href = `${this.pageBaseUrl}?page=1&selected_feed=${encodeURIComponent(val)}`;
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
     * Open a popup window.
     */
    openPopup(url: string, type: string): void {
      if (type === 'audio') {
        window.open(url, 'child', 'scrollbars,width=650,height=600');
      } else {
        window.open(url);
      }
    },

    /**
     * Handle click on not found images.
     * Replaces error images with checkboxes.
     */
    handleNotFoundClick(event: Event): void {
      const target = event.target as HTMLElement;
      if (!target.classList.contains('not_found')) return;

      const id = target.getAttribute('name') || '';

      const label = document.createElement('label');
      label.className = 'wrap_checkbox';
      label.setAttribute('for', id);
      label.innerHTML = '<span></span>';

      const checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.className = 'markcheck';
      checkbox.id = id;
      checkbox.value = id;
      checkbox.name = 'marked_items[]';
      checkbox.addEventListener('change', () => {
        markClick();
      });

      target.after(label);
      target.replaceWith(checkbox);

      // Re-index tab order
      const elements = document.querySelectorAll<HTMLElement>(
        ':is(input, select, textarea, button), .wrap_checkbox span, a:not([name^="rec"])'
      );
      elements.forEach((el, i) => {
        el.setAttribute('tabindex', String(i + 1));
      });
    }
  };
}

/**
 * Initialize the feed browse Alpine component.
 */
export function initFeedBrowseAlpine(): void {
  Alpine.data('feedBrowse', feedBrowseData);
}

// Register immediately (before Alpine.start())
initFeedBrowseAlpine();

// Export to window for backward compatibility
declare global {
  interface Window {
    feedBrowseData: typeof feedBrowseData;
  }
}

window.feedBrowseData = feedBrowseData;
