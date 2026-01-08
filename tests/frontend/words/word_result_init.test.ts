/**
 * Tests for word_result_init.ts - Auto-initializes word result views
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { autoInitWordResults } from '../../../src/frontend/js/modules/vocabulary/pages/word_result_init';

// Mock dependencies
vi.mock('../../../src/frontend/js/modules/vocabulary/services/word_dom_updates', () => ({
  updateNewWordInDOM: vi.fn(),
  updateExistingWordInDOM: vi.fn(),
  completeWordOperation: vi.fn(),
  getParentContext: vi.fn(() => document),
  updateLearnStatus: vi.fn(),
  updateTestWordInDOM: vi.fn(),
  deleteWordFromDOM: vi.fn(),
  markWordWellKnownInDOM: vi.fn(),
  markWordIgnoredInDOM: vi.fn(),
  updateMultiWordInDOM: vi.fn(),
  deleteMultiWordFromDOM: vi.fn(),
  updateBulkWordInDOM: vi.fn()
}));

vi.mock('../../../src/frontend/js/modules/vocabulary/services/word_status', () => ({
  createWordTooltip: vi.fn(() => 'tooltip text')
}));

vi.mock('../../../src/frontend/js/modules/text/pages/reading/frame_management', () => ({
  cleanupRightFrames: vi.fn()
}));

vi.mock('../../../src/frontend/js/modules/vocabulary/services/term_operations', () => ({
  loadTermTranslations: vi.fn()
}));

vi.mock('../../../src/frontend/js/shared/utils/html_utils', () => ({
  escapeHtml: vi.fn((s) => s)
}));

import {
  updateNewWordInDOM,
  completeWordOperation,
  deleteWordFromDOM,
  markWordWellKnownInDOM,
  markWordIgnoredInDOM,
  updateMultiWordInDOM,
  deleteMultiWordFromDOM,
  updateBulkWordInDOM,
  updateLearnStatus,
  updateExistingWordInDOM
} from '../../../src/frontend/js/modules/vocabulary/services/word_dom_updates';
import { cleanupRightFrames } from '../../../src/frontend/js/modules/text/pages/reading/frame_management';

describe('word_result_init.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // Cleanup Frames Tests
  // ===========================================================================

  describe('cleanup frames', () => {
    it('calls cleanupRightFrames when data attribute is present', () => {
      document.body.innerHTML = `
        <div data-lwt-cleanup-frames="true"></div>
      `;

      autoInitWordResults();

      expect(cleanupRightFrames).toHaveBeenCalled();
    });

    it('does not call cleanupRightFrames when data attribute is missing', () => {
      document.body.innerHTML = '<div>No cleanup</div>';

      autoInitWordResults();

      expect(cleanupRightFrames).not.toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // Save Result Config Tests
  // ===========================================================================

  describe('save result config', () => {
    it('initializes from save result config', () => {
      document.body.innerHTML = `
        <script data-lwt-save-result-config type="application/json">
          {
            "wid": 123,
            "status": 2,
            "translation": "translated",
            "romanization": "roman",
            "text": "word",
            "hex": "abc123",
            "textId": 1,
            "todoContent": "5 words"
          }
        </script>
      `;

      autoInitWordResults();

      expect(updateNewWordInDOM).toHaveBeenCalledWith({
        wid: 123,
        status: 2,
        translation: 'translated',
        romanization: 'roman',
        text: 'word',
        hex: 'abc123'
      });
      expect(completeWordOperation).toHaveBeenCalledWith('5 words');
    });

    it('handles invalid JSON gracefully', () => {
      const errorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      document.body.innerHTML = `
        <script data-lwt-save-result-config type="application/json">
          invalid json
        </script>
      `;

      autoInitWordResults();

      expect(errorSpy).toHaveBeenCalledWith(
        'Failed to parse save result config:',
        expect.any(Error)
      );
    });
  });

  // ===========================================================================
  // Edit Result Config Tests
  // ===========================================================================

  describe('edit result config', () => {
    it('calls updateNewWordInDOM for new words', () => {
      document.body.innerHTML = `
        <script data-lwt-edit-result-config type="application/json">
          {
            "wid": 456,
            "status": 1,
            "translation": "new translation",
            "romanization": "",
            "text": "newword",
            "hex": "def456",
            "textId": 2,
            "todoContent": "10 words",
            "isNew": true
          }
        </script>
      `;

      autoInitWordResults();

      expect(updateNewWordInDOM).toHaveBeenCalled();
      expect(completeWordOperation).toHaveBeenCalledWith('10 words');
    });

    it('calls updateExistingWordInDOM for existing words', () => {
      document.body.innerHTML = `
        <script data-lwt-edit-result-config type="application/json">
          {
            "wid": 789,
            "status": 3,
            "oldStatus": 2,
            "translation": "updated",
            "romanization": "rom",
            "text": "existing",
            "textId": 1,
            "todoContent": "15 words",
            "isNew": false
          }
        </script>
      `;

      autoInitWordResults();

      expect(updateExistingWordInDOM).toHaveBeenCalled();
      expect(completeWordOperation).toHaveBeenCalledWith('15 words');
    });
  });

  // ===========================================================================
  // Delete Result Config Tests
  // ===========================================================================

  describe('delete result config', () => {
    it('initializes from delete result config', () => {
      document.body.innerHTML = `
        <script data-lwt-delete-result-config type="application/json">
          {
            "wid": 111,
            "term": "deleted word",
            "todoContent": "8 words"
          }
        </script>
      `;

      autoInitWordResults();

      expect(deleteWordFromDOM).toHaveBeenCalledWith(111, 'deleted word');
      expect(completeWordOperation).toHaveBeenCalledWith('8 words');
    });
  });

  // ===========================================================================
  // Insert Well-Known Result Config Tests
  // ===========================================================================

  describe('insert wellknown result config', () => {
    it('initializes from insert wellknown result config', () => {
      document.body.innerHTML = `
        <script data-lwt-insert-wellknown-result-config type="application/json">
          {
            "wid": 222,
            "hex": "aaa",
            "term": "known word",
            "todoContent": "3 words"
          }
        </script>
      `;

      autoInitWordResults();

      expect(markWordWellKnownInDOM).toHaveBeenCalledWith(222, 'aaa', 'known word');
      expect(completeWordOperation).toHaveBeenCalledWith('3 words');
    });
  });

  // ===========================================================================
  // Insert Ignore Result Config Tests
  // ===========================================================================

  describe('insert ignore result config', () => {
    it('initializes from insert ignore result config', () => {
      document.body.innerHTML = `
        <script data-lwt-insert-ignore-result-config type="application/json">
          {
            "wid": 333,
            "hex": "bbb",
            "term": "ignored word",
            "todoContent": "2 words"
          }
        </script>
      `;

      autoInitWordResults();

      expect(markWordIgnoredInDOM).toHaveBeenCalledWith(333, 'bbb', 'ignored word');
      expect(completeWordOperation).toHaveBeenCalledWith('2 words');
    });
  });

  // ===========================================================================
  // Edit Multi Update Result Config Tests
  // ===========================================================================

  describe('edit multi update result config', () => {
    it('initializes from edit multi update result config', () => {
      document.body.innerHTML = `
        <script data-lwt-edit-multi-update-result-config type="application/json">
          {
            "wid": 444,
            "text": "multi word",
            "translation": "phrase",
            "romanization": "rom",
            "status": 4,
            "oldStatus": 3
          }
        </script>
      `;

      autoInitWordResults();

      expect(updateMultiWordInDOM).toHaveBeenCalledWith(
        444, 'multi word', 'phrase', 'rom', 4, 3
      );
    });
  });

  // ===========================================================================
  // Delete Multi Result Config Tests
  // ===========================================================================

  describe('delete multi result config', () => {
    it('initializes from delete multi result config', () => {
      document.body.innerHTML = `
        <script data-lwt-delete-multi-result-config type="application/json">
          {
            "wid": 555,
            "showAll": true,
            "todoContent": "12 words"
          }
        </script>
      `;

      autoInitWordResults();

      expect(deleteMultiWordFromDOM).toHaveBeenCalledWith(555, true);
      expect(completeWordOperation).toHaveBeenCalledWith('12 words');
    });
  });

  // ===========================================================================
  // Bulk Save Result Config Tests
  // ===========================================================================

  describe('bulk save result config', () => {
    it('initializes from bulk save result config', () => {
      document.body.innerHTML = `
        <div id="displ_message">Updating...</div>
        <script data-lwt-bulk-save-result-config type="application/json">
          {
            "words": [
              { "wid": 1, "hex": "a", "status": 1 },
              { "wid": 2, "hex": "b", "status": 2 }
            ],
            "useTooltip": true,
            "cleanUp": true,
            "todoContent": "20 words"
          }
        </script>
      `;

      autoInitWordResults();

      expect(updateBulkWordInDOM).toHaveBeenCalledTimes(2);
      expect(updateLearnStatus).toHaveBeenCalledWith('20 words');
    });

    it('removes displ_message element', () => {
      document.body.innerHTML = `
        <div id="displ_message">Updating...</div>
        <script data-lwt-bulk-save-result-config type="application/json">
          {
            "words": [],
            "useTooltip": false,
            "cleanUp": false,
            "todoContent": ""
          }
        </script>
      `;

      autoInitWordResults();

      expect(document.getElementById('displ_message')).toBeNull();
    });

    it('calls cleanupRightFrames when cleanUp is true', () => {
      document.body.innerHTML = `
        <script data-lwt-bulk-save-result-config type="application/json">
          {
            "words": [],
            "useTooltip": false,
            "cleanUp": true,
            "todoContent": ""
          }
        </script>
      `;

      autoInitWordResults();

      expect(cleanupRightFrames).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // All Well-Known Result Config Tests
  // ===========================================================================

  describe('all wellknown result config', () => {
    beforeEach(() => {
      // Mock parent for cClick
      Object.defineProperty(window, 'parent', {
        writable: true,
        value: {
          cClick: vi.fn(),
          setTimeout: vi.fn((fn) => fn())
        }
      });
    });

    it('initializes from all wellknown result config', () => {
      document.body.innerHTML = `
        <span class="TERMabc status0">word1</span>
        <span class="TERMdef status0">word2</span>
        <script data-lwt-all-wellknown-config type="application/json">
          {
            "words": [
              { "wid": 1, "hex": "abc", "term": "word1", "status": 99 },
              { "wid": 2, "hex": "def", "term": "word2", "status": 98 }
            ],
            "useTooltips": true,
            "todoContent": "0 words"
          }
        </script>
      `;

      autoInitWordResults();

      expect(updateLearnStatus).toHaveBeenCalledWith('0 words');
    });

    it('updates word elements with new status', () => {
      document.body.innerHTML = `
        <span class="TERMabc status0" data_status="0">word</span>
        <script data-lwt-all-wellknown-config type="application/json">
          {
            "words": [
              { "wid": 1, "hex": "abc", "term": "word", "status": 99 }
            ],
            "useTooltips": false,
            "todoContent": ""
          }
        </script>
      `;

      autoInitWordResults();

      const wordEl = document.querySelector('.TERMabc');
      expect(wordEl?.classList.contains('status99')).toBe(true);
      expect(wordEl?.classList.contains('word1')).toBe(true);
      expect(wordEl?.getAttribute('data_status')).toBe('99');
      expect(wordEl?.getAttribute('data_wid')).toBe('1');
    });
  });

  // ===========================================================================
  // Hover Save Result Config Tests
  // ===========================================================================

  describe('hover save result config', () => {
    it('initializes from hover save result config', () => {
      document.body.innerHTML = `
        <span class="TERMabc status0">word</span>
        <script data-lwt-hover-save-result-config type="application/json">
          {
            "wid": 123,
            "hex": "abc",
            "status": 1,
            "translation": "translated",
            "wordRaw": "word",
            "todoContent": "5 words"
          }
        </script>
      `;

      autoInitWordResults();

      const wordEl = document.querySelector('.TERMabc');
      expect(wordEl?.classList.contains('status1')).toBe(true);
      expect(wordEl?.classList.contains('word123')).toBe(true);
      expect(updateLearnStatus).toHaveBeenCalledWith('5 words');
      expect(cleanupRightFrames).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // Edit Term Result Config Tests
  // ===========================================================================

  describe('edit term result config', () => {
    it('initializes from edit term result config for table test', () => {
      // Mock parent location for table test detection
      Object.defineProperty(window, 'parent', {
        writable: true,
        value: {
          location: { href: 'test.php?type=table' }
        }
      });

      document.body.innerHTML = `
        <span id="STAT123">Old status</span>
        <span id="TERM123">Old term</span>
        <span id="TRAN123">Old trans</span>
        <span id="ROMA123">Old roman</span>
        <span id="SENT123">Old sent</span>
        <script data-lwt-edit-term-result-config type="application/json">
          {
            "wid": 123,
            "text": "new term",
            "translation": "new trans",
            "translationWithTags": "<b>new trans</b>",
            "romanization": "new roman",
            "status": 2,
            "sentence": "new sentence",
            "statusControlsHtml": "<button>Status</button>"
          }
        </script>
      `;

      autoInitWordResults();

      expect(document.querySelector('#TERM123')!.innerHTML).toBe('new term');
      expect(document.querySelector('#TRAN123')!.innerHTML).toBe('new trans');
      expect(document.querySelector('#ROMA123')!.innerHTML).toBe('new roman');
      expect(document.querySelector('#SENT123')!.innerHTML).toBe('new sentence');
      expect(cleanupRightFrames).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // Multiple Config Elements Tests
  // ===========================================================================

  describe('multiple config elements', () => {
    it('handles no config elements', () => {
      document.body.innerHTML = '<div>No configs</div>';

      expect(() => autoInitWordResults()).not.toThrow();
    });

    it('processes multiple different config types', () => {
      document.body.innerHTML = `
        <div data-lwt-cleanup-frames="true"></div>
        <script data-lwt-delete-result-config type="application/json">
          { "wid": 1, "term": "word", "todoContent": "" }
        </script>
        <script data-lwt-insert-wellknown-result-config type="application/json">
          { "wid": 2, "hex": "a", "term": "known", "todoContent": "" }
        </script>
      `;

      autoInitWordResults();

      expect(cleanupRightFrames).toHaveBeenCalled();
      expect(deleteWordFromDOM).toHaveBeenCalled();
      expect(markWordWellKnownInDOM).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // Error Handling Tests
  // ===========================================================================

  describe('error handling', () => {
    it('handles invalid all wellknown config', () => {
      const errorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      document.body.innerHTML = `
        <script data-lwt-all-wellknown-config type="application/json">
          {invalid}
        </script>
      `;

      autoInitWordResults();

      expect(errorSpy).toHaveBeenCalledWith(
        'Failed to parse all wellknown result config:',
        expect.any(Error)
      );
    });

    it('handles invalid edit term result config', () => {
      const errorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      document.body.innerHTML = `
        <script data-lwt-edit-term-result-config type="application/json">
          not json
        </script>
      `;

      autoInitWordResults();

      expect(errorSpy).toHaveBeenCalledWith(
        'Failed to parse edit term result config:',
        expect.any(Error)
      );
    });

    it('handles invalid bulk save result config', () => {
      const errorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      document.body.innerHTML = `
        <script data-lwt-bulk-save-result-config type="application/json">
          {bad json
        </script>
      `;

      autoInitWordResults();

      expect(errorSpy).toHaveBeenCalledWith(
        'Failed to parse bulk save result config:',
        expect.any(Error)
      );
    });
  });
});
