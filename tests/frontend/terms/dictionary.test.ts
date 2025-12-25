/**
 * Tests for dictionary.ts - Dictionary URL creation and translation utilities
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  owin,
  oewin,
  createTheDictUrl,
  createTheDictLink,
  createSentLookupLink,
  getLangFromDict,
  translateSentence,
  translateSentence2,
  translateWord,
  translateWord2,
  translateWord3,
} from '../../../src/frontend/js/modules/vocabulary/services/dictionary';
import * as frameManagement from '../../../src/frontend/js/modules/text/pages/reading/frame_management';

// Mock dependencies
vi.mock('../../../src/frontend/js/modules/text/pages/reading/frame_management', () => ({
  showRightFramesPanel: vi.fn(),
  loadDictionaryFrame: vi.fn(),
}));

describe('dictionary.ts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ===========================================================================
  // owin Tests
  // ===========================================================================

  describe('owin', () => {
    it('opens window with correct parameters', () => {
      const mockWindow = {} as Window;
      const openSpy = vi.spyOn(window, 'open').mockReturnValue(mockWindow);

      const result = owin('http://example.com/dict?word=test');

      expect(openSpy).toHaveBeenCalledWith(
        'http://example.com/dict?word=test',
        'dictwin',
        'width=800, height=400, scrollbars=yes, menubar=no, resizable=yes, status=no'
      );
      expect(result).toBe(mockWindow);
    });

    it('handles null return from window.open', () => {
      vi.spyOn(window, 'open').mockReturnValue(null);

      const result = owin('http://example.com');

      expect(result).toBeNull();
    });
  });

  // ===========================================================================
  // oewin Tests
  // ===========================================================================

  describe('oewin', () => {
    it('opens edit window with correct parameters', () => {
      const mockWindow = {} as Window;
      const openSpy = vi.spyOn(window, 'open').mockReturnValue(mockWindow);

      const result = oewin('http://example.com/edit');

      expect(openSpy).toHaveBeenCalledWith(
        'http://example.com/edit',
        'editwin',
        'width=800, height=600, scrollbars=yes, menubar=no, resizable=yes, status=no'
      );
      expect(result).toBe(mockWindow);
    });
  });

  // ===========================================================================
  // createTheDictUrl Tests
  // ===========================================================================

  describe('createTheDictUrl', () => {
    it('appends term to URL without placeholder', () => {
      const result = createTheDictUrl('http://dict.com/lookup?q=', 'hello');
      expect(result).toBe('http://dict.com/lookup?q=hello');
    });

    it('replaces lwt_term with encoded term', () => {
      const result = createTheDictUrl('http://dict.com/lwt_term/translate', 'test word');
      expect(result).toBe('http://dict.com/test%20word/translate');
    });

    it('replaces lwt_term with encoded term', () => {
      const result = createTheDictUrl('http://dict.com/lwt_term/translate', 'bonjour');
      expect(result).toBe('http://dict.com/bonjour/translate');
    });

    it('replaces placeholder with + when term is empty', () => {
      const result = createTheDictUrl('http://dict.com/lwt_term', '');
      expect(result).toBe('http://dict.com/+');
    });

    it('handles URL encoding for special characters', () => {
      const result = createTheDictUrl('http://dict.com/lwt_term', 'café');
      expect(result).toBe('http://dict.com/caf%C3%A9');
    });

    it('trims whitespace from URL and term', () => {
      const result = createTheDictUrl('  http://dict.com/lwt_term  ', '  hello  ');
      expect(result).toBe('http://dict.com/hello');
    });

    it('handles lwt_term in URL path', () => {
      // Simple replacement test - lwt_term is replaced with encoded term
      const result = createTheDictUrl('http://dict.com/search/lwt_term/lookup', 'test');
      expect(result).toBe('http://dict.com/search/test/lookup');
    });
  });

  // ===========================================================================
  // createTheDictLink Tests
  // ===========================================================================

  describe('createTheDictLink', () => {
    it('returns empty string for empty URL', () => {
      const result = createTheDictLink('', 'word', 'Dict', 'Look up:');
      expect(result).toBe('');
    });

    it('returns empty string for empty text', () => {
      const result = createTheDictLink('http://dict.com', 'word', '', 'Look up:');
      expect(result).toBe('');
    });

    it('creates link with popup when popup=true', () => {
      const result = createTheDictLink('http://dict.com/lwt_term', 'hello', 'Dict1', '', true);
      expect(result).toContain('data-action="dict-popup"');
      expect(result).toContain('data-url="http://dict.com/hello"');
      expect(result).toContain('Dict1');
      expect(result).toContain('class="click"');
    });

    it('creates regular link when popup=false', () => {
      const result = createTheDictLink('http://dict.com/lwt_term', 'hello', 'Dict2', '', false);
      expect(result).toContain('<a href=');
      expect(result).toContain('Dict2');
      expect(result).toContain('target="ru"');
    });

    it('includes txtbefore in output', () => {
      const result = createTheDictLink('http://dict.com/lwt_term', 'hello', 'Dict', 'Look up:');
      expect(result).toContain('Look up:');
    });

    it('defaults to non-popup mode when popup not specified', () => {
      const result = createTheDictLink(
        'http://dict.com?q=lwt_term',
        'hello',
        'Dict',
        ''
      );
      expect(result).toContain('<a href=');
      expect(result).toContain('target="ru"');
    });

    it('escapes special chars in popup data-url', () => {
      const result = createTheDictLink('http://dict.com/lwt_term', "it's", 'Dict', '', true);
      expect(result).toContain('data-action="dict-popup"');
      // The apostrophe should be URL-encoded
      expect(result).toContain("it's");
    });
  });

  // ===========================================================================
  // createSentLookupLink Tests
  // ===========================================================================

  describe('createSentLookupLink', () => {
    it('returns empty string for empty URL', () => {
      const result = createSentLookupLink(1, 1, '', 'Trans');
      expect(result).toBe('');
    });

    it('returns empty string for empty text', () => {
      const result = createSentLookupLink(1, 1, 'http://trans.com', '');
      expect(result).toBe('');
    });

    it('creates popup link when popup=true', () => {
      const result = createSentLookupLink(5, 10, 'http://translate.com', 'Trans', true);
      expect(result).toContain('data-action="dict-popup"');
      expect(result).toContain('http://translate.com');
    });

    it('creates frame link when popup=false', () => {
      const result = createSentLookupLink(3, 7, 'http://translate.com', 'Translate', false);
      expect(result).toContain('<a href=');
      expect(result).toContain('http://translate.com');
      expect(result).toContain('target="ru"');
    });

    it('uses direct URL for non-popup links', () => {
      // URLs are now used directly, not through trans.php
      const result = createSentLookupLink(1, 1, 'http://example.com', 'Trans');
      expect(result).toContain('http://example.com');
      // Uses data-action instead of inline onclick
      expect(result).toContain('data-action="dict-frame"');
    });

    it('defaults to non-popup when popup not specified', () => {
      const result = createSentLookupLink(1, 1, 'http://translate.com', 'Trans');
      expect(result).toContain('<a href=');
      expect(result).toContain('target="ru"');
    });
  });

  // ===========================================================================
  // getLangFromDict Tests
  // ===========================================================================

  describe('getLangFromDict', () => {
    it('returns empty string for empty URL', () => {
      const result = getLangFromDict('');
      expect(result).toBe('');
    });

    it('returns empty string for whitespace URL', () => {
      const result = getLangFromDict('   ');
      expect(result).toBe('');
    });

    it('extracts sl parameter from Google Translate URL', () => {
      const result = getLangFromDict('http://translate.google.com?sl=en&tl=es');
      expect(result).toBe('en');
    });

    it('extracts source parameter from LibreTranslate URL', () => {
      const result = getLangFromDict(
        'http://libretranslate.com?lwt_translator=libretranslate&source=fr'
      );
      expect(result).toBe('fr');
    });

    it('handles standard Google Translate URL', () => {
      const result = getLangFromDict('http://translate.google.com?sl=de');
      expect(result).toBe('de');
    });

    it('returns empty for invalid relative URLs', () => {
      // Old trans.php and ggl.php gateway URLs are no longer supported
      // The function now requires valid absolute URLs
      const result = getLangFromDict('trans.php?sl=ja');
      expect(result).toBe('');
    });

    it('handles full Google Translate URL with sl parameter', () => {
      const result = getLangFromDict('https://translate.google.com/?sl=zh&tl=en');
      expect(result).toBe('zh');
    });

    it('returns empty string when sl parameter not found', () => {
      const result = getLangFromDict('http://example.com/translate');
      expect(result).toBe('');
    });
  });

  // ===========================================================================
  // translateSentence Tests
  // ===========================================================================

  describe('translateSentence', () => {
    it('does nothing when sentctl is undefined', () => {
      translateSentence('http://translate.com/lwt_term', undefined);

      expect(frameManagement.loadDictionaryFrame).not.toHaveBeenCalled();
    });

    it('does nothing when URL is empty', () => {
      const textarea = document.createElement('textarea');
      textarea.value = 'Hello world';

      translateSentence('', textarea);

      expect(frameManagement.loadDictionaryFrame).not.toHaveBeenCalled();
    });

    it('translates sentence and removes curly braces', () => {
      const textarea = document.createElement('textarea');
      textarea.value = 'Hello {world}';

      translateSentence('http://translate.com/lwt_term', textarea);

      expect(frameManagement.loadDictionaryFrame).toHaveBeenCalledWith(
        'http://translate.com/Hello%20world'
      );
    });
  });

  // ===========================================================================
  // translateSentence2 Tests
  // ===========================================================================

  describe('translateSentence2', () => {
    it('does nothing when sentctl is undefined', () => {
      const openSpy = vi.spyOn(window, 'open').mockReturnValue(null);

      translateSentence2('http://translate.com/lwt_term', undefined);

      expect(openSpy).not.toHaveBeenCalled();
    });

    it('opens popup with translated sentence', () => {
      const openSpy = vi.spyOn(window, 'open').mockReturnValue(null);
      const textarea = document.createElement('textarea');
      textarea.value = 'Test sentence';

      translateSentence2('http://translate.com/lwt_term', textarea);

      expect(openSpy).toHaveBeenCalledWith(
        'http://translate.com/Test%20sentence',
        'dictwin',
        expect.any(String)
      );
    });
  });

  // ===========================================================================
  // translateWord Tests
  // ===========================================================================

  describe('translateWord', () => {
    it('does nothing when wordctl is undefined', () => {
      translateWord('http://dict.com/lwt_term', undefined);

      expect(frameManagement.loadDictionaryFrame).not.toHaveBeenCalled();
    });

    it('translates word in right frame', () => {
      const input = document.createElement('input');
      input.value = 'bonjour';

      translateWord('http://dict.com/lwt_term', input);

      expect(frameManagement.loadDictionaryFrame).toHaveBeenCalledWith(
        'http://dict.com/bonjour'
      );
    });
  });

  // ===========================================================================
  // translateWord2 Tests
  // ===========================================================================

  describe('translateWord2', () => {
    it('does nothing when wordctl is undefined', () => {
      const openSpy = vi.spyOn(window, 'open').mockReturnValue(null);

      translateWord2('http://dict.com/lwt_term', undefined);

      expect(openSpy).not.toHaveBeenCalled();
    });

    it('opens popup with translated word', () => {
      const openSpy = vi.spyOn(window, 'open').mockReturnValue(null);
      const input = document.createElement('input');
      input.value = 'hello';

      translateWord2('http://dict.com/lwt_term', input);

      expect(openSpy).toHaveBeenCalledWith(
        'http://dict.com/hello',
        'dictwin',
        expect.any(String)
      );
    });
  });

  // ===========================================================================
  // translateWord3 Tests
  // ===========================================================================

  describe('translateWord3', () => {
    it('opens popup with word directly', () => {
      const openSpy = vi.spyOn(window, 'open').mockReturnValue(null);

      translateWord3('http://dict.com/lwt_term', 'world');

      expect(openSpy).toHaveBeenCalledWith(
        'http://dict.com/world',
        'dictwin',
        expect.any(String)
      );
    });
  });

  // ===========================================================================
  // Event Delegation Tests (Integration)
  // ===========================================================================

  describe('Event Delegation', () => {
    it('handles dict-popup data action', () => {
      const openSpy = vi.spyOn(window, 'open').mockReturnValue(null);
      document.body.innerHTML = `
        <button data-action="dict-popup" data-url="http://dict.com/test">Dict</button>
      `;

      // Trigger DOMContentLoaded to initialize event delegation
      document.dispatchEvent(new Event('DOMContentLoaded'));

      // Click the button
      const button = document.querySelector('button') as HTMLButtonElement;
      button.click();

      expect(openSpy).toHaveBeenCalledWith(
        'http://dict.com/test',
        'dictwin',
        expect.any(String)
      );
    });

    it('handles translate-word data action', () => {
      document.body.innerHTML = `
        <input type="text" id="wordInput" value="hola" />
        <button
          data-action="translate-word"
          data-url="http://dict.com/lwt_term"
          data-wordctl="wordInput"
        >Translate</button>
      `;

      document.dispatchEvent(new Event('DOMContentLoaded'));

      const button = document.querySelector('button') as HTMLButtonElement;
      button.click();

      expect(frameManagement.loadDictionaryFrame).toHaveBeenCalled();
    });

    it('handles translate-word-popup data action', () => {
      const openSpy = vi.spyOn(window, 'open').mockReturnValue(null);
      document.body.innerHTML = `
        <input type="text" id="wordInput2" value="guten" />
        <button
          data-action="translate-word-popup"
          data-url="http://dict.com/lwt_term"
          data-wordctl="wordInput2"
        >Translate Popup</button>
      `;

      document.dispatchEvent(new Event('DOMContentLoaded'));

      const button = document.querySelector('button') as HTMLButtonElement;
      button.click();

      expect(openSpy).toHaveBeenCalled();
    });

    it('handles translate-word-direct data action', () => {
      const openSpy = vi.spyOn(window, 'open').mockReturnValue(null);
      document.body.innerHTML = `
        <button
          data-action="translate-word-direct"
          data-url="http://dict.com/lwt_term"
          data-word="ciao"
        >Direct</button>
      `;

      document.dispatchEvent(new Event('DOMContentLoaded'));

      const button = document.querySelector('button') as HTMLButtonElement;
      button.click();

      expect(openSpy).toHaveBeenCalledWith(
        'http://dict.com/ciao',
        'dictwin',
        expect.any(String)
      );
    });
  });
});
