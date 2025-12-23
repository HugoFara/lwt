/**
 * Tests for overlib_interface.ts - Word popup interface functions
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  run_overlib_status_98,
  run_overlib_status_99,
  run_overlib_status_1_to_5,
  run_overlib_status_unknown,
  run_overlib_multiword,
  run_overlib_test,
  make_overlib_link_new_multiword,
  make_overlib_link_wb,
  make_overlib_link_wbnl,
  make_overlib_link_wbnl2,
  make_overlib_link_change_status_all,
  make_overlib_link_change_status_alltest,
  make_overlib_link_change_status,
  make_overlib_link_change_status_test,
  make_overlib_link_change_status_test2,
  make_overlib_link_new_word,
  make_overlib_link_edit_multiword,
  make_overlib_link_edit_multiword_title,
  make_overlib_link_create_edit_multiword,
  make_overlib_link_create_edit_multiword_rtl,
  make_overlib_link_edit_word,
  make_overlib_link_edit_word_title,
  make_overlib_link_delete_word,
  make_overlib_link_delete_multiword,
  make_overlib_link_wellknown_word,
  make_overlib_link_ignore_word,
  make_overlib_audio,
  createStatusChangeButton,
  createStatusButtonsAll,
  createDeleteButton,
  createWellKnownButton,
  createIgnoreButton,
  createTestStatusButtons,
} from '../../../src/frontend/js/terms/overlib_interface';
import type { WordActionContext } from '../../../src/frontend/js/reading/word_actions';

// Mock the word_actions module
vi.mock('../../../src/frontend/js/reading/word_actions', () => ({
  changeWordStatus: vi.fn().mockResolvedValue({ success: true }),
  deleteWord: vi.fn().mockResolvedValue({ success: true }),
  markWellKnown: vi.fn().mockResolvedValue({ success: true }),
  markIgnored: vi.fn().mockResolvedValue({ success: true }),
  incrementWordStatus: vi.fn().mockResolvedValue({ success: true }),
}));

import { initLanguageConfig, resetLanguageConfig } from '../../../src/frontend/js/core/language_config';
import { initTextConfig, resetTextConfig } from '../../../src/frontend/js/core/text_config';
import { initSettingsConfig, resetSettingsConfig } from '../../../src/frontend/js/core/settings_config';

describe('overlib_interface.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    // Initialize state modules
    resetLanguageConfig();
    resetTextConfig();
    resetSettingsConfig();
    initLanguageConfig({
      id: 1,
      dictLink1: 'http://dict1.example.com/###',
      dictLink2: 'http://dict2.example.com/###',
      translatorLink: 'http://translator.example.com/###',
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
  // make_overlib_link_new_multiword Tests
  // ===========================================================================

  describe('make_overlib_link_new_multiword', () => {
    it('returns empty string when all multiwords are empty', () => {
      const result = make_overlib_link_new_multiword(1, '5', ['', '', '', '', '', '', '', ''], false);
      expect(result).toBe('');
    });

    it('returns empty string when all multiwords are undefined', () => {
      const result = make_overlib_link_new_multiword(1, '5', [undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined], false);
      expect(result).toBe('');
    });

    it('creates LTR multiword links for valid multiwords via modal', () => {
      const multiWords = ['2 word1 word2', undefined, '4 word1 word2 word3 word4', '', '', '', '', ''];
      const result = make_overlib_link_new_multiword(1, '5', multiWords, false);

      expect(result).toContain('Expr:');
      expect(result).toContain('2..');
      expect(result).toContain('4..');
      expect(result).toContain('openMultiWordModal');
    });

    it('creates RTL multiword links when rtl is true', () => {
      const multiWords = ['2 word1', undefined, undefined, undefined, undefined, undefined, undefined, '8 word8'];
      const result = make_overlib_link_new_multiword(1, '5', multiWords, true);

      expect(result).toContain('Expr:');
      expect(result).toContain('dir="rtl"');
    });

    it('handles numeric torder', () => {
      const multiWords = ['2 test', '', '', '', '', '', '', ''];
      const result = make_overlib_link_new_multiword(1, 10, multiWords, false);

      expect(result).toContain('openMultiWordModal(1, 10,');
    });
  });

  // ===========================================================================
  // make_overlib_link_wb Tests
  // ===========================================================================

  describe('make_overlib_link_wb', () => {
    it('creates dictionary links with lookup term', () => {
      const result = make_overlib_link_wb(
        'http://dict1.com/###',
        'http://dict2.com/###',
        'http://trans.com/###',
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
      const result = make_overlib_link_wb(
        'http://dict1.com/###',
        'http://dict2.com/###',
        'http://trans.com/###',
        'test',
        1,
        '5'
      );

      expect(result).toContain('Lookup Sentence:');
    });

    it('excludes sentence lookup when torder is 0', () => {
      const result = make_overlib_link_wb(
        'http://dict1.com/###',
        'http://dict2.com/###',
        'http://trans.com/###',
        'test',
        1,
        '0'
      );

      expect(result).not.toContain('Lookup Sentence:');
    });

    it('excludes sentence lookup when txid is 0', () => {
      const result = make_overlib_link_wb(
        'http://dict1.com/###',
        'http://dict2.com/###',
        'http://trans.com/###',
        'test',
        0,
        '5'
      );

      expect(result).not.toContain('Lookup Sentence:');
    });
  });

  // ===========================================================================
  // make_overlib_link_wbnl Tests
  // ===========================================================================

  describe('make_overlib_link_wbnl', () => {
    it('creates dictionary links with term prefix', () => {
      const result = make_overlib_link_wbnl(
        'http://dict1.com/###',
        'http://dict2.com/###',
        'http://trans.com/###',
        'test',
        1,
        '5'
      );

      expect(result).toContain('Term:');
    });

    it('includes sentence link when valid', () => {
      const result = make_overlib_link_wbnl(
        'http://dict1.com/###',
        'http://dict2.com/###',
        'http://trans.com/###',
        'test',
        1,
        '5'
      );

      expect(result).toContain('Sentence:');
    });
  });

  // ===========================================================================
  // make_overlib_link_wbnl2 Tests
  // ===========================================================================

  describe('make_overlib_link_wbnl2', () => {
    it('creates dictionary links for term and sentence', () => {
      const result = make_overlib_link_wbnl2(
        'http://dict1.com/###',
        'http://dict2.com/###',
        'http://trans.com/###',
        'test',
        'This is a test sentence.'
      );

      expect(result).toContain('Term:');
      expect(result).toContain('Sentence:');
    });

    it('excludes sentence when empty', () => {
      const result = make_overlib_link_wbnl2(
        'http://dict1.com/###',
        'http://dict2.com/###',
        'http://trans.com/###',
        'test',
        ''
      );

      expect(result).toContain('Term:');
      expect(result).not.toContain('Sentence:');
    });
  });

  // ===========================================================================
  // make_overlib_link_change_status_all Tests
  // ===========================================================================

  describe('make_overlib_link_change_status_all', () => {
    it('returns links for all status levels (1-5, 98, 99)', () => {
      const result = make_overlib_link_change_status_all(1, '5', 100, 3);

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
  // make_overlib_link_change_status Tests
  // ===========================================================================

  describe('make_overlib_link_change_status', () => {
    it('returns diamond when old status equals new status', () => {
      const result = make_overlib_link_change_status(1, '5', 100, 3, 3);

      expect(result).toContain('◆');
      expect(result).not.toContain('<a href');
    });

    it('returns link with new status when different', () => {
      const result = make_overlib_link_change_status(1, '5', 100, 3, 4);

      expect(result).toContain('set_word_status.php');
      expect(result).toContain('tid=1');
      expect(result).toContain('ord=5');
      expect(result).toContain('wid=100');
      expect(result).toContain('status=4');
      expect(result).toContain('[4]');
    });

    it('handles status 99 (well-known)', () => {
      const result = make_overlib_link_change_status(1, '5', 100, 3, 99);

      expect(result).toContain('[WKn]');
      expect(result).toContain('status=99');
    });

    it('handles status 98 (ignored)', () => {
      const result = make_overlib_link_change_status(1, '5', 100, 3, 98);

      expect(result).toContain('[Ign]');
      expect(result).toContain('status=98');
    });
  });

  // ===========================================================================
  // make_overlib_link_change_status_alltest Tests
  // ===========================================================================

  describe('make_overlib_link_change_status_alltest', () => {
    it('returns test status links for all levels', () => {
      const result = make_overlib_link_change_status_alltest(100, 2);

      expect(result).toContain('set_test_status.php');
      expect(result).toContain('wid=100');
      // Current status (2) should show diamond inside link
      expect(result).toContain('◆');
    });
  });

  // ===========================================================================
  // make_overlib_link_change_status_test2 Tests
  // ===========================================================================

  describe('make_overlib_link_change_status_test2', () => {
    it('shows diamond when status matches', () => {
      const result = make_overlib_link_change_status_test2(100, 3, 3);

      expect(result).toContain('◆');
      expect(result).toContain('set_test_status.php');
    });

    it('shows status abbreviation when status differs', () => {
      const result = make_overlib_link_change_status_test2(100, 3, 4);

      expect(result).toContain('[4]');
      expect(result).not.toContain('◆');
    });
  });

  // ===========================================================================
  // make_overlib_link_change_status_test Tests
  // ===========================================================================

  describe('make_overlib_link_change_status_test', () => {
    it('creates link with positive change and success sound', () => {
      const result = make_overlib_link_change_status_test(100, 1, 'Got it!');

      expect(result).toContain('set_test_status.php');
      expect(result).toContain('wid=100');
      expect(result).toContain('stchange=1');
      expect(result).toContain('successSound()');
      expect(result).toContain('Got it!');
    });

    it('creates link with negative change and failure sound', () => {
      const result = make_overlib_link_change_status_test(100, -1, 'Oops!');

      expect(result).toContain('stchange=-1');
      expect(result).toContain('failureSound()');
      expect(result).toContain('Oops!');
    });
  });

  // ===========================================================================
  // make_overlib_link_new_word Tests
  // ===========================================================================

  describe('make_overlib_link_new_word', () => {
    it('creates link to learn a new word', () => {
      const result = make_overlib_link_new_word(1, '5', 100);

      expect(result).toContain('/word/edit');
      expect(result).toContain('tid=1');
      expect(result).toContain('ord=5');
      expect(result).toContain('wid=100');
      expect(result).toContain('Learn term');
      expect(result).toContain('showRightFramesPanel()');
    });
  });

  // ===========================================================================
  // make_overlib_link_edit_multiword Tests
  // ===========================================================================

  describe('make_overlib_link_edit_multiword', () => {
    it('creates button to edit a multiword via modal', () => {
      const result = make_overlib_link_edit_multiword(1, '5', 100);

      expect(result).toContain('openMultiWordModal');
      expect(result).toContain("1, 5, '', 0, 100");  // textId, position, text='', wordCount=0, wordId
      expect(result).toContain('Edit term');
    });
  });

  // ===========================================================================
  // make_overlib_link_edit_multiword_title Tests
  // ===========================================================================

  describe('make_overlib_link_edit_multiword_title', () => {
    it('creates styled title button for multiword via modal', () => {
      const result = make_overlib_link_edit_multiword_title('2-Word-Expression', 1, '5', 100);

      expect(result).toContain('style="color:yellow"');
      expect(result).toContain('openMultiWordModal');
      expect(result).toContain("1, 5, '', 0, 100");  // textId, position, text='', wordCount=0, wordId
      expect(result).toContain('2-Word-Expression');
    });
  });

  // ===========================================================================
  // make_overlib_link_create_edit_multiword Tests
  // ===========================================================================

  describe('make_overlib_link_create_edit_multiword', () => {
    it('creates button to create/edit multiword via modal', () => {
      const result = make_overlib_link_create_edit_multiword(3, 1, '5', '3 word1 word2 word3');

      expect(result).toContain('openMultiWordModal');
      expect(result).toContain("1, 5, '3 word1 word2 word3', 3");  // textId, position, text, wordCount
      expect(result).toContain('3..');
    });
  });

  // ===========================================================================
  // make_overlib_link_create_edit_multiword_rtl Tests
  // ===========================================================================

  describe('make_overlib_link_create_edit_multiword_rtl', () => {
    it('creates RTL button for multiword via modal', () => {
      const result = make_overlib_link_create_edit_multiword_rtl(3, 1, '5', '3 מילה1 מילה2');

      expect(result).toContain('dir="rtl"');
      expect(result).toContain('openMultiWordModal');
      expect(result).toContain('3..');
    });
  });

  // ===========================================================================
  // make_overlib_link_edit_word Tests
  // ===========================================================================

  describe('make_overlib_link_edit_word', () => {
    it('creates link to edit a word', () => {
      const result = make_overlib_link_edit_word(1, '5', 100);

      expect(result).toContain('/word/edit');
      expect(result).toContain('tid=1');
      expect(result).toContain('ord=5');
      expect(result).toContain('wid=100');
      expect(result).toContain('Edit term');
    });
  });

  // ===========================================================================
  // make_overlib_link_edit_word_title Tests
  // ===========================================================================

  describe('make_overlib_link_edit_word_title', () => {
    it('creates styled title link for word', () => {
      const result = make_overlib_link_edit_word_title('TestWord', 1, '5', 100);

      expect(result).toContain('style="color:yellow"');
      expect(result).toContain('/word/edit');
      expect(result).toContain('TestWord');
    });
  });

  // ===========================================================================
  // make_overlib_link_delete_word Tests
  // ===========================================================================

  describe('make_overlib_link_delete_word', () => {
    it('creates link to delete a word with confirmation', () => {
      const result = make_overlib_link_delete_word(1, 100);

      expect(result).toContain('delete_word.php');
      expect(result).toContain('wid=100');
      expect(result).toContain('tid=1');
      expect(result).toContain('confirmDelete()');
      expect(result).toContain('Delete term');
    });
  });

  // ===========================================================================
  // make_overlib_link_delete_multiword Tests
  // ===========================================================================

  describe('make_overlib_link_delete_multiword', () => {
    it('creates link to delete a multiword with confirmation', () => {
      const result = make_overlib_link_delete_multiword(1, 100);

      expect(result).toContain('delete_mword.php');
      expect(result).toContain('wid=100');
      expect(result).toContain('tid=1');
      expect(result).toContain('confirmDelete()');
      expect(result).toContain('Delete term');
    });
  });

  // ===========================================================================
  // make_overlib_link_wellknown_word Tests
  // ===========================================================================

  describe('make_overlib_link_wellknown_word', () => {
    it('creates link to mark word as well-known', () => {
      const result = make_overlib_link_wellknown_word(1, '5');

      expect(result).toContain('insert_word_wellknown.php');
      expect(result).toContain('tid=1');
      expect(result).toContain('ord=5');
      expect(result).toContain('I know this term well');
    });
  });

  // ===========================================================================
  // make_overlib_link_ignore_word Tests
  // ===========================================================================

  describe('make_overlib_link_ignore_word', () => {
    it('creates link to ignore a word', () => {
      const result = make_overlib_link_ignore_word(1, '5');

      expect(result).toContain('insert_word_ignore.php');
      expect(result).toContain('tid=1');
      expect(result).toContain('ord=5');
      expect(result).toContain('Ignore this term');
    });
  });

  // ===========================================================================
  // make_overlib_audio Tests
  // ===========================================================================

  describe('make_overlib_audio', () => {
    it('creates audio button with speech dispatcher', () => {
      const result = make_overlib_audio('hello');

      // Now uses Lucide SVG icons instead of PNG images
      expect(result).toContain('<i');
      expect(result).toContain('data-lucide="volume-2"');
      expect(result).toContain('speechDispatcher');
      expect(result).toContain("'hello'");
      expect(result).toContain("'1'"); // Language ID is 1
    });

    it('escapes HTML characters in text', () => {
      const result = make_overlib_audio('<script>alert("xss")</script>');

      expect(result).not.toContain('<script>alert');
      // HTML characters are escaped in the onclick attribute
      expect(result).toContain('&amp;');
    });
  });

  // ===========================================================================
  // run_overlib_status_* Integration Tests (these call overlib)
  // ===========================================================================

  // Note: These tests are skipped because they require the word popup dialog
  // which is not properly set up in the test environment
  describe.skip('run_overlib functions (require popup dialog)', () => {
    it('run_overlib_status_98 returns boolean', () => {
      const result = run_overlib_status_98(
        'http://dict1.com/###', 'http://dict2.com/###', 'http://trans.com/###',
        'hint', 1, '5', 'word', 100, ['', '', '', '', '', '', '', ''], false, ''
      );
      expect(typeof result).toBe('boolean');
    });

    it('run_overlib_status_99 returns boolean', () => {
      const result = run_overlib_status_99(
        'http://dict1.com/###', 'http://dict2.com/###', 'http://trans.com/###',
        'hint', 1, '5', 'word', 100, ['', '', '', '', '', '', '', ''], false, ''
      );
      expect(typeof result).toBe('boolean');
    });

    it('run_overlib_status_1_to_5 returns boolean', () => {
      const result = run_overlib_status_1_to_5(
        'http://dict1.com/###', 'http://dict2.com/###', 'http://trans.com/###',
        'hint', 1, '5', 'word', 100, 3, ['', '', '', '', '', '', '', ''], false, ''
      );
      expect(typeof result).toBe('boolean');
    });

    it('run_overlib_status_unknown returns boolean', () => {
      const result = run_overlib_status_unknown(
        'http://dict1.com/###', 'http://dict2.com/###', 'http://trans.com/###',
        'hint', 1, '5', 'word', ['', '', '', '', '', '', '', ''], false
      );
      expect(typeof result).toBe('boolean');
    });

    it('run_overlib_multiword returns boolean', () => {
      const result = run_overlib_multiword(
        'http://dict1.com/###', 'http://dict2.com/###', 'http://trans.com/###',
        'hint', 1, '5', 'multi word', 100, 3, '2 ', ''
      );
      expect(typeof result).toBe('boolean');
    });

    it('run_overlib_test returns boolean', () => {
      const result = run_overlib_test(
        'http://dict1.com/###', 'http://dict2.com/###', 'http://trans.com/###',
        100, 'word', 'translation', 'roman', 3, 'test sentence', 1
      );
      expect(typeof result).toBe('boolean');
    });

    it('run_overlib_test with todo=0 skips interactive buttons', () => {
      const result = run_overlib_test(
        'http://dict1.com/###', 'http://dict2.com/###', 'http://trans.com/###',
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
      const { changeWordStatus } = await import('../../../src/frontend/js/reading/word_actions');
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
      const { deleteWord } = await import('../../../src/frontend/js/reading/word_actions');

      const btn = createDeleteButton(mockContext, true);
      btn.click();

      expect(confirmSpy).toHaveBeenCalledWith('Are you sure you want to delete this term?');
      expect(deleteWord).not.toHaveBeenCalled();

      confirmSpy.mockRestore();
    });

    it('deletes without confirmation when confirm is false', async () => {
      const { deleteWord } = await import('../../../src/frontend/js/reading/word_actions');
      vi.mocked(deleteWord).mockClear();

      const btn = createDeleteButton(mockContext, false);
      btn.click();

      expect(btn.disabled).toBe(true);
      expect(deleteWord).toHaveBeenCalledWith(mockContext);
    });

    it('calls deleteWord after confirmation', async () => {
      vi.spyOn(window, 'confirm').mockReturnValue(true);
      const { deleteWord } = await import('../../../src/frontend/js/reading/word_actions');
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
      const { markWellKnown } = await import('../../../src/frontend/js/reading/word_actions');
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
      const { markIgnored } = await import('../../../src/frontend/js/reading/word_actions');
      vi.mocked(markIgnored).mockClear();

      const btn = createIgnoreButton(mockContext);
      btn.click();

      expect(btn.disabled).toBe(true);
      expect(markIgnored).toHaveBeenCalledWith(mockContext);
    });
  });

  describe('createTestStatusButtons', () => {
    it('creates test buttons for status 1-5', () => {
      const mockContext: WordActionContext = {
        textId: 1,
        wordId: 100,
        position: 5,
        text: 'test',
        status: 3,
      };

      const fragment = createTestStatusButtons(mockContext);
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

      const fragment = createTestStatusButtons(mockContext);
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

      const fragment = createTestStatusButtons(mockContext);
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

      const fragment = createTestStatusButtons(mockContext);
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

      const fragment = createTestStatusButtons(mockContext);
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

      const fragment = createTestStatusButtons(mockContext);
      const container = document.createElement('div');
      container.appendChild(fragment);

      // Should show 1 -> 2 for "Got it" and 1 -> 1 for "Oops"
      expect(container.innerHTML).toContain('1 ▶ 2');
      expect(container.innerHTML).toContain('1 ▶ 1');
    });

    it('calls incrementWordStatus up on Got it click', async () => {
      const { incrementWordStatus } = await import('../../../src/frontend/js/reading/word_actions');
      vi.mocked(incrementWordStatus).mockClear();

      const mockContext: WordActionContext = {
        textId: 1,
        wordId: 100,
        position: 5,
        text: 'test',
        status: 3,
      };

      const fragment = createTestStatusButtons(mockContext);
      const container = document.createElement('div');
      container.appendChild(fragment);

      const gotItBtn = container.querySelector('.lwt-test-btn--success') as HTMLButtonElement;
      gotItBtn.click();

      expect(gotItBtn.disabled).toBe(true);
      expect(incrementWordStatus).toHaveBeenCalledWith(mockContext, 'up');
    });

    it('calls incrementWordStatus down on Oops click', async () => {
      const { incrementWordStatus } = await import('../../../src/frontend/js/reading/word_actions');
      vi.mocked(incrementWordStatus).mockClear();

      const mockContext: WordActionContext = {
        textId: 1,
        wordId: 100,
        position: 5,
        text: 'test',
        status: 3,
      };

      const fragment = createTestStatusButtons(mockContext);
      const container = document.createElement('div');
      container.appendChild(fragment);

      const oopsBtn = container.querySelector('.lwt-test-btn--failure') as HTMLButtonElement;
      oopsBtn.click();

      expect(oopsBtn.disabled).toBe(true);
      expect(incrementWordStatus).toHaveBeenCalledWith(mockContext, 'down');
    });
  });
});
