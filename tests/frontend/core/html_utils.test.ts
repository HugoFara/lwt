/**
 * Tests for core/html_utils.ts - HTML escaping utility functions
 */
import { describe, it, expect } from 'vitest';
import {
  escape_html_chars,
  escape_html_chars_2,
  escape_apostrophes,
  renderTags,
  renderStatusBarChart
} from '../../../src/frontend/js/shared/utils/html_utils';

describe('core/html_utils.ts', () => {
  // ===========================================================================
  // escape_html_chars Tests
  // ===========================================================================

  describe('escape_html_chars', () => {
    it('escapes ampersand', () => {
      expect(escape_html_chars('foo & bar')).toBe('foo &amp; bar');
    });

    it('escapes less than', () => {
      expect(escape_html_chars('a < b')).toBe('a &lt; b');
    });

    it('escapes greater than', () => {
      expect(escape_html_chars('a > b')).toBe('a &gt; b');
    });

    it('escapes double quotes', () => {
      expect(escape_html_chars('say "hello"')).toBe('say &quot;hello&quot;');
    });

    it('escapes single quotes', () => {
      expect(escape_html_chars("it's")).toBe("it&#039;s");
    });

    it('escapes carriage return to br tag', () => {
      expect(escape_html_chars('line1\x0dline2')).toBe('line1<br />line2');
    });

    it('escapes multiple special characters', () => {
      expect(escape_html_chars('<script>"alert(\'xss\')&"</script>')).toBe(
        '&lt;script&gt;&quot;alert(&#039;xss&#039;)&amp;&quot;&lt;/script&gt;'
      );
    });

    it('returns empty string for empty input', () => {
      expect(escape_html_chars('')).toBe('');
    });

    it('returns same string when no special characters', () => {
      expect(escape_html_chars('hello world 123')).toBe('hello world 123');
    });

    it('handles unicode characters', () => {
      expect(escape_html_chars('日本語 & 中文')).toBe('日本語 &amp; 中文');
    });
  });

  // ===========================================================================
  // escape_html_chars_2 Tests
  // ===========================================================================

  describe('escape_html_chars_2', () => {
    it('escapes title without annotation', () => {
      expect(escape_html_chars_2('Hello <World>', '')).toBe('Hello &lt;World&gt;');
    });

    it('highlights annotation in red when provided', () => {
      const result = escape_html_chars_2('Hello World', 'World');

      expect(result).toContain('<span style="color:red">World</span>');
    });

    it('escapes both title and annotation', () => {
      const result = escape_html_chars_2('A & B', 'B');

      expect(result).toBe('A &amp; <span style="color:red">B</span>');
    });

    it('escapes special characters in annotation', () => {
      const result = escape_html_chars_2('Say "Hello"', '"Hello"');

      expect(result).toContain('<span style="color:red">&quot;Hello&quot;</span>');
    });

    it('returns escaped title when annotation is empty string', () => {
      expect(escape_html_chars_2('Test & Check', '')).toBe('Test &amp; Check');
    });

    it('handles annotation not found in title', () => {
      const result = escape_html_chars_2('Hello World', 'xyz');

      // Should still escape but not highlight
      expect(result).toBe('Hello World');
    });

    it('handles multiple occurrences of annotation', () => {
      const result = escape_html_chars_2('a a a', 'a');

      // Should replace first occurrence
      expect(result).toContain('<span style="color:red">a</span>');
    });
  });

  // ===========================================================================
  // escape_apostrophes Tests
  // ===========================================================================

  describe('escape_apostrophes', () => {
    it('escapes single apostrophe', () => {
      expect(escape_apostrophes("it's")).toBe("it\\'s");
    });

    it('escapes multiple apostrophes', () => {
      expect(escape_apostrophes("don't won't can't")).toBe("don\\'t won\\'t can\\'t");
    });

    it('returns same string without apostrophes', () => {
      expect(escape_apostrophes('hello world')).toBe('hello world');
    });

    it('returns empty string for empty input', () => {
      expect(escape_apostrophes('')).toBe('');
    });

    it('does not escape double quotes', () => {
      expect(escape_apostrophes('"hello"')).toBe('"hello"');
    });

    it('handles apostrophe at start and end', () => {
      expect(escape_apostrophes("'hello'")).toBe("\\'hello\\'");
    });
  });

  // ===========================================================================
  // renderTags Tests
  // ===========================================================================

  describe('renderTags', () => {
    it('returns empty string for empty input', () => {
      expect(renderTags('')).toBe('');
    });

    it('returns empty string for whitespace-only input', () => {
      expect(renderTags('   ')).toBe('');
    });

    it('renders single tag', () => {
      const result = renderTags('important');

      expect(result).toContain('class="tag is-info is-light is-small"');
      expect(result).toContain('important');
    });

    it('renders multiple comma-separated tags', () => {
      const result = renderTags('tag1,tag2,tag3');

      expect(result).toContain('tag1');
      expect(result).toContain('tag2');
      expect(result).toContain('tag3');
    });

    it('trims whitespace from tags', () => {
      const result = renderTags(' tag1 , tag2 , tag3 ');

      expect(result).toContain('>tag1</span>');
      expect(result).toContain('>tag2</span>');
      expect(result).toContain('>tag3</span>');
    });

    it('filters empty tags', () => {
      const result = renderTags('tag1,,tag2');

      expect(result).toContain('tag1');
      expect(result).toContain('tag2');
      expect((result.match(/<span/g) || []).length).toBe(2);
    });

    it('escapes HTML in tags', () => {
      const result = renderTags('<script>');

      expect(result).not.toContain('<script>');
      expect(result).toContain('&lt;script&gt;');
    });

    it('handles unicode tags', () => {
      const result = renderTags('日本語,中文');

      expect(result).toContain('日本語');
      expect(result).toContain('中文');
    });
  });

  // ===========================================================================
  // renderStatusBarChart Tests
  // ===========================================================================

  describe('renderStatusBarChart', () => {
    it('returns empty chart for null stats', () => {
      const result = renderStatusBarChart(null);

      expect(result).toBe('<div class="status-bar-chart empty"></div>');
    });

    it('returns empty chart for undefined stats', () => {
      const result = renderStatusBarChart(undefined);

      expect(result).toBe('<div class="status-bar-chart empty"></div>');
    });

    it('returns empty chart when total is 0', () => {
      const result = renderStatusBarChart({
        total: 0,
        unknown: 0,
        statusCounts: {}
      });

      expect(result).toBe('<div class="status-bar-chart empty"></div>');
    });

    it('renders chart with unknown words', () => {
      const result = renderStatusBarChart({
        total: 100,
        unknown: 50,
        statusCounts: {}
      });

      expect(result).toContain('class="status-segment bc0"');
      expect(result).toContain('50.00%');
      expect(result).toContain('Unknown');
    });

    it('renders chart with status counts', () => {
      const result = renderStatusBarChart({
        total: 100,
        unknown: 0,
        statusCounts: {
          '1': 20,
          '2': 30,
          '3': 50
        }
      });

      expect(result).toContain('bc1');
      expect(result).toContain('bc2');
      expect(result).toContain('bc3');
    });

    it('renders all status types', () => {
      const result = renderStatusBarChart({
        total: 100,
        unknown: 10,
        statusCounts: {
          '1': 10,
          '2': 10,
          '3': 10,
          '4': 10,
          '5': 10,
          '98': 20,
          '99': 20
        }
      });

      expect(result).toContain('bc0');
      expect(result).toContain('bc1');
      expect(result).toContain('bc2');
      expect(result).toContain('bc3');
      expect(result).toContain('bc4');
      expect(result).toContain('bc5');
      expect(result).toContain('bc98');
      expect(result).toContain('bc99');
    });

    it('includes correct status labels', () => {
      const result = renderStatusBarChart({
        total: 100,
        unknown: 0,
        statusCounts: { '5': 50, '99': 50 }
      });

      expect(result).toContain('Learned (5)');
      expect(result).toContain('Well Known');
    });

    it('skips statuses with zero count', () => {
      const result = renderStatusBarChart({
        total: 100,
        unknown: 100,
        statusCounts: { '1': 0, '2': 0 }
      });

      expect(result).not.toContain('bc1');
      expect(result).not.toContain('bc2');
    });

    it('calculates correct percentages', () => {
      const result = renderStatusBarChart({
        total: 200,
        unknown: 50,
        statusCounts: { '1': 150 }
      });

      expect(result).toContain('25.00%');  // 50/200
      expect(result).toContain('75.00%');  // 150/200
    });

    it('includes percentage in title attribute', () => {
      const result = renderStatusBarChart({
        total: 100,
        unknown: 30,
        statusCounts: {}
      });

      expect(result).toContain('title="Unknown: 30 (30.0%)"');
    });

    it('wraps segments in status-bar-chart div', () => {
      const result = renderStatusBarChart({
        total: 100,
        unknown: 100,
        statusCounts: {}
      });

      expect(result).toMatch(/^<div class="status-bar-chart">/);
      expect(result).toMatch(/<\/div>$/);
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('escape_html_chars handles consecutive special chars', () => {
      expect(escape_html_chars('<<>>')).toBe('&lt;&lt;&gt;&gt;');
    });

    it('escape_html_chars handles mixed content', () => {
      const input = 'Hello <b>World</b> & "Quotes" \'apostrophe\'';
      const expected = 'Hello &lt;b&gt;World&lt;/b&gt; &amp; &quot;Quotes&quot; &#039;apostrophe&#039;';
      expect(escape_html_chars(input)).toBe(expected);
    });

    it('renderTags handles single comma', () => {
      expect(renderTags(',')).toBe('');
    });

    it('renderTags handles trailing comma', () => {
      const result = renderTags('tag1,tag2,');

      expect((result.match(/<span/g) || []).length).toBe(2);
    });

    it('renderStatusBarChart handles very small percentages', () => {
      const result = renderStatusBarChart({
        total: 10000,
        unknown: 1,
        statusCounts: {}
      });

      expect(result).toContain('0.01%');
    });

    it('renderStatusBarChart handles 100% single status', () => {
      const result = renderStatusBarChart({
        total: 100,
        unknown: 100,
        statusCounts: {}
      });

      expect(result).toContain('100.00%');
    });
  });
});
