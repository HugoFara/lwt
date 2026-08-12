/**
 * Tests for modules/feed/api/save_feed.ts
 *
 * The shared save path behind the three feed forms outside the manager SPA.
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

vi.mock('../../../../src/frontend/js/shared/api/client', () => ({
  apiGet: vi.fn(),
  apiPost: vi.fn(),
  apiPut: vi.fn(),
  apiDelete: vi.fn()
}));

import {
  readFeedForm,
  saveFeed
} from '../../../../src/frontend/js/modules/feed/api/save_feed';
import { apiPost, apiPut } from '../../../../src/frontend/js/shared/api/client';

/** A payload that passes validation. */
function validData() {
  return {
    langId: 2,
    name: 'Tagesschau',
    sourceUri: 'https://example.com/rss',
    articleSectionTags: '//div',
    filterTags: '',
    options: 'edit_text=1'
  };
}

describe('save_feed.ts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // readFeedForm
  // ===========================================================================

  describe('readFeedForm', () => {
    it('maps the Nf-prefixed fields onto the API payload', () => {
      document.body.innerHTML = `
        <form>
          <select name="NfLgID"><option value="3" selected>Deutsch</option></select>
          <input name="NfName" value="Tagesschau">
          <input name="NfSourceURI" value="https://example.com/rss">
          <input name="NfArticleSectionTags" value="//div[@class='article']">
          <input name="NfFilterTags" value="//aside">
        </form>`;
      const form = document.querySelector('form')!;

      expect(readFeedForm(form, 'edit_text=1')).toEqual({
        langId: 3,
        name: 'Tagesschau',
        sourceUri: 'https://example.com/rss',
        articleSectionTags: "//div[@class='article']",
        filterTags: '//aside',
        options: 'edit_text=1'
      });
    });

    it('falls back to empty values for absent fields', () => {
      document.body.innerHTML = '<form></form>';
      const form = document.querySelector('form')!;

      const data = readFeedForm(form, '');

      expect(data.langId).toBe(0);
      expect(data.name).toBe('');
      expect(data.sourceUri).toBe('');
    });
  });

  // ===========================================================================
  // saveFeed — validation
  // ===========================================================================

  describe('saveFeed validation', () => {
    it('refuses a feed with no language', async () => {
      const result = await saveFeed({ ...validData(), langId: 0 }, null);

      expect(result.feedId).toBeNull();
      expect(result.error).toContain('language');
      expect(apiPost).not.toHaveBeenCalled();
    });

    it('refuses a feed with a blank name', async () => {
      const result = await saveFeed({ ...validData(), name: '   ' }, null);

      expect(result.feedId).toBeNull();
      expect(apiPost).not.toHaveBeenCalled();
    });

    it('refuses a feed with a blank URL', async () => {
      const result = await saveFeed({ ...validData(), sourceUri: '' }, null);

      expect(result.feedId).toBeNull();
      expect(apiPost).not.toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // saveFeed — dispatch
  // ===========================================================================

  describe('saveFeed dispatch', () => {
    it('posts when creating', async () => {
      vi.mocked(apiPost).mockResolvedValue({ data: { success: true, feed: { id: 7 } } });

      const result = await saveFeed(validData(), null);

      expect(apiPost).toHaveBeenCalledWith('/feeds', expect.objectContaining({ langId: 2 }));
      expect(result).toEqual({ feedId: 7, error: '' });
    });

    it('puts when updating', async () => {
      vi.mocked(apiPut).mockResolvedValue({ data: { success: true, feed: { id: 9 } } });

      const result = await saveFeed(validData(), 9);

      expect(apiPut).toHaveBeenCalledWith('/feeds/9', expect.any(Object));
      expect(apiPost).not.toHaveBeenCalled();
      expect(result.feedId).toBe(9);
    });

    it('passes a transport error through', async () => {
      vi.mocked(apiPost).mockResolvedValue({ error: 'Language not found or access denied' });

      const result = await saveFeed(validData(), null);

      expect(result).toEqual({
        feedId: null,
        error: 'Language not found or access denied'
      });
    });

    /**
     * The handler answers 200 with success:false for its own validation
     * failures, so a 200 is not on its own proof the feed was saved.
     */
    it('treats success:false as a failure', async () => {
      vi.mocked(apiPost).mockResolvedValue({
        data: { success: false, error: 'Feed name is required' }
      });

      const result = await saveFeed(validData(), null);

      expect(result.feedId).toBeNull();
      expect(result.error).toBe('Feed name is required');
    });

    it('keeps the known ID when an update answers without one', async () => {
      vi.mocked(apiPut).mockResolvedValue({ data: { success: true } });

      const result = await saveFeed(validData(), 9);

      expect(result.feedId).toBe(9);
    });
  });
});
