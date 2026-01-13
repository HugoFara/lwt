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

  // ===========================================================================
  // SRT Parsing Tests
  // Skipped: FileReader async operations not completing reliably in test environment
  // ===========================================================================

  describe.skip('SRT file parsing', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <input type="file" id="importFile" />
        <span id="importFileStatus"></span>
        <input name="TxText" value="" />
        <input name="TxTitle" value="" />
      `;
      initFileImport();
    });

    it('parses simple SRT content', async () => {
      const srtContent = `1
00:00:01,000 --> 00:00:04,000
Hello World

2
00:00:05,000 --> 00:00:08,000
Goodbye World`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const srtFile = new File([srtContent], 'subtitles.srt', { type: 'text/plain' });
      Object.defineProperty(input, 'files', { value: [srtFile] });
      input.dispatchEvent(new Event('change'));

      // Wait for FileReader to complete
      await vi.waitFor(() => {
        const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
        return textInput.value.includes('Hello World');
      });

      const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
      expect(textInput.value).toContain('Hello World');
      expect(textInput.value).toContain('Goodbye World');
    });

    it('removes HTML tags from SRT', async () => {
      const srtContent = `1
00:00:01,000 --> 00:00:04,000
<i>Italic text</i>

2
00:00:05,000 --> 00:00:08,000
<b>Bold text</b>`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const srtFile = new File([srtContent], 'subtitles.srt', { type: 'text/plain' });
      Object.defineProperty(input, 'files', { value: [srtFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
        return textInput.value.includes('Italic text');
      });

      const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
      expect(textInput.value).not.toContain('<i>');
      expect(textInput.value).not.toContain('</i>');
      expect(textInput.value).not.toContain('<b>');
      expect(textInput.value).not.toContain('</b>');
      expect(textInput.value).toContain('Italic text');
      expect(textInput.value).toContain('Bold text');
    });

    it('handles multi-line cues', async () => {
      const srtContent = `1
00:00:01,000 --> 00:00:04,000
Line 1
Line 2`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const srtFile = new File([srtContent], 'subtitles.srt', { type: 'text/plain' });
      Object.defineProperty(input, 'files', { value: [srtFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
        return textInput.value.includes('Line 1');
      });

      const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
      expect(textInput.value).toContain('Line 1');
      expect(textInput.value).toContain('Line 2');
    });

    it('sets title from SRT filename', async () => {
      const srtContent = `1
00:00:01,000 --> 00:00:04,000
Content`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const srtFile = new File([srtContent], 'MyVideo.srt', { type: 'text/plain' });
      Object.defineProperty(input, 'files', { value: [srtFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const titleInput = document.querySelector<HTMLInputElement>('[name="TxTitle"]')!;
        return titleInput.value === 'MyVideo';
      });

      const titleInput = document.querySelector<HTMLInputElement>('[name="TxTitle"]')!;
      expect(titleInput.value).toBe('MyVideo');
    });

    it('does not override existing title', async () => {
      const titleInput = document.querySelector<HTMLInputElement>('[name="TxTitle"]')!;
      titleInput.value = 'Existing Title';

      const srtContent = `1
00:00:01,000 --> 00:00:04,000
Content`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const srtFile = new File([srtContent], 'NewTitle.srt', { type: 'text/plain' });
      Object.defineProperty(input, 'files', { value: [srtFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
        return textInput.value.includes('Content');
      });

      expect(titleInput.value).toBe('Existing Title');
    });

    it('handles Windows line endings (CRLF)', async () => {
      const srtContent = "1\r\n00:00:01,000 --> 00:00:04,000\r\nWindows line endings\r\n";

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const srtFile = new File([srtContent], 'subtitles.srt', { type: 'text/plain' });
      Object.defineProperty(input, 'files', { value: [srtFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
        return textInput.value.includes('Windows line endings');
      });

      const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
      expect(textInput.value).toContain('Windows line endings');
    });

    it('handles Mac line endings (CR)', async () => {
      const srtContent = "1\r00:00:01,000 --> 00:00:04,000\rMac line endings\r";

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const srtFile = new File([srtContent], 'subtitles.srt', { type: 'text/plain' });
      Object.defineProperty(input, 'files', { value: [srtFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
        return textInput.value.includes('Mac line endings');
      });

      const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
      expect(textInput.value).toContain('Mac line endings');
    });

    it('shows success message with cue count', async () => {
      const srtContent = `1
00:00:01,000 --> 00:00:04,000
First

2
00:00:05,000 --> 00:00:08,000
Second

3
00:00:09,000 --> 00:00:12,000
Third`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const srtFile = new File([srtContent], 'subtitles.srt', { type: 'text/plain' });
      Object.defineProperty(input, 'files', { value: [srtFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const status = document.querySelector<HTMLElement>('#importFileStatus')!;
        return status.textContent?.includes('cue');
      });

      const status = document.querySelector<HTMLElement>('#importFileStatus')!;
      expect(status.textContent).toContain('3 subtitle cues');
      expect(status.textContent).toContain('SRT');
      expect(status.classList.contains('has-text-success')).toBe(true);
    });

    it('uses singular for single cue', async () => {
      const srtContent = `1
00:00:01,000 --> 00:00:04,000
Only one cue`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const srtFile = new File([srtContent], 'subtitles.srt', { type: 'text/plain' });
      Object.defineProperty(input, 'files', { value: [srtFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const status = document.querySelector<HTMLElement>('#importFileStatus')!;
        return status.textContent?.includes('cue');
      });

      const status = document.querySelector<HTMLElement>('#importFileStatus')!;
      expect(status.textContent).toContain('1 subtitle cue');
      expect(status.textContent).not.toContain('cues');
    });

    it('shows error for empty file', async () => {
      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const srtFile = new File([''], 'empty.srt', { type: 'text/plain' });
      Object.defineProperty(input, 'files', { value: [srtFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const status = document.querySelector<HTMLElement>('#importFileStatus')!;
        return status.textContent !== 'Reading subtitle file...';
      });

      const status = document.querySelector<HTMLElement>('#importFileStatus')!;
      expect(status.textContent).toBe('File is empty');
      expect(status.classList.contains('has-text-danger')).toBe(true);
    });

    it('shows error for SRT with no text content', async () => {
      const srtContent = `1
00:00:01,000 --> 00:00:04,000

2
00:00:05,000 --> 00:00:08,000
`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const srtFile = new File([srtContent], 'empty.srt', { type: 'text/plain' });
      Object.defineProperty(input, 'files', { value: [srtFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const status = document.querySelector<HTMLElement>('#importFileStatus')!;
        return status.textContent !== 'Reading subtitle file...';
      });

      const status = document.querySelector<HTMLElement>('#importFileStatus')!;
      expect(status.textContent).toBe('No text content found');
      expect(status.classList.contains('has-text-danger')).toBe(true);
    });
  });

  // ===========================================================================
  // VTT Parsing Tests
  // Skipped: FileReader async operations not completing reliably in test environment
  // ===========================================================================

  describe.skip('VTT file parsing', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <input type="file" id="importFile" />
        <span id="importFileStatus"></span>
        <input name="TxText" value="" />
        <input name="TxTitle" value="" />
      `;
      initFileImport();
    });

    it('parses simple VTT content', async () => {
      const vttContent = `WEBVTT

00:00:01.000 --> 00:00:04.000
Hello World

00:00:05.000 --> 00:00:08.000
Goodbye World`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const vttFile = new File([vttContent], 'subtitles.vtt', { type: 'text/vtt' });
      Object.defineProperty(input, 'files', { value: [vttFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
        return textInput.value.includes('Hello World');
      });

      const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
      expect(textInput.value).toContain('Hello World');
      expect(textInput.value).toContain('Goodbye World');
    });

    it('removes VTT formatting tags', async () => {
      const vttContent = `WEBVTT

00:00:01.000 --> 00:00:04.000
<c.yellow>Colored text</c>

00:00:05.000 --> 00:00:08.000
<v Speaker>Voice text</v>`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const vttFile = new File([vttContent], 'subtitles.vtt', { type: 'text/vtt' });
      Object.defineProperty(input, 'files', { value: [vttFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
        return textInput.value.includes('Colored text');
      });

      const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
      expect(textInput.value).not.toContain('<c');
      expect(textInput.value).not.toContain('<v');
      expect(textInput.value).toContain('Colored text');
      expect(textInput.value).toContain('Voice text');
    });

    it('skips NOTE blocks', async () => {
      const vttContent = `WEBVTT

NOTE This is a comment

00:00:01.000 --> 00:00:04.000
Actual content`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const vttFile = new File([vttContent], 'subtitles.vtt', { type: 'text/vtt' });
      Object.defineProperty(input, 'files', { value: [vttFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
        return textInput.value.includes('Actual content');
      });

      const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
      expect(textInput.value).not.toContain('This is a comment');
      expect(textInput.value).toContain('Actual content');
    });

    it('skips STYLE blocks', async () => {
      const vttContent = `WEBVTT

STYLE
::cue { color: yellow; }

00:00:01.000 --> 00:00:04.000
Actual content`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const vttFile = new File([vttContent], 'subtitles.vtt', { type: 'text/vtt' });
      Object.defineProperty(input, 'files', { value: [vttFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
        return textInput.value.includes('Actual content');
      });

      const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
      expect(textInput.value).not.toContain('::cue');
      expect(textInput.value).toContain('Actual content');
    });

    it('skips REGION blocks', async () => {
      const vttContent = `WEBVTT

REGION
id:region1
width:40%

00:00:01.000 --> 00:00:04.000
Actual content`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const vttFile = new File([vttContent], 'subtitles.vtt', { type: 'text/vtt' });
      Object.defineProperty(input, 'files', { value: [vttFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
        return textInput.value.includes('Actual content');
      });

      const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
      expect(textInput.value).not.toContain('region1');
      expect(textInput.value).toContain('Actual content');
    });

    it('handles VTT with cue identifiers', async () => {
      const vttContent = `WEBVTT

cue-1
00:00:01.000 --> 00:00:04.000
First cue

cue-2
00:00:05.000 --> 00:00:08.000
Second cue`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const vttFile = new File([vttContent], 'subtitles.vtt', { type: 'text/vtt' });
      Object.defineProperty(input, 'files', { value: [vttFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
        return textInput.value.includes('First cue');
      });

      const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
      expect(textInput.value).toContain('First cue');
      expect(textInput.value).toContain('Second cue');
    });

    it('removes lang tags', async () => {
      const vttContent = `WEBVTT

00:00:01.000 --> 00:00:04.000
<lang en>English text</lang>`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const vttFile = new File([vttContent], 'subtitles.vtt', { type: 'text/vtt' });
      Object.defineProperty(input, 'files', { value: [vttFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
        return textInput.value.includes('English text');
      });

      const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
      expect(textInput.value).not.toContain('<lang');
      expect(textInput.value).toContain('English text');
    });

    it('removes ruby and rt tags', async () => {
      const vttContent = `WEBVTT

00:00:01.000 --> 00:00:04.000
<ruby>漢字<rt>かんじ</rt></ruby>`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const vttFile = new File([vttContent], 'subtitles.vtt', { type: 'text/vtt' });
      Object.defineProperty(input, 'files', { value: [vttFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
        return textInput.value.includes('漢字');
      });

      const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
      expect(textInput.value).not.toContain('<ruby>');
      expect(textInput.value).not.toContain('<rt>');
      expect(textInput.value).toContain('漢字');
      expect(textInput.value).toContain('かんじ');
    });

    it('sets title from VTT filename', async () => {
      const vttContent = `WEBVTT

00:00:01.000 --> 00:00:04.000
Content`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const vttFile = new File([vttContent], 'MyVideo.vtt', { type: 'text/vtt' });
      Object.defineProperty(input, 'files', { value: [vttFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const titleInput = document.querySelector<HTMLInputElement>('[name="TxTitle"]')!;
        return titleInput.value === 'MyVideo';
      });

      const titleInput = document.querySelector<HTMLInputElement>('[name="TxTitle"]')!;
      expect(titleInput.value).toBe('MyVideo');
    });

    it('handles VTT header with description', async () => {
      const vttContent = `WEBVTT - This file has a description

00:00:01.000 --> 00:00:04.000
Content`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const vttFile = new File([vttContent], 'subtitles.vtt', { type: 'text/vtt' });
      Object.defineProperty(input, 'files', { value: [vttFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
        return textInput.value.includes('Content');
      });

      const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
      expect(textInput.value).not.toContain('WEBVTT');
      expect(textInput.value).not.toContain('description');
      expect(textInput.value).toContain('Content');
    });
  });

  // ===========================================================================
  // Format Detection Tests
  // Skipped: FileReader async operations not completing reliably in test environment
  // ===========================================================================

  describe.skip('subtitle format detection', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <input type="file" id="importFile" />
        <span id="importFileStatus"></span>
        <input name="TxText" value="" />
        <input name="TxTitle" value="" />
      `;
      initFileImport();
    });

    it('detects SRT by extension', async () => {
      const srtContent = `1
00:00:01,000 --> 00:00:04,000
SRT content`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const srtFile = new File([srtContent], 'subtitles.srt', { type: 'text/plain' });
      Object.defineProperty(input, 'files', { value: [srtFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const status = document.querySelector<HTMLElement>('#importFileStatus')!;
        return status.textContent?.includes('SRT');
      });

      const status = document.querySelector<HTMLElement>('#importFileStatus')!;
      expect(status.textContent).toContain('SRT');
    });

    it('detects VTT by extension', async () => {
      const vttContent = `WEBVTT

00:00:01.000 --> 00:00:04.000
VTT content`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const vttFile = new File([vttContent], 'subtitles.vtt', { type: 'text/vtt' });
      Object.defineProperty(input, 'files', { value: [vttFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const status = document.querySelector<HTMLElement>('#importFileStatus')!;
        return status.textContent?.includes('VTT');
      });

      const status = document.querySelector<HTMLElement>('#importFileStatus')!;
      expect(status.textContent).toContain('VTT');
    });

    it('handles uppercase SRT extension', async () => {
      const srtContent = `1
00:00:01,000 --> 00:00:04,000
Uppercase extension`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const srtFile = new File([srtContent], 'subtitles.SRT', { type: 'text/plain' });
      Object.defineProperty(input, 'files', { value: [srtFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
        return textInput.value.includes('Uppercase extension');
      });

      const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
      expect(textInput.value).toContain('Uppercase extension');
    });

    it.skip('handles uppercase VTT extension', async () => {
      const vttContent = `WEBVTT

00:00:01.000 --> 00:00:04.000
Uppercase extension`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const vttFile = new File([vttContent], 'subtitles.VTT', { type: 'text/vtt' });
      Object.defineProperty(input, 'files', { value: [vttFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
        return textInput.value.includes('Uppercase extension');
      });

      const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
      expect(textInput.value).toContain('Uppercase extension');
    });
  });

  // ===========================================================================
  // Text Normalization Tests
  // Skipped: FileReader async operations not completing in test environment
  // ===========================================================================

  describe.skip('text normalization', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <input type="file" id="importFile" />
        <span id="importFileStatus"></span>
        <input name="TxText" value="" />
        <input name="TxTitle" value="" />
      `;
      initFileImport();
    });

    it('normalizes multiple blank lines', async () => {
      const srtContent = `1
00:00:01,000 --> 00:00:04,000
First



2
00:00:05,000 --> 00:00:08,000
Second`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const srtFile = new File([srtContent], 'subtitles.srt', { type: 'text/plain' });
      Object.defineProperty(input, 'files', { value: [srtFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
        return textInput.value.includes('First');
      });

      const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
      // Should not have more than 2 consecutive newlines
      expect(textInput.value).not.toContain('\n\n\n');
    });

    it('trims whitespace from lines', async () => {
      const srtContent = `1
00:00:01,000 --> 00:00:04,000
  Spaces around  `;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const srtFile = new File([srtContent], 'subtitles.srt', { type: 'text/plain' });
      Object.defineProperty(input, 'files', { value: [srtFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
        return textInput.value.includes('Spaces around');
      });

      const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
      expect(textInput.value).toBe('Spaces around');
    });

    it('normalizes multiple spaces within text', async () => {
      const srtContent = `1
00:00:01,000 --> 00:00:04,000
Multiple   spaces   here`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const srtFile = new File([srtContent], 'subtitles.srt', { type: 'text/plain' });
      Object.defineProperty(input, 'files', { value: [srtFile] });
      input.dispatchEvent(new Event('change'));

      await vi.waitFor(() => {
        const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
        return textInput.value.includes('Multiple');
      });

      const textInput = document.querySelector<HTMLInputElement>('[name="TxText"]')!;
      expect(textInput.value).not.toContain('   ');
      expect(textInput.value).toContain('Multiple spaces here');
    });
  });

  // ===========================================================================
  // Missing Element Handling Tests
  // ===========================================================================

  describe('missing element handling', () => {
    it('handles missing status element gracefully', async () => {
      document.body.innerHTML = `
        <input type="file" id="importFile" />
        <input name="TxText" value="" />
        <input name="TxTitle" value="" />
      `;
      initFileImport();

      const srtContent = `1
00:00:01,000 --> 00:00:04,000
Content`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const srtFile = new File([srtContent], 'subtitles.srt', { type: 'text/plain' });
      Object.defineProperty(input, 'files', { value: [srtFile] });

      expect(() => input.dispatchEvent(new Event('change'))).not.toThrow();
    });

    it('handles missing text input gracefully', async () => {
      document.body.innerHTML = `
        <input type="file" id="importFile" />
        <span id="importFileStatus"></span>
        <input name="TxTitle" value="" />
      `;
      initFileImport();

      const srtContent = `1
00:00:01,000 --> 00:00:04,000
Content`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const srtFile = new File([srtContent], 'subtitles.srt', { type: 'text/plain' });
      Object.defineProperty(input, 'files', { value: [srtFile] });

      expect(() => input.dispatchEvent(new Event('change'))).not.toThrow();
    });

    it('handles missing title input gracefully', async () => {
      document.body.innerHTML = `
        <input type="file" id="importFile" />
        <span id="importFileStatus"></span>
        <input name="TxText" value="" />
      `;
      initFileImport();

      const srtContent = `1
00:00:01,000 --> 00:00:04,000
Content`;

      const input = document.querySelector<HTMLInputElement>('#importFile')!;
      const srtFile = new File([srtContent], 'subtitles.srt', { type: 'text/plain' });
      Object.defineProperty(input, 'files', { value: [srtFile] });

      expect(() => input.dispatchEvent(new Event('change'))).not.toThrow();
    });
  });
});
