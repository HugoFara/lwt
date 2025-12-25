/**
 * Tests for reading/word_actions.ts - Word operations via API
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock dependencies
vi.mock('../../../src/frontend/js/modules/vocabulary/api/terms_api', () => ({
  TermsApi: {
    setStatus: vi.fn(),
    incrementStatus: vi.fn(),
    delete: vi.fn(),
    createQuick: vi.fn()
  }
}));

vi.mock('../../../src/frontend/js/modules/review/api/review_api', () => ({
  ReviewApi: {
    updateStatus: vi.fn()
  }
}));

vi.mock('../../../src/frontend/js/modules/vocabulary/services/word_dom_updates', () => ({
  updateWordStatusInDOM: vi.fn(),
  deleteWordFromDOM: vi.fn(),
  markWordWellKnownInDOM: vi.fn(),
  markWordIgnoredInDOM: vi.fn(),
  updateLearnStatus: vi.fn()
}));

vi.mock('../../../src/frontend/js/modules/text/pages/reading/frame_management', () => ({
  cleanupRightFrames: vi.fn(),
  successSound: vi.fn(),
  failureSound: vi.fn()
}));

vi.mock('../../../src/frontend/js/modules/vocabulary/components/word_popup', () => ({
  cClick: vi.fn()
}));

vi.mock('../../../src/frontend/js/modules/vocabulary/components/result_panel', () => ({
  showResultPanel: vi.fn(),
  hideResultPanel: vi.fn(),
  showErrorInPanel: vi.fn()
}));

import {
  changeWordStatus,
  incrementWordStatus,
  deleteWord,
  markWellKnown,
  markIgnored,
  updateReviewStatus,
  getContextFromElement,
  buildContext,
  type WordActionContext
} from '../../../src/frontend/js/modules/vocabulary/services/word_actions';
import { TermsApi } from '../../../src/frontend/js/modules/vocabulary/api/terms_api';
import { ReviewApi } from '../../../src/frontend/js/modules/review/api/review_api';
import {
  updateWordStatusInDOM,
  deleteWordFromDOM,
  markWordWellKnownInDOM,
  markWordIgnoredInDOM,
  updateLearnStatus
} from '../../../src/frontend/js/modules/vocabulary/services/word_dom_updates';
import { successSound, failureSound } from '../../../src/frontend/js/modules/text/pages/reading/frame_management';
import { cClick } from '../../../src/frontend/js/modules/vocabulary/components/word_popup';
import { showResultPanel, showErrorInPanel } from '../../../src/frontend/js/modules/vocabulary/components/result_panel';

describe('reading/word_actions.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // changeWordStatus Tests
  // ===========================================================================

  describe('changeWordStatus', () => {
    const baseContext: WordActionContext = {
      textId: 1,
      wordId: 100,
      position: 5,
      text: 'hello',
      hex: 'abc123',
      status: 1,
      translation: 'bonjour',
      romanization: ''
    };

    it('returns error when wordId is missing', async () => {
      const context = { ...baseContext, wordId: undefined };

      const result = await changeWordStatus(context, 2);

      expect(result.success).toBe(false);
      expect(result.error).toBe('No word ID for status change');
    });

    it('calls TermsApi.setStatus with correct arguments', async () => {
      vi.mocked(TermsApi.setStatus).mockResolvedValue({ data: { set: 2 } });

      await changeWordStatus(baseContext, 2);

      expect(TermsApi.setStatus).toHaveBeenCalledWith(100, 2);
    });

    it('returns error on API failure', async () => {
      vi.mocked(TermsApi.setStatus).mockResolvedValue({ error: 'Server error' });

      const result = await changeWordStatus(baseContext, 2);

      expect(result.success).toBe(false);
      expect(result.error).toBe('Server error');
      expect(showErrorInPanel).toHaveBeenCalledWith('Server error');
    });

    it('updates DOM on success', async () => {
      vi.mocked(TermsApi.setStatus).mockResolvedValue({ data: { set: 3 } });

      await changeWordStatus(baseContext, 3);

      expect(updateWordStatusInDOM).toHaveBeenCalledWith(
        100,
        3,
        'hello',
        'bonjour',
        ''
      );
    });

    it('shows result panel on success', async () => {
      vi.mocked(TermsApi.setStatus).mockResolvedValue({ data: { set: 5 } });

      await changeWordStatus(baseContext, 5);

      expect(showResultPanel).toHaveBeenCalledWith(
        expect.stringContaining('Level 5'),
        expect.any(Object)
      );
    });

    it('closes popup on success', async () => {
      vi.mocked(TermsApi.setStatus).mockResolvedValue({ data: { set: 2 } });

      await changeWordStatus(baseContext, 2);

      expect(cClick).toHaveBeenCalled();
    });

    it('returns success with new status', async () => {
      vi.mocked(TermsApi.setStatus).mockResolvedValue({ data: { set: 4 } });

      const result = await changeWordStatus(baseContext, 4);

      expect(result.success).toBe(true);
      expect(result.newStatus).toBe(4);
    });

    it('handles status 98 (ignored)', async () => {
      vi.mocked(TermsApi.setStatus).mockResolvedValue({ data: { set: 98 } });

      const result = await changeWordStatus(baseContext, 98);

      expect(result.success).toBe(true);
      expect(showResultPanel).toHaveBeenCalledWith(
        expect.stringContaining('Ignored'),
        expect.any(Object)
      );
    });

    it('handles status 99 (well-known)', async () => {
      vi.mocked(TermsApi.setStatus).mockResolvedValue({ data: { set: 99 } });

      const result = await changeWordStatus(baseContext, 99);

      expect(result.success).toBe(true);
      expect(showResultPanel).toHaveBeenCalledWith(
        expect.stringContaining('Well-known'),
        expect.any(Object)
      );
    });
  });

  // ===========================================================================
  // incrementWordStatus Tests
  // ===========================================================================

  describe('incrementWordStatus', () => {
    const baseContext: WordActionContext = {
      textId: 1,
      wordId: 100,
      position: 5,
      text: 'hello'
    };

    it('returns error when wordId is missing', async () => {
      const context = { ...baseContext, wordId: undefined };

      const result = await incrementWordStatus(context, 'up');

      expect(result.success).toBe(false);
      expect(result.error).toBe('No word ID for status increment');
    });

    it('calls TermsApi.incrementStatus with up direction', async () => {
      vi.mocked(TermsApi.incrementStatus).mockResolvedValue({ data: { set: 3 } });

      await incrementWordStatus(baseContext, 'up');

      expect(TermsApi.incrementStatus).toHaveBeenCalledWith(100, 'up');
    });

    it('calls TermsApi.incrementStatus with down direction', async () => {
      vi.mocked(TermsApi.incrementStatus).mockResolvedValue({ data: { set: 1 } });

      await incrementWordStatus(baseContext, 'down');

      expect(TermsApi.incrementStatus).toHaveBeenCalledWith(100, 'down');
    });

    it('plays success sound on increment up', async () => {
      vi.mocked(TermsApi.incrementStatus).mockResolvedValue({ data: { set: 3 } });

      await incrementWordStatus(baseContext, 'up');

      expect(successSound).toHaveBeenCalled();
      expect(failureSound).not.toHaveBeenCalled();
    });

    it('plays failure sound on increment down', async () => {
      vi.mocked(TermsApi.incrementStatus).mockResolvedValue({ data: { set: 1 } });

      await incrementWordStatus(baseContext, 'down');

      expect(failureSound).toHaveBeenCalled();
      expect(successSound).not.toHaveBeenCalled();
    });

    it('returns error on API failure', async () => {
      vi.mocked(TermsApi.incrementStatus).mockResolvedValue({ error: 'Server error' });

      const result = await incrementWordStatus(baseContext, 'up');

      expect(result.success).toBe(false);
      expect(result.error).toBe('Server error');
    });

    it('updates learn status when increment data provided', async () => {
      vi.mocked(TermsApi.incrementStatus).mockResolvedValue({
        data: { set: 3, increment: '+1' }
      });

      await incrementWordStatus(baseContext, 'up');

      expect(updateLearnStatus).toHaveBeenCalledWith('+1');
    });

    it('returns new status on success', async () => {
      vi.mocked(TermsApi.incrementStatus).mockResolvedValue({ data: { set: 4 } });

      const result = await incrementWordStatus(baseContext, 'up');

      expect(result.success).toBe(true);
      expect(result.newStatus).toBe(4);
    });
  });

  // ===========================================================================
  // deleteWord Tests
  // ===========================================================================

  describe('deleteWord', () => {
    const baseContext: WordActionContext = {
      textId: 1,
      wordId: 100,
      position: 5,
      text: 'hello'
    };

    it('returns error when wordId is missing', async () => {
      const context = { ...baseContext, wordId: undefined };

      const result = await deleteWord(context);

      expect(result.success).toBe(false);
      expect(result.error).toBe('No word ID for deletion');
    });

    it('calls TermsApi.delete with correct ID', async () => {
      vi.mocked(TermsApi.delete).mockResolvedValue({ data: { deleted: true } });

      await deleteWord(baseContext);

      expect(TermsApi.delete).toHaveBeenCalledWith(100);
    });

    it('returns error on API failure', async () => {
      vi.mocked(TermsApi.delete).mockResolvedValue({ error: 'Delete failed' });

      const result = await deleteWord(baseContext);

      expect(result.success).toBe(false);
      expect(result.error).toBe('Delete failed');
    });

    it('updates DOM on success', async () => {
      vi.mocked(TermsApi.delete).mockResolvedValue({ data: { deleted: true } });

      await deleteWord(baseContext);

      expect(deleteWordFromDOM).toHaveBeenCalledWith(100, 'hello');
    });

    it('shows result panel on success', async () => {
      vi.mocked(TermsApi.delete).mockResolvedValue({ data: { deleted: true } });

      await deleteWord(baseContext);

      expect(showResultPanel).toHaveBeenCalledWith(
        'Term deleted',
        expect.any(Object)
      );
    });

    it('closes popup on success', async () => {
      vi.mocked(TermsApi.delete).mockResolvedValue({ data: { deleted: true } });

      await deleteWord(baseContext);

      expect(cClick).toHaveBeenCalled();
    });

    it('returns success on completion', async () => {
      vi.mocked(TermsApi.delete).mockResolvedValue({ data: { deleted: true } });

      const result = await deleteWord(baseContext);

      expect(result.success).toBe(true);
    });
  });

  // ===========================================================================
  // markWellKnown Tests
  // ===========================================================================

  describe('markWellKnown', () => {
    const baseContext: WordActionContext = {
      textId: 1,
      position: 5,
      text: 'hello',
      hex: 'abc123'
    };

    it('returns error when hex is missing', async () => {
      const context = { ...baseContext, hex: undefined };

      const result = await markWellKnown(context);

      expect(result.success).toBe(false);
      expect(result.error).toBe('No hex identifier for term');
    });

    it('calls TermsApi.createQuick with status 99', async () => {
      vi.mocked(TermsApi.createQuick).mockResolvedValue({ data: { term_id: 200 } });

      await markWellKnown(baseContext);

      expect(TermsApi.createQuick).toHaveBeenCalledWith(1, 5, 99);
    });

    it('returns error on API failure', async () => {
      vi.mocked(TermsApi.createQuick).mockResolvedValue({ error: 'Create failed' });

      const result = await markWellKnown(baseContext);

      expect(result.success).toBe(false);
      expect(result.error).toBe('Create failed');
    });

    it('updates DOM with new term ID', async () => {
      vi.mocked(TermsApi.createQuick).mockResolvedValue({ data: { term_id: 200 } });

      await markWellKnown(baseContext);

      expect(markWordWellKnownInDOM).toHaveBeenCalledWith(200, 'abc123', 'hello');
    });

    it('shows result panel on success', async () => {
      vi.mocked(TermsApi.createQuick).mockResolvedValue({ data: { term_id: 200 } });

      await markWellKnown(baseContext);

      expect(showResultPanel).toHaveBeenCalledWith(
        'Marked as well-known',
        expect.any(Object)
      );
    });

    it('returns success with status 99 and wordId', async () => {
      vi.mocked(TermsApi.createQuick).mockResolvedValue({ data: { term_id: 200 } });

      const result = await markWellKnown(baseContext);

      expect(result.success).toBe(true);
      expect(result.newStatus).toBe(99);
      expect(result.wordId).toBe(200);
    });

    it('closes popup on success', async () => {
      vi.mocked(TermsApi.createQuick).mockResolvedValue({ data: { term_id: 200 } });

      await markWellKnown(baseContext);

      expect(cClick).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // markIgnored Tests
  // ===========================================================================

  describe('markIgnored', () => {
    const baseContext: WordActionContext = {
      textId: 1,
      position: 5,
      text: 'the',
      hex: 'def456'
    };

    it('returns error when hex is missing', async () => {
      const context = { ...baseContext, hex: undefined };

      const result = await markIgnored(context);

      expect(result.success).toBe(false);
      expect(result.error).toBe('No hex identifier for term');
    });

    it('calls TermsApi.createQuick with status 98', async () => {
      vi.mocked(TermsApi.createQuick).mockResolvedValue({ data: { term_id: 201 } });

      await markIgnored(baseContext);

      expect(TermsApi.createQuick).toHaveBeenCalledWith(1, 5, 98);
    });

    it('returns error on API failure', async () => {
      vi.mocked(TermsApi.createQuick).mockResolvedValue({ error: 'Create failed' });

      const result = await markIgnored(baseContext);

      expect(result.success).toBe(false);
      expect(result.error).toBe('Create failed');
    });

    it('updates DOM with new term ID', async () => {
      vi.mocked(TermsApi.createQuick).mockResolvedValue({ data: { term_id: 201 } });

      await markIgnored(baseContext);

      expect(markWordIgnoredInDOM).toHaveBeenCalledWith(201, 'def456', 'the');
    });

    it('shows result panel on success', async () => {
      vi.mocked(TermsApi.createQuick).mockResolvedValue({ data: { term_id: 201 } });

      await markIgnored(baseContext);

      expect(showResultPanel).toHaveBeenCalledWith(
        'Marked as ignored',
        expect.any(Object)
      );
    });

    it('returns success with status 98', async () => {
      vi.mocked(TermsApi.createQuick).mockResolvedValue({ data: { term_id: 201 } });

      const result = await markIgnored(baseContext);

      expect(result.success).toBe(true);
      expect(result.newStatus).toBe(98);
    });
  });

  // ===========================================================================
  // updateReviewStatus Tests
  // ===========================================================================

  describe('updateReviewStatus', () => {
    it('calls ReviewApi.updateStatus with word ID and status', async () => {
      vi.mocked(ReviewApi.updateStatus).mockResolvedValue({ data: { status: 3 } });

      await updateReviewStatus(100, 3);

      expect(ReviewApi.updateStatus).toHaveBeenCalledWith(100, 3, undefined);
    });

    it('calls ReviewApi.updateStatus with change', async () => {
      vi.mocked(ReviewApi.updateStatus).mockResolvedValue({ data: { status: 4 } });

      await updateReviewStatus(100, undefined, 1);

      expect(ReviewApi.updateStatus).toHaveBeenCalledWith(100, undefined, 1);
    });

    it('returns error on API failure', async () => {
      vi.mocked(ReviewApi.updateStatus).mockResolvedValue({ error: 'Review failed' });

      const result = await updateReviewStatus(100, 3);

      expect(result.success).toBe(false);
      expect(result.error).toBe('Review failed');
    });

    it('plays success sound on positive change', async () => {
      vi.mocked(ReviewApi.updateStatus).mockResolvedValue({ data: { status: 4 } });

      await updateReviewStatus(100, undefined, 1);

      expect(successSound).toHaveBeenCalled();
    });

    it('plays failure sound on negative change', async () => {
      vi.mocked(ReviewApi.updateStatus).mockResolvedValue({ data: { status: 2 } });

      await updateReviewStatus(100, undefined, -1);

      expect(failureSound).toHaveBeenCalled();
    });

    it('does not play sound when no change provided', async () => {
      vi.mocked(ReviewApi.updateStatus).mockResolvedValue({ data: { status: 3 } });

      await updateReviewStatus(100, 3);

      expect(successSound).not.toHaveBeenCalled();
      expect(failureSound).not.toHaveBeenCalled();
    });

    it('returns new status on success', async () => {
      vi.mocked(ReviewApi.updateStatus).mockResolvedValue({ data: { status: 5 } });

      const result = await updateReviewStatus(100, 5);

      expect(result.success).toBe(true);
      expect(result.newStatus).toBe(5);
    });
  });

  // ===========================================================================
  // getContextFromElement Tests
  // ===========================================================================

  describe('getContextFromElement', () => {
    it('extracts hex from class name', () => {
      document.body.innerHTML = `
        <span class="word TERMabc123" data_wid="100" data_order="5">hello</span>
      `;
      const element = document.querySelector('span') as HTMLElement;

      const context = getContextFromElement(element);

      expect(context.hex).toBe('abc123');
    });

    it('extracts word ID from data attribute', () => {
      document.body.innerHTML = `
        <span class="word" data_wid="100">hello</span>
      `;
      const element = document.querySelector('span') as HTMLElement;

      const context = getContextFromElement(element);

      expect(context.wordId).toBe(100);
    });

    it('returns undefined wordId when not present', () => {
      document.body.innerHTML = `
        <span class="word" data_order="5">hello</span>
      `;
      const element = document.querySelector('span') as HTMLElement;

      const context = getContextFromElement(element);

      expect(context.wordId).toBeUndefined();
    });

    it('extracts position from data_order', () => {
      document.body.innerHTML = `
        <span class="word" data_order="15">hello</span>
      `;
      const element = document.querySelector('span') as HTMLElement;

      const context = getContextFromElement(element);

      expect(context.position).toBe(15);
    });

    it('extracts position from data_pos as fallback', () => {
      document.body.innerHTML = `
        <span class="word" data_pos="20">hello</span>
      `;
      const element = document.querySelector('span') as HTMLElement;

      const context = getContextFromElement(element);

      expect(context.position).toBe(20);
    });

    it('extracts text content', () => {
      document.body.innerHTML = `
        <span class="word">bonjour</span>
      `;
      const element = document.querySelector('span') as HTMLElement;

      const context = getContextFromElement(element);

      expect(context.text).toBe('bonjour');
    });

    it('extracts text from data_text for multiword terms', () => {
      document.body.innerHTML = `
        <span class="word mwsty" data_text="hello world">hello world</span>
      `;
      const element = document.querySelector('span') as HTMLElement;

      const context = getContextFromElement(element);

      expect(context.text).toBe('hello world');
    });

    it('extracts status from data_status', () => {
      document.body.innerHTML = `
        <span class="word" data_status="3">hello</span>
      `;
      const element = document.querySelector('span') as HTMLElement;

      const context = getContextFromElement(element);

      expect(context.status).toBe(3);
    });

    it('extracts translation from data_trans', () => {
      document.body.innerHTML = `
        <span class="word" data_trans="bonjour">hello</span>
      `;
      const element = document.querySelector('span') as HTMLElement;

      const context = getContextFromElement(element);

      expect(context.translation).toBe('bonjour');
    });

    it('extracts romanization from data_rom', () => {
      document.body.innerHTML = `
        <span class="word" data_rom="konnichiwa">こんにちは</span>
      `;
      const element = document.querySelector('span') as HTMLElement;

      const context = getContextFromElement(element);

      expect(context.romanization).toBe('konnichiwa');
    });

    it('extracts text ID from element data attribute', () => {
      document.body.innerHTML = `
        <span class="word" data-text-id="42">hello</span>
      `;
      const element = document.querySelector('span') as HTMLElement;

      const context = getContextFromElement(element);

      expect(context.textId).toBe(42);
    });

    it('extracts text ID from parent element', () => {
      document.body.innerHTML = `
        <div data-text-id="42">
          <span class="word">hello</span>
        </div>
      `;
      const element = document.querySelector('span') as HTMLElement;

      const context = getContextFromElement(element);

      expect(context.textId).toBe(42);
    });

    it('handles missing hex class', () => {
      document.body.innerHTML = `
        <span class="word status1">hello</span>
      `;
      const element = document.querySelector('span') as HTMLElement;

      const context = getContextFromElement(element);

      expect(context.hex).toBeUndefined();
    });
  });

  // ===========================================================================
  // buildContext Tests
  // ===========================================================================

  describe('buildContext', () => {
    it('creates context with required fields', () => {
      const context = buildContext({
        textId: 1,
        position: 5,
        text: 'hello'
      });

      expect(context.textId).toBe(1);
      expect(context.position).toBe(5);
      expect(context.text).toBe('hello');
    });

    it('creates context with all optional fields', () => {
      const context = buildContext({
        textId: 1,
        wordId: 100,
        position: 5,
        text: 'hello',
        hex: 'abc123',
        status: 3,
        translation: 'bonjour',
        romanization: 'hello'
      });

      expect(context.wordId).toBe(100);
      expect(context.hex).toBe('abc123');
      expect(context.status).toBe(3);
      expect(context.translation).toBe('bonjour');
      expect(context.romanization).toBe('hello');
    });

    it('handles undefined optional fields', () => {
      const context = buildContext({
        textId: 1,
        position: 5,
        text: 'hello',
        wordId: undefined,
        hex: undefined
      });

      expect(context.wordId).toBeUndefined();
      expect(context.hex).toBeUndefined();
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles concurrent status changes', async () => {
      vi.mocked(TermsApi.setStatus).mockResolvedValue({ data: { set: 2 } });

      const context: WordActionContext = {
        textId: 1,
        wordId: 100,
        position: 5,
        text: 'hello'
      };

      const [result1, result2] = await Promise.all([
        changeWordStatus(context, 2),
        changeWordStatus(context, 3)
      ]);

      expect(result1.success).toBe(true);
      expect(result2.success).toBe(true);
    });

    it('handles Unicode text in context', () => {
      document.body.innerHTML = `
        <span class="word" data_wid="100" data_trans="こんにちは">你好</span>
      `;
      const element = document.querySelector('span') as HTMLElement;

      const context = getContextFromElement(element);

      expect(context.text).toBe('你好');
      expect(context.translation).toBe('こんにちは');
    });

    it('handles empty strings in context', () => {
      document.body.innerHTML = `
        <span class="word" data_trans="" data_rom=""></span>
      `;
      const element = document.querySelector('span') as HTMLElement;

      const context = getContextFromElement(element);

      expect(context.translation).toBe('');
      expect(context.romanization).toBe('');
    });

    it('handles wordId of 0', () => {
      document.body.innerHTML = `
        <span class="word" data_wid="0">hello</span>
      `;
      const element = document.querySelector('span') as HTMLElement;

      const context = getContextFromElement(element);

      // 0 is falsy, so should be undefined
      expect(context.wordId).toBeUndefined();
    });

    it('markWellKnown does not update DOM when no term_id returned', async () => {
      vi.mocked(TermsApi.createQuick).mockResolvedValue({ data: {} });

      const context: WordActionContext = {
        textId: 1,
        position: 5,
        text: 'hello',
        hex: 'abc123'
      };

      await markWellKnown(context);

      expect(markWordWellKnownInDOM).not.toHaveBeenCalled();
    });
  });
});
