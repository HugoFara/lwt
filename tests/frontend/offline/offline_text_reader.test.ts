/**
 * Tests for shared/offline/offline-text-reader.ts - Offline-first text reading
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock dependencies before importing
vi.mock('../../../src/frontend/js/shared/offline/text-service', () => ({
  isOfflineStorageAvailable: vi.fn().mockReturnValue(true),
  isTextAvailableOffline: vi.fn().mockResolvedValue({ available: false }),
  getOfflineTextData: vi.fn().mockResolvedValue(null),
}));

vi.mock('../../../src/frontend/js/modules/text/api/texts_api', () => ({
  TextsApi: {
    getWords: vi.fn().mockResolvedValue({ data: null, error: 'Not found' }),
  },
}));

vi.mock('../../../src/frontend/js/shared/offline/db', () => ({
  offlineDb: {
    texts: {
      toArray: vi.fn().mockResolvedValue([]),
    },
  },
}));

import {
  getTextWordsOfflineFirst,
  canReadText,
  getReadableOfflineTextIds,
  type TextDataResult,
} from '../../../src/frontend/js/shared/offline/offline-text-reader';
import {
  isOfflineStorageAvailable,
  isTextAvailableOffline,
  getOfflineTextData,
} from '../../../src/frontend/js/shared/offline/text-service';
import { TextsApi } from '../../../src/frontend/js/modules/text/api/texts_api';
import { offlineDb } from '../../../src/frontend/js/shared/offline/db';

describe('shared/offline/offline-text-reader.ts', () => {
  let consoleWarnSpy: ReturnType<typeof vi.spyOn>;

  beforeEach(() => {
    vi.clearAllMocks();
    consoleWarnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});

    // Default to online
    Object.defineProperty(navigator, 'onLine', {
      value: true,
      writable: true,
      configurable: true,
    });

    vi.mocked(isOfflineStorageAvailable).mockReturnValue(true);
  });

  afterEach(() => {
    consoleWarnSpy.mockRestore();
  });

  // ===========================================================================
  // getTextWordsOfflineFirst Tests
  // ===========================================================================

  describe('getTextWordsOfflineFirst', () => {
    const mockTextData = {
      config: {
        langId: 1,
        title: 'Test Text',
        audioUri: null,
        sourceUri: null,
      },
      words: [
        { text: 'Hello', status: 1 },
        { text: 'World', status: 2 },
      ],
    };

    describe('when online', () => {
      beforeEach(() => {
        Object.defineProperty(navigator, 'onLine', { value: true });
      });

      it('fetches from network first', async () => {
        vi.mocked(TextsApi.getWords).mockResolvedValue({
          data: mockTextData,
          error: undefined,
        });

        const result = await getTextWordsOfflineFirst(123);

        expect(TextsApi.getWords).toHaveBeenCalledWith(123);
        expect(result.source).toBe('network');
        expect(result.data).toEqual(mockTextData);
      });

      it('indicates offline availability status', async () => {
        vi.mocked(isTextAvailableOffline).mockResolvedValue({ available: true });
        vi.mocked(TextsApi.getWords).mockResolvedValue({
          data: mockTextData,
          error: undefined,
        });

        const result = await getTextWordsOfflineFirst(123);

        expect(result.offlineAvailable).toBe(true);
      });

      it('falls back to offline on network error when data is cached', async () => {
        vi.mocked(isTextAvailableOffline).mockResolvedValue({ available: true });
        vi.mocked(TextsApi.getWords).mockRejectedValue(new Error('Network error'));
        vi.mocked(getOfflineTextData).mockResolvedValue(mockTextData);

        const result = await getTextWordsOfflineFirst(123);

        expect(result.source).toBe('offline');
        expect(result.data).toEqual(mockTextData);
        expect(consoleWarnSpy).toHaveBeenCalledWith(
          'Network failed, using offline data'
        );
      });

      it('throws error when network fails and no offline data', async () => {
        vi.mocked(isTextAvailableOffline).mockResolvedValue({ available: false });
        vi.mocked(TextsApi.getWords).mockRejectedValue(new Error('Network error'));

        await expect(getTextWordsOfflineFirst(123)).rejects.toThrow('Network error');
      });

      it('throws error when API returns error response', async () => {
        vi.mocked(isTextAvailableOffline).mockResolvedValue({ available: false });
        vi.mocked(TextsApi.getWords).mockResolvedValue({
          data: undefined,
          error: 'Text not found',
        });

        await expect(getTextWordsOfflineFirst(123)).rejects.toThrow('Text not found');
      });

      it('throws generic error when API returns no data and no error', async () => {
        vi.mocked(isTextAvailableOffline).mockResolvedValue({ available: false });
        vi.mocked(TextsApi.getWords).mockResolvedValue({
          data: undefined,
          error: undefined,
        });

        await expect(getTextWordsOfflineFirst(123)).rejects.toThrow('Failed to fetch text');
      });
    });

    describe('when offline', () => {
      beforeEach(() => {
        Object.defineProperty(navigator, 'onLine', { value: false });
      });

      it('returns cached data when available', async () => {
        vi.mocked(isTextAvailableOffline).mockResolvedValue({ available: true });
        vi.mocked(getOfflineTextData).mockResolvedValue(mockTextData);

        const result = await getTextWordsOfflineFirst(123);

        expect(result.source).toBe('offline');
        expect(result.data).toEqual(mockTextData);
        expect(result.offlineAvailable).toBe(true);
        expect(TextsApi.getWords).not.toHaveBeenCalled();
      });

      it('throws error when text not cached', async () => {
        vi.mocked(isTextAvailableOffline).mockResolvedValue({ available: false });

        await expect(getTextWordsOfflineFirst(123)).rejects.toThrow(
          'Text not available offline. Download it first while online.'
        );
      });

      it('throws error when offline data retrieval returns null', async () => {
        vi.mocked(isTextAvailableOffline).mockResolvedValue({ available: true });
        vi.mocked(getOfflineTextData).mockResolvedValue(null);

        await expect(getTextWordsOfflineFirst(123)).rejects.toThrow(
          'Text not available offline. Download it first while online.'
        );
      });
    });

    describe('when storage is not available', () => {
      it('skips offline check when storage unavailable', async () => {
        vi.mocked(isOfflineStorageAvailable).mockReturnValue(false);
        vi.mocked(TextsApi.getWords).mockResolvedValue({
          data: mockTextData,
          error: undefined,
        });

        const result = await getTextWordsOfflineFirst(123);

        expect(isTextAvailableOffline).not.toHaveBeenCalled();
        expect(result.offlineAvailable).toBe(false);
      });
    });
  });

  // ===========================================================================
  // canReadText Tests
  // ===========================================================================

  describe('canReadText', () => {
    describe('when online', () => {
      beforeEach(() => {
        Object.defineProperty(navigator, 'onLine', { value: true });
      });

      it('returns true regardless of offline availability', async () => {
        vi.mocked(isTextAvailableOffline).mockResolvedValue({ available: false });

        const result = await canReadText(123);

        expect(result).toBe(true);
        expect(isTextAvailableOffline).not.toHaveBeenCalled();
      });
    });

    describe('when offline', () => {
      beforeEach(() => {
        Object.defineProperty(navigator, 'onLine', { value: false });
      });

      it('returns true when text is cached', async () => {
        vi.mocked(isOfflineStorageAvailable).mockReturnValue(true);
        vi.mocked(isTextAvailableOffline).mockResolvedValue({ available: true });

        const result = await canReadText(123);

        expect(result).toBe(true);
        expect(isTextAvailableOffline).toHaveBeenCalledWith(123);
      });

      it('returns false when text is not cached', async () => {
        vi.mocked(isOfflineStorageAvailable).mockReturnValue(true);
        vi.mocked(isTextAvailableOffline).mockResolvedValue({ available: false });

        const result = await canReadText(456);

        expect(result).toBe(false);
      });

      it('returns false when storage is not available', async () => {
        vi.mocked(isOfflineStorageAvailable).mockReturnValue(false);

        const result = await canReadText(789);

        expect(result).toBe(false);
        expect(isTextAvailableOffline).not.toHaveBeenCalled();
      });
    });
  });

  // ===========================================================================
  // getReadableOfflineTextIds Tests
  // ===========================================================================

  describe('getReadableOfflineTextIds', () => {
    it('returns empty array when storage not available', async () => {
      vi.mocked(isOfflineStorageAvailable).mockReturnValue(false);

      const result = await getReadableOfflineTextIds();

      expect(result).toEqual([]);
    });

    it('returns list of cached text IDs', async () => {
      vi.mocked(isOfflineStorageAvailable).mockReturnValue(true);
      vi.mocked(offlineDb.texts.toArray).mockResolvedValue([
        { id: 1, langId: 1, title: 'Text 1' },
        { id: 5, langId: 1, title: 'Text 5' },
        { id: 12, langId: 2, title: 'Text 12' },
      ] as any);

      const result = await getReadableOfflineTextIds();

      expect(result).toEqual([1, 5, 12]);
    });

    it('returns empty array when no texts cached', async () => {
      vi.mocked(isOfflineStorageAvailable).mockReturnValue(true);
      vi.mocked(offlineDb.texts.toArray).mockResolvedValue([]);

      const result = await getReadableOfflineTextIds();

      expect(result).toEqual([]);
    });
  });

  // ===========================================================================
  // TextDataResult Type Tests
  // ===========================================================================

  describe('TextDataResult type', () => {
    it('has correct structure for network source', async () => {
      vi.mocked(TextsApi.getWords).mockResolvedValue({
        data: { config: {} as any, words: [] },
        error: undefined,
      });
      vi.mocked(isTextAvailableOffline).mockResolvedValue({ available: false });

      const result: TextDataResult = await getTextWordsOfflineFirst(1);

      expect(result).toHaveProperty('data');
      expect(result).toHaveProperty('source');
      expect(result).toHaveProperty('offlineAvailable');
      expect(result.source).toBe('network');
    });

    it('has correct structure for offline source', async () => {
      Object.defineProperty(navigator, 'onLine', { value: false });
      vi.mocked(isTextAvailableOffline).mockResolvedValue({ available: true });
      vi.mocked(getOfflineTextData).mockResolvedValue({ config: {} as any, words: [] });

      const result: TextDataResult = await getTextWordsOfflineFirst(1);

      expect(result.source).toBe('offline');
      expect(result.offlineAvailable).toBe(true);
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles concurrent requests', async () => {
      vi.mocked(TextsApi.getWords).mockResolvedValue({
        data: { config: {} as any, words: [] },
        error: undefined,
      });
      vi.mocked(isTextAvailableOffline).mockResolvedValue({ available: false });

      const [result1, result2, result3] = await Promise.all([
        getTextWordsOfflineFirst(1),
        getTextWordsOfflineFirst(2),
        getTextWordsOfflineFirst(3),
      ]);

      expect(result1.source).toBe('network');
      expect(result2.source).toBe('network');
      expect(result3.source).toBe('network');
      expect(TextsApi.getWords).toHaveBeenCalledTimes(3);
    });

    it('handles rapid online/offline state changes', async () => {
      // Start online
      Object.defineProperty(navigator, 'onLine', { value: true });
      vi.mocked(TextsApi.getWords).mockResolvedValue({
        data: { config: {} as any, words: [] },
        error: undefined,
      });
      vi.mocked(isTextAvailableOffline).mockResolvedValue({ available: true });

      const result1 = await getTextWordsOfflineFirst(1);
      expect(result1.source).toBe('network');

      // Go offline
      Object.defineProperty(navigator, 'onLine', { value: false });
      vi.mocked(getOfflineTextData).mockResolvedValue({ config: {} as any, words: [] });

      const result2 = await getTextWordsOfflineFirst(1);
      expect(result2.source).toBe('offline');
    });
  });
});
