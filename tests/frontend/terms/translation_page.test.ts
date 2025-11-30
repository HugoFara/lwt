/**
 * Tests for translation_page.ts - Auto-initialization for translation pages
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import $ from 'jquery';
import { autoInitTranslationPages } from '../../../src/frontend/js/terms/translation_page';

// Mock dependencies
vi.mock('../../../src/frontend/js/core/user_interactions', () => ({
  speechDispatcher: vi.fn()
}));

vi.mock('../../../src/frontend/js/terms/translation_api', () => ({
  getGlosbeTranslation: vi.fn()
}));

import { speechDispatcher } from '../../../src/frontend/js/core/user_interactions';
import { getGlosbeTranslation } from '../../../src/frontend/js/terms/translation_api';

describe('translation_page.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // Google Translate Page Tests
  // ===========================================================================

  describe('Google Translate page initialization', () => {
    it('sets up text-to-speech click handler', () => {
      document.body.innerHTML = `
        <button id="textToSpeech">Speak</button>
        <button id="del_translation">Delete</button>
        <script data-lwt-google-translate-config type="application/json">
          {"text": "hello", "langId": 1, "hasParentFrame": false}
        </script>
      `;

      autoInitTranslationPages();

      const button = document.querySelector('#textToSpeech')!;
      $(button).trigger('click');

      expect(speechDispatcher).toHaveBeenCalledWith('hello', 1);
    });

    it('handles string langId by converting to number', () => {
      document.body.innerHTML = `
        <button id="textToSpeech">Speak</button>
        <button id="del_translation">Delete</button>
        <script data-lwt-google-translate-config type="application/json">
          {"text": "word", "langId": "5", "hasParentFrame": false}
        </script>
      `;

      autoInitTranslationPages();

      $('#textToSpeech').trigger('click');

      expect(speechDispatcher).toHaveBeenCalledWith('word', 5);
    });

    it('removes delete button when not in frame context', () => {
      document.body.innerHTML = `
        <button id="textToSpeech">Speak</button>
        <button id="del_translation">Delete</button>
        <script data-lwt-google-translate-config type="application/json">
          {"text": "test", "langId": 1, "hasParentFrame": false}
        </script>
      `;

      autoInitTranslationPages();

      expect(document.querySelector('#del_translation')).toBeNull();
    });

    it('handles missing config element gracefully', () => {
      document.body.innerHTML = '<div>No config here</div>';

      expect(() => autoInitTranslationPages()).not.toThrow();
    });

    it('handles invalid JSON gracefully', () => {
      document.body.innerHTML = `
        <script data-lwt-google-translate-config type="application/json">
          not valid json
        </script>
      `;

      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      autoInitTranslationPages();

      expect(consoleSpy).toHaveBeenCalledWith(
        'Failed to parse Google Translate config:',
        expect.any(Error)
      );
    });
  });

  // ===========================================================================
  // Glosbe Page Tests
  // ===========================================================================

  describe('Glosbe page initialization', () => {
    it('triggers translation lookup', () => {
      document.body.innerHTML = `
        <button id="del_translation">Delete</button>
        <script data-lwt-glosbe-config type="application/json">
          {"phrase": "hola", "from": "es", "dest": "en", "hasParentFrame": false}
        </script>
      `;

      autoInitTranslationPages();

      expect(getGlosbeTranslation).toHaveBeenCalledWith('hola', 'es', 'en');
    });

    it('removes delete button when not in frame context', () => {
      document.body.innerHTML = `
        <button id="del_translation">Delete</button>
        <script data-lwt-glosbe-config type="application/json">
          {"phrase": "test", "from": "en", "dest": "de", "hasParentFrame": false}
        </script>
      `;

      autoInitTranslationPages();

      expect(document.querySelector('#del_translation')).toBeNull();
    });

    it('displays empty term error message', () => {
      document.body.innerHTML = `
        <script data-lwt-glosbe-config type="application/json">
          {"phrase": "", "from": "en", "dest": "de", "hasParentFrame": false, "error": "empty_term"}
        </script>
      `;

      autoInitTranslationPages();

      expect(document.body.innerHTML).toContain('Term is not set!');
      expect(document.body.innerHTML).toContain('msgblue');
      expect(getGlosbeTranslation).not.toHaveBeenCalled();
    });

    it('displays API error message', () => {
      document.body.innerHTML = `
        <script data-lwt-glosbe-config type="application/json">
          {"phrase": "", "from": "en", "dest": "de", "hasParentFrame": false, "error": "api_error"}
        </script>
      `;

      autoInitTranslationPages();

      expect(document.body.innerHTML).toContain('something wrong with the Glosbe API');
      expect(document.body.innerHTML).toContain('red');
      expect(getGlosbeTranslation).not.toHaveBeenCalled();
    });

    it('handles invalid Glosbe JSON gracefully', () => {
      document.body.innerHTML = `
        <script data-lwt-glosbe-config type="application/json">
          {invalid}
        </script>
      `;

      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      autoInitTranslationPages();

      expect(consoleSpy).toHaveBeenCalledWith(
        'Failed to parse Glosbe config:',
        expect.any(Error)
      );
    });
  });

  // ===========================================================================
  // Multiple Config Elements Tests
  // ===========================================================================

  describe('multiple config elements', () => {
    it('handles both Google and Glosbe configs on same page', () => {
      document.body.innerHTML = `
        <button id="textToSpeech">Speak</button>
        <button id="del_translation">Delete</button>
        <script data-lwt-google-translate-config type="application/json">
          {"text": "hello", "langId": 1, "hasParentFrame": false}
        </script>
        <script data-lwt-glosbe-config type="application/json">
          {"phrase": "hola", "from": "es", "dest": "en", "hasParentFrame": false}
        </script>
      `;

      autoInitTranslationPages();

      // Both should be processed
      $('#textToSpeech').trigger('click');
      expect(speechDispatcher).toHaveBeenCalled();
      expect(getGlosbeTranslation).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // Edge Cases Tests
  // ===========================================================================

  describe('edge cases', () => {
    it('handles empty config object', () => {
      document.body.innerHTML = `
        <script data-lwt-google-translate-config type="application/json">
          {}
        </script>
      `;

      expect(() => autoInitTranslationPages()).not.toThrow();
    });

    it('handles missing #textToSpeech element', () => {
      document.body.innerHTML = `
        <script data-lwt-google-translate-config type="application/json">
          {"text": "hello", "langId": 1, "hasParentFrame": false}
        </script>
      `;

      expect(() => autoInitTranslationPages()).not.toThrow();
    });

    it('handles missing #del_translation element', () => {
      document.body.innerHTML = `
        <script data-lwt-google-translate-config type="application/json">
          {"text": "hello", "langId": 1, "hasParentFrame": false}
        </script>
      `;

      expect(() => autoInitTranslationPages()).not.toThrow();
    });

    it('handles special characters in phrase', () => {
      document.body.innerHTML = `
        <script data-lwt-glosbe-config type="application/json">
          {"phrase": "hola & adiós", "from": "es", "dest": "en", "hasParentFrame": false}
        </script>
      `;

      autoInitTranslationPages();

      expect(getGlosbeTranslation).toHaveBeenCalledWith('hola & adiós', 'es', 'en');
    });
  });
});
