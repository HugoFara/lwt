/**
 * Tests for translation_api.ts - Translation API functions
 */
import { describe, it, expect, afterEach, vi } from 'vitest';
import {
  getLibreTranslateTranslationBase,
  getLibreTranslateTranslation,
} from '../../../src/frontend/js/terms/translation_api';

describe('translation_api.ts', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ===========================================================================
  // LibreTranslate Functions Tests
  // ===========================================================================

  describe('getLibreTranslateTranslationBase', () => {
    it('makes correct fetch request with default parameters', async () => {
      const mockResponse = { translatedText: 'Bonjour' };
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.resolve(mockResponse),
      } as Response);

      const result = await getLibreTranslateTranslationBase(
        'Hello',
        'en',
        'fr'
      );

      expect(fetchSpy).toHaveBeenCalledWith(
        'http://localhost:5000/translate',
        expect.objectContaining({
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
        })
      );

      const callBody = JSON.parse(
        (fetchSpy.mock.calls[0][1] as RequestInit).body as string
      );
      expect(callBody).toEqual({
        q: 'Hello',
        source: 'en',
        target: 'fr',
        format: 'text',
        api_key: '',
      });

      expect(result).toBe('Bonjour');
    });

    it('includes API key when provided', async () => {
      const mockResponse = { translatedText: 'Hola' };
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.resolve(mockResponse),
      } as Response);

      await getLibreTranslateTranslationBase(
        'Hello',
        'en',
        'es',
        'my-api-key'
      );

      const callBody = JSON.parse(
        (fetchSpy.mock.calls[0][1] as RequestInit).body as string
      );
      expect(callBody.api_key).toBe('my-api-key');
    });

    it('uses custom URL when provided', async () => {
      const mockResponse = { translatedText: 'Ciao' };
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.resolve(mockResponse),
      } as Response);

      await getLibreTranslateTranslationBase(
        'Hello',
        'en',
        'it',
        '',
        'http://custom.libretranslate.com/translate'
      );

      expect(fetchSpy).toHaveBeenCalledWith(
        'http://custom.libretranslate.com/translate',
        expect.any(Object)
      );
    });

    it('handles auto language detection', async () => {
      const mockResponse = { translatedText: 'Привет' };
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.resolve(mockResponse),
      } as Response);

      await getLibreTranslateTranslationBase('Hello', 'auto', 'ru');

      const callBody = JSON.parse(
        (fetchSpy.mock.calls[0][1] as RequestInit).body as string
      );
      expect(callBody.source).toBe('auto');
    });

    it('handles empty text', async () => {
      const mockResponse = { translatedText: '' };
      vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.resolve(mockResponse),
      } as Response);

      const result = await getLibreTranslateTranslationBase('', 'en', 'fr');

      expect(result).toBe('');
    });

    it('handles special characters in text', async () => {
      const mockResponse = { translatedText: 'Bonjour le monde !' };
      vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.resolve(mockResponse),
      } as Response);

      const result = await getLibreTranslateTranslationBase(
        'Hello, world!',
        'en',
        'fr'
      );

      expect(result).toBe('Bonjour le monde !');
    });

    it('handles unicode text', async () => {
      const mockResponse = { translatedText: 'こんにちは' };
      vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.resolve(mockResponse),
      } as Response);

      const result = await getLibreTranslateTranslationBase(
        'Hello',
        'en',
        'ja'
      );

      expect(result).toBe('こんにちは');
    });
  });

  describe('getLibreTranslateTranslation', () => {
    it('extracts parameters from URL correctly', async () => {
      const mockResponse = { translatedText: 'Bonjour' };
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.resolve(mockResponse),
      } as Response);

      const url = new URL(
        'http://localhost:5000?lwt_translator=libretranslate&lwt_key=mykey'
      );
      const result = await getLibreTranslateTranslation(url, 'Hello', 'en', 'fr');

      expect(result).toBe('Bonjour');
      const callBody = JSON.parse(
        (fetchSpy.mock.calls[0][1] as RequestInit).body as string
      );
      expect(callBody.api_key).toBe('mykey');
    });

    it('uses custom AJAX URL when lwt_translator_ajax is provided', async () => {
      const mockResponse = { translatedText: 'Hola' };
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.resolve(mockResponse),
      } as Response);

      const url = new URL(
        'http://localhost:5000?lwt_translator=libretranslate&lwt_translator_ajax=' +
          encodeURIComponent('http://custom.server.com/api/translate')
      );
      await getLibreTranslateTranslation(url, 'Hello', 'en', 'es');

      expect(fetchSpy).toHaveBeenCalledWith(
        'http://custom.server.com/api/translate',
        expect.any(Object)
      );
    });

    it('throws error for unsupported translator', async () => {
      const url = new URL('http://localhost:5000?lwt_translator=google');

      await expect(
        getLibreTranslateTranslation(url, 'Hello', 'en', 'fr')
      ).rejects.toThrow('Translation API not supported: google!');
    });

    it('throws error when lwt_translator is missing', async () => {
      const url = new URL('http://localhost:5000');

      await expect(
        getLibreTranslateTranslation(url, 'Hello', 'en', 'fr')
      ).rejects.toThrow('Translation API not supported');
    });

    it('constructs default translate endpoint when lwt_translator_ajax is not provided', async () => {
      const mockResponse = { translatedText: 'Ciao' };
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.resolve(mockResponse),
      } as Response);

      const url = new URL(
        'http://localhost:5000/api?lwt_translator=libretranslate'
      );
      await getLibreTranslateTranslation(url, 'Hello', 'en', 'it');

      // Should use the base URL + '/translate'
      expect(fetchSpy).toHaveBeenCalledWith(
        'http://localhost:5000/apitranslate',
        expect.any(Object)
      );
    });

    it('handles URL without API key', async () => {
      const mockResponse = { translatedText: 'Guten Tag' };
      const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.resolve(mockResponse),
      } as Response);

      const url = new URL(
        'http://localhost:5000?lwt_translator=libretranslate'
      );
      await getLibreTranslateTranslation(url, 'Hello', 'en', 'de');

      const callBody = JSON.parse(
        (fetchSpy.mock.calls[0][1] as RequestInit).body as string
      );
      expect(callBody.api_key).toBe('');
    });
  });

  // ===========================================================================
  // Error Handling Tests
  // ===========================================================================

  describe('Error Handling', () => {
    it('propagates fetch errors', async () => {
      vi.spyOn(globalThis, 'fetch').mockRejectedValue(
        new Error('Network error')
      );

      await expect(
        getLibreTranslateTranslationBase('Hello', 'en', 'fr')
      ).rejects.toThrow('Network error');
    });

    it('handles JSON parse errors', async () => {
      vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.reject(new Error('Invalid JSON')),
      } as unknown as Response);

      await expect(
        getLibreTranslateTranslationBase('Hello', 'en', 'fr')
      ).rejects.toThrow('Invalid JSON');
    });

    it('handles server error responses', async () => {
      vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({ error: 'Rate limit exceeded' }),
      } as Response);

      // The function currently doesn't check for errors in response
      // It would return undefined for translatedText
      const result = await getLibreTranslateTranslationBase('Hello', 'en', 'fr');
      expect(result).toBeUndefined();
    });
  });

  // ===========================================================================
  // Edge Cases Tests
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles very long text', async () => {
      const longText = 'Hello '.repeat(1000);
      const mockResponse = { translatedText: 'Bonjour '.repeat(1000) };
      vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.resolve(mockResponse),
      } as Response);

      const result = await getLibreTranslateTranslationBase(
        longText,
        'en',
        'fr'
      );

      expect(result).toBe('Bonjour '.repeat(1000));
    });

    it('handles newlines in text', async () => {
      const mockResponse = { translatedText: 'Ligne 1\nLigne 2' };
      vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.resolve(mockResponse),
      } as Response);

      const result = await getLibreTranslateTranslationBase(
        'Line 1\nLine 2',
        'en',
        'fr'
      );

      expect(result).toBe('Ligne 1\nLigne 2');
    });

    it('handles HTML content in text', async () => {
      const mockResponse = { translatedText: '<p>Bonjour</p>' };
      vi.spyOn(globalThis, 'fetch').mockResolvedValue({
        json: () => Promise.resolve(mockResponse),
      } as Response);

      const result = await getLibreTranslateTranslationBase(
        '<p>Hello</p>',
        'en',
        'fr'
      );

      expect(result).toBe('<p>Bonjour</p>');
    });
  });
});
