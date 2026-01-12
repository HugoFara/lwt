/**
 * Tests for dictionaries/local_dictionary_api.ts - Local dictionary API
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Hoist mock functions so they're available during vi.mock hoisting
const { mockApiGet } = vi.hoisted(() => ({
  mockApiGet: vi.fn()
}));

// Mock the API client
vi.mock('../../../src/frontend/js/shared/api/client', () => ({
  apiGet: mockApiGet
}));

import {
  lookupLocal,
  getLocalDictMode,
  clearModeCache,
  hasLocalDictionaries,
  shouldUseOnline,
  formatResult,
  formatResults,
  DictionaryMode,
  type LocalDictResult
} from '../../../src/frontend/js/dictionaries/local_dictionary_api';

describe('dictionaries/local_dictionary_api.ts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    // Clear the mode cache between tests
    clearModeCache();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ===========================================================================
  // DictionaryMode Constants Tests
  // ===========================================================================

  describe('DictionaryMode constants', () => {
    it('has correct values', () => {
      expect(DictionaryMode.ONLINE_ONLY).toBe(0);
      expect(DictionaryMode.LOCAL_FIRST).toBe(1);
      expect(DictionaryMode.LOCAL_ONLY).toBe(2);
      expect(DictionaryMode.COMBINED).toBe(3);
    });
  });

  // ===========================================================================
  // lookupLocal Tests
  // ===========================================================================

  describe('lookupLocal', () => {
    it('calls API with correct parameters', async () => {
      mockApiGet.mockResolvedValue({
        data: { results: [], mode: 0 },
        error: undefined
      });

      await lookupLocal(5, 'hello');

      expect(mockApiGet).toHaveBeenCalledWith(
        '/local-dictionaries/lookup',
        { language_id: 5, term: 'hello' }
      );
    });

    it('returns API response', async () => {
      const mockResults = [
        { term: 'hello', definition: 'greeting', reading: null, pos: null, dictionary: 'test' }
      ];
      mockApiGet.mockResolvedValue({
        data: { results: mockResults, mode: 1 },
        error: undefined
      });

      const response = await lookupLocal(1, 'hello');

      expect(response.data?.results).toEqual(mockResults);
      expect(response.data?.mode).toBe(1);
    });

    it('handles API errors', async () => {
      mockApiGet.mockResolvedValue({
        data: null,
        error: 'Network error'
      });

      const response = await lookupLocal(1, 'hello');

      expect(response.error).toBe('Network error');
    });
  });

  // ===========================================================================
  // getLocalDictMode Tests
  // ===========================================================================

  describe('getLocalDictMode', () => {
    it('fetches mode from API', async () => {
      mockApiGet.mockResolvedValue({
        data: { results: [], mode: 2 },
        error: undefined
      });

      const mode = await getLocalDictMode(1);

      expect(mode).toBe(2);
      expect(mockApiGet).toHaveBeenCalledWith(
        '/local-dictionaries/lookup',
        { language_id: 1, term: '__mode_check__' }
      );
    });

    it('caches mode per language', async () => {
      mockApiGet.mockResolvedValue({
        data: { results: [], mode: 1 },
        error: undefined
      });

      // First call fetches from API
      await getLocalDictMode(5);
      expect(mockApiGet).toHaveBeenCalledTimes(1);

      // Second call uses cache
      const mode = await getLocalDictMode(5);
      expect(mockApiGet).toHaveBeenCalledTimes(1);
      expect(mode).toBe(1);
    });

    it('caches separately per language', async () => {
      mockApiGet
        .mockResolvedValueOnce({ data: { results: [], mode: 1 }, error: undefined })
        .mockResolvedValueOnce({ data: { results: [], mode: 2 }, error: undefined });

      const mode1 = await getLocalDictMode(1);
      const mode2 = await getLocalDictMode(2);

      expect(mode1).toBe(1);
      expect(mode2).toBe(2);
      expect(mockApiGet).toHaveBeenCalledTimes(2);
    });

    it('defaults to ONLINE_ONLY when no data', async () => {
      mockApiGet.mockResolvedValue({
        data: null,
        error: undefined
      });

      const mode = await getLocalDictMode(1);

      expect(mode).toBe(DictionaryMode.ONLINE_ONLY);
    });

    it('defaults to ONLINE_ONLY when mode undefined', async () => {
      mockApiGet.mockResolvedValue({
        data: { results: [] },
        error: undefined
      });

      const mode = await getLocalDictMode(1);

      expect(mode).toBe(DictionaryMode.ONLINE_ONLY);
    });
  });

  // ===========================================================================
  // clearModeCache Tests
  // ===========================================================================

  describe('clearModeCache', () => {
    it('clears specific language cache', async () => {
      mockApiGet.mockResolvedValue({
        data: { results: [], mode: 1 },
        error: undefined
      });

      // Populate cache
      await getLocalDictMode(1);
      await getLocalDictMode(2);
      expect(mockApiGet).toHaveBeenCalledTimes(2);

      // Clear only language 1
      clearModeCache(1);

      // Language 1 should re-fetch
      await getLocalDictMode(1);
      expect(mockApiGet).toHaveBeenCalledTimes(3);

      // Language 2 should use cache
      await getLocalDictMode(2);
      expect(mockApiGet).toHaveBeenCalledTimes(3);
    });

    it('clears all cache when no langId provided', async () => {
      mockApiGet.mockResolvedValue({
        data: { results: [], mode: 1 },
        error: undefined
      });

      // Populate cache
      await getLocalDictMode(1);
      await getLocalDictMode(2);
      expect(mockApiGet).toHaveBeenCalledTimes(2);

      // Clear all
      clearModeCache();

      // Both should re-fetch
      await getLocalDictMode(1);
      await getLocalDictMode(2);
      expect(mockApiGet).toHaveBeenCalledTimes(4);
    });
  });

  // ===========================================================================
  // hasLocalDictionaries Tests
  // ===========================================================================

  describe('hasLocalDictionaries', () => {
    it('returns false for ONLINE_ONLY mode', async () => {
      mockApiGet.mockResolvedValue({
        data: { results: [], mode: DictionaryMode.ONLINE_ONLY },
        error: undefined
      });

      const result = await hasLocalDictionaries(1);

      expect(result).toBe(false);
    });

    it('returns true for LOCAL_FIRST mode', async () => {
      mockApiGet.mockResolvedValue({
        data: { results: [], mode: DictionaryMode.LOCAL_FIRST },
        error: undefined
      });

      const result = await hasLocalDictionaries(1);

      expect(result).toBe(true);
    });

    it('returns true for LOCAL_ONLY mode', async () => {
      mockApiGet.mockResolvedValue({
        data: { results: [], mode: DictionaryMode.LOCAL_ONLY },
        error: undefined
      });

      const result = await hasLocalDictionaries(1);

      expect(result).toBe(true);
    });

    it('returns true for COMBINED mode', async () => {
      mockApiGet.mockResolvedValue({
        data: { results: [], mode: DictionaryMode.COMBINED },
        error: undefined
      });

      const result = await hasLocalDictionaries(1);

      expect(result).toBe(true);
    });
  });

  // ===========================================================================
  // shouldUseOnline Tests
  // ===========================================================================

  describe('shouldUseOnline', () => {
    it('returns true for ONLINE_ONLY mode', async () => {
      mockApiGet.mockResolvedValue({
        data: { results: [], mode: DictionaryMode.ONLINE_ONLY },
        error: undefined
      });

      const result = await shouldUseOnline(1);

      expect(result).toBe(true);
    });

    it('returns false for LOCAL_ONLY mode', async () => {
      mockApiGet.mockResolvedValue({
        data: { results: [], mode: DictionaryMode.LOCAL_ONLY },
        error: undefined
      });

      const result = await shouldUseOnline(1);

      expect(result).toBe(false);
    });

    it('returns true for COMBINED mode', async () => {
      mockApiGet.mockResolvedValue({
        data: { results: [], mode: DictionaryMode.COMBINED },
        error: undefined
      });

      const result = await shouldUseOnline(1);

      expect(result).toBe(true);
    });

    describe('LOCAL_FIRST mode', () => {
      beforeEach(() => {
        mockApiGet.mockResolvedValue({
          data: { results: [], mode: DictionaryMode.LOCAL_FIRST },
          error: undefined
        });
      });

      it('returns true when no local results', async () => {
        const result = await shouldUseOnline(1, false);

        expect(result).toBe(true);
      });

      it('returns false when local results found', async () => {
        const result = await shouldUseOnline(1, true);

        expect(result).toBe(false);
      });
    });
  });

  // ===========================================================================
  // formatResult Tests
  // ===========================================================================

  describe('formatResult', () => {
    const baseResult: LocalDictResult = {
      term: 'hello',
      definition: 'a greeting',
      reading: null,
      pos: null,
      dictionary: 'Test Dict'
    };

    it('formats basic result', () => {
      const html = formatResult(baseResult);

      expect(html).toContain('class="local-dict-entry"');
      expect(html).toContain('class="local-dict-headword"');
      expect(html).toContain('hello');
      expect(html).toContain('a greeting');
      expect(html).toContain('Test Dict');
    });

    it('includes reading when provided', () => {
      const result = { ...baseResult, reading: 'はろー' };

      const html = formatResult(result);

      expect(html).toContain('class="local-dict-reading"');
      expect(html).toContain('[はろー]');
    });

    it('includes part of speech when provided', () => {
      const result = { ...baseResult, pos: 'noun' };

      const html = formatResult(result);

      expect(html).toContain('class="local-dict-pos"');
      expect(html).toContain('(noun)');
    });

    it('hides dictionary name when showDictName is false', () => {
      const html = formatResult(baseResult, false);

      expect(html).not.toContain('class="local-dict-source"');
      expect(html).not.toContain('Test Dict');
    });

    it('escapes HTML in term', () => {
      const result = { ...baseResult, term: '<script>alert("xss")</script>' };

      const html = formatResult(result);

      expect(html).not.toContain('<script>');
      expect(html).toContain('&lt;script&gt;');
    });

    it('escapes HTML in definition', () => {
      const result = { ...baseResult, definition: '<b>bold</b>' };

      const html = formatResult(result);

      expect(html).not.toContain('<b>bold</b>');
      expect(html).toContain('&lt;b&gt;');
    });

    it('escapes HTML in reading', () => {
      const result = { ...baseResult, reading: '<em>test</em>' };

      const html = formatResult(result);

      expect(html).not.toContain('<em>test</em>');
    });

    it('escapes HTML in pos', () => {
      const result = { ...baseResult, pos: '<span>verb</span>' };

      const html = formatResult(result);

      expect(html).not.toContain('<span>verb</span>');
    });
  });

  // ===========================================================================
  // formatResults Tests
  // ===========================================================================

  describe('formatResults', () => {
    it('returns empty message for empty array', () => {
      const html = formatResults([]);

      expect(html).toContain('class="local-dict-empty"');
      expect(html).toContain('No local dictionary results found');
    });

    it('formats single result without separator', () => {
      const results: LocalDictResult[] = [
        { term: 'hello', definition: 'greeting', reading: null, pos: null, dictionary: 'Dict' }
      ];

      const html = formatResults(results);

      expect(html).toContain('hello');
      expect(html).not.toContain('<hr');
    });

    it('formats multiple results with separators', () => {
      const results: LocalDictResult[] = [
        { term: 'hello', definition: 'greeting', reading: null, pos: null, dictionary: 'Dict1' },
        { term: 'hello', definition: 'interjection', reading: null, pos: null, dictionary: 'Dict2' }
      ];

      const html = formatResults(results);

      expect(html).toContain('greeting');
      expect(html).toContain('interjection');
      expect(html).toContain('class="local-dict-separator"');
    });

    it('shows dictionary name when multiple results', () => {
      const results: LocalDictResult[] = [
        { term: 'hello', definition: 'greeting', reading: null, pos: null, dictionary: 'Dict1' },
        { term: 'hello', definition: 'interjection', reading: null, pos: null, dictionary: 'Dict2' }
      ];

      const html = formatResults(results);

      expect(html).toContain('Dict1');
      expect(html).toContain('Dict2');
    });

    it('hides dictionary name for single result', () => {
      const results: LocalDictResult[] = [
        { term: 'hello', definition: 'greeting', reading: null, pos: null, dictionary: 'Dict' }
      ];

      const html = formatResults(results);

      // Single result should not show dict name
      expect(html).not.toContain('class="local-dict-source"');
    });
  });
});
