/**
 * File Import - Handle import of subtitles, EPUB, and audio/video files on text edit form.
 *
 * Supports:
 * - SRT/VTT subtitles: Parse client-side and populate textarea
 * - EPUB files: Redirect to dedicated book import page
 * - Audio/Video files: Use Whisper transcription (handled by whisper_import.ts)
 *
 * @license unlicense
 * @since   3.0.0
 */

import { onDomReady } from '@shared/utils/dom_ready';
import { isAudioVideoFile, handleFileSelection as handleWhisperFileSelection, initWhisperImport } from './whisper_import';

/**
 * Parsed subtitle result.
 */
interface SubtitleParseResult {
  success: boolean;
  text: string;
  cueCount: number;
  error?: string;
}

/**
 * Set the value of a form input by name attribute.
 */
function setInputByName(name: string, value: string): void {
  const el = document.querySelector<HTMLInputElement | HTMLTextAreaElement>(
    `[name="${name}"]`
  );
  if (el) {
    el.value = value;
  }
}

/**
 * Get the value of a form input by name attribute.
 */
function getInputByName(name: string): string {
  const el = document.querySelector<HTMLInputElement | HTMLTextAreaElement>(
    `[name="${name}"]`
  );
  return el?.value ?? '';
}

/**
 * Update the status message for file import.
 */
function setImportStatus(msg: string, isError = false, isInfo = false): void {
  const statusEl = document.getElementById('importFileStatus');
  if (statusEl) {
    statusEl.textContent = msg;
    statusEl.classList.remove('has-text-danger', 'has-text-success', 'has-text-info');
    if (isError) {
      statusEl.classList.add('has-text-danger');
    } else if (isInfo) {
      statusEl.classList.add('has-text-info');
    } else if (msg) {
      statusEl.classList.add('has-text-success');
    }
  }
}

/**
 * Get file extension in lowercase.
 */
function getFileExtension(filename: string): string {
  return filename.split('.').pop()?.toLowerCase() ?? '';
}

/**
 * Detect subtitle format from filename and content.
 */
function detectSubtitleFormat(
  filename: string,
  content: string
): 'srt' | 'vtt' | null {
  const ext = getFileExtension(filename);
  if (ext === 'srt') return 'srt';
  if (ext === 'vtt') return 'vtt';

  // Fall back to content detection
  if (content.trim().startsWith('WEBVTT')) return 'vtt';
  if (/^\d+\s*\n\d{2}:\d{2}:\d{2},\d{3}\s*-->/m.test(content)) return 'srt';

  return null;
}

/**
 * Parse SRT format content.
 */
function parseSrt(content: string): string {
  content = content.replace(/\r\n|\r/g, '\n');
  const blocks = content.trim().split(/\n\s*\n/);
  const texts: string[] = [];

  for (const block of blocks) {
    const trimmedBlock = block.trim();
    if (!trimmedBlock) continue;

    const lines = trimmedBlock.split('\n');
    const textLines: string[] = [];

    for (const line of lines) {
      const trimmedLine = line.trim();
      if (/^\d+$/.test(trimmedLine)) continue;
      if (trimmedLine.includes('-->')) continue;
      if (trimmedLine) {
        const cleanLine = trimmedLine.replace(/<[^>]*>/g, '');
        if (cleanLine) textLines.push(cleanLine);
      }
    }

    if (textLines.length > 0) {
      texts.push(textLines.join('\n'));
    }
  }

  return texts.join('\n\n');
}

/**
 * Parse VTT format content.
 */
function parseVtt(content: string): string {
  content = content.replace(/\r\n|\r/g, '\n');
  content = content.replace(/^WEBVTT[^\n]*\n/, '');

  const blocks = content.trim().split(/\n\s*\n/);
  const texts: string[] = [];

  for (const block of blocks) {
    const trimmedBlock = block.trim();
    if (!trimmedBlock) continue;
    if (trimmedBlock.startsWith('NOTE')) continue;
    if (trimmedBlock.startsWith('STYLE')) continue;
    if (trimmedBlock.startsWith('REGION')) continue;

    const lines = trimmedBlock.split('\n');
    const textLines: string[] = [];
    let foundTimecode = false;

    for (const line of lines) {
      const trimmedLine = line.trim();

      if (!foundTimecode && !trimmedLine.includes('-->')) {
        continue;
      }

      if (trimmedLine.includes('-->')) {
        foundTimecode = true;
        continue;
      }

      if (foundTimecode && trimmedLine) {
        let cleanLine = trimmedLine.replace(/<\/?(?:c|v|lang|b|i|u|ruby|rt)[^>]*>/g, '');
        cleanLine = cleanLine.replace(/<[^>]*>/g, '');
        if (cleanLine) textLines.push(cleanLine);
      }
    }

    if (textLines.length > 0) {
      texts.push(textLines.join('\n'));
    }
  }

  return texts.join('\n\n');
}

