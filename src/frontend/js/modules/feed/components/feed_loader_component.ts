/**
 * Feed Loader Alpine Component
 *
 * Handles batch feed loading via AJAX with status updates,
 * replacing inline event handlers with Alpine.js reactive patterns.
 *
 * @license Unlicense
 * @since   3.0.0
 */

import Alpine from 'alpinejs';

/**
 * Configuration for a single feed to load.
 */
export interface FeedLoadConfig {
  id: number;
  name: string;
  sourceUri: string;
  options: string;
}

/**
 * Configuration passed from PHP via JSON.
 */
export interface FeedLoaderConfig {
  feeds: FeedLoadConfig[];
  redirectUrl: string;
}

/**
 * Status for each feed being loaded.
 */
export type FeedStatus = 'waiting' | 'loading' | 'success' | 'error';

/**
 * Feed loader Alpine component data interface.
 */
export interface FeedLoaderData {
  // Config
  feeds: FeedLoadConfig[];
  redirectUrl: string;

  // State
  feedStatuses: Record<number, FeedStatus>;
  feedMessages: Record<number, string>;
  loadedCount: number;
  isComplete: boolean;

  // Computed
  totalCount: number;

  // Methods
  init(): void;
  loadAllFeeds(): Promise<void>;
  loadSingleFeed(feed: FeedLoadConfig): Promise<void>;
  getStatusClass(feedId: number): string;
  handleContinue(): void;
}

/**
 * Create the feed loader Alpine component.
 *
 * @param config - Initial configuration from PHP
 * @returns Alpine component data object
 */
export function feedLoaderData(config: FeedLoaderConfig = { feeds: [], redirectUrl: '/feeds' }): FeedLoaderData {
  return {
    feeds: config.feeds,
    redirectUrl: config.redirectUrl,

    feedStatuses: {},
    feedMessages: {},
    loadedCount: 0,
    isComplete: false,

    get totalCount(): number {
      return this.feeds.length;
    },

    /**
     * Initialize the component and start loading feeds.
     * Reads config from JSON script tag if available.
     */
    init(): void {
      const configEl = document.getElementById('feed-loader-config');
      if (configEl) {
        try {
          const jsonConfig = JSON.parse(configEl.textContent || '{}') as FeedLoaderConfig;
          this.feeds = jsonConfig.feeds ?? this.feeds;
          this.redirectUrl = jsonConfig.redirectUrl ?? this.redirectUrl;
        } catch {
          // Invalid JSON, use defaults
        }
      }

      // Initialize status for each feed
      for (const feed of this.feeds) {
        this.feedStatuses[feed.id] = 'waiting';
        this.feedMessages[feed.id] = `${feed.name}: waiting`;
      }

      // Start loading feeds if there are any
      if (this.feeds.length > 0) {
        this.loadAllFeeds();
      } else {
        // No feeds to load, redirect immediately
        window.location.replace(this.redirectUrl);
      }
    },

    /**
     * Load all feeds in parallel and redirect when complete.
     */
    async loadAllFeeds(): Promise<void> {
      const promises = this.feeds.map(feed => this.loadSingleFeed(feed));

      try {
        await Promise.all(promises);
      } catch (error) {
        console.error('Some feeds failed to load:', error);
      }

      this.isComplete = true;
      // Auto-redirect after all feeds are loaded
      window.location.replace(this.redirectUrl);
    },

    /**
     * Load a single feed via AJAX.
     */
    async loadSingleFeed(feed: FeedLoadConfig): Promise<void> {
      this.feedStatuses[feed.id] = 'loading';
      this.feedMessages[feed.id] = `${feed.name}: loading`;

      try {
        const formData = new FormData();
        formData.append('name', feed.name);
        formData.append('source_uri', feed.sourceUri);
        formData.append('options', feed.options);

        const response = await fetch(`/api/v1/feeds/${feed.id}/load`, {
          method: 'POST',
          body: formData
        });

        const data = await response.json();

        if (data.error) {
          this.feedStatuses[feed.id] = 'error';
          this.feedMessages[feed.id] = data.error;
        } else {
          this.feedStatuses[feed.id] = 'success';
          this.feedMessages[feed.id] = data.message;
          this.loadedCount++;
        }
      } catch (error) {
        console.error(`Failed to load feed ${feed.id}:`, error);
        this.feedStatuses[feed.id] = 'error';
        this.feedMessages[feed.id] = `Error loading feed: ${feed.name}`;
      }
    },

    /**
     * Get CSS class for feed status display.
     */
    getStatusClass(feedId: number): string {
      const status = this.feedStatuses[feedId];
      if (status === 'error') return 'notification is-danger';
      return 'notification is-info';
    },

    /**
     * Handle continue button click.
     */
    handleContinue(): void {
      window.location.replace(this.redirectUrl);
    }
  };
}

/**
 * Initialize the feed loader Alpine component.
 */
export function initFeedLoaderAlpine(): void {
  Alpine.data('feedLoader', feedLoaderData);
}

// Register immediately (before Alpine.start())
initFeedLoaderAlpine();

// Export to window for backward compatibility
declare global {
  interface Window {
    feedLoaderData: typeof feedLoaderData;
  }
}

window.feedLoaderData = feedLoaderData;
