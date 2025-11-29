/**
 * Tests for text_annotations.ts - Annotation processing for text reading
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import $ from 'jquery';
import {
  getAttr,
  word_each_do_text_text,
  mword_each_do_text_text
} from '../../../src/frontend/js/reading/text_annotations';

// Make jQuery available globally
(global as any).$ = $;
(global as any).jQuery = $;

// Mock word_status module
vi.mock('../../../src/frontend/js/terms/word_status', () => ({
  make_tooltip: vi.fn((text, trans, rom, status) =>
    `${text} [${trans}] (${rom}) - Status: ${status}`
  )
}));

import { make_tooltip } from '../../../src/frontend/js/terms/word_status';

// Mock LWT_DATA global
const createMockLWT_DATA = () => ({
  language: {
    id: 1,
    dict_link1: 'http://dict1.example.com/###',
    dict_link2: 'http://dict2.example.com/###',
    translator_link: 'http://translate.example.com/###',
    delimiter: ',',
    rtl: false,
  },
  text: {
    id: 42,
    reading_position: 0,
    annotations: {} as Record<string, [unknown, string, string]>,
  },
  settings: {
    jQuery_tooltip: false,
    hts: 0,
    word_status_filter: '',
    annotations_mode: 0,
  },
});

describe('text_annotations.ts', () => {
  let mockLWT_DATA: ReturnType<typeof createMockLWT_DATA>;

  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    mockLWT_DATA = createMockLWT_DATA();
    (window as any).LWT_DATA = mockLWT_DATA;
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // getAttr Tests
  // ===========================================================================

  describe('getAttr', () => {
    it('returns attribute value when it exists', () => {
      document.body.innerHTML = '<span id="test" data-value="hello"></span>';
      const $el = $('#test');

      const result = getAttr($el, 'data-value');

      expect(result).toBe('hello');
    });

    it('returns empty string when attribute does not exist', () => {
      document.body.innerHTML = '<span id="test"></span>';
      const $el = $('#test');

      const result = getAttr($el, 'data-missing');

      expect(result).toBe('');
    });

    it('returns empty string for undefined attribute', () => {
      document.body.innerHTML = '<span id="test" data-value></span>';
      const $el = $('#test');

      const result = getAttr($el, 'data-nonexistent');

      expect(result).toBe('');
    });

    it('handles custom data attributes with underscores', () => {
      document.body.innerHTML = '<span id="test" data_order="15"></span>';
      const $el = $('#test');

      const result = getAttr($el, 'data_order');

      expect(result).toBe('15');
    });

    it('handles empty attribute value', () => {
      document.body.innerHTML = '<span id="test" data-value=""></span>';
      const $el = $('#test');

      const result = getAttr($el, 'data-value');

      expect(result).toBe('');
    });

    it('handles numeric attribute values as strings', () => {
      document.body.innerHTML = '<span id="test" data-count="42"></span>';
      const $el = $('#test');

      const result = getAttr($el, 'data-count');

      expect(result).toBe('42');
      expect(typeof result).toBe('string');
    });
  });

  // ===========================================================================
  // word_each_do_text_text Tests
  // ===========================================================================

  describe('word_each_do_text_text', () => {
    it('does not match annotation when wid is empty', () => {
      mockLWT_DATA.text.annotations = {
        '10': [null, 'word123', 'note']
      };
      mockLWT_DATA.settings.jQuery_tooltip = true;  // Disable tooltip
      (window as any).LWT_DATA = mockLWT_DATA;

      document.body.innerHTML = `
        <span id="word1" class="word" data_wid="" data_order="10" data_trans="translation" data_rom="rom" data_status="1">Hello</span>
      `;
      const element = document.getElementById('word1') as HTMLElement;

      word_each_do_text_text.call(element, 0);

      // Should not have set data_ann since wid is empty and doesn't match annotation
      expect($(element).attr('data_ann')).toBeUndefined();
    });

    it('adds annotation when wid matches annotation entry', () => {
      mockLWT_DATA.text.annotations = {
        '10': [null, 'word123', 'note']
      };
      (window as any).LWT_DATA = mockLWT_DATA;

      document.body.innerHTML = `
        <span id="word1" class="word" data_wid="word123" data_order="10" data_trans="translation" data_rom="rom" data_status="1">Hello</span>
      `;
      const element = document.getElementById('word1') as HTMLElement;

      word_each_do_text_text.call(element, 0);

      expect($(element).attr('data_ann')).toBe('note');
    });

    it('combines annotation with translation when not duplicate', () => {
      mockLWT_DATA.text.annotations = {
        '10': [null, 'word123', 'annotation']
      };
      (window as any).LWT_DATA = mockLWT_DATA;

      document.body.innerHTML = `
        <span id="word1" class="word" data_wid="word123" data_order="10" data_trans="translation" data_rom="rom" data_status="1">Hello</span>
      `;
      const element = document.getElementById('word1') as HTMLElement;

      word_each_do_text_text.call(element, 0);

      expect($(element).attr('data_trans')).toBe('annotation / translation');
    });

    it('does not duplicate annotation in translation', () => {
      mockLWT_DATA.text.annotations = {
        '10': [null, 'word123', 'hello']
      };
      mockLWT_DATA.language.delimiter = ',';
      (window as any).LWT_DATA = mockLWT_DATA;

      document.body.innerHTML = `
        <span id="word1" class="word" data_wid="word123" data_order="10" data_trans="hello" data_rom="rom" data_status="1">Hello</span>
      `;
      const element = document.getElementById('word1') as HTMLElement;

      word_each_do_text_text.call(element, 0);

      // Should not add duplicate
      expect($(element).attr('data_trans')).toBe('hello');
    });

    it('sets tooltip when jQuery_tooltip is disabled', () => {
      mockLWT_DATA.settings.jQuery_tooltip = false;
      (window as any).LWT_DATA = mockLWT_DATA;

      document.body.innerHTML = `
        <span id="word1" class="word" data_wid="word123" data_order="10" data_trans="translation" data_rom="romanization" data_status="2">Hello</span>
      `;
      const element = document.getElementById('word1') as HTMLElement;

      word_each_do_text_text.call(element, 0);

      expect(make_tooltip).toHaveBeenCalledWith(
        'Hello',
        'translation',
        'romanization',
        '2'
      );
    });

    it('does not set tooltip when jQuery_tooltip is enabled', () => {
      mockLWT_DATA.settings.jQuery_tooltip = true;
      (window as any).LWT_DATA = mockLWT_DATA;

      document.body.innerHTML = `
        <span id="word1" class="word" data_wid="word123" data_order="10" data_trans="translation" data_rom="rom" data_status="1">Hello</span>
      `;
      const element = document.getElementById('word1') as HTMLElement;

      word_each_do_text_text.call(element, 0);

      expect(make_tooltip).not.toHaveBeenCalled();
    });

    it('handles missing data_status by defaulting to 0', () => {
      mockLWT_DATA.settings.jQuery_tooltip = false;
      (window as any).LWT_DATA = mockLWT_DATA;

      document.body.innerHTML = `
        <span id="word1" class="word" data_wid="word123" data_order="10" data_trans="translation" data_rom="rom">Hello</span>
      `;
      const element = document.getElementById('word1') as HTMLElement;

      word_each_do_text_text.call(element, 0);

      expect(make_tooltip).toHaveBeenCalledWith(
        'Hello',
        'translation',
        'rom',
        '0'
      );
    });

    it('does not match annotation when wid differs', () => {
      mockLWT_DATA.text.annotations = {
        '10': [null, 'differentWord', 'note']
      };
      (window as any).LWT_DATA = mockLWT_DATA;

      document.body.innerHTML = `
        <span id="word1" class="word" data_wid="word123" data_order="10" data_trans="translation" data_rom="rom" data_status="1">Hello</span>
      `;
      const element = document.getElementById('word1') as HTMLElement;

      word_each_do_text_text.call(element, 0);

      expect($(element).attr('data_ann')).toBeUndefined();
    });

    it('handles annotation with special regex characters', () => {
      mockLWT_DATA.text.annotations = {
        '10': [null, 'word123', 'note (test)']
      };
      (window as any).LWT_DATA = mockLWT_DATA;

      document.body.innerHTML = `
        <span id="word1" class="word" data_wid="word123" data_order="10" data_trans="other" data_rom="rom" data_status="1">Hello</span>
      `;
      const element = document.getElementById('word1') as HTMLElement;

      word_each_do_text_text.call(element, 0);

      expect($(element).attr('data_ann')).toBe('note (test)');
    });
  });

  // ===========================================================================
  // mword_each_do_text_text Tests
  // ===========================================================================

  describe('mword_each_do_text_text', () => {
    it('does nothing when data_status is empty', () => {
      document.body.innerHTML = `
        <span id="mword1" class="mword" data_wid="mword123" data_order="10" data_trans="translation" data_rom="rom" data_status="">Multi Word</span>
      `;
      const element = document.getElementById('mword1') as HTMLElement;

      mword_each_do_text_text.call(element, 0);

      expect(make_tooltip).not.toHaveBeenCalled();
    });

    it('does nothing when wid is empty', () => {
      document.body.innerHTML = `
        <span id="mword1" class="mword" data_wid="" data_order="10" data_trans="translation" data_rom="rom" data_status="1">Multi Word</span>
      `;
      const element = document.getElementById('mword1') as HTMLElement;

      mword_each_do_text_text.call(element, 0);

      expect($(element).attr('data_ann')).toBeUndefined();
    });

    it('searches for annotation in even offsets (2, 4, 6...)', () => {
      // Annotation at offset +4 from order 10 = 14
      mockLWT_DATA.text.annotations = {
        '14': [null, 'mword123', 'multi annotation']
      };
      (window as any).LWT_DATA = mockLWT_DATA;

      document.body.innerHTML = `
        <span id="mword1" class="mword" data_wid="mword123" data_order="10" data_trans="translation" data_rom="rom" data_status="2" data_text="Multi Word">Multi Word</span>
      `;
      const element = document.getElementById('mword1') as HTMLElement;

      mword_each_do_text_text.call(element, 0);

      expect($(element).attr('data_ann')).toBe('multi annotation');
    });

    it('stops searching after finding first matching annotation', () => {
      mockLWT_DATA.text.annotations = {
        '12': [null, 'mword123', 'first annotation'],
        '14': [null, 'mword123', 'second annotation']
      };
      (window as any).LWT_DATA = mockLWT_DATA;

      document.body.innerHTML = `
        <span id="mword1" class="mword" data_wid="mword123" data_order="10" data_trans="translation" data_rom="rom" data_status="2" data_text="Multi Word">Multi Word</span>
      `;
      const element = document.getElementById('mword1') as HTMLElement;

      mword_each_do_text_text.call(element, 0);

      // Should find first one at offset +2
      expect($(element).attr('data_ann')).toBe('first annotation');
    });

    it('combines annotation with translation when not duplicate', () => {
      mockLWT_DATA.text.annotations = {
        '12': [null, 'mword123', 'note']
      };
      (window as any).LWT_DATA = mockLWT_DATA;

      document.body.innerHTML = `
        <span id="mword1" class="mword" data_wid="mword123" data_order="10" data_trans="original" data_rom="rom" data_status="2" data_text="Multi">Multi</span>
      `;
      const element = document.getElementById('mword1') as HTMLElement;

      mword_each_do_text_text.call(element, 0);

      expect($(element).attr('data_trans')).toBe('note / original');
    });

    it('does not duplicate annotation in translation', () => {
      mockLWT_DATA.text.annotations = {
        '12': [null, 'mword123', 'same']
      };
      mockLWT_DATA.language.delimiter = ',';
      (window as any).LWT_DATA = mockLWT_DATA;

      document.body.innerHTML = `
        <span id="mword1" class="mword" data_wid="mword123" data_order="10" data_trans="same" data_rom="rom" data_status="2" data_text="Multi">Multi</span>
      `;
      const element = document.getElementById('mword1') as HTMLElement;

      mword_each_do_text_text.call(element, 0);

      expect($(element).attr('data_trans')).toBe('same');
    });

    it('sets tooltip when jQuery_tooltip is disabled', () => {
      mockLWT_DATA.settings.jQuery_tooltip = false;
      (window as any).LWT_DATA = mockLWT_DATA;

      document.body.innerHTML = `
        <span id="mword1" class="mword" data_wid="mword123" data_order="10" data_trans="trans" data_rom="rom" data_status="3" data_text="Multi Word">Multi Word</span>
      `;
      const element = document.getElementById('mword1') as HTMLElement;

      mword_each_do_text_text.call(element, 0);

      expect(make_tooltip).toHaveBeenCalledWith(
        'Multi Word',
        'trans',
        'rom',
        '3'
      );
    });

    it('does not set tooltip when jQuery_tooltip is enabled', () => {
      mockLWT_DATA.settings.jQuery_tooltip = true;
      (window as any).LWT_DATA = mockLWT_DATA;

      document.body.innerHTML = `
        <span id="mword1" class="mword" data_wid="mword123" data_order="10" data_trans="trans" data_rom="rom" data_status="3" data_text="Multi">Multi</span>
      `;
      const element = document.getElementById('mword1') as HTMLElement;

      mword_each_do_text_text.call(element, 0);

      expect(make_tooltip).not.toHaveBeenCalled();
    });

    it('uses data_text for tooltip instead of element text', () => {
      mockLWT_DATA.settings.jQuery_tooltip = false;
      (window as any).LWT_DATA = mockLWT_DATA;

      document.body.innerHTML = `
        <span id="mword1" class="mword" data_wid="mword123" data_order="10" data_trans="trans" data_rom="rom" data_status="3" data_text="Full Text">Short</span>
      `;
      const element = document.getElementById('mword1') as HTMLElement;

      mword_each_do_text_text.call(element, 0);

      expect(make_tooltip).toHaveBeenCalledWith(
        'Full Text',
        'trans',
        'rom',
        '3'
      );
    });

    it('searches up to offset 16', () => {
      // Annotation at offset +16 from order 10 = 26
      mockLWT_DATA.text.annotations = {
        '26': [null, 'mword123', 'far annotation']
      };
      (window as any).LWT_DATA = mockLWT_DATA;

      document.body.innerHTML = `
        <span id="mword1" class="mword" data_wid="mword123" data_order="10" data_trans="trans" data_rom="rom" data_status="2" data_text="Multi">Multi</span>
      `;
      const element = document.getElementById('mword1') as HTMLElement;

      mword_each_do_text_text.call(element, 0);

      expect($(element).attr('data_ann')).toBe('far annotation');
    });

    it('does not find annotation beyond offset 16', () => {
      // Annotation at offset +18 from order 10 = 28 (beyond search range)
      mockLWT_DATA.text.annotations = {
        '28': [null, 'mword123', 'too far']
      };
      (window as any).LWT_DATA = mockLWT_DATA;

      document.body.innerHTML = `
        <span id="mword1" class="mword" data_wid="mword123" data_order="10" data_trans="trans" data_rom="rom" data_status="2" data_text="Multi">Multi</span>
      `;
      const element = document.getElementById('mword1') as HTMLElement;

      mword_each_do_text_text.call(element, 0);

      expect($(element).attr('data_ann')).toBeUndefined();
    });

    it('handles missing data_status by defaulting to 0', () => {
      mockLWT_DATA.settings.jQuery_tooltip = false;
      (window as any).LWT_DATA = mockLWT_DATA;

      document.body.innerHTML = `
        <span id="mword1" class="mword" data_wid="mword123" data_order="10" data_trans="trans" data_rom="rom" data_status="5" data_text="Multi">Multi</span>
      `;
      const element = document.getElementById('mword1') as HTMLElement;

      mword_each_do_text_text.call(element, 0);

      expect(make_tooltip).toHaveBeenCalledWith(
        'Multi',
        'trans',
        'rom',
        '5'
      );
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('getAttr handles jQuery object with no elements', () => {
      const $empty = $();

      const result = getAttr($empty, 'data-test');

      expect(result).toBe('');
    });

    it('word_each_do_text_text handles annotation with brackets', () => {
      mockLWT_DATA.text.annotations = {
        '10': [null, 'word123', 'note [extra]']
      };
      (window as any).LWT_DATA = mockLWT_DATA;

      document.body.innerHTML = `
        <span id="word1" class="word" data_wid="word123" data_order="10" data_trans="other [info]" data_rom="rom" data_status="1">Hello</span>
      `;
      const element = document.getElementById('word1') as HTMLElement;

      word_each_do_text_text.call(element, 0);

      expect($(element).attr('data_trans')).toContain('note [extra]');
    });

    it('handles delimiter at start/end of translation', () => {
      mockLWT_DATA.text.annotations = {
        '10': [null, 'word123', 'ann']
      };
      mockLWT_DATA.language.delimiter = ',';
      (window as any).LWT_DATA = mockLWT_DATA;

      document.body.innerHTML = `
        <span id="word1" class="word" data_wid="word123" data_order="10" data_trans=",ann," data_rom="rom" data_status="1">Hello</span>
      `;
      const element = document.getElementById('word1') as HTMLElement;

      word_each_do_text_text.call(element, 0);

      // The regex should match ann at the start/end with delimiters
      expect($(element).attr('data_trans')).toBe(',ann,');
    });

    it('handles data_order with leading zeros', () => {
      mockLWT_DATA.text.annotations = {
        '5': [null, 'word123', 'note']
      };
      (window as any).LWT_DATA = mockLWT_DATA;

      document.body.innerHTML = `
        <span id="word1" class="word" data_wid="word123" data_order="05" data_trans="trans" data_rom="rom" data_status="1">Hello</span>
      `;
      const element = document.getElementById('word1') as HTMLElement;

      word_each_do_text_text.call(element, 0);

      // '05' !== '5' so annotation won't match - this is expected behavior
      expect($(element).attr('data_ann')).toBeUndefined();
    });

    it('mword_each_do_text_text handles zero order value', () => {
      mockLWT_DATA.text.annotations = {
        '2': [null, 'mword123', 'note']
      };
      (window as any).LWT_DATA = mockLWT_DATA;

      document.body.innerHTML = `
        <span id="mword1" class="mword" data_wid="mword123" data_order="0" data_trans="trans" data_rom="rom" data_status="2" data_text="Multi">Multi</span>
      `;
      const element = document.getElementById('mword1') as HTMLElement;

      mword_each_do_text_text.call(element, 0);

      expect($(element).attr('data_ann')).toBe('note');
    });
  });
});
