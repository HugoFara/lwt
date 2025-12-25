/**
 * Feed Text Edit Alpine Component
 *
 * Handles bulk feed text import form functionality:
 * - Scrolling to the first table on page load
 * - Initializing Tagify on feed tag inputs
 * - Checkbox changes to enable/disable feed form fields
 *
 * @license Unlicense
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import Tagify from '@yaireo/tagify';
import { fetchTextTags, getTextTagsSync } from '../../core/app_data';

// Extend HTMLInputElement to include the _tagify property
declare global {
  interface HTMLInputElement {
    _tagify?: Tagify;
  }
}

/**
 * Configuration for the feed text edit component.
 */
export interface FeedTextEditConfig {
  scrollToTable?: boolean;
}

/**
 * Feed text edit Alpine component data interface.
 */
export interface FeedTextEditData {
  // Config
  scrollToTable: boolean;
  initialized: boolean;

  // Methods
  init(): Promise<void>;
  initTagifyOnFeedInput(ulElement: HTMLUListElement): void;
  handleFeedCheckboxChange(event: Event): void;
}

/**
 * Alpine component context type with $el magic property.
 */
type AlpineContext = FeedTextEditData & { $el: HTMLElement };

/**
 * Create the feed text edit Alpine component.
 *
 * @param config - Initial configuration from PHP
 * @returns Alpine component data object
 */
export function feedTextEditData(config: FeedTextEditConfig = {}): FeedTextEditData {
  return {
    scrollToTable: config.scrollToTable ?? true,
    initialized: false,

    /**
     * Initialize the component.
     * Reads config from JSON script tag if available.
     */
    async init(this: AlpineContext): Promise<void> {
      const configEl = document.getElementById('feed-text-edit-config');
      if (configEl) {
        try {
          const jsonConfig = JSON.parse(configEl.textContent || '{}') as FeedTextEditConfig;
          this.scrollToTable = jsonConfig.scrollToTable ?? this.scrollToTable;
        } catch {
          // Invalid JSON, use defaults
        }
      }

      // Scroll to the first table
      if (this.scrollToTable) {
        const firstTable = this.$el.querySelector('table');
        if (firstTable) {
          firstTable.scrollIntoView({ behavior: 'instant', block: 'start' });
        }
      }

      // Prefetch text tags before initializing Tagify inputs
      await fetchTextTags();

      // Initialize Tagify on all feed tag UL elements
      const ulElements = this.$el.querySelectorAll<HTMLUListElement>('ul[name^="feed"]');
      ulElements.forEach(ul => this.initTagifyOnFeedInput(ul));

      this.initialized = true;
    },

    /**
     * Initialize Tagify on a single UL element, converting it to a Tagify input.
     */
    initTagifyOnFeedInput(ulElement: HTMLUListElement): void {
      const fieldName = ulElement.getAttribute('name');
      if (!fieldName) return;

      // Extract existing tags from LI elements
      const existingTags: string[] = [];
      ulElement.querySelectorAll('li').forEach((li) => {
        const text = li.textContent?.trim();
        if (text) {
          existingTags.push(text);
        }
      });

      // Create input element to replace the UL
      const input = document.createElement('input');
      input.type = 'text';
      input.name = fieldName;
      input.className = 'tagify-feed-input';
      input.value = existingTags.join(', ');

      // Extract feed index from name like "feed[0][TxTags]"
      const match = fieldName.match(/feed\[(\d+)\]/);
      input.dataset.feedIndex = match ? match[1] : '';

      // Replace UL with input
      ulElement.replaceWith(input);

      // Initialize Tagify with currently cached text tags
      const tagify = new Tagify(input, {
        whitelist: getTextTagsSync(),
        dropdown: {
          enabled: 1,
          maxItems: 20,
          closeOnSelect: true,
          highlightFirst: true
        },
        duplicates: false
      });

      // Add existing tags
      if (existingTags.length > 0) {
        tagify.addTags(existingTags);
      }

      // Store tagify instance on the input for later access
      input._tagify = tagify;
    },

    /**
     * Handle checkbox change to enable/disable the associated feed form fields.
     */
    handleFeedCheckboxChange(event: Event): void {
      const checkbox = event.target as HTMLInputElement;
      const feedIndex = checkbox.value;
      const feedFields = document.querySelectorAll<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>(
        `[name^="feed[${feedIndex}]"]`
      );
      const tagifyInput = document.querySelector<HTMLInputElement>(
        '.tagify-feed-input[data-feed-index="' + feedIndex + '"]'
      );
      const tagify = tagifyInput?._tagify;

      if (checkbox.checked) {
        feedFields.forEach(el => {
          el.disabled = false;
          // Add notempty class to title and text fields
          if (el.name.includes('[TxTitle]') || el.name.includes('[TxText]')) {
            el.classList.add('notempty');
          }
        });
        if (tagify) {
          tagify.setDisabled(false);
        }
      } else {
        feedFields.forEach(el => {
          el.disabled = true;
          el.classList.remove('notempty');
        });
        if (tagify) {
          tagify.setDisabled(true);
        }
      }
    }
  };
}

/**
 * Initialize the feed text edit Alpine component.
 */
export function initFeedTextEditAlpine(): void {
  Alpine.data('feedTextEdit', feedTextEditData);
}

// Register immediately (before Alpine.start())
initFeedTextEditAlpine();

// Export to window for backward compatibility
declare global {
  interface Window {
    feedTextEditData: typeof feedTextEditData;
  }
}

window.feedTextEditData = feedTextEditData;