/**
 * Parse subtitle content based on format.
 */
function parseSubtitle(content: string, format: 'srt' | 'vtt'): SubtitleParseResult {
  if (!content.trim()) {
    return { success: false, text: '', cueCount: 0, error: 'File is empty' };
  }

  let text: string;
  if (format === 'srt') {
    text = parseSrt(content);
  } else {
    text = parseVtt(content);
  }

  text = text
    .replace(/[^\S\n]+/g, ' ')
    .split('\n')
    .map((line) => line.trim())
    .join('\n')
    .replace(/\n{3,}/g, '\n\n')
    .trim();

  if (!text) {
    return { success: false, text: '', cueCount: 0, error: 'No text content found' };
  }

  const cueCount = (text.match(/\n\n/g)?.length ?? 0) + 1;

  return { success: true, text, cueCount };
}

/**
 * Handle subtitle file - parse and populate textarea.
 */
function handleSubtitleFile(file: File): void {
  setImportStatus('Reading subtitle file...');

  const reader = new FileReader();

  reader.onload = (e) => {
    const content = e.target?.result as string;
    if (!content) {
      setImportStatus('Failed to read file', true);
      return;
    }

    const format = detectSubtitleFormat(file.name, content);
    if (!format) {
      setImportStatus('Could not detect subtitle format', true);
      return;
    }

    const result = parseSubtitle(content, format);

    if (!result.success) {
      setImportStatus(result.error ?? 'Failed to parse subtitle file', true);
      return;
    }

    setInputByName('TxText', result.text);

    if (!getInputByName('TxTitle')) {
      const titleFromFile = file.name.replace(/\.(srt|vtt)$/i, '');
      setInputByName('TxTitle', titleFromFile);
    }

    setImportStatus(
      `Imported ${result.cueCount} subtitle cue${result.cueCount !== 1 ? 's' : ''} from ${format.toUpperCase()}`
    );
  };

  reader.onerror = () => {
    setImportStatus('Error reading file', true);
  };

  reader.readAsText(file);
}

/**
 * Handle EPUB file - redirect to book import page.
 */
function handleEpubFile(file: File): void {
  setImportStatus('Redirecting to EPUB importer...', false, true);

  // Store file info for the book import page
  // We'll use sessionStorage to pass the filename hint
  sessionStorage.setItem('pendingEpubImport', JSON.stringify({
    filename: file.name,
    timestamp: Date.now()
  }));

  // Redirect to book import page
  // The user will need to re-select the file there (browser security limitation)
  window.location.href = '/book/import?from=text';
}

/**
 * Handle file selection based on type.
 */
function handleFileImport(file: File): void {
  const ext = getFileExtension(file.name);

  if (ext === 'epub') {
    handleEpubFile(file);
  } else if (ext === 'srt' || ext === 'vtt') {
    handleSubtitleFile(file);
  } else if (isAudioVideoFile(file.name)) {
    // Audio/video files are handled by whisper_import.ts
    // The handleWhisperFileSelection will show the transcription options
    handleWhisperFileSelection(file);
  } else {
    setImportStatus('Unsupported file type. Use .epub, .srt, .vtt, or audio/video files.', true);
  }
}

/**
 * Initialize file import functionality.
 */
export function initFileImport(): void {
  const fileInput = document.querySelector<HTMLInputElement>('#importFile');

  if (!fileInput) return;

  fileInput.addEventListener('change', () => {
    const file = fileInput.files?.[0];
    if (file) {
      handleFileImport(file);
    }
  });

  // Initialize whisper transcription buttons if present
  initWhisperImport();
}

// Auto-initialize on document ready
onDomReady(initFileImport);
