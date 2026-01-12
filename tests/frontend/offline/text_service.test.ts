/**
 * Tests for shared/offline/text-service.ts - Offline text storage service
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock db module before importing
vi.mock('../../../src/frontend/js/shared/offline/db', () => {
  const mockTable = () => ({
    toArray: vi.fn().mockResolvedValue([]),
    get: vi.fn().mockResolvedValue(undefined),
    put: vi.fn().mockResolvedValue(1),
    delete: vi.fn().mockResolvedValue(undefined),
    clear: vi.fn().mockResolvedValue(undefined),
    update: vi.fn().mockResolvedValue(1),
    where: vi.fn().mockReturnThis(),
    equals: vi.fn().mockReturnThis(),
  });

  return {
    offlineDb: {
      texts: mockTable(),
      textWords: mockTable(),
      languages: mockTable(),
      metadata: mockTable(),
      transaction: vi.fn().mockImplementation(
        async (_mode: string, _tables: unknown[], callback: () => Promise<void>) => {
          await callback();
        }
      ),
    },
    isIndexedDBAvailable: vi.fn().mockReturnValue(true),
    setMetadata: vi.fn().mockResolvedValue(undefined),
    getMetadata: vi.fn().mockResolvedValue(undefined),
  };
});

// Mock TextsApi
vi.mock('../../../src/frontend/js/modules/text/api/texts_api', () => ({
  TextsApi: {
    getWords: vi.fn().mockResolvedValue({ data: null, error: 'Not found' }),
  },
}));

// Mock apiGet
vi.mock('../../../src/frontend/js/shared/api/client', () => ({
  apiGet: vi.fn().mockResolvedValue({ data: null, error: 'Not found' }),
}));

import {
  isOfflineStorageAvailable,
  isTextAvailableOffline,
  getOfflineTextIds,
  downloadTextForOffline,
  removeTextFromOffline,
  getOfflineTextData,
  getOfflineTextMeta,
  getOfflineTextsByLanguage,
  getOfflineSummary,
  ensureLanguageCached,
  getOfflineLanguage,
  isCurrentlyOffline,
  getLastSyncTime,
  type OfflineAvailability,
  type OfflineSummary,
} from '../../../src/frontend/js/shared/offline/text-service';
import {
  offlineDb,
  isIndexedDBAvailable,
  setMetadata,
  getMetadata,
} from '../../../src/frontend/js/shared/offline/db';
import { TextsApi } from '../../../src/frontend/js/modules/text/api/texts_api';
import { apiGet } from '../../../src/frontend/js/shared/api/client';

describe('shared/offline/text-service.ts', () => {
  let consoleWarnSpy: ReturnType<typeof vi.spyOn>;

  beforeEach(() => {
    vi.clearAllMocks();
    consoleWarnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});
    vi.mocked(isIndexedDBAvailable).mockReturnValue(true);

    // Reset navigator.onLine
    Object.defineProperty(navigator, 'onLine', {
      value: true,
      writable: true,
      configurable: true,
    });
  });

  afterEach(() => {
    consoleWarnSpy.mockRestore();
  });

  // ===========================================================================
  // isOfflineStorageAvailable Tests
  // ===========================================================================

  describe('isOfflineStorageAvailable', () => {
    it('returns true when IndexedDB is available', () => {
      vi.mocked(isIndexedDBAvailable).mockReturnValue(true);
      expect(isOfflineStorageAvailable()).toBe(true);
    });

    it('returns false when IndexedDB is not available', () => {
      vi.mocked(isIndexedDBAvailable).mockReturnValue(false);
      expect(isOfflineStorageAvailable()).toBe(false);
    });
  });

  // ===========================================================================
  // isTextAvailableOffline Tests
  // ===========================================================================

  describe('isTextAvailableOffline', () => {
    it('returns unavailable when IndexedDB not available', async () => {
      vi.mocked(isIndexedDBAvailable).mockReturnValue(false);

      const result = await isTextAvailableOffline(123);

      expect(result).toEqual({ available: false });
    });

    it('returns unavailable when text not found', async () => {
      vi.mocked(offlineDb.texts.get).mockResolvedValue(undefined);

      const result = await isTextAvailableOffline(123);

      expect(result).toEqual({ available: false });
      expect(offlineDb.texts.get).toHaveBeenCalledWith(123);
    });

    it('returns available with metadata when text exists', async () => {
      const downloadedAt = new Date('2024-06-01');
      vi.mocked(offlineDb.texts.get).mockResolvedValue({
        id: 123,
        downloadedAt,
        sizeBytes: 5000,
      } as any);

      const result = await isTextAvailableOffline(123);

      expect(result).toEqual({
        available: true,
        downloadedAt,
        sizeBytes: 5000,
      });
    });
  });

  // ===========================================================================
  // getOfflineTextIds Tests
  // ===========================================================================

  describe('getOfflineTextIds', () => {
    it('returns empty array when IndexedDB not available', async () => {
      vi.mocked(isIndexedDBAvailable).mockReturnValue(false);

      const result = await getOfflineTextIds();

      expect(result).toEqual([]);
    });

    it('returns list of text IDs', async () => {
      vi.mocked(offlineDb.texts.toArray).mockResolvedValue([
        { id: 1 },
        { id: 5 },
        { id: 10 },
      ] as any);

      const result = await getOfflineTextIds();

      expect(result).toEqual([1, 5, 10]);
    });
  });

  // ===========================================================================
  // downloadTextForOffline Tests
  // ===========================================================================

  describe('downloadTextForOffline', () => {
    const mockTextResponse = {
      data: {
        words: [{ text: 'hello', status: 1 }],
        config: {
          langId: 1,
          title: 'Test Text',
          audioUri: null,
          sourceUri: 'https://example.com',
        },
      },
      error: undefined,
    };

    beforeEach(() => {
      vi.mocked(TextsApi.getWords).mockResolvedValue(mockTextResponse);
      vi.mocked(offlineDb.languages.get).mockResolvedValue({ id: 1 } as any);
    });

    it('throws when IndexedDB not available', async () => {
      vi.mocked(isIndexedDBAvailable).mockReturnValue(false);

      await expect(downloadTextForOffline(123)).rejects.toThrow(
        'Offline storage is not available'
      );
    });

    it('fetches text data from API', async () => {
      await downloadTextForOffline(123);

      expect(TextsApi.getWords).toHaveBeenCalledWith(123);
    });

    it('throws when API returns error', async () => {
      vi.mocked(TextsApi.getWords).mockResolvedValue({
        data: undefined,
        error: 'Text not found',
      });

      await expect(downloadTextForOffline(123)).rejects.toThrow('Text not found');
    });

    it('throws generic error when API returns no data', async () => {
      vi.mocked(TextsApi.getWords).mockResolvedValue({
        data: undefined,
        error: undefined,
      });

      await expect(downloadTextForOffline(123)).rejects.toThrow(
        'Failed to fetch text data'
      );
    });

    it('stores text and words in database', async () => {
      await downloadTextForOffline(123);

      expect(offlineDb.transaction).toHaveBeenCalled();
      expect(offlineDb.texts.put).toHaveBeenCalledWith(
        expect.objectContaining({
          id: 123,
          langId: 1,
          title: 'Test Text',
        })
      );
      expect(offlineDb.textWords.put).toHaveBeenCalledWith(
        expect.objectContaining({
          textId: 123,
          words: mockTextResponse.data!.words,
        })
      );
    });

    it('calls progress callback', async () => {
      const progressCallback = vi.fn();

      await downloadTextForOffline(123, progressCallback);

      expect(progressCallback).toHaveBeenCalledWith(0, 'Fetching text data...');
      expect(progressCallback).toHaveBeenCalledWith(30, 'Fetching language data...');
      expect(progressCallback).toHaveBeenCalledWith(50, 'Saving to offline storage...');
      expect(progressCallback).toHaveBeenCalledWith(70, 'Writing to database...');
      expect(progressCallback).toHaveBeenCalledWith(100, 'Download complete');
    });

    it('caches language data', async () => {
      vi.mocked(offlineDb.languages.get).mockResolvedValue(undefined);
      vi.mocked(apiGet).mockResolvedValue({
        data: {
          id: 1,
          name: 'English',
          abbreviation: 'en',
          rightToLeft: false,
          removeSpaces: false,
          dict1Link: '',
          dict2Link: '',
          translatorLink: '',
          textSize: 100,
        },
      });

      await downloadTextForOffline(123);

      expect(apiGet).toHaveBeenCalledWith('/languages/1');
    });

    it('updates last sync metadata', async () => {
      await downloadTextForOffline(123);

      expect(setMetadata).toHaveBeenCalledWith('lastTextDownload', expect.any(String));
    });
  });

  // ===========================================================================
  // removeTextFromOffline Tests
  // ===========================================================================

  describe('removeTextFromOffline', () => {
    it('does nothing when IndexedDB not available', async () => {
      vi.mocked(isIndexedDBAvailable).mockReturnValue(false);

      await removeTextFromOffline(123);

      expect(offlineDb.transaction).not.toHaveBeenCalled();
    });

    it('deletes text and words from database', async () => {
      await removeTextFromOffline(123);

      expect(offlineDb.transaction).toHaveBeenCalled();
      expect(offlineDb.texts.delete).toHaveBeenCalledWith(123);
      expect(offlineDb.textWords.delete).toHaveBeenCalledWith(123);
    });
  });

  // ===========================================================================
  // getOfflineTextData Tests
  // ===========================================================================

  describe('getOfflineTextData', () => {
    it('returns null when IndexedDB not available', async () => {
      vi.mocked(isIndexedDBAvailable).mockReturnValue(false);

      const result = await getOfflineTextData(123);

      expect(result).toBeNull();
    });

    it('returns null when text not found', async () => {
      vi.mocked(offlineDb.texts.get).mockResolvedValue(undefined);

      const result = await getOfflineTextData(123);

      expect(result).toBeNull();
    });

    it('returns null when words not found', async () => {
      vi.mocked(offlineDb.texts.get).mockResolvedValue({ id: 123 } as any);
      vi.mocked(offlineDb.textWords.get).mockResolvedValue(undefined);

      const result = await getOfflineTextData(123);

      expect(result).toBeNull();
    });

    it('returns text data when both text and words exist', async () => {
      const mockConfig = { langId: 1, title: 'Test' };
      const mockWords = [{ text: 'hello' }];

      vi.mocked(offlineDb.texts.get).mockResolvedValue({
        id: 123,
        config: mockConfig,
      } as any);
      vi.mocked(offlineDb.textWords.get).mockResolvedValue({
        textId: 123,
        words: mockWords,
      } as any);

      const result = await getOfflineTextData(123);

      expect(result).toEqual({
        config: mockConfig,
        words: mockWords,
      });
    });

    it('updates last accessed time', async () => {
      vi.mocked(offlineDb.texts.get).mockResolvedValue({ id: 123, config: {} } as any);
      vi.mocked(offlineDb.textWords.get).mockResolvedValue({ words: [] } as any);

      await getOfflineTextData(123);

      expect(offlineDb.texts.update).toHaveBeenCalledWith(123, {
        lastAccessedAt: expect.any(Date),
      });
    });
  });

  // ===========================================================================
  // getOfflineTextMeta Tests
  // ===========================================================================

  describe('getOfflineTextMeta', () => {
    it('returns null when IndexedDB not available', async () => {
      vi.mocked(isIndexedDBAvailable).mockReturnValue(false);

      const result = await getOfflineTextMeta(123);

      expect(result).toBeNull();
    });

    it('returns null when text not found', async () => {
      vi.mocked(offlineDb.texts.get).mockResolvedValue(undefined);

      const result = await getOfflineTextMeta(123);

      expect(result).toBeNull();
    });

    it('returns text metadata', async () => {
      const mockText = { id: 123, title: 'Test', langId: 1 };
      vi.mocked(offlineDb.texts.get).mockResolvedValue(mockText as any);

      const result = await getOfflineTextMeta(123);

      expect(result).toEqual(mockText);
    });
  });

  // ===========================================================================
  // getOfflineTextsByLanguage Tests
  // ===========================================================================

  describe('getOfflineTextsByLanguage', () => {
    it('returns empty array when IndexedDB not available', async () => {
      vi.mocked(isIndexedDBAvailable).mockReturnValue(false);

      const result = await getOfflineTextsByLanguage(1);

      expect(result).toEqual([]);
    });

    it('queries texts by language ID', async () => {
      const mockTexts = [
        { id: 1, langId: 1 },
        { id: 2, langId: 1 },
      ];
      vi.mocked(offlineDb.texts.where).mockReturnValue({
        equals: vi.fn().mockReturnValue({
          toArray: vi.fn().mockResolvedValue(mockTexts),
        }),
      } as any);

      const result = await getOfflineTextsByLanguage(1);

      expect(offlineDb.texts.where).toHaveBeenCalledWith('langId');
      expect(result).toEqual(mockTexts);
    });
  });

  // ===========================================================================
  // getOfflineSummary Tests
  // ===========================================================================

  describe('getOfflineSummary', () => {
    it('returns empty summary when IndexedDB not available', async () => {
      vi.mocked(isIndexedDBAvailable).mockReturnValue(false);

      const result = await getOfflineSummary();

      expect(result).toEqual({
        totalTexts: 0,
        totalSizeBytes: 0,
        byLanguage: [],
      });
    });

    it('calculates summary from stored data', async () => {
      vi.mocked(offlineDb.texts.toArray).mockResolvedValue([
        { id: 1, langId: 1, sizeBytes: 1000 },
        { id: 2, langId: 1, sizeBytes: 2000 },
        { id: 3, langId: 2, sizeBytes: 1500 },
      ] as any);
      vi.mocked(offlineDb.languages.toArray).mockResolvedValue([
        { id: 1, name: 'English' },
        { id: 2, name: 'French' },
      ] as any);

      const result = await getOfflineSummary();

      expect(result.totalTexts).toBe(3);
      expect(result.totalSizeBytes).toBe(4500);
      expect(result.byLanguage).toHaveLength(2);
      expect(result.byLanguage).toContainEqual({
        langId: 1,
        langName: 'English',
        textCount: 2,
        sizeBytes: 3000,
      });
      expect(result.byLanguage).toContainEqual({
        langId: 2,
        langName: 'French',
        textCount: 1,
        sizeBytes: 1500,
      });
    });

    it('uses fallback language name when not found', async () => {
      vi.mocked(offlineDb.texts.toArray).mockResolvedValue([
        { id: 1, langId: 99, sizeBytes: 1000 },
      ] as any);
      vi.mocked(offlineDb.languages.toArray).mockResolvedValue([]);

      const result = await getOfflineSummary();

      expect(result.byLanguage[0].langName).toBe('Language 99');
    });
  });

  // ===========================================================================
  // ensureLanguageCached Tests
  // ===========================================================================

  describe('ensureLanguageCached', () => {
    it('does nothing when IndexedDB not available', async () => {
      vi.mocked(isIndexedDBAvailable).mockReturnValue(false);

      await ensureLanguageCached(1);

      expect(offlineDb.languages.get).not.toHaveBeenCalled();
    });

    it('does nothing when language already cached', async () => {
      vi.mocked(offlineDb.languages.get).mockResolvedValue({ id: 1 } as any);

      await ensureLanguageCached(1);

      expect(apiGet).not.toHaveBeenCalled();
    });

    it('fetches and caches language when not cached', async () => {
      vi.mocked(offlineDb.languages.get).mockResolvedValue(undefined);
      vi.mocked(apiGet).mockResolvedValue({
        data: {
          id: 1,
          name: 'English',
          abbreviation: 'en',
          rightToLeft: false,
          removeSpaces: false,
          dict1Link: 'https://dict1.com',
          dict2Link: 'https://dict2.com',
          translatorLink: 'https://translate.com',
          textSize: 100,
        },
      });

      await ensureLanguageCached(1);

      expect(apiGet).toHaveBeenCalledWith('/languages/1');
      expect(offlineDb.languages.put).toHaveBeenCalledWith(
        expect.objectContaining({
          id: 1,
          name: 'English',
          abbreviation: 'en',
          dictLinks: {
            dict1: 'https://dict1.com',
            dict2: 'https://dict2.com',
            translator: 'https://translate.com',
          },
        })
      );
    });

    it('handles API error gracefully', async () => {
      vi.mocked(offlineDb.languages.get).mockResolvedValue(undefined);
      vi.mocked(apiGet).mockResolvedValue({ error: 'Not found' });

      await ensureLanguageCached(1);

      expect(consoleWarnSpy).toHaveBeenCalledWith('Failed to cache language 1');
      expect(offlineDb.languages.put).not.toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // getOfflineLanguage Tests
  // ===========================================================================

  describe('getOfflineLanguage', () => {
    it('returns null when IndexedDB not available', async () => {
      vi.mocked(isIndexedDBAvailable).mockReturnValue(false);

      const result = await getOfflineLanguage(1);

      expect(result).toBeNull();
    });

    it('returns null when language not found', async () => {
      vi.mocked(offlineDb.languages.get).mockResolvedValue(undefined);

      const result = await getOfflineLanguage(1);

      expect(result).toBeNull();
    });

    it('returns language data', async () => {
      const mockLang = { id: 1, name: 'English' };
      vi.mocked(offlineDb.languages.get).mockResolvedValue(mockLang as any);

      const result = await getOfflineLanguage(1);

      expect(result).toEqual(mockLang);
    });
  });

  // ===========================================================================
  // isCurrentlyOffline Tests
  // ===========================================================================

  describe('isCurrentlyOffline', () => {
    it('returns false when online', () => {
      Object.defineProperty(navigator, 'onLine', { value: true });
      expect(isCurrentlyOffline()).toBe(false);
    });

    it('returns true when offline', () => {
      Object.defineProperty(navigator, 'onLine', { value: false });
      expect(isCurrentlyOffline()).toBe(true);
    });
  });

  // ===========================================================================
  // getLastSyncTime Tests
  // ===========================================================================

  describe('getLastSyncTime', () => {
    it('returns null when no sync recorded', async () => {
      vi.mocked(getMetadata).mockResolvedValue(undefined);

      const result = await getLastSyncTime();

      expect(result).toBeNull();
      expect(getMetadata).toHaveBeenCalledWith('lastTextDownload');
    });

    it('returns Date from stored timestamp', async () => {
      vi.mocked(getMetadata).mockResolvedValue('2024-06-15T10:30:00.000Z');

      const result = await getLastSyncTime();

      expect(result).toBeInstanceOf(Date);
      expect(result!.toISOString()).toBe('2024-06-15T10:30:00.000Z');
    });
  });

  // ===========================================================================
  // Type Export Tests
  // ===========================================================================

  describe('Type Exports', () => {
    it('exports OfflineAvailability type', () => {
      const availability: OfflineAvailability = {
        available: true,
        downloadedAt: new Date(),
        sizeBytes: 1000,
      };
      expect(availability.available).toBe(true);
    });

    it('exports OfflineSummary type', () => {
      const summary: OfflineSummary = {
        totalTexts: 5,
        totalSizeBytes: 10000,
        byLanguage: [
          { langId: 1, langName: 'English', textCount: 3, sizeBytes: 6000 },
        ],
      };
      expect(summary.totalTexts).toBe(5);
    });
  });
});
