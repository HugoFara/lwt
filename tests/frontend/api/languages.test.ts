/**
 * Tests for api/languages.ts - Languages API operations
 */
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { LanguagesApi } from '../../../src/frontend/js/api/languages';
import * as apiClient from '../../../src/frontend/js/core/api_client';

// Mock the api_client module
vi.mock('../../../src/frontend/js/core/api_client', () => ({
  apiGet: vi.fn(),
  apiPost: vi.fn(),
  apiPut: vi.fn(),
  apiDelete: vi.fn(),
}));

describe('api/languages.ts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  // ===========================================================================
  // list Tests
  // ===========================================================================

  describe('LanguagesApi.list', () => {
    it('calls apiGet with /languages endpoint', async () => {
      const mockResponse = {
        data: {
          languages: [
            { id: 1, name: 'English', hasExportTemplate: false, textCount: 5, archivedTextCount: 2, wordCount: 100, feedCount: 0, articleCount: 0 }
          ],
          currentLanguageId: 1
        },
        error: undefined,
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      const result = await LanguagesApi.list();

      expect(apiClient.apiGet).toHaveBeenCalledWith('/languages');
      expect(result.data?.languages).toHaveLength(1);
      expect(result.data?.currentLanguageId).toBe(1);
    });

    it('handles empty language list', async () => {
      const mockResponse = {
        data: { languages: [], currentLanguageId: 0 },
        error: undefined,
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      const result = await LanguagesApi.list();

      expect(result.data?.languages).toEqual([]);
    });

    it('handles error response', async () => {
      const mockResponse = {
        data: undefined,
        error: 'Database connection failed',
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      const result = await LanguagesApi.list();

      expect(result.error).toBe('Database connection failed');
    });
  });

  // ===========================================================================
  // get Tests
  // ===========================================================================

  describe('LanguagesApi.get', () => {
    it('calls apiGet with language ID', async () => {
      const mockResponse = {
        data: {
          language: {
            id: 1,
            name: 'English',
            dict1Uri: 'https://example.com',
            dict2Uri: '',
            translatorUri: '',
            exportTemplate: '',
            textSize: 100,
            characterSubstitutions: '',
            regexpSplitSentences: '.!?',
            exceptionsSplitSentences: '',
            regexpWordCharacters: 'a-zA-Z',
            removeSpaces: false,
            splitEachChar: false,
            rightToLeft: false,
            ttsVoiceApi: '',
            showRomanization: false
          },
          allLanguages: { English: 1 }
        },
        error: undefined,
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      const result = await LanguagesApi.get(1);

      expect(apiClient.apiGet).toHaveBeenCalledWith('/languages/1');
      expect(result.data?.language.name).toBe('English');
    });

    it('handles non-existent language', async () => {
      const mockResponse = {
        data: undefined,
        error: 'Language not found',
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      const result = await LanguagesApi.get(999);

      expect(result.error).toBe('Language not found');
    });
  });

  // ===========================================================================
  // create Tests
  // ===========================================================================

  describe('LanguagesApi.create', () => {
    it('calls apiPost with language data', async () => {
      const mockResponse = {
        data: { success: true, id: 5 },
        error: undefined,
      };
      vi.mocked(apiClient.apiPost).mockResolvedValue(mockResponse);

      const createData = {
        name: 'Spanish',
        dict1Uri: 'https://dict.example.com',
      };
      const result = await LanguagesApi.create(createData);

      expect(apiClient.apiPost).toHaveBeenCalledWith('/languages', createData);
      expect(result.data?.success).toBe(true);
      expect(result.data?.id).toBe(5);
    });

    it('handles validation error', async () => {
      const mockResponse = {
        data: { success: false, error: 'Name is required' },
        error: undefined,
      };
      vi.mocked(apiClient.apiPost).mockResolvedValue(mockResponse);

      const result = await LanguagesApi.create({ name: '' });

      expect(result.data?.success).toBe(false);
      expect(result.data?.error).toBe('Name is required');
    });

    it('handles duplicate name error', async () => {
      const mockResponse = {
        data: { success: false, error: 'Language with this name already exists' },
        error: undefined,
      };
      vi.mocked(apiClient.apiPost).mockResolvedValue(mockResponse);

      const result = await LanguagesApi.create({ name: 'English' });

      expect(result.data?.error).toContain('already exists');
    });
  });

  // ===========================================================================
  // update Tests
  // ===========================================================================

  describe('LanguagesApi.update', () => {
    it('calls apiPut with language ID and data', async () => {
      const mockResponse = {
        data: { success: true, reparsed: 0 },
        error: undefined,
      };
      vi.mocked(apiClient.apiPut).mockResolvedValue(mockResponse);

      const updateData = { name: 'British English' };
      const result = await LanguagesApi.update(1, updateData);

      expect(apiClient.apiPut).toHaveBeenCalledWith('/languages/1', updateData);
      expect(result.data?.success).toBe(true);
    });

    it('returns reparsed count when settings change', async () => {
      const mockResponse = {
        data: { success: true, reparsed: 150 },
        error: undefined,
      };
      vi.mocked(apiClient.apiPut).mockResolvedValue(mockResponse);

      const result = await LanguagesApi.update(1, { regexpWordCharacters: 'a-zA-Z0-9' });

      expect(result.data?.reparsed).toBe(150);
    });

    it('handles update error', async () => {
      const mockResponse = {
        data: undefined,
        error: 'Update failed',
      };
      vi.mocked(apiClient.apiPut).mockResolvedValue(mockResponse);

      const result = await LanguagesApi.update(1, { name: '' });

      expect(result.error).toBe('Update failed');
    });
  });

  // ===========================================================================
  // delete Tests
  // ===========================================================================

  describe('LanguagesApi.delete', () => {
    it('calls apiDelete with language ID', async () => {
      const mockResponse = {
        data: { success: true },
        error: undefined,
      };
      vi.mocked(apiClient.apiDelete).mockResolvedValue(mockResponse);

      const result = await LanguagesApi.delete(1);

      expect(apiClient.apiDelete).toHaveBeenCalledWith('/languages/1');
      expect(result.data?.success).toBe(true);
    });

    it('returns related data counts', async () => {
      const mockResponse = {
        data: {
          success: true,
          relatedData: { texts: 5, archivedTexts: 2, words: 100, feeds: 1 }
        },
        error: undefined,
      };
      vi.mocked(apiClient.apiDelete).mockResolvedValue(mockResponse);

      const result = await LanguagesApi.delete(1);

      expect(result.data?.relatedData?.texts).toBe(5);
      expect(result.data?.relatedData?.words).toBe(100);
    });

    it('handles delete error', async () => {
      const mockResponse = {
        data: undefined,
        error: 'Cannot delete language with existing texts',
      };
      vi.mocked(apiClient.apiDelete).mockResolvedValue(mockResponse);

      const result = await LanguagesApi.delete(1);

      expect(result.error).toBe('Cannot delete language with existing texts');
    });
  });

  // ===========================================================================
  // getStats Tests
  // ===========================================================================

  describe('LanguagesApi.getStats', () => {
    it('calls apiGet with stats endpoint', async () => {
      const mockResponse = {
        data: { texts: 10, archivedTexts: 5, words: 500, feeds: 2 },
        error: undefined,
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      const result = await LanguagesApi.getStats(1);

      expect(apiClient.apiGet).toHaveBeenCalledWith('/languages/1/stats');
      expect(result.data?.texts).toBe(10);
      expect(result.data?.words).toBe(500);
    });
  });

  // ===========================================================================
  // refresh Tests
  // ===========================================================================

  describe('LanguagesApi.refresh', () => {
    it('calls apiPost with refresh endpoint', async () => {
      const mockResponse = {
        data: {
          success: true,
          sentencesDeleted: 100,
          textItemsDeleted: 500,
          sentencesAdded: 105,
          textItemsAdded: 520
        },
        error: undefined,
      };
      vi.mocked(apiClient.apiPost).mockResolvedValue(mockResponse);

      const result = await LanguagesApi.refresh(1);

      expect(apiClient.apiPost).toHaveBeenCalledWith('/languages/1/refresh', {});
      expect(result.data?.success).toBe(true);
      expect(result.data?.sentencesAdded).toBe(105);
    });
  });

  // ===========================================================================
  // getDefinitions Tests
  // ===========================================================================

  describe('LanguagesApi.getDefinitions', () => {
    it('calls apiGet with definitions endpoint', async () => {
      const mockResponse = {
        data: {
          definitions: {
            'English': {
              glosbeIso: 'en',
              googleIso: 'en',
              biggerFont: false,
              wordCharRegExp: 'a-zA-Z',
              sentSplRegExp: '.!?',
              makeCharacterWord: false,
              removeSpaces: false,
              rightToLeft: false
            }
          }
        },
        error: undefined,
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      const result = await LanguagesApi.getDefinitions();

      expect(apiClient.apiGet).toHaveBeenCalledWith('/languages/definitions');
      expect(result.data?.definitions['English']).toBeDefined();
      expect(result.data?.definitions['English'].glosbeIso).toBe('en');
    });
  });

  // ===========================================================================
  // setDefault Tests
  // ===========================================================================

  describe('LanguagesApi.setDefault', () => {
    it('calls apiPost with set-default endpoint', async () => {
      const mockResponse = {
        data: { success: true },
        error: undefined,
      };
      vi.mocked(apiClient.apiPost).mockResolvedValue(mockResponse);

      const result = await LanguagesApi.setDefault(2);

      expect(apiClient.apiPost).toHaveBeenCalledWith('/languages/2/set-default', {});
      expect(result.data?.success).toBe(true);
    });

    it('handles set-default error', async () => {
      const mockResponse = {
        data: undefined,
        error: 'Language not found',
      };
      vi.mocked(apiClient.apiPost).mockResolvedValue(mockResponse);

      const result = await LanguagesApi.setDefault(999);

      expect(result.error).toBe('Language not found');
    });
  });
});
