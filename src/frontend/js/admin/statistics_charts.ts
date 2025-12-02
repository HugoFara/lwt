/**
 * Statistics Charts Module - Renders Chart.js visualizations for statistics page
 *
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { Chart, registerables } from 'chart.js';

// Register all Chart.js components
Chart.register(...registerables);

/**
 * Status colors matching LWT's existing status styles.
 */
const STATUS_COLORS = {
  s1: '#F5B8A9',   // Unknown (status 1) - red/pink
  s2: '#F5CCA9',   // Learning 2 - orange
  s3: '#F5E1A9',   // Learning 3 - yellow
  s4: '#F5F3A9',   // Learning 4 - light yellow
  s5: '#CCFFCC',   // Learned (status 5) - light green
  s99: '#99DDDF', // Well Known (status 99) - cyan
  s98: '#E5E5E5'  // Ignored (status 98) - gray
};

/**
 * Data structure for intensity statistics per language.
 */
interface IntensityLanguageData {
  name: string;
  s1: number;
  s2: number;
  s3: number;
  s4: number;
  s5: number;
  s99: number;
}

/**
 * Data structure for frequency statistics totals.
 */
interface FrequencyTotals {
  ct: number;  // Created today
  at: number;  // Activity today
  kt: number;  // Known today
  cy: number;  // Created yesterday
  ay: number;  // Activity yesterday
  ky: number;  // Known yesterday
  cw: number;  // Created this week
  aw: number;  // Activity this week
  kw: number;  // Known this week
  cm: number;  // Created this month
  am: number;  // Activity this month
  km: number;  // Known this month
  ca: number;  // Created this year
  aa: number;  // Activity this year
  ka: number;  // Known this year
}

/**
 * Initialize the intensity chart (stacked vertical bar).
 * Shows term status distribution by language.
 *
 * @param canvasId - The ID of the canvas element
 * @param data - Array of language intensity data
 */
export function initIntensityChart(
  canvasId: string,
  data: IntensityLanguageData[]
): Chart | null {
  const canvas = document.getElementById(canvasId) as HTMLCanvasElement | null;
  if (!canvas) {
    return null;
  }

  const labels = data.map(lang => lang.name);

  const chartData = {
    labels,
    datasets: [
      {
        label: 'Unknown (1)',
        data: data.map(lang => lang.s1),
        backgroundColor: STATUS_COLORS.s1
      },
      {
        label: 'Learning (2)',
        data: data.map(lang => lang.s2),
        backgroundColor: STATUS_COLORS.s2
      },
      {
        label: 'Learning (3)',
        data: data.map(lang => lang.s3),
        backgroundColor: STATUS_COLORS.s3
      },
      {
        label: 'Learning (4)',
        data: data.map(lang => lang.s4),
        backgroundColor: STATUS_COLORS.s4
      },
      {
        label: 'Learned (5)',
        data: data.map(lang => lang.s5),
        backgroundColor: STATUS_COLORS.s5
      },
      {
        label: 'Well Known (99)',
        data: data.map(lang => lang.s99),
        backgroundColor: STATUS_COLORS.s99
      }
    ]
  };

  return new Chart(canvas, {
    type: 'bar',
    data: chartData,
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'bottom'
        },
        tooltip: {
          callbacks: {
            afterBody: function (context) {
              const dataIndex = context[0].dataIndex;
              const total = chartData.datasets.reduce(
                (sum, ds) => sum + (ds.data[dataIndex] as number),
                0
              );
              return 'Total active: ' + total;
            }
          }
        }
      },
      scales: {
        x: {
          stacked: true
        },
        y: {
          stacked: true,
          title: {
            display: true,
            text: 'Number of Terms'
          }
        }
      }
    }
  });
}

/**
 * Initialize the frequency chart (line chart).
 * Shows learning activity evolution over time.
 *
 * @param canvasId - The ID of the canvas element
 * @param totals - The frequency totals data
 */
export function initFrequencyChart(
  canvasId: string,
  totals: FrequencyTotals
): Chart | null {
  const canvas = document.getElementById(canvasId) as HTMLCanvasElement | null;
  if (!canvas) {
    return null;
  }

  const chartData = {
    labels: ['Today', 'Yesterday', 'Last 7 Days', 'Last 30 Days', 'Last 365 Days'],
    datasets: [
      {
        label: 'Created',
        data: [totals.ct, totals.cy, totals.cw, totals.cm, totals.ca],
        borderColor: STATUS_COLORS.s1,
        backgroundColor: STATUS_COLORS.s1,
        tension: 0.3,
        fill: false
      },
      {
        label: 'Activity',
        data: [totals.at, totals.ay, totals.aw, totals.am, totals.aa],
        borderColor: STATUS_COLORS.s3,
        backgroundColor: STATUS_COLORS.s3,
        tension: 0.3,
        fill: false
      },
      {
        label: 'Known',
        data: [totals.kt, totals.ky, totals.kw, totals.km, totals.ka],
        borderColor: STATUS_COLORS.s5,
        backgroundColor: STATUS_COLORS.s5,
        tension: 0.3,
        fill: false
      }
    ]
  };

  return new Chart(canvas, {
    type: 'line',
    data: chartData,
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'bottom'
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: 'Number of Terms'
          }
        }
      }
    }
  });
}

/**
 * Initialize statistics charts from data attributes on the page.
 * Looks for elements with data-statistics-intensity and data-statistics-frequency.
 */
export function initStatisticsCharts(): void {
  // Initialize intensity chart
  const intensityDataEl = document.getElementById('statistics-intensity-data');
  if (intensityDataEl) {
    try {
      const intensityData = JSON.parse(
        intensityDataEl.dataset.languages || '[]'
      ) as IntensityLanguageData[];
      if (intensityData.length > 0) {
        initIntensityChart('intensityChart', intensityData);
      }
    } catch (e) {
      console.error('Failed to parse intensity chart data:', e);
    }
  }

  // Initialize frequency chart
  const frequencyDataEl = document.getElementById('statistics-frequency-data');
  if (frequencyDataEl) {
    try {
      const frequencyTotals = JSON.parse(
        frequencyDataEl.dataset.totals || '{}'
      ) as FrequencyTotals;
      initFrequencyChart('frequencyChart', frequencyTotals);
    } catch (e) {
      console.error('Failed to parse frequency chart data:', e);
    }
  }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', initStatisticsCharts);
