/**
 * Tests for whisper_import.ts - Audio/video transcription functionality
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { isAudioVideoFile, handleFileSelection, initWhisperImport } from '../../../src/frontend/js/modules/text/pages/whisper_import';

describe('whisper_import.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // isAudioVideoFile Tests
  // ===========================================================================

  describe('isAudioVideoFile', () => {
    it('returns true for mp3 files', () => {
      expect(isAudioVideoFile('audio.mp3')).toBe(true);
    });

    it('returns true for mp4 files', () => {
      expect(isAudioVideoFile('video.mp4')).toBe(true);
    });

    it('returns true for wav files', () => {
      expect(isAudioVideoFile('recording.wav')).toBe(true);
    });

    it('returns true for webm files', () => {
      expect(isAudioVideoFile('video.webm')).toBe(true);
    });

    it('returns true for ogg files', () => {
      expect(isAudioVideoFile('audio.ogg')).toBe(true);
    });

    it('returns true for m4a files', () => {
      expect(isAudioVideoFile('audio.m4a')).toBe(true);
    });

    it('returns true for mkv files', () => {
      expect(isAudioVideoFile('video.mkv')).toBe(true);
    });

    it('returns true for flac files', () => {
      expect(isAudioVideoFile('audio.flac')).toBe(true);
    });

    it('returns true for avi files', () => {
      expect(isAudioVideoFile('video.avi')).toBe(true);
    });

    it('returns true for mov files', () => {
      expect(isAudioVideoFile('video.mov')).toBe(true);
    });

    it('returns true for wma files', () => {
      expect(isAudioVideoFile('audio.wma')).toBe(true);
    });

    it('returns true for aac files', () => {
      expect(isAudioVideoFile('audio.aac')).toBe(true);
    });

    it('returns true for uppercase extensions', () => {
      expect(isAudioVideoFile('AUDIO.MP3')).toBe(true);
      expect(isAudioVideoFile('VIDEO.MP4')).toBe(true);
    });

    it('returns true for mixed case extensions', () => {
      expect(isAudioVideoFile('audio.Mp3')).toBe(true);
    });

    it('returns false for txt files', () => {
      expect(isAudioVideoFile('document.txt')).toBe(false);
    });

    it('returns false for pdf files', () => {
      expect(isAudioVideoFile('document.pdf')).toBe(false);
    });

    it('returns false for epub files', () => {
      expect(isAudioVideoFile('book.epub')).toBe(false);
    });

    it('returns false for srt files', () => {
      expect(isAudioVideoFile('subtitles.srt')).toBe(false);
    });

    it('returns false for files without extension', () => {
      expect(isAudioVideoFile('noextension')).toBe(false);
    });

    it('returns false for empty string', () => {
      expect(isAudioVideoFile('')).toBe(false);
    });

    it('handles paths with directories', () => {
      expect(isAudioVideoFile('/path/to/audio.mp3')).toBe(true);
      expect(isAudioVideoFile('/path/to/document.txt')).toBe(false);
    });
  });

  // ===========================================================================
  // handleFileSelection Tests
  // ===========================================================================

  describe('handleFileSelection', () => {
    beforeEach(() => {
      // Set up DOM elements needed for handleFileSelection
      document.body.innerHTML = `
        <span id="importFileStatus"></span>
        <div id="whisperOptions" style="display: none;"></div>
        <div id="whisperUnavailable" style="display: none;"></div>
      `;

      // Mock fetch for whisper availability check
      global.fetch = vi.fn().mockResolvedValue({
        json: () => Promise.resolve({ data: { available: false } })
      });
    });

    it('hides whisper options when file is null', () => {
      handleFileSelection(null);

      const options = document.getElementById('whisperOptions');
      expect(options?.style.display).toBe('none');
    });

    it('shows info status for audio files', () => {
      const file = new File([''], 'audio.mp3', { type: 'audio/mpeg' });

      handleFileSelection(file);

      const status = document.getElementById('importFileStatus');
      expect(status?.textContent).toContain('Audio/video file selected');
    });

    it('hides whisper options for non-audio files', () => {
      const file = new File([''], 'document.txt', { type: 'text/plain' });

      handleFileSelection(file);

      const options = document.getElementById('whisperOptions');
      expect(options?.style.display).toBe('none');
    });
  });

  // ===========================================================================
  // initWhisperImport Tests
  // ===========================================================================

  describe('initWhisperImport', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <input type="file" id="importFile" />
        <button id="startTranscription"></button>
        <button id="whisperCancel"></button>
        <span id="importFileStatus"></span>
        <div id="whisperOptions" style="display: none;"></div>
        <div id="whisperUnavailable" style="display: none;"></div>
      `;

      global.fetch = vi.fn().mockResolvedValue({
        json: () => Promise.resolve({ data: { available: false } })
      });
    });

    it('does not throw when called', () => {
      expect(() => initWhisperImport()).not.toThrow();
    });

    it('adds change listener to file input', () => {
      const fileInput = document.getElementById('importFile')!;
      const addEventListenerSpy = vi.spyOn(fileInput, 'addEventListener');

      initWhisperImport();

      expect(addEventListenerSpy).toHaveBeenCalledWith('change', expect.any(Function));
    });

    it('adds click listener to start button', () => {
      const startBtn = document.getElementById('startTranscription')!;
      const addEventListenerSpy = vi.spyOn(startBtn, 'addEventListener');

      initWhisperImport();

      expect(addEventListenerSpy).toHaveBeenCalledWith('click', expect.any(Function));
    });

    it('adds click listener to cancel button', () => {
      const cancelBtn = document.getElementById('whisperCancel')!;
      const addEventListenerSpy = vi.spyOn(cancelBtn, 'addEventListener');

      initWhisperImport();

      expect(addEventListenerSpy).toHaveBeenCalledWith('click', expect.any(Function));
    });

    it('handles missing elements gracefully', () => {
      document.body.innerHTML = '';

      expect(() => initWhisperImport()).not.toThrow();
    });
  });

  // ===========================================================================
  // Integration Tests
  // ===========================================================================

  describe('integration', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <input type="file" id="importFile" />
        <button id="startTranscription"></button>
        <button id="whisperCancel"></button>
        <span id="importFileStatus"></span>
        <div id="whisperOptions" style="display: none;"></div>
        <div id="whisperUnavailable" style="display: none;"></div>
        <div id="whisperProgress" style="display: none;"></div>
        <span id="whisperStatusText"></span>
        <progress id="whisperProgressBar"></progress>
        <select id="whisperLanguage"></select>
        <select id="whisperModel"></select>
      `;

      global.fetch = vi.fn().mockResolvedValue({
        json: () => Promise.resolve({ data: { available: false } })
      });
    });

    it('file selection updates UI for audio file', async () => {
      initWhisperImport();

      const fileInput = document.getElementById('importFile') as HTMLInputElement;
      const audioFile = new File([''], 'test.mp3', { type: 'audio/mpeg' });
      Object.defineProperty(fileInput, 'files', { value: [audioFile] });

      fileInput.dispatchEvent(new Event('change'));

      // Wait for async check
      await new Promise(resolve => setTimeout(resolve, 0));

      const status = document.getElementById('importFileStatus');
      expect(status?.textContent).toContain('Audio/video');
    });

    it('non-audio file hides whisper options', () => {
      initWhisperImport();

      const fileInput = document.getElementById('importFile') as HTMLInputElement;
      const textFile = new File([''], 'test.txt', { type: 'text/plain' });
      Object.defineProperty(fileInput, 'files', { value: [textFile] });

      fileInput.dispatchEvent(new Event('change'));

      const options = document.getElementById('whisperOptions');
      expect(options?.style.display).toBe('none');
    });
  });
});
