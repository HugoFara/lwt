/**
 * Tests for vocabulary/stores/word_store.ts - Word store Alpine.js store
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Hoist mock functions so they're available during vi.mock hoisting
const {
  mockTermsApi,
  mockTextsApi,
  mockInjectTextStyles,
  mockGenerateParagraphStyles,
  mockRenderText,
  mockUpdateWordStatusInDOM,
  mockUpdateWordTranslationInDOM
} = vi.hoisted(() => ({
  mockTermsApi: {
    setStatus: vi.fn(),
    createQuick: vi.fn(),
    delete: vi.fn()
  },
  mockTextsApi: {
    getWords: vi.fn()
  },
  mockInjectTextStyles: vi.fn(),
  mockGenerateParagraphStyles: vi.fn(() => 'p { margin: 0; }'),
  mockRenderText: vi.fn(() => '<span>rendered text</span>'),
  mockUpdateWordStatusInDOM: vi.fn(),
  mockUpdateWordTranslationInDOM: vi.fn()
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

// Mock TextsApi
vi.mock('../../../../src/frontend/js/modules/text/api/texts_api', () => ({
  TextsApi: mockTextsApi
}));

// Mock text_styles
vi.mock('../../../../src/frontend/js/modules/text/pages/reading/text_styles', () => ({
  injectTextStyles: mockInjectTextStyles,
  generateParagraphStyles: mockGenerateParagraphStyles
}));

// Mock text_renderer
vi.mock('../../../../src/frontend/js/modules/text/pages/reading/text_renderer', () => ({
  renderText: mockRenderText,
  updateWordStatusInDOM: mockUpdateWordStatusInDOM,
  updateWordTranslationInDOM: mockUpdateWordTranslationInDOM
}));

import Alpine from 'alpinejs';
import {
  getWordStore,
  initWordStore
} from '../../../../src/frontend/js/modules/vocabulary/stores/word_store';

describe('vocabulary/stores/word_store.ts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    // Re-initialize store for each test
    initWordStore();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ===========================================================================
  // Store Initialization Tests
  // ===========================================================================

  describe('Store initialization', () => {
    it('registers store with Alpine', () => {
      expect(Alpine.store).toHaveBeenCalledWith('words', expect.any(Object));
    });

    it('initializes with default values', () => {
      const store = getWordStore();

      expect(store.wordsByHex).toBeInstanceOf(Map);
      expect(store.wordsByHex.size).toBe(0);
      expect(store.words).toEqual([]);
      expect(store.textId).toBe(0);
      expect(store.langId).toBe(0);
      expect(store.title).toBe('');
      expect(store.audioUri).toBeNull();
      expect(store.sourceUri).toBeNull();
      expect(store.rightToLeft).toBe(false);
      expect(store.textSize).toBe(100);
      expect(store.isLoading).toBe(false);
      expect(store.isInitialized).toBe(false);
    });

    it('initializes dictLinks with empty strings', () => {
      const store = getWordStore();

      expect(store.dictLinks.dict1).toBe('');
      expect(store.dictLinks.dict2).toBe('');
      expect(store.dictLinks.translator).toBe('');
    });

    it('initializes UI state', () => {
      const store = getWordStore();

      expect(store.selectedHex).toBeNull();
      expect(store.selectedPosition).toBeNull();
      expect(store.popoverTargetElement).toBeNull();
      expect(store.isPopoverOpen).toBe(false);
      expect(store.isEditModalOpen).toBe(false);
    });

    it('initializes display settings', () => {
      const store = getWordStore();

      expect(store.showAll).toBe(false);
      expect(store.showTranslations).toBe(true);
      expect(store.renderedHtml).toBe('');
    });
  });

  // ===========================================================================
  // loadText Tests
  // ===========================================================================

  describe('loadText', () => {
    const mockConfig = {
      textId: 1,
      langId: 2,
      title: 'Test Text',
      audioUri: 'http://audio.com/file.mp3',
      sourceUri: 'http://source.com',
      audioPosition: 0,
      rightToLeft: false,
      textSize: 120,
      removeSpaces: false,
      dictLinks: { dict1: 'http://dict1.com/lwt_term', dict2: '', translator: '' },
      showLearning: 1,
      displayStatTrans: 1,
      modeTrans: 2,
      termDelimiter: ';',
      annTextSize: 60
    };

    const mockWords = [
      {
        position: 1,
        sentenceId: 1,
        text: 'Hello',
        textLc: 'hello',
        hex: 'abc123',
        isNotWord: false,
        wordCount: 1,
        hidden: false,
        wordId: 100,
        status: 2,
        translation: 'Bonjour',
        romanization: ''
      }
    ];

    it('does nothing for invalid textId', async () => {
      const store = getWordStore();

      await store.loadText(0);

      expect(mockTextsApi.getWords).not.toHaveBeenCalled();
    });

    it('does nothing for negative textId', async () => {
      const store = getWordStore();

      await store.loadText(-1);

      expect(mockTextsApi.getWords).not.toHaveBeenCalled();
    });

    it('sets isLoading during operation', async () => {
      const store = getWordStore();
      mockTextsApi.getWords.mockResolvedValue({
        data: { words: [], config: mockConfig },
        error: undefined
      });

      const promise = store.loadText(1);
      expect(store.isLoading).toBe(true);

      await promise;
      expect(store.isLoading).toBe(false);
    });

    it('loads words successfully', async () => {
      const store = getWordStore();
      mockTextsApi.getWords.mockResolvedValue({
        data: { words: mockWords, config: mockConfig },
        error: undefined
      });

      await store.loadText(1);

      expect(store.textId).toBe(1);
      expect(store.title).toBe('Test Text');
      expect(store.words).toHaveLength(1);
      expect(store.isInitialized).toBe(true);
    });

    it('handles API error', async () => {
      const store = getWordStore();
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      mockTextsApi.getWords.mockResolvedValue({
        data: null,
        error: 'Not found'
      });

      await store.loadText(1);

      expect(store.isInitialized).toBe(false);
      expect(consoleSpy).toHaveBeenCalled();
    });

    it('handles exceptions', async () => {
      const store = getWordStore();
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      mockTextsApi.getWords.mockRejectedValue(new Error('Network error'));

      await store.loadText(1);

      expect(store.isLoading).toBe(false);
      expect(consoleSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // initFromData Tests
  // ===========================================================================

  describe('initFromData', () => {
    const mockConfig = {
      textId: 5,
      langId: 3,
      title: 'Test Title',
      audioUri: 'http://audio.com/test.mp3',
      sourceUri: 'http://source.com/text',
      audioPosition: 10,
      rightToLeft: true,
      textSize: 150,
      removeSpaces: true,
      dictLinks: {
        dict1: 'http://dict1.com/lwt_term',
        dict2: 'http://dict2.com/lwt_term',
        translator: 'http://trans.com/lwt_term'
      },
      showLearning: 2,
      displayStatTrans: 3,
      modeTrans: 1,
      termDelimiter: ';',
      annTextSize: 75
    };

    const mockWords = [
      {
        position: 1,
        sentenceId: 1,
        text: 'Hello',
        textLc: 'hello',
        hex: 'abc123',
        isNotWord: false,
        wordCount: 1,
        hidden: false,
        wordId: 100,
        status: 2,
        translation: 'Bonjour',
        romanization: 'ello'
      },
      {
        position: 2,
        sentenceId: 1,
        text: ' ',
        textLc: ' ',
        hex: 'space',
        isNotWord: true,
        wordCount: 0,
        hidden: false,
        wordId: null,
        status: 0,
        translation: ''
      },
      {
        position: 3,
        sentenceId: 1,
        text: 'World',
        textLc: 'world',
        hex: 'def456',
        isNotWord: false,
        wordCount: 1,
        hidden: false,
        wordId: null,
        status: 0,
        translation: ''
      }
    ];

    it('sets configuration values', () => {
      const store = getWordStore();

      store.initFromData(mockWords, mockConfig);

      expect(store.textId).toBe(5);
      expect(store.langId).toBe(3);
      expect(store.title).toBe('Test Title');
      expect(store.audioUri).toBe('http://audio.com/test.mp3');
      expect(store.sourceUri).toBe('http://source.com/text');
      expect(store.audioPosition).toBe(10);
      expect(store.rightToLeft).toBe(true);
      expect(store.textSize).toBe(150);
      expect(store.removeSpaces).toBe(true);
    });

    it('sets dict links', () => {
      const store = getWordStore();

      store.initFromData(mockWords, mockConfig);

      expect(store.dictLinks.dict1).toBe('http://dict1.com/lwt_term');
      expect(store.dictLinks.dict2).toBe('http://dict2.com/lwt_term');
      expect(store.dictLinks.translator).toBe('http://trans.com/lwt_term');
    });

    it('sets annotation settings', () => {
      const store = getWordStore();

      store.initFromData(mockWords, mockConfig);

      expect(store.showLearning).toBe(2);
      expect(store.displayStatTrans).toBe(3);
      expect(store.modeTrans).toBe(1);
      expect(store.termDelimiter).toBe(';');
      expect(store.annTextSize).toBe(75);
    });

    it('injects text styles', () => {
      const store = getWordStore();

      store.initFromData(mockWords, mockConfig);

      expect(mockInjectTextStyles).toHaveBeenCalledWith(mockConfig);
    });

    it('generates paragraph styles', () => {
      const store = getWordStore();

      store.initFromData(mockWords, mockConfig);

      expect(mockGenerateParagraphStyles).toHaveBeenCalledWith(mockConfig);
      expect(store.paragraphStyles).toBe('p { margin: 0; }');
    });

    it('builds words array', () => {
      const store = getWordStore();

      store.initFromData(mockWords, mockConfig);

      expect(store.words).toHaveLength(3);
      expect(store.words[0].text).toBe('Hello');
      expect(store.words[0].hex).toBe('abc123');
      expect(store.words[0].status).toBe(2);
    });

    it('indexes words by hex (excluding non-words)', () => {
      const store = getWordStore();

      store.initFromData(mockWords, mockConfig);

      expect(store.wordsByHex.size).toBe(2); // 'abc123' and 'def456', not 'space'
      expect(store.wordsByHex.has('abc123')).toBe(true);
      expect(store.wordsByHex.has('def456')).toBe(true);
      expect(store.wordsByHex.has('space')).toBe(false);
    });

    it('groups words with same hex', () => {
      const store = getWordStore();
      const wordsWithDuplicate = [
        ...mockWords,
        {
          position: 4,
          sentenceId: 2,
          text: 'Hello',
          textLc: 'hello',
          hex: 'abc123', // Same hex as word 1
          isNotWord: false,
          wordCount: 1,
          hidden: false,
          wordId: 100,
          status: 2,
          translation: 'Bonjour'
        }
      ];

      store.initFromData(wordsWithDuplicate, mockConfig);

      const helloWords = store.wordsByHex.get('abc123');
      expect(helloWords).toHaveLength(2);
    });

    it('sets isInitialized to true', () => {
      const store = getWordStore();

      store.initFromData(mockWords, mockConfig);

      expect(store.isInitialized).toBe(true);
    });

    it('generates rendered HTML', () => {
      const store = getWordStore();

      store.initFromData(mockWords, mockConfig);

      expect(mockRenderText).toHaveBeenCalled();
      expect(store.renderedHtml).toBe('<span>rendered text</span>');
    });

    it('handles missing optional config values with defaults', () => {
      const store = getWordStore();
      const minimalConfig = {
        textId: 1,
        langId: 1,
        title: 'Test',
        audioUri: null,
        sourceUri: null,
        audioPosition: 0,
        rightToLeft: false,
        textSize: 100,
        dictLinks: { dict1: '', dict2: '', translator: '' }
        // Missing optional fields
      };

      store.initFromData([], minimalConfig as never);

      expect(store.showLearning).toBe(1);
      expect(store.displayStatTrans).toBe(1);
      expect(store.modeTrans).toBe(2);
      expect(store.termDelimiter).toBe('');
      expect(store.annTextSize).toBe(50);
      expect(store.removeSpaces).toBe(false);
    });
  });

  // ===========================================================================
  // getRenderedHtml Tests
  // ===========================================================================

  describe('getRenderedHtml', () => {
    it('returns empty string when no words', () => {
      const store = getWordStore();
      store.words = [];

      const result = store.getRenderedHtml();

      expect(result).toBe('');
    });

    it('calls renderText with correct settings', () => {
      const store = getWordStore();
      store.words = [{ text: 'hello' }] as never[];
      store.showAll = true;
      store.showTranslations = false;
      store.rightToLeft = true;
      store.textSize = 150;
      store.showLearning = 2;
      store.displayStatTrans = 3;
      store.modeTrans = 1;
      store.annTextSize = 75;

      store.getRenderedHtml();

      expect(mockRenderText).toHaveBeenCalledWith(
        store.words,
        expect.objectContaining({
          showAll: true,
          showTranslations: false,
          rightToLeft: true,
          textSize: 150,
          showLearning: 2,
          displayStatTrans: 3,
          modeTrans: 1,
          annTextSize: 75
        })
      );
    });
  });

  // ===========================================================================
  // setTextHtml Tests
  // ===========================================================================

  describe('setTextHtml', () => {
    it('sets element innerHTML', () => {
      const store = getWordStore();
      store.renderedHtml = '<p>test content</p>';
      const mockEl = { innerHTML: '' } as HTMLElement;

      store.setTextHtml(mockEl);

      expect(mockEl.innerHTML).toBe('<p>test content</p>');
    });
  });

  // ===========================================================================
  // Selection Methods Tests
  // ===========================================================================

  describe('selectWord', () => {
    it('sets selection state', () => {
      const store = getWordStore();
      const mockElement = document.createElement('span');

      store.selectWord('abc123', 5, mockElement);

      expect(store.selectedHex).toBe('abc123');
      expect(store.selectedPosition).toBe(5);
      expect(store.popoverTargetElement).toBe(mockElement);
      expect(store.isPopoverOpen).toBe(true);
    });

    it('works without target element', () => {
      const store = getWordStore();

      store.selectWord('abc123', 5);

      expect(store.popoverTargetElement).toBeNull();
      expect(store.isPopoverOpen).toBe(true);
    });
  });

  describe('closePopover', () => {
    it('clears selection state', () => {
      const store = getWordStore();
      store.selectedHex = 'abc123';
      store.selectedPosition = 5;
      store.popoverTargetElement = document.createElement('span');
      store.isPopoverOpen = true;

      store.closePopover();

      expect(store.isPopoverOpen).toBe(false);
      expect(store.popoverTargetElement).toBeNull();
      expect(store.selectedHex).toBeNull();
      expect(store.selectedPosition).toBeNull();
    });
  });

  describe('openEditModal', () => {
    it('closes popover and opens modal', () => {
      const store = getWordStore();
      store.isPopoverOpen = true;

      store.openEditModal();

      expect(store.isPopoverOpen).toBe(false);
      expect(store.isEditModalOpen).toBe(true);
    });
  });

  describe('closeEditModal', () => {
    it('closes modal and clears selection', () => {
      const store = getWordStore();
      store.isEditModalOpen = true;
      store.selectedHex = 'abc';
      store.selectedPosition = 1;
      store.popoverTargetElement = document.createElement('span');

      store.closeEditModal();

      expect(store.isEditModalOpen).toBe(false);
      expect(store.selectedHex).toBeNull();
      expect(store.selectedPosition).toBeNull();
      expect(store.popoverTargetElement).toBeNull();
    });
  });

  // ===========================================================================
  // getSelectedWord Tests
  // ===========================================================================

  describe('getSelectedWord', () => {
    it('returns null when no selection', () => {
      const store = getWordStore();
      store.selectedHex = null;

      expect(store.getSelectedWord()).toBeNull();
    });

    it('returns null when position is null', () => {
      const store = getWordStore();
      store.selectedHex = 'abc';
      store.selectedPosition = null;

      expect(store.getSelectedWord()).toBeNull();
    });

    it('returns null when hex not found', () => {
      const store = getWordStore();
      store.selectedHex = 'notfound';
      store.selectedPosition = 1;

      expect(store.getSelectedWord()).toBeNull();
    });

    it('returns word by position', () => {
      const store = getWordStore();
      const word1 = { position: 1, text: 'hello' };
      const word2 = { position: 5, text: 'hello again' };
      store.wordsByHex.set('abc', [word1, word2] as never[]);
      store.selectedHex = 'abc';
      store.selectedPosition = 5;

      const result = store.getSelectedWord();

      expect(result).toBe(word2);
    });

    it('returns first word if position not found', () => {
      const store = getWordStore();
      const word1 = { position: 1, text: 'hello' };
      store.wordsByHex.set('abc', [word1] as never[]);
      store.selectedHex = 'abc';
      store.selectedPosition = 99; // Not found

      const result = store.getSelectedWord();

      expect(result).toBe(word1);
    });
  });

  // ===========================================================================
  // getWordsByHex Tests
  // ===========================================================================

  describe('getWordsByHex', () => {
    it('returns empty array for unknown hex', () => {
      const store = getWordStore();

      expect(store.getWordsByHex('unknown')).toEqual([]);
    });

    it('returns words for known hex', () => {
      const store = getWordStore();
      const words = [{ text: 'hello' }];
      store.wordsByHex.set('abc', words as never[]);

      expect(store.getWordsByHex('abc')).toBe(words);
    });
  });

  // ===========================================================================
  // setStatus Tests
  // ===========================================================================

  describe('setStatus', () => {
    it('returns false for unknown hex', async () => {
      const store = getWordStore();

      const result = await store.setStatus('unknown', 3);

      expect(result).toBe(false);
      expect(mockTermsApi.setStatus).not.toHaveBeenCalled();
    });

    it('returns false when word has no wordId', async () => {
      const store = getWordStore();
      store.wordsByHex.set('abc', [{ wordId: null }] as never[]);

      const result = await store.setStatus('abc', 3);

      expect(result).toBe(false);
    });

    it('calls API with correct parameters', async () => {
      const store = getWordStore();
      store.wordsByHex.set('abc', [{ wordId: 123, status: 1 }] as never[]);
      mockTermsApi.setStatus.mockResolvedValue({ data: {}, error: undefined });

      await store.setStatus('abc', 3);

      expect(mockTermsApi.setStatus).toHaveBeenCalledWith(123, 3);
    });

    it('updates store and DOM on success', async () => {
      const store = getWordStore();
      const word = { wordId: 123, status: 1 };
      store.wordsByHex.set('abc', [word] as never[]);
      mockTermsApi.setStatus.mockResolvedValue({ data: {}, error: undefined });

      const result = await store.setStatus('abc', 3);

      expect(result).toBe(true);
      expect(mockUpdateWordStatusInDOM).toHaveBeenCalledWith('abc', 3, 123);
    });

    it('closes popover on success', async () => {
      const store = getWordStore();
      store.wordsByHex.set('abc', [{ wordId: 123 }] as never[]);
      store.isPopoverOpen = true;
      mockTermsApi.setStatus.mockResolvedValue({ data: {}, error: undefined });

      await store.setStatus('abc', 3);

      expect(store.isPopoverOpen).toBe(false);
    });

    it('returns false on API error', async () => {
      const store = getWordStore();
      store.wordsByHex.set('abc', [{ wordId: 123 }] as never[]);
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      mockTermsApi.setStatus.mockResolvedValue({ data: null, error: 'Failed' });

      const result = await store.setStatus('abc', 3);

      expect(result).toBe(false);
      expect(consoleSpy).toHaveBeenCalled();
    });

    it('handles exceptions', async () => {
      const store = getWordStore();
      store.wordsByHex.set('abc', [{ wordId: 123 }] as never[]);
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      mockTermsApi.setStatus.mockRejectedValue(new Error('Network'));

      const result = await store.setStatus('abc', 3);

      expect(result).toBe(false);
      expect(store.isLoading).toBe(false);
      expect(consoleSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // createQuickWord Tests
  // ===========================================================================

  describe('createQuickWord', () => {
    it('calls API with correct parameters', async () => {
      const store = getWordStore();
      store.textId = 5;
      store.wordsByHex.set('abc', [{ wordId: null }] as never[]);
      mockTermsApi.createQuick.mockResolvedValue({
        data: { term_id: 999 },
        error: undefined
      });

      await store.createQuickWord('abc', 10, 99);

      expect(mockTermsApi.createQuick).toHaveBeenCalledWith(5, 10, 99);
    });

    it('updates store and DOM on success', async () => {
      const store = getWordStore();
      store.textId = 5;
      const word = { wordId: null, status: 0 };
      store.wordsByHex.set('abc', [word] as never[]);
      mockTermsApi.createQuick.mockResolvedValue({
        data: { term_id: 999 },
        error: undefined
      });

      const result = await store.createQuickWord('abc', 10, 98);

      expect(result).toBe(true);
      expect(mockUpdateWordStatusInDOM).toHaveBeenCalledWith('abc', 98, 999);
    });

    it('returns false on API error', async () => {
      const store = getWordStore();
      store.textId = 5;
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      mockTermsApi.createQuick.mockResolvedValue({
        data: null,
        error: 'Failed'
      });

      const result = await store.createQuickWord('abc', 10, 99);

      expect(result).toBe(false);
      expect(consoleSpy).toHaveBeenCalled();
    });

    it('returns false when no term_id returned', async () => {
      const store = getWordStore();
      store.textId = 5;
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      mockTermsApi.createQuick.mockResolvedValue({
        data: {},
        error: undefined
      });

      const result = await store.createQuickWord('abc', 10, 99);

      expect(result).toBe(false);
      expect(consoleSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // deleteWord Tests
  // ===========================================================================

  describe('deleteWord', () => {
    it('returns false for unknown hex', async () => {
      const store = getWordStore();

      const result = await store.deleteWord('unknown');

      expect(result).toBe(false);
    });

    it('returns false when word has no wordId', async () => {
      const store = getWordStore();
      store.wordsByHex.set('abc', [{ wordId: null }] as never[]);

      const result = await store.deleteWord('abc');

      expect(result).toBe(false);
    });

    it('calls API and updates store/DOM on success', async () => {
      const store = getWordStore();
      store.wordsByHex.set('abc', [{ wordId: 123, status: 3 }] as never[]);
      mockTermsApi.delete.mockResolvedValue({ data: {}, error: undefined });

      const result = await store.deleteWord('abc');

      expect(result).toBe(true);
      expect(mockTermsApi.delete).toHaveBeenCalledWith(123);
      expect(mockUpdateWordStatusInDOM).toHaveBeenCalledWith('abc', 0, null);
      expect(mockUpdateWordTranslationInDOM).toHaveBeenCalledWith('abc', '', '');
    });

    it('returns false on API error', async () => {
      const store = getWordStore();
      store.wordsByHex.set('abc', [{ wordId: 123 }] as never[]);
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      mockTermsApi.delete.mockResolvedValue({ data: null, error: 'Failed' });

      const result = await store.deleteWord('abc');

      expect(result).toBe(false);
      expect(consoleSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // getDictUrl Tests
  // ===========================================================================

  describe('getDictUrl', () => {
    it('returns # without selected word', () => {
      const store = getWordStore();
      store.selectedHex = null;

      expect(store.getDictUrl('dict1')).toBe('#');
    });

    it('returns # without template', () => {
      const store = getWordStore();
      store.wordsByHex.set('abc', [{ position: 1, text: 'hello' }] as never[]);
      store.selectedHex = 'abc';
      store.selectedPosition = 1;
      store.dictLinks.dict1 = '';

      expect(store.getDictUrl('dict1')).toBe('#');
    });

    it('replaces lwt_term with encoded word', () => {
      const store = getWordStore();
      store.wordsByHex.set('abc', [{ position: 1, text: 'café' }] as never[]);
      store.selectedHex = 'abc';
      store.selectedPosition = 1;
      store.dictLinks.dict1 = 'http://dict.com/lwt_term';

      expect(store.getDictUrl('dict1')).toBe('http://dict.com/caf%C3%A9');
    });

    it('returns correct URL for each dictionary type', () => {
      const store = getWordStore();
      store.wordsByHex.set('abc', [{ position: 1, text: 'hello' }] as never[]);
      store.selectedHex = 'abc';
      store.selectedPosition = 1;
      store.dictLinks.dict1 = 'http://d1.com/lwt_term';
      store.dictLinks.dict2 = 'http://d2.com/lwt_term';
      store.dictLinks.translator = 'http://t.com/lwt_term';

      expect(store.getDictUrl('dict1')).toBe('http://d1.com/hello');
      expect(store.getDictUrl('dict2')).toBe('http://d2.com/hello');
      expect(store.getDictUrl('translator')).toBe('http://t.com/hello');
    });
  });

  // ===========================================================================
  // updateWordInStore Tests
  // ===========================================================================

  describe('updateWordInStore', () => {
    it('does nothing for unknown hex', () => {
      const store = getWordStore();

      store.updateWordInStore('unknown', { status: 3 });

      // Should not throw
    });

    it('updates all words with hex', () => {
      const store = getWordStore();
      const word1 = { status: 1, translation: '' };
      const word2 = { status: 1, translation: '' };
      store.wordsByHex.set('abc', [word1, word2] as never[]);

      store.updateWordInStore('abc', { status: 3, translation: 'hello' });

      expect(word1.status).toBe(3);
      expect(word1.translation).toBe('hello');
      expect(word2.status).toBe(3);
      expect(word2.translation).toBe('hello');
    });

    it('triggers reactivity by updating Map entry', () => {
      const store = getWordStore();
      const originalWords = [{ status: 1 }];
      store.wordsByHex.set('abc', originalWords as never[]);

      store.updateWordInStore('abc', { status: 3 });

      const updatedWords = store.wordsByHex.get('abc');
      expect(updatedWords).not.toBe(originalWords); // New array reference
    });
  });

  // ===========================================================================
  // Window Export Tests
  // ===========================================================================

  describe('Window Exports', () => {
    it('exposes getWordStore on window', () => {
      expect(window.getWordStore).toBeDefined();
    });
  });
});
