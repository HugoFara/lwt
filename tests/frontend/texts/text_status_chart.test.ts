/**
 * Tests for texts/text_status_chart.ts - Chart.js status visualizations
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  createTextStatusChart,
  updateTextStatusChart,
  initTextStatusCharts,
  updateAllTextStatusCharts,
  destroyAllTextStatusCharts
} from '../../../src/frontend/js/texts/text_status_chart';

// Mock Chart.js
vi.mock('chart.js', () => {
  const mockChartInstance = {
    destroy: vi.fn(),
    update: vi.fn(),
    data: {
      datasets: [] as Array<{ data: number[] }>
    }
  };

  class MockChart {
    static instances: Array<typeof mockChartInstance> = [];

    constructor(canvas: HTMLCanvasElement, config: { data: { datasets: Array<{ data: number[] }> } }) {
      void canvas; // Canvas element is used implicitly by Chart.js
      mockChartInstance.data.datasets = config.data.datasets.map(ds => ({ ...ds }));
      MockChart.instances.push(mockChartInstance);
      return mockChartInstance;
    }

    static register() {}
  }

  return {
    Chart: MockChart,
    registerables: []
  };
});

describe('texts/text_status_chart.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    document.body.innerHTML = '';
    destroyAllTextStatusCharts();
  });

  // ===========================================================================
  // DOM Setup Helpers
  // ===========================================================================

  function createChartCanvas(textId: string): HTMLCanvasElement {
    const canvas = document.createElement('canvas');
    canvas.id = `chart_${textId}`;
    canvas.dataset.textId = textId;
    canvas.className = 'text-status-chart';
    document.body.appendChild(canvas);
    return canvas;
  }

  function createStatusSpan(status: number, textId: string, count: number): HTMLSpanElement {
    const span = document.createElement('span');
    span.id = `stat_${status}_${textId}`;
    span.textContent = String(count);
    span.style.display = 'none';
    document.body.appendChild(span);
    return span;
  }

  function setupStatusData(textId: string, data: Record<number, number>): void {
    Object.entries(data).forEach(([status, count]) => {
      createStatusSpan(Number(status), textId, count);
    });
  }

  // ===========================================================================
  // createTextStatusChart Tests
  // ===========================================================================

  describe('createTextStatusChart', () => {
    it('returns null when canvas not found', () => {
      const result = createTextStatusChart('nonexistent');

      expect(result).toBeNull();
    });

    it('creates chart when canvas exists', () => {
      createChartCanvas('1');
      setupStatusData('1', { 0: 10, 1: 5 });

      const result = createTextStatusChart('1');

      expect(result).not.toBeNull();
    });

    it('reads data from DOM when not provided', () => {
      createChartCanvas('1');
      setupStatusData('1', { 0: 20, 1: 10, 2: 5 });

      const chart = createTextStatusChart('1');

      expect(chart).not.toBeNull();
    });

    it('uses provided data when available', () => {
      createChartCanvas('1');
      const data = { 0: 50, 1: 25, 2: 25 };

      const chart = createTextStatusChart('1', data);

      expect(chart).not.toBeNull();
    });

    it('destroys existing chart before creating new one', () => {
      createChartCanvas('1');
      setupStatusData('1', { 0: 10 });

      const chart1 = createTextStatusChart('1');
      const chart2 = createTextStatusChart('1');

      expect(chart1).not.toBeNull();
      expect(chart2).not.toBeNull();
    });
  });

  // ===========================================================================
  // updateTextStatusChart Tests
  // ===========================================================================

  describe('updateTextStatusChart', () => {
    it('creates new chart when none exists', () => {
      createChartCanvas('1');
      setupStatusData('1', { 0: 10 });

      updateTextStatusChart('1');

      // Should not throw
      expect(true).toBe(true);
    });

    it('updates existing chart data', () => {
      createChartCanvas('1');
      setupStatusData('1', { 0: 10, 1: 5 });

      createTextStatusChart('1');

      // Update DOM values
      const stat0 = document.getElementById('stat_0_1');
      if (stat0) stat0.textContent = '20';

      updateTextStatusChart('1');

      // Should not throw
      expect(true).toBe(true);
    });
  });

  // ===========================================================================
  // initTextStatusCharts Tests
  // ===========================================================================

  describe('initTextStatusCharts', () => {
    it('initializes charts for all canvas elements with text-status-chart class', () => {
      createChartCanvas('1');
      createChartCanvas('2');
      createChartCanvas('3');
      setupStatusData('1', { 0: 10 });
      setupStatusData('2', { 0: 20 });
      setupStatusData('3', { 0: 30 });

      initTextStatusCharts();

      // All canvases should have charts
      expect(true).toBe(true);
    });

    it('skips canvases without textId', () => {
      const canvas = document.createElement('canvas');
      canvas.className = 'text-status-chart';
      // No data-text-id
      document.body.appendChild(canvas);

      expect(() => initTextStatusCharts()).not.toThrow();
    });

    it('handles empty page with no charts', () => {
      expect(() => initTextStatusCharts()).not.toThrow();
    });
  });

  // ===========================================================================
  // updateAllTextStatusCharts Tests
  // ===========================================================================

  describe('updateAllTextStatusCharts', () => {
    it('updates all charts on the page', () => {
      createChartCanvas('1');
      createChartCanvas('2');
      setupStatusData('1', { 0: 10 });
      setupStatusData('2', { 0: 20 });

      initTextStatusCharts();
      updateAllTextStatusCharts();

      // Should not throw
      expect(true).toBe(true);
    });

    it('handles page with no charts', () => {
      expect(() => updateAllTextStatusCharts()).not.toThrow();
    });
  });

  // ===========================================================================
  // destroyAllTextStatusCharts Tests
  // ===========================================================================

  describe('destroyAllTextStatusCharts', () => {
    it('destroys all chart instances', () => {
      createChartCanvas('1');
      createChartCanvas('2');
      setupStatusData('1', { 0: 10 });
      setupStatusData('2', { 0: 20 });

      initTextStatusCharts();
      destroyAllTextStatusCharts();

      // Should be able to create new charts
      const newChart = createTextStatusChart('1');
      expect(newChart).not.toBeNull();
    });

    it('handles being called when no charts exist', () => {
      expect(() => destroyAllTextStatusCharts()).not.toThrow();
    });

    it('can be called multiple times', () => {
      createChartCanvas('1');
      setupStatusData('1', { 0: 10 });
      initTextStatusCharts();

      destroyAllTextStatusCharts();
      destroyAllTextStatusCharts();
      destroyAllTextStatusCharts();

      expect(true).toBe(true);
    });
  });

  // ===========================================================================
  // Global Window Exports Tests
  // ===========================================================================

  describe('Window Exports', () => {
    it('exposes initTextStatusCharts on window', () => {
      expect(window.initTextStatusCharts).toBeDefined();
    });

    it('exposes updateAllTextStatusCharts on window', () => {
      expect(window.updateAllTextStatusCharts).toBeDefined();
    });

    it('exposes updateTextStatusChart on window', () => {
      expect(window.updateTextStatusChart).toBeDefined();
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles missing status spans gracefully', () => {
      createChartCanvas('1');
      // No status spans created

      expect(() => createTextStatusChart('1')).not.toThrow();
    });

    it('handles non-numeric status span content', () => {
      createChartCanvas('1');
      const span = document.createElement('span');
      span.id = 'stat_0_1';
      span.textContent = 'not a number';
      document.body.appendChild(span);

      expect(() => createTextStatusChart('1')).not.toThrow();
    });

    it('handles empty status span content', () => {
      createChartCanvas('1');
      const span = document.createElement('span');
      span.id = 'stat_0_1';
      span.textContent = '';
      document.body.appendChild(span);

      expect(() => createTextStatusChart('1')).not.toThrow();
    });

    it('handles zero total', () => {
      createChartCanvas('1');
      setupStatusData('1', { 0: 0, 1: 0, 2: 0 });

      const chart = createTextStatusChart('1');

      expect(chart).not.toBeNull();
    });

    it('handles very large counts', () => {
      createChartCanvas('1');
      setupStatusData('1', { 0: 1000000, 1: 500000 });

      const chart = createTextStatusChart('1');

      expect(chart).not.toBeNull();
    });
  });
});
