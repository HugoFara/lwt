/**
 * Tests for word_form_auto.ts - Word Form Auto-translate and Auto-romanization
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  autoTranslate,
  autoRomanization,
  initWordFormAuto,
  type WordFormConfig
} from '../../../src/frontend/js/forms/word_form_auto';

// Mock dependencies
vi.mock('../../../src/frontend/js/terms/translation_api', () => ({
  getLibreTranslateTranslation: vi.fn()
}));

vi.mock('../../../src/frontend/js/core/user_interactions', () => ({
  getPhoneticTextAsync: vi.fn()
}));

vi.mock('../../../src/frontend/js/terms/dictionary', () => ({
  getLangFromDict: vi.fn()
}));

import { getLibreTranslateTranslation } from '../../../src/frontend/js/terms/translation_api';
import { getPhoneticTextAsync } from '../../../src/frontend/js/core/user_interactions';
import { getLangFromDict } from '../../../src/frontend/js/terms/dictionary';

describe('word_form_auto.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // autoTranslate Tests
  // ===========================================================================

  describe('autoTranslate', () => {
    it('does nothing when transUri is empty', async () => {
      const config: WordFormConfig = {
        transUri: '',
        langShort: 'en',
        lang: 1
      };

      await autoTranslate(config);

      expect(getLibreTranslateTranslation).not.toHaveBeenCalled();
    });

    it('does nothing when transUri is not LibreTranslate', async () => {
      const config: WordFormConfig = {
        transUri: 'https://translate.google.com/?q=test',
        langShort: 'en',
        lang: 1
      };

      await autoTranslate(config);

      expect(getLibreTranslateTranslation).not.toHaveBeenCalled();
    });

    it('calls LibreTranslate when URL has lwt_translator=libretranslate', async () => {
      document.body.innerHTML = `
        <input id="wordfield" value="Bonjour" />
        <form name="newword">
          <textarea name="WoTranslation"></textarea>
        </form>
      `;

      (getLibreTranslateTranslation as any).mockResolvedValue('Hello');

      const config: WordFormConfig = {
        transUri: 'http://localhost:5000/?lwt_translator=libretranslate&source=fr&target=en&q=lwt_term',
        langShort: 'fr',
        lang: 1
      };

      await autoTranslate(config);

      expect(getLibreTranslateTranslation).toHaveBeenCalled();

      const translationField = document.querySelector('textarea[name="WoTranslation"]') as HTMLTextAreaElement;
      expect(translationField.value).toBe('Hello');
    });

    it('uses langShort as source when source param not in URL', async () => {
      document.body.innerHTML = `
        <input id="wordfield" value="Test" />
        <form name="newword">
          <textarea name="WoTranslation"></textarea>
        </form>
      `;

      (getLibreTranslateTranslation as any).mockResolvedValue('Translated');

      const config: WordFormConfig = {
        transUri: 'http://localhost:5000/?lwt_translator=libretranslate&target=en&q=lwt_term',
        langShort: 'de',
        lang: 2
      };

      await autoTranslate(config);

      expect(getLibreTranslateTranslation).toHaveBeenCalledWith(
        expect.any(URL),
        'Test',
        'de',  // Should use langShort
        'en'
      );
    });

    it('does nothing when term is empty', async () => {
      document.body.innerHTML = `
        <input id="wordfield" value="" />
        <form name="newword">
          <textarea name="WoTranslation"></textarea>
        </form>
      `;

      const config: WordFormConfig = {
        transUri: 'http://localhost:5000/?lwt_translator=libretranslate&source=fr&target=en&q=lwt_term',
        langShort: 'fr',
        lang: 1
      };

      await autoTranslate(config);

      expect(getLibreTranslateTranslation).not.toHaveBeenCalled();
    });

    it('logs warning when target language not configured', async () => {
      document.body.innerHTML = `
        <input id="wordfield" value="Test" />
        <form name="newword">
          <textarea name="WoTranslation"></textarea>
        </form>
      `;

      const consoleSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});

      const config: WordFormConfig = {
        transUri: 'http://localhost:5000/?lwt_translator=libretranslate&source=fr',  // Missing target
        langShort: 'fr',
        lang: 1
      };

      await autoTranslate(config);

      expect(consoleSpy).toHaveBeenCalledWith('LibreTranslate target language not configured');
      expect(getLibreTranslateTranslation).not.toHaveBeenCalled();
    });

    it('handles translation API errors gracefully', async () => {
      document.body.innerHTML = `
        <input id="wordfield" value="Test" />
        <form name="newword">
          <textarea name="WoTranslation"></textarea>
        </form>
      `;

      (getLibreTranslateTranslation as any).mockRejectedValue(new Error('Network error'));
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      const config: WordFormConfig = {
        transUri: 'http://localhost:5000/?lwt_translator=libretranslate&source=fr&target=en',
        langShort: 'fr',
        lang: 1
      };

      await autoTranslate(config);

      expect(consoleSpy).toHaveBeenCalled();
    });

    it('handles missing wordfield element', async () => {
      document.body.innerHTML = `
        <form name="newword">
          <textarea name="WoTranslation"></textarea>
        </form>
      `;

      const config: WordFormConfig = {
        transUri: 'http://localhost:5000/?lwt_translator=libretranslate&source=fr&target=en',
        langShort: 'fr',
        lang: 1
      };

      await autoTranslate(config);

      expect(getLibreTranslateTranslation).not.toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // autoRomanization Tests
  // ===========================================================================

  describe('autoRomanization', () => {
    it('calls phonetic API with term and language ID', async () => {
      document.body.innerHTML = `
        <input id="wordfield" value="日本語" />
        <form name="newword">
          <input name="WoRomanization" type="text" value="" />
        </form>
      `;

      (getPhoneticTextAsync as any).mockResolvedValue({ phonetic_reading: 'nihongo' });

      await autoRomanization(3);

      expect(getPhoneticTextAsync).toHaveBeenCalledWith('日本語', 3);

      const romanField = document.querySelector('input[name="WoRomanization"]') as HTMLInputElement;
      expect(romanField.value).toBe('nihongo');
    });

    it('does nothing when term is empty', async () => {
      document.body.innerHTML = `
        <input id="wordfield" value="" />
        <form name="newword">
          <input name="WoRomanization" type="text" value="" />
        </form>
      `;

      await autoRomanization(1);

      expect(getPhoneticTextAsync).not.toHaveBeenCalled();
    });

    it('handles API errors gracefully', async () => {
      document.body.innerHTML = `
        <input id="wordfield" value="テスト" />
        <form name="newword">
          <input name="WoRomanization" type="text" value="" />
        </form>
      `;

      (getPhoneticTextAsync as any).mockRejectedValue(new Error('API error'));
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      await autoRomanization(1);

      expect(consoleSpy).toHaveBeenCalled();
    });

    it('handles empty API response', async () => {
      document.body.innerHTML = `
        <input id="wordfield" value="Test" />
        <form name="newword">
          <input name="WoRomanization" type="text" value="" />
        </form>
      `;

      (getPhoneticTextAsync as any).mockResolvedValue(null);

      await autoRomanization(1);

      const romanField = document.querySelector('input[name="WoRomanization"]') as HTMLInputElement;
      expect(romanField.value).toBe('');
    });

    it('handles missing phonetic_reading in response', async () => {
      document.body.innerHTML = `
        <input id="wordfield" value="Test" />
        <form name="newword">
          <input name="WoRomanization" type="text" value="" />
        </form>
      `;

      (getPhoneticTextAsync as any).mockResolvedValue({ other_field: 'value' });

      await autoRomanization(1);

      const romanField = document.querySelector('input[name="WoRomanization"]') as HTMLInputElement;
      expect(romanField.value).toBe('');
    });

    it('handles missing wordfield element', async () => {
      document.body.innerHTML = `
        <form name="newword">
          <input name="WoRomanization" type="text" value="" />
        </form>
      `;

      await autoRomanization(1);

      expect(getPhoneticTextAsync).not.toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // initWordFormAuto Tests
  // ===========================================================================

  describe('initWordFormAuto', () => {
    it('does nothing when config element does not exist', () => {
      expect(() => initWordFormAuto()).not.toThrow();
    });

    it('parses config and runs auto-translate and auto-romanization', async () => {
      document.body.innerHTML = `
        <script id="word-form-config" type="application/json">
          {"transUri": "", "langShort": "en", "lang": 1}
        </script>
        <input id="wordfield" value="" />
        <form name="newword">
          <textarea name="WoTranslation"></textarea>
          <input name="WoRomanization" type="text" />
        </form>
      `;

      (getLangFromDict as any).mockReturnValue('en');

      initWordFormAuto();

      // Should have called getLangFromDict to determine language
      expect(getLangFromDict).toHaveBeenCalled();
    });

    it('uses getLangFromDict when langShort not in config', async () => {
      document.body.innerHTML = `
        <script id="word-form-config" type="application/json">
          {"transUri": "http://example.com", "langShort": "", "lang": 1}
        </script>
        <input id="wordfield" value="" />
        <form name="newword">
          <textarea name="WoTranslation"></textarea>
          <input name="WoRomanization" type="text" />
        </form>
      `;

      (getLangFromDict as any).mockReturnValue('fr');

      initWordFormAuto();

      expect(getLangFromDict).toHaveBeenCalledWith('http://example.com');
    });

    it('handles invalid JSON config gracefully', () => {
      document.body.innerHTML = `
        <script id="word-form-config" type="application/json">
          {invalid json}
        </script>
      `;

      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      expect(() => initWordFormAuto()).not.toThrow();
      expect(consoleSpy).toHaveBeenCalled();
    });

    it('handles empty config element', () => {
      document.body.innerHTML = `
        <script id="word-form-config" type="application/json"></script>
      `;

      expect(() => initWordFormAuto()).not.toThrow();
    });
  });
});
