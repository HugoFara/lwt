/**
 * Tests for api/texts.ts - Texts API wrapper
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock the api_client module
vi.mock('../../../src/frontend/js/core/api_client', () => ({
  apiGet: vi.fn(),
  apiPost: vi.fn(),
  apiPut: vi.fn(),
  apiDelete: vi.fn()
}));

import { TextsApi } from '../../../src/frontend/js/api/texts';
import { apiGet, apiPost, apiPut } from '../../../src/frontend/js/core/api_client';

describe('api/texts.ts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ===========================================================================
  // getStatistics Tests
  // ===========================================================================

  describe('TextsApi.getStatistics', () => {
    it('calls apiGet with correct endpoint for single text ID', async () => {
      vi.mocked(apiGet).mockResolvedValue({ data: { wordCounts: {} } });

      await TextsApi.getStatistics([1]);

      expect(apiGet).toHaveBeenCalledWith('/texts/statistics', { texts_id: '1' });
    });

    it('calls apiGet with comma-separated IDs for multiple texts', async () => {
      vi.mocked(apiGet).mockResolvedValue({ data: { wordCounts: {} } });

      await TextsApi.getStatistics([1, 2, 3]);

      expect(apiGet).toHaveBeenCalledWith('/texts/statistics', { texts_id: '1,2,3' });
    });

    it('accepts string parameter directly', async () => {
      vi.mocked(apiGet).mockResolvedValue({ data: { wordCounts: {} } });

      await TextsApi.getStatistics('5,6,7');

      expect(apiGet).toHaveBeenCalledWith('/texts/statistics', { texts_id: '5,6,7' });
    });

    it('returns statistics data on success', async () => {
      const mockStats = {
        wordCounts: {
          total: 100,
          unique: 80,
          unknown: 20,
          learning: 30,
          learned: 25,
          wellKnown: 5,
          ignored: 0
        },
        statusBreakdown: { 1: 20, 2: 15, 3: 10, 4: 5, 5: 25 }
      };

      vi.mocked(apiGet).mockResolvedValue({ data: mockStats });

      const result = await TextsApi.getStatistics([1]);

      expect(result.data).toEqual(mockStats);
    });

    it('returns error on failure', async () => {
      vi.mocked(apiGet).mockResolvedValue({ error: 'Text not found' });

      const result = await TextsApi.getStatistics([999]);

      expect(result.error).toBe('Text not found');
    });

    it('handles empty array', async () => {
      vi.mocked(apiGet).mockResolvedValue({ data: { wordCounts: {} } });

      await TextsApi.getStatistics([]);

      expect(apiGet).toHaveBeenCalledWith('/texts/statistics', { texts_id: '' });
    });
  });

  // ===========================================================================
  // create Tests
  // ===========================================================================

  describe('TextsApi.create', () => {
    it('calls apiPost with correct endpoint', async () => {
      vi.mocked(apiPost).mockResolvedValue({ data: { id: 1 } });

      await TextsApi.create({
        title: 'Test Text',
        langId: 1,
        text: 'Hello world'
      });

      expect(apiPost).toHaveBeenCalledWith('/texts', expect.any(Object));
    });

    it('sends correct basic data', async () => {
      vi.mocked(apiPost).mockResolvedValue({ data: { id: 1 } });

      await TextsApi.create({
        title: 'Test Text',
        langId: 1,
        text: 'Hello world'
      });

      expect(apiPost).toHaveBeenCalledWith('/texts', {
        title: 'Test Text',
        lg_id: 1,
        text: 'Hello world',
        source_uri: undefined,
        audio_uri: undefined,
        tags: undefined
      });
    });

    it('sends optional fields when provided', async () => {
      vi.mocked(apiPost).mockResolvedValue({ data: { id: 1 } });

      await TextsApi.create({
        title: 'Test Text',
        langId: 1,
        text: 'Hello world',
        sourceUri: 'https://example.com/source',
        audioUri: 'https://example.com/audio.mp3',
        tags: ['tag1', 'tag2']
      });

      expect(apiPost).toHaveBeenCalledWith('/texts', {
        title: 'Test Text',
        lg_id: 1,
        text: 'Hello world',
        source_uri: 'https://example.com/source',
        audio_uri: 'https://example.com/audio.mp3',
        tags: ['tag1', 'tag2']
      });
    });

    it('returns new text ID on success', async () => {
      vi.mocked(apiPost).mockResolvedValue({ data: { id: 42 } });

      const result = await TextsApi.create({
        title: 'Test',
        langId: 1,
        text: 'Content'
      });

      expect(result.data?.id).toBe(42);
    });

    it('returns error on failure', async () => {
      vi.mocked(apiPost).mockResolvedValue({ error: 'Language not found' });

      const result = await TextsApi.create({
        title: 'Test',
        langId: 999,
        text: 'Content'
      });

      expect(result.error).toBe('Language not found');
    });

    it('handles empty title', async () => {
      vi.mocked(apiPost).mockResolvedValue({ data: { id: 1 } });

      await TextsApi.create({
        title: '',
        langId: 1,
        text: 'Content'
      });

      expect(apiPost).toHaveBeenCalledWith('/texts', expect.objectContaining({
        title: ''
      }));
    });

    it('handles long text content', async () => {
      vi.mocked(apiPost).mockResolvedValue({ data: { id: 1 } });

      const longText = 'A'.repeat(10000);
      await TextsApi.create({
        title: 'Long Text',
        langId: 1,
        text: longText
      });

      expect(apiPost).toHaveBeenCalledWith('/texts', expect.objectContaining({
        text: longText
      }));
    });

    it('handles Unicode content', async () => {
      vi.mocked(apiPost).mockResolvedValue({ data: { id: 1 } });

      await TextsApi.create({
        title: '日本語のテキスト',
        langId: 1,
        text: 'こんにちは世界'
      });

      expect(apiPost).toHaveBeenCalledWith('/texts', expect.objectContaining({
        title: '日本語のテキスト',
        text: 'こんにちは世界'
      }));
    });
  });

  // ===========================================================================
  // setDisplayMode Tests
  // ===========================================================================

  describe('TextsApi.setDisplayMode', () => {
    it('calls apiPut with correct endpoint', async () => {
      vi.mocked(apiPut).mockResolvedValue({ data: { updated: true } });

      await TextsApi.setDisplayMode(1, { annotations: 2 });

      expect(apiPut).toHaveBeenCalledWith('/texts/1/display-mode', { annotations: 2 });
    });

    it('sends all display mode options', async () => {
      vi.mocked(apiPut).mockResolvedValue({ data: { updated: true } });

      await TextsApi.setDisplayMode(1, {
        annotations: 3,
        romanization: true,
        translation: false
      });

      expect(apiPut).toHaveBeenCalledWith('/texts/1/display-mode', {
        annotations: 3,
        romanization: true,
        translation: false
      });
    });

    it('sends partial display mode options', async () => {
      vi.mocked(apiPut).mockResolvedValue({ data: { updated: true } });

      await TextsApi.setDisplayMode(5, { romanization: true });

      expect(apiPut).toHaveBeenCalledWith('/texts/5/display-mode', {
        romanization: true
      });
    });

    it('returns success result', async () => {
      vi.mocked(apiPut).mockResolvedValue({ data: { updated: true } });

      const result = await TextsApi.setDisplayMode(1, { annotations: 1 });

      expect(result.data?.updated).toBe(true);
    });

    it('returns error on failure', async () => {
      vi.mocked(apiPut).mockResolvedValue({ error: 'Text not found' });

      const result = await TextsApi.setDisplayMode(999, { annotations: 1 });

      expect(result.error).toBe('Text not found');
    });

    it('handles empty options object', async () => {
      vi.mocked(apiPut).mockResolvedValue({ data: { updated: true } });

      await TextsApi.setDisplayMode(1, {});

      expect(apiPut).toHaveBeenCalledWith('/texts/1/display-mode', {});
    });

    it('handles different text IDs', async () => {
      vi.mocked(apiPut).mockResolvedValue({ data: { updated: true } });

      await TextsApi.setDisplayMode(123, { annotations: 1 });

      expect(apiPut).toHaveBeenCalledWith('/texts/123/display-mode', expect.any(Object));
    });
  });

  // ===========================================================================
  // markAllWellKnown Tests
  // ===========================================================================

  describe('TextsApi.markAllWellKnown', () => {
    it('calls apiPut with correct endpoint', async () => {
      vi.mocked(apiPut).mockResolvedValue({ data: { count: 10 } });

      await TextsApi.markAllWellKnown(1);

      expect(apiPut).toHaveBeenCalledWith('/texts/1/mark-all-wellknown', {});
    });

    it('returns count of marked words', async () => {
      vi.mocked(apiPut).mockResolvedValue({ data: { count: 25 } });

      const result = await TextsApi.markAllWellKnown(1);

      expect(result.data?.count).toBe(25);
    });

    it('returns zero count when no words to mark', async () => {
      vi.mocked(apiPut).mockResolvedValue({ data: { count: 0 } });

      const result = await TextsApi.markAllWellKnown(1);

      expect(result.data?.count).toBe(0);
    });

    it('returns error on failure', async () => {
      vi.mocked(apiPut).mockResolvedValue({ error: 'Text not found' });

      const result = await TextsApi.markAllWellKnown(999);

      expect(result.error).toBe('Text not found');
    });

    it('handles different text IDs', async () => {
      vi.mocked(apiPut).mockResolvedValue({ data: { count: 5 } });

      await TextsApi.markAllWellKnown(456);

      expect(apiPut).toHaveBeenCalledWith('/texts/456/mark-all-wellknown', {});
    });

    it('sends empty body', async () => {
      vi.mocked(apiPut).mockResolvedValue({ data: { count: 0 } });

      await TextsApi.markAllWellKnown(1);

      expect(apiPut).toHaveBeenCalledWith(expect.any(String), {});
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('getStatistics handles large array of IDs', async () => {
      vi.mocked(apiGet).mockResolvedValue({ data: { wordCounts: {} } });

      const ids = Array.from({ length: 100 }, (_, i) => i + 1);
      await TextsApi.getStatistics(ids);

      expect(apiGet).toHaveBeenCalledWith('/texts/statistics', {
        texts_id: ids.join(',')
      });
    });

    it('create handles special characters in title', async () => {
      vi.mocked(apiPost).mockResolvedValue({ data: { id: 1 } });

      await TextsApi.create({
        title: 'Test "Title" with <special> & characters',
        langId: 1,
        text: 'Content'
      });

      expect(apiPost).toHaveBeenCalledWith('/texts', expect.objectContaining({
        title: 'Test "Title" with <special> & characters'
      }));
    });

    it('create handles newlines in text', async () => {
      vi.mocked(apiPost).mockResolvedValue({ data: { id: 1 } });

      await TextsApi.create({
        title: 'Multi-line',
        langId: 1,
        text: 'Line 1\nLine 2\nLine 3'
      });

      expect(apiPost).toHaveBeenCalledWith('/texts', expect.objectContaining({
        text: 'Line 1\nLine 2\nLine 3'
      }));
    });

    it('setDisplayMode handles annotation value 0', async () => {
      vi.mocked(apiPut).mockResolvedValue({ data: { updated: true } });

      await TextsApi.setDisplayMode(1, { annotations: 0 });

      expect(apiPut).toHaveBeenCalledWith('/texts/1/display-mode', {
        annotations: 0
      });
    });

    it('handles concurrent API calls', async () => {
      vi.mocked(apiGet).mockResolvedValue({ data: { wordCounts: {} } });
      vi.mocked(apiPut).mockResolvedValue({ data: { count: 5 } });

      const [statsResult, markResult] = await Promise.all([
        TextsApi.getStatistics([1]),
        TextsApi.markAllWellKnown(1)
      ]);

      expect(apiGet).toHaveBeenCalled();
      expect(apiPut).toHaveBeenCalled();
      expect(statsResult.data).toBeDefined();
      expect(markResult.data).toBeDefined();
    });
  });
});
