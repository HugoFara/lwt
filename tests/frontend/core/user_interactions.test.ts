/**
 * Tests for user_interactions.ts - User interaction functions
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import $ from 'jquery';
import {
  quickMenuRedirection,
  newExpressionInteractable,
  goToLastPosition,
  saveReadingPosition,
  saveAudioPosition,
  getPhoneticTextAsync,
  deepReplace,
  deepFindValue,
  readTextWithExternal,
  cookieTTSSettings,
  readRawTextAloud,
  readTextAloud,
  handleReadingConfiguration,
  speechDispatcher,
} from '../../../src/frontend/js/core/user_interactions';
import * as cookies from '../../../src/frontend/js/core/cookies';

// Mock SpeechSynthesisUtterance for jsdom environment
class MockSpeechSynthesisUtterance {
  text = '';
  lang = '';
  rate = 1;
  pitch = 1;
  voice: { name: string } | null = null;
}

// Make it available globally
(globalThis as unknown as Record<string, unknown>).SpeechSynthesisUtterance =
  MockSpeechSynthesisUtterance;

describe('user_interactions.ts', () => {
  beforeEach(() => {
    // Make jQuery global for tests that need it
    (globalThis as unknown as Record<string, unknown>).$ = $;
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ===========================================================================
  // Quick Menu Redirection Tests
  // ===========================================================================

  describe('quickMenuRedirection', () => {
    beforeEach(() => {
      // Create a quickmenu element
      const quickmenu = document.createElement('select');
      quickmenu.id = 'quickmenu';
      quickmenu.innerHTML = '<option value="">Select</option>';
      document.body.appendChild(quickmenu);

      // Mock top.location
      Object.defineProperty(window, 'top', {
        value: {
          location: {
            href: '',
          },
        },
        writable: true,
      });
    });

    afterEach(() => {
      document.body.innerHTML = '';
    });

    it('does nothing for empty value', () => {
      const initialHref = window.top!.location.href;
      quickMenuRedirection('');
      expect(window.top!.location.href).toBe(initialHref);
    });

    it('redirects to info.html for INFO value', () => {
      quickMenuRedirection('INFO');
      expect(window.top!.location.href).toBe('docs/info.html');
    });

    it('redirects to feeds page for rss_import value', () => {
      quickMenuRedirection('rss_import');
      expect(window.top!.location.href).toBe('do_feeds.php?check_autoupdate=1');
    });

    it('redirects to .php page for other values', () => {
      quickMenuRedirection('edit_texts');
      expect(window.top!.location.href).toBe('edit_texts.php');
    });

    it('resets quickmenu selectedIndex to 0', () => {
      const quickmenu = document.getElementById('quickmenu') as HTMLSelectElement;
      quickmenu.selectedIndex = 1;
      quickMenuRedirection('');
      expect(quickmenu.selectedIndex).toBe(0);
    });
  });

  // ===========================================================================
  // Deep Replace Tests
  // ===========================================================================

  describe('deepReplace', () => {
    it('replaces string values at top level', () => {
      const obj = { name: 'hello lwt_term world' };
      deepReplace(obj, 'lwt_term', 'test');
      expect(obj.name).toBe('hello test world');
    });

    it('replaces string values in nested objects', () => {
      const obj = {
        level1: {
          level2: {
            value: 'prefix_lwt_term_suffix',
          },
        },
      };
      deepReplace(obj, 'lwt_term', 'replacement');
      expect(obj.level1.level2.value).toBe('prefix_replacement_suffix');
    });

    it('handles multiple occurrences (only first is replaced)', () => {
      const obj = { text: 'lwt_term and lwt_term' };
      deepReplace(obj, 'lwt_term', 'word');
      // Note: String.replace only replaces first occurrence by default
      expect(obj.text).toBe('word and lwt_term');
    });

    it('does not modify non-matching strings', () => {
      const obj = { name: 'no match here' };
      deepReplace(obj, 'lwt_term', 'replacement');
      expect(obj.name).toBe('no match here');
    });

    it('handles arrays in objects', () => {
      const obj = {
        items: ['item1', 'lwt_term item', 'item3'],
      };
      // Note: The function doesn't handle arrays directly based on the code
      // Arrays are objects but their values have numeric keys
      deepReplace(obj, 'lwt_term', 'replaced');
      expect(obj.items[1]).toBe('replaced item');
    });

    it('handles null values', () => {
      const obj = { value: null, name: 'lwt_term' };
      deepReplace(obj as Record<string, unknown>, 'lwt_term', 'test');
      expect(obj.name).toBe('test');
      expect(obj.value).toBeNull();
    });

    it('handles empty object', () => {
      const obj = {};
      expect(() => deepReplace(obj, 'lwt_term', 'test')).not.toThrow();
    });
  });

  // ===========================================================================
  // Deep Find Value Tests
  // ===========================================================================

  describe('deepFindValue', () => {
    it('finds value at top level', () => {
      const obj = {
        audio: 'data:audio/mp3;base64,abc123',
        other: 'not data',
      };
      const result = deepFindValue(obj, 'data:');
      expect(result).toBe('data:audio/mp3;base64,abc123');
    });

    it('finds value in nested objects', () => {
      const obj = {
        response: {
          data: {
            audio_content: 'data:audio/wav;base64,xyz789',
          },
        },
      };
      const result = deepFindValue(obj, 'data:');
      expect(result).toBe('data:audio/wav;base64,xyz789');
    });

    it('returns null when not found', () => {
      const obj = {
        name: 'test',
        value: 'other',
      };
      const result = deepFindValue(obj, 'data:');
      expect(result).toBeNull();
    });

    it('returns first matching value', () => {
      const obj = {
        first: 'data:first',
        second: 'data:second',
      };
      const result = deepFindValue(obj, 'data:');
      // Should return first encountered match
      expect(result).toMatch(/^data:/);
    });

    it('handles empty object', () => {
      const result = deepFindValue({}, 'data:');
      expect(result).toBeNull();
    });

    it('handles deeply nested structures', () => {
      const obj = {
        a: {
          b: {
            c: {
              d: {
                value: 'data:deep/nested',
              },
            },
          },
        },
      };
      const result = deepFindValue(obj, 'data:');
      expect(result).toBe('data:deep/nested');
    });
  });

  // ===========================================================================
  // Cookie TTS Settings Tests
  // ===========================================================================

  describe('cookieTTSSettings', () => {
    beforeEach(() => {
      // Clear cookies
      document.cookie.split(';').forEach((c) => {
        document.cookie = c
          .replace(/^ +/, '')
          .replace(/=.*/, '=;expires=Thu, 01 Jan 1970 00:00:00 GMT');
      });
    });

    it('returns empty object when no cookies set', () => {
      const settings = cookieTTSSettings('en');
      expect(settings).toEqual({});
    });

    it('retrieves rate from cookie', () => {
      document.cookie = 'tts[enRate]=1.5';
      const settings = cookieTTSSettings('en');
      expect(settings.rate).toBe(1.5);
    });

    it('retrieves pitch from cookie', () => {
      document.cookie = 'tts[enPitch]=0.8';
      const settings = cookieTTSSettings('en');
      expect(settings.pitch).toBe(0.8);
    });

    it('retrieves voice from cookie', () => {
      document.cookie = 'tts[enVoice]=Google%20US%20English';
      const settings = cookieTTSSettings('en');
      expect(settings.voice).toBe('Google US English');
    });

    it('retrieves all settings together', () => {
      document.cookie = 'tts[frRate]=1.2';
      document.cookie = 'tts[frPitch]=1.0';
      document.cookie = 'tts[frVoice]=French%20Voice';
      const settings = cookieTTSSettings('fr');
      expect(settings).toEqual({
        rate: 1.2,
        pitch: 1.0,
        voice: 'French Voice',
      });
    });

    it('handles different languages independently', () => {
      document.cookie = 'tts[enRate]=1.0';
      document.cookie = 'tts[frRate]=1.5';

      const enSettings = cookieTTSSettings('en');
      const frSettings = cookieTTSSettings('fr');

      expect(enSettings.rate).toBe(1.0);
      expect(frSettings.rate).toBe(1.5);
    });
  });

  // ===========================================================================
  // Read Raw Text Aloud Tests
  // ===========================================================================

  describe('readRawTextAloud', () => {
    let mockSpeak: ReturnType<typeof vi.fn>;
    let mockGetVoices: ReturnType<typeof vi.fn>;

    beforeEach(() => {
      mockSpeak = vi.fn();
      mockGetVoices = vi.fn().mockReturnValue([
        { name: 'Google US English', lang: 'en-US' },
        { name: 'Google UK English', lang: 'en-GB' },
      ]);

      // Mock SpeechSynthesis
      Object.defineProperty(window, 'speechSynthesis', {
        value: {
          speak: mockSpeak,
          getVoices: mockGetVoices,
        },
        writable: true,
      });

      // Mock getCookie to return empty for TTS settings
      vi.spyOn(cookies, 'getCookie').mockReturnValue(null);
    });

    it('creates SpeechSynthesisUtterance with text', () => {
      const result = readRawTextAloud('Hello world', 'en-US');

      expect(result).toBeInstanceOf(MockSpeechSynthesisUtterance);
      expect(result.text).toBe('Hello world');
    });

    it('sets language on utterance', () => {
      const result = readRawTextAloud('Bonjour', 'fr-FR');

      expect(result.lang).toBe('fr-FR');
    });

    it('sets rate when provided', () => {
      const result = readRawTextAloud('Hello', 'en-US', 1.5);

      expect(result.rate).toBe(1.5);
    });

    it('sets pitch when provided', () => {
      const result = readRawTextAloud('Hello', 'en-US', undefined, 0.8);

      expect(result.pitch).toBe(0.8);
    });

    it('sets voice when provided and available', () => {
      const result = readRawTextAloud(
        'Hello',
        'en-US',
        undefined,
        undefined,
        'Google US English'
      );

      expect(result.voice?.name).toBe('Google US English');
    });

    it('calls speechSynthesis.speak', () => {
      readRawTextAloud('Hello', 'en-US');

      expect(mockSpeak).toHaveBeenCalledTimes(1);
      expect(mockSpeak).toHaveBeenCalledWith(
        expect.any(MockSpeechSynthesisUtterance)
      );
    });

    it('uses TTS settings from cookies when no explicit params', () => {
      vi.spyOn(cookies, 'getCookie')
        .mockReturnValueOnce('1.3') // Rate
        .mockReturnValueOnce('0.9') // Pitch
        .mockReturnValueOnce('Google UK English'); // Voice

      const result = readRawTextAloud('Hello', 'en-US');

      expect(result.rate).toBe(1.3);
      expect(result.pitch).toBe(0.9);
      expect(result.voice?.name).toBe('Google UK English');
    });

    it('prioritizes explicit params over cookie settings', () => {
      vi.spyOn(cookies, 'getCookie')
        .mockReturnValueOnce('1.3') // Rate from cookie
        .mockReturnValueOnce('0.9') // Pitch from cookie
        .mockReturnValueOnce(null); // No voice in cookie

      const result = readRawTextAloud('Hello', 'en-US', 2.0, 1.5);

      expect(result.rate).toBe(2.0);
      expect(result.pitch).toBe(1.5);
    });

    it('handles empty language string', () => {
      const result = readRawTextAloud('Hello', '');

      // Should not set lang when empty
      expect(result.lang).toBe('');
    });

    it('returns the utterance object', () => {
      const result = readRawTextAloud('Test', 'en-US');

      expect(result).toBeDefined();
      expect(result.text).toBe('Test');
    });
  });

  // ===========================================================================
  // newExpressionInteractable Tests
  // ===========================================================================

  describe('newExpressionInteractable', () => {
    beforeEach(() => {
      // Set up parent document
      Object.defineProperty(window, 'parent', {
        value: { document: document },
        writable: true,
        configurable: true,
      });
    });

    it('creates multi-word element', () => {
      document.body.innerHTML = `
        <span id="ID-1-1" data_pos="10">Word1</span>
        <span id="ID-3-1" data_pos="20">Word2</span>
      `;

      const text = { '1': 'multi word' };
      const attrs = ' class="mword status3"';

      newExpressionInteractable(text, attrs, 2, 'abc123', false);

      const mword = document.getElementById('ID-1-2');
      expect(mword).not.toBeNull();
      expect(mword?.classList.contains('mword')).toBe(true);
    });

    it('removes existing multi-word of same length', () => {
      document.body.innerHTML = `
        <span id="ID-1-2" class="mword">Old MW</span>
        <span id="ID-1-1" data_pos="10">Word1</span>
        <span id="ID-3-1" data_pos="20">Word2</span>
      `;

      const text = { '1': 'new multi word' };
      newExpressionInteractable(text, ' class="mword"', 2, 'def456', false);

      const mwords = document.querySelectorAll('#ID-1-2');
      expect(mwords.length).toBe(1);
      expect(mwords[0].textContent).toBe('new multi word');
    });

    it('sets data_order and order class on multi-word', () => {
      document.body.innerHTML = `
        <span id="ID-5-1" data_pos="50">Word</span>
      `;

      const text = { '5': 'test' };
      newExpressionInteractable(text, ' class="mword"', 2, 'ghi789', true);

      const mword = document.getElementById('ID-5-2');
      expect(mword?.getAttribute('data_order')).toBe('5');
      expect(mword?.classList.contains('order5')).toBe(true);
    });
  });

  // ===========================================================================
  // goToLastPosition Tests
  // ===========================================================================

  describe('goToLastPosition', () => {
    beforeEach(() => {
      // Mock LWT_DATA
      (window as unknown as Record<string, unknown>).LWT_DATA = {
        text: { reading_position: 0 },
      };
      vi.spyOn(window, 'focus').mockImplementation(() => {});
    });

    it('scrolls to position 0 when reading_position is 0', () => {
      document.body.innerHTML = `
        <span class="wsty" data_pos="100">Word</span>
      `;
      (window as unknown as { LWT_DATA: { text: { reading_position: number } } }).LWT_DATA.text.reading_position = 0;

      // Should not throw
      expect(() => goToLastPosition()).not.toThrow();
    });

    it('finds element at exact reading position', () => {
      document.body.innerHTML = `
        <span class="wsty" data_pos="50">First</span>
        <span class="wsty" data_pos="100">Target</span>
        <span class="wsty" data_pos="150">Last</span>
      `;
      (window as unknown as { LWT_DATA: { text: { reading_position: number } } }).LWT_DATA.text.reading_position = 100;

      expect(() => goToLastPosition()).not.toThrow();
    });

    it('finds closest element when exact position not found', () => {
      document.body.innerHTML = `
        <span class="wsty" data_pos="50">First</span>
        <span class="wsty" data_pos="150">Last</span>
      `;
      (window as unknown as { LWT_DATA: { text: { reading_position: number } } }).LWT_DATA.text.reading_position = 100;

      expect(() => goToLastPosition()).not.toThrow();
    });

    it('handles no wsty elements', () => {
      document.body.innerHTML = '<div>No words</div>';
      (window as unknown as { LWT_DATA: { text: { reading_position: number } } }).LWT_DATA.text.reading_position = 100;

      expect(() => goToLastPosition()).not.toThrow();
    });
  });

  // ===========================================================================
  // saveReadingPosition Tests
  // ===========================================================================

  describe('saveReadingPosition', () => {
    it('makes POST request to save position', () => {
      const postSpy = vi.spyOn($, 'post').mockImplementation(() => ({} as JQuery.jqXHR));

      saveReadingPosition(42, 100);

      expect(postSpy).toHaveBeenCalledWith(
        'api.php/v1/texts/42/reading-position',
        { position: 100 }
      );
    });
  });

  // ===========================================================================
  // saveAudioPosition Tests
  // ===========================================================================

  describe('saveAudioPosition', () => {
    it('makes POST request to save audio position', () => {
      const postSpy = vi.spyOn($, 'post').mockImplementation(() => ({} as JQuery.jqXHR));

      saveAudioPosition(42, 50.5);

      expect(postSpy).toHaveBeenCalledWith(
        'api.php/v1/texts/42/audio-position',
        { position: 50.5 }
      );
    });
  });

  // ===========================================================================
  // getPhoneticTextAsync Tests
  // ===========================================================================

  describe('getPhoneticTextAsync', () => {
    it('makes GET request with language string', () => {
      const getJSONSpy = vi.spyOn($, 'getJSON').mockImplementation(
        () => ({} as JQuery.jqXHR<{ phonetic_reading: string }>)
      );

      getPhoneticTextAsync('hello', 'en-US');

      expect(getJSONSpy).toHaveBeenCalledWith(
        'api.php/v1/phonetic-reading',
        { text: 'hello', lang: 'en-US' }
      );
    });

    it('makes GET request with language ID number', () => {
      const getJSONSpy = vi.spyOn($, 'getJSON').mockImplementation(
        () => ({} as JQuery.jqXHR<{ phonetic_reading: string }>)
      );

      getPhoneticTextAsync('hello', 5);

      expect(getJSONSpy).toHaveBeenCalledWith(
        'api.php/v1/phonetic-reading',
        { text: 'hello', lang_id: 5 }
      );
    });
  });

  // ===========================================================================
  // readTextWithExternal Tests
  // ===========================================================================

  describe('readTextWithExternal', () => {
    it('makes fetch request with replaced placeholders', async () => {
      const mockAudio = { play: vi.fn() };
      vi.spyOn(globalThis, 'Audio').mockImplementation(() => mockAudio as unknown as HTMLAudioElement);

      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({ audio: 'data:audio/mp3;base64,test' }),
      } as Response);

      const voiceApi = JSON.stringify({
        input: 'http://tts.example.com/speak',
        options: {
          method: 'POST',
          body: { text: 'lwt_term', language: 'lwt_lang' },
        },
      });

      readTextWithExternal('hello', voiceApi, 'en');

      await new Promise(resolve => setTimeout(resolve, 10));

      expect(fetchSpy).toHaveBeenCalled();
    });

    it('logs error on fetch failure', async () => {
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      vi.spyOn(globalThis, 'fetch').mockRejectedValue(new Error('Network error'));

      const voiceApi = JSON.stringify({
        input: 'http://tts.example.com/speak',
        options: { method: 'POST', body: {} },
      });

      readTextWithExternal('hello', voiceApi, 'en');

      await new Promise(resolve => setTimeout(resolve, 10));

      expect(consoleSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // readTextAloud Tests
  // ===========================================================================

  describe('readTextAloud', () => {
    let mockSpeak: ReturnType<typeof vi.fn>;

    beforeEach(() => {
      mockSpeak = vi.fn();
      Object.defineProperty(window, 'speechSynthesis', {
        value: {
          speak: mockSpeak,
          getVoices: vi.fn().mockReturnValue([]),
        },
        writable: true,
      });
      vi.spyOn(cookies, 'getCookie').mockReturnValue(null);
    });

    it('reads text directly when convert_to_phonetic is false', () => {
      readTextAloud('hello', 'en-US', 1.0, 1.0, undefined, false);

      expect(mockSpeak).toHaveBeenCalled();
    });

    it('fetches phonetic text when convert_to_phonetic is true', () => {
      const getJSONSpy = vi.spyOn($, 'getJSON').mockImplementation(() => {
        return {
          then: vi.fn().mockReturnThis(),
        } as unknown as JQuery.jqXHR<{ phonetic_reading: string }>;
      });

      readTextAloud('hello', 'en-US', 1.0, 1.0, undefined, true);

      expect(getJSONSpy).toHaveBeenCalledWith(
        'api.php/v1/phonetic-reading',
        expect.any(Object)
      );
    });
  });

  // ===========================================================================
  // handleReadingConfiguration Tests
  // ===========================================================================

  describe('handleReadingConfiguration', () => {
    let mockSpeak: ReturnType<typeof vi.fn>;

    beforeEach(() => {
      mockSpeak = vi.fn();
      Object.defineProperty(window, 'speechSynthesis', {
        value: {
          speak: mockSpeak,
          getVoices: vi.fn().mockReturnValue([]),
        },
        writable: true,
      });
      vi.spyOn(cookies, 'getCookie').mockReturnValue(null);
    });

    it('reads directly for direct mode', () => {
      const config = {
        reading_mode: 'direct' as const,
        name: 'English',
        abbreviation: 'en-US',
      };

      handleReadingConfiguration(config, 'hello', 1);

      expect(mockSpeak).toHaveBeenCalled();
    });

    it('fetches phonetic for internal mode', () => {
      const getJSONSpy = vi.spyOn($, 'getJSON').mockImplementation(() => ({
        then: vi.fn().mockReturnThis(),
      } as unknown as JQuery.jqXHR<{ phonetic_reading: string }>));

      const config = {
        reading_mode: 'internal' as const,
        name: 'Chinese',
        abbreviation: 'zh-CN',
      };

      handleReadingConfiguration(config, '你好', 2);

      expect(getJSONSpy).toHaveBeenCalled();
    });

    it('uses external API for external mode', async () => {
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({ audio: 'data:audio/mp3;base64,test' }),
      } as Response);

      const config = {
        reading_mode: 'external' as const,
        name: 'Japanese',
        abbreviation: 'ja-JP',
        voiceapi: JSON.stringify({
          input: 'http://api.example.com',
          options: { method: 'POST', body: {} },
        }),
      };

      handleReadingConfiguration(config, 'こんにちは', 3);

      await new Promise(resolve => setTimeout(resolve, 10));

      expect(fetchSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // speechDispatcher Tests
  // ===========================================================================

  describe('speechDispatcher', () => {
    it('makes GET request for reading configuration', () => {
      const getJSONSpy = vi.spyOn($, 'getJSON').mockImplementation(
        () => ({} as JQuery.jqXHR)
      );

      speechDispatcher('hello', 5);

      expect(getJSONSpy).toHaveBeenCalledWith(
        'api.php/v1/languages/5/reading-configuration',
        { lang_id: 5 },
        expect.any(Function)
      );
    });
  });

  // ===========================================================================
  // Edge Cases and Error Handling
  // ===========================================================================

  describe('Edge Cases', () => {
    it('deepReplace handles simple nested objects', () => {
      // Test that deeply nested objects work correctly
      const obj = {
        level1: {
          level2: {
            value: 'lwt_term here',
          },
        },
      };

      deepReplace(obj, 'lwt_term', 'replaced');
      expect(obj.level1.level2.value).toBe('replaced here');
    });

    it('deepFindValue handles objects with prototype properties', () => {
      const obj = Object.create({ inherited: 'data:inherited' });
      obj.own = 'not matching';

      const result = deepFindValue(obj, 'data:');
      // Should only find own properties, not inherited
      expect(result).toBeNull();
    });

    it('cookieTTSSettings handles malformed cookie values', () => {
      document.cookie = 'tts[enRate]=not-a-number';

      const settings = cookieTTSSettings('en');
      // parseFloat('not-a-number') returns NaN
      expect(Number.isNaN(settings.rate)).toBe(true);
    });

    it('readRawTextAloud handles voice not found', () => {
      // Override getVoices to return empty array
      (window.speechSynthesis as unknown as Record<string, unknown>).getVoices =
        vi.fn().mockReturnValue([]);

      // Mock getCookie to return no TTS settings
      vi.spyOn(cookies, 'getCookie').mockReturnValue(null);

      const result = readRawTextAloud(
        'Hello',
        'en-US',
        undefined,
        undefined,
        'NonExistentVoice'
      );

      // Voice should not be set when not found (stays at default null)
      expect(result.voice).toBeNull();
    });
  });
});
