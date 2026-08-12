/**
 * Feed Wizard Step 4 Component - Edit Options.
 *
 * Alpine.js component for the feed wizard step 4 (final configuration).
 * Handles language selection, feed options, and form submission.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import type { FeedWizardStoreState, FeedOptions } from '../types/feed_wizard_types';
import { getFeedWizardStore } from '../stores/feed_wizard_store';
import { saveFeed } from '../api/save_feed';

/**
 * Step 4 component configuration from PHP.
 */
export interface Step4Config {
  editFeedId: number | null;
  feedTitle: string;
  rssUrl: string;
  articleSection: string;
  filterTags: string;
  feedText: string;
  langId: number | null;
  options: Partial<FeedOptions>;
  languages: Array<{ id: number; name: string }>;
}

/**
 * Step 4 component data interface.
 */
export interface FeedWizardStep4Data {
  // Configuration
  config: Step4Config;
  languages: Array<{ id: number; name: string }>;

  // Form data
  languageId: string;
  feedName: string;
  sourceUri: string;
  articleSection: string;
  filterTags: string;

  // Options
  editText: boolean;
  autoUpdateEnabled: boolean;
  autoUpdateInterval: string;
  autoUpdateUnit: string;
  maxLinksEnabled: boolean;
  maxLinks: string;
  maxTextsEnabled: boolean;
  maxTexts: string;
  charsetEnabled: boolean;
  charset: string;
  tagEnabled: boolean;
  tag: string;

  // Save state
  saving: boolean;
  saveError: string;

  // Computed
  readonly store: FeedWizardStoreState;
  readonly isEditMode: boolean;
  readonly submitLabel: string;

  // Lifecycle
  init(): void;

  // Actions
  toggleOption(option: string): void;
  buildOptionsString(): string;
  goBack(): void;
  hasSaveError(): boolean;
  handleSubmit(event: Event): void;
  cancel(): void;
}

/**
 * Read configuration from JSON script tag.
 */
function readConfig(): Step4Config {
  const configEl = document.getElementById('wizard-step4-config');
  if (!configEl) {
    return {
      editFeedId: null,
      feedTitle: '',
      rssUrl: '',
      articleSection: '',
      filterTags: '',
      feedText: '',
      langId: null,
      options: {},
      languages: []
    };
  }

  try {
    return JSON.parse(configEl.textContent || '{}');
  } catch {
    console.error('Failed to parse wizard step 4 config');
    return {
      editFeedId: null,
      feedTitle: '',
      rssUrl: '',
      articleSection: '',
      filterTags: '',
      feedText: '',
      langId: null,
      options: {},
      languages: []
    };
  }
}

/**
 * Feed wizard step 4 component factory.
 */
