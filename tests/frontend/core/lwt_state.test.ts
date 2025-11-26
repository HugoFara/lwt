/**
 * Tests for lwt_state.ts - LWT State Management and core data structures
 */
import { describe, it, expect, beforeEach } from 'vitest';
import {
  LWT_DATA,
  WID,
  TID,
  WBLINK1,
  WBLINK2,
  WBLINK3,
  RTL,
  type LwtLanguage,
  type LwtText,
  type LwtWord,
  type LwtTest,
  type LwtSettings,
  type LwtDataInterface,
} from '../../../src/frontend/js/core/lwt_state';

describe('lwt_state.ts', () => {
  // ===========================================================================
  // LWT_DATA Object Structure Tests
  // ===========================================================================

  describe('LWT_DATA structure', () => {
    it('is defined and is an object', () => {
      expect(LWT_DATA).toBeDefined();
      expect(typeof LWT_DATA).toBe('object');
    });

    it('has all required top-level properties', () => {
      expect(LWT_DATA).toHaveProperty('language');
      expect(LWT_DATA).toHaveProperty('text');
      expect(LWT_DATA).toHaveProperty('word');
      expect(LWT_DATA).toHaveProperty('test');
      expect(LWT_DATA).toHaveProperty('settings');
    });
  });

  // ===========================================================================
  // LWT_DATA.language Tests
  // ===========================================================================

  describe('LWT_DATA.language', () => {
    it('has correct initial structure', () => {
      const lang = LWT_DATA.language;
      expect(lang).toHaveProperty('id');
      expect(lang).toHaveProperty('dict_link1');
      expect(lang).toHaveProperty('dict_link2');
      expect(lang).toHaveProperty('translator_link');
      expect(lang).toHaveProperty('delimiter');
      expect(lang).toHaveProperty('word_parsing');
      expect(lang).toHaveProperty('rtl');
      expect(lang).toHaveProperty('ttsVoiceApi');
    });

    it('has correct initial values', () => {
      expect(LWT_DATA.language.id).toBe(0);
      expect(LWT_DATA.language.dict_link1).toBe('');
      expect(LWT_DATA.language.dict_link2).toBe('');
      expect(LWT_DATA.language.translator_link).toBe('');
      expect(LWT_DATA.language.delimiter).toBe('');
      expect(LWT_DATA.language.word_parsing).toBe('');
      expect(LWT_DATA.language.rtl).toBe(false);
      expect(LWT_DATA.language.ttsVoiceApi).toBe('');
    });

    it('allows modification of language properties', () => {
      const originalId = LWT_DATA.language.id;
      LWT_DATA.language.id = 42;
      expect(LWT_DATA.language.id).toBe(42);
      // Reset
      LWT_DATA.language.id = originalId;
    });

    it('allows setting dictionary links', () => {
      const original = LWT_DATA.language.dict_link1;
      LWT_DATA.language.dict_link1 = 'http://dict.example.com';
      expect(LWT_DATA.language.dict_link1).toBe('http://dict.example.com');
      LWT_DATA.language.dict_link1 = original;
    });

    it('allows setting rtl flag', () => {
      const original = LWT_DATA.language.rtl;
      LWT_DATA.language.rtl = true;
      expect(LWT_DATA.language.rtl).toBe(true);
      LWT_DATA.language.rtl = original;
    });
  });

  // ===========================================================================
  // LWT_DATA.text Tests
  // ===========================================================================

  describe('LWT_DATA.text', () => {
    it('has correct initial structure', () => {
      const text = LWT_DATA.text;
      expect(text).toHaveProperty('id');
      expect(text).toHaveProperty('reading_position');
      expect(text).toHaveProperty('annotations');
    });

    it('has correct initial values', () => {
      expect(LWT_DATA.text.id).toBe(0);
      expect(LWT_DATA.text.reading_position).toBe(-1);
      expect(LWT_DATA.text.annotations).toEqual({});
    });

    it('allows modification of text id', () => {
      const originalId = LWT_DATA.text.id;
      LWT_DATA.text.id = 100;
      expect(LWT_DATA.text.id).toBe(100);
      LWT_DATA.text.id = originalId;
    });

    it('allows modification of reading position', () => {
      const original = LWT_DATA.text.reading_position;
      LWT_DATA.text.reading_position = 50;
      expect(LWT_DATA.text.reading_position).toBe(50);
      LWT_DATA.text.reading_position = original;
    });

    it('allows adding annotations', () => {
      const original = { ...LWT_DATA.text.annotations };
      LWT_DATA.text.annotations['1'] = { term: 'test', translation: 'prueba' };
      expect(LWT_DATA.text.annotations['1']).toEqual({ term: 'test', translation: 'prueba' });
      LWT_DATA.text.annotations = original;
    });
  });

  // ===========================================================================
  // LWT_DATA.word Tests
  // ===========================================================================

  describe('LWT_DATA.word', () => {
    it('has correct initial structure', () => {
      const word = LWT_DATA.word;
      expect(word).toHaveProperty('id');
    });

    it('has correct initial values', () => {
      expect(LWT_DATA.word.id).toBe(0);
    });

    it('allows modification of word id', () => {
      const originalId = LWT_DATA.word.id;
      LWT_DATA.word.id = 999;
      expect(LWT_DATA.word.id).toBe(999);
      LWT_DATA.word.id = originalId;
    });
  });

  // ===========================================================================
  // LWT_DATA.test Tests
  // ===========================================================================

  describe('LWT_DATA.test', () => {
    it('has correct initial structure', () => {
      const test = LWT_DATA.test;
      expect(test).toHaveProperty('solution');
      expect(test).toHaveProperty('answer_opened');
    });

    it('has correct initial values', () => {
      expect(LWT_DATA.test.solution).toBe('');
      expect(LWT_DATA.test.answer_opened).toBe(false);
    });

    it('allows modification of solution', () => {
      const original = LWT_DATA.test.solution;
      LWT_DATA.test.solution = 'correct answer';
      expect(LWT_DATA.test.solution).toBe('correct answer');
      LWT_DATA.test.solution = original;
    });

    it('allows modification of answer_opened', () => {
      const original = LWT_DATA.test.answer_opened;
      LWT_DATA.test.answer_opened = true;
      expect(LWT_DATA.test.answer_opened).toBe(true);
      LWT_DATA.test.answer_opened = original;
    });
  });

  // ===========================================================================
  // LWT_DATA.settings Tests
  // ===========================================================================

  describe('LWT_DATA.settings', () => {
    it('has correct initial structure', () => {
      const settings = LWT_DATA.settings;
      expect(settings).toHaveProperty('jQuery_tooltip');
      expect(settings).toHaveProperty('hts');
      expect(settings).toHaveProperty('word_status_filter');
    });

    it('has correct initial values', () => {
      expect(LWT_DATA.settings.jQuery_tooltip).toBe(false);
      expect(LWT_DATA.settings.hts).toBe(0);
      expect(LWT_DATA.settings.word_status_filter).toBe('');
    });

    it('allows modification of jQuery_tooltip', () => {
      const original = LWT_DATA.settings.jQuery_tooltip;
      LWT_DATA.settings.jQuery_tooltip = true;
      expect(LWT_DATA.settings.jQuery_tooltip).toBe(true);
      LWT_DATA.settings.jQuery_tooltip = original;
    });

    it('allows modification of hts (hover text-to-speech)', () => {
      const original = LWT_DATA.settings.hts;
      LWT_DATA.settings.hts = 2;
      expect(LWT_DATA.settings.hts).toBe(2);
      LWT_DATA.settings.hts = original;
    });

    it('allows modification of word_status_filter', () => {
      const original = LWT_DATA.settings.word_status_filter;
      LWT_DATA.settings.word_status_filter = '1,2,3';
      expect(LWT_DATA.settings.word_status_filter).toBe('1,2,3');
      LWT_DATA.settings.word_status_filter = original;
    });
  });

  // ===========================================================================
  // Legacy Global Variables Tests
  // ===========================================================================

  describe('Legacy global variables', () => {
    it('WID is exported and initialized to 0', () => {
      expect(WID).toBe(0);
    });

    it('TID is exported and initialized to 0', () => {
      expect(TID).toBe(0);
    });

    it('WBLINK1 is exported and initialized to empty string', () => {
      expect(WBLINK1).toBe('');
    });

    it('WBLINK2 is exported and initialized to empty string', () => {
      expect(WBLINK2).toBe('');
    });

    it('WBLINK3 is exported and initialized to empty string', () => {
      expect(WBLINK3).toBe('');
    });

    it('RTL is exported and initialized to 0', () => {
      expect(RTL).toBe(0);
    });
  });

  // ===========================================================================
  // Type Interface Tests
  // ===========================================================================

  describe('Type interfaces', () => {
    it('LwtLanguage type is correctly structured', () => {
      const lang: LwtLanguage = {
        id: 1,
        dict_link1: 'http://dict1.com',
        dict_link2: 'http://dict2.com',
        translator_link: 'http://translator.com',
        delimiter: ',',
        word_parsing: 'regex',
        rtl: true,
        ttsVoiceApi: 'Google'
      };
      expect(lang.id).toBe(1);
      expect(lang.rtl).toBe(true);
    });

    it('LwtText type is correctly structured', () => {
      const text: LwtText = {
        id: 1,
        reading_position: 100,
        annotations: { '1': { data: 'test' } }
      };
      expect(text.id).toBe(1);
      expect(text.reading_position).toBe(100);
    });

    it('LwtWord type is correctly structured', () => {
      const word: LwtWord = {
        id: 42
      };
      expect(word.id).toBe(42);
    });

    it('LwtTest type is correctly structured', () => {
      const test: LwtTest = {
        solution: 'answer',
        answer_opened: true
      };
      expect(test.solution).toBe('answer');
      expect(test.answer_opened).toBe(true);
    });

    it('LwtSettings type is correctly structured', () => {
      const settings: LwtSettings = {
        jQuery_tooltip: true,
        hts: 2,
        word_status_filter: '1,2,3',
        annotations_mode: 1
      };
      expect(settings.jQuery_tooltip).toBe(true);
      expect(settings.hts).toBe(2);
      expect(settings.annotations_mode).toBe(1);
    });

    it('LwtDataInterface type combines all sub-types', () => {
      const data: LwtDataInterface = {
        language: {
          id: 1,
          dict_link1: '',
          dict_link2: '',
          translator_link: '',
          delimiter: '',
          word_parsing: '',
          rtl: false,
          ttsVoiceApi: ''
        },
        text: {
          id: 1,
          reading_position: 0,
          annotations: {}
        },
        word: {
          id: 1
        },
        test: {
          solution: '',
          answer_opened: false
        },
        settings: {
          jQuery_tooltip: false,
          hts: 0,
          word_status_filter: ''
        }
      };
      expect(data.language.id).toBe(1);
      expect(data.text.id).toBe(1);
      expect(data.word.id).toBe(1);
    });
  });

  // ===========================================================================
  // Integration Tests
  // ===========================================================================

  describe('Integration', () => {
    it('LWT_DATA can be used to configure a complete reading session', () => {
      // Save original state
      const originalLang = { ...LWT_DATA.language };
      const originalText = { ...LWT_DATA.text };
      const originalSettings = { ...LWT_DATA.settings };

      // Configure for a reading session
      LWT_DATA.language.id = 1;
      LWT_DATA.language.dict_link1 = 'http://dict.example.com/###';
      LWT_DATA.language.rtl = false;
      LWT_DATA.text.id = 42;
      LWT_DATA.text.reading_position = 0;
      LWT_DATA.settings.jQuery_tooltip = true;
      LWT_DATA.settings.hts = 2;

      // Verify configuration
      expect(LWT_DATA.language.id).toBe(1);
      expect(LWT_DATA.text.id).toBe(42);
      expect(LWT_DATA.settings.hts).toBe(2);

      // Restore original state
      Object.assign(LWT_DATA.language, originalLang);
      Object.assign(LWT_DATA.text, originalText);
      Object.assign(LWT_DATA.settings, originalSettings);
    });

    it('LWT_DATA annotations can store word-to-translation mappings', () => {
      const originalAnnotations = { ...LWT_DATA.text.annotations };

      // Add some annotations
      LWT_DATA.text.annotations = {
        '1': ['order1', 'wid1', 'translation1'],
        '2': ['order2', 'wid2', 'translation2'],
        '10': ['order10', 'wid10', 'translation10']
      };

      expect(Object.keys(LWT_DATA.text.annotations)).toHaveLength(3);
      expect(LWT_DATA.text.annotations['1']).toEqual(['order1', 'wid1', 'translation1']);

      // Restore
      LWT_DATA.text.annotations = originalAnnotations;
    });
  });

});
