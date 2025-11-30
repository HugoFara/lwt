/**
 * Tests for term_operations.ts - Translation updates, term editing, and annotations
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import $ from 'jquery';
import {
  setTransRoman,
  translation_radio,
  display_example_sentences,
  do_ajax_save_impr_text,
  updateTermTranslation,
  addTermTranslation,
  changeTableTestStatus,
  do_ajax_req_sim_terms,
  do_ajax_show_similar_terms,
  change_example_sentences_zone,
  do_ajax_show_sentences,
  type TransData,
} from '../../../src/frontend/js/terms/term_operations';

// Mock lwtFormCheck global
const mockLwtFormCheck = {
  makeDirty: vi.fn(),
};

// Setup global mocks
beforeEach(() => {
  (window as unknown as Record<string, unknown>).lwtFormCheck = mockLwtFormCheck;
  (globalThis as unknown as Record<string, unknown>).$ = $;
});

describe('term_operations.ts', () => {
  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
    mockLwtFormCheck.makeDirty.mockClear();
  });

  // ===========================================================================
  // setTransRoman Tests
  // ===========================================================================

  describe('setTransRoman', () => {
    it('sets translation in WoTranslation textarea', () => {
      document.body.innerHTML = '<textarea name="WoTranslation"></textarea>';

      setTransRoman('hello', '');

      expect($('textarea[name="WoTranslation"]').val()).toBe('hello');
    });

    it('sets romanization in WoRomanization input', () => {
      document.body.innerHTML = '<input name="WoRomanization" />';

      setTransRoman('', 'pinyin');

      expect($('input[name="WoRomanization"]').val()).toBe('pinyin');
    });

    it('sets both translation and romanization', () => {
      document.body.innerHTML = `
        <textarea name="WoTranslation"></textarea>
        <input name="WoRomanization" />
      `;

      setTransRoman('hello', 'hola');

      expect($('textarea[name="WoTranslation"]').val()).toBe('hello');
      expect($('input[name="WoRomanization"]').val()).toBe('hola');
    });

    it('calls lwtFormCheck.makeDirty when translation is set', () => {
      document.body.innerHTML = '<textarea name="WoTranslation"></textarea>';

      setTransRoman('test', '');

      expect(mockLwtFormCheck.makeDirty).toHaveBeenCalled();
    });

    it('calls lwtFormCheck.makeDirty when romanization is set', () => {
      document.body.innerHTML = '<input name="WoRomanization" />';

      setTransRoman('', 'test');

      expect(mockLwtFormCheck.makeDirty).toHaveBeenCalled();
    });

    it('does not call makeDirty when no fields exist', () => {
      document.body.innerHTML = '<div>No form fields</div>';

      setTransRoman('test', 'test');

      expect(mockLwtFormCheck.makeDirty).not.toHaveBeenCalled();
    });

    it('handles empty strings', () => {
      document.body.innerHTML = `
        <textarea name="WoTranslation">existing</textarea>
        <input name="WoRomanization" value="existing" />
      `;

      setTransRoman('', '');

      expect($('textarea[name="WoTranslation"]').val()).toBe('');
      expect($('input[name="WoRomanization"]').val()).toBe('');
    });

    it('handles special characters in translation', () => {
      document.body.innerHTML = '<textarea name="WoTranslation"></textarea>';

      setTransRoman('<script>alert("xss")</script>', '');

      expect($('textarea[name="WoTranslation"]').val()).toBe('<script>alert("xss")</script>');
    });

    it('handles unicode characters', () => {
      document.body.innerHTML = `
        <textarea name="WoTranslation"></textarea>
        <input name="WoRomanization" />
      `;

      setTransRoman('日本語', 'にほんご');

      expect($('textarea[name="WoTranslation"]').val()).toBe('日本語');
      expect($('input[name="WoRomanization"]').val()).toBe('にほんご');
    });
  });

  // ===========================================================================
  // translation_radio Tests
  // ===========================================================================

  describe('translation_radio', () => {
    const baseTransData: TransData = {
      wid: 1,
      trans: 'hello',
      ann_index: '0',
      term_ord: '1',
      term_lc: 'test',
      lang_id: 1,
      translations: ['hello', 'hi', 'hey']
    };

    it('returns empty string when wid is null', () => {
      const transData = { ...baseTransData, wid: null };
      const result = translation_radio('hello', transData);
      expect(result).toBe('');
    });

    it('returns empty string for empty translation', () => {
      const result = translation_radio('', baseTransData);
      expect(result).toBe('');
    });

    it('returns empty string for whitespace-only translation', () => {
      const result = translation_radio('   ', baseTransData);
      expect(result).toBe('');
    });

    it('returns empty string for asterisk translation', () => {
      const result = translation_radio('*', baseTransData);
      expect(result).toBe('');
    });

    it('creates radio button HTML for valid translation', () => {
      const result = translation_radio('hello', baseTransData);

      expect(result).toContain('<input');
      expect(result).toContain('type="radio"');
      expect(result).toContain('name="rg0"');
      expect(result).toContain('value="hello"');
      expect(result).toContain('hello');
    });

    it('marks radio as checked when translation matches current', () => {
      const transData = { ...baseTransData, trans: 'hello' };
      const result = translation_radio('hello', transData);

      expect(result).toContain('checked="checked"');
    });

    it('does not mark radio as checked when translation differs', () => {
      const transData = { ...baseTransData, trans: 'goodbye' };
      const result = translation_radio('hello', transData);

      expect(result).not.toContain('checked="checked"');
    });

    it('escapes HTML special characters in translation', () => {
      const result = translation_radio('<script>', baseTransData);

      expect(result).toContain('&lt;script&gt;');
      expect(result).not.toContain('<script>');
    });

    it('uses correct annotation index in name attribute', () => {
      const transData = { ...baseTransData, ann_index: '42' };
      const result = translation_radio('test', transData);

      expect(result).toContain('name="rg42"');
    });

    it('adds impr-ann-radio class to input', () => {
      const result = translation_radio('test', baseTransData);

      expect(result).toContain('class="impr-ann-radio"');
    });

    it('handles translations with quotes', () => {
      const result = translation_radio('say "hello"', baseTransData);

      expect(result).toContain('&quot;');
    });

    it('handles translations with ampersands', () => {
      const result = translation_radio('cats & dogs', baseTransData);

      expect(result).toContain('&amp;');
    });

    it('trims whitespace from translation', () => {
      const result = translation_radio('  hello  ', baseTransData);

      expect(result).toContain('value="hello"');
    });
  });

  // ===========================================================================
  // display_example_sentences Tests
  // ===========================================================================

  describe('display_example_sentences', () => {
    beforeEach(() => {
      (window as unknown as Record<string, unknown>).lwtFormCheck = mockLwtFormCheck;
    });

    it('returns a div element', () => {
      const result = display_example_sentences([], 'document.getElementById("target")');

      expect(result).toBeInstanceOf(HTMLDivElement);
    });

    it('returns empty div for empty sentences array', () => {
      const result = display_example_sentences([], 'target');

      expect(result.children.length).toBe(0);
    });

    it('creates one child per sentence', () => {
      const sentences: [string, string][] = [
        ['<b>Hello</b> world', 'Hello'],
        ['<b>Goodbye</b> world', 'Goodbye']
      ];

      const result = display_example_sentences(sentences, 'target');

      expect(result.children.length).toBe(2);
    });

    it('includes tick-button image for each sentence', () => {
      const sentences: [string, string][] = [
        ['Test sentence', 'Test']
      ];

      const result = display_example_sentences(sentences, 'target');
      const img = result.querySelector('img');

      expect(img).not.toBeNull();
      expect(img?.src).toContain('tick-button.png');
      expect(img?.title).toBe('Choose');
    });

    it('creates clickable span with data attributes', () => {
      const sentences: [string, string][] = [
        ['Test sentence', 'Test']
      ];

      const result = display_example_sentences(sentences, 'myField');
      const clickable = result.querySelector('span.click');

      expect(clickable).not.toBeNull();
      expect(clickable?.getAttribute('data-action')).toBe('copy-sentence');
      expect(clickable?.getAttribute('data-target')).toBe('myField');
      expect(clickable?.getAttribute('data-sentence')).toBe('Test');
    });

    it('includes sentence display text', () => {
      const sentences: [string, string][] = [
        ['<b>Hello</b> world', 'Hello']
      ];

      const result = display_example_sentences(sentences, 'target');

      expect(result.innerHTML).toContain('<b>Hello</b> world');
    });

    it('stores sentence with special characters in data attribute', () => {
      const sentences: [string, string][] = [
        ['Test', "it's a test"]
      ];

      const result = display_example_sentences(sentences, 'target');
      const clickable = result.querySelector('span.click');

      expect(clickable?.getAttribute('data-sentence')).toBe("it's a test");
    });

    it('uses data-action copy-sentence for event delegation', () => {
      const sentences: [string, string][] = [
        ['Test', 'value']
      ];

      const result = display_example_sentences(sentences, 'target');
      const clickable = result.querySelector('span.click');

      expect(clickable?.getAttribute('data-action')).toBe('copy-sentence');
      expect(clickable?.getAttribute('data-target')).toBe('target');
    });

    it('handles multiple sentences correctly', () => {
      const sentences: [string, string][] = [
        ['First sentence', 'First'],
        ['Second sentence', 'Second'],
        ['Third sentence', 'Third']
      ];

      const result = display_example_sentences(sentences, 'target');

      expect(result.children.length).toBe(3);
      expect(result.innerHTML).toContain('First sentence');
      expect(result.innerHTML).toContain('Second sentence');
      expect(result.innerHTML).toContain('Third sentence');
    });

    it('creates correct structure: div > span.click > img', () => {
      const sentences: [string, string][] = [
        ['Test', 'value']
      ];

      const result = display_example_sentences(sentences, 'target');
      const parentDiv = result.firstChild as HTMLDivElement;
      const clickSpan = parentDiv.firstChild as HTMLSpanElement;
      const img = clickSpan.firstChild as HTMLImageElement;

      expect(parentDiv.tagName).toBe('DIV');
      expect(clickSpan.tagName).toBe('SPAN');
      expect(clickSpan.classList.contains('click')).toBe(true);
      expect(img.tagName).toBe('IMG');
    });
  });

  // ===========================================================================
  // TransData Interface Tests
  // ===========================================================================

  describe('TransData interface', () => {
    it('accepts valid TransData object', () => {
      const data: TransData = {
        wid: 1,
        trans: 'translation',
        ann_index: '0',
        term_ord: '1',
        term_lc: 'word',
        lang_id: 1,
        translations: ['trans1', 'trans2']
      };

      expect(data.wid).toBe(1);
      expect(data.translations).toHaveLength(2);
    });

    it('allows null wid', () => {
      const data: TransData = {
        wid: null,
        trans: '',
        ann_index: '0',
        term_ord: '1',
        term_lc: 'word',
        lang_id: 1,
        translations: []
      };

      expect(data.wid).toBeNull();
    });

    it('allows empty translations array', () => {
      const data: TransData = {
        wid: 1,
        trans: '',
        ann_index: '0',
        term_ord: '1',
        term_lc: 'word',
        lang_id: 1,
        translations: []
      };

      expect(data.translations).toHaveLength(0);
    });
  });

  // ===========================================================================
  // do_ajax_save_impr_text Tests
  // ===========================================================================

  describe('do_ajax_save_impr_text', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <span id="wait1"><img src="icn/empty.gif" /></span>
        <div id="editimprtextdata" data_id="123"></div>
      `;
    });

    it('shows waiting indicator and makes POST request', () => {
      const postSpy = vi.spyOn($, 'post').mockImplementation(
        (_url, _data, callback) => {
          if (typeof callback === 'function') {
            callback({});
          }
          return {} as JQuery.jqXHR;
        }
      );

      do_ajax_save_impr_text(123, 'rg1', '{"rg1":"test"}');

      expect(postSpy).toHaveBeenCalledWith(
        'api.php/v1/texts/123/annotation',
        expect.objectContaining({
          elem: 'rg1',
          data: '{"rg1":"test"}',
        }),
        expect.any(Function),
        'json'
      );
    });

    it('alerts on error response', () => {
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});
      vi.spyOn($, 'post').mockImplementation((_url, _data, callback) => {
        if (typeof callback === 'function') {
          callback({ error: 'Test error message' });
        }
        return {} as JQuery.jqXHR;
      });

      do_ajax_save_impr_text(123, 'rg1', '{}');

      expect(alertSpy).toHaveBeenCalledWith(
        expect.stringContaining('Test error message')
      );
    });
  });

  // ===========================================================================
  // updateTermTranslation Tests
  // ===========================================================================

  describe('updateTermTranslation', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <input id="trans-field" value="new translation" />
        <div id="editimprtextdata" data_id="1"></div>
      `;
    });

    it('alerts when translation is empty', () => {
      document.body.innerHTML = '<input id="trans-field" value="" />';
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      updateTermTranslation(1, '#trans-field');

      expect(alertSpy).toHaveBeenCalledWith(
        expect.stringContaining('empty')
      );
    });

    it('alerts when translation is asterisk', () => {
      document.body.innerHTML = '<input id="trans-field" value="*" />';
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      updateTermTranslation(1, '#trans-field');

      expect(alertSpy).toHaveBeenCalledWith(
        expect.stringContaining("'*'")
      );
    });

    it('makes POST request with trimmed translation', () => {
      document.body.innerHTML = '<input id="trans-field" value="  trimmed  " />';
      const postSpy = vi.spyOn($, 'post').mockImplementation(() => ({} as JQuery.jqXHR));

      updateTermTranslation(42, '#trans-field');

      expect(postSpy).toHaveBeenCalledWith(
        'api.php/v1/terms/42/translations',
        expect.objectContaining({ translation: 'trimmed' }),
        expect.any(Function),
        'json'
      );
    });

    it('alerts on empty response', () => {
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});
      vi.spyOn($, 'post').mockImplementation((_url, _data, callback) => {
        if (typeof callback === 'function') {
          callback('');
        }
        return {} as JQuery.jqXHR;
      });

      updateTermTranslation(1, '#trans-field');

      expect(alertSpy).toHaveBeenCalledWith(
        expect.stringContaining('failed')
      );
    });

    it('alerts on error response', () => {
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});
      vi.spyOn($, 'post').mockImplementation((_url, _data, callback) => {
        if (typeof callback === 'function') {
          callback({ error: 'DB error' });
        }
        return {} as JQuery.jqXHR;
      });

      updateTermTranslation(1, '#trans-field');

      expect(alertSpy).toHaveBeenCalledWith(
        expect.stringContaining('DB error')
      );
    });
  });

  // ===========================================================================
  // addTermTranslation Tests
  // ===========================================================================

  describe('addTermTranslation', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <input id="trans-field" value="new translation" />
        <div id="editimprtextdata" data_id="1"></div>
      `;
    });

    it('alerts when translation is empty', () => {
      document.body.innerHTML = '<input id="trans-field" value="" />';
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      addTermTranslation('#trans-field', 'word', 1);

      expect(alertSpy).toHaveBeenCalledWith(
        expect.stringContaining('empty')
      );
    });

    it('makes POST request with correct parameters', () => {
      const postSpy = vi.spyOn($, 'post').mockImplementation(() => ({} as JQuery.jqXHR));

      addTermTranslation('#trans-field', 'testword', 5);

      expect(postSpy).toHaveBeenCalledWith(
        'api.php/v1/terms/new',
        expect.objectContaining({
          translation: 'new translation',
          term_text: 'testword',
          lg_id: 5,
        }),
        expect.any(Function),
        'json'
      );
    });

    it('alerts on error response', () => {
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});
      vi.spyOn($, 'post').mockImplementation((_url, _data, callback) => {
        if (typeof callback === 'function') {
          callback({ error: 'Creation failed' });
        }
        return {} as JQuery.jqXHR;
      });

      addTermTranslation('#trans-field', 'word', 1);

      expect(alertSpy).toHaveBeenCalledWith(
        expect.stringContaining('Creation failed')
      );
    });
  });

  // ===========================================================================
  // changeTableTestStatus Tests
  // ===========================================================================

  describe('changeTableTestStatus', () => {
    beforeEach(() => {
      document.body.innerHTML = '<span id="STAT123">Current Status</span>';
    });

    it('makes POST request for status up', () => {
      const postSpy = vi.spyOn($, 'post').mockImplementation(() => ({} as JQuery.jqXHR));

      changeTableTestStatus('123', true);

      expect(postSpy).toHaveBeenCalledWith(
        'api.php/v1/terms/123/status/up',
        {},
        expect.any(Function),
        'json'
      );
    });

    it('makes POST request for status down', () => {
      const postSpy = vi.spyOn($, 'post').mockImplementation(() => ({} as JQuery.jqXHR));

      changeTableTestStatus('123', false);

      expect(postSpy).toHaveBeenCalledWith(
        'api.php/v1/terms/123/status/down',
        {},
        expect.any(Function),
        'json'
      );
    });

    it('updates DOM on successful response', () => {
      vi.spyOn($, 'post').mockImplementation((_url, _data, callback) => {
        if (typeof callback === 'function') {
          callback({ increment: '<span class="status5">5</span>' });
        }
        return {} as JQuery.jqXHR;
      });

      changeTableTestStatus('123', true);

      expect($('#STAT123').html()).toContain('status5');
    });

    it('does nothing on empty response', () => {
      vi.spyOn($, 'post').mockImplementation((_url, _data, callback) => {
        if (typeof callback === 'function') {
          callback('');
        }
        return {} as JQuery.jqXHR;
      });

      changeTableTestStatus('123', true);

      expect($('#STAT123').html()).toBe('Current Status');
    });

    it('does nothing on error response', () => {
      vi.spyOn($, 'post').mockImplementation((_url, _data, callback) => {
        if (typeof callback === 'function') {
          callback({ error: 'Status change failed' });
        }
        return {} as JQuery.jqXHR;
      });

      changeTableTestStatus('123', true);

      expect($('#STAT123').html()).toBe('Current Status');
    });
  });

  // ===========================================================================
  // do_ajax_req_sim_terms Tests
  // ===========================================================================

  describe('do_ajax_req_sim_terms', () => {
    it('makes GET request with correct parameters', () => {
      const getJSONSpy = vi.spyOn($, 'getJSON').mockImplementation(
        () => ({} as JQuery.jqXHR<{ similar_terms: string }>)
      );

      do_ajax_req_sim_terms(5, 'hello');

      expect(getJSONSpy).toHaveBeenCalledWith(
        'api.php/v1/similar-terms',
        { lg_id: 5, term: 'hello' }
      );
    });

    it('returns jQuery jqXHR object', () => {
      const mockJqXHR = { done: vi.fn(), fail: vi.fn() } as unknown as JQuery.jqXHR<{
        similar_terms: string;
      }>;
      vi.spyOn($, 'getJSON').mockReturnValue(mockJqXHR);

      const result = do_ajax_req_sim_terms(1, 'test');

      expect(result).toBe(mockJqXHR);
    });
  });

  // ===========================================================================
  // do_ajax_show_similar_terms Tests
  // ===========================================================================

  describe('do_ajax_show_similar_terms', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <div id="simwords"></div>
        <input id="langfield" value="5" />
        <input id="wordfield" value="hello" />
      `;
    });

    it('shows loading indicator', () => {
      vi.spyOn($, 'getJSON').mockReturnValue({
        done: () => ({ fail: vi.fn() }),
        fail: vi.fn(),
      } as unknown as JQuery.jqXHR<{ similar_terms: string }>);

      do_ajax_show_similar_terms();

      expect($('#simwords').html()).toContain('waiting2.gif');
    });

    it('updates simwords on success', () => {
      vi.spyOn($, 'getJSON').mockReturnValue({
        done: vi.fn().mockImplementation(function (this: unknown, callback: (data: { similar_terms: string }) => void) {
          callback({ similar_terms: '<div>Similar words here</div>' });
          return { fail: vi.fn() };
        }),
        fail: vi.fn(),
      } as unknown as JQuery.jqXHR<{ similar_terms: string }>);

      do_ajax_show_similar_terms();

      expect($('#simwords').html()).toBe('<div>Similar words here</div>');
    });
  });

  // ===========================================================================
  // change_example_sentences_zone Tests
  // ===========================================================================

  describe('change_example_sentences_zone', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <div id="exsent-waiting" style="display: block;"></div>
        <div id="exsent-sentences" style="display: none;"></div>
      `;
    });

    it('hides waiting indicator and shows sentences zone', () => {
      change_example_sentences_zone([], 'target');

      expect($('#exsent-waiting').css('display')).toBe('none');
      expect($('#exsent-sentences').css('display')).not.toBe('none');
    });

    it('appends sentences to the zone', () => {
      const sentences: [string, string][] = [
        ['Test sentence', 'Test'],
      ];

      change_example_sentences_zone(sentences, 'target');

      expect($('#exsent-sentences').html()).toContain('Test sentence');
    });
  });

  // ===========================================================================
  // do_ajax_show_sentences Tests
  // ===========================================================================

  describe('do_ajax_show_sentences', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <div id="exsent-interactable" style="display: block;"></div>
        <div id="exsent-waiting" style="display: none;"></div>
        <div id="exsent-sentences" style="display: none;"></div>
      `;
    });

    it('shows waiting indicator and hides interactable', () => {
      vi.spyOn($, 'getJSON').mockImplementation(() => ({} as JQuery.jqXHR));

      do_ajax_show_sentences(1, 'word', 'target', 5);

      expect($('#exsent-interactable').css('display')).toBe('none');
      expect($('#exsent-waiting').css('display')).not.toBe('none');
    });

    it('calls API with term ID when wid is a valid number', () => {
      const getJSONSpy = vi.spyOn($, 'getJSON').mockImplementation(
        () => ({} as JQuery.jqXHR)
      );

      do_ajax_show_sentences(1, 'word', 'target', 42);

      expect(getJSONSpy).toHaveBeenCalledWith(
        'api.php/v1/sentences-with-term/42',
        expect.objectContaining({ lg_id: 1, word_lc: 'word' }),
        expect.any(Function)
      );
    });

    it('calls API without term ID when wid is -1 (advanced search)', () => {
      const getJSONSpy = vi.spyOn($, 'getJSON').mockImplementation(
        () => ({} as JQuery.jqXHR)
      );

      do_ajax_show_sentences(1, 'word', 'target', -1);

      expect(getJSONSpy).toHaveBeenCalledWith(
        'api.php/v1/sentences-with-term',
        expect.objectContaining({
          lg_id: 1,
          word_lc: 'word',
          advanced_search: true,
        }),
        expect.any(Function)
      );
    });

    it('calls API without term ID for non-integer wid', () => {
      const getJSONSpy = vi.spyOn($, 'getJSON').mockImplementation(
        () => ({} as JQuery.jqXHR)
      );

      do_ajax_show_sentences(1, 'word', 'target', 'invalid');

      expect(getJSONSpy).toHaveBeenCalledWith(
        'api.php/v1/sentences-with-term',
        expect.objectContaining({ lg_id: 1, word_lc: 'word' }),
        expect.any(Function)
      );
    });
  });

  // ===========================================================================
  // changeImprAnnText Tests
  // ===========================================================================

  describe('changeImprAnnText', () => {
    // Note: These tests are skipped because they require the serializeObject plugin
    // which is loaded separately via jQuery extensions
    it.skip('checks previous radio button and triggers save', () => {
      // This would need $.fn.serializeObject to be available
    });
  });

  // ===========================================================================
  // changeImprAnnRadio Tests
  // ===========================================================================

  describe('changeImprAnnRadio', () => {
    // Note: These tests are skipped because they require the serializeObject plugin
    it.skip('triggers save when radio changes', () => {
      // This would need $.fn.serializeObject to be available
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('setTransRoman handles multiple WoTranslation textareas (only first)', () => {
      document.body.innerHTML = `
        <textarea name="WoTranslation"></textarea>
        <textarea name="WoTranslation"></textarea>
      `;

      // With length === 1 check, should not set when multiple exist
      setTransRoman('test', '');

      // The function checks length === 1, so with 2 textareas it won't set
      expect($('textarea[name="WoTranslation"]').first().val()).toBe('');
    });

    it('translation_radio handles very long translations', () => {
      const longTrans = 'a'.repeat(1000);
      const result = translation_radio(longTrans, {
        wid: 1,
        trans: '',
        ann_index: '0',
        term_ord: '1',
        term_lc: 'word',
        lang_id: 1,
        translations: []
      });

      expect(result).toContain(longTrans);
    });

    it('display_example_sentences handles sentences with HTML', () => {
      const sentences: [string, string][] = [
        ['<div class="highlight">Test</div>', 'Test']
      ];

      const result = display_example_sentences(sentences, 'target');

      // The HTML should be preserved in the display
      expect(result.innerHTML).toContain('<div class="highlight">');
    });

    it('display_example_sentences handles empty string values', () => {
      const sentences: [string, string][] = [
        ['', '']
      ];

      const result = display_example_sentences(sentences, 'target');

      expect(result.children.length).toBe(1);
    });
  });
});