export function feedWizardStep4Data(): FeedWizardStep4Data {
  const config = readConfig();

  return {
    // Configuration
    config,
    languages: config.languages || [],

    // Form data - initialized from config
    languageId: config.langId?.toString() || '',
    feedName: config.feedTitle || '',
    sourceUri: config.rssUrl || '',
    articleSection: config.articleSection || '',
    filterTags: config.filterTags || '',

    // Options - initialized from config
    editText: config.options?.editText || false,
    autoUpdateEnabled: config.options?.autoUpdate?.enabled || false,
    autoUpdateInterval: config.options?.autoUpdate?.interval?.toString() || '',
    autoUpdateUnit: config.options?.autoUpdate?.unit || 'h',
    maxLinksEnabled: config.options?.maxLinks?.enabled || false,
    maxLinks: config.options?.maxLinks?.value?.toString() || '',
    maxTextsEnabled: config.options?.maxTexts?.enabled || false,
    maxTexts: config.options?.maxTexts?.value?.toString() || '',
    charsetEnabled: config.options?.charset?.enabled || false,
    charset: config.options?.charset?.value || '',
    tagEnabled: config.options?.tag?.enabled || false,
    tag: config.options?.tag?.value || '',

    saving: false,
    saveError: '',

    get store(): FeedWizardStoreState {
      return getFeedWizardStore();
    },

    get isEditMode(): boolean {
      return this.config.editFeedId !== null;
    },

    get submitLabel(): string {
      return this.isEditMode ? 'Update' : 'Save';
    },

    init(): void {
      // Configure store
      this.store.configure({
        step: 4,
        rssUrl: this.config.rssUrl,
        feedTitle: this.config.feedTitle,
        feedText: this.config.feedText,
        editFeedId: this.config.editFeedId,
        options: {
          languageId: config.langId,
          editText: this.editText,
          autoUpdate: {
            enabled: this.autoUpdateEnabled,
            interval: this.autoUpdateInterval ? parseInt(this.autoUpdateInterval, 10) : null,
            unit: this.autoUpdateUnit as 'h' | 'd' | 'w'
          },
          maxLinks: {
            enabled: this.maxLinksEnabled,
            value: this.maxLinks ? parseInt(this.maxLinks, 10) : null
          },
          maxTexts: {
            enabled: this.maxTextsEnabled,
            value: this.maxTexts ? parseInt(this.maxTexts, 10) : null
          },
          charset: {
            enabled: this.charsetEnabled,
            value: this.charset
          },
          tag: {
            enabled: this.tagEnabled,
            value: this.tag
          }
        }
      });
    },

    toggleOption(option: string): void {
      switch (option) {
        case 'autoUpdate':
          // Enable/disable associated inputs based on checkbox
          break;
        case 'maxLinks':
          break;
        case 'maxTexts':
          break;
        case 'charset':
          break;
        case 'tag':
          break;
      }
    },

    buildOptionsString(): string {
      const parts: string[] = [];

      if (this.editText) {
        parts.push('edit_text=1');
      }

      if (this.autoUpdateEnabled && this.autoUpdateInterval) {
        parts.push(`autoupdate=${this.autoUpdateInterval}${this.autoUpdateUnit}`);
      }

      if (this.maxLinksEnabled && this.maxLinks) {
        parts.push(`max_links=${this.maxLinks}`);
      }

      if (this.maxTextsEnabled && this.maxTexts) {
        parts.push(`max_texts=${this.maxTexts}`);
      }

      if (this.charsetEnabled && this.charset) {
        parts.push(`charset=${this.charset}`);
      }

      if (this.tagEnabled && this.tag) {
        parts.push(`tag=${this.tag}`);
      }

      // Add article source if set
      if (this.config.feedText) {
        parts.push(`article_source=${this.config.feedText}`);
      }

      return parts.join(',');
    },

    goBack(): void {
      const optionsStr = this.buildOptionsString();
      const url = `/feeds/wizard?step=3&NfOptions=${encodeURIComponent(optionsStr)}` +
        `&NfLgID=${encodeURIComponent(this.languageId)}` +
        `&NfName=${encodeURIComponent(this.feedName)}`;
      window.location.href = url;
    },

    hasSaveError(): boolean {
      return this.saveError !== '';
    },

    /**
     * Save the finished feed through the API.
     *
     * This form used to post itself to /feeds/edit. That route stopped saving
     * anything when the server-rendered feeds list was retired in favour of
     * the manager SPA — it 302s to /feeds/manage — so finishing the wizard
     * silently discarded the feed. Saving through the API both fixes that and
     * puts the submitted language behind the ownership check the controller
     * never had (#262).
     *
     * @param event Submit event
     */
    handleSubmit(event: Event): void {
      event.preventDefault();
      if (this.saving) return;

      this.saveError = '';
      this.saving = true;

      const data = {
        langId: Number(this.languageId) || 0,
        name: this.feedName,
        sourceUri: this.sourceUri,
        articleSectionTags: this.articleSection,
        filterTags: this.filterTags,
        options: this.buildOptionsString()
      };

      void saveFeed(data, this.config.editFeedId).then((result) => {
        if (result.feedId === null) {
          this.saveError = result.error;
          this.saving = false;
          return;
        }
        this.store.reset();
        window.location.href = '/feeds/manage';
      });
    },

    cancel(): void {
      window.location.href = '/feeds/edit?del_wiz=1';
    }
  };
}

/**
 * Initialize the step 4 Alpine component.
 */
export function initFeedWizardStep4Alpine(): void {
  Alpine.data('feedWizardStep4', feedWizardStep4Data);
}

// Register immediately
initFeedWizardStep4Alpine();

// Expose for global access
declare global {
  interface Window {
    feedWizardStep4Data: typeof feedWizardStep4Data;
  }
}

window.feedWizardStep4Data = feedWizardStep4Data;
