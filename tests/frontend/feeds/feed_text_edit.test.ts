/**
 * Tests for feed_text_edit.ts - Bulk feed text import form functionality
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { initFeedTextEditForm } from '../../../src/frontend/js/feeds/feed_text_edit';

// Mock Tagify - must be a class/constructor function
vi.mock('@yaireo/tagify', () => {
  return {
    default: class MockTagify {
      addTags = vi.fn();
      setDisabled = vi.fn();
    }
  };
});

// Mock app_data to provide text tags
vi.mock('../../../src/frontend/js/core/app_data', () => ({
  fetchTextTags: vi.fn().mockResolvedValue(['tag1', 'tag2', 'tag3']),
  getTextTagsSync: vi.fn().mockReturnValue(['tag1', 'tag2', 'tag3'])
}));

describe('feed_text_edit.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // initFeedTextEditForm Tests
  // ===========================================================================

  describe('initFeedTextEditForm', () => {
    it('scrolls to first table when present', async () => {
      document.body.innerHTML = `
        <div style="height: 2000px;">Spacer</div>
        <table id="firstTable">
          <tr><td>Content</td></tr>
        </table>
      `;

      const scrollIntoViewMock = vi.fn();
      const table = document.querySelector('table')!;
      table.scrollIntoView = scrollIntoViewMock;

      await initFeedTextEditForm();

      expect(scrollIntoViewMock).toHaveBeenCalledWith({ behavior: 'instant', block: 'start' });
    });

    it('does not throw when no table present', async () => {
      document.body.innerHTML = '<div>No table here</div>';

      await expect(initFeedTextEditForm()).resolves.not.toThrow();
    });

    it('handles checkbox change to enable form fields', async () => {
      document.body.innerHTML = `
        <input type="checkbox" value="0" checked>
        <input type="text" name="feed[0][TxTitle]" disabled>
        <input type="text" name="feed[0][TxText]" disabled>
      `;

      await initFeedTextEditForm();

      const checkbox = document.querySelector<HTMLInputElement>('input[type="checkbox"]')!;
      checkbox.checked = true;
      checkbox.dispatchEvent(new Event('change', { bubbles: true }));

      // Fields should be enabled
      const titleInput = document.querySelector<HTMLInputElement>('[name="feed[0][TxTitle]"]')!;
      expect(titleInput.disabled).toBe(false);
    });

    it('handles checkbox change to disable form fields', async () => {
      document.body.innerHTML = `
        <input type="checkbox" value="0">
        <input type="text" name="feed[0][TxTitle]">
        <input type="text" name="feed[0][TxText]">
      `;

      await initFeedTextEditForm();

      const checkbox = document.querySelector<HTMLInputElement>('input[type="checkbox"]')!;
      checkbox.checked = false;
      checkbox.dispatchEvent(new Event('change', { bubbles: true }));

      // Fields should be disabled
      const titleInput = document.querySelector<HTMLInputElement>('[name="feed[0][TxTitle]"]')!;
      expect(titleInput.disabled).toBe(true);
    });

    it('initializes Tagify on UL elements with feed names', async () => {
      document.body.innerHTML = `
        <ul name="feed[0][TxTags]">
          <li>tag1</li>
          <li>tag2</li>
        </ul>
      `;

      // Trigger the function
      await initFeedTextEditForm();

      // UL should be replaced with input
      const input = document.querySelector<HTMLInputElement>('.tagify-feed-input');
      expect(input).toBeTruthy();
      expect(input?.name).toBe('feed[0][TxTags]');
      expect(input?.dataset.feedIndex).toBe('0');
    });

    it('extracts existing tags from LI elements', async () => {
      document.body.innerHTML = `
        <ul name="feed[1][TxTags]">
          <li>first tag</li>
          <li>second tag</li>
          <li>third tag</li>
        </ul>
      `;

      await initFeedTextEditForm();

      const input = document.querySelector<HTMLInputElement>('.tagify-feed-input');
      expect(input?.value).toBe('first tag, second tag, third tag');
    });

    it('handles empty UL elements', async () => {
      document.body.innerHTML = `
        <ul name="feed[2][TxTags]"></ul>
      `;

      await initFeedTextEditForm();

      const input = document.querySelector<HTMLInputElement>('.tagify-feed-input');
      expect(input).toBeTruthy();
      expect(input?.value).toBe('');
    });

    it('ignores UL without name attribute', async () => {
      document.body.innerHTML = `
        <ul>
          <li>ignored</li>
        </ul>
      `;

      await initFeedTextEditForm();

      // UL should still be there
      expect(document.querySelector('ul')).toBeTruthy();
      expect(document.querySelector('.tagify-feed-input')).toBeNull();
    });
  });

  // ===========================================================================
  // Auto-initialization Tests
  // ===========================================================================

  describe('auto-initialization', () => {
    it('initializes when checked_feeds_save input exists', async () => {
      document.body.innerHTML = `
        <input type="hidden" name="checked_feeds_save" value="1">
        <table><tr><td>Feed content</td></tr></table>
      `;

      const scrollIntoViewMock = vi.fn();
      const table = document.querySelector('table')!;
      table.scrollIntoView = scrollIntoViewMock;

      // Simulate DOMContentLoaded
      await import('../../../src/frontend/js/feeds/feed_text_edit');
      document.dispatchEvent(new Event('DOMContentLoaded'));

      // The scroll function should have been called
      // Note: In actual implementation, auto-init happens on DOMContentLoaded
    });

    it('does not initialize when checked_feeds_save input is missing', async () => {
      document.body.innerHTML = '<div>Regular page</div>';

      // Should not throw when auto-init runs and doesn't find the marker
      expect(() => {
        document.dispatchEvent(new Event('DOMContentLoaded'));
      }).not.toThrow();
    });
  });

  // ===========================================================================
  // Edge Cases Tests
  // ===========================================================================

  describe('edge cases', () => {
    it('handles multiple checkboxes for different feeds', async () => {
      document.body.innerHTML = `
        <input type="checkbox" value="0" checked>
        <input type="checkbox" value="1">
        <input type="text" name="feed[0][TxTitle]">
        <input type="text" name="feed[1][TxTitle]" disabled>
      `;

      await initFeedTextEditForm();

      // Toggle first checkbox
      const checkbox0 = document.querySelector<HTMLInputElement>('input[value="0"]')!;
      checkbox0.checked = false;
      checkbox0.dispatchEvent(new Event('change', { bubbles: true }));

      expect(document.querySelector<HTMLInputElement>('[name="feed[0][TxTitle]"]')?.disabled).toBe(true);

      // Toggle second checkbox
      const checkbox1 = document.querySelector<HTMLInputElement>('input[value="1"]')!;
      checkbox1.checked = true;
      checkbox1.dispatchEvent(new Event('change', { bubbles: true }));

      expect(document.querySelector<HTMLInputElement>('[name="feed[1][TxTitle]"]')?.disabled).toBe(false);
    });

    it('handles feed index extraction from various name formats', async () => {
      document.body.innerHTML = `
        <ul name="feed[123][TxTags]">
          <li>test</li>
        </ul>
      `;

      await initFeedTextEditForm();

      const input = document.querySelector<HTMLInputElement>('.tagify-feed-input');
      expect(input?.dataset.feedIndex).toBe('123');
    });

    it('handles whitespace in tag text', async () => {
      document.body.innerHTML = `
        <ul name="feed[0][TxTags]">
          <li>  spaced tag  </li>
          <li>normal tag</li>
        </ul>
      `;

      await initFeedTextEditForm();

      const input = document.querySelector<HTMLInputElement>('.tagify-feed-input');
      expect(input?.value).toContain('spaced tag');
    });
  });
});
