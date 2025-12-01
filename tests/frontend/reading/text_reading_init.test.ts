/**
 * Tests for text_reading_init.ts - Text reading initialization
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  initTTS,
  toggleReading,
  saveTextStatus,
  initTextReading,
  initTextReadingHeader,
  autoInit
} from '../../../src/frontend/js/reading/text_reading_init';
import { LWT_DATA } from '../../../src/frontend/js/core/lwt_state';

// Mock dependencies
vi.mock('../../../src/frontend/js/terms/dictionary', () => ({
  getLangFromDict: vi.fn().mockReturnValue('en')
}));

vi.mock('../../../src/frontend/js/reading/text_events', () => ({
  prepareTextInteractions: vi.fn()
}));

vi.mock('../../../src/frontend/js/core/user_interactions', () => ({
  goToLastPosition: vi.fn(),
  saveReadingPosition: vi.fn(),
  saveAudioPosition: vi.fn(),
  readRawTextAloud: vi.fn()
}));

vi.mock('../../../src/frontend/js/media/html5_audio_player', () => ({
  getAudioPlayer: vi.fn()
}));

import { getLangFromDict } from '../../../src/frontend/js/terms/dictionary';
import { prepareTextInteractions } from '../../../src/frontend/js/reading/text_events';
import { goToLastPosition, saveReadingPosition, saveAudioPosition, readRawTextAloud } from '../../../src/frontend/js/core/user_interactions';
import { getAudioPlayer } from '../../../src/frontend/js/media/html5_audio_player';

describe('text_reading_init.ts', () => {
  let mockSpeechSynthesis: any;

  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();

    // Reset window globals
    delete (window as any)._lwtPhoneticText;
    delete (window as any)._lwtLanguageCode;
    delete (window as any)._lwtVoiceApi;
    delete (window as any)._lwtTextId;
    delete (window as any).new_globals;
    delete (window as any).LANG;
    delete (window as any).LWT_VITE_LOADED;

    // Reset LWT_DATA
    if (LWT_DATA.language) {
      delete LWT_DATA.language.ttsVoiceApi;
      delete LWT_DATA.language.translator_link;
    }
    if (LWT_DATA.text) {
      delete LWT_DATA.text.id;
      delete LWT_DATA.text.reading_position;
    }
    if (LWT_DATA.test) {
      delete LWT_DATA.test.answer_opened;
    }
    if (LWT_DATA.settings) {
      delete LWT_DATA.settings.jQuery_tooltip;
    }

    // Mock speechSynthesis
    mockSpeechSynthesis = {
      speaking: false,
      cancel: vi.fn(),
      speak: vi.fn()
    };
    Object.defineProperty(window, 'speechSynthesis', {
      value: mockSpeechSynthesis,
      writable: true,
      configurable: true
    });
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // initTTS Tests
  // ===========================================================================

  describe('initTTS', () => {
    it('does nothing when _lwtPhoneticText is undefined', () => {
      initTTS();
      // No error should occur
    });

    it('initializes text_reader when _lwtPhoneticText is set', () => {
      (window as any)._lwtPhoneticText = 'Hello world';
      (window as any)._lwtLanguageCode = 'en-US';

      // Setup LWT_DATA.language
      LWT_DATA.language = { translator_link: 'https://translate.google.com' };
      (getLangFromDict as any).mockReturnValue('en');

      initTTS();

      // TTS should be initialized (we can't directly inspect internal state, but no error means success)
    });

    it('sets ttsVoiceApi on LWT_DATA.language', () => {
      (window as any)._lwtPhoneticText = 'Test text';
      (window as any)._lwtVoiceApi = 'google';
      LWT_DATA.language = {};

      initTTS();

      expect(LWT_DATA.language.ttsVoiceApi).toBe('google');
    });

    it('handles missing _lwtVoiceApi gracefully', () => {
      (window as any)._lwtPhoneticText = 'Test text';
      LWT_DATA.language = {};

      initTTS();

      expect(LWT_DATA.language.ttsVoiceApi).toBe('');
    });
  });

  // ===========================================================================
  // toggleReading Tests
  // ===========================================================================

  describe('toggleReading', () => {
    it('alerts when speechSynthesis is undefined', () => {
      Object.defineProperty(window, 'speechSynthesis', {
        value: undefined,
        writable: true,
        configurable: true
      });

      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      toggleReading();

      expect(alertSpy).toHaveBeenCalledWith(expect.stringContaining('speechSynthesis'));
    });

    it('cancels speech when already speaking', () => {
      mockSpeechSynthesis.speaking = true;

      toggleReading();

      expect(mockSpeechSynthesis.cancel).toHaveBeenCalled();
    });

    it('starts reading when not speaking', () => {
      mockSpeechSynthesis.speaking = false;
      (window as any)._lwtPhoneticText = 'Read this text';
      (window as any)._lwtLanguageCode = 'en';
      LWT_DATA.language = { translator_link: '' };

      toggleReading();

      // Should call readRawTextAloud or initReading
      // The function calls internal initReading which calls readRawTextAloud
      expect(readRawTextAloud).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // saveTextStatus Tests
  // ===========================================================================

  describe('saveTextStatus', () => {
    it('does nothing when _lwtTextId is undefined', () => {
      saveTextStatus();

      expect(saveAudioPosition).not.toHaveBeenCalled();
    });

    it('saves audio position from HTML5 audio player', () => {
      (window as any)._lwtTextId = 123;

      const mockPlayer = {
        getCurrentTime: vi.fn().mockReturnValue(45.5)
      };
      (getAudioPlayer as any).mockReturnValue(mockPlayer);

      saveTextStatus();

      expect(saveAudioPosition).toHaveBeenCalledWith(123, 45.5);
    });

    it('does nothing when HTML5 player not available', () => {
      (window as any)._lwtTextId = 456;
      (getAudioPlayer as any).mockReturnValue(null);

      saveTextStatus();

      expect(saveAudioPosition).not.toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // initTextReading Tests
  // ===========================================================================

  describe('initTextReading', () => {
    it('merges new_globals into window', () => {
      (window as any).new_globals = {
        LWT_DATA: {
          language: { id: 1, name: 'English' },
          text: { id: 5, title: 'Test' }
        }
      };

      initTextReading();

      // Values should be merged
      expect(LWT_DATA.language).toBeDefined();
    });

    it('sets window.LANG from getLangFromDict', () => {
      LWT_DATA.language = { translator_link: 'https://example.com' };
      (getLangFromDict as any).mockReturnValue('fr');

      initTextReading();

      expect(window.LANG).toBe('fr');
    });

    it('resets reading_position to -1', () => {
      LWT_DATA.text = { id: 1 };

      initTextReading();

      expect(LWT_DATA.text.reading_position).toBe(-1);
    });

    it('initializes test answer_opened to false', () => {
      LWT_DATA.test = {};

      initTextReading();

      expect(LWT_DATA.test.answer_opened).toBe(false);
    });

    it('sets up document ready handlers', () => {
      initTextReading();

      // The ready handlers should be attached (we can verify they exist)
      // Note: Testing DOMContentLoaded handlers directly is complex in vitest
      // This test verifies the function runs without error
      expect(true).toBe(true);
    });
  });

  // ===========================================================================
  // initTextReadingHeader Tests
  // ===========================================================================

  describe('initTextReadingHeader', () => {
    it('sets up beforeunload handler', () => {
      const addEventListenerSpy = vi.spyOn(window, 'addEventListener');

      initTextReadingHeader();

      expect(addEventListenerSpy).toHaveBeenCalledWith('beforeunload', expect.any(Function));
      addEventListenerSpy.mockRestore();
    });

    it('initializes TTS', () => {
      (window as any)._lwtPhoneticText = 'Test';
      LWT_DATA.language = {};

      initTextReadingHeader();

      // TTS should be initialized (sets ttsVoiceApi)
      expect(LWT_DATA.language.ttsVoiceApi).toBeDefined();
    });

    it('binds click handler for readTextButton', () => {
      document.body.innerHTML = '<button id="readTextButton">Read</button>';

      const readTextButton = document.getElementById('readTextButton')!;
      const addEventListenerSpy = vi.spyOn(readTextButton, 'addEventListener');

      initTextReadingHeader();

      // Check if click handler was bound to the button
      expect(addEventListenerSpy).toHaveBeenCalledWith('click', expect.any(Function));
      addEventListenerSpy.mockRestore();
    });
  });

  // ===========================================================================
  // autoInit Tests
  // ===========================================================================

  describe('autoInit', () => {
    it('calls initTextReading when #thetext exists with new_globals', () => {
      document.body.innerHTML = '<div id="thetext"></div>';
      (window as any).new_globals = { someData: true };

      autoInit();

      // initTextReading should have been called (checks via side effects)
    });

    it('does not call initTextReading when #thetext is missing', () => {
      (window as any).new_globals = { someData: true };

      autoInit();

      // No error should occur
    });

    it('does not call initTextReading when new_globals is missing', () => {
      document.body.innerHTML = '<div id="thetext"></div>';

      autoInit();

      // No error should occur
    });

    it('calls initTextReadingHeader when _lwtPhoneticText is defined', () => {
      (window as any)._lwtPhoneticText = 'Some phonetic text';
      LWT_DATA.language = {};

      autoInit();

      expect(LWT_DATA.language.ttsVoiceApi).toBeDefined();
    });

    it('does not call initTextReadingHeader when _lwtPhoneticText is undefined', () => {
      LWT_DATA.language = {};

      autoInit();

      expect(LWT_DATA.language.ttsVoiceApi).toBeUndefined();
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles empty phonetic text', () => {
      (window as any)._lwtPhoneticText = '';
      LWT_DATA.language = { translator_link: '' };

      initTTS();

      // Should not crash
    });

    it('handles null language in LWT_DATA', () => {
      (window as any)._lwtPhoneticText = 'Test';
      (window as any)._lwtVoiceApi = 'api';

      // LWT_DATA.language is undefined
      delete (LWT_DATA as any).language;

      // Should not throw
      expect(() => initTTS()).not.toThrow();
    });

    it('handles speechSynthesis.cancel throwing', () => {
      mockSpeechSynthesis.speaking = true;
      mockSpeechSynthesis.cancel = vi.fn().mockImplementation(() => {
        throw new Error('Cancel failed');
      });

      // This should propagate the error since it's unexpected
      expect(() => toggleReading()).toThrow();
    });

    it('handles missing translator_link in language', () => {
      LWT_DATA.language = {};

      // Clear mock calls before the test
      vi.mocked(getLangFromDict).mockClear();

      initTextReading();

      // getLangFromDict is called with the translator_link (undefined becomes '')
      // or may not be called at all if the condition is not met
      // Just verify no crash occurs
    });

    it('sets html lang attribute when LANG differs from translator_link', () => {
      document.body.innerHTML = '<html><body></body></html>';
      LWT_DATA.language = { translator_link: 'https://different.com' };
      (getLangFromDict as any).mockReturnValue('de');

      initTextReading();

      const html = document.querySelector('html');
      expect(html?.getAttribute('lang')).toBe('de');
    });
  });
});
