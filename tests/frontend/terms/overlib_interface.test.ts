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
} from '../../../src/frontend/js/terms/overlib_interface';

// Mock LWT_DATA global
const mockLWT_DATA = {
  language: {
    id: 1,
    dict_link1: 'http://dict1.example.com/###',
    dict_link2: 'http://dict2.example.com/###',
    translator_link: 'http://translator.example.com/###',
    delimiter: ',',
    word_parsing: '',
    rtl: false,
    ttsVoiceApi: ''
  },
  text: {
    id: 1,
    reading_position: 0,
    annotations: {}
  },
  word: { id: 0 },
  test: { solution: '', answer_opened: false },
  settings: { jQuery_tooltip: false, hts: 0, word_status_filter: '' }
};

// Mock STATUSES for getStatusName and getStatusAbbr
const mockSTATUSES: Record<string, { name: string; abbr: string; score: number; color: string }> = {
  '1': { name: 'Learning (1)', abbr: '1', score: 1, color: '#f5b8a9' },
  '2': { name: 'Learning (2)', abbr: '2', score: 2, color: '#f5cca9' },
  '3': { name: 'Learning (3)', abbr: '3', score: 3, color: '#f5e1a9' },
  '4': { name: 'Learning (4)', abbr: '4', score: 4, color: '#f5f3a9' },
  '5': { name: 'Learned', abbr: '5', score: 5, color: '#a9f5bc' },
  '98': { name: 'Ignored', abbr: 'Ign', score: 98, color: '#e0e0e0' },
  '99': { name: 'Well Known', abbr: 'WKn', score: 99, color: '#a9f5f1' },
};

// Set up globals
(globalThis as unknown as Record<string, unknown>).LWT_DATA = mockLWT_DATA;
(globalThis as unknown as Record<string, unknown>).STATUSES = mockSTATUSES;

describe('overlib_interface.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    (globalThis as unknown as Record<string, unknown>).LWT_DATA = JSON.parse(JSON.stringify(mockLWT_DATA));
    (globalThis as unknown as Record<string, unknown>).STATUSES = mockSTATUSES;
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

    it('creates LTR multiword links for valid multiwords', () => {
      const multiWords = ['2 word1 word2', undefined, '4 word1 word2 word3 word4', '', '', '', '', ''];
      const result = make_overlib_link_new_multiword(1, '5', multiWords, false);

      expect(result).toContain('Expr:');
      expect(result).toContain('2..');
      expect(result).toContain('4..');
      expect(result).toContain('href="edit_mword.php?tid=1');
      expect(result).toContain('&amp;ord=5');
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

      expect(result).toContain('&amp;ord=10');
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
      expect(result).toContain('showRightFrames()');
    });
  });

  // ===========================================================================
  // make_overlib_link_edit_multiword Tests
  // ===========================================================================

  describe('make_overlib_link_edit_multiword', () => {
    it('creates link to edit a multiword', () => {
      const result = make_overlib_link_edit_multiword(1, '5', 100);

      expect(result).toContain('edit_mword.php');
      expect(result).toContain('tid=1');
      expect(result).toContain('ord=5');
      expect(result).toContain('wid=100');
      expect(result).toContain('Edit term');
    });
  });

  // ===========================================================================
  // make_overlib_link_edit_multiword_title Tests
  // ===========================================================================

  describe('make_overlib_link_edit_multiword_title', () => {
    it('creates styled title link for multiword', () => {
      const result = make_overlib_link_edit_multiword_title('2-Word-Expression', 1, '5', 100);

      expect(result).toContain('style="color:yellow"');
      expect(result).toContain('edit_mword.php');
      expect(result).toContain('2-Word-Expression');
    });
  });

  // ===========================================================================
  // make_overlib_link_create_edit_multiword Tests
  // ===========================================================================

  describe('make_overlib_link_create_edit_multiword', () => {
    it('creates link to create/edit multiword with length and text', () => {
      const result = make_overlib_link_create_edit_multiword(3, 1, '5', '3 word1 word2 word3');

      expect(result).toContain('edit_mword.php');
      expect(result).toContain('tid=1');
      expect(result).toContain('ord=5');
      expect(result).toContain('txt=3 word1 word2 word3');
      expect(result).toContain('3..');
    });
  });

  // ===========================================================================
  // make_overlib_link_create_edit_multiword_rtl Tests
  // ===========================================================================

  describe('make_overlib_link_create_edit_multiword_rtl', () => {
    it('creates RTL link for multiword', () => {
      const result = make_overlib_link_create_edit_multiword_rtl(3, 1, '5', '3 מילה1 מילה2');

      expect(result).toContain('dir="rtl"');
      expect(result).toContain('edit_mword.php');
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

      expect(result).toContain('<img');
      expect(result).toContain('speaker-volume.png');
      expect(result).toContain('speechDispatcher');
      expect(result).toContain("'hello'");
      expect(result).toContain("'" + mockLWT_DATA.language.id + "'");
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

  // Note: These tests are skipped because they require jQuery UI dialog
  // which is not available in the test environment
  describe.skip('run_overlib functions (require jQuery UI)', () => {
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
        100, 'word', 'translation', 'roman', 3, 'test sentence', 1, 2
      );
      expect(typeof result).toBe('boolean');
    });

    it('run_overlib_test with todo=0 skips interactive buttons', () => {
      const result = run_overlib_test(
        'http://dict1.com/###', 'http://dict2.com/###', 'http://trans.com/###',
        100, 'word', 'translation', 'roman', 3, 'test sentence', 0, 2
      );
      expect(typeof result).toBe('boolean');
    });
  });
});
