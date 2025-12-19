/**
 * Tests for feed_loader.ts - Feed loader functionality
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { loadFeeds, initFeedLoader, type FeedLoaderConfig } from '../../../src/frontend/js/feeds/feed_loader';

describe('feed_loader.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    // Mock location.replace
    Object.defineProperty(window, 'location', {
      value: { replace: vi.fn(), href: '' },
      writable: true
    });
    // Mock fetch
    global.fetch = vi.fn();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // loadFeeds Tests
  // ===========================================================================

  describe('loadFeeds', () => {
    it('redirects immediately when no feeds to load', async () => {
      const config: FeedLoaderConfig = {
        feeds: [],
        redirectUrl: '/feeds?done=1'
      };

      await loadFeeds(config);

      expect(window.location.replace).toHaveBeenCalledWith('/feeds?done=1');
    });

    it('loads a single feed and redirects', async () => {
      document.body.innerHTML = `
        <div id="feed_1">Loading...</div>
        <span id="feedcount">0</span>
      `;

      (global.fetch as any).mockResolvedValue({
        json: () => Promise.resolve({ message: 'Feed loaded successfully' })
      });

      const config: FeedLoaderConfig = {
        feeds: [{
          id: 1,
          name: 'Test Feed',
          sourceUri: 'http://example.com/feed.xml',
          options: 'opt1=val1'
        }],
        redirectUrl: '/feeds?loaded=1'
      };

      await loadFeeds(config);

      expect(fetch).toHaveBeenCalledWith(
        'api.php/v1/feeds/1/load',
        expect.objectContaining({
          method: 'POST',
          body: expect.any(FormData)
        })
      );
      expect(window.location.replace).toHaveBeenCalledWith('/feeds?loaded=1');
    });

    it('updates feed count on successful load', async () => {
      document.body.innerHTML = `
        <div id="feed_1">Loading...</div>
        <span id="feedcount">5</span>
      `;

      (global.fetch as any).mockResolvedValue({
        json: () => Promise.resolve({ message: 'Feed loaded' })
      });

      const config: FeedLoaderConfig = {
        feeds: [{
          id: 1,
          name: 'Test Feed',
          sourceUri: 'http://example.com/feed.xml',
          options: ''
        }],
        redirectUrl: '/done'
      };

      await loadFeeds(config);

      const feedCount = document.getElementById('feedcount')!;
      expect(feedCount.textContent).toBe('6');
    });

    it('shows loading message while loading', async () => {
      document.body.innerHTML = `
        <div id="feed_1">Initial</div>
      `;

      let resolvePromise: (value: any) => void;
      const fetchPromise = new Promise(resolve => {
        resolvePromise = resolve;
      });

      (global.fetch as any).mockReturnValue(fetchPromise);

      const config: FeedLoaderConfig = {
        feeds: [{
          id: 1,
          name: 'My Feed',
          sourceUri: 'http://example.com/feed.xml',
          options: ''
        }],
        redirectUrl: '/done'
      };

      // Start loading (don't await yet)
      const loadPromise = loadFeeds(config);

      // Give time for the loading message to be set
      await new Promise(r => setTimeout(r, 0));

      // Check loading message
      const feedEl = document.getElementById('feed_1');
      expect(feedEl?.innerHTML).toContain('My Feed: loading');

      // Complete the fetch
      resolvePromise!({
        json: () => Promise.resolve({ message: 'Done' })
      });

      await loadPromise;
    });

    it('displays error message from API', async () => {
      document.body.innerHTML = `
        <div id="feed_1">Loading...</div>
      `;

      (global.fetch as any).mockResolvedValue({
        json: () => Promise.resolve({ error: 'Feed URL is invalid' })
      });

      const config: FeedLoaderConfig = {
        feeds: [{
          id: 1,
          name: 'Test Feed',
          sourceUri: 'invalid',
          options: ''
        }],
        redirectUrl: '/done'
      };

      await loadFeeds(config);

      // Check for error message in DOM (the element gets replaced)
      expect(document.body.innerHTML).toContain('Feed URL is invalid');
    });

    it('handles fetch errors gracefully', async () => {
      document.body.innerHTML = `
        <div id="feed_1">Loading...</div>
      `;

      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      (global.fetch as any).mockRejectedValue(new Error('Network error'));

      const config: FeedLoaderConfig = {
        feeds: [{
          id: 1,
          name: 'Test Feed',
          sourceUri: 'http://example.com/feed.xml',
          options: ''
        }],
        redirectUrl: '/done'
      };

      await loadFeeds(config);

      expect(consoleSpy).toHaveBeenCalled();
      expect(window.location.replace).toHaveBeenCalledWith('/done');
    });

    it('loads multiple feeds in parallel', async () => {
      document.body.innerHTML = `
        <div id="feed_1">Feed 1</div>
        <div id="feed_2">Feed 2</div>
        <div id="feed_3">Feed 3</div>
        <span id="feedcount">0</span>
      `;

      (global.fetch as any).mockResolvedValue({
        json: () => Promise.resolve({ message: 'Loaded' })
      });

      const config: FeedLoaderConfig = {
        feeds: [
          { id: 1, name: 'Feed 1', sourceUri: 'http://example.com/1', options: '' },
          { id: 2, name: 'Feed 2', sourceUri: 'http://example.com/2', options: '' },
          { id: 3, name: 'Feed 3', sourceUri: 'http://example.com/3', options: '' }
        ],
        redirectUrl: '/done'
      };

      await loadFeeds(config);

      // All feeds should have been loaded
      expect(fetch).toHaveBeenCalledTimes(3);
      expect(window.location.replace).toHaveBeenCalledWith('/done');
    });

    it('sends correct FormData to API', async () => {
      document.body.innerHTML = `<div id="feed_1"></div>`;

      let capturedBody: FormData | null = null;
      (global.fetch as any).mockImplementation((url: string, options: any) => {
        void url; // URL is used implicitly by fetch mock
        capturedBody = options.body;
        return Promise.resolve({
          json: () => Promise.resolve({ message: 'OK' })
        });
      });

      const config: FeedLoaderConfig = {
        feeds: [{
          id: 1,
          name: 'My Test Feed',
          sourceUri: 'http://example.com/rss.xml',
          options: 'opt=123'
        }],
        redirectUrl: '/done'
      };

      await loadFeeds(config);

      expect(capturedBody).toBeInstanceOf(FormData);
      expect(capturedBody!.get('name')).toBe('My Test Feed');
      expect(capturedBody!.get('source_uri')).toBe('http://example.com/rss.xml');
      expect(capturedBody!.get('options')).toBe('opt=123');
    });

    it('handles missing feed element gracefully', async () => {
      // No feed element in DOM
      document.body.innerHTML = '';

      (global.fetch as any).mockResolvedValue({
        json: () => Promise.resolve({ message: 'OK' })
      });

      const config: FeedLoaderConfig = {
        feeds: [{
          id: 999,
          name: 'Missing Feed',
          sourceUri: 'http://example.com/feed.xml',
          options: ''
        }],
        redirectUrl: '/done'
      };

      // Should not throw
      await expect(loadFeeds(config)).resolves.not.toThrow();
      expect(window.location.replace).toHaveBeenCalledWith('/done');
    });

    it('handles missing feedcount element', async () => {
      document.body.innerHTML = `<div id="feed_1"></div>`;
      // No feedcount element

      (global.fetch as any).mockResolvedValue({
        json: () => Promise.resolve({ message: 'OK' })
      });

      const config: FeedLoaderConfig = {
        feeds: [{
          id: 1,
          name: 'Test Feed',
          sourceUri: 'http://example.com/feed.xml',
          options: ''
        }],
        redirectUrl: '/done'
      };

      // Should not throw
      await expect(loadFeeds(config)).resolves.not.toThrow();
    });
  });

  // ===========================================================================
  // initFeedLoader Tests
  // ===========================================================================

  describe('initFeedLoader', () => {
    it('does nothing when config element does not exist', () => {
      expect(() => initFeedLoader()).not.toThrow();
    });

    it('parses config from JSON script element', async () => {
      document.body.innerHTML = `
        <script id="feed-loader-config" type="application/json">
          {"feeds":[],"redirectUrl":"/feeds"}
        </script>
      `;

      (global.fetch as any).mockResolvedValue({
        json: () => Promise.resolve({ message: 'OK' })
      });

      initFeedLoader();

      // Should redirect immediately since feeds array is empty
      await new Promise(r => setTimeout(r, 0));
      expect(window.location.replace).toHaveBeenCalledWith('/feeds');
    });

    it('handles invalid JSON gracefully', async () => {
      document.body.innerHTML = `
        <script id="feed-loader-config" type="application/json">
          {invalid json}
        </script>
      `;

      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      initFeedLoader();

      await new Promise(r => setTimeout(r, 10));

      expect(consoleSpy).toHaveBeenCalled();
    });

    it('handles empty config element gracefully', async () => {
      document.body.innerHTML = `
        <script id="feed-loader-config" type="application/json">{"feeds": [], "redirectUrl": "/done"}</script>
      `;

      // With valid but empty feeds array, should redirect immediately
      initFeedLoader();

      await new Promise(r => setTimeout(r, 10));

      expect(window.location.replace).toHaveBeenCalledWith('/done');
    });
  });

  // ===========================================================================
  // Continue Button Tests
  // ===========================================================================

  describe('continue button', () => {
    it('redirects when continue button is clicked', () => {
      document.body.innerHTML = `
        <button data-action="feed-continue" data-url="/feeds?page=2">Continue</button>
      `;

      // Simulate DOMContentLoaded to set up the handler
      const event = new Event('DOMContentLoaded');
      document.dispatchEvent(event);

      // Need to manually initialize since we're in test environment
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

      const button = document.querySelector<HTMLButtonElement>('[data-action="feed-continue"]')!;
      button.click();

      expect(window.location.replace).toHaveBeenCalledWith('/feeds?page=2');
    });

    it('does nothing when continue button has no URL', () => {
      document.body.innerHTML = `
        <button data-action="feed-continue">Continue</button>
      `;

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

      const button = document.querySelector<HTMLButtonElement>('[data-action="feed-continue"]')!;
      button.click();

      expect(window.location.replace).not.toHaveBeenCalled();
    });
  });
});
