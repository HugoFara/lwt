/**
 * Tests for text_annotations.ts - Annotation processing for text reading
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  getAttr,
  word_each_do_text_text,
  mword_each_do_text_text
} from '../../../src/frontend/js/modules/text/pages/reading/text_annotations';

// Mock word_status module
vi.mock('../../../src/frontend/js/modules/vocabulary/services/word_status', () => ({
  make_tooltip: vi.fn((text, trans, rom, status) =>
    `${text} [${trans}] (${rom}) - Status: ${status}`
  )
}));

import { make_tooltip } from '../../../src/frontend/js/modules/vocabulary/services/word_status';
import {
  setAnnotations,
  resetTextConfig
} from '../../../src/frontend/js/modules/text/stores/text_config';
import {
  initLanguageConfig,
  resetLanguageConfig
} from '../../../src/frontend/js/modules/language/stores/language_config';

describe('text_annotations.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();

    // Initialize language config with default delimiter
    initLanguageConfig({
      id: 1,
      dictLink1: 'http://dict1.example.com/lwt_term',
      dictLink2: 'http://dict2.example.com/lwt_term',
      translatorLink: 'http://translate.example.com/lwt_term',
      delimiter: ',',
      rtl: false
    });

    // Reset annotations
    setAnnotations({});
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
    resetTextConfig();
    resetLanguageConfig();
  });

  // ===========================================================================
  // getAttr Tests
  // ===========================================================================

  describe('getAttr', () => {
    it('returns attribute value when it exists', () => {
      document.body.innerHTML = '<span id="test" data-value="hello"></span>';
      const el = document.getElementById('test') as HTMLElement;

      const result = getAttr(el, 'data-value');

      expect(result).toBe('hello');
    });

    it('returns empty string when attribute does not exist', () => {
      document.body.innerHTML = '<span id="test"></span>';
      const el = document.getElementById('test') as HTMLElement;

      const result = getAttr(el, 'data-missing');

      expect(result).toBe('');
    });

    it('returns empty string for undefined attribute', () => {
      document.body.innerHTML = '<span id="test" data-value></span>';
      const el = document.getElementById('test') as HTMLElement;

      const result = getAttr(el, 'data-nonexistent');

      expect(result).toBe('');
    });

    it('handles custom data attributes with underscores', () => {
      document.body.innerHTML = '<span id="test" data_order="15"></span>';
      const el = document.getElementById('test') as HTMLElement;

      const result = getAttr(el, 'data_order');

      expect(result).toBe('15');
    });

    it('handles empty attribute value', () => {
      document.body.innerHTML = '<span id="test" data-value=""></span>';
      const el = document.getElementById('test') as HTMLElement;

      const result = getAttr(el, 'data-value');

      expect(result).toBe('');
    });

    it('handles numeric attribute values as strings', () => {
      document.body.innerHTML = '<span id="test" data-count="42"></span>';
      const el = document.getElementById('test') as HTMLElement;

      const result = getAttr(el, 'data-count');

      expect(result).toBe('42');
      expect(typeof result).toBe('string');
    });
  });

  // ===========================================================================
  // word_each_do_text_text Tests
  // ===========================================================================

  describe('word_each_do_text_text', () => {
    it('does not match annotation when wid is empty', () => {
      setAnnotations({
        '10': [null, 'word123', 'note']
      });

      document.body.innerHTML = `
        <span id="word1" class="word" data_wid="" data_order="10" data_trans="translation" data_rom="rom" data_status="1">Hello</span>
      `;
      const element = document.getElementById('word1') as HTMLElement;

      word_each_do_text_text.call(element);

      // Should not have set data_ann since wid is empty and doesn't match annotation
      expect(element.getAttribute('data_ann')).toBeNull();
    });

    it('adds annotation when wid matches annotation entry', () => {
      setAnnotations({
        '10': [null, 'word123', 'note']
      });

      document.body.innerHTML = `
        <span id="word1" class="word" data_wid="word123" data_order="10" data_trans="translation" data_rom="rom" data_status="1">Hello</span>
      `;
      const element = document.getElementById('word1') as HTMLElement;

      word_each_do_text_text.call(element);

      expect(element.getAttribute('data_ann')).toBe('note');
    });

    it('combines annotation with translation when not duplicate', () => {
      setAnnotations({
        '10': [null, 'word123', 'annotation']
      });

      document.body.innerHTML = `
        <span id="word1" class="word" data_wid="word123" data_order="10" data_trans="translation" data_rom="rom" data_status="1">Hello</span>
      `;
      const element = document.getElementById('word1') as HTMLElement;

      word_each_do_text_text.call(element);

      expect(element.getAttribute('data_trans')).toBe('annotation / translation');
    });

    it('does not duplicate annotation in translation', () => {
      setAnnotations({
        '10': [null, 'word123', 'hello']
      });
      initLanguageConfig({ delimiter: ',' });

      document.body.innerHTML = `
        <span id="word1" class="word" data_wid="word123" data_order="10" data_trans="hello" data_rom="rom" data_status="1">Hello</span>
      `;
      const element = document.getElementById('word1') as HTMLElement;

      word_each_do_text_text.call(element);

      // Should not add duplicate
      expect(element.getAttribute('data_trans')).toBe('hello');
    });

    it('sets tooltip (native tooltips always enabled)', () => {
      document.body.innerHTML = `
        <span id="word1" class="word" data_wid="word123" data_order="10" data_trans="translation" data_rom="romanization" data_status="2">Hello</span>
      `;
      const element = document.getElementById('word1') as HTMLElement;

      word_each_do_text_text.call(element);

      expect(make_tooltip).toHaveBeenCalledWith(
        'Hello',
        'translation',
        'romanization',
        '2'
      );
    });

    it('handles missing data_status by defaulting to 0', () => {
      document.body.innerHTML = `
        <span id="word1" class="word" data_wid="word123" data_order="10" data_trans="translation" data_rom="rom">Hello</span>
      `;
      const element = document.getElementById('word1') as HTMLElement;

      word_each_do_text_text.call(element);

      expect(make_tooltip).toHaveBeenCalledWith(
        'Hello',
        'translation',
        'rom',
        '0'
      );
    });

    it('does not match annotation when wid differs', () => {
      setAnnotations({
        '10': [null, 'differentWord', 'note']
      });

      document.body.innerHTML = `
        <span id="word1" class="word" data_wid="word123" data_order="10" data_trans="translation" data_rom="rom" data_status="1">Hello</span>
      `;
      const element = document.getElementById('word1') as HTMLElement;

      word_each_do_text_text.call(element);

      expect(element.getAttribute('data_ann')).toBeNull();
    });

    it('handles annotation with special regex characters', () => {
      setAnnotations({
        '10': [null, 'word123', 'note (test)']
      });

      document.body.innerHTML = `
        <span id="word1" class="word" data_wid="word123" data_order="10" data_trans="other" data_rom="rom" data_status="1">Hello</span>
      `;
      const element = document.getElementById('word1') as HTMLElement;

      word_each_do_text_text.call(element);

      expect(element.getAttribute('data_ann')).toBe('note (test)');
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

      mword_each_do_text_text.call(element);

      expect(make_tooltip).not.toHaveBeenCalled();
    });

    it('does nothing when wid is empty', () => {
      document.body.innerHTML = `
        <span id="mword1" class="mword" data_wid="" data_order="10" data_trans="translation" data_rom="rom" data_status="1">Multi Word</span>
      `;
      const element = document.getElementById('mword1') as HTMLElement;

      mword_each_do_text_text.call(element);

      expect(element.getAttribute('data_ann')).toBeNull();
    });

    it('searches for annotation in even offsets (2, 4, 6...)', () => {
      // Annotation at offset +4 from order 10 = 14
      setAnnotations({
        '14': [null, 'mword123', 'multi annotation']
      });

      document.body.innerHTML = `
        <span id="mword1" class="mword" data_wid="mword123" data_order="10" data_trans="translation" data_rom="rom" data_status="2" data_text="Multi Word">Multi Word</span>
      `;
      const element = document.getElementById('mword1') as HTMLElement;

      mword_each_do_text_text.call(element);

      expect(element.getAttribute('data_ann')).toBe('multi annotation');
    });

    it('stops searching after finding first matching annotation', () => {
      setAnnotations({
        '12': [null, 'mword123', 'first annotation'],
        '14': [null, 'mword123', 'second annotation']
      });

      document.body.innerHTML = `
        <span id="mword1" class="mword" data_wid="mword123" data_order="10" data_trans="translation" data_rom="rom" data_status="2" data_text="Multi Word">Multi Word</span>
      `;
      const element = document.getElementById('mword1') as HTMLElement;

      mword_each_do_text_text.call(element);

      // Should find first one at offset +2
      expect(element.getAttribute('data_ann')).toBe('first annotation');
    });

    it('combines annotation with translation when not duplicate', () => {
      setAnnotations({
        '12': [null, 'mword123', 'note']
      });

      document.body.innerHTML = `
        <span id="mword1" class="mword" data_wid="mword123" data_order="10" data_trans="original" data_rom="rom" data_status="2" data_text="Multi">Multi</span>
      `;
      const element = document.getElementById('mword1') as HTMLElement;

      mword_each_do_text_text.call(element);

      expect(element.getAttribute('data_trans')).toBe('note / original');
    });

    it('does not duplicate annotation in translation', () => {
      setAnnotations({
        '12': [null, 'mword123', 'same']
      });
      initLanguageConfig({ delimiter: ',' });

      document.body.innerHTML = `
        <span id="mword1" class="mword" data_wid="mword123" data_order="10" data_trans="same" data_rom="rom" data_status="2" data_text="Multi">Multi</span>
      `;
      const element = document.getElementById('mword1') as HTMLElement;

      mword_each_do_text_text.call(element);

      expect(element.getAttribute('data_trans')).toBe('same');
    });

    it('sets tooltip (native tooltips always enabled)', () => {
      document.body.innerHTML = `
        <span id="mword1" class="mword" data_wid="mword123" data_order="10" data_trans="trans" data_rom="rom" data_status="3" data_text="Multi Word">Multi Word</span>
      `;
      const element = document.getElementById('mword1') as HTMLElement;

      mword_each_do_text_text.call(element);

      expect(make_tooltip).toHaveBeenCalledWith(
        'Multi Word',
        'trans',
        'rom',
        '3'
      );
    });

    it('uses data_text for tooltip instead of element text', () => {
      document.body.innerHTML = `
        <span id="mword1" class="mword" data_wid="mword123" data_order="10" data_trans="trans" data_rom="rom" data_status="3" data_text="Full Text">Short</span>
      `;
      const element = document.getElementById('mword1') as HTMLElement;

      mword_each_do_text_text.call(element);

      expect(make_tooltip).toHaveBeenCalledWith(
        'Full Text',
        'trans',
        'rom',
        '3'
      );
    });

    it('searches up to offset 16', () => {
      // Annotation at offset +16 from order 10 = 26
      setAnnotations({
        '26': [null, 'mword123', 'far annotation']
      });

      document.body.innerHTML = `
        <span id="mword1" class="mword" data_wid="mword123" data_order="10" data_trans="trans" data_rom="rom" data_status="2" data_text="Multi">Multi</span>
      `;
      const element = document.getElementById('mword1') as HTMLElement;

      mword_each_do_text_text.call(element);

      expect(element.getAttribute('data_ann')).toBe('far annotation');
    });

    it('does not find annotation beyond offset 16', () => {
      // Annotation at offset +18 from order 10 = 28 (beyond search range)
      setAnnotations({
        '28': [null, 'mword123', 'too far']
      });

      document.body.innerHTML = `
        <span id="mword1" class="mword" data_wid="mword123" data_order="10" data_trans="trans" data_rom="rom" data_status="2" data_text="Multi">Multi</span>
      `;
      const element = document.getElementById('mword1') as HTMLElement;

      mword_each_do_text_text.call(element);

      expect(element.getAttribute('data_ann')).toBeNull();
    });

    it('handles status 5 correctly', () => {
      document.body.innerHTML = `
        <span id="mword1" class="mword" data_wid="mword123" data_order="10" data_trans="trans" data_rom="rom" data_status="5" data_text="Multi">Multi</span>
      `;
      const element = document.getElementById('mword1') as HTMLElement;

      mword_each_do_text_text.call(element);

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
    it('getAttr handles element without attribute', () => {
      document.body.innerHTML = `<span id="test"></span>`;
      const element = document.getElementById('test') as HTMLElement;

      const result = getAttr(element, 'data-test');

      expect(result).toBe('');
    });

    it('word_each_do_text_text handles annotation with brackets', () => {
      setAnnotations({
        '10': [null, 'word123', 'note [extra]']
      });

      document.body.innerHTML = `
        <span id="word1" class="word" data_wid="word123" data_order="10" data_trans="other [info]" data_rom="rom" data_status="1">Hello</span>
      `;
      const element = document.getElementById('word1') as HTMLElement;

      word_each_do_text_text.call(element);

      const trans = element.getAttribute('data_trans');
      expect(trans).toContain('note [extra]');
    });

    it('handles delimiter at start/end of translation', () => {
      setAnnotations({
        '10': [null, 'word123', 'ann']
      });
      initLanguageConfig({ delimiter: ',' });

      document.body.innerHTML = `
        <span id="word1" class="word" data_wid="word123" data_order="10" data_trans=",ann," data_rom="rom" data_status="1">Hello</span>
      `;
      const element = document.getElementById('word1') as HTMLElement;

      word_each_do_text_text.call(element);

      // The regex should match ann at the start/end with delimiters
      expect(element.getAttribute('data_trans')).toBe(',ann,');
    });

    it('handles data_order with leading zeros', () => {
      setAnnotations({
        '5': [null, 'word123', 'note']
      });

      document.body.innerHTML = `
        <span id="word1" class="word" data_wid="word123" data_order="05" data_trans="trans" data_rom="rom" data_status="1">Hello</span>
      `;
      const element = document.getElementById('word1') as HTMLElement;

      word_each_do_text_text.call(element);

      // '05' !== '5' so annotation won't match - this is expected behavior
      expect(element.getAttribute('data_ann')).toBeNull();
    });

    it('mword_each_do_text_text handles zero order value', () => {
      setAnnotations({
        '2': [null, 'mword123', 'note']
      });

      document.body.innerHTML = `
        <span id="mword1" class="mword" data_wid="mword123" data_order="0" data_trans="trans" data_rom="rom" data_status="2" data_text="Multi">Multi</span>
      `;
      const element = document.getElementById('mword1') as HTMLElement;

      mword_each_do_text_text.call(element);

      expect(element.getAttribute('data_ann')).toBe('note');
    });
  });
});
