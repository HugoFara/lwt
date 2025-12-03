/**
 * HTML escaping utility functions for LWT.
 *
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   2.10.0-fork Extracted from pgm.ts
 */

/**
 * Replace html characters with encodings
 *
 * See https://stackoverflow.com/questions/1787322/what-is-the-htmlspecialchars-equivalent-in-javascript
 *
 * @param s String to be escaped
 * @returns Escaped string
 */
export function escape_html_chars(s: string): string {
  const map: Record<string, string> = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
    '\x0d': '<br />' // This one inserts HTML, delete? (2.9.0)
  };

  // eslint-disable-next-line no-control-regex -- intentionally matching carriage return
  return s.replace(/[&<>"'\x0d]/g, function (m) { return map[m]; });
}

/**
 * Escape the HTML characters, with an eventual annotation
 *
 * @param title String to be escaped
 * @param ann   An annotation to show in red
 * @returns Escaped string
 */
export function escape_html_chars_2(title: string, ann: string): string {
  if (ann !== '') {
    const ann2 = escape_html_chars(ann);
    return escape_html_chars(title).replace(ann2,
      '<span style="color:red">' + ann2 + '</span>');
  }
  return escape_html_chars(title);
}

/**
 * Escape only single apostrophe ("'") from string
 *
 * @param s String to be escaped
 * @returns Escaped string
 */
export function escape_apostrophes(s: string): string {
  return s.replace(/'/g, "\\'");
}

/**
 * Render a comma-separated tag list as Bulma tag components.
 *
 * @param tagList Comma-separated tag list (e.g., "tag1,tag2,tag3")
 * @returns HTML string with Bulma tags
 */
export function renderBulmaTags(tagList: string): string {
  if (!tagList || tagList.trim() === '') {
    return '';
  }

  const tags = tagList.split(',').map(t => t.trim()).filter(t => t !== '');
  if (tags.length === 0) {
    return '';
  }

  return tags
    .map(tag => `<span class="tag is-info is-light is-small">${escape_html_chars(tag)}</span>`)
    .join('');
}

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
 * Order of statuses in the chart (left to right).
 */
const STATUS_ORDER = [0, 1, 2, 3, 4, 5, 99, 98];

/**
 * Statistics data from the API.
 */
interface StatsData {
  total: number;
  unknown: number;
  statusCounts: Record<string, number>;
}

/**
 * Render a horizontal stacked bar chart showing word status distribution.
 *
 * Uses CSS classes bc0-bc5, bc98, bc99 for colors (defined in css_charts.css).
 *
 * @param stats Statistics object with total, unknown, and statusCounts
 * @returns HTML string for the status bar chart
 */
export function renderStatusBarChart(stats: StatsData | null | undefined): string {
  if (!stats || stats.total === 0) {
    return '<div class="status-bar-chart empty"></div>';
  }

  const { total, unknown, statusCounts } = stats;

  // Build segments for each status
  const segments: string[] = [];

  for (const status of STATUS_ORDER) {
    let count: number;
    if (status === 0) {
      count = unknown;
    } else {
      count = statusCounts[String(status)] || 0;
    }

    if (count > 0) {
      const percent = (count / total) * 100;
      const label = STATUS_LABELS[status];
      segments.push(
        `<div class="status-segment bc${status}" ` +
        `style="width: ${percent.toFixed(2)}%" ` +
        `title="${label}: ${count} (${percent.toFixed(1)}%)"></div>`
      );
    }
  }

  return `<div class="status-bar-chart">${segments.join('')}</div>`;
}

