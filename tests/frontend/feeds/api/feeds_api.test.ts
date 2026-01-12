/**
 * Tests for feeds/api/feeds_api.ts - Feeds REST API client
 */
import { describe, it, expect, beforeEach, vi } from 'vitest';
import * as apiClient from '../../../../src/frontend/js/shared/api/client';
import {
  getFeeds,
  getFeed,
  createFeed,
  updateFeed,
  deleteFeed,
  deleteFeeds,
  loadFeed,
  getArticles,
  deleteArticles,
  importArticles,
  resetErrorArticles,
  buildOptionsString,
  parseOptionsString,
  formatAutoUpdate,
  getStatusBadgeClass,
  getStatusLabel,
  type Feed,
  type Article,
  type FeedListResponse,
  type ArticlesResponse
} from '../../../../src/frontend/js/modules/feed/api/feeds_api';

// Mock the api_client module
vi.mock('../../../../src/frontend/js/shared/api/client', () => ({
  apiGet: vi.fn(),
  apiPost: vi.fn(),
  apiPut: vi.fn(),
  apiDelete: vi.fn()
}));

describe('feeds/api/feeds_api.ts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  // ===========================================================================
  // getFeeds Tests
  // ===========================================================================

  describe('getFeeds', () => {
    it('calls apiGet with /feeds/list endpoint', async () => {
      const mockResponse = {
        data: {
          feeds: [],
          pagination: { page: 1, per_page: 50, total: 0, total_pages: 0 },
          languages: []
        } as FeedListResponse,
        error: undefined
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      await getFeeds();

      expect(apiClient.apiGet).toHaveBeenCalledWith('/feeds/list', {});
    });

    it('passes filter parameters to API', async () => {
      const mockResponse = {
        data: {
          feeds: [],
          pagination: { page: 1, per_page: 50, total: 0, total_pages: 0 },
          languages: []
        } as FeedListResponse,
        error: undefined
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      await getFeeds({ lang: 1, query: 'test', page: 2, per_page: 25, sort: 1 });

      expect(apiClient.apiGet).toHaveBeenCalledWith('/feeds/list', {
        lang: 1,
        query: 'test',
        page: 2,
        per_page: 25,
        sort: 1
      });
    });

    it('returns feed list from response', async () => {
      const mockFeed: Feed = {
        id: 1,
        name: 'Test Feed',
        sourceUri: 'https://example.com/feed.xml',
        langId: 1,
        langName: 'English',
        articleSectionTags: '//item',
        filterTags: '',
        options: {},
        optionsString: '',
        updateTimestamp: 1234567890,
        lastUpdate: '2025-01-01',
        articleCount: 10
      };
      const mockResponse = {
        data: {
          feeds: [mockFeed],
          pagination: { page: 1, per_page: 50, total: 1, total_pages: 1 },
          languages: [{ id: 1, name: 'English' }]
        } as FeedListResponse,
        error: undefined
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      const result = await getFeeds();

      expect(result.data?.feeds).toHaveLength(1);
      expect(result.data?.feeds[0].name).toBe('Test Feed');
    });

    it('handles error response', async () => {
      const mockResponse = {
        data: undefined,
        error: 'Failed to fetch feeds'
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      const result = await getFeeds();

      expect(result.error).toBe('Failed to fetch feeds');
    });
  });

  // ===========================================================================
  // getFeed Tests
  // ===========================================================================

  describe('getFeed', () => {
    it('calls apiGet with feed ID', async () => {
      const mockResponse = {
        data: { id: 1, name: 'Test Feed' } as Feed,
        error: undefined
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      await getFeed(1);

      expect(apiClient.apiGet).toHaveBeenCalledWith('/feeds/1');
    });

    it('returns feed data', async () => {
      const mockFeed: Feed = {
        id: 5,
        name: 'My Feed',
        sourceUri: 'https://example.com/rss',
        langId: 2,
        langName: 'French',
        articleSectionTags: '//entry',
        filterTags: '',
        options: {},
        optionsString: '',
        updateTimestamp: 0,
        lastUpdate: '',
        articleCount: 0
      };
      const mockResponse = {
        data: mockFeed,
        error: undefined
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      const result = await getFeed(5);

      expect(result.data?.name).toBe('My Feed');
      expect(result.data?.langId).toBe(2);
    });
  });

  // ===========================================================================
  // createFeed Tests
  // ===========================================================================

  describe('createFeed', () => {
    it('calls apiPost with /feeds endpoint', async () => {
      const mockResponse = {
        data: { success: true, feed: { id: 1 } as Feed },
        error: undefined
      };
      vi.mocked(apiClient.apiPost).mockResolvedValue(mockResponse);

      await createFeed({
        langId: 1,
        name: 'New Feed',
        sourceUri: 'https://example.com/feed.xml'
      });

      expect(apiClient.apiPost).toHaveBeenCalledWith('/feeds', {
        langId: 1,
        name: 'New Feed',
        sourceUri: 'https://example.com/feed.xml'
      });
    });

    it('returns created feed on success', async () => {
      const mockFeed: Feed = {
        id: 10,
        name: 'Created Feed',
        sourceUri: 'https://example.com/new.xml',
        langId: 1,
        langName: 'English',
        articleSectionTags: '',
        filterTags: '',
        options: {},
        optionsString: '',
        updateTimestamp: 0,
        lastUpdate: '',
        articleCount: 0
      };
      const mockResponse = {
        data: { success: true, feed: mockFeed },
        error: undefined
      };
      vi.mocked(apiClient.apiPost).mockResolvedValue(mockResponse);

      const result = await createFeed({
        langId: 1,
        name: 'Created Feed',
        sourceUri: 'https://example.com/new.xml'
      });

      expect(result.data?.success).toBe(true);
      expect(result.data?.feed.id).toBe(10);
    });

    it('includes optional fields', async () => {
      const mockResponse = {
        data: { success: true, feed: { id: 1 } as Feed },
        error: undefined
      };
      vi.mocked(apiClient.apiPost).mockResolvedValue(mockResponse);

      await createFeed({
        langId: 1,
        name: 'Feed with Options',
        sourceUri: 'https://example.com/feed.xml',
        articleSectionTags: '//article',
        filterTags: '//h1',
        options: 'edit_text=1,autoupdate=1h'
      });

      expect(apiClient.apiPost).toHaveBeenCalledWith('/feeds', {
        langId: 1,
        name: 'Feed with Options',
        sourceUri: 'https://example.com/feed.xml',
        articleSectionTags: '//article',
        filterTags: '//h1',
        options: 'edit_text=1,autoupdate=1h'
      });
    });
  });

  // ===========================================================================
  // updateFeed Tests
  // ===========================================================================

  describe('updateFeed', () => {
    it('calls apiPut with feed ID', async () => {
      const mockResponse = {
        data: { success: true, feed: { id: 1 } as Feed },
        error: undefined
      };
      vi.mocked(apiClient.apiPut).mockResolvedValue(mockResponse);

      await updateFeed(1, { name: 'Updated Name' });

      expect(apiClient.apiPut).toHaveBeenCalledWith('/feeds/1', { name: 'Updated Name' });
    });

    it('returns updated feed on success', async () => {
      const mockFeed: Feed = {
        id: 1,
        name: 'Updated Feed',
        sourceUri: 'https://example.com/feed.xml',
        langId: 1,
        langName: 'English',
        articleSectionTags: '',
        filterTags: '',
        options: {},
        optionsString: '',
        updateTimestamp: 0,
        lastUpdate: '',
        articleCount: 0
      };
      const mockResponse = {
        data: { success: true, feed: mockFeed },
        error: undefined
      };
      vi.mocked(apiClient.apiPut).mockResolvedValue(mockResponse);

      const result = await updateFeed(1, { name: 'Updated Feed' });

      expect(result.data?.success).toBe(true);
      expect(result.data?.feed.name).toBe('Updated Feed');
    });
  });

  // ===========================================================================
  // deleteFeed Tests
  // ===========================================================================

  describe('deleteFeed', () => {
    it('calls apiDelete with feed ID', async () => {
      const mockResponse = {
        data: { success: true, deleted: 1 },
        error: undefined
      };
      vi.mocked(apiClient.apiDelete).mockResolvedValue(mockResponse);

      await deleteFeed(5);

      expect(apiClient.apiDelete).toHaveBeenCalledWith('/feeds/5');
    });

    it('returns success status', async () => {
      const mockResponse = {
        data: { success: true, deleted: 1 },
        error: undefined
      };
      vi.mocked(apiClient.apiDelete).mockResolvedValue(mockResponse);

      const result = await deleteFeed(5);

      expect(result.data?.success).toBe(true);
      expect(result.data?.deleted).toBe(1);
    });
  });

  // ===========================================================================
  // deleteFeeds Tests
  // ===========================================================================

  describe('deleteFeeds', () => {
    it('calls apiDelete with /feeds endpoint', async () => {
      const mockResponse = {
        data: { success: true, deleted: 3 },
        error: undefined
      };
      vi.mocked(apiClient.apiDelete).mockResolvedValue(mockResponse);

      await deleteFeeds([1, 2, 3]);

      expect(apiClient.apiDelete).toHaveBeenCalledWith('/feeds');
    });
  });

  // ===========================================================================
  // loadFeed Tests
  // ===========================================================================

  describe('loadFeed', () => {
    it('calls apiPost with feed load endpoint', async () => {
      const mockResponse = {
        data: { success: true, message: 'Loaded 10 articles', imported: 10, duplicates: 2 },
        error: undefined
      };
      vi.mocked(apiClient.apiPost).mockResolvedValue(mockResponse);

      await loadFeed(1, 'Test Feed', 'https://example.com/feed.xml', 'edit_text=1');

      expect(apiClient.apiPost).toHaveBeenCalledWith('/feeds/1/load', {
        name: 'Test Feed',
        source_uri: 'https://example.com/feed.xml',
        options: 'edit_text=1'
      });
    });

    it('returns load result', async () => {
      const mockResponse = {
        data: { success: true, message: 'Loaded 5 articles', imported: 5, duplicates: 0 },
        error: undefined
      };
      vi.mocked(apiClient.apiPost).mockResolvedValue(mockResponse);

      const result = await loadFeed(1, 'Feed', 'https://example.com', '');

      expect(result.data?.success).toBe(true);
      expect(result.data?.imported).toBe(5);
    });
  });

  // ===========================================================================
  // getArticles Tests
  // ===========================================================================

  describe('getArticles', () => {
    it('calls apiGet with /feeds/articles endpoint', async () => {
      const mockResponse = {
        data: {
          articles: [],
          pagination: { page: 1, per_page: 50, total: 0, total_pages: 0 },
          feed: { id: 1, name: 'Test Feed', langId: 1 }
        } as ArticlesResponse,
        error: undefined
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      await getArticles({ feed_id: 1 });

      expect(apiClient.apiGet).toHaveBeenCalledWith('/feeds/articles', { feed_id: 1 });
    });

    it('passes query parameters', async () => {
      const mockResponse = {
        data: {
          articles: [],
          pagination: { page: 1, per_page: 25, total: 0, total_pages: 0 },
          feed: { id: 1, name: 'Test', langId: 1 }
        } as ArticlesResponse,
        error: undefined
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      await getArticles({ feed_id: 1, query: 'search', page: 2, per_page: 25, sort: 2 });

      expect(apiClient.apiGet).toHaveBeenCalledWith('/feeds/articles', {
        feed_id: 1,
        query: 'search',
        page: 2,
        per_page: 25,
        sort: 2
      });
    });

    it('returns articles list', async () => {
      const mockArticle: Article = {
        id: 1,
        title: 'Article Title',
        link: 'https://example.com/article',
        description: 'Description',
        date: '2025-01-01',
        audio: '',
        hasText: false,
        status: 'new',
        textId: null,
        archivedTextId: null
      };
      const mockResponse = {
        data: {
          articles: [mockArticle],
          pagination: { page: 1, per_page: 50, total: 1, total_pages: 1 },
          feed: { id: 1, name: 'Test', langId: 1 }
        } as ArticlesResponse,
        error: undefined
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      const result = await getArticles({ feed_id: 1 });

      expect(result.data?.articles).toHaveLength(1);
      expect(result.data?.articles[0].title).toBe('Article Title');
    });
  });

  // ===========================================================================
  // deleteArticles Tests
  // ===========================================================================

  describe('deleteArticles', () => {
    it('calls apiDelete with feed articles endpoint', async () => {
      const mockResponse = {
        data: { success: true, deleted: 5 },
        error: undefined
      };
      vi.mocked(apiClient.apiDelete).mockResolvedValue(mockResponse);

      await deleteArticles(1, [1, 2, 3]);

      expect(apiClient.apiDelete).toHaveBeenCalledWith('/feeds/articles/1');
    });

    it('works without article IDs', async () => {
      const mockResponse = {
        data: { success: true, deleted: 10 },
        error: undefined
      };
      vi.mocked(apiClient.apiDelete).mockResolvedValue(mockResponse);

      await deleteArticles(5);

      expect(apiClient.apiDelete).toHaveBeenCalledWith('/feeds/articles/5');
    });
  });

  // ===========================================================================
  // importArticles Tests
  // ===========================================================================

  describe('importArticles', () => {
    it('calls apiPost with article IDs', async () => {
      const mockResponse = {
        data: { success: true, imported: 3, errors: [] },
        error: undefined
      };
      vi.mocked(apiClient.apiPost).mockResolvedValue(mockResponse);

      await importArticles([1, 2, 3]);

      expect(apiClient.apiPost).toHaveBeenCalledWith('/feeds/articles/import', {
        article_ids: [1, 2, 3]
      });
    });

    it('returns import result', async () => {
      const mockResponse = {
        data: { success: true, imported: 2, errors: ['Article 3 failed'] },
        error: undefined
      };
      vi.mocked(apiClient.apiPost).mockResolvedValue(mockResponse);

      const result = await importArticles([1, 2, 3]);

      expect(result.data?.imported).toBe(2);
      expect(result.data?.errors).toContain('Article 3 failed');
    });
  });

  // ===========================================================================
  // resetErrorArticles Tests
  // ===========================================================================

  describe('resetErrorArticles', () => {
    it('calls apiDelete with reset-errors endpoint', async () => {
      const mockResponse = {
        data: { success: true, reset: 5 },
        error: undefined
      };
      vi.mocked(apiClient.apiDelete).mockResolvedValue(mockResponse);

      await resetErrorArticles(1);

      expect(apiClient.apiDelete).toHaveBeenCalledWith('/feeds/1/reset-errors');
    });

    it('returns reset count', async () => {
      const mockResponse = {
        data: { success: true, reset: 3 },
        error: undefined
      };
      vi.mocked(apiClient.apiDelete).mockResolvedValue(mockResponse);

      const result = await resetErrorArticles(1);

      expect(result.data?.reset).toBe(3);
    });
  });

  // ===========================================================================
  // buildOptionsString Tests
  // ===========================================================================

  describe('buildOptionsString', () => {
    it('returns empty string for empty options', () => {
      expect(buildOptionsString({})).toBe('');
    });

    it('builds string with edit_text option', () => {
      expect(buildOptionsString({ edit_text: '1' })).toBe('edit_text=1');
    });

    it('builds string with autoupdate option', () => {
      expect(buildOptionsString({ autoupdate: '1h' })).toBe('autoupdate=1h');
    });

    it('builds string with max_links option', () => {
      expect(buildOptionsString({ max_links: '50' })).toBe('max_links=50');
    });

    it('builds string with max_texts option', () => {
      expect(buildOptionsString({ max_texts: '100' })).toBe('max_texts=100');
    });

    it('builds string with charset option', () => {
      expect(buildOptionsString({ charset: 'utf-8' })).toBe('charset=utf-8');
    });

    it('builds string with tag option', () => {
      expect(buildOptionsString({ tag: 'news' })).toBe('tag=news');
    });

    it('builds string with article_source option', () => {
      expect(buildOptionsString({ article_source: '//content' })).toBe('article_source=//content');
    });

    it('combines multiple options with commas', () => {
      const result = buildOptionsString({
        edit_text: '1',
        autoupdate: '1d',
        max_links: '20'
      });

      expect(result).toBe('edit_text=1,autoupdate=1d,max_links=20');
    });

    it('ignores undefined options', () => {
      const result = buildOptionsString({
        edit_text: '1',
        autoupdate: undefined,
        max_links: '50'
      });

      expect(result).toBe('edit_text=1,max_links=50');
    });
  });

  // ===========================================================================
  // parseOptionsString Tests
  // ===========================================================================

  describe('parseOptionsString', () => {
    it('returns empty object for empty string', () => {
      expect(parseOptionsString('')).toEqual({});
    });

    it('parses single option', () => {
      expect(parseOptionsString('edit_text=1')).toEqual({ edit_text: '1' });
    });

    it('parses multiple options', () => {
      const result = parseOptionsString('edit_text=1,autoupdate=1h,max_links=50');

      expect(result).toEqual({
        edit_text: '1',
        autoupdate: '1h',
        max_links: '50'
      });
    });

    it('handles spaces around keys and values', () => {
      const result = parseOptionsString(' edit_text = 1 , autoupdate = 2d ');

      expect(result).toEqual({
        edit_text: '1',
        autoupdate: '2d'
      });
    });

    it('handles option with empty value', () => {
      const result = parseOptionsString('tag=');

      expect(result).toEqual({ tag: '' });
    });

    it('handles all option types', () => {
      const result = parseOptionsString(
        'edit_text=1,autoupdate=1w,max_links=100,max_texts=50,charset=utf-8,tag=news,article_source=//div'
      );

      expect(result).toEqual({
        edit_text: '1',
        autoupdate: '1w',
        max_links: '100',
        max_texts: '50',
        charset: 'utf-8',
        tag: 'news',
        article_source: '//div'
      });
    });
  });

  // ===========================================================================
  // formatAutoUpdate Tests
  // ===========================================================================

  describe('formatAutoUpdate', () => {
    it('returns "Never" for undefined', () => {
      expect(formatAutoUpdate(undefined)).toBe('Never');
    });

    it('returns "Never" for empty string', () => {
      expect(formatAutoUpdate('')).toBe('Never');
    });

    it('returns "Every hour" for 1h', () => {
      expect(formatAutoUpdate('1h')).toBe('Every hour');
    });

    it('returns "Every X hours" for Xh', () => {
      expect(formatAutoUpdate('6h')).toBe('Every 6 hours');
      expect(formatAutoUpdate('12h')).toBe('Every 12 hours');
    });

    it('returns "Every day" for 1d', () => {
      expect(formatAutoUpdate('1d')).toBe('Every day');
    });

    it('returns "Every X days" for Xd', () => {
      expect(formatAutoUpdate('3d')).toBe('Every 3 days');
      expect(formatAutoUpdate('7d')).toBe('Every 7 days');
    });

    it('returns "Every week" for 1w', () => {
      expect(formatAutoUpdate('1w')).toBe('Every week');
    });

    it('returns "Every X weeks" for Xw', () => {
      expect(formatAutoUpdate('2w')).toBe('Every 2 weeks');
      expect(formatAutoUpdate('4w')).toBe('Every 4 weeks');
    });

    it('returns "Never" for unrecognized format (5m)', () => {
      expect(formatAutoUpdate('5m')).toBe('Never');
    });
  });

  // ===========================================================================
  // getStatusBadgeClass Tests
  // ===========================================================================

  describe('getStatusBadgeClass', () => {
    it('returns is-success for imported status', () => {
      expect(getStatusBadgeClass('imported')).toBe('is-success');
    });

    it('returns is-info for archived status', () => {
      expect(getStatusBadgeClass('archived')).toBe('is-info');
    });

    it('returns is-danger for error status', () => {
      expect(getStatusBadgeClass('error')).toBe('is-danger');
    });

    it('returns is-light for new status', () => {
      expect(getStatusBadgeClass('new')).toBe('is-light');
    });
  });

  // ===========================================================================
  // getStatusLabel Tests
  // ===========================================================================

  describe('getStatusLabel', () => {
    it('returns Imported for imported status', () => {
      expect(getStatusLabel('imported')).toBe('Imported');
    });

    it('returns Archived for archived status', () => {
      expect(getStatusLabel('archived')).toBe('Archived');
    });

    it('returns Error for error status', () => {
      expect(getStatusLabel('error')).toBe('Error');
    });

    it('returns New for new status', () => {
      expect(getStatusLabel('new')).toBe('New');
    });
  });
});
