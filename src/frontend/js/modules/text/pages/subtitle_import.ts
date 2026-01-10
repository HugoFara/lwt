/**
 * Subtitle Import - Parse SRT/VTT subtitle files and populate the text form.
 *
 * Handles client-side parsing of subtitle files to provide immediate
 * preview in the textarea before saving.
 *
 * @license unlicense
 * @since   3.0.0
 */

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
 * Update the status message for subtitle import.
 */
function setSubtitleStatus(msg: string, isError = false): void {
  const statusEl = document.getElementById('subtitleStatus');
  if (statusEl) {
    statusEl.textContent = msg;
    statusEl.classList.toggle('has-text-danger', isError);
    statusEl.classList.toggle('has-text-success', !isError && msg !== '');
  }
}

/**
 * Detect subtitle format from filename and content.
 */
function detectFormat(
  filename: string,
  content: string
): 'srt' | 'vtt' | null {
  const ext = filename.split('.').pop()?.toLowerCase();
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
  // Normalize line endings
  content = content.replace(/\r\n|\r/g, '\n');

  // Split by blank lines (cue boundaries)
  const blocks = content.trim().split(/\n\s*\n/);
  const texts: string[] = [];

  for (const block of blocks) {
    const trimmedBlock = block.trim();
    if (!trimmedBlock) continue;

    const lines = trimmedBlock.split('\n');
    const textLines: string[] = [];

    for (const line of lines) {
      const trimmedLine = line.trim();

      // Skip sequence number (line that's just digits)
      if (/^\d+$/.test(trimmedLine)) continue;

      // Skip timecode line (contains -->)
      if (trimmedLine.includes('-->')) continue;

      // Keep text content (strip HTML tags)
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
  // Normalize line endings
  content = content.replace(/\r\n|\r/g, '\n');

  // Remove WEBVTT header
  content = content.replace(/^WEBVTT[^\n]*\n/, '');

  // Split by blank lines
  const blocks = content.trim().split(/\n\s*\n/);
  const texts: string[] = [];

  for (const block of blocks) {
    const trimmedBlock = block.trim();
    if (!trimmedBlock) continue;

    // Skip NOTE blocks
    if (trimmedBlock.startsWith('NOTE')) continue;

    // Skip STYLE blocks
    if (trimmedBlock.startsWith('STYLE')) continue;

    // Skip REGION blocks
    if (trimmedBlock.startsWith('REGION')) continue;

    const lines = trimmedBlock.split('\n');
    const textLines: string[] = [];
    let foundTimecode = false;

    for (const line of lines) {
      const trimmedLine = line.trim();

      // Skip cue identifier (line before timecode)
      if (!foundTimecode && !trimmedLine.includes('-->')) {
        continue;
      }

      // Skip timecode line
      if (trimmedLine.includes('-->')) {
        foundTimecode = true;
        continue;
      }

      // Keep text content (after timecode)
      if (foundTimecode && trimmedLine) {
        // Strip VTT styling tags
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

  // Clean up the text
  text = text
    .replace(/[^\S\n]+/g, ' ')  // Normalize spaces
    .split('\n')
    .map((line) => line.trim())
    .join('\n')
    .replace(/\n{3,}/g, '\n\n')  // Max 2 consecutive newlines
    .trim();

  if (!text) {
    return { success: false, text: '', cueCount: 0, error: 'No text content found' };
  }

  const cueCount = (text.match(/\n\n/g)?.length ?? 0) + 1;

  return { success: true, text, cueCount };
}

/**
 * Handle subtitle file selection.
 */
function handleSubtitleFile(file: File): void {
  setSubtitleStatus('Reading file...');

  const format = detectFormat(file.name, '');
  if (!format) {
    // We'll detect from content after reading
  }

  const reader = new FileReader();

  reader.onload = (e) => {
    const content = e.target?.result as string;
    if (!content) {
      setSubtitleStatus('Failed to read file', true);
      return;
    }

    const detectedFormat = detectFormat(file.name, content);
    if (!detectedFormat) {
      setSubtitleStatus('Unsupported file format. Use .srt or .vtt files.', true);
      return;
    }

    const result = parseSubtitle(content, detectedFormat);

    if (!result.success) {
      setSubtitleStatus(result.error ?? 'Failed to parse subtitle file', true);
      return;
    }

    // Populate form fields
    setInputByName('TxText', result.text);

    // Auto-fill title if empty
    if (!getInputByName('TxTitle')) {
      const titleFromFile = file.name.replace(/\.(srt|vtt)$/i, '');
      setInputByName('TxTitle', titleFromFile);
    }

    setSubtitleStatus(
      `Imported ${result.cueCount} subtitle cue${result.cueCount !== 1 ? 's' : ''} from ${detectedFormat.toUpperCase()} file`
    );
  };

  reader.onerror = () => {
    setSubtitleStatus('Error reading file', true);
  };

  reader.readAsText(file);
}

/**
 * Initialize subtitle import functionality.
 */
export function initSubtitleImport(): void {
  const fileInput = document.querySelector<HTMLInputElement>(
    'input[name="subtitleFile"]'
  );

  if (!fileInput) return;

  fileInput.addEventListener('change', () => {
    const file = fileInput.files?.[0];
    if (file) {
      handleSubtitleFile(file);
    }
  });
}

// Auto-initialize on document ready
document.addEventListener('DOMContentLoaded', initSubtitleImport);
