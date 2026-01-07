/**
 * Tests for multi-word selection module.
 *
 * @vitest-environment jsdom
 */

import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';

// Mock Alpine.js
const mockStore = {
  loadForEdit: vi.fn()
};

vi.mock('alpinejs', () => ({
  default: {
    store: vi.fn(() => mockStore)
  }
}));

// Mock frame management
vi.mock('../../../src/frontend/js/modules/text/pages/reading/frame_management', () => ({
  loadModalFrame: vi.fn()
}));

import {
  handleTextSelection,
  setupMultiWordSelection,
  mwordDragNDrop,
  mword_drag_n_drop_select,
  mword_touch_select
} from '../../../src/frontend/js/modules/text/pages/reading/text_multiword_selection';

describe('text_multiword_selection.ts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
    // Mock window.location
    Object.defineProperty(window, 'location', {
      value: {
        pathname: '/text/read',
        search: '?start=1'
      },
      writable: true
    });
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('handleTextSelection', () => {
    it('does nothing when selection is empty', () => {
      document.body.innerHTML = `
        <div id="thetext">
          <span id="sent_1">
            <span class="wsty" data_order="1">Hello</span>
            <span class="wsty" data_order="2">World</span>
          </span>
        </div>
      `;
      const container = document.getElementById('thetext')!;

      // Mock empty selection
      const mockSelection = {
        isCollapsed: true,
        rangeCount: 0,
        getRangeAt: vi.fn(),
        removeAllRanges: vi.fn()
      };
      vi.spyOn(window, 'getSelection').mockReturnValue(mockSelection as unknown as Selection);

      handleTextSelection(container);

      expect(mockStore.loadForEdit).not.toHaveBeenCalled();
    });

    it('does nothing when only one word is selected', () => {
      document.body.innerHTML = `
        <div id="thetext">
          <span id="sent_1">
            <span class="wsty" data_order="1">Hello</span>
            <span class="wsty" data_order="2">World</span>
          </span>
        </div>
      `;
      const container = document.getElementById('thetext')!;
      const word1 = container.querySelector('[data_order="1"]')!;

      // Create a mock range that only contains one word
      const mockRange = {
        intersectsNode: (node: Node) => node === word1
      };

      const mockSelection = {
        isCollapsed: false,
        rangeCount: 1,
        getRangeAt: vi.fn().mockReturnValue(mockRange),
        removeAllRanges: vi.fn()
      };
      vi.spyOn(window, 'getSelection').mockReturnValue(mockSelection as unknown as Selection);

      handleTextSelection(container);

      expect(mockStore.loadForEdit).not.toHaveBeenCalled();
    });

    it('opens modal when multiple words are selected', () => {
      // In the real database, words are stored consecutively without space elements
      // The getSelectedText function adds spaces between consecutive word elements
      document.body.innerHTML = `
        <div id="thetext">
          <span id="sent_1">
            <span id="ID-1-1" class="wsty word" data_order="1">Hello</span>
            <span id="ID-2-1" class="wsty word" data_order="2">World</span>
          </span>
        </div>
      `;
      const container = document.getElementById('thetext')!;
      const word1 = container.querySelector('[data_order="1"]')!;
      const word2 = container.querySelector('[data_order="2"]')!;

      // Create a mock range that contains both words
      const mockRange = {
        intersectsNode: (node: Node) => node === word1 || node === word2
      };

      const mockSelection = {
        isCollapsed: false,
        rangeCount: 1,
        getRangeAt: vi.fn().mockReturnValue(mockRange),
        removeAllRanges: vi.fn()
      };
      vi.spyOn(window, 'getSelection').mockReturnValue(mockSelection as unknown as Selection);

      handleTextSelection(container);

      expect(mockStore.loadForEdit).toHaveBeenCalled();
      expect(mockStore.loadForEdit).toHaveBeenCalledWith(
        1, // textId from URL
        1, // position (first word's data_order)
        'Hello World', // text extracted with space added between consecutive words
        2 // word count
      );
    });

    it('shows alert when selected text is too long', () => {
      // Words are stored consecutively without space elements
      document.body.innerHTML = `
        <div id="thetext">
          <span id="sent_1">
            <span id="ID-1-1" class="wsty" data_order="1">${'A'.repeat(200)}</span>
            <span id="ID-2-1" class="wsty" data_order="2">${'B'.repeat(100)}</span>
          </span>
        </div>
      `;
      const container = document.getElementById('thetext')!;
      const word1 = container.querySelector('[data_order="1"]')!;
      const word2 = container.querySelector('[data_order="2"]')!;

      const mockRange = {
        intersectsNode: (node: Node) => node === word1 || node === word2
      };

      const mockSelection = {
        isCollapsed: false,
        rangeCount: 1,
        getRangeAt: vi.fn().mockReturnValue(mockRange),
        removeAllRanges: vi.fn()
      };
      vi.spyOn(window, 'getSelection').mockReturnValue(mockSelection as unknown as Selection);
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      handleTextSelection(container);

      expect(alertSpy).toHaveBeenCalledWith('Selected text is too long!!!');
      expect(mockStore.loadForEdit).not.toHaveBeenCalled();
    });
  });

  describe('setupMultiWordSelection', () => {
    it('attaches mouseup event listener to container', () => {
      document.body.innerHTML = '<div id="thetext"></div>';
      const container = document.getElementById('thetext')!;
      const addEventListenerSpy = vi.spyOn(container, 'addEventListener');

      setupMultiWordSelection(container);

      expect(addEventListenerSpy).toHaveBeenCalledWith('mouseup', expect.any(Function));
    });
  });

  describe('legacy exports', () => {
    it('exports mwordDragNDrop for backwards compatibility', () => {
      expect(mwordDragNDrop).toBeDefined();
      expect(mwordDragNDrop.context).toBeUndefined();
      expect(typeof mwordDragNDrop.stopInteraction).toBe('function');
    });

    it('exports mword_drag_n_drop_select for backwards compatibility', () => {
      expect(typeof mword_drag_n_drop_select).toBe('function');
      // Should be a no-op
      mword_drag_n_drop_select();
    });

    it('exports mword_touch_select for backwards compatibility', () => {
      expect(typeof mword_touch_select).toBe('function');
      // Should be a no-op
      mword_touch_select();
    });
  });

  describe('getTextIdFromUrl', () => {
    it('extracts text ID from query parameter', () => {
      Object.defineProperty(window, 'location', {
        value: {
          pathname: '/text/read',
          search: '?start=42'
        },
        writable: true
      });

      // Words are stored consecutively without space elements
      document.body.innerHTML = `
        <div id="thetext">
          <span id="sent_1">
            <span id="ID-1-1" class="wsty" data_order="1">Hello</span>
            <span id="ID-2-1" class="wsty" data_order="2">World</span>
          </span>
        </div>
      `;
      const container = document.getElementById('thetext')!;
      const word1 = container.querySelector('[data_order="1"]')!;
      const word2 = container.querySelector('[data_order="2"]')!;

      const mockRange = {
        intersectsNode: (node: Node) => node === word1 || node === word2
      };

      const mockSelection = {
        isCollapsed: false,
        rangeCount: 1,
        getRangeAt: vi.fn().mockReturnValue(mockRange),
        removeAllRanges: vi.fn()
      };
      vi.spyOn(window, 'getSelection').mockReturnValue(mockSelection as unknown as Selection);

      handleTextSelection(container);

      expect(mockStore.loadForEdit).toHaveBeenCalledWith(
        42, // textId extracted from URL
        1,
        'Hello World',
        2
      );
    });
  });
});
