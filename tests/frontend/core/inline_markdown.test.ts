/**
 * Tests for inline Markdown parser.
 *
 * @license Unlicense <http://unlicense.org/>
 */

import { describe, it, expect } from 'vitest';
import { parseInlineMarkdown, containsMarkdown } from '../../../src/frontend/js/core/inline_markdown';

describe('parseInlineMarkdown', () => {
  describe('basic formatting', () => {
    it('should parse bold text with double asterisks', () => {
      expect(parseInlineMarkdown('**bold**')).toBe('<strong>bold</strong>');
    });

    it('should parse italic text with single asterisk', () => {
      expect(parseInlineMarkdown('*italic*')).toBe('<em>italic</em>');
    });

    it('should parse strikethrough with double tildes', () => {
      expect(parseInlineMarkdown('~~strikethrough~~')).toBe('<del>strikethrough</del>');
    });

    it('should parse links', () => {
      expect(parseInlineMarkdown('[text](https://example.com)'))
        .toBe('<a href="https://example.com" target="_blank" rel="noopener noreferrer">text</a>');
    });
  });

  describe('combined formatting', () => {
    it('should handle bold and italic together', () => {
      expect(parseInlineMarkdown('**bold** and *italic*'))
        .toBe('<strong>bold</strong> and <em>italic</em>');
    });

    it('should handle multiple formatting types', () => {
      expect(parseInlineMarkdown('**bold** *italic* ~~strike~~'))
        .toBe('<strong>bold</strong> <em>italic</em> <del>strike</del>');
    });

    it('should handle formatting within links', () => {
      expect(parseInlineMarkdown('[**bold link**](https://example.com)'))
        .toBe('<a href="https://example.com" target="_blank" rel="noopener noreferrer"><strong>bold link</strong></a>');
    });
  });

  describe('edge cases', () => {
    it('should return empty string for empty input', () => {
      expect(parseInlineMarkdown('')).toBe('');
    });

    it('should return empty string for null-ish values', () => {
      expect(parseInlineMarkdown(null as unknown as string)).toBe('');
      expect(parseInlineMarkdown(undefined as unknown as string)).toBe('');
    });

    it('should handle plain text without formatting', () => {
      expect(parseInlineMarkdown('just plain text')).toBe('just plain text');
    });

    it('should not parse incomplete bold (single asterisks)', () => {
      // Single asterisks should be treated as italic, not partial bold
      expect(parseInlineMarkdown('single *asterisks* only')).toBe('single <em>asterisks</em> only');
    });
  });

  describe('XSS prevention', () => {
    it('should escape HTML tags', () => {
      expect(parseInlineMarkdown('<script>alert("xss")</script>'))
        .toBe('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;');
    });

    it('should escape angle brackets', () => {
      expect(parseInlineMarkdown('a < b > c'))
        .toBe('a &lt; b &gt; c');
    });

    it('should escape quotes', () => {
      expect(parseInlineMarkdown('He said "hello"'))
        .toBe('He said &quot;hello&quot;');
    });

    it('should escape ampersands', () => {
      expect(parseInlineMarkdown('Tom & Jerry'))
        .toBe('Tom &amp; Jerry');
    });
  });

  describe('URL sanitization', () => {
    it('should allow https URLs', () => {
      expect(parseInlineMarkdown('[link](https://example.com)'))
        .toContain('href="https://example.com"');
    });

    it('should allow http URLs', () => {
      expect(parseInlineMarkdown('[link](http://example.com)'))
        .toContain('href="http://example.com"');
    });

    it('should allow relative URLs starting with /', () => {
      expect(parseInlineMarkdown('[link](/path/to/page)'))
        .toContain('href="/path/to/page"');
    });

    it('should allow relative URLs starting with ./', () => {
      expect(parseInlineMarkdown('[link](./relative)'))
        .toContain('href="./relative"');
    });

    it('should allow relative URLs starting with ../', () => {
      expect(parseInlineMarkdown('[link](../parent)'))
        .toContain('href="../parent"');
    });

    it('should block javascript: URLs', () => {
      expect(parseInlineMarkdown('[click](javascript:alert(1))'))
        .toContain('href="#"');
      expect(parseInlineMarkdown('[click](javascript:alert(1))'))
        .not.toContain('javascript:');
    });

    it('should block data: URLs', () => {
      expect(parseInlineMarkdown('[click](data:text/html,<script>alert(1)</script>)'))
        .toContain('href="#"');
    });

    it('should block vbscript: URLs', () => {
      expect(parseInlineMarkdown('[click](vbscript:msgbox)'))
        .toContain('href="#"');
    });
  });

  describe('link attributes', () => {
    it('should add target="_blank" to links', () => {
      expect(parseInlineMarkdown('[link](https://example.com)'))
        .toContain('target="_blank"');
    });

    it('should add rel="noopener noreferrer" to links', () => {
      expect(parseInlineMarkdown('[link](https://example.com)'))
        .toContain('rel="noopener noreferrer"');
    });
  });
});

describe('containsMarkdown', () => {
  it('should detect bold markers', () => {
    expect(containsMarkdown('**bold**')).toBe(true);
  });

  it('should detect italic markers', () => {
    expect(containsMarkdown('*italic*')).toBe(true);
  });

  it('should detect strikethrough markers', () => {
    expect(containsMarkdown('~~strike~~')).toBe(true);
  });

  it('should detect links', () => {
    expect(containsMarkdown('[text](url)')).toBe(true);
  });

  it('should return false for plain text', () => {
    expect(containsMarkdown('just plain text')).toBe(false);
  });

  it('should return false for empty string', () => {
    expect(containsMarkdown('')).toBe(false);
  });

  it('should return false for null-ish values', () => {
    expect(containsMarkdown(null as unknown as string)).toBe(false);
    expect(containsMarkdown(undefined as unknown as string)).toBe(false);
  });
});
