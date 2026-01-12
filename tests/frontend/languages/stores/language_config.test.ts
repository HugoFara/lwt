/**
 * Tests for language_config.ts - Language configuration module.
 */
import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import {
  initLanguageConfig,
  initLanguageConfigFromDOM,
  getLanguageConfig,
  getLanguageId,
  getDictionaryLinks,
  isRtl,
  getDelimiter,
  getTtsVoiceApi,
  getSourceLang,
  setTtsVoiceApi,
  setDictionaryLinks,
  isLanguageConfigInitialized,
  resetLanguageConfig
} from '../../../../src/frontend/js/modules/language/stores/language_config';

describe('language_config.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    resetLanguageConfig();
  });

  afterEach(() => {
    document.body.innerHTML = '';
    resetLanguageConfig();
  });

  describe('initLanguageConfig', () => {
    it('initializes with partial config', () => {
      initLanguageConfig({
        id: 5,
        dictLink1: 'https://dict1.example.com'
      });

      const config = getLanguageConfig();
      expect(config.id).toBe(5);
      expect(config.dictLink1).toBe('https://dict1.example.com');
      expect(config.dictLink2).toBe('');
      expect(config.rtl).toBe(false);
    });

    it('initializes with full config', () => {
      initLanguageConfig({
        id: 10,
        name: 'German',
        dictLink1: 'https://dict1.com',
        dictLink2: 'https://dict2.com',
        translatorLink: 'https://translator.com',
        delimiter: '/',
        wordParsing: 1,
        rtl: false,
        ttsVoiceApi: 'Google',
        sourceLang: 'de'
      });

      const config = getLanguageConfig();
      expect(config.id).toBe(10);
      expect(config.name).toBe('German');
      expect(config.dictLink1).toBe('https://dict1.com');
      expect(config.dictLink2).toBe('https://dict2.com');
      expect(config.translatorLink).toBe('https://translator.com');
      expect(config.delimiter).toBe('/');
      expect(config.wordParsing).toBe(1);
      expect(config.rtl).toBe(false);
      expect(config.ttsVoiceApi).toBe('Google');
      expect(config.sourceLang).toBe('de');
    });

    it('sets isInitialized to true', () => {
      expect(isLanguageConfigInitialized()).toBe(false);

      initLanguageConfig({ id: 1 });

      expect(isLanguageConfigInitialized()).toBe(true);
    });

    it('overwrites previous config', () => {
      initLanguageConfig({ id: 1, dictLink1: 'first' });
      initLanguageConfig({ id: 2, dictLink1: 'second' });

      const config = getLanguageConfig();
      expect(config.id).toBe(2);
      expect(config.dictLink1).toBe('second');
    });
  });

  describe('initLanguageConfigFromDOM', () => {
    it('does nothing when #thetext element does not exist', () => {
      document.body.innerHTML = '<div id="other"></div>';

      initLanguageConfigFromDOM();

      expect(isLanguageConfigInitialized()).toBe(false);
    });

    it('initializes from data attributes on #thetext', () => {
      document.body.innerHTML = `
        <div id="thetext"
          data-lang-id="5"
          data-dict-link1="https://dict1.test"
          data-dict-link2="https://dict2.test"
          data-translator-link="https://translate.test"
          data-delimiter="|"
          data-rtl="true"
          data-tts-voice-api="WebSpeech">
        </div>
      `;

      initLanguageConfigFromDOM();

      const config = getLanguageConfig();
      expect(config.id).toBe(5);
      expect(config.dictLink1).toBe('https://dict1.test');
      expect(config.dictLink2).toBe('https://dict2.test');
      expect(config.translatorLink).toBe('https://translate.test');
      expect(config.delimiter).toBe('|');
      expect(config.rtl).toBe(true);
      expect(config.ttsVoiceApi).toBe('WebSpeech');
    });

    it('handles rtl="1" as true', () => {
      document.body.innerHTML = `
        <div id="thetext" data-rtl="1"></div>
      `;

      initLanguageConfigFromDOM();

      expect(isRtl()).toBe(true);
    });

    it('handles rtl="false" as false', () => {
      document.body.innerHTML = `
        <div id="thetext" data-rtl="false"></div>
      `;

      initLanguageConfigFromDOM();

      expect(isRtl()).toBe(false);
    });

    it('handles rtl="0" as false', () => {
      document.body.innerHTML = `
        <div id="thetext" data-rtl="0"></div>
      `;

      initLanguageConfigFromDOM();

      expect(isRtl()).toBe(false);
    });

    it('handles partial data attributes', () => {
      document.body.innerHTML = `
        <div id="thetext" data-lang-id="3"></div>
      `;

      initLanguageConfigFromDOM();

      const config = getLanguageConfig();
      expect(config.id).toBe(3);
      expect(config.dictLink1).toBe('');
      expect(config.dictLink2).toBe('');
    });

    it('parses language ID as integer', () => {
      document.body.innerHTML = `
        <div id="thetext" data-lang-id="42"></div>
      `;

      initLanguageConfigFromDOM();

      expect(getLanguageId()).toBe(42);
      expect(typeof getLanguageId()).toBe('number');
    });

    it('sets isInitialized when #thetext exists with data', () => {
      document.body.innerHTML = `
        <div id="thetext" data-lang-id="1"></div>
      `;

      initLanguageConfigFromDOM();

      expect(isLanguageConfigInitialized()).toBe(true);
    });
  });

  describe('getLanguageConfig', () => {
    it('returns a copy of config (immutable)', () => {
      initLanguageConfig({ id: 1 });

      const config1 = getLanguageConfig();
      const config2 = getLanguageConfig();

      expect(config1).not.toBe(config2);
      expect(config1).toEqual(config2);
    });

    it('returns default config when not initialized', () => {
      const config = getLanguageConfig();

      expect(config.id).toBe(0);
      expect(config.dictLink1).toBe('');
      expect(config.rtl).toBe(false);
    });
  });

  describe('getLanguageId', () => {
    it('returns language ID', () => {
      initLanguageConfig({ id: 15 });

      expect(getLanguageId()).toBe(15);
    });

    it('returns 0 when not initialized', () => {
      expect(getLanguageId()).toBe(0);
    });
  });

  describe('getDictionaryLinks', () => {
    it('returns dictionary links object', () => {
      initLanguageConfig({
        dictLink1: 'https://dict1.com',
        dictLink2: 'https://dict2.com',
        translatorLink: 'https://translate.com'
      });

      const links = getDictionaryLinks();

      expect(links.dict1).toBe('https://dict1.com');
      expect(links.dict2).toBe('https://dict2.com');
      expect(links.translator).toBe('https://translate.com');
    });

    it('returns empty strings when not initialized', () => {
      const links = getDictionaryLinks();

      expect(links.dict1).toBe('');
      expect(links.dict2).toBe('');
      expect(links.translator).toBe('');
    });
  });

  describe('isRtl', () => {
    it('returns true for RTL language', () => {
      initLanguageConfig({ rtl: true });

      expect(isRtl()).toBe(true);
    });

    it('returns false for LTR language', () => {
      initLanguageConfig({ rtl: false });

      expect(isRtl()).toBe(false);
    });

    it('returns false when not initialized', () => {
      expect(isRtl()).toBe(false);
    });
  });

  describe('getDelimiter', () => {
    it('returns delimiter', () => {
      initLanguageConfig({ delimiter: ';' });

      expect(getDelimiter()).toBe(';');
    });

    it('returns empty string when not initialized', () => {
      expect(getDelimiter()).toBe('');
    });
  });

  describe('getTtsVoiceApi', () => {
    it('returns TTS voice API', () => {
      initLanguageConfig({ ttsVoiceApi: 'ResponsiveVoice' });

      expect(getTtsVoiceApi()).toBe('ResponsiveVoice');
    });

    it('returns empty string when not initialized', () => {
      expect(getTtsVoiceApi()).toBe('');
    });
  });

  describe('getSourceLang', () => {
    it('returns source language code', () => {
      initLanguageConfig({ sourceLang: 'fr' });

      expect(getSourceLang()).toBe('fr');
    });

    it('returns undefined when not set', () => {
      initLanguageConfig({ id: 1 });

      expect(getSourceLang()).toBeUndefined();
    });
  });

  describe('setTtsVoiceApi', () => {
    it('sets TTS voice API', () => {
      setTtsVoiceApi('Google');

      expect(getTtsVoiceApi()).toBe('Google');
    });

    it('sets isInitialized to true', () => {
      expect(isLanguageConfigInitialized()).toBe(false);

      setTtsVoiceApi('WebSpeech');

      expect(isLanguageConfigInitialized()).toBe(true);
    });

    it('overwrites existing value', () => {
      initLanguageConfig({ ttsVoiceApi: 'Old' });

      setTtsVoiceApi('New');

      expect(getTtsVoiceApi()).toBe('New');
    });
  });

  describe('setDictionaryLinks', () => {
    it('sets all dictionary links', () => {
      setDictionaryLinks({
        dict1: 'https://new-dict1.com',
        dict2: 'https://new-dict2.com',
        translator: 'https://new-translate.com'
      });

      const links = getDictionaryLinks();
      expect(links.dict1).toBe('https://new-dict1.com');
      expect(links.dict2).toBe('https://new-dict2.com');
      expect(links.translator).toBe('https://new-translate.com');
    });

    it('sets only dict1 when provided', () => {
      initLanguageConfig({
        dictLink1: 'original1',
        dictLink2: 'original2',
        translatorLink: 'originalT'
      });

      setDictionaryLinks({ dict1: 'new1' });

      const links = getDictionaryLinks();
      expect(links.dict1).toBe('new1');
      expect(links.dict2).toBe('original2');
      expect(links.translator).toBe('originalT');
    });

    it('sets only dict2 when provided', () => {
      initLanguageConfig({
        dictLink1: 'original1',
        dictLink2: 'original2'
      });

      setDictionaryLinks({ dict2: 'new2' });

      const links = getDictionaryLinks();
      expect(links.dict1).toBe('original1');
      expect(links.dict2).toBe('new2');
    });

    it('sets only translator when provided', () => {
      initLanguageConfig({ translatorLink: 'original' });

      setDictionaryLinks({ translator: 'newT' });

      const links = getDictionaryLinks();
      expect(links.translator).toBe('newT');
    });

    it('sets isInitialized to true', () => {
      expect(isLanguageConfigInitialized()).toBe(false);

      setDictionaryLinks({ dict1: 'test' });

      expect(isLanguageConfigInitialized()).toBe(true);
    });

    it('does not change values for undefined properties', () => {
      initLanguageConfig({
        dictLink1: 'keep1',
        dictLink2: 'keep2',
        translatorLink: 'keepT'
      });

      setDictionaryLinks({});

      const links = getDictionaryLinks();
      expect(links.dict1).toBe('keep1');
      expect(links.dict2).toBe('keep2');
      expect(links.translator).toBe('keepT');
    });
  });

  describe('isLanguageConfigInitialized', () => {
    it('returns false initially', () => {
      expect(isLanguageConfigInitialized()).toBe(false);
    });

    it('returns true after initLanguageConfig', () => {
      initLanguageConfig({});

      expect(isLanguageConfigInitialized()).toBe(true);
    });

    it('returns false after reset', () => {
      initLanguageConfig({ id: 1 });
      resetLanguageConfig();

      expect(isLanguageConfigInitialized()).toBe(false);
    });
  });

  describe('resetLanguageConfig', () => {
    it('resets all values to defaults', () => {
      initLanguageConfig({
        id: 99,
        dictLink1: 'test',
        rtl: true,
        ttsVoiceApi: 'Test'
      });

      resetLanguageConfig();

      const config = getLanguageConfig();
      expect(config.id).toBe(0);
      expect(config.dictLink1).toBe('');
      expect(config.rtl).toBe(false);
      expect(config.ttsVoiceApi).toBe('');
    });

    it('resets isInitialized to false', () => {
      initLanguageConfig({ id: 1 });
      expect(isLanguageConfigInitialized()).toBe(true);

      resetLanguageConfig();

      expect(isLanguageConfigInitialized()).toBe(false);
    });
  });
});
