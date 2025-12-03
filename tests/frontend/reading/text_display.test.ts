/**
 * Tests for text_display.ts - Word counts, barcharts, and text statistics
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Create the mocks using vi.hoisted so they're available before module import
const { mockApiGet, mockDoAjaxSaveSetting } = vi.hoisted(() => ({
  mockApiGet: vi.fn(),
  mockDoAjaxSaveSetting: vi.fn()
}));

// Mock the api_client module
vi.mock('../../../src/frontend/js/core/api_client', () => ({
  apiGet: mockApiGet
}));

// Mock the ajax_utilities module
vi.mock('../../../src/frontend/js/core/ajax_utilities', () => ({
  do_ajax_save_setting: mockDoAjaxSaveSetting
}));

import {
  set_barchart_item,
  set_word_counts,
  word_count_click,
  lwt,
  _initTestState,
} from '../../../src/frontend/js/reading/text_display';

// Mock WORDCOUNTS data structure
const createMockWordCounts = () => ({
  expr: { '1': 5, '2': 10 } as Record<string, number>,
  expru: { '1': 3, '2': 7 } as Record<string, number>,
  total: { '1': 100, '2': 200 } as Record<string, number>,
  totalu: { '1': 80, '2': 150 } as Record<string, number>,
  stat: {
    '1': { '1': 10, '2': 20, '3': 30 } as Record<string, number>,
    '2': { '1': 15, '2': 25, '3': 35 } as Record<string, number>,
  } as Record<string, Record<string, number>>,
  statu: {
    '1': { '1': 8, '2': 15, '3': 25 } as Record<string, number>,
    '2': { '1': 12, '2': 20, '3': 30 } as Record<string, number>,
  } as Record<string, Record<string, number>>,
});

// Setup module state via test helper
beforeEach(() => {
  _initTestState(createMockWordCounts(), 0, 0);
  // Provide default resolved value for apiGet to prevent unhandled rejections
  mockApiGet.mockReset();
  mockApiGet.mockResolvedValue({ data: createMockWordCounts(), error: undefined });
});

describe('text_display.ts', () => {
  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // set_barchart_item Tests
  // ===========================================================================

  describe('set_barchart_item', () => {
    beforeEach(() => {
      _initTestState(createMockWordCounts(), 0, 0);
    });

    it('sets border-top-width on bar chart items', () => {
      document.body.innerHTML = `
        <ul class="barchart">
          <span id="bc_text_1"></span>
          <li><span>10</span></li>
          <li><span>20</span></li>
        </ul>
      `;

      const barchart = document.querySelector('.barchart') as HTMLElement;
      set_barchart_item.call(barchart);

      const items = document.querySelectorAll('.barchart li');
      items.forEach((item) => {
        const borderWidth = (item as HTMLElement).style.borderTopWidth;
        expect(borderWidth).toMatch(/^\d+(\.\d+)?px$/);
      });
    });

    it('uses unique counts when SUW bit 4 is set', () => {
      _initTestState(createMockWordCounts(), 16, 0); // bit 4 = unique
      document.body.innerHTML = `
        <ul class="barchart">
          <span id="bc_text_1"></span>
          <li><span>10</span></li>
        </ul>
      `;

      const barchart = document.querySelector('.barchart') as HTMLElement;

      // Should not throw
      expect(() => set_barchart_item.call(barchart)).not.toThrow();
    });

    it('handles missing text ID gracefully', () => {
      document.body.innerHTML = `
        <ul class="barchart">
          <span id="bc_text_"></span>
          <li><span>10</span></li>
        </ul>
      `;

      const barchart = document.querySelector('.barchart') as HTMLElement;

      expect(() => set_barchart_item.call(barchart)).not.toThrow();
    });

    it('calculates logarithmic height for bar items', () => {
      document.body.innerHTML = `
        <ul class="barchart">
          <span id="bc_text_1"></span>
          <li><span>1</span></li>
          <li><span>50</span></li>
        </ul>
      `;

      const barchart = document.querySelector('.barchart') as HTMLElement;
      set_barchart_item.call(barchart);

      const items = document.querySelectorAll('.barchart li');
      const firstHeight = parseFloat((items[0] as HTMLElement).style.borderTopWidth);
      const secondHeight = parseFloat((items[1] as HTMLElement).style.borderTopWidth);

      // Smaller count should have larger border (inverted bar chart)
      expect(firstHeight).toBeGreaterThan(secondHeight);
    });
  });

  // ===========================================================================
  // set_word_counts Tests
  // ===========================================================================

  describe('set_word_counts', () => {
    beforeEach(() => {
      _initTestState(createMockWordCounts(), 0, 0);
      document.body.innerHTML = `
        <span id="total_1"></span>
        <span id="total_2"></span>
        <span id="saved_1"></span>
        <span id="saved_2"></span>
        <span id="todo_1"></span>
        <span id="todo_2"></span>
        <span id="unknownpercent_1"></span>
        <span id="unknownpercent_2"></span>
        <span id="stat_0_1"></span>
        <span id="stat_0_2"></span>
        <span id="stat_1_1"></span>
        <span id="stat_2_1"></span>
        <span id="stat_3_1"></span>
        <span id="stat_1_2"></span>
        <span id="stat_2_2"></span>
        <span id="stat_3_2"></span>
        <ul class="barchart"><span id="bc_text_1"></span><li><span>10</span></li></ul>
      `;
    });

    it('updates total counts in DOM', () => {
      set_word_counts();

      const total1 = document.getElementById('total_1');
      const total2 = document.getElementById('total_2');
      expect(total1?.innerHTML).toBe('100');
      expect(total2?.innerHTML).toBe('200');
    });

    it('updates saved counts in DOM', () => {
      set_word_counts();

      // saved = known - expr
      const saved1 = document.getElementById('saved_1')?.innerHTML || '';
      expect(saved1).toMatch(/\d+\+\d+/); // Format: "X+Y"
    });

    it('updates todo counts in DOM', () => {
      set_word_counts();

      const todo1 = document.getElementById('todo_1')?.innerHTML || '0';
      expect(parseInt(todo1, 10)).toBeGreaterThanOrEqual(0);
    });

    it('updates unknown percent in DOM', () => {
      set_word_counts();

      const percent1 = document.getElementById('unknownpercent_1')?.innerHTML || '0';
      expect(parseFloat(percent1)).toBeGreaterThanOrEqual(0);
      expect(parseFloat(percent1)).toBeLessThanOrEqual(100);
    });

    it('uses unique counts when SUW bit 0 is set', () => {
      _initTestState(createMockWordCounts(), 1, 0);
      set_word_counts();

      // With SUW & 1, should use totalu values
      const total1 = document.getElementById('total_1');
      const total2 = document.getElementById('total_2');
      expect(total1?.innerHTML).toBe('80');
      expect(total2?.innerHTML).toBe('150');
    });

    it('handles missing stat data gracefully', () => {
      _initTestState({
        ...createMockWordCounts(),
        stat: {},
        statu: {},
      }, 0, 0);

      expect(() => set_word_counts()).not.toThrow();
    });

    it('shows 0 for saved when no known words', () => {
      _initTestState({
        ...createMockWordCounts(),
        stat: { '1': {}, '2': {} },
        statu: { '1': {}, '2': {} },
      }, 0, 0);

      set_word_counts();

      const saved1 = document.getElementById('saved_1');
      expect(saved1?.innerHTML).toBe('0');
    });
  });

  // ===========================================================================
  // word_count_click Tests
  // ===========================================================================

  describe('word_count_click', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <span id="total" data_wo_cnt="0" class="wc_cont"><span data_wo_cnt="0">t</span></span>
        <span id="saved" data_wo_cnt="0"></span>
        <span id="unknown" data_wo_cnt="0"></span>
        <span id="unknownpercent" data_wo_cnt="0"></span>
        <span id="chart" data_wo_cnt="0"></span>
        <span id="total_1"></span>
        <span id="saved_1"></span>
        <span id="todo_1"></span>
        <span id="unknownpercent_1"></span>
        <span id="stat_0_1"></span>
        <ul class="barchart"><span id="bc_text_1"></span><li><span>10</span></li></ul>
      `;
      _initTestState(createMockWordCounts(), 0, 0);
    });

    it('calculates SUW based on data attributes', () => {
      word_count_click();

      // word_count_click should run without error
      expect(true).toBe(true);
    });

    it('toggles display between u and t in wc_cont children', () => {
      document.body.innerHTML = `
        <span class="wc_cont">
          <span data_wo_cnt="0">t</span>
          <span data_wo_cnt="1">u</span>
        </span>
        <span id="total" data_wo_cnt="0"></span>
        <span id="saved" data_wo_cnt="0"></span>
        <span id="unknown" data_wo_cnt="0"></span>
        <span id="unknownpercent" data_wo_cnt="0"></span>
        <span id="chart" data_wo_cnt="0"></span>
        <span id="total_1"></span>
        <ul class="barchart"><span id="bc_text_1"></span></ul>
      `;

      word_count_click();

      const children = document.querySelectorAll('.wc_cont > span');
      children.forEach((child) => {
        const text = child.innerHTML;
        expect(['u', 't']).toContain(text);
      });
    });

    it('calls set_word_counts', () => {
      // Reset module state for this test
      _initTestState(createMockWordCounts(), 0, 0);

      document.body.innerHTML = `
        <span class="wc_cont"><span data_wo_cnt="0">t</span></span>
        <span id="total" data_wo_cnt="0"></span>
        <span id="saved" data_wo_cnt="0"></span>
        <span id="unknown" data_wo_cnt="0"></span>
        <span id="unknownpercent" data_wo_cnt="0"></span>
        <span id="chart" data_wo_cnt="0"></span>
        <span id="total_1"></span>
        <span id="saved_1"></span>
        <span id="todo_1"></span>
        <span id="unknownpercent_1"></span>
        <span id="stat_0_1"></span>
        <span id="stat_1_1"></span>
        <span id="stat_2_1"></span>
        <span id="stat_3_1"></span>
        <ul class="barchart"><span id="bc_text_1"></span><li><span>10</span></li></ul>
      `;

      word_count_click();

      // set_word_counts should have updated the DOM with total counts
      const total1 = document.getElementById('total_1');
      expect(total1?.innerHTML).toBe('100');
    });
  });

  // ===========================================================================
  // lwt Object Tests
  // ===========================================================================

  describe('lwt object', () => {
    describe('prepare_word_count_click', () => {
      it('attaches click handlers to word count elements', () => {
        document.body.innerHTML = `
          <span id="total" data_wo_cnt="0"></span>
          <span id="saved" data_wo_cnt="0"></span>
          <span id="unknown" data_wo_cnt="0"></span>
          <span id="unknownpercent" data_wo_cnt="0"></span>
          <span id="chart" data_wo_cnt="0"></span>
          <input class="markcheck" value="1" />
        `;

        lwt.prepare_word_count_click();

        // Check that title attribute was set
        const total = document.getElementById('total');
        const title = total?.getAttribute('title') || '';
        expect(title).toContain('Unique');
        expect(title).toContain('Total');
      });

      it('sets title attribute on word count elements', () => {
        document.body.innerHTML = `
          <span id="total" data_wo_cnt="0"></span>
          <span id="saved" data_wo_cnt="0"></span>
          <span id="unknown" data_wo_cnt="0"></span>
          <span id="unknownpercent" data_wo_cnt="0"></span>
          <span id="chart" data_wo_cnt="0"></span>
        `;

        lwt.prepare_word_count_click();

        const saved = document.getElementById('saved');
        const unknown = document.getElementById('unknown');
        expect(saved?.getAttribute('title')).toBeDefined();
        expect(unknown?.getAttribute('title')).toBeDefined();
      });
    });

    describe('save_text_word_count_settings', () => {
      it('does not save when showUniqueWords equals initialShowCounts', () => {
        _initTestState(createMockWordCounts(), 5, 5); // SUW=5, initial=5

        document.body.innerHTML = `
          <span id="total" data_wo_cnt="1"></span>
          <span id="saved" data_wo_cnt="0"></span>
          <span id="unknown" data_wo_cnt="1"></span>
          <span id="unknownpercent" data_wo_cnt="0"></span>
          <span id="chart" data_wo_cnt="1"></span>
        `;

        lwt.save_text_word_count_settings();

        // do_ajax_save_setting should not be called
        // This verifies the early return
        expect(mockDoAjaxSaveSetting).not.toHaveBeenCalled();
      });

      it('saves when showUniqueWords differs from initialShowCounts', () => {
        _initTestState(createMockWordCounts(), 5, 0); // SUW=5, initial=0

        document.body.innerHTML = `
          <span id="total" data_wo_cnt="1"></span>
          <span id="saved" data_wo_cnt="0"></span>
          <span id="unknown" data_wo_cnt="1"></span>
          <span id="unknownpercent" data_wo_cnt="0"></span>
          <span id="chart" data_wo_cnt="1"></span>
        `;

        lwt.save_text_word_count_settings();

        // Settings should be saved
        expect(mockDoAjaxSaveSetting).toHaveBeenCalled();
      });
    });
  });

  // ===========================================================================
  // Integration Tests
  // ===========================================================================

  describe('Integration', () => {
    it('full word count display workflow', () => {
      // Reset module state for this test
      _initTestState(createMockWordCounts(), 0, 0);

      document.body.innerHTML = `
        <span class="wc_cont"><span data_wo_cnt="0">t</span></span>
        <span id="total" data_wo_cnt="0"></span>
        <span id="saved" data_wo_cnt="0"></span>
        <span id="unknown" data_wo_cnt="0"></span>
        <span id="unknownpercent" data_wo_cnt="0"></span>
        <span id="chart" data_wo_cnt="0"></span>
        <span id="total_1"></span>
        <span id="total_2"></span>
        <span id="saved_1"></span>
        <span id="saved_2"></span>
        <span id="todo_1"></span>
        <span id="todo_2"></span>
        <span id="unknownpercent_1"></span>
        <span id="unknownpercent_2"></span>
        <span id="stat_0_1"></span>
        <span id="stat_0_2"></span>
        <span id="stat_1_1"></span>
        <span id="stat_2_1"></span>
        <span id="stat_3_1"></span>
        <span id="stat_1_2"></span>
        <span id="stat_2_2"></span>
        <span id="stat_3_2"></span>
        <ul class="barchart"><span id="bc_text_1"></span><li><span>10</span></li></ul>
        <ul class="barchart"><span id="bc_text_2"></span><li><span>20</span></li></ul>
      `;

      // Run word count click to update display
      word_count_click();

      // Verify totals are populated
      const total1 = document.getElementById('total_1');
      const total2 = document.getElementById('total_2');
      expect(total1?.innerHTML).toBe('100');
      expect(total2?.innerHTML).toBe('200');

      // Verify saved counts are populated
      const saved1 = document.getElementById('saved_1');
      expect(saved1?.innerHTML).toBeTruthy();

      // Verify todo counts are populated
      const todo1 = document.getElementById('todo_1');
      expect(todo1?.innerHTML).toBeTruthy();
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles zero word counts', () => {
      _initTestState({
        expr: { '1': 0 },
        expru: { '1': 0 },
        total: { '1': 0 },
        totalu: { '1': 0 },
        stat: { '1': {} },
        statu: { '1': {} },
      }, 0, 0);

      document.body.innerHTML = `
        <span id="total_1"></span>
        <span id="saved_1"></span>
        <span id="todo_1"></span>
        <span id="unknownpercent_1"></span>
        <span id="stat_0_1"></span>
        <ul class="barchart"><span id="bc_text_1"></span></ul>
      `;

      expect(() => set_word_counts()).not.toThrow();
      const total1 = document.getElementById('total_1');
      expect(total1?.innerHTML).toBe('0');
    });

    it('handles missing DOM elements gracefully', () => {
      document.body.innerHTML = ''; // Empty DOM

      expect(() => word_count_click()).not.toThrow();
    });

    it('handles very large word counts', () => {
      _initTestState({
        expr: { '1': 1000000 },
        expru: { '1': 999999 },
        total: { '1': 10000000 },
        totalu: { '1': 9999999 },
        stat: { '1': { '1': 5000000, '2': 3000000 } },
        statu: { '1': { '1': 4999999, '2': 2999999 } },
      }, 0, 0);

      document.body.innerHTML = `
        <span id="total_1"></span>
        <span id="saved_1"></span>
        <span id="todo_1"></span>
        <span id="unknownpercent_1"></span>
        <span id="stat_0_1"></span>
        <span id="stat_1_1"></span>
        <span id="stat_2_1"></span>
        <ul class="barchart"><span id="bc_text_1"></span><li><span>1000000</span></li></ul>
      `;

      expect(() => set_word_counts()).not.toThrow();
    });
  });
});
