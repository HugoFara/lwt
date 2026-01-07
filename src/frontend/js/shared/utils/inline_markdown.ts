/**
 * Inline Markdown Parser
 *
 * Parses inline Markdown syntax to HTML.
 * Supports: **bold**, *italic*, [links](url), ~~strikethrough~~
 *
 * Security:
 * - HTML is escaped before parsing (XSS prevention)
 * - Only http/https/relative URLs allowed in links
 * - Generated tags: <strong>, <em>, <del>, <a>
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

/**
 * Escape HTML special characters.
 *
 * @param str - Input string
 * @returns String with HTML entities escaped
 */
function escapeHtml(str: string): string {
  return str
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

/**
 * Sanitize URL for safe use in href attribute.
 *
 * Only allows http, https, and relative URLs.
 * Blocks javascript:, data:, and other potentially dangerous protocols.
 *
 * @param url - URL to sanitize
 * @returns Safe URL or '#' if blocked
 */
function sanitizeUrl(url: string): string {
  const trimmed = url.trim();

  // Allow relative URLs
  if (
    trimmed.startsWith('/') ||
    trimmed.startsWith('./') ||
    trimmed.startsWith('../')
  ) {
    return trimmed;
  }

  // Allow http/https
  if (/^https?:\/\//i.test(trimmed)) {
    return trimmed;
  }

  // Block everything else (javascript:, data:, etc.)
  return '#';
}

/**
 * Parse inline Markdown to HTML.
 *
 * Processing order matters:
 * 1. Escape HTML first (security)
 * 2. Links [text](url) - most specific pattern
 * 3. Bold **text**
 * 4. Italic *text* (after bold to avoid conflicts)
 * 5. Strikethrough ~~text~~
 *
 * @param text - Input text with Markdown
 * @returns HTML string
 */
export function parseInlineMarkdown(text: string): string {
  if (!text) return '';

  // Step 1: Escape HTML characters first
  let result = escapeHtml(text);

  // Step 2: Links [text](url)
  result = result.replace(
    /\[([^\]]+)\]\(([^)]+)\)/g,
    (_, linkText: string, url: string) => {
      const safeUrl = sanitizeUrl(url);
      return `<a href="${safeUrl}" target="_blank" rel="noopener noreferrer">${linkText}</a>`;
    }
  );

  // Step 3: Bold **text**
  result = result.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');

  // Step 4: Italic *text* (not preceded/followed by asterisk)
  result = result.replace(/(?<!\*)\*([^*]+)\*(?!\*)/g, '<em>$1</em>');

  // Step 5: Strikethrough ~~text~~
  result = result.replace(/~~([^~]+)~~/g, '<del>$1</del>');

  return result;
}

/**
 * Check if text contains any inline Markdown syntax.
 *
 * Useful for optimization - skip parsing if no Markdown present.
 *
 * @param text - Input text
 * @returns True if Markdown syntax detected
 */
export function containsMarkdown(text: string): boolean {
  if (!text) return false;
  // Check for: **bold**, *italic*, [link](url), ~~strike~~
  return /\*\*|(?<!\*)\*(?!\*)|\[.+\]\(.+\)|~~/.test(text);
}
