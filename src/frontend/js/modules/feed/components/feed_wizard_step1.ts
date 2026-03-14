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
 * Hidden form data for curated feed submission.
 */
interface CuratedFormData {
  NfLgID: string;
  NfName: string;
  NfSourceURI: string;
  NfArticleSectionTags: string;
  NfFilterTags: string;
  NfOptions: string;
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
  languages: Array<{ id: number; name: string }>;
  curatedFeeds: CuratedFeedGroup[];
  readonly filteredCuratedFeeds: CuratedFeedGroup[];
  curatedFormData: CuratedFormData;

  // Lifecycle
  init(): void;

  // Actions
  cancel(): void;
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

    curatedFormData: {
      NfLgID: '',
      NfName: '',
      NfSourceURI: '',
      NfArticleSectionTags: '',
      NfFilterTags: '',
      NfOptions: ''
    },

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

    addCuratedFeed(source: CuratedSource): void {
      // Populate hidden form and submit using current language from navbar
      this.curatedFormData = {
        NfLgID: String(this.currentLanguageId),
        NfName: source.name,
        NfSourceURI: source.url,
        NfArticleSectionTags: source.articleSectionTags,
        NfFilterTags: source.filterTags,
        NfOptions: source.options
      };

      // Need to wait one tick for Alpine to update the hidden inputs
      this.$nextTick(() => {
        const form = document.getElementById('curated-feed-form') as HTMLFormElement | null;
        if (form) {
          form.submit();
        }
      });
    }
  } as FeedWizardStep1Data & { $nextTick: (fn: () => void) => void };
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
