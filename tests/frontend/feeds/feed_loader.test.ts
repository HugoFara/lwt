/**
 * Tests for feed_loader_component.ts - Feed loader Alpine component
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  feedLoaderData,
  type FeedLoaderConfig
} from '../../../src/frontend/js/modules/feed/components/feed_loader_component';

describe('feed_loader_component.ts', () => {
  let originalLocation: Location;

  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    // Save original location
    originalLocation = window.location;
    // Mock location.replace
    Object.defineProperty(window, 'location', {
      value: { replace: vi.fn(), href: '' },
      writable: true,
      configurable: true
    });
    // Mock fetch
    global.fetch = vi.fn();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
    // Restore original location
    Object.defineProperty(window, 'location', {
      value: originalLocation,
      writable: true,
      configurable: true
    });
  });

  // ===========================================================================
  // feedLoaderData Factory Function Tests
  // ===========================================================================

  describe('feedLoaderData', () => {
    it('creates component with default values', () => {
      const component = feedLoaderData();

      expect(component.feeds).toEqual([]);
      expect(component.redirectUrl).toBe('/feeds');
      expect(component.loadedCount).toBe(0);
      expect(component.isComplete).toBe(false);
    });

    it('creates component with provided config values', () => {
      const config: FeedLoaderConfig = {
        feeds: [{ id: 1, name: 'Test', sourceUri: 'http://test.com', options: '' }],
        redirectUrl: '/custom/redirect'
      };

      const component = feedLoaderData(config);

      expect(component.feeds).toHaveLength(1);
      expect(component.feeds[0].name).toBe('Test');
      expect(component.redirectUrl).toBe('/custom/redirect');
    });

    it('computes totalCount correctly', () => {
      const config: FeedLoaderConfig = {
        feeds: [
          { id: 1, name: 'Feed 1', sourceUri: 'http://test.com/1', options: '' },
          { id: 2, name: 'Feed 2', sourceUri: 'http://test.com/2', options: '' },
          { id: 3, name: 'Feed 3', sourceUri: 'http://test.com/3', options: '' }
        ],
        redirectUrl: '/done'
      };

      const component = feedLoaderData(config);

      expect(component.totalCount).toBe(3);
    });
  });

  // ===========================================================================
  // init() Method Tests
  // ===========================================================================

  describe('init()', () => {
    it('reads config from JSON script tag', () => {
      document.body.innerHTML = `
        <script type="application/json" id="feed-loader-config">
          {"feeds":[{"id":1,"name":"JSON Feed","sourceUri":"http://json.com","options":""}],"redirectUrl":"/json/done"}
        </script>
      `;

      const component = feedLoaderData();
      // Prevent actual loading by mocking loadAllFeeds
      component.loadAllFeeds = vi.fn();
      component.init();

      expect(component.feeds).toHaveLength(1);
      expect(component.feeds[0].name).toBe('JSON Feed');
      expect(component.redirectUrl).toBe('/json/done');
    });

    it('keeps defaults if no JSON config element exists', () => {
      const component = feedLoaderData();
      // Mock loadAllFeeds to prevent redirect
      component.loadAllFeeds = vi.fn();
      component.init();

      expect(component.feeds).toEqual([]);
      expect(component.redirectUrl).toBe('/feeds');
    });

    it('handles invalid JSON gracefully', () => {
      document.body.innerHTML = `
        <script type="application/json" id="feed-loader-config">
          {invalid json}
        </script>
      `;

      const component = feedLoaderData({
        feeds: [{ id: 1, name: 'Original', sourceUri: 'http://orig.com', options: '' }],
        redirectUrl: '/original'
      });
      component.loadAllFeeds = vi.fn();

      expect(() => component.init()).not.toThrow();
      // Should keep original values
      expect(component.feeds).toHaveLength(1);
      expect(component.feeds[0].name).toBe('Original');
    });

    it('initializes feedStatuses and feedMessages for each feed', () => {
      const config: FeedLoaderConfig = {
        feeds: [
          { id: 1, name: 'Feed A', sourceUri: 'http://a.com', options: '' },
          { id: 2, name: 'Feed B', sourceUri: 'http://b.com', options: '' }
        ],
        redirectUrl: '/done'
      };

      const component = feedLoaderData(config);
      component.loadAllFeeds = vi.fn();
      component.init();

      expect(component.feedStatuses[1]).toBe('waiting');
      expect(component.feedStatuses[2]).toBe('waiting');
      expect(component.feedMessages[1]).toBe('Feed A: waiting');
      expect(component.feedMessages[2]).toBe('Feed B: waiting');
    });

    it('redirects immediately when no feeds to load', () => {
      const component = feedLoaderData({
        feeds: [],
        redirectUrl: '/empty/redirect'
      });
      component.init();

      expect(window.location.replace).toHaveBeenCalledWith('/empty/redirect');
    });
  });

  // ===========================================================================
  // loadSingleFeed() Tests
  // ===========================================================================

  describe('loadSingleFeed()', () => {
    it('updates status to loading when starting', async () => {
      (global.fetch as any).mockResolvedValue({
        json: () => Promise.resolve({ message: 'Feed loaded' })
      });

      const component = feedLoaderData();
      component.feedStatuses = { 1: 'waiting' };
      component.feedMessages = { 1: 'Test: waiting' };

      const loadPromise = component.loadSingleFeed({
        id: 1,
        name: 'Test Feed',
        sourceUri: 'http://test.com',
        options: ''
      });

      // Check immediately - should be loading
      expect(component.feedStatuses[1]).toBe('loading');
      expect(component.feedMessages[1]).toBe('Test Feed: loading');

      await loadPromise;
    });

    it('updates status to success on successful load', async () => {
      (global.fetch as any).mockResolvedValue({
        json: () => Promise.resolve({ message: 'Successfully loaded 5 items' })
      });

      const component = feedLoaderData();
      component.feedStatuses = { 1: 'waiting' };
      component.feedMessages = { 1: 'Test: waiting' };
      component.loadedCount = 0;

      await component.loadSingleFeed({
        id: 1,
        name: 'Test Feed',
        sourceUri: 'http://test.com',
        options: ''
      });

      expect(component.feedStatuses[1]).toBe('success');
      expect(component.feedMessages[1]).toBe('Successfully loaded 5 items');
      expect(component.loadedCount).toBe(1);
    });

    it('updates status to error when API returns error', async () => {
      (global.fetch as any).mockResolvedValue({
        json: () => Promise.resolve({ error: 'Invalid feed URL' })
      });

      const component = feedLoaderData();
      component.feedStatuses = { 1: 'waiting' };
      component.feedMessages = { 1: 'Test: waiting' };
      component.loadedCount = 0;

      await component.loadSingleFeed({
        id: 1,
        name: 'Test Feed',
        sourceUri: 'invalid',
        options: ''
      });

      expect(component.feedStatuses[1]).toBe('error');
      expect(component.feedMessages[1]).toBe('Invalid feed URL');
      expect(component.loadedCount).toBe(0); // Not incremented on error
    });

    it('handles fetch errors gracefully', async () => {
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      (global.fetch as any).mockRejectedValue(new Error('Network error'));

      const component = feedLoaderData();
      component.feedStatuses = { 1: 'waiting' };
      component.feedMessages = { 1: 'Test: waiting' };

      await component.loadSingleFeed({
        id: 1,
        name: 'Test Feed',
        sourceUri: 'http://test.com',
        options: ''
      });

      expect(component.feedStatuses[1]).toBe('error');
      expect(component.feedMessages[1]).toBe('Error loading feed: Test Feed');
      expect(consoleSpy).toHaveBeenCalled();
    });

    it('sends correct FormData to API', async () => {
      let capturedBody: FormData | null = null;
      (global.fetch as any).mockImplementation((_url: string, options: any) => {
        capturedBody = options.body;
        return Promise.resolve({
          json: () => Promise.resolve({ message: 'OK' })
        });
      });

      const component = feedLoaderData();
      component.feedStatuses = { 1: 'waiting' };
      component.feedMessages = { 1: '' };

      await component.loadSingleFeed({
        id: 1,
        name: 'My Test Feed',
        sourceUri: 'http://example.com/rss.xml',
        options: 'opt=123'
      });

      expect(capturedBody).toBeInstanceOf(FormData);
      expect(capturedBody!.get('name')).toBe('My Test Feed');
      expect(capturedBody!.get('source_uri')).toBe('http://example.com/rss.xml');
      expect(capturedBody!.get('options')).toBe('opt=123');
    });

    it('calls correct API endpoint', async () => {
      (global.fetch as any).mockResolvedValue({
        json: () => Promise.resolve({ message: 'OK' })
      });

      const component = feedLoaderData();
      component.feedStatuses = { 42: 'waiting' };
      component.feedMessages = { 42: '' };

      await component.loadSingleFeed({
        id: 42,
        name: 'Test',
        sourceUri: 'http://test.com',
        options: ''
      });

      expect(fetch).toHaveBeenCalledWith(
        '/api/v1/feeds/42/load',
        expect.objectContaining({ method: 'POST' })
      );
    });
  });

  // ===========================================================================
  // loadAllFeeds() Tests
  // ===========================================================================

  describe('loadAllFeeds()', () => {
    it('loads multiple feeds in parallel', async () => {
      (global.fetch as any).mockResolvedValue({
        json: () => Promise.resolve({ message: 'Loaded' })
      });

      const component = feedLoaderData({
        feeds: [
          { id: 1, name: 'Feed 1', sourceUri: 'http://1.com', options: '' },
          { id: 2, name: 'Feed 2', sourceUri: 'http://2.com', options: '' },
          { id: 3, name: 'Feed 3', sourceUri: 'http://3.com', options: '' }
        ],
        redirectUrl: '/done'
      });
      component.feedStatuses = { 1: 'waiting', 2: 'waiting', 3: 'waiting' };
      component.feedMessages = { 1: '', 2: '', 3: '' };

      await component.loadAllFeeds();

      expect(fetch).toHaveBeenCalledTimes(3);
      expect(component.isComplete).toBe(true);
      expect(window.location.replace).toHaveBeenCalledWith('/done');
    });

    it('redirects after all feeds are loaded', async () => {
      (global.fetch as any).mockResolvedValue({
        json: () => Promise.resolve({ message: 'OK' })
      });

      const component = feedLoaderData({
        feeds: [{ id: 1, name: 'Test', sourceUri: 'http://test.com', options: '' }],
        redirectUrl: '/feeds?success=1'
      });
      component.feedStatuses = { 1: 'waiting' };
      component.feedMessages = { 1: '' };

      await component.loadAllFeeds();

      expect(window.location.replace).toHaveBeenCalledWith('/feeds?success=1');
    });

    it('still redirects even if some feeds fail', async () => {
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      (global.fetch as any)
        .mockResolvedValueOnce({ json: () => Promise.resolve({ message: 'OK' }) })
        .mockRejectedValueOnce(new Error('Failed'));

      const component = feedLoaderData({
        feeds: [
          { id: 1, name: 'Feed 1', sourceUri: 'http://1.com', options: '' },
          { id: 2, name: 'Feed 2', sourceUri: 'http://2.com', options: '' }
        ],
        redirectUrl: '/done'
      });
      component.feedStatuses = { 1: 'waiting', 2: 'waiting' };
      component.feedMessages = { 1: '', 2: '' };

      await component.loadAllFeeds();

      expect(consoleSpy).toHaveBeenCalled();
      expect(window.location.replace).toHaveBeenCalledWith('/done');
    });
  });

  // ===========================================================================
  // getStatusClass() Tests
  // ===========================================================================

  describe('getStatusClass()', () => {
    it('returns "notification is-danger" for error status', () => {
      const component = feedLoaderData();
      component.feedStatuses = { 1: 'error' };

      expect(component.getStatusClass(1)).toBe('notification is-danger');
    });

    it('returns "notification is-info" for other statuses', () => {
      const component = feedLoaderData();
      component.feedStatuses = {
        1: 'waiting',
        2: 'loading',
        3: 'success'
      };

      expect(component.getStatusClass(1)).toBe('notification is-info');
      expect(component.getStatusClass(2)).toBe('notification is-info');
      expect(component.getStatusClass(3)).toBe('notification is-info');
    });
  });

  // ===========================================================================
  // handleContinue() Tests
  // ===========================================================================

  describe('handleContinue()', () => {
    it('navigates to redirect URL', () => {
      const component = feedLoaderData({
        feeds: [],
        redirectUrl: '/feeds?page=2'
      });

      component.handleContinue();

      expect(window.location.replace).toHaveBeenCalledWith('/feeds?page=2');
    });

    it('uses default redirect URL', () => {
      const component = feedLoaderData();

      component.handleContinue();

      expect(window.location.replace).toHaveBeenCalledWith('/feeds');
    });
  });
});
