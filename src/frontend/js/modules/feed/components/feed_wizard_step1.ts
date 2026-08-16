/**
 * Feed Wizard Step 1 Component - Choose How to Add a Feed.
 *
 * Alpine.js component for the feed wizard step 1 with three paths:
 * 1. Browse curated sources
 * 2. Enter feed URL (guided wizard)
 * 3. Manual setup
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import type { FeedWizardStoreState } from '../types/feed_wizard_types';
import { getFeedWizardStore } from '../stores/feed_wizard_store';
import { saveFeed } from '../api/save_feed';

/**
 * A curated feed source entry.
 */
interface CuratedSource {
  name: string;
  url: string;
  articleSectionTags: string;
  filterTags: string;
  options: string;
  category: string;
  level: string;
}

/**
 * A curated feed language group.
 */
interface CuratedFeedGroup {
  language: string;
  languageName: string;
  sources: CuratedSource[];
}

/**
 * Step 1 component configuration from PHP.
 */
export interface Step1Config {
  rssUrl: string;
  hasError: boolean;
  editFeedId: number | null;
  languages: Array<{ id: number; name: string }>;
  curatedFeeds: CuratedFeedGroup[];
  currentLanguageId: number;
  currentLanguageName: string;
}

/**
 * Step 1 component data interface.
 */
export interface FeedWizardStep1Data {
  config: Step1Config;

  // Tab state
  activeTab: 'browse' | 'wizard' | 'manual';

  // Wizard tab
  rssUrl: string;
  readonly store: FeedWizardStoreState;
  readonly isValidUrl: boolean;

  // Browse tab
  browseLanguageFilter: string;
  browseSearch: string;
  currentLanguageId: number;
  selectedUrls: string[];
  languages: Array<{ id: number; name: string }>;
  curatedFeeds: CuratedFeedGroup[];
  readonly filteredCuratedFeeds: CuratedFeedGroup[];

  // Save state
  saving: boolean;
  saveError: string;

  // Lifecycle
  init(): void;

  // Actions
  cancel(): void;
  hasSaveError(): boolean;
  addSelectedFeeds(): void;
  addCuratedFeed(source: CuratedSource): void;

}

/**
 * Read configuration from JSON script tag.
 */
function readConfig(): Step1Config {
  const configEl = document.getElementById('wizard-step1-config');
  const defaults: Step1Config = {
    rssUrl: '',
    hasError: false,
    editFeedId: null,
    languages: [],
    curatedFeeds: [],
    currentLanguageId: 0,
    currentLanguageName: ''
  };

  if (!configEl) {
    return defaults;
  }

  try {
    const parsed = JSON.parse(configEl.textContent || '{}');
    return { ...defaults, ...parsed };
  } catch {
    console.error('Failed to parse wizard step 1 config');
    return defaults;
  }
}

/**
 * Feed wizard step 1 component factory.
 */
export function feedWizardStep1Data(): FeedWizardStep1Data {
  const config = readConfig();

  return {
    config,

    // Tab state — default to browse if curated feeds exist, else wizard
    activeTab: config.curatedFeeds.length > 0 ? 'browse' : 'wizard',

    // Wizard tab
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

    // Browse tab — auto-preselect language filter based on current navbar language
    browseLanguageFilter: (() => {
      const name = config.currentLanguageName.toLowerCase();
      if (!name) return '';
      const match = config.curatedFeeds.find(
        g => name.includes(g.languageName.toLowerCase())
          || g.languageName.toLowerCase().includes(name)
      );
      return match?.language ?? '';
    })(),
    browseSearch: '',
    currentLanguageId: config.currentLanguageId,
    selectedUrls: [] as string[],
    languages: config.languages,
    curatedFeeds: config.curatedFeeds,

    get filteredCuratedFeeds(): CuratedFeedGroup[] {
      let groups = this.curatedFeeds;

      // Filter by language
      if (this.browseLanguageFilter) {
        groups = groups.filter(g => g.language === this.browseLanguageFilter);
      }

      // Filter by search term
      const search = this.browseSearch.toLowerCase().trim();
      if (search) {
        groups = groups
          .map(g => ({
            ...g,
            sources: g.sources.filter(
              s =>
                s.name.toLowerCase().includes(search) ||
                s.category.toLowerCase().includes(search) ||
                s.url.toLowerCase().includes(search)
            )
          }))
          .filter(g => g.sources.length > 0);
      }

      return groups;
    },

    saving: false,
    saveError: '',

    init(): void {
      // Configure store for step 1
      this.store.configure({
        step: 1,
        rssUrl: this.config.rssUrl,
        editFeedId: this.config.editFeedId
      });

      // If there was an error, switch to wizard tab
      if (this.config.hasError) {
        this.activeTab = 'wizard';
      }
    },

    cancel(): void {
      window.location.href = '/feeds/manage';
    },

    addSelectedFeeds(): void {
      if (this.selectedUrls.length === 0) return;

      // Find the first selected source object by URL
      for (const group of this.curatedFeeds) {
        const source = group.sources.find(s => this.selectedUrls.includes(s.url));
        if (source) {
          this.addCuratedFeed(source);
          return;
        }
      }
    },

    hasSaveError(): boolean {
      return this.saveError !== '';
    },

    /**
     * Add a curated source as a feed.
     *
     * This used to fill a hidden form and submit it to /feeds/new, which meant
     * waiting a tick for Alpine to write the inputs before posting. Calling the
     * API directly removes both the hidden form and that timing dependency
     * (#262). The language is the navbar's current selection, as before.
     */
    addCuratedFeed(source: CuratedSource): void {
      if (this.saving) return;

      this.saveError = '';
      this.saving = true;

      void saveFeed({
        langId: this.currentLanguageId,
        name: source.name,
        sourceUri: source.url,
        articleSectionTags: source.articleSectionTags,
        filterTags: source.filterTags,
        options: source.options
      }, null).then((result) => {
        if (result.feedId === null) {
          this.saveError = result.error;
          this.saving = false;
          return;
        }
        window.location.href = `/feeds/${result.feedId}/edit`;
      });
    }
  } as FeedWizardStep1Data;
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
