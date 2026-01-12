/**
 * Tests for language/stores/language_store.ts - Language list Alpine.js store
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Hoist mock functions so they're available during vi.mock hoisting
const { mockLanguagesApi } = vi.hoisted(() => ({
  mockLanguagesApi: {
    list: vi.fn(),
    getDefinitions: vi.fn(),
    setDefault: vi.fn(),
    delete: vi.fn(),
    refresh: vi.fn()
  }
}));

// Mock Alpine.js before importing the store
vi.mock('alpinejs', () => {
  const stores: Record<string, unknown> = {};
  return {
    default: {
      store: vi.fn((name: string, data?: unknown) => {
        if (data !== undefined) {
          stores[name] = data;
        }
        return stores[name];
      })
    }
  };
});

// Mock LanguagesApi
vi.mock('../../../../src/frontend/js/modules/language/api/languages_api', () => ({
  LanguagesApi: mockLanguagesApi
}));

import Alpine from 'alpinejs';
import {
  getLanguageStore,
  initLanguageStore
} from '../../../../src/frontend/js/modules/language/stores/language_store';

describe('language/stores/language_store.ts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    // Re-initialize store for each test
    initLanguageStore();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ===========================================================================
  // Store Initialization Tests
  // ===========================================================================

  describe('Store initialization', () => {
    it('registers store with Alpine', () => {
      expect(Alpine.store).toHaveBeenCalledWith('languages', expect.any(Object));
    });

    it('initializes with default values', () => {
      const store = getLanguageStore();

      expect(store.languages).toEqual([]);
      expect(store.currentLanguageId).toBe(0);
      expect(store.definitions).toEqual({});
      expect(store.isLoading).toBe(true);
      expect(store.error).toBeNull();
      expect(store.deleteConfirmId).toBeNull();
      expect(store.refreshingId).toBeNull();
      expect(store.wizardModalOpen).toBe(false);
    });
  });

  // ===========================================================================
  // currentLanguage computed property Tests
  // ===========================================================================

  describe('currentLanguage getter', () => {
    it('returns undefined when no languages', () => {
      const store = getLanguageStore();
      store.languages = [];
      store.currentLanguageId = 1;

      expect(store.currentLanguage).toBeUndefined();
    });

    it('returns undefined when currentLanguageId is 0', () => {
      const store = getLanguageStore();
      store.languages = [{ id: 1, name: 'English' } as never];
      store.currentLanguageId = 0;

      expect(store.currentLanguage).toBeUndefined();
    });

    it('returns the matching language', () => {
      const store = getLanguageStore();
      const english = { id: 1, name: 'English' };
      const spanish = { id: 2, name: 'Spanish' };
      store.languages = [english, spanish] as never[];
      store.currentLanguageId = 2;

      expect(store.currentLanguage).toEqual(spanish);
    });
  });

  // ===========================================================================
  // loadLanguages Tests
  // ===========================================================================

  describe('loadLanguages', () => {
    it('sets isLoading to true at start', async () => {
      const store = getLanguageStore();
      store.isLoading = false;

      mockLanguagesApi.list.mockResolvedValue({
        data: { languages: [], currentLanguageId: 0 },
        error: undefined
      });

      const promise = store.loadLanguages();
      expect(store.isLoading).toBe(true);
      await promise;
    });

    it('clears error at start', async () => {
      const store = getLanguageStore();
      store.error = 'Previous error';

      mockLanguagesApi.list.mockResolvedValue({
        data: { languages: [], currentLanguageId: 0 },
        error: undefined
      });

      await store.loadLanguages();
      expect(store.error).toBeNull();
    });

    it('loads languages successfully', async () => {
      const store = getLanguageStore();
      const languages = [
        { id: 1, name: 'English', hasExportTemplate: true, textsCount: 5, wordsCount: 100 },
        { id: 2, name: 'Spanish', hasExportTemplate: false, textsCount: 3, wordsCount: 50 }
      ];

      mockLanguagesApi.list.mockResolvedValue({
        data: { languages, currentLanguageId: 1 },
        error: undefined
      });

      await store.loadLanguages();

      expect(store.languages).toEqual(languages);
      expect(store.currentLanguageId).toBe(1);
      expect(store.isLoading).toBe(false);
      expect(store.error).toBeNull();
    });

    it('sets error when API returns error', async () => {
      const store = getLanguageStore();

      mockLanguagesApi.list.mockResolvedValue({
        data: null,
        error: 'API Error'
      });

      await store.loadLanguages();

      expect(store.error).toBe('API Error');
      expect(store.isLoading).toBe(false);
      expect(store.languages).toEqual([]);
    });

    it('sets default error message when no data', async () => {
      const store = getLanguageStore();

      mockLanguagesApi.list.mockResolvedValue({
        data: null,
        error: undefined
      });

      await store.loadLanguages();

      expect(store.error).toBe('Failed to load languages');
    });

    it('handles exceptions gracefully', async () => {
      const store = getLanguageStore();
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      mockLanguagesApi.list.mockRejectedValue(new Error('Network error'));

      await store.loadLanguages();

      expect(store.error).toBe('Failed to load languages');
      expect(store.isLoading).toBe(false);
      expect(consoleSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // loadDefinitions Tests
  // ===========================================================================

  describe('loadDefinitions', () => {
    it('loads definitions successfully', async () => {
      const store = getLanguageStore();
      const definitions = {
        English: { name: 'English', sentences: 'en', specialCharacters: '' },
        Spanish: { name: 'Spanish', sentences: 'es', specialCharacters: 'ñáéíóú' }
      };

      mockLanguagesApi.getDefinitions.mockResolvedValue({
        data: { definitions },
        error: undefined
      });

      await store.loadDefinitions();

      expect(store.definitions).toEqual(definitions);
    });

    it('handles API error silently', async () => {
      const store = getLanguageStore();
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      mockLanguagesApi.getDefinitions.mockResolvedValue({
        data: null,
        error: 'Failed to load'
      });

      await store.loadDefinitions();

      expect(store.definitions).toEqual({});
      expect(consoleSpy).toHaveBeenCalled();
    });

    it('handles exceptions gracefully', async () => {
      const store = getLanguageStore();
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      mockLanguagesApi.getDefinitions.mockRejectedValue(new Error('Network error'));

      await store.loadDefinitions();

      expect(store.definitions).toEqual({});
      expect(consoleSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // setCurrentLanguage Tests
  // ===========================================================================

  describe('setCurrentLanguage', () => {
    it('sets current language successfully', async () => {
      const store = getLanguageStore();
      store.currentLanguageId = 1;

      mockLanguagesApi.setDefault.mockResolvedValue({
        data: { success: true },
        error: undefined
      });

      const result = await store.setCurrentLanguage(2);

      expect(result).toBe(true);
      expect(store.currentLanguageId).toBe(2);
      expect(mockLanguagesApi.setDefault).toHaveBeenCalledWith(2);
    });

    it('returns false and sets error on API error', async () => {
      const store = getLanguageStore();
      store.currentLanguageId = 1;

      mockLanguagesApi.setDefault.mockResolvedValue({
        data: null,
        error: 'API Error'
      });

      const result = await store.setCurrentLanguage(2);

      expect(result).toBe(false);
      expect(store.error).toBe('API Error');
      expect(store.currentLanguageId).toBe(1); // Unchanged
    });

    it('returns false when success is false', async () => {
      const store = getLanguageStore();

      mockLanguagesApi.setDefault.mockResolvedValue({
        data: { success: false },
        error: undefined
      });

      const result = await store.setCurrentLanguage(2);

      expect(result).toBe(false);
      expect(store.error).toBe('Failed to set default language');
    });

    it('handles exceptions gracefully', async () => {
      const store = getLanguageStore();
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      mockLanguagesApi.setDefault.mockRejectedValue(new Error('Network error'));

      const result = await store.setCurrentLanguage(2);

      expect(result).toBe(false);
      expect(store.error).toBe('Failed to set default language');
      expect(consoleSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // deleteLanguage Tests
  // ===========================================================================

  describe('deleteLanguage', () => {
    it('deletes language successfully', async () => {
      const store = getLanguageStore();
      store.languages = [
        { id: 1, name: 'English' },
        { id: 2, name: 'Spanish' }
      ] as never[];
      store.deleteConfirmId = 2;

      mockLanguagesApi.delete.mockResolvedValue({
        data: { success: true },
        error: undefined
      });

      const result = await store.deleteLanguage(2);

      expect(result).toBe(true);
      expect(store.languages).toHaveLength(1);
      expect(store.languages[0]).toEqual({ id: 1, name: 'English' });
      expect(store.deleteConfirmId).toBeNull();
      expect(mockLanguagesApi.delete).toHaveBeenCalledWith(2);
    });

    it('clears currentLanguageId when deleting current language', async () => {
      const store = getLanguageStore();
      store.languages = [{ id: 1, name: 'English' }] as never[];
      store.currentLanguageId = 1;

      mockLanguagesApi.delete.mockResolvedValue({
        data: { success: true },
        error: undefined
      });

      await store.deleteLanguage(1);

      expect(store.currentLanguageId).toBe(0);
    });

    it('does not change currentLanguageId when deleting other language', async () => {
      const store = getLanguageStore();
      store.languages = [
        { id: 1, name: 'English' },
        { id: 2, name: 'Spanish' }
      ] as never[];
      store.currentLanguageId = 1;

      mockLanguagesApi.delete.mockResolvedValue({
        data: { success: true },
        error: undefined
      });

      await store.deleteLanguage(2);

      expect(store.currentLanguageId).toBe(1);
    });

    it('returns false on API error', async () => {
      const store = getLanguageStore();
      store.languages = [{ id: 1, name: 'English' }] as never[];

      mockLanguagesApi.delete.mockResolvedValue({
        data: null,
        error: 'Cannot delete'
      });

      const result = await store.deleteLanguage(1);

      expect(result).toBe(false);
      expect(store.error).toBe('Cannot delete');
      expect(store.languages).toHaveLength(1); // Unchanged
    });

    it('returns false when success is false with error message', async () => {
      const store = getLanguageStore();
      store.languages = [{ id: 1, name: 'English' }] as never[];

      mockLanguagesApi.delete.mockResolvedValue({
        data: { success: false, error: 'Language has texts' },
        error: undefined
      });

      const result = await store.deleteLanguage(1);

      expect(result).toBe(false);
      expect(store.error).toBe('Language has texts');
    });

    it('returns false when success is false without error message', async () => {
      const store = getLanguageStore();
      store.languages = [{ id: 1, name: 'English' }] as never[];

      mockLanguagesApi.delete.mockResolvedValue({
        data: { success: false },
        error: undefined
      });

      const result = await store.deleteLanguage(1);

      expect(result).toBe(false);
      expect(store.error).toBe('Failed to delete language');
    });

    it('handles exceptions gracefully', async () => {
      const store = getLanguageStore();
      store.languages = [{ id: 1, name: 'English' }] as never[];
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      mockLanguagesApi.delete.mockRejectedValue(new Error('Network error'));

      const result = await store.deleteLanguage(1);

      expect(result).toBe(false);
      expect(store.error).toBe('Failed to delete language');
      expect(consoleSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // refreshLanguage Tests
  // ===========================================================================

  describe('refreshLanguage', () => {
    it('sets refreshingId during operation', async () => {
      const store = getLanguageStore();

      mockLanguagesApi.refresh.mockResolvedValue({
        data: { success: true },
        error: undefined
      });

      const promise = store.refreshLanguage(1);
      expect(store.refreshingId).toBe(1);

      await promise;
      expect(store.refreshingId).toBeNull();
    });

    it('refreshes language successfully', async () => {
      const store = getLanguageStore();

      mockLanguagesApi.refresh.mockResolvedValue({
        data: { success: true },
        error: undefined
      });

      const result = await store.refreshLanguage(1);

      expect(result).toBe(true);
      expect(store.refreshingId).toBeNull();
      expect(mockLanguagesApi.refresh).toHaveBeenCalledWith(1);
    });

    it('returns false on API error', async () => {
      const store = getLanguageStore();

      mockLanguagesApi.refresh.mockResolvedValue({
        data: null,
        error: 'Refresh failed'
      });

      const result = await store.refreshLanguage(1);

      expect(result).toBe(false);
      expect(store.error).toBe('Refresh failed');
      expect(store.refreshingId).toBeNull();
    });

    it('returns false when success is false', async () => {
      const store = getLanguageStore();

      mockLanguagesApi.refresh.mockResolvedValue({
        data: { success: false },
        error: undefined
      });

      const result = await store.refreshLanguage(1);

      expect(result).toBe(false);
      expect(store.error).toBe('Failed to refresh language');
      expect(store.refreshingId).toBeNull();
    });

    it('handles exceptions gracefully', async () => {
      const store = getLanguageStore();
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      mockLanguagesApi.refresh.mockRejectedValue(new Error('Network error'));

      const result = await store.refreshLanguage(1);

      expect(result).toBe(false);
      expect(store.error).toBe('Failed to refresh language');
      expect(store.refreshingId).toBeNull();
      expect(consoleSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // Modal Methods Tests
  // ===========================================================================

  describe('openWizardModal', () => {
    it('sets wizardModalOpen to true', () => {
      const store = getLanguageStore();
      store.wizardModalOpen = false;

      store.openWizardModal();

      expect(store.wizardModalOpen).toBe(true);
    });
  });

  describe('closeWizardModal', () => {
    it('sets wizardModalOpen to false', () => {
      const store = getLanguageStore();
      store.wizardModalOpen = true;

      store.closeWizardModal();

      expect(store.wizardModalOpen).toBe(false);
    });
  });

  // ===========================================================================
  // Delete Confirmation Methods Tests
  // ===========================================================================

  describe('showDeleteConfirm', () => {
    it('sets deleteConfirmId', () => {
      const store = getLanguageStore();

      store.showDeleteConfirm(5);

      expect(store.deleteConfirmId).toBe(5);
    });
  });

  describe('hideDeleteConfirm', () => {
    it('clears deleteConfirmId', () => {
      const store = getLanguageStore();
      store.deleteConfirmId = 5;

      store.hideDeleteConfirm();

      expect(store.deleteConfirmId).toBeNull();
    });
  });

  // ===========================================================================
  // Window Export Tests
  // ===========================================================================

  describe('Window Exports', () => {
    it('exposes getLanguageStore on window', () => {
      expect(window.getLanguageStore).toBeDefined();
    });
  });
});
