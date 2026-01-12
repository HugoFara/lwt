/**
 * Tests for file_import.ts - File import functionality for texts
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock whisper_import
vi.mock('../../../src/frontend/js/modules/text/pages/whisper_import', () => ({
  isAudioVideoFile: vi.fn((filename: string) => {
    const ext = filename.split('.').pop()?.toLowerCase() ?? '';
    return ['mp3', 'mp4', 'wav', 'webm', 'ogg', 'm4a'].includes(ext);
  }),
  handleFileSelection: vi.fn()
}));

import { initFileImport } from '../../../src/frontend/js/modules/text/pages/file_import';
import { handleFileSelection as handleWhisperFileSelection } from '../../../src/frontend/js/modules/text/pages/whisper_import';

describe('file_import.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    sessionStorage.clear();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
    sessionStorage.clear();
  });

  // ===========================================================================
  // initFileImport Tests
  // ===========================================================================

  describe('initFileImport', () => {
    it('does nothing if file input not found', () => {
      document.body.innerHTML = '<div></div>';

      expect(() => initFileImport()).not.toThrow();
    });

    it('adds change listener to file input', () => {
      document.body.innerHTML = '<input type="file" id="importFile" />';
      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const addEventListenerSpy = vi.spyOn(input, 'addEventListener');

      initFileImport();

      expect(addEventListenerSpy).toHaveBeenCalledWith('change', expect.any(Function));
    });

    it('handles missing input gracefully', () => {
      document.body.innerHTML = '<div id="other"></div>';

      expect(() => initFileImport()).not.toThrow();
    });
  });

  // ===========================================================================
  // EPUB File Tests
  // ===========================================================================

  describe('EPUB file handling', () => {
    it('redirects to book import page for EPUB', () => {
      const originalLocation = window.location;
      delete (window as { location?: Location }).location;
      window.location = { href: '' } as Location;

      document.body.innerHTML = `
        <input type="file" id="importFile" />
        <span id="importFileStatus"></span>
      `;

      initFileImport();

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const epubFile = new File([''], 'book.epub', { type: 'application/epub+zip' });
      Object.defineProperty(input, 'files', { value: [epubFile] });
      input.dispatchEvent(new Event('change'));

      expect(window.location.href).toBe('/book/import?from=text');

      window.location = originalLocation;
    });

    it('stores EPUB info in sessionStorage', () => {
      const originalLocation = window.location;
      delete (window as { location?: Location }).location;
      window.location = { href: '' } as Location;

      document.body.innerHTML = `
        <input type="file" id="importFile" />
        <span id="importFileStatus"></span>
      `;

      initFileImport();

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const epubFile = new File([''], 'my-book.epub', { type: 'application/epub+zip' });
      Object.defineProperty(input, 'files', { value: [epubFile] });
      input.dispatchEvent(new Event('change'));

      const stored = JSON.parse(sessionStorage.getItem('pendingEpubImport') || '{}');
      expect(stored.filename).toBe('my-book.epub');
      expect(stored.timestamp).toBeDefined();

      window.location = originalLocation;
    });

    it('shows info status for EPUB redirect', () => {
      const originalLocation = window.location;
      delete (window as { location?: Location }).location;
      window.location = { href: '' } as Location;

      document.body.innerHTML = `
        <input type="file" id="importFile" />
        <span id="importFileStatus"></span>
      `;

      initFileImport();

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const epubFile = new File([''], 'book.epub', { type: 'application/epub+zip' });
      Object.defineProperty(input, 'files', { value: [epubFile] });
      input.dispatchEvent(new Event('change'));

      const status = document.querySelector<HTMLElement>('#importFileStatus')!;
      expect(status.classList.contains('has-text-info')).toBe(true);
      expect(status.textContent).toContain('Redirecting');

      window.location = originalLocation;
    });
  });

  // ===========================================================================
  // Audio/Video File Tests
  // ===========================================================================

  describe('audio/video file handling', () => {
    it('delegates to whisper handler for audio files', () => {
      document.body.innerHTML = `
        <input type="file" id="importFile" />
        <span id="importFileStatus"></span>
      `;

      initFileImport();

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const audioFile = new File([''], 'audio.mp3', { type: 'audio/mpeg' });
      Object.defineProperty(input, 'files', { value: [audioFile] });
      input.dispatchEvent(new Event('change'));

      expect(handleWhisperFileSelection).toHaveBeenCalledWith(audioFile);
    });

    it('delegates to whisper handler for video files', () => {
      document.body.innerHTML = `
        <input type="file" id="importFile" />
        <span id="importFileStatus"></span>
      `;

      initFileImport();

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const videoFile = new File([''], 'video.mp4', { type: 'video/mp4' });
      Object.defineProperty(input, 'files', { value: [videoFile] });
      input.dispatchEvent(new Event('change'));

      expect(handleWhisperFileSelection).toHaveBeenCalledWith(videoFile);
    });

    it('recognizes wav audio files', () => {
      document.body.innerHTML = `
        <input type="file" id="importFile" />
        <span id="importFileStatus"></span>
      `;

      initFileImport();

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const wavFile = new File([''], 'recording.wav', { type: 'audio/wav' });
      Object.defineProperty(input, 'files', { value: [wavFile] });
      input.dispatchEvent(new Event('change'));

      expect(handleWhisperFileSelection).toHaveBeenCalledWith(wavFile);
    });

    it('recognizes webm video files', () => {
      document.body.innerHTML = `
        <input type="file" id="importFile" />
        <span id="importFileStatus"></span>
      `;

      initFileImport();

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const webmFile = new File([''], 'video.webm', { type: 'video/webm' });
      Object.defineProperty(input, 'files', { value: [webmFile] });
      input.dispatchEvent(new Event('change'));

      expect(handleWhisperFileSelection).toHaveBeenCalledWith(webmFile);
    });
  });

  // ===========================================================================
  // Unsupported File Tests
  // ===========================================================================

  describe('unsupported file handling', () => {
    it('shows error for unsupported file types', () => {
      document.body.innerHTML = `
        <input type="file" id="importFile" />
        <span id="importFileStatus"></span>
      `;

      initFileImport();

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const pdfFile = new File([''], 'document.pdf', { type: 'application/pdf' });
      Object.defineProperty(input, 'files', { value: [pdfFile] });
      input.dispatchEvent(new Event('change'));

      const status = document.querySelector<HTMLElement>('#importFileStatus')!;
      expect(status.classList.contains('has-text-danger')).toBe(true);
      expect(status.textContent).toContain('Unsupported');
    });

    it('shows error for docx files', () => {
      document.body.innerHTML = `
        <input type="file" id="importFile" />
        <span id="importFileStatus"></span>
      `;

      initFileImport();

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const docxFile = new File([''], 'document.docx', { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' });
      Object.defineProperty(input, 'files', { value: [docxFile] });
      input.dispatchEvent(new Event('change'));

      const status = document.querySelector<HTMLElement>('#importFileStatus')!;
      expect(status.classList.contains('has-text-danger')).toBe(true);
    });

    it('shows error for zip files', () => {
      document.body.innerHTML = `
        <input type="file" id="importFile" />
        <span id="importFileStatus"></span>
      `;

      initFileImport();

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const zipFile = new File([''], 'archive.zip', { type: 'application/zip' });
      Object.defineProperty(input, 'files', { value: [zipFile] });
      input.dispatchEvent(new Event('change'));

      const status = document.querySelector<HTMLElement>('#importFileStatus')!;
      expect(status.classList.contains('has-text-danger')).toBe(true);
    });
  });

  // ===========================================================================
  // Edge Cases Tests
  // ===========================================================================

  describe('edge cases', () => {
    it('handles no file selected', () => {
      document.body.innerHTML = `
        <input type="file" id="importFile" />
        <span id="importFileStatus"></span>
      `;

      initFileImport();

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      Object.defineProperty(input, 'files', { value: [] });
      input.dispatchEvent(new Event('change'));

      // Should not throw or show error
      const status = document.querySelector<HTMLElement>('#importFileStatus')!;
      expect(status.textContent).toBe('');
    });

    it('handles null files property', () => {
      document.body.innerHTML = `
        <input type="file" id="importFile" />
        <span id="importFileStatus"></span>
      `;

      initFileImport();

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      Object.defineProperty(input, 'files', { value: null });
      input.dispatchEvent(new Event('change'));

      // Should not throw
      const status = document.querySelector<HTMLElement>('#importFileStatus')!;
      expect(status.textContent).toBe('');
    });

    it('handles file with no extension', () => {
      document.body.innerHTML = `
        <input type="file" id="importFile" />
        <span id="importFileStatus"></span>
      `;

      initFileImport();

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const noExtFile = new File([''], 'noextension', { type: 'application/octet-stream' });
      Object.defineProperty(input, 'files', { value: [noExtFile] });
      input.dispatchEvent(new Event('change'));

      const status = document.querySelector<HTMLElement>('#importFileStatus')!;
      expect(status.classList.contains('has-text-danger')).toBe(true);
    });

    it('handles uppercase file extension', () => {
      document.body.innerHTML = `
        <input type="file" id="importFile" />
        <span id="importFileStatus"></span>
      `;

      initFileImport();

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const mp3File = new File([''], 'AUDIO.MP3', { type: 'audio/mpeg' });
      Object.defineProperty(input, 'files', { value: [mp3File] });
      input.dispatchEvent(new Event('change'));

      expect(handleWhisperFileSelection).toHaveBeenCalledWith(mp3File);
    });

    it('handles mixed case epub extension', () => {
      const originalLocation = window.location;
      delete (window as { location?: Location }).location;
      window.location = { href: '' } as Location;

      document.body.innerHTML = `
        <input type="file" id="importFile" />
        <span id="importFileStatus"></span>
      `;

      initFileImport();

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const epubFile = new File([''], 'Book.EPUB', { type: 'application/epub+zip' });
      Object.defineProperty(input, 'files', { value: [epubFile] });
      input.dispatchEvent(new Event('change'));

      expect(window.location.href).toBe('/book/import?from=text');

      window.location = originalLocation;
    });
  });
});
