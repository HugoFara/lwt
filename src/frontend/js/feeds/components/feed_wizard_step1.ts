/**
 * Feed Wizard Step 1 Component - Enter Feed URL.
 *
 * Alpine.js component for the feed wizard step 1 (URL entry).
 * Handles URL validation and form submission.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import type { FeedWizardStoreState } from '../types/feed_wizard_types';
import { getFeedWizardStore } from '../stores/feed_wizard_store';

/**
 * Step 1 component configuration from PHP.
 */
export interface Step1Config {
  rssUrl: string;
  hasError: boolean;
  editFeedId: number | null;
}

/**
 * Step 1 component data interface.
 */
export interface FeedWizardStep1Data {
  // Configuration
  config: Step1Config;

  // Form data
  rssUrl: string;

  // Computed
  readonly store: FeedWizardStoreState;
  readonly isValidUrl: boolean;

  // Lifecycle
  init(): void;

  // Actions
  cancel(): void;
}

/**
 * Read configuration from JSON script tag.
 */
function readConfig(): Step1Config {
  const configEl = document.getElementById('wizard-step1-config');
  if (!configEl) {
    return {
      rssUrl: '',
      hasError: false,
      editFeedId: null
    };
  }

  try {
    return JSON.parse(configEl.textContent || '{}');
  } catch {
    console.error('Failed to parse wizard step 1 config');
    return {
      rssUrl: '',
      hasError: false,
      editFeedId: null
    };
  }
}

/**
 * Feed wizard step 1 component factory.
 */
export function feedWizardStep1Data(): FeedWizardStep1Data {
  const config = readConfig();

  return {
    // Configuration
    config,

    // Form data
    rssUrl: config.rssUrl || '',

    get store(): FeedWizardStoreState {
      return getFeedWizardStore();
    },

    get isValidUrl(): boolean {
      if (!this.rssUrl) return false;
      try {
        new URL(this.rssUrl);
        return true;
      } catch {
        return false;
      }
    },

    init(): void {
      // Configure store for step 1
      this.store.configure({
        step: 1,
        rssUrl: this.config.rssUrl,
        editFeedId: this.config.editFeedId
      });
    },

    cancel(): void {
      window.location.href = '/feeds/edit?del_wiz=1';
    }
  };
}

/**
 * Initialize the step 1 Alpine component.
 */
export function initFeedWizardStep1Alpine(): void {
  Alpine.data('feedWizardStep1', feedWizardStep1Data);
}

// Register immediately
initFeedWizardStep1Alpine();

// Expose for global access
declare global {
  interface Window {
    feedWizardStep1Data: typeof feedWizardStep1Data;
  }
}

window.feedWizardStep1Data = feedWizardStep1Data;
