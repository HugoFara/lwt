/**
 * Tests for app_data.ts - Application data fetching and caching
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  statuses,
  fetchTermTags,
  fetchTextTags,
  getTermTagsSync,
  getTextTagsSync,
  clearTagCaches,
  initTagsData,
} from '../../../src/frontend/js/core/app_data';

describe('app_data.ts', () => {
  beforeEach(() => {
    // Clear caches before each test
    clearTagCaches();
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ===========================================================================
  // statuses Tests
  // ===========================================================================

  describe('statuses', () => {
    it('contains learning statuses 1-5', () => {
      expect(statuses[1]).toEqual({ abbr: '1', name: 'Learning' });
      expect(statuses[2]).toEqual({ abbr: '2', name: 'Learning' });
      expect(statuses[3]).toEqual({ abbr: '3', name: 'Learning' });
      expect(statuses[4]).toEqual({ abbr: '4', name: 'Learning' });
      expect(statuses[5]).toEqual({ abbr: '5', name: 'Learned' });
    });

    it('contains Well Known status (99)', () => {
      expect(statuses[99]).toEqual({ abbr: 'WKn', name: 'Well Known' });
    });

    it('contains Ignored status (98)', () => {
      expect(statuses[98]).toEqual({ abbr: 'Ign', name: 'Ignored' });
    });

    it('has correct number of statuses', () => {
      expect(Object.keys(statuses)).toHaveLength(7);
    });
  });

  // ===========================================================================
  // fetchTermTags Tests
  // ===========================================================================

  describe('fetchTermTags', () => {
    it('fetches tags from API on first call', async () => {
      const mockTags = ['tag1', 'tag2', 'tag3'];
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockTags),
      } as Response);

      const result = await fetchTermTags();

      expect(fetchSpy).toHaveBeenCalledWith('api.php/v1/tags/term');
      expect(result).toEqual(mockTags);
    });

    it('returns cached tags on subsequent calls', async () => {
      const mockTags = ['cached-tag'];
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockTags),
      } as Response);

      // First call - fetches from API
      await fetchTermTags();
      expect(fetchSpy).toHaveBeenCalledTimes(1);

      // Second call - uses cache
      const result = await fetchTermTags();
      expect(fetchSpy).toHaveBeenCalledTimes(1);
      expect(result).toEqual(mockTags);
    });

    it('refreshes cache when refresh=true', async () => {
      const initialTags = ['initial'];
      const updatedTags = ['updated'];

      const fetchSpy = vi
        .spyOn(globalThis, 'fetch')
        .mockResolvedValueOnce({
          ok: true,
          json: () => Promise.resolve(initialTags),
        } as Response)
        .mockResolvedValueOnce({
          ok: true,
          json: () => Promise.resolve(updatedTags),
        } as Response);

      // First call
      await fetchTermTags();

      // Force refresh
      const result = await fetchTermTags(true);

      expect(fetchSpy).toHaveBeenCalledTimes(2);
      expect(result).toEqual(updatedTags);
    });

    it('returns empty array and logs error on API failure', async () => {
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        ok: false,
        statusText: 'Not Found',
      } as Response);

      const result = await fetchTermTags();

      expect(consoleSpy).toHaveBeenCalledWith(
        'Failed to fetch term tags:',
        'Not Found'
      );
      expect(result).toEqual([]);
    });

    it('returns cached data on API failure if cache exists', async () => {
      const cachedTags = ['cached'];
      vi.spyOn(globalThis, 'fetch')
        .mockResolvedValueOnce({
          ok: true,
          json: () => Promise.resolve(cachedTags),
        } as Response)
        .mockResolvedValueOnce({
          ok: false,
          statusText: 'Server Error',
        } as Response);

      // First successful call
      await fetchTermTags();

      // Force refresh - API fails
      vi.spyOn(console, 'error').mockImplementation(() => {});
      const result = await fetchTermTags(true);

      expect(result).toEqual(cachedTags);
    });

    it('handles network errors gracefully', async () => {
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      vi.spyOn(globalThis, 'fetch').mockRejectedValue(
        new Error('Network error')
      );

      const result = await fetchTermTags();

      expect(consoleSpy).toHaveBeenCalledWith(
        'Error fetching term tags:',
        expect.any(Error)
      );
      expect(result).toEqual([]);
    });
  });

  // ===========================================================================
  // fetchTextTags Tests
  // ===========================================================================

  describe('fetchTextTags', () => {
    it('fetches tags from API on first call', async () => {
      const mockTags = ['text-tag1', 'text-tag2'];
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockTags),
      } as Response);

      const result = await fetchTextTags();

      expect(fetchSpy).toHaveBeenCalledWith('api.php/v1/tags/text');
      expect(result).toEqual(mockTags);
    });

    it('returns cached tags on subsequent calls', async () => {
      const mockTags = ['cached-text-tag'];
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockTags),
      } as Response);

      await fetchTextTags();
      await fetchTextTags();

      expect(fetchSpy).toHaveBeenCalledTimes(1);
    });

    it('refreshes cache when refresh=true', async () => {
      const updatedTags = ['new-text-tag'];

      vi.spyOn(globalThis, 'fetch')
        .mockResolvedValueOnce({
          ok: true,
          json: () => Promise.resolve(['old']),
        } as Response)
        .mockResolvedValueOnce({
          ok: true,
          json: () => Promise.resolve(updatedTags),
        } as Response);

      await fetchTextTags();
      const result = await fetchTextTags(true);

      expect(result).toEqual(updatedTags);
    });

    it('returns empty array on API failure', async () => {
      vi.spyOn(console, 'error').mockImplementation(() => {});
      vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        ok: false,
        statusText: 'Forbidden',
      } as Response);

      const result = await fetchTextTags();

      expect(result).toEqual([]);
    });

    it('handles network errors gracefully', async () => {
      vi.spyOn(console, 'error').mockImplementation(() => {});
      vi.spyOn(globalThis, 'fetch').mockRejectedValue(
        new Error('Connection refused')
      );

      const result = await fetchTextTags();

      expect(result).toEqual([]);
    });
  });

  // ===========================================================================
  // getTermTagsSync Tests
  // ===========================================================================

  describe('getTermTagsSync', () => {
    it('returns empty array when cache is not populated', () => {
      const result = getTermTagsSync();
      expect(result).toEqual([]);
    });

    it('returns cached tags after fetchTermTags is called', async () => {
      const mockTags = ['sync-tag1', 'sync-tag2'];
      vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockTags),
      } as Response);

      await fetchTermTags();
      const result = getTermTagsSync();

      expect(result).toEqual(mockTags);
    });
  });

  // ===========================================================================
  // getTextTagsSync Tests
  // ===========================================================================

  describe('getTextTagsSync', () => {
    it('returns empty array when cache is not populated', () => {
      const result = getTextTagsSync();
      expect(result).toEqual([]);
    });

    it('returns cached tags after fetchTextTags is called', async () => {
      const mockTags = ['text-sync-tag'];
      vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockTags),
      } as Response);

      await fetchTextTags();
      const result = getTextTagsSync();

      expect(result).toEqual(mockTags);
    });
  });

  // ===========================================================================
  // clearTagCaches Tests
  // ===========================================================================

  describe('clearTagCaches', () => {
    it('clears term tags cache', async () => {
      const mockTags = ['to-be-cleared'];
      vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockTags),
      } as Response);

      await fetchTermTags();
      expect(getTermTagsSync()).toEqual(mockTags);

      clearTagCaches();
      expect(getTermTagsSync()).toEqual([]);
    });

    it('clears text tags cache', async () => {
      const mockTags = ['text-to-clear'];
      vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockTags),
      } as Response);

      await fetchTextTags();
      expect(getTextTagsSync()).toEqual(mockTags);

      clearTagCaches();
      expect(getTextTagsSync()).toEqual([]);
    });

    it('causes next fetch to call API again', async () => {
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        ok: true,
        json: () => Promise.resolve([]),
      } as Response);

      await fetchTermTags();
      expect(fetchSpy).toHaveBeenCalledTimes(1);

      clearTagCaches();
      await fetchTermTags();
      expect(fetchSpy).toHaveBeenCalledTimes(2);
    });
  });

  // ===========================================================================
  // initTagsData Tests
  // ===========================================================================

  describe('initTagsData', () => {
    it('fetches both term and text tags', async () => {
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        ok: true,
        json: () => Promise.resolve([]),
      } as Response);

      await initTagsData();

      expect(fetchSpy).toHaveBeenCalledTimes(2);
      expect(fetchSpy).toHaveBeenCalledWith('api.php/v1/tags/term');
      expect(fetchSpy).toHaveBeenCalledWith('api.php/v1/tags/text');
    });

    it('fetches both tag types in parallel', async () => {
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockImplementation(
        (url) =>
          new Promise((resolve) => {
            setTimeout(
              () => {
                resolve({
                  ok: true,
                  json: () => Promise.resolve([url]),
                } as Response);
              },
              url === 'api.php/v1/tags/term' ? 100 : 50
            );
          })
      );

      const startTime = Date.now();
      await initTagsData();
      const elapsed = Date.now() - startTime;

      // Should complete in around 100ms (the slower request), not 150ms (sum of both)
      expect(elapsed).toBeLessThan(150);
      expect(fetchSpy).toHaveBeenCalledTimes(2);
    });

    it('populates both sync caches', async () => {
      vi.spyOn(globalThis, 'fetch')
        .mockResolvedValueOnce({
          ok: true,
          json: () => Promise.resolve(['term-tag']),
        } as Response)
        .mockResolvedValueOnce({
          ok: true,
          json: () => Promise.resolve(['text-tag']),
        } as Response);

      await initTagsData();

      expect(getTermTagsSync()).toEqual(['term-tag']);
      expect(getTextTagsSync()).toEqual(['text-tag']);
    });

    it('handles partial failures gracefully', async () => {
      vi.spyOn(console, 'error').mockImplementation(() => {});
      vi.spyOn(globalThis, 'fetch')
        .mockResolvedValueOnce({
          ok: true,
          json: () => Promise.resolve(['term-success']),
        } as Response)
        .mockResolvedValueOnce({
          ok: false,
          statusText: 'Text API Error',
        } as Response);

      // Should not throw
      await expect(initTagsData()).resolves.not.toThrow();

      expect(getTermTagsSync()).toEqual(['term-success']);
      expect(getTextTagsSync()).toEqual([]);
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles empty tags array from API', async () => {
      vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        ok: true,
        json: () => Promise.resolve([]),
      } as Response);

      const result = await fetchTermTags();

      expect(result).toEqual([]);
    });

    it('handles tags with special characters', async () => {
      const specialTags = ['tag with spaces', 'tag/with/slashes', 'tag&ampersand'];
      vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(specialTags),
      } as Response);

      const result = await fetchTermTags();

      expect(result).toEqual(specialTags);
    });

    it('handles unicode tags', async () => {
      const unicodeTags = ['日本語', 'العربية', 'emoji🎉'];
      vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(unicodeTags),
      } as Response);

      const result = await fetchTermTags();

      expect(result).toEqual(unicodeTags);
    });

    it('handles large number of tags', async () => {
      const manyTags = Array.from({ length: 1000 }, (_, i) => `tag-${i}`);
      vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(manyTags),
      } as Response);

      const result = await fetchTermTags();

      expect(result).toHaveLength(1000);
      expect(result[999]).toBe('tag-999');
    });
  });
});
