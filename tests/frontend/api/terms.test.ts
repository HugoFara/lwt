/**
 * Tests for api/terms.ts - Terms API wrapper
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock the api_client module
vi.mock('../../../src/frontend/js/core/api_client', () => ({
  apiGet: vi.fn(),
  apiPost: vi.fn(),
  apiPut: vi.fn(),
  apiDelete: vi.fn(),
  apiPostForm: vi.fn()
}));

import { TermsApi } from '../../../src/frontend/js/api/terms';
import {
  apiGet,
  apiPost,
  apiPut,
  apiDelete,
  apiPostForm
} from '../../../src/frontend/js/core/api_client';

describe('api/terms.ts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ===========================================================================
  // get Tests
  // ===========================================================================

  describe('TermsApi.get', () => {
    it('calls apiGet with correct endpoint', async () => {
      vi.mocked(apiGet).mockResolvedValue({ data: { id: 1, text: 'hello' } });

      await TermsApi.get(1);

      expect(apiGet).toHaveBeenCalledWith('/terms/1');
    });

    it('returns term data on success', async () => {
      const mockTerm = {
        id: 1,
        text: 'hello',
        textLc: 'hello',
        translation: 'bonjour',
        status: 2,
        langId: 1
      };
      vi.mocked(apiGet).mockResolvedValue({ data: mockTerm });

      const result = await TermsApi.get(1);

      expect(result.data).toEqual(mockTerm);
    });

    it('returns error on failure', async () => {
      vi.mocked(apiGet).mockResolvedValue({ error: 'Term not found' });

      const result = await TermsApi.get(999);

      expect(result.error).toBe('Term not found');
    });

    it('handles different term IDs', async () => {
      vi.mocked(apiGet).mockResolvedValue({ data: { id: 123 } });

      await TermsApi.get(123);

      expect(apiGet).toHaveBeenCalledWith('/terms/123');
    });
  });

  // ===========================================================================
  // setStatus Tests
  // ===========================================================================

  describe('TermsApi.setStatus', () => {
    it('calls apiPostForm with correct endpoint', async () => {
      vi.mocked(apiPostForm).mockResolvedValue({ data: { set: 3 } });

      await TermsApi.setStatus(100, 3);

      expect(apiPostForm).toHaveBeenCalledWith('/terms/100/status/3', {});
    });

    it('handles all valid status values', async () => {
      vi.mocked(apiPostForm).mockResolvedValue({ data: { set: 1 } });

      for (const status of [1, 2, 3, 4, 5, 98, 99]) {
        await TermsApi.setStatus(1, status);
        expect(apiPostForm).toHaveBeenCalledWith(`/terms/1/status/${status}`, {});
      }
    });

    it('returns new status on success', async () => {
      vi.mocked(apiPostForm).mockResolvedValue({ data: { set: 5 } });

      const result = await TermsApi.setStatus(1, 5);

      expect(result.data?.set).toBe(5);
    });

    it('returns error on failure', async () => {
      vi.mocked(apiPostForm).mockResolvedValue({ error: 'Invalid status' });

      const result = await TermsApi.setStatus(1, 10);

      expect(result.error).toBe('Invalid status');
    });
  });

  // ===========================================================================
  // incrementStatus Tests
  // ===========================================================================

  describe('TermsApi.incrementStatus', () => {
    it('calls apiPostForm with up direction', async () => {
      vi.mocked(apiPostForm).mockResolvedValue({ data: { set: 3 } });

      await TermsApi.incrementStatus(100, 'up');

      expect(apiPostForm).toHaveBeenCalledWith('/terms/100/status/up', {});
    });

    it('calls apiPostForm with down direction', async () => {
      vi.mocked(apiPostForm).mockResolvedValue({ data: { set: 1 } });

      await TermsApi.incrementStatus(100, 'down');

      expect(apiPostForm).toHaveBeenCalledWith('/terms/100/status/down', {});
    });

    it('returns new status and increment info', async () => {
      vi.mocked(apiPostForm).mockResolvedValue({
        data: { set: 4, increment: '+1' }
      });

      const result = await TermsApi.incrementStatus(1, 'up');

      expect(result.data?.set).toBe(4);
      expect(result.data?.increment).toBe('+1');
    });

    it('returns error on failure', async () => {
      vi.mocked(apiPostForm).mockResolvedValue({ error: 'Cannot increment' });

      const result = await TermsApi.incrementStatus(1, 'up');

      expect(result.error).toBe('Cannot increment');
    });
  });

  // ===========================================================================
  // delete Tests
  // ===========================================================================

  describe('TermsApi.delete', () => {
    it('calls apiDelete with correct endpoint', async () => {
      vi.mocked(apiDelete).mockResolvedValue({ data: { deleted: true } });

      await TermsApi.delete(100);

      expect(apiDelete).toHaveBeenCalledWith('/terms/100');
    });

    it('returns success on deletion', async () => {
      vi.mocked(apiDelete).mockResolvedValue({ data: { deleted: true } });

      const result = await TermsApi.delete(1);

      expect(result.data?.deleted).toBe(true);
    });

    it('returns error on failure', async () => {
      vi.mocked(apiDelete).mockResolvedValue({ error: 'Term not found' });

      const result = await TermsApi.delete(999);

      expect(result.error).toBe('Term not found');
    });
  });

  // ===========================================================================
  // updateTranslation Tests
  // ===========================================================================

  describe('TermsApi.updateTranslation', () => {
    it('calls apiPut with correct endpoint and data', async () => {
      vi.mocked(apiPut).mockResolvedValue({ data: { update: 'success' } });

      await TermsApi.updateTranslation(100, 'new translation');

      expect(apiPut).toHaveBeenCalledWith('/terms/100/translation', {
        translation: 'new translation'
      });
    });

    it('returns success on update', async () => {
      vi.mocked(apiPut).mockResolvedValue({ data: { update: 'success' } });

      const result = await TermsApi.updateTranslation(1, 'bonjour');

      expect(result.data?.update).toBe('success');
    });

    it('returns error on failure', async () => {
      vi.mocked(apiPut).mockResolvedValue({ error: 'Update failed' });

      const result = await TermsApi.updateTranslation(1, 'test');

      expect(result.error).toBe('Update failed');
    });

    it('handles empty translation', async () => {
      vi.mocked(apiPut).mockResolvedValue({ data: { update: 'success' } });

      await TermsApi.updateTranslation(1, '');

      expect(apiPut).toHaveBeenCalledWith('/terms/1/translation', {
        translation: ''
      });
    });

    it('handles Unicode translation', async () => {
      vi.mocked(apiPut).mockResolvedValue({ data: { update: 'success' } });

      await TermsApi.updateTranslation(1, '你好');

      expect(apiPut).toHaveBeenCalledWith('/terms/1/translation', {
        translation: '你好'
      });
    });
  });

  // ===========================================================================
  // addWithTranslation Tests
  // ===========================================================================

  describe('TermsApi.addWithTranslation', () => {
    it('calls apiPost with correct endpoint and data', async () => {
      vi.mocked(apiPost).mockResolvedValue({ data: { term_id: 200 } });

      await TermsApi.addWithTranslation('hello', 1, 'bonjour');

      expect(apiPost).toHaveBeenCalledWith('/terms', {
        text: 'hello',
        lg_id: 1,
        translation: 'bonjour'
      });
    });

    it('returns new term ID on success', async () => {
      vi.mocked(apiPost).mockResolvedValue({ data: { term_id: 200, term_lc: 'hello' } });

      const result = await TermsApi.addWithTranslation('hello', 1, 'bonjour');

      expect(result.data?.term_id).toBe(200);
      expect(result.data?.term_lc).toBe('hello');
    });

    it('returns error on failure', async () => {
      vi.mocked(apiPost).mockResolvedValue({ error: 'Term already exists' });

      const result = await TermsApi.addWithTranslation('existing', 1, 'trans');

      expect(result.error).toBe('Term already exists');
    });

    it('handles different language IDs', async () => {
      vi.mocked(apiPost).mockResolvedValue({ data: { term_id: 1 } });

      await TermsApi.addWithTranslation('test', 5, 'translation');

      expect(apiPost).toHaveBeenCalledWith('/terms', expect.objectContaining({
        lg_id: 5
      }));
    });
  });

  // ===========================================================================
  // createQuick Tests
  // ===========================================================================

  describe('TermsApi.createQuick', () => {
    it('calls apiPost with correct endpoint for well-known (99)', async () => {
      vi.mocked(apiPost).mockResolvedValue({ data: { term_id: 300 } });

      await TermsApi.createQuick(1, 5, 99);

      expect(apiPost).toHaveBeenCalledWith('/terms/quick', {
        textId: 1,
        position: 5,
        status: 99
      });
    });

    it('calls apiPost with correct endpoint for ignored (98)', async () => {
      vi.mocked(apiPost).mockResolvedValue({ data: { term_id: 300 } });

      await TermsApi.createQuick(1, 5, 98);

      expect(apiPost).toHaveBeenCalledWith('/terms/quick', {
        textId: 1,
        position: 5,
        status: 98
      });
    });

    it('returns new term ID on success', async () => {
      vi.mocked(apiPost).mockResolvedValue({ data: { term_id: 300, term_lc: 'word' } });

      const result = await TermsApi.createQuick(1, 5, 99);

      expect(result.data?.term_id).toBe(300);
    });

    it('returns error on failure', async () => {
      vi.mocked(apiPost).mockResolvedValue({ error: 'Position out of range' });

      const result = await TermsApi.createQuick(1, 9999, 99);

      expect(result.error).toBe('Position out of range');
    });

    it('sends correct text ID', async () => {
      vi.mocked(apiPost).mockResolvedValue({ data: { term_id: 1 } });

      await TermsApi.createQuick(42, 5, 99);

      expect(apiPost).toHaveBeenCalledWith('/terms/quick', expect.objectContaining({
        textId: 42
      }));
    });
  });

  // ===========================================================================
  // getSimilar Tests
  // ===========================================================================

  describe('TermsApi.getSimilar', () => {
    it('calls apiGet with correct parameters', async () => {
      vi.mocked(apiGet).mockResolvedValue({ data: [] });

      await TermsApi.getSimilar('hello', 1);

      expect(apiGet).toHaveBeenCalledWith('/similar-terms', {
        term: 'hello',
        lg_id: 1
      });
    });

    it('returns array of similar terms', async () => {
      const mockSimilar = [
        { id: 1, text: 'hello', translation: 'bonjour', status: 3 },
        { id: 2, text: 'helo', translation: 'salut', status: 2 }
      ];
      vi.mocked(apiGet).mockResolvedValue({ data: mockSimilar });

      const result = await TermsApi.getSimilar('hello', 1);

      expect(result.data).toEqual(mockSimilar);
      expect(result.data).toHaveLength(2);
    });

    it('returns empty array when no similar terms', async () => {
      vi.mocked(apiGet).mockResolvedValue({ data: [] });

      const result = await TermsApi.getSimilar('xyz', 1);

      expect(result.data).toEqual([]);
    });

    it('returns error on failure', async () => {
      vi.mocked(apiGet).mockResolvedValue({ error: 'Language not found' });

      const result = await TermsApi.getSimilar('test', 999);

      expect(result.error).toBe('Language not found');
    });

    it('handles Unicode term text', async () => {
      vi.mocked(apiGet).mockResolvedValue({ data: [] });

      await TermsApi.getSimilar('こんにちは', 2);

      expect(apiGet).toHaveBeenCalledWith('/similar-terms', {
        term: 'こんにちは',
        lg_id: 2
      });
    });
  });

  // ===========================================================================
  // getSentences Tests
  // ===========================================================================

  describe('TermsApi.getSentences', () => {
    it('calls apiGet with correct parameters', async () => {
      vi.mocked(apiGet).mockResolvedValue({ data: [] });

      await TermsApi.getSentences(100, 1);

      expect(apiGet).toHaveBeenCalledWith('/sentences-with-term', {
        term_id: 100,
        lg_id: 1
      });
    });

    it('returns array of sentences', async () => {
      const mockSentences = [
        { id: 1, sentence: 'Hello world', textId: 1, textTitle: 'Text 1' },
        { id: 2, sentence: 'Hello there', textId: 2, textTitle: 'Text 2' }
      ];
      vi.mocked(apiGet).mockResolvedValue({ data: mockSentences });

      const result = await TermsApi.getSentences(100, 1);

      expect(result.data).toEqual(mockSentences);
      expect(result.data).toHaveLength(2);
    });

    it('returns empty array when no sentences found', async () => {
      vi.mocked(apiGet).mockResolvedValue({ data: [] });

      const result = await TermsApi.getSentences(100, 1);

      expect(result.data).toEqual([]);
    });

    it('returns error on failure', async () => {
      vi.mocked(apiGet).mockResolvedValue({ error: 'Term not found' });

      const result = await TermsApi.getSentences(999, 1);

      expect(result.error).toBe('Term not found');
    });
  });

  // ===========================================================================
  // getImported Tests
  // ===========================================================================

  describe('TermsApi.getImported', () => {
    it('calls apiGet with correct endpoint', async () => {
      vi.mocked(apiGet).mockResolvedValue({ data: [] });

      await TermsApi.getImported();

      expect(apiGet).toHaveBeenCalledWith('/terms/imported');
    });

    it('returns array of imported terms', async () => {
      const mockTerms = [
        { id: 1, text: 'term1', textLc: 'term1', translation: 'trans1', status: 1, langId: 1 },
        { id: 2, text: 'term2', textLc: 'term2', translation: 'trans2', status: 2, langId: 1 }
      ];
      vi.mocked(apiGet).mockResolvedValue({ data: mockTerms });

      const result = await TermsApi.getImported();

      expect(result.data).toEqual(mockTerms);
    });

    it('returns empty array when no imported terms', async () => {
      vi.mocked(apiGet).mockResolvedValue({ data: [] });

      const result = await TermsApi.getImported();

      expect(result.data).toEqual([]);
    });

    it('returns error on failure', async () => {
      vi.mocked(apiGet).mockResolvedValue({ error: 'Server error' });

      const result = await TermsApi.getImported();

      expect(result.error).toBe('Server error');
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles concurrent API calls', async () => {
      vi.mocked(apiGet).mockResolvedValue({ data: { id: 1 } });
      vi.mocked(apiPostForm).mockResolvedValue({ data: { set: 2 } });

      const [getResult, statusResult] = await Promise.all([
        TermsApi.get(1),
        TermsApi.setStatus(1, 2)
      ]);

      expect(getResult.data).toBeDefined();
      expect(statusResult.data).toBeDefined();
    });

    it('handles special characters in translation', async () => {
      vi.mocked(apiPut).mockResolvedValue({ data: { update: 'success' } });

      await TermsApi.updateTranslation(1, 'test & "special" <chars>');

      expect(apiPut).toHaveBeenCalledWith('/terms/1/translation', {
        translation: 'test & "special" <chars>'
      });
    });

    it('handles very long translation', async () => {
      vi.mocked(apiPut).mockResolvedValue({ data: { update: 'success' } });

      const longTranslation = 'A'.repeat(10000);
      await TermsApi.updateTranslation(1, longTranslation);

      expect(apiPut).toHaveBeenCalledWith('/terms/1/translation', {
        translation: longTranslation
      });
    });

    it('handles term with all fields', async () => {
      const fullTerm = {
        id: 1,
        text: 'hello',
        textLc: 'hello',
        translation: 'bonjour',
        romanization: 'hello',
        status: 3,
        langId: 1,
        sentence: 'Hello world',
        tags: ['greeting', 'common']
      };
      vi.mocked(apiGet).mockResolvedValue({ data: fullTerm });

      const result = await TermsApi.get(1);

      expect(result.data).toEqual(fullTerm);
    });

    it('getSimilar handles empty string term', async () => {
      vi.mocked(apiGet).mockResolvedValue({ data: [] });

      await TermsApi.getSimilar('', 1);

      expect(apiGet).toHaveBeenCalledWith('/similar-terms', {
        term: '',
        lg_id: 1
      });
    });

    it('createQuick handles position 0', async () => {
      vi.mocked(apiPost).mockResolvedValue({ data: { term_id: 1 } });

      await TermsApi.createQuick(1, 0, 99);

      expect(apiPost).toHaveBeenCalledWith('/terms/quick', expect.objectContaining({
        position: 0
      }));
    });
  });
});
