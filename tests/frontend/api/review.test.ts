/**
 * Tests for api/review.ts - Review API operations
 */
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { ReviewApi } from '../../../src/frontend/js/api/review';
import * as apiClient from '../../../src/frontend/js/core/api_client';

// Mock the api_client module
vi.mock('../../../src/frontend/js/core/api_client', () => ({
  apiGet: vi.fn(),
  apiPut: vi.fn(),
}));

describe('api/review.ts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  // ===========================================================================
  // getNextWord Tests
  // ===========================================================================

  describe('ReviewApi.getNextWord', () => {
    it('calls apiGet with correct parameters', async () => {
      const mockResponse = {
        data: {
          word_id: 123,
          word_text: 'test',
          group: 'group1',
        },
        error: undefined,
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      const params = {
        testKey: 'abc123',
        selection: 'all',
        wordMode: true,
        lgId: 1,
        wordRegex: '.*',
        type: 1,
      };

      const result = await ReviewApi.getNextWord(params);

      expect(apiClient.apiGet).toHaveBeenCalledWith('/review/next-word', {
        test_key: 'abc123',
        selection: 'all',
        word_mode: true,
        lg_id: 1,
        word_regex: '.*',
        type: 1,
      });
      expect(result).toEqual(mockResponse);
    });

    it('handles word mode false', async () => {
      const mockResponse = { data: {}, error: undefined };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      const params = {
        testKey: 'key',
        selection: 'selected',
        wordMode: false,
        lgId: 2,
        wordRegex: '[a-z]+',
        type: 2,
      };

      await ReviewApi.getNextWord(params);

      expect(apiClient.apiGet).toHaveBeenCalledWith('/review/next-word', {
        test_key: 'key',
        selection: 'selected',
        word_mode: false,
        lg_id: 2,
        word_regex: '[a-z]+',
        type: 2,
      });
    });
  });

  // ===========================================================================
  // getTomorrowCount Tests
  // ===========================================================================

  describe('ReviewApi.getTomorrowCount', () => {
    it('calls apiGet with correct parameters', async () => {
      const mockResponse = {
        data: { count: 42 },
        error: undefined,
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      const result = await ReviewApi.getTomorrowCount('testKey123', 'all');

      expect(apiClient.apiGet).toHaveBeenCalledWith('/review/tomorrow-count', {
        test_key: 'testKey123',
        selection: 'all',
      });
      expect(result.data?.count).toBe(42);
    });

    it('handles different selection values', async () => {
      const mockResponse = { data: { count: 10 }, error: undefined };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      await ReviewApi.getTomorrowCount('key', 'status1');

      expect(apiClient.apiGet).toHaveBeenCalledWith('/review/tomorrow-count', {
        test_key: 'key',
        selection: 'status1',
      });
    });
  });

  // ===========================================================================
  // updateStatus Tests
  // ===========================================================================

  describe('ReviewApi.updateStatus', () => {
    it('calls apiPut with word_id and status', async () => {
      const mockResponse = {
        data: { status: 3 },
        error: undefined,
      };
      vi.mocked(apiClient.apiPut).mockResolvedValue(mockResponse);

      const result = await ReviewApi.updateStatus(123, 3, undefined);

      expect(apiClient.apiPut).toHaveBeenCalledWith('/review/status', {
        word_id: 123,
        status: 3,
        change: undefined,
      });
      expect(result.data?.status).toBe(3);
    });

    it('calls apiPut with word_id and change', async () => {
      const mockResponse = {
        data: { status: 4 },
        error: undefined,
      };
      vi.mocked(apiClient.apiPut).mockResolvedValue(mockResponse);

      const _result = await ReviewApi.updateStatus(456, undefined, 1);

      expect(apiClient.apiPut).toHaveBeenCalledWith('/review/status', {
        word_id: 456,
        status: undefined,
        change: 1,
      });
    });

    it('handles status change with negative change', async () => {
      const mockResponse = { data: { status: 2 }, error: undefined };
      vi.mocked(apiClient.apiPut).mockResolvedValue(mockResponse);

      await ReviewApi.updateStatus(789, undefined, -1);

      expect(apiClient.apiPut).toHaveBeenCalledWith('/review/status', {
        word_id: 789,
        status: undefined,
        change: -1,
      });
    });

    it('handles error response', async () => {
      const mockResponse = {
        data: undefined,
        error: 'Word not found',
      };
      vi.mocked(apiClient.apiPut).mockResolvedValue(mockResponse);

      const result = await ReviewApi.updateStatus(999, 5, undefined);

      expect(result.error).toBe('Word not found');
    });

    it('sets both status and change when provided', async () => {
      const mockResponse = { data: { status: 5 }, error: undefined };
      vi.mocked(apiClient.apiPut).mockResolvedValue(mockResponse);

      await ReviewApi.updateStatus(100, 5, 1);

      expect(apiClient.apiPut).toHaveBeenCalledWith('/review/status', {
        word_id: 100,
        status: 5,
        change: 1,
      });
    });
  });
});
