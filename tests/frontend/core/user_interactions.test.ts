/**
 * Tests for user_interactions.ts - User interaction functions
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
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
} from '../../../src/frontend/js/shared/utils/user_interactions';

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
    // Setup before each test
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

    it('redirects to docs for INFO value', () => {
      quickMenuRedirection('INFO');
      expect(window.top!.location.href).toBe('/docs/');
    });

    it('redirects to feeds page for rss_import value', () => {
      quickMenuRedirection('rss_import');
      expect(window.top!.location.href).toBe('/feeds');
    });

    it('redirects to clean URL for edit_texts value', () => {
      quickMenuRedirection('edit_texts');
      expect(window.top!.location.href).toBe('/texts');
    });

    it('redirects to clean URL for edit_words value', () => {
      quickMenuRedirection('edit_words');
      expect(window.top!.location.href).toBe('/words');
    });

    it('redirects to clean URL for settings value', () => {
      quickMenuRedirection('settings');
      expect(window.top!.location.href).toBe('/admin/settings');
    });

    it('redirects to home for index value', () => {
      quickMenuRedirection('index');
      expect(window.top!.location.href).toBe('/');
    });

    it('logs error and does not navigate for unknown values', () => {
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      const originalHref = window.top!.location.href;

      quickMenuRedirection('unknown_page');

      expect(window.top!.location.href).toBe(originalHref);
      expect(consoleSpy).toHaveBeenCalledWith(
        'Quick menu: unknown value "unknown_page". Add it to quickMenuRoutes.'
      );
      consoleSpy.mockRestore();
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
  // Cookie TTS Settings Tests (now uses localStorage)
  // ===========================================================================

  describe('cookieTTSSettings', () => {
    beforeEach(() => {
      // Clear localStorage
      localStorage.clear();
      // Mark migration as complete so it doesn't try to read cookies
      localStorage.setItem('lwt.tts.migrated', '1');
    });

    afterEach(() => {
      localStorage.clear();
    });

    it('returns empty object when no settings stored', () => {
      const settings = cookieTTSSettings('en');
      expect(settings).toEqual({});
    });

    it('retrieves rate from localStorage', () => {
      localStorage.setItem('lwt.tts.en', JSON.stringify({ rate: 1.5 }));
      const settings = cookieTTSSettings('en');
      expect(settings.rate).toBe(1.5);
    });

    it('retrieves pitch from localStorage', () => {
      localStorage.setItem('lwt.tts.en', JSON.stringify({ pitch: 0.8 }));
      const settings = cookieTTSSettings('en');
      expect(settings.pitch).toBe(0.8);
    });

    it('retrieves voice from localStorage', () => {
      localStorage.setItem('lwt.tts.en', JSON.stringify({ voice: 'Google US English' }));
      const settings = cookieTTSSettings('en');
      expect(settings.voice).toBe('Google US English');
    });

    it('retrieves all settings together', () => {
      localStorage.setItem('lwt.tts.fr', JSON.stringify({
        rate: 1.2,
        pitch: 1.0,
        voice: 'French Voice',
      }));
      const settings = cookieTTSSettings('fr');
      expect(settings).toEqual({
        rate: 1.2,
        pitch: 1.0,
        voice: 'French Voice',
      });
    });

    it('handles different languages independently', () => {
      localStorage.setItem('lwt.tts.en', JSON.stringify({ rate: 1.0 }));
      localStorage.setItem('lwt.tts.fr', JSON.stringify({ rate: 1.5 }));

      const enSettings = cookieTTSSettings('en');
      const frSettings = cookieTTSSettings('fr');

      expect(enSettings.rate).toBe(1.0);
      expect(frSettings.rate).toBe(1.5);
    });

    it('extracts two-letter code from longer language codes', () => {
      localStorage.setItem('lwt.tts.en', JSON.stringify({ rate: 1.3 }));
      // cookieTTSSettings('en-US') should extract 'en' and find the settings
      const settings = cookieTTSSettings('en-US');
      expect(settings.rate).toBe(1.3);
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

      // Clear localStorage and mark migration complete
      localStorage.clear();
      localStorage.setItem('lwt.tts.migrated', '1');
    });

    afterEach(() => {
      localStorage.clear();
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

    it('uses TTS settings from localStorage when no explicit params', () => {
      localStorage.setItem('lwt.tts.en', JSON.stringify({
        rate: 1.3,
        pitch: 0.9,
        voice: 'Google UK English',
      }));

      const result = readRawTextAloud('Hello', 'en-US');

      expect(result.rate).toBe(1.3);
      expect(result.pitch).toBe(0.9);
      expect(result.voice?.name).toBe('Google UK English');
    });

    it('prioritizes explicit params over localStorage settings', () => {
      localStorage.setItem('lwt.tts.en', JSON.stringify({
        rate: 1.3,
        pitch: 0.9,
      }));

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
    beforeEach(async () => {
      // Use fake timers to prevent setTimeout callbacks from firing
      // (overlib and closePopup are called via setTimeout and would cause errors)
      vi.useFakeTimers();
      vi.spyOn(window, 'focus').mockImplementation(() => {});
      // Dynamically import reading_state to set position
      const { setReadingPosition } = await import('../../../src/frontend/js/modules/text/stores/reading_state');
      setReadingPosition(0);
    });

    afterEach(() => {
      vi.useRealTimers();
    });

    it('scrolls to position 0 when reading_position is 0', async () => {
      const { setReadingPosition } = await import('../../../src/frontend/js/modules/text/stores/reading_state');
      document.body.innerHTML = `
        <span class="wsty" data_pos="100">Word</span>
      `;
      setReadingPosition(0);

      // Should not throw
      expect(() => goToLastPosition()).not.toThrow();
    });

    it('finds element at exact reading position', async () => {
      const { setReadingPosition } = await import('../../../src/frontend/js/modules/text/stores/reading_state');
      document.body.innerHTML = `
        <span class="wsty" data_pos="50">First</span>
        <span class="wsty" data_pos="100">Target</span>
        <span class="wsty" data_pos="150">Last</span>
      `;
      setReadingPosition(100);

      expect(() => goToLastPosition()).not.toThrow();
    });

    it('finds closest element when exact position not found', async () => {
      const { setReadingPosition } = await import('../../../src/frontend/js/modules/text/stores/reading_state');
      document.body.innerHTML = `
        <span class="wsty" data_pos="50">First</span>
        <span class="wsty" data_pos="150">Last</span>
      `;
      setReadingPosition(100);

      expect(() => goToLastPosition()).not.toThrow();
    });

    it('handles no wsty elements', async () => {
      const { setReadingPosition } = await import('../../../src/frontend/js/modules/text/stores/reading_state');
      document.body.innerHTML = '<div>No words</div>';
      setReadingPosition(100);

      expect(() => goToLastPosition()).not.toThrow();
    });
  });

  // ===========================================================================
  // saveReadingPosition Tests
  // ===========================================================================

  describe('saveReadingPosition', () => {
    it('makes POST request to save position', () => {
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({} as Response);

      saveReadingPosition(42, 100);

      expect(fetchSpy).toHaveBeenCalledWith(
        '/api/v1/texts/42/reading-position',
        expect.objectContaining({
          method: 'POST',
          body: expect.any(String)
        })
      );
    });
  });

  // ===========================================================================
  // saveAudioPosition Tests
  // ===========================================================================

  describe('saveAudioPosition', () => {
    it('makes POST request to save audio position', () => {
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({} as Response);

      saveAudioPosition(42, 50.5);

      expect(fetchSpy).toHaveBeenCalledWith(
        '/api/v1/texts/42/audio-position',
        expect.objectContaining({
          method: 'POST',
          body: expect.any(String)
        })
      );
    });
  });

  // ===========================================================================
  // getPhoneticTextAsync Tests
  // ===========================================================================

  describe('getPhoneticTextAsync', () => {
    it('makes GET request with language string', () => {
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({ phonetic_reading: 'hello' })
      } as Response);

      getPhoneticTextAsync('hello', 'en-US');

      expect(fetchSpy).toHaveBeenCalledWith(
        expect.stringContaining('/api/v1/phonetic-reading')
      );
    });

    it('makes GET request with language ID number', () => {
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({ phonetic_reading: 'hello' })
      } as Response);

      getPhoneticTextAsync('hello', 5);

      expect(fetchSpy).toHaveBeenCalledWith(
        expect.stringContaining('/api/v1/phonetic-reading')
      );
    });
  });

  // ===========================================================================
  // readTextWithExternal Tests
  // ===========================================================================

  describe('readTextWithExternal', () => {
    it('makes fetch request with replaced placeholders', async () => {
      const mockAudio = { play: vi.fn() };
      // Use function (not arrow) for proper constructor mocking
      vi.spyOn(globalThis, 'Audio').mockImplementation(function() {
        return mockAudio as unknown as HTMLAudioElement;
      });

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
      localStorage.clear();
      localStorage.setItem('lwt.tts.migrated', '1');
    });

    afterEach(() => {
      localStorage.clear();
    });

    it('reads text directly when convert_to_phonetic is false', () => {
      readTextAloud('hello', 'en-US', 1.0, 1.0, undefined, false);

      expect(mockSpeak).toHaveBeenCalled();
    });

    it('fetches phonetic text when convert_to_phonetic is true', () => {
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({ phonetic_reading: 'hello' })
      } as Response);

      readTextAloud('hello', 'en-US', 1.0, 1.0, undefined, true);

      expect(fetchSpy).toHaveBeenCalledWith(
        expect.stringContaining('/api/v1/phonetic-reading')
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
      localStorage.clear();
      localStorage.setItem('lwt.tts.migrated', '1');
    });

    afterEach(() => {
      localStorage.clear();
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
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({ phonetic_reading: 'nǐ hǎo' })
      } as Response);

      const config = {
        reading_mode: 'internal' as const,
        name: 'Chinese',
        abbreviation: 'zh-CN',
      };

      handleReadingConfiguration(config, '你好', 2);

      expect(fetchSpy).toHaveBeenCalled();
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
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({ reading_mode: 'direct', abbreviation: 'en-US', name: 'English' })
      } as Response);

      speechDispatcher('hello', 5);

      expect(fetchSpy).toHaveBeenCalledWith(
        expect.stringContaining('/api/v1/languages/5/reading-configuration')
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

    it('cookieTTSSettings handles malformed localStorage values', () => {
      // Store invalid JSON
      localStorage.setItem('lwt.tts.en', 'not-valid-json');

      const settings = cookieTTSSettings('en');
      // Should return empty object when JSON parsing fails
      expect(settings).toEqual({});
    });

    it('readRawTextAloud handles voice not found', () => {
      // Override getVoices to return empty array
      (window.speechSynthesis as unknown as Record<string, unknown>).getVoices =
        vi.fn().mockReturnValue([]);

      // Clear localStorage
      localStorage.clear();
      localStorage.setItem('lwt.tts.migrated', '1');

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
