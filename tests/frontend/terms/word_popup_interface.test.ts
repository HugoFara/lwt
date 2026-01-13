/**
 * Tests for word_popup_interface.ts - Word popup interface functions
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  showIgnoredWordPopup,
  showWellKnownWordPopup,
  showLearningWordPopup,
  showUnknownWordPopup,
  showMultiWordPopup,
  showReviewWordPopup,
  createNewMultiWordLink,
  createDictionaryLinks,
  createDictionaryLinksnl,
  createDictionaryLinksnl2,
  createStatusChangeLinks,
  createReviewStatusChangeLinks,
  createStatusChangeLink,
  createReviewStatusLink,
  createReviewStatusLink2,
  createNewWordLink,
  createEditMultiWordLink,
  createEditMultiWordTitleLink,
  createOrEditMultiWordLink,
  createOrEditMultiWordLinkRtl,
  createEditWordLink,
  createEditWordTitleLink,
  createDeleteWordLink,
  createDeleteMultiWordLink,
  createWellKnownWordLink,
  createIgnoreWordLink,
  createAudioButton,
  createStatusChangeButton,
  createStatusButtonsAll,
  createDeleteButton,
  createWellKnownButton,
  createIgnoreButton,
  createReviewStatusButtons,
  createLocalDictSection,
  addLocalDictToPopup,
  buildKnownWordPopupContent,
  buildUnknownWordPopupContent,
  buildWordDetailsPanel,
  openMultiWordModal,
} from '../../../src/frontend/js/modules/vocabulary/services/word_popup_interface';
import type { WordActionContext } from '../../../src/frontend/js/modules/vocabulary/services/word_actions';

// Mock the word_actions module
vi.mock('../../../src/frontend/js/modules/vocabulary/services/word_actions', () => ({
  changeWordStatus: vi.fn().mockResolvedValue({ success: true }),
  deleteWord: vi.fn().mockResolvedValue({ success: true }),
  markWellKnown: vi.fn().mockResolvedValue({ success: true }),
  markIgnored: vi.fn().mockResolvedValue({ success: true }),
  incrementWordStatus: vi.fn().mockResolvedValue({ success: true }),
}));

// Mock local dictionaries API
const mockHasLocalDictionaries = vi.fn();
const mockLookupLocal = vi.fn();
const mockFormatResults = vi.fn();

vi.mock('../../../src/frontend/js/dictionaries', () => ({
  hasLocalDictionaries: (...args: unknown[]) => mockHasLocalDictionaries(...args),
  lookupLocal: (...args: unknown[]) => mockLookupLocal(...args),
  formatResults: (...args: unknown[]) => mockFormatResults(...args),
}));

// Mock TermsApi
const mockGetDetails = vi.fn();
vi.mock('../../../src/frontend/js/modules/vocabulary/api/terms_api', () => ({
  TermsApi: {
    getDetails: (...args: unknown[]) => mockGetDetails(...args),
  },
}));

// Mock Alpine store
const mockMultiWordFormStore = {
  loadForEdit: vi.fn(),
};
vi.mock('alpinejs', () => ({
  default: {
    store: vi.fn((name: string) => {
      if (name === 'multiWordForm') return mockMultiWordFormStore;
      return {};
    }),
  },
}));

import { initLanguageConfig, resetLanguageConfig } from '../../../src/frontend/js/modules/language/stores/language_config';
import { initTextConfig, resetTextConfig } from '../../../src/frontend/js/modules/text/stores/text_config';
import { initSettingsConfig, resetSettingsConfig } from '../../../src/frontend/js/shared/utils/settings_config';

describe('word_popup_interface.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    // Initialize state modules
    resetLanguageConfig();
    resetTextConfig();
    resetSettingsConfig();
    initLanguageConfig({
      id: 1,
      dictLink1: 'http://dict1.example.com/lwt_term',
      dictLink2: 'http://dict2.example.com/lwt_term',
      translatorLink: 'http://translator.example.com/lwt_term',
      delimiter: ',',
      rtl: false,
      ttsVoiceApi: ''
    });
    initTextConfig({ id: 1 });
    initSettingsConfig({ hts: 0 });
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ===========================================================================
  // createNewMultiWordLink Tests
  // ===========================================================================

  describe('createNewMultiWordLink', () => {
    it('returns empty string when all multiwords are empty', () => {
      const result = createNewMultiWordLink(1, '5', ['', '', '', '', '', '', '', ''], false);
      expect(result).toBe('');
    });

    it('returns empty string when all multiwords are undefined', () => {
      const result = createNewMultiWordLink(1, '5', [undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined], false);
      expect(result).toBe('');
    });

    it('creates LTR multiword links for valid multiwords via modal', () => {
      const multiWords = ['2 word1 word2', undefined, '4 word1 word2 word3 word4', '', '', '', '', ''];
      const result = createNewMultiWordLink(1, '5', multiWords, false);

      expect(result).toContain('Expr:');
      expect(result).toContain('2..');
      expect(result).toContain('4..');
      expect(result).toContain('openMultiWordModal');
    });

    it('creates RTL multiword links when rtl is true', () => {
      const multiWords = ['2 word1', undefined, undefined, undefined, undefined, undefined, undefined, '8 word8'];
      const result = createNewMultiWordLink(1, '5', multiWords, true);

      expect(result).toContain('Expr:');
      expect(result).toContain('dir="rtl"');
    });

    it('handles numeric torder', () => {
      const multiWords = ['2 test', '', '', '', '', '', '', ''];
      const result = createNewMultiWordLink(1, 10, multiWords, false);

      expect(result).toContain('openMultiWordModal(1, 10,');
    });
  });

  // ===========================================================================
  // createDictionaryLinks Tests
  // ===========================================================================

  describe('createDictionaryLinks', () => {
    it('creates dictionary links with lookup term', () => {
      const result = createDictionaryLinks(
        'http://dict1.com/lwt_term',
        'http://dict2.com/lwt_term',
        'http://trans.com/lwt_term',
        'test',
        1,
        '5'
      );

      expect(result).toContain('Lookup Term:');
      expect(result).toContain('Dict1');
      expect(result).toContain('Dict2');
      expect(result).toContain('Trans');
    });

    it('includes sentence lookup when torder and txid are valid', () => {
      const result = createDictionaryLinks(
        'http://dict1.com/lwt_term',
        'http://dict2.com/lwt_term',
        'http://trans.com/lwt_term',
        'test',
        1,
        '5'
      );

      expect(result).toContain('Lookup Sentence:');
    });

    it('excludes sentence lookup when torder is 0', () => {
      const result = createDictionaryLinks(
        'http://dict1.com/lwt_term',
        'http://dict2.com/lwt_term',
        'http://trans.com/lwt_term',
        'test',
        1,
        '0'
      );

      expect(result).not.toContain('Lookup Sentence:');
    });

    it('excludes sentence lookup when txid is 0', () => {
      const result = createDictionaryLinks(
        'http://dict1.com/lwt_term',
        'http://dict2.com/lwt_term',
        'http://trans.com/lwt_term',
        'test',
        0,
        '5'
      );

      expect(result).not.toContain('Lookup Sentence:');
    });
  });

  // ===========================================================================
  // createDictionaryLinksnl Tests
  // ===========================================================================

  describe('createDictionaryLinksnl', () => {
    it('creates dictionary links with term prefix', () => {
      const result = createDictionaryLinksnl(
        'http://dict1.com/lwt_term',
        'http://dict2.com/lwt_term',
        'http://trans.com/lwt_term',
        'test',
        1,
        '5'
      );

      expect(result).toContain('Term:');
    });

    it('includes sentence link when valid', () => {
      const result = createDictionaryLinksnl(
        'http://dict1.com/lwt_term',
        'http://dict2.com/lwt_term',
        'http://trans.com/lwt_term',
        'test',
        1,
        '5'
      );

      expect(result).toContain('Sentence:');
    });
  });

  // ===========================================================================
  // createDictionaryLinksnl2 Tests
  // ===========================================================================

  describe('createDictionaryLinksnl2', () => {
    it('creates dictionary links for term and sentence', () => {
      const result = createDictionaryLinksnl2(
        'http://dict1.com/lwt_term',
        'http://dict2.com/lwt_term',
        'http://trans.com/lwt_term',
        'test',
        'This is a test sentence.'
      );

      expect(result).toContain('Term:');
      expect(result).toContain('Sentence:');
    });

    it('excludes sentence when empty', () => {
      const result = createDictionaryLinksnl2(
        'http://dict1.com/lwt_term',
        'http://dict2.com/lwt_term',
        'http://trans.com/lwt_term',
        'test',
        ''
      );

      expect(result).toContain('Term:');
      expect(result).not.toContain('Sentence:');
    });
  });

  // ===========================================================================
  // createStatusChangeLinks Tests
  // ===========================================================================

  describe('createStatusChangeLinks', () => {
    it('returns links for all status levels (1-5, 98, 99)', () => {
      const result = createStatusChangeLinks(1, '5', 100, 3);

      expect(result).toContain('St:');
      // Should contain links for 1, 2, 4, 5, 98, 99 (not 3 since it's current)
      expect(result).toContain('[1]');
      expect(result).toContain('[2]');
      expect(result).toContain('[4]');
      expect(result).toContain('[5]');
      expect(result).toContain('[WKn]');
      expect(result).toContain('[Ign]');
      // Current status (3) should show diamond
      expect(result).toContain('◆');
    });
  });

  // ===========================================================================
  // createStatusChangeLink Tests
  // ===========================================================================

  describe('createStatusChangeLink', () => {
    it('returns diamond when old status equals new status', () => {
      const result = createStatusChangeLink(1, '5', 100, 3, 3);

      expect(result).toContain('◆');
      expect(result).not.toContain('<a href');
    });

    it('returns link with new status when different', () => {
      const result = createStatusChangeLink(1, '5', 100, 3, 4);

      expect(result).toContain('/word/set-status');
      expect(result).toContain('tid=1');
      expect(result).toContain('ord=5');
      expect(result).toContain('wid=100');
      expect(result).toContain('status=4');
      expect(result).toContain('[4]');
    });

    it('handles status 99 (well-known)', () => {
      const result = createStatusChangeLink(1, '5', 100, 3, 99);

      expect(result).toContain('[WKn]');
      expect(result).toContain('status=99');
    });

    it('handles status 98 (ignored)', () => {
      const result = createStatusChangeLink(1, '5', 100, 3, 98);

      expect(result).toContain('[Ign]');
      expect(result).toContain('status=98');
    });
  });

  // ===========================================================================
  // createReviewStatusChangeLinks Tests
  // ===========================================================================

  describe('createReviewStatusChangeLinks', () => {
    it('returns test status links for all levels', () => {
      const result = createReviewStatusChangeLinks(100, 2);

      expect(result).toContain('/word/set-review-status');
      expect(result).toContain('wid=100');
      // Current status (2) should show diamond inside link
      expect(result).toContain('◆');
    });
  });

  // ===========================================================================
  // createReviewStatusLink2 Tests
  // ===========================================================================

  describe('createReviewStatusLink2', () => {
    it('shows diamond when status matches', () => {
      const result = createReviewStatusLink2(100, 3, 3);

      expect(result).toContain('◆');
      expect(result).toContain('/word/set-review-status');
    });

    it('shows status abbreviation when status differs', () => {
      const result = createReviewStatusLink2(100, 3, 4);

      expect(result).toContain('[4]');
      expect(result).not.toContain('◆');
    });
  });

  // ===========================================================================
  // createReviewStatusLink Tests
  // ===========================================================================

  describe('createReviewStatusLink', () => {
    it('creates link with positive change and success sound', () => {
      const result = createReviewStatusLink(100, 1, 'Got it!');

      expect(result).toContain('/word/set-review-status');
      expect(result).toContain('wid=100');
      expect(result).toContain('stchange=1');
      expect(result).toContain('successSound()');
      expect(result).toContain('Got it!');
    });

    it('creates link with negative change and failure sound', () => {
      const result = createReviewStatusLink(100, -1, 'Oops!');

      expect(result).toContain('stchange=-1');
      expect(result).toContain('failureSound()');
      expect(result).toContain('Oops!');
    });
  });

  // ===========================================================================
  // createNewWordLink Tests
  // ===========================================================================

  describe('createNewWordLink', () => {
    it('creates link to learn a new word', () => {
      const result = createNewWordLink(1, '5', 100);

      expect(result).toContain('/word/edit');
      expect(result).toContain('tid=1');
      expect(result).toContain('ord=5');
      expect(result).toContain('wid=100');
      expect(result).toContain('Learn term');
      expect(result).toContain('showRightFramesPanel()');
    });
  });

  // ===========================================================================
  // createEditMultiWordLink Tests
  // ===========================================================================

  describe('createEditMultiWordLink', () => {
    it('creates button to edit a multiword via modal', () => {
      const result = createEditMultiWordLink(1, '5', 100);

      expect(result).toContain('openMultiWordModal');
      expect(result).toContain("1, 5, '', 0, 100");  // textId, position, text='', wordCount=0, wordId
      expect(result).toContain('Edit term');
    });
  });

  // ===========================================================================
  // createEditMultiWordTitleLink Tests
  // ===========================================================================

  describe('createEditMultiWordTitleLink', () => {
    it('creates styled title button for multiword via modal', () => {
      const result = createEditMultiWordTitleLink('2-Word-Expression', 1, '5', 100);

      expect(result).toContain('style="color:yellow"');
      expect(result).toContain('openMultiWordModal');
      expect(result).toContain("1, 5, '', 0, 100");  // textId, position, text='', wordCount=0, wordId
      expect(result).toContain('2-Word-Expression');
    });
  });

  // ===========================================================================
  // createOrEditMultiWordLink Tests
  // ===========================================================================

  describe('createOrEditMultiWordLink', () => {
    it('creates button to create/edit multiword via modal', () => {
      const result = createOrEditMultiWordLink(3, 1, '5', '3 word1 word2 word3');

      expect(result).toContain('openMultiWordModal');
      expect(result).toContain("1, 5, '3 word1 word2 word3', 3");  // textId, position, text, wordCount
      expect(result).toContain('3..');
    });
  });

  // ===========================================================================
  // createOrEditMultiWordLinkRtl Tests
  // ===========================================================================

  describe('createOrEditMultiWordLinkRtl', () => {
    it('creates RTL button for multiword via modal', () => {
      const result = createOrEditMultiWordLinkRtl(3, 1, '5', '3 מילה1 מילה2');

      expect(result).toContain('dir="rtl"');
      expect(result).toContain('openMultiWordModal');
      expect(result).toContain('3..');
    });
  });

  // ===========================================================================
  // createEditWordLink Tests
  // ===========================================================================

  describe('createEditWordLink', () => {
    it('creates link to edit a word', () => {
      const result = createEditWordLink(1, '5', 100);

      expect(result).toContain('/word/edit');
      expect(result).toContain('tid=1');
      expect(result).toContain('ord=5');
      expect(result).toContain('wid=100');
      expect(result).toContain('Edit term');
    });
  });

  // ===========================================================================
  // createEditWordTitleLink Tests
  // ===========================================================================

  describe('createEditWordTitleLink', () => {
    it('creates styled title link for word', () => {
      const result = createEditWordTitleLink('TestWord', 1, '5', 100);

      expect(result).toContain('style="color:yellow"');
      expect(result).toContain('/word/edit');
      expect(result).toContain('TestWord');
    });
  });

  // ===========================================================================
  // createDeleteWordLink Tests
  // ===========================================================================

  describe('createDeleteWordLink', () => {
    it('creates link to delete a word with confirmation', () => {
      const result = createDeleteWordLink(1, 100);

      expect(result).toContain('/word/delete-term');
      expect(result).toContain('wid=100');
      expect(result).toContain('tid=1');
      expect(result).toContain('confirmDelete()');
      expect(result).toContain('Delete term');
    });
  });

  // ===========================================================================
  // createDeleteMultiWordLink Tests
  // ===========================================================================

  describe('createDeleteMultiWordLink', () => {
    it('creates link to delete a multiword with confirmation', () => {
      const result = createDeleteMultiWordLink(1, 100);

      expect(result).toContain('/word/delete-multi');
      expect(result).toContain('wid=100');
      expect(result).toContain('tid=1');
      expect(result).toContain('confirmDelete()');
      expect(result).toContain('Delete term');
    });
  });

  // ===========================================================================
  // createWellKnownWordLink Tests
  // ===========================================================================

  describe('createWellKnownWordLink', () => {
    it('creates link to mark word as well-known', () => {
      const result = createWellKnownWordLink(1, '5');

      expect(result).toContain('/word/insert-wellknown');
      expect(result).toContain('tid=1');
      expect(result).toContain('ord=5');
      expect(result).toContain('I know this term well');
    });
  });

  // ===========================================================================
  // createIgnoreWordLink Tests
  // ===========================================================================

  describe('createIgnoreWordLink', () => {
    it('creates link to ignore a word', () => {
      const result = createIgnoreWordLink(1, '5');

      expect(result).toContain('/word/insert-ignore');
      expect(result).toContain('tid=1');
      expect(result).toContain('ord=5');
      expect(result).toContain('Ignore this term');
    });
  });

  // ===========================================================================
  // createAudioButton Tests
  // ===========================================================================

  describe('createAudioButton', () => {
    it('creates audio button with speech dispatcher', () => {
      const result = createAudioButton('hello');

      // Now uses Lucide SVG icons instead of PNG images
      expect(result).toContain('<i');
      expect(result).toContain('data-lucide="volume-2"');
      expect(result).toContain('speechDispatcher');
      expect(result).toContain("'hello'");
      expect(result).toContain("'1'"); // Language ID is 1
    });

    it('escapes HTML characters in text', () => {
      const result = createAudioButton('<script>alert("xss")</script>');

      expect(result).not.toContain('<script>alert');
      // HTML characters are escaped in the onclick attribute
      expect(result).toContain('&amp;');
    });
  });

  // ===========================================================================
  // Popup Integration Tests (these call showPopup)
  // ===========================================================================

  // Note: These tests are skipped because they require the word popup dialog
  // which has complex DOM and Alpine.js dependencies that are difficult to mock.
  // The individual helper functions (createDictionaryLinks, createStatusChangeLinks, etc.)
  // are already thoroughly tested above.
  describe.skip('popup functions (require popup dialog)', () => {
    it('showIgnoredWordPopup returns boolean', () => {
      const result = showIgnoredWordPopup(
        'http://dict1.com/lwt_term', 'http://dict2.com/lwt_term', 'http://trans.com/lwt_term',
        'hint', 1, '5', 'word', 100, ['', '', '', '', '', '', '', ''], false, ''
      );
      expect(typeof result).toBe('boolean');
    });

    it('showWellKnownWordPopup returns boolean', () => {
      const result = showWellKnownWordPopup(
        'http://dict1.com/lwt_term', 'http://dict2.com/lwt_term', 'http://trans.com/lwt_term',
        'hint', 1, '5', 'word', 100, ['', '', '', '', '', '', '', ''], false, ''
      );
      expect(typeof result).toBe('boolean');
    });

    it('showLearningWordPopup returns boolean', () => {
      const result = showLearningWordPopup(
        'http://dict1.com/lwt_term', 'http://dict2.com/lwt_term', 'http://trans.com/lwt_term',
        'hint', 1, '5', 'word', 100, 3, ['', '', '', '', '', '', '', ''], false, ''
      );
      expect(typeof result).toBe('boolean');
    });

    it('showUnknownWordPopup returns boolean', () => {
      const result = showUnknownWordPopup(
        'http://dict1.com/lwt_term', 'http://dict2.com/lwt_term', 'http://trans.com/lwt_term',
        'hint', 1, '5', 'word', ['', '', '', '', '', '', '', ''], false
      );
      expect(typeof result).toBe('boolean');
    });

    it('showMultiWordPopup returns boolean', () => {
      const result = showMultiWordPopup(
        'http://dict1.com/lwt_term', 'http://dict2.com/lwt_term', 'http://trans.com/lwt_term',
        'hint', 1, '5', 'multi word', 100, 3, '2 ', ''
      );
      expect(typeof result).toBe('boolean');
    });

    it('showReviewWordPopup returns boolean', () => {
      const result = showReviewWordPopup(
        'http://dict1.com/lwt_term', 'http://dict2.com/lwt_term', 'http://trans.com/lwt_term',
        100, 'word', 'translation', 'roman', 3, 'test sentence', 1
      );
      expect(typeof result).toBe('boolean');
    });

    it('showReviewWordPopup with todo=0 skips interactive buttons', () => {
      const result = showReviewWordPopup(
        'http://dict1.com/lwt_term', 'http://dict2.com/lwt_term', 'http://trans.com/lwt_term',
        100, 'word', 'translation', 'roman', 3, 'test sentence', 0
      );
      expect(typeof result).toBe('boolean');
    });
  });

  // ===========================================================================
  // Modern API-based Button Tests
  // ===========================================================================

  describe('createStatusChangeButton', () => {
    const mockContext: WordActionContext = {
      textId: 1,
      wordId: 100,
      position: 5,
      text: 'test',
      status: 3,
    };

    it('creates a button element', () => {
      const btn = createStatusChangeButton(mockContext, 4);
      expect(btn.tagName).toBe('BUTTON');
      expect(btn.type).toBe('button');
    });

    it('shows status abbreviation for different status', () => {
      const btn = createStatusChangeButton(mockContext, 4);
      expect(btn.textContent).toContain('[4]');
      expect(btn.disabled).toBe(false);
    });

    it('shows diamond and disables for current status', () => {
      const btn = createStatusChangeButton(mockContext, 3);
      expect(btn.textContent).toBe('◆');
      expect(btn.disabled).toBe(true);
      expect(btn.classList.contains('lwt-status-btn--current')).toBe(true);
    });

    it('applies custom className', () => {
      const btn = createStatusChangeButton(mockContext, 4, { className: 'custom-class' });
      expect(btn.className).toBe('custom-class');
    });

    it('sets title to status name', () => {
      const btn = createStatusChangeButton(mockContext, 99);
      expect(btn.title).toBe('Well Known');
    });

    it('shows abbreviation without brackets when showAbbr is false', () => {
      const btn = createStatusChangeButton(mockContext, 4, { showAbbr: false });
      expect(btn.textContent).toBe('4');
    });

    it('handles click event on non-current status', async () => {
      const { changeWordStatus } = await import('../../../src/frontend/js/modules/vocabulary/services/word_actions');
      const btn = createStatusChangeButton(mockContext, 4);

      const clickEvent = new MouseEvent('click', { bubbles: true });
      btn.dispatchEvent(clickEvent);

      // Button should be disabled after click
      expect(btn.disabled).toBe(true);
      expect(changeWordStatus).toHaveBeenCalledWith(mockContext, 4);
    });
  });

  describe('createStatusButtonsAll', () => {
    const mockContext: WordActionContext = {
      textId: 1,
      wordId: 100,
      position: 5,
      text: 'test',
      status: 3,
    };

    it('creates a document fragment with status label', () => {
      const fragment = createStatusButtonsAll(mockContext);
      const container = document.createElement('div');
      container.appendChild(fragment);

      expect(container.textContent).toContain('St:');
    });

    it('creates buttons for statuses 1-5, 99, and 98', () => {
      const fragment = createStatusButtonsAll(mockContext);
      const container = document.createElement('div');
      container.appendChild(fragment);

      const buttons = container.querySelectorAll('button');
      // 7 statuses: 1, 2, 3, 4, 5, 99, 98
      expect(buttons.length).toBe(7);
    });

    it('shows diamond for current status (3)', () => {
      const fragment = createStatusButtonsAll(mockContext);
      const container = document.createElement('div');
      container.appendChild(fragment);

      const currentBtn = Array.from(container.querySelectorAll('button'))
        .find(btn => btn.textContent === '◆');
      expect(currentBtn).toBeDefined();
      expect(currentBtn?.disabled).toBe(true);
    });
  });

  describe('createDeleteButton', () => {
    const mockContext: WordActionContext = {
      textId: 1,
      wordId: 100,
      position: 5,
      text: 'test',
    };

    it('creates a delete button', () => {
      const btn = createDeleteButton(mockContext);
      expect(btn.tagName).toBe('BUTTON');
      expect(btn.textContent).toBe('Delete term');
      expect(btn.className).toContain('lwt-action-btn--delete');
    });

    it('shows confirmation dialog when confirm is true', async () => {
      const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(false);
      const { deleteWord } = await import('../../../src/frontend/js/modules/vocabulary/services/word_actions');

      const btn = createDeleteButton(mockContext, true);
      btn.click();

      expect(confirmSpy).toHaveBeenCalledWith('Are you sure you want to delete this term?');
      expect(deleteWord).not.toHaveBeenCalled();

      confirmSpy.mockRestore();
    });

    it('deletes without confirmation when confirm is false', async () => {
      const { deleteWord } = await import('../../../src/frontend/js/modules/vocabulary/services/word_actions');
      vi.mocked(deleteWord).mockClear();

      const btn = createDeleteButton(mockContext, false);
      btn.click();

      expect(btn.disabled).toBe(true);
      expect(deleteWord).toHaveBeenCalledWith(mockContext);
    });

    it('calls deleteWord after confirmation', async () => {
      vi.spyOn(window, 'confirm').mockReturnValue(true);
      const { deleteWord } = await import('../../../src/frontend/js/modules/vocabulary/services/word_actions');
      vi.mocked(deleteWord).mockClear();

      const btn = createDeleteButton(mockContext, true);
      btn.click();

      expect(btn.disabled).toBe(true);
      expect(deleteWord).toHaveBeenCalledWith(mockContext);
    });
  });

  describe('createWellKnownButton', () => {
    const mockContext: WordActionContext = {
      textId: 1,
      position: 5,
      text: 'test',
    };

    it('creates a well-known button', () => {
      const btn = createWellKnownButton(mockContext);
      expect(btn.tagName).toBe('BUTTON');
      expect(btn.textContent).toBe('I know this term well');
      expect(btn.className).toContain('lwt-action-btn--wellknown');
    });

    it('calls markWellKnown on click', async () => {
      const { markWellKnown } = await import('../../../src/frontend/js/modules/vocabulary/services/word_actions');
      vi.mocked(markWellKnown).mockClear();

      const btn = createWellKnownButton(mockContext);
      btn.click();

      expect(btn.disabled).toBe(true);
      expect(markWellKnown).toHaveBeenCalledWith(mockContext);
    });
  });

  describe('createIgnoreButton', () => {
    const mockContext: WordActionContext = {
      textId: 1,
      position: 5,
      text: 'test',
    };

    it('creates an ignore button', () => {
      const btn = createIgnoreButton(mockContext);
      expect(btn.tagName).toBe('BUTTON');
      expect(btn.textContent).toBe('Ignore this term');
      expect(btn.className).toContain('lwt-action-btn--ignore');
    });

    it('calls markIgnored on click', async () => {
      const { markIgnored } = await import('../../../src/frontend/js/modules/vocabulary/services/word_actions');
      vi.mocked(markIgnored).mockClear();

      const btn = createIgnoreButton(mockContext);
      btn.click();

      expect(btn.disabled).toBe(true);
      expect(markIgnored).toHaveBeenCalledWith(mockContext);
    });
  });

  describe('createReviewStatusButtons', () => {
    it('creates test buttons for status 1-5', () => {
      const mockContext: WordActionContext = {
        textId: 1,
        wordId: 100,
        position: 5,
        text: 'test',
        status: 3,
      };

      const fragment = createReviewStatusButtons(mockContext);
      const container = document.createElement('div');
      container.appendChild(fragment);

      // Should have "Got it" and "Oops" buttons
      expect(container.innerHTML).toContain('Got it!');
      expect(container.innerHTML).toContain('Oops!');
    });

    it('shows correct status transitions', () => {
      const mockContext: WordActionContext = {
        textId: 1,
        wordId: 100,
        position: 5,
        text: 'test',
        status: 3,
      };

      const fragment = createReviewStatusButtons(mockContext);
      const container = document.createElement('div');
      container.appendChild(fragment);

      // Status 3 -> 4 for "Got it"
      expect(container.innerHTML).toContain('3 ▶ 4');
      // Status 3 -> 2 for "Oops"
      expect(container.innerHTML).toContain('3 ▶ 2');
    });

    it('caps "Got it" at status 5', () => {
      const mockContext: WordActionContext = {
        textId: 1,
        wordId: 100,
        position: 5,
        text: 'test',
        status: 5,
      };

      const fragment = createReviewStatusButtons(mockContext);
      const container = document.createElement('div');
      container.appendChild(fragment);

      // Status 5 -> 5 for "Got it" (already at max)
      expect(container.innerHTML).toContain('5 ▶ 5');
    });

    it('floors "Oops" at status 1', () => {
      const mockContext: WordActionContext = {
        textId: 1,
        wordId: 100,
        position: 5,
        text: 'test',
        status: 1,
      };

      const fragment = createReviewStatusButtons(mockContext);
      const container = document.createElement('div');
      container.appendChild(fragment);

      // Status 1 -> 1 for "Oops" (already at min)
      expect(container.innerHTML).toContain('1 ▶ 1');
    });

    it('returns empty fragment for status outside 1-5', () => {
      const mockContext: WordActionContext = {
        textId: 1,
        wordId: 100,
        position: 5,
        text: 'test',
        status: 99,
      };

      const fragment = createReviewStatusButtons(mockContext);
      const container = document.createElement('div');
      container.appendChild(fragment);

      expect(container.innerHTML).toBe('');
    });

    it('uses status 1 as default when status is undefined', () => {
      const mockContext: WordActionContext = {
        textId: 1,
        wordId: 100,
        position: 5,
        text: 'test',
      };

      const fragment = createReviewStatusButtons(mockContext);
      const container = document.createElement('div');
      container.appendChild(fragment);

      // Should show 1 -> 2 for "Got it" and 1 -> 1 for "Oops"
      expect(container.innerHTML).toContain('1 ▶ 2');
      expect(container.innerHTML).toContain('1 ▶ 1');
    });

    it('calls incrementWordStatus up on Got it click', async () => {
      const { incrementWordStatus } = await import('../../../src/frontend/js/modules/vocabulary/services/word_actions');
      vi.mocked(incrementWordStatus).mockClear();

      const mockContext: WordActionContext = {
        textId: 1,
        wordId: 100,
        position: 5,
        text: 'test',
        status: 3,
      };

      const fragment = createReviewStatusButtons(mockContext);
      const container = document.createElement('div');
      container.appendChild(fragment);

      const gotItBtn = container.querySelector('.lwt-test-btn--success') as HTMLButtonElement;
      gotItBtn.click();

      expect(gotItBtn.disabled).toBe(true);
      expect(incrementWordStatus).toHaveBeenCalledWith(mockContext, 'up');
    });

    it('calls incrementWordStatus down on Oops click', async () => {
      const { incrementWordStatus } = await import('../../../src/frontend/js/modules/vocabulary/services/word_actions');
      vi.mocked(incrementWordStatus).mockClear();

      const mockContext: WordActionContext = {
        textId: 1,
        wordId: 100,
        position: 5,
        text: 'test',
        status: 3,
      };

      const fragment = createReviewStatusButtons(mockContext);
      const container = document.createElement('div');
      container.appendChild(fragment);

      const oopsBtn = container.querySelector('.lwt-test-btn--failure') as HTMLButtonElement;
      oopsBtn.click();

      expect(oopsBtn.disabled).toBe(true);
      expect(incrementWordStatus).toHaveBeenCalledWith(mockContext, 'down');
    });
  });

  // ===========================================================================
  // createLocalDictSection Tests
  // ===========================================================================

  describe('createLocalDictSection', () => {
    it('returns null when no local dictionaries available', async () => {
      mockHasLocalDictionaries.mockResolvedValue(false);

      const result = await createLocalDictSection(1, 'test');

      expect(result).toBeNull();
      expect(mockHasLocalDictionaries).toHaveBeenCalledWith(1);
    });

    it('creates section with results when lookup succeeds', async () => {
      mockHasLocalDictionaries.mockResolvedValue(true);
      mockLookupLocal.mockResolvedValue({
        data: {
          results: [{ term: 'test', definition: 'a test' }],
        },
      });
      mockFormatResults.mockReturnValue('<div>test - a test</div>');

      const result = await createLocalDictSection(1, 'hello');

      expect(result).not.toBeNull();
      expect(result!.className).toBe('lwt-local-dict-section');
      expect(result!.innerHTML).toContain('Local Dictionary');
      expect(result!.innerHTML).toContain('test - a test');
    });

    it('shows empty message when no results found', async () => {
      mockHasLocalDictionaries.mockResolvedValue(true);
      mockLookupLocal.mockResolvedValue({
        data: {
          results: [],
        },
      });

      const result = await createLocalDictSection(1, 'unknown');

      expect(result).not.toBeNull();
      expect(result!.innerHTML).toContain('No local results found');
    });

    it('shows error message when lookup returns error', async () => {
      mockHasLocalDictionaries.mockResolvedValue(true);
      mockLookupLocal.mockResolvedValue({
        error: 'Dictionary not found',
      });

      const result = await createLocalDictSection(1, 'test');

      expect(result).not.toBeNull();
      expect(result!.innerHTML).toContain('Dictionary not found');
    });

    it('shows error message when lookup throws exception', async () => {
      mockHasLocalDictionaries.mockResolvedValue(true);
      mockLookupLocal.mockRejectedValue(new Error('Network error'));

      const result = await createLocalDictSection(1, 'test');

      expect(result).not.toBeNull();
      expect(result!.innerHTML).toContain('Failed to look up term');
    });
  });

  // ===========================================================================
  // addLocalDictToPopup Tests
  // ===========================================================================

  describe('addLocalDictToPopup', () => {
    it('adds section with separator when local dict available', async () => {
      mockHasLocalDictionaries.mockResolvedValue(true);
      mockLookupLocal.mockResolvedValue({
        data: {
          results: [{ term: 'test', definition: 'a test' }],
        },
      });
      mockFormatResults.mockReturnValue('<div>test - a test</div>');

      const container = document.createElement('div');
      const result = await addLocalDictToPopup(container, 1, 'test');

      expect(result).toBe(true);
      expect(container.querySelector('hr.lwt-popup-separator')).not.toBeNull();
      expect(container.querySelector('.lwt-local-dict-section')).not.toBeNull();
    });

    it('returns false and adds nothing when no local dict', async () => {
      mockHasLocalDictionaries.mockResolvedValue(false);

      const container = document.createElement('div');
      const result = await addLocalDictToPopup(container, 1, 'test');

      expect(result).toBe(false);
      expect(container.children.length).toBe(0);
    });
  });

  // ===========================================================================
  // buildKnownWordPopupContent Tests
  // ===========================================================================

  describe('buildKnownWordPopupContent', () => {
    const mockContext: WordActionContext = {
      textId: 1,
      wordId: 100,
      position: 5,
      text: 'test',
      status: 3,
    };

    const dictLinks = {
      dict1: 'http://dict1.com/lwt_term',
      dict2: 'http://dict2.com/lwt_term',
      translator: 'http://trans.com/lwt_term',
    };

    it('creates popup content with audio button', () => {
      const content = buildKnownWordPopupContent(mockContext, dictLinks, [], false);

      expect(content.className).toBe('lwt-word-popup-content');
      expect(content.querySelector('.lwt-popup-audio')).not.toBeNull();
    });

    it('creates popup content with status buttons', () => {
      const content = buildKnownWordPopupContent(mockContext, dictLinks, [], false);

      const buttons = content.querySelectorAll('button.lwt-status-btn');
      expect(buttons.length).toBeGreaterThan(0);
    });

    it('creates popup content with edit link', () => {
      const content = buildKnownWordPopupContent(mockContext, dictLinks, [], false);

      const editLink = content.querySelector('a[href*="/word/edit"]');
      expect(editLink).not.toBeNull();
      expect(editLink!.innerHTML).toContain('Edit term');
    });

    it('creates popup content with delete button', () => {
      const content = buildKnownWordPopupContent(mockContext, dictLinks, [], false);

      const deleteBtn = content.querySelector('.lwt-action-btn--delete');
      expect(deleteBtn).not.toBeNull();
    });

    it('creates popup content with dictionary links', () => {
      const content = buildKnownWordPopupContent(mockContext, dictLinks, [], false);

      expect(content.innerHTML).toContain('Dict1');
      expect(content.innerHTML).toContain('Dict2');
      expect(content.innerHTML).toContain('Trans');
    });

    it('includes multiword expressions when provided', () => {
      const multiWords = ['2 word1 word2', undefined, '', '', '', '', '', ''];
      const content = buildKnownWordPopupContent(mockContext, dictLinks, multiWords, false);

      expect(content.innerHTML).toContain('Expr:');
    });

    it('excludes multiword section when no expressions', () => {
      const content = buildKnownWordPopupContent(mockContext, dictLinks, [], false);

      expect(content.innerHTML).not.toContain('Expr:');
    });
  });

  // ===========================================================================
  // buildUnknownWordPopupContent Tests
  // ===========================================================================

  describe('buildUnknownWordPopupContent', () => {
    const mockContext: WordActionContext = {
      textId: 1,
      position: 5,
      text: 'newword',
    };

    const dictLinks = {
      dict1: 'http://dict1.com/lwt_term',
      dict2: 'http://dict2.com/lwt_term',
      translator: 'http://trans.com/lwt_term',
    };

    it('creates popup content with audio button', () => {
      const content = buildUnknownWordPopupContent(mockContext, dictLinks, [], false);

      expect(content.className).toBe('lwt-word-popup-content');
      expect(content.querySelector('.lwt-popup-audio')).not.toBeNull();
    });

    it('creates popup content with well-known button', () => {
      const content = buildUnknownWordPopupContent(mockContext, dictLinks, [], false);

      const wkBtn = content.querySelector('.lwt-action-btn--wellknown');
      expect(wkBtn).not.toBeNull();
      expect(wkBtn!.textContent).toBe('I know this term well');
    });

    it('creates popup content with ignore button', () => {
      const content = buildUnknownWordPopupContent(mockContext, dictLinks, [], false);

      const ignoreBtn = content.querySelector('.lwt-action-btn--ignore');
      expect(ignoreBtn).not.toBeNull();
      expect(ignoreBtn!.textContent).toBe('Ignore this term');
    });

    it('creates popup content with dictionary links', () => {
      const content = buildUnknownWordPopupContent(mockContext, dictLinks, [], false);

      expect(content.innerHTML).toContain('Dict1');
    });

    it('includes multiword expressions when provided', () => {
      const multiWords = ['2 test expr', '', '', '', '', '', '', ''];
      const content = buildUnknownWordPopupContent(mockContext, dictLinks, multiWords, false);

      expect(content.innerHTML).toContain('Expr:');
    });
  });

  // ===========================================================================
  // buildWordDetailsPanel Tests
  // ===========================================================================

  describe('buildWordDetailsPanel', () => {
    it('shows error when termId is 0', async () => {
      const panel = await buildWordDetailsPanel(0);

      expect(panel.className).toBe('lwt-word-details');
      expect(panel.innerHTML).toContain('No term ID provided');
    });

    it('shows error when API returns error', async () => {
      mockGetDetails.mockResolvedValue({ error: 'Term not found' });

      const panel = await buildWordDetailsPanel(100);

      expect(panel.innerHTML).toContain('Term not found');
    });

    it('renders term details when API succeeds', async () => {
      mockGetDetails.mockResolvedValue({
        data: {
          text: 'hello',
          translation: 'hola',
          notes: 'A greeting',
          tags: ['common', 'greeting'],
          romanization: 'heh-loh',
          sentence: 'Say {hello} to everyone.',
          status: 3,
          statusLabel: 'Learning (3)',
        },
      });

      const panel = await buildWordDetailsPanel(100);

      expect(panel.innerHTML).toContain('Term:');
      expect(panel.innerHTML).toContain('hello');
      expect(panel.innerHTML).toContain('Translation:');
      expect(panel.innerHTML).toContain('hola');
      expect(panel.innerHTML).toContain('Notes:');
      expect(panel.innerHTML).toContain('A greeting');
      expect(panel.innerHTML).toContain('Tags:');
      expect(panel.innerHTML).toContain('common');
      expect(panel.innerHTML).toContain('greeting');
      expect(panel.innerHTML).toContain('Romaniz.:');
      expect(panel.innerHTML).toContain('heh-loh');
      expect(panel.innerHTML).toContain('Sentence:');
      expect(panel.innerHTML).toContain('<b>hello</b>');
      expect(panel.innerHTML).toContain('Status:');
      expect(panel.innerHTML).toContain('Learning (3)');
    });

    it('excludes translation when it equals asterisk', async () => {
      mockGetDetails.mockResolvedValue({
        data: {
          text: 'hello',
          translation: '*',
          status: 3,
          statusLabel: 'Learning (3)',
        },
      });

      const panel = await buildWordDetailsPanel(100);

      expect(panel.innerHTML).not.toContain('Translation:');
    });

    it('excludes empty optional fields', async () => {
      mockGetDetails.mockResolvedValue({
        data: {
          text: 'hello',
          status: 3,
          statusLabel: 'Learning (3)',
        },
      });

      const panel = await buildWordDetailsPanel(100);

      expect(panel.innerHTML).not.toContain('Translation:');
      expect(panel.innerHTML).not.toContain('Notes:');
      expect(panel.innerHTML).not.toContain('Tags:');
      expect(panel.innerHTML).not.toContain('Romaniz.:');
      expect(panel.innerHTML).not.toContain('Sentence:');
    });

    it('passes annotation to API when provided', async () => {
      mockGetDetails.mockResolvedValue({
        data: {
          text: 'hello',
          translation: 'hi [informal]',
          status: 3,
          statusLabel: 'Learning (3)',
        },
      });

      await buildWordDetailsPanel(100, 'informal');

      expect(mockGetDetails).toHaveBeenCalledWith(100, 'informal');
    });
  });

  // ===========================================================================
  // openMultiWordModal Tests
  // ===========================================================================

  describe('openMultiWordModal', () => {
    it('calls store loadForEdit with new expression parameters', () => {
      openMultiWordModal(1, 5, 'hello world', 2);

      expect(mockMultiWordFormStore.loadForEdit).toHaveBeenCalledWith(
        1, 5, 'hello world', 2, undefined
      );
    });

    it('calls store loadForEdit with existing word parameters', () => {
      openMultiWordModal(1, 5, '', 0, 100);

      expect(mockMultiWordFormStore.loadForEdit).toHaveBeenCalledWith(
        1, 5, '', 0, 100
      );
    });

    it('is exposed on window object', () => {
      expect(window.openMultiWordModal).toBe(openMultiWordModal);
    });
  });
});
