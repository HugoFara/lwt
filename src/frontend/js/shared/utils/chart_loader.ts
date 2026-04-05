/**
 * Shared Chart.js Loader - Tree-shaken dynamic import.
 *
 * Registers only the components used in LWT (bar and line charts)
 * instead of the full chart.js bundle, reducing ~203 KB to ~120 KB.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.2
 */

import type { Chart as ChartType } from 'chart.js';

let Chart: typeof ChartType | null = null;

/**
 * Dynamically load Chart.js with only the components LWT uses.
 * Registers: Bar, Line, CategoryScale, LinearScale, Tooltip, Legend.
 */
export async function loadChartJs(): Promise<typeof ChartType> {
  if (Chart) return Chart;

  const {
    Chart: ChartClass,
    BarController,
    BarElement,
    LineController,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
    Filler,
  } = await import('chart.js');

  ChartClass.register(
    BarController,
    BarElement,
    LineController,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
    Filler
  );

  Chart = ChartClass;
  return Chart;
}
