/**
 * Tests for api/words.ts - Words API operations
 */
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { WordsApi } from '../../../src/frontend/js/modules/vocabulary/api/words_api';
import * as apiClient from '../../../src/frontend/js/shared/api/client';

// Mock the api_client module
vi.mock('../../../src/frontend/js/shared/api/client', () => ({
  apiGet: vi.fn(),
  apiPut: vi.fn(),
}));

describe('api/words.ts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  // ===========================================================================
  // getList Tests
  // ===========================================================================

  describe('WordsApi.getList', () => {
    it('calls apiGet with /terms/list endpoint', async () => {
      const mockResponse = {
        data: {
          words: [],
          pagination: { page: 1, per_page: 50, total: 0, total_pages: 0 }
        },
        error: undefined,
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      await WordsApi.getList({});

      expect(apiClient.apiGet).toHaveBeenCalledWith('/terms/list', {});
    });

    it('includes language filter when provided', async () => {
      const mockResponse = {
        data: { words: [], pagination: { page: 1, per_page: 50, total: 0, total_pages: 0 } },
        error: undefined,
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      await WordsApi.getList({ lang: 1 });

      expect(apiClient.apiGet).toHaveBeenCalledWith('/terms/list', { lang: 1 });
    });

    it('includes text_id filter when provided', async () => {
      const mockResponse = {
        data: { words: [], pagination: { page: 1, per_page: 50, total: 0, total_pages: 0 } },
        error: undefined,
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      await WordsApi.getList({ text_id: 5 });

      expect(apiClient.apiGet).toHaveBeenCalledWith('/terms/list', { text_id: 5 });
    });

    it('includes status filter when provided', async () => {
      const mockResponse = {
        data: { words: [], pagination: { page: 1, per_page: 50, total: 0, total_pages: 0 } },
        error: undefined,
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      await WordsApi.getList({ status: '1-3' });

      expect(apiClient.apiGet).toHaveBeenCalledWith('/terms/list', { status: '1-3' });
    });

    it('includes query filter when provided', async () => {
      const mockResponse = {
        data: { words: [], pagination: { page: 1, per_page: 50, total: 0, total_pages: 0 } },
        error: undefined,
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      await WordsApi.getList({ query: 'hello', query_mode: 'term' });

      expect(apiClient.apiGet).toHaveBeenCalledWith('/terms/list', { query: 'hello', query_mode: 'term' });
    });

    it('includes tag filters when provided', async () => {
      const mockResponse = {
        data: { words: [], pagination: { page: 1, per_page: 50, total: 0, total_pages: 0 } },
        error: undefined,
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      await WordsApi.getList({ tag1: 1, tag2: 2, tag12: 0 });

      expect(apiClient.apiGet).toHaveBeenCalledWith('/terms/list', { tag1: 1, tag2: 2, tag12: 0 });
    });

    it('includes sort and pagination params', async () => {
      const mockResponse = {
        data: { words: [], pagination: { page: 2, per_page: 25, total: 100, total_pages: 4 } },
        error: undefined,
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      await WordsApi.getList({ sort: 2, page: 2, per_page: 25 });

      expect(apiClient.apiGet).toHaveBeenCalledWith('/terms/list', { sort: 2, page: 2, per_page: 25 });
    });

    it('excludes null and empty filters', async () => {
      const mockResponse = {
        data: { words: [], pagination: { page: 1, per_page: 50, total: 0, total_pages: 0 } },
        error: undefined,
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      await WordsApi.getList({ lang: null, text_id: '', status: '', query: '' });

      expect(apiClient.apiGet).toHaveBeenCalledWith('/terms/list', {});
    });

    it('returns word items with all properties', async () => {
      const mockWord = {
        id: 1,
        text: 'hello',
        translation: 'hola',
        romanization: '',
        sentence: 'Hello world',
        sentenceOk: true,
        status: 3,
        statusAbbr: '3',
        statusLabel: 'Learning (3)',
        days: '5',
        score: 75,
        score2: 80,
        tags: 'greetings',
        langId: 1,
        langName: 'Spanish',
        rightToLeft: false,
        ttsClass: null
      };
      const mockResponse = {
        data: {
          words: [mockWord],
          pagination: { page: 1, per_page: 50, total: 1, total_pages: 1 }
        },
        error: undefined,
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      const result = await WordsApi.getList({});

      expect(result.data?.words[0]).toEqual(mockWord);
    });

    it('handles error response', async () => {
      const mockResponse = {
        data: undefined,
        error: 'Database error',
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      const result = await WordsApi.getList({});

      expect(result.error).toBe('Database error');
    });
  });

  // ===========================================================================
  // getFilterOptions Tests
  // ===========================================================================

  describe('WordsApi.getFilterOptions', () => {
    it('calls apiGet with /terms/filter-options endpoint', async () => {
      const mockResponse = {
        data: {
          languages: [],
          texts: [],
          tags: [],
          statuses: [],
          sorts: []
        },
        error: undefined,
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      await WordsApi.getFilterOptions();

      expect(apiClient.apiGet).toHaveBeenCalledWith('/terms/filter-options', {});
    });

    it('includes language ID when provided', async () => {
      const mockResponse = {
        data: {
          languages: [],
          texts: [{ id: 1, title: 'Text 1' }],
          tags: [],
          statuses: [],
          sorts: []
        },
        error: undefined,
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      await WordsApi.getFilterOptions(1);

      expect(apiClient.apiGet).toHaveBeenCalledWith('/terms/filter-options', { language_id: 1 });
    });

    it('returns filter options', async () => {
      const mockResponse = {
        data: {
          languages: [{ id: 1, name: 'English' }],
          texts: [{ id: 1, title: 'My Text' }],
          tags: [{ id: 1, name: 'important' }],
          statuses: [{ value: '1', label: 'Learning (1)' }],
          sorts: [{ value: 1, label: 'Term A-Z' }]
        },
        error: undefined,
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      const result = await WordsApi.getFilterOptions();

      expect(result.data?.languages).toHaveLength(1);
      expect(result.data?.texts).toHaveLength(1);
      expect(result.data?.tags).toHaveLength(1);
    });

    it('handles null language ID', async () => {
      const mockResponse = {
        data: { languages: [], texts: [], tags: [], statuses: [], sorts: [] },
        error: undefined,
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      await WordsApi.getFilterOptions(null);

      expect(apiClient.apiGet).toHaveBeenCalledWith('/terms/filter-options', {});
    });
  });

  // ===========================================================================
  // bulkAction Tests
  // ===========================================================================

  describe('WordsApi.bulkAction', () => {
    it('calls apiPut with bulk-action endpoint', async () => {
      const mockResponse = {
        data: { success: true, count: 5, message: '5 terms updated' },
        error: undefined,
      };
      vi.mocked(apiClient.apiPut).mockResolvedValue(mockResponse);

      const result = await WordsApi.bulkAction([1, 2, 3, 4, 5], 'setstatus2');

      expect(apiClient.apiPut).toHaveBeenCalledWith('/terms/bulk-action', {
        ids: [1, 2, 3, 4, 5],
        action: 'setstatus2'
      });
      expect(result.data?.success).toBe(true);
      expect(result.data?.count).toBe(5);
    });

    it('includes data parameter when provided', async () => {
      const mockResponse = {
        data: { success: true, count: 3, message: 'Tag added' },
        error: undefined,
      };
      vi.mocked(apiClient.apiPut).mockResolvedValue(mockResponse);

      await WordsApi.bulkAction([1, 2, 3], 'addtag', 'important');

      expect(apiClient.apiPut).toHaveBeenCalledWith('/terms/bulk-action', {
        ids: [1, 2, 3],
        action: 'addtag',
        data: 'important'
      });
    });

    it('handles delete action', async () => {
      const mockResponse = {
        data: { success: true, count: 2, message: '2 terms deleted' },
        error: undefined,
      };
      vi.mocked(apiClient.apiPut).mockResolvedValue(mockResponse);

      const result = await WordsApi.bulkAction([1, 2], 'del');

      expect(result.data?.message).toBe('2 terms deleted');
    });

    it('handles error response', async () => {
      const mockResponse = {
        data: { success: false, count: 0, message: 'No terms found' },
        error: undefined,
      };
      vi.mocked(apiClient.apiPut).mockResolvedValue(mockResponse);

      const result = await WordsApi.bulkAction([], 'setstatus1');

      expect(result.data?.success).toBe(false);
    });
  });

  // ===========================================================================
  // allAction Tests
  // ===========================================================================

  describe('WordsApi.allAction', () => {
    it('calls apiPut with all-action endpoint', async () => {
      const mockResponse = {
        data: { success: true, count: 100, message: '100 terms updated' },
        error: undefined,
      };
      vi.mocked(apiClient.apiPut).mockResolvedValue(mockResponse);

      const filters = { lang: 1, status: '1' };
      const result = await WordsApi.allAction(filters, 'setstatus2');

      expect(apiClient.apiPut).toHaveBeenCalledWith('/terms/all-action', {
        filters,
        action: 'setstatus2'
      });
      expect(result.data?.count).toBe(100);
    });

    it('includes data parameter when provided', async () => {
      const mockResponse = {
        data: { success: true, count: 50, message: 'Tag added to 50 terms' },
        error: undefined,
      };
      vi.mocked(apiClient.apiPut).mockResolvedValue(mockResponse);

      await WordsApi.allAction({ lang: 1 }, 'addtag', 'reviewed');

      expect(apiClient.apiPut).toHaveBeenCalledWith('/terms/all-action', {
        filters: { lang: 1 },
        action: 'addtag',
        data: 'reviewed'
      });
    });

    it('handles error response', async () => {
      const mockResponse = {
        data: undefined,
        error: 'Action failed',
      };
      vi.mocked(apiClient.apiPut).mockResolvedValue(mockResponse);

      const result = await WordsApi.allAction({}, 'del');

      expect(result.error).toBe('Action failed');
    });
  });

  // ===========================================================================
  // inlineEdit Tests
  // ===========================================================================

  describe('WordsApi.inlineEdit', () => {
    it('calls apiPut with inline-edit endpoint for translation', async () => {
      const mockResponse = {
        data: { success: true, value: 'bonjour' },
        error: undefined,
      };
      vi.mocked(apiClient.apiPut).mockResolvedValue(mockResponse);

      const result = await WordsApi.inlineEdit(1, 'translation', 'bonjour');

      expect(apiClient.apiPut).toHaveBeenCalledWith('/terms/1/inline-edit', {
        field: 'translation',
        value: 'bonjour'
      });
      expect(result.data?.value).toBe('bonjour');
    });

    it('calls apiPut with inline-edit endpoint for romanization', async () => {
      const mockResponse = {
        data: { success: true, value: 'konnichiwa' },
        error: undefined,
      };
      vi.mocked(apiClient.apiPut).mockResolvedValue(mockResponse);

      const result = await WordsApi.inlineEdit(2, 'romanization', 'konnichiwa');

      expect(apiClient.apiPut).toHaveBeenCalledWith('/terms/2/inline-edit', {
        field: 'romanization',
        value: 'konnichiwa'
      });
      expect(result.data?.success).toBe(true);
    });

    it('handles empty value', async () => {
      const mockResponse = {
        data: { success: true, value: '' },
        error: undefined,
      };
      vi.mocked(apiClient.apiPut).mockResolvedValue(mockResponse);

      await WordsApi.inlineEdit(1, 'translation', '');

      expect(apiClient.apiPut).toHaveBeenCalledWith('/terms/1/inline-edit', {
        field: 'translation',
        value: ''
      });
    });

    it('handles error response', async () => {
      const mockResponse = {
        data: { success: false, error: 'Term not found' },
        error: undefined,
      };
      vi.mocked(apiClient.apiPut).mockResolvedValue(mockResponse);

      const result = await WordsApi.inlineEdit(999, 'translation', 'test');

      expect(result.data?.success).toBe(false);
      expect(result.data?.error).toBe('Term not found');
    });

    it('handles special characters in value', async () => {
      const mockResponse = {
        data: { success: true, value: 'Привет' },
        error: undefined,
      };
      vi.mocked(apiClient.apiPut).mockResolvedValue(mockResponse);

      await WordsApi.inlineEdit(1, 'translation', 'Привет');

      expect(apiClient.apiPut).toHaveBeenCalledWith('/terms/1/inline-edit', {
        field: 'translation',
        value: 'Привет'
      });
    });
  });
});
