/**
 * Tests for shared/offline/db.ts - IndexedDB offline database
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import Dexie from 'dexie';

// Mock Dexie before importing the module
vi.mock('dexie', () => {
  const mockTable = () => ({
    toArray: vi.fn().mockResolvedValue([]),
    get: vi.fn().mockResolvedValue(undefined),
    put: vi.fn().mockResolvedValue(1),
    delete: vi.fn().mockResolvedValue(undefined),
    clear: vi.fn().mockResolvedValue(undefined),
    count: vi.fn().mockResolvedValue(0),
    where: vi.fn().mockReturnThis(),
    equals: vi.fn().mockReturnThis(),
  });

  class MockDexie {
    texts = mockTable();
    textWords = mockTable();
    languages = mockTable();
    metadata = mockTable();
    pendingOps = mockTable();

    version = vi.fn().mockReturnValue({
      stores: vi.fn().mockReturnThis(),
    });

    transaction = vi.fn().mockImplementation(
      async (_mode: string, _tables: unknown[], callback: () => Promise<void>) => {
        await callback();
      }
    );

    constructor(public name: string) {}
  }

  return { default: MockDexie };
});

// Import after mocking
import {
  LwtOfflineDatabase,
  offlineDb,
  isIndexedDBAvailable,
  getOfflineStorageSize,
  getOfflineTextCount,
  clearOfflineData,
  getMetadata,
  setMetadata,
  type OfflineText,
  type OfflineTextWords,
  type OfflineLanguage,
  type SyncMetadata,
  type PendingOperation,
} from '../../../src/frontend/js/shared/offline/db';

describe('shared/offline/db.ts', () => {
  const originalIndexedDB = globalThis.indexedDB;

  afterEach(() => {
    vi.clearAllMocks();
    // Restore indexedDB
    Object.defineProperty(globalThis, 'indexedDB', {
      value: originalIndexedDB,
      writable: true,
      configurable: true,
    });
  });

  // ===========================================================================
  // LwtOfflineDatabase Class Tests
  // ===========================================================================

  describe('LwtOfflineDatabase', () => {
    it('creates database with correct name', () => {
      const db = new LwtOfflineDatabase();
      expect(db.name).toBe('LwtOfflineDB');
    });

    it('initializes all required tables', () => {
      const db = new LwtOfflineDatabase();
      expect(db.texts).toBeDefined();
      expect(db.textWords).toBeDefined();
      expect(db.languages).toBeDefined();
      expect(db.metadata).toBeDefined();
      expect(db.pendingOps).toBeDefined();
    });

    it('sets up schema version', () => {
      const db = new LwtOfflineDatabase();
      expect(db.version).toHaveBeenCalledWith(1);
    });
  });

  // ===========================================================================
  // offlineDb Singleton Tests
  // ===========================================================================

  describe('offlineDb singleton', () => {
    it('exports a singleton database instance', () => {
      expect(offlineDb).toBeInstanceOf(LwtOfflineDatabase);
    });

    it('has all required tables', () => {
      expect(offlineDb.texts).toBeDefined();
      expect(offlineDb.textWords).toBeDefined();
      expect(offlineDb.languages).toBeDefined();
      expect(offlineDb.metadata).toBeDefined();
      expect(offlineDb.pendingOps).toBeDefined();
    });
  });

  // ===========================================================================
  // isIndexedDBAvailable Tests
  // ===========================================================================

  describe('isIndexedDBAvailable', () => {
    it('returns true when indexedDB is available', () => {
      Object.defineProperty(globalThis, 'indexedDB', {
        value: {},
        writable: true,
        configurable: true,
      });

      expect(isIndexedDBAvailable()).toBe(true);
    });

    it('returns false when indexedDB is undefined', () => {
      Object.defineProperty(globalThis, 'indexedDB', {
        value: undefined,
        writable: true,
        configurable: true,
      });

      expect(isIndexedDBAvailable()).toBe(false);
    });

    it('returns false when indexedDB is null', () => {
      Object.defineProperty(globalThis, 'indexedDB', {
        value: null,
        writable: true,
        configurable: true,
      });

      expect(isIndexedDBAvailable()).toBe(false);
    });

    it('returns false when accessing indexedDB throws', () => {
      Object.defineProperty(globalThis, 'indexedDB', {
        get() {
          throw new Error('Access denied');
        },
        configurable: true,
      });

      expect(isIndexedDBAvailable()).toBe(false);
    });
  });

  // ===========================================================================
  // getOfflineStorageSize Tests
  // ===========================================================================

  describe('getOfflineStorageSize', () => {
    it('returns 0 when no texts are stored', async () => {
      vi.mocked(offlineDb.texts.toArray).mockResolvedValue([]);

      const size = await getOfflineStorageSize();

      expect(size).toBe(0);
    });

    it('calculates total size from all texts', async () => {
      const mockTexts: OfflineText[] = [
        {
          id: 1,
          langId: 1,
          title: 'Test 1',
          audioUri: null,
          sourceUri: null,
          config: {} as OfflineText['config'],
          downloadedAt: new Date(),
          lastAccessedAt: new Date(),
          sizeBytes: 1000,
        },
        {
          id: 2,
          langId: 1,
          title: 'Test 2',
          audioUri: null,
          sourceUri: null,
          config: {} as OfflineText['config'],
          downloadedAt: new Date(),
          lastAccessedAt: new Date(),
          sizeBytes: 2500,
        },
      ];
      vi.mocked(offlineDb.texts.toArray).mockResolvedValue(mockTexts);

      const size = await getOfflineStorageSize();

      expect(size).toBe(3500);
    });

    it('handles texts with zero size', async () => {
      const mockTexts: OfflineText[] = [
        {
          id: 1,
          langId: 1,
          title: 'Empty',
          audioUri: null,
          sourceUri: null,
          config: {} as OfflineText['config'],
          downloadedAt: new Date(),
          lastAccessedAt: new Date(),
          sizeBytes: 0,
        },
      ];
      vi.mocked(offlineDb.texts.toArray).mockResolvedValue(mockTexts);

      const size = await getOfflineStorageSize();

      expect(size).toBe(0);
    });
  });

  // ===========================================================================
  // getOfflineTextCount Tests
  // ===========================================================================

  describe('getOfflineTextCount', () => {
    it('returns count from database', async () => {
      vi.mocked(offlineDb.texts.count).mockResolvedValue(5);

      const count = await getOfflineTextCount();

      expect(count).toBe(5);
      expect(offlineDb.texts.count).toHaveBeenCalled();
    });

    it('returns 0 when no texts stored', async () => {
      vi.mocked(offlineDb.texts.count).mockResolvedValue(0);

      const count = await getOfflineTextCount();

      expect(count).toBe(0);
    });
  });

  // ===========================================================================
  // clearOfflineData Tests
  // ===========================================================================

  describe('clearOfflineData', () => {
    it('clears all tables in a transaction', async () => {
      await clearOfflineData();

      expect(offlineDb.transaction).toHaveBeenCalled();
      expect(offlineDb.texts.clear).toHaveBeenCalled();
      expect(offlineDb.textWords.clear).toHaveBeenCalled();
      expect(offlineDb.languages.clear).toHaveBeenCalled();
      expect(offlineDb.metadata.clear).toHaveBeenCalled();
    });

    it('uses read-write transaction mode', async () => {
      await clearOfflineData();

      expect(offlineDb.transaction).toHaveBeenCalledWith(
        'rw',
        expect.any(Array),
        expect.any(Function)
      );
    });
  });

  // ===========================================================================
  // getMetadata Tests
  // ===========================================================================

  describe('getMetadata', () => {
    it('returns undefined when key not found', async () => {
      vi.mocked(offlineDb.metadata.get).mockResolvedValue(undefined);

      const result = await getMetadata('nonexistent');

      expect(result).toBeUndefined();
      expect(offlineDb.metadata.get).toHaveBeenCalledWith('nonexistent');
    });

    it('returns value when key exists', async () => {
      const mockRecord: SyncMetadata = {
        key: 'testKey',
        value: { foo: 'bar' },
        updatedAt: new Date(),
      };
      vi.mocked(offlineDb.metadata.get).mockResolvedValue(mockRecord);

      const result = await getMetadata<{ foo: string }>('testKey');

      expect(result).toEqual({ foo: 'bar' });
    });

    it('returns string values correctly', async () => {
      const mockRecord: SyncMetadata = {
        key: 'version',
        value: '1.0.0',
        updatedAt: new Date(),
      };
      vi.mocked(offlineDb.metadata.get).mockResolvedValue(mockRecord);

      const result = await getMetadata<string>('version');

      expect(result).toBe('1.0.0');
    });

    it('returns number values correctly', async () => {
      const mockRecord: SyncMetadata = {
        key: 'count',
        value: 42,
        updatedAt: new Date(),
      };
      vi.mocked(offlineDb.metadata.get).mockResolvedValue(mockRecord);

      const result = await getMetadata<number>('count');

      expect(result).toBe(42);
    });
  });

  // ===========================================================================
  // setMetadata Tests
  // ===========================================================================

  describe('setMetadata', () => {
    it('stores key-value pair with timestamp', async () => {
      const beforeCall = new Date();
      await setMetadata('testKey', 'testValue');
      const afterCall = new Date();

      expect(offlineDb.metadata.put).toHaveBeenCalledWith(
        expect.objectContaining({
          key: 'testKey',
          value: 'testValue',
          updatedAt: expect.any(Date),
        })
      );

      // Verify timestamp is recent
      const callArg = vi.mocked(offlineDb.metadata.put).mock.calls[0][0];
      expect(callArg.updatedAt.getTime()).toBeGreaterThanOrEqual(beforeCall.getTime());
      expect(callArg.updatedAt.getTime()).toBeLessThanOrEqual(afterCall.getTime());
    });

    it('stores object values', async () => {
      await setMetadata('config', { debug: true, version: 2 });

      expect(offlineDb.metadata.put).toHaveBeenCalledWith(
        expect.objectContaining({
          key: 'config',
          value: { debug: true, version: 2 },
        })
      );
    });

    it('stores null values', async () => {
      await setMetadata('cleared', null);

      expect(offlineDb.metadata.put).toHaveBeenCalledWith(
        expect.objectContaining({
          key: 'cleared',
          value: null,
        })
      );
    });

    it('stores array values', async () => {
      await setMetadata('ids', [1, 2, 3]);

      expect(offlineDb.metadata.put).toHaveBeenCalledWith(
        expect.objectContaining({
          key: 'ids',
          value: [1, 2, 3],
        })
      );
    });
  });

  // ===========================================================================
  // Type Export Tests
  // ===========================================================================

  describe('Type Exports', () => {
    it('exports OfflineText type with correct shape', () => {
      const text: OfflineText = {
        id: 1,
        langId: 1,
        title: 'Test',
        audioUri: null,
        sourceUri: 'https://example.com',
        config: {
          langId: 1,
          title: 'Test',
          audioUri: null,
          sourceUri: 'https://example.com',
        } as OfflineText['config'],
        downloadedAt: new Date(),
        lastAccessedAt: new Date(),
        sizeBytes: 1000,
      };
      expect(text.id).toBe(1);
    });

    it('exports OfflineTextWords type with correct shape', () => {
      const words: OfflineTextWords = {
        textId: 1,
        words: [],
        syncedAt: new Date(),
      };
      expect(words.textId).toBe(1);
    });

    it('exports OfflineLanguage type with correct shape', () => {
      const lang: OfflineLanguage = {
        id: 1,
        name: 'English',
        abbreviation: 'en',
        rightToLeft: false,
        removeSpaces: false,
        dictLinks: { dict1: '', dict2: '', translator: '' },
        textSize: 100,
        downloadedAt: new Date(),
      };
      expect(lang.name).toBe('English');
    });

    it('exports SyncMetadata type with correct shape', () => {
      const meta: SyncMetadata = {
        key: 'lastSync',
        value: '2024-01-01',
        updatedAt: new Date(),
      };
      expect(meta.key).toBe('lastSync');
    });

    it('exports PendingOperation type with correct shape', () => {
      const op: PendingOperation = {
        id: 1,
        type: 'word_status',
        entityId: 123,
        data: { status: 3 },
        createdAt: new Date(),
        retries: 0,
      };
      expect(op.type).toBe('word_status');
    });
  });
});
