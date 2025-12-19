/**
 * Feed loader functionality.
 *
 * Handles loading/updating feeds via AJAX, replacing the PHP-generated
 * JavaScript with a TypeScript implementation.
 *
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

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
 * Load a single feed via AJAX.
 *
 * @param feed - Feed configuration
 * @returns Promise that resolves when the feed is loaded
 */
async function loadSingleFeed(feed: FeedLoadConfig): Promise<void> {
  const feedElement = document.getElementById(`feed_${feed.id}`);
  if (feedElement) {
    feedElement.outerHTML = `<div id="feed_${feed.id}" class="msgblue"><p>${feed.name}: loading</p></div>`;
  }

  try {
    const formData = new FormData();
    formData.append('name', feed.name);
    formData.append('source_uri', feed.sourceUri);
    formData.append('options', feed.options);

    const response = await fetch(`api.php/v1/feeds/${feed.id}/load`, {
      method: 'POST',
      body: formData
    });

    const data = await response.json();
    const updatedElement = document.getElementById(`feed_${feed.id}`);

    if (updatedElement) {
      if (data.error) {
        updatedElement.outerHTML = `<div class="red"><p>${data.error}</p></div>`;
      } else {
        updatedElement.outerHTML = `<div class="msgblue"><p>${data.message}</p></div>`;
      }
    }

    // Update feed count
    const feedCountElement = document.getElementById('feedcount');
    if (feedCountElement) {
      const currentCount = parseInt(feedCountElement.textContent || '0', 10);
      feedCountElement.textContent = String(currentCount + 1);
    }
  } catch (error) {
    console.error(`Failed to load feed ${feed.id}:`, error);
    const updatedElement = document.getElementById(`feed_${feed.id}`);
    if (updatedElement) {
      updatedElement.outerHTML = `<div class="red"><p>Error loading feed: ${feed.name}</p></div>`;
    }
  }
}

/**
 * Load multiple feeds and redirect when complete.
 *
 * @param config - Feed loader configuration
 */
export async function loadFeeds(config: FeedLoaderConfig): Promise<void> {
  if (config.feeds.length === 0) {
    window.location.replace(config.redirectUrl);
    return;
  }

  // Load all feeds in parallel
  const promises = config.feeds.map(feed => loadSingleFeed(feed));

  try {
    await Promise.all(promises);
  } catch (error) {
    console.error('Some feeds failed to load:', error);
  }

  // Redirect after all complete
  window.location.replace(config.redirectUrl);
}

/**
 * Initialize feed loader from JSON config element.
 */
export function initFeedLoader(): void {
  const configEl = document.getElementById('feed-loader-config');
  if (!configEl) return;

  let config: FeedLoaderConfig;
  try {
    config = JSON.parse(configEl.textContent || '{}');
  } catch (e) {
    console.error('Failed to parse feed-loader-config:', e);
    return;
  }

  loadFeeds(config);
}

/**
 * Initialize continue button event handler.
 */
function initContinueButton(): void {
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement;
    if (target.matches('[data-action="feed-continue"]')) {
      e.preventDefault();
      const url = target.dataset.url;
      if (url) {
        window.location.replace(url);
      }
    }
  });
}

// Auto-initialize on DOM ready if config element is present
document.addEventListener('DOMContentLoaded', () => {
  initContinueButton();
  if (document.getElementById('feed-loader-config')) {
    initFeedLoader();
  }
});

// Export to window for potential external use
declare global {
  interface Window {
    loadFeeds: typeof loadFeeds;
    initFeedLoader: typeof initFeedLoader;
  }
}

window.loadFeeds = loadFeeds;
window.initFeedLoader = initFeedLoader;
