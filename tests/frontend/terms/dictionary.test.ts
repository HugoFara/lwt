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
} from '../../../src/frontend/js/terms/dictionary';
import * as frameManagement from '../../../src/frontend/js/reading/frame_management';

// Mock dependencies
vi.mock('../../../src/frontend/js/reading/frame_management', () => ({
  showRightFrames: vi.fn(),
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

    it('replaces ### with encoded term', () => {
      const result = createTheDictUrl('http://dict.com/###/translate', 'test word');
      expect(result).toBe('http://dict.com/test%20word/translate');
    });

    it('replaces lwt_term with encoded term', () => {
      const result = createTheDictUrl('http://dict.com/lwt_term/translate', 'bonjour');
      expect(result).toBe('http://dict.com/bonjour/translate');
    });

    it('replaces placeholder with + when term is empty', () => {
      const result = createTheDictUrl('http://dict.com/###', '');
      expect(result).toBe('http://dict.com/+');
    });

    it('handles URL encoding for special characters', () => {
      const result = createTheDictUrl('http://dict.com/###', 'café');
      expect(result).toBe('http://dict.com/caf%C3%A9');
    });

    it('trims whitespace from URL and term', () => {
      const result = createTheDictUrl('  http://dict.com/###  ', '  hello  ');
      expect(result).toBe('http://dict.com/hello');
    });

    it('handles double ### for deprecated encoding format', () => {
      const consoleSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});

      const result = createTheDictUrl('http://dict.com/###UTF-8###/lookup', 'test');

      expect(consoleSpy).toHaveBeenCalledWith(expect.stringContaining('UTF-8'));
      expect(result).toContain('test');
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

    it('creates link with popup for URL starting with *', () => {
      const result = createTheDictLink('*http://dict.com/###', 'hello', 'Dict1', '');
      expect(result).toContain('owin(');
      expect(result).toContain('Dict1');
      expect(result).toContain('class="click"');
    });

    it('creates regular link for non-popup URL', () => {
      const result = createTheDictLink('http://dict.com/###', 'hello', 'Dict2', '');
      expect(result).toContain('<a href=');
      expect(result).toContain('Dict2');
      expect(result).toContain('target="ru"');
    });

    it('includes txtbefore in output', () => {
      const result = createTheDictLink('http://dict.com/###', 'hello', 'Dict', 'Look up:');
      expect(result).toContain('Look up:');
    });

    it('detects lwt_popup parameter for popup mode', () => {
      const result = createTheDictLink(
        'http://dict.com?lwt_popup=1&q=###',
        'hello',
        'Dict',
        ''
      );
      expect(result).toContain('owin(');
    });

    it('escapes apostrophes in popup onclick', () => {
      const result = createTheDictLink('*http://dict.com/###', "it's", 'Dict', '');
      expect(result).toContain('owin(');
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

    it('creates popup link for URL starting with *', () => {
      const result = createSentLookupLink(5, 10, '*http://translate.com', 'Trans');
      expect(result).toContain('owin(');
      expect(result).toContain('trans.php?x=1&i=5&t=10');
    });

    it('creates frame link for external URL without *', () => {
      const result = createSentLookupLink(3, 7, 'http://translate.com', 'Translate');
      expect(result).toContain('<a href=');
      expect(result).toContain('trans.php?x=1&i=3&t=7');
      expect(result).toContain('target="ru"');
    });

    it('returns empty for non-external, non-popup URL', () => {
      // An invalid URL that's neither external nor popup
      // Actually, this returns empty because the URL parser throws
      const result = createSentLookupLink(1, 1, 'notaurl', 'Trans');
      expect(result).toBe('');
    });

    it('detects lwt_popup parameter', () => {
      const result = createSentLookupLink(
        1, 1, 'http://translate.com?lwt_popup=1', 'Trans'
      );
      expect(result).toContain('owin(');
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

    it('handles URL starting with *', () => {
      const result = getLangFromDict('*http://translate.google.com?sl=de');
      expect(result).toBe('de');
    });

    it('handles trans.php URL prefix', () => {
      const result = getLangFromDict('trans.php?sl=ja');
      expect(result).toBe('ja');
    });

    it('handles ggl.php URL prefix', () => {
      const result = getLangFromDict('ggl.php?sl=zh');
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
      translateSentence('http://translate.com/###', undefined);

      expect(frameManagement.showRightFrames).not.toHaveBeenCalled();
    });

    it('does nothing when URL is empty', () => {
      const textarea = document.createElement('textarea');
      textarea.value = 'Hello world';

      translateSentence('', textarea);

      expect(frameManagement.showRightFrames).not.toHaveBeenCalled();
    });

    it('translates sentence and removes curly braces', () => {
      const textarea = document.createElement('textarea');
      textarea.value = 'Hello {world}';

      translateSentence('http://translate.com/###', textarea);

      expect(frameManagement.showRightFrames).toHaveBeenCalledWith(
        undefined,
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

      translateSentence2('http://translate.com/###', undefined);

      expect(openSpy).not.toHaveBeenCalled();
    });

    it('opens popup with translated sentence', () => {
      const openSpy = vi.spyOn(window, 'open').mockReturnValue(null);
      const textarea = document.createElement('textarea');
      textarea.value = 'Test sentence';

      translateSentence2('http://translate.com/###', textarea);

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
      translateWord('http://dict.com/###', undefined);

      expect(frameManagement.showRightFrames).not.toHaveBeenCalled();
    });

    it('translates word in right frame', () => {
      const input = document.createElement('input');
      input.value = 'bonjour';

      translateWord('http://dict.com/###', input);

      expect(frameManagement.showRightFrames).toHaveBeenCalledWith(
        undefined,
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

      translateWord2('http://dict.com/###', undefined);

      expect(openSpy).not.toHaveBeenCalled();
    });

    it('opens popup with translated word', () => {
      const openSpy = vi.spyOn(window, 'open').mockReturnValue(null);
      const input = document.createElement('input');
      input.value = 'hello';

      translateWord2('http://dict.com/###', input);

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

      translateWord3('http://dict.com/###', 'world');

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
          data-url="http://dict.com/###"
          data-wordctl="wordInput"
        >Translate</button>
      `;

      document.dispatchEvent(new Event('DOMContentLoaded'));

      const button = document.querySelector('button') as HTMLButtonElement;
      button.click();

      expect(frameManagement.showRightFrames).toHaveBeenCalled();
    });

    it('handles translate-word-popup data action', () => {
      const openSpy = vi.spyOn(window, 'open').mockReturnValue(null);
      document.body.innerHTML = `
        <input type="text" id="wordInput2" value="guten" />
        <button
          data-action="translate-word-popup"
          data-url="http://dict.com/###"
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
          data-url="http://dict.com/###"
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
