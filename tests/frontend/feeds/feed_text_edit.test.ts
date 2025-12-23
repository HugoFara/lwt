/**
 * Tests for feed_text_edit_component.ts - Feed text edit Alpine component
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

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

import {
  feedTextEditData,
  type FeedTextEditConfig
} from '../../../src/frontend/js/feeds/components/feed_text_edit_component';

describe('feed_text_edit_component.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // feedTextEditData Factory Function Tests
  // ===========================================================================

  describe('feedTextEditData', () => {
    it('creates component with default values', () => {
      const component = feedTextEditData();

      expect(component.scrollToTable).toBe(true);
      expect(component.initialized).toBe(false);
    });

    it('creates component with provided config values', () => {
      const config: FeedTextEditConfig = {
        scrollToTable: false
      };

      const component = feedTextEditData(config);

      expect(component.scrollToTable).toBe(false);
    });
  });

  // ===========================================================================
  // init() Method Tests
  // ===========================================================================

  describe('init()', () => {
    it('scrolls to first table when present and scrollToTable is true', async () => {
      document.body.innerHTML = `
        <div id="container">
          <div style="height: 2000px;">Spacer</div>
          <table id="firstTable">
            <tr><td>Content</td></tr>
          </table>
        </div>
      `;

      const component = feedTextEditData({ scrollToTable: true });
      const container = document.getElementById('container')!;
      (component as unknown as { $el: HTMLElement }).$el = container;

      const scrollIntoViewMock = vi.fn();
      const table = container.querySelector('table')!;
      table.scrollIntoView = scrollIntoViewMock;

      await component.init.call(component as any);

      expect(scrollIntoViewMock).toHaveBeenCalledWith({ behavior: 'instant', block: 'start' });
    });

    it('does not scroll when scrollToTable is false', async () => {
      document.body.innerHTML = `
        <div id="container">
          <table id="firstTable">
            <tr><td>Content</td></tr>
          </table>
        </div>
      `;

      const component = feedTextEditData({ scrollToTable: false });
      const container = document.getElementById('container')!;
      (component as unknown as { $el: HTMLElement }).$el = container;

      const scrollIntoViewMock = vi.fn();
      const table = container.querySelector('table')!;
      table.scrollIntoView = scrollIntoViewMock;

      await component.init.call(component as any);

      expect(scrollIntoViewMock).not.toHaveBeenCalled();
    });

    it('does not throw when no table present', async () => {
      document.body.innerHTML = '<div id="container">No table here</div>';

      const component = feedTextEditData();
      const container = document.getElementById('container')!;
      (component as unknown as { $el: HTMLElement }).$el = container;

      await expect(component.init.call(component as any)).resolves.not.toThrow();
    });

    it('reads config from JSON script tag', async () => {
      document.body.innerHTML = `
        <div id="container">
          <script type="application/json" id="feed-text-edit-config">
            {"scrollToTable": false}
          </script>
        </div>
      `;

      const component = feedTextEditData();
      const container = document.getElementById('container')!;
      (component as unknown as { $el: HTMLElement }).$el = container;

      await component.init.call(component as any);

      expect(component.scrollToTable).toBe(false);
    });

    it('sets initialized to true after init', async () => {
      document.body.innerHTML = '<div id="container"></div>';

      const component = feedTextEditData();
      const container = document.getElementById('container')!;
      (component as unknown as { $el: HTMLElement }).$el = container;

      await component.init.call(component as any);

      expect(component.initialized).toBe(true);
    });
  });

  // ===========================================================================
  // initTagifyOnFeedInput() Tests
  // ===========================================================================

  describe('initTagifyOnFeedInput()', () => {
    it('replaces UL with input element', () => {
      document.body.innerHTML = `
        <ul name="feed[0][TxTags]">
          <li>tag1</li>
          <li>tag2</li>
        </ul>
      `;

      const component = feedTextEditData();
      const ul = document.querySelector<HTMLUListElement>('ul')!;

      component.initTagifyOnFeedInput(ul);

      // UL should be replaced with input
      const input = document.querySelector<HTMLInputElement>('.tagify-feed-input');
      expect(input).toBeTruthy();
      expect(document.querySelector('ul')).toBeNull();
    });

    it('creates input with correct attributes', () => {
      document.body.innerHTML = `
        <ul name="feed[5][TxTags]">
          <li>test</li>
        </ul>
      `;

      const component = feedTextEditData();
      const ul = document.querySelector<HTMLUListElement>('ul')!;

      component.initTagifyOnFeedInput(ul);

      const input = document.querySelector<HTMLInputElement>('.tagify-feed-input');
      expect(input?.name).toBe('feed[5][TxTags]');
      expect(input?.dataset.feedIndex).toBe('5');
    });

    it('extracts existing tags from LI elements', () => {
      document.body.innerHTML = `
        <ul name="feed[1][TxTags]">
          <li>first tag</li>
          <li>second tag</li>
          <li>third tag</li>
        </ul>
      `;

      const component = feedTextEditData();
      const ul = document.querySelector<HTMLUListElement>('ul')!;

      component.initTagifyOnFeedInput(ul);

      const input = document.querySelector<HTMLInputElement>('.tagify-feed-input');
      expect(input?.value).toBe('first tag, second tag, third tag');
    });

    it('handles empty UL elements', () => {
      document.body.innerHTML = `
        <ul name="feed[2][TxTags]"></ul>
      `;

      const component = feedTextEditData();
      const ul = document.querySelector<HTMLUListElement>('ul')!;

      component.initTagifyOnFeedInput(ul);

      const input = document.querySelector<HTMLInputElement>('.tagify-feed-input');
      expect(input).toBeTruthy();
      expect(input?.value).toBe('');
    });

    it('ignores UL without name attribute', () => {
      document.body.innerHTML = `
        <ul>
          <li>ignored</li>
        </ul>
      `;

      const component = feedTextEditData();
      const ul = document.querySelector<HTMLUListElement>('ul')!;

      component.initTagifyOnFeedInput(ul);

      // UL should still be there
      expect(document.querySelector('ul')).toBeTruthy();
      expect(document.querySelector('.tagify-feed-input')).toBeNull();
    });

    it('handles whitespace in tag text', () => {
      document.body.innerHTML = `
        <ul name="feed[0][TxTags]">
          <li>  spaced tag  </li>
          <li>normal tag</li>
        </ul>
      `;

      const component = feedTextEditData();
      const ul = document.querySelector<HTMLUListElement>('ul')!;

      component.initTagifyOnFeedInput(ul);

      const input = document.querySelector<HTMLInputElement>('.tagify-feed-input');
      expect(input?.value).toContain('spaced tag');
    });
  });

  // ===========================================================================
  // handleFeedCheckboxChange() Tests
  // ===========================================================================

  describe('handleFeedCheckboxChange()', () => {
    it('enables form fields when checkbox is checked', () => {
      document.body.innerHTML = `
        <input type="checkbox" value="0" checked>
        <input type="text" name="feed[0][TxTitle]" disabled>
        <input type="text" name="feed[0][TxText]" disabled>
      `;

      const component = feedTextEditData();
      const checkbox = document.querySelector<HTMLInputElement>('input[type="checkbox"]')!;
      const event = { target: checkbox } as unknown as Event;

      component.handleFeedCheckboxChange(event);

      const titleInput = document.querySelector<HTMLInputElement>('[name="feed[0][TxTitle]"]')!;
      const textInput = document.querySelector<HTMLInputElement>('[name="feed[0][TxText]"]')!;
      expect(titleInput.disabled).toBe(false);
      expect(textInput.disabled).toBe(false);
    });

    it('disables form fields when checkbox is unchecked', () => {
      document.body.innerHTML = `
        <input type="checkbox" value="0">
        <input type="text" name="feed[0][TxTitle]">
        <input type="text" name="feed[0][TxText]">
      `;

      const component = feedTextEditData();
      const checkbox = document.querySelector<HTMLInputElement>('input[type="checkbox"]')!;
      checkbox.checked = false;
      const event = { target: checkbox } as unknown as Event;

      component.handleFeedCheckboxChange(event);

      const titleInput = document.querySelector<HTMLInputElement>('[name="feed[0][TxTitle]"]')!;
      expect(titleInput.disabled).toBe(true);
    });

    it('adds notempty class to title and text fields when enabled', () => {
      document.body.innerHTML = `
        <input type="checkbox" value="0" checked>
        <input type="text" name="feed[0][TxTitle]" disabled>
        <textarea name="feed[0][TxText]" disabled></textarea>
      `;

      const component = feedTextEditData();
      const checkbox = document.querySelector<HTMLInputElement>('input[type="checkbox"]')!;
      const event = { target: checkbox } as unknown as Event;

      component.handleFeedCheckboxChange(event);

      const titleInput = document.querySelector<HTMLInputElement>('[name="feed[0][TxTitle]"]')!;
      const textArea = document.querySelector<HTMLTextAreaElement>('[name="feed[0][TxText]"]')!;
      expect(titleInput.classList.contains('notempty')).toBe(true);
      expect(textArea.classList.contains('notempty')).toBe(true);
    });

    it('removes notempty class when disabled', () => {
      document.body.innerHTML = `
        <input type="checkbox" value="0">
        <input type="text" name="feed[0][TxTitle]" class="notempty">
      `;

      const component = feedTextEditData();
      const checkbox = document.querySelector<HTMLInputElement>('input[type="checkbox"]')!;
      checkbox.checked = false;
      const event = { target: checkbox } as unknown as Event;

      component.handleFeedCheckboxChange(event);

      const titleInput = document.querySelector<HTMLInputElement>('[name="feed[0][TxTitle]"]')!;
      expect(titleInput.classList.contains('notempty')).toBe(false);
    });

    it('handles multiple checkboxes for different feeds', () => {
      document.body.innerHTML = `
        <input type="checkbox" value="0" checked>
        <input type="checkbox" value="1">
        <input type="text" name="feed[0][TxTitle]">
        <input type="text" name="feed[1][TxTitle]" disabled>
      `;

      const component = feedTextEditData();

      // Toggle first checkbox off
      const checkbox0 = document.querySelector<HTMLInputElement>('input[value="0"]')!;
      checkbox0.checked = false;
      component.handleFeedCheckboxChange({ target: checkbox0 } as unknown as Event);
      expect(document.querySelector<HTMLInputElement>('[name="feed[0][TxTitle]"]')?.disabled).toBe(true);

      // Toggle second checkbox on
      const checkbox1 = document.querySelector<HTMLInputElement>('input[value="1"]')!;
      checkbox1.checked = true;
      component.handleFeedCheckboxChange({ target: checkbox1 } as unknown as Event);
      expect(document.querySelector<HTMLInputElement>('[name="feed[1][TxTitle]"]')?.disabled).toBe(false);
    });
  });
});
