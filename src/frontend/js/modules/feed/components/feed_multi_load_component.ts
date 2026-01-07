/**
 * Feed Multi-Load Alpine Component
 *
 * Handles batch feed update selection with checkboxes,
 * replacing inline event handlers with Alpine.js reactive patterns.
 *
 * @license Unlicense
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import { setLang } from '@modules/language/stores/language_settings';

/**
 * Configuration for the multi-load component, passed from PHP.
 */
export interface FeedMultiLoadConfig {
  cancelUrl?: string;
  filterUrl?: string;
}

/**
 * Feed multi-load Alpine component data interface.
 * Note: $el is provided by Alpine at runtime.
 */
export interface FeedMultiLoadData {
  // Config
  cancelUrl: string;
  filterUrl: string;

  // Methods
  init(): void;
  markAll(): void;
  markNone(): void;
  collectAndSubmit(): void;
  handleLanguageFilter(event: Event): void;
  cancel(): void;
}

/**
 * Alpine component context type with $el magic property.
 */
type AlpineContext = FeedMultiLoadData & { $el: HTMLElement };

/**
 * Create the feed multi-load Alpine component.
 *
 * @param config - Initial configuration from PHP
 * @returns Alpine component data object
 */
export function feedMultiLoadData(config: FeedMultiLoadConfig = {}): FeedMultiLoadData {
  return {
    cancelUrl: config.cancelUrl ?? '/feeds?selected_feed=0',
    filterUrl: config.filterUrl ?? '/feeds/edit?multi_load_feed=1&page=1',

    /**
     * Initialize the component.
     * Reads config from JSON script tag if available.
     */
    init(): void {
      const configEl = document.getElementById('feed-multi-load-config');
      if (configEl) {
        try {
          const jsonConfig = JSON.parse(configEl.textContent || '{}') as FeedMultiLoadConfig;
          this.cancelUrl = jsonConfig.cancelUrl ?? this.cancelUrl;
          this.filterUrl = jsonConfig.filterUrl ?? this.filterUrl;
        } catch {
          // Invalid JSON, use defaults
        }
      }
    },

    /**
     * Mark all checkboxes in the form.
     */
    markAll(this: AlpineContext): void {
      const form = this.$el.querySelector('form') ?? this.$el.closest('form');
      if (!form) return;

      form.querySelectorAll<HTMLInputElement>('input[type="checkbox"].markcheck')
        .forEach(cb => { cb.checked = true; });
    },

    /**
     * Unmark all checkboxes in the form.
     */
    markNone(this: AlpineContext): void {
      const form = this.$el.querySelector('form') ?? this.$el.closest('form');
      if (!form) return;

      form.querySelectorAll<HTMLInputElement>('input[type="checkbox"].markcheck')
        .forEach(cb => { cb.checked = false; });
    },

    /**
     * Collect all checked checkbox values into hidden field and submit.
     */
    collectAndSubmit(this: AlpineContext): void {
      const form = this.$el.querySelector('form') ?? this.$el.closest('form');
      if (!form) return;

      const hiddenField = form.querySelector<HTMLInputElement>('#map');
      if (!hiddenField) return;

      const checkboxes = form.querySelectorAll<HTMLInputElement>('input[type="checkbox"]:checked');
      const values = Array.from(checkboxes)
        .map(cb => cb.value)
        .filter(val => val !== '');

      hiddenField.value = values.join(', ');
    },

    /**
     * Handle language filter change.
     */
    handleLanguageFilter(event: Event): void {
      const select = event.target as HTMLSelectElement;
      setLang(select, this.filterUrl);
    },

    /**
     * Navigate to cancel URL.
     */
    cancel(): void {
      location.href = this.cancelUrl;
    }
  };
}

/**
 * Initialize the feed multi-load Alpine component.
 */
export function initFeedMultiLoadAlpine(): void {
  Alpine.data('feedMultiLoad', feedMultiLoadData);
}

// Register immediately (before Alpine.start())
initFeedMultiLoadAlpine();

// Export to window for debugging
declare global {
  interface Window {
    feedMultiLoadData: typeof feedMultiLoadData;
  }
}

window.feedMultiLoadData = feedMultiLoadData;
