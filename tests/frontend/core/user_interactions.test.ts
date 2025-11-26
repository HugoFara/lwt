/**
 * Tests for user_interactions.ts - User interaction functions
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  quickMenuRedirection,
  deepReplace,
  deepFindValue,
  cookieTTSSettings,
  readRawTextAloud,
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
