/**
 * Text Status Chart - Chart.js visualizations for text word status distribution.
 *
 * Renders horizontal stacked bar charts showing the distribution of word statuses
 * (Unknown, Learning 1-5, Well Known, Ignored) for each text card.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { Chart, registerables } from 'chart.js';

// Register all Chart.js components
Chart.register(...registerables);

/**
 * Status colors matching LWT's existing barchart CSS styles.
 */
const STATUS_COLORS: Record<number, string> = {
  0: '#8bdadc',   // Unknown - cyan
  1: '#f24e4e',   // Learning 1 - red
  2: '#ffac80',   // Learning 2 - orange
  3: '#ffe199',   // Learning 3 - light orange
  4: '#fffd77',   // Learning 4 - yellow
  5: '#9f9',      // Learned 5 - green
  98: '#aaa',     // Ignored - gray
  99: '#999'      // Well Known - dark gray
};

/**
 * Status labels for tooltips.
 */
const STATUS_LABELS: Record<number, string> = {
  0: 'Unknown',
  1: 'Learning (1)',
  2: 'Learning (2)',
  3: 'Learning (3)',
  4: 'Learning (4)',
  5: 'Learned (5)',
  98: 'Ignored',
  99: 'Well Known'
};

/**
 * Status descriptions for tooltips.
 */
const STATUS_DESCRIPTIONS: Record<number, string> = {
  0: 'Words not yet saved to your vocabulary',
  1: 'New words - just started learning',
  2: 'Words you are beginning to recognize',
  3: 'Words you are getting familiar with',
  4: 'Words you almost know',
  5: 'Words you have fully learned',
  98: 'Words marked as ignored (punctuation, names, etc.)',
  99: 'Words you already know well'
};

/**
 * Status order for chart segments.
 */
const STATUS_ORDER = [0, 1, 2, 3, 4, 5, 99, 98];

/**
 * Map of text ID to Chart instance for cleanup/updates.
 */
const chartInstances: Map<string, Chart> = new Map();

/**
 * Get status data from hidden span elements.
 *
 * @param textId - The text ID
 * @returns Object with status counts
 */
function getStatusDataFromDOM(textId: string): Record<number, number> {
  const data: Record<number, number> = {};

  STATUS_ORDER.forEach(status => {
    const el = document.getElementById(`stat_${status}_${textId}`);
    if (el) {
      data[status] = parseInt(el.textContent || '0', 10);
    } else {
      data[status] = 0;
    }
  });

  return data;
}

/**
 * Create or update a status chart for a text.
 *
 * @param textId - The text ID
 * @param data - Optional status data (if not provided, reads from DOM)
 * @returns The Chart instance or null if canvas not found
 */
export function createTextStatusChart(
  textId: string,
  data?: Record<number, number>
): Chart | null {
  const canvas = document.getElementById(`chart_${textId}`) as HTMLCanvasElement | null;
  if (!canvas) {
    return null;
  }

  // Get data from DOM if not provided
  const statusData = data || getStatusDataFromDOM(textId);

  // Check if there's any data
  const total = Object.values(statusData).reduce((sum, val) => sum + val, 0);

  // Destroy existing chart if present
  const existingChart = chartInstances.get(textId);
  if (existingChart) {
    existingChart.destroy();
  }

  // Create datasets for each status
  const datasets = STATUS_ORDER.map(status => ({
    label: STATUS_LABELS[status],
    data: [statusData[status] || 0],
    backgroundColor: STATUS_COLORS[status],
    borderWidth: 0,
    barPercentage: 1.0,
    categoryPercentage: 1.0
  }));

  const chart = new Chart(canvas, {
    type: 'bar',
    data: {
      labels: [''],
      datasets
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          enabled: total > 0,
          callbacks: {
            title: function(context) {
              const datasetIndex = context[0].datasetIndex;
              const status = STATUS_ORDER[datasetIndex];
              return STATUS_DESCRIPTIONS[status] || '';
            },
            label: function(context) {
              const value = context.raw as number;
              const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0';
              return `${context.dataset.label}: ${value} (${percentage}%)`;
            }
          }
        }
      },
      scales: {
        x: {
          stacked: true,
          display: false,
          grid: {
            display: false
          }
        },
        y: {
          stacked: true,
          display: false,
          grid: {
            display: false
          }
        }
      },
      animation: {
        duration: 300
      },
      layout: {
        padding: 0
      }
    }
  });

  chartInstances.set(textId, chart);
  return chart;
}

/**
 * Update an existing text status chart with new data.
 *
 * @param textId - The text ID
 */
export function updateTextStatusChart(textId: string): void {
  const chart = chartInstances.get(textId);
  const statusData = getStatusDataFromDOM(textId);

  if (chart) {
    // Update existing chart
    STATUS_ORDER.forEach((status, index) => {
      if (chart.data.datasets[index]) {
        chart.data.datasets[index].data = [statusData[status] || 0];
      }
    });
    chart.update('none');
  } else {
    // Create new chart
    createTextStatusChart(textId, statusData);
  }
}

/**
 * Initialize all text status charts on the page.
 * Looks for canvas elements with class 'text-status-chart'.
 */
export function initTextStatusCharts(): void {
  const canvases = document.querySelectorAll<HTMLCanvasElement>('.text-status-chart');

  canvases.forEach(canvas => {
    const textId = canvas.dataset.textId;
    if (textId) {
      createTextStatusChart(textId);
    }
  });
}

/**
 * Update all text status charts on the page.
 * Called after word counts are loaded via AJAX.
 */
export function updateAllTextStatusCharts(): void {
  const canvases = document.querySelectorAll<HTMLCanvasElement>('.text-status-chart');

  canvases.forEach(canvas => {
    const textId = canvas.dataset.textId;
    if (textId) {
      updateTextStatusChart(textId);
    }
  });
}

/**
 * Destroy all chart instances (for cleanup).
 */
export function destroyAllTextStatusCharts(): void {
  chartInstances.forEach(chart => {
    chart.destroy();
  });
  chartInstances.clear();
}

// Expose functions globally for use by text_display.ts
declare global {
  interface Window {
    initTextStatusCharts: typeof initTextStatusCharts;
    updateAllTextStatusCharts: typeof updateAllTextStatusCharts;
    updateTextStatusChart: typeof updateTextStatusChart;
  }
}

window.initTextStatusCharts = initTextStatusCharts;
window.updateAllTextStatusCharts = updateAllTextStatusCharts;
window.updateTextStatusChart = updateTextStatusChart;

// Auto-initialize on DOM ready
document.addEventListener('DOMContentLoaded', initTextStatusCharts);
