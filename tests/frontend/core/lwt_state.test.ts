/**
 * Tests for lwt_state.ts - LWT State Management and state modules
 */
import { describe, it, expect, beforeEach } from 'vitest';
import type { LwtLanguage, LwtText, LwtWord, LwtTest, LwtSettings } from '../../../src/frontend/js/core/lwt_state';
import {
  // Language config
  initLanguageConfig,
  getLanguageConfig,
  getLanguageId,
  getDictionaryLinks,
  isRtl,
  getDelimiter,
  getTtsVoiceApi,
  setTtsVoiceApi,
  resetLanguageConfig,
  // Text config
  initTextConfig,
  getTextId,
  setTextId,
  getAnnotations,
  setAnnotations,
  hasAnnotations,
  getAnnotation,
  resetTextConfig,
  // Settings config
  initSettingsConfig,
  getHtsMode,
  isTtsOnHover,
  isTtsOnClick,
  getWordStatusFilter,
  getAnnotationsMode,
  isFrameModeEnabled,
  isApiModeEnabled,
  getSettingsConfig,
  resetSettingsConfig,
  // Reading state
  getReadingPosition,
  setReadingPosition,
  resetReadingPosition,
  hasReadingPosition,
  // Test state
  getCurrentWordId,
  setCurrentWordId,
  getTestSolution,
  setTestSolution,
  isAnswerOpened,
  setAnswerOpened,
  openAnswer,
  resetAnswer,
  resetTestState,
} from '../../../src/frontend/js/core/lwt_state';

