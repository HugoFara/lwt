/**
 * Tests for modules/text/pages/text_form_save.ts
 *
 * The text editor's form markup is unchanged by the move to /api/v1 — the
 * fields keep their TxTitle / TxText / TxLgID names — so what these cover is
 * the translation from that markup to the API payload.
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

vi.mock('../../../src/frontend/js/shared/api/client', () => ({
  apiGet: vi.fn(),
  apiPost: vi.fn(),
  apiPut: vi.fn(),
  apiDelete: vi.fn()
}));

import {
  readTagNames,
  readTextForm,
  saveTextForm
} from '../../../src/frontend/js/modules/text/pages/text_form_save';
import { apiPost, apiPut } from '../../../src/frontend/js/shared/api/client';

/**
 * Build a text editor form with the given field values.
 */
function buildForm(fields: Record<string, string>, tagMarkup = ''): HTMLFormElement {
  const form = document.createElement('form');
  form.innerHTML =
    Object.entries(fields)
      .map(([name, value]) => `<input name="${name}" value="${value}">`)
      .join('') + tagMarkup;
  document.body.appendChild(form);
  return form;
}

describe('text_form_save.ts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // readTagNames
  // ===========================================================================

  describe('readTagNames', () => {
    it('returns an empty list when the widget is absent', () => {
      const form = buildForm({ TxTitle: 'A' });
      expect(readTagNames(form)).toEqual([]);
    });

    it('reads one name per widget input', () => {
      const form = buildForm(
        {},
        `<input name="TextTags[TagList][]" value="news">
         <input name="TextTags[TagList][]" value="german">`
      );
      expect(readTagNames(form)).toEqual(['news', 'german']);
    });

    it('splits comma-joined values the way the server does', () => {
      const form = buildForm({}, '<input name="TextTags[TagList][]" value="news,german">');
      expect(readTagNames(form)).toEqual(['news', 'german']);
    });

    it('trims and drops blanks', () => {
      const form = buildForm({}, '<input name="TextTags[TagList][]" value=" news , , german ,">');
      expect(readTagNames(form)).toEqual(['news', 'german']);
    });

    it('reads the selected options of a multi-select widget', () => {
      const form = document.createElement('form');
      form.innerHTML = `
        <select name="TextTags[TagList][]" multiple>
          <option value="news" selected>news</option>
          <option value="german" selected>german</option>
          <option value="unused">unused</option>
        </select>`;
      document.body.appendChild(form);

      expect(readTagNames(form)).toEqual(['news', 'german']);
    });
  });

  // ===========================================================================
  // readTextForm
  // ===========================================================================

  describe('readTextForm', () => {
    it('maps the form fields onto the API payload', () => {
      const form = buildForm({
        TxTitle: 'Der Wanderer',
        TxLgID: '3',
        TxText: 'Ein kurzer Text.',
        TxSourceURI: 'https://example.com/a',
        TxAudioURI: 'media/a.mp3'
      });

      expect(readTextForm(form)).toEqual({
        title: 'Der Wanderer',
        langId: 3,
        text: 'Ein kurzer Text.',
        sourceUri: 'https://example.com/a',
        audioUri: 'media/a.mp3',
        tags: []
      });
    });

    it('trims the title but leaves the text untouched', () => {
      const form = buildForm({ TxTitle: '  Titel  ', TxText: '  Text  ', TxLgID: '1' });

      const payload = readTextForm(form);

      expect(payload.title).toBe('Titel');
      expect(payload.text).toBe('  Text  ');
    });

    it('falls back to zero for a missing or unparseable language', () => {
      expect(readTextForm(buildForm({ TxTitle: 'T' })).langId).toBe(0);
      expect(readTextForm(buildForm({ TxLgID: 'abc' })).langId).toBe(0);
    });

    it('uses empty strings for absent optional fields', () => {
      const payload = readTextForm(buildForm({ TxTitle: 'T', TxText: 'x', TxLgID: '1' }));

      expect(payload.sourceUri).toBe('');
      expect(payload.audioUri).toBe('');
    });
  });

  // ===========================================================================
  // saveTextForm
  // ===========================================================================

  describe('saveTextForm', () => {
    const fields = { TxTitle: 'T', TxLgID: '2', TxText: 'Words' };

    it('creates through POST when there is no text id', async () => {
      vi.mocked(apiPost).mockResolvedValue({
        data: { textId: 7, bookId: null, message: 'saved' }
      });

      const result = await saveTextForm(buildForm(fields), 0);

      expect(apiPost).toHaveBeenCalledWith('/texts', expect.objectContaining({
        title: 'T',
        language_id: 2,
        text: 'Words'
      }));
      expect(apiPut).not.toHaveBeenCalled();
      expect(result).toEqual({ textId: 7, bookId: null, error: '' });
    });

    it('updates through PUT when there is a text id', async () => {
      vi.mocked(apiPut).mockResolvedValue({
        data: { textId: 9, bookId: null, message: 'saved' }
      });

      const result = await saveTextForm(buildForm(fields), 9);

      expect(apiPut).toHaveBeenCalledWith('/texts/9', expect.any(Object));
      expect(apiPost).not.toHaveBeenCalled();
      expect(result.textId).toBe(9);
    });

    it('reports the book a long text was split into', async () => {
      vi.mocked(apiPost).mockResolvedValue({
        data: { textId: 11, bookId: 4, message: 'split' }
      });

      const result = await saveTextForm(buildForm(fields), 0);

      expect(result.bookId).toBe(4);
      expect(result.textId).toBe(11);
    });

    it('passes the API error through', async () => {
      vi.mocked(apiPost).mockResolvedValue({ error: 'Language not found' });

      const result = await saveTextForm(buildForm(fields), 0);

      expect(result).toEqual({ textId: null, bookId: null, error: 'Language not found' });
    });

    it('reports a message when the response carries neither data nor error', async () => {
      vi.mocked(apiPost).mockResolvedValue({});

      const result = await saveTextForm(buildForm(fields), 0);

      expect(result.textId).toBeNull();
      expect(result.error).not.toBe('');
    });

    it('sends the tag names the widget holds', async () => {
      vi.mocked(apiPost).mockResolvedValue({
        data: { textId: 1, bookId: null, message: '' }
      });

      await saveTextForm(
        buildForm(fields, '<input name="TextTags[TagList][]" value="news,german">'),
        0
      );

      expect(apiPost).toHaveBeenCalledWith('/texts', expect.objectContaining({
        tags: ['news', 'german']
      }));
    });
  });
});
