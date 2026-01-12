/**
 * Tests for vocabulary/stores/word_form_store.ts - Word form Alpine.js store
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Hoist mock functions so they're available during vi.mock hoisting
const { mockTermsApi } = vi.hoisted(() => ({
  mockTermsApi: {
    getForEdit: vi.fn(),
    createFull: vi.fn(),
    updateFull: vi.fn()
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

// Mock TermsApi
vi.mock('../../../../src/frontend/js/modules/vocabulary/api/terms_api', () => ({
  TermsApi: mockTermsApi
}));

import Alpine from 'alpinejs';
import {
  getWordFormStore,
  initWordFormStore
} from '../../../../src/frontend/js/modules/vocabulary/stores/word_form_store';

describe('vocabulary/stores/word_form_store.ts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    // Re-initialize store for each test
    initWordFormStore();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ===========================================================================
  // Store Initialization Tests
  // ===========================================================================

  describe('Store initialization', () => {
    it('registers store with Alpine', () => {
      expect(Alpine.store).toHaveBeenCalledWith('wordForm', expect.any(Object));
    });

    it('initializes with default values', () => {
      const store = getWordFormStore();

      expect(store.formData.text).toBe('');
      expect(store.formData.translation).toBe('');
      expect(store.formData.status).toBe(1);
      expect(store.formData.tags).toEqual([]);
      expect(store.originalData).toBeNull();
      expect(store.wordId).toBeNull();
      expect(store.textId).toBe(0);
      expect(store.position).toBe(0);
      expect(store.hex).toBe('');
      expect(store.isNewWord).toBe(true);
      expect(store.isVisible).toBe(false);
      expect(store.isSubmitting).toBe(false);
      expect(store.isLoading).toBe(false);
    });

    it('initializes errors as null', () => {
      const store = getWordFormStore();

      expect(store.errors.lemma).toBeNull();
      expect(store.errors.translation).toBeNull();
      expect(store.errors.romanization).toBeNull();
      expect(store.errors.sentence).toBeNull();
      expect(store.errors.notes).toBeNull();
      expect(store.errors.general).toBeNull();
    });
  });

  // ===========================================================================
  // isDirty computed property Tests
  // ===========================================================================

  describe('isDirty getter', () => {
    it('returns false when no originalData', () => {
      const store = getWordFormStore();
      store.originalData = null;

      expect(store.isDirty).toBe(false);
    });

    it('returns false when data unchanged', () => {
      const store = getWordFormStore();
      store.formData = {
        text: 'test',
        textLc: 'test',
        lemma: 'lemma',
        translation: 'trans',
        romanization: 'rom',
        sentence: 'sent',
        notes: 'notes',
        status: 1,
        tags: ['tag1']
      };
      store.originalData = {
        text: 'test',
        textLc: 'test',
        lemma: 'lemma',
        translation: 'trans',
        romanization: 'rom',
        sentence: 'sent',
        notes: 'notes',
        status: 1,
        tags: ['tag1']
      };

      expect(store.isDirty).toBe(false);
    });

    it('returns true when lemma changed', () => {
      const store = getWordFormStore();
      store.formData = { ...store.formData, lemma: 'new lemma' };
      store.originalData = { ...store.formData, lemma: 'old lemma' };

      expect(store.isDirty).toBe(true);
    });

    it('returns true when translation changed', () => {
      const store = getWordFormStore();
      store.formData = { ...store.formData, translation: 'new trans' };
      store.originalData = { ...store.formData, translation: 'old trans' };

      expect(store.isDirty).toBe(true);
    });

    it('returns true when status changed', () => {
      const store = getWordFormStore();
      store.formData = { ...store.formData, status: 2 };
      store.originalData = { ...store.formData, status: 1 };

      expect(store.isDirty).toBe(true);
    });

    it('returns true when tags changed', () => {
      const store = getWordFormStore();
      store.formData = { ...store.formData, tags: ['tag1', 'tag2'] };
      store.originalData = { ...store.formData, tags: ['tag1'] };

      expect(store.isDirty).toBe(true);
    });

    it('returns false when tags same but different order', () => {
      const store = getWordFormStore();
      store.formData = {
        text: 'test',
        textLc: 'test',
        lemma: '',
        translation: '',
        romanization: '',
        sentence: '',
        notes: '',
        status: 1,
        tags: ['tag2', 'tag1']
      };
      store.originalData = {
        text: 'test',
        textLc: 'test',
        lemma: '',
        translation: '',
        romanization: '',
        sentence: '',
        notes: '',
        status: 1,
        tags: ['tag1', 'tag2']
      };

      expect(store.isDirty).toBe(false);
    });
  });

  // ===========================================================================
  // isValid computed property Tests
  // ===========================================================================

  describe('isValid getter', () => {
    it('returns true when no errors', () => {
      const store = getWordFormStore();
      store.errors = {
        lemma: null,
        translation: null,
        romanization: null,
        sentence: null,
        notes: null,
        general: null
      };

      expect(store.isValid).toBe(true);
    });

    it('returns false when lemma error exists', () => {
      const store = getWordFormStore();
      store.errors.lemma = 'Too long';

      expect(store.isValid).toBe(false);
    });

    it('returns false when general error exists', () => {
      const store = getWordFormStore();
      store.errors.general = 'API error';

      expect(store.isValid).toBe(false);
    });
  });

  // ===========================================================================
  // canSubmit computed property Tests
  // ===========================================================================

  describe('canSubmit getter', () => {
    it('returns true when valid and not submitting/loading', () => {
      const store = getWordFormStore();
      store.isSubmitting = false;
      store.isLoading = false;

      expect(store.canSubmit).toBe(true);
    });

    it('returns false when submitting', () => {
      const store = getWordFormStore();
      store.isSubmitting = true;

      expect(store.canSubmit).toBe(false);
    });

    it('returns false when loading', () => {
      const store = getWordFormStore();
      store.isLoading = true;

      expect(store.canSubmit).toBe(false);
    });

    it('returns false when has validation errors', () => {
      const store = getWordFormStore();
      store.errors.translation = 'Too long';

      expect(store.canSubmit).toBe(false);
    });
  });

  // ===========================================================================
  // loadForEdit Tests
  // ===========================================================================

  describe('loadForEdit', () => {
    const mockResponse = {
      term: {
        id: 123,
        hex: 'abc123',
        text: 'hello',
        textLc: 'hello',
        lemma: '',
        translation: 'bonjour',
        romanization: '',
        sentence: 'Hello world.',
        notes: '',
        status: 1,
        tags: ['greeting']
      },
      language: {
        showRomanization: true
      },
      allTags: ['greeting', 'common'],
      similarTerms: [{ id: 1, text: 'hola', translation: 'hello' }],
      isNew: false
    };

    it('sets isLoading during operation', async () => {
      const store = getWordFormStore();
      mockTermsApi.getForEdit.mockResolvedValue({
        data: mockResponse,
        error: undefined
      });

      const promise = store.loadForEdit(1, 5, 123);
      expect(store.isLoading).toBe(true);

      await promise;
      expect(store.isLoading).toBe(false);
    });

    it('loads term data successfully', async () => {
      const store = getWordFormStore();
      mockTermsApi.getForEdit.mockResolvedValue({
        data: mockResponse,
        error: undefined
      });

      await store.loadForEdit(1, 5, 123);

      expect(store.textId).toBe(1);
      expect(store.position).toBe(5);
      expect(store.wordId).toBe(123);
      expect(store.hex).toBe('abc123');
      expect(store.isNewWord).toBe(false);
      expect(store.formData.text).toBe('hello');
      expect(store.formData.translation).toBe('bonjour');
      expect(store.formData.tags).toEqual(['greeting']);
      expect(store.showRomanization).toBe(true);
      expect(store.allTags).toEqual(['greeting', 'common']);
      expect(store.isVisible).toBe(true);
    });

    it('converts asterisk translation to empty string', async () => {
      const store = getWordFormStore();
      mockTermsApi.getForEdit.mockResolvedValue({
        data: {
          ...mockResponse,
          term: { ...mockResponse.term, translation: '*' }
        },
        error: undefined
      });

      await store.loadForEdit(1, 5);

      expect(store.formData.translation).toBe('');
    });

    it('stores original data for dirty detection', async () => {
      const store = getWordFormStore();
      mockTermsApi.getForEdit.mockResolvedValue({
        data: mockResponse,
        error: undefined
      });

      await store.loadForEdit(1, 5, 123);

      expect(store.originalData).not.toBeNull();
      expect(store.originalData?.translation).toBe('bonjour');
    });

    it('sets error on API error', async () => {
      const store = getWordFormStore();
      mockTermsApi.getForEdit.mockResolvedValue({
        data: null,
        error: 'Not found'
      });

      await store.loadForEdit(1, 5);

      expect(store.errors.general).toBe('Not found');
      expect(store.isVisible).toBe(false);
    });

    it('sets error on response error field', async () => {
      const store = getWordFormStore();
      mockTermsApi.getForEdit.mockResolvedValue({
        data: { error: 'Term not found' },
        error: undefined
      });

      await store.loadForEdit(1, 5);

      expect(store.errors.general).toBe('Term not found');
    });

    it('handles exceptions gracefully', async () => {
      const store = getWordFormStore();
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      mockTermsApi.getForEdit.mockRejectedValue(new Error('Network error'));

      await store.loadForEdit(1, 5);

      expect(store.errors.general).toBe('Failed to load term data');
      expect(consoleSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // reset Tests
  // ===========================================================================

  describe('reset', () => {
    it('resets all values to defaults', () => {
      const store = getWordFormStore();

      // Set some values
      store.formData.translation = 'test';
      store.wordId = 123;
      store.textId = 1;
      store.position = 5;
      store.hex = 'abc';
      store.isNewWord = false;
      store.isVisible = true;
      store.isSubmitting = true;
      store.errors.general = 'error';

      store.reset();

      expect(store.formData.translation).toBe('');
      expect(store.formData.status).toBe(1);
      expect(store.wordId).toBeNull();
      expect(store.textId).toBe(0);
      expect(store.position).toBe(0);
      expect(store.hex).toBe('');
      expect(store.isNewWord).toBe(true);
      expect(store.isVisible).toBe(false);
      expect(store.isSubmitting).toBe(false);
      expect(store.errors.general).toBeNull();
    });
  });

  // ===========================================================================
  // validateField Tests
  // ===========================================================================

  describe('validateField', () => {
    it('validates lemma length', () => {
      const store = getWordFormStore();
      store.formData.lemma = 'a'.repeat(251);

      store.validateField('lemma');

      expect(store.errors.lemma).toBe('Lemma must be 250 characters or less');
    });

    it('clears lemma error when valid', () => {
      const store = getWordFormStore();
      store.errors.lemma = 'error';
      store.formData.lemma = 'valid';

      store.validateField('lemma');

      expect(store.errors.lemma).toBeNull();
    });

    it('validates translation length', () => {
      const store = getWordFormStore();
      store.formData.translation = 'a'.repeat(501);

      store.validateField('translation');

      expect(store.errors.translation).toBe('Translation must be 500 characters or less');
    });

    it('validates romanization length', () => {
      const store = getWordFormStore();
      store.formData.romanization = 'a'.repeat(101);

      store.validateField('romanization');

      expect(store.errors.romanization).toBe('Romanization must be 100 characters or less');
    });

    it('validates sentence length', () => {
      const store = getWordFormStore();
      store.formData.sentence = 'a'.repeat(1001);

      store.validateField('sentence');

      expect(store.errors.sentence).toBe('Sentence must be 1000 characters or less');
    });

    it('validates notes length', () => {
      const store = getWordFormStore();
      store.formData.notes = 'a'.repeat(1001);

      store.validateField('notes');

      expect(store.errors.notes).toBe('Notes must be 1000 characters or less');
    });
  });

  // ===========================================================================
  // validate Tests
  // ===========================================================================

  describe('validate', () => {
    it('returns true when all fields valid', () => {
      const store = getWordFormStore();
      store.formData = {
        text: 'test',
        textLc: 'test',
        lemma: 'lemma',
        translation: 'trans',
        romanization: 'rom',
        sentence: 'sent',
        notes: 'notes',
        status: 1,
        tags: []
      };

      const result = store.validate();

      expect(result).toBe(true);
      expect(store.isValid).toBe(true);
    });

    it('returns false when any field invalid', () => {
      const store = getWordFormStore();
      store.formData.lemma = 'a'.repeat(251);

      const result = store.validate();

      expect(result).toBe(false);
      expect(store.isValid).toBe(false);
    });

    it('validates all fields', () => {
      const store = getWordFormStore();
      store.formData = {
        text: 'test',
        textLc: 'test',
        lemma: 'a'.repeat(251),
        translation: 'a'.repeat(501),
        romanization: 'a'.repeat(101),
        sentence: 'a'.repeat(1001),
        notes: 'a'.repeat(1001),
        status: 1,
        tags: []
      };

      store.validate();

      expect(store.errors.lemma).not.toBeNull();
      expect(store.errors.translation).not.toBeNull();
      expect(store.errors.romanization).not.toBeNull();
      expect(store.errors.sentence).not.toBeNull();
      expect(store.errors.notes).not.toBeNull();
    });
  });

  // ===========================================================================
  // save Tests
  // ===========================================================================

  describe('save', () => {
    it('returns error if validation fails', async () => {
      const store = getWordFormStore();
      store.formData.lemma = 'a'.repeat(251);

      const result = await store.save();

      expect(result.success).toBe(false);
      expect(result.error).toBe('Please fix validation errors');
      expect(mockTermsApi.createFull).not.toHaveBeenCalled();
    });

    it('creates new term when isNewWord is true', async () => {
      const store = getWordFormStore();
      store.isNewWord = true;
      store.textId = 1;
      store.position = 5;
      store.formData.translation = 'hello';
      store.formData.status = 2;

      mockTermsApi.createFull.mockResolvedValue({
        data: {
          term: { id: 123, hex: 'abc123' }
        },
        error: undefined
      });

      const result = await store.save();

      expect(result.success).toBe(true);
      expect(result.wordId).toBe(123);
      expect(result.hex).toBe('abc123');
      expect(mockTermsApi.createFull).toHaveBeenCalledWith(
        expect.objectContaining({
          textId: 1,
          position: 5,
          translation: 'hello',
          status: 2
        })
      );
    });

    it('updates existing term when isNewWord is false', async () => {
      const store = getWordFormStore();
      store.isNewWord = false;
      store.wordId = 123;
      store.formData.translation = 'updated';

      mockTermsApi.updateFull.mockResolvedValue({
        data: {
          term: { id: 123, hex: 'abc123' }
        },
        error: undefined
      });

      const result = await store.save();

      expect(result.success).toBe(true);
      expect(mockTermsApi.updateFull).toHaveBeenCalledWith(
        123,
        expect.objectContaining({
          translation: 'updated'
        })
      );
    });

    it('returns error when wordId missing for update', async () => {
      const store = getWordFormStore();
      store.isNewWord = false;
      store.wordId = null;

      const result = await store.save();

      expect(result.success).toBe(false);
      expect(result.error).toBe('Word ID is missing');
    });

    it('sets isSubmitting during operation', async () => {
      const store = getWordFormStore();
      store.isNewWord = true;
      store.textId = 1;

      mockTermsApi.createFull.mockResolvedValue({
        data: { term: { id: 1, hex: 'a' } },
        error: undefined
      });

      const promise = store.save();
      expect(store.isSubmitting).toBe(true);

      await promise;
      expect(store.isSubmitting).toBe(false);
    });

    it('handles API error', async () => {
      const store = getWordFormStore();
      store.isNewWord = true;
      store.textId = 1;

      mockTermsApi.createFull.mockResolvedValue({
        data: null,
        error: 'Server error'
      });

      const result = await store.save();

      expect(result.success).toBe(false);
      expect(result.error).toBe('Server error');
      expect(store.errors.general).toBe('Server error');
    });

    it('handles response error field', async () => {
      const store = getWordFormStore();
      store.isNewWord = true;
      store.textId = 1;

      mockTermsApi.createFull.mockResolvedValue({
        data: { error: 'Duplicate term' },
        error: undefined
      });

      const result = await store.save();

      expect(result.success).toBe(false);
      expect(result.error).toBe('Duplicate term');
    });

    it('handles missing term in response', async () => {
      const store = getWordFormStore();
      store.isNewWord = true;
      store.textId = 1;

      mockTermsApi.createFull.mockResolvedValue({
        data: {},
        error: undefined
      });

      const result = await store.save();

      expect(result.success).toBe(false);
      expect(result.error).toBe('No term data returned');
    });

    it('handles exceptions gracefully', async () => {
      const store = getWordFormStore();
      store.isNewWord = true;
      store.textId = 1;
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      mockTermsApi.createFull.mockRejectedValue(new Error('Network error'));

      const result = await store.save();

      expect(result.success).toBe(false);
      expect(result.error).toBe('Failed to save term');
      expect(consoleSpy).toHaveBeenCalled();
    });

    it('updates store state after successful save', async () => {
      const store = getWordFormStore();
      store.isNewWord = true;
      store.textId = 1;
      store.formData.translation = 'test';

      mockTermsApi.createFull.mockResolvedValue({
        data: { term: { id: 456, hex: 'xyz789' } },
        error: undefined
      });

      await store.save();

      expect(store.wordId).toBe(456);
      expect(store.hex).toBe('xyz789');
      expect(store.isNewWord).toBe(false);
      expect(store.originalData?.translation).toBe('test');
    });
  });

  // ===========================================================================
  // copyTranslationFromSimilar Tests
  // ===========================================================================

  describe('copyTranslationFromSimilar', () => {
    it('sets translation when empty', () => {
      const store = getWordFormStore();
      store.formData.translation = '';

      store.copyTranslationFromSimilar('hello');

      expect(store.formData.translation).toBe('hello');
    });

    it('appends to existing translation with semicolon', () => {
      const store = getWordFormStore();
      store.formData.translation = 'existing';

      store.copyTranslationFromSimilar('new');

      expect(store.formData.translation).toBe('existing; new');
    });

    it('validates translation after copying', () => {
      const store = getWordFormStore();
      store.formData.translation = 'a'.repeat(490);

      store.copyTranslationFromSimilar('a'.repeat(20));

      expect(store.errors.translation).toBe('Translation must be 500 characters or less');
    });
  });

  // ===========================================================================
  // Window Export Tests
  // ===========================================================================

  describe('Window Exports', () => {
    it('exposes getWordFormStore on window', () => {
      expect(window.getWordFormStore).toBeDefined();
    });
  });
});
