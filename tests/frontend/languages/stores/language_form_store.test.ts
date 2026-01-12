/**
 * Tests for language/stores/language_form_store.ts - Language form Alpine.js store
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Hoist mock functions so they're available during vi.mock hoisting
const { mockLanguagesApi } = vi.hoisted(() => ({
  mockLanguagesApi: {
    get: vi.fn(),
    getDefinitions: vi.fn(),
    create: vi.fn(),
    update: vi.fn()
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

import {
  getLanguageFormStore,
  initLanguageFormStore,
  type LanguageFormStoreState
} from '../../../../src/frontend/js/modules/language/stores/language_form_store';

describe('language/stores/language_form_store.ts', () => {
  let store: LanguageFormStoreState;

  beforeEach(() => {
    vi.clearAllMocks();
    // Re-initialize store for each test
    initLanguageFormStore();
    store = getLanguageFormStore();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ===========================================================================
  // Initialization Tests
  // ===========================================================================

  describe('initialization', () => {
    it('creates store with default values', () => {
      expect(store).toBeDefined();
      expect(store.formData.name).toBe('');
      expect(store.isNew).toBe(true);
      expect(store.isLoading).toBe(false);
      expect(store.isSubmitting).toBe(false);
    });

    it('sets sensible default form values', () => {
      expect(store.formData.textSize).toBe(100);
      expect(store.formData.exportTemplate).toBe('$y\\t$t\\n');
      expect(store.formData.regexpSplitSentences).toBe('.!?');
      expect(store.formData.regexpWordCharacters).toBe('a-zA-ZÀ-ÖØ-öø-ȳ');
      expect(store.formData.showRomanization).toBe(true);
    });

    it('initializes errors as null', () => {
      expect(store.errors.name).toBeNull();
      expect(store.errors.dict1Uri).toBeNull();
      expect(store.errors.regexpSplitSentences).toBeNull();
      expect(store.errors.regexpWordCharacters).toBeNull();
      expect(store.errors.textSize).toBeNull();
      expect(store.errors.general).toBeNull();
    });
  });

  // ===========================================================================
  // Computed Properties Tests
  // ===========================================================================

  describe('computed properties', () => {
    describe('isDirty', () => {
      it('returns false when originalData is null', () => {
        store.originalData = null;
        expect(store.isDirty).toBe(false);
      });

      it('returns false when form matches original', async () => {
        // Load new language which sets originalData
        mockLanguagesApi.getDefinitions.mockResolvedValue({ data: { definitions: {} } });
        await store.loadForEdit(null);
        expect(store.isDirty).toBe(false);
      });

      it('returns true when form has changes', async () => {
        mockLanguagesApi.getDefinitions.mockResolvedValue({ data: { definitions: {} } });
        await store.loadForEdit(null);
        store.formData.name = 'Changed Name';
        expect(store.isDirty).toBe(true);
      });
    });

    describe('isValid', () => {
      it('returns true when no errors', () => {
        expect(store.isValid).toBe(true);
      });

      it('returns false when there are errors', () => {
        store.errors.name = 'Name is required';
        expect(store.isValid).toBe(false);
      });
    });

    describe('canSubmit', () => {
      it('returns true when valid and not loading', () => {
        expect(store.canSubmit).toBe(true);
      });

      it('returns false when submitting', () => {
        store.isSubmitting = true;
        expect(store.canSubmit).toBe(false);
      });

      it('returns false when loading', () => {
        store.isLoading = true;
        expect(store.canSubmit).toBe(false);
      });

      it('returns false when invalid', () => {
        store.errors.name = 'Invalid';
        expect(store.canSubmit).toBe(false);
      });
    });
  });

  // ===========================================================================
  // loadForEdit Tests
  // ===========================================================================

  describe('loadForEdit', () => {
    it('loads new language with defaults', async () => {
      mockLanguagesApi.getDefinitions.mockResolvedValue({ data: { definitions: {} } });

      await store.loadForEdit(null);

      expect(store.isNew).toBe(true);
      expect(store.languageId).toBeNull();
      expect(store.formData.name).toBe('');
      expect(store.originalData).not.toBeNull();
      expect(store.isLoading).toBe(false);
    });

    it('loads existing language data', async () => {
      const mockLanguage = {
        id: 1,
        name: 'French',
        dict1Uri: 'http://dict1.example.com',
        dict2Uri: 'http://dict2.example.com',
        translatorUri: 'http://translate.example.com',
        dict1PopUp: true,
        dict2PopUp: false,
        translatorPopUp: true,
        sourceLang: 'fr',
        targetLang: 'en',
        exportTemplate: '$t',
        textSize: 120,
        characterSubstitutions: '',
        regexpSplitSentences: '.!?',
        exceptionsSplitSentences: '',
        regexpWordCharacters: 'a-zA-Z',
        removeSpaces: false,
        splitEachChar: false,
        rightToLeft: false,
        ttsVoiceApi: '',
        showRomanization: false
      };

      mockLanguagesApi.get.mockResolvedValue({
        data: {
          language: mockLanguage,
          allLanguages: { French: 1, English: 2 }
        }
      });
      mockLanguagesApi.getDefinitions.mockResolvedValue({ data: { definitions: {} } });

      await store.loadForEdit(1);

      expect(store.isNew).toBe(false);
      expect(store.languageId).toBe(1);
      expect(store.formData.name).toBe('French');
      expect(store.formData.textSize).toBe(120);
      expect(store.allLanguages).toEqual({ French: 1, English: 2 });
      expect(store.isLoading).toBe(false);
    });

    it('handles API error when loading language', async () => {
      mockLanguagesApi.get.mockResolvedValue({
        error: 'Language not found'
      });

      await store.loadForEdit(999);

      expect(store.errors.general).toBe('Language not found');
      expect(store.isLoading).toBe(false);
    });

    it('handles exception during load', async () => {
      mockLanguagesApi.get.mockRejectedValue(new Error('Network error'));
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      await store.loadForEdit(1);

      expect(store.errors.general).toBe('Failed to load language');
      expect(consoleSpy).toHaveBeenCalled();
      consoleSpy.mockRestore();
    });
  });

  // ===========================================================================
  // loadDefinitions Tests
  // ===========================================================================

  describe('loadDefinitions', () => {
    it('loads language definitions', async () => {
      const mockDefinitions = {
        French: { glosbeIso: 'fr', googleIso: 'fr', sentSplRegExp: '.!?' },
        German: { glosbeIso: 'de', googleIso: 'de', sentSplRegExp: '.!?' }
      };
      mockLanguagesApi.getDefinitions.mockResolvedValue({
        data: { definitions: mockDefinitions }
      });

      await store.loadDefinitions();

      expect(store.definitions).toEqual(mockDefinitions);
    });

    it('handles API error when loading definitions', async () => {
      mockLanguagesApi.getDefinitions.mockResolvedValue({
        error: 'Failed to load definitions'
      });
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      await store.loadDefinitions();

      expect(store.definitions).toEqual({});
      expect(consoleSpy).toHaveBeenCalled();
      consoleSpy.mockRestore();
    });
  });

  // ===========================================================================
  // applyPreset Tests
  // ===========================================================================

  describe('applyPreset', () => {
    beforeEach(() => {
      store.definitions = {
        English: {
          glosbeIso: 'en',
          googleIso: 'en',
          sentSplRegExp: '.!?',
          wordCharRegExp: 'a-zA-Z',
          makeCharacterWord: false,
          removeSpaces: false,
          rightToLeft: false,
          biggerFont: false
        },
        Japanese: {
          glosbeIso: 'ja',
          googleIso: 'ja',
          sentSplRegExp: '。！？',
          wordCharRegExp: '\\p{Han}\\p{Hiragana}\\p{Katakana}',
          makeCharacterWord: true,
          removeSpaces: true,
          rightToLeft: false,
          biggerFont: true
        },
        Arabic: {
          glosbeIso: 'ar',
          googleIso: 'ar',
          sentSplRegExp: '.!?؟',
          wordCharRegExp: '\\p{Arabic}',
          makeCharacterWord: false,
          removeSpaces: false,
          rightToLeft: true,
          biggerFont: false
        }
      };
    });

    it('applies preset for study language', () => {
      store.applyPreset('English', 'Japanese');

      expect(store.formData.name).toBe('Japanese');
      expect(store.formData.dict1Uri).toContain('glosbe.com/ja/en');
      expect(store.formData.dict1PopUp).toBe(true);
      expect(store.formData.translatorUri).toContain('translate.google.com');
      expect(store.formData.translatorPopUp).toBe(true);
      expect(store.formData.sourceLang).toBe('ja');
      expect(store.formData.targetLang).toBe('en');
      expect(store.formData.textSize).toBe(150); // biggerFont
      expect(store.formData.splitEachChar).toBe(true);
      expect(store.formData.removeSpaces).toBe(true);
    });

    it('handles RTL languages', () => {
      store.applyPreset('English', 'Arabic');

      expect(store.formData.rightToLeft).toBe(true);
    });

    it('logs error for unknown study language', () => {
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      store.applyPreset('English', 'Unknown');

      expect(consoleSpy).toHaveBeenCalledWith('Unknown study language:', 'Unknown');
      consoleSpy.mockRestore();
    });

    it('uses default native language when unknown', () => {
      store.applyPreset('Unknown', 'Japanese');

      // Should use 'en' as default
      expect(store.formData.dict1Uri).toContain('glosbe.com/ja/en');
      expect(store.formData.targetLang).toBe('en');
    });
  });

  // ===========================================================================
  // reset Tests
  // ===========================================================================

  describe('reset', () => {
    it('resets all form state', async () => {
      // First load some data
      mockLanguagesApi.getDefinitions.mockResolvedValue({ data: { definitions: {} } });
      await store.loadForEdit(null);
      store.formData.name = 'Test';
      store.languageId = 1;
      store.isNew = false;

      store.reset();

      expect(store.formData.name).toBe('');
      expect(store.originalData).toBeNull();
      expect(store.languageId).toBeNull();
      expect(store.isNew).toBe(true);
      expect(store.allLanguages).toEqual({});
      expect(store.isLoading).toBe(false);
      expect(store.isSubmitting).toBe(false);
      expect(store.errors.name).toBeNull();
    });
  });

  // ===========================================================================
  // validate Tests
  // ===========================================================================

  describe('validate', () => {
    it('validates all fields and returns true when valid', () => {
      store.formData.name = 'Test Language';
      store.formData.regexpSplitSentences = '.!?';
      store.formData.regexpWordCharacters = 'a-z';
      store.formData.textSize = 100;

      const result = store.validate();

      expect(result).toBe(true);
      expect(store.isValid).toBe(true);
    });

    it('returns false when name is missing', () => {
      store.formData.name = '';

      const result = store.validate();

      expect(result).toBe(false);
      expect(store.errors.name).toBe('Language name is required');
    });

    it('returns false when name is too long', () => {
      store.formData.name = 'A'.repeat(50);

      store.validate();

      expect(store.errors.name).toBe('Language name must be 40 characters or less');
    });

    it('returns false when name is duplicate', () => {
      store.allLanguages = { 'Test': 2 };
      store.languageId = 1; // Different ID
      store.formData.name = 'Test';

      store.validate();

      expect(store.errors.name).toBe('A language with this name already exists');
    });

    it('allows same name for same language ID', () => {
      store.allLanguages = { 'Test': 1 };
      store.languageId = 1; // Same ID
      store.formData.name = 'Test';

      store.validate();

      expect(store.errors.name).toBeNull();
    });
  });

  // ===========================================================================
  // validateField Tests
  // ===========================================================================

  describe('validateField', () => {
    describe('name validation', () => {
      it('requires name', () => {
        store.formData.name = '';
        store.validateField('name');
        expect(store.errors.name).toBe('Language name is required');
      });

      it('requires name to not be whitespace only', () => {
        store.formData.name = '   ';
        store.validateField('name');
        expect(store.errors.name).toBe('Language name is required');
      });

      it('accepts valid name', () => {
        store.formData.name = 'Valid Name';
        store.validateField('name');
        expect(store.errors.name).toBeNull();
      });
    });

    describe('regexpSplitSentences validation', () => {
      it('requires sentence split characters', () => {
        store.formData.regexpSplitSentences = '';
        store.validateField('regexpSplitSentences');
        expect(store.errors.regexpSplitSentences).toBe('Sentence split characters are required');
      });

      it('accepts valid pattern', () => {
        store.formData.regexpSplitSentences = '.!?';
        store.validateField('regexpSplitSentences');
        expect(store.errors.regexpSplitSentences).toBeNull();
      });
    });

    describe('regexpWordCharacters validation', () => {
      it('requires word characters pattern', () => {
        store.formData.regexpWordCharacters = '';
        store.validateField('regexpWordCharacters');
        expect(store.errors.regexpWordCharacters).toBe('Word characters pattern is required');
      });

      it('accepts valid pattern', () => {
        store.formData.regexpWordCharacters = 'a-zA-Z';
        store.validateField('regexpWordCharacters');
        expect(store.errors.regexpWordCharacters).toBeNull();
      });
    });

    describe('textSize validation', () => {
      it('rejects size below 50', () => {
        store.formData.textSize = 40;
        store.validateField('textSize');
        expect(store.errors.textSize).toBe('Text size must be between 50 and 300');
      });

      it('rejects size above 300', () => {
        store.formData.textSize = 350;
        store.validateField('textSize');
        expect(store.errors.textSize).toBe('Text size must be between 50 and 300');
      });

      it('accepts valid size', () => {
        store.formData.textSize = 150;
        store.validateField('textSize');
        expect(store.errors.textSize).toBeNull();
      });
    });

    describe('ttsVoiceApi validation', () => {
      it('accepts empty value', () => {
        store.formData.ttsVoiceApi = '';
        store.validateField('ttsVoiceApi');
        expect(store.errors.ttsVoiceApi).toBeNull();
      });

      it('accepts valid JSON', () => {
        store.formData.ttsVoiceApi = '{"voice": "en-US"}';
        store.validateField('ttsVoiceApi');
        expect(store.errors.ttsVoiceApi).toBeNull();
      });

      it('rejects invalid JSON', () => {
        store.formData.ttsVoiceApi = '{invalid json}';
        store.validateField('ttsVoiceApi');
        expect(store.errors.ttsVoiceApi).toBe('TTS Voice API must be valid JSON');
      });
    });
  });

  // ===========================================================================
  // save Tests
  // ===========================================================================

  describe('save', () => {
    beforeEach(() => {
      store.formData.name = 'Test Language';
      store.formData.regexpSplitSentences = '.!?';
      store.formData.regexpWordCharacters = 'a-z';
    });

    describe('creating new language', () => {
      beforeEach(async () => {
        mockLanguagesApi.getDefinitions.mockResolvedValue({ data: { definitions: {} } });
        await store.loadForEdit(null);
        store.formData.name = 'New Language';
      });

      it('creates language successfully', async () => {
        mockLanguagesApi.create.mockResolvedValue({
          data: { success: true, id: 5 }
        });

        const result = await store.save();

        expect(result.success).toBe(true);
        expect(result.id).toBe(5);
        expect(store.languageId).toBe(5);
        expect(store.isNew).toBe(false);
        expect(store.isSubmitting).toBe(false);
      });

      it('handles create API error', async () => {
        mockLanguagesApi.create.mockResolvedValue({
          error: 'Failed to create'
        });

        const result = await store.save();

        expect(result.success).toBe(false);
        expect(result.error).toBe('Failed to create');
        expect(store.errors.general).toBe('Failed to create');
      });

      it('handles create API returning success false', async () => {
        mockLanguagesApi.create.mockResolvedValue({
          data: { success: false, error: 'Validation failed' }
        });

        const result = await store.save();

        expect(result.success).toBe(false);
        expect(result.error).toBe('Validation failed');
      });
    });

    describe('updating existing language', () => {
      beforeEach(async () => {
        mockLanguagesApi.get.mockResolvedValue({
          data: {
            language: {
              id: 1,
              name: 'French',
              dict1Uri: '',
              dict2Uri: '',
              translatorUri: '',
              exportTemplate: '',
              textSize: 100,
              characterSubstitutions: '',
              regexpSplitSentences: '.!?',
              exceptionsSplitSentences: '',
              regexpWordCharacters: 'a-z',
              removeSpaces: false,
              splitEachChar: false,
              rightToLeft: false,
              ttsVoiceApi: '',
              showRomanization: true
            },
            allLanguages: {}
          }
        });
        mockLanguagesApi.getDefinitions.mockResolvedValue({ data: { definitions: {} } });
        await store.loadForEdit(1);
      });

      it('updates language successfully', async () => {
        mockLanguagesApi.update.mockResolvedValue({
          data: { success: true, reparsed: 5 }
        });

        const result = await store.save();

        expect(result.success).toBe(true);
        expect(result.id).toBe(1);
        expect(result.reparsed).toBe(5);
        expect(store.isSubmitting).toBe(false);
      });

      it('handles update API error', async () => {
        mockLanguagesApi.update.mockResolvedValue({
          error: 'Failed to update'
        });

        const result = await store.save();

        expect(result.success).toBe(false);
        expect(result.error).toBe('Failed to update');
      });

      it('fails when languageId is null', async () => {
        store.isNew = false;
        store.languageId = null;

        const result = await store.save();

        expect(result.success).toBe(false);
        expect(result.error).toBe('Language ID is missing');
      });
    });

    it('returns error when validation fails', async () => {
      store.formData.name = ''; // Invalid

      const result = await store.save();

      expect(result.success).toBe(false);
      expect(result.error).toBe('Please fix validation errors');
    });

    it('handles exception during save', async () => {
      mockLanguagesApi.getDefinitions.mockResolvedValue({ data: { definitions: {} } });
      await store.loadForEdit(null);
      store.formData.name = 'Test';
      mockLanguagesApi.create.mockRejectedValue(new Error('Network error'));
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      const result = await store.save();

      expect(result.success).toBe(false);
      expect(result.error).toBe('Failed to save language');
      expect(consoleSpy).toHaveBeenCalled();
      consoleSpy.mockRestore();
    });
  });
});
