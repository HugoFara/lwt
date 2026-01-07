/**
 * Statistics Charts Module - Alpine.js component with Chart.js visualizations
 *
 * Uses dynamic imports to only load Chart.js (~200KB) when the statistics page
 * is actually visited.
 *
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import type { Chart as ChartType } from 'chart.js';

// Chart.js module reference (loaded dynamically)
let Chart: typeof ChartType | null = null;

/**
 * Dynamically load Chart.js only when needed.
 */
async function loadChartJs(): Promise<typeof ChartType> {
  if (Chart) return Chart;

  const chartModule = await import('chart.js');
  Chart = chartModule.Chart;
  Chart.register(...chartModule.registerables);
  return Chart;
}

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
 * Alpine.js component state interface.
 */
interface StatisticsAppState {
  chartsInitialized: boolean;
  intensityData: IntensityLanguageData[];
  frequencyTotals: FrequencyTotals | null;
  init(this: StatisticsAppState & { $nextTick: (cb: () => void) => void }): void;
  initCharts(): Promise<void>;
}

/**
 * Initialize the intensity chart (stacked vertical bar).
 * Shows term status distribution by language.
 *
 * @param canvasId - The ID of the canvas element
 * @param data - Array of language intensity data
 */
export async function initIntensityChart(
  canvasId: string,
  data: IntensityLanguageData[]
): Promise<ChartType | null> {
  const canvas = document.getElementById(canvasId) as HTMLCanvasElement | null;
  if (!canvas) {
    return null;
  }

  // Dynamically load Chart.js
  const ChartClass = await loadChartJs();

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

  return new ChartClass(canvas, {
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
export async function initFrequencyChart(
  canvasId: string,
  totals: FrequencyTotals
): Promise<ChartType | null> {
  const canvas = document.getElementById(canvasId) as HTMLCanvasElement | null;
  if (!canvas) {
    return null;
  }

  // Dynamically load Chart.js
  const ChartClass = await loadChartJs();

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

  return new ChartClass(canvas, {
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
 * Alpine.js data component for the statistics page.
 * Manages Chart.js initialization after DOM is ready.
 * Chart.js is only loaded when this component is initialized.
 */
export function statisticsApp(): StatisticsAppState {
  return {
    chartsInitialized: false,
    intensityData: [],
    frequencyTotals: null,

    /**
     * Initialize the component - automatically called by Alpine.
     */
    init() {
      // Parse data from JSON script elements
      const intensityDataEl = document.getElementById('statistics-intensity-data');
      const frequencyDataEl = document.getElementById('statistics-frequency-data');

      if (intensityDataEl?.textContent) {
        try {
          const parsed = JSON.parse(intensityDataEl.textContent);
          this.intensityData = parsed.languages || [];
        } catch (e) {
          console.error('Failed to parse intensity chart data:', e);
        }
      }

      if (frequencyDataEl?.textContent) {
        try {
          const parsed = JSON.parse(frequencyDataEl.textContent);
          this.frequencyTotals = parsed.totals || null;
        } catch (e) {
          console.error('Failed to parse frequency chart data:', e);
        }
      }

      // Use $nextTick to ensure DOM is fully ready
      this.$nextTick(() => this.initCharts());
    },

    /**
     * Initialize Chart.js charts.
     * Chart.js is dynamically imported only when needed.
     */
    async initCharts() {
      if (this.chartsInitialized) {
        return;
      }

      // Load charts in parallel for faster initialization
      const chartPromises: Promise<ChartType | null>[] = [];

      if (this.intensityData.length > 0) {
        chartPromises.push(initIntensityChart('intensityChart', this.intensityData));
      }

      if (this.frequencyTotals) {
        chartPromises.push(initFrequencyChart('frequencyChart', this.frequencyTotals));
      }

      await Promise.all(chartPromises);
      this.chartsInitialized = true;
    }
  };
}

/**
 * Register the Alpine.js component.
 */
export function initStatisticsAlpine(): void {
  Alpine.data('statisticsApp', statisticsApp);
}

// Auto-register before Alpine.start() is called
initStatisticsAlpine();

// Legacy function for backward compatibility
export function initStatisticsCharts(): void {
  // No-op - charts are now initialized via Alpine component
  console.warn('initStatisticsCharts() is deprecated. Use x-data="statisticsApp()" instead.');
}