describe('lwt_state.ts', () => {
  // ===========================================================================
  // Language Config Tests
  // ===========================================================================

  describe('Language Config', () => {
    beforeEach(() => {
      resetLanguageConfig();
    });

    it('has correct initial values', () => {
      const config = getLanguageConfig();
      expect(config.id).toBe(0);
      expect(config.dictLink1).toBe('');
      expect(config.dictLink2).toBe('');
      expect(config.translatorLink).toBe('');
      expect(config.delimiter).toBe('');
      expect(config.wordParsing).toBe('');
      expect(config.rtl).toBe(false);
      expect(config.ttsVoiceApi).toBe('');
    });

    it('initializes from config object', () => {
      initLanguageConfig({
        id: 42,
        dictLink1: 'http://dict1.com',
        dictLink2: 'http://dict2.com',
        translatorLink: 'http://translator.com',
        delimiter: ',',
        rtl: true,
        ttsVoiceApi: 'Google'
      });

      expect(getLanguageId()).toBe(42);
      expect(isRtl()).toBe(true);
      expect(getDelimiter()).toBe(',');
      expect(getTtsVoiceApi()).toBe('Google');
    });

    it('getDictionaryLinks returns all links', () => {
      initLanguageConfig({
        dictLink1: 'http://dict1.com/###',
        dictLink2: 'http://dict2.com/###',
        translatorLink: 'http://translate.com/###'
      });

      const links = getDictionaryLinks();
      expect(links.dict1).toBe('http://dict1.com/###');
      expect(links.dict2).toBe('http://dict2.com/###');
      expect(links.translator).toBe('http://translate.com/###');
    });

    it('allows setting TTS voice API', () => {
      setTtsVoiceApi('Amazon Polly');
      expect(getTtsVoiceApi()).toBe('Amazon Polly');
    });
  });

  // ===========================================================================
  // Text Config Tests
  // ===========================================================================

  describe('Text Config', () => {
    beforeEach(() => {
      resetTextConfig();
    });

    it('has correct initial values', () => {
      expect(getTextId()).toBe(0);
      expect(getAnnotations()).toBe(0);
    });

    it('initializes from config object', () => {
      initTextConfig({
        id: 100,
        annotations: { '1': ['order', 'wid', 'translation'] }
      });

      expect(getTextId()).toBe(100);
      expect(hasAnnotations()).toBe(true);
    });

    it('allows modification of text id', () => {
      setTextId(42);
      expect(getTextId()).toBe(42);
    });

    it('allows setting annotations', () => {
      setAnnotations({
        '1': ['order1', 'wid1', 'translation1'],
        '2': ['order2', 'wid2', 'translation2']
      });

      expect(hasAnnotations()).toBe(true);
      expect(getAnnotation('1')).toEqual(['order1', 'wid1', 'translation1']);
      expect(getAnnotation('2')).toEqual(['order2', 'wid2', 'translation2']);
    });

    it('returns undefined for missing annotation', () => {
      setAnnotations({ '1': ['order', 'wid', 'trans'] });
      expect(getAnnotation('999')).toBeUndefined();
    });

    it('hasAnnotations returns false for number', () => {
      setAnnotations(0);
      expect(hasAnnotations()).toBe(false);
    });
  });

  // ===========================================================================
  // Settings Config Tests
  // ===========================================================================

  describe('Settings Config', () => {
    beforeEach(() => {
      resetSettingsConfig();
    });

    it('has correct initial values', () => {
      const config = getSettingsConfig();
      expect(config.hts).toBe(0);
      expect(config.wordStatusFilter).toBe('');
      expect(config.annotationsMode).toBe(1);
      expect(config.useFrameMode).toBe(false);
    });

    it('initializes from config object', () => {
      initSettingsConfig({
        hts: 2,
        wordStatusFilter: '1,2,3',
        annotationsMode: 3,
        useFrameMode: true
      });

      expect(getHtsMode()).toBe(2);
      expect(getWordStatusFilter()).toBe('1,2,3');
      expect(getAnnotationsMode()).toBe(3);
      expect(isFrameModeEnabled()).toBe(true);
      expect(isApiModeEnabled()).toBe(false);
    });

    it('isTtsOnHover returns true when hts is 2', () => {
      initSettingsConfig({ hts: 2 });
      expect(isTtsOnHover()).toBe(true);
      expect(isTtsOnClick()).toBe(false);
    });

    it('isTtsOnClick returns true when hts is 3', () => {
      initSettingsConfig({ hts: 3 });
      expect(isTtsOnHover()).toBe(false);
      expect(isTtsOnClick()).toBe(true);
    });
  });

  // ===========================================================================
  // Reading State Tests
  // ===========================================================================

  describe('Reading State', () => {
    beforeEach(() => {
      resetReadingPosition();
    });

    it('has correct initial value', () => {
      expect(getReadingPosition()).toBe(-1);
      expect(hasReadingPosition()).toBe(false);
    });

    it('allows setting reading position', () => {
      setReadingPosition(100);
      expect(getReadingPosition()).toBe(100);
      expect(hasReadingPosition()).toBe(true);
    });

    it('resetReadingPosition sets to -1', () => {
      setReadingPosition(50);
      resetReadingPosition();
      expect(getReadingPosition()).toBe(-1);
      expect(hasReadingPosition()).toBe(false);
    });
  });

  // ===========================================================================
  // Test State Tests
  // ===========================================================================

  describe('Test State', () => {
    beforeEach(() => {
      resetTestState();
    });

    it('has correct initial values', () => {
      expect(getCurrentWordId()).toBe(0);
      expect(getTestSolution()).toBe('');
      expect(isAnswerOpened()).toBe(false);
    });

    it('allows setting word id', () => {
      setCurrentWordId(999);
      expect(getCurrentWordId()).toBe(999);
    });

    it('allows setting test solution', () => {
      setTestSolution('correct answer');
      expect(getTestSolution()).toBe('correct answer');
    });

    it('allows setting answer opened', () => {
      setAnswerOpened(true);
      expect(isAnswerOpened()).toBe(true);
    });

    it('openAnswer sets answer opened to true', () => {
      openAnswer();
      expect(isAnswerOpened()).toBe(true);
    });

    it('resetAnswer sets answer opened to false', () => {
      openAnswer();
      resetAnswer();
      expect(isAnswerOpened()).toBe(false);
    });

    it('resetTestState resets all test state', () => {
      setCurrentWordId(123);
      setTestSolution('answer');
      openAnswer();

      resetTestState();

      expect(getCurrentWordId()).toBe(0);
      expect(getTestSolution()).toBe('');
      expect(isAnswerOpened()).toBe(false);
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
        annotations: { '1': ['order1', 'wid1', 'translation1'] }
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
        hts: 2,
        word_status_filter: '1,2,3',
        annotations_mode: 1
      };
      expect(settings.hts).toBe(2);
      expect(settings.annotations_mode).toBe(1);
    });
  });

  // ===========================================================================
  // Integration Tests
  // ===========================================================================

  describe('Integration', () => {
    beforeEach(() => {
      resetLanguageConfig();
      resetTextConfig();
      resetSettingsConfig();
      resetReadingPosition();
      resetTestState();
    });

    it('can configure a complete reading session', () => {
      // Configure for a reading session
      initLanguageConfig({
        id: 1,
        dictLink1: 'http://dict.example.com/###',
        rtl: false
      });
      initTextConfig({ id: 42 });
      initSettingsConfig({ hts: 2 });
      setReadingPosition(0);

      // Verify configuration
      expect(getLanguageId()).toBe(1);
      expect(getTextId()).toBe(42);
      expect(getHtsMode()).toBe(2);
      expect(getReadingPosition()).toBe(0);
    });

    it('annotations can store word-to-translation mappings', () => {
      setAnnotations({
        '1': ['order1', 'wid1', 'translation1'],
        '2': ['order2', 'wid2', 'translation2'],
        '10': ['order10', 'wid10', 'translation10']
      });

      expect(hasAnnotations()).toBe(true);
      expect(getAnnotation('1')).toEqual(['order1', 'wid1', 'translation1']);
      expect(getAnnotation('2')).toEqual(['order2', 'wid2', 'translation2']);
      expect(getAnnotation('10')).toEqual(['order10', 'wid10', 'translation10']);
    });
  });
});
